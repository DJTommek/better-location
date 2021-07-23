<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotImplementedException;

final class DuckDuckGoService extends AbstractService
{
	const ID = 21;
	const NAME = 'DuckDuckGo';
	const NAME_SHORT = 'DDG';

	const LINK = 'https://duckduckgo.com';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotImplementedException('Drive link is not implemented.');
		} else {
			return self::LINK . sprintf('/?q=%1$f,%2$f&iaxm=maps', $lat, $lon);
		}
	}

	public function isValid(): bool
	{
		throw new NotImplementedException('Validating is not implemented.');
	}

	public function process()
	{
		throw new NotImplementedException('Processing is not available.');
	}
}
