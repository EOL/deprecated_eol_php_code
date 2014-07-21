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
// ==================================================================================================
// Nemertea remote
$params["nemertea"]["remote"]["lifedesk"]          = "http://nemertea.lifedesks.org/eol-partnership.xml.gz";
$params["nemertea"]["remote"]["bibtex_file"]       = "http://nemertea.lifedesks.org/biblio/export/bibtex/";
$params["nemertea"]["remote"]["scratchpad_images"] = "";
$params["nemertea"]["remote"]["name"]              = "nemertea";
// Nemertea Dropbox
$params["nemertea"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/nemertea/eol-partnership.xml.gz";
$params["nemertea"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/nemertea/Biblio-Bibtex.bib";
$params["nemertea"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/nemertea/file_importer_image_xls%20(1).xls";
$params["nemertea"]["dropbox"]["name"]              = "nemertea";
// Nemertea local
$params["nemertea"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/nemertea/eol-partnership.xml.gz";
$params["nemertea"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/nemertea/Biblio-Bibtex.bib";
$params["nemertea"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/nemertea/file_importer_image_xls%20(1).xls";
$params["nemertea"]["local"]["name"]              = "nemertea";
// ==================================================================================================
// Peracarida remote
$params["peracarida"]["remote"]["lifedesk"]          = "http://peracarida.lifedesks.org/eol-partnership.xml.gz";
$params["peracarida"]["remote"]["bibtex_file"]       = ""; // no bibtex file
$params["peracarida"]["remote"]["scratchpad_images"] = "";
$params["peracarida"]["remote"]["name"]              = "peracarida";
// Peracarida Dropbox
$params["peracarida"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/peracarida/eol-partnership.xml.gz";
$params["peracarida"]["dropbox"]["bibtex_file"]       = ""; // no bibtex file
$params["peracarida"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/peracarida/file_importer_image_xls%20(3).xls";
$params["peracarida"]["dropbox"]["name"]              = "peracarida";
// Peracarida local
$params["peracarida"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/peracarida/eol-partnership.xml.gz";
$params["peracarida"]["local"]["bibtex_file"]       = ""; // no bibtex file
$params["peracarida"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/peracarida/file_importer_image_xls%20(3).xls";
$params["peracarida"]["local"]["name"]              = "peracarida";
// ==================================================================================================
// Syrphidae remote
$params["syrphidae"]["remote"]["lifedesk"]          = "http://syrphidae.lifedesks.org/eol-partnership.xml.gz";
$params["syrphidae"]["remote"]["bibtex_file"]       = "http://syrphidae.lifedesks.org/biblio/export/bibtex/";
$params["syrphidae"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/syrphidae/file_importer_image_xls.xls";
$params["syrphidae"]["remote"]["name"]              = "syrphidae";
// Syrphidae Dropbox
$params["syrphidae"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/syrphidae/eol-partnership.xml.gz";
$params["syrphidae"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/syrphidae/Biblio-Bibtex.bib";
$params["syrphidae"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/syrphidae/file_importer_image_xls.xls";
$params["syrphidae"]["dropbox"]["name"]              = "syrphidae";
// Syrphidae local
$params["syrphidae"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/syrphidae/eol-partnership.xml.gz";
$params["syrphidae"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/syrphidae/Biblio-Bibtex.bib";
$params["syrphidae"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/syrphidae/file_importer_image_xls.xls";
$params["syrphidae"]["local"]["name"]              = "syrphidae";
// ==================================================================================================
// Tunicata remote
$params["tunicata"]["remote"]["lifedesk"]          = "http://tunicata.lifedesks.org/eol-partnership.xml.gz";
$params["tunicata"]["remote"]["bibtex_file"]       = "http://tunicata.lifedesks.org/biblio/export/bibtex/";
$params["tunicata"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/tunicata/file_importer_image_xls%20%25281%2529%20(1).xls";
$params["tunicata"]["remote"]["name"]              = "tunicata";
// Tunicata Dropbox
$params["tunicata"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/tunicata/eol-partnership.xml.gz";
$params["tunicata"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/tunicata/Biblio-Bibtex.bib";
$params["tunicata"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/tunicata/file_importer_image_xls%20%25281%2529%20(1).xls";
$params["tunicata"]["dropbox"]["name"]              = "tunicata";
// Tunicata local
$params["tunicata"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/tunicata/eol-partnership.xml.gz";
$params["tunicata"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/tunicata/Biblio-Bibtex.bib";
$params["tunicata"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/tunicata/file_importer_image_xls%20%25281%2529%20(1).xls";
$params["tunicata"]["local"]["name"]              = "tunicata";
// ==================================================================================================
// Leptogastrinae remote
$params["leptogastrinae"]["remote"]["lifedesk"]          = "http://leptogastrinae.lifedesks.org/eol-partnership.xml.gz";
$params["leptogastrinae"]["remote"]["bibtex_file"]       = "http://leptogastrinae.lifedesks.org/biblio/export/bibtex/";
$params["leptogastrinae"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/leptogastrinae/file_importer_image_xls%20%25281%2529.xls";
$params["leptogastrinae"]["remote"]["name"]              = "leptogastrinae";
// Leptogastrinae Dropbox
$params["leptogastrinae"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/leptogastrinae/eol-partnership.xml.gz";
$params["leptogastrinae"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/leptogastrinae/Biblio-Bibtex.bib";
$params["leptogastrinae"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/leptogastrinae/file_importer_image_xls%20%25281%2529.xls";
$params["leptogastrinae"]["dropbox"]["name"]              = "leptogastrinae";
// Leptogastrinae local
$params["leptogastrinae"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/leptogastrinae/eol-partnership.xml.gz";
$params["leptogastrinae"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/leptogastrinae/Biblio-Bibtex.bib";
$params["leptogastrinae"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/leptogastrinae/file_importer_image_xls%20%25281%2529.xls";
$params["leptogastrinae"]["local"]["name"]              = "leptogastrinae";
// ==================================================================================================

/* paste here which Lifedesk you want to export: e.g. $parameters = $params["leptogastrinae"]["local"]; */
$parameters = $params["leptogastrinae"]["dropbox"];
if($parameters) $func->export_lifedesk_to_scratchpad($parameters);
else echo "\nNothing to process. Program will terminate\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>