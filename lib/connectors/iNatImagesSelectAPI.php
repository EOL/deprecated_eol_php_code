<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from inat_images_select.php] */
class iNatImagesSelectAPI
{
    function __construct($archive_builder, $resource_id, $archive_path)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->archive_path = $archive_path;
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
        $this->image_limit = 100; //100 orig
        if(Functions::is_production()) {
            $this->cache_path = '/extra/other_files/iNat_image_DwCA/cache_image_score/';
            // $this->temp_image_repo = "/html/eol_php_code/applications/blur_detection_opencv_eol/eol_images/";
            $this->temp_image_repo = "/var/www/html/eol_php_code/applications/blur_detection_opencv_eol/eol_images/";
        }
        else {
            $this->cache_path = '/Volumes/AKiTiO4/web/cp/iNat_image_DwCA/cache_image_score/';
            $this->temp_image_repo = "/Library/WebServer/Documents/eol_php_code/applications/blur_detection_opencv_eol/eol_images/";
        }
        if(!is_dir($this->cache_path)) mkdir($this->cache_path);
        $this->agent_ids = array();
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
        
        //step 1: get total images count per taxon
        $this->unique_ids = array();
        $tbl = "http://eol.org/schema/media/document";
        self::process_table($tables[$tbl][0], 'get_total_images_count_per_taxon', $this->extensions[$tbl]);
        // print_r($this->total_images_per_taxon); //exit;
        
        //step 2:
        $this->unique_ids = array();
        $tbl = "http://eol.org/schema/media/document";
        self::process_table($tables[$tbl][0], 'select_100_images', $this->extensions[$tbl]);

        //step 3:
        $this->unique_ids = array();
        $tbl = "http://eol.org/schema/agent/agent";
        self::process_table($tables[$tbl][0], 'carry-over', $this->extensions[$tbl]); //also includes only agents actually used in media objects
    }
    private function process_table($meta, $what, $class)
    {   //print_r($meta);
        echo "\nprocess_table: [$what] [$meta->file_uri]...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 500) == 0) echo "\n".number_format($i);
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
            if($what == 'get_total_images_count_per_taxon') {
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                @$this->total_images_per_taxon[$taxonID]++;
            }
            if($what == 'select_100_images') {
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $accessURI = $rec['http://rs.tdwg.org/ac/terms/accessURI'];
                @$this->running_taxon_images_count[$taxonID]++;
                
                if($this->total_images_per_taxon[$taxonID] > 100) { //many many images per taxon. Compute image score only for these images
                    if($ret = self::get_blurriness_score($accessURI)) {
                        // print_r($ret);
                        /*Array(
                            [score] => 262.24131043428315
                            [url] => https://inaturalist-open-data.s3.amazonaws.com/photos/119359998/original.jpg
                        )*/
                        if(!$ret['score']) {
                            echo "\n-----------start\n"; print_r($ret);
                            echo "\nblank score, will try again\n";
                            $ret = self::get_blurriness_score($accessURI, true); //2nd param true means force compute of score
                            print_r($ret); echo "\n-----------end\n";
                        }
                    }
                    else { //cannot download image
                        echo "\nWill ignore record, cannot download image.\n";
                        continue;
                    }
                }
                else { //taxon with few images. i.e. less than 100
                    if($this->running_taxon_images_count[$taxonID] > $this->image_limit) continue;
                }
                
                // /* start saving
                $o = new \eol_schema\MediaResource();
                $uris = array_keys($rec); // print_r($uris); //exit;
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                }
                $unique_field = $o->identifier;
                if(!isset($this->unique_ids[$unique_field])) {
                    $this->unique_ids[$unique_field] = '';
                    $this->archive_builder->write_object_to_file($o);
                    
                    // /*
                    $agent_ids = explode(";", $o->agentID);
                    $agent_ids = array_map('trim', $agent_ids);
                    foreach($agent_ids as $agent_id) $this->agent_ids[$agent_id] = '';
                    // */
                }
                // */
                // if($i >= 13) break; //debug only
            }
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
    private function get_blurriness_score($accessURI, $forceYN = false)
    {
        $md5_id = md5($accessURI);
        if($arr = $this->func->retrieve_json_obj($md5_id, false)) { //2nd param false means returned value is an array()
            // print_r($arr); 
            // debug("\nscore retrieved...\n");  //just for testing
            
            if($forceYN) {
                if($arr = self::compute_blurriness_score($accessURI)) {
                    $json = json_encode($arr);
                    $this->func->save_json($md5_id, $json);
                    // print_r($arr); 
                    // debug("\nscore generated...\n");  //just for testing
                }
            }
        }
        else {
            if($arr = self::compute_blurriness_score($accessURI)) {
                $json = json_encode($arr);
                $this->func->save_json($md5_id, $json);
                // print_r($arr); 
                // debug("\nscore generated...\n");  //just for testing
            }
        }
        return $arr;
    }
    private function compute_blurriness_score($url)
    {
        if($target = self::download_image($url)) {
            // echo "\ndownloaded: [$target]\n";
            $py_script = str_ireplace("/eol_images", "", $this->temp_image_repo);
            $py_script .= "detect_blur.py";
            $cmd = 'python '.$py_script.' --images '.$this->temp_image_repo.' --threshold 100'; //with or without threshold since we are just after the score
            // echo "\naccessURI: [$url]\n";
            // echo "\ncmd:\n[$cmd]\n";
            $output = shell_exec($cmd); // echo "\nRequest output:\n$output\n";
            $arr = array("score" => trim($output), "url" => $url); // print_r($arr);
            unlink($target); //delete temp downloaded image e.g. 17796c5772dbfc3e53d48e881fbb3c1e.jpeg
            return $arr;
        }
        else {
            echo "\nCannot download [$url].\n";
        }
    }
    private function download_image($url)
    {   //wget -nc https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /Volumes/AKiTiO4/other_files/bundle_images/xxx/740.027116-1.jpg
        $filename = md5($url);
        $ext = pathinfo($url, PATHINFO_EXTENSION);
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