<?php
namespace php_active_record;

define('DOWNLOAD_WAIT_TIME', '1000000');  // 2 second wait after every web request
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
define("WIKI_USER_PREFIX", "http://commons.wikimedia.org/wiki/User:");
define("WIKI_PREFIX", "http://commons.wikimedia.org/wiki/");
require_vendor("wikipedia");


$resource = Resource::find(71);


// cleaning up downloaded files
$base_directory_path = DOC_ROOT ."update_resources/connectors/files/";
$part_file_base = $base_directory_path."wikimedia/part_";
shell_exec("rm -f ". $part_file_prefix . "*");
shell_exec("rm -f ". $base_directory_path . "wikimedia.xml");
shell_exec("rm -f ". $base_directory_path . "wikimedia.xml.bz2");


// download latest Wikimedia Commons export
echo "curl ".$resource->accesspoint_url." -o ". $base_directory_path . "wikimedia.xml.bz2\n";
shell_exec("curl ".$resource->accesspoint_url." -o ". $base_directory_path . "wikimedia.xml.bz2");
// unzip the download
shell_exec("bunzip2 ". $base_directory_path . "wikimedia.xml.bz2");
// split the huge file into 300M chunks
$part_file_suffix_chars = 3; //allows for splitting the file into max 26^n parts, e.g. n=3, parts=300Mb, copes with xml files < 4.2Tb
shell_exec("split -a ".$part_file_suffix_chars." -b 300m ". $base_directory_path . "wikimedia.xml ". $part_file_base);

// preparing global variables
$GLOBALS['taxa'] = array();
$GLOBALS['taxonomic_categories'] = array();
$GLOBALS['taxonomic_galleries']  = array();
$GLOBALS['gallery_files'] = array();
$n_media_files = $n_pages = 0;


// first pass through all files to grab taxon information and determine scientific images
iterate_files($part_file_base, $part_file_suffix_chars, 'php_active_record\get_taxonomic_pages');

$galleries_with_files = count(array_unique($GLOBALS['gallery_files']));
echo "\n\n# total galleries:  ".count($GLOBALS['taxonomic_galleries']).", with ".count($GLOBALS['gallery_files'])." media files.";
echo " $galleries_with_files galleries (".@($galleries_with_files/count($GLOBALS['taxonomic_galleries'])*100)."%) actually have files\n";
echo "\n\n# total categories: ".count($GLOBALS['taxonomic_categories'])."\n";


// FINAL PASS: grab file information for scientific media pages and save to file
$xml_output = new \SchemaDocument(CONTENT_RESOURCE_LOCAL_PATH . $resource_id."_tmp.xml");
iterate_files($part_file_base, $part_file_suffix_chars, 'php_active_record\get_media_pages');
$last_number = batch_process(); //process the pages remaining in the last batch
echo "\n\n (last ".$last_number." media files processed)\n";

echo "\n\n# media files: ".$n_media_files." (in ".count($GLOBALS['taxa'])." taxa)\n";

check_remaining_gallery_files();

process_resource_file($xml_output);
echo "End\n";




// FUNCTIONS

function iterate_files($part_file_base, $n_suffix_chars, $callback, $title = false)
{
    $left_overs="";
    $suffix = str_repeat('a', $n_suffix_chars);
    while ((strlen($suffix)==$n_suffix_chars) && process_file($part_file_base.$suffix, $callback, $left_overs, $title)) {
       $suffix++; // auto-increment allows us to match the output of the 'split' command: aaa->aab, aaz->aba, etc
    };
    if (!preg_match('/\s*<\/mediawiki>\s*/smi', $left_overs)) 
    {
        echo "WARNING: THE LAST WIKI FILE APPEARS TO BE TRUNCATED. Part of the wiki download may be missing.\n";
        flush();
    }
}

function process_file($filename, $callback, &$left_overs="", $title = false)
{
    if (!file_exists($filename)) {
        echo "Assuming no more part files to process (as ".basename($filename)." doesn't exist)\n";
        return FALSE;
    }
    echo "Processing file ".basename($filename)." with callback $callback. Memory at start: ".(memory_get_usage()/1024/1024)." Mb\n";
    flush();
    $FILE = fopen($filename, "r");

    $current_page = $left_overs;
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

    $left_overs = $current_page;
    return TRUE;
}


