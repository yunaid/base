<?php

namespace Base\Exception;

class Handler
{
	/**
	 * Callable that is called when handling
	 * @var Callable 
	 */
	protected $callback = null;
	
	/**
	 * Whether we are already handling an exception (to prevent endless exception handling)
	 * @var boolean 
	 */
	protected $handling = false;

	
	/**
	 * Set a callback
	 * @param Callable $callback
	 */
	public function callback(Callable $callback)
	{
		// only initialize once
		if ($this->callback === null) {
			
			$this->callback = $callback;

			// register exception handler
			set_exception_handler([$this, 'handle']);

			// register error handler
			set_error_handler(function($code, $error, $file = null, $line = null) {
				$this->handle(new \ErrorException($error, $code, 0, $file, $line));
			});

			// register fatal error handler
			register_shutdown_function(function() {
				if ($error = error_get_last()) {
					if (in_array($error['type'], [E_PARSE, E_ERROR, E_USER_ERROR])) {
						// rerout as errorexception
						$this->handle(new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));
						// Actually shut down
						exit(1);
					}
				}
			});
		}
	}

	
	/**
	 * Handler that is called when an uncaught exception is thrown
	 * @param \Exception $exception
	 */
	public function handle(\Exception $exception)
	{
		// clean buffer
		for ($i = 0; $i < ob_get_level(); $i++) {
			ob_end_clean();
		}

		if ($this->handling === false) {
			
			// start handling
			$this->handling = true;

			// call callback
			if (is_callable($this->callback)) {
				call_user_func($this->callback, $exception);
			}

			//done
			exit(1);
		} else {
			// something went sour. Stop right here.
			echo '<!-- Error handling exception: ';
			echo $exception->getMessage();
			echo ' -->';
			exit(1);
		}
	}
}
