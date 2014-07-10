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
// test LifeDesk
$params["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/eol-partnership.xml.gz";
$params["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/sample.bib";
$params["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/nemertea/file_importer_image_xls%20(1).xls";
$params["name"]              = "test";
*/

/*
// Nemertea remote
$params["lifedesk"]          = "http://nemertea.lifedesks.org/eol-partnership.xml.gz";
$params["bibtex_file"]       = "http://nemertea.lifedesks.org/biblio/export/bibtex/";
$params["scratchpad_images"] = "";
$params["name"]              = "nemertea";

// Nemertea Dropbox
$params["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/nemertea/eol-partnership.xml.gz";
$params["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/nemertea/Biblio-Bibtex.bib";
$params["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/nemertea/file_importer_image_xls%20(1).xls";
$params["name"]              = "nemertea";

// Nemertea local
$params["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/nemertea/eol-partnership.xml.gz";
$params["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/nemertea/Biblio-Bibtex.bib";
$params["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/nemertea/file_importer_image_xls%20(1).xls";
$params["name"]              = "nemertea";
*/

/*
// Peracarida remote
$params["lifedesk"]          = "http://peracarida.lifedesks.org/eol-partnership.xml.gz";
$params["bibtex_file"]       = ""; // no bibtex file
$params["scratchpad_images"] = "";
$params["name"]              = "peracarida";

// Peracarida Dropbox
$params["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/peracarida/eol-partnership.xml.gz";
$params["bibtex_file"]       = ""; // no bibtex file
$params["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/peracarida/file_importer_image_xls%20(3).xls";
$params["name"]              = "peracarida";

// Peracarida local
$params["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/peracarida/eol-partnership.xml.gz";
$params["bibtex_file"]       = ""; // no bibtex file
$params["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/peracarida/file_importer_image_xls%20(3).xls";
$params["name"]              = "peracarida";
*/

$params = array();
/* 
paste here which Lifedesk you want to export 
*/
if($params) $func->export_lifedesk_to_scratchpad($params);
else echo "\nNothing to process. Program will terminate\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>