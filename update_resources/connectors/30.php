<?php

define('USING_SPM', true);
include_once(dirname(__FILE__) . "/../../config/environment.php");
// require_vendor('rdfapi-php');
require_vendor('rdf');




$download_cache_path = DOC_ROOT . "temp/plazi.xml";
$new_resource_path = DOC_ROOT . "temp/30.xml";

$new_resource_xml = Functions::get_remote_file("http://plazi.cs.umb.edu/exist/rest/db/taxonx_docs");

if(!($OUT = fopen($new_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$new_resource_path);
  return;
}
fwrite($OUT, $new_resource_xml);
fclose($OUT);

unset($new_resource_xml);



$prefix = "http://plazi.cs.umb.edu/exist/rest/db/taxonx_docs/getSPM.xq?render=xhtml&description=broad&associations=no&doc=";
$all_taxa = array();
$file_names = array();

$xml = simplexml_load_file($new_resource_path);
$xml_exist = $xml->children("http://exist.sourceforge.net/NS/exist");
foreach(@$xml_exist->collection as $collection)
{
    $collection_exist = $collection->children("http://exist.sourceforge.net/NS/exist");
    foreach(@$collection_exist->resource as $resource)
    {
        $attributes = array();
        foreach($resource->attributes() as $a => $b) $attributes[$a] = $b;
        
        $file_names[trim($attributes["name"])] = 1;
    }
}

$start = false;
$i = 0;
krsort($file_names);
foreach($file_names as $file_name => $v)
{
    $i++;
    
    //if($file_name!="5959_tx.xml") continue;
    //if($file_name!="2006_Huveneers_gg1_tx.xml") continue;
    
    echo "$file_name<br>\n";
    
    $url = $prefix . $file_name;
    $file_contents = Functions::get_remote_file($url);
    if(!$file_contents) echo "downloading failed\n";
    $file_contents = str_replace("<xhtml:p xmlns:xhtml=\"http://www.w3.org/1999/xhtml\">", htmlspecialchars("<p>"), $file_contents);
    $file_contents = str_replace("</xhtml:p>", htmlspecialchars("</p>"), $file_contents);
    
    if(!($OUT = fopen($download_cache_path, "w+")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$download_cache_path);
      return;
    }
    fwrite($OUT, $file_contents);
    fclose($OUT);
    
    if(filesize($download_cache_path))
    {
        clearstatcache();
        echo "$file_name - ".filesize($download_cache_path)."<br>\n";
        echo "<hr>Parsing Document $file_name<hr>\n";
        
        process_file($download_cache_path, $url);
        
        echo "Processed $file_name\n";
    }
}



if(!($OUT = fopen($new_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$new_resource_path);
  return;
}
fwrite($OUT, serialize($all_taxa));
fclose($OUT);

shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/helpers/plazi_step_two.php");

shell_exec("rm -f ". DOC_ROOT . "temp/30.xml");
shell_exec("rm -f ". DOC_ROOT . "temp/downloaded_rdf.rdf");
shell_exec("rm -f ". DOC_ROOT . "temp/plazi.xml");



function process_file($path, $url)
{
    try { $document = new RDFDocument($path, $url); }
    catch (Exception $e)
    {
        //Functions::catch_exception($e);
        return array();
    }
    global $all_taxa;
    
    //$document->show();
    
    $authorship = "";
    $publication_citation = "";
    $source_url = "";
    $actors = $document->get_resources("tbase:Actor");
    foreach($actors as $actor)
    {
        if($citation = $actor->get_resource("tcom:publishedInCitation", "RDFDocumentElement"))
        {
            $publication_fields = array();
            if($field = @trim($citation->get_literal("tpcit:authorship")))
            {
                $publication_fields[] = $field;
                $authorship = $field;
            }
            if($field = @trim($citation->get_literal("tpcit:datePublished"))) $publication_fields[] = $field;
            if($field = @trim($citation->get_literal("tpcit:title"))) $publication_fields[] = $field;
            if($field = @trim($citation->get_literal("tpcit:parentPublicationString"))) $publication_fields[] = $field;
            if($field = @trim($citation->get_literal("tpcit:pages"))) $publication_fields[] = "pp. " . $field;
            if($field = @trim($citation->get_literal("tpcit:volume"))) $publication_fields[] = "vol. " . $field;
            if($field = @trim($citation->get_resource_reference("tpcit:url"))) $source_url = $field;
            
            $publication_citation = implode(", ", $publication_fields);
            break;
        }
    }
    
    $species_profiles = $document->get_resources("spm:SpeciesProfileModel");
    foreach($species_profiles as $profile)
    {
        //$rights = trim($profile->get_literal("dwc:rights"));
        //if(!$rights || preg_match("/^public domain/i", $rights, $arr)) $rights = "http://creativecommons.org/licenses/publicdomain/";
        $rights = "not applicable";
        
        $taxon_parameters = array();
        $concepts = $profile->get_resources("spm:aboutTaxon", "TaxonConcept");
        foreach($concepts as $concept)
        {
            $taxon_name_string = trim($concept->get_literal("tc:nameString"));
            $taxon_title = trim($concept->get_literal("dc:title"));
            if(!$taxon_title) $taxon_title = $taxon_name_string;
            if(!$taxon_title) break;
            
            echo "$taxon_title\n";
            
            $taxon_parameters = array(
                                        "identifier"        => $concept->identifier(),
                                        "scientificName"    => htmlspecialchars_decode($taxon_title));
            if($publication_citation) $taxon_parameters["references"][] = array("fullReference" => $publication_citation);
            if($source_url) $taxon_parameters["source"] = $source_url;
            elseif(preg_match("/^([".UPPER."][".LOWER."]+) ([".LOWER."]+)$/", $taxon_name_string, $arr)) $taxon_parameters["source"] = "http://plazi.org:8080/GgSRS/search?118273105.isNomenclature=1&118273105.exactMatch=1&118273105.genus=".$arr[1]."&118273105.species=".$arr[2];
            break;
        }
        if(!$taxon_parameters) continue;
        
        $taxon_parameters["dataObjects"] = array();
        
        $info_items = $profile->get_resources("spm:hasInformation", "InfoItem");
        foreach($info_items as $info_item)
        {
            $info_item_subject = trim($info_item->get_type());
            if($info_item_subject == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description") $info_item_subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription";
            $info_item_content = trim($info_item->get_literal("spm:hasContent"));
            
            if(!$info_item_content) continue;
            
            $data_object_parameters = array("identifier"    => $info_item->identifier(),
                                            "dataType"      => "http://purl.org/dc/dcmitype/Text",
                                            "mimeType"      => "text/html",
                                            "license"       => $rights,
                                            "description"   => htmlspecialchars_decode($info_item_content));
            
            if($info_item_subject) $data_object_parameters["subjects"][] = array("label" => $info_item_subject);
            if($publication_citation) $data_object_parameters["bibliographicCitation"] = $publication_citation;
            if(@$taxon_parameters["source"]) $data_object_parameters["source"] = $taxon_parameters["source"];
            if($authorship) $data_object_parameters["agents"][] = array("fullName" => $authorship, "role" => "author");
            
            $taxon_parameters["dataObjects"][] = $data_object_parameters;
        }
        
        if($taxon_parameters["dataObjects"]) echo "   This one has data objects<br>\n";
        
        $all_taxa[] = $taxon_parameters;
    }
}

?>