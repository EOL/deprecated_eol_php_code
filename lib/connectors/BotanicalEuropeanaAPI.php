<?php
namespace php_active_record;
/* connector: []
Botanic Garden and Botanical Museum Berlin-Dahlem, Europeana collection
Process the archive file, download the images locally first.
*/
/* there is also rake_tasks/bulk_image_resize.php -> probably a utility

*/
class BotanicalEuropeanaAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->object_ids = array();
        
        $this->zip_path = "http://opendata.eol.org/dataset/85d351ea-53bf-4cd9-8f6b-7ee4bfc07152/resource/02b89b97-21c1-46ac-99b7-8f16ab0fbb11/download/europeanaberlin.zip";
        $this->zip_path = "http://localhost/~eolit/cp/BotanicalEuropeana/europeana_berlin.zip";

        $this->local_destination = "/Volumes/Eli blue/BotanicalEuropeana";
        $this->text_path = array();
        
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->names_no_entry_from_partner_dump_file = $this->TEMP_DIR . "names_no_entry_from_partner.txt";
        
        $this->media_fields = array("identifier", "taxonID", "type", "format", "accessURI", "UsageTerms", "publisher", "contributor", "creator", "furtherInformationURL", "description", "rights/Owner", "Rating");
    }

    private function get_names_no_entry_from_partner()
    {
        $names = array();
        $dump_file = DOC_ROOT . "/public/tmp/europeana/names_no_entry_from_partner.txt";
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line) $names[$line] = "";
        }
        return array_keys($names);
    }

    function resize_downloaded_images() // utility Aug 2014
    {
        // $f = "/Volumes/Eli blue/BotanicalEuropeana/BotanicalEuropeana_6/B_-W_19326%20-01%200.jpg";
        // print_r(pathinfo($f)); exit;

        $content_manager = new ContentManager();
        if(!$this->load_zip_contents()) return;
        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();
        $records = $func->make_array($this->text_path["media"], $this->media_fields);
        $total = count($records);
        $i = 0;
        foreach($records as $rec)
        {
            $i++;
            echo "\n" . number_format($i) . " of $total";
            
            /* breakdown when caching
            $cont = false;
            // if($i >= 1      && $i < 20000)  $cont = true;
            // if($i >= 20000 && $i < 20000*2)  $cont = true; done
            // if($i >= 20000*2 && $i < 20000*3)  $cont = true;
            // if($i >= 20000*3 && $i < 20000*4)  $cont = true;
            // if($i >= 20000*4 && $i < 20000*5)  $cont = true; done
            
            // if($i >= 15000      && $i < 20000)  $cont = true; done
            // if($i >= 20000+15000 && $i < 20000*2)  $cont = true; done
            // if($i >= (20000*2)+15000 && $i < 20000*3)  $cont = true;
            if($i >= 17900 && $i < 20000)  $cont = true;
            
            if(!$cont) continue;
            */
            
            $parts = pathinfo($rec['accessURI']);
            if($image_cache_path = self::get_image_cache_path($parts['basename']))
            {
                $parts = pathinfo($image_cache_path);
                /*
                [dirname] => /Volumes/Eli blue/BotanicalEuropeana/BotanicalEuropeana_6
                [basename] => B_-W_19326%20-01%200.jpg
                [extension] => jpg
                [filename] => B_-W_19326%20-01%200
                */
                $temp = explode("Europeana_", $parts['dirname']);
                $destin_folder = "/Volumes/Eli blue/BotanicalEuropeana_small/" . $temp[1] . "/";
                if(!file_exists($destin_folder)) mkdir($destin_folder);
                $dimension = array(1300, 1080); // Jen's choice 1300x1080
                if(!file_exists($destin_folder . $parts['filename'] . "_" . implode("_", $dimension) . "." . $parts['extension']))
                {
                    echo "\nre-sizing... [$image_cache_path]";
                    $path = str_replace('\/', '\/', $image_cache_path);
                    $path = str_replace(' ', '\ ', $path);
                    $destination_path = str_replace('\/', '\/', $destin_folder);
                    $destination_path = str_replace(' ', '\ ', $destination_path);
                    $prefix = $parts['filename'];
                    $content_manager->create_smaller_version($path, $dimension, $destination_path.$prefix, implode("_", $dimension));
                }
                else echo " -done- ";
            }
            else
            {
                // /*
                echo "\n not in local, will try to download...";
                $folder = $this->local_destination . "/BotanicalEuropeana_20" . "/";
                $destination = $folder . $parts["basename"];
                if(!file_exists($folder)) mkdir($folder);
                self::save_big_file_to_local($rec["accessURI"], $destination);
                // */
            }
        }
        // remove temp dir
        $parts = pathinfo($this->text_path["taxa"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }

    private function get_image_cache_path($basename)
    {
        for($i = 0; $i <= 15; $i++)
        {
            $file = $this->local_destination . "/BotanicalEuropeana_$i/$basename";
            if(file_exists($file)) return $file;
        }
        return false;
    }

    function match_big_with_small_images() // create small ver for every big image
    {
        $content_manager = new ContentManager();
        $dimension = array(1300, 1080); // Jen's choice 1300x1080
        for($i = 0; $i <= 15; $i++)
        {
            $source_dir = $this->local_destination . "/BotanicalEuropeana_$i";
            $target_dir = "/Volumes/Eli blue/BotanicalEuropeana_small/$i/";
            echo "\n $source_dir";
            $k = 0;
            foreach (glob("$source_dir/*.jpg") as $filename)
            {
                /* breakdown when caching
                $k++;
                $cont = false;
                if($k >= 0 && $k < 4000)  $cont = true;
                // if($k >= 4000 && $k < 4500)  $cont = true;
                // if($k >= 4500 && $k < 8000)  $cont = true;
                if(!$cont) continue;
                */
                
                $parts = pathinfo($filename);
                if(!file_exists($target_dir . $parts['filename'] . "_" . implode("_", $dimension) . "." . $parts['extension']))
                {
                    echo "\n[$i-$k] re-sizing... [$filename]";
                    $path = str_replace('\/', '\/', $filename);
                    $path = str_replace(' ', '\ ', $path);
                    $destination_path = str_replace('\/', '\/', $target_dir);
                    $destination_path = str_replace(' ', '\ ', $destination_path);
                    $prefix = $parts['filename'];
                    $content_manager->create_smaller_version($path, $dimension, $destination_path.$prefix, implode("_", $dimension));
                }
                else echo "\n-done- ";
            }
        }
    }

    function delete_broken_files() // utility
    {
        $files = DOC_ROOT . "/temp/broken/*.*";
        echo "\n[$files]\n";
        foreach(glob($files) as $filename)
        {
            $parts = pathinfo($filename);
            $basename = str_replace("_1300_1080", "", $parts['basename']); // big
            // $basename = $parts['basename']; // small
            echo "\n $basename";
            //start deleting
            for($i = 0; $i <= 15; $i++)
            {
                $file = $this->local_destination . "/BotanicalEuropeana_$i/$basename"; // big
                // $file = $this->local_destination . "_small/$i/$basename"; // small
                if(file_exists($file))
                {
                    echo "\n file found...will delete...";
                    unlink($file);
                }
            }
        }
    }

    function get_all_taxa()
    {
        if(!$this->load_zip_contents()) return;
        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();

        $this->create_instances_from_taxon_object($func);
        $this->create_media($func);
        $this->create_archive();

        $parts = pathinfo($this->text_path["taxa"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }

    private function create_media($func)
    {
        $fields = $this->media_fields;
        $records = $func->make_array($this->text_path["media"], $fields);
        
        $total = count($records);
        $i = 0;
        foreach($records as $rec)
        {
            $i++;
            echo "\n $i of $total";
            $mr = new \eol_schema\MediaResource();
            $mr->identifier             = $rec["identifier"];
            $mr->taxonID                = $rec["taxonID"];
            $mr->type                   = $rec["type"];
            $mr->format                 = $rec["format"];
            $mr->accessURI              = $rec["accessURI"];
            $mr->UsageTerms             = $rec["UsageTerms"];
            $mr->publisher              = $rec["publisher"];
            $mr->contributor            = $rec["contributor"];
            $mr->creator                = $rec["creator"];
            $mr->furtherInformationURL  = $rec["furtherInformationURL"];
            $mr->description            = $rec["description"];
            $mr->Owner                  = $rec["rights/Owner"];
            $mr->Rating                 = $rec["Rating"];
            // if(!in_array($mr->identifier, $this->object_ids))
            // {
            //    $this->object_ids[] = $mr->identifier;
               $this->archive_builder->write_object_to_file($mr);
            // }
        }
    }

    function move_files() //utility
    {
        /*
        $i = 1;
        while(true)
        {
            $folder = $this->local_destination_old . "_" . $i;
            if(file_exists($folder))
            {
                recursive_rmdir($folder);
                debug("\n temporary directory removed: " . $folder);
                $i++;
            }
            else exit;
        }
        exit;
        */
        
        if(!self::load_zip_contents()) return FALSE;
    
        $folder = $this->local_destination_old . "_1";
        if(!file_exists($folder)) mkdir($folder);
            
        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();
        $fields = $this->media_fields;
        $records = $func->make_array($this->text_path["media"], $fields);
        $total = count($records);
        $i = 0;
        foreach($records as $rec)
        {
            $i++;
            echo "\n $i of $total \n";
            $parts = pathinfo($rec["accessURI"]);
            $destination = $this->local_destination_old . "/" . $parts["basename"];
            echo "\n destination: [$destination] ";
            if(file_exists($destination))
            {
                echo " - already exists, moving...";
                unlink($destination);
                
                /* working...
                $filenames = glob($folder . "/" . "*.*");
                if(count($filenames) >= 5000)
                {
                    $arr = explode("_", $folder);
                    $count = intval($arr[1]) + 1;
                    $folder = $this->local_destination_old . "_" . $count . "/";
                    if(!file_exists($folder)) mkdir($folder);
                    print "\n copying 111...";
                    copy($destination, $folder . "/" . $parts["basename"]);
                }
                else
                {
                    print "\n copying 222...";
                    copy($destination, $folder . "/" . $parts["basename"]);
                }
                */
                
            }
        }
        
        
    }

    function save_before_site_goes_dark()
    {
        if(!self::load_zip_contents()) return FALSE;
        $names_no_entry_from_partner = self::get_names_no_entry_from_partner();
        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();
        $fields = $this->media_fields;
        $records = $func->make_array($this->text_path["media"], $fields);
        $total = count($records);
        $i = 0;

        $folder = $this->local_destination . "_15";
        if(!file_exists($folder)) mkdir($folder);

        foreach($records as $rec)
        {
            $i++;
            echo "\n $i of $total \n";

            // if($i <= 88325) continue; //debug 4,709

            $parts = pathinfo($rec["accessURI"]);
            $destination = $folder . "/" . $parts["basename"];

            // if(!file_exists($destination))
            if(!self::image_exists($parts["basename"]))
            {
                if(in_array($rec["accessURI"], $names_no_entry_from_partner)) continue;    // [$param] -> name_no_entry_from_partner";
                echo "- does not exist ";
                echo "\n -- " . $rec["accessURI"] . " processing... \n";

                $filenames = glob($folder . "/" . "*.*");
                if(count($filenames) >= 5000)
                {
                    $arr = explode("_", $folder);
                    $count = intval($arr[1]) + 1;
                    $folder = $this->local_destination . "_" . $count . "/";
                    if(!file_exists($folder)) mkdir($folder);
                    print "\n saving 111...";
                    echo "\n destination: [$destination] ";
                    self::save_big_file_to_local($rec["accessURI"], $destination);
                }
                else
                {
                    print "\n saving 222...";
                    echo "\n destination: [$destination] ";
                    self::save_big_file_to_local($rec["accessURI"], $destination);
                }
            }
            else
            {
                 echo " - already exists";
            }
        }
    }

    private function image_exists($basename)
    {
        for($i=0; $i<=20; $i++)
        {
            $folder = $this->local_destination . "_" . $i;
            $filename = $folder . "/" . $basename;
            if(!file_exists($folder)) continue;
            if(file_exists($filename))
            {
                echo "\n image exists: [$filename]";
                return true;
            }
        }
        return false;
    }
    
    private function save_big_file_to_local($source, $destination) // utility
    {
        $timestart = time_elapsed();
        if($contents = Functions::lookup_with_cache($source, array('cache_path' => '/Volumes/Eli blue/eol_cache/', 'download_wait_time' => 2000000, 
                                                                   'timeout' => 7200, 'download_attempts' => 1, 'delay_in_minutes' => 0)))
        {
            $destination_handle = fopen($destination, "w");
            fclose($destination_handle);
            $destination_handle = fopen($destination, "a");
            fwrite($destination_handle, $contents);
            fclose($destination_handle);
        }
        else
        {
            echo "\n no result for: [$source]\n";
            self::save_to_dump($source, $this->names_no_entry_from_partner_dump_file);
            return false;
        }
        $elapsed_time_sec = time_elapsed() - $timestart;
        echo "\n";
        echo "elapsed time = " . $elapsed_time_sec . " seconds \n";
        echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
        echo "\nDone processing.\n";
        return true;
    }

    private function save_to_dump($data, $filename)
    {
        $WRITE = fopen($filename, "a");
        if($data && is_array($data)) fwrite($WRITE, json_encode($data) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }

    function create_instances_from_taxon_object($func)
    {
        $fields = array("taxonID", "scientificName");
        $records = $func->make_array($this->text_path["taxa"], $fields);
        $total = count($records);
        foreach($records as $rec)
        {
            $i++;
            echo "\n $i of $total";
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $rec["taxonID"];
            $taxon->scientificName  = $rec["scientificName"];
            $this->taxa[$taxon->taxonID] = $taxon;
        }
    }
    
    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }

    function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::lookup_with_cache($this->zip_path, array('download_wait_time' => 1000000, 'timeout' => 7200, 'download_attempts' => 1, 'delay_in_minutes' => 2)))
        {
            $parts = pathinfo($this->zip_path);
            $temp_file_path = $this->TEMP_FILE_PATH . "/" . $parts["basename"];
            $TMP = fopen($temp_file_path, "w");
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("tar -xzf $temp_file_path -C $this->TEMP_FILE_PATH");
            if(file_exists($this->TEMP_FILE_PATH . "/europ_taxa_berlin.txt")) 
            {
                $this->text_path["taxa"]    = $this->TEMP_FILE_PATH . "/europ_taxa_berlin.txt";
                $this->text_path["media"]   = $this->TEMP_FILE_PATH . "/europ_media_berlin.txt";
                return TRUE;
            }
            else return FALSE;
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return FALSE;
        }
    }

    function unlink_files() // utility
    {
        // $files = array(
        //  "/Volumes/Time_Machine_Backups/dir_nbii_zip/Animals_Reproduction_ParentalBehavior.zip",
        //  "/Volumes/Time_Machine_Backups/dir_nbii_zip/Animals_Reptiles.zip"
        //  );
        //  foreach($files as $file) unlink($file);
    }

}
?>