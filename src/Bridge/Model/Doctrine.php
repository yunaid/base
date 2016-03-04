<?php

namespace Base\Bridge\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Repository;

class Model
{	
	/**
	 * The entity name
	 * @var string 
	 */
	protected $name = null;
	
	/**
	 * The Doctrine entitymanager
	 * @var type 
	 */
	protected $manager = null;
	
	/**
	 * Closure to create entities
	 * @var \Closure 
	 */
	protected $entityFactory = null;
	

	/**
	 * Constructor
	 * @param string $name
	 * @param \Doctrine\ORM\EntityManager $manager
	 * @param \Closure $entityFactory
	 */
	public function __contruct($name, EntityManager $manager, \Closure $entityFactory)
	{
		$this->name = $name;
		$this->manager = $manager;
		$this->entityFactory = $entityFactory;
	}
	
	
	/**
	 * Get a Repository
	 * @param string $name
	 * @return Doctrine\ORM\Repository
	 */
	public function repository($name = null)
	{
		if($name === null){
			$name = $this->name;
		}
		return $this->manager->getRepository($this->name);
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
	


	public function all($limit = null, $offset = 0, array $order = array(), Doctrine\ORM\Repository $repository = null)
	{
		if($repository === null){
			$repository = $this->repository();
		}
		
		$query = $repository->createQueryBuilder('item');
		
		if($limit){
			$query->setMaxResults($limit);
		}
		if($offset) {
			$query->setFirstResult($offset);
		}
		if($order) {
			foreach($order as $field => $direction) {
				$query->orderBy($field, $direction);
			}
		}

		$repository->
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