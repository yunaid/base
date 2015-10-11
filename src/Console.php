<?php

namespace Base;

class Console
{

	protected $nr = 1;
	protected $profile = null;
	protected $request = null;
	protected $router = null;
	protected $log = [];
	protected $trace = null;
	protected $exception = null;


	/**
	 * Initialize console
	 * @param \Base\Profile $profile
	 * @param \Base\Request $request
	 * @param \Base\Router $router
	 */
	public function __construct($profile = null, $request = null, $router = null)
	{
		$this->profile = $profile;
		$this->request = $request;
		$this->router = $router;
	}


	/**
	 * Dump variables
	 */
	public function dump()
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$index = 0;
		foreach ($trace as $key => $call) {
			if ($call['function'] == 'trace' || $call['function'] == 'dump') {
				$index = $key;
			}
		}
		$data = [];
		foreach (func_get_args() as $part) {
			$data [] = $this->renderVar($part);
		}
		if (isset($trace[$index]['file'])) {
			$data [] = $trace[$index]['file'] . ' line: ' . $trace[$index]['line'];
		}
		$this->log [] = $data;
	}


	/**
	 * Dump variables an stop script execution
	 */
	public function stop()
	{
		call_user_func_array([$this, 'dump'], func_get_args());
		$this->trace = debug_backtrace();
		echo $this->render('dump');
		exit;
	}


	/**
	 * Dump variables, create a stack trace and stop script execution
	 */
	public function trace()
	{
		call_user_func_array([$this, 'dump'], func_get_args());
		$this->trace = debug_backtrace();
		echo $this->render('trace');
		exit;
	}


	/**
	 * Render an exception and stop script execution
	 * @param \Exception $exception
	 */
	public function exception($exception)
	{
		$this->exception = $exception;
		echo $this->render('error');
	}


	/**
	 * Render the console
	 * @param Mixed $active
	 * @return String
	 */
	public function render($active = false)
	{
		$show = $active ? 'true' : 'false';
		$active = $active ? $active : 'log';
		$buttons = '';
		$groups = '';



		// exception group
		if ($this->exception !== null) {
			$buttons .= str_replace(['{{group}}', '{{label}}'], ['error', 'Error'], $this->templateButton);
			$this->nr = -1;
			$trace = $this->exception->getTrace();
			$first = array_shift($trace);
			$show = 'true';

			$rows = $this->renderRow([
				'<br /><span class="base-console-error">' . get_class($this->exception) . ': ' . strip_tags($this->exception->getMessage()) . '</span><br /><br />'
				], false);

			$rows .= $this->renderRow([
				$this->renderSource($this->exception->getFile(), $this->exception->getLine())
				], false);


			foreach ($this->renderTrace($trace) as $call) {
				$rows .= $this->renderRow($call);
			}

			$groups .= str_replace(['{{group}}', '{{rows}}'], ['error', $rows], $this->templateGroup);
		}




		// trace group
		if ($this->trace !== null) {
			$buttons .= str_replace(['{{group}}', '{{label}}'], ['trace', 'Trace'], $this->templateButton);
			$this->nr = 1;
			$rows = '';
			$show = 'true';
			foreach ($this->renderTrace($this->trace) as $call) {
				$rows .= $this->renderRow($call);
			}

			$groups .= str_replace(['{{group}}', '{{rows}}'], ['trace', $rows], $this->templateGroup);
		}




		// log group
		$buttons .= str_replace(['{{group}}', '{{label}}'], ['log', 'Dump'], $this->templateButton);
		$this->nr = 1;
		$rows = '';
		foreach ($this->log as $log) {
			$show = 'true';
			$rows .= $this->renderRow($log);
		}
		$groups .= str_replace(['{{group}}', '{{rows}}'], ['log', $rows], $this->templateGroup);





		// application group
		$buttons .= str_replace(['{{group}}', '{{label}}'], ['application', 'Application'], $this->templateButton);
		$this->nr = 1;
		$application = $this->profile->end();
		$rows = $this->renderRow(['Time elapsed', $application[0]], false);
		$rows .= $this->renderRow(['Memory peak', $application[1]], false);
		$rows .= $this->renderRow(['Request', $this->renderVar($this->request->data())], false);
		$rows .= $this->renderRow(['Params', $this->renderVar($this->request->params())], false);
		$rows .= $this->renderRow(['GET', $this->renderVar($this->request->query())], false);
		$rows .= $this->renderRow(['POST', $this->renderVar($this->request->post())], false);

		$groups .= str_replace(['{{group}}', '{{rows}}'], ['application', $rows], $this->templateGroup);





		// includes group
		$buttons .= str_replace(['{{group}}', '{{label}}'], ['included', 'Included'], $this->templateButton);
		$this->nr = 1;
		$rows = '';
		$included = get_included_files();
		foreach ($included as $file) {
			$rows .= $this->renderRow([$file]);
		}
		$groups .= str_replace(['{{group}}', '{{rows}}'], ['included', $rows], $this->templateGroup);




		// profile data
		foreach ($this->profile->data() as $group => $entries) {
			$buttons .= str_replace(['{{group}}', '{{label}}'], [$group, ucfirst($group)], $this->templateButton);
			$this->nr = 1;
			$rows = '';
			foreach ($entries as $entry) {
				$rows .= $this->renderRow($entry);
			}
			$groups .= str_replace(['{{group}}', '{{rows}}'], [$group, $rows], $this->templateGroup);
		}


		// output
		return str_replace(['{{buttons}}', '{{groups}}', '{{show}}', '{{active}}'], [$buttons, $groups, $show, $active], $this->template);
	}


	/**
	 * Render a table row in the console
	 * @param Array $cols
	 * @param Boolean $nr Show line number
	 * @return String
	 */
	protected function renderRow($cols = [], $nr = true)
	{
		if (!is_array($cols)) {
			$cols = [$cols];
		}

		// first values of the replace
		$replace = [
			$nr ? $this->nr : '',
			$this->nr % 2 == 0 ? 'even' : 'odd'
		];

		// add row to replace
		$row = '';

		$counter = 0;
		foreach ($cols as $col) {
			$counter++;
			if ($counter === count($cols)) {
				$colspan = 'colspan="' . (20 - count($cols)) . '"';
			} else {
				$colspan = '';
			}
			if (!is_string($col) && !is_int($col)) {
				$col = $this->renderVar($col);
			}
			$row .= '<td ' . $colspan . '>' . $col . '</td>';
		}

		// add to replace
		$replace[] = $row;

		// create html
		$html = str_replace(
			['{{nr}}', '{{oddeven}}', '{{row}}'], $replace, $this->templateRow
		);

		// increment for next call
		$this->nr++;

		return $html;
	}


	/**
	 * var_dump with markup and depth limit
	 * @staticvar type $marker
	 * @staticvar array $objects
	 * @param Mixed $var
	 * @param int $length
	 * @param int $limit
	 * @param int $level
	 * @return String
	 */
	protected function renderVar($var, $length = 128, $limit = 3, $level = 0)
	{
		$output = '';

		if ($var === null) {
			$output = '<small>NULL</small>';
		} elseif (is_bool($var)) {
			$output = '<small>bool</small> ' . ($var ? 'true' : 'false');
		} elseif (is_float($var)) {
			$output = '<small>float</small> ' . $var;
		} elseif (is_resource($var)) {
			if (($type = get_resource_type($var)) === 'stream' && $meta = stream_get_meta_data($var)) {
				$meta = stream_get_meta_data($var);
				if (isset($meta['uri'])) {
					$file = $meta['uri'];
					$output = '<small>resource</small><span>(' . $type . ')</span> ' . htmlspecialchars($file, ENT_NOQUOTES);
				}
			} else {
				$output = '<small>resource</small><span>(' . $type . ')</span>';
			}
		} elseif (is_string($var)) {
			// Encode the string
			$str = htmlspecialchars($var, ENT_NOQUOTES);
			$output = '<small>string</small><span>(' . strlen($var) . ')</span> "' . $str . '"';
		} elseif (is_array($var)) {

			$output = [];
			$space = str_repeat($s = '    ', $level);

			static $marker;
			if ($marker === null) {
				$marker = uniqid("\x00");
			}

			if (empty($var)) {
				// Do nothing
			} elseif (isset($var[$marker])) {
				$output[] = "(\n" . $space . $s . "*RECURSION*\n" . $space . ")";
			} elseif ($level < $limit) {
				$output[] = '<span>(';
				$var[$marker] = true;
				foreach ($var as $key => & $val) {
					if ($key === $marker) {
						continue;
					}
					if (!is_int($key)) {
						$key = '"' . htmlspecialchars($key, ENT_NOQUOTES) . '"';
					}
					$output [] = $space . $s . $key . ' => ' . $this->renderVar($val, $length, $limit, $level + 1);
				}
				unset($var[$marker]);

				$output[] = $space . ')</span>';
			} else {
				// Depth too great
				$output[] = "(\n" . $space . $s . "...\n" . $space . ")";
			}
			$output = '<small>array</small><span>(' . count($var) . ')</span> ' . implode("\n", $output);
		} elseif (is_object($var)) {

			$array = (array) $var;
			$output = [];
			$space = str_repeat($s = '    ', $level);
			$hash = spl_object_hash($var);

			static $objects = [];

			if (empty($var)) {
				// Do nothing
			} elseif (isset($objects[$hash])) {
				$output[] = "{\n" . $space . $s . "*RECURSION*\n" . $space . "}";
			} elseif ($level < $limit) {
				$output[] = "<code>{";

				$objects[$hash] = TRUE;
				foreach ($array as $key => & $val) {
					if ($key[0] === "\x00") {
						// Determine if the access is protected or protected
						$access = '<small>' . (($key[1] === '*') ? 'protected' : 'private') . '</small>';

						// Remove the access level from the variable name
						$key = substr($key, strrpos($key, "\x00") + 1);
					} else {
						$access = '<small>public</small>';
					}
					$output[] = $space . $s . $access . ' ' . $key . ' => ' . $this->renderVar($val, $length, $limit, $level + 1);
				}
				unset($objects[$hash]);

				$output[] = $space . '}</code>';
			} else {
				// Depth too great
				$output[] = "{\n" . $space . $s . "...\n" . $space . "}";
			}

			$output = '<small>object</small> <span>' . get_class($var) . '(' . count($array) . ')</span> ' . implode("\n", $output);
		} else {
			$output = '<small>' . gettype($var) . '</small> ' . htmlspecialchars(print_r($var, TRUE), ENT_NOQUOTES);
		}

		if ($level === 0) {
			return '<pre>' . $output . '</pre>';
		} else {
			return $output;
		}
	}


	/**
	 * Render a stacktrace into nicer chunk of HTML
	 * @param Array $trace
	 * @return Array
	 */
	public function renderTrace($trace)
	{
		$statements = ['include', 'include_once', 'require', 'require_once'];

		$output = [];

		foreach ($trace as $step) {
			// the call that will be passed to renderRow later on
			$call = [
				'source' => '',
				'function' => '',
			];

			// get source
			if (isset($step['file']) && isset($step['line'])) {
				$call['source'] = $this->renderSource($step['file'], $step['line']);
			}

			// get function
			if (isset($step['class'])) {
				$call['function'] = $step['class'] . $step['type'] . $step['function'];
			} else {
				$call['function'] = $step['function'];
			}

			// get arguments: will be appended to $call
			if ($step['function'] == 'call_user_func_array') {
				// TODO: some useful info here?
			} elseif (in_array($step['function'], $statements) && isset($step['args'])) {
				$call = array_merge($call, [ $this->renderVar($step['args'][0])]);
			} elseif (isset($step['args'])) {
				if (!function_exists($step['function']) || strpos($step['function'], '{closure}') !== false) {
					$params = null;
				} else {
					if (isset($step['class'])) {
						if (method_exists($step['class'], $step['function'])) {
							$reflection = new \ReflectionMethod($step['class'], $step['function']);
						} elseif (method_exists($step['class'], '__call')) {
							$reflection = new \ReflectionMethod($step['class'], '__call');
						} else {
							$reflection = new \ReflectionMethod($step['class'], '__callStatic');
						}
					} else {
						$reflection = new \ReflectionFunction($step['function']);
					}
					$params = $reflection->getParameters();
				}

				$args = [];
				foreach ($step['args'] as $i => $arg) {
					if (isset($params[$i])) {
						$args[$params[$i]->name] = $this->renderVar($arg);
					} else {
						$args[$i] = $this->renderVar($arg);
					}
				}
				$call = array_merge($call, $args);
			}
			$output[] = $call;
		}
		return $output;
	}


	/**
	 * Render lines of a file
	 * @param String $file
	 * @param int $linenr
	 * @param int $padding
	 * @return string
	 */
	public function renderSource($file, $linenr, $padding = 5)
	{
		$output = '<span class="base-console-source-file">' . $file . '</span><br />';


		if (!$file || !is_readable($file)) {
			return $output;
		}

		$file = fopen($file, 'r');
		$line = 0;
		$range = ['start' => $linenr - $padding, 'end' => $linenr + $padding];
		// Set the zero-padding amount for line numbers
		$format = '% ' . strlen($range['end']) . 'd';

		$source = '';
		while (($row = fgets($file)) !== false) {

			if (++$line > $range['end'])
				break;

			if ($line >= $range['start']) {
				$row = htmlspecialchars($row, ENT_NOQUOTES);
				$row = '<span class="base-console-source-line-number">' . sprintf($format, $line) . '</span> ' . $row;

				if ($line === $linenr) {
					$row = '<span class="base-console-source-line base-console-source-highlight">' . $row . '</span>';
				} else {
					$row = '<span class="base-console-source-line">' . $row . '</span>';
				}
				$source .= $row;
			}
		}
		// Close the file
		fclose($file);
		return $output . '<pre class="base-console-source"><code>' . $source . '</code></pre>';
	}

	/**
	 * Templates using {{var}}, use with str_replace
	 */
	protected $template = <<<BASE
