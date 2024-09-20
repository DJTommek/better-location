<?php declare(strict_types=1);

namespace App;

use Tracy\Debugger;

class Database
{
	public const PDO_CODE_INTEGRITY_CONSTRAINT_VIOLATION = '23000';

	/** @var string Randomly occuring error on WEDOS webhosting */
	private const PDO_REPREPARED_ERROR = 'SQLSTATE[HY000]: General error: 1615 Prepared statement needs to be re-prepared';

	private const CONNECTION_MAX_TRIES = 3;

	/**
	 * Lazy access - do not use directly, use getLink() instead.
	 * @readonly
	 */
	private \PDO $link;

	public function __construct(
		private readonly string $server,
		private readonly string $schema,
		private readonly string $user,
		#[\SensitiveParameter] private readonly string $pass,
		private readonly string $charset = 'utf8mb4',
	) {
	}

	/**
	 * Connect to the database, if connection wad not initialized, yet.
	 */
	private function getLink(): \PDO
	{
		if (!isset($this->link)) {
			$this->connect();
		}

		return $this->link;
	}

	private function connect(): void
	{
		for ($retry = 0; $retry < self::CONNECTION_MAX_TRIES; $retry++) {
			try {
				$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $this->server, $this->schema, $this->charset);
				$this->link = new \PDO($dsn, $this->user, $this->pass, [
					\PDO::ATTR_PERSISTENT => true,
				]);
				// Fetch each row as array indexed by column name
				$this->link->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
				// Return int and float columns as PHP int and float types
				$this->link->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
				// https://stackoverflow.com/questions/10113562/pdo-mysql-use-pdoattr-emulate-prepares-or-not
				$this->link->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
				// Throw \PDOException in case of error. See https://www.php.net/manual/en/class.pdoexception.php
				$this->link->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				// Fix if database server don't have enabled STRICT_ALL_TABLES. See https://stackoverflow.com/questions/27880035/what-causes-mysql-not-to-enforce-not-null-constraint
				$this->link->query('SET SESSION SQL_MODE=STRICT_ALL_TABLES');
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

	public function beginTransaction(): bool
	{
		return $this->getLink()->beginTransaction();
	}

	public function commit(): bool
	{
		return $this->getLink()->commit();
	}

	public function rollback(): bool
	{
		return $this->getLink()->rollBack();
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
		$sql = $this->getLink()->prepare($query);
		$sql->execute($params);
		return $sql;
	}
}
