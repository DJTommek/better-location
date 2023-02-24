<?php declare(strict_types=1);

namespace App\Web\Login;

use App\Config;
use App\Database;
use App\Factory;
use App\Repository\WebLoginEntity;
use App\Repository\WebLoginRepository;
use App\TelegramCustomWrapper\Login;
use Nette\Http\UrlImmutable;

class LoginFacade
{
	private const COOKIE_NAME = Config::WEB_COOKIES_PREFIX . 'login';

	private Database $db;
	private WebLoginRepository $webLoginRepository;

	private bool $isLogged = false;
	private ?WebLoginEntity $entity = null;

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

	private function getCookieOptions(\DateTimeInterface $expires): array
	{
		return [
			'domain' => Config::getAppUrl()->getDomain(0),
			'expires' => $expires->getTimestamp(),
			'secure' => true,
			'httponly' => true,
			'path' => '/',
			// @TODO If samesite is set to Strict, cookies are 'delayed' and not set properly on page load. See #118 for more info
			// 'samesite' => 'Strict',
		];
	}

	public function setCookie(string $hash): void
	{
		$expires = (new \DateTime())->add(new \DateInterval(Config::WEB_COOKIES_LOGIN_EXPIRATION));
		$options = $this->getCookieOptions($expires);
		setcookie(self::COOKIE_NAME, $hash, $options);
	}

	public function deleteCookie(): void
	{
		$expires = (new \DateTime())->sub(new \DateInterval('P14D'));
		setcookie(self::COOKIE_NAME, '', $this->getCookieOptions($expires));
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

	public function saveToDatabase(Login $tgLogin): void
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

	public function getTelegramId(): ?int
	{
		if ($this->entity) {
			return $this->entity->userTelegramId;
		}
		return null;
	}

	public function getPhotoUrl(): ?UrlImmutable
	{
		if ($this->entity) {
			return $this->entity->userPhotoUrl;
		}
		return null;
	}
}

