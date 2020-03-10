<?php
namespace php_active_record;
/* Wikipedia in different languages */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikipediaAPI');
require_library('connectors/WikiDataAPI');
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false; //orig false in production

/*
$func = new WikiDataAPI('1', 'eli', 'wikipedia', array()); //generic call
$func->retrieve_info_on_bot_wikis();
exit("\nend\n");
*/

/*
$file = CONTENT_RESOURCE_LOCAL_PATH.'dataset_comparison.txt';
$date_today = date("d-m-y",time());
echo "\nLast modified on: " . date("d-m-y", filemtime($file));
echo "\nLast accessed on: " . date("d-m-y", fileatime($file));
echo "\nDate today: $date_today";
exit("\n");
*/

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
// $func = new WikiDataAPI($resource_id, "sv");     //317830    (sv) Swedish    //many many bot inspired
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
/* 
to test locall when developing:
php update_resources/connectors/wikipedia.php _ de         //or en, es, it 

to generate stats: 'en' here can be any language...
php update_resources/connectors/wikipedia.php _ en taxon_wiki_per_language_stats
*/

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
/* No longer have multiple connectors
$langs_with_multiple_connectors = array_merge($langs_with_multiple_connectors, array("no", "fi", "ca", "uk")); //3rd batch Norwegian Finnish Catalan Ukranian
$langs_with_multiple_connectors = array_merge($langs_with_multiple_connectors, array("tr", "ro", "cs")); //4th batch Turkish Romanian Czech
*/

$use_MultipleConnJenkinsAPI = array("min", "war", "id"); //first client for MultipleConnJenkinsAPI | , "cy", "az", "ast", "bg" "ceb"
/* No longer have multiple connectors
$use_MultipleConnJenkinsAPI = array_merge($use_MultipleConnJenkinsAPI, array("szl", "af", "ka", "lt"));
*/
$langs_with_multiple_connectors = array_merge($langs_with_multiple_connectors, $use_MultipleConnJenkinsAPI);

$func = new WikiDataAPI($resource_id, $language, 'wikipedia', $langs_with_multiple_connectors); //generic call

if(in_array($language, $langs_with_multiple_connectors)) { //uncomment in real operation
// if(false) { //*** use this when developing to process language e.g. 'en' for one taxon only
    $status_arr = $func->generate_resource($params['task'], $params['range_from'], $params['range_to'], $params['actual']);  //ran 6 connectors bec of lookup caching. Then ran 1 connector to finalize.
    if($status_arr[0]) {
        echo "\n".$params['actual']." -- finished\n";
        if($status_arr[1]) {
            echo "\n---Can now proceed - finalize dwca...---\n\n";
            Functions::finalize_dwca_resource($resource_id, true, true, $timestart); //2nd param true means big file; 3rd param true means will delete working folder
            delete_temp_files_and_others($language); // delete six (6) .tmp files and one (1) wikipedia_generation_status for language in question
        }
        else {
            echo "\nCannot finalize dwca yet.\n";
            // /* ------------------------------------------------------ place to start injecting MultipleConnJenkinsAPI
            if(in_array($language, $use_MultipleConnJenkinsAPI)) inject_MultipleConnJenkinsAPI($language);
            // ------------------------------------------------------ */
        }
    }
    else exit(1);
}
else { //orig - just one connector
    $func->generate_resource();
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means delete working folder
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

function inject_MultipleConnJenkinsAPI($language)
{
    /* START continue lifeline of Jenkins event --------------------------------------------- */
    require_library('connectors/MultipleConnJenkinsAPI');
    $funcj = new MultipleConnJenkinsAPI();
    echo "\ntry to finalize now...\n";
    $total_count = 2700000; //2500000 old value
    $arr_info = array();
    $arr_info['finalize_now'] = true;
    $arr_info['langx'] = $language;
    $arr_info['connector'] = 'gen_wikipedia_by_lang';
    $arr_info['divisor'] = 6;
    $arr_info['total_count'] = $total_count;
    $batches = array();
    $batches[] = array(1, $total_count);
    $arr_info['batches'] = $batches;
    $funcj->jenkins_call($arr_info, "finalize"); //finally make the call
    /* END continue lifeline of Jenkins event ----------------------------------------------- */
}
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

wikipedia-en (Enlish)
wikipedia-de (German)
wikipedia-es (Spanish)
wikipedia-fr (French)
wikipedia-it (Italian)
wikipedia-ja (Japanese)
wikipedia-ko (Korean)
wikipedia-pt (Portuguese)
wikipedia-ru (Russian)
wikipedia-zh (Chinese)
wikipedia-nl (Dutch)
wikipedia-pl (Polish)
wikipedia-vi (Vietnamese)
wikipedia-cs (Czech)
wikipedia-tr (Turkish)
wikipedia-fi (Finnish)
wikipedia-no (Norwegian)
wikipedia-ro (Romanian)
wikipedia-uk (Ukranian)
wikipedia-ca (Catalan)
wikipedia-sv (Swedish)
wikipedia-sr (Serbian)
wikipedia-id (Indonesian)
wikipedia-hu (Hungarian)
wikipedia-sh (Serbo-Croatian)
wikipedia-ms (Malay)
wikipedia-hy (Armenian)
wikipedia-eu (Basque)
wikipedia-min (Minangkabau)
wikipedia-bg (Bulgarian)
wikipedia-cy (Welsh)
wikipedia-az (Azerbaijani)
wikipedia-ast (Asturian)


-ar     Arabic              --- *I still have ignored for now Arabic (ar) and Persian (fa).
-fa     Persian             --- *I still have ignored for now Arabic (ar) and Persian (fa).
-he     Hebrew              --- same as Arabic and Persian
-pnb    Western Punjabi     --- same as Arabic and Persian
-azb    South Azerbaijani   --- same as Arabic and Persian
-ur     Urdu                --- same as Arabic and Persian


-eo Esperanto --- But... let's skip Volapuk (Volapük vo) and Esperanto. I'm not sure I want to open that can of worms.

Next batch:
-szl Silesian
-af Afrikaans
-ka Georgian
-lt Lithuanian
-et Estonian
-nv Navajo

Next batch:
-hr Croatian    https://en.wikipedia.org/wiki/Croatian_language
-gl Galician    https://en.wikipedia.org/wiki/Galician_language
-br Breton      https://en.wikipedia.org/wiki/Breton_language

Next batch:
-la Latin   7292 https://en.wikipedia.org/wiki/Latin_language
-da Danish  7246 https://en.wikipedia.org/wiki/Danish_language

Next batch:
ml	6662    https://en.wikipedia.org/wiki/Malayalam             Malayalam
be	6435    https://en.wikipedia.org/wiki/Belarusian_language   Belarusian

Next batch:
th	6412    https://en.wikipedia.org/wiki/Thai_language     Thai
ms	4364    https://en.wikipedia.org/wiki/Malay_language    Malay

Next batch:
kk	4293    Kazakh  https://en.wikipedia.org/wiki/Kazakh_language
lv	4280    Latvian https://en.wikipedia.org/wiki/Latvian_language
is	4221    Icelandic   https://en.wikipedia.org/wiki/Icelandic_language

hy	4146    Armenian    https://en.wikipedia.org/wiki/Armenian_language
simple	4079 --- looks like just English
mrj	4051    Hill Mari   https://en.wikipedia.org/wiki/Hill_Mari_language

Next batch:
nn	4002    Norwegian (Nynorsk)     https://en.wikipedia.org/wiki/Nynorsk
sk	3736    Slovak                  https://en.wikipedia.org/wiki/Slovak_language

Next batch:
hsb	3502    Upper Sorbian   https://en.wikipedia.org/wiki/Upper_Sorbian_language
sl	3449    Slovenian       https://en.wikipedia.org/wiki/Slovene_language

Next batch:
pms	3369    Piedmontese     https://en.wikipedia.org/wiki/Piedmontese_language
ga	2976    Irish           https://en.wikipedia.org/wiki/Irish_language
bn	2865    Bengali         https://en.wikipedia.org/wiki/Bengali_language

START aggregate resource:
ta	2820    Tamil       https://en.wikipedia.org/wiki/Tamil_language

el	2665    Greek       https://en.wikipedia.org/wiki/Greek_language
mk	2583    Macedonian      https://en.wikipedia.org/wiki/Macedonian_language

ky	2364    Kirghiz     https://en.wikipedia.org/wiki/Kyrgyz_language


sco	2304
zh-yue	2193
hi	2113
fy	2062
tl	1803
jv	1745
ia	1664
ne	1647
oc	1621
qu	1583
be-x-old	1532
koi	1531
frr	1486
udm	1474
ba	1370
an	1295

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
80	            Saturday 2019-06-01 10:48:55 AM	{"media_resource.tab":499623,"taxon.tab":277263} -- ??? questionable decrease... WILL INVESTIGATE. All other languages are "Consistent OK"
80	            Sunday 2019-08-04 08:21:19 AM	{"media_resource.tab":757097,"taxon.tab":398984} -- back to nomral - Consistent OK
80	            Friday 2019-12-06 04:50:35 AM	{"media_resource.tab":760245,"taxon.tab":401004,"time_elapsed":{"sec":44547.6,"min":742.46,"hr":12.37}} OK
80              Wednesday 2020-02-12 09:42:40 AM{"media_resource.tab":769297,"taxon.tab":405446,"time_elapsed":{"sec":35740.92,"min":595.68,"hr":9.93}} OK

wikipedia-es	Sunday 2017-12-03 12:21:46 AM	{"media_resource.tab":300492,"taxon.tab":165487}
wikipedia-es	Saturday 2018-05-05 03:59:03 AM	{"media_resource.tab":300473,"taxon.tab":165477}
wikipedia-es	Tuesday 2018-10-16 12:05:22 AM	{"media_resource.tab":300460,"taxon.tab":165470}
wikipedia-es	Wednesday 2018-11-14 07:12:40 AM{"media_resource.tab":305501,"taxon.tab":168126}
wikipedia-es	Monday 2018-11-19 12:45:00 PM	{"media_resource.tab":181219,"taxon.tab":107133} -- big reduction
wikipedia-es	Monday 2018-11-19 05:22:14 PM	{"media_resource.tab":305501,"taxon.tab":168126} -- back to normal
wikipedia-es	Sunday 2018-12-16 10:52:06 AM	{"media_resource.tab":306063,"taxon.tab":168420} -- looking good :-)
wikipedia-es	Thursday 2019-02-14 02:31:24 PM	{"media_resource.tab":306886,"taxon.tab":168848} -- Consistent OK
wikipedia-es	Tuesday 2019-04-23 12:18:06 AM	{"media_resource.tab":307434,"taxon.tab":169155} -- consistent increase even after -> only taxon with object is included in DwCA
wikipedia-es	Saturday 2019-06-01 04:32:50 PM	{"media_resource.tab":308901,"taxon.tab":169932} -- Consistent OK
wikipedia-es	Monday 2019-08-05 04:07:27 AM	{"media_resource.tab":309524,"taxon.tab":170246} -- Consistent OK
wikipedia-es	Saturday 2019-12-07 06:02:16 AM	{"media_resource.tab":312775,"taxon.tab":172104,"time_elapsed":{"sec":21455.2,"min":357.59,"hr":5.96}} OK
wikipedia-es	Wednesday 2020-02-12 03:06:27 PM{"media_resource.tab":314378,"taxon.tab":172925,"time_elapsed":{"sec":14881.69,"min":248.03,"hr":4.13}} OK

