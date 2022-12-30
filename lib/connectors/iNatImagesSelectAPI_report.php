<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from inat_images_select.php] */
class iNatImagesSelectAPI
{
    function __construct($archive_builder, $resource_id, $archive_path, $params)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->archive_path = $archive_path;
        $this->params = $params;
        // $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*1, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->extensions = array("http://rs.gbif.org/terms/1.0/vernacularname"     => "vernacular",
                                  "http://rs.tdwg.org/dwc/terms/occurrence"         => "occurrence",
                                  "http://rs.tdwg.org/dwc/terms/measurementorfact"  => "measurementorfact",
                                  "http://eol.org/schema/media/document"            => "document",
                                  "http://rs.gbif.org/terms/1.0/reference"          => "reference",
                                  "http://eol.org/schema/agent/agent"               => "agent",
                                  "http://eol.org/schema/association"               => "association",
                                  "http://rs.tdwg.org/dwc/terms/taxon"              => "taxon",

                                  //start of other row_types: check for NOTICES or WARNINGS, add here those undefined URIs
                                  "http://rs.gbif.org/terms/1.0/description"        => "document",
                                  "http://rs.gbif.org/terms/1.0/multimedia"         => "document",
                                  "http://eol.org/schema/reference/reference"       => "reference"
                                  );
        $this->image_limit = 20; //100; //100 orig
        if(Functions::is_production()) {
            $this->cache_path = '/extra/other_files/iNat_image_DwCA/cache_image_score/';
            // $this->temp_image_repo = "/html/eol_php_code/applications/blur_detection_opencv_eol/eol_images/";
            $this->temp_image_repo = "/var/www/html/eol_php_code/applications/blur_detection_opencv_eol/eol_images/";
        }
        else {
            $this->cache_path = '/Volumes/AKiTiO4/web/cp/iNat_image_DwCA/cache_image_score/';
            $this->temp_image_repo = "/opt/homebrew/var/www/eol_php_code/applications/blur_detection_opencv_eol/eol_images/";
        }
        if(!is_dir($this->cache_path)) mkdir($this->cache_path);
        $this->agent_ids = array();
        $this->media_count = 0;
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        $tables = $info['harvester']->tables; // print_r($tables); exit;
        $extensions = array_keys($tables); //print_r($extensions); exit;
        /*Array(
            [0] => http://eol.org/schema/agent/agent
            [1] => http://eol.org/schema/media/document
            [2] => http://rs.tdwg.org/dwc/terms/taxon
        )*/
        
        // /*
        require_library('connectors/CacheMngtAPI');
        $this->func = new CacheMngtAPI($this->cache_path);
        // */
        
        // /* for Jen's flowering plants report
        $tbl = "http://rs.tdwg.org/dwc/terms/taxon";
        self::process_table($tables[$tbl][0], 'get_Plantae_taxonIDs', $this->extensions[$tbl]); //generates $this->Plantae_taxonIDs
        echo "\nPlantae_taxonIDs: ".count($this->Plantae_taxonIDs)."\n";
        // */
        
        // /* used during caching
        //step 1: get total images count per taxon
        $this->unique_ids = array();
        $tbl = "http://eol.org/schema/media/document";
        self::process_table($tables[$tbl][0], 'get_total_images_count_per_taxon', $this->extensions[$tbl]); //generates $this->total_images_per_taxon
        // print_r($this->total_images_per_taxon); //exit;
        // */
        /* good stats
        foreach($this->total_images_per_taxon as $taxonID => $total_images) {
            if($total_images > 100) { @$more++; $ret[$taxonID] = ''; }
            else                    { @$less++; $ret2[$taxonID] = ''; }
        }
        echo "\nmore than 100 images: [$more]\n";
        echo "\ntaxonIDs: ".count($ret)."\n";
        echo "\nless than 100 images: [$less]\n";
        echo "\ntaxonIDs: ".count($ret2)."\n";
        // exit("\n-end-\n");
        // as of: 7-Mar-2022 - from 1st GBIF export
        // more:       47489    // taxonIDs:   47489
        // less:       242899   // taxonIDs:   242899
        */
        
        //step 2:
        $this->unique_ids = array();
        $tbl = "http://eol.org/schema/media/document";
        self::process_table($tables[$tbl][0], 'select_100_images', $this->extensions[$tbl]);
        
        unset($this->total_images_per_taxon);
        unset($this->running_taxon_images_count);

        //step 3: carry-over agent but only those actually used in media
        $this->unique_ids = array();
        $tbl = "http://eol.org/schema/agent/agent";
        self::process_table($tables[$tbl][0], 'carry-over', $this->extensions[$tbl]); //also includes only agents actually used in media objects
    }
    private function process_table($meta, $what, $class)
    {   //print_r($meta);
        
        $modulo = 10000;
        if($needle = @$this->params['taxonID']) $modulo = 500000;
        
        echo "\nprocess_table: [$what] [$meta->file_uri]...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) { $i++;
            if($what != "get_total_images_count_per_taxon") {
                if(($i % $modulo) == 0) echo "\n".number_format($i). " [$what]";
            }
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array(
                [http://purl.org/dc/terms/identifier] => f2d37303ef878880c26f0028e4e51128
                [http://rs.tdwg.org/dwc/terms/taxonID] => 6c2d12b42fa5108952956024716c2267
                [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/StillImage
                [http://purl.org/dc/terms/format] => image/jpeg
                [http://purl.org/dc/terms/title] => 
                [http://purl.org/dc/terms/description] => 
                [http://rs.tdwg.org/ac/terms/accessURI] => https://inaturalist-open-data.s3.amazonaws.com/photos/179418423/original.jpeg
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => https://www.inaturalist.org/photos/179418423
                [http://purl.org/dc/terms/language] => en
                [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by-nc/4.0/
                [http://eol.org/schema/agent/agentID] => 158ba8069d90c10d4f82293b0140f710; 9a6544e87051f5994b0b924b21e0f9a6
            )*/
            
            if($needle = @$this->params['taxonID']) {
                if(in_array($class, array('taxon', 'document', 'vernacular'))) {
                    if($needle != @$rec['http://rs.tdwg.org/dwc/terms/taxonID']) continue;
                }
            }
            
            //=======================================================================================
            if($what == 'get_total_images_count_per_taxon') {
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                @$this->total_images_per_taxon[$taxonID]++;
            }
            //=======================================================================================
            if($what == 'select_100_images') {
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $accessURI = $rec['http://rs.tdwg.org/ac/terms/accessURI'];
                // /* REMINDER: temporarily commented for Katja's and Jen's report. Should be included in normal operation! (series 2 change)
                if(@$this->running_taxon_images_count[$taxonID] > $this->image_limit) continue;
                // */
                
                /* REMINDER: should be commented in normal operation. Only for Jen's Plantae report. (series 2 change)
                if(isset($this->Plantae_taxonIDs[$taxonID])) {}
                else continue;
                */
                
                // /* orig: Eli's scheme
                if($this->total_images_per_taxon[$taxonID] <= $this->image_limit) { //get all, no need to check score
                    /* REMINDER: this should be commented in normal operation. Used in Katja's report. (series 2 change)
                    continue;
                    */
                }
                else {
                    // echo "\ntaxon ($taxonID) with > 100 images: ".$this->total_images_per_taxon[$taxonID]."\n"; //good debug
                    if($ret = self::get_blurriness_score($accessURI)) { // print_r($ret);
                        // Array(
                        //     [score] => 262.24131043428315
                        //     [url] => https://inaturalist-open-data.s3.amazonaws.com/photos/119359998/original.jpg
                        // )
                        if(!$ret['score']) {
                            echo "\n-----------start\n"; print_r($ret);
                            echo "\nblank score, will try again\n";
                            $ret = self::get_blurriness_score($accessURI, true); //2nd param true means force compute of score
                            print_r($ret); echo "\n-----------end\n";
                            if(!$ret['score']) continue;
                        }
                    }
                    else { //cannot download image
                        echo "\nWill ignore record, cannot download image.\n";
                        continue;
                    }
                    
                    if($needle = @$this->params['taxonID']) {} //not score-specific if per taxon
                    else { //main operation --- scoring here is considered
                        // if($ret['score'] < 1000) continue; //during initial testing
                        
                        $highest_16th = (float) $ret['highest 1/16th score'];
                        
                        /* for Katja's report only: I'm looking for images where the highest 1/16th score is in the 100-300 range. Should be commented in normal operation
                        if($highest_16th >= 100 && $highest_16th <= 300) {}
                        else continue;
                        */
                        
                        // /* FINALLY: for normal operation
                        if($highest_16th >= 100) {}
                        else continue;
                        // */
                    }
                }
                // */
                
                /* Katja's scheme: a random-pick (21-100) and scoring (>100)
                $total_images_per_taxon = $this->total_images_per_taxon[$taxonID];
                if($total_images_per_taxon <= $this->image_limit) {} //get all, no need to check score
                elseif($total_images_per_taxon > $this->image_limit && $total_images_per_taxon <= 100) {} //get all, no need to check score
                elseif($total_images_per_taxon > 100) { //scoring --- entire block here copied from above
                    // echo "\ntaxon ($taxonID) with > 100 images: ".$this->total_images_per_taxon[$taxonID]."\n"; //good debug
                    if($ret = self::get_blurriness_score($accessURI)) { // print_r($ret);
                        // Array(
                        //     [score] => 262.24131043428315
                        //     [url] => https://inaturalist-open-data.s3.amazonaws.com/photos/119359998/original.jpg
                        // )
                        if(!$ret['score']) {
                            echo "\n-----------start\n"; print_r($ret);
                            echo "\nblank score, will try again\n";
                            $ret = self::get_blurriness_score($accessURI, true); //2nd param true means force compute of score
                            print_r($ret); echo "\n-----------end\n";
                            if(!$ret['score']) continue;
                        }
                    }
                    else { //cannot download image
                        echo "\nWill ignore record, cannot download image.\n";
                        continue;
                    }
                    if($needle = @$this->params['taxonID']) {} //not score-specific if per taxon
                    else { //main operation
                        if($ret['score'] < 1000) continue;
                    }
                }
                */
                
                @$this->running_taxon_images_count[$taxonID]++;
                
                // /* (series 2 change)
                @$this->media_count++;
                if($this->media_count >= 3000000) return; //3000000 normal operation --- for Katja's report 5K, during dev - for a report asked by Katja
                // */
                
                // /* start saving
                $o = new \eol_schema\MediaResource();
                $uris = array_keys($rec); // print_r($uris); //exit;
                
                /* if u want to limit columns for per taxon DwCA
                if($needle = @$this->params['taxonID']) $uris = array('http://purl.org/dc/terms/identifier', 'http://rs.tdwg.org/dwc/terms/taxonID', 'http://rs.tdwg.org/ac/terms/additionalInformation', 'http://rs.tdwg.org/ac/terms/accessURI', 'http://eol.org/schema/agent/agentID');
                */
                
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                }
                
                /*
                if($needle = @$this->params['taxonID']) $o->additionalInformation = $ret['score']." | ".$ret['highest 1/16th score']; //."|".$ret['url'];
                */
                $o->additionalInformation = $ret['score']." | ".$ret['highest 1/16th score']; //."|".$ret['url'];
                
                $unique_field = $o->identifier;
                if(!isset($this->unique_ids[$unique_field])) {
                    $this->unique_ids[$unique_field] = '';
                    $this->archive_builder->write_object_to_file($o);
                    
                    // /* to prevent duplicate agents
                    $agent_ids = explode(";", $o->agentID);
                    $agent_ids = array_map('trim', $agent_ids);
                    foreach($agent_ids as $agent_id) $this->agent_ids[$agent_id] = '';
                    // */
                }
                // */
                // if($i >= 13) break; //debug only
            }
            //=======================================================================================
            elseif($what == 'carry-over') {
                // print_r($this->agent_ids); exit;
                if    ($class == "vernacular")  $o = new \eol_schema\VernacularName();
                elseif($class == "agent")       $o = new \eol_schema\Agent();
                elseif($class == "reference")   $o = new \eol_schema\Reference();
                elseif($class == "taxon")       $o = new \eol_schema\Taxon();
                elseif($class == "document")    $o = new \eol_schema\MediaResource();
                else exit("\nUndefined class 03 [$class]. Will terminate.\n");
                $uris = array_keys($rec); // print_r($uris); //exit;
                $row_str = "";
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                }
                
                // /*
                if($class == "taxon") $unique_field = $o->taxonID;
                else                  $unique_field = $o->identifier; //the rest goes here
                // */
                
                // /*
                if($class == "agent") {
                    if(!isset($this->agent_ids[$o->identifier])) continue;
                }
                // */
                
                if(!isset($this->unique_ids[$unique_field])) {
                    $this->unique_ids[$unique_field] = '';
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            //=======================================================================================
            if($what == 'get_Plantae_taxonIDs') { // print_r($rec); exit("\ncha 1\n");
                /*Array(
                    [http://rs.tdwg.org/dwc/terms/taxonID] => 07cb0ee9203934e5fc3fbc2ccfcee1e3
                    [http://rs.tdwg.org/ac/terms/furtherInformationURL] => https://www.inaturalist.org/taxa/372465
                    [http://rs.tdwg.org/dwc/terms/scientificName] => Trigoniophthalmus alternatus (Silvestri, 1904)
                    [http://rs.tdwg.org/dwc/terms/kingdom] => Animalia
                    [http://rs.tdwg.org/dwc/terms/phylum] => Arthropoda
                    [http://rs.tdwg.org/dwc/terms/class] => Insecta
                    [http://rs.tdwg.org/dwc/terms/order] => Archaeognatha
                    [http://rs.tdwg.org/dwc/terms/family] => Machilidae
                    [http://rs.tdwg.org/dwc/terms/genus] => Trigoniophthalmus
                    [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                )*/
                if($rec['http://rs.tdwg.org/dwc/terms/kingdom'] == 'Plantae') $this->Plantae_taxonIDs[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = '';
            }
            //=======================================================================================
        }
    }
    private function get_field_from_uri($uri)
    {
        $field = pathinfo($uri, PATHINFO_BASENAME);
        $parts = explode("#", $field);
        if($parts[0]) $field = $parts[0];
        if(@$parts[1]) $field = $parts[1];
        return $field;
    }
    function get_blurriness_score($accessURI, $forceYN = false, $func = false)
    {
        if($func) $this->func = $func; //only when calling it from inat_images_select.php during dev
        $md5_id = md5($accessURI);
        if($arr = $this->func->retrieve_json_obj($md5_id, false)) { //2nd param false means returned value is an array()
            // print_r($arr); 
            // debug("\nscore retrieved...\n");  //just for testing
            
            if($forceYN) {
                if($arr = self::compute_blurriness_score($accessURI)) {
                    $arr = self::average_score($arr);
                    $json = json_encode($arr);
                    $this->func->save_json($md5_id, $json);
                    // print_r($arr); 
                    // debug("\nscore generated...\n");  //just for testing
                }
            }
            else {
                if(!isset($arr['parts'])) {
                    $arr = self::average_score($arr);
                    $json = json_encode($arr);
                    $this->func->save_json($md5_id, $json);
                }
            }
        }
        else {
            if($arr = self::compute_blurriness_score($accessURI)) {
                $arr = self::average_score($arr);
                $json = json_encode($arr);
                $this->func->save_json($md5_id, $json);
                // print_r($arr); 
                // debug("\nscore generated...\n");  //just for testing
            }
        }
        
        // /* deletion was moved here instead:
        if(file_exists($arr['local'])) unlink($arr['local']); //delete temp downloaded image e.g. 17796c5772dbfc3e53d48e881fbb3c1e.jpeg
        // e.g. "/opt/homebrew/var/www/eol_php_code/applications/blur_detection_opencv_eol/eol_images/f02b40a842171a27faaafa617331dce9.jpg"
        // */
        
        // /* step 1: delete previous old 16 files --- deletion was moved here instead:
        $ext = pathinfo($arr['local'], PATHINFO_EXTENSION);
        $dir = pathinfo($arr['local'], PATHINFO_DIRNAME);
        $file_path = str_replace("eol_images", "img_parts/", $dir);
        foreach(glob($file_path . "*." . $ext) as $filename) unlink($filename);
        // */

        return $arr;
    }
    function average_score($arr)
    {   /*Array(
            [score] => 19.909014155363817
            [url] => http://localhost/other_files/iNat_imgs/original_11.jpg
            [local] => /opt/homebrew/var/www/eol_php_code/applications/blur_detection_opencv_eol/eol_images/f02b40a842171a27faaafa617331dce9.jpg
        )*/
        /*Array(
            [dirname] => /opt/homebrew/var/www/eol_php_code/applications/blur_detection_opencv_eol/eol_images
            [basename] => f02b40a842171a27faaafa617331dce9.jpg
            [extension] => jpg
            [filename] => f02b40a842171a27faaafa617331dce9
        )*/

        if(!file_exists(@$arr['local'])) { echo "-R-"; //exit; //Needs to re-download image again...
            if($arr['local'] = self::download_image($arr['url'])) {} // echo "\ndownloaded: [$target]\n";
            else exit("\ncannot download remote image [".$arr['url']."]\n");
        }

        /* step 1: delete previous old 16 files --- WAS MOVED ELSEWHERE
        $ext = pathinfo($arr['local'], PATHINFO_EXTENSION);
        $dir = pathinfo($arr['local'], PATHINFO_DIRNAME);
        $file_path = str_replace("eol_images", "img_parts/", $dir);
        foreach(glob($file_path . "*." . $ext) as $filename) unlink($filename);
        */
        
        /* step 2: run imagemagick */
        $filename = pathinfo($arr['local'], PATHINFO_FILENAME);
        $destination = str_replace("eol_images", "img_parts", $arr['local']);
        // print_r(pathinfo($arr['local'])); exit;
        $destination = str_replace($filename, "parts", $destination); //exit("\n[$destination]\n");
        // convert -crop 25%x25% input.png output.png --- this divides the image input.png into 16 equal parts.
        $cmd = "convert -crop 25%x25% ".$arr['local']." $destination"; //divided by 16
        // $cmd = "convert -crop 50%x50% ".$arr['local']." $destination"; //divided by 4
        shell_exec($cmd); //generates parts-0.jpg to parts-15.jpg

        /* step 2: start loop of 16 parts
        // unlink($arr['local']);
        for($i = 0; $i <= 15; $i++) {
            $parts_image_file = str_replace("parts.", "parts-".$i.".", $destination);
            echo "\n$parts_image_file\n";
        }
        */
        
        $temp_image_repo = str_ireplace("/eol_images", "/img_parts", $this->temp_image_repo);
        if($ret = self::compute_blurriness_score_folder(2, $temp_image_repo)) {
            $arr['parts'] = $ret[0];
            $arr['highest 1/16th score'] = $ret[1];
            // print_r($arr);
            // exit("\n111\n");
        }
        return $arr;
    }
    private function compute_blurriness_score($url, $threshold = 1, $image_repo = false) //1 is having score as output, otherwise more output
    {
        if(!$image_repo) $image_repo = $this->temp_image_repo;
        if($target = self::download_image($url)) { // echo "\ndownloaded: [$target]\n";
            echo "-*-";
            $py_script = str_ireplace("/eol_images", "", $this->temp_image_repo);
            $py_script .= "detect_blur.py";
            $cmd = 'python '.$py_script.' --images '.$image_repo.' --threshold '.$threshold; //with or without threshold since we are just after the score
            // echo "\naccessURI: [$url]\n"; // echo "\ncmd:\n[$cmd]\n";
            $output = shell_exec($cmd); // echo "\nRequest output:\n$output\n";
            $arr = array("score" => trim($output), "url" => $url, "local" => $target); // print_r($arr);
            /* won't be deleted here anymore:
            unlink($target); //delete temp downloaded image e.g. 17796c5772dbfc3e53d48e881fbb3c1e.jpeg
            e.g. "/opt/homebrew/var/www/eol_php_code/applications/blur_detection_opencv_eol/eol_images/f02b40a842171a27faaafa617331dce9.jpg"
            */
            return $arr;
        }
        else echo "\nCannot download [$url]. May need to report to iNaturalist\n";
    }
    private function compute_blurriness_score_folder($threshold = 1, $image_repo = false) //1 is having score as output, otherwise more output
    {
        if(!$image_repo) $image_repo = $this->temp_image_repo;
        if(is_dir($image_repo)) { // echo "\ndownloaded: [$target]\n";
            $py_script = str_ireplace("/eol_images", "", $this->temp_image_repo);
            $py_script .= "detect_blur.py";
            $cmd = 'python '.$py_script.' --images '.$image_repo.' --threshold '.$threshold; //with or without threshold since we are just after the score
            $output = shell_exec($cmd); // echo "\nRequest output:\n$output\n";
            // /* convert string output to array()
            $output = str_ireplace($image_repo, "", $output);
            $arr = explode("\n", $output);
            foreach($arr as $row) {
                $cols = explode(" - ", $row);
                $cols = array_map('trim', $cols);
                if(@$cols[1]) {
                    $index = str_ireplace(array("parts-", ".jpg"), "", $cols[0]);
                    if($index <= 15) $final[$cols[0]] = $cols[1]; //limit to 16 images (0-15). Since sometimes 19 images are generated by imagemagick, where 16-18 are blank images
                }
            }
            $final = array_filter($final); //remove null arrays
            asort($final);
            $highest_16th_score = end($final);
            // */
            return array($final, $highest_16th_score);
        }
        else exit("\nFolder not found [$image_repo]\n");
    }
    private function download_image($url)
    {   //wget -nc https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /Volumes/AKiTiO4/other_files/bundle_images/xxx/740.027116-1.jpg
        $filename = md5($url);
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        if(!$ext) $ext = 'jpg';
        if($ext == "jpe") $ext = 'jpg';
        if($ext == "txt") $ext = 'jpg';
        
        /* step 1: delete previous file, if there is any
        $file_path = $this->temp_image_repo;
        foreach(glob($file_path . "*.j*") as $filename) unlink($filename);
        // exit("\nelix1\n");
        */
        
        $target = $this->temp_image_repo.$filename.".".$ext;
        if(!file_exists($target) || filesize($target) == 0) {
            // sleep(1); //delay for 1 second
            $cmd = WGET_PATH . " $url -O ".$target; //wget -nc --> means 'no overwrite'
            $cmd .= " 2>&1";
            $shell_debug = shell_exec($cmd);
            if(stripos($shell_debug, "ERROR 404: Not Found") !== false) { //string is found
                if(file_exists($target)) unlink($target);
                return false;
                exit("\nURL path does not exist.\n$url\n\n");
            }
            // echo "\n---\n".trim($shell_debug)."\n---\n"; //exit;
        }
        if(file_exists($target) && filesize($target)) return $target;
        else {
            if(file_exists($target)) unlink($target);
            return false;
        }
    }
}
?>