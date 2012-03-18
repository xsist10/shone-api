<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Initialize Trackback Class
|==========================================================
|
*/
if ( ! class_exists('_Trackback'))
{
	require_once(BASEPATH.'libraries/Trackback'.EXT);
	$this->trackback = new _Trackback();
	$this->ci_is_loaded[] = 'trackback';
}

?>