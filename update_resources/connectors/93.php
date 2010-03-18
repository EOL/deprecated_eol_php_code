<?php
/* Antarctic Invertebrates connector */

include_once(dirname(__FILE__) . "/../../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];

$resource = new Resource(93); //93 exit($resource->id);

$wrap="\n";
//$wrap="<br>";

$schema_taxa = array();
$used_taxa = array();

/*
*/
$xml_url = "http://antiz.redmon.com/admin/taxon_descriptions.cfm?rows=5";
$xml_url = "http://127.0.0.1/ai.xml";
$xml_url = "http://antiz.redmon.com/admin/taxon_descriptions.cfm";

if(!($xml = @simplexml_load_file($xml_url)))
{
    print "$wrap <a href='$url'>$url</a> not accessible"; exit;
}

// loop
$k=0;
foreach($xml->taxon as $rec)
{
    print "$wrap" . $rec["name"];
    //print "$wrap" . $rec->original_description;    
    /*
    $dwc_Kingdom = trim($dwc->Kingdom);
    $dwc_Phylum = trim($dwc->Phylum);
    $dwc_Class = trim($dwc->Class);
    $dwc_Order = trim($dwc->Order);
    $dwc_Family = trim($dwc->Family);
    */


    $dwc_ScientificName = "";
    $dwc_Genus = "";
    $dwc_Family = "";

    
    if  (@$rec["rank"]=="Genus")//or @$rec["rank"]=="Subgenus"   
    {   $dwc_Genus = trim($rec["name"]);
        $dwc_ScientificName = trim($rec["name"]);
    }
    elseif  (@$rec["rank"]=="Species")//or @$rec["rank"]=="Subspecies"
    {   $dwc_ScientificName = trim($rec["name"]);
        $dwc_Genus = substr($rec["name"],0,stripos($rec["name"]," "));
    }    
    elseif  (@$rec["rank"]=="Family")//or @$rec["rank"]=="Subfamily"
    {   $dwc_ScientificName = trim($rec["name"]);
        $dwc_Family = trim($rec["name"]);
    }   
    else continue; 

    
    
    $taxon_identifier = "AI_" . $rec["ID"];
    $agent = get_agent($rec->original_description);        
    
    print "$wrap agent = [$agent]";
    $source_url = "http://antiz.redmon.com/taxon_view.cfm?mode=advancedSearch&name=" . urlencode($dwc_ScientificName) . "&rank=&phylum=&match=substring&Submit=Search";
    
    //print " -- <a href='$source_url'>url</a>";    
    print "$wrap";    
    
    if(@$used_taxa[$taxon_identifier])
    {
        $taxon_parameters = $used_taxa[$taxon_identifier];
    }
    else
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = $taxon_identifier;
        /*
        $taxon_parameters["kingdom"] = $dwc_Kingdom;
        $taxon_parameters["phylum"] = $dwc_Phylum;
        $taxon_parameters["class"] = $dwc_Class;
        $taxon_parameters["order"] = $dwc_Order;
        $taxon_parameters["family"] = $dwc_Family;
        */
        
        $taxon_parameters["family"] = $dwc_Family;
        $taxon_parameters["genus"] = $dwc_Genus;
        $taxon_parameters["scientificName"]= $dwc_ScientificName;        
        $taxon_parameters["source"] = $source_url;
                
        $taxon_parameters["dataObjects"]= array();        
        $used_taxa[$taxon_identifier] = $taxon_parameters;        

        // /* start first dataobject - text 
        if(isset($rec->original_description))
        {    
            $dcterms_created="";
            $dcterms_modified="";
            $copyright_text="";
        
            $dc_identifier = $taxon_identifier . "_GenDesc";
            $dc_source = $source_url;
            $agent_name = $agent;
            $license = "http://creativecommons.org/licenses/by-nc/3.0/";    
            $desc = $rec->original_description;
            $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $copyright_text, $license, $agent_name, $desc, "text", $source_url);       
            $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);         
        }
        // end first dataobject - text */             
        
    }

    /* start 2nd dataobject - image */         
    /* end second dataobject - image */      
    
    $used_taxa[$taxon_identifier] = $taxon_parameters;                
    //$k++;if($k == 3)break;    //debug; to limit no. of records
}

