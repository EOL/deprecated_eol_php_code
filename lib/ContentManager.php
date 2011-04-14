<?php

class ContentManager
{
    private $remote_server_ip;
    private $unique_key;
    
    function __construct($server_ip = 0)
    {
        $this->remote_server_ip = $server_ip;
        $this->unique_key = Functions::generate_guid();
    }
    
    
    // This function will download a file and place it on the content server
    //
    // Possible types for this function
    // image - this is for images
    // video - this is for videos - mpg, flv, mp4, etc...
    // partner - this type means we are downloading a logo for a content partner
    // resource - this means we are downloading an XML or zipped file of the EOL schema for processing
    
    function grab_file($file, $resource_id, $type, $large_thumbnail_dimensions = CONTENT_IMAGE_LARGE, $timeout = DOWNLOAD_TIMEOUT_SECONDS)
    {
        $new_file_path = "";
        $suffix = "";
        if(preg_match("/\.([^\.]+)$/",$file,$arr)) $suffix = strtolower(trim($arr[1]));
        
        // resources may need a little extra time to establish a connection
        if($type == "resource") $timeout = 60;
        
        $temp_file_path = CONTENT_TEMP_PREFIX.$this->unique_key.".file";
        if(preg_match("/^http:\/\//",$file))
        {
            if($file_contents = Functions::get_remote_file($file, DOWNLOAD_WAIT_TIME, $timeout))
            {
                // if this is a resource then update the old references to the schema
                // there were a few temporary locations for the schema which were being used by early providers
                // and not all of them have been updated
                if($type=="resource")
                {
                    $file_contents = str_replace("http://www.eol.org/transfer/data/0.1", "http://www.eol.org/transfer/content/0.1", $file_contents);
                    $file_contents = str_replace("http://services.eol.org/development/pleary/xml/content4.xsd", "http://services.eol.org/schema/content_0_1.xsd", $file_contents);
                }
                
                $TMP = fopen($temp_file_path,"w+");
                fwrite($TMP, $file_contents);
                fclose($TMP);
            }
        }
        
        // if the download succeeded
        if(file_exists($temp_file_path))
        {
            if(SYSTEM_OS == "Windows") $new_suffix = $this->determine_file_suffix_pc($temp_file_path,$suffix);
            else $new_suffix = $this->determine_file_suffix($temp_file_path,$suffix);
            
            if($new_suffix)
            {
                if($type=="image") $new_file_prefix = $this->new_content_file_name();
                elseif($type=="video") $new_file_prefix = $this->new_content_file_name();
                elseif($type=="sound") $new_file_prefix = $this->new_content_file_name();
                elseif($type=="partner") $new_file_prefix = $this->new_partner_file_name();
                elseif($type=="resource") $new_file_prefix = $this->new_resource_file_name($resource_id);
                
                $new_file_path = $new_file_prefix.".".$new_suffix;
                
                // copy temporary file into its new home
                //shell_exec("cp ".$temp_file_path." ".$new_file_path);
                copy($temp_file_path, $new_file_path);
                
                // fail if for some reason there is still no file at the new path
                if(!file_exists($new_file_path))
                {
                    trigger_error("ContentManager: Unable to download file $file", E_USER_NOTICE);
                    return false;
                }
                
                if(preg_match("/^(.*)\.gz$/", $new_file_path, $arr))
                {
                    shell_exec("mv ".$new_file_path." ".$arr[1].".xml.gz");
                    shell_exec("gunzip -f ".$arr[1].".xml.gz");
                    $new_file_path = $arr[1].".xml";
                }
                
                // create thumbnails of website content and agent logos
                if($type=="image") $this->create_content_thumbnails($new_file_path, $new_file_prefix, $large_thumbnail_dimensions);
                elseif($type=="partner") $this->create_agent_thumbnails($new_file_path, $new_file_prefix);
                
                // Take the substring of the new file path to return via the webservice
                if(($type=="image" || $type=="video" || $type=="sound") && preg_match("/^".preg_quote(CONTENT_LOCAL_PATH, "/")."(.*)\.[^\.]+$/",$new_file_path,$arr)) $new_file_path = str_replace("/", "", $arr[1]);
                elseif($type=="partner" && preg_match("/^".preg_quote(CONTENT_PARTNER_LOCAL_PATH, "/")."(.*)\.[^\.]+$/",$new_file_path,$arr)) $new_file_path = $arr[1];
                elseif($type=="resource" && preg_match("/^".preg_quote(CONTENT_RESOURCE_LOCAL_PATH, "/")."(.*)$/",$new_file_path,$arr))  $new_file_path = $arr[1];
                
            }
        }
        
        if(file_exists($temp_file_path)) unlink($temp_file_path);
        return $new_file_path;
    }

    
    function determine_file_suffix($file_path,$suffix)
    {
        // use the Unix/Linux `file` command to determine file type
        $stat = strtolower(shell_exec("file ".$file_path));
        $file_type = "";
        if(preg_match("/^[^ ]+: (.*)$/",$stat,$arr)) $file_type = trim($arr[1]);
        if(preg_match("/^\"(.*)/", $file_type, $arr)) $file_type = trim($arr[1]);
        
        $new_suffix = "";
        
        // images
        if(preg_match("/jpeg image data/i", $file_type))                                $new_suffix = "jpg";
        elseif(preg_match("/tiff image data/i", $file_type))                            $new_suffix = "tif";
        elseif(preg_match("/PNG image/i", $file_type))                                  $new_suffix = "png";
        elseif(preg_match("/GIF image/i", $file_type))                                  $new_suffix = "gif";
        
        // videos
        elseif(preg_match("/^macromedia flash/i", $file_type))                          $new_suffix = "flv";
        elseif(preg_match("/apple quicktime/i", $file_type))                            $new_suffix = "mov";
        elseif(preg_match("/^riff \(little-endian\) data, avi/i", $file_type))          $new_suffix = "avi";
        elseif(preg_match("/^iso Media, mpeg v4/i", $file_type))                        $new_suffix = "mp4";
        elseif(preg_match("/^microsoft asf/i", $file_type))                             $new_suffix = "wmv";
        elseif(preg_match("/^mpeg sequence/i", $file_type))                             $new_suffix = "mpg";
        elseif(preg_match("/^flc animation/i", $file_type))                             $new_suffix = "flc";
        elseif($suffix=="wmv" && preg_match("/^microsoft asf/i", $file_type))           $new_suffix = "wmv";
        
        // audio
        elseif(preg_match("/^riff \(little-endian\) data, wave audio/i", $file_type))   $new_suffix = "wav";
        elseif(preg_match("/^iff data, aiff audio/i", $file_type))                      $new_suffix = "aif";
        elseif(preg_match("/mpeg adts, layer iii/i", $file_type))                       $new_suffix = "mp3";
        elseif(preg_match("/^ogg data, vorbis audio/i", $file_type))                    $new_suffix = "ogg";
        elseif(preg_match("/^flac audio/i", $file_type))                                $new_suffix = "flac";
        elseif(preg_match("/^sun\/next audio data/i", $file_type))                      $new_suffix = "au";
        elseif(preg_match("/^mpeg adts, aac/i", $file_type))                            $new_suffix = "aac";
        elseif($suffix=="wma" && preg_match("/^microsoft asf/i", $file_type))           $new_suffix = "wma";
        
        // compressed
        elseif(preg_match("/^gzip compressed data/i", $file_type))                      $new_suffix = "gz";
        elseif(preg_match("/^posix tar archive/i", $file_type))                         $new_suffix = "tar ";
        elseif(preg_match("/^zip archive data/i", $file_type))                          $new_suffix = "zip";
        
        // other - xml, html, pdf
        elseif(preg_match("/^xml( |$)/i", $file_type) || preg_match("/xml$/i", $file_type)) $new_suffix = "xml";
        elseif(preg_match("/^pdf( |$)/i", $file_type))                                  $new_suffix = "pdf";
        elseif(preg_match("/^html( |$)/i", $file_type))                                 $new_suffix = "html";
        elseif($suffix=="xml" && preg_match("/^utf-8 unicode /i", $file_type))          $new_suffix = "xml";
        elseif($suffix=="xml" && preg_match("/^ascii text/i", $file_type))              $new_suffix = "xml";
        elseif($suffix=="xml" && preg_match("/^ASCII English text/i", $file_type))      $new_suffix = "xml";
        
        return $new_suffix;
    }
    
