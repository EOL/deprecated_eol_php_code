<?php
$feeds = array(	
				1 => array(	"title" => "Recently harvested resources (n=20)"		, "feed" => "1"),
				2 => array(	"title" => "Recently published resources (n=20)"		, "feed" => "2"),
				3 => array(	"title" => "Harvested resources awaiting publication"	, "feed" => "3"),
				4 => array(	"title" => "Resources with harvest errors"				, "feed" => "4"),
				5 => array(	"title" => "Content Partner Resource Status"			, "feed" => "5"),
				6 => array(	"title" => "Schedules for next harvest (multiple row)"	, "feed" => "6"),
				7 => array(	"title" => "Schedules for next harvest (single row)"	, "feed" => "7")
			  );
/*			  
$domain = "services.eol.org";
$domain = "eol";
$feed_path = "eol_php_code/applications/RSS_resource_monitor/process.php";
*/
$domain 	= $_SERVER['HTTP_HOST'];
$temp 		= $_SERVER['SCRIPT_NAME'];
$temp 		= trim(substr($temp,1,strlen($temp)));
$temp 		= str_ireplace("index.php", "process.php", $temp);
$feed_path 	= $temp;
?>