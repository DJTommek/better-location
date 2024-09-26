<?php declare(strict_types=1);

namespace App\Web\Chat;

/**
 * Object is representing one item in https://choices-js.github.io/Choices/
 */
class ChoiceItem
{
	public int $value;
	public string $label;
	public bool $selected;
}
