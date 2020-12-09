<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Factory;
use App\Foursquare\Client;
use App\Foursquare\Types\VenueType;
use App\Utils\General;

final class FoursquareService extends AbstractService
{
	const NAME = 'Foursquare';

	const LINK = Client::LINK;
	const LINK_SHARE = 'https://foursquare.com/explore?ll=50.052152,14.453566';

	const VENUE_ID_REGEX = '[a-f0-9]{24}';

	/**
	 * https://foursquare.com/v/typika/5bfe5f9e54b7a90025543a66
	 */
	const URL_PATH_VENUE_REGEX = '/^\/v\/[^\/]+\/(' . self::VENUE_ID_REGEX . ')$/i';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return self::LINK . sprintf('/explore?ll=%1$f,%2$f', $lat, $lon);
		}
	}

	public static function isValid(string $input): bool
	{
		return self::isUrl($input);
	}

	public static function isUrl(string $url): bool
	{
		return self::isCorrectDomainUrl($url) && self::isVenueIdUrl($url);
	}

	private static function isCorrectDomainUrl($url): bool
	{
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			// @TODO add support for domains based on country (eg it.foursquare.com)
			in_array(mb_strtolower($parsedUrl['host']), ['foursquare.com', 'www.foursquare.com'], true) &&
			isset($parsedUrl['path'])
		);
	}

	private static function isVenueIdUrl($url): bool
	{
		$parsedUrl = General::parseUrl(mb_strtolower($url));
		return !!(preg_match(self::URL_PATH_VENUE_REGEX, $parsedUrl['path']));
	}

	private static function getVenueIdFromUrl($url): ?string
	{
		$parsedUrl = General::parseUrl(mb_strtolower($url));
		if (preg_match(self::URL_PATH_VENUE_REGEX, $parsedUrl['path'], $matches)) {
			return $matches[1];
		} else {
			return null;
		}
	}

	public static function parseUrl(string $url): BetterLocation
	{
		if ($venueId = self::getVenueIdFromUrl($url)) {
			$client = Factory::Foursquare();
			$venue = $client->loadVenue($venueId);
			return self::formatApiResponse($venue, $url);
		} else {
			throw new InvalidLocationException(sprintf('Invalid venue ID in URL %s.', self::NAME));
		}
	}

	public static function parseCoords(string $url): BetterLocation
	{
		throw new NotImplementedException('Parsing coordinates is not available.');
	}

	public static function parseCoordsMultiple(string $input): BetterLocationCollection
	{
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}

	private static function formatApiResponse(VenueType $venue, string $inputUrl): BetterLocation
	{
		// @TODO warning if location isFuzzed (lat lon are inaccurate)
		// @TODO warning if now closed?
		$betterLocation = new BetterLocation($inputUrl, $venue->location->lat, $venue->location->lng, self::class);
		$betterLocation->setAddress($venue->location->getFormattedAddress());

		$prefix = sprintf('%s <a href="%s">%s</a>', $betterLocation->getPrefixMessage(), $venue->url, $venue->name);
		$betterLocation->setPrefixMessage($prefix);

		$descriptionValues = [];
		if (isset($venue->contact->facebookUsername)) {
			$descriptionValues[] = sprintf('<a href="https://facebook.com/%s">Facebook</a>', $venue->contact->facebookUsername);
		}
		if (isset($venue->contact->twitter)) {
			$descriptionValues[] = sprintf('<a href="https://twitter.com/@%s">Twitter</a>', $venue->contact->twitter);
		}
		if (isset($venue->contact->instagram)) {
			$descriptionValues[] = sprintf('<a href="https://instagram.com/%s">Instagram</a>', $venue->contact->instagram);
		}
		if (isset($venue->contact->formattedPhone)) {
			$descriptionValues[] = $venue->contact->formattedPhone;
		}
		if (count($descriptionValues) > 0) {
			$betterLocation->setDescription(join(', ', $descriptionValues));
		}
		return $betterLocation;
	}
}
