<?php
namespace Base;

class ContainerException extends \Exception{};

/**
 * Container that resolves dependencies.
 * 
 * Definitions have to be defined through the set() method
 * 
 * Definitions can be resolved using the get() method
 * 
 * 
 */
class Container
{
	/**
	 * The definitions stored via set
	 * Defitions are stored as ['name' => [definition]]
	 * With multiple calls to set, there can be multiple definitions for one name
	 * These can be used as parents
	 * @var array 
	 */
	protected $definitions = [];
	
	/**
	 * Aliases for long names
	 * Aliases will take precedence over actual definitions
	 * @var array 
	 */
	protected $aliases = [];
			
	/**
	 * Definitions marked as shared
	 * @var array 
	 */
	protected $shared = [];
	
	/**
	 * Definitions marked as grouped
	 * @var array 
	 */
	protected $grouped = [];
	
	/**
	 * Created instances
	 * @var array 
	 */
	protected $instances = [];
	
	/**
	 * Definitions that are resolving
	 * Will hold the remaining parents of a definition so they can be used by a call to parent()
	 * @var array 
	 */
	protected $resolving = [];
	
	
	/**
	 * Add a new definition or definitions
	 * @param array | string $nameOrDefinitions
	 * @param Mixed $definition
	 * @return \Base\Container
	 */
	public function set($nameOrDefinitions, $definition = null)
	{
		// always work with an array
		if($definition !== null){
			// name definition pair given
			$definitions = [$nameOrDefinitions => $definition];
			$names = [$nameOrDefinitions];
			$size = 1;
		} else {
			// assoc array given
			$definitions = $nameOrDefinitions;
			$names = array_keys($definitions);
			$size = count($names);
		}
		// loop throught the array and create an array for each name
		// then store the definition in that array. 
		// With more calls to set, the arrays can fill up with more than one definition
		
		// Use the for loop + array_keys + size for speed
		for ($i=0; $i < $size; $i++) {
			$name = $names[$i];
			if(!isset($this->definitions[$name])){
				$this->definitions[$name] = [];
			}
			$this->definitions[$name][] =  $definitions[$name];
		}
		return $this;
	}

	
	/**
	 * Set alias for a definition or multiple definitions at once
	 * @param string|array $aliasOrAliases
	 * @param string $name
	 * @return \Base\Container
	 */
	public function alias($aliasOrAliases, $name = null)
	{
		if (is_array($aliasOrAliases)) {
			// set aliases
			$this->aliases = array_merge($this->aliases, $aliasOrAliases);
		} else {
			$this->aliases[$aliasOrAliases] = $name;
		}
		return $this;
	}
	
	
	/**
	 * Set definition and mark as as shared
	 * Or mark definitions as shared
	 * @param string|array $nameOrNames
	 * @param mixed $definition
	 * @return \Base\Container
	 */
	public function share($nameOrNames, $definition = null)
	{
		if (is_array($nameOrNames)) {
			// flag them as shared all at once
			$this->shared = array_merge($this->shared, array_fill_keys($nameOrNames, true));
		} else {
			$this->shared[$nameOrNames] = true;
			if($definition !== null){
				$this->set($nameOrNames, $definition);
			}
		}
		return $this;
	}
	
	
	/**
	 * Mark a name as group and provide a default value
	 * Or mark multiple groups with default values
	 * @param string | array $nameOrNames
	 * @param mixed $default
	 * @return \Base\Container
	 */
	public function group($nameOrNames, $default = null)
	{
		if (is_array($nameOrNames)) {
			// flag them as grouped all at once
			$this->grouped = array_merge($this->grouped, $nameOrNames);
		} else {
			$this->grouped[$nameOrNames] = $default;
		}
		return $this;
	}
	
	
	/**
	 * Get an instance, if it is marked shared and exists, get existing
	 * If not: create it
	 * @param string $name
	 * @return mixed
	 */
	public function get()
	{
		$args = func_get_args();
		$name = array_shift($args);
		if(isset($this->aliases[$name])) {
			$name = $this->aliases[$name];
		}
		if (isset($this->definitions[$name])) {
			if(isset($this->grouped[$name])){
				$args[0] = isset($args[0]) ? $args[0] : $this->grouped[$name];
				$instanceName = '__'.$name.'.'.$args[0].'__';
			} else {
				$instanceName = $name;
			}
			if (!isset($this->instances[$instanceName]) || !isset($this->shared[$name])) {
				$this->instances[$instanceName] = $this->create($name, $args);
			}
			return $this->instances[$instanceName];
		} else {
			throw new ContainerException('Definition: "'.$name.'" does not exist');
		}
	}

	
	/**
	 * For the creation of a new instance, even on shared definitions
	 * @param string $name
	 * @return mixed
	 */
	public function make()
	{
		$args = func_get_args();
		$name = array_shift($args);
		if(isset($this->aliases[$name])) {
			$name = $this->aliases[$name];
		}
		if (isset($this->definitions[$name])) {
			if(isset($this->grouped[$name])){
				$args[0] = isset($args[0]) ? $args[0] : $this->grouped[$name];
			}
			return $this->create($name, $args);
		} else {
			throw new ContainerException('Definition: "'.$name.'" does not exist');
		}
	}
	

