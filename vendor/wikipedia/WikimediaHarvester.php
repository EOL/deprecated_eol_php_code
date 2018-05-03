<?php
namespace php_active_record;

class WikimediaHarvester
{
    private $mysqli;
    private $resource_file;
    private $resource;

    function __construct($resource)
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->validMIMEtypes = array('image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/tiff', 'application/ogg', 'audio/ogg', 'video/ogg');
        $this->resource = $resource;
        // allows for splitting the file into max 26^n parts, e.g. n=3, parts=300Mb, copes with xml files < 4.2Tb
        $this->part_file_suffix_chars = 3;
        $this->number_of_separate_taxa = 0;
        $this->taxa = array();
        $this->taxonav_includes = array();
        $this->galleries_for_file = array();  // key=media-filename, value = array of gallery names (usually just one name)
        $this->taxonomies_for_file = array(); // key=media-filename, value = count of taxonomies for this file (just for info)
        $this->taxonomy_pagenames = array();  // key=media-filename, value = array of redirects (used as temp name store)
        $this->map_categories = null;
        // TODO - add blacklist of "unwanted" categories, so that if an image falls into one of these (or a child thereof),
        // it is not harvested. E.g. a suggested "unwanted" category might be
        // Category:Uploaded_with_Open_Access_Media_Importer_and_needing_category_review
        $this->total_pages_in_dump = 0;
        $this->queue_of_pages_to_process = array();
        
        /* //Eli added these two lines this when trying to revive this connector
        if(!isset($this->resource->id)) $this->resource->id = 80;
        if(!isset($this->resource->accesspoint_url)) $this->resource->accesspoint_url = "http://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles.xml.bz2";
        */
        
