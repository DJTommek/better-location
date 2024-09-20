<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Address\AddressProvider;
use App\BetterLocation\Service\WazeService;
use DJTommek\Coordinates\CoordinatesInterface;
use unreal4u\TelegramAPI\Telegram;

class ProcessExample implements CoordinatesInterface
{
	// Default example location in center of Prague, Czechia
	public const LAT = 50.087451;
	public const LON = 14.420671;

	private string $exampleInput;
	private BetterLocationCollection $exampleCollection;

	public function __construct(
		private readonly WazeService $wazeService,
		private readonly AddressProvider $addressProvider,
	) {
	}

	public function getExampleCollection(): BetterLocationCollection
	{
		if (!isset($this->exampleCollection)) {
			$this->wazeService->setInput($this->getExampleInput());
			$isValid = $this->wazeService->validate();
			assert($isValid);
			$this->wazeService->process();
			$collection = $this->wazeService->getCollection();
			assert($collection->count() === 1);

			$location = $collection->getFirst();
			$address = $this->addressProvider->reverse($location);
			$location->setAddress($address);

			$this->exampleCollection = $collection;
		}
		return $this->exampleCollection;
	}

	public function getExampleLocation(): BetterLocation
	{
		return $this->getExampleCollection()->getFirst();
	}

	public function getExampleInput(): string
	{
		if (!isset($this->exampleInput)) {
			$this->exampleInput = $this->wazeService->getShareLink(self::LAT, self::LON);
		}
		return $this->exampleInput;
	}

	public function getLat(): float
	{
		return $this->getExampleLocation()->getLat();
	}

	public function getLon(): float
	{
		return $this->getExampleLocation()->getLon();
	}

	public function getLatLon(string $delimiter = ','): string
	{
		return $this->getExampleLocation()->getCoordinates()->getLatLon($delimiter);
	}
}
