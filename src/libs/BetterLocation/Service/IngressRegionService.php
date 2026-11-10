<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\ServicesManager;
use App\Utils\Formatter;
use App\Utils\IngressRegion;
use App\Utils\S2Cell;

/**
 * Ingress scoring regions - named S2 cells of level 6, eg. "NR02-FOXTROT-05". Location of region is its center.
 *
 * @link https://github.com/IITC-CE/ingress-intel-total-conversion/blob/master/plugins/regions.js
 * @link https://fevgames.net/ingress/ingress-guide/concepts/regional-scores/
 */
final class IngressRegionService extends AbstractService
{
	const int ID = 73;
	const string NAME = 'Ingress region';

	private const string INTEL_LINK = 'https://intel.ingress.com';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_TEXT,
		ServicesManager::TAG_GENERATE_TEXT_OFFLINE,
	];

	private S2Cell $cell;

	#[\Override]
	public static function getShareText(float $lat, float $lon): string
	{
		return IngressRegion::fromCoordinates($lat, $lon);
	}

	#[\Override]
	public function validate(): bool
	{
		if ($this->url !== null) {
			return false;
		}

		$cell = IngressRegion::nameToCell($this->input);
		if ($cell === null) {
			return false;
		}

		$this->cell = $cell;
		return true;
	}

	#[\Override]
	public function process(): void
	{
		assert(isset($this->cell));
		$this->collection->add(self::createLocation($this->input, $this->cell));
	}

	#[\Override]
	public static function findInText(string $input): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		if (preg_match_all('/(?<![0-9a-z-])(' . IngressRegion::RE_NAME_STRICT . ')(?![0-9a-z-])/i', $input, $matches)) {
			foreach ($matches[1] as $name) {
				$cell = IngressRegion::nameToCell($name);
				if ($cell !== null) {
					$collection->add(self::createLocation($name, $cell));
				}
			}
		}
		return $collection;
	}

	private static function createLocation(string $input, S2Cell $cell): BetterLocation
	{
		$name = IngressRegion::cellToName($cell);
		$center = $cell->getCenter();

		$location = new BetterLocation($input, $center->getLat(), $center->getLon(), self::class);
		$location->setPrefixMessage(sprintf(
			'<a href="%s">%s</a> <code>%s</code>: ',
			sprintf('%s/?ll=%F,%F&z=8', self::INTEL_LINK, $center->getLat(), $center->getLon()),
			self::NAME,
			$name,
		));
		$location->addDescription(self::areaDescription($cell));

		return $location;
	}

	/**
	 * Area covered by region, eg. "Area of ~18 520 km²."
	 */
	private static function areaDescription(S2Cell $cell): string
	{
		return sprintf('Area of ~%s.', Formatter::area($cell->getAreaM2()));
	}
}
