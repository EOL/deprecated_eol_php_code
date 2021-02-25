<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from SDR_consolid8.php for DATA-1777] */
class SDR_Consolid8API
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        // require_library('connectors/TraitGeneric'); 
        // $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        // $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */
        
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'carry-over');
    }
    private function process_measurementorfact($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...[$what]\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            // print_r($meta->fields);
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k]; //put "@" as @$tmp[$k] during development
                $k++;
            } print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 81ec79aed2c2cc9ef21dbb386ad75d0d_parent_basal_values
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => f5c907b74855c54eac52d520c95cf30e_parent_basal_values
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://eol.org/schema/terms/Australasia
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 2018-Oct-10
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => summary of records available in EOL
                [http://purl.org/dc/terms/source] => https://eol.org/terms/search_results?utf8=✓&term_query[clade_id]=288&term_query[filters_attributes][0][pred_uri]=http://eol.org/schema/terms/Present&term_query[filters_attributes][0][op]=is_any&term_query[result_type]=record&commit=Search
                [http://eol.org/schema/parentMeasurementID] => 
            )*/
            $rec = array_map('trim', $rec);
            if($what == 'carry-over') self::write_MoF_rec($rec);
            elseif($what == 'consolidate_parent_BV') self::append_parent_BV_resource_txt($rec);
            //===========================================================================================================================================================
            // if($i >= 10) break; //debug only
        }
    }
    private function write_MoF_rec($rec)
    {
        $m = new \eol_schema\MeasurementOrFact_specific();
        $uris = array_keys($rec);
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            $m->$field = $rec[$uri];
        }

        /* add measurementID if missing --- New Jan 14, 2021
        if(!isset($m->measurementID)) {
            $m->measurementID = Functions::generate_measurementID($m, $this->resource_id); //3rd param is optional. If blank then it will consider all properties of the extension
        }
        */

        if(!isset($this->measurementIDs[$m->measurementID])) {
            $this->measurementIDs[$m->measurementID] = '';
            $this->archive_builder->write_object_to_file($m);
        }
        return $m;
    }
}
?>