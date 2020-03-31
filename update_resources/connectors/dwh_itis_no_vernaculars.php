<?php
namespace php_active_record;
/* This is to cater exclusively for DATA-1849: remove common names for https://opendata.eol.org/dataset/tram-804-itis-hierarchy/resource/461015db-542a-4cc7-ab19-269884fcfc9a
That is: ITIS hierarchy from 31-Mar-2019 downloads
URL: https://editors.eol.org/eol_php_code/applications/content_server/resources/itis_2019-03-31.tar.gz
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 'itis_2019-03-31_no_vernaculars';
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/itis_2019-03-31.tar.gz';
process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
    /* Vernaculars are removed.
    http://rs.gbif.org/terms/1.0/vernacularname
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>