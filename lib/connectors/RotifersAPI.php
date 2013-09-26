<?php
namespace php_active_record;
// connector: [660]
class RotifersAPI
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
        $this->media_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->EOL = 'http://www.eol.org/voc/table_of_contents';
        /* $this->zip_path = "http://localhost/~eolit/cp/Rotifers/rotifers.zip"; */
        $this->zip_path = "https://dl.dropboxusercontent.com/u/7597512/Rotifers/rotifers.zip";
        $this->text_path = array();
        /* $this->image_path = "http://www.rotifera.hausdernatur.at/TestRWC/Rotifer_data/images"; */
        $this->image_path = "http://89.26.108.66/Rotifer_data/images";
        $this->invalid_taxa = array(); // for stats
        $this->taxa_references = array();
        $this->image_references = array();
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
        self::process_taxon_references($func);
        self::process_image_references($func);
        $fields = array("lngSpecies_ID", "lngRank_ID", "bytValidity", "bytAvailability", "lngGenus_ID", "lngSubGenus_ID", "strSpecies", "lngInfraRank_ID", "strSubSpeciesInfra", "lngAuthor_ID", "intYear", "strParentheses", "strIUI", "strOrigSpell", "strOrigComb");
        $taxa = $func->make_array($this->text_path["species"], $fields, "", array());
        $link = array();
        foreach($taxa as $rec) $link = $this->create_instances_from_taxon_object($rec, $link);
        echo "\n\n total rows: " . count($taxa);
        echo "\n\n link: " . count($link);
        echo "\n";
        self::process_specimen($link, $func);
        self::process_distribution($link, $func);
        self::process_specimen_images_v2($link, $func);
        self::process_species_images($link, $func);
        $this->create_archive();
    }

    private function process_taxon_references($func)
    {
        $fields = array("lngSpecies_ID", "lngF1_Ref_ID", "lngF3_RefAuthor_ID", "intF4_Year", "txtF5_Title", "lngF7_Journal_ID", "strF10_Vol", "strF13_Pages");
        $texts = $func->make_array($this->text_path["references"], $fields);
        foreach($texts as $rec)
        {
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
            if(!isset($this->resource_reference_ids[$r->identifier]))
            {
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
        foreach($texts as $rec)
        {
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
            if(!isset($this->resource_reference_ids[$r->identifier]))
            {
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
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $rec)
        {
            if($rec["lngImage_ID"] == "lngImage_ID" || $rec["blnPermission"] == "FALSE") continue;
            $description = "";
            $rec["lngImage_ID"] = self::remove_quotes($rec["lngImage_ID"]);
            $media_url = self::get_image_path($rec["lngImage_ID"], $rec["lngImgType_ID"]);
            if(!$media_url) continue;
            $rec["lngImage_ID"] = str_ireplace(" ", "_", $rec["lngImage_ID"]);
            $media_id = $rec["lngImage_ID"];
            if($rec["lngImage_ID"])
            {
                $rec["lngSpecies_ID"] = self::remove_quotes($rec["lngSpecies_ID"]);
                if($rec["lngSpecies_ID"] = trim(Functions::canonical_form($rec["lngSpecies_ID"])))
                {
                    if($taxon_id = @$link[$rec["lngSpecies_ID"]]) self::get_images($description, $taxon_id, $media_id, $media_url, $ref_ids, $agent_ids);
                    else
                    {
                        if($rec["lngSpecies_ID"] != "lngSpecies_ID" && !in_array($rec["lngSpecies_ID"], $this->invalid_taxa))
                        {
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
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $rec)
        {
            if(!$rec["lngImgType_ID"]) continue;
            if(!$rec["lngSpecies_ID"]) continue;
            if($rec["lngImage_ID"] == "lngImage_ID" || $rec["blnPermission"] == "FALSE") continue;
            $description = self::remove_quotes($rec["strNotes1"]);
            $rec["lngImage_ID"] = self::remove_quotes($rec["lngImage_ID"]);
            $media_url = self::get_image_path($rec["lngImage_ID"], $rec["lngImgType_ID"]);
            if(!$media_url) continue;
            $media_id = str_ireplace(" ", "_", $rec["lngImage_ID"]);
            if($rec["lngImage_ID"])
            {
                $rec["lngSpecies_ID"] = self::remove_quotes($rec["lngSpecies_ID"]);
                if($rec["lngSpecies_ID"] = trim(Functions::canonical_form($rec["lngSpecies_ID"])))
                {
                    if($taxon_id = @$link[$rec["lngSpecies_ID"]]) self::get_images($description, $taxon_id, $media_id, $media_url, $ref_ids, $agent_ids);
                    else
                    {
                        if($rec["lngSpecies_ID"] != "lngSpecies_ID" && !in_array($rec["lngSpecies_ID"], $this->invalid_taxa))
                        {
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
        if(in_array($filename, array("Slide Preparation"))) return false;
        switch($image_type)
        {
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
        if($reference_ids = @$this->image_references[$media_id])
        {
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
        if($bibliographicCitation) $mr->bibliographicCitation = $bibliographicCitation;
        $this->archive_builder->write_object_to_file($mr);
    }

    private function get_citation($reference_ids)
    {
        $citation = "";
        foreach($reference_ids as $id)
        {
            if(@$this->resource_reference_ids[$id]) $citation .= trim($this->resource_reference_ids[$id]) . "<br><br>";
        }
        if($citation) return substr($citation, 0, strlen($citation) - 8); // to remove the last "<br><br>"
        return false;
    }

    private function process_distribution_xml($link, $func)
    {
        $xml = Functions::get_hashed_response($this->text_path["distribution"], array('timeout' => 10800, 'download_attempts' => 2));
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        $taxa = array();
        foreach($xml->distribution as $rec)
        {
            $description = "";
            if(self::is_valid_string($rec->lngBiogeo_ID))
            {
                $description .= $rec->lngBiogeo_ID;
                if(self::is_valid_string($rec->txtComments)) $description .= ", " . $rec->txtComments;
            }
            else
            {
                if(self::is_valid_string($rec->txtComments)) $description .= $rec->txtComments;
            }
            if($description)
            {
                $rec->lngSpeciesSenior_ID = self::remove_quotes($rec->lngSpeciesSenior_ID);
                if($rec->lngSpeciesSenior_ID = trim(Functions::canonical_form($rec->lngSpeciesSenior_ID)))
                {
                    if($taxon_id = @$link[$rec->lngSpeciesSenior_ID]) 
                    {
                        $taxa[$taxon_id]["distribution"][] = $description;
                        $taxa[$taxon_id]["lngBiogeo_ID"] = $rec->lngBiogeo_ID;
                    }
                    else
                    {
                        if($rec->lngSpeciesSenior_ID != "lngSpeciesSenior_ID" && !in_array($rec->lngSpeciesSenior_ID, $this->invalid_taxa))
                        {
                            $investigate++;
                            echo("\n investigate: distribution: [$taxon_id] --- taxon = " . $rec->lngSpeciesSenior_ID . "\n");
                        }
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
        print_r($taxa); exit;
        foreach($taxa as $taxon_id => $rec)
        {
            if(@$rec["distribution"])
            {
                $rec["distribution"] = array_unique($rec["distribution"]);
                $description = implode("<br>", $rec["distribution"]);
                self::get_texts($description, $taxon_id, '', '#Distribution', $taxon_id."_dist", $ref_ids, $agent_ids);
            }
        }
    }

    private function process_distribution($link, $func)
    {
        $fields = array("lngSpeciesSenior_ID", "lngBiogeo_ID", "txtComments"); // lngSpeciesSenior_ID is the taxon
        $texts = $func->make_array($this->text_path["distribution"], $fields);
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        $taxa = array();
        foreach($texts as $rec)
        {
            $description = "";
            if(self::is_valid_string($rec["lngBiogeo_ID"]))
            {
                $description .= $rec["lngBiogeo_ID"];
                // if(self::is_valid_string($rec["txtComments"])) $description .= ", " . $rec["txtComments"];
            }
            else
            {
                // if(self::is_valid_string($rec["txtComments"])) $description .= $rec["txtComments"];
            }
            if($description)
            {
                $rec["lngSpeciesSenior_ID"] = self::remove_quotes($rec["lngSpeciesSenior_ID"]);
                if($rec["lngSpeciesSenior_ID"] = trim(Functions::canonical_form($rec["lngSpeciesSenior_ID"])))
                {
                    if($taxon_id = @$link[$rec["lngSpeciesSenior_ID"]]) 
                    {
                        $taxa[$taxon_id]["distribution"][] = $description;
                        $taxa[$taxon_id]["lngBiogeo_ID"] = $rec["lngBiogeo_ID"];
                    }
                    else
                    {
                        if($rec["lngSpeciesSenior_ID"] != "lngSpeciesSenior_ID" && !in_array($rec["lngSpeciesSenior_ID"], $this->invalid_taxa))
                        {
                            $investigate++;
                            echo("\n investigate: distribution: [$taxon_id] --- taxon = " . $rec["lngSpeciesSenior_ID"] . "\n");
                        }
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
        foreach($taxa as $taxon_id => $rec)
        {
            if(@$rec["distribution"])
            {
                $rec["distribution"] = array_unique($rec["distribution"]);
                $description = implode("<br>", $rec["distribution"]);
                self::get_texts($description, $taxon_id, '', '#Distribution', $taxon_id."_dist", $ref_ids, $agent_ids);
            }
        }
    }

    private function is_valid_string($string)
    {
        $string = trim($string);
        if(is_numeric(stripos($string, "n.s."))) return false;
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
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $rec)
        {
            $description = $rec["strTypeStat"];
            if($description) $description .= " for " . $rec["taxon"];
            else $description = "Non-type voucher specimen for " . $rec["taxon"];
            if(self::is_valid_string($rec["strCatNr"])) $description .= "<br>Catalog number: " . $rec["strCatNr"];
            if(self::is_valid_string($rec["strRepName"])) $description .= "<br>Collection: " . $rec["strRepName"];
            $prepared_by = "";
            if(self::is_valid_string($rec["lngPersPrep_ID"])) 
            {
                $prepared_by .= "<br>Prepared by: " . $rec["lngPersPrep_ID"];
                /* commented later on per advise by partner
                if($rec["lngPersID2_ID"]) $prepared_by .= "; " . $rec["lngPersID2_ID"];
                if($rec["lngPersID3_ID"]) $prepared_by .= "; " . $rec["lngPersID3_ID"];
                */
            }
            $description .= self::remove_quotes($prepared_by);
            if(self::is_valid_string($rec["lngPrep_ID"])) $description .= "<br>Sex/stage/structure: " . $rec["lngPrep_ID"];
            $preparation = "";
            if(self::is_valid_string($rec["lngDocuTypeSpecimen"])) 
            {
                $preparation .= "<br>Preparation: " . $rec["lngDocuTypeSpecimen"];
                if($rec["lngPrepMeth_ID"]) $preparation .= "; " . $rec["lngPrepMeth_ID"];
            }
            $description .= $preparation;
            if(self::is_valid_string($rec["bytCountPrep"])) $description .= "<br>Specimen Count: " . $rec["bytCountPrep"];
            $notes = "";
            if(self::is_valid_string($rec["lngEcolNote_ID"]))
            {
                $notes .= "<br>Notes: " . $rec["lngEcolNote_ID"];
                if(self::is_valid_string($rec["txtPrepNotes"])) $notes .= "; " . $rec["txtPrepNotes"];
                if(self::is_valid_string($rec["txtPersNotes"])) $notes .= "; " . $rec["txtPersNotes"];
            }
            $description .= $notes;
            $description = self::remove_quotes($description);
            if($description)
            {
                if($rec["taxon"] = trim(Functions::canonical_form($rec["taxon"])))
                {
                    if($taxon_id = @$link[$rec["taxon"]]) self::get_texts($description, $taxon_id, '', '#TypeInformation', $rec["lngSpecimen_ID"], $ref_ids, $agent_ids);
                    else
                    {
                        if($rec["taxon"] != "taxon" && !in_array($rec["taxon"], $this->invalid_taxa))
                        {
                            $investigate++;
                            echo("\n investigate: specimen: {$taxon_id} --- taxon = " . $rec["taxon"] . "\n");
                        }
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
    }

    function create_instances_from_taxon_object($rec, $link)
    {
        $rec = array_map('trim', $rec);
        $sciname = $rec["lngGenus_ID"];
        if($rec["strSpecies"]) $sciname .= " " . $rec["strSpecies"];
        if($rec["lngInfraRank_ID"]) $sciname .= " " . $rec["lngInfraRank_ID"];
        if($rec["strSubSpeciesInfra"]) $sciname .= " " . $rec["strSubSpeciesInfra"];
        $sciname = trim($sciname);
        if(!$sciname || substr($sciname, 0, 1) == "-") return $link;
        $taxon_id = (string) $rec["lngSpecies_ID"];
        $rank = self::get_rank($rec["lngRank_ID"]);
        $genus = "";
        if(is_numeric(stripos($sciname, " ")))
        {
            $parts = explode(" ", $sciname);
            $genus = $parts[0];
        }
        $authorship = "";
        $authorship = $rec["lngAuthor_ID"] . " " . $rec["intYear"];
        if($rec["strParentheses"] == "y") $authorship = "($authorship)";
        $authorship = self::remove_quotes($authorship);
        if($rec["bytValidity"] != "valid")
        {
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
        $taxon->genus                       = (string) $genus;
        $this->taxa[$taxon->taxonID] = $taxon;
        return $link;
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
        $mr->furtherInformationURL = "";
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
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }

    function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::get_remote_file($this->zip_path, array('timeout' => 172800, 'download_attempts' => 2)))
        {
            $parts = pathinfo($this->zip_path);
            $temp_file_path = $this->TEMP_FILE_PATH . "/" . $parts["basename"];
            $TMP = fopen($temp_file_path, "w");
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("tar -xzf $temp_file_path -C $this->TEMP_FILE_PATH");
            if(!file_exists($this->TEMP_FILE_PATH . "/species.txt")) 
            {
                $this->TEMP_FILE_PATH = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($this->TEMP_FILE_PATH . "/species.txt")) return;
            }
            $this->text_path["species"]          = $this->TEMP_FILE_PATH . "/species.txt";
            $this->text_path["specimen"]         = $this->TEMP_FILE_PATH . "/specimen.txt";
            $this->text_path["distribution"]     = $this->TEMP_FILE_PATH . "/distribution.txt";
            $this->text_path["specimen_images"]  = $this->TEMP_FILE_PATH . "/specimen_images_v2.txt";
            $this->text_path["species_images"]   = $this->TEMP_FILE_PATH . "/species_images.txt";
            $this->text_path["references"]       = $this->TEMP_FILE_PATH . "/references.txt";
            $this->text_path["image_references"] = $this->TEMP_FILE_PATH . "/image_references.txt";
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return;
        }
    }

    private function get_rank($string)
    {
        switch($string)
        {
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
        }
    }

    private function process_specimen_images($link, $func)
    {
        $fields = array("lngSpecies_ID", "lngImage_ID", "lngDocuTypeSpecimen", "lngPrep_ID", "lngSpecimen_ID", "lngImgType_ID", "blnPermission");
        $texts = $func->make_array($this->text_path["specimen_images"], $fields);
        $ref_ids = array();
        $agent_ids = array();
        $investigate = 0;
        foreach($texts as $rec)
        {
            if($rec["lngImage_ID"] == "lngImage_ID") continue;
            if($rec["lngImage_ID"] == "lngImage_ID" || $rec["blnPermission"] == "FALSE") continue;
            $description = "";
            if($rec["lngDocuTypeSpecimen"]) 
            {
                $description .= $rec["lngDocuTypeSpecimen"];
                if($rec["lngPrep_ID"]) $description .= ", " . $rec["lngPrep_ID"];
            }
            else
            {
                if($rec["lngPrep_ID"]) $description .= $rec["lngPrep_ID"];
            }
            $rec["lngImage_ID"] = self::remove_quotes($rec["lngImage_ID"]);
            $media_url = self::get_image_path($rec["lngImage_ID"], $rec["lngImgType_ID"]);
            if(!$media_url) continue;
            $rec["lngImage_ID"] = str_ireplace(" ", "_", $rec["lngImage_ID"]);
            $media_id = $rec["lngImage_ID"];
            if($rec["lngImage_ID"])
            {
                $rec["lngSpecies_ID"] = self::remove_quotes($rec["lngSpecies_ID"]);
                if($rec["lngSpecies_ID"] = trim(Functions::canonical_form($rec["lngSpecies_ID"])))
                {
                    if($taxon_id = @$link[$rec["lngSpecies_ID"]]) self::get_images($description, $taxon_id, $media_id, $media_url, $ref_ids, $agent_ids);
                    else
                    {
                        if($rec["lngSpecies_ID"] != "lngSpecies_ID" && !in_array($rec["lngSpecies_ID"], $this->invalid_taxa))
                        {
                            $investigate++;
                            echo("\n investigate: specimen images: [$taxon_id] --- taxon = " . $rec["lngSpecies_ID"] . "\n");
                        }
                    }
                }
            }
        }
        echo "\n investigate: $investigate \n";
    }

}
?>