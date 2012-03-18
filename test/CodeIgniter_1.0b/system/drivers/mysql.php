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
| File: drivers/mysql.php
|----------------------------------------------------------
| Purpose: MySQL Database Class
|==========================================================
*/

class DB {
	var $username;
	var $password;
	var $hostname;
	var $database;
	var $persistent		= FALSE;
	var $create_db		= FALSE;
	var $conn_id		= FALSE;
	var $result_id		= FALSE;
	var $debug			= FALSE;
	var $delete_hack	= TRUE;
	var $benchmark		= 0;
	var $query_count	= 0;
	var $bind_marker	= '?';
	
	// Active record variables
	var $where			= FALSE;
	var $limit			= FALSE;
	var $offset			= FALSE;
	var $order			= FALSE;
	var $order_by		= FALSE;
	var $active_table	= '';
	var $active_data	= array();
	var $active_where	= array();

	
	/*
	|=====================================================
	| Set Connection Variables
	|=====================================================
	|
	| Database settings can be passed as discreet 
	| parameters or as a data source name in the first 
	| parameter. DSNs must have this prototype:
    | $dsn = 'driver://username:password@hostname/database';
	|
	*/
	function DB($hostname, $username = '', $password = '', $database = '', $create_db = FALSE)
	{
		if ( ! strpos($hostname,'://')) 
		{
			$this->hostname  = $hostname;	
			$this->username  = $username;
			$this->password  = $password;
			$this->database	 = $database;
			$this->create_db = $create_db;
		}
		else
		{
			if (FALSE === ($dsn = @parse_url($hostname)))
			{
				log_message('error', 'Invalid DB Connection String');
			
				if ($this->debug)
				{
					return $this->show_error('db_invalid_connection_str');
				}
				return FALSE;			
			}
			
			$this->hostname = ( ! isset($dsn['host'])) ? '' : rawurldecode($dsn['host']);
			$this->username = ( ! isset($dsn['user'])) ? '' : rawurldecode($dsn['user']);
			$this->password = ( ! isset($dsn['pass'])) ? '' : rawurldecode($dsn['pass']);
			$this->database = ( ! isset($dsn['path'])) ? '' : rawurldecode(substr($dsn['path'], 1));
		}
		
		log_message('debug', 'MySQL Database Class Initialized');
	}

	/*
	|============================================
	| Connect to the database
	|============================================
	*/
    function connect()
    {
    	if ($this->debug)
    	{
			$this->conn_id = ($this->persistent == FALSE) ? mysql_connect($this->hostname, $this->username, $this->password) : mysql_pconnect($this->hostname, $this->username, $this->password);
		}
		else
		{
			$this->conn_id = ($this->persistent == FALSE) ? @mysql_connect($this->hostname, $this->username, $this->password) : @mysql_pconnect($this->hostname, $this->username, $this->password);
		}
        
        if ( ! $this->conn_id)
        { 
			log_message('error', 'Unable to connect to the database');
			
            if ($this->debug)
            {
				return $this->show_error('db_unable_to_connect');
            }
            return FALSE;        
        }
        
		if ( ! $this->select_db())
		{
			log_message('error', 'Unable to select database: '.$this->database);
		
            if ($this->debug)
            {
				return $this->show_error('db_unable_to_select', $this->database);
            }
            return FALSE;        
		}
        
        return TRUE;
	}

	/*
	|============================================
	| Non-persistent connection
	|============================================
	*/
	function nconnect()
	{
		$this->persistent = FALSE;
		return $this->connect();
	}

	/*
	|============================================
	| Persistent connection
	|============================================
	*/
	function pconnect()
	{
		$this->persistent = TRUE;
		return $this->connect();
	}

	/*
	|============================================
	| Select Database
	|============================================
	*/
    function select_db()
    {
		if ( ! @mysql_select_db($this->database, $this->conn_id))
		{
			if ($this->create_db == FALSE)
			{
				return FALSE;
			}
			else
			{				
				if ( ! @mysql_query("CREATE DATABASE IF NOT EXISTS ".$this->database, $this->conn_id)) 
				{
					log_message('error', 'Unable to create database: '.$this->database);
				
					if ($this->debug)
					{
						return $this->show_error('db_unable_to_create', $this->database);
					}
					return FALSE;
				} 
				
				$this->create_db = FALSE;
				return $this->select_db();				
			}
		}
		
		return TRUE;    
	}

