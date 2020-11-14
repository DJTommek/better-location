<?php declare(strict_types=1);

namespace App\Geocaching\Types;

use App\Geocaching\Client;

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
}
