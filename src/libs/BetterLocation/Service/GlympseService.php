<?php declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use DJTommek\GlympseApi\GlympseApiException;
use DJTommek\GlympseApi\Types\TicketInvite;
use Tracy\Debugger;
use Tracy\ILogger;
use Utils\General;

final class GlympseService extends AbstractService
{
	const NAME = 'Glympse';

	const LINK = 'https://glympse.com';

	const TYPE_GROUP = 'group';
	const TYPE_INVITE = 'invite';
	const TYPE_DESTINATION = 'destination';

	public static function getConstants(): array {
		return [
			self::TYPE_INVITE,
			self::TYPE_GROUP,
			self::TYPE_DESTINATION,
		];
	}

	const PATH_INVITE_ID_REGEX = '/^\/[0-9a-z]+-[0-9a-z]+$/i';
	const PATH_GROUP_REGEX = '/^\/!.+$/i';

	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	public static function isValid(string $url): bool {
		return self::isUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws NotImplementedException
	 */
	public static function parseCoords(string $url): BetterLocation {
		throw new NotImplementedException('Parsing single coordinate is not supported. Use parseMultipleCoords() instead.');
	}

	public static function isUrl(string $url): bool {
		return self::isCorrectDomainUrl($url) && (self::isInviteIdUrl($url) || self::isGroupUrl($url));
	}

	private static function isCorrectDomainUrl($url): bool {
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array(mb_strtolower($parsedUrl['host']), ['glympse.com', 'www.glympse.com']) &&
			isset($parsedUrl['path'])
		);
	}

	public static function isInviteIdUrl(string $url): bool {
		$parsedUrl = General::parseUrl($url);
		return (!!preg_match(self::PATH_INVITE_ID_REGEX, $parsedUrl['path']));
	}

	public static function isGroupUrl(string $url): bool {
		$parsedUrl = General::parseUrl($url);
		return (!!preg_match(self::PATH_GROUP_REGEX, $parsedUrl['path']));
	}

	public static function parseCoordsMultiple(string $url): BetterLocationCollection {
		if (self::isInviteIdUrl($url)) {
			return self::processInvite($url);
		} else if (self::isGroupUrl($url)) {
			return self::processGroup($url);
		} else {
			throw new \LogicException(sprintf('Invalid %s link.', self::NAME));
		}
	}

