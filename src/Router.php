<?php

namespace Base;

use \Base\HTTP\Request as Request;

class RouterException extends \Exception{}

class Router
{
	/**
	 * Request object
	 * @var \Base\HTTP\Request 
	 */
	protected $request = null;
	
	/**
	 * Mapped routes
	 * @var array 
	 */
	protected $routes = [];
	
	/**
	 * Default route to use
	 * @var type 
	 */
	protected $route = null;
	
	/**
	 * Parser callables
	 * @var array 
	 */
	protected $parse = [];
	
	/**
	 * Builder callables
	 * @var array 
	 */
	protected $build = [];
	
	/**
	 * Extracted params from a matching uri after parsing
	 * @var array 
	 */
	protected $params = [];
	
	/**
	 * Params to retain from current request into uri
	 * @var array 
	 */
	protected $retain = [];
	
	/**
	 * Cached compiled uris with placeholders for params
	 * @var array 
	 */
	protected $compiled = [];

	
	/**
	 * Cosntructor
	 * @param \Base\HTTP\Request $request
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}


	/**
	 * Add a route or set the default route
	 * 
	 * $name can be used to target a specific route when buildign an url
	 * $patter is the pattern to match
	 * $options can container the following
	 *	'conditions': an array with regexes for params in the uri
	 *	'defaults': an array with default values for missing optional params in the uri
	 *	'parse': a closure that will be run after an uri is matched. $params and $request are arguments. Return false move on to next route
	 *  'build': a closure that will be run before an uri is built from params. $params is the argument
	 * 
	 * $function is a callback funtion that is provided with a request and response. 
	 * If not $function is provided, a controller/action combination is used, based on  'controller' and 'action' params
	 * 
	 * @param string $name
	 * @param string $pattern
	 * @param array $options
	 * @param Callable $function
	 * @return \Base\Router
	 */
	public function route($name, $pattern = null, array $options = [], \Closure $function = null)
	{
		if ($pattern === null) {
			// dont add a route, instead, set the current default route to use
			$this->route = $name;
		} else {
			// add/replace the route
			$this->routes[$name] = array_merge(
				[
					'pattern' => $pattern,
					'function' => $function,
					'conditions' => [],
					'defaults' => [],
					'parse' => null,
					'build' => null,
				], $options
			);
		}
		return $this;
	}


	/**
	 * Add a parses callback
	 * @param Callable $parse
	 * @return \Base\Router
	 */
	public function parse($parse)
	{
		$this->parse[] = $parse;
		return $this;
	}


	/**
	 * Add a builder callback
	 * @param Callable $build
	 * @return \Base\Router
	 */
	public function build($build)
	{
		$this->build[] = $build;
		return $this;
	}


	/**
	 * Set which params for the current request to retain
	 * @param array $retain
	 * @return \Base\Router
	 */
	public function retain(array $retain)
	{
		$this->retain = array_merge($this->retain, $retain);
		return $this;
	}


	/**
	 * Execute the router
	 * @return boolean|\Closure
	 */
	public function execute()
	{
		foreach ($this->routes as $name => $route) {
			// check if a route matches: it will return params
			if ($params = $this->params($route['pattern'], $this->request->uri, $route['conditions'], $route['defaults'])) {
				// filter params
				if (is_object($route['parse']) && method_exists($route['parse'], '__invoke')) {
					$params = $route['parse']($params, $this->request);
					if ($params === false) {
						continue;
					}
				}

				// run parse callbacks
				foreach ($this->parse as $parse) {
					$params = $parse($params, $this->request);
					if ($params === false) {
						continue;
					}
				}

				// still here: this is the route to use
				// set the params in the request so they can be used later
				$this->request->params($params);

				// return the command for this route
				return $route['function'];
			}
		}
		return false;
	}


