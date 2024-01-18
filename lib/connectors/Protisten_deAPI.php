<?php
namespace php_active_record;
// connector: [protisten.php]
// http://content.eol.org/resources/791
class Protisten_deAPI
{
    function __construct($folder, $param)
    {
        $this->resource_id = $folder;
        $this->domain = "http://www.phorid.net/diptera/";
        $this->taxa_list_url     = $this->domain . "diptera_index.html";
        $this->phoridae_list_url = $this->domain . "lower_cyclorrhapha/phoridae/phoridae.html";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->do_ids = array();
        $this->download_options = array('cache' => 1, 'resource_id' => $this->resource_id, 'download_wait_time' => 1000000, 'timeout' => 1200); //jenkins harvest monthly
        // 'download_attempts' => 1, 'delay_in_minutes' => 2, 
        if($val = @$param['expire_seconds']) $this->download_options['expire_seconds'] = $val;
        else                                 $this->download_options['expire_seconds'] = 60*60*12; //half day
        print_r($this->download_options); //exit;

        // $this->download_options['user_agent'] = 'User-Agent: curl/7.39.0'; // did not work here, but worked OK in USDAfsfeisAPI.php
        $this->download_options['user_agent'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)'; //worked OK!!!
        
        $this->page['main']           = 'http://www.protisten.de/gallery-ALL/Galerie001.html';
        $this->page['pre_url']        = 'http://www.protisten.de/gallery-ALL/Galerie';
        $this->page['image_page_url'] = 'http://www.protisten.de/gallery-ALL/';
        /* Google sheet used: This is sciname mapping to EOL PageID. Initiated by Wolfgang Bettighofer.
        https://docs.google.com/spreadsheets/d/1QnT-o-t4bVp-BP4jFFA-Alr4PlIj7fAD6RRb5iC6BYA/edit#gid=0
        */
        /* obsolete
        $this->stable_urls = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/protisten_de/EOL_ELI_gallery-ARCHIVE_2023-04-18.tsv";
        */
    }
    function start()
    {   
        /* obsolete
        self::get_stable_urls_info();
        */
        $this->stable_urls_info = array();

        self::taxon_mapping_from_GoogleSheet();
        self::write_agent();
        $batches = self::get_total_batches(); print_r($batches);
        foreach($batches as $filename) {
            echo "\nprocess batch [$filename]\n";
            self::process_one_batch($filename);
            // break; //debug - process only 1 batch.
        }
        $this->archive_builder->finalize(true);
        if(isset($this->debug)) print_r($this->debug);
        if(!@$this->debug['does not exist']) echo "\n--No broken images!--\n";
    }
    private function process_one_batch($filename)
    {   
        // $url = 'http://www.protisten.de/gallery-ALL/Galerie022.html'; //debug only - force
                // http://www.protisten.de/gallery-ALL/Galerie001.html
        $url = $this->page['pre_url'].$filename;
        echo "\nProcessing ".$url."\n";
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match_all("/<table border=\'0\'(.*?)<\/table>/ims", $html, $arr)) { //this gives 2 records, we use the 2nd one
                $cont = $arr[1];
                $cont = $cont[1]; // 2nd record
                if(preg_match_all("/<td align=\'center\'(.*?)<\/td>/ims", $cont, $arr2)) {
                    $rows = $arr2[1];
                    foreach($rows as $row) { //exit("\nditox 5\n");
                        /*[0] =>  width='130' bgcolor='#A5A59B'><a href='2_Acanthoceras-spec.html'>2 images<br><img  width='100' height='100'  border='0'  
                        src='thumbs/Acanthoceras_040-125_P6020240-251_ODB.jpg'><br><i>Acanthoceras</i> spec.</a>
                        */
                        // print_r($rows); exit;

                        $rec = array();
                        if(preg_match("/href=\'(.*?)\'/ims", $row, $arr)) $rec['image_page'] = $arr[1];
                        if(preg_match("/\.jpg\'>(.*?)<\/a>/ims", $row, $arr)) $rec['taxon'] = strip_tags($arr[1]);
                        // print_r($rec); exit;
                        if(@$rec['image_page'] && @$rec['taxon']) {
                            self::parse_image_page($rec);
                        }
                    }
                }
            }
        }
    }
    private function parse_image_page($rec)
    {
        $html_filename = $rec['image_page'];
        // echo "\n".$html_filename." -- ";

        $this->filenames = array();
        $this->filenames[] = $html_filename;
        
        $rec['next_pages'] = self::get_all_next_pages($this->page['image_page_url'].$html_filename);
        $rec['media_info'] = self::get_media_info($rec);
        if($rec['media_info']) self::write_archive($rec);
    }
    private function get_media_info($rec)
    {
        $media_info = array();
        if($pages = @$rec['next_pages']) {
            foreach($pages as $html_filename) {
                if($val = self::parse_media($this->page['image_page_url'].$html_filename)) $media_info[] = $val;
            }
        }
        return $media_info;
    }
    private function parse_media($url)
    {
        $m = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = utf8_encode($html); //needed for this resource. May not be needed for other resources.
            $html = Functions::conv_to_utf8($html);
            $html = self::clean_str($html);
            if(preg_match("/MARK 14\:(.*?)<\/td>/ims", $html, $arr)) {
                $tmp = str_replace("&nbsp;", " ", strip_tags($arr[1]));
                if(preg_match("/\-\-\>(.*?)~~~/ims", $tmp."~~~", $arr)) $tmp = $arr[1];
                $tmp = Functions::remove_whitespace(trim($tmp));
                $m['desc'] = $tmp;
            }
            if(preg_match("/MARK 12\:(.*?)<\/td>/ims", $html, $arr)) {
                if(preg_match("/<img src=\"(.*?)\"/ims", $arr[1], $arr2)) $m['image'] = $arr2[1];
                /*
                e.g. value is:                     "pics/Acanthoceras_040-125_P6020240-251-totale_ODB.jpg"
                http://www.protisten.de/gallery-ALL/pics/Acanthoceras_040-125_P6020240-251-totale_ODB.jpg
                */
            }
            if(preg_match("/MARK 13\:(.*?)<\/td>/ims", $html, $arr)) {
                $tmp = str_replace("&nbsp;", " ", strip_tags($arr[1]));
                if(preg_match("/\-\-\>(.*?)~~~/ims", $tmp."~~~", $arr)) $tmp = $arr[1];
                $tmp = str_ireplace(' spec.', '', $tmp);
                $tmp = Functions::remove_whitespace(trim($tmp));
                $m['sciname'] = $tmp;
            }
            if(preg_match("/MARK 10\:(.*?)<\/td>/ims", $html, $arr)) {
                $tmp = str_replace("&nbsp;", " ", strip_tags($arr[1]));
                if(preg_match("/\-\-\>(.*?)~~~/ims", $tmp."~~~", $arr)) $tmp = Functions::remove_whitespace($arr[1]);
                // echo "\n[".$tmp."]\n";
                $arr = explode(":",$tmp);
                $arr = array_map('trim', $arr);
                // print_r($arr);
                $m['ancestry'] = $arr;
                
                $tmp = array_pop($arr); //last element
                $m['parent_id'] = self::format_id($arr[count($arr)-1])."-".self::format_id($tmp); //combination of last 2 immediate parents
                // echo "\n$parent\n"; exit;
            }
            // print_r($m); exit;
        }
        if(@$m['sciname'] && @$m['image']) return $m;
        else return array();
    }
    private function format_id($id)
    {
        return strtolower(str_replace(" ", "_", $id));
    }
    private function clean_str($str)
    {
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011", "", ""), " ", trim($str));
        return trim($str);
    }
    private function get_all_next_pages($url)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = str_replace('href="yyy.html"', "", $html);
            /*MARK 6: a href="yyy.html" (Nachfolger) -->
            	   <a href="3_Acanthoceras-spec.html"
            */
            if(preg_match("/MARK 6\:(.*?)target/ims", $html, $arr)) {
                $tmp = $arr[1];
                if(preg_match("/href=\"(.*?)\"/ims", $tmp, $arr2)) {
                    if($html_filename = $arr2[1]) {
                        if(!in_array($html_filename, $this->filenames)) {
                            $this->filenames[] = $html_filename;
                            self::get_all_next_pages($this->page['image_page_url'].$html_filename);
                        }
                        // else return $this->filenames;
                    }
                    // else return $this->filenames;
                }
            }
            // else return $this->filenames;
        }
        return $this->filenames;
    }
    private function get_total_batches()
    {
        if($html = Functions::lookup_with_cache($this->page['main'], $this->download_options)) {
            //<a href='Galerie001.html'>
            if(preg_match_all("/href=\'Galerie(.*?)\'/ims", $html, $arr)) {
                return $arr[1];
            }
        }
    }
    private function write_archive($rec)
    {
        // print_r($rec); exit;
        /* [media_info] => Array(
            [0] => Array(
                    [desc] => Scale bar indicates 50 µm. The specimen was gathered in the wetlands of national park Unteres Odertal (100 km north east of Berlin). The image was built up using several photomicrographic frames with manual stacking technique. Images were taken using Zeiss Universal with Olympus C7070 CCD camera. Image under Creative Commons License V 3.0 (CC BY-NC-SA). Der Messbalken markiert eine Länge von 50 µm. Die Probe wurde in den Feuchtgebieten des Nationalpark Unteres Odertal (100 km nordöstlich von Berlin) gesammelt. Mikrotechnik: Zeiss Universal, Kamera: Olympus C7070. Creative Commons License V 3.0 (CC BY-NC-SA). For permission to use of (high-resolution) images please contact postmaster@protisten.de.
                    [image] => pics/Acanthoceras_040-125_P6020240-251-totale_ODB.jpg
                    [sciname] => Acanthoceras spec.
                )
        */
        $i = -1;
        foreach($rec['media_info'] as $r) { $i++;
            $taxon = new \eol_schema\Taxon();
            $r['taxon_id'] = md5($r['sciname']);
            $r['source_url'] = $this->page['image_page_url'].@$rec['next_pages'][$i];
            $taxon->taxonID                 = $r['taxon_id'];
            $taxon->scientificName          = $r['sciname'];
            
            if($EOLid = @$this->taxon_EOLpageID[$r['sciname']]) $taxon->EOLid = $EOLid; // http://eol.org/schema/EOLid
            if(isset($this->remove_scinames[$r['sciname']])) continue;
            
            $taxon->parentNameUsageID       = $r['parent_id'];
            $taxon->furtherInformationURL   = $r['source_url'];
            // $taxon->taxonRank                = '';
            $taxon->higherClassification    = implode("|", $r['ancestry']);
            // echo "\n$taxon->higherClassification\n";
            // if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
            if($val = @$r['ancestry']) self::create_taxa_for_ancestry($val, $taxon->parentNameUsageID);
            if(@$r['image']) self::write_image($r);
        }
    }
    private function create_taxa_for_ancestry($ancestry, $parent_id)
    {
        // echo "\n$parent_id\n";
        // print_r($ancestry);
        //store taxon_id and parent_id
        $i = -1; $store = array();
        foreach($ancestry as $sci) {
            $i++;
            if($i == 0) $taxon_id = self::format_id($sci);
            else        $taxon_id = self::format_id($ancestry[$i-1])."-".self::format_id($sci);
            $store[] = $taxon_id;
        }
        // print_r($store);
        //write to dwc
        $i = -1;
        foreach($ancestry as $sci) {
            $i++;
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                 = $store[$i];
            $taxon->scientificName          = $sci;
            
            if($EOLid = @$this->taxon_EOLpageID[$sci]) $taxon->EOLid = $EOLid; // http://eol.org/schema/EOLid
            
            $taxon->parentNameUsageID       = @$store[$i-1];
            $taxon->higherClassification    = self::get_higherClassification($ancestry, $i);
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
        }
    }
    private function get_higherClassification($ancestry, $i)
    {
        $j = -1; $final = array();
        foreach($ancestry as $sci) {
            $j++;
            if($j < $i) $final[] = $sci;
        }
        return implode("|", $final);
    }
    private function write_agent()
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = 'Wolfgang Bettighofer';
        $r->agentRole       = 'creator';
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        $r->term_homepage   = 'http://www.protisten.de/english/index.html';
        $r->term_mbox       = 'Wolfgang.Bettighofer@gmx.de';
        $this->archive_builder->write_object_to_file($r);
        $this->agent_id = array($r->identifier);
    }
    private function write_image($rec)
    {
        $mr = new \eol_schema\MediaResource();
        $mr->agentID                = implode("; ", $this->agent_id);
        $mr->taxonID                = $rec["taxon_id"];
        $mr->identifier             = md5($rec['image']);
        $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
        $mr->language               = 'en';
        $mr->format                 = Functions::get_mimetype($rec['image']);
        $this->debug['mimetype'][$mr->format] = '';

        $mr->accessURI              = self::format_accessURI($this->page['image_page_url'].$rec['image']);
        
        // /* New: Jun 13,2023
        if(!self::image_exists_YN($mr->accessURI)) {
            $this->debug['does not exist'][$mr->accessURI] = '';
            return;
        }
        // */
        
        $mr->furtherInformationURL  = self::format_furtherInfoURL($rec['source_url'], $mr->accessURI, $mr);
        $mr->Owner                  = "Wolfgang Bettighofer";
        $mr->UsageTerms             = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $mr->description            = @$rec["desc"];
        if(!isset($this->obj_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->obj_ids[$mr->identifier] = '';
        }
    }
    private function image_exists_YN($image_url)
    {   /* curl didn't work
        // Initialize cURL
        $ch = curl_init($image_url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // Check the response code
        if($responseCode == 200) return true;  //echo 'File exists';
        else                     return false; //echo 'File not found';
        */

        // /* fopen worked spledidly OK
        // Open file
        $handle = @fopen($image_url, 'r');
        // Check if file exists
        if(!$handle) return false; //echo 'File not found';
        else         return true; //echo 'File exists';
        // */
    }
    private function format_furtherInfoURL($source_url, $accessURI, $mr) //3rd param for debug only
    {
        /*
        Your column D e.g.
        https://www.protisten.de/gallery-ARCHIVE/gallery-ARCHIVE/pics/Zivkovicia-spectabilis-010-200-0-7054665-683-HHW.jpg.html
        My media URL e.g.
        https://www.protisten.de/gallery-ARCHIVE/pics/Zivkovicia-spectabilis-010-200-0-7054665-683-HHW.jpg
        */

        /* obsolete
        if($final = @$this->stable_urls_info[$accessURI]) return $final;
        else {
            // echo "\n----------not found in Wolfgang's spreadsheet\n";
            // print_r($mr);
            // print_r($this->stable_urls_info); //good debug
            // echo "\n[".$accessURI."]\n";
            // echo "\n[".$mr->accessURI."]\n";
            // echo "\n----------\n"; //exit;

            $this->debug['not found in Wolfgang spreadsheet'][$accessURI] = '';
            $this->debug['not found in Wolfgang spreadsheet'][$mr->accessURI] = '';

            return $source_url; //return the non-stable URL but currently working
        }
        */
        return $source_url; //return the non-stable URL but currently working
    }
    private function format_accessURI($url)
    {   /*
        https://www.protisten.de/gallery-ALL/pics/Penium-polymorphum-var-polymorphum-040-200-2-B090576-593-transversal4-WPT.jpg
        https://www.protisten.de/gallery-ARCHIVE/pics/Penium-polymorphum-var-polymorphum-040-200-2-B090576-593-transversal4-WPT.jpg
        */
        $url = str_replace("/gallery-ALL/pics/", "/gallery-ARCHIVE/pics/", $url);

        // http://www.protisten.de/gallery-ALL/../gallery-ARCHIVE/pics/Cocconeis-pediculus-040-200-2-2088285-303-transversal-FUS.jpg
        $url = str_replace("/gallery-ALL/..", "", $url);
        $url = str_replace("http://", "https://", $url);
        $url = str_replace(" ", "", $url);
        // return $url;
        // at this point $url is: https://www.protisten.de/gallery-ARCHIVE/pics/micrasterias-truncata3-jwbw.jpg

        /* as of Dec 29, 2023
        Hi Eli, 
        (only) in the part "gallery-ARCHIVE/pics/" all images now have the trailer "_NEW", e.g.
        https://www.protisten.de/gallery-ARCHIVE/pics/Acineta-flava-025-100-5308794-812-ODB_NEW.jpg
        and all the html pages in https://www.protisten.de/gallery-ARCHIVE/ address the new filenames:
        <td colspan="2"  align="left" width="400"><img src="../gallery-ARCHIVE/pics/Acineta-flava-025-100-5308794-812-ODB_NEW.jpg"></td>
        Can you work with this?
        Wolfgang */
        $url = self::add_NEW_if_needed($url);
        return $url;
    }
    private function add_NEW_if_needed($url)
    {
        if(stripos($url, "gallery-ARCHIVE/pics/") !== false) { //string is found
            $filename = pathinfo($url, PATHINFO_FILENAME);
            $last_4chars = substr($filename, -4);
            if($last_4chars != "_NEW") {
                $new_filename = $filename."_NEW";
                $url = str_replace($filename, $new_filename, $url);
            }
        }
        return $url;
    }
    /* obsolete
    function get_stable_urls_info()
    {
        $local_tsv = Functions::save_remote_file_to_local($this->stable_urls, $this->download_options);
        $i = 0;
        foreach(new FileIterator($local_tsv) as $line_number => $line) {
            $i++;
            $row = explode("\t", $line);
            if($i == 1) $fields = $row;
            else {
                $k = -1;
                $rec = array();
                foreach($fields as $field) { $k++;
                    $rec[$field] = @$row[$k];
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); break; exit;
                // Array(
                //     [Taxon] => Acanthoceras spec.
                //     [EOL page] => https://eol.org/pages/92738
                //     [furtherInfoURL] => https://www.protisten.de/gallery-ARCHIVE/Acanthoceras-spec-parch2022-2.html
                //     [mediaURL] => https://www.protisten.de/gallery-ARCHIVE/pics/Acanthoceras-040-125-P6020240-251-totale-ODB.jpg
                //     [] => 
                // )
                $furtherInfoURL = $rec['furtherInfoURL'];
                $furtherInfoURL = str_replace('"', '', $furtherInfoURL);

                $mediaURL = $rec['mediaURL'];
                $mediaURL = str_replace('"', '', $mediaURL);

                // wrong entry from Wolfgang's spreadsheet
                // https://www.protisten.de/gallery-ARCHIVE/gallery-ALL/pics/Vorticella-040-125-2-3189016-017-AQU.jpg
                $mediaURL = str_replace("gallery-ALL/", "", $mediaURL);
                // https://www.protisten.de/gallery-ARCHIVE/pics/Trachelomonas- granulosa -040-200-2-06157084-100-5-Augenfleck-ASW.jpg
                $mediaURL = str_replace(" ", "", $mediaURL);

                $this->stable_urls_info[$mediaURL] = $furtherInfoURL;
            }
        }
        unlink($local_tsv);
        // print_r($this->stable_urls_info); echo "\n".count($this->stable_urls_info)."\n";
    } */
    private function taxon_mapping_from_GoogleSheet()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1QnT-o-t4bVp-BP4jFFA-Alr4PlIj7fAD6RRb5iC6BYA';
        $params['range']         = 'Sheet1!A2:D70'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params); // print_r($arr); exit;
        /*Array(
            [0] => Array(
                    [0] => Actinotaenium clevei
                    [1] => https://eol.org/pages/913594
                )
            [1] => Array(
                    [0] => Ankistrodesmus gracilis
                    [1] => https://eol.org/pages/6051692
            [37] => Array(
                    [0] => Edaphoallogromia australica
                    [1] => https://eol.org/pages/12155574
                    [2] => Lieberkuehnia wageneri
                    [3] => https://eol.org/pages/39306525
                )
        */
        foreach($arr as $rec) {
            $this->taxon_EOLpageID[$rec[0]] = pathinfo($rec[1], PATHINFO_BASENAME);
            if($val = @$rec[2]) $this->remove_scinames[$val] = '';
        }
        print_r($this->taxon_EOLpageID);
        print_r($this->remove_scinames); //exit;
    }
}
?>