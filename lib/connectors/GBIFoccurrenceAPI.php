<?php
namespace php_active_record;
/* connector: [gbif_georeference.php]
This script searches GBIF API occurrence data via taxon (taxon_key)

1. search via GBIF API
1.1 get taxonkey using scientific name
1.2 use taxonkey to get occurrence data

2. use the SPG hot list as list of taxa

3. use taxa list (2-column text file - taxon_concept_id & scientific name) from EoL
3.1. loop through the taxa list
3.2. get taxonkey using scientific name
3.3. use taxonkey to get the occurrence in CSV file (CSV created in 4.2)

4. process the big GBIF occurrence file (CSV file)
4.1. loop through the list
4.2. save individual CSV file for each taxon (to be used in 3.3)
*/

class GBIFoccurrenceAPI
{
    // const DL_MAP_SPECIES_LIST   = "http://www.discoverlife.org/export/species_map.txt";
    const DL_MAP_SPECIES_LIST   = "http://localhost/cp/DiscoverLife/species_map.txt";
    
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
        
        /* for mac mini
        // it seems no longer used
        // $this->save_path['cluster']     = DOC_ROOT . "public/tmp/google_maps/cluster/";
        // $this->save_path['cluster_v2']  = DOC_ROOT . "public/tmp/google_maps/cluster_v2/";
        */
        
        // /* for macbook
        // it seems no longer used
        // $this->save_path['cluster']     = "/Volumes/Thunderbolt4/cluster_cache/cluster/";
        // $this->save_path['cluster_v2']  = "/Volumes/Thunderbolt4/cluster_cache/cluster_v2/";
        // */

        $this->save_path['map_data'] = "/Volumes/AKiTiO4/map_data_new/";
        $this->save_path['map_data'] = "/Volumes/AKiTiO4/map_data_2018/";
        

        // not being used anymore
        // $this->save_path['fusion']      = DOC_ROOT . "public/tmp/google_maps/fusion/";
        // $this->save_path['fusion2']     = DOC_ROOT . "public/tmp/google_maps/fusion2/";
        // $this->save_path['kml']         = DOC_ROOT . "public/tmp/google_maps/kml/";
        
        $this->rec_limit = 100000; //50000;
        
