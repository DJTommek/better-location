<?php declare(strict_types=1);

namespace App\Repository;

use App\Database;

/**
 * Methods that
 * - must always return instance must be named with prefix `getBy`, eg. `getById()`, `getByTelegramId()`
 * - might return instance or null must be named with prefix `findBy`, eg. `findByTelegramId()`
 * - is returning array of instances (including empty array) also must be named with prefix `findBy`
 */
abstract class Repository
{
	public const TRUE = 1;
	public const FALSE = 0;

	public const DISABLED = 0;
	public const ENABLED = 1;
	public const DELETED = 2;

	public const ORDER_ASC = 'ASC';
	public const ORDER_DESC = 'DESC';
	public const ORDERS = [
		self::ORDER_ASC,
		self::ORDER_DESC,
	];

	public const DATETIME_FORMAT = 'Y-m-d H:i:s';

	public readonly Database $db;

	public function __construct(Database $database)
	{
		$this->db = $database;
	}

	/**
	 * Generate question marks for use as SQL ... IN (?, ?, ?, ...)
	 *
	 * @param array $values
	 * @return string Question marks joined with comma
	 */
	protected static function inHelper(array $values): string
	{
		$questionMarks = array_fill(0, count($values), '?');
		return implode(',', $questionMarks);
	}
}
