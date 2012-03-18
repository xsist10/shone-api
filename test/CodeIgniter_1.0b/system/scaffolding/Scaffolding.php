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
| File: Scaffolding.php
|----------------------------------------------------------
| Purpose: Provides the Scaffolding framework
|==========================================================
*/


class Scaffolding {

	var $current_table;
	var $base_url = '';

	function Scaffolding($db_table)
	{
		/*
		|----------------------------------------------
		| Globalize objects
		|----------------------------------------------
		|
		| All of the objects instantiated by the Controller class
		| (for example: $this->db, $this->config, $this->uri, etc.)
		| are only available to Controller and its direct
		| child.  The Scaffolding class and the "view" files loaded
		| by it, since they are not childrend of Controller, will
		| not have access to the objects unless we globallize
		| them.
		|
		*/
		global $CI, $BM;
		
		if ( ! in_array('db', $CI->ci_is_loaded))
		{
			$CI->initialize('database');
		}

		foreach ($CI->ci_is_loaded as $val)
		{
			$this->$val =& $CI->$val;
		}
		
		/*
		|----------------------------------------------
		| Set the current table name
		|----------------------------------------------
		|
		| This is done when initializing scaffolding:
		|
		| $this->init_scaffolding('table_name')
		|
		*/
		
		$this->current_table = $db_table;
		
		/*
		|----------------------------------------------
		| Set the path to the "view" files
		|----------------------------------------------
		|
		| We'll manually override the "view" path so that
		| the load->view function knows where to look.
		|
		*/
		$this->load->_set_view_path(BASEPATH.'scaffolding/views/');

		/*
		|----------------------------------------------
		| Set the base URL
		|----------------------------------------------
		*/
		$this->base_url = $this->config->site_url().'/'.$this->uri->segment(1).$this->uri->slash_segment(2, 'both');
		$this->base_uri = $this->uri->segment(1).$this->uri->slash_segment(2, 'leading');
		/*
		|----------------------------------------------
		| Set a few globals
		|----------------------------------------------
		*/
				
		$data = array(
						'image_url'	=> $this->config->system_url().'scaffolding/images/',
						'base_uri'  => $this->base_uri,
						'base_url'	=> $this->base_url
					);
		
		$this->load->vars($data);
		
		/*
		|----------------------------------------------
		| Load the helper files we plan to use
		|----------------------------------------------
		*/
		$this->load->helper(array('url', 'form'));

		/*
		|----------------------------------------------
		| Load the database class
		|----------------------------------------------
		|
		| Just in case the controller hasn't done so,
		| we'll load it here.
		|
		*/
		
		$CI->initialize('database');
		
		log_message('debug', 'Scaffolding Class Initialized');
	}
	
	/*
	|=====================================================
	| "Add" Page
	|=====================================================
	|
	| Shows a form representing the currently selected DB
	| so that data can be inserted
	|
	*/
	function add()
	{
		$this->db->use_table($this->current_table);
		$this->db->limit(1);
		$query = $this->db->get();
	
		$data = array(
						'title'	=> 'Add Data',
						'fields' => $query->fields(),
						'action' => $this->base_uri.'/insert'
					);
	
		$this->load->view('add', $data);
	}

	/*
	|=====================================================
	| Insert the data
	|=====================================================
	*/
	function insert()
	{
		$this->db->use_table($this->current_table);
		$this->db->set($_POST);
		
		if ($this->db->insert() === FALSE)
		{
			$this->add();
		}
		else
		{
			redirect($this->base_uri.'/view/');
		}
	}

	/*
	|=====================================================
	| "View" Page
	|=====================================================
	|
	| Shows a table containing the data in the currently
	| selected DB
	|
	*/
	function view()
	{
		/*
		|----------------------------------------------
		| Fetch the total number of DB rows
		|----------------------------------------------
		|
		*/
		$query = $this->db->query("SELECT COUNT(*) AS count FROM ".$this->current_table);
		$row = $query->row(); 
		$total_rows = $row->count;

		/*
		|----------------------------------------------
		| Select the table for use
		|----------------------------------------------
		|
		*/
		$this->db->use_table($this->current_table);

		/*
		|----------------------------------------------
		| Set the query limit/offset
		|----------------------------------------------
		|
		*/
		$per_page = 20;
		$offset = $this->uri->segment(4, 0);
		$this->db->limit($per_page);
		$this->db->offset($offset);
		
		/*
		|----------------------------------------------
		| Run the query
		|----------------------------------------------
		|
		*/		
		$query = $this->db->get();

		/*
		|----------------------------------------------
		| Now let's get the field names
		|----------------------------------------------
		|
		*/				
		$fields = $query->field_names();
		$primary = current($fields);

		/*
		|----------------------------------------------
		| Pagination!
		|----------------------------------------------
		|
		*/			
	
		global $CI;
		$CI->initialize('pagination', 
							array(
									'base_url'		 => $this->base_url.'/view',
									'total_rows'	 => $total_rows,
									'per_page'		 => $per_page,
									'full_tag_open'	 => '<p>',
									'full_tag_close' => '</p>'
									)
								);	

		$data = array(
						'title'		=> 'View Data',
						'query'		=> $query,
						'fields'	=> $fields,
						'primary'	=> $primary,
						'paginate'	=> $CI->pagination->create_links()
					);
	
		$this->load->view('view', $data);
	}

	/*
	|=====================================================
	| "Edit" Page
	|=====================================================
	|
	| Shows a form representing the currently selected DB
	| so that data can be edited
	|
	*/
	function edit()
	{
		if (FALSE === ($id = $this->uri->segment(4)))
		{
			return $this->view();
		}

		// Fetch the primary field name
		$fields = $this->db->field_names($this->current_table);				
		$primary = current($fields);

		// Run the query
		$this->db->use_table($this->current_table);
		$this->db->where($primary, $id);
		$query = $this->db->get();

		$data = array(
						'title'		=> 'Add Data',
						'fields'	=> $query->fields(),
						'query'		=> $query->row(),
						'action'	=> $this->base_uri.'/update/'.$this->uri->segment(4)
					);
	
		$this->load->view('edit', $data);
	}

	/*
	|=====================================================
	| Update
	|=====================================================
	*/
	function update()
	{	
		// Fetch the field names
		$fields = $this->db->field_names($this->current_table);				
		$primary = current($fields);

		// Now do the query
		$this->db->use_table($this->current_table);
		$this->db->set($_POST);
		$this->db->where($primary, $this->uri->segment(4));
		$this->db->update();
		
		redirect($this->base_uri.'/view/');
	}

	/*
	|=====================================================
	| Delete Confirmation
	|=====================================================
	*/
	function delete()
	{
		$data = array(
						'title'		=> 'Delete Data',
						'message'	=> 'Are you sure you want to delete entry ID '.$this->uri->segment(4).'?',
						'no'		=> anchor(array($this->base_uri, 'view'), 'No'),
						'yes'		=> anchor(array($this->base_uri, 'do_delete', $this->uri->segment(4)), 'Yes')
					);
	
		$this->load->view('delete', $data);
	}

	/*
	|=====================================================
	| Delete
	|=====================================================
	*/
	function do_delete()
	{		
		// Fetch the field names
		$fields = $this->db->field_names($this->current_table);				
		$primary = current($fields);

		// Now do the query
		$this->db->use_table($this->current_table);
		$this->db->where($primary, $this->uri->segment(4));
		$this->db->delete();

		header("Refresh:0;url=".site_url(array($this->base_uri, 'view')));
		exit;
	}

}
?>