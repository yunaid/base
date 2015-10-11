<?php

namespace Base;

class Validation
{

	protected $rules = [];
	protected $errors = [];


	public function rule($field, $rule)
	{
		if (!isset($this->rules[$field])) {
			$this->rules[$field] = [];
		}
		$this->rules[$field][$rule] = array_slice(func_get_args(), 2);
	}


	public function validate($fieldOrValues, $value = null)
	{
		$result = true;
		if (is_array($fieldOrValues)) {
			$this->errors = [];
			foreach ($this->rules as $field => $rules) {
				$passed = $this->validate($field, isset($fieldOrValues[$field]) ? $fieldOrValues[$field] : null );
				if (!$passed) {
					$result = false;
				}
			}
		} else {
			$field = $fieldOrValues;
			if (isset($this->rules[$field])) {
				foreach ($this->rules[$field] as $rule => $params) {
					if (method_exists($this, $rule)) {
						array_unshift($params, $value);
						$passed = call_user_func_array([$this, $rule], $params);
					} elseif (isset($params[0]) && is_callable($params[0])) {
						$callable = array_shift($params);
						array_unshift($params, $value);
						$passed = call_user_func_array($callable, $params);
					}

					if (!$passed) {
						$result = false;

						if (!isset($this->errors[$field])) {
							$this->errors[$field] = [];
						}
						$this->errors[$field][] = $rule;
					}
				}
			}
		}
		return $result;
	}


	public function error($field)
	{
		if (isset($this->errors[$field])) {
			return $this->errors[$field];
		}
		return [];
	}


	public function errors()
	{
		return $this->errors;
	}


	public function required($value)
	{
		if (!isset($value) || $value === null || $value === '' || $value === []) {
			return false;
		} else {
			return true;
		}
	}


	public function regex($value, $regex)
	{
		return (boolean) preg_match($regex, (string) $value);
	}


	public function max($value, $max)
	{
		if (is_numeric($value)) {
			if ((float) $value <= $max) {
				return true;
			}
		} elseif (is_array($value)) {
			if (count($value) <= $max) {
				return true;
			}
		} elseif (is_string($value)) {
			if (mb_strlen($value) <= $max) {
				return true;
			}
		}
		return false;
	}


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
			if (mb_strlen($value) >= $min) {
				return true;
			}
		}
		return false;
	}


	public function length($value, $length)
	{
		if (is_array($value)) {
			if (count($value) == $length) {
				return true;
			}
		} elseif (is_string($value)) {
			if (mb_strlen($value) == $length) {
				return true;
			}
		}
		return false;
	}


	public function is($value, $is, $strict = true)
	{
		if ($strict) {
			return $value === $is;
		} else {
			return $value == $is;
		}
	}


	public function not($value, $not, $strict = false)
	{
		if ($strict) {
			return $value !== $not;
		} else {
			return $value != $not;
		}
	}


	public function int($value)
	{
		return is_int($value);
	}


	public function digit($value)
	{
		return ctype_digit((string) $value);
	}


	public function alpha($value)
	{
		return ctype_alpha((string) $value);
	}


	public function alnum($value)
	{
		return ctype_alnum((string) $value);
	}


	public function numeric($value)
	{
		return is_numeric($value);
	}


	public function email($value)
	{
		return (boolean) filter_var($value, FILTER_VALIDATE_EMAIL);
	}


	public function url($value)
	{
		return (boolean) filter_var($value, FILTER_VALIDATE_URL);
	}


	public function ip($value)
	{
		return (boolean) filter_var($value, FILTER_VALIDATE_IP);
	}


	public function date($value)
	{
		return (strtotime($value) !== false);
	}

}
