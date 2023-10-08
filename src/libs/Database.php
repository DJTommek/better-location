<?php declare(strict_types=1);

namespace App;

use Tracy\Debugger;

class Database
{
	/** @var string Randomly occuring error on WEDOS webhosting */
	private const PDO_REPREPARED_ERROR = 'SQLSTATE[HY000]: General error: 1615 Prepared statement needs to be re-prepared';

	private const CONNECTION_MAX_TRIES = 3;

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

	private \PDO $db;

	public function __construct(string $server, string $schema, string $user, string $pass, string $charset = 'utf8mb4')
	{
		for ($retry = 0; $retry < self::CONNECTION_MAX_TRIES; $retry++) {
			try {
				$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $server, $schema, $charset);
				$this->db = new \PDO($dsn, $user, $pass, [
					\PDO::ATTR_PERSISTENT => true,
				]);
				// Fetch each row as array indexed by column name
				$this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
				// Return int and float columns as PHP int and float types
				$this->db->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
				// https://stackoverflow.com/questions/10113562/pdo-mysql-use-pdoattr-emulate-prepares-or-not
				$this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
				// Throw \PDOException in case of error. See https://www.php.net/manual/en/class.pdoexception.php
				$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				// Fix if database server don't have enabled STRICT_ALL_TABLES. See https://stackoverflow.com/questions/27880035/what-causes-mysql-not-to-enforce-not-null-constraint
				$this->db->query('SET SESSION SQL_MODE=STRICT_ALL_TABLES');
				return; // successfullly connected
			} catch (\PDOException $exception) {
				if ($exception->getMessage() === 'SQLSTATE[HY000] [2002] Cannot assign requested address') {
					Debugger::log(sprintf('Unable to connect to database "%s" (try %d/%d).', $exception->getMessage(), ($retry + 1), self::CONNECTION_MAX_TRIES), Debugger::WARNING);
					sleep(1);
					continue;
				}

				throw $exception;
			}
		}

		assert(isset($exception));
		throw $exception;
	}

	public function getLink(): \PDO
	{
		return $this->db;
	}

	/**
	 * Shortcut for prepared statement
	 *
	 * @param string $query SQL query
	 * @param mixed ...$params Optional parameters for fill prepared statements
	 */
	public function query(string $query, mixed ...$params): \PDOStatement
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

	/**
	 * @param array<mixed> $params
	 */
	private function queryReal(string $query, array $params): \PDOStatement
	{
		$sql = $this->db->prepare($query);
		$sql->execute($params);
		return $sql;
	}
}
