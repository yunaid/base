<?php
namespace Base;
class CookieException extends \Exception{}

class Cookie 
{
	protected $cookie = null;
	
	protected $request = null;
	
	protected $salt = null;

	protected $expiration = 0;

	protected $path = '/';

	protected $domain = null;

	protected $secure = false;

	protected $httponly = true;

	
	public function __construct(& $cookie, $request, $salt)
	{
		$this->cookie = $cookie;
		$this->request = $request;
		$this->salt = $salt;
	}
	
	
	public function expiration($expiration = null)
	{
		if($expiration === null){
			return $this->expiration;
		} else {
			$this->expiration = $expiration;
		}
	}
	
	
	public function path($path = null)
	{
		if($path === null){
			return $this->path;
		} else {
			$this->path = $path;
		}
	}
	
	
	public function domain($domain = null)
	{
		if($domain === null){
			return $this->domain;
		} else {
			$this->domain = $domain;
		}
	}
	
	
	public function secure($secure = null)
	{
		if($secure === null){
			return $this->secure;
		} else {
			$this->secure = $secure;
		}
	}
	
	
	public function httponly($httponly = null)
	{
		if($httponly === null){
			return $this->httponly;
		} else {
			$this->httponly = $httponly;
		}
	}
	
	
	public function get($name, $default = null)
	{
		if (!isset($this->cookie[$name])){
			return $default;
		}

		$cookie = $this->cookie[$name];
		if(strpos($cookie,'____') !== false){
			$parts = explode('____',$cookie);
			$hash = $parts[0];
			$value = $parts[1];
			if($this->hash($name, $value) === $hash){
				return $value;
			} 
			$this->delete($name);
		}
		return $default;
	}


	
	public function set($name, $value, $expiration = null, $raw = false)
	{
		if ($expiration === null){
			$expiration = $this->expiration;
		}
		if ($expiration != 0){
			$expiration += time();
		}
		if(!$raw) {
			$value = $this->hash($name, $value).'____'.$value;
		}
		setcookie($name, $value, $expiration, $this->path, $this->domain, $this->secure, $this->httponly);
	}

	
	
	public function delete($name)
	{
		unset($this->cookie[$name]);
		setcookie($name, null, -86400, $this->path, $this->domain, $this->secure, $this->httponly);
	}

	
	/**
	 * Get a  hash
	 * @param string $name
	 * @param string $value
	 * @return string
	 * @throws CookieException
	 */
	public function hash($name, $value)
	{
		if ( ! $this->salt === null){
			throw new CookieException('No salt set in cookie');
		}	
		return sha1($this->request->agent.$name.$value.$this->salt);
	}
}
