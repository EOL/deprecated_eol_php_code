<?php
namespace php_active_record;
/* LifeDesk to Scratchpad migration
estimated execution time:
This generates a gzip file in: DOC_ROOT/tmp/ folder
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/LifeDeskToScratchpadAPI');
$timestart = time_elapsed();
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
$params["syrphidae"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/syrphidae/syrphidae.xls";
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
$params["tunicata"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/tunicata/tunicata.xls";
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
$params["leptogastrinae"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/leptogastrinae/leptogastrinae.xls";
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
$params["continenticola"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/continenticola/continenticola.xls";
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
$params["pelagics"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/pelagics/pelagics.xls";
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
$params["staurozoa"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/staurozoa/staurozoa.xls";
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
$params["cnidaria"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/cnidaria/cnidaria.xls";
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
$params["porifera"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/porifera/porifera.xls";
// ==================================================================================================
// sacoglossa remote
$params["sacoglossa"]["remote"]["lifedesk"]          = "http://sacoglossa.lifedesks.org/eol-partnership.xml.gz";
$params["sacoglossa"]["remote"]["bibtex_file"]       = "http://sacoglossa.lifedesks.org/biblio/export/bibtex/";
$params["sacoglossa"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/sacoglossa/file_importer_image_xls%20(2).xls";
$params["sacoglossa"]["remote"]["name"]              = "sacoglossa";
// sacoglossa Dropbox
$params["sacoglossa"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/sacoglossa/eol-partnership.xml.gz";
$params["sacoglossa"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/sacoglossa/Biblio-Bibtex.bib";
$params["sacoglossa"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/sacoglossa/file_importer_image_xls%20(2).xls";
$params["sacoglossa"]["dropbox"]["name"]              = "sacoglossa";
// sacoglossa local
$params["sacoglossa"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/sacoglossa/eol-partnership.xml.gz";
$params["sacoglossa"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/sacoglossa/Biblio-Bibtex.bib";
$params["sacoglossa"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/sacoglossa/file_importer_image_xls (2).xls";
$params["sacoglossa"]["local"]["name"]              = "sacoglossa";
// ==================================================================================================
// buccinids remote
$params["buccinids"]["remote"]["lifedesk"]          = "http://buccinids.lifedesks.org/eol-partnership.xml.gz";
$params["buccinids"]["remote"]["bibtex_file"]       = "http://buccinids.lifedesks.org/biblio/export/bibtex/";
$params["buccinids"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/buccinids/file_importer_image_xls%20(1).xls";
$params["buccinids"]["remote"]["name"]              = "buccinids";
// buccinids Dropbox
$params["buccinids"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/buccinids/eol-partnership.xml.gz";
$params["buccinids"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/buccinids/Biblio-Bibtex.bib";
$params["buccinids"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/buccinids/file_importer_image_xls%20(1).xls";
$params["buccinids"]["dropbox"]["name"]              = "buccinids";
// buccinids local
$params["buccinids"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/buccinids/eol-partnership.xml.gz";
$params["buccinids"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/buccinids/Biblio-Bibtex.bib";
$params["buccinids"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/buccinids/file_importer_image_xls%20(1).xls";
$params["buccinids"]["local"]["name"]              = "buccinids";
// ==================================================================================================
// apoidea remote
$params["apoidea"]["remote"]["lifedesk"]          = "http://apoidea.lifedesks.org/eol-partnership.xml.gz";
$params["apoidea"]["remote"]["bibtex_file"]       = "http://apoidea.lifedesks.org/biblio/export/bibtex/";
$params["apoidea"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/apoidea/file_importer_image_xls.xls";
$params["apoidea"]["remote"]["name"]              = "apoidea";
// apoidea Dropbox
$params["apoidea"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/apoidea/eol-partnership.xml.gz";
$params["apoidea"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/apoidea/Biblio-Bibtex.bib";
$params["apoidea"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/apoidea/file_importer_image_xls.xls";
$params["apoidea"]["dropbox"]["name"]              = "apoidea";
// apoidea local
$params["apoidea"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/apoidea/eol-partnership.xml.gz";
$params["apoidea"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/apoidea/Biblio-Bibtex.bib";
$params["apoidea"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/apoidea/file_importer_image_xls.xls";
$params["apoidea"]["local"]["name"]              = "apoidea";
$params["apoidea"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/apoidea/apoidea.xls";
// ==================================================================================================
// opisthostoma remote
$params["opisthostoma"]["remote"]["lifedesk"]          = "http://opisthostoma.lifedesks.org/eol-partnership.xml.gz";
$params["opisthostoma"]["remote"]["bibtex_file"]       = "http://opisthostoma.lifedesks.org/biblio/export/bibtex/";
$params["opisthostoma"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/opisthostoma/file_importer_image_xls%20(1).xls";
$params["opisthostoma"]["remote"]["name"]              = "opisthostoma";
// opisthostoma Dropbox
$params["opisthostoma"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/opisthostoma/eol-partnership.xml.gz";
$params["opisthostoma"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/opisthostoma/Biblio-Bibtex.bib";
$params["opisthostoma"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/opisthostoma/file_importer_image_xls%20(1).xls";
$params["opisthostoma"]["dropbox"]["name"]              = "opisthostoma";
// opisthostoma local
$params["opisthostoma"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/opisthostoma/eol-partnership.xml.gz";
$params["opisthostoma"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/opisthostoma/Biblio-Bibtex.bib";
$params["opisthostoma"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/opisthostoma/file_importer_image_xls (1).xls";
$params["opisthostoma"]["local"]["name"]              = "opisthostoma";
$params["opisthostoma"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/opisthostoma/opisthostoma.xls";
// ==================================================================================================
// borneanlandsnails remote
$params["borneanlandsnails"]["remote"]["lifedesk"]          = "http://borneanlandsnails.lifedesks.org/eol-partnership.xml.gz";
$params["borneanlandsnails"]["remote"]["bibtex_file"]       = "http://borneanlandsnails.lifedesks.org/biblio/export/bibtex/";
$params["borneanlandsnails"]["remote"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/borneanlandsnails/file_importer_image_xls%20(2).xls";
$params["borneanlandsnails"]["remote"]["name"]              = "borneanlandsnails";
// borneanlandsnails Dropbox
$params["borneanlandsnails"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/borneanlandsnails/eol-partnership.xml.gz";
$params["borneanlandsnails"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/borneanlandsnails/Biblio-Bibtex.bib";
$params["borneanlandsnails"]["dropbox"]["scratchpad_images"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/borneanlandsnails/file_importer_image_xls%20(2).xls";
$params["borneanlandsnails"]["dropbox"]["name"]              = "borneanlandsnails";
// borneanlandsnails local
$params["borneanlandsnails"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/borneanlandsnails/eol-partnership.xml.gz";
$params["borneanlandsnails"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/borneanlandsnails/Biblio-Bibtex.bib";
$params["borneanlandsnails"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/borneanlandsnails/file_importer_image_xls%20(2).xls";
$params["borneanlandsnails"]["local"]["name"]              = "borneanlandsnails";
$params["borneanlandsnails"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/borneanlandsnails/borneanlandsnails.xls";
// ==================================================================================================
// malaypeninsularsnail remote
$params["malaypeninsularsnail"]["remote"]["lifedesk"]          = "http://malaypeninsularsnail.lifedesks.org/eol-partnership.xml.gz";
$params["malaypeninsularsnail"]["remote"]["bibtex_file"]       = "http://malaypeninsularsnail.lifedesks.org/biblio/export/bibtex/";
$params["malaypeninsularsnail"]["remote"]["scratchpad_images"] = "";
$params["malaypeninsularsnail"]["remote"]["name"]              = "malaypeninsularsnail";
// malaypeninsularsnail Dropbox
$params["malaypeninsularsnail"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/malaypeninsularsnail/eol-partnership.xml.gz";
$params["malaypeninsularsnail"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/malaypeninsularsnail/Biblio-Bibtex.bib";
$params["malaypeninsularsnail"]["dropbox"]["scratchpad_images"] = "";
$params["malaypeninsularsnail"]["dropbox"]["name"]              = "malaypeninsularsnail";
// malaypeninsularsnail local
$params["malaypeninsularsnail"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/malaypeninsularsnail/eol-partnership.xml.gz";
$params["malaypeninsularsnail"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/malaypeninsularsnail/Biblio-Bibtex.bib";
$params["malaypeninsularsnail"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/malaypeninsularsnail/file_importer_image_xls.xls";
$params["malaypeninsularsnail"]["local"]["name"]              = "malaypeninsularsnail";
$params["malaypeninsularsnail"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/malaypeninsularsnail/malaypeninsularsnail.xls";
// ==================================================================================================
// sipuncula remote
$params["sipuncula"]["remote"]["lifedesk"]          = "http://sipuncula.lifedesks.org/eol-partnership.xml.gz";
$params["sipuncula"]["remote"]["bibtex_file"]       = "http://sipuncula.lifedesks.org/biblio/export/bibtex/";
$params["sipuncula"]["remote"]["scratchpad_images"] = "";
$params["sipuncula"]["remote"]["name"]              = "sipuncula";
// sipuncula Dropbox
$params["sipuncula"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/sipuncula/eol-partnership.xml.gz";
$params["sipuncula"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/sipuncula/Biblio-Bibtex.bib";
$params["sipuncula"]["dropbox"]["scratchpad_images"] = "";
$params["sipuncula"]["dropbox"]["name"]              = "sipuncula";
// sipuncula local
$params["sipuncula"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/sipuncula/eol-partnership.xml.gz";
$params["sipuncula"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/sipuncula/Biblio-Bibtex.bib";
$params["sipuncula"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/sipuncula/file_importer_image_xls.xls";
$params["sipuncula"]["local"]["name"]              = "sipuncula";
$params["sipuncula"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/sipuncula/sipuncula.xls";
// ==================================================================================================
// hawaiilandsnails remote
$params["hawaiilandsnails"]["remote"]["lifedesk"]          = "http://hawaiilandsnails.lifedesks.org/eol-partnership.xml.gz";
$params["hawaiilandsnails"]["remote"]["bibtex_file"]       = "http://hawaiilandsnails.lifedesks.org/biblio/export/bibtex/";
$params["hawaiilandsnails"]["remote"]["scratchpad_images"] = "";
$params["hawaiilandsnails"]["remote"]["name"]              = "hawaiilandsnails";
// hawaiilandsnails Dropbox
$params["hawaiilandsnails"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/hawaiilandsnails/eol-partnership.xml.gz";
$params["hawaiilandsnails"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/hawaiilandsnails/Biblio-Bibtex.bib";
$params["hawaiilandsnails"]["dropbox"]["scratchpad_images"] = "";
$params["hawaiilandsnails"]["dropbox"]["name"]              = "hawaiilandsnails";
// hawaiilandsnails local
$params["hawaiilandsnails"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/hawaiilandsnails/eol-partnership.xml.gz";
$params["hawaiilandsnails"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/hawaiilandsnails/Biblio-Bibtex.bib";
$params["hawaiilandsnails"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/hawaiilandsnails/file_importer_image_xls%20(1).xls";
$params["hawaiilandsnails"]["local"]["name"]              = "hawaiilandsnails";
// ==================================================================================================
// ostracoda remote
$params["ostracoda"]["remote"]["lifedesk"]          = "http://ostracoda.lifedesks.org/eol-partnership.xml.gz";
$params["ostracoda"]["remote"]["bibtex_file"]       = "http://ostracoda.lifedesks.org/biblio/export/bibtex/";
$params["ostracoda"]["remote"]["scratchpad_images"] = "";
$params["ostracoda"]["remote"]["name"]              = "ostracoda";
// ostracoda Dropbox
$params["ostracoda"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/ostracoda/eol-partnership.xml.gz";
$params["ostracoda"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/ostracoda/Biblio-Bibtex.bib";
$params["ostracoda"]["dropbox"]["scratchpad_images"] = "";
$params["ostracoda"]["dropbox"]["name"]              = "ostracoda";
// ostracoda local
$params["ostracoda"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/ostracoda/eol-partnership.xml.gz";
$params["ostracoda"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/ostracoda/Biblio-Bibtex.bib";
$params["ostracoda"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/ostracoda/file_importer_image_xls%20(2).xls";
$params["ostracoda"]["local"]["name"]              = "ostracoda";
// ==================================================================================================
// ampullariidae remote
$params["ampullariidae"]["remote"]["lifedesk"]          = "http://ampullariidae.lifedesks.org/eol-partnership.xml.gz";
$params["ampullariidae"]["remote"]["bibtex_file"]       = "http://ampullariidae.lifedesks.org/biblio/export/bibtex/";
$params["ampullariidae"]["remote"]["scratchpad_images"] = "";
$params["ampullariidae"]["remote"]["name"]              = "ampullariidae";
// ampullariidae Dropbox
$params["ampullariidae"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/ampullariidae/eol-partnership.xml.gz";
$params["ampullariidae"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/ampullariidae/Biblio-Bibtex.bib";
$params["ampullariidae"]["dropbox"]["scratchpad_images"] = "";
$params["ampullariidae"]["dropbox"]["name"]              = "ampullariidae";
// ampullariidae local
$params["ampullariidae"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/ampullariidae/eol-partnership.xml.gz";
$params["ampullariidae"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/ampullariidae/Biblio-Bibtex.bib";
$params["ampullariidae"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/ampullariidae/file_importer_image_xls.xls";
$params["ampullariidae"]["local"]["name"]              = "ampullariidae";
$params["ampullariidae"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/ampullariidae/ampullariidae.xls";
// ==================================================================================================
// cephaloleia remote
$params["cephaloleia"]["remote"]["lifedesk"]          = "http://cephaloleia.lifedesks.org/eol-partnership.xml.gz";
$params["cephaloleia"]["remote"]["bibtex_file"]       = "http://cephaloleia.lifedesks.org/biblio/export/bibtex/";
$params["cephaloleia"]["remote"]["scratchpad_images"] = "";
$params["cephaloleia"]["remote"]["name"]              = "cephaloleia";
// cephaloleia Dropbox
$params["cephaloleia"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/cephaloleia/eol-partnership.xml.gz";
$params["cephaloleia"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/cephaloleia/Biblio-Bibtex.bib";
$params["cephaloleia"]["dropbox"]["scratchpad_images"] = "";
$params["cephaloleia"]["dropbox"]["name"]              = "cephaloleia";
// cephaloleia local
$params["cephaloleia"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/cephaloleia/eol-partnership.xml.gz";
$params["cephaloleia"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/cephaloleia/Biblio-Bibtex.bib";
$params["cephaloleia"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/cephaloleia/file_importer_image_xls%20(1).xls";
$params["cephaloleia"]["local"]["name"]              = "cephaloleia";
// ==================================================================================================
// mormyrids remote
$params["mormyrids"]["remote"]["lifedesk"]          = "http://mormyrids.lifedesks.org/eol-partnership.xml.gz";
$params["mormyrids"]["remote"]["bibtex_file"]       = "http://mormyrids.lifedesks.org/biblio/export/bibtex/";
$params["mormyrids"]["remote"]["scratchpad_images"] = "";
$params["mormyrids"]["remote"]["name"]              = "mormyrids";
// mormyrids Dropbox
$params["mormyrids"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/mormyrids/eol-partnership.xml.gz";
$params["mormyrids"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/mormyrids/Biblio-Bibtex.bib";
$params["mormyrids"]["dropbox"]["scratchpad_images"] = "";
$params["mormyrids"]["dropbox"]["name"]              = "mormyrids";
// mormyrids local
$params["mormyrids"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/mormyrids/eol-partnership.xml.gz";
$params["mormyrids"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/mormyrids/Biblio-Bibtex.bib";
$params["mormyrids"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/mormyrids/file_importer_image_xls%20(1).xls";
$params["mormyrids"]["local"]["name"]              = "mormyrids";
$params["mormyrids"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/mormyrids/mormyrids.xls";
// ==================================================================================================
// terrslugs remote
$params["terrslugs"]["remote"]["lifedesk"]          = "http://terrslugs.lifedesks.org/eol-partnership.xml.gz";
$params["terrslugs"]["remote"]["bibtex_file"]       = "http://terrslugs.lifedesks.org/biblio/export/bibtex/";
$params["terrslugs"]["remote"]["scratchpad_images"] = "";
$params["terrslugs"]["remote"]["name"]              = "terrslugs";
// terrslugs Dropbox
$params["terrslugs"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/terrslugs/eol-partnership.xml.gz";
$params["terrslugs"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/terrslugs/Biblio-Bibtex.bib";
$params["terrslugs"]["dropbox"]["scratchpad_images"] = "";
$params["terrslugs"]["dropbox"]["name"]              = "terrslugs";
// terrslugs local
$params["terrslugs"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/terrslugs/eol-partnership.xml.gz";
$params["terrslugs"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/terrslugs/Biblio-Bibtex.bib";
$params["terrslugs"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/terrslugs/file_importer_image_xls%20(2).xls";
$params["terrslugs"]["local"]["name"]              = "terrslugs";
$params["terrslugs"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/terrslugs/terrslugs.xls";
// ==================================================================================================
// agrilus remote
$params["agrilus"]["remote"]["lifedesk"]          = "http://agrilus.lifedesks.org/eol-partnership.xml.gz";
$params["agrilus"]["remote"]["bibtex_file"]       = "";
$params["agrilus"]["remote"]["scratchpad_images"] = "";
$params["agrilus"]["remote"]["name"]              = "agrilus";
// agrilus Dropbox
$params["agrilus"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/agrilus/eol-partnership.xml.gz";
$params["agrilus"]["dropbox"]["bibtex_file"]       = "";
$params["agrilus"]["dropbox"]["scratchpad_images"] = "";
$params["agrilus"]["dropbox"]["name"]              = "agrilus";
// agrilus local
$params["agrilus"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/agrilus/eol-partnership.xml.gz";
$params["agrilus"]["local"]["bibtex_file"]       = ""; // no bibtex file
$params["agrilus"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/agrilus/file_importer_image_xls.xls";
$params["agrilus"]["local"]["name"]              = "agrilus";
// ==================================================================================================
// camptosomata remote
$params["camptosomata"]["remote"]["lifedesk"]          = "http://camptosomata.lifedesks.org/eol-partnership.xml.gz";
$params["camptosomata"]["remote"]["bibtex_file"]       = "";
$params["camptosomata"]["remote"]["scratchpad_images"] = "";
$params["camptosomata"]["remote"]["name"]              = "camptosomata";
// camptosomata Dropbox
$params["camptosomata"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/camptosomata/eol-partnership.xml.gz";
$params["camptosomata"]["dropbox"]["bibtex_file"]       = "";
$params["camptosomata"]["dropbox"]["scratchpad_images"] = "";
$params["camptosomata"]["dropbox"]["name"]              = "camptosomata";
// camptosomata local
$params["camptosomata"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/camptosomata/eol-partnership.xml.gz";
$params["camptosomata"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/camptosomata/Biblio-Bibtex.bib";
$params["camptosomata"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/camptosomata/file_importer_image_xls.xls";
$params["camptosomata"]["local"]["name"]              = "camptosomata";
$params["camptosomata"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/camptosomata/camptosomata.xls";
// ==================================================================================================
// urbanfloranyc remote
$params["urbanfloranyc"]["remote"]["lifedesk"]          = "http://urbanfloranyc.lifedesks.org/eol-partnership.xml.gz";
$params["urbanfloranyc"]["remote"]["bibtex_file"]       = "";
$params["urbanfloranyc"]["remote"]["scratchpad_images"] = "";
$params["urbanfloranyc"]["remote"]["name"]              = "urbanfloranyc";
// urbanfloranyc Dropbox
$params["urbanfloranyc"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/urbanfloranyc/eol-partnership.xml.gz";
$params["urbanfloranyc"]["dropbox"]["bibtex_file"]       = "";
$params["urbanfloranyc"]["dropbox"]["scratchpad_images"] = "";
$params["urbanfloranyc"]["dropbox"]["name"]              = "urbanfloranyc";
// urbanfloranyc local
$params["urbanfloranyc"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/urbanfloranyc/eol-partnership.xml.gz";
$params["urbanfloranyc"]["local"]["bibtex_file"]       = ""; // no bibtex file
$params["urbanfloranyc"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/urbanfloranyc/file_importer_image_xls.xls";
$params["urbanfloranyc"]["local"]["name"]              = "urbanfloranyc";
// ==================================================================================================
// marineinvaders remote
$params["marineinvaders"]["remote"]["lifedesk"]          = "http://marineinvaders.lifedesks.org/eol-partnership.xml.gz";
$params["marineinvaders"]["remote"]["bibtex_file"]       = "";
$params["marineinvaders"]["remote"]["scratchpad_images"] = "";
$params["marineinvaders"]["remote"]["name"]              = "marineinvaders";
// marineinvaders Dropbox
$params["marineinvaders"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/marineinvaders/eol-partnership.xml.gz";
$params["marineinvaders"]["dropbox"]["bibtex_file"]       = "";
$params["marineinvaders"]["dropbox"]["scratchpad_images"] = "";
$params["marineinvaders"]["dropbox"]["name"]              = "marineinvaders";
// marineinvaders local
$params["marineinvaders"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/marineinvaders/eol-partnership.xml.gz";
$params["marineinvaders"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/marineinvaders/Biblio-Bibtex.bib";
$params["marineinvaders"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/marineinvaders/file_importer_image_xls.xls";
$params["marineinvaders"]["local"]["name"]              = "marineinvaders";
$params["marineinvaders"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/marineinvaders/node_importer_biblio_xls.xls";
// ==================================================================================================
// neritopsine remote
$params["neritopsine"]["remote"]["lifedesk"]          = "http://neritopsine.lifedesks.org/eol-partnership.xml.gz";
$params["neritopsine"]["remote"]["bibtex_file"]       = "";
$params["neritopsine"]["remote"]["scratchpad_images"] = "";
$params["neritopsine"]["remote"]["name"]              = "neritopsine";
// neritopsine Dropbox
$params["neritopsine"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/neritopsine/eol-partnership.xml.gz";
$params["neritopsine"]["dropbox"]["bibtex_file"]       = "";
$params["neritopsine"]["dropbox"]["scratchpad_images"] = "";
$params["neritopsine"]["dropbox"]["name"]              = "neritopsine";
// neritopsine local
$params["neritopsine"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/neritopsine/eol-partnership.xml.gz";
$params["neritopsine"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/neritopsine/Biblio-Bibtex.bib";
$params["neritopsine"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/neritopsine/file_importer_image_xls.xls";
$params["neritopsine"]["local"]["name"]              = "neritopsine";
$params["neritopsine"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/neritopsine/node_importer_biblio_xls.xls";
// ==================================================================================================
// polycladida remote
$params["polycladida"]["remote"]["lifedesk"]          = "http://polycladida.lifedesks.org/eol-partnership.xml.gz";
$params["polycladida"]["remote"]["bibtex_file"]       = "";
$params["polycladida"]["remote"]["scratchpad_images"] = "";
$params["polycladida"]["remote"]["name"]              = "polycladida";
// polycladida Dropbox
$params["polycladida"]["dropbox"]["lifedesk"]          = "";
$params["polycladida"]["dropbox"]["bibtex_file"]       = "";
$params["polycladida"]["dropbox"]["scratchpad_images"] = "";
$params["polycladida"]["dropbox"]["name"]              = "polycladida";
// polycladida local
$params["polycladida"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/polycladida/eol-partnership.xml.gz";
$params["polycladida"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/polycladida/Biblio-Bibtex.bib";
$params["polycladida"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/polycladida/file_importer_image_xls.xls";
$params["polycladida"]["local"]["name"]              = "polycladida";
$params["polycladida"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/polycladida/node_importer_biblio_xls.xls";
// ==================================================================================================
// tabanidae remote
$params["tabanidae"]["remote"]["lifedesk"]          = "http://tabanidae.lifedesks.org/eol-partnership.xml.gz";
$params["tabanidae"]["remote"]["bibtex_file"]       = "";
$params["tabanidae"]["remote"]["scratchpad_images"] = "";
$params["tabanidae"]["remote"]["name"]              = "tabanidae";
// tabanidae Dropbox
$params["tabanidae"]["dropbox"]["lifedesk"]          = "";
$params["tabanidae"]["dropbox"]["bibtex_file"]       = "";
$params["tabanidae"]["dropbox"]["scratchpad_images"] = "";
$params["tabanidae"]["dropbox"]["name"]              = "tabanidae";
// tabanidae local
$params["tabanidae"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/tabanidae/eol-partnership.xml.gz";
$params["tabanidae"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/tabanidae/Biblio-Bibtex.bib";
$params["tabanidae"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/tabanidae/file_importer_image_xls.xls";
$params["tabanidae"]["local"]["name"]              = "tabanidae";
$params["tabanidae"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/tabanidae/node_importer_biblio_xls.xls";
// ==================================================================================================
// squatlobsters remote
$params["squatlobsters"]["remote"]["lifedesk"]          = "http://squatlobsters.lifedesks.org/eol-partnership.xml.gz";
$params["squatlobsters"]["remote"]["bibtex_file"]       = "";
$params["squatlobsters"]["remote"]["scratchpad_images"] = "";
$params["squatlobsters"]["remote"]["name"]              = "squatlobsters";
// squatlobsters Dropbox
$params["squatlobsters"]["dropbox"]["lifedesk"]          = "";
$params["squatlobsters"]["dropbox"]["bibtex_file"]       = "";
$params["squatlobsters"]["dropbox"]["scratchpad_images"] = "";
$params["squatlobsters"]["dropbox"]["name"]              = "squatlobsters";
// squatlobsters local
$params["squatlobsters"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/squatlobsters/eol-partnership.xml.gz";
$params["squatlobsters"]["local"]["bibtex_file"]       = "";
$params["squatlobsters"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/squatlobsters/file_importer_image_xls.xls";
$params["squatlobsters"]["local"]["name"]              = "squatlobsters";
$params["squatlobsters"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/squatlobsters/node_importer_biblio_xls.xls";
// ==================================================================================================
// simuliidae remote
$params["simuliidae"]["remote"]["lifedesk"]          = "http://simuliidae.lifedesks.org/eol-partnership.xml.gz";
$params["simuliidae"]["remote"]["bibtex_file"]       = "";
$params["simuliidae"]["remote"]["scratchpad_images"] = "";
$params["simuliidae"]["remote"]["name"]              = "simuliidae";
// simuliidae Dropbox
$params["simuliidae"]["dropbox"]["lifedesk"]          = "";
$params["simuliidae"]["dropbox"]["bibtex_file"]       = "";
$params["simuliidae"]["dropbox"]["scratchpad_images"] = "";
$params["simuliidae"]["dropbox"]["name"]              = "simuliidae";
// simuliidae local
$params["simuliidae"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/simuliidae/eol-partnership.xml.gz";
$params["simuliidae"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/simuliidae/Biblio-Bibtex.bib";
$params["simuliidae"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/simuliidae/file_importer_image_xls.xls";
$params["simuliidae"]["local"]["name"]              = "simuliidae";
$params["simuliidae"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/simuliidae/node_importer_biblio_xls.xls";
// ==================================================================================================
// proctotrupidae remote
$params["proctotrupidae"]["remote"]["lifedesk"]          = "http://proctotrupidae.lifedesks.org/eol-partnership.xml.gz";
$params["proctotrupidae"]["remote"]["bibtex_file"]       = "";
$params["proctotrupidae"]["remote"]["scratchpad_images"] = "";
$params["proctotrupidae"]["remote"]["name"]              = "proctotrupidae";
// proctotrupidae Dropbox
$params["proctotrupidae"]["dropbox"]["lifedesk"]          = "";
$params["proctotrupidae"]["dropbox"]["bibtex_file"]       = "";
$params["proctotrupidae"]["dropbox"]["scratchpad_images"] = "";
$params["proctotrupidae"]["dropbox"]["name"]              = "proctotrupidae";
// proctotrupidae local
$params["proctotrupidae"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/proctotrupidae/eol-partnership.xml.gz";
$params["proctotrupidae"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/proctotrupidae/Biblio-Bibtex.bib";
$params["proctotrupidae"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/proctotrupidae/file_importer_image_xls.xls";
$params["proctotrupidae"]["local"]["name"]              = "proctotrupidae";
$params["proctotrupidae"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/proctotrupidae/node_importer_biblio_xls.xls";
// ==================================================================================================
// opisthobranchia remote
$params["opisthobranchia"]["remote"]["lifedesk"]          = "http://opisthobranchia.lifedesks.org/eol-partnership.xml.gz";
$params["opisthobranchia"]["remote"]["bibtex_file"]       = "";
$params["opisthobranchia"]["remote"]["scratchpad_images"] = "";
$params["opisthobranchia"]["remote"]["name"]              = "opisthobranchia";
// opisthobranchia Dropbox
$params["opisthobranchia"]["dropbox"]["lifedesk"]          = "";
$params["opisthobranchia"]["dropbox"]["bibtex_file"]       = "";
$params["opisthobranchia"]["dropbox"]["scratchpad_images"] = "";
$params["opisthobranchia"]["dropbox"]["name"]              = "opisthobranchia";
// opisthobranchia local
$params["opisthobranchia"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/opisthobranchia/eol-partnership.xml.gz";
$params["opisthobranchia"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/opisthobranchia/Biblio-Bibtex.bib";
$params["opisthobranchia"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/opisthobranchia/file_importer_image_xls (1).xls";
$params["opisthobranchia"]["local"]["name"]              = "opisthobranchia";
$params["opisthobranchia"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/opisthobranchia/node_importer_biblio_xls (1).xls";
// ==================================================================================================
// katydidsfrombrazil remote
$params["katydidsfrombrazil"]["remote"]["lifedesk"]          = "http://katydidsfrombrazil.lifedesks.org/eol-partnership.xml.gz";
$params["katydidsfrombrazil"]["remote"]["bibtex_file"]       = "";
$params["katydidsfrombrazil"]["remote"]["scratchpad_images"] = "";
$params["katydidsfrombrazil"]["remote"]["name"]              = "katydidsfrombrazil";
// katydidsfrombrazil Dropbox
$params["katydidsfrombrazil"]["dropbox"]["lifedesk"]          = "";
$params["katydidsfrombrazil"]["dropbox"]["bibtex_file"]       = "";
$params["katydidsfrombrazil"]["dropbox"]["scratchpad_images"] = "";
$params["katydidsfrombrazil"]["dropbox"]["name"]              = "katydidsfrombrazil";
// katydidsfrombrazil local
$params["katydidsfrombrazil"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/katydidsfrombrazil/eol-partnership.xml.gz";
$params["katydidsfrombrazil"]["local"]["bibtex_file"]       = "";
$params["katydidsfrombrazil"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/katydidsfrombrazil/file_importer_image_xls.xls";
$params["katydidsfrombrazil"]["local"]["name"]              = "katydidsfrombrazil";
$params["katydidsfrombrazil"]["local"]["scratchpad_biblio"] = "";
// ==================================================================================================
// hypogymnia remote
$params["hypogymnia"]["remote"]["lifedesk"]          = "http://hypogymnia.lifedesks.org/eol-partnership.xml.gz";
$params["hypogymnia"]["remote"]["bibtex_file"]       = "";
$params["hypogymnia"]["remote"]["scratchpad_images"] = "";
$params["hypogymnia"]["remote"]["name"]              = "hypogymnia";
// hypogymnia Dropbox
$params["hypogymnia"]["dropbox"]["lifedesk"]          = "";
$params["hypogymnia"]["dropbox"]["bibtex_file"]       = "";
$params["hypogymnia"]["dropbox"]["scratchpad_images"] = "";
$params["hypogymnia"]["dropbox"]["name"]              = "hypogymnia";
// hypogymnia local
$params["hypogymnia"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/hypogymnia/eol-partnership.xml.gz";
$params["hypogymnia"]["local"]["bibtex_file"]       = "";
$params["hypogymnia"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/hypogymnia/file_importer_image_xls (1).xls";
$params["hypogymnia"]["local"]["name"]              = "hypogymnia";
$params["hypogymnia"]["local"]["scratchpad_biblio"] = "";
// ==================================================================================================
// salamandersofchina remote
$params["salamandersofchina"]["remote"]["lifedesk"]          = "http://salamandersofchina.lifedesks.org/eol-partnership.xml.gz";
$params["salamandersofchina"]["remote"]["bibtex_file"]       = "";
$params["salamandersofchina"]["remote"]["scratchpad_images"] = "";
$params["salamandersofchina"]["remote"]["name"]              = "salamandersofchina";
// salamandersofchina Dropbox
$params["salamandersofchina"]["dropbox"]["lifedesk"]          = "";
$params["salamandersofchina"]["dropbox"]["bibtex_file"]       = "";
$params["salamandersofchina"]["dropbox"]["scratchpad_images"] = "";
$params["salamandersofchina"]["dropbox"]["name"]              = "salamandersofchina";
// salamandersofchina local
$params["salamandersofchina"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/salamandersofchina/eol-partnership.xml.gz";
$params["salamandersofchina"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/salamandersofchina/Biblio-Bibtex.bib";
$params["salamandersofchina"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/salamandersofchina/file_importer_image_xls.xls";
$params["salamandersofchina"]["local"]["name"]              = "salamandersofchina";
$params["salamandersofchina"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/salamandersofchina/node_importer_biblio_xls.xls";
// ==================================================================================================
// ebasidiolichens remote
$params["ebasidiolichens"]["remote"]["lifedesk"]          = "http://ebasidiolichens.lifedesks.org/eol-partnership.xml.gz";
$params["ebasidiolichens"]["remote"]["bibtex_file"]       = "";
$params["ebasidiolichens"]["remote"]["scratchpad_images"] = "";
$params["ebasidiolichens"]["remote"]["name"]              = "ebasidiolichens";
// ebasidiolichens Dropbox
$params["ebasidiolichens"]["dropbox"]["lifedesk"]          = "";
$params["ebasidiolichens"]["dropbox"]["bibtex_file"]       = "";
$params["ebasidiolichens"]["dropbox"]["scratchpad_images"] = "";
$params["ebasidiolichens"]["dropbox"]["name"]              = "ebasidiolichens";
// ebasidiolichens local
$params["ebasidiolichens"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/ebasidiolichens/eol-partnership.xml.gz";
$params["ebasidiolichens"]["local"]["bibtex_file"]       = "";
$params["ebasidiolichens"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/ebasidiolichens/file_importer_image_xls.xls";
$params["ebasidiolichens"]["local"]["name"]              = "ebasidiolichens";
$params["ebasidiolichens"]["local"]["scratchpad_biblio"] = "";
// ==================================================================================================
// hundrednewlichens remote
$params["hundrednewlichens"]["remote"]["lifedesk"]          = "http://hundrednewlichens.lifedesks.org/eol-partnership.xml.gz";
$params["hundrednewlichens"]["remote"]["bibtex_file"]       = "";
$params["hundrednewlichens"]["remote"]["scratchpad_images"] = "";
$params["hundrednewlichens"]["remote"]["name"]              = "hundrednewlichens";
// hundrednewlichens Dropbox
$params["hundrednewlichens"]["dropbox"]["lifedesk"]          = "";
$params["hundrednewlichens"]["dropbox"]["bibtex_file"]       = "";
$params["hundrednewlichens"]["dropbox"]["scratchpad_images"] = "";
$params["hundrednewlichens"]["dropbox"]["name"]              = "hundrednewlichens";
// hundrednewlichens local
$params["hundrednewlichens"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/hundrednewlichens/eol-partnership.xml.gz";
$params["hundrednewlichens"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/hundrednewlichens/Biblio-Bibtex.bib";
$params["hundrednewlichens"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/hundrednewlichens/file_importer_image_xls (1).xls";
$params["hundrednewlichens"]["local"]["name"]              = "hundrednewlichens";
$params["hundrednewlichens"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/hundrednewlichens/node_importer_biblio_xls.xls";
// ==================================================================================================
// molluscacolombia remote
$params["molluscacolombia"]["remote"]["lifedesk"]          = "http://molluscacolombia.lifedesks.org/eol-partnership.xml.gz";
$params["molluscacolombia"]["remote"]["bibtex_file"]       = "";
$params["molluscacolombia"]["remote"]["scratchpad_images"] = "";
$params["molluscacolombia"]["remote"]["name"]              = "molluscacolombia";
// molluscacolombia Dropbox
$params["molluscacolombia"]["dropbox"]["lifedesk"]          = "";
$params["molluscacolombia"]["dropbox"]["bibtex_file"]       = "";
$params["molluscacolombia"]["dropbox"]["scratchpad_images"] = "";
$params["molluscacolombia"]["dropbox"]["name"]              = "molluscacolombia";
// molluscacolombia local
$params["molluscacolombia"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/molluscacolombia/eol-partnership.xml.gz";
$params["molluscacolombia"]["local"]["bibtex_file"]       = "";
$params["molluscacolombia"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/molluscacolombia/untitled.xls";
$params["molluscacolombia"]["local"]["name"]              = "molluscacolombia";
$params["molluscacolombia"]["local"]["scratchpad_biblio"] = "";
// ==================================================================================================
// lincolnsflorafauna remote
$params["lincolnsflorafauna"]["remote"]["lifedesk"]          = "http://lincolnsflorafauna.lifedesks.org/eol-partnership.xml.gz";
$params["lincolnsflorafauna"]["remote"]["bibtex_file"]       = "";
$params["lincolnsflorafauna"]["remote"]["scratchpad_images"] = "";
$params["lincolnsflorafauna"]["remote"]["name"]              = "lincolnsflorafauna";
// lincolnsflorafauna Dropbox
$params["lincolnsflorafauna"]["dropbox"]["lifedesk"]          = "";
$params["lincolnsflorafauna"]["dropbox"]["bibtex_file"]       = "";
$params["lincolnsflorafauna"]["dropbox"]["scratchpad_images"] = "";
$params["lincolnsflorafauna"]["dropbox"]["name"]              = "lincolnsflorafauna";
// lincolnsflorafauna local
$params["lincolnsflorafauna"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/lincolnsflorafauna/eol-partnership.xml.gz";
$params["lincolnsflorafauna"]["local"]["bibtex_file"]       = "";
$params["lincolnsflorafauna"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/lincolnsflorafauna/file_importer_image_xls.xls";
$params["lincolnsflorafauna"]["local"]["name"]              = "lincolnsflorafauna";
$params["lincolnsflorafauna"]["local"]["scratchpad_biblio"] = "";
// ==================================================================================================
// arachnids remote
$params["arachnids"]["remote"]["lifedesk"]          = "http://arachnids.lifedesks.org/eol-partnership.xml.gz";
$params["arachnids"]["remote"]["bibtex_file"]       = "";
$params["arachnids"]["remote"]["scratchpad_images"] = "";
$params["arachnids"]["remote"]["name"]              = "arachnids";
// arachnids Dropbox
$params["arachnids"]["dropbox"]["lifedesk"]          = "";
$params["arachnids"]["dropbox"]["bibtex_file"]       = "";
$params["arachnids"]["dropbox"]["scratchpad_images"] = "";
$params["arachnids"]["dropbox"]["name"]              = "arachnids";
// arachnids local
$params["arachnids"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/arachnids/eol-partnership.xml.gz";
$params["arachnids"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/arachnids/Biblio-Bibtex.bib";
$params["arachnids"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/arachnids/image.xls";
$params["arachnids"]["local"]["name"]              = "arachnids";
$params["arachnids"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/arachnids/biblio.xls";
// ==================================================================================================
// congofishes remote
$params["congofishes"]["remote"]["lifedesk"]          = "http://congofishes.lifedesks.org/eol-partnership.xml.gz";
$params["congofishes"]["remote"]["bibtex_file"]       = "";
$params["congofishes"]["remote"]["scratchpad_images"] = "";
$params["congofishes"]["remote"]["name"]              = "congofishes";
// congofishes Dropbox
$params["congofishes"]["dropbox"]["lifedesk"]          = "";
$params["congofishes"]["dropbox"]["bibtex_file"]       = "";
$params["congofishes"]["dropbox"]["scratchpad_images"] = "";
$params["congofishes"]["dropbox"]["name"]              = "congofishes";
// congofishes local
$params["congofishes"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/congofishes/eol-partnership.xml.gz";
$params["congofishes"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/congofishes/Biblio-Bibtex.bib";
$params["congofishes"]["local"]["scratchpad_images"] = "";
$params["congofishes"]["local"]["name"]              = "congofishes";
$params["congofishes"]["local"]["scratchpad_biblio"] = "";
// ==================================================================================================
// indiareeffishes remote
$params["indiareeffishes"]["remote"]["lifedesk"]          = "http://indiareeffishes.lifedesks.org/eol-partnership.xml.gz";
$params["indiareeffishes"]["remote"]["bibtex_file"]       = "";
$params["indiareeffishes"]["remote"]["scratchpad_images"] = "";
$params["indiareeffishes"]["remote"]["name"]              = "indiareeffishes";
// indiareeffishes Dropbox
$params["indiareeffishes"]["dropbox"]["lifedesk"]          = "";
$params["indiareeffishes"]["dropbox"]["bibtex_file"]       = "";
$params["indiareeffishes"]["dropbox"]["scratchpad_images"] = "";
$params["indiareeffishes"]["dropbox"]["name"]              = "indiareeffishes";
// indiareeffishes local
$params["indiareeffishes"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/indiareeffishes/eol-partnership.xml.gz";
$params["indiareeffishes"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/indiareeffishes/Biblio-Bibtex.bib";
$params["indiareeffishes"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/indiareeffishes/file_importer_image_xls.xls";
$params["indiareeffishes"]["local"]["name"]              = "indiareeffishes";
$params["indiareeffishes"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/indiareeffishes/node_importer_biblio_xls.xls";
// ==================================================================================================
// olivirv remote
$params["olivirv"]["remote"]["lifedesk"]          = "http://olivirv.lifedesks.org/eol-partnership.xml.gz";
$params["olivirv"]["remote"]["bibtex_file"]       = "";
$params["olivirv"]["remote"]["scratchpad_images"] = "";
$params["olivirv"]["remote"]["name"]              = "olivirv";
// olivirv Dropbox
$params["olivirv"]["dropbox"]["lifedesk"]          = "";
$params["olivirv"]["dropbox"]["bibtex_file"]       = "";
$params["olivirv"]["dropbox"]["scratchpad_images"] = "";
$params["olivirv"]["dropbox"]["name"]              = "olivirv";
// olivirv local
$params["olivirv"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/olivirv/eol-partnership.xml.gz";
$params["olivirv"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/olivirv/Biblio-Bibtex.bib";
$params["olivirv"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/olivirv/file_importer_image_xls.xls";
$params["olivirv"]["local"]["name"]              = "olivirv";
$params["olivirv"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/olivirv/node_importer_biblio_xls.xls";
// ==================================================================================================
// avesamericanas remote
$params["avesamericanas"]["remote"]["lifedesk"]          = "http://avesamericanas.lifedesks.org/eol-partnership.xml.gz";
$params["avesamericanas"]["remote"]["bibtex_file"]       = "";
$params["avesamericanas"]["remote"]["scratchpad_images"] = "";
$params["avesamericanas"]["remote"]["name"]              = "avesamericanas";
// avesamericanas Dropbox
$params["avesamericanas"]["dropbox"]["lifedesk"]          = "";
$params["avesamericanas"]["dropbox"]["bibtex_file"]       = "";
$params["avesamericanas"]["dropbox"]["scratchpad_images"] = "";
$params["avesamericanas"]["dropbox"]["name"]              = "avesamericanas";
// avesamericanas local
$params["avesamericanas"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/avesamericanas/eol-partnership.xml.gz";
$params["avesamericanas"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/avesamericanas/Biblio-Bibtex.bib";
$params["avesamericanas"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/avesamericanas/file_importer_image_xls1.xls";
$params["avesamericanas"]["local"]["name"]              = "avesamericanas";
$params["avesamericanas"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/avesamericanas/node_importer_biblio_xls1.xls";
// ==================================================================================================
// neotropnathistory remote
$params["neotropnathistory"]["remote"]["lifedesk"]          = "http://neotropnathistory.lifedesks.org/eol-partnership.xml.gz";
$params["neotropnathistory"]["remote"]["bibtex_file"]       = "";
$params["neotropnathistory"]["remote"]["scratchpad_images"] = "";
$params["neotropnathistory"]["remote"]["name"]              = "neotropnathistory";
// neotropnathistory Dropbox
$params["neotropnathistory"]["dropbox"]["lifedesk"]          = "";
$params["neotropnathistory"]["dropbox"]["bibtex_file"]       = "";
$params["neotropnathistory"]["dropbox"]["scratchpad_images"] = "";
$params["neotropnathistory"]["dropbox"]["name"]              = "neotropnathistory";
// neotropnathistory local
$params["neotropnathistory"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/neotropnathistory/eol-partnership.xml.gz";
$params["neotropnathistory"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/neotropnathistory/Biblio-Bibtex.bib";
$params["neotropnathistory"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/neotropnathistory/file_importer_image_xls.xls";
$params["neotropnathistory"]["local"]["name"]              = "neotropnathistory";
$params["neotropnathistory"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/neotropnathistory/node_importer_biblio_xls.xls";
// ==================================================================================================
// quercus remote
$params["quercus"]["remote"]["lifedesk"]          = "http://quercus.lifedesks.org/eol-partnership.xml.gz";
$params["quercus"]["remote"]["bibtex_file"]       = "";
$params["quercus"]["remote"]["scratchpad_images"] = "";
$params["quercus"]["remote"]["name"]              = "quercus";
// quercus Dropbox
$params["quercus"]["dropbox"]["lifedesk"]          = "";
$params["quercus"]["dropbox"]["bibtex_file"]       = "";
$params["quercus"]["dropbox"]["scratchpad_images"] = "";
$params["quercus"]["dropbox"]["name"]              = "quercus";
// quercus local
$params["quercus"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/quercus/eol-partnership.xml.gz";
$params["quercus"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/quercus/Biblio-Bibtex.bib";
$params["quercus"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/quercus/file_importer_image_xls (1).xls";
$params["quercus"]["local"]["name"]              = "quercus";
$params["quercus"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/quercus/node_importer_biblio_xls.xls";
// ==================================================================================================
// caterpillars remote
$params["caterpillars"]["remote"]["lifedesk"]          = "http://caterpillars.lifedesks.org/eol-partnership.xml.gz";
$params["caterpillars"]["remote"]["bibtex_file"]       = "";
$params["caterpillars"]["remote"]["scratchpad_images"] = "";
$params["caterpillars"]["remote"]["name"]              = "caterpillars";
// caterpillars Dropbox
$params["caterpillars"]["dropbox"]["lifedesk"]          = "";
$params["caterpillars"]["dropbox"]["bibtex_file"]       = "";
$params["caterpillars"]["dropbox"]["scratchpad_images"] = "";
$params["caterpillars"]["dropbox"]["name"]              = "caterpillars";
// caterpillars local
$params["caterpillars"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/caterpillars/eol-partnership.xml.gz";
$params["caterpillars"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/caterpillars/Biblio-Bibtex.bib";
$params["caterpillars"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/caterpillars/file_importer_image_xls.xls";
$params["caterpillars"]["local"]["name"]              = "caterpillars";
$params["caterpillars"]["local"]["scratchpad_biblio"] = "http://localhost/~eolit/cp/LD2Scratchpad/caterpillars/node_importer_biblio_xls.xls";
// ==================================================================================================
// africanamphibians remote
$params["africanamphibians"]["remote"]["lifedesk"]          = "http://africanamphibians.lifedesks.org/eol-partnership.xml.gz";
$params["africanamphibians"]["remote"]["bibtex_file"]       = "http://africanamphibians.lifedesks.org/biblio/export/bibtex/";
$params["africanamphibians"]["remote"]["scratchpad_images"] = "";
$params["africanamphibians"]["remote"]["name"]              = "africanamphibians";
// africanamphibians Dropbox
$params["africanamphibians"]["dropbox"]["lifedesk"]          = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/africanamphibians/eol-partnership.xml.gz";
$params["africanamphibians"]["dropbox"]["bibtex_file"]       = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/africanamphibians/Biblio-Bibtex.bib";
$params["africanamphibians"]["dropbox"]["scratchpad_images"] = "";
$params["africanamphibians"]["dropbox"]["name"]              = "africanamphibians";
// africanamphibians local
$params["africanamphibians"]["local"]["lifedesk"]           = "http://localhost/~eolit/cp/LD2Scratchpad/africanamphibians/eol-partnership.xml.gz";
$params["africanamphibians"]["local"]["bibtex_file"]        = "http://localhost/~eolit/cp/LD2Scratchpad/africanamphibians/Biblio-Bibtex.bib";
$params["africanamphibians"]["local"]["scratchpad_images"]  = "http://localhost/~eolit/cp/LD2Scratchpad/africanamphibians/file_importer_image_xls.xls";
$params["africanamphibians"]["local"]["name"]               = "africanamphibians";
$params["africanamphibians"]["local"]["scratchpad_biblio"]  = "http://localhost/~eolit/cp/LD2Scratchpad/africanamphibians/node_importer_biblio_xls.xls"; //old - africanamphibians.xls
$params["africanamphibians"]["local"]["scratchpad_taxonomy"]= "http://localhost/~eolit/cp/LD2Scratchpad/africanamphibians/taxonomy_importer_amphibians_xls.xls";
// ==================================================================================================
// neotropicalfishes remote
$params["neotropicalfishes"]["remote"]["lifedesk"]          = "http://neotropicalfishes.lifedesks.org/eol-partnership.xml.gz";
$params["neotropicalfishes"]["remote"]["bibtex_file"]       = "";
$params["neotropicalfishes"]["remote"]["scratchpad_images"] = "";
$params["neotropicalfishes"]["remote"]["name"]              = "neotropicalfishes";
// neotropicalfishes Dropbox
$params["neotropicalfishes"]["dropbox"]["lifedesk"]          = "";
$params["neotropicalfishes"]["dropbox"]["bibtex_file"]       = "";
$params["neotropicalfishes"]["dropbox"]["scratchpad_images"] = "";
$params["neotropicalfishes"]["dropbox"]["name"]              = "neotropicalfishes";
// neotropicalfishes local
$params["neotropicalfishes"]["local"]["lifedesk"]           = "http://localhost/~eolit/cp/LD2Scratchpad/neotropicalfishes/eol-partnership.xml.gz";
$params["neotropicalfishes"]["local"]["bibtex_file"]        = "";
$params["neotropicalfishes"]["local"]["scratchpad_images"]  = "http://localhost/~eolit/cp/LD2Scratchpad/neotropicalfishes/file_importer_image_xls.xls";
$params["neotropicalfishes"]["local"]["name"]               = "neotropicalfishes";
$params["neotropicalfishes"]["local"]["scratchpad_biblio"]  = "http://localhost/~eolit/cp/LD2Scratchpad/neotropicalfishes/node_importer_biblio_xls.xls";
// ==================================================================================================
// dinoflagellate remote
$params["dinoflagellate"]["remote"]["lifedesk"]          = "http://dinoflagellate.lifedesks.org/eol-partnership.xml.gz";
$params["dinoflagellate"]["remote"]["bibtex_file"]       = "";
$params["dinoflagellate"]["remote"]["scratchpad_images"] = "";
$params["dinoflagellate"]["remote"]["name"]              = "dinoflagellate";
// dinoflagellate Dropbox
$params["dinoflagellate"]["dropbox"]["lifedesk"]          = "";
$params["dinoflagellate"]["dropbox"]["bibtex_file"]       = "";
$params["dinoflagellate"]["dropbox"]["scratchpad_images"] = "";
$params["dinoflagellate"]["dropbox"]["name"]              = "dinoflagellate";
// dinoflagellate local
$params["dinoflagellate"]["local"]["lifedesk"]           = "http://localhost/~eolit/cp/LD2Scratchpad/dinoflagellate/eol-partnership.xml.gz";
$params["dinoflagellate"]["local"]["bibtex_file"]        = "";
$params["dinoflagellate"]["local"]["scratchpad_images"]  = "";
$params["dinoflagellate"]["local"]["name"]               = "dinoflagellate";
$params["dinoflagellate"]["local"]["scratchpad_biblio"]  = "http://localhost/~eolit/cp/LD2Scratchpad/dinoflagellate/node_importer_biblio_xls.xls";
// ==================================================================================================
http://chess.lifedesks.org/eol-partnership.xml.gz

// chess remote
$params["chess"]["remote"]["lifedesk"]          = "http://chess.lifedesks.org/eol-partnership.xml.gz";
$params["chess"]["remote"]["bibtex_file"]       = "";
$params["chess"]["remote"]["scratchpad_images"] = "";
$params["chess"]["remote"]["name"]              = "chess";
// chess Dropbox
$params["chess"]["dropbox"]["lifedesk"]          = "";
$params["chess"]["dropbox"]["bibtex_file"]       = "";
$params["chess"]["dropbox"]["scratchpad_images"] = "";
$params["chess"]["dropbox"]["name"]              = "chess";
// chess local
$params["chess"]["local"]["lifedesk"]           = "http://localhost/~eolit/cp/LD2Scratchpad/chess/eol-partnership.xml.gz";
$params["chess"]["local"]["bibtex_file"]        = "";
$params["chess"]["local"]["scratchpad_images"]  = "http://localhost/~eolit/cp/LD2Scratchpad/chess/file_importer_image_xls.xls";
$params["chess"]["local"]["name"]               = "chess";
$params["chess"]["local"]["scratchpad_biblio"]  = "http://localhost/~eolit/cp/LD2Scratchpad/chess/node_importer_biblio_xls.xls";
// ==================================================================================================



/* paste here which Lifedesk you want to export: e.g. $parameters = $params["leptogastrinae"]["dropbox"];
the export files (in .tar.gz) will be found in: DOC_ROOT/tmp/leptogastrinae_LD_to_Scratchpad_export.tar.gz
*/