        $this->csv_paths = array();
        /* obsolete
        $this->csv_paths[] = DOC_ROOT . "/public/tmp/google_maps/GBIF_taxa_csv_animalia/";
        $this->csv_paths[] = DOC_ROOT . "/public/tmp/google_maps/GBIF_taxa_csv_incertae/";
        $this->csv_paths[] = DOC_ROOT . "/public/tmp/google_maps/GBIF_taxa_csv_others/";
        */
        $this->csv_paths[] = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_taxa_csv/";
    }

    function start()
    {
        /* steps in order accordingly
        1. Delete all .json files
        First try using the API
        2. self::process_all_eol_taxa(); return;
        then use the CSV as 2nd option
        3. self::generate_map_data_using_GBIF_csv_files(); return;
        */
        
        /* 237020 - /map_data_from_api/ */
        // start GBIF
        // self::breakdown_GBIF_csv_file_v2(); return;
        // self::breakdown_GBIF_csv_file(); echo "\nDONE: breakdown_GBIF_csv_file()\n"; return;
        // self::generate_map_data_using_GBIF_csv_files(); return;
        // end GBIF
        
        // self::start_clustering(); return;                        //distance clustering sample
        // self::get_center_latlon_using_taxonID(206692); return;   //computes the center lat long
        
        //OKAY to use
        //---------------------------------------------------------------------------------------------------------------------------------------------
        // /*
        if(Functions::is_production()) $path = "/extra/eol_php_code_public_tmp/google_maps/taxon_concept_names.tab";
        else                           $path = "/Volumes/AKiTiO4/z backup/eol_php_code_public_tmp/google_maps/taxon_concept_names.tab";
        self::process_all_eol_taxa($path, false); return;           //make use of tab-delimited text file from JRice
        // */
        //---------------------------------------------------------------------------------------------------------------------------------------------
        
        // self::process_hotlist_spreadsheet(); return;             //make use of hot list spreadsheet from SPG
        // self::process_DL_taxon_list(); return;                   //make use of taxon list from DiscoverLife
        
        $scinames = array();                                        //make use of manual taxon list
        $scinames["Phalacrocorax penicillatus"] = 1048643;
        $scinames["Chanos chanos"] = 224731;
        $scinames["Gadus morhua"] = 206692;
        $scinames["Atractoscion aequidens"] = 203945;
        $scinames["Veronica beccabunga"] = 578492;
        $scinames["Tragopogon pratensis"] = 503271;
        $scinames["Chelidonium majus"] = 488380;
        $scinames["Veronica hederifolia"] = 578497;
        $scinames["Saxicola torquatus"] = 284202;
        $scinames["Chorthippus parallelus"] = 495478;
        $scinames["Micarea lignaria"] = 197344;
        $scinames["Gadidae"] = 5503;
        $scinames["Animalia"] = 1;
        $scinames['Dermaptera'] = 405;
        $scinames["Soleidae"] = 5169;
        $scinames["Caridinophilidae"] = 50;
        $scinames["Chenonetta"] = 104248;
        $scinames["Coniocybaceae"] = 6217;
        $scinames["Oenanthe"] = 49118;
        $scinames["Plantae"] = 281;
        $scinames["Chaetoceros"] = 12010;
        $scinames["Fragaria vesca auct. nonn. fl. as. med."] = 229665;
        $scinames["Achillea millefolium"] = 467225;
        $scinames["Gadus morhua"] = 206692;
        $scinames["Achillea millefolium L."] = 45850244;
        $scinames["Francolinus levaillantoides"] = 1; //5227890
        $scinames["Phylloscopus trochilus"] = 2; //2493052
        $scinames["Aichi virus"] = 540501;
        $scinames["Anthriscus sylvestris (L.) Hoffm."] = 584996; //from Plantae group
        $scinames["Xenidae"] = 8965;
        $scinames["Gadidae"] = 5503;
        $scinames["Soleidae"] = 5169;
        $scinames["Chenonetta"] = 104248;
        
        $scinames = array();
        // $scinames["Pterostichus tarsalis"] = 312743;
        // $scinames["Hyperiidae"] = 1180;
        // $scinames["Decapoda"] = 1183; //then will try CSV source to see if we're getting more data from CSV or API 
        $scinames["Gadus morhua"] = 206692;
        
        /*
        will test this as well: 
        [Moehringia trinervia][tc_id = 482255]
        $scinames["Moehringia trinervia"] = 482255; //then will try CSV source to see if we're getting more data from CSV or API 
        */
        
        
        foreach($scinames as $sciname => $taxon_concept_id) self::main_loop($sciname, $taxon_concept_id);
        
        /* API result:
        [offset]        => 0
        [limit]         => 20
        [endOfRecords]  => 
        [count]         => 78842
        [results]       => Array
        */
    }
    
    //==========================
    // start GBIF methods
    //==========================
    private function breakdown_GBIF_csv_file_v2() // a test for Gadus morhua (2415835), to check if there are about 70K plus records, test pass OK
    {
        // return;
        $path = DOC_ROOT . "/public/tmp/google_maps/GBIF_csv/Animalia/animalia.csv";
        $path = "/Volumes/Thunderbolt4/eol_cache_gbif/pub_tmp_google_maps/GBIF_csv/Animalia/animalia.csv";
        $k = 0;
        $i = 0;
        $not42 = 0;
        $noTaxonkey = 0;
        foreach(new FileIterator($path) as $line_number => $line) { // 'true' will auto delete temp_filepath
            $i++; echo "$i ";
            if($i == 1) continue;
            $row = explode("\t", $line);
            if(count($row) != 42) {
                echo "\n" . count($row) . "\n";
                $not42++;
            }
            if(!@$row[26]) {
                $noTaxonkey++;
                continue;
            }
            /*
            $taxonkey = $row[26];
            if($taxonkey == 2415835) {
                if($row[16] && $row[17]) $k++;
            }
            */
        }
        // echo "\ntotal for gadus morhua: [$k]\n";
        echo "\nNot 42: [$not42]\n";
        echo "\nNo taxonkey: [$noTaxonkey]\n";
    }
    private function breakdown_GBIF_csv_file() //working as of Mar 3 Thursday
    {
        // return;
        /* ran it with all taxon levels [finished in 4.79 hours]
        $path  = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_csv/Incertae sedis/incertae sedis.csv";
        $path2 = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_taxa_csv_incertae/";
        */
        
        /* ran it with all taxon levels; total records in cmd finished: 361,245,321 
        $path  = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_csv/Animalia/animalia.csv";
        $path2 = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_taxa_csv_animalia/";
        */
        
        /* total records in cmd finished: 151,838,119
           // Mar 14 2:05 AM - run it with just species-level taxa
           // Apr 27         - run it with just higher-level taxa
        $path  = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_csv/Others/others.csv";
        $path2 = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_taxa_csv_others/";
        */
        
        $path  = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_csv/Gadus morhua.csv";
        $path2 = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_taxa_csv/";
        

        $i = 0;
        foreach(new FileIterator($path) as $line_number => $line) { // 'true' will auto delete temp_filepath
            $i++;
            if(($i % 5000) == 0) echo number_format($i) . " ";
            // if($i < 66445000) continue; //bec of machine shutdown - for 'others.csv'
            // if($i < 167133238) continue; //bec of machine shutdown - for 'animalia.csv'

            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                continue;
            }
            else {
                if(!@$row[1]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = $row[$k];
                    $k++;
                }
            }
            if(!@$rec['taxonkey']) continue;
            //start exclude higher-level taxa ========================================= used 1st batch of Plantae group
            /*
            $sciname = Functions::canonical_form($row[12]);
            if(stripos($sciname, " ") !== false) $cont = true; //there is space, meaning a species-level taxon
            else                                 $cont = false;
            if(!$cont) continue;
            */
            //end exclude higher-level taxa ===========================================
            //start exclude species-level taxa ========================================= used for 2nd batch of Plantae group
            /*
            $sciname = Functions::canonical_form($row[12]);
            if(stripos($sciname, " ") !== false) $cont = false; //there is space, meaning a species-level taxon
            else                                 $cont = true;
            if(!$cont) continue;
            */
            //end exclude species-level taxa ===========================================
            
            $taxonkey = $rec['taxonkey'];
            $rek = array($rec['gbifid'], $rec['datasetkey'], $rec['scientificname'], $rec['publishingorgkey'], $rec['decimallatitude'], $rec['decimallongitude'], $rec['eventdate'], $rec['institutioncode'], $rec['catalognumber'], $rec['identifiedby'], $rec['recordedby']);
            if($rec['decimallatitude'] && $rec['decimallongitude']) {
                $csv_file = $path2 . $taxonkey . ".csv";
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
        }
    }
    
    private function generate_map_data_using_GBIF_csv_files()
    {
        $eol_taxon_id_list = self::process_all_eol_taxa($path, true); //listOnly = true
        // print_r($eol_taxon_id_list); echo "\n" . count($eol_taxon_id_list) . "\n"; return; //[Triticum aestivum virus] => 540152
        
        // $eol_taxon_id_list["Gadus morhua"] = 206692;
        // $eol_taxon_id_list["Achillea millefolium L."] = 45850244;
        // $eol_taxon_id_list["Francolinus levaillantoides"] = 1; //5227890
        // $eol_taxon_id_list["Phylloscopus trochilus"] = 2; //2493052
        // $eol_taxon_id_list["Aichi virus"] = 540501;
        // $eol_taxon_id_list["Anthriscus sylvestris (L.) Hoffm."] = 584996; //from Plantae group
        // $eol_taxon_id_list["Xenidae"] = 8965;
        // $eol_taxon_id_list["Gadidae"] = 5503;
        // $eol_taxon_id_list["Soleidae"] = 5169;
        // $eol_taxon_id_list["Plantae"] = 281;
        // $eol_taxon_id_list["Chaetoceros"] = 12010;
        // $eol_taxon_id_list["Chenonetta"] = 104248;
        
        /* for testing 1 taxon
        $eol_taxon_id_list = array();
        // $eol_taxon_id_list["Hyperiidae"] = 1180;
        $eol_taxon_id_list["Decapoda"] = 1183;
        */

        $paths = $this->csv_paths;
        
        $i = 0;
        foreach($eol_taxon_id_list as $sciname => $taxon_concept_id) {
            $i++;
            // ==============================
            /*
            $m = 100000;
            $cont = false;
            // if($i >=  1    && $i < $m)    $cont = true;
            // if($i >=  $m   && $i < $m*2)  $cont = true;
            // if($i >=  $m*2 && $i < $m*3)  $cont = true;
            // if($i >=  $m*3 && $i < $m*4)  $cont = true;
            // if($i >=  $m*4 && $i < $m*5)  $cont = true;
            // if($i >=  $m*5 && $i < $m*6)  $cont = true;
            if(!$cont) continue;
            */
            // ==============================
            echo "\n$i. [$sciname][$taxon_concept_id]";
            if($usageKey = self::get_usage_key($sciname)) {
                echo "\nOK GBIF key [$usageKey]\n";
                if(self::map_data_file_already_been_generated($taxon_concept_id)) continue;
                
                if($final = self::prepare_csv_data($usageKey, $paths)) {
                    echo "\n" . $final['count'] . "\n";
                    if($final['count'] > 20000) {
                        echo "\n > 20K\n";
                        self::process_revised_cluster($final, $taxon_concept_id); //done after main demo using screenshots
                    }
                    elseif($final['count'] <= 20000) {
                        echo "\n <= 20K\n";
                        $final['actual'] = $final['count'];
                        // if(!($this->file = Functions::file_open($this->save_path['cluster'].$taxon_concept_id.".json", "w"))) return;
                        if(!($this->file = Functions::file_open(self::get_map_data_path($taxon_concept_id).$taxon_concept_id.".json", "w"))) return;
                        $json = json_encode($final);
                        fwrite($this->file, "var data = ".$json);
                        fclose($this->file);
                    }
                    else exit("\nShould not go here 001 [$sciname]\n");
                }
                else echo "\nmap data not yet available [$sciname]\n";
            }
            else echo "\n usageKey not found! [$sciname]\n";
        } //end main foreach()
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
            $csv = $path . $usageKey . ".csv";
            if(file_exists($csv)) {
                echo "\nusageKey = [$usageKey] found in [$path]";
                $file_array = file($csv);
                $gbif_ids = array();
                foreach($file_array as $line) {
                    $row = explode("\t", $line);
                    
                    //make record unique
                    $gbifid = $row[0];
                    if(isset($gbif_ids[$gbifid])) continue;
                    else $gbif_ids[$gbifid] = '';
                    
                    $rec = array();
                    $rec['a']   = $row[8];
                    $rec['b']   = $row[2];
                    $rec['c']   = self::get_org_name('publisher', @$row[3]);
                    $rec['d']   = @$row[3];
                    if($val = @$row[7]) $rec['c'] .= " ($val)";
                    $rec['e']   = self::get_org_name('dataset', @$row[1]);
                    $rec['f']   = @$row[1];
                    $rec['g']   = $row[0];
                    $rec['h']   = $row[4];
                    $rec['i']   = $row[5];
                    $rec['j']   = @$row[10];
                    $rec['k']   = @$row[9];
                    $rec['l']   = '';
                    $rec['m']   = @$row[6];
                    /*
                    $row[0] => gbifid               0
                    $row[1] => datasetkey           1
                    $row[12] => scientificname      2
                    $row[15] => publishingorgkey    3
                    $row[16] => decimallatitude     4
                    $row[17] => decimallongitude    5
                    $row[22] => eventdate           6
                    $row[29] => institutioncode     7
                    $row[31] => catalognumber       8
                    $row[33] => identifiedby        9
                    $row[36] => recordedby          10
                    */

                    /*
                    old csv:
                    gbifid	datasetkey	occurrenceid	kingdom	phylum	class	order	family	genus	species	infraspecificepithet	taxonrank	scientificname	countrycode	locality	publishingorgkey	decimallatitude	decimallongitude	                                                    elevation	elevationaccuracy	depth	depthaccuracy	eventdate	day	month	year	taxonkey	specieskey	basisofrecord	institutioncode	collectioncode	catalognumber	recordnumber	identifiedby	rights	rightsholder	recordedby	typestatus	establishmentmeans	lastinterpreted	mediatype	issue
                    new csv:
                    gbifid	datasetkey	occurrenceid	kingdom	phylum	class	order	family	genus	species	infraspecificepithet	taxonrank	scientificname	countrycode	locality	publishingorgkey	decimallatitude	decimallongitude	coordinateuncertaintyinmeters	coordinateprecision	elevation	elevationaccuracy	depth	depthaccuracy	eventdate	day	month	year	taxonkey	specieskey	basisofrecord	institutioncode	collectioncode	catalognumber	recordnumber	identifiedby	license	rightsholder	recordedby	typestatus	establishmentmeans	lastinterpreted	mediatype	issue
                    */
                    
                    $final['records'][] = $rec;
                }
                $final['count'] = count($final['records']);
            }
            else echo "\n[$usageKey] NOT found in [$path]";
        }
        return $final;
    }
    //==========================
    // end GBIF methods
    //==========================
    private function process_all_eol_taxa($path, $listOnly = false)
    {
        if($listOnly) $list = array();
        $i = 0;
        foreach(new FileIterator($path) as $line_number => $line) { // 'true' will auto delete temp_filepath
            $line = explode("\t", $line);
            $taxon_concept_id = $line[0];
            $sciname          = Functions::canonical_form($line[1]);
            if($listOnly) {
                $list[$sciname] = $taxon_concept_id;
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
        /* working but procedure changed to use subdirectories for map data storage
        $filenames = array($this->save_path['cluster'].$basename.".json", $this->save_path['cluster_v2'].$basename.".json");
        foreach($filenames as $filename) {
            if(file_exists($filename)) {
                echo "\n[$basename] already generated OK";
                return true;
            }
        }
        return false;
        */
        
        $filename = self::get_map_data_path($basename).$basename.".json";
        if(file_exists($filename)) {
            echo "\n[$basename] already generated OK";
            return true;
        }
        else return false;
    }
    private function main_loop($sciname, $taxon_concept_id = false)
    {
        $sciname = Functions::canonical_form($sciname); echo "\n[$sciname]\n";
        $basename = $sciname;
        if($val = $taxon_concept_id) $basename = $val;
        
        if(self::map_data_file_already_been_generated($basename)) return;

        /*
        if(!($this->file2 = Functions::file_open($this->save_path['fusion'].$basename.".txt", "w"))) return;
        if(!($this->file3 = Functions::file_open($this->save_path['fusion2'].$basename.".json", "w"))) return;
        */

        $final_count = false;
        if($rec = self::get_initial_data($sciname)) {
            print_r($rec);
            // first is check the csv front
            if($final = self::prepare_csv_data($rec['usageKey'], $this->csv_paths)) {
                print_r($final);
                if($final['count'] > $this->rec_limit) {
                    echo "\n -- will just use CSV source instead -- " . $final['count'] . " > " . $this->rec_limit . " \n"; //exit;
                    return; //if count > from csv then use csv later instead using - generate_map_data_using_GBIF_csv_files()
                }
                else echo "\n -- will use API as source 01 -- " . $final['count'] . " < " . $this->rec_limit . " \n";
            }
            else echo "\n -- will use API as source 02 -- No CSV data \n"; //exit;
            // end
            
            // if($rec['count'] < $this->rec_limit) //only process taxa with < 100K georeference records
            // {
                $final = self::get_georeference_data($rec['usageKey'], $basename);
                $final_count = $final['count'];
                if($final_count > 20000)
                {
                    $final_count = self::process_revised_cluster($final, $basename); //done after main demo using screenshots
                }
            // }
            // else //e.g. 102569. [Chenonetta][104248]
            // {
            //     echo "\nIGNORED:[$sciname][tc_id=$basename][" . $rec['count'] . "]\n";
            //     $final = self::get_georeference_data($rec['usageKey'], $basename);
            //     $final_count = $final['count'];
            //     $final_count = self::process_revised_cluster($final, $basename);
            // }
        }
        
        /*
        fclose($this->file2);
        fclose($this->file3);
        */
        // fclose($this->file4); //kml
        
        if(!$final_count) {
            $filename = self::get_map_data_path($basename).$basename.".json";
            if(file_exists($filename)) unlink($filename); //delete cluster map data
            /*
            unlink($this->save_path['fusion'].$basename.".txt");
            unlink($this->save_path['fusion2'].$basename.".json");
            */
        }
        else { //delete respective file
            if($final_count < 20000) {
                /*
                unlink($this->save_path['fusion'].$basename.".txt");   //delete Fusion data
                unlink($this->save_path['fusion2'].$basename.".json"); //delete Fusion data (centerLatLon, tableID, publishers)
                */
            }
            else {
                echo "\nfinal_count is [$final_count]\n";
                $filename = self::get_map_data_path($basename).$basename.".json";
                if(file_exists($filename)) unlink($filename); //delete cluster map data
            }
        }
    }
    private function process_revised_cluster($final, $basename)
    {
        // if(!($this->file5 = Functions::file_open($this->save_path['cluster_v2'].$basename.".json", "w"))) return;
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
            
            $limit_to_break = 20000;
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
            echo "\ntaxon_concept_ID [$basename] revised cluster unsuccessful\n";
            if(!($fhandle = Functions::file_open(DOC_ROOT . "public/tmp/google_maps/alert.txt", "a"))) return;
            fwrite($fhandle, "$basename" . "\t" . count($unique) . "\n");
            fclose($fhandle);
            // exit("\neli exits here...\n");
            
            //start force-get only the first 20k records
            $to_be_saved = self::reduce_records($to_be_saved);

            echo "\n Final total after reduce_records() [$decimal_places]: " . count($to_be_saved['records']) . "\n";

            $to_be_saved['count'] = count($to_be_saved['records']);
            $to_be_saved['actual'] = $final['count'];
            $json = json_encode($to_be_saved);
            fwrite($this->file5, "var data = ".$json);
            fclose($this->file5);
            return $to_be_saved['count']; //the smaller value; the bigger one is $to_be_saved['actual']
        }
        else {
            echo "\n Final total [$decimal_places]: " . count($unique) . "\n";
            $to_be_saved['count'] = count($to_be_saved['records']);
            $to_be_saved['actual'] = $final['count'];
            $json = json_encode($to_be_saved);
            fwrite($this->file5, "var data = ".$json);
            fclose($this->file5);
            return $to_be_saved['count']; //the smaller value; the bigger one is $to_be_saved['actual']
            
            //unlink the original cluster
            // fclose($this->file);
            // unlink($this->save_path['cluster'].$basename.".json");
            // $this->file = false;
        }
        
    }
    function reduce_records($to_be_saved)
    {
        $i = -1;
        foreach($to_be_saved['records'] as $r) {
            $i++;
            if($i > 20000) $to_be_saved['records'][$i] = '';
        }
        $to_be_saved['records'] = array_filter($to_be_saved['records']); //remove null arrays
        $to_be_saved['records'] = array_values($to_be_saved['records']); //reindex key
        return $to_be_saved;
    }
    function save_ids_to_text_from_many_folders() //a utility
    {   /*
        $dir_to_process = "/Volumes/MacMini_HD2/batch_parts/map_data_batch2/";
        $text_file      = "/Volumes/MacMini_HD2/batch_parts/taxon_concept_IDS.txt";
        */
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
    private function get_georeference_data($taxonKey, $basename)
    {
        $offset = 0;
        $limit = 300;
        $continue = true;
        $final = array();
        $final['records'] = array();
        while($continue) {
            if($offset > $this->rec_limit) break; //working... uncomment if u want to limit to 100,000
            $url = $this->gbif_occurrence_data . $taxonKey . "&limit=$limit";
            if($offset) $url .= "&offset=$offset";
            if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                $j = json_decode($json);
                // print_r($j);
                $recs = self::write_to_file($j);
                $final['records'] = array_merge($final['records'], $recs);

                echo "\n incremental count: " . count($recs) . "\n";

                if($j->endOfRecords)                            $continue = false;
                if(count($final['records']) > $this->rec_limit) $continue = false; //limit no. of markers in Google maps is 100K //working... uncomment if u want to limit to 100,000
            }
            else break; //just try again next time...
            $offset += $limit;
        }
        
        $final['count'] = count($final['records']);
        $final['actual'] = count($final['records']);
        
        echo "\nFinal count: " . $final['count'] . "\n";
        $json = json_encode($final);
        
        // if(!($this->file = Functions::file_open($this->save_path['cluster'].$basename.".json", "w"))) return;
        if(!($this->file = Functions::file_open(self::get_map_data_path($basename).$basename.".json", "w"))) return;
        fwrite($this->file, "var data = ".$json);
        fclose($this->file);
        
        /* self::write_to_supplementary_fusion_text($final); */
        
        return $final;
    }
    private function get_center_latlon_using_taxonID($taxon_concept_id)
    {
        $rows = self::prepare_data($taxon_concept_id);
        echo "\n" . count($rows) . "\n";
        $minlat = false; $minlng = false; $maxlat = false; $maxlng = false;
        foreach($rows as $row) //$row is String not array
        {
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
        foreach($records as $r)
        {
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
        foreach($final['records'] as $r)
        {
            if($r['h'] && $r['i']) $publishers[$r['c']] = '';
        }
        $publishers = array_keys($publishers);
        sort($publishers);
        
        //get center lat lon:
        $temp = self::get_center_latlon_using_coordinates($final['records']);
        $center_lat = $temp['center_lat'];
        $center_lon = $temp['center_lon'];
        
        if($center_lat && $center_lon && $publishers)
        {
            $arr = array("tableID" => "", "total" => count($final['records']), "center_lat" => $center_lat, "center_lon" => $center_lon, "publishers" => $publishers);
            echo "\n" . json_encode($arr) . "\n";
            fwrite($this->file3, "var data = ".json_encode($arr));
        }
        
        /*
        var data = {"center_lat": 33.83253, "center_lon": -118.4745, "tableID": "1TspfLoWk5Vee6PHP78g09vwYtmNoeMIBgvt6Keiq", 
        "publishers" : ["Cornell Lab of Ornithology (CLO)", "Museum of Comparative Zoology, Harvard University (MCZ)"] };
        
        [count] => 619
        [records] => Array
                (
                    [0] => Array
                        (
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

                    [1] => Array
                        (
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
        foreach($j->results as $r)
        {
            // if($i > 2) break; //debug
            $i++;
            if(@$r->decimalLongitude && @$r->decimalLatitude)
            {
                $rec = array();
                $rec['a']   = (string) @$r->catalogNumber;
                $rec['b']   = self::get_sciname($r);
                $rec['c']   = self::get_org_name('publisher', @$r->publishingOrgKey);
                $rec['d']   = @$r->publishingOrgKey;
                if($val = @$r->institutionCode) $rec['c'] .= " ($val)";
                $rec['e']   = self::get_org_name('dataset', @$r->datasetKey);
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
        if(!$id) return "";
        $options = $this->download_options;
        $options['delay_in_minutes'] = 0;
        $options['expire_seconds'] = false; //debug
        
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
        }
        else {
            exit("\nCannot get usage_key for ($sciname)\n");
        }
    }

    private function get_usage_key($sciname)
    {
        if($json = Functions::lookup_with_cache($this->gbif_taxon_info . $sciname, $this->download_options))
        {
            $json = json_decode($json);
            $usageKey = false;
            if(!isset($json->usageKey))
            {
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
        if($json = Functions::lookup_with_cache($this->gbif_taxon_info . $sciname . "&verbose=true", $this->download_options))
        {
            $usagekeys = array();
            $options = array();
            $json = json_decode($json);
            if(!isset($json->alternatives)) return false;
            foreach($json->alternatives as $rec)
            {
                if($rec->canonicalName == $sciname)
                {
                    $options[$rec->rank][] = $rec->usageKey;
                    $usagekeys[] = $rec->usageKey;
                }
            }
            if($options)
            {
                /* from NCBIGGIqueryAPI.php connector
                if(isset($options["FAMILY"])) return min($options["FAMILY"]);
                else return min($usagekeys);
                */
                return min($usagekeys);
            }
        }
        return false;
    }
    
    private function process_hotlist_spreadsheet()
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $families = array();
        $doc = "http://localhost/eol_php_code/public/tmp/spreadsheets/SPG Hotlist Official Version.xlsx";
        // $doc = "http://localhost/~eolit/eli/eol_php_code/public/tmp/spreadsheets/SPG Hotlist Official Version.xlsx"; //for MacBook
        echo "\n processing [$doc]...\n";
        if($path = Functions::save_remote_file_to_local($doc, array("timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2)))
        {
            $arr = $parser->convert_sheet_to_array($path);
            $i = -1;
            foreach($arr['Animals'] as $sciname)
            {
                $i++;
                $sciname = trim(Functions::canonical_form($sciname));
                // if(stripos($sciname, " ") !== false) //process only species-level taxa
                if(true)
                {
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
