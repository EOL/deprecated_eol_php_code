<?php
namespace php_active_record;
/* Wikipedia in different languages */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiHTMLAPI');
require_library('connectors/WikipediaAPI');
require_library('connectors/WikiDataAPI');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig false in production

/*
$resource_id = 'wikipedia-ce';
inject_jenkins_run($resource_id);
*/
/*
$ret = get_next_lang_after('en');
print_r($ret);
$next_lang = $ret[0];
$six_conn = $ret[1];
exit("\nNext lang is: [$next_lang][$six_conn]\n");
*/
/*
$all_6c = get_all_6_connectors();
print_r($all_6c);
*/

/*
// PHP program to illustrate date_sub() function

// Subtract 5 years from the 25th of June, 2018
$date = date_create('2018-06-25');
date_sub($date, date_interval_create_from_date_string('5 years'));  echo date_format($date, 'Y-m-d') . "\n";

// Subtract 5 month from the 25th of June, 2018
$date = date_create('2018-06-25');
date_sub($date, date_interval_create_from_date_string('5 month'));  echo date_format($date, 'Y-m-d'). "\n";

// // Subtract 5 days from the 25th of June, 2018
$date = date_create('2018-06-25');
date_sub($date, date_interval_create_from_date_string('5 days'));   echo date_format($date, 'Y-m-d');

$date = date("Y-m-d");
$today = date_create($date);
echo "\ntoday: ".date_format($today, 'Y-m-d')."\n";
date_sub($today, date_interval_create_from_date_string('2 months'));
echo "yesterday: " . date_format($today, 'Y-m-d'). "\n";
*/
// Input : echo "Last modified: ".date("F d Y H:i:s.", filemtime("gfg.txt"));
// Output : Last modified: May 1 2018 07:26:14.

/*
$lang = 'es';
$lang = 'ce';
if(is_this_wikipedia_lang_old_YN($lang)) {
    echo "\nYes, this is an old file.\n";
}
else echo "\nNo, this is already a new file\n";
*/

// exit(0); //SUCCESS in Jenkins
// exit(1); //SUCCESS in Jenkins
// exit();  //succes in Jenkins
// shell_exec("exit 1"); //still success in Jenkins

/*
$lang = 'or';
$info = get_language_info_from_TSV($lang);
print_r($info);
$lang = $info[0]; $status = $info[1]; $six_conn = $info[2];
if($status == 'Y' && $six_conn != '6c') echo "\nproceed with harvest\n";
else echo "\ncannot proceed, go to next lang\n";
exit("\n-end-\n");
*/

// echo "\n".date("Y-m-d", false);
// exit("\n-end-\n");

function get_language_info_from_TSV($needle)
{
    $tsv = DOC_ROOT. "update_resources/connectors/all_wikipedias_main.tsv";
    $txt = file_get_contents($tsv);
    $rows = explode("\n", $txt);
    $final = array();
    foreach($rows as $row) {
        $arr = explode("\t", $row);
        $arr = array_map('trim', $arr);
        // print_r($arr);
        $lang = $arr[0]; $status = $arr[1]; $six_conn = $arr[2];
        if($needle == $lang) return $arr;
    }
    return false;
}
function is_this_wikipedia_lang_old_YN($lang)
{
    $lang_date = get_date_of_this_wikipedia_lang($lang);
    echo "\ndate of $lang: $lang_date\n";
    // get date today minus 2 months
    $date = date("Y-m-d");
    $today = date_create($date);
    echo "\ntoday: ".date_format($today, 'Y-m-d')."\n";
    date_sub($today, date_interval_create_from_date_string('2 months'));
    $minus_2_months = date_format($today, 'Y-m-d');
    // compare
    echo "minus 2 months: " .$minus_2_months. "\n";
    echo "\n$lang_date < $minus_2_months \n";
    if($lang_date < $minus_2_months) return true;
    else return false;
}
function get_date_of_this_wikipedia_lang($lang)
{
    $file = CONTENT_RESOURCE_LOCAL_PATH.'wikipedia-'.$lang.'.tar.gz';
    return date("Y-m-d", filemtime($file));
}



function get_all_6_connectors()
{   $final = array();
    $tsv = DOC_ROOT. "update_resources/connectors/all_wikipedias_main.tsv";
    $txt = file_get_contents($tsv);
    $rows = explode("\n", $txt);
    /* step1: get all valid langs to process */
    $final = array();
    foreach($rows as $row) {
        $arr = explode("\t", $row);
        $arr = array_map('trim', $arr);
        // print_r($arr);
        $lang = $arr[0]; $status = $arr[1]; $six_conn = $arr[2];
        if($six_conn == '6c') $final[] = $lang;
    }
    return $final;
}

