<?php
exit; 
/*connector for WORMS

start: Fri Jul 16 11:25AM
end: Wed Jul 21 04:00PM


estimated execution time: 112 hours

Partner provides a list of their species ID's in multiple XML services. They also provided individual 
species service for the EOL-schema. This connector loops to the ID's then run each species to their species service 
and compiles all XML into 1 final XML for EOL ingestion.

This can run on Eli's PC and just move the resource 26.xml to Beast.

*/
$timestart = microtime(1);

/*  connector start     connector end           ID's    bad ID's    connector completed     published in eol.org
                        2010-Jan-11             160115  97    
                        2010-Mar-02             137794  59          
                        2010-Mar-15             167902  3679                                            
                        2010-May-24             7986                112 hrs
    2010-07-16 11:25AM  2010-07-21 04:00PM      188328  6845        123 hrs                 2010-07-22 09:34AM  
    2010-08-09 PM  
    

not well-formed XML = 102320,102337,102338,102352,102355,102369,102370,102387,102388,102419,102420,
102437,102438,102452,102455,102469,102470,142868,145997,100558,371481,371483,371532,371534,371549,
372454,372467,372469,372484,371650,371665,368300,368257,368259,368272,368274,368289,368291,279034,
385873,385888,385890,386283,386297,386298,438235,438237,438014,438016,438050,438080,437694,437719
        
http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=231158    

        2010-feb-26
2007    53366
2008    66101
2009    8955
2010    9572         
*/


$GLOBALS['ENV_NAME'] = "slave_215";
include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$bad_id=""; //not well formed XML
// /*
$start=0;
$file_number=1;
// */

$resource = new Resource(26);//WORMS
//exit("[$resource->id]");


//$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "bold_" . $file_number .".xml";
//$OUT = fopen($old_resource_path, "w+");

$main_count=0;
//====================================================================================
$main_id_list = array();
//$id_processed = array();
$main_id_list = get_main_id_list();
$total_taxid_count = count($main_id_list);
echo "\n total taxid count = " . $total_taxid_count . "\n\n";;
//====================================================================================
$i=1;
$bad=0;
//while( count($id_processed) != count($main_id_list) )
//{
    echo "-x- \n";    
    for ($i = $start; $i < $total_taxid_count; $i++)     
    {
        $taxid = $main_id_list[$i];
        //if(!in_array("$taxid", $id_processed))        
        //{                        
            
            //if(count($id_processed) % 10000 == 0)
            if($i % 10000 == 0) //working
            {   
                //start new file                
                if(isset($OUT))fclose($OUT);
                $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "/temp/worms_" . $file_number .".xml";
                $OUT = fopen($old_resource_path, "w+");            
                $file_number++;
            }            
                        
            // /*
            //if(process($taxid,$OUT))
            if($contents=process($taxid))            
            {
                //$id_processed[] = $taxid;
                echo " -ok- ";
                //new
                fwrite($OUT, $contents);
                //new
            }
            else
            {
                echo " -bad- "; $bad++;
            }
            // */                        
            echo $i+1 . ". of $total_taxid_count [bad=$bad] \n";            
            //echo $i+1 . ". " . count($id_processed) . " of " . $total_taxid_count . "\n";                        
        //}                
    }    
    /* working; only needed with while()
    $main_id_list = get_main_id_list();
    */
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

//while($i <= $total_taxid_count)
while(true)
{
    $i++; print "$i ";
    $file = CONTENT_RESOURCE_LOCAL_PATH . "/temp/worms_" . $i .".xml";
    $str = Functions::get_remote_file($file);
    if($str)
    {
        fwrite($OUT, $str);
        unlink($file);
    }            
    else break;    
    //new
    //if($i <= $total_taxid_count)unlink($file);    
}
print "\n not well-formed XML = $bad_id \n";
print "\n --end-- \n";
fclose($OUT);

$OUT = fopen("bad_id.txt", "w+");            
fwrite($OUT, $bad_id);fclose($OUT);


$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";

exit("\n\n Done processing.");
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################

