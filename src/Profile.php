<?php

namespace Base;

class Profile
{
	/**
	 * Start time
	 * @var int 
	 */
	protected $start = 0;
	
	/**
	 * Active profiling groups
	 * @var array
	 */
	protected $profiling = [];
	
	/**
	 * All profiler groups
	 * @var array 
	 */
	protected $groups = [];

	
	/**
	 * A start time is passed to the profiler
	 * Or take the current time when no values is passed
	 * @param int $start
	 */
	public function __construct($start = null)
	{
		$this->start = $start === null ? microtime(true) : $start;
	}


	/**
	 * Start a profiling session
	 * Returns a token that can be used to end the session
	 * @param string $group
	 * @param array $data additional data
	 * @return string 
	 */
	public function start($group, array $data = [])
	{
		$token = md5(microtime() . '_' . rand(0, 1000000));

		if (!is_array($data)) {
			$data = [$data];
		}

		$this->profiling[$token] = [
			'group' => $group,
			'data' => $data,
			'start' => [
				'time' => microtime(true),
				'memory' => memory_get_usage()
			]
		];
		return $token;
	}


	/**
	 * End a profiling session
	 * When no profiling token is passed, the data from the start of the object will be returned
	 * @param string $token
	 * @return array|void
	 */
	public function end($token = null)
	{
		if ($token === null) {
			return [
				(microtime(true) - $this->start) . ' s',
				(memory_get_peak_usage() / 1000) . ' Kb'
			];
		}

		if (isset($this->profiling[$token])) {
			$group = $this->profiling[$token]['group'];
			$data = $this->profiling[$token]['data'][] = (microtime(true) - $this->profiling[$token]['start']['time']) . ' s';
			$data = $this->profiling[$token]['data'][] = ((memory_get_usage() - $this->profiling[$token]['start']['memory']) / 1000) . ' Kb';
			if (!isset($this->groups[$group])) {
				$this->groups[$group] = [];
			}
			$this->groups[$group][] = $this->profiling[$token]['data'];
			unset($this->profiling[$token]);
		}
	}

	
	/**
	 * return all the profiling data that was gathered
	 * @return array
	 */
	public function data()
	{
		return $this->groups;
	}
}