	/**
	 * When creating an instance, the parents (overwritten) definitions 
	 * will be available in the resolving array, but only during creation
	 * When calling $container->parent('name') from the definition, the next
	 * 'definition' will be consumed
	 * 
	 * @param string $name
	 * @return mixed
	 */
	public function parent()
	{
		$args = func_get_args();
		$name = array_shift($args);
		if(isset($this->aliases[$name])) {
			$name = $this->aliases[$name];
		}
		if (isset($this->resolving[$name]) && ! empty($this->resolving[$name])) {
			// get the parents of the last resolving name
			$parents = array_pop($this->resolving[$name]);
			// pop off the next in line
			$definition = array_pop($parents);
			// re-add the remaining to the resolving names
			$this->resolving[$name][] = $parents;
			// retrieve the instance with an explicit definition, so there wont be
			// any extra additions to 'resolving'
			return $this->create($name, $args, $definition);
		} else {
			throw new ContainerException('Parent of definition: "'.$name.'" does not exist');
		}
	}


	/**
	 * Create a new instance
	 * @param string $name
	 * @return mixed
	 */
	protected function create($name, $args, $definition = null)
	{
		// if definition is not set, the call came from get() of make()
		// if it was set, the call came from parent()
		if($definition === null){
			// get the last added definition
			$definitions = $this->definitions[$name];
			$definition = array_pop($definitions);
			
			// set the remaining definitions (the parents) as resolving, so they can be used 
			// with a call to parent() from the definition
			// multiple calls to the same name (nested calls) are queue'd and resolved in order
			if(isset($this->resolving[$name]) && ! empty($this->resolving[$name])){
				if(count($this->resolving[$name]) >= 10){
					throw new ContainerException('Maximum number of recursive calls exceeded on definition: "'.$name.'"');
				}
				$this->resolving[$name][] = $definitions;
			} else {
				$this->resolving[$name] = [$definitions];
			}
		}
		
		if(is_object($definition) && method_exists($definition, '__invoke')) {
			// run the definition
			switch(count($args)) {
				case 0:
					$instance = $definition($this);
					break;
				case 1:
					$instance = $definition($this, $args[0]);
					break;
				case 2:
					$instance = $definition($this, $args[0], $args[1]);
					break;
				case 3:
					$instance = $definition($this, $args[0], $args[1], $args[2]);
					break;
				default:
					array_unshift($args, $this);
					$instance = call_user_func_array($definition, $args);
			}
		} else {
			// just return the definition
			$instance = $definition;
		}
		
		// after retreival, get rid of the current (the last added) resolver entirely
		array_pop($this->resolving[$name]);
		
		// return the instance
		return $instance;
	}
}