	/**
	 * Add a predefined url
	 * @param string|array $name
	 * @param string $url
	 */
	public function set($nameOrUrls, $url = null)
	{
		if (is_array($nameOrUrls)) {
			$this->urls = array_merge($this->urls, $nameOrUrls);
		} else {
			$this->urls[$nameOrUrls] = $url;
		}
		return $this;
	}


	/**
	 * Create url
	 * 
	 * Pass null to get the current url
	 * Pass true to get the current url with querystring
	 * Pass a string that was defined with 'set' to get a predefined url
	 * Pass '/' to get the current url base
	 * Pass Foo@bar:baz to build an url with controller Foo, action bar, id baz
	 * Pass Foo@bar:baz, [] to build with additional params
	 * Pass [] to build from params
	 * Pass Foo@bar:baz, route to build with route
	 * Pass Foo@bar:baz, [], route to build with route and params
	 * Pass [], route to build from params with route
	 * 
	 * @return string
	 * @throws \Base\RouterException
	 */
	public function url()
	{
		$args = func_get_args();

		if (!isset($args[0])) {
			// return current url
			return $this->request->url(false);
		} elseif ($args[0] === true) {
			// return current url with qs
			return $this->request->url(true);
		} elseif (is_string($args[0]) && isset($this->urls[$args[0]])) {
			// get a predefined url
			return $this->urls[$args[0]];
		} elseif ($args[0] === '/') {
			// return current url base
			return $this->request->base;
		} elseif (is_string($args[0])) {
			// extract Controller / action / id from string
			$parts = [];
			preg_match('/([^\@]*)\@?([^\:]*)\:*(.*)/', $args[0], $parts);
			$params = [
				'controller' => $parts[1],
				'action' => $parts[2],
				'id' => $parts[3],
			];
		} elseif (is_array($args[0])) {
			// params given
			$params = $args[0];
		} else {
			// no extra params
			$params = [];
		}

		if (isset($args[1]) && is_array($args[1])) {
			$params = array_merge($params, $args[1]);
			if (isset($args[2])) {
				$route = $args[2];
			} else {
				$route = null;
			}
		} elseif (isset($args[1]) && is_string($args[1])) {
			$route = $args[1];
		} else {
			$route = null;
		}

		// get route
		if ($route === null) {
			if ($this->route === null) {
				throw new RouterException('trying to build url without route');
			} else {
				$route = $this->route;
			}
		}
		if (!isset($this->routes[$route])) {
			throw new RouterException('provided route ' . $route . ' is not defined');
		}

		// retain values
		$currentParams = $this->request->params();
		foreach ($this->retain as $param) {
			if (isset($currentParams[$param]) && (!isset($params[$param]) || $params[$param] === '')) {
				$params[$param] = $currentParams[$param];
			}
		}
		// call router builders on params
		foreach ($this->build as $build) {
			$params = $build($params);
		}

		// call route builders on params
		if (is_object($this->routes[$route]['build']) && method_exists($this->routes[$route]['build'], '__invoke')) {
			$params = $this->routes[$route]['build']($params);
		}

		// create uri with route and params
		$uri = $this->uri($this->routes[$route]['pattern'], $params);

		// get base url
		if (isset($params['base'])) {
			$base = $params['base'];
		} else {
			$request = $this->request->data();
			$protocol = isset($params['protocol']) ? $params['protocol'] : $request['protocol'];
			$domain = isset($params['domain']) ? $params['domain'] : $request['domain'];
			$port = isset($params['port']) ? $params['port'] : $request['port'];
			$path = isset($params['path']) ? $params['path'] : $request['path'];
			$base = ($protocol !== '' ? $protocol . '://' : '//') . $domain . ($port != '80' ? ':' . $port : '') . '/' . $path;
		}
		return rtrim($base, '/') . '/' . $uri;
	}


