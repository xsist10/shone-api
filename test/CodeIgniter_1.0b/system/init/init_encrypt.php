<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Initialize Encryption Class
|==========================================================
|
*/
if ( ! class_exists('_Encrypt'))
{
	require_once(BASEPATH.'libraries/Encrypt'.EXT);
	$this->encrypt = new _Encrypt();
	$this->ci_is_loaded[] = 'encrypt';
}

?>