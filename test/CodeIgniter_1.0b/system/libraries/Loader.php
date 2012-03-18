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
| File: libraries/Loader.php
|----------------------------------------------------------
| Purpose: Loads views and files
|==========================================================
|
| Note: The function names in this file may seem a little
| nondescript.  That's because they are intended to be
| read as part of object name.  For exmple, the "view"
| function will be used like:
|
|	$this->load->view
|
*/


class _Loader Extends Controller {

	var $ob_level;
	var $cached_vars	= array();
	var $user_vars		= array();
	var $helpers		= array();
	var $plugins		= array();
	var $scripts		= array();
	var $languages		= array();
	var $view_path		= '';

	/*
	|=================================================
	|  Constructor
	|=================================================
	|
	| The constructor does two things:
	|
	| Sets the initial output buffering level.  We use
	| output buffering to suppress the output so it can be
	| sent to the browser at the end of sysetm execution.
	| This increases performance and gives us more control.
	|
	*/
	function _Loader()
	{
		log_message('debug', "Loader Class Initialized");
		$this->view_path = BASEPATH.'application/views/';
		$this->ob_level = ob_get_level();
	}
	
	/*
	|==========================================================
	| Class Loader
	|==========================================================
	|
	| This function lets users load and instantiate classes.
	| It is designed to be called from a user's app controllers.
	| The reason there is a conditional with an array called 
	| $recursive is to overcome a problem that can 
	| happen if the user puts this function in a 
	| constructor.  In that event, the $CI object variable set by 
	| the front controller doesn't yet exist as an object,
	| which prevents us from instantiating anyting in the 
	| Controller class, so we will instead set the 
	| $recursive array which the front controller 
	| will use to initialize the classes with. 
	| Confused?  Exhales.... Me too...
	|
	*/	
	function library($class)
	{
		global $CI, $recursive;
		
		if ($class == '')
			return;
		
		if (is_object($CI))
		{
			$CI->initialize($class);
		}
		else
		{
			$recursive[$class] = FALSE;
		}
	}

	/*
	|==========================================================
	| Database Loader
	|==========================================================
	|
	*/	
	function database($db = '', $return = FALSE)
	{
		global $CI, $recursive;
		
		if (is_object($CI))
		{
			if ($return === TRUE)
			{
				return $CI->init_database($db, TRUE);
			}
			else
			{
				$CI->init_database($db);
			}
		}
		else
		{
			$recursive['database'] = FALSE;
		}
	}

	/*
	|==========================================================
	| Scaffolding Loader
	|==========================================================
	|
	*/	
	function scaffolding($table = '')
	{
		global $CI, $recursive;
		
		if ($table == FALSE)
		{
			show_error('You must include the name of the table you would like access when you initialize scaffolding');
		}
		
		if (is_object($CI))
		{
			$CI->init_scaffolding($table);
		}
		else
		{
			$recursive['scaffolding'] = $table;
		}
	}

	/*
	|==========================================================
	| Load View
	|==========================================================
	|
	| This function is used to load a "view" file.  It has 
	| three parameters:
	|
	| 1. The name of the "view" file to be included.
	| 2. An associative array of data to be extracted for use in the view.
	| 3. TRUE/FALSE - whether to return the data or load it.  In
	| some cases it's advantageous to be able to retun data so that
	| a developer can process it in some way.
	|
	*/
	function view($view, $vars = array(), $return = FALSE)
	{
		if (is_array($view))
		{
			$temp	= $view;
			$view	= $temp['0'];			
			$vars	= $temp['1'];			
			$return	= $temp['2'];			
		}
		return $this->_load(array('view' => $view, 'vars' => $vars, 'return' => $return));
	}

	/*
	|==========================================================
	| Load File
	|==========================================================
	|
	| This is a generic file loader.
	|
	*/
	function file($path, $return = FALSE)
	{
		return $this->_load(array('path' => $path, 'return' => $return));
	}

