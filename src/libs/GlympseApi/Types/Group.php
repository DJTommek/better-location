<?php declare(strict_types=1);

namespace GlympseApi\Types;

use Utils\StringUtils;

/**
 * @version 2020-10-14
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 * @see https://developer.glympse.com/docs/core/api/reference/groups/groups
 */
class Group extends Type
{
	public static function createFromVariable(\stdClass $variables): self {
		$class = new self();
		foreach ($variables as $key => $value) {
			$propertyName = StringUtils::camelize($key);
			if ($key === 'members') {
				$value = GroupMember::createMultiple($value);
			}
			$class->{$propertyName} = $value;
		}
		return $class;
	}

	/** @var string */
	public $type = 'group';
	/** @var ?string The ID representing this group */
	public $id = null;
	/** @var ?int The timestamp to use for calls to /events (timestamp o 0) */
	public $events = null;
	/** @var ?GroupMember[] */
	public $members = null;
	/** @var ?bool */
	public $public = null;
	/** @var ?string The name of this group (same as group_name in request) */
	public $name = null;
	/** @var ?string The branding identifier, if set, for the group */
	public $branding = null;
}
