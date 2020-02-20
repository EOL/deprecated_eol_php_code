<?php
namespace php_active_record;
// connector: [media_convert.php]
class MediaConvertAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        if(Functions::is_production()) {
            $this->path['source']       = '/extra/other_files/EOL_media/';
            $this->path['destination']  = '/extra/other_files/EOL_media_tmp/';
        }
        else {
            $this->path['source']       = '/Volumes/AKiTiO4/web/cp/EOL_media/';
            $this->path['destination']  = '/Volumes/AKiTiO4/web/cp/EOL_media_tmp/';
        }

        /*
        1. split:
        $ tar cz EOL_media_tmp | split -b 300000000 - ./split.gz_
        2. then download each file locally and merge:
        $ cat split.gz_* | tar xz

[root@eol-archive EOL_media_tmp]# find . -type f | wc -l
6721 - files total

        1. split:
        $ tar cz EOL_media_tmp_mp4 | split -b 300000000 - ./mp4.gz_
        2. then download each file locally and merge:
        $ cat mp4.gz_* | tar xz

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

scp mp4.gz_bd archive:~/temp/mp4/.
scp mp4.gz_bc archive:~/temp/mp4/.
scp mp4.gz_bb archive:~/temp/mp4/.
scp mp4.gz_ba archive:~/temp/mp4/.
scp mp4.gz_az archive:~/temp/mp4/.
scp mp4.gz_ay archive:~/temp/mp4/.
scp mp4.gz_ax archive:~/temp/mp4/.
scp mp4.gz_aw archive:~/temp/mp4/.
scp mp4.gz_av archive:~/temp/mp4/.
scp mp4.gz_au archive:~/temp/mp4/.
scp mp4.gz_at archive:~/temp/mp4/.
scp mp4.gz_as archive:~/temp/mp4/.
scp mp4.gz_ar archive:~/temp/mp4/.
scp mp4.gz_aq archive:~/temp/mp4/.
scp mp4.gz_ap archive:~/temp/mp4/.
scp mp4.gz_ao archive:~/temp/mp4/.
scp mp4.gz_an archive:~/temp/mp4/.
scp mp4.gz_am archive:~/temp/mp4/.
scp mp4.gz_al archive:~/temp/mp4/.
scp mp4.gz_ak archive:~/temp/mp4/.
scp mp4.gz_aj archive:~/temp/mp4/.
scp mp4.gz_ai archive:~/temp/mp4/.
scp mp4.gz_ah archive:~/temp/mp4/.
scp mp4.gz_ag archive:~/temp/mp4/.
scp mp4.gz_af archive:~/temp/mp4/.
scp mp4.gz_ae archive:~/temp/mp4/.
scp mp4.gz_ad archive:~/temp/mp4/.
scp mp4.gz_ac archive:~/temp/mp4/.
scp mp4.gz_ab archive:~/temp/mp4/.
scp mp4.gz_aa archive:~/temp/mp4/.

cp -r EOL_media_tmp_mp4 /extra/other_files
        */
    }
    function start_233($info)
    {   $tables = $info['harvester']->tables;
        // print_r($tables); exit;
        self::process_vernacular($tables['http://rs.gbif.org/terms/1.0/vernacularname'][0]); //per DATA-1848
        self::process_media($tables['http://eol.org/schema/media/document'][0]);
    }
    private function process_vernacular($meta)
    {   //print_r($meta);
        echo "\nprocess_vernacular...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/vernacularName] => Mustard yellow massive column coral
                [http://purl.org/dc/terms/language] => en
                [http://rs.tdwg.org/dwc/terms/taxonID] => 3552
            )*/
            //------------------------------------------------------------------------------------------------------
            $comname = $rec['http://rs.tdwg.org/dwc/terms/vernacularName'];
            $rec['http://rs.tdwg.org/dwc/terms/vernacularName'] = trim(str_ireplace('Unidentified', '', $comname));
            //------------------------------------------------------------------------------------------------------
            $o = new \eol_schema\VernacularName();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    private function process_media($meta)
    {   //print_r($meta);
        echo "\nprocess_media...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://purl.org/dc/terms/identifier] => 32809587
                [http://rs.tdwg.org/dwc/terms/taxonID] => 2
                [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/MovingImage
                [http://rs.tdwg.org/audubon_core/subtype] => 
                [http://purl.org/dc/terms/format] => video/quicktime
                [http://purl.org/dc/terms/description] => Indo-Pacific, Duration 19 seconds
                [http://rs.tdwg.org/ac/terms/accessURI] => https://editors.eol.org/other_files/EOL_media/87/32809587.mov
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://www.underseaproductions.com/royalty-free-stock-footage/humpback-unicornfish-naso-brachycentron-feeding-black-sand-slope-muck-tracking-hd-video-29694
                [http://ns.adobe.com/xap/1.0/CreateDate] => 2012-10-07T13:07:39Z
                [http://purl.org/dc/terms/language] => en
                [http://ns.adobe.com/xap/1.0/Rating] => 2.5
                [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by-nc/3.0/
                [http://ns.adobe.com/xap/1.0/rights/Owner] => Josh Jensen, Undersea Productions
                [http://eol.org/schema/agent/agentID] => c44a8993a5b878d11c8cbda204ccd80c; f433a45680bf8a28bb8fb6975aee67b0
            )*/
            $accessURI = $rec['http://rs.tdwg.org/ac/terms/accessURI'];
            $rec['http://rs.tdwg.org/ac/terms/accessURI'] = str_replace('.mov', '.mp4', $accessURI);
            $rec['http://purl.org/dc/terms/format'] = Functions::get_mimetype($rec['http://rs.tdwg.org/ac/terms/accessURI']);
            $o = new \eol_schema\MediaResource();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    function start_utility()
    {   /*
        self::move_movie_files(); //in eol-archive
        self::convert_mov_2_mp4(); //in MacMini
        */
        self::move_mp4_2_EOL_media(); //in eol-archive
    }
    private function convert_mov_2_mp4() //a utility
    {
        $path = '/Library/WebServer/Documents/eol_php_code/EOL_media_tmp/';
        $dir_to_process = $path;
        if($dir = opendir($dir_to_process)) {
            while(false !== ($subdir = readdir($dir))) {
                if(!in_array($subdir, array(".",".."))) {

                    /* to make 2 connectors run. One for even and another for odd $subdir
                    if(intval($subdir) % 2 == 0) {
                        echo "Even";
                        // continue;
                    } 
                    else {
                        echo "Odd";
                        continue;
                    }
                    */
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
                            else { //we can now delete the $source (.mov file)
                                unlink($source);
                            }
                        }
                    }
                }
            }
        }
    }
    private function move_movie_files() //a utility
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
    private function move_mp4_2_EOL_media()
    {
        $dir_to_process = '/extra/other_files/EOL_media_tmp_mp4/';
        if($dir = opendir($dir_to_process)) {
            while(false !== ($subdir = readdir($dir))) {
                if(!in_array($subdir, array(".",".."))) {
                    echo "\n[$subdir]";
                    $files = $dir_to_process.$subdir."/*.mp4";
                    foreach (glob($files) as $filename) {
                        if(filesize($filename)) {
                            echo "\n[$filename] - "; //good debug
                            $source = $filename;
                            $target = str_replace("EOL_media_tmp_mp4", "EOL_media", $filename);
                            if(!file_exists($target)) {
                                if(copy($source, $target)) {
                                    echo("\nCopied from: [$source]\n");
                                    echo("\nCopied to: [$target]\n");
                                    // exit;
                                }
                                else exit("\nerror: unable to copy [$source] [$target]\n");
                            }
                        }
                    }
                }
            }
        }
    }
}
?>