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
        
        if(Functions::is_production()) $this->data_dump_url = "http://www.discoverlife.org/users/s/Storey,_Malcolm/Malcolm_Storey_images.txt";
        else                           $this->data_dump_url = "http://localhost/eol_php_code/tmp2/Malcolm_Storey_images.txt";

        $this->original_resource = "http://opendata.eol.org/dataset/b0846bb0-7b81-40c7-8878-fb71f830ed17/resource/4444b435-c5e9-4d9e-b9ac-e6da8fd3fc55/download/168nov2010.xml.gz";
        $this->original_resource = "https://opendata.eol.org/dataset/a39f10aa-409f-42cd-a0df-8a5cd225cc51/resource/3092817a-1b5c-4922-820a-a8263ea38769/download/168nov2010.xml.gz";
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
        if($sciname && $taxon_id) {
            // /* format for uniformity
            if($val = @$row[$col['Photographer']])          $row[$col['Photographer']]          = self::format_person_by($val);
            if($val = @$row[$col['Recorded/Collected by']]) $row[$col['Recorded/Collected by']] = self::format_person_by($val);
            if($val = @$row[$col['Identified by']])         $row[$col['Identified by']]         = self::format_person_by($val);
            if($val = @$row[$col['Confirmed by']])          $row[$col['Confirmed by']]          = self::format_person_by($val);
            // */

            // /* for stats only
            $Photographer           = @$row[$col['Photographer']];
            $Recorded_Collected_by  = @$row[$col['Recorded/Collected by']];
            $Identified_by          = @$row[$col['Identified by']];
            $Confirmed_by           = @$row[$col['Confirmed by']];
            @$this->debug['agent breakdown']['Photographer'][$Photographer]++;
            @$this->debug['agent breakdown']['Recorded/Collected by'][$Recorded_Collected_by]++;
            @$this->debug['agent breakdown']['Identified by'][$Identified_by]++;
            @$this->debug['agent breakdown']['Confirmed by'][$Confirmed_by]++;           
            // */

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
        if($temp_filepath = Functions::save_remote_file_to_local($this->data_dump_url, array('cache' => 1, 'expire_seconds' => 60*60*24, 'timeout' => 4800, 'download_attempts' => 2, 'delay_in_minutes' => 3)))
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
        print_r($this->debug);
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
                        $r->term_name = str_replace('"', "", $do->agent);
                        $r->identifier = md5("$r->term_name|$do->agent['role']");
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
                        $description = trim(self::clean_str(Functions::conv_to_utf8($description)));
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
        // /*
        // https://eol-jira.bibalex.org/browse/DATA-1878?focusedCommentId=67762&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67762
        $agents_array = array();
            if($val = @$row[$col['Photographer']])          $agents_array = explode(",", $val);
        elseif($val = @$row[$col['Recorded/Collected by']]) $agents_array = explode(",", $val);
        else                                                $agents_array[] = "Malcolm Storey";
        // */

        $agents_array = array_map('trim', $agents_array);
        $agents_array = array_filter($agents_array); //remove null arrays
        $agents_array = array_unique($agents_array); //make unique
        $agents_array = array_values($agents_array); //reindex key

        $agent_ids = array();
        foreach($agents_array as $agent) {
            $agent = (string)trim($agent);
            $r = new \eol_schema\Agent();
            $r->term_name = $agent;
            $r->agentRole = "photographer";
            $r->identifier = md5($r->term_name."|".$r->agentRole);
            $agent_ids[] = $r->identifier;
            if(!in_array($r->identifier, $this->resource_agent_ids)) {
               $this->resource_agent_ids[] = $r->identifier;
               $this->archive_builder->write_object_to_file($r);
            }
        }

        // /* add Malcolm as compiler
        $r = new \eol_schema\Agent();
        $r->term_name = "Malcolm Storey";
        $r->agentRole = "compiler";
        $r->identifier = md5($r->term_name."|".$r->agentRole);
        $agent_ids[] = $r->identifier;
        if(!in_array($r->identifier, $this->resource_agent_ids)) {
            $this->resource_agent_ids[] = $r->identifier;
            $this->archive_builder->write_object_to_file($r);
        }        
        // */

        // /* maybe an overkill but to make sure no duplicates
        $agent_ids = array_map('trim', $agent_ids);
        $agent_ids = array_filter($agent_ids); //remove null arrays
        $agent_ids = array_unique($agent_ids); //make unique
        $agent_ids = array_values($agent_ids); //reindex key
        // */

        return $agent_ids;
    }
    function download_img_then_use_local_file_as_path($url)
    {   // "http://www.discoverlife.org/mp/20p?img=I_MWS10894&res=mx"
        $destination_folder = "/Volumes/Crucial_2TB/DiscoverLife_images/";  //local Mac Studio
        $destination_folder = "/html/other_files/DiscoverLife_images/";     //eol-archive
        $destination_folder = "/var/www/html/other_files/DiscoverLife_images/";     //eol-archive

        if(preg_match("/discoverlife.org\/mp\/20p\?img\=I_MWS(.*?)\&res\=mx/ims", $url, $arr)) {
            $img_id = $arr[1];
            $filename = self::generate_path_filename($url, $destination_folder, $img_id); debug("\nlocal: [$filename]\n");
            if(file_exists($filename)) {
                if(filesize($filename) > 0) return self::convert_local_filename_to_media_url($filename);
                else {
                    unlink($filename);
                    if($media_url = self::download_DL_image($url, $filename)) return $media_url;
                }
            }
            else {
                if($media_url = self::download_DL_image($url, $filename)) return $media_url;
            }
        }
        else return $url; //nothing changed in $url
    }
    private function download_DL_image($url, $filename)
    {   // wget -O sample.jpg "https://www.discoverlife.org/mp/20p?img=I_MWS71571&res=mx" --no-check-certificate -nc
        $cmd = 'wget -O '.$filename.' "'.$url.'" --no-check-certificate -nc';
        sleep(5);
        $output = shell_exec($cmd);
        // echo "\nTerminal: [$output]\n";
        if(filesize($filename) > 200) return self::convert_local_filename_to_media_url($filename); //at least 200 bytes
        else {
            return false;
            exit("\nInvestiagate DL image\n[$url]\nstops here...\n");
        }
    }
    private function convert_local_filename_to_media_url($filename)
    {   // local filename: [/Volumes/Crucial_2TB/DiscoverLife_images/e5/c7/10894.jpg // exit("\nlocal filename: [$filename\nstop muna\n");

        if(filesize($filename) < 200) return false;

        if(preg_match("/DiscoverLife_images\/(.*?)elix/ims", $filename."elix", $arr)) {
            return "https://editors.eol.org/other_files/DiscoverLife_images/".$arr[1];
        }
        else return false; //exit("\nShould not go here...\n");
        // https://editors.eol.org/other_files/DiscoverLife_images/eli.html --> to check
    }
    private function generate_path_filename($url, $main_path, $img_id)
    {
        $md5 = md5($url);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$img_id.jpg";
        return $filename;
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
        $mr->title = (string) self::clean_str(Functions::conv_to_utf8($row[$col['Title']]));
        $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
        // /*
        if($val = self::download_img_then_use_local_file_as_path($row[$col['DiscoverLife URL']])) $mr->accessURI = $val;
        else return;
        // */
        // $mr->accessURI = $row[$col['DiscoverLife URL']];
        $mr->creator = '';
        $mr->CreateDate = '';
        $mr->modified = '';
        
        // /* ---------- Always add Malcolm Storey as Owner: https://eol-jira.bibalex.org/browse/DATA-1878?focusedCommentId=67762&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67762
            if($val = @$row[$col['Photographer']])          $owner = self::format_person_by($val);
        elseif($val = @$row[$col['Recorded/Collected by']]) $owner = self::format_person_by($val);
        else                                                $owner = "Malcolm Storey";
        $owner_array = explode(",", $owner);
        $owner_array = array_map('trim', $owner_array);
        $owner_array = array_filter($owner_array); //remove null arrays
        $owner_array = array_unique($owner_array); //make unique
        $owner_array = array_values($owner_array); //reindex key
        $owner_final = Functions::remove_whitespace(implode(", ", $owner_array));
        // ---------- */
        $mr->Owner = self::format_person_by($owner_final); //always has a value

        $mr->publisher = '';
        // $mr->audience = 'Everyone';
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
        /* I think this is removed already.
        $description .= $row[$col['Recorded/Collected by']] != "" ? "Recorded/Collected by: " . self::format_person_by($row[$col['Recorded/Collected by']]) . ". " : "";
        */
        $description .= $row[$col['Identified by']] != "" ? "Identified by: " . self::format_person_by($row[$col['Identified by']]) . ". " : "";
        $description .= $row[$col['Confirmed by']] != "" ? "Confirmed by: " . self::format_person_by($row[$col['Confirmed by']]) . ". " : "";
        
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
        
        $mr->description = Functions::conv_to_utf8($description);
        $this->archive_builder->write_object_to_file($mr);
    }
    private function format_person_by($str)
    {
        $str = str_replace('"', "", trim($str));
        $str = str_replace(",", ", ", $str);
        return Functions::remove_whitespace($str);
    }
    private function create_instances_from_taxon_object($row, $col, $reference_ids)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon_id = (string)$row[$col['NWB taxon id']];
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        $taxon->taxonID = "BI-taxon-" . $taxon_id;
        $rank = (string)trim($row[$col['Rank']]);
        if(self::valid_rank($rank)) $taxon->taxonRank = $rank;
        $scientificName = (string)Functions::conv_to_utf8($row[$col['Taxon']]);
        if(!$scientificName) return; //blank
        $taxon->scientificName              = $scientificName;
        $taxon->kingdom                     = (string)$row[$col['Kingdom']];
        $taxon->phylum                      = (string)$row[$col['Phylum']];
        $taxon->class                       = (string)$row[$col['Class']];
        $taxon->order                       = (string)$row[$col['Order']];
        $taxon->family                      = (string)$row[$col['Family']];
        $taxon->furtherInformationURL       = $row[$col['BioImages taxon page']];
        // $taxon->acceptedNameUsage           = ''; //'Accepted name' --- just remove it: DATA-1878
        $original_identification            = (string)$row[$col['Original ident']];
        $taxon->namePublishedIn             = $original_identification;
        $taxonRemarks = '';
        $taxonRemarks .= $original_identification != "" ? "Original identification: " . $original_identification . ". " : "";
        $taxonRemarks .= $rank != "" ? "Rank: " . $rank . ". " : "";
        $NBN_Code = (string)$row[$col['NBN Code']];
        $taxonRemarks .= $NBN_Code != "" ? "UK NBN (National Biodiversity Network) taxon code: " . $NBN_Code . ". " : "";
        $taxon->taxonRemarks                = $taxonRemarks;
        // $taxon->infraspecificEpithet        = ''; //--- just remove it: DATA-1878
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