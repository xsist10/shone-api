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
| File: libraries/Parser.php
|----------------------------------------------------------
| Purpose: Simple pseudo-variable parser:  {some_var}
|==========================================================
*/



/*
|==========================================================
| Variable Parser
|==========================================================
|
*/

class _Parser {

	var $l_delim = '{';
	var $r_delim = '}';
	

	/*
	|==========================================================
	|  Parse a template
	|==========================================================
	|
	| Parses pseudo-variables contained in the specified
	| template, replacing them with the data in the second param
	|
	*/
	function parse($template, $data, $return = FALSE)
	{
		global $OUT;

		$template = call('load', 'view', array($template, '', TRUE));
		
		if ($template == '')
		{
			return FALSE;
		}
		
		foreach ($data as $key => $val)
		{
			if ( ! is_array($val))
			{
				$template = $this->_parse_single($key, $val, $template);
			}
			else
			{
				$template = $this->_parse_pair($key, $val, $template);		
			}
		}
		
		if ($return == FALSE)
		{
			$OUT->final_output = $template;
		}
		
		return $template;
	}

	/*
	|==========================================================
	|  Set the left/right variable delimiters
	|==========================================================
	|
	*/
	function set_delimiters($l = '{', $r = '}')
	{
		$this->l_delim = $l;
		$this->r_delim = $r;
	}

	/*
	|==========================================================
	|  Parse a single key/value
	|==========================================================
	|
	*/
	function _parse_single($key, $val, $string)
	{
		return str_replace($this->l_delim.$key.$this->r_delim, $val, $string);
	}

	/*
	|==========================================================
	|  Parse a tag pair
	|==========================================================
	|
	| Parses tag pairs:  {some_tag} string... {/some_tag}
	|
	*/
	function _parse_pair($variable, $data, $string)
	{	
		if (FALSE === ($match = $this->_match_pair($string, $variable)))
		{
			return $string;
		}

		$str = '';
		foreach ($data as $row)
		{
			$temp = $match['1'];
			foreach ($row as $key => $val)
			{
				$temp = $this->_parse_single($key, $val, $temp);
			}
			
			$str .= $temp;
			
		}
		
		return str_replace($match['0'], $str, $string);
	}

	/*
	|==========================================================
	|  Matches a variable pair
	|==========================================================
	|
	*/
	function _match_pair($string, $variable)
	{
		if ( ! preg_match("|".$this->l_delim . $variable . $this->r_delim."(.+)".$this->l_delim . '/' . $variable . $this->r_delim."|s", $string, $match))
		{
			return FALSE;
		}
		
		return $match;
	}

}

?>