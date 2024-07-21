<?php declare(strict_types=1);

namespace App\Factory;

use App\BetterLocation\BetterLocationCollection;
use App\IngressLanchedRu\Client as LanchedRuClient;
use App\Pluginer\Pluginer;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\ProcessedMessageResult;

final readonly class ProcessedMessageResultFactory
{
	public function __construct(
		private ?LanchedRuClient $lanchedRuClient = null,
	) {
	}

	public function create(
		BetterLocationCollection $collection,
		BetterLocationMessageSettings $messageSettings,
		?Pluginer $pluginer = null,
		?bool $addressForce = null,
	): ProcessedMessageResult {
		return new ProcessedMessageResult(
			$collection,
			$messageSettings,
			$pluginer,
			$this->lanchedRuClient,
			$addressForce,
		);
	}
}