wikipedia-it	Tuesday 2017-11-14 04:48:25 AM	{"media_resource.tab":64723,"taxon.tab":39992}
wikipedia-it	Monday 2017-12-04 05:16:39 AM	{"media_resource.tab":64861,"taxon.tab":40069}
wikipedia-it	Friday 2018-04-27 05:50:21 AM	{"media_resource.tab":64854,"taxon.tab":40067}
wikipedia-it	Thursday 2018-10-11 11:05:17 AM	{"media_resource.tab":64853,"taxon.tab":40067}
wikipedia-it	Tuesday 2018-11-13 07:56:41 PM	{"media_resource.tab":67470,"taxon.tab":41498}
wikipedia-it	Monday 2018-11-19 05:38:43 AM	{"media_resource.tab":67470,"taxon.tab":41498}
wikipedia-it	Sunday 2018-12-16 07:33:26 PM	{"media_resource.tab":67624,"taxon.tab":41605} -- looking good :-)
wikipedia-it	Friday 2019-02-15 12:22:29 AM	{"media_resource.tab":68176,"taxon.tab":41899} -- Consistent OK
wikipedia-it	Tuesday 2019-04-23 05:20:02 AM	{"media_resource.tab":68460,"taxon.tab":42065} -- consistent increase even after -> only taxon with object is included in DwCA
wikipedia-it	Saturday 2019-06-01 06:02:50 PM	{"media_resource.tab":68816,"taxon.tab":42275} -- Consistent OK
wikipedia-it	Monday 2019-08-05 09:43:59 AM	{"media_resource.tab":69122,"taxon.tab":42450} -- Consistent OK
wikipedia-it	Saturday 2019-12-07 11:38:19 AM	{"media_resource.tab":70036,"taxon.tab":42981,"time_elapsed":{"sec":5812.1,"min":96.87,"hr":1.61}} OK
wikipedia-it	Wednesday 2020-02-12 04:17:22 PM{"media_resource.tab":70425,"taxon.tab":43244,"time_elapsed":{"sec":3078.6,"min":51.31,"hr":0.86}} OK

