<?php

namespace Base;

class Database
{
	/**
	 * Factory for new \Base\Database\Query objects
	 * @var \Closure 
	 */
	protected $queryFactory = null;
	
	/**
	 * Factory for new \Base\Database\Raw objects
	 * @var \Closure 
	 */
	protected $rawFactory = null;


	/**
	 * Constructor
	 * @param \Closure $queryFactory
	 * @param \Closure $rawFactory
	 */
	public function __construct(\Closure $queryFactory, \Closure $rawFactory)
	{
		$this->queryFactory = $queryFactory;
		$this->rawFactory = $rawFactory;
	}

	
	/**
	 * Create a custom query with ? placeholders and params
	 * @param string $query
	 * @param array $params
	 * @return \Base\Database\Query
	 */
	public function query($query, array $params = [])
	{
		return $this->queryFactory->__invoke('query')->query($query, $params);
	}


	/**
	 * Create an insert query
	 * @param string $table
	 * @return \Base\Database\Query
	 */
	public function insert($table)
	{
		return $this->queryFactory->__invoke('insert')->table($table);
	}


	/**
	 * Create a select query, pass in select fields as arguments
	 * Pass ['field','alias'] to select a field as alias
	 * @return \Base\Database\Query
	 */
	public function select()
	{
		return call_user_func_array([$this->queryFactory->__invoke('select'), 'select'], func_get_args());
	}


	/**
	 * Create an update query
	 * @param type $table
	 * @return \Base\Database\Query
	 */
	public function update($table)
	{
		return $this->queryFactory->__invoke('update')->table($table);
	}

	
	/**
	 * Create a delete query
	 * @param string $table
	 * @return \Base\Database\Query
	 */
	public function delete($table)
	{
		return $this->queryFactory->__invoke('delete')->table($table);
	}


	/**
	 * Create a Raw object that wont be escaped
	 * @param string $expression
	 * @return \Base\Database\Raw
	 */
	public function raw($expression)
	{
		return $this->rawFactory->__invoke($expression);
	}

}