<?php
namespace php_active_record;
/* connector: [eol_api.php]
This script uses the different means to access the EOL API.
Can be used for OpenData's customized subsets.
*/

class EolAPI
{
    const DL_MAP_SPECIES_LIST   = "http://www.discoverlife.org/export/species_map.txt";
    
    function __construct($folder = null, $query = null)
    {
        /* add: 'resource_id' => "eol_api" ;if you want to add the cache inside a folder [eol_api] inside [eol_cache] */
        $this->download_options = array(
            'resource_id'        => 'eol_api',  //resource_id here is just a folder name in cache
            'expire_seconds'     => false,      //always false since EOL V2 won't change anymore
            'download_wait_time' => 500000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache/";      //used in Functions.php for all general cache


        $this->api['Pages'] = "http://eol.org/api/pages/1.0.json?batch=false&images_per_page=75&images_page=1&videos_per_page=75&videos_page=1&sounds_per_page=75&sounds_page=1&maps_per_page=75&maps_page=1&texts_per_page=75&texts_page=1&iucn=false&subjects=overview&licenses=all&details=true&common_names=true&synonyms=true&references=true&taxonomy=true&vetted=0&cache_ttl=&language=en&id=";
        $this->api['DataObjects'][0] = "http://eol.org/api/data_objects/1.0/";
        $this->api['DataObjects'][1] = ".json?taxonomy=true&cache_ttl=";
        // e.g. http://eol.org/api/data_objects/1.0/19173106.json?taxonomy=true&cache_ttl=
        // max of 7 simultaneous api calls, still works OK
        //=============================
        $this->path  = '/Volumes/Thunderbolt4/OpenData/eol_data-2016-12-08/';
        $this->path2 = '/Volumes/Thunderbolt4/OpenData/eol_data-2017-01-04/';
        
        $this->opendata['tsv']['data_objects']                          = $this->path  . 'data_objects.tsv';
        $this->opendata['tsv']['data_objects_taxon_concepts']           = $this->path  . 'data_objects_taxon_concepts.tsv';
        $this->opendata['tsv']['data_objects_additional_attribution']   = $this->path  . 'data_objects_additional_attribution.tsv';
        $this->opendata['tsv']['hierarchy_entries']                     = $this->path2 . 'hierarchy_entries.tsv';
        
        $this->opendata['headers']['data_objects']     = self::set_field_names("data_object_id, guid, data_type, object_url, further_information_url, title, first_200_characters_of_body, original_size_url, toc_item_label, license_name, rights_statement, rights_holder, location");
        $this->opendata['headers_txt']['data_objects'] = self::set_field_names("guid, data_type, object_url, further_information_url, title, first_200_characters_of_body, original_size_url, toc_item_label, license_name, rights_statement, rights_holder, location");
        // print "data_objects\n"; print_r($this->opendata['headers']['data_objects']); //exit;

        $this->opendata['headers']['data_objects_taxon_concepts']     = self::set_field_names("data_object_id, guid, taxon_concept_id, visibility, vetted");
        $this->opendata['headers_txt']['data_objects_taxon_concepts'] = self::set_field_names("data_object_id, guid, visibility, vetted");
        // echo "data_objects_taxon_concepts\n"; print_r($this->opendata['headers']['data_objects_taxon_concepts']);
        
        $this->opendata['headers']['data_objects_additional_attribution'] = self::set_field_names("data_object_id, role_label, agent_name, agent_url");
        $this->opendata['headers_txt']['data_objects_additional_attribution'] = self::set_field_names("role_label, agent_name, agent_url");
        // print "data_objects_additional_attribution\n"; print_r($this->opendata['headers']['data_objects_additional_attribution']); 

        $this->opendata['headers']['hierarchy_entries'] = self::set_field_names("taxon_concept_id, hieararchy_entry_id, entry_is_preferred_for_concept, name_string, he_identifier"); //ver 1
        $this->opendata['headers']['hierarchy_entries'] = self::set_field_names("hieararchy_entry_id, taxon_concept_id, hierarchy_id, parent_id, resource_primary_key, entry_is_preferred_for_concept, scientific_name, original_size_url");
        // print "hierarchy_entries\n"; print_r($this->opendata['headers']['hierarchy_entries']); 
        // exit;
        
        // for creating archives
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->do = array();
        $this->agent_ids = array();
    }
    
    private function set_field_names($str)
    {
        $temp = explode(",", $str);
        return array_map("trim", $temp);
    }

    private function get_do_via_api($do_id)
    {
        $rec = array();
        if($objects = self::get_objects($do_id))
        {
            $do = $objects['dataObjects'][0];
            // print_r($do);
            /* from api */
            /* from TSV
            [guid] => "3a666fed60cee9eb6ded909538e806ae"
            [data_type] => "Image"
            [object_url] => "http://www.biolib.cz/IMG/GAL/106882.jpg"
            [further_information_url] => "http://www.biolib.cz/en/image/id106882/"
            [title] => ""
            [first_200_characters_of_body] => ""
            [original_size_url] => "http://media.eol.org/content/2010/03/24/04/60285_orig.jpg"
            [toc_item_label] => 
            [license_name] => "cc-by-nc 3.0"
            [rights_statement] => ""
            [rights_holder] => "Tamara Horová"
            [location] => "Czech Republic, Útěchov u Brna"
            */
            $rec = array(
                "guid"                          => @$do['identifier'],
                "data_type"                     => self::format_datatype_from_api2tsv(@$do['dataType']),
                "object_url"                    => @$do['mediaURL'],
                "further_information_url"       => @$do['source'],
                "title"                         => @$do['title'],
                "first_200_characters_of_body"  => @$do['description'],
                "original_size_url"             => @$do['eolMediaURL'],
                "toc_item_label"                => "",
                "license_name"                  => self::format_license_from_api2tsv(@$do['license']),
                "rights_statement"              => "",
                "rights_holder"                 => @$do['rightsHolder'],
                "location"                      => @$do['location']
            );
        }
        return $rec;
    }
    
    
    function start()
    {
        /* new func
        $str = "http://purl.org/dc/dcmitype/MovingImage"; $str = "http://purl.org/dc/dcmitype/Text"; echo self::format_datatype_from_api2tsv($str); exit;
        $str = "http://creativecommons.org/licenses/by/2.0/"; $str = "https://creativecommons.org/licenses/by-nc-sa/4.0"; echo self::format_license_from_api2tsv($str); exit;
        */
        
        /* new func
        $do_id = "29933553"; //no taxon
        $do_id = "16794735"; //with taxon
        $do_id = "30073527"; //with many data
        $rec = self::get_do_via_api($do_id); 
        print_r($rec); exit;
        */
        
        //---------------------------------------------------------------------------------------------------------------------------------------------
        // /*
        if(Functions::is_production()) $path = "/extra/eol_php_code_public_tmp/google_maps/taxon_concept_names.tab";
        else                           $path = "/Volumes/AKiTiO4/z backup/eol_php_code_public_tmp/google_maps/taxon_concept_names.tab";
        self::process_all_eol_taxa($path); return;                    //make use of tab-delimited text file from JRice
        // */
        //---------------------------------------------------------------------------------------------------------------------------------------------
        /* has not run yet in eol-archive Jenkins
        $doc = "http://localhost/eol_php_code/public/tmp/spreadsheets/SPG Hotlist Official Version.xlsx";
        self::process_hotlist_spreadsheet($doc); return;             //make use of hot list spreadsheet from SPG
        */
        //---------------------------------------------------------------------------------------------------------------------------------------------

        // self::process_DL_taxon_list(); return;                   //make use of taxon list from DiscoverLife
        
        // self::process_tsv_file($this->opendata['tsv']['data_objects'], $this->opendata['headers']['data_objects']);
        // self::process_tsv_file($this->opendata['tsv']['data_objects_taxon_concepts'], $this->opendata['headers']['data_objects_taxon_concepts']);
        // self::process_tsv_file($this->opendata['tsv']['data_objects_additional_attribution'], $this->opendata['headers']['data_objects_additional_attribution']);
        // self::process_tsv_file($this->opendata['tsv']['hierarchy_entries'], $this->opendata['headers']['hierarchy_entries']);

        /*
        $scinames = array();                                        //make use of manual taxon list
        $scinames["baby Isaiah"] = 1;
        // $scinames["Chanos chanos"] = 224731;
        // $scinames["Gadus morhua"] = 206692;
        foreach($scinames as $sciname => $taxon_concept_id) self::main_loop($sciname, $taxon_concept_id);
        */
        
        /* API result:
        [offset]        => 0
        [limit]         => 20
        [endOfRecords]  => 
        [count]         => 78842
        [results]       => Array
        */
    }
    
    private function process_tsv_file($tsv_fle, $fields)
    {
        $debug = array();
        $i = 0; $total = 0;
        $do_add_att = array();
        
        $j = 0; //count of 'true' taxa
        foreach(new FileIterator($tsv_fle) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            if(!$line) continue;
            $orig_line = $line;

            // $line = str_replace('\r\n', "", $line); //for data_objects -- no point as the tsv file has \n in its column values...

            //start formatting $line --- data_objects_additional_attribution
            $line = str_ireplace(" „sachara“", " sachara", $line);
            $line = str_replace('/\\ \\/\\/ /\\', '', $line);
            
            $line = str_replace('\N', "", $line);
            $line = str_replace('\"', "'", $line);
            
            $line = str_replace(array('“', '”', '„', '“'), '', $line);
            
            // $line = str_replace('""', '"', $line); -- seems erroneous to do this
            
            // --- hierarchy_entries
            // manually removed 15 instances of "\" as line terminator in the hierarchy_entries.txv file
            // manually removed \\ and ” e.g. in hierarchy_entries -> 212281	60545823	"false"	"Carcharhinus obscurus\\"	""
            
            if(preg_match_all("/\"(.*?)\"/ims", $line, $arr))
            {
                foreach($arr[1] as $str)
                {
                    $orig_str = $str;
                    $str = str_replace(array("\t"), " ", $str); //remove \t in values
                    $line = str_replace($orig_str, $str, $line);
                }
            }
            
            $row_str = $line;
            $line = explode("\t", $line);
            
            $i++;
            if(($i % 100000) == 0) echo number_format($i) . " ";
            if(($i % 50000) == 0) echo number_format($i) . " ";
            
            
            //==================
            /*
            $m = 100000000;
            $cont = false;
            if($i >=  10000000    && $i < $m)    $cont = true;
            // if($i >=  $m   && $i < $m*2)  $cont = true;
            if(!$cont) continue;
            */
            //==================
            
            
            if(count($line) != count($fields))
            {
                // /*
                // continue;
                // echo "\n-----------------start";
                // print_r($line); print_r($fields);
                // echo "\n count line: "     . count($line);
                // echo "\n count fields: "   . count($fields);
                // echo "\n fixed: $row_str";
                // echo "\n first_200: ".strlen(@$row['first_200_characters_of_body']);
                // echo "\n orig: $orig_line";
                // echo("\n-----------------end");
                // exit;
                continue;
                // */
            }
            $total++;
            
            
            $k = 0; 
            $row = array();
            foreach($fields as $field)
            {
                @$row[$field] = $line[$k];
                // if($field == 'data_type') $debug[@$line[$k]] = ''; //working only on debug

                /*
                //data_objects_taxon_concepts
                if($field == "visibility") $debug['visibility'][@$line[$k]] = ''; //working only on debug
                if($field == "vetted")     $debug['vetted'][@$line[$k]] = '';     //working only on debug
                */
                
                $k++;
            }
            $row = array_map('trim', $row);
            // print_r($row); 
            
            // echo $row['data_type']. "\n";
            
            //=========================================================================================================================================================
            /*
                [0] => data_object_id
                [1] => guid
                [2] => data_type
                [3] => object_url
                [4] => further_information_url
                [5] => title
                [6] => first_200_characters_of_body
                [7] => original_size_url
                [8] => toc_item_label
                [9] => license_name
                [10] => rights_statement
                [11] => rights_holder
                [12] => location
            */
            // /*
            if(stripos($tsv_fle, 'data_objects.tsv') !== false) //string is found
            {
                /* fatal error
                $rec = array();
                $this->do[$row['data_object_id']] = array('guid' => $row['guid'], 'data_type' => $row['data_type'], 'object_url' => $row['object_url'], 'further_information_url' => $row['further_information_url'], 
                'title' => $row['title'], 'first2h' => $row['first_200_characters_of_body'], 
                'original_size_url' => $row['original_size_url'], 'toc_item_label' => $row['toc_item_label'], 'license_name' => $row['license_name'], 'rights_statement' => $row['rights_statement'], 
                'rights_holder' => $row['rights_holder'], 'location' => $row['location']);
                */
                
                /*
                if($row['data_type'] == '"Text"' && strlen($row['first_200_characters_of_body']) >= 199)
                {
                    self::get_objects($row['data_object_id']); //cache dataObjects with >= 199 descriptions
                    // echo "\n-----------------";
                    // print_r($line); 
                    // echo "\n$row_str\n";
                    // echo strlen($row['first_200_characters_of_body']);
                    // exit("\n-----------------");
                }
                */
                
                // /*
                $m = 3000000;
                $cont = false;
                // if($i >=  1,287,872     && $i < $m)    $cont = true;
                // if($i >=  3,543,076    && $i < $m*2)  $cont = true;
                // if($i >=  6,824,609  && $i < $m*3)  $cont = true;
                if(!$cont) continue;
                // */
                
                // /* another process to gen temp files
                if($do_id = $row['data_object_id'])
                {
                    $rec = array('guid' => $row['guid'], 'data_type' => $row['data_type'], 'object_url' => $row['object_url'], 'further_information_url' => $row['further_information_url'], 
                                 'title' => $row['title'], 'first_200' => $row['first_200_characters_of_body'], 
                                 'original_size_url' => $row['original_size_url'], 'toc_item_label' => $row['toc_item_label'], 'license_name' => $row['license_name'], 
                                 'rights_statement' => $row['rights_statement'], 'rights_holder' => $row['rights_holder'], 'location' => $row['location']);
                    self::create_or_skip_file('data_objects', $do_id, $rec);
                }
                // if($total > 70) break; //end loop - debug
                // */
                
            }
            // */
            
            //=========================================================================================================================================================
            /* data_objects_taxon_concepts
            [visibility] => Array
                    [] => 
                    ["Visible"] => 
                    ["Invisible"] => 
                    ["Preview"] => 
            [vetted] => Array
                    [] => 
                    ["Trusted"] => 
                    ["Inappropriate"] => 
                    ["Untrusted"] => 
                    ["Unknown"] => 
            */
            if(stripos($tsv_fle, 'data_objects_taxon_concepts.tsv') !== false) //string is found
            {
                /*
                [data_object_id] => 12
                [guid] => "e0a3ee0080dcba934c90f793b3b417e9"
                [taxon_concept_id] => 
                [visibility] => 
                [vetted] => 
                */
                echo number_format($i) . " ";
                // /*
                $m = 3000000;
                $cont = false;
                // if($i >=  1909086  && $i < $m)    $cont = true;
                // if($i >=  4977912    && $i < $m*2)  $cont = true; DONE
                // if($i >=  8523128  && $i < $m*3)  $cont = true;
                if($i >=  2900000  && $i < $m*3)  $cont = true;
                
                if(!$cont) continue;
                // */
                
                // /*----------
                //this process is to just cache API calls
                if($taxon_concept_id = $row['taxon_concept_id'])
                {
                    if($row['visibility'] == '"Visible"' && $row['vetted'] == '"Trusted"')
                    {
                        self::api_using_tc_id($taxon_concept_id);
                        print_r($row);
                    }
                }
                // ----------*/
                
                /*----------
                //another process here is to generate txt files
                if($taxon_concept_id = $row['taxon_concept_id']) 
                {
                    $rec = array('data_object_id' => $row['data_object_id'], 'guid' => $row['guid'], 'visibility' => $row['visibility'], 'vetted' => $row['vetted']);
                    // self::create_or_append_file('data_objects_taxon_concepts', $taxon_concept_id, $rec);
                }
                ----------*/
            }
            //=========================================================================================================================================================
            if(stripos($tsv_fle, 'data_objects_additional_attribution.tsv') !== false) //string is found
            {   // single data_object_id can have multiple records
                /*
                (
                    [data_object_id] => 4946187
                    [role_label] => "Source"
                    [agent_name] => "Annotated Checklist of the Flowering Plants of Nepal @ eFloras.org"
                    [agent_url] => "http://www.efloras.org/flora_page.aspx?flora_id=110"
                )
                */
                
                /* done Jan 10, 2017
                if($do_id = $row['data_object_id'])
                {
                    $rec = array('role_label' => $row['role_label'], 'agent_name' => $row['agent_name'], 'agent_url' => $row['agent_url']);
                    // self::create_or_append_file('data_objects_additional_attribution', $do_id, $rec);
                }
                */
            }
            //=========================================================================================================================================================
            /*
            (   [hieararchy_entry_id] => 20584782
                [taxon_concept_id] => 1119595
                [hierarchy_id] => 115
                [parent_id] => 20584778
                [resource_primary_key] => "242422289"
                [entry_is_preferred_for_concept] => "false"
                [scientific_name] => "Eriocaulon quinquangulare L."
                [original_size_url] => "http://media.eol.org/content/2014/05/25/17/19300_orig.jpg"
            )
            */
            if(stripos($tsv_fle, 'hierarchy_entries.tsv') !== false) //string is found
            {
                // echo number_format($i) . " ";
                // /*
                $m = 3250000;
                $cont = false;

                // if($i >=  4,540,517  && $i < 5000000)    $cont = true;   //5 million
                // if($i >=  5,587,337  && $i < 6000000)    $cont = true;   //6 500 000
                // if($i >=  6,018,760  && $i < 6500000)    $cont = true;   
                // if($i >=  8,656,898  && $i < 9000000)    $cont = true;   //9 mil
                // if($i >=  9,613,213  && $i < $m*3)       $cont = true;   //9 750 000
                // if($i >=  11,330,257 && $i < 12000000)   $cont = true;    //12000000
                if($i >=  12067033 && $i < $m*4)       $cont = true;     //13,000,000

                if(!$cont) continue;
                // */
                
                // if($row['entry_is_preferred_for_concept'] == '"true"')
                if(true)
                {
                    if($taxon_concept_id = $row['taxon_concept_id'])
                    {
                        /* for generating archive
                        // if($j == 0) $row['taxon_concept_id'] = 1034599;     //debug
                        // if($j == 1) $row['taxon_concept_id'] = 46323194;    //debug
                        // if($j == 2) $row['taxon_concept_id'] = 133027;      //debug
                        $j++;
                        self::generate_archive_taxon($row);
                        */
                        
                        // /* // just caching...
                        self::api_using_tc_id($taxon_concept_id); print_r($row);
                        echo " - " . number_format($i) . " "; //just for logging
                        // */
                    }
                }
                // if($j > 0) break; //end loop - debug
            }
            //=========================================================================================================================================================
            // exit;
        }//end loop

        // if(stripos($tsv_fle, 'hierarchy_entries.tsv') !== false) $this->archive_builder->finalize(true); //string is found

        print_r($debug);
        /*
        (
            ["Image"] => 
            ["Video"] => 
            ["Sound"] => 
        )                                                           filtered    JRice           script, not filtered
        ----- data_objects                          total records: [8,459,244]  [8,462,944]     [8,468,882]
        
        ----- data_objects_taxon_concepts:          total records: [8,524,425]  [8,524,425]     [8,524,425]
                                                                   [8,524,425]                  [8,524,425]
                                                                   
        ----- data_objects_additional_attribution:  total records: [9,338,320]  [9,343,687]     [9,343,687]
                                                                   [9,343,686]                  [9,343,687]
                                                                   9,343,686
                                                                   
        ----- hierarchy_entries                     total records: [12,564,069] [-no figure-]   [12,564,072]
                                                                   [12,564,072]                 [12,564,072]
                                                                   [12,564,072] [12,564,087]    [12,564,102]
        */
        print_r($do_add_att);
        echo "\ntotal records $tsv_fle: [$total]\n";
        
        
        /* when generating archive
        print_r($this->do);
        print_r($this->agent_ids);
        */
    }
    
    private function process_all_eol_taxa($path, $listOnly = false)
    {
        if($listOnly) $list = array();
        $i = 0;
        foreach(new FileIterator($path) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            $line = explode("\t", $line);
            $taxon_concept_id = $line[0];
            $sciname          = Functions::canonical_form($line[1]);
            if($listOnly) {
                $list[$sciname] = $taxon_concept_id;
                continue;
            }
            $i++;

            // if(stripos($sciname, " ") !== false) //only species-level taxa
            if(true) //all taxa
            {
                //==================
                // /*
                $m = 75000;
                $cont = false;
                // if($i >=  1    && $i < $m)    $cont = true; done
                // if($i >=  $m   && $i < $m*2)  $cont = true; done
                
                if($i >=  400000 && $i < 520000)  $cont = true;

                if(!$cont) continue;
                // */
                //==================
                
                if(($i % 100) == 0) echo "\n".number_format($i).". [$sciname][tc_id = $taxon_concept_id]";
                self::api_using_tc_id($taxon_concept_id);
                
            }
            // else echo "\n[$sciname] will pass higher-level taxa at this time...\n";
        }//end loop
        if($listOnly) return $list;
    }
    
    private function api_using_tc_id($taxon_concept_id)
    {
        if($json = Functions::lookup_with_cache($this->api['Pages'].$taxon_concept_id, $this->download_options)) {
            $arr = json_decode($json, true);
            $objects = $arr['dataObjects'];
            echo " - " . count($objects);
            return; //debug
            foreach($objects as $o) {
                echo "\n" . $o['dataObjectVersionID'];
                if($o['dataType'] == "http://purl.org/dc/dcmitype/Text" && strlen($o['description']) >= 199) //cache if desc is long since in tsv descs are substring of 200 chars only
                {
                    $objects = self::get_objects($o['dataObjectVersionID']);
                    foreach($objects['dataObjects'] as $o) echo " - " . @$o['mimeType'];
                    // print_r($objects);
                }
            }
        }
    }
    
    private function get_objects($data_object_id)
    {
        $call = $this->api['DataObjects'][0].$data_object_id.$this->api['DataObjects'][1];
        if($json = Functions::lookup_with_cache($call, $this->download_options)) return json_decode($json, true);
        return false;
    }

    private function create_or_append_file($folder, $id, $rec)
    {
        $path = self::generate_path($folder, $id);
        $filename = $path . "/$id.txt";
        // echo "\n$filename";
        $WRITE = fopen($filename, "a");
        fwrite($WRITE, implode("\t", $rec) . "\n");
        fclose($WRITE);
    }

    private function create_or_skip_file($folder, $id, $rec)
    {
        $path = self::generate_path($folder, $id);
        $filename = $path . "/$id.txt";
        if(!file_exists($filename))
        {
            // echo "\n[$filename] generated";
            $WRITE = fopen($filename, "a");
            fwrite($WRITE, implode("\t", $rec) . "\n");
            fclose($WRITE);
        }
        // else echo "\nalready generated [$filename]";
    }

    private function generate_path($folder, $id)
    {
        $md5 = md5($id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($this->path."/$folder/" . $cache1))              mkdir($this->path."/$folder/" . $cache1);
        if(!file_exists($this->path."/$folder/" . "$cache1/$cache2"))    mkdir($this->path."/$folder/" . "$cache1/$cache2");
        return $this->path."/$folder/" . "$cache1/$cache2";
    }

    private function generate_archive_taxon($rec)
    {
        /*
        (   [hieararchy_entry_id] => 20584782
            [taxon_concept_id] => 1119595
            [hierarchy_id] => 115
            [parent_id] => 20584778
            [resource_primary_key] => "242422289"
            [entry_is_preferred_for_concept] => "false"
            [scientific_name] => "Eriocaulon quinquangulare L."
            [original_size_url] => "http://media.eol.org/content/2014/05/25/17/19300_orig.jpg"
        )
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxon_concept_id'];
        $taxon->parentNameUsageID       = $rec['parent_id'];
        $taxon->scientificName          = self::unquote($rec['scientific_name']);
        $taxon->furtherInformationURL   = 'http://eol.org/pages/'.$taxon->taxonID;
        // if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
        $this->archive_builder->write_object_to_file($taxon);
        self::process_data_objects($taxon->taxonID);
    }

    private function generate_archive_do($do, $taxonID, $do_id)
    {
        /*
        [guid] => "3a666fed60cee9eb6ded909538e806ae"
        [data_type] => "Image"
        [object_url] => "http://www.biolib.cz/IMG/GAL/106882.jpg"
        [further_information_url] => "http://www.biolib.cz/en/image/id106882/"
        [title] => ""
        [first_200_characters_of_body] => ""
        [original_size_url] => "http://media.eol.org/content/2010/03/24/04/60285_orig.jpg"
        [toc_item_label] => 
        [license_name] => "cc-by-nc 3.0"
        [rights_statement] => ""
        [rights_holder] => "Tamara Horová"
        [location] => "Czech Republic, Útěchov u Brna"
        */
        // not used
        // [object_url] => "http://www.biolib.cz/IMG/GAL/106882.jpg"
        // [toc_item_label] => 
        // [guid]
        
        // print_r($do);
        
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID                = $taxonID;
        $mr->identifier             = $do_id; //or [guid]
        $mr->type                   = $do['data_type'];
        $mr->language               = '';
        $mr->format                 = '';
        $mr->accessURI              = $do['original_size_url'];
        $mr->thumbnailURL           = '';
        $mr->CVterm                 = '';
        $mr->Owner                  = $do['rights_holder'];
        $mr->rights                 = $do['rights_statement'];
        $mr->title                  = $do['title'];
        $mr->UsageTerms             = $do['license_name'];
        $mr->description            = $do['first_200_characters_of_body'];
        $mr->LocationCreated        = $do['location'];
        $mr->furtherInformationURL  = $do['further_information_url'];
        
        // $mr->bibliographicCitation = $o['dcterms_bibliographicCitation'];
        // $mr->audience       = 'Everyone';
        // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);

        if($agent_ids = self::process_agents($do_id)) $mr->agentID = implode("; ", $agent_ids);
        $this->archive_builder->write_object_to_file($mr);
    }
    
    private function process_agents($do_id)
    {
        $agent_ids = array();
        if($text_file = self::get_text_file_for_id('data_objects_additional_attribution', $do_id))
        {
            if($agents = self::get_rows_from_text_file($text_file))
            {
                foreach($agents as $agent)
                {
                    $agent = self::un_quote_all($agent);
                    $agent = self::add_headers($agent, 'data_objects_additional_attribution');
                    /*
                    (
                        [role_label] => Compiler
                        [agent_name] => John Pickering
                        [agent_url] => http://www.discoverlife.org/
                    )
                    */
                    // print_r($agent);
                    if(!$agent['agent_name']) continue;
                    $r = new \eol_schema\Agent();
                    $r->term_name       = $agent['agent_name'];
                    $r->agentRole       = $agent['role_label'];
                    $r->identifier      = md5("$r->term_name|$r->agentRole");
                    $r->term_homepage   = $agent['agent_url'];
                    $agent_ids[] = $r->identifier;
                    if(!isset($this->agent_ids[$r->identifier]))
                    {
                       $this->agent_ids[$r->identifier] = ''; //$r->term_name;
                       $this->archive_builder->write_object_to_file($r);
                    }
                }
            }
        }
        // else echo "\nno agents for this object...\n";
        return $agent_ids;
    }
    
    private function generate_archive_agents($agents)
    {
    }
    
    private function process_data_objects($taxon_concept_id)
    {
        if($text_file = self::get_text_file_for_id('data_objects_taxon_concepts', $taxon_concept_id))
        {
            // echo "\n text file for [$taxon_concept_id]: $text_file\n";
            if($do_tcs = self::get_rows_from_text_file($text_file))
            {
                foreach($do_tcs as $do_tc)
                {
                    /* one rec $do_tc
                    (
                        [0] => 29949124
                        [1] => "e74719ed0aeafb2118fb33f7580c05dc"
                        [2] => "Visible"
                        [3] => "Unknown"
                    )
                    */
                    // $do_tc[0] = "5822414"; //debug only --- for testing...
                    
                    $do_tc = self::add_headers($do_tc, 'data_objects_taxon_concepts');
                    // print_r($do_tc);
                    if($do_id = $do_tc['data_object_id'])
                    {
                        if($text_file = self::get_text_file_for_id('data_objects', $do_id))
                        {
                            $do = self::get_rows_from_text_file($text_file);
                            $do = $do[0]; //since this is only 1 row
                            $do = self::un_quote_all($do);
                            $do = self::add_headers($do, 'data_objects');
                            self::generate_archive_do($do, $taxon_concept_id, $do_id);
                        }
                        else
                        {
                            echo "\n[$do_id] no obj text file yet...will use API\n";
                            //start using API to get data_object info
                            if($do = self::get_do_via_api($do_id)) 
                            {
                                echo " with API rec OK";
                                self::generate_archive_do($do, $taxon_concept_id, $do_id);
                            }
                        }
                    }
                }
            }
        }
    }

    private function add_headers($rec, $tbl)
    {
        $fields = $this->opendata['headers_txt'][$tbl];
        $k = 0;
        $final = array();
        foreach($rec as $r)
        {
            $final[$fields[$k]] = $r;
            $k++;
        }
        return $final;
    }
    
    private function get_text_file_for_id($folder, $id)
    {
        $md5 = md5($id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $file = $this->path."/$folder/" . "$cache1/$cache2/$id.txt";
        if(file_exists($file)) return $file;
        else return false;
    }
    
    private function get_rows_from_text_file($file)
    {
        $rows = array();
        foreach(new FileIterator($file) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            if($line) $rows[] = explode("\t", $line);
        }
        return $rows;
    }
    

    private function unquote($str)
    {
        return str_replace('"', "", $str);
    }
    
    private function un_quote_all($recs)
    {
        $final = array();
        foreach($recs as $rec) $final[] = self::unquote($rec);
        return $final;
    }
    
    private function format_license_from_api2tsv($str)
    {
        //http://creativecommons.org/licenses/by/2.0/   https://creativecommons.org/licenses/by-nc-sa/4.0
        $str = str_replace("https", "http", $str);
        $str = str_replace("http://creativecommons.org/licenses/", "cc-", $str);
        $str = str_replace("/", " ", $str);
        return trim($str);
    }
    
    private function format_datatype_from_api2tsv($str)
    {
        /*
        http://purl.org/dc/dcmitype/MovingImage http://purl.org/dc/dcmitype/Sound http://purl.org/dc/dcmitype/StillImage http://purl.org/dc/dcmitype/Text
        ["Image"] => ["Video"] => ["Sound"] =>
        */ 
        $str = str_replace('http://purl.org/dc/dcmitype/', "", $str);
        $str = str_replace("MovingImage", "Video", $str);
        $str = str_replace("StillImage", "Image", $str);
        return $str;
    }

    private function xxx()
    {
        $expire_seconds = false;

        /* debug
        if($sciname == "Falco chrysaetos") $expire_seconds = true;
        else                               $expire_seconds = false;
        */
        
        /*              http://eol.org/api/search/1.0.xml?q=Xanthopsar+flavus&page=1&exact=false&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl= */
        $search_call = "http://eol.org/api/search/1.0.xml?q=" . $sciname .  "&page=1&exact=false&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl=";
        if($xml = Functions::lookup_with_cache($search_call, array('timeout' => 30, 'expire_seconds' => $expire_seconds, 'resource_id' => 'eol_api'))) //resource_id here is just a folder name in cache
        {
            $xml = simplexml_load_string($xml);
            $sciname = Functions::canonical_form($sciname);
            if($sciname == Functions::canonical_form(@$xml->entry[0]->title)) return false; //sciname is not a synonym but accepted name
            else
            {
                $titles = array();
                foreach($xml->entry as $entry) $titles[] = Functions::canonical_form($entry->title);
                if(in_array($sciname, $titles)) return false; //sciname is not a synonym but accepted name
                else return true;
            }
        }
        return false;
    }

    private function main_loop($sciname, $taxon_concept_id = null)
    {
        self::api_using_tc_id($taxon_concept_id);
    }

    private function get_sciname($r)
    {
        // if($r->taxonRank == "SPECIES") return $r->species;
        return $r->scientificName;
    }

    private function process_hotlist_spreadsheet($doc)
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $families = array();
        echo "\n processing [$doc]...\n";
        if($path = Functions::save_remote_file_to_local($doc, array("timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2))) {
            $arr = $parser->convert_sheet_to_array($path);
            // print_r($arr); exit;
            $i = -1;
            foreach($arr['Animals'] as $sciname) {
                $i++;
                $sciname = trim(Functions::canonical_form($sciname));
                // if(stripos($sciname, " ") !== false) //process only species-level taxa
                if(true) {
                    $taxon_concept_id = $arr['1'][$i];
                    echo "\n$i. [$sciname][$taxon_concept_id]";
                    //==================
                    /*
                    $m = 10000;
                    $cont = false;
                    // if($i >=  1    && $i < $m)    $cont = true;
                    // if($i >=  $m   && $i < $m*2)  $cont = true;
                    // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                    // if($i >=  $m*3 && $i < $m*4)  $cont = true;
                    // if($i >=  $m*4 && $i < $m*5)  $cont = true;
                    // if($i >=  $m*5 && $i < $m*6)  $cont = true;
                    // if($i >=  $m*6 && $i < $m*7)  $cont = true;
                    if(!$cont) continue;
                    */
                    self::main_loop($sciname, $taxon_concept_id);
                    //==================
                    // break; //debug - process only 1
                }
            }
            unlink($path);
        }
        else echo "\n [$doc] unavailable! \n";
    }

    private function process_DL_taxon_list()
    {
        exit("\nThis will query name not taxon_concept_id. Program will stop now.\n");
        $temp_filepath = Functions::save_remote_file_to_local(self::DL_MAP_SPECIES_LIST, array('timeout' => 4800, 'download_attempts' => 5));
        if(!$temp_filepath)
        {
            echo "\n\nExternal file not available. Program will terminate.\n";
            return;
        }
        $i = 0;
        foreach(new FileIterator($temp_filepath, true) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            $i++;
            if($line)
            {
                /*
                $m = 10000;
                $cont = false;
                if($i >=  1    && $i < $m)    $cont = true;
                // if($i >=  $m   && $i < $m*2)  $cont = true;
                // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                // if($i >=  $m*3 && $i < $m*4)  $cont = true;
                // if($i >=  $m*4 && $i < $m*5)  $cont = true;
                if(!$cont) continue;
                */
                $arr = explode("\t", $line);
                $sciname = trim($arr[0]);
                echo "\n[$sciname]\n";
                self::main_loop($sciname);
            }
            // if($i >= 5) break; //debug
        }
    }

}
?>