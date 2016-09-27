<?php
$feeds = array(	
				1  => array("title" => "Recently harvested resources (n=50)"		, "feed" => "1"),
				2  => array("title" => "Recently published resources (n=50)"		, "feed" => "2"),
				3  => array("title" => "Harvested resources awaiting publication"	, "feed" => "3"),
				4  => array("title" => "Resources with harvest errors"				, "feed" => "4"),
				5  => array("title" => "Content Partner Resource Status"			, "feed" => "5"),
				6  => array("title" => "Schedules for next harvest (multiple row)"	, "feed" => "6"),
				7  => array("title" => "Schedules for next harvest (single row)"	, "feed" => "7"),
                8  => array("title" => "Resources: Upload Failed"	                , "feed" => "8"),
                9  => array("title" => "Resources: Validation Failed"	            , "feed" => "9"),
                10 => array("title" => "Resources: Processing Failed"	            , "feed" => "10"),                
                11 => array("title" => "Resources: Publish Pending"	                , "feed" => "11"),
                12 => array("title" => "Resources: Unpublish Pending"	            , "feed" => "12"),
                13 => array("title" => "Resources: Harvest Requested"                   , "feed" => "13")
			  );
              
$domain 	= $_SERVER['HTTP_HOST'];
$temp 		= $_SERVER['SCRIPT_NAME'];
$temp 		= trim(substr($temp,1,strlen($temp)));
$temp 		= str_ireplace("index.php", "process.php", $temp);
$feed_path 	= $temp;
?>