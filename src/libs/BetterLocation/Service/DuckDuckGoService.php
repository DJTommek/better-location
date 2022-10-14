<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;

final class DuckDuckGoService extends AbstractService
{
	const ID = 21;
	const NAME = 'DuckDuckGo';
	const NAME_SHORT = 'DDG';

	const LINK = 'https://duckduckgo.com';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return self::LINK . sprintf('/?q=%1$F,%2$F&iaxm=maps', $lat, $lon);
		}
	}

	public function isValid(): bool
	{
		return false; // Currently not implemented
	}

	public function process(): void
	{
		throw new NotSupportedException('Processing is not available.');
	}
}