<style>
			
	
	#base-console{
		position: fixed;
		z-index: 100000;
		width: 100%;
		left: 0;
		top: 100%;
		margin-top: -40px;
		opacity: 0.9;
	}
	#base-console pre{
		font-family: monospace;
	}
	.base-console-header{
		background: black;
		width: 100%;
		height: 40px;
	}
	
	.base-console-button {
		display: block;
		float: left;
		eight: 18px;
		line-height: 18px;
		margin: 5px;
		padding: 5px;
		border: 1px solid white;
		color: white;
		font-family: arial;
		font-size: 13px;
		border-radius: 5px;
		cursor: pointer;
	}
	
	.base-console-button-minimize, 
	.base-console-button-close
	{
		float:right;
	}
	
	.base-console-button-minimize{
		display: none;
	}
	
	.base-console-body{
		display: block;
		width: 100%;
		height: 100%;
		overflow: auto;
		background-color: #DDD;
		font-family: arial;
	}
	.base-console-body table{
		font-size: 13px;
		border-spacing: 0;
	}
	
	.base-console-group .odd{
		background-color: #FFF;
	}
	
	.base-console-group .even{
		background-color: #EEE;
	}

	.base-console-group td{
		margin: 0;
		padding: 6px;
		border-right: 1px solid #EEE;
		line-height: 1em;
		font-size: 100%;
		text-align: left;
	}
		

	.base-console-linenr{
		width: 20px;
		color: white;
		text-align: center;
		background-color: #777;
	}
	
	.base-console-group .even .base-console-linenr{
		background-color: #333;
	}
		
	.base-console-source{
		background-color: #000;
	}
		
	.base-console-source-file{
		
	}
	.base-console-error{
		font-size: 20px;
		color: #990000;
	}
		
		
	.base-console-source-line{
		color: FFF;
	}
		
	.base-console-source-highlight{
		color: FF9999;
		background-color: #555;
	}
		

