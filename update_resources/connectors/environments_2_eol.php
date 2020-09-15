<?php
namespace php_active_record;
/* DATA-1851: reconstructing the Environments-EOL resource
Next step now is to combine all the steps within a general connector:
1. read any EOL DwCA resource (with text objects)
2. generate individual txt files for the articles with filename convention.
3. run environment_tagger against these text files
4. generate the raw file: eol_tags_noParentTerms.tsv
5. generate the updated DwCA resource, now with Trait data from Environments
    5.1 append in MoF the new environments trait data
    5.2 include the following (if available) from the text object where trait data was derived from. Reflect this in the MoF.
      5.2.1 source - http://purl.org/dc/terms/source
      5.2.2 bibliographicCitation - http://purl.org/dc/terms/bibliographicCitation
      5.2.3 contributor - http://purl.org/dc/terms/contributor
      5.2.4 referenceID - http://eol.org/schema/reference/referenceID
      5.2.5 agendID -> contributor

Implementation:
php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags", "resource":"AmphibiaWeb text", "resource_id":"21", "subjects":"Distribution"}'
php update_resources/connectors/environments_2_eol.php _ '{"task": "apply_formats_filters", "resource_id":"21"}'
php update_resources/connectors/environments_2_eol.php _ '{"task": "apply_formats_filters_latest", "resource_id":"21"}'

21_final	Mon 2020-09-07 06:20:02 AM	{"agent.tab":743, "measurement_or_fact.tab":8961, "media_resource.tab":8138, "occurrence.tab":8961, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":{"sec":47.32, "min":0.79, "hr":0.01}}
21_final	Tue 2020-09-08 01:09:17 AM	{"agent.tab":743, "measurement_or_fact.tab":8961, "media_resource.tab":8138, "occurrence.tab":8961, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":{"sec":48.43, "min":0.81, "hr":0.01}}
21_final	Mon 2020-09-14 04:24:19 AM	{"agent.tab":743, "measurement_or_fact.tab":8961, "media_resource.tab":8138, "occurrence.tab":8961, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":{"sec":47.69, "min":0.79, "hr":0.01}}
21_final	Mon 2020-09-14 12:23:34 PM	{"agent.tab":743, "measurement_or_fact.tab":7094, "media_resource.tab":8138, "occurrence.tab":7094, "reference.tab":5353, "taxon.tab":2283, "vernacular_name.tab":2090, "time_elapsed":{"sec":47.03, "min":0.78, "hr":0.01}}

617_final	        Mon 2020-09-07 11:41:14 PM	{"measurement_or_fact.tab":818305, "occurrence.tab":818305, "taxon.tab":410005, "time_elapsed":{"sec":596.04, "min":9.93, "hr":0.17}}
wikipedia_en_traits	Mon 2020-09-07 11:51:11 PM	{"measurement_or_fact.tab":818305, "occurrence.tab":818305, "taxon.tab":160598, "time_elapsed":false}

617_final	Tue 2020-09-08 02:05:06 AM	        {"measurement_or_fact.tab":818305, "occurrence.tab":818305, "taxon.tab":410005, "time_elapsed":{"sec":598.92, "min":9.98, "hr":0.17}}
wikipedia_en_traits	Tue 2020-09-08 02:15:01 AM	{"measurement_or_fact.tab":818305, "occurrence.tab":818305, "taxon.tab":160598, "time_elapsed":false}

617_final	Tue 2020-09-08 11:27:07 PM	        {"measurement_or_fact.tab":818305, "occurrence.tab":818305, "taxon.tab":410005, "time_elapsed":{"sec":600.27, "min":10, "hr":0.17}}
wikipedia_en_traits	Tue 2020-09-08 11:37:05 PM	{"measurement_or_fact.tab":818305, "occurrence.tab":818305, "taxon.tab":160598, "time_elapsed":false}

617_final	Mon 2020-09-14 05:26:31 AM	        {"measurement_or_fact.tab":818251, "occurrence.tab":818251, "taxon.tab":410005, "time_elapsed":{"sec":597.53, "min":9.96, "hr":0.17}}
wikipedia_en_traits	Mon 2020-09-14 05:36:27 AM	{"measurement_or_fact.tab":818251, "occurrence.tab":818251, "taxon.tab":160591, "time_elapsed":false}

Started cleaning eol_tags.tsv and eol_tags_noParentTerms.tsv
617_final	Mon 2020-09-14 01:02:29 PM	        {"measurement_or_fact.tab":509013, "occurrence.tab":509013, "taxon.tab":410005, "time_elapsed":{"sec":433.21, "min":7.22, "hr":0.12}}
wikipedia_en_traits	Mon 2020-09-14 01:08:45 PM	{"measurement_or_fact.tab":509013, "occurrence.tab":509013, "taxon.tab":160580, "time_elapsed":false}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$task = $param['task'];
$resource = @$param['resource'];

/* during development only. Not part of main operation
require_library('connectors/Environments2EOLAPI');
$func = new Environments2EOLAPI($param);
// $func->clean_eol_tags_tsv(); //works OK
$func->clean_noParentTerms(); //works OK
exit("\n-end-\n");
*/

if($task == 'generate_eol_tags') {                      //step 1
    $param['resource_id'] .= "_ENV"; //e.g. 21_ENV
    require_library('connectors/Environments2EOLAPI');
    $func = new Environments2EOLAPI($param);
    $func->generate_eol_tags($resource);
}
elseif($task == 'apply_formats_filters') {              //step 2
    $param['resource_id'] .= "_ENVO";
    $resource_id = $param['resource_id']; //e.g. 21_ENVO
    $old_resource_id = substr($resource_id, 0, strlen($resource_id)-1); //should get "21_ENV"
    $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$old_resource_id.'.tar.gz';
    $dwca_file = '/u/scripts/eol_php_code/applications/content_server/resources/'.$old_resource_id.'.tar.gz';
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    $preferred_rowtypes = array();
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence');
    // $excluded_rowtypes will be processed in EnvironmentsFilters.php which will be called from DwCA_Utility.php
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
elseif($task == 'apply_formats_filters_latest') {       //step 3
    $param['resource_id'] .= "_final";
    $resource_id = $param['resource_id']; //e.g. 21_final
    $old_resource_id = str_replace('_final', '_ENVO', $resource_id); //e.g. 21_ENVO
    if(Functions::is_production()) $dwca_file = '/u/scripts/eol_php_code/applications/content_server/resources/'.$old_resource_id.'.tar.gz';
    else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$old_resource_id.'.tar.gz';
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    $preferred_rowtypes = array();
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence', 
                               'http://rs.tdwg.org/dwc/terms/taxon');
    // $excluded_rowtypes will be processed in New_EnvironmentsEOLDataConnector.php which will be called from DwCA_Utility.php. Part of legacy filters.
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>