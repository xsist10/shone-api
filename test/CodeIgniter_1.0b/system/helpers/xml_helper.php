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
| File: helpers/xml_helpers.php
|----------------------------------------------------------
| Purpose: XML Helpers
|==========================================================
*/

	
/*
|==========================================================
| Convert Reserved XML characters to Entities
|==========================================================
|
*/
function xml_convert($str)
{
	$temp = '__TEMP_AMPERSANDS';
	
	$str = preg_replace("/&#(\d+);/", "$temp\\1;", $str);
	$str = preg_replace("/&(\w+);/",  "$temp\\1;", $str);
	
	$str = str_replace(array("&","<",">","\"", "'", "-"),
					   array("&amp;", "&lt;", "&gt;", "&quot;", "&#39;", "&#45;"),
					   $str);
		
	$str = preg_replace("/$temp(\d+);/","&#\\1;",$str);
	$str = preg_replace("/$temp(\w+);/","&\\1;", $str);
		
	return $str;
}    


?>