</style>

<div id="base-console">
	<div class="base-console-header">
		{{buttons}}
		<span class="base-console-button base-console-button-close">&times;</span>
		<span class="base-console-button base-console-button-minimize">&minus;</span>
	</div>
	<div class="base-console-body">
		{{groups}}
	</div>
</div>

<script>
	(function(w,d){
		// set the height
		d.querySelector('.base-console-body').style.height = w.innerHeight - 100 + 'px';
			
		// hide all content
		var groups = d.querySelectorAll('.base-console-group');
		for(var i=0;i<groups.length; i++){
			groups[i].style.display = 'none';
		}
		
		// add listeners to buttons
		var buttons = d.querySelectorAll('.base-console-button');
		for(var i=0;i<buttons.length; i++){
			buttons[i].onclick = function(){
			
				// move console up
				d.querySelector('#base-console').style.top = '100px';
			
				// show minimize button
				d.querySelector('.base-console-button-minimize').style.display = 'block';
			
				// turn all buttons white
				var buttons = d.querySelectorAll('.base-console-button');
				for(var i=0;i<buttons.length; i++){
					buttons[i].style.color = 'white';
					buttons[i].style.borderColor = 'white';
				}
				
				// color the clicked button
				this.style.color = '#99CCFF';
				this.style.borderColor = '#99CCFF';
				
				// hide all the content groups
				var groups = d.querySelectorAll('.base-console-group');
				for(var i=0;i<groups.length; i++){
					groups[i].style.display = 'none';
				}
				
				// show the group corresponding to the button
				var group = this.getAttribute('data-for');
				var groups = d.querySelectorAll('[data-id='+group+']');
				groups[0].style.display = 'block';
			};
		}

		// minimize button
		d.querySelector('.base-console-button-minimize').onclick = function(){
			d.querySelector('#base-console').style.top = '100%';
			d.querySelector('.base-console-button-minimize').style.display = 'none';
		};
		
		// close button
		d.querySelector('.base-console-button-close').onclick = function(){
			var node = 	d.querySelector('#base-console');
			node.parentNode.removeChild(node);
		};
			
		// start with open console on a defined tab
		var show = {{show}};
		if(show){
			d.querySelector('.base-console-button[data-for={{active}}]').onclick();
		}
	})(window,document);
</script>
BASE;
	protected $templateButton = <<<BASE
<span class="base-console-button" data-for="{{group}}">{{label}}</span>
BASE;
	protected $templateGroup = <<<BASE
<div class="base-console-group" data-id="{{group}}">
	<table width="100%">
		{{rows}}
	</table>
</div>
BASE;
	protected $templateRow = <<<BASE
<tr class="base-console-row {{oddeven}}">
	<td class="base-console-linenr">{{nr}}</td>{{row}}
</tr>
BASE;

}
