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
        $this->download_options = array('resource_id' => 'flickr', 'expire_seconds' => 60*60*24*30, 'download_wait_time' => 2000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 1);

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
        foreach(new FileIterator($local) as $line_number => $line) {
            $photo_id = trim($line);
            if(!$photo_id) continue;
            echo "\n[$photo_id]\n";
            self::process_photo($photo_id);
            exit;
        }
        unlink($local);
    }
    private function process_photo($photo_id)
    {
        self::download_photo($photo_id);
        $orig_size = self::get_size_details($photo_id, 'Original');
        $medium_size = self::get_size_details($photo_id, 'Medium');
        print_r($medium_size); print_r($orig_size);

        $url = $this->api['getInfo'].$photo_id;
        // echo "\n[$url]\n";
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $j = json_decode($json);
            foreach($j->photo->notes->note as $note) {
                $note->cmd = self::compute_new_coordinates($note, $orig_size, $medium_size);
                $note->cmd2 = self::compute_new_coordinates_for_default_medium($note, $medium_size);
                print_r($note);
            }
        }
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
