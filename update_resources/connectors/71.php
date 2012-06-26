<?php
namespace php_active_record;

define('DOWNLOAD_WAIT_TIME', '2000000');  // 2 second wait after every web request
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
define("WIKI_USER_PREFIX", "http://commons.wikimedia.org/wiki/User:");
require_vendor("wikipedia");


$resource = Resource::find(71);


// cleaning up downloaded files
shell_exec("rm -f ". DOC_ROOT ."update_resources/connectors/files/wikimedia/*");
shell_exec("rm -f ". DOC_ROOT ."update_resources/connectors/files/wikimedia.xml");
shell_exec("rm -f ". DOC_ROOT ."update_resources/connectors/files/wikimedia.xml.bz2");


// download latest Wikimedia Commons export
echo "curl ".$resource->accesspoint_url." -o ". DOC_ROOT ."update_resources/connectors/files/wikimedia.xml.bz2\n";
shell_exec("curl ".$resource->accesspoint_url." -o ". DOC_ROOT ."update_resources/connectors/files/wikimedia.xml.bz2");
// unzip the download
shell_exec("bunzip2 ". DOC_ROOT ."update_resources/connectors/files/wikimedia.xml.bz2");
// split the huge file into 300M chunks
shell_exec("split -b 300m ". DOC_ROOT ."update_resources/connectors/files/wikimedia.xml ". DOC_ROOT ."update_resources/connectors/files/wikimedia/part_");

// determine the filename of the last chunk
$last_line = exec("ls -l ". DOC_ROOT ."update_resources/connectors/files/wikimedia");
if(preg_match("/part_([a-z]{2})$/", trim($last_line), $arr)) $final_part_suffix = $arr[1];
else
{
    echo "\n\nCouldn't determine the last file to process\n$last_line\n\n";
    exit;
}



// preparing global variables
$GLOBALS['scientific_pages'] = array();
$GLOBALS['scientific_page_images'] = array();
$GLOBALS['taxa'] = array();
$GLOBALS['data_objects'] = array();


// first pass through all files to grab taxon information and determine scientific images
iterate_files('php_active_record\get_scientific_pages');
// second pass to grab image information for scientific images
iterate_files('php_active_record\get_media_pages');

echo "\n\n# total taxa: ".count($GLOBALS['taxa'])."\n";
echo "# total images: ".count($GLOBALS['data_objects'])."\n";


// calling Wikimedia Commons API to get image file URLs which are not in the XML dump
get_image_urls();
// prepare EOL Content Schema XML file and save to CONTENT_RESOURCE_LOCAL_PATH
create_resource_file();

echo "end";


















// FUNCTIONS

function iterate_files($callback, $title = false)
{
    global $final_part_suffix;
    list($major, $minor) = str_split($final_part_suffix);
    $ord1 = ord("a");
    $ord2 = ord("a");
    
    $left_overs = "";
    while($ord1 <= ord($major))
    {
        while($ord2 <= ord("z"))
        {
            $left_overs = process_file(chr($ord1).chr($ord2), $left_overs, $callback, $title);
            
            if($ord1 == ord($major) && $ord2 == ord($minor))
            {
                break;
            }
            $ord2++;
            // if($ord2 == ord("b")) break;
        }
        
        $ord1++;
        $ord2 = ord("a");
        // break;
    }
}

function process_file($part_suffix, $left_overs, $callback, $title = false)
{
    echo "Processing file $part_suffix with callback $callback ".memory_get_usage()."\n";
    flush();
    $FILE = fopen(DOC_ROOT ."update_resources/connectors/files/wikimedia/part_".$part_suffix, "r");
    
    $current_page = $left_overs;
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
                $page_number++;
                if($page_number % 100000 == 0)
                {
                    echo "page: $page_number\n";
                    echo "memory: ".memory_get_usage()."\n";
                    flush();
                }
                
                if($title && !preg_match("/<title>". preg_quote($title, "/") ."<\/title>/ims", $current_page))
                {
                    echo "<title>". preg_quote($title, "/") ."<\/title>\n";
                    continue;
                }
                
                call_user_func($callback, $current_page);
                $current_page = "";
            }
        }
    }
    
    echo "\n\n# taxa so far: ".count($GLOBALS['taxa'])."\n";
    echo "# images so far: ".count($GLOBALS['image_titles'])."\n";
    echo "# objects so far: ".count($GLOBALS['data_objects'])."\n";
    return $current_page;
}





