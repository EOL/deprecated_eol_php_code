<?php
namespace php_active_record;
// connector: [media_convert.php]
class MediaConvertAPI
{
    function __construct($archive_builder = false, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->domain = "http://www.phorid.net/diptera/";
        $this->taxa_list_url     = $this->domain . "diptera_index.html";
        $this->phoridae_list_url = $this->domain . "lower_cyclorrhapha/phoridae/phoridae.html";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->do_ids = array();
        $this->download_options = array('cache' => 1, 'resource_id' => $this->resource_id, 'download_wait_time' => 500000, 'timeout' => 1200, 
        // 'download_attempts' => 1, 'delay_in_minutes' => 2, 
                                        'expire_seconds' => 60*60*24*30*3); //harvest quarterly
        // $this->download_options['expire_seconds'] = false;
        // $this->download_options['user_agent'] = 'User-Agent: curl/7.39.0'; // did not work here, but worked OK in USDAfsfeisAPI.php
        $this->download_options['user_agent'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)'; //worked OK!!!

        $this->path['source']       = '/extra/other_files/EOL_media/';
        $this->path['destination']  = '/extra/other_files/EOL_media_tmp/';

        $this->path['source']       = '/Volumes/AKiTiO4/web/cp/EOL_media/';
        $this->path['destination']  = '/Volumes/AKiTiO4/web/cp/EOL_media_tmp/';
        /*
        split.gz_aa is => do2.sql.gz => do2.sql

        1. split:
        $ tar cz EOL_media_tmp | split -b 300000000 - ./split.gz_
        2. then download each file locally and merge:
        $ cat split.gz_* | tar xz
        
wget https://editors.eol.org/other_files/split.gz_br
wget https://editors.eol.org/other_files/split.gz_bq
wget https://editors.eol.org/other_files/split.gz_bp
wget https://editors.eol.org/other_files/split.gz_bo
wget https://editors.eol.org/other_files/split.gz_bn
wget https://editors.eol.org/other_files/split.gz_bm
wget https://editors.eol.org/other_files/split.gz_bl
wget https://editors.eol.org/other_files/split.gz_bk
wget https://editors.eol.org/other_files/split.gz_bj
wget https://editors.eol.org/other_files/split.gz_bi
wget https://editors.eol.org/other_files/split.gz_bh
wget https://editors.eol.org/other_files/split.gz_bg
wget https://editors.eol.org/other_files/split.gz_bf
wget https://editors.eol.org/other_files/split.gz_be
wget https://editors.eol.org/other_files/split.gz_bd
wget https://editors.eol.org/other_files/split.gz_bc
wget https://editors.eol.org/other_files/split.gz_bb
wget https://editors.eol.org/other_files/split.gz_ba
wget https://editors.eol.org/other_files/split.gz_az
wget https://editors.eol.org/other_files/split.gz_ay
wget https://editors.eol.org/other_files/split.gz_ax
wget https://editors.eol.org/other_files/split.gz_aw
wget https://editors.eol.org/other_files/split.gz_av
wget https://editors.eol.org/other_files/split.gz_au
wget https://editors.eol.org/other_files/split.gz_at
wget https://editors.eol.org/other_files/split.gz_as
wget https://editors.eol.org/other_files/split.gz_ar
wget https://editors.eol.org/other_files/split.gz_aq
wget https://editors.eol.org/other_files/split.gz_ap
wget https://editors.eol.org/other_files/split.gz_ao
wget https://editors.eol.org/other_files/split.gz_an
wget https://editors.eol.org/other_files/split.gz_am
wget https://editors.eol.org/other_files/split.gz_al
wget https://editors.eol.org/other_files/split.gz_ak
wget https://editors.eol.org/other_files/split.gz_aj
wget https://editors.eol.org/other_files/split.gz_ai
wget https://editors.eol.org/other_files/split.gz_ah
wget https://editors.eol.org/other_files/split.gz_ag
wget https://editors.eol.org/other_files/split.gz_af
wget https://editors.eol.org/other_files/split.gz_ae
wget https://editors.eol.org/other_files/split.gz_ad
wget https://editors.eol.org/other_files/split.gz_ac

wget https://editors.eol.org/other_files/split.gz_ab
wget https://editors.eol.org/other_files/split.gz_aa
        */
        
    }
    function convert_mov_2_mp4() //a utility
    {
        $dir_to_process = $this->path['destination'];
        if($dir = opendir($dir_to_process)) {
            while(false !== ($subdir = readdir($dir))) {
                if(!in_array($subdir, array(".",".."))) {
                    echo "\n[$subdir]";
                    $files = $dir_to_process.$subdir."/*.mov";
                    foreach (glob($files) as $filename) {
                        if(filesize($filename)) {
                            echo "\n[$filename] - "; //good debug
                            $source = $filename;
                            $target = str_replace(".mov", ".mp4", $filename);
                            if(!file_exists($target)) {
                                shell_exec("ffmpeg -i $source $target");
                            }
                        }
                    }
                }
            }
        }
        
    }
    function move_movie_files() //a utility
    {
        $dir_to_process = $this->path['source'];
        if($dir = opendir($dir_to_process)) {
            while(false !== ($subdir = readdir($dir))) {
                if(!in_array($subdir, array(".",".."))) {
                    echo "\n[$subdir]";
                    $files = $dir_to_process.$subdir."/*.mov";
                    foreach (glob($files) as $filename) {
                        if(filesize($filename)) {
                            echo "\n[$filename] - "; //good debug
                            if(!file_exists($this->path['destination'].$subdir)) {
                                if(mkdir($this->path['destination'].$subdir)) echo "\n - folder created [$subdir]";
                            }
                            $source = $filename;
                            $target = str_replace("EOL_media", "EOL_media_tmp", $filename);
                            if(!file_exists($target)) {
                                if(copy($source, $target)) echo "\n file copied OK [$filename]";
                            }
                        }
                    }
                }
            }
        }
    }
    function start()
    {   /*
        self::move_movie_files();
        */
        self::convert_mov_2_mp4();
    }
    /*
    private function write_archive($rec)
    {
        $i = -1;
        foreach($rec['media_info'] as $r) { $i++;
            $taxon = new \eol_schema\Taxon();
            $r['taxon_id'] = md5($r['sciname']);
            $r['source_url'] = $this->page['image_page_url'].@$rec['next_pages'][$i];
            $taxon->taxonID                 = $r['taxon_id'];
            $taxon->scientificName          = $r['sciname'];
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
        $mr->UsageTerms             = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $mr->description            = @$rec["desc"];
        if(!isset($this->obj_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->obj_ids[$mr->identifier] = '';
        }
    }
    */
}
?>