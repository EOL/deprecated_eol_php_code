#!/usr/local/bin/php
<?php
//#!/usr/local/bin/php
//connector for WORMS
//exit;
/* 
22966. 22964 of 68984
*/
set_time_limit(0);
ini_set('memory_limit','3500M');
//define("ENVIRONMENT", "development");
define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

// /*
//if(isset($argv[1]))$start=$argv[1]);        
$start=0;
//if(isset($argv[2]))$file_number=$argv[2]);  
$file_number=1;
// */

//only on local; to be deleted before going into production
/*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
*/
$resource = new Resource(26);//WORMS
//exit("[$resource->id]");


//$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "bold_" . $file_number .".xml";
//$OUT = fopen($old_resource_path, "w+");

$main_count=0;
//====================================================================================
$main_id_list = array();
$id_processed = array();
$main_id_list = get_main_id_list();
$total_taxid_count = count($main_id_list);
echo "\n total taxid count = " . $total_taxid_count . "\n\n";;
//exit;
//====================================================================================
$i=1;
//while( count($id_processed) != count($main_id_list) )
//{
    echo "-x- \n";    
    for ($i = $start; $i < $total_taxid_count; $i++)     
    {
        $taxid = $main_id_list[$i];
        //if(!in_array("$taxid", $id_processed))        
        //{                        
            //if($i % 2 == 0)
            if(count($id_processed) % 10000 == 0)
            {   
                //start new file                
                if(isset($OUT))fclose($OUT);
                $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "/temp/worms_" . $file_number .".xml";
                $OUT = fopen($old_resource_path, "w+");            
                $file_number++;
            }
            if(process($taxid))
            {
                //$id_processed[] = $taxid;
                echo " -ok- ";
            }
            else echo " -bad- ";
            
            echo $i+1 . ". of $total_taxid_count \n";            
            //echo count($id_processed) . " of " . $total_taxid_count . "\n";                        
        //}                
    }    
    $main_id_list = get_main_id_list();

//}//end while

//print_r($main_id_list);print_r($id_processed);
//====================================================================================
$str = "</response>";fwrite($OUT, $str);fclose($OUT);
//====================================================================================
//start compiling all worms_?.xml 
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
$OUT = fopen($old_resource_path, "w+");
$str = "<?xml version='1.0' encoding='utf-8' ?>\n";
$str .= "<response\n";
$str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";           
$str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
$str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";           
$str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";           
$str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";           
$str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";           
$str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";                      
$str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
fwrite($OUT, $str);
$i=0;
while(true)
{
    print "$i "; $i++;
    $file = CONTENT_RESOURCE_LOCAL_PATH . "/temp/worms_" . $i .".xml";
    $str = Functions::get_remote_file($file);
    if($str)
    {
        fwrite($OUT, $str);
        unlink($file);
    }        
    else break;
}
print "\n --end-- ";
fclose($OUT);
//end
//====================================================================================
//start functions #################################################################################################
function process($id)
{   global $OUT;        
    $file = "http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=$id";
    //       http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=255127    
    $contents = Functions::get_remote_file($file);
    if($contents)
    {
    	$pos1 = stripos($contents,"<taxon>");
    	$pos2 = stripos($contents,"</taxon>");			
    	if($pos1 != "" and $pos2 != "")
    	{
    		$contents = trim(substr($contents,$pos1,$pos2-$pos1+8));
            fwrite($OUT, $contents);
            return true;
    	}
    }    
    return false;
}//end process() 
function get_main_id_list()
{
    //$url[]="http://127.0.0.1/mtce/WORMS/20090819/id/2007.xml";
    //$url[]="http://127.0.0.1/mtce/WORMS/20090819/id/2008.xml";
    //$url[]="http://127.0.0.1/mtce/WORMS/20090819/id/2009.xml";

     /*
    $url[]="http://127.0.0.1/mtce/WORMS/20091016/id/2007.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20091016/id/2008.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20091016/id/2009.xml";
     */

//     /*
    $url[]="http://127.0.0.1/mtce/WORMS/20091016/id/test1.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20091016/id/test2.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20091016/id/test3.xml";    
//     */

    //$url[]="http://www.marinespecies.org/aphia.php?p=eol&action=taxlist&startdate=19960101&enddate=20071231";
    //$url[]="http://www.marinespecies.org/aphia.php?p=eol&action=taxlist&startdate=20080101&enddate=20081231";
    //$url[]="http://www.marinespecies.org/aphia.php?p=eol&action=taxlist&startdate=20090101&enddate=20091231";    
 
    echo "\n URLs = " . sizeof($url) . "\n";
    $no_of_urls = sizeof($url);        
    $arr = array(); 
    $jj=0;
    for ($i = 0; $i < count($url); $i++) 
    {
        $j=0;        
        //if($xml = @simplexml_load_file($url[$i]))        
        if($xml = Functions::get_hashed_response($url[$i]))        
        {   
            $no_of_taxdetail = count($xml->taxdetail);
            foreach($xml->taxdetail as $taxdetail)
            {
                $temp = @$taxdetail["id"];
                $arr["$temp"]=true;
                $j++; $jj++;
            }    
        }
        echo "\n" . $i+1 . " of " . $no_of_urls . " URLs | taxid count = " . $j . "\n";     
    }
    $arr = array_keys($arr);
    return $arr;
}//get_main_id_list()
?>