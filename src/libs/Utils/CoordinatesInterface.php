<?php declare(strict_types=1);

namespace App\Utils;

interface CoordinatesInterface
{
	public function getLat(): float;
	public function getLon(): float;
}
