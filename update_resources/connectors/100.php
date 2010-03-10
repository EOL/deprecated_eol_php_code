<?php
//connector for CONABIO

set_time_limit(0);
ini_set('memory_limit','3500M');
//define("ENVIRONMENT", "development");

//define("ENVIRONMENT", "slave_32");

$GLOBALS['ENV_NAME'] = 'slave_32';


//define("MYSQL_DEBUG", false);
//define("DEBUG", false);

include_once(dirname(__FILE__) . "/../../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];

$bad_id=""; //not well formed XML
// /*
$start=0;
$file_number=1;
// */

//only on local; to be deleted before going into production
/*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
*/
$resource = new Resource(100);//CONABIO
//exit("[$resource->id]");



$main_count=0;
//====================================================================================
$main_id_list = array();
//$id_processed = array();
$main_id_list = get_main_id_list();
$total_taxid_count = count($main_id_list);
echo "\n total taxid count = " . $total_taxid_count . "\n\n";;
//exit;
//====================================================================================
$i=1;
$bad=0;
    echo "-x- \n";    
    for ($i = $start; $i < $total_taxid_count; $i++)     
    {
        $taxid = $main_id_list[$i];

            if($i % 10000 == 0) //working
            {   
                //start new file                
                if(isset($OUT))fclose($OUT);
                $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "/temp/worms_" . $file_number .".xml";
                $OUT = fopen($old_resource_path, "w+");            
                $file_number++;
            }                        
            if($contents=process($taxid))            
            {
                echo " -ok- ";
                fwrite($OUT, $contents);
            }
            else
            {
                echo " -bad- "; $bad++;
            }
            echo $i+1 . ". of $total_taxid_count [bad=$bad] \n";            
    }    


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
if($bad_id != "")print "\n not well-formed XML = $bad_id \n";
print "\n --end-- \n";
fclose($OUT);

$OUT = fopen("bad_id.txt", "w+");            
fwrite($OUT, $bad_id);fclose($OUT);

//end
//====================================================================================
//start functions #################################################################################################
function process($id)
{   
    global $bad_id;
    
    $file = "http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=$id";
    $file = $id;    
    
    set_time_limit(0);
    if($xml = Functions::get_hashed_response($file)){}
    else
    {
        $bad_id .= $id . ",";
        return false;
    }
    
    $contents = Functions::get_remote_file($file);
    //$contents = get_file_contents($file);
    if($contents)
    {
    	$pos1 = stripos($contents,"<taxon>");
    	$pos2 = stripos($contents,"</taxon>");			
    	if($pos1 != "" and $pos2 != "")
    	{
    		$contents = trim(substr($contents,$pos1,$pos2-$pos1+8));
            //fwrite($OUT, $contents);
            return $contents;
            //return true;
    	}
    }    
    return false;
}//end process() 
function get_main_id_list()
{

    
    /*
    $url[] = "http://services.eol.org/eol_php_code/update_resources/connectors/files/WORMS/2007.xml";
    $url[] = "http://services.eol.org/eol_php_code/update_resources/connectors/files/WORMS/2008.xml";
    $url[] = "http://services.eol.org/eol_php_code/update_resources/connectors/files/WORMS/2009.xml";
    $url[] = "http://services.eol.org/eol_php_code/update_resources/connectors/files/WORMS/2010.xml";
    */    
    
    $url[] = "http://conabioweb.conabio.gob.mx/xmleol/EolList.xml";
    
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
            foreach($xml->id as $taxdetail)
            {
                $temp = @$taxdetail;
                echo "\n" . $temp;
                $arr["$temp"]=true;
                $j++; $jj++;
            }    
        }
        echo " \n" . $i+1 . " of " . $no_of_urls . " URLs | taxid count = " . $j . "\n";     
    }
    print " \n";
    $arr = array_keys($arr);
    return $arr;
}//get_main_id_list()

/*
function get_file_contents($url)
{
    $contents = "";
    set_time_limit(0);
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