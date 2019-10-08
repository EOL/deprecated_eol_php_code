<?php
namespace php_active_record;
/* 
wget    http://ipt.jbrj.gov.br/jbrj/archive.do?r=lista_especies_flora_brasil -O lista_especies_flora_brasil.zip
wget -q http://ipt.jbrj.gov.br/jbrj/archive.do?r=lista_especies_flora_brasil -O /extra/other_files/GBIF_DwCA/lista_especies_flora_brasil.zip
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

$resource_id = 'brazilian_flora';
$dwca_file = "https://editors.eol.org/other_files/GBIF_DwCA/lista_especies_flora_brasil.zip";
process_resource_url($dwca_file, $resource_id, $timestart);

// $elapsed_time_sec = time_elapsed() - $timestart;
// echo "\n\n";
// echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
// echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
// echo "\nDone processing.\n";
/* 
The vernacularname file looks pretty good as is.
The taxon file has a couple of columns we haven't heard of, which I'm comfortable leaving out.
*/
function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    /* Orig in meta.xml has capital letters. Just a note reminder.
    rowType="http://rs.tdwg.org/dwc/terms/Taxon">
    rowType="http://rs.gbif.org/terms/1.0/VernacularName">
    rowType="http://rs.gbif.org/terms/1.0/SpeciesProfile">
    rowType="http://rs.gbif.org/terms/1.0/Reference">
    rowType="http://rs.gbif.org/terms/1.0/Distribution">
    rowType="http://rs.tdwg.org/dwc/terms/ResourceRelationship">    IGNORE
    rowType="http://rs.gbif.org/terms/1.0/TypesAndSpecimen">        IGNORE
    */

    $preferred_rowtypes = array('http://rs.gbif.org/terms/1.0/vernacularname', 'http://rs.tdwg.org/dwc/terms/taxon');
    /* These will be processed in BrazilianFloraAPI.php which will be called from DwCA_Utility.php
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
}
?>