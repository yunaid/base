<?php

namespace Base\Cache;

class CacheFileException extends \Exception {};

class File implements \Base\Contract\Cache\Adapter
{
	/**
	 * Spl file info cache dir
	 * @var \SplFileInfo 
	 */
	protected $dir = null;
	
	
	/**
	 * Params
	 * @var array 
	 */
	protected $params = [
		'path' => '___cache___',
		'prefix' => ''
	];


	/**
	 * Construct
	 * @param array $params
	 * @throws \Base\CacheFileException
	 */
	public function __construct(array $params)
	{
		$this->params = array_merge($this->params, $params);
		
		// get dir
		$this->dir = new \SplFileInfo($params['path']);

		// check if readable
		if (!$this->dir->isReadable()) {
			throw new CacheFileException($params['path'] . ' is not readable');
		}

		// check if writable
		if (!$this->dir->isReadable()) {
			throw new CacheFileException($params['path'] . ' is not writable');
		}
	}

	
	/**
	* {@inheritdoc}
	*/
	public function get($name, $default = null)
	{
		// explode on separator
		$parts = explode('.', str_replace(['/', '\\'], '_', $this->params['prefix'].$name));
		// last part is filename
		$filename = array_pop($parts) . '.cache';
		// get directory
		$directory = $this->dir->getRealPath() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR;

		// get file
		$file = new \SplFileInfo($directory . $filename);

		if (!$file->isFile()) {
			// file doesnt exist: return default
			return $default;
		} else {
			// get time created
			$created = $file->getMTime();
			// get data handle
			$data = $file->openFile();
			// lifetime is the first line
			$lifetime = $data->fgets();

			if ($data->eof()) {
				// end of file: cache is corrupted: delete it and return default
				unlink($file->getRealPath());
				return $default;
			}

			// read data lines
			$cache = '';
			while ($data->eof() === false) {
				$cache .= $data->fgets();
			}

			if (($created + (int) $lifetime) < time()) {
				// Expired: delete the file & return default
				$path = $file->getRealPath();
				if (file_exists($path)) {
					unset($data);
					unlink($path);
				}
				return $default;
			} else {
				try {
					$unserialized = unserialize($cache);
				} catch (Exception $e) {
					// Failed to unserialize: delete file and return default
					unlink($file->getRealPath());
					$unserialized = $default;
				}
				return $unserialized;
			}
		}
	}


	/**
	* {@inheritdoc}
	* @throws \Base\CacheFileException
	*/
	public function set($name, $value, $lifetime = 3600)
	{
		// explode on separator
		$parts = explode('.', str_replace(['/', '\\'], '_', $this->params['prefix'].$name));
		// last part is filename
		$filename = array_pop($parts) . '.cache';
		// get directory
		$directory = $this->dir->getRealPath() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR;
		// get dir
		$dir = new \SplFileInfo($directory);

		if (!$dir->isDir()) {
			// create the directory
			if (!mkdir($directory, 0777, TRUE)) {
				throw CacheFileException::factory('unable to create directory : :directory', [':directory' => $directory]);
			}
		}

		// get file
		$resource = new \SplFileInfo($directory . $filename);
		$file = $resource->openFile('w');

		// create data
		$data = $lifetime . "\n" . serialize($value);

		// write it
		$file->fwrite($data, strlen($data));
		$file->fflush();
	}


	/**
	* {@inheritdoc}
	*/
	public function delete($name = '*')
	{
		// explode on separator
		$parts = explode($this->params['separator'], $this->params['prefix'].$name);
		// last part is filename
		$filename = array_pop($parts);
		// get file path
		$path = implode(DIRECTORY_SEPARATOR, $parts);
		// get directory
		$directory = $this->dir->getRealPath() . DIRECTORY_SEPARATOR . $path  . ($path !== '' ? DIRECTORY_SEPARATOR : '');

		if ($filename === '*') {
			// recursively delete entire directory
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator(
					$directory, 
					\FilesystemIterator::SKIP_DOTS
				), 
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ($iterator as $filename => $fileInfo) {
				if ($fileInfo->isDir()) {
					rmdir($filename);
				} else {
					unlink($filename);
				}
			}
		} else {
			if (file_exists($directory . $filename . '.cache')) {
				// only delete file
				unlink($directory . $filename);
			}
		}
	}
}
