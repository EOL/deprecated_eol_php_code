<?php
namespace php_active_record;
// connector: [lifedesk_export]
class LifeDeskToScratchpadAPI
{
    function __construct()
    {
        $this->download_options = array('resource_id' => 'LD_Scratchpad', 'download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 2, 'delay_in_minutes' => 2); // 15mins timeout
        $this->download_options["expire_seconds"] = false;// normal operation is 0, needs to be 0; // zip file, bibtex
        /* Just set to false Feb 7, 2018 since there is no more Scratchpad conversion going on. and load_zip_contents() is being used elsewhere. e.g. LifeDeskToEOLAPI.php */
        
        /*
        $this->file_importer_xls["image"] = "http://localhost/cp/LD2Scratchpad/templates/file_importer_image_xls.xls";
        $this->file_importer_xls["text"] = "http://localhost/cp/LD2Scratchpad/templates/TEMPLATE-import_into_taxon_description_xls.xls";
        $this->file_importer_xls["parent_child"]= "http://localhost/cp/LD2Scratchpad/templates/template_parent_child.xls";
        */
        $this->file_importer_xls["image"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/templates/file_importer_image_xls.xls";
        $this->file_importer_xls["text"] = "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/templates/TEMPLATE-import_into_taxon_description_xls.xls";
        $this->file_importer_xls["parent_child"]= "https://dl.dropboxusercontent.com/u/7597512/LifeDesk_exports/templates/template_parent_child.xls";
        
        $this->spreadsheet_options = array("cache" => 1, "timeout" => 3600, "file_extension" => "xls", 'download_attempts' => 2, 'delay_in_minutes' => 2, 'resource_id' => 'LD_Scratchpad');
        $this->spreadsheet_options["expire_seconds"] = 0; // false => won't expire; 0 => expires now
        
        $this->LD_image_source_expire_seconds = false;  // false => won't expire; 0 => expires now
        $this->LD_nodes_pages_expire_seconds = false;   // false => won't expire; 0 => expires now
    }

    function export_lifedesk_to_scratchpad($params)
    {
        print_r($params);
        $this->text_path = array();
        $this->booklet_taxa_list = array();
        $this->booklet_title_list = array();
        $this->lifedesk_fields = array();
        $this->scratchpad_image_taxon_list = array();
        $this->used_GUID = array();
        $this->taxon_description = array();
        $this->creators_for_pages = array();    // used when adding text from LD taxon pages
        $this->biblio_taxa = array();           // used when searching which taxa are related to which biblio, searches are in the taxon pages.
        $this->taxonomy_biblio = array();       // used when searching which biblio id (NID) belong to a taxon
        
        if($val = @$params["scratchpad_biblio"]) $this->file_importer_xls["biblio"] = $val;
        if($val = @$params["scratchpad_taxonomy"]) $this->file_importer_xls["taxonomy"] = $val;
        
        if(self::load_zip_contents($params["lifedesk"]))
        {
            self::prepare_tab_delimited_text_files();
            if($val = $params["scratchpad_images"]) self::get_scratchpad_image_taxon_list($val, "0"); // "0" here means to open the 1st worksheet from the spreadsheet
            self::parse_eol_xml();
            self::get_taxon_descriptions_from_LD_taxon_pages($params);  //new
            self::get_images_from_LD_image_gallery($params);            //new
            if($val = @$params["scratchpad_images"]) self::add_images_without_taxa_to_export_file($val);
        }

        unset($this->used_GUID);
        unset($this->scratchpad_image_taxon_list);
        unset($this->creators_for_pages);
        
        self::initialize_dump_file($this->text_path["bibtex"]);
        if($val = @$params["bibtex_file"]) self::convert_bibtex_file($val);
        if($val = @$params["scratchpad_biblio"]) self::fill_up_biblio_spreadsheet($val);
        if($val = @$params["scratchpad_taxonomy"]) self::fill_up_taxonomy_spreadsheet($val);

        unset($this->taxon_description);
        unset($this->booklet_taxa_list);
        unset($this->booklet_title_list);
        unset($this->lifedesk_fields);
        unset($this->biblio_taxa);
        unset($this->taxonomy_biblio);

        self::convert_tab_to_xls($params);
        // remove temp dir
        $parts = pathinfo($this->text_path["eol_xml"]);
        recursive_rmdir($parts["dirname"]); //debug - comment if you want to see: images_not_in_xls.txt
        debug("\n temporary directory removed: " . $parts["dirname"]);
        print_r($params);
        if($val = @$this->debug["undefined subjects"])
        {
            echo "\n undefined subjects: ";
            print_r($val);
            exit;
        }
    }

    private function fill_up_biblio_spreadsheet($spreadsheet)
    {
        $this->biblio_taxa = self::format_pipe_values($this->biblio_taxa);
        $headers = self::get_column_headers($spreadsheet);
        if($arr = self::convert_spreadsheet($spreadsheet, 0))
        {
            print "\n spreadsheet: " . count($arr[$headers[0]]) . "\n";
            $i = 0;
            foreach($arr['GUID'] as $guid)
            {
                $scinames = self::get_scinames_from_booklet_title_list($arr['Title'][$i]);
                if($scinames2 = @$this->biblio_taxa[$arr['Title'][$i]])
                {
                    if($scinames) $scinames .= "|" . $scinames2;
                    else $scinames = $scinames2;
                }
                
                // will try when title is stripped of tags
                if(!$scinames)
                {
                    $scinames = self::get_scinames_from_booklet_title_list(strip_tags($arr['Title'][$i]));
                    if($scinames2 = @$this->biblio_taxa[strip_tags($arr['Title'][$i])])
                    {
                        if($scinames) $scinames .= "|" . $scinames2;
                        else $scinames = $scinames2;
                    }
                }

                $scinames = self::make_unique_pipe_delimited_string($scinames);

                $rec = array();
                foreach($headers as $header)
                {
                    if($header == "Taxonomic name (Name)") $rec[$header] = $scinames;
                    else $rec[$header] = $arr[$header][$i];
                }
                if($rec) self::save_to_template($rec, $this->text_path["biblio"], "biblio");
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

    private function make_unique_pipe_delimited_string($string)
    {
        $arr = explode("|", $string);
        $arr = array_unique($arr);
        return implode("|", $arr);
    }
    
    private function format_pipe_values($records)
    {
        foreach($records as $key => $values) $records[$key] = implode("|", array_unique($values));
        return $records;
    }

    private function get_row_from_spreadsheet($spreadsheet, $header)
    {
        if($spreadsheet)
        {
            if($arr = self::convert_spreadsheet($spreadsheet, 0)) return $arr[$header];
        }
        return array();
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
        $orig_title = $title;
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
            if($biblio_title == $orig_title) return implode("|", $scinames);
        }
        foreach($this->booklet_title_list as $biblio_title => $scinames)
        {
            if($title == substr($biblio_title, 0, strlen($title))) return implode("|", $scinames);
        }
        if($scinames = self::get_scinames_from_taxon_description($title)) return $scinames;
        return "";
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
        if(@$this->file_importer_xls["taxonomy"]) $types[] = "taxonomy";
        
        foreach($types as $type)
        {
            self::initialize_dump_file($this->text_path[$type]);
            $headers = self::get_column_headers($this->file_importer_xls[$type]);
            if($type == "text")
            {
                if(!in_array("Taxonomy", $headers)) $headers[] = "Taxonomy";
                if(!in_array("TypeInformation", $headers)) $headers[] = "TypeInformation";
                if(!in_array("Key", $headers)) $headers[] = "Key";
                if(!in_array("Notes", $headers)) $headers[] = "Notes";
                if(!in_array("Citation", $headers)) $headers[] = "Citation";
                if(!in_array("Bibliography", $headers)) $headers[] = "Bibliography";
                if(!in_array("References", $headers)) $headers[] = "References";
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
            $headers = array_keys($arr);
            $i = 0;
            foreach($arr["GUID"] as $guid)
            {
                $filename = strtolower($arr["Filename"][$i]);
                /*
                $this->scratchpad_image_taxon_list[$filename]["guid"]        = self::clean_str($guid);
                $this->scratchpad_image_taxon_list[$filename]["taxon"]       = self::clean_str($arr["Taxonomic name (Name)"][$i]);
                // we are not sure if these 3 fiels will be provided by the scratchpad file
                $this->scratchpad_image_taxon_list[$filename]["license"]     = self::clean_str($arr["Licence"][$i]);
                $this->scratchpad_image_taxon_list[$filename]["description"] = self::clean_str($arr["Description"][$i]);
                $this->scratchpad_image_taxon_list[$filename]["creator"]     = self::clean_str($arr["Creator"][$i]);
                */
                foreach($headers as $header) $this->scratchpad_image_taxon_list[$filename][$header] = self::clean_str($arr[$header][$i]);
                $i++;
            }
        }
        /*
        [guid] => 69bb0ba4-2294-4d09-9dd8-5c2e7b6e1f5b
        [taxon] =>
        [license] => Attribution CC BY
        [description] =>
        [creator] =>
        */
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
    
    public function convert_spreadsheet($spreadsheet, $worksheet = null, $spreadsheet_options = array())
    {
        if(!$spreadsheet_options) $spreadsheet_options = $this->spreadsheet_options;
        require_library('XLSParser');
        $parser = new XLSParser();
        /* debug only
        $classes = get_declared_classes();
        print_r($classes);
        */
        if($path = Functions::save_remote_file_to_local($spreadsheet, $spreadsheet_options))
        {
            echo "\nlocal spreadsheet path: [$path]\n"; //debug
            $arr = $parser->convert_sheet_to_array($path, $worksheet);
            unlink($path);
            return $arr;
        }
        else echo "\n [$spreadsheet] unavailable! \n";
        return false;
    }
    
    private function load_xml()
    {
        $xml_str = file_get_contents($this->text_path["eol_xml"]);
        $xml_str = str_replace("", "", $xml_str);
        if($xml = simplexml_load_string($xml_str)) return $xml;
        return false;
    }
    
    private function parse_eol_xml()
    {
        // for stats
        $parts = pathinfo($this->text_path["eol_xml"]);
        $dump_file = $parts["dirname"] . "/images_not_in_xls.txt";

        if(!$xml = self::load_xml()) exit("\nLifeDesk XML is invalid\n\n");

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
                    if(preg_match("/>(.*?)_xxx/ims", $arr[1]."_xxx", $arr))
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
                    
                    if($this->scratchpad_image_taxon_list)
                    {
                        if($guid = @$this->scratchpad_image_taxon_list[$filename]["GUID"])
                        {
                            $rec["GUID"] = $guid;
                            $this->used_GUID[$guid] = '';
                        }
                        else // this may not be needed anymore...
                        {
                            // let us try exchanging jpeg to jpg and vice versa
                            if(is_numeric(stripos($filename, ".jpg")))
                            {
                                $filename = str_ireplace(".jpg", ".jpeg", $filename);
                                if($guid = @$this->scratchpad_image_taxon_list[$filename]["GUID"])
                                {
                                    $rec["GUID"] = $guid;
                                    $this->used_GUID[$guid] = '';
                                }
                            }
                            elseif(is_numeric(stripos($filename, ".jpeg")))
                            {
                                $filename = str_ireplace(".jpeg", ".jpg", $filename);
                                if($guid = @$this->scratchpad_image_taxon_list[$filename]["GUID"])
                                {
                                    $rec["GUID"] = $guid;
                                    $this->used_GUID[$guid] = '';
                                }
                            }
                            else
                            {
                                echo "\n alert: no guid [$filename][$t_dc2->identifier]\n"; // this means that an image file in XML is not found in the image XLS submitted by SPG
                                self::save_to_dump($sciname . "\t" . $t_dc2->identifier . "\t" . $filename, $dump_file);
                                // exit("\n-stopped- Will need to notify SPG\n");
                            }
                        }
                    }
                    // print_r($rec);
                    // [Taxonomic name (Name)] => Olivella tehuelchana (Orbigny, 1839)
                    // [Filename] => oliva_tehuelchana_tf.jpg
                    // [Licence] => Public Domain
                    // [Description] => Type figures of Oliva tehuelchana Orbigny, 1839. Recent.<br>. Photographer: Original images. Publisher: Voskuil, Ron. 
                    // [Creator] => Original images
                    // [GUID] => 43d9a637-f773-4ab7-b16f-d33356b19154
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
    
    private function get_taxon_descriptions_from_LD_taxon_pages($params)
    {
        // for biblio spreadsheet
        $biblios = self::get_row_from_spreadsheet(@$params['scratchpad_biblio'], "Title");
        /* headers = self::get_column_headers($this->file_importer_xls["text"]); */
        $headers = $this->lifedesk_fields["text"];
        
        // for stats
        $parts = pathinfo($this->text_path["eol_xml"]);
        $dump_file = $parts["dirname"] . "/images_not_in_xls2.txt";
        
        $options = $this->download_options;
        $options['expire_seconds'] = $this->LD_nodes_pages_expire_seconds; // lookup to LifeDesk page should not expire unless requested to have a fresh export to scratchpad
        $options['download_wait_time'] = 2000000;

        $topics = array();
        $records = array();
        //start accessing individual taxon page in LD
        if($pages = self::get_nodes_or_pages("taxa", $params, $options))
        {
            $total = count($pages);
            $i = 0;
            foreach($pages as $page)
            {
                $i++;
                if(($i % 100) == 0) echo "\n$i of $total --- page: [$page] " . $params["name"];
                $sciname = false;
                // <h3 class="taxonpage">Distribution</h3>
                if($html = Functions::lookup_with_cache("http://" . $params["name"] . ".lifedesks.org/pages/$page", $options))
                {
                    /* getting just topics -- working
                    if(preg_match_all("/<h3 class=\"taxonpage\">(.*?)<\/h3>/ims", $html, $arr))
                    {
                        $topics = array_merge($topics, $arr[1]);
                        $topics = array_unique($topics);
                    }
                    */
                    
                    // /*
                    $rec = array();
                    $html = str_ireplace('<div class="taxonpage-children">', '<div class="sub-chapter"><div class="taxonpage-children">', $html);
                    if(preg_match_all("/<h3 class=\"taxonpage\">(.*?)<div class=\"sub-chapter\">/ims", $html, $arr))
                    {
                        $sections = $arr[1];
                        foreach($sections as $section)
                        {
                            $str = strip_tags($section, "<p><em><h3>");
                            $str = str_ireplace(array('Comment (0)',"\n"), '', $str);
                            $str = Functions::remove_whitespace($str);
                            $parts = explode("</h3>", $str);
                            $parts = array_map('trim', $parts);
                            $rec[$parts[0]] = $parts[1];
                        }
                    }
                    // else echo "\nno articles\n"; working...
                    if(preg_match("/<h1 class=\"taxonpage\">(.*?)<\/h1>/ims", $html, $arr)) // getting sciname
                    {
                        $sciname = trim(strip_tags($arr[1]));
                        $records[$sciname]["articles"] = $rec; // assigning objects to sciname
                        $records[$sciname]["page"] = $page; // assigning page to sciname
                    }
                    // */
                    
                    // /*
                    // DATA-1552
                    if(@$params['scratchpad_biblio'] && $sciname)
                    {
                        // if(preg_match("/<h2 class=\"taxonpage\">References<\/h2>(.*?)title=\"About this site\">About this site<\/a>/ims", $html, $arr))
                        if(true)
                        {
                            // $html = $arr[1];
                            foreach($biblios as $biblio)
                            {
                                if(is_numeric(stripos($html, $biblio))) $this->biblio_taxa[$biblio][] = $sciname;
                                elseif(is_numeric(stripos(strip_tags($html), $biblio))) $this->biblio_taxa[$biblio][] = $sciname;
                                elseif(is_numeric(stripos($html, strip_tags($biblio)))) $this->biblio_taxa[$biblio][] = $sciname;
                                /*
                                elseif(is_numeric(stripos(strip_tags($html), strip_tags($biblio)))) exit("\ccc1\n" . $params["name"]);
                                else // this may not be needed anymore...
                                {
                                    $html = str_ireplace(array("\n"), "", $html);
                                    if(is_numeric(stripos($html, $biblio))) exit("\naaa\n" . $params["name"]);
                                    elseif(is_numeric(stripos(strip_tags($html), $biblio))) exit("\nbbb\n" . $params["name"]);
                                    elseif(is_numeric(stripos($html, strip_tags($biblio)))) exit("\nccc\n" . $params["name"]);
                                    elseif(is_numeric(stripos(strip_tags($html), strip_tags($biblio)))) exit("\nddd\n" . $params["name"]);
                                }
                                */
                            }
                        }
                    }
                    // */
                    
                    // /*
                    // DATA-1554
                    if(@$params['scratchpad_taxonomy'] && $sciname)
                    {
                        if(preg_match_all("/biblio\/view\/(.*?)\"/ims", $html, $arr))
                        {
                            if($val = @$this->taxonomy_biblio[$sciname]) $this->taxonomy_biblio[$sciname] = array_merge($val, $arr[1]);
                            else                                         $this->taxonomy_biblio[$sciname] = $arr[1];
                        }
                    }
                    // */
                    
                }
            } //foreach page
        }

        $topics = array_unique($topics);
        if($records) self::save_taxon_articles_to_text($records, $headers);
    }
    
    private function save_taxon_articles_to_text($records, $headers)
    {
        foreach($records as $sciname => $rec)
        {
            foreach($rec["articles"] as $topic => $text)
            {
                if($topic == "Risk Statement")                  $topic = "Risk statement";
                elseif($topic == "Molecular Biology")           $topic = "Molecular biology";
                elseif(in_array($topic, array("Taxon Biology", "Overview"))) $topic = "Taxon biology";
                elseif($topic == "Life Cycle")                  $topic = "Life cycle";
                elseif($topic == "Life Expectancy")             $topic = "Life expectancy";
                elseif($topic == "Population Biology")          $topic = "Population biology";
                elseif($topic == "Trophic Strategy")            $topic = "Trophic strategy";
                elseif(in_array($topic, array("Body length", "General comments", "Original description", "Original Published Description", "Description générale", "Summary", "Detailed Description", "Liew Manuscript Discussion", "Liew Manuscript Description", "General Description", "Description", "Synoptic Description", "Original description(s)", "Technical Description", "Records &amp; General Information on the taxon", "The information shown herein was extracted from the following publications"))) $topic = "General description";
                elseif(in_array($topic, array("Look Alikes", "Looks Alike", "Similar Species", "Comparisons"))) $topic = "Look alikes";
                elseif(in_array($topic, array("Common names", "Synonomies", "Concepts and synonymy", "Common and Local Names", "Taxonomic History", "Local Names", "Taxonomic Notes", "Taxonomic Discussion", "Floral Records", "notes for taxonomic status", "Name-Bearing Type", "Taxonomic Remarks", "Etymology", "Nomenclature and Synonymy", "Synonyms", "Synonymy", "Abbreviations", "Nomenclature", "Additional species, genera..."))) $topic = "Taxonomy";
                elseif(in_array($topic, array("Type", "Typification Details", "Specimen information", "Type Citation", "Specimens Examined", "Type Data", "Type locality", "Type Information", "Type material location", "Liew Manuscript Type material", "Type material", "Type Material", "Type Locality", "Type specimens", "Type species, type genus..."))) $topic = "TypeInformation";
                elseif(in_array($topic, array("Conservation Actions and Management", "Conservation", "Conservation Status", "Liew Manuscript Conservation status", "IUCN Red List Category and Justification of Conservation Status"))) $topic = "Conservation status";
                elseif(in_array($topic, array("Latin Diagnosis", "Diagnostic Features", "Liew Manuscript Diagnosis", "Diagnostic Description", "Differential diagnosis", "Diagnosis"))) $topic = "Diagnostic description";
                elseif(in_array($topic, array("Foodplant Associations", "Parasitoid Associations", "Predator Associations", "Host species", "Prey", "Pollination Ecology", "Diseases and Parasites", "Enemies and parasitoids", "Known Prey Organisms", "Known Predators", "Known host plants"))) $topic = "Associations";
                elseif(in_array($topic, array("Data sources", "Fishery information", "Gaps in our knowledge", "Related literature", "Useful Links", "Other Remarks", "Additional Resources", "Other Databases", "Additional links", "Liew Manuscript Other material examined", "General notes", "Other material", "Additional Information", "Systematic Discussion", "Material Examined", "Remarks"))) $topic = "Notes";
                elseif(in_array($topic, array("Color", "Egg, larval, pupal and adult morphology", "Tadpole morphology", "External Appearance", "Sensory Organs", "Proboscis and Rhynchocoel System", "Blood Vascular System", "Excretory System", "Nervous System", "Body Wall", "Glands", "morphological description", "Osteology"))) $topic = "Morphology";
                elseif(in_array($topic, array("Digestive System", "Herbivores"))) $topic = "Trophic strategy";
                elseif(in_array($topic, array("Phenology", "Asexual reproduction", "Reproduction and Development", "Reproductive System"))) $topic = "Reproduction";
                elseif(in_array($topic, array("Life Habit", "Regeneration", "Reproduction and Life History", "Adult behavior", "Larval morphology and behavior", "Nesting behavior", "Nestling description", "Nesting Biology", "Behavior", "Repository", "Adult chaetotaxy", "Advertisement Call", "Modes and Mechanisms of Locomotion", "Activity and Special Behaviors"))) $topic = "Behaviour";
                elseif(in_array($topic, array("Life History", "Metamorphosis"))) $topic = "Life cycle";
                elseif(in_array($topic, array("Distribution in the Plot", "Global Distribution", "Altitudinal Range", "Geographic Range", "Introduction", "Distribution and Habitat", "Occurrence", "Liew Manuscript Distribution", "Known distribution"))) $topic = "Distribution";
                elseif(in_array($topic, array("Natural History", "Ecology and Behaviour", "Urban Ecology"))) $topic = "Ecology";
                elseif(in_array($topic, array("Systematics", "Chemistry", "Phylogenetics", "Systematics and Phylogenetics", "Phylogenetic Relationships"))) $topic = "Genetics";
                elseif(in_array($topic, array("Toxicity", "Research Use"))) $topic = "Uses";
                elseif(in_array($topic, array("Please cite this taxon page as"))) $topic = "Citation";
                elseif(in_array($topic, array("Description compiled by", "Edited by"))) $topic = "Creator";
                elseif(in_array($topic, array("Electric Organ Discharge", "Egg description"))) $topic = "Biology";
                elseif(in_array($topic, array("Depth range", "Palynology", "Habitat and Ecology", "Nest description", "Nesting habitat", "Habitat and Host Associations"))) $topic = "Habitat";
                elseif(in_array($topic, array("Seasonality"))) $topic = "Cyclicity";
                elseif(in_array($topic, array("Key to Species", "Systematics and Identification"))) $topic = "Key";
                elseif(in_array($topic, array("Dry weight (biomass)"))) $topic = "Size";

                if(!in_array($topic, $headers))
                {
                    $this->debug["undefined subjects"][$topic] = '';
                    continue;
                }
                if($topic && $text && $sciname)
                {
                    $rekord = array();
                    $rekord["Taxonomic name (Name)"] = $sciname;
                    $rekord["Creator"] = $this->creators_for_pages[$rec["page"]];
                    $rekord[$topic] = $text;
                    self::save_to_template($rekord, $this->text_path["text"], "text");
                }
            }
        }
    }

    /*
        [4] => General description
        [5] => Biology
        [6] => Media (Filename)
        [7] => Media (URL)
        [8] => Conservation status
        [9] => Legislation
        [10] => Management
        [11] => Procedures
        [12] => Threats
        [13] => Trends
        [14] => Behaviour
        [15] => Cytology
        [16] => Diagnostic description
        [17] => Genetics
        [18] => Growth
        [19] => Look alikes
        [20] => Molecular biology
        [21] => Morphology
        [22] => Physiology
        [23] => Size
        [24] => Taxon biology
        [25] => Evolution
        [26] => Phylogeny
        [27] => Map
        [28] => Associations
        [29] => Cyclicity
        [30] => Dispersal
        [31] => Distribution
        [32] => Ecology
        [33] => Habitat
        [34] => Life cycle
        [35] => Life expectancy
        [36] => Migration
        [37] => Trophic strategy
        [38] => Population biology
        [39] => Reproduction
        [40] => Diseases
        [41] => Risk statement
        [42] => Uses
        [43] => Taxonomy
        [44] => TypeInformation
        [45] => Creator
    */

    private function get_images_from_LD_image_gallery($params)
    {
        // for stats
        $parts = pathinfo($this->text_path["eol_xml"]);
        $dump_file = $parts["dirname"] . "/images_not_in_xls2.txt";
        
        // [lifedesk]           => http://localhost/cp/LD2Scratchpad/olivirv/eol-partnership.xml.gz
        // [bibtex_file]        => http://localhost/cp/LD2Scratchpad/olivirv/Biblio-Bibtex.bib
        // [scratchpad_images]  => http://localhost/cp/LD2Scratchpad/olivirv/file_importer_image_xls.xls
        // [name]               => olivirv
        // [scratchpad_biblio]  => http://localhost/cp/LD2Scratchpad/olivirv/node_importer_biblio_xls.xls

        $options = $this->download_options;
        $options['expire_seconds'] = $this->LD_nodes_pages_expire_seconds; // lookup to LifeDesk page should not expire unless requested to have a fresh export to scratchpad
        $options['download_wait_time'] = 2000000;

        //start accessing individual image page in LD
        if($nodes = self::get_nodes_or_pages("image", $params, $options))
        {
            echo "\nnodes: " . count($nodes) . "\n";
            foreach($nodes as $node)
            {
                $proceed_save = false;
                $rec = array();
                if($html = Functions::lookup_with_cache("http://" . $params["name"] . ".lifedesks.org/node/$node", $options))
                {
                    /*
                    [Taxonomic name (Name)] => Olivella tehuelchana (Orbigny, 1839)
                    [Filename] => oliva_tehuelchana_tf.jpg
                    [Licence] => Public Domain
                    [Description] => Type figures of Oliva tehuelchana Orbigny, 1839. Recent.<br>. Photographer: Original images. Publisher: Voskuil, Ron. 
                    [Creator] => Original images
                    [GUID] => 43d9a637-f773-4ab7-b16f-d33356b19154
                    */

                    //Taxonomic name (Name)
                    //<h3>Taxa</h3><ul class="vocabulary-list"><li class="first last"><a href="/pages/4028">Ancillaria conus Andrzeiovski, 1833</a></li>
                    if(preg_match("/<h3>Taxa<\/h3>(.*?)<\/li>/ims", $html, $arr)) $rec["Taxonomic name (Name)"] = strip_tags($arr[1]);
                    
                    //Filename: ancillaria_conus__and_tf.preview.jpg
                    $str = ".preview.";// . $image_extension;
                    if(preg_match("/class=\"jqzoom\"><img src=\"(.*?)\"/ims", $html, $arr))
                    {
                        $path = $arr[1];
                        $parts = pathinfo($path);
                        $rec["Filename"] = str_replace(".preview.", ".", strtolower($parts["basename"]));
                    }
                    
                    //Licence
                    //<strong>Photographer:</strong> Original image</span><span><a href="http://creativecommons.org/licenses/publicdomain/3.0/"
                    if(preg_match("/href=\"http:\/\/creativecommons.org(.*?)\"/ims", $html, $arr)) $rec["Licence"] = self::get_license($arr[1]);
                    
                    //Description
                    if(preg_match("/<title>(.*?)<\/title>/ims", $html, $arr)) $rec["Description"] = $arr[1];
                    
                    //Creator
                    if(preg_match("/<strong>Photographer:<\/strong>(.*?)<\/span>/ims", $html, $arr)) $rec["Creator"] = $arr[1];
                    
                    //GUID
                    if($guid = @$this->scratchpad_image_taxon_list[$rec["Filename"]]["GUID"])
                    {
                        $rec["GUID"] = $guid;
                        if(!isset($this->used_GUID[$guid]))
                        {
                            $this->used_GUID[$guid] = '';
                            $proceed_save = true;
                        }
                    }
                    else
                    {
                        if(isset($rec["Filename"]) && isset($rec["Taxonomic name (Name)"]))
                        {
                            // this means that an image file in XML is not found in the image XLS submitted by SPG
                            self::save_to_dump($rec["Taxonomic name (Name)"] . "\t" . "" . "\t" . $rec["Filename"], $dump_file);
                            echo "\n no guid node:[$node] \n";
                            // print_r($rec); // exit("\n-stopped- NO GUID: Will need to notify SPG\n");
                        }
                        else echo "\n blank filename in the page \n"; //e.g. http://africanamphibians.lifedesks.org/node/807
                    }
                    if($proceed_save)
                    {
                        $rec = array_map('trim', $rec);
                        self::save_to_template($rec, $this->text_path["image"], "image");
                    }
                }
            }// for loop
        }
    }
    
    private function get_nodes_or_pages($what, $params, $options)
    {
        if($what == "image") $var = "node";
        if($what == "taxa") $var = "pages";

        $nodes = array();
        $url = "http://" . $params["name"] . ".lifedesks.org/$what";
        if($html = Functions::lookup_with_cache($url, $options))
        {
            // "pager-last last"><a href="/image?page=81"
            if(preg_match("/\"pager-last last\"><a href=\"\/" . $what . "\?page=(.*?)\"/ims", $html, $arr)) $last_page = $arr[1];
            else $last_page = 0;

            echo "\n last page: [$last_page]\n";
            $page = 0;
            for($page = 0; $page <= $last_page; $page++)
            {
                if($page > 0) $url = "http://" . $params["name"] . ".lifedesks.org/" . $what . "?page=" . $page;
                if($html = Functions::lookup_with_cache($url, $options)) // start accessing pages
                {
                    if($what == "image")
                    {
                        // <span class="field-content"><a href="/node/4485"
                        if(preg_match_all("/<span class=\"field-content\"><a href=\"\/" . $var . "\/(.*?)\"/ims", $html, $arr)) $nodes = array_merge($nodes, array_unique($arr[1]));
                    }
                    elseif($what == "taxa")
                    {
                        if(preg_match("/<tbody>(.*?)<\/tbody>/ims", $html, $arr))
                        {
                            $html = str_ireplace(array(' class="odd"', ' class="even"', ' class="active"'), "", $arr[1]);
                            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr))
                            {
                                $rows = $arr[1];
                                foreach($rows as $row)
                                {
                                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $arr))
                                    {
                                        $tds = $arr[1];
                                        // <span class="taxon-list taxon_description_missing">&nbsp;</span>
                                        if(count($tds) != 4)
                                        {
                                            if(in_array($params["name"], array('porifera'))) continue;
                                            else exit("\nInvestigate: wrong no. of columns\n");
                                        }
                                        $condition = is_numeric(stripos($tds[1], 'class="taxon-list taxon_description"'));
                                        if(@$params['scratchpad_biblio'])   $condition = true; // get all rows --- old value -> $condition = is_numeric(stripos($tds[1], 'class="taxon-list taxon_description"')) || is_numeric(stripos($tds[1], 'class="taxon-list biblio"'));
                                        if(@$params['scratchpad_taxonomy']) $condition = true; // get all rows
                                        if($condition)
                                        {
                                            // <a href="/pages/3095">
                                            if(preg_match("/<a href=\"\/pages\/(.*?)\"/ims", $tds[0], $arr))
                                            {
                                                $nodes[] = $arr[1];
                                                $this->creators_for_pages[$arr[1]] = self::get_creator_from_column($tds[2]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // break; //debug
            }
        }
        $nodes = array_unique($nodes);
        return $nodes;
    }
    
    private function get_creator_from_column($str)
    {
        // title="View user profile.">Voskuil, Ron</a>
        if(preg_match("/title=\"View user profile.\">(.*?)<\/a>/ims", $str, $arr)) return trim($arr[1]);
        return false;
    }
    
    private function add_images_without_taxa_to_export_file($spreadsheet)
    {
        $headers = self::get_column_headers($spreadsheet);
        foreach($this->scratchpad_image_taxon_list as $filename => $value)
        {
            $rec = array();
            if(!isset($this->used_GUID[$value["GUID"]]))
            {
                /*
                $rec["GUID"]                    = $value["guid"];
                $rec["Filename"]                = $filename;
                $rec["Taxonomic name (Name)"]   = $value["taxon"];
                $rec["Licence"]     = @$value["license"];
                $rec["Description"] = @$value["description"];
                $rec["Creator"]     = @$value["creator"];
                */
                foreach($headers as $header) $rec[$header] = @$value[$header];
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
            $options['expire_seconds'] = $this->LD_image_source_expire_seconds; // lookup to LifeDesk page should not expire unless requested to have a fresh export to scratchpad
            if($html = Functions::lookup_with_cache($dc->source, $options))
            {
                if(preg_match("/\.preview\.(.*?)\"/ims", $html, $arr)) $image_extension = $arr[1];
                else $image_extension = self::get_image_extension($do->mimeType);
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
            if(!($TMP = fopen($temp_file_path, "w")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $temp_file_path);
              return;
            }
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
            $this->text_path["image"]        = $temp_dir . "filled_file_importer_image_xls.txt";
            $this->text_path["text"]         = $temp_dir . "filled_TEMPLATE-import_into_taxon_description_xls.txt";
            $this->text_path["bibtex"]       = $temp_dir . "Biblio-Bibtex.bib";
            $this->text_path["biblio"]       = $temp_dir . "filled_node_importer_biblio_xls.txt";
            $this->text_path["parent_child"] = $temp_dir . "parent_child.txt"; // for export_lifedesk_taxonomy()
            $this->text_path["taxonomy"]     = $temp_dir . "filled_taxonomy_importer_xls.txt";
            
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
        if(@$this->file_importer_xls["taxonomy"]) $types[] = "taxonomy";
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
        $command_line = "tar -czf " . DOC_ROOT . "/public/tmp/lifedesk_exports/" . $params["name"] . "_LD_to_Scratchpad_export.tar.gz --directory=" . $destination_folder . " .";
        $output = shell_exec($command_line);
        recursive_rmdir($destination_folder);
    }

    public function convert_bibtex_file($file)
    {
        if($contents = Functions::lookup_with_cache($file, $this->download_options))
        {
            $contents = str_replace("@", "_xxxyyy@", $contents."@");
            if(preg_match_all("/\@(.*?)_xxxyyy/ims", $contents, $arr))
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
                                // echo "\n[$item]\n";
                                $item = str_replace("}", ", " . implode(", ", $this->booklet_taxa_list[$id])."}", $item);
                                // echo "\ninserted:";
                                // echo "\n[$item]\n";
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
        if(!($WRITE = fopen($filename, "a")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
          return;
        }
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
        if(!($WRITE = fopen($filename, "a")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
          return;
        }
        if($rec && is_array($rec)) fwrite($WRITE, json_encode($rec) . $nextline);
        else                       fwrite($WRITE, $rec . $nextline);
        fclose($WRITE);
    }

    private function initialize_dump_file($file)
    {
        if(!($f=fopen($file,"w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $file);
          return;
        } 
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
            echo "\n investigate: no subject [$subject]\n";
            exit;
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

    function export_lifedesk_taxonomy($params)
    {
        $this->text_path = array();
        if(self::load_zip_contents($params["lifedesk"]))
        {
            // initialize
            $type = "parent_child";
            self::initialize_dump_file($this->text_path[$type]);
            $headers = self::get_column_headers($this->file_importer_xls[$type]);
            print_r($headers);
            $this->lifedesk_fields[$type] = $headers;
            self::save_to_dump(implode("\t", $headers), $this->text_path[$type]);
            
            /* fields from template
            [0] => Identifier
            [1] => Parent
            [2] => Child
            [3] => Rank
            [4] => Synonyms
            [5] => Vernaculars
            [6] => VernacularsLanguage
            [7] => Description
            */
            
            // loop xml
            if(!$xml = self::load_xml()) exit("\nLifeDesk XML is invalid\n\n");
            foreach($xml->taxon as $t)
            {
                $dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
                $dc = $t->children("http://purl.org/dc/elements/1.1/");
                $rec = array();
                if(preg_match("/tid:(.*?)_xxx/ims", (string) $dc->identifier."_xxx", $arr)) $rec["Identifier"] = $arr[1];
                if($val = $dwc->Genus) $rec["Parent"] = (string) $val;
                elseif($val = $dwc->Family) $rec["Parent"] = (string) $val;
                elseif($val = $dwc->Order) $rec["Parent"] = (string) $val;
                elseif($val = $dwc->Class) $rec["Parent"] = (string) $val;
                elseif($val = $dwc->Phylum) $rec["Parent"] = (string) $val;
                elseif($val = $dwc->Kingdom) $rec["Parent"] = (string) $val;
                $rec["Child"] = (string) $dwc->ScientificName;
                $rec["Rank"] = ""; // ???
                
                $temp = array();
                foreach($t->synonym as $name) $temp[] = (string) $name;
                $rec["Synonyms"] = implode("|", $temp);
                
                $temp = array();
                foreach($t->commonName as $name) $temp[] = (string) $name;
                $rec["Vernaculars"] = implode(",", $temp);
                
                $rec["VernacularsLanguage"] = "";
                $rec["Description"] = "";
                self::save_to_template($rec, $this->text_path[$type], $type);
            }
            
            // compress
            $destination_folder = create_temp_dir() . "/";
            // move file to temp folder for compressing
            if($path = $this->text_path[$type])
            {
                $parts = pathinfo($path);
                copy($this->text_path[$type], $destination_folder . $parts["basename"]);
            }

            // compress export files
            $command_line = "tar -czf " . DOC_ROOT . "/public/tmp/lifedesk_exports/" . $params["name"] . "_parent_child.tar.gz --directory=" . $destination_folder . " .";
            $output = shell_exec($command_line);
            recursive_rmdir($destination_folder);
        }
        
        // remove temp dir
        $parts = pathinfo($this->text_path["eol_xml"]);
        recursive_rmdir($parts["dirname"]); //debug - comment if you want to see: images_not_in_xls.txt
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }

    private function fill_up_taxonomy_spreadsheet($spreadsheet)
    {
        $this->taxonomy_biblio = self::format_pipe_values($this->taxonomy_biblio);
        $headers = self::get_column_headers($spreadsheet);
        if($arr = self::convert_spreadsheet($spreadsheet, 0))
        {
            print "\n spreadsheet: " . count($arr[$headers[0]]) . "\n";
            $i = 0;
            foreach($arr['Term name'] as $term_name)
            {
                $NIDs = @$this->taxonomy_biblio[$term_name];
                $rec = array();
                foreach($headers as $header)
                {
                    if($header == "Reference (NID)") $rec[$header] = $NIDs;
                    else $rec[$header] = $arr[$header][$i];
                }
                if($rec) self::save_to_template($rec, $this->text_path["taxonomy"], "taxonomy");
                $i++;
            }
        }
    }

}
?>