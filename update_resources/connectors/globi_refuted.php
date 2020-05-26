<?php
namespace php_active_record;
/* DATA-1854
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// /* //main operation
require_library('connectors/DwCA_Utility');
$resource_id = "globi_associations_refuted";
$dwca = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/globi_associations.tar.gz';
$func = new DwCA_Utility($resource_id, $dwca);

/*reminder upper-case used in meta.xml e.g. 'http://rs.tdwg.org/dwc/terms/Taxon', 'http://eol.org/schema/reference/Reference' */
$preferred_rowtypes = array(); //was forced to lower case in DwCA_Utility.php

$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, true, true, $timestart);
// */
?>