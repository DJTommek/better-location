<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\Config;
use App\Utils\DateImmutableUtils;
use App\Utils\Strict;
use Nette\Http\Url;

class Login
{
	const REQUIRED_INPUTS = ['id', 'first_name', 'auth_date', 'hash'];
	const ALLOWED_INPUTS = ['last_name', 'username', 'photo_url'];
	const MAX_OLD = 86400;

	/** @var array */
	private $raw;

	/** @var ?bool */
	private $verified = null;

	/** @var int */
	private $id;
	/** @var string */
	private $firstName;
	/** @var \DateTimeImmutable */
	private $authDate;
	/** @var string */
	private $hash;
	/** @var string */
	private $lastName;
	/** @var string */
	private $username;
	/** @var string */
	private $photoUrl;

	public function __construct(array $raw)
	{
		$this->raw = $raw;
		$this->fillFromRaw();
	}

	/** Check if provided array has all required keys for verification */
	public static function hasRequiredGetParams($getParams): bool
	{
		$intersected = array_intersect(array_keys($getParams), \App\TelegramCustomWrapper\Login::REQUIRED_INPUTS);
		return count($intersected) === count(self::REQUIRED_INPUTS);
	}

	/**
	 * Verify authorization data according provided hash
	 *
	 * @link https://core.telegram.org/widgets/login#checking-authorization
	 * @link https://core.telegram.org/bots/api#loginurl
	 * @author https://gist.github.com/anonymous/6516521b1fb3b464534fbc30ea3573c2#file-check_authorization-php
	 */
	public function isVerified(): bool
	{
		if ($this->verified === null) {
			$dataCheckArr = [];
			foreach ($this->getRawFiltered() as $key => $value) {
				$dataCheckArr[] = $key . '=' . $value;
			}
			sort($dataCheckArr);
			$realHash = hash_hmac('sha256', implode("\n", $dataCheckArr), $this->secretKey());
			$this->verified = strcmp($realHash, $this->hash) === 0;
		}
		return $this->verified;
	}

	public function isTooOld(): bool
	{
		return time() - $this->authDate->getTimestamp() > self::MAX_OLD;
	}

	/** Filter raw GET values to get only these, which are necessary to verification */
	private function getRawFiltered(): array
	{
		$allowedKeys = array_flip(array_merge(self::REQUIRED_INPUTS, self::ALLOWED_INPUTS));
		unset($allowedKeys['hash']);
		return array_intersect_key($this->raw, $allowedKeys);
	}

	private function secretKey(): string
	{
		return hash('sha256', Config::TELEGRAM_BOT_TOKEN, true);
	}

	private function fillFromRaw(): void
	{
		$this->id = Strict::intval($this->raw['id']);
		$this->firstName = $this->raw['first_name'];
		$this->authDate = DateImmutableUtils::fromTimestamp(Strict::intval($this->raw['auth_date']));
		$this->hash = $this->raw['hash'];
		$this->lastName = $this->raw['last_name'] ?? null;
		$this->username = $this->raw['username'] ?? null;
		$this->photoUrl = isset($this->raw['photo_url']) ? new Url($this->raw['photo_url']) : null;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getFirstName(): string
	{
		return $this->firstName;
	}

	public function getAuthDate(): ?\DateTimeImmutable
	{
		return $this->authDate;
	}

	public function getLastName(): ?string
	{
		return $this->lastName;
	}

	public function getUsername(): ?string
	{
		return $this->username;
	}

	public function getPhotoUrl(): ?string
	{
		return $this->photoUrl;
	}

	public function displayname(): string
	{
		$displayName = $this->username ? ('@' . $this->username) : ($this->firstName . ' ' . $this->lastName);
		return trim(htmlspecialchars($displayName, ENT_NOQUOTES));
	}

}
