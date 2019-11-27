<?php
namespace php_active_record;

class ContentManager
{
    private $unique_key;
    private static $valid_image_extensions = array(
        'jpg', 'png', 'gif', 'tiff', 'tif', 'svg', 'bmp');
    private static $valid_video_extensions = array(
        'flv', 'mov', 'avi', 'mp4', 'wmv', 'flc', 'ogg', 'mpeg');
    private static $valid_sound_extensions = array(
        'wav', 'aif', 'mp3', 'ogg', 'flac', 'au', 'aac', 'wma');

    function __construct()
    {
        /* Construct a unique_key for each instance of a ContentManager, passed as an option to the function
         download_temp_file_and_assign_extension(), and used as a unique temporary name for the downloaded file.
         This means a single ContentManager instance may overwrite previously downloaded temp files. */
        $this->unique_key = Functions::generate_guid();
    }

    // This function will download a file and place it on the content server
    //
    // Possible types for this function
    // image - this is for images
    // video - this is for videos - mpg, flv, mp4, etc...
    // audio - this is for sounds - mp3, wav, etc...
    // upload - what is this for?
    // partner - this type means we are downloading a logo for a content partner
    // resource - this means we are downloading an XML or zipped file of the EOL schema for processing
    // dataset - we create a zip file of the dataset provided

    function grab_file($file, $type, $options = array())
    {

        $permanent_prefix = $this->permanent_file_prefix($type, $options);
        if(empty($permanent_prefix)) return false;

        $extension = strtolower(trim(pathinfo($file, PATHINFO_EXTENSION)));
        //Try hard linking to a local version of the file if it exists, to save space (especially relevant for cropping images)
        //Note that the PHP docs wrongly claim that link() requires extra privileges on Windows.
        //But is *is* true that we can't hard link to directories, so check this via is_file().
        //Don't link to archive files, but force download them again, so that they are unpacked in the correct manner.
        if (self::is_local($file) && is_file($file) && !in_array($extension, array('gz', 'gzip', 'zip', 'tar'), true)) {
            //hack required because we don't have the originally downloaded file extension, so simply give the new version the same extension as the old one
            $permanent_file_path = $permanent_prefix;
            if (strlen($extension)) $permanent_file_path .= '.'.$extension;
            if (link($file, $permanent_file_path)) {
                $cache_file_path = $file;
                if($GLOBALS['ENV_DEBUG'])
                {
                	echo "Hard link created (old file is $file, now linked at $permanent_file_path)\n";
                	write_to_resource_harvesting_log("Hard link created (old file is $file, now linked at $permanent_file_path)");
                }
            }
        }

        //If we couldn't hard link, download the file from scratch
        if (!isset($cache_file_path))
        {
            if(@!$options['timeout']) $options['timeout'] = DOWNLOAD_TIMEOUT_SECONDS;
            $temp_file_path = self::download_temp_file_and_assign_extension($file, $type, array_merge($options, array('unique_key' => $this->unique_key)));
            if (empty($temp_file_path)) return false;

            //if suffix, require lowercase
            if ($suffix = strtolower(trim(pathinfo($temp_file_path, PATHINFO_EXTENSION))))
            {
                $cache_file_path = $permanent_file_path = $permanent_prefix . "." . $suffix;
                if(copy($temp_file_path, $permanent_file_path))
                  unlink($temp_file_path);
            } else {
                //no suffix
                if ($type !== 'resource') {
                    if(file_exists($temp_file_path)) unlink($temp_file_path);
                    return false;
                };
                $cache_file_path = $permanent_file_path = $permanent_prefix;
                Functions::file_rename($temp_file_path, $permanent_file_path);
            }

            // fail if for some reason there is still no file at the new path
            if(!file_exists($permanent_file_path))
            {
                trigger_error("ContentManager: Unable to download file $file", E_USER_NOTICE);
                write_to_resource_harvesting_log("ContentManager: Unable to download file $file");
                return false;
            }
        }

        // create thumbnails of website content and agent logos
        if    ($type==="image")   $this->create_local_files_and_thumbnails($cache_file_path, $permanent_prefix, $options);
        elseif($type==="audio")   $this->create_audio_thumbnails($cache_file_path, $permanent_prefix, $options);
        elseif($type==="partner") $this->create_agent_thumbnails($cache_file_path, $permanent_prefix);
        elseif($type==="dataset")
        {
            $permanent_file_path = $this->zip_file($cache_file_path);
            $this->delete_old_datasets();
        }

        //return the cache file location: for most objects this is a number formed by concatenating the cache dir names, so we lose the file extension
        //TODO in this case: return the array [number, extension], so we can access the original downloaded file.
        switch($type) {
            case "image":
            case "video":
            case "audio":
            case "upload":
            case "partner":
                self::create_checksum($permanent_file_path);
                // Take the substring of the new file path to return via the webservice, so that /content/2009/05/19/23/85866.png -> 200905192385866
                return self::cache_path2num($permanent_file_path);
            case "resource":
                //slightly oddly, service.php distinguishes between resource folders and plain files by whether CONTENT_RESOURCE_LOCAL_PATH is prefixed
                if (!$suffix) return($permanent_file_path);
                if (preg_match("/^".preg_quote(CONTENT_RESOURCE_LOCAL_PATH, "/")."(.*)$/", $permanent_file_path, $arr)) return($arr[1]);
            case "dataset":
                if (preg_match("/^".preg_quote(CONTENT_DATASET_PATH, "/")."(.*)$/", $permanent_file_path, $arr)) return($arr[1]);
        }
        return null;
    }

    function permanent_file_prefix($type, $options)
    {
        switch($type) {
            case "image":
            case "video":
            case "audio":
            case "upload":
            case "partner":
                return($this->new_content_file_name());
            case "resource":
                return($this->new_resource_file_name(@$options['resource_id']));
            case "dataset":
                return($this->new_dataset_file_name(@$options['data_search_file_id']));
        }
        write_to_resource_harvesting_log("ContentManager: non-valid type (".$type.")");
        trigger_error("ContentManager: non-valid type (".$type.")", E_USER_NOTICE);
        return null;
    }

