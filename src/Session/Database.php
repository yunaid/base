<?php

namespace Base\Session;

use \Base\Database as DB;
use \Base\Cookie as Cookie;
use \Base\Encryption as Encryption;

class Database extends Driver
{
	/**
	 * Database instance
	 * @var \Base\Database 
	 */
	protected $database = null;
	
	/**
	 * Session id
	 * @var string 
	 */
	protected $id = null;
	
	/**
	 * The db row containing the session
	 * @var array 
	 */
	protected $row = null;
	

	/**
	 * Constructor
	 * Register write function
	 * @param array $params
	 * @param \Base\Database $database
	 * @param \Base\Cookie $cookie
	 * @param \Base\Encryption $encryption
	 */
	public function __construct(array $params, DB $database, Cookie $cookie, Encryption $encryption = null)
	{
		// write on shutdown
		register_shutdown_function([$this, 'write']);
		
		// merge params
		$this->params = array_replace_recursive([
			'table' => 'session',
			'columns' => [
				'id' => 'id',
				'updated' => 'updated',
				'data' => 'data'
			],
			'gc' => 1000
		], $this->params, $params);

		
		$this->cookie = $cookie;
		$this->database = $database;
		$this->encryption = $encryption;

		if($id = $this->cookie->get($this->params['name'], null)){
			// cookie has session id
			$this->id = $id;
		} else {
			// cookie doenst have a session: create an id
			$this->id = md5(uniqid(null, true) . mt_rand(0, 100000));
			$this->cookie->set($this->params['name'], $this->id, $this->params['lifetime']);
		}
		
		// retrieve session from db
		$result = $this->database->select($this->params['columns']['data'])
		->from($this->params['table'])
		->where($this->params['columns']['id'], $this->id)
		->limit(1)
		->result();
		
		if (count($result) > 0) {
			// retrieved raw data
			$this->row = $result[0];
		}
		
		// garbage collect by chance
		if (mt_rand(0, $this->params['gc'] + 1) === $this->params['gc']) {
			$this->gc();
		}
	}


	/**
	 * Garbage collect
	 */
	protected function gc()
	{
		$this->database->delete($this->params['table'])
		->where($this->params['columns']['updated'], '<', time() - $this->params['lifetime'])
		->execute();
	}
	
	
	/**
	* {@inheritdoc}
	*/
	protected function retrieve()
	{
		if($this->row !== null) {
			return $this->row[$this->params['columns']['data']];
		} else {
			// no data
			return false;
		}
	}

	
	/**
	* {@inheritdoc}
	*/
	protected function store($data)
	{
		if($this->row === null) {
			// create a new row
			$this->row = [
				$this->params['columns']['id'] => $this->id,
				$this->params['columns']['updated'] => time(),
				$this->params['columns']['data'] => $data
			];
			// store it
			$this->database->insert($this->params['table'])
			->values($this->row)
			->execute();
		} else {
			// update row
			$this->row[$this->params['columns']['data']] = $data;
			// store it
			$this->database->update($this->params['table'])
			->set([
				$this->params['columns']['updated'] => time(),
				$this->params['columns']['data'] => $data,
			])
			->where($this->params['columns']['id'], $this->id)
			->execute();
		}
	}
	
	
	/**
	* {@inheritdoc}
	*/
	public function extend()
	{
		$this->cookie->set($this->params['name'], $this->id, $this->params['lifetime']);
	}

	
	/**
	* {@inheritdoc}
	*/
	public function destroy()
	{
		$this->database->delete($this->params['table'])
		->where($this->params['columns']['id'], $this->id)
		->execute();
		$this->cookie->delete($this->params['name']);
		$this->id = null;
	}
}