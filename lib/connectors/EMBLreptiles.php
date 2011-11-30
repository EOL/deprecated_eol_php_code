<?php
namespace php_active_record;
/* connector: 306 */
class EMBLreptiles
{
    const TAXON_SOURCE_URL = "http://reptile-database.reptarium.cz/species?";

    public static function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $source_data = DOC_ROOT . "update_resources/connectors/files/EMBL/reptile_DB_small.xls";
        $source_data = DOC_ROOT . "update_resources/connectors/files/EMBL/reptile_DB.xls";
        require_library('XLSParser');
        $parser = new XLSParser();
        $taxa = $parser->prepare_data($parser->convert_sheet_to_array($source_data), "single",
            "Species", "Species", "Author", "Year", "Family", "Comments", "Common_name", "Continent", "CurrentURL",
            "Distribution", "links", "References", "Subspecies", "Synonyms", "types", "URLcount");
        $i = 0;
        $total = sizeof($taxa);
        foreach($taxa as $taxon)
        {
            $i++;

            $sciname = @$taxon["Species"];
            if($taxon["Author"]) $sciname .= " " . $taxon["Author"];
            if($taxon["Year"]) $sciname .= " " . $taxon["Year"];
            $taxon["id"] = str_ireplace(" ", "_", $sciname);

            print "\n $i of $total";
            $arr = self::get_embl_taxa($taxon, $used_collection_ids);
            $page_taxa               = $arr[0];
            $used_collection_ids     = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);
        }
        return $all_taxa;
    }

    private function get_references($refs)
    {
        $refs = explode("\n", $refs);
        $references = array();
        foreach($refs as $ref)
        { 
            $ref = str_ireplace("--> ", "", $ref);
            $references[] = array("fullReference" => $ref);
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
            if(is_numeric(stripos($name, "E:"))) $lang = "en";
            elseif(is_numeric(stripos($name, "G:"))) $lang = "de";
            else $lang = "en";
            $name = str_ireplace("E: ", "", $name);
            $name = str_ireplace("G: ", "", $name);
            if($name) $vernacular_names[] = array("name" => $name, "language" => $lang);
        }
        return $vernacular_names;
    }

    public static function get_embl_taxa($taxon, $used_collection_ids)
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
        //if(sizeof($arr_objects)) We should get all taxa, not just those with text.
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
            $arr_data[]=array(  "identifier"   => "rdb_" . $taxon_id,
                                "source"       => self::TAXON_SOURCE_URL . "genus=$genus&species=$species",
                                "kingdom"      => "",
                                "phylum"       => "",
                                "class"        => "",
                                "order"        => "",
                                "family"       => $family,
                                "genus"        => $genus,
                                "sciname"      => $sciname,
                                "reference"    => self::get_references($taxon["References"]),
                                "synonyms"     => self::get_synonyms($taxon["Synonyms"]),
                                "commonNames"  => self::get_vernacular_names($taxon["Common_name"]),
                                "data_objects" => $arr_objects
                             );
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
        //$source        = $taxon["CurrentURL"];
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
                      "language"     => $language
                    );
    }

}
?>