	/*
	|==========================================================
	| Set Variables
	|==========================================================
	|
	| Once variables are set they become availabe within
	| the controller class and its "view" files.
	|
	*/
	function vars($vars = array())
	{
		if (is_array($vars) AND count($vars) > 0)
		{
			foreach ($vars as $key => $val)
			{
				$this->user_vars[$key] = $val;
			}
		}
	}

	/*
	|==========================================================
	| Load Helper
	|==========================================================
	|
	| This function loads the specified helper file.
	|
	*/
	function helper($helpers = array())
	{
		if ( ! is_array($helpers))
		{
			$helpers = array($helpers);
		}
	
		foreach ($helpers as $helper)
		{
			if (isset($this->helpers[$helper]))
			{
				continue;
			}
		
			$helper = strtolower(str_replace(EXT, '', str_replace('_helper', '', $helper)).'_helper');
		
			if ( ! include_once(BASEPATH.'helpers/'.$helper.EXT))
			{
				show_error('Unable to load the requested file: helpers/'.$helper.EXT);
			}
			
			$this->helpers[$helper] = TRUE;
		}
		
		log_message('debug', 'Helpers loaded: '.implode(', ', $helpers));
	}

	/*
	|==========================================================
	| Load Helpers
	|==========================================================
	|
	| This is simply an alias to the above function in case the
	| user has written the plural form of this function.
	|
	*/
	function helpers($helpers = array())
	{
		$this->helper($helpers);
	}

	/*
	|==========================================================
	| Load Plugin
	|==========================================================
	|
	| This function loads the specified plugin.
	|
	*/
	function plugin($plugins = array())
	{
		if ( ! is_array($plugins))
		{
			$plugins = array($plugins);
		}
	
		foreach ($plugins as $plugin)
		{
			if (isset($this->plugins[$plugin]))
			{
				continue;
			}
	
			$plugin = strtolower(str_replace(EXT, '', str_replace('_plugin.', '', $plugin)).'_pi');
		
			if ( ! include_once(BASEPATH.'plugins/'.$plugin.EXT))
			{
				show_error('Unable to load the requested file: plugins/'.$plugin.EXT);
			}
			
			$this->plugins[$plugin] = TRUE;
		}
		
		log_message('debug', 'Plugins loaded: '.implode(', ', $plugins));
	}

	/*
	|==========================================================
	| Load Script
	|==========================================================
	|
	| This function loads the specified include file from the
	| application/scripts/ folder
	|
	*/
	function script($scripts = array())
	{
		if ( ! is_array($scripts))
		{
			$scripts = array($scripts);
		}
	
		foreach ($scripts as $script)
		{
			if (isset($this->scripts[$script]))
			{
				continue;
			}
	
			$script = strtolower(str_replace(EXT, '', $script));
		
			if ( ! include_once(BASEPATH.'application/scripts/'.$script.EXT))
			{
				show_error('Unable to load the requested script: scripts/'.$script.EXT);
			}
			
			$this->scripts[$script] = TRUE;
		}
		
		log_message('debug', 'Scripts loaded: '.implode(', ', $scripts));
	}

	/*
	|==========================================================
	| Load Plugins
	|==========================================================
	|
	| This is simply an alias to the above function in case the
	| user has written the plural form of this function.
	|
	*/
	function plugins($plugins = array())
	{
		$this->plugin($plugins);
	}

	/*
	|==========================================================
	| Loads a language file
	|==========================================================
	*/
	
	function language($file = '', $lang = '')
	{
		call('lang', 'load', array($file, $lang));
	}

	/*
	|==========================================================
	| Loads a config file
	|==========================================================
	*/
	
	function config($file = '')
	{
		call('config', 'load', $file);
	}

	/*
	|==========================================================
	| Set the Path to the "views" folder
	|==========================================================
	*/
	
	function _set_view_path($path)
	{
		$this->view_path = $path;
	}

