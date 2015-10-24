<?php

namespace Base;

class Reader
{
	/**
	 * File finder
	 * @var \Closure 
	 */
	protected $finder = null;

	/**
	 * Create a reader
	 * @param \Closure $finder
	 */
	public function __construct(\Closure $finder)
	{
		$this->finder = $finder;
	}

	
	/**
	 * Get a resource
	 * @param string|array $resource
	 * @return array
	 */
	public function get($resource)
	{
		// cast to array
		$resources = (array) $resource;

		// load all paths
		$loaded = [];
		foreach ($resources as $resource) {
			$loaded[] = $this->load($resource);
		}
		
		// merge them
		$data = $this->merge($loaded);

		// clean up stray objects
		array_walk_recursive($data, function(& $element) {
			if (is_object($element)) {
				$element = (array) $element;
			}
		});
		
		return $data;
	}
	

	/**
	 * Load a resource
	 * @param array $resource
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
	 * Merge multiple arrays
	 * @param array $parts
	 * @return array
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
}