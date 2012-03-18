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
| File: libraries/Config.php
|----------------------------------------------------------
| Purpose: Config file handler
|==========================================================
*/


class _Config {

	var $config = array();
	var $is_loaded = array();

	/*
	|=====================================================
	| Constructor 
	|=====================================================
	|
	*/	
	function _Config()
	{
		global $config;
		$this->config = $config;
		
		log_message('debug', "Config Class Initialized");
	}

	/*
	|===========================================
	| Load Config File
	|============================================
	*/		
	function load($file = '')
	{
		$file = ($file == '') ? 'config' : str_replace(EXT, '', $file);
	
		if (in_array($file, $this->is_loaded))
		{                
			return;
		}
	
		include_once(BASEPATH.'config/'.$file.EXT);

		if ( ! isset($config) OR ! is_array($config))
		{
			show_error('Your '.$file.EXT.' file does not appear to contain a valid configuration array.');
		}
		
		$this->config = array_merge($this->config, $config);
		$this->is_loaded[] = $file;
		unset($config);

		log_message('debug', 'Config file loaded: config/'.$file.EXT);
	}
	
	/*
	|===========================================
	| Fetch a config file item
	|============================================
	|
	| The second parameter allows a slash to be
	| added to the end of the item, in the case
	| of a path.
	|
	*/		
	function item($item, $slash = FALSE)
	{
		if (is_array($item))
		{
			$temp = $item;
			$item = $temp['0'];
			$slash = $temp['1'];
		}
	
		if ( ! isset($this->config[$item])) 
		{
			return FALSE;
		}
		
		$pref = $this->config[$item];
		
		if ($pref == '')
		{
			return $pref;
		}
			
        if ($slash !== FALSE AND ereg("/$", $pref) === FALSE)
        {
			$pref .= '/';
        }
        
        return $pref;
	}

	/*
	|===========================================
	| Site URL
	|============================================
	*/		
	function site_url($uri = '')
	{
		if (is_array($uri))
		{ 
			$uri = implode('/', $uri);
		}
		
		if ($uri == '')
		{
			return $this->item('base_url', 1).$this->item('index_page');
		}
		else
		{
			return $this->item('base_url', 1).$this->item('index_page', 1).preg_replace("|^/*(.+?)/*$|", "\\1", $uri);
		}
	}

	/*
	|===========================================
	| System URL
	|============================================
	*/		
	function system_url()
	{
		$x = explode("/", preg_replace("|/*(.+?)/*$|", "\\1", BASEPATH));
		return $this->item('base_url', 1).end($x).'/';
	}
		
	/*
	|===========================================
	| Set a config file item
	|============================================
	*/		
	function set_item($item, $value)
	{
		$this->config[$item] = $value;
	}
}
?>