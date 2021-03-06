<?php

namespace Base;

use \Base\View\Engine as Engine;

class View implements \JsonSerializable
{
	/**
	 * Unique id for all the view files
	 * @var int 
	 */
	protected static $uid = 0;
	
	/**
	 * File name
	 * @var string 
	 */
	protected $file = null;
	
	/**
	 * Data that can be used
	 * @var array 
	 */
	protected $data = [];
	
	/**
	 * Id of the this file instance
	 * @var int 
	 */
	protected $id = null;
	
	/**
	 * The view engine
	 * @var \Base\View\Engine
	 */
	protected $engine = null;
	
	/**
	 * Blocks
	 * @var array 
	 */
	protected $blocks = [];
	
	/**
	 * Rendered string, used to cache a view
	 * @var string 
	 */
	protected $rendered = null;

	
	/**
	 * New View
	 * @param \Base\View\Engine $engine
	 * @param string $file
	 * @param array $data
	 */
	public function __construct(Engine $engine, $file, array $data = [])
	{
		// generate and assign unique id
		static::$uid++;
		$this->id = static::$uid;
		
		$this->engine = $engine;
		$this->file = $file;
		$this->data = $data;
	}

	
	/**
	 * Set value or values
	 * @param string|array $keyOrValues
	 * @param mixed $value
	 * @return \Base\View
	 */
	public function set($keyOrValues, $value = null)
	{
		if (is_array($keyOrValues)) {
			$this->data = array_merge($this->data, $keyOrValues);
		} else {
			$this->data[$keyOrValues] = $value;
		}
		return $this;
	}


	/**
	 * Bind value
	 * @param string $key
	 * @param mixed $value
	 * @return \Base\View
	 */
	public function bind($key, & $value)
	{
		$this->data[$key] = & $value;
		return $this;
	}
	

	/**
	 * Append a value to a var that was allready set
	 * @param string $var
	 * @param mixed $append
	 */
	public function append($var, $append)
	{
		$val = isset($this->data[$var]) ? $this->data[$var] : null;

		if ($val === null) {
			$this->set($var, $append);
		} elseif (is_string($val)) {
			$this->set($var, $val . (string) $append);
		} elseif (is_array($val)) {
			$val[] = $append;
			$this->set($var, $val);
		}
	}
	
	
	/**
	 * Let the engine render this view
	 * There might be a pre-rendered version, when the view object was serialized (see __sleep())
	 * If that's the case: use that version
	 * @return string
	 */
	public function render()
	{
		if($this->rendered !== null) {
			return $this->rendered;
		} else {
			return $this->engine->render($this->id, $this->file, $this->data, $this->blocks);
		}
	}

	
	/**
	 * Autorendering
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}
	
	
	/**
	 * When serializing, render the view and store the result
	 * This might happen when someone caches the unrendered view
	 * @return array
	 */
	public function __sleep()
	{
		$this->rendered = $this->render();
		return ['rendered'];
	}
	
	
	/**
	 * When json_encoding return the rendered result
	 * @return string
	 */
	public function jsonSerialize() 
	{
      return $this->render();
    }
	
	
	/**
	 * Get id, used by engine
	 * @return int
	 */
	public function id()
	{
		return $this->id;
	}

	
	/**
	 * Get data, used by engine
	 * @return array
	 */
	public function data()
	{
		return $this->data;
	}
	
	
	/**
	 * Get file, used by engine
	 * @return string
	 */
	public function file()
	{
		return $this->file;
	}

	
	/**
	 * Set and get blocks, used by engine
	 * @param string $name Name of the block
	 * @param string $value Contents of the block
	 * @return null|string
	 */
	public function block($name, $value = null)
	{
		if ($value === null) {
			if (isset($this->blocks[$name])) {
				return $this->blocks[$name];
			} else {
				return null;
			}
		} else {
			$this->blocks[$name] = $value;
		}
	}
}
