<?php
namespace php_active_record;
/*
Mexican Amphibians (DATA-1560)
Partner provides an archive file, but needs adjustments:

- in meta.xml, change entry "http://rs.tdwg.org/dwc/text/tdwg_dwc_text.xsd" to "http://services.eol.org/schema/dwca/tdwg_dwc_text.xsd".
- in meta.xml, change entry "furtherinformationURL" to "furtherInformationURL", case sensitive here.

estimated execution time: 1.2 minutes
                            13Jan
measurement_or_fact.tab:    [1909]
occurrence.tab:             [815]
reference                   [113]
taxon.tab:                  [370]
*/

return; // Anne is now uploading file directly to the resource account, connector temporarily disabled
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MexicanAmphibiansAPI');
$timestart = time_elapsed();

$resource_id = 856;
/* history:
$params["dwca_file"] = "http://localhost/~eolit/cp/MexicanAmphibians/Mex_Amph.zip";                     // raw from Anne Thessen
$params["dwca_file"] = "http://localhost/~eolit/cp/MexicanAmphibians/Mex_Amph/Archive.zip";             // adjusted meta XML
$params["dwca_file"] = "https://dl.dropboxusercontent.com/u/7597512/MexicanAmphibians/Archive.zip";
*/
$params["dwca_file"] = "http://localhost/~eolit/cp/MexicanAmphibians/877.zip";                          //new resource from Anne
$params["dwca_file"] = "https://dl.dropboxusercontent.com/u/7597512/MexicanAmphibians/877.zip";

$func = new MexicanAmphibiansAPI($resource_id, $params);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>