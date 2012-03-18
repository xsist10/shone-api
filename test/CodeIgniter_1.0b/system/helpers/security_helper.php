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
| File: helpers/security_helper.php
|----------------------------------------------------------
| Purpose: Security related Helpers
|==========================================================
*/


/*
|==========================================================
| XSS Filtering
|==========================================================
|
*/	
function xss_clean($str)
{
	return $CI->input->xss_clean($str);
}

/*
|==========================================================
| Strip Image Tags
|==========================================================
|
*/	
function strip_image_tags($str)
{    
	$str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
	$str = preg_replace("#<img\s+.*?src\s*=\s*(.+?).*?\>#", "\\1", $str);
			
	return $str;
}


/*
|==========================================================
| Convert PHP tags to entities
|==========================================================
|
*/	
function encode_php_tags($str)
{
	return str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
	// <? fixes BBEdit bug
}

?>