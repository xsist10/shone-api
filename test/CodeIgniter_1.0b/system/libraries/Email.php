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
| File: libraries/Email.php
|----------------------------------------------------------
| Purpose: Email Sending Class
|==========================================================
*/


class _Email {

	var	$useragent		= "Code Igniter";
	var	$mailpath		= "/usr/sbin/sendmail";	// Sendmail path
	var	$protocol		= "mail";	// mail/sendmail/smtp
	var	$smtp_host		= "";		// SMTP Server.  Example: mail.earthlink.net
	var	$smtp_user		= "";		// SMTP Username
	var	$smtp_pass		= "";		// SMTP Password
	var	$smtp_port		= "25";		// SMTP Port
	var	$smtp_timeout	= 5;		// SMTP Timeout in seconds
	var	$wordwrap		= TRUE;		// true/false  Turns word-wrap on/off
	var	$wrapchars		= "76";		// Number of characters to wrap at.
	var	$mailtype		= "text";	// text/html  Defines email formatting
	var	$charset		= "utf-8";	// Default char set: iso-8859-1 or us-ascii
	var	$multipart		= "mixed";	// "mixed" (in the body) or "related" (separate)
	var $alt_message	= '';		// Alternative message for HTML emails
	var	$validate		= FALSE;	// true/false.  Enables email validation
	var	$priority		= "3";		// Default priority (1 - 5)
	var	$newline		= "\n";		// Default newline. "\r\n" or "\n" (Use "\r\n" to comply with RFC 822)
	var	$bcc_batch_mode	= FALSE;	// true/false  Turns on/off Bcc batch feature
	var	$bcc_batch_size	= 200;		// If bcc_batch_mode = true, sets max number of Bccs in each batch
	
	//	Private variables.

	var	$subject		= "";
	var	$body			= "";
	var	$finalbody		= "";
	var	$alt_boundary	= "";
	var	$atc_boundary	= "";
	var	$header_str		= "";
	var	$smtp_connect	= "";
	var	$encoding		= "8bit";
	var $safe_mode		= FALSE;
	var $IP				= FALSE;
	var	$smtp_auth		= FALSE;
	var $replyto_flag	= FALSE;
	var	$debug_msg		= array();
	var	$recipients		= array();
	var	$cc_array		= array();
	var	$bcc_array		= array();
	var	$headers		= array();
	var	$attach_name	= array();
	var	$attach_type	= array();
	var	$attach_disp	= array();
	var	$protocols		= array('mail', 'sendmail', 'smtp');
	var	$base_charsets	= array('iso-8859-1', 'us-ascii');
	var	$bit_depths		= array('7bit', '8bit');
	var	$priorities		= array('1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)');	


	/*
	|=====================================================
	| Constructor - Sets Email Preferences
	|=====================================================
	|
	| The constructor can be passed an array of config values
	|
	*/	
	function _Email($config = array())
	{		
		if (count($config) > 0)
		{
			$this->initialize($config);
		}	

		log_message('debug', "Email Class Initialized");
	}	
	
	/*
	|=====================================================
	| Initialize preferences
	|=====================================================
	|
	*/	
	function initialize($config = array())
	{
		$this->clear();
		foreach ($config as $key => $val)
		{
			if (isset($this->$key))
			{
				$method = 'set_'.$key;
				
				if (method_exists($this, $method))
				{
					$this->$method($val);
				}
				else
				{
					$this->$key = $val;
				}			
			}
		}
        $this->smtp_auth = ($this->smtp_user == '' AND $this->smtp_pass == '') ? FALSE : TRUE;			
		$this->safe_mode = (@ini_get("safe_mode") == 0) ? FALSE : TRUE;
	}

	/*
	|=====================================================
	| Initialize the Email Data
	|=====================================================
	|
	*/	
	function clear()
	{
		$this->subject		= "";
		$this->body			= "";
		$this->finalbody	= "";
		$this->header_str	= "";
		$this->replyto_flag = FALSE;
		$this->recipients	= array();
		$this->headers		= array();
		$this->debug_msg	= array();
		
		$this->add_header('User-Agent', $this->useragent);				
		$this->add_header('Date', $this->set_date());
	}

	/*
	|=====================================================
	| Set FROM
	|=====================================================
	|
	*/	
	function from($from, $name = '')
	{
		if (preg_match( '/\<(.*)\>/', $from, $match))
			$from = $match['1'];

		if ($this->validate)
			$this->validate_email($this->str_to_array($from));
			
		if ($name != '' && substr($name, 0, 1) != '"')
		{
			$name = '"'.$name.'"';
		}
	
		$this->add_header('From', $name.' <'.$from.'>');
		$this->add_header('Return-Path', '<'.$from.'>');
	}

