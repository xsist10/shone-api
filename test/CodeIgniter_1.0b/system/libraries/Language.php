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
| File: libraries/Lang.php
|----------------------------------------------------------
| Purpose: Language handler class
|==========================================================
*/


class _Language {
	var $language	= array();
	var $is_loaded	= array();

	/*
	|=====================================================
	| Constructor 
	|=====================================================
	|
	*/	
	function _Language()
	{
		log_message('debug', "Language Class Initialized");
	}

	/*
	|==========================================================
	| Loads a language file
	|==========================================================
	*/
	
	function load($langfile = '', $idiom = '')
	{
		if (is_array($langfile))
		{
			$temp = $langfile;
			$langfile = $temp['0'];
			$idiom = $temp['1'];
		}
	
		$langfile = str_replace(EXT, '', str_replace('_lang.', '', $langfile)).'_lang'.EXT;
		
		if (call('CI', 'is_loaded', $langfile) == TRUE)
		{
			return;
		}
		
		if ($idiom == '')
		{
			$deft_lang = call('config', 'item', 'language');
			$idiom = ($deft_lang == '') ? 'english' : $deft_lang;
		}
	
		if ( ! file_exists(BASEPATH.'language/'.$idiom.'/'.$langfile))
		{
			show_error('Unable to load the requested language file: language/'.$langfile.EXT);
		}

		include_once(BASEPATH.'language/'.$idiom.'/'.$langfile);
		            
		if ( ! isset($lang))
		{
			log_message('error', 'Language file contains no data: language/'.$idiom.'/'.$langfile);
			return;
		}
		
		$this->ci_is_loaded[] = $langfile;
		$this->language = array_merge($this->language, $lang);
		unset($lang);
		
		log_message('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile);
	}

	/*
	|==========================================================
	| Returns a single line of text
	|==========================================================
	*/
	
	function line($line = '')
	{
		return ($line == '' OR ! isset($this->language[$line])) ? FALSE : $this->language[$line];
	}


}
?>