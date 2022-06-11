<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Factory;
use App\Foursquare\Client;
use App\Foursquare\Types\VenueType;

final class FoursquareService extends AbstractService
{
	const ID = 22;
	const NAME = 'Foursquare';

	const LINK = Client::LINK;

	const VENUE_ID_REGEX = '[a-f0-9]{24}';

	/**
	 * https://foursquare.com/v/typika/5bfe5f9e54b7a90025543a66
	 */
	const URL_PATH_VENUE_REGEX = '/^\/v\/[^\/]+\/(' . self::VENUE_ID_REGEX . ')$/i';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return self::LINK . sprintf('/explore?ll=%1$f,%2$f', $lat, $lon);
		}
	}

	public function isValid(): bool
	{
		if (
			$this->url &&
			$this->url->getDomain(2) === 'foursquare.com' &&
			preg_match(self::URL_PATH_VENUE_REGEX, $this->url->getPath(), $matches)
		) {
			$this->data->venueId = $matches[1];
			return true;
		}
		return false;
	}

	public function process(): void
	{
		$client = Factory::Foursquare();
		$venue = $client->loadVenue($this->data->venueId);
		$this->collection->add($this->venueToBetterLocation($venue));
	}

	private function venueToBetterLocation(VenueType $venue): BetterLocation
	{
		// @TODO warning if location isFuzzed (lat lon are inaccurate)
		// @TODO warning if now closed?
		$betterLocation = new BetterLocation($this->input, $venue->location->lat, $venue->location->lng, self::class);
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
