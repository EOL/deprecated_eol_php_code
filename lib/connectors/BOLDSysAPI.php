<?php
namespace php_active_record;
/* connector: 212 
--- BOLDS resource for species-level taxa [212]. This is now scheduled as a cron task.
Connector uses BOLDS service for most of the info but still scrapes the nucleotides sequence for species level taxa.
The service is per phylum level e.g.: http://v2.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=Annelida
This service will then list all the species under this phylum. The list of phylum names at the moment is hard-coded.
The connector runs all the phylum taxa, assembles each of the taxon info and generates the final EOL XML.

With the availability of the BOLDS big XML file (http://www.boldsystems.org/export/boldrecords.xml.gz), 
the nucleotides sequence is no longer scraped from the site.
*/

define("PHYLUM_SERVICE_URL", "http://v2.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=");
define("SPECIES_URL", "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=");

class BOLDSysAPI
{
    const MAP_SCALE = "/libhtml/icons/mapScale_BOLD.png";
    const BOLDS_DOMAIN = "http://www.boldsystems.org";
    const BOLDS_DOMAIN_NEW = "http://v2.boldsystems.org";

    private static $saved_sequences;

    public function __construct()
    {           
        $this->TEMP_FILE_PATH         = DOC_ROOT . "/update_resources/connectors/files/BOLD/";
        $this->WORK_LIST              = $this->TEMP_FILE_PATH . "sl_work_list.txt"; //sl - species-level taxa
        $this->WORK_IN_PROGRESS_LIST  = $this->TEMP_FILE_PATH . "sl_work_in_progress_list.txt";
        $this->INITIAL_PROCESS_STATUS = $this->TEMP_FILE_PATH . "sl_initial_process_status.txt";
        $this->LOG_FILE               = $this->TEMP_FILE_PATH . "cannot_access_phylum.txt";
        $this->SAVED_SEQUENCES_FILE   = $this->TEMP_FILE_PATH . "taxa_sequences.txt";
    }

