<?php
namespace php_active_record;
/* connector [185] Connector scrapes the partner's site, assembles the information and generates a DWC-A */
class TurbellarianAPI_v2
{
    function __construct($folder)
    {
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

        $this->TEMP_DIR = create_temp_dir() . "/";
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

    private function add_additional_mappings()
    {
        $url = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/Tropicos/countries with added URIs.txt";
        $options = $this->download_options;
        $options['cache'] = 1;
        $options['expire_seconds'] = 60*60*24;
        $local = Functions::save_remote_file_to_local($url, $options);
        $handle = fopen($local, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = str_replace("\n", "", $line);
                $a = explode("\t", $line); $a = array_map('trim', $a);
                $this->uri_values[$a[0]] = $a[1];
                /* not needed anymore
                $this->ctrys_with_diff_name[] = $a[0]; //what goes here is e.g. 'Burma Rep.', if orig ctry name is 'Burma' and Tropicos calls it differently e.g. 'Burma Rep.'.
                */
            }
            fclose($handle);
        } 
        else echo "\nCannot read!\n";
        unlink($local);
    }

    function start()
    {
        $this->uri_values = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($this->uri_values)."\n";
        self::add_additional_mappings(); //add more mappings specific only to this resource
        echo "\n".count($this->uri_values)."\n";
        
        
        $this->agent_ids = self::get_object_agents($this->agents);
        
        /* main operation
        $all_ids = self::get_all_ids();
        foreach($all_ids as $code) {
            // echo " $code";
            self::process_page($code);
        }
        */
        self::process_page(3159); //3158 3191 4901 3511 [5654 - has direct and downline images]  1223 3749 [6788 with downline syn]
        // self::process_page(8216);
        // self::get_valid_ids(3159);
        // exit;
        $this->archive_builder->finalize(TRUE);
        
        
        //start stats for un-mapped countries
        $OUT = Functions::file_open(DOC_ROOT."/tmp/185_unmapped_countries.txt", "w");
        $countries = array_keys($this->unmapped_countries);
        sort($countries);
        foreach($countries as $c) fwrite($OUT, $c."\n");
        fclose($OUT);
        
    }
    private function format_html($html)
    {
        $html = str_ireplace('<td  title="1">', "<td>", $html);
        $html = str_ireplace("<td >", "<td>", $html);
        $html = str_ireplace("<th >", "<th>", $html);
        $html = str_ireplace("<td>&nbsp;</td>", "", $html);
        return $html;
    }
    private function process_page($id)
    {
        $html = Functions::lookup_with_cache($this->page['action_1'].$id, $this->download_options);
        $html = self::format_html($html);
        $html = self::get_string_starting_from('table of subtaxa', $html);
        if(preg_match("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
            $str = $arr[1];
            // echo "\n[$str]\n";
            // <th>Allostoma</th>
            // <td>Beneden, 1861</td>
            $main_sci['code'] = $id;
            if(preg_match("/<th>(.*?)<\/th>/ims", $str, $arr)) $main_sci['name'] = $arr[1];
            if(preg_match("/<td>(.*?)<\/td>/ims", $str, $arr)) $main_sci['author'] = $arr[1];
            // print_r($main_sci);
            
            // $direct_images = self::get_direct_images($str, $id);                                    //action=2
            $invalid_names = self::get_invalid_names($html);
            // $downline_images = self::get_downline_images($str, $id, $invalid_names);                //action=23
            $distribution = self::parse_TableOfTaxa($html, $main_sci, $invalid_names, 'distribution');    //action=16
            // $diagnosis = self::parse_TableOfTaxa($html, $main_sci, $invalid_names, 'diagnosis');          //action=15
            // self::parse_TableOfTaxa($html, $main_sci, $invalid_names, 'downline_synonyms');            //action=6

            /*
            if($val = $direct_images) $main_sci['direct_images'] = $val;
            if($val = $downline_images) $main_sci['downline_images'] = $val;
            */
            
            
            if($main_sci['name']) self::write_to_archive($main_sci);
        }
    }
    private function write_to_archive($rec)
    {
        print_r($rec);
        // exit;
        self::write_taxon($rec);
        if($val = @$rec['downline_images']) self::write_downline_images($val, $rec['name']);
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
    private function write_downline_images($dl_images, $main_name)
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
        if(strlen($rec['code']) <= 4) $width = 1;
        elseif(strlen($rec['code']) == 5) $width = 2;
        else exit("\nInitialize code length for image writing...\n");
        
        $old = "/thb".substr($rec['code'],0,$width)."/";
        $new = "/img".substr($rec['code'],0,$width)."/";
        $path = str_replace($old, $new, $rec['path']);
        $path = $this->domain."/".str_replace("_thb", "", $path);
        // exit("\n[$path]\n");
        return $path; //this is mediaURL
    }
    private function write_image($rec)
    {
        // [path] => media/thb2/2639a_thb.jpg
        // [code] => 2639
        // http://turbellaria.umaine.edu/media/img2/2639a.jpg
        
        $mediaURL = self::format_image_path($rec);
        // print_r($rec); //exit;

        $mr = new \eol_schema\MediaResource();
        if($this->agent_ids)      $mr->agentID = implode("; ", $this->agent_ids);
        $mr->taxonID        = $rec["code"];
        $mr->identifier     = pathinfo($mediaURL, PATHINFO_BASENAME);
        $mr->type           = "http://purl.org/dc/dcmitype/StillImage";
        // $mr->language       = 'en';
        $mr->format         = Functions::get_mimetype($mediaURL);
        // $mr->title          = "";
        // $mr->CreateDate     = "";
        $mr->Owner          = $this->rights_holder;
        // $mr->rights         = "";
        $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        // $mr->audience       = 'Everyone';
        // $mr->description    = "";
        $mr->accessURI      = $mediaURL;
        if(!isset($this->object_ids[$mr->identifier])) {
           $this->object_ids[$mr->identifier] = '';
           $this->archive_builder->write_object_to_file($mr);
        }
    }
    private function write_taxon($t)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $t['code'];
        $taxon->scientificName              = $t['name'];
        $taxon->scientificNameAuthorship    = $t['author'];
        $taxon->furtherInformationURL       = $this->page['action_1'].$t['code'];
        
        if($val = @$t['acceptedNameUsageID']) {
            $taxon->acceptedNameUsageID = $val;
            $taxon->taxonomicStatus = 'synonym';
        }
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
                    $row_rec['author'] = $tr_cols[1];
                }
                
