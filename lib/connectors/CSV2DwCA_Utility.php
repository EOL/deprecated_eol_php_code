<?php
namespace php_active_record;
/* connector: [430] 
This can be a generic connector for CSV DwCA resources.
*/
class CSV2DwCA_Utility
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->debug = array();
        
        /* Please take note of some Meta XML entries have upper and lower case differences */
        $this->extensions = array("http://rs.gbif.org/terms/1.0/vernacularname"     => "vernacular",
                                  "http://rs.tdwg.org/dwc/terms/occurrence"         => "occurrence",
                                  "http://rs.tdwg.org/dwc/terms/measurementorfact"  => "measurementorfact",
                                  "http://rs.tdwg.org/dwc/terms/taxon"              => "taxon",
                                  "http://eol.org/schema/media/document"            => "document",
                                  "http://rs.gbif.org/terms/1.0/reference"          => "reference",
                                  "http://eol.org/schema/agent/agent"               => "agent",

                                  //start of other row_types: check for NOTICES or WARNINGS, add here those undefined URIs
                                  "http://rs.gbif.org/terms/1.0/description"        => "document",
                                  "http://rs.gbif.org/terms/1.0/multimedia"         => "document",
                                  "http://eol.org/schema/reference/reference"       => "reference",
                                  "http://rs.tdwg.org/dwc/terms/Taxon"              => "taxon",
                                  "http://eol.org/schema/media/Document"            => "document"
                                  );
    }

    private function start()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }

    function convert_archive()
    {
        if(!($info = self::start())) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        echo "\nConverting CSV archive to EOL DwCA...\n";
        foreach($tables as $key => $values) {
            $tbl = $values[0];
            // print_r($tbl); exit;
            if($class = @$this->extensions[$tbl->row_type]) //process only defined row_types
            {
                echo "\n -- Processing [$class]...\n";
                self::process_extension($tbl->file_uri, $class, $tbl);
            }
            else exit("\nInvalid row_type [$tbl->row_type]\n");
        }
        
        $this->archive_builder->finalize(TRUE);
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
    }

    private function process_extension($csv_file, $class, $tbl)
    {
        $do_ids = array(); //for validation, prevent duplicate identifiers
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            if    ($class == "vernacular")  $c = new \eol_schema\VernacularName();
            elseif($class == "agent")       $c = new \eol_schema\Agent();
            elseif($class == "reference")   $c = new \eol_schema\Reference();
            elseif($class == "taxon")       $c = new \eol_schema\Taxon();
            elseif($class == "document")    $c = new \eol_schema\MediaResource();
            elseif($class == "occurrence")  $c = new \eol_schema\Occurrence();
            elseif($class == "measurementorfact")   $c = new \eol_schema\MeasurementOrFact();
            else exit("\nUndefined class [$class]\n");

            $row = fgetcsv($file);
            $i++; if(($i % 2000) == 0) echo "\n $i ";
            // if($i > 2000) break; //debug only - process a subset first 2k
            
            if($i == 1) {
                $fields = $row;
                $count = count($fields);
                // print_r($fields); break; //debug
            }
            else { //main records

                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n");
                    $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }

                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                
                // print_r($fields); print_r($rec);
                
                //start process record =============================================================================================
                if($class == 'document') {
                    if($rec['taxonID'] && $rec['accessURI']) {
                        if(!self::valid_uri_url($rec['accessURI'])) continue;
                        if(!self::valid_uri_url($rec['thumbnailURL'])) $rec['thumbnailURL'] = "";

                        $do_id = $rec['identifier'];
                        if(in_array($do_id, $do_ids)) {
                            // exit("\nduplicate do_id [$do_id]\n"); //debug
                            continue;
                        }
                        else $do_ids[] = $do_id;
                    }
                }
                
                // print_r($tbl); exit;
                foreach($tbl->fields as $f) {
                    $field = pathinfo($f['term'], PATHINFO_FILENAME);
                    
                    // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName" or "wgs84_pos#lat"
                    $parts = explode("#", $field);
                    if($parts[0]) $field = $parts[0];
                    if(@$parts[1]) $field = $parts[1];
                    
                    $c->$field = $rec[$field];
                }
                //end process record =============================================================================================

                // print_r($rec); exit;
                
            } //main records
            $this->archive_builder->write_object_to_file($c);
            
        } //main loop
        fclose($file);
    }
    
    private function valid_uri_url($str)
    {
        if(substr($str,0,7) == "http://") return true;
        elseif(substr($str,0,8) == "https://") return true;
        return false;
    }
    
    /* was not used
    private function clean_html($html)
    {
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        return Functions::remove_whitespace($html);
    }
    function start_fix_supplied_archive_by_partner()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*25, 'cache' => 1)); //expires in 25 days 
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        print_r($paths);
        self::process_extension($archive_path);
        recursive_rmdir($temp_dir);
    }
    */
}
?>
