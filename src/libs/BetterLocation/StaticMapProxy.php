<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BingMaps\StaticMaps;
use App\Config;
use App\Factory;
use App\Repository\StaticMapCacheRepository;
use DJTommek\Coordinates\CoordinatesInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Nette\Http\UrlImmutable;
use Nette\Utils\FileSystem;

/**
 * Handle generating and loading static map image.
 */
class StaticMapProxy
{
	private const HASH_ALGORITHM = 'fnv1a64';

	private StaticMapCacheRepository $staticMapCacheRepository;
	private string $dir;

	private string $privateUrl;
	private string $cacheId;
	private UrlImmutable $publicUrl;
	private readonly \GuzzleHttp\Client $httpClient;

	private function __construct()
	{
		$this->dir = Config::getDataTempDir() . '/staticmap';
		FileSystem::createDir($this->dir);

		$db = Factory::database();
		$this->staticMapCacheRepository = new StaticMapCacheRepository($db);
		$this->httpClient = new \GuzzleHttp\Client([
			'base_uri' => StaticMaps::LINK,
			'timeout' => 5,
			'connection_timeout' => 5,
		]);
	}

	/**
	 * Load static map image based on previously generated cacheId (saved in database)
	 *
	 * If cached file is not available, new file will be generated and saved.
	 *
	 * @param string $cacheId
	 * @return ?self Return null if cacheId does not exists.
	 */
	public static function fromCacheId(string $cacheId): ?self
	{
		$self = new self();
		$entity = $self->staticMapCacheRepository->fromId($cacheId);

		if ($entity === null) {
			return null;
		}

		$self->privateUrl = $entity->url;
		$self->cacheId = $entity->id;
		return $self;
	}

	/**
	 * Load static map image based on provided single location.
	 */
	public static function fromLocation(CoordinatesInterface $input): ?self
	{
		return self::fromLocations([$input]);
	}

	/**
	 * Load static map image based on provided input (single or multiple locations).
	 *
	 * @param array<CoordinatesInterface>|BetterLocationCollection $locations
	 */
	public static function fromLocations(array|BetterLocationCollection $locations): ?self
	{
		if (!Config::isBingStaticMaps()) {
			return null;
		}

		$markers = [];
		foreach ($locations as $location) {
			if (!$location instanceof CoordinatesInterface) {
				throw new \InvalidArgumentException('Invalid location.');
			}

			$markers[] = $location;
		}

		$self = new self();
		$self->privateUrl = self::generatePrivateUrl($markers);
		$self->cacheId = hash(self::HASH_ALGORITHM, $self->privateUrl());

		// If does not exists in database, yet, create new
		$entity = $self->staticMapCacheRepository->fromId($self->cacheId);
		if ($entity === null) {
			$self->staticMapCacheRepository->save($self->cacheId, $self->privateUrl());
		}

		return $self;
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
