<?php

namespace Base\Database;

class Query
{

	protected $connection = null;
	protected $quote = null;
	protected $type = null;
	protected $query = '';
	protected $params = [];
	protected $table = '';
	protected $select = [];
	protected $distinct = false;
	protected $values = [];
	protected $joins = [];
	protected $where = [];
	protected $group = [];
	protected $having = [];
	protected $limit = false;
	protected $offset = false;
	protected $order = [];
	protected $union = [];
	protected $unionAll = [];
	protected $operators = [
		'=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE', 'NOT LIKE', 'BETWEEN', 'ILIKE',
		'&', '|', '^', '<<', '>>', 'RLIKE', 'REXEXP', 'NOT REGEXP', 'IN', 'ISNULL'
	];
	protected $result = null;

	
	public function __construct($connection, $type = null)
	{
		$this->connection = $connection;
		$this->type = $type;
		$this->quote = $connection->quote();
	}

	public function query($query, $params = [])
	{
		$this->query = $query;
		$this->params = $params;
		return $query;
	}


	public function select()
	{
		$this->select = array_merge($this->select, func_get_args());
		return $this;
	}


	public function table($table)
	{
		$this->table = $table;
		return $this;
	}


	public function distinct($distinct = true)
	{
		$this->distinct = $distinct;
		return $this;
	}


	public function from($table)
	{
		$this->table = $table;
		return $this;
	}


	public function set($columnOrValues, $value = null)
	{
		$this->values($columnOrValues, $value);
		return $this;
	}


	public function values($columnOrValues, $value = null)
	{
		if (is_array($columnOrValues)) {
			$this->values = array_merge($this->values, $columnOrValues);
		} else {
			$this->values[$columnOrValues] = $value;
		}
		return $this;
	}


	public function join($table, $type = 'INNER')
	{
		$this->joins[] = [
			'table' => $table,
			'on' => [],
			'where' => [],
			'type' => $type
		];
		return $this;
	}


