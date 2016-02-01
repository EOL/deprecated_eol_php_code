<?php
namespace php_active_record;
/* connector: [799] */

class EOLSpreadsheetTextToArchiveAPI
{
    function __construct($resource_id)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
    }

    function convert_to_dwca($params)
    {
        foreach($params['extensions'] as $extension)
        {
            echo "\n[$extension]\n";
            self::process_text_file($extension, $params['text_files_path']);
            // break; //debug -- to process just 1 extension
            if($extension == "taxa")              unset($this->taxon_ids);
            elseif($extension == "references")     unset($this->ref_ids);
            elseif($extension == "occurrences")     unset($this->occur_ids);
            elseif($extension == "measurements") unset($this->measurement_ids);
        }
        $this->archive_builder->finalize(TRUE);
        print_r(@$this->debug);
    }

    private function process_text_file($extension, $path)
    {
        ini_set("auto_detect_line_endings", true);
        // $extension = 'references'; //debug -- to process just 1 extension
        $filename = Functions::save_remote_file_to_local($path.$extension.".txt", array('cache' => 0, 'file_extension' => "txt"));
        $i = 0;
        foreach(new FileIterator($filename) as $line_number => $line)
        {
            $temp = explode("\t", $line);
            $i++;
            if(($i % 50000) == 0) echo "\n" . number_format($i) . " - ";
            if    ($i == 1) $fields = $temp;
            elseif($i == 2) $uris = $temp;
            elseif($i == 3) $requirements = $temp;
            elseif($i > 8)
            {
                $rec = array();
                $k = 0;
                // 2 checks if valid record
                if(!$temp) continue;
                /* seems we cannot capture the prob. here
                if(count($fields) < count($temp)) continue;
                */
                foreach($temp as $t)
                {
                    $rec[@$fields[$k]] = $t;
                    $k++;
                }
                self::create_archive($extension, $fields, $uris, $rec);
            }
            // if($i >= 1000) break; //debug
        }
        unlink($filename);
    }
    
    private function create_archive($extension, $fields, $uris, $rec)
    {
        if($extension == "taxa")              $t = new \eol_schema\Taxon();
        elseif($extension == "references")      $t = new \eol_schema\Reference();
        elseif($extension == "occurrences")  $t = new \eol_schema\Occurrence();
        elseif($extension == "measurements") $t = new \eol_schema\MeasurementOrFact();
        else
        {
            echo "\nextension undefined!\n"; return;
        }
        
        $i = 0;
        foreach($fields as $field)
        {
            if($val = pathinfo($uris[$i], PATHINFO_BASENAME)) $t->$val = self::clean_string(@$rec[$fields[$i]]);
            $i++;
        }
        
        if($extension == "taxa")
        {
            if(!trim($t->scientificName)) return;
            if(!isset($this->taxon_ids[$t->taxonID]))
            {
                $this->taxon_ids[$t->taxonID] = '';
                $this->archive_builder->write_object_to_file($t);
            }
            // else echo "\nduplicate taxon entry excluded..."; commented but working... preferably uncomment during development
        }
        elseif($extension == "references")
        {
            if(!trim($t->identifier)) return;
            if(!isset($this->ref_ids[$t->identifier]))
            {
                $this->ref_ids[$t->identifier] = '';
                $this->archive_builder->write_object_to_file($t);
            }
            else echo "\nduplicate reference excluded...";
        }
        elseif($extension == "occurrences")
        {
            if(!trim($t->occurrenceID)) return;
            if(!isset($this->occur_ids[$t->occurrenceID]))
            {
                $this->occur_ids[$t->occurrenceID] = '';
                $this->archive_builder->write_object_to_file($t);
            }
            else echo "\nduplicate occurrence entry excluded...";
        }
        elseif($extension == "measurements")
        {
            if(!trim($t->measurementType)) return;
            $val = md5($t->occurrenceID.$t->measurementType);
            if(!isset($this->measurement_ids[$val]))
            {
                $this->measurement_ids[$t->occurrenceID] = '';
                $this->archive_builder->write_object_to_file($t);
            }
            else echo "\nduplicate measurement entry excluded...";
        }
    }
    
    private function clean_string($str)
    {
        $str = trim($str);
        if(substr($str, 0, 1) == '"' && substr($str, -1) == '"') $str = substr($str, 1, strlen($str)-2);
        
        if(!Functions::is_utf8($str)) $str = utf8_encode($str);
        return trim($str);
    }

}
?>

