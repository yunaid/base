<?php

namespace Base\Database;

class Mysql extends Connection
{
	/**
	 * The quote style that should be used
	 * @var string 
	 */
	protected $quote = '`';
}