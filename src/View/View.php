<?php

namespace \Base\View;

class View
{
	/**
	 * Classname, so we can differentiate between 'View' and 'Fetch'
	 * When using 'View' the output will be echo'd directly
	 * @var string 
	 */
	protected static $className = 'View';
	
	/**
	 * The render engine
	 * @var type 
	 */
	protected static $engine = null;
	
	
	public static function engine($engine)
	{
	}
	
	
	/**
	 * Extend a different view file
	 * @param string $file
	 */
	public static function extend($file)
	{
		// get current rendering view
		$view = static::$map[static::$rendering]['part'];

		// create new view with current rendering data
		$parent = static::$engine->view($file, $part->data());

		// add its id to the currently rendering objct as 'parent' property
		static::$map[static::$rendering]['parent'] = $parent->id();
	}


	/**
	 * Shorthand for start(); $content; end();
	 * @param String $name
	 * @param String $content
	 */
	public static function block($name, $content = '')
	{
		static::start($name);
		echo $content;
		static::end();
	}


	/**
	 * Start the rendering of a contentblock
	 * @param String $name
	 */
	public static function start($name)
	{
		// add info to the block stack
		static::$stack[] = [
			'rendering' => static::$rendering,
			'name' => $name
		];
		// start buffering
		ob_start();
	}


	/**
	 * Complete the rendering of a contentblock
	 */
	public static function end()
	{
		// end buffering get the block
		$output = ob_get_contents();
		ob_end_clean();

		// get the block info
		$block = array_pop(static::$stack);

		// get the part in which the block was started and ended
		$part = static::$map[$block['rendering']]['part'];

		// set the output as a block in the part
		// but only if it wasn't already set by a child part
		if (!$part->block($block['name'])) {
			$part->block($block['name'], $output);
		}

		if (count(static::$stack) > 0) {
			// stack is not empty: this is a nested block
			// the output should end up here
			echo $part->block($block['name']);
		} else {
			if (static::$map[$block['rendering']]['parent'] !== null) {
				// stack is empty, but we are extending another part
				// set the output in the parent part
				$parent = static::$map[static::$map[$block['rendering']]['parent']]['part'];
				$parent->block($block['name'], $output);
			} else {
				// stack is empty, we are not extending
				// the output should end up here
				echo $part->block($block['name']);
			}
		}
	}


	/**
	 * Add asset to specific asset group
	 * @param string $group
	 * @param string $html
	 * @param bool $duplicate push to assets regardless of element existing
	 */
	public static function asset($group, $html = '', $duplicate = false)
	{
		// create group array
		if (!isset(static::$assets[$group])) {
			static::$assets[$group] = [];
		}
		// add html to group array
		if (!in_array($html, static::$assets[$group]) || $duplicate == true) {
			static::$assets[$group][] = $html;
		}
	}


	/**
	 * Get accumulated assets, provide no group for all assets
	 * @param string $group
	 * @return array
	 */
	public static function assets($group = null)
	{
		if ($group === null) {
			return static::$assets;
		} elseif (isset(static::$assets[$group])) {
			return static::$assets[$group];
		} else {
			return [];
		}
	}


	public static function html($string)
	{
		if (static::$className === static::$fetchClass) {
			return htmlspecialchars($string);
		} else {
			echo htmlspecialchars($string);
		}
	}


	public static function attribute($string)
	{
		if (static::$className === static::$fetchClass) {
			return htmlspecialchars($string, ENT_QUOTES);
		} else {
			echo htmlspecialchars($string, ENT_QUOTES);
		}
	}


	public static function part($file, $data = [])
	{
		if (static::$className === static::$fetchClass) {
			return static::$instance->make($file, $data)->render();
		} else {
			echo static::$instance->make($file, $data)->render();
		}
	}


	public static function shared($name)
	{
		if (isset(static::$shared[$name])) {
			if (static::$className === static::$fetchClass) {
				return static::$shared[$name];
			} else {
				echo static::$shared[$name];
			}
		} else {
			return null;
		}
	}


	public static function get()
	{
		if (static::$container !== null) {
			$arg = func_get_args();
			$name = array_shift($arg);
			if (count($arg) === 1) {
				$arg = $arg[0];
			}
			$result = static::$container->get($name, $arg);
			if (!is_scalar($result) || static::$className === static::$fetchClass) {
				return $result;
			} else {
				echo $result;
			}
		} else {
			return null;
		}
	}


	public static function __callStatic($name, $args)
	{
		if (!isset(static::$helpers[$name]) && static::$container !== null) {
			// helper not set, but container is present. Get helper from under the helper key
			static::$helpers[$name] = static::$container->get(static::$helperPrefix . $name);
		}

		if (isset(static::$helpers[$name])) {
			$helper = static::$helpers[$name];
			if (is_object($helper) && method_exists($helper, '__invoke')) {
				switch (count($args)) {
					case 0:
						$result = $helper();
						break;
					case 1:
						$result = $helper($args[0]);
						break;
					case 2:
						$result = $helper($args[0], $args[1]);
						break;
					case 3:
						$result = $helper($args[0], $args[1], $args[2]);
						break;
					default:
						$result = call_user_func_array($helper, $args);
				}
				// helper is a closue: call it and return the result
				if (static::$className === static::$fetchClass || !is_scalar($result)) {
					return $result;
				} else {
					echo $result;
				}
			} elseif (is_scalar($helper)) {
				// scalars or ok to output
				if (static::$className === static::$fetchClass) {
					return $helper;
				} else {
					echo $helper;
				}
			} else {
				// helper is something else (probably an object) just return it
				return $helper;
			}
		} else {
			return null;
		}
	}
}


class Fetch extends View
{
	// class name
	protected static $className = 'Fetch';
}