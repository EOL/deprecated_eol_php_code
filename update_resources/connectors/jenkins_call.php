<?php
/*
Worked as it was able to call the local Jenkins.
But this was not used at all.
And for some reason the PHP inside the job didn't execute.
==================================================================

then this block here is where I copied portions of FreshData/config/settingz.php

==================================================================
REMINDER:
For future use: is to just make a form, where I'll paste the list of IDs from e.g.:
https://eol-jira.bibalex.org/secure/attachment/74820/plants.txt
And let the original script run its course.

That will make it similar to the apps before it.
*/

require_once("../../../LiteratureEditor/Custom/lib/Functions.php");
require_once("../../../FreshData/controllers/other.php");
require_once("../../../FreshData/controllers/freshdata.php");

// exit("\n".getcwd()."\n");

$ctrler = new freshdata_controller(array());
/* no need
$task = $ctrler->get_available_job("xls2dwca_job");
*/

$task = "BHL_Plants";
$resource_ID = "91529";

// echo "\n".JENKINS_CRUMB."\n";

$c = $ctrler->build_curl_cmd_for_jenkins_specific($task, $resource_ID);
$shell_debug = shell_exec($c);
?>