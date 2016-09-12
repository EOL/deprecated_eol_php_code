<?php
namespace php_active_record;
// /* Converting EoEarth.org HTML to MediaWiki
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('HTML2MediaWikiAPI_EoEarth');
$func = new HTML2MediaWikiAPI_EoEarth();
// $func->start(); //comment if you want to run generate_dbase_for_redirect() OR generate_wanted_pages() OR start_edit_published_wiki()
// */

/* Downloading articles from EoEarth.org
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/EoEarthAPI');
$func = new EoEarthAPI();
$func->start();
*/

// /* this will edit published wiki pages
$func->start_edit_published_wiki();
// */

/* this will generate the dbase for the redirect system
$func->generate_dbase_for_redirect();
*/

/* this will save items in special:wantedpages
$func->save_wanted_pages();                      //the actual function
$func->retrieve_wanted_pages_from_text_file();   //to investigate
$func->generate_wanted_pages();                  //when just checking the list (obsolete)
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>