<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Utils\UTM;
use App\Utils\UTMFormat;
use DJTommek\Coordinates\Coordinates;
use Tracy\Debugger;
use Tracy\ILogger;

final class UTMService extends AbstractService
{
	const ID = 56;
	const NAME = 'UTM';

	private UTM $utm;

	public static function findInText(string $text): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		$inStringRegex = '/' . self::generateRegex(3) . '/i';
		if (preg_match_all($inStringRegex, $text, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$usngRaw = $matches[0][$i];
				$service = new UTMService();
				$service->setInput($usngRaw);
				try {
					if ($service->validate()) {
						$service->process();
						$collection->add($service->getCollection());
					} else {
						Debugger::log(sprintf('%s input "%s" was findInText() but not validated', self::class, $usngRaw), Debugger::ERROR);
					}
				} catch (InvalidLocationException $exception) {
					Debugger::log($exception, ILogger::DEBUG);
				}
			}
		}
		return $collection;
	}

	public function validate(): bool
	{
		if (isset($this->inputUrl)) {
			return false;
		}

		$regex = self::generateRegex(1);
		$input = mb_strtoupper($this->input);
		if ((bool)preg_match('/^' . $regex . '$/', $input, $matches) === false) {
			return false;
		}

		try {
			$this->utm = new UTM(
				zoneNumber: (int)$matches[1],
				zoneBand: $matches[2],
				easting: (int)$matches[3],
				northing: (int)$matches[4],
			);
			$this->utm->getLat();
			return true;
		} catch (\Throwable) {
			// Swallow, probably not valid
		}
		return false;
	}

	public function process(): void
	{
		$this->collection->add(
			new BetterLocation($this->input, $this->utm->getLat(), $this->utm->getLon(), self::class),
		);
	}

	public static function getShareText(float $lat, float $lon): ?string
	{
		try {
			$utm = UTM::fromCoordinates(new Coordinates($lat, $lon));
			return $utm->format(UTMFormat::ZONE_COORDS);
		} catch (\Throwable) {
			// Coordinates are probably out of range
		}
		return null;
	}

	/**
	 * Generate regex, that validates UTM coordinates.
	 */
	private static function generateRegex(int $northingMinPrecision): string
	{
		assert($northingMinPrecision >= 1 && $northingMinPrecision <= 7, 'Northing minimal precision should be between 1 and 7');

		$re = '([0-9]{1,2})'; // Zone number

		$allowedLetters = join('', [...UTM::BANDS_NORTH, ...UTM::BANDS_SOUTH]);
		$re .= '([' . $allowedLetters . '])'; // Zone band
		$re .= ' ?';
		$re .= '([0-9]{6})'; // Easting
		$re .= ' ?';
		$re .= '([0-9]{' . $northingMinPrecision . ',7})'; // Northing

		return $re;
	}
}
