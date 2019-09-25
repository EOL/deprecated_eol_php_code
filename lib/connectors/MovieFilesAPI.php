<?php
namespace php_active_record;
// connector: [movie_files.php]
class MovieFilesAPI
{
    function __construct($archive_builder = false, $resource_id, $dwca_file = false)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->dwca = $dwca_file;
        
        /*
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */
        
        $this->download_options = array(
            'resource_id'        => 'dwca_and_media',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 1000000, 'timeout' => 60*60*3, 'download_attempts' => 2, 'delay_in_minutes' => 0.5, 'cache' => 1); //timeout 60*60*3 is 3 hours
        if(Functions::is_production())  $this->path['source']       = '/extra/other_files/EOL_media/resources/';
        else                            $this->path['source']       = '/Volumes/AKiTiO4/other_files/EOL_media/resources/';
        // $this->log_file = CONTENT_RESOURCE_LOCAL_PATH.'gbif_names_not_found_in_eol.txt';
    }
    private function access_dwca()
    {   
        /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca, "meta.xml", $this->download_options);
        print_r($paths);
        */
        // /* local when developing
        $paths = Array(
            "archive_path" => "/Library/WebServer/Documents/eol_php_code/tmp/dir_60894_mov/",
            "temp_dir" => "/Library/WebServer/Documents/eol_php_code/tmp/dir_60894_mov/"
        );
        // */
        return $paths;
    }
    function download_mov_convert_2_mp4()
    {   $paths = self::access_dwca();
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }

        self::process_media($tables['http://eol.org/schema/media/document'][0]);
        // $this->archive_builder->finalize(TRUE);

        /* un-comment in real operation
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        */
    }
    private function process_media($meta)
    {   //print_r($meta);
        echo "\nprocess_taxon...\n"; $i = 0; $f = 0;
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
            /* debug only
            if($rec['http://rs.tdwg.org/ac/terms/accessURI'] == 'http://archive.serpentproject.com/secure/00001313/01/siphonophore_05_04_06_13_00_59.mov') {
                    print_r($rec); exit;
            }
            */
            if(self::media_is_movie($rec)) {
                $f++;
                if($mov_file = self::download_proper($rec['http://rs.tdwg.org/ac/terms/accessURI'], true)) self::convert_to_mp4($mov_file);
            }
            // if($f >= 10) break;
        }
    }
    private function convert_to_mp4($mov_file)
    {
        $source = $mov_file;
        $target = str_replace(".mov", ".mp4", $mov_file);
        if(!file_exists($target)) {
            shell_exec("ffmpeg -i $source $target");
        }
    }
    private function generate_file_path($url)
    {
        $filename = pathinfo($url, PATHINFO_BASENAME);
        $md5 = md5($url);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);

        $options['cache_path'] = $this->path['source'];
        $options['cache_path'] .= str_replace('_mp4', '', $this->resource_id) . "/";
        if(!file_exists($options['cache_path'])) mkdir($options['cache_path']);

        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$filename";
        return $cache_path;
    }
    private function media_is_movie($rec)
    {
        if(pathinfo($rec['http://rs.tdwg.org/ac/terms/accessURI'], PATHINFO_EXTENSION) == 'mov') return true;
        if($rec['http://purl.org/dc/terms/format'] == 'video/quicktime') return true;
    }
    private function download_proper($url, $downloadYN = false) //e.g. http://archive.serpentproject.com/612/01/waic_seisrnger_cent35_10.mov
    {
        $destination = self::generate_file_path($url);
        if(!$downloadYN) return $destination; //this is media_url for the data_object;
        else {
            // /* uncomment in real operation. This is just to stop downloading of images.
            if(!file_exists($destination)) {
                $options = $this->download_options;
                $options['expire_seconds'] = false;
                $local = Functions::save_remote_file_to_local($url, $options);
                // echo "\n[$local]\n[$destination]"; //exit;
                if(filesize($local)) {
                    debug("\nSaving locally...[$destination]\n");
                    Functions::file_rename($local, $destination);
                    return $destination; //this is media_url for the data_object;
                }
                if(file_exists($local)) unlink($local);
            }
            else {
                debug("\nFile exists already\n");
                if(filesize($destination)) return $destination;
                else {
                    echo "\nERROR: Investigate destination is zero bytes [$destination]\n"; exit("\n");
                }
            }
            // */
        }
        return false;
    }
    /* ====================== Start 2nd part ====================== */
    function update_dwca($info)
    {   $tables = $info['harvester']->tables;
        self::process_media_with_update($tables['http://eol.org/schema/media/document'][0]);
    }
    private function process_media_with_update($meta)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...\n"; $i = 0;
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
            /*Array
            (
                [http://purl.org/dc/terms/identifier] => http://archive.serpentproject.com/612/01/waic_seisrnger_cent35_10.mov
                [http://rs.tdwg.org/dwc/terms/taxonID] => serpent_Actinoscyphia_aurelia
                [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/MovingImage
                [http://purl.org/dc/terms/format] => video/quicktime
                [http://rs.tdwg.org/ac/terms/accessURI] => http://archive.serpentproject.com/612/01/waic_seisrnger_cent35_10.mov
            */
            if(self::media_is_movie($rec)) {
                $accessURI = self::generate_file_path($rec['http://rs.tdwg.org/ac/terms/accessURI']);
                $accessURI = str_replace('.mov', '.mp4', $accessURI);
                // exit("\n$accessURI\n");
                if(!file_exists($accessURI)) continue; //means the conversion from .mov to .mp4 was not successful
                
                $arr = explode('other_files', $accessURI);
                $final = 'https://editors.eol.org/other_files/'.$arr[1];
                
                $rec['http://rs.tdwg.org/ac/terms/accessURI'] = $final;
                $rec['http://purl.org/dc/terms/format'] = Functions::get_mimetype($rec['http://rs.tdwg.org/ac/terms/accessURI']);
                // print_r($rec); exit;
            }
            //=========================================
            $o = new \eol_schema\MediaResource();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    /*
    private function log_record($rec, $sciname = '', $flag = '')
    {
        if(!($file = Functions::file_open($this->log_file, "a"))) return;
        fwrite($file, implode("\t", array($rec['http://rs.tdwg.org/dwc/terms/taxonID'], $rec['http://rs.tdwg.org/dwc/terms/scientificName'], "[$sciname]", $flag))."\n");
        fclose($file);
    }
    */
}
?>