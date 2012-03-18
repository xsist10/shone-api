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
| File: libraries/Input.php
|----------------------------------------------------------
| Purpose: Pre-processes global input data for security
|==========================================================
*/


class _Input {
	var $use_xss_clean	= FALSE;
	var $ip_address		= FALSE;
	var $user_agent		= FALSE;
	
	/*
	|==========================================================
	| Constructor
	|==========================================================
	|
	| Simply sets whether to globally enable the XSS processing.
	|
	*/	
	function _Input($xss_clean = FALSE)
	{
		$this->use_xss_clean = ($xss_clean === FALSE) ? FALSE : TRUE;
		log_message('debug', "Input Class Initialized");
	}
	
	/*
	|==========================================================
	| Sanitize Globals
	|==========================================================
	|
	| This function does the folowing:
	|
	| Unsets $_GET data.  No GET data is allowed.
	|
	| Unsets all globals if register_globals is enabled
	|
	| Cycles through the $_POST and $_COOKIE arrays to escapes all values
	|
	| Standardizes newline characters to \n
	|
	*/
	function sanitize_globals()
	{
		/*
		|------------------------------------------------
		| Unset the $_GET global
		|------------------------------------------------
		|
		| We like pretty URLs, so there's no need for this
		|
		*/
	
		unset($_GET);
	
		/*
		|------------------------------------------------
		| Unset globals
		|------------------------------------------------
		|
		| This is effectively the same as:
		| register_globals = off
		|
		*/
	
		foreach (array($_POST, $_COOKIE) as $global)
		{
			if ( ! is_array($global))
			{
				unset($$global);
			}
			else
			{
				foreach ($global as $key => $val)
				{
					unset($$key);
				}    
			}
		}
				
		/*
		|------------------------------------------------
		| Clean $_POST Data
		|------------------------------------------------
		*/
	
		if (is_array($_POST) AND count($_POST) > 0)
		{
			foreach($_POST as $key => $val)
			{                
				if (is_array($val))
				{ 	
					foreach($val as $k => $v)
					{                    
						$_POST[$this->clean_input_keys($key)][$this->clean_input_keys($k)] = $this->clean_input_data($v);
					}
				}
				else
				{
					$_POST[$this->clean_input_keys($key)] = $this->clean_input_data($val);
				}
			}            
		}
	
		/*
		|------------------------------------------------
		| Clean $_COOKIE Data
		|------------------------------------------------
		*/
		
		if (is_array($_COOKIE) AND count($_COOKIE) > 0)
		{
			foreach($_COOKIE as $key => $val)
			{              
				$_COOKIE[$this->clean_input_keys($key)] = $this->clean_input_data($val);
			}    
		}
		
		log_message('debug', "Global POST and COOKIE data sanitized");
	}	
	
	/*
	|==========================================================
	| Clean Intput Data
	|==========================================================
	|
	| This is a helper function. It escapes data and 
	| standardizes newline characters to \n
	|
	*/	
	function clean_input_data($str)
	{
		if (is_array($str))
		{
			$new_array = array();
			foreach ($str as $key => $val)
			{
				$new_array[$key] = $this->clean_input_data($val);
			}
			return $new_array;
		}
	
		if ( ! get_magic_quotes_gpc())
		{
			$str = addslashes($str);
		}
		
		if ($this->use_xss_clean === TRUE)
		{
			$str = $this->xss_clean($str);
		}
		
		return preg_replace("/\015\012|\015|\012/", "\n", $str);
	}
	
	/*
	|==========================================================
	| Clean Keys
	|==========================================================
	|
	| This is a helper function. To prevent malicious users 
	| from trying to exploit keys we make sure that keys are 
	| only named with alpha-numeric text and a few other items.
	|
	*/
	function clean_input_keys($str)
	{    
		 if ( ! preg_match("/^[a-z0-9:_\/-]+$/i", $str))
		 { 
			exit('Disallowed Key Characters: '.$str);
		 }
	
		if ( ! get_magic_quotes_gpc())
		{
		   return addslashes($str);
		}
		
		return $str;
	}
	
	/*
	|==========================================================
	| Fetch an item from the POST array
	|==========================================================
	|
	*/
	function post($index = '', $xss_clean = FALSE)
	{		
		if ( ! isset($_POST[$index]))
		{
			return FALSE;
		}
		else
		{
			if ($this->use_xss_clean === TRUE)
			{
				return $this->xss_clean($_POST[$index]);
			}
			else
			{
				return $_POST[$index];
			}
		}
	}

