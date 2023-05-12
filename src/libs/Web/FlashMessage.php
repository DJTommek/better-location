<?php declare(strict_types=1);

namespace App\Web;

class FlashMessage
{
	const FLASH_SUCCESS = 'success';
	const FLASH_INFO = 'info';
	const FLASH_WARNING = 'warning';
	const FLASH_ERROR = 'danger';

	/**
	 * @param string $content Content to be displayed.
	 * @param string $type One of FlashMessage::FLASH_* constants.
	 * @param ?int $dismiss int = milliseconds after message should dissapear, null = user has to close manually
	 */
	public function __construct(
		public string $content,
		public string $type = self::FLASH_INFO,
		public ?int   $dismiss = 4_000
	)
	{
		assert(in_array($type, self::flashTypes(), true));
		if (is_int($dismiss)) {
			assert($dismiss > 0);
		}
	}

	/**
	 * @return array<string>
	 */
	private function flashTypes(): array
	{
		return [
			self::FLASH_SUCCESS,
			self::FLASH_INFO,
			self::FLASH_WARNING,
			self::FLASH_ERROR,
		];
	}
}

