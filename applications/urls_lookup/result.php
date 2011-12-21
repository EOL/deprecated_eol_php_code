<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('UrlLookUp');
$func = new UrlLookUp();
error_reporting(0);
$arr_4saving    = array();
$total_cnt      = 0;
$sub_total      = 0;
$eol_site       = "www.eol.org";
$eol_site       = "app1.eol.org";
$FindIT         = "http://www.ubio.org/webservices/service.php?function=findIT&url=";
$nameBankURL    = "http://www.ubio.org/browser/details.php?namebankID=";
/* good sample URLs
http://www.ubio.org/webservices/service.php?function=findIT&url=http://spire.umbc.edu/ontologies/EthanPlants.owl
http://spire.umbc.edu/ontologies/EthanPlants.owl
http://zipcodezoo.com/Protozoa/L/Lepocinclis_ovata/
*/
$list           = $_REQUEST['list'];
$separator      = $_REQUEST['separator'];
$choice         = $_REQUEST['choice'];
if(isset($_REQUEST['withCSV']))$withCSV = $_REQUEST['withCSV'];
if($choice == "")
{
    print"<i>Please paste your list of URLs inside the box. <br>Select a filter and separator then click 'Submit'.
    <p>The URLs will be sent to UBio-FindIT, and this tool will output a tab-delimited TXT file of all names gathered by FindIT 
    using the URLs submitted.<p>It is recommended to use a Spreadsheet in opening the tab-delimited TXT file.</i>";
    exit;
}
$rd  = ""; //row data
$cr  = "\n";
$sep = ",";
$sep = chr(9); //tab
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
$arr = explode($separator, $list);
$arr = $func->array_trim($arr); 
print "<font size='2' face='courier'>Total no. of URLs submitted: " . " " . count($arr) . "</font><hr>";
$us = "&#153;"; //unique separator
$rd .= "\n";
print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Unicode MS;'>";
for ($i = 0; $i < count($arr); $i++)
{
    $url = $FindIT . urlencode(trim($arr[$i]));
    $y = $i + 1;
    print "<tr><td>$y. <a href='$url'>$arr[$i]</a></td></tr>";
    $cont = "y";
    if($xml = Functions::get_hashed_response($url))
    {
        $names = $func->get_names($xml,$nameBankURL,$arr_4saving);
        $html = $names[0];
        $sub_total = $names[1];
        $arr_4saving = $names[2];
        $total_cnt += $sub_total;
        print "<tr><td>$html</td></tr>";        
    }
    else print "<tr><td>-not well formed-</td></tr>";
    print "<tr><td>names = $sub_total</td></tr>";   
    $sub_total = 0;   
}
print "<tr><td>total = $total_cnt</td></tr>"; 
print "</table>";
$func->save_to_txt($arr_4saving);
?>