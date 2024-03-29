<?php declare(strict_types=1);

namespace App;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Repository\ChatEntity;
use App\Repository\ChatRepository;
use App\Repository\FavouritesRepository;
use App\Repository\UserEntity;
use App\Repository\UserRepository;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use DJTommek\Coordinates\CoordinatesImmutable;

class User
{
	private FavouritesRepository $favouritesRepository;
	private UserRepository $userRepository;
	private ChatRepository $chatRepository;

	private UserEntity $userEntity;
	/** Lazy (should be accessed only via getPrivateChatEntity()) */
	private ChatEntity $userPrivateChatEntity;

	/** Lazy list of Favourites (should be accessed only via getFavourites()) */
	private ?BetterLocationCollection $favourites = null;
	/** Lazy (should be accessed only via getMessageSettings()) */
	private ?BetterLocationMessageSettings $messageSettings = null;

	public function __construct(int $telegramId, string $telegramDisplayname)
	{
		$db = Factory::database();
		$this->userRepository = new UserRepository($db);
		$this->chatRepository = new ChatRepository($db);
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

	public function getPrivateChatEntity(): ChatEntity
	{
		if (!isset($this->userPrivateChatEntity)) {
			$userTgId = $this->getTelegramId();
			$chatEntity = $this->chatRepository->fromTelegramId($userTgId);
			if ($chatEntity === null) {
				throw new \RuntimeException(sprintf('User ID %d (TG ID = %d) does not has private chat settings, yet.', $this->getId(), $userTgId));
			}
			$this->userPrivateChatEntity = $chatEntity;
		}
		return $this->userPrivateChatEntity;

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

	public function setLastKnownLocation(float $lat, float $lon, \DateTimeInterface $datetime = null): void
	{
		$coords = new CoordinatesImmutable($lat, $lon);
		$this->userEntity->setLastLocation($coords, $datetime);
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
			$location = BetterLocation::fromCoords($this->userEntity->lastLocation);
			$location->setPrefixMessage(sprintf('%s Last location', Icons::CURRENT_LOCATION));

			// Show datetime of last location update in local timezone based on timezone on that location itself
			$geonames = Factory::geonames()->timezone($location->getLat(), $location->getLon());
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
			$chatEntity = $this->getPrivateChatEntity();
			$this->messageSettings = BetterLocationMessageSettings::loadByChatId($chatEntity->id);
		}
		return $this->messageSettings;
	}

	public function getEntity(): UserEntity
	{
		return $this->userEntity;
	}
}