	/*
	|==========================================================
	| Fetch an item from the COOKIE array
	|==========================================================
	|
	*/
	function cookie($index = '', $xss_clean = FALSE)
	{
		if ( ! isset($_COOKIE[$index]))
		{
			return FALSE;
		}
		else
		{
			if ($this->use_xss_clean === TRUE)
			{
				return $this->xss_clean($_COOKIE[$index]);
			}
			else
			{
				return $_COOKIE[$index];
			}
		}
	}


	/*
	|==========================================================
	| Fetch the IP Address
	|==========================================================
	|
	*/
	function ip_address()
	{
		if ($this->ip_address !== FALSE)
		{
			return $this->ip_address;
		}
	
		$cip = (isset($_SERVER['HTTP_CLIENT_IP']) AND $_SERVER['HTTP_CLIENT_IP'] != "") ? $_SERVER['HTTP_CLIENT_IP'] : FALSE;
		$rip = (isset($_SERVER['REMOTE_ADDR']) AND $_SERVER['REMOTE_ADDR'] != "") ? $_SERVER['REMOTE_ADDR'] : FALSE;
		$fip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND $_SERVER['HTTP_X_FORWARDED_FOR'] != "") ? $_SERVER['HTTP_X_FORWARDED_FOR'] : FALSE;
					
		if ($cip && $rip)	$this->ip_address = $cip;	
		elseif ($rip)		$this->ip_address = $rip;
		elseif ($cip)		$this->ip_address = $cip;
		elseif ($fip)		$this->ip_address = $fip;
		
		if (strstr($this->ip_address, ','))
		{
			$x = explode(',', $this->ip_address);
			$this->ip_address = end($x);
		}
		
		if ( ! $this->valid_ip($this->ip_address))
		{
			$this->ip_address = '0.0.0.0';
		}
		
		unset($cip);
		unset($rip);
		unset($fip);
		
