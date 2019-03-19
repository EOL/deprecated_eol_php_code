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
    }
    function start()
    {
        $batches = self::get_total_batches();
        foreach($batches as $filename) {
            $recs = self::process_one_batch($filename);
            break; //debug
        }
        exit;
        // $this->archive_builder->finalize(true);
    }
    private function process_one_batch($filename)
    {
        if($html = Functions::lookup_with_cache($this->page['pre_url'].$filename, $this->download_options)) {
            if(preg_match("/<table border=\'0\'(.*?)<\/table>/ims", $html, $arr)) {
                if(preg_match_all("/<td align=\'center\'(.*?)<\/td>/ims", $arr[1], $arr2)) {
                    print_r($arr2[1]);
                }
                
            }
        }
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
    function write_archive($rec)
    {
        $taxon = new \eol_schema\Taxon();
        // if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        // $taxon->family                  = (string) @$rec['family'];
        // $taxon->taxonRank               = (string) $rec['rank'];
        $rec['taxon'] = self::clean_sciname($rec['taxon']);
        $rec['taxon_id'] = md5($rec['taxon']);
        $taxon->taxonID                 = $rec['taxon_id'];
        $taxon->scientificName          = $rec['taxon'];
        $taxon->taxonRank                = @$rec['rank']; //from PCAT
        $taxon->scientificNameAuthorship = @$rec['Author']; //from PCAT
        $taxon->furtherInformationURL   = @$rec['source_url'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        if(@$rec['image']) self::write_image($rec);
        return $rec;
    }
    private function write_agent()
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = 'phorid.net: online data for phorid flies';
        $r->agentRole       = 'publisher';
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        $r->term_homepage   = 'http://phorid.net/index.php'; //'http://www.phorid.net/diptera/diptera_index.html';
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
        $mr->accessURI              = $rec['image'];
        $mr->Owner                  = "Diptera of Central America";
        $mr->UsageTerms             = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $mr->description            = @$rec["caption"];
        if(!isset($this->obj_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->obj_ids[$mr->identifier] = '';
        }
    }
}
?>