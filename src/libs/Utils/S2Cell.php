<?php declare(strict_types=1);

namespace App\Utils;

use DJTommek\Coordinates\CoordinatesImmutable;

/**
 * Minimal subset of Google S2 geometry: cell defined by cube face, I/J coordinates and level.
 *
 * Compared to the Google S2 library this does not use 64-bit cell IDs. Cell is represented as face + I/J, where I/J
 * are in range 0 to (1 << level) - 1, and position on Hilbert curve is represented as list of quads (0-3), one per
 * level. This is enough for low levels such as Ingress scoring regions (level 6).
 *
 * @author jonatkins and IITC-CE contributors - Original JavaScript implementation (ISC License)
 * @author Tomas Palider (DJTommek) <tomas@palider.cz> - Ported to PHP
 * @link https://github.com/IITC-CE/ingress-intel-total-conversion/blob/master/plugins/external/s2geometry.js
 * @link https://s2geometry.io/
 */
final readonly class S2Cell
{
	public const int MAX_LEVEL = 30;

	/** Hilbert curve: [current square][quadX * 2 + quadY] => [position, next square] */
	private const array HILBERT_MAP = [
		'a' => [[0, 'd'], [1, 'a'], [3, 'b'], [2, 'a']],
		'b' => [[2, 'b'], [1, 'b'], [3, 'a'], [0, 'c']],
		'c' => [[2, 'c'], [3, 'd'], [1, 'c'], [0, 'b']],
		'd' => [[0, 'a'], [3, 'c'], [1, 'd'], [2, 'd']],
	];

	/** Reverse of HILBERT_MAP: [current square][position] => [quadX * 2 + quadY, next square] */
	private const array HILBERT_MAP_REVERSE = [
		'a' => [[0, 'd'], [1, 'a'], [3, 'a'], [2, 'b']],
		'b' => [[3, 'c'], [1, 'b'], [0, 'b'], [2, 'a']],
		'c' => [[3, 'b'], [2, 'c'], [0, 'c'], [1, 'd']],
		'd' => [[0, 'a'], [2, 'd'], [3, 'd'], [1, 'c']],
	];

	/**
	 * @param int<0,5> $face
	 */
	private function __construct(
		public int $face,
		public int $i,
		public int $j,
		public int $level,
	) {
	}

	public static function fromFaceIJ(int $face, int $i, int $j, int $level): self
	{
		if ($face < 0 || $face > 5) {
			throw new \InvalidArgumentException(sprintf('Invalid face %d, must be between 0 and 5.', $face));
		}
		if ($level < 0 || $level > self::MAX_LEVEL) {
			throw new \InvalidArgumentException(sprintf('Invalid level %d, must be between 0 and %d.', $level, self::MAX_LEVEL));
		}
		$maxSize = 1 << $level;
		if ($i < 0 || $i >= $maxSize || $j < 0 || $j >= $maxSize) {
			throw new \InvalidArgumentException(sprintf('Invalid I/J [%d, %d] for level %d.', $i, $j, $level));
		}

		return new self($face, $i, $j, $level);
	}

	public static function fromLatLon(float $lat, float $lon, int $level): self
	{
		[$face, $u, $v] = self::xyzToFaceUV(self::latLonToXYZ($lat, $lon));
		$maxSize = 1 << $level;
		$i = max(0, min($maxSize - 1, (int)floor(self::uvToST($u) * $maxSize)));
		$j = max(0, min($maxSize - 1, (int)floor(self::uvToST($v) * $maxSize)));
		return self::fromFaceIJ($face, $i, $j, $level);
	}

	/**
	 * Create cell from face and position on Hilbert curve (list of quads 0-3, one per level).
	 *
	 * @param list<int> $positions
	 */
	public static function fromFacePosition(int $face, array $positions): self
	{
		$square = ($face & 1) === 1 ? 'd' : 'a';
		$i = 0;
		$j = 0;
		foreach ($positions as $position) {
			if ($position < 0 || $position > 3) {
				throw new \InvalidArgumentException(sprintf('Invalid Hilbert curve position %d, must be between 0 and 3.', $position));
			}
			[$quad, $square] = self::HILBERT_MAP_REVERSE[$square][$position];
			$i = ($i << 1) | (($quad & 2) !== 0 ? 1 : 0);
			$j = ($j << 1) | (($quad & 1) !== 0 ? 1 : 0);
		}
		return self::fromFaceIJ($face, $i, $j, count($positions));
	}

	/**
	 * Position on Hilbert curve as list of quads (0-3), one per level.
	 *
	 * @return list<int>
	 */
	public function getHilbertPositions(): array
	{
		$square = ($this->face & 1) === 1 ? 'd' : 'a';
		$positions = [];
		for ($bit = $this->level - 1; $bit >= 0; $bit--) {
			$quadX = ($this->i >> $bit) & 1;
			$quadY = ($this->j >> $bit) & 1;
			[$position, $square] = self::HILBERT_MAP[$square][$quadX * 2 + $quadY];
			$positions[] = $position;
		}
		return $positions;
	}

	/**
	 * Official 64-bit S2 cell ID: 3 bits of face, 2 bits per level of Hilbert curve position, then a single 1 bit
	 * marking the level, rest is zeros. Note that faces 4 and 5 have the highest bit set, so in PHP the returned
	 * integer is negative. Use getToken() or sprintf('%u') for unsigned representation.
	 */
	public function getId(): int
	{
		$id = $this->face << 61;
		foreach ($this->getHilbertPositions() as $index => $position) {
			$id |= $position << (59 - 2 * $index);
		}
		return $id | (1 << (60 - 2 * $this->level));
	}

	/**
	 * Official S2 cell token: hexadecimal cell ID without trailing zeros, eg. "470b94eb" for level 14.
	 */
	public function getToken(): string
	{
		return rtrim(sprintf('%016x', $this->getId()), '0');
	}

	public function getCenter(): CoordinatesImmutable
	{
		return $this->ijOffsetToCoordinates(0.5, 0.5);
	}

	/**
	 * Corners of cell in counter-clockwise order. Edges between corners are geodesics (great circles).
	 *
	 * @return array{CoordinatesImmutable, CoordinatesImmutable, CoordinatesImmutable, CoordinatesImmutable}
	 */
	public function getCorners(): array
	{
		return [
			$this->ijOffsetToCoordinates(0.0, 0.0),
			$this->ijOffsetToCoordinates(0.0, 1.0),
			$this->ijOffsetToCoordinates(1.0, 1.0),
			$this->ijOffsetToCoordinates(1.0, 0.0),
		];
	}

	/**
	 * Exact area of cell on sphere with mean Earth radius (edges are geodesics, so cell is spherical quadrilateral).
	 */
	public function getAreaM2(): float
	{
		$xyz = [
			$this->ijOffsetToXYZ(0.0, 0.0),
			$this->ijOffsetToXYZ(0.0, 1.0),
			$this->ijOffsetToXYZ(1.0, 1.0),
			$this->ijOffsetToXYZ(1.0, 0.0),
		];
		$excess = self::sphericalTriangleExcess($xyz[0], $xyz[1], $xyz[2])
			+ self::sphericalTriangleExcess($xyz[0], $xyz[2], $xyz[3]);
		return $excess * Coordinates::EARTH_RADIUS ** 2;
	}

	public function equals(self $other): bool
	{
		return $this->face === $other->face
			&& $this->i === $other->i
			&& $this->j === $other->j
			&& $this->level === $other->level;
	}

	private function ijOffsetToCoordinates(float $offsetI, float $offsetJ): CoordinatesImmutable
	{
		return self::xyzToLatLon($this->ijOffsetToXYZ($offsetI, $offsetJ));
	}

	/**
	 * @return array{float, float, float}
	 */
	private function ijOffsetToXYZ(float $offsetI, float $offsetJ): array
	{
		$maxSize = 1 << $this->level;
		$u = self::stToUV(($this->i + $offsetI) / $maxSize);
		$v = self::stToUV(($this->j + $offsetJ) / $maxSize);
		return self::faceUVToXYZ($this->face, $u, $v);
	}

	/**
	 * Spherical excess of triangle on unit sphere (Van Oosterom & Strackee formula)
	 *
	 * @param array{float, float, float} $a
	 * @param array{float, float, float} $b
	 * @param array{float, float, float} $c
	 */
	private static function sphericalTriangleExcess(array $a, array $b, array $c): float
	{
		$a = self::normalize($a);
		$b = self::normalize($b);
		$c = self::normalize($c);

		$tripleProduct = $a[0] * ($b[1] * $c[2] - $b[2] * $c[1])
			- $a[1] * ($b[0] * $c[2] - $b[2] * $c[0])
			+ $a[2] * ($b[0] * $c[1] - $b[1] * $c[0]);
		$dot = fn(array $p, array $q): float => $p[0] * $q[0] + $p[1] * $q[1] + $p[2] * $q[2];

		return abs(2 * atan2($tripleProduct, 1 + $dot($a, $b) + $dot($b, $c) + $dot($c, $a)));
	}

	/**
	 * @param array{float, float, float} $xyz
	 * @return array{float, float, float}
	 */
	private static function normalize(array $xyz): array
	{
		$length = sqrt($xyz[0] ** 2 + $xyz[1] ** 2 + $xyz[2] ** 2);
		return [$xyz[0] / $length, $xyz[1] / $length, $xyz[2] / $length];
	}

	/**
	 * @return array{float, float, float}
	 */
	private static function latLonToXYZ(float $lat, float $lon): array
	{
		$phi = deg2rad($lat);
		$theta = deg2rad($lon);
		$cosPhi = cos($phi);
		return [cos($theta) * $cosPhi, sin($theta) * $cosPhi, sin($phi)];
	}

	/**
	 * @param array{float, float, float} $xyz
	 */
	private static function xyzToLatLon(array $xyz): CoordinatesImmutable
	{
		$lat = atan2($xyz[2], sqrt($xyz[0] ** 2 + $xyz[1] ** 2));
		$lon = atan2($xyz[1], $xyz[0]);
		return new CoordinatesImmutable(rad2deg($lat), rad2deg($lon));
	}

	/**
	 * @param array{float, float, float} $xyz
	 * @return array{int<0,5>, float, float} face, u, v
	 */
	private static function xyzToFaceUV(array $xyz): array
	{
		$abs = array_map(abs(...), $xyz);
		if ($abs[0] > $abs[1]) {
			$face = $abs[0] > $abs[2] ? 0 : 2;
		} else {
			$face = $abs[1] > $abs[2] ? 1 : 2;
		}
		if ($xyz[$face] < 0) {
			$face += 3;
		}

		[$x, $y, $z] = $xyz;
		[$u, $v] = match ($face) {
			0 => [$y / $x, $z / $x],
			1 => [-$x / $y, $z / $y],
			2 => [-$x / $z, -$y / $z],
			3 => [$z / $x, $y / $x],
			4 => [$z / $y, -$x / $y],
			5 => [-$y / $z, -$x / $z],
		};
		return [$face, $u, $v];
	}

	/**
	 * @return array{float, float, float}
	 */
	private static function faceUVToXYZ(int $face, float $u, float $v): array
	{
		return match ($face) {
			0 => [1.0, $u, $v],
			1 => [-$u, 1.0, $v],
			2 => [-$u, -$v, 1.0],
			3 => [-1.0, -$v, -$u],
			4 => [$v, -1.0, -$u],
			5 => [$v, $u, -1.0],
			default => throw new \InvalidArgumentException('Invalid face'),
		};
	}

	/**
	 * Quadratic projection
	 */
	private static function stToUV(float $st): float
	{
		if ($st >= 0.5) {
			return (1 / 3) * (4 * $st * $st - 1);
		}
		return (1 / 3) * (1 - 4 * (1 - $st) * (1 - $st));
	}

	private static function uvToST(float $uv): float
	{
		if ($uv >= 0) {
			return 0.5 * sqrt(1 + 3 * $uv);
		}
		return 1 - 0.5 * sqrt(1 - 3 * $uv);
	}
}
