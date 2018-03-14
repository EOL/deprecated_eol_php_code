<?php
namespace php_active_record;
/* connector: [879] 
http://en.wikipedia.org/wiki/Portal:Fungi
*/
class WikipediaMycologicalAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('download_wait_time' => 2000000, 'timeout' => 172800, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*25); //expires in 25 days

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_cache_wiki_regions/";
        else                           $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache_wiki_regions/";

        $this->wikipedia_fungal_species = "http://en.wikipedia.org/wiki/Category:Lists_of_fungal_species";
        $this->mushroom_observer_eol    = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Wikipedia/mushroom_observer_eol.xml";
        $this->triple_uris_spreadsheet  = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Wikipedia/wikimushrooms.xlsx";
        /*
        $this->mushroom_observer_eol    = "http://localhost/cp/Wikipedia/mushroom_observer_eol.xml";
        $this->triple_uris_spreadsheet  = "http://localhost/cp/Wikipedia/wikimushrooms.xlsx";
        */
        
        $this->dump_file = DOC_ROOT . "temp/wikipedia_wrong_urls.txt";
        $this->triples_file = DOC_ROOT . "temp/wikipedia_triples.txt";
        $this->unique_triples = array();
    }

    function get_all_taxa()
    {
        $this->uris = self::get_uris();
        $wrong_urls = self::get_urls_from_dump($this->dump_file);
        self::process_wikepedia_fungal_list($wrong_urls);
        self::process_mushroom_observer_list($wrong_urls);
        if(!($WRITE = fopen($this->triples_file, "w"))) return;
        else fclose($WRITE); //initialize file
        foreach(array_keys($this->unique_triples) as $triple) self::save_to_dump($triple, $this->triples_file);
        echo "\n count of scinames: "              . count($this->debug["sciname"]);
        echo "\n count of scinames with triples: " . count($this->debug["sciname with triples"]);
        $this->archive_builder->finalize(TRUE);
        
        if($val = @$this->debug['undefined']) print_r($val);
    }

    private function get_uris()
    {
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();
        $spreadsheet_options = array("cache" => 1, "timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2);
        // $spreadsheet_options["expire_seconds"] = 0; // false => won't expire; 0 => expires now
        $temp = $func->convert_spreadsheet($this->triple_uris_spreadsheet, 0, $spreadsheet_options);
        /* spreadsheet headers: Wikipedia triple - Measurement Type - Measurement Value1 - Measurement Value2 */
        $uris = array();
        $i = -1;
        foreach($temp["Wikipedia triple"] as $triple) {
            $i++;
            if($temp["Measurement Type"][$i] == "EXCLUDE") continue;
            $uris[$triple]["mtype"] = $temp["Measurement Type"][$i];
            $uris[$triple]["v1"]    = @$temp["Measurement Value1"][$i];
            $uris[$triple]["v2"]    = @$temp["Measurement Value2"][$i];
        }
        return $uris;
    }

    private function process_wikepedia_fungal_list($wrong_urls)
    {
        $urls = array();
        if($html = Functions::lookup_with_cache($this->wikipedia_fungal_species, $this->download_options)) {
            //<a href="/wiki/List_of_Agaricus_species" title="List of Agaricus species">
            if(preg_match_all("/<li><a href=\"\/wiki\/List_of_(.*?)\"/ims", $html, $arr)) {
                print_r($arr[1]);
                foreach($arr[1] as $path) {
                    if(!is_numeric(stripos($path, "_species"))) continue;
                    $parts = explode("_", $path);
                    $genus = $parts[0];
                    if($html = Functions::lookup_with_cache("http://en.wikipedia.org/wiki/List_of_" . $path, $this->download_options)) {
                        $html = strip_tags($html, "<li><a>");
                        //<a href="/wiki/Amanita_albocreata"
                        if(preg_match_all("/<a href=\"\/wiki\/" . $genus . "(.*?)\"/ims", $html, $arr2)) {
                            foreach($arr2[1] as $path) $urls[] = "http://en.wikipedia.org/" . "wiki/$genus" . $path;
                        }
                        // /*
                        //http://en.wikipedia.org/w/index.php?title=Pertusaria_aberrans&action=edit&redlink=1
                        if(preg_match_all("/<a href=\"\/w\/index.php\?title=" . $genus . "(.*?)\"/ims", $html, $arr2)) {
                            foreach($arr2[1] as $path) $urls[] = "http://en.wikipedia.org/" . "w/index.php?title=$genus" . $path;
                        }
                        // */
                    }
                    // break; //debug
                }
            }
        }
        
        $urls = array_filter($urls);
        $i = 0;
        $total = count($urls);
        foreach($urls as $url) {
            $i++; 
            if(($i % 50) == 0) echo "\n$i of $total";
            $url = str_ireplace("&amp;", "&", $url);
            self::get_triple($url, $wrong_urls);
        }
    }
    
    private function process_mushroom_observer_list($wrong_urls)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($file = Functions::lookup_with_cache($this->mushroom_observer_eol, $options)) {
            $xml = simplexml_load_string($file);
            $i = 0;
            $total = count($xml->taxon);
            foreach($xml->taxon as $t) {
                $i++;
                // if($i > 40) break; //debug
                $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
                $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
                $sciname = Functions::import_decode($t_dwc->ScientificName);
                $sciname = Functions::canonical_form($sciname);
                echo "\n$i of $total: $sciname";
                $url = "http://en.wikipedia.org/wiki/" . str_replace(" ", "_", $sciname);
                self::get_triple($url, $wrong_urls);
            }
        }
    }
    
    private function get_triple($url, $wrong_urls)
    {
        if(in_array($url, $wrong_urls)) return;
        $rec = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            //sciname
            if(preg_match("/<h1 id=\"firstHeading\"(.*?)<\/h1>/ims", $html, $arr)) $rec["sciname"] = strip_tags("<h1 " . $arr[1]);
            //ancestry
            $ranks = array("kingdom", "division", "phylum", "class", "order", "family", "genus");
            foreach($ranks as $rank) {
                if(preg_match("/<span class=\"" . $rank . "\"(.*?)<\/span>/ims", $html, $arr)) $rec["ancestry"][$rank] = strip_tags("<span " . $arr[1]);
            }
            //triples
            if(preg_match("/title=\"Mycology\">Mycological characteristics(.*?)<\/table>/ims", $html, $arr)) {
                if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1], $arr)) {
                    foreach($arr[1] as $row) {
                        $row = strip_tags($row);
                        $row = trim(str_replace(array("\n"), " ", $row));
                        $rec["triples"][] = $row;
                    }
                }
            }
            // fix the 'or ' phrase; and saving it to $this->unique_triples
            if(@$rec["triples"]) {
                $i = 0;
                foreach(@$rec["triples"] as $triple) {
                    if(substr($triple, 0, 3) == "or ") {
                        $rec["triples"][$i-1] .= " " . $rec["triples"][$i];
                        $rec["triples"][$i] = "";
                    }
                    $i++;
                }
                $rec["triples"] = array_filter($rec["triples"]);
                foreach($rec["triples"] as $triple) $this->unique_triples[$triple] = '';
            }
            
            if($sciname = $rec["sciname"]) {
                if($sciname) $this->debug["sciname"][$sciname] = '';
                if(@$rec["triples"]) $this->debug["sciname with triples"][$sciname] = '';
            }
            
        }
        else self::save_to_dump($url, $this->dump_file);
        $rec["source"] = $url;
        if(@$rec["sciname"]) self::create_instances_from_taxon_object($rec);
        else {
            /*
            echo "\n No sciname";
            print_r($rec);
            */
        }
    }
    
    private function get_urls_from_dump($fname)
    {
        $urls = array();
        if($filename = Functions::save_remote_file_to_local($fname, $this->download_options)) {
            foreach(new FileIterator($filename) as $line_number => $line) {
                if($line) $urls[$line] = '';
            }
            unlink($filename);
        }
        return array_keys($urls);
    }
    
    private function create_instances_from_taxon_object($rec)
    {
        /* sample $rec value:
        [sciname] => Cystoderma carcharias
        [ancestry] => Array
            (
                [kingdom] => Fungi
                [phylum] => Basidiomycota
                [class] => Agaricomycetes
                [order] => Agaricales
                [family] => Agaricaceae
                [genus] => Cystoderma
            )
        [triples] => Array
            (
                [0] => gills on hymenium
                [1] => cap is convex or flat or adnate
                [3] => stipe has a ring
                [4] => spore print is white
                [5] => ecology is saprotrophic
                [6] => edibility: inedible
            )
        */
        if(@$rec["triples"]) {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = str_replace(" ", "_", $rec["sciname"]);
            $taxon->scientificName  = $rec["sciname"];
            $taxon->kingdom         = @$rec["ancestry"]["kingdom"];
            $taxon->phylum          = @$rec["ancestry"]["phylum"];
            $taxon->class           = @$rec["ancestry"]["class"];
            $taxon->order           = @$rec["ancestry"]["order"];
            $taxon->family          = @$rec["ancestry"]["family"];
            $taxon->genus           = @$rec["ancestry"]["genus"];
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
            $rec["taxon_id"] = $taxon->taxonID;
            
            // structured data
            foreach($rec["triples"] as $triple) {
                if($triple == "hymenium attachment is not applicable") continue; //excluded per Jen's spreadsheet
                if($mtype = $this->uris[$triple]["mtype"]) {
                    if($v1 = $this->uris[$triple]["v1"]) {
                        $rec["catnum"] = pathinfo($v1, PATHINFO_FILENAME);
                        self::add_string_types($rec, $v1, $mtype);
                    }
                    if($v2 = $this->uris[$triple]["v2"]) {
                        $rec["catnum"] = pathinfo($v2, PATHINFO_FILENAME);
                        self::add_string_types($rec, $v2, $mtype);
                    }
                }
                else {
                    print_r($rec);
                    echo "\n[$triple]";
                    $this->debug['undefined'][$triple] = '';
                    // exit("\n-investigate-\n");
                }
            }
        }
    }

    private function add_string_types($rec, $value, $mtype)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum);
        $m = new \eol_schema\MeasurementOrFact();
        $m->occurrenceID        = $occurrence_id;
        $m->measurementType     = $mtype;
        $m->measurementValue    = $value;
        $m->measurementOfTaxon  = 'true';
        $m->measurementMethod   = 'crowdsourced';
        $m->source              = $rec["source"];

        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);
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
        
        /* old ways
        $this->archive_builder->write_object_to_file($o);
        return $occurrence_id;
        */
    }

    private function save_to_dump($data, $filename) // utility
    {
        if(!($WRITE = Functions::file_open($filename, "a"))) return;
        if($data && is_array($data)) fwrite($WRITE, json_encode($data) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }

}
?>