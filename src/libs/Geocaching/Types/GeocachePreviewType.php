<?php declare(strict_types=1);

namespace App\Geocaching\Types;

use App\Geocaching\Client;

/**
 * Class GeocachePreviewType
 *
 * Premium: https://www.geocaching.com/geocache/GC8KK62
 */
class GeocachePreviewType
{
	/** @var string */
	public $name;
	/** @var string */
	public $code;
	/** @var bool */
	public $premiumOnly;
	/** @var int */
	public $favouritePoints;
	/** @var int */
	public $geocacheType;
	/** @var int */
	public $containerType;
	/** @var float */
	public $difficulty;
	/** @var float */
	public $terrain;
	/** @var string */
	public $description;
	/** @var string */
	public $shortDescription;
	/** @var string */
	public $hint;
	/** @var bool */
	public $userFound;
	/** @var bool */
	public $userDidNotFind;
	/** @var bool */
	public $userFavorited;
	/** @var string */
	public $detailsUrl;
	/** @var bool */
	public $hasGeotour;
	/** @var \DateTimeImmutable */
	public $placedDate;
	/** @var \stdClass @TODO */
	public $owner; // code and username
	/** @var int */
	public $cacheStatus;
	/** @var \stdClass @TODO */
	public $postedCoordinates; // latitude longitude
	/** @var int */
	public $totalActivities;
	/** @var array @TODO */
	public $recentActivities;

	public static function createFromVariable(\stdClass $variables): self
	{
		$class = new self();
		foreach ($variables as $key => $value) {
			if ($key === 'placedDate') {
				$value = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $value);
			}
			$class->{$key} = $value;
		}
		return $class;
	}

	public function getLink(): string
	{
		return Client::LINK . $this->detailsUrl;
	}

	public function getType(): string
	{
		switch ($this->geocacheType) {
			case 2: // https://www.geocaching.com/geocache/GC24GCV
				return 'Traditional';
			case 3: // https://www.geocaching.com/geocache/GC3HK7M
				return 'Multi';
			case 4: // https://www.geocaching.com/geocache/GC88ZPV
				return 'Virtual';
			case 5: // https://www.geocaching.com/geocache/GC7X2M6
				return 'Letterbox Hybrid';
			case 6: // https://www.geocaching.com/geocache/GC90M42
				return 'Event';
			case 11: // https://www.geocaching.com/geocache/GCPDPE
				return 'Webcam';
			case 8: // https://www.geocaching.com/geocache/GC3DYC4
				return 'Mystery';
			case 137: // https://www.geocaching.com/geocache/GC1PPBR
				return 'Earth';
			case 453: // https://www.geocaching.com/geocache/GC8MCKP
				return 'Mega-Event';
			case 1304: // https://www.geocaching.com/geocache/GC7WWW0
				return 'GPS Adventures Exhibit';
			case 1858: // https://www.geocaching.com/geocache/GC6NTQV
				return 'Wherigo';
			case 7005: // https://www.geocaching.com/geocache/GC7WWWW
				return 'Giga-Event';
			default:
				throw new \InvalidArgumentException(sprintf('Unknown geocache type for geocacheType "%s".', $this->geocacheType));
		}
	}

	public function getSize(): string
	{
		switch ($this->containerType) {
			case 1: // https://www.geocaching.com/geocache/GC1PPBR
				return 'none';
			case 2:
				return 'micro';
			case 3:
				return 'regular';
			case 4: // https://www.geocaching.com/geocache/GC7X2M6
				return 'large';
			case 5: // https://www.geocaching.com/geocache/GC88ZPV
				return 'virtual';
			case 6: // https://www.geocaching.com/geocache/GC825XA
				return 'other';
			case 8: // https://www.geocaching.com/geocache/GC24GCV
				return 'small';
			default:
				throw new \InvalidArgumentException(sprintf('Unknown container size for containerType "%s".', $this->geocacheType));
		}
	}

	public function getStatus(): string
	{
		switch ($this->cacheStatus) {
			case 0:
				return 'active';
			case 1: // https://www.geocaching.com/geocache/GC8ZFK8
				return 'disabled';
			default:
				throw new \InvalidArgumentException(sprintf('Unknown geocache status for cacheStatus "%s".', $this->geocacheType));
		}

	}
}
