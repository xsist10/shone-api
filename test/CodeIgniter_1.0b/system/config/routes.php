<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------
| This file lets you re-map URI requests to specific locations 
|
| Typically there is a one-to-one relationship between a URL string 
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
| 	www.your-site.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL. 
|
| For example, lets say your site contains URLs with this prototype:
|
|	www.your-site.com/product/1/
|	www.your-site.com/product/2/
|	www.your-site.com/product/3/
|	www.your-site.com/product/4/
|
| You could set up a routing rule like this:
|
|	$route['product/:num'] = "catalog/product_lookup";
|
| With this rule, if the literal word "product" is found in the first 
| segment of the URL and a number is found in the second segment, 
| the "catalog" class and the "product_lookup" method are instead used.
|
| -------------------------------------------------------------------
| INSTRUCTIONS
| -------------------------------------------------------------------
|
| Routes are set using an assoiative array called $route.  The
| array key will contain the URI to be matched, the array value will 
| contain the destination it should be re-routed to. You can match 
| literal values or you can use two wildcard types:
|
|	:num
|	:any
|
| :num will match a segment containing only numbers.
| :any will match a segment containing any character.
|
| -------------------------------------------------------------------
| EXAMPLES
| -------------------------------------------------------------------
|
|	$route['journals'] = "blogs";
|
| Any URL containing the word "journals" in the first segment will
| be remapped to the "blogs" class.
|
|	$route['blog/joe'] = "blogs/users/34";
|
| Any URL containing the segments blog/joe will be remapped to the 
| "blogs" class and the "users" method.  The ID will be set to "34".
|
|	$route['product/:any'] = "catalog/product_lookup";
|
| Any URL with "product" as the first segment, and anything in the
| second will be remapped to the "catalog" class and the 
| "product_lookup" method.
|
| IMPORTANT: Do not use leading/trailing slashes.
|
| -------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------
|
| There are two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['scaffolding_trigger'] = 'scaffolding';
|
| This route lets you set a "secret" word that will trigger the 
| scaffolding feature for added security. Note: Scaffolding must be 
| enabled in the controller in which you intend to use it.  Please see 
| the user guide for details.
|
*/

$route['default_controller'] = 'welcome';
$route['scaffolding_trigger'] = 'scaffolding';

// Define your own routes below -------------------------------------



?>