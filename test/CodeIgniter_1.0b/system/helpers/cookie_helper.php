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
| File: helpers/cookie_helper.php
|----------------------------------------------------------
| Purpose: Cookie Helpers
|==========================================================
*/

	
/*
|==========================================================
| Set Cookie
|==========================================================
|
| Accepts six parameters:
|	Cookie name
|	Cookie value
|	Expiration - in seconds.  Will be added to current time
|	Domain
|	Path
|	Prefix
|
| Can also submit an associative array in the first parameter
| containing the values.
*/
function set_cookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '')
{ 
	if (is_array($name))
	{
		$values = array('name', 'value', 'expire', 'domain', 'path', 'prefix');
		
		foreach ($values as $item)
		{
			if (isset($name[$item]))
			{
				$$item = $name[$item];
			}
		}
	}
			
	if ($expire == '' || ! is_numeric($expire))
	{
		$expire = time() - 86500;
	}
	elseif ($expire != 0)
	{
		$expire = time() + $expire;
	}
	else
	{
		$expire = 0;
	}
	
	$value = stripslashes($value);
				
	setcookie($prefix.$name, $value, $expire, $path, $domain, 0);
}

?>