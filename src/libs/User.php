<?php declare(strict_types=1);

namespace App;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use Nette\Utils\Strings;

class User
{
	private $db;

	private $id;
	private $telegramId;
	private $telegramDisplayname;
	private $lastKnownLocation;
	private $lastKnownLocationDatetime;

	/** @var UserSettings */
	private $settings;

	/** @var BetterLocationCollection */
	private $favourites;
	/** @var BetterLocationCollection */
	private $favouritesDeleted;

	public function __construct(int $telegramId, string $telegramDisplayname)
	{
		$this->telegramId = $telegramId;
		$this->telegramDisplayname = $telegramDisplayname;
		$this->db = Factory::Database();
		$this->settings = new UserSettings();
		$userData = $this->register($telegramId, $telegramDisplayname);
		$this->updateCachedData($userData);
		$this->updateFavouritesFromDb();
	}

	private function updateCachedData($newUserData)
	{
		$this->id = $newUserData['user_id'];
		$this->telegramId = $newUserData['user_telegram_id'];
		$this->telegramDisplayname = $newUserData['user_telegram_name'];
		if (isset($newUserData['user_location_lat']) and isset($newUserData['user_location_lon']) and isset($newUserData['user_location_last_update'])) {
			if (is_null($newUserData['user_location_lat']) || is_null($newUserData['user_location_lon']) || is_null($newUserData['user_location_last_update'])) {
				$this->lastKnownLocation = null;
				$this->lastKnownLocationDatetime = null;
			} else {
				$this->lastKnownLocation = BetterLocation::fromLatLon($newUserData['user_location_lat'], $newUserData['user_location_lon']);
				$this->lastKnownLocationDatetime = new \DateTimeImmutable($newUserData['user_location_last_update']);
				$this->lastKnownLocation->setPrefixMessage(sprintf('%s Last location', Icons::CURRENT_LOCATION));
				$this->lastKnownLocation->setDescription(sprintf('Last update %s', $this->lastKnownLocationDatetime->format(\App\Config::DATETIME_FORMAT_ZONE)));
			}
		}
		foreach ($newUserData as $key => $value) {
			if (Strings::startsWith($key, 'settings_')) {
				$this->settings->set($key, $value);
			}
		}
	}

	public function register(int $telegramId, ?string $telegramUsername = null)
	{
		$this->db->query('INSERT INTO better_location_user (user_telegram_id, user_telegram_name, user_last_update, user_registered) VALUES (?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP()) 
			ON DUPLICATE KEY UPDATE user_telegram_name = ?, user_last_update = UTC_TIMESTAMP()',
			$telegramId, $telegramUsername, $telegramUsername
		);
		return $this->loadFromDb();
	}

	/** @throws InvalidLocationException */
	public function setLastKnownLocation(float $lat, float $lon)
	{
		$this->update(null, $lat, $lon);
	}

	public function setSettingsPreview(bool $value)
	{
		$this->update(null, null, null, $value);
	}

	public function setSettingsSendNativeLocation(bool $value)
	{
		$this->update(null, null, null, null, $value);
	}

	private function loadFromDb()
	{
		return $this->db->query('SELECT * FROM better_location_user WHERE user_telegram_id = ?', $this->telegramId)->fetch();
	}

	public function updateFavouritesFromDb(): BetterLocationCollection
	{
		$favourites = $this->db->query('SELECT * FROM better_location_favourites WHERE user_id = ?', $this->id)->fetchAll(\PDO::FETCH_OBJ);
		$this->favourites = new BetterLocationCollection();
		$this->favouritesDeleted = new BetterLocationCollection();
		foreach ($favourites as $favouriteDb) {
			$location = BetterLocation::fromLatLon($favouriteDb->lat, $favouriteDb->lon);
			$location->setPrefixMessage(sprintf('%s %s', Icons::FAVOURITE, $favouriteDb->title));

			if ($favouriteDb->status === Database::ENABLED) {
				$this->favourites->add($location);
			} else if ($favouriteDb->status === Database::DELETED) {
				$this->favouritesDeleted->add($location);
			} else {
				throw new \Exception(sprintf('Unexpected type of favourites type: "%d"', $favouriteDb->status));
			}
		}
		return $this->getFavourites();
	}

	public function getFavourite(float $lat, float $lon): ?BetterLocation
	{
		return $this->favourites->getByLatLon($lat, $lon);
	}

