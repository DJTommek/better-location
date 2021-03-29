<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Utils\MGRS;
use Tracy\Debugger;
use Tracy\ILogger;

final class MGRSService extends AbstractService
{
	const NAME = 'MGRS';

	public static function findInText(string $text): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		$inStringRegex = '/' . MGRS::getMgrsRegex(3, false, false) . '/';
		if (preg_match_all($inStringRegex, $text, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$mgrsRaw = $matches[0][$i];
				$service = new self($mgrsRaw);
				try {
					if ($service->isValid()) {
						$service->process();
						$collection->add($service->getCollection());
					} else {
						Debugger::log(sprintf('MGRS input "%s" was findInText() but not validated', $mgrsRaw), Debugger::ERROR);
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
		return MGRS::isMGRS($this->input);
	}

	public function process(): void
	{
		$mgrs = MGRS::fromMGRS($this->input);
		$this->collection->add(new BetterLocation($this->input, $mgrs->getLat(), $mgrs->getLon(), get_called_class()));
	}
}
