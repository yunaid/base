<?php

namespace Base\Database;

use \Base\Database\Connection as Connection;

class Query
{
	// the connection
	protected $connection = null;
	
	/**
	 * The quote string to use
	 * @var string 
	 */
	protected $quote = null;
	
	/**
	 * Query type, can be select, update, craete, delete, query
	 * @var string 
	 */
	protected $type = null;
	
	/**
	 * A raw query
	 * @var string 
	 */
	protected $query = '';
	
	/**
	 * The params the go with the raw params
	 * @var array 
	 */
	protected $params = [];
	
	/**
	 * Table name
	 * @var string 
	 */
	protected $table = '';
	
	/**
	 * Fields to select
	 * @var array 
	 */
	protected $select = [];
	
	/**
	 * Distrinct fields
	 * @var array 
	 */
	protected $distinct = [];
	
	/**
	 * Values to update
	 * @var array 
	 */
	protected $values = [];
	
	/**
	 * Joins
	 * @var array 
	 */
	protected $joins = [];
	
	/**
	 * Where clauses
	 * @var array 
	 */
	protected $where = [];
	
	/**
	 * Group statements
	 * @var array 
	 */
	protected $group = [];
	
	/**
	 * Having clauses
	 * @var array 
	 */
	protected $having = [];
	
	/**
	 * Limit
	 * @var int|boolean 
	 */
	protected $limit = false;
	
	/**
	 * Offset
	 * @var int|boolean 
	 */
	protected $offset = false;
	
	/**
	 * Order statements
	 * @var array 
	 */
	protected $order = [];
	
	/**
	 * union statements
	 * @var array 
	 */
	protected $union = [];
	
	/**
	 * Union all statments
	 * @var array 
	 */
	protected $unionAll = [];
	
	/**
	 * List of allowed operators
	 * @var array 
	 */
	protected $operators = [
		'=', 
		'<', 
		'>', 
		'<=', 
		'>=', 
		'<>', 
		'!=', 
		'&',
		'|', 
		'^', 
		'<<', 
		'>>',
		'LIKE', 
		'NOT LIKE', 
		'BETWEEN', 
		'NOT BETWEEN', 
		'ILIKE', 
		'RLIKE',
		'REXEXP', 
		'NOT REGEXP', 
		'IN', 
		'ISNULL', 
		'SOUNDS LIKE',
	];
	
	/**
	 * Var that holds the result after a call to execute
	 * @var int|\PDOStatement 
	 */
	protected $result = null;

	
	/**
	 * Create a new query and get quote style from connection
	 * @param \Base\Database\Connection $connection
	 * @param string $type
	 */
	public function __construct( Connection $connection, $type = null)
	{
		$this->connection = $connection;
		$this->type = $type;
		$this->quote = $connection->quote();
	}

	
	/**
	 * Set a query with params
	 * @param string $query
	 * @param array $params
	 * @return string
	 */
	public function query($query, array $params = [])
	{
		$this->query = $query;
		$this->params = $params;
		return $query;
	}


	/**
	 * Add values to select.
	 * Pass field directly as arguments
	 * Pass ['field1', 'alias'], 'field2' to select as alias
	 * @return \Base\Database\Query
	 */
	public function select()
	{
		$this->select = array_merge($this->select, func_get_args());
		return $this;
	}

	/**
	 * Set table name
	 * @param string $table
	 * @return \Base\Database\Query
	 */
	public function table($table)
	{
		$this->table = $table;
		return $this;
	}

	
	/**
	 * Add distinct columns
	 * @param string|array $distinct
	 * @return \Base\Database\Query
	 */
	public function distinct($columnOrColumns)
	{
		$this->distinct = array_merge($this->distinct, (array) $columnOrColumns);
		return $this;
	}

	
	/**
	 * Set table name
	 * @param string $table
	 * @return \Base\Database\Query
	 */
	public function from($table)
	{
		$this->table = $table;
		return $this;
	}

	
	/**
	 * Set values for update (alias for :: values)
	 * @param string|array $columnOrValues
	 * @param string|int $value
	 * @return \Base\Database\Query
	 */
	public function set($columnOrValues, $value = null)
	{
		$this->values($columnOrValues, $value);
		return $this;
	}


