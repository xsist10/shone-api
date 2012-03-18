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
| File: helpers/date_helper.php
|----------------------------------------------------------
| Purpose: Date related helpers
|==========================================================
*/

/*
|==========================================================
| Get "now" time
|==========================================================
|
| This function returns time() or its GMT equivalent
| based on the config file preference
|
*/
function now()
{
	return call('CI', 'get_system_time');
}

/*
|==========================================================
| Convert MySQL Style Datecodes
|==========================================================
|
| This function is identical to PHPs date() function,
| except that it allows date codes to be formatted using
| the MySQL style, where each code letter is preceded
| with a percent sign:  %Y %m %d etc...
|
| The benefit of doing dates this way is that you don't
| have to worry about escaping your text letters that
| match the date codes.
|
*/
function mdate($datestr = '', $time = '')
{
	if ($datestr == '')
		return '';
	
	if ($time == '')
		$time = now();
		
	$datestr = str_replace('%\\', '', preg_replace("/([a-z]+?){1}/i", "\\\\\\1", $datestr));
	return date($datestr, $time);
}

	
/*
|==========================================================
| Format A Timespan
|==========================================================
|
| Returns a span of seconds in this format: 
| 	10 days 14 hours 36 minutes 47 seconds
|
*/
function timespan($seconds = 1, $time = '')
{
	if ( ! is_numeric($seconds))
	{
		$seconds = 1;
	}
	
	if ( ! is_numeric($time))
	{
		$time = time();
	}
	
	if ($time <= $seconds)
	{
		$seconds = 1;
	}
	else
	{
		$seconds = $time - $seconds;
	}
	
	call('lang', 'load', 'date');
	
	$str = '';
	$years = floor($seconds / 31536000);
	
	if ($years > 0)
	{	
		$str .= $years.' '.call('lang', 'line', ($years	> 1) ? 'date_years' : 'date_year').', ';
	}	
	
	$seconds -= $years * 31536000;
	
	$months = floor($seconds / 2628000);
	
	if ($years > 0 || $months > 0)
	{
		if ($months > 0)
		{	
			$str .= $months.' '.call('lang', 'line', ($months	> 1) ? 'date_months' : 'date_month').', ';
		}	
	
		$seconds -= $months * 2628000;
	}

	$weeks = floor($seconds / 604800);
	
	if ($years > 0 || $months > 0 || $weeks > 0)
	{
		if ($weeks > 0)
		{	
			$str .= $weeks.' '.call('lang', 'line', ($weeks	> 1) ? 'date_weeks' : 'date_week').', ';
		}
		
		$seconds -= $weeks * 604800;
	}			

	$days = floor($seconds / 86400);
	
	if ($months > 0 || $weeks > 0 || $days > 0)
	{
		if ($days > 0)
		{	
			$str .= $days.' '.call('lang', 'line', ($days	> 1) ? 'date_days' : 'date_day').', ';
		}
	
		$seconds -= $days * 86400;
	}
	
	$hours = floor($seconds / 3600);
	
	if ($days > 0 || $hours > 0)
	{
		if ($hours > 0)
		{
			$str .= $hours.' '.call('lang', 'line', ($hours	> 1) ? 'date_hours' : 'date_hour').', ';
		}
		
		$seconds -= $hours * 3600;
	}
	
	$minutes = floor($seconds / 60);
	
	if ($days > 0 || $hours > 0 || $minutes > 0)
	{
		if ($minutes > 0)
		{	
			$str .= $minutes.' '.call('lang', 'line', ($minutes	> 1) ? 'date_minutes' : 'date_minutes').', ';
		}
		
		$seconds -= $minutes * 60;
	}
	
	if ($str == '')
	{
		$str .= $seconds.' '.call('lang', 'line', ($seconds	> 1) ? 'date_seconds' : 'date_second').', ';
	}
			
	return substr(trim($str), 0, -1);
}

	
/*
|==========================================================
| Number of days in a month
|==========================================================
|
| Takes a month/year as input and returns
| the number of days for the given month/year.
| Takes leap years into consideration.
|
*/
function days_in_month($month = 0, $year = '')
{
	if ($month < 1 || $month > 12)
	{
		return 0;
	}
	
	if ( ! is_numeric($year) OR strlen($year) != 4)
	{
		$year = date('Y');
	}
	
	if ($month == 2)
	{        
		if ($year % 400 == 0 || ($year % 4 == 0 AND $year % 100 != 0))
		{
			return 29;
		}
	}

	$days_in_month	= array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	return $days_in_month[$month - 1];
}

/*
|==========================================================
| Converts a local Unix timestamp to GMT
|==========================================================
|
*/
function local_to_gmt($time = '')
{
	if ($time == '')
		$time = time();
	
	return mktime( gmdate("H", $time), gmdate("i", $time), gmdate("s", $time), gmdate("m", $time), gmdate("d", $time), gmdate("Y", $time));   
}

/*
|==========================================================
| Converts GMT time to a localized value
|==========================================================
|
| Takes a Unix timestamp (in GMT) as input, and returns
| at the local value based on the timezone and DST setting
| submitted
|
*/
function gmt_to_local($time = '', $timezone = 'UTC', $dst = FALSE)
{			
	if ($time == '')
	{
		return now();
	}
	
	$time += timezones($timezone) * 3600;

	if ($dst == TRUE)
	{
		$time += 3600;
	}
	
	return $time;
}


