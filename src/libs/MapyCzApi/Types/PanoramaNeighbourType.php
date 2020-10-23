<?php declare(strict_types=1);

namespace MapyCzApi\Types;

/**
 * @method static self cast(\stdClass $stdClass)
 *
 * @version 2020-10-23
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 */
class PanoramaNeighbourType extends Type
{
	/**
	 * @param \stdClass $response
	 * @return self[]
	 */
	public static function createFromResponse(\stdClass $response): array
	{
		$neighbours = [];
		foreach ($response->result->neighbours as $neighbourRaw) {
			$neighbour = new PanoramaNeighbourType();
			foreach ($neighbourRaw as $key => $value) {
				if (in_array($key, ['far', 'near'])) {
					if (empty((array)$value) === false) {
						$value = PanoramaType::cast($value);
					} else {
						$value = null;  // Don't create empty PanoramaType object
					}
				}
				$neighbour->{$key} = $value;
			}
			$neighbours[] = $neighbour;
		}
		return $neighbours;
	}

	/** @var float */
	public $angle;
	/** @var ?PanoramaType */
	public $near;
	/** @var ?PanoramaType */
	public $far;

	/** @throws \Exception if near and far properties are missing */
	public function getLat(): float
	{
		if ($this->near) {
			return $this->near->getLat();
		} else if ($this->far) {
			return $this->far->getLat();
		} else {
			throw new \Exception('Can\'t get latitude  - no near or far panorama is available');
		}
	}

	/** @throws \Exception if near and far properties are missing */
	public function getLon(): float
	{
		if ($this->near) {
			return $this->near->getLon();
		} else if ($this->far) {
			return $this->far->getLon();
		} else {
			throw new \Exception('Can\'t get longitude - no near or far panorama is available');
		}
	}

	public function __set($name, $value)
	{
		throw new \OutOfBoundsException(sprintf('Property "%s$%s" is not predefined.', static::class, $name));
	}
}