wikipedia-de	Saturday 2017-11-11 08:52:35 PM	{"media_resource.tab":87725,"taxon.tab":55915}
957	            Monday 2017-12-04 05:20:51 AM	{"media_resource.tab":87940,"taxon.tab":56041}
957	            Friday 2018-04-27 02:57:47 AM	{"media_resource.tab":87934,"taxon.tab":56036}
957	            Thursday 2018-10-11 10:57:17 PM	{"media_resource.tab":87871,"taxon.tab":56015}
957	            Wednesday 2018-11-14 03:15:52 AM{"media_resource.tab":90281,"taxon.tab":57490}
957	            Monday 2018-11-19 05:37:09 AM	{"media_resource.tab":90281,"taxon.tab":57490}
957	            Monday 2018-12-17 07:31:51 AM	{"media_resource.tab":90502,"taxon.tab":57637} -- looking good :-)
957	            Friday 2019-02-15 12:13:40 PM	{"media_resource.tab":91356,"taxon.tab":58153} -- Consistent OK
957	            Tuesday 2019-04-23 10:46:42 AM	{"media_resource.tab":91948,"taxon.tab":58535} -- consistent increase even after -> only taxon with object is included in DwCA
957	            Saturday 2019-06-01 07:19:00 PM	{"media_resource.tab":92485,"taxon.tab":58906} -- Consistent OK
957	            Monday 2019-08-05 04:06:18 PM	{"media_resource.tab":93118,"taxon.tab":59264} -- Consistent OK
957	            Saturday 2019-12-07 07:05:55 PM	{"media_resource.tab":94255,"taxon.tab":59964,"time_elapsed":{"sec":6686.38,"min":111.44,"hr":1.86}} OK
957	            Thursday 2020-02-13 08:55:35 AM	{"media_resource.tab":94413,"taxon.tab":60261,"time_elapsed":{"sec":6187.82,"min":103.13,"hr":1.72}} OK

wikipedia-fr	Sunday 2017-12-03 12:04:24 AM	{"media_resource.tab":214962,"taxon.tab":119824}
wikipedia-fr	Sunday 2018-04-29 04:10:02 PM	{"media_resource.tab":214956,"taxon.tab":119821}
wikipedia-fr	Sunday 2018-10-14 11:36:53 AM	{"media_resource.tab":214976,"taxon.tab":119816}
wikipedia-fr	Wednesday 2018-11-14 09:40:10 PM{"media_resource.tab":224117,"taxon.tab":124547} might change...
wikipedia-fr	Thursday 2018-11-15 05:24:25 AM	{"media_resource.tab":224055,"taxon.tab":124547}
wikipedia-fr	Monday 2018-11-19 07:24:35 AM	{"media_resource.tab":224056,"taxon.tab":124547}
wikipedia-fr	Tuesday 2018-12-18 11:41:11 AM	{"media_resource.tab":225329,"taxon.tab":125283} -- looking good :-)
wikipedia-fr	Saturday 2019-02-16 05:44:04 PM	{"media_resource.tab":226858,"taxon.tab":126145} -- Consistent OK
wikipedia-fr	Wednesday 2019-04-24 01:53:21 AM{"media_resource.tab":229673,"taxon.tab":127530} -- consistent increase even after -> only taxon with object is included in DwCA
wikipedia-fr	Saturday 2019-06-01 10:00:05 PM	{"media_resource.tab":232256,"taxon.tab":128840} -- Consistent OK
wikipedia-fr	Tuesday 2019-08-06 08:00:27 AM	{"media_resource.tab":233979,"taxon.tab":129714} -- Consistent OK
wikipedia-fr	Sunday 2019-12-08 03:02:57 PM	{"media_resource.tab":236596,"taxon.tab":131404,"time_elapsed":{"sec":17997.21,"min":299.95,"hr":5}} OK
wikipedia-fr	Friday 2020-02-14 03:23:14 AM	{"media_resource.tab":238880,"taxon.tab":132543,"time_elapsed":{"sec":16649.6,"min":277.49,"hr":4.62}} OK

wikipedia-ja	Friday 2017-11-10 08:35:41 AM	{"media_resource.tab":26208,"taxon.tab":20431}
wikipedia-ja	Saturday 2017-12-02 10:53:40 PM	{"media_resource.tab":26264,"taxon.tab":20475}
wikipedia-ja	Friday 2018-04-27 03:18:00 PM	{"media_resource.tab":26262,"taxon.tab":20474}
wikipedia-ja	Friday 2018-10-12 11:31:52 AM	{"media_resource.tab":26259,"taxon.tab":20474}
wikipedia-ja	Saturday 2018-11-17 08:39:01 AM	{"media_resource.tab":27790,"taxon.tab":21652}
wikipedia-ja	Monday 2018-11-19 10:43:18 AM	{"media_resource.tab":27787,"taxon.tab":21652}
wikipedia-ja	Wednesday 2018-12-19 01:33:58 AM{"media_resource.tab":27841,"taxon.tab":21694} -- looking good :-)
wikipedia-ja	Sunday 2019-02-17 07:03:28 AM	{"media_resource.tab":27966,"taxon.tab":21789} -- Consistent OK
wikipedia-ja	Wednesday 2019-04-24 07:39:22 AM{"media_resource.tab":28101,"taxon.tab":21901} -- consistent increase even after -> only taxon with object is included in DwCA
wikipedia-ja	Saturday 2019-06-01 10:37:00 PM	{"media_resource.tab":28191,"taxon.tab":21976} -- Consistent OK
wikipedia-ja	Tuesday 2019-08-06 02:04:11 PM	{"media_resource.tab":28329,"taxon.tab":22058} -- Consistent OK
wikipedia-ja	Sunday 2019-12-08 10:35:20 PM	{"media_resource.tab":28745,"taxon.tab":22370,"time_elapsed":{"sec":27134.47,"min":452.24,"hr":7.54}} OK
wikipedia-ja	Friday 2020-02-14 10:26:53 AM	{"media_resource.tab":28788,"taxon.tab":22503,"time_elapsed":{"sec":25412.39,"min":423.54,"hr":7.06}} OK

wikipedia-ko	Thursday 2017-11-30 10:29:47 PM	{"media_resource.tab":24527,"taxon.tab":16954}
wikipedia-ko	Sunday 2017-12-03 12:48:17 AM	{"media_resource.tab":24701,"taxon.tab":17060}
wikipedia-ko	Tuesday 2018-05-01 03:32:49 PM	{"media_resource.tab":24654,"taxon.tab":17047}
wikipedia-ko	Friday 2018-11-16 01:45:28 AM	{"media_resource.tab":28204,"taxon.tab":19078}
wikipedia-ko	Monday 2018-11-19 10:28:56 AM	{"media_resource.tab":28204,"taxon.tab":19078}
wikipedia-ko	Wednesday 2018-12-19 01:40:27 AM{"media_resource.tab":28249,"taxon.tab":19117} -- looking good :-)
wikipedia-ko	Sunday 2019-02-17 08:14:59 AM	{"media_resource.tab":28498,"taxon.tab":19246} -- Consistent OK
wikipedia-ko	Wednesday 2019-04-24 08:10:41 AM{"media_resource.tab":29273,"taxon.tab":19794} -- consistent increase even after -> only taxon with object is included in DwCA
wikipedia-ko	Saturday 2019-06-01 10:47:17 PM	{"media_resource.tab":29999,"taxon.tab":20205} -- Consistent OK
wikipedia-ko	Tuesday 2019-08-06 02:41:24 PM	{"media_resource.tab":30430,"taxon.tab":20479} -- Consistent OK
wikipedia-ko	Sunday 2019-12-08 11:31:00 PM	{"media_resource.tab":31582,"taxon.tab":21197,"time_elapsed":{"sec":30474.15,"min":507.9,"hr":8.47}} OK
wikipedia-ko	Friday 2020-02-14 11:15:04 AM	{"media_resource.tab":31862,"taxon.tab":21412,"time_elapsed":{"sec":28303.91,"min":471.73,"hr":7.86}} OK