		return $this->ip_address;
	}
		
	/*
	|==========================================================
	| Validate IP Address
	|==========================================================
	|
	*/
	function valid_ip($ip)
	{
		return ( ! preg_match( "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $ip)) ? FALSE : TRUE;
	}
	
	/*
	|==========================================================
	| User Agent
	|==========================================================
	|
	*/
	function user_agent()
	{
		if ($this->user_agent !== FALSE)
		{
			return $this->user_agent;
		}
	
		$this->user_agent = ( ! isset($_SERVER['HTTP_USER_AGENT'])) ? FALSE : $_SERVER['HTTP_USER_AGENT'];
		
		return $this->user_agent;
	}
	
	/*
	|==========================================================
	| XSS Clean
	|==========================================================
	|
	| Sanitizes data so that Cross Site Scripting Hacks can be
	| prevented.Ê This function does a fair amount of work but
	| it is extremely thorough, designed to prevent even the
	| most obscure XSS attempts.Ê Nothing is ever 100% foolproof,
	| of course, but I haven't been able to get anything passed
	| the filter.
	|
	| Note: This function should only be used to deal with data
	| upon submission.Ê It's not something that should
	| be used for general runtime processing.
	|
	| This function was based in part on some code and ideas I
	| got from Bitflux: http://blog.bitflux.ch/wiki/XSS_Prevention
	|
	| Rather than removing malicious code, however, I've opted
	| to render is safe by converting it to character entities.
	| I believe this is a more graceful approach.
	|
	| To help develop this script I used this great list of
	| vulnerabilities along with a few other hacks I've 
	| harvested from examining vulnerabilities in other programs:
	| http://ha.ckers.org/xss.html
	|
	*/
	function xss_clean($str)
	{	
		/*
		|----------------------------------------------
		| Remove Null Characters
		|----------------------------------------------
		|
		| This prevents sandwiching null characters
		| between ascii characters, like Java\0script.
		|
		*/
		$str = preg_replace('/\0+/', '', $str);
		$str = preg_replace('/(\\\\0)+/', '', $str);
	
		/*
		|----------------------------------------------
		| Validate standard character entites
		|----------------------------------------------
		|
		| Add a semicolon if missing.  We do this to enable
		| the conversion of entities to ASCII later.
		|
		*/
		$str = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u',"\\1;",$str);
		
		/*
		|----------------------------------------------
		| Validate UTF16 two byte encodeing (x00) 
		|----------------------------------------------
		|
		| Just as above, adds a semicolon if missing.
		|
		*/
		$str = preg_replace('#(&\#x*)([0-9A-F]+);*#iu',"\\1\\2;",$str);
	
		/*
		|----------------------------------------------
		| Convert character entities to ASCII 
		|----------------------------------------------
		|
		| This permits our tests below to work reliably
		|
		*/		
		$str = html_entity_decode($str, ENT_COMPAT, "UTF-8");
		
		/*
		|----------------------------------------------
		| URL Decode
		|----------------------------------------------
		|
		| Just in case stuff like this is submitted:
		|
		| <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		|
		*/		
		$str = urldecode($str);
	
		/*
		|----------------------------------------------
		| Convert all tabs to spaces
		|----------------------------------------------
		|
		| This prevents strings like this: ja	vascript
		| Note: we deal with spaces between characters later.
		|
		*/		
		$str = preg_replace("#\t+#", " ", $str);
	
		/*
		|----------------------------------------------
		| Makes PHP tags safe
		|----------------------------------------------
		|
		|  Note: XML tags are inadvertently replaced too:
		|
		|	<?xml
		|
		| But it doesn't seem to pose a problem.
		|
		*/		
		$str = str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
		// <? fixes BBEdit bug
		
		/*
		|----------------------------------------------
		| Compact any exploded words
		|----------------------------------------------
		|
		| This corrects words like:  j a v a s c r i p t
		| And even words with characters that span multiple lines.
		| These words are compacted back to their correct state.
		|
		*/		
		$words = array('javascript', 'vbscript', 'script', 'applet', 'alert');
		foreach ($words as $word)
		{
			$temp = '';
			for ($i = 0; $i < strlen($word); $i++)
			{
				$temp .= substr($word, $i, 1)."\s*";
			}
			
			$temp = substr($temp, 0, -3);
			$str = preg_replace('#'.$temp.'#is', $word, $str);
		}
	
		/*
		|----------------------------------------------
		| Sanitize naughty HTML elements
		|----------------------------------------------
		|
		| If a tag containing any of the words in the list 
		| below is found, the tag gets converted to entities.
		|
		| So this: <blink>
		| Becomes: &lt;blink&gt;
		|
		*/		
				
		$str = preg_replace('#<(/*\s*)(applet|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|layer|link|meta|object|plaintext|style|script|textarea|title|xml|xss)([^>]*)>#is', "&lt;\\1\\2\\3&gt;", $str);
		
		/*
		|----------------------------------------------
		| Sanitize naughty PHP and JS elements
		|----------------------------------------------
		|
		| Similar to above, only instead of looking for
		| tags it looks for PHP and JavaScript commands
		| that are disallowed.  Rather than removing the
		| code, it simply converts the parenthesis to entities
		| rendering the code unexecutable.
		|
		| For example:	eval('some code')
		| Becomes:		eval&#40;'some code'&#41;
		|
		*/		
				
		$str = preg_replace('#(alert|cmd|passthru|eval|exec|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);
		
		/*
		|----------------------------------------------
		| Remove JavaScript Event Handlers
		|----------------------------------------------
		|
		| Note: This code is a little blunt.  It removes
		| the event handler and anything upto the closing >, 
		| but it's unlkely to be a problem.
		|
		*/				
		$str = preg_replace('#(<[^>]+[\x00-\x20\"\'])(onblur|onchange|onclick|onfocus|onload|onmouseover|onmouseup|onmousedown|onselect|onsubmit|onunload|onkeypress|onkeydown|onkeyup|onresize)[^>]*>#iU',"\\1>",$str);
		
		/*
		|----------------------------------------------
		| Make a few other items safe
		|----------------------------------------------
		*/				
		$bad = array(
						'document.cookie'	=> 'document&#46;cookie',
						'document.write'	=> 'document&#46;write',
						"javascript\s*:"	=> 'javascript&#58;',
						"Redirect\s+302"	=> '',
						'<!--'				=> '&lt;!--',
						'-->'				=> '--&gt;'
					);
	
		foreach ($bad as $key => $val)
		{
			$str = preg_replace("#".$key."#i", $val, $str);   
		}
	
		log_message('debug', "XSS Filtering completed");
		return $str;
	}

}
?>