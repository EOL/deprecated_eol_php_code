<?php
namespace php_active_record;


// get HTTP request parameters
$parameters = array_merge($_GET, $_POST);
if(@isset($parameters["debug"]))
{
    define('DEBUG', true);
    define('MYSQL_DEBUG', true);
}

require_once(__DIR__ . '/../../../config/environment.php');

$request_method = $_SERVER['REQUEST_METHOD'];

$controller = @$parameters["ac_controller"];
$action = @$parameters["ac_action"];
$id = @$parameters["ac_id"];
unset($parameters["ac_controller"]);
unset($parameters["ac_action"]);
unset($parameters["ac_id"]);

if(is_numeric($action))
{
    $id = $action;
    $action = NULL;
}
if(@$parameters['action'])
{
    $action = $parameters['action'];
    unset($parameters['action']);
}

// if an id is passed it must be numeric
//if(!is_null($id) && !is_numeric($id)) trigger_error('Identifier must be an integer', E_USER_ERROR);

// need to cast the id as an int
if(isset($id)) $parameters["id"] = (int) $id;

// set default action to 'index'
if(!$action) $action = 'index';



if($controller)
{
    // path to controller class
    $file = DOC_ROOT . 'app/controllers/' . $controller . '_controller.php';
    
    // make sure the controller class exists
    if(file_exists($file))
    {
        // include the controller class
        @include_once($file);
        $class = __NAMESPACE__ . '\\' . to_camel_case($controller) . "Controller";
        
        // make sure the controller class actually loaded
        if(class_exists($class))
        {
            // make sure the defined controller method is defined
            if(!method_exists($class, $action)) trigger_error('Undefined method `' . $action . '` in class `' . $class . '`', E_USER_ERROR);
            
            // a bit of a hack so the controller can intercept the call and run stuff before/after the method
            $action = 'method_|' . $controller . '|' . $action;
            
            // call the action and pass the arguments
            return $class::$action($parameters);
        }
    }
    
    // the controller class doesn't exist
    header("HTTP/1.0 404 Not Found");
    echo file_get_contents(DOC_ROOT . '/public/404.html');
    exit;
    trigger_error('Unknown controller `' . $controller . '`', E_USER_ERROR);
}

// shouldn't ever get here
header("HTTP/1.0 404 Not Found");
echo file_get_contents(DOC_ROOT . '/public/404.html');
exit;
trigger_error('Unspecified controller', E_USER_ERROR);

?>