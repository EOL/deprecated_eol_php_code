<?php
namespace php_active_record;
/* connector: [gbif_georeference_dwca.php]
This script parses the GBIF DwCA occurrence downloads, then creates the individual CSV file per taxon, then generates the map data (.json) using the taxonKey 
that is mapped to EOL's (sciname, taxonConceptID)

1. process the big GBIF occurrence file (DwCA)
1.1. loop through the verbatim.txt -> this has media/image information
1.2. save individual CSV file for each taxon (to be used in 2.3)

2. use taxa list (2-column text file - taxon_concept_id & scientific name) from EoL
2.1. loop through the taxa list
2.2. get taxonkey using scientific name
2.3. use taxonkey to get the occurrence in CSV file (CSV created in 4.2)

*/
class GBIFoccurrenceAPI_DwCA //this makes use of the GBIF DwCA occurrence downloads
{
    function __construct($folder = null, $query = null)
    {
        /* add: 'resource_id' => "gbif" ;if you want to add cache inside a folder [gbif] inside [eol_cache_gbif] */
        $this->download_options = array(
            'expire_seconds'     => 60*60*24*30*4, //4 months to expire
            'download_wait_time' => 1000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //debug | true -- expires now

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_cache_gbif/";
        else                           $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache_gbif/";

        //GBIF services
        $this->gbif_taxon_info      = "http://api.gbif.org/v1/species/match?name="; //http://api.gbif.org/v1/species/match?name=felidae&kingdom=Animalia
        $this->gbif_record_count    = "http://api.gbif.org/v1/occurrence/count?taxonKey=";
        $this->gbif_occurrence_data = "http://api.gbif.org/v1/occurrence/search?taxonKey=";
        
        $this->html['publisher']    = "http://www.gbif.org/publisher/";
        $this->html['dataset']      = "http://www.gbif.org/dataset/";
        
        if(Functions::is_production()) {
            $this->save_path['taxa_csv_path']     = "/extra/other_files/GBIF_occurrence/GBIF_taxa_csv_dwca/";
            $this->save_path['multimedia_gbifID'] = "/extra/other_files/GBIF_occurrence/multimedia_gbifID/";
            $this->save_path['map_data']          = "/extra/map_data_dwca/";
            $this->eol_taxon_concept_names_tab    = "/extra/eol_php_code_public_tmp/google_maps/taxon_concept_names.tab";
        }
        else {
            $this->save_path['taxa_csv_path']     = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_taxa_csv_dwca/";
            $this->save_path['multimedia_gbifID'] = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/multimedia_gbifID/";
            $this->save_path['map_data']          = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/map_data_dwca/";
            $this->eol_taxon_concept_names_tab    = "/Volumes/AKiTiO4/z backup/eol_php_code_public_tmp/google_maps old/taxon_concept_names.tab";
        }
        $this->csv_paths = array();
        $this->csv_paths[] = $this->save_path['taxa_csv_path'];
        

        $this->rec_limit = 100000; //50000;
        
        $this->limit_20k = 20000; //20000;
        $this->api['dataset'] = "http://api.gbif.org/v1/dataset/";
        $this->debug = array();
    }
    function start()
    {
        /* tests
        $datasetKey = "0e7bd6f7-7fc6-4150-a531-2209f7156a91";
        $datasetKey = "492d63a8-4978-4bc7-acd8-7d0e3ac0e744";
        $str = self::get_org_name('dataset', $datasetKey);
        echo "\ndataset: [$str]\n";
        $orgKey = self::get_dataset_field($datasetKey, 'publishingOrganizationKey');
        $dataset_name = self::get_dataset_field($datasetKey, 'title');
        echo "\norg key: [$orgKey]\n";
        echo "\ndataset name: [$dataset_name]\n";
        exit("\n-end tests-\n");
        */
        /* Steps (August 2018) using the DwCA occurrence downloads from GBIF */
        // Step 1. Delete all .json files
        // Step 2. 
            // self::breakdown_GBIF_DwCA_file(); echo "\nDONE: breakdown_GBIF_DwCA_file()\n";                              return; //IMPORTANT: this can only be run once every harvest
        // Step 3. 
            // self::breakdown_multimedia_to_gbifID_files();   echo "\nDONE: breakdown_multimedia_to_gbifID_files()\n";    return; //took 18 mins in eol-archive
        // Step 4. 
            self::generate_map_data_using_GBIF_csv_files(); echo "\nDONE: generate_map_data_using_GBIF_csv_files()\n";
            if($this->debug) Functions::start_print_debug($this->debug, "gen_map_data_via_gbif_csv");                   return;
        // Step 5. pick if there are taxa still without map data (.json), if yes, use API to get map data.
        
        //---------------------------------------------------------------------------------------------------------------------------------------------
        /*
        self::process_all_eol_taxa(false, false); return;   //make use of tab-delimited text file from JRice
        */
        //---------------------------------------------------------------------------------------------------------------------------------------------

        /* testing...
        $scinames = array(); //make use of manual taxon list
        $scinames["Gadus morhua"] = 206692;
        $scinames["Gadidae"] = 5503;
        $scinames["Animalia"] = 1;
        foreach($scinames as $sciname => $taxon_concept_id) self::main_loop($sciname, $taxon_concept_id);
        */
    }
    //##################################### start DwCA process ###########################################################################################################################
    private function breakdown_multimedia_to_gbifID_files()
    {
        $path2 = $this->save_path['multimedia_gbifID'];
        if(Functions::is_production()) {
            $paths[] = "/extra/other_files/GBIF_occurrence/DwCA_Other7Groups/multimedia.txt";
            $paths[] = "/extra/other_files/GBIF_occurrence/DwCA_Plantae/multimedia.txt";
            $paths[] = "/extra/other_files/GBIF_occurrence/DwCA_Animalia/multimedia.txt";
        }
        else $paths[] = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/occurrence_downloads/DwCA/Gadus morhua/multimedia.txt";
        foreach($paths as $path) {
            $i = 0;
            foreach(new FileIterator($path) as $line_number => $line) { // 'true' will auto delete temp_filepath
                $i++; if(($i % 10000) == 0) echo "\n [$path] ".number_format($i) . " ";
                if($i == 1) $line = strtolower($line);
                $row = explode("\t", $line);
                if($i == 1) {
                    $fields = $row;
                    continue;
                }
                else {
                    if(!@$row[0]) continue; //$row[0] is gbifID
                    $k = 0; $rec = array();
                    foreach($fields as $fld) {
                        $rec[$fld] = $row[$k];
                        $k++;
                    }
                }
                // print_r($rec); exit("\nstopx\n");
                /* Array(
                    [gbifid] => 1883941229
                    [type] => StillImage
                    [format] => image/jpeg
                    [identifier] => https://static.inaturalist.org/photos/21812110/original.jpeg?1532308417
                    [references] => https://www.inaturalist.org/photos/21812110
                    [title] => 
                    [description] => 
                    [created] => 2018-07-21T20:30Z
                    [creator] => mkkennedy
                    [contributor] => 
                    [publisher] => iNaturalist
                    [audience] => 
                    [source] => 
                    [license] => http://creativecommons.org/licenses/by-nc/4.0/
                    [rightsholder] => mkkennedy
                )*/
                $gbifid = $rec['gbifid'];
                if($rec['type'] == "StillImage" && $rec['format'] != "image/tiff" && $rec['identifier']) {
                    $path3 = self::get_md5_path($path2, $gbifid);
                    $txt_file = $path3 . $gbifid . ".txt";
                    if(!file_exists($txt_file)) { //take note: only one image is saved per gbifID
                        $fhandle = Functions::file_open($txt_file, "w");
                        fwrite($fhandle, $rec['identifier'] . "\n"); fclose($fhandle);
                    }
                }
            }//end loop text file
        }//end foreach($paths)
    }
    private function breakdown_GBIF_DwCA_file()
    {
        exit("\nFinished running Aug 23, 2018\n");
        $path2 = $this->save_path['taxa_csv_path'];
        if(Functions::is_production()) {
            // $paths[] = "/extra/other_files/GBIF_occurrence/DwCA_Animalia/occurrence.txt";        //~717 million - Took 3 days 15 hr (when API calls are not yet cached)
            // $paths[] = "/extra/other_files/GBIF_occurrence/DwCA_Plantae/occurrence.txt";         //~183 million - Took 1 day 19 hr (when API calls are not yet cached)
            // $paths[] = "/extra/other_files/GBIF_occurrence/DwCA_Other7Groups/occurrence.txt";    //~25 million - Took 5 hr 10 min (when API calls are not yet cached)
        }
        else {
            $paths[]  = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/occurrence_downloads/DwCA/Gadus morhua/occurrence.txt";
        }
        foreach($paths as $path) {
            $i = 0;
            foreach(new FileIterator($path) as $line_number => $line) { // 'true' will auto delete temp_filepath
                $i++;
                if(($i % 50000) == 0) echo number_format($i) . "[$path] ";
                if($i == 1) $line = strtolower($line);
                $row = explode("\t", $line);
                if($i == 1) {
                    $fields = $row; continue;
                }
                else {
                    if(!@$row[0]) continue; //$row[0] is gbifID
                    $k = 0; $rec = array();
                    foreach($fields as $fld) {
                        $rec[$fld] = $row[$k];
                        $k++;
                    }
                }
                // print_r($rec); exit("\nstopx\n");
                if(!@$rec['taxonkey']) continue;
                $taxonkey = $rec['taxonkey'];
                // echo "\n".$rec['datasetkey']."\n";
                $rec['publishingorgkey'] = self::get_dataset_field($rec['datasetkey'], 'publishingOrganizationKey');

                $rek = array($rec['gbifid'], $rec['datasetkey'], $rec['scientificname'], $rec['publishingorgkey'], $rec['decimallatitude'], $rec['decimallongitude'], $rec['eventdate'], 
                $rec['institutioncode'], $rec['catalognumber'], $rec['identifiedby'], $rec['recordedby']);
                if($rec['decimallatitude'] && $rec['decimallongitude']) {
                    $path3 = self::get_md5_path($path2, $taxonkey);
                    $csv_file = $path3 . $taxonkey . ".csv";
                    if(!file_exists($csv_file)) {
                        //order of fields here is IMPORTANT: will use it when accessing these generated individual taxon csv files
                        $str = 'gbifid,datasetkey,scientificname,publishingorgkey,decimallatitude,decimallongitude,eventdate,institutioncode,catalognumber,identifiedby,recordedby';
                        $fhandle = Functions::file_open($csv_file, "a");
                        fwrite($fhandle, implode("\t", explode(",", $str)) . "\n");
                        fclose($fhandle);
                    }
                    $fhandle = Functions::file_open($csv_file, "a");
                    fwrite($fhandle, implode("\t", $rek) . "\n");
                    fclose($fhandle);
                }
            } //end foreach()
        } //end loop paths
    }
    private function get_dataset_field($datasetKey, $return_field)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($datasetKey && $json = Functions::lookup_with_cache($this->api['dataset'].$datasetKey, $options)) {
            $obj = json_decode($json);
            if(!isset($obj->$return_field)) { //debug only
                print_r($obj);
                exit("\n[$datasetKey]: ".$this->api['dataset'].$datasetKey."\n");
            }
            return $obj->$return_field;
        }
        else return self::get_org_name('dataset', $datasetKey);
    }
    //##################################### end DwCA process #############################################################################################################################
    //==========================
    // start GBIF methods
    //==========================
    private function get_md5_path($path, $taxonkey)
    {
        $md5 = md5($taxonkey);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($path . $cache1)) mkdir($path . $cache1);
        if(!file_exists($path . "$cache1/$cache2")) mkdir($path . "$cache1/$cache2");
        return $path . "$cache1/$cache2/";
    }
    private function generate_map_data_using_GBIF_csv_files()
    {
        /* uncomment in real operation
        $eol_taxon_id_list = self::process_all_eol_taxa(false, true); //listOnly = true
        echo "\n eol_taxon_id_list total: ".count($eol_taxon_id_list)."\n";
        // exit("\nstopx\n");
        */
        
        // print_r($eol_taxon_id_list); echo "\n" . count($eol_taxon_id_list) . "\n"; return; //[Triticum aestivum virus] => 540152
        
        // $eol_taxon_id_list["Gadus morhua"] = 206692;
        // $eol_taxon_id_list["Achillea millefolium L."] = 45850244;
        // $eol_taxon_id_list["Francolinus levaillantoides"] = 1; //5227890
        // $eol_taxon_id_list["Phylloscopus trochilus"] = 2; //2493052
        // $eol_taxon_id_list["Anthriscus sylvestris (L.) Hoffm."] = 584996; //from Plantae group
        // $eol_taxon_id_list["Xenidae"] = 8965;
        // $eol_taxon_id_list["Soleidae"] = 5169;
        // $eol_taxon_id_list["Plantae"] = 281;
        // $eol_taxon_id_list["Chaetoceros"] = 12010;
        // $eol_taxon_id_list["Chenonetta"] = 104248;
        
        // /* for testing 1 taxon
        $eol_taxon_id_list = array();
        $eol_taxon_id_list["Gadus morhua"] = 206692;
        // $eol_taxon_id_list["Gadidae"] = 5503;
        // $eol_taxon_id_list["Hyperiidae"] = 1180;
        // $eol_taxon_id_list["Decapoda"] = 1183;
        // $eol_taxon_id_list["Proterebia keymaea"] = 137680; //csv map data not available from DwCA download
        // $eol_taxon_id_list["Aichi virus"] = 540501;
        // */

        $paths = $this->csv_paths;
        
        $i = 0;
        foreach($eol_taxon_id_list as $sciname => $taxon_concept_id) {
            $i++;
            // ==============================
            /*
            $m = 100000;
            $m = count($eol_taxon_id_list)/6;
            $cont = false;
            // if($i >=  1    && $i < $m)    $cont = true;
            // if($i >=  $m   && $i < $m*2)  $cont = true;
            if($i >=  $m*2 && $i < $m*3)  $cont = true;
            // if($i >=  $m*3 && $i < $m*4)  $cont = true;
            // if($i >=  $m*4 && $i < $m*5)  $cont = true;
            // if($i >=  $m*5 && $i < $m*6)  $cont = true;

            // if($i >=  1 && $i < 5) $cont = true;
            if(!$cont) continue;
            */
            // ==============================
            echo "\n$i. [$sciname][$taxon_concept_id]";
            if($usageKey = self::get_usage_key($sciname)) {
                echo "\nOK GBIF key [$usageKey]\n";
                if(self::map_data_file_already_been_generated($taxon_concept_id)) continue;
                
                if($final = self::prepare_csv_data($usageKey, $paths)) {
                    echo "\n Records from CSV: " . $final['count'] . "";
                    if($final['count'] > $this->limit_20k) {
                        echo " --- > 20K\n";
                        self::process_revised_cluster($final, $taxon_concept_id); //done after main demo using screenshots
                    }
                    elseif($final['count'] <= $this->limit_20k) {
                        echo " --- <= 20K\n";
                        $final['actual'] = $final['count'];
                        if(!($this->file = Functions::file_open(self::get_map_data_path($taxon_concept_id).$taxon_concept_id.".json", "w"))) return;
                        $json = json_encode($final, JSON_UNESCAPED_SLASHES);
                        fwrite($this->file, "var data = ".$json);
                        fclose($this->file);
                    }
                    else exit("\nShould not go here 001 [$sciname][$taxon_concept_id]\n");
                }
                else {
                    echo "\nCSV map data not available [$sciname][$taxon_concept_id]\n";
                    $this->debug['CSV map data not available']["[$sciname][$taxon_concept_id]"] = '';
                    self::gen_map_data_using_api($sciname, $taxon_concept_id);
                }
            }
            else {
                echo "\n usageKey not found! [$sciname][$taxon_concept_id]\n";
                $this->debug['usageKey not found']["[$sciname][$taxon_concept_id]"] = '';
            }
        } //end main foreach()
    }
    private function gen_map_data_using_api($sciname, $taxon_concept_id) //NEW Aug 24, 2018
    {
        echo "\nWill try to use API...\n";
        if($rec = self::get_initial_data($sciname)) {
            print_r($rec);
            self::get_georeference_data_via_api($rec['usageKey'], $taxon_concept_id);
        }
    }
    private function get_georeference_data_via_api($taxonKey, $basename) //updated from original version
    {
        $offset = 0; $limit = 300; $continue = true; $final = array(); echo "\n";
        $final['records'] = array();
        while($continue) {
            if($offset > $this->rec_limit) break; //working... uncomment if u want to limit to 100,000
            $url = $this->gbif_occurrence_data . $taxonKey . "&limit=$limit";
            if($offset) $url .= "&offset=$offset";
            if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                $j = json_decode($json);
                if(!is_object($j)) {
                    $offset += $limit;
                    continue;
                }
                $recs = self::write_to_file($j);
                $final['records'] = array_merge($final['records'], $recs);
                echo " increments: " . count($recs) . "";
                if($j->endOfRecords)                            $continue = false;
                if(count($final['records']) > $this->rec_limit) $continue = false; //limit no. of markers in Google maps is 100K //working... uncomment if u want to limit to 100,000
            }
            else break; //just try again next time...
            $offset += $limit;
        }
        $final['count']  = count($final['records']);
        $final['actual'] = count($final['records']);
        $final_count = $final['count'];
        echo "\nFinal count: " . $final_count . "\n";

        if($final_count > $this->limit_20k) {
            $final_count = self::process_revised_cluster($final, $basename); //done after main demo using screenshots
        }
        else {
            $json = json_encode($final, JSON_UNESCAPED_SLASHES);
            if(!($this->file = Functions::file_open(self::get_map_data_path($basename).$basename.".json", "w"))) return;
            fwrite($this->file, "var data = ".$json);
            fclose($this->file);
        }
    }
    private function get_map_data_path($taxon_concept_id)
    {
        $folder = $taxon_concept_id % 100;
        $path = $this->save_path['map_data']."/".$folder."/";
        if(!is_dir($path)) mkdir($path);
        return $path;
    }
    private function prepare_csv_data($usageKey, $paths)
    {
        $final = array();
        foreach($paths as $path) {
            $final_path = self::get_md5_path($path, $usageKey);
            $csv = $final_path . $usageKey . ".csv";
            if(file_exists($csv)) {
                echo "\nusageKey = [$usageKey] found in [$path]";
                $file_array = file($csv);
                $gbif_ids = array(); $i = 0;
                foreach($file_array as $line) {
                    $i++;
                    $row = explode("\t", $line);
                    if($i == 1) {
                        $fields = $row;
                        continue;
                    }
                    else {
                        if(!@$row[1]) continue;
                        $k = 0; $rek = array();
                        foreach($fields as $fld) {
                            $rek[$fld] = $row[$k];
                            $k++;
                        }
                        $rek = array_map('trim', $rek);
                        // print_r($rek); exit;
                    }
                    
                    //make record unique
                    $gbifid = $rek['gbifid'];
                    if(isset($gbif_ids[$gbifid])) continue;
                    else $gbif_ids[$gbifid] = '';
                    
                    $rec = array();
                    $rec['a']   = $rek['catalognumber'];
                    $rec['b']   = $rek['scientificname'];
                    $rec['c']   = self::get_org_name('publisher', @$rek['publishingorgkey']);
                    $rec['d']   = @$rek['publishingorgkey'];
                    if($val = @$rek['institutioncode']) $rec['c'] .= " ($val)";
                    $rec['e']   = self::get_dataset_field(@$rek['datasetkey'], 'title'); //self::get_org_name('dataset', @$rek['datasetkey']);
                    $rec['f']   = @$rek['datasetkey'];
                    $rec['g']   = $rek['gbifid'];
                    $rec['h']   = $rek['decimallatitude'];
                    $rec['i']   = $rek['decimallongitude'];
                    $rec['j']   = @$rek['recordedby'];
                    $rec['k']   = @$rek['identifiedby'];
                    $rec['l']   = self::get_media_by_gbifid($gbifid);
                    $rec['m']   = @$rek['eventdate'];
                    /* to have map data with only those with media
                    if($rec['l']) $final['records'][] = $rec;
                    */
                    $final['records'][] = $rec;
                }
                $final['count'] = count($final['records']);
            }
            else echo "\n[$usageKey] NOT found in [$path]";
        }
        return $final;
    }
    private function get_media_by_gbifid($gbifid)
    {
        $path = $this->save_path['multimedia_gbifID'];
        $final_path = self::get_md5_path($path, $gbifid);
        $txt_file = $final_path . $gbifid . ".txt";
        if(file_exists($txt_file)) {
            // echo "\nmedia found [$gbifid]\n";
            $str = file_get_contents($txt_file);
            return str_ireplace("\n", "", $str); //remove line separator
        }
        return '';
    }
    //==========================
    // end GBIF methods
    //==========================
    private function process_all_eol_taxa($path = false, $listOnly = false)
    {
        if(!$path) $path = $this->eol_taxon_concept_names_tab;
        if($listOnly) $list = array();
        $i = 0;
        foreach(new FileIterator($path) as $line_number => $line) { // 'true' will auto delete temp_filepath
            $line = explode("\t", $line);
            $taxon_concept_id = $line[0];
            $sciname          = Functions::canonical_form(@$line[1]);
            if($listOnly) {
                if($taxon_concept_id) $list[$sciname] = $taxon_concept_id;
                continue;
            }
            $i++;

            if($taxon_concept_id == 1) continue;
            // if(stripos($sciname, " ") !== false) //only species-level taxa
            if(true) { //all taxa
                echo "\n$i. [$sciname][tc_id = $taxon_concept_id]";
                //==================
                /*
                285. [Geraniaceae][tc_id = 285]
                [Geraniaceae]

                Total:[1212423]
                [4676] NOT found in [/Library/WebServer/Documents/eol_php_code//public/tmp/google_maps/GBIF_taxa_csv_animalia/]
                [4676] NOT found in [/Library/WebServer/Documents/eol_php_code//public/tmp/google_maps/GBIF_taxa_csv_incertae/]
                [4676] found in [/Library/WebServer/Documents/eol_php_code//public/tmp/google_maps/GBIF_taxa_csv_others/]
                 -- will use API as source 01 -- 411 > 50000 
                */
                /*
                $m = 100000;
                $cont = false;
                // if($i >=  1    && $i < $m)    $cont = true;
                // if($i >=  $m   && $i < $m*2)  $cont = true;
                // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                // if($i >=  $m*3 && $i < $m*4)  $cont = true;
                // if($i >=  $m*4 && $i < $m*5)  $cont = true;
                // if($i >=  $m*5 && $i < $m*6)  $cont = true;

                if($i >=  $m*4 && $i < $m*6)  $cont = true;

                if(!$cont) continue;
                */
                //==================
                self::main_loop($sciname, $taxon_concept_id); //uncomment in real operation...
                if($usageKey = self::get_usage_key($sciname)) echo " - OK [$usageKey]"; //used to cache all usageKey requests...
                else                                          echo " - usageKey not found!";
                exit("\n--stopx--\n");
            }
            // else echo "\n[$sciname] will pass higher-level taxa at this time...\n";
        }//end loop
        if($listOnly) return $list;
    }
    private function map_data_file_already_been_generated($basename)
    {
        // return false; //debug
        $filename = self::get_map_data_path($basename).$basename.".json";
        if(file_exists($filename)) {
            if(filesize($filename) > 0) {
                echo "[$basename] map data (.json) already generated OK [$filename]";
                return true;
            }
            else {
                $this->debug['json exists but zero length'][$basename] = '';
                unlink($filename);
            }
        }
        else return false;
    }
    private function main_loop($sciname, $taxon_concept_id = false)
    {
        $sciname = Functions::canonical_form($sciname); echo "\n[$sciname]\n";
        $basename = $sciname;
        if($val = $taxon_concept_id) $basename = $val;
        if(self::map_data_file_already_been_generated($basename)) return;
        $final_count = false;
        if($rec = self::get_initial_data($sciname)) {
            print_r($rec);
            // first is check the csv front ------------------------------------------------------------------------------------------
            if($final = self::prepare_csv_data($rec['usageKey'], $this->csv_paths)) {
                // print_r($final);
                if($final['count'] > $this->rec_limit) {
                    echo "\n -- will just use CSV source instead -- " . $final['count'] . " > " . $this->rec_limit . " \n"; //exit;
                    return; //if count > from csv then use csv later instead using - generate_map_data_using_GBIF_csv_files()
                }
                else echo "\n -- will use API as source 01 -- Records from CSV: " . $final['count'] . " < " . $this->rec_limit . " \n";
            }
            else echo "\n -- will use API as source 02 -- No CSV data \n"; //exit;
            // end ------------------------------------------------------------------------------------------
            
            self::get_georeference_data_via_api($rec['usageKey'], $basename);
        }
        if(!$final_count) {
            $filename = self::get_map_data_path($basename).$basename.".json";
            if(file_exists($filename)) unlink($filename); //delete cluster map data
        }
        else { //delete respective file
            if($final_count < $this->limit_20k) {}
            else {
                echo "\nfinal_count is [$final_count]\n";
                $filename = self::get_map_data_path($basename).$basename.".json";
                if(file_exists($filename)) {
                    unlink($filename); //delete cluster map data
                    exit("\nInvestigate: file deleted ($filename)\n");
                }
            }
        }
    }
    private function process_revised_cluster($final, $basename)
    {
        if(!($this->file5 = Functions::file_open(self::get_map_data_path($basename).$basename.".json", "w"))) return;
        $to_be_saved = array();
        $to_be_saved['records'] = array();
        $unique = array();
        $decimal_places = 6;
        while(true) {
            foreach($final['records'] as $r) {
                $lat = number_format($r['h'], $decimal_places);
                $lon = number_format($r['i'], $decimal_places);
                if(isset($unique["$lat,$lon"])) continue;
                else $unique["$lat,$lon"] = '';
                $to_be_saved['records'][] = $r;
            }
            echo "\n New total [$decimal_places]: " . count($unique) . "\n";
            
            $limit_to_break = $this->limit_20k;
            if($basename == 281) $limit_to_break = 35000; //Plantae 34131

            if(count($to_be_saved['records']) < $limit_to_break || $decimal_places == 0) break; //orig value is 0, not 1
            else {   //initialize vars
                $decimal_places--;
                $to_be_saved = array();
                $to_be_saved['records'] = array();
                $unique = array();
            }
        }
        
        //flag if after revised cluster is still unsuccessful
        if(count($unique) > $limit_to_break) {
            exit("\ntaxon_concept_ID [$basename] revised cluster unsuccessful\n");
            if(!($fhandle = Functions::file_open(DOC_ROOT . "public/tmp/google_maps/alert.txt", "a"))) return;
            fwrite($fhandle, "$basename" . "\t" . count($unique) . "\n");
            fclose($fhandle);
            // exit("\neli exits here...\n");
            
            //start force-get only the first 20k records
            $to_be_saved = self::force_reduce_records($to_be_saved);

            echo "\n Final total after force_reduce_records() [$decimal_places]: " . count($to_be_saved['records']) . "\n";

            $to_be_saved['count'] = count($to_be_saved['records']);
            $to_be_saved['actual'] = $final['count'];
            $json = json_encode($to_be_saved, JSON_UNESCAPED_SLASHES);
            fwrite($this->file5, "var data = ".$json);
            fclose($this->file5);
            return $to_be_saved['count']; //the smaller value; the bigger one is $to_be_saved['actual']
        }
        else {
            echo "\n Final total [$decimal_places]: " . count($unique) . "\n";
            $to_be_saved['count'] = count($to_be_saved['records']);
            $to_be_saved['actual'] = $final['count'];
            $json = json_encode($to_be_saved, JSON_UNESCAPED_SLASHES);
            fwrite($this->file5, "var data = ".$json);
            fclose($this->file5);
            return $to_be_saved['count']; //the smaller value; the bigger one is $to_be_saved['actual']
        }
        
    }
    function force_reduce_records($to_be_saved)
    {
        $i = -1;
        foreach($to_be_saved['records'] as $r) {
            $i++;
            if($i > $this->limit_20k) $to_be_saved['records'][$i] = '';
        }
        $to_be_saved['records'] = array_filter($to_be_saved['records']); //remove null arrays
        $to_be_saved['records'] = array_values($to_be_saved['records']); //reindex key
        return $to_be_saved;
    }
    function save_ids_to_text_from_many_folders() //a utility
    {   
        $dir_to_process = $this->save_path['map_data'];
        $text_file = "/Volumes/Thunderbolt4/map_data_zip/final_taxon_concept_IDS.txt";
        
        $i = 0;
        if(!($fhandle = Functions::file_open($text_file, "w"))) return;
        if($dir = opendir($dir_to_process)) {
            while(false !== ($subdir = readdir($dir))) {
                if(!in_array($subdir, array(".",".."))) {
                    echo "\n[$subdir]";
                    $files = $dir_to_process.$subdir."/*.json";
                    foreach (glob($files) as $filename) {
                        echo "\n[$filename] - " . pathinfo($filename, PATHINFO_FILENAME);
                        fwrite($fhandle, pathinfo($filename, PATHINFO_FILENAME) . "\n");
                        $i++;
                    }
                }
            }
        }
        fclose($fhandle);
        echo "\n--end taxon_concept_IDs total: [$i]--\n";
    }
    private function prepare_data($taxon_concept_id)
    {
        $txtFile = DOC_ROOT . "/public/tmp/google_maps/fusion/" . $taxon_concept_id . ".txt";
        $file_array = file($txtFile);
        unset($file_array[0]); //remove first line, the headers
        return $file_array;
    }
    private function get_center_latlon_using_taxonID($taxon_concept_id)
    {
        $rows = self::prepare_data($taxon_concept_id);
        echo "\n" . count($rows) . "\n";
        $minlat = false; $minlng = false; $maxlat = false; $maxlng = false;
        foreach($rows as $row) { //$row is String not array
            $cols = explode("\t", $row);
            // print_r($cols);
            /*
            if(count($cols) != 11) continue; //exclude row if total no. of cols is not 11, just to be sure that the col 10 is the "lat,long" column.
            $temp = explode(",", $cols[10]); //col 10 is the latlon column.
            $lat = $temp[0];
            $lon = $temp[1];
            */
            $lat = $cols[7];
            $lon = $cols[8];
            if ($lat && $lon) {
                if ($minlat === false) { $minlat = $lat; } else { $minlat = ($lat < $minlat) ? $lat : $minlat; }
                if ($maxlat === false) { $maxlat = $lat; } else { $maxlat = ($lat > $maxlat) ? $lat : $maxlat; }
                if ($minlng === false) { $minlng = $lon; } else { $minlng = ($lon < $minlng) ? $lon : $minlng; }
                if ($maxlng === false) { $maxlng = $lon; } else { $maxlng = ($lon > $maxlng) ? $lon : $maxlng; }
            }
            $lat_center = $maxlat - (($maxlat - $minlat) / 2);
            $lon_center = $maxlng - (($maxlng - $minlng) / 2);
            // echo "\n[$lat_center][$lon_center]\n";
            echo "\n$lat_center".","."$lon_center\n";
            return $lat_center.','.$lon_center;
        }
        /* computation based on: http://stackoverflow.com/questions/6671183/calculate-the-center-point-of-multiple-latitude-longitude-coordinate-pairs */
    }
    private function get_center_latlon_using_coordinates($records)
    {
        $minlat = false; $minlng = false; $maxlat = false; $maxlng = false;
        foreach($records as $r) {
            $lat = $r['h'];
            $lon = $r['i'];
            if ($lat && $lon) {
                if ($minlat === false) { $minlat = $lat; } else { $minlat = ($lat < $minlat) ? $lat : $minlat; }
                if ($maxlat === false) { $maxlat = $lat; } else { $maxlat = ($lat > $maxlat) ? $lat : $maxlat; }
                if ($minlng === false) { $minlng = $lon; } else { $minlng = ($lon < $minlng) ? $lon : $minlng; }
                if ($maxlng === false) { $maxlng = $lon; } else { $maxlng = ($lon > $maxlng) ? $lon : $maxlng; }
            }
            $lat_center = $maxlat - (($maxlat - $minlat) / 2);
            $lon_center = $maxlng - (($maxlng - $minlng) / 2);
            return array('center_lat' => $lat_center, 'center_lon' => $lon_center);
        }
        /* computation based on: http://stackoverflow.com/questions/6671183/calculate-the-center-point-of-multiple-latitude-longitude-coordinate-pairs */
    }
    private function write_to_supplementary_fusion_text($final)
    {
        //get publishers:
        $publishers = array();
        foreach($final['records'] as $r) {
            if($r['h'] && $r['i']) $publishers[$r['c']] = '';
        }
        $publishers = array_keys($publishers);
        sort($publishers);
        
        //get center lat lon:
        $temp = self::get_center_latlon_using_coordinates($final['records']);
        $center_lat = $temp['center_lat'];
        $center_lon = $temp['center_lon'];
        
        if($center_lat && $center_lon && $publishers) {
            $arr = array("tableID" => "", "total" => count($final['records']), "center_lat" => $center_lat, "center_lon" => $center_lon, "publishers" => $publishers);
            echo "\n" . json_encode($arr) . "\n";
            fwrite($this->file3, "var data = ".json_encode($arr));
        }
        
        /*
        var data = {"center_lat": 33.83253, "center_lon": -118.4745, "tableID": "1TspfLoWk5Vee6PHP78g09vwYtmNoeMIBgvt6Keiq", 
        "publishers" : ["Cornell Lab of Ornithology (CLO)", "Museum of Comparative Zoology, Harvard University (MCZ)"] };

        [count] => 619
        [records] => Array (
                    [0] => Array (
                            [catalogNumber] => 1272385
                            [sciname] => Chanos chanos (Forsskål, 1775)
                            [publisher] => iNaturalist.org (iNaturalist)
                            [publisher_id] => 28eb1a3f-1c15-4a95-931a-4af90ecb574d
                            [dataset] => iNaturalist research-grade observations
                            [dataset_id] => 50c9509d-22c7-4a22-a47d-8c48425ef4a7
                            [gbifID] => 1088910889
                            [lat] => 1.87214
                            [lon] => -157.42781
                            [recordedBy] => David R
                            [identifiedBy] => 
                            [pic_url] => http://static.inaturalist.org/photos/1596294/original.jpg?1444769372
                        )
                    [1] => Array (
                            [catalogNumber] => 2014-0501
                            [sciname] => Chanos chanos (Forsskål, 1775)
                            [publisher] => MNHN - Museum national d'Histoire naturelle (MNHN)
                            [publisher_id] => 2cd829bb-b713-433d-99cf-64bef11e5b3e
                            [dataset] => Fishes collection (IC) of the Muséum national d'Histoire naturelle (MNHN - Paris)
                            [dataset_id] => f58922e2-93ed-4703-ba22-12a0674d1b54
                            [gbifID] => 1019730375
                            [lat] => -12.8983
                            [lon] => 45.19877
                            [recordedBy] => 
                            [identifiedBy] => 
                            [pic_url] => 
                        )
        */
    }
    private function write_to_file($j) //for cluster map
    {
        $recs = array();
        $i = 0;
        foreach($j->results as $r) {
            // if($i > 2) break; //debug
            $i++;
            if(@$r->decimalLongitude && @$r->decimalLatitude) {
                $rec = array();
                $rec['a']   = (string) @$r->catalogNumber;
                $rec['b']   = self::get_sciname($r);
                $rec['c']   = self::get_org_name('publisher', @$r->publishingOrgKey);
                $rec['d']   = @$r->publishingOrgKey;
                if($val = @$r->institutionCode) $rec['c'] .= " ($val)";
                $rec['e']   = self::get_dataset_field(@$rek['datasetkey'], 'title'); //self::get_org_name('dataset', @$r->datasetKey);
                $rec['f']   = @$r->datasetKey;
                $rec['g']   = $r->gbifID;
                $rec['h']   = $r->decimalLatitude;
                $rec['i']   = $r->decimalLongitude;
                $rec['j']   = @$r->recordedBy;
                $rec['k']   = @$r->identifiedBy;
                $rec['l']   = @$r->media[0]->identifier;
                $rec['m']   = @$r->eventDate;
                /*
                $header['a'] = "catalogNumber";
                $header['b'] = "sciname";
                $header['c'] = "publisher";
                $header['d'] = "publisher_id";
                $header['e'] = "dataset";
                $header['f'] = "dataset_id";
                $header['g'] = "gbifID";
                $header['h'] = "lat";
                $header['i'] = "lon";
                $header['j'] = "recordedBy";
                $header['k'] = "identifiedBy";
                $header['l'] = "pic_url";
                $header['m'] = "eventDate";
                
                fields from the CSV downloaded from GBIF download service:
                gbifid    datasetkey    occurrenceid    kingdom    phylum    class    order    family    genus    species    infraspecificepithet    taxonrank    scientificname    countrycode    locality    
                publishingorgkey    decimallatitude    decimallongitude    elevation    elevationaccuracy    depth    depthaccuracy    eventdate    day    month    year    taxonkey    specieskey    
                basisofrecord    institutioncode    collectioncode    catalognumber    recordnumber    identifiedby    rights    rightsholder    recordedby    typestatus    
                establishmentmeans    lastinterpreted    mediatype    issue
                */
                
                /* self::write_to_fusion_table($rec); */
                $recs[] = $rec;
                
                /*
                Catalogue number: 3043
                Uncinocythere stubbsi
                Institution: Unidad de Ecología (Ostrácodos), Dpto. Microbiología y Ecología, Universidad de Valencia
                Collection: Entocytheridae (Ostracoda) World Database
                */
            }
        }
        return $recs;
    }
    private function write_to_fusion_table($rec)
    {   /*
        [catalogNumber] => 1272385
        [sciname] => Chanos chanos (Forsskål, 1775)
        [publisher] => iNaturalist.org (iNaturalist)
        [publisher_id] => 28eb1a3f-1c15-4a95-931a-4af90ecb574d
        [dataset] => iNaturalist research-grade observations
        [dataset_id] => 50c9509d-22c7-4a22-a47d-8c48425ef4a7
        [gbifID] => 1088910889
        [lat] => 1.87214
        [lon] => -157.42781
        [recordedBy] => David R
        [pic_url] => http://static.inaturalist.org/photos/1596294/original.jpg?1444769372
        */
        // fwrite($this->file2, implode("\t", $rec) . "\n"); //works OK but it has 2 fields for lat and lon
        
        $rek = $rec;
        $rek['location'] = $rec['h'] . "," . $rec['i'];
        unset($rek['lat']);
        unset($rek['lon']);
        fwrite($this->file2, implode("\t", $rek) . "\n");
        
        /* un-scalable, not an option
        //start kml 
        $kml_string = "<Placemark><name>" . $rec['a'] . "</name><description><![CDATA[" . $rec['a'] . "]]></description><Point><coordinates>" . $rek['location'] . ",0</coordinates></Point></Placemark>";
        fwrite($this->file4, $kml_string . "\n");
        //end kml
        */
    }
    private function get_sciname($r)
    {
        // if($r->taxonRank == "SPECIES") return $r->species;
        return $r->scientificName;
    }
    private function get_org_name($org, $id)
    {
        $id = trim($id);
        if(!$id) return "";
        $options = $this->download_options;
        $options['delay_in_minutes'] = 0;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($this->html[$org] . $id, $options)) {
            if(preg_match("/Full title<\/h3>(.*?)<\/p>/ims", $html, $arr)) return strip_tags(trim($arr[1]));
        }
    }
    private function get_initial_data($sciname)
    {
        if($usageKey = self::get_usage_key($sciname)) {
            $count = Functions::lookup_with_cache($this->gbif_record_count . $usageKey, $this->download_options);
            if($count > 0) {
                echo "\nTotal:[$count]"; //total records; with or without lat long
                $rec['usageKey'] = $usageKey;
                $rec["count"] = $count;
                return $rec;
            }
            else echo("\nNo occurrence\n");
        }
        else {
            exit("\nCannot get usage_key for ($sciname)\n");
        }
    }
    private function get_usage_key($sciname)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($this->gbif_taxon_info . $sciname, $options)) {
            $json = json_decode($json);
            $usageKey = false;
            if(!isset($json->usageKey)) {
                if(isset($json->note)) $usageKey = self::get_usage_key_again($sciname);
                else {} // e.g. Fervidicoccaceae
            }
            else $usageKey = trim((string) $json->usageKey);
            if($val = $usageKey) return $val;
        }
        return false;
    }
    private function get_usage_key_again($sciname)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        echo "\n2nd try to get usageKey ($sciname)\n";
        if($json = Functions::lookup_with_cache($this->gbif_taxon_info . $sciname . "&verbose=true", $options)) {
            $usagekeys = array();
            $options = array();
            $json = json_decode($json);
            if(!isset($json->alternatives)) return false;
            foreach($json->alternatives as $rec) {
                if($rec->canonicalName == $sciname) {
                    $options[$rec->rank][] = $rec->usageKey;
                    $usagekeys[] = $rec->usageKey;
                }
            }
            if($options) {
                /* from NCBIGGIqueryAPI.php connector
                if(isset($options["FAMILY"])) return min($options["FAMILY"]);
                else return min($usagekeys);
                */
                return min($usagekeys);
            }
        }
        return false;
    }
    private function process_current_hotlist_spreadsheet() //if we want to use the API for species-level taxa.
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1124WNU1r1-X1lGrtg8aFLg72IoMUlHpDoNK5QS_mb9E';
        $params['range']         = 'Sheet 1!A1:B73054'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        // print_r($arr); exit("\n");
        /* [73052] => Array(
                [0] => Clostridium
                [1] => 83389
            )
        [73053] => Array(
                [0] => Banasa dimiata
                [1] => 609110
            )
        */
        $species_level = 0; $m = 72311/3; $i = 0;
        foreach($arr as $rec) { $i++;
            $sciname = $rec[0];
            $taxon_concept_id = $rec[1];
            $sciname = trim(Functions::canonical_form($sciname));
            echo "\n$i. [$sciname][$taxon_concept_id]";
            if(stripos($sciname, " ") !== false) { //process only species-level taxa
                $species_level++;
                echo " [$sciname]";
                // /*
                $cont = false;
                if($i >=  1    && $i < $m)    $cont = true;
                // if($i >=  $m   && $i < $m*2)  $cont = true;
                // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                if(!$cont) continue;
                // */
                self::main_loop($sciname, $taxon_concept_id);
                exit("\n\ntemporary exit...\n");
            }
        }
        echo "\nspecies-level taxa count: $species_level\n";
    }
    private function process_hotlist_spreadsheet()
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $families = array();
        $doc = "http://localhost/eol_php_code/public/tmp/spreadsheets/SPG Hotlist Official Version.xlsx";
        // $doc = "http://localhost/~eolit/eli/eol_php_code/public/tmp/spreadsheets/SPG Hotlist Official Version.xlsx"; //for MacBook
        echo "\n processing [$doc]...\n";
        if($path = Functions::save_remote_file_to_local($doc, array("timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2))) {
            $arr = $parser->convert_sheet_to_array($path);
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
        $temp_filepath = Functions::save_remote_file_to_local(self::DL_MAP_SPECIES_LIST, array('timeout' => 4800, 'download_attempts' => 5));
        if(!$temp_filepath) {
            echo "\n\nExternal file not available. Program will terminate.\n";
            return;
        }
        $i = 0;
        foreach(new FileIterator($temp_filepath, true) as $line_number => $line) { // 'true' will auto delete temp_filepath
            $i++;
            if($line) {
                $m = 10000;
                $cont = false;
                if($i >=  1    && $i < $m)    $cont = true;
                // if($i >=  $m   && $i < $m*2)  $cont = true;
                // if($i >=  $m*2 && $i < $m*3)  $cont = true;
                // if($i >=  $m*3 && $i < $m*4)  $cont = true;
                // if($i >=  $m*4 && $i < $m*5)  $cont = true;
                if(!$cont) continue;
                $arr = explode("\t", $line);
                $sciname = trim($arr[0]);
                echo "\n[$sciname]\n";
                self::main_loop($sciname);
            }
            // if($i >= 5) break; //debug
        }
    }
    //========================================================
    // start of Clustering code: (http://www.appelsiini.net/2008/introduction-to-marker-clustering-with-google-maps)
    //========================================================
    function start_clustering()
    {
        define('OFFSET', 268435456);
        define('RADIUS', 85445659.4471); /* $offset / pi() */
        $markers   = array();
        $markers[] = array('id' => 'marker_1',                            'lat' => 59.441193, 'lon' => 24.729494);
        $markers[] = array('id' => 'marker_2',                            'lat' => 59.432365, 'lon' => 24.742992);
        $markers[] = array('id' => 'marker_3',                            'lat' => 59.431602, 'lon' => 24.757563);
        $markers[] = array('id' => 'marker_4',                            'lat' => 59.437843, 'lon' => 24.765759);
        $markers[] = array('id' => 'marker_5',                            'lat' => 59.439644, 'lon' => 24.779041);
        $markers[] = array('id' => 'marker_6',                            'lat' => 59.434776, 'lon' => 24.756681);
        $clustered = self::cluster($markers, 50, 11); //middel orig 20
        print_r($clustered);
    }
    function cluster($markers, $distance, $zoom) {
        $clustered = array();
        /* Loop until all markers have been compared. */
        while (count($markers)) {
            $marker  = array_pop($markers);
            $cluster = array();
            /* Compare against all markers which are left. */
            foreach ($markers as $key => $target) {
                $pixels = self::pixelDistance($marker['lat'], $marker['lon'],
                                        $target['lat'], $target['lon'],
                                        $zoom);
                /* If two markers are closer than given distance remove */
                /* target marker from array and add it to cluster.      */
                if ($distance > $pixels) {
                    printf("Distance between %s,%s and %s,%s is %d pixels.\n", 
                        $marker['lat'], $marker['lon'],
                        $target['lat'], $target['lon'],
                        $pixels);
                    unset($markers[$key]);
                    $cluster[] = $target;
                }
            }

            /* If a marker has been added to cluster, add also the one  */
            /* we were comparing to and remove the original from array. */
            if (count($cluster) > 0) {
                $cluster[] = $marker;
                $clustered[] = $cluster;
            } else {
                $clustered[] = $marker;
            }
        }
        return $clustered;
    }
    function lonToX($lon) {
        return round(OFFSET + RADIUS * $lon * pi() / 180);        
    }
    function latToY($lat) {
        return round(OFFSET - RADIUS * 
                    log((1 + sin($lat * pi() / 180)) / 
                    (1 - sin($lat * pi() / 180))) / 2);
    }
    function pixelDistance($lat1, $lon1, $lat2, $lon2, $zoom) {
        $x1 = self::lonToX($lon1);
        $y1 = self::latToY($lat1);
        $x2 = self::lonToX($lon2);
        $y2 = self::latToY($lat2);
        return sqrt(pow(($x1-$x2),2) + pow(($y1-$y2),2)) >> (21 - $zoom);
    }
    //========================================================
    // end of Clustering code: (http://www.appelsiini.net/2008/introduction-to-marker-clustering-with-google-maps)
    //========================================================

}
?>
