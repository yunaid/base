<?php

class ContainerTest extends PHPUnit_Framework_TestCase
{
	protected function make()
	{
		return new \Base\Container;
	}
	
	
	public function testGet()
	{
		// non closure retrieval
		$container = $this->make();
		$container->set('foo' , 'bar');
		$this->assertEquals('bar', $container->get('foo'));
		
		// run a closure
		$container = $this->make();
		$container->set('foo' , function($container){
			return 'bar';
		});
		$this->assertEquals('bar', $container->get('foo'));
		
		// run a closure with arguments
		$container = $this->make();
		$container->set('foo' , function($container, $arg1, $arg2){
			return $arg1.'-'.$arg2;
		});
		$this->assertEquals('a-b', $container->get('foo', 'a', 'b'));
	}
	
	
	public function testSet()
	{
		// basic setter
		$container = $this->make();
		$container->set('foo' , 'bar');
		$this->assertEquals('bar', $container->get('foo'));
		
		// array setter
		$container = $this->make();
		$container->set([
			'foo1' => 'bar1',
			'foo2' => 'bar2',
		]);
		$this->assertEquals('bar1', $container->get('foo1'));
		$this->assertEquals('bar2', $container->get('foo2'));
	}
	
	
	public function testAlias()
	{
		// basic alias
		$container = $this->make();
		$container->set('foo' , 'bar');
		$container->alias('alias', 'foo');
		$this->assertEquals('bar', $container->get('alias'));
		
		// array setter
		$container = $this->make();
		$container->set([
			'foo1' => 'bar1',
			'foo2' => 'bar2',
		]);
		$container->alias([
			'alias1' => 'foo1',
			'alias2' => 'foo2'
		]);
		
		$this->assertEquals('bar1', $container->get('alias1'));
		$this->assertEquals('bar2', $container->get('alias2'));
		
		
		// test multiple alias
		$container = $this->make();
		$container->set('foo' , 'bar');
		$container->alias('alias1', 'foo');
		$container->alias('alias2', 'foo');
		$this->assertEquals('bar', $container->get('alias1'));
		$this->assertEquals('bar', $container->get('alias2'));
		
		
		// test alias same name
		$container = $this->make();
		$container->set('foo' , 'bar');
		$container->alias('foo', 'foo');
		$this->assertEquals('bar', $container->get('foo'));
		
		// test alias precedence
		$container = $this->make();
		$container->set('foo' , 'bar');
		$container->set('baz' , 'qux');
		$container->alias('baz', 'foo');
		$this->assertEquals('bar', $container->get('baz'));
	}
	

	public function testShare()
	{
		// run a shared closure: first result should be used
		$container = $this->make();
		$container->share('foo', function($container, $arg){
			return $arg;
		});
		$container->get('foo', 'a');
		$this->assertEquals('a', $container->get('foo', 'b'));
		
		// the same but with separate calls
		$container = $this->make();
		$container->share('foo')
		->set('foo', function($container, $arg){
			return $arg;
		});
		$container->get('foo', 'a');
		$this->assertEquals('a', $container->get('foo', 'b'));
		
		
		// the same but with array setter
		$container = $this->make();
		$container
		->share(['foo', 'bar'])
		->set([
			'foo' => function($container, $arg){
				return $arg;
			},
			'bar' => function($container, $arg){
				return $arg;
			}
		]);
		
		$container->get('foo', 'a');
		$container->get('bar', 'a');
		$this->assertEquals('a', $container->get('foo', 'b'));
		$this->assertEquals('a', $container->get('bar', 'b'));
		
	}
	
	
	
	public function testMake()
	{
		// run a closure
		$container = $this->make();
		$container->set('foo' , function($container){
			return 'bar';
		});
		$this->assertEquals('bar', $container->make('foo'));

		
		// run a shared closure: new result should be used
		$container = $this->make();
		$container->share('foo' , function($container, $arg){
			return $arg;
		});
		$container->get('foo', 'a');
		$this->assertEquals('b', $container->make('foo', 'b'));
	}
	

