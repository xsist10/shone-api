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
| File: helpers/form_helper.php
|----------------------------------------------------------
| Purpose: Form Helpers
|==========================================================
*/

	
/*
|==========================================================
| Form Declaration
|==========================================================
|
*/
function form_open($action, $attributes = array(), $hidden = array())
{
	$form = '<form method="post" action="'.call('config', 'site_url', $action).'"';
	
	if (is_array($attributes) AND count($attributes) > 0)
	{
		foreach ($attributes as $key => $val)
		{
			$form .= ' '.$key.'="'.$val.'"';  
		}
	}
	
	$form .= '>';

	if (is_array($hidden) AND count($hidden > 0))
	{
		$form .= form_hidden($hidden);
	}
	
	return $form;
}

/*
|==========================================================
| Form Declaration - Multipart type
|==========================================================
|
*/
function form_open_multipart($action, $attributes = array(), $hidden = array(), $tagopen = '<div style="padding:0;margin:0;>', $tagclose = '</div>')
{
	$attributes['enctype'] = 'multipart/form-data';
	return form_open($action, $attributes, $hidden, $tagopen, $tagclose);
}
	
/*
|==========================================================
| Hidden Input Field
|==========================================================
|
*/
function form_hidden($name, $value = '')
{        
	if ( ! is_array($name))
	{
		return '<input type="hidden" name="'.$name.'" value="'.form_prep($value).'" />';
	}

	$form = '';
	foreach ($name as $name => $value)
	{
		$form .= '<input type="hidden" name="'.$name.'" value="'.form_prep($value).'" />';
	}
	
	return $form;
}

	
/*
|==========================================================
| Text Input Field
|==========================================================
|
*/
function form_input($data = '', $value = '', $extra = '')
{
	$defaults = array('type' => 'text', 'name' => (( ! is_array($data)) ? $data : ''), 'value' => $value, 'maxlength' => '500', 'size' => '50', 'style' => 'width:100%;');

	return "<input ".parse_form_attributes($data, $defaults).$extra." />\n";
}

/*
|==========================================================
| Password Field
|==========================================================
|
*/
function form_password($data = '', $value = '', $extra = '')
{
	if ( ! is_array($data))
	{
		$data['name'] = $data;
	}

	$data['type'] = 'password';
	return form_input($data, $value, $extra);
}

/*
|==========================================================
| Upload Field
|==========================================================
|
*/
function form_upload($data = '', $value = '', $extra = '')
{
	if ( ! is_array($data))
	{
		$data['name'] = $data;
	}

	$data['type'] = 'file';
	return form_input($data, $value, $extra);
}

/*
|==========================================================
| Textarea Field
|==========================================================
|
*/
function form_textarea($data = '', $value = '', $extra = '')
{
	$defaults = array('type' => 'text', 'name' => (( ! is_array($data)) ? $data : ''), 'cols' => '90', 'rows' => '12', 'style' => 'width:100%;');
	
	return "<textarea ".parse_form_attributes($data, $defaults).$extra.">".(( ! isset($data['value'])) ? $value : $data['value'])."</textarea>\n";
}

/*
|==========================================================
| Dropdown Field
|==========================================================
|
*/
function form_dropdown($name = '', $options = array(), $selected = '', $extra = '')
{
	if ($extra != '') $extra = ' '.$extra;
		
	$form = '<select name="'.$name.'"'.$extra.">\n";
	
	foreach ($options as $key => $val)
	{
		$sel = ($selected != $key) ? '' : ' selected';
		
		$form .= '<option name="'.$key.'"'.$sel.'>'.$val."</option>\n";
	}

	$form .= '</select>';
	
	return $form;
}

/*
|==========================================================
| Checkbox Field
|==========================================================
|
*/
function form_checkbox($data = '', $value = '', $checked = TRUE, $extra = '')
{
	$defaults = array('type' => 'checkbox', 'name' => (( ! is_array($data)) ? $data : ''), 'value' => $value);
	
	if (isset($data['checked']))
	{
		$checked = $data['checked'];
	}
	
	if ($checked == TRUE)
	{
		$defaults['checked']  = "checked";
	}

	return "<input ".parse_form_attributes($data, $defaults).$extra." />\n";
}

/*
|==========================================================
| Radio Field
|==========================================================
|
*/
function form_radio($data = '', $value = '', $checked = TRUE, $extra = '')
{
	if ( ! is_array($data))
	{
		$data['name'] = $data;
	}

	$data['type'] = 'radio';
	return form_checkbox($data, $value, $checked, $extra);
}


/*
|==========================================================
| Submit Button
|==========================================================
|
*/
function form_submit($data = '', $value = '', $extra = '')
{
	$defaults = array('type' => 'submit', 'name' => (( ! is_array($data)) ? $data : ''), 'value' => $value);

	return "<input ".parse_form_attributes($data, $defaults).$extra." />\n";
}

/*
|==========================================================
| Submit Close
|==========================================================
|
*/
function form_close($extra = '')
{
	return "</form>\n".$extra;
}


/*
|==========================================================
| Form Prep
|==========================================================
|
| Formats text so that it can be safely placed in
| a form field in the event it has HTML tags.
|
*/
function form_prep($str = '')
{
	if ($str == '')
	{
		return '';
	}
	
	return str_replace(array("'", '"'), array("&#39;", "&quot;"), htmlspecialchars($str));	
}

/*
|==========================================================
| Parses the form attributes
|==========================================================
|
*/
function parse_form_attributes($attributes, $default)
{
	if (is_array($attributes))
	{
		foreach ($default as $key => $val)
		{
			if (isset($attributes[$key]))
			{
				$default[$key] = $attributes[$key];
				unset($attributes[$key]);
			}
		}
		
		if (count($attributes) > 0)
		{	
			$default = array_merge($default, $attributes);
		}
	}
	
	$att = '';
	foreach ($default as $key => $val)
	{
		if ($key == 'value')
		{
			$val = form_prep($val);
		}
	
		$att .= $key . '="' . $val . '" ';
	}

	return $att;
}

?>