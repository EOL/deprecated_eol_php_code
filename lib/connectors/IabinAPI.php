<?php
namespace php_active_record;
/* connector: 297
We received a Darwincore archive file from the partner. It has a pliniancore extension.
Partner hasn't yet hosted the DWC-A file.
Connector reads the archive file, assembles the data and generates the EOL XML.
*/
class IabinAPI
{
    private static $MAPPINGS;

    public static function get_all_taxa()
    {
        self::$MAPPINGS = self::assign_mappings();
        $all_taxa = array();
        $final_taxa = array();
        $used_collection_ids = array();
        $harvester = new ContentArchiveReader(NULL, DOC_ROOT . "temp/dwca_iabin");
        $tables = $harvester->tables;
        if(!($GLOBALS['fields'] = $tables["http://www.pliniancore.org/plic/pcfcore/pliniancore2.3"][0]->fields))
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        $images = self::get_images($harvester->process_row_type('http://rs.gbif.org/terms/1.0/image'));
        $references = self::get_references($harvester->process_row_type('http://rs.gbif.org/terms/1.0/reference'));
        $vernacular_names = self::get_vernacular_names($harvester->process_row_type('http://rs.gbif.org/terms/1.0/vernacularname'));
        $taxon_media = array();
        $media = $harvester->process_row_type('http://www.pliniancore.org/plic/pcfcore/PlinianCore2.3');
        foreach($media as $m)
        {
            $taxon_id = $m['http://rs.tdwg.org/dwc/terms/taxonID'];
            @$taxon_media[$taxon_id][] = $m;
        }
        $taxa = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon');
        $i = 0;
        $total = sizeof($taxa);
        foreach($taxa as $taxon)
        {
            $i++;
            debug(" $i of $total");
            $taxon_id = @$taxon['http://rs.tdwg.org/dwc/terms/taxonID'];
            $taxon["id"] = $taxon_id;
            $taxon["image"] = @$images[$taxon_id];
            $taxon["reference"] = @$references[$taxon_id];
            $taxon["vernacular_name"] = @$vernacular_names[$taxon_id];
            $taxon["media"] = $taxon_media[$taxon_id];
            $arr = self::get_iabin_taxa($taxon, $used_collection_ids);
            $page_taxa               = $arr[0];
            $used_collection_ids     = $arr[1];
            //do in batches to speed it up.
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            if(count($all_taxa) == 1000)
            {
                $final_taxa = array_merge($final_taxa, $all_taxa);
                $all_taxa = array();
            }
        }
        //last writes
        $final_taxa = array_merge($final_taxa, $all_taxa);
        return $final_taxa;
    }

    private function get_images($imagex)
    {
        $images = array();
        foreach($imagex as $image)
        {
            if($image['http://purl.org/dc/terms/identifier'])
            {
                $taxon_id = $image['http://rs.tdwg.org/dwc/terms/taxonID'];
                $images[$taxon_id]['url'][]           = $image['http://purl.org/dc/terms/identifier'];
                $images[$taxon_id]['caption'][]       = $image['http://purl.org/dc/terms/description'];
                $images[$taxon_id]['license'][]       = $image['http://purl.org/dc/terms/license'];
                $images[$taxon_id]['created'][]       = $image['http://purl.org/dc/terms/created'];
                /* not available for IABIN in images
                $images[$taxon_id]['publisher'][]     = $image['http://purl.org/dc/terms/publisher'];
                $images[$taxon_id]['creator'][]       = $image['http://purl.org/dc/terms/creator'];
                $images[$taxon_id]['rightsHolder'][]  = $image['http://purl.org/dc/terms/rightsHolder'];
                */
            }
        }
        return $images;
    }

    private function get_references($refs)
    {
        $references = array();
        foreach($refs as $ref)
        {
            if($ref['http://purl.org/dc/terms/bibliographicCitation']) $references[$ref['http://rs.tdwg.org/dwc/terms/taxonID']] = $ref['http://purl.org/dc/terms/bibliographicCitation'];
        }
        return $references;
    }

    private function get_vernacular_names($names)
    {
        $vernacular_names = array();
        foreach($names as $name)
        {
            $taxon_id = $name['http://rs.tdwg.org/dwc/terms/taxonID'];
            if($common_names = $name['http://rs.tdwg.org/dwc/terms/vernacularName']) //comma-separated common names
            {
                //remove parenthesis for this string "(Frank and Ramus"
                if($pos = stripos($common_names, "(Frank and Ramus")) $common_names = trim(substr($common_names, 0, $pos-1));
                $common_names = explode(",", $common_names); 
                foreach($common_names as $common_name)
                {
                    $vernacular_names[$taxon_id][] = array("name" => trim($common_name), "language" => self::get_language($name['http://purl.org/dc/terms/language']));
                }
            }
        }
        return $vernacular_names;
    }