/*
print "<pre>";
print_r($used_taxa);
print "</pre>";
exit;
*/

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

function get_data_object($id, $created, $modified, $rightsHolder, $license, $agent_name ,$description, $type, $source_url)
{
    $dataObjectParameters = array();
    
    if($type == "text")
    {   
        $dataObjectParameters["identifier"] = "txt_" . $id;    
        $dataObjectParameters["title"] = "Description";
        ///////////////////////////////////    
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
        ///////////////////////////////////        
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";    
        $dataObjectParameters["mimeType"] = "text/html";        
    }
    else
    {
        /* not used for this provider
        $dataObjectParameters["identifier"] = "img_" . $id;    
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $dataObjectParameters["mimeType"] = "image/jpeg";
        $dataObjectParameters["thumbnailURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=thumb";
        $dataObjectParameters["mediaURL"] = "http://www.morphbank.net/?id=" . $id . "&imgType=jpg";        
        */
    }
    
    $dataObjectParameters["description"] = $description;        
    $dataObjectParameters["created"] = $created;
    $dataObjectParameters["modified"] = $modified;    
    $dataObjectParameters["rightsHolder"] = $rightsHolder;            
    $dataObjectParameters["language"] = "en";    
    $dataObjectParameters["license"] = $license;        
    $dataObjectParameters["source"] = $source_url;
    ///////////////////////////////////
    if($agent_name)    
    {
        $agentParameters = array();
        $agentParameters["role"] = "author";
        $agentParameters["fullName"] = $agent_name;
        $agents[] = new SchemaAgent($agentParameters);
        $dataObjectParameters["agents"] = $agents;    
    }
     
        $agent_name = "Smithsonian National Museum of Natural History - Antarctic Invertebrates";    
        $agentParameters = array();
        $agentParameters["role"] = "project";
        $agentParameters["fullName"] = $agent_name;
        $agentParameters["homepage"] = "http://antiz.redmon.com/index.cfm";
        
        
        $agents[] = new SchemaAgent($agentParameters);
        $dataObjectParameters["agents"] = $agents;    

    
    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();    
    $audienceParameters = array();    
    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    return $dataObjectParameters;
}

function get_agent($str)
{   $str = trim(clean_str($str));
    $str = strip_tags($str);    
    
    $agent="";
    if(substr($str,strlen($str)-1) != ")") return "";
    
    
    //get start pos of agent    
    $pos = find_pos_of_this_char_by_moving_backwards($str,"(");
    if($pos != "")
    {
        //$agent = substr($str,$pos,strlen($str)-$pos);    
        $agent = trim(substr($str,$pos,strlen($str)));    
        
        
        //get end pos of agent
        //$pos = find_pos_of_this_char_by_moving_backwards($agent,",");
        $pos = find_pos_of_this_char_by_moving_backwards($agent,")");
        if($pos != "")$agent = substr($agent,0,$pos+1);                

        $agent = str_replace("e.g.,", '', $agent);    
    
        //remove 1st char if ';' or '"' or '('
        if(in_array(substr($agent,0,1),array(';' , '"'))) $agent = trim(substr($agent,1,strlen($agent)));
    
    }
    else $agent = "";      
    
    
    $agent = trim(remove_chars($agent));        
    return $agent;
}
function find_pos_of_this_char_by_moving_backwards($str,$char)
{   /* find pos of char by moving backwards starting from the end of string */
    $new_char="";
    $pos = strlen($str)-1;
    while ($new_char != $char and $pos > 0)
    {
        $new_char = substr($str,$pos,1);
        $pos--;
    }
    return $pos;
}
function clean_str($str)
{   $str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);			
    return $str;
}
function remove_chars($str) 
{   
    //$arr = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0" , ":" , "(" , ")");
    $arr = array("(" , ")");
  	$str = str_replace($arr, '', $str);    
  	return $str;
}

?>