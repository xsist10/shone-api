<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Initialize Validation Class
|==========================================================
*/
if ( ! class_exists('_Validation'))
{
	require_once(BASEPATH.'libraries/Validation'.EXT);
	$this->validation = new _Validation();	
	$this->ci_is_loaded[] = 'validation';
}

?>