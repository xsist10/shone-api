<?php

error_reporting(E_ALL);

/*
|------------------------------------------------
| SYSTEM FOLDER NAME
|------------------------------------------------
|
| Include the path if the folder is not in the same 
| directory as this file.  No trailing slash
*/

	$system_folder = "system";
	
/*
|------------------------------------------------
| URI PROTOCOL
|------------------------------------------------
|
| This variable determines which server global 
| should be used to retrieve the URI string.  The 
| default setting of "auto" works for most servers.
| If your links do not seem to work, try one of 
| the other delicious flavors:
| 
| 'auto'			Default - auto detects
| 'path_info'		Uses the PATH_INFO 
| 'query_string'	Uses the QUERY_STRING
*/

	$uri_protocol = 'auto';

/*
|================================================
| END OF USER CONFIGURABLE SETTINGS
|================================================
*/

if (function_exists('realpath') AND @realpath(dirname(__FILE__)) !== FALSE)
{
	$system_folder = str_replace("\\", "/", realpath(dirname(__FILE__))).'/'.$system_folder;
}

define('EXT', '.'.pathinfo(__FILE__, PATHINFO_EXTENSION));
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('BASEPATH', $system_folder.'/');

require_once BASEPATH.'libraries/Front_controller'.EXT;
?>