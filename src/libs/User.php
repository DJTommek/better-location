<?php

class User
{
	private $db;

	private $id;
	private $telegramId;
	private $telegramUsername;
	/**
	 * @var \BetterLocation\BetterLocation[]
	 */
	private $favourites = [];

	/**
	 * User constructor.
	 *
	 * @param int $telegramId
	 * @param string|null $telegramUsername
	 */
	public function __construct(int $telegramId, ?string $telegramUsername = null) {
		$this->telegramId = $telegramId;
		$this->telegramUsername = $telegramUsername;
		$this->db = Factory::Database();
		$userData = $this->register($telegramId, $telegramUsername);
		$this->updateCachedData($userData);
		$this->loadFavourites();
	}

	private function updateCachedData($newUserData) {
		$this->id = $newUserData['user_id'];
		$this->telegramId = $newUserData['user_telegram_id'];
		$this->telegramUsername = $newUserData['user_telegram_name'];
	}

	public function register(int $telegramId, ?string $telegramUsername = null) {
		$this->db->query('INSERT INTO better_location_user (user_telegram_id, user_telegram_name, user_last_update, user_registered) VALUES (?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP()) 
			ON DUPLICATE KEY UPDATE user_telegram_name = ?, user_last_update = UTC_TIMESTAMP()',
			$telegramId, $telegramUsername, $telegramUsername
		);
		return $this->load();
	}

	public function load() {
		return $this->db->query('SELECT * FROM better_location_user WHERE user_telegram_id = ?', $this->telegramId)->fetchAll()[0];
	}

	public function loadFavourites() {
		$favourites = $this->db->query('SELECT * FROM better_location_favourites WHERE user_id = ?', $this->id)->fetchAll(\PDO::FETCH_OBJ);
		$this->favourites = [];
		foreach ($favourites as $favourite) {
			$key = sprintf('%f,%f', $favourite->lat, $favourite->lon);
			$this->favourites[$key] = new \BetterLocation\BetterLocation(
				$favourite->lat,
				$favourite->lon,
				sprintf('%s %s', Icons::FAVOURITE, $favourite->title),
			);
		}
		return $this->getFavourites();
	}

	public function getFavourite(float $lat, float $lon): ?\BetterLocation\BetterLocation {
		$key = sprintf('%f,%f', $lat, $lon);
		if (isset($this->favourites[$key])) {
			return $this->favourites[$key];
		} else {
			return null;
		}
	}

	public function addFavourites(\BetterLocation\BetterLocation $betterLocation, ?string $title = null) {
		// @TODO check if new location is not already saved in $this->favourites
		try {
			$this->db->query('INSERT INTO better_location_favourites (user_id, lat, lon, title) VALUES (?, ?, ?, ?)',
				$this->id, $betterLocation->getLat(), $betterLocation->getLon(), $title
			);
			return true;
		} catch (\Exception $exception) {
			\Tracy\Debugger::log(sprintf('Error while adding favourite location: %s', \Tracy\ILogger::ERROR));
			return false;
		}
	}

	public function removeFavourite(\BetterLocation\BetterLocation $betterLocation) {
		try {
			$key = sprintf('%f,%f', $betterLocation->getLat(), $betterLocation->getLon());
			unset($this->favourites[$key]);
			$this->db->query('DELETE FROM better_location_favourites WHERE user_id = ? AND lat = ? AND lon = ?',
				$this->id, $betterLocation->getLat(), $betterLocation->getLon()
			);
			return true;
		} catch (\Exception $exception) {
			\Tracy\Debugger::log(sprintf('Error while removing favourite location: %s', \Tracy\ILogger::ERROR));
			return false;
		}
	}

	public function update(?string $telegramUsername = null) {
		$queries = [];
		$params = [];
		if (is_string($telegramUsername)) {
			$queries[] = 'user_telegram_name = ?';
			$params[] = $telegramUsername;
		}
		if (count($params) > 0) {
			$query = sprintf('UPDATE better_location_user SET %s WHERE user_telegram_id = ?', join($queries, ', '));

			$params[] = $this->telegramId;
			call_user_func_array([$this->db, 'query'], array_merge([$query], $params));
			$newData = $this->load();
			$this->updateCachedData($newData);
		}
		return $this->get();
	}

	public function get() {
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getTelegramId() {
		return $this->telegramId;
	}

	/**
	 * @return mixed
	 */
	public function getTelegramUsername() {
		return $this->telegramUsername;
	}

	/**
	 * @return \BetterLocation\BetterLocation[]
	 */
	public function getFavourites() {
		return $this->favourites;
	}
}