function get_scientific_pages($xml)
{
    if(preg_match("/\{\{Taxonavigation/", $xml, $arr))
    {
        $page = new \WikimediaPage($xml);
        $GLOBALS['scientific_pages'][$page->title] = 1;
        if($params = $page->taxon_parameters())
        {
            if(@$params['scientificName']) $GLOBALS['taxa'][$page->title] = $params;
        }
        
        $images = $page->images();
        foreach($images as $image)
        {
            $GLOBALS['scientific_page_images'][$page->title][] = "File:".$image;
            $GLOBALS['image_titles']["File:".$image] = 1;
        }
    }
}

function get_media_pages($xml)
{
    $page = new \WikimediaPage($xml);
    if(@$GLOBALS['image_titles'][$page->title])
    {
        if($params = $page->data_object_parameters())
        {
            $GLOBALS['data_objects'][$page->title] = $params;
        }
    }
}







function create_resource_file()
{
    global $resource;
    $all_taxa = array();
    
    $taxon_number = 0;
    foreach($GLOBALS['taxa'] as $taxon_title => $taxon_parameters)
    {
        if(isset($GLOBALS['scientific_page_images'][$taxon_title]))
        {
            foreach($GLOBALS['scientific_page_images'][$taxon_title] as $image_title)
            {
                if(isset($GLOBALS['data_objects'][$image_title]) && isset($GLOBALS['data_objects'][$image_title]['mediaURL']))
                {
                    $taxon_parameters["dataObjects"][] = new \SchemaDataObject($GLOBALS['data_objects'][$image_title]);
                }
            }
        }
        
        unset($GLOBALS['taxa'][$taxon_title]);
        unset($GLOBALS['scientific_page_images'][$taxon_title]);
        
        $all_taxa[] = new \SchemaTaxon($taxon_parameters);
    }
    
    $FILE = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource->id."_tmp.xml", "w+");
    \SchemaDocument::get_taxon_xml($all_taxa, $FILE);
    fclose($FILE);
    
    if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource->id."_tmp.xml") > 600)
    {
        @unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource->id."_previous.xml");
        @rename(CONTENT_RESOURCE_LOCAL_PATH . $resource->id.".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource->id."_previous.xml");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $resource->id."_tmp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource->id.".xml");
        
        $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Force Harvest')->id." WHERE id=$resource->id");
    }
}

function get_image_urls()
{
    $search_titles = array();
    $total_objects = count($GLOBALS['data_objects']);
    $total_lookups = ceil($total_objects/50);
    static $lookup_number = 0;
    foreach($GLOBALS['data_objects'] as $params)
    {
        $stripped_title = str_replace(" ", "_", $params['title']);
        $search_titles[$stripped_title] = $params['title'];
        if(count($search_titles) >= 50)
        {
            $lookup_number++;
            echo "Looking up $lookup_number of $total_lookups\n";
            flush();
            lookup_image_urls($search_titles);
            $search_titles = array();
        }
    }
    
    if($search_titles)
    {
        $lookup_number++;
        echo "Looking up $lookup_number of $total_lookups ".memory_get_usage()."\n";
        flush();
        lookup_image_urls($search_titles);
    }
}

function lookup_image_urls($titles)
{
    $url = "http://commons.wikimedia.org/w/api.php?action=query&format=json&prop=imageinfo&iiurlwidth=460&iiprop=url&titles=";
    $url .= implode("|", array_keys($titles));
    
    $result = Functions::get_remote_file_fake_browser($url);
    
    $normalized = array();
    $json = json_decode($result);
    if(isset($json->query->normalized))
    {
        foreach($json->query->normalized as $obj)
        {
            $normalized[(string) $obj->to] = (string) $obj->from;
        }
    }
    
    if(isset($json->query->pages))
    {
        foreach($json->query->pages as $obj)
        {
            $title = (string) $obj->title;
            //if(isset($normalized[$title])) $title = (string) $normalized[$obj->title];
            
            if(isset($GLOBALS['data_objects'][$title]))
            {
                $url = $obj->imageinfo[0]->url;
                $GLOBALS['data_objects'][$title]['mediaURL'] = $url;
            }else echo "NOTHING FOR $title\n";
        }
    }
}






function print_page(&$page)
{
    echo "<b>".$page->title."</b><br>";
    echo "<div style='background-color:#DDDDDD;'><pre>".htmlspecialchars($page->xml)."</pre></div>\n";
    Functions::print_pre($page->licenses());
    Functions::print_pre($page->taxonomy());
    Functions::print_pre($page->taxon_parameters());
    Functions::print_pre($page->data_object_parameters());
    Functions::print_pre($page->information());
    echo "Contributor: ". $page->contributor ."<br>";
    echo "Author: ". $page->author() ."<br>";
    echo "Description: ". $page->description() ."<br>";
    echo "<hr>";
}

?>