                if    ($what == 'distribution') self::get_text_object($row, $code, $this->action[$what], $what);
                elseif($what == 'diagnosis')    self::get_text_object($row, $code, $this->action[$what], $what);
                elseif($what == 'downline_synonyms') {
                    // print_r($main_sci);
                    if($syns = self::get_text_object($row, $code, $this->action[$what], $what)) {
                        $row_rec['synonyms'] = $syns;
                        print_r($row_rec);
                        
                        self::write_taxon($row_rec);
                        foreach($syns as $syn) {
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
            echo "\nwith [$what] --> ";
            // <a href="/turb3.php?action=16&code=3190&valid=0">dist'n</a>
            if(preg_match("/action=".$action."&code=".$id."(.*?)\"/ims", $str, $arr)) {
                if($val = (string) trim($arr[1])) $url = $this->page["action_".$action].$id.$val;
                else                              $url = $this->page["action_".$action].$id;
                echo "[$url]\n"; //e.g. http://turbellaria.umaine.edu/turb3.php?action=16&code=13396&valid=0
                if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                    if    ($what == 'distribution') $distributions = self::parse_distribution($html);
                    elseif($what == 'downline_synonyms') {
                        return self::parse_synonyms($html, $id);
                    }
                }
            }
        }
        return false;
    }
    private function parse_synonyms($html, $id) //$id here is only for investigation if needed
    {
        $final = array();
        $html = self::get_string_starting_from('table of synonyms', $html);
        if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr)) { //print_r($arr[1]);
            foreach($arr[1] as $row) {
                // $row = str_replace("<td >&nbsp;</td>", "", $row);
                if(preg_match_all("/<td >(.*?)<\/td>/ims", $row, $arr2)) { 
                    $temp = $arr2[1];
                    // print_r($temp);
                    if(preg_match("/&code=(.*?)\"/ims", $temp[1], $arr3)) $code = $arr3[1];
                    elseif(preg_match("/&code=(.*?)\"/ims", $temp[2], $arr3)) $code = $arr3[1];
                    else exit("\nInvestigate no code in synonym [$id]\n"); 
                    $rec = array();
                    $rec['code'] = $code;
                    if($temp[0] != "&nbsp;") $rec['name'] = Functions::remove_whitespace(strip_tags($temp[0]." ".$temp[1]));
                    else                     $rec['name'] = Functions::remove_whitespace(strip_tags($temp[1]));
                    $rec['author'] = strip_tags($temp[2]);
                    if($temp[3] != "&nbsp;") $rec['taxon_remarks'] = Functions::remove_whitespace(strip_tags($temp[3]));
                    $final[] = $rec;
                }
            }
        }
        // print_r($final);
        // exit;
        return $final;
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

                    $ctry = self::get_country_string($cols[$i['site']], $cols);
                    if($country_uri = self::get_country_uri($ctry)) {} //mapped OK
                    else {
                        $this->unmapped_countries[$ctry] = ''; //for stats only
                        continue; //will wait for Jen's mapping so we get all country strings its respective country URI
                        /* debug only
                        if($ctry == ")Croatia") {
                            print_r($cols); exit;
                        }
                        */
                    }
                    exit("\n[$ctry] [$country_uri]\n");
                    /* working but not used. Used as TraitBank rather than text object
                    $dist = $cols[$i['site']];
                    if($val = $cols[$i['collection date']]) $dist .= "<br>Collection date: " . $val;
                    if($val = $cols[$i['kind']]) $dist .= "<br>Kind: " . $val;
                    if($val = $cols[$i['depth']]) $dist .= "<br>Depth: " . $val;
                    if($val = $cols[$i['substrate']]) $dist .= "<br>Substrate: " . $val;
                    if($val = $cols[$i['salin']]) $dist .= "<br>Salin: " . $val;
                    if($val = $cols[$i['comments']]) $dist .= "<br>Comments: " . $val;
                    $rec['dist'] = $dist;
                    */
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
            /* working OK but too hard-coded, better to read the mapping from external file
            switch ($country) {
                case "United States of America":return "http://www.wikidata.org/entity/Q30";
                case "Myanmar":                 return "http://www.wikidata.org/entity/Q836";
            }
            */
        }
    }
    private function get_country_string($str, $cols)
    {
        if(stripos($str, 'Ecuador') !== false) return 'Ecuador'; //string is found
        if(stripos($str, "Il'myenskoye (?)") !== false) return $str; //string is found
        if(substr($str,0,6) == "Italy,") return "Italy";
        if(substr($str,0,6) == "Italy:") return "Italy";
        if(substr($str,-6) == ", Sylt") return "Germany";
        
        $orig_str = $str;
        $str = trim(preg_replace('/\s*\([^)]*\)/', '', $str)); //remove parenthesis
        if(!$str) $str = $orig_str;
        
        // echo "\n[$str]\n";
        $a = explode(",", $str);
        $a = array_map('trim', $a);
        $final = array_pop($a);
        if(in_array($final, self::country_sub_strings())) return $orig_str;
        
        return $final;
        // exit("\n[$ctry]\n");
    }
    private function country_sub_strings()
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
                    // print_r($cols);
                    /* Array
                    (
                        [0] => Westblad E
                        [1] => 1952
                        [2] => <a href="/turb3.php?action=21&litrec=7144&code=3749"><img src="/icons/small/image.png" alt="index card avail."></a><br />&nbsp;
                        [3] => Some new &quot;Alloeocoels&quot; (Turbellaria) from the Scandinavian west coast.
                        [4] => Univ Bergen Abrok, Naturvet rekke 7: 1-27
                    )
                    */
                    $cols[2] = null;
                    $cols = array_filter($cols);
                    // print_r($cols);
                    $final = implode(". ", $cols);
                    return array('ref' => str_replace("..", ".", $final), 'url' => $url);
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
            echo "\nwith downline image(s)\n";
            if(preg_match("/action=23&code=".$id."(.*?)\"/ims", $str, $arr)) {
                $url = $this->page['action_23'].$id.$arr[1];
                echo "\n$url\n"; //e.g. http://turbellaria.umaine.edu/turb3.php?action=2&code=5654&smk=0
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
            echo "\nwith direct image(s)\n";
            //<a href="/turb3.php?action=2&code=3511&smk=1">
            if(preg_match("/action=2&code=".$id."(.*?)\"/ims", $str, $arr)) {
                $url = $this->page['action_2'].$id.$arr[1];
                echo "\n$url\n"; //e.g. http://turbellaria.umaine.edu/turb3.php?action=2&code=5654&smk=0
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
        
        // $main_ids = array(12823); //14686 12856 12278
        
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
        if(ctype_lower($first_char))
        {
            if($orig_string != "incertae sedis") return true;
        }
        return false;
    }
    private function get_object_agents($agents)
    {
        $agent_ids = array();
        foreach($agents as $agent)
        {
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
