<?php

namespace Base\ORM;

class Mapper implements \Iterator
{

	// The name of the schema for this mapper
	protected $name = null;
	
	// Alias used in queries for this mapper
	protected $alias = '';
	
	// The Schema object holding params for all mappers
	protected $schema = null;
	
	// Creates records with make()
	protected $recordFactory = null;
	
	// Creates mappers with make()
	protected $mapperFactory = null;
	
	// DB instance
	protected $database = null;
	
	// Basic select query. Will be cloned to create actual query. use Query methods to access the query directly
	protected $query = null;
	
	// The params extracted from schema for this mapper / name
	protected $params = [
		'database' => '',
		'table' => '',
		'columns' => [],
		'relations' => []
	];
	
	// Mapper: the relational parent of the mapper.
	protected $origin = null;
	
	// Array with mappers created for relations the of produced records
	protected $relations = [];
	
	// Modifiers
	protected $only = [];
	protected $with = [];
	protected $filter = [];
	protected $filterPivot = [];
	protected $sort = [];
	protected $sortPivot = [];
	protected $amount = null;
	protected $skip = null;
	
	// Helper methods, callable on records
	protected $methods = [];
	
	// Iterator implementation
	// PDO iterator, set by all()
	protected $iterator = null;
	
	// position for the implementation of Iterator
	protected $position = 0;
	
	// current row fetched from PDO, for the implementation of Iterator
	protected $current = null;

	
	/**
	 * Construct
	 * Store arguments and extract params by given name for this mapper
	 * 
	 * @param String $name
	 * @param \Base\ORM\Schema $schema
	 * @param \Base\Database $database
	 * @param \Base\ORM\RecordFactory $recordFactory
	 * @param \Base\ORM\MapperFactory $mapperFactory
	 */
	public function __construct($name, $schema, $database, $recordFactory, $mapperFactory)
	{
		$this->name = $name;
		$this->alias = $this->name;
		$this->schema = $schema;
		$this->database = $database;
		$this->recordFactory = $recordFactory;
		$this->mapperFactory = $mapperFactory;
		$this->query = $database->select();
		if($params = $this->schema->get($name)){
			$this->params = array_merge($this->params, $this->schema->get($name));
		}
	}

	
	/**
	 * Get or Set with.
	 * @param mixed $with Array or String to add, null to get
	 * @return mixed \Base\ORM\Mapper or Array
	 */
	public function with($with = null)
	{
		if ($with === null) {
			return $this->with;
		} elseif (is_array($with)) {
			$this->with = array_merge($this->with, $with);
		} else {
			$this->with [] = $with;
		}
		return $this;
	}


	/**
	 * Select only specific values to keep queries small. 
	 * Be careful ,as it will omit foreign keys needed for joins
	 * @param Mixed $only Array for multiple values String to set single value
	 * @return \Base\ORM\Mapper
	 */
	public function only($only)
	{
		if (!is_array($only)) {
			$only = [$only];
		}
		$this->only = $only;
		return $this;
	}


	/**
	 * Add a filter
	 * @param String|Callable|Array $keyOrCallableOrFilters 
	 * @param String|Callable $operatorOrValuesOrCallable
	 * @param Mixed $value
	 * @return \Base\ORM\Mapper
	 */
	public function filter($keyOrCallableOrFilters = null, $operatorOrValueOrCallable = null, $value = null)
	{
		if ($keyOrCallableOrFilters === null) {
			// get filters
			return $this->filter;
		} elseif (is_object($keyOrCallableOrFilters) && method_exists($keyOrCallableOrFilters, '__invoke')) {
			// filter is an unnamed closure
			$this->filter [] = $keyOrCallableOrFilters;
		} elseif (is_object($operatorOrValueOrCallable) && method_exists($operatorOrValueOrCallable, '__invoke')) {
			// filter is an named closure (shouldnt happen, makes no sense)
			$this->filter[$keyOrCallableOrFilters] = $operatorOrValueOrCallable;
		} elseif(is_array($keyOrCallableOrFilters)) {
			// called with key / value filters, automatically assume '='
			foreach($keyOrCallableOrFilters as $key => $value) {
				$this->filter[$key] = [$key, '=', $value];
			}
		} elseif ($value === null) {
			// key value pair given
			$this->filter[$keyOrCallableOrFilters] = [$keyOrCallableOrFilters, '=', $operatorOrValueOrCallable];
		} else {
			// complete key operator value pair given
			$this->filter[$keyOrCallableOrFilters] = [$keyOrCallableOrFilters, $operatorOrValueOrCallable, $value];
		}
		return $this;
	}


