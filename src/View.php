<?php

namespace Base;

class ViewException extends \Exception{}

class View
{

	// class name
	protected static $className = 'View';
	// view instance
	protected static $instance = null;
	// file finder
	protected static $finder = null;
	// aliases used in view files
	protected static $alias = ['view' => 'view', 'fetch' => 'fetch'];
	// whether the class is already be aliased
	protected static $aliased = false;
	// name of the fetch class
	protected static $fetchClass = 'Fetch';
	// container to load helpers
	protected static $container = null;
	// continerprefix for helpers
	protected static $helperPrefix = 'helper.';
	// registered and found helpers
	protected static $helpers = [];
	// shared data accross all views
	protected static $shared = [];
	// counter used to generate unique ids
	protected static $count = 0;
	// map to keep track of extending parts
	protected static $map = [];
	// currently rendering part-id
	protected static $rendering = null;
	// stack of blocks called within eachother
	protected static $stack = [];
	// registered assets
	protected static $assets = [];
	// found files
	protected static $files = [];


	/**
	 * Extend a different view file
	 * @param String $file
	 */
	public static function extend($file)
	{
		// get current rendering view
		$part = static::$map[static::$rendering]['part'];

		// create new view with current rendering data
		$parent = static::$instance->make($file, $part->data());

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


	/**
	 * Capture view contents
	 * @param String $file
	 * @param Array $data
	 * @return String
	 */
	protected static function capture($__file__, $__data__)
	{
		// set view alias, but only once
		if (static::$aliased === false) {
			static::$aliased = true;
			class_alias('\\' . get_called_class(), static::$alias['view']);
			class_alias('\\Base\\Fetch', static::$alias['fetch']);
		}

		// find the file and store the contents. Do this only once
		if (!isset(static::$files[$__file__])) {
			if ($__path__ = static::$finder->__invoke($__file__)) {
				static::$files[$__file__] = [
					'path' => $__path__,
					'function' => null
				];
			} else {
				throw new ViewException('View \'' . $__file__ . '\' not found');
			}
		}


		// Start capture the view output
		if (static::$files[$__file__]['function'] === null) {
			extract($__data__, EXTR_OVERWRITE);

			ob_start();
			$result = include(static::$files[$__file__]['path']);
			$html = ob_get_clean();

			$function = 'extract($data, EXTR_OVERWRITE);';
			$function.= ' ?> ' . file_get_contents(static::$files[$__file__]['path']) . ' <?php ';
			static::$files[$__file__]['function'] = create_function('&$data', $function);
		} else {
			$function = static::$files[$__file__]['function'];

			ob_start();
			$function($__data__);
			$html = ob_get_clean();
		}
		// return the bufered result
		return $html;
	}

	// factory for view-parts
	protected $partFactory = null;


	/**
	 * Constructor
	 * @param String $file
	 * @param Array $data
	 */
	public function __construct($finder, $partFactory, $alias = ['view' => 'view', 'fetch' => 'fetch'], $container = null, $helperPrefix = 'helper.')
	{
		static::$finder = $finder;
		$this->partFactory = $partFactory;
		static::$alias = $alias;
		static::$container = $container;
		static::$helperPrefix = $helperPrefix;
		static::$instance = $this;
	}


	public function make($file, $data = [])
	{
		// create a unique id
		$id = ++static::$count;

		// create a part
		$part = $this->partFactory->make($file, $data, $id, $this);

		// add it to the map
		static::$map[$id] = [
			'part' => $part,
			'parent' => null
		];

		return $part;
	}


	/**
	 * Add Helper
	 * @param string $name
	 * @param \Closure $callable
	 */
	public function helper($name, $callable)
	{
		static::$helpers[$name] = $callable;
	}


	/**
	 * Set a global value
	 *
	 * @param   string  $key   variable name or an array of variables
	 * @param   mixed   $value  value
	 * @return  void
	 */
	public function share($keyOrValues, $value = null)
	{
		if (is_array($keyOrValues)) {
			foreach ($keyOrValues as $key => $value) {
				static::$shared[$key] = $value;
			}
		} else {
			static::$shared[$keyOrValues] = $value;
		}
	}


	/**
	 * Render the view
	 * Check if a call to extend was done
	 * if so: render that parent too 
	 */
	public function render($id, $file, $data = [], $blocks = [])
	{
		// remember currently rendering id
		$previous = static::$rendering;

		// set the current rendering id
		static::$rendering = $id;

		// start rendering / collect blocks / extending
		$output = static::capture($file, $data);

		// check if the view was extended from a parent
		if (static::$map[static::$rendering]['parent'] !== null) {
			// get parent part
			$parent = static::$map[static::$map[static::$rendering]['parent']]['part'];

			// overwrite blocks in parentview with blocks from this view
			foreach ($blocks as $name => $content) {
				$parent->block($name, $content);
			}

			// render the parent, this will set the rendinrg_id to the parent's
			// will go deeper as subsequent calls to View::extend are done
			$output = $parent->render();
		}

		// restore previous rendering id
		static::$rendering = $previous;

		return $output;
	}

}

class Fetch extends View
{

	// class name
	protected static $className = 'Fetch';

}
