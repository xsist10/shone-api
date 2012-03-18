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
| File: libraries/Log.php
|----------------------------------------------------------
| Purpose: Message logging class
|==========================================================
*/


class _Log {

	var $log_path;
	var $_threshold	= 1;
	var $_date_fmt	= 'Y-m-d H:i:s';
	var $_enabled	= TRUE;
	var $_levels	= array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');

	/*
	|=====================================================
	| Constructor
	|=====================================================
	*/	
	function _Log($path = '', $threshold = '', $date_fmt = '')
	{	
		$this->log_path = ($path != '') ? $path : BASEPATH.'logs/';

		if ( ! is_dir($this->log_path) OR ! is_writable($this->log_path))
		{
			$this->_enabled = FALSE;
		}
		
		if (is_numeric($threshold))
		{
			$this->_threshold = $threshold;
		}
			
		if ($date_fmt != '')
		{
			$this->_date_fmt = $date_fmt;
		}
	}
	
	/*
	|===========================================
	| Write Log File
	|============================================
	*/		
	function write_log($level = 'error', $msg, $php_error = FALSE)
	{		
		if ($this->_enabled === FALSE)
		{
			return FALSE;
		}
	
		$level = strtoupper($level);
		
		if ( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold))
		{
			return FALSE;
		}
	
		$filepath = $this->log_path.'log-'.date('Y-m-d').'.php';
		$message  = '';
		
		if ( ! file_exists($filepath))
		{
			$message .= "<"."?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?".">\n\n";
		}
			
		if ( ! $fp = @fopen($filepath, "a"))
		{
			return FALSE;
		}

		$message .= $level.' '.(($level == 'INFO') ? ' -' : '-').' '.date($this->_date_fmt). ' --> '.$msg."\n";
		
		flock($fp, LOCK_EX);	
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);
	
		@chmod($filepath, 0666); 		
		return TRUE;
	}

}
?>