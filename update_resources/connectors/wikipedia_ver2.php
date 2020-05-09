<?php
namespace php_active_record;
/* Wikipedia in different languages */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiHTMLAPI');
require_library('connectors/WikipediaAPI');
require_library('connectors/WikiDataAPI');
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false; //orig false in production

/*
$func = new WikiDataAPI('1', 'inh', 'wikipedia', array()); //generic call
$html = file_get_contents($GLOBALS['MAIN_TMP_PATH']."wiki_test.html"); // echo $html;
$needle = 'style="margin-left:1em; background:#f9f9f9; border: 1px #aaa solid; border-collapse: collapse; font-size: 95%;"';
if($tmp = $func->get_pre_tag_entry($html, $needle)) {
    echo "\n[$tmp]\n";
    $left = $tmp . $needle;
    echo "\n[$left]\n";
    $html = $func->get_real_coverage($left, $html);
    echo "\n($html)\n";
}
exit("\n--end--\n");
*/

/*
$a['hierarchy without kingdom']['c'] = 1;
$a['hierarchy without kingdom']['a'] = 1;
$a['hierarchy without kingdom']['d'] = 1;
$a['hierarchy without kingdom']['b'] = 1;
$tmp = array_keys($a['hierarchy without kingdom']);
sort($tmp);
print_r($tmp);
exit;
*/

/*
$html = '
<table style="background:#E8FFE0;font-size:smaller;padding:1ex;" class="plainlinks123 ambox ambox-style">
<table style="background:#E8FFE0;font-size:smaller;padding:1ex;" class="plainlinks ambox ambox-style">
<table style="background:#E8FFE0;font-size:smaller;padding:1ex;" class="plainlinks abc ambox ambox-style">';
$right = 'class="plainlinks ambox ambox-style"';
$str = get_pre_tag_entry($html, $right);
exit("\n-end-\n");
*/

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
php update_resources/connectors/wikipedia.php _ de generate_resource_force _ _ _ 'wolf'
to generate stats: 'en' here can be any language...
php update_resources/connectors/wikipedia.php _ en taxon_wiki_per_language_stats
*/

/* during development only *** ================================================================================ 
https://meta.wikimedia.org/wiki/List_of_Wikipedias
https://editors.eol.org/eol_php_code/applications/content_server/resources/reports/taxon_wiki_per_language_count_2020_02.txt

php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Panthera leo'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'wolf'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Formicidae'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Gadus morhua'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'fish Pisces'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'starfish Asteroidea'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Orca'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Shark Selachimorpha'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Pacific halibut'
php update_resources/connectors/wikipedia.php _ 'en' generate_resource_force _ _ _ 'Pale fox'

=============================================================================================================== */
$params['jenkins_or_cron']  = @$argv[1];
$params['language']         = @$argv[2];
$params['task']             = @$argv[3];
$params['range_from']       = @$argv[4];
$params['range_to']         = @$argv[5];
$params['actual']           = @$argv[6];
$debug_taxon                = @$argv[7];
print_r($params);
/*
Array(
    [jenkins_or_cron] => jenkins
    [language] => nl
    [task] => generate_resource
    [range_from] => 833332
    [range_to] => 1249998
    [actual] => 3of6
)
Array(
    [jenkins_or_cron] => jenkins
    [language] => nl
    [task] => generate_resource
    [range_from] => 
    [range_to] => 
    [actual] => 
)
*/

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

// /* new section for wikipedia_ver2 ****************************
$actual = @$params['actual'];
if($actual) $resource_id .= "_".$actual;
else { //meaning ready to finalize DwCA. Series 1of6, 2of6 - 6of6 are now done.
    aggregate_6partial_wikipedias($timestart, $resource_id);
}
// ************************************************************** */

