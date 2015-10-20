<?php

namespace Base\Contract\Cache;

interface Adapter
{
	
	/**
	 * Get a value from cache
	 * @param string $name
	 * @param atring|array $default
	 * @return mixed
	 */
	public function get($name, $default = null);
	
	
	/**
	 * Set a value
	 * @param string $name
	 * @param string $value
	 * @param int $lifetime in seconds
	 * @return void
	 */
	public function set($name, $value, $lifetime = 3600);
	
	
	/**
	 * Delete a value from cache
	 * Use a wildcard * to remove a group of entries at once
	 * @param string $name
	 * @return void
	 */
	public function delete($name = '*');
}
