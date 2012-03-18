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
| File: helpers/html_helper.php
|----------------------------------------------------------
| Purpose: HTML Helpers
|==========================================================
*/

	
/*
|==========================================================
| Heading
|==========================================================
|
| Creates an HTML <h2> tag.  First param is the date.
| Second param is the size of the heading tag.
|
*/
function heading($data = '', $h = '1')
{
	return "<h".$h.">".$data."</h".$h.">";
}

/*
|==========================================================
| Creates <br /> tags based on number supplied
|==========================================================
|
*/
function br($num = 1)
{
	return str_repeat("&nbsp;", $num);
}

/*
|==========================================================
| Creates non-breaking based on number supplied
|==========================================================
|
*/
function nbs($num = 1)
{
	return str_repeat("&nbsp;", $num);
}






?>