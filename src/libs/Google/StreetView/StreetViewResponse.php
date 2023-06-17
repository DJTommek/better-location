<?php declare(strict_types=1);

namespace App\Google\StreetView;

use App\Dto\AbstractDto;
use DJTommek\Coordinates\CoordinatesImmutable;

class StreetViewResponse extends AbstractDto
{
	public string $copyright;
	public string $date;
	public CoordinatesImmutable $location;
	public string $pano_id;
	public string $status;

	public function set(string $name, mixed $value): void
	{
		$this->{$name} = match ($name) {
			'location' => new CoordinatesImmutable($value->lat, $value->lng),
			default => $value,
		};
	}
}
