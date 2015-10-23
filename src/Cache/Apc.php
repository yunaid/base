<?php

namespace Base\Cache;

class APC extends \Base\Cache\Adapter
{
	
	/**
	* {@inheritdoc}
	*/
	public function get($name, $default = null)
	{
		$data = \apc_fetch($name, $success);
		return $success ? $data : $default;
	}


	/**
	* {@inheritdoc}
	*/
	public function set($name, $value, $lifetime = 3600)
	{
		return \apc_store($name, $value, $lifetime);
	}


	/**
	* {@inheritdoc}
	*/
	public function delete($name = '*')
	{
		if (strpos($name, '*') !== strlen($name) - 1) {
			// delete single key when the last character is not a star
			\apc_delete($name);
		} else {
			// last character is a star
			$name = rtrim($name, '*');
			// loop through everything starting with the given string
			$names = new \APCIterator('user', '#^' . preg_quote($name) . '#', APC_ITER_KEY, 1);
			// ... and delete it
			foreach ($names as $found) {
				\apc_delete($found['key']);
			}
		}
	}
}
