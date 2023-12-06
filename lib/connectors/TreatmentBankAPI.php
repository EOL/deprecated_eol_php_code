<?php
namespace php_active_record;
/* connector: [treatment_bank.php]
Below is the algorithm for this connector: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=66362&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66362
Hi Jen,
I think I now got a pathway to harvest their complete data.
From their API page you can obtain a list of all the treatments available from Plazi using:
http://tb.plazi.org/GgServer/xml.rss.xml

    - this XML is too big to load in browser for investigation
    - download it locally using: wget http://tb.plazi.org/GgServer/xml.rss.xml
    - it lists 611,618 (778,468) (779,222) treatments and the corresponding metadata file
    e.g. metadata http://tb.plazi.org/GgServer/xml/03FA87C50911FFB0FC2DFC79FB4AD551.xml
    From this metadata XML you can filter docType="treatment".
    And get the masterDocId="FFC3FFBD0912FFB9FF8BFFEAFFFDD364".
    Now you can get the DwCA using the masterDocId.
    e.g.
    tb.plazi.org/GgServer/dwca/FFC3FFBD0912FFB9FF8BFFEAFFFDD364.zip
    - from the DwCA, the eml.xml is a good source for attribution
    - Jen at this point, my concern is the taxa.txt (for names) and the media.txt to do textmining? Is that correct?

Thanks.
PS: I find the GBIF path incomplete and I assume not updated.
*/
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
        // else                        $this->path['main'] = '/Volumes/AKiTiO4/other_files/dumps/TreatmentBank/';       //old
        else                           $this->path['main'] = '/Volumes/Crucial_2TB/other_files2/dumps/TreatmentBank/';  //new

        $this->path['xml.rss'] = $this->path['main']."xml.rss.xml";
        // $this->path['xml.rss'] = $this->path['main']."xml.rss_OK.xml";
        
        if(!is_dir($this->path['main'])) mkdir($this->path['main']);
        if(!is_dir($this->path['main']."DwCA/")) mkdir($this->path['main']."DwCA/");

        $path = CONTENT_RESOURCE_LOCAL_PATH."/reports/TreatmentBank";
        if(!is_dir($path)) mkdir($path);
        $this->dwca_list_txt = $path . "/Plazi_DwCA_list.txt";
        
        /* Some notes:
        From XML rss:
        <item>
        <title>Scotina celans Blackwall 1841</title>
        <description>Scotina celans Blackwall 1841 (pages 85-85) in Paschetta, Mauro, Christille, Claretta, Marguerettaz, Fabio &amp; Isaia, Marco 2016, 
        Regional catalogue of the spiders (Arachnida, Araneae) of Aosta Valley (NW Italy), Zoosystema 38 (1), pages 49-125</description>
        <link>http://tb.plazi.org/GgServer/xml/475887A6ED003862C1489F0C8CADFBD4</link>
        <pubDate>2021-03-03T16:40:41-02:00</pubDate>
        <guid isPermaLink="false">475887A6ED003862C1489F0C8CADFBD4.xml</guid>
        </item>
        
        "Paschetta, Mauro, Christille, Claretta, Marguerettaz, Fabio & Isaia, Marco, 2016, 
        Regional catalogue of the spiders (Arachnida, Araneae) of Aosta Valley (NW Italy)". 
        
        From: http://tb.plazi.org/GgServer/xml/475887A6ED003862C1489F0C8CADFBD4
        
        <document id="BA54B2DC762A1AE50259E4E42ECA063C" ID-DOI="http://doi.org/10.5281/zenodo.4578738" ID-ISSN="1638-9387" ID-Zenodo-Dep="4578738" ID-ZooBank="urn:lsid:zoobank.org:pub:0F3B35C3-FB21-40C4-915E-2C7C4712CD9F" _generate="added" approvalRequired="443" approvalRequired_for_taxonomicNames="26" approvalRequired_for_textStreams="384" approvalRequired_for_treatments="33" checkinTime="1614789632185" checkinUser="felipe" 
        docAuthor="Paschetta, Mauro, Christille, Claretta, Marguerettaz, Fabio & Isaia, Marco" 
        docDate="2016" docId="475887A6ED003862C1489F0C8CADFBD4" docLanguage="en" docName="Zoosystema.38.1.49-125.pdf" docOrigin="Zoosystema 38 (1)" docSource="http://dx.doi.org/10.5252/z2016n1a3" docStyle="DocumentStyle:0AF8C315773078909029C6FC3CC05C6C.1:Zoosystema.2015-2017.journal_article" docStyleId="0AF8C315773078909029C6FC3CC05C6C" docStyleName="Zoosystema.2015-2017.journal_article" docStyleVersion="1" 
        docTitle="Scotina celans Blackwall 1841" docType="treatment" docVersion="3" 
        lastPageNumber="85" masterDocId="BB61FFDEED243846C05F9C498E34FFF6" 
        masterDocTitle="Regional catalogue of the spiders (Arachnida, Araneae) of Aosta Valley (NW Italy)" 
        masterLastPageNumber="125" masterPageNumber="49" pageId="36" 
        pageNumber="85" updateTime="1614800640157" updateUser="ExternalLinkService" zenodo-license-document="CC0-1.0" zenodo-license-figures="CC0-1.0" zenodo-license-treatments="UNSPECIFIED">
        
        BB61FFDEED243846C05F9C498E34FFF6
        tb.plazi.org/GgServer/dwca/BB61FFDEED243846C05F9C498E34FFF6.zip
        */
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
                        // /* main operation - uncommentn in real operation
                        if(($i % 5000) == 0) echo "[$i] ";
                        self::process_item_buildup_list($xml);
                        // if($i == 10) break; //debug only                        
                        // */

                        /* debug only , during dev only
                        $from = 25000; $to = 30000; //range with many en
                        if($i >= $from && $i <= $to) {
                            self::process_item_buildup_list($xml);
                        }
                        */                        
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
        )
        as of Nov 30, 2023:
        SimpleXMLElement Object(
            [title] => Erro Darby 2017
            [description] => Erro Darby 2017 (pages 10-10) in Darby, Michael 2019, New Ptiliidae (Coleoptera) from Sarawak in the spirit collection of the Natural History Museum, London, European Journal of Taxonomy 512, pages 1-50
            [link] => http://tb.plazi.org/GgServer/xml/BE2487B5FF9AFFDD6D847C934E85FD2C
            [pubDate] => 2019-04-04T16:06:30-02:00
            [guid] => BE2487B5FF9AFFDD6D847C934E85FD2C.xml
        )*/
        $url = $xml->link.".xml";
        debug("".$url."");
        $xml_string = Functions::lookup_with_cache($url, $this->download_options);
        $hash = simplexml_load_string($xml_string); // print_r($hash); 
        
        if($hash{"docType"} == "treatment" && $hash{"masterDocId"} && $hash{"docLanguage"} == "en") {
            // echo "\ndocType: [".$hash{"docType"}."]";
            // echo "\nmasterDocId: [".$hash{"masterDocId"}."]\n";

            $masterDocId = (string) $hash{"masterDocId"};
            $this->stats['masterDocId'][$masterDocId] = '';
            // ---------------------
            $ret = self::generate_source_destination($masterDocId);
            $source = $ret['source']; $destination = $ret['destination'];
            // ---------------------
            self::run_wget_download($source, $destination, $url);
        }
        else {
            // print_r($xml); echo("\nInvestigate, docType not a 'treatment'\n");
        }
        // exit("\n-exit hash-\n");
    }
    private function generate_source_destination($masterDocId)
    {
            $source = str_replace("masterDocId", $masterDocId, $this->service['DwCA zip download']);
            $temp_path = $this->path['main']."DwCA/".substr($masterDocId,0,2)."/";
            if(!is_dir($temp_path)) mkdir($temp_path);
            $destination = $temp_path.$masterDocId.".zip";
            return array('source' => $source, 'destination' => $destination);
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
            $output = shell_exec($cmd); sleep(2); //echo "\n----------\n$output\n----------\n"; //too many lines
            if(file_exists($destination) && filesize($destination)) {
                debug("\n".$destination." downloaded successfully");
                echo " OK ";
            }
            else echo("\n[$url]\nERROR: Cannot download [$source].\n");
        }
        else {
            debug("\nFile already exists: [$destination] - ".filesize($destination)."\n");
        }
    }
    function build_up_dwca_list() //main 2nd step
    {        
        $this->WRITE = fopen($this->dwca_list_txt, "w"); //initialize
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
        if($hash{"docType"} == "treatment" && $hash{"masterDocId"} && $hash{"docLanguage"} == "en") {
            // echo "\ndocType: [".$hash{"docType"}."]";
            // echo "\nmasterDocId: [".$hash{"masterDocId"}."]\n";
            $masterDocId = (string) $hash{"masterDocId"};
            // ---------------------
            $ret = self::generate_source_destination($masterDocId);
            $source = $ret['source']; $destination = $ret['destination'];
            // ---------------------
            if(file_exists($destination) && filesize($destination) && !isset($this->stats['masterDocId'][$masterDocId])) {
                $this->stats['masterDocId'][$masterDocId] = '';
                debug("\n$destination -- [".filesize($destination)."]");
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