<?php
namespace php_active_record;
/* 
wget    http://ipt.jbrj.gov.br/jbrj/archive.do?r=lista_especies_flora_brasil -O lista_especies_flora_brasil.zip
wget -q http://ipt.jbrj.gov.br/jbrj/archive.do?r=lista_especies_flora_brasil -O /extra/other_files/GBIF_DwCA/lista_especies_flora_brasil.zip

BF	Wednesday 2019-10-09 10:48:30 AM{"measurement_or_fact_specific.tab":407771,"occurrence_specific.tab":407771,"reference.tab":55234,"taxon.tab":122658,"vernacular_name.tab":9124,"time_elapsed":{"sec":923.5,"min":15.39,"hr":0.26}} Mac Mini
BF	Wednesday 2019-10-09 10:46:32 AM{"measurement_or_fact_specific.tab":407771,"occurrence_specific.tab":407771,"reference.tab":55234,"taxon.tab":122658,"vernacular_name.tab":9124,"time_elapsed":{"sec":466.44,"min":7.77,"hr":0.13}} eol-archive
BF	Thursday 2019-10-10 05:46:16 AM	{"measurement_or_fact_specific.tab":413020,"occurrence_specific.tab":413020,"reference.tab":55234,"taxon.tab":122658,"vernacular_name.tab":9124,"time_elapsed":{"sec":468.82,"min":7.81,"hr":0.13}} eol-archive
BF	Monday 2019-10-14 04:19:54 AM	{"measurement_or_fact_specific.tab":413020,"occurrence_specific.tab":404891,"reference.tab":55234,"taxon.tab":122658,"vernacular_name.tab":9124,"time_elapsed":{"sec":461.29,"min":7.69,"hr":0.13}} all eol-archive at this point.
BF	Monday 2019-11-25 09:20:28 AM	{"measurement_or_fact_specific.tab":413020, "occurrence_specific.tab":404891, "reference.tab":55234, "taxon.tab":122658, "vernacular_name.tab":9124,"time_elapsed":{"sec":454.81,"min":7.58,"hr":0.13}}
BF	Tue 2021-01-05 11:18:15 PM	    {"measurement_or_fact_specific.tab":413020, "occurrence_specific.tab":404891, "reference.tab":55234, "taxon.tab":122658, "vernacular_name.tab":9124, "time_elapsed":{"sec":643.33, "min":10.72, "hr":0.18}}
BF	Tue 2021-01-26 08:11:48 PM	    {"measurement_or_fact_specific.tab":413020, "occurrence_specific.tab":404891, "reference.tab":55234, "taxon.tab":122658, "vernacular_name.tab":9124, "time_elapsed":{"sec":589.04, "min":9.82, "hr":0.16}}
BF	Wed 2022-12-14 08:58:21 AM	    {"measurement_or_fact_specific.tab":413020, "occurrence_specific.tab":404891, "reference.tab":55234, "taxon.tab":122658, "vernacular_name.tab":9124, "time_elapsed":{"sec":621.19, "min":10.35, "hr":0.17}}
BF	Tue 2023-02-14 08:58:20 AM	    {"measurement_or_fact_specific.tab":413020, "occurrence_specific.tab":404891, "reference.tab":55234, "taxon.tab":122658, "vernacular_name.tab":9124, "time_elapsed":{"sec":619.08, "min":10.32, "hr":0.17}}
*/

/*
latest status: https://eol-jira.bibalex.org/browse/DATA-1919?focusedCommentId=67375&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67375
Hi Jen,
We've been refreshing BrazilianFlora monthly for quite sometime now.
But at some point the source file became un-available.
wget http://ipt.jbrj.gov.br/jbrj/archive.do?r=lista_especies_flora_brasil -O lista_especies_flora_brasil.zip

And our connector just kept on using the last downloaded version.
Here is the latest numbers now:
{"MoF":413020, "occurrence":404891, "reference.tab":55234, "taxon.tab":122658, "vernacular_name.tab":9124}
[OpenData|https://opendata.eol.org/dataset/brazilian-flora/resource/04e94dff-d997-4e3f-946c-2c4bf5173256]
Thanks.
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