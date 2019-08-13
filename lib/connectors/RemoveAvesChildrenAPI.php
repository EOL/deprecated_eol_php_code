<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from 727.php for DATA-1819] */
class USDAPlants2019
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        $this->download_options = array('resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);

        $this->area['L48'] = "Lower 48 United States of America";
        $this->area['AK'] = "Alaska, USA";
        $this->area['HI'] = "Hawaii, USA";
        $this->area['PR'] = "Puerto Rico";
        $this->area['VI'] = "U. S. Virgin Islands";
        $this->area['CAN'] = "Canada";
        $this->area['GL'] = "Greenland (Denmark)";
        $this->area['SPM'] = "St. Pierre and Miquelon (France)";
        $this->area['NA'] = "North America";
        $this->area['NAV'] = "Navassa Island";
        $this->state_list_page = 'https://plants.sc.egov.usda.gov/dl_state.html';
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        $tables = $info['harvester']->tables;
        // self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        // self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
        
        print_r($this->debug); exit;
    }
    private function process_generic_table($meta, $what)
    {   //print_r($meta);
        echo "\nprocess $what...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            print_r($rec); exit;
            /**/
            
            if($what == 'taxon')            $o = new \eol_schema\Taxon();
            elseif($what == 'MoF')          $o = new \eol_schema\MeasurementOrFact_specific();
            elseif($what == 'occurrence')   $o = new \eol_schema\Occurrence();
            
            
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
