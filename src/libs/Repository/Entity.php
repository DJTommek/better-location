<?php declare(strict_types=1);

namespace App\Repository;

abstract class Entity
{
	abstract static function fromRow(array $row);

	public static function fromRows(array $rows): array {
		$result = [];
		foreach($rows as $row) {
			$result[] = static::fromRow($row);
		}
		return $result;
	}
}
