<?php
namespace php_active_record;
/* connector for Efloras
estimated execution time: 3 minutes
This script will modify the original Efloras resource (17_orig.xml).
    - change subject GeneralDescript to Morphology
    - remove all references
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 17;

$original_file = "http://dl.dropbox.com/u/7597512/resources/17_orig.xml.gz";
$xml = load_file($original_file);
$xml = preg_replace("/<reference (.*?)>/ims", "<reference>", $xml);
$xml = preg_replace("/<reference>(.*?)<\/reference>/ims", "", $xml);

/* 
works, but takes a long time than just use str_ireplace()
$xml = change_subject($xml); 
*/

$xml = str_ireplace("<subject>http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription</subject>", "<subject>http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology</subject>", $xml);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w");
fwrite($OUT, $xml);
fclose($OUT);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");

function load_file($filename)
{
    $temp_file_path = create_temp_dir() . "/";
    print "\n " . $temp_file_path;
    if($file_contents = Functions::get_remote_file($filename, DOWNLOAD_WAIT_TIME, 999999))
    {
        $uncompressed_file = $temp_file_path . "/17_orig.xml";
        $compressed_file = $temp_file_path . "/17_orig.xml.gz";
        $TMP = fopen($compressed_file, "w");
        fwrite($TMP, $file_contents);
        fclose($TMP);
        $output = shell_exec("gzip -d $compressed_file $temp_file_path");
        $xml = Functions::get_remote_file($uncompressed_file);
    }
    else exit("\n\n Connector terminated. Remote files are not ready.\n\n");
    // remove tmp dir
    if($temp_file_path) shell_exec("rm -fr $temp_file_path");
    return $xml;
}

function change_subject($xml_string)
{
    $xml = simplexml_load_string($xml_string);
    $i = 0;
    foreach($xml->taxon as $taxon)
    {
        $i++; print "$i ";
        foreach($taxon->dataObject as $dataObject)
        {
            $dataObject_dc = $dataObject->children("http://purl.org/dc/elements/1.1/");
            $eol_subjects[] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
            if(@$dataObject->subject)
            {
                if (in_array($dataObject->subject, $eol_subjects)) $dataObject->subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology";
            }
        }
    }
    return $xml->asXML();
}

?>