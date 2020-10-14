<?php declare(strict_types=1);

namespace GlympseApi\Types\Properties;

use Utils\StringUtils;

/**
 * @version 2020-10-14
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 * @see https://developer.glympse.com/docs/core/api/reference/objects/data-points#eta-property
 */
class EtaProperty extends Property
{
	public static function createFromVariable(\stdClass $variables): self {
		$class = new self();
		foreach ($variables as $key => $value) {
			$name = StringUtils::camelize($key);
			if (in_array($name, ['eta'])) {
				$value = new \DateInterval(sprintf('PT%dS', $value));
			} else if (in_array($name, ['etaTs'])) {
				$value = new \DateTimeImmutable(sprintf('@%d', $value / 1000));
			}
			$class->{$name} = $value;
		}
		return $class;
	}

	/** @var ?\DateInterval ETA value */
	public $eta = null;
	/** @var ?\DateTimeImmutable Time when ETA was calculated */
	public $etaTs = null;
}