	/**
	 * @param string|null $title used only if it never existed before
	 * @throws \Exception
	 */
	public function addFavourite(BetterLocation $betterLocation, ?string $title = null): BetterLocation
	{
		if ($this->getFavourite($betterLocation->getLat(), $betterLocation->getLon())) {
			// already saved
		} else if ($deletedFavourite = $this->favouritesDeleted->getByLatLon($betterLocation->getLat(), $betterLocation->getLon())) { // already saved but deleted
			$this->db->query('UPDATE better_location_favourites SET status = ? WHERE user_id = ? AND lat = ? AND lon = ?',
				Database::ENABLED, $this->id, $betterLocation->getLat(), $betterLocation->getLon()
			);
			$this->updateFavouritesFromDb();
		} else { // not in database at all
			$this->db->query('INSERT INTO better_location_favourites (user_id, status, lat, lon, title) VALUES (?, ?, ?, ?, ?)',
				$this->id, Database::ENABLED, $betterLocation->getLat(), $betterLocation->getLon(), $title
			);
			$this->updateFavouritesFromDb();
		}
		return $this->getFavourite($betterLocation->getLat(), $betterLocation->getLon());
	}

	/** @throws \Exception */
	public function deleteFavourite(BetterLocation $betterLocation): void
	{
		$this->db->query('UPDATE better_location_favourites SET status = ? WHERE user_id = ? AND lat = ? AND lon = ?',
			Database::DELETED, $this->id, $betterLocation->getLat(), $betterLocation->getLon()
		);
		$this->updateFavouritesFromDb();
	}

	/** @throws \Exception */
	public function renameFavourite(BetterLocation $betterLocation, string $title): BetterLocation
	{
		$this->db->query('UPDATE better_location_favourites SET title = ? WHERE user_id = ? AND lat = ? AND lon = ?',
			htmlspecialchars($title),
			$this->id, $betterLocation->getLat(), $betterLocation->getLon()
		);
		$this->updateFavouritesFromDb();
		return $this->getFavourite($betterLocation->getLat(), $betterLocation->getLon());
	}

	/** @throws InvalidLocationException */
	public function update(
		?string $telegramUsername = null,
		?float $locationLat = null,
		?float $locationLon = null,
		?bool $settingsPreview = null,
		?bool $settingsSendNativeLocation = null
	): self
	{
		$queries = [];
		$params = [];
		if (is_string($telegramUsername)) {
			$queries[] = 'user_telegram_name = ?';
			$params[] = $telegramUsername;
		}
		if (is_bool($settingsPreview)) {
			$queries[] = 'settings_preview = ?';
			$params[] = $settingsPreview ? Database::TRUE : Database::FALSE;
		}
		if (is_bool($settingsSendNativeLocation)) {
			$queries[] = 'settings_send_native_location = ?';
			$params[] = $settingsSendNativeLocation ? Database::TRUE : Database::FALSE;
		}
		if ($locationLat && $locationLon) {
			if (BetterLocation::isLatValid($locationLat) === false || BetterLocation::isLonValid($locationLon) === false) {
				throw new InvalidLocationException('Invalid coordinates');
			}
			$queries[] = 'user_location_lat = ?';
			$params[] = $locationLat;
			$queries[] = 'user_location_lon = ?';
			$params[] = $locationLon;
			$queries[] = 'user_location_last_update = UTC_TIMESTAMP()';
		}
		if (count($params) > 0) {
			$query = sprintf('UPDATE better_location_user SET %s WHERE user_telegram_id = ?', join($queries, ', '));

			$params[] = $this->telegramId;
			call_user_func_array([$this->db, 'query'], array_merge([$query], $params));
			$newData = $this->loadFromDb();
			$this->updateCachedData($newData);
		}
		return $this;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getTelegramId(): int
	{
		return $this->telegramId;
	}

	public function getTelegramDisplayname(): string
	{
		return $this->telegramDisplayname;
	}

	public function getFavourites(): BetterLocationCollection
	{
		return $this->favourites;
	}

	public function getLastKnownLocation(): ?BetterLocation
	{
		return $this->lastKnownLocation;
	}

	public function getLastKnownLocationDatetime(): ?\DateTimeImmutable
	{
		return $this->lastKnownLocationDatetime;
	}

	public function settings(): UserSettings
	{
		return $this->settings;
	}
}
