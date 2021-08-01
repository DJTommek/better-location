<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Config;
use App\Database;
use App\Factory;
use Nette\Http\UrlImmutable;

class StaticMapProxy
{
	const CACHE_FOLDER = Config::FOLDER_TEMP . '/staticmap';
	const HASH_ALGORITHM = 'fnv1a64';

	/** @var BetterLocation[] */
	private $markers = [];
	private $markersParams = [];
	private $lock = false;

	private $db;

	/** @var ?string */
	private $cacheId = null;
	/** @var ?string */
	private $urlOriginal = null;
	/** @var ?UrlImmutable */
	private $urlCached = null;
	/** @var ?string  */
	private $fileCached = null;


	public function __construct(Database $database)
	{
		$this->db = $database;
		if (is_dir(self::CACHE_FOLDER) === false && @mkdir(self::CACHE_FOLDER, 0755, true) === false) {
			throw new \Exception(sprintf('Error while creating folder for Static map proxy cached responses: "%s"', error_get_last()['message']));
		}
	}

	private function throwIfLocked(): void
	{
		if ($this->lock) {
			throw new \Exception('Object is already locked, can\'t be updated anymore');
		}
	}

	public function addMarker(BetterLocation $marker, array $params = []): self
	{
		$this->throwIfLocked();
		$this->markers[] = $marker;
		$this->markersParams[] = $params;
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

	public function downloadAndCache(array $mapParams = []): self
	{
		$this->lock = true;
		$this->urlOriginal = $this->generateUrlOriginal($mapParams);
		$this->cacheId = $this->generateCacheId();
		$this->fileCached = $this->generateCachePath();
		if ($this->cacheHit() === false) {
			$this->downloadImage();
		}
		$this->saveToDb();
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

	public function getUrl(): UrlImmutable
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
		// Not using INSERT IGNORE as it ignores ALL errors so run update, see https://stackoverflow.com/a/4920619/3334403
		$sql = 'INSERT INTO better_location_static_map_cache (id, url) VALUES (?, ?) ON DUPLICATE KEY UPDATE url=url';
		$this->db->query($sql, $this->cacheId, $this->urlOriginal);
	}

	private function generateUrlOriginal(array $mapParams = []): string
	{
		$api = Factory::BingStaticMaps();
		foreach ($this->markers as $key => $marker) {
			$markerParams = $this->markersParams[$key] ?? [];
			$iconStyle =  $markerParams['iconStyle'] ?? null;
			$label = $markerParams['label'] ?? (string)($key + 1);
			$api->addPushpin($marker->getLat(), $marker->getLon(), $iconStyle, $label);
		}
		return $api->generateLink($mapParams);
	}

	private function generateCacheId(): string
	{
		return hash(self::HASH_ALGORITHM, $this->urlOriginal);
	}

	public function generateCachePath(): string
	{
		return sprintf('%s/%s.jpg', self::CACHE_FOLDER, $this->cacheId);
	}

	private function generateCacheUrl(): UrlImmutable
	{
		return Config::getStaticImageUrl()->withQueryParameter('id', $this->cacheId);
	}
}
