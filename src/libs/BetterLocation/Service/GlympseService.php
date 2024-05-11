<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Config;
use App\Icons;
use App\Utils\Utils;
use DJTommek\GlympseApi\GlympseApi;
use DJTommek\GlympseApi\GlympseApiException;
use DJTommek\GlympseApi\Types\TicketInvite;
use Tracy\Debugger;
use Tracy\ILogger;

final class GlympseService extends AbstractService
{
	const ID = 27;
	const NAME = 'Glympse';

	const LINK = 'https://glympse.com';

	const TYPE_GROUP = 'group';
	const TYPE_INVITE = 'invite';
	const TYPE_DESTINATION = 'destination';

	public function __construct(
		private readonly ?GlympseApi $glympseApi = null,
	) {
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_INVITE,
			self::TYPE_GROUP,
			self::TYPE_DESTINATION,
		];
	}

	const PATH_INVITE_ID_REGEX = '/^\/([0-9a-z]+-[0-9a-z]+)$/i';
	const PATH_GROUP_REGEX = '/^\/!(.+)$/i';

	public function isValid(): bool
	{
		if ($this->url && $this->url->getDomain() === 'glympse.com') {
			if (preg_match(self::PATH_INVITE_ID_REGEX, $this->url->getPath(), $matches)) {
				$this->data->inviteId = $matches[1];
				return true;
			} else if (preg_match(self::PATH_GROUP_REGEX, $this->url->getPath(), $matches)) {
				$this->data->groupName = $matches[1];
				return true;
			}
		}
		return false;
	}

	public function process(): void
	{
		if ($this->glympseApi === null) {
			throw new \RuntimeException('Glympse API is not available.');
		}

		if ($this->data->inviteId ?? null) {
			$this->processInvite();
		} else if ($this->data->groupName ?? null) {
			$this->processGroup();
		} else {
			throw new \LogicException(sprintf('Invalid %s link.', self::NAME));
		}
	}

	private function processInviteLocation(string $type, TicketInvite $invite): BetterLocation
	{
		if (in_array($type, self::getConstants()) === false) {
			throw new \OutOfBoundsException(sprintf('Invalid %s service type "%s"', self::NAME, $type));
		}
		$now = new \DateTimeImmutable();
		$currentLocationDescriptions = [];
		$willExpireWarningInterval = new \DateInterval('PT30M');
		$diff = $invite->properties->endTime->getTimestamp() - $now->getTimestamp();
		$glympseExpired = false;
		if ($diff <= 0) {
			$glympseExpired = true;
			$currentLocationDescriptions[] = sprintf('%s Glympse expired at %s (%s ago)',
				Icons::WARNING,
				$invite->properties->endTime->format(Config::DATETIME_FORMAT_ZONE),
				preg_replace('/ [0-9]+s$/', '', Utils::sToHuman($diff * -1))
			);
		} else if ($invite->properties->endTime < ((clone $now)->add($willExpireWarningInterval))) {
			$currentLocationDescriptions[] = sprintf('%s Glympse will expire soon, at %s', Icons::WARNING, $invite->properties->endTime->format(Config::TIME_FORMAT_ZONE));
		}
		$lastLocation = $invite->getLastLocation();
		$currentLocation = new BetterLocation($this->inputUrl, $lastLocation->latitude, $lastLocation->longtitude, self::class, $type);
		$currentLocation->setRefreshable(true);
		$diff = $now->getTimestamp() - $lastLocation->timestamp->getTimestamp();
		if (
			$diff > 600 &&  // show last update message only if it was updated long ago.
			$glympseExpired === false // If Glympse is expired, there is already warning message so no need to duplicate that info
		) {
			$lastUpdateText = sprintf('%s Last location update: %s (%s ago)',
				Icons::WARNING,
				$lastLocation->timestamp->format(Config::DATETIME_FORMAT_ZONE),
				preg_replace('/ [0-9]+s$/', '', Utils::sToHuman($diff))
			);
			$currentLocationDescriptions[] = $lastUpdateText;
		}
		if ($invite->properties->message) {
			$currentLocationDescriptions[] = sprintf('Glympse message: %s', htmlentities($invite->properties->message));
		}
		if ($type === self::TYPE_GROUP) {
			$prefix = sprintf('Glympse <a href="%s">!%s</a> (<a href="%s">%s</a>)',
				$this->inputUrl->getAbsoluteUrl(), // assuming, that this url is https://glympse.com/!someTag
				self::getGroupIdFromUrl($this->inputUrl->getAbsoluteUrl()),
				$invite->getInviteIdUrl(),
				$invite->properties->name
			);
		} else {
			$prefix = sprintf('Glympse (<a href="%s">%s</a>)', $invite->getInviteIdUrl(), $invite->properties->name);
		}
		$currentLocation->setPrefixMessage($prefix);
		$currentLocation->setDescription(join(PHP_EOL, $currentLocationDescriptions));
		return $currentLocation;
	}

	private function processInviteDestinationLocation(TicketInvite $invite): BetterLocation
	{
		$now = new \DateTimeImmutable();
		$destinationDescriptions = [];
		$destination = new BetterLocation($this->inputUrl, $invite->properties->destination->lat, $invite->properties->destination->lng, self::class, self::TYPE_DESTINATION);
		$destination->setRefreshable(true);
		$destination->setPrefixMessage(sprintf('Glympse destination (<a href="%s">%s</a>)',
			$invite->getInviteIdUrl(),
			$invite->properties->name,
		));
		if ($invite->properties->destination->name) {
			$destinationDescriptions[] = $invite->properties->destination->name;
		}
		if ($invite->properties->eta && $invite->properties->route) {
			if ($invite->properties->route->distance >= 100000) { // 100 km
				$distanceString = sprintf('%d km', round($invite->properties->route->distance / 1000));
			} else if ($invite->properties->route->distance >= 100) { // 1 km
				$distanceString = sprintf('%s km', round($invite->properties->route->distance / 1000, 2));
			} else {
				$distanceString = sprintf('%d m', $invite->properties->route->distance);
			}

			$etaInfo = sprintf('Distance: %s, ETA: %s (%s)',
				$distanceString,
				Utils::sToHuman(intval($invite->properties->eta->eta->format('%s'))),
				$invite->properties->eta->etaTs->add($invite->properties->eta->eta)->format(Config::TIME_FORMAT_ZONE),
			);
			$diff = $now->getTimestamp() - $invite->properties->eta->etaTs->getTimestamp();
			if ($diff > 600) {
				$etaInfo .= sprintf(' %s Calculated %s ago', Icons::WARNING, preg_replace('/ [0-9]+s$/', '', Utils::sToHuman($diff)));
			}
			$destinationDescriptions[] = $etaInfo;
		}
		$destination->setDescription(join(PHP_EOL, $destinationDescriptions));
		return $destination;
	}

	public function processInvite(): void
	{
		try {
			$inviteResponse = $this->glympseApi->invites($this->data->inviteId, null, null, null, true);
			$inviteLocation = $this->processInviteLocation(self::TYPE_INVITE, $inviteResponse);
			$this->collection->add($inviteLocation);
			if ($inviteResponse->properties->destination) {
				$this->collection->add($this->processInviteDestinationLocation($inviteResponse));
			}
		} catch (GlympseApiException $exception) {
			if ($this->isExceptionWhitelisted($exception) === false) {
				Debugger::log($exception, ILogger::DEBUG);
				throw new InvalidLocationException(sprintf(
					'Error while processing %s invite code "%s": "%s"',
					self::NAME,
					htmlentities($this->data->inviteId),
					$exception->getMessage()
				));
			}
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
	}

	public function processGroup(): void
	{
		try {
			$groupsResponse = $this->glympseApi->groups($this->data->groupName);
			foreach ($groupsResponse->members as $member) {
				try {
					$inviteResponse = $this->glympseApi->invites($member->invite, null, null, null, true);
					$inviteLocation = $this->processInviteLocation(self::TYPE_GROUP, $inviteResponse);
					$this->collection->add($inviteLocation);
					if ($inviteResponse->properties->destination) {
						$this->collection->add($this->processInviteDestinationLocation($inviteResponse));
					}
				} catch (GlympseApiException $exception) {
					if ($this->isExceptionWhitelisted($exception) === false) {
						Debugger::log($exception, ILogger::DEBUG);
					}
				}
			}
		} catch (GlympseApiException $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf(
				'Error while processing %s tag "!%s": "%s"',
				self::NAME,
				htmlentities($this->data->groupName),
				$exception->getMessage()
			));
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
	}

	public static function getInviteIdFromUrl(string $url): ?string
	{
		$parsedUrl = Utils::parseUrl($url);
		// no need to check domain and path, it already has been done earlier
		if (preg_match(GlympseService::PATH_INVITE_ID_REGEX, $parsedUrl['path'])) {
			return mb_substr($parsedUrl['path'], 1); // remove "/"
		}
		return null;
	}

	public static function getGroupIdFromUrl(string $url): ?string
	{
		$parsedUrl = Utils::parseUrl($url);
		// no need to check domain and path, it already has been done earlier
		if (preg_match(GlympseService::PATH_GROUP_REGEX, $parsedUrl['path'])) {
			return urldecode(mb_substr($parsedUrl['path'], 2)); // remove "/!"
		}
		return null;
	}

	private function isExceptionWhitelisted(\Exception $exception): bool
	{
		return (
			$exception instanceof GlympseApiException
			&& str_contains($exception->getMessage(), 'The specified invite code is no longer available')
		);
	}
}
