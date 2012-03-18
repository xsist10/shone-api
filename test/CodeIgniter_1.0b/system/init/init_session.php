<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Initialize Session Class
|==========================================================
|
*/
global $CI, $recursive;
if ( ! is_object($CI))
{
	$recursive['session'] = FALSE;
}
else
{ 
	if ( ! class_exists('_Session'))
	{
		require_once(BASEPATH.'libraries/Session'.EXT);
		$this->session = new _Session();
		$this->ci_is_loaded[] = 'session';
	}
}

?>