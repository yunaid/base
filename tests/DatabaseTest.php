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
		
		$this->assertEquals($query, 'INSERT INTO `foo` (`bar`) VALUES (?)');
		$this->assertEquals($params, ['baz']);
		
		
		
		list($query, $params) = $database->insert('f`o\'o')
		->values([
			'bar' => 'baz',
			'qux' => $database->raw('raw'),
		])
		->compile();
		
		$this->assertEquals($query, 'INSERT INTO `f``o\'o` (`bar`, `qux`) VALUES (?, raw)');
		$this->assertEquals($params, ['baz']);
	}
	
	
	
	public function testDelete()
	{
		$database = $this->database();
		
		list($query, $params) = $database->delete('foo')
		->compile();
		
		$this->assertEquals($query, 'DELETE FROM `foo`');
		$this->assertEquals($params, []);
		
		
		list($query, $params) = $database->delete('foo')
		->where('id', 5)
		->compile();
		
		$this->assertEquals($query, 'DELETE FROM `foo` WHERE `id` = ?');
		$this->assertEquals($params, [5]);
	}
	
	
	
	
}