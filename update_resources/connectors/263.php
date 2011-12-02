<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli = $GLOBALS['db_connection'];




/*require_library('connectors/NatureServeAPI');
unlink(DOC_ROOT . "/temp/dwc_archive_test/meta.xml");
unlink(DOC_ROOT . "/temp/dwc_archive_test/taxon.tab");
unlink(DOC_ROOT . "/temp/dwc_archive_test/taxon_working.tab");
unlink(DOC_ROOT . "/temp/dwc_archive_test/media_resource.tab");
unlink(DOC_ROOT . "/temp/dwc_archive_test/media_resource_working.tab");
unlink(DOC_ROOT . "/temp/dwc_archive_test/reference.tab");
rmdir(DOC_ROOT . "/temp/dwc_archive_test/");
$naturserveAPI = new NatureServeAPI();
$naturserveAPI->get_all_taxa();
*/








$resource_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . "263_temp.xml", "w+");

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());



require_vendor('eol_content_schema_v2');
$archive = new ContentArchiveReader(null, DOC_ROOT . "/temp/dwc_archive_test/");

$GLOBALS['data_objects'] = array();
$GLOBALS['taxon_id_media'] = array();

print_r($archive->tables);

$archive->process_table("http://labs2.eol.org/schema/ontology.rdf#Document", "php_active_record\\lookup_references");
$archive->process_table("http://labs2.eol.org/schema/ontology.rdf#MediaResource", "php_active_record\\lookup_data_objects");
$archive->process_table("http://rs.tdwg.org/dwc/terms/Taxon", "php_active_record\\lookup_taxa", $resource_file);


