<?php

namespace Base;

use \Base\Validation as Validation;
use \Base\HTTP\Request as Request;

class Form
{
	/**
	 * Unique id for each form
	 * @var int 
	 */
	protected static $uid = 0;
	
	/**
	 * Validation object
	 * @var \Base\Validation 
	 */
	protected $valdiation = null;
	
	/**
	 * Request object
	 * @var \Base\HTTP\Request
	 */
	protected $request = null;
	
	/**
	 * Closure to create elements
	 * @var \Closure 
	 */
	protected $elementFactory = null;
	
	/**
	 * Wheter the form was built or not
	 * @var boolean 
	 */
	protected $built = false;
	
	/**
	 * Wheter the form was precessed or not
	 * @var boolean 
	 */
	protected $processed = false;
	
	/**
	 * Was the form submitted
	 * @var boolean 
	 */
	protected $submitted = false;
	
	/**
	 * Is the form valid
	 * @var boolean 
	 */
	protected $valid = false;
	
	/**
	 * Form id
	 * @var int 
	 */
	protected $id = 0;
	
	/**
	 * Form action
	 * @var string 
	 */
	protected $action = '';
	
	/**
	 * Form method
	 * @var string 
	 */
	protected $method = 'POST';
	
	/**
	 * Form enctype
	 * @var string 
	 */
	protected $enctype = 'multipart/form-data';
	
	/**
	 * Other form attributes
	 * @var array 
	 */
	protected $attributes = [];
	
	/**
	 * Form fields, holds elements, default values, etc.
	 * @var array 
	 */
	protected $fields = [];
	
	/**
	 * List of elements. An elements can also have nested elements
	 * @var array 
	 */
	protected $elements = [];
	
	/**
	 * Registered rules
	 * @var array 
	 */
	protected $rules = [];
	
	/**
	 * Values by key
	 * @var array 
	 */
	protected $values = [];


	/**
	 * Create a new form
	 * @param \Base\Validation $validation
	 * @param \Base\HTTP\Request $request
	 * @param \Closure $elementFactory
	 */
	public function __construct(Validation $validation, Request $request, \Closure $elementFactory)
	{
		$this->id = ++static::$uid;
		$this->validation = $validation;
		$this->request = $request;
		$this->elementFactory = $elementFactory;
	}

	
	/**
	 * Get or set id
	 * @param int|null $id
	 * @return \Base\Form|int
	 */
	public function id($id = null)
	{
		if ($id === null) {
			return $this->id;
		} else {
			$this->id = $id;
			return $this;
		}
	}

	
	/**
	 * Get or set action
	 * @param string|null $action
	 * @return \Base\Form|string
	 */
	public function action($action = null)
	{
		if ($action === null) {
			return $this->action;
		} else {
			$this->action = $action;
			return $this;
		}
	}


	/**
	 * Get or set method
	 * @param string|null $method
	 * @return \Base\Form|string
	 */
	public function method($method = null)
	{
		if ($method === null) {
			return $this->method;
		} else {
			$this->method = strtoupper($method);
			return $this;
		}
	}

	
	/**
	 * Get or set enctype
	 * @param string|null $enctype
	 * @return \Base\Form|string
	 */
	public function enctype($enctype = null)
	{
		if ($enctype === null) {
			return $this->enctype;
		} else {
			$this->enctype = $enctype;
			return $this;
		}
	}

