<?php declare(strict_types=1);

namespace App\BetterLocation;

use DJTommek\Coordinates\CoordinatesInterface;
use Tracy\Debugger;

class VcardLocationParser
{
	private BetterLocationCollection $collection;

	public function __construct(
		private readonly string         $input,
		private readonly GooglePlaceApi $googlePlaceApi
	)
	{
	}

	public function process(?string $searchLanguageCode = null, ?CoordinatesInterface $location = null): self
	{
		$parser = new \JeroenDesloovere\VCard\VCardParser($this->input);
		$this->collection = new BetterLocationCollection();

		foreach ($parser->getCards() as $card) {
			foreach ($card->address ?? [] as $addressGroupKey => $addressGroup) {
				foreach ($addressGroup as $address) {
					$addressToSearch = $this->stringifyAddress($address);
					if ($addressToSearch === '') {
						continue;
					}
					// @TODO handle errors, process other addresses if possible
					$result = $this->googlePlaceApi->searchPlace($addressToSearch, $searchLanguageCode, $location, false);
					if ($result->isEmpty()) {
						continue;
					}

					if ($result->count() > 1) {
						$msg = sprintf(
							'Searching address "%s" from VCard returned %d locations but 1 expected. Code should be adjusted accordingly.',
							$addressToSearch,
							$result->count(),
						);
						Debugger::log($msg, Debugger::WARNING);
						// @phpstan-ignore-next-line
						assert(false, $msg);
					}

					$location = $result->getFirst();

					$prefix = sprintf(
						'Contact %s %s address',
						htmlspecialchars($this->cardDisplayname($card)),
						htmlspecialchars($addressGroupKey)
					);
					$location->setPrefixMessage($prefix);
					$this->collection->add($location);
					break;
				}
			}
		}

		return $this;
	}

	public function getCollection(): BetterLocationCollection
	{
		$this->assertProcessed();
		return $this->collection;
	}

	private function cardDisplayname(\stdClass $card): string
	{
		return $card->fullname;
	}

	private function stringifyAddress(\stdClass $address): string
	{
		$parts = [
			$address->name,
			$address->extended,
			$address->street,
			$address->city,
			$address->region,
			$address->zip,
			$address->country,
		];
		return join(' ', array_filter($parts));
	}

	private function assertProcessed(): void
	{
		if (isset($this->collection) === false) {
			throw new \RuntimeException(sprintf('Run %s::process() first.', self::class));
		}
	}
}
