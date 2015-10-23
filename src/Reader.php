<?php

namespace Base;

use \Base\Cache;
use \Base\Arr;

class Reader
{

	protected $finder = null;

	protected $cache = null;
	
	protected $data = [];
	
	public function __construct(\Closure $finder, Cache $cache = null)
	{
		$this->finder = $finder;
		$this->cache = $cache;
	}


	public function read($resource, $as = null)
	{
		if($as !== null){
			$name = $as;
		} elseif(is_string($resource)){
			$name = $resource;
		} else {
			$name = crc32(implode('_', (array) $resource));
		}
		
		if(!isset($this->data[$name])){
			if($this->cache !== null && $data = $this->cache->get($name)) {
				// result from cache
				$this->data[$name] = $data;
			} else {
				$resources = (array) $resource;
				
				// load all paths
				$loaded = [];
				foreach ($resources as $resource) {
					$loaded[] = $this->load($resource);
				}

				// merge them
				$data = $this->merge($loaded);

				// clean up stray objects
				$data = $this->clean($data);
				
				// cache data
				if($this->cache !== null){
					$this->cache->set($name, $data);
				}

				// store it locally
				$this->data[$name] = $data;
			}
		}
		return $this->data[$name];
	}
	

	/**
	 * Load cascading paths
	 * @param Array $paths
	 * @return Array
	 */
	protected function load($resource)
	{
		$files = $this->finder->__invoke($resource, 'php', true);
		$loaded = [];
		foreach ($files as $file) {
			$loaded[] = include($file);
		}
		return $this->merge($loaded);
	}


	/**
	 * Merge multiple arrys
	 * @param Array $parts
	 * @return Array
	 */
	protected function merge($parts)
	{
		if (count($parts) === 0) {
			return [];
		} else {
			// start with the deepest group
			$parts = array_reverse($parts);

			// get the deepest layer first
			$data = array_shift($parts);

			// loop through groups and merge
			foreach ($parts as $part) {
				$data = array_replace_recursive($data, $part);
			}
			return $data;
		}
	}


	/**
	 * Clean of remaing (object)'s in a data array
	 * @param array $data
	 * @return array
	 */
	protected function clean($data)
	{
		array_walk_recursive($data, function(& $element) {
			if (is_object($element)) {
				$element = (array) $element;
			}
		});
		return $data;
	}
}