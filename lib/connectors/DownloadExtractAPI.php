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



            /* be responsible, don't forget to delete the temp folder
            recursive_rmdir($ret['temp_dir']);
            */
        }
        else exit("\nERROR: Cannot download file [$params[url]]\n");
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

        if(filesize($destination)) return array('destination' => $destination, 'temp_dir' => $temp_dir);
    }
}
?>