<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Initialize File Uploading Class
|==========================================================
|
| It looks to see if a config file exists so that
| parameters can be hard coded
|
*/
if ( ! class_exists('_Upload'))
{
	$config = array();
	if (file_exists(BASEPATH.'config/upload'.EXT))
	{
		include_once(BASEPATH.'config/upload'.EXT);
	}
	
	require_once(BASEPATH.'libraries/Upload'.EXT);
	$this->upload = new _Upload($config);
	$this->ci_is_loaded[] = 'upload';
}

?>