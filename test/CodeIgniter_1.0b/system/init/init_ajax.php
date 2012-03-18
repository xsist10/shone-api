<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Initialize AJAX Class
|==========================================================
|
*/
if ( ! class_exists('_AJAX'))
{
	require_once(BASEPATH.'libraries/Ajax'.EXT);
	$this->ajax = new _AJAX();
	$this->ci_is_loaded[] = 'ajax';
}

?>