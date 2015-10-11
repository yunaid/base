<?php

namespace Base;

class Database
{
	protected $queryFactory = null;
	protected $rawFactory = null;

	public function __construct($queryFactory, $rawFactory)
	{
		$this->queryFactory = $queryFactory;
		$this->rawFactory = $rawFactory;
	}

	public function query($query, $params = [])
	{
		return $this->queryFactory->__invoke('query')->query($query, $params);
	}


	public function insert($table)
	{
		return $this->queryFactory->__invoke('insert')->table($table);
	}


	public function select()
	{
		return call_user_func_array([$this->queryFactory->__invoke('select'), 'select'], func_get_args());
	}


	public function update($table)
	{
		return $this->queryFactory->__invoke('update')->table($table);
	}


	public function delete($table)
	{
		return $this->queryFactory->__invoke('delete')->table($table);
	}


	public function raw($expression)
	{
		return $this->rawFactory->__invoke($expression);
	}

}