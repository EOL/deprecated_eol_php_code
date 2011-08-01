<?php
namespace php_active_record;
class INBioAPI
{
    private static $MAPPINGS;

    public static function get_all_taxa()
    {
        self::$MAPPINGS = self::assign_mappings();
        $all_taxa = array();
        $used_collection_ids = array();

        require_vendor('eol_content_schema');
        $harvester = new ContentArchiveHarvester(NULL, DOC_ROOT . "temp/dwca");
        $tables = $harvester->tables;
        $GLOBALS['fields'] = $tables["http://www.pliniancore.org/plic/pcfcore/pliniancore2.3"]->fields;

        $taxon_media = array();
        $media = $harvester->process_table('http://www.pliniancore.org/plic/pcfcore/PlinianCore2.3');
        foreach($media as $m) @$taxon_media[$m['http://rs.tdwg.org/dwc/terms/taxonID']] = $m;
        $taxa = $harvester->process_table('http://rs.tdwg.org/dwc/terms/Taxon');
        $i = 0;
        $total = sizeof($taxa);
        foreach($taxa as $taxon)
        {
            $i++;
            print "\n $i of $total";
            $taxon_id = @$taxon['http://rs.tdwg.org/dwc/terms/taxonID'];
            $taxon["id"] = $taxon_id;
            //if($taxon_id != 3870) continue; //debug
            $taxon["media"] = $taxon_media[$taxon_id];
            $arr = self::get_inbio_taxa($taxon, $used_collection_ids);
            $page_taxa               = $arr[0];
            $used_collection_ids     = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);
            //if($i >= 10) break; //debug
        }
        return $all_taxa;
    }

    public static function get_inbio_taxa($taxon, $used_collection_ids)
    {
        $response = self::parse_xml($taxon);//this will output the raw (but structured) array
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

    private function parse_xml($taxon)
    {
        $taxon_id = $taxon["id"];
        $arr_data = array();
        $arr_objects = array();
        if($taxon["media"])
        {
            foreach($GLOBALS['fields'] as $field)
            {
                $term = $field["term"];
                $mappings = self::$MAPPINGS;
                if(@$mappings[$term] && @$taxon["media"][$term])
                {
                    $arr_objects[] = self::prepare_text_objects($taxon, $term);
                }                
            }
            $arr_objects = self::prepare_image_objects($taxon, $arr_objects);
            $commonNames = self::get_commonNames($taxon);
            if(sizeof($arr_objects))
            {
                $arr_data[]=array(  "identifier"   => $taxon_id,
                                    "source"       => "",
                                    "kingdom"      => @$taxon["http://rs.tdwg.org/dwc/terms/kingdom"],
                                    "phylum"       => @$taxon["http://rs.tdwg.org/dwc/terms/phylum"],
                                    "class"        => @$taxon["http://rs.tdwg.org/dwc/terms/class"],
                                    "order"        => @$taxon["http://rs.tdwg.org/dwc/terms/order"],
                                    "family"       => @$taxon["http://rs.tdwg.org/dwc/terms/family"],
                                    "genus"        => @$taxon["http://rs.tdwg.org/dwc/terms/genus"],
                                    "sciname"      => @$taxon["http://rs.tdwg.org/dwc/terms/scientificName"],
                                    "taxon_refs"   => array(),
                                    "synonyms"     => array(),
                                    "commonNames"  => $commonNames,
                                    "data_objects" => $arr_objects
                                 );
            }
        }
        return $arr_data;
    }

    private function get_commonNames($taxon)
    {
        $commonNames = array();
        if($taxon["media"]["http://www.pliniancore.org/plic/pcfcore/commonNames"])
        {
            $names = explode(",", $taxon["media"]["http://www.pliniancore.org/plic/pcfcore/commonNames"]);
            foreach($names as $name) $commonNames[] = array("name" => trim($name), "language" => "");
        }
        return $commonNames;
    }

    private function prepare_image_objects($taxon, $arr_objects)
    {
        for($i = 1; $i <= 3; $i++)
        {
            $caption = "http://www.pliniancore.org/plic/pcfcore/captionImage" . $i;
            $url = "http://www.pliniancore.org/plic/pcfcore/urlImage" . $i;
            if(@$taxon["media"][$url])
            {
                $identifier = $taxon["media"][$url];
                $description = $taxon["media"][$caption];
                $mimeType   = "image/jpeg";
                $dataType   = "http://purl.org/dc/dcmitype/StillImage";
                $title      = "";
                $subject    = "";
                $mediaURL   = $taxon["media"][$url]; 
                $location   = "";
                $license    = "http://creativecommons.org/licenses/by-nc/3.0/";
                $rightsHolder = ""; //"Rightsholder to be supplied by InBio";
                $source     = "";
                $agent      = array();
                $refs       = array();
                $modified   = "";
                $language   = self::get_language($taxon["media"]["http://www.pliniancore.org/plic/pcfcore/language"]);
                $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $language);
            }
        }
        return $arr_objects;
    }

    private function get_language($lang)
    {
        if($lang == "Ingles") return "en";
        else return "es";
    }

    private function prepare_text_objects($taxon, $term)
    {
        $temp = parse_url($term);
        $description  = $taxon["media"][$term];
        $identifier   = $taxon["id"] . str_replace("/", "_", $temp["path"]);
        $mimeType     = "text/html";
        $dataType     = "http://purl.org/dc/dcmitype/Text";
        $title        = "";
        $subject      = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#" . self::$MAPPINGS[$term];
        $mediaURL     = "";
        $location     = "";
        $license      = "http://creativecommons.org/licenses/by-nc/3.0/";
        $rightsHolder = ""; //"Rightsholder to be supplied by InBio";
        $source       = "";
        $refs         = array();
        if($taxon["media"]["http://www.pliniancore.org/plic/pcfcore/speciesPublicationReference"]) $refs[] = array("fullReference" => $taxon["media"]["http://www.pliniancore.org/plic/pcfcore/speciesPublicationReference"]);
        if($taxon["media"]["http://www.pliniancore.org/plic/pcfcore/theReferences"]) $refs[] = array("fullReference" => $taxon["media"]["http://www.pliniancore.org/plic/pcfcore/theReferences"]);
        $agent = self::get_agents($taxon);
        $modified = "";
        $language = self::get_language($taxon["media"]["http://www.pliniancore.org/plic/pcfcore/language"]);
        return self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $language);
    }

    private function get_agents($taxon)
    {
        $agent = array();
        if($taxon["media"]["http://www.pliniancore.org/plic/pcfcore/creators"])
        {
            $creators = explode(",", $taxon["media"]["http://www.pliniancore.org/plic/pcfcore/creators"]);
            foreach($creators as $creator) $agent[] = array("role" => "creator", "homepage" => "", "fullName" => trim(strip_tags($creator)));
        }
        if($taxon["media"]["http://www.pliniancore.org/plic/pcfcore/contributors"])
        {
            $creators = explode(",", $taxon["media"]["http://www.pliniancore.org/plic/pcfcore/contributors"]);
            foreach($creators as $creator)
            {
                $creator = trim(strip_tags(str_replace("\\", "", $creator)));
                if($creator) $agent[] = array("role" => "source", "homepage" => "", "fullName" => $creator);
            }
        }
        return $agent;
    }

    private function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $language)
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
                      "language"     => $language
                    );
    }

    private function assign_mappings()
    {
        return array("http://www.pliniancore.org/plic/pcfcore/unstructuredNaturalHistory" => "Description",
                   "http://www.pliniancore.org/plic/pcfcore/scientificDescription"        => "DiagnosticDescription",
                   "http://www.pliniancore.org/plic/pcfcore/distribution"                 => "Distribution",
                   "http://www.pliniancore.org/plic/pcfcore/feeding"                      => "TrophicStrategy",
                   "http://www.pliniancore.org/plic/pcfcore/identificationKeys"           => "Key",
                   "http://www.pliniancore.org/plic/pcfcore/invasivenessData"             => "RiskStatement",
                   "http://www.pliniancore.org/plic/pcfcore/theUses"                      => "Uses",
                   "http://www.pliniancore.org/plic/pcfcore/migratoryData"                => "Migration",
                   "http://www.pliniancore.org/plic/pcfcore/ecologicalSignificance"       => "Ecology",
                   "http://www.pliniancore.org/plic/pcfcore/annualCycle"                  => "Cyclicity",
                   "http://www.pliniancore.org/plic/pcfcore/folklore"                     => "TaxonBiology",
                   "http://www.pliniancore.org/plic/pcfcore/populationBiology"            => "PopulationBiology",
                   "http://www.pliniancore.org/plic/pcfcore/threatStatus"                 => "ConservationStatus",
                   "http://www.pliniancore.org/plic/pcfcore/abstract"                     => "Description",
                   "http://www.pliniancore.org/plic/pcfcore/interactions"                 => "Associations",
                   "http://www.pliniancore.org/plic/pcfcore/territory"                    => "Behaviour",
                   "http://www.pliniancore.org/plic/pcfcore/behavior"                     => "Behaviour",
                   "http://www.pliniancore.org/plic/pcfcore/chromosomicNumberN"           => "Cytology",
                   "http://www.pliniancore.org/plic/pcfcore/reproduction"                 => "Reproduction",
                   "http://www.pliniancore.org/plic/pcfcore/theManagement"                => "Management",
                   "http://www.pliniancore.org/plic/pcfcore/endemicity"                   => "Distribution",
                   "http://www.pliniancore.org/plic/pcfcore/briefDescription"             => "TaxonBiology",
                   "http://www.pliniancore.org/plic/pcfcore/habit"                        => "Morphology",
                   "http://www.pliniancore.org/plic/pcfcore/legislation"                  => "Legislation",
                   "http://www.pliniancore.org/plic/pcfcore/habitat"                      => "Habitat",
                   "http://www.pliniancore.org/plic/pcfcore/lifeCycle"                    => "LifeCycle",
                   "http://www.pliniancore.org/plic/pcfcore/molecularData"                => "MolecularBiology",
                   "http://www.pliniancore.org/plic/pcfcore/typification"                 => "Distribution"
                   );
                   
                   //todo: ask SPG
                   /*
                   "http://www.pliniancore.org/plic/pcfcore/molecularData"                  => "EOL#SystematicsOrPhylogenetics", 
                   "http://www.pliniancore.org/plic/pcfcore/typification"                   => "EOL#TypeInformation", 
                   "http://www.pliniancore.org/plic/pcfcore/unstructedDocumentation"        => "EOL#Notes"
                   */
    }

}
?>