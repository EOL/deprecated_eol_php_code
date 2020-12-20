<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from Environments2EOLAPI.php; from environments_2_eol.php for DATA-1851] */
class Environments2EOLfinal
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->debug = array();
        /* OLD - Vangelis
        if(Functions::is_production()) $this->root_path = '/u/scripts/vangelis_tagger/';
        else                           $this->root_path = '/Library/WebServer/Documents/vangelis_tagger/';
        */
        // /* NEW - Pensoft
        if(Functions::is_production()) $this->root_path = '/html/Pensoft_annotator/';
        else                           $this->root_path = '/Library/WebServer/Documents/Pensoft_annotator/';
        
        if($resource_id == '617_ENV') {} //Wikipedia EN
        else { //rest of the resources
            $tmp = str_replace('_ENV', '', $resource_id);
            $this->root_path .= $tmp.'/';
            if(!is_dir($this->root_path)) mkdir($this->root_path);
            // exit("\n$this->root_path\n");
        }
        
        // */
        $this->eol_tags_path        = $this->root_path.'eol_tags/';
        $this->json_temp_path       = $this->root_path.'temp_json/';
        echo "\nEnvironments2EOLfinal resource_id: [$this->resource_id]\n";
        if($this->resource_id == '617_ENV') $this->modulo = 50000; //Wikipedia EN
        else                                $this->modulo = 2000;
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {   
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping -> not needed here
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        END DATA-1841 terms remapping
        self::initialize_mapping(); //for location string mappings
        */
        // /* customize
        echo "\n resource_id is [$this->resource_id]\n";
        if($this->resource_id == '26_ENV') { //this will just populate MoF. Too big in memory to do in DwCA_Utility.php.
            $tables = $info['harvester']->tables;
            $meta = $tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0];
            self::process_table($meta, 'create extension', 'measurementorfact');
        }
        // */
        self::add_environmental_traits();
        /* Below will be used if there are adjustments to existing MoF and Occurrences
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        */
        if($this->debug) print_r($this->debug);
    }
    private function initialize_mapping()
    {   
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
    }
    private function borrow_data()
    {
        require_library('connectors/EnvironmentsEOLDataConnector');
        $func = new EnvironmentsEOLDataConnector();
        $this->excluded_uris = $func->excluded_measurement_values(); //from here: https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=62373&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62373
        return $func;
    }
    private function add_environmental_traits()
    {
        echo "\nProcessing...Environments2EOLfinal...$this->resource_id\n";
        $old_func = self::borrow_data(); // print_r($this->excluded_uris); exit("\nexcluded uris\n");
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        $tsv = $this->eol_tags_path.'eol_tags_noParentTerms.tsv';
        $i = 0;
        foreach(new FileIterator($tsv) as $line_number => $row) {
            $i++; if(($i % $this->modulo) == 0) echo "\n".number_format($i);
            $arr = explode("\t", $row); // print_r($arr); exit;
            /* Array(
                [0] => 1005_-_1005_distribution.txt
                [1] => 117
                [2] => 122
                [3] => shrubs
                [4] => ENVO:00000300
            )*/
            $arr[0] = str_replace('.txt', '', $arr[0]);
            $a = explode("_-_", $arr[0]);
            $taxonID = @$a[0];
            $identifier = @$a[1];
            $rek = self::retrieve_json($taxonID."_".$identifier);
            if(@$arr[3] && @$arr[4]) {
                // /* post legacy filters: start Aug 5, 2020: /DATA-1851?focusedCommentId=65084&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65084
                $tags_not_to_be_used = array("Playa", "nest", "aquarium", "logged areas", "trenches", "bamboo");
                if(in_array($arr[3], $tags_not_to_be_used)) continue;
                // */
                
                $rec = array();
                $rec["taxon_id"] = $taxonID;
                $rec["catnum"] = md5($row);
                $rec['measurementType'] = 'http://purl.obolibrary.org/obo/RO_0002303';
                $rec['measurementRemarks'] = "source text: \"" . $arr[3] . "\"";
                
                $basename = $arr[4]; //e.g. 'ENVO:00000300'
                if(stripos($basename, "ENVO") !== false) { //string is found
                    $string_uri = 'http://purl.obolibrary.org/obo/'.str_replace(':', '_', $basename);
                }
                else $string_uri = $basename;

                // /* from legacy filters: EnvironmentsEOLDataConnector.php
                if(in_array($string_uri, $this->excluded_uris)) {
                    // echo "\nOh there is one filtered!\n"; //debug only
                    @$this->debug['legacy filter']['excluded uris occurrences']++;
                    continue;
                }
                // */
                
                if($val = @$rek['source']) $rec['source'] = $val;
                if($val = @$rek['bibliographicCitation']) $rec['bibliographicCitation'] = $val;
                if($val = @$rek['contributor']) $rec['contributor'] = $val;
                if($val = @$rek['referenceID']) $rec['referenceID'] = $val;
                if($val = @$rek['agentID'])     $rec['contributor'] = self::format_contributor_using_agentIDs($val);

                // /* from legacy filters: EnvironmentsEOLDataConnector.php
                $rec['measurementValue'] = $string_uri;
                if($rec = $old_func->adjustments($rec)) $this->func->add_string_types($rec, $rec['measurementValue'], $rec['measurementType'], "true");
                // */
            }
        }
    }
    private function format_contributor_using_agentIDs($agendIDs) //assumed agendIDs are semi-colon separated values
    {
        $final = '';
        $ids = explode(";", trim($agendIDs));
        $ids = array_map('trim', $ids);
        foreach($ids as $id) {
            $arr = self::retrieve_json('agent_'.$id);
            $arr = array_map('trim', $arr);
            if(!@$arr['term_name']) continue;
            /* Array(
                [identifier] => e4caf6a093328770804c83ba12c4e52c
                [term_name] => Albertina P. Lima
                [agentRole] => author
                [term_homepage] => http://eol.org
            )*/
            // print_r($arr); exit("\neli 100\n");
            unset($arr['identifier']);
            if($val = $arr['term_name']) $final .= " $val";
            if($val = @$arr['agentRole']) $final .= " ($val).";
            if($homepage = @$arr['term_homepage']) {
                if(self::valid_url($homepage)) $final .= " $homepage";
            }
        }
        return trim($final);
    }
    private function retrieve_json($id)
    {
        $file = self::build_path($id);
        if(is_file($file)) {
            $json = file_get_contents($file); // echo "\nRetrieved OK [$id]";
            return json_decode($json, true);
        }
        // else echo("\nFile not found [$id] [$file]\n"); //It means that the record doesn't have any attribution so no file was saved.
    }
    private function build_path($id) //$id is "$taxonID_$identifier"
    {
        $filename = "$id.json";
        $md5 = md5($id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        return $this->json_temp_path . "$cache1/$cache2/$filename";
    }
    private function valid_url($url)
    {
        if(substr($url, 0, 4) == 'http') return true;
    }
    /* Below will be used if there are adjustments to existing MoF and Occurrences
    private function process_measurementorfact($meta)
    {}
    private function process_occurrence($meta)
    {}
    */
    private function process_table($meta, $what, $class) //a generic method to populate an extension.
    {   print_r($meta);
        echo "\nprocess_table [$what]\n";
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /**/
            if($what == 'create extension') {
                if    ($class == "vernacular")          $o = new \eol_schema\VernacularName();
                elseif($class == "agent")               $o = new \eol_schema\Agent();
                elseif($class == "reference")           $o = new \eol_schema\Reference();
                elseif($class == "taxon")               $o = new \eol_schema\Taxon();
                elseif($class == "document")            $o = new \eol_schema\MediaResource();
                elseif($class == "occurrence")          $o = new \eol_schema\Occurrence();
                elseif($class == "measurementorfact")   $o = new \eol_schema\MeasurementOrFact();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
        }
    }
}
?>