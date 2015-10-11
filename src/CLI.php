<?php

namespace Base;

class CLI
{
	// the command passed
	protected $command = '';
	protected $arguments = [];
	
	
	/**
	 * Constuctor
	 * @param array $arguments
	 */
	public function __construct(array $arguments)
	{
		// remove script
		array_shift($arguments);
		// get command
		$this->command = array_shift($arguments);
		// store arguments
		$this->arguments = $arguments;
	}
	
	
	public function params()
	{
		return $this->arguments;
	}
	
	
	public function command()
	{
		return $this->command;
	}
}