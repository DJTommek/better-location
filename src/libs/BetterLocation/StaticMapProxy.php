<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Config;
use App\Factory;

class StaticMapProxy
{
	const CACHE_PATH = Config::FOLDER_DATA . '/staticmap';
	const HASH_ALGORITHM = 'fnv1a64';

	/** @var BetterLocation[] */
	private $markers = [];
	private $lock = false;

	private $cacheId = null;
	private $urlOriginal = null;
	private $urlCached = null;
	private $fileCached = null;


	public function __construct()
	{
		if (is_null(Config::STATIC_MAPS_PROXY_URL)) {
			throw new \Exception('Public cache URL is not set in local config.');
		}
	}

	private function throwIfLocked(): void {
		if ($this->lock) {
			throw new \Exception('Object is already locked, can\'t be updated anymore');
		}
	}

	public function addMarker(BetterLocation $marker): self
	{
		$this->throwIfLocked();
		$this->markers[] = $marker;
		return $this;
	}

	public function addMarkers(BetterLocationCollection $markers): self
	{
		$this->throwIfLocked();
		foreach ($markers->getLocations() as $marker) {
			$this->addMarker($marker);
		}
		return $this;
	}

	public function run(): self
	{
		$this->lock = true;
		$this->urlOriginal = $this->generateUrlOriginal();
		$this->cacheId = $this->generateCacheId();
		$this->fileCached = self::generateCachePath($this->cacheId);
		if ($this->cacheHit($this->fileCached) === false) {
			$this->downloadImage();
		}
		$this->urlCached = $this->generateCacheUrl();
		return $this;
	}

	public function getUrl() {
		return $this->urlCached;
	}

	public static function cacheHit(string $filePath): bool {
		return file_exists($filePath);
	}

	/**
	 * @TODO Add check if file was really downloaded and saved
	 */
	private function downloadImage() {
		file_put_contents($this->fileCached, file_get_contents($this->urlOriginal));
	}

	private function generateUrlOriginal(): string
	{
		$api = Factory::BingStaticMaps();
		foreach ($this->markers as $key => $marker) {
			$api->addPushpin($marker->getLat(), $marker->getLon(), null, (string)($key + 1));
		}
		return $api->generateLink();
	}

	private function generateCacheId(): string
	{
		return hash(self::HASH_ALGORITHM, $this->urlOriginal);
	}

	public static function generateCachePath(string $cacheId): string
	{
		return sprintf('%s/%s.jpg', self::CACHE_PATH, $cacheId);
	}

	private function generateCacheUrl(): string
	{
		return Config::STATIC_MAPS_PROXY_URL . $this->cacheId;
	}
}
