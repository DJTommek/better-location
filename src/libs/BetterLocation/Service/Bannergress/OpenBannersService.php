<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Bannergress;

final class OpenBannersService extends BannergressAbstractService
{
	const int ID = 49;
	const string NAME = 'OpenBanners';
	public const TAGS = [];

	#[\Override]
 protected function isValidDomain(): bool
	{
		return $this->url->getDomain() === 'openbanners.org';
	}

	#[\Override]
 protected function mosaicUrl(string $mosaicId): string
	{
		return 'https://www.openbanners.org/banner/' . $mosaicId;
	}
}
