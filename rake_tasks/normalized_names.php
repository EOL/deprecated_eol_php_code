#!/usr/local/bin/php
<?php


define("ENVIRONMENT", "development");
//define("MYSQL_DEBUG", "true");
include_once("../config/start.php");



Tasks::rebuild_normalized_names();


?>