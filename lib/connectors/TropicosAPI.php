<?php
namespace php_active_record;
/* connector: [218]  
Missouri Botanical Garden:
--- Tropicos resource [218]
Partner provides a number of services to share their data to EOL. There is no scraping for this resource.
Partner provides a list of IDs: e.g. http://services.tropicos.org/Name/List?startid=0&PageSize=1000&apikey=2810ce68-f4cf-417c-b336-234bc8928390&format=json
The connector does some looping to get all the IDs.
And partner provides 7 different services for each type of information:
http://services.tropicos.org/Name/25510055?format=json&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/ChromosomeCounts?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/Images?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/Distributions?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/Synonyms?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/References?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/HigherTaxa?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390

* Tropicos web service goes down daily between 7-8am Eastern. So the connector process sleeps for an hour during this downtime.
* Connector runs for a long time because the sheer number of server requests to get all data for all taxa.
Last collection numbers: taxa=260,738; articles=345,414; images=80,125
*/

/*
<a href="http://services.tropicos.org/Name/25510055/ChromosomeCounts?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">1</a>
<a href="http://services.tropicos.org/Name/25510055/Images?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">2</a>
<a href="http://services.tropicos.org/Name/25510055/Distributions?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">3</a>
<a href="http://services.tropicos.org/Name/25510055/Synonyms?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">4</a>
<a href="http://services.tropicos.org/Name/25510055/References?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">5</a>
<a href="http://services.tropicos.org/Name/25510055/HigherTaxa?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">6</a>

Take note of these sample ID's which generated resource without <dwc:ScientificName> last time connector was run: 13000069 13000165 50335886
*/

define("TROPICOS_NAME_EXPORT_FILE", DOC_ROOT . "/update_resources/connectors/files/Tropicos/tropicos_ids.txt");
define("TROPICOS_DOMAIN", "http://www.tropicos.org");
define("TROPICOS_TAXON_DETAIL_PAGE", "http://www.tropicos.org/Name/");
define("TROPICOS_IMAGE_LOCATION_LOW_BANDWIDTH", "http://www.tropicos.org/ImageScaled.aspx?imageid=");
define("TROPICOS_API_KEY", "2810ce68-f4cf-417c-b336-234bc8928390");
define("TROPICOS_API_SERVICE", "http://services.tropicos.org/Name/");

class TropicosAPI
{
    public function __construct() 
    {           
        $this->TEMP_FILE_PATH         = DOC_ROOT . "/update_resources/connectors/files/Tropicos/";
        $this->WORK_LIST              = DOC_ROOT . "/update_resources/connectors/files/Tropicos/work_list.txt"; //sl - species-level taxa
        $this->WORK_IN_PROGRESS_LIST  = DOC_ROOT . "/update_resources/connectors/files/Tropicos/work_in_progress_list.txt";
        $this->INITIAL_PROCESS_STATUS = DOC_ROOT . "/update_resources/connectors/files/Tropicos/initial_process_status.txt";
    }

