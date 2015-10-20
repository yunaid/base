<?php

namespace Base;

use \Base\Cache\Adapter as Adapter;

class Cache
{
	/**
	 * Params
	 * @var array 
	 */
	protected $params = [
		'active' => true,
		'lifetime' => 3600
	];
	
	/**
	 * Adapter
	 * @var \Base\Contract\Cache\Adapter
	 */
	protected $adapter = null;
	
	/**
	 * The group (acts as a prefix)
	 * @var string 
	 */
	protected $group = 'default';
	
	
	/**
	 * Constructor
	 * @param string $group
	 * @param \Base\Cache\Adapter $adapter
	 * @param array $params
	 */
	public function __construct($group, Adapter $adapter, array $params = [])
	{
		$this->adapter = $adapter;
		$this->group = $group;
		$this->params = array_merge($this->params, $params);
	}


	/**
	 * Set a value
	 * @param string $name
	 * @param string|array $value
	 * @param int|boolean $lifetime
	 */
	public function set($name, $value, $lifetime = false)
	{
		if ($this->params['active'] == false) {
			return;
		}
		$lifetime = $lifetime ? : $this->params['lifetime'];
		$this->adapter->set($this->group.'.'.$name, $value, $lifetime);
	}


	/**
	 * Get a value
	 * @param string $name
	 * @param string|array $default
	 * @return string|array
	 */
	public function get($name, $default = null)
	{
		if ($this->params['active'] == false) {
			return $default;
		}
		return $this->adapter->get($this->group.'.'.$name, $default);
	}


	/**
	 * Delete a value
	 * leave empty to delete everything in this group
	 * '*' at the end allowed
	 * @param string $name
	 */
	public function delete($name = '*')
	{
		if ($this->params['active'] == false) {
			return;
		}
		$this->adapter->delete($this->group.'.'.$name);
	}
}