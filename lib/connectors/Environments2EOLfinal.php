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

        if(Functions::is_production()) $this->root_path = '/u/scripts/vangelis_tagger/';
        else                           $this->root_path = '/Library/WebServer/Documents/vangelis_tagger/';
        $this->eol_tags_path        = $this->root_path.'eol_tags/';
        $this->json_temp_path       = $this->root_path.'temp_json/';
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
        self::add_environmental_traits();
        /* Below will be used if there are adjustments to existing MoF and Occurrences
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        */
    }
    private function initialize_mapping()
    {   
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
    }
    private function add_environmental_traits()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        $tsv = $this->eol_tags_path.'eol_tags_noParentTerms.tsv';
        $i = 0;
        foreach(new FileIterator($tsv) as $line_number => $row) {
            $i++; if(($i % 1000) == 0) echo "\n".number_format($i);
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
                $rec = array();
                $rec["taxon_id"] = $taxonID;
                $rec["catnum"] = md5($row);
                $rec['measurementRemarks'] = $arr[3];
                $string_uri = 'http://purl.obolibrary.org/obo/'.str_replace(':', '_', $arr[4]);
                $mtype = 'http://purl.obolibrary.org/obo/RO_0002303';
                if($val = @$rek['source']) $rec['source'] = $val;
                if($val = @$rek['bibliographicCitation']) $rec['bibliographicCitation'] = $val;
                if($val = @$rek['contributor']) $rec['contributor'] = $val;
                if($val = @$rek['referenceID']) $rec['referenceID'] = $val;
                $this->func->add_string_types($rec, $string_uri, $mtype, "true");
            }
        }
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
    /* Below will be used if there are adjustments to existing MoF and Occurrences
    private function process_measurementorfact($meta)
    {}
    private function process_occurrence($meta)
    {}
    */
}
?>