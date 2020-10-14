<?php declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use GlympseApi\Glympse;
use GlympseApi\GlympseApiException;
use Tracy\Debugger;
use Tracy\ILogger;
use Utils\General;

final class GlympseService extends AbstractService
{
	const NAME = 'Glympse';

	const LINK = 'https://glympse.com';

	const PATH_ID_REGEX = '/^\/[0-9a-z]{4}-[0-9a-z]{4}$/i';

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
		$url = mb_strtolower($url);
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array($parsedUrl['host'], ['glympse.com', 'www.glympse.com']) &&
			isset($parsedUrl['path']) &&
			preg_match(self::PATH_ID_REGEX, $parsedUrl['path'])
		);
	}

	/**
	 * @param string $url
	 * @return BetterLocationCollection
	 */
	public static function parseCoordsMultiple(string $url): BetterLocationCollection {
		$glympseApi = \Factory::Glympse();
		$glympseApi->loadToken();
		$betterLocationCollection = new BetterLocationCollection();
		$inviteId = Glympse::getInviteIdFromUrl($url);
		try {
			$inviteResponse = $glympseApi->loadInvite($inviteId);
			$now = new \DateTimeImmutable();

			try { // Current location
				$currentLocationDescriptions = [];
				$willExpireWarningInterval = new \DateInterval('PT30M');
				if ($inviteResponse->properties->endTime < $now) {
					$currentLocationDescriptions[] = sprintf('%s Glympse expired at %s', \Icons::WARNING, $inviteResponse->properties->endTime->format(\Config::DATETIME_FORMAT_ZONE));
				} else if ($inviteResponse->properties->endTime < ((clone $now)->add($willExpireWarningInterval))) {
					$currentLocationDescriptions[] = sprintf('%s Glympse will expire soon, at %s', \Icons::WARNING, $inviteResponse->properties->endTime->format(\Config::TIME_FORMAT_ZONE));
				}
				$lastLocation = $inviteResponse->getLastLocation();
				$currentLocation = new BetterLocation($url, $lastLocation->latitude, $lastLocation->longtitude, self::class);
				$currentLocationDescriptions[] = sprintf('Last update: %s', $lastLocation->timestamp->format(\Config::DATETIME_FORMAT_ZONE));
				if ($inviteResponse->properties->message) {
					$currentLocationDescriptions[] = sprintf('Glympse message: %s', htmlentities($inviteResponse->properties->message));
				}
				$currentLocation->setPrefixMessage($currentLocation->getPrefixMessage() . ' ' . $inviteResponse->properties->name);
				$currentLocation->setDescription(join(PHP_EOL, $currentLocationDescriptions));
				$betterLocationCollection->add($currentLocation);
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
			}

			try { // Destination location
				if ($inviteResponse->properties->destination) {
					$destinationDescriptions = [];
					$destination = new BetterLocation($url, $inviteResponse->properties->destination->lat, $inviteResponse->properties->destination->lng, self::class);
					$destination->setPrefixMessage($destination->getPrefixMessage() . ' destination');
					if ($inviteResponse->properties->destination->name) {
						$destinationDescriptions[] = $inviteResponse->properties->destination->name;
					}
					if ($inviteResponse->properties->eta && $inviteResponse->properties->route) {
						$inviteResponse->properties->route->distance = 6578;
						if ($inviteResponse->properties->route->distance >= 100000) { // 100 km
							$distanceString = sprintf('%d km', round($inviteResponse->properties->route->distance / 1000));
						} else if ($inviteResponse->properties->route->distance >= 100) { // 1 km
							$distanceString = sprintf('%s km', round($inviteResponse->properties->route->distance / 1000, 2));
						} else {
							$distanceString = sprintf('%d m', $inviteResponse->properties->route->distance);
						}
						$destinationDescriptions[] = sprintf('Distance: %s, calculated ETA: %s', $distanceString, General::sToHuman(intval($inviteResponse->properties->eta->eta->format('%s'))));
					}
					$destination->setDescription(join(PHP_EOL, $destinationDescriptions));
					$betterLocationCollection->add($destination);
				}
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
			}

			return $betterLocationCollection;
		} catch (GlympseApiException $exception) {
			throw new InvalidLocationException(sprintf('Error while processing %s: %s', self::NAME, $exception->getMessage()));
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
	}
}
