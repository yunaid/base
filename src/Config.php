<?php

namespace Base;

use \Base\Reader;
use \Base\Arr;
use \Base\Cache;

class Config
{
	/**
	 * Name of the config file / resource
	 * @var string 
	 */
	protected $name = null;
	
	/**
	 * Reader
	 * @var \Base\Reader 
	 */
	protected $reader = null;
	
	/**
	 * Array accessor
	 * @var \Base\Arr 
	 */
	protected $arr = null;
	
	/**
	 * Array of names to use instead of name
	 * @var array 
	 */
	protected $resource = null;
	
	/**
	 * Cache key
	 * @var string 
	 */
	protected $key = null;
	
	/**
	 * Cache object
	 * @var \Base\Cache 
	 */
	protected $cache = null;
	
	/**
	 * Retrieved data or not
	 * @var boolean 
	 */
	protected $loaded = false;

	
	/**
	 * Create a config object
	 * @param string $name
	 * @param \Base\Reader $reader
	 * @param \Base\Arr $arr
	 * @param array $resource
	 * @param \Base\Cache $cache
	 */
	public function __construct($name, Reader $reader, Arr $arr, $resource = null, Cache $cache = null)
	{
		$this->name = $name;
		$this->reader = $reader;
		$this->arr = $arr;
		$this->resource = $resource;
		$this->cache = $cache;
		
		if(is_array($this->resource)){
			$this->key = abs(crc32(implode('_', $this->resource)));
		} else {
			$this->key = $this->name;
		}
	}
	
	
	/**
	 * Get a path form the config
	 * @param string|array $path
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($path = null, $default = null)
	{
		if($this->loaded === false) {
			if($this->cache !== null && $data = $this->cache->get($this->key)) {
				$this->arr->data($data);
			} else {
				if($this->resource === null) {
					$data = $this->reader->get($this->name);
				} else {
					$data = $this->reader->get($this->resource);
				}
				if($this->cache !== null) {
					$this->cache->set($this->key, $data);
				}
				$this->arr->data($data);
			}	
			$this->loaded = true;
		}
		return $this->arr->get($path, $default);
	}
}
