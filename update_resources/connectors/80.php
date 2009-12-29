<?php


define('ENVIRONMENT', 'slave');
//define('DEBUG', true);
include_once(dirname(__FILE__) . "/../../config/start.php");
define("WIKI_USER_PREFIX", "http://en.wikipedia.org/wiki/User:");
Functions::require_module("wikipedia");
$mysqli =& $GLOBALS['mysqli_connection'];

date_default_timezone_set('America/New_York');



$resource = new Resource(80);

// download latest Wikipedia export
shell_exec("curl ".$resource->accesspoint_url." -o ".dirname(__FILE__)."/files/wikipedia.xml.bz2");
// unzip the download
shell_exec("bunzip2 ".dirname(__FILE__)."/files/wikipedia.xml.bz2");
// split the huge file into 300M chunks
shell_exec("split -b 300m ".dirname(__FILE__)."/files/wikipedia.xml ".dirname(__FILE__)."/files/wikipedia/part_");

// determine the filename of the last chunk
$last_line = exec("ls -l ".dirname(__FILE__)."/files/wikipedia");
if(preg_match("/part_([a-z]{2})$/", trim($last_line), $arr)) $last_part = $arr[1];
else
{
    echo "\n\nCouldn't determine the last file to process\n$last_line\n\n";
    exit;
}


//$_GET['title'] = "Polar bear";

// preparing global variables
$GLOBALS['taxa_pages'] = array();


$GLOBALS['resource_file'] = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource->id.".xml", "w+");
fwrite($GLOBALS['resource_file'], SchemaDocument::xml_header());

iterate_files($last_part, "get_scientific_pages");

fwrite($GLOBALS['resource_file'], SchemaDocument::xml_footer());
fclose($GLOBALS['resource_file']);

// cleaning up downloaded files
shell_exec("rm -f ".dirname(__FILE__)."/files/wikipedia/*");
shell_exec("rm -f ".dirname(__FILE__)."/files/wikipedia.xml");
shell_exec("rm -f ".dirname(__FILE__)."/files/wikipedia.xml.bz2");

echo "end";












function iterate_files($last_part, $callback)
{
    list($major, $minor) = str_split($last_part);
    
    $ord1 = ord("a");
    $ord2 = ord("a");
    
    //if($title) list($ord1, $ord2) = array(ord($major), ord($minor));
    
    while($ord1 <= ord($major))
    {
        while($ord2 <= ord("z"))
        {
            process_file(chr($ord1).chr($ord2), $callback);
            
            if($ord1 == ord($major) && $ord2 == ord($minor))
            {
                break;
            }
            $ord2++;
            //break;
        }
        
        $ord1++;
        $ord2 = ord("a");
        //break;
    }
}

function process_file($part_suffix, $callback)
{
    echo("Processing file $part_suffix with callback $callback\n");
    flush();
    $FILE = fopen(dirname(__FILE__)."/files/wikipedia/part_".$part_suffix, "r");
    
    $current_page = "";
    static $page_number = 0;
    while(!feof($FILE))
    {
        if($line = fgets($FILE, 4096))
        {
            $current_page .= $line;
            
            if(trim($line) == "<page>")
            {
                $current_page = $line;
            }
            if(trim($line) == "</page>")
            {
                if($page_number % 50000 == 0)
                {
                    echo("page: $page_number\n");
                    echo("memory: ".memory_get_usage()."\n");
                    flush();
                }
                $page_number++;
                
                call_user_func($callback, $current_page);
                $current_page = "";
            }
        }
    }
}


function get_scientific_pages($xml)
{
    if(preg_match("/\{\{Taxobox/ims", $xml, $arr))
    {
        $count = count($GLOBALS['taxa_pages']);
        if($count && $count%1000==0) echo "taxon: $count\n";
        
        
        $page = new WikiPage($xml);
        if(preg_match("/wikipedia/ims", $page->title)) return false;
        if(preg_match("/taxobox/ims", $page->title)) return false;
        if(preg_match("/template/ims", $page->title)) return false;
        
        // break if this is not the desired page
        if(@$_GET['title'] && strtolower($page->title) != strtolower($_GET['title'])) return false;
        
        $GLOBALS['taxa_pages'][] = $page->title;
        
        echo $page->title."\n";
        if($taxon_params = $page->taxon_parameters())
        {
            if($data_object_params = $page->data_object_parameters())
            {
                $taxon_params['dataObjects'][] = new SchemaDataObject($data_object_params);
            }else echo "   no data object\n";
            
            
            $taxon = new SchemaTaxon($taxon_params);
            
            fwrite($GLOBALS['resource_file'], $taxon->__toXML());
        }else
        {
            echo "   no taxon\n";
            return false;
        }
    }
}




?>