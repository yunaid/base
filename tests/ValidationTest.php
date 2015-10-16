<?php

class ValidationTest extends PHPUnit_Framework_TestCase
{
	protected function make()
	{
		return  new \Base\Validation();
	}
	
	public function testBasic()
	{
		// single rule
		$validation = $this->make();
		$validation->rule('foo','required');
		
		// rule fails
		$this->assertEquals($validation->validate('foo', null), false);
		$this->assertEquals($validation->error('foo'), ['required']);
		$this->assertEquals($validation->errors(), ['foo' => ['required']]);
		
		// rule passes
		$this->assertEquals($validation->validate('foo', 'baz'), true);
		$this->assertEquals($validation->error('foo'), []);
		$this->assertEquals($validation->errors(), []);
		
		// test value set: fails
		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->error('foo'), ['required']);
		$this->assertEquals($validation->errors(), ['foo' => ['required']]);
		
		// test value set: passes
		$this->assertEquals($validation->validate('foo', 'baz'), true);
		$this->assertEquals($validation->error('foo'), []);
		$this->assertEquals($validation->errors(), []);
		
		// multiple rules
		$validation->rule('bar','is','qux');
		
		// fail
		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->error('foo'), ['required']);
		$this->assertEquals($validation->error('bar'), ['is']);
		$this->assertEquals($validation->errors(), ['foo' => ['required'], 'bar' => ['is']]);
		
		// only one fail
		$this->assertEquals($validation->validate(['bar' => 'qux']), false);
		$this->assertEquals($validation->errors(), ['foo' => ['required']]);
		
		// pass
		$this->assertEquals($validation->validate(['foo'=>'bar', 'bar' => 'qux']), true);
		$this->assertEquals($validation->errors(), []);
	}
	
	
	
	public function testPresets()
	{
		// required
		$validation = $this->make()->rule('foo','required');
		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => '']), false);
		$this->assertEquals($validation->validate(['foo' => null]), false);
		$this->assertEquals($validation->validate(['foo' => []]), false);
		$this->assertEquals($validation->validate(['foo' => false]), false);
		
		$this->assertEquals($validation->validate(['foo' => '0']), true);
		$this->assertEquals($validation->validate(['foo' => '-1']), true);
		$this->assertEquals($validation->validate(['foo' => 'false']), true);
		
		// regex
		$validation = $this->make()->rule('foo','regex', '#[0-9]+#');
		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => '']), false);
		$this->assertEquals($validation->validate(['foo' => null]), false);
		$this->assertEquals($validation->validate(['foo' => []]), false);
		$this->assertEquals($validation->validate(['foo' => false]), false);
		$this->assertEquals($validation->validate(['foo' => 123]), false);
		$this->assertEquals($validation->validate(['foo' => 'abc']), false);
		
		$this->assertEquals($validation->validate(['foo' => '0']), true);
		$this->assertEquals($validation->validate(['foo' => '456356']), true);
		
		
		// max
		$validation = $this->make()->rule('foo','max', '2');

		$this->assertEquals($validation->validate(['foo' => 3]), false);
		$this->assertEquals($validation->validate(['foo' => 'abc']), false);
		$this->assertEquals($validation->validate(['foo' => [1,2,3]]), false);
		
		$this->assertEquals($validation->validate([]), true);
		$this->assertEquals($validation->validate(['foo' => null]), true);
		$this->assertEquals($validation->validate(['foo' => false]), true);
		$this->assertEquals($validation->validate(['foo' => -1]), true);
		$this->assertEquals($validation->validate(['foo' => 0]), true);
		$this->assertEquals($validation->validate(['foo' => 1]), true);
		$this->assertEquals($validation->validate(['foo' => []]), true);
		$this->assertEquals($validation->validate(['foo' => [1,2]]), true);
		$this->assertEquals($validation->validate(['foo' => 'a']), true);
		$this->assertEquals($validation->validate(['foo' => 'ab']), true);
		
		
		// min
		$validation = $this->make()->rule('foo','min', '2');

		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => null]), false);
		$this->assertEquals($validation->validate(['foo' => false]), false);
		$this->assertEquals($validation->validate(['foo' => 1]), false);
		$this->assertEquals($validation->validate(['foo' => 'a']), false);
		$this->assertEquals($validation->validate(['foo' => [1]]), false);
		
		$this->assertEquals($validation->validate(['foo' => 2]), true);
		$this->assertEquals($validation->validate(['foo' => 3]), true);
		$this->assertEquals($validation->validate(['foo' => [1,2]]), true);
		$this->assertEquals($validation->validate(['foo' => [1,2,3]]), true);
		$this->assertEquals($validation->validate(['foo' => 'ab']), true);
		$this->assertEquals($validation->validate(['foo' => 'abc']), true);
		
		
		// length
		$validation = $this->make()->rule('foo','length', '2');

		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => null]), false);
		$this->assertEquals($validation->validate(['foo' => false]), false);
		$this->assertEquals($validation->validate(['foo' => 2]), false);
		$this->assertEquals($validation->validate(['foo' => 'a']), false);
		$this->assertEquals($validation->validate(['foo' => [1]]), false);
		
		$this->assertEquals($validation->validate(['foo' => [1,2]]), true);
		$this->assertEquals($validation->validate(['foo' => 'ab']), true);
		
		// is non strict
		$validation = $this->make()->rule('foo','is', 0, false);

		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => 1]), false);
		$this->assertEquals($validation->validate(['foo' => [1]]), false);

		$this->assertEquals($validation->validate(['foo' => 'abc']), true);
		$this->assertEquals($validation->validate(['foo' => 0]), true);
		$this->assertEquals($validation->validate(['foo' => false]), true);
		$this->assertEquals($validation->validate(['foo' => '']), true);

		
		$validation = $this->make()->rule('foo','is', '123', false);

		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => 1]), false);
		$this->assertEquals($validation->validate(['foo' => 'a']), false);
		$this->assertEquals($validation->validate(['foo' => [123]]), false);

		$this->assertEquals($validation->validate(['foo' => 123]), true);
		$this->assertEquals($validation->validate(['foo' => '123']), true);
		
		
		$validation = $this->make()->rule('foo','is', null, false);
		$this->assertEquals($validation->validate(['foo' => 1]), false);
		$this->assertEquals($validation->validate([]), true);
		
		// is strict
		$validation = $this->make()->rule('foo','is', 0);

		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => 1]), false);
		$this->assertEquals($validation->validate(['foo' => 'a']), false);
		$this->assertEquals($validation->validate(['foo' => [1]]), false);
		$this->assertEquals($validation->validate(['foo' => '']), false);
		$this->assertEquals($validation->validate(['foo' => []]), false);

		$this->assertEquals($validation->validate(['foo' => 0]), true);

		
		$validation = $this->make()->rule('foo','is', '123');

		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => 123]), false);
		$this->assertEquals($validation->validate(['foo' => [123]]), false);

		$this->assertEquals($validation->validate(['foo' => '123']), true);
		
		
		// not non strict
		$validation = $this->make()->rule('foo','not', 0);

		$this->assertEquals($validation->validate(['foo' => 0]), false);
		$this->assertEquals($validation->validate(['foo' => '0']), false);
		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => '']), false);
		$this->assertEquals($validation->validate(['foo' => false]), false);
		
		$this->assertEquals($validation->validate(['foo' => 1]), true);
		

		$validation = $this->make()->rule('foo','not', 123);

		$this->assertEquals($validation->validate(['foo' => 123]), false);
		$this->assertEquals($validation->validate(['foo' => '123']), false);
		
		$this->assertEquals($validation->validate([]), true);
		$this->assertEquals($validation->validate(['foo' => '']), true);
		$this->assertEquals($validation->validate(['foo' => false]), true);
		$this->assertEquals($validation->validate(['foo' => null]), true);

		
		// not strict
		$validation = $this->make()->rule('foo','not', 0, true);

		$this->assertEquals($validation->validate(['foo' => 0]), false);

		$this->assertEquals($validation->validate([]), true);
		$this->assertEquals($validation->validate(['foo' => '0']), true);
		$this->assertEquals($validation->validate(['foo' => '']), true);
		$this->assertEquals($validation->validate(['foo' => 1]), true);
		$this->assertEquals($validation->validate(['foo' => false]), true);

		
		// int
		$validation = $this->make()->rule('foo','int');
		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => true]), false);
		$this->assertEquals($validation->validate(['foo' => []]), false);
		$this->assertEquals($validation->validate(['foo' => '123']), false);
		$this->assertEquals($validation->validate(['foo' => 1.5]), false);
		$this->assertEquals($validation->validate(['foo' => 123]), true);
	}
	
	
	public function testCustom()
	{
		// required
		$validation = $this->make()->rule('foo','custom', function($val){
			return $val === 'bar';
		});
		$this->assertEquals($validation->validate([]), false);
		$this->assertEquals($validation->validate(['foo' => 1]), false);
		$this->assertEquals($validation->validate(['foo' => 'a']), false);
		$this->assertEquals($validation->validate(['foo' => 'bar']), true);
	}
	
}

