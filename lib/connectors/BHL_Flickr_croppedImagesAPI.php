<?php
namespace php_active_record;

class BHL_Flickr_croppedImagesAPI
{
    public function __construct($folder)
    {
        $this->folder = $folder;
        $this->path['temp_dir'] = "/Volumes/Thunderbolt4/EOL_V2/";
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->download_options = array('cache' => 1, 'resource_id' => 'flickr', 'expire_seconds' => 60*60*24*30, 'download_wait_time' => 2000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 1);

        $this->flickr_photo_ids_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/bhl_images_with_box_coordinates.txt";
        $this->api['getInfo'] = "https://api.flickr.com/services/rest/?&method=flickr.photos.getInfo&api_key=".FLICKR_API_KEY."&extras=geo,tags,machine_tags,o_dims,views,media";
        $this->api['getInfo'] .= "&format=json&nojsoncallback=1";
        $this->api['getInfo'] .= "&photo_id=";

        $this->api['getSizes'] = "https://api.flickr.com/services/rest/?&method=flickr.photos.getSizes&api_key=".FLICKR_API_KEY;
        $this->api['getSizes'] .= "&format=json&nojsoncallback=1";
        $this->api['getSizes'] .= "&photo_id=";

        if(Functions::is_production()) $this->cropped_images_path = '/extra/other_files/BHL_cropped_images/';
        else                           $this->cropped_images_path = '/Volumes/AKiTiO4/other_files/BHL_cropped_images/';

        // https://api.flickr.com/services/rest/?&method=flickr.photos.getSizes&api_key=7856957eced5a8ddbad50f1bca0db452&format=rest&nojsoncallback=1&photo_id=5987276725
    }
    public function start()
    {
        if(!is_dir($this->cropped_images_path)) mkdir($this->cropped_images_path);
        $local = Functions::save_remote_file_to_local($this->flickr_photo_ids_file, array("cache" => 1, 'expire_seconds' => 60*60*24*30));
        $i = 0;
        foreach(new FileIterator($local) as $line_number => $line) {
            $i++; $photo_id = trim($line);
            if(!$photo_id) continue;
            echo "\n[$photo_id]\n";
            $j = self::process_photo($photo_id);
            $cropped_imgs = self::create_cropped_images($photo_id, $j);
            // self::create_archive($photo_id, $j, $cropped_imgs);
            if($i >= 3) break; //debug - process just 1 photo
        }
        unlink($local);
    }
    private function process_photo($photo_id)
    {
        $orig_file = self::download_photo($photo_id, "Original");
        $medium_file = self::download_photo($photo_id, "Medium");

        //compute & save new coordinates
        $orig_size = self::get_size_details($photo_id, 'Original');
        $medium_size = self::get_size_details($photo_id, 'Medium');
        $url = $this->api['getInfo'].$photo_id;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $j = json_decode($json);
            foreach($j->photo->notes->note as $note) {
                $note->cmd_orig = self::compute_new_coordinates($note, $orig_size, $medium_size);
                $note->cmd_medium = self::compute_new_coordinates_for_default_medium($note, $medium_size);
                $note->orig_file = $orig_file;
                $note->medium_file = $medium_file;
                // print_r($note);
            }
            return $j;
        }
        return false;
    }
    private function create_cropped_images($photo_id, $j)
    {
        $final = array();
        foreach($j->photo->notes->note as $note) {
            // print_r($note);
            /*
            [cmd_orig] => 485.0701754386x631.008+699.40350877193+1932.462
            [cmd_medium] => 86x112+124+343
            [orig_file] => 5987276725_Original.jpg
            [medium_file] => 5987276725_Medium.jpg
            */
            $path = $this->cropped_images_path;
            $file_extension = pathinfo($note->orig_file, PATHINFO_EXTENSION);
            
            $filename = $note->id."_orig".".$file_extension";
            $note->orig_cropped = $filename;
            $cmd_line = "convert ".$path.$note->orig_file." -crop ".$note->cmd_orig." ".$path.$filename;
            // echo "\n[$cmd_line]\n";
            shell_exec($cmd_line);

            $filename = $note->id."_medium".".$file_extension";
            $note->medium_cropped = $filename;
            $cmd_line = "convert ".$path.$note->medium_file." -crop ".$note->cmd_medium." ".$path.$filename;
            // echo "\n[$cmd_line]\n";
            shell_exec($cmd_line);
            $final[] = $note;
        }
        // print_r($final);
        return $final;
    }
    
    private function download_photo($photo_id, $size)
    {
        $rec = self::get_size_details($photo_id, $size);
        // print_r($rec);
        /* stdClass Object (
            [label] => Original
            [width] => 1929
            [height] => 2817
            [source] => https://farm7.staticflickr.com/6010/5987276725_789341ef2c_o.jpg
            [url] => https://www.flickr.com/photos/biodivlibrary/5987276725/sizes/o/
            [media] => photo)
        */
        $options = $this->download_options;
        // $options['file_extension'] = pathinfo($rec->source, PATHINFO_EXTENSION);
        $options['expire_seconds'] = false; //doesn't need to expire at all
        $local = Functions::save_remote_file_to_local($rec->source, $options);
        $destination = $this->cropped_images_path.$photo_id."_".$size.".".pathinfo($rec->source, PATHINFO_EXTENSION);
        Functions::file_rename($local, $destination);
        // echo "\n[$local]\n[$destination]";
        // print_r(pathinfo($destination));
        // exit;
        return pathinfo($destination, PATHINFO_BASENAME);
    }
    private function compute_new_coordinates_for_default_medium($note, $medium) // w x h + x + y
    {
        $cmd = $note->w."x".$note->h."+".$note->x."+".$note->y;
        return $cmd;
    }
    private function compute_new_coordinates($note, $orig, $medium)
    {   /* stdClass Object ( --> orig_size
            [label] => Original
            [width] => 1929
            [height] => 2817
            [source] => https://farm7.staticflickr.com/6010/5987276725_789341ef2c_o.jpg
            [url] => https://www.flickr.com/photos/biodivlibrary/5987276725/sizes/o/
            [media] => photo)
        stdClass Object ( --> note
            [id] => 72157680051511041
            [author] => 126912357@N06
            [authorname] => siobhan leachman
            [authorrealname] => Siobhan Leachman
            [authorispro] => 0
            [x] => 16
            [y] => 334
            [w] => 86
            [h] => 122
            [_content] => taxonomy:binomial=&quot;Rhynchites similis&quot;)
        */
        // $medium->width = 300;
        $new_x = ($orig->width*$note->x)/$medium->width;
        $new_w = ($orig->width*$note->w)/$medium->width;
        $new_y = ($orig->height*$note->y)/$medium->height;
        $new_h = ($orig->height*$note->h)/$medium->height;
        $cmd = $new_w."x".$new_h."+".$new_x."+".$new_y; // w x h + x + y
        return $cmd;
    }
    private function get_size_details($photo_id, $size) //$size e.g. "Original" or "Medium"
    {
        $url = $this->api['getSizes'].$photo_id;
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $j = json_decode($json);
            foreach($j->sizes->size as $rec) {
                if($rec->label == $size) return $rec;
            }
        }
        exit("\nInvestigate photo_id [$photo_id] no orig size details\n");
        return false;
    }
}
?>