function get_next_lang_after($needle)
{   // echo "\n". DOC_ROOT;
    // /opt/homebrew/var/www/eol_php_code/
    $tsv = DOC_ROOT. "update_resources/connectors/all_wikipedias_main.tsv";
    $txt = file_get_contents($tsv);
    $rows = explode("\n", $txt);
    /* step1: get all valid langs to process */
    $final = array();
    foreach($rows as $row) {
        $arr = explode("\t", $row);
        $arr = array_map('trim', $arr); // print_r($arr);
        $lang = $arr[0]; $status = $arr[1];
        // if($lang == "-----") continue;
        // if($status == "N") continue;
        $final[] = $arr;
    } // print_r($final);
    /* step2: loop and search for needle in $final, get $i */
    $i = -1;
    foreach($final as $arr) { $i++; // print_r($rek); exit;
        /*Array(
            [0] => pl
            [1] => Y
        )*/
        $lang = $arr[0]; $status = $arr[1];
        if($needle == $lang) break;
    }
    /* step3: start with $i, then get the next valid lang */
    $start = $i+1; // echo "\nstart at: [$start]\n";
    $i = -1;
    foreach($final as $arr) { $i++; // print_r($rek); exit;
        /*Array(
            [0] => pl
            [1] => Y
            [2] => 6c
        )*/
        if($i >= $start) {
            $lang = $arr[0]; $status = $arr[1]; $six_conn = $arr[2];
            if($status == "Y" && $six_conn == "6c") return array($lang, $six_conn);
        }
    }
    return false;
}