    public static function get_iabin_taxa($taxon, $used_collection_ids)
    {
        $response = self::parse_xml($taxon);
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
        /* For IABIN, $taxon["media"] can be multiple, unlike with INBIO which is only 1. */
        $taxon_texts = $taxon["media"];
        if($taxon_texts)
        {
            $mappings = self::$MAPPINGS;
            foreach($taxon_texts as $taxon_text)
            {
                foreach($GLOBALS['fields'] as $field)
                {
                    $term = $field["term"];
                    if(@$mappings[$term] && @$taxon_text[$term])
                    {
                        if($object = self::prepare_text_objects($taxon, $taxon_text, $term)) $arr_objects[] = $object;
                    }
                }
            }
            $arr_objects = self::prepare_image_objects($taxon, $arr_objects);
            $refs = array();
            if($taxon["reference"]) $refs[] = array("fullReference" => $taxon["reference"]);
            if(sizeof($arr_objects))
            {
                $sciname = @$taxon["http://rs.tdwg.org/dwc/terms/scientificName"];
                if(@$taxon["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"]) $sciname .= " " . $taxon["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"];
                $arr_data[]=array(  "identifier"   => $taxon_id,
                                    "source"       => "",
                                    "kingdom"      => @$taxon["http://rs.tdwg.org/dwc/terms/kingdom"],
                                    "phylum"       => @$taxon["http://rs.tdwg.org/dwc/terms/phylum"],
                                    "class"        => @$taxon["http://rs.tdwg.org/dwc/terms/class"],
                                    "order"        => @$taxon["http://rs.tdwg.org/dwc/terms/order"],
                                    "family"       => @$taxon["http://rs.tdwg.org/dwc/terms/family"],
                                    "genus"        => @$taxon["http://rs.tdwg.org/dwc/terms/genus"],
                                    "sciname"      => $sciname,
                                    "reference"    => $refs,
                                    "synonyms"     => array(),
                                    "commonNames"  => $taxon["vernacular_name"],
                                    "data_objects" => $arr_objects
                                 );
            }
        }
        return $arr_data;
    }

    private function prepare_image_objects($taxon, $arr_objects)
    {
        $image_urls = @$taxon["image"]['url'];
        $i = 0;
        if($image_urls)
        {
          foreach($image_urls as $image_url)
          {
            if($image_url)
            {
                $description    = @$taxon["image"]['caption'][$i];
                $mimeType       = "image/jpeg";
                $dataType       = "http://purl.org/dc/dcmitype/StillImage";
                $title          = "";
                $subject        = "";
                $mediaURL       = self::get_href_from_anchor_tag(@$taxon["image"]['url'][$i]);
                $identifier     = $mediaURL;
                $location       = "";
                $license_index  = @$taxon["image"]['license'][$i];
                $license_info["CC-Attribution-NonCommercial-ShareAlike"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
                $license        = @$license_info[$license_index];
                $rightsHolder   = @$taxon["http://purl.org/dc/terms/rightsHolder"];
                $created        = @$taxon["image"]['created'][$i];
                $source         = "";
                $agent          = array();
                if(@$taxon["image"]['creator'][$i]) $agent[] = array("role" => "photographer", "homepage" => "", "fullName" => @$taxon["image"]['creator'][$i]);
                if(@$taxon["image"]['publisher'][$i]) $agent[] = array("role" => "publisher", "homepage" => "", "fullName" => @$taxon["image"]['publisher'][$i]);
                $refs           = array();
                $modified       = "";
                $created        = "";
                $language       = "";
                $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $created, $language);
            }
            $i++;
          }
        }
        return $arr_objects;
    }

    private function get_language($lang)
    {
        if(in_array($lang, array("English", "en"))) return "en";
        elseif(in_array($lang, array("Español", "es"))) return "es";
        elseif(in_array($lang, array("Portugués", "pt"))) return "pt";
        else return "";
    }

