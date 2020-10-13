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
	 * @throws InvalidLocationException
	 */
	public static function parseCoords(string $url): BetterLocation {
		$glympseApi = \Factory::Glympse();
		$glympseApi->loadToken();
		$inviteId = Glympse::getInviteIdFromUrl($url);
		try {
			$betterLocationDescriptions = [];
			$inviteResponse = $glympseApi->loadInvite($inviteId);
			$willExpireWarningInterval = new \DateInterval('PT30M');
			$now = new \DateTimeImmutable();
			if ($inviteResponse->propertyEndTime < $now) {
				$betterLocationDescriptions[] = sprintf('%s Glympse expired at %s', \Icons::WARNING, $inviteResponse->propertyEndTime->format(\Config::DATETIME_FORMAT_ZONE));
			} else if ($inviteResponse->propertyEndTime < ((clone $now)->add($willExpireWarningInterval))) {
				$betterLocationDescriptions[] = sprintf('%s Glympse will expire soon, at %s', \Icons::WARNING, $inviteResponse->propertyEndTime->format(\Config::TIME_FORMAT_ZONE));
			}
			$lastLocation = $inviteResponse->getLastLocation();
			$betterLocation = new BetterLocation($url, $lastLocation->latitude, $lastLocation->longtitude, self::class);
			$betterLocationDescriptions[] = sprintf('Location from %s', $lastLocation->timestamp->format(\Config::DATETIME_FORMAT_ZONE));
			$betterLocation->setDescription(join(PHP_EOL, $betterLocationDescriptions));
			return $betterLocation;
		} catch (GlympseApiException $exception) {
			throw new InvalidLocationException(sprintf('Error while processing %s: %s', self::NAME, $exception->getMessage()));
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
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
	 * @param string $input
	 * @return BetterLocationCollection
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $input): BetterLocationCollection {
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}
}
