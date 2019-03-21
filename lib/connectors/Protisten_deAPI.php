<?php
namespace php_active_record;
// connector: [protisten.php]
class Protisten_deAPI
{
    function __construct($folder)
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
        $this->download_options = array('cache' => 1, 'resource_id' => $this->resource_id, 'download_wait_time' => 1000000, 'timeout' => 1200, 'download_attempts' => 1, 'delay_in_minutes' => 2, 
                                        'expire_seconds' => 60*60*24*25);
        // $this->download_options['expire_seconds'] = false;
        // $this->download_options['user_agent'] = 'User-Agent: curl/7.39.0'; // did not work here, but worked OK in USDAfsfeisAPI.php
        $this->download_options['user_agent'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)'; //worked OK!!!
        
        $this->page['main'] = 'http://www.protisten.de/gallery_ALL/Galerie001.html';
        $this->page['pre_url'] = 'http://www.protisten.de/gallery_ALL/Galerie';
        $this->page['image_page_url'] = 'http://www.protisten.de/gallery_ALL/';
    }
    function start()
    {
        self::write_agent();
        $batches = self::get_total_batches();
        foreach($batches as $filename) {
            self::process_one_batch($filename);
            // break; //debug - process only 1 batch.
        }
        $this->archive_builder->finalize(true);
    }
    private function process_one_batch($filename)
    {
        if($html = Functions::lookup_with_cache($this->page['pre_url'].$filename, $this->download_options)) {
            if(preg_match("/<table border=\'0\'(.*?)<\/table>/ims", $html, $arr)) {
                if(preg_match_all("/<td align=\'center\'(.*?)<\/td>/ims", $arr[1], $arr2)) {
                    $rows = $arr2[1];
                    foreach($rows as $row) {
                        /*[0] =>  width='130' bgcolor='#A5A59B'><a href='2_Acanthoceras-spec.html'>2 images<br><img  width='100' height='100'  border='0'  
                        src='thumbs/Acanthoceras_040-125_P6020240-251_ODB.jpg'><br><i>Acanthoceras</i> spec.</a>
                        */
                        $rec = array();
                        if(preg_match("/href=\'(.*?)\'/ims", $row, $arr)) $rec['image_page'] = $arr[1];
                        if(preg_match("/\.jpg\'>(.*?)<\/a>/ims", $row, $arr)) $rec['taxon'] = strip_tags($arr[1]);
                        if($rec['image_page'] && $rec['taxon']) {
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
        echo "\n".$html_filename." -- ";

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
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = utf8_encode($html); //needed for this resource. May not be needed for other resources.
            $html = Functions::conv_to_utf8($html);
            $html = self::clean_str($html);
            if(preg_match("/MARK 14\:(.*?)<\/td>/ims", $html, $arr)) {
                $tmp = str_replace("&nbsp;", " ", strip_tags($arr[1]));
                if(preg_match("/\-\-\>(.*?)xxx/ims", $tmp."xxx", $arr)) $tmp = $arr[1];
                $tmp = Functions::remove_whitespace(trim($tmp));
                $m['desc'] = $tmp;
            }
            if(preg_match("/MARK 12\:(.*?)<\/td>/ims", $html, $arr)) {
                if(preg_match("/<img src=\"(.*?)\"/ims", $arr[1], $arr2)) $m['image'] = $arr2[1];
                /*
                e.g. value is:                     "pics/Acanthoceras_040-125_P6020240-251-totale_ODB.jpg"
                http://www.protisten.de/gallery_ALL/pics/Acanthoceras_040-125_P6020240-251-totale_ODB.jpg
                */
            }
            if(preg_match("/MARK 13\:(.*?)<\/td>/ims", $html, $arr)) {
                $tmp = str_replace("&nbsp;", " ", strip_tags($arr[1]));
                if(preg_match("/\-\-\>(.*?)xxx/ims", $tmp."xxx", $arr)) $tmp = $arr[1];
                $tmp = Functions::remove_whitespace(trim($tmp));
                $m['sciname'] = $tmp;
            }
            // print_r($m);
        }
        return $m;
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
            // if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
            // $taxon->family                  = (string) @$rec['family'];
            // $taxon->taxonRank               = (string) $rec['rank'];
            $r['taxon_id'] = md5($r['sciname']);
            $r['source_url'] = $this->page['image_page_url'].@$rec['next_pages'][$i];
            $taxon->taxonID                 = $r['taxon_id'];
            $taxon->scientificName          = $r['sciname'];
            // $taxon->taxonRank                = @$rec['rank']; //from PCAT
            // $taxon->scientificNameAuthorship = @$rec['Author']; //from PCAT
            $taxon->furtherInformationURL   = $r['source_url'];
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
            if(@$r['image']) self::write_image($r);
        }
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
        $mr->furtherInformationURL  = $rec['source_url'];
        $mr->accessURI              = $this->page['image_page_url'].$rec['image'];
        $mr->Owner                  = "Wolfgang Bettighofer";
        $mr->UsageTerms             = "http://creativecommons.org/licenses/by-nc/3.0/";
        $mr->description            = @$rec["desc"];
        if(!isset($this->obj_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->obj_ids[$mr->identifier] = '';
        }
    }
}
?>