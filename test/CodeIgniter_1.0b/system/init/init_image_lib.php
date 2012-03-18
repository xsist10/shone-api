<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Initialize Image Manipulation Class
|==========================================================
*/
if ( ! class_exists('_Image_lib'))
{
	$config = array();
	if (file_exists(BASEPATH.'config/image_lib'.EXT))
	{
		include_once(BASEPATH.'config/image_lib'.EXT);
	}
	
	require_once(BASEPATH.'libraries/Image_lib'.EXT);
	$this->image_lib = new _Image_lib($config);
	
	$this->ci_is_loaded[] = 'image_lib';
}

?>