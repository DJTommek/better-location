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
		$coords = self::parseUrl($url);
		if ($coords) {
			return new BetterLocation($url, $coords[0], $coords[1], self::class);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords from %s link %s.', self::NAME, $url));
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

	public static function parseUrl(string $url): ?array {
		$glympseApi = \Factory::Glympse();
		$glympseApi->loadToken();
		$inviteId = Glympse::getInviteIdFromUrl($url);
		try {
			$inviteResponse = $glympseApi->loadInvite($inviteId);
			$lastLocation = $inviteResponse->getLastLocation();
			return [
				$lastLocation->latitude,
				$lastLocation->longtitude,
			];
		} catch (GlympseApiException $exception) {
			throw new InvalidLocationException(sprintf('Error while processing %s: %s', self::NAME, $exception->getMessage()));
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
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
