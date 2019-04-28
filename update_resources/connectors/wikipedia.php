<?php
namespace php_active_record;
/* Wikipedia in different languages */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiDataAPI');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false;

/*
$arr = Functions::get_undefined_uris_from_resource(false, false);
// $arr = Functions::get_undefined_uris_from_resource_v1(false, false);
print_r($arr); exit;
*/

/*
$str = "abcd|e|||";
echo "\n[$str]\n";
$str = Functions::remove_this_last_char_from_str($str, "|");
echo "\n[$str]\n";
exit;
*/

/* testing routines...
$str = "Year: [https://www.flickr.com/search/?tags=bookyear1841 1841] ([https://www.flickr.com/search/?tags=bookdecade1840 1840s])";
$str =  "Publisher: [https://www.flickr.com/search/?tags=bookpublisherLeiden_A_Arnz_comp_ Leiden, A. Arnz &amp; comp.]";
echo "\n$str\n";
echo wiki2html($str);
exit("\n");
*/

/* utility
$func = new WikiDataAPI($resource_id, "");
//these 2 functions are ran one after the other, preferably. This is process a new WikiMedia dump
$func->create_temp_files_based_on_wikimedia_filenames();     //create blank json files
$func->fill_in_temp_files_with_wikimedia_dump_data();        //fill-in those blank json files
exit("\n Finished preparing new WikiMedia dump \n");
*/

/* utility
$func = new WikiDataAPI($resource_id, "");
$func->process_wikimedia_txt_dump(); //initial verification of the wikimedia dump file. Not part of the normal operation
exit("\n Finished: just exploring... \n");
*/

// $func = new WikiDataAPI($resource_id, "cu");     cu ? investigate why so few...
// $func = new WikiDataAPI($resource_id, "uk");     uk (uk) Ukrainian
// $func = new WikiDataAPI($resource_id, "pl");     pl (pl) Polish

// not yet complete:
// $func = new WikiDataAPI($resource_id, "nl");     //496940    (nl) Dutch
// $func = new WikiDataAPI($resource_id, "sv");     //317830    (sv) Swedish    //still being run, many many bot inspired
// $func = new WikiDataAPI($resource_id, "vi");     //459950    (vi) Vietnamese

/*
$func = new WikiDataAPI($resource_id, "en", "taxonomy");    //3rd param is boolean taxonomy; true means will generate hierarchy resource. [wikidata-hierarchy]    //done
$func = new WikiDataAPI($resource_id, "en", "wikimedia");   //done - Used for Commons - total taxa = 2,208,086 // has its own connector wikidata.php
*/
//===================
// $func = new WikiDataAPI($resource_id, "ceb");

// Wikipedia English is EOL resource_id = 80 --> http://www.eol.org/content_partners/129/resources/80
// Wikipedia German is EOL resource_id = 957

// print_r($argv);
// php5.6 wikipedia.php jenkins en generate_resource 1 300000 1of6
// php5.6 wikipedia.php jenkins de #German
/* to test locall when developing:
php update_resources/connectors/wikipedia.php _ de         //or en, es, it */
$params['jenkins_or_cron']  = @$argv[1];
$params['language']         = @$argv[2];

$params['task']             = @$argv[3];
$params['range_from']       = @$argv[4];
$params['range_to']         = @$argv[5];
$params['actual']           = @$argv[6];
print_r($params);

// /* //----------start main operation
if($val = $params['language']) $language = $val;
else                           $language = "zh"; //manually supplied

if    ($language == 'en') $resource_id = 80;
elseif($language == 'de') $resource_id = 957;
else $resource_id = "wikipedia-".$language;

/* test only
delete_temp_files_and_others($language);
exit("\nend test\n");
*/

$langs_with_multiple_connectors = array("en", "es", "fr", "de", "it", "pt", "zh"); //1st batch | single connectors: ko, ja, ru
$langs_with_multiple_connectors = array_merge($langs_with_multiple_connectors, array("nl", "pl", "sv", "vi")); //2nd batch Dutch Polish Swedish Vietnamese
/* Not yet with multiple connectors
$langs_with_multiple_connectors = array_merge($langs_with_multiple_connectors, array("no", "fi", "ca", "uk")); //3rd batch Norwegian Finnish Catalan Ukranian
$langs_with_multiple_connectors = array_merge($langs_with_multiple_connectors, array("tr", "ro", "cs")); //4th batch Turkish Romanian Czech
*/
$func = new WikiDataAPI($resource_id, $language, 'wikipedia', $langs_with_multiple_connectors); //generic call

