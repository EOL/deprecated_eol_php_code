<?php
namespace php_active_record;
/* connector: [799] */
/* connector: [generic_xls2dwca.php] */

class EOLSpreadsheetToArchiveAPI
{
    function __construct($resource_id)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array("timeout" => 3600, 'download_attempts' => 2, 'delay_in_minutes' => 2);
        // $this->download_options['cache'] = 1; //will use cache
    }

    //start text process ====================================================================================
    function convert_spreadsheet_text_to_dwca($params)
    {
        foreach($params['extensions'] as $extension)
        {
            echo "\n[$extension]\n";
            self::process_text_file($extension, $params['text_files_path']);
            // break; //debug -- to process just 1 extension
            if($extension == "taxa")             unset($this->taxon_ids);
            elseif($extension == "references")   unset($this->ref_ids);
            elseif($extension == "occurrences")  unset($this->occur_ids);
            elseif($extension == "measurements") unset($this->measurement_ids);
        }
        $this->archive_builder->finalize(TRUE);
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
                
                // checks if valid record
                if(!$temp) continue;
                
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
    //end text process ======================================================================================

    function convert_to_dwca($spreadsheet)
    {
        self::start($spreadsheet);
        $this->archive_builder->finalize(TRUE);
    }

    private function start($spreadsheet, $temp_path = false)
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $doc = self::download_file_accordingly($spreadsheet);
        $download_options = $this->download_options;
        // $download_options['cache'] = 1; //debug - comment in real operation
        $download_options['file_extension'] = self::get_extension($doc);
        
        if($path = Functions::save_remote_file_to_local($doc, $download_options))
        {
            $worksheets = self::get_worksheets($path, $parser, $temp_path);
            print_r($worksheets);
            foreach($worksheets as $index => $worksheet_title)
            {
                echo "\nProcessing worksheet: [$worksheet_title]";

                if($temp_path) //meaning save to text files
                {
                    $params = array("worksheet_title" => $worksheet_title, "path" => $temp_path);
                    $parser->convert_sheet_to_array($path, $index, NULL, $params);
                }
                else
                {
                    $arr = $parser->convert_sheet_to_array($path, $index);
                    if(!self::sheet_is_valid($arr, $worksheet_title))
                    {
                        echo " - invalid worksheet\n";
                        continue;
                    }
                    $fields = array();
                    $uris = array();
                    $fields = array_keys($arr);
                    foreach($fields as $field) $uris[] = $arr[$field][0];
                    // print_r($fields); print_r($uris); continue;
                    $i = -1;
                    foreach($arr[$fields[0]] as $row)
                    {
                        $i++;
                        if($i > 7) // >= 8
                        {
                            $rec = array();
                            foreach($fields as $field) $rec[$field] = $arr[$field][$i];
                            if($rec) self::create_archive($worksheet_title, $fields, $uris, $rec);
                        }
                    }
                }
            }
            unlink($path);
            if(file_exists($doc)) unlink($doc);
        }
        else echo "\n [$doc] unavailable! \n";
    }
    
    /*
    function convert_to_text_to_dwca($spreadsheet) //this saves the worksheets to text files first, then converts the DWC-A.
    {
        $temp_path = DOC_ROOT . "/tmp/". time();
        mkdir($temp_path);
        echo "\n[$temp_path]\n";
        self::start($spreadsheet, $temp_path); //2nd param triggers save to text files
        self::process_text_filez($temp_path);
        $this->archive_builder->finalize(TRUE);
        if(is_dir($temp_path)) recursive_rmdir($temp_path);
    }
    
    private function process_text_filez($temp_path)
    {
        foreach(glob($temp_path."/*.txt") as $filename)
        {
            $extension = pathinfo($filename, PATHINFO_FILENAME);
            // $extension2 = get_sheet_name_from_text_file();
            echo "\n[$extension]\n";
            
            $fields = array();
            $uris = array();
            
            $i = 0;
            foreach(new FileIterator($filename) as $line_number => $line)
            {
                $temp = explode("\t", $line);
                $i++;
                if(($i % 50000) == 0) echo "\n" . number_format($i) . " - ";
                if    ($i == 1) $fields = $temp;
                elseif($i == 2) $uris = $temp;
                elseif($i == 3) $requirements = $temp;
                elseif($i > 9)
                {
                    $rec = array();
                    $k = 0;
                    
                    // checks if valid record
                    if(!$temp) continue;
                    if(!$fields) continue;
                    if(!$uris) continue;
                    
                    foreach($temp as $t)
                    {
                        $rec[@$fields[$k]] = $t;
                        $k++;
                    }
                    
                    // print_r($fields); print_r($uris); print_r($rec);
                    self::create_archive($extension, $fields, $uris, $rec);
                }
            }
        }
    }
    */

    public function create_archive($extension, $fields, $uris, $rec)
    {
        if($extension == "media")                       $t = new \eol_schema\MediaResource();
        elseif($extension == "taxa")                    $t = new \eol_schema\Taxon();
        elseif($extension == "references")              $t = new \eol_schema\Reference();
        elseif($extension == "occurrences")             $t = new \eol_schema\Occurrence();
        elseif($extension == "measurements or facts")   $t = new \eol_schema\MeasurementOrFact();
        elseif($extension == "measurements")            $t = new \eol_schema\MeasurementOrFact();
        elseif($extension == "common names")            $t = new \eol_schema\VernacularName();
        elseif($extension == "agents")                  $t = new \eol_schema\Agent();
        elseif($extension == "associations")            $t = new \eol_schema\Association();
        elseif($extension == "events")                  $t = new \eol_schema\Event();
        else
        {
            echo "\nextension undefined![$extension]\n";
            return;
        }
        
        $i = 0;
        foreach($fields as $field)
        {
            if($val = self::get_field_from_uri(@$uris[$i])) $t->$val = self::clean_string(@$rec[$fields[$i]]);
            $i++;
        }
        
        if($extension == "media")
        {
            if(!trim($t->taxonID)) return; //meaning object is not assigned to any taxon
            if(!isset($this->media_ids[$t->identifier]))
            {
                $this->media_ids[$t->identifier] = '';
                $this->archive_builder->write_object_to_file($t);
            }
            else echo "\nduplicate taxon entry excluded...[$t->identifier]"; //commented but working... preferably uncomment during development
        }
        elseif($extension == "taxa")
        {
            if(!trim($t->scientificName)) return;
            if(!isset($this->taxon_ids[$t->taxonID]))
            {
                $this->taxon_ids[$t->taxonID] = '';
                $this->archive_builder->write_object_to_file($t);
            }
            else echo "\nduplicate taxon entry excluded...[$t->taxonID]"; //commented but working... preferably uncomment during development
        }
        elseif($extension == "references")
        {
            if(!trim($t->identifier)) return;
            if(!isset($this->ref_ids[$t->identifier]))
            {
                $this->ref_ids[$t->identifier] = '';
                $this->archive_builder->write_object_to_file($t);
            }
            else echo "\nduplicate reference excluded...[$t->identifier]";
        }
        elseif($extension == "occurrences")
        {
            if(!trim($t->occurrenceID)) return;
            if(!isset($this->occur_ids[$t->occurrenceID]))
            {
                $this->occur_ids[$t->occurrenceID] = '';
                $this->archive_builder->write_object_to_file($t);
            }
            else echo "\nduplicate occurrence entry excluded...[$t->occurrenceID]";
        }
        elseif(in_array($extension, array("measurements or facts", "measurements")))
        {
            if(!trim($t->measurementType)) return;
            /* if(!trim($t->occurrenceID)) return; //seems occurrenceID is not required, meaning this can be blank */
            
            if($val = $t->measurementID) //if measurementID exists, then it has to be unique
            {
                if(!isset($this->measurement_ids[$val]))
                {
                    $this->measurement_ids[$val] = '';
                    $this->archive_builder->write_object_to_file($t);
                }
            }
            else $this->archive_builder->write_object_to_file($t);
        }
        elseif($extension == "common names")
        {
            if(!trim($t->vernacularName)) return;
            $this->archive_builder->write_object_to_file($t);
        }
        elseif($extension == "agents")
        {
            if(!trim($t->agentRole)) return;
            if(!isset($this->agent_ids[$t->identifier])) //t->agentID from http://eol.org/schema/media_agents.xml, but just t->identifier from the spreadsheet
            {
                $this->agent_ids[$t->identifier] = '';
                $this->archive_builder->write_object_to_file($t);
            }
            else echo "\nduplicate agent entry excluded...[$t->identifier]";
        }
        elseif($extension == "associations")
        {
            if(!trim($t->associationType)) return;
            $this->archive_builder->write_object_to_file($t);
        }
        elseif($extension == "events")
        {
            $this->archive_builder->write_object_to_file($t);
        }
        
    }
    
    private function clean_string($str)
    {
        $str = trim($str);
        if(substr($str, 0, 1) == '"' && substr($str, -1) == '"') $str = substr($str, 1, strlen($str)-2);
        
        if(!Functions::is_utf8($str)) $str = utf8_encode($str);
        return trim($str);
    }
    
    private function get_extension($file_path)
    {
        $path_info = pathinfo($file_path);
        return strtolower(@$path_info['extension']);
    }
    
    private function get_worksheets($path, $parser, $temp_path)
    {
        if($temp_path) //meaning spreadsheet is big
        {
            // /* //manual
            $worksheets[0] = "media";
            $worksheets[1] = "taxa";
            $worksheets[2] = "common names";
            $worksheets[3] = "references";
            $worksheets[4] = "agents";
            $worksheets[5] = "occurrences";
            $worksheets[6] = "measurements or facts";
            $worksheets[7] = "associations";
            $worksheets[8] = "events";
            return $worksheets;
        }
        
        $i = 0;
        while(true)
        {
            $a = $parser->convert_sheet_to_array($path, $i);
            if($a)
            {
                $col_titles_on_sheet = array_keys($a);
                if($col_titles_on_sheet[0] == "MediaID") $worksheets[$i] = "media";
                if(in_array("Full Reference", $col_titles_on_sheet) && in_array("ReferenceID", $col_titles_on_sheet)) $worksheets[$i] = "references";
                if(in_array("ScientificName", $col_titles_on_sheet) && in_array("Identifier", $col_titles_on_sheet)) $worksheets[$i] = "taxa";
                if(in_array("Name", $col_titles_on_sheet) && in_array("TaxonID", $col_titles_on_sheet)) $worksheets[$i] = "common names";
                if(in_array("AgentID", $col_titles_on_sheet) && in_array("Role", $col_titles_on_sheet)) $worksheets[$i] = "agents";
                if(in_array("OccurrenceID", $col_titles_on_sheet) && in_array("TaxonID", $col_titles_on_sheet)) $worksheets[$i] = "occurrences";
                if(in_array("Occurrence ID", $col_titles_on_sheet) && in_array("TaxonID", $col_titles_on_sheet)) $worksheets[$i] = "occurrences";
                if(in_array("Measurement ID", $col_titles_on_sheet) && in_array("MeasurementOfTaxon", $col_titles_on_sheet)) $worksheets[$i] = "measurements or facts";
                if(in_array("AssociationID", $col_titles_on_sheet) && in_array("Association Type", $col_titles_on_sheet)) $worksheets[$i] = "associations";
                if(in_array("EventID", $col_titles_on_sheet) && in_array("Locality", $col_titles_on_sheet)) $worksheets[$i] = "events";
                if(in_array("Agent Roles", $col_titles_on_sheet) && in_array("Data Types", $col_titles_on_sheet)) {} //this is just controlled vocabulary worksheet
            }
            else break;
            $i++;
        }
        return $worksheets;
    }
    
    private function sheet_is_valid($arr, $title)
    {
        $arr = array_keys($arr);
        if($title == 'media')                       {if($arr[0] == "MediaID") return true;}
        elseif($title == 'taxa')                    {if($arr[1] == "ScientificName") return true;}
        elseif($title == 'common names')            {if($arr[1] == "Name") return true;}
        elseif($title == 'references')              {if($arr[0] == "ReferenceID") return true;}
        elseif($title == 'agents')                  {if($arr[0] == "AgentID") return true;}
        elseif($title == 'occurrences')             {if($arr[0] == "OccurrenceID") return true;}
        elseif($title == 'measurements or facts')   {if($arr[0] == "Measurement ID") return true;}
        elseif($title == 'associations')            {if($arr[0] == "AssociationID") return true;}
        elseif($title == 'events')                  {if($arr[0] == "EventID") return true;}

        echo "\nInvestigate invalid worksheet: [$title]\n";
        print_r($arr);
        return false;
    }

    private function get_field_from_uri($uri)
    {
        $temp = pathinfo($uri, PATHINFO_BASENAME);
        /*
        if($temp == "schema#localityName")  return "localityName"; //from reference extension
        elseif($temp == "wgs84_pos#alt")    return "alt"; //from media extension
        elseif($temp == "wgs84_pos#lat")    return "lat"; //from media extension
        elseif($temp == "wgs84_pos#long")   return "long"; //from media extension
        */
        
        $arr = explode("#", $temp);
        if($val = @$arr[1]) return $val;
        else return $arr[0];
    }

    private function download_file_accordingly($path)
    {
        $pathinfo = pathinfo($path);
        if(stripos($pathinfo['dirname'], "https://www.dropbox.com/") !== false) //string is found => spreadsheet is from DropBox
        {
            $a = explode("?", $pathinfo['basename']);
            $extension = self::get_extension($a[0]);
            
            $download_options = $this->download_options;
            $download_options['file_extension'] = $extension;
            
            $path = str_ireplace("dl=0", "dl=1", $path);
            if($newpath = Functions::save_remote_file_to_local($path, $download_options))
            {
                echo("\nnewpath: [$newpath]\n");
                return $newpath;
            }
        }
        return $path;
    }

}
?>
