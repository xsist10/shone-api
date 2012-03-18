<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Instantiate the Email Class
|==========================================================
|
| It looks to see if a config file exists so that
| parameters can be hard coded
|
*/
if ( ! class_exists('_Email'))
{
	$config = array();
	if (file_exists(BASEPATH.'config/email'.EXT))
	{
		include_once(BASEPATH.'config/email'.EXT);
	}
	
	require_once(BASEPATH.'libraries/Email'.EXT);		
	$this->email = new _Email($config);
	$this->ci_is_loaded[] = 'email';
}
?>