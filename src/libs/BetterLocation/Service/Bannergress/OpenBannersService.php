<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Bannergress;

final class OpenBannersService extends BannergressAbstractService
{
	const ID = 49;
	const NAME = 'OpenBanners';
	public const TAGS = [];

	protected function isValidDomain(): bool
	{
		return $this->url->getDomain() === 'openbanners.org';
	}

	protected function mosaicUrl(string $mosaicId): string
	{
		return 'https://www.openbanners.org/banner/' . $mosaicId;
	}
}
