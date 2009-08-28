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
    // content - this is for images, video, sound... main content items for the species pages
    // partner - this type means we are downloading a logo for a content partner
    // resource - this means we are downloading an XML or zipped file of the EOL schema for processing
    
    function grab_file($file, $resource_id, $type, $large_thumbnail_dimensions = CONTENT_IMAGE_LARGE)
    {
        $new_file_path = "";
        
        $suffix = "";
        if(preg_match("/\.([^\.]+)$/",$file,$arr)) $suffix = trim($arr[1]);
        
        $temp_file_path = CONTENT_TEMP_PREFIX.$this->unique_key.".file";
        if(preg_match("/^http:\/\//",$file))
        {
            if($file_contents = Functions::get_remote_file($file))
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
            $new_suffix = $this->determine_file_suffix($temp_file_path,$suffix);
            if($new_suffix)
            {
                if($type=="content") $new_file_prefix = $this->new_content_file_name();
                elseif($type=="partner") $new_file_prefix = $this->new_partner_file_name();
                elseif($type=="resource") $new_file_prefix = $this->new_resource_file_name($resource_id);
                
                $new_file_path = $new_file_prefix.".".$new_suffix;
                
                // copy temporary file into its new home
                shell_exec("cp ".$temp_file_path." ".$new_file_path);
                
                // fail if for some reason there is still no file at the new path
                if(!file_exists($new_file_path)) return false;
                
                if(preg_match("/^(.*)\.gz$/", $new_file_path, $arr))
                {
                    shell_exec("mv ".$new_file_path." ".$arr[1].".xml.gz");
                    shell_exec("gunzip -f ".$arr[1].".xml.gz");
                    $new_file_path = $arr[1].".xml";
                }
                
                // create thumbnails of website content and agent logos
                if($type=="content") $this->create_content_thumbnails($new_file_path, $new_file_prefix, $large_thumbnail_dimensions);
                elseif($type=="partner") $this->create_agent_thumbnails($new_file_path, $new_file_prefix);
                
                // Take the substring of the new file path to return via the webservice
                if($type=="content" && preg_match("/^".preg_quote(CONTENT_LOCAL_PATH, "/")."(.*)\.[^\.]+$/",$new_file_path,$arr)) $new_file_path = str_replace("/", "", $arr[1]);
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
        
        if(preg_match("/^([^ ]+) image data/",$file_type,$arr))
        {
            $new_suffix = $arr[1];
            if($new_suffix == "jpeg") $new_suffix = "jpg";
            if($new_suffix == "tiff") $new_suffix = "tif";
        }elseif(preg_match("/^macromedia flash/i",$file_type,$arr)) $new_suffix = "flv";
        elseif(preg_match("/^apple quickTime/i",$file_type,$arr)) $new_suffix = "mov";
        elseif(preg_match("/^riff \(little-endian\) data, avi/i",$file_type,$arr)) $new_suffix = "avi";
        elseif(preg_match("/^gzip compressed data/i",$file_type,$arr)) $new_suffix = "gz";
        elseif(preg_match("/^posix tar archive/i",$file_type,$arr)) $new_suffix = "tar ";
        elseif(preg_match("/^zip archive data/i",$file_type,$arr)) $new_suffix = "zip";
        elseif(preg_match("/^xml( |$)/i",$file_type,$arr)) $new_suffix = "xml";
        elseif(preg_match("/^pdf( |$)/i",$file_type,$arr)) $new_suffix = "pdf";
        elseif(preg_match("/^html( |$)/i",$file_type,$arr)) $new_suffix = "html";
        elseif($suffix=="xml" && preg_match("/^utf-8 unicode /i",$file_type,$arr)) $new_suffix = "xml";
        elseif($suffix=="xml" && preg_match("/^ascii text/i",$file_type,$arr)) $new_suffix = "xml";
        
        return $new_suffix;
    }
    
    function local_file_size($file)
    {
        $stat = @stat($file);
        return @$stat["size"];
    }
    
    function create_content_thumbnails($file, $prefix, $large_thumbnail_dimensions = CONTENT_IMAGE_LARGE)
    {
        shell_exec("convert $file -strip -quality 80 ".$prefix."_orig.jpg");
        shell_exec("convert $file -strip -resize ".$large_thumbnail_dimensions."\">\" ".$prefix."_large.jpg");
        shell_exec("convert $file -strip -resize ".CONTENT_IMAGE_MEDIUM."\">\" ".$prefix."_medium.jpg");
        shell_exec("convert $file -strip -resize ".CONTENT_IMAGE_SMALL."\">\" ".$prefix."_small.jpg");
    }
    
    function create_agent_thumbnails($file, $prefix)
    {
        echo shell_exec("convert $file -resize ".PARTNER_LOGO_LARGE."\">\" ".$prefix."_large.png");
        echo shell_exec("convert $file -resize ".PARTNER_LOGO_SMALL."\">\" ".$prefix."_small.png");
    }
    
    function random_digits($number, $start)
    {
        $start = "1".str_repeat($start, $number);
        $end = "1".str_repeat(9, $number);
        $random = rand($start,$end);
        return substr($random, 1);
    }
    
    function new_partner_file_name()
    {
        $file = $this->random_digits(4, 1);
        while(glob(CONTENT_PARTNER_LOCAL_PATH."/$file"."*"))
        {
            $file = $this->random_digits(4, 1);
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
        $file = $this->random_digits(5, 0);    
        while(glob(CONTENT_LOCAL_PATH."$year/$month/$day/$hour/$file"."*"))
        {
            $file = $this->random_digits(5, 0);
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