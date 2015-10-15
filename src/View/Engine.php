<?php

namespace Base\View;

class ViewEngineException extends \Exception{}

class Engine
{
	
	/**
	 * File finder
	 * @var \Closure 
	 */
	protected $finder;
	
	/**
	 * Closure to create new views
	 * @var \Closure 
	 */
	protected $viewFactory;
	

	public function __construct($finder, $viewFactory, $viewAlias = 'view', $fetchAlias = 'fetch')
	{
		$this->finder = $finder;
		$this->viewFactory = $viewFactory;
		
		// set view and fetch aliases
		class_alias('\\Base\\View\\View', $viewAlias);
		class_alias('\\Base\\View\\Fetch', $fetchAlias);
	}
	
	
	
	
	public function render(\Base\View $view)
	{
		// remember currently rendering id
		$previous = $this->rendering;

		// set the current rendering id
		$this->rendering = $view->id();

		// start rendering / collect blocks / extending
		$output = $this->capture($view->file(), $view->data());

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
	

		
	protected function capture($__file__, $__data__)
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
	
	
}





class __View
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
