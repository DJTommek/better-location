<?php declare(strict_types=1);

namespace App\Web\Admin;

use App\Web\Flash;

class TesterResult
{
	public function __construct(
		public readonly string $input,
		public readonly ?string $resultHtml = null,
		public readonly ?Flash $resultSeverity = null,
		public readonly ?string $betterLocationTextHtml = null,
		/**
		 * @var array<array<\unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button>>
		 */
		public readonly array $betterLocationButtons = [],
	) {
	}
}