function get_taxonomic_pages($xml)
{
    global $n_pages;
    if (\WikimediaPage::fast_is_gallery_category_or_template($xml))
    {
        if ($text_start = strpos($xml, "<text")) //make sure we don't include cases with {{Taxonavigation in the comments field, etc.
        {
            if(preg_match("/\{\{Taxonavigation/", $xml, $arr, 0, $text_start))
            {
                $page = new \WikimediaPage($xml);
                if ($page->is_template()) {
                    if ($page->contains_template("Taxonavigation"))
                    {  //This is a template that itself contains the template "Taxonavigation", so we might be interested in it
                        if (preg_match("/^Template:Taxonavigation\//", $page->title))
                        {
                            //we don't need to worry: it's something like Template::Taxonavigation/doc,
                        } else {
                            print "The template '".$page->title."' transcludes {{Taxonavigation}}: we might want to consider looking for taxonomic pages containing this template too.\n";
                        }
                    }
                } else {
                    if($params = $page->taxon_parameters()
                    {
                        if(@$params['scientificName']) {
                            $GLOBALS['taxa'][$page->title] = $params;
                            
                            if($page->is_category()) 
                            {
                                $GLOBALS['taxonomic_categories'][$page->title] = $page->taxonomy_score();
                            } elseif ($page->is_gallery())
                            {
                                $GLOBALS['taxonomic_galleries'][$page->title] = $page->taxonomy_score();
                                foreach($page->media_on_page() as $file)
                                {
                                    $GLOBALS['gallery_files']["File:".$file] = $page->title;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $n_pages++;
    if($n_pages % 100000 == 0)
    {
        echo "Page: $n_pages (first pass). # taxa so far: ".count($GLOBALS['taxa']).". Memory: ".(memory_get_usage()/1024/1024)." Mb\n";
        flush();
    }
}

function get_media_pages($xml)
{
    global $n_pages;
    global $n_media_files;

    static $processed_files=0;
    static $p=0;

    $wanted=FALSE;
    if (\WikimediaPage::fast_is_media($xml)) {
        $page = new \WikimediaPage($xml);

        //check if this page has been listed in a gallery
        if(isset($GLOBALS['gallery_files'][$page->title]))
        {
            if (isset($page->redirect)) {
                //we won't catch redirects to pages earlier in the XML dump. Let's just hope those
                //have been picked up when scanning for categories. We'll check this later
                echo "Page '".$page->title."' listed in gallery ".$GLOBALS['gallery_files'][$page->title]."has been redirected. Now looking for '".$page->redirect."' instead\n";
                flush();
                $GLOBALS['gallery_files'][$page->redirect] = $GLOBALS['gallery_files'][$page->title];
            } else {
                $page->set_gallery($GLOBALS['gallery_files'][$page->title]);
                $wanted=TRUE;
            };
            unset($GLOBALS['gallery_files'][$page->title]); //remove the links to the gallery files as we go
        }

        //check if the page has an associated "taxonomic category"
        foreach ($page->quick_categories() as $cat) { //done on every file in the dump: be careful not to trigger off a remote API call
            $cat = "Category:$cat";
            if (isset($GLOBALS['taxonomic_categories'][$cat])) 
            {
                $wanted=TRUE; //just flag this as wanted. We'll search for proper categories later
                break;
            }
        }
            
        if ($wanted) 
        {
            $processed_files += batch_process($page);
        }
    }
    $p++;
    if($p % 100000 == 0)
    {
        echo "Page: $p (final pass, ".round($p/$n_pages*100, 1)."% done). # media files so far: ".$n_media_files." (".$processed_files." completed via MediaWiki API query). Memory: ".(memory_get_usage()/1024/1024)." Mb\n";
        flush();
    }
}

function batch_process($page=null)
{   //if page is null, just process any remaining in the batch
    global $n_media_files;
    static $batch=array();
    $batch_volume = \WikimediaPage::$max_titles_per_lookup;
    
    if ($page) {
        $n_media_files++;
        //we could potentially only check files with recently updated timestamps here?
        //but we would also need to catch unchanged files whose taxonomic classification has changed
        $batch[] = $page;
        if (count($batch) < $batch_volume) return 0; //wait until we have enough in a batch.
    }

    //either there are enough pages in the batch to process, or $page==null, triggering us to process the remaining pages in the batch
    \WikimediaPage::process_pages_using_API($batch);
    
    foreach ($batch as $page) {
        //page may have multiple taxonomies: e.g. from gallery "Mus musculus", category "Mus musculus", category "Mus", etc.
        //pick the one with the highest "taxonomy score"
        if (is_null($gallery = $page->get_gallery())) {
            $best_taxonomy = null;
            $best_taxonomy_score = -1;
        } else {
            $best_taxonomy = $gallery;
            $best_taxonomy_score = $GLOBALS['taxonomic_galleries'][$best_taxonomy];
        };
    
        if (!isset($page->categories) || (count($page->categories)==0)) {
            echo "ERROR. This shouldn't happen.";
            if (!isset($page->categories)) 
            {    
                echo " Categories do not even exist for ".$page->title." (have you failed to connect to the Wikimedia API?)\n";
            } else {
                echo " No categories at all for ".$page->title."\n";
            }
        } else {
            $potential_license_categories = "";
            foreach($page->categories as $cat) 
            {
                if (isset($GLOBALS['taxonomic_categories']["Category:$cat"])) {
                    $fullcat="Category:$cat";
                    $diff = $best_taxonomy_score - $GLOBALS['taxonomic_categories'][$fullcat];
                    if ($diff < 0) 
                    {
                        if (($diff <-0.5) && isset($best_taxonomy))
                        {
                            echo "Got a substantially better taxonomy for ".$page->title.": $best_taxonomy (score $best_taxonomy_score)";
                            echo " replaced with $fullcat (score ".$GLOBALS['taxonomic_categories'][$fullcat].")\n";
                            flush();
                        };

                        $best_taxonomy = $fullcat;
                        $best_taxonomy_score = $GLOBALS['taxonomic_categories'][$fullcat];
                    }
                } else {
                    $potential_license_categories .= $cat."\n";
                }
            }
            
            if ($license = \WikimediaPage::match_license($potential_license_categories, FALSE)) {
                $page->set_license($license); //override with the more reliable license from categories
            }
        }
        if (!$page->has_license()) {
            echo "No valid license category for ".$page->title;
            if (isset($page->categories)) echo " (Categories: ".implode("|",$page->categories).")";
            echo "\n";
            flush();
        }
        if (empty($best_taxonomy)) {
            echo "ERROR. This shouldn't happen. No valid taxonomy for ".$page->title;
            if (isset($page->categories)) echo " (Categories: ".implode("|",$page->categories).")";
            echo "\n";
            flush();
        }

        $taxon_data = $GLOBALS['taxa'][$best_taxonomy];
        add_to_resource_file($taxon_data, $page->get_data_object_parameters());
    };

    $batch_size=count($batch);
    $batch=array();
    return $batch_size;
}


function add_to_resource_file($taxon_data, $data_object_parameters)
{
    global $xml_output;
 
    if(isset($data_object_parameters['mediaURL'])) {
        $taxon_data['dataObjects'][] = new \SchemaDataObject($data_object_parameters);

        $taxon = new \SchemaTaxon($taxon_data);
        $xml_output->save_taxon_xml($taxon);
    };
}

function process_resource_file($xml_output)
{
    $filename = $xml_output->filename;
    if (isset($xml_output)) unset($xml_output);

    $basename = preg_replace("/_tmp\.xml$/", "", $filename);
    if((filesize($filename) > 600) && ($basename != $filename))
    {
        @rename($basename.".xml", $basename."_previous.xml"); //overwrite previous
        @rename($filename, $basename.".xml");

        $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Force Harvest')->id." WHERE id=$resource->id");
    }
}

function check_remaining_gallery_files() {
    if (count($GLOBALS['gallery_files'])) {
        $good_files=array();
        echo count($GLOBALS['gallery_files'])." gallery files remaining at end. Checking them out:";flush();
        $titles = array_chunk(array_keys($GLOBALS['gallery_files']), \WikimediaPage::$max_titles_per_lookup, true);
        foreach ($titles as $batch) {
            $good_files += \WikimediaPage::check_page_titles($batch);
        }
        if (count($good_files)) {
            echo "\n\nMISSED THE FOLLOWING ".count($good_files)." FILES";
            echo " (if you have the scanned whole XML dump, these may be pages whose title has changed and have not been placed in a valid taxonomic category)\n";
            foreach ($good_files as $title) {
                echo "* ".$title." in gallery <".$GLOBALS['gallery_files'][$title].">\n";
            }
        }
        flush();
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
