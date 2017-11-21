<?php
namespace php_active_record;
/* connector: 306 
Data tickets: https://jira.eol.org/browse/COLLAB-377
              https://jira.eol.org/browse/DATA-714
SPG purchased the database. It was downloaded from: http://dl.dropbox.com/u/6379405/Reptile_Database_2011_Win_kj56.zip
It is a desktop windows application that has export feautures. We then exported the data we needed as spreadsheets (.xls).
 update_resources/connectors/files/EMBL/reptile_DB.xls
 update_resources/connectors/files/EMBL/references.xls
 update_resources/connectors/files/EMBL/taxon_references.xls
The connector then reads these spreadsheets and generates the EOL XML.
*/
class EMBLreptiles
{
    const TAXON_SOURCE_URL = "http://reptile-database.reptarium.cz/species?";
    public function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();

        /* old implementation
        $source_data = DOC_ROOT . "update_resources/connectors/files/EMBL/reptile_DB_small.xls"; // use this when testing
        $source_data = DOC_ROOT . "update_resources/connectors/files/EMBL/reptile_DB.xls";
        $references  = DOC_ROOT . "update_resources/connectors/files/EMBL/references.xls";
        $taxon_ref   = DOC_ROOT . "update_resources/connectors/files/EMBL/taxon_references.xls";
        */
        
        $temp = "http://localhost/cp/EMBL/reptile_DB_small.xls"; // use this when testing
        $temp = "http://localhost/cp/EMBL/reptile_DB.xls";       // use this in normal operation
        // $temp = "https://dl.dropboxusercontent.com/u/5763406/resources/306/reptile_DB.xls";
        $source_data = Functions::save_remote_file_to_local($temp, array("file_extension" => "xls", "cache" => 1, "expire_seconds" => false, "timeout" => 60*60)); //1hr timeout

        /* seems not being used at the moment
        $temp = "http://localhost/cp/EMBL/references.xls";
        $references = Functions::save_remote_file_to_local($temp, array("file_extension" => "xls", "cache" => 1));
        $temp = "http://localhost/cp/EMBL/taxon_references.xls";
        $taxon_ref_xls = Functions::save_remote_file_to_local($temp, array("file_extension" => "xls", "cache" => 1));
        */
        
        require_library('XLSParser');
        $parser = new XLSParser();
        $taxa = $parser->prepare_data($parser->convert_sheet_to_array($source_data), "single",
            "Species", "Species", "Author", "Year", "Family", "Comments", "Common_name", "Continent", "CurrentURL",
            "Distribution", "links", "References", "Subspecies", "Synonyms", "types", "URLcount");

        /* seems not being used at the moment
        $taxon_ref = $parser->prepare_data($parser->convert_sheet_to_array($taxon_ref_xls), "single", "Species", "RefNumbers");
        $GLOBALS['references'] = $parser->prepare_data($parser->convert_sheet_to_array($references), "single", "refnum", "author", "year", "title", "source");
        */
        $taxon_ref = array();
        $GLOBALS['references'] = array();

