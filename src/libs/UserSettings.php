<?php declare(strict_types=1);

namespace App;

use App\Utils\Strict;

class UserSettings
{
	/** @var bool */
	private $preview = false;
	/** @var bool */
	private $sendNativeLocation = false;

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function set(string $name, $value)
	{
		switch ($name) {
			case 'settings_preview';
			case 'preview';
				return $this->setPreview(Strict::boolval($value));
			case 'settings_send_native_location';
			case 'send_native_location';
				return $this->setSendNativeLocation(Strict::boolval($value));
			default:
				throw new \InvalidArgumentException(sprintf('Unknown settings name "%s".', $name));
		}
	}

	/** @return mixed */
	public function get(string $name)
	{
		return $this->$name;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function __set(string $name, $value)
	{
		return $this->set($name, $value);
	}

	public function __get($name)
	{
		return $this->get($name);
	}

	public function setPreview(bool $value): bool
	{
		return $this->preview = $value;
	}

	public function getPreview(): bool
	{
		return $this->preview;
	}

	public function setSendNativeLocation(bool $value): bool
	{
		return $this->sendNativeLocation = $value;
	}

	public function getSendNativeLocation(): bool
	{
		return $this->sendNativeLocation;
	}
}