$langs_with_multiple_connectors = array("en", "es", "fr", "de", "it", "pt", "zh"); //1st batch | single connectors: ko, ja, ru
$langs_with_multiple_connectors = array_merge($langs_with_multiple_connectors, array("nl", "pl", "sv", "vi")); //2nd batch Dutch Polish Swedish Vietnamese
/* No longer have multiple connectors
$langs_with_multiple_connectors = array_merge($langs_with_multiple_connectors, array("no", "fi", "ca", "uk")); //3rd batch Norwegian Finnish Catalan Ukranian
$langs_with_multiple_connectors = array_merge($langs_with_multiple_connectors, array("tr", "ro", "cs")); //4th batch Turkish Romanian Czech
*/

$use_MultipleConnJenkinsAPI = array("min", "war", "ceb", "id"); //first client for MultipleConnJenkinsAPI | , "cy", "az", "ast", "bg" "ceb"
/* No longer have multiple connectors
$use_MultipleConnJenkinsAPI = array_merge($use_MultipleConnJenkinsAPI, array("szl", "af", "ka", "lt"));
*/
$langs_with_multiple_connectors = array_merge($langs_with_multiple_connectors, $use_MultipleConnJenkinsAPI);

$func = new WikiDataAPI($resource_id, $language, 'wikipedia', $langs_with_multiple_connectors, $debug_taxon); //generic call

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
            echo "\nCannot finalize dwca yet. But will generate partial DwCA [$resource_id]\n";
            // /* ------------------------------------------------------ place to start injecting MultipleConnJenkinsAPI
            if(in_array($language, $use_MultipleConnJenkinsAPI)) inject_MultipleConnJenkinsAPI($language);
            // ------------------------------------------------------ */
            
            // /* new section for wikipedia_ver2 ****************************
            Functions::finalize_dwca_resource($resource_id, true, true, $timestart); //2nd param true means big file; 3rd param true means will delete working folder
            // ************************************************************** */
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
function aggregate_6partial_wikipedias($timestart, $resource_id)
{
    require_library('connectors/DwCA_Aggregator');
    $langs = array();
    //wikipedia-nl_1of6... and so on
    //80_1of6 ... and so on
    
    //string generate the partials 1-6:
    for ($i = 1; $i <= 6; $i++) $langs[] = $resource_id."_".$i."of6";
    print_r($langs);

    $resource_id .= '_ELI'; //debug only
    echo "\nProcessing [$resource_id] partials:[".count($langs)."]...\n";
    $func = new DwCA_Aggregator($resource_id, NULL, 'regular'); //'regular' not 'wikipedia' which is used in wikipedia aggregate resource
    $func->combine_DwCAs($langs);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
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
(ps) Pashto https://en.wikipedia.org/wiki/Pashto            --- same as Arabic and Persian
(sd) Sindhi https://en.wikipedia.org/wiki/Sindhi_language   --- same as Arabic and Persian
(ckb) Sorani    https://en.wikipedia.org/wiki/Sorani        --- same as Arabic and Persian
(dv) Divehi     https://en.wikipedia.org/wiki/Maldivian_language    307     --- same as Arabic and Persian
(yi) Yiddish                https://en.wikipedia.org/wiki/Yiddish	219     --- same as Arabic and Persian


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
ta  Tamil       https://en.wikipedia.org/wiki/Tamil_language
ceb Cebuano     https://en.wikipedia.org/wiki/Cebuano_language
el  Greek       https://en.wikipedia.org/wiki/Greek_language
mk  Macedonian  https://en.wikipedia.org/wiki/Macedonian_language
ky  Kirghiz     https://en.wikipedia.org/wiki/Kyrgyz_language
sco	2304    Scots   https://en.wikipedia.org/wiki/Scots_language
zh-yue	2193 --- passed for now. Seems it needs major code revamp
hi	2113    Hindi 	https://en.wikipedia.org/wiki/Hindi
fy	2062    West Frisian    https://en.wikipedia.org/wiki/West_Frisian_language
tl	1803    Tagalog         https://en.wikipedia.org/wiki/Tagalog_language
jv	1745    Javanese        https://en.wikipedia.org/wiki/Javanese_language
ia	1664    Interlingua     https://en.wikipedia.org/wiki/Interlingua
ne	1647    Nepali      https://en.wikipedia.org/wiki/Nepali_language
oc	1621    Occitan     https://en.wikipedia.org/wiki/Occitan_language
qu	1583    Quechua     https://en.wikipedia.org/wiki/Quechuan_languages
be-x-old	1532 (be-x-old:, redirects to be-tarask:)   Belarusian (Taraškievica)   https://en.wikipedia.org/wiki/Tara%C5%A1kievica
koi Komi-Permyak    https://en.wikipedia.org/wiki/Komi-Permyak_language
frr North Frisian   https://en.wikipedia.org/wiki/North_Frisian_language
udm Udmurt          https://en.wikipedia.org/wiki/Udmurt_language
ba  Bashkir     https://en.wikipedia.org/wiki/Bashkir_language
an  Aragonese   https://en.wikipedia.org/wiki/Aragonese_language
zh-min-nan  Min Nan     https://en.wikipedia.org/wiki/Southern_Min
sw          Swahili     https://en.wikipedia.org/wiki/Swahili_language
te          Telugu      https://en.wikipedia.org/wiki/Telugu_language
uz          Uzbek       https://en.wikipedia.org/wiki/Uzbek_language
bs          Bosnian     https://en.wikipedia.org/wiki/Bosnian_language
ku          Kurdish     https://en.wikipedia.org/wiki/Kurdish_languages
io          Ido         https://en.wikipedia.org/wiki/Ido_language
my          Burmese     https://en.wikipedia.org/wiki/Burmese_language
mn          Mongolian   https://en.wikipedia.org/wiki/Mongolian_language
kv          Komi        https://en.wikipedia.org/wiki/Komi_language
(lb)  Luxembourgish   https://en.wikipedia.org/wiki/Luxembourgish
(su)  Sundanese       https://en.wikipedia.org/wiki/Sundanese_language
(kn)  Kannada         https://en.wikipedia.org/wiki/Kannada
(tt)  Tatar           https://en.wikipedia.org/wiki/Tatar_language
sq  Albanian        https://en.wikipedia.org/wiki/Albanian_language
csb Kashubian       https://en.wikipedia.org/wiki/Kashubian_language
mr  Marathi         https://en.wikipedia.org/wiki/Marathi_language

* (co) Corsican        https://en.wikipedia.org/wiki/Corsican_language
* (fo) Faroese         https://en.wikipedia.org/wiki/Faroese_language
* (os) Ossetian        https://en.wikipedia.org/wiki/Ossetian_language
* (cv) Chuvash         https://en.wikipedia.org/wiki/Chuvash_language
* (kab) Kabyle      https://en.wikipedia.org/wiki/Kabyle_language
* (sah) Sakha       https://en.wikipedia.org/wiki/Yakut_language 
* (nds) Low Saxon   https://en.wikipedia.org/wiki/Low_German
* (lmo) Lombard       https://en.wikipedia.org/wiki/Lombard_language
* (pa) Punjabi       https://en.wikipedia.org/wiki/Punjabi_language
* (wa) Walloon       https://en.wikipedia.org/wiki/Walloon_language
* (vls) West Flemish    https://en.wikipedia.org/wiki/West_Flemish
* (gv) Manx            https://en.wikipedia.org/wiki/Manx_language
* (wuu) Wu              https://en.wikipedia.org/wiki/Wu_Chinese
* (nah) Nahuatl         https://en.wikipedia.org/wiki/Nahuatl

* (as) Assamese       https://en.wikipedia.org/wiki/Assamese_language
* (dsb) Lower Sorbian https://en.wikipedia.org/wiki/Lower_Sorbian_language
* (li) Limburgish     https://en.wikipedia.org/wiki/Limburgish
* (mi) Maori          https://en.wikipedia.org/wiki/M%C4%81ori_language

* (kbd) Kabardian Circassian  https://en.wikipedia.org/wiki/Kabardian_language
* (mdf) Moksha                https://en.wikipedia.org/wiki/Moksha_language
* (to) Tongan                 https://en.wikipedia.org/wiki/Tongan_language
* (bat-smg) Samogitian        https://en.wikipedia.org/wiki/Samogitian_dialect

* (olo) Livvi-Karelian    https://en.wikipedia.org/wiki/Livvi-Karelian_language
* (mhr) Meadow Mari       https://en.wikipedia.org/wiki/Meadow_Mari_language
* (tg) Tajik              https://en.wikipedia.org/wiki/Tajik_language
* (pcd) Picard            https://en.wikipedia.org/wiki/Picard_language

* (vep) Vepsian           https://en.wikipedia.org/wiki/Veps_language
* (se) Northern Sami      https://en.wikipedia.org/wiki/Northern_Sami_language
* (am) Amharic            https://en.wikipedia.org/wiki/Amharic
* (si) Sinhalese          https://en.wikipedia.org/wiki/Sinhala_language

* (ht) Haitian            https://en.wikipedia.org/wiki/Haitian_language
* (gn) Guarani            https://en.wikipedia.org/wiki/Guarani_language
* (rue) Rusyn             https://en.wikipedia.org/wiki/Rusyn_language
* (mt) Maltese            https://en.wikipedia.org/wiki/Maltese_language
* (gu) Gujarati           https://en.wikipedia.org/wiki/Gujarati_language

https://editors.eol.org/eol_php_code/applications/content_server/resources/reports/taxon_wiki_per_language_count_2020_02.txt

* (als) Alemannic         https://en.wikipedia.org/wiki/Alemannic_German
* (or) Oriya              https://en.wikipedia.org/wiki/Odia_language         
* (bh) Bhojpuri           https://en.wikipedia.org/wiki/Bhojpuri_language     
* (myv) Erzya             https://en.wikipedia.org/wiki/Erzya_language        
* (scn) Sicilian          https://en.wikipedia.org/wiki/Sicilian_language     
* (gd) Scottish Gaelic    https://en.wikipedia.org/wiki/Scottish_Gaelic       
* (pam) Kapampangan       https://en.wikipedia.org/wiki/Kapampangan_language  
* (xmf) Mingrelian        https://en.wikipedia.org/wiki/Mingrelian_language   
* (cdo) Min Dong          https://en.wikipedia.org/wiki/Eastern_Min           
* (bar) Bavarian          https://en.wikipedia.org/wiki/Bavarian_language     
* (nap) Neapolitan        https://en.wikipedia.org/wiki/Neapolitan_language   

* (lfn) Lingua Franca Nova    https://en.wikipedia.org/wiki/Lingua_Franca_Nova
(eo) Esperanto   https://en.wikipedia.org/wiki/Esperanto
* (vo) Volapük     https://en.wikipedia.org/wiki/Volap%C3%BCk
* (nds-nl) Dutch Low Saxon    https://en.wikipedia.org/wiki/Dutch_Low_Saxon
* (bo) Tibetan                https://en.wikipedia.org/wiki/Tibetan_language
* (stq) Saterland Frisian     https://en.wikipedia.org/wiki/Saterland_Frisian
* (inh) Ingush                https://en.wikipedia.org/wiki/Ingush_language

* (ha) Hausa                  https://en.wikipedia.org/wiki/Hausa_language
* (lbe) Lak                   https://en.wikipedia.org/wiki/Lak_language
* (lij) Ligurian              https://en.wikipedia.org/wiki/Ligurian_(Romance_language)
* (lez) Lezgian               https://en.wikipedia.org/wiki/Lezgian_language	220
* (sa) Sanskrit               https://en.wikipedia.org/wiki/Sanskrit	219
* (ace) Acehnese              https://en.wikipedia.org/wiki/Acehnese_language	217
* (diq) Zazaki                https://en.wikipedia.org/wiki/Zaza_language
* (ce) Chechen                https://en.wikipedia.org/wiki/Chechen_language

* (yo) Yoruba                 https://en.wikipedia.org/wiki/Yoruba_language
* (rw) Kinyarwanda            https://en.wikipedia.org/wiki/Kinyarwanda
* (vec) Venetian              https://en.wikipedia.org/wiki/Venetian_language
* (sc) Sardinian              https://en.wikipedia.org/wiki/Sardinian_language
* (ln) Lingala                https://en.wikipedia.org/wiki/Lingala

(hak) Hakka                 https://en.wikipedia.org/wiki/Hakka_Chinese

kw	188
bcl	186
za	175
ang	167
eml	165
av	159
chy	150
fj	150
ik	150
ug	144
zea	144
bxr	139
zh-classical	139
bjn	137
so	137
arz	135
mwl	130
sn	130
chr	128
mai	117
tk	117
tcy	115
szy	113
mzn	111
wo	110
ab	108
ban	108
ay	107
tyv	107
atj	104
new	103
fiu-vro	100
mg	93
rm	93
ltg	85
ext	84
kl	82
roa-rup	80
nrm	79
rn	79
dty	77
hyw	72
lo	72
kg	70
km	68
gom	65
frp	62
sat	62
gan	60
haw	60
hif	59
nso	59
xal	58
mnw	57
zu	57
bi	54
lad	51
map-bms	50
roa-tara	50
pdc	49
kbp	48
jbo	44
kaa	42
srn	41
vo	41
gag	40
ty	40
fur	38
ie	38
lg	38
ts	38
bpy	36
iu	36
arc	35
gor	35
nov	35
crh	32
tum	30
glk	28
krc	28
ksh	28
na	28
ny	26
pfl	26
xh	25
tpi	24
cr	23
gcr	23
jam	21
ak	19
bm	19
cu	19
ks	18
pap	18
got	17
ee	16
ady	15
pih	15
ki	14
shn	13
pi	12
sm	11
ti	11
ve	11
ch	10
ig	10
lrc	10
om	9
st	9
din	8
ss	8
tet	8
sg	5
ff	4
pnt	4
tn	4
cbk-zam	3
rmy	3
bug	2
data	2
dz	2
nqo	2
mh	1
tw	1



---------------------------------------------------------------------------------
*/
/*
this is a request made by wikimedia harvester (71): this 2 are same, first is a subset of the 2nd. And one is urlencoded() other is not.
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo|categories&iiprop=url|mime|mediatype&cllimit=500&redirects&titles=File%3AROSMARINUS+OFFICINALIS+-+MORROCURT+-+IB-976+(Roman%C3%AD).JPG
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo%7Ccategories&iiprop=url%7Cmime%7Cmediatype&cllimit=500&redirects&titles=File%3AROSMARINUS+OFFICINALIS+-+MORROCURT+-+IB-976+%28Roman%C3%AD%29.JPG%7CFile%3ACapra+hircus+aegagrus-cropped.jpg%7CFile%3AEschrichtius+robustus+01-cropped.jpg%7CFile%3AMonachus+monachus+-+Museo+civico+di+storia+naturale+%28Milan%29-cropped.jpg%7CFile%3AMustela+putorius+01-cropped.jpg%7CFile%3ACondylactis+gigantea+%28giant+Caribbean+sea+anemone%29+closeup.jpg%7CFile%3A20140922-cephalopholis+hemistiktos.jpg%7CFile%3APelecanus+occidentalis+in+flight+at+Bodega+Bay.jpg%7CFile%3AUdotea+flabellum+%28mermaid%27s+fan+alga%29+Bahamas.jpg%7CFile%3ARhipocephalus+phoenix+%28pinecone+alga%29+%28San+Salvador+Island%2C+Bahamas%29.jpg%7CFile%3APadina+boergesenii+%28leafy+rolled-blade+algae+Bahamas.jpg%7CFile%3ABird%27s-nest+Fern+Asplenium+nidus+Leaves+2.jpg%7CFile%3ABird%27s-nest+Fern+Asplenium+nidus+Leaves+1.jpg%7CFile%3ABlack+Caterpillar+Fern+Scyphularia+pentaphylla+Leaves.JPG%7CFile%3ABlunt-lobed+Woodsia+obtusa+Winter+Foliage.JPG%7CFile%3AChinese+Elm+Ulmus+parvifolia+%2832-0052-A%29+Bark+1.JPG%7CFile%3AChinese+Elm+Ulmus+parvifolia+%2832-0052-A%29+Bark+2.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282012-055-A%29+Trunk+Bark.JPG%7CFile%3AJapanese+Alder+Alnus+japonica+%2881-305-A%29+Base+Bark.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282010-012-A%29+Bark+Closeup+1.JPG%7CFile%3AJapanese+Maple+Acer+palmatum+%282010-012-A%29+Bark+Closeup+2.JPG%7CFile%3AJapanese+Zelkova+serrata+%2881-502-L%29+Upper+Trunk+Bark.JPG%7CFile%3AMiyabe+Maple+Acer+miyabei+%2851-292-A%29+Trunk+Bark+Closeup.JPG%7CFile%3AJapanese+Zelkova+serrata+%2881-502-L%29+Lower+Trunk+Bark.JPG%7CFile%3AMiyabe+Maple+Acer+miyabei+%2851-292-A%29+Trunk+Bark.JPG%7CFile%3ARed+Maple+Acer+rubrum+%2832-0877-A%29+Trunk+Bark.JPG%7CFile%3AWhite+Ash+Fraxinus+americana+%2854-0751-A%29+Trunk+Bark.JPG%7CFile%3ANerita+versicolor+%28four-toothed+nerite+snails%29+Bahamas.jpg%7CFile%3ACenchritis+muricatus+%28beaded+periwinkle+snails%29+Bahamas.jpg%7CFile%3AMonodelphis+domestica+skeleton+-+ZooKeys+465-10.png%7CFile%3AMonodelphis+brevicaudata+skull+-+ZooKeys+465-08.png%7CFile%3APeradectes+molars.png%7CFile%3APediomys+molars.png%7CFile%3AScreen+on+the+supermarket+shelf+%282654814813%29.jpg%7CFile%3ASacpha+Hodgson.jpg%7CFile%3AA+Sulphur+Crested+White+Cockatoo+%28Cacatua+galerita%29%2C+Cronulla%2C+NSW+Australia.jpg%7CFile%3AGOCZA%C5%81KOWICE+ZDR%C3%93J%2C+AB.+071.JPG%7CFile%3AVexillum+ebenus+01.JPG%7CFile%3ASCANDIX+PECTEN-VENERIS+-+AGUDA+-+IB-049+%28Agulles+de+pastor%29.JPG%7CFile%3ASCANDIX+PECTEN-VENERIS+-+AGUDA+-+IB-069+%28Agulles+de+pastor%29.JPG%7CFile%3ACRATAEGUS+MONOGYNA+-+AGUDA+-+IB-082+%28Ar%C3%A7%29.JPG%7CFile%3ACRATAEGUS+MONOGYNA+-+AGUDA+-+IB-089+%28Ar%C3%A7%29.JPG%7CFile%3ATagetes+erecta+23122014+%284%29.jpg%7CFile%3ACalendula+officinalis+27122014+%286%29.jpg%7CFile%3AFlowers+of+Judas+tree.jpg%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9259.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9262.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9263.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9264.JPG%7CFile%3ASyntrichia+montana+%28a%2C+164114-475524%29+9265.JPG&continue=
http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo|categories&iiprop=url|mime|mediatype&cllimit=500&redirects&titles=File:Whales are Paraphyletic.png
*/
?>