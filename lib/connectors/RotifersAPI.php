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
        $this->EOL = 'http://eol.org/schema/eol_info_items.xml';
        $this->zip_path = "http://localhost/~eolit/rotifers.zip";
        $this->zip_path = "https://dl.dropboxusercontent.com/u/7597512/Rotifers/rotifers.zip";
        $this->text_path = array();
        $this->image_path = "http://www.rotifera.hausdernatur.at/TestRWC/Rotifer_data/images";
        $this->image_path =                 "http://89.26.108.66/TestRWC/Rotifer_data/images";
        $this->debug_count = 0;
    }

    function get_all_taxa()
    {
        self::process_text_files();
        // remove temp dir
        $path = $this->text_path["species"];
        $parts = pathinfo($path);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
        echo "\n total text scan: $this->debug_count \n";
    }

    private function process_text_files()
    {
        self::load_zip_contents();
        print_r($this->text_path);
        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();
        $fields = array("lngSpecies_ID", "lngRank_ID", "bytValidity", "bytAvailability", "lngGenus_ID", "lngSubGenus_ID", "strSpecies", "lngInfraRank_ID", "strSubSpeciesInfra", "lngAuthor_ID", "intYear", "strParentheses", "strIUI", "strOrigSpell", "strOrigComb");
        $taxa = $func->make_array($this->text_path["species"], $fields, "", array());
        $link = array();
        foreach($taxa as $rec)
        {
            $sciname = $rec["strOrigComb"];
            echo "\n $sciname";
            $link = $this->create_instances_from_taxon_object($rec, array(), $link);
        }
        echo "\n\n total rows: " . count($taxa);
        echo "\n\n link: " . count($link);
        echo "\n";
        print_r($link);
        self::process_specimen($link, $func);
        self::process_distribution($link, $func);
        self::process_specimen_images($link, $func);
        self::process_species_images($link, $func);
        $this->create_archive();
    }

    private function process_species_images($link, $func)
    {
        $fields = array("lngSpecies_ID", "lngImage_ID", "lngImgType_ID", "blnPermission");
        $texts = $func->make_array($this->text_path["species_images"], $fields);
        $ref_ids = array();
        $agent_ids = array();
        $investigate = -1;
        /*
        [lngSpecies_ID] => "Aspelta curvidactyla B?rzi??, 1949"
        [lngImage_ID] => Aspelta curvidactyla_UhegiynGol.jpg
        Taxon name: [tblSpeciesImage IngSpecies_ID]
        AccessURI: “somethingsomethingelseChristianwilltellusthedomain/”[tblSpeciesImage IngImage_ID]
        */
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
                $rec["lngSpecies_ID"] = trim(Functions::canonical_form($rec["lngSpecies_ID"]));
                if($taxon_id = @$link[$rec["lngSpecies_ID"]]) self::get_images($description, $taxon_id, $media_id."_$taxon_id", $media_url, $ref_ids, $agent_ids);
                else
                {
                    $investigate++;
                    if($rec["lngSpecies_ID"] != "lngSpecies_ID") echo("\n investigate: species images: [$taxon_id] --- taxon = " . $rec["lngSpecies_ID"] . "\n");
                }
            }
        }
        echo "\n investigate: $investigate \n";
    }

    private function process_specimen_images($link, $func)
    {
        $fields = array("lngSpecies_ID", "lngImage_ID", "lngDocuTypeSpecimen", "lngPrep_ID", "lngSpecimen_ID", "lngImgType_ID", "blnPermission");
        $texts = $func->make_array($this->text_path["specimen_images"], $fields);
        $ref_ids = array();
        $agent_ids = array();
        $investigate = -1;
        /*
        [lngSpecies_ID] => "Aspelta psitta Harring et Myers, 1928"
        [lngImage_ID] => "Aspelta psitta Harring & Myers, 1928 [Donner, 1972].jpg"
        [lngDocuTypeSpecimen] => 
        [lngPrep_ID] => 
        [lngSpecimen_ID] => 
        Taxon name: [tblSpecimenImage IngSpecimen_ID->tblSpecimen IngSpecies_ID]
        AccessURI: “somethingsomethingChristianwilltellusthedomain/”[ tblSpecimenImage IngImage_ID]
        Description: [tblSpecimenImage IngSpecimen_ID->[tblSpecimen IngDocuTypeSpecimen, IngPrep_ID]
        */
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
            if($rec["lngSpecimen_ID"]) $media_id .= "_" . $rec["lngSpecimen_ID"];
            if($rec["lngImage_ID"])
            {
                $rec["lngSpecies_ID"] = self::remove_quotes($rec["lngSpecies_ID"]);
                $rec["lngSpecies_ID"] = trim(Functions::canonical_form($rec["lngSpecies_ID"]));
                
                if($taxon_id = @$link[$rec["lngSpecies_ID"]]) self::get_images($description, $taxon_id, $media_id, $media_url, $ref_ids, $agent_ids);
                else
                {
                    $investigate++;
                    if($rec["lngSpecies_ID"] != "lngSpecies_ID") echo("\n investigate: specimen images: [$taxon_id] --- taxon = " . $rec["lngSpecies_ID"] . "\n");
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
                $folder = "map"; // ?
                break;
            default:
                echo("\n\n investigate: no folder: [$filename] -- [$image_type] \n ");
        }
        $image_path = $this->image_path . "/$folder/_full-size/$filename";
        
        // remove text scan images
        if(preg_match("/_text(.*?).jpg/ims", $filename, $arr) || preg_match("/_text(.*?)].jpg/ims", $filename, $arr))
        {
            echo "\n $image_path \n";
            $this->debug_count++;
            return false;
        }
        return $image_path;
    }

    private function get_images($description, $taxon_id, $media_id, $media_url, $reference_ids, $agent_ids)
    {
        if(in_array($media_id, $this->media_ids)) return;
        $this->media_ids[] = $media_id;
        $mr = new \eol_schema\MediaResource();
        if($reference_ids)  $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids)      $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID        = (string) $taxon_id;
        $mr->identifier     = (string) $media_id;
        $mr->type           = "http://purl.org/dc/dcmitype/StillImage";
        $mr->language       = 'en';
        $mr->format         = Functions::get_mimetype($media_url);
        // $mr->furtherInformationURL = "";
        $mr->CVterm         = "";
        $mr->Owner          = "";
        $mr->rights         = "";
        $mr->title          = "";
        $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc/3.0/";
        $mr->audience       = 'Everyone';
        $mr->description    = (string) $description;
        // $mr->subtype        = "Map";
        $mr->accessURI      = $media_url;
        $this->archive_builder->write_object_to_file($mr);
    }

    private function process_distribution($link, $func)
    {
        $fields = array("lngSpeciesSenior_ID", "lngBiogeo_ID", "txtComments"); // lngSpeciesSenior_ID is the taxon
        $texts = $func->make_array($this->text_path["distribution"], $fields);
        $ref_ids = array();
        $agent_ids = array();
        $investigate = -1;
        $taxa = array();
        foreach($texts as $rec)
        {
            $description = "";
            if($rec["lngBiogeo_ID"]) 
            {
                $description .= $rec["lngBiogeo_ID"];
                if($rec["txtComments"]) $description .= ", " . $rec["txtComments"];
            }
            else
            {
                if($rec["txtComments"]) $description .= $rec["txtComments"];
            }
            if($description)
            {
                $rec["lngSpeciesSenior_ID"] = self::remove_quotes($rec["lngSpeciesSenior_ID"]);
                $rec["lngSpeciesSenior_ID"] = trim(Functions::canonical_form($rec["lngSpeciesSenior_ID"]));
                if($taxon_id = @$link[$rec["lngSpeciesSenior_ID"]]) 
                {
                    if($description != "- n.s. -") $taxa[$taxon_id]["distribution"][] = $description;
                    $taxa[$taxon_id]["lngBiogeo_ID"] = $rec["lngBiogeo_ID"];
                }
                else
                {
                    $investigate++;
                    if($rec["lngSpeciesSenior_ID"] != "lngSpeciesSenior_ID") echo("\n investigate: distribution: [$taxon_id] --- taxon = " . $rec["lngSpeciesSenior_ID"] . "\n");
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

    private function remove_quotes($string)
    {
        return str_ireplace('"', '', $string);
    }

    private function process_specimen($link, $func)
    {
        $fields = array("lngSpecimen_ID", "taxon", "strTypeStat", "strCatNr", "lngRepository_ID", "strRepName", "lngPersPrep_ID", "lngPersID2_ID", "lngPersID3_ID", "lngPrep_ID", "lngDocuTypeSpecimen", "lngPrepMeth_ID", "bytCountPrep", "lngEcolNote_ID", "txtPrepNotes", "txtPersNotes");
        $texts = $func->make_array($this->text_path["specimen"], $fields, "");
        $ref_ids = array();
        $agent_ids = array();
        $investigate = -1;
        foreach($texts as $rec)
        {
            $description = $rec["strTypeStat"];
            if($description) $description .= " for " . $rec["taxon"];
            else $description = "Non-type voucher specimen for " . $rec["taxon"];
            if($rec["strCatNr"]) $description .= "<br>Catalog number: " . $rec["strCatNr"];
            if($rec["strRepName"]) $description .= "<br>Collection: " . $rec["strRepName"];
            $prepared_by = "";
            if($rec["lngPersPrep_ID"]) 
            {
                $prepared_by .= "<br>Prepared by: " . $rec["lngPersPrep_ID"];
                /* commented later on per advise by partner
                if($rec["lngPersID2_ID"]) $prepared_by .= "; " . $rec["lngPersID2_ID"];
                if($rec["lngPersID3_ID"]) $prepared_by .= "; " . $rec["lngPersID3_ID"];
                */
            }
            $description .= self::remove_quotes($prepared_by);
            if($rec["lngPrep_ID"]) $description .= "<br>Sex/stage/structure: " . $rec["lngPrep_ID"];
            $preparation = "";
            if($rec["lngDocuTypeSpecimen"]) 
            {
                $preparation .= "<br>Preparation: " . $rec["lngDocuTypeSpecimen"];
                if($rec["lngPrepMeth_ID"]) $preparation .= "; " . $rec["lngPrepMeth_ID"];
            }
            $description .= $preparation;
            if($rec["bytCountPrep"]) $description .= "<br>Specimen Count: " . $rec["bytCountPrep"];
            $notes = "";
            if($rec["lngEcolNote_ID"]) 
            {
                $notes .= "<br>Notes: " . $rec["lngEcolNote_ID"];
                if($rec["txtPrepNotes"]) $notes .= "; " . $rec["txtPrepNotes"];
                if($rec["txtPersNotes"]) $notes .= "; " . $rec["txtPersNotes"];
            }
            $description .= $notes;
            $description = self::remove_quotes($description);
            if($description)
            {
                $rec["taxon"] = trim(Functions::canonical_form($rec["taxon"]));
                if($taxon_id = @$link[$rec["taxon"]]) self::get_texts($description, $taxon_id, '', '#TypeInformation', $rec["lngSpecimen_ID"], $ref_ids, $agent_ids);
                else
                {
                    $investigate++;
                    if($rec["taxon"] != "taxon") echo("\n investigate: specimen: {$taxon_id} --- taxon = " . $rec["taxon"] . "\n");
                }
            }
        }
        echo "\n investigate: $investigate \n";
    }

    function create_instances_from_taxon_object($rec, $reference_ids, $link)
    {
        /*
        [lngSpecies_ID] => 4134
        [lngRank_ID] => Sp
        [bytValidity] => valid
        [bytAvailability] => available
        [lngGenus_ID] => Notholca
        [lngSubGenus_ID] => 
        [strSpecies] => pacifica
        [lngInfraRank_ID] => 
        [strSubSpeciesInfra] => 
        [lngAuthor_ID] => Russell
        [intYear] => 1962
        [strParentheses] => y
        [strIUI] => 
        [strOrigSpell] => Pseudonotholca pacifica
        [strOrigComb] => Pseudonotholca pacifica
        */
        
        $sciname = $rec["lngGenus_ID"];
        if($rec["strSpecies"]) $sciname .= " " . $rec["strSpecies"];
        if($rec["lngInfraRank_ID"]) $sciname .= " " . $rec["lngInfraRank_ID"];
        if($rec["strSubSpeciesInfra"]) $sciname .= " " . $rec["strSubSpeciesInfra"];
        if(!$sciname) return $link;
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
        $authorship = str_replace('"', "", $authorship);
        $link[Functions::canonical_form($sciname)] = $taxon_id;

        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID                     = (string) $taxon_id; // take note, not TAXONID
        $taxon->taxonRank                   = (string) $rank;
        $taxon->scientificName              = (string) $sciname;
        $taxon->scientificNameAuthorship    = (string) $authorship;
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
        $description = utf8_encode($description);
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
        if($file_contents = Functions::get_remote_file($this->zip_path, DOWNLOAD_WAIT_TIME, 999999))
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
            $this->text_path["species"] = $this->TEMP_FILE_PATH . "/species.txt";
            $this->text_path["specimen"] = $this->TEMP_FILE_PATH . "/specimen.txt";
            $this->text_path["distribution"] = $this->TEMP_FILE_PATH . "/distribution.txt";
            $this->text_path["specimen_images"] = $this->TEMP_FILE_PATH . "/specimen_images.txt";
            $this->text_path["species_images"] = $this->TEMP_FILE_PATH . "/species_images.txt";
            $this->text_path["references"] = $this->TEMP_FILE_PATH . "/references.txt";
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

}
?>