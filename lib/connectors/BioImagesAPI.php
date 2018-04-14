<?php
namespace php_active_record;
/* connector: [168]
This connector will process the images objects from a remote tab-delimited text file hosted in DiscoverLife.
And will process the text objects from the original 168.xml retrieved from TheBeast.
*/

class BioImagesAPI
{
    function __construct($resource_id = false)
    {
        // for testing:
        // $this->data_dump_url = "http://localhost/~eolit/eol_php_code/update_resources/connectors/files/BioImages/Nov2012/Malcolm_Storey_images_test.TXT";
        // $this->data_dump_url = "http://localhost/~eolit/eol_php_code/update_resources/connectors/files/BioImages/Nov2012/Malcolm_Storey_images.TXT";
        // $this->original_resource = "http://localhost/~eolit/eol_php_code/applications/content_server/resources/168_Nov2010_small.xml.gz";
        // $this->original_resource = "http://localhost/~eolit/eol_php_code/applications/content_server/resources/168_Nov2010.xml.gz";
        // $this->original_resource = "http://opendata.eol.org/dataset/b0846bb0-7b81-40c7-8878-fb71f830ed17/resource/4444b435-c5e9-4d9e-b9ac-e6da8fd3fc55/download/168nov2010.xml.gz";

        // $this->data_dump_url = "http://pick14.pick.uga.edu/users/s/Storey,_Malcolm/Malcolm_Storey_images.TXT"; obsolete
        $this->data_dump_url = "http://www.discoverlife.org/users/s/Storey,_Malcolm/Malcolm_Storey_images.txt";
        $this->original_resource = "http://opendata.eol.org/dataset/b0846bb0-7b81-40c7-8878-fb71f830ed17/resource/4444b435-c5e9-4d9e-b9ac-e6da8fd3fc55/download/168nov2010.xml.gz";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->media_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
    }

    private function parse_record_element($row, $col)
    {
        $sciname = @$row[$col['Taxon']];
        $taxon_id = @$row[$col['NWB taxon id']];
        if($sciname && $taxon_id)
        {
            // echo "\n" . " - " . $sciname . " - " . $taxon_id;
            $reference_ids = array(); // no taxon references yet
            $ref_ids = array(); // no data_object references yet
            $agent_ids = self::get_object_agents($row, $col);
            $this->create_instances_from_taxon_object($row, $col, $reference_ids);
            self::get_images($row, $col, $ref_ids, $agent_ids);
        }
    }

    function get_all_taxa()
    {
        if($temp_filepath = Functions::save_remote_file_to_local($this->data_dump_url, array('cache' => 1, 'expire_seconds' => false, 'timeout' => 4800, 'download_attempts' => 2, 'delay_in_minutes' => 3)))
        {
            //start - remove bom --------------
            $contents = file_get_contents($temp_filepath);
            $FILE = Functions::file_open($temp_filepath, "w");
            fwrite($FILE, Functions::remove_utf8_bom($contents));
            fclose($FILE);
            //end - remove bom --------------
        
            $col = array();
            foreach(new FileIterator($temp_filepath, true) as $line_num => $line) // 'true' will auto delete temp_filepath
            {
                $line = trim($line);
                $row = explode("\t", $line);
                if($line_num == 0) {
                    foreach($row as $id => $value) {
                        // echo "\n $id -- $value";
                        $col[trim($value)] = $id;
                    }
                }
                else {
                    if(@$row[$col['Taxon']]) {
                        // echo "\n" . $row[$col['Taxon']];
                        self::parse_record_element($row, $col);
                    }
                }
            }
            //get text objects from the original resource (168.xml in Nov 2010)
            self::get_texts();
            // finalize the process and create the archive
            $this->create_archive();
        }
        else echo "\n Remote file not ready. Will terminate.";
    }

