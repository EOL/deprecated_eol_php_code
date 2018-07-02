<?php
namespace php_active_record;
// connector: [660]
class RotifersAPI
{
    const CLASS_PARENT_ID = "Rotifera";
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->media_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';
        $this->text_path = array();

        // $this->zip_path = "http://localhost/cp/Rotifers/rotifers.zip";
        $this->zip_path = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Rotifers/rotifers.zip";
        
        // $this->image_path = "http://www.rotifera.hausdernatur.at/TestRWC/Rotifer_data/images";   // old
        // $this->image_path = "http://89.26.108.66/Rotifer_data/images";                           // old
        $this->image_path = "http://www.rotifera.hausdernatur.at/Rotifer_data/images";              // new, working as of July 1, 2018
        
        $this->invalid_taxa = array(); // for stats
        $this->taxa_references = array();
        $this->image_references = array();
        $this->taxon_url = "http://www.rotifera.hausdernatur.at/Species/Index/";

        $this->species_level_names = array();
        $this->higher_level_taxa = array();
        $this->id_names_list = array();
        $this->statuses = array();
    }

    function get_all_taxa()
    {
        self::process_text_files();
        // remove temp dir
        $path = $this->text_path["species"];
        $parts = pathinfo($path);
        $parts["dirname"] = str_ireplace("/rotifers", "", $parts["dirname"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }

    private function process_text_files()
    {
        self::load_zip_contents();
        print_r($this->text_path);
        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();
        self::initial_statuses($func);
        self::initialize_higher_level_taxa($func);
        self::prepare_higher_level_synonyms();
        self::process_taxon_references($func);
        self::process_image_references($func);
        
        $fields = array("lngSpecies_ID", "tblGenus.lngGenus_ID", "strGenus", "lngFamily_ID", "strFamily", "lngOrder_ID", "strOrder", "lngSuperOrder_ID", "strSuperOrder", "lngSubClass_ID", "strSubClass", "lngClass_ID", "strClass", "tblSpecies.lngGenus_ID", "lngSubGenus_ID", "strSpecies", "lngInfraRank_ID", "strSubSpeciesInfra", "lngAuthor_ID", "intYear", "strParentheses", "lngRank_ID", "bytValidity", "bytAvailability", "lngTaxStat_ID", "blnIsJunior", "lngSenior_ID");
        $taxa = $func->make_array($this->text_path["classification"], $fields, "", array());
        array_shift($taxa);
        $link = array();
        foreach($taxa as $rec) $link = $this->create_instances_from_taxon_object($rec, $link, "species");
        foreach($taxa as $rec) $link = $this->create_instances_from_taxon_object($rec, $link, "subspecies");
        
        // manual addition
        $names = array();
        $names[] = array("taxon" => "Rotifera", "id" => "Rotifera", "rank" => "phylum", "parent_id" => "");
        self::add_higher_taxa($names);
        
        // synonyms
        self::prepare_species_level_synonyms($func);

        // stats
        echo "\n\n total rows: " . count($taxa);
        echo "\n link: " . count($link) . "\n";

        // dataObjects
        self::process_specimen($link, $func);
        self::process_distribution($link, $func);
        self::process_specimen_images_v2($link, $func);
        self::process_species_images($link, $func);
        $this->create_archive();
    }

    private function initial_statuses($func)
    {
        $fields = array("lngTaxStat_ID", "strShortCode", "strTaxStatus");
        $records = $func->make_array($this->text_path["statuses"], $fields, "", array());
        array_shift($records);
        foreach($records as $rec) $this->statuses[$rec["strShortCode"]] = $rec["strTaxStatus"];
    }

    private function prepare_higher_level_synonyms()
    {
        foreach($this->higher_level_taxa as $t) {
            if(!in_array($t["level"], array("f_", "g_"))) continue; // only for family and genus
            if($t["blnIsJunior"] != "TRUE") continue;               // junior must be TRUE
            
            if    ($t["level"] == "f_") $rank = "family";
            elseif($t["level"] == "g_") $rank = "genus";
            
            $senior = functions::canonical_form($t["lngSenior_ID"]);
            if(!isset($this->id_names_list[$senior])) {
                echo "\n investigate lngSenior_ID doesn't exist 1 [$senior] \n";
                print_r($t);
            }
            if($senior && $this->id_names_list[$senior]) {
                $remarks = self::get_taxon_remarks($t);
                $rec = array();
                $rec["taxonID"] = $t["id"];
                $rec["scientificName"] = $t["taxon"];
                $rec["scientificNameAuthorship"] = $t["authorship"];
                $rec["taxonRank"] = $rank;
                $rec["acceptedNameUsageID"] = $this->id_names_list[$senior]["id"];
                $rec["taxonomicStatus"] = "synonym";
                $rec["taxonRemarks"] = $remarks;
                self::add_synonyms($rec);
            }
        }
    }

    private function prepare_species_level_synonyms($func)
    {
        $fields = array("lngSpecies_ID", "lngRank_ID", "bytValidity", "bytAvailability", "lngGenus_ID", "lngSubGenus_ID", "strSpecies", "lngInfraRank_ID", "strSubSpeciesInfra", "lngAuthor_ID", "intYear", "strParentheses", "strIUI", "strOrigSpell", "strOrigComb", "lngTaxStat_ID", "blnIsJunior", "lngSenior_ID");
        $records = $func->make_array($this->text_path["species"], $fields, "", array());
        array_shift($records);
        foreach($records as $t) {
            if($t["blnIsJunior"] != "TRUE") continue; // junior must be TRUE
            $senior = functions::canonical_form($t["lngSenior_ID"]);
            if(!isset($this->id_names_list[$senior])) {
                if(is_numeric($senior) || $senior == "") continue; // no need to investigate
                echo "\n investigate lngSenior_ID doesn't exist 2 [$senior] \n"; // when checked acceptable cases (n=6)
                print_r($t);
                continue;
            }
            $authorship = self::get_authorship($t);
            $rank = self::get_rank($t["lngRank_ID"]);
            $sciname = self::get_sciname($t);
            if($senior && $this->id_names_list[$senior]) {
                $remarks = self::get_taxon_remarks($t);
                $rec = array();
                $rec["taxonID"] = $t["lngSpecies_ID"];
                $rec["scientificName"] = $sciname;
                $rec["scientificNameAuthorship"] = $authorship;
                $rec["taxonRank"] = $rank;
                $rec["acceptedNameUsageID"] = $this->id_names_list[$senior]["id"];
                $rec["taxonomicStatus"] = "synonym";
                $rec["taxonRemarks"] = $remarks;
                self::add_synonyms($rec);
            }
        }
    }

    private function get_taxon_remarks($rec)
    {
        $remarks = "";
        if($rec["bytValidity"]) $remarks = "<br>Validity: " . $rec["bytValidity"];
        if($rec["lngTaxStat_ID"]) $remarks = "<br>Status: " . $this->statuses[$rec["lngTaxStat_ID"]] . " (" . $rec["lngTaxStat_ID"] . ")";
        return $remarks;
    }

    private function get_authorship($rec)
    {
        $authorship = trim($rec["lngAuthor_ID"] . ", " . $rec["intYear"]);
        if(strtolower(trim($rec["strParentheses"])) == "y") $authorship = "($authorship)";
        $authorship = self::remove_quotes($authorship);
        $authorship = str_replace(" et ", " & ", $authorship);
        return $authorship;
    }
    
    private function add_synonyms($rec)
    {
        if(!$rec["scientificName"]) return;
        $synonym = new \eol_schema\Taxon();
        $synonym->taxonID                       = $rec["taxonID"];
        $synonym->scientificName                = $rec["scientificName"];
        $synonym->scientificNameAuthorship      = $rec["scientificNameAuthorship"];
        $synonym->taxonRank                     = $rec["taxonRank"];
        $synonym->acceptedNameUsageID           = $rec["acceptedNameUsageID"];
        $synonym->taxonomicStatus               = $rec["taxonomicStatus"];
        $synonym->taxonRemarks                  = $rec["taxonRemarks"];
        if(!isset($this->taxon_ids[$synonym->taxonID]) && $synonym->scientificName) {
            $this->archive_builder->write_object_to_file($synonym);
            $this->taxon_ids[$synonym->taxonID] = 1;
        }
        else {
            echo "\n investigate: synonym already entered";
            print_r($rec);
        }
    }

    private function initialize_higher_level_taxa($func)
    {
        $taxa = array();
        $levels = array("class", "subclass", "superorder", "order", "family", "genus", "subgenus");
        foreach($levels as $level) {
            if($level == "class")          $fields = array("strClass", "c_", "lngClass_ID", "strClass",                                     "lngAuthor_ID", "intYear", "txtNotes");
            elseif($level == "subclass")   $fields = array("strSubClass", "sc_", "lngSubClass_ID", "lngClass_ID", "strSubClass",            "lngAuthor_ID", "intYear", "txtNotes");
            elseif($level == "superorder") $fields = array("strSuperOrder", "so_", "lngSuperOrder_ID", "lngSubClass_ID", "strSuperOrder",   "lngAuthor_ID", "intYear", "txtNotes");
            elseif($level == "order")      $fields = array("strOrder", "o_", "lngOrder_ID", "lngSuperOrder_ID", "strOrder",                 "lngAuthor_ID", "intYear", "txtNotes");
            elseif($level == "subgenus")   $fields = array("strSubGenus", "sg_", "lngSubGenus_ID", "lngGenus_ID", "strSubGenus",            "lngAuthor_ID", "intYear", "txtTypeSpecies", "txtTaxNotes");
            elseif($level == "family")     $fields = array("strFamily", "f_", "lngFamily_ID", "lngOrder_ID", "strFamily",                   "lngAuthor_ID", "intYear", "lngTaxStat_ID", "bytValidity", "blnIsJunior", "lngSenior_ID", "txtDiagnosis", "txtTaxNotes");
            elseif($level == "genus")      $fields = array("strGenus", "g_", "lngGenus_ID", "lngFamily_ID", "lngRank_ID", "strGenus",       "lngAuthor_ID", "intYear", "lngTaxStat_ID", "bytValidity", "blnIsJunior", "lngSenior_ID", "txtTaxNotes", "txtDiagnosis");
            
            $taxon_field = $fields[0];
            array_shift($fields);
            $id_code = $fields[0];
            array_shift($fields);
            $id_field = $fields[0];
            
            $records = $func->make_array($this->text_path[$level], $fields, "", array());
            array_shift($records);
            
            foreach($records as $rec) {
                if(!self::is_valid_string($rec[$taxon_field])) continue;
                $authorship = "";
                if(self::is_valid_string($rec["lngAuthor_ID"])) {
                    $authorship = trim($rec["lngAuthor_ID"] . ", " . $rec["intYear"]);
                    $authorship = self::remove_quotes($authorship);
                }
                $txtNotes = "";
                if(self::is_valid_string(@$rec["txtNotes"])) $txtNotes = trim($rec["txtNotes"]);
                $txtDiagnosis = "";
                if(self::is_valid_string(@$rec["txtDiagnosis"])) $txtDiagnosis = trim($rec["txtDiagnosis"]);
                $txtTaxNotes = "";
                if(self::is_valid_string(@$rec["txtTaxNotes"])) $txtTaxNotes = trim($rec["txtTaxNotes"]);
                $lngTaxStat_ID = "";
                if(self::is_valid_string(@$rec["lngTaxStat_ID"])) $lngTaxStat_ID = trim($rec["lngTaxStat_ID"]);
                $bytValidity = "";
                if(self::is_valid_string(@$rec["bytValidity"])) $bytValidity = trim($rec["bytValidity"]);
                $blnIsJunior = "";
                if(self::is_valid_string(@$rec["blnIsJunior"])) $blnIsJunior = trim($rec["blnIsJunior"]);
                $lngSenior_ID = "";
                if(self::is_valid_string(@$rec["lngSenior_ID"])) $lngSenior_ID = trim($rec["lngSenior_ID"]);

                if(in_array($level, array("family", "genus"))) { // bec only family and genus have the bytValidity field
                    if($bytValidity != "valid") continue;
                }
                
                $taxon_id = $id_code.$rec[$id_field];
                $temp = array("id" => $taxon_id, "taxon" => $rec[$taxon_field], "authorship" => $authorship, "level" => $id_code,
                        "txtNotes" => $txtNotes,
                        "txtDiagnosis" => $txtDiagnosis,
                        "txtTaxNotes" => $txtTaxNotes,
                        "lngTaxStat_ID" => $lngTaxStat_ID,
                        "bytValidity" => $bytValidity,
                        "blnIsJunior" => $blnIsJunior,
                        "lngSenior_ID" => $lngSenior_ID);
                $taxa[$taxon_id] = $temp;
                $this->id_names_list[$rec[$taxon_field]]["id"] = $taxon_id;
            }
        }
        $this->higher_level_taxa = $taxa;
    }
    
    private function process_taxon_references($func)
    {
        $fields = array("lngSpecies_ID", "lngF1_Ref_ID", "lngF3_RefAuthor_ID", "intF4_Year", "txtF5_Title", "lngF7_Journal_ID", "strF10_Vol", "strF13_Pages");
        $texts = $func->make_array($this->text_path["references"], $fields);
        array_shift($texts);
        foreach($texts as $rec) {
            if($rec["lngF1_Ref_ID"] == "lngF1_Ref_ID") continue;
            $ref = "";
            if(self::is_valid_string($rec["lngF3_RefAuthor_ID"]))   $ref .= $rec["lngF3_RefAuthor_ID"] . ". ";
            if(self::is_valid_string($rec["intF4_Year"]))           $ref .= $rec["intF4_Year"] . ". ";
            if(self::is_valid_string($rec["txtF5_Title"]))          $ref .= $rec["txtF5_Title"] . ". ";
            if(self::is_valid_string($rec["lngF7_Journal_ID"]))     $ref .= $rec["lngF7_Journal_ID"] . ". ";
            if(self::is_valid_string($rec["strF10_Vol"]))           $ref .= $rec["strF10_Vol"] . ". ";
            if(self::is_valid_string($rec["strF13_Pages"]))         $ref .= $rec["strF13_Pages"] . ". ";
            $ref = trim(self::remove_quotes($ref));
            for($i = 1; $i <= 5; $i++) $ref = str_replace("..", ".", $ref);
            if(!$ref) continue;
            $r = new \eol_schema\Reference();
            $r->full_reference = $ref;
            $r->identifier = $rec["lngF1_Ref_ID"];
            if(!isset($this->resource_reference_ids[$r->identifier])) {
               $this->resource_reference_ids[$r->identifier] = $r->full_reference;
               $this->archive_builder->write_object_to_file($r);
            }
            $taxon_id = Functions::canonical_form($rec["lngSpecies_ID"]);
            $this->taxa_references[$taxon_id][] = $r->identifier;
        }
    }

    private function process_image_references($func)
    {
        $fields = array("lngImage_ID", "lngSpecies_ID", "lngRef_ID", "strPages", "lngF3_RefAuthor_ID", "intF4_Year", "txtF5_Title", "lngF7_Journal_ID", "strF10_Vol");
        $texts = $func->make_array($this->text_path["image_references"], $fields);
        array_shift($texts);
        foreach($texts as $rec) {
            if($rec["lngRef_ID"] == "lngRef_ID") continue;
            $ref = "";
            if(self::is_valid_string($rec["lngF3_RefAuthor_ID"]))   $ref .= $rec["lngF3_RefAuthor_ID"] . ". ";
            if(self::is_valid_string($rec["intF4_Year"]))           $ref .= $rec["intF4_Year"] . ". ";
            if(self::is_valid_string($rec["txtF5_Title"]))          $ref .= $rec["txtF5_Title"] . ". ";
            if(self::is_valid_string($rec["lngF7_Journal_ID"]))     $ref .= $rec["lngF7_Journal_ID"] . ". ";
            if(self::is_valid_string($rec["strF10_Vol"]))           $ref .= $rec["strF10_Vol"] . ". ";
            if(self::is_valid_string($rec["strPages"]))             $ref .= $rec["strPages"] . ". ";
            $ref = trim(self::remove_quotes($ref));
            for($i = 1; $i <= 5; $i++) $ref = str_replace("..", ".", $ref);
            if(!$ref) continue;
            $r = new \eol_schema\Reference();
            $r->full_reference = $ref;
            $r->identifier = $rec["lngRef_ID"];
            if(!isset($this->resource_reference_ids[$r->identifier])) {
               $this->resource_reference_ids[$r->identifier] = $r->full_reference;
               $this->archive_builder->write_object_to_file($r);
            }
            $image_id = str_replace(" ", "_", self::remove_quotes($rec["lngImage_ID"]));
            $this->image_references[$image_id][] = $r->identifier;
        }
    }

    private function process_species_images($link, $func)
    {
        $fields = array("lngSpecies_ID", "lngImage_ID", "lngImgType_ID", "blnPermission");
        $texts = $func->make_array($this->text_path["species_images"], $fields);
        array_shift($texts);
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $rec) {
            if($rec["lngImage_ID"] == "lngImage_ID" || $rec["blnPermission"] == "FALSE") continue;
            $description = "";
            $rec["lngImage_ID"] = self::remove_quotes($rec["lngImage_ID"]);
            $media_url = self::get_image_path($rec["lngImage_ID"], $rec["lngImgType_ID"]);
            if(!$media_url) continue;
            $rec["lngImage_ID"] = str_ireplace(" ", "_", $rec["lngImage_ID"]);
            $media_id = $rec["lngImage_ID"];
            if($rec["lngImage_ID"]) {
                $rec["lngSpecies_ID"] = self::remove_quotes($rec["lngSpecies_ID"]);
                if($rec["lngSpecies_ID"] = trim(Functions::canonical_form($rec["lngSpecies_ID"]))) {
                    if($taxon_id = @$link[$rec["lngSpecies_ID"]]) self::get_images($description, $taxon_id, $media_id, $media_url, $ref_ids, $agent_ids);
                    else {
                        if($taxon_id && $rec["lngSpecies_ID"] != "lngSpecies_ID" && !in_array($rec["lngSpecies_ID"], $this->invalid_taxa)) {
                            $investigate++;
                            echo("\n investigate: species images: [$taxon_id] --- taxon = " . $rec["lngSpecies_ID"] . "\n");
                        }
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
    }

    private function process_specimen_images_v2($link, $func)
    {
        $fields = array("lngSpecies_ID", "lngImage_ID", "lngDocuTypeSpecimen", "lngPrep_ID", "lngSpecimen_ID", "lngImgType_ID", "blnPermission");
        $fields = array("lngImage_ID", "strNotes1", "txtNotes2", "blnPermission", "lngImgType_ID", "lngSpecies_ID");
        $texts = $func->make_array($this->text_path["specimen_images"], $fields);
        array_shift($texts);
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $rec) {
            if(!$rec["lngImgType_ID"]) continue;
            if(!$rec["lngSpecies_ID"]) continue;
            if($rec["lngImage_ID"] == "lngImage_ID" || $rec["blnPermission"] == "FALSE") continue;
            $description = self::remove_quotes($rec["strNotes1"]);
            $rec["lngImage_ID"] = self::remove_quotes($rec["lngImage_ID"]);
            $media_url = self::get_image_path($rec["lngImage_ID"], $rec["lngImgType_ID"]);
            if(!$media_url) continue;
            $media_id = str_ireplace(" ", "_", $rec["lngImage_ID"]);
            if($rec["lngImage_ID"]) {
                $rec["lngSpecies_ID"] = self::remove_quotes($rec["lngSpecies_ID"]);
                if($rec["lngSpecies_ID"] = trim(Functions::canonical_form($rec["lngSpecies_ID"]))) {
                    if($taxon_id = @$link[$rec["lngSpecies_ID"]]) self::get_images($description, $taxon_id, $media_id, $media_url, $ref_ids, $agent_ids);
                    else {
                        if($taxon_id && $rec["lngSpecies_ID"] != "lngSpecies_ID" && !in_array($rec["lngSpecies_ID"], $this->invalid_taxa)) {
                            $investigate++;
                            echo("\n investigate: specimen images: [$taxon_id] --- taxon = " . $rec["lngSpecies_ID"] . "\n");
                        }
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
    }

    private function get_image_path($filename, $image_type)
    {
        $filename = trim($filename);
        $image_type = trim($image_type);
        if(in_array(trim($filename), array("Slide Preparation", "lngImage_ID"))) return false;
        switch($image_type) {
            case "Additional Scan":
                 $folder = "addscan";
                 break;
            case "Automontage":
                $folder = "automontage";
                break;
            case "Live Image":
                $folder = "observation";
                break;
            case "Macrohabitat":
                $folder = "habitat";
                break;
            case "Microhabitat":
                $folder = "habitat";
                break;
            case "Preserved Image":
                $folder = "observation";
                break;
            case "Type Scan":
                $folder = "typescan";
                break;
            case "Topomap":
                echo("\n\n investigate: $filename -- $image_type \n ");
                return false;
                $folder = "map";
                break;
            default:
                echo("\n\n investigate: no folder: [$filename] -- [$image_type] \n ");
                return false;
        }
        $image_path = $this->image_path . "/$folder/_full-size/$filename";
        // remove text scan images
        if(preg_match("/_text(.*?).jpg/ims", $filename, $arr) || preg_match("/_text(.*?)].jpg/ims", $filename, $arr)) return false;
        return $image_path;
    }

    private function get_images($description, $taxon_id, $media_id, $media_url, $reference_ids, $agent_ids)
    {
        $description = utf8_encode(self::remove_quotes($description));
        if(in_array($media_id, $this->media_ids)) return;
        $this->media_ids[] = $media_id;
        $bibliographicCitation = false;
        if($reference_ids = @$this->image_references[$media_id]) {
            $reference_ids = array_unique($reference_ids);
            $bibliographicCitation = self::get_citation($reference_ids);
        }
        $mr = new \eol_schema\MediaResource();
        if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids)      $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID        = (string) $taxon_id;
        $mr->identifier     = (string) $media_id;
        $mr->type           = "http://purl.org/dc/dcmitype/StillImage";
        $mr->language       = 'en';
        $mr->format         = Functions::get_mimetype($media_url);
        $mr->CVterm         = "";
        $mr->Owner          = "";
        $mr->rights         = "";
        $mr->title          = "";
        $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc/3.0/";
        $mr->audience       = 'Everyone';
        $mr->description    = (string) $description;
        $mr->accessURI      = $media_url;
        $mr->furtherInformationURL = $this->taxon_url . $taxon_id;
        if($bibliographicCitation) $mr->bibliographicCitation = $bibliographicCitation;
        $this->archive_builder->write_object_to_file($mr);
    }

    private function get_citation($reference_ids)
    {
        $citation = "";
        foreach($reference_ids as $id) {
            if(@$this->resource_reference_ids[$id]) $citation .= trim($this->resource_reference_ids[$id]) . "<br><br>";
        }
        if($citation) return substr($citation, 0, strlen($citation) - 8); // to remove the last "<br><br>"
        return false;
    }

    private function process_distribution($link, $func)
    {
        $fields = array("lngSpeciesSenior_ID", "lngBiogeo_ID", "txtComments"); // lngSpeciesSenior_ID is the taxon
        $texts = $func->make_array($this->text_path["distribution"], $fields);
        array_shift($texts);
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        $taxa = array();
        foreach($texts as $rec) {
            $description = "";
            if(self::is_valid_string($rec["lngBiogeo_ID"])) {
                $description .= $rec["lngBiogeo_ID"];
                if(self::is_valid_string($rec["txtComments"])) $description .= ", " . $rec["txtComments"];
            }
            else {
                if(self::is_valid_string($rec["txtComments"])) $description .= $rec["txtComments"];
            }
            if($description) {
                $rec["lngSpeciesSenior_ID"] = self::remove_quotes($rec["lngSpeciesSenior_ID"]);
                if($rec["lngSpeciesSenior_ID"] = trim(Functions::canonical_form($rec["lngSpeciesSenior_ID"]))) {
                    if($taxon_id = @$link[$rec["lngSpeciesSenior_ID"]])  {
                        $taxa[$taxon_id]["distribution"][] = $description;
                        $taxa[$taxon_id]["lngBiogeo_ID"] = $rec["lngBiogeo_ID"];
                    }
                    else {
                        if($taxon_id && $rec["lngSpeciesSenior_ID"] != "lngSpeciesSenior_ID" && !in_array($rec["lngSpeciesSenior_ID"], $this->invalid_taxa)) {
                            $investigate++;
                            echo("\n investigate: distribution: [$taxon_id] --- taxon = " . $rec["lngSpeciesSenior_ID"] . "\n");
                        }
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
        foreach($taxa as $taxon_id => $rec) {
            if(@$rec["distribution"]) {
                $rec["distribution"] = array_unique($rec["distribution"]);
                $description = implode("<br>", $rec["distribution"]);
                self::get_texts($description, $taxon_id, 'Biogeography', '#Distribution', $taxon_id."_dist", $ref_ids, $agent_ids);
            }
        }
    }

    private function is_valid_string($string)
    {
        $string = trim($string);
        if(is_numeric(stripos($string, "n.s."))) return false;
        if(is_numeric(stripos($string, "????"))) return false;
        if($string) return true;
        return false;
    }
    
    private function remove_quotes($string)
    {
        $string = str_replace(array("...", ".."), ".", $string);
        return str_ireplace('"', '', $string);
    }

    private function process_specimen($link, $func)
    {
        $fields = array("lngSpecimen_ID", "taxon", "strTypeStat", "strCatNr", "lngRepository_ID", "strRepName", "lngPersPrep_ID", "lngPersID2_ID", "lngPersID3_ID", "lngPrep_ID", "lngDocuTypeSpecimen", "lngPrepMeth_ID", "bytCountPrep", "lngEcolNote_ID", "txtPrepNotes", "txtPersNotes");
        $texts = $func->make_array($this->text_path["specimen"], $fields, "");
        array_shift($texts);
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $rec) {
            $description = $rec["strTypeStat"];
            if($description) $description .= " for " . $rec["taxon"];
            else $description = "Non-type voucher specimen for " . $rec["taxon"];
            if(self::is_valid_string($rec["strCatNr"])) $description .= "<br>Catalog number: " . $rec["strCatNr"];
            if(self::is_valid_string($rec["strRepName"])) $description .= "<br>Collection: " . $rec["strRepName"];
            $prepared_by = "";
            if(self::is_valid_string($rec["lngPersPrep_ID"])) {
                $prepared_by .= "<br>Prepared by: " . $rec["lngPersPrep_ID"];
                /* commented later on per advise by partner
                if($rec["lngPersID2_ID"]) $prepared_by .= "; " . $rec["lngPersID2_ID"];
                if($rec["lngPersID3_ID"]) $prepared_by .= "; " . $rec["lngPersID3_ID"];
                */
            }
            $description .= self::remove_quotes($prepared_by);
            if(self::is_valid_string($rec["lngPrep_ID"])) $description .= "<br>Sex/stage/structure: " . $rec["lngPrep_ID"];
            $preparation = "";
            if(self::is_valid_string($rec["lngDocuTypeSpecimen"])) {
                $preparation .= "<br>Preparation: " . $rec["lngDocuTypeSpecimen"];
                if($rec["lngPrepMeth_ID"]) $preparation .= "; " . $rec["lngPrepMeth_ID"];
            }
            $description .= $preparation;
            if(self::is_valid_string($rec["bytCountPrep"])) $description .= "<br>Specimen Count: " . $rec["bytCountPrep"];
            $notes = "";
            if(self::is_valid_string($rec["lngEcolNote_ID"])) {
                $notes .= "<br>Notes: " . $rec["lngEcolNote_ID"];
                if(self::is_valid_string($rec["txtPrepNotes"])) $notes .= "; " . $rec["txtPrepNotes"];
                if(self::is_valid_string($rec["txtPersNotes"])) $notes .= "; " . $rec["txtPersNotes"];
            }
            $description .= $notes;
            $description = self::remove_quotes($description);
            if($description) {
                if($rec["taxon"] = trim(Functions::canonical_form($rec["taxon"]))) {
                    if($taxon_id = @$link[$rec["taxon"]]) self::get_texts($description, $taxon_id, 'Collection specimen', '#TypeInformation', $rec["lngSpecimen_ID"], $ref_ids, $agent_ids);
                    else {
                        if($taxon_id && $rec["taxon"] != "taxon" && !in_array($rec["taxon"], $this->invalid_taxa)) {
                            $investigate++;
                            echo("\n investigate: specimen: [$taxon_id] --- taxon = " . $rec["taxon"] . "\n");
                        }
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
    }

    function create_instances_from_taxon_object($rec, $link, $level_to_process)
    {
        if($level_to_process == "species") {
            if($rec["lngInfraRank_ID"] != "" || $rec["strSubSpeciesInfra"] != "") return $link;
        }
        if($level_to_process == "subspecies") {
            if($rec["lngInfraRank_ID"] == "" && $rec["strSubSpeciesInfra"] == "") return $link;
        }

        $rec["lngClass_ID"]          = "c_".$rec["lngClass_ID"];
        $rec["lngSubClass_ID"]       = "sc_".$rec["lngSubClass_ID"];
        $rec["lngSuperOrder_ID"]     = "so_".$rec["lngSuperOrder_ID"];
        $rec["lngOrder_ID"]          = "o_".$rec["lngOrder_ID"];
        $rec["lngFamily_ID"]         = "f_".$rec["lngFamily_ID"];
        $rec["tblGenus.lngGenus_ID"] = "g_".$rec["tblGenus.lngGenus_ID"];

        self::prepare_higher_taxa($rec);
        $rec = array_map('trim', $rec);
        $sciname = self::get_sciname($rec);

        $species_level_parent_id = self::get_parent(array($rec["tblGenus.lngGenus_ID"], $rec["lngFamily_ID"], $rec["lngOrder_ID"], $rec["lngSuperOrder_ID"], $rec["lngSubClass_ID"], $rec["lngClass_ID"], self::CLASS_PARENT_ID));
        if($rec["lngInfraRank_ID"] != "" || $rec["strSubSpeciesInfra"] != "") { // meaning taxon is subspecies
            /* $parent_id = self::add_species_for_subspecies_taxon($rec, $species_level_parent_id); 
            this adds the binomial for the subspecies, if binomial doesn't exist - not needed anymore */
            $parent_id = $species_level_parent_id;
        }
        else { // meaning taxon is species
            $species_name = trim($rec["tblSpecies.lngGenus_ID"] . " " . $rec["strSpecies"]);
            $this->species_level_names[$species_name] = $rec;
            $parent_id = $species_level_parent_id;
        }
        
        $sciname = trim($sciname);
        if(!$sciname || substr($sciname, 0, 1) == "-") return $link;
        $taxon_id = (string) $rec["lngSpecies_ID"];
        $rank = self::get_rank($rec["lngRank_ID"]);
        $authorship = self::get_authorship($rec);
        
        // fill-up taxon_names
        $this->id_names_list[$sciname]["id"] = $taxon_id;

        if($rec["bytValidity"] != "valid" || $rec["blnIsJunior"] == "TRUE") {
            $this->invalid_taxa[] = Functions::canonical_form($sciname); // for stats
            return $link;
        }
        
        if($reference_ids = @$this->taxa_references[Functions::canonical_form($sciname)]) $reference_ids = array_unique($reference_ids);
        $link[Functions::canonical_form($sciname)] = $taxon_id;
        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID                     = (string) $taxon_id;
        $taxon->taxonRank                   = (string) $rank;
        $taxon->scientificName              = (string) $sciname;
        $taxon->scientificNameAuthorship    = (string) $authorship;
        $taxon->parentNameUsageID           = $parent_id;
        $taxon->furtherInformationURL       = $this->taxon_url . $taxon_id;
        $taxon->taxonomicStatus             = $rec["bytValidity"];
        $remarks = self::get_taxon_remarks($rec);
        $taxon->taxonRemarks                = $remarks;
        if(!isset($this->taxon_ids[$taxon->taxonID]) && $taxon->scientificName) {
            $this->taxa[$taxon->taxonID] = $taxon;
            $this->taxon_ids[$taxon->taxonID] = 1;
        }
        return $link;
    }

    private function get_sciname($rec)
    {
        if(isset($rec["tblSpecies.lngGenus_ID"])) $sciname = $rec["tblSpecies.lngGenus_ID"];
        if(isset($rec["lngGenus_ID"])) $sciname = $rec["lngGenus_ID"];
        /* if($rec["lngSubGenus_ID"]) $sciname .= " " . $rec["lngSubGenus_ID"]; */ //not implemented, per partner's instructions
        if($rec["strSpecies"]) $sciname .= " " . $rec["strSpecies"];
        if($rec["lngInfraRank_ID"]) $sciname .= " " . $rec["lngInfraRank_ID"];
        if($rec["strSubSpeciesInfra"]) $sciname .= " " . $rec["strSubSpeciesInfra"];
        return $sciname;
    }
    
    private function prepare_higher_taxa($rec)
    {
        $class_parent_id      = self::CLASS_PARENT_ID;
        $subclass_parent_id   = self::get_parent(array(                                                                                             $rec["lngClass_ID"], $class_parent_id));
        $superorder_parent_id = self::get_parent(array(                                                                     $rec["lngSubClass_ID"], $rec["lngClass_ID"], $class_parent_id));
        $order_parent_id      = self::get_parent(array(                                           $rec["lngSuperOrder_ID"], $rec["lngSubClass_ID"], $rec["lngClass_ID"], $class_parent_id));
        $family_parent_id     = self::get_parent(array(                      $rec["lngOrder_ID"], $rec["lngSuperOrder_ID"], $rec["lngSubClass_ID"], $rec["lngClass_ID"], $class_parent_id));
        $genus_parent_id      = self::get_parent(array($rec["lngFamily_ID"], $rec["lngOrder_ID"], $rec["lngSuperOrder_ID"], $rec["lngSubClass_ID"], $rec["lngClass_ID"], $class_parent_id));
        $names = array();

        if(isset($this->higher_level_taxa[$rec["lngClass_ID"]])) {
            if(self::is_valid_string($rec["strClass"]))      $names[] = array("taxon" => $rec["strClass"],      "id" => $rec["lngClass_ID"],          "rank" => "class",      "parent_id" => $class_parent_id,      "authorship" => $this->higher_level_taxa[$rec["lngClass_ID"]]["authorship"]);
        }
        if(isset($this->higher_level_taxa[$rec["lngSubClass_ID"]])) {
            if(self::is_valid_string($rec["strSubClass"]))   $names[] = array("taxon" => $rec["strSubClass"],   "id" => $rec["lngSubClass_ID"],       "rank" => "subclass",   "parent_id" => $subclass_parent_id,   "authorship" => $this->higher_level_taxa[$rec["lngSubClass_ID"]]["authorship"]);
        }
        if(isset($this->higher_level_taxa[$rec["lngSuperOrder_ID"]])) {
            if(self::is_valid_string($rec["strSuperOrder"])) $names[] = array("taxon" => $rec["strSuperOrder"], "id" => $rec["lngSuperOrder_ID"],     "rank" => "superorder", "parent_id" => $superorder_parent_id, "authorship" => $this->higher_level_taxa[$rec["lngSuperOrder_ID"]]["authorship"]);
        }
        if(isset($this->higher_level_taxa[$rec["lngOrder_ID"]])) {
            if(self::is_valid_string($rec["strOrder"]))      $names[] = array("taxon" => $rec["strOrder"],      "id" => $rec["lngOrder_ID"],          "rank" => "order",      "parent_id" => $order_parent_id,      "authorship" => $this->higher_level_taxa[$rec["lngOrder_ID"]]["authorship"]);
        }
        if(isset($this->higher_level_taxa[$rec["lngFamily_ID"]])) {
            if(self::is_valid_string($rec["strFamily"]))     $names[] = array("taxon" => $rec["strFamily"],     "id" => $rec["lngFamily_ID"],         "rank" => "family",     "parent_id" => $family_parent_id,     "authorship" => $this->higher_level_taxa[$rec["lngFamily_ID"]]["authorship"]);
        }
        if(isset($this->higher_level_taxa[$rec["tblGenus.lngGenus_ID"]])) {
            if(self::is_valid_string($rec["strGenus"]))      $names[] = array("taxon" => $rec["strGenus"],      "id" => $rec["tblGenus.lngGenus_ID"], "rank" => "genus",      "parent_id" => $genus_parent_id,      "authorship" => $this->higher_level_taxa[$rec["tblGenus.lngGenus_ID"]]["authorship"]);
        }
        self::add_higher_taxa($names);
    }
    
    private function get_parent($parents)
    {
        foreach($parents as $parent) {
            $parts = explode("_", $parent);
            if(self::is_valid_string(@$parts[1])) {
                if(isset($this->higher_level_taxa[$parent])) return $parent;
            }
        }
        return self::CLASS_PARENT_ID;
    }

    private function add_species_for_subspecies_taxon($rec, $species_level_parent_id) // this $rec is subspecies taxon
    {
        $species_name = trim($rec["tblSpecies.lngGenus_ID"] . " " . $rec["strSpecies"]);
        if(isset($this->species_level_names[$species_name])) return $this->species_level_names[$species_name]["lngSpecies_ID"];
        else {
            $names = array();
            if(self::is_valid_string($species_name)) $names[] = array("taxon" => $species_name, "id" => str_replace(" ", "_", $species_name), "rank" => "species", "parent_id" => $species_level_parent_id);
            self::add_higher_taxa($names);
            return str_replace(" ", "_", $species_name);
        }
    }
    
    private function add_higher_taxa($names)
    {
        foreach($names as $name) {
            $taxon = new \eol_schema\Taxon();
            $taxon->scientificName      = (string) $name["taxon"];
            $taxon->taxonID             = (string) $name["id"];
            $taxon->taxonRank           = (string) $name["rank"];
            $taxon->parentNameUsageID   = (string) $name["parent_id"];
            $taxon->scientificNameAuthorship = (string) @$name["authorship"];
            if(!isset($this->taxon_ids[$taxon->taxonID]) && $taxon->scientificName) {
                $this->taxa[$taxon->taxonID] = $taxon;
                $this->taxon_ids[$taxon->taxonID] = 1;
            }
        }
    }
    
    private function get_texts($description, $taxon_id, $title, $subject, $code, $reference_ids = null, $agent_ids = null)
    {
        $description = utf8_encode(self::remove_quotes($description));
        $description = str_ireplace("<br>", "xxxyyy", $description);
        $description = strip_tags($description);
        $description = str_ireplace("xxxyyy", "<br>", $description);
        if(in_array($code, $this->media_ids)) return;
        if(!Functions::is_utf8($description)) return;
        $this->media_ids[] = $code;
        if(in_array($subject, array("#TypeInformation"))) $subject = $this->EOL . $subject;
        else $subject = $this->SPM . $subject;
        $mr = new \eol_schema\MediaResource();
        if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID = $taxon_id;
        $mr->identifier = $code;
        $mr->type = "http://purl.org/dc/dcmitype/Text";
        $mr->language = "en";
        $mr->format = "text/html";
        $mr->furtherInformationURL = $this->taxon_url . $taxon_id;
        $mr->description = (string) $description;
        $mr->CVterm = $subject;
        $mr->title = $title;
        $mr->creator = "";
        $mr->CreateDate = "";
        $mr->modified = "";
        $mr->UsageTerms = "http://creativecommons.org/licenses/by-nc/3.0/";
        $mr->Owner = "";
        $mr->publisher = "";
        $mr->audience = "Everyone";
        $mr->bibliographicCitation = "";
        $this->archive_builder->write_object_to_file($mr);
    }

    function create_archive()
    {
        foreach($this->taxa as $t) {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }

    function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::get_remote_file($this->zip_path, array('timeout' => 172800, 'download_attempts' => 2))) {
            $parts = pathinfo($this->zip_path);
            $temp_file_path = $this->TEMP_FILE_PATH . "/" . $parts["basename"];
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("unzip $temp_file_path -d $this->TEMP_FILE_PATH");
            if(!file_exists($this->TEMP_FILE_PATH . "/species.txt")) {
                $this->TEMP_FILE_PATH = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($this->TEMP_FILE_PATH . "/species.txt")) return;
            }
            $this->text_path["statuses"]         = $this->TEMP_FILE_PATH . "/statuses.txt";
            $this->text_path["classification"]   = $this->TEMP_FILE_PATH . "/classification.txt";
            $this->text_path["species"]          = $this->TEMP_FILE_PATH . "/species.txt";
            $this->text_path["class"]            = $this->TEMP_FILE_PATH . "/class.txt";
            $this->text_path["subclass"]         = $this->TEMP_FILE_PATH . "/subclass.txt";
            $this->text_path["superorder"]       = $this->TEMP_FILE_PATH . "/superorder.txt";
            $this->text_path["order"]            = $this->TEMP_FILE_PATH . "/order.txt";
            $this->text_path["subgenus"]         = $this->TEMP_FILE_PATH . "/subgenus.txt";
            $this->text_path["genus"]            = $this->TEMP_FILE_PATH . "/genus.txt";
            $this->text_path["family"]           = $this->TEMP_FILE_PATH . "/family.txt";
            $this->text_path["specimen"]         = $this->TEMP_FILE_PATH . "/specimen.txt";
            $this->text_path["distribution"]     = $this->TEMP_FILE_PATH . "/distribution.txt";
            $this->text_path["specimen_images"]  = $this->TEMP_FILE_PATH . "/specimen_images_v2.txt";
            $this->text_path["species_images"]   = $this->TEMP_FILE_PATH . "/species_images.txt";
            $this->text_path["references"]       = $this->TEMP_FILE_PATH . "/references.txt";
            $this->text_path["image_references"] = $this->TEMP_FILE_PATH . "/image_references.txt";
        }
        else {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return;
        }
    }

    private function get_rank($string)
    {
        switch($string) {
            case "CL":
                 return "class";
                 break;
            case "FA":
                return "family";
                break;
            case "f.":
                return "forma";
                break;
            case "Ge":
                return "genus";
                break;
            case "OR":
                return "order";
                break;
            case "Sp":
                return "species";
                break;
            case "sCl":
                return "subclass";
                break;
            case "sGe":
                return "subgenus";
                break;
            case "Ssp":
                return "subspecies";
                break;
            case "var.":
                return "varietas";
                break;
            default:
                if($string != "") echo "\n investigate no rank [$string]\n";
        }
    }

    private function process_specimen_images($link, $func)
    {
        $fields = array("lngSpecies_ID", "lngImage_ID", "lngDocuTypeSpecimen", "lngPrep_ID", "lngSpecimen_ID", "lngImgType_ID", "blnPermission");
        $texts = $func->make_array($this->text_path["specimen_images"], $fields);
        array_shift($texts);
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $rec) {
            if($rec["lngImage_ID"] == "lngImage_ID") continue;
            if($rec["lngImage_ID"] == "lngImage_ID" || $rec["blnPermission"] == "FALSE") continue;
            $description = "";
            if($rec["lngDocuTypeSpecimen"]) {
                $description .= $rec["lngDocuTypeSpecimen"];
                if($rec["lngPrep_ID"]) $description .= ", " . $rec["lngPrep_ID"];
            }
            else {
                if($rec["lngPrep_ID"]) $description .= $rec["lngPrep_ID"];
            }
            $rec["lngImage_ID"] = self::remove_quotes($rec["lngImage_ID"]);
            $media_url = self::get_image_path($rec["lngImage_ID"], $rec["lngImgType_ID"]);
            if(!$media_url) continue;
            $rec["lngImage_ID"] = str_ireplace(" ", "_", $rec["lngImage_ID"]);
            $media_id = $rec["lngImage_ID"];
            if($rec["lngImage_ID"]) {
                $rec["lngSpecies_ID"] = self::remove_quotes($rec["lngSpecies_ID"]);
                if($rec["lngSpecies_ID"] = trim(Functions::canonical_form($rec["lngSpecies_ID"]))) {
                    if($taxon_id = @$link[$rec["lngSpecies_ID"]]) self::get_images($description, $taxon_id, $media_id, $media_url, $ref_ids, $agent_ids);
                    else {
                        if($taxon_id && $rec["lngSpecies_ID"] != "lngSpecies_ID" && !in_array($rec["lngSpecies_ID"], $this->invalid_taxa)) {
                            $investigate++;
                            echo("\n investigate: specimen images: [$taxon_id] --- taxon = " . $rec["lngSpecies_ID"] . "\n");
                        }
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
    }

    function some_stats()
    {
        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();
        $fields = array("identifier", "full_reference");
        $path = DOC_ROOT . "applications/content_server/resources/660/reference.tab";
        $records = $func->make_array($path, $fields, "", array());
        foreach($records as $rec) print_r($rec);
    }

}
?>
