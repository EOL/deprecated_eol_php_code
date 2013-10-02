<?php
namespace php_active_record;

class WikimediaHarvester
{
    private $mysqli;
    private $taxa_pages;
    private $pageids_to_update;
    private $pageids_to_ignore;
    private $resource_file;
    private $resource;

    function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->resource = Resource::find(71);
        $this->base_directory_path = DOC_ROOT . "update_resources/connectors/files/";
        $this->part_file_base = $this->base_directory_path . "wikimedia/part_";
        // allows for splitting the file into max 26^n parts, e.g. n=3, parts=300Mb, copes with xml files < 4.2Tb
        $this->part_file_suffix_chars = 3;
        $this->taxa = array();
        $this->taxonav_includes = array();
        $this->gallery_files_to_check = array(); //key=media-filename, value = array of gallery names (usually just one)
        $this->taxonomies_for_media_file = array();    //key=media-filename, value = count of taxonomies for this file
        $this->map_categories = self::get_map_categories($this->base_directory_path);
        $this->total_pages_in_dump = 0;
        $this->queue_of_pages_to_process = array();

        $this->resource_file_path = CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id . "_temp.xml";
        $this->resource_file = new \SchemaDocument($this->resource_file_path);
    }

    public function begin_wikipedia_harvest()
    {
        // delete the downloaded files
        $this->cleanup_dump();
        $this->download_dump();

        // FIRST PASS: go through all files to grab TaxonavigationIncluded files, e.g. https://commons.wikimedia.org/wiki/Template:Aves
        $this->iterate_files(array($this, 'get_taxonav_includes'));

        // INTERMEDIATE PASS: grab taxon information and determine scientific images
        $this->iterate_files(array($this, 'get_taxonomic_pages'));

        // FINAL PASS: grab file information for scientific media pages and save to file
        $this->iterate_files(array($this, 'get_media_pages'));

        echo "\n\n(processing last ". count($this->queue_of_pages_to_process) ." media)\n";
        $this->process_page_queue();
        echo "\n\n# media files: ".count($this->taxonomies_for_media_file)." (in ". count($this->taxa) ." taxa)\n";
        $this->check_for_unaccounted_galleries();

        @unlink(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_previous.xml");
        @rename(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id.".xml", CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_previous.xml");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id.".xml");

        echo "End\n";
        self::print_memory_and_time();
    }

    private function download_dump()
    {
        // download latest Wikimedia Commons export
        shell_exec("curl ".$this->resource->accesspoint_url." -o ". $this->base_directory_path . "wikimedia.xml.bz2");
        // unzip the download
        shell_exec("bunzip2 ". $this->base_directory_path . "wikimedia.xml.bz2");
        // split the huge file into 300M chunks
        shell_exec("split -a ". $this->part_file_suffix_chars ." -b 300m ". $this->base_directory_path . "wikimedia.xml ". $this->part_file_base);
    }

    private function cleanup_dump()
    {
        // cleaning up downloaded files
        shell_exec("rm -f ". $this->base_directory_path . "wikimedia/*");
        shell_exec("rm -f ". $this->base_directory_path . "wikimedia.xml");
        shell_exec("rm -f ". $this->base_directory_path . "wikimedia.xml.bz2");
    }

    private function iterate_files($callback)
    {
        $total_pages_processed = 0;
        $this->page_iteration_left_overs = "";
        $suffix = str_repeat('a', $this->part_file_suffix_chars);
        while((strlen($suffix) == $this->part_file_suffix_chars))
        {
            $filename = $this->part_file_base . $suffix;
            if(!file_exists($filename))
            {
                echo "Assuming no more part files to process (as ". basename($filename) ." doesn't exist)\n";
                break;
            }
            $total_pages_processed += $this->process_file($filename, $callback);
            // auto-increment allows us to match the output of the 'split' command: aaa->aab, aaz->aba, etc
            $suffix++;
        }
        if(!preg_match('/\s*<\/mediawiki>\s*/smi', $this->page_iteration_left_overs))
        {
            echo "WARNING: THE LAST WIKI FILE APPEARS TO BE TRUNCATED. Part of the wiki download may be missing.\n";
        }
        if ($this->total_pages_in_dump==0) $this->total_pages_in_dump = $total_pages_processed;
    }

    private function process_file($filename, $callback)
    {
        $pages_processed=0;
        if(file_exists($filename)) {
            echo "Processing file '". basename($filename) ."' with callback ". $callback[1] ."\n";
            self::print_memory_and_time();
    
            // the last file ended in the middle of a page. Use the remainder of the last file as a starting point
            if (isset($this->page_iteration_left_overs)) 
                $current_page = $this->page_iteration_left_overs; 
            else
                $current_page = "";
            $this->page_iteration_left_overs = "";
            foreach(new FileIterator($filename) as $line)
            {
                $line .= "\n";
                $current_page .= $line;
                // this is a new page so reset $current_page
                if(trim($line) == "<page>") $current_page = $line;
                elseif(trim($line) == "</page>")
                {
                    call_user_func($callback, $current_page);
                    $pages_processed++;
                    $current_page = "";
                }
            }
            // in the middle of a <page> tag. Save the current page to use as a starting pont for the next file
            $this->page_iteration_left_overs = $current_page;
        }
        return $pages_processed;
    }

    private function get_taxonav_includes($xml)
    {
        if(\WikimediaPage::fast_is_template($xml))
        {
            // make sure we don't include cases with {{Template in the comments field, etc.
            if($text_start = strpos($xml, "<text"))
            {
                // also catches TaxonavigationIncluded etc.
                if(preg_match("/\{\{Taxonavigation/", $xml, $arr, 0, $text_start))
                {
                    $page = new \WikimediaPage($xml);
                    if($page->contains_template("TaxonavigationIncluded[\w\s]*"))
                    {
                        $include_array = $page->taxonav_as_array("[Tt]axonavigationIncluded[\w\s]*");
                        if(count($include_array))
                        {
                            $this->taxonav_includes[$page->title] = array('taxo' => $include_array, 'last_mod'=>strtotime($page->timestamp));
                        }else echo "$page->title is not a real TaxonavigationInclude* template\n";
                    }
                }
            }
        }
        static $count = 0;
        $count++;
        if(($count % 100000 == 0) || ($count == $this->total_pages_in_dump))
        {
            echo "Page: $count (preliminary pass";
            if ($this->total_pages_in_dump) echo ": ".round($count/$this->total_pages_in_dump*100, 1)."% done";
            echo "). # TaxonavigationIncluded files so far: ". count($this->taxonav_includes) . ".\n";
            self::print_memory_and_time();
        }
    }

    private function get_taxonomic_pages($xml)
    {
        if(\WikimediaPage::fast_is_gallery_category_or_template($xml))
        {
            // make sure we don't include cases with {{Taxonavigation in the comments field, etc.
            if($text_start = strpos($xml, "<text"))
            {
                if(preg_match("/\{\{Taxonavigation/", $xml, $arr, 0, $text_start))
                {
                    $page = new \WikimediaPage($xml);
                    if($page->is_template())
                    {
                        if($page->contains_template("Taxonavigation"))
                        {
                            // This is a template that itself contains the template "Taxonavigation", so we might be interested in it
                            if(preg_match("/^Template:Taxonavigation\//", $page->title))
                            {
                                // we don't need to worry: it's something like Template::Taxonavigation/doc,
                            }else
                            {
                                echo "The template '$page->title' transcludes {{Taxonavigation}}: we might want to consider looking for taxonomic pages containing this template too.\n";
                            }
                        }
                    }else
                    {
                        // pass in "taxonav_includes" to avoid lots of API calls
                        if($params = $page->taxonomy($this->taxonav_includes))
                        {
                            if($params->scientificName())
                            {
                                $this->taxa[$page->title] = $params;

                                if($page->is_gallery())
                                {
                                    foreach($page->media_on_page() as $file)
                                    {
                                        $this->gallery_files_to_check["File:".$file][] = $page->title;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        static $count = 0;
        $count++;
        if(($count % 100000 == 0) || $count == $this->total_pages_in_dump)
        {
            echo "Page: $count (penultimate pass";
            if ($this->total_pages_in_dump) echo ": ".round($count/$this->total_pages_in_dump*100, 1)."% done";
            echo "). # taxa so far: ". count($this->taxa)." of which ";
            $galleries = count(array_unique(call_user_func_array('array_merge', $this->gallery_files_to_check)));
            echo round($galleries/count($this->taxa)*100, 2)."% ($galleries) are galleries rather than categories. ";
            echo "The galleries found so far contain ". count($this->gallery_files_to_check) ." media files.\n";
            self::print_memory_and_time();
        }
    }

    private function get_media_pages($xml)
    {
        $wanted = false;
        if(\WikimediaPage::fast_is_media($xml))
        {
            $page = new \WikimediaPage($xml);
            // check if this page has been listed in a gallery
            if(isset($this->gallery_files_to_check[$page->title]))
            {
                if(isset($page->redirect))
                {
                    // we won't catch redirects to pages earlier in the XML dump.
                    $multiple_galleries = count($this->gallery_files_to_check[$page->title])>1;
                    echo "Page '$page->title' listed in galler".($multiple_galleries?'ies':'y');
                    echo ' '.implode(", ", $this->gallery_files_to_check[$page->title])." has been redirected. ";
                    if (isset($this->taxonomies_for_media_file[$page->redirect])) {
                        echo "We have already processed the redirected page ('$page->redirect'), so for this page only ";
                        echo ($multiple_galleries?'these taxonomies have':'this taxonomy has')." been ignored.\n";
                    } else {
                        if (isset($this->gallery_files_to_check[$page->redirect])) {
                            $this->gallery_files_to_check[$page->redirect] = array_merge($this->gallery_files_to_check[$page->redirect], $this->gallery_files_to_check[$page->title]);
                            echo "Added these galler".($multiple_galleries?'ies':'y');
                            echo " to the redirected page ('$page->redirect').\n";
                        } else {
                            $this->gallery_files_to_check[$page->redirect] = $this->gallery_files_to_check[$page->title];
                            echo "Looking for the redirected page ('$page->redirect')  instead.\n";
                        };
                    };
                }else
                {
                    $page->add_galleries($this->gallery_files_to_check[$page->title]);
                    $wanted = true;
                }
                unset($this->gallery_files_to_check[$page->title]);
            }

            if(!$wanted)
            {
                // check if the page has an associated "taxonomic category"
                // done on every file in the dump: be careful not to trigger off a remote API call
                foreach($page->get_categories() as $cat)
                {
                    if(isset($this->taxa["Category:$cat"]))
                    {
                        // just flag this as wanted. We'll search for proper categories later
                        $wanted = true;
                        break;
                    }
                }
            }
            if($wanted) $this->queue_page_for_processing($page);
        }

        static $count = 0;
        $count++;
        if(($count % 100000 == 0) || $count == $this->total_pages_in_dump)
        {
            echo "Page: $count (final pass";
            if ($this->total_pages_in_dump) echo ": ".round($count/$this->total_pages_in_dump*100, 1)."% done";
            echo "# media files checked so far: ".count($this->taxonomies_for_media_file).", of which ";
            echo (array_sum($this->taxonomies_for_media_file)/count($this->taxonomies_for_media_file)-1)*100;
            echo "% have multiple taxa.";
            self::print_memory_and_time();
        }
    }

    private function queue_page_for_processing($page)
    {
        if(!$page) return;
        //if we want only to download recently changed wikimedia files, we could look at 
        //whether the last run date of this script is more recent that either strtodate($page->timestamp), 
        //or $this->taxa[$this->gallery_files_to_check[$page->title]]->last_taxonomy_change
        // But since we are currently checking categories via the call returned from the API,
        // we can't check the recent mod time of a categorised media file without an API call.
        $this->queue_of_pages_to_process[] = $page;
        // when the queue is large enough, process it
        if(count($this->queue_of_pages_to_process) >= \WikimediaPage::$max_titles_per_lookup)
        {
            $this->process_page_queue();
        }
    }

    private function process_page_queue()
    {
        \WikimediaPage::process_pages_using_API($this->queue_of_pages_to_process);
        foreach($this->queue_of_pages_to_process as $page)
        {
            // page may have multiple taxonomies: e.g. from gallery "Mus musculus", category "Mus musculus", category "Mus", etc.
            $taxonomies = $page->get_galleries();
            
            // only look for categories gleaned from the API (more reliable)
            $categories_from_API = $page->get_categories(true);
            if(count($categories_from_API) == 0)
            {
                echo "ERROR. This shouldn't happen. No categories at all for $page->title (have you failed to connect to the Wikimedia API?)\n";
            }else
            {
                $potential_license_categories = array();
                $map = false;
                foreach($categories_from_API as $cat)
                {
                    if(isset($this->taxa["Category:$cat"]))
                    {
                        $taxonomies[] = "Category:$cat";
                    }elseif(isset($this->map_categories[$cat]))
                    {
                        $map = true;
                    }
                    // neither a taxonomic category, nor a map category, so maybe its a license category
                    else $potential_license_categories[] = $cat;
                }

                if($map) $page->set_additionalInformation("<subtype>Map</subtype>");
                if($potential_license_categories) $page->reassess_licenses_with_additions($potential_license_categories);
            }
            if(!$page->has_license())
            {
                echo "No valid license category for $page->title (Categories: ".implode("|", $categories_from_API) .")\n";
                // continue;
            }
            if(!$page->has_valid_mime_type())
            {
                $params = $page->get_data_object_parameters();
                echo "No valid mime_type category for $page->title (". @$params['mimeType'] .")\n";
                continue;
            }

            if(empty($taxonomies))
            {
                echo "That's odd: no valid taxonomies for $page->title . Perhaps the categories via the API have changed since the XML dump (dump: ". implode("|", $page->categories_from_wikitext) .", API: ". implode("|", $categories_from_API) .")\n";
            }else
            {
                $mesg = self::remove_duplicate_taxonomies($taxonomies);
                if (!empty($mesg)) echo $mesg."in wikimedia page <$page->title>\n";
                if ($GLOBALS['ENV_DEBUG'] && (count($taxonomies) > 1))
                    echo "Multiple taxonomies in <$page->title>: '".implode("', '", $taxonomies)."'.\n";
                $this->taxonomies_for_media_file[$page->title] = count($taxonomies);

                $data_object_parameters = $page->get_data_object_parameters();
                foreach($taxonomies as $taxonomy) {
                    $this->add_to_resource_file($this->taxa[$taxonomy]->asEoLtaxonObject(), $data_object_parameters);
                }
            }
        }

        $this->queue_of_pages_to_process = array();
    }

    function remove_duplicate_taxonomies(&$names) {
        $return_message="";
        foreach(array_keys($names) as $focal_key) {
            foreach(array_keys($names) as $compare_key) {
                if ($focal_key != $compare_key) {
                    $focal_taxon = $names[$focal_key];
                    $compare_taxon = $names[$compare_key];                    
                    if ($this->taxa[$focal_taxon]->identical_taxonomy_to($this->taxa[$compare_taxon])) {
                        //if identical, pick the one with an "authority"
                        if (empty($this->taxa[$focal_taxon]->authority) xor empty($this->taxa[$focal_taxon]->authority)) {
                            //one has an authority, the other doesn't
                            if (empty($this->taxa[$focal_taxon]->authority)) {
                                if($GLOBALS['ENV_DEBUG'])
                                    $return_message .= "deleting ".$focal_taxon." which an identical taxonomy to ".$compare_taxon." but no authority field, ";
                                unset($names[$focal_key]);
                            break;
                            }
                        } elseif(!$this->taxa[$focal_taxon]->page_younger_than($this->taxa[$compare_taxon])) {
                            //both or neither have authorities, so pick the most recently changed page
                            if($GLOBALS['ENV_DEBUG'])
                                $return_message .= "deleting ".$focal_taxon." which is identical to, but isn't any younger than ".$compare_taxon.", ";
                            unset($names[$focal_key]);
                            break;
                        }
                    } elseif ($this->taxa[$focal_taxon]->is_nested_in($this->taxa[$compare_taxon])) {
                        //remove any that are simply parents (e.g. remove 'Homo' if we also have 'Homo sapiens')
                        if($GLOBALS['ENV_DEBUG']) 
                            $return_message .= "deleting ".$focal_taxon." which is a subset of ".$compare_taxon.", ";
                        unset($names[$focal_key]);
                        break;
                    } elseif ($this->taxa[$focal_taxon]->overlaps_without_conflict($this->taxa[$compare_taxon])) {
                        //the taxonomies are compatible, but have some complementary information
                        if (($this->taxa[$compare_taxon]->number_of_levels() > 2) && 
                            ($this->taxa[$focal_taxon]->is_less_precise_than($this->taxa[$compare_taxon]))) {
                            if($GLOBALS['ENV_DEBUG'])
                                $return_message .= "deleting ".$focal_taxon." which (while it contains some additional information) is less precise a classification than ".$compare_taxon.", ";
                            unset($names[$focal_key]);
                            break;
                        }
                    }
                }
            }
        }
        return $return_message;    
    }

    private function check_for_unaccounted_galleries()
    {
        {
            $good_files = array();
            echo count($this->gallery_files_to_check) ." gallery files remaining at end. Checking them out:";
            $titles = array_chunk(array_keys($this->gallery_files_to_check), \WikimediaPage::$max_titles_per_lookup, true);
            foreach($titles as $batch)
            {
                $good_files += \WikimediaPage::check_page_titles($batch);
            }
            if(count($good_files))
            {
                echo "\n\nMISSED THE FOLLOWING ". count($good_files) ." FILES";
                echo " (if you have the scanned whole XML dump, these may be redirected pages, listed in a gallery under";
                echo " the deprecated name, and which additionally have not been placed in a valid taxonomic category.";
                echo "  To harvest these files and remove this error message, you should probably edit the gallery file";
                echo " on wikimedia commons so that it uses the current, rather than deprecated, filename.)\n";
                
                foreach($good_files as $title => $json)
                {
                    echo "* $title in galler".(count($this->gallery_files_to_check[$title])>1?'ies':'y');
                    echo " <".implode(", ", $this->gallery_files_to_check[$title]).">\n";
                }
            }
        }
    }

    private static function get_map_categories($base_directory_path, $contact_sites=true)
    {
        // Try to get latest list of map categories. It's hard to use the MediaWiki API to recursively descend categories
        // but there are 2 online tools which can do it. Try both of these, and if it fails, just use a previously saved version
        // (using an old version should be no problem, as we don't expect many changes to this category structure)

        $mapcats = array();
        echo "Looking for map categories...\n";
        if ($contact_sites) $mapcats = self::get_all_child_categories("Distributional maps of organisms");
        if(count($mapcats) > 1) // will always have the base category present
        {
            // overwrite previous
            @rename($base_directory_path."MapCategories.txt", $base_directory_path."MapCategories_previous.txt");
            file_put_contents($base_directory_path."MapCategories.txt", implode("\n",array_keys($mapcats)));
            return $mapcats;
        }else
        {
            echo "Didn't download new list of map categories: using old version.\n";
            $mapcats = file($base_directory_path."MapCategories.txt", FILE_IGNORE_NEW_LINES);
            return(array_fill_keys($mapcats, 1));
        }
    }
    
    private static function get_all_child_categories($base_category, $depth=null)
    {
        $sites = array( "toolserver" => "http://toolserver.org/~daniel/WikiSense/CategoryIntersect.php?wikifam=commons.wikimedia.org&basedeep=100&mode=cl&go=Scan&format=csv&userlang=en&basecat=",
                        "wmflabs" => "http://tools.wmflabs.org/catscan2/quick_intersection.php?lang=commons&project=wikimedia&ns=14&depth=-1&max=30000&start=0&format=json&sparse=1&cats=");

        $cats = array($base_category => 1);
        if(count($cats) <= 1)
        {
            $url = $sites["toolserver"].urlencode($base_category);
            $tab_separated_string = Functions::get_remote_file_fake_browser($url, array('download_wait_time' => DOWNLOAD_WAIT_TIME*10, 'timeout' => DOWNLOAD_TIMEOUT_SECONDS*10));
            if(isset($tab_separated_string) && !preg_match("/^[^\r\n]*Database Error/i",$tab_separated_string))
            {
                foreach(preg_split("/(\r?\n)|(\n?\r)/", $tab_separated_string, null, PREG_SPLIT_NO_EMPTY) as $line)
                {
                    //  Category name is after first tab
                    $name = preg_replace("/_/u", " ", preg_replace("/^[^\t]*\t([^\t]*).*$/u", "$1", $line));
                    $cats[$name] = 1;
                }
                echo "Got ".count($cats)." categories from toolserver ($url)\n";
            }else echo "Couldn't get categories from toolserver ($url)\n";
        }

        if(count($cats) <= 1)
        {
            $url = $sites["wmflabs"].urlencode($base_category);
            $json = @json_decode(Functions::get_remote_file($url));
            if(isset($json) && isset($json->pages))
            {
                foreach($json->pages as $cat)
                {
                    $name = preg_replace("/_/u", " ", preg_replace("/^Category:/u", "", $cat));
                    $cats[$name] = 1;
                }
                echo "Got ".count($cats)." categories from wmflabs ($url)\n";
            }else echo "Couldn't get categories from wmflabs ($url)\n";
        }
        
        return $cats;
    }
    
    private function add_to_resource_file($taxon_data, $data_object_parameters)
    {
        if(isset($data_object_parameters['mediaURL']))
        {
            $taxon_data['dataObjects'][] = new \SchemaDataObject($data_object_parameters);
            $taxon = new \SchemaTaxon($taxon_data);
            $this->resource_file->save_taxon_xml($taxon);
        }
    }

    public static function print_memory_and_time()
    {
        echo "Memory: ". memory_get_usage_in_mb() ." MB\n";
        echo "Time  : ". round(time_elapsed(), 2) ." s\n\n\n";
    }
}

?>
