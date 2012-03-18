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
| File: libraries/Controller.php
|----------------------------------------------------------
| Purpose: Application Controller
|==========================================================
*/


class Controller {

	var $ci_autoload	= array('config', 'input', 'benchmark', 'uri', 'lang', 'output');
	var $ci_autoconfig	= array();
	var $ci_is_loaded	= array();
	var $ci_scaffolding	= FALSE;
	var $ci_scaff_table	= FALSE;
	var $ci_system_time;
	
	/*
	|==========================================================
	| Constructor
	|==========================================================
	|
	| Loads the "autoload" config file to see if anything 
	| needs to be loaded by default.  It also loads the
	| base classes we need for each execution.
	|
	*/
	function Controller()
	{	
		/*
		|----------------------------------------------
		| Auto-initialize
		|----------------------------------------------
		|
		| This initializes the core systems that are
		| specified in the libraries/autoload.php file, as
		| well as the systems specified in the $autoload
		| class array above.
		|
		| It returns the "autoload" array so we can
		| pass it to the Loader class since it needs
		| to autoload plugins and helper files
		|
		*/
	
		$autoload = $this->auto_initialize();
		
		/*
		|----------------------------------------------
		| Instantiate the "Loader" class
		|----------------------------------------------
		*/
		require_once(BASEPATH.'libraries/Loader'.EXT);
		$this->load = new _Loader();
		$this->ci_is_loaded[] = 'load';
		$this->load->_autoload($autoload);
		
		log_message('debug', "Controller Class Initialized");
	}
	
	/*
	|==========================================================
	| Auto-initialize Core Classes
	|==========================================================
	|
	| The config/autoload.php file contains an array that
	| permits sub-systems to be loaded automatically.
	|
	*/
	function auto_initialize()
	{
		include_once(BASEPATH.'config/autoload'.EXT);
		
		if ( ! isset($autoload))
		{
			return FALSE;
		}
		
		if ( ! is_array($autoload['core']))
		{
			$autoload['core'] = array($autoload['core']);
		}

		if ( ! is_array($autoload['config']))
		{
			$autoload['config'] = array($autoload['config']);
		}

		$this->ci_autoconfig = $autoload['config'];
		
		foreach (array_merge($this->ci_autoload, $autoload['core']) as $item)
		{
			$this->initialize($item);
		}
		
		return $autoload;
	}

	/*
	|==========================================================
	| Initialization Handler
	|==========================================================
	|
	| Looks for the existence of a handler method and calls it
	|
	*/
	function initialize($what, $param = FALSE)
	{		
		$method = 'init_'.strtolower(str_replace(EXT, '', $what));

		if ( ! method_exists($this, $method))
		{
			if ( ! file_exists(BASEPATH.'init/'.$method.EXT))
			{
				log_message('error', "Unable to load the requested class: ".$what);
				show_error("Unable to load the class: ".$what);
			}
			
			 include(BASEPATH.'init/'.$method.EXT);
		}
		else
		{
			if ($param === FALSE)
			{
				$this->$method();
			}
			else
			{
				$this->$method($param);
			}
		}
	}

	/*
	|==========================================================
	| Tests to see if an class is loaded
	|==========================================================
	*/
	function is_loaded($class)
	{
		return ( ! in_array($class, $this->ci_is_loaded)) ? FALSE : TRUE;
	}

	/*
	|==========================================================
	| Initialize Input Class
	|==========================================================
	|
	|  We sanitize input automatically
	|
	*/
	function init_input()
	{
		if (class_exists('_Input')) return;
		require_once(BASEPATH.'libraries/Input'.EXT);	
		$this->input = new _Input($this->config->item('global_xss_filtering'));
		$this->input->sanitize_globals();	
		$this->ci_is_loaded[] = 'input';
	}

	/*
	|==========================================================
	| Initialize Benchmark
	|==========================================================
	|
	| Since the Benchmark class was instantiated by the front
	| controller all we need to do is globalize the object
	| and set a local version.
	|
	*/
	function init_benchmark()
	{	
		global $BM;
		$this->benchmark =& $BM;
		$this->ci_is_loaded[] = 'benchmark';
	}
	