//end
//====================================================================================
//start functions #################################################################################################
function process($id)
{   
    global $bad_id;
    
    $file = "http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=$id";
    //$file = "http://127.0.0.1/worms.xml";
    
    // http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=377972
    // http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=255100        
    // http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=247972     
    // http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=248002
    // http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=137115
    // http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=247983
    
    
    if($xml = Functions::get_hashed_response($file)){}
    else
    {
        $bad_id .= $id . ",";
        return false;
    }
    
    $contents = Functions::get_remote_file($file);
    if($contents)
    {
    	$pos1 = stripos($contents,"<taxon>");
    	$pos2 = stripos($contents,"</taxon>");			
    	if($pos1 != "" and $pos2 != "")
    	{
    		$contents = trim(substr($contents,$pos1,$pos2-$pos1+8));
            return $contents;
    	}
    }    
    return false;
}//end process() 
function get_main_id_list()
{
    $url=array();
    
    // /* comment this when debugging
    /*
    $url[]="http://127.0.0.1/mtce/WORMS/20090605/id/2007.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20090605/id/2008.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20090605/id/2009.xml";    
    
    $url[]="http://127.0.0.1/mtce/WORMS/20090819/id/2007.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20090819/id/2008.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20090819/id/2009.xml";
    
    $url[]="http://127.0.0.1/mtce/WORMS/20091016/id/2007.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20091016/id/2008.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20091016/id/2009.xml";
    
    $url[]="http://127.0.0.1/mtce/WORMS/20091112/id/2007.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20091112/id/2008.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20091112/id/2009.xml";    
    
    $url[]="http://127.0.0.1/mtce/WORMS/20100104/id/2007.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100104/id/2008.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100104/id/2009.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100104/id/2010.xml";        
    */

    $url[]="http://127.0.0.1/mtce/WORMS/20100226/id/2007.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100226/id/2008.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100226/id/2009.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100226/id/2010.xml";    

    $url[]="http://127.0.0.1/mtce/WORMS/20100519/id/2007.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100519/id/2008.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100519/id/2009.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100519/id/2010.xml";    

    $url[]="http://127.0.0.1/mtce/WORMS/20100716/id/2007.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100716/id/2008.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100716/id/2009.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100716/id/2010.xml";    

    $url[]="http://127.0.0.1/mtce/WORMS/20100806/id/2007.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100806/id/2008.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100806/id/2009.xml";
    $url[]="http://127.0.0.1/mtce/WORMS/20100806/id/2010.xml";    

    // */
    
    /* for testing
    $url[]="http://127.0.0.1/mtce/WORMS/20100104/test.xml";    
    */

    /* WORMS server can't render such requests online
    $url[]="http://www.marinespecies.org/aphia.php?p=eol&action=taxlist&startdate=19960101&enddate=20071231";
    $url[]="http://www.marinespecies.org/aphia.php?p=eol&action=taxlist&startdate=20080101&enddate=20081231";
    $url[]="http://www.marinespecies.org/aphia.php?p=eol&action=taxlist&startdate=20090101&enddate=20091231";
    $url[]="http://www.marinespecies.org/aphia.php?p=eol&action=taxlist&startdate=20100101&enddate=20101231";
    */
    
    /*
    $url[] = "http://services.eol.org/eol_php_code/update_resources/connectors/files/WORMS/2007.xml";
    $url[] = "http://services.eol.org/eol_php_code/update_resources/connectors/files/WORMS/2008.xml";
    $url[] = "http://services.eol.org/eol_php_code/update_resources/connectors/files/WORMS/2009.xml";
    $url[] = "http://services.eol.org/eol_php_code/update_resources/connectors/files/WORMS/2010.xml";
    */    
 
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

/*
function get_file_contents($url)
{
    $contents = "";
 	$handle = fopen($url, "r");	
	if ($handle)
	{	
		while (!feof($handle)){$contents .= fread($handle, 8192);}
		fclose($handle);				
    }
    else print "[error fopen] \n ";
    return $contents;
}
*/

?>