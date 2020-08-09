<?php

class Database
{
	/**
	 * @var PDO to database
	 */
	private $db;

	public function __construct($db_server, $db_schema, $db_user, $db_pass, $db_charset = 'utf8mb4') {
		$dsn = 'mysql:host=' . $db_server . ';dbname=' . $db_schema . ';charset=' . $db_charset;
		$this->db = new PDO($dsn, $db_user, $db_pass);
		$this->db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
		$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function getLink(): PDO {
		return $this->db;
	}

	/**
	 * Shortcut for prepared statement
	 *
	 * @param string $query
	 * @param mixed ...$params
	 * @return bool|PDOStatement
	 */
	public function query(string $query, ...$params) {
		$sql = $this->db->prepare($query);
		$sql->setFetchMode(PDO::FETCH_ASSOC);
		$sql->execute($params);
		return $sql;
	}

	/**
	 * Array shortcut for prepared statement
	 *
	 * @param string $query
	 * @param $params
	 * @return bool|PDOStatement
	 */
	public function queryArray(string $query, array $params) {
		$sql = $this->db->prepare($query);
		$sql->setFetchMode(PDO::FETCH_ASSOC);
		$sql->execute($params);
		return $sql;
	}
}
