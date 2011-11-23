<?php
namespace php_active_record;
/* connector: [218]  */

define("TROPICOS_NAME_EXPORT_FILE", DOC_ROOT . "/update_resources/connectors/files/Tropicos/tropicos_ids.txt");
define("TROPICOS_DOMAIN", "http://www.tropicos.org");
define("TROPICOS_TAXON_DETAIL_PAGE", "http://www.tropicos.org/Name/");
define("TROPICOS_IMAGE_LOCATION_LOW_BANDWIDTH", "http://www.tropicos.org/ImageScaled.aspx?imageid=");
define("TROPICOS_API_KEY", "2810ce68-f4cf-417c-b336-234bc8928390");
define("TROPICOS_API_SERVICE", "http://services.tropicos.org/Name/");

/*
<a href="http://services.tropicos.org/Name/25510055/ChromosomeCounts?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">1</a>
<a href="http://services.tropicos.org/Name/25510055/Images?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">2</a>
<a href="http://services.tropicos.org/Name/25510055/Distributions?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">3</a>
<a href="http://services.tropicos.org/Name/25510055/Synonyms?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">4</a>
<a href="http://services.tropicos.org/Name/25510055/References?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">5</a>
<a href="http://services.tropicos.org/Name/25510055/HigherTaxa?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390">6</a>
*/

/*
Take note of these sample ID's which generated resource without <dwc:ScientificName> last time connector was run:
13000069
13000165 
50335886
*/

class TropicosAPI
{
    private static $TEMP_FILE_PATH;
    private static $WORK_LIST;
    private static $WORK_IN_PROGRESS_LIST;
    private static $INITIAL_PROCESS_STATUS;

    function start_process($resource_id, $call_multiple_instance)
    {
        self::$TEMP_FILE_PATH         = DOC_ROOT . "/update_resources/connectors/files/Tropicos/";
        self::$WORK_LIST              = DOC_ROOT . "/update_resources/connectors/files/Tropicos/work_list.txt";
        self::$WORK_IN_PROGRESS_LIST  = DOC_ROOT . "/update_resources/connectors/files/Tropicos/work_in_progress_list.txt";
        self::$INITIAL_PROCESS_STATUS = DOC_ROOT . "/update_resources/connectors/files/Tropicos/initial_process_status.txt";

        if(!trim(Functions::get_a_task(self::$WORK_IN_PROGRESS_LIST)))//don't do this if there are harvesting task(s) in progress
        {
            if(!trim(Functions::get_a_task(self::$INITIAL_PROCESS_STATUS)))//don't do this if initial process is still running
            {
                Functions::add_a_task("Initial process start", self::$INITIAL_PROCESS_STATUS);
                // this will prepare a list of all species id
                self::build_id_list(); // 13 mins. execution
                // step 1: divides the big list of ids into small files
                self::divide_text_file(10000); //debug orig 10000, for testing use 5
                Functions::delete_a_task("Initial process start", self::$INITIAL_PROCESS_STATUS);//remove a task from task list
            }
        }

        // step 2: run multiple instances 
        while(true) //main process
        {
            $task = Functions::get_a_task(self::$WORK_LIST);//get task to work on
            if($task)
            {
                print "\n Process this: $task";
                Functions::delete_a_task($task, self::$WORK_LIST);//remove a task from task list
                Functions::add_a_task($task, self::$WORK_IN_PROGRESS_LIST);
                print "$task \n";
                $task = str_ireplace("\n", "", $task);//remove carriage return got from text file
                if($call_multiple_instance) //call 2 other instances for a total of 3 instances running
                {
                    Functions::run_another_connector_instance($resource_id, 2);
                    $call_multiple_instance = 0;
                }
                self::get_all_taxa($task);//main task
                print"\n Task $task is done. \n";
                Functions::delete_a_task("$task\n", self::$WORK_IN_PROGRESS_LIST);//remove a task from task list
            }
            else
            {
                print "\n\n [$task] Work list done or list hasn't been created yet " . date('Y-m-d h:i:s a', time());
                break;
            }
        }

        if(!$task = trim(Functions::get_a_task(self::$WORK_IN_PROGRESS_LIST)))//don't do this if there are task(s) in progress
        {
            // step 3: this should only run when all of instances of step 2 are done
            sleep(10); //debug orig 10
            self::combine_all_xmls($resource_id);
            self::delete_temp_files(self::$TEMP_FILE_PATH . "temp_tropicos_batch_", "xml"); //debug comment this line if u want to have a source for checking encoding probs in the XML
            self::delete_temp_files(self::$TEMP_FILE_PATH . "batch_", "txt");
        }
    }

