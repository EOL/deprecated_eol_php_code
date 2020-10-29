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
task_123
php update_resources/connectors/resource_utility.php _ '{"resource_id": "692_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "201_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "726_meta_recoded", "task": "metadata_recoding"}'

task_67
php update_resources/connectors/resource_utility.php _ '{"resource_id": "770_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "natdb_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "copepods_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "42_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "cotr_meta_recoded", "task": "metadata_recoding"}'
task_45
php update_resources/connectors/resource_utility.php _ '{"resource_id": "test_meta_recoded", "task": "metadata_recoding"}'
END of metadata_recoding

201	                Wed 2020-10-14 02:15:39 PM	{"measurement_or_fact.tab"         :195703, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":518.17, "min":8.640000000000001, "hr":0.14}}
201_meta_recoded	Thu 2020-10-29 10:54:43 AM	{"measurement_or_fact_specific.tab":148096, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":216.07, "min":3.6, "hr":0.06}}
less MoF is expected for 201_meta_recoded

726	            Thursday 2019-12-05 09:09:30 AM	{"measurement_or_fact.tab"         :21485, "occurrence.tab":2838, "taxon.tab":968, "time_elapsed":{"sec":17.5,"min":0.29,"hr":0}}
726_meta_recoded	Thu 2020-10-29 11:44:26 AM	{"measurement_or_fact_specific.tab":21485, "occurrence.tab":2838, "taxon.tab":968, "time_elapsed":{"sec":15.11, "min":0.25, "hr":0}}

770	                Tue 2020-09-15 09:20:16 AM	{"measurement_or_fact_specific.tab":979, "occurrence_specific.tab":978, "reference.tab":1, "taxon.tab":921, "time_elapsed":false}
770_meta_recoded	Wed 2020-10-28 09:37:23 AM	{"measurement_or_fact_specific.tab":979, "occurrence_specific.tab":978, "reference.tab":1, "taxon.tab":921, "time_elapsed":{"sec":8.01, "min":0.13, "hr":0}}
natdb	        Friday 2020-07-17 11:24:08 AM	{"measurement_or_fact_specific.tab":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":293.77, "min":4.9, "hr":0.08}}
natdb_meta_recoded	Wed 2020-10-28 09:43:50 AM	{"measurement_or_fact_specific.tab":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":82.73, "min":1.38, "hr":0.02}}
copepods	        Thursday 2019-07-11 08:30:46 AM	{"measurement_or_fact_specific.tab":21345,"occurrence.tab":18259,"reference.tab":925,"taxon.tab":2644}
copepods_meta_recoded	Wed 2020-10-28 09:47:22 AM	{"measurement_or_fact_specific.tab":21345, "occurrence_specific.tab":18259, "reference.tab":925, "taxon.tab":2644, "time_elapsed":{"sec":21.39, "min":0.36, "hr":0.01}}

42	            Sun 2020-09-13 04:41:23 PM	{"agent.tab":146, "measurement_or_fact_specific.tab":177712, "media_resource.tab":135702, "occurrence_specific.tab":161031, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":7343.42, "min":122.39, "hr":2.04}}
42_meta_recoded	Thu 2020-10-29 12:22:42 PM	{"agent.tab":146, "measurement_or_fact_specific.tab":177712, "media_resource.tab":135702, "occurrence_specific.tab":161031, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":313.42, "min":5.22, "hr":0.09}}


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
    if($resource_id == '201_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/201.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/201.tar.gz";
    }
    if($resource_id == '726_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/726.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/726.tar.gz";
    }
    elseif($resource_id == '770_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/770.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/770.tar.gz";
    }
    elseif($resource_id == 'natdb_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/natdb.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/natdb.tar.gz";
    }
    elseif($resource_id == 'copepods_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/copepods.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/copepods.tar.gz";
    }
    elseif($resource_id == '42_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/42.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/42.tar.gz";
    }
    elseif($resource_id == 'cotr_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/cotr.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/cotr.tar.gz";
    }
    elseif($resource_id == 'test_meta_recoded') { //task_45: no actual resource atm.
        $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/test_mUnit_sMethod.zip";
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
        $preferred_rowtypes = array();
        if(in_array($resource_id, array('201_meta_recoded', '726_meta_recoded'))) {
            $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact'); //means occurrenct tab is just carry-over
        }
        else $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/occurrence', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
    }
    
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>