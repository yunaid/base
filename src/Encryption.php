<?php

namespace Base;

class Encryption
{

	protected $params = [
		'key' => '__key__',
		'mode' => MCRYPT_MODE_NOFB,
		'cipher' => MCRYPT_RIJNDAEL_128
	];
	protected $ivSize = 0;
	protected $rand = null;


	public function __construct($params = [])
	{
		$this->params = array_merge($this->params, $params);
		$this->ivSize = mcrypt_get_iv_size($this->params['cipher'], $this->params['mode']);
	}


	protected function rand()
	{
		if (defined('MCRYPT_DEV_URANDOM')) {
			$this->rand = MCRYPT_DEV_URANDOM;
		} elseif (defined('MCRYPT_DEV_RANDOM')) {
			$this->rand = MCRYPT_DEV_RANDOM;
		} else {
			$this->rand = MCRYPT_RAND;
			// The system random number generator must always be seeded each
			// time it is used, or it will not produce true random results
			mt_srand();
		}
	}


	public function encrypt($data)
	{
		if ($this->rand === null) {
			$this->rand();
		}
		$iv = mcrypt_create_iv($this->ivSize, $this->rand);
		$data = mcrypt_encrypt($this->params['cipher'], $this->params['key'], $data, $this->params['mode'], $iv);
		return base64_encode($iv . $data);
	}


	public function decrypt($data)
	{
		$data = base64_decode($data, true);

		if (!$data) {
			return false;
		}

		$iv = substr($data, 0, $this->ivSize);
		$data = substr($data, $this->ivSize);

		return rtrim(mcrypt_decrypt($this->params['cipher'], $this->params['key'], $data, $this->params['mode'], $iv), "\0");
	}

}
