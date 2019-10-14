<?php
namespace php_active_record;
/* Global Register of Introduced and Invasive Species : DATA-1838
e.g. Belgium
https://www.gbif.org/dataset/6d9e952f-948c-4483-9807-575348147c7e
https://api.gbif.org/v1/dataset/6d9e952f-948c-4483-9807-575348147c7e/document

Hi Jen,
Attached [dataset_comparison.txt].
Here is a script-generated report comparing South Africa to the other 122 datasets.
- Only Belgium has an extra extension (http://rs.gbif.org/terms/1.0/description)
- The script notes of extra extension and extra fields in each extension in comparison with that of South Africa.
Thanks.

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GlobalRegister_IntroducedInvasiveSpecies');
$timestart = time_elapsed();
$cmdline_params['jenkins_or_cron'] = @$argv[1]; //irrelevant here

/* test
$south = array('a','b','c');
$belgium = array('a','b','c','d','e');
$diff = array_diff($belgium, $south); //proper
// $diff = array_diff($south, $belgium); //can be used to check what is in others but South Africa doesn't have
print_r($diff); exit;
*/

// remote
$params["dwca_file"]     = "https://editors.eol.org/other_files/GBIF_DwCA/xxx.zip";

// e.g.
// Belgium -- https://ipt.inbo.be/archive.do?r=unified-checklist
// South Africa -- http://ipt.ala.org.au/archive.do?r=south-africa-griis-gbif

$resource_id = 'griis'; //Global Register of Introduced and Invasive Species
$func = new GlobalRegister_IntroducedInvasiveSpecies($resource_id);
$func->compare_meta_between_datasets(); //a utility to generate report for Jen

// $func->start($params);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>