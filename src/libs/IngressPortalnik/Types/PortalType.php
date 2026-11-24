<?php declare(strict_types=1);

namespace App\IngressPortalnik\Types;

use App\Ingress\PortalInterface;
use App\Utils\Ingress;

/**
 * Current state of a portal as returned by IvoPortalnik API (schema "Portal").
 */
final readonly class PortalType implements PortalInterface
{
	public function __construct(
		public string $guid,
		public float $lat,
		public float $lng,
		public ?string $name,
		public ?string $imageUrl,
		public ?string $address,
		public \DateTimeImmutable $firstSeenAt,
		public \DateTimeImmutable $lastSeenAt,
		/** When the current values last changed */
		public \DateTimeImmutable $updatedAt,
	) {
	}

	public static function createFromVariable(\stdClass $variables): self
	{
		return new self(
			guid: $variables->guid,
			lat: (float)$variables->lat,
			lng: (float)$variables->lng,
			name: self::trimOrNull($variables->name ?? null),
			imageUrl: $variables->image_url ?? null,
			address: self::trimOrNull($variables->address ?? null),
			firstSeenAt: new \DateTimeImmutable($variables->first_seen_at),
			lastSeenAt: new \DateTimeImmutable($variables->last_seen_at),
			updatedAt: new \DateTimeImmutable($variables->updated_at),
		);
	}

	private static function trimOrNull(mixed $value): ?string
	{
		if ($value === null) {
			return null;
		}
		$value = trim((string)$value);
		return $value === '' ? null : $value;
	}

	#[\Override]
	public function getGuid(): string
	{
		return $this->guid;
	}

	#[\Override]
	public function getName(): ?string
	{
		return $this->name;
	}

	#[\Override]
	public function getAddress(): ?string
	{
		return $this->address;
	}

	/**
	 * Links are generated locally (not taken from API response), so they are same as with other portal providers.
	 */
	#[\Override]
	public function getIntelLink(): string
	{
		return Ingress::generateIntelPortalLink($this->lat, $this->lng);
	}

	#[\Override]
	public function getPrimeLink(): string
	{
		return (string)Ingress::generatePrimePortalLink($this->guid);
	}

	#[\Override]
	public function getLightshipLink(): string
	{
		return (string)Ingress::generateNianticLightshipLink($this, guid: $this->guid);
	}

	#[\Override]
	public function getImageLink(?int $size = null): ?string
	{
		$result = $this->imageUrl;
		if ($result !== null && $size !== null) {
			$result .= '=s' . $size;
		}
		return $result;
	}

	#[\Override]
	public function getLat(): float
	{
		return $this->lat;
	}

	#[\Override]
	public function getLon(): float
	{
		return $this->lng;
	}

	#[\Override]
	public function getLatLon(string $delimiter = ','): string
	{
		return sprintf('%F%s%F', $this->getLat(), $delimiter, $this->getLon());
	}
}
