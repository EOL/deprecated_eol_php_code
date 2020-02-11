<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from remove_Aves_children_from_268.php from https://eol-jira.bibalex.org/browse/DATA-1814?focusedCommentId=63686&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63686] */
class RemoveAvesChildrenAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        // $this->download_options = array('resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);

    }
    /*================================================================= STARTS HERE ======================================================================*/
    private function get_children_of_Aves()
    {
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . "368".".tar.gz";
        $descendant_taxon_ids = $func->get_descendants_given_parent_ids($dwca_file, array(36616, 7022)); //Aves taxon_id is 36616 | Polychaeta = 7022
        return $descendant_taxon_ids;
    }
    function start($info)
    {
        $this->children_of_Aves = array();
        $children = self::get_children_of_Aves();
        foreach($children as $child) $this->children_of_Aves[$child] = '';
        unset($children);
        echo "\nChildren of Aves: ".count($this->children_of_Aves)."\n";
        
        $tables = $info['harvester']->tables;
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
        self::process_generic_table($tables['http://rs.gbif.org/terms/1.0/vernacularname'][0], 'vernacular');
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'occurrence');
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'MoF');
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
            // print_r($rec); exit;
            /**/
            
            if($what == 'taxon') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) continue;
                // if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']])) continue; --- COMMMENT THIS - VERY WRONG.
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID']])) continue;
                $o = new \eol_schema\Taxon();
            }
            elseif($what == 'vernacular') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) continue;
                $o = new \eol_schema\VernacularName();
            }
            elseif($what == 'occurrence') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) {
                    $this->remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
                    continue;
                }
                $o = new \eol_schema\Occurrence();
            }
            elseif($what == 'MoF') {
                if(isset($this->remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
                $o = new \eol_schema\MeasurementOrFact_specific();
            }
            else exit("\nInvestigate [$what]\n");
            
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
