<?php

namespace Base\View;

class Part
{

	// filename
	protected $file = null;
	// variables
	protected $data = [];
	// part id
	protected $id = null;
	// view object
	protected $view = null;
	// part rendered blocks
	protected $blocks = [];


	public function make($file, $data, $id, $view)
	{
		return new self($file, $data, $id, $view);
	}


	public function __construct($file, $data, $id, $view)
	{
		$this->file = $file;
		$this->data = $data;
		$this->id = $id;
		$this->view = $view;
	}


	public function id()
	{
		return $this->id;
	}


	public function data()
	{
		return $this->data;
	}


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


	public function render()
	{
		return $this->view->render($this->id, $this->file, $this->data, $this->blocks);
	}


	/**
	 * Set value
	 * @param Mixed $key
	 * @param Mixed $value
	 * @return \Base\View\Part
	 */
	public function set($key, $value = null)
	{
		if (is_array($key)) {
			foreach ($key as $name => $value) {
				$this->data[$name] = $value;
			}
		} else {
			$this->data[$key] = $value;
		}
		return $this;
	}


	/**
	 * Bind value
	 * @param String $key
	 * @param Mixed $value
	 * @return \Base\View
	 */
	public function bind($key, & $value)
	{
		$this->data[$key] = & $value;
		return $this;
	}


	/**
	 * append a value to a var that was allready set
	 * create a new var if var doesn't exist
	 */
	public function append($var, $append)
	{
		$val = isset($this->data[$var]) ? $this->data[$var] : null;

		if ($val === null) {
			$this->set($var, $append);
		} elseif (is_string($val)) {
			$this->set($var, $val . $append);
		} elseif (is_array($val)) {
			$val[] = $append;
			$this->set($var, $val);
		}
	}

}
