<?php declare(strict_types=1);

namespace App\Utils;

/**
 * Ingress scoring regions: named S2 cells of level 6, eg. "NR02-FOXTROT-05".
 *
 * Name consists of:
 * - face name (S2 cube face)
 * - number 01-16 and code word, both derived from the most significant four bits of I/J of the cell (level 4 cell,
 *   cluster of 16 regions). On odd faces I and J are swapped.
 * - number 00-15 derived from the last two Hilbert curve positions of the level 6 cell
 *
 * @author jonatkins and IITC-CE contributors - Original JavaScript implementation (ISC License)
 * @author Tomas Palider (DJTommek) <tomas@palider.cz> - Ported to PHP
 * @link https://github.com/IITC-CE/ingress-intel-total-conversion/blob/master/plugins/regions.js
 */
final class IngressRegion
{
	public const int LEVEL = 6;
	private const int CLUSTER_LEVEL = 4;

	public const array FACE_NAMES = ['AF', 'AS', 'NR', 'PA', 'AM', 'ST'];
	public const array CODE_WORDS = [
		'ALPHA', 'BRAVO', 'CHARLIE', 'DELTA', 'ECHO', 'FOXTROT', 'GOLF', 'HOTEL',
		'JULIET', 'KILO', 'LIMA', 'MIKE', 'NOVEMBER', 'PAPA', 'ROMEO', 'SIERRA',
	];

	/**
	 * Strict regex for canonical name, eg. "NR02-FOXTROT-05"
	 */
	public const string RE_NAME_STRICT = '(?:AF|AS|NR|PA|AM|ST)(?:0[1-9]|1[0-6])-(?:ALPHA|BRAVO|CHARLIE|DELTA|ECHO|FOXTROT|GOLF|HOTEL|JULIET|KILO|LIMA|MIKE|NOVEMBER|PAPA|ROMEO|SIERRA)-(?:0[0-9]|1[0-5])';

	/**
	 * Forgiving regex: dashes between all components and leading zeros are optional, whitespace should be removed
	 * before matching. Groups: 1 = face, 2 = number, 3 = code word, 4 = cell number.
	 */
	private const string RE_NAME_LOOSE = '/^(AF|AS|NR|PA|AM|ST)-?(1[0-6]|0?[1-9])-?(ALPHA|BRAVO|CHARLIE|DELTA|ECHO|FOXTROT|GOLF|HOTEL|JULIET|KILO|LIMA|MIKE|NOVEMBER|PAPA|ROMEO|SIERRA)-?(1[0-5]|0?[0-9])$/i';

	public static function fromCoordinates(float $lat, float $lon): string
	{
		return self::cellToName(S2Cell::fromLatLon($lat, $lon, self::LEVEL));
	}

	public static function cellToName(S2Cell $cell): string
	{
		if ($cell->level !== self::LEVEL) {
			throw new \InvalidArgumentException(sprintf('Ingress region cell must have level %d, got %d.', self::LEVEL, $cell->level));
		}

		$regionI = $cell->i >> (self::LEVEL - self::CLUSTER_LEVEL);
		$regionJ = $cell->j >> (self::LEVEL - self::CLUSTER_LEVEL);
		// For odd faces Niantic swaps number and code word
		if ($cell->face % 2 === 1) {
			[$regionI, $regionJ] = [$regionJ, $regionI];
		}

		$positions = $cell->getHilbertPositions();
		$number = $positions[4] * 4 + $positions[5];

		return sprintf(
			'%s%02d-%s-%02d',
			self::FACE_NAMES[$cell->face],
			$regionI + 1,
			self::CODE_WORDS[$regionJ],
			$number,
		);
	}

	/**
	 * Normalize region name into canonical format, eg. "nr2 foxtrot 5" -> "NR02-FOXTROT-05"
	 *
	 * @return ?string Null if name is not valid
	 */
	public static function normalizeName(string $name): ?string
	{
		$cell = self::nameToCell($name);
		return $cell === null ? null : self::cellToName($cell);
	}

	public static function isValid(string $name): bool
	{
		return self::nameToCell($name) !== null;
	}

	/**
	 * @return ?S2Cell Null if name is not valid
	 */
	public static function nameToCell(string $name): ?S2Cell
	{
		$name = preg_replace('/\s+/', '', $name);
		if (preg_match(self::RE_NAME_LOOSE, $name, $matches) !== 1) {
			return null;
		}

		$face = array_search(mb_strtoupper($matches[1]), self::FACE_NAMES, true);
		$codeWordIndex = array_search(mb_strtoupper($matches[3]), self::CODE_WORDS, true);
		assert(is_int($face) && is_int($codeWordIndex));
		$regionI = (int)$matches[2] - 1;
		$regionJ = $codeWordIndex;
		$number = (int)$matches[4];

		if ($face % 2 === 1) {
			[$regionI, $regionJ] = [$regionJ, $regionI];
		}

		$cluster = S2Cell::fromFaceIJ($face, $regionI, $regionJ, self::CLUSTER_LEVEL);
		$positions = $cluster->getHilbertPositions();
		$positions[] = intdiv($number, 4);
		$positions[] = $number % 4;

		return S2Cell::fromFacePosition($face, $positions);
	}
}
