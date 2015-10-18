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
		$this->assertEquals(false, $validation->validate('foo', null));
		$this->assertEquals(['required'], $validation->error('foo'));
		$this->assertEquals(['foo' => ['required']], $validation->errors());
		
		// rule passes
		$this->assertEquals(true, $validation->validate('foo', 'baz'));
		$this->assertEquals([], $validation->error('foo'));
		$this->assertEquals([], $validation->errors());
		
		// test value set: fails
		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(['required'], $validation->error('foo'));
		$this->assertEquals(['foo' => ['required']], $validation->errors());
		
		// test value set: passes
		$this->assertEquals(true, $validation->validate('foo', 'baz'));
		$this->assertEquals([], $validation->error('foo'));
		$this->assertEquals([], $validation->errors());
		
		// multiple rules
		$validation->rule('bar','is','qux');
		
		// fail
		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(['required'], $validation->error('foo'));
		$this->assertEquals(['is'], $validation->error('bar'));
		$this->assertEquals(['foo' => ['required'], 'bar' => ['is']], $validation->errors());
		
		// only one fail
		$this->assertEquals(false, $validation->validate(['bar' => 'qux']));
		$this->assertEquals(['foo' => ['required']], $validation->errors());
		
		// pass
		$this->assertEquals(true, $validation->validate(['foo'=>'bar', 'bar' => 'qux']));
		$this->assertEquals([], $validation->errors());
	}
	
	
	
	public function testPresets()
	{
		// required
		$validation = $this->make()->rule('foo','required');
		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(false, $validation->validate(['foo' => '']));
		$this->assertEquals(false, $validation->validate(['foo' => null]));
		$this->assertEquals(false, $validation->validate(['foo' => []]));
		$this->assertEquals(false, $validation->validate(['foo' => false]));
		
		$this->assertEquals(true, $validation->validate(['foo' => '0']));
		$this->assertEquals(true, $validation->validate(['foo' => '-1']));
		$this->assertEquals(true, $validation->validate(['foo' => 'false']));
		
		// regex
		$validation = $this->make()->rule('foo','regex', '#[0-9]+#');
		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(false, $validation->validate(['foo' => '']));
		$this->assertEquals(false, $validation->validate(['foo' => null]));
		$this->assertEquals(false, $validation->validate(['foo' => []]));
		$this->assertEquals(false, $validation->validate(['foo' => false]));
		$this->assertEquals(false, $validation->validate(['foo' => 123]));
		$this->assertEquals(false, $validation->validate(['foo' => 'abc']));
		
		$this->assertEquals(true, $validation->validate(['foo' => '0']));
		$this->assertEquals(true, $validation->validate(['foo' => '456356']));
		
		
		// max
		$validation = $this->make()->rule('foo','max', '2');

		$this->assertEquals(false, $validation->validate(['foo' => 3]));
		$this->assertEquals(false, $validation->validate(['foo' => 'abc']));
		$this->assertEquals(false, $validation->validate(['foo' => [1,2,3]]));
		
		$this->assertEquals(true, $validation->validate([]));
		$this->assertEquals(true, $validation->validate(['foo' => null]));
		$this->assertEquals(true, $validation->validate(['foo' => false]));
		$this->assertEquals(true, $validation->validate(['foo' => -1]));
		$this->assertEquals(true, $validation->validate(['foo' => 0]));
		$this->assertEquals(true, $validation->validate(['foo' => 1]));
		$this->assertEquals(true, $validation->validate(['foo' => []]));
		$this->assertEquals(true, $validation->validate(['foo' => [1,2]]));
		$this->assertEquals(true, $validation->validate(['foo' => 'a']));
		$this->assertEquals(true, $validation->validate(['foo' => 'ab']));
		
		
		// min
		$validation = $this->make()->rule('foo','min', '2');

		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(false, $validation->validate(['foo' => null]));
		$this->assertEquals(false, $validation->validate(['foo' => false]));
		$this->assertEquals(false, $validation->validate(['foo' => 1]));
		$this->assertEquals(false, $validation->validate(['foo' => 'a']));
		$this->assertEquals(false, $validation->validate(['foo' => [1]]));
		
		$this->assertEquals(true, $validation->validate(['foo' => 2]));
		$this->assertEquals(true, $validation->validate(['foo' => 3]));
		$this->assertEquals(true, $validation->validate(['foo' => [1,2]]));
		$this->assertEquals(true, $validation->validate(['foo' => [1,2,3]]));
		$this->assertEquals(true, $validation->validate(['foo' => 'ab']));
		$this->assertEquals(true, $validation->validate(['foo' => 'abc']));
		
		
		// length
		$validation = $this->make()->rule('foo','length', '2');

		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(false, $validation->validate(['foo' => null]));
		$this->assertEquals(false, $validation->validate(['foo' => false]));
		$this->assertEquals(false, $validation->validate(['foo' => 2]));
		$this->assertEquals(false, $validation->validate(['foo' => 'a']));
		$this->assertEquals(false, $validation->validate(['foo' => [1]]));
		
		$this->assertEquals(true, $validation->validate(['foo' => [1,2]]));
		$this->assertEquals(true, $validation->validate(['foo' => 'ab']));
		
		// is non strict
		$validation = $this->make()->rule('foo','is', 0, false);

		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(false, $validation->validate(['foo' => 1]));
		$this->assertEquals(false, $validation->validate(['foo' => [1]]));

		$this->assertEquals(true, $validation->validate(['foo' => 'abc']));
		$this->assertEquals(true, $validation->validate(['foo' => 0]));
		$this->assertEquals(true, $validation->validate(['foo' => false]));
		$this->assertEquals(true, $validation->validate(['foo' => '']));

		
		$validation = $this->make()->rule('foo','is', '123', false);

		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(false, $validation->validate(['foo' => 1]));
		$this->assertEquals(false, $validation->validate(['foo' => 'a']));
		$this->assertEquals(false, $validation->validate(['foo' => [123]]));

		$this->assertEquals(true, $validation->validate(['foo' => 123]));
		$this->assertEquals(true, $validation->validate(['foo' => '123']));
		
		
		$validation = $this->make()->rule('foo','is', null, false);
		$this->assertEquals(false, $validation->validate(['foo' => 1]));
		$this->assertEquals(true, $validation->validate([]));
		
		// is strict
		$validation = $this->make()->rule('foo','is', 0);

		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(false, $validation->validate(['foo' => 1]));
		$this->assertEquals(false, $validation->validate(['foo' => 'a']));
		$this->assertEquals(false, $validation->validate(['foo' => [1]]));
		$this->assertEquals(false, $validation->validate(['foo' => '']));
		$this->assertEquals(false, $validation->validate(['foo' => []]));

		$this->assertEquals(true, $validation->validate(['foo' => 0]));

		
		$validation = $this->make()->rule('foo','is', '123');

		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(false, $validation->validate(['foo' => 123]));
		$this->assertEquals(false, $validation->validate(['foo' => [123]]));

		$this->assertEquals(true, $validation->validate(['foo' => '123']));
		
		
		// not non strict
		$validation = $this->make()->rule('foo','not', 0);

		$this->assertEquals(false, $validation->validate(['foo' => 0]));
		$this->assertEquals(false, $validation->validate(['foo' => '0']));
		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(false, $validation->validate(['foo' => '']));
		$this->assertEquals(false, $validation->validate(['foo' => false]));
		
		$this->assertEquals(true, $validation->validate(['foo' => 1]));
		

		$validation = $this->make()->rule('foo','not', 123);

		$this->assertEquals(false, $validation->validate(['foo' => 123]));
		$this->assertEquals(false, $validation->validate(['foo' => '123']));
		
		$this->assertEquals(true, $validation->validate([]));
		$this->assertEquals(true, $validation->validate(['foo' => '']));
		$this->assertEquals(true, $validation->validate(['foo' => false]));
		$this->assertEquals(true, $validation->validate(['foo' => null]));

		
		// not strict
		$validation = $this->make()->rule('foo','not', 0, true);

		$this->assertEquals(false, $validation->validate(['foo' => 0]));

		$this->assertEquals(true, $validation->validate([]));
		$this->assertEquals(true, $validation->validate(['foo' => '0']));
		$this->assertEquals(true, $validation->validate(['foo' => '']));
		$this->assertEquals(true, $validation->validate(['foo' => 1]));
		$this->assertEquals(true, $validation->validate(['foo' => false]));
	}
	
	
	public function testCustom()
	{
		// required
		$validation = $this->make()->rule('foo','custom', function($val){
			return $val === 'bar';
		});
		$this->assertEquals(false, $validation->validate([]));
		$this->assertEquals(false, $validation->validate(['foo' => 1]));
		$this->assertEquals(false, $validation->validate(['foo' => 'a']));
		$this->assertEquals(true, $validation->validate(['foo' => 'bar']));
	}
	
}

