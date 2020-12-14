<?php
namespace php_active_record;
/* 
A new method based here: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65425&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65425

Baed on copied templates:
remove_Aves_children_from_368.php   => filter_term_group_by_taxa.php
RemoveAvesChildrenAPI.php           => FilterTermGroupByTaxa.php

Implement:
normal operation:
php update_resources/connectors/filter_term_group_by_taxa.php _ '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1390, Q1357, Q10908"}'
during dev:
php update_resources/connectors/filter_term_group_by_taxa.php _ '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1357"}'
php update_resources/connectors/filter_term_group_by_taxa.php _ '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1390"}'
php update_resources/connectors/filter_term_group_by_taxa.php _ '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q10908"}'


Jenkins:
php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1357"}'
php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q1390"}'
php5.6 filter_term_group_by_taxa.php jenkins '{"source": "617_ENV", "target":"wikipedia_en_traits_FTG", "taxonIDs": "Q10908"}'

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','8096M');
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$source = $param['source'];         //e.g. '617_ENV'
$resource_id = $param['target'];    //e.g. 'wikipedia_en_traits_FTG' FTG stands for 'filter term group'.
$dwca_file = CONTENT_RESOURCE_LOCAL_PATH . "/$source" . ".tar.gz"; //$source e.g. "617_ENV.tar.gz"
process_resource_url($dwca_file, $resource_id, $timestart, $param);

function process_resource_url($dwca_file, $resource_id, $timestart, $param)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file, $param);

    /* Orig in meta.xml has capital letters. Just a note reminder.
    rowType="http://rs.tdwg.org/dwc/terms/Taxon">
    rowType="http://rs.tdwg.org/dwc/terms/Occurrence">
    rowType="http://rs.tdwg.org/dwc/terms/MeasurementOrFact">
    rowType="http://rs.gbif.org/terms/1.0/VernacularName">
    */

    $preferred_rowtypes = array(); //no prefered. All will be customized
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence');
    /* during dev
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence', 'http://rs.tdwg.org/dwc/terms/taxon');
    */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    unset($func);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //, true, true
}
?>