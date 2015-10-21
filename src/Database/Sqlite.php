<?php

namespace Base\Database;

class Sqlite extends Connection
{
	/**
	 * The quote style that should be used
	 * @var string 
	 */
	protected $quote = '"';
}
