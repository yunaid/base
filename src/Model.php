<?php

namespace Base;

class Model
{	
	// the schema name
	protected $name = null;
	
	// the schema object
	protected $schema = null;
	
	// database object
	protected $database = null;
	
	// craetes mappers with make()
	protected $mapperFactory = null;
	
	// creates entities with make()
	protected $entityFactory = null;
	
	
	/**
	 * Constructor
	 * @param string $name
	 * @param \Base\ORM\Schema $schema
	 * @param \Base\Database $database
	 * @param \Base\ORM\Mapper $mapper
	 * @param \Base\ORM\Entity $entityFactory
	 */
	public function __construct($name, $schema, $mapperFactory, $entityFactory, $database)
	{
		$this->name = $name;
		$this->schema = $schema;
		$this->mapperFactory = $mapperFactory;
		$this->entityFactory = $entityFactory;
		$this->database = $database;
	}
	
	
	/**
	 * Get a new mapper
	 * @return \Base\ORM\Mapper
	 */
	public function mapper($name = null)
	{
		if($name === null){
			$name = $this->name;
		}
		return $this->mapperFactory->__invoke($name);
	}
	
	
	/**
	 * Get an empty entity
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
	 * Get all records from a mapper
	 * More likely a custom method will be used to get exactly what we want
	 * @param Boolean $array get as array (or iterator)
	 * @return array|iterator
	 */
	public function all($amount = null, $skip = 0, $sort = array(), $mapper = null)
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
	 * @param array $idOrFilters
	 * @return \Base\ORM\Record
	 */
	public function one($idOrFilters, $mapper = null)
	{
		if($mapper === null){
			$mapper = $this->mapper();
		} 
		return $mapper
		->one($idOrFilters);
	}
	
	
	/**
	 * Build a functioning record from an array and query for the relations based on given ids
	 * @param array $values
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
	 * @param array $idOrFilters
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
	public function create($data = [])
	{
		return $this->entity()
		->data($data)
		->save();
	}
	
	
	/**
	 * Update an entity
	 * @param type $idOrFilters
	 * @param array $data
	 * @return \Base\ORM\Entity | false
	 */
	public function update($idOrFilters, $data = [])
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
	 * @param array $idOrFilters
	 * @return \Base\ORM\Entity | false
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