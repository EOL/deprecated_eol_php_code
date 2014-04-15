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
            if($type=="image") $this->create_content_thumbnails($new_file_path, $new_file_prefix, $options);
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
        $temp_file_path_with_extension = self::give_temp_file_right_extension($temp_file_path, $suffix, $options['unique_key']);
        $temp_file_path_with_extension = self::enforce_extentions_for_type($temp_file_path_with_extension, $options['type']);
        return $temp_file_path_with_extension;
    }

    public static function enforce_extentions_for_type($temp_file_path_with_extension, $type)
    {
        $pathinfo = pathinfo($temp_file_path_with_extension);
        $extension = strtolower($pathinfo['extension']);
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

    function create_content_thumbnails($file, $prefix, $options = array())
    {
        $local_file = $this->reduce_original($file, $prefix, $options);
        // we make an exception
        if(isset($options['large_image_dimensions']) && is_array($options['large_image_dimensions']))
        {
            $large_image_dimensions = $options['large_image_dimensions'];
        }else $large_image_dimensions = ContentManager::large_image_dimensions();
        $image_path = $this->create_smaller_version($local_file, $large_image_dimensions, $prefix, implode(ContentManager::large_image_dimensions(), '_'));
        $this->create_smaller_version($image_path, ContentManager::medium_image_dimensions(), $prefix, implode(ContentManager::medium_image_dimensions(), '_'));
        $this->create_smaller_version($image_path, ContentManager::small_image_dimensions(), $prefix, implode(ContentManager::small_image_dimensions(), '_'));
        if(isset($options['crop_width'])) $image_path = $prefix . '_orig.jpg';
        $this->create_upper_left_crop($image_path, ContentManager::large_square_dimensions(), $prefix, $options);
        $this->create_upper_left_crop($image_path, ContentManager::small_square_dimensions(), $prefix, $options);
    }

    function create_agent_thumbnails($file, $prefix)
    {
        $this->create_constrained_square_crop($file, ContentManager::large_square_dimensions(), $prefix);
        $this->create_constrained_square_crop($file, ContentManager::small_square_dimensions(), $prefix);
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

    function create_upper_left_crop($path, $dimensions, $prefix, $options = array())
    {
        if(isset($options['crop_width']))
        {
            // we have a bespoke crop region, with x & y offsets, plus a crop width
            // offsets are from the 580 x 360 version (but CSS scales them to 540 X 360. The crop will be taken from the original
            // form, so the offsets and width need to be converted to match the dimensions of the original form
            $sizes = getimagesize($path); //this is the full-sized _orig image, properly rotated
            if(@!$sizes[1])
            {
                trigger_error("ContentManager: Unable to determine image dimensions $file, using default crop", E_USER_NOTICE);
            } else
            {
                $width = $sizes[0];
                $height = $sizes[1];
                $offset_factor = 1;
                if(($width / $height) < ( 540 / 360 ))
                {
                    if($height > 360) $offset_factor = $height / 360;
                } else
                {
                    if($width > 540) $offset_factor = $width / 540;
                }
                $new_crop_width = floatval($options['crop_width']) * $offset_factor;
                $new_x_offset = floatval($options['x_offset']) * $offset_factor;
                $new_y_offset = floatval($options['y_offset']) * $offset_factor;

                $command = CONVERT_BIN_PATH. " $path -strip -background white -flatten -quiet -quality 80 -gravity NorthWest \
                        -crop ".$new_crop_width."x".$new_crop_width."+".$new_x_offset."+".$new_y_offset." +repage \
                        -resize ".$dimensions[0]."x".$dimensions[0];
            }
        }else
        {
            // default command just makes the image square by cropping the edges: see http://www.imagemagick.org/Usage/resize/#fill
            $command = CONVERT_BIN_PATH. " $path -strip -background white -flatten -quiet -quality 80 \
                            -resize ".$dimensions[0]."x".$dimensions[0]."^ \
                            -gravity NorthWest -crop ".$dimensions[0]."x".$dimensions[0]."+0+0 +repage";
        }
        $new_image_path = $prefix ."_". $dimensions[0] ."_". $dimensions[0] .".jpg";
        shell_exec($command." ".$new_image_path);
        self::create_checksum($new_image_path);
    }

    function create_constrained_square_crop($path, $dimensions, $prefix)
    {
        // requires "convert" to support -gravity center -extent: ImageMagick >= 6.3.2
        $command = CONVERT_BIN_PATH." $path -strip -background white -flatten -auto-orient -quiet -quality 80 \
                        -resize '".$dimensions[0]."x".$dimensions[0]."' -gravity center \
                        -extent '".$dimensions[0]."x".$dimensions[0]."' +repage";
        $new_image_path = $prefix."_".$dimensions[0]."_".$dimensions[0].".jpg";
        shell_exec($command." ".$new_image_path);
        self::create_checksum($new_image_path);
        return $new_image_path;
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

    public function crop_image($data_object_id, $x, $y, $w)
    {
        $data_object = DataObject::find($data_object_id);
        if(!$data_object)
        {
            trigger_error("ContentManager: Invalid data object ID $data_object_id", E_USER_NOTICE);
            return false;
        }
        if($data_object->is_image() && $data_object->object_cache_url)
        {
            $cache_path = self::cache_path($data_object->object_cache_url);
            $image_url = CONTENT_LOCAL_PATH . $cache_path ."_orig.jpg";
            if(!file_exists($image_url)) $image_url = "http://content71.eol.org/content/" . $cache_path ."_orig.jpg";
            return $this->grab_file($image_url, "image", array('x_offset' => $x, 'y_offset' => $y, 'crop_width' => $w));
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
