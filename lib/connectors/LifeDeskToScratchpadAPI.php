<?php
namespace php_active_record;
// connector: [lifedesk_export]
class LifeDeskToScratchpadAPI
{
    function __construct()
    {
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 2); // 15mins timeout
        /*
        $this->file_importer_xls["image"] = "http://localhost/~eolit/cp/LD2Scratchpad/templates/file_importer_image_xls.xls";
        $this->file_importer_xls["text"] = "http://localhost/~eolit/cp/LD2Scratchpad/templates/TEMPLATE-import_into_taxon_description_xls.xls";
        */
        $this->file_importer_xls["image"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/templates/file_importer_image_xls.xls";
        $this->file_importer_xls["text"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/templates/TEMPLATE-import_into_taxon_description_xls.xls";
    }

    function export_lifedesk_to_scratchpad($params)
    {
        $this->text_path = array();
        $this->booklet_taxa_list = array();
        $this->booklet_title_list = array();
        $this->lifedesk_fields = array();
        $this->scratchpad_image_taxon_list = array();
        $this->used_GUID = array();
        $this->taxon_description = array();
        
        if($val = @$params["scratchpad_biblio"]) $this->file_importer_xls["biblio"] = $val;
        if(self::load_zip_contents($params["lifedesk"]))
        {
            self::prepare_tab_delimited_text_files();
            self::get_scratchpad_image_taxon_list($params["scratchpad_images"], "0"); // "0" here means to open the 1st worksheet from the spreadsheet
            self::parse_eol_xml();
            self::add_images_without_taxa_to_export_file();
        }
        self::initialize_dump_file($this->text_path["bibtex"]);
        if($val = @$params["bibtex_file"])
        {
            self::convert_bibtex_file($val);
            if($val = @$params["scratchpad_biblio"]) self::fill_up_biblio_spreadsheet($params);
        }
        
        self::convert_tab_to_xls($params);
        // remove temp dir
        $parts = pathinfo($this->text_path["eol_xml"]);
        recursive_rmdir($parts["dirname"]); //debug - comment if you want to see: images_not_in_xls.txt
        debug("\n temporary directory removed: " . $parts["dirname"]);
        print_r($params);
        print "\ntaxon_description: " . count($this->taxon_description) . "\n\n";
    }

    private function fill_up_biblio_spreadsheet($params)
    {
        if($this->booklet_title_list) self::write_biblio_text($params['scratchpad_biblio']);
    }

    private function write_biblio_text($spreadsheet)
    {
        $headers = self::get_column_headers($spreadsheet);
        if($arr = self::convert_spreadsheet($spreadsheet, 0))
        {
            print "\n spreadsheet: " . count($arr[$headers[0]]) . "\n";
            $i = 0;
            foreach($arr['GUID'] as $guid)
            {
                if($scinames = self::get_scinames_from_booklet_title_list($arr['Title'][$i]))
                {
                    $rec = array();
                    foreach($headers as $header)
                    {
                        if($header == "Taxonomic name (Name)") $rec[$header] = $scinames;
                        else $rec[$header] = $arr[$header][$i];
                    }
                    if($rec) self::save_to_template($rec, $this->text_path["biblio"], "biblio");
                }
                else
                {
                    $rec = array();
                    foreach($headers as $header) $rec[$header] = $arr[$header][$i];
                    if($rec) self::save_to_template($rec, $this->text_path["biblio"], "biblio");
                }
                $i++;
            }
            /*
            [0] => GUID
            [1] => Title
            [2] => Body
            [3] => Taxonomic name (Name)
            [4] => Taxonomic name (TID)
            [5] => Taxonomic name (GUID)
            [6] => File attachments (Filename)
            [7] => File attachments (URL)
            [8] => Node Author (UID)
            */
        }
    }
    
    private function get_scinames_from_taxon_description($title)
    {
        foreach($this->taxon_description as $sciname => $rec)
        {
            foreach(array_keys($rec) as $desc)
            {
                if(is_numeric(stripos($desc, $title))) return $sciname;
            }
        }
        return false;
    }
    
    private function get_scinames_from_booklet_title_list($title)
    {
        // manual adjustments
        $title = str_ireplace("’", "'", $title);
        $title = str_ireplace("&", "&amp;", $title);
        $title = str_ireplace("{\'ı", "í", $title);
        
        foreach($this->booklet_title_list as $biblio_title => $scinames)
        {
            if($biblio_title == $title) return implode("|", $scinames);
        }
        foreach($this->booklet_title_list as $biblio_title => $scinames)
        {
            if($title == substr($biblio_title, 0, strlen($title))) return implode("|", $scinames);
        }
        if($scinames = self::get_scinames_from_taxon_description($title)) return $scinames;
        return false;
    }
    
    // private function get_biblio_titles_from_LD_xml($params)
    // {
    //     $titles = array();
    //     $response = Functions::lookup_with_cache($params['biblio_xml_file'], $this->download_options);
    //     $xml = simplexml_load_string($response);
    //     foreach($xml->records->record as $rec)
    //     {
    //         $titles[(string) $rec->titles->title->style] = '';
    //     }
    //     print_r($titles);
    //     return array_keys($titles);
    // }
    
    // private function download_bibtex_xml($params)
    // {
    //     if(@$params['bibtex_file'])
    //     {
    //         $file = "http://" . $params['name'] . ".lifedesks.org/biblio/export/xml/";
    //         if($xml = Functions::lookup_with_cache($file, $this->download_options))
    //         {
    //             $destination = "/Users/eolit/Sites/cp/LD2Scratchpad/" . $params['name'] . "/biblio_xml.xml";
    //             if($TMP = fopen($destination, "w"))
    //             {
    //                 fwrite($TMP, $xml);
    //                 fclose($TMP);
    //                 echo "\n saved...$destination\n";
    //             }
    //             else exit("\n cannot access path... \n");
    //         }
    //     }
    // }

    private function prepare_tab_delimited_text_files()
    {
        $types = array("image", "text");
        if(@$this->file_importer_xls["biblio"]) $types[] = "biblio";
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

    private function get_scratchpad_image_taxon_list($spreadsheet, $worksheet)
    {
        if($arr = self::convert_spreadsheet($spreadsheet, $worksheet))
        {
            $i = 0;
            foreach($arr["GUID"] as $guid)
            {
                $filename = strtolower($arr["Filename"][$i]);
                $this->scratchpad_image_taxon_list[$filename]["guid"]        = self::clean_str($guid);
                $this->scratchpad_image_taxon_list[$filename]["taxon"]       = self::clean_str($arr["Taxonomic name (Name)"][$i]);
                // we are not sure if these 3 fiels will be provided by the scratchpad file
                $this->scratchpad_image_taxon_list[$filename]["license"]     = self::clean_str($arr["Licence"][$i]);
                $this->scratchpad_image_taxon_list[$filename]["description"] = self::clean_str($arr["Description"][$i]);
                $this->scratchpad_image_taxon_list[$filename]["creator"]     = self::clean_str($arr["Creator"][$i]);
                $i++;
            }
        }
    }
    
    private function clean_str($str)
    {
        $str = str_replace(array("\t", chr(9), "\n", chr(10)), "", $str);
        return $str;
    }
    
    private function get_column_headers($spreadsheet)
    {
        $fields = array();
        if($arr = self::convert_spreadsheet($spreadsheet, 0)) $fields = array_keys($arr);
        return $fields;
    }
    
    private function convert_spreadsheet($spreadsheet, $worksheet = null)
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        if($path = Functions::save_remote_file_to_local($spreadsheet, array("cache" => 1, "timeout" => 3600, "file_extension" => "xls", 'download_attempts' => 2, 'delay_in_minutes' => 2)))
        {
            $arr = $parser->convert_sheet_to_array($path, $worksheet);
            unlink($path);
            return $arr;
        }
        else echo "\n [$spreadsheet] unavailable! \n";
        return false;
    }
    
    private function parse_eol_xml()
    {
        // for stats
        $parts = pathinfo($this->text_path["eol_xml"]);
        $dump_file = $parts["dirname"] . "/images_not_in_xls.txt";

        $xml_str = file_get_contents($this->text_path["eol_xml"]);
        $xml_str = str_replace("", "", $xml_str);
        if(!$xml = simplexml_load_string($xml_str)) exit("\nLifeDesk XML is invalid\n\n");

        $i = 0;
        foreach($xml->taxon as $t)
        {
            $i++;
            $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
            $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
            $identifier = (string) $t_dc->identifier;
            $sciname    = (string) $t_dwc->ScientificName;
            foreach($t->reference as $ref)
            {
                if(preg_match("/lifedesks.org\/biblio\/view\/(.*?)\"/ims", $ref, $arr)) $this->booklet_taxa_list[$arr[1]][$sciname] = '';

                /* lifedesks.org/biblio/view/55">biblio title goes here</a> --- get title */
                if(preg_match("/lifedesks.org\/biblio\/view\/(.*?)<\/a>/ims", $ref, $arr))
                {
                    if(preg_match("/>(.*?)xxx/ims", $arr[1]."xxx", $arr))
                    {
                        $this->booklet_title_list[Functions::remove_whitespace($arr[1])][] = $sciname;
                    }
                }
                
            }
            if(($i % 100) == 0) echo "\n$i. [$identifier][$sciname]";
            $objects = $t->dataObject;
            foreach($objects as $do)
            {
                $t_dc2      = $do->children("http://purl.org/dc/elements/1.1/");
                $t_dcterms  = $do->children("http://purl.org/dc/terms/");
                
                $this->taxon_description[$sciname][(string) $t_dc2->description] = '';
                
                $rec = array();
                $rec["Taxonomic name (Name)"] = $sciname;
                if($do->dataType == "http://purl.org/dc/dcmitype/StillImage")
                {
                    if($filename = self::get_mediaURL($t_dc2, $do)) $rec["Filename"] = $filename;
                    else continue;
                    $rec["Licence"] = self::get_license((string) $do->license);
                    $rec["Description"] = self::get_description($t_dc2, $do, "image");
                    $rec["Creator"] = self::get_creator($t_dcterms, $do, "image");
                    
                    if($guid = @$this->scratchpad_image_taxon_list[$filename]["guid"])
                    {
                        $rec["GUID"] = $guid;
                        $this->used_GUID[$guid] = '';
                    }
                    else
                    {
                        echo "\n alert: no guid [$filename][$t_dc2->identifier]\n"; // this means that an image file in XML is not found in the image XLS submitted by SPG
                        self::save_to_dump($sciname . "\t" . $t_dc2->identifier . "\t" . $filename, $dump_file);
                        exit("\n-stopped- Will need to notify SPG\n");
                    }
                    
                    self::save_to_template($rec, $this->text_path["image"], "image");
                }
                elseif($do->dataType == "http://purl.org/dc/dcmitype/Text")
                {
                    if($val = self::get_subject($do))
                    {
                        if($rec[$val] = self::get_description($t_dc2, $do))
                        {
                            $rec["Creator"] = self::get_creator($t_dcterms, $do); // not yet implemented in the xls template
                            self::save_to_template($rec, $this->text_path["text"], "text");
                        }
                    }
                }
            }
            // if($i > 5) break; //debug
        }
    }
    
    private function add_images_without_taxa_to_export_file()
    {
        foreach($this->scratchpad_image_taxon_list as $filename => $value)
        {
            $rec = array();
            if(!isset($this->used_GUID[$value["guid"]]))
            {
                $rec["GUID"] = $value["guid"];
                $rec["Filename"] = $filename;
                $rec["Taxonomic name (Name)"] = $value["taxon"];
                $rec["Licence"]     = @$value["license"];
                $rec["Description"] = @$value["description"];
                $rec["Creator"]     = @$value["creator"];
                self::save_to_template($rec, $this->text_path["image"], "image");
            }
        }
    }
    
    private function get_license($path)
    {
        if(is_numeric(stripos($path, "licenses/publicdomain/"))) return "Public Domain";
        elseif(is_numeric(stripos($path, "licenses/by/")))       return "Attribution CC BY";
        elseif(is_numeric(stripos($path, "licenses/by-nc/")))    return "Attribution, Non-Commercial CC BY-NC";
        elseif(is_numeric(stripos($path, "licenses/by-sa/")))    return "Attribution, Share Alike CC BY-SA";
        elseif(is_numeric(stripos($path, "licenses/by-nc-sa/"))) return "Attribution, Non-Commercial, Share Alike CC BY-NC-SA";
        echo "\n investigate: no license \n";
        return false;
    }
    
    private function get_description($dc, $do, $data_type = null)
    {
        $desc = "";
        if($val = $dc->description) $desc .= trim($val);
        else return $desc;
        $desc = self::clean_desc($desc);
        $desc = self::set_desc_separator($desc);
        if($data_type == "image")
        {
            $agent_types = array("photographer", "author", "creator", "composer", "source", "publisher", "compiler", "editor", "project", "recorder", "animator", "illustrator", "director");
            foreach($agent_types as $agent_type)
            {
                if($val = self::get_agent_by_type($do->agent, $agent_type)) $desc .= ucfirst($agent_type) . ": " . implode(";", $val) . ". ";
            }
        }
        return $desc;
    }
    
    private function set_desc_separator($desc)
    {
        if(!$desc) return $desc;
        if(substr($desc, -1) == ".") return $desc .= " ";
        else return $desc .= ". ";
    }
    
    private function get_agent_by_type($agents, $agent_type)
    {
        $agent_names = array();
        foreach($agents as $agent)
        {
            if(is_numeric(stripos($agent{"role"}, $agent_type))) $agent_names[(string) $agent] = '';
        }
        return array_keys($agent_names);
    }
    
    private function get_creator($dcterms, $do, $data_type = null)
    {
        $creator = "";
        if($val = $dcterms->rightsHolder) $creator .= $val;
        else
        {
            if($data_type == "image") $agent_types = array("photographer", "author", "creator", "composer", "source", "publisher", "compiler", "editor", "project", "recorder", "animator", "illustrator", "director");
            else                      $agent_types = array("author", "creator", "composer", "source", "publisher", "compiler", "editor", "project", "recorder", "photographer", "animator", "illustrator", "director");
            foreach($agent_types as $agent_type)
            {
                $agent_names = array();
                $agent_names = self::get_agent_by_type($do->agent, $agent_type);
                if($agent_names) break;
            }
            $creator = implode(",", $agent_names);
        }
        if(!$creator && !is_numeric(stripos($do->license, "publicdomain")))
        {
            echo "\n[investigate: no creator and not public domain!]\n";
            print_r($agent_names);
        }
        return $creator;
    }
    
    private function get_mediaURL($dc, $do)
    {
        if($dc->source && $do->mediaURL)
        {
            $options = $this->download_options;
            $options['expire_seconds'] = false; // lookup to LifeDesk page should not expire unless requested to have a fresh export to scratchpad
            if($html = Functions::lookup_with_cache($dc->source, $options))
            {
                $image_extension = self::get_image_extension($do->mimeType);
                $str = ".preview." . $image_extension;
                if(preg_match("/class=\"jqzoom\"><img src=\"(.*?)" . $str . "/ims", $html, $arr))
                {
                    $path = $arr[1] . ".$image_extension";
                    $parts = pathinfo($path);
                    return strtolower($parts["basename"]);
                }
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
    
    function load_zip_contents($zip_file)
    {
        $temp_dir = create_temp_dir() . "/";
        if($file_contents = Functions::lookup_with_cache($zip_file, $this->download_options))
        {
            $parts = pathinfo($zip_file);
            $temp_file_path = $temp_dir . "/" . $parts["basename"];
            $TMP = fopen($temp_file_path, "w");
            fwrite($TMP, $file_contents);
            fclose($TMP);
            
            if(is_numeric(stripos($zip_file, ".tar.gz"))) $output = shell_exec("tar -xzf $temp_file_path -C $temp_dir");
            elseif(is_numeric(stripos($zip_file, ".xml.gz"))) $output = shell_exec("gzip -d $temp_file_path -q "); //$temp_dir
            
            if(!file_exists($temp_dir . "/eol-partnership.xml"))
            {
                $temp_dir = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($temp_dir . "/eol-partnership.xml")) return false;
            }
            $this->text_path["eol_xml"] = $temp_dir . "eol-partnership.xml";
            // initialize tab-delimited text files to be used
            $this->text_path["image"] = $temp_dir . "file_importer_image_xls.txt";
            $this->text_path["text"] = $temp_dir . "TEMPLATE-import_into_taxon_description_xls.txt";
            $this->text_path["bibtex"] = $temp_dir . "Biblio-Bibtex.bib";
            $this->text_path["biblio"] = $temp_dir . "filled_node_importer_biblio_xls.txt";
            print_r($this->text_path);
            return $this->text_path;
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return false;
        }
    }

    public function convert_tab_to_xls($params)
    {
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php';
        $destination_folder = create_temp_dir() . "/";
        $types = array("image", "text");
        if(@$this->file_importer_xls["biblio"]) $types[] = "biblio";
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
        if(@$params["bibtex_file"])
        {
            $parts = pathinfo($this->text_path["bibtex"]);
            copy($this->text_path["bibtex"], $destination_folder . $parts["basename"]);
        }
        // compress export files
        $command_line = "tar -czf " . DOC_ROOT . "/tmp/" . $params["name"] . "_LD_to_Scratchpad_export.tar.gz --directory=" . $destination_folder . " .";
        $output = shell_exec($command_line);
        recursive_rmdir($destination_folder);
    }

    public function convert_bibtex_file($file)
    {
        if($contents = Functions::lookup_with_cache($file, $this->download_options))
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
                                $this->booklet_taxa_list[$id] = self::enclose_array_values_with_quotes($this->booklet_taxa_list[$id]);
                                echo "\n[$item]\n";
                                $item = str_replace("}", ", " . implode(", ", $this->booklet_taxa_list[$id])."}", $item);
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
                        $this->booklet_taxa_list[$id] = self::enclose_array_values_with_quotes($this->booklet_taxa_list[$id]);
                        $rec[] = "keywords = {" . implode(", ", $this->booklet_taxa_list[$id]) . "}" . chr(10) . "}" . "\n";
                        $rec[$i-1] = str_replace(chr(10), "", $rec[$i-1]);
                        $rec[$i-1] = substr($rec[$i-1], 0, strlen($rec[$i-1])-1);
                        $rec[$i-1] .= "," . chr(10);
                        // echo "\n to be inserted: [" . $rec[$i-1] . "]\n";
                    }
                    // save to text file
                    $rec = implode(chr(9), $rec);
                    self::save_to_dump($rec, $this->text_path["bibtex"], ""); // no line separator, deliberately done for bibtex purposes
                }
            }
        }
        else echo "\n investigate: [$file] not found... \n";
    }

    private function get_title($rec)
    {
        foreach($rec as $item)
        {
            if(is_numeric(stripos($item, "title =")))
            {
                $str = trim(str_ireplace(array("title = {"), "", $item));
                if(substr($str, -1) == ",") $str = trim(substr($str, 0, strlen($str)-1));
                if(substr($str, -1) == "}") $str = trim(substr($str, 0, strlen($str)-1));
                return $str;
            }
        }
        return '';
    }
    
    private function enclose_array_values_with_quotes($arr)
    {
        $final = array_keys($arr);
        $i = 0;
        foreach($final as $name)
        {
            $final[$i] = '"' . $name . '"';
            $i++;
        }
        return $final;
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

    private function clean_desc($desc)
    {
        $desc = str_ireplace(array("<em></em>", "<p>.</p>"), "", $desc);
        $desc = strip_tags($desc, "<br><p><i><em>");
        return $desc;
    }

}
?>