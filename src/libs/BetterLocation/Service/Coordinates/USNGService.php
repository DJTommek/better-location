<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Utils\MGRS;
use Tracy\Debugger;
use Tracy\ILogger;

final class USNGService extends AbstractService
{
	const ID = 13;
	const NAME = 'USNG';

	public static function findInText(string $text): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		$inStringRegex = '/' . MGRS::getUSNGRegex(3, false, false) . '/';
		if (preg_match_all($inStringRegex, $text, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$usngRaw = $matches[0][$i];
				$service = new self($usngRaw);
				try {
					if ($service->isValid()) {
						$service->process();
						$collection->add($service->getCollection());
					} else {
						Debugger::log(sprintf('USNG input "%s" was findInText() but not validated', $usngRaw), Debugger::ERROR);
					}
				} catch (InvalidLocationException $exception) {
					Debugger::log($exception, ILogger::DEBUG);
				}
			}
		}
		return $collection;
	}

	public function isValid(): bool
	{
		return MGRS::isUSNG($this->input);
	}

	public function process(): void
	{
		$usng = MGRS::fromUSNG($this->input);
		$this->collection->add(new BetterLocation($this->input, $usng->getLat(), $usng->getLon(), get_called_class()));
	}

	public static function getShareText(float $lat, float $lon): string
	{
		$mgrs = new MGRS();
		return $mgrs->LLtoUSNG($lat, $lon, 5);
	}
}
