<?php
//#!/usr/local/bin/php
//
//exit;

define("ENVIRONMENT", "slave");
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

set_time_limit(0);

//=================================================================
//query 1
$days = getDays();

print $days . "<hr>";

$query = "Select
page_stats_taxa.id,
page_stats_taxa.taxa_count,
page_stats_taxa.taxa_text,
page_stats_taxa.taxa_images,
page_stats_taxa.taxa_text_images,
page_stats_taxa.taxa_BHL_no_text,
page_stats_taxa.taxa_links_no_text,
page_stats_taxa.taxa_images_no_text,
page_stats_taxa.taxa_text_no_images,
page_stats_taxa.vet_obj_only_1cat_inCOL,
page_stats_taxa.vet_obj_only_1cat_notinCOL,
page_stats_taxa.vet_obj_morethan_1cat_inCOL,
page_stats_taxa.vet_obj_morethan_1cat_notinCOL,
page_stats_taxa.vet_obj,
page_stats_taxa.no_vet_obj2,
page_stats_taxa.with_BHL,
page_stats_taxa.vetted_not_published,
page_stats_taxa.vetted_unknown_published_visible_inCol,
page_stats_taxa.vetted_unknown_published_visible_notinCol,
page_stats_taxa.date_created,
page_stats_taxa.time_created,
page_stats_taxa.pages_incol,
page_stats_taxa.pages_not_incol,
page_stats_taxa.`timestamp`
From page_stats_taxa
where concat(date_created,' ',time_created) not in($days)
"; 

print $query;

$result = $mysqli->query($query);    


/*table fields
$fields=array();
$fields[]="id";
$fields[]="taxa_count";
$fields[]="taxa_text";
$fields[]="taxa_images";
$fields[]="taxa_text_images";
$fields[]="taxa_BHL_no_text";
$fields[]="taxa_links_no_text";
$fields[]="taxa_images_no_text";
$fields[]="taxa_text_no_images";
$fields[]="vet_obj_only_1cat_inCOL";
$fields[]="vet_obj_only_1cat_notinCOL";
$fields[]="vet_obj_morethan_1cat_inCOL";
$fields[]="vet_obj_morethan_1cat_notinCOL";
$fields[]="vet_obj";
$fields[]="no_vet_obj2";
$fields[]="with_BHL";
$fields[]="vetted_not_published";
$fields[]="vetted_unknown_published_visible_inCol";
$fields[]="vetted_unknown_published_visible_notinCol";
$fields[]="date_created";
$fields[]="time_created";
$fields[]="pages_incol";
$fields[]="pages_not_incol";
$fields[]="timestamp";
*/

$temp = save_to_txt($result,"saved_stats",",","csv");
//=================================================================

function save_to_txt($result,$filename,$field_separator,$file_extension)
{
	$str="";    
    
	while($result && $row=$result->fetch_assoc())	
	{        
        $found = false;
        if($found){}
        else
        {
            //==============================================================
            $arr = array();
            $col_total        = $row["pages_incol"];
            $notcol_total     = $row["pages_not_incol"];
            //$arr['Run date'] 
                $arr[]= "$row[date_created] $row[time_created]";                
                
            //$arr['Total number of pages']                        
                $arr[]= $col_total + $notcol_total;
            //$arr['Total number of pages with names from CoL']    
                $arr[]= $col_total;
            //$arr['Total number of pages with names not in CoL']  
                $arr[]= $notcol_total;                
            //$arr['Pages with content']              
                $arr[]= $row["taxa_count"];
            //$arr['Pages with text']                 
                $arr[]= $row["taxa_text"];
            //$arr['Pages with images']               
                $arr[]= $row["taxa_images"];
            //$arr['Pages with text and images']      
                $arr[]= $row["taxa_text_images"];
            //$arr['Pages with images and no text']   
                $arr[]= $row["taxa_images_no_text"];
            //$arr['Pages with text and no images']   
                $arr[]= $row["taxa_text_no_images"];                
            //$arr['Number of pages with at least one vetted data object']                                        
                $arr[]= $row["vet_obj"];
            //$arr['Number of taxa with no data objects (in CoL), i.e. base pages']                               
                $arr[]= $row["no_vet_obj2"];
            //$arr['Number of pages with a CoL name and a vetted data or Flickr object in one category']          
                $arr[]= $row["vet_obj_only_1cat_inCOL"];
            //$arr['Number of non CoL pages with a vetted data object or an image from Flickr in one category']   
                $arr[]= $row["vet_obj_only_1cat_notinCOL"];
            //$arr['Number of pages with a CoL name with vetted data objects in more than one category']          
                $arr[]= $row["vet_obj_morethan_1cat_inCOL"];
            //$arr['Number of non CoL pages taxa with vetted data objects in more than one category']             
                $arr[]= $row["vet_obj_morethan_1cat_notinCOL"];
            //$arr['Approved pages awaiting publication']                          
                $arr[]= $row["vetted_not_published"];
            //$arr['Pages with CoL names with content that requires curation']     
                $arr[]= $row["vetted_unknown_published_visible_inCol"];
            //$arr['Pages NOT with CoL names with content that requires curation'] 
                $arr[]= $row["vetted_unknown_published_visible_notinCol"];            
            //==============================================================
    		for ($i = 0; $i < count($arr); $i++) 		
	    	{
		    	$str .= $arr[$i] . $field_separator;    //chr(9) is tab
    		}
    		$str .= "\n";            
        }// not found

	}
    if($file_extension == "txt")$temp = "temp/";
    else                        $temp = "";
    
    $filename = "$filename" . "." . $file_extension;
	if($fp = fopen($filename,"a")){fwrite($fp,$str);fclose($fp);}		
    
}//function save_to_txt($result,$filename,$fields,$year_month,$field_separator,$with_col_header,$file_extension)




function getDays()
{
    $filename = "saved_stats.csv";
    if(!($handle = fopen($filename, "r")))return;
    
    $comma_separated='';
    while (($data = fgetcsv($handle)) !== FALSE) 
    {
        $num = count($data);
        for ($c=0; $c < $num; $c++) 
        {        
            if($c==0)$comma_separated .= "'$data[$c]'" . ",";
        }
    }//end while
    $comma_separated = trim(substr($comma_separated,0,strlen($comma_separated)-1));
    return $comma_separated;
}




function get_val_var($v)
{
    if     (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif (isset($_POST["$v"])){$var=$_POST["$v"];}
    else   return NULL;                            
    return $var;    
}



?>