wikipedia-pt	Sunday 2017-12-03 01:34:21 AM	{"media_resource.tab":192390,"taxon.tab":108840}
wikipedia-pt	Friday 2018-05-04 01:26:59 PM	{"media_resource.tab":192384,"taxon.tab":108838}
wikipedia-pt	Monday 2018-11-19 12:44:50 PM	{"media_resource.tab":156778,"taxon.tab":91720} -- big reduction
wikipedia-pt	Tuesday 2018-11-20 05:17:44 AM	{"media_resource.tab":197927,"taxon.tab":111719} -- back to normal
wikipedia-pt	Saturday 2018-12-22 11:51:14 AM	{"media_resource.tab":198183,"taxon.tab":111889} -- looking good :-)
wikipedia-pt	Sunday 2019-02-17 06:16:01 PM	{"media_resource.tab":198441,"taxon.tab":112063} -- consistent OK. Started the 6-connectors run.
wikipedia-pt	Wednesday 2019-04-24 01:28:22 PM{"media_resource.tab":200018,"taxon.tab":112900} -- consistent increase even after -> only taxon with object is included in DwCA
wikipedia-pt	Sunday 2019-06-02 12:29:35 AM	{"media_resource.tab":200968,"taxon.tab":113400} -- consistent OK
wikipedia-pt	Tuesday 2019-08-06 07:36:33 PM	{"media_resource.tab":201181,"taxon.tab":113524} -- consistent OK
wikipedia-pt	Monday 2019-12-09 06:58:13 AM	{"media_resource.tab":201459,"taxon.tab":113797,"time_elapsed":{"sec":14445.76,"min":240.76,"hr":4.01}} OK
wikipedia-pt	Friday 2020-02-14 05:58:38 PM	{"media_resource.tab":200572,"taxon.tab":113431,"time_elapsed":{"sec":13086.54,"min":218.11,"hr":3.64}} decreased but still reasonable

wikipedia-ru	Sunday 2017-11-12 12:03:09 PM	{"media_resource.tab":77531,"taxon.tab":47336}
wikipedia-ru	Saturday 2017-12-02 11:31:53 PM	{"media_resource.tab":77649,"taxon.tab":47398}
wikipedia-ru	Saturday 2018-04-28 08:31:18 PM	{"media_resource.tab":77630,"taxon.tab":47351}
wikipedia-ru	Saturday 2018-10-13 03:14:13 AM	{"media_resource.tab":77574,"taxon.tab":47321}
wikipedia-ru	Saturday 2018-11-17 03:13:13 AM	{"media_resource.tab":81804,"taxon.tab":49707}
wikipedia-ru	Monday 2018-11-19 11:20:03 AM	{"media_resource.tab":81804,"taxon.tab":49707}
wikipedia-ru	Thursday 2018-12-20 04:02:22 AM	{"media_resource.tab":82064,"taxon.tab":49847} -- looking good :-)
wikipedia-ru	Monday 2019-02-18 09:29:31 AM	{"media_resource.tab":82814,"taxon.tab":50255} -- consistent OK.
wikipedia-ru	Wednesday 2019-04-24 06:38:49 PM{"media_resource.tab":83696,"taxon.tab":50801} -- consistent increase even after -> only taxon with object is included in DwCA
wikipedia-ru	Saturday 2019-06-01 11:30:10 PM	{"media_resource.tab":84584,"taxon.tab":51341} -- consistent OK.
wikipedia-ru	Wednesday 2019-08-07 01:24:05 AM{"media_resource.tab":85285,"taxon.tab":51763} -- consistent OK
wikipedia-ru	Monday 2019-12-09 01:14:36 PM	{"media_resource.tab":86861,"taxon.tab":52711,"time_elapsed":{"sec":79890.51,"min":1331.51,"hr":22.19}} OK
wikipedia-ru	Saturday 2020-02-15 12:27:06 AM	{"media_resource.tab":87583,"taxon.tab":53186,"time_elapsed":{"sec":75825.96,"min":1263.77,"hr":21.06}} OK

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
wikipedia-zh	Sunday 2019-06-02 02:57:45 AM	{"media_resource.tab":178460,"taxon.tab":104587} -- consistent OK
wikipedia-zh	Wednesday 2019-08-07 05:45:01 AM{"media_resource.tab":180820,"taxon.tab":105668} -- consistent OK
wikipedia-zh	Monday 2019-12-09 08:11:09 PM	{"media_resource.tab":187787,"taxon.tab":109444,"time_elapsed":{"sec":14459.96,"min":241,"hr":4.02}} OK
wikipedia-zh	Saturday 2020-02-15 05:56:19 AM	{"media_resource.tab":187730,"taxon.tab":109498,"time_elapsed":{"sec":12381.98,"min":206.37,"hr":3.44}} OK
--------------------------------------------- START OF NEW BATCH ---------------------------------------------
wikipedia-vo	Wednesday 2019-04-17 09:43:21 AM{"media_resource.tab":34,"taxon.tab":118} - asked by Jen to ignore for now.

langs_with_multiple_connectors: "nl", "pl", "sv", "vi"
wikipedia-nl	Thursday 2019-04-18 09:03:04 AM	{"media_resource.tab":975151,"taxon.tab":950717}
wikipedia-nl	Sunday 2019-08-11 02:03:22 PM	{"media_resource.tab":975588,"taxon.tab":950697} OK
wikipedia-nl	Tuesday 2019-12-17 06:19:27 PM	{"media_resource.tab":1770142,"taxon.tab":951601,"time_elapsed":{"sec":78226.17,"min":1303.77,"hr":21.73}} big increase in objects
wikipedia-nl	Wednesday 2020-02-19 01:37:09 PM{"media_resource.tab":1773494,"taxon.tab":952431,"time_elapsed":{"sec":102373.69,"min":1706.23,"hr":28.44,"day":1.19}} consistent OK

wikipedia-pl	Thursday 2019-04-18 07:30:05 PM	{"media_resource.tab":92390,"taxon.tab":56112}
wikipedia-pl	Sunday 2019-08-11 07:14:48 PM	{"media_resource.tab":93531,"taxon.tab":56711} OK
wikipedia-pl	Wednesday 2019-12-18 12:01:58 AM{"media_resource.tab":93983,"taxon.tab":57065,"time_elapsed":{"sec":3904.94,"min":65.08,"hr":1.08}} OK
wikipedia-pl	Wednesday 2020-02-19 07:49:29 PM{"media_resource.tab":94500,"taxon.tab":57463,"time_elapsed":{"sec":5394.76,"min":89.91,"hr":1.5}} OK

wikipedia-vi	Saturday 2019-04-27 01:40:44 AM	{"media_resource.tab":1582933,"taxon.tab":826812}
wikipedia-vi	Wednesday 2019-08-14 07:34:50 PM{"media_resource.tab":1584541,"taxon.tab":827634} OK
wikipedia-vi	Sunday 2020-02-23 08:36:50 PM	{"media_resource.tab":1585312,"taxon.tab":827677,"time_elapsed":{"sec":132729.61,"min":2212.16,"hr":36.87,"day":1.54}}

wikipedia-sv	Tuesday 2019-04-30 09:04:30 PM	{"media_resource.tab":80151,"taxon.tab":51485}
wikipedia-sv	Friday 2019-08-16 04:32:53 PM	{"media_resource.tab":80428,"taxon.tab":51666} OK
wikipedia-sv	Tuesday 2020-02-25 10:36:42 PM	{"media_resource.tab":92614,"taxon.tab":59816,"time_elapsed":{"sec":129981.75,"min":2166.36,"hr":36.11,"day":1.5}}

