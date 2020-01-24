<?php
namespace php_active_record;
/* connectors: [bold2inat.php] https://eol-jira.bibalex.org/browse/COLLAB-1004 */
class BOLD2iNaturalistAPI
{
    function __construct($app)
    {
        $this->resource_id = ''; //will be initialized in start()
        $this->app = $app;
        // $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        // $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        
        $this->download_options = array('cache' => 1, 'resource_id' => 'MarineGEO', 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 2000000); //orig expires quarterly
        $this->download_options['expire_seconds'] = false; //probably permanent

        $this->api['coll_num'] = 'http://www.boldsystems.org/index.php/API_Public/specimen?ids=COLL_NUM&format=json';
        
        if($app == 'specimen_export') {}
        if($app == 'specimen_image_export') {}

        /* ============================= START for bold2inat ============================= */
        if($app == 'bold2inat') {
            $this->input['path'] = DOC_ROOT.'/applications/BOLD2iNAT/temp/'; //input.xlsx
            $dir = $this->input['path'];
            if(!is_dir($dir)) mkdir($dir);
            
            $this->resources['path'] = CONTENT_RESOURCE_LOCAL_PATH."MarineGEO_bold2inat/";
            $dir = $this->resources['path'];
            if(!is_dir($dir)) mkdir($dir);
            
            // no need anymore
            // $dir = $this->resources['path'].'TSVs';
            // if(!is_dir($dir)) mkdir($dir);
            
            /* not used here...
            $this->input['worksheets'] = array('Sheet1');
            $this->labels['Sheet1']['MOOP'] = array('Image File', 'Original Specimen', 'View Metadata', 'Caption', 'Measurement', 'Measurement Type', 'Sample Id', 'Process Id', 'License Holder', 'License', 'License Year', 'License Institution', 'License Contact', 'Photographer');
            $this->labels_Lab_Sheet = array('Process ID', 'Sample ID', 'Field ID');
            */

            $this->api['BOLDS specimen'] = "http://www.boldsystems.org/index.php/API_Public/specimen?container=PROJECT_CODE&taxon=TAXON_STR&format=tsv";
            $this->dept_map['FISH'] = 'fishes';
            $this->dept_map['MAMMALS'] = 'mammals';
            $this->dept_map['HERPS'] = 'herps'; //Amphibians & Reptiles
            $this->dept_map['BIRDS'] = 'birds';
            $this->dept_map['BOTANY'] = 'botany';
            $this->dept_map['PALEOBIOLOGY'] = 'paleo';
            
            $this->inat_service['taxa'] = 'https://api.inaturalist.org/v1/taxa?q=NAME_STR&rank=RANK_STR';
            
            //create working folders
            if(Functions::is_production()) $main_path = "/extra/other_files/MarineGeo/";
            else                           $main_path = "/Volumes/AKiTiO4/other_files/MarineGeo/";
            if(!is_dir($main_path)) mkdir($main_path);
            
            $path = $main_path."Bold2iNat/";
            if(!is_dir($path)) mkdir($path);
            $this->path['image_folder'] = $path;
            
            $path = $main_path."TSVs/";
            if(!is_dir($path)) mkdir($path);
            $this->path['TSV_folder'] = $path;

            $path = $main_path."summary/";
            if(!is_dir($path)) mkdir($path);
            $this->path['summary_folder'] = $path;
            
            $this->html['by processid'] = 'http://www.boldsystems.org/index.php/Public_RecordView?processid=PROCESS_ID';
        }
        /* ============================= END for bold2inat ============================= */
    }
    private function summary_report($what, $arr = array())
    {
        $filename = $this->manual_entry->Proj."_".str_replace(' ','_',$this->manual_entry->Taxon).".tsv";
        $tsvfile = $this->path['summary_folder'] . $filename;
        $headers = array('scientificName', 'rank', 'iNat_taxonID', 'iNat_Observation_ID', 'date_collected', 'iNat_desc', 'lat', 'lon', 'iNat_place_guess', 'iNat_photo_record_IDs', 
        'iNat_photo_IDs', 'image_urls');
        if($what == 'initialize') {
            $WRITE = Functions::file_open($tsvfile, "w");
            fwrite($WRITE, implode("\t", $headers) . "\n");
            fclose($WRITE);
        }
        elseif($what == 'write row') {
            $rek = $arr['rek'];
            $ret_photo_record_ids = $arr['ret_photo_record_ids'];
            /*
            Array(
                [sciname] => Abudefduf sordidus
                [rank] => species
                [iNat_taxonID] => 123922
                [iNat_desc] => Hawaii, Oahu, Kaneohe Bay, He`eia fish pond. Collected between 0-3 meters. Identified by: Zeehan Jaafar.
                [coordinates] => Array(
                        [lat] => 21.4372
                        [lon] => -157.806
                    )
                [iNat_place_guess] => Hawaii, Oahu, Kaneohe Bay, He`eia fish pond.
                [image_urls] => Array(
                        [0] => http://www.boldsystems.org/pics/KANB/USNM_442262_photograph_KB17_089_110mmSL_LRP_17_13+1507843006.JPG
                    )
                [date_collected] => 2017-05-23
            )
            Array(
                [0] => Array(
                        [iNat_item_id] => 55686716
                        [photo_id] => 60119007
                    )
            )
            */
            foreach($ret_photo_record_ids as $r) {
                $photo_record_ids[] = $r['iNat_item_id'];
                $photo_ids[] = $r['photo_id'];
            }
            $row = array($rek['sciname'], $rek['rank'], $rek['iNat_taxonID'], $rek['observation_id'], $rek['date_collected'], $rek['iNat_desc'], 
            $rek['coordinates']['lat'], $rek['coordinates']['lon'], $rek['iNat_place_guess'], implode("|", $photo_record_ids), implode("|", $photo_ids), implode("|", $rek['image_urls']));
            $WRITE = Functions::file_open($tsvfile, "a");
            fwrite($WRITE, implode("\t", $row) . "\n");
            fclose($WRITE);
        }
    }
    function start($filename = false, $form_url = false, $uuid = false, $json = false)
    {
        if($this->app == 'bold2inat') {
            if($json) {
                $this->manual_entry = json_decode($json); //for specimen_image_export
                self::summary_report('initialize');
                // print_r($this->manual_entry); exit("\n-end 1-\n");
                if($recs_count = self::generate_info_list_tsv($this->manual_entry->Proj, $this->manual_entry->Taxon)) {
                    echo "\nTSV file total rows: $recs_count\n";
                    self::process_project_tsv_file($this->manual_entry->Proj, $this->manual_entry->Taxon); //main loop for the TSV file
                }
                else echo "\nNo records found. Please modify search parameters.\n";
            }
        }

        /* not used here...
        // for $form_url:
        if($form_url && $form_url != '_') $filename = self::process_form_url($form_url, $uuid); //this will download (wget) and save file in /specimen_export/temp/
        
        if(pathinfo($filename, PATHINFO_EXTENSION) == "zip") { //e.g. input.xlsx.zip
            $filename = self::process_zip_file($filename);
        }
        
        if(!$filename) $filename = 'input.xlsx'; //kinda debug mode. not used in real operation. But ok to stay un-commented.
        $input_file = $this->input['path'].$filename; //e.g. $filename is 'input_Eli.xlsx'
        if(file_exists($input_file)) {
            $this->resource_id = pathinfo($input_file, PATHINFO_FILENAME);
            self::read_input_file($input_file); //writes to text files for reading in next step.
            self::create_output_file();
        }
        else debug("\nInput file not found: [$input_file]\n");
        */
    }
    // ==========================================START bold2inat==============================================
    private function process_project_tsv_file($proj, $taxon)
    {
        $taxon = str_replace(' ', '_', $taxon);
        $local_tsv = $this->path['TSV_folder'].$proj."_$taxon.tsv";
        $i = 0; $count = 0;
        foreach(new FileIterator($local_tsv) as $line_number => $line) {
            $line = explode("\t", $line); $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [processid] => KANB003-17
                    [sampleid] => AH9BK85
                    [recordID] => 8341167
                    [catalognum] => USNM:FISH:442211
                    [fieldnum] => KB17-037
                    [institution_storing] => Smithsonian Institution National Museum of Natural History
                    [collection_code] => 
                    [bin_uri] => BOLD:AAB6123
                    [phylum_taxID] => 18
                    [phylum_name] => Chordata
                    [class_taxID] => 77
                    [class_name] => Actinopterygii
                    [order_taxID] => 775026
                    [order_name] => Ovalentaria
                    [family_taxID] => 1854
                    [family_name] => Pomacentridae
                    [subfamily_taxID] => 64350
                    [subfamily_name] => Pomacentrinae
                    [genus_taxID] => 3584
                    [genus_name] => Abudefduf
                    [species_taxID] => 11479
                    [species_name] => Abudefduf sordidus
                    [subspecies_taxID] => 
                    [subspecies_name] => 
                    [identification_provided_by] => Diane E. Pitassy
                    [identification_method] => 
                    [identification_reference] => (Forsskål, 1775)
                    [tax_note] => 
                    [voucher_status] => vouchered:registered collection
                    [tissue_type] => 
                    [collection_event_id] => 
                    [collectors] => J. Zill, L. Parenti, K. Cole, Z. Jaafar, D. Pitassy & H. Banshaw
                    [collectiondate_start] => 
                    [collectiondate_end] => 
                    [collectiontime] => 
                    [collection_note] => Collected between 0-5 meters.
                    [site_code] => 
                    [sampling_protocol] => 
                    [lifestage] => 
                    [sex] => 
                    [reproduction] => 
                    [habitat] => 
                    [associated_specimens] => 
                    [associated_taxa] => 
                    [extrainfo] => SL=155 mm.
                    [notes] => 
                    [lat] => 21.444
                    [lon] => -157.796
                    [coord_source] => 
                    [coord_accuracy] => 
                    [elev] => 
                    [depth] => 
                    [elev_accuracy] => 
                    [depth_accuracy] => 5
                    [country] => United States
                    [province_state] => Hawaii
                    [region] => 
                    [sector] => 
                    [exactsite] => Hawaii, Oahu, Kaneohe Bay, Coconut Island, windwar
                    [image_ids] => 3206805
                    [image_urls] => http://www.boldsystems.org/pics/KANB/USNM_442211_photograph_KB17_037_155mmSL_LRP_17_07+1507842962.JPG
                    [media_descriptors] => Lateral
                    [captions] => USNM 442211 photograph lateral view
                    [copyright_holders] => 
                    [copyright_years] => 2017
                    [copyright_licenses] => CreativeCommons ? Attribution Non-Commercial (by-nc)
                    [copyright_institutions] => Smithsonian Institution National Museum of Natural History
                    [photographers] => Diane E. Pitassy
                )*/
                $rek = array();
                if($rek = self::get_name_from_names($rec)) {
                    // print_r($rec); exit;
                    debug("\nSearching for '$rek[sciname]' with rank '$rek[rank]'\n");
                    $rek['iNat_taxonID'] = self::get_iNat_taxonID($rek);
                    $rek['iNat_desc'] = self::get_iNat_desc($rec);
                    $rek['coordinates'] = self::get_coordinates($rec);
                    $rek['iNat_place_guess'] = $rec['exactsite'];
                    $rek['image_urls'] = self::get_image_urls($rec);
                    $rek['date_collected'] = self::get_date_collected_from_html($rec);
                    $count++;
                    self::save_observation_and_images_2iNat($rek, $rec);
                }
                // else exit("\nInvestigate: cannot get name.\n"); //Should be commented. Since there are recs now that should be excluded.
            }
        }
        echo "\nTotal records: [$count]\n";
    }
    private function save_observation_and_images_2iNat($rek, $rec)
    {   echo "\nStart saving...\n";
        //step 1: save image(s) locally
        foreach($rek['image_urls'] as $url) {
            $rek['local_paths'][] = self::save_image_to_local($url);
        }
        print_r($rek); //good debug
        //step 2: save observation to iNat
        $observation_id = self::save_observation_2iNat($rek, $rec);
        //step 3: save image(s) to iNat
        $ret_photo_record_ids = array();
        if($observation_id) {
            $ret_photo_record_ids = self::save_images_2iNat($observation_id, $rec, $rek);
        }
        else echo "\nInvestigate: blank observation_id\n";
        echo "\nobservation_id: [$observation_id]\n"; print_r($ret_photo_record_ids); //good debug
        $rek['observation_id'] = $observation_id;
        self::summary_report('write row', array('rek' => $rek, 'ret_photo_record_ids' => $ret_photo_record_ids));
    }
    private function save_images_2iNat($observation_id, $rec, $rek)
    {
        // print_r($rec); echo "\n$observation_id\n"; exit;
        //step 1: build info
        $info = self::build_image_info($rec, $rek['local_paths']);
        //step 2: save image(s)
        $ret_photo_record_ids = self::upload_images_2iNat($observation_id, $info);
        return $ret_photo_record_ids; //for stats
    }
    private function upload_images_2iNat($observation_id, $info)
    {   //print_r($info); exit("\n--[$observation_id]--\n");
        /*Array(
            [0] => Array(
                    [image_ids] => 3206817
                    [image_urls] => http://www.boldsystems.org/pics/KANB/USNM_442246_photograph_KB17_073_110.5mmSL_LRP_17_13+1507842990.JPG
                    [media_descriptors] => Lateral
                    [captions] => USNM 442246 photograph lateral view
                    [copyright_years] => 2017
                    [copyright_licenses] => CreativeCommons � Attribution Non-Commercial (by-nc)
                    [copyright_institutions] => Smithsonian Institution National Museum of Natural History
                    [photographers] => Diane E. Pitassy
                    [license] => CC-BY-NC
                    [local_path] => /Volumes/AKiTiO4/other_files/MarineGeo/Bold2iNat/0c/ac/USNM_442246_photograph_KB17_073_110.5mmSL_LRP_17_13+1507842990.JPG
                )
        )*/
        $ret_photo_record_ids = array();
        foreach($info as $r) {
            /*curl --verbose \
              --header 'Authorization: YOUR_JWT' \
              -F observation_photo[observation_id]=OBSERVATION_ID \
              -F file=@/path/to/local/photo.jpg \
              https://api.inaturalist.org/v1/observation_photos
            */
            
            $id_arr = array();
            $id_arr['observation_id'] = $observation_id;
            $id_arr['image_id'] = $r['image_ids'];
            $id_arr['image_url'] = $r['image_urls'];
            
            /* Check if photo was already added in iNat */
            $photo_local_id = md5(json_encode($id_arr));
            // echo "\nphoto_local_id: [$photo_local_id]";
            if($ret_arr = self::item_already_saved_in_iNat($photo_local_id, 'photo')) {
                $iNat_photo_id = $ret_arr['iNat_item_id'];
                echo " --> Photo ALREADY saved in iNat. iNat Photo ID:[$iNat_photo_id]\n";
                /* return $iNat_photo_id; */ //Fatal to do a return here. It will break the loop.
                $ret_photo_record_ids[] = $ret_arr;
                continue;
            }
            else echo " --> New photo, proceed saving to iNat...\n";
            
            /* Start adding photo via API */
            $YOUR_JWT = $this->manual_entry->JWT;
            $token_type = $this->manual_entry->token_type;

            /* caused 'server internal error'
            -F observation_photo[uuid]=$r[uuid] \
            */
            // $cmd = "curl --verbose \
            $cmd = "curl -s \
                --header 'Authorization: $token_type $YOUR_JWT' \
                -F observation_photo[observation_id]=$observation_id \
                -F file=@$r[local_path] \
                https://api.inaturalist.org/v1/observation_photos";

            $cmd .= " 2>&1";
            // echo "\n$cmd\n"; //good debug
            
            // /*
            $shell_debug = shell_exec($cmd);
            echo "\n*------*\n".trim($shell_debug)."\n*------*\n"; //good debug
            if(stripos($shell_debug, '{"error":{"original":{"error"') !== false) echo("\n<i>Has error: Invetigate build no. in Jenkins.</i>\n\n"); //string is found
            else {
                $ret = self::parse_shell_debug($shell_debug);
                if(isset($ret->error)) return false;
                if(isset($ret->id)) {
                    if($ret->id) {
                        echo "\nSaved NEW photo. Photo ID: ".$ret->id."\n";
                        // self::flag_local_sys_this_item_was_saved_in_iNat($ret->id, $photo_local_id, 'photo');
                        self::flag_local_sys_this_item_was_saved_in_iNat($ret, $photo_local_id, 'photo');
                        /* return $ret->id; */ //Fatal to do a return here. It will break the loop.
                        $ret_arr = array();
                        $ret_arr['iNat_item_id'] = $ret->id;
                        $ret_arr['photo_id'] = $ret->photo_id;
                        $ret_photo_record_ids[] = $ret_arr;
                        continue;
                    }
                }
            }
            // */
            
        }
        return $ret_photo_record_ids;
    }
    private function save_observation_2iNat($rek, $rec)
    {   // print_r($rek); exit("\n--elix--\n");
        /*Array(
            [sciname] => Lutjanus fulvus
            [rank] => species
            [iNat_taxonID] => 121459
            [iNat_desc] => Hawaii, Oahu, Kaneohe Bay, He`eia fish pond. Collected between 0-3 meters. Identified by: Zeehan Jaafar.
            [coordinates] => Array(
                    [lat] => 21.4372
                    [lon] => -157.806
                )
            [image_urls] => Array(
                    [0] => http://www.boldsystems.org/pics/KANB/USNM_442246_photograph_KB17_073_110.5mmSL_LRP_17_13+1507842990.JPG
                )
            [local_paths] => Array(
                    [0] => /Volumes/AKiTiO4/other_files/MarineGeo/Bold2iNat/0c/ac/USNM_442246_photograph_KB17_073_110.5mmSL_LRP_17_13+1507842990.JPG
                )
        )*/
        /* from Ken-ichi:
        curl --verbose \
          --header 'Authorization: YOUR_JWT' \
          -d '{"observation": {"description": "test observation"}}' \
          https://api.inaturalist.org/v1/observations
        */
        /* other fields from iNat, not used atm.
        observation[observed_on_string]=2013-01-03&
        observation[time_zone]=Eastern+Time+(US+%26+Canada)&
        observation[tag_list]=foo,bar&
        observation[place_guess]=clinton,+ct&
        observation[map_scale]=11&
        observation[location_is_exact]=false&
        observation[positional_accuracy]=7798&
        observation[geoprivacy]=obscured&
        observation[observation_field_values_attributes][0][observation_field_id]=
        */
        $input_arr['observation'] = array(
            'species_guess' => $rek['sciname'],
            'taxon_id' => $rek['iNat_taxonID'],
            'description' => $rek['iNat_desc'],
            'latitude' => $rek['coordinates']['lat'],
            'longitude' => $rek['coordinates']['lon'],
            'place_guess' => $rek['iNat_place_guess'],
            'observed_on_string' => $rek['date_collected']
        );
        
        // http://v3.boldsystems.org/index.php/API_Public/specimen?format=tsv&ids=KANB014-17
       // http://v3.boldsystems.org/index.php/API_Public/specimen?format=json&ids=KANB003-17
       // http://www.boldsystems.org/index.php/API_Public/specimen?ids=KANB003-17&format=json
        // ids=ACRJP618-11|ACRJP619-11 returns records matching these Process IDs.
        
        $id_arr = $rec;
        /* Check if observation was already added in iNat */
        $json = self::customize_json_encode_observation($id_arr);
        // echo "\njson = [$json]\n";  exit;
        $observation_local_id = md5($json);
        // echo "\nobservation_local_id: [$observation_local_id]";
        if($iNat_observation_id = self::item_already_saved_in_iNat($observation_local_id, 'observation')) {
            echo " --> Observation ALREADY saved in iNat. iNat Observation ID:[$iNat_observation_id]\n";
            return $iNat_observation_id;
        }
        else echo " --> New observation, proceed saving to iNat...\n";
        /* Start adding observation via API */
        $json = Functions::json_encode_decode($input_arr, 'encode');
        $YOUR_JWT = $this->manual_entry->JWT;
        $token_type = $this->manual_entry->token_type;
        // $cmd = "curl --verbose \
        $cmd = "curl -s \
              --header 'Authorization: $token_type $YOUR_JWT' \
              -d '$json' \
              https://api.inaturalist.org/v1/observations";
        $cmd .= " 2>&1";
        echo "\n$cmd\n"; //good debug

        // /*
        $shell_debug = shell_exec($cmd);
        echo "\n*------*\n".trim($shell_debug)."\n*------*\n"; //good debug
        if(stripos($shell_debug, '{"error":{"original":{"error"') !== false) echo("\n<i>Has error: Invetigate build no. in Jenkins.</i>\n\n"); //string is found
        else {
            $ret = self::parse_shell_debug($shell_debug);
            if(isset($ret->error)) return false;
            if(isset($ret->id)) {
                if($ret->id) {
                    echo "\nSaved NEW observation. Observation ID: ".$ret->id."\n";
                    self::flag_local_sys_this_item_was_saved_in_iNat($ret->id, $observation_local_id, 'observation');
                    return $ret->id;
                }
                else echo "\nInvestigate: observation ID is blank from iNat API result\n";
            }
            else echo "\nInvestigate: no observation ID detected from iNat API result\n";
        }
        // */
    }
    private function parse_shell_debug($json)
    {   
        /* First option: worked locally but gives this error in eol-archive:
        // JSON [decode] error: Syntax error, malformed JSON
        // Investigate: iNat API result is invalid json string.
        
        // the last row before the json api output:
        // * Connection #0 to host api.inaturalist.org left intact
        // if(preg_match("/left intact(.*?)xxx/ims", $str.'xxx', $arr)) {
        if(preg_match("/\{\"id\"\:(.*?)xxx/ims", $str.'xxx', $arr)) {
            $json = trim('{"id":'.$arr[1]);
            if($arr = Functions::json_encode_decode($json, 'decode')) {
                print_r($arr); //debug only
                return $arr;
            }
            else exit("\nInvestigate: iNat API result is invalid json string.\n");
        }
        else exit("\nInvestigate: iNat API shell_debug is invalid.\n");
        */
        
        if($arr = Functions::json_encode_decode($json, 'decode')) {
            print_r($arr); //debug only
            return $arr;
        }
        else exit("\nInvestigate: iNat API result is invalid json string.\n");

    }
    private function flag_local_sys_this_item_was_saved_in_iNat($iNat_item_id, $local_item_id, $what)
    {
        if($what == 'observation') {
            $arr['iNat_item_id'] = $iNat_item_id;
            $json = json_encode($arr);
        }
        elseif($what == 'photo') {
            $ret = $iNat_item_id;
            $arr['iNat_item_id'] = $ret->id;
            $arr['photo_id'] = $ret->photo_id;
            $json = json_encode($arr);
        }
        $path = self::build_path($local_item_id);
        if(!file_exists($path)) { //should not exist at this point
            if($FILE = Functions::file_open($path, 'w')) {
                fwrite($FILE, $json); fclose($FILE);
                debug("\nLocal sys flagged: [$path]\n");
            }
        }
        else exit("\nInvestigate went here [$iNat_item_id] [$local_item_id]\n");
        // $iNat_observ_id, $local_observation_id
    }
    private function item_already_saved_in_iNat($item_id, $what)
    {
        $path = self::build_path($item_id);
        debug("\ndebug: $path\n"); //debug only
        if(file_exists($path)) {
            $json = trim(file_get_contents($path));
            $arr = json_decode($json, true);
            if($what == 'observation') {
                return $arr['iNat_item_id'];
            }
            elseif($what == 'photo') {
                return $arr; //array('iNat_item_id' => xx, 'photo_id' => yy)
            }
        }
        return false;
        // $observation_id
    }
    private function build_path($id)
    {
        $options['cache_path'] = $this->path['image_folder'];
        $filename = "$id.txt";
        $md5 = $id;
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        return $options['cache_path'] . "$cache1/$cache2/$filename";
    }
    private function save_image_to_local($url)
    {
        $options['cache_path'] = $this->path['image_folder'];
        $filename = pathinfo($url, PATHINFO_BASENAME);
        $md5 = md5($filename);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$filename";
        if(file_exists($cache_path)) {} //echo("\nFile already exists\n");
        else {
            echo("\nSaving photo to local...\n");
            self::save_image($url, $cache_path);
        }
        return $cache_path;
    }
    private function save_image($url, $target)
    {   //wget -nc http://www.boldsystems.org/pics/KANB/USNM_442211_photograph_KB17_037_155mmSL_LRP_17_07+1507842962.JPG -O /Volumes/AKiTiO4/other_files/xxx/file.ext
        $cmd = WGET_PATH . " $url -O ".$target; //wget -nc --> means 'no overwrite'
        $cmd .= " 2>&1";
        $shell_debug = shell_exec($cmd);
        if(stripos($shell_debug, "ERROR 404: Not Found") !== false) echo("\n<i>URL path does not exist.\n$url</i>\n\n"); //string is found
        echo "\n---\n".trim($shell_debug)."\n---\n";
    }
    private function build_image_info($rec, $local_paths)
    {
        $fields = array('image_ids', 'image_urls', 'media_descriptors', 'captions', 'copyright_holders', 'copyright_years', 'copyright_licenses', 'copyright_institutions', 'photographers');
        foreach($fields as $fld) {
            if($val = $rec[$fld]) {
                $arr = explode("|", $val);
                $arr = array_map('trim', $arr);
                $i = -1;
                foreach($arr as $a) { $i++;
                    $info[$i][$fld] = $a;
                }
            }
        }
        $i = -1;
        foreach($info as $r) { $i++;
            $info[$i]['license'] = self::get_license($r['copyright_licenses']);
            $info[$i]['local_path'] = $local_paths[$i];
            $info[$i]['uuid'] = $this->manual_entry->Proj."_".$r['image_ids'];
        }
        // print_r($info); exit;
        return $info;
    }
    private function get_image_urls($rec)
    {   /* These fields are pipe "|" delimited if there are multiple images:
        image_ids	image_urls	media_descriptors	captions	copyright_holders	copyright_years	copyright_licenses	copyright_institutions	photographers
        */
        if($val = $rec['image_urls']) {
            $arr = explode("|", $val);
            $arr = array_map('trim', $arr);
            // print_r($arr);
            return $arr;
        }
    }
    private function get_license($str)
    {
        if(preg_match("/\((.*?)\)/ims", $str, $arr)) {
            $tmp = trim(strtoupper($arr[1]));
            if($tmp) {
                if(substr($tmp,0,3) == 'CC-') return $tmp;
                else                          return 'CC-'.$tmp;
            }
        }
    }
    private function get_coordinates($rec)
    {
        if($rec['lat'] && $rec['lon']) {
            return array('lat' => $rec['lat'], 'lon' => $rec['lon']);
        }
    }
    private function get_iNat_desc($rec)
    {
        $tmp = trim("$rec[exactsite]. $rec[collection_note].");
        if($val = $rec['identification_provided_by']) $tmp .= " Identified by: $val.";
        $tmp = Functions::remove_whitespace(trim($tmp));
        $tmp = str_replace("..", ".", $tmp);
        return $tmp;
    }
    private function get_iNat_taxonID($rek)
    {   /* Array(
            [sciname] => Zebrasoma flavescens
            [rank] => species
        )*/
        $url = str_replace('NAME_STR', $rek['sciname'], $this->inat_service['taxa']);
        $url = str_replace('RANK_STR', $rek['rank'], $url);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $arr = json_decode($json, true);
            foreach($arr['results'] as $r) {
                if($r['name'] == $rek['sciname']) return $r['id'];
            }
        }
    }
    private function get_name_from_names($rec)
    {
        /*   [phylum_taxID] => 18
             [phylum_name] => Chordata
             [class_taxID] => 77
             [class_name] => Actinopterygii
             [order_taxID] => 747044
             [order_name] => Acanthuriformes
             [family_taxID] => 440
             [family_name] => Acanthuridae
             [subfamily_taxID] => 34529
             [subfamily_name] => Acanthurinae
             [genus_taxID] => 3674
             [genus_name] => Zebrasoma
             [species_taxID] => 47230
             [species_name] => Zebrasoma flavescens
             [subspecies_taxID] => 
             [subspecies_name] => 
        */
        $keys = array('subspecies_name', 'species_name', 'genus_name', 'subfamily_name', 'family_name', 'order_name', 'class_name', 'phylum_name');

        // /* New: further filter the names vs. the form-manual-entered Taxon value.
        if($this->manual_entry->Taxon) {
            $cont = false;
            foreach($keys as $key) {
                if($val = $rec[$key]) {
                    if(stripos($val, $this->manual_entry->Taxon) !== false) { //string is found
                        $cont = true;
                        break;
                    }
                }
            }
            if(!$cont) return false;
        }
        // */

        foreach($keys as $key) {
            if($val = $rec[$key]) {
                $final = array();
                $final['sciname'] = $val;
                $final['rank'] = str_replace('_name', '', $key);
                $final = array_map('trim', $final);
                return $final;
            }
        }
    }
    private function show_total_rows($file)
    {
        if(file_exists($file)) {
            $total = shell_exec("wc -l < ".escapeshellarg($file));
            return trim($total);
        }
    }
    private function customize_json_encode_observation($arr)
    {
        if($json = Functions::json_encode_decode($arr, 'encode')) return $json;
        else {
            $arr['copyright_licenses'] = '';
            if($json = Functions::json_encode_decode($arr, 'encode')) return $json;
            else {
                exit("\nInvestigate: problem with arr-to-json. Inform eagbayani@eol.org\n");
            }
        }
    }
    private function get_date_collected_from_html($rec)
    {
        /*td width="126"><b>Date Collected:</b></td>
        <td>2017-05-23</td></tr>*/
        $process_id = $rec['processid'];
        if($val = @$this->info_date_collected[$process_id]) return $val;
        
        $url = str_replace('PROCESS_ID', $process_id, $this->html['by processid']);
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($url, $options)) {
            if(preg_match("/Date Collected\:<\/b>(.*?)<\/tr>/ims", $html, $arr)) {
                if($date = trim(strip_tags($arr[1]))) {
                    $this->info_date_collected[$process_id] = $date;
                    return $date;
                }
            }
        }
    }
    // ==========================================END bold2inat==============================================
    private function process_form_url($form_url, $uuid)
    {   //wget -nc https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /Volumes/AKiTiO4/other_files/bundle_images/xxx/740.027116-1.jpg
        $ext = pathinfo($form_url, PATHINFO_EXTENSION);
        $target = $this->input['path'].$uuid.".".$ext;
        $cmd = WGET_PATH . " $form_url -O ".$target; //wget -nc --> means 'no overwrite'
        $cmd .= " 2>&1";
        $shell_debug = shell_exec($cmd);
        if(stripos($shell_debug, "ERROR 404: Not Found") !== false) exit("\n<i>URL path does not exist.\n$form_url</i>\n\n"); //string is found
        echo "\n---\n".trim($shell_debug)."\n---\n"; //exit;
        return pathinfo($target, PATHINFO_BASENAME);
    }
    private function process_zip_file($filename)
    {
        $test_temp_dir = create_temp_dir();
        $local = Functions::save_remote_file_to_local($this->input['path'].$filename);
        $output = shell_exec("unzip -o $local -d $test_temp_dir");
        // echo "<hr> [$output] <hr>";
        $ext = "xls";
        $new_local = self::get_file_inside_dir_with_this_extension($test_temp_dir."/*.$ext*");
        $new_local_ext = pathinfo($new_local, PATHINFO_EXTENSION);
        $destination = $this->input['path'].pathinfo($filename, PATHINFO_FILENAME).".$new_local_ext";
        /* debug only
        echo "\n\nlocal file = [$local]";
        echo "\nlocal dir = [$test_temp_dir]";
        echo "\nnew local file = [$new_local]";
        echo "\nnew_local_ext = [$new_local_ext]\n\n";
        echo "\ndestination = [$destination]\n\n";
        */
        Functions::file_rename($new_local, $destination);
        if($GLOBALS['ENV_DEBUG']) print_r(pathinfo($destination));

        //remove these 2 that were used above if file is a zip file
        unlink($local);
        recursive_rmdir($test_temp_dir);

        return pathinfo($destination, PATHINFO_BASENAME);
    }
    private function get_file_inside_dir_with_this_extension($files)
    {
        $arr = glob($files);
        return $arr[0];
        // foreach (glob($files) as $filename) echo "\n- $filename\n";
    }
    /* =======================================START create output file======================================= */
    private function create_output_file()
    {
        if($this->app == 'specimen_image_export') {
            $this->labels['Sheet1'] = array('Lab Sheet' => $this->labels_Lab_Sheet, 'Sheet1' => $this->labels['Sheet1']['MOOP']);
            // print_r($this->labels); exit;
        }
        require_library('MarineGEO_XLSParser');
        $parser = new MarineGEO_XLSParser($this->labels, $this->resource_id, $this->app);
        if($this->app == 'specimen_export') $parser->create_specimen_export(); //creates to final xls
        elseif($this->app == 'specimen_image_export') $parser->create_specimen_image_export(); //creates the final xls
    }
    /* ========================================END create output file======================================== */
    private function read_input_file($input_file)
    {
        $final = array();
        require_library('XLSParser'); $parser = new XLSParser();
        debug("\n reading: " . $input_file . "\n");

        // $temp = $parser->convert_sheet_to_array($input_file); //automatically gets 1st sheet
        // $temp = $parser->convert_sheet_to_array($input_file, '3'); //gets the 4th sheet. '0' gets the 1st sheet.
        
        $sheet_names = $this->input['worksheets'];
        foreach($sheet_names as $sheet_name) self::read_worksheet($sheet_name, $input_file, $parser);
        
        if($this->app == 'specimen_image_export') self::generate_Lab_Sheet_Worksheet();
    }
    private function read_worksheet($sheet_name, $input_file, $parser)
    {
        self::initialize_file($sheet_name);
        $temp = $parser->convert_sheet_to_array($input_file, NULL, NULL, false, $sheet_name);
        $headers = array_keys($temp);
        // print_r($temp); print_r($headers); exit;
        
        $fld = $headers[0]; $i = -1;
        foreach($temp[$fld] as $col) { $i++;
            $input_rec = array();
            
            //START check if entire row is blank, if yes then ignore row
            $ignoreRow = true;
            foreach($headers as $header) {
                if($val = trim(@$temp[$header][$i])) {
                    $ignoreRow = false;
                    break;
                }
            }
            //END check if entire row is blank, if yes then ignore row

            if(!$ignoreRow) {
                foreach($headers as $header) $input_rec[$header] = $temp[$header][$i];
                // echo "\ncount $i\n";
                // print_r($input_rec); //exit;
                $output_rec = self::compute_output_rec($input_rec, $sheet_name);
                self::write_output_rec_2txt($output_rec, $sheet_name);
            }
        }
    }
    private function initialize_file($sheet_name)
    {
        $filename = $this->resources['path'].$this->resource_id."_".str_replace(" ", "_", $sheet_name).".txt";
        $WRITE = Functions::file_open($filename, "w");
        fclose($WRITE);
    }
    private function write_output_rec_2txt($rec, $sheet_name)
    {
        $filename = $this->resources['path'].$this->resource_id."_".str_replace(" ", "_", $sheet_name).".txt";
        $fields = array_keys($rec);
        $WRITE = Functions::file_open($filename, "a");
        clearstatcache(); //important for filesize()
        if(filesize($filename) == 0) fwrite($WRITE, implode("\t", $fields) . "\n");
        $save = array();
        foreach($fields as $fld) $save[] = $rec[$fld];
        fwrite($WRITE, implode("\t", $save) . "\n");
        fclose($WRITE);
    }
    private function compute_output_rec($input_rec, $sheet_name)
    {
        $output_rec = array();
        $subheads = array_keys($this->labels[$sheet_name]);
        foreach($subheads as $subhead) {
            $fields = $this->labels[$sheet_name][$subhead]; //print_r($fields); exit;
            foreach($fields as $field) {
                if($this->app == 'specimen_export') $output_rec[$field] = self::construct_output($sheet_name, $field, $input_rec);
                else                                $output_rec[$field] = self::construct_output_image($sheet_name, $field, $input_rec);
            }
        }
        // print_r($output_rec); exit("\nstopx\n");
        if($this->app == 'specimen_image_export') {
            // echo "\n$sheet_name\n"; print_r($output_rec); //good debug
            $this->save_ProcessID_from_MOOP[$output_rec['Process Id']] = '';
        }
        return $output_rec;
    }
    private function construct_output_image($sheet_name, $field, $input_rec)
    {   //echo "\n[$sheet_name]\n"; echo "\n[$field]\n"; print_r($input_rec); exit;
        /*  [Sheet1]
            [Image File]
            Array(
                [IRB] => 12307479
                [Description] => TL=457.2 mm. Photographed during Kaneohe Bay, Hawaii, Bioblitz expedition, 2017. Specimen voucher field number: KB17-032
                [Title: (Resource Information)] => Diodon hystrix USNM 442206 photograph dorsal view
                [Creator: (Resource Information)] => Parenti, Lynne R.
            )
        */
        $ret_Title = self::parse_Title($input_rec['Title: (Resource Information)']);
        $ret_Desc = self::parse_Description($input_rec['Description']);
        $ret_Creator = self::parse_Creator($input_rec['Creator: (Resource Information)']);
        switch ($field) {
            case "Image File";          return "https://collections.nmnh.si.edu/search/".$this->dept_map[$this->manual_entry->Dept]."/search.php?action=10&width=640&irn=".$input_rec['IRB'];
            case "Original Specimen";   return 'Yes';
            case "View Metadata";       return $ret_Title['View Metadata'];
            case "Caption";             return $ret_Title['Caption'];
            case "Measurement";         return $ret_Desc['Measurement'];
            case "Measurement Type";    return $ret_Desc['Measurement Type'];
            case "Sample Id";           return $ret_Title['Sample Id'];
            case "Process Id";          return $ret_Title['Process Id'];
            case "License Holder";      return ''; //leave blank per Jira
            case "License";             return $this->manual_entry->Lic;
            case "License Year";        return $this->manual_entry->Lic_yr;
            case "License Institution"; return $this->manual_entry->Lic_inst;
            case "License Contact";     return $this->manual_entry->Lic_cont;
            case "Photographer";        return $ret_Creator['Photographer'];
            default:
                exit("\nInvestigate field [$sheet_name] [$field] not defined.\n");
        }
    }
    /* ========================================================== START for image_export ========================================================== */
    private function generate_Lab_Sheet_Worksheet()
    {
        $this->info_catalognum = NULL; //purge
        $filename = $this->resources['path'].$this->resource_id."_".str_replace(" ", "_", 'Lab_Sheet').".txt";
        $fields = $this->labels_Lab_Sheet;
        $WRITE = Functions::file_open($filename, "w");
        fwrite($WRITE, implode("\t", $fields) . "\n");
        if($loop = @$this->info_processid) {
            foreach($loop as $processid => $rek) {
                if(isset($this->save_ProcessID_from_MOOP[$processid])) { //this will limit only 'Process Id' that appears in MOOP.
                    $save = array($processid, $rek['sampleid'], $rek['fieldnum']);
                    fwrite($WRITE, implode("\t", $save) . "\n");
                }
            }
        }
        fclose($WRITE);
    }
    private function parse_Creator($str)
    {
        /* Photographer:
        from the image_input file, column "Creator: (Resource Information)", take the whole string. If it contains a comma followed by a space (only once in the string), 
        use this as a separator, reverse the order of the segments, and separate them by a space, eg: "Pitassy, Diane E." -> "Diane E. Pitassy". 
        If the string contains multiple commas, just leave it as is. */
        // $str = "Parenti, Lynne R., Eli"; //debug only
        $arr = explode(",", $str);
        $arr = array_map('trim', $arr);
        if(count($arr) <= 2) $final['Photographer'] = trim($arr[1].' '.$arr[0]);
        else $final['Photographer'] = $str;
        return $final;
    }
    private function parse_Title($str)
    {   /* View Metadata:
        from the image_input file, column "Title: (Resource Information)", everything that follows the string " photograph ", 
        eg: for "Diodon hystrix USNM 442206 photograph dorsal view", -> "dorsal view" */
        $arr = explode("photograph", $str);
        $final['View Metadata'] = trim($arr[count($arr)-1]);
        //----------------------------------------
        /* Caption:
        from the image_input file, column "Title: (Resource Information)", everything that follows the string "USNM", but include the string itself, 
        eg: "Diodon hystrix USNM 442206 photograph dorsal view", -> "USNM 442206 photograph dorsal view" */
        $arr = explode("USNM", $str);
        $final['Caption'] = 'USNM '.trim($arr[count($arr)-1]);
        //----------------------------------------
        /* Sample ID:
        from the image_input file, column "Title: (Resource Information)", find the number that follows the string "USNM". Using the string menu-selected for Department by the user, 
        construct a triple of the form: "USNM:FISH:442211". Then, in the BOLD API result, find the row containing that triple. Take the string from the "sampleid" column.
        */
        if(preg_match_all('((?:[0-9]+,)*[0-9]+(?:\.[0-9]+)?)', $final['Caption'], $arr)) {
            $numerical_part = $arr[0][0];
            $triple = "USNM:".$this->manual_entry->Dept.":$numerical_part";
            $ret = @$this->info_catalognum[$triple]; //catalognum from API is the $triple
            $final['Sample Id'] = @$ret['sampleid'];
            $final['Process Id'] = @$ret['processid'];
        }
        //----------------------------------------
        return $final;
    }
    private function parse_Description($orig)
    {   /* Measurement: e.g. "TL=457.2 mm. Photographed during Kaneohe Bay, Hawaii, Bioblitz expedition, 2017. Specimen voucher field number: KB17-032"
        from the image_input file, column "Description", look for "=" followed by (possibly whitespace followed by) a number, which may contain a decimal. 
        The number may be followed by (possibly whitespace followed by) up to three characters representing a unit. 
        That will be followed by ".", ";", "," and/or " " OR it could be the end of the input field. Take the number and the whitespace + up to 3chars, if present. 
        eg: for "TL=457.2 mm. Photographed during Kaneohe Bay, Hawaii, Bioblitz expedition, 2017. Specimen voucher field number: KB17-032" -> 457.3 mm */
        // $str = "TL=4,357.2 mm. Photographed during Kaneohe Bay, Hawaii, Bioblitz expedition, 2017. Specimen voucher field number: KB17-032";
        // $str = "TL=4,357.2 mm; Photographed";
        // $str = "TL=4,357.2 mm, Photographed";
        // $str = "TL=4,357.2 mm Photographed";
        // $str = "TL=4,357.2 mm";
        $str = $orig;
        $final = array();
        $tmp = explode("=", $str);
        $str = $tmp[1];
        // echo "\n[$str]\n";
        // if(preg_match_all('!\d+\.*\d*!', $str, $arr)) {
        if(preg_match_all('((?:[0-9]+,)*[0-9]+(?:\.[0-9]+)?)', $str, $arr)) {
            // print_r($arr);
            $number_str = $arr[0][0];
            // echo "\n[$number_str]\n";
            $str = trim(str_replace($number_str, '', $str));
            // echo "\n[$str]\n";
            $unit = '';
            $chars = array(".", ";", ",", " ");
            for($i = 0; $i <= strlen($str); $i++) {
                $char = substr($str,$i,1);
                if(in_array($char, $chars)) break;
                $unit .= $char;
            }
            // echo "\n[$unit]\n";
            $final['Measurement'] = "$number_str $unit";
        }
        else exit("\nTest this value: [$str]\n");
        //----------------------------------------
        /* Measurement Type:
        in the pattern match above, take whatever is to the left of the "=". If that string contains a separator ".", ";" or "," 
        take only what follows the last separator before the "=". 
        eg: for "TL=457.2 mm. Photographed during Kaneohe Bay, Hawaii, Bioblitz expedition, 2017. Specimen voucher field number: KB17-032" -> TL */
        $str = $orig;
        // $str = "TL=4,357.2 mm"; //debug only
        $tmp = explode("=", $str);
        if($tmp[0] && @$tmp[1]) {
            $str = trim($tmp[0]);
            // echo ("\n[$str]\n");
            $chars = array(".", ";", ",");
            foreach($chars as $char) {
                if(stripos($str, $char) !== false) { //string is found
                    $arr = explode($char, $str);
                    // print_r($arr);
                    $final['Measurement Type'] = end($arr); // exit("\n".$final['Measurement Type']."\n");
                    break;
                }
            }
            if(!@$final['Measurement Type']) $final['Measurement Type'] = $str;
        }
        //----------------------------------------
        return $final;
    }
    private function generate_info_list_tsv($project, $taxon) //e.g. $project = 'KANB'
    {
        $url = str_replace('PROJECT_CODE', $project, $this->api['BOLDS specimen']);
        $url = str_replace('TAXON_STR', str_replace(' ', '_', $taxon), $url);
        $local_tsv = self::download_tsv($url, $project, $taxon);
        $i = 0;
        foreach(new FileIterator($local_tsv) as $line_number => $line) {
            $line = explode("\t", $line); $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                if($val = $rec['catalognum']) $this->info_catalognum[$val] = array('sampleid' => $rec['sampleid'], 'processid' => $rec['processid']);
                /* Lab Sheet Worksheet:
                Process ID: List all the Process ID values used in the first worksheet.
                Sample ID: List each Sample ID with its corresponding Process ID from the other worksheet, or you can use the process ID value to find the row and take the string from the "sampleid" column
                Field ID: use the process ID value to find the row and take the string from the "fieldnum" column
                */
                if($val = $rec['processid']) $this->info_processid[$val] = array('sampleid' => $rec['sampleid'], 'fieldnum' => $rec['fieldnum']);
            }
        }
        
        $k = self::show_total_rows($local_tsv);
        $k = $k - 1; //don't count the headers
        // echo "\ntotal k: [$k]\n"; //debug
        $i = $i - 1 - 1;
        return $i;
    }
    private function download_tsv($form_url, $project, $taxon)
    {
        $taxon = str_replace(' ', '_', $taxon);
        $target = $this->path['TSV_folder'].$project."_$taxon.tsv";
        $cmd = WGET_PATH . " -nc '$form_url' -O ".$target; //wget -nc --> means 'no overwrite'
        $cmd .= " 2>&1";
        $shell_debug = shell_exec($cmd);
        if(stripos($shell_debug, "ERROR 404: Not Found") !== false) exit("\n<i>URL path does not exist.\n$form_url</i>\n\n"); //string is found
        echo "\n---\n".trim($shell_debug)."\n---\n";
        return $target;
    }
    /* ========================================================== END for image_export ========================================================== */
    
    private function construct_output($sheet_name, $field, $input_rec)
    {   /* Array(
        [Collector Number: (Version 1.2 elements (2))] => KB17-277
        [Sex: (Sex/Stage)] => F
        [Reproduction Description] => nonreproductive
        [Life Stage: (Version 1.3 changes (1))] => juv
        [Kind: (Measurements Details)] => 
        [Verbatim value: (Measurements Details)] => 
        [Unit: (Measurements Details)] => 
        [Note: (Note Details)] => Archival sample.
        [Secondary Sample Type] => Fin-clip
        [GUID: (GUIDs)] => ark:/65665/3d6ffd3e4-1188-40e4-848a-2f1dff71abe0
        )
        Array(
            [0] => Sample ID
            [1] => Sex
            [2] => Reproduction
            [3] => Life Stage
            [4] => Extra Info
            [5] => Notes
        )
        Array(
            [0] => Voucher Status
            [1] => Tissue Descriptor
            [2] => External URLs
            [3] => Associated Taxa
            [4] => Associated Specimens
        )*/
        switch ($field) {
            /*=====Voucher Data=====*/
            case "Sample ID":               return $input_rec['Collector Number: (Version 1.2 elements (2))'];
            case "Field ID":                return $input_rec['Collector Number: (Version 1.2 elements (2))'];
            case "Museum ID":               return $input_rec['Institution Code: (Version 1.2 elements (1))'].":FISH:".$input_rec['Catalog No.Text: (MaNIS extensions (1))'];
            case "Collection Code":         return '';
            case "Institution Storing":     return ''; //to be mapped
            /*=====Specimen Details=====*/
            case "Sample ID":               return $input_rec['Collector Number: (Version 1.2 elements (2))'];
            case "Sex":                     return $input_rec['Sex: (Sex/Stage)'];
            case "Reproduction":            return $input_rec['Reproduction Description'];
            case "Life Stage":              return $input_rec['Life Stage: (Version 1.3 changes (1))'];
            case "Extra Info":              return ''; //No Equivalent
            case "Notes":                   return $input_rec['Note: (Note Details)'];
            case "Voucher Status":          return ''; //No Equivalent
            case "Tissue Descriptor":       return ''; //to be mapped
            case "External URLs":           return self::prepend_guids($input_rec['GUID: (GUIDs)']); //"http://n2t.net/".$input_rec['GUID: (GUIDs)'];
            case "Associated Taxa":         return ''; //No Equivalent
            case "Associated Specimens":    return ''; //No Equivalent
            /*=====Taxonomy Data=====*/
            case "Phylum":                  return $input_rec['Phylum: (Version 1.2 elements (1))'];
            case "Class":                   return $input_rec['Class: (Version 1.2 elements (1))'];
            case "Order":                   return $input_rec['Order: (Version 1.2 elements (1))'];
            case "Family":                  return $input_rec['Family: (Version 1.2 elements (1))'];
            case "Subfamily":               return ''; //No Equivalent
            case "Tribe":                   return ''; //No Equivalent
            case "Genus":                   return $input_rec['Genus: (Version 1.2 elements (1))'];
            case "Species":                 return $input_rec['Species: (Version 1.2 elements (2))'];
            case "Subspecies":              return ''; //get from API
            case "Identifier":              return $input_rec['Identified By: (Version 1.2 elements (2))'];
            case "Identifier Email":        return ''; //No Equivalent
            case "Identification Method":   return ''; //No Equivalent
            case "Taxonomy Notes":          return ''; //No Equivalent
            /*=====Collection Data=====*/
            case "Collectors":      return $input_rec['Collector: (Version 1.2 elements (2))'];
            case "Collection Date": return self::format_Collection_Date($input_rec);
            case "Country/Ocean":   return $input_rec['Country: (Version 1.2 elements (3))'];
            case "State/Province":  return $input_rec['State/Province: (Version 1.2 elements (3))'];
            case "Region":          return $input_rec['County: (Version 1.2 elements (3))'];
            case "Sector":          return '';
            case "Exact Site":      return $input_rec['Locality: (Version 1.2 elements (3))'];
            case "Lat":             return $input_rec['Latitude: (Version 1.2 elements (3))'];
            case "Lon":             return $input_rec['Longitude: (Version 1.2 elements (3))'];
            case "Elev":            return $input_rec['Maximum Elevation: (Version 1.2 elements (4))'];
            //2nd half
            case "Depth":                       return $input_rec['Maximum Depth: (Version 1.2 elements (4))'];
            case "Elevation Precision":         return self::possible_subtraction($input_rec['Maximum Elevation: (Version 1.2 elements (4))'], $input_rec['Minimum Elevation: (Version 1.2 elements (4))']);
            case "Depth Precision":             return self::possible_subtraction($input_rec['Maximum Depth: (Version 1.2 elements (4))'], $input_rec['Minimum Depth: (Version 1.2 elements (4))']);
            case "GPS Source":                  return ''; //to be mapped
            case "Coordinate Accuracy":         return ''; //to be mapped
            case "Event Time":                  return '';
            case "Collection Date Accuracy":    return '';
            case "Habitat":                     return '';
            case "Sampling Protocol":           return '';
            case "Collection Notes":            return $input_rec['Minimum Depth: (Version 1.2 elements (4))']."-".$input_rec['Maximum Depth: (Version 1.2 elements (4))']." m";
            case "Site Code":                   return '';
            case "Collection Event ID":         return $input_rec['Field Number: (Version 1.2 elements (2))'];
            /*=====End=====*/
            default:
                exit("\nInvestigate field [$field] not defined.\n");
        }
    }
    private function possible_subtraction($max, $min)
    {
        if(is_numeric($max) && is_numeric($min)) return $max - $min;
    }
    private function prepend_guids($guids) /* //"http://n2t.net/".$input_rec['GUID: (GUIDs)']; */
    {
        $arr = array();
        $separators = array(',', ';', '|');
        $used_separator = ",";
        $separator_foundYN = false;
        foreach($separators as $sep) {
            $arr = array_merge($arr, explode($sep, $guids));
            if(stripos($guids, $sep) !== false) { //string is found
                $used_separator = $sep;
                $separator_foundYN = true;
            }
        }
        $arr = array_map('trim', $arr);
        // print_r($arr);
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        // print_r($arr);
        // if a $separator_foundYN is true, then delete from $final the original $guids
        if($separator_foundYN) {
            if (($key = array_search($guids, $arr)) !== false) {
                unset($arr[$key]);
                // print_r($arr);
            }
        }
        // start prepending if value starts with "ark:/". Please pre-pend "http://n2t.net/"
        $final = array();
        foreach($arr as $val) {
            if(substr($val,0,5) == 'ark:/') $final[] = "http://n2t.net/".$val;
            else                            $final[] = $val;
        }
        // print_r($final);
        return implode(" $used_separator ", $final);
    }
    private function format_Collection_Date($input_rec)
    {
        return $input_rec['Day Collected: (Version 1.2 elements (3))'].";".$input_rec['Month Collected: (Version 1.2 elements (3))'].";".$input_rec['Year Collected: (Version 1.2 elements (2))'];
    }
    private function search_collector_no($coll_num)
    {
        $url = str_replace('COLL_NUM', $coll_num, $this->api['coll_num']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $arr = json_decode($json, true);
            print_r($arr);
        }
    }
    function test() //very initial stages.
    {
        /* may not be needed since output.xls is based on input.xls
        $coll_num = 'KB17-277';
        self::search_collector_no($coll_num); exit;
        */
        /*
        if($local_xls = Functions::save_remote_file_to_local($this->ant_habitat_mapping_file, array('cache' => 1, 'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'file_extension' => 'xlsx', 'expire_seconds' => false))) {}
        unlink($local_xls);
        */
    }
}
?>