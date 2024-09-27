<?php declare(strict_types=1);

namespace App\Repository;

use App\TelegramCustomWrapper;
use Nette\Http\UrlImmutable;

class WebLoginRepository extends Repository
{
	public function findByHash(string $hash): ?WebLoginEntity
	{
		$sql = 'SELECT * FROM better_location_web_login WHERE hash = ?';
		$row = $this->db->query($sql, $hash)->fetch();
		return $row ? WebLoginEntity::fromRow($row) : null;
	}

	public function save(
		string $hash,
		int $userTelegramId,
		\DateTimeImmutable $authDate,
		string $userFirstName,
		?string $userLastName = null,
		?string $userName = null,
		?UrlImmutable $userPhotoUrl = null
	): void
	{
		$sql = 'INSERT IGNORE INTO better_location_web_login (hash, user_telegram_id, auth_date, user_first_name, user_last_name, user_name, user_photo_url) VALUES (?, ?, ?, ?, ?, ?, ?)';
		$this->db->query($sql, $hash, $userTelegramId, $authDate->getTimestamp(), $userFirstName, $userLastName, $userName, $userPhotoUrl);
	}

	public function saveFromTelegramLogin(TelegramCustomWrapper\Login $tgLogin): void
	{
		$this->save(
			$tgLogin->hash(),
			$tgLogin->userTelegramId(),
			$tgLogin->authDate(),
			$tgLogin->userFirstName(),
			$tgLogin->userLastName(),
			$tgLogin->userLoginName(),
			$tgLogin->userPhotoUrl()
		);
	}

	public function deleteByHash(string $hash): void
	{
		$sql = 'DELETE FROM better_location_web_login WHERE hash = ?';
		$this->db->query($sql, $hash);
	}
}
