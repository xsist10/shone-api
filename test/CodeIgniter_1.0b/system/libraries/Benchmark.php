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
| File: libraries/Benchark.php
|----------------------------------------------------------
| Purpose: Benchmarking class
|==========================================================
*/


/*
|==========================================================
| Benchmark Class
|==========================================================
|
*/
class _Benchmark {
	var $marker = array();
    
    function _Benchmark($mark = FALSE)
    {
    	if ($mark !== FALSE)
    	{
    		$this->mark($mark);
    	}
    	
		log_message('debug', "Benchmark Class Initialized");
    }
    
	/*
	|--------------------------------------------
	| Set a marker
	|--------------------------------------------
	*/
    function mark($name)
    {
        $this->marker[$name] = microtime();
    }
    
	/*
	|--------------------------------------------
	| Calculate elapsed time between two points
	|--------------------------------------------
	|
	| Calculates the time difference between two marked
	| points.
	|
	| If the first parameter is empty this function 
	| instead returns the {elapsed_time} pseudo-variable.
	| This permits the the full system execution time to
	| be shown in a template. The output class will
	| swap the real value for this variable.
	|
	*/
    function elapsed_time($point1 = '', $point2 = '', $decimals = 4)
    {
    	if ($point1 == '')
    	{
			return '{elapsed_time}';
    	}
    	    
    	if ( ! isset($this->marker[$point2]))
        	$this->marker[$point2] = microtime();
        	    
        list($sm, $ss) = explode(' ', $this->marker[$point1]);
        list($em, $es) = explode(' ', $this->marker[$point2]);
                        
        return number_format(($em + $es) - ($sm + $ss), $decimals);
    }
    
	/*
	|==========================================================
	| Memory Usage
	|==========================================================
	|
	| This function returns the {memory_usage} pseudo-variable.
	| This permits the person to put it anywhere in a template 
	| without. The output class will swap the real value for 
	| this variable.
	|
	*/
	function memory_usage()
	{
		return '{memory_usage}';
	}
}
?>