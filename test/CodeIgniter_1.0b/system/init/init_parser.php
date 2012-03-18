<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|==========================================================
| Initialize Variable Parser Class
|==========================================================
|
*/
if ( ! class_exists('_Parser'))
{
	require_once(BASEPATH.'libraries/Parser'.EXT);
	$this->parser = new _Parser();
	$this->ci_is_loaded[] = 'parser';
}

?>