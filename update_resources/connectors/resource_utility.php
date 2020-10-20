<?php
namespace php_active_record;
/* This is generic way of calling ResourceUtility
removing taxa without MoF records.
first client: https://jenkins.eol.org/job/EOL%20Connectors/job/Environmental%20tagger%20for%20EOL%20resources/job/Wikipedia%20EN%20(English)/
              environments_2_eol.php for Wikipedia EN 

php update_resources/connectors/resource_utility.php _ '{"resource_id": "617_final", "task": "remove_taxa_without_MoF"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "wiki_en_report", "task": "report_4_Wikipedia_EN_traits"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "WoRMS2EoL_zip", "task": "add_canonical_in_taxa"}'
START of metadata_recoding
php update_resources/connectors/resource_utility.php _ '{"resource_id": "692_meta_recoded", "task": "metadata_recoding"}'
END of metadata_recoding
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
$task = $param['task'];
print_r($param);

if($task == 'remove_taxa_without_MoF') {
    if(Functions::is_production()) $dwca_file = '/u/scripts/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
    else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
    // /* ---------- customize here ----------
    if($resource_id == '617_final') $resource_id = "wikipedia_en_traits";
    else exit("\nERROR: [$task] resource_id not yet initialized. Will terminate.\n");
    // ----------------------------------------*/
}
elseif($task == 'report_4_Wikipedia_EN_traits') { //for Jen: https://eol-jira.bibalex.org/browse/DATA-1858?focusedCommentId=65155&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65155
    $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/wikipedia_en_traits.tar.gz';
    // $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/708.tar.gz'; //testing investigation only
}
elseif($task == 'add_canonical_in_taxa') {
    if($resource_id == 'WoRMS2EoL_zip') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/other_files/WoRMS/WoRMS2EoL.zip";
                                        // $dwca_file = "http://www.marinespecies.org/export/eol/WoRMS2EoL.zip";
        else                            $dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";
    }
    else exit("\nERROR: [$task] resource_id not yet initialized. Will terminate.\n");
}
elseif($task == 'metadata_recoding') {
    if($resource_id == '692_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/692.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/692.tar.gz";
    }
    else exit("\nERROR: [$task] resource_id not yet initialized. Will terminate.\n");
}

else exit("\nERROR: task not yet initialized. Will terminate.\n");
process_resource_url($dwca_file, $resource_id, $task, $timestart);

function process_resource_url($dwca_file, $resource_id, $task, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    
    if($task == 'remove_taxa_without_MoF') {
        if(in_array($resource_id, array('wikipedia_en_traits'))) {
            $preferred_rowtypes = array();
            $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
            /* These below will be processed in ResourceUtility.php which will be called from DwCA_Utility.php
            http://rs.tdwg.org/dwc/terms/taxon
            */
        }
    }
    elseif($task == 'report_4_Wikipedia_EN_traits') {
        $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact'); //best to set this to array() and just set $excluded_rowtypes to taxon
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact');
    }
    elseif($task == 'add_canonical_in_taxa') {
        /* working but not needed for DH purposes
        $preferred_rowtypes = array();
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
        */
        $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
    }

    elseif($task == 'metadata_recoding') {
        /* working but not needed for DH purposes
        $preferred_rowtypes = array();
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
        */
        $preferred_rowtypes = array();
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/occurrence', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
    }
    
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>