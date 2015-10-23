<?php

namespace Base;

class ApplicationException extends \Exception {}

class Base
{

	/**
	 * Container instance
	 * @var \Base\Container 
	 */
	protected $container = null;


	/**
	 * Bootstrap the application
	 * - PHP settings
	 * - Configure autoloader
	 * - Create container
	 * - Register Base providers
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		// PHP settigns
		date_default_timezone_set($config['base']['php']['timezone']);
		setlocale(LC_ALL, $config['base']['php']['locale']);
		ini_set('display_errors', $config['base']['php']['display_errors']);
		error_reporting($config['base']['php']['error_reporting']);


		// configure the autoloader
		if ($config['base']['loader']['autoload']) {
			require( $config['base']['loader']['file'] );
			$loader = new $config['base']['loader']['class']();
			$loader->prefix($config['base']['loader']['prefix']);
			$loader->auto();
		}
		// create the container
		$this->container = new $config['base']['container']['class']();

		// register core providers
		call_user_func_array([$config['base']['provider']['class'], 'register'], [$this->container, $config]);
	}


	/**
	 * Regsiter providers on the container
	 * Each provider should have a function 'register' that accepts a container instance
	 * @param array $providers
	 */
	public function providers(array $providers)
	{
		
		foreach ($providers as $provider) {
			call_user_func([$provider, 'register'], $this->container);
		}
	}


	/**
	 * Get the container
	 * @return \Base\Container
	 */
	public function container()
	{
		return $this->container;
	}


	/**
	 * Execute the App for a http request
	 * @return \Base\Base
	 */
	public function http()
	{
		
		// get function from the router
		// this function is defined with the route
		// if there is no function defined, a controller will be used
		$function = $this->container->get('base.router')->execute();

		// get request & response
		$request = $this->container->get('base.http.request');
		$response = $this->container->get('base.http.response');

		if (is_object($function) && method_exists($function, '__invoke')) {
			// execute closure
			$body = $function($request, $response);

			// use it as response body if anything was returned
			if ($body !== null) {
				$response->body($body);
			}
		} else {
			// create controller
			if (!$request->get('controller')) {
				throw new ApplicationException('No controller set in request params');
			}
			$controller = $this->container->get('base.controller', $request->get('controller'));

			// get action
			if (!$request->get('action')) {
				throw new ApplicationException('No action set in request params');
			}
			$action = $request->get('action');

			// call action
			if (!is_callable([$controller, $action])) {
				throw new ApplicationException('Action "' . $action . '" no found on controller "' . $request->get('controller') . '"');
			}

			if (!is_callable([$controller, 'filter']) || $controller->filter($request, $response) !== false) {
				// filter didnt return false: continue
				if (!is_callable([$controller, 'before']) || $controller->before($request, $response) !== false) {
					// before didnt return false: continue
					// get the returnvalue of the action
					$body = $controller->{$action}($request, $response);
					// use it as response body if anything was returned
					if ($body !== null) {
						$response->body($body);
					}
					// call after method
					if (is_callable([$controller, 'after'])) {
						$controller->after($request, $response);
					}
				}
			}
		}
		return $this;
	}


	/**
	 * Send response header and body
	 */
	public function respond()
	{
		$this->container->get('base.http.response')->send();
	}
	
	
	/**
	 * Execute the App for a cli command
	 * @return \Base\Command
	 */
	public function cli()
	{
		$cli = $this->container->get('base.cli');
		$command = $this->container->get('base.command', $cli->command());
		return $command($cli->params());
	}

}
