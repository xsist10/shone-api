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
| File: helpers/array_helper.php
|----------------------------------------------------------
| Purpose: Array related Helpers
|==========================================================
*/

/*
|==========================================================
| Random Element
|==========================================================
|
| Takes an array as input and returns a random element
|
*/
function random_element($array)
{
	if ( ! is_array($array))
	{
		return $array;
	}
	return $array[array_rand($array)];
}


?>