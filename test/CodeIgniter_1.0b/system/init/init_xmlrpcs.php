<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Initialize XML-RPC Server Class
|==========================================================
*/
if ( ! class_exists('_XML_RPC_Server'))
{
	$config = array();
	if (file_exists(BASEPATH.'config/xmlrpcs'.EXT))
	{
		include_once(BASEPATH.'config/xmlrpcs'.EXT);
	}
			
	require_once(BASEPATH.'libraries/Xmlrpc'.EXT);
	require_once(BASEPATH.'libraries/Xmlrpcs'.EXT);
	$this->xmlrpc  = new _XML_RPC();
	$this->xmlrpcs = new _XML_RPC_Server($config);
	$this->ci_is_loaded[] = 'xmlrpc';
	$this->ci_is_loaded[] = 'xmlrpcs';
}
?>