	/*
	|============================================
	| Execute the Query
	|============================================
	*/
    function query($sql, $binds = FALSE)
    {
		if ( ! $this->conn_id)
		{
			$this->connect();
		}

		if ($sql == '')
		{
            if ($this->debug)
            {
				log_message('error', 'Invalid query: '.$sql);
				return $this->show_error('db_invalid_query');
            }
            return FALSE;        
		}
		
		/*
		|----------------------------------------
		| Compile binds if needed
		|----------------------------------------
		*/
		if ($binds !== FALSE)
		{
			$sql = $this->compile_binds($sql, $binds);
		}

		/*
		|----------------------------------------
		| Pre-process the SQL Query
		|----------------------------------------
		*/
		$sql = $this->prep_query($sql);
	
		/*
		|----------------------------------------
		| Start the Query Timer
		|----------------------------------------
		*/
        $time_start = list($sm, $ss) = explode(' ', microtime());

		/*
		|----------------------------------------
		| Run the Query
		|----------------------------------------
		*/
        if ( FALSE === ($this->result_id = @mysql_query($sql, $this->conn_id)))
        { 
            if ($this->debug)
            {
				log_message('error', 'Query error: '.$this->error_message());
				return $this->show_error(
										array(
												'Error Number: '.$this->error_number(), 
												$this->error_message(),
												$sql
											)
										);
            }
          
          return FALSE;
        }
        
		/*
		|----------------------------------------
		| Stop and aggregate the query time results
		|----------------------------------------
		*/		
		$time_end = list($em, $es) = explode(' ', microtime());
		$this->benchmark += ($em + $es) - ($sm + $ss);

		/*
		|----------------------------------------
		| Increment the query counter
		|----------------------------------------
		*/
        $this->query_count++;

		/*
		|----------------------------------------
		| Instnatiate and return the result object
		|----------------------------------------
		*/
		return new DB_result($this->conn_id, $this->result_id);
	}