    function initialize_text_files()
    {
        if(!($f = fopen($this->WORK_LIST, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $this->WORK_LIST);
        }else  fclose($f);
        if(!($f = fopen($this->WORK_IN_PROGRESS_LIST, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $this->WORK_IN_PROGRESS_LIST);
        } else fclose($f);
        if(!($f = fopen($this->INITIAL_PROCESS_STATUS, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $this->INITIAL_PROCESS_STATUS);
        }else  fclose($f);
        //this is not needed but just to have a clean directory
        Functions::delete_temp_files($this->TEMP_FILE_PATH . "temp_tropicos_batch_", "xml");
        Functions::delete_temp_files($this->TEMP_FILE_PATH . "batch_", "txt");
    }

    function start_process($resource_id, $call_multiple_instance, $connectors_to_run = 1)
    {
        $this->resource_id = $resource_id;
        $this->call_multiple_instance = $call_multiple_instance;
        $this->connectors_to_run = $connectors_to_run;
        if(!trim(Functions::get_a_task($this->WORK_IN_PROGRESS_LIST)))//don't do this if there are harvesting task(s) in progress
        {
            if(!trim(Functions::get_a_task($this->INITIAL_PROCESS_STATUS)))//don't do this if initial process is still running
            {
                Functions::add_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);
                // this will prepare a list of all species id; 13 mins. execution
                self::build_id_list();
                // divides the big list of ids into small files
                self::divide_text_file(10000); //debug orig 10000, for testing use 5
                Functions::delete_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);//remove a task from task list
            }
        }
        Functions::process_work_list($this);
        if(!$task = trim(Functions::get_a_task($this->WORK_IN_PROGRESS_LIST)))//don't do this if there are task(s) in progress
        {
            // step 3: this should only run when all of instances of step 2 are done
            sleep(10); //debug orig 10
            Functions::combine_all_eol_resource_xmls($resource_id, $this->TEMP_FILE_PATH . "temp_tropicos_batch_*.xml");
            Functions::delete_temp_files($this->TEMP_FILE_PATH . "temp_tropicos_batch_", "xml"); //debug comment this line if u want to have a source for checking encoding probs in the XML
            Functions::delete_temp_files($this->TEMP_FILE_PATH . "batch_", "txt");
            Functions::set_resource_status_to_harvest_requested($resource_id);
        }
    }

    function get_all_taxa($task, $temp_file_path)
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $filename = $temp_file_path . $task . ".txt";
        echo "\nfilename: [$filename]";
        $i = 0;
        foreach(new FileIterator($filename) as $line_number => $line)
        {
            self::check_server_downtime();
            if($line)
            {
                $i++; echo "\n$i ";
                $line = trim($line);
                $fields = explode("\t", $line);
                $taxon_id = trim($fields[0]);
                $arr = self::get_tropicos_taxa($taxon_id, $used_collection_ids);
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];
                if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                unset($page_taxa);
            }
            else echo "\n Task list: end-of-file";
        }
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        // $xml = self::add_rating_to_image_object($xml, '1.0');
        $resource_path = $temp_file_path . "temp_tropicos_" . $task . ".xml";
        if(!($OUT = fopen($resource_path, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $resource_path);
          return;
        }
        fwrite($OUT, $xml);
        fclose($OUT);
    }

