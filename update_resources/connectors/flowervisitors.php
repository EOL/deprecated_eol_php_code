<?php
//#!/usr/local/bin/php

$providers = array( 0 => array( "url" => dirname(__FILE__) . "/files/BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml"      , "active" => 0),
                    1 => array( "url" => dirname(__FILE__) . "/files/Zootaxa_986_Hamilton_taXMLit_v4-03-UTF8.xml" , "active" => 0),
                    2 => array( "url" => "http://pandanus.eol.org/public/BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml"  , "active" => 1)                    
                  );

/* flowervisitors connector */

//exit;
define("ENVIRONMENT", "development");
//define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];

 /*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
 */

$resource = new Resource(1); //exit($resource->id);


$schema_taxa = array();
$used_taxa = array();


$file = "http://flowervisitors.info/index.htm";
$str = Functions::get_remote_file($file);
$str = clean_str($str);

//$str = str_ireplace('×' , "", $str);    //for Vernonia × illinoensis (Illinois Ironweed)
$str = str_ireplace('&times;' , "", $str);    //for Vernonia × illinoensis (Illinois Ironweed)


$pos = stripos($str,"<!-- Google CSE Search Box Ends -->");
$str = trim(substr($str,$pos+35,strlen($str)));

$str = str_ireplace('<BR>' , "<br>", $str);	

$str = strip_tags($str, '<br><a>');
$str = "<br>" . $str;

$str = str_ireplace('<br><br>' , "&arr[]=", $str);	
$str = str_ireplace('<br>' , "&arr[]=", $str);	
//$str = str_ireplace('.htm"'  , "&", $str);
$arr=array();	
parse_str($str);	
print "after parse_str recs = " . count($arr) . "<hr>";	
//print_r($arr);

//print $str;
$i=0;
foreach($arr as $species)
{
    $i++;
    //$species = clean_str($species);
    print "{$species}";       
    /* <A HREF="plants/velvetleaf.htm" NAME="velvetleaf">Abutilon theophrastii (Velvet Leaf)</A> */
    $sciname="";$commonname="";$url="";    
    $beg='HREF="'; $end1='" NAME'; $end2="173xxx";    $url = "http://flowervisitors.info/" . trim(parse_html($species,$beg,$end1,$end2,$end2,$end2,""));    

    $species = strip_tags($species);            
    $species = "xxx" . $species;    
    
    $beg='('; $end1=')'; $end2="173xxx";    $commonname = parse_html($species,$beg,$end1,$end2,$end2,$end2,"");
    $beg='xxx'; $end1='('; $end2="173xxx";    $sciname = trim(parse_html($species,$beg,$end1,$end2,$end2,$end2,""));
    
    //print "[$sciname][$commonname][$url]";
    //print "<br>";     
    //===========================================================    
    $dwc_Genus = substr($sciname,0,stripos($sciname," "));
    $dwc_ScientificName = $sciname;
    $taxon_identifier = str_replace(" ", "_", $dwc_ScientificName);        
    
    if(@$used_taxa[$taxon_identifier])
    {
        $taxon_parameters = $used_taxa[$taxon_identifier];
    }
    else
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = $taxon_identifier;
        $taxon_parameters["genus"] = $dwc_Genus;
        $taxon_parameters["scientificName"]= $dwc_ScientificName;        
        $taxon_parameters["source"] = $url;        
        $taxon_parameters["commonNames"] = array();
        $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => $commonname, "language" => "en"));
        $taxon_parameters["dataObjects"]= array();        
        $used_taxa[$taxon_identifier] = $taxon_parameters;
    }     
    
    //start text dataobject
    $str = Functions::get_remote_file($url);
    
    //start get title
    $title="";
    $beg='</TITLE>'; $end1='<HR'; $end2="173xxx";    
    $title = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,""));            
    $title = str_ireplace('<BR>' , " ", $title);        
    $title = trim(strip_tags($title)) . " (<i>$dwc_ScientificName</i>)";
    print "[$title]"; //exit;
    //end get title

    $str = clean_str($str);        

    //start get desc            
    $desc="";
    $beg='<BLOCKQUOTE>'; $end1='</BLOCKQUOTE>'; $end2="173xxx";    
    $desc = trim(parse_html($str,$beg,$end1,$end2,$end2,$end2,""));            
    $desc = strip_tags($desc,"<br><p><b><i>");            
    //print "<hr>$desc";exit;
    //end get desc
    
    $dc_identifier = "txt_" . $taxon_identifier;
    /*
       
    */

    $data_object_parameters = get_data_object($dc_identifier, $desc, $title, $url);       
    $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     


    //end text dataobject            
    
    $used_taxa[$taxon_identifier] = $taxon_parameters;            
        
    
}//main loop


foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---

function get_data_object($id, $description, $title, $url)
{
    $type="text";
    
    $dataObjectParameters = array();
    
    if($type == "text")
    {   
        $dataObjectParameters["identifier"] = $id;    
        $dataObjectParameters["title"] = $title;
        ///////////////////////////////////    
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations";
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
        ///////////////////////////////////        
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";    
        $dataObjectParameters["mimeType"] = "text/html";        
    }
    else
    {
        $dataObjectParameters["identifier"] = "img_" . $id;    
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $dataObjectParameters["mimeType"] = "image/jpeg";
        $dataObjectParameters["thumbnailURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=thumb";
        $dataObjectParameters["mediaURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=jpg";        
    }


    $dataObjectParameters["description"] = $description;        
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;    
    
    $dataObjectParameters["language"] = "en";    
    
    $dataObjectParameters["source"] = $url;
    

    $dataObjectParameters["rights"] = "Copyright © 2002-2009 by Dr. John Hilty";
    $dataObjectParameters["rightsHolder"] = "John Hilty";
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by-nc/3.0/";

    
    ///////////////////////////////////
    $agentParameters = array();
    $agentParameters["homepage"] = "http://flowervisitors.info/";
    $agentParameters["role"] = "source";
    $agentParameters["fullName"] = "John Hilty";
    $agents[] = new SchemaAgent($agentParameters);
    $dataObjectParameters["agents"] = $agents;    
    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();        
    $audienceParameters = array();      
    $audienceParameters["label"] = "Expert users";      $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    $audienceParameters["label"] = "General public";    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    return $dataObjectParameters;
}

function clean_str($str)
{    
    $str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);			
    //$str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '#', $str);			
    // this line counts how many # as num, and repeats this char in num times, then replaces these chars with just 1 space ' ' 
    //$str = str_replace(str_repeat("#", substr_count($str, '#')), ' ', $str);
    return $str;
}
function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL)	//str = the html block
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
		if(substr($str,$i,$beg_len) == $beg)
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

?>