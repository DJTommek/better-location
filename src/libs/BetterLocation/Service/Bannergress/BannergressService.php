<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Bannergress;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;

final class BannergressService extends BannergressAbstractService
{
	const ID = 23;
	const NAME = 'Bannergress';
	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return sprintf('https://bannergress.com/map?lat=%F&lng=%F&zoom=15', $lat, $lon);
		}
	}

	protected function isValidDomain(): bool
	{
		return $this->url->getDomain(0) === 'bannergress.com';
	}

	protected function mosaicUrl(string $mosaicId): string
	{
		return 'https://bannergress.com/banner/' . $mosaicId;
	}
}
