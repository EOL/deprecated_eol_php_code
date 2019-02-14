<?php
namespace php_active_record;
// connector: [683] formerly 661
class DipteraCentralAmericaAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->domain = "http://www.phorid.net/diptera/";
        $this->taxa_list_url     = $this->domain . "diptera_index.html";
        $this->phoridae_list_url = $this->domain . "lower_cyclorrhapha/phoridae/phoridae.html";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->do_ids = array();
        $this->download_options = array('cache' => 1, 'resource_id' => 683, 'download_wait_time' => 1000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 2, 'expire_seconds' => 60*60*24*25);
        // $this->download_options['expire_seconds'] = 0;
        // $this->download_options['user_agent'] = 'User-Agent: curl/7.39.0'; // did not work here, but worked OK in USDAfsfeisAPI.php
        $this->download_options['user_agent'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)'; //worked OK!!!
        $this->page['trait_present'] = "http://phorid.net/pcat/index.php";
    }
    function start()
    {
        self::write_agent();
        self::process_diptera_main();
        self::process_phoridae_list();
        self::process_trait_data();
        $this->archive_builder->finalize(true);
    }
    private function initialize_mapping()
    {
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // print_r($this->uris);
    }
    private function process_trait_data()
    {
        self::initialize_mapping();
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        self::parse_pcat(); //Phorid Catalog (PCAT)
        ksort($this->debug['no mappings orig']);
        print_r($this->debug);
        echo "\n".count(array_keys($this->debug['no mappings']));
        echo "\n".count(array_keys($this->debug['no mappings orig']))."\n";
        // exit("\n-endx-\n");
    }
    private function parse_pcat()
    {
        if($html = Functions::lookup_with_cache($this->page['trait_present'], $this->download_options)) {
            $fields = array();
            //get headers
            if(preg_match("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
                if(preg_match_all("/<th>(.*?)<\/th>/ims", $arr[1], $arr2)) {
                    $fields = array_map('strip_tags', $arr2[1]);
                    print_r($fields);
                }
            }
            if(!$fields) exit("\nCheck fields selection, will terminate.\n");
            //parse actual trait data - present
            if(preg_match("/<tbody class=\"scrollContent\">(.*?)<\/tbody>/ims", $html, $arr)) {
                $str = $arr[1];
                //manual massage:
                $str = str_replace("<tr >", "<tr>", $str);
                $str = str_replace("</tr>", "</tr><tr>", $str); //to complete pairs of <tr></tr>
                self::parse_pcat_proper($str, $fields);
            }
        }
    }
    private function parse_pcat_proper($html, $fields)
    {
        if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
            // print_r($arr[1][0]); exit;
            $rows = $arr[1];
            echo "\n".count($rows)."\n";
            $limit = 0; //only for debug to limit
            foreach($rows as $row) {
                $limit++;
                if(($limit % 300) == 0) echo "\n".number_format($limit);
                if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $arr)) {
                    $tds = $arr[1];
                    $rec = array(); $i = -1;
                    foreach($fields as $field) {
                        $i++;
                        $rec[$field] = $tds[$i];
                    }
                    // print_r($rec); //exit;
                    /*Array(
                        [Genus] => Woodiphora
                        [Species] => apicipennis
                        [Author] => Borgmeier, 1963
                        [Distribution] => Panama, Ecuador, Brazil
                    )
                    */
                    $rec['taxon'] = $rec['Genus'].' '.$rec['Species'];
                    $rec['rank'] = 'species';
                    
                    /* good debug - process one species
                    if($rec['Distribution'] == 'Panama, Ecuador, Brazil') {
                        print_r($rec);
                        if($rec) self::process_trait_present_rec($rec);
                        break;
                    }
                    */
                    // /* normal operation
                    if($rec) self::process_trait_present_rec($rec);
                    // */
                    // if($limit >= 5) break; //debug only
                }
            }
        }
    }
    private function process_trait_present_rec($rek)
    {
        $rek = self::write_archive($rek);
        $orig_Distribution = $rek['Distribution'];
        $rek['Distribution'] = self::some_massaging($rek['Distribution']);
        $locations = explode(",", $rek['Distribution']);
        $locations = array_map('trim', $locations);
        $locations = array_filter($locations); //remove null arrays
        $locations = array_unique($locations); //make unique
        // print_r($locations); exit;
        
        foreach($locations as $string_val) {
            $string_val = self::some_massaging2($string_val);
            $mType = 'http://eol.org/schema/terms/Present';
            $taxon_id = $rek['taxon_id'];
            $rec = array();
            $rec["taxon_id"] = $taxon_id;
            $rec["catnum"] = $taxon_id.'_'.$string_val;
            if($string_uri = self::get_string_uri($string_val)) {
                $this->taxa_with_trait[$taxon_id] = ''; //to be used when creating taxon.tab
                $rec['measurementRemarks'] = $orig_Distribution;
                $rec['source'] = $this->page['trait_present'];
                // $rec['referenceID'] = '';
                // $rec['bibliographicCitation'] = '';
                $this->func->add_string_types($rec, $string_uri, $mType, "true");
            }
            else {
                $this->debug['no mappings'][$string_val] = '';
                $this->debug['no mappings orig'][$orig_Distribution] = $string_val;
            }
        }

        // http://eol.org/schema/terms/extinct
        $mValue = 'http://eol.org/schema/terms/extant';
        self::add_ExtinctionStatus($mValue, $rek);
    }
    private function add_ExtinctionStatus($mValue, $rek)
    {
        $mType = 'http://eol.org/schema/terms/ExtinctionStatus';
        $taxon_id = $rek['taxon_id'];
        $rec = array();
        $rec["taxon_id"] = $taxon_id;
        $rec["catnum"] = $taxon_id.'_'.'ExtinctionStatus';
        $this->taxa_with_trait[$taxon_id] = ''; //to be used when creating taxon.tab
        $rec['source'] = $this->page['trait_present'];
        // $rec['measurementRemarks'] = '';
        // $rec['bibliographicCitation'] = '';
        // $rec['referenceID'] = '';
        $this->func->add_string_types($rec, $mValue, $mType, "true");
    }
    private function get_string_uri($string)
    {
        switch ($string) { //put here customized mapping
            // case "NR":                return false; //"DO NOT USE";
            // case "United States of America":    return "http://www.wikidata.org/entity/Q30";
        }
        if($string_uri = @$this->uris[$string]) return $string_uri;
    }
    private function process_phoridae_list()
    {
        $path = pathinfo($this->phoridae_list_url, PATHINFO_DIRNAME);
        if($html = Functions::lookup_with_cache($this->phoridae_list_url, $this->download_options)) {
            // echo "\n$html\n"; exit;
            //onclick="MM_openBrWindow('phorid_genera/abaristophora.html','abaristophora','width=620,height=501')"><em>Abaristophora</em> Schmitz </a></p>
            if(preg_match_all("/MM_openBrWindow\(\'(.*?)\'/ims", $html, $arr)) {
                // print_r($arr[1]);
                foreach($arr[1] as $str) {
                    $url = "$path/$str";
                    // echo "\n$url\n";
                    if($html = self::get_html($url)) {
                        $html = str_ireplace('<p class="PhotoLabels">&nbsp;</p>', "", $html);
                        // exit("\n$html\n");
                        /*
                        <div class="PhoridSpecies">
                        <img src="../phoridae_images/abaristophora.jpg" width="600" height="471" alt="Abaristophora sp"/><span class="PhotoLabels"><em>Abaristophora</em> sp., male, Costa Rica: San Gerardo</span></div>
                        OR
                        <div class="PhoridSpecies">
                        <img src="../phoridae_images/trineurocephalaM128348.jpg" width="492" height="600" alt="Trineurocephala sp., male"/>
                        <p class="PhotoLabels"><em>Trineurocephala</em> sp., male, Costa Rica: San Luis</p>
                        <p class="PhotoLabels">&nbsp;</p>
                        <img src="../phoridae_images/trineurocephalaF046305.jpg" width="600" height="333" alt="Trineurocephala sp., female"/>
                        <p class="PhotoLabels"><em>Trineurocephala</em> sp., female, Costa Rica: 7 km SW Bribri</p>
                        </div>
                        */

                        $delimiter = self::get_delimiter($html, $url);
                        // echo "\n - [$delimiter]";

                        if(preg_match_all("/<img (.*?)".$delimiter."/ims", $html, $arr2)) {
                            // print_r($arr2[1]);
                            foreach($arr2[1] as $str) {
                                $rec = array();
                                if(preg_match("/src=\"(.*?)\"/ims", $str, $arr3)) {
                                    $tmp = pathinfo($url, PATHINFO_DIRNAME)."/".$arr3[1];
                                    $rec['image'] = str_replace("phorid_genera/../", "", $tmp);
                                }
                                if(preg_match("/alt=\"(.*?)\"/ims", $str, $arr3)) $rec['taxon'] = $arr3[1];
                                if(preg_match("/class=\"PhotoLabels\">(.*?)elix/ims", $str."elix", $arr3)) $rec['caption'] = $arr3[1];
                                $rec['source_url'] = $url;
                                // print_r($rec);
                                self::write_archive($rec);
                            }
                        }
                    }
                }
            }
        }
    }
    private function get_html($url)
    {   /*  good one:   _analytics_scr.src = '/_Incapsula_Resource?
            bad one:    <script src="/_Incapsula_Resource?
        */
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(stripos($html, '<script src="/_Incapsula_Resource?') !== false) //string is found
            {
                $options = $this->download_options;
                $options['expire_seconds'] = 0;
                if($html = Functions::lookup_with_cache($url, $options)) return $html;
            }
            else return $html;
        }
        else exit("\nProblem accessing [$url]\n");
    }
    private function process_diptera_main()
    {
        if($html = Functions::lookup_with_cache($this->taxa_list_url, $this->download_options)) { //get_remote_file_fake_browser
            // exit("\n$html\n");
            $exclude = array('../books/books_index.html', 'further_reading.html', 'javascript:;', '../index.html');
            // ><a href="nematocerous/ptychopteridae/ptychopteridae.html"
            if(preg_match_all("/><a href=\"(.*?)\"/ims", $html, $arr)) {
                $paths = array_diff($arr[1], $exclude);
                $paths = array_map('trim', $paths);
                // print_r($paths);
                foreach($paths as $path) {
                    $url = $this->domain.$path;
                    if($html = self::get_html($url)) {
                        self::parse_image_info($html, $url);
                    }
                }
            }
            else echo "\n-none-\n";
        }
    }
    private function get_delimiter($html, $url)
    {
        if(stripos($html, '<span class="PhotoLabels">') !== false) $delimiter = "<\/span>"; //string is found
        elseif(stripos($html, '<p class="PhotoLabels">') !== false) $delimiter = "<\/p>"; //string is found
        else exit("\nCannot get delimiter [$url]\n$html\n");
        return $delimiter;
    }
    private function parse_image_info($html, $url)
    {
        /*
        <div class="DipteraImage">
        <img src="milichiidae_image.jpg" width="400" height="472" alt="Pholeomyia politifacies"/>
        <p class="PhotoLabels"><em>Pholeomyia politifacies</em> Sabrosky 1959, Costa Rica:<br/>
        La Selva Biological Station</p>
        </div>
        */
        // echo "\n$html\n";
        $delimiter = self::get_delimiter($html, $url);
        $recs = array();
        if(preg_match("/<div class=\"DipteraImage\">(.*?)<\/div>/ims", $html, $arr)) {
            // echo "\n".$arr[1]."\n";
            if(preg_match_all("/<img (.*?)".$delimiter."/ims", $html, $arr2)) {
                // print_r($arr2[1]);
                foreach($arr2[1] as $str) {
                    $rec = array();
                    if(preg_match("/src=\"(.*?)\"/ims", $str, $arr3)) $rec['image'] = pathinfo($url, PATHINFO_DIRNAME)."/".$arr3[1];
                    if(preg_match("/alt=\"(.*?)\"/ims", $str, $arr3)) $rec['taxon'] = $arr3[1];
                    if(preg_match("/class=\"PhotoLabels\">(.*?)elix/ims", $str."elix", $arr3)) $rec['caption'] = $arr3[1];
                    $rec['source_url'] = $url;
                    // print_r($rec);
                    self::write_archive($rec);
                }
            }
        }
        // print_r($recs);
        return $recs;
    }
    function write_archive($rec)
    {
        /* froom PCAT table
        Array(
            [Genus] => Woodiphora
            [Species] => apicipennis
            [Author] => Borgmeier, 1963
            [Distribution] => Panama, Ecuador, Brazil
            [taxon] => Woodiphora apicipennis
            [rank] => species
        )*/
        
        // [image] => http://www.phorid.net/diptera/calyptratae/tachinidae/tachinidae_image3.jpg
        // [taxon] => Cordyligaster sp.
        // [caption] => <em>Cordyligaster</em> sp., Costa Rica: 1.8mi W Rincon
        // [source_url] => http://www.phorid.net/diptera/calyptratae/tachinidae/tachinidae.html
        
        $taxon = new \eol_schema\Taxon();
        // if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        // $taxon->family                  = (string) @$rec['family'];
        // $taxon->taxonRank               = (string) $rec['rank'];

        $rec['taxon'] = self::clean_sciname($rec['taxon']);
        $rec['taxon_id'] = md5($rec['taxon']);
        $taxon->taxonID                 = $rec['taxon_id'];
        $taxon->scientificName          = $rec['taxon'];
        $taxon->taxonRank                = @$rec['rank']; //from PCAT
        $taxon->scientificNameAuthorship = @$rec['Author']; //from PCAT
        $taxon->furtherInformationURL   = @$rec['source_url'];
        $taxon->order = 'Diptera';
        $taxon->class = 'Insecta';
        $taxon->kingdom = 'Animalia';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        if(@$rec['image']) self::write_image($rec);
        return $rec;
    }
    private function clean_sciname($str)
    {
        $tmp = explode(",", $str);
        $str = trim($tmp[0]);
        
        $exclude = array("sp. male", "sp. female", "sp male", "sp female", "undet. ", " sp.", " sp", "Brown male", " female");
        $str = str_ireplace($exclude, "", $str);
        $str = Functions::remove_whitespace($str);
        return $str;
    }
    private function write_agent()
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = 'phorid.net: online data for phorid flies';
        $r->agentRole       = 'publisher';
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        $r->term_homepage   = 'http://phorid.net/index.php'; //'http://www.phorid.net/diptera/diptera_index.html';
        $this->archive_builder->write_object_to_file($r);
        $this->agent_id = array($r->identifier);
    }
    private function write_image($rec)
    {
        $mr = new \eol_schema\MediaResource();
        $mr->agentID                = implode("; ", $this->agent_id);
        $mr->taxonID                = $rec["taxon_id"];
        $mr->identifier             = md5($rec['image']);
        $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
        $mr->language               = 'en';
        $mr->format                 = Functions::get_mimetype($rec['image']);
        $mr->furtherInformationURL  = $rec['source_url'];
        $mr->accessURI              = $rec['image'];
        // $mr->Owner                  = "";
        $mr->UsageTerms             = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $mr->description            = @$rec["caption"];
        // if(!$rec['caption'])
        // {
        //     print_r($rec); exit("\nno caption\n");
        // }
        if(!isset($this->obj_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->obj_ids[$mr->identifier] = '';
        }
    }
    private function some_massaging($s)
    {
        $s = str_ireplace(array("etc."), "", $s);
        $s = str_ireplace(array("&", " to ", " and "), ",", $s);
        $s = Functions::remove_whitespace($s);
        $s = str_ireplace("Central,northern South America", "Central America , South America", $s);
        $s = str_ireplace("xxx", "yyy", $s);
        $s = str_ireplace("xxx", "yyy", $s);
        $s = str_ireplace("xxx", "yyy", $s);
        $s = str_ireplace("xxx", "yyy", $s);
        $s = str_ireplace("Malaysia (Sabah, Borneo)", "Malaysia , Borneo", $s);
        $s = str_ireplace("N., S.America", "North America , South America", $s);
        $s = str_ireplace("Central , South America", "Central America , South America", $s);
        $s = str_ireplace("Zimbabwe, Mad., South Af", "Zimbabwe, Madagascar, South Africa", $s);
        $s = str_ireplace("N,C, , S.America", "North America, Central America, South America", $s);
        $s = str_ireplace("Zimbabwe, Malawi, Sth Af", "Zimbabwe, Malawi, South Africa", $s);
        $s = str_ireplace("Brazzil", "Brazil", $s);
        $s = str_ireplace("N.America", "North America", $s);
        $s = str_ireplace("S.America", "South America", $s);
        $s = str_ireplace("SouthAfrica", "South Africa", $s);
        $s = str_ireplace("Camaroon", "Cameroon", $s);
        $s = str_ireplace("USSR", "Soviet Union", $s);
        $s = str_ireplace("CentralAmerica", "Central America", $s);
        $s = str_ireplace("SEAsia", "South East Asia", $s);
        $s = str_ireplace("SE Asia", "South East Asia", $s);
        $s = str_ireplace("C. Rica", "Costa Rica", $s);
        $s = str_ireplace("Afganistan", "Afghanistan", $s);
        $s = str_ireplace("Sénégal", "Senegal", $s);
        $s = str_ireplace("NewGuinea", "New Guinea", $s);
        $s = str_ireplace("N.Amer", "North America", $s);
        $s = str_ireplace("Colom.", "Colombia", $s);
        $s = str_ireplace("Guat.", "Guatemala", $s);
        $s = str_ireplace("Borneo (Sabah)", "Borneo", $s);
        $s = str_ireplace("UAE", "United Arab Emirates", $s);
        $s = str_ireplace("Iv. Coast", "Ivory Coast", $s);
        $s = str_ireplace("N,S America", "North America , South America", $s);
        $s = str_ireplace("Phillipines", "Philippines", $s);
        $s = str_ireplace("Boliv.", "Bolivia", $s);
        $s = str_ireplace("British Guiana", "British Guyana", $s);
        $s = str_ireplace("Venezuelae", "Venezuela", $s);
        $s = str_ireplace("South , Central America", "South America , Central America", $s);
        $s = str_ireplace("N., S.America", "North America, South America", $s);
        $s = str_ireplace("Tadjikistan", "Tajikistan", $s);
        $s = str_ireplace("Ama Brazil", "Brazil", $s);
        $s = str_ireplace("xxx", "yyy", $s);
        $s = str_ireplace("xxx", "yyy", $s);
        $s = str_ireplace("xxx", "yyy", $s);
        return $s;
    }
    private function some_massaging2($s)
    {
        if($s == "Phil")        return "Philippines";
        if($s == "Canary Is")   return  "Canary Islands";
        if($s == "Argen")       return  "Argentina";
        if($s == "Britain")     return  "Great Britain";
        if($s == "Japa")        return  "Japan";
        if($s == "Mex")         return  "Mexico";
        if($s == "South Americ") return  "South America";
        if($s == "Hond.")        return  "Honduras";
        if($s == "Pana")         return  "Panama";
        if($s == "Cana")         return  "Canada";
        if($s == "Mariana Island")  return  "Mariana Islands";
        if($s == "South Af")    return  "South Africa";
        if($s == "Caroline Is") return  "Caroline Islands";
        if($s == "Canada USA")  return  "Canada";
        if($s == "Amazonian South America") return  "South America";
        if($s == "Amazonian Brazil")        return  "Brazil";
        if($s == "Cape Verde Is.")          return  "Cape Verde";
        if($s == "Cape Verde Islands")      return  "Cape Verde";
        if($s == "northern South America")  return  "South America";
        if($s == "Northern South America")  return  "South America";
        if($s == "Comoros Islands")         return  "Comoros";
        if($s == "Comoros Is")              return  "Comoros";
        if($s == "eastern Russia")          return  "Russia";
        if($s == "Western Colombia")        return  "Colombia";
        if($s == "Widespread Amazon Basin") return  "Amazon Basin";
        if($s == "tropical South America")  return  "South America";
        if($s == "worldwide")               return  "Worldwide";
        return $s;
    }
    //####################################################################################
    function get_all_taxa()
    {
        if($records = self::parse_html())
        {
            $i = 0;
            $total = count($records);
            echo "\n total records: $total";
            foreach($records as $taxon => $rec)
            {
                $i++;
                echo "\n $i of $total: " . $taxon;
                if(isset($rec[0]["image"])) self::prepare_images($taxon, $rec);
                else $this->create_instances_from_taxon_object($taxon, $rec, array());
            }
            $this->create_archive();
        }
    }

    private function prepare_object_refs($connections)
    {
        $reference_ids = array();
        $string = "";
        foreach($connections as $conn)
        {
            if($conn["title"] == "Selected References") $string = $conn["desc"];
        }
        if(preg_match_all("/<li>(.*?)<\/li>/ims", $string, $arr))
        {
            $refs = $arr[1];
            foreach($refs as $ref)
            {
                $ref = (string) trim($ref);
                if(!$ref) continue;
                $r = new \eol_schema\Reference();
                $r->full_reference = $ref;
                $r->identifier = md5($ref);
                $reference_ids[] = $r->identifier;
                if(!in_array($r->identifier, $this->resource_reference_ids))
                {
                   $this->resource_reference_ids[] = $r->identifier;
                   $this->archive_builder->write_object_to_file($r);
                }
            }
        }
        return $reference_ids;
    }

    private function parse_html()
    {
        $records = array();
        if($html = Functions::lookup_with_cache($this->taxa_list_url, $this->download_options))
        {
            $html = str_ireplace(array(' width="150"', ' align="left"', ' width="300"'), "", $html);
            if(preg_match_all("/<p class=\"FamilyNames\">(.*?)<\/div>/ims", $html, $arr))
            {
                $i = 0;
                foreach($arr[1] as $block)
                {
                    $i++;
                    // if($i != 3) continue; //debug -- to select which block to process, e.g. choosing "Lower Cyclorrhapha families:"
                    if(preg_match("/(.*?)\:/ims", $block, $match)) $group_name = trim($match[1]);
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $block, $match)) 
                    {
                        foreach($match[1] as $line)
                        {
                            $taxon_name = "";
                            $url = "";
                            if(is_numeric(stripos($line, "href=")))
                            {
                                if(preg_match("/>(.*?)</ims", $line, $match)) $taxon_name = trim($match[1]);
                                if(preg_match("/\"(.*?)\"/ims", $line, $match)) $url = trim($match[1]);
                            }
                            else $taxon_name = $line;
                            if($taxon_name != "&nbsp;")
                            {
                                if($url) $records[$taxon_name]["url"] = $this->domain . $url;
                                $records[$taxon_name]["rank"] = "family";
                                $records[$taxon_name]["taxon_id"] = self::get_taxon_id($taxon_name);
                            }
                        }
                    }
                }
            }
        }
        else
        {
            echo ("\n Problem with the remote file: $this->taxa_list_url");
            return false;
        }
        $records = self::get_genera($records);
        return $records;
    }

    private function get_taxon_id($name)
    {
        if(is_numeric(stripos($name, " sp"))) return str_ireplace(" ", "_", $name);
        else return str_ireplace(" ", "_", Functions::canonical_form($name));
    }

    private function get_genera($records)
    {
        $i = 0; $total = count($records);
        echo "\n cumulative total records: $total";
        $image_records = array();
        foreach($records as $taxon => $info)
        {
            $i++;
            echo "\n $i of $total: " . $taxon . "\n";
            // if($i != 4) continue; //debug --- to select which family to process, e.g. choosing "Phoridae" under "Lower Cyclorrhapha families:"
            if($url = @$info["url"]) 
            {
                if($html = Functions::lookup_with_cache($url, $this->download_options))
                {
                    //manual adjustment
                    $html = str_ireplace("Microdon Megacephalus", "Microdon megacephalus", $html);

                    $image_records = array_merge($image_records, self::get_images_from_genera_list_page($html, $url, $taxon));
                    /*
                    <div class="DipteraGenera">
                      <p><em>Amphicnephes</em> Loew </p>
                      <p><em>Rivellia</em> Robineau-Desvoidy </p>
                      <p><em>Senopterina</em> Macquart</p>
                    </div>
                    */
                    if(preg_match("/<div class=\"DipteraGenera\">(.*?)<\/div>/ims", $html, $match))
                    {
                        if(preg_match_all("/<p>(.*?)<\/p>/ims", $match[1], $matches))
                        {
                            $k = 0;
                            foreach($matches[1] as $genera)
                            {
                                // start getting images per genera
                                $k++; 
                                // if($k != 1) continue; //debug -- to select what row, which genera to get image from
                                if(preg_match("/openBrWindow\(\'(.*?)\'/ims", $genera, $arr))
                                {
                                    $image_page_url = $arr[1];
                                    $path_parts = pathinfo($url);
                                    $image_page_url = $path_parts["dirname"] . "/" . $image_page_url;
                                    echo("\n image_page_url: [$image_page_url] \n ");
                                    
                                    if($popup_page = Functions::lookup_with_cache($image_page_url, $this->download_options))
                                    {
                                        $records = self::scrape_image_info($popup_page, $records, $image_page_url, $taxon);
                                    }
                                }

                                // start getting each genera name
                                $genera = trim(strip_tags($genera));
                                if(!preg_match("/(Undescribed|undet)/i", $genera))
                                {
                                    $records[$genera]["url"] = $url;
                                    $records[$genera]["rank"] = "genus";
                                    $records[$genera]["family"] = $taxon;
                                    $records[$genera]["taxon_id"] = self::get_taxon_id($genera);
                                }
                            }
                        }
                        else echo "\n\n alert: investigate 01 - no genera list detected: $url \n\n";
                    }
                }
            }
            // if($i >= 1) break; //debug -- limit the no. of families
        }
        $records = array_merge($records, $image_records);
        return $records;
    }

    private function get_images_from_genera_list_page($html, $url, $family)
    {
        /*
        <div class="DipteraImage">
            <img src="tabanidae_image1.jpg" width="400" height="282" alt="Tabanus albocirculas" />
                <p class="PhotoLabels"><em>Tabanus albocirculas</em> Hine 1907, Costa Rica: La Selva Biological Station</p>
                <p class="PhotoLabels">&nbsp;</p>
            <img src="tabanidae_image2.jpg" width="400" height="304" alt="Chlorotabanus mexicanus" />
                <p class="PhotoLabels"><em>Chlorotabanus mexicanus</em> (Linnaeus 1758), Costa Rica: 29 km W Tortuguero</p>
        </div>
        <div class="DipteraImage"><img src="ptychopteridae_image.jpg" width="400" height="293" alt="Ptychoptera townesi" />
        <span class="PhotoLabels"><em>Ptychoptera townesi</em> Alexander 1943, USA: California: 4mi SW Stirling City</span>
        </div>
        */
        /*
        <div class="DipteraImage"><img src="pseudopomyzidae_image.jpg" width="400" height="278" alt="undet. Pseudopomyzidae" />
           <p class="PhotoLabels">undet. Pseudopomyzidae, Costa Rica: Albergue de Heliconia</p>
         </div>        
        */
        /*
        <div class="DipteraImage">
            <img src="syrphidae_image1.jpg" width="400" height="366" alt="Microdon megacephalus" /><span class="PhotoLabels"><em>Microdon Megacephalus</em> 
            Shannon 1929, Costa Rica: Santa Rosa NP</span>
            <p>&nbsp;</p>
          <img src="syrphidae_image2.jpg" width="400" height="314" alt="Ornidia obesa" /><span class="PhotoLabels"><em>Ornidia obesa</em> (Fabricius 1775), 
          Mexico: hills west of Fortin de las Flores </span></div>
        */
        $records = array();
        if(preg_match("/<div class=\"DipteraImage\">(.*?)<\/div>/ims", $html, $match)) $records = self::scrape_image_info($match[1], $records, $url, $family);
        return $records;
    }

    private function scrape_image_info($match, $records, $url, $family)
    {
        $match = str_ireplace("<p>&nbsp;</p>", "", $match);
        if(preg_match_all("/<img src=(.*?)<\/p>/ims", $match, $matches) || preg_match_all("/<img src=(.*?)<\/span>/ims", $match, $matches))
        {
            foreach($matches[1] as $line)
            {
                $image = "";
                $taxon = "";
                $caption = "";
                $rank = "";
                if(preg_match("/\"(.*?)\"/ims", $line, $match))
                {
                    $image = $match[1];
                    $path_parts = pathinfo($url);
                    $image = $path_parts["dirname"] . "/" . $image;
                }
                $line .= "xxx";
                if(preg_match("/class=\"PhotoLabels\">(.*?)xxx/ims", $line, $match))
                {
                    $caption = trim(strip_tags($match[1], "<em><i>"));
                    $caption = str_ireplace(array("\n", "\r", "&nbsp;"), " ", $caption);
                    $taxon = explode(",", $caption);
                    $taxon = strip_tags($taxon[0]);
                    $taxon = trim(str_ireplace(array("undet."), "", $taxon));
                }
                if($taxon == $family)
                {
                    $family = "";
                    $rank = "family";
                }
                $records[$taxon][] = array("url" => $url, "rank" => $rank, "family" => $family, "image" => $image, "caption" => $caption, "taxon_id" => self::get_taxon_id($taxon));
            }
        }
        return $records;
    }
    
}
?>