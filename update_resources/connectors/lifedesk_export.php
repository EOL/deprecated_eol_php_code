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
$params["nemertea"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/nemertea/file_importer_image_xls%20(1).xls";
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
// continenticola remote
$params["continenticola"]["remote"]["lifedesk"]          = "http://continenticola.lifedesks.org/eol-partnership.xml.gz";
$params["continenticola"]["remote"]["bibtex_file"]       = "http://continenticola.lifedesks.org/biblio/export/bibtex/";
$params["continenticola"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/continenticola/file_importer_image_xls.xls";
$params["continenticola"]["remote"]["name"]              = "continenticola";
// continenticola Dropbox
$params["continenticola"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/continenticola/eol-partnership.xml.gz";
$params["continenticola"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/continenticola/Biblio-Bibtex.bib";
$params["continenticola"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/continenticola/file_importer_image_xls.xls";
$params["continenticola"]["dropbox"]["name"]              = "continenticola";
// continenticola local
$params["continenticola"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/continenticola/eol-partnership.xml.gz";
$params["continenticola"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/continenticola/Biblio-Bibtex.bib";
$params["continenticola"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/continenticola/file_importer_image_xls.xls";
$params["continenticola"]["local"]["name"]              = "continenticola";
// ==================================================================================================
// pelagics remote
$params["pelagics"]["remote"]["lifedesk"]          = "http://pelagics.lifedesks.org/eol-partnership.xml.gz";
$params["pelagics"]["remote"]["bibtex_file"]       = "http://pelagics.lifedesks.org/biblio/export/bibtex/";
$params["pelagics"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/pelagics/file_importer_image_xls%20(1).xls";
$params["pelagics"]["remote"]["name"]              = "pelagics";
// pelagics Dropbox
$params["pelagics"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/pelagics/eol-partnership.xml.gz";
$params["pelagics"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/pelagics/Biblio-Bibtex.bib";
$params["pelagics"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/pelagics/file_importer_image_xls%20(1).xls";
$params["pelagics"]["dropbox"]["name"]              = "pelagics";
// pelagics local
$params["pelagics"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/pelagics/eol-partnership.xml.gz";
$params["pelagics"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/pelagics/Biblio-Bibtex.bib";
$params["pelagics"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/pelagics/file_importer_image_xls%20(1).xls";
$params["pelagics"]["local"]["name"]              = "pelagics";
// ==================================================================================================
// parmotrema remote
$params["parmotrema"]["remote"]["lifedesk"]          = "http://parmotrema.lifedesks.org/eol-partnership.xml.gz";
$params["parmotrema"]["remote"]["bibtex_file"]       = "http://parmotrema.lifedesks.org/biblio/export/bibtex/";
$params["parmotrema"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/parmotrema/file_importer_image_xls%20(1).xls";
$params["parmotrema"]["remote"]["name"]              = "parmotrema";
// parmotrema Dropbox
$params["parmotrema"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/parmotrema/eol-partnership.xml.gz";
$params["parmotrema"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/parmotrema/Biblio-Bibtex.bib";
$params["parmotrema"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/parmotrema/file_importer_image_xls%20(1).xls";
$params["parmotrema"]["dropbox"]["name"]              = "parmotrema";
// parmotrema local
$params["parmotrema"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/parmotrema/eol-partnership.xml.gz";
$params["parmotrema"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/parmotrema/Biblio-Bibtex.bib";
$params["parmotrema"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/parmotrema/file_importer_image_xls%20(1).xls";
$params["parmotrema"]["local"]["name"]              = "parmotrema";
// ==================================================================================================
// liquensbr remote
$params["liquensbr"]["remote"]["lifedesk"]          = "http://liquensbr.lifedesks.org/eol-partnership.xml.gz";
$params["liquensbr"]["remote"]["bibtex_file"]       = ""; // no bibtex
$params["liquensbr"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/liquensbr/file_importer_image_xls%20(1).xls";
$params["liquensbr"]["remote"]["name"]              = "liquensbr";
// liquensbr Dropbox
$params["liquensbr"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/liquensbr/eol-partnership.xml.gz";
$params["liquensbr"]["dropbox"]["bibtex_file"]       = ""; // no bibtex
$params["liquensbr"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/liquensbr/file_importer_image_xls%20(1).xls";
$params["liquensbr"]["dropbox"]["name"]              = "liquensbr";
// liquensbr local
$params["liquensbr"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/liquensbr/eol-partnership.xml.gz";
$params["liquensbr"]["local"]["bibtex_file"]       = ""; // no bibtex
$params["liquensbr"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/liquensbr/file_importer_image_xls%20(1).xls";
$params["liquensbr"]["local"]["name"]              = "liquensbr";
// ==================================================================================================
// liquensms remote
$params["liquensms"]["remote"]["lifedesk"]          = "http://liquensms.lifedesks.org/eol-partnership.xml.gz";
$params["liquensms"]["remote"]["bibtex_file"]       = ""; // no bibtex
$params["liquensms"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/liquensms/file_importer_image_xls%20(2).xls";
$params["liquensms"]["remote"]["name"]              = "liquensms";
// liquensms Dropbox
$params["liquensms"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/liquensms/eol-partnership.xml.gz";
$params["liquensms"]["dropbox"]["bibtex_file"]       = ""; // no bibtex
$params["liquensms"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/liquensms/file_importer_image_xls%20(2).xls";
$params["liquensms"]["dropbox"]["name"]              = "liquensms";
// liquensms local
$params["liquensms"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/liquensms/eol-partnership.xml.gz";
$params["liquensms"]["local"]["bibtex_file"]       = ""; // no bibtex
$params["liquensms"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/liquensms/file_importer_image_xls%20(2).xls";
$params["liquensms"]["local"]["name"]              = "liquensms";
// ==================================================================================================
// staurozoa remote
$params["staurozoa"]["remote"]["lifedesk"]          = "http://staurozoa.lifedesks.org/eol-partnership.xml.gz";
$params["staurozoa"]["remote"]["bibtex_file"]       = "http://staurozoa.lifedesks.org/biblio/export/bibtex/";
$params["staurozoa"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/staurozoa/file_importer_image_xls%20(1).xls";
$params["staurozoa"]["remote"]["name"]              = "staurozoa";
// staurozoa Dropbox
$params["staurozoa"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/staurozoa/eol-partnership.xml.gz";
$params["staurozoa"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/staurozoa/Biblio-Bibtex.bib";
$params["staurozoa"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/staurozoa/file_importer_image_xls%20(1).xls";
$params["staurozoa"]["dropbox"]["name"]              = "staurozoa";
// staurozoa local
$params["staurozoa"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/staurozoa/eol-partnership.xml.gz";
$params["staurozoa"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/staurozoa/Biblio-Bibtex.bib";
$params["staurozoa"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/staurozoa/file_importer_image_xls%20(1).xls";
$params["staurozoa"]["local"]["name"]              = "staurozoa";
// ==================================================================================================
// cnidaria remote
$params["cnidaria"]["remote"]["lifedesk"]          = "http://cnidaria.lifedesks.org/eol-partnership.xml.gz";
$params["cnidaria"]["remote"]["bibtex_file"]       = "http://cnidaria.lifedesks.org/biblio/export/bibtex/";
$params["cnidaria"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/cnidaria/file_importer_image_xls%20(2).xls";
$params["cnidaria"]["remote"]["name"]              = "cnidaria";
// cnidaria Dropbox
$params["cnidaria"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/cnidaria/eol-partnership.xml.gz";
$params["cnidaria"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/cnidaria/Biblio-Bibtex.bib";
$params["cnidaria"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/cnidaria/file_importer_image_xls%20(2).xls";
$params["cnidaria"]["dropbox"]["name"]              = "cnidaria";
// cnidaria local
$params["cnidaria"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/cnidaria/eol-partnership.xml.gz";
$params["cnidaria"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/cnidaria/Biblio-Bibtex.bib";
$params["cnidaria"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/cnidaria/file_importer_image_xls%20(2).xls";
$params["cnidaria"]["local"]["name"]              = "cnidaria";
// ==================================================================================================
// porifera remote
$params["porifera"]["remote"]["lifedesk"]          = "http://porifera.lifedesks.org/eol-partnership.xml.gz";
$params["porifera"]["remote"]["bibtex_file"]       = "http://porifera.lifedesks.org/biblio/export/bibtex/";
$params["porifera"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/porifera/file_importer_image_xls%20(1).xls";
$params["porifera"]["remote"]["name"]              = "porifera";
// porifera Dropbox
$params["porifera"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/porifera/eol-partnership.xml.gz";
$params["porifera"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/porifera/Biblio-Bibtex.bib";
$params["porifera"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/porifera/file_importer_image_xls%20(1).xls";
$params["porifera"]["dropbox"]["name"]              = "porifera";
// porifera local
$params["porifera"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/porifera/eol-partnership.xml.gz";
$params["porifera"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/porifera/Biblio-Bibtex.bib";
$params["porifera"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/porifera/file_importer_image_xls%20(1).xls";
$params["porifera"]["local"]["name"]              = "porifera";
// ==================================================================================================

/* paste here which Lifedesk you want to export: e.g. $parameters = $params["leptogastrinae"]["dropbox"];
the export files (in .tar.gz) will be found in: DOC_ROOT/tmp/leptogastrinae_LD_to_Scratchpad_export.tar.gz
*/
$parameters = $params["staurozoa"]["dropbox"];
if($parameters) $func->export_lifedesk_to_scratchpad($parameters);
else echo "\nNothing to process. Program will terminate\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>