    function determine_file_suffix_pc($file_path,$suffix)
    {
        $new_suffix=$suffix;
        $arr = array('jpg','tif','flv','mov','avi','gz','tar','zip','xml','pdf','html','png','xml','gif', 'mp4', 'wmv', 'mpg', 'mpeg');
        
        if(!in_array($suffix, $arr))
        {
            $new_suffix=false;
            // /*
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
            // */
        }        
        return $new_suffix;
    }
    
    function local_file_size($file)
    {
        $stat = @stat($file);
        return @$stat["size"];
    }
    
    function create_content_thumbnails($file, $prefix, $large_thumbnail_dimensions = CONTENT_IMAGE_LARGE)
    {
        shell_exec("convert $file -strip -background white -flatten -quality 80 ".$prefix."_orig.jpg");
        shell_exec("convert $file -strip -background white -flatten -resize ".$large_thumbnail_dimensions."\">\" ".$prefix."_large.jpg");
        shell_exec("convert $file -strip -background white -flatten -resize ".CONTENT_IMAGE_MEDIUM."\">\" ".$prefix."_medium.jpg");
        shell_exec("convert $file -strip -background white -flatten -resize ".CONTENT_IMAGE_SMALL."\">\" ".$prefix."_small.jpg");
    }
    
    function create_agent_thumbnails($file, $prefix)
    {
        echo shell_exec("convert $file -background white -flatten -resize ".PARTNER_LOGO_LARGE."\">\" ".$prefix."_large.png");
        echo shell_exec("convert $file -background white -flatten -resize ".PARTNER_LOGO_SMALL."\">\" ".$prefix."_small.png");
    }
    
    function new_partner_file_name()
    {
        $file = random_digits(6, 1);
        while(glob(CONTENT_PARTNER_LOCAL_PATH."/$file"."*"))
        {
            $file = random_digits(6, 1);
        }
        
        return CONTENT_PARTNER_LOCAL_PATH.$file;
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
    
    public static function sync_to_content_servers($year, $month, $day, $hour)
    {
        if(@!$GLOBALS['eol_content_servers']) return;
        foreach($GLOBALS['eol_content_servers'] as $content_server_ip)
        {
            $connection = new SSH2Connection($content_server_ip, CONTENT_PARTNER_USER, CONTENT_PARTNER_PASSWORD);
            $connection->sync_content($year, $month, $day, $hour);
            unset($connection);
        }
    }

    public static function sync_partner_logos()
    {
        if(@!$GLOBALS['eol_content_servers']) return;
        foreach($GLOBALS['eol_content_servers'] as $content_server_ip)
        {
            $connection = new SSH2Connection($content_server_ip, CONTENT_PARTNER_USER, CONTENT_PARTNER_PASSWORD);
            $connection->sync_logos();
            unset($connection);
        }
    }
}

?>