Not yet with multiple connectors: "no", "fi", "ca", "uk", "tr", "ro", "cs"
wikipedia-cs	Saturday 2019-04-27 06:30:40 AM	{"media_resource.tab":23413,"taxon.tab":17991}
wikipedia-cs	Thursday 2019-08-15 12:27:48 AM	{"media_resource.tab":23627,"taxon.tab":18160} OK
wikipedia-cs	Monday 2020-02-24 01:40:53 AM	{"media_resource.tab":24113,"taxon.tab":18596,"time_elapsed":{"sec":18234.27,"min":303.9,"hr":5.07}}

wikipedia-tr	Saturday 2019-04-27 07:39:05 AM	{"media_resource.tab":11114,"taxon.tab":9113}
wikipedia-tr	Wednesday 2019-08-14 10:02:10 PM{"media_resource.tab":11146,"taxon.tab":9139} OK
wikipedia-tr	Sunday 2020-02-23 11:13:08 PM	{"media_resource.tab":11201,"taxon.tab":9359,"time_elapsed":{"sec":9368.58,"min":156.14,"hr":2.6}}

wikipedia-fi	Saturday 2019-04-27 09:26:44 AM	{"media_resource.tab":39542,"taxon.tab":26545}
wikipedia-fi	Thursday 2019-08-15 03:29:14 AM	{"media_resource.tab":40149,"taxon.tab":27013} OK
wikipedia-fi	Monday 2020-02-24 05:25:36 AM	{"media_resource.tab":42163,"taxon.tab":28356,"time_elapsed":{"sec":31716.74,"min":528.61,"hr":8.81}}

wikipedia-no	Saturday 2019-04-27 02:04:01 PM	{"media_resource.tab":61203,"taxon.tab":37024}
wikipedia-no	Thursday 2019-08-15 07:56:33 AM	{"media_resource.tab":62169,"taxon.tab":37534} OK
wikipedia-no	Monday 2020-02-24 10:31:26 AM	{"media_resource.tab":64683,"taxon.tab":39117,"time_elapsed":{"sec":50066.81,"min":834.45,"hr":13.91}}

wikipedia-ro	Saturday 2019-04-27 02:09:48 PM	{"media_resource.tab":60752,"taxon.tab":34846}
wikipedia-ro	Thursday 2019-08-15 07:49:25 AM	{"media_resource.tab":60891,"taxon.tab":34936} OK
wikipedia-ro	Monday 2020-02-24 09:52:29 AM	{"media_resource.tab":61125,"taxon.tab":35235,"time_elapsed":{"sec":47730.09,"min":795.5,"hr":13.26}}

wikipedia-uk	Saturday 2019-04-27 03:30:05 PM	{"media_resource.tab":33509,"taxon.tab":30899}
wikipedia-uk	Thursday 2019-08-15 06:32:40 AM	{"media_resource.tab":37569,"taxon.tab":33991} OK
wikipedia-uk	Monday 2020-02-24 09:06:21 AM	{"media_resource.tab":40135,"taxon.tab":36523,"time_elapsed":{"sec":44962.12,"min":749.37,"hr":12.49}}

wikipedia-ca	Sunday 2019-04-28 03:07:45 AM	{"media_resource.tab":124020,"taxon.tab":70790}
wikipedia-ca	Thursday 2019-08-15 08:52:01 PM	{"media_resource.tab":124621,"taxon.tab":71136} OK
wikipedia-ca	Tuesday 2020-02-25 12:18:35 AM	{"media_resource.tab":124044,"taxon.tab":71415,"time_elapsed":{"sec":99695.53,"min":1661.59,"hr":27.69,"day":1.15}}

wikipedia-sr	Sunday 2019-10-27 09:47:57 AM	{"media_resource.tab":19809,"taxon.tab":15500,"time_elapsed":false}
wikipedia-sr	Tuesday 2020-02-25 04:55:39 AM	{"media_resource.tab":19539,"taxon.tab":15459,"time_elapsed":{"sec":16616.7,"min":276.95,"hr":4.62}}

wikipedia-id (Indonesian) Tuesday 2019-10-29 02:46:33 PM{"media_resource.tab":244932,"taxon.tab":130506,"time_elapsed":false}
wikipedia-id             Thursday 2020-02-27 12:00:57 PM{"media_resource.tab":246799, "taxon.tab":131970, "time_elapsed":{"sec":198309.24,"min":3305.15,"hr":55.09,"day":2.3}}
wikipedia-id            Monday 2020-03-02 12:29:14 PM	{"media_resource.tab":246801, "taxon.tab":131971, "time_elapsed":{"sec":11721.51, "min":195.36, "hr":3.26}}

Below here: starts with multiple connector v2:

wikipedia-hu	Thursday 2019-10-31 09:12:57 AM	{"media_resource.tab":43804,"taxon.tab":28245,"time_elapsed":{"sec":36599.33,"min":609.99,"hr":10.17}}
wikipedia-hu	Friday 2020-02-28 12:24:22 PM	{"media_resource.tab":44458,"taxon.tab":28657,"time_elapsed":{"sec":33891.53,"min":564.86,"hr":9.41}}

wikipedia-sh	Sunday 2019-11-03 10:54:20 PM	{"media_resource.tab":4145,"taxon.tab":3960,"time_elapsed":{"sec":3963.9,"min":66.07,"hr":1.1}}
wikipedia-sh	Thursday 2019-11-07 06:07:45 AM	{"media_resource.tab":4068,"taxon.tab":3973,"time_elapsed":{"sec":857.63,"min":14.29,"hr":0.24}}
wikipedia-sh	Friday 2019-11-15 10:57:39 AM	{"media_resource.tab":4068,"taxon.tab":3973,"time_elapsed":{"sec":1130.61,"min":18.84,"hr":0.31}}
wikipedia-sh	Friday 2020-02-28 01:26:28 PM	{"media_resource.tab":4055, "taxon.tab":4000, "time_elapsed":{"sec":3717.54, "min":61.96, "hr":1.03}}

wikipedia-eu	Tuesday 2019-11-05 05:55:55 PM	{"media_resource.tab":142743,"taxon.tab":82575,"time_elapsed":{"sec":112061.2,"min":1867.69,"hr":31.13,"day":1.3}}
wikipedia-eu	Saturday 2020-02-29 04:56:45 PM	{"media_resource.tab":142295, "taxon.tab":82493, "time_elapsed":{"sec":6504.3, "min":108.41, "hr":1.81}}

wikipedia-cy	Thursday 2019-11-14 11:33:19 AM	{"media_resource.tab":34585,"taxon.tab":23238,"time_elapsed":{"sec":26810.84,"min":446.85,"hr":7.45}}
wikipedia-cy	Friday 2020-02-28 10:05:55 AM	{"media_resource.tab":34735,"taxon.tab":23445,"time_elapsed":{"sec":25500.34,"min":425.01,"hr":7.08}}