    private function get_texts()
    {
        require_library('connectors/BoldsImagesAPIv2');
        $func = new BoldsImagesAPIv2("");
        $path = $func->download_and_extract_remote_file($this->original_resource, true); //2nd param True meqns will use cache.

        if($xml = Functions::lookup_with_cache($path, array('timeout' => 172800, 'download_attempts' => 2, 'delay_in_minutes' => 3))) {
            $xml = simplexml_load_string($xml);
            $total = count($xml->taxon);
            $i = 0;
            foreach($xml->taxon as $t) {
                $i++;
                if(($i % 5000) == 0) echo "\n ".number_format($i)." of $total";
                
                $do_count = sizeof($t->dataObject);
                if($do_count > 0) {
                    $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
                    $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
                    $taxonID = (string)trim($t_dc->identifier);
                    $source = self::clean_str("http://www.bioimages.org.uk/html/" . str_replace(" ", "_", Functions::canonical_form($t_dwc->ScientificName)) . ".htm");

                    //---------------------------------
                    $taxon = new \eol_schema\Taxon();
                    $taxon->taxonID                     = $taxonID;
                    $taxon->scientificName              = $t_dwc->ScientificName;
                    $taxon->kingdom                     = $t_dwc->Kingdom;
                    $taxon->phylum                      = $t_dwc->Phylum;
                    $taxon->class                       = $t_dwc->Class;
                    $taxon->order                       = $t_dwc->Order;
                    $taxon->family                      = $t_dwc->Family;
                    $taxon->furtherInformationURL       = $source;
                    // echo "\n $taxon->taxonID - $taxon->scientificName [$source]";
                    if(isset($this->taxa[$taxonID])) {} //echo " -- already exists";
                    else $this->taxa[$taxonID] = $taxon;
                    //---------------------------------

                    foreach($t->dataObject as $do) {
                        if($do->dataType != "http://purl.org/dc/dcmitype/Text") continue;
                        $t_dc2      = $do->children("http://purl.org/dc/elements/1.1/");
                        $t_dcterms  = $do->children("http://purl.org/dc/terms/");

                        //---------------------------
                        $agent_ids = array();
                        $r = new \eol_schema\Agent();
                        $r->term_name = $do->agent;
                        $r->identifier = md5("$do->agent|$do->agent['role']");
                        $r->agentRole = $do->agent['role'];
                        $r->term_homepage = "http://www.bioimages.org.uk/index.htm";
                        $agent_ids[] = $r->identifier;
                        if(!in_array($r->identifier, $this->resource_agent_ids)) {
                           $this->resource_agent_ids[] = $r->identifier;
                           $this->archive_builder->write_object_to_file($r);
                        }
                        //---------------------------

                        $text_identifier = self::clean_str($t_dc2->identifier);
                        if(in_array($text_identifier, $this->media_ids)) continue;
                        else $this->media_ids[] = $text_identifier;
                        $mr = new \eol_schema\MediaResource();
                        if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
                        $mr->taxonID        = $taxonID;
                        $mr->identifier     = $text_identifier;
                        $mr->type           = (string)"http://purl.org/dc/dcmitype/Text"; //$do->dataType;
                        $mr->language       = "en";
                        $mr->format         = "text/html"; //$do->mimeType;
                        $mr->furtherInformationURL = (string)trim($source);

                        /* very long text objects, temporarily ignored */
                        $problematic_objects = array("http://www.bioimages.org.uk/html/Betula.htm",
                                                     "http://www.bioimages.org.uk/html/Broadleaved_trees.htm",
                                                     "http://www.bioimages.org.uk/html/Fagus.htm",
                                                     "http://www.bioimages.org.uk/html/Pinopsida.htm",
                                                     "http://www.bioimages.org.uk/html/Poaceae.htm",
                                                     "http://www.bioimages.org.uk/html/Quercus.htm",
                                                     "http://www.bioimages.org.uk/html/Salix.htm",
                                                     "http://www.bioimages.org.uk/html/Trees.htm");
                        if(in_array($mr->furtherInformationURL, $problematic_objects)) continue;

                        $mr->CVterm         = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations";
                        $mr->Owner          = "BioImages";
                        $mr->title          = "Associations";
                        $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
                        // $mr->audience       = 'Everyone';
                        // $mr->accessURI      = $source;

                        $description = (string) $t_dc2->description;
                        $description = trim(self::clean_str(utf8_encode($description)));
                        if(!$description) continue;
                        else
                        {
                            $mr->description = $description;
                            $this->archive_builder->write_object_to_file($mr);
                        }
                    }
                }
            }
        }
        else echo "\n Down: " . $this->original_resource;
        unlink($path);
        echo "\n temporary XML file removed: [$path]\n";
    }

