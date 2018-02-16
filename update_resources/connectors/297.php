<?php
namespace php_active_record;
/* connector for Iabin
Partner provides DWC-A file
estimated execution time: 2 minutes
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

echo "\n Connector obsolete. Used lifedesk_combination.php instead. \n";
return;

require_library('connectors/INBioAPI');
require_library('connectors/IabinAPI');
$resource_id = 297; 
if($taxa = IabinAPI::get_all_taxa($resource_id))
{
    $xml = \SchemaDocument::get_taxon_xml($taxa);
    $xml = INBioAPI::assign_eol_subjects($xml);
    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
    if(!($OUT = fopen($resource_path, "w")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
      return;
    }
    fwrite($OUT, $xml);
    fclose($OUT);
    Functions::set_resource_status_to_harvest_requested($resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\nelapsed time = " . $elapsed_time_sec . " seconds";
echo "\nelapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\nDone processing.";
?>