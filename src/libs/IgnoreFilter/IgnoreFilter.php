<?php declare(strict_types=1);

namespace App\IgnoreFilter;

final readonly class IgnoreFilter
{
	public function __construct(
		public IgnoreFilterParams $params,
	) {
	}

	public function matches(int|string $telegramSenderId): bool
	{
		if (in_array($telegramSenderId, $this->params->ignoredTelegramSenderIds, true)) {
			return true;
		}

		return false;
	}
}
