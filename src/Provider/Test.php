<?php

namespace Base\Provider;

use \Base\Container as Container;

class Test
{
	/**
	 * Register base providers
	 * @param \Base\Container $container
	 * @param array $config
	 * @return void
	 */
	public static function register(Container $container)
	{
		$container->set('database', function($container){
			$connection = new \Base\Database\Connection();
			return new \Base\Database(
				function($type) use ($connection){
					return new \Base\Database\Query($connection, $type);
				},
				function($expression) {
					return new \Base\Database\Raw($expression);
				}
			);
		});
	}
}

