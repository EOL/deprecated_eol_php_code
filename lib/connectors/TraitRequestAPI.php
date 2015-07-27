<?php
namespace php_active_record;
/* connector: [trait_request] WEB-5987
Connector processes a spreadsheet, looks-up using the EOL API (search, traits) and generates a report of the requested taxa 
and its respective available traits in eol.org.
*/

class TraitRequestAPI
{
    function __construct()
    {
        $this->download_options = array("resource_id" => "trait_request", "download_wait_time" => 2000000, "timeout" => 3600, "download_attempts" => 1);
        $this->download_options['expire_seconds'] = false;
        $this->spreadsheet_options = array("cache" => 0, "timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 1, 'delay_in_minutes' => 1); //we don't want to cache spreadsheet
        $this->url['api_search'] = "http://eol.org/api/search/1.0.json?page=1&exact=true&cache_ttl=&q=";
        $this->url['api_traits'] = "http://eol.org/api/traits/";
        $this->url['measurement_extension'] = "https://dl.dropboxusercontent.com/u/1355101/ontology/measurement_extension.xml";
        $this->measurement_fields = array();
    }
    
    function generate_traits_for_taxa($params)
    {
        $this->temp_dir = create_temp_dir() . "/";
        $taxa         = self::access_spreadsheet($params, 0);
        $this->traits = self::access_spreadsheet($params, 1);
        self::process_taxa($taxa, "get_measurement_fields");
        self::initialize_text_files();
        self::process_taxa($taxa, "process_taxa");
        self::delete_blank_text_files();
        // compress text files, delete temp dir
        $trait_request_dir_path = DOC_ROOT . "/public/tmp/trait_request/";
        if(!is_dir($trait_request_dir_path)) mkdir($trait_request_dir_path);
        $command_line = "tar -czf " . $trait_request_dir_path . $params["name"] . ".tar.gz --directory=" . $this->temp_dir . " .";
        $output = shell_exec($command_line);
        recursive_rmdir($this->temp_dir);
    }

    private function process_taxa($taxa, $purpose)
    {
        $i = -1;
        $total = count($taxa['Genus']);
        foreach($taxa['Genus'] as $genus)
        {
            $i++;
            echo "\n$i of $total";
            $sciname = $genus . " " . $taxa['Species'][$i];
            if($json = Functions::lookup_with_cache($this->url['api_search'].$sciname, $this->download_options))
            {
                $obj = json_decode($json);
                if($rec = @$obj->results[0])
                {
                    $taxon_rec = array();
                    $taxon_rec['sciname'] = $sciname;
                    $taxon_rec['taxon_id'] = $rec->id;
                    if($purpose == "process_taxa")
                    {
                        $taxon_rec['records'] = self::process_taxon($rec->id);
                        self::save_to_text_file($taxon_rec);
                    }
                    elseif($purpose == "get_measurement_fields") self::get_measurement_fields($rec->id);
                }
                else echo "\nnot found [$sciname]";
            }
            else echo "\n not found: [$sciname]";
            // if($i > 2) break; //debug
        }
    }

    private function get_measurement_fields($taxon_id)
    {
        if($json = Functions::lookup_with_cache($this->url['api_traits'].$taxon_id, $this->download_options))
        {
            $obj = json_decode($json);
            foreach($obj->{"@graph"} as $rec)
            {
                if($r = @$rec->{"dwc:measurementType"}) //process only structured data, not regular dataObjects
                {
                    if(!in_array($r->{"@id"}, $this->traits['trait_uri'])) continue; //process only those traits listed by client
                    $this->measurement_fields = array_merge($this->measurement_fields, array_keys((array) $rec));
                }
            }
            $this->measurement_fields = array_unique($this->measurement_fields);
        }
    }

    private function save_to_text_file($taxon_rec)
    {
        $sciname = $taxon_rec['sciname'];
        $taxon_id = $taxon_rec['taxon_id'];
        $i = 0;
        foreach($this->traits['trait_uri'] as $trait)
        {
            $filename = $this->temp_dir . str_replace("/", " ", $this->traits['trait'][$i]) . ".txt";
            $WRITE = fopen($filename, "a");
            foreach($taxon_rec['records'] as $rec)
            {
                if($rec['dwc:measurementType']->{"@id"} == $trait) {}
                else continue;
                $str = $sciname."\t";
                foreach($this->measurement_fields as $f)
                {
                    if(in_array($f, array("dwc:measurementType", "dwc:measurementValue", "dwc:measurementUnit", "eolterms:statisticalMethod")))
                    {
                        $r = @$rec[$f];
                        if(@$r->{"@id"})
                        {
                            $str .= @$r->{"@id"}."\t";
                            $str .= @$r->{"rdfs:label"}->en."\t";
                        }
                        else $str.= "\t".$r."\t";
                    }
                    else $str .= (string) @$rec[$f]."\t";
                }
                fwrite($WRITE, $str."\n");
            }
            fclose($WRITE);
            $i++;
        }
    }
    
    private function initialize_text_files()
    {
        $i = 0;
        foreach($this->traits['trait_uri'] as $trait)
        {
            $filename = $this->temp_dir . str_replace("/", " ", $this->traits['trait'][$i]) . ".txt";
            $WRITE = fopen($filename, "a");
            $str = "Taxa\t";
            foreach($this->measurement_fields as $f)
            {
                if(in_array($f, array("dwc:measurementType", "dwc:measurementValue", "dwc:measurementUnit", "eolterms:statisticalMethod")))
                {
                    $str .= "[$f]_uri"."\t";
                    $str .= "[$f]_label"."\t";
                }
                else $str .= $f."\t";
            }
            fwrite($WRITE, $str."\n");
            fclose($WRITE);
            $i++;
        }
    }
    
    private function delete_blank_text_files()
    {
        $i = 0;
        foreach($this->traits['trait_uri'] as $trait)
        {
            $filename = $this->temp_dir . str_replace("/", " ", $this->traits['trait'][$i]) . ".txt";
            if(Functions::count_rows_from_text_file($filename) < 2) unlink($filename);
            $i++;
        }
    }
    
    private function process_taxon($taxon_id)
    {
        $recs = array();
        if($json = Functions::lookup_with_cache($this->url['api_traits'].$taxon_id, $this->download_options))
        {
            $obj = json_decode($json);
            foreach($obj->{"@graph"} as $rec)
            {
                $rek = array();
                if($r = @$rec->{"dwc:measurementType"})
                {
                    if(!in_array($r->{"@id"}, $this->traits['trait_uri'])) continue;
                    foreach($this->measurement_fields as $f)
                    {
                        if($f == "data_point_uri_id") $rek[$f] = $rec->{"dwc:taxonID"} . "/data#data_point_" . @$rec->{$f};
                        else $rek[$f] = @$rec->{$f};
                    }
                }
                if($rek) $recs[] = $rek;
            }
        }
        return $recs;
    }
    
    private function access_spreadsheet($params, $sheet_no)
    {
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();
        if($val = @$params["spreadsheet_options"]) $spreadsheet_options = $val;
        else                                       $spreadsheet_options = $this->spreadsheet_options;
        if($arr = $func->convert_spreadsheet($params['spreadsheet'], $sheet_no, $spreadsheet_options)) return $arr;
    }

}
?>