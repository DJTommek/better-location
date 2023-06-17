<?php declare(strict_types=1);

namespace App\Google\Geocoding;

class GeocodeResponse
{
	public \stdClass $plus_code;
	/**
	 * @var array<mixed>
	 */
	public array $results;
	public string $status;

	/**
	 * @param \stdClass|array<mixed> $raw
	 */
	public static function cast(\stdClass|array $raw): self
	{
		$result = new self();
		foreach ((array)$raw as $name => $value) {
			$result->{$name} = $value;
		}
		return $result;
	}

	public function getAddress(): ?string
	{
		foreach ($this->results as $result) {
			if (isset($result->formatted_address)) {
				return $result->formatted_address;
			}
		}
		return null;
	}

	public function __toString(): string
	{
		return $this->getAddress();
	}
}