	/*
	|============================================
	| Prep the SQL
	|============================================
	|
	| This lets us pre-process the query if needed
	|
	*/
    function prep_query($sql)
    {
		/*
		|----------------------------------------------
		| "DELETE FROM TABLE" returns 0 affected rows
		| This hack modifies the query so that it 
		| returns the number of affected rows
		|----------------------------------------------
		*/    
		if ($this->delete_hack === TRUE)
		{
			if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql)) 
			{
				$sql = preg_replace("/^\s*DELETE\s+FROM\s+(\S+)\s*$/", "DELETE FROM \\1 WHERE 1=1", $sql);
			}
		}
		return $sql;
    }

	/*
	|============================================
	| Active Record - Select the current table
	|============================================
	*/
	function use_table($table)
	{
		$this->active_table	= $table;
		$this->init_active_record();
	}
	
	/*
	|============================================
	| Initialize the Active Record Variables
	|============================================
	*/
	function init_active_record()
	{
		$this->where		= FALSE;
		$this->limit		= FALSE;
		$this->offset		= FALSE;
		$this->order		= FALSE;
		$this->active_data	= array();
		$this->active_where	= array();
	}

	/*
	|============================================
	| Active Record - Set a column key/value
	|============================================
	*/
	function set($key, $value = '')
	{
		if ( ! is_array($key))
		{
			$this->active_data[$key] = $value;
		}	
		else
		{
			foreach ($key as $k => $v)
			{
				$this->active_data[$k] = $v;
			}
		}
	}

	/*
	|============================================
	| Active Record - Set the "where" values
	|============================================
	*/
	function where($key, $value = '')
	{
		if ($value == '')
		{
			$this->where = $key;
		}
		else
		{
			if (is_array($value))
			{
				foreach ($key as $k => $v)
				{
					$this->active_where[$key] = $value;
				}
			}
			else
			{
				$this->active_where[$key] = $value;
			}
		}
	}

	/*
	|============================================
	| Active Record - Set the "limit" value
	|============================================
	*/
	function limit($value)
	{
		$this->limit = $value;
	}

	/*
	|============================================
	| Active Record - Set the "offset" value
	|============================================
	*/
	function offset($value)
	{
		$this->offset = $value;
	}

	/*
	|============================================
	| Active Record - Set the "order" value
	|============================================
	*/
	function order($value)
	{
		$this->order = $value;
	}

	/*
	|============================================
	| Active Record - Set the "order by" value
	|============================================
	*/
	function order_by($value)
	{
		$this->order_by = $value;
	}

	/*
	|============================================
	| Active Record - Get
	|============================================
	*/
	function get($fields = FALSE)
	{
		if ($this->active_table == '')
		{
            if ($this->debug)
            {
				return $this->show_error('db_must_set_table');
            }
            return FALSE;        
		}
	
		$sql = "SELECT ";
		
		if ($fields === FALSE)
		{
			$sql .= '*';
		}
		else
		{
			if ( ! is_array($fields))
			{
				$fields = array($fields);
			}
			
			$sql .= implode(',', $fields);
		}
		
		$sql .= " FROM {$this->active_table}";
		
		if ($this->where !== FALSE)
		{
			$sql .= " WHERE ".$this->where;
		}
		else
		{
			if (count($this->active_where) > 0)
			{
				$sql .= " WHERE ".implode(' AND ', $this->comparison_string($this->active_where));
			}
		}
		
		if ($this->order_by !== FALSE)
		{
			$sql .= " ORDER BY ".$this->order_by;
			
			if ($this->order !== FALSE)
			{
				$sql .= ($this->order == 'desc') ? ' DESC' : ' ASC';
			}		
		}
		
		if (is_numeric($this->limit))
		{
			$this->offset = ( ! is_numeric($this->offset) OR $this->offset == 0) ? '' : $this->offset.',';
			
 			$sql .= ' LIMIT '.$this->offset.' '.$this->limit;
		}
		
		$this->init_active_record();

		return $this->query($sql);		
	}

	/*
	|============================================
	| Active Record - Insert
	|============================================
	*/
	function insert()
	{
		if ($this->active_table == '')
		{
            if ($this->debug)
            {
				return $this->show_error('db_must_set_table');
            }
            return FALSE;        
		}
		
		if (count($this->active_data) == 0)
		{
            return FALSE;        
		}
	
		$data = array();
		foreach($this->active_data as $val)
		{
			$data[] = $this->smart_escape_str($val);		
		}
	
		$sql = "INSERT INTO {$this->active_table} (".implode(', ', array_keys($this->active_data)).") VALUES (".implode(',', $data).")";
			
		$this->init_active_record();
		return $this->query($sql);
	}

	/*
	|============================================
	| Active Record - Update
	|============================================
	*/
	function update()
	{
		if (count($this->active_data) == 0)
		{
            if ($this->debug)
            {
				return $this->show_error('db_must_use_set');
            }
            return FALSE;        
		}

		if ($this->active_table == '')
		{
            if ($this->debug)
            {
				return $this->show_error('db_must_set_table');
            }
            return FALSE;        
		}
	
		if (count($this->active_where) == 0 AND $this->where === FALSE)
		{
            if ($this->debug)
            {
				return $this->show_error('db_must_use_where');
            }
            return FALSE;        
		}
		
		$sql = "UPDATE {$this->active_table} SET ".implode(',', $this->comparison_string($this->active_data));
		
		if ($this->where !== FALSE)
		{
			$sql .= " WHERE ".$this->where;
		}
		else
		{
			$sql .= " WHERE ".implode(' AND ', $this->comparison_string($this->active_where));
		}

		
		$this->init_active_record();
		return $this->query($sql);
	}

	/*
	|============================================
	| Active Record - Delete
	|============================================
	*/
	function delete()
	{
		if ($this->active_table == '')
		{
            if ($this->debug)
            {
				return $this->show_error('db_must_use_set');
            }
            return FALSE;        
		}
	
		if (count($this->active_where) == 0 AND $this->where === FALSE)
		{
            if ($this->debug)
            {
				return $this->show_error('db_del_must_use_where');
            }
            return FALSE;        
		}		
		
		$sql = "DELETE FROM  {$this->active_table} ";
		
		if ($this->where !== FALSE)
		{
			$sql .= " WHERE ".$this->where;
		}
		else
		{
			$sql .= " WHERE ".implode(' AND ', $this->comparison_string($this->active_where));
		}

		$this->init_active_record();
		
		return $this->query($sql);
	}

	/*
	|============================================
	| Active Record - Build a comparison string
	|============================================
	*/
	function comparison_string($array)
	{
		$data = array();
		foreach($array as $key => $val)
		{
			$data[] = $key." = ".$this->smart_escape_str($val);
		}
		
		return $data;
	}

	/*
	|============================================
	| Compile Bindings
	|============================================
	*/
	function compile_binds($sql, $binds)
	{	
		if (FALSE === strpos($sql, $this->bind_marker))
		{
			return $sql;
		}
		
		if ( ! is_array($binds))
		{
			$binds = array($binds);
		}
		
		foreach ($binds as $val)
		{
			switch (gettype($val))
			{
				case 'string'	:	$val = "'".$this->escape_str($val)."'";
					break;
				case 'boolean'	:	$val = ($val === FALSE) ? 0 : 1;
					break;
				default			:	$val = ($val === NULL) ? 'NULL' : $val;
					break;
			}
		
			$sql = preg_replace("#".preg_quote($this->bind_marker)."#", $val, $sql, 1);
		}
		
		return $sql;
	}

	/*
	|============================================
	| MySQL Escape String
	|============================================
	*/
    function escape_str($str)    
    {        
		return mysql_real_escape_string(stripslashes($str));
    }

	/*
	|============================================
	| "Smart" MySQL Escape String
	|============================================
	|
	| Escapes/quotes string data types.
	| Sets boolean and null types
	|
	*/
	function smart_escape_str($str)
	{
		$str = stripslashes($str);
	
		switch (gettype($str))
		{
			case 'string'	:	$str = "'".mysql_real_escape_string($str)."'";
				break;
			case 'boolean'	:	$str = ($str === FALSE) ? 0 : 1;
				break;
			default			:	$str = ($str === NULL) ? 'NULL' : $str;
				break;
		}

		return $str;
	}

	/*
	|============================================
	| Set Debug Value
	|============================================
	*/
	function set_debug($val)
	{
		$this->debug = ($val === TRUE) ? TRUE : FALSE;
	}

	/*
	|============================================
	| Set Persistence
	|============================================
	*/
	function set_persistence($val)
	{
		$this->persistent = ($val === TRUE) ? TRUE : FALSE;
	}

	/*
	|============================================
	| Enable/Disable "delete" hack 
	|============================================
	*/
	function set_delete_hack($val)
	{
		$this->delete_hack = ($val === TRUE) ? TRUE : FALSE;
	}

	/*
	|============================================
	| Close DB Connection
	|============================================
	*/
    function close()
    {
        if ($this->conn_id !== FALSE)
            mysql_close($this->conn_id);
            
		$this->conn_id = FALSE;
    }         

	/*
	|============================================
	| Fetch the Query Elapsed Time
	|============================================
	*/
	function elapsed_time($decimals = 6)
	{
		return number_format($this->benchmark, $decimals);
	}

	/*
	|============================================
	| Total Number of Queries
	|============================================
	*/
	function total_queries()
	{
		return $this->query_count;
	}

	/*
	|============================================
	| Affected Rows
	|============================================
	*/
	function affected_rows()
	{
		return @mysql_affected_rows($this->conn_id);
	}

	/*
	|============================================
	| Insert ID
	|============================================
	*/
	function insert_id()
	{
		return @mysql_insert_id($this->conn_id);
	}

	/*
	|============================================
	| Fetch the Error Message
	|============================================
	*/
	function error_message()
	{
		return @mysql_error($this->conn_id);
	}

	/*
	|============================================
	| Fetch the Error Number
	|============================================
	*/
	function error_number()
	{
		return @mysql_errno($this->conn_id);
	}

	/*
	|============================================
	| MySQL Version Number
	|============================================
	*/
	function version()
	{
		$query = $this->query("SELECT version() AS ver");
		$row = $query->row();
		return $row->ver;
	}

	/*
	|============================================
	| Determine if a particular table exists
	|============================================
	*/
	function table_exists($table_name)
	{		
		return ( ! in_array($table_name, $this->fetch_tables())) ? FALSE : TRUE;
	}

	/*
	|============================================
	| Fetch MySQL Table Names
	|============================================
	*/
	function fetch_tables()
	{      
		if ( ! $this->conn_id)    
		{
			$this->connect();
		}
			
		$retval = array();

		if (phpversion() < 5)
		{		
			$tables = mysql_list_tables($this->database);		

			for ($i = 0; $i < mysql_numrows($tables); $i++) 
			{
				$row = mysql_tablename($tables, $i);
				$retval[] = $row;			
			}
		}
		else
		{
			$query = $this->query("SHOW TABLES FROM `".$this->database."`");
			
			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$retval[] = array_shift($row);
				}
			}
		}
	
		return $retval;
	}
	
	/*
	|============================================
	| Fetch MySQL Fields - Returns all fields as objects
	|============================================
	*/
    function fields($table = '')
    {
    	if ($table == '')
    	{
			if ($this->debug)
			{
				return $this->show_error('db_field_param_missing');
			}
			return FALSE;			
    	}
    	
    	if ( ! is_resource($this->result_id))
    	{
    		$query = $this->query("SELECT * FROM {$table} LIMIT 1");
    	}
    	
    	return $query->fields();
    }

	/*
	|============================================
	| Fetch MySQL Field Names
	|============================================
	|
	| Same as above only simply returns field names
	|
	*/
    function field_names($table = '')
    {
    	if ($table == '')
    	{
			if ($this->debug)
			{
				return $this->show_error('db_field_param_missing');
			}
			return FALSE;			
    	}
    	
    	if ( ! is_resource($this->result_id))
    	{
    		$query = $this->query("SELECT * FROM {$table} LIMIT 1");
    	}
    	
    	return $query->field_names();
    }

	/*
	|============================================
	| Write an Insert String
	|============================================
	*/
	function insert_string($table, $data, $addslashes = FALSE)
	{
		$fields = '';      
		$values = '';
		
		if (strpos($table, '.'))
		{
			$x = explode('.', $table, 3);
			$table = $x['0'].'`.`'.$x['1'];
		}
		
		foreach($data as $key => $val) 
		{
			$fields .= '`' . $key . '`, ';
			$val = ($addslashes === TRUE) ? addslashes($val) : $val;
			$values .= $this->smart_escape_str($val).', ';
		}
		
		$fields = preg_replace( "/, $/" , "" , $fields);
		$values = preg_replace( "/, $/" , "" , $values);
		
		return 'INSERT INTO `'.$table.'` ('.$fields.') VALUES ('.$values.')';
	}    

	/*
	|============================================
	| Write an Update String
	|============================================
	*/
	function update_string($table, $data, $where)
	{
		if ($where == '')
			return false;
		
		$str  = '';
		$dest = '';
		
		if (stristr($table, '.'))
		{
			$x = explode('.', $table, 3);
			$table = $x['0'].'`.`'.$x['1'];
		}
		
		foreach($data as $key => $val) 
		{
			$str .= '`'.$key."` = ".$this->smart_escape_str($val).", ";
		}
		
		$str = preg_replace( "/, $/" , "" , $str);
		
		if (is_array($where))
		{
			foreach ($where as $key => $val)
			{
				$dest .= $key." = ".$this->smart_escape_str($val)." AND ";
			}
			
			$dest = preg_replace( "/AND $/" , "" , $dest);
		}
		else
			$dest = $where;
		
		return 'UPDATE `'.$table.'` SET '.$str.' WHERE '.$dest;        
	}    
	
	/*
	|============================================
	| Error Message
	|============================================
	*/
    function show_error($error = '', $swap = '') 
    {
		$LANG = new _Language();
		$LANG->load('db');

		$heading = 'MySQL Error';
		$message = '<p>'.implode('</p><p>', ( ! is_array($error)) ? array(str_replace('%s', $swap, $LANG->line($error))) : $error).'</p>';	

		ob_start();
		?><html><head><title>MySQL Error</title>
		<style type="text/css">
			body { 
			background-color:	#fff; 
			margin:				40px; 
			font-family:		Lucida Grande, Verdana, Sans-serif;
			font-size:			12px;
			color:				#000;
			}
			#content  {
			border:				#999 1px solid;
			background-color:	#fff;
			padding:			20px 20px 12px 20px;
			}
			h1 {
			font-weight:		normal;
			font-size:			14px;
			color:				#990000;
			margin: 			0 0 4px 0;
			}
		</style>
		</head>
		<body>
			<div id="content">
				<h1><?php echo $heading; ?></h1>
				<?php echo $message; ?>
			</div>
		</body></html><?php
		$buffer = ob_get_contents();					
		ob_end_clean(); 
		exit($buffer);
    }  
}



