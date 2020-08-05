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
php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags", "resource":"AmphibiaWeb text", "resource_id":"21_ENV", "subjects":"Distribution"}'
php update_resources/connectors/environments_2_eol.php _ '{"task": "apply_formats_filters", "resource_id":"21_ENVO"}'
php update_resources/connectors/environments_2_eol.php _ '{"task": "apply_formats_filters_latest", "resource_id":"21_final"}'
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$task = $param['task'];
$resource = @$param['resource'];

if($task == 'generate_eol_tags') {                      //step 1
    require_library('connectors/Environments2EOLAPI');
    $func = new Environments2EOLAPI($param);
    $func->generate_eol_tags($resource);
}
elseif($task == 'apply_formats_filters') {              //step 2
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