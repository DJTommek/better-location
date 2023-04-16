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
	const TYPE_LOCATIONLESS = 12;
	const TYPE_EVENT_CITO = 13;
	const TYPE_EARTH = 137;
	const TYPE_EVENT_MEGA = 453;
	const TYPE_GPS_ADVENTURES_EXHIBIT = 1304;
	const TYPE_WHERIGO = 1858;
	const TYPE_EVENT_COMMUNITY_CELEBRATION = 3653;
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
		foreach ((array)$variables as $key => $value) {
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
		return match ($this->geocacheType) {
			self::TYPE_TRADITIONAL => 'Traditional', // https://www.geocaching.com/geocache/GC24GCV
			self::TYPE_MULTI => 'Multi', // https://www.geocaching.com/geocache/GC3HK7M
			self::TYPE_VIRTUAL => 'Virtual', // https://www.geocaching.com/geocache/GC88ZPV
			self::TYPE_LETTERBOX => 'Letterbox Hybrid', // https://www.geocaching.com/geocache/GC7X2M6
			self::TYPE_LOCATIONLESS => 'Locationless', // https://www.geocaching.com/geocache/GC7X2M6
			self::TYPE_EVENT => 'Event', // https://www.geocaching.com/geocache/GC90M42
			self::TYPE_EVENT_CITO => 'CITO', // https://www.geocaching.com/geocache/GC7WWWP
			self::TYPE_MYSTERY => 'Mystery', // https://www.geocaching.com/geocache/GC3DYC4
			self::TYPE_WEBCAM => 'Webcam', // https://www.geocaching.com/geocache/GCPDPE
			self::TYPE_EARTH => 'Earth', // https://www.geocaching.com/geocache/GC1PPBR
			self::TYPE_EVENT_MEGA => 'Mega-Event', // https://www.geocaching.com/geocache/GC8MCKP
			self::TYPE_GPS_ADVENTURES_EXHIBIT => 'GPS Adventures Exhibit', // https://www.geocaching.com/geocache/GC7WWW0
			self::TYPE_WHERIGO => 'Wherigo', // https://www.geocaching.com/geocache/GC6NTQV
			self::TYPE_EVENT_GIGA => 'Giga-Event', // https://www.geocaching.com/geocache/GC7WWWW
			self::TYPE_EVENT_COMMUNITY_CELEBRATION => 'Community celebration event', // https://www.geocaching.com/geocache/GC8HMX9
			default => throw new \InvalidArgumentException(sprintf('Unknown geocache type for geocacheType "%s".', $this->geocacheType)),
		};
	}

	public function getSize(): string
	{
		return match ($this->containerType) {
			self::SIZE_NONE => 'none', // https://www.geocaching.com/geocache/GC1PPBR
			self::SIZE_MICRO => 'micro',
			self::SIZE_REGULAR => 'regular',
			self::SIZE_LARGE => 'large', // https://www.geocaching.com/geocache/GC7X2M6
			self::SIZE_VIRTUAL => 'virtual', // https://www.geocaching.com/geocache/GC88ZPV
			self::SIZE_OTHER => 'other', // https://www.geocaching.com/geocache/GC825XA
			self::SIZE_SMALL => 'small', // https://www.geocaching.com/geocache/GC24GCV
			default => throw new \InvalidArgumentException(sprintf('Unknown container size for containerType "%s".', $this->geocacheType)),
		};
	}

	public function getStatus(): string
	{
		return match ($this->cacheStatus) {
			self::STATUS_ACTIVE => 'active',
			self::STATUS_DISABLED => 'disabled', // https://www.geocaching.com/geocache/GC8ZFK8
			default => throw new \InvalidArgumentException(sprintf('Unknown geocache status for cacheStatus "%s".', $this->geocacheType)),
		};
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
		$eventTypes = [
			self::TYPE_EVENT,
			self::TYPE_EVENT_GIGA,
			self::TYPE_EVENT_MEGA,
			self::TYPE_EVENT_COMMUNITY_CELEBRATION,
			self::TYPE_EVENT_CITO,
		];
		if ($this->geocacheType === self::TYPE_VIRTUAL && in_array($this->containerType, $otherSizes, true)) {
			return $this->getType();
		} else if ($this->geocacheType === self::TYPE_EARTH && in_array($this->containerType, $otherSizes, true)) {
			return $this->getType();
		} else if ($this->geocacheType === self::TYPE_LOCATIONLESS && in_array($this->containerType, $otherSizes, true)) {
			return $this->getType();
		} else if (in_array($this->geocacheType, $eventTypes, true) && in_array($this->containerType, $otherSizes, true)) {
			return $this->getType();
		}
		return $this->getType() . ' ' . $this->getSize();
	}
}
