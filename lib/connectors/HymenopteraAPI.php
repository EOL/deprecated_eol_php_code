<?php
namespace php_active_record;
// connector: [664]
class HymenopteraAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->text_path = array();
        $this->zip_path = "https://dl.dropboxusercontent.com/u/7597512/Hymenoptera/Hymenoptera_6_Mar_2003.zip";
        // $this->zip_path = "http://localhost/~eolit/Hymenoptera_6_Mar_2003.zip";
        // $this->zip_path = "http://localhost/~eolit/Hymenoptera_small.zip";
    }

    function get_all_taxa()
    {
        if(self::process_text_files())
        {
            $this->create_archive();
            // remove temp dir
            $parts = pathinfo($this->text_path["1"]);
            recursive_rmdir($parts["dirname"]);
            debug("\n temporary directory removed: " . $parts["dirname"]);
        }
    }

    private function process_text_files()
    {
        if(!self::load_zip_contents()) return FALSE;
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
        [Super-Generic ] => 614 xxx
        [Genus/Subgenus ] => 2540 xxx
        [Genus/Subgenus Synon] => 3459
        [Species ] => 19970 xxx
        [Species Synonym ] => 11083
        [Unplaced Taxon ] => 445 xxx
        [Nomen Nudum ] => 177
        [Species Group ] => 440 xxx
        */
        
        $i = 0;
        $link = array();
        foreach($taxa as $rec)
        {
            if(in_array($rec["RECTYPE"], array("Species", "Genus/Subgenus", "Species Group", "Super-Generic", "Unplaced Taxon", "Nomen Nudum")))
            {
                $i++;
                $sciname = $rec["GENUS"] . " " . $rec["SPECIES"] . " " . $rec["SUBSPECS"];
                if($rec["RECTYPE"] == "Species Group") $sciname = $rec["SPCGROUP"];
                $temp[$sciname] = 1;
                $link = $this->create_instances_from_taxon_object($rec, array(), $link);
            }
        }
        echo "\n\n total rows: " . count($taxa);
        echo "\n\n total taxa: " . count($temp);
        echo "\n\n link: " . count($link);
        echo "\n";

        self::process_free_text($func, $link);
        self::process_geo_data($link);
        self::process_ecology($func, $link);
        self::process_pollen_visitation($func, $link);
        self::process_predation($func, $link);
        return TRUE;

        /* Waiting from SPG on items below this line */

        // some sort of references
        // $fields = array("IDNUM", "REVSNTX", "TAXNTX");
        // $references = $func->make_array($this->text_path[4], $fields, "", array(), "^");
        // print_r($references);

        // references for biology and morphology
        // $fields = array("IDNUM", "BIOLGYTX", "MORPHTX");
        // $texts = $func->make_array($this->text_path[5], $fields, "", array(), "^");
        // $ref_ids = array();
        // $agent_ids = array();
        // foreach($texts as $text)
        // {
        //     $biology = (string) $text["BIOLGYTX"];
        //     $morphology = (string) $text["MORPHTX"];
        //     $taxon_id = @$link[$text["IDNUM"]];
        //     if($biology) $ref_ids = array_merge($ref_ids, get_object_reference_ids($biology));
        //     if($morphology) $ref_ids = array_merge($ref_ids, get_object_reference_ids($morphology));
        // }

        // comma-separated taxon names
        // $fields = array("IDNUM", "AHOSTTX", "PHOSTTX");
        // $taxon_remarks = $func->make_array($this->text_path[7], $fields, "", array(), "^");
        // print_r($taxon_remarks);

        // comma-separated taxon names
        // $fields = array("IDNUM", "PARATX", "SECHOSTX");
        // $taxon_remarks2 = $func->make_array($this->text_path[8], $fields, "", array(), "^");
        // print_r($taxon_remarks2);

        // wait for SPG to classify  
        // $fields = array("IDNUM", "HYPATX", "SYNTX");
        // $comments = $func->make_array($this->text_path[10], $fields, "", array(), "^");
        // print_r($comments);
    }

    private function process_geo_data($link)
    {
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
        foreach(new FileIterator($this->text_path[3]) as $line_number => $line)
        {
            if($line)
            {
                $desc = "";
                $line = trim($line);
                $values = explode("^", $line);
                $values = array_map('trim', $values);
                if($values[0] == "IDNUM") continue;
                echo "\n geodata $values[0] --- $values[302]";
                $idnum = $values[0];
                $desc = $values[1];
                for ($i = 2; $i <= 151; $i=$i+2)
                {
                    if($values[$i]) $desc .= "<br>" . $values[$i] . ": ". $values[$i+1];
                }
                for ($i = 152; $i <= 301; $i=$i+2) 
                {
                    if($values[$i]) $desc .= "<br>" . $values[$i] . ": ". $values[$i+1];
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
    }

    private function process_pollen_visitation($func, $link)
    {
        $fields = array("IDNUM", "POLLENTX", "VISITSTX");
        $texts = $func->make_array($this->text_path[6], $fields, "", array(), "^");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $text)
        {
            $pollen = (string) $text["POLLENTX"];
            $visits = (string) $text["VISITSTX"];
            $taxon_id = @$link[$text["IDNUM"]];
            if($pollen)
            {
                if($taxon_id = @$link[$text["IDNUM"]]) self::get_texts($pollen, $taxon_id, '', '#Associations', $text["IDNUM"] . "_pollen", $ref_ids, $agent_ids);
                else
                {
                    if($text["IDNUM"] != "IDNUM") 
                    {
                        $investigate++;
                        echo("\n investigate: pollen: {$taxon_id}[$pollen] -- IDNUM = " . $text["IDNUM"] . "\n");
                    }
                }
            }
            if($visits)
            {
                if($taxon_id = @$link[$text["IDNUM"]]) self::get_texts($visits, $taxon_id, '', '#Associations', $text["IDNUM"] . "_visit", $ref_ids, $agent_ids);
                else
                {
                    if($text["IDNUM"] != "IDNUM")
                    {
                        $investigate++;
                        echo("\n investigate: visits: {$taxon_id}[$visits] -- IDNUM = " . $text["IDNUM"] . "\n");
                    } 
                }
            }
        }
        echo "\n investigate: $investigate \n";
    }

    private function process_ecology($func, $link)
    {
        $fields = array("IDNUM", "ECOTX");
        $texts = $func->make_array($this->text_path[11], $fields, "", array(), "^");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $text)
        {
            $description = (string) $text["ECOTX"];
            $description = str_ireplace("&", "", $description);
            if($description)
            {
                if($taxon_id = @$link[$text["IDNUM"]]) self::get_texts($description, $taxon_id, '', '#Ecology', $text["IDNUM"]."_eco", $ref_ids, $agent_ids);
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
    }

    private function process_free_text($func, $link)
    {
        $fields = array("IDNUM", "FREETX");
        $texts = $func->make_array($this->text_path[2], $fields, "", array(), "^");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $text)
        {
            $description = (string) $text["FREETX"];
            if($description)
            {
                if($taxon_id = @$link[$text["IDNUM"]]) self::get_texts($description, $taxon_id, '', '#TaxonBiology', $text["IDNUM"]."_brief", $ref_ids, $agent_ids);
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
    }

    private function process_predation($func, $link)
    {
        $fields = array("IDNUM", "PREYTX", "PREDTX");
        $texts = $func->make_array($this->text_path[9], $fields, "", array(), "^");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $text)
        {
            $prey = (string) $text["PREYTX"];
            $predator = (string) $text["PREDTX"];
            $taxon_id = @$link[$text["IDNUM"]];
            if($prey)
            {
                if($taxon_id = @$link[$text["IDNUM"]]) self::get_texts($prey, $taxon_id, 'Prey', '#Associations', $text["IDNUM"].'_prey', $ref_ids, $agent_ids);
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
                if($taxon_id = @$link[$text["IDNUM"]]) self::get_texts($predator, $taxon_id, 'Predator', '#Associations', $text["IDNUM"].'_pred', $ref_ids, $agent_ids);
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

    function create_instances_from_taxon_object($rec, $reference_ids, $link)
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

        if(in_array($rec["RECTYPE"], array("Super-Generic", "Nomen Nudum", "Unplaced Taxon", "Species")))
        {
            if($rec["GENUS"] && $rec["SPECIES"])
            {
                $sciname = $rec["GENUS"] . " " . $rec["SPECIES"] . " " . $rec["SUBSPECS"];
                $rank = "species";
                $genus = $rec["GENUS"];
            }
            elseif($rec["SUBFAM"])
            {
                $sciname = $rec["SUBFAM"];
                $rank = "subfamily";
                $genus = "";
            }
            elseif($rec["FAMILY"])
            {
                $sciname = $rec["FAMILY"];
                $rank = "family";
                $genus = "";
            }
            elseif($rec["SUPERFAM"])
            {
                $sciname = $rec["SUPERFAM"];
                $rank = "super family";
                $genus = "";
            }
            elseif($rec["DIVISION"])
            {
                $sciname = $rec["DIVISION"];
                $rank = "division";
                $genus = "";
            }
            elseif($rec["SUBORDER"])
            {
                $sciname = $rec["SUBORDER"];
                $rank = "sub order";
                $genus = "";
            }
        }
        elseif($rec["RECTYPE"] == "Genus/Subgenus")
        {
            if($rec["SUBGENUS"])
            {
                $sciname = $rec["SUBGENUS"];
                $rank = "subgenus";
                $genus = "";
            }
            elseif($rec["GENUS"])
            {
                $sciname = $rec["GENUS"];
                $rank = "genus";
                $genus = "";
            }
            elseif($rec["LOCGENUS"])
            {
                $sciname = $rec["LOCGENUS"];
                $rank = "genus";
                $genus = "";
            }
            elseif($rec["LOCSUBGN"])
            {
                $sciname = $rec["LOCSUBGN"];
                $rank = "subgenus";
                $genus = "";
            }
        }
        elseif($rec["RECTYPE"] == "Species Group")
        {
            $sciname = $rec["SPCGROUP"];
            $rank = "species group";
            $genus = "";
        }
        $sciname = utf8_encode(trim($sciname));
        $taxon_id = (string) str_ireplace(" ", "_", trim($sciname));
        if(!$sciname)
        {
            echo "\n will exit 02"; 
            print_r($rec);
            return $link;
        }
        $authorship = $rec["AUTHOR"] . " " . $rec["PUBYEAR"];
        $idnum = (string) $rec["IDNUM"];
        $link[$idnum] = $taxon_id;

        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID                     = (string) $taxon_id; // take note, not TAXONID
        $taxon->taxonRank                   = (string) $rank;
        $taxon->scientificName              = (string) $sciname;
        $taxon->scientificNameAuthorship    = (string) $authorship;
        $taxon->family                      = (string) $rec["FAMILY"];
        $taxon->genus                       = (string) $genus;
        $this->taxa[$taxon->taxonID] = $taxon;
        return $link;
    }

    private function get_object_reference_ids($ref)
    {
        $reference_ids = array();
        $r = new \eol_schema\Reference();
        $r->full_reference = (string) $ref;
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
            $mr->taxonID = $taxon_id;
            $mr->identifier = $mr->taxonID . "_" . $code;
            $mr->type = 'http://purl.org/dc/dcmitype/Text';
            $mr->language = 'en';
            $mr->format = 'text/html';
            $mr->furtherInformationURL = '';
            $mr->description = utf8_encode($description);
            $mr->CVterm = $this->SPM . $subject;
            $mr->title = $title;
            $mr->creator = '';
            $mr->CreateDate = '';
            $mr->modified = '';
            $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc/3.0/';
            $mr->Owner = '';
            $mr->publisher = '';
            $mr->audience = 'Everyone';
            $mr->bibliographicCitation = '';
            $this->archive_builder->write_object_to_file($mr);
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }

    function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::get_remote_file($this->zip_path, array('timeout' => 172800, 'download_attempts' => 5)))
        {
            $parts = pathinfo($this->zip_path);
            $temp_file_path = $this->TEMP_FILE_PATH . "/" . $parts["basename"];
            $TMP = fopen($temp_file_path, "w");
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("tar -xzf $temp_file_path -C $this->TEMP_FILE_PATH");
            if(!file_exists($this->TEMP_FILE_PATH . "/Hds1-Hymenoptera-Final.txt")) 
            {
                $this->TEMP_FILE_PATH = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($this->TEMP_FILE_PATH . "/Hds1-Hymenoptera-Final.txt")) return FALSE;
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
            return TRUE;
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return FALSE;
        }
    }

}
?>