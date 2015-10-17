<?php

namespace Base\View;

class Engine
{
	
	/* ------------------------------
	 * Static part of the View Engine
	 * ------------------------------/
	

	/**
	 * Alias, so we can differentiate between 'View' and 'Fetch'
	 * When using 'View' the output will be echo'd directly
	 * @var string 
	 */
	protected static $alias = 'view';
	
	/**
	 * The alias against $alias will we tested
	 * @var string 
	 */
	protected static $fetch = 'fetch';
	
	/**
	 * The render engine instance
	 * @var \Base\View\Engine 
	 */
	protected static $engine = null;
	
	/**
	 * Closure to find files
	 * @var \Closure 
	 */
	protected static $finder = null;

	
	/**
	 * Container to load helpers
	 * @var \Base\Container 
	 */
	protected static $container = null;
	
	/**
	 * Prefix when fetching from container
	 * @var string 
	 */
	protected static $prefix = 'view.';
	
	/**
	 * registered and found helpers
	 * @var array 
	 */
	protected static $helpers = [];
	
	/**
	 * Shared data accross all views
	 * @var array 
	 */
	protected static $shared = [];
	
	/**
	 * map to keep track of extending views
	 * @var array 
	 */
	protected static $map = [];
	
	/**
	 * currently rendering view-id
	 * @var int 
	 */
	protected static $rendering = null;
	
	/**
	 * Stack of blocks called within eachother
	 * @var array 
	 */
	protected static $stack = [];
	
	/**
	 * Registered assets
	 * @var array 
	 */
	protected static $assets = [];
	
	/**
	 * Found files
	 * @var array 
	 */
	protected static $files = [];
	

	/**
	 * Create a single instance of the engine
	 * @param \Closure $finder
	 * @param \Closure $factory
	 * @param \Base\Container $container
	 * @param string $view
	 * @param string $fetch
	 * @param string $prefix
	 * @return \Base\View\Engine
	 */
	public static function instance($finder, $factory, $container = null, $view = 'view', $fetch = 'fetch', $prefix = 'view.')
	{
		if(static::$engine === null) {
			static::$engine = new self($finder, $factory, $container, $view, $fetch, $prefix);
		}
		return static::$engine;
	}
	
	
	/**
	 * Capture view contents
	 * @param string $file
	 * @param array $data
	 * @return string
	 */
	protected static function capture($__file__, $__data__)
	{
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
	
	
	/**
	 * Extend a different view file
	 * @param string $file
	 */
	public static function extend($file)
	{
		// get current rendering view
		$view = static::$map[static::$rendering]['view'];

		// create new view with current rendering data
		$parent = static::$engine->make($file, $view->data());

		// add its id to the currently rendering objct as 'parent' property
		static::$map[static::$rendering]['parent'] = $parent->id();
	}


	/**
	 * Shorthand for start(); $content; end();
	 * @param string $name
	 * @param string $content
	 */
	public static function block($name, $content = '')
	{
		static::start($name);
		echo $content;
		static::end();
	}


	/**
	 * Start the rendering of a contentblock
	 * @param string $name
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
		$part = static::$map[$block['rendering']]['view'];

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
				$parent = static::$map[static::$map[$block['rendering']]['parent']]['view'];
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

	
	/**
	 * get escaped html
	 * @param string $string
	 * @return string
	 */
	public static function html($string)
	{
		if (static::$alias === static::$fetch) {
			return htmlspecialchars($string);
		} else {
			echo htmlspecialchars($string);
		}
	}


	/**
	 * Get escaped html attribute
	 * @param string $string
	 * @return string
	 */
	public static function attribute($string)
	{
		if (static::$alias === static::$fetch) {
			return htmlspecialchars($string, ENT_QUOTES);
		} else {
			echo htmlspecialchars($string, ENT_QUOTES);
		}
	}


	/**
	 * Create and redner a subview
	 * @param string $file
	 * @param array $data
	 * @return string|void
	 */
	public static function view($file, array $data = [])
	{
		// create view
		$view = static::$engine->make($file, $data);
		
		// return / echo it
		if (static::$alias === static::$fetch) {
			return $view->render();
		} else {
			echo $view->render();
		}
	}


	/**
	 * Get a shared variable
	 * @param string $name
	 * @return mixed
	 */
	public static function shared($name)
	{
		if (isset(static::$shared[$name])) {
			if (static::$alias === static::$fetch) {
				return static::$shared[$name];
			} else {
				echo static::$shared[$name];
			}
		} else {
			return null;
		}
	}

	
	/**
	 * Get something from the container
	 * @return mixed
	 */
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

	
	/**
	 * Use a helper
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public static function __callStatic($name, array $args)
	{
		if (!isset(static::$helpers[$name]) && static::$container !== null) {
			// helper not set, but container is present. Get helper from under the helper key
			static::$helpers[$name] = static::$container->get(static::$prefix . $name);
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
				if (static::$alias === static::$fetch || !is_scalar($result)) {
					return $result;
				} else {
					echo $result;
				}
			} elseif (is_scalar($helper)) {
				// scalars or ok to output
				if (static::$alias === static::$fetch) {
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
	
	


	
	/* ------------------------------
	 * Object part of the View Engine
	 * ------------------------------*/
	
	/**
	 * Factory for creating new views
	 * @var \Closure 
	 */
	protected $factory;

	/**
	 * Protected Constructor
	 * @param \Closure $finder
	 * @param \Closure $factory
	 * @param \Base\Container $container
	 * @param string $view
	 * @param string $fetch
	 * @param string $prefix
	 */
	protected function __construct($finder, $factory, $container, $view, $fetch, $prefix)
	{
		// set view and fetch aliases
		class_alias('\\Base\\View\\Engine', $view);
		class_alias('\\Base\\View\\Fetch', $fetch);
		
		$this->factory = $factory;
		
		// set in static
		static::$finder = $finder;
		static::$container = $container;
		static::$prefix = $prefix;
	}
	
	
	/**
	 * Make a new view
	 * @param string $file
	 * @param array $data
	 * @return \Base\View
	 */
	public function make($file, array $data = [])
	{
		// create a view
		$view = $this->factory->__invoke($this, $file, $data);

		// add it to the map
		static::$map[$view->id()] = [
			'view' => $view,
			'parent' => null
		];

		return $view;
	}
	
	
	/**
	 * Set a global value or values
	 * @param string|array $keyOrValues
	 * @param mixed $value
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
	 * Render a view
	 * @param int $id
	 * @param string $file
	 * @param array $data
	 * @param array $blocks
	 * @return string
	 */
	public function render($id, $file, array $data = [], array $blocks = [])
	{
		// remember currently rendering id
		$rendering = static::$rendering;

		// set the current rendering id
		static::$rendering = $id;

		// start rendering / collect blocks / extending
		$output = static::capture($file, $data);

		// check if the view was extended from a parent
		if (static::$map[static::$rendering]['parent'] !== null) {
			// get parent part
			$parent = static::$map[static::$map[static::$rendering]['parent']]['view'];

			// overwrite blocks in parentview with blocks from this view
			foreach ($blocks as $name => $block) {
				$parent->block($name, $block);
			}

			// render the parent, this will set the rendinrg_id to the parent's
			// will go deeper as subsequent calls to View::extend are done
			$output = $parent->render();
		}

		// restore previous rendering id
		static::$rendering = $rendering;

		// done
		return $output;
	}
}