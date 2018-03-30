<?php
namespace php_active_record;
/* connector: [546]
Partner provides a big XML file. Connector parses it and generates a DWC-A image resource.
Connector excludes those images already included in the original/published BOLDS image resource.
*/
class BoldsImagesAPIv2
{
    function __construct($folder = false)
    {
        $this->max_images_per_taxon = 10;
        // $this->data_dump_url = "http://www.boldsystems.org/export/boldrecords.xml.gz"; --- obsolete, no longer being offered by partner.
        $this->data_dump_url = "http://localhost/cp/BOLDS/boldrecords.xml.gz"; // debug
        // $this->data_dump_url = "http://localhost/~eolit/cp/BOLDS/bolds_sample_data.xml.gz"; // debug

        $this->sourceURL = "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->do_ids = array();

        $this->old_bolds_image_ids_path = "http://opendata.eol.org/dataset/965c35fa-2a4a-487d-a92c-da86febbe6c0/resource/538d0ec1-ad74-4c92-9468-b71eee633e57/download/oldboldsimageids.txt"; // image IDs of the 1st BOLDS image resource
        $this->old_bolds_image_ids_path = "http://localhost/cp/BOLDS/old_BOLDS_image_ids.txt"; // debug
        $this->old_bolds_image_ids = array();
        $this->old_bolds_image_ids_count = 0;
        $this->info = array();

        // for generating the higher-level taxa list
        $this->MASTER_LIST = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_master_list.txt";
        $this->OLD_MASTER_LIST = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_master_list 2011 09 25.txt"; // debug
        // $this->OLD_MASTER_LIST = "https://opendata.eol.org/u/7597512/BOLDS/hl_master_list 2011 09 25.txt";
        
        $this->download_options = array('cache' => 1, 'timeout' => 2400, 'download_attempts' => 2);
        // $this->download_options['cache_path'] = "/Volumes/Eli blue/eol_cache/";
    }

