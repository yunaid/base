<?php

namespace Base;

class CommandException extends \Exception{}

class Command
{
	// Container instance
	protected $container = null;

	/**
	 * Cosntructor
	 * @param \Base\Container $container
	 */
	public function __construct(\Base\Container $container)
	{
		$this->container = $container;
	}
	
	
	/**
	 * Call the class
	 * @return type
	 */
	public function __invoke()
	{
		return $this->execute();
	}
	
	
	protected function execute(){}
	
	
	/**
	 * Shortcut
	 * use the container to get an object
	 * @return mixed
	 */
	protected function get($name)
	{
		$args = func_get_args();
		array_shift($args);
		switch(count($args)) {
			case 0:
				return $this->container->get($name);
			case 1:
				return $this->container->get($name, $args[0]);
			case 2:
				return $this->container->get($name, $args[0], $args[1]);
			case 3:
				return $this->container->get($name, $args[0], $args[1], $args[2]);
			default:
				array_unshift($name, $args);
				return call_user_func_array([$this->container, 'get'], $args);
		}
	}
}