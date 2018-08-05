<?php
namespace php_active_record;
// connector: [667]
class AlgaebaseClassificationAPI
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
        $this->object_ids = array();
        $this->text_path = array();
        $this->zip_path = "http://localhost/~eolit/cp/AlgaeBase/AlgaebaseClassification.zip";
        $this->zip_path = "http://opendata.eol.org/dataset/d810e6b8-60b7-4405-8351-8bb20f2ed0a0/resource/a18eadd3-5846-4c21-830f-8f6dd0560a0b/download/algaebaseclassification.zip";
        $this->taxon_link["genus"] = "http://www.algaebase.org/search/genus/detail/?genus_id=";
        $this->taxon_link["species"] = "http://www.algaebase.org/search/species/detail/?species_id=";
        $this->csv_fields["genus"] = array("genus.genus", "genus.id", "genus.sStatus", "ta.taxon_authority", "ta.authority_year", "genus.Synonym_of_id", "empire", "kingdom", "subkingdom", "infrakingdom", "phylum", "subphylum", "class", "subclass", "order", "suborder", "family", "subfamily", "tribe");
        $this->csv_fields["species"] = array("species.id", "genus.Genus", "species.Species", "species.Subspecies", "species.Variety", "species.Forma", "taxon_authority.taxon_authority", "taxon_authority.authority_year", "species.Current_flag", "species.Record_status", "species.Accepted_name_serial", "species.genus_id", "species.key_Habitat", "species.Type_locality");
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';
        $this->bibliographic_citation = "M.D. Guiry in Guiry, M.D. & Guiry, G.M. " . date("Y") . ". AlgaeBase. World-wide electronic publication, National University of Ireland, Galway. http://www.algaebase.org; searched on " . date("d M Y") . ".";
        $this->not_numeric_id = 0;
        $this->syn_count = 0;
        $this->no_rank = 0;
        $this->debug = array(); // stats
    }

    function get_all_taxa()
    {
        if(!self::load_zip_contents()) return FALSE;
        self::assign_name_and_id();
        self::process_files("species");
        self::process_genera_files();
        self::add_higher_level_taxa_to_archive();
        $this->create_archive();
        // remove temp dir
        recursive_rmdir($this->TEMP_FILE_PATH);
        echo ("\n temporary directory removed: " . $this->TEMP_FILE_PATH);
        // some stats
        echo "\n count not numeric: " . $this->not_numeric_id;
        echo "\n synonyms count: " . $this->syn_count;
        echo "\n wrong data: " . $this->no_rank;
        // print_r($this->debug); //to list the unique habitat values
    }

    private function assign_name_and_id()
    {
        foreach($this->text_path as $group => $file_path)
        {
            $kind = $group;
            if($group != "species") $kind = "genus";
            foreach(new FileIterator($file_path) as $line_number => $line)
            {
                if($line)
                {
                    $line = str_ireplace(array('\n', '\t'), '', $line);
                    $line = trim($line);
                    $cols = str_getcsv($line, ',', '"');
                    $i = 0;
                    foreach($cols as $col)
                    {
                        $rec[$this->csv_fields[$kind][$i]] = $col;
                        $i++;
                    }
                    if($group == "species") 
                    {
                        $sciname = $rec["genus.Genus"] . " " . $rec["species.Species"];
                        if($rec["species.Subspecies"]) $sciname .= " " . $rec["species.Subspecies"];
                        if($rec["species.Variety"]) $sciname .= " " . $rec["species.Variety"];
                        if($rec["species.Forma"]) $sciname .= " " . $rec["species.Forma"];
                        $sciname = trim($sciname);
                        $taxon_id = "s_" . $rec["species.id"];
                        $parent = "g_" . $rec["species.genus_id"];
                    }
                    else
                    {
                        $sciname = trim($rec["genus.genus"]);
                        $taxon_id = "g_" . $rec["genus.id"];
                        $parent = "";
                        if    (@$rec["family"] && @$rec["family"] != "Fungi Family") $parent = @$rec["family"];
                        elseif(@$rec["order"]  && @$rec["order"]  != "Fungi Order") $parent = @$rec["order"];
                        elseif(@$rec["class"]  && @$rec["class"]  != "Fungi Class") $parent = @$rec["class"];
                        elseif(@$rec["phylum"] && @$rec["phylum"] != "Fungi Phylum") $parent = @$rec["phylum"];
                        elseif(@$rec["kingdom"]) $parent = @$rec["kingdom"];
                        if(!isset($this->name_id[@$rec["family"]])  && @$rec["family"] && @$rec["family"] != "Fungi Family") self::create_taxon(@$rec["family"], @$rec["order"], "family");
                        if(!isset($this->name_id[@$rec["order"]])   && @$rec["order"]  && @$rec["order"]  != "Fungi Order") self::create_taxon(@$rec["order"], @$rec["class"], "order");
                        if(!isset($this->name_id[@$rec["class"]])   && @$rec["class"]  && @$rec["class"]  != "Fungi Class") self::create_taxon(@$rec["class"], @$rec["phylum"], "class");
                        if(!isset($this->name_id[@$rec["phylum"]])  && @$rec["phylum"] && @$rec["phylum"] != "Fungi Phylum") self::create_taxon(@$rec["phylum"], @$rec["kingdom"], "phylum");
                        if(!isset($this->name_id[@$rec["kingdom"]]) && @$rec["kingdom"]) self::create_taxon(@$rec["kingdom"], "", "kingdom");
                    }
                    if($group == "species") $id = "species.id";
                    else                    $id = "genus.id";
                    if($rec[$id] == $id) continue;
                    $sciname = self::remove_brackets($sciname);
                    $parent = self::remove_brackets($parent);
                    $this->name_id[$taxon_id]["sciname"] = $sciname;
                    $this->name_id[$taxon_id]["parent"] = $parent;
                }
            }
        }
        echo "\n count: " . count($this->name_id) . "\n";
    }

    private function create_taxon($child, $parent, $rank)
    {
        $child = self::remove_brackets($child);
        $parent = self::remove_brackets($parent);
        $this->name_id[$child]["sciname"] = $child;
        $this->name_id[$child]["rank"] = $rank;
        $this->name_id[$child]["parent"] = $parent;
    }

    private function add_higher_level_taxa_to_archive()
    {
        $exclude = array("kingdom", "phylum", "class", "order", "family", "genus", "species", "subspecies");
        foreach($this->name_id as $taxon_id => $rec)
        {
            if(in_array($rec["sciname"], $exclude)) continue;
            if(!Functions::is_utf8($rec["sciname"])) continue;
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = (string) $taxon_id;
            $taxon->taxonRank                   = (string) @$rec["rank"];
            $taxon->scientificName              = (string) Functions::import_decode($rec["sciname"]);
            $taxon->parentNameUsageID           = $rec["parent"];
            if(isset($this->taxon_ids[$taxon_id])) continue;
            if(!$taxon->parentNameUsageID && @$rec["rank"] != "kingdom") continue;
            if(is_numeric(stripos($rec["sciname"], "unassigned"))) continue;
            if(!@$rec["rank"]) 
            {
                $this->no_rank++;
                // echo "\n wrong data: " . $rec["sciname"] . " [$taxon_id]";
            }
            else
            {
                $this->taxa[$taxon->taxonID] = $taxon;
                $this->taxon_ids[$taxon->taxonID] = 1;
            }
        }
    }

    private function process_files($group)
    {
        $kind = $group;
        if($group != "species") $kind = "genus";
        foreach(new FileIterator($this->text_path[$group]) as $line_number => $line)
        {
            if($line)
            {
                $line = trim(str_ireplace("\n", "", $line));
                $cols = str_getcsv($line, ',', '"');
                $i = 0;
                foreach($cols as $col)
                {
                    $rec[$this->csv_fields[$kind][$i]] = $col;
                    $i++;
                }
                if($group == "species")
                {
                    $id = "species.id";
                    $check_count = 14;
                }
                else
                {
                    $id = "genus.id";
                    $check_count = 19;
                }
                if($rec[$id] == $id) continue; //ignore first row
                if(!is_numeric($rec[$id]))
                {
                    $this->not_numeric_id++;
                    // echo "\n investigate taxon_id not numeric - data not suitable: [$rec[$id]]";
                    continue;
                }
                if(count($rec) != $check_count)
                {
                    echo "\n Investigate, invalid number of columns";
                    print_r($rec);
                    continue;
                }
                if($group != "species") $group = "genus";
                $this->create_instances_from_taxon_object($rec, $group, $line);
            }
        }
    }

    private function process_genera_files()
    {
        $genus_taxa = array("eukaryota", "prokaryota");
        foreach($genus_taxa as $group) self::process_files($group);
    }

    function create_instances_from_taxon_object($rec, $rank, $line)
    {
        $reference_ids = array();
        $source_url = "";
        $taxonomic_status = "";
        $genus = "";
        $parentNameUsageID = "";
        $taxon_remarks = "";

        if($rank == "species")
        {
            if(!$rec["genus.Genus"] || !$rec["species.Species"]) return;
            $sciname = $rec["genus.Genus"] . " " . $rec["species.Species"];
            if($rec["species.Subspecies"]) $sciname .= " " . $rec["species.Subspecies"];
            if($rec["species.Variety"]) $sciname .= " " . $rec["species.Variety"];
            if($rec["species.Forma"]) $sciname .= " " . $rec["species.Forma"];
            $sciname = trim($sciname);
            $taxon_id = "s_" . $rec["species.id"];

            if(!is_numeric($rec["species.id"])) // stats
            {
                echo "\n investigate species ID not numeric " . $rec["species.id"];
                return;
            }

            $authorship = $rec["taxon_authority.taxon_authority"];
            if($rec["taxon_authority.authority_year"]) $authorship .= " " . $rec["taxon_authority.authority_year"];
            $sciname = utf8_encode($sciname);
            $authorship = utf8_encode($authorship);
            if($rec["species.Current_flag"] == "U") $taxonomic_status = "uncertain";
            $rank = self::get_rank($rec["species.Record_status"]);
            $source_url = $this->taxon_link["species"] . $rec["species.id"];
            $parentNameUsageID = "g_" . $rec["species.genus_id"];

            $desc = $rec["species.key_Habitat"];
            if($desc && !in_array(trim($desc), array("none", "None")))
            {
                $this->debug[$desc] = "";
                $habitat = self::format_habitat($desc);
                self::add_string_types($taxon_id, "Habitat", $habitat, "http://eol.org/schema/terms/Habitat");
                // self::get_texts($desc, $taxon_id, '', $this->SPM . '#Habitat', '_habitat', array(), array()); --- conveted to structured data
            } 
            $desc = $rec["species.Type_locality"];
            if($desc && !in_array(trim($desc), array("[None given]")))
            {
                $desc = "Type locality: " . $rec["species.Type_locality"];
                self::get_texts($desc, $taxon_id, '', $this->EOL . '#TypeInformation', '_typeinfo', array(), array());
            }

            if($rec["species.Accepted_name_serial"] != 0)
            {
                $synonym_record = array("taxon_id"            => $taxon_id,
                                        "sciname"             => $sciname,
                                        "authorship"          => $authorship,
                                        "rank"                => $rank,
                                        "acceptedNameUsageID" => "s_" . $rec["species.Accepted_name_serial"]);
                self::create_synonym($synonym_record);
                return;
            }
        }
        if($rank == "genus")
        {
            $sciname = trim($rec["genus.genus"]);
            $taxon_id = "g_" . $rec["genus.id"];
            
            if(!is_numeric($rec["genus.id"])) // stats
            {
                echo "\n investigate genus ID not numeric ". $rec["genus.id"];
                return;
            }
            
            $authorship = $rec["ta.taxon_authority"];
            if($rec["ta.authority_year"]) $authorship .= " " . $rec["ta.authority_year"];

            if($this->name_id[$taxon_id]["sciname"] != $sciname) 
            {
                echo "\n investigate 01 [$sciname]\n";
                echo "\n[" . $this->name_id[$taxon_id]["sciname"] . "] neq [$sciname]";
                print_r($rec);
            }

            $sciname = utf8_encode($sciname);
            $authorship = utf8_encode($authorship);
            
            if($rec["genus.sStatus"] == "U") $taxonomic_status = "uncertain";
            $source_url = $this->taxon_link["genus"] . $taxon_id;
            if($parent = $this->name_id[$taxon_id]["parent"]) $parentNameUsageID = $this->name_id[$parent]["sciname"];

            $taxon_remarks = "";
            if($rec["empire"])          $taxon_remarks .= "<br>Empire: " . $rec["empire"];
            if($rec["kingdom"])         $taxon_remarks .= "<br>Kingdom: " . $rec["kingdom"];
            if($rec["subkingdom"])      $taxon_remarks .= "<br>Subkingdom: " . $rec["subkingdom"];
            if($rec["infrakingdom"])    $taxon_remarks .= "<br>Infrakingdom: " . $rec["infrakingdom"];
            if($rec["phylum"])          $taxon_remarks .= "<br>Phylum: " . $rec["phylum"];
            if($rec["subphylum"])       $taxon_remarks .= "<br>Subphylum: " . $rec["subphylum"];
            if($rec["class"])           $taxon_remarks .= "<br>Class: " . $rec["class"];
            if($rec["subclass"])        $taxon_remarks .= "<br>Subclass: " . $rec["subclass"];
            if($rec["order"])           $taxon_remarks .= "<br>Order: " . $rec["order"];
            if($rec["suborder"])        $taxon_remarks .= "<br>Suborder: " . $rec["suborder"];
            if($rec["family"])          $taxon_remarks .= "<br>Family: " . $rec["family"];
            if($rec["subfamily"])       $taxon_remarks .= "<br>Subfamily: " . $rec["subfamily"];
            if($rec["tribe"])           $taxon_remarks .= "<br>Tribe: " . $rec["tribe"];

            if($rec["genus.Synonym_of_id"] != 0)
            {
                $synonym_record = array("taxon_id"            => $taxon_id,
                                        "sciname"             => $sciname,
                                        "authorship"          => $authorship,
                                        "rank"                => $rank,
                                        "acceptedNameUsageID" => "g_" . $rec["genus.Synonym_of_id"]);
                self::create_synonym($synonym_record);
                return;
            }
        }

        $sciname = self::remove_brackets($sciname);
        $authorship = self::remove_brackets($authorship);

        if(!$sciname) return;
        if(is_numeric(stripos($sciname, "unassigned"))) return;
        if(!Functions::is_utf8($sciname) || !Functions::is_utf8($authorship)) return;

        // debug("\n sciname: [$sciname] [$authorship]");
        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID                     = (string) $taxon_id;
        $taxon->taxonRank                   = (string) $rank;
        $taxon->scientificName              = (string) $sciname;
        $taxon->scientificNameAuthorship    = (string) trim($authorship);
        $taxon->taxonomicStatus             = (string) $taxonomic_status;
        $taxon->furtherInformationURL       = (string) $source_url;
        $taxon->parentNameUsageID           = $parentNameUsageID;
        // $taxon->taxonRemarks                = $taxon_remarks; // no decision yet
        
        if(!$parentNameUsageID) 
        {
            echo "\n investigate 02 \n";
            print_r($rec);
        }
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxa[$taxon->taxonID] = $taxon;
            $this->taxon_ids[$taxon->taxonID] = 1;
            if(!$rank)
            {
                $this->no_rank++;
                echo "\n no rank2: [$sciname] [$taxon_id]";
            }
        }
        // else echo "\n already exists: [$taxon->taxonID] [$sciname] [$taxon_id]";
    }

    private function format_habitat($desc)
    {
        $desc = trim(strtolower($desc));
        if    ($desc == "freshwater")               return "http://purl.obolibrary.org/obo/ENVO_00002037";
        elseif($desc == "marine/freshwater")        return "http://eol.org/schema/terms/freshwaterAndMarine";
        elseif($desc == "brackish")                 return "http://purl.obolibrary.org/obo/ENVO_00000570";
        elseif($desc == "ubiquitous")               return "http://eol.org/schema/terms/ubiquitous";
        elseif($desc == "marine")                   return "http://purl.obolibrary.org/obo/ENVO_00000569";
        elseif($desc == "terrestrial")              return "http://purl.obolibrary.org/obo/ENVO_00002009";
        elseif($desc == "marine/terrestrial")       return "http://eol.org/schema/terms/terrestrialAndMarine";
        elseif($desc == "freshwater/terrestrial")   return "http://eol.org/schema/terms/terrestrialAndFreshwater";
        else
        {
            echo "\n investigate undefined habitat [$desc]\n";
            return $desc;
        }
    }
    
    private function remove_brackets($string)
    {
        return trim(preg_replace('/\s*\[[^)]*\]/', '', $string)); //remove brackets []
    }
    
    private function get_taxon_id($line, $rank)
    {
        if($rank == "species") $index = 0;
        else $index = 1;
        $line = explode(',', $line);
        return $line[$index];
    }

    private function get_rank($status)
    {
        switch ($status)
        {
            case "S":
                return "species";
            case "U":
                return "subspecies";
            case "V":
                return "variety";
            case "F":
                return "forma";
            default:
                return "species";
        }
    }
    
    private function create_synonym($rec)
    {
        if(!Functions::is_utf8($rec["sciname"])) return;
        $synonym = new \eol_schema\Taxon();
        $rec["sciname"] = self::remove_brackets($rec["sciname"]);
        $rec["authorship"] = self::remove_brackets($rec["authorship"]);
        if(!Functions::is_utf8($rec["sciname"]) || !Functions::is_utf8($rec["authorship"])) return;
        $synonym->taxonID                       = (string) $rec["taxon_id"];
        $synonym->scientificName                = (string) $rec["sciname"];
        $synonym->scientificNameAuthorship      = (string) $rec["authorship"];
        $synonym->taxonRank                     = (string) $rec["rank"];
        $synonym->acceptedNameUsageID           = (string) $rec["acceptedNameUsageID"];
        $synonym->taxonomicStatus               = (string) "synonym";
        if(!$synonym->scientificName) return;
        if(!isset($this->taxon_ids[$synonym->taxonID]))
        {
            $this->archive_builder->write_object_to_file($synonym);
            $this->taxon_ids[$synonym->taxonID] = 1;
            $this->syn_count++;
        }
    }

    private function get_texts($description, $taxon_id, $title, $subject, $code, $reference_ids = null, $agent_ids = null)
    {
        $description = utf8_encode($description);
        if(!Functions::is_utf8($description)) return;
        $mr = new \eol_schema\MediaResource();
        if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID = $taxon_id;
        $mr->identifier = $mr->taxonID . "_" . $code;
        $mr->type = 'http://purl.org/dc/dcmitype/Text';
        $mr->language = 'en';
        $mr->format = 'text/html';
        $mr->furtherInformationURL = $this->taxon_link["species"] . str_replace("s_", "", $taxon_id);
        $mr->description = $description;
        $mr->CVterm = $subject;
        $mr->title = $title;
        $mr->creator = '';
        $mr->CreateDate = '';
        $mr->modified = '';
        $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc/3.0/';
        $mr->Owner = '';
        $mr->publisher = '';
        $mr->audience = 'Everyone';
        $mr->bibliographicCitation = $this->bibliographic_citation;
        $this->archive_builder->write_object_to_file($mr);
    }

    function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }

    private function add_string_types($taxon_id, $label, $value, $mtype)
    {
        $catnum = "h";
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;
        $m->measurementOfTaxon = 'true';
        $m->source = $this->taxon_link["species"] . str_replace("s_", "", $taxon_id);
        $m->contributor = 'AlgaeBase';
        $m->measurementType = $mtype;
        $m->measurementValue = $value;
        $m->measurementMethod = '';
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . 'O' . $catnum; // suggested by Katja to use -- ['O' . $catnum]
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

    function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::get_remote_file($this->zip_path, array('timeout' => 172800, 'download_attempts' => 2)))
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
            if(file_exists($this->TEMP_FILE_PATH . "/all_species.csv")) 
            {
                $this->text_path["species"] = $this->TEMP_FILE_PATH . "/all_species.csv";
                $this->text_path["prokaryota"] = $this->TEMP_FILE_PATH . "/prokaryota.csv";
                $this->text_path["eukaryota"] = $this->TEMP_FILE_PATH . "/eukaryota (2).csv";
                print_r($this->text_path);
                return TRUE;
            }
            else return FALSE;
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return FALSE;
        }
    }

}
?>
