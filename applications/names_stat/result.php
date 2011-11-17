<?php
namespace php_active_record;
/*
API call examples:
http://140.247.232.200/api/pages/206692?images=75&text=75&subjects=all&vetted=1
http://140.247.232.200/api/search/gadus morhua
*/
require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];
require_library("PageRichnessCalculator");
require_library('NameStat');
$func = new NameStat();
if(!isset($_REQUEST['return'])) exit;
$report         = 'list';
$returns        = $_REQUEST['return'];
$sort_order     = $_REQUEST['sort'];
$list           = $_REQUEST['list'];
$separator      = $_REQUEST['separator'];
$choice         = $_REQUEST['choice'];
$withCSV        = $_REQUEST['withCSV'];
if(isset($_REQUEST['strict'])) 
{
    $strict = $_REQUEST['strict'];
    if (!in_array($strict, array('canonical_match', 'exact_string', 'default'))) $strict = 'canonical_match';    
}
$search_api = "http://140.247.232.200/api/search/";
if(trim($choice) == "")
{
    print "<i>Please paste your list of names inside the box. <br>Select a filter and separator then click 'Submit'.</i>";
    exit;
}
if($separator == '')
{
    switch (true)
    {
        case $choice == 1:  $separator = chr(13); break;
        case $choice == 2:  $separator = chr(10); break;
        case $choice == 3:  $separator = chr(9); break;
        case $choice == 4:  $separator = ','; break;
        default:break;
    }    
}    
$list = $separator . $list; //weird behavior - first char must be the separator
$names = explode("$separator", $list);
$names = array_unique($names);
$names = array_filter($names);
$names = array_values($names);
$names = limit_array($names, 100); // will only process 100 names max
krsort($names); // inverse entry so it outputs the same way it entered
print "<font size='2' face='courier'>
Reminder: The 'Last curated' column is disabled.<br>
Total no. of names submitted: " . count($names) . "</font>";
if(count($names) == 0) exit;
$taxa_table = array();
foreach($names as $sciname)
{
    $sciname = str_ireplace(".", "", $sciname);
    $file = $search_api . urlencode($sciname);
    $file = str_replace("+", "%20", $file);
    $xml = Functions::get_hashed_response($file);
    print "<pre>";
    $taxon = $func->get_details($xml, $sciname, $strict);
    $taxon = $func->sort_details($taxon, $returns);
    $taxa_table = array_merge($taxon, $taxa_table);
    print"</pre>";
}
if($sort_order != "normal") $taxa_table = $func->sort_by_key($taxa_table, "orig_sciname", $sort_order);
$func->show_table($taxa_table);

function limit_array($arr, $limit)
{
    $new = array();
    for ($i = 0; $i < $limit; $i++) if(@$arr[$i]) $new[] = $arr[$i];
    return $new;
}
?>