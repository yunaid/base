<?php

class DatabaseTest extends PHPUnit_Framework_TestCase
{
	protected function database()
	{
		$container = new \Base\Container();
		\Base\Provider\Test::register($container);
		return  $container->get('database');
	}
	
	
	public function testInsert()
	{
		$database = $this->database();
		
		list($query, $params) = $database->insert('foo')
		->values([
			'bar' => 'baz'
		])
		->compile();
		
		$this->assertEquals('INSERT INTO `foo` (`bar`) VALUES (?)', $query);
		$this->assertEquals(['baz'], $params);
		
		
		list($query, $params) = $database->insert('f`o\'o')
		->values([
			'bar' => 'baz',
			'qux' => $database->raw('raw'),
		])
		->values([
			'bar' => 'boo'
		])
		->compile();
		
		$this->assertEquals('INSERT INTO `f``o\'o` (`bar`, `qux`) VALUES (?, raw)', $query);
		$this->assertEquals(['boo'], $params);
	}
	
	
	
	public function testDelete()
	{
		$database = $this->database();
		
		list($query, $params) = $database->delete('foo')
		->compile();
		
		$this->assertEquals('DELETE FROM `foo`', $query);
		$this->assertEquals([], $params);
		
		
		list($query, $params) = $database->delete('foo')
		->where('id', 5)
		->compile();
		
		$this->assertEquals('DELETE FROM `foo` WHERE `id` = ?', $query);
		$this->assertEquals([5], $params);
	}
	
	
	public function testUpdate()
	{
		$database = $this->database();
		
		list($query, $params) = $database->update('foo')
		->values('foo', 'bar')
		->set([
			'baz' => 'qux'
		])
		->values([
			'foo' => 'boo',
			'empty' => null
		])
		->where('id', 1)
		->compile();
		
		$this->assertEquals('UPDATE `foo` SET `foo` = ?, `baz` = ?, `empty` = DEFAULT(`empty`) WHERE `id` = ?', $query);
		$this->assertEquals(['boo', 'qux', 1], $params);
	}
	
	
	public function testSelectBasic()
	{
		$database = $this->database();
		
		list($query, $params) = $database->select('foo', ['bar', 'baz'])
		->distinct('foo')
		->distinct(['foo', 'bar'])
		->from('qux')
		->join('foreign')
		->on('foreign.id','foo.foreign_id')
		->where('id', 1)
		->group('thing')
		->having('doo', 'dah')
		->order('field1', 'asc')
		->order(['field1' => 'desc', 'field2' => 'asc'])
		->limit(3)
		->limit(4)
		->offset(5)
		->offset(6)
		->compile();
		
		$expected = 'SELECT `foo`, `bar` AS `baz` '.
		'DISTINCT `foo`, `foo`, `bar` '.
		'FROM `qux` '.
		'INNER JOIN `foreign` '.
		'ON `foreign`.`id` = `foo`.`foreign_id` '.
		'WHERE `id` = ? '.
		'GROUP BY `thing` '.
		'HAVING `doo` = ? '.
		'ORDER BY `field1` DESC, `field2` ASC '.
		'LIMIT 4 '.
		'OFFSET 6';
		
		$this->assertEquals($expected, $query);
		$this->assertEquals([1, 'dah'], $params);
	}
	

	
	public function testOperators()
	{
		$database = $this->database();
		
		list($query, $params) = $database->select('foo')
		->from('bar')
		->where('var', 1)
		->where('var', '=', 2)
		->where('var', '<>', 3)
		->where('var', 'IN', [4,5,6])
		->where('var', 'BETWEEN', [7,8])
		->where('var', 'NOT BETWEEN', [9,10])
		->where('var', null)
		->where('var', '=', null)
		->where('var', '!=', null)
		->where('var', '<>', null)
		->where('var', 'REGEXP', '[0-9]')
		->where('var', 'NOT REGEXP', '[a-z]')
		->compile();
		 
		$expected = 'SELECT `foo` '.
		'FROM `bar` '.
		'WHERE `var` = ? '.
		'AND `var` = ? '.
		'AND `var` <> ? '.
		'AND `var` IN (?,?,?) '.
		'AND `var` BETWEEN ? AND ? '.
		'AND `var` NOT BETWEEN ? AND ? '.
		'AND `var` IS NULL '.
		'AND `var` IS NULL '.
		'AND `var` IS NOT NULL '.
		'AND `var` IS NOT NULL '.
		'AND `var` REGEXP ? '.
		'AND `var` NOT REGEXP ?';
		
		$this->assertEquals($expected, $query);
		$this->assertEquals([1,2,3,4,5,6,7,8,9,10,'[0-9]','[a-z]'], $params);
	}
	
	
	
	public function testLogic()
	{
		$database = $this->database();
		
		list($query, $params) = $database->select('foo')
		->from('bar')
		->where('var', 1)
		->orWhere('var', 2)
		->orWhere(function($query){
			$query->where('var', '<>' , 3)
			->where(function($query){
				$query->where('var', '<>', 4)
				->orWhere('var', 'IN', [5,6]);
			});
		})
		->compile();
		
		$expected = 'SELECT `foo` '.
		'FROM `bar` '.
		'WHERE `var` = ? '.
		'OR `var` = ? '.
		'OR (`var` <> ? '.
		'AND (`var` <> ? '.
		'OR `var` IN (?,?)))';
		
		$this->assertEquals($expected, $query);
		$this->assertEquals([1,2,3,4,5,6], $params);
	}
	
	
	public function testJoins()
	{
		$database = $this->database();
		
		// basic join
		list($query, $params) = $database->select('foo')
		->from('bar')
		->join('baz')->on('baz.id','bar.baz_id')
		->compile();
		
		$expected = 'SELECT `foo` '.
		'FROM `bar` '.
		'INNER JOIN `baz` '.
		'ON `baz`.`id` = `bar`.`baz_id`';

		$this->assertEquals($expected, $query);
		$this->assertEquals([], $params);
		
		
	}
	
	
	public function testUnion()
	{
		
	}
}