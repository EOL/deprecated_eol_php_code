<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from species_checklists.php] */
class SpeciesChecklistAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        $tables = $info['harvester']->tables;
        $tbls = array_keys($tables); print_r($tbls);
        foreach($tbls as $tbl) {
            self::process_extension($tables[$tbl][0]); //this is just to copy extension but with customization as described in DATA-1817
        }
    }
    private function get_dwca_short_fields($meta_fields)
    {
        foreach($meta_fields as $f) $final[] = pathinfo($f['term'], PATHINFO_FILENAME);
        return $final;
    }
    private function process_extension($meta)
    {   //print_r($meta->fields); //exit;
        echo "\nProcesing $meta->row_type ...\n";
        $dwca_short_fields = self::get_dwca_short_fields($meta->fields);
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            /* not followed since meta.xml is not reflective of the actual dwca. DwCA seems manually created.
            if($meta->ignore_header_lines && $i == 1) continue;
            */
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            
            // print_r($dwca_short_fields); print_r($tmp); exit;
            if(in_array($tmp[0], $dwca_short_fields)) continue; //since meta.xml is not reflective of the actual dwca. DwCA seems manually created.

            // echo "\n".count($meta->fields);
            // echo "\n".count($tmp); exit("\n");
            if(count($meta->fields != count($tmp))) continue;
            
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /**/

            $class = strtolower(pathinfo($meta->row_type, PATHINFO_FILENAME));
            if    ($class == "vernacular")          $o = new \eol_schema\VernacularName();
            elseif($class == "agent")               $o = new \eol_schema\Agent();
            elseif($class == "reference")           $o = new \eol_schema\Reference();
            elseif($class == "taxon")               $o = new \eol_schema\Taxon();
            elseif($class == "document")            $o = new \eol_schema\MediaResource();
            elseif($class == "occurrence")          $o = new \eol_schema\Occurrence();
            elseif($class == "measurementorfact")   $o = new \eol_schema\MeasurementOrFact();
            else exit("\nUndefined class\n");
            
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
    /* this is just to copy the extension as is. No customization.
    private function process_generic($meta)
    {   //print_r($meta);
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
            $o = new \eol_schema\Association();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    */
    
}
?>