	public function on($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		if (count($this->joins) == 0) {
			throw new QueryException('Called ::on without calling join first');
		}
		$this->condition('AND', $this->joins[count($this->joins) - 1]['on'], $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	public function orOn($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		if (count($this->joins) == 0) {
			throw new QueryException('Called ::or_on without calling join first');
		}
		$this->condition('OR', $this->joins[count($this->joins) - 1]['on'], $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	public function onWhere($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		if (count($this->joins) == 0) {
			throw new QueryException('Called ::onWhere without calling join first');
		}
		$this->condition('AND', $this->joins[count($this->joins) - 1]['where'], $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	public function orOnWhere($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		if (count($this->joins) == 0) {
			throw new QueryException('Called ::orOnWhere without calling join first');
		}
		$this->condition('OR', $this->joins[count($this->joins) - 1]['where'], $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	public function where($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		$this->condition('AND', $this->where, $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	public function orWhere($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		$this->condition('OR', $this->where, $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	public function group($group)
	{
		if (is_array($group)) {
			$this->group = array_merge($this->group, $group);
		} else {
			$this->group[] = $group;
		}
		return $this;
	}


	public function having($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		$this->condition('AND', $this->having, $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	public function orHaving($firstOrCallable, $operatorOrSecond = null, $second = null)
	{
		$this->condition('OR', $this->having, $firstOrCallable, $operatorOrSecond, $second);
		return $this;
	}


	public function limit($limit)
	{
		$this->limit = $limit;
		return $this;
	}


	public function offset($offset)
	{
		$this->offset = $offset;
		return $this;
	}


	public function order($columnOrOrders, $direction = 'ASC')
	{
		if (is_array($columnOrOrders)) {
			$this->order = array_merge($this->order, $columnOrOrders);
		} else {
			$this->order[$columnOrOrders] = $direction;
		}
		return $this;
	}


	public function union($query)
	{
		$this->union [] = $query;
		return $this;
	}


	public function unionAll($query)
	{
		$this->unionAll [] = $query;
		return $this;
	}


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

		$query .= ') VALUES ( ';

		$separator = '';
		foreach ($this->values as $identifier => $value) {
			if ($value instanceof \YF\Core\Database\Raw) {
				$query .= $separator . $value->expression() . ' ';
			} else {
				$params [] = $value;
				$query .= $separator . '? ';
			}
			$separator = ', ';
		}

		$query .= ') ';

		return [$query, $params];
	}


	protected function compileSelect()
	{
		$params = [];

		// select
		$query = 'SELECT ';

		//distinct
		if ($this->distinct) {
			$query.= 'DISTINCT ';
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
					$query.= $separator . $identifier->expression() . ' ';
				} else {
					$query.= $separator . $this->quoteIdentifier($identifier) . ' ';
				}
				if ($alias) {
					$query.= 'AS ' . $this->quoteIdentifier($alias) . ' ';
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

		if ($table instanceof \YF\Core\Database\Query) {
			list($subQuery, $subParams) = $table->compile();
			$params = array_merge($params, $subParams);
			$query.= 'FROM (' . $subQuery . ') ';
		} else {
			$query.= 'FROM ' . $this->quoteTable($table) . ' ';
		}
		if ($alias) {
			$query.= 'AS ' . $this->quoteTable($alias) . ' ';
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
			$query.= 'WHERE ' . $whereQuery;
			$params = array_merge($params, $whereParams);
		}

		// group by
		$separator = 'GROUP BY ';
		foreach ($this->group as $identifier) {
			if ($identifier instanceof \YF\Core\Database\Raw) {
				$query.= $separator . $identifier->expression() . ' ';
			} else {
				$query.= $separator . $this->quoteIdentifier($identifier) . ' ';
			}
			$separator = ', ';
		}

		// having
		if (count($this->having) > 0) {
			list($havingQuery, $havingParams) = $this->compileConditions($this->having);
			$query.= 'HAVING ' . $havingQuery . ' ';
			$params = array_merge($params, $havingParams);
		}

		// order
		$separator = 'ORDER BY ';
		foreach ($this->order as $identifier => $direction) {
			$direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
			if ($identifier instanceof \YF\Core\Database\Raw) {
				$query.= $separator . $identifier->expression() . ' ' . $direction . ' ';
			} else {
				$query.= $separator . $this->quoteIdentifier($identifier) . ' ' . $direction . ' ';
			}
			$separator = ', ';
		}

		// limit
		if ($this->limit !== false) {
			$query .= 'LIMIT ' . (int) $this->limit . ' ';
		}

		// offset
		if ($this->offset !== false) {
			$query .= 'OFFSET ' . (int) $this->offset . ' ';
		}

		// union / union_all
		$unioned = false;
		foreach ($this->union as $union) {
			list($unionQuery, $unionParams) = $union->compile();
			$params = array_merge($params, $unionParams);
			if ($unioned == false) {
				$query = '(' . $query . ') ';
			}
			$query.= 'UNION (' . $unionQuery . ') ';
			$unioned = true;
		}

		foreach ($this->unionAll as $union) {
			list($unionQuery, $unionParams) = $union->compile();
			$params = array_merge($params, $unionParams);
			if ($unioned == false) {
				$query = '(' . $query . ') ';
			}
			$query.= 'UNION ALL (' . $unionQuery . ') ';
			$unioned = true;
		}


		return [$query, $params];
	}


	protected function compileUpdate()
	{
		$params = [];
		$query = 'UPDATE ';
		$query .= $this->quoteTable($this->table) . ' ';
		$query .= 'SET ';

		$separator = '';
		foreach ($this->values as $identifier => $value) {
			$query .= $separator . $this->quoteIdentifier($identifier) . ' = ';
			if ($value instanceof \YF\Core\Database\Raw) {
				$query .= $value->expression() . ' ';
			} elseif ($value === null) {
				$query .= 'DEFAULT(' . $this->quoteIdentifier($identifier) . ') ';
			} else {
				$params [] = $value;
				$query .= '? ';
			}
			$separator = ', ';
		}

		if (count($this->where) > 0) {
			list($whereQuery, $whereParams) = $this->compileConditions($this->where);
			$query.= 'WHERE ' . $whereQuery;
			$params = array_merge($params, $whereParams);
		}
		return [$query, $params];
	}


	protected function compileDelete()
	{
		$query = 'DELETE FROM ';
		$query .= $this->quoteTable($this->table) . ' ';
		$params = [];
		if (count($this->where) > 0) {
			list($whereQuery, $whereParams) = $this->compileConditions($this->where);
			$query.= 'WHERE ' . $whereQuery;
			$params = array_merge($params, $whereParams);
		}
		return [$query, $params];
	}


	protected function compileConditions($conditions, $asOn = false)
	{
		$query = '';
		$params = [];
		$start = true;
		$omitLogic = true;

		foreach ($conditions as $condition) {

			if (!$omitLogic && isset($condition['logic'])) {
				$query .= $condition['logic'] . ' ';
			}
			$omitLogic = false;

			if ($condition['type'] === 'group_open') {
				$query .= '( ';
				$omitLogic = true;
			} elseif ($condition['type'] === 'group_close') {
				$query .= ') ';
			} else {
				$query .= $this->quoteIdentifier($condition['first']) . ' ';
				if ($asOn == true) {
					$query .= $condition['operator'] . ' ';
					$query .= $this->quoteIdentifier($condition['second']) . ' ';
				} else {
					if ($condition['second'] instanceof \YF\Core\Database\Raw) {
						$query .= $condition['operator'] . ' ';
						$query .= $condition['second']->expression() . ' ';
					} elseif ($condition['operator'] == '=' && $condition['second'] === null) {
						$query .= 'IS NULL ';
					} elseif (($condition['operator'] == '!=' || $condition['operator'] == '<>') && $condition['second'] === null) {
						$query .= 'IS NOT NULL ';
					} elseif ($condition['second'] instanceof \YF\Core\Database\Query) {
						list($subQuery, $subParams) = $condition['second']->compile();
						$params = array_merge($params, $subParams);
						$query .= $condition['operator'] . ' ';
						$query.= '(' . $subQuery . ') ';
					} elseif (is_array($condition['second'])) {
						if ($condition['operator'] === 'BETWEEN') {
							list($min, $max) = $condition['second'];
							$params[] = $min;
							$params[] = $max;
							$query .= $condition['operator'] . ' ';
							$query .= '? AND ? ';
						} elseif ($condition['operator'] === 'IN') {
							$query .= $condition['operator'] . ' ';
							$query .= '(' . implode(',', array_fill(0, count($condition['second']), '?')) . ') ';
							$params = array_merge($params, $condition['second']);
						}
					} else {
						$query .= strtoupper($condition['operator']) . ' ';
						$params [] = $condition['second'];
						$query .= '? ';
					}
				}
			}
		}
		return [$query, $params];
	}


	protected function quoteTable($table)
	{
		return $this->quote . str_replace($this->quote, $this->quote . $this->quote, $table) . $this->quote;
	}


	protected function quoteIdentifier($identifier)
	{
		$parts = explode('.', $identifier);
		$quoted = [];
		foreach ($parts as $part) {
			$quoted [] = $this->quote . str_replace($this->quote, $this->quote . $this->quote, $part) . $this->quote;
		}
		return implode('.', $quoted);
	}


	public function execute($id = 'id')
	{
		list($query, $params) = $this->compile();
		$this->result = $this->connection->execute($query, $params, $this->type, $id);
		return $this;
	}


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


	public function iterator()
	{
		if ($this->result === null) {
			$this->execute();
		}
		return $this->result;
	}

}
