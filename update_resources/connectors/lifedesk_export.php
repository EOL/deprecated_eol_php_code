<?php
namespace php_active_record;
/* LifeDesk to Scratchpad migration
estimated execution time:
This generates a gzip file in: DOC_ROOT/tmp/ folder
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/LifeDeskToScratchpadAPI');
$timestart = time_elapsed();
$func = new LifeDeskToScratchpadAPI();

/* 
test LifeDesk
$params["lifedesk"]      = "http://localhost/~eolit/cp/LD2Scratchpad/eol-partnership.xml.gz";
$params["bibtex_file"]   = "http://localhost/~eolit/cp/LD2Scratchpad/sample.bib";
$params["name"]          = "test";

Nemertea LifeDesk local
$params["lifedesk"]      = "http://localhost/~eolit/cp/LD2Scratchpad/nemertea/eol-partnership.xml.gz";
$params["bibtex_file"]   = "http://localhost/~eolit/cp/LD2Scratchpad/nemertea/Biblio-Bibtex.bib";
$params["name"]          = "nemertea";

Nemertea LifeDesk remote
$params["lifedesk"]      = "http://nemertea.lifedesks.org/eol-partnership.xml.gz";
$params["bibtex_file"]   = "http://nemertea.lifedesks.org/biblio/export/bibtex/";
$params["name"]          = "nemertea";
*/

// /* Nemertea LifeDesk Dropbox
$params["lifedesk"]      = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/nemertea/eol-partnership.xml.gz";
$params["bibtex_file"]   = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/nemertea/Biblio-Bibtex.bib";
$params["name"]          = "nemertea";
// */

$func->export_lifedesk_to_scratchpad($params);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>