if(in_array($language, $langs_with_multiple_connectors)) { //uncomment in real operation
// if(false) { //*** use this when developing to process language e.g. 'en' for one taxon only
    $status_arr = $func->generate_resource($params['task'], $params['range_from'], $params['range_to'], $params['actual']);  //ran 6 connectors bec of lookup caching. Then ran 1 connector to finalize.
    if($status_arr[0]) {
        echo "\n".$params['actual']." -- finished\n";
        if($status_arr[1]) {
            echo "\n---Can now proceed - finalize dwca...---\n\n";
            Functions::finalize_dwca_resource($resource_id, true, true); //2nd param true means big file; 3rd param true means will delete working folder
            delete_temp_files_and_others($language); // delete six (6) .tmp files and one (1) wikipedia_generation_status for language in question
        }
        else echo "\nCannot finalize dwca yet.\n";
    }
    else exit(1);
}
else { //orig - just one connector
    $func->generate_resource();
    Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means delete working folder
}
// ----------end main operation */

/* final-pt
not defined parent [Q4674600]
not defined parent [Q18596649]
total undefined parent_id: 2
*/

/* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function delete_temp_files_and_others($language)
{   /*
    -rw-r--r-- 1 root      root       91798932 Apr 18 19:30 wikipedia-pl.tar.gz
    -rw-r--r-- 1 root      root             15 Apr 18 19:29 wikipedia_generation_status_pl_2019_04.txt
    -rw-r--r-- 1 root      root       47597921 Apr 18 18:28 wikipedia_pl_2019-04-18_09_22.tmp
    -rw-r--r-- 1 root      root              0 Apr 18 17:42 wikipedia_pl_2019-04-18_17_17.tmp
    -rw-r--r-- 1 root      root              0 Apr 18 15:26 wikipedia_pl_2019-04-18_15_31.tmp
    -rw-r--r-- 1 root      root              0 Apr 18 15:13 wikipedia_pl_2019-04-18_15_06.tmp
    -rw-r--r-- 1 root      root              0 Apr 18 14:59 wikipedia_pl_2019-04-18_14_11.tmp
    -rw-r--r-- 1 root      root              0 Apr 18 14:56 wikipedia_pl_2019-04-18_14_07.tmp
    */
    $paths[] = CONTENT_RESOURCE_LOCAL_PATH . "wikipedia_generation_status_".$language."_*.txt";
    $paths[] = CONTENT_RESOURCE_LOCAL_PATH . "wikipedia_".$language."_*.tmp";
    foreach($paths as $path) {
        foreach(glob($path) as $filename) {
            echo "\n[$filename] [".filesize($filename)."] - ";
            if(unlink($filename)) echo "deleted OK\n";
            else                  echo "deletion failed\n";
        }
    }
}

