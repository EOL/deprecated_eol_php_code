<?php
$path = @$argv[1];
ini_set('display_errors', true);

if(!$path) echo "\n\n    php v2_generate_checksums.php [PATH_TO_START_DIRECTORY]\n\n\n";
else read_directory($path);

exit;



function read_directory($path, $level = 0)
{
    if($handle = opendir($path))
    {
        $images_to_convert = array();
        $processed_images = array();
        while(false !== ($file = readdir($handle)))
        {
            if(substr($file, 0, 1) == '.') continue;
            $this_path = $path . "/" . $file;
            if(is_dir($this_path))
            {
                echo "$this_path\n";
                read_directory($this_path, $level+1);
            }elseif(preg_match("/^(.*)\.([^\.]+)$/", $file, $arr))
            {
                $prefix = $arr[1];
                $extension = $arr[2];
                if($extension == "sha1")
                {
                    $processed_images[$path . "/" . $prefix] = 1;
                }else
                {
                    $images_to_convert[$this_path] = 1;
                }
            }else
            {
                echo "Whats up with $path ?\n";
            }
        }

        ksort($images_to_convert);
        foreach($images_to_convert as $path => $junk)
        {
            if(isset($processed_images[$path])) continue;
            
            // echo "create_checksum($path);\n";
            create_checksum($path);
            static $count_processed = 0;
            $count_processed++;
            
            if($count_processed % 100 == 0)
            {
                echo "$path\n";
                echo "$count_processed : ". time_elapsed() ."\n";
            }
        }
        closedir($handle);
    }
}

function create_checksum($file_path)
{
    if(file_exists($file_path))
    {
        if(!($OUT = fopen("$file_path.sha1", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " ."$file_path.sha1");
          return;
        }
        fwrite($OUT, sha1_file($file_path));
        fclose($OUT);
    }
}

function time_elapsed($reset = false)
{
    static $a;
    if(!isset($a) || $reset) $a = microtime(true);
    return (string) round(microtime(true)-$a, 6);
}

?>

