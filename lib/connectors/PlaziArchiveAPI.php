<?php
namespace php_active_record;
/* connector: [686, 687] used by "Plazi Treatments" and "Zootaxa via Plazi" resources
*/
class PlaziArchiveAPI
{
    function clean_media_extension($resource_id, $dwca_file)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        if($paths = $func->extract_archive_file($dwca_file, "meta.xml")) {
            print_r($paths);
            if($contents = Functions::get_remote_file($paths['archive_path'] . "media.txt", array('timeout' => 172800))) {
                $contents = str_ireplace('<a title=""', '<a title="', $contents);
                $contents = str_ireplace('"" href=""', '" href="', $contents);
                $contents = str_ireplace('"">', '">', $contents);
                $contents = str_ireplace("No known copyright restrictions apply. See Agosti, D., Egloff, W., 2009. Taxonomic information exchange and copyright: the Plazi approach. BMC Research Notes 2009, 2:53 for further explanation.", 'No known copyright restrictions', $contents);
                
                //saving new media.txt
                if(!($WRITE = Functions::file_open($paths['archive_path'] . "media.txt", "w"))) return;
                fwrite($WRITE, $contents);
                fclose($WRITE);
                
                // remove the archive file e.g. plazi.zip
                $info = pathinfo($dwca_file);
                unlink($paths['archive_path'] . $info["basename"]);
                
                // creating the archive file
                $command_line = "tar -czf " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz --directory=" . $paths['archive_path'] . " .";
                $output = shell_exec($command_line);
                
                // moving files to /resources/
                recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
                if(!file_exists(CONTENT_RESOURCE_LOCAL_PATH . $resource_id)) mkdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
                $src = $paths['archive_path'];
                $dst = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/";
                $files = glob($paths['archive_path'] . "*.*");
                foreach($files as $file) {
                    $file_to_go = str_replace($src, $dst, $file);
                    copy($file, $file_to_go);
                }
            }
            // remove temp dir
            recursive_rmdir($paths['archive_path']);
            echo ("\n temporary directory removed: " . $paths['archive_path']);
        }
    }
}
?>