	/**
	 * Create uri
	 * @param string $pattern
	 * @param array $params
	 * @throws \Base\RouterException
	 * @return string
	 */
	public function uri($pattern, array $params = [])
	{
		// create a key based on the uri pattern and the available params
		$key = $pattern . '|' . implode('|', array_keys(array_filter($params)));

		if (!isset($this->compiled[$key])) {
			// replace all params :name in the pattern with {{name}} tokens
			// Do this only if the param is given in params
			// If not, place a {{!}} token
			while (preg_match('#:(\w+)#', $pattern, $match)) {
				list($group, $param) = $match;
				if (isset($params[$param]) && $params[$param] !== '') {
					// param is available: place a token in the uri
					$token = '{{' . $param . '}}';
					$pattern = str_replace($group, '{{' . $param . '}}', $pattern);
				} else {
					// param is not available: mark it for deletion
					$pattern = str_replace($group, '{{!}}', $pattern);
				}
			}
			// Optional (bracketed) parts. from inside to outside
			// Remove entire match when there is a '{{!}}', in the match
			// Otherwise, only remove brackets
			// Repeat until all brackets are gone.
			while (preg_match('#\(([^()]*)\)#', $pattern, $match)) {
				list($group, $inner) = $match;
				if (strpos($inner, '{{!}}') !== false) {
					// remove entire match
					$pattern = str_replace($group, '', $pattern);
				} else {
					// remove brackets
					$pattern = str_replace($group, $inner, $pattern);
				}
			}

			// there is still an unavailable param in the compiled uri
			// This means the param was not optional, but also not available
			if (strpos($pattern, '{{!}}') !== false) {
				throw new RouterException('Missing param in compiled uri: ' . $pattern);
			}

			// store the compiled uri
			// so we can do a simple str_replace the next time this combination of
			// uri / available params comes along
			$this->compiled[$key] = $pattern;
		}

		// We have a colpiled pattern with {{param}} tokens now.
		// replace all the tokens with actual params
		// TODO: optimize this
		$uri = $this->compiled[$key];
		foreach ($params as $key => $value) {
			$uri = str_replace('{{' . $key . '}}', $value, $uri);
		}
		return $uri;
	}


	/**
	 * Get params
	 * @param string $pattern
	 * @param string $uri
	 * @param array $conditions
	 * @param array $defaults
	 * @return boolean|string
	 */
	public function params($pattern, $uri, array $conditions = [], array $defaults = [])
	{
		// create regex
		$regex = str_replace('?', '\?', $pattern);
		// make all ()'s optional by adding a ? to the ()
		$regex = str_replace(')', ')?', $regex);
		// extract all :words and save them
		// replace :var with 
		// (?P<var>[^/]+) or (?P<var>condition)
		$vars = [];
		$regex = preg_replace_callback('#:(\w+)#', function($match) use(& $vars, $conditions) {
			// save param in class
			$vars[] = $match[1];
			if (isset($conditions[$match[1]])) {
				// add conditions if they are given
				return '(?P<' . $match[1] . '>' . $conditions[$match[1]] . ')';
			} else {
				// else the condition is 'no slash, no question mark'
				return '(?P<' . $match[1] . '>[^/\?]+)';
			}
		}, $regex);

		// make a trailing slash optional
		if (substr($regex, -1) === '/') {
			$regex .= '?';
		}
		// wrap it in regex markers
		$regex = '#^' . $regex . '$#';
		// found matches
		$matches = [];
		// match it
		if (preg_match($regex, $uri, $matches)) {
			// save values and create array with params to be returned to the router
			$params = [];
			foreach ($vars as $var) {
				if (isset($matches[$var]) && $matches[$var] !== '') {
					// add it to the to-be returned params
					$params[$var] = $matches[$var];
				} elseif (isset($defaults[$var])) {
					// add default to the to-be returned params
					$params[$var] = $defaults[$var];
				}
			}
			// additional defaults
			foreach ($defaults as $var => $val) {
				if (!isset($params[$var])) {
					$params[$var] = $val;
				}
			}
			// return found params
			return $params;
		} else {
			// not matched: return false
			return false;
		}
	}
}
