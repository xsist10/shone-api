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
| File: helpers/directory_helper.php
|----------------------------------------------------------
| Purpose: Directory related helpers
|==========================================================
*/

	
/*
|==========================================================
| Create a Direcotry Map
|==========================================================
|
| Reads the specified directory and builds an array
| representation of it.  Sub-folders contained with the
| directory will be mapped as well.
|
*/
function directory_map($source_dir, $top_level_only = FALSE)
{
	if ( ! isset($filedata))
		$filedata = array();
	
	if ($fp = @opendir($source_dir))
	{ 
		while (FALSE !== ($file = readdir($fp)))
		{
			if (@is_dir($source_dir.$file) && substr($file, 0, 1) != '.' AND $top_level_only == FALSE) 
			{       
				$temp_array = array();
				 
				$temp_array = directory_map($source_dir.$file."/");   
				
				$filedata[$file] = $temp_array;
			}
			elseif (substr($file, 0, 1) != ".")
			{
				$filedata[] = $file;
			}
		}         
		return $filedata;        
	} 
}


?>