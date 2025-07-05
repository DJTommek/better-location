<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Address\AddressInterface;
use App\BetterLocation\Service\AbstractService;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use DJTommek\Coordinates\CoordinatesInterface;

interface MessageGeneratorInterface
{
	/**
	 * @param array<class-string<AbstractService>,string> $pregeneratedLinks
	 * @param list<Description> $descriptions
	 */
	public function generate(
		CoordinatesInterface $coordinates,
		BetterLocationMessageSettings $settings,
		string $prefixMessage,
		?string $coordinatesSuffixMessage = null,
		array $pregeneratedLinks = [],
		array $descriptions = [],
		?AddressInterface $address = null,
	): string;
}
