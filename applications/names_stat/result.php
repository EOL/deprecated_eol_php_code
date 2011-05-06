<?php
require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

require_library("PageRichnessCalculator");
require_library('NameStat');
$func = new NameStat();

if(!isset($_REQUEST['return'])) exit;
$report         = 'list';
$returns        = $_REQUEST['return'];
$sort_order     = $_REQUEST['sort'];
//$vetted         = $_REQUEST['vetted'];
$list           = $_REQUEST['list'];
$separator      = $_REQUEST['separator'];
$choice         = $_REQUEST['choice'];
$withCSV        = $_REQUEST['withCSV'];

if(isset($_REQUEST['strict'])) $strict = true;
else $strict = false;

$search_api = "http://www.eol.org/api/search/";
/*
API call examples:
http://www.eol.org/api/pages/206692?images=75&text=75&subjects=all&vetted=1
http://www.eol.org/api/search/gadus morhua
*/

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
$list = $separator . $list;    //weird behavior - first char must be the separator
$names = explode("$separator", $list);
$names = array_unique($names);
$names = array_filter($names);

print "<font size='2' face='courier'>Total no. of names submitted: " . " " . count($names) . "</font>";
if(count($names) == 0) exit;
print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Unicode MS;'>";

$taxa_table = array();
foreach($names as $sciname)
{
    $file = $search_api . urlencode($sciname);
    $xml = Functions::get_hashed_response($file);
    print "<pre>";
    $taxon = $func->get_details($xml, $sciname, $strict);
	$taxon = $func->sort_details($taxon, $returns);
    $taxa_table = array_merge($taxon, $taxa_table);
    print"</pre>";
}
$taxa_table = $func->sort_by_key($taxa_table, "orig_sciname", $sort_order);
$func->show_table($taxa_table);
?>