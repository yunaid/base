<?php

namespace Base;

class ValidationException extends \Exception {}



class Validation
{

	/**
	 * Validation rules
	 * @var array 
	 */
	protected $rules = [];

	/**
	 * Rules that produced errors
	 * @var array 
	 */
	protected $errors = [];


	/**
	 * Add a rule for a field
	 * Add additional arguments as needed
	 * 
	 * @param string $field
	 * @param string $rule
	 * @return \Base\Validation
	 */
	public function rule($field, $rule)
	{
		if (!isset($this->rules[$field])) {
			$this->rules[$field] = [];
		}
		$this->rules[$field][$rule] = array_slice(func_get_args(), 2);
		
		return $this;
	}


	/**
	 * Validate one or more values
	 * @param string|array $fieldOrValues
	 * @param mixed $value
	 * @return boolean
	 */
	public function validate($fieldOrValues, $value = null)
	{
		// reset errors
		$this->errors = [];

		if (is_array($fieldOrValues)) {
			// start off expecting the best
			$result = true;
			// test all the rules on the provided values
			foreach ($this->rules as $field => $rules) {
				$passed = $this->test($field, isset($fieldOrValues[$field]) ? $fieldOrValues[$field] : null );
				if (!$passed) {
					$result = false;
				}
			}
			return $result;
		} else {
			// test only a single field
			return $this->test($fieldOrValues, $value);
		}
	}


	/**
	 * Test the rules for one field on a value
	 * And store the error(s)
	 * @param string $field
	 * @param mixed $value
	 */
	protected function test($field, $value)
	{
		// assume passed
		$result = true;

		if (isset($this->rules[$field])) {
			// run all the rules that apply to this field
			foreach ($this->rules[$field] as $rule => $params) {
				if (method_exists($this, $rule)) {
					// a predefined rule
					array_unshift($params, $value);
					$passed = call_user_func_array([$this, $rule], $params);
				} elseif (isset($params[0]) && is_callable($params[0])) {
					// a custom rule
					$callable = array_shift($params);
					array_unshift($params, $value);
					$passed = call_user_func_array($callable, $params);
				} else {
					throw new ValidationException('Rule ' . $rule . ' for ' . $field . ' is not predefined and not callable');
				}

				if (!$passed) {
					// flag result and store all encountered errors
					$result = false;
					// create errors array for this field
					if (!isset($this->errors[$field])) {
						$this->errors[$field] = [];
					}
					// add the error
					$this->errors[$field][] = $rule;
				}
			}
		}
		return $result;
	}


	/**
	 * Get errors for a specific field
	 * @param string $field
	 * @return array
	 */
	public function error($field)
	{
		if (isset($this->errors[$field])) {
			return $this->errors[$field];
		}
		return [];
	}


	/**
	 * Get all errors
	 * @return array
	 */
	public function errors()
	{
		return $this->errors;
	}


	/**
	 * Rule: required
	 * @param mixed $value
	 * @return boolean
	 */
	public function required($value)
	{
		if (!isset($value) || $value === null || $value === '' || $value === [] ||  $value === false) {
			return false;
		} else {
			return true;
		}
	}


	/**
	 * Rule: regex
	 * @param string $value
	 * @param string $regex
	 * @return boolean
	 */
	public function regex($value, $regex)
	{
		if(!is_string($value)){
			return false;
		}
		return (boolean) preg_match($regex, (string) $value);
	}


	/**
	 * Rule: max numierc value, array members, or stringlength
	 * @param float|array|string $value
	 * @param float $max
	 * @return boolean
	 */
	public function max($value, $max)
	{
		if (is_numeric($value)) {
			if ((float) $value > $max) {
				return false;
			}
		} elseif (is_array($value)) {
			if (count($value) > $max) {
				return false;
			}
		} elseif (is_string($value)) {
			if(function_exists('mb_strlen')){
				$length = mb_strlen($value);
			} else {
				$length = strlen($value);
			}
			if ($length > $max) {
				return false;
			}
		}
		return true;
	}


	/**
	 * Rule: min numeric value, array members, or stringlength
	 * @param float|array|string $value
	 * @param float $max
	 * @return boolean
	 */
	public function min($value, $min)
	{
		if (is_numeric($value)) {
			if ((float) $value >= $min) {
				return true;
			}
		} elseif (is_array($value)) {
			if (count($value) >= $min) {
				return true;
			}
		} elseif (is_string($value)) {
			if(function_exists('mb_strlen')){
				$length = mb_strlen($value);
			} else {
				$length = strlen($value);
			}
			if ($length >= $min) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Rule: specifix array length or string length
	 * @param array|string $value
	 * @param int $length
	 * @return boolean
	 */
	public function length($value, $length)
	{
		if (is_array($value)) {
			if (count($value) == $length) {
				return true;
			}
		} elseif (is_string($value)) {
			if(function_exists('mb_strlen')){
				$strLength = mb_strlen($value);
			} else {
				$strLength = strlen($value);
			}
			if ($strLength == $length) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Rule: strict / non-strict comparison
	 * @param mixed $value
	 * @param mixed $is
	 * @param boolean $strict
	 * @return boolean
	 */
	public function is($value, $is, $strict = true)
	{
		if ($value === null && $is !== null) {
			return false;
		} elseif ($strict) {
			return $value === $is;
		} else {
			return $value == $is;
		}
	}


	/**
	 * Rule: negative strict / non-strict comparison
	 * @param mixed $value
	 * @param mixed $is
	 * @param boolean $strict
	 * @return boolean
	 */
	public function not($value, $not, $strict = false)
	{
		if ($strict) {
			return $value !== $not;
		} else {
			return $value != $not;
		}
	}


	/**
	 * Rule: check for int
	 * @param mixed $value
	 * @return boolean
	 */
	public function int($value)
	{
		return is_int($value);
	}


	/**
	 * Rule: check for digit
	 * @param mixed $value
	 * @return boolean
	 */
	public function digit($value)
	{
		if(is_string($value) || is_int($value)){
			return ctype_digit((string) $value);
		} else {
			return false;
		}
	}


	/**
	 * Rule: check for alpha
	 * @param mixed $value
	 * @return boolean
	 */
	public function alpha($value)
	{
		if(is_string($value)) {
			return ctype_alpha((string) $value);
		} else {
			return false;
		}
	}


	/**
	 * Rule: check for alphanumeric
	 * @param mixed $value
	 * @return boolean
	 */
	public function alnum($value)
	{
		if(is_string($value) || is_int($value)){
			return ctype_alnum((string) $value);
		} else {
			return false;
		}
	}


	/**
	 * Rule: check for numeric
	 * @param mixed $value
	 * @return boolean
	 */
	public function numeric($value)
	{
		return is_numeric($value);
	}


	/**
	 * Rule: check for email
	 * @param string $value
	 * @return boolean
	 */
	public function email($value)
	{
		return (boolean) filter_var($value, FILTER_VALIDATE_EMAIL);
	}


	/**
	 * Rule: check for url
	 * @param string $value
	 * @return boolean
	 */
	public function url($value)
	{
		return (boolean) filter_var($value, FILTER_VALIDATE_URL);
	}


	/**
	 * Rule: check for ip
	 * @param string $value
	 * @return boolean
	 */
	public function ip($value)
	{
		return (boolean) filter_var($value, FILTER_VALIDATE_IP);
	}


	/**
	 * Rule: check for valid date format
	 * @param int|string $value
	 * @return boolean
	 */
	public function date($value)
	{
		return (strtotime($value) !== false);
	}

}