	/*
	|==========================================================
	| Initialize Database
	|==========================================================
	|
	| Loads the DB config file, instantiates the DB class
	| and connects to the specified DB.
	*/
	function init_database($params = '', $return = FALSE)
	{
		$dsn_str = FALSE;
		$db_vals = array('hostname' => '', 'username' => '', 'password' => '', 'database' => '', 'pconnect' => FALSE, 'dbdriver' => 'mysql', 'db_debug' => FALSE);
	
		if (is_array($params))
		{		
			foreach ($db_vals as $key => $val)
			{
				if (isset($params[$key]))
				{
					$db_vals[$key] = $params[$key];
				}
			}		
		}
		else
		{
			if (strpos($params,'://') !== FALSE) 
			{
				$dsn_str = TRUE;
			}
			else
			{
				include(BASEPATH.'config/database'.EXT);
				$group = ($params == '') ? $active_group : $params;
				
				foreach ($db_vals as $key => $val)
				{
					if (isset($db[$group][$key]))
					{
						$db_vals[$key] = $db[$group][$key];
					}
				}
			}
		}
		
		if ( ! class_exists('DB'))
		{
			require_once(BASEPATH.'drivers/'.$db_vals['dbdriver'].EXT);
		}

		if ($dsn_str === TRUE)
		{
			$DB = new DB($params);
		}
		else
		{
			$DB = new DB(
							$db_vals['hostname'],
							$db_vals['username'],
							$db_vals['password'],
							$db_vals['database']
						);
			
			$DB->set_debug($db_vals['db_debug']);
			$DB->set_persistence($db_vals['pconnect']);			
			$DB->connect();
		}
		
		if ($return === TRUE)
		{
			return $DB;
		}
		
		$this->ci_is_loaded[] = 'db';
		$this->db = $DB;		
	}

	/*
	|==========================================================
	| Initialize Config Class
	|==========================================================
	*/
	function init_config()
	{
		if (class_exists('_Config')) return;
		
		require_once(BASEPATH.'libraries/Config'.EXT);
		$this->config = new _Config();
		
		foreach ($this->ci_autoconfig as $conf)
		{
			$this->config->load($conf);
		}
		
		$this->ci_is_loaded[] = 'config';
	}

	/*
	|==========================================================
	| Initialize URI Class
	|==========================================================
	*/
	function init_uri()
	{
		if (class_exists('_URI')) return;
		
		require_once(BASEPATH.'libraries/URI'.EXT);
		$this->uri = new _URI();
		$this->ci_is_loaded[] = 'uri';
	}

	/*
	|==========================================================
	| Initialize Language Class
	|==========================================================
	*/
	function init_lang()
	{
		if (class_exists('_Language')) return;
		
		require_once(BASEPATH.'libraries/Language'.EXT);
		$this->lang = new _Language();
		$this->ci_is_loaded[] = 'lang';
	}

	/*
	|==========================================================
	| Initialize Output Class
	|==========================================================
	|
	| Since the Output class was instantiated by the front
	| controller all we need to do is globalize the object
	| and set a local version.
	|
	*/
	function init_output()
	{
		global $OUT;
		$this->output =& $OUT;
		$this->ci_is_loaded[] = 'output';
	}
	
	/*
	|==========================================================
	| Initialize Scaffolding
	|==========================================================
	|
	| This initializing function works a bit different than the
	| others. It doesn't load the class.  Instead, it simply
	| sets a flag indicating that scaffolding is allowed to be
	| used.  The actual scaffolding function below is
	| called by the front controller based on whether the
	| second segment of the URL matches the "secret" scaffolding
	| word stored in the config/routes.php
	|
	*/
	function init_scaffolding($table = FALSE)
	{
		if ($table === FALSE)
		{
			show_error('You must include the name of the table you would like access when you initialize scaffolding');
		}
		
		$this->ci_scaffolding = TRUE;
		$this->ci_scaff_table = $table;
	}

	/*
	|==========================================================
	| Scaffolding Class Interface
	|==========================================================
	|
	*/
	function _scaffolding()
	{
		if ($this->ci_scaffolding === FALSE OR $this->ci_scaff_table === FALSE)
		{
			show_404('Scaffolding unavailable');
		}
		
		if (class_exists('Scaffolding')) return;
			
		if ( ! in_array($this->uri->segment(3), array('add', 'insert', 'edit', 'update', 'view', 'delete', 'do_delete')))
		{
			$method = 'view';
		}
		else
		{
			$method = $this->uri->segment(3);
		}
		
		require_once(BASEPATH.'scaffolding/Scaffolding'.EXT);
		$this->scaff = new Scaffolding($this->ci_scaff_table);
		$this->scaff->$method();
	}

	/*
	|=====================================================
	| Get the current time
	|=====================================================
	*/    
	function get_system_time()
	{
		return ($this->ci_system_time == '') ? $this->set_system_time() : $this->ci_system_time;
	}

	/*
	|=====================================================
	| Set the "now" time.
	|=====================================================
	*/    
	function set_system_time()
	{		
		if (strtolower($this->config->item('time_reference')) == 'gmt') 
		{
			$now = time(); 
        	$this->ci_system_time = mktime(gmdate("H", $now), gmdate("i", $now), gmdate("s", $now), gmdate("m", $now), gmdate("d", $now), gmdate("Y", $now));   
		
			if (strlen($this->ci_system_time) < 10)
			{
				$this->ci_system_time = time();
				log_message('error', 'The Date class could not set a proper GMT timestamp so the local time() value was used.');
			}
		}
		else
		{
			$this->ci_system_time = time();
		}
		
		return $this->ci_system_time;
	}

}
?>