    function get_all_taxa($data_dump_url = false)
    {
        if(!$data_dump_url) $data_dump_url = $this->data_dump_url;
        $path = self::download_and_extract_remote_file($data_dump_url);
        echo "\n xml file: [$path] \n";
        $reader = new \XMLReader();
        $reader->open($path);
        $i = 0;

        // retrive all image_ids from the first/original BOLDS images resource
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        $this->old_bolds_image_ids_path = Functions::save_remote_file_to_local($this->old_bolds_image_ids_path, $options);
        if(!($READ = Functions::file_open($this->old_bolds_image_ids_path, "r"))) return;
        $contents = fread($READ, filesize($this->old_bolds_image_ids_path));
        fclose($READ);
        $this->old_bolds_image_ids = json_decode($contents, true);
        echo "\n\n from text file: " . count($this->old_bolds_image_ids) . "\n\n";
        // end

        $i = 0;
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "record") {
                $string = $reader->readOuterXML();
                if($xml = simplexml_load_string($string)) {
                    $i++;
                    self::parse_record_element($xml);
                    if(($i % 10000) == 0) echo "\n".number_format($i)." ";
                }
                // debug - to process by batch
                // $i++;
                // if($i > 1000000)
                // {
                //     self::parse_record_element($xml);
                //     echo "\n $i. ";
                //     if($i > 1500000) break;
                // }
            }
        }
        $this->archive_builder->finalize(true);
        unlink($path);
        unlink($this->old_bolds_image_ids_path);
        echo "\n\n total old ids: " . $this->old_bolds_image_ids_count . "\n\n";
    }

    function download_and_extract_remote_file($file = false, $use_cache = false)
    {
        if(!$file) $file = $this->data_dump_url; // used when this function is called elsewhere
        $download_options = $this->download_options;
        $download_options['timeout'] = 172800;
        $download_options['file_extension'] = 'xml.gz';
        if($use_cache) $download_options['cache'] = 1; // this pertains to the generation of higher-level-taxa list
        // $download_options['cache'] = 0; // 0 only when developing //debug - comment in real operation
        $temp_path = Functions::save_remote_file_to_local($file, $download_options);
        echo "\n [$temp_path] \n";
        shell_exec("gzip -d " . $temp_path);
        return str_ireplace(".xml.gz", ".xml", $temp_path);
    }

    private function parse_record_element($rec)
    {
        $reference_ids = array();
        $ref_ids = array();
        $agent_ids = array();
        $rec = $this->create_instances_from_taxon_object($rec, $reference_ids);
        if($rec) self::get_images($rec, $ref_ids, $agent_ids);
    }

    private function get_object_agents($rec)
    {
        /*
        <media>
          <mediaID>1203947</mediaID>
          <image_link>http://www.boldsystems.org/pics/_w300/BLPDT/10-SRNP-108212_DHJ653087+1309969002.jpg</image_link>
          <photographer>Daniel H. Janzen</photographer>
          <licensing></licensing>
        </media>
        */
        $agent_ids = array();
        if(@$rec->photographer) {
            $agent = (string) trim($rec->photographer);
            if($agent != "") {
                $r = new \eol_schema\Agent();
                $r->term_name = $agent;
                $r->identifier = md5("$agent|photographer");
                $r->agentRole = "photographer";
                $r->term_homepage = "";
                $agent_ids[] = $r->identifier;
                if(!in_array($r->identifier, $this->resource_agent_ids)) {
                   $this->resource_agent_ids[] = $r->identifier;
                   $this->archive_builder->write_object_to_file($r);
                }
            }
        }
        return $agent_ids;
    }

    private function get_images($rec, $reference_ids = null, $agent_ids = null)
    {
        /*
        <specimen_multimedia>
          <media>
            <mediaID>1203947</mediaID>
            <image_link>http://www.boldsystems.org/pics/_w300/BLPDT/10-SRNP-108212_DHJ653087+1309969002.jpg</image_link>
            <photographer>Daniel H. Janzen</photographer>
            <licensing>
              <license>CreativeCommons - Attribution Non-Commercial Share-Alike</license>
              <licenseholder>Daniel H. Janzen</licenseholder>
              <licenseholder_institution>Guanacaste Dry Forest Conservation Fund</licenseholder_institution>
              <year>2010</year>
            </licensing>
          </media>
        </specimen_multimedia>
        */
        if(!@$rec->specimen_multimedia) return;
        $count = 0;
        foreach(@$rec->specimen_multimedia->media as $media) {
            if(trim(@$media->image_link) != "" && !is_numeric(stripos($media->licensing->license, "No Derivatives"))) {
                $SampleID = trim($rec->specimen_identifiers->sampleid);
                $ProcessID = trim($rec->processid);
                $Orientation = trim($media->caption);

                // start checking if image already exists from first/original images resource
                $old_id = $SampleID . "_" . $ProcessID . "_" . $Orientation;
                if(in_array($old_id, $this->old_bolds_image_ids)) {
                    // echo "\n [$old_id] Found an old ID, will ignore \n";
                    $this->old_bolds_image_ids_count++;
                    continue;
                }
                // end -

                $taxon_id = trim($rec->taxon_id);
                if(@$this->info[$taxon_id]) {
                    if($this->info[$taxon_id] == $this->max_images_per_taxon) {
                        // echo(" --- max $this->max_images_per_taxon images reached for [$taxon_id][$rec->sciname] -- ");
                        break;
                    }
                    $this->info[$taxon_id]++;
                }
                else $this->info[$taxon_id] = 1;

                $description = "";
                if(@$rec->specimen_identifiers->sampleid) $description .= "Sample ID = " . $SampleID . "<br>";
                if(@$rec->processid)                      $description .= "Process ID = " . $ProcessID . "<br>";
                if(@$media->caption)                      $description .= "Caption = " . $Orientation . "<br>";

                $rights = "";
                if(@$media->licensing->year) $rights = "Copyright ". $media->licensing->year;

                $rightsHolder = "";
                if(@$media->licensing->licenseholder) $rightsHolder = $media->licensing->licenseholder;
                if(@$media->licensing->licenseholder_institution) $rightsHolder .= ". " . $media->licensing->licenseholder_institution . ".";

                $agent_ids = self::get_object_agents($media);

                $mediaID = trim($media->mediaID);
                $license = self::get_license($media->licensing->license);
                if(trim($rec->taxon_id) != "" && $mediaID != "" && $license && Functions::get_mimetype($media->image_link) != "") {
                    if(in_array($mediaID, $this->do_ids)) {
                        echo("\n it should not pass here, just in case... \n");
                        continue;
                    }
                    else $this->do_ids[] = $mediaID;

                    $mr = new \eol_schema\MediaResource();
                    if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
                    if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
                    $mr->taxonID                = (string) $rec->taxon_id;
                    $mr->identifier             = (string) $mediaID;
                    $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
                    $mr->language               = 'en';
                    $mr->format                 = (string) Functions::get_mimetype($media->image_link);
                    $mr->furtherInformationURL  = (string) $this->sourceURL . $rec->taxon_id;
                    $mr->description            = (string) $description;
                    $mr->UsageTerms             = (string) $license;
                    $mr->Owner                  = (string) $rightsHolder;
                    $mr->rights                 = (string) $rights;
                    $mr->accessURI              = (string) $media->image_link;
                    $mr->Rating                 = 2;
                    $this->archive_builder->write_object_to_file($mr);
                }
            }
        }
    }

    function create_instances_from_taxon_object($rec, $reference_ids)
    {
        $info = self::get_sciname($rec->taxonomy);
        $sciname  = $info["taxon_name"];
        $taxon_id = $info["taxon_id"];
        $rank     = $info["rank"];
        $ancestry = $info["ancestry"];

        $rec->taxon_id = $taxon_id;
        $rec->sciname = $sciname;

        if(trim($taxon_id) == "" || trim($sciname) == "") return false;
        if(in_array($taxon_id, $this->taxon_ids)) return $rec;
        else $this->taxon_ids[] = $taxon_id;

        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);

        $taxon->taxonID                     = (string) $taxon_id;
        $taxon->taxonRank                   = (string) $rank;
        $taxon->scientificName              = (string) $sciname;
        $taxon->kingdom                     = (string) @$ancestry->kingdom->taxon->name;
        $taxon->phylum                      = (string) @$ancestry->phylum->taxon->name;
        $taxon->class                       = (string) @$ancestry->class->taxon->name;
        $taxon->order                       = (string) @$ancestry->order->taxon->name;
        $taxon->family                      = (string) @$ancestry->family->taxon->name;
        $taxon->genus                       = (string) @$ancestry->genus->taxon->name;
        $taxon->taxonRemarks                = (string) @$rec->taxonomy->identification_provided_by ? "Taxonomy identification provided by " . $rec->taxonomy->identification_provided_by : '';
        
        if(!isset($this->taxa[$taxon_id])) {
            $this->taxa[$taxon_id] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
        return $rec;
    }

    private function get_license($license)
    {
        switch($license)
        {
            case "CreativeCommons - Attribution Non-Commercial Share-Alike" : return "http://creativecommons.org/licenses/by-nc-sa/3.0/"; break;
            case "CreativeCommons - Attribution"                            : return "http://creativecommons.org/licenses/by/3.0/"; break;
            case "CreativeCommons - Attribution Non-Commercial"             : return "http://creativecommons.org/licenses/by-nc/3.0/"; break;
            case "CreativeCommons - Attribution Share-Alike"                : return "http://creativecommons.org/licenses/by-sa/3.0/"; break;
            default:
            {
                echo("Invalid license: [$license]");
                return false;
                break;
            }
        }
    }

    private function get_sciname($name)
    {
        if(@$name->species->taxon->name != "") {
            $taxon_name = (string) $name->species->taxon->name;
            $taxon_id = (string) $name->species->taxon->taxon_id;
            $rank = "species";
        }
        elseif(@$name->genus->taxon->name != "") {
            $taxon_name = (string) $name->genus->taxon->name;
            $taxon_id = (string) $name->genus->taxon->taxon_id;
            $rank = "genus";
            $name->genus->taxon->name = "";
        }
        elseif(@$name->subfamily->taxon->name != "") {
            $taxon_name = (string) $name->subfamily->taxon->name;
            $taxon_id = (string) $name->subfamily->taxon->taxon_id;
            $rank = "subfamily";
            $name->subfamily->taxon->name = "";
        }
        elseif(@$name->family->taxon->name != "") {
            $taxon_name = (string) $name->family->taxon->name;
            $taxon_id = (string) $name->family->taxon->taxon_id;
            $rank = "family";
            $name->family->taxon->name = "";
        }
        elseif(@$name->order->taxon->name != "") {
            $taxon_name = (string) $name->order->taxon->name;
            $taxon_id = (string) $name->order->taxon->taxon_id;
            $rank = "order";
            $name->order->taxon->name = "";
        }
        elseif(@$name->class->taxon->name != "") {
            $taxon_name = (string) $name->class->taxon->name;
            $taxon_id = (string) $name->class->taxon->taxon_id;
            $rank = "class";
            $name->class->taxon->name = "";
        }
        elseif(@$name->phylum->taxon->name != "") {
            $taxon_name = (string) $name->phylum->taxon->name;
            $taxon_id = (string) $name->phylum->taxon->taxon_id;
            $rank = "phylum";
            $name->phylum->taxon->name = "";
        }
        elseif(@$name->kingdom->taxon->name != "") {
            $taxon_name = (string) $name->kingdom->taxon->name;
            $taxon_id = (string) $name->kingdom->taxon->taxon_id;
            $rank = "kingdom";
            $name->kingdom->taxon->name = "";
        }
        if(@$taxon_name) return array("taxon_name" => $taxon_name, "taxon_id" => $taxon_id, "rank" => $rank, "ancestry" => $name);
        else return false;
    }

    function generate_higher_level_taxa_list($data_dump_url = false)
    {
        if(!$data_dump_url) $data_dump_url = $this->data_dump_url;
        $path = self::download_and_extract_remote_file($data_dump_url, true); // true means it will use cache
        echo "\n xml file: [$path] \n";
        $reader = new \XMLReader();
        $reader->open($path);
        $i = 0;
        $sl_taxa = array(); // species-level taxa
        $hl_taxa = array(); // higher-level taxa
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "record") {
                $string = $reader->readOuterXML();
                $xml = simplexml_load_string($string);
                //for species-level taxa
                if($sciname = @$xml->taxonomy->species->taxon->name) {
                   $sl_taxa["$sciname"]["rank"] = "species";
                   $sl_taxa["$sciname"]["taxon_id"] = $xml->taxonomy->species->taxon->taxon_id;
                }
                //for higher-level taxa
                if($sciname = @$xml->taxonomy->genus->taxon->name) {
                    $hl_taxa["$sciname"]["rank"] = "genus"; 
                    $hl_taxa["$sciname"]["taxon_id"] = $xml->taxonomy->genus->taxon->taxon_id;
                }
                if($sciname = @$xml->taxonomy->subfamily->taxon->name) {
                    $hl_taxa["$sciname"]["rank"] = "subfamily";
                    $hl_taxa["$sciname"]["taxon_id"] = $xml->taxonomy->subfamily->taxon->taxon_id;
                }
                if($sciname = @$xml->taxonomy->family->taxon->name) {
                    $hl_taxa["$sciname"]["rank"] = "family";
                    $hl_taxa["$sciname"]["taxon_id"] = $xml->taxonomy->family->taxon->taxon_id;
                }
                if($sciname = @$xml->taxonomy->order->taxon->name) {
                    $hl_taxa["$sciname"]["rank"] = "order";
                    $hl_taxa["$sciname"]["taxon_id"] = $xml->taxonomy->order->taxon->taxon_id;
                }
                if($sciname = @$xml->taxonomy->class->taxon->name) {
                    $hl_taxa["$sciname"]["rank"] = "class";
                    $hl_taxa["$sciname"]["taxon_id"] = $xml->taxonomy->class->taxon->taxon_id;
                }
                if($sciname = @$xml->taxonomy->phylum->taxon->name) {
                    $hl_taxa["$sciname"]["rank"] = "phylum";
                    $hl_taxa["$sciname"]["taxon_id"] = $xml->taxonomy->phylum->taxon->taxon_id;
                }
                if($sciname = @$xml->taxonomy->kingdom->taxon->name) {
                    $hl_taxa["$sciname"]["rank"] = "kingdom";
                    $hl_taxa["$sciname"]["taxon_id"] = $xml->taxonomy->kingdom->taxon->taxon_id;
                }
            }
        }
        unlink($path);
        ksort($hl_taxa);
        ksort($sl_taxa);
        echo "\n\n higher-level taxa count: " . count($hl_taxa);
        $i = 0;
        if(!($fn = Functions::file_open($this->MASTER_LIST, "w"))) return;
        foreach($hl_taxa as $key => $value) {
            $i++; echo "\n $i. $key -- $value[rank] $value[taxon_id]";
            fwrite($fn, $value["taxon_id"] . "\t" . $key . "\t" . $value["rank"] . "\n");
        }
        echo "\n\n species-level taxa count: " . count($sl_taxa);
        echo "\n higher-level taxa count: " . count($hl_taxa);
        fclose($fn);
        self::reconcile_with_old_master_list($hl_taxa); // debug - uncomment in normal operation, comment when developing for quick processing
    }

    private function reconcile_with_old_master_list($hl_taxa)
    {
        if(!($write = Functions::file_open($this->MASTER_LIST, "a"))) return;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        $temp_filepath = Functions::save_remote_file_to_local($this->OLD_MASTER_LIST, $options);
        foreach(new FileIterator($temp_filepath, true) as $line_number => $line) // 'true' will auto delete temp_filepath
        {
            $split = explode("\t", trim($line));
            if($sciname = @$split[1]) {
                $id = $split[0];
                if(!isset($hl_taxa[$sciname]["taxon_id"])) {
                    // echo "\n to be added: [$sciname - $id]";
                    fwrite($write, $id . "\t" . $sciname . "\t" . "" . "\n");
                }
            }
        }
        fclose($write);
    }

}
?>