    private function prepare_text_objects($taxon, $taxon_text, $term)
    {
        $temp = parse_url($term);
        $description   = trim($taxon_text[$term]);
        //to handle data problem from IABIN
        if(in_array($description, array(". . .", ". ."))) return array();
        $identifier    = $taxon["id"] . str_replace("/", "_", $temp["path"]);
        $mimeType      = "text/html";
        $dataType      = "http://purl.org/dc/dcmitype/Text";
        $title         = "";
        $subject       = self::$MAPPINGS[$term];
        $mediaURL      = "";
        $location      = "";
        $license       = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $rightsHolder  = @$taxon["http://purl.org/dc/terms/rightsHolder"];
        $source        = "";
        $refs          = array();
        $agent         = self::get_agents($taxon_text);
        $created       = $taxon_text["http://purl.org/dc/terms/created"];
        $modified      = "";
        $language      = self::get_language($taxon["http://purl.org/dc/terms/language"]);
        return self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $created, $language);
    }

    private function get_agents($taxon_text)
    {
        $agent = array();
        if($taxon_text["http://purl.org/dc/terms/creator"])
        {
            $creators = explode(",", $taxon_text["http://purl.org/dc/terms/creator"]);
            foreach($creators as $creator) $agent[] = array("role" => "author", "homepage" => "", "fullName" => trim(strip_tags($creator)));
        }
        if($taxon_text["http://purl.org/dc/elements/1.1/contributor"])
        {
            $contributors = explode(",", $taxon_text["http://purl.org/dc/elements/1.1/contributor"]);
            foreach($contributors as $contributor)
            {
                $contributor = trim(strip_tags(str_replace("\\", "", $contributor)));
                if($contributor) $agent[] = array("role" => "editor", "homepage" => "", "fullName" => $contributor);
            }
        }
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

    private function assign_mappings()
    {
        $SPM = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#";
        $EOL = "http://www.eol.org/voc/table_of_contents#";
        return array(  "http://www.pliniancore.org/plic/pcfcore/scientificDescription"        => $SPM . "DiagnosticDescription",
                       "http://www.pliniancore.org/plic/pcfcore/distribution"                 => $SPM . "Distribution",
                       "http://www.pliniancore.org/plic/pcfcore/feeding"                      => $SPM . "TrophicStrategy",
                       "http://www.pliniancore.org/plic/pcfcore/identificationKeys"           => $SPM . "Key",
                       "http://www.pliniancore.org/plic/pcfcore/invasivenessData"             => $SPM . "RiskStatement",
                       "http://www.pliniancore.org/plic/pcfcore/theUses"                      => $SPM . "Uses",
                       "http://www.pliniancore.org/plic/pcfcore/migratoryData"                => $SPM . "Migration",
                       "http://www.pliniancore.org/plic/pcfcore/ecologicalSignificance"       => $SPM . "Ecology",
                       "http://www.pliniancore.org/plic/pcfcore/annualCycle"                  => $SPM . "Cyclicity",
                       "http://www.pliniancore.org/plic/pcfcore/folklore"                     => $SPM . "TaxonBiology",
                       "http://www.pliniancore.org/plic/pcfcore/populationBiology"            => $SPM . "PopulationBiology",
                       "http://www.pliniancore.org/plic/pcfcore/threatStatus"                 => $SPM . "ConservationStatus",
                       "http://www.pliniancore.org/plic/pcfcore/abstract"                     => $SPM . "Description",
                       "http://www.pliniancore.org/plic/pcfcore/interactions"                 => $SPM . "Associations",
                       "http://www.pliniancore.org/plic/pcfcore/territory"                    => $SPM . "Behaviour",
                       "http://www.pliniancore.org/plic/pcfcore/behavior"                     => $SPM . "Behaviour",
                       "http://www.pliniancore.org/plic/pcfcore/chromosomicNumberN"           => $SPM . "Cytology",
                       "http://www.pliniancore.org/plic/pcfcore/reproduction"                 => $SPM . "Reproduction",
                       "http://www.pliniancore.org/plic/pcfcore/theManagement"                => $SPM . "Management",
                       "http://www.pliniancore.org/plic/pcfcore/endemicity"                   => $SPM . "Distribution",
                       "http://www.pliniancore.org/plic/pcfcore/briefDescription"             => $SPM . "TaxonBiology",
                       "http://www.pliniancore.org/plic/pcfcore/habit"                        => $SPM . "Morphology",
                       "http://www.pliniancore.org/plic/pcfcore/legislation"                  => $SPM . "Legislation",
                       "http://www.pliniancore.org/plic/pcfcore/habitat"                      => $SPM . "Habitat",
                       "http://www.pliniancore.org/plic/pcfcore/lifeCycle"                    => $SPM . "LifeCycle",
                       "http://www.pliniancore.org/plic/pcfcore/molecularData"                => $EOL . "SystematicsOrPhylogenetics",
                       "http://www.pliniancore.org/plic/pcfcore/typification"                 => $EOL . "TypeInformation",
                       "http://www.pliniancore.org/plic/pcfcore/unstructuredNaturalHistory"   => $EOL . "Notes",
                       "http://www.pliniancore.org/plic/pcfcore/unstructedDocumentation"      => $EOL . "Notes",
                       "http://www.pliniancore.org/plic/pcfcore/unstructuredDocumentation"    => $EOL . "Notes",
                       "http://iucn.org/terms/threatStatus"                                   => $SPM . "ConservationStatus",
                       "http://rs.tdwg.org/dwc/terms/habitat"                                 => $SPM . "Habitat",
                       "http://rs.tdwg.org/dwc/terms/establishmentMeans"                      => $SPM . "Distribution",
                       "http://purl.org/dc/terms/abstract"                                    => $SPM . "TaxonBiology"
                   );
    }

    private function get_string_between($str_left, $str_right, $string)
    {
        if(preg_match("/$str_left(.*?)$str_right/ims", $string, $matches)) return trim($matches[1]);
        return;
    }

    private function get_href_from_anchor_tag($str)
    {
        return self::get_string_between('href = \"', '\"', $str);
    }

}
?>