<?php declare(strict_types=1);

namespace App;

use Tracy\Debugger;

class Database
{
	/** @var string Randomly occuring error on WEDOS webhosting */
	private const PDO_REPREPARED_ERROR = 'SQLSTATE[HY000]: General error: 1615 Prepared statement needs to be re-prepared';

	/** @var \PDO */
	private $db;

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

	public function __construct($db_server, $db_schema, $db_user, $db_pass, $db_charset = 'utf8mb4')
	{
		$dsn = 'mysql:host=' . $db_server . ';dbname=' . $db_schema . ';charset=' . $db_charset;
		$this->db = new \PDO($dsn, $db_user, $db_pass);
		$this->db->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
		$this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		// Fix if database server don't have enabled STRICT_ALL_TABLES. See https://stackoverflow.com/questions/27880035/what-causes-mysql-not-to-enforce-not-null-constraint
		$this->db->query('SET SESSION SQL_MODE=STRICT_ALL_TABLES');
	}

	public function getLink(): \PDO
	{
		return $this->db;
	}

	/**
	 * Shortcut for prepared statement
	 *
	 * @param mixed ...$params
	 * @return bool|\PDOStatement
	 */
	public function query(string $query, ...$params)
	{
		try {
			return $this->queryReal($query, $params);
		} catch (\PDOException $exception) {
			if ($exception->getMessage() === self::PDO_REPREPARED_ERROR) {
				Debugger::log('Catched PDO re-prepared error, trying again...', Debugger::WARNING);
				Debugger::log($exception, Debugger::WARNING);
				return $this->queryReal($query, $params);
			} else {
				throw $exception;
			}
		}
	}

	private function queryReal(string $query, $params)
	{
		$sql = $this->db->prepare($query);
		$sql->setFetchMode(\PDO::FETCH_ASSOC);
		$sql->execute($params);
		return $sql;
	}


	/**
	 * Array shortcut for prepared statement
	 *
	 * @return bool|\PDOStatement
	 */
	public function queryArray(string $query, array $params)
	{
		$sql = $this->db->prepare($query);
		$sql->setFetchMode(\PDO::FETCH_ASSOC);
		$sql->execute($params);
		return $sql;
	}
}
