<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Icons;
use App\MiniCurl\MiniCurl;

final class BannergressService extends AbstractService
{
	const ID = 23;
	const NAME = 'Bannergress';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return sprintf('https://bannergress.com/map?lat=%F&lng=%F&zoom=15', $lat, $lon);
		}
	}

	public function isValid(): bool
	{
		if (
			$this->url &&
			$this->url->getDomain(0) === 'bannergress.com' &&
			preg_match('/^\/banner\/(.+)$/', $this->url->getPath(), $matches)
		) {
			$this->data->mosaicId = $matches[1];
			return true;
		}
		return false;
	}

	public function process(): void
	{
		$mosaic = $this->loadApi($this->data->mosaicId);
		$mosaicUrl = 'https://bannergress.com/banner/' . $mosaic->id;
		$mosaicPicture = 'https://api.bannergress.com' . $mosaic->picture;
		$location = new BetterLocation($this->inputUrl, $mosaic->startLatitude, $mosaic->startLongitude, self::class);
		$location->setInlinePrefixMessage(sprintf('%s %s', self::NAME, $mosaic->title));
		$location->setPrefixMessage(sprintf('<a href="%s">%s %s</a> <a href="%s">%s</a>', $mosaicUrl, self::NAME, $mosaic->title, $mosaicPicture, Icons::PICTURE));
		$location->setDescription(sprintf('%d missions, %.1F km', $mosaic->numberOfMissions, $mosaic->lengthMeters / 1000));
		$this->collection->add($location);
	}

	private function loadApi(string $mosaicId): \stdClass
	{
		$response = (new MiniCurl('https://api.bannergress.com/bnrs/' . $mosaicId))
			->allowCache(Config::CACHE_TTL_BANNERGRESS)
			->run()
			->getBody();
		return json_decode($response);
	}
}
