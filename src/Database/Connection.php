<?php

namespace Base\Database;

use \Base\Profile as Profile;

class DatabaseException extends \Exception{};

class Connection
{
	/**
	 * The default quote that should be used
	 * @var string 
	 */
	protected $quote = '`';
	
	
	/**
	 * The actual connection
	 * @var \PDO 
	 */
	protected $connection = null;
	
	
	/**
	 * Connection params
	 * @var array 
	 */
	protected $params = [
		'dsn' => '',
		'username' => '',
		'password' => '',
		'options' => [],
		'profile' => false,
	];
	
	
	/**
	 * Profile objecy
	 * @var \Base\Profile 
	 */
	protected $profile = null;


	/**
	 * Construct
	 * @param array $params
	 * @param \Base\Profile $profile
	 */
	public function __construct(array $params = [], Profile $profile = null)
	{
		$this->params = array_merge($this->params, $params);
		$this->profile = $profile;
	}


	/**
	 * Connect to DB and store connection
	 */
	public function connect()
	{
		if ($this->connection === null) {
			$this->connection = new \PDO(
				$this->params['dsn'], $this->params['username'], $this->params['password'], $this->params['options']
			);
		}
	}


	/**
	 * Get the quote character
	 * Used by Query
	 * @return string
	 */
	public function quote()
	{
		return $this->quote;
	}


	/**
	 * Run a query on the connection
	 * @param string $query
	 * @param array $params
	 * @param string $type select / insert update / delete
	 * @param string $id the name of the id column for lastInsertId
	 * @return mixed resultset, rowcount of last inserted id
	 * @throws \Base\DatabaseException
	 */
	public function execute($query, array $params, $type = null, $id = 'id')
	{
		$this->connect();
		$statement = $this->connection->prepare($query);

		if ($this->params['profile']) {
			$token = $this->profile->start('database', [$query, $params]);
		}

		$result = $statement->execute($params);

		if ($this->params['profile']) {
			$this->profile->end($token);
		}

		if ($result == false) {
			$error = $statement->errorInfo();
			throw new DatabaseException($error[2]);
		}

		if ($type === 'select' || ($type === 'query' && strtoupper(substr($query, 0, 6)) === 'SELECT')) {
			return $statement;
		} elseif ($type === 'insert' || ($type === 'query' && strtoupper(substr($query, 0, 6)) === 'INSERT')) {
			return $this->connection->lastInsertId($id);
		} else {
			return $statement->rowCount();
		}
	}
}
