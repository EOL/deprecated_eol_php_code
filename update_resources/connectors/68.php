#!/usr/local/bin/php
<?php
//connector for Duth Species Catalogue
set_time_limit(0);
//define("ENVIRONMENT", "development");
define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

// /*
$start=0;
$file_number=1;
// */

//only on local; to be deleted before going into production
/*
    $mysqli->truncate_tables("development");
    Functions::load_fixtures("development");
*/
/*
    as of       id      bad 
    Jan23 2010  4744    108
    
*/

$resource = new Resource(68); //exit("[$resource->id]");
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
    //$taxid = "000464941632";//debug Acipitter
    //$taxid = "000000016023";//Agrilus planipennis Fairmaire, 1888 

    if($i % 10000 == 0) //working
    {   
        //start new file                
        if(isset($OUT))fclose($OUT);
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "/dutch_" . $file_number .".xml";
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
        echo " -bad- "; 
        $bad++;
    }    
    //print"<hr>[$contents]<hr>";    
    echo $i+1 . ". of $total_taxid_count [bad=$bad] \n";            
    /*
    if($i==0)$i=$total_taxid_count;//debug to limit the loop; $i==0 just 1 taxa to process; $i==1 2 taxa to process
    */
}    
//====================================================================================
$str = "</response>";fwrite($OUT, $str);fclose($OUT);
//====================================================================================
//start compiling all individual xml 
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
    $i++; print "$i ";
    $file = CONTENT_RESOURCE_LOCAL_PATH . "/dutch_" . $i .".xml";
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
print "\n --end-- \n";
fclose($OUT);

//====================================================================================
//start functions #################################################################################################
function process($id)
{   
    //global $OUT;        
    $file = "http://www.nederlandsesoorten.nl/get?site=nlsr&view=nlsr&page_alias=conceptcard&cid=$id&version=EOL";
    //       http://www.nederlandsesoorten.nl/get?site=nlsr&view=nlsr&page_alias=conceptcard&cid=0AHCYFBQBTMT&version=EOL
    //       http://www.nederlandsesoorten.nl/get?site=nlsr&view=nlsr&page_alias=conceptcard&cid=000457094381&version=eol
    
    //print"<hr><a href='$file'>$file</a>";
    
    
    /*
    http://www.nederlandsesoorten.nl/get?site=nlsr&view=nlsr&page_alias=conceptcard&cid=0AHCYFBQBTMT&version=EOL
    http://www.nederlandsesoorten.nl/get?site=nlsr&view=nlsr&page_alias=conceptcard&cid=000457094381&version=eol
    */    

    //start process reference
        $ref="";
        if($xml = Functions::get_hashed_response($file))
        {   
            foreach($xml->taxon as $taxon)
            {
                foreach($taxon->dataObject as $dobj)
                {
                    $t_dc = $dobj->children("http://purl.org/dc/elements/1.1/");        
                    $desc = trim($t_dc->description);
                    if  (   strpos($desc,'Literatuur') != ""   and
                            $dobj->subject == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription"    
                        )
                    {
                        //print"<hr>$desc";
                        $desc = clean_str($desc);
                        $desc = "---" . str_ireplace("&amp;", "xxx", $desc);                        
                	    $beg='---';$end1="</p><h1>"; $end2="173xxx";			
                    	$desc = parse_html($desc,$beg,$end1,$end2,$end2,$end2,"",false);	//str = the html block        
                        
                        //print"<hr>$desc<hr>";

                        $desc = str_ireplace('<p>' , "&arr[]=", $desc);	
                        $arr=array();	
                        parse_str($desc);
                        
                        $ref = process_array($arr);
                        //print "<pre>";print($ref);print "</pre>";//exit;                                
                    }                  
                    //else print "[x]";  //not Literatuur
                }
            }
        }    
    //end process reference
    //exit("ditox");

    set_time_limit(0);
    $contents = Functions::get_remote_file($file);
    if($ref != "")
    {
        //insert the $ref inside the XML
        $pos = strpos($contents,'<dataObject>');
        $contents = substr_replace($contents, $ref, $pos,0) ;                
        $contents = str_ireplace("<reference></reference>", "", $contents);//remove blank ref        
    }
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
    //$url[] = "http://128.128.175.77/mtce/DutchSpeciesCatalogue/DutchSpeciesCatalogueIDs.xml";
    //$url[] = "http://services.eol.org/eol_php_code/update_resources/connectors/files/DutchSpeciesCatalogue/DutchSpeciesCatalogueIDs.xml";    
    $url[] = "http://www.nederlandsesoorten.nl/eol/EolList.xml";
    //print_r($url);exit;
 
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
            $no_of_taxdetail = count($xml->id);
            foreach($xml->id as $id)
            {
                $temp = $id;
                $arr["$temp"]=true;
                $j++; $jj++;
            }    
        }
        echo "\n" . $i+1 . " of " . $no_of_urls . " URLs | taxid count = " . $j . "\n";     
    }
    $arr = array_keys($arr);    
    //print_r($arr);exit;    
    return $arr;
}//get_main_id_list()


function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL,$exit_on_first_match=false)	//str = the html block
{
    //PRINT "[$all]"; exit;
	$beg_len = strlen(trim($beg));
	$end1_len = strlen(trim($end1));
	$end2_len = strlen(trim($end2));
	$end3_len = strlen(trim($end3));	
	$end4_len = strlen(trim($end4));		
	//print "[[$str]]";

	$str = trim($str); 	
	$str = $str . "|||";	
	$len = strlen($str);	
	$arr = array(); $k=0;	
	for ($i = 0; $i < $len; $i++) 
	{
        if(strtolower(substr($str,$i,$beg_len)) == strtolower($beg))
		{	
			$i=$i+$beg_len;
			$pos1 = $i;			
			//print substr($str,$i,10) . "<br>";									
			$cont = 'y';
			while($cont == 'y')
			{
				if(	substr($str,$i,$end1_len) == $end1 or 
					substr($str,$i,$end2_len) == $end2 or 
					substr($str,$i,$end3_len) == $end3 or 
					substr($str,$i,$end4_len) == $end4 or 
					substr($str,$i,3) == '|||' )
				{
					$pos2 = $i - 1; 					
					$cont = 'n';					
					$arr[$k] = substr($str,$pos1,$pos2-$pos1+1);																				
					//print "$arr[$k] $wrap";					                    
					$k++;
				}
				$i++;
			}//end while
			$i--;			
            
            //start exit on first occurrence of $beg
            if($exit_on_first_match)break;
            //end exit on first occurrence of $beg
            
		}		
	}//end outer loop
    if($all == "")	
    {
        $id='';
	    for ($j = 0; $j < count($arr); $j++){$id = $arr[$j];}		
        return $id;
    }
    elseif($all == "all") return $arr;	
}//end function

function process_array($arr)
{
    $ref="";
    for ($i = 0; $i < count($arr); $i++) 
    {           
        $str = str_ireplace("xxx", "&amp;", $arr[$i]);        
        //$str = strip_tags($str,'<em><i>');
        $str = strip_tags($str);
        //$ref .= "&lt;reference&gt;$str&lt;/reference&gt;";        
        $ref .= "<reference>$str</reference>";        
    }
    //return $arr;    
    return $ref;
}
function clean_str($str)
{    
    $str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);			
    return $str;
}
?>