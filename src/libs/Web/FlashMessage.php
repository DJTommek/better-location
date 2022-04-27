<?php declare(strict_types=1);

namespace App\Web;

class FlashMessage
{
	const FLASH_INFO = 'info';
	const FLASH_WARNING = 'warning';
	const FLASH_ERROR = 'danger';

	public string $text;
	public string $type;
	public ?int $dismiss;

	public function __construct(string $text, string $type, ?int $dismiss)
	{
		assert(in_array($type, self::flashTypes(), true));
		if (is_int($dismiss)) {
			assert($dismiss > 0);
		}
		$this->text = $text;
		$this->type = $type;
		$this->dismiss = $dismiss;
	}

	private function flashTypes(): array
	{
		return [
			self::FLASH_INFO,
			self::FLASH_WARNING,
			self::FLASH_ERROR,
		];
	}
}

