<?php declare(strict_types=1);

namespace App\BetterLocation;

class Description
{
	public function __construct(
		public string          $content,
		public string|int|null $key = null
	)
	{
	}

	public function __toString(): string
	{
		return $this->content;
	}
}
