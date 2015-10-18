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
		->from('qux')
		->where('id', 1)
		->group('thing')
		->having('doo', 'dah')
		->order('field1', 'asc')
		->order(['field1' => 'desc', 'field1' => 'asc'])
		->limit(3)
		->limit(4)
		->offset(5)
		->offset(6)
		->compile();
		
		$this->assertEquals('SELECT `foo`, `bar` AS `baz` FROM `qux` WHERE `id` = ?', $query);
		$this->assertEquals([1], $params);
	}
	

	public function testCondition()
	{
		$database = $this->database();
		
		list($query, $params) = $database->select('foo')
		->from('bar')
		->where('var', 1)
		->where('var', '=', 1)
		->where('var', '<>', 1)
		->where('var', 'IN', [1,2,3])
		->where('var', 'BETWEEN', [1,3])
		->where('var', 'NOT BETWEEN', [1,3])
		->where('var', null)
		->where('var', '=', null)
		->where('var', '!=', null)
		->where('var', '<>', null)
		->compile();
		 
		$expected = 'SELECT `foo` FROM `bar` ' .
		'WHERE `var`' .
		$this->assertEquals($expected, $query);
		$this->assertEquals([1], $params);
	}

}