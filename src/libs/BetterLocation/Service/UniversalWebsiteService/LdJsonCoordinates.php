<?php declare(strict_types=1);

namespace App\BetterLocation\Service\UniversalWebsiteService;

use App\Address\Address;
use DJTommek\Coordinates\CoordinatesImmutable;

final class LdJsonCoordinates extends CoordinatesImmutable
{
	public function __construct(
		mixed $lat,
		mixed $lon,
		public ?Address $address = null,
		public ?string $placeName = null,
	) {
		parent::__construct($lat, $lon);
	}
}
