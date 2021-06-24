<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Utils\Coordinates;

final class BetterLocationService extends AbstractService
{
	const NAME = 'BetterLocation';

	const LINK = 'https://better-location.palider.cz';

	const URL_PATH_REGEX = '/^\/(-?[0-9.]+),(-?[0-9.]+)$/';

	/** @throws NotSupportedException */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return sprintf('%s/%F,%F', self::LINK, $lat, $lon);
		}
	}

	public function isValid(): bool
	{
		if (
			$this->url &&
			$this->url->getDomain(3) === 'better-location.palider.cz' &&
			preg_match(self::URL_PATH_REGEX, $this->url->getPath(), $matches)
		) {
			try {
				$this->data->coords = new Coordinates($matches[1], $matches[2]);
				return true;
			} catch (InvalidLocationException $exception) {
				return false;
			}
		}
		return false;
	}

	public function process()
	{
		$location = new BetterLocation($this->input, $this->data->coords->getLat(), $this->data->coords->getLon(), self::class);
		$this->collection->add($location);
	}
}
