<?php
//define("ENVIRONMENT", "integration");				//where stats are stored
//define("ENVIRONMENT", "slave_215");				//where stats are stored
//define("ENVIRONMENT", "development");				//where stats are stored
//define("ENVIRONMENT", "data_main");				//where stats are stored

define("DEBUG", true);
define("MYSQL_DEBUG", false);
define("DEBUG_TO_FILE", false);

require_once(dirname(__FILE__)."/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

set_time_limit(0);

$parameters =& $_GET;
if(!$parameters) $parameters =& $_POST;
if(@!$parameters["_ctrl"]) $parameters["_ctrl"] = "species_stats";
$controller = @$parameters["_ctrl"];

if(@$argv[1]) $parameters['group']  = $argv[1];
if(@$argv[2]) $parameters['f']      = $argv[2];

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
