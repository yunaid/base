<?php

namespace Base;

use \Base\ORM\Schema as Schema;
use \Base\ORM\Mapper as Mapper;
use \Base\Database as Database;

class Model
{	
	/**
	 * The schema name
	 * @var string 
	 */
	protected $name = null;
	
	/**
	 * The schema object
	 * @var \Base\ORM\Schema 
	 */
	protected $schema = null;
	
	/**
	 * Database object
	 * @var \Base\Database 
	 */
	protected $database = null;
	
	/**
	 * Closure that creates makkers
	 * @var \Closure 
	 */
	protected $mapperFactory = null;
	
	/**
	 * Closure that creates entities
	 * @var \Closure 
	 */
	protected $entityFactory = null;
	
	/**
	 * Methods that need to be registered with the mapper so they are available in records
	 * @var array 
	 */
	protected $presenters = [];
	
	
	/**
	 * Constructor
	 * @param string $name
	 * @param \Base\ORM\Schema $schema
	 * @param \Base\Database $database
	 * @param \Base\ORM\Mapper $mapper
	 * @param \Base\ORM\Entity $entityFactory
	 */
	public function __construct($name, Schema $schema, \Closure $mapperFactory, \Closure $entityFactory, Database $database)
	{
		$this->name = $name;
		$this->schema = $schema;
		$this->mapperFactory = $mapperFactory;
		$this->entityFactory = $entityFactory;
		$this->database = $database;
	}
	
	
	/**
	 * Get a new mapper
	 * Register all methods from $presenters array
	 * @param string $name
	 * @return \Base\ORM\Mapper
	 */
	public function mapper($name = null)
	{
		if($name === null){
			$name = $this->name;
		}
		$mapper = $this->mapperFactory->__invoke($name);
		foreach($this->presenters as $name){
			if(method_exists($this, $name)) {
				$mapper->method($name, [$this, $name]);
			}
		}
		return $mapper;
	}
	
	
	/**
	 * Get an empty entity
	 * @param string $name
	 * @return \Base\ORM\Entity
	 */
	public function entity($name = null)
	{
		if($name === null){
			$name = $this->name;
		}
		return $this->entityFactory->__invoke($name);
	}
	

	/**
	 * 
	 * @param int $amount
	 * @param int $skip
	 * @param array $sort
	 * @param \Base\ORM\Mapper $mapper
	 * @return \Base\ORM\Mapper
	 */
	public function all($amount = null, $skip = 0, array $sort = array(), Mapper $mapper = null)
	{
		if($mapper === null){
			$mapper = $this->mapper();
		} 
		return $mapper
		->amount($amount)
		->skip($skip)
		->sort($sort)
		->all();
	}
	
	
	/**
	 * Get one record from a mapper
	 * @param array|int|string $idOrFilters
	 * @param \Base\ORM\Mapper $mapper
	 * @return \Base\ORM\Record
	 */
	public function one($idOrFilters, Mapper $mapper = null)
	{
		if($mapper === null){
			$mapper = $this->mapper();
		} 
		return $mapper->one($idOrFilters);
	}
	
	
	/**
	 * Build a functioning record from an array and query for the relations based on given ids
	 * @param array $data
	 * @return \Base\ORM\Record
	 */
	public function record($data)
	{
		if($this->mapper === null){
			$mapper = $this->mapper();
		} else {
			$mapper = $this->mapper;
		}
		return $this->mapper()
		->record($data);
	}
	

	/**
	 * Get a loaded entity
	 * @param int|stirng|array $idOrFilters
	 * @return \Base\ORM\Entity
	 */
	public function load($idOrFilters)
	{
		return $this->entity()
		->load($idOrFilters);
	}
	
	
	/**
	 * Create and return an antity
	 * @param array $data
	 * @return \Base\ORM\Entity
	 */
	public function create(array $data = [])
	{
		return $this->entity()
		->data($data)
		->save();
	}
	
	
	/**
	 * Update an entity
	 * @param int|string|array $idOrFilters
	 * @param array $data
	 * @return \Base\ORM\Entity|false
	 */
	public function update($idOrFilters, array $data = [])
	{
		$entity = $this->load($idOrFilters);
		
		if($entity->loaded()){
			return $entity
			->data($data)
			->save();
		} else {
			return false;
		}
	}
	
	
	/**
	 * Delete an entity
	 * @param int|string|array $idOrFilters
	 * @return \Base\ORM\Entity|false
	 */
	public function delete($idOrFilters)
	{
		$entity = $this->load($idOrFilters);
		
		if($entity->loaded()){
			return $entity->delete();
		} else {
			return false;
		}
	}
}