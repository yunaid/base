<?php

namespace Base\HTTP;

class ResponseException extends \Exception{} 

class Response
{
	/**
	 * Status
	 * @var int 
	 */
	protected $status = 200;
	
	/**
	 * Headers to send
	 * @var array 
	 */
	protected $headers = [];
	
	/**
	 * The body to ouput
	 * @var string 
	 */
	protected $body = '';
	
	
	/**
	 * Status messages
	 * @var array 
	 */
	protected $messages = array(
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',
		// Success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 is deprecated but reserved
		307 => 'Temporary Redirect',
		// Client Error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	);


	/**
	 * Set the status
	 * @param int $status
	 * @return \Base\HTTP\Response
	 * @throws \Base\HTTP\ResponseException
	 */
	public function status($status)
	{
		if(!isset($this->messages[$status])){
			throw new ResponseException('Unknown status: '.$status);
		}
		$this->status = $status;
		
		return $this;
	}
	
	
	/**
	 * Set or get headers
	 * @param string|array $nameOrHeaders
	 * @param string $value
	  * @return array|\Base\HTTP\Response
	 */
	public function headers($nameOrHeaders = null, $value = null)
	{
		if ($nameOrHeaders === null) {
			return $this->headers;
		} elseif (is_array($nameOrHeader)){
			$this->headers = array_merge($this->headers, $nameOrHeader);
		} else {
			$this->headers[$nameOrHeaders] = $value;
		}
		return $this;
	}

	
	/**
	 * Set or get body
	 * @param string $body
	 * @return string|\Base\HTTP\Response
	 */
	public function body($body = null)
	{
		if ($body === null) {
			return $this->body;
		} else {
			$this->body = $body;
		}
		return $this;
	}

	
	/**
	 * Send response headers and body
	 */
	public function send()
	{
		$message = $this->messages[$this->status];
		header('HTTP/1.1 '.$this->status.' '.$message);
		foreach ($this->headers as $name => $value) {
			$values = explode("\n", $value);
			foreach ($values as $val) {
				header($name.': '.$val, false);
			}
		}
		echo $this->body;
	}
	

	/**
	 * Helper: Do a redirect and exit
	 * @param string $url
	 * @param int $status
	 */
	public function redirect($url, $status = 301)
	{
		if(!isset($this->messages[$status])){
			throw new ResponseException('Unknown status: '.$status);
		}
		$message = $this->messages[$status];
		header('HTTP/1.1 '.$status.' '.$message);
		header('Location: ' . $url);
		exit;
	}
}
