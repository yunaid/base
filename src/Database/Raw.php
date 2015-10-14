<?php

namespace Base\Database;

class Raw
{
	/**
	 * The string that should not me escaped
	 * @var string 
	 */
	protected $expression = null;
	
	
	/**
	 * Craete a raw expression
	 * @param string $expression
	 */
	public function __construct($expression)
	{
		$this->expression = $expression;
	}
		
	
	/**
	 * Get the expression
	 * @return string
	 */
	public function expression()
	{
		return $this->expression;
	}
}