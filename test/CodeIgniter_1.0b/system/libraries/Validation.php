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
| File: libraries/Validate.php
|----------------------------------------------------------
| Purpose: Validation Library
|==========================================================
*/


class _Validation {
	
	// Public variables
	var $error_string	= '';

	// Private vars
	var $_error_array		= array();
	var $_rules				= array();
	var $_fields			= array();
	var $_error_messages	= array();
	var $_current_field  	= '';
	var $_safe_form_data 	= FALSE;
	var $_error_prefix		= '<p>';
	var $_error_suffix		= '</p>';
	

	/*
	|=====================================================
	| Constructor 
	|=====================================================
	|
	*/	
	function _Validation()
	{
		log_message('debug', "Validation Class Initialized");
	}
						
	/*
	|==========================================================
	| Set Fields
	|==========================================================
	|
	| This function takes an array of field names as input
	| and generates class variables with the same name, which
	| will either be blank or contain the $_POST value corresponding
	| to it:
	|
	*/
	function set_fields($data = '', $field = '')
	{	
		if ($data == '')
			return;
	
		if ($data != '' AND ! is_array($data))
		{
			if ($field == '')
				return;
				
			$data[$data] = $field;
		}
	
		if (count($this->_fields) == 0)
		{
			$this->_fields = $data;
		}
	
		foreach($this->_fields as $key => $val)
		{
			$this->$key = ( ! isset($_POST[$key])) ? '' : $this->prep_for_form($_POST[$key]);
			
			$error = $key.'_error';
			if ( ! isset($this->$error))
			{
				$this->$error = '';			
			}
		}		
	}

	/*
	|==========================================================
	| Set Rules
	|==========================================================
	|
	| This function takes an array of field names and validation 
	| rules as input ad simply stores is for use later.
	| to it:
	|
	*/
	function set_rules($data, $rules = '')
	{
		if ( ! is_array($data))
		{
			if ($rules == '')
				return;
				
			$data[$data] = $rules;
		}
	
		foreach ($data as $key => $val)
		{
			$this->_rules[$key] = $val;
		}
	}

	/*
	|==========================================================
	| Set Error Message
	|==========================================================
	|
	| This function lets users set their own error messages
	| on the fly.  Note:  The key name has to match the 
	| function name that it corresponds to.
	|
	*/
	function set_message($lang, $val = '')
	{
		if ( ! is_array($lang))
		{
			$lang = array($lang => $val);
		}
	
		$this->_error_messages = array_merge($this->_error_messages, $lang);
	}

	/*
	|==========================================================
	| Set The Error Delimiter
	|==========================================================
	|
	| Permits a prefix/suffix to be added to each error message
	|
	*/	
	function set_error_delimiters($prefix = '<p>', $suffix = '</p>')
	{
		$this->_error_prefix = $prefix;
		$this->_error_suffix = $suffix;
	}
	
