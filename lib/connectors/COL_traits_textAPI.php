<?php
namespace php_active_record;
/* connector: [COL_trait_text.php]
*/
class COL_traits_textAPI
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

    function convert_archive()
    {
        if(!($info = self::prepare_dwca())) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        echo "\nConverting COL archive to EOL DwCA...\n";
        

        // $tables = array_diff($tables, array("http://rs.tdwg.org/dwc/terms/measurementorfact")); //exclude measurementorfact
        // $tables = array_diff($tables, array("http://rs.gbif.org/terms/1.0/vernacularname")); //exclude vernacular name
        // $tables = array_diff($tables, array("http://eol.org/schema/association")); //exclude association name

        /*
        taxa.txt - names & hierarchy, extinct/extant measurements
        vernacular.txt - common names
        reference.txt - taxon references
        speciesprofile.txt - TraitBank habitat data
        distribution.txt - TraitBank distribution data
        description.txt - text objects (distribution notes)
        Array
        (
            [0] => http://rs.tdwg.org/dwc/terms/taxon
            [1] => http://rs.gbif.org/terms/1.0/distribution
            [2] => http://rs.gbif.org/terms/1.0/description
            [3] => http://rs.gbif.org/terms/1.0/reference
            [4] => http://rs.gbif.org/terms/1.0/speciesprofile
            [5] => http://rs.gbif.org/terms/1.0/vernacularname
        )
        */
        
        /* this is memory-intensive
        foreach($tables as $table) {
            $records = $harvester->process_row_type($table);
            // self::process_fields($records, pathinfo($table, PATHINFO_BASENAME));
            foreach($records as $rec) {
                echo "\n[$table]\n";
                print_r($rec); break;
            }
            $records = null;
        }
        */

        
        foreach($tables as $key => $values) {
            $tbl = $values[0];
            echo "\n".$tbl->row_type . " -- " . $tbl->file_uri;
            /*
            if($class = @$this->extensions[$tbl->row_type]) //process only defined row_types
            {
                echo "\n -- Processing [$class]...\n";
                self::process_extension($tbl->file_uri, $class, $tbl);
            }
            else exit("\nInvalid row_type [$tbl->row_type]\n");
            */
        }
        
        // $this->archive_builder->finalize(TRUE);
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
    }

    private function compute_for_dwca_file()
    {
        return "http://localhost/cp/COL/2018-03-28-archive-complete.zip";
    }
    private function prepare_dwca()
    {
        $dwca = self::compute_for_dwca_file();
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca, "meta.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
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

    private function process_extension($csv_file, $class, $tbl)
    {
    }
    private function get_google_sheet() //sheet found here: https://eol-jira.bibalex.org/browse/DATA-1744
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '19nQkPuuCB9lhQEoOByfdP0-Uwwhn5Y_uTu4zs_SVANI';
        $params['range']         = 'languages!A2:B451'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]] = $item[1];
        return $final;
    }
    
    
}
?>
