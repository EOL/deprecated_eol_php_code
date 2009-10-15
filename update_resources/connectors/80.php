<?php


define('ENVIRONMENT', 'slave');
//define('DEBUG', true);
include_once(dirname(__FILE__) . "/../../config/start.php");
define("WIKI_USER_PREFIX", "http://en.wikipedia.org/wiki/User:");
Functions::require_module("wikipedia");
$mysqli =& $GLOBALS['mysqli_connection'];

date_default_timezone_set('America/New_York');



$resource = new Resource(80);

// // download latest Wikimedia Commons export
// shell_exec("curl ".$resource->accesspoint_url." -o ".dirname(__FILE__)."/files/wikipedia.xml.bz2");
// // unzip the download
// shell_exec("bunzip2 ".dirname(__FILE__)."/files/wikipedia.xml.bz2");
// // split the huge file into 300M chunks
// shell_exec("split -b 300m ".dirname(__FILE__)."/files/wikipedia.xml ".dirname(__FILE__)."/files/wikipedia/part_");

// determine the filename of the last chunk
$last_line = exec("ls -l ".dirname(__FILE__)."/files/wikipedia");
if(preg_match("/part_([a-z]{2})$/", trim($last_line), $arr)) $last_part = $arr[1];
else
{
    echo "\n\nCouldn't determine the last file to process\n$last_line\n\n";
    exit;
}


// do something for only one title
if(@$_GET['title']) $title = strtolower(trim($_GET['title']));
//else $title = strtolower("Bird");


// grab the stored list of title/part_number
$GLOBALS['titles_files'] = array();
if($title)
{
    $file = file(LOCAL_ROOT . "temp/wiki_titles.txt");
    $part = "";
    foreach($file as $line)
    {
        $line = trim($line);
        if(preg_match("/^>>([a-z]{2})$/", $line, $arr))
        {
            $part = $arr[1];
            continue;
        }else
        {
            $GLOBALS['titles_files'][strtolower($line)] = $part;
        }
    }
}


// preparing global variabl
$GLOBALS['taxa_pages'] = array();
$GLOBALS['all_taxa'] = array();

if($title)
{
    if(@$GLOBALS['titles_files'][$title]) iterate_files($GLOBALS['titles_files'][$title], "get_scientific_pages", $title);
    else
    {
        echo "Can't find $title";
        exit;
    }
}else
{
    $$xml = "<?xml version='1.0' encoding='utf-8' ?>\n";
    $xml .= "<response\n";
    $xml .= "  xmlns='http://www.eol.org/transfer/content/0.2'\n";
    $xml .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
    $xml .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
    $xml .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
    $xml .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
    $xml .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
    $xml .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
    $xml .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.2 http://services.eol.org/schema/content_0_2.xsd'>\n";
    
    $GLOBALS['resource_file'] = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource->id.".xml", "w+");
    fwrite($GLOBALS['resource_file'], $xml);
    
    iterate_files($last_part, "get_scientific_pages");
    
    fwrite($GLOBALS['resource_file'], "</response>");
    fclose($GLOBALS['resource_file']);
}


















function iterate_files($last_part, $callback, $title = false)
{
    list($major, $minor) = str_split($last_part);
    
    $ord1 = ord("a");
    $ord2 = ord("a");
    
    if($title) list($ord1, $ord2) = array(ord($major), ord($minor));
    
    while($ord1 <= ord($major))
    {
        while($ord2 <= ord("z"))
        {
            process_file(chr($ord1).chr($ord2), $callback, $title);
            
            if($ord1 == ord($major) && $ord2 == ord($minor))
            {
                break;
            }
            $ord2++;
        }
        
        $ord1++;
        $ord2 = ord("a");
    }
}

function process_file($part_suffix, $callback, $title = false)
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
                
                
                call_user_func($callback, $current_page, $OUT, $title);
                $current_page = "";
                
                //$count = count($GLOBALS['taxa_pages']);
                // if($count>=20)
                // {
                //     break;
                // }
            }
        }
    }
}

function get_scientific_pages($xml, $OUT, $title = false)
{
    if(preg_match("/\{\{Taxobox/ims", $xml, $arr))
    {
        $count = count($GLOBALS['taxa_pages']);
        if($count && $count%1000==0) echo "taxon: $count\n";
        
        
        $page = new WikiPage($xml);
        if(preg_match("/wikipedia/ims", $page->title)) return false;
        if(preg_match("/taxobox/ims", $page->title)) return false;
        
        // break if this is not the desired page
        if($title && strtolower($page->title) != $title) return false;
        
        $GLOBALS['taxa_pages'][] = $page->title;
        
        // this is an exception for the API that feeds out the HTML
        if($title && @$_GET['title'])
        {
            echo '  <script type="text/javascript" src="http://services.eol.org/wiki_ogg_player.js"></script>
                    <link rel="stylesheet" href="http://services.eol.org/wikipedia.css" type="text/css" media="screen" />
                        <div id="globalWrapper">
                        <div id="column-content">
                        <div id="content">
                        <div id="bodyContent">';
            echo $page->get_page_html();
            echo '</div></div></div></div>';
            exit;
        }
        
        if($taxon_params = $page->taxon_parameters())
        {
            if($data_object_params = $page->data_object_parameters())
            {
                $taxon_params['dataObjects'][] = new SchemaDataObject($data_object_params);
            }
            
            $taxon = new SchemaTaxon($taxon_params);
            
            fwrite($GLOBALS['resource_file'], $taxon->__toXML());
        }
        else return false;
    }
}




?>