	/*
	|=====================================================
	| Set Reply-to
	|=====================================================
	|
	*/	
	function reply_to($replyto, $name = '')
	{
		if (preg_match( '/\<(.*)\>/', $replyto, $match))
			$replyto = $match['1'];

		if ($this->validate)
			$this->validate_email($this->str_to_array($replyto));	

		if ($name == '')
		{
			$name = $replyto;
		}

		if (substr($name, 0, 1) != '"')
		{
			$name = '"'.$name.'"';
		}

		$this->add_header('Reply-To', $name.' <'.$replyto.'>');
		$this->replyto_flag = TRUE;
	}

	/*
	|=====================================================
	|  Set Recipients
	|=====================================================
	|
	*/	
	function to($to)
	{
		$to = $this->str_to_array($to);
		$to = $this->clean_email($to);
	
		if ($this->validate)
			$this->validate_email($to);
			
		if ($this->get_protocol() != 'mail')
			$this->add_header('To', implode(", ", $to));

		switch ($this->get_protocol())
		{
			case 'smtp'		: $this->recipients = $to;
			break;
			case 'sendmail'	: $this->recipients = implode(", ", $to);
			break;
			case 'mail'		: $this->recipients = implode(", ", $to);
			break;
		}	
	}

	/*
	|=====================================================
	| Set CC
	|=====================================================
	|
	*/	
	function cc($cc)
	{	
		$cc = $this->str_to_array($cc);
		$cc = $this->clean_email($cc);

		if ($this->validate)
			$this->validate_email($cc);

		$this->add_header('Cc', implode(", ", $cc));
		
		if ($this->get_protocol() == "smtp")
			$this->cc_array = $cc;
	}
	
	/*
	|=====================================================
	| Set BCC
	|=====================================================
	|
	*/	
	function bcc($bcc, $limit = '')
	{
		if ($limit != '' && is_numeric($limit))
		{
			$this->bcc_batch_mode = true;
			$this->bcc_batch_size = $limit;
		}

		$bcc = $this->str_to_array($bcc);
		$bcc = $this->clean_email($bcc);
		
		if ($this->validate)
			$this->validate_email($bcc);

		if (($this->get_protocol() == "smtp") || ($this->bcc_batch_mode && count($bcc) > $this->bcc_batch_size))
			$this->bcc_array = $bcc;
		else
			$this->add_header('Bcc', implode(", ", $bcc));
	}

	/*
	|=====================================================
	| Set Email Subject
	|=====================================================
	|
	*/	
	function subject($subject)
	{
		$subject = preg_replace("/(\r\n)|(\r)|(\n)/", "", $subject);
		$subject = preg_replace("/(\t)/", " ", $subject);
		
		$this->add_header('Subject', trim($subject));		
	}

	/*
	|=====================================================
	| Set Body
	|=====================================================
	|
	*/	
	function message($body)
	{
		$body = rtrim(str_replace("\r", "", $body));
	
		if ($this->wordwrap === TRUE  AND  $this->mailtype != 'html')
			$this->body = $this->word_wrap($body);
		else
			$this->body = $body;	
			
		$this->body = stripslashes($this->body);
	}	

	/*
	|=====================================================
	| Add a Header Item
	|=====================================================
	|
	*/	
	function add_header($header, $value)
	{
		$this->headers[$header] = $value;
	}

	/*
	|=====================================================
	| Convert a String to an Array
	|=====================================================
	|
	*/	
	function str_to_array($email)
	{
		if ( ! is_array($email))
		{	
			if (ereg(',$', $email))
				$email = substr($email, 0, -1);
			
			if (ereg('^,', $email))
				$email = substr($email, 1);	
					
			if (ereg(',', $email))
			{					
				$x = explode(',', $email);
				$email = array();
				
				for ($i = 0; $i < count($x); $i ++)
					$email[] = trim($x[$i]);
			}
			else
			{				
				$email = trim($email);
				settype($email, "array");
			}
		}
		return $email;
	}

	/*
	|=====================================================
	| Set Multipart Value
	|=====================================================
	|
	*/	
	function set_multipart($type = 'mixed')
	{
		$this->multipart = ($type == 'related') ? 'mixed' : 'related';
	}

	/*
	|=====================================================
	| Set Multipart Value
	|=====================================================
	|
	*/	
	function set_alt_message($str = '')
	{
		$this->alt_message = ($str == '') ? '' : $str;
	}

	/*
	|=====================================================
	| Set Mailtype
	|=====================================================
	|
	*/	
	function set_mailtype($type = 'text')
	{
		$this->mailtype = ($type == 'html') ? 'html' : 'text';
	}

	/*
	|=====================================================
	| Set Wordwrap
	|=====================================================
	|
	*/	
	function set_wordwrap($wordwrap = TRUE)
	{
		$this->wordwrap = ($wordwrap === FALSE) ? FALSE : TRUE;
	}

	/*
	|=====================================================
	| Set Protocal
	|=====================================================
	|
	*/	
	function set_protocol($protocol = 'mail')
	{ 
		$this->protocol = ( ! in_array($protocol, $this->protocols)) ? 'mail' : strtolower($protocol);
	}
	