/*
|============================================
| DB Result Class
|============================================
*/
class DB_result {

	var $conn_id;
	var $result_id;
	var $result_array  = array();
	var $result_object = array();
	var $current_row = 0;


	function DB_result($conn_id, $result_id)
	{
		$this->conn_id = $conn_id;
		$this->result_id = $result_id;		
	}

	/*
	|============================================
	| Number of rows in the result set
	|============================================
	*/
	function num_rows()
	{
		return @mysql_num_rows($this->result_id);
	}

	/*
	|============================================
	| Number of fields in the result set
	|============================================
	*/
	function num_fields()
	{
		return @mysql_num_fields($this->result_id);
	}

	/*
	|============================================
	| Affected Rows
	|============================================
	*/
	function affected_rows()
	{
		return @mysql_affected_rows($this->conn_id);
	}

	/*
	|============================================
	| Insert ID
	|============================================
	*/
	function insert_id()
	{
		return @mysql_insert_id($this->conn_id);
	}
	
	/*
	|============================================
	| MySQL Result - Returns an Object
	|============================================
	*/
	function result()
	{
		if (count($this->result_object) > 0)
		{
			return $this->result_object;
		}

		while ($row = mysql_fetch_object($this->result_id))
		{
			$this->result_object[] = $row;
		}
		
		return $this->result_object;
	}
	
