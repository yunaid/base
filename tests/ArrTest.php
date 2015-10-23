<?php

class ArrTest extends PHPUnit_Framework_TestCase
{
	protected function make($data)
	{
		return  new \Base\Arr($data);
	}
	
	public function testBasicGet()
	{
		$data = [
			'foo' => [
				'bar' => 'val1'
			]
		];
		
		$arr = $this->make($data);
		
		$this->assertEquals('val1', $arr->get('foo.bar'));
		$this->assertEquals('val1', $arr->get(['foo', 'bar']));
		$this->assertEquals(['bar' => 'val1'], $arr->get('foo'));
		$this->assertEquals(['bar' => 'val1'], $arr->get(['foo']));
		
		$this->assertEquals('default', $arr->get('bar', 'default'));
		$this->assertEquals('default', $arr->get(['bar'], 'default'));
		
		$this->assertEquals(['foo' => ['bar' => 'val1']], $arr->get());
		$this->assertEquals(['foo' => ['bar' => 'val1']], $arr->flat());
		$this->assertEquals(['foo' => ['bar' => 'val1']], $arr->get(null));
		$this->assertEquals(['foo' => ['bar' => 'val1']], $arr->get(null, 'default'));
			
	}
	
	
	public function testDataGetterSetter()
	{
		$arr = $this->make([]);
		
		$arr->data([
			'foo' => [
				'bar' => 'val1'
			]
		]);
		$this->assertEquals(['foo' => ['bar' => 'val1']], $arr->data());
	}
	
	
	
	public function testComplicatedGet()
	{
		$data = [
			'bar.baz' => [
				'qux' => 'val2'
			],
			'bar' => [
				'baz.qux' => 'val3'
			]
		];
		
		$arr = $this->make($data);
		
		$this->assertEquals('val2', $arr->get(['bar.baz', 'qux']));
		$this->assertEquals('val3', $arr->get(['bar', 'baz.qux']));
		$this->assertEquals('val3', $arr->get('bar.baz.qux'));
		$this->assertEquals('default', $arr->get(['bar', 'baz',' qux'], 'default'));
	}
}