	/*
	|=====================================================
	| Set Priority
	|=====================================================
	|
	*/	
	function set_priority($n = 3)
	{
		if ( ! is_numeric($n))
		{
			$this->priority = 3;
			return;
		}
	
		if ($n < 1 OR $n > 5)
		{
			$this->priority = 3;
			return;
		}
	
		$this->priority = $n;
	}
	
	/*
	|=====================================================
	| Set Newline Character
	|=====================================================
	|
	*/	
	function set_newline($newline = "\n")
	{
		if ($newline != "\n" OR $newline != "\r\n" OR $newline != "\r")
		{
			$this->newline	= "\n";	
			return;
		}
	
		$this->newline	= $newline;	
	}
	
	/*
	|=====================================================
	| Set the Message ID
	|=====================================================
	|
	*/	
	function set_message_id()
	{
		$from = $this->headers['Return-Path'];
		$from = str_replace(">", "", $from);
		$from = str_replace("<", "", $from);
	
        return  "<".uniqid('').strstr($from, '@').">";	        
	}

	/*
	|=====================================================
	| Set Message Boundry
	|=====================================================
	|
	*/	
	function set_boundaries()
	{
		$this->alt_boundary = "B_ALT_".uniqid(''); // mulipart/alternative
		$this->atc_boundary = "B_ATC_".uniqid(''); // attachment boundary
	}
	
	/*
	|=====================================================
	| Get Mail Protocol
	|=====================================================
	|
	*/	
	function get_protocol($return = true)
	{
		$this->protocol = strtolower($this->protocol);
	
		$this->protocol = ( ! in_array($this->protocol, $this->protocols)) ? 'mail' : $this->protocol;
		
		if ($return == true) 
			return $this->protocol;
	}

	/*
	|=====================================================
	| Get Mail Encoding
	|=====================================================
	|
	*/	
	function get_encoding($return = true)
	{		
		$this->encoding = ( ! in_array($this->encoding, $this->bit_depths)) ? '7bit' : $this->encoding;
		
		if ( ! in_array($this->charset, $this->base_charsets)) 
			$this->encoding = "8bit";
			
		if ($return == true) 
			return $this->encoding;
	}

	/*
	|=====================================================
	| Get content type (text/html/attachment)
	|=====================================================
	|
	*/	
	function get_content_type()
	{	
			if	($this->mailtype == 'html' &&  count($this->attach_name) == 0)
				return 'html';
	
		elseif	($this->mailtype == 'html' &&  count($this->attach_name)  > 0)
				return 'html-attach';				
				
		elseif	($this->mailtype == 'text' &&  count($this->attach_name)  > 0)
				return 'plain-attach';
				
		  else	return 'plain';
	}

	/*
	|=====================================================
	| Set RFC 822 Date
	|=====================================================
	|
	*/	
	function set_date()
	{
		$timezone = date("Z");
		$operator = (substr($timezone, 0, 1) == '-') ? '-' : '+';
		$timezone = abs($timezone);
		$timezone = ($timezone/3600) * 100 + ($timezone % 3600) /60;
		
		return sprintf("%s %s%04d", date("D, j M Y H:i:s"), $operator, $timezone);
	}

	/*
	|=====================================================
	| Mime message
	|=====================================================
	|
	*/	
	function mime_message()
	{
		return "This is a multi-part message in MIME format.".$this->newline."Your email application may not support this format.";
	}

	/*
	|=====================================================
	| Validate Email Address
	|=====================================================
	|
	*/	
	function validate_email($email)
	{	
		if ( ! is_array($email))
		{
			$this->set_message('email_must_be_array');		
			return FALSE;
		}

		foreach ($email as $val)
		{
			if ( ! $this->valid_email($val)) 
			{
				$this->set_message('email_invalid_address', $val);				
				return FALSE;
			}
		}
	}	

