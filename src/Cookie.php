<?php

namespace Base;

use \Base\HTTP\Request as Request;

class CookieException extends \Exception {}

class Cookie
{

	/**
	 * $_COOKIE
	 * @var array 
	 */
	protected $cookie = null;

	/**
	 * Request object
	 * @var \Base\HTTP\Request 
	 */
	protected $request = null;

	/**
	 * Salt to prevent cookie falsification
	 * @var string 
	 */
	protected $salt = null;

	/**
	 * Lifetime in seconds
	 * @var int 
	 */
	protected $expiration = 0;

	/**
	 * Cookiepath
	 * @var string 
	 */
	protected $path = '/';

	/**
	 * Cookiedomain
	 * @var string 
	 */
	protected $domain = null;

	/**
	 * Secure yes/no
	 * @var boolean 
	 */
	protected $secure = false;

	/**
	 * Httponly yes/no
	 * @var boolean 
	 */
	protected $httponly = true;


	/**
	 * Create the cookie instance
	 * @param array $cookie
	 * @param \Base\HTTP\Request $request
	 * @param string $salt
	 */
	public function __construct(array & $cookie, Request $request, $salt)
	{
		$this->cookie = $cookie;
		$this->request = $request;
		$this->salt = $salt;
	}


	/**
	 * Get or set the default expiration
	 * @param int $expiration in seconds
	 * @return int|void
	 */
	public function expiration($expiration = null)
	{
		if ($expiration === null) {
			return $this->expiration;
		} else {
			$this->expiration = $expiration;
		}
	}


	/**
	 * Get or set the cookie path
	 * @param string|null $path
	 * @return string|void
	 */
	public function path($path = null)
	{
		if ($path === null) {
			return $this->path;
		} else {
			$this->path = $path;
		}
	}


	/**
	 * Get or set the domain
	 * @param string|null $domain
	 * @return string|void
	 */
	public function domain($domain = null)
	{
		if ($domain === null) {
			return $this->domain;
		} else {
			$this->domain = $domain;
		}
	}


	/**
	 * Get or set secure
	 * @param string|null $secure
	 * @return string|void
	 */
	public function secure($secure = null)
	{
		if ($secure === null) {
			return $this->secure;
		} else {
			$this->secure = $secure;
		}
	}


	/**
	 * Get or set httponly
	 * @param boolean|null $httponly
	 * @return boolean|void
	 */
	public function httponly($httponly = null)
	{
		if ($httponly === null) {
			return $this->httponly;
		} else {
			$this->httponly = $httponly;
		}
	}


	/**
	 * Get a cookie value
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public function get($name, $default = null)
	{
		if (!isset($this->cookie[$name])) {
			return $default;
		}

		$cookie = $this->cookie[$name];
		if (strpos($cookie, '____') !== false) {
			$parts = explode('____', $cookie);
			$hash = $parts[0];
			$value = $parts[1];
			if ($this->hash($name, $value) === $hash) {
				return $value;
			}
			$this->delete($name);
		}
		return $default;
	}


	/**
	 * Set a cookie value
	 * @param string $name
	 * @param string|int $value
	 * @param int $expiration
	 * @param boolean $raw dont salt the value
	 */
	public function set($name, $value, $expiration = null, $raw = false)
	{
		if ($expiration === null) {
			$expiration = $this->expiration;
		}
		if ($expiration != 0) {
			$expiration += time();
		}
		if (!$raw) {
			$value = $this->hash($name, $value) . '____' . $value;
		}
		setcookie($name, $value, $expiration, $this->path, $this->domain, $this->secure, $this->httponly);
	}


	/**
	 * Delete a value
	 * @param string $name
	 */
	public function delete($name)
	{
		unset($this->cookie[$name]);
		setcookie($name, null, -86400, $this->path, $this->domain, $this->secure, $this->httponly);
	}


	/**
	 * Get a hash to salt a value
	 * @param string $name
	 * @param string|int $value
	 * @return string
	 * @throws CookieException
	 */
	public function hash($name, $value)
	{
		if (!$this->salt === null) {
			throw new CookieException('No salt set in cookie');
		}
		return sha1($this->request->agent . $name . $value . $this->salt);
	}

}
