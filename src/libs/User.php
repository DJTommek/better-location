<?php declare(strict_types=1);

namespace App;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Repository\FavouritesRepository;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\Utils\Coordinates;
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

	/** @var ?BetterLocationMessageSettings */
	private $messageSettings;

	/** @var FavouritesRepository */
	private $favouritesRepository;

	public function __construct(int $telegramId, string $telegramDisplayname)
	{
		$this->telegramId = $telegramId;
		$this->telegramDisplayname = $telegramDisplayname;
		$this->db = Factory::Database();
		$this->favouritesRepository = new FavouritesRepository($this->db);
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
		$this->favourites = new BetterLocationCollection();
		foreach ($this->favouritesRepository->byUserId($this->id) as $favourite) {
			$location = BetterLocation::fromLatLon($favourite->lat, $favourite->lon);
			$location->setPrefixMessage(sprintf('%s %s', Icons::FAVOURITE, $favourite->title));
			$this->favourites->add($location);
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
	public function addFavourite(BetterLocation $location, ?string $title = null): BetterLocation
	{
		if ($this->getFavourite($location->getLat(), $location->getLon()) === null) { // add only if it is not added already
			$this->favouritesRepository->add($this->id, $location->getLat(), $location->getLon(), $title);
			$this->updateFavouritesFromDb();
		}
		return $this->getFavourite($location->getLat(), $location->getLon());
	}

	/** @throws \Exception */
	public function deleteFavourite(BetterLocation $location): void
	{
		$this->favouritesRepository->removeByUserLatLon($this->id, $location->getLat(), $location->getLon());
		$this->updateFavouritesFromDb();
	}

	/** @throws \Exception */
	public function renameFavourite(BetterLocation $location, string $title): BetterLocation
	{
		$this->favouritesRepository->renameByUserLatLon($this->id, $location->getLat(), $location->getLon(), $title);
		$this->updateFavouritesFromDb();
		return $this->getFavourite($location->getLat(), $location->getLon());
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
			if (Coordinates::isLat($locationLat) === false || Coordinates::isLon($locationLon) === false) {
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

	public function getMessageSettings(): BetterLocationMessageSettings
	{
		if ($this->messageSettings === null) {
			$this->messageSettings = BetterLocationMessageSettings::loadByChatId($this->id);
		}
		return $this->messageSettings;
	}
}
