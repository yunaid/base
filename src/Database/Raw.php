<?php
namespace Base\Database;
class Raw
{
	protected $expression = null;
	
	public function __construct($expression)
	{
		$this->expression = $expression;
	}
			
	public function expression()
	{
		return $this->expression;
	}
}