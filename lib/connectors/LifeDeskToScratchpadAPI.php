<?php
namespace php_active_record;
// connector: [lifedesk_export]
class LifeDeskToScratchpadAPI
{
    function __construct()
    {
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1);
        $this->text_path = array();
        $this->booklet_taxa_list = array();
        /*
        $this->file_importer_xls["image"] = "http://localhost/~eolit/cp/LD2Scratchpad/file_importer_image_xls.xls";
        $this->file_importer_xls["text"] = "http://localhost/~eolit/cp/LD2Scratchpad/TEMPLATE-import_into_taxon_description_xls.xls";
        */
        $this->file_importer_xls["image"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/file_importer_image_xls.xls";
        $this->file_importer_xls["text"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/TEMPLATE-import_into_taxon_description_xls.xls";
    }

    function export_lifedesk_to_scratchpad($params)
    {
        if(self::load_zip_contents($params["lifedesk"]))
        {
            self::prepare_tab_delimited_text_files();
            self::parse_eol_xml();
        }
        print_r($this->booklet_taxa_list);
        self::initialize_dump_file($this->text_path["bibtex"]);
        self::convert_bibtex_file($params["bibtex_file"]);
        self::convert_tab_to_xls($params["name"]);
        // remove temp dir
        $parts = pathinfo($this->text_path["eol_xml"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }

    private function prepare_tab_delimited_text_files()
    {
        $types = array("image", "text");
        foreach($types as $type)
        {
            self::initialize_dump_file($this->text_path[$type]);
            $headers = self::get_column_headers($this->file_importer_xls[$type]);
            if($type == "text")
            {
                if(!in_array("Creator", $headers)) $headers[] = "Creator";
            }
            self::save_to_dump(implode("\t", $headers), $this->text_path[$type]);
            $this->lifedesk_fields[$type] = $headers;
        }
    }

    private function get_column_headers($spreadsheet)
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $fields = array();
        echo "\n processing [$spreadsheet]...\n";
        if($path = Functions::save_remote_file_to_local($spreadsheet, array("cache" => 1, "timeout" => 3600, "file_extension" => "xls", 'download_attempts' => 2, 'delay_in_minutes' => 2)))
        {
            $arr = $parser->convert_sheet_to_array($path);
            $fields = array_keys($arr);
            unlink($path);
        }
        else echo "\n [$spreadsheet] unavailable! \n";
        return $fields;
    }
    
    private function parse_eol_xml()
    {
        $xml = simplexml_load_file($this->text_path["eol_xml"]);
        $i = 0;
        foreach($xml->taxon as $t)
        {
            $i++;
            $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
            $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
            $identifier = Functions::import_decode($t_dc->identifier);
            $sciname    = Functions::import_decode($t_dwc->ScientificName);
            foreach($t->reference as $ref)
            {
                if(preg_match("/lifedesks.org\/biblio\/view\/(.*?)\"/ims", $ref, $arr)) $this->booklet_taxa_list[$arr[1]][$sciname] = '';
            }
            echo "\n [$identifier][$sciname]";
            $objects = $t->dataObject;
            foreach($objects as $do)
            {
                $t_dc2      = $do->children("http://purl.org/dc/elements/1.1/");
                $t_dcterms  = $do->children("http://purl.org/dc/terms/");
                $rec = array();
                $rec["Taxonomic name (Name)"] = $sciname;
                if($do->dataType == "http://purl.org/dc/dcmitype/StillImage")
                {
                    if($val = self::get_mediaURL($t_dc2, $do)) $rec["Filename"] = $val;
                    else continue;
                    $rec["Licence"] = (string) $do->license;
                    $rec["Description"] = self::get_description($t_dc2, $do, "image");
                    $rec["Creator"] = self::get_creator($t_dcterms, $do, "image");
                    self::save_to_template($rec, $this->text_path["image"], "image");
                }
                elseif($do->dataType == "http://purl.org/dc/dcmitype/Text")
                {
                    if($val = self::get_subject($do))
                    {
                        $rec[$val] = self::get_description($t_dc2, $do);
                        $rec["Creator"] = self::get_creator($t_dcterms, $do); // not yet implemented in the xls template
                        self::save_to_template($rec, $this->text_path["text"], "text");
                    }
                }
            }
            // if($i > 5) break; //debug
        }
    }
    
    private function get_description($dc, $do, $data_type = null)
    {
        $desc = "";
        if($val = $dc->description) $desc .= trim($val);
        $desc = self::set_desc_separator($desc);
        if($data_type == "image")
        {
            if($photographers = self::get_photographers($do->agent)) $desc .= "Photographer: " . implode(";", $photographers) . ". ";
        }
        return $desc;
    }
    
    private function set_desc_separator($desc)
    {
        if(substr($desc, -4) == "<br>") return $desc;
        elseif(substr($desc, -2) == ". ") return $desc;
        elseif(substr($desc, -1) == ".") return $desc .= " ";
        return $desc;
    }
    
    private function get_photographers($agents)
    {
        $photographers = array();
        foreach($agents as $agent)
        {
            if(is_numeric(stripos($agent{"role"}, "photographer"))) $photographers[(string) $agent] = '';
        }
        return array_keys($photographers);
    }
    
    private function get_creator($dcterms, $do, $data_type = null)
    {
        $creator = "";
        if($val = $dcterms->rightsHolder) $creator .= $val;
        else
        {
            if($data_type == "image")
            {
                $photographers = self::get_photographers($do->agent);
                $creator = implode(",", $photographers);
            }
        }
        if(!$creator && !is_numeric(stripos($do->license, "publicdomain"))) echo "\n[investigate: no creator and not public domain!]\n";
        return $creator;
    }
    
    private function get_mediaURL($dc, $do)
    {
        if($dc->source && $do->mediaURL)
        {
            if($html = Functions::lookup_with_cache($dc->source, $this->download_options))
            {
                $image_extension = self::get_image_extension($do->mimeType);
                $str = ".preview." . $image_extension;
                if(preg_match("/class=\"jqzoom\"><img src=\"(.*?)" . $str . "/ims", $html, $arr)) return $arr[1] . ".$image_extension";
            }
        }
        return false;
    }

    private function get_image_extension($mimetype)
    {
        if($mimetype == "image/bmp") return "bmp";
        elseif($mimetype == "image/gif") return "gif";
        elseif($mimetype == "image/jpeg") return "jpg";
        elseif($mimetype == "image/png") return "png";
        elseif($mimetype == "image/tiff") return "tif";
        return false;
    }
    
    private function load_zip_contents($zip_file)
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::lookup_with_cache($zip_file, array('timeout' => 172800, 'download_attempts' => 5)))
        {
            $parts = pathinfo($zip_file);
            $temp_file_path = $this->TEMP_FILE_PATH . "/" . $parts["basename"];
            $TMP = fopen($temp_file_path, "w");
            fwrite($TMP, $file_contents);
            fclose($TMP);
            
            if(is_numeric(stripos($zip_file, ".tar.gz"))) $output = shell_exec("tar -xzf $temp_file_path -C $this->TEMP_FILE_PATH");
            elseif(is_numeric(stripos($zip_file, ".xml.gz"))) $output = shell_exec("gzip -d $temp_file_path -q "); //$this->TEMP_FILE_PATH
            
            if(!file_exists($this->TEMP_FILE_PATH . "/eol-partnership.xml")) 
            {
                $this->TEMP_FILE_PATH = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($this->TEMP_FILE_PATH . "/eol-partnership.xml")) return false;
            }
            $this->text_path["eol_xml"] = $this->TEMP_FILE_PATH . "eol-partnership.xml";
            // initialize tab-delimited text files to be used
            $this->text_path["image"] = $this->TEMP_FILE_PATH . "file_importer_image_xls.txt";
            $this->text_path["text"] = $this->TEMP_FILE_PATH . "TEMPLATE-import_into_taxon_description_xls.txt";
            $this->text_path["bibtex"] = $this->TEMP_FILE_PATH . "Biblio-Bibtex.bib";
            return true;
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return false;
        }
    }

    public function convert_tab_to_xls($lifedesk)
    {
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php';
        $destination_folder = create_temp_dir() . "/";
        $types = array("image", "text");
        foreach($types as $type)
        {
            $inputFileName = $this->text_path[$type];
            $outputFileName = str_replace(".txt", ".xls", $this->text_path[$type]);
            // start conversion
            $objReader = \PHPExcel_IOFactory::createReader('CSV');
            // If the files uses a delimiter other than a comma (e.g. a tab), then tell the reader
            $objReader->setDelimiter("\t");
            // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
            // $objReader->setInputEncoding('UTF-16LE');
            /* other settings:
            $objReader->setEnclosure(" ");
            $objReader->setLineEnding($endrow);
            */
            $objPHPExcel = $objReader->load($inputFileName);
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save($outputFileName);
            // move file to temp folder for compressing
            $parts = pathinfo($outputFileName);
            copy($outputFileName, $destination_folder . $parts["basename"]);
        }
        // move file to temp folder for compressing
        $parts = pathinfo($this->text_path["bibtex"]);
        copy($this->text_path["bibtex"], $destination_folder . $parts["basename"]);
        // compress export files
        $command_line = "tar -czf " . DOC_ROOT . "/tmp/" . $lifedesk . "_LD_to_Scratchpad_export.tar.gz --directory=" . $destination_folder . " .";
        $output = shell_exec($command_line);
        recursive_rmdir($destination_folder);
    }

    public function convert_bibtex_file($file)
    {
        if($contents = Functions::lookup_with_cache($file, array('timeout' => 172800, 'download_attempts' => 5)))
        {
            $contents = str_replace("@", "xxxyyy@", $contents."@");
            if(preg_match_all("/\@(.*?)xxxyyy/ims", $contents, $arr))
            {
                foreach($arr[1] as $r) // loop each booklet or article
                {
                    $rec = explode("\t", $r);
                    $i = 0;
                    $id = false;
                    $with_keyword = false;
                    foreach($rec as $item) // loop each item per record
                    {
                        if($i == 0)
                        {
                            if(preg_match("/article(.*?)\,/ims", $item, $arr2) || preg_match("/booklet(.*?)\,/ims", $item, $arr2))
                            {
                                $arr2[1] = trim(str_replace("{","", $arr2[1]));
                                $id = $arr2[1];
                            }
                            $rec[0] = "@" . $rec[0];
                        }
                        else // after first item
                        {
                            if(is_numeric(stripos($item, "keywords = {")) && @$this->booklet_taxa_list[$id])
                            {
                                echo "\n[$item]\n";
                                $item = str_replace("}", ", " . implode(", ", array_keys($this->booklet_taxa_list[$id]))."}", $item);
                                echo "\ninserted:";
                                echo "\n[$item]\n";
                                $rec[$i] = $item;
                                $with_keyword = true;
                            }
                        }
                        $i++;
                    }
                    if(!$with_keyword && @$this->booklet_taxa_list[$id])
                    {
                        $rec[] = "keywords = {" . implode(", ", array_keys($this->booklet_taxa_list[$id])) . "}" . chr(10) . "}" . "\n";
                        $rec[$i-1] = str_replace(chr(10), "", $rec[$i-1]);
                        $rec[$i-1] = substr($rec[$i-1], 0, strlen($rec[$i-1])-1);
                        $rec[$i-1] .= "," . chr(10);
                        echo "\n to be inserted: [" . $rec[$i-1] . "]\n";
                    }
                    // save to text file
                    $rec = implode(chr(9), $rec);
                    self::save_to_dump($rec, $this->text_path["bibtex"], "");
                }
            }
        }
        else echo "\n investigate: [$file] not found... \n";
    }
    
    private function save_to_template($rec, $filename, $type)
    {
        $WRITE = fopen($filename, "a");
        foreach($this->lifedesk_fields[$type] as $header)
        {
            if($val = (@$rec[$header])) fwrite($WRITE, $val . "\t");
            else                        fwrite($WRITE, "\t");
        }
        fwrite($WRITE, "\n");
        fclose($WRITE);
    }
    
    private function save_to_dump($rec, $filename, $nextline = "\n")
    {
        $WRITE = fopen($filename, "a");
        if($rec && is_array($rec)) fwrite($WRITE, json_encode($rec) . $nextline);
        else                       fwrite($WRITE, $rec . $nextline);
        fclose($WRITE);
    }

    private function initialize_dump_file($file)
    {
        echo "\n initialize file:[$file]\n";
        $f=fopen($file,"w"); 
        # Now UTF-8 - Add byte order mark 
        fwrite($f, pack("CCC",0xef,0xbb,0xbf));
        fclose($f);
    }
    
    private function get_subject($do)
    {
        $spm = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#";
        $subject = $do->subject;
        if(is_numeric(stripos($subject, $spm)))
        {
            $subject = str_replace($spm, "", $subject);
            if($subject == "Conservation")              return "Management";
            elseif($subject == "Description")           return "General description";
            elseif($subject == "ConservationStatus")    return "Conservation status";
            elseif($subject == "DiagnosticDescription") return "Diagnostic description";
            elseif($subject == "GeneralDescription")    return "General description";
            elseif($subject == "LifeCycle")             return "Life cycle";
            elseif($subject == "LifeExpectancy")        return "Life expectancy";
            elseif($subject == "LookAlikes")            return "Look alikes";
            elseif($subject == "MolecularBiology")      return "Molecular biology";
            elseif($subject == "PopulationBiology")     return "Population biology";
            elseif($subject == "RiskStatement")         return "Risk statement";
            elseif($subject == "TaxonBiology")          return "Taxon biology";
            elseif($subject == "TrophicStrategy")       return "Trophic strategy";
            else return $subject;
        }
        else
        {
            echo "\n investigate: no subject\n";
            print_r($do);
        }
        /* Reminders:
        
        available in SPM but not as a header in the spreadsheet template
            http://rs.tdwg.org/ontology/voc/SPMInfoItems#Key

        available headers in spreadsheet template but I don't know the corresponding <subject>
            [26] => Phylogeny
            [27] => Map
        */
    }

}
?>