<?php

namespace Base\ORM;

class Entity
{
	// the schema name
	protected $name = null;
	
	// schema object with all schema information
	protected $schema = [];
	
	// database object used for persisting data
	protected $database = null;
	
	// mapper object
	protected $mapper = null;
	
	// params extracted from schema for this $name
	protected $params = [
		'database' => '',
		'table' => '',
		'columns' => [],
		'json' => [],
		'relations' => []
	];
	
	// the loaded record
	protected $record = null;
	
	// provided update to save later on
	protected $data = [];
	
	// whether the entity has a loaded status
	protected $loaded = null;
	

	/**
	 * Constructor
	 * @param string $name
	 * @param \Base\ORM\Schema $schema
	 * @param \Base\Database $database
	 * @param \Base\ORM\Mapper $mapper
	 */
	public function __construct($name, $schema, $database, $mapper)
	{
		$this->name = $name;
		$this->schema = $schema;
		$this->database = $database;
		$this->mapper = $mapper;
		$params = $schema->get($name);
		$this->params = array_merge($this->params, $params ? $params : []);
	}
	
	
	/**
	 * Use the mapper object to load data for id or set of filters
	 * @param int|array $idOrFilters
	 * @return \Base\ORM\Entity
	 */
	public function load($idOrFilters)
	{
		$this->record = $this->mapper->one($idOrFilters);
		return $this;
	}
	
	
	/**
	 * Get or set data 
	 * @param array $data
	 * @return \Base\ORM\Entity|array
	 */
	public function data($data = null)
	{
		if($data === null){
			if($this->record !== null){
				$data = $this->record->flat();
				foreach($data as $key => $value){
					if(isset($this->data[$key])){
						$data[$key] = $this->data[$key];
					}
				}
				return $data;
			} else {
				$data = [];
				foreach($this->params['columns'] as $column){
					if(isset($this->data[$column])) {
						$data[$column] = $this->data[$column];
					}
				}
				foreach($this->params['relations'] as $name){
					if(isset($this->data[$name])) {
						$data[$name] = $this->data[$name];
					}
				}
				return $data;
			}
		} else {
			if(is_array($data)){
				$this->data = array_merge($this->data, $data);
			}
			return $this;
		}
	}
	
	
	/**
	 * Get original data
	 * @param string $name specific name. leave empty to get all all the data
	 * @return mixed
	 */
	public function original($name = null)
	{
		if($this->record !== null){
			if($name === null){
				return $this->record->flat();
			} else {
				return $this->record->{$name};
			}
		} else {
			return null;
		}
	}
	
	
	/**
	 * Get or set the loaded property
	 * Pass a boolean to manually set it. Leave empty to get it from the loaded data
	 * @param void|boolean $loaded
	 * @return boolean
	 */
	public function loaded($loaded = null)
	{
		if(is_bool($loaded)){
			$this->loaded = $loaded;
			return $this;
		} elseif($this->loaded === null) {
			$this->loaded = $this->record !== null;
			return $this->loaded;
		} else {
			return $this->loaded;
		}
	}
	
	
	/**
	 * clear data and loaded variable
	 * @return \Base\ORM\Entity
	 */
	public function clear()
	{
		$this->data = [];
		$this->loaded = null;
		$this->record = null;
		return $this;
	}
	
	
	/**
	 * Remove entity from database
	 * @return \Base\ORM\Entity
	 */
	public function delete()
	{
		// remove entity from db
		if($this->record !== null){
			$this->database->delete($this->params['table'])
			->where('id',$this->record->id)
			->execute();
		}
		
		// remove relations from db
		foreach($this->params['relations'] as $relation){
			if($relation[1] === 'pivot' || $relation[1] === 'set'){
				$this->remove($relation[0]);
			}
		}
		// clear the entity
		$this->clear();
		
		return $this;
	}
	
	
	/**
	 * Add a many to many relation
	 * @param string $name
	 * @param int $id
	 * @param array $pivot extra data for pivot
	 * @return \Base\ORM\Entity
	 */
	public function add($name, $id, $pivot = [])
	{
		if($this->loaded() && isset($this->params['relations'][$name])){
			$params = $this->params['relations'][$name];
			
			if($params[1] === 'pivot'){
				$data = [
					$params[3] => $this->id,
					$params[4] => $id
				];

				// add filtered pivot values
				if(isset($params[5]) && is_array($params[5])){
					foreach($params[5] as $column){
						if(isset($pivot[$column])){
							$data[$column] = $pivot[$column];
						}
					}
				}
				
				// insert into pivot
				$this->database
				->insert($params[2])
				->data($data)
				->execute();
			}
			
			if($params[1] === 'set'){
				// create a new set
				if($this->data[$params[3]] == 0){
					 $this->data[$params[3]] = $this->database->insert($params[2])
					->data([
						'id' => 0,
						$params[4] => 0,
					])
					->result('set_id');
					 $this->save();
				}
				// insert into set
				$data = [
					'id' => $this->data[$params[3]],
					$params[4] => $id
				];

				// add filtered pivot values
				if(isset($params[5]) && is_array($params[5])){
					foreach($params[5] as $column){
						if(isset($pivot[$column])){
							$data[$column] = $pivot[$column];
						}
					}
				}
				
				$this->database->insert($params[2])
				->values($data)
				->execute();
			}
		}
		return $this;
	}
	
	
	/**
	 * Remove one or all many to many relations 
	 * @param string $name
	 * @param void|int $id
	 * @return \Base\ORM\Entity
	 */
	public function remove($name, $id = null)
	{
		if($this->loaded() && isset($this->params['relations'][$name])){
			$params = $this->params['relations'][$name];
			
			if($params[1] === 'pivot'){
				// delete from pivot
				$query = $this->database->delete($params[2])
				->where($params[3], $this->id);	
				if($id !== null){
					$query->where($params[4], $id);
				}	
				$query->execute();
			}
			
			if($params[1] === 'set'){
				// delete from set
				$query = $this->database->delete($params[2])
				->where('id', $this->{$params[3]});
				if($id !== null){
					$query->where($params[4], $id);
				}	
				$query->execute();
			}
		}
		return $this;
	}
	
	
	/**
	 * Save the current data
	 * @return \Base\ORM\Entity
	 */
	public function save()
	{
		// the data to create or update
		$data = [];
		
		// prepare data to update or insert
		foreach($this->params['columns'] as $column => $format){
			if(isset($this->data[$column])){
				// get the value
				$value = $this->data[$column];
				// convert to json if necesary
				if($format == 'json' || $format == 'array'){
					$value = json_encode($value);
				}
				// store value in data
				$data[$column] = $value;
			}
		}
		
		// update belongs-to relations
		foreach($this->params['relations'] as $relation){
			if(isset($this->data[$relation[0]]) && $relation[1] === 'belongs'){
				// get value
				$value = $this->data[$relation[0]];
				// extact id and set foreign key
				if(is_object($value) && isset($value->id) ){
					$data[$relation[2]] = $value->id;
				} elseif(is_array($value) && isset($value['id']) ) {
					$data[$relation[2]] = $value['id'];
				} else {
					$data[$relation[2]] = $value;
				}
			}
		}
		
		// update or create row
		if($this->record !== null){
			// update by record-id
			$this->database->update($this->params['table'])
			->set($data)
			->where('id',$this->record->id)
			->execute();
			
		} elseif(isset($data['id'])) {
			// update by given id
			$this->database->update($this->params['table'])
			->set($data)
			->where('id', $data['id'])
			->execute();
			
			// load record
			$this->record = $this->mapper->one($data['id']);
		} else {
			// create new
			$id = $this->database->insert($this->params['table'])
			->values($data)
			->result();

			// load record
			$this->record = $this->mapper->one($id);
		}
		
		// update, create or remove pivot- and set-relations
		if($this->record !== null){
			
			foreach($this->params['relations'] as $relation) {
				
				if(isset($this->data[$relation[0]]) && ($relation[1] === 'pivot' || $relation[1] === 'set' )) {
					// get values for this relation
					$values = $this->data[$relation[0]];
				
					// convert non sequential arrays to array
					if(!is_array($values) || array_values($values) !== $values){
						$values= [$values];
					}
					
					// extract ids and pivot-data from value
					$items = [];
					foreach($values as $value){
						if(is_object($value) && isset($value->id) ){
							$items[] = [
								'id' => $value->id,
								'pivot' => isset($value->pivot) && is_array($value->pivot) ? $value->pivot : array()
							];
						} elseif(is_array($value) && isset($value['id']) ) {
							$items[] = [
								'id' => $value['id'],
								'pivot' => isset($value['pivot'])  && is_array($value['pivot']) ? $value['pivot'] : array()
							];
						} else {
							$items[] = [
								'id' => $value,
								'pivot' => []
							];
						}
					}
					
					// get existing row for pivot relation
					if($relation[1] === 'pivot'){
						$rows = $this->database
						->select()
						->from($relation[2])
						->where($relation[3], $this->record->id)
						->execute();
					}
					
					// get existing row for set relation
					if($relation[1] === 'set'){
						$rows = $this->database
						->select()
						->from($relation[2])
						->where('id', $this->record->{$relation[3]})
						->execute();
					}
					
					// go through existing rows: we will re-use them for changing relations
					foreach($rows as $index => $row){
					
						// delete row from the pivot table
						$this->database
						->delete($relation[2])
						->where('id', $row['id'])
						->execute();
							
						if(isset($items[$index])){
							// we have an item that we can place in this row
							// row to insert at the same id
							$insert = [
								'id' => $row['id'],
								$relation[4] => $items[$index]['id']
							];
							
							// add provided pivot values
							if(isset($relation[5]) && is_array($relation[5])){
								foreach($relation[5] as $column){
									if(isset($items[$index]['pivot'][$column])){
										$insert[$column] = $items[$index]['pivot'][$column];
									}
								}
							}

							// insert a new row the pivot table at the same location
							$this->insert($relation[2])
							->values($insert)
							->execute();
						}
					}
					
					// add remaining items
					// we already covered an amount of items: the number of rows we looped through previously
					for($i = count($rows); $i < count($items); $i++){
						// add the relation
						$this->add($key, $items[$i]['id'],  $items[$i]['pivot']);
					}
				}
			}
		}
		return $this;
	}
	

	/**
	 * set a property in data
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}
	
	
	/**
	 * Get a property from data or a relation
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if(isset($this->data[$name])){
			return $this->data[$name];
		} elseif($this->record !== null){
			return $this->record->{$name};
		} else {
			return null;
		}
	}
	
	
	/**
	 * Call relations on a record
	 * @param string $name relation name
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		if($this->record !== null){
			return call_user_func_array([ $this->record, $name ], $args);
		} else {
			return null;
		}
	}
}