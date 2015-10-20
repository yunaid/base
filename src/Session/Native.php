<?php

namespace Base\Session;

use \Base\Cookie as Cookie;
use \Base\Encryption as Encryption;

class Native extends Driver
{
	
	/**
	 * Constructor
	 * @param array $params
	 * @param \Base\Cookie $cookie
	 * @param \Base\Encryption $encryption
	 */
	public function __construct(array $params, Cookie $cookie, Encryption $encryption = null)
	{
		// write on shutdown
		register_shutdown_function([$this, 'write']);
		
		$this->params = array_replace_recursive([
			'path' => null,
		], $this->params, $params);
			
			
		$this->cookie = $cookie;
		$this->encryption = $encryption;
		
		if($this->params['path'] !== null){
			session_save_path($this->params['path']);
		}
		
		// set the session cookie params like the cookie params
		session_set_cookie_params($this->params['lifetime'], $this->cookie->path(), $this->cookie->domain(), $this->cookie->secure(), $this->cookie->httponly());

		// dont send cache control headers
		session_cache_limiter(false);

		// set cookie name
		session_name($this->params['name']);

		// start the session
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
	}


	/**
	* {@inheritdoc}
	*/
	protected function retrieve()
	{
		// return the session data key
		return isset($_SESSION['data']) ? $_SESSION['data'] : [];
	}


	/**
	* {@inheritdoc}
	*/
	protected function store($data)
	{
		$_SESSION['data'] = $data;
		session_write_close();
	}

	
	/**
	* {@inheritdoc}
	*/
	public function extend()
	{
		// start the session to prevent garbage collection
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		// reset cookie
		$this->cookie->set($this->params['name'], session_id(), $this->params['lifetime'], true);
	}
	
	
	/**
	* {@inheritdoc}
	*/
	public function destroy()
	{
		session_destroy();
		$this->cookie->delete($this->params['name']);
	}
}