	/**
	 * Set values
	 * @param string|array $columnOrValues
	 * @param string $value
	 * @return \Base\Database\Query
	 */
	public function values($columnOrValues, $value = null)
	{
		if (is_array($columnOrValues)) {
			$this->values = array_merge($this->values, $columnOrValues);
		} else {
			$this->values[$columnOrValues] = $value;
		}
		return $this;
	}


	/**
	 * Add a join
	 * @param string $table
	 * @param string $type
	 * @return \Base\Database\Query
	 * @throws QueryException
	 */
	public function join($table, $type = 'INNER')
	{
		if ($this->type !== 'select') {
			throw new QueryException('JOIN can only be used on a SELECT query');
		}
		$this->joins[] = [
			'table' => $table,
			'on' => [],
			'where' => [],
			'type' => $type
		];
		return $this;
	}


	/**
	 * Add on clause for active join
	 * @param string|\Closure $firstOrCallable
	 * @param string $operatorOrSecond
	 * @param string $second
	 * @return \Base\Database\Query
	 * @throws QueryException
	 */
	public function on($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		if (count($this->joins) == 0) {
			throw new QueryException('Called ::on without calling join first');
		}
		$this->condition('AND', $this->joins[count($this->joins) - 1]['on'], $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}

	
	/**
	 * Add an OR ON cluase
	 * @param string|\Closure $firstOrCallable
	 * @param string $operatorOrSecond
	 * @param string $second
	 * @return \Base\Database\Query
	 * @throws QueryException
	 */
	public function orOn($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		if (count($this->joins) == 0) {
			throw new QueryException('Called ::or_on without calling join first');
		}
		$this->condition('OR', $this->joins[count($this->joins) - 1]['on'], $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	/**
	 * Add an ON clause not directly related to column matching
	 * @param string|\Closure $firstOrCallable
	 * @param string $operatorOrSecond
	 * @param string $second
	 * @return \Base\Database\Query
	 * @throws QueryException
	 */
	public function onWhere($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		if (count($this->joins) == 0) {
			throw new QueryException('Called ::onWhere without calling join first');
		}
		$this->condition('AND', $this->joins[count($this->joins) - 1]['where'], $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}

	
	/**
	 * Add an OR ON clause not directly related to column matching
	 * @param string|\Closure $firstOrCallable
	 * @param string $operatorOrSecond
	 * @param string $second
	 * @return \Base\Database\Query
	 * @throws QueryException
	 */
	public function orOnWhere($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		if (count($this->joins) == 0) {
			throw new QueryException('Called ::orOnWhere without calling join first');
		}
		$this->condition('OR', $this->joins[count($this->joins) - 1]['where'], $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	/**
	 * Add a WHERE clause. When used more than once, will use AND as logic
	 * @param string|\Closure $firstOrCallable
	 * @param string $operatorOrSecond
	 * @param string $second
	 * @return \Base\Database\Query
	 * @throws QueryException
	 */
	public function where($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		$this->condition('AND', $this->where, $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}

	
	/**
	 * Add an OR clause.  When used more than once, will use OR as logic
	 * @param string|\Closure $firstOrCallable
	 * @param string $operatorOrSecond
	 * @param string $second
	 * @return \Base\Database\Query
	 * @throws QueryException
	 */
	public function orWhere($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		$this->condition('OR', $this->where, $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	/**
	 * Add a GROUP BY statement
	 * @param string|array $group
	 * @return \Base\Database\Query
	 */
	public function group($group)
	{
		if (is_array($group)) {
			$this->group = array_merge($this->group, $group);
		} else {
			$this->group[] = $group;
		}
		return $this;
	}


	/**
	 * Add a HAVING clause. multiple HAVING clauses will be bomined with AND
	 * @param string|\Closure $firstOrCallable
	 * @param string $operatorOrSecond
	 * @param string $second
	 * @return \Base\Database\Query
	 */
	public function having($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		$this->condition('AND', $this->having, $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}

	
	/**
	 * Add a HAVING clause. multiple HAVING clauses will be bomined with OR
	 * @param string|\Closure $firstOrCallable
	 * @param string $operatorOrSecond
	 * @param string $second
	 * @return \Base\Database\Query
	 */
	public function orHaving($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		$this->condition('OR', $this->having, $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}

	
	/**
	 * Add a LIMIT statement
	 * @param int $limit
	 * @return \Base\Database\Query
	 */
	public function limit($limit)
	{
		$this->limit = $limit;
		return $this;
	}


	/**
	 * Add an OFFSET statement
	 * @param int $offset
	 * @return \Base\Database\Query
	 */
	public function offset($offset)
	{
		$this->offset = $offset;
		return $this;
	}


	/**
	 * Add ORDER BY statements
	 * @param string|array $columnOrOrders
	 * @param string $direction
	 * @return \Base\Database\Query
	 */
	public function order($columnOrOrders, $direction = 'ASC')
	{
		if (is_array($columnOrOrders)) {
			$this->order = array_merge($this->order, $columnOrOrders);
		} else {
			$this->order[$columnOrOrders] = $direction;
		}
		return $this;
	}


	/**
	 * Add a UNION statement
	 * @param \Base\Database\Query $query
	 * @return \Base\Database\Query
	 */
	public function union($query)
	{
		$this->union [] = $query;
		return $this;
	}


	/**
	 * Add a UNION ALL statement
	 * @param \Base\Database\Query $query
	 * @return \Base\Database\Query
	 */
	public function unionAll($query)
	{
		$this->unionAll [] = $query;
		return $this;
	}


	/**
	 * Add a condition
	 * @param string $logic
	 * @param array $stack
	 * @param string|\Closure $firstOrCallable
	 * @param string $operatorOrSecond
	 * @param string $second
	 */
	protected function condition($logic, & $stack, $firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		if (is_object($firstOrCallable) && is_callable($firstOrCallable, '__invoke')) {
			$stack[] = [
				'logic' => $logic,
				'type' => 'group_open',
			];

			$firstOrCallable($this);

			$stack[] = [
				'type' => 'group_close'
			];
		} else {
			if (!in_array(strtoupper($operatorOrSecond), $this->operators)) {
				$operator = '=';
				$second = $operatorOrSecond;
			} else {
				$operator = strtoupper($operatorOrSecond);
			}
			$stack[] = [
				'logic' => $logic,
				'type' => null,
				'first' => $firstOrCallable,
				'operator' => $operator,
				'second' => $second
			];
		}
	}


	/**
	 * Compile the query
	 * Return an array with 
	 * - the query as a string with placeholders as first element
	 * - The parameters as array in the second argument
	 * @return array
	 */
	public function compile()
	{
		switch ($this->type) {
			case 'query':
				return [$this->query, $this->params];
			case 'insert':
				return $this->compileInsert();
			case 'select':
				return $this->compileSelect();
			case 'update':
				return $this->compileUpdate();
			case 'delete':
				return $this->compileDelete();
		}
	}

	
	/**
	 * Comile an insert query
	 * @return array
	 */
	protected function compileInsert()
	{
		$params = [];
		$query = 'INSERT INTO ';
		$query .= $this->quoteTable($this->table) . ' (';

		$separator = '';
		foreach (array_keys($this->values) as $identifier) {
			$query .= $separator . $this->quoteIdentifier($identifier);
			$separator = ', ';
		}

		$query .= ') VALUES (';

		$separator = '';
		foreach ($this->values as $identifier => $value) {
			if ($value instanceof \Base\Database\Raw) {
				$query .= $separator . $value->expression();
			} else {
				$params [] = $value;
				$query .= $separator . '?';
			}
			$separator = ', ';
		}

		$query .= ')';

		return [$query, $params];
	}

	
	/**
	 * Compile a select query
	 * @return array
	 */
	protected function compileSelect()
	{
		$params = [];

		// select
		$query = 'SELECT ';

		//distinct
		if (count($this->distinct) > 0) {
			$query.= 'DISTINCT '.implode(', '.$this->distinct).', ';
		}

		// values
		if (count($this->select) === 0) {
			$query .= '* ';
		} else {
			$separator = '';
			foreach ($this->select as $identifier) {
				if (is_array($identifier)) {
					list($identifier, $alias) = $identifier;
				} else {
					$alias = false;
				}
				if ($identifier instanceof \Base\Database\Raw) {
					$query.= $separator . $identifier->expression();
				} else {
					$query.= $separator . $this->quoteIdentifier($identifier);
				}
				if ($alias) {
					$query.= ' AS ' . $this->quoteIdentifier($alias);
				}
				$separator = ', ';
			}
		}

		// from
		if (is_array($this->table)) {
			list($table, $alias) = $this->table;
		} else {
			$table = $this->table;
			$alias = false;
		}

		if ($table instanceof \Base\Database\Query) {
			list($subQuery, $subParams) = $table->compile();
			$params = array_merge($params, $subParams);
			$query.= ' FROM (' . $subQuery . ')';
		} else {
			$query.= ' FROM ' . $this->quoteTable($table);
		}
		if ($alias) {
			$query.= ' AS ' . $this->quoteTable($alias);
		}


		// join
		if (count($this->joins) > 0) {
			foreach ($this->joins as $join) {
				if (in_array(strtoupper($join['type']), ['INNER', 'OUTER', 'LEFT', 'RIGHT', 'FULL'])) {
					$query .= strtoupper($join['type']) . ' JOIN ';

					if (is_array($join['table'])) {
						list($table, $alias) = $join['table'];
					} else {
						$table = $join['table'];
						$alias = false;
					}

					$query.= $this->quoteTable($table) . ' ';
					if ($alias) {
						$query.= 'AS ' . $this->quoteTable($alias) . ' ';
					}

					$on = false;
					if (count($join['on']) > 0) {
						$on = true;
						list($onQuery, $onParams) = $this->compileConditions($join['on'], true);
						$query .= 'ON ' . $onQuery . ' ';
						$params = array_merge($params, $onParams);
					}

					if (count($join['where']) > 0) {
						list($onWhereQuery, $onWhereParams) = $this->compileConditions($join['where']);
						if ($on === true) {
							$query .= $join['where'][0]['logic'] . ' ';
						}
						$query.= $onWhereQuery . ' ';
						$params = array_merge($params, $onWhereParams);
					}
				}
			}
		}

		// where
		if (count($this->where) > 0) {
			list($whereQuery, $whereParams) = $this->compileConditions($this->where);
			$query.= ' WHERE ' . $whereQuery;
			$params = array_merge($params, $whereParams);
		}

		// group by
		$separator = ' GROUP BY ';
		foreach ($this->group as $identifier) {
			if ($identifier instanceof \Base\Database\Raw) {
				$query.= $separator . $identifier->expression();
			} else {
				$query.= $separator . $this->quoteIdentifier($identifier);
			}
			$separator = ', ';
		}

		// having
		if (count($this->having) > 0) {
			list($havingQuery, $havingParams) = $this->compileConditions($this->having);
			$query.= ' HAVING ' . $havingQuery;
			$params = array_merge($params, $havingParams);
		}

		// order
		$separator = ' ORDER BY ';
		foreach ($this->order as $identifier => $direction) {
			$direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
			if ($identifier instanceof \Base\Database\Raw) {
				$query.= $separator . $identifier->expression() . ' ' . $direction;
			} else {
				$query.= $separator . $this->quoteIdentifier($identifier) . ' ' . $direction;
			}
			$separator = ', ';
		}

		// limit
		if ($this->limit !== false) {
			$query .= ' LIMIT ' . (int) $this->limit;
		}

		// offset
		if ($this->offset !== false) {
			$query .= ' OFFSET ' . (int) $this->offset;
		}

		// union / union_all
		$unioned = false;
		foreach ($this->union as $union) {
			list($unionQuery, $unionParams) = $union->compile();
			$params = array_merge($params, $unionParams);
			if ($unioned == false) {
				$query = '(' . $query . ')';
			}
			$query.= ' UNION (' . $unionQuery . ')';
			$unioned = true;
		}

		foreach ($this->unionAll as $union) {
			list($unionQuery, $unionParams) = $union->compile();
			$params = array_merge($params, $unionParams);
			if ($unioned == false) {
				$query = '(' . $query . ') ';
			}
			$query.= ' UNION ALL (' . $unionQuery . ')';
			$unioned = true;
		}


		return [$query, $params];
	}


	/**
	 * Compile an update query
	 * @return array
	 */
	protected function compileUpdate()
	{
		$params = [];
		$query = 'UPDATE ';
		$query .= $this->quoteTable($this->table) . ' ';
		$query .= 'SET ';

		$separator = '';
		foreach ($this->values as $identifier => $value) {
			$query .= $separator . $this->quoteIdentifier($identifier) . ' = ';
			if ($value instanceof \Base\Database\Raw) {
				$query .= $value->expression();
			} elseif ($value === null) {
				$query .= 'DEFAULT(' . $this->quoteIdentifier($identifier) . ')';
			} else {
				$params [] = $value;
				$query .= '?';
			}
			$separator = ', ';
		}

		if (count($this->where) > 0) {
			list($whereQuery, $whereParams) = $this->compileConditions($this->where);
			$query.= ' WHERE ' . $whereQuery;
			$params = array_merge($params, $whereParams);
		}
		return [$query, $params];
	}


	/**
	 * Compile a delete query
	 * @return array
	 */
	protected function compileDelete()
	{
		$query = 'DELETE FROM ';
		$query .= $this->quoteTable($this->table);
		$params = [];
		if (count($this->where) > 0) {
			list($whereQuery, $whereParams) = $this->compileConditions($this->where);
			$query.= ' WHERE ' . $whereQuery;
			$params = array_merge($params, $whereParams);
		}
		return [$query, $params];
	}


	/**
	 * Compile a set of conditions
	 * @param array $conditions
	 * @param boolean $asOn
	 * @return array
	 */
	protected function compileConditions(array $conditions, $asOn = false)
	{
		$query = '';
		$params = [];
		$start = true;
		$omitLogic = true;

		foreach ($conditions as $condition) {

			if (!$omitLogic && isset($condition['logic'])) {
				$query .= ' '.$condition['logic'] . ' ';
			}
			$omitLogic = false;

			if ($condition['type'] === 'group_open') {
				$query .= '(';
				$omitLogic = true;
			} elseif ($condition['type'] === 'group_close') {
				$query .= ')';
			} else {
				$query .= $this->quoteIdentifier($condition['first']) . ' ';
				if ($asOn == true) {
					$query .= $condition['operator'] . ' ';
					$query .= $this->quoteIdentifier($condition['second']);
				} else {
					if ($condition['second'] instanceof \Base\Database\Raw) {
						$query .= $condition['operator'] . ' ';
						$query .= $condition['second']->expression();
					} elseif ($condition['operator'] == '=' && $condition['second'] === null) {
						$query .= 'IS NULL';
					} elseif (($condition['operator'] == '!=' || $condition['operator'] == '<>') && $condition['second'] === null) {
						$query .= 'IS NOT NULL';
					} elseif ($condition['second'] instanceof \Base\Database\Query) {
						list($subQuery, $subParams) = $condition['second']->compile();
						$params = array_merge($params, $subParams);
						$query .= $condition['operator'] . ' ';
						$query.= '(' . $subQuery . ')';
					} elseif (is_array($condition['second'])) {
						if ($condition['operator'] === 'BETWEEN' || $condition['operator'] === 'NOT BETWEEN') {
							list($min, $max) = $condition['second'];
							$params[] = $min;
							$params[] = $max;
							$query .= $condition['operator'] . ' ';
							$query .= '? AND ?';
						} elseif ($condition['operator'] === 'IN') {
							$query .= $condition['operator'] . ' ';
							$query .= '(' . implode(',', array_fill(0, count($condition['second']), '?')) . ')';
							$params = array_merge($params, $condition['second']);
						}
					} else {
						$query .= strtoupper($condition['operator']) . ' ';
						$params [] = $condition['second'];
						$query .= '?';
					}
				}
			}
		}
		return [$query, $params];
	}

	/**
	 * Quote a tablename
	 * replace a quote character in the tablename with a double quote character
	 * FI: "table`name" becomes "`table``name`"
	 * @param string $table
	 * @return string
	 */
	protected function quoteTable($table)
	{
		return $this->quote . str_replace($this->quote, $this->quote . $this->quote, $table) . $this->quote;
	}


	/**
	 * Quote an identifier
	 * quote dotted identifiers and quote quotes
	 * "table.col`umn" becomnes "`table`.`col``umn`"
	 * @param string $identifier
	 * @return string
	 */
	protected function quoteIdentifier($identifier)
	{
		$parts = explode('.', $identifier);
		$quoted = [];
		foreach ($parts as $part) {
			$quoted [] = $this->quote . str_replace($this->quote, $this->quote . $this->quote, $part) . $this->quote;
		}
		return implode('.', $quoted);
	}


	/**
	 * Execute the query
	 * With insert queries, pass in the names of the id-field, to get the last-inserted id
	 * The result is either:
	 * - last inserted id for INSERT
	 * - affected rows for UPDATE / DELETE
	 * - resultset for SELECT
	 * @param string $id
	 * @return \Base\Database\Query
	 */
	public function execute($id = 'id')
	{
		list($query, $params) = $this->compile();
		$this->result = $this->connection->execute($query, $params, $this->type, $id);
		return $this;
	}


	/**
	  * Execute the query and get the results immediatly
	 *  With insert queries, pass in the names of the id-field as $primaryOrKey, to get the last-inserted value of that field
	 *  With select queries, pass in a $key and/or a $val to get the following
	 * 
	 * result();
	 * [row, row, ...]
	 * 
	 * result('title');
	 * ['title' => row, 'title' => row, ...]
	 * 
	 * result('title', 'description')
	 * ['title' => 'description', 'title' => 'description', ...]
	 * 
	 *  The result is either:
	 * - last inserted id for INSERT
	 * - number of affected rows for UPDATE / DELETE
	 * - resultsset for SELECT
	 * 
	 * To get the raw itereator use ::iterator
	 * 
	 * @param string $primaryOrKey
	 * @param string $val
	 * @return int|array
	 */
	public function result($primaryOrKey = null, $val = null)
	{
		if ($this->result === null) {
			if ($this->type === 'insert' && $primaryOrKey !== null) {
				$this->execute($primaryOrKey);
			} else {
				$this->execute();
			}
		}

		if (is_int($this->result) || is_string($this->result)) {
			return $this->result;
		} else {
			$result = [];
			$this->result->setFetchMode(\PDO::FETCH_ASSOC);
			foreach ($this->result as $row) {
				if ($primaryOrKey !== null) {
					if ($val !== null) {
						$result[$row[$primaryOrKey]] = $row[$val];
					} else {
						$result[$row[$primaryOrKey]] = $row;
					}
				} else {
					$result[] = $row;
				}
			}
			return $result;
		}
	}


	/**
	 * Get the raw PDO iterator for a SELECT
	 * @return \PDOStatement
	 */
	public function iterator()
	{
		if ($this->result === null) {
			$this->execute();
		}
		return $this->result;
	}

}