        $i = 0;
        $total = sizeof($taxa);
        foreach($taxa as $taxon)
        {
            $i++;
            $sciname = @$taxon["Species"];
            $taxon["ref_numbers"] = @$taxon_ref[$sciname]["RefNumbers"];
            if($taxon["Author"]) $sciname .= " " . $taxon["Author"];
            if($taxon["Year"]) $sciname .= " " . $taxon["Year"];
            $taxon["id"] = str_ireplace(" ", "_", $sciname);
            if(($i % 1000) == 0) print "\n $i of $total";
            $arr = self::get_embl_taxa($taxon, $used_collection_ids);
            $page_taxa               = $arr[0];
            $used_collection_ids     = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);
        }
        unlink($source_data);
        /* seems not being used at the moment
        unlink($references);
        unlink($taxon_ref_xls);
        */
        return $all_taxa;
    }

    private function get_references($refs)
    {
        $ref_ids = explode("\n", $refs);
        $references = array();
        foreach($ref_ids as $ref_id)
        { 
            if(@$GLOBALS['references'][$ref_id]['author'])
            {
                $ref = $GLOBALS['references'][$ref_id]['author'] . ". ";
                $ref .= $GLOBALS['references'][$ref_id]['year'] . ". ";
                $ref .= $GLOBALS['references'][$ref_id]['title'] . ". ";
                $ref .= $GLOBALS['references'][$ref_id]['source'] . ".";
                $ref = str_replace("..", ".", $ref);
                $references[] = array("fullReference" => $ref);
            }
        }
        return $references;
    }

    private function get_synonyms($names)
    {
        $names = explode("\n", $names);
        $synonyms = array();
        foreach($names as $name)
        {
            if($name) $synonyms[] = array("synonym" => $name, "relationship" => "synonym");
        }
        return $synonyms;
    }

    private function get_vernacular_names($names)
    {
        $names = explode("\n", $names);
        $vernacular_names = array();
        foreach($names as $name)
        {
            /* original; obsolete now
            if(is_numeric(stripos($name, "E:"))) $lang = "en";
            elseif(is_numeric(stripos($name, "G:"))) $lang = "de";
            else $lang = "en";
            $name = str_ireplace("E: ", "", $name);
            $name = str_ireplace("G: ", "", $name);
            $name = trim($name);
            */
            
            $namez = explode(",", $name);
            $namez = array_map('trim', $namez);
            foreach($namez as $name)
            {
                if($name)
                {
                    $parts = explode(": ", $name);
                    if($val = @$parts[1])
                    {
                        $name = trim($val);
                        $lang = trim($parts[0]);
                        $lang = self::get_lang_abb($lang);
                    }
                    else 
                    {
                        if(strpos(trim($parts[0]), ":")  !== false)//string is found
                        {
                            continue; //e.g. "dugesii:"
                        }
                        else
                        {
                            $name = trim($parts[0]);
                            $lang = "en";
                        }
                    }
                    $vernacular_names[] = array("name" => $name, "language" => $lang);
                }
            }
        }
        return $vernacular_names;
    }
    
    private function get_lang_abb($str)
    {
        switch ($str) {
            case "G": return "de"; break;
            case "V": return "vi"; break;
            case "Turkish": return "tr"; break;
            case "Thai": return "th"; break;
            case "Tamil": return "ta"; break;
            case "Spanish": return "es"; break;
            case "S": return "es"; break;
            case "NL": return "nl"; break;
            case "Nepali": return "ne"; break;
            case "Lao": return "lo"; break;
            case "J": return "ja"; break;
            case "I": return "it"; break;
            case "Hindi": return "hi"; break;
            case "German": return "de"; break;
            case "French": return "fr"; break;
            case "F": return "fr"; break;
            case "English": return "en"; break;
            case "E": return "en"; break;
            case "Dutch": return "nl"; break;
            case "Deutsch": return "de"; break;
            case "D": return "nl"; break;
            default: return "en";
        }
    }

    private function get_embl_taxa($taxon, $used_collection_ids)
    {
        $response = self::parse_data($taxon);
        $page_taxa = array();
        foreach($response as $rec)
        {            
            if(@$used_collection_ids[$rec["identifier"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["identifier"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    private function parse_data($taxon)
    {
        $taxon_id = $taxon["id"];
        $arr_data = array();
        $arr_objects = array();
        if($taxon["Distribution"]) $arr_objects[] = self::prepare_text_objects($taxon);
        if(1 == 1)
        {
            $sciname = @$taxon["Species"];
            if($taxon["Author"]) $sciname .= " " . $taxon["Author"];
            if($taxon["Year"]) $sciname .= " " . $taxon["Year"];
            $pos = stripos($taxon["Species"], " ");
            $genus = trim(substr($taxon["Species"], 0, $pos));
            $species = trim(substr($taxon["Species"], $pos, strlen($taxon["Species"])));
            $families = explode(",", $taxon["Family"]);
            $family = $families[0];
            $arr_data[] = array("identifier"   => "rdb_" . $taxon_id,
                                "source"       => self::TAXON_SOURCE_URL . "genus=$genus&species=$species",
                                "kingdom"      => "",
                                "phylum"       => "",
                                "class"        => "",
                                "order"        => "",
                                "family"       => $family,
                                "genus"        => $genus,
                                "sciname"      => $sciname,
                                "reference"    => self::get_references($taxon["ref_numbers"]),
                                "synonyms"     => self::get_synonyms($taxon["Synonyms"]),
                                "commonNames"  => self::get_vernacular_names($taxon["Common_name"]),
                                "data_objects" => $arr_objects);
        }
        return $arr_data;
    }

    private function prepare_text_objects($taxon)
    {
        $description = "";
        if($taxon["Continent"]) $description .= "Continent: " . $taxon["Continent"] . "<br>";
        if($taxon["Distribution"]) $description .= "Distribution: " . $taxon["Distribution"];
        $description = str_ireplace("Type locality:", "<br>Type locality:", $description);
        $identifier    = $taxon["id"] . "_distribution";
        $mimeType      = "text/html";
        $dataType      = "http://purl.org/dc/dcmitype/Text";
        $title         = "";
        $subject       = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
        $mediaURL      = "";
        $location      = "";
        $license       = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $rightsHolder  = "Peter Uetz";
        $source        = "";
        $refs          = array();
        $agent         = self::get_agents($taxon);
        $created       = "";
        $modified      = "";
        $language      = "en";
        return self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $created, $language);
    }

    private function get_agents($taxon)
    {
        $agent = array();
        $agent[] = array("role" => "editor", "homepage" => "http://www.reptile-database.org/", "fullName" => "Peter Uetz");
        return $agent;
    }

    private function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $created, $language)
    {
        if(!Functions::is_utf8($description)) $description = utf8_encode($description);
        return array( "identifier"   => $identifier,
                      "dataType"     => $dataType,
                      "mimeType"     => $mimeType,
                      "title"        => $title,
                      "source"       => $source,
                      "description"  => $description,
                      "mediaURL"     => $mediaURL,
                      "agent"        => $agent,
                      "license"      => $license,
                      "location"     => $location,
                      "rightsHolder" => $rightsHolder,
                      "reference"    => $refs,
                      "subject"      => $subject,
                      "modified"     => $modified,
                      "created"      => $created,
                      "language"     => $language);
    }

}
?>