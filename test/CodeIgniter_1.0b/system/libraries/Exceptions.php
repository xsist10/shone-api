<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
|==========================================================
| Code Igniter - by pMachine
|----------------------------------------------------------
| www.codeignitor.com
|----------------------------------------------------------
| Copyright (c) 2006, pMachine, Inc.
|----------------------------------------------------------
| This library is licensed under an open source agreement:
| www.codeignitor.com/docs/license.html
|----------------------------------------------------------
| File: libraries/Exceptions.php
|----------------------------------------------------------
| Purpose: Error and Exceptions class
|==========================================================
*/


class _Exceptions {
	var $action;
	var $severity;
	var $message;
	var $filename;
	var $line;

	var $levels = array(
						E_ERROR				=>	'Error',
						E_WARNING			=>	'Warning',
						E_PARSE				=>	'Parsing Error',
						E_NOTICE			=>	'Notice',
						E_CORE_ERROR		=>	'Core Error',
						E_CORE_WARNING		=>	'Core Warning',
						E_COMPILE_ERROR		=>	'Compile Error',
						E_COMPILE_WARNING	=>	'Compile Warning',
						E_USER_ERROR		=>	'User Error',
						E_USER_WARNING		=>	'User Warning',
						E_USER_NOTICE		=>	'User Notice',
						E_STRICT			=>	'Runtime Notice'
					);


	/*
	|=====================================================
	| Constructor 
	|=====================================================
	|
	*/	
	function _Exceptions()
	{
		log_message('debug', "Output Class Initialized");
	}

	/*
	|==========================================================
	| Exception Logger
	|==========================================================
	|
	| This function logs PHP generate error messages
	|
	*/
	function log_exception($severity, $message, $filepath, $line)
	{	
		log_message('error', 'Severity: '.$severity.' '.$this->levels[$severity].' --> '.$message. ' '.$filepath.' '.$line, TRUE);
	}

	/*
	|==========================================================
	| 404 Page Not Found Handler
	|==========================================================
	*/
	function show_404_page($page = '')
	{	
		$heading = "404 Page Not Found";
		$message = "The page you requested was not found."; 

		log_message('error', '404 Page Not Found --> '.$page);
		echo $this->error_page($heading, $message, '404');
		exit;
	}

	/*
	|==========================================================
	| Error Page
	|==========================================================
	|
	| This function takes an error message as input
	| (either as a string or an array) and displayes
	| it using the specified template.
	|
	*/
	function error_page($heading, $message, $template = 'error')
	{
		$message = '<p>'.implode('</p><p>', ( ! is_array($message)) ? array($message) : $message).'</p>';
				
		ob_start();
		include_once(BASEPATH.'application/errors/'.$template.EXT);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}	

}
?>