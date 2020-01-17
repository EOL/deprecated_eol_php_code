<?php
namespace php_active_record;
/* from: https://eol-jira.bibalex.org/browse/TRAM-703?focusedCommentId=63349&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63349
Mushroom Observer (16)

16	Tuesday 2019-12-31 12:17:55 AM	{"agent.tab":1268,"media_resource.tab":36564,"reference.tab":3453,"taxon.tab":5018,"time_elapsed":{"sec":20.93,"min":0.35,"hr":0.01}}

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 16;

/* Step 1: This is now done. Run once only, everytime you want a new refresh from http://mushroomobserver.org/eol.xml
$resource_path = 'http://mushroomobserver.org/eol.xml'; //from https://opendata.eol.org/dataset/mushroom-observer/resource/e320b637-3ffd-42e8-b08b-788b7c5156f6
$download_options = array('resource_id' => $resource_id, 'cache' => 1, 'expire_seconds' => 60*60*24*30, 'download_wait_time' => 1000000, 'timeout' => 86400, 'download_attempts' => 1, 'delay_in_minutes' => 1);
// $download_options['expire_seconds'] = 0;
if($local_path = Functions::save_remote_file_to_local($resource_path, $download_options))
{
    echo "\nSaved in [$local_path]\n";
    $xml = file_get_contents($local_path);
    $str="<?xml"; //good debug
    if($pos = strpos($xml, $str)) {
        $xml = trim(substr($xml, $pos, strlen($xml)));
        // exit(substr($xml,0,100));
        // exit($xml);
        $xml = delete_this_point_and_beyond("</response>", $xml) . "</response>";
        // exit($xml);
        $local_xml_file = CONTENT_RESOURCE_LOCAL_PATH . "mushroomobserver_eol.xml";
        $f = Functions::file_open($local_xml_file, "w");
        fwrite($f, $xml); fclose($f);
        unlink($local_path);
        exit("\nManual edit using TextMate next.\n"); //XML is a bit messed up atm. Needs manual editing. Open XML using browser to see what sections to be removed.
    }
    unlink($local_path);
}
else exit("\nFile not found. Will terminate.\n");
exit("\nCheck XML before proceeding.\n");
*/

/* Step 2: */
//start creating the archive file using the generated EOL XML file above
$url = 'https://editors.eol.org/other_files/MushroomObserver/mushroomobserver_eol.xml.zip';
require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = $url;
$params["filename"]     = "mushroomobserver_eol.xml";
$params["dataset"]      = "";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, false, 60*60*24); // 2nd param true => means it is an XML file, not an archive file nor a zip file. 3rd param is expire_seconds
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
// unlink($params["filename"]); //comment if you want to check mushroomobserver_eol.xml

function delete_this_point_and_beyond($str, $html)
{
    // $html = "123456789"; $str="56"; //good debug
    if($pos = strpos($html, $str)) {
        $html = substr($html,0,$pos);
        // exit("\n$pos\n[$html]\n");
    }
    return $html;
}
?>