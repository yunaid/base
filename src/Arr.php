<?php

namespace Base;

class Arr
{

	/**
	 * Array with data
	 * @var array 
	 */
	protected $data = [];

	/**
	 * Contructor
	 * @param array $data
	 */
	public function __construct(array $data = [])
	{
		$this->data = $data;
	}


	/**
	 * Get or set data
	 * @param array|null $data
	 * @return array|void
	 */
	public function data(array $data = null)
	{
		if($data === null) {
			return $this->data;
		} else {
			$this->data = $data;
		}
	}
	
	
	/**
	 * Alias for ->data(null) or ->get(null)
	 * @return array
	 */
	public function flat()
	{
		return $this->data;
	}
	
	/**
	 * Get a var or the default value
	 * 
	 * 
	 * With the given data array
	 * -----------------------------
	 * [
	 *   'foo' => [
	 *      'bar' => 'val1'
	 *   ],
	 *   'bar.baz' => [
	 *      'qux' => 'val2'
	 *   ],
	 *   'bar' => [
	 *      'baz.qux' => 'val3'
	 *   ]
	 * ]
	 * 
	 * ------------------------------
	 * ->get('foo.bar');
	 * ->get(['foo', 'bar']);
	 * Will return 'val1'
	 * 
	 * ------------------------------
	 * ->get('bar.baz.qux');
	 * ->get(['bar.baz', 'qux']);
	 * Will return 'val2'
	 * 
	 * ------------------------------
	 * ->get(['bar', 'baz.qux']);
	 * Will return 'val3'
	 * 
	 * ------------------------------
	 * ['bar', 'baz', 'qux']
	 * Will not match anything
	 * 
	 * 
	 * @param string|array $path
	 * @param string $default
	 * @return string|array
	 */
	public function get($path = null, $default = null)
	{
		// return all
		if (!$path) {
			return $this->data;
		}

		if (is_array($path)) {
			// path array provided
			// easy, keys with a dot are provided as-is in the array
			$walker = $this->data;
			while (count($path) > 0) {
				$part = array_shift($path);
				if (is_array($walker) && isset($walker[$part])) {
					// go deeper
					$walker = $walker[$part];
				} else {
					// not here, done
					return $default;
				}
			}
			// return what we ended up with
			return $walker;
		} else {
			// dotted path provided
			// hard, because data keys can also contain dots
			$walker = $this->data;
			$base = '';
			$separator = '';
			$done = false;


			while (($parts = explode('.', $path, 2)) && !$done) {
				// build a base key to look for in the current walker
				$base .= $separator . $parts[0];
				$separator = '.';

				if (is_array($walker) && isset($walker[$base])) {
					// base key found in current walker: go deeper
					$walker = $walker[$base];
					// reset the base key
					$separator = '';
					$base = '';
				}

				if (isset($parts[1])) {
					// more parts to find
					if (is_array($walker) && isset($walker[$parts[1]])) {
						// the rest of the path is entirely matched
						$walker = $walker[$parts[1]];
						// we are done: set base to '' to generate a succesful result
						$base = '';
						$done = true;
					} else {
						// part not found directly: start again with this part as the new path
						$path = $parts[1];
					}
				} else {
					// no more parts to find
					$done = true;
				}
			}
			if ($base === '') {
				// base is empty: path was found
				return $walker;
			} else {
				// base is not empty: path was not found
				return $default;
			}
		}
	}
}