	/**
	 * Remove all filters on a specific key
	 * @param type $key
	 * @return \Base\ORM\Mapper
	 */
	public function unfilter($key = null)
	{
		if ($key === null) {
			$this->filter = [];
		} elseif (isset($this->filter[$key])) {
			unset($this->filter[$key]);
		}
		return $this;
	}


	/**
	 * Sort or sorts
	 * Provide $key, $direction or array('key'=>'direction')
	 * @param Mixed $keyOrSorts
	 * @param String $direction ASC / DESC
	 * @return \Base\ORM\Mapper
	 */
	public function sort($keyOrSorts = null, $direction = 'ASC')
	{

		if ($keyOrSorts === null) {
			return $this->sort;
		} elseif (is_array($keyOrSorts)) {
			$this->sort = array_merge($this->sort, $keyOrSorts);
		} else {
			$this->sort[$keyOrSorts] = $direction;
		}
		return $this;
	}


	/**
	 * Remove all sorts on a key
	 * @param Mixed $key String for a key, null for all
	 * @return \Base\ORM\Mapper
	 */
	public function unsort($key = null)
	{
		if ($key === null) {
			$this->sort = [];
		} elseif (isset($this->sort[$key])) {
			unset($this->sort[$key]);
		}
		return $this;
	}


	/**
	 * Set amount
	 * @param int $amount
	 * @return \Base\ORM\Mapper
	 */
	public function amount($amount)
	{
		if(!$amount){
			$amount = null;
		}
		$this->amount = $amount;
		return $this;
	}


	/**
	 * Set Skip
	 * @param int $skip
	 * @return \Base\ORM\Mapper
	 */
	public function skip($skip)
	{
		if(!$skip){
			$skip = null;
		}
		$this->skip = $skip;
		return $this;
	}


	/**
	 * Used to call set filters / sorters / etc. on a relation
	 * The first of the arguments should be a callable function
	 * The function will we called with the relation mapper as argument
	 * Returns $this so it is chainable
	 * 
	 * Or set a piece of query directly on the query object (select / where , etc.)
	 * 
	 * @param String $name
	 * @param Array $arguments
	 * @return \Base\ORM\Mapper
	 */
	public function __call($name, $arguments)
	{
		if (isset($this->params['relations'][$name])) {
			// get mapper
			$mapper = $this->mapper($name);
			if (isset($arguments[0]) && is_object($arguments[0]) && method_exists($arguments[0], '__invoke')) {
				// call give closure with the mapper as argument
				$arguments[0]([$mapper]);
				return $this;
			} else {
				// just get the mapper
				return $mapper;
			}
		} else {
			if (method_exists($this->query, $name)) {
				// $name is a query function. Add it to the query
				call_user_func_array([$this->query, $name], $arguments);
			}
			return $this;
		}
	}


	/**
	 * Get a pivot
	 * @param Callable $pivot
	 * @return Mixed
	 */
	public function pivot($callable = null)
	{
		if (is_object($callable) && method_exists($callable,'__invoke')) {
			// make sure calls to filter and sort go into a separate array
			$filter = $this->filter;
			$sort = $this->sort;
			$this->filter = $this->filterPivot;
			$this->sort =  $this->sortPivot;

			$callable($this);
			
			$this->filter = $filter;
			$this->sort = $sort;
		}
		return $this;
		
	}

	
	/**
	 * Fire a query to get a collection of records
	 * 
	 * @param \Base\ORM\Record $record. Used when fetching relations of a record
	 * @return String $name relation name to get
	 * @return Mixid \Base\ORM\Mapper Array
	 */
	public function all($record = null, $name = null)
	{
		// create a query
		$query = $this->query();


		if ($this->origin !== null && $record !== null) {
			$relation = $this->origin->relation($name);
			switch ($relation[1]) {
				case 'many':
					$query->where($this->alias . '.' . $relation[2], $record->id);
					break;
				case 'pivot':
				case 'set':
					// extra pivot stuff
					if ($this->pivot !== null) {
						// pivot filters
						foreach ($this->filterPivot as $key => $filter) {
							if (is_array($filter)) {
								$query->where($this->alias . ':pivot.' . $filter[0], $filter[1], $filter[2]);
							} elseif (is_callable($filter)) {
								$query->where($filter);
							}
						}
						// pivot sorts
						foreach ($this->sortPivot as $key => $direction) {
							$query->order($this->alias . ':pivot.' . $key, $direction);
						}

						// pivot columns
						if (isset($relation[5]) && is_array($relation[5])) {
							foreach ($relation[5] as $column) {
								$query->select([$this->alias . ':pivot.' . $column, $this->alias . ':pivot:' . $column]);
							}
						}
					}
				case 'pivot':
					$query->join([$relation[2], $this->alias . ':pivot'], 'INNER')
						->on($this->alias . ':pivot.' . $relation[4], $this->alias . '.id')
						->on_where($this->alias . ':pivot.' . $relation[3], $record->id);
					break;
				case 'set':
					$query->join([$relation[2], $this->alias . ':pivot'], 'INNER')
						->on($this->alias . ':pivot.' . $relation[4], $this->alias . '.id')
						->on_where($this->alias . ':pivot', $record->{$relation[3]});
					break;
			}
		}

		$this->iterator = $query->iterator();
		return $this;
		
	}