wikipedia-ast	Thursday 2019-11-14 01:18:56 PM	{"media_resource.tab":30745,"taxon.tab":21852,"time_elapsed":{"sec":3411.17,"min":56.85,"hr":0.95}}
wikipedia-ast	Sunday 2019-12-01 07:22:08 PM	{"media_resource.tab":30773,"taxon.tab":21869,"time_elapsed":{"sec":2550.47,"min":42.51,"hr":0.71}} Consistent OK
wikipedia-ast	Monday 2020-02-24 12:27:28 PM	{"media_resource.tab":30760,"taxon.tab":21925,"time_elapsed":{"sec":25302.2,"min":421.7,"hr":7.03}}

wikipedia-az	Thursday 2019-11-14 12:21:35 PM	{"media_resource.tab":33656,"taxon.tab":19977,"time_elapsed":{"sec":2888.26,"min":48.14,"hr":0.8}}
wikipedia-az	Sunday 2019-12-01 07:20:43 PM	{"media_resource.tab":33670,"taxon.tab":19994,"time_elapsed":{"sec":2422.35,"min":40.37,"hr":0.67}} Consistent OK
wikipedia-az	Monday 2020-02-24 08:01:48 PM	{"media_resource.tab":33671,"taxon.tab":20019,"time_elapsed":{"sec":27251.88,"min":454.2,"hr":7.57}}

even when everything is cached already: execution time for 'min' is: Took 3 days 6 hr
wikipedia-min	Tuesday 2019-11-12 05:33:48 PM	{                            "taxon.tab":175486,"time_elapsed":{"sec":14126.38,"min":235.44,"hr":3.92}}
wikipedia-min	Thursday 2019-11-14 09:33:15 AM	{"media_resource.tab":233143,"taxon.tab":175486,"time_elapsed":{"sec":15853.41,"min":264.22,"hr":4.4}}
wikipedia-min	Friday 2019-11-15 04:50:27 PM	{"media_resource.tab":233143,"taxon.tab":175486,"time_elapsed":{"sec":17561.97,"min":292.7,"hr":4.88}}
wikipedia-min	Sunday 2019-12-01 12:32:16 PM	{"media_resource.tab":36571,"taxon.tab":33504,"time_elapsed":{"sec":284106.26,"min":4735.1,"hr":78.92,"day":3.29}}
wikipedia-min	Tuesday 2020-02-25 01:37:22 AM	{"media_resource.tab":233146,"taxon.tab":175538,"time_elapsed":{"sec":16030.08,"min":267.17,"hr":4.45}}

wikipedia-bg	Saturday 2019-11-16 12:44:27 AM	{"media_resource.tab":60646,"taxon.tab":41185,"time_elapsed":{"sec":50384.04,"min":839.73,"hr":14}}
wikipedia-bg	Tuesday 2020-02-25 09:57:49 AM	{"media_resource.tab":60449,"taxon.tab":41001,"time_elapsed":{"sec":50016.98,"min":833.62,"hr":13.89}}

wikipedia-szl (Silesian)	Monday 2019-12-30 09:10:41 PM	{"media_resource.tab":84818,"taxon.tab":51729,"time_elapsed":{"sec":4974.49,"min":82.91,"hr":1.38}}
wikipedia-szl	            Wednesday 2020-02-26 04:57:36 AM{"media_resource.tab":84820,"taxon.tab":51761,"time_elapsed":{"sec":68376.8,"min":1139.61,"hr":18.99}}

wikipedia-af (Afrikaans)	Monday 2019-12-30 09:32:55 PM	{"media_resource.tab":24930,"taxon.tab":15715,"time_elapsed":{"sec":1324.55,"min":22.08,"hr":0.37}}
wikipedia-af	            Wednesday 2020-02-26 10:29:16 AM{"media_resource.tab":24998,"taxon.tab":15805,"time_elapsed":{"sec":19890.93,"min":331.52,"hr":5.53}}

wikipedia-ka Monday 2019-12-30 09:53:27 PM	{"media_resource.tab":20898,"taxon.tab":12929,"time_elapsed":{"sec":1226.34,"min":20.44,"hr":0.34}}
wikipedia-ka Monday 2020-02-24 03:24:11 PM	{"media_resource.tab":20916,"taxon.tab":13013,"time_elapsed":{"sec":17555.45,"min":292.59,"hr":4.88}}

wikipedia-lt (Lithuanian)	Monday 2019-12-30 10:13:07 PM	{"media_resource.tab":15664,"taxon.tab":12845,"time_elapsed":{"sec":1171.69,"min":19.53,"hr":0.33}}
wikipedia-lt	            Monday 2020-02-24 07:27:31 PM	{"media_resource.tab":15654,"taxon.tab":12910,"time_elapsed":{"sec":14590.23,"min":243.17,"hr":4.05}}

wikipedia-et (Estonian)	Tuesday 2019-12-31 01:17:03 AM	{"media_resource.tab":14231,"taxon.tab":11499,"time_elapsed":{"sec":11027.62,"min":183.79,"hr":3.06}}
wikipedia-et	        Monday 2020-02-24 10:52:21 PM	{"media_resource.tab":14252,"taxon.tab":11591,"time_elapsed":{"sec":12278.48,"min":204.64,"hr":3.41}}

wikipedia-nv (Navajo)	Tuesday 2019-12-31 04:43:56 AM	{"media_resource.tab":8385,"taxon.tab":10836,"time_elapsed":{"sec":12404.83,"min":206.75,"hr":3.45}}
wikipedia-nv	        Tuesday 2020-02-25 03:12:31 AM	{"media_resource.tab":10063,"taxon.tab":12860,"time_elapsed":{"sec":15603.69,"min":260.06,"hr":4.33}}

next batch:
wikipedia-gl	Wednesday 2020-01-15 01:29:21 AM{"media_resource.tab":10738,"taxon.tab":8595,"time_elapsed":{"sec":8959.67,"min":149.33,"hr":2.49}}
wikipedia-gl	Tuesday 2020-02-25 03:33:55 AM	{"media_resource.tab":10804,"taxon.tab":8640,"time_elapsed":{"sec":1277.25,"min":21.29,"hr":0.35}}

wikipedia-br	Wednesday 2020-01-15 02:00:51 AM{"media_resource.tab":12758,"taxon.tab":9382,"time_elapsed":{"sec":10780.27,"min":179.67,"hr":2.99}}
wikipedia-br	Tuesday 2020-02-25 03:59:11 AM	{"media_resource.tab":12728,"taxon.tab":9382,"time_elapsed":{"sec":1508.53,"min":25.14,"hr":0.42}}

wikipedia-hr	Wednesday 2020-01-15 02:07:17 AM{"media_resource.tab":13051,"taxon.tab":10114,"time_elapsed":{"sec":11335.24,"min":188.92,"hr":3.15}}
wikipedia-hr	Monday 2020-02-24 10:25:47 AM	{"media_resource.tab":13632,"taxon.tab":10447,"time_elapsed":{"sec":1991.1,"min":33.19,"hr":0.55}}

wikipedia-la	Wednesday 2020-01-29 11:03:21 AM{"media_resource.tab":8579,"taxon.tab":6704,"time_elapsed":{"sec":10414.46,"min":173.57,"hr":2.89}}
wikipedia-la	Monday 2020-02-24 10:49:45 AM	{"media_resource.tab":8746,"taxon.tab":6807,"time_elapsed":{"sec":1429.66,"min":23.83,"hr":0.4}}

