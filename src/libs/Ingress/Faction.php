<?php declare(strict_types=1);

namespace App\Ingress;

enum Faction: string
{
	case Enlightened = 'enlightened';
	case Resistance = 'resistance';
	case Machina = 'machina';
	case Neutral = 'neutral';

	public function label(): string
	{
		return match ($this) {
			self::Enlightened => 'Enlightened',
			self::Resistance => 'Resistance',
			self::Machina => 'Machina',
			self::Neutral => 'Neutral',
		};
	}
}
