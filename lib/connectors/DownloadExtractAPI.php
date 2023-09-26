<?php
namespace php_active_record;
// connector: [download_extract.php]
class DownloadExtractAPI
{
    function __construct()
    {
    }
    function download_and_extract($params)
    {
        if($ret = self::download_file_using_wget($params)) {
            print_r($ret);
            /* Array(
                [downloaded_file] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_95886/xeno_canto.tar.gz
                [temp_dir] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_95886/
            )*/
            $paths = self::decompress_file($ret['downloaded_file'], $ret['temp_dir']);



            /* be responsible, don't forget to delete the temp folder
            recursive_rmdir($ret['temp_dir']);
            */
        }
        else exit("\nERROR: Cannot download file [$params[url]]\n");
    }
    function decompress_file($dwca_file, $destination_path) //$file is a local file. Downloaded file or something else but a local file.
    {
        $last_3_chars = substr($dwca_file, -3); // .gz
        $last_4_chars = substr($dwca_file, -4); // .tgz | .tar | .zip
        $last_5_chars = substr($dwca_file, -5); // .gzip
        $last_7_chars = substr($dwca_file, -7); // exit("\n[$last_7_chars]\n"); // .tag.gz | .tar.xz
        $last_8_chars = substr($dwca_file, -8); // .tar.bz2

        if($last_8_chars == ".tar.bz2")                             $cmd = "tar -jxf $dwca_file --directory $destination_path";
        if($last_7_chars == ".tar.gz" || $last_4_chars == ".tgz")   {
            /* doesn't work
            $cmd = "tar -zxf $dwca_file --directory $destination_path";
            */
            // /* works OK
            $cur_dir = getcwd();
            chdir($destination_path);
            shell_exec("tar -zxvf $dwca_file");
            chdir($cur_dir);
            // */
        }
        if($last_7_chars == ".tar.xz")                              $cmd = "tar -Jxvf $dwca_file --directory $destination_path";
        if($last_5_chars == ".gzip" || $last_3_chars == ".gz")      $cmd = "gunzip -cvf $dwca_file > $destination_path"; // v - verbose; f - force overwrite
        if($last_4_chars == ".tar")                                 $cmd = "tar xf $dwca_file --directory $destination_path";
        if($last_4_chars == ".zip")                                 $cmd = "unzip -ad $destination_path $dwca_file";

        echo "\ndwca_file: [$dwca_file]\n";
        echo "\ndestination_path: [$destination_path]\n";

        /*
        https://www.cyberciti.biz/faq/howto-extract-tar-file-to-specific-directory-on-unixlinux/
        x : Extract files
        f : Tar archive name
        --directory : Set directory name to extract files
        -C : Set dir name to extract files
        -z : Work on .tar.gz (gzip) file format
        -j : Work on .tar.bz2 (bzip2) file format
        -J (capital J) : Work on .tar.xz (xz) file format (see how to extract tar.xz files in Linux for more details) https://www.cyberciti.biz/faq/how-to-extract-tar-xz-files-in-linux-and-unzip-all-files/
        -v : Verbose output i.e. show progress on screen
        */

        shell_exec($cmd);

        exit("\n-exit muna-\n");
        /*
        if(preg_match("/^(.*)\.(tar.gz|tgz)$/", $dwca_file, $arr)) {
            $archive_path = str_ireplace(".tar.gz", "", $temp_file_path);
            $archive_path = str_ireplace(".tgz", "", $temp_file_path);
        }
        elseif(preg_match("/^(.*)\.(gz|gzip)$/", $dwca_file, $arr)) {
            $archive_path = str_ireplace(".gz", "", $temp_file_path);
        }
        elseif(preg_match("/^(.*)\.(zip)$/", $dwca_file, $arr) || preg_match("/mcz_for_eol(.*?)/ims", $dwca_file, $arr)) {
            shell_exec("unzip -ad $temp_dir $temp_file_path");
            $archive_path = str_ireplace(".zip", "", $temp_file_path);
        } 
        else {
            debug("-- archive not gzip or zip. [$dwca_file]");
            return;
        }
        */


    }
    function download_file_using_wget($params)
    {   /*
        Array(
            [dirname] => https://collections.nmnh.si.edu/ipt
            [basename] => archive.do?r=nmnh_extant_dwc-a&v=1.72
            [extension] => 72
            [filename] => archive.do?r=nmnh_extant_dwc-a&v=1
        )
        Array(
            [dirname] => http://localhost/cp_new/NMNH/type_specimen_resource
            [basename] => dwca-nmnh_extant_dwc-a-v1.8.zip
            [extension] => zip
            [filename] => dwca-nmnh_extant_dwc-a-v1.8
        )*/
        $path_info = pathinfo($params['url']);
        if($val = @$params['force_extension']) {
            $extension = $val;
            $basename = $path_info['filename'].".".$extension;
        }
        else $basename = $path_info['basename'];
        // exit("\n[$basename]\n");

        $temp_dir = create_temp_dir() . "/";
        $destination = $temp_dir . $basename;
        $cmd = "wget -O $destination $params[url]"; //echo("\n[$cmd]\n");
        // $cmd .= " 2>&1"; //commented bec. I want to see the progress indicator.
        $shell_debug = shell_exec($cmd);
        // echo "\n*------*\n".trim($shell_debug)."\n*------*\n"; //good debug

        if(filesize($destination)) return array('downloaded_file' => $destination, 'temp_dir' => $temp_dir);
    }
}
?>