    public static function download_temp_file_and_assign_extension($file_path_or_uri, $type, $options = array())
    {
        debug("ContentManager: = download_temp_file_and_assign_extension ==========");
        if(@!$options['unique_key']) $options['unique_key'] = Functions::generate_guid();
        if(@!$options['timeout']) $options['timeout'] = DOWNLOAD_TIMEOUT_SECONDS;

        $suffix = null;
        if(@$options['suffix']) $suffix = $options['suffix'];
        elseif(preg_match("/\.([^\.]+)$/", $file_path_or_uri, $arr)) $suffix = strtolower(trim($arr[1]));

        // resources may need a little extra time to establish a connection
        if(($type === 'resource') && $options['timeout'] < 60) $options['timeout'] = 60;

        $temp_file_path = CONTENT_TEMP_PREFIX . $options['unique_key'] . ".file";
        debug("ContentManager: = $temp_file_path (from $file_path_or_uri )");
        if(preg_match("/^(http|https|ftp):\/\//", $file_path_or_uri) || self::is_local($file_path_or_uri))
        {
            if($file_contents = Functions::get_remote_file($file_path_or_uri, array('timeout' => $options['timeout'])))
            {
                debug("ContentManager: got remote file");
                // if this is a resource then update the old references to the schema
                // there were a few temporary locations for the schema which were being used by early providers
                // and not all of them have been updated
                if($type === 'resource')
                {
                    debug("ContentManager: is resource");
                    $file_contents = str_replace("http://www.eol.org/transfer/data/0.1",
                                                 "http://www.eol.org/transfer/content/0.1", $file_contents);
                    $file_contents = str_replace("http://services.eol.org/development/pleary/xml/content4.xsd",
                                                 "http://services.eol.org/schema/content_0_1.xsd", $file_contents);
                }

                if(!($TMP = fopen($temp_file_path,"w+")))
                {
                  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$temp_file_path);
                  return;
                }
                fwrite($TMP, $file_contents);
                debug("ContentManager: wrote file");
                fclose($TMP);
            }
        }
        if (is_file($temp_file_path)) {
          // debug("temp_file_path: [$temp_file_path]");
          debug("ContentManager: is file (suffix $suffix)");
          $temp_file_path_with_extension = self::give_temp_file_right_extension($temp_file_path, $suffix, @$options['unique_key']);
          debug("ContentManager: gave extension: $temp_file_path_with_extension");
          $temp_file_path_with_extension = self::enforce_extensions_for_type($temp_file_path_with_extension, $type);
          debug("ContentManager: enforced extension: $temp_file_path_with_extension");
          return $temp_file_path_with_extension;
        } else return null;
    }

    public static function enforce_extensions_for_type($temp_file_path_with_extension, $type)
    {
        $pathinfo = pathinfo($temp_file_path_with_extension);
        $extension = strtolower(@$pathinfo['extension']);
        if(($type == 'image' && !in_array($extension, self::$valid_image_extensions)) ||
           ($type == 'video' && !in_array($extension, self::$valid_video_extensions)) ||
           ($type == 'sound' && !in_array($extension, self::$valid_sound_extensions)))
        {
            unlink($temp_file_path_with_extension);
            return false;
        }

        return $temp_file_path_with_extension;
    }

    public static function give_temp_file_right_extension($temp_file_path, $original_suffix, $unique_key)
    {
        debug("ContentManager: give_temp_file_right_extension");
        // if the download succeeded
        if(file_exists($temp_file_path))
        {
            debug("ContentManager: file exists (original suffix $original_suffix)");
            if(SYSTEM_OS == "Windows") $new_suffix = self::determine_file_suffix_pc($temp_file_path, $original_suffix);
            else $new_suffix = self::determine_file_suffix($temp_file_path, $original_suffix);

            if($new_suffix)
            {
                debug("ContentManager: new_suffix: $new_suffix");
                $new_temp_file_path = CONTENT_TEMP_PREFIX . $unique_key . "." . $new_suffix;
                debug("ContentManager: new_temp_file_path: $new_temp_file_path");
                // copy temporary file from $PATH.file to $PATH.tar.gz for example
                if(copy($temp_file_path, $new_temp_file_path))
                  unlink($temp_file_path);

                // fail if for some reason there is still no file at the new path
                if(!file_exists($new_temp_file_path))
                {
                    if(file_exists($temp_file_path)) unlink($temp_file_path);
                    write_to_resource_harvesting_log("ContentManager: Unable to download file $file_path_or_uri");
                    trigger_error("ContentManager: Unable to download file $file_path_or_uri", E_USER_NOTICE);
                    return false;
                }
                if(preg_match("/^(.*)\.(gz|gzip)$/", $new_temp_file_path, $arr))
                {
                    debug("ContentManager: ungzipping...");
                    shell_exec(GUNZIP_BIN_PATH . " -f " . escapeshellarg($new_temp_file_path));
                    $new_temp_file_path = $arr[1];
                    return self::give_temp_file_right_extension($new_temp_file_path, $original_suffix, $unique_key);
                    self::move_up_if_only_directory($new_temp_file_path); //Comment by Yan: this seems redundent.
                }
                if(preg_match("/^(.*)\.(tar)$/", $new_temp_file_path, $arr))
                {
                    debug("ContentManager: untarring...");
                    $archive_directory = $arr[1];
                    @unlink($archive_directory);
                    @rmdir($archive_directory);
                    mkdir($archive_directory);

                    shell_exec(TAR_BIN_PATH . " -xf " . escapeshellarg($new_temp_file_path) . " -C " . escapeshellarg($archive_directory));
                    if(file_exists($new_temp_file_path)) unlink($new_temp_file_path);
                    $new_temp_file_path = $archive_directory;
                    self::move_up_if_only_directory($new_temp_file_path);
                }
                if(preg_match("/^(.*)\.(zip)$/", $new_temp_file_path, $arr))
                {
                    debug("ContentManager: unzipping $new_temp_file_path ...");
                    $archive_directory = $arr[1];
                    @unlink($archive_directory);
                    @rmdir($archive_directory);
                    mkdir($archive_directory);

                    shell_exec(UNZIP_BIN_PATH . " -d " . escapeshellarg($archive_directory) . " " . escapeshellarg($new_temp_file_path));
                    if(file_exists($new_temp_file_path)) unlink($new_temp_file_path);
                    $new_temp_file_path = $archive_directory;
                    self::move_up_if_only_directory($new_temp_file_path);
                }
                debug("ContentManager: done with $new_temp_file_path ...");
                if(file_exists($new_temp_file_path)) return $new_temp_file_path;
            }
        }
    }

    public static function move_up_if_only_directory($directory_path)
    {
        $files = read_dir($directory_path);
        $only_file = null;
        foreach($files as $file)
        {
            // there can be only one only file, must not start with a dot
            if($only_file) return;
            if(substr($file, 0, 1) == ".") continue;
            $only_file = $directory_path ."/". $file;
        }
        if(is_dir($only_file))
        {
            Functions::file_rename($only_file, $directory_path . "_swap");
            /* causes Warning: rmdir() Directory not empty
            rmdir($directory_path);
            */
            recursive_rmdir($directory_path);
            
            /* causes PHP error, cannot copy dir; used file_rename() below instead
            if(copy($directory_path . "_swap", $directory_path))
              unlink($directory_path . "_swap");
            */
            Functions::file_rename($directory_path . "_swap", $directory_path);
        }
    }

    public static function determine_file_suffix($file_path, $suffix)
    {
        // use the Unix/Linux `file` command to determine file type
        $stat = strtolower(shell_exec(FILE_BIN_PATH . " " . escapeshellarg($file_path)));
        $file_type = "";
        if(preg_match("/^[^ ]+: (.*)$/",$stat,$arr)) $file_type = trim($arr[1]);
        if(preg_match("/^\"(.*)/", $file_type, $arr)) $file_type = trim($arr[1]);

        $new_suffix = "";

        // images
        if(preg_match("/jpeg image data/i", $file_type))                                $new_suffix = "jpg";
        elseif(preg_match("/tiff image data/i", $file_type))                            $new_suffix = "tif";
        elseif(preg_match("/PNG image/i", $file_type))                                  $new_suffix = "png";
        elseif(preg_match("/GIF image/i", $file_type))                                  $new_suffix = "gif";
        elseif(preg_match("/SVG Scalable/i", $file_type))                               $new_suffix = "svg";

        // videos
        elseif(preg_match("/^macromedia flash/i", $file_type))                          $new_suffix = "flv";
        elseif(preg_match("/apple quicktime/i", $file_type))                            $new_suffix = "mov";
        elseif(preg_match("/^riff \(little-endian\) data, avi/i", $file_type))          $new_suffix = "avi";
        elseif(preg_match("/^iso Media, mpeg v4/i", $file_type))                        $new_suffix = "mp4";
        elseif(preg_match("/^microsoft asf/i", $file_type))                             $new_suffix = "wmv";
        elseif(preg_match("/^mpeg sequence/i", $file_type))                             $new_suffix = "mpg";
        elseif(preg_match("/^flc animation/i", $file_type))                             $new_suffix = "flc";
        elseif(preg_match("/^ogg data, theora video/i", $file_type))                    $new_suffix = "ogg";
        elseif(preg_match("/^ogg data, skeleton/i", $file_type))                        $new_suffix = "ogg";
        elseif($suffix == "wmv" && preg_match("/^microsoft asf/i", $file_type))         $new_suffix = "wmv";

        // audio
        elseif(preg_match("/^riff \(little-endian\) data, wave audio/i", $file_type))   $new_suffix = "wav";
        elseif(preg_match("/^iff data, aiff audio/i", $file_type))                      $new_suffix = "aif";
        elseif(preg_match("/mpeg adts, layer iii/i", $file_type))                       $new_suffix = "mp3";
        elseif(preg_match("/^audio .* mp3/i", $file_type))                              $new_suffix = "mp3";
        elseif(preg_match("/^ogg data, vorbis audio/i", $file_type))                    $new_suffix = "ogg";
        elseif(preg_match("/^flac audio/i", $file_type))                                $new_suffix = "flac";
        elseif(preg_match("/^sun\/next audio data/i", $file_type))                      $new_suffix = "au";
        elseif(preg_match("/^mpeg adts, aac/i", $file_type))                            $new_suffix = "aac";
        elseif($suffix == "wma" && preg_match("/^microsoft asf/i", $file_type))         $new_suffix = "wma";

        // compressed
        elseif(preg_match("/^gzip compressed data/i", $file_type))                      $new_suffix = "gz";
        elseif(preg_match("/^posix tar archive/i", $file_type))                         $new_suffix = "tar";
        elseif(preg_match("/^tar archive/i", $file_type))                               $new_suffix = "tar";
        elseif(preg_match("/^zip archive data/i", $file_type))
        {
            debug("ContentManager: It's compressed, it's a zip, and the suffix is $suffix");
            if($suffix == "xlsx")                                                       $new_suffix = "xlsx";
            else                                                                        $new_suffix = "zip";
        }

        // other - xml, html, pdf
        elseif(preg_match("/^xml( |$)/i", $file_type) || preg_match("/xml$/i", $file_type)) $new_suffix = "xml";
        elseif(preg_match("/^pdf( |$)/i", $file_type))                                  $new_suffix = "pdf";
        elseif($suffix == "csv" && preg_match("/^html /i", $file_type))                 $new_suffix = "csv";
        elseif(preg_match("/^html( |$)/i", $file_type))                                 $new_suffix = "html";
        elseif(preg_match("/ Excel(,|$)/i", $file_type))                                $new_suffix = "xls";
        elseif(preg_match("/ Excel 2007/i", $file_type))                                $new_suffix = "xlsx";
        elseif($suffix == "xml" && preg_match("/^utf-8 unicode /i", $file_type))        $new_suffix = "xml";
        elseif($suffix == "xml" && preg_match("/^ascii text/i", $file_type))            $new_suffix = "xml";
        elseif($suffix == "csv" && preg_match("/^ascii text/i", $file_type))            $new_suffix = "csv";
        elseif($suffix == "csv" && preg_match("/english text/i", $file_type))           $new_suffix = "csv";
        elseif($suffix == "csv" && preg_match("/^utf-8 unicode /i", $file_type))        $new_suffix = "csv";
        elseif($suffix == "xml" && preg_match("/^ASCII English text/i", $file_type))    $new_suffix = "xml";
        // some XML files like BibAlex's resource doesnt have an extension and just has a utf-8 descriptor
        elseif(preg_match("/^utf-8 unicode /i", $file_type))                            $new_suffix = "xml";
        elseif(preg_match("/Microsoft Office Document/i", $file_type))                  $new_suffix = "xls";
        // not sure why this is happening, but when downloading JPG from EOL content servers I was getting this type
        elseif(preg_match("/Minix filesystem/i", $file_type))                           $new_suffix = "jpg";

        elseif($file_type == 'composite document file v2 document, little endian, os: windows, version 5.0, code page: 0') $new_suffix = "xls"; //new Nov 26, 2019
        else debug("May need to investigate. file_type [$file_type] is unknown.");                                                              //new Nov 26, 2019

        return $new_suffix;
    }

    public static function determine_file_suffix_pc($file_path, $suffix)
    {
        $new_suffix = $suffix;
        $arr = array('jpg','tif','flv','mov','avi','gz','tar','zip','xml','pdf','html','png','xml','gif', 'mp4', 'wmv', 'mpg', 'mpeg');

        if(!in_array($suffix, $arr))
        {
            $new_suffix=false;
            $new_suffix=false;
            $new_suffix="xml";

            $image = array(
                    0 => array(    "type" => "image/bmp"        , "suffix" => "bmp"),
                    1 => array(    "type" => "image/gif"        , "suffix" => "gif"),
                    2 => array(    "type" => "image/jpeg"       , "suffix" => "jpg"),
                    3 => array(    "type" => "image/png"        , "suffix" => "png"),
                    4 => array(    "type" => "image/svg+xml"    , "suffix" => "svg"),
                    5 => array(    "type" => "image/tiff"       , "suffix" => "tif"),
                    6 => array(    "type" => "video/mp4"        , "suffix" => "mp4"),
                    7 => array(    "type" => "video/x-ms-wmv"   , "suffix" => "wmv"),
                    8 => array(    "type" => "video/mpeg"       , "suffix" => "mpg"),
                    9 => array(    "type" => "video/quicktime"  , "suffix" => "mov"),
                    10 => array(   "type" => "video/x-flv"      , "suffix" => "flv")
                          );
            $url = $file_path;
            $image_data = getimagesize($url);

            for ($i = 0; $i < count($image); $i++)
            {
                if($image_data["mime"] == $image[$i]["type"])
                {
                    $new_suffix = $image[$i]["suffix"];
                    break;
                }
            }
        }
        return $new_suffix;
    }

    static function large_image_dimensions()
    {
        static $dimensions = array(580, 360);
        return $dimensions;
    }

    static function medium_image_dimensions()
    {
        static $dimensions = array(260, 190);
        return $dimensions;
    }

    static function small_image_dimensions()
    {
        static $dimensions = array(98, 68);
        return $dimensions;
    }

    static function large_square_dimensions()
    {
        static $dimensions = array(130, 130);
        return $dimensions;
    }

    static function small_square_dimensions()
    {
        static $dimensions = array(88, 88);
        return $dimensions;
    }

    function create_local_files_and_thumbnails($original_file, $prefix, $options = array())
    {
        $fullsize_jpg = $this->reduced_original($original_file, $prefix, $options);
        if(!file_exists($fullsize_jpg)) {
        	write_to_resource_harvesting_log("ContentManager: Unable to create jpg file from downloaded file $original_file");
            trigger_error("ContentManager: Unable to create jpg file from downloaded file $original_file.", E_USER_NOTICE);
            return false;
        }
        //get the size from the jpg version, which is properly orientated
        $sizes = getimagesize($fullsize_jpg);
        $width = @$sizes[0];
        $height = @$sizes[1];
        if (empty($width) && empty($height)) {
            write_to_resource_harvesting_log("ContentManager: Unable to getimagesize for $fullsize_jpg: using default crop and not recording image_size data");
            trigger_error("ContentManager: Unable to getimagesize for $fullsize_jpg: using default crop and not recording image_size data", E_USER_NOTICE);
        }

        // we make an exception
        if(isset($options['large_image_dimensions']) && is_array($options['large_image_dimensions']))
        {
            $large_image_dimensions = $options['large_image_dimensions'];
        } else $large_image_dimensions = ContentManager::large_image_dimensions();

        //create smaller versions, or hard-link to them if $fullsize_jpg is a previously existing file
        $large_jpg = $this->create_smaller_version($fullsize_jpg, $large_image_dimensions, $prefix, implode(ContentManager::large_image_dimensions(), '_'));
        //use large (not fullsize) jpg to create the medium & small versions, to save processing potentially enormous _orig.jpg files.
        $this->create_smaller_version($large_jpg, ContentManager::medium_image_dimensions(), $prefix, implode(ContentManager::medium_image_dimensions(), '_'));
        $this->create_smaller_version($large_jpg, ContentManager::small_image_dimensions(), $prefix, implode(ContentManager::small_image_dimensions(), '_'));

        $custom_crop = $this->check_image_database(@$options['data_object_id'], @$options['data_object_guid']);
        if (isset($options['crop_pct']) && count($options['crop_pct']) >= 4)
            $custom_crop = $options['crop_pct'];

        if (count($custom_crop) >= 4)
        {
            foreach($custom_crop as &$p) $p =  min(max($p, 0), 100);
            $crop_pixels = array(intval(round($custom_crop[0]/100.0*$width)),
                                 intval(round($custom_crop[1]/100.0*$height)),
                                 intval(round($custom_crop[2]/100.0*$width)));
            $crop_pixels[]= empty($custom_crop[3]) ? $crop_pixels[2] : intval(round($custom_crop[3]/100.0*$height));

            //if this image has a custom crop, it could be of a tiny region, so use the huge image, to avoid pixellation.
            //Ideally, we'd use the original image but it could be rotated differently, and we don't currently store rotation information in the DB.
            $this->create_crop($fullsize_jpg, ContentManager::large_square_dimensions(), $prefix, $crop_pixels);
            $this->create_crop($fullsize_jpg, ContentManager::small_square_dimensions(), $prefix, $crop_pixels);
        } else {
            //we are taking the default crop, so to save cpu time, don't bother cropping the full size image, just use the "large" 580_360 version
            $this->create_crop($large_jpg, ContentManager::large_square_dimensions(), $prefix);
            $this->create_crop($large_jpg, ContentManager::small_square_dimensions(), $prefix);
        }
        //update width & height in case they have changed, but only change % crop values if we have a $new_crop
        $this->save_image_size_data(@$options['data_object_id'], $width, $height, $custom_crop);
    }

    function create_agent_thumbnails($file, $prefix)
    {
        $this->create_constrained_square_crop($file, ContentManager::large_square_dimensions(), $prefix);
        $this->create_constrained_square_crop($file, ContentManager::small_square_dimensions(), $prefix);
    }

    function check_image_database($data_object_id, $data_object_guid)
    {
        //check if $data_object_id already exists in images_sizes table: insert if not. Return potential cropping info.
        $crop = $this->get_crop_from_DB($data_object_id);
        if (isset($crop)) return $crop;

        //if we get here, DB entry for this data_obj_id doesn't exist, so create the image_size entry in the DB (height, etc may be filled in later)
        $GLOBALS['mysqli_connection']->insert("INSERT IGNORE INTO image_sizes (data_object_id) VALUES ($data_object_id)");
        //check for other data_object IDs with the same GUID, which might provide a relevant previous crop
        if (isset($data_object_guid)) {
            $resp = $GLOBALS['mysqli_connection']->query("SELECT id FROM data_objects WHERE guid='$data_object_guid' AND published=1 ORDER BY id DESC LIMIT 1");
            if ($resp) {
                if ($resp->num_rows) {
                    $row = $resp->fetch_row();
                    if ($row && isset($row[0]))
                        return $this->get_crop_from_DB($row[0]);
                }
            } else {
                write_to_resource_harvesting_log("ContentManager: Database error while getting data_objects with guid='$data_object_guid' from data_objects table");
                trigger_error("ContentManager: Database error while getting data_objects with guid='$data_object_guid' from data_objects table", E_USER_NOTICE);
            }
        }
        return false;
    }

    function get_crop_from_DB($data_object_id) {
        //return the crop data, or NULL if the query ran but found no entry in the image_sizes table, else return false
        if (isset($data_object_id)) {
            $resp = $GLOBALS['mysqli_connection']->query("SELECT crop_x_pct, crop_y_pct, crop_width_pct, crop_height_pct FROM image_sizes WHERE data_object_id=$data_object_id LIMIT 1");
            if ($resp) {
                if (!$resp->num_rows) {
                    //DB query OK, but no rows found
                    return NULL;
                }
                $crop = $resp->fetch_row();
                if (isset($crop[0]) and isset($crop[1]) and isset($crop[2])) return $crop;
            } else {
            	write_to_resource_harvesting_log("ContentManager: Database error while getting data_object $data_object_id from image_sizes table");
                trigger_error("ContentManager: Database error while getting data_object $data_object_id from image_sizes table", E_USER_NOTICE);
            }
        }
        return false;
    }

    function save_image_size_data($data_object_id, $width, $height, $crop_percentages=NULL)
    {
        if (!empty($width) and !empty($height) and isset($data_object_id)) {
            // **** do we also want to store rotation information in the DB?
            //This might require getting it from ImageMagick (http://www.imagemagick.org/discourse-server/viewtopic.php?f=1&t=26568)
            $sql = sprintf("SET width=%u,height=%u", $width, $height);
            if (count($crop_percentages) >= 4)
            {   //also set the crop values at the same time
                if (is_null($crop_percentages[3]))
                { //the 4th %age (height) might be unset
                    $sql .= vsprintf(",crop_x_pct=%.2F,crop_y_pct=%.2F,crop_width_pct=%.2F,crop_height_pct=NULL", $crop_percentages);
                } else {
                    $sql .= vsprintf(",crop_x_pct=%.2F,crop_y_pct=%.2F,crop_width_pct=%.2F,crop_height_pct=%.2F", $crop_percentages);
                }
            }
            $GLOBALS['mysqli_connection']->update("UPDATE image_sizes ".$sql." WHERE data_object_id=$data_object_id");
        }
    }

    function hard_link_to_existing($old_prefix, $new_prefix, $suffix)
    {
        // If the $old_prefix does not match $new_prefix, look for a previously cached file with the old
        // prefix and the suffix. If it exists, create a link from $old_prefix.$suffix to $new_prefix.$suffix.
        $new_file = $new_prefix.$suffix;
        if ($old_prefix != $new_prefix) {
            //look for an already existing equivalent of $old_file with the new suffix we can link to
            if (file_exists($old_file = $old_prefix.$suffix)) {
                if (link($old_file, $new_file)) {
                    if($GLOBALS['ENV_DEBUG'])
                    {
                     echo "Hard link created (old file is $old_file, now linked at $new_file)\n";
                     write_to_resource_harvesting_log("Hard link created (old file is $old_file, now linked at $new_file)");
                    }
                    self::create_checksum($new_file);
                    //return the old version, to indicate to future calls that other cached files may be available
                    return $old_file;
                }
            }
        }
        return false;
    }

    function reduced_original($src_image, $prefix, $options = array())
    {
        $suffix = "_orig.jpg";
        $src_prefix = self::cache_prefix($src_image);
        if ($link = $this->hard_link_to_existing($src_prefix, $prefix, $suffix)) return $link;

        $new_image_path = $prefix.$suffix;
        $rotate = "-auto-orient";
        if(isset($options['rotation'])) $rotate = "-rotate " . intval($options['rotation']);
        $command = CONVERT_BIN_PATH. " " . escapeshellarg($src_image) . " -strip -background white -flatten $rotate -quiet -quality 80";
        shell_exec($command . " " . escapeshellarg($new_image_path));
        self::create_checksum($new_image_path);
        return $new_image_path;
    }

    function create_smaller_version($src_image, $dimensions, $prefix, $suffix_dims)
    {
        //makes a smaller image (returning the new path), or hard links to one if it exists already (returning the old path)
        //N.B. we don't need to rotate, as this works on already-rotated version
        $suffix = "_" . $suffix_dims . ".jpg";
        $src_prefix = self::cache_prefix($src_image);
        if ($link = $this->hard_link_to_existing($src_prefix, $prefix, $suffix)) return $link;

        $new_image_path = $prefix . $suffix;
        $command = CONVERT_BIN_PATH . ' ' . escapeshellarg($src_image) . ' -strip -background white -flatten -quiet -quality 80';
        $command .= ' -resize ' . escapeshellarg($dimensions[0] . 'x' . $dimensions[1] . '>');
        shell_exec($command . ' ' . escapeshellarg($new_image_path));
        self::create_checksum($new_image_path);
        return $new_image_path;
    }

    function create_crop($src_image, $dimensions, $prefix, $crop_pixels=NULL)
    {
        //Do not make square thumbnails by hard linking to old versions, as the crop size may have changed.
        $command = CONVERT_BIN_PATH . ' ' . escapeshellarg($src_image) . ' -strip -background white -flatten -quiet -quality 80';
        if(count($crop_pixels)>=4)
        {
            $command .= ' -gravity NorthWest -crop ' . escapeshellarg($crop_pixels[2] . 'x' . $crop_pixels[3] . '+' . $crop_pixels[0] . '+' . $crop_pixels[1]);
            $command .= ' +repage -resize ' . escapeshellarg($dimensions[0] . 'x' . $dimensions[1] . '!');
        } else {
            // default command just makes the image square by cropping the edges: see http://www.imagemagick.org/Usage/resize/#fill
            $command .= ' -resize ' . escapeshellarg($dimensions[0] . 'x' . $dimensions[1] . '^') . ' -gravity NorthWest';
            $command .= ' -crop ' . escapeshellarg($dimensions[0] . 'x' . $dimensions[1] . '+0+0') . ' +repage';
        }

        $new_image_path = $prefix . '_' . $dimensions[0] . '_' . $dimensions[1] . '.jpg';
        shell_exec($command . ' ' . escapeshellarg($new_image_path));
        self::create_checksum($new_image_path);
    }

    function create_constrained_square_crop($src_image, $dimensions, $prefix)
    {
        // requires "convert" to support -gravity center -extent: ImageMagick >= 6.3.2
        $command = CONVERT_BIN_PATH . ' ' . escapeshellarg($src_image) . ' -strip -background white -flatten -auto-orient -quiet -quality 80 ';
        $command .=  ' -resize ' . escapeshellarg($dimensions[0] . 'x' . $dimensions[1]) . ' -gravity center';
        $command .=  ' -extent ' . escapeshellarg($dimensions[0] . 'x' . $dimensions[1]) . ' +repage';
        $new_image_path = $prefix . '_' . $dimensions[0] . '_' . $dimensions[1] . '.jpg';
        shell_exec($command . ' ' . escapeshellarg($new_image_path));
        self::create_checksum($new_image_path);
    }

    function create_audio_thumbnails($audiofile, $prefix, $options = array())
    {
        /* Some audio, video, etc files may have thumbnails created from the harvesting site (code in DataObjects.php).
        This could be a picture, a spectrogram, or whatever. Here we create a default thumbnail set for audio files.
        Those that already have a thumbnail will thus have 2 potential sets of thumbnails. The "default" ones created
         here are guaranteed to have the same prefix as the audio (.wav, .mp3 etc) file, but are .png files */

        //Create "XXX_orig.png" spectrogram, whose width varies with length of sound file (with maximum length constraint)
        $truncate_after_seconds = 60;
        $pixels_per_second = 100;
        $dimensions = array($pixels_per_second) + self::large_image_dimensions(); //overwrite $dimension[0] (width)
        $this->create_spectrogram($audiofile, $dimensions, $prefix, $truncate_after_seconds, true, "orig", false);

        //Fixed width spectrogram thumbnails, omit axes for smaller sizes
        $truncate_after_seconds = 30;
        $this->create_spectrogram($audiofile, self::large_image_dimensions(), $prefix, $truncate_after_seconds, true);
        $this->create_spectrogram($audiofile, self::medium_image_dimensions(), $prefix, $truncate_after_seconds, true);
        $this->create_spectrogram($audiofile, self::small_image_dimensions(), $prefix, $truncate_after_seconds, false);
        $this->create_spectrogram($audiofile, self::large_square_dimensions(), $prefix, $truncate_after_seconds, false);
        $this->create_spectrogram($audiofile, self::small_square_dimensions(), $prefix, $truncate_after_seconds, false);
    }

    function create_spectrogram($audiofile, $dimensions, $prefix, $max_seconds, $show_axes, $suffix=null, $fixed_width=true)
    {
        if (defined('SOX_BIN_PATH')) {
            if (is_null($suffix))
                $suffix = implode($dimensions, '_');
            $spectrogram_path = $prefix."_".$suffix.".png";

            // by trial and error, axis labels etc in SoX spectrograms seem to take 144 px in the X direction and 78 in the Y
            $axis_decoration_px = array(144, 78);
            $x = $dimensions[0];
            $y = $dimensions[1];
            //limit max dB, force white background, simple colour scheme, etc. see http://sox.sourceforge.net/sox.html
            $sox_options = "--null remix - trim 0 $max_seconds norm spectrogram -z 50 -p 1 -l  -c ''";
            if ($show_axes) {
                $y -= $axis_decoration_px[1];
                if ($fixed_width)
                    $x -= $axis_decoration_px[0];
            } else {
                $sox_options .= " -r";
            }

            if ($fixed_width)
            {
                //SoX only accepts x > 100px: if less, make a larger image and post-process to resize it down.
                if ($x < 100)
                {
                    $x = 100;
                    $post_processing = CONVERT_BIN_PATH." ".escapeshellarg($spectrogram_path)." -resize ".$dimensions[0]."x".$dimensions[1]."! ".escapeshellarg($spectrogram_path);
                }
                $sox_options .= " -x $x -y $y";
            } else {
                //if not fixed_width, first element of $dimensions gives pixels per second
                $sox_options .= " -X $x -y $y";
            }

            $command = SOX_BIN_PATH." -V0 ".escapeshellarg($audiofile)." $sox_options";
            shell_exec($command." -o ".escapeshellarg($spectrogram_path));
            //if sox does not exist, or cannot create the spectrograph, the spectrogram file should not exist
            if (file_exists($spectrogram_path)) {
                if (isset($post_processing))
                    shell_exec($post_processing);
                self::create_checksum($spectrogram_path);
                return $spectrogram_path;
            } else {
            	write_to_resource_harvesting_log("ContentManager: SoX could not produce thumbnail spectrogram for audio file $audiofile");
                trigger_error("ContentManager: SoX could not produce thumbnail spectrogram for audio file $audiofile", E_USER_NOTICE);
            }
        }
        return null;
    }

    function new_content_file_name()
    {
        $date = date("Y m d H");
        list($year, $month, $day, $hour) = explode(" ",$date);

        if(!file_exists(CONTENT_LOCAL_PATH."$year")) mkdir(CONTENT_LOCAL_PATH."$year");
        if(!file_exists(CONTENT_LOCAL_PATH."$year/$month")) mkdir(CONTENT_LOCAL_PATH."$year/$month");
        if(!file_exists(CONTENT_LOCAL_PATH."$year/$month/$day")) mkdir(CONTENT_LOCAL_PATH."$year/$month/$day");
        if(!file_exists(CONTENT_LOCAL_PATH."$year/$month/$day/$hour")) mkdir(CONTENT_LOCAL_PATH."$year/$month/$day/$hour");

        // Generate a random identifier for the data object
        // loop until we have a unique random identifier
        $file = random_digits(5);
        while(glob(CONTENT_LOCAL_PATH."$year/$month/$day/$hour/$file"."*"))
        {
            $file = random_digits(5);
        }
        return CONTENT_LOCAL_PATH."$year/$month/$day/$hour/$file";
    }

    function new_resource_file_name($resource_id)
    {
        if (isset($resource_id)) return CONTENT_RESOURCE_LOCAL_PATH.$resource_id;
        write_to_resource_harvesting_log("ContentManager: type is 'resource' but no resource id given");
        trigger_error("ContentManager: type is 'resource' but no resource id given", E_USER_NOTICE);
        return null;
    }

    function new_dataset_file_name($data_search_file_id)
    {
        if(isset($data_search_file_id)) return CONTENT_DATASET_PATH . "eol_download_" . $data_search_file_id;
        write_to_resource_harvesting_log("ContentManager: type is 'dataset' but no data_search_file_id given");
        trigger_error("ContentManager: type is 'dataset' but no data_search_file_id given", E_USER_NOTICE);
        return null;
    }

    private static function random_md5()
    {
        return md5(microtime(true) . mt_rand(10000,90000));
    }

    private static function create_checksum($file_path)
    {
        if(file_exists($file_path))
        {
            $OUT = fopen("$file_path.sha1", "w+");
            fwrite($OUT, sha1_file($file_path));
            fclose($OUT);
        }
    }

    public static function is_local($file)
    {
        //is $file an absolute path to a local file? may need tweaking under Windows OS
        return preg_match("/^\//", $file);
    }

    public static function cache_prefix($path)
    {
        //strips extensions off a path to a cached file, e.g. /content/2009/05/19/23/85866_xxx_yyy.jpg -> /content/2009/05/19/23/85866
        //assumes the file cache extension is 5 characters long (in the example, "85866")
        $extension_length = strlen(basename($path))-5;
        return(substr($path, 0, -$extension_length));
    }

    public static function cache_num2path($object_cache_num)
    {
        //convert 15 digit numeric $object_cache_url (e.g. 200905192385866) to path form (e.g. 2009/05/19/23/85866)
        return substr($object_cache_num, 0, 4)."/".substr($object_cache_num, 4, 2)."/".substr($object_cache_num, 6, 2)."/".substr($object_cache_num, 8, 2)."/".substr($object_cache_num, 10, 5);
    }

    public static function cache_path2num($path)
    {
        //return the last 15 digits to store in the $object_cache_url field
        return substr(str_replace("/", "",self::cache_prefix($path)), -15);
    }

    public function crop_image($data_object_id, $x, $y, $w, $h=NULL)
    {
        //function called by a user interaction (custom crop). If h is not given, assume a square crop
        $data_object = DataObject::find($data_object_id);
        if(!$data_object)
        {
        	write_to_resource_harvesting_log("ContentManager: Cropping invalid data object ID $data_object_id");
            trigger_error("ContentManager: Cropping invalid data object ID $data_object_id", E_USER_NOTICE);
        } elseif($data_object->is_image() && $data_object->object_cache_url)
        {
            /* we have problems because we don't actually save the filename extension of the original file.
            Until we can get this from the database, we hack around this as follows */
            $cache_path = self::cache_num2path($data_object->object_cache_url);
            if (is_file($image_url = CONTENT_LOCAL_PATH . $cache_path . "_orig.jpg")) {
                //we have a proper _orig file. Now try hard to find the original version, even through we don't have the extension
                foreach (self::$valid_image_extensions as $ext) {
                    $orig = CONTENT_LOCAL_PATH . $cache_path . "." . $ext;
                    if(is_file($orig)) {
                        $image_url = $orig;
                        break;
                    }
                }
                $sizes = getimagesize(CONTENT_LOCAL_PATH . $cache_path . "_580_360.jpg");
            } else {
                //use the online version, yuck. This hacks around the problem of having old images stored on a different filesystem
                $image_url = "http://content.eol.org/content/" . $cache_path ."_orig.jpg";
                $sizes = getimagesize("http://content.eol.org/content/" . $cache_path . "_580_360.jpg");
            }
            $image_options = array('data_object_id' => $data_object->id, 'data_object_guid' => $data_object->guid);
            // user has defined a bespoke crop region, with crop given as x & y offsets, plus a crop width & poss height.
            // Offsets are from the 580 x 360 version. However, if they are wider than
            // 540px, CSS scales the image proportionally to fit into a max width of 540.
            // The offsets and width need to be scaled to match the image dimensions
            //
            // **** Perhaps we should do this calculation in the Ruby front-end code (nearer to the css layout) rather than in php,
            // and simply pass percentages into this function? That would also stop doing a getimagesize() on the _580_360 file
            // (NB we can't use h & w from the original as it may need rotating). We could then use crop_image_pct() as below
            if(count($sizes)>=2 and $sizes[0]>0 and $sizes[1]>0)
            {
                $width = intval($sizes[0]);
                $height = intval($sizes[1]);
                $scale_factor = 1;
                if(($width / $height) < ( 540 / 360 ))
                {
                    //smaller width, so scaling only happens if height exceeds max
                    if($height > 360) $scale_factor = $height / 360;
                } else
                {
                    //smaller height, so scaling only happens if width exceeds max
                    if($width > 540) $scale_factor = $width / 540;
                }
                $x_pct = 100.0 * $x * $scale_factor/$width;
                $y_pct = 100.0 * $y * $scale_factor/$height;
                $w_pct = 100.0 * $w * $scale_factor/$width;
                $h_pct = $h ? 100.0 * $h * $scale_factor/$height : null;
                $image_options['crop_pct']=array($x_pct, $y_pct, $w_pct, $h_pct);
            } else {
            	write_to_resource_harvesting_log("ContentManager: Unable to determine image dimensions of $file, using default crop");
                trigger_error("ContentManager: Unable to determine image dimensions of $file, using default crop", E_USER_NOTICE);
            }
            return $this->grab_file($image_url, "image", $image_options);
        }
        return false;
    }

    public function crop_image_pct($data_object_id, $x_pct, $y_pct, $w_pct, $h_pct=NULL)
    {
        //function called by a user interaction (custom crop). If h is not given, assume a square crop
        $data_object = DataObject::find($data_object_id);
        if(!$data_object)
        {
        	write_to_resource_harvesting_log("ContentManager: Cropping invalid data object ID $data_object_id");
            trigger_error("ContentManager: Cropping invalid data object ID $data_object_id", E_USER_NOTICE);
        } elseif($data_object->is_image() && $data_object->object_cache_url)
        {
            /* we have problems because we don't actually save the filename extension of the original file.
            Until we can get this from the database, we hack around this as follows */
            $cache_path = self::cache_num2path($data_object->object_cache_url);
            foreach (self::$valid_image_extensions as $ext) {
                $image_url = CONTENT_LOCAL_PATH . $cache_path . "." . $ext;
                if(is_file($image_url)) break;
            }
            // If we can't find the original download, save the local or previous jpg versions as the original (yuck)
            if(!is_file($image_url)) $image_url = CONTENT_LOCAL_PATH . $cache_path . "_orig.jpg";
            if(!is_file($image_url)) $image_url = "http://content.eol.org/content/" . $cache_path ."_orig.jpg";
            return $this->grab_file($image_url, "image", array('crop_pct'=>array($x_pct, $y_pct, $w_pct, $h_pct), 'data_object_id' => $data_object->id, 'data_object_guid' => $data_object->guid));
        }
    }

    private function zip_file($file_path)
    {
        $zip = new \ZipArchive();
        $zip_path = $file_path .".zip";
        if($zip->open($zip_path, \ZipArchive::OVERWRITE) === true)
        {
            $zip->addFile($file_path, basename($file_path));
            $zip->close();
            if(file_exists($zip_path))
            {
                unlink($file_path);
                return $zip_path;
            }
        }
        return $file_path;
    }

    private function delete_old_datasets()
    {
        // **** Deletes all datasets > 14 days old. Do we *really* want to do this every time a new daaset is uploaded?
        $ls = scandir(CONTENT_DATASET_PATH);
        foreach($ls as $file)
        {
            if(strpos($file, '.') === 0) continue;
            $filepath = CONTENT_DATASET_PATH . "/" . $file;
            $days_since_modification = (time() - filemtime($filepath)) / 60 / 60 / 24;
            if($days_since_modification >= 14) unlink($filepath);
        }
    }

}

?>
