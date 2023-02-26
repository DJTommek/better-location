<?php declare(strict_types=1);

namespace App;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Repository\FavouritesRepository;
use App\Repository\UserEntity;
use App\Repository\UserRepository;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\Utils\Coordinates;

class User
{
	private FavouritesRepository $favouritesRepository;
	private UserRepository $userRepository;

	private UserEntity $userEntity;

	/** Lazy list of Favourites (should be accessed only via getFavourites()) */
	private ?BetterLocationCollection $favourites = null;
	/** Lazy (should be accessed only via getMessageSettings()) */
	private ?BetterLocationMessageSettings $messageSettings = null;

	public function __construct(int $telegramId, string $telegramDisplayname)
	{
		$db = Factory::Database();
		$this->userRepository = new UserRepository($db);
		$this->favouritesRepository = new FavouritesRepository($db);

		$userEntity = $this->userRepository->fromTelegramId($telegramId);

		if ($userEntity === null) {
			// Does not exists, yet, create new
			$this->userRepository->insert($telegramId, $telegramDisplayname);
			$userEntity = $this->userRepository->fromTelegramId($telegramId);
		}

		assert($userEntity instanceof UserEntity);
		$this->userEntity = $userEntity;
	}

	public function touchLastUpdate(): void
	{
		$this->update();
	}

	private function update(): void
	{
		$this->userRepository->update($this->userEntity);
		$this->userEntity = $this->userRepository->fromTelegramId($this->userEntity->telegramId);
	}

	public function setLastKnownLocation(float $lat, float $lon): void
	{
		$coords = new Coordinates($lat, $lon);
		$this->userEntity->setLastLocation($coords);
		$this->update();
	}

	public function getFavourite(float $lat, float $lon): ?BetterLocation
	{
		return $this->getFavourites()->getByLatLon($lat, $lon);
	}

	/**
	 * @param string|null $title used only if it never existed before
	 */
	public function addFavourite(BetterLocation $location, ?string $title = null): BetterLocation
	{
		if ($this->getFavourite($location->getLat(), $location->getLon()) === null) { // add only if it is not added already
			$this->favouritesRepository->add($this->userEntity->id, $location->getLat(), $location->getLon(), $title);
			$this->favourites = null; // clear cached favourites
		}
		return $this->getFavourite($location->getLat(), $location->getLon());
	}

	public function deleteFavourite(BetterLocation $location): void
	{
		$this->favouritesRepository->removeByUserLatLon($this->userEntity->id, $location->getLat(), $location->getLon());
		$this->favourites = null; // clear cached favourites
	}

	public function renameFavourite(BetterLocation $location, string $title): BetterLocation
	{
		$this->favouritesRepository->renameByUserLatLon($this->userEntity->id, $location->getLat(), $location->getLon(), $title);
		$this->favourites = null; // clear cached favourites
		return $this->getFavourite($location->getLat(), $location->getLon());
	}

	public function getId(): int
	{
		return $this->userEntity->id;
	}

	public function getTelegramId(): int
	{
		return $this->userEntity->telegramId;
	}

	public function getTelegramDisplayname(): string
	{
		return $this->userEntity->telegramName;
	}

	public function getFavourites(): BetterLocationCollection
	{
		if ($this->favourites === null) {
			$this->favourites = new BetterLocationCollection();
			foreach ($this->favouritesRepository->byUserId($this->userEntity->id) as $favourite) {
				$location = BetterLocation::fromLatLon($favourite->lat, $favourite->lon);
				$location->setPrefixMessage(sprintf('%s %s', Icons::FAVOURITE, $favourite->title));
				$this->favourites->add($location);
			}
		}
		return $this->favourites;
	}

	public function getLastKnownLocation(): ?BetterLocation
	{
		if ($this->userEntity->lastLocation) {
			$location = BetterLocation::fromLatLon($this->userEntity->getLat(), $this->userEntity->getLon());
			$location->setPrefixMessage(sprintf('%s Last location', Icons::CURRENT_LOCATION));

			// Show datetime of last location update in local timezone based on timezone on that location itself
			$geonames = Factory::Geonames()->timezone($location->getLat(), $location->getLon());
			$lastUpdate = $this->userEntity->lastLocationUpdate->setTimezone($geonames->timezone);

			$location->setDescription(sprintf('Last update %s', $lastUpdate->format(\App\Config::DATETIME_FORMAT_ZONE)));
			return $location;
		} else {
			return null;
		}
	}

	public function getLastKnownLocationDatetime(): ?\DateTimeImmutable
	{
		return $this->userEntity->lastLocationUpdate;
	}

	public function getMessageSettings(): BetterLocationMessageSettings
	{
		if ($this->messageSettings === null) {
			$this->messageSettings = BetterLocationMessageSettings::loadByChatId($this->userEntity->id);
		}
		return $this->messageSettings;
	}

	public function getEntity(): UserEntity
	{
		return $this->userEntity;
	}
}
