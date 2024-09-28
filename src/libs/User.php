<?php declare(strict_types=1);

namespace App;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Repository\FavouritesRepository;
use App\Repository\UserEntity;
use App\Repository\UserRepository;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use DJTommek\Coordinates\CoordinatesImmutable;

class User
{
	/** Lazy list of Favourites (should be accessed only via getFavourites()) */
	private ?BetterLocationCollection $favourites = null;
	/** Lazy (should be accessed only via getMessageSettings()) */
	private ?BetterLocationMessageSettings $messageSettings = null;

	public function __construct(
		private readonly UserRepository $userRepository,
		private readonly FavouritesRepository $favouritesRepository,
		private UserEntity $userEntity,
		private Chat $privateChat,
	) {
	}

	public function setLastUpdate(\DateTimeInterface $lastUpdate): void
	{
		$this->userEntity->lastUpdate = \DateTimeImmutable::createFromInterface($lastUpdate);
		$this->update();
	}

	private function update(): void
	{
		$this->userRepository->update($this->userEntity);
		$this->userEntity = $this->userRepository->findByTelegramId($this->userEntity->telegramId);
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
			foreach ($this->favouritesRepository->findByUser($this->userEntity->id) as $favourite) {
				$location = BetterLocation::fromLatLon($favourite->lat, $favourite->lon);
				$location->setPrefixMessage(sprintf('%s %s', Icons::FAVOURITE, htmlspecialchars($favourite->title)));
				$this->favourites->add($location);
			}
		}
		return $this->favourites;
	}

	public function getLastCoordinates(): ?CoordinatesImmutable
	{
		return $this->userEntity->lastLocation;
	}

	public function getLastCoordinatesDatetime(): ?\DateTimeImmutable
	{
		return $this->userEntity->lastLocationUpdate;
	}

	public function getMessageSettings(): BetterLocationMessageSettings
	{
		if ($this->messageSettings === null) {
			$this->messageSettings = BetterLocationMessageSettings::loadByChatId($this->getPrivateChat()->getEntity()->id);
		}
		return $this->messageSettings;
	}

	public function getEntity(): UserEntity
	{
		return $this->userEntity;
	}

	public function getPrivateChat(): Chat
	{
		return $this->privateChat;
	}
}
