<?php

namespace Base;

use Base\HTTP\Request as Request;
use Base\HTTP\Response as Response;

class ControllerException extends \Exception{}

class Controller extends Command
{
	/**
	* Filter hook: overwrite in extensions
	* return false to stop execution
	 * @param \Base\HTTP\Request $request
	 * @param \Base\HTTP\Response $response
	 * @return boolean
	 */
	public function filter(Request $request, Response $response)
	{
		return true;
	}
	
	
	/**
	 * Before hook: overwrite in extensions
	 * @param \Base\HTTP\Request $request
	 * @param \Base\HTTP\Response $response
	 */
	public function before(Request $request, Response $response){}


	/**
	 * After hook: overwrite in extensions
	 * @param \Base\HTTP\Request $request
	 * @param \Base\HTTP\Response $response
	 */
	public function after(Request $request, Response $response){}
	
}