<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from make_hash_IDs_4Deltas.php] */
class DeltasHashIDsAPI
{
    function __construct($archive_builder, $resource_id, $archive_path)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->archive_path = $archive_path;
        // $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*1, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->extensions = array("http://rs.gbif.org/terms/1.0/vernacularname"     => "vernacular",
                                  "http://rs.tdwg.org/dwc/terms/occurrence"         => "occurrence",
                                  "http://rs.tdwg.org/dwc/terms/measurementorfact"  => "measurementorfact",
                                  "http://eol.org/schema/media/document"            => "document",
                                  "http://rs.gbif.org/terms/1.0/reference"          => "reference",
                                  "http://eol.org/schema/agent/agent"               => "agent",

                                  //start of other row_types: check for NOTICES or WARNINGS, add here those undefined URIs
                                  "http://rs.gbif.org/terms/1.0/description"        => "document",
                                  "http://rs.gbif.org/terms/1.0/multimedia"         => "document",
                                  "http://eol.org/schema/reference/reference"       => "reference"
                                  );
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        $tables = $info['harvester']->tables; // print_r($tables); exit;
        $extensions = array_keys($tables);
        $extensions = array_diff($extensions, array("http://rs.tdwg.org/dwc/terms/taxon")); // print_r($extensions); exit;
        /*Array(
            [1] => http://eol.org/schema/agent/agent
            [2] => http://eol.org/schema/media/document
        )*/
        $extensions = array("http://eol.org/schema/media/document");
        foreach($extensions as $tbl) {
            $this->unique_ids = array();
            self::process_table($tables[$tbl][0], 'hash_identifiers', $this->extensions[$tbl]);
        }
        
    }
    private function process_table($meta, $what, $class)
    {   //print_r($meta);
        echo "\nprocess_table: [$what] [$meta->file_uri]...\n"; $i = 0;
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
            // print_r($rec); //exit;
            /*Array(
                [http://purl.org/dc/terms/identifier] => 101547869c8c59ff4d957018c28441f8
                [http://xmlns.com/foaf/spec/#term_name] => Tommyknocker
                [http://eol.org/schema/agent/agentRole] => creator
                [http://xmlns.com/foaf/spec/#term_homepage] => https://en.wikipedia.org/wiki/User:Tommyknocker
            )*/
            if($what == 'hash_identifiers') {
                if    ($class == "vernacular")  $o = new \eol_schema\VernacularName();
                elseif($class == "agent")       $o = new \eol_schema\Agent();
                elseif($class == "reference")   $o = new \eol_schema\Reference();
                elseif($class == "taxon")       $o = new \eol_schema\Taxon();
                elseif($class == "document")    $o = new \eol_schema\MediaResource();
                elseif($class == "occurrence")  $o = new \eol_schema\Occurrence();
                elseif($class == "occurrence_specific")  $o = new \eol_schema\Occurrence_specific(); //1st client is 10088_5097_ENV
                elseif($class == "measurementorfact")   $o = new \eol_schema\MeasurementOrFact();
                else exit("\nUndefined class [$class]. Will terminate.\n");

                $uris = array_keys($rec); // print_r($uris); //exit;
                $row_str = "";
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    // /*
                    $parts = explode("#", $field);
                    if($parts[0]) $field = $parts[0];
                    if(@$parts[1]) $field = $parts[1];
                    // */
                    $o->$field = $rec[$uri];
                    if($field != 'identifier') $row_str .= $rec[$uri]." | ";
                }
                $o->identifier = md5($row_str); //exit("\n[$row_str][$row_str]\n");
                if(!isset($this->unique_ids[$o->identifier])) {
                    $this->unique_ids[$o->identifier] = '';
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            // if($i >= 10) break; //debug only
        }
    }
}
?>