	/**
	 * Get or set attributes
	 * @param array|null $attributes
	 * @return \Base\Form|array
	 */
	public function attributes(array $attributes = null)
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

	
	/**
	 * Add an element
	 * @param string $key
	 * @param string|int $typeOrIndex
	 * @param array $params
	 * @return null|array|\Base\Form\Element
	 */
	public function element($key, $typeOrIndex = null, array $params = [])
	{
		if ($typeOrIndex === null) {
			// get element without index
			if (isset($this->fields[$key])) {
				if ($this->fields[$key]['group'] === true) {
					// get entire group
					return $this->fields[$key]['elements'];
				} else {
					// get first element
					return $this->_fields[$key]['elements'][0];
				}
			} else {
				return null;
			}
		} elseif (is_int($typeOrIndex)) {
			// get indexed element
			if (isset($this->fields[$key]) && isset($this->fields[$key]['elements'][$typeOrIndex])) {
				return $this->fields[$key]['elements'][$typeOrIndex];
			} else {
				return null;
			}
		} else {
			// create element
			$type = $typeOrIndex;
			$element = $this->elementFactory->__invoke($key, $type, $params, $this);
			// store it
			$this->elements[] = $element;

			if (isset($this->fields[$key])) {
				// element with the same key was already present
				// So it is a set
				// set the index in the element
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

	
	/**
	 * Create a group of elements
	 * @param string $type
	 * @param \Closure $function
	 * @param array $params
	 */
	public function group($type, \Closure $function, array $params = [])
	{
		// store curent elements
		$elements = $this->elements;

		// new empty elements array
		$this->elements = [];

		// call callable and let it register more elements or groups
		if (is_object($function) && method_exists($function, '__invoke')) {
			$function($this);
		}

		// callable done: add the current elements as a group to the stored elements
		// use an object that can pass as an element (its has ->type)
		$elements[] = (object) array_merge($params, [
			'group' => true,
			'type' => $type,
			'elements' => $this->elements,
		]);

		// restore the previous elements
		$this->elements = $elements;
	}

	
	/**
	 * Add a rule
	 * @param string $key
	 * @param string $rule
	 */
	public function rule($key, $rule)
	{
		if ($rule === 'required') {
			if (isset($this->fields[$key])) {
				$this->fields[$key]['required'] = true;
			}
		}
		call_user_func_array([$this->validation, 'rule'], func_get_args());
	}


	/**
	 * Check if the form was submitted
	 * @return boolean
	 */
	public function submitted()
	{
		if ($this->processed === false) {
			$this->process();
		}
		return $this->submitted;
	}


	/**
	 * Check if the validation passes
	 * @return boolean
	 */
	public function valid()
	{
		if ($this->processed === false) {
			$this->process();
		}
		return $this->valid;
	}


	/**
	 * Get elements
	 * @return array
	 */
	public function elements()
	{
		if ($this->built === false) {
			$this->init();
		}
		return $this->elements;
	}


	/**
	 * Get the errors for a key or all errors
	 * @param string|null $key
	 * @return array
	 */
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


	/**
	 * Check if an element can contain multiple values
	 * @param string $key
	 * @return boolean
	 */
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


	/**
	 * Check if element is part of a set
	 * @param string $key
	 * @return boolean
	 */
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


	/**
	 * Check if element is required
	 * @param string $key
	 * @return boolean
	 */
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

	
	/**
	 * Get or set the values
	 * @param array|null $values
	 * @param boolean $complete Use defaults or '' to fill up non-supplied values
	 * @return array|void
	 */
	public function values($values = null, $complete = false)
	{
		if ($values === null) {
			// get values
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
			// set values
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


	/**
	 * Get or set a value
	 * @param string $key
	 * @param null|string|int|array $value
	 * @return string|array|void
	 */
	public function value($key, $value = null)
	{
		if ($value === null) {
			// get value
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
				// search for a subkey
				foreach ($this->fields as $superkey => $field) {
					if (is_array($field['keys']) && in_array($key, $field['keys'])) {
						$this->values[$superkey][$key] = $value;
					}
				}
			}
		}
	}
	
	
	/**
	 * Start using form
	 * If the form was not built, build it
	 */
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
				$this->build();
			}
		}
	}

	
	/**
	 * Process the form
	 */
	protected function process()
	{
		if ($this->built === false) {
			$this->init();
		}

		if ($this->processed === false) {
			// process only once
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

	
	/**
	 * Validate the form
	 */
	protected function validate()
	{
		if ($this->processed === false) {
			$this->process();
		}
		$this->valid = $this->validation->validate($this->values);
	}
}
