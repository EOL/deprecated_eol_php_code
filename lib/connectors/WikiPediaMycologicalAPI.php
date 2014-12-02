<?php
namespace php_active_record;
/* connector: [mycological] 

http://en.wikipedia.org/wiki/Portal:Fungi
*/
class WikipediaMycologicalAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();
        $this->download_options = array('download_wait_time' => 2000000, 'timeout' => 172800, 'download_attempts' => 1);
        $this->list_of_taxon_ids = array();
        
        $this->mushroom_observer_eol = "http://localhost/~eolit/cp/Wikipedia/mushroom_observer_eol.xml";
        $this->dump_file = DOC_ROOT . "temp/wikipedia_wrong_urls.txt";
        $this->triples_file = DOC_ROOT . "temp/wikipedia_triples.txt";
        $this->wikipedia_fungal_species = "http://en.wikipedia.org/wiki/Category:Lists_of_fungal_species";
        $this->unique_triples = array();
    }

    function get_all_taxa()
    {
        // self::get_triple("http://en.wikipedia.org/wiki/Cystoderma_carcharias", array()); exit;
        
        $wrong_urls = self::get_urls_from_dump($this->dump_file);
        self::process_wikepedia_fungal_list($wrong_urls);
        self::process_mushroom_observer_list($wrong_urls);
        $WRITE = fopen($this->triples_file, "w"); fclose($WRITE); //initialize file
        foreach(array_keys($this->unique_triples) as $triple) self::save_to_dump($triple, $this->triples_file);
        
        echo "\n count of scinames: " . count($this->debug["sciname"]);
        echo "\n count of scinames with triples: " . count($this->debug["sciname with triples"]);
        
        exit;
        $this->archive_builder->finalize(TRUE);
        self::remove_temp_dir();
    }
    
    private function process_wikepedia_fungal_list($wrong_urls)
    {
        $urls = array();
        if($html = Functions::lookup_with_cache($this->wikipedia_fungal_species, $this->download_options))
        {
            //<a href="/wiki/List_of_Agaricus_species" title="List of Agaricus species">
            if(preg_match_all("/<li><a href=\"\/wiki\/List_of_(.*?)\"/ims", $html, $arr))
            {
                print_r($arr[1]); //exit;
                foreach($arr[1] as $path)
                {
                    if(!is_numeric(stripos($path, "_species"))) continue;
                    
                    echo "\n[$path]--";
                    $parts = explode("_", $path);
                    $genus = $parts[0];
                    if($html = Functions::lookup_with_cache("http://en.wikipedia.org/wiki/List_of_" . $path, $this->download_options))
                    {
                        $html = strip_tags($html, "<li><a>");
                        //<a href="/wiki/Amanita_albocreata"
                        if(preg_match_all("/<a href=\"\/wiki\/" . $genus . "(.*?)\"/ims", $html, $arr2))
                        {
                            foreach($arr2[1] as $path) $urls[] = "http://en.wikipedia.org/" . "wiki/$genus" . $path;
                        }
                        // /*
                        //http://en.wikipedia.org/w/index.php?title=Pertusaria_aberrans&action=edit&redlink=1
                        if(preg_match_all("/<a href=\"\/w\/index.php\?title=" . $genus . "(.*?)\"/ims", $html, $arr2))
                        {
                            foreach($arr2[1] as $path) $urls[] = "http://en.wikipedia.org/" . "w/index.php?title=$genus" . $path;
                        }
                        // */
                    }
                    // break; //debug
                }
            }
        }
        
        $urls = array_filter($urls);
        print_r($urls); //exit;
        $i = 0;
        $total = count($urls);
        foreach($urls as $url)
        {
            $i++; echo "\n$i of $total";
            $url = str_ireplace("&amp;", "&", $url);
            self::get_triple($url, $wrong_urls);
        }
    }
    
    private function process_mushroom_observer_list($wrong_urls)
    {
        if($file = Functions::lookup_with_cache($this->mushroom_observer_eol, $this->download_options))
        {
            $xml = simplexml_load_string($file);
            $i = 0;
            $total = count($xml->taxon);
            foreach($xml->taxon as $t)
            {
                $i++;
                // if($i > 40) break;
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
        // $url = "http://en.wikipedia.org/w/index.php?title=Agaricus_pilatianus";
        // $url = "http://en.wikipedia.org/wiki/Agaricus_californicus";
        // $url = "http://en.wikipedia.org/wiki/Boletus_amygdalinus"; //debug
        // $url = "http://en.wikipedia.org/wiki/Phallus_calongei";
        // $url = "http://en.wikipedia.org/wiki/Boletus_lignatilis";

        if(in_array($url, $wrong_urls)) return;
        $rec = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            //sciname
            if(preg_match("/<h1 id=\"firstHeading\"(.*?)<\/h1>/ims", $html, $arr)) $rec["sciname"] = strip_tags("<h1 " . $arr[1]);
            //ancestry
            $ranks = array("kingdom", "division", "phylum", "class", "order", "family", "genus");
            foreach($ranks as $rank)
            {
                if(preg_match("/<span class=\"" . $rank . "\"(.*?)<\/span>/ims", $html, $arr)) $rec["ancestry"][$rank] = strip_tags("<span " . $arr[1]);
            }
            $will_exit = false;
            //triples
            if(preg_match("/title=\"Mycology\">Mycological characteristics(.*?)<\/table>/ims", $html, $arr))
            {
                if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1], $arr))
                {
                    foreach($arr[1] as $row)
                    {
                        // $row = strip_tags($row, "<b><a>");
                        // $row = strip_tags($row, "<b>");
                        $row = strip_tags($row);
                        $row = trim(str_replace(array("\n"), " ", $row));
                        $rec["triples"][] = $row;
                        // if($row == "to olive") $will_exit = true; //debug
                    }
                }
            }
            if($will_exit) print_r($rec); // a good overview of a record: sciname, ancestry, triples
            
            // fix the 'or ' phrase; and saving it to $this->unique_triples
            if(@$rec["triples"])
            {
                $i = 0;
                foreach(@$rec["triples"] as $triple)
                {
                    if(substr($triple, 0, 3) == "or ")
                    {
                        $rec["triples"][$i-1] .= " " . $rec["triples"][$i];
                        $rec["triples"][$i] = "";
                    }
                    $i++;
                }
                $rec["triples"] = array_filter($rec["triples"]);
                if($will_exit)
                {
                    print_r($rec); // a good overview of a record: sciname, ancestry, triples
                    exit("\n[$url]\n");
                }
                
                foreach($rec["triples"] as $triple) $this->unique_triples[$triple] = '';
            }
            
            if($sciname = $rec["sciname"])
            {
                if($sciname) $this->debug["sciname"][$sciname] = '';
                if(@$rec["triples"]) $this->debug["sciname with triples"][$sciname] = '';
            }
            
        }
        else self::save_to_dump($url, $this->dump_file);
        // print_r($rec);
        
        //for counting
        /*
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
        
    }
    
    private function remove_temp_dir()
    {
        // remove temp dir
        $path = $this->text_path["IRMNG_DWC"];
        $parts = pathinfo($path);
        $parts["dirname"] = str_ireplace("/IRMNG_DWC", "", $parts["dirname"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }

    private function get_urls_from_dump($fname)
    {
        $urls = array();
        if($filename = Functions::save_remote_file_to_local($fname, $this->download_options))
        {
            foreach(new FileIterator($filename) as $line_number => $line)
            {
                if($line) $urls[$line] = '';
            }
            unlink($filename);
        }
        return array_keys($urls);
    }
    
    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                  = $rec["TAXONID"];
        if($val = trim($rec["SCIENTIFICNAMEAUTHORSHIP"])) $taxon->scientificName = str_replace($val, "", $rec["SCIENTIFICNAME"]);
        else                                              $taxon->scientificName = $rec["SCIENTIFICNAME"];
        $taxon->family                   = $rec["FAMILY"];
        $taxon->genus                    = $rec["GENUS"];
        $taxon->taxonRank                = $rec["TAXONRANK"];
        $taxon->taxonomicStatus          = $rec["TAXONOMICSTATUS"];
        $taxon->taxonRemarks             = $rec["TAXONREMARKS"];
        $taxon->namePublishedIn          = $rec["NAMEPUBLISHEDIN"];
        $taxon->scientificNameAuthorship = $rec["SCIENTIFICNAMEAUTHORSHIP"];
        $taxon->parentNameUsageID        = $rec["PARENTNAMEUSAGEID"];
        if($rec["TAXONID"] != $rec["ACCEPTEDNAMEUSAGEID"]) $taxon->acceptedNameUsageID = $rec["ACCEPTEDNAMEUSAGEID"];
        $this->archive_builder->write_object_to_file($taxon);
    }

    private function add_string_types($rec, $label, $value, $mtype)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;
        $m->measurementType = $mtype;
        $m->measurementValue = $value;
        $m->measurementOfTaxon = 'true';
        // $m->measurementRemarks = ''; $m->contributor = ''; $m->measurementMethod = '';
        if(isset($rec["rank"]))
        {
            $param = "";
            if    (in_array($rec["rank"], array("kingdom", "phylum", "class", "order"))) $param = $rec["SCIENTIFICNAME"];
            elseif(in_array($rec["rank"], array("family", "genus")))                     $param = $taxon_id;
            elseif($rec["rank"] == "species")                                            $param = urlencode(trim($rec["SCIENTIFICNAME"]));
            if($param) $m->source = $this->source_links[$rec["rank"]] . $param;
        }
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        return $o;
    }

    private function save_to_dump($data, $filename) // utility
    {
        $WRITE = fopen($filename, "a");
        if($data && is_array($data)) fwrite($WRITE, json_encode($data) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }

}
?>