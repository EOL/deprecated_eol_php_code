<?php
namespace php_active_record;
/* connector for EMBLreptiles
SPG bought their CD. BIG exports their data from the CD to a spreadsheet.
This connector processes the spreadsheet.
estimated execution time: 3.8 minutes

bad char in <dc:description>:
<dc:identifier>rdb_Agama_finchi_BÖHME,_WAGNER,_MALONZA,_LÖTTERS_&amp;_KÖHLER_2005</dc:identifier>
<dc:identifier>rdb_Boiga_bengkuluensis_ORLOV,_KUDRYAVTZEV,_RYABOV_&amp;_SHUMAKOV_2003</dc:identifier>
<dc:identifier>rdb_Trioceros_harennae_LARGEN_1995</dc:identifier>

<taxon>
  <dc:identifier>rdb_Plestiodon_gilberti_VAN_DENBURGH_1896</dc:identifier>
  <dc:source>http://reptile-database.reptarium.cz/species?genus=Plestiodon&amp;species=gilberti</dc:source>
  <dwc:Family>Scincidae</dwc:Family>
  <dwc:Genus>Plestiodon</dwc:Genus>
  <dwc:ScientificName>Plestiodon gilberti VAN DENBURGH 1896</dwc:ScientificName>
<commonName xml:lang="en">arizonensis: Arizona Skink</commonName>
<commonName xml:lang="en">cancellosus: Variegated Skink</commonName>
<commonName xml:lang="en">gilberti: Greater Brown Skink</commonName>
<commonName xml:lang="en">placerensis: Northern Brown Skink</commonName>
<commonName xml:lang="en">rubricaudatus: Western Redtail Skink</commonName> -- this common name has bad char
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/EMBLreptiles');
$resource_id = 306; 
$taxa = EMBLreptiles::get_all_taxa($resource_id);
$xml = \SchemaDocument::get_taxon_xml($taxa);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w");
fwrite($OUT, $xml);
fclose($OUT);

// set to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml"))
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::force_harvest()->id . " WHERE id=" . $resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds   \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
exit("\n\n Done processing.");
?>