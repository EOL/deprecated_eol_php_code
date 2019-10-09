<?php
namespace php_active_record;
/* 
wget    http://ipt.jbrj.gov.br/jbrj/archive.do?r=lista_especies_flora_brasil -O lista_especies_flora_brasil.zip
wget -q http://ipt.jbrj.gov.br/jbrj/archive.do?r=lista_especies_flora_brasil -O /extra/other_files/GBIF_DwCA/lista_especies_flora_brasil.zip

BF	Wednesday 2019-10-09 10:48:30 AM{"measurement_or_fact_specific.tab":407771,"occurrence_specific.tab":407771,"reference.tab":55234,"taxon.tab":122658,"vernacular_name.tab":9124,"time_elapsed":{"sec":923.5,"min":15.39,"hr":0.26}} Mac Mini
BF	Wednesday 2019-10-09 10:46:32 AM{"measurement_or_fact_specific.tab":407771,"occurrence_specific.tab":407771,"reference.tab":55234,"taxon.tab":122658,"vernacular_name.tab":9124,"time_elapsed":{"sec":466.44,"min":7.77,"hr":0.13}} eol-archive
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

$resource_id = 'BF'; //'brazilian_flora';
$dwca_file = "https://editors.eol.org/other_files/GBIF_DwCA/lista_especies_flora_brasil.zip";
process_resource_url($dwca_file, $resource_id, $timestart);

// $elapsed_time_sec = time_elapsed() - $timestart;
// echo "\n\n";
// echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
// echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
// echo "\nDone processing.\n";
/* 
The vernacularname file looks pretty good as is.
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

    $preferred_rowtypes = array(); //all tables processed elsewhere in BrazilianFloraAPI
    /* These will be processed in BrazilianFloraAPI.php which will be called from DwCA_Utility.php
    vernacularname, Taxon, Reference, SpeciesProfile, Distribution
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
}
?>