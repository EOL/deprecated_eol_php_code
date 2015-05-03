<?php
namespace php_active_record;

/*connector for Encyclopedia of Marine Life of Britain and Ireland
estimated execution time: 28-30 mins for 868 species.

Partner provides a list of URL's for its individual species XML.
The connector loops to this list and compiles it to one final resource for EOL ingestion.
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$start = 0;
$resource_id = 107; //Encyclopedia of Marine Life of Britain and Ireland 
$main_count = 0;

$file_number = 1;
$path = DOC_ROOT . "update_resources/connectors/files/britain_ireland_" . $file_number . ".xml";
if(!$OUT = fopen($path, "w+")) print "\n Cannot create file $path";

$main_id_list = array();
$main_id_list = get_main_id_list();
$total_taxid_count = count($main_id_list);

print "\n total taxid count = " . $total_taxid_count . "\n\n";
$i = 1;
print "\n";
for ($i = $start; $i < $total_taxid_count; $i++)
{
    $taxid = $main_id_list[$i];
    if($contents = process($taxid))
    {
        print " -ok- ";
        fwrite($OUT, $contents);
    }
    else print "\n no write";
    print $i+1 . ". of $total_taxid_count \n";
}
$str = "</response>";
fwrite($OUT, $str);
fclose($OUT);

//start compiling all britain_ireland_?.xml 
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
if(!($OUT = fopen($old_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
  return;
}
$str = "<?xml version='1.0' encoding='utf-8' ?>\n";
$str .= "<response\n";
$str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
$str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
$str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
$str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
$str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
$str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
$str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
$str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
fwrite($OUT, $str);
$i = 0;
while(true)
{
    $i++; print "$i ";
    $file = DOC_ROOT . "/update_resources/connectors/files/britain_ireland_" . $i . ".xml";
    if (file_exists($file))
    {
        if($str = Functions::get_remote_file($file))
        {
            fwrite($OUT, $str);
            unlink($file);
        }
    }
    else break;
}

print "\n --end-- \n";
fclose($OUT);

$elapsed_time_sec = microtime(1) - $timestart;
print "\n";
print "elapsed time = $elapsed_time_sec sec                 \n";
print "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
print "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

//start functions #################################################################################################
function process($id)
{
    $file = $id;
    if(!$xml = Functions::get_hashed_response($file)) 
    {
        print "\n invalid XML";
        return false;
    }
    if($contents = Functions::get_remote_file($file))
    {
        if($contents)
        {
            $pos1 = stripos($contents,"<taxon>");
            $pos2 = stripos($contents,"</taxon>");
            if($pos1 != "" and $pos2 != "")
            {
                $contents = trim(substr($contents, $pos1, $pos2 - $pos1 + 8));
                return $contents;
            }
        }
    }
    return false;
}

function get_main_id_list()
{
    $urls[] = "http://www.habitas.org.uk/marinelife/specieslist_xml.asp";
    print "\n URLs = " . sizeof($urls) . "\n";
    $no_of_urls = sizeof($urls);
    $arr = array();
    foreach($urls as $url)
    {
        $j = 0;
        if($xml = Functions::get_hashed_response($url))
        {
            foreach($xml->id as $source_url)
            {
                print "\n --> " . $source_url;
                @$arr["$source_url"] = true;
                $j++;
            }
            print "\n";
        }
        print "\n" . " no. of urls: " . $no_of_urls . " URLs | taxid count = " . $j . "\n";
    }
    print " \n";
    $arr = array_keys($arr);
    return $arr;
}

?>