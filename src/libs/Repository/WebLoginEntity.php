<?php declare(strict_types=1);

namespace App\Repository;

use App\Utils\DateImmutableUtils;
use App\Utils\Strict;
use Nette\Http\UrlImmutable;

class WebLoginEntity extends Entity
{
	/** @var string */
	public $hash;
	/** @var int */
	public $userTelegramId;
	/** @var \DateTimeImmutable */
	public $authDate;
	/** @var ?string */
	public $userName;
	/** @var ?string */
	public $userFirstName;
	/** @var ?string */
	public $userLastNAme;
	/** @var UrlImmutable */
	public $userPhotoUrl;

	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->hash = $row['hash'];
		$entity->userTelegramId = $row['user_telegram_id'];
		$entity->authDate = DateImmutableUtils::fromTimestamp($row['auth_date']);
		$entity->userName = $row['user_name'];
		$entity->userFirstName = $row['user_first_name'];
		$entity->userLastNAme = $row['user_last_name'];
		$entity->userPhotoUrl = Strict::urlImmutable($row['user_photo_url']);
		return $entity;
	}

	public function displayname(): string
	{
		$displayName = $this->userName ? ('@' . $this->userName) : ($this->userFirstName . ' ' . $this->userLastNAme);
		return trim(htmlspecialchars($displayName, ENT_NOQUOTES));
	}
}
