<?php

// require_once("../../../FreshData/config/settingz.php");
require_once("../../../LiteratureEditor/Custom/lib/Functions.php");
require_once("../../../FreshData/controllers/other.php");
require_once("../../../FreshData/controllers/freshdata.php");
$params =& $_GET;
if(!$params) $params =& $_POST;
// echo "<pre>"; print_r($params); echo "</pre>";

$ctrler = new freshdata_controller($params);
$task = $ctrler->get_available_job("genHigherClass_job");
print_r($task);

?>