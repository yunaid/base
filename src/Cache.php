<?php

namespace Base;

class Cache
{
	// params
	protected $params = [
		'active' => true,
		'lifetime' => 3600
	];
	
	// the cache adapter
	protected $adapter = null;
	
	// the group (acts as a prefix)
	protected $group = 'default';
	
	
	/**
	 * Constructor
	 * @param string $group
	 * @param \Cache\Adapter $adapter
	 */
	public function __construct($group, $adapter, $params = [])
	{
		$this->adapter = $adapter;
		$this->group = $group;
		$this->params = array_merge($this->params, $params);
	}


	/**
	 * Set a value
	 * @param string $name
	 * @param string|array $value
	 * @param int $lifetime
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