<?php

class RouterTest extends PHPUnit_Framework_TestCase
{
	protected function make()
	{
		return new \Base\Router($this->request());
	}
	
	
	public function testUriToParams()
	{
		$router = $this->make();
		
		/*
		 * Basic matching
		 */
		
		// non matching
		$this->assertEquals(
			false, 
			$router->params(':foo', '')
		);

		// optional variable
		$this->assertEquals(
			[], 
			$router->params('(:foo)', '')
		);

		// variable
		$this->assertEquals(
			['foo' => 'bar'], 
			$router->params(':foo', 'bar')
		);

		// optional supplied variable
		$this->assertEquals(
			['foo' => 'bar'], 
			$router->params('(:foo)', 'bar')
		);

		// non matching
		$this->assertEquals(
			false, 
			$router->params(':foo1/:foo2', 'bar1')
		);

		// more variables
		$this->assertEquals(
			['foo1' => 'bar1', 'foo2' => 'bar2'], 
			$router->params(':foo1/:foo2', 'bar1/bar2')
		);

		// non matching (missing /)
		$this->assertEquals(
			false, 
			$router->params(':foo1/(:foo2)', 'bar1')
		);

		// also optional / matches
		$this->assertEquals(
			['foo1' => 'bar1'], 
			$router->params(':foo1(/:foo2)', 'bar1')
		);
		
		// nested optionals
		$this->assertEquals(
			['foo1' => 'bar1', 'foo2' => 'bar2'], 
			$router->params(':foo1(/:foo2(/:foo3))', 'bar1/bar2')
		);
		
		// nested optionals unmathcing nesting
		$this->assertEquals(
			false, 
			$router->params(':foo1(/prefix2_:foo2(/prefix3_:foo3))', 'bar1/prefix3_bar3')
		);
		
		// more complicated optionals
		$this->assertEquals(
			['foo1' => 'bar1'], 
			$router->params('(:foo1(/))(:foo2)', 'bar1')
		);
		$this->assertEquals(
			['foo1' => 'bar1'], 
			$router->params('(:foo1(/))(:foo2)', 'bar1/')
		);
		$this->assertEquals(
			['foo1' => 'bar1', 'foo2' => 'bar2'], 
			$router->params('(:foo1(/))(:foo2)', 'bar1/bar2')
		);
		
		
		/*
		 * Conditions
		 */
		
		// foo can only be 'baz'
		$this->assertEquals(
			false, 
			$router->params(':foo', 'bar', ['foo' => 'baz'])
		);

		// condition met
		$this->assertEquals(
			['foo' => 'bar'], 
			$router->params(':foo', 'bar', ['foo' => 'bar'])
		);
		
		// regex condition doesnt match
		$this->assertEquals(
			false, 
			$router->params(':foo', 'bar', ['foo' => '\d+'])
		);
		
		// regex matches
		$this->assertEquals(
			['foo' => '1234'], 
			$router->params(':foo', '1234', ['foo' => '\d+'])
		);
		
		// longer regex fails
		$this->assertEquals(
			false, 
			$router->params(':foo', 'ba1234', ['foo' => 'ba[0-9]+r'])
		);
		
		// longer regex matches
		$this->assertEquals(
			['foo' => 'ba1234r'], 
			$router->params(':foo', 'ba1234r', ['foo' => 'ba[0-9]+r'])
		);
		
		// second regex doenst match
		$this->assertEquals(
			false, 
			$router->params(':foo1/:foo2', 'bar', ['foo1' => '\w+', 'foo2' => '\d+'])
		);
		
		// optional param missing: regex is ignored
		$this->assertEquals(
			['foo1' => 'bar'], 
			$router->params(':foo1(/:foo2)', 'bar', ['foo1' => '\w+', 'foo2' => '\d+'])
		);
		
		// optional param available: regex is appllied
		$this->assertEquals(
			false, 
			$router->params(':foo1(/:foo2)', 'bar/baz', ['foo1' => '\w+', 'foo2' => '\d+'])
		);
				

		/*
		 * Defaults
		 */
		
		// route still has to match
		$this->assertEquals(
			false, 
			$router->params(':foo', '', [], ['foo'=> 'bar'])
		);
		
		// only works with optional
		$this->assertEquals(
			['foo' => 'bar'], 
			$router->params('(:foo)', '', [], ['foo'=> 'bar'])
		);
		
		// defaults are always appended
		$this->assertEquals(
			['foo' => 'bar', 'baz'=> 'qux'], 
			$router->params(':foo', 'bar', [], ['baz'=> 'qux'])
		);
	}
	
	
	public function testParamsToUri()
	{
		$router = $this->make();
		
		// basic replacement
		$this->assertEquals(
			'bar', 
			$router->uri(':foo', ['foo' => 'bar'])
		);
		$this->assertEquals(
			'bar1/bar2', 
			$router->uri(':foo1/:foo2', ['foo1' => 'bar1', 'foo2' => 'bar2'])
		);
		// optionals
		$this->assertEquals(
			'bar1/', 
			$router->uri(':foo1/(:foo2)', ['foo1' => 'bar1'])
		);
		$this->assertEquals(
			'bar1', 
			$router->uri(':foo1(/:foo2)', ['foo1' => 'bar1'])
		);
		
		// optionals without params are included
		$this->assertEquals(
			'bar/', 
			$router->uri(':foo(/)', ['foo' => 'bar'])
		);
		// optionals without params inside optionals with missing params are not included
		$this->assertEquals(
			'bar1', 
			$router->uri(':foo1(/:foo2(/))', ['foo1' => 'bar1'])
		);
	}
	
	
	
	public function testMissingParam() {
		$router = $this->make();
        try {
			$router->uri(':foo', []);
		} catch (Exception $expected) {
            return;
        }
        $this->fail('Expected missing parameter exception not thrown');
    }
	
	
	
	
	public function testParse()
	{
		
	}
	
	
	public function testBuild()
	{
		
	}
	
	
	public function testUrl()
	{
		
	}
	
	
	public function testGarbage()
	{
		
	}
	
			/*
		  $router->route(
		  'test',
		  ':foo',
		  'command',
		  []
		  );
		 */

	protected function request($server = [], $get = [], $post = [])
	{
		$request = new \Base\HTTP\Request(array_merge([
			'REQUEST_METHOD' => 'GET',
			'HTTPS' => 'off',
			'SERVER_NAME' => 'test.com',
			'SERVER_PORT' => '80',
			'SCRIPT_NAME' => 'test.php',
			'REQUEST_URI' => '',
			'QUERY_STRING' => '',
			'REMOTE_ADDR' => 'remote',
			'HTTP_USER_AGENT' => 'test',
			], $server), $get, new \Base\Arr($post));
		
		return $request;
	}

}
