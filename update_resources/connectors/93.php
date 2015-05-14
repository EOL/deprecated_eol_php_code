<?php
/* Antarctic Invertebrates connector 
estimated execution time:   2-3 mins. without the reference
                            15 mins. with reference

Partner provided an XML service for their entire data. XML is not EOL-compliant. 
Connector reads the XML and generates the EOL-compliant XML.

...working but needs to be in a class API
*/

$timestart = microtime(1);

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];
$resource_id = 93;

$schema_taxa = array();
$used_taxa = array();

//Reference
$GLOBALS['cited_taxa_prefix'] = "http://invertebrates.si.edu/antiz/taxon_view.cfm?mode=bibliography&citation=";

/*
$reference_html = "http://localhost/eol_php_code/update_resources/connectors/files/AntarcticInvertebrates/bibliography.cfm.html";                       
*/
$reference_html = "http://pandanus.eol.org/public/content_partners/AntarcticInvertebrates/bibliography.cfm.html";
$arr = get_references($reference_html);
$arr_ref = $arr[0];
$arr_taxon_ref = $arr[1];

/*
$xml_url = "http://antiz.redmon.com/admin/taxon_descriptions.cfm?rows=5";
$xml_url = "http://antiz.redmon.com/admin/taxon_descriptions.cfm";
$xml_url = DOC_ROOT . "/update_resources/connectors/files/AntarcticInvertebrates/ai.xml";                       
*/
$xml_url = "http://pandanus.eol.org/public/content_partners/AntarcticInvertebrates/AI.xml";

if(!($xml = @simplexml_load_file($xml_url)))
{
    print "\n <a href='$url'>$url</a> not accessible"; exit;
}

$k=1;
foreach($xml->taxon as $rec)
{
    print "\n" . " $k of " . count($xml->taxon) . " " . $rec["name"] ;

    $dwc_ScientificName = "";
    $dwc_Genus = "";
    $dwc_Family = "";    
    
    if  (@$rec["rank"]=="Species")
    {   
        $dwc_ScientificName = trim($rec["name"]);        
        $dwc_Genus = substr($rec["name"],0,stripos($rec["name"]," "));
    }    
    else $dwc_ScientificName = trim($rec["name"]);           
    
    $taxon_identifier = "AI_" . $rec["ID"];
    $agent = get_agent($rec->original_description);        
    
    /* obsolete
    $source_url = "http://antiz.redmon.com/taxon_view.cfm?mode=advancedSearch&name=" . urlencode($dwc_ScientificName) . "&rank=&phylum=&match=substring&Submit=Search";
    */       
    $source_url = "http://invertebrates.si.edu/antiz/taxon_view.cfm?mode=advancedSearch&name=" . urlencode($dwc_ScientificName) . "&rank=&phylum=&match=substring&Submit=Search";                                                                                                                                         
    
    print "\n";    
    
    if(@$used_taxa[$taxon_identifier])
    {
        $taxon_parameters = $used_taxa[$taxon_identifier];
    }
    else
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = $taxon_identifier;        
        $taxon_parameters["family"] = $dwc_Family;
        $taxon_parameters["genus"] = $dwc_Genus;
        $taxon_parameters["scientificName"]= $dwc_ScientificName;        
        $taxon_parameters["source"] = $source_url;
                
        $taxon_parameters["dataObjects"]= array();        
        $used_taxa[$taxon_identifier] = $taxon_parameters;        

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
            
            $taxon_id = $rec["ID"];
            $taxon_ref = @$arr_taxon_ref["$taxon_id"];                        
            print" taxon_id = $taxon_id ";        
            
            $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $copyright_text, $license, $agent_name, $desc, "text", $source_url, $taxon_ref, $arr_ref);                   
            $taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_parameters);         
        }        
    }    
    
    /* start 2nd dataobject - image */         
    /* end second dataobject - image */          
    
    $used_taxa[$taxon_identifier] = $taxon_parameters;                
    $k++;
    //if($k == 3)break;  //debug - to limit no. of records
}

foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new \SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
if(!($OUT = fopen($old_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
  return;
}
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";

echo "\n\n Done processing.";
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################

function get_references($file)
{
    $str = Functions::get_remote_file($file);                
    
    $str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);    
    $str = str_replace(array("<nobr>", "</nobr>", "taxon_view.cfm?mode=bibliography&citation=","&#776;"), '', $str);            
    
    $str = str_ireplace('&#8216;' , "'", $str);//special char [�]
    $str = str_ireplace('&#8217;' , "'", $str);//special char [�]
    $str = str_ireplace('&#8220;' , '"', $str); //special char [�]
    $str = str_ireplace('&#8221;' , '"', $str); //special char [�]     
    $str = str_ireplace('&#8211;' , '-', $str);//special char [�]     
    $str = str_ireplace('&#769;' , "'", $str);//special char [?]         

    $str = str_ireplace('&amp;' , "and", $str);        
    $str=Functions::import_decode($str);    
    
    $str = str_ireplace('<p class="biblio">' , 'xxx', $str);    
    $str = str_ireplace('xxx' , "&arr[]=", $str);        
    $arr = array(); parse_str($str);            

    $arr_ref=array();     
    $arr_taxon_ref = array();    

    $i=1;
    foreach($arr as $r)
    {
        print"\n $i of " . count($arr) . "\n";
        if(is_numeric(stripos($r,'<a href="')))
        {
            if(preg_match("/<a href=\"(.*?)\">/", $r, $matches))$ref_num = $matches[1];            
            $file = $GLOBALS['cited_taxa_prefix'] . $ref_num;
            $str = Functions::get_remote_file($file);                                                
            
            //<input type="checkbox"
            $str = str_ireplace('<input type="checkbox"' , 'xxx<input type="checkbox"', $str);        
            $str = str_ireplace('xxx' , "&arr2[]=", $str);        
            $arr2 = array(); parse_str($str);            
            foreach($arr2 as $r2)
            {
                //onclick="reMap('8418','Chlamys gemmulata')"
                if(preg_match("/onclick=\"reMap(.*?)\"/", $r2, $matches))$sub_str = $matches[1];            
                    
                //('6577','Galiteuthis glacialis')
                if(preg_match("/\(\'(.*?)\'\,/", $sub_str, $matches))$taxon_id = $matches[1];
                if(preg_match("/\,\'(.*?)\'\)/", $sub_str, $matches))$taxon = $matches[1];
                print"[$taxon_id][$taxon]\n";

                //$arr_taxon_ref[$taxon_id][]=$ref_num;                
                $arr_taxon_ref["$taxon_id"][]=$ref_num;                
                
            }                                        
            $arr_ref[$ref_num]=$str = trim(str_ireplace('View cited taxa' , "", strip_tags($r,"<em>")));                        
            $i++;
            //if($i == 3)break;//debug - to limit no. of records
        }
    }    
    return array(0 => $arr_ref, 1 => $arr_taxon_ref);
}

function get_data_object($id, $created, $modified, $rightsHolder, $license, $agent_name ,$description, $type, $source_url, $taxon_ref, $arr_ref)
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
        $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);
        ///////////////////////////////////        
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";    
        $dataObjectParameters["mimeType"] = "text/html";        
    }
    
    $dataObjectParameters["description"] = $description;        
    $dataObjectParameters["created"] = $created;
    $dataObjectParameters["modified"] = $modified;    
    $dataObjectParameters["rightsHolder"] = $rightsHolder;            
    $dataObjectParameters["language"] = "en";    
    $dataObjectParameters["license"] = $license;        
    $dataObjectParameters["source"] = $source_url;
    ///////////////////////////////////
    if($taxon_ref)
    {
        foreach($taxon_ref as $ref_id)
        {
            $reference = $arr_ref[$ref_id];
            
            $dataObjectParameters["references"] = array();
            $referenceParameters = array();
            $referenceParameters["fullReference"] = trim(utf8_encode($reference));
            $href = $GLOBALS['cited_taxa_prefix'] . $ref_id;
            $referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "url" , "value" => $href));                                
            $references[] = new \SchemaReference($referenceParameters);
            $dataObjectParameters["references"] = $references;            
        }
    }
    ///////////////////////////////////    
    
    if($agent_name)    
    {
        $agentParameters = array();
        $agentParameters["role"] = "author";
        $agentParameters["fullName"] = $agent_name;
        $agents[] = new \SchemaAgent($agentParameters);
        $dataObjectParameters["agents"] = $agents;    
    }

    //another agent - project     
    $agent_name = "Smithsonian National Museum of Natural History - Antarctic Invertebrates";    
    $agentParameters = array();
    $agentParameters["role"] = "project";
    $agentParameters["fullName"] = $agent_name;
    $agentParameters["homepage"] = "http://antiz.redmon.com/index.cfm";
    $agents[] = new \SchemaAgent($agentParameters);
    $dataObjectParameters["agents"] = $agents;    
    
    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();    
    $audienceParameters = array();    
    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);    
    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);    
    return $dataObjectParameters;
}

function get_agent($str)
{   
    $str = trim(clean_str($str));
    $str = strip_tags($str);        
    $agent="";
    if(substr($str,strlen($str)-1) != ")") return "";    
    
    //get start pos of agent    
    $pos = find_pos_of_this_char_by_moving_backwards($str,"(");
    if($pos != "")
    {
        $agent = trim(substr($str,$pos,strlen($str)));    
                
        //get end pos of agent
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
    $arr = array("(" , ")");
    $str = str_replace($arr, '', $str);    
    return $str;
}
?>