<?php
exit;
/* Antarctic Invertebrates connector 
estimated execution time: 2-3 mins. without the reference; 15 mins. with reference.

Partner provided an XML service for their entire data. XML is not EOL-compliant. 
Connector reads the XML and generates the EOL-compliant XML.

*/
$timestart = microtime(1);

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];
//$resource = new Resource(93); //93 exit($resource->id);
$resource_id = 93;
$wrap="\n";
//$wrap="<br>";

$schema_taxa = array();
$used_taxa = array();

//Reference
$cited_taxa_prefix = "http://invertebrates.si.edu/antiz/taxon_view.cfm?mode=bibliography&citation=";
$reference_html = "files/AntarcticInvertebrates/bibliography.cfm.html";

$arr = get_references($reference_html);
$arr_ref = $arr[0];
$arr_taxon_ref = $arr[1];
//print"<pre>";print_r($arr_ref);print"</pre>";
//print"<pre>";print_r($arr_taxon_ref);print"</pre>";        

/*
$xml_url = "http://antiz.redmon.com/admin/taxon_descriptions.cfm?rows=5";
$xml_url = "http://127.0.0.1/ai.xml";
*/
$xml_url = "http://antiz.redmon.com/admin/taxon_descriptions.cfm";
$xml_url = "http://127.0.0.1/ai.xml";

if(!($xml = @simplexml_load_file($xml_url)))
{
    print "$wrap <a href='$url'>$url</a> not accessible"; exit;
}

// loop
$k=1;
foreach($xml->taxon as $rec)
{
    print "$wrap" . " $k of " . count($xml->taxon) . " " . $rec["name"] ;
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
    
    //print "$wrap agent = [$agent]";
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
            
            //ditox            
            $taxon_id = $rec["ID"];
            $taxon_ref = @$arr_taxon_ref["$taxon_id"];                        
            print" taxon_id = $taxon_id ";
        
            //exit;
            
            $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $copyright_text, $license, $agent_name, $desc, "text", $source_url, $taxon_ref);       
            
            $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);         
        }
        // end first dataobject - text */             
        
    }    
    /* start 2nd dataobject - image */         
    /* end second dataobject - image */          
    $used_taxa[$taxon_identifier] = $taxon_parameters;                
    $k++;
    //if($k == 10)break;  //debug - to limit no. of records
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
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";

exit("\n\n Done processing.");
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################

function get_references($file)
{
    global $cited_taxa_prefix;
    
    //$file = "files/AntarcticInvertebrates/bibliography.cfm.html";
    //$file = "files/AntarcticInvertebrates/temp.html";

    $str = Functions::get_remote_file($file);            
    $str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);	
    $str = str_replace(array("<nobr>", "</nobr>", "taxon_view.cfm?mode=bibliography&citation=","&#776;"), '', $str);	        
    
    $str = str_ireplace('&#8216;' , "'", $str);//special char [‘]
    $str = str_ireplace('&#8217;' , "'", $str);//special char [’]
    $str = str_ireplace('&#8220;' , '"', $str); //special char [“]
    $str = str_ireplace('&#8221;' , '"', $str); //special char [”]     
    $str = str_ireplace('&#8211;' , '-', $str);//special char [–]     
    $str = str_ireplace('&#769;' , "'", $str);//special char [?]         

    //$str = str_ireplace('&amp;' , "&#38;", $str);	    
    $str = str_ireplace('&amp;' , "and", $str);	    
    $str=Functions::import_decode($str);    
    
    $str = str_ireplace('<p class="biblio">' , 'xxx', $str);	
    $str = str_ireplace('xxx' , "&arr[]=", $str);	    
    $arr = array(); parse_str($str);	        

    //print"<pre>";print_r($arr);print"</pre>";    
    //exit;

    $arr_ref=array();     
    $arr_taxon_ref = array();    

    $i=1;
    foreach($arr as $r)
    {
        print"\n $i of " . count($arr) . "\n";
        if(is_numeric(stripos($r,'<a href="')))
        {
            if(preg_match("/<a href=\"(.*?)\">/", $r, $matches))$ref_num = $matches[1];
            //print "$ref_num - $r <br>";                        
            //print "ref_id [$ref_num] ";                        
            
            $file = $cited_taxa_prefix . $ref_num;
            $str = Functions::get_remote_file($file);                                                
            
            //<input type="checkbox"
            $str = str_ireplace('<input type="checkbox"' , 'xxx<input type="checkbox"', $str);	    
            $str = str_ireplace('xxx' , "&arr2[]=", $str);	    
            $arr2 = array(); parse_str($str);	        
            foreach($arr2 as $r2)
            {
                //onclick="reMap('8418','Chlamys gemmulata')"
                if(preg_match("/onclick=\"reMap(.*?)\"/", $r2, $matches))$sub_str = $matches[1];            
                //print"<pre>";print_r($matches);print"</pre>";
                //exit;   
            
                //('6577','Galiteuthis glacialis')
                if(preg_match("/\(\'(.*?)\'\,/", $sub_str, $matches))$taxon_id = $matches[1];
                if(preg_match("/\,\'(.*?)\'\)/", $sub_str, $matches))$taxon = $matches[1];
                print"[$taxon_id][$taxon]\n";

                //$arr_taxon_ref[$taxon_id][]=$ref_num;                
                $arr_taxon_ref["$taxon_id"][]=$ref_num;                
                
            }                                        
            $arr_ref[$ref_num]=$str = trim(str_ireplace('View cited taxa' , "", strip_tags($r,"<em>")));                        
            $i++;
            //if($i == 1)break;//debug - to limit no. of records
        }
    }    
    //print"<pre>";print_r($arr_ref);print"</pre>";
    //print"<pre>";print_r($arr_taxon_ref);print"</pre>";        
    //exit;    
    return array(0 => $arr_ref, 1 => $arr_taxon_ref);
}

function get_data_object($id, $created, $modified, $rightsHolder, $license, $agent_name ,$description, $type, $source_url, $taxon_ref)
{
    global $arr_ref;    
    global $cited_taxa_prefix;

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
    if($taxon_ref)
    {
        foreach($taxon_ref as $ref_id)
        {
            $reference = $arr_ref[$ref_id];
            
            $dataObjectParameters["references"] = array();
            $referenceParameters = array();
            $referenceParameters["fullReference"] = trim(utf8_encode($reference));
            $href = $cited_taxa_prefix . $ref_id;
            $referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "url" , "value" => $href));                                
            $references[] = new SchemaReference($referenceParameters);
            $dataObjectParameters["references"] = $references;            
        }
    }
    ///////////////////////////////////    

    /* working
    $dataObjectParameters["references"] = array();
    $referenceParameters = array();
    $referenceParameters["fullReference"] = "test reference";                        
    $href="http://www.fishbase.org";
    $referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "url" , "value" => $href));                                
    $references[] = new SchemaReference($referenceParameters);
    $dataObjectParameters["references"] = $references;            
    */
    
    if($agent_name)    
    {
        $agentParameters = array();
        $agentParameters["role"] = "author";
        $agentParameters["fullName"] = $agent_name;
        $agents[] = new SchemaAgent($agentParameters);
        $dataObjectParameters["agents"] = $agents;    
    }

    //another agent - project     
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