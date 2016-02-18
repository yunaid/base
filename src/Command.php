<?php

namespace Base;

use \Base\Container;
use \Base\CLI;

class CommandException extends \Exception{}

class Command
{
	/**
	 * Container instance
	 * @var \Base\Container 
	 */
	protected $container = null;

	
	/**
	 * Constructor
	 * @param \Base\Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}
	
	
	/**
	 * The actual logic
	 */
	protected function execute(CLI $cli)
	{
	}

}