    function divide_text_file($divisor)
    {
        $i = 0;
        $file_ctr = 0;
        $str = "";
        foreach(new FileIterator(TROPICOS_NAME_EXPORT_FILE) as $line_number => $line)
        {
            if($line)
            {
                $line .= "\n"; // FileIterator removes the carriage-return
                $i++;
                $str .= $line;
                echo "\n $i. $line";
                if($i == $divisor)//no. of names per text file
                {
                    $file_ctr++;
                    $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 2);
                    if(!($OUT = fopen($this->TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w")))
                    {
                      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $this->TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt");
                      return;
                    }
                    fwrite($OUT, $str);
                    fclose($OUT);
                    $str = ""; 
                    $i = 0;
                }
            }
        }

        //last writes
        if($str)
        {
            $file_ctr++;
            $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 2);
            if(!($OUT = fopen($this->TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $this->TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt");
              return;
            }
            fwrite($OUT, $str);
            fclose($OUT);
        }

        //create TROPICOS_work_list
        $str = "";
        FOR($i = 1; $i <= $file_ctr; $i++)
        {
            $str .= "batch_" . Functions::format_number_with_leading_zeros($i, 2) . "\n";
        }
        $filename = $this->WORK_LIST;
        if($OUT = fopen($filename, "w+"))
        {
            fwrite($OUT, $str);
            fclose($OUT);
        }
    }

    public static function get_tropicos_taxa($taxon_id, $used_collection_ids)
    {
        $response = self::parse_xml($taxon_id);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["source"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["source"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    function parse_xml($taxon_id)
    {
        $arr_data = array();
        $arr_objects = array();

        $arr_objects = self::get_images($taxon_id, $arr_objects);
        /*
        process only those with images        
        if(sizeof($arr_objects) == 0)
        {
            echo "\n no images ";
            return array();
        }
        */

        if(!$name = Functions::get_remote_file(TROPICOS_API_SERVICE . $taxon_id . "?format=json&apikey=" . TROPICOS_API_KEY, array('timeout' => 4800, 'download_attempts' => 5))) echo "\n lost connection \n";
        $name = json_decode($name, true);
        $sciname = "" . @$name['ScientificNameWithAuthors'];
        echo "[$taxon_id] " . $sciname;
        /* working but temporarily commented by Chris Freeland
        $arr_objects = self::get_chromosome_count($taxon_id, $arr_objects);
        */
        $arr_objects = self::get_distributions($taxon_id, $arr_objects, $sciname);
        if(sizeof($arr_objects) == 0) return array();
        $arr_synonyms   = self::get_synonyms($taxon_id);
        $arr_taxon_ref  = self::get_taxon_ref($taxon_id);
        $taxonomy       = self::get_taxonomy($taxon_id);
        $arr_data[]=array("identifier"   => $taxon_id,
                          "source"       => TROPICOS_TAXON_DETAIL_PAGE . $taxon_id,
                          "kingdom"      => @$taxonomy['kingdom'],
                          "phylum"       => @$taxonomy['phylum'],
                          "class"        => @$taxonomy['class'],
                          "order"        => @$taxonomy['order'],
                          "family"       => @$taxonomy['family'],
                          "genus"        => @$taxonomy['genus'],
                          "sciname"      => $sciname,
                          "reference"    => $arr_taxon_ref,
                          "synonyms"     => $arr_synonyms,
                          "commonNames"  => array(),
                          "data_objects" => $arr_objects
                         );
        return $arr_data;
    }

    function get_taxonomy($taxon_id)
    {
        $taxonomy = array();
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/HigherTaxa?format=xml&apikey=" . TROPICOS_API_KEY, array('timeout' => 4800, 'download_attempts' => 5));
        foreach($xml->Name as $rec)
        {
            if($rec->Rank == "kingdom") $taxonomy['kingdom'] = $rec->ScientificNameWithAuthors;
            if($rec->Rank == "phylum")  $taxonomy['phylum'] = $rec->ScientificNameWithAuthors;
            if($rec->Rank == "class")   $taxonomy['class'] = $rec->ScientificNameWithAuthors;
            if($rec->Rank == "order")   $taxonomy['order'] = $rec->ScientificNameWithAuthors;
            if($rec->Rank == "family")  $taxonomy['family'] = $rec->ScientificNameWithAuthors;
            if($rec->Rank == "genus")   $taxonomy['genus'] = $rec->ScientificNameWithAuthors;
        }
        return $taxonomy;
    }

    function get_taxon_ref($taxon_id)
    {
        $refs = array();
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/References?format=xml&apikey=" . TROPICOS_API_KEY, array('timeout' => 4800, 'download_attempts' => 5));
        foreach($xml->NameReference as $rec)
        {
            if(!isset($rec->Reference->ReferenceId)) continue;
            $ref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->Reference->ReferenceId);
            $citation = trim($rec->Reference->FullCitation);
            $refs[] = array("url" => $ref_url, "fullReference" => $citation);
        }
        return $refs;
    }

    function get_images($taxon_id, $arr_objects)
    {
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/Images?format=xml&apikey=" . TROPICOS_API_KEY, array('timeout' => 4800, 'download_attempts' => 5));
        $with_image = 0;
        foreach($xml->Image as $rec)
        {
            if($rec->Error)
            {
                echo "\n no images - " . $rec->DetailUrl;
                continue;
            }
            
            $with_image++;
            if($with_image > 15) break;//max no. of images per taxon //debug orig 15

            $description = $rec->NameText . ". " . $rec->LongDescription;
            if($rec->SpecimenId)    $description .= "<br>" . "SpecimenId: " . $rec->SpecimenId;
            if($rec->SpecimenText)  $description .= "<br>" . "SpecimenText: " . $rec->SpecimenText;
            if($rec->Caption)       $description .= "<br>" . "Caption: " . $rec->Caption;
            if($rec->PhotoLocation) $description .= "<br>" . "Location: " . $rec->PhotoLocation;
            if($rec->PhotoDate)     $description .= "<br>" . "Photo taken: " . $rec->PhotoDate;
            if($rec->ImageKindText) $description .= "<br>" . "Image kind: " . $rec->ImageKindText;

            $valid_licenses = array("http://creativecommons.org/licenses/by/3.0/",
                                   "http://creativecommons.org/licenses/by-sa/3.0/",
                                   "http://creativecommons.org/licenses/by-nc/3.0/",
                                   "http://creativecommons.org/licenses/by-nc-sa/3.0/",
                                   "http://creativecommons.org/licenses/publicdomain/");
            if(!in_array(trim($rec->LicenseUrl), $valid_licenses))
            {
                echo "\n invalid image license - " . $rec->DetailUrl . "\n";
                continue;
            }
            else echo "\n valid image license - " . $rec->DetailUrl . "\n";
            $license = $rec->LicenseUrl;

            $agent = array();
            if(trim($rec->Photographer) != "") $agent[] = array("role" => "photographer", "homepage" => "", "fullName" => $rec->Photographer);

            $rightsHolder   = $rec->Copyright;
            $location   = $rec->PhotoLocation;
            $identifier = $rec->ImageId;
            $dataType   = "http://purl.org/dc/dcmitype/StillImage";
            $mimeType   = "image/jpeg";
            $title      = "";
            $subject    = "";
            $source = $rec->DetailUrl; // e.g. http://www.tropicos.org/Image/40777

            if($rec->DetailJpgUrl == 'http://www.tropicos.org/images/imageprotected.jpg') 
            {
                $mediaURL = $rec->ThumbnailUrl;
                $additionalInformation = '<rating>1.0</rating>';
            }
            else
            {
                $mediaURL = $rec->DetailJpgUrl;
                $additionalInformation = '<rating>2.0</rating>';
            }

            $refs = array();
            $description .= "<br>Full sized images can be obtained by going to the <a href='$source'>original source page</a>.";
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $additionalInformation, $arr_objects);
        }
        return $arr_objects;
    }

    function get_chromosome_count($taxon_id, $arr_objects)
    {
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/ChromosomeCounts?format=xml&apikey=" . TROPICOS_API_KEY, array('timeout' => 4800, 'download_attempts' => 5));
        $refs = array();
        $temp_reference = array();
        $with_content = false;
        $GametophyticCount = array();
        $SporophyticCount = array();
        $IPCNReferenceID = array();
        foreach($xml->ChromosomeCount as $rec)
        {
            if(!isset($rec->GametophyticCount) && !isset($rec->SporophyticCount)) continue;
            $with_content = true;
            $citation = trim($rec->Reference->FullCitation);
            $ref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->Reference->ReferenceId);
            if($rec->GametophyticCount) $GametophyticCount["$rec->GametophyticCount"] = 1;
            if($rec->SporophyticCount) $SporophyticCount["$rec->SporophyticCount"] = 1;
            if(trim($rec->IPCNReferenceID))
            {                
                $IPCNref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->IPCNReferenceID);
                $index = "<a target='tropicos' href='" . $IPCNref_url . "'>" . $rec->IPCNAbbreviation . "</a>";
                $IPCNReferenceID[$index] = 1;
            }
            //this is to prevent getting duplicate references
            if(!in_array($citation, $temp_reference)) $refs[] = array("url" => $ref_url, "fullReference" => $citation);
            $temp_reference[] = $citation;
        }
        $description = "";
        $GametophyticCount = array_keys($GametophyticCount);
        $SporophyticCount = array_keys($SporophyticCount);
        $IPCNReferenceID = array_keys($IPCNReferenceID);
        if($GametophyticCount) $description .= "Gametophyte chromosome count = " . implode("; ", $GametophyticCount) . "<br><br>";
        if($SporophyticCount) $description .= "Sporophyte chromosome count = " . implode("; ", $SporophyticCount) . "<br><br>";
        if($IPCNReferenceID) $description .= "IPCN Ref. = " . implode("; ", $IPCNReferenceID) . "<br><br>";
        if($with_content)
        {
            $source = TROPICOS_DOMAIN . "/Name/" . $taxon_id . "?tab=chromosomecounts";
            $identifier = $taxon_id . "_chromosome";
            $mimeType   = "text/html";
            $dataType   = "http://purl.org/dc/dcmitype/Text";
            $title      = "Chromosome Counts";
            $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Genetics";
            $agent      = array();
            $agent[]    = array("role" => "source", "homepage" => "http://www.tropicos.org", "fullName" => "Tropicos");
            $mediaURL   = "";
            $location   = "";
            $license    = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $rightsHolder   = "";
            $arr_objects    = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $additionalInformation='', $arr_objects);
        }
        return $arr_objects;
    }

    function get_distributions($taxon_id, $arr_objects, $sciname)
    {
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/Distributions?format=xml&apikey=" . TROPICOS_API_KEY, array('timeout' => 4800, 'download_attempts' => 5));
        $refs = array();
        $temp_reference = array();
        $temp_location = array();
        $with_content = false;
        $description = "";
        foreach($xml->Distribution as $rec)
        {
            if(!isset($rec->Location->CountryName)) continue;
            $with_content = true;
            $citation = trim($rec->Reference->FullCitation);
            $ref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->Reference->ReferenceId);
            //this is prevent getting duplicate distribution entry, even if API has duplicates.
            if(!in_array(trim($rec->Location->CountryName) . trim($rec->Location->RegionName), $temp_location))
            {
                $description .= trim($rec->Location->CountryName) . " (" . trim($rec->Location->RegionName) . ")<br>";
            }
            $temp_location[] = trim($rec->Location->CountryName) . trim($rec->Location->RegionName);
            //this is to prevent getting duplicate references
            if(!in_array($citation, $temp_reference)) $refs[] = array("url" => $ref_url, "fullReference" => $citation);
            $temp_reference[] = $citation;
        }

        if($with_content)
        {
            $description = "<i>$sciname</i>: <br>" . $description . "<br>Note: This information is based on publications available through <a href='http://tropicos.org/'>Tropicos</a> and may not represent the entire distribution. Tropicos does not categorize distributions as native or non-native.";
            $source = TROPICOS_DOMAIN . "/Name/" . $taxon_id . "?tab=distribution";
            $identifier = $taxon_id . "_distribution";
            $mimeType   = "text/html";
            $dataType   = "http://purl.org/dc/dcmitype/Text";
            $title      = "Localities documented in Tropicos sources";
            $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
            $agent      = array();
            $agent[] = array("role" => "source", "homepage" => "http://www.tropicos.org", "fullName" => "Tropicos");
            $mediaURL   = "";
            $location   = "";
            $license    = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $rightsHolder   = "";
            $arr_objects    = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, 
                $rightsHolder, $refs, $subject, $additionalInformation='', $arr_objects);
        }
        return $arr_objects;
    }

    function get_synonyms($taxon_id)
    {
        $arr_synonyms = array();
        $arr = array();
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/Synonyms?format=xml&apikey=" . TROPICOS_API_KEY, array('timeout' => 4800, 'download_attempts' => 5));
        foreach($xml->Synonym as $syn)
        {
            $synonym = trim($syn->SynonymName->ScientificNameWithAuthors);
            $arr[$synonym] = "";
        }
        foreach(array_keys($arr) as $synonym)
        {
            if($synonym) $arr_synonyms[] = array("synonym" => $synonym, "relationship" => "synonym");
        }
        return $arr_synonyms;
    }

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $additionalInformation='', $arr_objects)
    {
        $arr_objects[]=array( "identifier"   => $identifier,
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
                              "language"     => "en",
                              "additionalInformation" => $additionalInformation
                            );
        return $arr_objects;
    }

    function build_id_list() // 13 mins execution time
    {
        if(!($OUT = fopen($this->TEMP_FILE_PATH . "tropicos_ids.txt", "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". $this->TEMP_FILE_PATH . "tropicos_ids.txt");
          return;
        }
        $startid = 0; // debug orig value 0; 1600267 with mediaURL and <location>; 1201245 with thumbnail size images
        //pagesize is the no. of records returned from Tropicos master list service
        $pagesize = 1000; // debug orig value 1000
        $count = 0;
        while(true)
        {
            $count++;
            $url = TROPICOS_API_SERVICE . "List?startid=$startid&PageSize=$pagesize&apikey=" . TROPICOS_API_KEY . "&format=json";
            echo "\n[$count] $url";
            if($json_ids = Functions::get_remote_file($url, DOWNLOAD_WAIT_TIME, array('timeout' => 4800, 'download_attempts' => 5)))
            {
                $ids = json_decode($json_ids, true);
                $str = "";
                foreach($ids as $id)
                {
                    if($id["NameId"])
                    {
                        $str .= $id["NameId"] . "\n";
                        $startid = $id["NameId"];
                    }
                    else echo "\n nameid undefined";
                }
                $startid++; // to avoid duplicate ids, set next id to get
                if($str != "") fwrite($OUT, $str);
            }
            else
            {
                echo "\n --server not accessible-- \n";
                break;
            }
            if($count == 1300) break; // normal operation
            // break; //debug
        }
        fclose($OUT);
    }
    
    private function truncate_text_file($filename)
    {
        if(!($OUT = fopen($filename, 'w')))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: ". $filename);
          return;
        }
        fwrite($OUT, "");
        fclose($OUT);
    }

    private function check_server_downtime()
    {
        $time = date('H:i:s', time());
        if($time >= "06:40:00" && $time <= "07:00:00")
        {
            echo "\n\n Process stopped at [$time], will resume in 1.5 hours...";
            sleep((60*60)+(60*30)); //sleep 1.5 hours
        }
    }

    function add_rating_to_image_object($xml_string, $rating)
    {
        if(!stripos($xml_string, "mediaURL")) return $xml_string;
        echo "\n this batch has mediaURL \n";
        $xml = simplexml_load_string($xml_string);
        foreach($xml->taxon as $taxon)
        {
            foreach($taxon->dataObject as $dataObject)
            {
                $dataObject_dc = $dataObject->children("http://purl.org/dc/elements/1.1/");
                if(@$dataObject->mediaURL)
                {
                    $dataObject->addChild("additionalInformation", "");
                    $dataObject->additionalInformation->addChild("rating", $rating);
                }
            }
        }
        return $xml->asXML();
    }

}
?>