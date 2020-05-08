<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. 

wikipedia_combined_languages	    Friday 2020-05-08 06:21:06 AM	{"media_resource.tab":99277, "taxon.tab":25775, "time_elapsed":{"sec":576.91, "min":9.62, "hr":0.16}}
wikipedia_combined_languages_batch2	Friday 2020-05-08 06:22:23 AM	{"media_resource.tab":3033, "taxon.tab":3366, "time_elapsed":{"sec":653.4, "min":10.89, "hr":0.18}}

wget https://editors.eol.org/eol_php_code/applications/content_server/resources/wikipedia_combined_languages.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/wikipedia_combined_languages_batch2.tar.gz

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/DwCA_Aggregator');
$langs = array();
$langs[1] = array('ta', 'ceb', 'el', 'mk', 'ky', 'sco', 'hi', 'fy', 'tl', 'jv', 'ia', 'be-x-old', 'oc', 'qu', 'ne', 'koi', 'frr', 'udm', 'ba', 'an', 'zh-min-nan', 'sw', 'ku', 'uz', 'te', 
               'bs', 'io', 'my', 'mn', 'kv', 'lb', 'su', 'kn', 'tt', 'co', 'sq', 'csb', 'mr', 'fo', 'os', 'cv', 'kab', 'sah', 'nds', 'lmo', 'pa', 'wa', 'vls', 'gv', 'wuu', 'mi', 'nah', 
               'dsb', 'kbd', 'to', 'mdf', 'li', 'as', 'bat-smg', 'olo', 'mhr', 'tg', 'pcd', 'ps', 'sd', 'vep', 'se', 'am', 'si', 'ht', 'gn', 'rue', 'mt', 'gu', 'ckb', 'als', 
               'or', 'bh', 'myv', 'scn', 'gd', 'dv', 'pam', 'xmf', 'cdo', 'bar', 'nap', 'lfn', 'nds-nl', 'bo', 'stq', 'inh', 'lbe', 'ha');

$langs[2] = array('lij', 'lez', 'sa', 'yi', 'ace', 'diq', 'ce', 'yo', 'rw', 'vec', 'sc', 'ln', 'hak_eli', 'kw', 'bcl', 'za', 'ang', 'eml', 'av', 'chy', 'fj', 'ik', 'ug', 'zea', 'bxr', 'zh-classical', 'bjn', 'so', 'arz', 'mwl', 'sn', 'chr', 
               'mai', 'tk', 'tcy', 'szy', 'mzn', 'wo', 'ab', 'ban', 'ay', 'tyv', 'atj', 'new', 'fiu-vro', 'mg', 'rm', 'ltg', 'ext', 'kl', 'roa-rup', 'nrm', 'rn', 'dty', 'hyw', 'lo', 'kg', 
               'km', 'gom', 'frp', 'sat', 'gan', 'haw', 'hif', 'nso', 'xal', 'mnw', 'zu', 'bi', 'lad', 'map-bms', 'roa-tara', 'pdc', 'kbp', 'jbo', 'kaa', 'srn', 'vo', 'gag', 'ty', 'fur', 
               'ie', 'lg', 'ts', 'bpy', 'iu', 'arc', 'gor', 'nov', 'crh', 'tum', 'glk', 'krc', 'ksh', 'na', 'ny', 'pfl', 'xh', 'tpi', 'cr', 'gcr', 'jam', 'ak', 'bm', 'cu', 'ks', 'pap', 
               'got', 'ee', 'ady', 'pih', 'ki', 'shn', 'pi', 'sm', 'ti', 've', 'ch', 'ig', 'lrc', 'om', 'st', 'din', 'ss', 'tet', 'sg', 'ff', 'pnt', 'tn', 'cbk-zam', 'rmy', 'bug', 'data', 
               'dz', 'nqo', 'mh', 'tw');

// $langs = array('mk'); //for testing

for ($x = 1; $x <= 2; $x++) {
    if($x == 1)     $resource_id = 'wikipedia_combined_languages';
    elseif($x == 2) $resource_id = 'wikipedia_combined_languages_batch2';
    echo "\nProcessing [$resource_id] languages:[".count($langs[$x])."]...\n";
    $func = new DwCA_Aggregator($resource_id);
    $func->combine_wikipedia_DwCAs($langs[$x]);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>