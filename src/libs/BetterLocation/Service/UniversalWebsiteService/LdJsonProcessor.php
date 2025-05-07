<?php declare(strict_types=1);

namespace App\BetterLocation\Service\UniversalWebsiteService;

use App\Address\Address;
use App\Address\Country;
use Nette\Utils\Json;
use Tracy\Debugger;

final class LdJsonProcessor
{
	/**
	 * @return array<LdJsonCoordinates>
	 */
	public function processLocation(\DOMXPath $domFinder): array
	{
		$places = [];
		$finderResults = $domFinder->query('//script[@type="application/ld+json"]');

		foreach ($finderResults as $finderResult) {
			assert($finderResult instanceof \DOMElement);
			try {
				$json = Json::decode($finderResult->textContent);
				$objects = is_object($json) ? [$json] : $json; // Content can be one object or array of objects
				foreach ($objects as $object) {
					try {

						$place = LdJsonCoordinates::safe($object->geo?->latitude ?? null, $object->geo?->longitude ?? null);
						if ($place === null) {
							continue; // @TODO add support for extracting coordinates from address $object->address->streetAddress
						}

						$place->placeName = $object->name ?? null;
						$place->address = $this->processAddress($object);
						$places[] = $place;
					} catch (\Throwable $exception) {
						Debugger::log($exception, Debugger::WARNING);
					}
				}
			} catch (\JsonException) {
				continue; // Swallow
			} catch (\Throwable $exception) {
				Debugger::log($exception, Debugger::WARNING);
			}
		}

		return $places;
	}

	private function processAddress(\stdClass $object): ?Address
	{
		try {
			$addressParts = [
				$object->address->streetAddress,
				$object->address->postalCode,
				$object->address->addressLocality,
				$object->address->addressCountry,
			];

			return new Address(
				address: implode(' ', $addressParts),
				country: $this->processCountry($object),
			);
		} catch (\Throwable) {
			return null; // swallow
		}
	}

	private function processCountry(\stdClass $object): ?Country
	{
		try {
			return new Country($object->address->addressCountry);
		} catch (\Throwable $exception) {
			return null; // swallow
		}
	}
}
