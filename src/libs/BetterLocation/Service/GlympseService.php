<?php declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use GlympseApi\Glympse;
use GlympseApi\GlympseApiException;
use GlympseApi\Types\TicketInvite;
use Tracy\Debugger;
use Tracy\ILogger;
use Utils\General;

final class GlympseService extends AbstractService
{
	const NAME = 'Glympse';

	const LINK = 'https://glympse.com';

	const PATH_INVITE_ID_REGEX = '/^\/[0-9a-z]{4}-[0-9a-z]{4}$/i';
	const PATH_GROUP_REGEX = '/^\/![0-9a-z]+$/i';

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

	private static function processInviteLocation(string $url, TicketInvite $invite): BetterLocation {
		$now = new \DateTimeImmutable();
		$currentLocationDescriptions = [];
		$willExpireWarningInterval = new \DateInterval('PT30M');
		if ($invite->properties->endTime < $now) {
			$currentLocationDescriptions[] = sprintf('%s Glympse expired at %s', \Icons::WARNING, $invite->properties->endTime->format(\Config::DATETIME_FORMAT_ZONE));
		} else if ($invite->properties->endTime < ((clone $now)->add($willExpireWarningInterval))) {
			$currentLocationDescriptions[] = sprintf('%s Glympse will expire soon, at %s', \Icons::WARNING, $invite->properties->endTime->format(\Config::TIME_FORMAT_ZONE));
		}
		$lastLocation = $invite->getLastLocation();
		$currentLocation = new BetterLocation($url, $lastLocation->latitude, $lastLocation->longtitude, self::class);
		$currentLocationDescriptions[] = sprintf('Last update: %s', $lastLocation->timestamp->format(\Config::DATETIME_FORMAT_ZONE));
		if ($invite->properties->message) {
			$currentLocationDescriptions[] = sprintf('Glympse message: %s', htmlentities($invite->properties->message));
		}
		$currentLocation->setPrefixMessage($currentLocation->getPrefixMessage() . ' ' . $invite->properties->name);
		$currentLocation->setDescription(join(PHP_EOL, $currentLocationDescriptions));
		return $currentLocation;
	}

	private static function processInviteDestinationLocation(string $url, TicketInvite $invite): BetterLocation {
		$destinationDescriptions = [];
		$destination = new BetterLocation($url, $invite->properties->destination->lat, $invite->properties->destination->lng, self::class);
		$destination->setPrefixMessage($destination->getPrefixMessage() . ' destination');
		if ($invite->properties->destination->name) {
			$destinationDescriptions[] = $invite->properties->destination->name;
		}
		if ($invite->properties->eta && $invite->properties->route) {
			$invite->properties->route->distance = 6578;
			if ($invite->properties->route->distance >= 100000) { // 100 km
				$distanceString = sprintf('%d km', round($invite->properties->route->distance / 1000));
			} else if ($invite->properties->route->distance >= 100) { // 1 km
				$distanceString = sprintf('%s km', round($invite->properties->route->distance / 1000, 2));
			} else {
				$distanceString = sprintf('%d m', $invite->properties->route->distance);
			}
			$destinationDescriptions[] = sprintf('Distance: %s, calculated ETA: %s', $distanceString, General::sToHuman(intval($invite->properties->eta->eta->format('%s'))));
		}
		$destination->setDescription(join(PHP_EOL, $destinationDescriptions));
		return $destination;
	}

	private static function processInvite($url): BetterLocationCollection {
		$glympseApi = \Factory::Glympse();
		$glympseApi->loadToken();
		$betterLocationCollection = new BetterLocationCollection();
		$inviteId = Glympse::getInviteIdFromUrl($url);
		try {
			$inviteResponse = $glympseApi->loadInvite($inviteId);
			$betterLocationCollection->add(self::processInviteLocation($url, $inviteResponse));
			if ($inviteResponse->properties->destination) {
				$betterLocationCollection->add(self::processInviteDestinationLocation($url, $inviteResponse));
			}
			return $betterLocationCollection;
		} catch (GlympseApiException $exception) {
			throw new InvalidLocationException(sprintf('Error while processing %s invite code %s: %s', self::NAME, htmlentities($inviteId), $exception->getMessage()));
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
	}

	private static function processGroup($url): BetterLocationCollection {
		$glympseApi = \Factory::Glympse();
		$glympseApi->loadToken();
		$betterLocationCollection = new BetterLocationCollection();
		$groupId = Glympse::getGroupIdFromUrl($url);
		try {
			$groupsResponse = $glympseApi->loadGroup($groupId);
			foreach ($groupsResponse->members as $member) {
				$inviteResponse = $glympseApi->loadInvite($member->invite);
				$betterLocationCollection->add(self::processInviteLocation($url, $inviteResponse));
			}
			return $betterLocationCollection;
		} catch (GlympseApiException $exception) {
			throw new InvalidLocationException(sprintf('Error while processing %s tag !%s: %s', self::NAME, htmlentities($groupId), $exception->getMessage()));
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
	}

}
