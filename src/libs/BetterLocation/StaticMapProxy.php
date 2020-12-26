<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Config;
use App\Database;
use App\Factory;

class StaticMapProxy
{
	const CACHE_PATH = Config::FOLDER_DATA . '/staticmap';
	const HASH_ALGORITHM = 'fnv1a64';

	/** @var BetterLocation[] */
	private $markers = [];
	private $lock = false;

	private $db;

	private $cacheId = null;
	private $urlOriginal = null;
	private $urlCached = null;
	private $fileCached = null;


	public function __construct(Database $database)
	{
		if (is_null(Config::STATIC_MAPS_PROXY_URL)) {
			throw new \Exception('Public cache URL is not set in local config.');
		}
		$this->db = $database;
	}

	private function throwIfLocked(): void
	{
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

	public function downloadAndCache(): self
	{
		$this->lock = true;
		$this->urlOriginal = $this->generateUrlOriginal();
		$this->cacheId = $this->generateCacheId();
		$this->fileCached = $this->generateCachePath();
		// @TODO check if it's saved to database
		if ($this->cacheHit() === false) {
			$this->downloadImage();
			$this->saveToDb();
		}
		$this->urlCached = $this->generateCacheUrl();
		return $this;
	}

	public function loadById(string $id): ?self
	{
		$this->lock = true;
		$this->cacheId = $id;
		if ($originalUrl = $this->loadFromDb()) {
			$this->urlOriginal = $originalUrl;
		} else {
			return null;
		}
		$this->fileCached = $this->generateCachePath();
		if ($this->cacheHit()) {
			$this->urlCached = $this->generateCacheUrl();
			return $this;
		} else {
			return null;
		}
	}

	public function getUrl(): string
	{
		return $this->urlCached;
	}

	public function cacheHit(): bool
	{
		return file_exists($this->fileCached);
	}

	/**
	 * @TODO Add check if file was really downloaded and saved
	 */
	private function downloadImage(): void
	{
		file_put_contents($this->fileCached, file_get_contents($this->urlOriginal));
	}

	private function loadFromDb(): ?string
	{
		$result = $this->db->query('SELECT url FROM better_location_static_map_cache WHERE id = ?', $this->cacheId)->fetchColumn();
		return $result === false ? null : $result;
	}

	private function saveToDb(): void
	{
		$this->db->query('INSERT INTO better_location_static_map_cache (id, url) VALUES (?, ?)', $this->cacheId, $this->urlOriginal);
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

	public function generateCachePath(): string
	{
		return sprintf('%s/%s.jpg', self::CACHE_PATH, $this->cacheId);
	}

	private function generateCacheUrl(): string
	{
		return Config::STATIC_MAPS_PROXY_URL . $this->cacheId;
	}
}
