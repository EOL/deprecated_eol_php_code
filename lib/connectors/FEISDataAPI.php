<?php
namespace php_active_record;
/* connector: [feis] 
This connector screen-scrapes the data from the individual pages in FEIS website.
*/
class FEISDataAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('resource_id' => 'FEIS', 'expire_seconds' => false, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1);
        $this->pages['mappings'] = 'dropbox path';
        $this->pages['mappings'] = 'http://localhost/cp/FEIS/Traitbank_resource/fireeffects habitat terms.xlsx';
        $this->debug = array();
    }

    function get_all_taxa()
    {
        require_library('connectors/USDAfsfeisAPI');
        $resource_id = false;
        $group["Plantae"] = "plants";
        $func = new USDAfsfeisAPI($resource_id, $group);
        $records = $func->prepare_taxa_urls();

        /*
        [taxonID] => WISFLO
        [url] => http://www.fs.fed.us/database/feis/plants/vine/wisspp/all.html
        [sciname] => Wisteria floribunda
        [vernacular] => Japanese wisteria
        [kingdom] => Plantae
        */
        
        $info = self::get_spreadsheet($this->pages['mappings']);
        $subsections = $info['subsections'];
        $habitats = $info['habitats'];
        
        foreach($records as $record)
        {
            // $record['url'] = 'http://www.fs.fed.us/database/feis/plants/tree/robpse/all.html'; //debug
            // $record['url'] = 'http://www.fs.fed.us/database/feis/plants/cactus/echfen/all.html'; //debug
            // $record['url'] = 'http://www.fs.fed.us/database/feis/plants/tree/alnrho/all.html'; //debug
            // $record['url'] = 'http://www.fs.fed.us/database/feis/plants/fern/botspp/all.html'; //debug
            // $record['url'] = 'http://www.fs.fed.us/database/feis/plants/forb/corvar/all.html'; //debug
            
            $rec = self::process_page($record['url']);
            $rec['taxon_id'] = $record['taxonID'];
            $rec['kingdom'] = $record['kingdom'];
            $rec['sciname'] = $record['sciname'];
            
            if(@$rec['life_form'] || @$rec['habitat'])
            {
                self::create_archive($rec, $habitats, $subsections);
                // print_r($rec);
            }
            // break; //debug
        }
        
        $this->archive_builder->finalize(TRUE);
        if($val = $this->debug) print_r($val);
    }
    
    private function process_page($url)
    {
        $rec = self::parse_html($url);
        return $rec;
    }
    
    private function parse_html($url)
    {
        $final = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            $html = str_ireplace("APPENDIX: FIRE REGEIME TABLE", "APPENDIX: FIRE REGIME TABLE", $html);
            
            $final['source'] = $url;
            $final['life_form'] = self::get_Raunkiaer_life_form($html, $url);
            
            $final['authorship_citation'] = self::get_authorship_citation($html);

            if(preg_match("/<a name=\"AppendixFireRegimeTable\"(.*?)<a name=\"AppendixB\">/ims", $html, $arr) ||
               preg_match("/<a name='AppendixFireRegimeTable'(.*?)<a name='AppendixB'>/ims", $html, $arr) ||
               preg_match("/<a name='APPENDIX: FIRE REGIME TABLE'(.*?)<a name='REFERENCES'>/ims", $html, $arr) ||
               preg_match("/<a name=\"APPENDIX: FIRE REGIME TABLE\"(.*?)<a name=\"REFERENCES\">/ims", $html, $arr) ||
               preg_match("/<a name=\"APPENDIX: FIRE REGIME TABLE\"(.*?)<a name='REFERENCES'>/ims", $html, $arr) ||
               preg_match("/<a name=\"AppendixFireRegimeTable\"(.*?)<a name='REFERENCES'>/ims", $html, $arr) ||
               preg_match("/<a name='AppendixFireRegimeTable'(.*?)<a name='REFERENCES'>/ims", $html, $arr)
              )
            {
                if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1], $arr2))
                {
                    $TRs = $arr2[1];
                    $i = 0;
                    foreach($TRs as $tr)
                    {
                        $i++;
                        if($i == 1) continue; //exclude first <tr>
                        
                        if(preg_match_all("/<td(.*?)<\/td>/ims", $tr, $arr3))
                        {
                            $temp = $arr3[1];
                            
                            $exclude = array(">Vegetation Community", ">Percent of fires", ">Surface or low", ">Mixed<", "vegetation communities");
                            if(self::needle_occurs_in_this_haystack($temp[0]."<", $exclude)) continue;
                            
                            if(count($temp) == 1) $index = self::clean_html(strip_tags("<td" . $temp[0]));
                            else
                            {
                                if(isset($index))
                                {
                                    if($to_be_added = self::get_term_to_be_added($temp[0]))
                                    {
                                        /* // a good way to catch/debug 
                                        if($to_be_added == "Pacific Northwest")
                                        {
                                            print_r($temp);
                                            echo "\nindex[$index]\n";
                                        }
                                        */

                                        if(isset($final['habitat'][$index]))
                                        {
                                            if(!in_array($to_be_added, @$final['habitat'][$index])) @$final['habitat'][$index][] = $to_be_added;
                                        }
                                        else @$final['habitat'][$index][] = $to_be_added;
                                    }
                                }
                            }
                        }
                    }
                }
                // else echo "\n No <tr>s\n";
            }
            // else echo "\nAPPENDIX: FIRE REGIME TABLE not found\n";
        }
        return $final;
    }
    
    private function get_term_to_be_added($str)
    {
        if(stripos($str, "<table") === false)
        {
            return self::clean_html(strip_tags("<td" . $str));
        }
        else return false;
    }
    
    private function get_Raunkiaer_life_form($html, $url)
    {
        $final = array();
        if(preg_match("/<a name=\"Raunkiaer life form\">(.*?)<span/ims", $html, $arr))
        {
            $html = strip_tags($arr[1], "<a>");
            if(preg_match_all("/<a href(.*?)<\/a>/ims", $html, $arr))
            {
                foreach($arr[1] as $t)
                {
                    if(preg_match("/>(.*?)xxx/ims", $t."xxx", $arr2))
                    {
                        if(!is_numeric($arr2[1]))
                        {
                            $final[] = $arr2[1];
                        }
                    }
                }
            }
        }
        return $final;
    }
    
    private function get_authorship_citation($html)
    {
        if(preg_match("/AUTHORSHIP AND CITATION:(.*?)\[<script/ims", $html, $arr))
        {
            $temp = self::clean_html(strip_tags($arr[1]));
            $temp .= " [" . date("Y, F d") . "].";
            return $temp;
        }
        return false;
    }
    
    private function needle_occurs_in_this_haystack($needle, $haystack)
    {
        foreach($haystack as $phrase)
        {
            if(is_numeric(stripos($needle, $phrase))) return true;
        }
        return false;
    }
    
    private function create_archive($rec, $habitats, $subsections)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxon_id'];
        $taxon->scientificName          = self::format_utf8($rec['sciname']);
        $taxon->kingdom                 = $rec['kingdom'];
        $taxon->furtherInformationURL   = $rec['source'];
        
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
        
        //start structured data - habitat
        $rek = array();
        $rek['source'] = $taxon->furtherInformationURL;
        $rek['taxon_id'] = $taxon->taxonID;
        $rek['citation'] = $rec['authorship_citation'];
        
        if($val = @$rec['habitat'])
        {
            foreach($val as $subsection => $terms)
            {
                foreach($terms as $term)
                {
                    $rek['catnum'] = $taxon->taxonID . "_[" . $subsection . "]_" . $term;
                    $rek['catnum'] = md5($rek['catnum']);
                    if($val = @$habitats[$term]) self::add_string_types($rek, $val, "http://purl.obolibrary.org/obo/RO_0002303");
                    else
                    {
                        $section = @$subsections[$subsection]['section'];
                        $this->debug[$rec['source']][$section][$subsection][$term] = '';
                    }
                }
            }
        }

        //start structured data - life form
        if($val = @$rec['life_form'])
        {
            foreach($val as $life_form)
            {
                $rek['catnum'] = $taxon->taxonID . "_" . $life_form;
                self::add_string_types($rek, "http://eol.org/schema/terms/".self::format_life_form($life_form), "http://eol.org/schema/terms/PlantHabit");
            }
        }
        
    }
    
    private function format_life_form($life_form)
    {
        //manual adjustment
        $life_form = str_ireplace("\ntherophyte", "therophyte", $life_form);
        $life_form = str_ireplace("phytes", "phyte", $life_form);
        
        $arr = explode(" ", $life_form);
        if(@$arr[1])
        {
            $arr[1] = ucfirst($arr[1]);
            $arr[0] = strtolower($arr[0]);
            return implode("", $arr);
        }
        else return strtolower($life_form);
    }

    private function add_string_types($rec, $value, $mtype)
    {
        $taxon_id = $rec['taxon_id'];
        $catnum = $rec['catnum'];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum, $rec);
        $m->occurrenceID = $occurrence_id;
        $m->measurementOfTaxon  = 'true';
        $m->measurementType     = $mtype;
        $m->measurementValue    = $value;
        $m->source              = $rec['source'];
        if($val = @$rec['citation']) $m->bibliographicCitation = $val;
        // $m->measurementMethod   = '';
        // $m->measurementRemarks  = '';
        // $m->contributor         = '';
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $catnum; //can be just this, no need to add taxon_id
        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID      = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
    }

    private function get_spreadsheet($spreadsheet)
    {
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();

        $final = array();
        $habitats = array();
        $spreadsheet_options = array("cache" => 0, "timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 1); //we don't want to cache spreadsheet
        if($filename = Functions::save_remote_file_to_local($spreadsheet, $spreadsheet_options))
        {
            if($arr = $func->convert_spreadsheet($filename, 0, $spreadsheet_options))
            {
                $i = 0;
                foreach($arr['subsection'] as $subsection)
                {
                    if($subsection)
                    {
                        $final[$subsection]['section'] = $arr['section'][$i];
                        $final[$subsection]['habitats'][] = $arr['source text'][$i];
                    }
                    $habitats[$arr['source text'][$i]] = $arr['term'][$i];
                    $i++;
                }
            }
            unlink($filename);
        }
        $final = array_filter($final); //remove null arrays
        $habitats = array_filter($habitats); //remove null arrays
        return array('subsections' => $final, 'habitats' => $habitats);
    }
    
    private function clean_html($html)
    {
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        return Functions::remove_whitespace($html);
    }
    
    private function format_utf8($str)
    {
        if(Functions::is_utf8($str)) return $str;
        else return utf8_encode($str);
    }

}
?>
