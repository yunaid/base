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
}