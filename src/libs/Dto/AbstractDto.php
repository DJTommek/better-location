<?php declare(strict_types=1);

namespace App\Dto;

#[\AllowDynamicProperties]
abstract class AbstractDto
{
	public final function __construct() { }

	/**
	 * @param \stdClass|array<mixed> $raw
	 */
	public static function cast(\stdClass|array $raw): static
	{
		$result = new static();
		foreach ((array)$raw as $name => $value) {
			$result->set($name, $value);
		}
		return $result;
	}

	/**
	 * @param array<mixed> $addressComponents
	 * @return array<self>
	 */
	public static function fromArray(array $addressComponents): array
	{
		$result = [];
		foreach ($addressComponents as $addressComponent) {
			$result[] = self::cast($addressComponent);
		}
		return $result;
	}

	/**
	 * You may overwrite this with custom implementation
	 */
	public function set(string $name, mixed $value): void
	{
		$this->{$name} = $value;
	}

	public final function __set(string $name, mixed $value): void
	{
		$this->set($name, $value);
	}
}
