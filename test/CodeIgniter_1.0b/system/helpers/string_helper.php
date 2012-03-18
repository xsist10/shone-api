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
| File: helpers/string_helper.php
|----------------------------------------------------------
| Purpose: String related Helpers
|==========================================================
*/

	
/*
|==========================================================
| Trim Slashes
|==========================================================
|
| Removes any leading/traling slashes from a string:
|
| /this/that/theother/
|
| becomes:
|
| this/that/theother
|
*/
function trim_slashes($str)
{
	return preg_replace("|^/*(.+?)/*$|", "\\1", $str);
}

/*
|==========================================================
| Reduce Double Slashes
|==========================================================
|
| Converts double slashes in a string to a single slash,
| except those found in http://
|
| http://www.some-site.com//index.php
|
| becomes:
|
| http://www.some-site.com/index.php
|
*/
function reduce_double_slashes($str)
{
	return preg_replace("#[^:]//+#", "/", $str);  
}


/*
|==========================================================
| Create a Random String
|==========================================================
|
| Useful for generating passwords or hashes.  
|
*/
function random_string($type = 'alnum', $len = 8)
{					
	switch($type)
	{
		case 'alnum'	:
		case 'numeric'	:
		case 'nozero'	:
		
				switch ($type)
				{
					case 'alnum'	:	$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						break;
					case 'numeric'	:	$pool = '0123456789';
						break;
					case 'nozero'	:	$pool = '123456789';
						break;
				}

				if ($len > 1) $len -= 1;

				$str = '';
				for ($i=0; $i < $len; $i++) 
				{    
					$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1); 
				}
				return $str;      
		  break;
		case 'unique' : return md5(uniqid(mt_rand())); 
		  break; 
	}        
}



/*
|==========================================================
| Repeater function
|==========================================================
|
*/
function repeater($data, $num = 1)
{
	return str_repeat($data, $num);
}



?>