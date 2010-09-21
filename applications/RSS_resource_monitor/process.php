<?php
header("Content-Type: application/rss+xml; charset=ISO-8859-1");
include("RSS.class.php");	

if(isset($_GET["f"]))   $f=$_GET["f"];
else                    $f=1;	
if(isset($_GET["resources_id"]))$id=$_GET["resources_id"];
else                            $id=21; //Amphibiaweb		
if(isset($_GET["f_list"]))  $f_list=$_GET["f_list"];
else                        $f_list='';
	
$rss = new RSS();			
$temp = $rss->GetFeed($f,$id,$f_list);
function clean_str($str)
{
	$str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);			
	return $str;
}	
echo trim($temp);
?>