wikipedia-da	Wednesday 2020-01-29 12:00:56 PM{"media_resource.tab":11293,"taxon.tab":9441,"time_elapsed":{"sec":13883.67,"min":231.39,"hr":3.86}}
wikipedia-da	Monday 2020-02-24 11:14:38 AM	{"media_resource.tab":11291,"taxon.tab":9443,"time_elapsed":{"sec":1487.04,"min":24.78,"hr":0.41}}

wikipedia-ml	Friday 2020-02-07 02:11:32 AM	{"media_resource.tab":11199,"taxon.tab":9874,"time_elapsed":{"sec":10098.64,"min":168.31,"hr":2.81}}
wikipedia-ml	Monday 2020-02-24 11:38:56 AM	{"media_resource.tab":11199,"taxon.tab":9874,"time_elapsed":{"sec":1450.03,"min":24.17,"hr":0.4}}

wikipedia-be	Friday 2020-02-07 02:12:26 AM	{"media_resource.tab":10961,"taxon.tab":8945,"time_elapsed":{"sec":10086.99,"min":168.12,"hr":2.8}}
wikipedia-be	Monday 2020-02-24 12:01:08 PM	{"media_resource.tab":10961,"taxon.tab":8945,"time_elapsed":{"sec":1322.46,"min":22.04,"hr":0.37}}

wikipedia-ms	Friday 2020-02-07 10:58:56 AM	{"media_resource.tab":7383,"taxon.tab":6862,"time_elapsed":{"sec":7194.9,"min":119.92,"hr":2}}
wikipedia-ms	Monday 2020-02-24 12:20:10 PM	{"media_resource.tab":7385,"taxon.tab":6862,"time_elapsed":{"sec":1134.02,"min":18.9,"hr":0.32}}

wikipedia-hy	Tuesday 2019-11-05 03:27:00 AM	{"media_resource.tab":6951,"taxon.tab":6345,"time_elapsed":{"sec":7314.49,"min":121.91,"hr":2.03}}
wikipedia-hy	Monday 2020-02-24 11:01:45 AM	{"media_resource.tab":7099,"taxon.tab":6491,"time_elapsed":{"sec":6913.5,"min":115.23,"hr":1.92}}

wikipedia-th	Friday 2020-02-07 11:42:25 AM	{"media_resource.tab":10780,"taxon.tab":8960,"time_elapsed":{"sec":9871.88,"min":164.53,"hr":2.74}}
wikipedia-th	Sunday 2020-02-23 11:33:09 PM	{"media_resource.tab":10784,"taxon.tab":8963,"time_elapsed":{"sec":1194.28,"min":19.9,"hr":0.33}}

wikipedia-lv	Monday 2020-02-10 11:49:36 AM	{"media_resource.tab":6742,"taxon.tab":5371,"time_elapsed":{"sec":6308.07,"min":105.13,"hr":1.75}}
wikipedia-lv	Monday 2020-02-24 12:07:56 AM	{"media_resource.tab":6742,"taxon.tab":5371,"time_elapsed":{"sec":1006.36,"min":16.77,"hr":0.28}}

wikipedia-kk	Monday 2020-02-10 11:55:42 AM	{"media_resource.tab":7291,"taxon.tab":6207,"time_elapsed":{"sec":6668.31,"min":111.14,"hr":1.85}}
wikipedia-kk	Sunday 2020-02-23 11:51:02 PM	{"media_resource.tab":7291,"taxon.tab":6207,"time_elapsed":{"sec":1068.04,"min":17.8,"hr":0.3}}

wikipedia-is	Monday 2020-02-10 12:03:32 PM	{"media_resource.tab":7556,"taxon.tab":6525,"time_elapsed":{"sec":7141.14,"min":119.02,"hr":1.98}}
wikipedia-is	Monday 2020-02-24 12:25:21 AM	{"media_resource.tab":7556,"taxon.tab":6525,"time_elapsed":{"sec":1036.59,"min":17.28,"hr":0.29}}

wikipedia-mrj	Thursday 2020-02-20 05:20:36 AM	{"media_resource.tab":5713,"taxon.tab":5402,"time_elapsed":{"sec":6659.51,"min":110.99,"hr":1.85}}
wikipedia-mrj	Monday 2020-02-24 11:22:46 AM	{"media_resource.tab":5713,"taxon.tab":5402,"time_elapsed":{"sec":1255.3,"min":20.92,"hr":0.35}}

wikipedia-nn	Friday 2020-02-21 03:04:41 AM	{"media_resource.tab":6438,"taxon.tab":5674,"time_elapsed":{"sec":5522.48,"min":92.04,"hr":1.53}}
wikipedia-nn	Tuesday 2020-02-25 10:59:03 PM	{"media_resource.tab":6438,"taxon.tab":5674,"time_elapsed":{"sec":1333,"min":22.22,"hr":0.37}}

wikipedia-sk	Friday 2020-02-21 03:21:29 AM	{"media_resource.tab":5664,"taxon.tab":5537,"time_elapsed":{"sec":1001.22,"min":16.69,"hr":0.28}}
wikipedia-sk	Tuesday 2020-02-25 11:16:37 PM	{"media_resource.tab":5664,"taxon.tab":5537,"time_elapsed":{"sec":1047.25,"min":17.45,"hr":0.29}}

wikipedia-pms	Piedmontese Monday 2020-02-24 11:59:54 AM	{"media_resource.tab":5563,"taxon.tab":5039,"time_elapsed":{"sec":1028.46,"min":17.14,"hr":0.29}}
wikipedia-ga	Irish       Monday 2020-02-24 01:26:28 PM	{"media_resource.tab":3372,"taxon.tab":5891,"time_elapsed":{"sec":1111.38,"min":18.52,"hr":0.31}}

wikipedia-bn	Bengali Monday 2020-02-24 11:27:12 AM	{"media_resource.tab":4470,"taxon.tab":4891,"time_elapsed":{"sec":4487.1,"min":74.79,"hr":1.25}}
wikipedia-bn	        Tuesday 2020-02-25 11:34:04 PM	{"media_resource.tab":4470,"taxon.tab":4891,"time_elapsed":{"sec":1039.38,"min":17.32,"hr":0.29}}

wikipedia-sl	Tuesday 2020-02-25 04:47:06 AM	{"media_resource.tab":4976,"taxon.tab":5769,"time_elapsed":{"sec":4786.97,"min":79.78,"hr":1.33}}

wikipedia-hsb	Tuesday 2020-02-25 05:05:10 AM	{"media_resource.tab":6512,"taxon.tab":4357,"time_elapsed":{"sec":5952.85,"min":99.21,"hr":1.65}}

wikipedia-war	Tuesday 2019-11-19 02:12:15 PM	{"media_resource.tab":2202108,"taxon.tab":1188292,"time_elapsed":{"sec":108798.46,"min":1813.31,"hr":30.22,"day":1.26}}
wikipedia-war	Friday 2020-02-28 03:18:10 PM	{"media_resource.tab":2201295,"taxon.tab":1187955,"time_elapsed":{"sec":105632.28,"min":1760.54,"hr":29.34,"day":1.22}}

