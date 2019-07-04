<?php
namespace php_active_record;
/* DATA-1812 

globi_associations	Monday 2019-07-01 09:53:16 AM	{"association.tab":3097482,"occurrence_specific.tab":2288584,"reference.tab":327413,"taxon.tab":215828}
globi_associations	Thursday 2019-07-04 06:20:42 AM	{"association.tab":3097726,"occurrence_specific.tab":2288805,"reference.tab":327528,"taxon.tab":215846}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* //main operation
require_library('connectors/DwCA_Utility');
$resource_id = "globi_associations";
$dwca = 'https://depot.globalbioticinteractions.org/snapshot/target/eol-globi-datasets-1.0-SNAPSHOT-darwin-core-aggregated.zip';
// $dwca = 'http://localhost/cp/GloBI_2019/eol-globi-datasets-1.0-SNAPSHOT-darwin-core-aggregated.zip';
$func = new DwCA_Utility($resource_id, $dwca);

$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/Taxon', 'http://eol.org/schema/reference/Reference'); //orig in partners meta XML. Overwritten below.
$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/reference/reference'); //was forced to lower case in DwCA_Utility.php

$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, true, true);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