	/**
	 * Get a single record. Either in relation to an origin or just by itself
	 * @param Mixed $idOrFilters a record id or several filters. Will be omitted when getting a related record
	 * @param \Base\ORM\Record The record that is requesting a relation 
	 * @param string $name The name of the relation that the record is requesting
	 * @return \Base\ORM\Record
	 */
	public function one($idOrFilters = null, $record = null, $name = null)
	{
		if ($this->origin !== null && in_array($name, $this->origin->with())) {
			// data was already loaded 'with' the original record.
			// the dataset in the record also contains the data for this new record
			return $this->record($record->data());
		}

		// create a new query
		$query = $this->query();

		if ($this->origin !== null && $record !== null) {
			$relation = $this->origin->relation($name);
			// this mapper has a relation to an origin
			switch ($relation[1]) {
				case 'one':
					// origin 'has one' of this
					$query->where($this->alias . '.' . $relation[2], $record->id);
					break;
				case 'belongs':
					// origin 'belongs to' this
					$query->where($this->alias . '.id', $record->{$relation[2]});
					break;
			}
		} elseif (is_scalar($idOrFilters) && $idOrFilters !== null) {
			$query->where($this->alias . '.id', $idOrFilters);
		} elseif (is_array($idOrFilters)) {
			foreach ($idOrFilters as $key => $val) {
				$query->where($this->alias . '.' . $key, $val);
			}
		}

		// get the result
		$result = $query->limit(1)->result();
		if (isset($result[0])) {
			return $this->record($result[0]);
		} else {
			return null;
		}
	}


	/**
	 * Count the number of results that will be returned when calling all()
	 * @param \Base\Database\Query $query
	 * @return int
	 */
	public function count($query = null)
	{
		if ($query === null) {
			$query = $this->createQuery();
		}
		// apply filters
		$this->applyFilters($query);
		// apply with as we might be filtering on values created in 'with'
		$this->applyWith($query);
		// ideally the columns applied in relations would be omitted
		// but the little extra overhead seems ok.
		$result = $query->select([$this->database->raw('COUNT(*)'), '__count__'])->result();
		return $result[0]['__count__'];
	}


	/**
	 * Run a custom query
	 * @param Base\Database\Query $query
	 * @return \Base\ORM\Mapper
	 */
	public function fetch($query)
	{
		$this->iterator = $query->iterator();
		return $this;
	}

	
	/**
	 * Build a query. If no query is provided, as basic select query will be created
	 * with FROM this table
	 * @param Base\Database\Query $query
	 * @return Base\Database\Query
	 */
	public function query($query = null)
	{
		// create a query
		if ($query === null) {
			$query = $this->createQuery();
		}

		$this->applyFilters($query);
		$this->applySorts($query);
		$this->applyAmount($query);
		$this->applySkip($query);
		$this->applyColumns($query);
		$this->applyWith($query);

		return $query;
	}


	protected function createQuery()
	{
		$query = clone($this->query);
		$query->from([$this->params['table'], $this->alias]);
		return $query;
	}