    function initialize_text_files()
    {
        $f = fopen($this->WORK_LIST, "w"); fclose($f);
        $f = fopen($this->WORK_IN_PROGRESS_LIST, "w"); fclose($f);
        $f = fopen($this->INITIAL_PROCESS_STATUS, "w"); fclose($f);
        //this is not needed but just to have a clean directory
        self::delete_temp_files($this->TEMP_FILE_PATH . "sl_batch_", "txt");
        self::delete_temp_files($this->TEMP_FILE_PATH . "sl_batch_", "xml");
        self::initialize_text_file();
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
                // Divide the big list of ids into small files
                Functions::add_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);
                self::create_master_list();
                Functions::delete_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);
            }
        }
        Functions::process_work_list($this);
        if(!$task = trim(Functions::get_a_task($this->WORK_IN_PROGRESS_LIST))) //don't do this if there are task(s) in progress
        {
            // Combine all XML files.
            Functions::combine_all_eol_resource_xmls($resource_id, $this->TEMP_FILE_PATH . "sl_batch_*.xml");
            // Set to force harvest
            Functions::set_resource_status_to_force_harvest($resource_id);
            // Delete temp files
            self::delete_temp_files($this->TEMP_FILE_PATH . "sl_batch_", "txt");
            self::delete_temp_files($this->TEMP_FILE_PATH . "sl_batch_", "xml"); //debug Don't delete it if you want to check subsets of the resource XML.
        }
    }

    function get_all_taxa($task, $temp_file_path)
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $filename = $temp_file_path . $task . ".txt";
        $records = self::get_array_from_json_file($filename);
        $num_rows = sizeof($records); $i = 0;
        foreach($records as $rec)
        {
            $i++; echo "\n [$i of $num_rows] ";
            echo $rec['taxonomy']['species']['taxon']['name'];
            // if(trim($rec['taxonomy']['species']['taxon']['name']) != "Lumbricus centralis") continue; //debug
            $arr = $this->get_boldsys_taxa($rec, $used_collection_ids);
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);
            unset($page_taxa);
        }
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $xml = str_replace("</mediaURL>", "</mediaURL><additionalInformation><subtype>map</subtype>\n</additionalInformation>\n", $xml);
        $resource_path = $temp_file_path . $task . ".xml";
        $OUT = fopen($resource_path, "w");
        fwrite($OUT, $xml); 
        fclose($OUT);
    }

    function get_boldsys_taxa($rec, $used_collection_ids)
    {
        $response = $this->parse_xml($rec);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["sciname"]] = true;
        }
        return array($page_taxa,$used_collection_ids);
    }

    function get_taxon_id($rec)
    {
        if(isset($rec['taxonomy']['species']['taxon']['taxid'])) return array($rec['taxonomy']['species']['taxon']['taxid'], $rec['taxonomy']['species']['taxon']['name']);
        if(isset($rec['taxonomy']['genus']['taxon']['taxid']))   return array($rec['taxonomy']['genus']['taxon']['taxid'], $rec['taxonomy']['genus']['taxon']['name']);
        if(isset($rec['taxonomy']['family']['taxon']['taxid']))  return array($rec['taxonomy']['family']['taxon']['taxid'], $rec['taxonomy']['family']['taxon']['name']);
        if(isset($rec['taxonomy']['order']['taxon']['taxid']))   return array($rec['taxonomy']['order']['taxon']['taxid'], $rec['taxonomy']['order']['taxon']['name']);
        if(isset($rec['taxonomy']['class']['taxon']['taxid']))   return array($rec['taxonomy']['class']['taxon']['taxid'], $rec['taxonomy']['class']['taxon']['name']);
        if(isset($rec['taxonomy']['phylum']['taxon']['taxid']))  return array($rec['taxonomy']['phylum']['taxon']['taxid'], $rec['taxonomy']['phylum']['taxon']['name']);
        if(isset($rec['taxonomy']['kingdom']['taxon']['taxid'])) return array($rec['taxonomy']['kingdom']['taxon']['taxid'], $rec['taxonomy']['kingdom']['taxon']['name']);
    }

    function parse_xml($rec)
    {
        $arr_data = array();
        $arr = self::get_taxon_id($rec);
        $taxon_id = $arr[0];
        $sciname  = $arr[1];
 
        //start data objects
        $arr_objects = array();
 
        //barcode stats
        $bold_stats = "";
        if(@$rec['stats']['public_barcodes']) $bold_stats .= "Public Records: " . @$rec['stats']['public_barcodes'] . "<br>";
        else                                  $bold_stats .= "Public Records: 0<br>";
        if(@$rec['stats']['barcodes'])        $bold_stats .= "Specimens with Barcodes: " . @$rec['stats']['barcodes'] . "<br>";
        if(@$rec['stats']['barcoded_species'])$bold_stats .= "Species With Barcodes: " . @$rec['stats']['barcoded_species'];
        $identifier  = $taxon_id . "_stats";
        $dataType    = "http://purl.org/dc/dcmitype/Text"; $mimeType    = "text/html";
        $title       = "Statistics of barcoding coverage: $sciname";
        $source      = SPECIES_URL . trim($taxon_id);
        $mediaURL    = "";               
        $description = "Barcode of Life Data Systems (BOLDS) Stats <br> $bold_stats";
        
        //same for all text objects
        $subject      = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#MolecularBiology";
        $license      = "http://creativecommons.org/licenses/by/3.0/";        
        $rightsHolder = "Barcode of Life Data Systems";

        //same for all objects
        $agent = array(0 => array("role" => "compiler", "homepage" => self::BOLDS_DOMAIN . "/", "fullName" => "Sujeevan Ratnasingham"),
                       1 => array("role" => "compiler", "homepage" => self::BOLDS_DOMAIN . "/", "fullName" => "Paul D.N. Hebert"));
        if($bold_stats != "") $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $license, $rightsHolder, $subject, $agent);
        
        //barcode image
        if(isset($rec['barcode_image_url']))
        {
            $identifier  = $taxon_id . "_barcode_data";
            $dataType    = "http://purl.org/dc/dcmitype/Text"; $mimeType = "text/html";
            $title       = "Barcode data: $sciname";
            $source      = SPECIES_URL . trim($taxon_id);
            $mediaURL    = "";

            $with_stats = @$rec['stats']['public_barcodes'] || @$rec['stats']['barcodes'] || @$rec['stats']['barcoded_species'];
            if($description = $this->check_if_with_content($taxon_id, $source, "http://".$rec['barcode_image_url'], $with_stats, @$rec['stats']['public_barcodes']))
            {
                $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $license, $rightsHolder, $subject, $agent);
            }
        }

        //map
        if(isset($rec['map_url']))
        {
            /*
            $identifier  = $taxon_id . "_map";
            $dataType    = "http://purl.org/dc/dcmitype/Text"; $mimeType    = "text/html";
            $title       = "Locations of barcode samples: $sciname";            
            $source      = SPECIES_URL . trim($taxon_id);
            $mediaURL    = "";               
            $description = "Collection Sites: world map showing specimen collection locations for <i>" . $sciname . "</i><br><img border='0' src='".$rec['map_url']."'>";                
            */
            $map_scale_url = self::BOLDS_DOMAIN . self::MAP_SCALE;
            $identifier  = $taxon_id . "_map";
            $dataType    = "http://purl.org/dc/dcmitype/StillImage";
            $mimeType    = "image/png";
            $title       = "BOLDS: Map of specimen collection locations for <i>" . $sciname . "</i>";
            $source      = SPECIES_URL . trim($taxon_id);
            $mediaURL    = $rec['map_url'];
            $description = "Collection Sites: world map showing specimen collection locations for <i>" . $sciname . "</i><br><img src='$map_scale_url'>";
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $license, $rightsHolder, $subject, $agent);
        }
        //end data objects

        $phylum = ""; $class = ""; $order = ""; $family = ""; $genus = ""; $species = "";
        if(isset($rec['taxonomy']['phylum']['taxon']['name']))   $phylum = $rec['taxonomy']['phylum']['taxon']['name'];
        if(isset($rec['taxonomy']['class']['taxon']['name']))    $class = $rec['taxonomy']['class']['taxon']['name'];
        if(isset($rec['taxonomy']['order']['taxon']['name']))    $order = $rec['taxonomy']['order']['taxon']['name'];
        if(isset($rec['taxonomy']['family']['taxon']['name']))   $family = $rec['taxonomy']['family']['taxon']['name'];
        if(isset($rec['taxonomy']['genus']['taxon']['name']))    $genus = $rec['taxonomy']['genus']['taxon']['name'];
        if(isset($rec['taxonomy']['species']['taxon']['name']))  $species = $rec['taxonomy']['species']['taxon']['name'];
        $arr_data[]=array(  "identifier"   => $taxon_id,
                            "source"       => SPECIES_URL . trim($taxon_id),
                            "kingdom"      => "",
                            "phylum"       => $phylum,
                            "class"        => $class,
                            "order"        => $order,
                            "family"       => $family,
                            "genus"        => $genus,
                            "sciname"      => $species,
                            "data_objects" => $arr_objects
                         );
        return $arr_data;
    }

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $license, $rightsHolder, $subject, $agent)
    {
        return array("identifier"   => $identifier,
                     "dataType"     => $dataType,
                     "mimeType"     => $mimeType,
                     "title"        => $title,
                     "source"       => $source,
                     "description"  => $description,
                     "mediaURL"     => $mediaURL,
                     "license"      => $license,
                     "rightsHolder" => $rightsHolder,
                     "subject"      => $subject,
                     "agent"        => $agent
                    );
    }

    function delete_temp_files($file_path, $file_extension = '*')
    {
        foreach (glob($file_path . "*." . $file_extension) as $filename) unlink($filename);
    }

    private function create_master_list()
    {
        //Animals
        $arr_phylum = array(0 => array( "name" => "Acanthocephala"   , "id" => 11),
                            1 => array( "name" => "Annelida"         , "id" => 2),
                            2 => array( "name" => "Arthropoda"       , "id" => 20),
                            3 => array( "name" => "Brachiopoda"      , "id" => 9),
                            4 => array( "name" => "Bryozoa"          , "id" => 7),
                            5 => array( "name" => "Chaetognatha"     , "id" => 13),
                            6 => array( "name" => "Chordata"         , "id" => 18),
                            7 => array( "name" => "Cnidaria"         , "id" => 3),
                            8 => array( "name" => "Cycliophora"      , "id" => 79455),
                            9 => array( "name" => "Echinodermata"    , "id" => 4),
                            10 => array( "name" => "Echiura"         , "id" => 27333),
                            11 => array( "name" => "Gnathostomulida" , "id" => 78956),
                            12 => array( "name" => "Hemichordata"    , "id" => 21),
                            13 => array( "name" => "Mollusca"        , "id" => 23),
                            14 => array( "name" => "Nematoda"        , "id" => 19),
                            15 => array( "name" => "Onychophora"     , "id" => 10),
                            16 => array( "name" => "Platyhelminthes" , "id" => 5),
                            17 => array( "name" => "Porifera"        , "id" => 24818),
                            18 => array( "name" => "Rotifera"        , "id" => 16),
                            19 => array( "name" => "Sipuncula"       , "id" => 15),
                            20 => array( "name" => "Tardigrada"      , "id" => 26033),
                            21 => array( "name" => "Xenoturbellida"  , "id" => 88647)
                           );
                           
        //Fungi 
        $temp = array(0 => array( "name" => "Ascomycota"      , "id" => 34),
                      1 => array( "name" => "Basidiomycota"   , "id" => 23675),
                      2 => array( "name" => "Chytridiomycota" , "id" => 23691),
                      3 => array( "name" => "Myxomycota"      , "id" => 83947),
                      4 => array( "name" => "Zygomycota"      , "id" => 23738)
                     );                        
        $arr_phylum = array_merge($arr_phylum, $temp);                 
        
        //Plants 
        $temp = array(0 => array( "name" => "Bryophyta"          , "id" => 176192),
                      1 => array( "name" => "Chlorophyta"        , "id" => 112296),
                      2 => array( "name" => "Lycopodiophyta"     , "id" => 38696),
                      3 => array( "name" => "Magnoliophyta"      , "id" => 12),
                      4 => array( "name" => "Pinophyta"          , "id" => 251587),
                      5 => array( "name" => "Pteridophyta"       , "id" => 38074),
                      6 => array( "name" => "Rhodophyta"         , "id" => 48327),
                      7 => array( "name" => "Stramenopiles"      , "id" => 109924)
                     );        
        $arr_phylum = array_merge($arr_phylum, $temp);                 
                         
        //Protists                        
        $temp = array(0 => array( "name" => "Bacillariophyta"    , "id" => 74445),
                      1 => array( "name" => "Ciliophora"         , "id" => 72834),
                      2 => array( "name" => "Dinozoa"            , "id" => 70855),
                      3 => array( "name" => "Heterokontophyta"   , "id" => 53944),
                      4 => array( "name" => "Opalozoa"           , "id" => 72171),
                      5 => array( "name" => "Straminipila"       , "id" => 23715),
                      6 => array( "name" => "Chlorarachniophyta" , "id" => 316986),
                      7 => array( "name" => "Pyrrophycophyta"    , "id" => 317010)
                     );
        $arr_phylum = array_merge($arr_phylum, $temp);

        /* //debug
        $arr_phylum = array();
        // $arr_phylum[] = array( "name" => "Chordata" , "id" => 18);
        $arr_phylum[] = array( "name" => "Annelida"       , "id" => 11);
        */

        self::count_taxa_per_phylum($arr_phylum);
    }

    private function count_taxa_per_phylum($arr_phylum)
    {
        $total_phylum = sizeof($arr_phylum);
        $p = 0;
        $records = array();
        $file_count = 0;
        foreach($arr_phylum as $phylum)
        {
            $p++;
            $phylum_path = PHYLUM_SERVICE_URL . $phylum['name'];
            // $phylum_path = "http://localhost/~eolit/eli/eol_php_code/update_resources/connectors/files/BOLD/Annelida.xml"; // debug
            echo "\n\nphylum service: " . $phylum_path . "\n";
            if($xml = Functions::get_hashed_response($phylum_path, DOWNLOAD_WAIT_TIME, 1200, 5))
            {
                echo "\n [$p of $total_phylum] $phylum[name] $phylum[id] -- [" . sizeof($xml->record) . "]";
                $i = 0;
                foreach($xml->record as $rec)
                {
                    $i++; 
                    $records[] = $rec;
                    if(sizeof($records) >= 10000) //debug orig divide into batch of 10000
                    {
                        $file_count++;
                        self::save_to_json_file($records, $this->TEMP_FILE_PATH . "sl_batch_" . Functions::format_number_with_leading_zeros($file_count, 3) . ".txt");
                        $records = array();
                    }
                    // if($i >= 20) break; //debug
                }
            }
            else
            {
                echo "\n\n Cannot access: " . $phylum_path;
                self::log_cannot_access_phylum($phylum_path);
            }
            sleep(10);
        }
        //last save
        if($records)
        {
            $file_count++;
            self::save_to_json_file($records, $this->TEMP_FILE_PATH . "sl_batch_" . Functions::format_number_with_leading_zeros($file_count, 3) . ".txt");
        }
        //create work_list
        $str = "";
        for($i = 1; $i <= $file_count; $i++) $str .= "sl_batch_" . Functions::format_number_with_leading_zeros($i, 3) . "\n";
        if($fp = fopen($this->WORK_LIST, "w"))
        {
            fwrite($fp, $str);
            fclose($fp);
        }
    }

    private function initialize_text_file()
    {
        $OUT = fopen($this->LOG_FILE, "a");
        fwrite($OUT, "===================" . "\n");
        fwrite($OUT, date("F j, Y, g:i:s a") . "\n");
        fclose($OUT);
    }

    function log_cannot_access_phylum($line)
    {
        if($fp = fopen($this->LOG_FILE, "a"))
        {
            fwrite($fp, $line. "\n");
            fclose($fp);
        }
    }

    function save_to_json_file($arr, $filename)
    {
        $WRITE = fopen($filename, "w");
        fwrite($WRITE, json_encode($arr));
        fclose($WRITE);
    }

    function get_array_from_json_file($filename)
    {
        $READ = fopen($filename, "r");
        $contents = fread($READ, filesize($filename));
        fclose($READ);
        return json_decode($contents,true);
    }

    // start of added functions

    public function check_if_with_content($taxid, $dc_source, $barcode_image_url = false, $with_stats, $public_barcodes)
    {
        //start get text dna sequece
        $src = self::BOLDS_DOMAIN_NEW . "/connect/REST/getBarcodeRepForSpecies.php?taxid=" . $taxid . "&iwidth=400";
        if($barcode_image_url || self::barcode_image_available($src))
        {
            $description = "The following is a representative barcode sequence, the centroid of all available sequences for this species.
            <br><a target='barcode' href='$src'><img src='$src' height=''></a>";
        }
        else $description = "Barcode image not yet available.";
        $description .= "<br><br>";

        $url = self::BOLDS_DOMAIN . "/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";
        // $arr = self::get_text_dna_sequence($url);
        $arr = $this->get_text_dna_sequence_v2($taxid);
        $count_sequence     = $arr["count_sequence"];
        $text_dna_sequence  = $arr["best_sequence"];
        // $url_fasta_file     = $arr["url_fasta_file"]; this will point to the fasta.fas file from BOLDS temp folder

        echo "\n[$public_barcodes]=[$count_sequence]\n";
        $str = "";
        if($count_sequence > 0)
        {
            if($count_sequence == 1) $str = "There is 1 barcode sequence available from BOLD and GenBank. 
                                     Below is the sequence of the barcode region Cytochrome oxidase subunit 1 (COI or COX1) from a member of the species.
                                     See the <a target='BOLDSys' href='$dc_source'>BOLD taxonomy browser</a> for more complete information about this specimen.
                                     Other sequences that do not yet meet barcode criteria may also be available.";
            else                     $str = "There are $count_sequence barcode sequences available from BOLD and GenBank.
                                     Below is a sequence of the barcode region Cytochrome oxidase subunit 1 (COI or COX1) from a member of the species.
                                     See the <a target='BOLDSys' href='$dc_source'>BOLD taxonomy browser</a> for more complete information about this specimen and other sequences.";
            $str .= "<br><br>";
            $text_dna_sequence .= "<br>-- end --<br>";
        }

        if(trim($text_dna_sequence) != "")
        {
            $temp = "$str ";
            $temp .= "<div style='font-size : x-small;overflow : scroll;'> $text_dna_sequence </div>";
        }
        else $temp = "No available public DNA sequences. <br>";

        if($count_sequence > 0 || $with_stats)
        {
            /* one-click         
            $url_fasta_file = "http://services.eol.org/eol_php_code/applications/barcode/get_text_dna_sequence.php?taxid=$taxid";
            */
            /* 2-click per PL advice */
            $url_fasta_file = self::BOLDS_DOMAIN . "/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";
            $temp .= "<br><a target='fasta' href='$url_fasta_file'>Download FASTA File</a>";
        }

        $description .= $temp;
        //end get text dna sequence

        if(Functions::is_utf8($description)) return $description;
        else return false;
    }

    function get_text_dna_sequence_v2($taxon_id)
    {
        if(!self::$saved_sequences)
        {
            // at this point the $this->SAVED_SEQUENCES_FILE will always exist
            if(!file_exists($this->SAVED_SEQUENCES_FILE)) $this->save_dna_sequence_from_big_xml();

            echo "\n loading saved sequences... \n";
            self::$saved_sequences = self::get_array_from_json_file($this->SAVED_SEQUENCES_FILE);
        }
        print_r(@self::$saved_sequences[$taxon_id]);
        return array("count_sequence" => @self::$saved_sequences[$taxon_id]["c"], "best_sequence" => @self::$saved_sequences[$taxon_id]["s"]);
    }

    private function get_text_dna_sequence($url)
    {
        echo "\n\n access get_text_dna_sequence(): $url \n"; 
        $str = Functions::get_remote_file($url, DOWNLOAD_WAIT_TIME, 1200, 5);
        if(preg_match("/\.\.\/temp\/(.*?)fasta\.fas/ims", $str, $matches)) $folder = $matches[1];
        $str = "";
        if($folder != "")
        {
            $url = self::BOLDS_DOMAIN_NEW . "/temp/" . $folder . "/fasta.fas";
            $str = Functions::get_remote_file($url, DOWNLOAD_WAIT_TIME, 1200, 5);
            echo "\n\n access: $url \n";
        }
        $count_sequence = substr_count($str, '>');
        //start get the single sequence = longest, with least N char
        $best_sequence = self::get_best_sequence($str);
        return array("count_sequence" => $count_sequence, "best_sequence" => $best_sequence);
    }

    private function get_best_sequence($str)
    {
        $str = str_ireplace('>', '&arr[]=', $str);
        $arr = array();
        parse_str($str);
        if(count($arr) > 0)
        {
            $biggest = 0;
            $index_with_longest_txt = 0;
            for ($i = 0; $i < count($arr); $i++)
            {
                $dna = trim($arr[$i]);
                $pos = strrpos($dna, "|");
                $new_dna = trim(substr($dna, $pos+1, strlen($dna)));
                $new_dna = str_ireplace(array("-", " "), "", $new_dna);
                $len_new_dna = strlen($new_dna);
                if($biggest < $len_new_dna)
                {
                    $biggest = $len_new_dna;
                    $index_with_longest_txt = $i;
                }
            }
            return $arr[$index_with_longest_txt];
        }
        else return "";
    }

    private function barcode_image_available($src)
    {
        $str = Functions::get_remote_file($src, DOWNLOAD_WAIT_TIME, 1200, 5);
        /*
        ERROR: Only species level taxids are accepted
        ERROR: Unable to retrieve sequence
        */
        if(is_numeric(stripos($str, "ERROR:"))) return false;
        else return true;
    }

    function save_dna_sequence_from_big_xml()
    {
        echo "\n\n saving dna sequence from big xml file...\n"; // from 212.php this file will always be re-created
        require_library('connectors/BoldsImagesAPIv2');
        $func = new BoldsImagesAPIv2();
        $path = $func->download_and_extract_remote_file();
        echo "\n\n $path";
        $reader = new \XMLReader();
        $reader->open($path);
        $taxa_sequences = array();
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "record")
            {
                $string = $reader->readOuterXML();
                $xml = simplexml_load_string($string);
                $best_sequence = "";
                if(@$xml->sequences->sequence)
                {
                    if    ($taxon_id = trim(@$xml->taxonomy->species->taxon->taxon_id)) {}
                    elseif($taxon_id = trim(@$xml->taxonomy->genus->taxon->taxon_id)) {}
                    elseif($taxon_id = trim(@$xml->taxonomy->subfamily->taxon->taxon_id)) {}
                    elseif($taxon_id = trim(@$xml->taxonomy->family->taxon->taxon_id)) {}
                    elseif($taxon_id = trim(@$xml->taxonomy->order->taxon->taxon_id)) {}
                    elseif($taxon_id = trim(@$xml->taxonomy->class->taxon->taxon_id)) {}
                    elseif($taxon_id = trim(@$xml->taxonomy->phylum->taxon->taxon_id)) {}
                    elseif($taxon_id = trim(@$xml->taxonomy->kingdom->taxon->taxon_id)) {}
                    $i = 0;
                    foreach(@$xml->sequences->sequence as $sequence)
                    {
                        $i++;
                        if(strlen($best_sequence) < strlen($sequence->nucleotides)) $best_sequence = trim($sequence->nucleotides);
                    }
                    if($best_sequence)
                    {
                        if(@$taxa_sequences[$taxon_id])
                        {
                            $old = $taxa_sequences[$taxon_id]["s"];
                            if(strlen($old) < strlen($best_sequence)) $taxa_sequences[$taxon_id]["s"] = $best_sequence;
                            $taxa_sequences[$taxon_id]["c"] += $i;
                        }
                        else
                        {
                            $taxa_sequences[$taxon_id]["s"] = $best_sequence;
                            $taxa_sequences[$taxon_id]["c"] = $i;
                        }
                    }
                }
            }
        }
        self::save_to_json_file($taxa_sequences, $this->SAVED_SEQUENCES_FILE);
        unlink($path); 
    }

}
?>