	/*
	|============================================
	| MySQL Result - Returns an Array
	|============================================
	*/
	function result_array()
	{
		if (count($this->result_array) > 0)
		{
			return $this->result_array;
		}
			
		while ($row = mysql_fetch_assoc($this->result_id))
		{
			$this->result_array[] = $row;
		}
		
		return $this->result_array;
	}
	
	/*
	|============================================
	| MySQL Row - Returns a single row
	|============================================
	*/
	function row($n = 0)
	{
		$result = $this->result();
	
		if ($n != $this->current_row AND isset($result[$n]))
		{
			$this->current_row = $n;
		}
	
		return $result[$this->current_row];
	}

	/*
	|============================================
	| MySQL Next Row - Returns the "next" row
	|============================================
	*/
	function next_row()
	{
		$result = $this->result();
		if (isset($result[$this->current_row + 1]))
		{
			++$this->current_row;
		}
				
		return $result[$this->current_row];
	}

	/*
	|============================================
	| MySQL Previous Row - Returns the "prev" row
	|============================================
	*/
	function previous_row()
	{
		$result = $this->result();
		if (isset($result[$this->current_row - 1]))
		{
			--$this->current_row;
		}
		return $result[$this->current_row];
	}

	/*
	|============================================
	| MySQL First Row - Returns the first row
	|============================================
	*/
	function first_row()
	{
		$result = $this->result();
		return $result[0];
	}

	/*
	|============================================
	| MySQL Last Row - Returns the "last" row
	|============================================
	*/
	function last_row()
	{
		$result = $this->result();
		return $result[count($result) -1];
	}

	/*
	|============================================
	| MySQL Fields - Returns an array of field names as objects
	|============================================
	*/
	function fields()
	{
		$retval = array();
		while ($field = mysql_fetch_field($this->result_id))
		{		
			$retval[] = $field;
		}
		
		return $retval;
	}
	
	/*
	|============================================
	| MySQL Field names
	|============================================
	|
	| Similar to the function above, only this one
	| returns the field names as an array
	|
	*/
	function field_names()
	{	
		$retval = array();
		while ($field = mysql_fetch_field($this->result_id))
		{
			$retval[] = $field->name;
		}
		
		return $retval;
	}
}
?>