// /* To run single LifeDesk:
$func = new LifeDeskToScratchpadAPI();
// $parameters = $params["dinoflagellate"]["local"];
// $parameters = $params["caterpillars"]["local"];
// $parameters = $params["neotropicalfishes"]["local"];
// $parameters = $params["lincolnsflorafauna"]["local"];
$parameters = $params["chess"]["local"];

if($parameters) $func->export_lifedesk_to_scratchpad($parameters);
else echo "\nNothing to process. Program will terminate\n";
// */


/* To run them all:
// "terrslugs" ***
$lifedesks = array("parmotrema", "pelagics", "continenticola", "leptogastrinae", "tunicata", "syrphidae", "peracarida", "nemertea","ostracoda", "hawaiilandsnails", 
                   "sipuncula", "malaypeninsularsnail", "borneanlandsnails", "opisthostoma", "apoidea", "buccinids", "sacoglossa", "porifera", "cnidaria", 
                   "staurozoa", "liquensms", "liquensbr", "africanamphibians", "cephaloleia", "ampullariidae", "agrilus", "mormyrids", "camptosomata", "urbanfloranyc", 
                   "marineinvaders", "neritopsine", "polycladida", "tabanidae", "squatlobsters", "simuliidae", "proctotrupidae", "opisthobranchia",
                   "katydidsfrombrazil", "hypogymnia", "salamandersofchina", "ebasidiolichens", "hundrednewlichens", "molluscacolombia", "lincolnsflorafauna",
                   "arachnids", "congofishes", "indiareeffishes", "olivirv", "avesamericanas", "neotropnathistory", "quercus", "caterpillars", "terrslugs",
                   "neotropicalfishes", "dinoflagellate", "chess");
foreach($lifedesks as $lifedesk)
{
    $func = new LifeDeskToScratchpadAPI();
    $func->export_lifedesk_to_scratchpad($params[$lifedesk]["local"]);
}
*/

/* start: Generate taxonomy of a LifeDesk 
// neotropicalfishes local
$params = array();
$params["neotropicalfishes"]["local"]["lifedesk"]   = "http://localhost/~eolit/cp/LD2Scratchpad/neotropicalfishes/eol-partnership.xml.gz";
$params["neotropicalfishes"]["local"]["name"]       = "neotropicalfishes";
$parameters = $params["neotropicalfishes"]["local"];
$func->export_lifedesk_taxonomy($parameters);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>