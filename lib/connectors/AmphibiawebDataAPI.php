<?php
namespace php_active_record;
/* connector: [959] */
class AmphibiawebDataAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->strings_to_search = "http://localhost/cp/Amphibiaweb/region strings for queries.txt";
        $this->strings_to_search = "http://opendata.eol.org/dataset/35d46a45-a330-4f09-810f-02d197a7d9fe/resource/1f3dad5e-3990-4e3c-a986-ef5e4062875d/download/region-strings-for-queries.txt";
        $this->pages['country records'] = 'http://amphibiaweb.org/cgi/amphib_query?rel-isocc=like&where-isocc=';                                            //e.g. China
        $this->pages['US state records'] = 'http://amphibiaweb.org/cgi/amphib_query?rel-state_code=like&where-state_code=';                                 //e.g. OK
        $this->pages['Canadian province records'] = 'http://amphibiaweb.org/cgi/amphib_query?rel-isocc=like&where-isocc=CA&rel-region=like&where-region=';  //e.g. Saskatchewan
        $this->pages['endemic'] = 'http://amphibiaweb.org/cgi/amphib_query?rel-isocc=eq&where-isocc='; //country records, for endemic measurementType, e.g. China
        $this->download_options = array('resource_id' => '959', 'expire_seconds' => false, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1); 
    }

    function get_all_taxa()
    {
        //TODO: [next] button is not processed
        ini_set("auto_detect_line_endings", true);
        $filename = Functions::save_remote_file_to_local($this->strings_to_search, array('cache' => 1, 'resource_id' => '959')); //resource_id here is just to have the cache stored in that folder
        
        $types[1] = 'country records';
        $types[2] = 'US state records';
        $types[3] = 'Canadian province records';
        
        $i = 1;
        foreach(new FileIterator($filename) as $line_number => $region)
        {
            if($region == "") // a blank row separates the different types
            {
                $i++;
                continue;
            }
            // $region = 'China'; //debug
            $type = $types[$i];
            $url = $this->pages[$type] . $region;

            if($records = self::process_html($url, 'pre')) self::create_archive($records, $region, 'present');

            if($type == 'country records') //endemic measurement is only for country records
            {
                $url = $this->pages['endemic'] . $region;
                if($records = self::process_html($url, 'end')) self::create_archive($records, $region, 'endemic');
            }
        }
        unlink($filename);
        $this->archive_builder->finalize(TRUE);
    }
    
    private function process_html($url, $type)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            $html = self::clean_html($html);
            $html = str_ireplace('<td align=center>', '<td>', $html);
            return self::parse_page($html, $type);
        }
        return false;
    }
    
    private function parse_page($html, $type)
    {
        $final = array();
        $html = strip_tags($html, "<tr><td><a>");
        $temp = explode("<tr><tr>", $html);
        foreach($temp as $t)
        {
            if(preg_match_all("/<td>(.*?)<\/td>/ims", $t, $arr))
            {
                $r = array_map('trim', $arr[1]);
                if(!@$r[6] || !@$r[7]) continue;
                $rec = array();
                $rec['sciname'] = strip_tags($r[0]);
                $rec['family'] = strip_tags($r[6]);
                $rec['order'] = strip_tags($r[7]);
                if(preg_match("/href=(.*?)>/ims", $r[0], $arr)) $rec['source'] = $arr[1];
                if($rec['sciname'] && $rec['source']) $final[] = $rec;
                
                // if($rec['sciname'] == "Amietophrynus latifrons") //debug
                // {
                //     print_r($rec);
                // }
            }
        }
        return $final;
    }

    private function create_archive($records, $region, $type)
    {
        foreach($records as $rec)
        {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = strtolower(str_replace(" ", "_", $rec['sciname']));
            $taxon->scientificName  = $rec['sciname'];
            $taxon->order           = $rec['order'];
            $taxon->family          = $rec['family'];
            
            if($path = $rec['source']) $taxon->furtherInformationURL = "http://amphibiaweb.org" . $path;
            else
            {
                print_r($rec); exit("\nno source\n");
            }
            
            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
            
            //start structured data
            $rec['source'] = $taxon->furtherInformationURL;
            $rec['taxon_id'] = $taxon->taxonID;
            $rec['catnum'] = $taxon->taxonID . "_" . $type . "_" . $region;
            if($type == "present")      self::add_string_types($rec, $region, "http://eol.org/schema/terms/Present");
            elseif($type == "endemic")  self::add_string_types($rec, $region, "http://eol.org/terms/endemic");
        }
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
        $m->bibliographicCitation = "AmphibiaWeb: Information on amphibian biology and conservation. [web application]. 2015. Berkeley, California: AmphibiaWeb. Available: http://amphibiaweb.org/.";
        // $m->measurementMethod   = '';
        // $m->measurementRemarks  = '';
        // $m->contributor         = '';
        // $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue'));
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $catnum; //can be just this, no need to add taxon_id
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID      = $taxon_id;
        
        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        
        $this->archive_builder->write_object_to_file($o);

        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
        
        /* old ways
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
        */
    }

    private function clean_html($html)
    {
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        return Functions::remove_whitespace($html);
    }

}
?>
