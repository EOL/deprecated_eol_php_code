<?php
namespace php_active_record;

$GLOBALS['flickr_licenses'] = array();
//$GLOBALS['flickr_licenses'][0] = "All Rights Reserved";
$GLOBALS['flickr_licenses'][1] = "http://creativecommons.org/licenses/by-nc-sa/2.0/";
$GLOBALS['flickr_licenses'][2] = "http://creativecommons.org/licenses/by-nc/2.0/";
//$GLOBALS['flickr_licenses'][3] = "http://creativecommons.org/licenses/by-nc-nd/2.0/";
$GLOBALS['flickr_licenses'][4] = "http://creativecommons.org/licenses/by/2.0/";
$GLOBALS['flickr_licenses'][5] = "http://creativecommons.org/licenses/by-sa/2.0/";
//$GLOBALS['flickr_licenses'][6] = "http://creativecommons.org/licenses/by-nd/2.0/";
$GLOBALS['flickr_licenses'][7] = "http://www.flickr.com/commons/usage/";

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
        $this->download_options['expire_seconds'] = false;
        
        $this->flickr_photo_ids_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/bhl_images_with_box_coordinates.txt";
        $this->api['getInfo'] = "https://api.flickr.com/services/rest/?&method=flickr.photos.getInfo&api_key=".FLICKR_API_KEY."&extras=geo,tags,machine_tags,o_dims,views,media";
        $this->api['getInfo'] .= "&format=json&nojsoncallback=1";
        $this->api['getInfo'] .= "&photo_id=";

        $this->api['getSizes'] = "https://api.flickr.com/services/rest/?&method=flickr.photos.getSizes&api_key=".FLICKR_API_KEY;
        $this->api['getSizes'] .= "&format=json&nojsoncallback=1";
        $this->api['getSizes'] .= "&photo_id=";

        if(Functions::is_production()) $this->cropped_images_path = '/extra/other_files/BHL_cropped_images/';
        else                           $this->cropped_images_path = '/Volumes/AKiTiO4/other_files/BHL_cropped_images/';

        $this->media_path = "https://editors.eol.org/other_files/BHL_cropped_images/";
        
        // https://api.flickr.com/services/rest/?&method=flickr.photos.getSizes&api_key=7856957eced5a8ddbad50f1bca0db452&format=rest&nojsoncallback=1&photo_id=5987276725
        $this->debug = array();
    }
    public function start()
    {
        if(!is_dir($this->cropped_images_path)) mkdir($this->cropped_images_path);
        $local = Functions::save_remote_file_to_local($this->flickr_photo_ids_file, array("cache" => 1, 'expire_seconds' => 60*60*24*30));
        $i = 0;
        foreach(new FileIterator($local) as $line_number => $line) {
            $i++; $photo_id = trim($line);
            if(!$photo_id) continue;
            
            // if($photo_id != "6001792845") continue; //debug only
            // if($photo_id != "6001785977") continue; // debug only - multiple binomial
            // if($photo_id != "6105705787") continue; //debug only
            // if($photo_id != "6266864276") continue; //debug only
            // if($photo_id != "8220470473") continue; //debug only
            // if($photo_id != "32645072266") continue; //debug only
            
            echo "\n[$photo_id]\n";
            $j = self::process_photo($photo_id);
            $cropped_imgs = self::create_cropped_images($photo_id, $j);
            self::create_archive($photo_id, $j, $cropped_imgs);
            // if($i >= 10) break; //debug - process limited no. of photos during development
        }
        unlink($local);
        $this->archive_builder->finalize(true);
        if($this->debug)
        {
            print_r($this->debug);
            echo "\n".count($this->debug['no binomial nor ancestry'])."\n";
        }
        else echo "\n-No debug errors-\n";
    }
    private function process_photo($photo_id)
    {
        
        $rec = self::get_size_details($photo_id, "Original");
        /* stdClass Object (
            [label] => Original
            [width] => 1929
            [height] => 2817
            [source] => https://farm7.staticflickr.com/6010/5987276725_789341ef2c_o.jpg
            [url] => https://www.flickr.com/photos/biodivlibrary/5987276725/sizes/o/
            [media] => photo)
        */
        $orig_file = self::download_photo($photo_id, "Original", $rec);
        $origsize_file_page = $rec->url;

        $rec = self::get_size_details($photo_id, "Medium");
        /*
        $medium_file = self::download_photo($photo_id, "Medium", $rec); //works OK but just commented as we don't crop the Medium size image
        */
        $medium_file = "";
        $mediumsize_file_page = $rec->url;
        
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
                
                $note->origsize_file_page = $origsize_file_page;
                $note->mediumsize_file_page = $mediumsize_file_page;
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
            $file_extension = self::get_extension($j, $note);
            
            $filename = $note->id."_orig".".$file_extension";
            $note->orig_cropped = $filename;
            $cmd_line = "convert ".$path.$note->orig_file." -crop ".$note->cmd_orig." ".$path.$filename;
            if(!file_exists($path.$filename)) shell_exec($cmd_line); //don't re-create image

            /*
            $filename = $note->id."_medium".".$file_extension";
            $note->medium_cropped = $filename;
            $cmd_line = "convert ".$path.$note->medium_file." -crop ".$note->cmd_medium." ".$path.$filename;
            if(!file_exists($path.$filename)) shell_exec($cmd_line); //don't re-create image
            */
            
            $final[] = $note;
        }
        // print_r($final);
        return $final;
    }
    private function get_extension($j, $note)
    {
        if($val = $j->photo->originalformat) return $val;
        elseif($val = pathinfo($note->orig_file, PATHINFO_EXTENSION)) return $val;
    }
    private function download_photo($photo_id, $size, $rec)
    {
        // $rec = self::get_size_details($photo_id, $size);
        /* stdClass Object (
            [label] => Original
            [width] => 1929
            [height] => 2817
            [source] => https://farm7.staticflickr.com/6010/5987276725_789341ef2c_o.jpg
            [url] => https://www.flickr.com/photos/biodivlibrary/5987276725/sizes/o/
            [media] => photo)
        */
        $options = $this->download_options;
        $options['expire_seconds'] = false; //doesn't need to expire at all
        $local = Functions::save_remote_file_to_local($rec->source, $options);
        $destination = $this->cropped_images_path.$photo_id."_".$size.".".pathinfo($rec->source, PATHINFO_EXTENSION);
        Functions::file_rename($local, $destination);
        // echo "\n[$local]\n[$destination]";
        // print_r(pathinfo($destination));
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
    private function create_archive($photo_id, $j, $cropped_imgs)
    {
        foreach($j->photo->notes->note as $note) {
            /* stdClass Object (
                [id] => 72157680051511041
                [author] => 126912357@N06
                [authorname] => siobhan leachman
                [authorrealname] => Siobhan Leachman
                [authorispro] => 0
                [x] => 16
                [y] => 334
                [w] => 86
                [h] => 122
                [_content] => taxonomy:binomial=&quot;Rhynchites similis&quot;
                [cmd_orig] => 485.0701754386x687.348+90.245614035088+1881.756
                [cmd_medium] => 86x122+16+334
                [orig_file] => 5987276725_Original.jpg
                [medium_file] => 5987276725_Medium.jpg
                [orig_cropped] => 72157680051511041_orig.jpg
                [medium_cropped] => 72157680051511041_medium.jpg)*/

            $info = self::get_sciname($note, $j->photo->id);
            if(!@$info['binomials'])
            {
                $info = self::adjust_names($info);
            }
            print_r($info);
            
            /*Array (
                [common] => Array (
                        [0] => Black Meadowhawk
                        [1] => Black Darter
                    )
                [phylum] => Arthropoda
                [class] => Insecta
                [order] => Odonata
                [family] => Libellulidae
                [binomials] => Array (
                        [0] => Sympetrum scoticum
                        [1] => Sympetrum danae
                    )
            )*/
            $taxon_ids = array();
            if(!@$info['binomials']) continue;
            foreach($info['binomials'] as $sciname) {
                $rec = array();
                $rec['sciname'] = $sciname;
                if(!$rec['sciname']) continue;
                $rec['taxon_id'] = str_replace(" ", "_", $rec['sciname']);
                $taxon_ids[$rec['taxon_id']] = ''; //to be use when writing objects

                $rec['kingdom'] = @$info['kingdom'];
                $rec['phylum'] = @$info['phylum'];
                $rec['class'] = @$info['class'];
                $rec['order'] = @$info['order'];
                $rec['family'] = @$info['family'];
                $rec['genus'] = @$info['genus'];
                $rec['rank'] = @$info['rank'];
                
                $rec['source'] = self::get_photo_page($j);
                self::write_archive($rec, 'taxon');
            }
            $taxon_ids = array_keys($taxon_ids);

            if(count($info['binomials']) == 1) //only when there is only 1 binomial where we process vernaculars
            {
                if($val = @$info['common']) self::write_archive($val, 'vernacular', $taxon_ids); 
            }
            
            $rec['agents'][] = self::get_agent($note);
            $rec['agents'][] = self::bhl_as_agent();
            $rec['objects'][] = self::get_objects($note, $j);
            self::write_archive($rec, 'object', $taxon_ids);
        }
    }
    private function adjust_names($info)
    {
        $ranks = array("genus", "family", "order", "class", "phylum", "kingdom", "superfamily","suborder");
        /* algorithm:
        if($sciname = $info['genus']) {
            $info['genus'] = '';
            $info['binomials'][] = $sciname;
            return $info;
        }
        */
        foreach($ranks as $rank) {
            if($sciname = @$info[$rank]) {
                $info[$rank] = '';
                $info['binomials'][] = $sciname;
                $info['rank'] = $rank;
                return $info;
            }
        }
        return $info;
    }
    private function get_sciname($note, $photo_id) // e.g. "taxonomy:binomial=&quot;Rhynchites similis&quot;"
    {
        echo "\n$note->_content\n";
        $final = array();
        
        if(preg_match("/taxonomy:kingdom=&quot;(.*?)&quot;/ims", $note->_content, $arr)) $final['kingdom'] = $arr[1];
        if(preg_match("/taxonomy:phylum=&quot;(.*?)&quot;/ims", $note->_content, $arr)) $final['phylum'] = $arr[1];
        if(preg_match("/taxonomy:class=&quot;(.*?)&quot;/ims", $note->_content, $arr)) $final['class'] = $arr[1];
        if(preg_match("/taxonomy:order=&quot;(.*?)&quot;/ims", $note->_content, $arr)) $final['order'] = $arr[1];
        if(preg_match("/taxonomy:family=&quot;(.*?)&quot;/ims", $note->_content, $arr)) $final['family'] = $arr[1];
        if(preg_match("/taxonomy:genus=&quot;(.*?)&quot;/ims", $note->_content, $arr)) $final['genus'] = $arr[1];
        if(preg_match_all("/taxonomy:common=&quot;(.*?)&quot;/ims", $note->_content, $arr)) $final['common'] = $arr[1];
        

        //seems separated by \n or 'next line'
        if(!@$final['kingdom']) if(preg_match("/taxonomy:kingdom=(.*?)\\\n/ims", $note->_content, $arr)) $final['kingdom'] = $arr[1];
        if(!@$final['phylum']) if(preg_match("/taxonomy:phylum=(.*?)\\\n/ims", $note->_content, $arr)) $final['phylum'] = $arr[1];
        if(!@$final['class']) if(preg_match("/taxonomy:class=(.*?)\\\n/ims", $note->_content, $arr)) $final['class'] = $arr[1];
        if(!@$final['order']) if(preg_match("/taxonomy:order=(.*?)\\\n/ims", $note->_content, $arr)) $final['order'] = $arr[1];
        if(!@$final['family']) if(preg_match("/taxonomy:family=(.*?)\\\n/ims", $note->_content, $arr)) $final['family'] = $arr[1];
        if(!@$final['genus']) if(preg_match("/taxonomy:genus=(.*?)\\\n/ims", $note->_content, $arr)) $final['genus'] = $arr[1];
        if(!@$final['common']) if(preg_match_all("/taxonomy:common=(.*?)\\\n/ims", $note->_content, $arr)) $final['common'] = $arr[1];

        if(!@$final['order']) if(preg_match("/taxonomy: order=(.*?)\\\n/ims", $note->_content, $arr)) $final['order'] = $arr[1];
        if(!@$final['superfamily']) if(preg_match("/taxonomy:superfamily=(.*?)\\\n/ims", $note->_content, $arr)) $final['superfamily'] = $arr[1];
        
        

        // where ending string is end of XML tag, nothing. So replaced it with string 'elix'
        if(!@$final['kingdom']) if(preg_match("/taxonomy:kingdom=(.*?)elix/ims", $note->_content.'elix', $arr)) $final['kingdom'] = $arr[1];
        if(!@$final['phylum']) if(preg_match("/taxonomy:phylum=(.*?)elix/ims", $note->_content.'elix', $arr)) $final['phylum'] = $arr[1];
        if(!@$final['class']) if(preg_match("/taxonomy:class=(.*?)elix/ims", $note->_content.'elix', $arr)) $final['class'] = $arr[1];
        if(!@$final['order']) if(preg_match("/taxonomy:order=(.*?)elix/ims", $note->_content.'elix', $arr)) $final['order'] = $arr[1];
        if(!@$final['family']) if(preg_match("/taxonomy:family=(.*?)elix/ims", $note->_content.'elix', $arr)) $final['family'] = $arr[1];
        if(!@$final['genus']) if(preg_match("/taxonomy:genus=(.*?)elix/ims", $note->_content.'elix', $arr)) $final['genus'] = $arr[1];
        if(!@$final['common']) if(preg_match_all("/taxonomy:common=(.*?)elix/ims", $note->_content.'elix', $arr)) $final['common'] = $arr[1];

        if(!@$final['order']) if(preg_match("/taxonomy: order=(.*?)elix/ims", $note->_content.'elix', $arr)) $final['order'] = $arr[1];
        if(!@$final['suborder']) if(preg_match("/taxonomy:suborder=(.*?)elix/ims", $note->_content.'elix', $arr)) $final['suborder'] = $arr[1];

        // binomail
        // $new_str = trim($note->_content)."\n";
        if(preg_match_all("/taxonomy:binomial=&quot;(.*?)&quot;/ims", $note->_content, $arr) ||
           preg_match_all("/taxonomy:binomail=&quot;(.*?)&quot;/ims", $note->_content, $arr) ||
           preg_match_all("/taxonomy:binomial=(.*?)\\\n/ims", $note->_content, $arr) ||
           preg_match_all("/taxonomy:binomial=(.*?)elix/ims", $note->_content.'elix', $arr)
           // preg_match_all("/taxonomy:binomial=(.*?)\\\n/ims", $new_str, $arr)
        ) {
            $final['binomials'] = $arr[1];
            $final['rank'] = 'species';
            return $final;
        }
        elseif(preg_match_all("/taxonomy:binomial=(.*?)&quot;/ims", $note->_content, $arr)) { // [_content] => &quot;taxonomy:binomial=Melittia snowii&quot;
            $final['binomials'] = $arr[1];
            $final['rank'] = 'species';
            return $final;
        }
        if(preg_match_all("/taxonomy:trinomial=&quot;(.*?)&quot;/ims", $note->_content, $arr)) {
            $final['binomials'] = $arr[1];
            return $final;
        }
        elseif(preg_match_all("/taxonomy:current=&quot;(.*?)&quot;/ims", $note->_content, $arr)) {
            $final['binomials'] = $arr[1];
            $final['rank'] = '';
            return $final;
        }
        elseif(@$final['kingdom']||@$final['phylum']||@$final['class']||@$final['order']||@$final['family']||@$final['genus']||@$final['superfamily']||@$final['suborder'])
        {
            return $final;
        }
        else echo "\nnothing found: no binomial nor any ancestry\n";

        $this->debug['no binomial nor ancestry'][$photo_id][$note->id] = '';
        print_r($note);
        echo("\nInvestigate no binomial nor ancestry [$photo_id][$note->id]\n");
        return false;
    }
    /*
    private function extra_string_process_for_ancestry($str, $final)
    {
        $arr = explode("\n", $str);
        print_r($arr);
        return $final;
    }
    */
    private function get_photo_page($j)
    {
        foreach($j->photo->urls->url as $url) {
            if($url->type) return $url->_content;
        }
    }
    private function get_objects($note, $j)
    {
        $obj['identifier'] = pathinfo($note->orig_cropped, PATHINFO_FILENAME);
        $obj['media_url'] = $this->media_path.$note->orig_cropped;
        $obj['source'] = $note->origsize_file_page;
        $obj['license'] = $GLOBALS['flickr_licenses'][$j->photo->license];
        if(!$obj['license']) exit("\nInvestigate license [$j->photo->id]\n");
        return $obj;
    }
    private function get_agent($note)
    {
        $agent['name'] = $note->authorrealname != "" ? $note->authorrealname: $note->authorname;
        $agent['role'] = 'creator';
        $agent['homepage'] = "https://www.flickr.com/photos/".$note->author."/";
        return $agent;
    }
    private function bhl_as_agent()
    {
        $agent['name'] = "Biodiversity Heritage Library";
        $agent['role'] = 'project';
        $agent['homepage'] = "https://www.flickr.com/photos/biodivlibrary/";
        return $agent;
    }
    private function write_archive($rec, $what, $taxon_ids = false)
    {
        if($what == 'taxon') {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $rec['taxon_id'];
            $taxon->scientificName  = $rec['sciname'];
            
            $taxon->kingdom = @$rec['kingdom'];
            $taxon->phylum = @$rec['phylum'];
            $taxon->class = @$rec['class'];
            $taxon->order = @$rec['order'];
            $taxon->family = @$rec['family'];
            $taxon->genus = @$rec['genus'];
            $taxon->taxonRank = @$rec['rank'];
            
            $taxon->furtherInformationURL = $rec['source'];
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
        }
        
        if($what == 'vernacular') {
            foreach($rec as $comname) {
                $v = new \eol_schema\VernacularName();
                $v->taxonID         = implode("; ", $taxon_ids); //this is just one id
                $v->vernacularName  = trim($comname);
                $v->language        = 'en';
                $this->archive_builder->write_object_to_file($v);
            }
        }

        if($what == 'object') {
            //start objects
            foreach($rec['objects'] as $o) {
                $mr = new \eol_schema\MediaResource();
                $mr->taxonID        = implode("; ", $taxon_ids); //$rec['taxon_id'];
                $mr->identifier     = $o['identifier'];
                $mr->format         = Functions::get_mimetype($o['media_url']);
                $mr->type           = Functions::get_datatype_given_mimetype($mr->format);
                $mr->furtherInformationURL = $rec['source'];
                $mr->accessURI      = $o['media_url'];
                $mr->UsageTerms     = $o['license'];
                if($agent_ids = self::create_agents($rec['agents'])) $mr->agentID = implode("; ", $agent_ids);

                /* not included
                $mr->language       = '';
                $mr->thumbnailURL   = '';
                $mr->CVterm         = '';
                $mr->Owner          = '';
                $mr->rights         = '';
                $mr->title          = '';
                $mr->description    = '';
                $mr->LocationCreated = '';
                $mr->bibliographicCitation = '';
                $mr->audience       = 'Everyone';
                if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
                */

                if(!isset($this->object_ids[$mr->identifier])) {
                    $this->archive_builder->write_object_to_file($mr);
                    $this->object_ids[$mr->identifier] = '';
                }
            }
        }

    }
    private function create_agents($agents)
    {
        $agent_ids = array();
        foreach($agents as $a) {
            $r = new \eol_schema\Agent();
            $r->term_name       = $a['name'];
            $r->agentRole       = $a['role'];
            $r->identifier      = md5("$r->term_name|$r->agentRole");
            $r->term_homepage   = $a['homepage'];
            $agent_ids[] = $r->identifier;
            if(!isset($this->agent_ids[$r->identifier]))
            {
               $this->agent_ids[$r->identifier] = $r->term_name;
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    
}
?>
