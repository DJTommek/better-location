<?php declare(strict_types=1);

namespace App\Factory;

use App\Address\AddressProvider;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\MessageGeneratorInterface;
use App\Ingress\PortalProvider;
use App\Pluginer\Pluginer;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\ProcessedMessageResult;

final readonly class ProcessedMessageResultFactory
{
	public function __construct(
		private ?PortalProvider $ingressPortalProvider = null,
		private ?AddressProvider $addressProvider = null,
	) {
	}

	public function create(
		BetterLocationCollection $collection,
		BetterLocationMessageSettings $messageSettings,
		MessageGeneratorInterface $messageGenerator,
		?Pluginer $pluginer = null,
		?bool $addressForce = null,
	): ProcessedMessageResult {
		return new ProcessedMessageResult(
			collection: $collection,
			messageSettings: $messageSettings,
			messageGenerator: $messageGenerator,
			pluginer: $pluginer,
			ingressPortalProvider: $this->ingressPortalProvider,
			addressProvider: $this->addressProvider,
			addressForce: $addressForce,
		);
	}
}
