<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from remove_taxa_without_MoF.php */
class ResourceUtility
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
    }
    /*================================================================= STARTS remove_taxa_without_MoF ======================================================================*/
    function remove_taxa_without_MoF($info)
    {   
        $tables = $info['harvester']->tables;
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        echo "\nResourceUtility...process_occurrence...\n"; $i = 0;
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
            // print_r($rec); exit("\ndebug...\n");
            /**/
            //===========================================================================================================================================================
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $this->taxon_ids[$taxonID] = '';
            //===========================================================================================================================================================
        }
    }
    private function process_taxon($meta)
    {   //print_r($meta);
        echo "\nResourceUtility...process_taxon...\n"; $i = 0;
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
            // print_r($rec); exit("\ndebug...\n");
            /**/
            //===========================================================================================================================================================
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if(!isset($this->taxon_ids[$taxonID])) continue;
            //===========================================================================================================================================================
            $uris = array_keys($rec);
            $o = new \eol_schema\Taxon();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    /*================================================================= ENDS remove_taxa_without_MoF ======================================================================*/
}
?>