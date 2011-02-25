<?php
require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

require_library('NameStat');
$func = new NameStat();

if(!isset($_REQUEST['return']))exit;

$report         = 'list';    //original functionality
$returns        = $_REQUEST['return'];
$sort_order     = $_REQUEST['sort'];
//$vetted         = $_REQUEST['vetted'];
$sciname_4color = "";

$list           = $_REQUEST['list'];
$separator      = $_REQUEST['separator'];
$choice         = $_REQUEST['choice'];
$withCSV        = $_REQUEST['withCSV'];

if(trim($choice) == "")
{
    print"<i>Please paste your list of names inside the box. <br>Select a filter and separator then click 'Submit'.</i>";
    exit;
}

$rd     = "";    //row data
$cr     = "\n";
$tab    = chr(9);

if($separator == '')
{
    switch (true)
    {
    case $choice == 1:  $separator = chr(13);break;
    case $choice == 2:  $separator = chr(10);break;    
    case $choice == 3:  $separator = chr(9);break;    
    case $choice == 4:  $separator = ',';break;    
    default:break;
    }    
}    
$list = $separator . $list;    //weird behavior - first char must be the separator
$arr = explode("$separator", $list);    
$orig_lenth_of_arr = count($arr);    
$arr = array_unique($arr);
$arr = $func->array_trim($arr,$orig_lenth_of_arr);    // $orig_lenth_of_arr --- this is the length of array after explode function

print "<font size='2' face='courier'>Total no. of names submitted: " . " " . count($arr) . "</font>";
if(count($arr) == 0){exit;}
print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Unicode MS;'>";
$us = "&#153;";    //unique separator
$value_list="";

$api_put_species="http://www.eol.org/api/search/";
$api_put_taxid_1="http://www.eol.org/api/pages/";
$api_put_taxid_2="?images=75&text=75&subjects=all";
//$api_put_taxid_2 .= "&vetted=$vetted";    

/*
API call examples:
http://www.eol.org/api/pages/206692?images=75&text=75&subjects=all&vetted=1
http://www.eol.org/api/search/gadus morhua
*/

$arr_table=array();
$arr = $func->clean_array($arr);

foreach($arr as $sciname)
{
    $file = $api_put_species . urlencode($sciname);
    $xml = Functions::get_hashed_response($file);
    print"<pre>";
    $arr_details = $func->get_details($xml,$sciname);  
    $arr_details = $func->sort_details($arr_details,$returns);
    $arr_table = array_merge($arr_details,$arr_table);    
    print"</pre>";
}
$arr_table = $func->sort_by_key($arr_table,"orig_sciname",$sort_order);
$func->show_table($arr_table);
?>