    public static function get_all_taxa($task)
    {
        $all_taxa = array();
        $used_collection_ids = array();

        $filename = self::$TEMP_FILE_PATH . $task . ".txt";
        print "\nfilename: [$filename]";
        $READ = fopen($filename, "r");
        $i = 0;
        while(!feof($READ))
        {
            self::check_server_downtime();
            if($line = fgets($READ))
            {
                $i++; print "\n$i ";
                $line = trim($line);
                $fields = explode("\t", $line);
                $taxon_id = trim($fields[0]);
                $arr = self::get_tropicos_taxa($taxon_id, $used_collection_ids);
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];
                if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                unset($page_taxa);
            }
            else print "\n invalid line";
        }
        fclose($READ);
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $xml = self::add_rating_to_image_object($xml, '1.0');
        $resource_path = self::$TEMP_FILE_PATH . "temp_tropicos_" . $task . ".xml";
        $OUT = fopen($resource_path, "w");
        fwrite($OUT, $xml);
        fclose($OUT);
    }

    function divide_text_file($divisor)
    {
        $READ = fopen(TROPICOS_NAME_EXPORT_FILE, "r");
        $i = 0;
        $file_ctr = 0;
        $str = "";
        print "\n";
        while(!feof($READ))
        {
            if($line = fgets($READ))
            {
                $i++;
                $str .= $line;
                print "$i. $line \n";
                if($i == $divisor)//no. of names per text file
                {
                    $file_ctr++;
                    $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 2);
                    $OUT = fopen(self::$TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w");
                    fwrite($OUT, $str);
                    fclose($OUT);
                    $str = ""; 
                    $i = 0;
                }
            }
        }
        fclose($READ);

        //last writes
        if($str)
        {
            $file_ctr++;
            $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 2);
            $OUT = fopen(self::$TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w");
            fwrite($OUT, $str);
            fclose($OUT);
        }

        //create TROPICOS_work_list
        $str = "";
        FOR($i = 1; $i <= $file_ctr; $i++)
        {
            $str .= "batch_" . Functions::format_number_with_leading_zeros($i, 2) . "\n";
        }
        $filename = self::$WORK_LIST;
        if($OUT = fopen($filename, "w+"))
        {
            fwrite($OUT, $str);
            fclose($OUT);
        }
    }

    private function delete_temp_files($file_path, $file_extension)
    {
        $i = 0;
        while(true)
        {
            $i++;
            $i_str = Functions::format_number_with_leading_zeros($i, 2);
            $filename = $file_path . $i_str . "." . $file_extension;
            if(file_exists($filename))
            {
                print "\n unlink: $filename";
                unlink($filename);
            }
            else return;
        }
    }

    function combine_all_xmls($resource_id)
    {
        print "\n\n Start compiling all XML...\n";
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        $OUT = fopen($old_resource_path, "w+");
        $str = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $str .= "<response\n";
        $str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
        $str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
        $str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
        $str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
        $str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
        fwrite($OUT, $str);
        $i=0;
        while(true)
        {
            $i++;
            $i_str = Functions::format_number_with_leading_zeros($i, 2);
            $filename = self::$TEMP_FILE_PATH . "temp_tropicos_batch_" . $i_str . ".xml";
            if(!is_file($filename))
            {
                print " -end compiling XML's- ";
                break;
            }
            print " $i ";
            $READ = fopen($filename, "r");
            $contents = fread($READ, filesize($filename));
            fclose($READ);
            if($contents)
            {
                $pos1 = stripos($contents, "<taxon>");
                $pos2 = stripos($contents, "</response>");
                $str  = substr($contents, $pos1, $pos2-$pos1);
                fwrite($OUT, $str);
            }
        }
        fwrite($OUT, "</response>");
        fclose($OUT);
        print"\n All XML compiled\n -end-of-process- \n";
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
            print "\n no images ";
            return array();
        }
        */

        if(!$name = Functions::get_remote_file(TROPICOS_API_SERVICE . $taxon_id . "?format=json&apikey=" . TROPICOS_API_KEY)) print "\n lost connection \n";
        $name = json_decode($name, true);
        print "[$taxon_id] " . @$name['ScientificNameWithAuthors'];
        /* working but temporarily commented by Chris Freeland
        $arr_objects = self::get_chromosome_count($taxon_id, $arr_objects);
        */
        $arr_objects = self::get_distributions($taxon_id, $arr_objects);
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
                          "sciname"      => $name['ScientificNameWithAuthors'],
                          "taxon_refs"   => $arr_taxon_ref,
                          "synonyms"     => $arr_synonyms,
                          "commonNames"  => array(),
                          "data_objects" => $arr_objects
                         );
        return $arr_data;
    }

    function get_taxonomy($taxon_id)
    {
        $taxonomy = array();
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/HigherTaxa?format=xml&apikey=" . TROPICOS_API_KEY);
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
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/References?format=xml&apikey=" . TROPICOS_API_KEY);
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
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/Images?format=xml&apikey=" . TROPICOS_API_KEY);
        $with_image = 0;
        foreach($xml->Image as $rec)
        {
            if($rec->Error)
            {
                print"\n no images - " . $rec->DetailUrl;
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
                print"\n invalid image license - " . $rec->DetailUrl . "\n";
                continue;
            }
            else print"\n valid image license - " . $rec->DetailUrl . "\n";
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

            /* we are not allowed to get the bigger size images, only thumbnails
            $mediaURL   = TROPICOS_IMAGE_LOCATION_LOW_BANDWIDTH . $rec->ImageId . "&maxwidth=600"; */
            $mediaURL = $rec->ThumbnailUrl;
            $refs = array();
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $arr_objects);
        }
        return $arr_objects;
    }

    function get_chromosome_count($taxon_id, $arr_objects)
    {
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/ChromosomeCounts?format=xml&apikey=" . TROPICOS_API_KEY);
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
            $arr_objects    = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $arr_objects);
        }
        return $arr_objects;
    }

    function get_distributions($taxon_id, $arr_objects)
    {
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/Distributions?format=xml&apikey=" . TROPICOS_API_KEY);
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
            $source = TROPICOS_DOMAIN . "/Name/" . $taxon_id . "?tab=distribution";
            $identifier = $taxon_id . "_distribution";
            $mimeType   = "text/html";
            $dataType   = "http://purl.org/dc/dcmitype/Text";
            $title      = "";
            $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
            $agent      = array();
            $agent[] = array("role" => "source", "homepage" => "http://www.tropicos.org", "fullName" => "Tropicos");
            $mediaURL   = "";
            $location   = "";
            $license    = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $rightsHolder   = "";
            $arr_objects    = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, 
                $rightsHolder, $refs, $subject, $arr_objects);
        }
        return $arr_objects;
    }

    function get_synonyms($taxon_id)
    {
        $arr_synonyms = array();
        $arr = array();
        $xml = Functions::get_hashed_response(TROPICOS_API_SERVICE . $taxon_id . "/Synonyms?format=xml&apikey=" . TROPICOS_API_KEY);
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

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $arr_objects)
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
                              "language"     => "en"
                            );
        return $arr_objects;
    }

    function build_id_list() // 13 mins execution time
    {
        $OUT = fopen(self::$TEMP_FILE_PATH . "tropicos_ids.txt", "w");
        $startid = 0; // debug orig value 0; 1600267 with mediaURL and <location>
        //pagesize is the no. of records returned from Tropicos master list service
        $pagesize = 1000; // debug orig value 1000
        $count = 0;
        while(true)
        {
            $count++;
            $url = TROPICOS_API_SERVICE . "List?startid=$startid&PageSize=$pagesize&apikey=" . TROPICOS_API_KEY . "&format=json";
            print "\n[$count] $url";
            if($json_ids = Functions::get_remote_file($url))
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
                    else print "\n nameid undefined";
                }
                $startid++; // to avoid duplicate ids, set next id to get
                if($str != "") fwrite($OUT, $str);
            }
            else
            {
                print "\n --server not accessible-- \n";
                break;
            }
            if($count == 1300) break; // normal operation
            //break; //debug
        }
        fclose($OUT);
    }
    
    private function truncate_text_file($filename)
    {
        $OUT = fopen($filename, 'w');
        fwrite($OUT, "");
        fclose($OUT);
    }

    private function check_server_downtime()
    {
        $time = date('H:i:s', time());
        if($time >= "06:40:00" && $time <= "07:00:00")
        {
            print "\n\n Process stopped at [$time], will resume in 1.5 hours...";
            sleep((60*60)+(60*30)); //sleep 1.5 hours
        }
    }

    function add_rating_to_image_object($xml_string, $rating)
    {
        if(!stripos($xml_string, "mediaURL")) return $xml_string;
        print "\n this batch has mediaURL \n";
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