<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1820

from legacy https://editors.eol.org/eol_php_code/applications/content_server/resources/legacy_EOL_233_final.tar.gz
Statistics
    http://rs.gbif.org/terms/1.0/vernacularname:
        Total by language:en: 3054
    http://rs.tdwg.org/dwc/terms/taxon:Total: 2164
    http://eol.org/schema/agent/agent:Total: 2
    http://eol.org/schema/media/document:
        Total by type:
            http://purl.org/dc/dcmitype/MovingImage: 6718
        Total by license:
            http://creativecommons.org/licenses/by-nc/3.0/: 6718
        Total by language:
            en: 6718
        Total by format:
            video/quicktime: 6718
        Total: 6718
*The legacy EOL_233_final.tar.gz was renamed to legacy_EOL_233_final.tar.gz. We're going to use the latter moving forward.
-rw-r--r-- 1 root root 350594 Mar 19  2018 EOL_233_final.tar.gz
-rw-r--r-- 1 root root 173724 Apr 16  2018 170.tar.gz
-----------------------------------------------------------------------------------------------------------------------
started using 233.php - Sep 24, 2019
233	Tuesday 2019-09-24 01:46:41 AM	{"agent.tab":2,"media_resource.tab":6718,"taxon.tab":2164,"vernacular_name.tab":3054}
233	Thursday 2020-02-20 09:18:43 AM	{"agent.tab":2,"media_resource.tab":6718,"taxon.tab":2164,"vernacular_name.tab":3054,"time_elapsed":{"sec":10.56,"min":0.18,"hr":0}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

$resource_id = 233;
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/legacy_EOL_233_final.tar.gz';
process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    /* Orig in meta.xml has capital letters. Just a note reminder. */
    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/agent/agent');
    /* These 2 will be processed in MediaConvertAPI.php which will be called from DwCA_Utility.php
    http://eol.org/schema/media/Document
    http://rs.gbif.org/terms/1.0/VernacularName
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true, means folder will be deleted
}
?>