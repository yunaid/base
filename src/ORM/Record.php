<?php

namespace Base\ORM;

use \Base\ORM\Mapper as Mapper;

/**
 * The Record object is returned by the Mapper class
 * when using the single or all methods (or one of the relation methods)
 * 
 * A record has a reference to it's parent mapper object
 * so that relations of this record can be resolved in that single mapper object
 * This keeps the record slim an memory consumtion low
 * 
 */
class Record
{

	/**
	 * Assoc array with data
	 * @var type 
	 */
	protected $data = null;
	
	/**
	 * Prefix
	 * @var string 
	 */
	protected $prefix = '';
	
	/**
	 * Parent mapper object
	 * @var \Base\ORM\Mapper 
	 */
	protected $mapper = null;
	
	/**
	 * Available columns
	 * @var array 
	 */
	protected $columns = [];
	
	/**
	 * Available relations
	 * @var array 
	 */
	protected $relations = [];
	
	/**
	 * Available methods in mapper
	 * @var type 
	 */
	protected $methods = [];
	
	
	/**
	 * Constructor
	 * @param array $data
	 * @param string $prefix
	 * @param \Base\ORM\Mapper $mapper
	 * @param array $columns
	 * @param array $relations
	 * @param array $methods
	 */
	public function __construct(array $data = [], $prefix = '', Mapper $mapper = null, array $columns = [], array $relations = [], array $methods = [])
	{
		$this->data = $data;
		$this->prefix = $prefix;
		$this->mapper = $mapper;
		$this->columns = $columns;
		$this->relations = $relations;
		$this->methods = $methods;
	}


	/**
	 * get the raw record data
	 * @return array
	 */
	public function data()
	{
		return $this->data;
	}


	/**
	 * Get data as flat array
	 * Include for relations an array with ids will be included
	 * @return array
	 */
	public function flat()
	{
		return $this->mapper->flat($this);
	}


	/**
	 * Get a pivot value
	 * @param string $name
	 * @return int|string|array|null
	 */
	public function pivot($name)
	{
		if (isset($this->data[$this->prefix . 'pivot:' . $name])) {
			return $this->data[$this->prefix . 'pivot:' . $name];
		} else {
			return null;
		}
	}


	/**
	 * Access a property of a Record
	 * First we will try to get the property from the data
	 * If that fails, we'll assume it is a relation and let the parent mapper 
	 * figure it out
	 * 
	 * @param string $name
	 * @return int|string|array|null
	 */
	public function __get($name)
	{
		if (isset($this->data[$this->prefix . $name])) {
			if (isset($this->columns[$name])) {
				switch ($this->columns[$name]) {
					case 'int':
						return (int) $this->data[$this->prefix . $name];
					case 'float':
						return (float) $this->data[$this->prefix . $name];
					case 'boolean':
						return (bool) $this->data[$this->prefix . $name];
					case 'json':
						return json_decode($this->data[$this->prefix . $name]);
					case 'array':
						return json_decode($this->data[$this->prefix . $name], true);
					default:
						return $this->data[$this->prefix . $name];
				}
			} else {
				return $this->data[$this->prefix . $name];
			}
		} elseif ($this->mapper !== null && in_array($name, $this->relations)) {
			$this->data[$this->prefix . $name] = $this->mapper->related($name, $this, null, 0, array());
			return $this->data[$this->prefix . $name];
		} else {
			return null;
		}
	}
	
	
	/**
	 * Call a defined helper method
	 * Or call a relation with amount / skip / sort
	 * @param string $name
	 * @param array $args
	 * @return int|string|array|null
	 */
	public function __call($name, array $args)
	{
		if ($this->mapper !== null) {
			if(in_array($name, $this->methods)) {
				return call_user_func_array([$this->mapper, 'method'], [$name, $this, $args]);
			}
			
			if (in_array($name, $this->relations)) {
				$amount = isset($args[0]) ? $args[0] : null;
				$skip = isset($args[1]) ? $args[1] : 1;
				$sort = isset($args[2]) ? $args[2] : array();
				return $this->mapper->related($name, $this, $amount, $skip, $sort);
			}
		}
	}

	
	/**
	 * Control the debug info
	 * @return array
	 */
	public function __debugInfo()
	{
		return $this->data;
	}


	/**
	 * Control the debug info
	 * @return string
	 */
	public function __toString()
	{
		return $this->prefix . var_export($this->data, true);
	}
}