function inject_jenkins_run($resource_id)
{   /*
    fill_up_undefined_parents.php jenkins '{"resource_id": "wikipedia-is", "source_dwca": "wikipedia-is", "resource": "fillup_missing_parents"}'
    */
    require_library('connectors/MultipleConnJenkinsAPI');
    $funcj = new MultipleConnJenkinsAPI();
    echo "\ntry to fillup_missing_parents...\n";
    $arr_info = array();
    $arr_info['resource_id'] = $resource_id;
    $arr_info['connector'] = 'fill_up_undefined_parents';
    $funcj->jenkins_call_single_run($arr_info, "fillup missing parents");
}
function delete_temp_files_and_others($language, $resource_id = false)
{   /*
    -rw-r--r-- 1 root      root       91798932 Apr 18 19:30 wikipedia-pl.tar.gz
    -rw-r--r-- 1 root      root             15 Apr 18 19:29 wikipedia_generation_status_pl_2019_04.txt
    -rw-r--r-- 1 root      root       47597921 Apr 18 18:28 wikipedia_pl_2019-04-18_09_22.tmp
    -rw-r--r-- 1 root      root              0 Apr 18 17:42 wikipedia_pl_2019-04-18_17_17.tmp
    -rw-r--r-- 1 root      root              0 Apr 18 15:26 wikipedia_pl_2019-04-18_15_31.tmp
    -rw-r--r-- 1 root      root              0 Apr 18 15:13 wikipedia_pl_2019-04-18_15_06.tmp
    -rw-r--r-- 1 root      root              0 Apr 18 14:59 wikipedia_pl_2019-04-18_14_11.tmp
    -rw-r--r-- 1 root      root              0 Apr 18 14:56 wikipedia_pl_2019-04-18_14_07.tmp
    
    -rw-r--r-- 1 root root  76296 Jun  6 05:45 wikipedia-ce_1of6.tar.gz
    -rw-r--r-- 1 root root  84669 Jun  6 05:45 wikipedia-ce_4of6.tar.gz
    -rw-r--r-- 1 root root  58418 Jun  6 05:44 wikipedia-ce_6of6.tar.gz
    -rw-r--r-- 1 root root  79654 Jun  6 05:44 wikipedia-ce_5of6.tar.gz
    -rw-r--r-- 1 root root  87294 Jun  6 05:44 wikipedia-ce_2of6.tar.gz
    -rw-r--r-- 1 root root  46594 Jun  6 05:44 wikipedia-ce_3of6.tar.gz
    */
    $paths[] = CONTENT_RESOURCE_LOCAL_PATH . "wikipedia_generation_status_".$language."_*.txt";
    $paths[] = CONTENT_RESOURCE_LOCAL_PATH . "wikipedia_".$language."_*.tmp";
    $paths[] = CONTENT_RESOURCE_LOCAL_PATH . "wikipedia-".$language."_*of6.tar.gz";
    if($resource_id) $paths[] = CONTENT_RESOURCE_LOCAL_PATH . $resource_id."_*of6.tar.gz"; //e.g. 80_1of6.tar.gz 
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
    require_library('connectors/DwCA_Aggregator_Functions');
    require_library('connectors/DwCA_Aggregator');
    $langs = array();
    //wikipedia-nl_1of6... and so on
    //80_1of6 ... and so on
    
    //string generate the partials 1-6:
    for ($i = 1; $i <= 6; $i++) $langs[] = $resource_id."_".$i."of6";
    print_r($langs);

    // $resource_id .= '_ELI'; //debug only
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
(ug) Uyghur                 https://en.wikipedia.org/wiki/Uyghur_language   --- same as Arabic and Persian
(arz) Egyptian Arabic       https://en.wikipedia.org/wiki/Egyptian_Arabic       --- same as Arabic and Persian
(mzn) Mazandarani           https://en.wikipedia.org/wiki/Mazanderani_language  --- same as Arabic and Persian


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

* (hak) Hakka                 https://en.wikipedia.org/wiki/Hakka_Chinese
* (kw) Cornish                https://en.wikipedia.org/wiki/Cornish_language
* (bcl) Central Bicolano      https://en.wikipedia.org/wiki/Central_Bikol
* (za) Zhuang                 https://en.wikipedia.org/wiki/Zhuang_languages
* (ang) Anglo-Saxon           https://en.wikipedia.org/wiki/Anglo-Frisian_languages#English_(Anglo)_languages
* (eml) Emilian-Romagnol      https://en.wikipedia.org/wiki/Emilian-Romagnol_language

* (av) Avar                   https://en.wikipedia.org/wiki/Avar_language
* (fj) Fijian                 https://en.wikipedia.org/wiki/Fijian_language
* (chy) Cheyenne              https://en.wikipedia.org/wiki/Cheyenne_language
* (ik) Inupiak                https://en.wikipedia.org/wiki/Inupiaq_language
* (zea) Zeelandic             https://en.wikipedia.org/wiki/Zeelandic
* (bxr) Buryat                https://en.wikipedia.org/wiki/Buryat_language
* (bjn) Banjar                https://en.wikipedia.org/wiki/Banjar_language (bjn or bvu)
* (so) Somali                 https://en.wikipedia.org/wiki/Somali_language

* (zh-classical) Classical Chinese    https://en.wikipedia.org/wiki/Classical_Chinese *(lzh)
* (mwl) Mirandese                     https://en.wikipedia.org/wiki/Mirandese_language
* (sn) Shona                          https://en.wikipedia.org/wiki/Shona_language
* (mai) Maithili              https://en.wikipedia.org/wiki/Maithili_language
* (chr) Cherokee              https://en.wikipedia.org/wiki/Cherokee_language
* (tk) Turkmen                https://en.wikipedia.org/wiki/Turkmen_language
* (szy) Sakizaya              https://en.wikipedia.org/wiki/Sakizaya_language
* (ab) Abkhazian              https://en.wikipedia.org/wiki/Abkhaz_language
* (tcy) Tulu                  https://en.wikipedia.org/wiki/Tulu_language
* (wo) Wolof                  https://en.wikipedia.org/wiki/Wolof_language
* (ban) Balinese              https://en.wikipedia.org/wiki/Balinese_language
* (ay) Aymara                 https://en.wikipedia.org/wiki/Aymara_language
* (tyv) Tuvan                 https://en.wikipedia.org/wiki/Tuvan_language
* (atj) Atikamekw             https://en.wikipedia.org/wiki/Atikamekw_language
* (new) Newar                 https://en.wikipedia.org/wiki/Newar_language
* (fiu-vro) Võro              https://en.wikipedia.org/wiki/V%C3%B5ro_language *(vro)
* (mg) Malagasy               https://en.wikipedia.org/wiki/Malagasy_language
* (rm) Romansh                https://en.wikipedia.org/wiki/Romansh_language
* (ltg) Latgalian             https://en.wikipedia.org/wiki/Latgalian_language
* (ext) Extremaduran          https://en.wikipedia.org/wiki/Extremaduran_language
* (kl) Greenlandic            https://en.wikipedia.org/wiki/Greenlandic_language
* (roa-rup) Aromanian         https://en.wikipedia.org/wiki/Aromanian_language *(rup)
* (nrm) Norman                https://en.wikipedia.org/wiki/Norman_language
* (rn) Kirundi                https://en.wikipedia.org/wiki/Kirundi
* (dty) Doteli                https://en.wikipedia.org/wiki/Doteli

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