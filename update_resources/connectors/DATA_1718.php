<?php
namespace php_active_record;
/* connector for DATA-1718.php */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];
$timestart = time_elapsed();

/*
$url1 = "http://media.eol.org/content/2012/11/08/08/28694_orig.jpg";
$url2 = "/opt/homebrew/var/www/eol_php_code/tmp/tmp_72243.file.pdf";
echo "\n$url1 - ".pathinfo($url1, PATHINFO_BASENAME);
echo "\n$url2 - ".pathinfo($url2, PATHINFO_FILENAME);
print_r(pathinfo($url1));
exit("\n");
*/

require_library('connectors/EOLv2MetadataAPI'); /* un-comment below to run specific report: */

/*
$func = new EOLv2MetadataAPI("");
// $func->start_partner_metadata(); //DATA-1718
// $func->save_all_MOUs();
// $func->start_resource_metadata(); //DATA-1720

// $func->user_activity_collections();         echo "\n end user_activity_collections()\n";        return; //DATA-1780     //this will run in MacMini, then load txt file to eol-archive.
// $func->replace_media_url_update_report();   echo "\n end replace_media_url_update_report()\n";  return; //DATA-1780     //this will run in eol-archive

// $func->load_v2_images_export_from_jrice(); echo "\n end load_v2_images_export_from_jrice()\n"; return;  //DATA-1781 //just one-time. Done.
// $func->loop_user_activity_image_file(); return;                                                         //DATA-1781 //a utility to check the speed of searching. Fast enough.
*/

/* https://eol-jira.bibalex.org/browse/DATA-1726
$resource_id = "user_added_comnames_20470";
$resource_id = "user_added_comnames";
$func = new EOLv2MetadataAPI($resource_id);
$func->start_user_added_comnames();
Functions::finalize_dwca_resource($resource_id); echo "\n end start_user_added_comnames() \n"; //return;
*/

/* https://eol-jira.bibalex.org/browse/DATA-1726
$resource_id = "user_preferred_comnames";
$func = new EOLv2MetadataAPI($resource_id);
$func->start_user_preferred_comnames(); echo "\n end start_user_preferred_comnames() \n"; //return;
*/

/* https://eol-jira.bibalex.org/browse/DATA-1730
// $resource_id = "1"; //"UA_text"; //
$resource_id = "user_added_text";
$func = new EOLv2MetadataAPI($resource_id);
$func->start_user_added_text();
Functions::finalize_dwca_resource($resource_id); echo "\n end start_user_added_text() \n"; //return;
*/

/* https://eol-jira.bibalex.org/browse/TRAM-708 & DATA-1719
$func = new EOLv2MetadataAPI('wala lang');
$func->download_resource_files();
// $func->test_xml_files();
*/

/* https://eol-jira.bibalex.org/browse/DATA-1731
$func = new EOLv2MetadataAPI();
$func->start_user_object_curation(); echo "\n end start_user_object_curation() \n"; return;
// Cannot find resource anymore = 347 last count
*/

/* https://eol-jira.bibalex.org/browse/DATA-1746: user activity: images selected as exemplar
$func = new EOLv2MetadataAPI();
$func->start_images_selected_as_exemplar(); echo "\n end start_images_selected_as_exemplar() \n"; return;
*/

/* https://eol-jira.bibalex.org/browse/DATA-1732
$func = new EOLv2MetadataAPI("");
$func->start_user_comments('DataObject');
$func->start_user_comments('TaxonConcept');
$func->start_user_comments('Collection'); echo "\n end start_user_comments() \n"; //return;
*/

// /* https://eol-jira.bibalex.org/browse/DATA-1740
$func = new EOLv2MetadataAPI("");
// $func->start_image_sizes(); echo "\n end start_image_sizes() \n"; return; DONE ALREADY last Oct 27, 2018
$func->start_image_sizes_PART2(); echo "\n end start_image_sizes_PART2() \n"; return; //DATA-1805: this adds the eol_pk from the report of Oct 27, 2018.
// */

/* https://eol-jira.bibalex.org/browse/DATA-1741
$func = new EOLv2MetadataAPI();
$func->start_image_ratings(); echo "\n end start_image_ratings() \n"; return;
*/


/* https://eol-jira.bibalex.org/browse/DATA-1719
$func = new EOLv2MetadataAPI("");
$func->DATA_1719(); --- didn't use this
*/

/* DATA-1786: user activity- taxonomic propagation of rating + exemplar
$func = new EOLv2MetadataAPI();
// $func->taxonomic_propagation('exemplar_images'); echo "\n end taxonomic_propagation(exemplar_images) \n"; //return;
$func->taxonomic_propagation('image_ratings'); echo "\n end taxonomic_propagation(image_ratings) \n"; //return;
// $func->utility_compare_eol_pk();
*/

/* DATA-1788: re-locating selected images in v2 by old identifiers
$func = new EOLv2MetadataAPI();
$func->DATA_1788(); echo "\n end DATA_1788() \n"; return;
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";

/*
querying dbase...[5488184]
 new tc_id [5488098]
from: [5488184] to final: tc_id [5488098]

querying dbase...[5828987]
 new tc_id [5828941]
from: [5828987] to final: tc_id [5828941]

querying dbase...[184041]
 new tc_id [183928]
 new tc_id [182546]
 new tc_id [182545]
 new tc_id [182542]
from: [184041] to final: tc_id [182542]

querying dbase...[184041]
 new tc_id [183928]
 new tc_id [182546]
 new tc_id [182545]
 new tc_id [182542]
from: [184041] to final: tc_id [182542]

querying dbase...[184041]
 new tc_id [183928]
 new tc_id [182546]
 new tc_id [182545]
 new tc_id [182542]
from: [184041] to final: tc_id [182542]

querying dbase...[163158]
 new tc_id [158670]
 new tc_id [158668]
from: [163158] to final: tc_id [158668]
*/
?>
