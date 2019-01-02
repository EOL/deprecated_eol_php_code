<?php
namespace php_active_record;
/* connector: [cites_species.php] */
class CITESspeciesAPI
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array(
            'resource_id'        => 'CITES',    //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30, //expires in 1 month
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        $this->debug = array();
        $this->service['per_page'] = 250; //orig 250, half of suggested which is 500
        $this->service['taxa'] = "https://api.speciesplus.net/api/v1/taxon_concepts?per_page=".$this->service['per_page']."&page=";
        $this->service['distribution'] = "https://api.speciesplus.net/api/v1/taxon_concepts/taxon_concept_id/distributions";
        $this->service['token'] = "qHNzqizUVrNlriueu8FSrQtt";
        if(Functions::is_production()) $this->download_options['cache_path']   = '/extra/eol_php_cache2/';
        else                           $this->download_options['cache_path']   = '/Volumes/AKiTiO4/eol_php_cache2/';
        // $this->download_options['expire_seconds'] = 0; //to force re-create cache. comment in normal operation
    }
    private function initialize_mapping()
    {
        $mappings = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        $mappings2 = Functions::get_eol_defined_uris_v1(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        $mappings = array_merge($mappings, $mappings2);
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
    }
    function start()
    {
        self::initialize_mapping(); //un-comment in real operation
        if(!is_dir($this->download_options['cache_path'])) mkdir($this->download_options['cache_path']);
        /*
        $json = '{"error":{"message":"Unpermitted parameters (per_page, page)"}}';
        $obj = json_decode($json);
        if(@$obj->error) {
            print_r($obj);
        }
        else echo "\nno error\n";
        exit;
        */
        /* just test
        self::get_distribution_per_id(4442); //works OK
        exit("\n-end test-\n");
        */

        $total_pages = self::get_total_pages();
        $page = 0; //normal operation
        // $page = 100; //debug only - force
        while($page <= $total_pages) {
            $page++;
            $url = $this->service['taxa'].$page;
            $cmd = 'curl "'.$url.'" -H "X-Authentication-Token:'.$this->service['token'].'"';
            echo "\n$cmd\n";
            $json = self::get_json_from_cache($cmd, $this->download_options);
            $obj = json_decode($json);
            if(@$obj->taxon_concepts) {
                $total_entries = count($obj->taxon_concepts);
                echo "\nNo. of taxa in this batch: ".$total_entries."\n";
                self::process_taxa($obj);
            }
            else {
                if($page <= $total_pages) {
                    echo "\nwe need to expire cache and overwrite the erroneous cache\n";
                    sleep(60*2);
                    //copied from above start ------------------------------------------------------------
                    $options = $this->download_options;
                    $options['expire_seconds'] = 0;
                    $json = self::get_json_from_cache($cmd, $options);
                    $obj = json_decode($json);
                    if(@$obj->taxon_concepts) {
                        $total_entries = count($obj->taxon_concepts);
                        echo "\nNo. of taxa in this batch (2nd try): ".$total_entries."\n";
                        self::process_taxa($obj);
                        continue;
                    }
                    //copied from above end ------------------------------------------------------------
                }
                break;
            }
            // if($page >= 5) break;   //debug only
            // break;   //debug only
        }
        // exit("\n-exitx-\n");
        $this->archive_builder->finalize(true);
        print_r($this->debug['status']);

        //massage debug for printing
        $countries = array_keys($this->debug['COUNTRY']); asort($countries);
        $territories = array_keys($this->debug['TERRITORY']); asort($territories);
        $this->debug = array();
        foreach($countries as $c) $this->debug['COUNTRY'][$c] = '';
        foreach($territories as $c) $this->debug['TERRITORY'][$c] = '';
        Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function process_taxa($object)
    {
        // print_r($object); exit;
        foreach($object->taxon_concepts as $obj) {
            /*  [id] => 12163
                [full_name] => Antilocapra americana mexicana
                [author_year] => Merriam, 1901
                [rank] => SUBSPECIES
                [name_status] => A
                [updated_at] => 2017-02-03T00:00:00.000Z
                [active] => 1
                [cites_listing] => I
                [higher_taxa] => stdClass Object(
                        [kingdom] => Animalia
                        [phylum] => Chordata
                        [class] => Mammalia
                        [order] => Artiodactyla
                        [family] => Antilocapridae
                    )*/
            $this->debug['status'][$obj->name_status] = '';
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                  = $obj->id;
            $taxon->scientificName           = $obj->full_name;
            $taxon->scientificNameAuthorship = $obj->author_year;
            $taxon->taxonRank                = strtolower($obj->rank);
            $ranks = array('kingdom','phylum','class','order','family');
            foreach($ranks as $rank) {
                if($rank != $taxon->taxonRank) {
                    if($val = @$obj->higher_taxa->{"$rank"}) $taxon->{"$rank"} = $val;
                }
            }
            // $taxon->furtherInformationURL   = $rec['furtherInformationURL'];
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
            /* synonyms are un-cleaned, unreliable
            if($val = @$obj->synonyms)     self::write_synonyms($val, $taxon->taxonID);
            */
            if($val = @$obj->common_names) self::write_comnames($val, $taxon->taxonID);
            self::get_distribution_per_id($taxon->taxonID);
        }
    }
    private function get_distribution_per_id($taxon_id)
    {
        $url = str_replace("taxon_concept_id", $taxon_id, $this->service['distribution']);
        $cmd = 'curl "'.$url.'" -H "X-Authentication-Token:'.$this->service['token'].'"';
        echo "\n$cmd\n";
        $json = self::get_json_from_cache($cmd, $this->download_options);
        $obj = json_decode($json);
        // print_r($obj); exit;
        echo "\nDistributions: ".count($obj)."\n";
        /*Array(
            [0] => stdClass Object(
                    [id] => 50
                    [iso_code2] => HR
                    [name] => Croatia
                    [tags] => Array()
                    [type] => COUNTRY
                    [references] => Array(
                            [0] => Mitchell-Jones, A. J., Amori, G., Bogdanowicz, W., Krystufek, B., Reijnders, P. J. H., Spitzenberger, F., Stubbe, M., Thissen, J. B. M. et al. 1999. The atlas of European mammals. T. & A. D. Poyser. London.
                        )
                )*/
        if($obj) {
            foreach($obj as $d) {
                
                if($country = @$d->name) {
                    $rec = array();
                    $rec["taxon_id"] = $taxon_id;
                    $rec["catnum"] = $taxon_id.$d->id;
                    if($country_uri = self::get_country_uri($country)) {
                        self::add_string_types($rec, $country_uri, "http://eol.org/schema/terms/Present", "true");
                    }
                    else $this->debug[$d->type][$country] = '';
                }
            }
        }
    }
    private function get_country_uri($country)
    {
        if($country_uri = @$this->uris[$country]) return $country_uri;
        else {
            switch ($country) { //put here customized mapping
                // case "Port of Entry":                return false; //"DO NOT USE";
                // case "United States of America":     return "http://www.wikidata.org/entity/Q30";
                // case "Dutch West Indies":            return "http://www.wikidata.org/entity/Q25227";
            }
        }
    }
    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"].$measurementType; //because one catalog no. can have 2 MeasurementOrFact entries. Each for country and habitat.
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum, $rec);
        $m = new \eol_schema\MeasurementOrFact();
        $m->occurrenceID       = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        if($measurementOfTaxon == "true") {
            $m->source      = @$rec["url"];
            $m->contributor = @$rec["contributor"];
            if($referenceID = @$rec["referenceID"]) $m->referenceID = $referenceID;
        }
        $m->measurementType  = $measurementType;
        $m->measurementValue = $value;
        // $m->bibliographicCitation = '';
        if($val = @$rec['measurementUnit'])     $m->measurementUnit = $val;
        if($val = @$rec['measurementMethod'])   $m->measurementMethod = $val;
        if($val = @$rec['statisticalMethod'])   $m->statisticalMethod = $val;
        if($val = @$rec['measurementRemarks'])  $m->measurementRemarks = $val;
        // $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue')); //3rd param is optional. If blank then it will consider all properties of the extension
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id); //3rd param is optional. If blank then it will consider all properties of the extension
        
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }
    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = md5($taxon_id . '_' . $catnum);
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $o->catalogNumber = @$rec['catalogNumber'];
        $o->dateIdentified = @$rec['dateIdentified'];
        $o->eventDate = @$rec['dateCollected'];
        // $o->locality = '';
        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    private function write_comnames($comnames, $taxon_id)
    {
        /*[common_names] => Array(
            [0] => stdClass Object(
                    [name] => Vlk
                    [language] => CS
                )
        */
        foreach($comnames as $obj) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $taxon_id;
            $v->vernacularName  = $obj->name;
            $v->language        = strtolower($obj->language);
            $id = md5($obj->name.$obj->language.$taxon_id);
            if(!isset($this->vernaculars[$id])) {
                $this->archive_builder->write_object_to_file($v);
                $this->vernaculars[$id] = '';
            }
        }
    }
    private function write_synonyms($synonyms, $accepted_id)
    {
        foreach($synonyms as $obj) {
            /*[synonyms] => Array(
                [0] => stdClass Object(
                        [id] => 32181
                        [full_name] => Canis himalayensis
                        [author_year] => Aggarwal, Kivisild, Ramadevi & Singh, 2007
                        [rank] => SPECIES
                    )*/
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                  = $obj->id;
            $taxon->acceptedNameUsageID      = $accepted_id;
            $taxon->scientificName           = $obj->full_name;
            $taxon->scientificNameAuthorship = $obj->author_year;
            $taxon->taxonRank                = strtolower($obj->rank);
            $taxon->taxonomicStatus          = "synonym";
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function get_json_from_cache($name, $options = array()) //json generated by CITES service
    {
        if(!isset($options['expire_seconds'])) $options['expire_seconds'] = false;
        if(!isset($options['cache_path'])) $options['cache_path'] = $this->download_options['cache_path'];

        if($resource_id = @$options['resource_id']) {
            $options['cache_path'] .= "$resource_id/";
            if(!file_exists($options['cache_path'])) mkdir($options['cache_path']);
        }

        $md5 = md5($name);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
        echo "\ncache_path: [$cache_path]\n";
        if(file_exists($cache_path)) {
            // echo "\nRetrieving cache ($name)...\n"; //good debug
            $file_contents = file_get_contents($cache_path);
            $cache_is_valid = true;
            if(($file_contents && $cache_is_valid) || (strval($file_contents) == "0" && $cache_is_valid)) {
                $file_age_in_seconds = time() - filemtime($cache_path);
                if($file_age_in_seconds < $options['expire_seconds']) return $file_contents;
                if($options['expire_seconds'] === false) return $file_contents;
            }
            @unlink($cache_path);
        }
        //generate json
        echo "\nGenerating cache json for the first time ($name)...\n";
        $cmd = $name;
        $json = shell_exec($cmd);
        if(true) {
            $json = Functions::conv_to_utf8($json);
            if(!$json) $json = "[]";
            if($FILE = Functions::file_open($cache_path, 'w+')) {
                fwrite($FILE, $json);
                fclose($FILE);
            }
            // just to check if you can now get the canonical
            $obj = json_decode($json);
            if(@$obj->error) {
                print("\n---------------------\n");
                print_r($obj);
                echo "\ncommand is: [$cmd]\n";
                exit("\n---------------------\n");
            }
        }
        return $json;
    }
    private function get_total_pages()
    {
        $page = 1;
        $url = $this->service['taxa'].$page;
        $cmd = 'curl "'.$url.'" -H "X-Authentication-Token:'.$this->service['token'].'"';
        echo "\n$cmd\n";
        $json = self::get_json_from_cache($cmd, $this->download_options);
        $obj = json_decode($json);
        print_r($obj->pagination);
        $total_pages = $obj->pagination->total_entries/$this->service['per_page'];
        // $total_pages = $obj->pagination->total_entries/250;
        echo "\n$total_pages\n";
        $total_pages = ceil($total_pages);
        echo "\n$total_pages\n";
        return $total_pages;
    }
    //#######################################################################################################################################################################
    private function create_archive($rec)
    {
        $rec['taxon_id'] = $rec['FAO Names']['taxonomic_code'];
        // print_r($rec);
        self::create_taxon($rec);
        self::create_vernaculars($rec);
        if($val = @$rec['Diagnostic Features'])   self::create_text_object($val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription", $rec);
        if($val = @$rec['Habitat and Biology'])   self::create_text_object($val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology", $rec);
        if($val = @$rec['Size'])                  self::create_text_object($val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size", $rec);
        if($val = @$rec['Interest to Fisheries']) self::create_text_object($val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Use", $rec);
    }
    private function create_vernaculars($rec)
    {   /*[FAO Names] => Array(
                [taxonomic_code] => 1431300105
                [comnames] => Array(
                        [0] => Array(
                                [lang] => en
                                [comname] => Whitespotted conger
                            )
        */
        if($names = @$rec['FAO Names']['comnames']) {}
        else return;
        foreach($names as $r) {
            if(!$r['comname']) continue;
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $rec['taxon_id'];
            $v->vernacularName  = $r['comname'];
            $v->language        = $r['lang'];
            $id = md5($r['comname'].$r['lang']);
            if(!isset($this->vernaculars[$id])) {
                $this->archive_builder->write_object_to_file($v);
                $this->vernaculars[$id] = '';
            }
        }
    }
    private function create_text_object($txt, $subject, $rec)
    {
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $rec['taxon_id'];
            $tmp = pathinfo($subject, PATHINFO_FILENAME);
            $tmp = explode("#", $tmp);
        $mr->identifier     = $rec['taxon_id']."_".$tmp[1];
        $mr->type           = 'http://purl.org/dc/dcmitype/Text';
        $mr->language       = 'en';
        $mr->format         = 'text/html';
        $mr->furtherInformationURL = $rec['furtherInformationURL'];
        $mr->CVterm         = $subject;
        // $mr->Owner          = '';
        // $mr->rights         = $o['dc_rights'];
        // $mr->title          = $o['dc_title'];
        $mr->UsageTerms     = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
        // $mr->audience       = 'Everyone';
        $mr->description    = $txt;
        // $mr->LocationCreated = $o['location'];
        $mr->bibliographicCitation = $rec['biblio'];
        if($reference_ids = self::create_references($rec))  $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids     = self::create_agents())          $mr->agentID     = implode("; ", $agent_ids);
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    private function create_agents()
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = 'Food and Agriculture Organization of the UN';
        $r->agentRole       = 'author';
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        $r->term_homepage   = 'http://www.fao.org/home/en/';
        $agent_ids[] = $r->identifier;
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = $r->term_name;
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }
    private function create_references($rec)
    {
        if($refs = $rec['references']) {}
        else return false;
        $reference_ids = array();
        foreach($refs as $ref) {
            $r = new \eol_schema\Reference();
            $r->full_reference = $ref;
            $r->identifier = md5($r->full_reference);
            // $r->uri = '';
            $reference_ids[] = $r->identifier;
            if(!isset($this->reference_ids[$r->identifier])) {
                $this->reference_ids[$r->identifier] = '';
                $this->archive_builder->write_object_to_file($r);
            }
        }
        return array_unique($reference_ids);
    }
    private function create_taxon($rec)
    {   /*Array(
            [sciname] => Boops boops (Linnaeus, 1758) 
            [furtherInformationURL] => http://www.fao.org/fishery/species/2385/en
            [FAO Names] => Array(
                    [taxonomic_code] => 1703926101
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['FAO Names']['taxonomic_code'];
        $taxon->scientificName          = $rec['sciname'];
        $taxon->taxonRank               = 'species';
        $taxon->furtherInformationURL   = $rec['furtherInformationURL'];
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function get_references($html, $id)
    {   /*>Bibliography</div><div>
        <div class="sourceEntryTitle">Compagno, 1984</div>
        <div class="sourceEntryTitle">Fowler, 1941</div>
        <div class="sourceEntryTitle">Goto &amp; Nakaya, 1996</div>
        <div class="sourceEntryTitle">Kharin, 1987</div>
        <div class="sourceEntryTitle">Smith, 1913</div>
        <div class="sourceEntryTitle">Teng, 1959b</div>
        </div></div></td></tr></tbody></table>
        */
        if(preg_match("/>Bibliography<\/div>(.*?)<\/table>/ims", $html, $arr)) {
            if(preg_match_all("/<div class=\"sourceEntryTitle\">(.*?)<\/div>/ims", $arr[1], $arr2)) {
                $arr3 = $arr2[1];
                $tmp = implode("xxxelixxx", $arr3);
                $tmp = str_replace(array("&nbsp;","<p>"), " ", $tmp);
                $tmp = Functions::remove_whitespace($tmp);
                $tmp = str_replace(array(". .",".."), ".", $tmp);
                $final = explode("xxxelixxx", $tmp);
                $final = array_map('trim', $final);
                return $final;
            }
        }
        else $this->debug['No refs'][$id] = '';
    }
    private function get_sciname($html, $id)
    {
        if(preg_match("/<td id=\"head_title_instance\" style=\";font-style:italic\">(.*?)<\/td>/ims", $html."xxx", $arr)) $rec['sciname'] = strip_tags($arr[1]);
        else exit("\nNo sciname [$id]\n");
        $rec['furtherInformationURL'] = str_replace("the_id", $id, $this->factsheet_page);
        return $rec;
    }
    private function other_str_format($str)
    {
        $str = trim($str);
        if(substr($str,0,3) == "<p>") $str = trim(substr($str,3,strlen($str)));
        if(substr($str, -3) == "<p>") $str = substr($str,0,strlen($str)-3);
        return $str;
    }
    private function parse_Geographical_Distribution($str, $id)
    {
        // echo "\n[$str]\n";
        $str = str_replace(". </i>","</i> .", $str);
        $str = str_replace("e. g.", "e.g.", $str);
        
        $arr = explode("<p>", $str);
        // print_r($arr);
        if($str = @$arr[2]) {}
        else {
            return;
            exit("\nInvestigate id [$id]. No geographical dist.\n");
        }
        
        $letters = array("N.W","e.g","i.e","fig19","St","fig","D","S","P","L","A","E","h","p","N","R","M","O","T","I","C");
        foreach($letters as $letter) $str = str_replace($letter.". ", $letter."xxx ", $str);
        // $str = str_replace("S. ", "Sxxx ", $str);
        // $str = str_replace("P. ", "Pxxx ", $str);
        // $str = str_replace("L. ", "Lxxx ", $str);
        
        $arr = explode(". ", $str." ");
        $arr = array_map('trim', $arr);
        $arr = array_filter($arr);
        // print_r($arr);
        foreach($arr as $distrib) {
            foreach($letters as $letter) $distrib = str_replace($letter."xxx ", $letter.". ", $distrib);
            $this->debug['Geographical Distribution'][$distrib] = '';
        }
        return "";
    }
    private function parse_Local_Names($str)
    {
        //manual adjustments
        $str = str_replace("(see Bini, 1970:56)", "(see Bini, 1970[colon]56)", $str);
        
        $comnames = array();
        /* 
        Japan : <p> <p> Higezame . 
        Mexico : <p> <p> Sand shark , <p> Gata .West Indies : <p> <p> Sand shark , <p> Gata .Brazil : <p> <p> Gata atlantica , <p> Cacao lixa .
        */
        $str = strip_tags($str);
        $str = Functions::remove_whitespace($str);
        
        $letters = array("incl","are","etc","U.S.A","S");
        foreach($letters as $letter) $str = str_replace($letter.". ", $letter."xxx ", $str);
        
        $arr = explode('. ', $str." ");
        $arr = array_map('trim', $arr);
        $arr = array_filter($arr);
        // print_r($arr);
        foreach($arr as $val) {
            foreach($letters as $letter) $val = str_replace($letter."xxx ", $letter.". ", $val);
            
            $arr2 = explode(":", $val);
            $arr2 = array_map('trim', $arr2);
            
            $lang_ctry = trim($arr2[0]);
            $lang_ctry = str_replace("(see Bini, 1970[colon]56)", "(see Bini, 1970:56)", $lang_ctry);
            
            if($val = @$arr2[1]) {
                $arr3 = explode(",", $val);
                $arr3 = array_map('trim', $arr3);
                // print_r($arr3);
                foreach($arr3 as $comname) {
                    $this->debug['Country Local Names'][$lang_ctry] = '';
                    $comnames[] = array("lang" => $lang_ctry, "comname" => $comname);
                }
            }
        }
        // exit("\n-end Local Names-\n");
        return $comnames;
    }
    private function parse_FAO_Names($str, $id)
    {
        $final = array();
        // $str = str_replace("&nbsp;", " ", $str);
        // $str = Functions::remove_whitespace($str);
        // echo "\n[$str]\n";
        if(preg_match("/Taxonomic Code:(.*?)xxx/ims", $str."xxx", $arr)) $final['taxonomic_code'] = trim($arr[1]);
        else exit("\nNo taxonomic_code [$id]\n");
        //get comnames
        $tmp = explode("3Alpha", $str);
        $str = $tmp[0];
        $str = trim(strip_tags($str));
        $str = str_replace(".", "", $str);
        // echo "\n[$str]\n";
        $arr = explode(",", $str);
        $arr = array_map("trim", $arr);
        // print_r($arr);
        $comnames = array();
        foreach($arr as $val) {
            $tmp = explode(" - ", $val);
            if($val = @$tmp[1]) $comnames[] = array("lang" => strtolower($tmp[0]), "comname" => $val);
        }
        $final['comnames'] = $comnames;
        // print_r($final);
        // exit("\n-end FAO-\n");
        return $final;
    }
}
?>