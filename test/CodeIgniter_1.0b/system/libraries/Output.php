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
| File: libraries/Output.php
|----------------------------------------------------------
| Purpose: Sends final output to browser
|==========================================================
*/



class _Output {

	var $final_output;
	var $cache_expiration = 0;

	/*
	|=====================================================
	| Constructor 
	|=====================================================
	|
	*/	
	function _Output()
	{
		log_message('debug', "Output Class Initialized");
	}

	/*
	|=====================================================
	| Get Output 
	|=====================================================
	|
	*/	
	function get_output()
	{
		return $this->final_output;
	}

	/*
	|=====================================================
	| Set Output 
	|=====================================================
	|
	*/	
	function set_output($output)
	{
		$this->final_output = $output;
	}

	/*
	|=====================================================
	| Set Cache 
	|=====================================================
	|
	*/	
	function cache($time)
	{
		$this->cache_expiration = ( ! is_numeric($time)) ? 0 : $time;
	}

	/*
	|========================================================
	| Display Output
	|========================================================
	|
	| All "view" data is automatically put into this variable 
	| by the controller class:
	|
	| $this->final_output
	|
	| This function simply echos the variable out.  It also 
	| does the following:
	| 
	| Stops the benchmark timer so the page rendering speed 
	| can be shown.
	|
	| Determines if the "memory_get_usage' function is available
	| so that the memory usage can be shown.
	|
	*/		
	function display()
	{	
		global $BM;
		
		if ($this->cache_expiration > 0)
		{
			$this->write_cache();
		}

		$elapsed = $BM->elapsed_time('code_igniter_start', 'code_igniter_end');		
		$memory	 = ( ! function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2).'MB';

		$this->final_output = str_replace('{memory_usage}', $memory, $this->final_output);		
		$this->final_output = str_replace('{elapsed_time}', $elapsed, $this->final_output);
		
		echo $this->final_output;
		
		log_message('debug', "Final output sent to browser");
		log_message('debug', "Total execution time: ".$elapsed);		
	}
	
	/*
	|=====================================================
	| Write a Cache File 
	|=====================================================
	|
	*/	
	function write_cache()
	{
		$path = call('config', 'item', 'cache_path');
	
		$cache_path = ($path == '') ? BASEPATH.'cache/' : $path;
		
		if ( ! is_dir($cache_path) OR ! is_writable($cache_path))
		{
			return;
		}
		
		$uri = call('config', 'item', array('base_url', 1))
			  .call('config', 'item', 'index_page')
			  .call('uri', 'uri_string');		
		
		$cache_path .= md5($uri);

        if ( ! $fp = @fopen($cache_path, 'wb'))
        {
			log_message('error', "Unable to write ache file: ".$cache_path);
            return;
		}
		
		$expire = time() + ($this->cache_expiration * 60);
		
        flock($fp, LOCK_EX);
        fwrite($fp, $expire.'TS--->'.$this->final_output);
        flock($fp, LOCK_UN);
        fclose($fp);
		@chmod($cache_path, 0777); 

		log_message('debug', "Cache file written: ".$cache_path);
	}
	
}
?>