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
| File: libraries/Router.php
|----------------------------------------------------------
| Purpose: Parses URIs and determines routing
|==========================================================
*/


class _Router {

	var $uri_string	= '';
	var $segments	= array();
	var $routes 	= array();
	var $class		= '';
	var $method		= 'index';
	var $default_controller;
	var $scaffolding_trigger;
	var $max_allowed_segments = 10;
	
	/*
	|=================================================
	|  Constructor
	|=================================================
	|
	| Simply loads the config/routes.php file.
	|
	*/
	function _Router()
	{	
		include_once(BASEPATH.'config/routes'.EXT);
		$this->routes = ( ! isset($route) OR ! is_array($route)) ? array() : $route;
		unset($route);
		log_message('debug', "Router Class Initialized");
	}

	/*
	|====================================================
	|  Determine the routing
	|====================================================
	|
	| This function determies what should be served based
	| on the URI request and any "routes" that have been 
	| set in the routing config file.
	|
	*/
	function fetch_routing($uri_protocol = 'auto')
	{
		/*
		|----------------------------------------------
		| Set the default controller
		|----------------------------------------------
		*/		
		$this->default_controller = ( ! isset($this->routes['default_controller']) OR $this->routes['default_controller'] == '') ? FALSE : strtolower($this->routes['default_controller']);
	
		/*
		|----------------------------------------------
		|  Fetch the URI string
		|----------------------------------------------
		|
		| Depending on the server, the URI will be
		| available in one of two globals:
		|
		| PATH_INFO
		| QUERY_STRING
		|
		*/
		switch ($uri_protocol)
		{
			case 'path_info'	: $this->uri_string = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');	
				break;
			case 'query_string' : $this->uri_string = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING'); 
				break;
			default : 
						$path_info = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
						
						if ($path_info != '' AND $path_info != "/".SELF)
						{
							$this->uri_string = $path_info;
						}
						else
						{
							$this->uri_string = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
						}
				break;
		}
	
		/*
		|----------------------------------------------
		| Is there a URI string?
		|----------------------------------------------
		|
		| If not, the default controller specified by
		| the admin in the "routes" file will be shown.
		|
		*/
		if ($this->uri_string == '')
		{
			if ($this->default_controller === FALSE)
			{
				show_error("Unable to determine what should be displayed. A default route has not been specified in the routing file.");
			}
		
			$this->set_default_controller();
			log_message('debug', "No URI present. Default controller set.");
			return;
		}

		/*
		|----------------------------------------------
		| Explode the URI Segments
		|----------------------------------------------
		|
		| The individual segments will be stored in the
		| $this->segments array.
		|
		*/		
		
		$this->compile_segments(explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $this->uri_string)));
		
		/*
		|----------------------------------------------
		| Remap the class/method if a route exists
		|----------------------------------------------
		*/		
		unset($this->routes['default_controller']);
		
		if (count($this->routes) > 0)
		{
			$this->parse_routes();
		}
	}

	
	/*
	|====================================================
	|  Parse Routes
	|====================================================
	|
	| This function matches any routes that may exist in
	| the config/routes.php file against the URI to 
	| determine if the class/method need to be remapped.
	|
	*/
	function parse_routes()
	{
		/*
		|----------------------------------------------
		| Turn the segment array into a URI string
		|----------------------------------------------
		*/

		$uri = implode('/', $this->segments);
		$num = count($this->segments);

		/*
		|----------------------------------------------
		| Is there a literal match?  If so we're done
		|----------------------------------------------
		*/

		if (isset($this->routes[$uri]))
		{
			$this->compile_segments(explode('/', $this->routes[$uri]), TRUE);		
			return;
		}
		
		/*
		|----------------------------------------------
		| Loop through the route array looking for wildcards
		|----------------------------------------------
		*/		
		foreach ($this->routes as $key => $val)
		{
			if (count(explode('/', $key)) != $num)
				continue;
		
			if (preg_match("|".str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key))."|", $uri))
			{
				$this->compile_segments(explode('/', $val), TRUE);		
				break;
			}
		}	
	}

	/*
	|====================================================
	|  Compile Segments
	|====================================================
	|
	| This function takes an array of URI segments as
	| input, and puts it into the $this->segments array.
	| It also sets the current class/method
	|
	*/
	function compile_segments($segs, $route = FALSE)
	{
		if (count($segs) > $this->max_allowed_segments)
		{
			exit('You have exeeded the number of allowed URI segments');
		}
		
		$segments = array();
	
		$i = 1;
		foreach($segs as $val)
		{		
			$segments[$i++] = $this->security_filtering(strtolower($val));
		}
		
		$this->set_class($segments['1']);
		
		if (isset($segments['2']))
		{
			/*
			|----------------------------------------------
			| No funny business with scaffolding
			|----------------------------------------------
			|
			| The saffolding function can't be called directly.
			|
			*/
			if ($segments['2'] == '_scaffolding')
			{
				log_message('error', 'Scaffolding can not be accessed directly');
				show_404();
			}
			/*
			|----------------------------------------------
			| A legit scaffolding request
			|----------------------------------------------
			*/
			if ($this->routes['scaffolding_trigger'] == $segments['2'])
			{
				$this->set_method('_scaffolding');
				$this->scaffolding_trigger = $segments['2'];
				unset($this->routes['scaffolding_trigger']);
			}
			else
			{
				/*
				|----------------------------------------------
				| A standard method request
				|----------------------------------------------
				*/
				$this->set_method($segments['2']);
			}
		}
		
		if ($route == FALSE)
		{
			$this->segments = $segments;
		}
		
		unset($segments);
	}
	
	/*
	|====================================================
	|  Filter segments for malicious characters
	|====================================================
	*/	
	function security_filtering($str)
	{
		 if ( ! preg_match("/^[a-z0-9\/~\s\.:_-]+$/i", $str))
		 { 
			exit('The URI you submitted has disallowed characters: '.$str);
		 }
		 
		 return $str;
	}

	/*
	|====================================================
	|  Set the class/method names to site default
	|====================================================
	*/	
	function set_default_controller()
	{
		$this->set_class($this->default_controller);
		$this->set_method('index');
	}

	/*
	|====================================================
	|  Set the class name
	|====================================================
	*/	
	function set_class($class)
	{
		$this->class = $class;
	}

	/*
	|====================================================
	|  Fetch the current class
	|====================================================
	*/	
	function fetch_class()
	{
		return $this->class;
	}

	/*
	|====================================================
	|  Set the method name
	|====================================================
	*/	
	function set_method($method)
	{
		$this->method = $method;
	}
	
	/*
	|====================================================
	|  Fetch the current method
	|====================================================
	*/	
	function fetch_method()
	{
		return $this->method;
	}
}
?>