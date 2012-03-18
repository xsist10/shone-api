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
| File: libraries/URI.php
|----------------------------------------------------------
| Purpose: Parses URIs and determines routing
|==========================================================
*/


class _URI {

	var $uri;

	/*
	|==========================================================
	| Constructor
	|==========================================================
	|
	| Simply globalizes the $RTR object.  The front
	| loads the Router class early on so it's not available
	| normally as other calsses are. 
	|
	*/		
	function _URI()
	{
		global $RTR;
		$this->uri = $RTR;
		
		log_message('debug', "URI Class Initialized");
	}
		
	/*
	|==========================================================
	| Fetch a URI Segment
	|==========================================================
	|
	| This function returns the URI segment based on the number
	| provided.  
	|
	*/
	function segment($n, $no_result = FALSE)
	{
		return ( ! isset($this->uri->segments[$n])) ? $no_result : $this->uri->segments[$n];
	}

	/*
	|==========================================================
	| Fetch a URI Segment and add a trailing slash
	|==========================================================
	|
	*/
	function slash_segment($n, $where = 'trailing')
	{	
		if ($where == 'trailing')
		{
			$trailing	= '/';
			$leading	= '';
		}
		elseif ($where == 'leading')
		{
			$leading	= '/';
			$trailing	= '';
		}
		else
		{
			$leading	= '/';
			$trailing	= '/';
		}
		return ( ! isset($this->uri->segments[$n])) ? '' : $leading.$this->uri->segments[$n].$trailing;
	}
	
	/*
	|==========================================================
	| Segment Array
	|==========================================================
	|
	*/
	function segment_array()
	{
		return $this->uri->segments;
	}

	/*
	|==========================================================
	| Total number of segments
	|==========================================================
	|
	*/
	function total_segment()
	{
		return count($this->uri->segments);
	}

	/*
	|==========================================================
	| Fetch the entire URI string
	|==========================================================
	|
	*/
	function uri_string()
	{
		return $this->uri->uri_string;
	}

}
?>