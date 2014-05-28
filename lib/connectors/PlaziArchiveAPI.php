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
        if($paths = $func->extract_archive_file($dwca_file, "meta.xml"))
        {
            print_r($paths);
            if($contents = Functions::get_remote_file($paths['archive_path'] . "media.txt", array('timeout' => 172800)))
            {
                $contents = str_ireplace('<a title=""', '<a title="', $contents);
                $contents = str_ireplace('"" href=""', '" href="', $contents);
                $contents = str_ireplace('"">', '">', $contents);
                
                //saving new media.txt
                $WRITE = fopen($paths['archive_path'] . "media.txt", "w");
                fwrite($WRITE, $contents);
                fclose($WRITE);
                
                // remove the archive file e.g. plazi.zip
                $info = pathinfo($dwca_file);
                unlink($paths['archive_path'] . $info["basename"]);
                
                // creating the archive file
                $command_line = "tar -czf " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz --directory=" . $paths['archive_path'] . " .";
                $output = shell_exec($command_line);
            }
            // remove temp dir
            recursive_rmdir($paths['archive_path']);
            echo ("\n temporary directory removed: " . $paths['archive_path']);
        }
    }
}
?>