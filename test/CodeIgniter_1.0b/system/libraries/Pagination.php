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
| File: libraries/Paginate.php
|----------------------------------------------------------
| Purpose: Pagination class
|==========================================================
*/



class _Pagination {

        var $base_url		= ''; // The page we are linking to
        var $total_rows  	= ''; // Total number of items (database results)
        var $per_page     	= 10; // Max number of items you want shown per page
        var $num_links    	=  2; // Number of "digit" links to show before/after the currently viewed page
        var $cur_page     	=  0; // The current page being viewed

        var $first_link   	= '&lsaquo; First';
        var $next_link		= '&gt;';
        var $prev_link		= '&lt;';
        var $last_link    	= 'Last &rsaquo;';

		var $uri_segment		= 3;
        var $full_tag_open		= '';
        var $full_tag_close		= '';
        var $first_tag_open		= '';
        var $first_tag_close	= '&nbsp;';
        var $last_tag_open		= '&nbsp;';
        var $last_tag_close		= '';
		var $cur_tag_open		= '&nbsp;<b>';
		var $cur_tag_close		= '</b>';
        var $next_tag_open		= '&nbsp;';
        var $next_tag_close		= '&nbsp;';
        var $prev_tag_open		= '&nbsp;';
        var $prev_tag_close		= '';
        var $num_tag_open		= '&nbsp;';
        var $num_tag_close		= '';

	/*
	|==========================================================
	| Constructor - sets default values
	|==========================================================
	|
	*/
    function _Pagination($params = array())
    {    
		if (count($params) > 0)
		{
			$this->initialize($params);		
		}
		
		log_message('debug', "Pagination Class Initialized");
    }

	/*
	|==========================================================
	| Initialize Preferences
	|==========================================================
	|
	*/
    function initialize($params = array())
    {    
		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				if (isset($this->$key))
				{
					$this->$key = $val;
				}
			}		
		}
    }
      
	/*
	|==========================================================
	| Generate the links
	|==========================================================
	|
	*/	
    function create_links()
    {  
		/*
		|------------------------------------------------
		|  Do we even need to generate links?
		|------------------------------------------------
		|
		| If our item count or per-page total is zero 
		| there is no need to continue.  Goodbye cruel world...
		|
		*/        
        if ($this->total_rows == 0 || $this->per_page == 0)
        {
           return '';
    	}
		/*
		|------------------------------------------------
		| Calculate the total number of pages
		|------------------------------------------------
		|
		*/        
        $num_pages = intval($this->total_rows / $this->per_page);
        
		/*
		|------------------------------------------------
		| Is there an odd number of pages?
		|------------------------------------------------
		|
		| Use modulus to see if our division has a remainder.
		| If so, add one to our page number.
		|
		*/
        if ($this->total_rows % $this->per_page) 
        {
            $num_pages++;
        }
 
 		/*
		|------------------------------------------------
		| Is there only one page?
		|------------------------------------------------
		|
		| Hm... nothing more to do here then. 
		|
		*/
        if ($num_pages == 1)
        {
            return '';
        }
        
		/*
		|------------------------------------------------
		| Determine the current page number
		|------------------------------------------------
		|
		| We'll round down the result in case we have
		| a decimal fraction which messes up the links.
		| Having fun yet?  Me neither...
		|
		*/
		if (call('uri', 'segment', $this->uri_segment) != 0)
		{
			$this->cur_page = call('uri', 'segment', $this->uri_segment);
		}
		
		if ( ! is_numeric($this->cur_page))
		{
			$this->cur_page = 0;
		}
		
		$uri_page_number = $this->cur_page;
		$this->cur_page = floor(($this->cur_page/$this->per_page) + 1);
   
		/*
		|------------------------------------------------
		| Calculate the start and end numbers
		|------------------------------------------------
		|
		| These determine which number to start and 
		| end the digit links with
		|
		*/
        $start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
        $end   = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;
        
		/*
		|------------------------------------------------
		| Add a trailing slash the the base URL if needed
		|------------------------------------------------
		|
		*/            
		$this->base_url = preg_replace("/(.+?)\/*$/", "\\1/",  $this->base_url);
  
  		// And here we go...
        $output = '';
 
		/*
		|------------------------------------------------
		| Render the "First" link
		|------------------------------------------------
		|
		*/
        if  ($this->cur_page > $this->num_links)
        {
            $output .= $this->first_tag_open.'<a href="'.$this->base_url.'">'.$this->first_link.'</a>'.$this->first_tag_close;
        }
 
		/*
		|------------------------------------------------
		|  Render the "previous" link
		|------------------------------------------------
		|
		*/
        if  (($this->cur_page - $this->num_links) >= 0)
        {
        	$i = $uri_page_number - $this->per_page;  
        	if ($i == 0) $i = '';
            $output .= $this->prev_tag_open.'<a href="'.$this->base_url.$i.'">'.$this->prev_link.'</a>'.$this->prev_tag_close;
        }
        
		/*
		|------------------------------------------------
		| Write the digit links
		|------------------------------------------------
		|
		*/
        for ($loop = $start -1; $loop <= $end; $loop++) 
        {
			$i = ($loop * $this->per_page) - $this->per_page;
					
			if ($i >= 0)
			{
				if ($this->cur_page == $loop)
				{
					$output .= $this->cur_tag_open.$loop.$this->cur_tag_close; // Current page
				}
				else
				{
					$n = ($i == 0) ? '' : $i;
					$output .= $this->num_tag_open.'<a href="'.$this->base_url.$n.'">'.$loop.'</a>'.$this->num_tag_close;
				}
			}
        } 

		/*
		|------------------------------------------------
		| Render the "next" link
		|------------------------------------------------
		|
		*/
        if ($this->cur_page < $num_pages)
        {  
            $output .= $this->next_tag_open.'<a href="'.$this->base_url.($this->cur_page * $this->per_page).'">'.$this->next_link.'</a>'.$this->next_tag_close;        
        }

		/*
		|------------------------------------------------
		| Render the "Last" link
		|------------------------------------------------
		|
		*/
        if (($this->cur_page + $this->num_links) < $num_pages)
        {
            $i = (($num_pages * $this->per_page) - $this->per_page);
            $output .= $this->last_tag_open.'<a href="'.$this->base_url.$i.'">'.$this->last_link.'</a>'.$this->last_tag_close;
        }
    
		/*
		|------------------------------------------------
		| Kill double slashes
		|------------------------------------------------
		|
		| Note: Sometimes we can end up with a double 
		| slash in the penultimate link so we'll kill 
		| all double shashes.
		|
		*/

		$output = preg_replace("#[^:]//+#", "/", $output);  
		
		/*
		|------------------------------------------------
		| Add the wrapper HTML if exists
		|------------------------------------------------
		|
		*/
		
		$output = $this->full_tag_open.$output.$this->full_tag_close;
		
		return $output;		
    }

}
?>