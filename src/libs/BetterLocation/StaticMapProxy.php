<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BingMaps\StaticMaps;
use App\Config;
use App\Factory;
use App\Repository\StaticMapCacheRepository;
use DJTommek\Coordinates\CoordinatesInterface;
use GuzzleHttp\RequestOptions;
use Nette\Http\UrlImmutable;
use Nette\Utils\FileSystem;

/**
 * Handle generating and loading static map image.
 */
class StaticMapProxy
{
	private const HASH_ALGORITHM = 'fnv1a64';

	private string $dir;

	private string $privateUrl;
	private string $cacheId;
	private UrlImmutable $publicUrl;
	private readonly \GuzzleHttp\Client $httpClient;

	public function __construct(
		private readonly StaticMapCacheRepository $staticMapCacheRepository,
	) {
		$this->dir = Config::getDataTempDir() . '/staticmap';
		FileSystem::createDir($this->dir);

		$this->httpClient = new \GuzzleHttp\Client([
			'base_uri' => StaticMaps::LINK,
			'timeout' => 5,
			'connection_timeout' => 5,
		]);
	}

	public function exists(): bool
	{
		return isset($this->cacheId);
	}

	public function initFromCacheId(string $cacheId): self
	{
		$entity = $this->staticMapCacheRepository->fromId($cacheId);

		if ($entity === null) {
			return $this;
		}

		$this->privateUrl = $entity->url;
		$this->cacheId = $entity->id;
		return $this;
	}

	/**
	 * Load static map image based on provided input (single or multiple locations).
	 *
	 * @param array<CoordinatesInterface>|BetterLocationCollection $locations
	 */
	public function initFromLocations(array|BetterLocationCollection $locations): self
	{
		if (!Config::isBingStaticMaps()) {
			return $this;
		}

		$markers = [];
		foreach ($locations as $location) {
			if (!$location instanceof CoordinatesInterface) {
				throw new \InvalidArgumentException('Invalid location.');
			}

			$markers[] = $location;
		}

		$this->privateUrl = self::generatePrivateUrl($markers);
		$this->cacheId = hash(self::HASH_ALGORITHM, $this->privateUrl());

		// If does not exists in database, yet, create new
		$entity = $this->staticMapCacheRepository->fromId($this->cacheId);
		if ($entity === null) {
			$this->staticMapCacheRepository->save($this->cacheId, $this->privateUrl());
		}

		return $this;
	}

	public function download(): void
	{
		if ($this->isCached()) {
			return;
		}
		$this->saveToCache();
	}

	/** @return UrlImmutable Public URL to generated image which can be shared to public. */
	public function publicUrl(): UrlImmutable
	{
		if (!isset($this->publicUrl)) {
			$this->publicUrl = Config::getStaticImageUrl($this->cacheId);
		}
		return $this->publicUrl;
	}

	/**
	 * @see Warning: Nette\Http\Url cant be used, see https://github.com/nette/http/issues/178
	 * @return string Private URL to generate image leading to external API, which cannot leak to public.
	 */
	private function privateUrl(): string
	{
		return $this->privateUrl;
	}

	/** @param CoordinatesInterface[] $markers */
	private static function generatePrivateUrl(array $markers): string
	{
		$api = Factory::bingStaticMaps();
		foreach ($markers as $key => $marker) {
			$api->addPushpin($marker->getLat(), $marker->getLon(), null, (string)($key + 1));
		}
		return $api->generateLink();
	}

	public function isCached(): bool
	{
		return file_exists($this->cachePath());
	}

	private function saveToCache(): void
	{
		$requestUrl = $this->privateUrl();
		$saveTo = $this->cachePath();
		$options = [
			RequestOptions::SINK => $saveTo,
		];
		$this->httpClient->get($requestUrl, $options);
	}

	public function cachePath(): string
	{
		return sprintf('%s/%s.jpg', $this->dir, $this->cacheId);
	}
}
