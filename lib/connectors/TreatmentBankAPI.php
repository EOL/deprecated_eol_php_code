<?php
namespace php_active_record;
/* connector: [treatment_bank.php] */
class TreatmentBankAPI
{
    function __construct($folder = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->debug = array();
        $this->download_options = array(
            'resource_id'        => "TreatmentBank",
            'expire_seconds'     => false, //expires set to false for now
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        $this->service['Plazi Treatments'] = "http://tb.plazi.org/GgServer/xml.rss.xml";
        $this->service['DwCA zip download'] = "tb.plazi.org/GgServer/dwca/masterDocId.zip";
        if(Functions::is_production()) $this->path['main'] = '/extra/dumps/TreatmentBank/';
        else                           $this->path['main'] = '/Volumes/AKiTiO4/other_files/dumps/TreatmentBank/';
        $this->path['xml.rss'] = $this->path['main']."xml.rss.xml";
        // $this->path['xml.rss'] = $this->path['main']."xml.rss_OK.xml";
        
        if(!is_dir($this->path['main'])) mkdir($this->path['main']);
        if(!is_dir($this->path['main']."DwCA/")) mkdir($this->path['main']."DwCA/");
    }
    function start($from, $to)
    {   //exit("\n[$from] [$to]\n");
        self::download_XML_treatments_list();
        self::read_xml_rss($from, $to);
    }
    private function read_xml_rss($from, $to, $purpose = "download all dwca")
    {
        $local = $this->path['xml.rss'];
        $reader = new \XMLReader();
        $reader->open($local);
        $i = 0;
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "item") {
                $string = $reader->readOuterXML();
                if($xml = simplexml_load_string($string)) { $i++;
                    
                    if($purpose == "download all dwca") {
                        if($i >= $from && $i <= $to) {
                            if(($i % 1000) == 0) echo "\n[$i] ";
                            self::process_item($xml);
                            // if($i == 6) break; //debug only
                        }
                        else continue;
                    }
                    elseif($purpose == "build-up local dwca list") {
                        self::process_item_buildup_list($xml);
                        if($i == 6) break; //debug only
                    }
                }
            }
        }
        echo "\nmasterDocIds: ".count(@$this->stats['masterDocId'])."\n";
        exit("\n-stop muna-\n");
    }
    private function process_item($xml)
    {   // print_r($xml); //exit;
        /*SimpleXMLElement Object(
            [title] => Cyrtodactylus majulah Grismer & Wood & Jr & Lim 2012, new species
            [description] => Cyrtodactylus majulah Grismer & Wood & Jr & Lim 2012, new species (pages 490-496) in Grismer, L. Lee, Wood, Perry L., Jr & Lim, Kelvin K. P. 2012, Cyrtodactylus Majulah, A New Species Of Bent-Toed Gecko (Reptilia: Squamata: Gekkonidae) From Singapore And The Riau Archipelago, Raffles Bulletin of Zoology 60 (2), pages 487-499
            [link] => http://tb.plazi.org/GgServer/xml/03FA87C50911FFB0FC2DFC79FB4AD551
            [pubDate] => 2021-08-29T02:36:49-02:00
            [guid] => 03FA87C50911FFB0FC2DFC79FB4AD551.xml
        )*/
        $url = $xml->link.".xml";
        debug("".$url."");
        $xml_string = Functions::lookup_with_cache($url, $this->download_options);
        $hash = simplexml_load_string($xml_string); // print_r($hash); 
        
        if($hash{"docType"} == "treatment" && $hash{"masterDocId"}) {
            // echo "\ndocType: [".$hash{"docType"}."]";
            // echo "\nmasterDocId: [".$hash{"masterDocId"}."]\n";
            $this->stats['masterDocId'][(string) $hash{"masterDocId"}] = '';
            $source = str_replace("masterDocId", $hash{"masterDocId"}, $this->service['DwCA zip download']);
            $temp_path = $this->path['main']."DwCA/".substr($hash{"masterDocId"},0,2)."/";
            if(!is_dir($temp_path)) mkdir($temp_path);
            $destination = $temp_path.$hash{"masterDocId"}.".zip";
            self::run_wget_download($source, $destination, $url);
        }
        else {
            // print_r($xml); echo("\nInvestigate, docType not a 'treatment'\n");
        }
        // exit("\n-exit hash-\n");
    }
    private function download_XML_treatments_list()
    {
        $source = $this->service['Plazi Treatments'];
        $destination = $this->path['xml.rss'];
        self::run_wget_download($source, $destination);
    }
    private function run_wget_download($source, $destination, $url = '')
    {
        if(!file_exists($destination) || filesize($destination) == 0) {
            $cmd = "wget --no-check-certificate ".$source." -O $destination"; $cmd .= " 2>&1";
            $cmd = "wget ".$source." -O $destination"; $cmd .= " 2>&1";
            debug("\nDownloading...[$cmd]\n");
            $output = shell_exec($cmd); sleep(5); //echo "\n----------\n$output\n----------\n"; //too many lines
            if(file_exists($destination) && filesize($destination)) echo "\n".$destination." downloaded successfully";
            else echo("\n[$url]\nERROR: Cannot download [$source].\n");
        }
        else {
            debug("\nFile already exists: [$destination] - ".filesize($destination)."\n");
        }
    }
    function build_up_dwca_list() //main 2nd step
    {
        $dwca_list_txt = CONTENT_RESOURCE_LOCAL_PATH."/reports/Plazi_DwCA_list.txt";
        $this->WRITE = fopen($dwca_list_txt, "w"); //initialize
        self::read_xml_rss(false, false, "build-up local dwca list");
        fclose($this->WRITE);
    }
    private function process_item_buildup_list($xml)
    {   // print_r($xml); //exit;
        /*SimpleXMLElement Object(
            [title] => Cyrtodactylus majulah Grismer & Wood & Jr & Lim 2012, new species
            [description] => Cyrtodactylus majulah Grismer & Wood & Jr & Lim 2012, new species (pages 490-496) in Grismer, L. Lee, Wood, Perry L., Jr & Lim, Kelvin K. P. 2012, Cyrtodactylus Majulah, A New Species Of Bent-Toed Gecko (Reptilia: Squamata: Gekkonidae) From Singapore And The Riau Archipelago, Raffles Bulletin of Zoology 60 (2), pages 487-499
            [link] => http://tb.plazi.org/GgServer/xml/03FA87C50911FFB0FC2DFC79FB4AD551
            [pubDate] => 2021-08-29T02:36:49-02:00
            [guid] => 03FA87C50911FFB0FC2DFC79FB4AD551.xml
        )*/
        $url = $xml->link.".xml"; // debug("".$url."");
        $xml_string = Functions::lookup_with_cache($url, $this->download_options);
        $hash = simplexml_load_string($xml_string); // print_r($hash); 
        if($hash{"docType"} == "treatment" && $hash{"masterDocId"}) {
            // echo "\ndocType: [".$hash{"docType"}."]";
            // echo "\nmasterDocId: [".$hash{"masterDocId"}."]\n";
            $masterDocId = (string) $hash{"masterDocId"};
            $source = str_replace("masterDocId", $masterDocId, $this->service['DwCA zip download']);
            $temp_path = $this->path['main']."DwCA/".substr($hash{"masterDocId"},0,2)."/";
            if(!is_dir($temp_path)) mkdir($temp_path);
            $destination = $temp_path.$masterDocId.".zip";
            if(file_exists($destination) && filesize($destination) && !isset($this->stats['masterDocId'][$masterDocId])) {
                $this->stats['masterDocId'][$masterDocId] = '';
                echo("\n$destination -- [".filesize($destination)."]");
                fwrite($this->WRITE, $destination."\n");
            }
        }
        // else { print_r($xml); echo("\nInvestigate, docType not a 'treatment'\n"); }
    }
    
    /* copied template
    private function create_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['specie_id'];
        $taxon->scientificName  = $rec['specie_name'];
        $taxon->kingdom = 'Animalia';
        $taxon->phylum = 'Cnidaria';
        $taxon->class = 'Anthozoa';
        $taxon->order = 'Scleractinia';
        // $taxon->taxonRank             = '';
        // $taxon->furtherInformationURL = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function load_zip_contents()
    {
        $options = $this->download_options;
        $options['file_extension'] = 'zip';
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($local_zip_file = Functions::save_remote_file_to_local($this->partner_source_csv, $options)) {
            $output = shell_exec("unzip -o $local_zip_file -d $this->TEMP_FILE_PATH");
            if(file_exists($this->TEMP_FILE_PATH . "/".$this->download_version."/".$this->download_version."_data.csv")) {
                $this->text_path["data"] = $this->TEMP_FILE_PATH . "/$this->download_version/".$this->download_version."_data.csv";
                $this->text_path["resources"] = $this->TEMP_FILE_PATH . "/$this->download_version/".$this->download_version."_resources.csv";
                print_r($this->text_path);
                echo "\nlocal_zip_file: [$local_zip_file]\n";
                unlink($local_zip_file);
                return TRUE;
            }
            else return FALSE;
        }
        else {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return FALSE;
        }
    }
    private function clean_html($arr)
    {
        $delimeter = "elicha173";
        $html = implode($delimeter, $arr);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("> |", ">", $html);
        $arr = explode($delimeter, $html);
        return $arr;
    }
    */
}
?>