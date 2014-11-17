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

    function grab_file($file, $type, $options = array())
    {
        if(@!$options['timeout']) $options['timeout'] = DOWNLOAD_TIMEOUT_SECONDS;
        $options['type'] = $type;
        if($temp_file_path = self::download_temp_file_and_assign_extension($file, array_merge($options, array('unique_key' => $this->unique_key, 'is_resource' => ($type == "resource")))))
        {
            $suffix = null;
            if(preg_match("/\.(.*)$/", $temp_file_path, $arr)) $suffix = strtolower(trim($arr[1]));
            if(!$suffix && $type != 'resource') return false;

            switch($type) {
                case "resource":
                    if(!isset($options['resource_id']) || !$options['resource_id']) {
                        trigger_error("ContentManager: type is 'resource' but no resource id given", E_USER_NOTICE);
                        return false;
                    }
                    if(!$suffix)
                    {   // this would be a DwC-A resource
                        $resource_archive_directory = $this->new_resource_file_name($options['resource_id']);
                        // first delete the archive directory that currently exists
                        recursive_rmdir($resource_archive_directory);
                        // move the temp, uncompressed directory to its new home with the resources
                        rename($temp_file_path, $resource_archive_directory);
                        return $resource_archive_directory;
                    }

                    $new_file_prefix = $this->new_resource_file_name($options['resource_id']);
                    break;
                case "image":
                case "video":
                case "audio":
                case "upload":
                case "partner":
                    $new_file_prefix = $this->new_content_file_name();
                    break;
                case "dataset":
                    $new_file_prefix = $this->new_dataset_file_name($options);
                    break;
                default:
                    trigger_error("ContentManager: non-valid type (".$type.")", E_USER_NOTICE);
                    return false;
            }
            if(!$new_file_prefix) return false;
            $new_file_path = $new_file_prefix . "." . $suffix;

            // copy temporary file into its new home
            copy($temp_file_path, $new_file_path);
            // fail if for some reason there is still no file at the new path
            if(!file_exists($new_file_path))
            {
                if(file_exists($temp_file_path)) unlink($temp_file_path);
                trigger_error("ContentManager: Unable to download file $file", E_USER_NOTICE);
                return false;
            }

            // create thumbnails of website content and agent logos
            if($type=="image") $this->create_local_files_and_thumbnails($new_file_path, $new_file_prefix, $options);
            elseif($type=="partner") $this->create_agent_thumbnails($new_file_path, $new_file_prefix);
            elseif($type=="dataset")
            {
                $new_file_path = $this->zip_file($new_file_path);
                $this->delete_old_datasets();
            }

            if(in_array($type, array("image", "video", "audio", "upload", "partner"))) self::create_checksum($new_file_path);

            // Take the substring of the new file path to return via the webservice
            if(($type=="image" || $type=="video" || $type=="audio" || $type=="partner" || $type=="upload") &&
              preg_match("/^".preg_quote(CONTENT_LOCAL_PATH, "/")."(.*)\.[^\.]+$/", $new_file_path, $arr))
            {
                $new_file_path = str_replace("/", "", $arr[1]);
            }
            elseif($type=="resource" &&
              preg_match("/^".preg_quote(CONTENT_RESOURCE_LOCAL_PATH, "/")."(.*)$/", $new_file_path, $arr))  $new_file_path = $arr[1];
            elseif($type=="dataset" &&
              preg_match("/^".preg_quote(CONTENT_DATASET_PATH, "/")."(.*)$/", $new_file_path, $arr))  $new_file_path = $arr[1];
        }

        if(file_exists($temp_file_path)) unlink($temp_file_path);
        if(isset($new_file_path) && $new_file_path) return $new_file_path;
        return null;
    }

    public static function download_temp_file_and_assign_extension($file_path_or_uri, $options = array())
    {
        if(!isset($options['is_resource'])) $options['is_resource'] = false;
        if(@!$options['unique_key']) $options['unique_key'] = Functions::generate_guid();
        if(@!$options['timeout']) $options['timeout'] = DOWNLOAD_TIMEOUT_SECONDS;

        $suffix = null;
        if(@$options['suffix']) $suffix = $options['suffix'];
        elseif(preg_match("/\.([^\.]+)$/", $file_path_or_uri, $arr)) $suffix = strtolower(trim($arr[1]));

        // resources may need a little extra time to establish a connection
        if($options['is_resource'] && $options['timeout'] < 60) $options['timeout'] = 60;

        $temp_file_path = CONTENT_TEMP_PREFIX . $options['unique_key'] . ".file";
        if(preg_match("/^(http|https|ftp):\/\//", $file_path_or_uri) || preg_match("/^\//", $file_path_or_uri))
        {
            if($file_contents = Functions::get_remote_file($file_path_or_uri, array('timeout' => $options['timeout'])))
            {
                // if this is a resource then update the old references to the schema
                // there were a few temporary locations for the schema which were being used by early providers
                // and not all of them have been updated
                if($options['is_resource'])
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
        $temp_file_path_with_extension = self::give_temp_file_right_extension($temp_file_path, $suffix, @$options['unique_key']);
        $temp_file_path_with_extension = self::enforce_extentions_for_type($temp_file_path_with_extension, @$options['type']);
        return $temp_file_path_with_extension;
    }

    public static function enforce_extentions_for_type($temp_file_path_with_extension, $type)
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
                    self::move_up_if_only_directory($new_temp_file_path);
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

    static function square_sizes()
    {
        static $list_of_sizes = array(88, 130);
        return $list_of_sizes;
    }

    function create_local_files_and_thumbnails($file, $prefix, $options = array())
    {
        $fullsize_jpg = $this->reduce_original($file, $prefix, $options);
        
        if(file_exists($fullsize_jpg)) {
            $sizes = getimagesize($fullsize_jpg);

            // we make an exception
            if(isset($options['large_image_dimensions']) && is_array($options['large_image_dimensions']))
            {
                $large_image_dimensions = $options['large_image_dimensions'];
            } else $large_image_dimensions = ContentManager::large_image_dimensions();
            $big_jpg = $this->create_smaller_version($fullsize_jpg, $large_image_dimensions, $prefix, implode(ContentManager::large_image_dimensions(), '_'));
            $this->create_smaller_version($fullsize_jpg, ContentManager::medium_image_dimensions(), $prefix, implode(ContentManager::medium_image_dimensions(), '_'));
            $this->create_smaller_version($fullsize_jpg, ContentManager::small_image_dimensions(), $prefix, implode(ContentManager::small_image_dimensions(), '_'));

            if (isset($options['crop_pct']) {
                //if this image has a custom crop, it could be of a tiny region, so use the full size image, to avoid pixellation
                $crop = $this->create_crops($fullsize_jpg, ContentManager::square_sizes(), $prefix, @$sizes[0], @$sizes[1], $options['crop_pct']);
            } else {
                //we are taking the default big crop, so to save cpu time, don't bother cropping the full size image, just use the 580_360 version
                $crop = $this->create_crops($big_jpg, ContentManager::square_sizes(), $prefix);
            }
            
            if (count($sizes) < 2 or $sizes[0]<=0 or $sizes[1]<=0)
            {
                trigger_error("ContentManager: Unable to getimagesize for $file: used default crop without recording image dimensions", E_USER_NOTICE);
            } else {
                if (isset($options['data_objects_id'])) {
                    //assume a row for this data_object_id has already been created in image_sizes (should have been done in DataObjects::cache_object)
                    $sql = sprintf("SET width=%u,height=%u", $width, $height)
                    if (count($crop)>=4) {
                        //also set the crop values at the same time
                        $sql .= sprintf(",crop_x_pct=%.2F,crop_y_pct=%.2F,crop_width_pct=%.2F,crop_height_pct=%.2F", $crop[0],$crop[1],$crop[2],$crop[3]);
                    }
                    $GLOBALS['mysqli_connection']->update("UPDATE image_sizes ".$sql." WHERE data_object_id=".intval($options['data_objects_id']));
                }
            }
        }
    }

    function create_agent_thumbnails($file, $prefix)
    {
        $this->create_constrained_square_crops($file, ContentManager::square_sizes(), $prefix);
    }

    function reduce_original($path, $prefix, $options = array())
    {
        $rotate = "-auto-orient";
        if(isset($options['rotation'])) $rotate = "-rotate ". intval($options['rotation']);
        $command = CONVERT_BIN_PATH." $path -strip -background white -flatten $rotate -quiet -quality 80";
        $new_image_path = $prefix."_orig.jpg";
        shell_exec($command." ".$new_image_path);
        self::create_checksum($new_image_path);
        return $new_image_path;
    }

    function create_smaller_version($path, $dimensions, $prefix, $suffix)
    {
        //don't need to rotate, as this works on already-rotated version
        $command = CONVERT_BIN_PATH." $path -strip -background white -flatten -quiet -quality 80 \
                        -resize ".$dimensions[0]."x".$dimensions[1]."\">\"";
        $new_image_path = $prefix ."_". $suffix .".jpg";
        shell_exec($command." ".$new_image_path);
        self::create_checksum($new_image_path);
        return $new_image_path;
    }

    function create_crops($path, $list_of_square_sizes, $prefix, $width=NULL, $height=NULL, $crop_percentages=NULL)
    {
        //if called with $crop != NULL, returns the crop area in percentages, and the image size
        $command_start = CONVERT_BIN_PATH. " $path -strip -background white -flatten -quiet -quality 80"
        // default latter part of command just makes the image square by cropping the edges: see http://www.imagemagick.org/Usage/resize/#fill 
        // any %1$u characters will be substituted by the crop size using sprintf
        $command_end = "-resize %1$ux%1$u^ -gravity NorthWest -crop %1$ux%1$u+0+0 +repage";
        if($width && $height && count($crop_percentages)>=4)
        {
            array_walk($crop_percentages, array("Functions", "truncate_from_0_100"));
            $x = intval(round($crop_percentages[0]/100.0*$width));
            $y = intval(round($crop_percentages[1]/100.0*$height));
            $w = intval(round($crop_percentages[2]/100.0*$width));
            $h = intval(round($crop_percentages[3]/100.0*$height));
            $command_end = "-gravity NorthWest -crop $wx$h+$x+$y +repage -resize %1$ux%1$u";
        }
        
        foreach ($sq_dim as $list_of_square_sizes) {
            $command_end = sprintf($command_end, $sq_dim)
            $new_image_path = $prefix."_".$sq_dim."_".$sq_dim.".jpg";
            shell_exec($command_start." ".$command_end." ".$new_image_path);
            self::create_checksum($new_image_path);
        }
        return $crop_percentages;
    }

    function create_constrained_square_crops($path, $list_of_square_sizes, $prefix)
    {
        foreach ($sq_dim as $list_of_square_sizes) {
            // requires "convert" to support -gravity center -extent: ImageMagick >= 6.3.2
            $command = CONVERT_BIN_PATH." $path -strip -background white -flatten -auto-orient -quiet -quality 80 \
                            -resize '".$sq_dim."x".$sq_dim."' -gravity center \
                            -extent '".$sq_dim."x".$sq_dim."' +repage";
            $new_image_path = $prefix."_".$sq_dim."_".$sq_dim.".jpg";
            shell_exec($command." ".$new_image_path);
            self::create_checksum($new_image_path);
        }
    }

    function new_resource_file_name($resource_id)
    {
        return CONTENT_RESOURCE_LOCAL_PATH.$resource_id;
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

    function new_dataset_file_name($options)
    {
        if(!$options['data_search_file_id']) return false;
        $file_path = CONTENT_DATASET_PATH . "eol_download_" . $options['data_search_file_id'];
        return $file_path;
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

    public static function cache_path($object_cache_url)
    {
        return substr($object_cache_url, 0, 4)."/".substr($object_cache_url, 4, 2)."/".substr($object_cache_url, 6, 2)."/".substr($object_cache_url, 8, 2)."/".substr($object_cache_url, 10, 5);
    }

    public function crop_image($data_object_id, $x, $y, $w, $h=NULL)
    {
        //function called by a user interaction (custom crop)
        $data_object = DataObject::find($data_object_id);
        if(!$data_object)
        {
            trigger_error("ContentManager: Invalid data object ID $data_object_id", E_USER_NOTICE);
        } elseif($data_object->is_image() && $data_object->object_cache_url)
        {
            $cache_path = self::cache_path($data_object->object_cache_url);
            $image_url = CONTENT_LOCAL_PATH . $cache_path ."_orig.jpg";
            if(!file_exists($image_url)) $image_url = "http://content71.eol.org/content/" . $cache_path ."_orig.jpg";
            if (is_null($h)) $h=$w;
            
            // user has defined a bespoke crop region, with crop given as x & y offsets, plus a crop width & poss height.
            // Offsets are from the 580 x 360 version. However, if they are wider than 
            // 540px, CSS scales the image proportionally to fit into a max width of 540.
            // The passed-in image may either be the 580_360 version or the original full-sized image, properly rotated.
            // The offsets and width need to be scaled to match the image dimensions
            $sizes = getimagesize($image_url);
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
                $x_pct = 100.0 * $x * $scale_factor/$width
                $y_pct = 100.0 * $y * $scale_factor/$height
                $w_pct = 100.0 * $w * $scale_factor/$width
                $h_pct = 100.0 * $h * $scale_factor/$height
                return $this->grab_file($image_url, "image", array('crop_pct' => array($x_pct, $y_pct, $w_pct, $h_pct), 'data_object_id' = $data_object_id);
            } else {
                trigger_error("ContentManager: Unable to determine image dimensions of $file, using default crop", E_USER_NOTICE);
            }
        }
        return false;
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
