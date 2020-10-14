<?php declare(strict_types=1);

namespace GlympseApi\Types;

use Utils\StringUtils;

/**
 * @version 2020-10-14
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 * @see https://developer.glympse.com/docs/core/api/reference/groups/groups
 */
class GroupMember extends Type
{
	/** @return self[] */
	public static function createMultiple(array $members): array {
		$result = [];
		foreach ($members as $rawMember) {
			$result[] = self::createFromVariable($rawMember);
		}
		return $result;
	}

	public static function createFromVariable(\stdClass $variables): self {
		$class = new self();
		foreach ($variables as $key => $value) {
			$propertyName = StringUtils::camelize($key);
			$class->{$propertyName} = $value;
		}
		return $class;
	}

	/** @var ?string The given user's unique ID. This user will only be returned if the associated invite code is less than 48 hours hold. */
	public $id = null;
	/** @var ?string The given user's unique invite code */
	public $invite = null;
}
