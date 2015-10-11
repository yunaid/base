<?php

namespace Base\Form;

class Element
{
	protected $key = null;
	protected $type = null;
	protected $params = [];
	protected $form = null;
	protected $index = 0;

	public function __construct($key, $type, $params, $form)
	{
		$this->key = $key;
		$this->type = $type;
		$this->params = $params;
		$this->form = $form;
	}
	
	
	public function index($index)
	{
		$this->index = $index;
	}
	
	public function value($value)
	{
		$this->form->value($this->key, $value);
	}
	
	
	public function __get($name)
	{
		switch($name){
			case 'name':
				// check with the form if this is a multiple or a set
				if($this->form->multiple($this->key) || $this->form->set($this->key)){
					return $this->key.'[]';
				} else {
					return $this->key;
				}
			case 'key':
			case 'type':
			case 'params':
				// just return the value
				return $this->{$name};
			case 'required':
				// only let the first element in a set get the required property
				return $this->index === 0 &&  $this->form->required($this->key);
			case 'value':
				$value = $this->form->value($this->key);
				if(is_array($value) && $this->form->set($this->key) && isset($value[$this->index])){
					// get the nth value in a set
					return $value[$this->index];
				} elseif(is_array($value) && $this->form->set($this->key)) {
					return '';
				} else {
					return $value;
				}
			case 'error':
				$errors = $this->form->errors($this->key);
				// get first error, but only if element is the first in a set
				if(is_array($errors) && isset($errors[0]) && $this->index === 0){
					return $errors[0];
				} else {
					return null;
				}
			case 'errors':
				$errors = $this->form->errors($this->key);
				// get errors, but only if element is the first in a set
				if($this->index === 0){
					return $errors;
				} else {
					return null;
				}
			default:
				// return a user defined param
				if(isset($this->params[$name])){
					return $this->params[$name];
				} else {
					return null;
				}
		}
	}
	
}

