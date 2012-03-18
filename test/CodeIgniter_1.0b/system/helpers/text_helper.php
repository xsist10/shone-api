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
| File: helpers/ip_helper.php
|----------------------------------------------------------
| Purpose: IP Address Helpers
|==========================================================
*/

	
/*
|==========================================================
| Word Limiter
|==========================================================
|
| Limits a string to X number of words
|
*/
function word_limiter($str, $n = 100, $end_char = '&#8230;')
{
	if (strlen($str) < $n) 
	{
		return $str;
	}
	
	$words = explode(' ', preg_replace("/\s+/", ' ', preg_replace("/(\r\n|\r|\n)/", " ", $str)));
	
	if (count($words) <= $n)
	{
		return $str;
	}
			
	$str = '';
	for ($i = 1; $i < $n; $i++) 
	{
		$str .= $words[$i].' ';
	}

	return trim($str).$end_char; 
}

/*
|==========================================================
| Character limiter
|==========================================================
|
| Limits the string based on the character count.
| Will preserve complete words.
|
*/
function character_limiter($str, $n = 500, $end_char = '&#8230;')
{
	if (strlen($str) < $n) 
	{
		return $str;
	}
		
	$str = preg_replace("/\s+/", ' ', preg_replace("/(\r\n|\r|\n)/", " ", $str));

	if (strlen($str) <= $n)
	{
		return $str;
	}
									
	$out = "";
	foreach (explode(' ', trim($str)) as $val)
	{
		$out .= $val.' ';			
		if (strlen($out) >= $n)
		{
			return trim($out).$end_char; 
		}		
	}
}


/*
|==========================================================
| High ASCII to Entities
|==========================================================
|
| Converts Hight ascii text and MS Word special chars
| to character entities
|
*/
function ascii_to_entities($str)
{
   $count	= 1;
   $out	= '';
   $temp	= array();
	   
   for ($i = 0, $s = strlen($str); $i < $s; $i++)
   {
	   $ordinal = ord($str[$i]);
	   
	   if ($ordinal < 128)
	   {
		   $out .= $str[$i];            
	   }
	   else
	   {
		   if (count($temp) == 0)
		   {
			   $count = ($ordinal < 224) ? 2 : 3;
		   }
		   
		   $temp[] = $ordinal;
		   
		   if (count($temp) == $count)
		   {
			   $number = ($count == 3) ? (($temp['0'] % 16) * 4096) + (($temp['1'] % 64) * 64) + ($temp['2'] % 64) : (($temp['0'] % 32) * 64) + ($temp['1'] % 64);

			   $out .= '&#'.$number.';';
			   $count = 1;
			   $temp = array();
		   }   
	   }   
   }
   
   return $out;
}


/*
|==========================================================
| Converts Entities back to ASCII
|==========================================================
|
*/
function entities_to_ascii($str, $all = TRUE)
{
   if (preg_match_all('/\&#(\d+)\;/', $str, $matches))
   {
	   for ($i = 0, $s = count($matches['0']); $i < $s; $i++)
	   {				
		   $digits = $matches['1'][$i];

		   $out = '';
   
		   if ($digits < 128)
		   {
			   $out .= chr($digits);
		   
		   } 
		   elseif ($digits < 2048)
		   {
			   $out .= chr(192 + (($digits - ($digits % 64)) / 64));
			   $out .= chr(128 + ($digits % 64));
		   } 
		   else
		   {
			   $out .= chr(224 + (($digits - ($digits % 4096)) / 4096));
			   $out .= chr(128 + ((($digits % 4096) - ($digits % 64)) / 64));
			   $out .= chr(128 + ($digits % 64));
		   }
   
		   $str = str_replace($matches['0'][$i], $out, $str);				
	   }
   }
   
   if ($all)
   {
	   $str = str_replace(array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;"),
						  array("&","<",">","\"", "'", "-"),
						  $str);
   }
   
   return $str;
}

/*
|==========================================================
| Word Censoring Function
|==========================================================
|
*/
function word_censor($str, $censored, $replacement = '')
{
	if ( ! is_array($censored))
	{
		return $str;
	}

	$str = ' '.$str.' ';
	foreach ($censored as $badword)
	{
		if ($replacement != '')
		{
			$str = preg_replace("/\b(".str_replace('\*', '\w*?', preg_quote($badword)).")\b/i", $replacement, $str);
		}
		else
		{
			$str = preg_replace("/\b(".str_replace('\*', '\w*?', preg_quote($badword)).")\b/ie", "str_repeat('#', strlen('\\1'))", $str);
		}
	}
	
	return trim($str);
}


/*
|==========================================================
| Code Highlighter
|==========================================================
|
| Colorizes code strings
|
*/
function highlight_code($str)
{		
	// The highlight string function encodes and highlights 
	// brackets so we need them to start raw 
	$str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);
	
	// Replace any existing PHP tags to temporary markers so they don't accidentally
	// break the string out of PHP, and thus, thwart the highlighting.
	
	$str = str_replace(array('&lt;?php', '?&gt;',  '\\'), array('phptagopen', 'phptagclose', 'backslashtmp'), $str);
		
	// The highlight_string function requires that the text be surrounded
	// by PHP tags.  Since we don't know if A) the submitted text has PHP tags,
	// or B) whether the PHP tags enclose the entire string, we will add our
	// own PHP tags around the string along with some markers to make replacement easier later
	
	$str = '<?php //tempstart'."\n".$str.'//tempend ?>'; // <?
	
	// All the magic happens here, baby!
	$str = highlight_string($str, TRUE);

	// Prior to PHP 5, the highligh function used icky <font> tags
	// so we'll replace them with <span> tags.	
	if (abs(phpversion()) < 5)
	{
		$str = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $str);
		$str = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $str);
	}
	
	// Remove our artificially added PHP
	$str = preg_replace("#\<code\>.+?//tempstart\<br />\</span\>#is", "<code>\n", $str);
	$str = preg_replace("#\<code\>.+?//tempstart\<br />#is", "<code>\n", $str);
	$str = preg_replace("#//tempend.+#is", "</span>\n</code>", $str);
	
	// Replace our markers back to PHP tags.
	$str = str_replace(array('phptagopen', 'phptagclose', 'backslashtmp'), array('&lt;?php', '?&gt;', '\\'), $str); //<?
				
	return $str;
}
	
/*
|==========================================================
| Highlight a pharse within a text string
|==========================================================
|
*/
function highlight_phrase($str, $phrase, $tag_open = '<strong>', $tag_close = '</strong>')
{
	if ($str == '')
	{
		return '';
	}
	
	if ($phrase != '')
	{
		return preg_replace('/('.preg_quote($phrase).')/i', $tag_open."\\1".$tag_close, $str);
	}

	return $str;
}
  

/*
|=====================================================
| Word Wrap
|=====================================================
|
| Wraps text at the specified character.  Maintains
| the integrity of words.
|
*/	
function word_wrap($str, $chars = '76')
{	
	if ( ! is_numeric($chars))
		$chars = 76;
	
	$str = preg_replace("/(\r\n|\r|\n)/", "\n", $str);
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

?>