<?php

namespace Base;

class CLI
{
	/**
	 * The passed command
	 * @var string 
	 */
	protected $command = '';
	
	/**
	 * Arguments
	 * @var array 
	 */
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
	
	
	/**
	 * Get arguments
	 * @return array
	 */
	public function params()
	{
		return $this->arguments;
	}
	
	
	/**
	 * Get command
	 * @return string
	 */
	public function command()
	{
		return $this->command;
	}
}