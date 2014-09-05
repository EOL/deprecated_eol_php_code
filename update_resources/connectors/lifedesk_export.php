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
$params["africanamphibians"]["local"]["lifedesk"]          = "http://localhost/~eolit/cp/LD2Scratchpad/africanamphibians/eol-partnership.xml.gz";
$params["africanamphibians"]["local"]["bibtex_file"]       = "http://localhost/~eolit/cp/LD2Scratchpad/africanamphibians/Biblio-Bibtex.bib";
$params["africanamphibians"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/africanamphibians/file_importer_image_xls.xls";
$params["africanamphibians"]["local"]["name"]              = "africanamphibians";
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
$params["camptosomata"]["local"]["scratchpad_images"] = "http://localhost/~eolit/cp/LD2Scratchpad/camptosomata/file_importer_image_xls%20(1).xls";
$params["camptosomata"]["local"]["name"]              = "camptosomata";
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

/* paste here which Lifedesk you want to export: e.g. $parameters = $params["leptogastrinae"]["dropbox"];
the export files (in .tar.gz) will be found in: DOC_ROOT/tmp/leptogastrinae_LD_to_Scratchpad_export.tar.gz
*/
$parameters = $params["africanamphibians"]["local"];
if($parameters) $func->export_lifedesk_to_scratchpad($parameters);
else echo "\nNothing to process. Program will terminate\n";

/* To run them all:
// "terrslugs" ***
$lifedesks = array("parmotrema", "pelagics", "continenticola", "leptogastrinae", "tunicata", "syrphidae", "peracarida", "nemertea","ostracoda", "hawaiilandsnails", 
                   "sipuncula", "malaypeninsularsnail", "borneanlandsnails", "opisthostoma", "apoidea", "buccinids", "sacoglossa", "porifera", "cnidaria", 
                   "staurozoa", "liquensms", "liquensbr", "africanamphibians", "cephaloleia", "ampullariidae", "agrilus", "mormyrids", "camptosomata", "urbanfloranyc");
foreach($lifedesks as $lifedesk) $func->export_lifedesk_to_scratchpad($params[$lifedesk]["local"]);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>