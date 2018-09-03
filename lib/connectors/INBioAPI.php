<?php
namespace php_active_record;
/* connector: 276
We received a Darwincore archive file from the partner. It has a pliniancore extension.
Partner hasn't yet hosted the DWC-A file.
Connector downloads the archive file, extracts, reads the archive file, assembles the data and generates the EOL XML.
*/
class INBioAPI
{
    private static $MAPPINGS;
    const TAXON_SOURCE_URL = "http://darnis.inbio.ac.cr/ubis/FMPro?-DB=UBIPUB.fp3&-lay=WebAll&-error=norec.html&-Format=detail.html&-Op=eq&-Find=&id=";
    const SPM = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#";
    const EOL = "http://www.eol.org/voc/table_of_contents#";

    function get_all_taxa($dwca_file)
    {
        self::$MAPPINGS = self::assign_mappings();
        $all_taxa = array();
        $used_collection_ids = array();
        $paths = self::extract_archive_file($dwca_file, "meta.xml");
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
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
        foreach($media as $m) @$taxon_media[$m['http://rs.tdwg.org/dwc/terms/taxonID']] = $m;
        $taxa = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon');
        $i = 0;
        $total = sizeof($taxa);
        foreach($taxa as $taxon)
        {
            $i++;
            debug("$i of $total");
            $taxon_id = @$taxon['http://rs.tdwg.org/dwc/terms/taxonID'];
            $taxon["id"] = $taxon_id;
            $taxon["image"] = @$images[$taxon_id];
            $taxon["reference"] = @$references[$taxon_id];
            $taxon["vernacular_name"] = @$vernacular_names[$taxon_id];
            $taxon["media"] = $taxon_media[$taxon_id];
            $arr = self::get_inbio_taxa($taxon, $used_collection_ids);
            $page_taxa               = $arr[0];
            $used_collection_ids     = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);
        }
        // remove tmp dir
        if($temp_dir) shell_exec("rm -fr $temp_dir");
        return $all_taxa;
    }

    function extract_archive_file($dwca_file, $check_file_or_folder_name, $download_options = array('timeout' => 172800, 'expire_seconds' => 0), $force_extension = false) //e.g. with force_extension is NMNHTypeRecordAPI_v2.php
    {
        debug("Please wait, downloading resource document...");
        $path_parts = pathinfo($dwca_file);
        $filename = $path_parts['basename'];
        if($force_extension) $filename = "elix.".$force_extension; //you can just make-up a filename (elix) here and add the forced extension.
        $temp_dir = create_temp_dir() . "/";
        debug($temp_dir);
        if($file_contents = Functions::lookup_with_cache($dwca_file, $download_options))
        {
            $temp_file_path = $temp_dir . "" . $filename;
            debug("temp_dir: $temp_dir");
            debug("Extracting... $temp_file_path");
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            sleep(5);

            if($force_extension == 'zip')
            {
                shell_exec("unzip -ad $temp_dir $temp_file_path");
                $archive_path = str_ireplace(".zip", "", $temp_file_path);
            }
            else
            {
                if(preg_match("/^(.*)\.(tar.gz|tgz)$/", $dwca_file, $arr)) {
                    $cur_dir = getcwd();
                    chdir($temp_dir);
                    shell_exec("tar -zxvf $temp_file_path");
                    chdir($cur_dir);
                    $archive_path = str_ireplace(".tar.gz", "", $temp_file_path);
                    $archive_path = str_ireplace(".tgz", "", $temp_file_path);
                }
                elseif(preg_match("/^(.*)\.(gz|gzip)$/", $dwca_file, $arr)) {
                    shell_exec("gunzip -f $temp_file_path");
                    $archive_path = str_ireplace(".gz", "", $temp_file_path);
                }
                elseif(preg_match("/^(.*)\.(zip)$/", $dwca_file, $arr) || preg_match("/mcz_for_eol(.*?)/ims", $dwca_file, $arr)) {
                    shell_exec("unzip -ad $temp_dir $temp_file_path");
                    $archive_path = str_ireplace(".zip", "", $temp_file_path);
                } 
                else {
                    debug("-- archive not gzip or zip. [$dwca_file]");
                    return;
                }
            }

            debug("archive path: [" . $archive_path . "]");
        }
        else
        {
            debug("Connector terminated. Remote files are not ready.");
            return;
        }

        if    (file_exists($temp_dir . $check_file_or_folder_name))           return array('archive_path' => $temp_dir,     'temp_dir' => $temp_dir);
        elseif(file_exists($archive_path . "/" . $check_file_or_folder_name)) return array('archive_path' => $archive_path, 'temp_dir' => $temp_dir);
        elseif(file_exists($temp_dir ."dwca/". $check_file_or_folder_name))   return array('archive_path' => $temp_dir."dwca/", 'temp_dir' => $temp_dir); //for http://britishbryozoans.myspecies.info/eol-dwca.zip where it extracts to /dwca/ folder instead of usual /eol-dwca/.
        else
        {
            echo "\n1. ".$temp_dir . $check_file_or_folder_name."\n";
            echo "\n2. ".$archive_path . "/" . $check_file_or_folder_name."\n";
            echo "\n3. ".$temp_dir ."dwca/". $check_file_or_folder_name."\n";
            debug("Can't find check_file_or_folder_name [$check_file_or_folder_name].");
            recursive_rmdir($temp_dir);
            return false;
            // return array('archive_path' => $temp_dir, 'temp_dir' => $temp_dir);
        }
    }

    public static function assign_eol_subjects($xml_string)
    {
        if(!stripos($xml_string, "http://www.eol.org/voc/table_of_contents#")) return $xml_string;
        debug("this resource has http://www.eol.org/voc/table_of_contents# ");
        $xml = simplexml_load_string($xml_string);
        foreach($xml->taxon as $taxon)
        {
            foreach($taxon->dataObject as $dataObject)
            {
                $eol_subjects[] = self::EOL . "SystematicsOrPhylogenetics";
                $eol_subjects[] = self::EOL . "TypeInformation";
                $eol_subjects[] = self::EOL . "Notes";
                if(@$dataObject->subject)
                {
                    if(in_array($dataObject->subject, $eol_subjects))
                    {
                        $dataObject->addChild("additionalInformation", "");
                        $dataObject->additionalInformation->addChild("subject", $dataObject->subject);
                        if    ($dataObject->subject == self::EOL . "SystematicsOrPhylogenetics") $dataObject->subject = self::SPM . "Evolution";
                        elseif($dataObject->subject == self::EOL . "TypeInformation")            $dataObject->subject = self::SPM . "DiagnosticDescription";
                        elseif($dataObject->subject == self::EOL . "Notes")                      $dataObject->subject = self::SPM . "Description";
                    }
                }
            }
        }
        return $xml->asXML();
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
                $images[$taxon_id]['license'][]       = @$image['http://purl.org/dc/terms/license'];
                $images[$taxon_id]['publisher'][]     = @$image['http://purl.org/dc/terms/publisher'];
                $images[$taxon_id]['creator'][]       = @$image['http://purl.org/dc/terms/creator'];
                $images[$taxon_id]['created'][]       = @$image['http://purl.org/dc/terms/created'];
                $images[$taxon_id]['rightsHolder'][]  = @$image['http://purl.org/dc/terms/rightsHolder'];
            }
        }
        return $images;
    }

    private function get_references($refs)
    {
        $references = array();
        foreach($refs as $ref)
        {
            $taxon_id = $ref['http://rs.tdwg.org/dwc/terms/taxonID'];
            if($ref['http://purl.org/dc/terms/bibliographicCitation']) $references[$taxon_id] = self::parse_references($ref['http://purl.org/dc/terms/bibliographicCitation']);
        }
        return $references;
    }

    private function get_vernacular_names($names)
    {
        $vernacular_names = array();
        foreach($names as $name)
        {
            $taxon_id = $name['http://rs.tdwg.org/dwc/terms/taxonID'];
            if($name['http://rs.tdwg.org/dwc/terms/vernacularName'])
            {
                $vernacular_names[$taxon_id][] = array("name" => $name['http://rs.tdwg.org/dwc/terms/vernacularName'], "language" => self::get_language(@$name['http://purl.org/dc/terms/language']));
            }
        }
        return $vernacular_names;
    }

    public static function get_inbio_taxa($taxon, $used_collection_ids)
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
        if($taxon["media"])
        {
            foreach($GLOBALS['fields'] as $field)
            {
                $term = $field["term"];
                $mappings = self::$MAPPINGS;
                if(@$mappings[$term] && @$taxon["media"][$term]) $arr_objects[] = self::prepare_text_objects($taxon, $term);
            }
            $arr_objects = self::prepare_image_objects($taxon, $arr_objects);
            $refs = array();
            if($taxon["reference"]) $refs = $taxon["reference"];
            if(sizeof($arr_objects))
            {
                $sciname = @$taxon["http://rs.tdwg.org/dwc/terms/scientificName"];
                if(@$taxon["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"]) $sciname .= " " . $taxon["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"];
                $arr_data[]=array(  "identifier"   => $taxon_id,
                                    "source"       => self::TAXON_SOURCE_URL . $taxon_id,
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

    private function parse_references($refs)
    {
        if    (is_numeric(stripos($refs, "<p>")))  $refs = explode("<p>", $refs);
        elseif(is_numeric(stripos($refs, "</p>"))) $refs = explode("</p>", $refs);
        else $refs = explode("<p>", $refs);
        $references = array();
        foreach($refs as $ref) $references[] = array("fullReference" => $ref);
        return $references;
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
                $identifier     = @$taxon["image"]['url'][$i];
                $description    = @$taxon["image"]['caption'][$i];
                $mimeType       = "image/jpeg";
                $dataType       = "http://purl.org/dc/dcmitype/StillImage";
                $title          = "";
                $subject        = "";
                $mediaURL       = @$taxon["image"]['url'][$i]; 
                $location       = "";
                $license_index  = @$taxon["image"]['license'][$i];
                $license_info["CC-Attribution-NonCommercial-ShareAlike"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
                $license        = @$license_info[$license_index];
                $rightsHolder   = @$taxon["image"]['rightsHolder'][$i];
                $created        = @$taxon["image"]['created'][$i];
                $source         = self::TAXON_SOURCE_URL . $taxon["id"];
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
        if($lang == "Ingles") return "en";
        elseif($lang == "Español") return "es";
        else return "es";
    }

    private function prepare_text_objects($taxon, $term)
    {
        $temp = parse_url($term);
        $description   = $taxon["media"][$term];
        $identifier    = $taxon["id"] . str_replace("/", "_", $temp["path"]);
        $mimeType      = "text/html";
        $dataType      = "http://purl.org/dc/dcmitype/Text";
        $title         = "";
        $subject       = self::$MAPPINGS[$term];
        $mediaURL      = "";
        $location      = "";
        $license_index = @$taxon["http://purl.org/dc/terms/license"];
        $license_info["CC-Attribution-NonCommercial-ShareAlike"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $license       = @$license_info[$license_index];
        $rightsHolder  = @$taxon["http://purl.org/dc/terms/rightsHolder"];
        $source        = self::TAXON_SOURCE_URL . $taxon["id"];
        $refs          = array();
        $agent         = self::get_agents($taxon);
        $created       = $taxon["media"]["http://purl.org/dc/terms/created"];
        $modified      = "";
        $language      = self::get_language($taxon["http://purl.org/dc/terms/language"]);
        return self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $created, $language);
    }

    private function get_agents($taxon)
    {
        $agent = array();
        if($taxon["media"]["http://purl.org/dc/terms/creator"])
        {
            $creators = explode(",", $taxon["media"]["http://purl.org/dc/terms/creator"]);
            foreach($creators as $creator) $agent[] = array("role" => "author", "homepage" => "", "fullName" => trim(strip_tags($creator)));
        }
        if($taxon["media"]["http://purl.org/dc/elements/1.1/contributor"])
        {
            $contributors = explode(",", $taxon["media"]["http://purl.org/dc/elements/1.1/contributor"]);
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
        return array(  "http://www.pliniancore.org/plic/pcfcore/scientificDescription"        => self::SPM . "DiagnosticDescription",
                       "http://www.pliniancore.org/plic/pcfcore/distribution"                 => self::SPM . "Distribution",
                       "http://www.pliniancore.org/plic/pcfcore/feeding"                      => self::SPM . "TrophicStrategy",
                       "http://www.pliniancore.org/plic/pcfcore/identificationKeys"           => self::SPM . "Key",
                       "http://www.pliniancore.org/plic/pcfcore/invasivenessData"             => self::SPM . "RiskStatement",
                       "http://www.pliniancore.org/plic/pcfcore/theUses"                      => self::SPM . "Uses",
                       "http://www.pliniancore.org/plic/pcfcore/migratoryData"                => self::SPM . "Migration",
                       "http://www.pliniancore.org/plic/pcfcore/ecologicalSignificance"       => self::SPM . "Ecology",
                       "http://www.pliniancore.org/plic/pcfcore/annualCycle"                  => self::SPM . "Cyclicity",
                       "http://www.pliniancore.org/plic/pcfcore/folklore"                     => self::SPM . "TaxonBiology",
                       "http://www.pliniancore.org/plic/pcfcore/populationBiology"            => self::SPM . "PopulationBiology",
                       "http://www.pliniancore.org/plic/pcfcore/threatStatus"                 => self::SPM . "ConservationStatus",
                       "http://www.pliniancore.org/plic/pcfcore/abstract"                     => self::SPM . "Description",
                       "http://www.pliniancore.org/plic/pcfcore/interactions"                 => self::SPM . "Associations",
                       "http://www.pliniancore.org/plic/pcfcore/territory"                    => self::SPM . "Behaviour",
                       "http://www.pliniancore.org/plic/pcfcore/behavior"                     => self::SPM . "Behaviour",
                       "http://www.pliniancore.org/plic/pcfcore/chromosomicNumberN"           => self::SPM . "Cytology",
                       "http://www.pliniancore.org/plic/pcfcore/reproduction"                 => self::SPM . "Reproduction",
                       "http://www.pliniancore.org/plic/pcfcore/theManagement"                => self::SPM . "Management",
                       "http://www.pliniancore.org/plic/pcfcore/endemicity"                   => self::SPM . "Distribution",
                       "http://www.pliniancore.org/plic/pcfcore/briefDescription"             => self::SPM . "TaxonBiology",
                       "http://www.pliniancore.org/plic/pcfcore/habit"                        => self::SPM . "Morphology",
                       "http://www.pliniancore.org/plic/pcfcore/legislation"                  => self::SPM . "Legislation",
                       "http://www.pliniancore.org/plic/pcfcore/habitat"                      => self::SPM . "Habitat",
                       "http://www.pliniancore.org/plic/pcfcore/lifeCycle"                    => self::SPM . "LifeCycle",
                       "http://iucn.org/terms/threatStatus"                                   => self::SPM . "ConservationStatus",
                       "http://rs.tdwg.org/dwc/terms/habitat"                                 => self::SPM . "Habitat",
                       "http://rs.tdwg.org/dwc/terms/establishmentMeans"                      => self::SPM . "Distribution",
                       "http://purl.org/dc/terms/abstract"                                    => self::SPM . "TaxonBiology",
                       "http://www.pliniancore.org/plic/pcfcore/molecularData"                => self::EOL . "SystematicsOrPhylogenetics", 
                       "http://www.pliniancore.org/plic/pcfcore/typification"                 => self::EOL . "TypeInformation", 
                       "http://www.pliniancore.org/plic/pcfcore/unstructuredNaturalHistory"   => self::EOL . "Notes",
                       "http://www.pliniancore.org/plic/pcfcore/unstructedDocumentation"      => self::EOL . "Notes",
                       "http://www.pliniancore.org/plic/pcfcore/unstructuredDocumentation"    => self::EOL . "Notes"
                   );
    }

}
?>