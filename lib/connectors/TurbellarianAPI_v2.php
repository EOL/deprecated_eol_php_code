<?php
namespace php_active_record;
/* connector [185] Connector scrapes the partner's site, assembles the information and generates a DWC-A */
class TurbellarianAPI_v2
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->domain = "http://turbellaria.umaine.edu";
        $this->taxa_url = $this->domain . "/turb3.php?action=1&code=";
        $this->rights_holder = "National Science Foundation - Turbellarian Taxonomic Database";
        $this->agents = array();
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Seth Tyler");
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Steve Schilling");
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Matt Hooge");
        $this->agents[] = array("role" => "compiler", "homepage" => "http://turbellaria.umaine.edu/", "name" => "Louise Bush");
        $this->SPM = "http://rs.tdwg.org/ontology/voc/SPMInfoItems";
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';

        $this->download_options = array('download_wait_time' => 500000, 'timeout' => 9600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => 60*60*24*25);
        $this->download_options['expire_seconds'] = false;
        
        $this->page['main'] = "http://turbellaria.umaine.edu/turbella.php";
        $this->page['action_1']  = "http://turbellaria.umaine.edu/turb3.php?action=1&code=";
        $this->page['action_2']  = "http://turbellaria.umaine.edu/turb3.php?action=2&code=";
        $this->page['action_23'] = "http://turbellaria.umaine.edu/turb3.php?action=23&code=";
        $this->page['action_16']  = "http://turbellaria.umaine.edu/turb3.php?action=16&code=";
        $this->page['action_15']  = "http://turbellaria.umaine.edu/turb3.php?action=15&code=";
        $this->page['action_6']   = "http://turbellaria.umaine.edu/turb3.php?action=6&code=";
        
        $this->action['direct_images'] = 2;
        $this->action['downline_images'] = 23;
        $this->action['distribution'] = 16;
        $this->action['diagnosis'] = 15;
        $this->action['downline_synonyms'] = 6;

        // 14676 - Xenacoelomorpha
        // 14686 - Nephrozoa
    }

    private function additional_mappings() //specific for this resource and one from Tropicos
    {
        require_library('connectors/TropicosArchiveAPI');
        $func = new TropicosArchiveAPI(NULL);
        $uri_values = $func->add_additional_mappings(true); //add country mappings used in Tropicos
        $this->uri_values = array_merge($this->uri_values, $uri_values);
        echo "\n".count($this->uri_values)." - URIs were added from Tropicos. \n";
        
        //add mappings specific to this resource: Turbellaria 185
        $mappings_specific_to_this_resource = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/Turbellaria/unmapped_countries%202%202.txt";
        $uri_values = $func->add_additional_mappings(true, $mappings_specific_to_this_resource);
        $this->uri_values = array_merge($this->uri_values, $uri_values);
        echo "\n".count($this->uri_values)." - URIs were added from Turbellarian. \n";
    }
    function start()
    {
        $this->uri_values = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($this->uri_values). " - default URIs from EOL registry.";
        self::additional_mappings(); //add more mappings specific only to this resource and one from Tropicos
        
        $this->agent_ids = self::get_object_agents($this->agents);
        $this->biblio_citation = self::get_biblio_citation();
        self::write_taxon(array('name' => 'Bilateria', 'code' => 'bilateria'));
        
        // /* main operation
        $all_ids = self::get_all_ids();
        foreach($all_ids as $code) self::process_page($code);
        // */

        //2645 2571 9350
        // self::process_page(226); //3158 3191 4901 3511 [5654 - has direct and downline images]  1223 3749 [6788 with downline syn]
        // self::process_page(8216);            //just tests
        // self::get_valid_ids(3159); exit;     //just tests
        $this->archive_builder->finalize(TRUE);
        //start stats for un-mapped countries
        if(isset($this->unmapped_countries)) {
            $OUT = Functions::file_open(DOC_ROOT."/tmp/185_unmapped_countries.txt", "w");
            $countries = array_keys($this->unmapped_countries);
            sort($countries);
            foreach($countries as $c) fwrite($OUT, $c."\n");
            fclose($OUT);
        }
    }
    private function process_indented_names($html)
    {
        if(preg_match("/<div class=\"listindent\">(.*?)<\/div>/ims", $html, $arr)) {
            $temp = $arr[1];
            $temp = explode('<div class="listindent">', $temp);
            $temp = array_map('trim', $temp);
            // print_r($temp);
            /*Array( e.g. $temp
                [0] => <a href="/turb3.php?action=1&code=14686">Nephrozoa </a>&nbsp; &nbsp; &nbsp; Jondelius, Ruiz-Trillo, Baguna, & Riutort, 2002
                [1] => <a href="/turb3.php?action=1&code=12856">Platyhelminthes </a>&nbsp; &nbsp; &nbsp; Minot, 1876
                [2] => <a href="/turb3.php?action=1&code=12276">Rhabditophora </a>&nbsp; &nbsp; &nbsp; Ehlers, 1985
                [3] => <a href="/turb3.php?action=1&code=13985">Trepaxonemata </a>&nbsp; &nbsp; &nbsp; Ehlers, 1984
                [4] => <a href="/turb3.php?action=1&code=12823">Neoophora </a>&nbsp; &nbsp; &nbsp; Westblad, 1948
                [5] => <a href="/turb3.php?action=1&code=12278">Eulecithophora </a>&nbsp; &nbsp; &nbsp; de Beauchamp, 1961
                [6] => <a href="/turb3.php?action=1&code=4793">Rhabdocoela </a>&nbsp; &nbsp; &nbsp; Ehrenberg, 1831
                [7] => <a href="/turb3.php?action=1&code=14173">Dalytyphloplanida </a>&nbsp; &nbsp; &nbsp; Willems, Wallberg, Jondelius, Littlewood, Backeljau, Schockaert, Artoi
                [8] => <a href="/turb3.php?action=1&code=11963">Luridae </a>&nbsp; &nbsp; &nbsp; Sterrer & Rieger, 1990
                [9] => Luriculus &nbsp; &nbsp; &nbsp; Faubel, Rohde, & Watson, 1994
            )*/
            $recs = array();
            foreach($temp as $str) {
                $rec = array();
                if(preg_match("/code=(.*?)\"/ims", $str, $arr)) $rec['code'] = $arr[1];
                if(preg_match("/>(.*?)<\/a>/ims", $str, $arr)) $rec['name'] = $arr[1];
                if(preg_match("/>&nbsp; &nbsp; &nbsp;(.*?)elix/ims", $str.'elix', $arr)) $rec['author'] = $arr[1];
                $rec = array_map('trim', $rec);
                if(@$rec['name']) $recs[] = $rec;
            }
            // print_r($recs);
            foreach($recs as $index => $rec) {
                // echo "\n$index\n"; print_r($rec);
                if($index === 0) $recs[$index]['parent_id'] = 'bilateria';
                else             $recs[$index]['parent_id'] = $recs[$index-1]['code'];
            }
            // print_r($recs);
            foreach($recs as $rec) self::write_taxon($rec);
            return array_pop($recs); //this ret value will be the parent of the main_sci
        }
    }
    private function process_page($id)
    {
        $html = Functions::lookup_with_cache($this->page['action_1'].$id, $this->download_options);
        $html = self::format_html($html);
        
        $parent_of_main_sci = self::process_indented_names($html);
        
        $html = self::get_string_starting_from('table of subtaxa', $html);
        if(preg_match("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
            $str = $arr[1];
            // echo "\n[$str]\n";
            // <th>Allostoma</th>
            // <td>Beneden, 1861</td>
            $main_sci['code'] = $id;
            $main_sci['parent_id'] = $parent_of_main_sci['code'];
            
            if(preg_match("/<th>(.*?)<\/th>/ims", $str, $arr)) $main_sci['name'] = $arr[1];
            
            if(self::starts_with_small_letter($main_sci['name'])) $main_sci['name'] = $parent_of_main_sci['name']." ".$main_sci['name']; //meaning it is a subspecies
            
            if(preg_match("/<td>(.*?)<\/td>/ims", $str, $arr)) $main_sci['author'] = $arr[1];
            if($main_sci['name'] == "incertae sedis") $main_sci['author'] = "";
            // print_r($main_sci);

            $direct_images = self::get_direct_images($str, $id);                                        //action=2
            $invalid_names = self::get_invalid_names($html);
            $downline_images = self::get_downline_images($str, $id, $invalid_names);                    //action=23
            $distribution = self::parse_TableOfTaxa($html, $main_sci, $invalid_names, 'distribution');  //action=16
            // $diagnosis = self::parse_TableOfTaxa($html, $main_sci, $invalid_names, 'diagnosis');        //action=15
            self::parse_TableOfTaxa($html, $main_sci, $invalid_names, 'downline_synonyms');             //action=6

            // /*
            if($val = $direct_images) $main_sci['direct_images'] = $val;
            if($val = $downline_images) $main_sci['downline_images'] = $val;
            // */
            if($main_sci['name']) self::write_to_archive($main_sci);
        }
    }
    private function write_to_archive($rec)
    {
        // print_r($rec); exit;
        self::write_taxon($rec);
        if($val = @$rec['downline_images']) self::write_downline_images($val, $rec['name'], $rec['code']);
        if($val = @$rec['direct_images']) self::write_direct_images($val, $rec);
    }
    private function write_direct_images($direct_images, $rec)
    {
        foreach($direct_images as $img_path) {
            $rec['path'] = $img_path;
            $rec['code'] = $rec['code'];
            self::write_image($rec);
        }
    }
    private function write_downline_images($dl_images, $main_name, $main_code)
    {
        foreach($dl_images as $code => $img) {
            // print_r($img); exit;
            foreach($img as $code => $value) {
                $taxon['code']   = $code;
                if(self::starts_with_small_letter($value['name'])) $taxon['name'] = $main_name." ".$value['name'];
                else {
                    // [6940] => [name] => St.Naumi
                    if(in_array($code, array(6940))) {}
                    else exit("\nInvestigate does not start with small letter [$code] \n");
                }
                $taxon['parent_id'] = $main_code;
                $taxon['author'] = $value['author'];
                self::write_taxon($taxon);
                foreach($value['images'] as $img_path) {
                    $rec['path'] = $img_path;
                    $rec['code'] = $code;
                    self::write_image($rec);
                }
            }
        }
    }
    private function format_image_path($rec)
    {   /*
        [path] => media/thb2/2639a_thb.jpg
        [code] => 2639
                  12980
        http://turbellaria.umaine.edu/media/img2/2639a.jpg
        */
        // echo "\n[$rec[path]]";
        $old = "/thb";
        $new = "/img";
        $path = str_replace($old, $new, $rec['path']);
        $path = $this->domain."/".str_replace("_thb", "", $path);
        // echo "\n[$path]";

        /* 1st version doesn't work for e.g. [media/thb0/233a_thb.gif]
        if    (strlen($rec['code']) <= 4) $width = 1;
        elseif(strlen($rec['code']) == 5) $width = 2;
        else exit("\nInitialize code length for image writing...\n");
        $old = "/thb".substr($rec['code'],0,$width)."/";
        $new = "/img".substr($rec['code'],0,$width)."/";
        $path = str_replace($old, $new, $rec['path']);
        $path = $this->domain."/".str_replace("_thb", "", $path);
        */
        return $path; //this is mediaURL
    }
    private function write_image($rec)
    {
        /*
        [path] => media/thb2/2639a_thb.jpg
        [code] => 2639
        http://turbellaria.umaine.edu/media/img2/2639a.jpg
        */
        $mediaURL = self::format_image_path($rec);
        $mr = new \eol_schema\MediaResource();
        if($this->agent_ids)      $mr->agentID = implode("; ", $this->agent_ids);
        $mr->taxonID        = $rec["code"];
        $mr->identifier     = pathinfo($mediaURL, PATHINFO_BASENAME);
        $mr->type           = "http://purl.org/dc/dcmitype/StillImage";
        $mr->format         = Functions::get_mimetype($mediaURL);
        $mr->Owner          = $this->rights_holder;
        $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $mr->accessURI      = $mediaURL;
        /* not used for now
        $mr->language       = 'en';
        $mr->title          = "";
        $mr->CreateDate     = "";
        $mr->rights         = "";
        $mr->audience       = 'Everyone';
        $mr->description    = "";
        */
        if(!isset($this->object_ids[$mr->identifier])) {
           $this->object_ids[$mr->identifier] = '';
           $this->archive_builder->write_object_to_file($mr);
        }
    }
    private function compute_rank($sciname)
    {
        $sciname = trim($sciname);
        if(stripos($sciname, ' sp.') !== false) return "species"; //string is found
        if(stripos($sciname, ' spec.') !== false) return "species"; //string is found
        if(stripos($sciname, ' species ') !== false) return "species"; //string is found
        
        if(stripos($sciname, '.') !== false) return; //string is found
        elseif(stripos($sciname, ',') !== false) return; //string is found
        
        $arr = explode(" ", $sciname);
        if(count($arr) == 2) return 'species';
        if(count($arr) == 3) {
            if(substr($arr[2],0,1) == "(") return;
            if(self::starts_with_small_letter($arr[1]) && self::starts_with_small_letter($arr[2])) return 'subspecies';
        }
    }
    private function write_taxon($t)
    {
        if(self::starts_with_small_letter($t['name'])) {
            print_r($t);
            if(!@$t['acceptedNameUsageID']) { //if synonym no need to investigate
                exit("\nInvestigate, small letter sciname\n");
            }
        }
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $t['code'];
        $taxon->scientificName              = $t['name'];
        $taxon->scientificNameAuthorship    = @$t['author'];
        $taxon->taxonRank                   = self::compute_rank($t['name']);
        $taxon->furtherInformationURL       = $this->page['action_1'].$t['code'];
        
        if($val = @$t['acceptedNameUsageID']) {
            $taxon->acceptedNameUsageID = $val;
            $taxon->taxonomicStatus = 'synonym';
        }
        else $taxon->parentNameUsageID = @$t['parent_id']; //parent is only for non-synonym
        if($val = @$t['taxon_remarks']) $taxon->taxonRemarks = $val;
        
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function parse_TableOfTaxa($html, $main_sci, $invalid_names, $what)
    {
        $id = $main_sci['code'];
        $html = self::get_string_starting_from('table of taxa', $html);
        if(preg_match_all("/<tr(.*?)<\/tr>/ims", $html, $arr)) {
            foreach($arr[1] as $row) {
                if(stripos($row, '<font color="red">') !== false) continue; //string is found
                if(stripos($row, '<font color="00cc00">') !== false) continue; //string is found
                
                //start compute for $code
                /*
                [2] => <a href="/turb3.php?action=6&code=3190">synonyms</a>
                [3] => <a href="/turb3.php?action=13&code=3190"><img src="/icons/small/image.png" alt="card avail."></a>
                [4] => <a href="/turb3.php?action=11&code=3190&syn=2">literature</a>
                [5] => <a href="/turb3.php?action=16&code=3190&valid=0">dist'n</a>
                       <a href="/turb3.php?action=15&code=439">diagnosis</a></td>
                */
                if    (preg_match("/action=6&code=(.*?)\">synonyms<\/a>/ims", $row, $arr3))                         $code = $arr3[1];
                elseif(preg_match("/action=13&code=(.*?)\"><img src=\"\/icons\/small\/image.png\"/ims", $row, $arr3)) $code = $arr3[1];
                elseif(preg_match("/action=11&code=(.*?)&syn=2\">literature<\/a>/ims", $row, $arr3))                $code = $arr3[1];
                elseif(preg_match("/action=11&code=(.*?)&syn=0\">literature<\/a>/ims", $row, $arr3))                $code = $arr3[1];
                elseif(preg_match("/action=16&code=(.*?)&valid=0\">/ims", $row, $arr3))                             $code = $arr3[1];
                elseif(preg_match("/action=15&code=(.*?)\">diagnosis<\/a>/ims", $row, $arr3))                       $code = $arr3[1];
                else exit("\nInvestigate cannot get id [$id]\n");
                // echo "\ncode [$code]\n";
                //end compute for $code

                if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $arr2)) {
                    $tr_cols = $arr2[1];
                    $tr_cols[0] = strip_tags($tr_cols[0]);
                    
                    if(in_array($what, array('downline_synonyms'))) {
                        if(!self::starts_with_small_letter($tr_cols[0])) continue;
                    }
                    
                    // print_r($tr_cols); //good debug
                    $row_rec = array();
                    $row_rec['code'] = $code;
                    $row_rec['name'] = $main_sci['name']." ".$tr_cols[0];
                    $row_rec['author'] = @$tr_cols[1];
                    $row_rec['parent_id'] = $main_sci['code'];
                }
                
                if($what == 'distribution') {
                    if($recs = self::get_text_object($row, $code, $this->action[$what], $what)) {
                        $row_rec['distributions'] = $recs;
                        // print_r($row_rec);
                        self::write_taxon($row_rec);
                        self::write_distribution_trait($row_rec);
                    }
                }
                elseif($what == 'diagnosis')    self::get_text_object($row, $code, $this->action[$what], $what);
                elseif($what == 'downline_synonyms') {
                    // print_r($main_sci);
                    if($syns = self::get_text_object($row, $code, $this->action[$what], $what)) {

                        /* good debug
                        if($row_rec['code'] == 14057) {
                            print_r($syns);
                            print_r($row_rec); exit;
                        }
                        */
                        
                        $row_rec['synonyms'] = $syns; //this may not be needed at all
                        // print_r($row_rec);
                        
                        self::write_taxon($row_rec);
                        foreach($syns as $syn) {
                            
                            // first check if name starts with small letter, if so add genus part of row_rec['name']
                            if(self::starts_with_small_letter($syn['name']) && substr($syn['name'],0,14) != "incertae sedis") {
                                $temp = explode(" ", $row_rec['name']);
                                $syn['name'] = $temp[0]." ".$syn['name'];
                            }
                            
                            
                            $syn['acceptedNameUsageID'] = $row_rec['code'];
                            self::write_taxon($syn);
                        }
                    }
                }
            } //end foreach()
        }
    }
    private function get_text_object($str, $id, $action, $what) //action=16
    {
        if(stripos($str, "action=".$action."&") !== false) {//string is found
            // echo "\nwith [$what] --> ";
            // <a href="/turb3.php?action=16&code=3190&valid=0">dist'n</a>
            if(preg_match("/action=".$action."&code=".$id."(.*?)\"/ims", $str, $arr)) {
                if($val = (string) trim($arr[1])) $url = $this->page["action_".$action].$id.$val;
                else                              $url = $this->page["action_".$action].$id;
                // echo "[$url]\n"; //e.g. http://turbellaria.umaine.edu/turb3.php?action=16&code=13396&valid=0
                if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                    if($what == 'distribution') {
                        $ret = array();
                        $temp = self::parse_distribution($html);
                        $ret['records'] = self::normalize_dist_records($temp);
                        $ret['source_url'] = $url;
                        return $ret;
                    }
                    elseif($what == 'downline_synonyms') {
                        // echo "\n[$url]";
                        /* good for debugging
                        if($url == "http://turbellaria.umaine.edu/turb3.php?action=6&code=56") $debug = true;
                        else                                                                   $debug = false;
                        */
                        $debug = false;
                        return self::parse_synonyms($html, $id, $debug);
                    }
                }
            }
        }
        return false;
    }
    private function normalize_dist_records($recs)
    {
        if($recs) { //do print_r($recs); to check values - script.
            foreach($recs as $rec) {
                $final[$rec['country_uri']]['country_value']  = $rec['country_value'];
                $final[$rec['country_uri']]['orig_country'][] = $rec['orig_country'];
                $final[$rec['country_uri']]['ref'][$rec['ref']['ref']] = $rec['ref']['url'];
            }
            return $final;
        }
    }
    private function parse_synonyms($html, $id, $debug) //$id here is only for investigation if needed
    {
        $final = array();
        $html = self::get_string_starting_from('table of synonyms', $html);
        if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr)) { //print_r($arr[1]);

            if($debug) print_r($arr[1]);
            
            foreach($arr[1] as $row) {
                // $row = str_replace("<td >&nbsp;</td>", "", $row);
                if(preg_match_all("/<td >(.*?)<\/td>/ims", $row, $arr2)) { 
                    $temp = $arr2[1];
                    
                    if($debug) print_r($temp);
                    
                    if(preg_match("/&code=(.*?)\"/ims", $temp[1], $arr3)) $code = $arr3[1];
                    elseif(preg_match("/&code=(.*?)\"/ims", $temp[2], $arr3)) $code = $arr3[1];
                    else exit("\nInvestigate no code in synonym [$id]\n"); 
                    $rec = array();
                    $rec['code'] = $code;
                    if($temp[0] != "&nbsp;") {
                        if($temp[0] == "incertae sedis") $rec['name'] = Functions::remove_whitespace(strip_tags($temp[1]));
                        else                             $rec['name'] = Functions::remove_whitespace(strip_tags($temp[0]." ".$temp[1]));
                    }
                    else                                 $rec['name'] = Functions::remove_whitespace(strip_tags($temp[1]));

                    /* prob 101
                    Stenostomidae Stenostomum agile -- IS NOT OK
                    Stenostomum unicolor constrictum -- IS OK
                    */
                    //fix prob 101 -------------------------------------------------------
                    $words = explode(" ", $rec['name']);
                    if(count($words) >= 3) {
                        if(!self::starts_with_small_letter($words[1])) { //if 2nd word starts with capital letter
                            array_shift($words);                         //then remove that word
                            // print_r($words); exit;
                            $rec['name'] = implode(" ", $words);
                        }
                    }
                    //end fix prob 101 -------------------------------------------------------
                    
                    $rec['author'] = strip_tags($temp[2]);
                    if($temp[3] != "&nbsp;") $rec['taxon_remarks'] = Functions::remove_whitespace(strip_tags($temp[3]));
                    $final[] = $rec;
                }
            }
        }
        return $final;
    }
    private function write_distribution_trait($row_rec)
    {
        /*Array(
            [code] => 8217
            [name] => Pentacoelum fucoideum
            [author] => Westblad, 1935
            [distributions] => Array
                (
                    [records] => Array
                        (
                            [http://www.geonames.org/2921044] => Array
                                (
                                    [country_value] => Germany
                                    [orig_country] => Array
                                        (
                                            [0] => Nord-Ostsee-Kanal (Nord-Ostseekanal, Kaiser Wilhelm Kanal, Kieler Kanal, Kiel Canal), Schleswig-Holstein, Germany
                                            [1] => Nord-Ostsee-Kanal (Nord-Ostseekanal, Kaiser Wilhelm Kanal, Kieler Kanal, Kiel Canal), Schleswig-Holstein, Germany
                                        )
                                    [ref] => Array
                                        (
                                            [Ax P. 1952. Eine Brackwasser-Lebensgemeinschaft an Holzpfaehlen des Nord-Ostsee-Kanals. Kieler Meeresforschungen 8:229-242,2 tab] => http://turbellaria.umaine.edu/turb3.php?action=10&litrec=9700
                                            [Ax P. 1959. Zur Systematik, Ökologie und Tiergeographie der Turbellarienfauna in den ponto-kaspischen Brackwassergebieten. Zool Jahrb Abt Syst Oekol Geogr Tiere 87:43-184] => http://turbellaria.umaine.edu/turb3.php?action=10&litrec=8004
                                        )
                                )
                    [source_url] => http://turbellaria.umaine.edu/turb3.php?action=16&code=8217&valid=0
        */
        // print_r($row_rec); exit;
        $taxon_id = $row_rec['code'];
        $source_url = $row_rec['distributions']['source_url'];
        $catnum     = md5($source_url); //decided to use source_url as catnum, not seem a bad idea.
        if($val = $row_rec['distributions']['records']) {
            foreach($val as $country_uri => $rek) {
                $orig_countries = array_unique($rek['orig_country']);
                $orig_countries = implode("<br>", $orig_countries);
                $mremarks = $orig_countries;
                $ref_ids = self::write_references($rek['ref']);
                self::add_string_types($taxon_id, $catnum, $country_uri, "http://eol.org/schema/terms/Present", true, $mremarks, $ref_ids, $source_url);
            }
        }
    }
    private function write_references($refs)
    {   /*Array(
                [Ax P. 1952. Eine Brackwasser-Lebensgemeinschaft an Holzpfaehlen des Nord-Ostsee-Kanals. Kieler Meeresforschungen 8:229-242,2 tab] => http://turbellaria.umaine.edu/turb3.php?action=10&litrec=9700
                [Ax P. 1959. Zur Systematik, Ökologie und Tiergeographie der Turbellarienfauna in den ponto-kaspischen Brackwassergebieten. Zool Jahrb Abt Syst Oekol Geogr Tiere 87:43-184] => http://turbellaria.umaine.edu/turb3.php?action=10&litrec=8004
            )
        */
        foreach($refs as $full_ref => $ref_url) {
            if(preg_match("/action=10&litrec=(.*?)elix/ims", $ref_url.'elix', $arr)) $ref_id = $arr[1];
            else exit("\nInvestigate no refno [$source_url]\n");
            $r = new \eol_schema\Reference();
            $r->full_reference = (string) $full_ref;
            $r->identifier = $ref_id;
            $ref_ids[$ref_id] = '';
            $r->uri = $ref_url;
            if(!isset($this->resource_reference_ids[$r->identifier])) {
               $this->resource_reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return array_keys($ref_ids);
    }
    private function add_string_types($taxon_id, $catnum, $value, $mtype, $mtaxon = false, $mremarks = '', $ref_ids, $source_url)
    {
        if(!trim($value)) return;
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence_id;
        if($mtaxon) {
            $m->measurementOfTaxon = 'true';
            $m->source = $source_url;
            $m->measurementRemarks = $mremarks;
            $m->bibliographicCitation = $this->biblio_citation;
        }
        $m->measurementType = $mtype;
        $m->measurementValue = (string) $value;
        if($val = $ref_ids) $m->referenceID = implode("; ", $val);
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->measurement_ids[$m->measurementID] = '';
            $this->archive_builder->write_object_to_file($m);
        }
    }
    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    private function parse_distribution($html)
    {
        $html = str_replace("<td>", "<td >", $html);
        $final = array();
        if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr)) { // print_r($arr[1]);
            foreach($arr[1] as $row) {
                $rec = array();
                $row = str_replace('&nbsp;', "", $row); // echo "\n[$row]\n";
                if(preg_match_all("/<td >(.*?)<\/td>/ims", $row, $arr2)) { 
                    $cols = $arr2[1];
                    if(!@$cols[1]) continue; //first row is header area
                    if(count($cols) != 11) exit("\nNeed to review connector, no. of columns for distribution changed.\n");

                    // print_r($cols); echo "\n".count($cols)."\n";
                    /*
                    [0] => A
                    [1] => Kors Fjord (Korsfjorden, Korsfjord, Krossfjorden), Fjord, near Bergen, Norway
                    [2] => <a href="/turb3.php?action=24&code=3751&srec=1554&dN=60.159721&dE=5.122778&srec=1554&output=json&oe=utf8&sensor=false">map</a>
                    [3] => <a href="/turb3.php?action=18&srec=1554&code=3751">1554</a>
                    [4] => summer 1951
                    [5] => default type
                    [6] => 600-700 m
                    [7] => mud
                    [8] => 
                    [9] => (St. 79/1951).
                    [10] => <a href="/turb3.php?action=10&litrec=7144&code=3749">Westblad E (1952)</a>: 9
                    */
                    $i['site'] = 1;
                    $i['collection date'] = 4;
                    $i['kind'] = 5;
                    $i['depth'] = 6;
                    $i['substrate'] = 7;
                    $i['salin'] = 7;
                    $i['comments'] = 9;
                    $i['reference'] = 10;

                    $site = trim($cols[$i['site']]);
                    if(substr($site, -1) == ",") $site = substr($site,0,strlen($site)-1);
                    $ctry = self::get_country_string($site);
                    if(!$ctry) exit("\nInvestigate blank ctry [".$site."]\n");
                    if($country_uri = self::get_country_uri($ctry)) {} //mapped OK
                    else {
                        $this->unmapped_countries[$ctry] = ''; //for stats only
                        // echo "\n{$site}"; //good debug
                        continue; //will wait for Jen's mapping so we get all country strings its respective country URI
                    }
                    // exit("\n[$ctry] [$country_uri]\n");
                    /* working but not used. Used as TraitBank rather than text object
                    $dist = $site;
                    if($val = $cols[$i['collection date']]) $dist .= "<br>Collection date: " . $val;
                    if($val = $cols[$i['kind']]) $dist .= "<br>Kind: " . $val;
                    if($val = $cols[$i['depth']]) $dist .= "<br>Depth: " . $val;
                    if($val = $cols[$i['substrate']]) $dist .= "<br>Substrate: " . $val;
                    if($val = $cols[$i['salin']]) $dist .= "<br>Salin: " . $val;
                    if($val = $cols[$i['comments']]) $dist .= "<br>Comments: " . $val;
                    $rec['dist'] = $dist;
                    */
                    $rec['country_uri'] = $country_uri;
                    $rec['country_value'] = $ctry;
                    $rec['orig_country'] = $site;
                    $rec['ref'] = self::parse_ref($cols[$i['reference']]);
                }
                if($rec) $final[] = $rec;
            }//end foreach
        }
        // print_r($final);
        // exit;
        return $final;
    }
    private function get_country_uri($country)
    {
        if($val = @$this->uri_values[$country]) return $val;
        else {
            // /* working OK but too hard-coded, better to read the mapping from external file
            switch ($country) {
                case "Brazil	 	 ": return "http://www.geonames.org/3469034";
            }
            // */
        }
    }
    private function get_country_string($str)
    {
        $orig_str = $str;
        if(stripos($str, 'Ecuador') !== false) return 'Ecuador'; //string is found
        if(stripos($str, "Il'myenskoye (?)") !== false) return $str; //string is found
        if(substr($str,0,6) == "Italy,") return "Italy";
        if(substr($str,0,6) == "Italy:") return "Italy";
        if(substr($str,-6) == ", Sylt") return "Germany";
        
        $str = trim(preg_replace('/\s*\([^)]*\)/', '', $str)); //remove parenthesis
        if(!$str) $str = $orig_str;
        
        // echo "\n[$str]\n";
        $a = explode(",", $str);
        $a = array_map('trim', $a);
        $final = array_pop($a);
        if(in_array($final, self::country_sub_strings())) return $orig_str;
        return $final;
    }
    private function country_sub_strings() //if substr is this then return entire original string
    {
        return array(")Croatia", "36km S of Buffalo", "Al Ghardaqa", "Aquarium", "Australioa", "Australis", "Baraoos", "Belize Barrier Reef", "Benajarafe", "Blyth", "Cap Canaille", 
        "Cape Colony", "Castiglione della Pescaia", "Ceylon", "Channel", "Charaki", "Costa Paradiso", "Eastern Alps", "Eastern North America", "Emerald Isle", 
        "European Artic and Atlantic coasts", "Firth of Forth", "Fondamente del Ponte Piccolo", "Former Galizia", "Formosa Strait", "Foroe Islands", "Foroyar Islands", "Fyrisån", 
        "Gabonese Republic", "Giglio Island", "Golfe du Lion", "Guadaloupe Archipelago", "Guinea-Strom", "Gulf of Siam", "High Tatra Mountains", "Il Cavaliere", "Ile Grosse", 
        "Ilse of Man", "Ingolf experimental station 28", "Isla Grandi", "Islas Los Frailes", "Isle of Man", "Isles of Shoals", "Jan Mayen Ridge", "Khalij as Suways", 
        "Kingdom of Lesotho", "Klockarudden", "Kominato beach", "La Ciaccia;", "Laing Island eastern Papua New Guinea.", "Lake Baikal", "Lake Tiberas", "Latvija", "Lebenon", 
        "Lemon Tree Gut", "Lietuva", "M. Sars experimental station 76", "Mala", "Mallorca", "Marbella", "Melville Bay", "Moseley 1877 ocean trawl site", "N of Jeddah", 
        "NW of Panggong Tso", "Netherland Antilles", "North Northeast of the navigation buoy C", "North Northwest of navigation buoy C", "North Sea site", 
        "Novi Pazarn road towards Osoje", "Ocean Drilling Program Drill Hole in the North Pacific Ocean", "Orzola", 
        "Plankton-Expedition der Humbolt-Stiftung sample site (Journalnummer der Plankton-Expedition 201", 
        "Plankton-Expedition der Humbolt-Stiftung sample site (Journalnummer der Plankton-Expedition 41", "Poland-Slovak border", "Popradské Pleso", "Porto Pozzo", 
        "Prefegitura ya  Rwanda", "Provincia de Huelva Spain: Salinas de San Rafael", "Pyidaungzu Myanma Naingngandaw", "Queensland Australia", "Ramfjord", "Rothen Meeres", 
        "Sacco San Biagio", "Saint Barthelemy", "Salter Path", "Sardinia", "Savoy region", "Schleswig-Holstein", "Seljonyi", "Solomon Islands", "South Abyssinia", 
        "South Georgia Island", "South Georgia and South Sandwich", "South Orkney Islands", "South West Abyssinia", "Southern Ural, Il'myenskoye (?)", "Southern Urals", "Sptizbergen", 
        "St. Helena South", "State of Malta", "Station 223", "Station 229", "Station 230", "Station 234", "Station 241", "Station 245", "Station 248", "Station 258", 
        "Station 271", "Station 277", "Station 284", "Station 291", "Station 312", "Strait of Dover", "Sumiainen", "Sylt", "Sylt Island", "Tasmanian Sea", "Tatra mountains", 
        "Thor experimental station 166", "Tjalfe experimental station 337", "Togolese Republic", "Transilvania", "Valentia Harbor", "Wreck Beach", "Yalta", 
        "beach S of Institute of Marine Sciences", "between Ascension Island and the Equator", "between Miass and Urazbaeva?", "coast of Wales", 
        "in front of Laboratoire Arago: L'Herbier", "in front of Laboratoire Arago: La Peleteuse", "just behind the Institute of Marine Sciences", 
        "lake areas north eastern Norway and far north western Russia", "main beach", "milieu hypothelminorhéique of St. Helena", "near Ascension Island", "near Chebarkul", 
        "near Fondamento del Ponte del Piccolo", "near Les Aloès", "near Miass?", "near Stylida", "near Turgoyak", "near Urazbaeva?", "next to the vaporetto stop “Giardini”", 
        "nonspecific", "north-east from Ile Grosse", "one of the most outer islands in the archipel", "scraped from ship during Deutschen Südpolar-Expedition 1901-1903.", 
        "western beach", "western part of Sylt Island", "Îles Marquises or Archipel des Marquises or Marquises");
    }
    private function parse_ref($str)
    {   //e.g. "<a href="/turb3.php?action=10&litrec=7144&code=3749">Westblad E (1952)</a>: 9"
        // echo "\n$str\n";
        // echo "\n".strip_tags($str)."\n";
        
        if(preg_match("/action=10&(.*?)\"/ims", $str, $arr)) {
            $url = "http://turbellaria.umaine.edu/turb3.php?action=10&".$arr[1];
            // echo "\nref url: [$url]\n"; //e.g. http://turbellaria.umaine.edu/turb3.php?action=10&litrec=7144&code=3749
                                           //e.g. http://turbellaria.umaine.edu/turb3.php?action=10&litrec=21896&code=3749
            if(preg_match("/elix(.*?)&code=/ims", "elix".$url, $arr)) $url = $arr[1];
            // echo "\nref url: [$url]\n"; 
            // exit;
            
            //start parsing html
            if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                $html = str_replace("<td>", "<td >", $html);
                if(preg_match_all("/<td >(.*?)<\/td>/ims", $html, $arr2)) { 
                    $cols = $arr2[1];
                    if(count($cols) != 5) exit("\nInvestigate, may need to review connector. No. of colums for reference changed. [$url]\n");
                    // print_r($cols);
                    /* Array(
                        [0] => Westblad E
                        [1] => 1952
                        [2] => <a href="/turb3.php?action=21&litrec=7144&code=3749"><img src="/icons/small/image.png" alt="index card avail."></a><br />&nbsp;
                        [3] => Some new &quot;Alloeocoels&quot; (Turbellaria) from the Scandinavian west coast.
                        [4] => Univ Bergen Abrok, Naturvet rekke 7: 1-27
                    )*/
                    
                    if(preg_match("/litrec=(.*?)elix/ims", $url.'elix', $arr3)) $refno = $arr3[1];
                    else exit("\nCannot get refno [$url]\n");
                    
                    $cols[2] = null;
                    $cols = array_filter($cols);
                    // print_r($cols);
                    $final = implode(". ", $cols);
                    return array('ref' => str_replace("..", ".", $final), 'url' => $url, 'refno' => $refno);
                }
            }
        }
        exit("\nInvestigate there should be ref here.\n");
    }
    private function get_invalid_names($html) //get Red and Green highlighted taxa
    {
        $html = self::get_string_starting_from('table of taxa', $html);
        // <font color="red">graffi</font>
        // <font color="00cc00">calyx</font>
        $invalid_names = array();
        if(preg_match_all("/<font color=\"red\">(.*?)<\/font>/ims", $html, $arr))    $invalid_names = array_merge($invalid_names, $arr[1]);
        if(preg_match_all("/<font color=\"00cc00\">(.*?)<\/font>/ims", $html, $arr)) $invalid_names = array_merge($invalid_names, $arr[1]);
        // print_r($invalid_names);
        return $invalid_names;
    }
    private function get_downline_images($str, $id, $exclude) //action=23
    {
        if(stripos($str, 'action=23&') !== false) {//string is found
            // echo "\nwith downline image(s)\n";
            if(preg_match("/action=23&code=".$id."(.*?)\"/ims", $str, $arr)) {
                $url = $this->page['action_23'].$id.$arr[1];
                // echo "\n$url\n"; //e.g. http://turbellaria.umaine.edu/turb3.php?action=2&code=5654&smk=0
                if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                    $html = self::get_string_starting_from('table of images of species', $html);
                    $html = self::format_html($html);
                    if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
                        $final = array();
                        foreach($arr[1] as $tr) {
                            if(stripos($tr, '[no figure]') !== false) continue; //string is found
                            if(preg_match_all("/<td>(.*?)<\/td>/ims", $tr, $arr2)) {
                                $img_tbl_row = $arr2[1];
                                /* Array (e.g. $img_tbl_row
                                    [0] => calyx
                                    [1] => Graff, 1911
                                    [2] => <a href="/turb3.php?action=7&code=3197&ltr=a&eltr=&img=3197a.gif"><img src="media/thb3/3197a_thb.gif" width="90" alt="fig Allostoma calyx"></a>
                                ) */
                                if(in_array($img_tbl_row[0], $exclude)) continue;
                                // print_r($img_tbl_row);
                                
                                $downline = array();
                                if(preg_match("/&code=(.*?)&/ims", $img_tbl_row[2], $arr3)) $code = $arr3[1];
                                else exit("\nInvestigate 001 [$id]\n");
                                $downline[$code]['name'] = strip_tags($img_tbl_row[0]);
                                $downline[$code]['author'] = $img_tbl_row[1];
                                if(preg_match_all("/<img src=\"(.*?)\"/ims", $img_tbl_row[2], $arr4)) $downline[$code]['images'] = $arr4[1];
                                // print_r($downline);
                                if($downline) $final[] = $downline;
                            }
                        }
                        // print_r($final);
                        return $final;
                    }
                }
            }
        }
        return false;
    }
    private function get_direct_images($str, $id) //action=2
    {
        if(stripos($str, 'action=2&') !== false) {//string is found
            // echo "\nwith direct image(s)\n";
            //<a href="/turb3.php?action=2&code=3511&smk=1">
            if(preg_match("/action=2&code=".$id."(.*?)\"/ims", $str, $arr)) {
                $url = $this->page['action_2'].$id.$arr[1];
                // echo "\n$url\n"; //e.g. http://turbellaria.umaine.edu/turb3.php?action=2&code=5654&smk=0
                if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                    $html = self::get_string_starting_from('table of thumbnail images', $html);
                    if(preg_match_all("/<img src=\"(.*?)\"/ims", $html, $arr)) {
                        // print_r($arr[1]);
                        return $arr[1];
                    }
                }
            }
        }
        return false;
    }
    private function get_all_ids()
    {
        $stack = array();
        $main_ids = self::get_main_ids(); //get main IDs from home page
        $main_ids[] = 14676; // - Xenacoelomorpha
        $main_ids[] = 14686; // - Nephrozoa
        
        /* comment in real operation ? 8804
        $main_ids = array(8804); //14686 12856 12278 11569 11996 --- //force here
        */
        
        foreach($main_ids as $id1) {
            $ids1 = self::get_valid_ids($id1); $stack = array_merge($stack, $ids1);
            foreach($ids1 as $id2) {
                $ids2 = self::get_valid_ids($id2); $stack = array_merge($stack, $ids2);
                foreach($ids2 as $id3) {
                    $ids3 = self::get_valid_ids($id3); $stack = array_merge($stack, $ids3);
                    foreach($ids3 as $id4) {
                        $ids4 = self::get_valid_ids($id4); $stack = array_merge($stack, $ids4);
                        foreach($ids4 as $id5) {
                            $ids5 = self::get_valid_ids($id5); $stack = array_merge($stack, $ids5);
                            foreach($ids5 as $id6) {
                                $ids6 = self::get_valid_ids($id6); $stack = array_merge($stack, $ids6);
                                foreach($ids6 as $id7) {
                                    $ids7 = self::get_valid_ids($id7); $stack = array_merge($stack, $ids7);
                                    foreach($ids7 as $id8) { 
                                        $ids8 = self::get_valid_ids($id8); $stack = array_merge($stack, $ids8);
                                        foreach($ids8 as $id9) { //exit("\nlevel 8\n");
                                            $ids9 = self::get_valid_ids($id9); $stack = array_merge($stack, $ids9);
                                            foreach($ids9 as $id10) { //exit("\nlevel 9\n");
                                                $ids10 = self::get_valid_ids($id10); $stack = array_merge($stack, $ids10);
                                                foreach($ids10 as $id11) { //exit("\nlevel 10\n");
                                                    $ids11 = self::get_valid_ids($id11); $stack = array_merge($stack, $ids11);
                                                    foreach($ids11 as $id12) { //exit("\nlevel 11\n");
                                                        $ids12 = self::get_valid_ids($id12); $stack = array_merge($stack, $ids12);
                                                        foreach($ids12 as $id13) { //exit("\nlevel 12\n");
                                                            $ids13 = self::get_valid_ids($id13); $stack = array_merge($stack, $ids13);
                                                            foreach($ids13 as $id14) { //exit("\nlevel 13\n");
                                                                $ids14 = self::get_valid_ids($id14); $stack = array_merge($stack, $ids14);
                                                                foreach($ids14 as $id15) { //exit("\nlevel 14\n");
                                                                    $ids15 = self::get_valid_ids($id15); $stack = array_merge($stack, $ids15);
                                                                    foreach($ids15 as $id16) { //exit("\nlevel 15\n");
                                                                        $ids16 = self::get_valid_ids($id16); $stack = array_merge($stack, $ids16);
                                                                        foreach($ids16 as $id17) { exit("\nlevel 16\n");
                                                                            $ids17 = self::get_valid_ids($id17); $stack = array_merge($stack, $ids17);
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        echo "\n--------------------------\n";
        // print_r($stack);
        echo "\nNot unique: ".count($stack)."\n";
        $stack = array_unique($stack);
        echo "\nUnique: ".count($stack)."\n";
        // exit("\n-stopx-\n");
        return $stack;
    }
    private function get_valid_ids($id)
    {
        // if(in_array($id, array(0,1))) return array();
        $valid = array();
        $html = Functions::lookup_with_cache($this->page['action_1'].$id, $this->download_options);
        $html = self::get_string_starting_from('table of subtaxa', $html);
        if(preg_match_all("/action=1&code=(.*?)<\/td>/ims", $html, $arr)) {
            // print_r($arr[1]); exit;
            foreach($arr[1] as $row) {
                if(stripos($row, '<font color="red">') !== false) continue; //string is found
                if(stripos($row, '<font color="00cc00">') !== false) continue; //string is found
                // echo "\n[$row]";
                if(preg_match("/elix173(.*?)\"/ims", 'elix173'.$row, $arr2)) $valid[] = $arr2[1];
            }
        }
        // print_r($valid);
        return $valid;
    }
    private function get_string_starting_from($str, $html)
    {
        if(preg_match("/".$str."(.*?)elix173/ims", $html."elix173", $arr)) return $arr[1];
    }
    private function get_main_ids() //get main IDs from home page
    {
        $html = Functions::lookup_with_cache($this->page['main'], $this->download_options);
        if(preg_match_all("/action=1&code=(.*?)\"/ims", $html, $arr)) return $arr[1];
    }
    private function starts_with_small_letter($orig_string)
    {
        $orig_string = trim($orig_string);
        $string = $orig_string;
        $first_char = substr($string, 0, 1);
        if($first_char == "(") $first_char = substr($string, 1, 1);
        if(!ctype_alpha($first_char)) return true;
        if(ctype_lower($first_char)) {
            if($orig_string != "incertae sedis") return true;
        }
        return false;
    }
    private function get_object_agents($agents)
    {
        $agent_ids = array();
        foreach($agents as $agent) {
            $r = new \eol_schema\Agent();
            $r->term_name = $agent["name"];
            $r->identifier = md5($agent["name"]."|".$agent["role"]);
            $r->term_homepage = $agent["homepage"];
            $r->agentRole = $agent["role"];
            $agent_ids[] = $r->identifier;
            if(!isset($this->resource_agent_ids[$r->identifier])) {
               $this->resource_agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    private function get_biblio_citation()
    {
        if($html = Functions::lookup_with_cache($this->domain, $this->download_options)) {
            if(preg_match_all("/Cite as(.*?)<\/p>/ims", $html, $arr)) {
                $recs = $arr[1];
                $rec = array_pop($recs); //get last record
                if(preg_match("/\"(.*?)\"/ims", $rec, $arr2)) return str_replace("\n", " ", $arr2[1]);
            }
        }
    }
    private function format_html($html)
    {
        for ($x = 0; $x <= 300; $x++) $html = str_ireplace('<td  title="'.$x.'">', "<td>", $html);
        /*
        $html = str_ireplace('<td  title="0">', "<td>", $html);
        $html = str_ireplace('<td  title="1">', "<td>", $html);
        $html = str_ireplace('<td  title="2">', "<td>", $html);
        $html = str_ireplace('<td  title="3">', "<td>", $html);
        */
        $html = str_ireplace("<td >", "<td>", $html);
        $html = str_ireplace("<th >", "<th>", $html);
        $html = str_ireplace("<td>&nbsp;</td>", "", $html);
        return $html;
    }
    private function xxx()
    {
        /* manually adding Bilateria */
        $rec = array();
        $rec["sciname"] = "Bilateria";
        $rec["taxon_id"] = "Bilateria";
        $rec["authorship"] = "";
        $rec["parent_id"] = "";
        $rec["status"] = "";
        $this->create_instances_from_taxon_object($rec);
        $this->create_archive();
        /* remove temp dir */
        recursive_rmdir($this->TEMP_DIR);
        echo ("\n temporary directory removed: " . $this->TEMP_DIR);
    }
}
?>