	/*
	|==========================================================
	| Loader
	|==========================================================
	|
	| This function isn't called directly.  It's called from
	| the two functions above.  It's used to load views and files
	|
	*/
	function _load($data)
	{
		/*
		|----------------------------------------------
		| Globalize objects
		|----------------------------------------------
		|
		| We have a bit of an issue due to the way PHP
		| protects the scope of object, and due to the fact
		| that PHP does not support multiple-inheritance. 
		| All of the objects instantiated by the Controller class
		| (for example: $this->db, $this->config, $this->uri, etc.)
		| are only available to Controller and its direct
		| child.  The "view" files loaded by this function, 
		| since they are not childrend of Controller, will
		| not have access to the objects unless we globallize
		| them.
		|
		*/
		global $CI, $OUT;
		foreach ($CI->ci_is_loaded as $val)
		{
			$this->$val =& $CI->$val;
		}
				
		/*
		|----------------------------------------------
		| Set the default data variables
		|----------------------------------------------
		*/		
		foreach (array('view', 'vars', 'path', 'return') as $val)
		{
			$$val = ( ! isset($data[$val])) ? FALSE : $data[$val];
		}
		
		/*
		|----------------------------------------------
		| Extract and cache variables
		|----------------------------------------------
		|
		| You can either set variables using the dedicated
		| $this->load_vars() function or via the second
		| parameter of this function. We'll
		| merge the two types and cache them so that
		| views that are embedded within other views
		| can have access to these variables.
		|
		*/	
		
		if (count($this->cached_vars) == 0 AND is_array($vars))
		{
			$this->cached_vars = array_merge($this->user_vars, $vars);
		}		
		extract($this->cached_vars);
		
		/*
		|----------------------------------------------
		| Set the path to the requested file
		|----------------------------------------------
		*/
		if ($path == '')
		{
			$ext = pathinfo($view, PATHINFO_EXTENSION);
			$file = ($ext == '') ? $view.EXT : $view;
			$path = $this->view_path.$file;
		}
		else
		{
			$x = explode('/', $path);
			$file = end($x);
		}
		
		
		/*
		|----------------------------------------------
		| Buffer the output
		|----------------------------------------------
		|
		| We buffer the output for two reasons:
		| 1. Speed. You get a significant speed boost.
		| 2. So that the final rendered template can be 
		| post-processed by the output class.  Why do we
		| need post processing?  For one thing, in order to 
		| show the elapsed page load time.  Unless we
		| can intercept the content right before it's sent to
		| the browser and then stop the timer, it won't be acurate.
		|
		*/
		ob_start();
	
		if ( ! file_exists($path))
		{
			show_error('Unable to load the requested file: '.$file);
		}
		
		include($path);
		log_message('debug', 'File loaded: '.$path);
		
		/*
		|----------------------------------------------
		| Return the file data if requested to
		|----------------------------------------------
		*/
		if ($return === TRUE)
		{
			$buffer = ob_get_contents();					
			ob_end_clean(); 
			return $buffer;
		}

		/*
		|----------------------------------------------
		| Flush the buffer
		|----------------------------------------------
		|
		| In order to permit templates (views) to be nested within
		| other views, we need to flush the content back out whenever 
		| we are beyond the first level of output buffering so that 
		| it can be seen and included  properly by the first included 
		| template and any subsequent ones. Oy!
		|
		*/		
		if (ob_get_level() > $this->ob_level + 1)
		{
			ob_end_flush();
		}
		else
		{
			$OUT->set_output(ob_get_contents());
			ob_end_clean();
		}
	}

	/*
	|==========================================================
	| Autoloader
	|==========================================================
	|
	| The config/autoload.php file contains an array that
	| permits sub-systems, plugins, and helpers to be loaded
	| automatically.
	|
	*/
	function _autoload($autoload)
	{
		if ($autoload === FALSE)
		{
			return;
		}
	
		foreach (array('helper', 'plugin', 'script') as $type)
		{
			if (isset($autoload[$type]))
			{
				if ( ! is_array($autoload[$type]))
				{
					$autoload[$type] = array($autoload[$type]);
				}
			
				foreach ($autoload[$type] as $item)
				{
					$this->$type($item);
				}
			}
		}
	}

}
?>