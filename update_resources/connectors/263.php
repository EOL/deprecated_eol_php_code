<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli = $GLOBALS['db_connection'];

require_library('connectors/NatureServeAPI');
@unlink(DOC_ROOT . "/temp/dwc_archive_test/meta.xml");
@unlink(DOC_ROOT . "/temp/dwc_archive_test/taxon.tab");
@unlink(DOC_ROOT . "/temp/dwc_archive_test/taxon_working.tab");
@unlink(DOC_ROOT . "/temp/dwc_archive_test/media_resource.tab");
@unlink(DOC_ROOT . "/temp/dwc_archive_test/media_resource_working.tab");
@unlink(DOC_ROOT . "/temp/dwc_archive_test/reference.tab");
rmdir(DOC_ROOT . "/temp/dwc_archive_test/");

$resource_id = 263;
$naturserveAPI = new NatureServeAPI();
$naturserveAPI->get_all_taxa();

if(!($resource_file = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $resource_id ."_temp.xml", "w+"))) return;

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());

$archive = new ContentArchiveReader(null, DOC_ROOT . "/temp/dwc_archive_test/");

$GLOBALS['data_objects'] = array();
$GLOBALS['taxon_id_media'] = array();
$GLOBALS['all_references'] = array();

// print_r($archive->tables);

$archive->process_row_type("http://eol.org/schema/reference/Reference", "php_active_record\\lookup_references");
$archive->process_row_type("http://eol.org/schema/media/Document", "php_active_record\\lookup_data_objects");
$archive->process_row_type("http://rs.tdwg.org/dwc/terms/Taxon", "php_active_record\\lookup_taxa", array('resource_file' => $resource_file));

// write the resource footer
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id ."_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");

/* obsolete
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 200000) {
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Harvest Requested')->id." WHERE id=$resource_id");
}
*/
// start convert XML to DwCA:
require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$params["filename"]     = "";
$params["dataset"]      = "";
$params["resource_id"]  = $resource_id;
$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 0); // false => means it is NOT an XML file, BUT an archive file OR a zip file. IMPORTANT: Expires now = 0.

$deleteYN = false; //true means delete the DwCA folder in /resources/
Functions::finalize_dwca_resource($resource_id, false, $deleteYN);

function lookup_taxa($taxon, $parameters)
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
    if($v = $taxon['http://rs.tdwg.org/dwc/terms/vernacularName']) {
        $taxon_parameters['commonNames'] = array(new \SchemaCommonName(array('name' => $v, 'language' => 'en')));
    }
    
    $taxon_parameters['dataObjects'] = array();
    if($data_object_ids = @$GLOBALS['taxon_id_media'][$taxon_parameters['identifier']]) {
        foreach($data_object_ids as $data_object_id) {
            if($data_object = $GLOBALS['data_objects'][$data_object_id]) {
                $taxon_parameters['dataObjects'][] = $data_object;
            }
        }
    }
    $taxon_parameters['references'] = array();
    if($reference_ids = $taxon['http://eol.org/schema/reference/referenceID']) {
        $reference_ids = explode("; ", $reference_ids);
        foreach($reference_ids as $ref_id) {
            if($r = $GLOBALS['all_references'][$ref_id]) {
                $taxon_parameters['references'][] = $r;
            }
        }
    }
    
    $t = new \SchemaTaxon($taxon_parameters);
    fwrite($parameters['resource_file'], $t->__toXML());
}

function lookup_data_objects($media)
{
    static $i=0;
    if($i%1000==0) echo "$i - ".memory_get_usage()."\n";
    $i++;
    // if($i >= 50000) return;
    
    $object_parameters = array();
    $object_parameters['additionalInformation'] = "";
    $object_parameters['identifier'] = $media['http://purl.org/dc/terms/identifier'];
    $object_parameters['dataType'] = $media['http://purl.org/dc/terms/type'];
    $object_parameters['mimeType'] = $media['http://purl.org/dc/terms/format'];
    if($creator = $media['http://purl.org/dc/terms/creator']) {
        $role = '';
        if($object_parameters['dataType'] == 'http://purl.org/dc/dcmitype/StillImage') $role = 'photographer';
        if($object_parameters['dataType'] == 'http://purl.org/dc/dcmitype/Text') $role = 'author';
        $object_parameters['agents'] = array(new \SchemaAgent(array('fullName' => $creator, 'role' => $role)));
    }
    if($v = $media['http://ns.adobe.com/xap/1.0/CreateDate']) $object_parameters['created'] = $v;
    if($v = $media['http://purl.org/dc/terms/title']) $object_parameters['title'] = $v;
    if($v = $media['http://purl.org/dc/terms/language']) $object_parameters['language'] = $v;
    if($v = $media['http://ns.adobe.com/xap/1.0/rights/UsageTerms']) $object_parameters['license'] = $v;
    if($object_parameters['license'] == 'http://creativecommons.org/licenses/publicdomain') {
        $object_parameters['license'] = 'http://creativecommons.org/licenses/publicdomain/';
    }
    if($v = $media['http://ns.adobe.com/xap/1.0/rights/Owner']) $object_parameters['rightsHolder'] = $v;
    if($v = $media['http://rs.tdwg.org/ac/terms/furtherInformationURL']) $object_parameters['source'] = $v;
    
    if($media['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] == 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Use') {
        $media['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses';
    }
    elseif($media['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] == 'http://www.eol.org/voc/table_of_contents#Taxonomy') {
        $media['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription';
        $object_parameters['additionalInformation'] .= "<subject>http://www.eol.org/voc/table_of_contents#Taxonomy</subject>";
    }
    
    if($v = $media['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm']) $object_parameters['subjects'] = array(new \SchemaSubject(array('label' => $v)));
    if($v = $media['http://purl.org/dc/terms/description']) $object_parameters['description'] = $v;
    if($v = $media['http://rs.tdwg.org/ac/terms/accessURI']) $object_parameters['mediaURL'] = $v;
    
    if($v = $media['http://rs.tdwg.org/audubon_core/subtype']) $object_parameters['additionalInformation'] .= "<subtype>Map</subtype>";

    if($v = $media['http://ns.adobe.com/xap/1.0/Rating']) $object_parameters['additionalInformation'] .= "<rating>$v</rating>";
    
    if($taxon_id = $media['http://rs.tdwg.org/dwc/terms/taxonID']) {
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
    
    $identifier = $ref['http://purl.org/dc/terms/identifier'];
    $full_reference = $ref['http://eol.org/schema/reference/full_reference'];
    $GLOBALS['all_references'][$identifier] = new \SchemaReference(array('full_reference' => $full_reference));
}
?>
