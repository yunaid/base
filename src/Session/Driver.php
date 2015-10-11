<?php
namespace Base\Session;

class SessionException extends \Exception {}

class Driver
{
	
	protected $params = [
		'lifetime' => 0,
		'encrypt' => true,
		'name' => 'session'
	];
	
	
	protected $data = null;
	
	protected $encryption = null;
	
	protected $cookie = null;
	

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
	
	
	public function set($var, $val)
	{
		if($this->data === null){
			$this->read();
		}
		$this->data[$var] = $val;
		
		return $this;
	}
	
	
	public function bind($var, & $val)
	{
		if($this->data === null){
			$this->read();
		}
		$this->data[$var] =& $val;
		
		return $this;
	}
	
	
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
	
	
	public function write()
	{
		$data = base64_encode(json_encode($this->data));

		if($this->params['encrypt']){
			$data = $this->encryption->encrypt($data);
		}
		$this->store($data);
	}
	
	public function destroy(){}
	
	public function extend(){}
	
	protected function retrieve(){}
	
	protected function store($data){}
	

}