// write the resource footer
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . "263_previous.xml");
@rename(CONTENT_RESOURCE_LOCAL_PATH . "263.xml", CONTENT_RESOURCE_LOCAL_PATH . "263_previous.xml");
rename(CONTENT_RESOURCE_LOCAL_PATH . "263_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . "263.xml");

// // set Flickr to force harvest
// if(filesize(CONTENT_RESOURCE_LOCAL_PATH . "263.xml"))
// {
//     $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Force Harvest')->id." WHERE id=263");
// }






function lookup_taxa($taxon, $resource_file)
{
    static $i=0;
    if($i%1000==0) echo "$i - ".memory_get_usage()."\n";
    $i++;
    // if($i >= 1000) return;
    
    $taxon_parameters = array();
    $taxon_parameters['identifier'] = $taxon['http://rs.tdwg.org/dwc/terms/taxonID'];
    $taxon_parameters['source'] = $taxon['http://purl.org/dc/terms/source'];
    $taxon_parameters['scientificName'] = $taxon['http://rs.tdwg.org/dwc/terms/scientificName'];
    if($v = $taxon['http://rs.tdwg.org/dwc/terms/kingdom']) $taxon_parameters['kingdom'] = $v;
    if($v = $taxon['http://rs.tdwg.org/dwc/terms/phylum']) $taxon_parameters['phylum'] = $v;
    if($v = $taxon['http://rs.tdwg.org/dwc/terms/class']) $taxon_parameters['class'] = $v;
    if($v = $taxon['http://rs.tdwg.org/dwc/terms/order']) $taxon_parameters['order'] = $v;
    if($v = $taxon['http://rs.tdwg.org/dwc/terms/family']) $taxon_parameters['family'] = $v;
    if($v = $taxon['http://rs.tdwg.org/dwc/terms/genus']) $taxon_parameters['genus'] = $v;
    if($v = $taxon['http://rs.tdwg.org/dwc/terms/taxonRank']) $taxon_parameters['rank'] = $v;
    if($v = $taxon['http://rs.tdwg.org/dwc/terms/vernacularName'])
    {
        $taxon_parameters['commonNames'] = array(new \SchemaCommonName(array('name' => $v, 'language' => 'en')));
    }
    
    $taxon_parameters['dataObjects'] = array();
    if($data_object_ids = @$GLOBALS['taxon_id_media'][$taxon_parameters['identifier']])
    {
        foreach($data_object_ids as $data_object_id)
        {
            if($data_object = $GLOBALS['data_objects'][$data_object_id])
            {
                $taxon_parameters['dataObjects'][] = $data_object;
            }
        }
    }
    $taxon_parameters['references'] = array();
    if($taxon_id_refs = @$GLOBALS['taxon_id_refs'][$taxon_parameters['identifier']])
    {
        foreach($taxon_id_refs as $ref)
        {
            $taxon_parameters['references'][] = $ref;
        }
    }
    
    $t = new \SchemaTaxon($taxon_parameters);
    fwrite($resource_file, $t->__toXML());
}

function lookup_data_objects($media)
{
    static $i=0;
    if($i%1000==0) echo "$i - ".memory_get_usage()."\n";
    $i++;
    // if($i >= 50000) return;
    
    
    $object_parameters = array();
    $object_parameters['additionalInformation'] = "";
    $object_parameters['identifier'] = $media['http://www.eol.org/schema/transfer#mediaResourceID'];
    $object_parameters['dataType'] = $media['http://www.eol.org/schema/transfer#type'];
    $object_parameters['mimeType'] = $media['http://www.eol.org/schema/transfer#mimeType'];
    if($creator = $media['http://www.eol.org/schema/transfer#creator'])
    {
        $role = '';
        if($object_parameters['dataType'] == 'http://purl.org/dc/dcmitype/StillImage') $role = 'photographer';
        if($object_parameters['dataType'] == 'http://purl.org/dc/dcmitype/Text') $role = 'author';
        $object_parameters['agents'] = array(new \SchemaAgent(array('fullName' => $creator, 'role' => $role)));
    }
    if($v = $media['http://www.eol.org/schema/transfer#created']) $object_parameters['created'] = $v;
    if($v = $media['http://www.eol.org/schema/transfer#title']) $object_parameters['title'] = $v;
    if($v = $media['http://www.eol.org/schema/transfer#language']) $object_parameters['language'] = $v;
    if($v = $media['http://www.eol.org/schema/transfer#license']) $object_parameters['license'] = $v;
    if($object_parameters['license'] == 'http://creativecommons.org/licenses/publicdomain')
    {
        $object_parameters['license'] = 'http://creativecommons.org/licenses/publicdomain/';
    }
    if($v = $media['http://www.eol.org/schema/transfer#rights']) $object_parameters['rights'] = $v;
    if($v = $media['http://www.eol.org/schema/transfer#rightsHolder']) $object_parameters['rightsHolder'] = $v;
    if($v = $media['http://www.eol.org/schema/transfer#additionalInformationURL']) $object_parameters['source'] = $v;
    
    
    
    if($media['http://www.eol.org/schema/transfer#subject'] == 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Use')
    {
        $media['http://www.eol.org/schema/transfer#subject'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses';
    }elseif($media['http://www.eol.org/schema/transfer#subject'] == 'http://www.eol.org/voc/table_of_contents#Taxonomy')
    {
        $media['http://www.eol.org/schema/transfer#subject'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription';
        $object_parameters['additionalInformation'] .= "<subject>http://www.eol.org/voc/table_of_contents#Taxonomy</subject>";
    }
    
    
    
    if($v = $media['http://www.eol.org/schema/transfer#subject']) $object_parameters['subjects'] = array(new \SchemaSubject(array('label' => $v)));
    if($v = $media['http://www.eol.org/schema/transfer#description']) $object_parameters['description'] = $v;
    if($v = $media['http://www.eol.org/schema/transfer#fileURL']) $object_parameters['mediaURL'] = $v;
    
    if($v = $media['http://www.eol.org/schema/transfer#subtype'])
    {
        $object_parameters['additionalInformation'] .= "<subtype>Map</subtype>";
    }
    
    
    if($taxon_id = $media['http://rs.tdwg.org/dwc/terms/taxonID'])
    {
        if(!isset($GLOBALS['taxon_id_media'][$taxon_id])) $GLOBALS['taxon_id_media'][$taxon_id] = array();
        $GLOBALS['taxon_id_media'][$taxon_id][] = $object_parameters['identifier'];
    }
    
    $GLOBALS['data_objects'][$object_parameters['identifier']] = new \SchemaDataObject($object_parameters);
}

function lookup_references($ref)
{
    static $i=0;
    if($i%1000==0) echo "$i - ".memory_get_usage()."\n";
    $i++;
    // if($i >= 1000) return;
    
    $full_reference = $ref['http://www.eol.org/schema/reference#fullReference'];
    if($taxon_id = $ref['http://rs.tdwg.org/dwc/terms/relatedResourceID'])
    {
        if(!isset($GLOBALS['taxon_id_refs'][$taxon_id])) $GLOBALS['taxon_id_refs'][$taxon_id] = array();
        $GLOBALS['taxon_id_refs'][$taxon_id][] = new \SchemaReference(array('fullReference' => $full_reference));
    }
    
}





?>