	/*
	|==========================================================
	| Run the Validator
	|==========================================================
	|
	| This function does all the work.
	|
	*/		
	function run()
	{
		/*
		|--------------------------------------------------
		| Do we even have any data to process?  Mm?
		|--------------------------------------------------
		*/	
		if (count($_POST) == 0 OR count($this->_rules) == 0)
		{
			return FALSE;
		}
	
		/*
		|--------------------------------------------------
		| Load the language file containing error messages
		|--------------------------------------------------
		*/	
		global $CI;
		$CI->lang->load('validation');
							
		/*
		|--------------------------------------------------
		| Cycle through the rules and test for errors
		|--------------------------------------------------
		*/
		foreach ($this->_rules as $field => $rules)
		{
			/*
			|--------------------------------------------------
			| Explode out the rules!
			|--------------------------------------------------
			*/
			$ex = explode('|', $rules);

			/*
			|--------------------------------------------------
			| Are we dealing with an "isset" rule?
			|--------------------------------------------------
			|
			| Before going further, we'll see if one of the rules
			| is to check whether the item is set (typically this
			| applies only to checkboxes).  If so, we'll
			| test for it here since there's not reason to go
			| further
			|
			*/
			if ( ! isset($_POST[$field]))
			{
				if (in_array('isset', $ex))
				{
					if ( ! isset($this->messages['isset'])) 
					{
						if (FALSE === ($line = $CI->lang->line('isset')))
						{
							$line = 'The field was not set';
						}							
					}
					else
					{
						$line = $this->_error_messages['isset'];
					}

					$field = ( ! isset($this->_fields[$field])) ? $field : $this->_fields[$field];
					$this->_error_array[] = sprintf($line, $field);	
				}
						
				continue;
			}
	
			/*
			|--------------------------------------------------
			| Set the current field
			|--------------------------------------------------
			|
			| The various prepping functions need to know the
			| current field name so they can do this:
			|
			| $_POST[$this->_current_field] == 'bla bla';
			|
			*/
			$this->_current_field = $field;

			/*
			|--------------------------------------------------
			| Cycle through the rules!
			|--------------------------------------------------
			*/
			foreach ($ex As $rule)
			{
				/*
				|--------------------------------------------------
				| Is the rule a callback?
				|--------------------------------------------------
				*/
			
				$callback = FALSE;
				if (substr($rule, 0, 9) == 'callback_')
				{
					$rule = substr($rule, 9);
					$callback = TRUE;
				}			
			
				/*
				|--------------------------------------------------
				| Strip the parameter (if exists) from the rule
				|--------------------------------------------------
				|
				| Rules can contain a parameter: max_length[5]
				|
				*/
			
				$param = FALSE;
				if (preg_match("/.*?(\[.*?\]).*/", $rule, $match))
				{
					$param = substr(substr($match['1'], 1), 0, -1);
					$rule  = str_replace($match['1'], '', $rule);
				}

				/*
				|--------------------------------------------------
				| Call the function that corresponds to the rule
				|--------------------------------------------------
				*/
				if ($callback === TRUE)
				{
					if ( ! method_exists($CI, $rule))
					{ 		
						continue;
					}
					
					$result = $CI->$rule($_POST[$field], $param);
				}
				else
				{				
					if ( ! method_exists($this, $rule))
					{
						/*
						|--------------------------------------------------
						| Run the native PHP function if called for
						|--------------------------------------------------
						|
						| If our own wrapper function doesn't exist we see
						| if a native PHP function does. Users can use
						| any native PHP function call that has one param.
						|
						*/
						if (function_exists($rule))
						{
							$_POST[$field] = $rule($_POST[$field]);
							$this->$field = $_POST[$field];
						}
											
						continue;
					}
					
					$result = $this->$rule($_POST[$field], $param);
				}
				
				/*
				|--------------------------------------------------
				| Did the rule test negatively?  If so, grab the error.
				|--------------------------------------------------
				*/
				if ($result === FALSE)
				{
					if ( ! isset($this->_error_messages[$rule])) 
					{
						if (FALSE === ($line = $CI->lang->line($rule)))
						{
							$line = 'A field error was encountered.';
						}							
					}
					else
					{
						$line = $this->_error_messages[$rule];;
					}				

					// Build the error message
					$mfield = ( ! isset($this->_fields[$field])) ? $field : $this->_fields[$field];
					$message = sprintf($line, $mfield, $param);

					// Set the error variable.  Example: $this->username_error
					$error = $field.'_error';
					$this->$error = $this->_error_prefix.$message.$this->_error_suffix;

					// Add the error to the error array
					$this->_error_array[] = $message;				
					continue 2;
				}
			}
		}
		
		$total_errors = count($this->_error_array);

		/*
		|--------------------------------------------------
		| Recompile the class variables
		|--------------------------------------------------
		|
		| If any prepping functions were called the $_POST data
		| might now be different then the corresponding class
		| variables so we'll set them anew.
		|
		*/	
		if ($total_errors > 0)
		{
			$this->_safe_form_data = TRUE;
		}
		
		$this->set_fields();
 
		/*
		|--------------------------------------------------
		| Did we end up with any errors?
		|--------------------------------------------------
		*/		
		if ($total_errors == 0)
		{
			return TRUE;
		}
		
		/*
		|--------------------------------------------------
		| Generate the error string
		|--------------------------------------------------
		*/		
		foreach ($this->_error_array as $val)
		{
			$this->error_string .= $this->_error_prefix.$val.$this->_error_suffix."\n";
		}

		return FALSE;
	}

	/*
	|==========================================================
	| Required
	|==========================================================
	|
	*/
	function required($str)
	{
		return (trim($str) == '') ? FALSE : TRUE;
	}
	
	/*
	|==========================================================
	| Match one field to another
	|==========================================================
	|
	*/
	function matches($str, $field)
	{
		if ( ! isset($_POST[$field]))
		{
			return FALSE;
		}
		
		return ($str !== $_POST[$field]) ? FALSE : TRUE;
	}
	
	/*
	|==========================================================
	| Minimum Length
	|==========================================================
	|
	*/	
	function min_length($str, $val)
	{
		if ( ! is_numeric($val))
		{
			return FALSE;
		}
	
		return (strlen($str) < $val) ? FALSE : TRUE;
	}

