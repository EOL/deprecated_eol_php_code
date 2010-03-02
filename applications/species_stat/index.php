<?php
//define("ENVIRONMENT", "integration");				//where stats are stored
//define("ENVIRONMENT", "slave_215");				//where stats are stored
//define("ENVIRONMENT", "development");				//where stats are stored
//define("ENVIRONMENT", "data_main");				//where stats are stored


//$GLOBALS['ENV_NAME'] = 'production';

define("DEBUG", true);
define("MYSQL_DEBUG", false);
define("DEBUG_TO_FILE", false);

//include_once(dirname(__FILE__).  "/../../config/environment.php");
require_once("../../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];

set_time_limit(0);

$parameters =& $_GET;
if(!$parameters) $parameters =& $_POST;
if(@!$parameters["_ctrl"]) $parameters["_ctrl"] = "species_stats";
$controller = @$parameters["_ctrl"];

if(@$argv[1]) $parameters['group']  = $argv[1];
if(@$argv[2]) $parameters['f']      = $argv[2];

/*
$parameters["_ctrl"] = "species_stats";
$parameters['group']  = 1;
$parameters['f']      = "results";
*/

if($controller)
{
    $class = $controller . "_controller";
    if(class_exists($class))
    {
        $ctrl = new $class();
    }else echo "Bad controller";
}else
{
    echo "No controller";
}

function __autoload($controller)
{
    $controller = preg_replace("/_controller$/", "", $controller);
    @include_once("controllers/". $controller. ".php");
}

?>
