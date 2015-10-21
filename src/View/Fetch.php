<?php

namespace Base\View;

class Fetch extends \Base\View\Engine
{
	/**
	 * Class alias.
	 * We can differentiate between the called class 'Engine' or 'Fetch'
	 * The first will mostly echo output, when fetch is called, it will return it.
	 * @var string 
	 */
	protected static $alias = 'fetch';
}