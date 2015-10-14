<?php

namespace Base\Log;

class LogFileException extends \Exception{}

class File
{
	
	/**
	 * Params
	 * @var array 
	 */
	protected $params = [
		'path' => null
	];
	
	
	/**
	 * Cosntructor
	 * Overwrite params
	 * @param array $params
	 */
	public function __construct(array $params = [])
	{     
		$this->params = array_merge($this->params, $params);
	}

	
	/**
	 * Writes each of the messages into the log file. The log file will be
	 * appended to the `YYYY/MM/DD.log.php` file, where YYYY is the current
	 * year, MM is the current month, and DD is the current day.
	 *
	 * @param   string $message
	 */
	public function write($message)
	{
		if ($this->params['path'] === null) {
			throw new LogFileException('Log path not set');
		}
		
		if (!is_dir($this->params['path']) || !is_writable($this->params['path'])) {
			throw new LogFileException('Log path: ' . $this->params['path'] . ' is not writable');
		}

		// Set the yearly directory name
		$directory = $this->params['path'] . date('Y');

		if (!is_dir($directory)) {
			// Create the yearly directory
			mkdir($directory, 02777);
			// Set permissions (must be manually set to fix umask issues)
			chmod($directory, 02777);
		}

		// Add the month to the directory
		$directory.= DIRECTORY_SEPARATOR . date('m');

		if (!is_dir($directory)) {
			// Create the monthly directory
			mkdir($directory, 02777);
			// Set permissions (must be manually set to fix umask issues)
			chmod($directory, 02777);
		}

		// Set the name of the log file
		$filename = $directory . DIRECTORY_SEPARATOR . date('d') . '.log';

		if (!file_exists($filename)) {
			// Create the log file
			file_put_contents($filename, '');
			// Allow anyone to write to log files
			chmod($filename, 0666);
		}
		file_put_contents($filename, PHP_EOL . $message, FILE_APPEND);
	}
}