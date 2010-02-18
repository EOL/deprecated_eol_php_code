<?php



define("ENVIRONMENT", "test");
define("DEBUG", true);
define("MYSQL_DEBUG", true);
include_once("../../config/start.php");


echo "<a href='".FlickrAPI::login_url()."'>login</a>";



?>