/*
|==========================================================
| Converts a MySQL Timestamp to Unix
|==========================================================
*/
function mysql_to_unix($time = '')
{    
	// We'll remove certain characters for backward compatibility
	// since the formatting changed with MySQL 4.1
	// YYYY-MM-DD HH:MM:SS
	
	$time = str_replace('-', '', $time);
	$time = str_replace(':', '', $time);
	$time = str_replace(' ', '', $time);
	
	// YYYYMMDDHHMMSS
	return  mktime( 
					substr($time, 8, 2),
					substr($time, 10, 2),
					substr($time, 12, 2),
					substr($time, 4, 2),
					substr($time, 6, 2),
					substr($time, 0, 4)
					);
}


/*
|==========================================================
| Unix to "Human"
|==========================================================
|
| Formats Unix timestamp to the following prototype: 2006-08-21 11:35 PM
|
*/
function unix_to_human($time = '', $seconds = FALSE, $fmt = 'us')
{
	$r  = date('Y', $time).'-'.date('m', $time).'-'.date('d', $time).' ';
		
	if ($fmt == 'us')
	{
		$r .= date('h', $time).':'.date('i', $time);
	}
	else
	{
		$r .= date('H', $time).':'.date('i', $time);
	}
	
	if ($seconds)
	{
		$r .= ':'.date('s', $time);
	}
	
	if ($fmt == 'us')
	{
		$r .= ' '.date('A', $time);
	}
		
	return $r;
}


/*
|==========================================================
| Convert "human" date to GMT
|==========================================================
|
| Reverses the above process
|
*/
function human_to_unix($datestr = '')
{
	if ($datestr == '')
	{
		return FALSE;
	}
	
	$datestr = trim($datestr);
	$datestr = preg_replace("/\040+/", "\040", $datestr);

	if ( ! ereg("^[0-9]{2,4}\-[0-9]{1,2}\-[0-9]{1,2}\040[0-9]{1,2}:[0-9]{1,2}.*$", $datestr))
	{
		return FALSE;
	}

	$split = preg_split("/\040/", $datestr);

	$ex = explode("-", $split['0']);            
	
	$year  = (strlen($ex['0']) == 2) ? '20'.$ex['0'] : $ex['0'];
	$month = (strlen($ex['1']) == 1) ? '0'.$ex['1']  : $ex['1'];
	$day   = (strlen($ex['2']) == 1) ? '0'.$ex['2']  : $ex['2'];

	$ex = explode(":", $split['1']); 
	
	$hour = (strlen($ex['0']) == 1) ? '0'.$ex['0'] : $ex['0'];
	$min  = (strlen($ex['1']) == 1) ? '0'.$ex['1'] : $ex['1'];

	if (isset($ex['2']) AND ereg("[0-9]{1,2}", $ex['2']))
	{
		$sec  = (strlen($ex['2']) == 1) ? '0'.$ex['2'] : $ex['2'];
	}
	else
	{
		// Unless specified, seconds get set to zero.
		$sec = '00';
	}
	
	if (isset($split['2']))
	{
		$ampm = strtolower($split['2']);
		
		if (substr($ampm, 0, 1) == 'p' AND $hour < 12)
			$hour = $hour + 12;
			
		if (substr($ampm, 0, 1) == 'a' AND $hour == 12)
			$hour =  '00';
			
		if (strlen($hour) == 1)
			$hour = '0'.$hour;
	}
			
	return mktime($hour, $min, $sec, $month, $day, $year);
}


/*
|==========================================================
| Timezone Menu
|==========================================================
|
| Generates a drop-down menu of timezones.
|
*/
function timezone_menu($default = 'UTC', $class = "", $name = 'timezones')
{
	call('lang', 'load', 'date');
	
	if ($default == 'GMT')
		$default = 'UTC';

	$menu = '<select name="'.$name.'"';
	
	if ($class != '')
	{
		$menu .= ' class="'.$class.'"';
	}
	
	$menu .= ">\n";
	
	foreach (timezones() as $key => $val)
	{
		$selected = ($default == $key) ? " selected='selected'" : '';

		$menu .= "<option value='{$key}'{$selected}>".call('lang', 'line', $key)."</option>\n";
	}

	$menu .= "</select>";

	return $menu;
}
	
/*
|==========================================================
| Timezones
|==========================================================
|
| Returns an array of timezones.  This is a helper function
| for varios other ones in this library
|
*/
function timezones($tz = '')
{
	// Note: Don't change the order of these even though 
	// some items appear to be in the wrong order
		
	$zones = array( 
					'UM12' => -12,
					'UM11' => -11,
					'UM10' => -10,
					'UM9'  => -9,
					'UM8'  => -8,
					'UM7'  => -7,
					'UM6'  => -6,
					'UM5'  => -5,
					'UM4'  => -4,
					'UM25' => -2.5,
					'UM3'  => -3,
					'UM2'  => -2,
					'UM1'  => -1,
					'UTC'  => 0,
					'UP1'  => +1,
					'UP2'  => +2,
					'UP3'  => +3,
					'UP25' => +2.5,
					'UP4'  => +4,
					'UP35' => +3.5,
					'UP5'  => +5,
					'UP45' => +4.5,
					'UP6'  => +6,
					'UP7'  => +7,
					'UP8'  => +8,
					'UP9'  => +9,
					'UP85' => +8.5,
					'UP10' => +10,
					'UP11' => +11,
					'UP12' => +12                    
				);
				
	if ($tz == '')
	{
		return $zones;
	}
	
	if ($tz == 'GMT')
		$tz = 'UTC';
	
	return ( ! isset($zones[$tz])) ? 0 : $zones[$tz];
}


?>