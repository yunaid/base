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
		
		$config = static::config($config);
		
		$container->share([
			'base.cache',
			'base.cache.adapter',
			'base.cli',
			'base.config',
			'base.console',
			'base.cookie',
			'base.database',
			'base.encryption',
			'base.exception.handler',
			'base.http.request',
			'base.http.response',
			'base.log',
			'base.loader',
			'base.orm.schema',
			'base.profile',
			'base.router',
			'base.session',
			'base.view.engine',
		])->group([
			'base.cache' => key($config['cache']),
			'base.cache.adapter' => key($config['cache.adapter']),
			'base.database' => key($config['database']),
			'base.log' => key($config['log']),
			'base.session' => key($config['session']),
		])->set([
			'base.arr' => function($container, $data = []) {
				return new \Base\Arr($data);
			},
			'base.cache' => function($container, $name, $adapter = null)  {
				$config = $container->get('base.config')->get(['cache', $name ], null);
				if($config) {
					$adapter = $container->get('base.cache.adapter', $config['adapter']);
				} else {
					$adapter = $container->get('base.cache.adapter');
				}
				return new \Base\Cache($name, $adapter, $config);
			},
			'base.cache.adapter' => function($container, $name) {
				$config = $container->get('base.config')->get(['cache.adapter', $name]);
				return new $config['class'](
					$config['params']
				);
			},
			'base.cli' => function($container) {
				return new \Base\CLI($_SERVER['argv']);
			}, 
			'base.command' => function($container, $class) {
				return new $class($container);
			},
			'base.config' => new \Base\Arr($config),
			'base.console' => function($container) {
				return new \Base\Console(
					$container->get('base.profile'), 
					$container->get('base.http.request'), 
					$container->get('base.router')
				);
			},
			'base.controller' => function($container, $class) {
				return new $class($container);
			},
			'base.cookie' => function($container) {
				return new \Base\Cookie(
					$_COOKIE, 
					$container->get('base.http.request'), 
					$container->get('base.config')->get('cookie.salt')
				);
			},
			'base.database' => function($container, $name) {
				$config = $container->get('base.config')->get(['database', $name]);
				$connection = new $config['class'](
					$config['params'], 
					$container->get('base.profile')
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
			'base.encryption' => function($container) {
				return new \Base\Encryption(
					$container->get('base.config')->get('encryption')
				);
			},
			'base.exception.handler' => function($container) {
				return new \Base\Exception\Handler();
			},
			'base.form' => function($container, $class) {
				return new $class(
					$container->get('base.validation'), 
					$container->get('base.http.request'),
					function($key, $type, $params, $form){
						return new \Base\Form\Element($key, $type, $params, $form);
					}
				);
			},
			'base.http.request' => function($container) {
				return new \Base\HTTP\Request(
					$_SERVER, 
					$_GET, 
					$container->get('base.arr', $_POST)
				);
			},
			'base.http.response' => function($container) {
				return new \Base\HTTP\Response();
			},
			'base.loader' => function($container) {
				return new \Base\Loader();
			},
			'base.log' => function($container, $name) {
				$config = $container->get('base.config')->get(['log', $name]);
				return new $config['class'](
					$config['params']
				);
			},
			'base.model' => function($container, $name, $class) {
				return new $class(
					$name,
					$container->get('base.orm.schema'),
					function($name) use ($container){
						return $container->get('base.orm.mapper', $name);
					},
					function($name) use ($container){
						return $container->get('base.orm.entity', $name);
					},
					$container->get('base.database',  $container->get('base.orm.schema')->get($name)['database'])
				);
			},
			'base.orm.entity' => function($container, $name) {
				$schema = $container->get('base.orm.schema');
				return new \Base\ORM\Entity(
					$name, 
					$schema, 
					$container->get('base.database',  $schema->get($name)['database']),
					$container->get('base.orm.mapper', $name) 
				);
			},
			'base.orm.mapper' => function($container, $name = null) {
				$schema = $container->get('base.orm.schema');
				return new \Base\ORM\Mapper(
					$name, 
					$schema, 
					$container->get('base.database', $schema->get($name)['database']),
					function($data = [], $prefix = '', $mapper = null, $columns = [], $relations = [], $methods = []){
						return new \Base\ORM\Record($data, $prefix, $mapper, $columns, $relations, $methods);
					},
					function($name) use ($container){
						return $container->get('base.orm.mapper', $name);
					}
				);
			},
			'base.orm.schema' => function($container) {
				return new \Base\ORM\Schema(
					$container->get('base.loader')->finder(
						$container->get('base.config')->get(['schema','path'])
					)
				);
			},
			'base.profile' => function($container) {
				return new \Base\Profile(START);
			},
			'base.router' => function($container) {
				return new \Base\Router(
					$container->get('base.http.request')
				);
			},
			'base.session' => function($container, $name) {
				$config = $container->get('base.config')->get( ['session', $name] );
				switch ($name) {
					case 'database':
						$session = new $config['class'](
							$config['params'],
							$container->get('base.database', $config['database']),
							$container->get('base.cookie'),
							$container->get('base.encryption')
						);
						break;
					case 'native':
						$session = new $config['class'](
							$config['params'],
							$container->get('base.cookie'),
							$container->get('base.encryption')
						);
						break;
				}
				return $session;
			},
			'base.validation' => function($container) {
				return new \Base\Validation();
			},
			'base.view' => function($container, $file = null, $data = []) {
				if($file === null) {
					return $container->get('base.view.engine');
				} else {
					return $container->get('base.view.engine')->make($file, $data);
				}
			},
			'base.view.engine' => function($container) {
				$config = $container->get('base.config')->get('view');
				return \Base\View\Engine::instance(
					$container->get('base.loader')->finder($config['path']),
					function($engine, $file, $data = []) {
						return new \Base\View($engine, $file, $data);
					},
					$config['alias']
				);
			},
		]);
	}
	
	
	/**
	 * Merge supplied config with default one
	 * @param array $config
	 * @return array
	 */
	protected static function config(array $config = [])
	{
		return array_replace_recursive(
			[
				'base' => [
					'container' => [							# Container
						'class' => '\\Base\\Container'			// Container-class to use 
					],
					'provider' => [								# Provider
						'class' => '\\Base\\Provider\\Base'		// Provider class to use
					],
					'loader' => [								# Loader
						'file' => __DIR__ .'/../Loader.php',	// Location of the loader file
						'class' => '\\Base\\Loader',			// Loader class to use
						'autoload' => true,						// Use autoloader of Loader
						'prefix' => [],							 // Class prefixes
					],
					'php' => [									 # PHP settings
						'timezone' => 'Europe/Amsterdam',
						'locale' => 'nl_NL.utf-8',
						'display_errors' => 0,
						'error_reporting' => E_ALL | E_STRICT
					],
				],
				'cache' => [									# Cache groups
					'default' => [
						'adapter' => 'file',					// Choose one of the available adapters
						'active' => true,						// Actually use this group or not
						'lifetime' => 3600						// default lifetime
					],
				],
				'cache.adapter' => [							# Cache adapters
					'apc' => [									// APC adapter
						'class' => '\\Base\\Cache\\Apc',		// Class to use
						'params' => [],
					],
					'file' => [									// File Adapter
						'class' => '\\Base\\Cache\\File',		// Class to use
						'params' => [
							'path' => null,						// Path to the cache dir
						]
					],
					'memcache' => [								// Memcache adapter
						'class' => '\\Base\\Cache\\Memcached',	// Class to use
						'params' => [							// Settings
							'compression' => false,
							'servers' => [
								'local' => [
									'host' => 'localhost',
									'port' => 11211,
									'persistent' => false,
									'weight' => 1,
									'timeout' => 1,
									'retry' => 15,
									'status' => true,
								],
							],
							'instant_death' => true,
						]
					],
				],
				'cookie' => [									# Cookie 
					'salt' => '{cookiesalt}',					// Salt used for validating tampering
				],
				'database' => [
					'mysql' => [
						'class' => '\\Base\\Database\\Mysql',
						'params' => [
							'dsn' => 'mysql:host=[host];dbname=[database]',
							'username' => '[usr]',
							'password' => '[pwd]',
							'options' => [
								PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
							],
							'profile' => false
						],
					],
					'postgre' => [
						'class' => '\\Base\\Database\\Postgresql',
						'params' => [
							'dsn' => 'mysql:host=[host];dbname=[database]',
							'username' => '[usr]',
							'password' => '[pwd]',
							'options' => [
								PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
							],
							'profile' => false
						],
					],
					'sqlite' => [
						'class' => '\\Base\\Database\\Sqlite',
						'params' => [
							'dsn' => 'mysql:host=[host];dbname=[database]',
							'username' => '[usr]',
							'password' => '[pwd]',
							'options' => [
								PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
							],
							'profile' => false
						],
					],
				],
				'encryption' => [
					'key' => '__key__',
					'mode' => MCRYPT_MODE_NOFB,
					'cipher' => MCRYPT_RIJNDAEL_128
				],
				'log' => [
					'file' => [
						'class' => '\\Base\\Log\\File',
						'params' => [
							'path' => null
						]
					]
				],
				'schema' => [
					'path' => [],
				],
				'session' => [
					'native' => [
						'class' => '\\Base\\Session\\Native',
						'params' => [
							'lifetime' => 0,
							'name' => 'session_1',
							'encrypt' => true,
							'path' => null
						]
					],
					'database' => [
						'class' => '\\Base\\Session\\Database',
						'database' => 'mysql',
						'params' => [
							'lifetime' => 0,
							'name' => 'session_2',
							'encrypt' => true,
							'table' => 'session',
							'columns' => [
								'id' => 'id',
								'updated' => 'updated',
								'data' => 'data'
							],
							'gc' => 1000
						]
					],
				],
				'view' => [
					'path' => [],
					'alias' => [
						'view' => 'view',
						'fetch' => 'fetch',
					]
				],
			],
			$config
		);
	}
}