	public function testGroup()
	{
		// run a grouped closure: default value for firest argument will be used
		$container = $this->make();
		$container->group('foo', 'bar')
		->set('foo', function($container, $name){
			return $name;
		});
		$this->assertEquals('bar', $container->get('foo'));
		
		// grouped shared definitions: new names produce new instances once
		$container = $this->make();
		$container
		->group('foo', 'bar')
		->share('foo')
		->set('foo', function($container, $name, $arg = null){
			return $arg;
		});
		$this->assertEquals('a', $container->get('foo', 'bar', 'a'));
		$this->assertEquals('a', $container->get('foo', 'bar', 'b'));
		$this->assertEquals('a', $container->get('foo', 'baz', 'a'));
		$this->assertEquals('a', $container->get('foo', 'baz', 'b'));
		$this->assertEquals('a', $container->get('foo'));
	}
	
	
	public function testNested()
	{
		// nested call
		$container = $this->make();
		$container->set('foo' , function($container){
			return 'bar';
		});
		$container->set('baz' , function($container){
			return $container->get('foo');
		});
		$this->assertEquals('bar', $container->make('baz'));
		
		
	}
	
	
	public function testCircular()
	{
		// circular call throws exception
		$container = $this->make();
		$container->set('foo' , function($container){
			return $container->get('bar');
		});
		$container->set('bar' , function($container){
			return $container->get('foo');
		});
		try{
			$container->get('foo');
		} catch(\Base\ContainerException $e){
			return;
		}
		$this->fail('Circular dependency exception not thrown');
	}
	
	
	public function testNotExisting()
	{
		$container = $this->make();
		try{
			$container->get('foo');
		} catch(\Base\ContainerException $e){
			return;
		}
		$this->fail('Definition not set exception not thrown');
	}
	
	
	
	public function testNotExistingParent()
	{
		$container = $this->make();
		$container->set('foo' , function($container){
			return $container->parent('foo');
		});
		try{
			$container->get('foo');
		} catch(\Base\ContainerException $e){
			return;
		}
		$this->fail('Parent not set exception not thrown');
	}
	
	
	
	public function testInheritance()
	{
		// basic overwrite
		$container = $container = $this->make();
		$container->set('foo', 'bar1');
		$container->set('foo', 'bar2');
		$this->assertEquals('bar2', $container->get('foo'));

		
		// basic inheritance
		$container = $container = $this->make();
		$container->set('foo', 'bar');
		$container->set('foo', function($container){
			return $container->parent('foo');
		});
		$this->assertEquals('bar', $container->get('foo'));

		
		//multiple levels inheritance
		$container = $container = $this->make();
		$container->set('foo', 'bar');
		$container->set('foo', function($container){
			return $container->parent('foo').'-1';
		});
		$container->set('foo', function($container){
			return $container->parent('foo').'-2';
		});
		$this->assertEquals('bar-1-2', $container->get('foo'));
		
		
		// nested inheritance
		$container = $container = $this->make();
		$container->set('foo', function($container, $arg){
			return $arg;
		});
		$container->set('foo', function($container, $arg = null){
			if($arg === null) {
				return $container->get('foo','default');
			}
			return $container->parent('foo', $arg);
		});
		$this->assertEquals('default', $container->get('foo'));
	}
	
	
	public function testAliasedSharedGroupedNestedInheritance()
	{
		$container = $container = $this->make();
		
		$container
		->group('foo', 'bar')
		->share('foo')
		->set('foo', function($container, $name, $arg){
			return $arg;
		})
		->set('foo', function($container, $name, $arg = null){
			if($arg === null) {
				return $container->get('foo', $name, 'default');
			}
			return $container->parent('foo', $name, $arg);
		})
		->alias('alias', 'foo');
		
		$this->assertEquals('default', $container->get('alias'));
		$this->assertEquals('default', $container->get('alias', 'bar'));
		$this->assertEquals('default', $container->get('alias', 'bar' ,'a'));
		
		$this->assertEquals('a', $container->get('alias', 'baz', 'a'));
		$this->assertEquals('a', $container->get('alias', 'baz', 'b'));
	}
}