	/*
	|=====================================================
	| Email Validation
	|=====================================================
	|
	*/	
	function valid_email($address)
	{
		if ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address))
			return FALSE;
		else 
			return TRUE;
	}

	/*
	|=====================================================
	| Clean Extended Email Address: Joe Smith <joe@smith.com>
	|=====================================================
	|
	*/	
	function clean_email($email)
	{
		if ( ! is_array($email))
		{
			if (preg_match('/\<(.*)\>/', $email, $match))
           		return $match['1'];
           	else
           		return $email;
		}
			
		$clean_email = array();

		for ($i=0; $i < count($email); $i++) 
		{
			if (preg_match( '/\<(.*)\>/', $email[$i], $match))
           		$clean_email[] = $match['1'];
           	else
           		$clean_email[] = $email[$i];
		}
		
		return $clean_email;
	}
	
	/*
	|=====================================================
	| Build alternative plain text message
	|=====================================================
	|
	| This function provides the raw message for use
	| in plain-text headers of HTML-formatted emails.
	| If the user hasn't specified his own alternative message  
	| it creates one by stripping the HTML
	|	
	*/	
	function get_alt_message()
	{
		if (eregi( '\<body(.*)\</body\>', $this->body, $match))
		{
			$body = $match['1'];
			$body = substr($body, strpos($body, ">") + 1);
		}
		else
		{
			$body = $this->body;
		}
		
		$body = trim(strip_tags($body));
		$body = preg_replace( '#<!--(.*)--\>#', "", $body);
		$body = str_replace("\t", "", $body);
		
		for ($i = 20; $i >= 3; $i--)
		{
			$n = "";
			
			for ($x = 1; $x <= $i; $x ++)
				 $n .= "\n";
		
			$body = str_replace($n, "\n\n", $body);	
		}

		return $this->word_wrap($body, '76');
	}
	
	/*
	|=====================================================
	| Word Wrap
	|=====================================================
	|
	*/	
	function word_wrap($str, $chars = '')
	{	
		if ($chars == '')
			$chars = ($this->wrapchars == "") ? "76" : $this->wrapchars;
		
		$lines = split("\n", $str);
		
		$output = "";

		while (list(, $thisline) = each($lines)) 
		{
			if (strlen($thisline) > $chars)
			{
				$line = "";
				
				$words = split(" ", $thisline);
				
				while(list(, $thisword) = each($words)) 
				{
					while((strlen($thisword)) > $chars) 
					{
						if (stristr($thisword, '{unwrap}') !== FALSE OR stristr($thisword, '{/unwrap}') !== FALSE)
						{
							break;
						}
					
						$cur_pos = 0;
						
						for($i=0; $i < $chars - 1; $i++)
						{
							$output .= $thisword[$i];
							$cur_pos++;
						}
						
						$output .= "\n";
						
						$thisword = substr($thisword, $cur_pos, (strlen($thisword) - $cur_pos));
					}
					
					if ((strlen($line) + strlen($thisword)) > $chars) 
					{
						$output .= $line."\n";
						
						$line = $thisword." ";
					} 
					else 
					{
						$line .= $thisword." ";
					}
				}
	
				$output .= $line."\n";
			} 
			else 
			{
				$output .= $thisline."\n";
			}
		}

		return $output;	
	}

	/*
	|=====================================================
	| Assign file attachments
	|=====================================================
	|
	*/		
	function attach($filename, $disposition = 'attachment')
	{			
		$this->attach_name[] = $filename;
		$this->attach_type[] = $this->mime_types(next(explode('.', basename($filename))));
		$this->attach_disp[] = $disposition; // Can also be 'inline'  Not sure if it matters 
	}

	/*
	|=====================================================
	| Build final headers
	|=====================================================
	|
	*/	
	function build_headers()
	{
		$this->add_header('X-Sender', $this->clean_email($this->headers['From']));
		$this->add_header('X-Mailer', $this->useragent);		
		$this->add_header('X-Priority', $this->priorities[$this->priority - 1]);
		$this->add_header('Message-ID', $this->set_message_id());		
		$this->add_header('Mime-Version', '1.0');
	}

	/*
	|=====================================================
	| Write Headers as a string
	|=====================================================
	|
	*/		
	function write_header_string()
	{
		if ($this->protocol == 'mail')
		{		
			$this->subject = $this->headers['Subject'];
			unset($this->headers['Subject']);
		}	

		reset($this->headers);
		$this->header_str = "";
				
		foreach($this->headers as $key => $val) 
		{
			$val = trim($val);
		
			if ($val != "")
			{
				$this->header_str .= $key.": ".$val.$this->newline;
			}
		}
		
		if ($this->get_protocol() == 'mail')
			$this->header_str = substr($this->header_str, 0, -1);				
	}

	/*
	|=====================================================
	| Build Final Body and attachments
	|=====================================================
	|
	*/	
	function build_finalbody()
	{
		$this->set_boundaries();
		$this->write_header_string();
		
		$hdr = ($this->get_protocol() == 'mail') ? $this->newline : '';
			
		switch ($this->get_content_type())
		{
			case 'plain' :
							
				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: " . $this->get_encoding();
				
				if ($this->get_protocol() == 'mail')
				{
					$this->header_str .= $hdr;
					$this->finalbody = $this->body;
					
					return;
				}
				
				$hdr .= $this->newline . $this->newline . $this->body;
				
				$this->finalbody = $hdr;
						
				return;
			
			break;
			case 'html' :
							
				$hdr .= "Content-Type: multipart/alternative; boundary=\"" . $this->alt_boundary . "\"" . $this->newline;
				$hdr .= $this->mime_message() . $this->newline . $this->newline;
				$hdr .= "--" . $this->alt_boundary . $this->newline;
				
				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: " . $this->get_encoding() . $this->newline . $this->newline;
				$hdr .= $this->get_alt_message() . $this->newline . $this->newline . "--" . $this->alt_boundary . $this->newline;
			
				$hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: quoted/printable";
				
				if ($this->get_protocol() == 'mail')
				{
					$this->header_str .= $hdr;
					$this->finalbody = $this->body . $this->newline . $this->newline . "--" . $this->alt_boundary . "--";
					
					return;
				}
				
				$hdr .= $this->newline . $this->newline;
				$hdr .= $this->body . $this->newline . $this->newline . "--" . $this->alt_boundary . "--";

				$this->finalbody = $hdr;
				
				return;
		
			break;
			case 'plain-attach' :
	
				$hdr .= "Content-Type: multipart/".$this->multipart."; boundary=\"" . $this->atc_boundary."\"" . $this->newline;
				$hdr .= $this->mime_message() . $this->newline . $this->newline;
				$hdr .= "--" . $this->atc_boundary . $this->newline;
	
				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: " . $this->get_encoding();
				
				if ($this->get_protocol() == 'mail')
				{
					$this->header_str .= $hdr;		
					
					$body  = $this->body . $this->newline . $this->newline;
				}
				
				$hdr .= $this->newline . $this->newline;
				$hdr .= $this->body . $this->newline . $this->newline;

			break;
			case 'html-attach' :
			
				$hdr .= "Content-Type: multipart/".$this->multipart."; boundary=\"" . $this->atc_boundary."\"" . $this->newline;
				$hdr .= $this->mime_message() . $this->newline . $this->newline;
				$hdr .= "--" . $this->atc_boundary . $this->newline;
	
				$hdr .= "Content-Type: multipart/alternative; boundary=\"" . $this->alt_boundary . "\"" . $this->newline .$this->newline;
				$hdr .= "--" . $this->alt_boundary . $this->newline;
				
				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: " . $this->get_encoding() . $this->newline . $this->newline;
				$hdr .= $this->get_alt_message() . $this->newline . $this->newline . "--" . $this->alt_boundary . $this->newline;
	
				$hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: quoted/printable";
				
				if ($this->get_protocol() == 'mail')
				{
					$this->header_str .= $hdr;	
					
					$body  = $this->body . $this->newline . $this->newline; 
					$body .= "--" . $this->alt_boundary . "--" . $this->newline . $this->newline;				
				}
				
				$hdr .= $this->newline . $this->newline;
				$hdr .= $this->body . $this->newline . $this->newline;
				$hdr .= "--" . $this->alt_boundary . "--" . $this->newline . $this->newline;

			break;
		}

		$attachment = array();

		$z = 0;
		
		for ($i=0; $i < count($this->attach_name); $i++)
		{
			$filename = $this->attach_name[$i];
			$basename = basename($filename);
			$ctype = $this->attach_type[$i];
						
			if ( ! file_exists($filename))
			{
				$this->set_message('email_attachment_missing', $filename); 
				return FALSE;
			}			

			$h  = "--".$this->atc_boundary.$this->newline;
			$h .= "Content-type: ".$ctype."; ";
			$h .= "name=\"".$basename."\"".$this->newline;
			$h .= "Content-Disposition: ".$this->attach_disp[$i].";".$this->newline;
			$h .= "Content-Transfer-Encoding: base64".$this->newline;

			$attachment[$z++] = $h;
			$file = filesize($filename) +1;
			
			if ( ! $fp = fopen($filename, 'r'))
			{
				$this->set_message('email_attachment_unredable', $filename); 
				return FALSE;
			}
			
			$attachment[$z++] = chunk_split(base64_encode(fread($fp, $file)));				
			fclose($fp);
		}

		if ($this->get_protocol() == 'mail')
		{
			$this->finalbody = $body . implode($this->newline, $attachment).$this->newline."--".$this->atc_boundary."--";	
			
			return;
		}
		
		$this->finalbody = $hdr.implode($this->newline, $attachment).$this->newline."--".$this->atc_boundary."--";	
		
		return;	
	}

	/*
	|=====================================================
	| Send Email
	|=====================================================
	|
	*/	
	function send()
	{			
		if ($this->replyto_flag == FALSE)
		{
			$this->reply_to($this->headers['From']);
		}
	
		if (( ! isset($this->recipients) AND ! isset($this->headers['To']))  AND
			( ! isset($this->bcc_array) AND ! isset($this->headers['Bcc'])) AND
			( ! isset($this->headers['Cc'])))
		{
			$this->set_message('email_no_recipients');					
			return FALSE;
		}

		$this->build_headers();
		
		if ($this->bcc_batch_mode  AND  count($this->bcc_array) > 0)
		{		
			if (count($this->bcc_array) > $this->bcc_batch_size)
				return $this->batch_bcc_send();
		}
		
		$this->build_finalbody();
						
		if ( ! $this->mail_spool())
			return FALSE;
		else
			return TRUE;
	}

	/*
	|=====================================================
	| Batch Bcc Send.  Sends groups of Bccs in batches
	|=====================================================
	|
	*/	
	function batch_bcc_send()
	{
		$float = $this->bcc_batch_size -1;
		
		$flag = 0;
		$set = "";
		
		$chunk = array();		
		
		for ($i = 0; $i < count($this->bcc_array); $i++)
		{
			if (isset($this->bcc_array[$i]))
				$set .= ", ".$this->bcc_array[$i];
		
			if ($i == $float)
			{	
				$chunk[] = substr($set, 1);
				$float = $float + $this->bcc_batch_size;
				$set = "";
			}
			
			if ($i == count($this->bcc_array)-1)
					$chunk[] = substr($set, 1);	
		}

		for ($i = 0; $i < count($chunk); $i++)
		{
			unset($this->headers['Bcc']);
			unset($bcc);

			$bcc = $this->str_to_array($chunk[$i]);
			$bcc = $this->clean_email($bcc);
	
			if ($this->protocol != 'smtp')
				$this->add_header('Bcc', implode(", ", $bcc));
			else
				$this->bcc_array = $bcc;
			
			$this->build_finalbody();
			$this->mail_spool();		
		}
	}
	/*
	|=====================================================
	| Unwrap special elements
	|=====================================================
	|
	*/	
    function unwrap_specials()
    {
        $this->finalbody = preg_replace_callback("/\{unwrap\}(.*?)\{\/unwrap\}/si", array($this, 'remove_nl_callback'), $this->finalbody);
    }

	/*
	|=====================================================
	| Strip line-breaks via callback
	|=====================================================
	|
	*/	
    function remove_nl_callback($matches)
    {
        return preg_replace("/(\r\n)|(\r)|(\n)/", "", $matches['1']);    
    }

	/*
	|=====================================================
	| Spool mail to the mail server
	|=====================================================
	|
	*/	
	function mail_spool()
	{
	    $this->unwrap_specials();

		switch ($this->get_protocol())
		{
			case 'mail'	:
			
					if ( ! $this->send_with_mail())
					{
						$this->set_message('email_send_failure_phpmail');							
						return FALSE;
					}
			break;
			case 'sendmail'	: 
								
					if ( ! $this->send_with_sendmail())
					{
						$this->set_message('email_send_failure_sendmail');							
						return FALSE;
					}
			break;
			case 'smtp'	: 
								
					if ( ! $this->send_with_smtp())
					{
						$this->set_message('email_send_failure_smtp');							
						return FALSE;
					}
			break;

		}

		$this->set_message('email_sent', $this->get_protocol());
		return true;
	}	

	/*
	|=====================================================
	| Send using mail()
	|=====================================================
	|
	*/	
	function send_with_mail()
	{	
		if ($this->safe_mode == TRUE)
		{
			if ( ! mail($this->recipients, $this->subject, $this->finalbody, $this->header_str))
				return FALSE;
			else
				return TRUE;		
		}
		else
		{
			if ( ! mail($this->recipients, $this->subject, $this->finalbody, $this->header_str, "-f".$this->clean_email($this->headers['From'])))
				return FALSE;
			else
				return TRUE;
		}
	}

	/*
	|=====================================================
	| Send using Sendmail
	|=====================================================
	|
	*/	
	function send_with_sendmail()
	{
		$fp = @popen($this->mailpath . " -oi -f ".$this->clean_email($this->headers['From'])." -t", 'w');
		
		if ( ! is_resource($fp))
		{								
			$this->set_message('email_no_socket');				
			return FALSE;
		}
		
		fputs($fp, $this->header_str);		
		fputs($fp, $this->finalbody);
		pclose($fp) >> 8 & 0xFF;
		
		return TRUE;
	}

	/*
	|=====================================================
	| Send using SMTP
	|=====================================================
	|
	*/	
	function send_with_smtp()
	{	
	    if ($this->smtp_host == '')
	    {	
			$this->set_message('email_no_hostname');		
			return FALSE;
		}

		$this->smtp_connect();
		$this->smtp_authenticate();
		
		$this->send_command('from', $this->clean_email($this->headers['From']));

		foreach($this->recipients as $val)
			$this->send_command('to', $val);
			
		if (count($this->cc_array) > 0)
		{
			foreach($this->cc_array as $val)
			{
				if ($val != "")
				$this->send_command('to', $val);
			}
		}

		if (count($this->bcc_array) > 0)
		{
			foreach($this->bcc_array as $val)
			{
				if ($val != "")
				$this->send_command('to', $val);
			}
		}
		
		$this->send_command('data');

		$this->send_data($this->header_str . $this->finalbody);
		
		$this->send_data('.');

		$reply = $this->get_data();
		
		$this->set_message($reply);			

		if (substr($reply, 0, 3) != '250')
		{
			$this->set_message('email_smtp_error', $reply);			
			return FALSE;
		}

		$this->send_command('quit');
		return true;
	}	

	/*
	|=====================================================
	| SMTP Connect
	|=====================================================
	|
	*/	
	function smtp_connect()
	{
	
		$this->smtp_connect = fsockopen($this->smtp_host, 
										$this->smtp_port,
										$errno, 
										$errstr, 
										$this->smtp_timeout);

		if( ! is_resource($this->smtp_connect))
		{								
			$this->set_message('email_smtp_error', $errno." ".$errstr);				
			return FALSE;
		}

		$this->set_message($this->get_data());
		return $this->send_command('hello');
	}

	/*
	|=====================================================
	| Send SMTP command
	|=====================================================
	|
	*/	
	function send_command($cmd, $data = '')
	{
		switch ($cmd)
		{
			case 'hello' :
		
					if ($this->smtp_auth || $this->get_encoding() == '8bit')
						$this->send_data('EHLO '.$this->get_hostname());
					else
						$this->send_data('HELO '.$this->get_hostname());
						
						$resp = 250;
			break;
			case 'from' :
			
						$this->send_data('MAIL FROM:<'.$data.'>');

						$resp = 250;
			break;
			case 'to'	:
			
						$this->send_data('RCPT TO:<'.$data.'>');

						$resp = 250;			
			break;
			case 'data'	:
			
						$this->send_data('DATA');

						$resp = 354;			
			break;
			case 'quit'	:
		
						$this->send_data('QUIT');
						
						$resp = 221;
			break;
		}
		
		$reply = $this->get_data();	
		
		$this->debug_msg[] = "<pre>".$cmd.": ".$reply."</pre>";

		if (substr($reply, 0, 3) != $resp)
		{
			$this->set_message('email_smtp_error', $reply);				
			return FALSE;
		}
			
		if ($cmd == 'quit')
			fclose($this->smtp_connect);
	
		return true;
	}

	/*
	|=====================================================
	|  SMTP Authenticate
	|=====================================================
	|
	*/	
	function smtp_authenticate()
	{	
		if ( ! $this->smtp_auth)
			return true;
			
		if ($this->smtp_user == ""  AND  $this->smtp_pass == "")
		{
			$this->set_message('email_no_smtp_unpw');
			return FALSE;
		}

		$this->send_data('AUTH LOGIN');

		$reply = $this->get_data();			

		if (substr($reply, 0, 3) != '334')
		{
			$this->set_message('email_filed_smtp_login', $reply);			
			return FALSE;
		}

		$this->send_data(base64_encode($this->smtp_user));

		$reply = $this->get_data();			

		if (substr($reply, 0, 3) != '334')
		{
			$this->set_message('email_smtp_auth_un', $reply);			
			return FALSE;
		}

		$this->send_data(base64_encode($this->smtp_pass));

		$reply = $this->get_data();			

		if (substr($reply, 0, 3) != '235')
		{
			$this->set_message('email_smtp_auth_pw', $reply);			
			return FALSE;
		}
	
		return true;
	}

	/*
	|=====================================================
	| Send SMTP data
	|=====================================================
	|
	*/	
	function send_data($data)
	{
		if ( ! fwrite($this->smtp_connect, $data . $this->newline))
		{
			$this->set_message('email_smtp_data_failure', $data);			
			return FALSE;
		}
		else
			return true;
	}

	/*
	|=====================================================
	| Get SMTP data
	|=====================================================
	|
	*/	
	function get_data()
	{
        $data = "";
    
		while ($str = fgets($this->smtp_connect, 512)) 
		{            
			$data .= $str;
			
			if (substr($str, 3, 1) == " ")
				break; 	
    	}
    	
    	return $data;
	}

	/*
	|=====================================================
	| Get Hostname
	|=====================================================
	|
	*/		
	function get_hostname()
	{	
		return ($this->smtp_host != '') ? $this->smtp_host : $this->get_ip();		
	}

	/*
	|=====================================================
	| Get IP
	|=====================================================
	|
	*/		
	function get_ip()
	{
		if ($this->IP !== FALSE)
		{
			return $this->IP;
		}
	
		$cip = (isset($_SERVER['HTTP_CLIENT_IP']) AND $_SERVER['HTTP_CLIENT_IP'] != "") ? $_SERVER['HTTP_CLIENT_IP'] : FALSE;
		$rip = (isset($_SERVER['REMOTE_ADDR']) AND $_SERVER['REMOTE_ADDR'] != "") ? $_SERVER['REMOTE_ADDR'] : FALSE;
		$fip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND $_SERVER['HTTP_X_FORWARDED_FOR'] != "") ? $_SERVER['HTTP_X_FORWARDED_FOR'] : FALSE;
					
		if ($cip && $rip) 	$this->IP = $cip;	
		elseif ($rip)		$this->IP = $rip;
		elseif ($cip)		$this->IP = $cip;
		elseif ($fip)		$this->IP = $fip;
		
		if (strstr($this->IP, ','))
		{
			$x = explode(',', $this->IP);
			$this->IP = end($x);
		}
		
		if ( ! preg_match( "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $this->IP))
			$this->IP = '0.0.0.0';
		
		unset($cip);
		unset($rip);
		unset($fip);
		
		return $this->IP;
	}

	/*
	|=====================================================
	| Get Debugg Message
	|=====================================================
	|
	*/	
	function print_debugger()
	{		
		$msg = '';
		
		if (count($this->debug_msg) > 0)
		{
			foreach ($this->debug_msg as $val)
			{
				$msg .= $val;
			}
		}
		
		$msg .= "<pre>".$this->header_str."\n".$this->subject."\n".$this->finalbody.'</pre>';	
		return $msg;
	}	

	/*
	|=====================================================
	| Set Message
	|=====================================================
	|
	*/	
	function set_message($msg, $val = '')
	{
		call('lang', 'load', 'email');
	
		if (FALSE === ($line = call('lang', 'line', $msg)))
		{	
			$this->debug_msg[] = str_replace('%s', $val, $msg)."<br />";
		}	
		else
		{
			$this->debug_msg[] = str_replace('%s', $val, $line)."<br />";
		}	
	}

	/*
	|=====================================================
	| Mime Types
	|=====================================================
	|
	*/		
	function mime_types($ext = "")
	{
		$mimes = array(	'hqx'	=>	'application/mac-binhex40',
						'cpt'	=>	'application/mac-compactpro',
						'doc'	=>	'application/msword',
						'bin'	=>	'application/macbinary',
						'dms'	=>	'application/octet-stream',
						'lha'	=>	'application/octet-stream',
						'lzh'	=>	'application/octet-stream',
						'exe'	=>	'application/octet-stream',
						'class'	=>	'application/octet-stream',
						'psd'	=>	'application/octet-stream',
						'so'	=>	'application/octet-stream',
						'sea'	=>	'application/octet-stream',
						'dll'	=>	'application/octet-stream',
						'oda'	=>	'application/oda',
						'pdf'	=>	'application/pdf',
						'ai'	=>	'application/postscript',
						'eps'	=>	'application/postscript',
						'ps'	=>	'application/postscript',
						'smi'	=>	'application/smil',
						'smil'	=>	'application/smil',
						'mif'	=>	'application/vnd.mif',
						'xls'	=>	'application/vnd.ms-excel',
						'ppt'	=>	'application/vnd.ms-powerpoint',
						'wbxml'	=>	'application/vnd.wap.wbxml',
						'wmlc'	=>	'application/vnd.wap.wmlc',
						'dcr'	=>	'application/x-director',
						'dir'	=>	'application/x-director',
						'dxr'	=>	'application/x-director',
						'dvi'	=>	'application/x-dvi',
						'gtar'	=>	'application/x-gtar',
						'php'	=>	'application/x-httpd-php',
						'php4'	=>	'application/x-httpd-php',
						'php3'	=>	'application/x-httpd-php',
						'phtml'	=>	'application/x-httpd-php',
						'phps'	=>	'application/x-httpd-php-source',
						'js'	=>	'application/x-javascript',
						'swf'	=>	'application/x-shockwave-flash',
						'sit'	=>	'application/x-stuffit',
						'tar'	=>	'application/x-tar',
						'tgz'	=>	'application/x-tar',
						'xhtml'	=>	'application/xhtml+xml',
						'xht'	=>	'application/xhtml+xml',
						'zip'	=>	'application/zip',
						'mid'	=>	'audio/midi',
						'midi'	=>	'audio/midi',
						'mpga'	=>	'audio/mpeg',
						'mp2'	=>	'audio/mpeg',
						'mp3'	=>	'audio/mpeg',
						'aif'	=>	'audio/x-aiff',
						'aiff'	=>	'audio/x-aiff',
						'aifc'	=>	'audio/x-aiff',
						'ram'	=>	'audio/x-pn-realaudio',
						'rm'	=>	'audio/x-pn-realaudio',
						'rpm'	=>	'audio/x-pn-realaudio-plugin',
						'ra'	=>	'audio/x-realaudio',
						'rv'	=>	'video/vnd.rn-realvideo',
						'wav'	=>	'audio/x-wav',
						'bmp'	=>	'image/bmp',
						'gif'	=>	'image/gif',
						'jpeg'	=>	'image/jpeg',
						'jpg'	=>	'image/jpeg',
						'jpe'	=>	'image/jpeg',
						'png'	=>	'image/png',
						'tiff'	=>	'image/tiff',
						'tif'	=>	'image/tiff',
						'css'	=>	'text/css',
						'html'	=>	'text/html',
						'htm'	=>	'text/html',
						'shtml'	=>	'text/html',
						'txt'	=>	'text/plain',
						'text'	=>	'text/plain',
						'log'	=>	'text/plain',
						'rtx'	=>	'text/richtext',
						'rtf'	=>	'text/rtf',
						'xml'	=>	'text/xml',
						'xsl'	=>	'text/xml',
						'mpeg'	=>	'video/mpeg',
						'mpg'	=>	'video/mpeg',
						'mpe'	=>	'video/mpeg',
						'qt'	=>	'video/quicktime',
						'mov'	=>	'video/quicktime',
						'avi'	=>	'video/x-msvideo',
						'movie'	=>	'video/x-sgi-movie',
						'doc'	=>	'application/msword',
						'word'	=>	'application/msword',
						'xl'	=>	'application/excel',
						'eml'	=>	'message/rfc822'
					);

		return ( ! isset($mimes[strtolower($ext)])) ? "application/x-unknown-content-type" : $mimes[strtolower($ext)];
	}
}
?>