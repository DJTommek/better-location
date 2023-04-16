<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Config;
use App\Factory;
use App\Repository\StaticMapCacheRepository;
use App\Utils\Coordinates;
use Nette\Http\UrlImmutable;
use Nette\Utils\FileSystem;

/**
 * Handle generating and loading static map image.
 */
class StaticMapProxy
{
	const CACHE_FOLDER = Config::FOLDER_TEMP . '/staticmap';
	const HASH_ALGORITHM = 'fnv1a64';

	/** @var StaticMapCacheRepository */
	private $staticMapCacheRepository;

	/** @var string */
	private $privateUrl;
	/** @var UrlImmutable */
	private $publicUrl;

	private function __construct()
	{
		FileSystem::createDir(self::CACHE_FOLDER);

		$db = Factory::database();
		$this->staticMapCacheRepository = new StaticMapCacheRepository($db);
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
		if ($entity = $self->staticMapCacheRepository->fromId($cacheId)) {
			$self->privateUrl = $entity->url;
			$self->process();
			return $self;
		} else {
			return null;
		}
	}

	/**
	 * Load static map image based on provided input (single or multiple locations).
	 *
	 * @param Coordinates|Coordinates[]|BetterLocation|BetterLocationCollection $input
	 */
	public static function fromLocations($input): ?self
	{
		if (!Config::isBingStaticMaps()) {
			return null;
		}

		$self = new self();
		$markers = [];
		if (is_iterable($input)) {
			foreach ($input as $location) {
				if ($location instanceof Coordinates) {
					$markers[] = $location;
				} else if ($location instanceof BetterLocation) {
					$markers[] = $location->getCoordinates();
				} else {
					throw new \InvalidArgumentException('Invalid location in iterable.');
				}
			}
		} else if ($input instanceof Coordinates) {
			$markers[] = $input;
		} else if ($input instanceof BetterLocation) {
			$markers[] = $input->getCoordinates();
		} else {
			throw new \InvalidArgumentException('Invalid location.');
		}
		$self->privateUrl = self::generatePrivateUrl($markers);
		$self->process();
		return $self;
	}

	/** Save to database and create cached file, both only if it was not done already. */
	private function process(): void
	{
		$entity = $this->staticMapCacheRepository->fromId($this->cacheId());
		if ($entity === null) {
			$this->staticMapCacheRepository->save($this->cacheId(), $this->privateUrl());
		}
		if ($this->cacheHit() === false) {
			$this->cacheSave();
		}
	}

	/** @return UrlImmutable Public URL to generated image which can be shared to public. */
	public function publicUrl(): UrlImmutable
	{
		if (is_null($this->publicUrl)) {
			$this->publicUrl = Config::getStaticImageUrl($this->cacheId());
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

	/** @param Coordinates[] $markers */
	private static function generatePrivateUrl(array $markers): string
	{
		$api = Factory::bingStaticMaps();
		foreach ($markers as $key => $marker) {
			$api->addPushpin($marker->getLat(), $marker->getLon(), null, (string)($key + 1));
		}
		return $api->generateLink();
	}

	private function cacheId(): string
	{
		return hash(self::HASH_ALGORITHM, $this->privateUrl());
	}

	private function cacheHit(): bool
	{
		return file_exists($this->cachePath());
	}

	private function cacheSave(): void
	{
		FileSystem::write($this->cachePath(), file_get_contents($this->privateUrl()));
	}

	public function cachePath(): string
	{
		return sprintf('%s/%s.jpg', self::CACHE_FOLDER, $this->cacheId());
	}
}
