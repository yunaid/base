<?php

namespace Base;

class Form
{

	protected static $counter = 0;
	protected $valdiation = null;
	protected $request = null;
	protected $elementFactory = null;
	protected $built = false;
	protected $processed = false;
	protected $submitted = false;
	protected $valid = false;
	protected $id = 0;
	protected $action = '';
	protected $method = 'POST';
	protected $enctype = 'multipart/form-data';
	protected $attributes = [];
	protected $fields = [];
	protected $elements = [];
	protected $rules = [];
	protected $values = [];


	public function __construct($validation, $request, $elementFactory)
	{
		$this->id = ++static::$counter;
		$this->validation = $validation;
		$this->request = $request;
		$this->elementFactory = $elementFactory;
	}


	public function id($id = null)
	{
		if ($id === null) {
			return $this->id;
		} else {
			$this->id = $id;
			return $this;
		}
	}


	public function action($action = null)
	{
		if ($action === null) {
			return $this->action;
		} else {
			$this->action = $action;
			return $this;
		}
	}


	public function method($method = null)
	{
		if ($method === null) {
			return $this->method;
		} else {
			$this->method = strtoupper($method);
			return $this;
		}
	}


	public function enctype($enctype = null)
	{
		if ($enctype === null) {
			return $this->enctype;
		} else {
			$this->enctype = $enctype;
			return $this;
		}
	}


	public function attributes($attributes = null)
	{
		if ($attributes === null) {
			return $this->attributes;
		} else {
			// merge class attribute
			if (isset($this->attributes['class']) && isset($attributes['class'])) {
				$attributes['class'] = $this->attributes['class'] . ' ' . $attributes['class'];
			}
			// merge all attrbiutes
			$this->attributes = array_merge($this->attributes, $attributes);
			return $this;
		}
	}


	public function element($key, $typeOrIndex = null, $params = [])
	{
		if ($typeOrIndex === null) {
			if (isset($this->fields[$key])) {
				if ($this->fields[$key]['group'] === true) {
					return $this->fields[$key]['elements'];
				} else {
					return $this->_fields[$key]['elements'][0];
				}
			} else {
				return null;
			}
		} elseif (is_int($typeOrIndex)) {
			if (isset($this->fields[$key]) && isset($this->fields[$key]['elements'][$typeOrIndex])) {
				return $this->fields[$key]['elements'][$typeOrIndex];
			} else {
				return null;
			}
		} else {
			$type = $typeOrIndex;

			$element = $this->elementFactory->__invoke($key, $type, $params, $this);

			$this->elements[] = $element;

			if (isset($this->fields[$key])) {
				$element->index(count($this->fields[$key]['elements']));
				$this->fields[$key]['set'] = true;
				$this->fields[$key]['elements'][] = $element;
			} else {
				// create new field
				$this->fields[$key] = [
					'multiple' => isset($params['multiple']) ? $params['multiple'] : ($type == 'checkbox'),
					'set' => false,
					'keys' => isset($params['keys']) ? $params['keys'] : false,
					'elements' => [$element],
					'default' => isset($params['default']) ? $params['default'] : null,
					'required' => false,
				];
			}
			return $element;
		}
	}


	public function group($type, $callable, $params = [])
	{
		// store curent elements
		$elements = $this->elements;

		// new empty elements array
		$this->elements = [];

		// call callable and let it register more elements or groups
		if ($callable instanceof \Closure) {
			$callable($this);
		}

		// callable done: add the current elements as a group to the stored elements
		$elements[] = (object) array_merge($params, [
				'group' => true,
				'type' => $type,
				'elements' => $this->elements,
		]);

		// restore the previous elements
		$this->elements = $elements;
	}


	public function rule($key, $rule)
	{
		if ($rule === 'required') {
			if (isset($this->fields[$key])) {
				$this->fields[$key]['required'] = true;
			}
		}
		call_user_func_array([$this->validation, 'rule'], func_get_args());
	}


	protected function init()
	{
		if ($this->built === false) {
			// build only once
			$this->built = true;

			// put hidden field with form id
			$this->element('form_' . $this->id . '_submitted', 'hidden');
			$this->value('form_' . $this->id . '_submitted', '1');

			// call the user defined build function
			if (method_exists($this, 'build')) {
				$this->build($this);
			}
		}
	}


