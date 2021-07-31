<?php declare(strict_types=1);

namespace App\Repository;

use App\Database;

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

	/** @var Database */
	public $db;

	public function __construct(Database $database)
	{
		$this->db = $database;
	}
}
