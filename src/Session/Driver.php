<?php
namespace Base\Session;

class SessionException extends \Exception {}

abstract class Driver
{
	/**
	 * Params
	 * @var array 
	 */
	protected $params = [
		'lifetime' => 0,
		'encrypt' => true,
		'name' => 'session'
	];
	
	/**
	 * Data to store
	 * @var array 
	 */
	protected $data = null;
	
	/**
	 * Encryption object
	 * @var \Base\Encryption 
	 */
	protected $encryption = null;
	
	/**
	 * The cookie object
	 * @var \Base\Cookie 
	 */
	protected $cookie = null;
	
	
	/**
	 * Get a var
	 * @param string $var
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($var = null, $default = null)
	{
		if($this->data === null){
			$this->read();
		}
		if($var === null){
			return $this->data;
		} elseif(isset($this->data[$var])){
			return $this->data[$var];
		} else {
			return $default;
		}
	}
	
	
	/**
	 * Set a var
	 * @param string $var
	 * @param mixed $val
	 * @return \Base\Session\Driver
	 */
	public function set($var, $val)
	{
		if($this->data === null){
			$this->read();
		}
		$this->data[$var] = $val;
		
		return $this;
	}
	
	
	/**
	 * Bind a var
	 * @param string $var
	 * @param mixed $val
	 * @return \Base\Session\Driver
	 */
	public function bind($var, & $val)
	{
		if($this->data === null){
			$this->read();
		}
		$this->data[$var] =& $val;
		
		return $this;
	}
	
	
	/**
	 * Retrieve and decrypt data
	 */
	public function read()
	{
		if($data = $this->retrieve()){
			if($this->params['encrypt']){
				$data = $this->encryption->decrypt($data);
			}
			$this->data = json_decode(base64_decode($data),true);
		} else {
			$this->data = [];
		}
	}
	
	
	/**
	 * Encrypt and store data
	 */
	public function write()
	{
		$data = base64_encode(json_encode($this->data));

		if($this->params['encrypt']){
			$data = $this->encryption->encrypt($data);
		}
		$this->store($data);
	}
	
	/**
	 * Destroy the session
	 */
	abstract public function destroy();
	
	/**
	 * Prolong the session life
	 */
	abstract public function extend();
	
	/**
	 * Retrieve data from storage
	 * @return string|null
	 */
	abstract protected function retrieve();
	
	/**
	 * Store data
	* @param string $data
	 */
	abstract protected function store($data);
}