wikipedia-ceb	Thursday 2019-11-21 02:50:18 AM	{"media_resource.tab":235,"taxon.tab":500,"time_elapsed":{"sec":92347.22,"min":1539.12,"hr":25.65,"day":1.07}}
wikipedia-ceb	Monday 2019-11-25 03:41:34 PM	{"media_resource.tab":235,"taxon.tab":500,"time_elapsed":{"sec":107687.93,"min":1794.8,"hr":29.91,"day":1.25}}
wikipedia-ceb	Wednesday 2020-02-26 01:03:38 PM{"media_resource.tab":242,"taxon.tab":517,"time_elapsed":{"sec":99250.21,"min":1654.17,"hr":27.57,"day":1.15}}

wikipedia-ta	Thursday 2020-03-05 05:14:42 AM	{"media_resource.tab":5129, "taxon.tab":5541, "time_elapsed":{"sec":5169.46, "min":86.16, "hr":1.44}}
wikipedia-ta	Friday 2020-03-06 02:59:05 AM	{"media_resource.tab":5129, "taxon.tab":5541, "time_elapsed":{"sec":1735.28, "min":28.92, "hr":0.48}}

wikipedia-el	Friday 2020-03-06 03:34:04 AM	{"media_resource.tab":3922, "taxon.tab":4310, "time_elapsed":{"sec":3654.53, "min":60.91, "hr":1.02}}

wikipedia-mk	Friday 2020-03-06 09:22:03 AM	{"media_resource.tab":3127, "taxon.tab":3216, "time_elapsed":{"sec":3265.6, "min":54.43, "hr":0.91}}

language	count
sv	1331982 multiple connector v1
ceb	1160652 not multiple. But many bots. taxon.tab 517 only
war	1141301 multiple already v2
nl	895922  multiple connector v1
vi	801467  multiple connector v1
en	400337  multiple connector v1
min	166065  multiple already v2
es	164332  multiple connector v1
id	124610  multiple already v2
fr	123447  multiple connector v1
pt	107621  multiple connector v1
zh	97549   multiple connector v1
eu	73404   takes a little over 1 day
ca	65155   takes a little over 1 day
pl	49360   multiple connector v1

---------------------------------------------------------------------------------
*/
/*
this is a request made by wikimedia harvester (71): this 2 are same, first is a subset of the 2nd. And one is urlencoded() other is not.
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo|categories&iiprop=url|mime|mediatype&cllimit=500&redirects&titles=File%3AROSMARINUS+OFFICINALIS+-+MORROCURT+-+IB-976+(Roman%C3%AD).JPG
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo%7Ccategories&iiprop=url%7Cmime%7Cmediatype&cllimit=500&redirects&titles=File%3AROSMARINUS+OFFICINALIS+-+MORROCURT+-+IB-976+%28Roman%C3%AD%29.JPG%7CFile%3ACapra+hircus+aegagrus-cropped.jpg%7CFile%3AEschrichtius+robustus+01-cropped.jpg%7CFile%3AMonachus+monachus+-+Museo+civico+di+storia+naturale+%28Milan%29-cropped.jpg%7CFile%3AMustela+putorius+01-cropped.jpg%7CFile%3ACondylactis+gigantea+%28giant+Caribbean+sea+anemone%29+closeup.jpg%7CFile%3A20140922-cephalopholis+hemistiktos.jpg%7CFile%3APelecanus+occidentalis+in+flight+at+Bodega+Bay.jpg%7CFile%3AUdotea+flabellum+%28mermaid%27s+fan+alga%29+Bahamas.jpg%7CFile%3ARhipocephalus+phoenix+%28pinecone+alga%29+%28San+Salvador+Island%2C+Bahamas%29.jpg%7CFile%3APadina+boergesenii+%28leafy+rolled-blade+algae+Bahamas.jpg%7CFile%3ABird%27s-nest+Fern+Asplenium+nidus+Leaves+2.jpg%7CFile%3ABird%27s-nest+Fern+Asplenium+nidus+Leaves+1.jpg%7CFile%3ABlack+Caterpillar+Fern+Scyphularia+pentaphylla+Leaves.JPG%7CFile%3ABlunt-lobed+Woodsia+obtusa+Winter+Foliage.JPG%7CFile%3AChinese+Elm+Ulmus+parvifolia+%2832-0052-A%29+Bark+1.JPG%7CFile%3AChinese+Elm+Ulmus+parvifolia+%2832-0052-A%29+Bark+2.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282012-055-A%29+Trunk+Bark.JPG%7CFile%3AJapanese+Alder+Alnus+japonica+%2881-305-A%29+Base+Bark.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282010-012-A%29+Bark+Closeup+1.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282010-012-A%29+Bark+Closeup+2.JPG%7CFile%3AJapanese+Zelkova+serrata+%2881-502-L%29+Upper+Trunk+Bark.JPG%7CFile%3AMiyabe+Maple+Acer+miyabei+%2851-292-A%29+Trunk+Bark+Closeup.JPG%7CFile%3AJapanese+Zelkova+serrata+%2881-502-L%29+Lower+Trunk+Bark.JPG%7CFile%3AMiyabe+Maple+Acer+miyabei+%2851-292-A%29+Trunk+Bark.JPG%7CFile%3ARed+Maple+Acer+rubrum+%2832-0877-A%29+Trunk+Bark.JPG%7CFile%3AWhite+Ash+Fraxinus+americana+%2854-0751-A%29+Trunk+Bark.JPG%7CFile%3ANerita+versicolor+%28four-toothed+nerite+snails%29+Bahamas.jpg%7CFile%3ACenchritis+muricatus+%28beaded+periwinkle+snails%29+Bahamas.jpg%7CFile%3AMonodelphis+domestica+skeleton+-+ZooKeys+465-10.png%7CFile%3AMonodelphis+brevicaudata+skull+-+ZooKeys+465-08.png%7CFile%3APeradectes+molars.png%7CFile%3APediomys+molars.png%7CFile%3AScreen+on+the+supermarket+shelf+%282654814813%29.jpg%7CFile%3ASacpha+Hodgson.jpg%7CFile%3AA+Sulphur+Crested+White+Cockatoo+%28Cacatua+galerita%29%2C+Cronulla%2C+NSW+Australia.jpg%7CFile%3AGOCZA%C5%81KOWICE+ZDR%C3%93J%2C+AB.+071.JPG%7CFile%3AVexillum+ebenus+01.JPG%7CFile%3ASCANDIX+PECTEN-VENERIS+-+AGUDA+-+IB-049+%28Agulles+de+pastor%29.JPG%7CFile%3ASCANDIX+PECTEN-VENERIS+-+AGUDA+-+IB-069+%28Agulles+de+pastor%29.JPG%7CFile%3ACRATAEGUS+MONOGYNA+-+AGUDA+-+IB-082+%28Ar%C3%A7%29.JPG%7CFile%3ACRATAEGUS+MONOGYNA+-+AGUDA+-+IB-089+%28Ar%C3%A7%29.JPG%7CFile%3ATagetes+erecta+23122014+%284%29.jpg%7CFile%3ACalendula+officinalis+27122014+%286%29.jpg%7CFile%3AFlowers+of+Judas+tree.jpg%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9259.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9262.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9263.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9264.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9265.JPG&continue=
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo|categories&iiprop=url|mime|mediatype&cllimit=500&redirects&titles=File:Whales are Paraphyletic.png
*/
?>