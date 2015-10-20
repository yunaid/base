<?php

namespace Base\ORM;

class Schema
{

	/**
	 * Loaded schema files
	 * @var array 
	 */
	protected $loaded = [];


	/**
	 * Construct
	 * @param \Closure $finder
	 */
	public function __construct($finder)
	{
		$this->finder = $finder;
	}


	/**
	 * Get a schema by name
	 * @param string $name
	 * @return array|null
	 */
	public function get($name)
	{
		$name = strtolower($name);
		// check if already loaded
		if (isset($this->loaded[$name])) {
			return $this->loaded[$name];
		}
		
		if ($file = $this->finder->__invoke($name)) {
			// get vars & store
			$this->loaded[$name] = include($file);
			// return vars
			return $this->loaded[$name];
		}
		// nothing found
		return null;
	}
}
