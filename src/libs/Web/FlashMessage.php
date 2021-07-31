<?php declare(strict_types=1);

namespace App\Web;

class FlashMessage
{
	const FLASH_INFO = 'info';
	const FLASH_WARNING = 'warning';
	const FLASH_ERROR = 'danger';

	private function flashTypes(): array
	{
		return [
			self::FLASH_INFO,
			self::FLASH_WARNING,
			self::FLASH_ERROR,
		];
	}

	public $text;
	public $type;

	public function __construct(string $text, string $type)
	{
		assert(in_array($type, self::flashTypes(), true));
		$this->text = $text;
		$this->type = $type;
	}
}