	protected function applyFilters(& $query)
	{
		foreach ($this->filter as $key => $filter) {
			if (is_array($filter)) {
				$query->where($this->alias . '.' . $filter[0], $filter[1], $filter[2]);
			} elseif (is_callable($filter)) {
				$query->where($filter);
			}
		}
	}


	protected function applySorts(& $query)
	{
		foreach ($this->sort as $key => $direction) {
			$query->order($this->alias . ':' . $key, $direction);
		}
	}


	protected function applyAmount(& $query)
	{
		if ($this->amount) {
			$query->limit($this->amount);
		}
	}


	protected function applySkip(& $query)
	{
		if ($this->skip) {
			$query->offset($this->skip);
		}
	}


	protected function applyColumns(& $query)
	{
		if (!empty($this->only)) {
			foreach ($this->only as $column) {
				if (isset($this->params['columns'][$column])) {
					$query->select([$this->alias . '.' . $column, $this->alias . ':' . $column]);
				}
			}
		} else {
			foreach ($this->params['columns'] as $column => $type) {
				$query->select([$this->alias.'.'.$column, $this->alias.':'.$column]);
			}
		}
	}


	protected function applyWith(& $query)
	{
		foreach ($this->with as $with) {
			if (isset($this->params['relations'][$with])) {
				$params = $this->params['relations'][$with];
				if ($params[1] === 'one' || $params[1] === 'belongs') {
					// the 'with' is a relation that can be loaded in one query
					// get the mapper for this relation
					$mapper = $this->mapper($with);
					// now do the actual join
					$query->join([$mapper->table(), $mapper->alias()], 'LEFT');
					// the 'on' part is different for one / belongs
					if ($params[1] === 'one') {
						$query->on($mapper->alias() . '.' . $params[2], $this->alias . '.id');
					}
					if ($params[1] === 'belongs') {
						$query->on($mapper->alias() . '.id', $this->alias . '.' . $params[2]);
					}
					// let the mapper set it's additional query stuff
					$mapper->query($query);
				}
			} else {
				$query->select($with);
			}
		}
	}


	/**
	 * Get a related records or record
	 * First get a relation mapper, then call all() or one() on it
	 * Called from a record
	 * 
	 * @param String $name the name of the relation
	 * @param Base\ORM\Record
	 * @param int $amount override or set amount of related items
	 * @param int $skip override or set skipped number of related items
	 * @param Array $sort override or set sorting
	 * @return Mixed array($records) or Base\ORM\Record
	 */
	public function related($name, $record, $amount = null, $skip = 0, $sort = array())
	{
		if (isset($this->params['relations'][$name])) {
			$type = $this->params['relations'][$name][1];
			if ($type === 'many' || $type === 'pivot' || $type === 'set') {
				$mapper = $this->mapper($name);
				if($amount){
					$mapper->amount($amount);
				}
				if($skip){
					$mapper->skip($skip);
				}
				if(is_array($sort) && count($sort) > 0){
					$mapper
					->unsort()
					->sort($sort);
				}
				return call_user_func_array([$mapper, 'all'], [$record, $name]);
			} elseif ($type === 'one' || $type === 'belongs') {
				return call_user_func_array([$this->mapper($name), 'one'], [null, $record, $name]);
			}
		}
		return null;
	}


