<?php

namespace Base\Database;

class Postgresql extends Connection
{
	/**
	 * The quote style that should be used
	 * @var string 
	 */
	protected $quote = '"';
}