<?php
namespace php_active_record;
// connector: [664]
class HymenopteraAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->taxon_ids = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->text_path = array();
        // $this->zip_path = "https://dl.dropboxusercontent.com/u/7597512/Hymenoptera/Hymenoptera_6_Mar_2003.zip";
        $this->zip_path = "http://localhost/cp/Hymenoptera/Hymenoptera_6_Mar_2003.zip";
        // $this->zip_path = "http://localhost/cp/Hymenoptera/Hymenoptera_small.zip";
        $this->occurrence_ids = array();
        $this->list_of_taxa = array();
    }

    function get_all_taxa()
    {
        $mappings = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uri_values = Functions::additional_mappings($mappings); //add more mappings used in the past
        
        if(self::process_text_files())
        {
            $this->create_archive();
            // remove temp dir
            $parts = pathinfo($this->text_path["1"]);
            recursive_rmdir($parts["dirname"]);
            debug("\n temporary directory removed: " . $parts["dirname"]);
        }
        //start stats for un-mapped countries
        if(isset($this->unmapped_countries)) {
            $OUT = Functions::file_open(DOC_ROOT."/tmp/664_unmapped_countries.txt", "w");
            $countries = array_keys($this->unmapped_countries);
            sort($countries);
            foreach($countries as $c) fwrite($OUT, $c."\n");
            fclose($OUT);
        }
        
    }

    private function process_text_files()
    {
        if(!self::load_zip_contents()) return false;
        print_r($this->text_path);
        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();
        $fields = array("IDNUM", "TAXONID", "TAXID1", "SUBORDER", "TAXID2", "DIVISION", "TAXID5", "SUPERFAM", "TAXID9", "FAMILY", "TAXID13", "SUBFAM", "TAXID17", "TRIBE", "TAXID20", "SUBTRIBE", "GENUS", "SUBGENUS", "SPECIES", "SUBSPECS", "RECTYPCD", "RECTYPE", "UPDATE", "UPDUSER", "LOCGENUS", "LOCSUBGN", "SPCGROUP", "LOCSPECS", "LOCSUBSP", "INFRASUB", "CAUTHOR", "ORIGAUTH", "AUTHOR", "PUBYEAR", "JOURNAL", "COLLATN", "INTROADV", "TYPEDEP", "TYPMATTX", "TYPELOC", "SEXTX", "TSGENUS", "TSSUBGEN", "TSSPECS", "TSSUBSP", "TSAUTHOR", "TSKIND", "INFRATYP", "LOCVAR", "SEQUENCE", "UPSTAT");
        $taxa = $func->make_array($this->text_path[1], $fields, "", array(), "^");

        /* to get the breakdown of taxa levels
        foreach($taxa as $rec)
        {
            if(@$temp[$rec["RECTYPE"]]) $temp[$rec["RECTYPE"]]++;
            else $temp[$rec["RECTYPE"]] = 1;
        }
        print_r($temp);
        // [Super-Generic ]        => 614 xxx
        // [Genus/Subgenus ]       => 2540 xxx
        // [Genus/Subgenus Synon]  => 3459
        // [Species ]              => 19970 xxx
        // [Species Synonym ]      => 11083
        // [Unplaced Taxon ]       => 445 xxx
        // [Nomen Nudum ]          => 177
        // [Species Group ]        => 440 xxx
        */
        
        $i = 0;
        $link = array();
        foreach($taxa as $rec)
        {
            if(in_array($rec["RECTYPE"], array("Species", "Genus/Subgenus", "Species Group", "Super-Generic"))) // "Unplaced Taxon", "Nomen Nudum"
            {
                $i++;
                $sciname = $rec["GENUS"] . " " . $rec["SPECIES"];
                $sciname = trim($sciname);
                if($rec["RECTYPE"] == "Species Group") $sciname = $rec["SPCGROUP"];
                $temp[$sciname] = 1;
                $link = $this->create_instances_from_taxon_object($rec, $link);
            }
        }
        echo "\n\n total rows: " . count($taxa);
        echo "\n total taxa: " . count($temp);
        echo "\n link: " . count($link) . "\n";

        $free_text          = self::process_free_text($func, $link);
        $geo_data           = self::process_geo_data($link); //working but only a handful of trait data for measurementType 'present'
        $ecology            = self::process_ecology($func, $link);
        
        /* working but commented. Un-finished task see: https://eol-jira.bibalex.org/browse/DATA-1275?focusedCommentId=57769&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-57769
        $pollen_visitation  = self::process_pollen_visitation($func, $link);
        $predation          = self::process_predation($func, $link);
        */
        
        return true;

        /* Waiting from SPG on items below this line */

        // some sort of references
        // $fields = array("IDNUM", "REVSNTX", "TAXNTX");
        // $references = $func->make_array($this->text_path[4], $fields, "", array(), "^");

        // references for biology and morphology
        // $fields = array("IDNUM", "BIOLGYTX", "MORPHTX");
        // $texts = $func->make_array($this->text_path[5], $fields, "", array(), "^");
        // $ref_ids = array();
        // $agent_ids = array();
        // foreach($texts as $text)
        // {
        //     $biology = $text["BIOLGYTX"];
        //     $morphology = $text["MORPHTX"];
        //     $taxon_id = @$link[$text["IDNUM"]];
        //     if($biology) $ref_ids = array_merge($ref_ids, get_object_reference_ids($biology));
        //     if($morphology) $ref_ids = array_merge($ref_ids, get_object_reference_ids($morphology));
        // }

        // comma-separated taxon names
        // $fields = array("IDNUM", "AHOSTTX", "PHOSTTX");
        // $taxon_remarks = $func->make_array($this->text_path[7], $fields, "", array(), "^");
        
        // comma-separated taxon names
        // $fields = array("IDNUM", "PARATX", "SECHOSTX");
        // $taxon_remarks2 = $func->make_array($this->text_path[8], $fields, "", array(), "^");

        // wait for SPG to classify  
        // $fields = array("IDNUM", "HYPATX", "SYNTX");
        // $comments = $func->make_array($this->text_path[10], $fields, "", array(), "^");
    }

    private function process_geo_data($link)
    {
        $with_data = false;
        // $fields = array("IDNUM", "GEOTX", "GEOCOD1", "GEOCOT1", "GEOCOD2", "GEOCOT2", "GEOCOD3", "GEOCOT3", "GEOCOD4", "GEOCOT4", "GEOCOD5", "GEOCOT5", "GEOCOD6", "GEOCOT6", "GEOCOD7", "GEOCOT7", "GEOCOD8", "GEOCOT8", "GEOCOD9", "GEOCOT9", "GEOCOD10", "GEOCOT10", "GEOCOD11", "GEOCOT11", "GEOCOD12", "GEOCOT12", "GEOCOD13", "GEOCOT13", "GEOCOD14", "GEOCOT14", "GEOCOD15", "GEOCOT15", "GEOCOD16", "GEOCOT16", "GEOCOD17", "GEOCOT17", "GEOCOD18", "GEOCOT18", "GEOCOD19", "GEOCOT19", "GEOCOD20", "GEOCOT20", "GEOCOD21", "GEOCOT21", "GEOCOD22", "GEOCOT22", "GEOCOD23", "GEOCOT23", "GEOCOD24", "GEOCOT24", "GEOCOD25", "GEOCOT25", "GEOCOD26", "GEOCOT26", "GEOCOD27", "GEOCOT27", "GEOCOD28", "GEOCOT28", "GEOCOD29", "GEOCOT29", "GEOCOD30", "GEOCOT30", "GEOCOD31", "GEOCOT31", "GEOCOD32", "GEOCOT32", "GEOCOD33", "GEOCOT33", "GEOCOD34", "GEOCOT34", "GEOCOD35", "GEOCOT35", "GEOCOD36", "GEOCOT36", "GEOCOD37", "GEOCOT37", "GEOCOD38", "GEOCOT38", "GEOCOD39", "GEOCOT39", "GEOCOD40", "GEOCOT40", "GEOCOD41", "GEOCOT41", "GEOCOD42", "GEOCOT42", "GEOCOD43", "GEOCOT43", "GEOCOD44", "GEOCOT44", "GEOCOD45", "GEOCOT45", "GEOCOD46", "GEOCOT46", "GEOCOD47", "GEOCOT47", "GEOCOD48", "GEOCOT48", "GEOCOD49", "GEOCOT49", "GEOCOD50", "GEOCOT50", "GEOCOD51", "GEOCOT51", "GEOCOD52", "GEOCOT52", "GEOCOD53", "GEOCOT53", "GEOCOD54", "GEOCOT54", "GEOCOD55", "GEOCOT55", "GEOCOD56", "GEOCOT56", "GEOCOD57", "GEOCOT57", "GEOCOD58", "GEOCOT58", "GEOCOD59", "GEOCOT59", "GEOCOD60", "GEOCOT60", "GEOCOD61", "GEOCOT61", "GEOCOD62", "GEOCOT62", "GEOCOD63", "GEOCOT63", "GEOCOD64", "GEOCOT64", "GEOCOD65", "GEOCOT65", "GEOCOD66", "GEOCOT66", "GEOCOD67", "GEOCOT67", "GEOCOD68", "GEOCOT68", "GEOCOD69", "GEOCOT69", "GEOCOD70", "GEOCOT70", "GEOCOD71", "GEOCOT71", "GEOCOD72", "GEOCOT72", "GEOCOD73", "GEOCOT73", "GEOCOD74", "GEOCOT74", "GEOCOD74", "GEOCOT75", "COCODE1", "COCODT1", "COCODE2", "COCODT2", "COCODE3", "COCODT3", "COCODE4", "COCODT4", "COCODE5", "COCODT5", "COCODE6", "COCODT6", "COCODE7", "COCODT7", "COCODE8", "COCODT8", "COCODE9", "COCODT9", "COCODE10", "COCODT10", "COCODE11", "COCODT11", "COCODE12", "COCODT12", "COCODE13", "COCODT13", "COCODE14", "COCODT14", "COCODE15", "COCODT15", "COCODE16", "COCODT16", "COCODE17", "COCODT17", "COCODE18", "COCODT18", "COCODE19", "COCODT19", "COCODE20", "COCODT20", "COCODE21", "COCODT21", "COCODE22", "COCODT22", "COCODE23", "COCODT23", "COCODE24", "COCODT24", "COCODE25", "COCODT25", "COCODE26", "COCODT26", "COCODE27", "COCODT27", "COCODE28", "COCODT28", "COCODE29", "COCODT29", "COCODE30", "COCODT30", "COCODE31", "COCODT31", "COCODE32", "COCODT32", "COCODE33", "COCODT33", "COCODE34", "COCODT34", "COCODE35", "COCODT35", "COCODE36", "COCODT36", "COCODE37", "COCODT37", "COCODE38", "COCODT38", "COCODE39", "COCODT39", "COCODE40", "COCODT40", "COCODE41", "COCODT41", "COCODE42", "COCODT42", "COCODE43", "COCODT43", "COCODE44", "COCODT44", "COCODE45", "COCODT45", "COCODE46", "COCODT46", "COCODE47", "COCODT47", "COCODE48", "COCODT48", "COCODE49", "COCODT49", "COCODE50", "COCODT50", "COCODE51", "COCODT51", "COCODE52", "COCODT52", "COCODE53", "COCODT53", "COCODE54", "COCODT54", "COCODE55", "COCODT55", "COCODE56", "COCODT56", "COCODE57", "COCODT57", "COCODE58", "COCODT58", "COCODE59", "COCODT59", "COCODE60", "COCODT60", "COCODE61", "COCODT61", "COCODE62", "COCODT62", "COCODE63", "COCODT63", "COCODE64", "COCODT64", "COCODE65", "COCODT65", "COCODE66", "COCODT66", "COCODE67", "COCODT67", "COCODE68", "COCODT68", "COCODE69", "COCODT69", "COCODE70", "COCODT70", "COCODE71", "COCODT71", "COCODE72", "COCODT72", "COCODE73", "COCODT73", "COCODE74", "COCODT74", "COCODE75", "COCODT75", "ECOTX");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        /*
        0-1
        1-75 150  -- 2-151
        1-75
        1-75 150 -- 152-301
        1-75
        1 -- 302
        */
        $k = 0;
        foreach(new FileIterator($this->text_path[3]) as $line_number => $line)
        {
            if($line)
            {
                $desc = "";
                $line = trim($line);
                $values = explode("^", $line);
                $values = array_map('trim', $values);
                if($values[0] == "IDNUM") continue;
                $idnum = $values[0];
                $desc = $values[1];
                for ($i = 2; $i <= 151; $i=$i+2)
                {
                    if($values[$i] && stripos($values[$i+1], "*") === false) $desc .= "<br>" . $values[$i] . ": ". $values[$i+1];
                }
                for ($i = 152; $i <= 301; $i=$i+2) 
                {
                    if($values[$i] && stripos($values[$i+1], "*") === false) $desc .= "<br>" . $values[$i] . ": ". $values[$i+1];
                }
                
                /* already gotten from another text file
                if($values[302])
                {
                    $values[302] = str_ireplace("&", "", $values[302]);
                    if($taxon_id = @$link[$idnum]) self::get_texts($values[302], $taxon_id, '', '#Ecology', $idnum."_eco", $ref_ids, $agent_ids);
                    else
                    {
                        $investigate++;
                        echo("\n investigate: ecology: {$taxon_id}[$values[302]] -- IDNUM = " . $idnum . "\n");
                    }
                }
                */
                
                if($desc = trim($desc))
                {
                    /* commented. Decided to just use text media object as distribution and no longer trait data 'present'
                    if($taxon_id = @$link[$idnum])
                    {
                        if($country_uri = self::get_country_uri($desc)) {
                            self::add_string_types($taxon_id, "Distribution", $country_uri, $desc); //previously value is $desc, now it is USA - http://www.geonames.org/6252001
                            $with_data = true;
                            // $k++; if($k >= 50) break; // debug to limit during preview phase
                        } //mapped OK
                        else {
                            $this->unmapped_countries[$desc] = ''; //for stats only
                            continue; //will wait for Jen's mapping so we get all country strings its respective country URI
                        }
                    }
                    else
                    {
                        $investigate++;
                        echo("\n investigate: distribution: {$taxon_id}[$desc] -- IDNUM = " . $idnum . "\n");
                    }
                    */
                }
                
                
                if($desc = trim($desc))
                {
                    if($taxon_id = @$link[$idnum]) self::get_texts($desc, $taxon_id, '', '#Distribution', $idnum."_dist", $ref_ids, $agent_ids);
                    else
                    {
                        $investigate++;
                        echo("\n investigate: distribution: {$taxon_id}[$desc] -- IDNUM = " . $idnum . "\n");
                    }
                }
                
            }
        }
        echo "\n investigate: $investigate \n";
        return $with_data;
    }

    private function process_pollen_visitation($func, $link)
    {
        $with_data = false;
        $fields = array("IDNUM", "POLLENTX", "VISITSTX");
        $texts = $func->make_array($this->text_path[6], $fields, "", array(), "^");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        $i = 0; $i2 = 0;
        foreach($texts as $text)
        {
            $pollen = str_replace("&", "", $text["POLLENTX"]);
            $visits = str_replace("&", "", $text["VISITSTX"]);
            $taxon_id = @$link[$text["IDNUM"]];
            if($pollen)
            {
                if($taxon_id = @$link[$text["IDNUM"]])
                {
                    $pollen = self::get_taxa_from_description($pollen, "plants");
                    self::add_association($pollen, $taxon_id, "Pollen");
                    $with_data = true;
                }
                else
                {
                    if($text["IDNUM"] != "IDNUM")
                    {
                        $investigate++;
                        echo("\n investigate: pollen: [$taxon_id][$pollen] -- IDNUM = " . $text["IDNUM"] . "\n");
                    }
                }
            }
            if($visits)
            {
                if($taxon_id = @$link[$text["IDNUM"]])
                {
                    $visits = self::get_taxa_from_description($visits, "plants");
                    self::add_association($visits, $taxon_id, "Visits");
                    $with_data = true;
                }
                else
                {
                    if($text["IDNUM"] != "IDNUM")
                    {
                        $investigate++;
                        echo("\n investigate: visits: [$taxon_id][$visits] -- IDNUM = " . $text["IDNUM"] . "\n");
                    } 
                }
            }
        }
        echo "\n investigate: $investigate \n";
        return $with_data;
    }

    private function process_ecology($func, $link)
    {
        $with_data = false;
        $fields = array("IDNUM", "ECOTX");
        $texts = $func->make_array($this->text_path[11], $fields, "", array(), "^");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        $i = 0;
        foreach($texts as $text)
        {
            $description = $text["ECOTX"];
            $description = str_ireplace("&", "", $description);
            if($description)
            {
                if($taxon_id = @$link[$text["IDNUM"]])
                {
                    self::get_texts($description, $taxon_id, '', '#Ecology', $text["IDNUM"]."_eco", $ref_ids, $agent_ids);
                    $with_data = true;
                    // echo "\n ecology: [$description]";
                    // $i++; if($i >= 150) break; //debug to limit during preview phase
                }
                else
                {
                    if($text["IDNUM"] != "IDNUM") 
                    {
                        $investigate++;
                        echo("\n investigate: ecology: {$taxon_id}[$description] -- IDNUM = " . $text["IDNUM"] . "\n");
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
        return $with_data;
    }

    private function process_free_text($func, $link)
    {
        $with_data = false;
        $fields = array("IDNUM", "FREETX");
        $texts = $func->make_array($this->text_path[2], $fields, "", array(), "^");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        $i = 0;
        foreach($texts as $text)
        {
            if($description = $text["FREETX"])
            {
                if($taxon_id = @$link[$text["IDNUM"]])
                {
                    self::get_texts($description, $taxon_id, '', '#TaxonBiology', $text["IDNUM"]."_brief", $ref_ids, $agent_ids);
                    $with_data = true;
                    // echo "\n taxonbiology: [$description]";
                    // $i++; if($i >= 50) break; //debug to limit during preview phase
                }
                else
                {
                    if($text["IDNUM"] != "IDNUM") 
                    {
                        $investigate++;
                        echo("\n investigate: free text: {$taxon_id}[$description] -- IDNUM = " . $text["IDNUM"] . "\n");
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
        return $with_data;
    }

    private function process_predation($func, $link)
    {
        $with_data = false;
        $fields = array("IDNUM", "PREYTX", "PREDTX");
        $texts = $func->make_array($this->text_path[9], $fields, "", array(), "^");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        $i = 0; $i2 = 0;
        foreach($texts as $text)
        {
            $prey = str_replace("&", "", $text["PREYTX"]);
            $predator = str_replace("&", "", $text["PREDTX"]);
            $taxon_id = @$link[$text["IDNUM"]];
            if($prey)
            {
                if($taxon_id = @$link[$text["IDNUM"]])
                {
                    $prey = self::get_taxa_from_description($prey, "animals");
                    self::add_association($prey, $taxon_id, "Prey");
                    $with_data = true;
                }
                else
                {
                    if($text["IDNUM"] != "IDNUM") 
                    {
                        $investigate++;
                        echo("\n investigate: prey: [$taxon_id][$prey] -- IDNUM = " . $text["IDNUM"] . "\n");
                    }
                }
            }
            if($predator)
            {
                if($taxon_id = @$link[$text["IDNUM"]])
                {
                    $predator = self::get_taxa_from_description($predator, "animals");
                    self::add_association($predator, $taxon_id, "Predator");
                    $with_data = true;
                }
                else
                {
                    if($text["IDNUM"] != "IDNUM") 
                    {
                        $investigate++;
                        echo("\n investigate: predator: [$taxon_id][$predator] -- IDNUM = " . $text["IDNUM"] . "\n");
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
        return $with_data;
    }

    private function add_association($names, $taxon_id, $type)
    {
        foreach($names as $taxon_name)
        {
            $taxon_name_id = str_ireplace(" ", "_", Functions::canonical_form($taxon_name));
            $occurrence_id = $this->add_occurrence($taxon_id, $taxon_name_id . "_$type");
            $related_taxon = $this->add_taxon($taxon_name);
            $related_occurrence_id = $this->add_occurrence($related_taxon->taxonID, $taxon_id . "_$type");
            $a = new \eol_schema\Association();
            $a->occurrenceID = $occurrence_id;
            if($type == "Predator") $a->associationType = "http://eol.org/schema/terms/HasPredator";
            if($type == "Prey")     $a->associationType = "http://eol.org/schema/terms/preysUpon";
            if($type == "Visits")   $a->associationType = "http://eol.org/schema/terms/FlowersVisitedBy";
            if($type == "Pollen")   $a->associationType = "http://eol.org/schema/terms/FlowersVisitedBy";
            $a->targetOccurrenceID = $related_occurrence_id;
            $this->archive_builder->write_object_to_file($a);
        }
    }

    private function add_taxon($taxon_name)
    {
        $taxon_id = str_ireplace(" ", "_", Functions::canonical_form($taxon_name));
        if(isset($this->taxon_ids[$taxon_id])) return $this->taxon_ids[$taxon_id];
        $t = new \eol_schema\Taxon();
        $t->taxonID = $taxon_id;
        $t->scientificName = $taxon_name;
        // $this->archive_builder->write_object_to_file($t);
        $this->taxon_ids[$taxon_id] = $t;
        return $t;
    }

    private function get_taxa_from_description($str, $type)
    {
        if($type == "animals")
        {
            // exclude name with these substrings
            $excluded = array("America", "adult", "European", "record", "prey ", "female", " are ", "English", "kinds ", "possibly", "complex", "cherry ", " spiders", "small ");
            // remove these substrings in names
            $to_be_removed = array("Dead arthropods belonging to", "Leaf-mining larvae of ", "Larvae of leaf-mining ", "are the preferred prey", 
            " from Galactica.", "Larvae of ", "young nymphs", "(?)", "Leaf-mining ", "?", "REY.", "Badgers", "Dead ", "in europe", " larva", " larvae", " nymphs", " nymph", "most commonly", "in N. Amer.", "workers." , "  in", "laboratory.", "group", "Primarily", "juveniles.", "juvenile", " probably ");
        }
        elseif($type == "plants")
        {
            // exclude name with these substrings
            $excluded = array("Unknown", "may apply to nesting site", "may apply to a nesting site");
            // remove these substrings in names
            $to_be_removed = array("Collects pollen and nectar primarily from summer and fall flowering", "and some composites were found in small amounts but possibly came from nectar plants", "Examined nest provisions consisted exclusively of nectar and pollen from flowers of", "Probably collects pollen mainly from the flowers of", 
            "Evidently collects pollen from the flowers of", "Apparently collects pollen only from the flowers of", "Evidently depends mostly upon the pollen and nectar of", "on which it may be an oligolege owing to its slender form and long tongue.",
            "Apparently collects pollen only from vernal and autumnal flowering", "Apparently an oligolege of summer and fall flowering", 
            "Oligolege of summer and fall flowering", "Presumably an oligolege of the", "Evidently an oligolege of", "Apparently an oligolege of", "Polylege with some preference for flowers of", "Apparently collects pollen from the flowers of", "Apparently strictly oligolectic on", "Apparently may collect pollen from the flowers of",
            "Collects pollen principally from", "Appears to collect pollen principally from the", "Analyzed pollen stores indicates reliance on",
            "Apparently mainly dependent upon the pollens of", "Apparently prefers pollen from", "Collects pollen from flowers of", "has been listed as collecting pollen from flowers of",
            "but visits flowers of", "Recorded from flowers of", "Visits flowers of", "Possibly autumnal flowering", 
            "Presumably autumnal flowering", "Presumably an vernal and autumnal flowering", "Collects pollen from the flowers of", "Possibly an autumnal flowering",
            "Collects pollen of", "Oligolectic on flowers of", "Polylege with apparent preference for flowers of",
            "Collects pollen regularly only from", "Collects pollen only from the flowers of", "Primarily associated with flowers of", 
            "Collects pollen and nectar chiefly from", "Collects pollen from early morning opening ligulate", "Collects pollen almost exclusively from ligulate",
            "Collects pollen from ligulate", "Based upon the mouth parts of the female", "Collects pollen primarily from microseridine",
            "Principal source of pollen is", "Principally gathers pollen from", "Principal pollen source is", "Collects pollen from stephanomerine", "Apparently collects pollen primarily from", "Collects pollen primarily from ligulate",
            "Most polylectic of all the species of the subgenus", "Presumably an late summer and fall flowering", "Probably oligolectic on a wide range of",
            "Apparently a polylege with preferences for flowers of", "Polylege with some preference for the flowers of", "mesophytic", "xerophytic", "Oligolectic on uncultivated", "Oligolectic on ligulate", "Possibly oligolectic on",
            "Polylectic with some preference for the pollens of the", "Presumably gathers pollen from", "Presumably an fall flowering", "which it apparently prefers", "but visits other flowers for nectar", "Apparently oligolectic on",
            "these and other flowers", "in approximately that order", " in that order", " in the early morning",
            "Oligolege of ", "for nectar.", "for pollen and nectar", "as the primary source of pollen", "in the fall", " groups", "also present",
            "presumably for nectar", "Collects pollen from", "Collects pollen");
        }
        $to_be_removed[] = "sp.";
        $to_be_removed[] = "spp.";
        
        $scinames = array();
        $separators = array(",", ";");
        if($type == "plants")
        {
            $separators[] = "including";
            $separators[] = "although";
            $separators[] = "especially";
            $separators[] = "and a secondary preference for";
            $separators[] = "with some preference for flowers of the genus";
            $separators[] = "as well as";
            $separators[] = "and possibly";
            $separators[] = "pollens and one cell was provisioned entirely with pollen from";
            $separators[] = "and secondarily";
            $separators[] = "and various legumes";
            $separators[] = "and most";
            $separators[] = "and is probably an";
            $separators[] = "and small amounts of";
            $separators[] = "Stores pollen of ";
            $separators[] = "and to a lesser extent those of the";
            $separators[] = "and also from";
        }
        elseif($type == "animals") $separators[] = "Other predators include";

        $names = self::get_words_from_string($str, $separators);
        foreach($names as $name)
        {
            if($type == "animals")
            {
                if(is_numeric(stripos($name, "Ceresini  probably Stictocephala")))
                {
                    $scinames["Ceresini"] = '';
                    $scinames["Stictocephala"] = '';
                    continue;
                }
                if(is_numeric(stripos($name, "Tachiinae probably Sibinia")))
                {
                    $scinames["Tachiinae"] = '';
                    $scinames["Sibinia"] = '';
                    continue;
                }
            }
            
            $name = trim(str_ireplace($to_be_removed, "", $name));
            foreach($excluded as $string)
            {
                if(is_numeric(stripos($name, $string))) $name = false;
            }
            if(ctype_lower(substr($name,0,1))) continue; // ignore if it starts with small char
            if(strlen($name) <= 3) continue; 
            if(substr($name, -2) == ").") $name = substr($name, 0, strlen($name)-1); // if last two chars is ")." - remove "."
            if($type == "animals")
            {
                if(is_numeric(strpos($name, "C. ")))
                {
                    $name = str_replace("C. rosaceana Harr.", "Choristoneura rosaceana Harr.", $name);
                    $name = str_replace("C. fumiferana (Clem.)", "Choristoneura fumiferana (Clem.)", $name);
                    $name = str_replace("C. pinus Free.", "Choristoneura pinus Free.", $name);
                }
            }
            if($type == "plants")
            {
                if    (is_numeric(stripos($name, "Oligolectic on Cucurbita foetidissima"))) $name = "Cucurbita foetidissima";
                elseif(is_numeric(stripos($name, "Hemizonia paniculata. Males and females"))) $name = "Hemizonia paniculata";
                elseif(is_numeric(stripos($name, "collected pollen mainly from Vaccinium stramineum"))) $name = "Vaccinium stramineum";
                elseif(is_numeric(stripos($name, "96 per cent Faboideae"))) $name = "Faboideae";
                elseif(is_numeric(stripos($name, "per cent from Compositae"))) $name = "Compositae";
                elseif(is_numeric(stripos($name, "Principally Camissonia cheiranthifolia cheiranthifolia"))) $name = "Camissonia cheiranthifolia cheiranthifolia";
                elseif(is_numeric(stripos($name, "Malvaceous genus Callirhoe"))) $name = "Callirhoe";
                elseif(is_numeric(stripos($name, "Malacothrix) and desert shrubs"))) $name = "Malacothrix";
                elseif(is_numeric(stripos($name, "Phacelia (collecting pollen in one instance) Raphanus sativus")))
                {
                    $scinames["Phacelia"] = '';
                    $scinames["Raphanus sativus"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "primarily from summer and fall flowering Astereae (Compositae)")))
                {
                    $scinames["Astereae"] = '';
                    $scinames["Compositae"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Solidago with some preference for flowers of Solidago and Aster")))
                {
                    $scinames["Solidago"] = '';
                    $scinames["Aster"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Mimosa and also has been collected at the flowers of Melilotus.")))
                {
                    $scinames["Mimosa"] = '';
                    $scinames["Melilotus"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Gutierrezia microcephala. Haplopappus heterophyllus.")))
                {
                    $scinames["Haplopappus heterophyllus"] = '';
                    $scinames["Gutierrezia microcephala"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Cucurbita digitata and Cucurbita palmata of the Digitata group")))
                {
                    $scinames["Cucurbita digitata"] = '';
                    $scinames["Cucurbita palmata"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Thysanella. Males have been taken while they were visiting honeydew of a Phylloxera infesting Quercus falcata")))
                {
                    $scinames["Thysanella"] = '';
                    $scinames["Quercus falcata"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Solidago. It has also been collected at honey dew of Phylloxera on Quercus alba")))
                {
                    $scinames["Quercus alba"] = '';
                    $scinames["Solidago"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Kallstroemia grandiflora. Kallstroemia grandiflora.")))
                {
                    $scinames["Kallstroemia grandiflora"] = '';
                    $scinames["Kallstroemia grandiflora"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "from flowers of Leguminosae and Verbenaceae have been observed")))
                {
                    $scinames["Leguminosae"] = '';
                    $scinames["Verbenaceae"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "observed it collecting pollen from Lepidium")))
                {
                    $scinames["Lepidium"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Solidago canadensis. In Texas Hurd has taken it at flowers of Chamaesaracha coronopus")))
                {
                    $scinames["Solidago canadensis"] = '';
                    $scinames["Chamaesaracha coronopus"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Sida hederacea and may also collect pollen from flowers of Sphaeralcea")))
                {
                    $scinames["Sida hederacea"] = '';
                    $scinames["Sphaeralcea"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Camissonia c. aurantiaca Camissonia c. clavaeformis and also occasionally from Camissonia decorticans desertorum")))
                {
                    $scinames["Camissonia c. aurantiaca"] = '';
                    $scinames["Camissonia c. clavaeformis"] = '';
                    $scinames["Camissonia decorticans desertorum"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Solidago canadensis Tamarix aralensis")))
                {
                    $scinames["Solidago canadensis"] = '';
                    $scinames["Tamarix aralensis"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Solidago canadensis Tamarix aralensis")))
                {
                    $scinames["Solidago canadensis"] = '';
                    $scinames["Tamarix aralensis"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Microseris nutans and locally obtains pollen from some of the crepidine Compositae such as Crepis occidentalis")))
                {
                    $scinames["Microseris nutans"] = '';
                    $scinames["Crepis occidentalis"] = '';
                    $scinames["Compositae"] = '';
                    continue;
                }
                elseif(preg_match("/\(originally(.*?)\)/ims", $name, $tempx)) $name = trim(preg_replace('/\s*\([^)]*\)/', '', $name)); //remove parenthesis
                elseif($tempx = explode("flowers of", $name))
                {
                    if(count($tempx) == 1) $name = trim($tempx[0]);
                    elseif(count($tempx) == 2) $name = trim($tempx[1]);
                }
            }
            elseif($type == "animals")
            {
                if(is_numeric(stripos($name, "Ladder-backed woodpecker (Dendrocopus scalaris)"))) $name = "Dendrocopus scalaris";
                elseif(is_numeric(stripos($name, "<. Pterophoridae"))) $name = "Pterophoridae";
                elseif(is_numeric(strpos($name, "  near "))) $name = str_replace("  near ", "", $name);
                elseif(is_numeric(strpos($name, " near "))) $name = str_replace(" near ", "", $name);
                elseif(is_numeric(stripos($name, "Empoasca fabae (Harr.) Exitianus exitiosus Uhl.")))
                {
                    $scinames["Empoasca fabae (Harr.)"] = '';
                    $scinames["Exitianus exitiosus Uhl."] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Dolichopodidae and Empididae especially Platypalpus")))
                {
                    $scinames["Dolichopodidae"] = '';
                    $scinames["Empididae"] = '';
                    $scinames["Platypalpus"] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Sarcophaga lherminieri R.- Desv. Sarcophaga opifera Coq.")))
                {
                    $scinames["Sarcophaga lherminieri R.- Desv."] = '';
                    $scinames["Sarcophaga opifera Coq."] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Villa chimaera (O. S.). Villa salebrosa Paint.")))
                {
                    $scinames["Villa chimaera (O. S.)"] = '';
                    $scinames["Villa salebrosa Paint."] = '';
                    continue;
                }
                elseif(is_numeric(stripos($name, "Eustala anastera (Walck.) Larinia directa (Hentz)")))
                {
                    $scinames["Eustala anastera (Walck.)"] = '';
                    $scinames["Larinia directa (Hentz)"] = '';
                    continue;
                }
            }
            if(substr($name, 0, 1) == ".") $name = trim(substr($name, 1, strlen($name))); // remove first char if is period "."
            if(is_numeric(substr($name, 0, 1))) continue; // exclude if first char is numeric
            $name = trim(str_replace(" .", "", $name));
            if($type == "plants")
            {
                if($name == "Apparently a polylege with some preference for leguminous flowers" ||
                   $name == "Apparently a polylege with no strong preferences" ||
                   $name == "Robertson (1929. Flowers and insects") $name = "";
            }
            $name = trim(str_ireplace($to_be_removed, "", $name));
            if(!is_numeric(stripos($name, " "))) $name = Functions::canonical_form($name); // if name is just 1 word, get canonical
            if($type == "plants")
            {
                if($tempx = explode(" and ", $name))
                {
                    foreach($tempx as $name)
                    {
                        if($name) $scinames[$name] = '';
                    }
                }
            }
            if($name) $scinames[$name] = '';
        }
        $scinames = array_keys($scinames);
        $this->list_of_taxa = array_merge($this->list_of_taxa, $scinames);
        $this->list_of_taxa = array_unique($this->list_of_taxa);
        return $scinames;
    }

    private function get_words_from_string($string, $separators)
    {
        $string = str_replace($separators, ";", $string);
        return explode(";", $string);
    }

    private function add_string_types($taxon_id, $label, $value, $mremarks = null)
    {
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $label);
        $m->occurrenceID = $occurrence_id;
        if(in_array($label, array("Prey", "Predator", "Pollen", "Visits", "Distribution"))) $m->measurementOfTaxon = 'true';
        if($label == "Occurrence ID")    $m->measurementType = "http://rs.tdwg.org/dwc/terms/occurrenceID";
        elseif($label == "Distribution") $m->measurementType = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
        $m->measurementValue = $value;
        $m->measurementRemarks = $mremarks;
        $m->contributor = 'Catalog of Hymenoptera in America North of Mexico';
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID]))
        {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }

    private function add_occurrence($taxon_id, $label)
    {
        $occurrence_id = md5($taxon_id . "_" . str_replace(" ", "_", $label));
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;

        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        else
        {
            $this->archive_builder->write_object_to_file($o);
            $this->occurrence_ids[$occurrence_id] = '';
            return $occurrence_id;
        }

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

    function create_instances_from_taxon_object($rec, $link)
    {
        /*
        [IDNUM] => 000137
        [TAXONID] => 30-10-020-060-060-00
        [TAXID1] => 3
        [SUBORDER] => SYMPHYTA 
        [TAXID2] => 30
        [DIVISION] =>  
        [TAXID5] => 30-10
        [SUPERFAM] => MEGALODONTOIDEA 
        [TAXID9] => 30-10-020
        [FAMILY] => PAMPHILIIDAE 
        [TAXID13] => 30-10-020-060
        [SUBFAM] => PAMPHILIINAE 
        [TAXID17] => 30-10-020-060-060
        [TRIBE] => PAMPHILIINI 
        [TAXID20] => 30-10-020-060-060-00
        [SUBTRIBE] => SUBTRIBE30-10-020-060-060
        [GENUS] => Lyda 
        [SUBGENUS] =>  
        [SPECIES] =>  
        [SUBSPECS] =>  
        [RECTYPCD] => 25
        [RECTYPE] => Genus/Subgenus Synon
        [UPDATE] =>  
        [UPDUSER] =>  
        [LOCGENUS] => PAMPHILIUS 
        [LOCSUBGN] =>  
        [SPCGROUP] =>  
        [LOCSPECS] =>  
        [LOCSUBSP] =>  
        [INFRASUB] =>  
        [CAUTHOR] =>  
        [ORIGAUTH] =>  
        [AUTHOR] => Fabricius 
        [PUBYEAR] => 1804 
        [JOURNAL] => Systema Piezatorum, 
        [COLLATN] => p. 43 
        */
        $rec = array_map('trim', $rec);
        $sciname = "";
        $genus = "";
        $rank = "";
        if(in_array($rec["RECTYPE"], array("Super-Generic", "Nomen Nudum", "Unplaced Taxon", "Species")))
        {
            if($rec["GENUS"] && $rec["SPECIES"])
            {
                $sciname = trim($rec["GENUS"] . " " . $rec["SPECIES"] . " " . $rec["SUBSPECS"]);
                $rank = "species";
                $genus = $rec["GENUS"];
            }
            elseif($rec["SUBTRIBE"] && stripos($rec["SUBTRIBE"], "0") === false)
            {
                $sciname = $rec["SUBTRIBE"];
                $rank = "subtribe";
            }
            elseif($rec["TRIBE"] && stripos($rec["TRIBE"], "0") === false)
            {
                $sciname = $rec["TRIBE"];
                $rank = "tribe";
            }
            elseif($rec["SUBFAM"] && stripos($rec["SUBFAM"], "0") === false)
            {
                $sciname = $rec["SUBFAM"];
                $rank = "subfamily";
            }
            elseif($rec["FAMILY"] && stripos($rec["FAMILY"], "0") === false)
            {
                $sciname = $rec["FAMILY"];
                $rank = "family";
            }
            elseif($rec["SUPERFAM"] && stripos($rec["SUPERFAM"], "0") === false)
            {
                $sciname = $rec["SUPERFAM"];
                $rank = "superfamily";
            }
            elseif($rec["DIVISION"] && stripos($rec["DIVISION"], "0") === false)
            {
                $sciname = $rec["DIVISION"];
                $rank = "division";
            }
            elseif($rec["SUBORDER"] && stripos($rec["SUBORDER"], "0") === false)
            {
                $sciname = $rec["SUBORDER"];
                $rank = "suborder";
            }
        }
        elseif($rec["RECTYPE"] == "Genus/Subgenus")
        {
            if($rec["SUBGENUS"])
            {
                $sciname = $rec["SUBGENUS"];
                $rank = "subgenus";
            }
            elseif($rec["GENUS"])
            {
                $sciname = $rec["GENUS"];
                $rank = "genus";
            }
            elseif($rec["LOCGENUS"])
            {
                $sciname = $rec["LOCGENUS"];
                $rank = "genus";
            }
            elseif($rec["LOCSUBGN"])
            {
                $sciname = $rec["LOCSUBGN"];
                $rank = "subgenus";
            }
        }
        elseif($rec["RECTYPE"] == "Species Group") $sciname = $rec["SPCGROUP"];

        // manual adjustment
        if($rec["IDNUM"] == "000001")
        {
            $sciname = "Hymenoptera";
            $rank = "order";
        }

        $sciname = utf8_encode(trim($sciname));
        $sciname = ucfirst(strtolower($sciname));
        $sciname = str_replace(array("(|)","(?)", "?"), "", $sciname);
        $sciname = trim(str_replace("  ", " ", $sciname));
        $taxon_id = str_ireplace(" ", "_", trim($sciname));
        $genus = str_replace(array("(|)","(?)", "?"), "", $genus);
        $genus = ucfirst(strtolower($genus));
        $rec["FAMILY"] = str_replace("(|)", "", $rec["FAMILY"]);
        $rec["FAMILY"] = ucfirst(strtolower($rec["FAMILY"]));
        if($sciname == $rec["FAMILY"]) $rec["FAMILY"] = "";
        if(!$sciname || is_numeric(stripos($sciname, "Species Group")) || is_numeric(stripos($sciname, "Unassigned")))
        {
            echo "\n will stop - erroneous sciname [$sciname][$taxon_id][" . $rec["IDNUM"] . "]\n";
            return $link;
        }
        $authorship     = trim($rec["AUTHOR"] . " " . trim($rec["PUBYEAR"]));
        $idnum          = $rec["IDNUM"];
        $link[$idnum]   = $taxon_id;
        if(isset($this->taxon_ids[$taxon_id])) return $link;
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $taxon_id; // take note, not TAXONID
        $taxon->taxonRank                   = $rank;
        $taxon->scientificName              = $sciname;
        $taxon->scientificNameAuthorship    = $authorship;
        $taxon->genus                       = $genus;
        if($rec["FAMILY"] && stripos($rec["FAMILY"], "0") === false) $taxon->family = $rec["FAMILY"];
        $this->taxon_ids[$taxon->taxonID] = $taxon;
        return $link;
    }

    private function create_archive()
    {
        foreach($this->taxon_ids as $key => $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }

    private function get_object_reference_ids($ref)
    {
        $reference_ids = array();
        $r = new \eol_schema\Reference();
        $r->full_reference = $ref;
        $r->identifier = md5($r->full_reference);
        $reference_ids[] = $r->identifier;
        if(!in_array($r->identifier, $this->resource_reference_ids))
        {
           $this->resource_reference_ids[] = $r->identifier;
           $this->archive_builder->write_object_to_file($r);
        }
        return $reference_ids;
    }

    private function get_texts($description, $taxon_id, $title, $subject, $code, $reference_ids = null, $agent_ids = null)
    {
        $description = str_ireplace("&", "", $description);
        $mr = new \eol_schema\MediaResource();
        if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID        = $taxon_id;
        $mr->identifier     = $mr->taxonID . "_" . $code;
        $mr->type           = 'http://purl.org/dc/dcmitype/Text';
        $mr->language       = 'en';
        $mr->format         = 'text/html';
        $mr->description    = utf8_encode($description);
        $mr->CVterm         = $this->SPM . $subject;
        $mr->title          = $title;
        $mr->UsageTerms     = 'http://creativecommons.org/licenses/by-nc/3.0/';
        $this->archive_builder->write_object_to_file($mr);
    }

    function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::lookup_with_cache($this->zip_path, array('timeout' => 172800, 'download_attempts' => 5)))
        {
            $parts = pathinfo($this->zip_path);
            $temp_file_path = $this->TEMP_FILE_PATH . "/" . $parts["basename"];
            if(!($TMP = fopen($temp_file_path, "w")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $temp_file_path);
              return;
            }
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("unzip -o $temp_file_path -d $this->TEMP_FILE_PATH");
            if(!file_exists($this->TEMP_FILE_PATH . "/Hds1-Hymenoptera-Final.txt")) 
            {
                $this->TEMP_FILE_PATH = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($this->TEMP_FILE_PATH . "/Hds1-Hymenoptera-Final.txt")) return false;
            }
            $this->text_path[1] = $this->TEMP_FILE_PATH . "/Hds1-Hymenoptera-Final.txt";
            $this->text_path[2] = $this->TEMP_FILE_PATH . "/Hds2-Hymenoptera-Final.txt";
            $this->text_path[3] = $this->TEMP_FILE_PATH . "/Hds3-Hymenoptera-Final.txt";
            $this->text_path[4] = $this->TEMP_FILE_PATH . "/Hds4-Hymenoptera-Final.txt";
            $this->text_path[5] = $this->TEMP_FILE_PATH . "/Hds5-Hymenoptera-Final.txt";
            $this->text_path[6] = $this->TEMP_FILE_PATH . "/Hds6-Hymenoptera-Final.txt";
            $this->text_path[7] = $this->TEMP_FILE_PATH . "/Hds7-Hymenoptera-Final.txt";
            $this->text_path[8] = $this->TEMP_FILE_PATH . "/Hds8-Hymenoptera-Final.txt";
            $this->text_path[9] = $this->TEMP_FILE_PATH . "/Hds9-Hymenoptera-Final.txt";
            $this->text_path[10] = $this->TEMP_FILE_PATH . "/Hds10-Hymenoptera-Final.txt";
            $this->text_path[11] = $this->TEMP_FILE_PATH . "/HymEcoParDone.txt";
            return true;
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return false;
        }
    }

    /* for text objects
    private function process_predation_v2($func, $link) 
    {
        $fields = array("IDNUM", "PREYTX", "PREDTX");
        $texts = $func->make_array($this->text_path[9], $fields, "", array(), "^");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $text)
        {
            $prey = $text["PREYTX"];
            $predator = $text["PREDTX"];
            $taxon_id = @$link[$text["IDNUM"]];
            if($prey)
            {
                if($taxon_id = @$link[$text["IDNUM"]]) self::add_predator_prey($taxon_id, $prey, "is_food_source_of");
                else
                {
                    if($text["IDNUM"] != "IDNUM") 
                    {
                        $investigate++;
                        echo("\n investigate: prey: {$taxon_id}[$prey] -- IDNUM = " . $text["IDNUM"] . "\n");
                    }
                }
            }
            if($predator)
            {
                if($taxon_id = @$link[$text["IDNUM"]]) self::add_predator_prey($taxon_id, $predator, "eats");
                else
                {
                    if($text["IDNUM"] != "IDNUM") 
                    {
                        $investigate++;
                        echo("\n investigate: predator: {$taxon_id}[$predator] -- IDNUM = " . $text["IDNUM"] . "\n");
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
    }
    private function add_predator_prey($taxon_id, $comma_delimited_names, $type)
    {
        $names = explode(",", $comma_delimited_names);
        foreach($names as $taxa)
        {
            //manual adjustments
            $to_be_removed = array("are preferred prey nesting sites near water", "prey preferences vary at different localities depending upon ecological factors", "cocoons which myersiana was reared bear attached fragments", "adults are stored more commonly than nymphs", "all prey records are spiders", "only nymphs have been reported as prey.", "most prey is nymphal but occasionally adults are used.", "most prey are nymphs but adults are occasionally stored.", "from Galactica", "Leaf-mining larvae of", "Larvae of", "in melliginis group", "rarely females of latter group.", "usually obtained from herbaceous vegetation in open fields.", "prey is usually obtained in wooded areas.", "in Europe it preys chiefly on DAE.", "both adults and juveniles.", "in Europe the usual prey is caterpillars", "but there is one record of storing coleopterous larvae", "nymphs adults", "nymphs adult", "most prey records are nymphs", "but adults are used occasionally", "Aufeius impressicollis prey specimens were nymphs", "Euschistus conspersus observed prey were nymphs", "all prey stored were nymphs", "mostly immatures are stored although adults are used occasionally", "most prey were juveniles occasionally adults were used", "nymphs few adults", "all are leaf-mining beetle larvae", "adults are preyed upon more frequently than nymphs", "mostly nymphs few adults may used", "only nymphs are used so far", "only nymphs have been prey", "both adults immatures are stored most are snare-building", "prey consisted immatures penultimate stages sexes", "only few snare-builders.", "stores mostly errant spiders", "prefers errant snare-building spiders ratio", "and about equal numbers errant snare-building", "mostly nymphs few adults may used", "both adults immatures are stored most are snare-building", "prey consisted immatures penultimate stages sexes", "only few snare-builders.", "prefers errant snare-building spiders ratio", "are preferred prey", "are important prey", "possibly puer nymph");
            $taxa = trim(str_ireplace($to_be_removed, "", $taxa));
            $taxa = trim(str_ireplace("occasionally on Pisauridae and Gnaphosidae.", "Pisauridae; Gnaphosidae", $taxa));
            $taxa = trim(str_ireplace("prey in U. S. consist principally of male Brachycera and Cyclorrhapha", "Brachycera; Cyclorrhapha", $taxa));
            $taxa = explode(";", $taxa);
            foreach($taxa as $taxon_name)
            {
                $taxon_name = trim(str_ireplace("&", "", $taxon_name));
                $taxon_name = Functions::canonical_form($taxon_name);
                //manual adjustments
                if(in_array(strtolower($taxon_name), array("preferred prey are calyptrate are used", "snare-building spiders are preferred errant ratio", "only few snare-builders.", "prey consisted immatures penultimate stages sexes", "both adults immatures are stored most are snare-building", "rey", "all prey were nymphs", "as", "only nymphs have been prey", "young nymphs", "and about equal numbers errant snare-building", "both adults immatures are stored", "prefers errant snare-building spiders ratio", "adults subadults", "Small spiders", "in other areas", "in other areas", "all prey records are", "usual prey are", "rarely", "nymphs", "nymph", "european", "larvae", "larva", "adult", "adults", "juvenile", "juv", "adjuncta", "caterpillars", "pupa", "all nymphs", "probably", "sp"))) continue;
                if($taxon_name == "") continue;
                echo "\n canonical: [$taxon_name]";
                $occurrence = $this->add_occurrence($taxon_id, $taxon_name);
                $related_taxon = $this->add_taxon($taxon_name);
                $related_occurrence = $this->add_occurrence($related_taxon->taxonID, $this->taxa[$taxon_id]->scientificName);
                $a = new \eol_schema\Association();
                $a->occurrenceID = $occurrence->occurrenceID;
                $a->associationType = "http://hymenoptera.org/$type";
                $a->targetOccurrenceID = $related_occurrence->occurrenceID;
                $this->archive_builder->write_object_to_file($a);
            }
        }
    }
    */

}
?>
