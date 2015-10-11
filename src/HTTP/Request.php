<?php

namespace Base\HTTP;

use \Base\Arr as Arr;

class Request
{
	// globals
	protected $server = [];
	protected $get = [];
	protected $post = [];
	
	// parsed data
	protected $data = [];
	
	// headers
	protected $headers = [];
	
	// The params after a request has been parsed by router
	protected $params = [];

	
	/**
	 * Constructor: parse globals
	 * @param array $server
	 * @param array $get
	 * @param \Base\Arr $post
	 */
	public function __construct(array $server, array $get,  Arr $post)
	{
		// save globals
		$this->server = $server;
		$this->get = $get;
		$this->post = $post;
		
		// SERVER VARS
		$data = [];
		// Method
		$data['method'] = strtolower($this->server['REQUEST_METHOD']);
		// Protocol
		$data['protocol'] = empty($this->server['HTTPS']) || $this->server['HTTPS'] === 'off' ? 'http' : 'https';
		// Name
		$data['domain'] = $this->server['SERVER_NAME'];
		// Port
		$data['port'] = $this->server['SERVER_PORT'];

		// Path
		// /foo/index.php
		$script = $this->server['SCRIPT_NAME'];
		// /foo/bar?test=abc 
		// /foo/index.php/bar?test=abc
		$uri = $this->server['REQUEST_URI'];
		
		// test=abc or ""
		$data['query'] = isset($this->server['QUERY_STRING']) ? $this->server['QUERY_STRING'] : '';
		// Path
		if (strpos($uri, $script) !== false) {
			// the scipt name is in the uri, so no rewriting
			// $path is the same as the script
			$path = $script;
		} else {
			// the script name is not in the uri, so it rewriting is going on
			// the dirname of the script is the path
			$path = str_replace('\\', '', dirname($script));
		}
		// ensure one trailing slash for path
		$data['path'] = trim($path, '/') . '/';

		// Uri after the base path
		// remove the path from the uri
		$virtual = substr_replace($uri, '', 0, strlen($data['path']));
		// remove the query from the uri
		$virtual = str_replace('?' . $data['query'], '', $virtual);
		// trim slashes
		$data['uri'] = trim($virtual, '/');

		// Ip
		$data['remote'] = $this->server['REMOTE_ADDR'];

		// user agent
		$data['agent'] = $this->server['HTTP_USER_AGENT'];

		// compose base url
		$base = $data['protocol'] . '://' . $data['domain'] . ($data['port'] != '80' ? ':' . $data['port'] : '') . '/' . ($data['path'] === '/' ? '' : $data['path']);
		$data['base'] = rtrim($base, '/') . '/';

		// save
		$this->data = $data;

		// HEADERS
		$headers = [];
		$extra = [
			'CONTENT_TYPE',
			'CONTENT_LENGTH',
			'PHP_AUTH_USER',
			'PHP_AUTH_PW',
			'PHP_AUTH_DIGEST',
			'AUTH_TYPE'
		];
		foreach ($this->server as $key => $value) {
			$key = strtoupper($key);
			if (strpos($key, 'X_') === 0 || strpos($key, 'HTTP_') === 0 || in_array($key, $extra)) {
				if ($key === 'HTTP_CONTENT_LENGTH') {
					continue;
				}
				$headers[$key] = $value;
			}
		}
		$this->headers = $headers;
	}

	
	/**
	 * Get header information
	 * @param string $var
	 * @param string $default
	 * @return string|array
	 */
	public function header($var = null, $default = null)
	{
		if ($var === null) {
			return $this->headers;
		} else {
			if (isset($this->headers[$var])) {
				return $this->headers[$var];
			} else {
				return $default;
			}
		}
	}

	
	/**
	 * Get request data like method, port, etc.
	 * @param string $var
	 * @param string $default
	 * @return string
	 */
	public function data($var = null, $default = null)
	{
		if ($var === null) {
			return $this->data;
		} else {
			if (isset($this->data[$var])) {
				return $this->data[$var];
			} else {
				return $default;
			}
		}
	}

	
	/**
	 * Get a var from $_GET
	 * @param string $var
	 * @param string $default
	 * @return string
	 */
	public function query($var = null, $default = null)
	{
		if ($var === null) {
			return $this->get;
		} else {
			if (isset($this->get[$var])) {
				return $this->get[$var];
			} else {
				return $default;
			}
		}
	}

	
	/**
	 * Get a var from $_POST
	 * @param string $var
	 * @param string $default
	 * @return string
	 */
	public function post($var = null, $default = null)
	{
		if ($var === null) {
			return $this->post->get();
		} else {
			return $this->post->get($var, $default);
		}
	}
	
	
	/**
	 * Set params after they have been parsed
	 * @param array $params
	 * @return mixed
	 */
	public function params($params = null)
	{
		if ($params === null) {
			return $this->params;
		} else {
			$this->params = $params;
		}
	}

	/**
	 * Get a variable that was parsed by Router
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public function get($name, $default = null)
	{
		if (isset($this->params[$name])) {
			return $this->params[$name];
		} else {
			return $default;
		}
	}


	/**
	 * Check whether the request is an ajax request
	 * @return boolean
	 */
	public function ajax()
	{
		if (isset($this->server['HTTP_X_REQUESTED_WITH'])) {
			return strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
		} else {
			return false;
		}
	}


	/**
	 * Get current url
	 * @param Boolean $qs get the querystring as well
	 * @return String
	 */
	public function url($qs = true)
	{
		$url = $this->data['base'] . $this->data['uri'];
		if ($qs && $this->data['query'] != '') {
			return $url . '?' . $this->data['query'];
		} else {
			return $url;
		}
	}

	
	/**
	 * Access to method, port, etc as properties
	 * @param string $var
	 * @return string
	 */
	public function __get($var)
	{
		if (isset($this->data[$var])) {
			return $this->data[$var];
		} else {
			return null;
		}
	}
}
