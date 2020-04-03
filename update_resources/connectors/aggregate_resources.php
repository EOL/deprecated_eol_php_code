<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

$resource_id = 'wikipedia_combined_languages';
require_library('connectors/DwCA_Aggregator');
$func = new DwCA_Aggregator($resource_id);
$langs = array('ta', 'ceb', 'el', 'mk', 'ky', 'sco', 'hi', 'fy', 'tl', 'jv', 'ia', 'be-x-old', 'oc', 'qu', 'ne', 'koi', 'frr', 'udm', 'ba', 'an', 'zh-min-nan', 'sw', 'ku', 'uz', 'te', 
               'bs', 'io', 'my', 'mn', 'kv', 'lb', 'su', 'kn', 'tt', 'co', 'sq', 'csb', 'mr', 'fo', 'os', 'cv', 'kab', 'sah', 'nds', 'lmo', 'pa', 'wa', 'vls', 'gv', 'wuu', 'mi', 'nah', 
               'dsb', 'kbd', 'to', 'mdf', 'li', 'as', 'bat-smg', 'olo', 'mhr', 'tg', 'pcd', 'ps', 'sd', 'vep', 'se', 'am', 'si', 'ht', 'gn', 'rue', 'mt', 'gu', 'ckb', 'als');

// $langs = array('mk'); //for testing
$func->combine_wikipedia_DwCAs($langs);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>