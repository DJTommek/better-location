<?php declare(strict_types=1);

namespace App\BetterLocation;

class Description implements \Stringable
{
	public const KEY_PRECISION = 'precision';

	public function __construct(
		public string          $content,
		public string|int|null $key = null
	)
	{
	}

	#[\Override]
 public function __toString(): string
	{
		return $this->content;
	}
}
