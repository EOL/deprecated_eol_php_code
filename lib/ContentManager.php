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
        if ((preg_match("/^\//", $file)) && is_file($file) && !in_array($extension, array('gz', 'gzip', 'zip', 'tar'), true)) {
            //hack required because we don't have the originally downloaded file extension, so simply give the new version the same extension as the old one
            $permanent_file_path = $permanent_prefix;
            if (strlen($extension)) $permanent_file_path .= '.'.$extension;
            if (link($file, $permanent_file_path)) {
                $cache_file_path = $file;
                if($GLOBALS['ENV_DEBUG']) echo "Hard link created (old file is $file, now linked at $permanent_file_path)\n";
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
                // **** Perhaps the two following lines should be simply a "rename"?
                copy($temp_file_path, $permanent_file_path);
                if(file_exists($temp_file_path)) unlink($temp_file_path);
            } else {
                //no suffix
                if ($type !== 'resource') {
                    if(file_exists($temp_file_path)) unlink($temp_file_path);
                    return false;
                };
                $cache_file_path = $permanent_file_path = $permanent_prefix;
                // first delete the archive directory that currently exists
                recursive_rmdir($permanent_file_path);
                // move the temp, uncompressed directory to its new home with the resources
                rename($temp_file_path, $permanent_file_path);
            }

            // fail if for some reason there is still no file at the new path
            if(!file_exists($permanent_file_path))
            {
                trigger_error("ContentManager: Unable to download file $file", E_USER_NOTICE);
                return false;
            }
        }
        
        // create thumbnails of website content and agent logos
        if    ($type==="image")   $this->create_local_files_and_thumbnails($cache_file_path, $permanent_prefix, $options);
        elseif($type==="partner") $this->create_agent_thumbnails($cache_file_path, $permanent_prefix);
        elseif($type==="dataset")
        {
            $permanent_file_path = $this->zip_file($cache_file_path);
            $this->delete_old_datasets();
        }

        //return the cache file location: for most object this is the concatenated numbers in the cache dirs, so we lose the file extension
        //in this case we should probably return an array of the number plus extension, so we can access the original downloaded file.
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
        trigger_error("ContentManager: non-valid type (".$type.")", E_USER_NOTICE);
        return null;
    }

    public static function download_temp_file_and_assign_extension($file_path_or_uri, $type, $options = array())
    {
        if(@!$options['unique_key']) $options['unique_key'] = Functions::generate_guid();
        if(@!$options['timeout']) $options['timeout'] = DOWNLOAD_TIMEOUT_SECONDS;

        $suffix = null;
        if(@$options['suffix']) $suffix = $options['suffix'];
        elseif(preg_match("/\.([^\.]+)$/", $file_path_or_uri, $arr)) $suffix = strtolower(trim($arr[1]));

        // resources may need a little extra time to establish a connection
        if(($type === 'resource') && $options['timeout'] < 60) $options['timeout'] = 60;

        $temp_file_path = CONTENT_TEMP_PREFIX . $options['unique_key'] . ".file";
        if(preg_match("/^(http|https|ftp):\/\//", $file_path_or_uri) || preg_match("/^\//", $file_path_or_uri))
        {
            if($file_contents = Functions::get_remote_file($file_path_or_uri, array('timeout' => $options['timeout'])))
            {
                // if this is a resource then update the old references to the schema
                // there were a few temporary locations for the schema which were being used by early providers
                // and not all of them have been updated
                if($type === 'resource')
                {
                    $file_contents = str_replace("http://www.eol.org/transfer/data/0.1",
                                                 "http://www.eol.org/transfer/content/0.1", $file_contents);
                    $file_contents = str_replace("http://services.eol.org/development/pleary/xml/content4.xsd",
                                                 "http://services.eol.org/schema/content_0_1.xsd", $file_contents);
                }

                $TMP = fopen($temp_file_path,"w+");
                fwrite($TMP, $file_contents);
                fclose($TMP);
            }
        }
        if (is_file($temp_file_path)) {
            $temp_file_path_with_extension = self::give_temp_file_right_extension($temp_file_path, $suffix, @$options['unique_key']);
            $temp_file_path_with_extension = self::enforce_extensions_for_type($temp_file_path_with_extension, $type);
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
        // if the download succeeded
        if(file_exists($temp_file_path))
        {
            if(SYSTEM_OS == "Windows") $new_suffix = self::determine_file_suffix_pc($temp_file_path, $original_suffix);
            else $new_suffix = self::determine_file_suffix($temp_file_path, $original_suffix);

            if($new_suffix)
            {
                $new_temp_file_path = CONTENT_TEMP_PREFIX . $unique_key . "." . $new_suffix;
                // copy temporary file from $PATH.file to $PATH.tar.gz for example
                rename($temp_file_path, $new_temp_file_path);

                // fail if for some reason there is still no file at the new path
                if(!file_exists($new_temp_file_path))
                {
                    if(file_exists($temp_file_path)) unlink($temp_file_path);
                    trigger_error("ContentManager: Unable to download file $file_path_or_uri", E_USER_NOTICE);
                    return false;
                }
                if(preg_match("/^(.*)\.(gz|gzip)$/", $new_temp_file_path, $arr))
                {
                    shell_exec(GUNZIP_BIN_PATH . " -f $new_temp_file_path");
                    $new_temp_file_path = $arr[1];
                    return self::give_temp_file_right_extension($new_temp_file_path, $original_suffix, $unique_key);
                    self::move_up_if_only_directory($new_temp_file_path); //Comment by Yan: this seems redundent.
                }
                if(preg_match("/^(.*)\.(tar)$/", $new_temp_file_path, $arr))
                {
                    $archive_directory = $arr[1];
                    @unlink($archive_directory);
                    @rmdir($archive_directory);
                    mkdir($archive_directory);

                    shell_exec(TAR_BIN_PATH . " -xf $new_temp_file_path -C $archive_directory");
                    if(file_exists($new_temp_file_path)) unlink($new_temp_file_path);
                    $new_temp_file_path = $archive_directory;
                    self::move_up_if_only_directory($new_temp_file_path);
                }
                if(preg_match("/^(.*)\.(zip)$/", $new_temp_file_path, $arr))
                {
                    $archive_directory = $arr[1];
                    @unlink($archive_directory);
                    @rmdir($archive_directory);
                    mkdir($archive_directory);

                    shell_exec(UNZIP_BIN_PATH . " -d $archive_directory $new_temp_file_path");
                    if(file_exists($new_temp_file_path)) unlink($new_temp_file_path);
                    $new_temp_file_path = $archive_directory;
                    self::move_up_if_only_directory($new_temp_file_path);
                }
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
            // there can be only one only file
            if($only_file) return;
            if(substr($file, 0, 1) == ".") continue;
            $only_file = $directory_path ."/". $file;
        }
        if(is_dir($only_file))
        {
            rename($only_file, $directory_path."_swap");
            rmdir($directory_path);
            rename($directory_path."_swap", $directory_path);
        }
    }

    public static function determine_file_suffix($file_path, $suffix)
    {
        // use the Unix/Linux `file` command to determine file type
        $stat = strtolower(shell_exec(FILE_BIN_PATH . " " . $file_path));
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
            if($suffix == "xlsx")                                                       $new_suffix = "xlsx";
            else                                                                        $new_suffix = "zip";
        }

        // other - xml, html, pdf
        elseif(preg_match("/^xml( |$)/i", $file_type) || preg_match("/xml$/i", $file_type)) $new_suffix = "xml";
        elseif(preg_match("/^pdf( |$)/i", $file_type))                                  $new_suffix = "pdf";
        elseif($suffix == "csv" && preg_match("/^html /i", $file_type))                 $new_suffix = "csv";
        elseif(preg_match("/^html( |$)/i", $file_type))                                 $new_suffix = "html";
        elseif(preg_match("/ Excel(,|$)/i", $file_type))                                $new_suffix = "xls";
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
            trigger_error("ContentManager: Unable to create jpg file from downloaded file $original_file.", E_USER_NOTICE);
            return false;
        }
        //get the size from the jpg version, which is properly orientated
        $sizes = getimagesize($fullsize_jpg);
        $width = @$sizes[0];
        $height = @$sizes[1];
        if (empty($width) && empty($height))
            trigger_error("ContentManager: Unable to getimagesize for $fullsize_jpg: using default crop and not recording image_size data", E_USER_NOTICE);

        // we make an exception
        if(isset($options['large_image_dimensions']) && is_array($options['large_image_dimensions']))
        {
            $large_image_dimensions = $options['large_image_dimensions'];
        } else $large_image_dimensions = ContentManager::large_image_dimensions();
        $big_jpg = $this->create_smaller_version($fullsize_jpg, $large_image_dimensions, $prefix, implode(ContentManager::large_image_dimensions(), '_'));
        $this->create_smaller_version($fullsize_jpg, ContentManager::medium_image_dimensions(), $prefix, implode(ContentManager::medium_image_dimensions(), '_'));
        $this->create_smaller_version($fullsize_jpg, ContentManager::small_image_dimensions(), $prefix, implode(ContentManager::small_image_dimensions(), '_'));

        $crop = $this->get_saved_crop_or_initialize(@$options['data_object_id']);
        $new_crop = @$options['crop_pct'];

        if ((count($new_crop)>=4) || (count($crop) >= 4))
        {
            if (count($new_crop)>=4) $crop=$new_crop;
            //if this image has a custom crop, it could be of a tiny region, so use the huge image, to avoid pixellation.
            //Ideally, we'd use the original image but it could be rotated differently, and we don't currently store rotation information in the DB.
            $this->create_crop($fullsize_jpg, ContentManager::large_square_dimensions(), $prefix, $width, $height, $crop);
            $this->create_crop($fullsize_jpg, ContentManager::small_square_dimensions(), $prefix, $width, $height, $crop);
        } else {
            //we are taking the default big crop, so to save cpu time, don't bother cropping the full size image, just use the 580_360 version
            $this->create_crop($big_jpg, ContentManager::large_square_dimensions(), $prefix);
            $this->create_crop($big_jpg, ContentManager::small_square_dimensions(), $prefix);
        }
        //update width & height in case they have changed, but only change % crop values if we have a $new_crop
        $this->save_image_size_data(@$options['data_object_id'], $width, $height, $new_crop);
    }

    function create_agent_thumbnails($file, $prefix)
    {
        $this->create_constrained_square_crops($file, ContentManager::large_square_dimensions(), $prefix);
        $this->create_constrained_square_crops($file, ContentManager::small_square_dimensions(), $prefix);
    }

    function get_saved_crop_or_initialize($data_object_id)
    {
        if (isset($data_object_id)) {
            // Check if the image_size db entry exists
            $resp = $GLOBALS['mysqli_connection']->query("SELECT crop_x_pct, crop_y_pct, crop_width_pct, crop_height_pct FROM image_sizes WHERE data_object_id=$data_object_id LIMIT 1");
            if ($resp) {
                if ($resp->num_rows) {
                    $crop = $resp->fetch_row();
                    if (isset($crop[0]) and isset($crop[1]) and isset($crop[2]))
                        return $crop;
                } else {
                    //DB entry for this data_obj_id doesn't exist: create the image_size entry in the DB (height, etc may be filled in later)
                    $GLOBALS['mysqli_connection']->insert("INSERT IGNORE INTO image_sizes (data_object_id) VALUES ($data_object_id)");
                }
            } else {
                trigger_error("ContentManager: Database error while getting data_object $data_object_id from image_sizes table", E_USER_NOTICE);
            }
        }
        return NULL;
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


    function hard_link_to_existing($old_file, $prefix, $new_suffix)
    {
        //if given an $old_file that does not match $prefix, look for a previously cached old equivalent with $suffix
        $new_file = $prefix.$new_suffix;
        $old_file_prefix = self::cache_prefix($old_file);
        if ($old_file_prefix != $prefix) {
            //look for an already existing equivalent of $old_file with the new suffix we can link to
            if (file_exists($old_equivalent = $old_file_prefix.$new_suffix)) {
                if (link($old_equivalent, $new_file)) {
                    if($GLOBALS['ENV_DEBUG']) echo "Hard link created (old file is $old_equivalent, now linked at $new_file)\n";
                    self::create_checksum($new_file);
                    //return the old version, to indicate to future calls that other cached files may be available
                    return $old_equivalent;
                }
            }
        }
        return false;
    }

    function reduced_original($path, $prefix, $options = array())
    {
        $suffix = "_orig.jpg";
        $new_image_path = $prefix.$suffix;
        if ($link = $this->hard_link_to_existing($path, $prefix, $suffix)) return $link;
        
        $rotate = "-auto-orient";
        if(isset($options['rotation'])) $rotate = "-rotate ". intval($options['rotation']);
        $command = CONVERT_BIN_PATH." $path -strip -background white -flatten $rotate -quiet -quality 80";
        shell_exec($command." ".$new_image_path);
        self::create_checksum($new_image_path);
        return $new_image_path;
    }

    function create_smaller_version($path, $dimensions, $prefix, $suffix_dims)
    {
        //N.B. we don't need to rotate, as this works on already-rotated version
        $suffix = "_". $suffix_dims .".jpg";
        $new_image_path = $prefix.$suffix;
        if ($link = $this->hard_link_to_existing($path, $prefix, $suffix)) return $link;

        $command = CONVERT_BIN_PATH." $path -strip -background white -flatten -quiet -quality 80 \
                        -resize ".$dimensions[0]."x".$dimensions[1]."\">\"";
        shell_exec($command." ".$new_image_path);
        self::create_checksum($new_image_path);
        return $new_image_path;
    }

    function create_crop($path, $dimensions, $prefix, $width=NULL, $height=NULL, &$crop_percentages=NULL)
    {
        //never look to hard link to old versions, as the crop size may have changed.
        //if called with $crop != NULL, returns the crop area in percentages, and the image size
        $command = CONVERT_BIN_PATH. " $path -strip -background white -flatten -quiet -quality 80";
        // default latter part of command just makes the image square by cropping the edges: see http://www.imagemagick.org/Usage/resize/#fill 
        // any %1$u characters will be substituted by the crop size using sprintf
        if($width && $height && count($crop_percentages)>=4)
        {
            foreach($crop_percentages as &$p) if ($p < 0) $p = 0; elseif ($p > 100) $p = 100;

            $x = intval(round($crop_percentages[0]/100.0*$width));
            $y = intval(round($crop_percentages[1]/100.0*$height));
            $w = intval(round($crop_percentages[2]/100.0*$width));
            $h = $crop_percentages[3] ? intval(round($crop_percentages[3]/100.0*$height)) : $w;
            $command .= ' -gravity NorthWest -crop '.$w.'x'.$h.'+'.$x.'+'.$y.' +repage -resize '.$dimensions[0].'x'.$dimensions[1].'\!';
        } else {
            $command .= ' -resize '.$dimensions[0].'x'.$dimensions[1].'^ -gravity NorthWest -crop '.$dimensions[0]."x".$dimensions[1].'+0+0 +repage';
        }
        
        $new_image_path = $prefix."_".$dimensions[0].'_'.$dimensions[1].".jpg";
        shell_exec($command." ".$new_image_path);
        self::create_checksum($new_image_path);
    }

    function create_constrained_square_crops($path, $list_of_square_sizes, $prefix)
    {
        // requires "convert" to support -gravity center -extent: ImageMagick >= 6.3.2
        $command = CONVERT_BIN_PATH." $path -strip -background white -flatten -auto-orient -quiet -quality 80 \
                        -resize '".$dimensions[0]."x".$dimensions[1]."' -gravity center \
                        -extent '".$dimensions[0]."x".$dimensions[1]."' +repage";
        $new_image_path = $prefix."_".$dimensions[0]."_".$dimensions[0].".jpg";
        shell_exec($command." ".$new_image_path);
        self::create_checksum($new_image_path);
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
        trigger_error("ContentManager: type is 'resource' but no resource id given", E_USER_NOTICE);
        return null;
    }

    function new_dataset_file_name($data_search_file_id)
    {
        if(isset($data_search_file_id)) return CONTENT_DATASET_PATH . "eol_download_" . $data_search_file_id;
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
            if(!is_file($image_url)) $image_url = "http://content71.eol.org/content/" . $cache_path ."_orig.jpg";

            $image_options = array('data_object_id' => $data_object_id);
            // user has defined a bespoke crop region, with crop given as x & y offsets, plus a crop width & poss height.
            // Offsets are from the 580 x 360 version. However, if they are wider than 
            // 540px, CSS scales the image proportionally to fit into a max width of 540.
            // The offsets and width need to be scaled to match the image dimensions
            //
            // **** Perhaps we should do this calculation in the Ruby front-end code (nearer to the css layout) rather than in php, 
            // and simply pass percentages into this function? That would also stop doing a getimagesize() on the _580_360 file
            // (NB we can't use h & w from the original as it may need rotating). We could then use crop_image_pct() as below
            $sizes = getimagesize(CONTENT_LOCAL_PATH . $cache_path . "_580_360.jpg");
            if(count($sizes)>=2 and $sizes[0]>0 and $sizes[1]>0)
            {
                $width = $sizes[0];
                $height = $sizes[1];
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
            if(!is_file($image_url)) $image_url = "http://content71.eol.org/content/" . $cache_path ."_orig.jpg";
            return $this->grab_file($image_url, "image", array('crop_pct'=>array($x_pct, $y_pct, $w_pct, $h_pct), 'data_object_id' => $data_object_id));
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
