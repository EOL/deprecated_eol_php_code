<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1881?focusedCommentId=65625&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65625
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 'Polytraits';
$dwca_file = 'https://editors.eol.org/other_files/eol_legacy_resources/Polytraits_16Mar2021.zip';
process_resource_url($dwca_file, $resource_id, $timestart);
function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    /* Orig in meta.xml has capital letters. Just a note reminder. */
    $preferred_rowtypes = array(); //blank like this means all rowtypes will be proccessed in DwCA_Utility.php
    $preferred_rowtypes = false;
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact');
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    // Functions::finalize_dwca_resource($resource_id);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means delete folder
}
?>