        $this->resource_file_path = CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id . "_temp.xml";
    }

    public function begin_wikimedia_harvest($files_subdir, $download=true)
    {
        exit("\nNot being used anymore\n\n");
        $base_directory_path = DOC_ROOT . $files_subdir . DIRECTORY_SEPARATOR;
        $part_files = array('base' => $base_directory_path, 'subdir' => 'wikimedia', 'prefix' => 'part_');
        $part_path = implode(DIRECTORY_SEPARATOR, $part_files);

        $this->resource_file = new \SchemaDocument($this->resource_file_path);

        //Get categories that tag an image as a distribution map.
        $this->map_categories = self::get_map_categories($base_directory_path);

        //set encoding for the xml dump file - important for things like WikiParser::mb_ucfirst
        mb_internal_encoding("UTF-8");

        if ($download)
        {
            // delete then re-download the huge wikimedia commons dump files
            $this->cleanup_dump($base_directory_path, $part_path); //Eli commented this when trying to revive this connector
            $this->download_dump($base_directory_path, $part_path);
        }

        // FIRST PASS: parse TaxonavigationIncluded* pages (e.g. https://commons.wikimedia.org/wiki/Template:Aves)
        // simultaneously locate galleries and categories with potential taxonomic information (i.e. a Taxonavigation template)
        // also make a list of "gallery media" (files listed in taxonomic galleries)
        $this->iterate_files(array($this, 'locate_taxonomic_pages'), $part_path);

        // SECOND PASS: parse & validate taxonomic information in galleries + categories - place in 'taxa' array
        // simutaneously link to 'taxa' any pages which redirect to these taxonomic pages
        // also if any gallery media are redirects, replace their name with the proper (redirected) name.
        $this->iterate_files(array($this, 'check_taxonomy_and_redirects'), $part_path);

        // FINAL PASS: check files for categories, grab file information for scientific media pages and save to file
        $this->iterate_files(array($this, 'get_media_pages'), $part_path);

        echo "\n(processing last ". count($this->queue_of_pages_to_process) ." media)\n";
        $this->process_page_queue();
        echo "\n\nTOTAL # OF MEDIA FILES: ".count($this->taxonomies_for_file)." (in ". count($this->taxa) ." taxa)\n";
        $this->check_for_unaccounted_galleries();

        @unlink(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_previous.xml");
        @rename(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id.".xml", CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_previous.xml");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id.".xml");

        echo "\nEND\n";
        self::print_memory_and_time();
    }

    private function download_dump($dir, $part_path)
    {
        // download latest Wikimedia Commons export
        echo "Downloading ".$this->resource->accesspoint_url." ...\n";
        shell_exec("curl ".escapeshellarg($this->resource->accesspoint_url)." -o ". escapeshellarg($dir . "wikimedia.xml.bz2")); //Eli commented this when trying to revive this connector
        // unzip the download
        echo "... unpacking downloaded file ...\n";
        shell_exec("bunzip2 --force ". escapeshellarg($dir . "wikimedia.xml.bz2"));
        // split the huge file into 300M chunks
        echo "... splitting file into parts ...\n";
        shell_exec("split -a ". $this->part_file_suffix_chars ." -b 300m ". escapeshellarg($dir . "wikimedia.xml")." ". escapeshellarg($part_path));
        echo "... done.\n";
    }

    private function cleanup_dump($dir, $part_path)
    {
        // cleaning up downloaded files
        echo "Removing old wikimedia dump files ...\n";
        shell_exec("rm -f ". escapeshellarg($part_path)."*");
        shell_exec("rm -f ". escapeshellarg($dir . "wikimedia.xml"));
        shell_exec("rm -f ". escapeshellarg($dir . "wikimedia.xml.bz2"));
        echo "... done.\n";
    }

    private function iterate_files($callback, $part_files)
    {
        $total_pages_processed = 0;
        $this->page_iteration_left_overs = "";
        $suffix = str_repeat('a', $this->part_file_suffix_chars);
        while((strlen($suffix) == $this->part_file_suffix_chars))
        {
            $filename = $part_files . $suffix;
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
        if($this->total_pages_in_dump == 0) $this->total_pages_in_dump = $total_pages_processed;
        echo "\n---------------------(done processing $total_pages_processed pages)---------------------\n\n\n";
    }

    private function process_file($filename, $callback)
    {
        $pages_processed = 0;
        if(file_exists($filename))
        {
            echo "Processing file '". basename($filename) ."' with callback ". $callback[1] ."\n";
            self::print_memory_and_time();

            // the last file ended in the middle of a page. Use the remainder of the last file as a starting point
            if(isset($this->page_iteration_left_overs)) $current_page = $this->page_iteration_left_overs;
            else $current_page = "";
            $this->page_iteration_left_overs = "";
            foreach(new FileIterator($filename, false, false) as $line)
            {
                $current_page .= $line;
                // this is a new page so reset $current_page
                if(trim($line) === "<page>") $current_page = $line;
                elseif(trim($line) === "</page>")
                {
                    call_user_func($callback, $current_page);
                    $pages_processed++;
                    $current_page = "";
                }
            }
            // in the middle of a <page> tag. Save the current page to use as a starting point for the next file
            $this->page_iteration_left_overs = $current_page;
        }
        return $pages_processed;
    }

    function locate_taxonomic_pages($xml)
    {
        $this->debug_locate_taxonomic_pages();
        // make sure we don't include cases with {{Taxonavigation in the comments field, etc.
        if(!\WikimediaPage::fast_is_gallery_category_or_template($xml)) return false;
        if(!($text_start = strpos($xml, "<text"))) return false;
        if(strpos($xml, "{{Taxonavigation", $text_start) === false) return false;
        $page = new \WikimediaPage($xml);

        if($page->is_template())
        {
            // should check here for template redirects
            if($page->contains_template("TaxonavigationIncluded[\w\s]*"))
            {
                $include_array = $page->taxonav_as_array("[Tt]axonavigationIncluded[\w\s]*");
                if(count($include_array))
                {
                    $this->taxonav_includes[$page->title] = array('taxo' => $include_array, 'last_mod' => @strtotime($page->timestamp));
                }else echo "$page->title is not a real TaxonavigationInclude* template\n";
            }elseif($page->contains_template("Taxonavigation"))
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
            if(!$page->contains_template("Taxonavigation")) return false;
            $this->taxonomy_pagenames[$page->title] = array();
            if(!$page->is_gallery()) return false;
            foreach($page->media_on_page() as $file)
            {
                $this->galleries_for_file["File:".\WikiParser::make_valid_pagetitle($file)][] = $page->title;
            }
        }
    }


    function check_taxonomy_and_redirects($xml)
    {
        $this->debug_check_taxonomy_and_redirects();
        // Phew, we don't need to worry about multiple redirects: http://en.wikipedia.org/wiki/Wikipedia:Double_redirects
        if(!\WikimediaPage::fast_is_gallery_category_or_media($xml)) return false;
        $page = new \WikimediaPage($xml);
        if(isset($page->redirect))
        {
            // must be a media file
            if(isset($this->galleries_for_file[$page->title]))
            {
                // need to use the filename to which the redirect is pointing, not the old name.
                if(isset($this->galleries_for_file[$page->redirect]))
                {
                    $this->galleries_for_file[$page->redirect] = array_merge($this->galleries_for_file[$page->redirect], $this->galleries_for_file[$page->title]);
                }else
                {
                    $this->galleries_for_file[$page->redirect] = $this->galleries_for_file[$page->title];
                }
                unset($this->galleries_for_file[$page->title]);
            // a category or gallery going by another name.
            }elseif(isset($this->taxonomy_pagenames[$page->redirect]))
            {
               if(isset($this->taxa[$page->redirect]))
               {
                   // we've already parsed the taxonomic page, so simply duplicate the info
                   $this->taxa[$page->title] = &$this->taxa[$page->redirect];
               }else
               {
                   // not parsed it yet: set it up so that when we encounter the other name, we know to duplicate it
                   $this->taxonomy_pagenames[$page->redirect][] = $page->title;
               }
            }
        }elseif(isset($this->taxonomy_pagenames[$page->title]))
        {
            // parse the taxo page: we can pass in "taxonav_includes" to avoid lots of API calls
            if(($params = $page->taxonomy($this->taxonav_includes)) && $params->scientificName())
            {
                $this->taxa[$page->title] = $params;
                $this->number_of_separate_taxa++;
                foreach($this->taxonomy_pagenames[$page->title] as $duplicate_page)
                {
                    if(isset($this->taxa[$duplicate_page])) echo("ERROR: taxonomy already set for <$duplicate_page>!\n");
                    $this->taxa[$duplicate_page] = &$this->taxa[$page->title];
                }
            }else echo "Couldn't get sensible taxonomy from <$page->title>.\n";
        } else {
            // catch pages which directly transclude a taxonomic category
            foreach($page->transcluded_categories() as $potential_category) {
                if (array_key_exists($potential_category, $this->taxonomy_pagenames)) {
                    //we may or may not have yet parsed the category page which is transcluded here
                    if(isset($this->taxa[$potential_category])) {
                        // we've already parsed the taxonomic page, so simply duplicate the info
                        $this->taxa[$page->title] = &$this->taxa[$potential_category];
                    } else
                    {
                        // not parsed it yet: set it up so that when we encounter the other name, we know to duplicate it
                        $this->taxonomy_pagenames[$potential_category][] = $page->title;
                    }
                }
            }
        }
    }

    private function get_media_pages($xml)
    {
        $this->debug_get_media_pages();
        if(!\WikimediaPage::fast_is_media($xml)) return false;
        $wanted = false;
        $page = new \WikimediaPage($xml);
        // check if this page has been listed in a gallery
        if(isset($this->galleries_for_file[$page->title]))
        {
            if(isset($page->redirect))
            {
                // This shouldn't happen!
                $multiple_galleries = count($this->galleries_for_file[$page->title]) > 1;
                echo "ERROR: page '$page->title' listed in galler".($multiple_galleries?'ies':'y');
                echo ' '. implode(", ", $this->galleries_for_file[$page->title]) ." still has redirect problems.\n";
            }else
            {
                // take care: some galleries may not have validated as proper taxa in check_redirects_and_taxonomy()
                // make sure we filter these out, otherwise we'll end up trying to access non-existing taxonomies
                $taxonomies = array_filter($this->galleries_for_file[$page->title], array($this, 'is_taxa_array_object'));
                if(count($taxonomies))
                {
                    $page->add_galleries($taxonomies);
                    $wanted = true;
                }
            }
            unset($this->galleries_for_file[$page->title]);
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

    private function queue_page_for_processing($page)
    {
        if(!$page) return;
        /* NB: if we want only to download recently changed wikimedia files, we could look at
           whether the last run date of this script is more recent that either strtodate($page->timestamp),
           or $this->taxa[$this->galleries_for_file[$page->title]]->last_taxonomy_change
           But since we are currently checking categories via the call returned from the API,
           we can't check the recent mod time of a categorised media file without an API call. */
           
        // We must process the queue either when number of pages is the maximum allowed by the API, or when the titles
        // make the total URL string too long (the latter only happens very rarely, when the av title length > ~160 chars)
        static $title_length=0;
        $title_length += strlen(urlencode($page->title."|"));
        // process the queue first if it is already hit the limits
        if((count($this->queue_of_pages_to_process) >= \WikimediaPage::$max_titles_per_lookup) ||
           ($title_length > \WikimediaPage::max_encoded_characters_in_titles()))
        {
            $this->process_page_queue();
            $title_length = strlen(urlencode($page->title."|"));
        }
        // now just add to the end of the queue
        $this->queue_of_pages_to_process[] = $page;
    }

    private function process_page_queue()
    {
        \WikimediaPage::process_pages_using_API($this->queue_of_pages_to_process);
        foreach($this->queue_of_pages_to_process as $page)
        {
            // page may have multiple taxonomies: e.g. from gallery "Mus musculus", categories "Mus musculus", "Mus", etc.
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
                    if($this->is_taxa_array_object("Category:$cat")) $taxonomies[] = "Category:$cat";
                    elseif(isset($this->map_categories[$cat])) $map = true;
                    // neither a taxonomic category, nor a map category, so maybe its a license category
                    else $potential_license_categories[] = $cat;
                }
                if($map) {
                    $page->set_additionalInformation("<subtype>Map</subtype>");
                    $page->reset_role_to_default();
                }
                if($potential_license_categories) $page->reassess_licenses_with_additions($potential_license_categories);
            }
            if(!$page->has_license())
            {
                echo "No valid license category for $page->title (Categories: ".implode("|", $categories_from_API) .")\n";
                //NB: as of 2013, about 4000 images don't have valid categories found: ignore these.
                // See discussion at http://eol.org/forums/4/topics/62
                continue;
            }
            if(!$page->has_valid_mime_type($this->validMIMEtypes))
            {
                $params = $page->get_data_object_parameters();
                echo "No valid mime_type category for $page->title (". @$params['mimeType'] .")\n";
                continue;
            }
            if(empty($taxonomies))
            {
                echo "No valid taxonomies for <$page->title>.";
                echo " Perhaps a gallery or category is specified in an invalid manner in the XML dump?";
                echo " Alternatively, the categories via the API may have changed since the creation of the XML dump.";
                echo " Galleries:". implode("|", $page->get_galleries()) .".";
                echo " Dump categories:". implode("|", $page->categories_from_wikitext). ".";
                echo " API categories:". implode("|", $categories_from_API) .".\n";
            }else
            {
                $mesg = self::remove_duplicate_taxonomies($taxonomies);
                if($GLOBALS['ENV_DEBUG'])
                {
                    if (!empty($mesg)) echo \WikiParser::mb_ucfirst($mesg)."in wikimedia page <$page->title>\n";
                    if (count($taxonomies) > 1) echo "Multiple taxonomies in <$page->title>: '".implode("', '", $taxonomies)."'.\n";
                }
                $this->taxonomies_for_file[$page->title] = count($taxonomies);
                $data_object_parameters = $page->get_data_object_parameters();
                foreach($taxonomies as $taxonomy)
                {
                    $this->add_to_resource_file($this->taxa[$taxonomy]->asEoLtaxonObject(), $data_object_parameters);
                }
            }
        }
        $this->queue_of_pages_to_process = array();
    }

    function remove_duplicate_taxonomies(&$names)
    {
        $return_message = "";
        foreach(array_keys($names) as $focal_key)
        {
            foreach(array_keys($names) as $compare_key)
            {
                if($focal_key != $compare_key)
                {
                    $focal_taxon = $names[$focal_key];
                    $compare_taxon = $names[$compare_key];
                    if($this->taxa[$focal_taxon]->identical_taxonomy_to($this->taxa[$compare_taxon]))
                    {
                        // if identical, pick the one with an "authority"
                        if(empty($this->taxa[$focal_taxon]->authority) xor empty($this->taxa[$compare_taxon]->authority))
                        {
                            // one has an authority, the other doesn't
                            if(empty($this->taxa[$focal_taxon]->authority))
                            {
                                $return_message .= "deleting ".$focal_taxon." which is an identical taxonomy to ".$compare_taxon." but has no authority field, ";
                                unset($names[$focal_key]);
                                break;
                            }
                        }elseif(!$this->taxa[$focal_taxon]->page_younger_than($this->taxa[$compare_taxon]))
                        {
                            // both or neither have authorities, so pick the most recently changed page
                            $return_message .= "deleting ".$focal_taxon." which is identical to, but isn't any younger than ".$compare_taxon.", ";
                            unset($names[$focal_key]);
                            break;
                        }
                    }elseif($this->taxa[$focal_taxon]->is_nested_in($this->taxa[$compare_taxon]))
                    {
                        // remove any that are simply parents (e.g. remove 'Homo' if we also have 'Homo sapiens')
                        $return_message .= "deleting ".$focal_taxon." which is a subset of ".$compare_taxon.", ";
                        unset($names[$focal_key]);
                        break;
                    }elseif($this->taxa[$focal_taxon]->overlaps_without_conflict($this->taxa[$compare_taxon]))
                    {
                        // the taxonomies are compatible, but have some complementary information
                        if(($this->taxa[$compare_taxon]->number_of_levels() > 2) &&
                            ($this->taxa[$focal_taxon]->is_less_precise_than($this->taxa[$compare_taxon])))
                        {
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
        $good_files = array();
        echo "\n".count($this->galleries_for_file) ." gallery files remain at the end (often misspellings in the gallery text). These are:\n";
        foreach ($this->galleries_for_file as $title => $galleries) 
            echo "  <$title> in galler".(count($this->galleries_for_file[$title])>1?'ies':'y')." <".implode(", ", $galleries).">\n";
        echo "Checking these out now...\n";
        $titles = array_chunk(array_keys($this->galleries_for_file), \WikimediaPage::$max_titles_per_lookup, true);
        foreach($titles as $batch)
        {
            $good_files += \WikimediaPage::check_page_titles($batch);
        }
        if(count($good_files))
        {
            echo "\nTHE FOLLOWING ". count($good_files) ." FILES COULD BE HARVESTED, BUT HAVE BEEN OVERLOOKED";
            foreach($good_files as $title => $json)
                echo "* <$title>\n";
        }
    }

    private static function get_map_categories($save_to_dir, $contact_sites = true)
    {
        // Try to get latest list of map categories. It's hard to use the MediaWiki API to recursively descend categories
        // but there are 2 online tools which can do it. Try both of these, and if it fails, just use a previously saved version
        // (using an old version should be no problem, as we don't expect many changes to this category structure)
        $mapcats = array();
        echo "Looking for map categories...\n";
        if($contact_sites) $mapcats = self::get_all_child_categories("Distributional maps of organisms");
        if(count($mapcats) > 1) // will always have the base category present
        {
            // overwrite previous
            @rename($save_to_dir."MapCategories.txt", $save_to_dir."MapCategories_previous.txt");
            file_put_contents($save_to_dir."MapCategories.txt", implode("\n",array_keys($mapcats)));
            return $mapcats;
        }else
        {
            echo "Didn't download new list of map categories: using old version.\n";
            $mapcats = file($save_to_dir."MapCategories.txt", FILE_IGNORE_NEW_LINES);
            return(array_fill_keys($mapcats, 1));
        }
    }

    private static function get_all_child_categories($base_category, $depth = null)
    {
        $sites = array( "toolserver" => "http://toolserver.org/~daniel/WikiSense/CategoryIntersect.php?wikifam=commons.wikimedia.org&basedeep=100&mode=cl&go=Scan&format=csv&userlang=en&basecat=",
                        "wmflabs" => "http://tools.wmflabs.org/quick-intersection/index.php?lang=commons&project=wikimedia&ns=14&depth=-1&max=30000&start=0&format=json&sparse=1&cats=");
        $cats = array($base_category => 1);
        // Using toolserver.org seems to only return max ~x500 categories, so its use has been commented out below
/*        if(count($cats) <= 1)
        {
            $url = $sites["toolserver"].urlencode($base_category);
            $tab_separated_string = Functions::get_remote_file_fake_browser($url, array('download_wait_time' => DOWNLOAD_WAIT_TIME*10, 'timeout' => DOWNLOAD_TIMEOUT_SECONDS*10));
            if(isset($tab_separated_string) && !preg_match("/^[^\r\n]*Database Error/i",$tab_separated_string))
            {
                foreach(preg_split("/(\r?\n)|(\n?\r)/", $tab_separated_string, null, PREG_SPLIT_NO_EMPTY) as $line)
                {
                    // Category name is after first tab
                    $name = preg_replace("/_/u", " ", preg_replace("/^[^\t]*\t([^\t]*).*$/u", "$1", $line));
                    $cats[$name] = 1;
                }
                echo "Got ".count($cats)." categories from toolserver ($url)\n";
            }else echo "Couldn't get categories from toolserver ($url)\n";
        }
*/
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

    private function debug_locate_taxonomic_pages()
    {
        static $count = 0;
        $count++;
        if(($count % 100000 == 0) || $count == $this->total_pages_in_dump)
        {
            echo "Page: $count (first pass";
            if($this->total_pages_in_dump) echo ": ".round($count/$this->total_pages_in_dump*100, 1)."% done";
            echo "). # TaxonavigationIncluded files so far: ". count($this->taxonav_includes);
            echo ". # potential taxa so far: ". count($this->taxonomy_pagenames).", of which ";
            $galleries = count(array_unique(call_user_func_array('array_merge', $this->galleries_for_file)));
            echo round($galleries/count($this->taxonomy_pagenames)*100, 1)."% ($galleries) are galleries rather than categories. ";
            echo "The galleries found so far contain ". count($this->galleries_for_file) ." media files.\n";
            self::print_memory_and_time();
        }
    }

    private function debug_check_taxonomy_and_redirects()
    {
        static $count = 0;
        $count++;
        if(($count % 100000 == 0) || $count == $this->total_pages_in_dump)
        {
            echo "Page: $count (second pass";
            if($this->total_pages_in_dump) echo ": ".round($count/$this->total_pages_in_dump*100, 1)."% done";
            echo "). # parsed taxa so far: ". $this->number_of_separate_taxa ." (". count($this->taxa);
            echo " including duplicated redirects), out of a potential total of ".count($this->taxonomy_pagenames).".\n";
            self::print_memory_and_time();
        }
    }

    private function debug_get_media_pages()
    {
        static $count = 0;
        $count++;
        if(($count % 100000 == 0) || $count == $this->total_pages_in_dump)
        {
            echo "Page: $count (final pass";
            if($this->total_pages_in_dump) echo ": ".round($count/$this->total_pages_in_dump*100, 1)."% done";
            echo "). # media files checked so far: ".count($this->taxonomies_for_file);
            if(count($this->taxonomies_for_file))
            {
                $multiple = count(array_filter($this->taxonomies_for_file, function ($a) { return $a > 1; }));
                echo ", of which ".($multiple/count($this->taxonomies_for_file)*100)."% ($multiple) have multiple taxa";
            }
            echo ".\n";
            self::print_memory_and_time();
        }
    }

    public static function print_memory_and_time()
    {
        echo "Memory: ". memory_get_usage_in_mb() ." MB\n";
        echo "Time  : ". round(time_elapsed(), 2) ." s\n\n";
    }

    private function is_taxa_array_object($name)
    {
        return is_object(@$this->taxa[$name]);
    }
}

?>
