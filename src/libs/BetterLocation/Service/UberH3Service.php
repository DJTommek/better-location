<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Utils\H3;

/**
 * Uber H3 - hexagonal hierarchical geospatial indexing system
 *
 * @link https://h3geo.org/
 * @link https://www.uber.com/blog/h3/
 * @link https://github.com/uber/h3
 * @link https://github.com/DJTommek/better-location/issues/144
 */
final class UberH3Service extends AbstractService
{
	const int ID = 71;
	const string NAME = 'Uber H3';
	const string NAME_SHORT = 'H3';

	const string LINK = 'https://h3geo.org';

	/**
	 * Resolution used when generating links and share texts. Resolution 13 has average cell area ~44 m² which is
	 * reasonable compromise between precision and code readability.
	 */
	const int DEFAULT_RESOLUTION = 13;

	/**
	 * Every valid H3 cell index in canonical format has 15 hexadecimal characters and starts with '8' (mode bits).
	 * Second character is resolution (0-f).
	 */
	private const string RE_CODE = '8[0-9a-f]{14}';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_TEXT,
		ServicesManager::TAG_GENERATE_TEXT_OFFLINE,
	];

	private string $code;

	#[\Override]
	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		}

		return self::codeToLink(H3::fromCoordinates($lat, $lon, self::DEFAULT_RESOLUTION));
	}

	#[\Override]
	public static function getShareText(float $lat, float $lon): string
	{
		return H3::fromCoordinates($lat, $lon, self::DEFAULT_RESOLUTION);
	}

	#[\Override]
	public function validate(): bool
	{
		return $this->isUrl() || $this->isCode();
	}

	#[\Override]
	public function process(): void
	{
		assert(isset($this->code));
		$this->collection->add(self::createLocation($this->input, $this->code));
	}

	#[\Override]
	public static function findInText(string $input): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		if (preg_match_all('/(?<![0-9a-z])(' . self::RE_CODE . ')(?![0-9a-z])/i', $input, $matches)) {
			foreach ($matches[1] as $code) {
				$code = mb_strtolower($code);
				if (H3::isValid($code)) {
					$collection->add(self::createLocation($code, $code));
				}
			}
		}
		return $collection;
	}

	/**
	 * @example https://h3geo.org/#hex=8d1e3541e32aa7f
	 */
	private function isUrl(): bool
	{
		if ($this->url === null || $this->url->getDomain(0) !== 'h3geo.org') {
			return false;
		}

		parse_str($this->url->getFragment(), $params);
		$code = $params['hex'] ?? null;
		if (is_string($code) === false) {
			return false;
		}

		return $this->setCodeIfValid($code);
	}

	/**
	 * @example 8d1e3541e32aa7f
	 */
	private function isCode(): bool
	{
		return $this->setCodeIfValid($this->input);
	}

	private function setCodeIfValid(string $code): bool
	{
		$code = mb_strtolower(trim($code));
		if (preg_match('/^' . self::RE_CODE . '$/', $code) !== 1 || H3::isValid($code) === false) {
			return false;
		}

		$this->code = $code;
		return true;
	}

	private static function createLocation(string $input, string $code): BetterLocation
	{
		$coords = H3::toCoordinates($code);
		$location = new BetterLocation($input, $coords->getLat(), $coords->getLon(), self::class);
		$location->setPrefixMessage(sprintf(
			'<a href="%s">%s</a> <code>%s</code>: ',
			self::codeToLink($code),
			self::NAME,
			$code,
		));

		$resolution = H3::getResolution($code);
		$location->addDescription(sprintf(
			'Resolution %d, average cell area %s.',
			$resolution,
			self::formatArea(H3::getAverageAreaM2($resolution)),
		));

		return $location;
	}

	private static function codeToLink(string $code): string
	{
		return sprintf('%s/#hex=%s', self::LINK, $code);
	}

	private static function formatArea(float $areaM2): string
	{
		if ($areaM2 >= 1_000_000) {
			return sprintf('%s km²', number_format($areaM2 / 1_000_000, $areaM2 >= 100_000_000 ? 0 : 1, '.', ' '));
		}
		return sprintf('%s m²', number_format($areaM2, $areaM2 >= 100 ? 0 : 1, '.', ' '));
	}
}
