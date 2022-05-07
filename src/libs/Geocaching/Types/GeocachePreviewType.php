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
	const TYPE_TRADITIONAL = 2;
	const TYPE_MULTI = 3;
	const TYPE_VIRTUAL = 4;
	const TYPE_LETTERBOX = 5;
	const TYPE_EVENT = 6;
	const TYPE_MYSTERY = 8;
	const TYPE_WEBCAM = 11;
	const TYPE_EARTH = 137;
	const TYPE_EVENT_MEGA = 453;
	const TYPE_GPS_ADVENTURES_EXHIBIT = 1304;
	const TYPE_WHERIGO = 1858;
	const TYPE_EVENT_GIGA = 7005;

	const SIZE_NONE = 1;
	const SIZE_MICRO = 2;
	const SIZE_REGULAR = 3;
	const SIZE_LARGE = 4;
	const SIZE_VIRTUAL = 5;
	const SIZE_OTHER = 6;
	const SIZE_SMALL = 8;

	const STATUS_ACTIVE = 0;
	const STATUS_DISABLED = 1;

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
			case self::TYPE_TRADITIONAL: // https://www.geocaching.com/geocache/GC24GCV
				return 'Traditional';
			case self::TYPE_MULTI: // https://www.geocaching.com/geocache/GC3HK7M
				return 'Multi';
			case self::TYPE_VIRTUAL: // https://www.geocaching.com/geocache/GC88ZPV
				return 'Virtual';
			case self::TYPE_LETTERBOX: // https://www.geocaching.com/geocache/GC7X2M6
				return 'Letterbox Hybrid';
			case self::TYPE_EVENT: // https://www.geocaching.com/geocache/GC90M42
				return 'Event';
			case self::TYPE_MYSTERY: // https://www.geocaching.com/geocache/GC3DYC4
				return 'Mystery';
			case self::TYPE_WEBCAM: // https://www.geocaching.com/geocache/GCPDPE
				return 'Webcam';
			case self::TYPE_EARTH: // https://www.geocaching.com/geocache/GC1PPBR
				return 'Earth';
			case self::TYPE_EVENT_MEGA: // https://www.geocaching.com/geocache/GC8MCKP
				return 'Mega-Event';
			case self::TYPE_GPS_ADVENTURES_EXHIBIT: // https://www.geocaching.com/geocache/GC7WWW0
				return 'GPS Adventures Exhibit';
			case self::TYPE_WHERIGO: // https://www.geocaching.com/geocache/GC6NTQV
				return 'Wherigo';
			case self::TYPE_EVENT_GIGA: // https://www.geocaching.com/geocache/GC7WWWW
				return 'Giga-Event';
			default:
				throw new \InvalidArgumentException(sprintf('Unknown geocache type for geocacheType "%s".', $this->geocacheType));
		}
	}

	public function getSize(): string
	{
		switch ($this->containerType) {
			case self::SIZE_NONE: // https://www.geocaching.com/geocache/GC1PPBR
				return 'none';
			case self::SIZE_MICRO:
				return 'micro';
			case self::SIZE_REGULAR:
				return 'regular';
			case self::SIZE_LARGE: // https://www.geocaching.com/geocache/GC7X2M6
				return 'large';
			case self::SIZE_VIRTUAL: // https://www.geocaching.com/geocache/GC88ZPV
				return 'virtual';
			case self::SIZE_OTHER: // https://www.geocaching.com/geocache/GC825XA
				return 'other';
			case self::SIZE_SMALL: // https://www.geocaching.com/geocache/GC24GCV
				return 'small';
			default:
				throw new \InvalidArgumentException(sprintf('Unknown container size for containerType "%s".', $this->geocacheType));
		}
	}

	public function getStatus(): string
	{
		switch ($this->cacheStatus) {
			case self::STATUS_ACTIVE:
				return 'active';
			case self::STATUS_DISABLED: // https://www.geocaching.com/geocache/GC8ZFK8
				return 'disabled';
			default:
				throw new \InvalidArgumentException(sprintf('Unknown geocache status for cacheStatus "%s".', $this->geocacheType));
		}
	}

	public function isDisabled(): bool
	{
		return ($this->cacheStatus === self::STATUS_DISABLED);
	}

	/**
	 * Optimalize output info about type and size of cache
	 *
	 * @return string
	 */
	public function getTypeAndSize(): string
	{
		$otherSizes = [self::SIZE_VIRTUAL, self::SIZE_OTHER, self::SIZE_NONE];
		$eventTypes = [self::TYPE_EVENT, self::TYPE_EVENT_GIGA, self::TYPE_EVENT_MEGA];
		if ($this->geocacheType === self::TYPE_VIRTUAL && in_array($this->containerType, $otherSizes, true)) {
			return $this->getType();
		} else if ($this->geocacheType === self::TYPE_EARTH && in_array($this->containerType, $otherSizes, true)) {
			return $this->getType();
		} else if (in_array($this->geocacheType, $eventTypes, true) && in_array($this->containerType, $otherSizes, true)) {
			return $this->getType();
		}
		return $this->getType() . ' ' . $this->getSize();
	}
}
