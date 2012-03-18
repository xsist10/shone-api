<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Initialize XML-RPC Request Class
|==========================================================
*/
if ( ! class_exists('_XML_RPC'))
{
	$config = array();
	if (file_exists(BASEPATH.'config/xmlrpc'.EXT))
	{
		include_once(BASEPATH.'config/xmlrpc'.EXT);
	}
		
	require_once(BASEPATH.'libraries/Xmlrpc'.EXT);		
	$this->xmlrpc = new _XML_RPC($config);
	$this->ci_is_loaded[] = 'xmlrpc';
}
?>