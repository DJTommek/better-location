<?php declare(strict_types=1);

namespace App\IngressLanchedRu\Types;

use App\IngressLanchedRu\Client;
use Tracy\Debugger;

class PortalType
{
	/** @var string */
	public $guid;
	/** @var float */
	public $lat;
	/** @var float */
	public $lng;
	/** @var string */
	public $name;
	/** @var ?string */
	public $image;
	/** @var ?string */
	public $address;

	public static function createFromVariable(\stdClass $variables): self
	{
		$class = new self();
		foreach ($variables as $key => $value) {
			if (in_array($key, ['name', 'address'], true)) {
				$value = trim($value);
			}
			$class->{$key} = $value;
		}
		return $class;
	}

	/** @param mixed $value */
	public function __set(string $name, $value): void
	{
		Debugger::log(sprintf('Property "%s$%s" of type "%s" is not predefined.', static::class, $name, gettype($value)), Debugger::WARNING);
	}

	public function getIntelLink(): string
	{
		return Client::LINK_INGRESS_INTEL . sprintf('/?ll=%1$f,%2$f&pll=%1$f,%2$f', $this->lat, $this->lng);
	}
}
