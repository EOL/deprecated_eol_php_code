<?php
namespace php_active_record;
/* This is a generic way to remove habitat values that are descendants of marine and terrestrial.
As requested here: https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=66742&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66742
First client is: Environments EOL (708.tar.gz generated by 708_new.php)

php update_resources/connectors/rem_marine_terr_desc.php _ '{"resource_id":"708"}'
-> Environments EOL
php update_resources/connectors/rem_marine_terr_desc.php _ '{"resource_id":"wikipedia_en_traits_tmp2"}'
-> Wikipedia Eng Traits
php update_resources/connectors/rem_marine_terr_desc.php _ '{"resource_id":"26_delta"}'
-> WoRMS
php update_resources/connectors/rem_marine_terr_desc.php _ '{"resource_id":"21_ENV"}'
-> AmphibiaWeb
php update_resources/connectors/rem_marine_terr_desc.php _ '{"resource_id":"22"}'
-> Animal Diversity Web (ADW)
php update_resources/connectors/rem_marine_terr_desc.php _ '{"resource_id":"24"}'
-> AntWeb
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron']  = @$argv[1]; //not needed here
$params                     = json_decode(@$argv[2], true);
$resource_id = @$params['resource_id']; 

if(Functions::is_production())  $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
else                            $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';

// /* customize here:
if(in_array($resource_id, array('708'))) {
    $resource_id .= "_cleaned_MoF_habitat"; //remove all records for taxon with habitat value(s) that are descendants of both marine and terrestrial
}
elseif($resource_id == "wikipedia_en_traits_tmp2") $resource_id = "wikipedia_en_traits_tmp3";
elseif($resource_id == "26_delta") $resource_id = "26_delta_new";
elseif($resource_id == "21_ENV") $resource_id = "21_cleaned_MoF_habitat";   //AmphibiaWeb reverted back to old state - https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=66801&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66801
elseif($resource_id == "22") $resource_id = "22_cleaned_MoF_habitat";       
elseif($resource_id == "24") $resource_id = "24_cleaned_MoF_habitat";       //AntWeb reverted back to old state - https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=66801&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66801
else exit("\nresource ID not yet initialized [$resource_id]\n");
// */

process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    if($resource_id == '708_cleaned_MoF_habitat') {
        $preferred_rowtypes = array();
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/reference/reference', 
                                   'http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence');
    }
    elseif($resource_id == 'wikipedia_en_traits_tmp3') {
        $preferred_rowtypes = array();
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 
                                   'http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence');
    }
    elseif(in_array($resource_id, array('26_delta_new', '21_cleaned_MoF_habitat', '22_cleaned_MoF_habitat', '24_cleaned_MoF_habitat'))) {
        $preferred_rowtypes = array();
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence');
    }
    else exit("\nresource ID not yet initialized [$resource_id]\n");
    /* $excluded_rowtypes will be processed in Clean_MoF_Habitat_API.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>