/* http://opendata.eol.org/dataset/wikipedia_5k
Data and Resources

wikipedia-en.tar.gz English *
wikipedia-es.tar.gz Spanish *
wikipedia-it.tar.gz Italian *
wikipedia-de.tar.gz German *
wikipedia-fr.tar.gz French *
wikipedia-zh.tar.gz Chinese
wikipedia-ru.tar.gz Russian
wikipedia-pt.tar.gz Portuguese
wikipedia-ja.tar.gz Japanese
wikipedia-ko.tar.gz Korean

To do:
wikipedia-nl.tar.gz Dutch
wikipedia-pl.tar.gz Polish
wikipedia-sv.tar.gz Swedish
wikipedia-vi.tar.gz Vietnamese
wikipedia-uk.tar.gz Ukrainian
wikipedia-cu.tar.gz Indo-European 	Church Slavic, Church Slavonic, Old Church Slavonic, Old Slavonic, Old Bulgarian

To do: DATA-1800
nl, 
pl, sv, vi, (and war, ceb)


wikipedia-en	Friday 2017-12-01 03:59:01 AM	{"media_resource.tab":671062,"taxon.tab":355505}
80	            Monday 2017-12-04 04:16:56 AM	{"media_resource.tab":672049,"taxon.tab":356043}
80	            Tuesday 2018-04-24 09:48:55 AM	{"media_resource.tab":672059,"taxon.tab":356038}
80              Tuesday 2018-04-24 09:48:55 AM	{"media_resource.tab":672059,"taxon.tab":356038}
80	            Saturday 2018-06-09 07:47:28 AM	{"media_resource.tab":672074,"taxon.tab":356035}
80	            Sunday 2018-07-08 08:56:53 AM	{"media_resource.tab":672072,"taxon.tab":356034}
80	            Wednesday 2018-10-10 03:03:49 AM{"media_resource.tab":672090,"taxon.tab":356032}
80	            Wednesday 2018-11-14 09:33:50 AM{"media_resource.tab":737715,"taxon.tab":388855}
80	            Monday 2018-11-19 12:46:35 PM	{"media_resource.tab":573888,"taxon.tab":312084} -- big reduction
80	            Tuesday 2018-11-20 12:22:44 PM	{"media_resource.tab":737413,"taxon.tab":388855} -- back to normal
80	            Friday 2018-12-14 08:58:32 PM	{"media_resource.tab":738993,"taxon.tab":389728} -- looking good :-)
80	            Wednesday 2019-02-13 12:06:37 AM{"media_resource.tab":744523,"taxon.tab":392598} -- Consistent OK
80	            Sunday 2019-04-21 10:47:23 AM	{"media_resource.tab":747078,"taxon.tab":393995} -- consistent increase even after -> only taxon with object is included in DwCA

wikipedia-es	Sunday 2017-12-03 12:21:46 AM	{"media_resource.tab":300492,"taxon.tab":165487}
wikipedia-es	Saturday 2018-05-05 03:59:03 AM	{"media_resource.tab":300473,"taxon.tab":165477}
wikipedia-es	Tuesday 2018-10-16 12:05:22 AM	{"media_resource.tab":300460,"taxon.tab":165470}
wikipedia-es	Wednesday 2018-11-14 07:12:40 AM{"media_resource.tab":305501,"taxon.tab":168126}
wikipedia-es	Monday 2018-11-19 12:45:00 PM	{"media_resource.tab":181219,"taxon.tab":107133} -- big reduction
wikipedia-es	Monday 2018-11-19 05:22:14 PM	{"media_resource.tab":305501,"taxon.tab":168126} -- back to normal
wikipedia-es	Sunday 2018-12-16 10:52:06 AM	{"media_resource.tab":306063,"taxon.tab":168420} -- looking good :-)
wikipedia-es	Thursday 2019-02-14 02:31:24 PM	{"media_resource.tab":306886,"taxon.tab":168848} -- Consistent OK
wikipedia-es	Tuesday 2019-04-23 12:18:06 AM	{"media_resource.tab":307434,"taxon.tab":169155} -- consistent increase even after -> only taxon with object is included in DwCA

wikipedia-it	Tuesday 2017-11-14 04:48:25 AM	{"media_resource.tab":64723,"taxon.tab":39992}
wikipedia-it	Monday 2017-12-04 05:16:39 AM	{"media_resource.tab":64861,"taxon.tab":40069}
wikipedia-it	Friday 2018-04-27 05:50:21 AM	{"media_resource.tab":64854,"taxon.tab":40067}
wikipedia-it	Thursday 2018-10-11 11:05:17 AM	{"media_resource.tab":64853,"taxon.tab":40067}
wikipedia-it	Tuesday 2018-11-13 07:56:41 PM	{"media_resource.tab":67470,"taxon.tab":41498}
wikipedia-it	Monday 2018-11-19 05:38:43 AM	{"media_resource.tab":67470,"taxon.tab":41498}
wikipedia-it	Sunday 2018-12-16 07:33:26 PM	{"media_resource.tab":67624,"taxon.tab":41605} -- looking good :-)
wikipedia-it	Friday 2019-02-15 12:22:29 AM	{"media_resource.tab":68176,"taxon.tab":41899} -- Consistent OK
wikipedia-it	Tuesday 2019-04-23 05:20:02 AM	{"media_resource.tab":68460,"taxon.tab":42065} -- consistent increase even after -> only taxon with object is included in DwCA

wikipedia-de	Saturday 2017-11-11 08:52:35 PM	{"media_resource.tab":87725,"taxon.tab":55915}
957	            Monday 2017-12-04 05:20:51 AM	{"media_resource.tab":87940,"taxon.tab":56041}
957	            Friday 2018-04-27 02:57:47 AM	{"media_resource.tab":87934,"taxon.tab":56036}
957	            Thursday 2018-10-11 10:57:17 PM	{"media_resource.tab":87871,"taxon.tab":56015}
957	            Wednesday 2018-11-14 03:15:52 AM{"media_resource.tab":90281,"taxon.tab":57490}
957	            Monday 2018-11-19 05:37:09 AM	{"media_resource.tab":90281,"taxon.tab":57490}
957	            Monday 2018-12-17 07:31:51 AM	{"media_resource.tab":90502,"taxon.tab":57637} -- looking good :-)
957	            Friday 2019-02-15 12:13:40 PM	{"media_resource.tab":91356,"taxon.tab":58153} -- Consistent OK
957	            Tuesday 2019-04-23 10:46:42 AM	{"media_resource.tab":91948,"taxon.tab":58535} -- consistent increase even after -> only taxon with object is included in DwCA

wikipedia-fr	Sunday 2017-12-03 12:04:24 AM	{"media_resource.tab":214962,"taxon.tab":119824}
wikipedia-fr	Sunday 2018-04-29 04:10:02 PM	{"media_resource.tab":214956,"taxon.tab":119821}
wikipedia-fr	Sunday 2018-10-14 11:36:53 AM	{"media_resource.tab":214976,"taxon.tab":119816}
wikipedia-fr	Wednesday 2018-11-14 09:40:10 PM{"media_resource.tab":224117,"taxon.tab":124547} might change...
wikipedia-fr	Thursday 2018-11-15 05:24:25 AM	{"media_resource.tab":224055,"taxon.tab":124547}
wikipedia-fr	Monday 2018-11-19 07:24:35 AM	{"media_resource.tab":224056,"taxon.tab":124547}
wikipedia-fr	Tuesday 2018-12-18 11:41:11 AM	{"media_resource.tab":225329,"taxon.tab":125283} -- looking good :-)
wikipedia-fr	Saturday 2019-02-16 05:44:04 PM	{"media_resource.tab":226858,"taxon.tab":126145} -- Consistent OK
wikipedia-fr	Wednesday 2019-04-24 01:53:21 AM{"media_resource.tab":229673,"taxon.tab":127530} -- consistent increase even after -> only taxon with object is included in DwCA

wikipedia-ko	Thursday 2017-11-30 10:29:47 PM	{"media_resource.tab":24527,"taxon.tab":16954}
wikipedia-ko	Sunday 2017-12-03 12:48:17 AM	{"media_resource.tab":24701,"taxon.tab":17060}
wikipedia-ko	Tuesday 2018-05-01 03:32:49 PM	{"media_resource.tab":24654,"taxon.tab":17047}
wikipedia-ko	Friday 2018-11-16 01:45:28 AM	{"media_resource.tab":28204,"taxon.tab":19078}
wikipedia-ko	Monday 2018-11-19 10:28:56 AM	{"media_resource.tab":28204,"taxon.tab":19078}
wikipedia-ko	Wednesday 2018-12-19 01:40:27 AM{"media_resource.tab":28249,"taxon.tab":19117} -- looking good :-)
wikipedia-ko	Sunday 2019-02-17 08:14:59 AM	{"media_resource.tab":28498,"taxon.tab":19246} -- Consistent OK
wikipedia-ko	Wednesday 2019-04-24 08:10:41 AM{"media_resource.tab":29273,"taxon.tab":19794} -- consistent increase even after -> only taxon with object is included in DwCA

wikipedia-ja	Friday 2017-11-10 08:35:41 AM	{"media_resource.tab":26208,"taxon.tab":20431}
wikipedia-ja	Saturday 2017-12-02 10:53:40 PM	{"media_resource.tab":26264,"taxon.tab":20475}
wikipedia-ja	Friday 2018-04-27 03:18:00 PM	{"media_resource.tab":26262,"taxon.tab":20474}
wikipedia-ja	Friday 2018-10-12 11:31:52 AM	{"media_resource.tab":26259,"taxon.tab":20474}
wikipedia-ja	Saturday 2018-11-17 08:39:01 AM	{"media_resource.tab":27790,"taxon.tab":21652}
wikipedia-ja	Monday 2018-11-19 10:43:18 AM	{"media_resource.tab":27787,"taxon.tab":21652}
wikipedia-ja	Wednesday 2018-12-19 01:33:58 AM{"media_resource.tab":27841,"taxon.tab":21694} -- looking good :-)
wikipedia-ja	Sunday 2019-02-17 07:03:28 AM	{"media_resource.tab":27966,"taxon.tab":21789} -- Consistent OK
wikipedia-ja	Wednesday 2019-04-24 07:39:22 AM{"media_resource.tab":28101,"taxon.tab":21901} -- consistent increase even after -> only taxon with object is included in DwCA

wikipedia-ru	Sunday 2017-11-12 12:03:09 PM	{"media_resource.tab":77531,"taxon.tab":47336}
wikipedia-ru	Saturday 2017-12-02 11:31:53 PM	{"media_resource.tab":77649,"taxon.tab":47398}
wikipedia-ru	Saturday 2018-04-28 08:31:18 PM	{"media_resource.tab":77630,"taxon.tab":47351}
wikipedia-ru	Saturday 2018-10-13 03:14:13 AM	{"media_resource.tab":77574,"taxon.tab":47321}
wikipedia-ru	Saturday 2018-11-17 03:13:13 AM	{"media_resource.tab":81804,"taxon.tab":49707}
wikipedia-ru	Monday 2018-11-19 11:20:03 AM	{"media_resource.tab":81804,"taxon.tab":49707}
wikipedia-ru	Thursday 2018-12-20 04:02:22 AM	{"media_resource.tab":82064,"taxon.tab":49847} -- looking good :-)
wikipedia-ru	Monday 2019-02-18 09:29:31 AM	{"media_resource.tab":82814,"taxon.tab":50255} -- consistent OK.
wikipedia-ru	Wednesday 2019-04-24 06:38:49 PM{"media_resource.tab":83696,"taxon.tab":50801} -- consistent increase even after -> only taxon with object is included in DwCA

wikipedia-pt	Sunday 2017-12-03 01:34:21 AM	{"media_resource.tab":192390,"taxon.tab":108840}
wikipedia-pt	Friday 2018-05-04 01:26:59 PM	{"media_resource.tab":192384,"taxon.tab":108838}
wikipedia-pt	Monday 2018-11-19 12:44:50 PM	{"media_resource.tab":156778,"taxon.tab":91720} -- big reduction
wikipedia-pt	Tuesday 2018-11-20 05:17:44 AM	{"media_resource.tab":197927,"taxon.tab":111719} -- back to normal
wikipedia-pt	Saturday 2018-12-22 11:51:14 AM	{"media_resource.tab":198183,"taxon.tab":111889} -- looking good :-)
wikipedia-pt	Sunday 2019-02-17 06:16:01 PM	{"media_resource.tab":198441,"taxon.tab":112063} -- consistent OK. Started the 6-connectors run.
wikipedia-pt	Wednesday 2019-04-24 01:28:22 PM{"media_resource.tab":200018,"taxon.tab":112900} -- consistent increase even after -> only taxon with object is included in DwCA

wikipedia-zh	Tuesday 2017-11-14 03:41:12 PM	{"media_resource.tab":156137,"taxon.tab":91145}
wikipedia-zh	Sunday 2017-12-03 12:33:20 AM	{"media_resource.tab":156585,"taxon.tab":91396}
wikipedia-zh	Monday 2018-04-30 08:13:07 AM	{"media_resource.tab":156574,"taxon.tab":91388}
wikipedia-zh	Sunday 2018-10-14 02:59:06 PM	{"media_resource.tab":156560,"taxon.tab":91381}
wikipedia-zh	Monday 2018-11-19 12:44:47 PM	{"media_resource.tab":127119,"taxon.tab":78239} -- big reduction
wikipedia-zh	Tuesday 2018-11-20 07:47:50 AM	{"media_resource.tab":175752,"taxon.tab":103141} -- back to normal
wikipedia-zh	Tuesday 2018-11-20 03:08:15 PM	{"media_resource.tab":175752,"taxon.tab":103141}
wikipedia-zh	Friday 2018-12-21 11:58:59 PM	{"media_resource.tab":175948,"taxon.tab":103260} -- looking good :-)
wikipedia-zh	Monday 2019-02-18 01:36:00 PM	{"media_resource.tab":176247,"taxon.tab":103406} -- consistent OK. Started the 6-connectors run.
wikipedia-zh	Wednesday 2019-04-24 10:03:31 PM{"media_resource.tab":177332,"taxon.tab":103989} -- consistent increase even after -> only taxon with object is included in DwCA
--------------------------------------------- START OF NEW BATCH ---------------------------------------------
wikipedia-vo	Wednesday 2019-04-17 09:43:21 AM{"media_resource.tab":34,"taxon.tab":118} - asked by Jen to ignore for now.

langs_with_multiple_connectors: "nl", "pl", "sv", "vi"
wikipedia-nl	Thursday 2019-04-18 09:03:04 AM	{"media_resource.tab":975151,"taxon.tab":950717}
wikipedia-pl	Thursday 2019-04-18 07:30:05 PM	{"media_resource.tab":92390,"taxon.tab":56112}
wikipedia-vi	Saturday 2019-04-27 01:40:44 AM	{"media_resource.tab":1582933,"taxon.tab":826812}
 
Not yet with multiple connectors: "no", "fi", "ca", "uk", "tr", "ro", "cs"
wikipedia-cs	Saturday 2019-04-27 06:30:40 AM	{"media_resource.tab":23413,"taxon.tab":17991}
wikipedia-tr	Saturday 2019-04-27 07:39:05 AM	{"media_resource.tab":11114,"taxon.tab":9113}
wikipedia-fi	Saturday 2019-04-27 09:26:44 AM	{"media_resource.tab":39542,"taxon.tab":26545}
wikipedia-no	Saturday 2019-04-27 02:04:01 PM	{"media_resource.tab":61203,"taxon.tab":37024}
wikipedia-ro	Saturday 2019-04-27 02:09:48 PM	{"media_resource.tab":60752,"taxon.tab":34846}
wikipedia-uk	Saturday 2019-04-27 03:30:05 PM	{"media_resource.tab":33509,"taxon.tab":30899}
wikipedia-ca	Sunday 2019-04-28 03:07:45 AM	{"media_resource.tab":124020,"taxon.tab":70790}

*/
/*
this is a request made by wikimedia harvester (71): this 2 are same, first is a subset of the 2nd. And one is urlencoded() other is not.
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo|categories&iiprop=url|mime|mediatype&cllimit=500&redirects&titles=File%3AROSMARINUS+OFFICINALIS+-+MORROCURT+-+IB-976+(Roman%C3%AD).JPG
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo%7Ccategories&iiprop=url%7Cmime%7Cmediatype&cllimit=500&redirects&titles=File%3AROSMARINUS+OFFICINALIS+-+MORROCURT+-+IB-976+%28Roman%C3%AD%29.JPG%7CFile%3ACapra+hircus+aegagrus-cropped.jpg%7CFile%3AEschrichtius+robustus+01-cropped.jpg%7CFile%3AMonachus+monachus+-+Museo+civico+di+storia+naturale+%28Milan%29-cropped.jpg%7CFile%3AMustela+putorius+01-cropped.jpg%7CFile%3ACondylactis+gigantea+%28giant+Caribbean+sea+anemone%29+closeup.jpg%7CFile%3A20140922-cephalopholis+hemistiktos.jpg%7CFile%3APelecanus+occidentalis+in+flight+at+Bodega+Bay.jpg%7CFile%3AUdotea+flabellum+%28mermaid%27s+fan+alga%29+Bahamas.jpg%7CFile%3ARhipocephalus+phoenix+%28pinecone+alga%29+%28San+Salvador+Island%2C+Bahamas%29.jpg%7CFile%3APadina+boergesenii+%28leafy+rolled-blade+algae+Bahamas.jpg%7CFile%3ABird%27s-nest+Fern+Asplenium+nidus+Leaves+2.jpg%7CFile%3ABird%27s-nest+Fern+Asplenium+nidus+Leaves+1.jpg%7CFile%3ABlack+Caterpillar+Fern+Scyphularia+pentaphylla+Leaves.JPG%7CFile%3ABlunt-lobed+Woodsia+obtusa+Winter+Foliage.JPG%7CFile%3AChinese+Elm+Ulmus+parvifolia+%2832-0052-A%29+Bark+1.JPG%7CFile%3AChinese+Elm+Ulmus+parvifolia+%2832-0052-A%29+Bark+2.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282012-055-A%29+Trunk+Bark.JPG%7CFile%3AJapanese+Alder+Alnus+japonica+%2881-305-A%29+Base+Bark.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282010-012-A%29+Bark+Closeup+1.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282010-012-A%29+Bark+Closeup+2.JPG%7CFile%3AJapanese+Zelkova+serrata+%2881-502-L%29+Upper+Trunk+Bark.JPG%7CFile%3AMiyabe+Maple+Acer+miyabei+%2851-292-A%29+Trunk+Bark+Closeup.JPG%7CFile%3AJapanese+Zelkova+serrata+%2881-502-L%29+Lower+Trunk+Bark.JPG%7CFile%3AMiyabe+Maple+Acer+miyabei+%2851-292-A%29+Trunk+Bark.JPG%7CFile%3ARed+Maple+Acer+rubrum+%2832-0877-A%29+Trunk+Bark.JPG%7CFile%3AWhite+Ash+Fraxinus+americana+%2854-0751-A%29+Trunk+Bark.JPG%7CFile%3ANerita+versicolor+%28four-toothed+nerite+snails%29+Bahamas.jpg%7CFile%3ACenchritis+muricatus+%28beaded+periwinkle+snails%29+Bahamas.jpg%7CFile%3AMonodelphis+domestica+skeleton+-+ZooKeys+465-10.png%7CFile%3AMonodelphis+brevicaudata+skull+-+ZooKeys+465-08.png%7CFile%3APeradectes+molars.png%7CFile%3APediomys+molars.png%7CFile%3AScreen+on+the+supermarket+shelf+%282654814813%29.jpg%7CFile%3ASacpha+Hodgson.jpg%7CFile%3AA+Sulphur+Crested+White+Cockatoo+%28Cacatua+galerita%29%2C+Cronulla%2C+NSW+Australia.jpg%7CFile%3AGOCZA%C5%81KOWICE+ZDR%C3%93J%2C+AB.+071.JPG%7CFile%3AVexillum+ebenus+01.JPG%7CFile%3ASCANDIX+PECTEN-VENERIS+-+AGUDA+-+IB-049+%28Agulles+de+pastor%29.JPG%7CFile%3ASCANDIX+PECTEN-VENERIS+-+AGUDA+-+IB-069+%28Agulles+de+pastor%29.JPG%7CFile%3ACRATAEGUS+MONOGYNA+-+AGUDA+-+IB-082+%28Ar%C3%A7%29.JPG%7CFile%3ACRATAEGUS+MONOGYNA+-+AGUDA+-+IB-089+%28Ar%C3%A7%29.JPG%7CFile%3ATagetes+erecta+23122014+%284%29.jpg%7CFile%3ACalendula+officinalis+27122014+%286%29.jpg%7CFile%3AFlowers+of+Judas+tree.jpg%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9259.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9262.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9263.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9264.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9265.JPG&continue=

http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo|categories&iiprop=url|mime|mediatype&cllimit=500&redirects&titles=File:Whales are Paraphyletic.png
*/
?>
