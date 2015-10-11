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
		
		$this->assertEquals($arr->get('foo.bar'), 'val1');
		$this->assertEquals($arr->get(['foo', 'bar']), 'val1');
		$this->assertEquals($arr->get('foo'), ['bar' => 'val1']);
		$this->assertEquals($arr->get(['foo']), ['bar' => 'val1']);
		
		$this->assertEquals($arr->get('bar', 'default'), 'default');
		$this->assertEquals($arr->get(['bar'], 'default'), 'default');
		
		$this->assertEquals($arr->get(), ['foo' => ['bar' => 'val1']]);
		$this->assertEquals($arr->flat(), ['foo' => ['bar' => 'val1']]);
		$this->assertEquals($arr->get(null), ['foo' => ['bar' => 'val1']]);
		$this->assertEquals($arr->get(null, 'default'), ['foo' => ['bar' => 'val1']]);
			
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
		
		$this->assertEquals($arr->get(['bar.baz', 'qux']), 'val2');
		$this->assertEquals($arr->get(['bar', 'baz.qux']), 'val3');
		$this->assertEquals($arr->get('bar.baz.qux'), 'val3');
		$this->assertEquals($arr->get(['bar', 'baz',' qux'], 'default'), 'default');
	}
}

