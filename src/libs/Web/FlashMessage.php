<?php declare(strict_types=1);

namespace App\Web;

readonly class FlashMessage
{
	/**
	 * @param string $content Content to be displayed.
	 * @param Flash $type How will be message formatted and displayed
	 * @param ?int $dismiss int = milliseconds after message should dissapear, null = user has to close manually
	 */
	public function __construct(
		public string $content,
		public Flash  $type = Flash::INFO,
		public ?int   $dismiss = 4_000
	)
	{
		if (is_int($dismiss)) {
			assert($dismiss > 0);
		}
	}
}