    private function clean_str($str)
    {
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011", "	", ""), " ", trim($str));
        $str = str_ireplace(array("    "), " ", trim($str));
        $str = str_ireplace(array("   "), " ", trim($str));
        $str = str_ireplace(array("  "), " ", trim($str));
        return $str;
    }

    private function get_object_agents($row, $col)
    {
        $agent_ids = array();
        $agents_array = explode(",", $row[$col['Recorded/Collected by']]);
        foreach($agents_array as $agent) {
            $agent = (string)trim($agent);
            if(!$agent) continue;
            $r = new \eol_schema\Agent();
            $r->term_name = $agent;
            $r->identifier = md5("$agent|compiler");
            $r->agentRole = "compiler";
            $agent_ids[] = $r->identifier;
            if(!in_array($r->identifier, $this->resource_agent_ids)) {
               $this->resource_agent_ids[] = $r->identifier;
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }

    private function get_images($row, $col, $reference_ids, $agent_ids)
    {
        if(!$row[$col['DiscoverLife URL']]) return;
        $mr = new \eol_schema\MediaResource();
        if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID = "BI-taxon-" . $row[$col['NWB taxon id']];
        $mr->identifier = "BI-image-" . $row[$col['NWB picture reference id']];
        $mr->type = 'http://purl.org/dc/dcmitype/StillImage';
        $mr->language = 'en';
        $mr->format = 'image/jpeg';
        $mr->furtherInformationURL = $row[$col['BioImages image page']];
        $mr->CVterm = '';
        $mr->title = (string) self::clean_str(utf8_encode($row[$col['Title']]));
        $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
        $mr->accessURI = $row[$col['DiscoverLife URL']];
        $mr->creator = '';
        $mr->CreateDate = '';
        $mr->modified = '';
        $mr->Owner = '';
        $mr->publisher = '';
        $mr->audience = 'Everyone';
        $mr->bibliographicCitation = '';
        $description = '';
        //record details
        $description .= $row[$col['Longitude (deg)']] != "" ? "Longitude (deg): " . $row[$col['Longitude (deg)']] . ". " : "";
        $description .= $row[$col['Latitude (deg)']] != "" ? "Latitude (deg): " . $row[$col['Latitude (deg)']] . ". " : "";
        $description .= $row[$col['Longitude (deg/min)']] != "" ? "Longitude (deg/min): " . $row[$col['Longitude (deg/min)']] . ". " : "";
        $description .= $row[$col['Latitude (deg/min)']] != "" ? "Latitude (deg/min): " . $row[$col['Latitude (deg/min)']] . ". " : "";
        $description .= $row[$col['Vice county name']] != "" ? "Vice county name: " . $row[$col['Vice county name']] . ". " : "";
        $description .= $row[$col['Vice county no']] != "" ? "Vice county no.: " . $row[$col['Vice county no']] . ". " : "";
        $description .= $row[$col['Country']] != "" ? "Country: " . $row[$col['Country']] . ". " : "";
        $description .= $row[$col['Stage']] != "" ? "Stage: " . $row[$col['Stage']] . ". " : "";
        $description .= $row[$col['Associated species']] != "" ? "Associated species: " . $row[$col['Associated species']] . ". " : "";
        $description .= $row[$col['Identified by']] != "" ? "Identified by: " . $row[$col['Identified by']] . ". " : "";
        $description .= $row[$col['Confirmed by']] != "" ? "Confirmed by: " . $row[$col['Confirmed by']] . ". " : "";
        $description .= $row[$col['Photo summary']] != "" ? "Photo summary: " . $row[$col['Photo summary']] . ". " : "";
        $description .= $row[$col['Record summary']] != "" ? "Comment: " . $row[$col['Record summary']] . ". " : "";
        //image details
        $description .= $row[$col['Description']] != "" ? "Description: " . $row[$col['Description']] . ". " : "";
        $description .= $row[$col['Shows']] != "" ? "Shows: " . $row[$col['Shows']] . ". " : "";
        $description .= $row[$col['Detail to note']] != "" ? "Detail to note: " . $row[$col['Detail to note']] . ". " : "";
        $description .= $row[$col['Other taxa shown']] != "" ? "Other taxa shown: " . $row[$col['Other taxa shown']] . ". " : "";
        $description .= $row[$col['Category']] != "" ? "Category: " . $row[$col['Category']] . ". " : "";
        $description .= $row[$col['Image scaling']] != "" ? "Image scaling: " . $row[$col['Image scaling']] . ". " : "";
        $description .= $row[$col['Real world width(mm)']] != "" ? "Real world width(mm): " . $row[$col['Real world width(mm)']] . ". " : "";
        $description .= $row[$col['Background']] != "" ? "Background: " . $row[$col['Background']] . ". " : "";
        $description .= $row[$col['Photo date']] != "" ? "Photo date: " . $row[$col['Photo date']] . ". " : "";
        $description .= $row[$col['In situ/arranged/studio/specimen etc']] != "" ? "Where photo was taken: " . $row[$col['In situ/arranged/studio/specimen etc']] . ". " : "";
        // $description .= $row[$col['Specimen prep']] != "" ? "Specimen preparation: " . $row[$col['Specimen prep']] . ". " : "";
        // $description .= $row[$col['Stained with']] != "" ? "Stained with: " . $row[$col['Stained with']] . ". " : "";
        // $description .= $row[$col['Mounted in']] != "" ? "Mounting medium: " . $row[$col['Mounted in']] . ". " : "";
        // $description .= $row[$col['Lighting & focus']] != "" ? "Lighting: " . $row[$col['Lighting & focus']] . ". " : "";
        // $description .= $row[$col['Post processing']] != "" ? "Post processing: " . $row[$col['Post processing']] . ". " : "";
        $description .= $row[$col['Annotation']] != "" ? "Annotation: " . $row[$col['Annotation']] . ". " : "";
        $description .= $row[$col['Orientation']] != "" ? "Orientation: " . $row[$col['Orientation']] . ". " : "";
        $description .= $row[$col['Kit']] != "" ? "Photographic equipment used: " . $row[$col['Kit']] . ". " : "";
        
        $description = str_replace("....", ".", $description);
        $description = str_replace("...", ".", $description);
        $description = str_replace("..", ".", $description);
        
        $mr->description = utf8_encode($description);
        $this->archive_builder->write_object_to_file($mr);
    }

    private function create_instances_from_taxon_object($row, $col, $reference_ids)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon_id = (string)$row[$col['NWB taxon id']];
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID = "BI-taxon-" . $taxon_id;
        $rank = (string)trim($row[$col['Rank']]);
        if(self::valid_rank($rank)) $taxon->taxonRank = $rank;
        $scientificName = (string)utf8_encode($row[$col['Taxon']]);
        if(!$scientificName) return; //blank
        $taxon->scientificName              = $scientificName;
        $taxon->kingdom                     = (string)$row[$col['Kingdom']];
        $taxon->phylum                      = (string)$row[$col['Phylum']];
        $taxon->class                       = (string)$row[$col['Class']];
        $taxon->order                       = (string)$row[$col['Order']];
        $taxon->family                      = (string)$row[$col['Family']];
        $taxon->furtherInformationURL       = $row[$col['BioImages taxon page']];
        $taxon->acceptedNameUsage           = ''; //'Accepted name'
        $original_identification            = (string)$row[$col['Original ident']];
        $taxon->namePublishedIn             = $original_identification;
        $taxonRemarks = '';
        $taxonRemarks .= $original_identification != "" ? "Original identification: " . $original_identification . ". " : "";
        $taxonRemarks .= $rank != "" ? "Rank: " . $rank . ". " : "";
        $NBN_Code = (string)$row[$col['NBN Code']];
        $taxonRemarks .= $NBN_Code != "" ? "UK NBN (National Biodiversity Network) taxon code: " . $NBN_Code . ". " : "";
        $taxon->taxonRemarks                = $taxonRemarks;
        $taxon->infraspecificEpithet        = '';
        $this->taxa[$taxon->taxonID] = $taxon;
    }

    private function valid_rank($rank)
    {
        $unrecognized_ranks = array("", "Anamorphic Species", "Hybrid", "Informal", "Aggregate", "Aberration", "Form genus", "Breed", "Cultivar", "Section (Zoo.)", "Forma specialis", "Nothosubspecies", "Anamorphic variety");
        if(in_array($rank, $unrecognized_ranks)) return false;
        else return true;
    }

    private function create_archive()
    {
        foreach($this->taxa as $t) {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }
}
?>