<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
|==========================================================
| Code Igniter - by pMachine
|----------------------------------------------------------
| www.codeignitor.com
|----------------------------------------------------------
| Copyright (c) 2006, pMachine, Inc.
|----------------------------------------------------------
| This library is licensed under an open source agreement:
| www.codeignitor.com/docs/license.html
|----------------------------------------------------------
| File: helpers/file_helper.php
|----------------------------------------------------------
| Purpose: File Helpers
|==========================================================
*/

	
/*
|==========================================================
| Read File
|==========================================================
|
| Opens the file specfied in the path and returns it as a string.
|
*/
function read_file($file)
{
	if ( ! file_exists($file))
	{
		return FALSE;
	}

	if ( ! $fp = @fopen($file, 'rb'))
	{
		return FALSE;
	}
		
	flock($fp, LOCK_SH);
	
	$data = '';
	if (filesize($file) > 0) 
	{
		$data = fread($fp, filesize($file)); 
	}

	flock($fp, LOCK_UN);
	fclose($fp); 

	return $data;
}

/*
|==========================================================
| Write File
|==========================================================
|
| Writes data to the file specified in the path.  
| Creats a new file if non-existant.
|
*/
function write_file($path, $data)
{
	if ( ! $fp = @fopen($path, 'wb'))
	{
		return FALSE;
	}
		
	flock($fp, LOCK_EX);
	fwrite($fp, $data);
	flock($fp, LOCK_UN);
	fclose($fp);	

	return TRUE;
}


?>