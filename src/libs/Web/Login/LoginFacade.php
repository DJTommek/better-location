<?php declare(strict_types=1);

namespace App\Web\Login;

use App\Factory;
use App\Repository\WebLoginEntity;
use App\Repository\WebLoginRepository;
use App\TelegramCustomWrapper\Login;

class LoginFacade
{
	const COOKIE_NAME = 'blb_login';
	const COOKIE_EXPIRES = 1209600; // 14 days

	private $db;
	/** @var WebLoginRepository */
	private $webLoginRepository;

	/** @var bool */
	private $isLogged = false;
	/** @var ?WebLoginEntity */
	private $entity;

	public function __construct()
	{
		$this->db = Factory::Database();
		$this->webLoginRepository = new WebLoginRepository($this->db);
		if ($cookie = $this->getCookie()) {
			if ($this->entity = $this->webLoginRepository->fromHash($cookie)) {
				$this->isLogged = true;
			}
		}
	}

	public function setCookie(string $hash)
	{
		setcookie(self::COOKIE_NAME, $hash, [
				'expires' => time() + self::COOKIE_EXPIRES,
				'domain' => 'better-location.local',
				'secure' => true,
				'httponly' => true,
				'samesite' => 'Strict',
			]
		);
	}

	public function deleteCookie()
	{
		setcookie(self::COOKIE_NAME, '', 1);
	}

	private function getCookie(): ?string
	{
		return $_COOKIE[self::COOKIE_NAME] ?? null;
	}

	public function logout(): void
	{
		$this->deleteCookie();
		$this->webLoginRepository->deleteByHash($this->entity->hash);
	}

	public function saveToDatabase(Login $tgLogin)
	{
		$this->webLoginRepository->saveFromTelegramLogin($tgLogin);
	}

	public function getEntity(): ?WebLoginEntity
	{
		return $this->entity;
	}

	public function isLogged(): bool
	{
		return $this->isLogged;
	}

	public function getDisplayName(): ?string
	{
		if ($this->entity) {
			return $this->entity->displayName();
		}
		return null;
	}
}
