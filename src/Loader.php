<?php

namespace Base;

class Loader {

	// Class aliases used
	protected $aliases = [];
	
	// Prefixes that map to specific paths
	protected $prefixes = [];
	
	// one-to-one class to file mappings
	protected $map = [];
	
	// a cache array for found files
	protected $found = [];

	/**
	 * Set prefixes
	 * @param string|array $prefixesOrPrefix
	 * @param string|array $path
	 * @return \Base\Loader
	 */
	public function prefix($prefixesOrPrefix = [], $path = null) {
		if(is_array($prefixesOrPrefix)){
			$this->prefixes = array_merge($this->prefixes, $prefixesOrPrefix);
		} else {
			$this->prefixes[$prefixesOrPrefix] = $path;
		}
		return $this;
	}

	
	/**
	 * Set aliases
	 * @param string|array $aliasOrAliases
	 * @param string $class
	 * @return \Base\Loader
	 */
	public function alias($aliasOrAliases = [], $class = null) {
		if(is_array($aliasOrAliases)){
			$this->aliases = array_merge($this->aliases, $aliasOrAliases);
		} else {
			$this->aliases[$aliasOrAliases] = $class;
		}
		return $this;
	}

	
	/**
	 * Map Classes to files
	 * @param string|array $mapOrClass
	 * @param string $path
	 * @return \Base\Loader|array
	 */
	public function map($mapOrClass = null, $path = null) {
		if ($mapOrClass === null) {
			return $this->map;
		}
		if(is_array($mapOrClass)){
			$this->map = array_merge($this->map, $mapOrClass);
		} else {
			$this->map[$mapOrClass] = $path;
		} 
		return $this;
	}

	
	/**
	 * Register as autoloader
	 */
	public function auto() {
		spl_autoload_register([$this, 'resolve']);
	}

	/**
	 * Resolve an unloaded class
	 * @param string $class
	 */
	public function resolve($class) {
		if (isset($this->map[$class])) {
			// mapped files
			require($this->map[$class]);
			//return the name
			return $this->map[$class];
		} elseif (isset($this->aliases[$class])) {
			// set class alias for PHP, then Loader will be hit again
			class_alias($this->aliases[$class], $class);
			return;
		} else {

			// start with unfound path
			$path = null;
			// for the first pass, the prefix is the entire class
			$prefix = $class;
			// the filename starts as empty string
			$file = '';
			// start with empty file separator
			$separator = '';
			// slice \\.* parts of the back of the prefix
			while (false !== $pos = strrpos($prefix, '\\')) {
				// second part is part of the filename
				$file = substr($prefix, $pos + 1) . $separator . $file;
				// set actual separator
				$separator = DIRECTORY_SEPARATOR;
				// first part is the prefix to look up
				$prefix = substr($prefix, 0, $pos + 1);
				// lookup prefix
				if (isset($this->prefixes[$prefix])) {
					// we found a path
					$path = $this->prefixes[$prefix];
					break;
				}
				// lose the trailing \ for the next pass
				$prefix = rtrim($prefix, '\\');
			}

			// fallback path
			if ($path === null && isset($this->prefixes[''])) {
				$path = $this->prefixes[''];
				$file = str_replace('\\', DIRECTORY_SEPARATOR, $class);
			}

			// find file
			if ($full = $this->find($file, $path)) {
				// require file
				$this->load($full);
				// return the name
				return $full;
			}
		}
	}

	
	/**
	 * Return a finder function 
	 * When the returned function is called, it will in turn call
	 * find with the provided paths
	 * @param string|array $paths
	 * @return \Closure
	 */
	public function finder($path) {
		$loader = $this;
		return function($file, $ext = 'php', $all = false) use ($path, $loader) {
			return $loader->find($file, $path, $ext, $all);
		};
	}

	
	/**
	 * Find a file, by going through all the provided directories
	 * @param string $file
	 * @param string|array $path
	 * @param string $ext
	 * @param boolean $all Get all files; dont stop at the first encounter
	 * @return string|array|boolean
	 */
	public function find($file, $path = [], $ext = 'php', $all = false) 
	{
		// force array
		$paths =  (array) $path;

		// try to get hotcached path
		$key = implode(';', $paths) . '_' . $file . '_' . $ext . '_' . ($all ? 'all' : 'first');
		if (isset($this->found[$key])) {
			return $this->found[$key];
		}

		// find file
		$found = [];
		foreach ($paths as $path) {
			$full = $path . $file . '.' . $ext;
			if (file_exists($full)) {
				if ($all) {
					// finding all valid paths: store path
					$found [] = $full;
				} else {
					// only getting first
					// cache found file
					$this->found[$key] = $full;
					// return the path
					return $full;
				}
			}
		}

		if ($all) {
			// hotcache found paths & return
			$this->found[$key] = $found;
			return $found;
		}

		// still here: tried to find path and failed
		return false;
	}
	
	
	/**
	 * require a file
	 * @param string $file
	 */
	public function load($file)
	{
		require($file);
	}
}