	/**
	 * Return the record given as a flat array
	 * If no record is given, flatten the current iterator
	 * For the relations with multiple elements, include arrays with ids
	 * @param Base\ORM\Record
	 * @return Array
	 */
	public function flat($record = null)
	{
		if($record === null) {
			// no record given: flatten the iterator
			if($this->iterator !== null) {
				$result = [];
				foreach ($this as $record) {
					$result [] = $record;
				}
				return $result;
			} else {
				return null;
			}
		} else {
			$flat = [];

			foreach ($this->params['columns'] as $column => $type) {
				$flat[$column] = $record->{$column};
			}
			foreach ($this->params['relations'] as $name => $relation) {
				if ($relation[1] === 'many' || $relation[1] === 'pivot' || $relation[1] === 'set') {
					$flat[$name] = [];
					$related = call_user_func_array([$this, 'all'], [$record, $name]);
					foreach ($related as $item) {
						$flat[$name][] = $item->id;
					}
				}
			}
			return $flat;
		}
	}

	
	/**
	 * Create a record from a flat array of values
	 * Relations can be present in the form of arrays with ids in the $data
	 * They will all be queried to populate the relations
	 * In a sense this is the reverse of the method 'flat'
	 * @param Array $data
	 * @return \Base\ORM\Record
	 */
	public function record($data = [])
	{
		foreach($this->params['columns'] as $name => $type){
			if(isset($data[$name])){
				
			}
		}
		
		foreach ($this->params['relations'] as $name => $relation) {
			if ($relation[1] === 'many' || $relation[1] === 'pivot' || $relation[1] === 'set' || $relation[1] === 'one') {
				if (isset($data[$relation[0]])) {
					$ids = $data[$relation[0]];
					if( ! is_array($ids)){
						$ids = [$ids];
					}
					$data[$relation[0]] = $this->mapper($relation[0])->filter('id', 'IN', $ids)->all();
				}
			}
		}
	
		return $this->recordFactory->__invoke(
			$data, 
			$this->alias . ':',
			$this, 
			$this->params['columns'], 
			array_keys($this->params['relations']), 
			array_keys($this->methods)
		);
	}
	
	
	/**
	 * Define or call a helper method (calling will be done from a record)
	 * @param string $name
	 * @param \Base\Record || Closure $recordOrCallable
	 * @param array $args
	 * @return mixed || null
	 */
	public function method($name, $recordOrCallable, $args = array())
	{
		if(is_object($definition) && method_exists($recordOrCallable, '__invoke')){
			// define a method
			$this->methods[$name] = $recordOrCallable;
		} elseif(isset($this->methods[$name])) {
			// call a method
			switch (count($args)) {
				case 0:
					return $this->methods[$name]($recordOrCallable);
				case 1:
					return $this->methods[$name]($recordOrCallable, $args[0]);
				case 2:
					return $this->methods[$name]($recordOrCallable, $args[0], $args[1]);
				case 3:
					return $this->methods[$name]($recordOrCallable, $args[0], $args[1], $args[2]);
				default:
					array_unshift($args, $recordOrCallable);
					return call_user_func_array($this->methods[$name], $args);
			}
		}
	}
	
	
	/**
	 * The mapper is a relation to some parent mapper
	 * Set that origin mapper and the name that it was sued under
	 * 
	 * also build an alias
	 * 
	 * This info is used when calling all() or one()
	 * 
	 * @param Object $origin
	 */
	public function origin($origin, $name)
	{
		// set the origin
		$this->origin = $origin;
		// create an alias from the origin
		$this->alias = $origin->alias() . ':' . $name;
	}


	/**
	 * Get the mapper alias
	 * @return String
	 */
	public function alias()
	{
		return $this->alias;
	}


	/**
	 * Get the params of a specific relation
	 * @param String $name
	 * @return Array
	 */
	public function relation($name)
	{
		if (isset($this->params['relations'][$name])) {
			return $this->params['relations'][$name];
		}
		return null;
	}


	/**
	 * Get the table name
	 * @return String
	 */
	public function table()
	{
		return $this->params['table'];
	}


	/**
	 * Get the columns
	 * @return Array
	 */
	public function columns()
	{
		return $this->params['colums'];
	}


	/**
	 * Get a related-mapper
	 * create it when it doesnt exist
	 * called when getting related records in a record
	 * or from __call when the relation is needed to put filters on
	 * 
	 * @param String $name
	 * @return \Base\ORM\Mapper
	 */
	protected function mapper($name)
	{
		if (!isset($this->mappers[$name])) {
			if (isset($this->params['relations'][$name])) {
				// create mapper
				$mapper = $this->mapperFactory->__invoke($this->params['relations'][$name][0]);
				// set origin
				$mapper->origin($this, $name);
			} else {
				$mapper = null;
			}

			// add it to the mappers
			$this->mappers[$name] = $mapper;
		}
		return $this->mappers[$name];
	}


	/**
	 * 
	 * Iterator methods
	 * 
	 */
	public function reset()
	{
		$this->iterator = null;
	}


	public function rewind()
	{
		if ($this->iterator === null) {
			$this->all();
		}
		$this->current = $this->iterator->fetch(\PDO::FETCH_ASSOC);
		$this->position = 0;
	}


	public function current()
	{
		return $this->record($this->current);
	}


	public function key()
	{
		return $this->position;
	}


	public function next()
	{
		$this->current = $this->iterator->fetch(\PDO::FETCH_ASSOC);
		$this->position++;
	}


	public function valid()
	{
		return $this->current == true;
	}

}