	/*
	|==========================================================
	| Max Length
	|==========================================================
	|
	*/	
	function max_length($str, $val)
	{
		if ( ! is_numeric($val))
		{
			return FALSE;
		}
	
		return (strlen($str) > $val) ? FALSE : TRUE;
	}

	/*
	|==========================================================
	| Exact Length
	|==========================================================
	|
	*/	
	function exact_length($str, $val)
	{
		if ( ! is_numeric($val))
		{
			return FALSE;
		}
	
		return (strlen($str) == $val) ? FALSE : TRUE;
	}

	/*
	|==========================================================
	| Valid Email
	|==========================================================
	|
	*/	
	function valid_email($str)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
	}
	
	/*
	|==========================================================
	| Alpha
	|==========================================================
	|
	*/	
	
	function alpha($str)
	{
		return ( ! preg_match("/^([-a-z])+$/i", $str)) ? FALSE : TRUE;
	}
	
	/*
	|==========================================================
	| Alpha-numeric
	|==========================================================
	|
	*/	
	function alpha_numeric($str)
	{
		return ( ! preg_match("/^([-a-z0-9])+$/i", $str)) ? FALSE : TRUE;
	}

	/*
	|==========================================================
	| Alpha-numeric with underscores and dashes
	|==========================================================
	|
	*/	
	function alpha_dash($str)
	{
		return ( ! preg_match("/^([-a-z0-9_-])+$/i", $str)) ? FALSE : TRUE;
	}
		
	/*
	|==========================================================
	| Numeric
	|==========================================================
	|
	*/	
	function numeric($str)
	{
		return ( ! is_numeric($str)) ? FALSE : TRUE;
	}
	
	/*
	|==========================================================
	| Set Select
	|==========================================================
	|
	| Enables pull-down lists to be set to the value the user
	| selected in the event of an error
	|
	*/	
	function set_select($field = '', $value = '')
	{
		if ($field == '' OR $value == '' OR  ! isset($_POST[$field]))
		{
			return '';
		}
			
		if ($_POST[$field] == $value)
		{
			return ' selected="selected"';
		}
	}

	/*
	|==========================================================
	| Set Radio
	|==========================================================
	|
	| Enables radio buttons to be set to the value the user
	| selected in the event of an error
	|
	*/	
	function set_radio($field = '', $value = '')
	{
		if ($field == '' OR $value == '' OR  ! isset($_POST[$field]))
		{
			return '';
		}
			
		if ($_POST[$field] == $value)
		{
			return ' checked="checked"';
		}
	}

	/*
	|==========================================================
	| Set Checkbox
	|==========================================================
	|
	| Enables checkboxes to be set to the value the user
	| selected in the event of an error
	|
	*/	
	function set_checkbox($field = '', $value = '')
	{
		if ($field == '' OR $value == '' OR  ! isset($_POST[$field]))
		{
			return '';
		}
			
		if ($_POST[$field] == $value)
		{
			return ' checked="checked"';
		}
	}

	/*
	|==========================================================
	| Prep data for form
	|==========================================================
	|
	| This function allows HTML to be safely shown in a form.
	| Special characters are converted.
	|
	*/
    function prep_for_form($str = '')
    {
    	if ($this->_safe_form_data == FALSE OR $str == '')
    	{
    		return $str;
    	}
            
		return str_replace(array("'", '"', '<', '>'), array("&#39;", "&quot;", '&lt;', '&gt;'), stripslashes($str));
    }

	/*
	|==========================================================
	| Prep URL
	|==========================================================
	|
	*/	
    function prep_url($str = '')
    {
		if ($str == 'http://' OR $str == '')
		{
			$_POST[$this->_current_field] = '';
			return;
		}
		
		if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://')
		{
			$str = 'http://'.$str;
		}
		
		$_POST[$this->_current_field] = $str;
    }

	/*
	|==========================================================
	| Strip Image Tags
	|==========================================================
	|
	*/	
    function strip_image_tags($str)
    {                    
        $_POST[$this->_current_field] = $this->input->strip_image_tags($str);
    }

	/*
	|==========================================================
	| XSS Clean
	|==========================================================
	|
	*/	
	function xss_clean($str)
	{
        $_POST[$this->_current_field] = $this->input->xss_clean($str);
	}

	/*
	|==========================================================
	| Convert PHP tags to entities
	|==========================================================
	|
	*/	
    function encode_php_tags($str)
    { 
    	$_POST[$this->_current_field] = str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
		// <? fixes BBEdit bug
	}

}
?>