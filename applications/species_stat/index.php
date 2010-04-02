<?php
exit("-disabled-");
//include_once(dirname(__FILE__).  "/../../config/environment.php");
require_once(dirname(__FILE__) ."/../../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];


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
