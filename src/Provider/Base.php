<?php

namespace Base\Provider;

use \Base\Container as Container;

class Base
{
	/**
	 * Register base providers
	 * @param \Base\Container $container
	 * @param array $config
	 * @return void
	 */
	public static function register(Container $container, array $config = [])
	{
		if(!defined('START')){
			define('START', microtime(true));
		}
			
		$container->share([
			'cache',
			'cache.adapter',
			'cli',
			'config',
			'console',
			'cookie',
			'database',
			'encryption',
			'exception.handler',
			'log',
			'loader',
			'orm.schema',
			'profile',
			'http.request',
			'http.response',
			'router',
			'session',
			'view.factory',
			'view.url',
		])->group([
			'cache' => key($config['cache']),
			'cache.adapter' => key($config['cache.adapter']),
			'database' => key($config['database']),
			'log' => key($config['log']),
			'session' => key($config['session']),
		])->set([
			'arr' => function($container, $data = []) {
				return new \Base\Arr($data);
			},
			'cache' => function($container, $name, $adapter = null)  {
				$config = $container->get('config')->get(['cache', $name ], null);
				if($config) {
					$adapter = $container->get('cache.adapter', $config['adapter']);
				} else {
					$adapter = $container->get('cache.adapter');
				}
				return new \Base\Cache($name, $adapter, $config);
			},
			'cache.adapter' => function($container, $name) {
				$config = $container->get('config')->get(['cache.adapter', $name]);
				return new $config['class'](
					$config['params']
				);
			},
			'cli' => function($container) {
				return new \Base\CLI($_SERVER['argv']);
			}, 
			'command' => function($container, $class) {
				return new $class($container);
			},
			'config' => new \Base\Arr($config),
			'console' => function($container) {
				return new \Base\Console(
					$container->get('profile'), 
					$container->get('http.request'), 
					$container->get('router')
				);
			},
			'controller' => function($container, $class) {
				return new $class($container);
			},
			'cookie' => function($container) {
				return new \Base\Cookie(
					$_COOKIE, 
					$container->get('http.request'), 
					$container->get('config')->get('cookie.salt')
				);
			},
			'database' => function($container, $name) {
				$config = $container->get('config')->get(['database', $name]);
				$connection = new $config['class'](
					$config['params'], 
					$container->get('profile')
				);
				return new \Base\Database(
					function($type) use ($connection){
						return new \Base\Database\Query($connection, $type);
					},
					function($expression) {
						return new \Base\Database\Raw($expression);
					}
				);
			},
			'encryption' => function($container) {
				return new \Base\Encryption(
					$container->get('config')->get('encryption')
				);
			},
			'exception.handler' => function($container) {
				return new \Base\Exception\Handler();
			},
			'form' => function($container, $class) {
				return new $class(
					$container->get('validation'), 
					$container->get('http.request'),
					function($key, $type, $params, $form){
						return new \Base\Form\Element($key, $type, $params, $form);
					}
				);
			},
			'http.request' => function($container) {
				return new \Base\HTTP\Request(
					$_SERVER, 
					$_GET, 
					$container->get('arr', $_POST)
				);
			},
			'http.response' => function($container) {
				return new \Base\HTTP\Response();
			},
			'loader' => function($container) {
				return new \Base\Loader();
			},
			'log' => function($container, $name) {
				$config = $container->get('config')->get(['log', $name]);
				return new $config['class'](
					$config['params']
				);
			},
			'model' => function($container, $name, $class) {
				return new $class(
					$name,
					$container->get('orm.schema'),
					function($name) use ($container){
						return $container->get('orm.mapper', $name);
					},
					function($name) use ($container){
						return $container->get('orm.entity', $name);
					},
					$container->get('database',  $container->get('orm.schema')->get($name)['database'])
				);
			},
			'orm.entity' => function($container, $name) {
				$schema = $container->get('orm.schema');
				return new \Base\ORM\Entity(
					$name, 
					$schema, 
					$container->get('database',  $schema->get($name)['database']),
					$container->get('orm.mapper', $name) 
				);
			},
			'orm.mapper' => function($container, $name = null) {
				$schema = $container->get('orm.schema');
				return new \Base\ORM\Mapper(
					$name, 
					$schema, 
					$container->get('database', $schema->get($name)['database']),
					function($data = [], $prefix = '', $mapper = null, $columns = [], $relations = [], $methods = []){
						return new \Base\ORM\Record($data, $prefix, $mapper, $columns, $relations, $methods);
					},
					function($name) use ($container){
						return $container->get('orm.mapper', $name);
					}
				);
			},
			'orm.schema' => function($container) {
				return new \Base\ORM\Schema(
					$container->get('loader')->finder(
						$container->get('config')->get('schema.path')
					)
				);
			},
			'profile' => function($container) {
				return new \Base\Profile(START);
			},
			'router' => function($container) {
				return new \Base\Router(
					$container->get('http.request')
				);
			},
			'session' => function($container, $name) {
				$config = $container->get('config')->get( ['session', $name] );
				switch ($name) {
					case 'database':
						$session = new $config['class'](
							$config['params'],
							$container->get('database', $config['database']),
							$container->get('cookie'),
							$container->get('encryption')
						);
						break;
					case 'native':
						$session = new $config['class'](
							$config['params'],
							$container->get('cookie'),
							$container->get('encryption')
						);
						break;
				}
				return $session;
			},
			'validation' => function($container) {
				return new \Base\Validation();
			},
			'view' => function($container, $file = null, $data = []) {
				if ($file === null) {
					return $container->get('view.factory');
				} else {
					return $container->get('view.factory')->make($file, $data);
				}
			},
			'view.factory' => function($container) {
				$config = $container->get('config')->get('view');
				return new \Base\View(
					$container->get('loader')->finder($config['path']), 
					new \Base\View\Part(null, null, null, null), 
					$config['alias'], 
					$container, 
					'view.'
				);
			},
			'view.url' => function($container) {
				$router = $container->get('router');
				return function($arg1 = null, $arg2 = null, $arg3 = null) use ($router) {
					return $router->url($arg1, $arg2, $arg3);
				};
			},
		]);
	}
}