	public function process()
	{
		if ($this->built === false) {
			$this->init();
		}

		if ($this->processed === false) {
			// prcess only once
			$this->processed = true;

			// check if submitted
			if ($this->method === 'POST') {
				$this->submitted = $this->request->post('form_' . $this->id . '_submitted', false) == '1';
			} else {
				$this->submitted = $this->request->query('form_' . $this->id . '_submitted', false) == '1';
			}

			if ($this->submitted) {
				if ($this->method === 'POST') {
					$this->values($this->request->post(), true);
				} else {
					$this->values($this->request->query(), true);
				}
				$this->validate();
			}
		}
	}


	public function submitted()
	{
		if ($this->processed === false) {
			$this->process();
		}
		return $this->submitted;
	}


	public function valid()
	{
		if ($this->processed === false) {
			$this->process();
		}
		return $this->valid;
	}


	public function elements()
	{
		if ($this->built === false) {
			$this->init();
		}
		return $this->elements;
	}


	public function errors($key = null)
	{
		if ($this->processed === false) {
			$this->process();
		}
		$errors = $this->validation->errors();

		if ($key === null) {
			return $errors;
		} elseif (isset($errors[$key])) {
			return $errors[$key];
		} else {
			return [];
		}
	}


	public function multiple($key)
	{
		if ($this->built === false) {
			$this->init();
		}
		if (isset($this->fields[$key])) {
			return $this->fields[$key]['multiple'];
		} else {
			return false;
		}
	}


	public function set($key)
	{
		if ($this->built === false) {
			$this->init();
		}
		if (isset($this->fields[$key])) {
			return $this->fields[$key]['set'];
		} else {
			return false;
		}
	}


	public function required($key)
	{
		if ($this->built === false) {
			$this->init();
		}
		if (isset($this->fields[$key])) {
			return $this->fields[$key]['required'];
		} else {
			return false;
		}
	}


	public function values($values = null, $complete = false)
	{
		if ($values === null) {

			if ($this->processed === false) {
				$this->process();
			}
			$result = [];
			foreach ($this->fields as $key => $field) {
				if (is_array($field['keys'])) {
					// flatten subkeys as main keys
					foreach ($field['keys'] as $subkey) {
						$result[$subkey] = $this->values[$key][$subkey];
					}
				} else {
					$result[$key] = $this->values[$key];
				}
			}
			return $result;
		} else {

			if ($this->built === false) {
				$this->init();
			}

			foreach ($this->fields as $key => $field) {
				if (isset($values[$key])) {
					// just set it
					$this->value($key, $values[$key]);
				} elseif ($complete) {
					// force values on all defined fields

					if (is_array($field['keys'])) {
						// create a compound element from provied and default values
						$value = [];
						foreach ($field['keys'] as $subkey) {
							if (isset($values[$subkey])) {
								$value[$subkey] = $values[$subkey];
							} elseif (is_array($field['default']) && isset($field['default'][$subkey])) {
								$value[$subkey] = $field['default'][$subkey];
							} else {
								$value[$subkey] = '';
							}
						}
						$this->value($key, $value);
					} elseif ($field['default'] !== null) {
						// get provided default value
						$this->value($key, $field['default']);
					} elseif ($field['multiple']) {
						// set empty array
						$this->value($key, []);
					} else {
						// set empty string
						$this->value($key, '');
					}
				}
			}
		}
	}


	public function value($key, $value = null)
	{
		if ($value === null) {
			if ($this->processed === false) {
				$this->process();
			}

			if (isset($this->values[$key])) {
				// just return the value
				return $this->values[$key];
			} else {
				// try to find a subkey
				foreach ($this->fields as $superkey => $field) {
					if (is_array($field['keys']) && in_array($key, $field['keys'])) {
						return $this->values[$superkey][$key];
					}
				}
				// didnt work out
				return null;
			}
		} else {

			if ($this->built === false) {
				$this->init();
			}

			// set value
			if (isset($this->fields[$key])) {
				if (($this->fields[$key]['multiple'] || $this->fields[$key]['set']) && !is_array($value)) {
					$this->values[$key] = [$value];
				} else {
					$this->values[$key] = $value;
				}
			} else {
				foreach ($this->fields as $superkey => $field) {
					if (is_array($field['keys']) && in_array($key, $field['keys'])) {
						$this->values[$superkey][$key] = $value;
					}
				}
			}
		}
	}


	protected function validate()
	{
		if ($this->processed === false) {
			$this->process();
		}
		$this->valid = $this->validation->validate($this->values);
	}

}