	public static function processInviteLocation(string $url, string $type, TicketInvite $invite): BetterLocation {
		if (in_array($type, self::getConstants()) === false) {
			throw new \OutOfBoundsException(sprintf('Invalid %s service type "%s"', self::NAME, $type));
		}
		$now = new \DateTimeImmutable();
		$currentLocationDescriptions = [];
		$willExpireWarningInterval = new \DateInterval('PT30M');
		$diff = $invite->properties->endTime->getTimestamp() - $now->getTimestamp();
		if ($diff <= 0) {
			$currentLocationDescriptions[] = sprintf('%s Glympse expired at %s (%s ago)',
				\Icons::WARNING,
				$invite->properties->endTime->format(\Config::DATETIME_FORMAT_ZONE),
				preg_replace('/ [0-9]+s$/', '', General::sToHuman($diff * -1))
			);
		} else if ($invite->properties->endTime < ((clone $now)->add($willExpireWarningInterval))) {
			$currentLocationDescriptions[] = sprintf('%s Glympse will expire soon, at %s', \Icons::WARNING, $invite->properties->endTime->format(\Config::TIME_FORMAT_ZONE));
		}
		$lastLocation = $invite->getLastLocation();
		$currentLocation = new BetterLocation($url, $lastLocation->latitude, $lastLocation->longtitude, self::class, $type);
		$diff = $now->getTimestamp() - $lastLocation->timestamp->getTimestamp();
		if ($diff > 600) { // show last update message only if it was updated long ago
			$lastUpdateText = sprintf('%s Last location update: %s (%s ago)',
				\Icons::WARNING,
				$lastLocation->timestamp->format(\Config::DATETIME_FORMAT_ZONE),
				preg_replace('/ [0-9]+s$/', '', General::sToHuman($diff))
			);
			$currentLocationDescriptions[] = $lastUpdateText;
		}
		if ($invite->properties->message) {
			$currentLocationDescriptions[] = sprintf('Glympse message: %s', htmlentities($invite->properties->message));
		}
		if ($type === self::TYPE_GROUP) {
			$prefix = sprintf('Glympse <a href="%s">!%s</a> (<a href="%s">%s</a>)',
				$url, // assuming, that this url is https://glympse.com/!someTag
				self::getGroupIdFromUrl($url),
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

	public static function processInviteDestinationLocation(string $url, TicketInvite $invite): BetterLocation {
		$now = new \DateTimeImmutable();
		$destinationDescriptions = [];
		$destination = new BetterLocation($url, $invite->properties->destination->lat, $invite->properties->destination->lng, self::class, self::TYPE_DESTINATION);
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
				General::sToHuman(intval($invite->properties->eta->eta->format('%s'))),
				$invite->properties->eta->etaTs->add($invite->properties->eta->eta)->format(\Config::TIME_FORMAT_ZONE),
			);
			$diff = $now->getTimestamp() - $invite->properties->eta->etaTs->getTimestamp();
			if ($diff > 600) {
				$etaInfo .= sprintf(' %s Calculated %s ago', \Icons::WARNING, preg_replace('/ [0-9]+s$/', '', General::sToHuman($diff)));
			}
			$destinationDescriptions[] = $etaInfo;
		}
		$destination->setDescription(join(PHP_EOL, $destinationDescriptions));
		return $destination;
	}

	public static function processInvite($url): BetterLocationCollection {
		$glympseApi = \Factory::Glympse();
		$glympseApi->loadToken();
		$betterLocationCollection = new BetterLocationCollection();
		$inviteId = self::getInviteIdFromUrl($url);
		try {
			$inviteResponse = $glympseApi->loadInvite($inviteId);
			$inviteLocation = self::processInviteLocation($url, self::TYPE_INVITE, $inviteResponse);
			$betterLocationCollection->add($inviteLocation);
			if ($inviteResponse->properties->destination) {
				$betterLocationCollection->add(self::processInviteDestinationLocation($url, $inviteResponse));
			}
			return $betterLocationCollection;
		} catch (GlympseApiException $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Error while processing %s invite code %s: %s', self::NAME, htmlentities($inviteId), $exception->getMessage()));
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
	}

	public static function processGroup($url): BetterLocationCollection {
		$glympseApi = \Factory::Glympse();
		$glympseApi->loadToken();
		$betterLocationCollection = new BetterLocationCollection();
		$groupId = self::getGroupIdFromUrl($url);
		try {
			$groupsResponse = $glympseApi->loadGroup($groupId);
			foreach ($groupsResponse->members as $member) {
				$inviteResponse = $glympseApi->loadInvite($member->invite);
				$inviteLocation = self::processInviteLocation($url, self::TYPE_GROUP, $inviteResponse);
				$betterLocationCollection->add($inviteLocation);
				if ($inviteResponse->properties->destination) {
					$betterLocationCollection->add(self::processInviteDestinationLocation($url, $inviteResponse));
				}
			}
			return $betterLocationCollection;
		} catch (GlympseApiException $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Error while processing %s tag !%s: %s', self::NAME, htmlentities($groupId), $exception->getMessage()));
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
	}

	public static function getInviteIdFromUrl(string $url): ?string {
		$parsedUrl = General::parseUrl($url);
		if (
			isset($parsedUrl['host']) &&
			in_array(mb_strtolower($parsedUrl['host']), ['glympse.com', 'www.glympse.com']) &&
			isset($parsedUrl['path']) &&
			preg_match(GlympseService::PATH_INVITE_ID_REGEX, $parsedUrl['path'])
		) {
			return mb_substr($parsedUrl['path'], 1);
		}
		return null;
	}

	public static function getGroupIdFromUrl(string $url): ?string {
		$parsedUrl = General::parseUrl($url);
		if (
			isset($parsedUrl['host']) &&
			in_array(mb_strtolower($parsedUrl['host']), ['glympse.com', 'www.glympse.com']) &&
			isset($parsedUrl['path']) &&
			preg_match(GlympseService::PATH_GROUP_REGEX, $parsedUrl['path'])
		) {
			return urldecode(mb_substr($parsedUrl['path'], 2));
		}
		return null;
	}
}
