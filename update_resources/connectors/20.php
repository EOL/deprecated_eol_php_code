<?php
namespace php_active_record;
/* connector for ZooKeys
estimated execution time: 1 minute
Connector reads the XML provided by partner and 
moves the <agent>s with role = 'source' to bibliographicCitation
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('ResourceDataObjectElementsSetting');

$timestart = time_elapsed();
$resource_id = 20;
$resource_path = "http://www.pensoft.net/J_FILES/EoLData/ZooKeys.xml";

$result = $GLOBALS['db_connection']->select("SELECT accesspoint_url FROM resources WHERE id=$resource_id");
if($result && $row = $result->fetch_row())
{
    $resource_path_from_registry = $row[0];
    if($resource_path != $resource_path_from_registry && $resource_path_from_registry != '') $resource_path = $resource_path_from_registry;
}
echo "\n processing resource: $resource_path \n";

if($local_path = Functions::save_remote_file_to_local($resource_path, 30000000, 600, 5))
{
    $func = new ResourceDataObjectElementsSetting($resource_id, $local_path);
    $dataObjects = get_values($local_path);
    $xml = remove_elements($local_path);
    $func->save_resource_document($xml);

    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
    $xml = fill_up_values($resource_path, $dataObjects);
    $func->save_resource_document($xml);
    Functions::set_resource_status_to_force_harvest($resource_id);

    // remove tmp dir
    $path_parts = pathinfo($local_path);
    $temp_directory = $path_parts['dirname'];
    if($temp_directory) shell_exec("rm -fr $temp_directory");
    debug("\n temporary directory removed: [$temp_directory]");
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";


function get_values($resource_path)
{
    $dataObjects = array();
    if($xml = Functions::get_hashed_response($resource_path, 30000000, 600, 5))
    {
        foreach($xml->taxon as $taxon)
        {
            foreach($taxon->dataObject as $dataObject)
            {
                $do_dc = $dataObject->children("http://purl.org/dc/elements/1.1/");
                if($dataObject->agent{'role'} == "source")
                {
                    $attrs = $do_dc->description->attributes('http://www.w3.org/XML/1998/namespace');
                    $dataObjects["$do_dc->identifier"] = array("dc_identifier"      => $do_dc->identifier,
                                                           "dataType"           => $dataObject->dataType,
                                                           "mimeType"           => $dataObject->mimeType,
                                                           "agent"              => $dataObject->agent,
                                                           "dc_title"           => $do_dc->title,
                                                           "dc_language"        => $do_dc->language,
                                                           "license"            => $dataObject->license,
                                                           "audience"           => $dataObject->audience,
                                                           "subject"            => $dataObject->subject,
                                                           "description"        => $do_dc->description,
                                                           "description_lang"   => $attrs['lang']);
                }
            }
        }
        return $dataObjects;
    }
}

function remove_elements($resource_path)
{
    if($xml = Functions::get_hashed_response($resource_path, 30000000, 600, 5))
    {
        foreach($xml->taxon as $taxon)
        {
            foreach($taxon->dataObject as $dataObject)
            {
                $do_dc = $dataObject->children("http://purl.org/dc/elements/1.1/");
                if($dataObject->agent{'role'} == "source")
                {
                    unset($dataObject->agent);
                    unset($dataObject->audience);
                    unset($dataObject->subject);
                    unset($do_dc->description);
                    $dataObject->addChild("dcterms:bibliographicCitation", '', 'http://purl.org/dc/terms/');
                    $dataObject->addChild("audience", "General public");
                    $dataObject->addChild("audience", "Expert users");
                    $dataObject->addChild("subject");
                    $dataObject->addChild("dc:description", '', 'http://purl.org/dc/elements/1.1/');
                    $do_dc->description->addAttribute("xml:lang", '', 'http://www.w3.org/XML/1998/namespace');
                }
            }
        }
        return $xml->asXML();
    }
}

function fill_up_values($resource_path, $dataObjects)
{
    if($xml = Functions::get_hashed_response($resource_path, 30000000, 600, 5))
    {
        foreach($xml->taxon as $taxon)
        {
            foreach($taxon->dataObject as $dataObject)
            {
                $do_dcterms = $dataObject->children("http://purl.org/dc/terms/");
                $do_dc = $dataObject->children("http://purl.org/dc/elements/1.1/");
                if(@$dataObjects["$do_dc->identifier"])
                {
                    $do_dcterms->bibliographicCitation = $dataObjects["$do_dc->identifier"]['agent'];
                    $dataObject->subject = $dataObjects["$do_dc->identifier"]['subject'];
                    $do_dc->description = $dataObjects["$do_dc->identifier"]['description'];
                    $do_dc->description->attributes('http://www.w3.org/XML/1998/namespace')->lang = $dataObjects["$do_dc->identifier"]['description_lang'];
                }
            }
        }
        return $xml->asXML();
    }
}

?>