<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
// require_library('connectors/AnageDataConnector');
// $GLOBALS['ENV_DEBUG'] = true;


$resource_id = 'globi';
$connector = new GlobiConnector($resource_id);
$connector->build_archive();
// Functions::set_resource_status_to_force_harvest($resource_id);



class GlobiConnector
{
    const DUMP_URL = "/Users/pleary/Downloads/eol-globi-datasets-1.0-SNAPSHOT/csv-all";

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
    }

    public function build_archive()
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->taxon_ids_to_lookup = array_flip(array('EOL:328615', 'EOL:206777', 'EOL:225829'));
        $this->occurrence_ids_to_lookup = array();
        $this->archive_reader = new ContentArchiveReader(null, '/Users/pleary/Downloads/eol-globi-datasets-1.0-SNAPSHOT/csv-all/');
        // $this->archive_reader->process_row_type('http://eol.org/schema/Association', array($this, 'process_association'));
        $this->archive_reader->process_row_type('http://rs.tdwg.org/dwc/terms/Occurrence', array($this, 'process_occurrence_1'));
        $this->archive_reader->process_row_type('http://eol.org/schema/Association', array($this, 'process_association_1'));
        $this->archive_reader->process_row_type('http://rs.tdwg.org/dwc/terms/Occurrence', array($this, 'process_occurrence_2'));
        $this->archive_reader->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon', array($this, 'process_taxon'));
        $this->archive_reader->process_row_type('http://rs.tdwg.org/dwc/terms/Occurrence', array($this, 'process_occurrence'));
        $this->archive_reader->process_row_type('http://eol.org/schema/Association', array($this, 'process_association'));
        $this->archive_reader->process_row_type('http://rs.tdwg.org/dwc/terms/MeasurementOrFact', array($this, 'process_measurement'));
        $this->archive_builder->finalize(true);
    }

    function process_occurrence_1($row)
    {
        self::debug_iterations('Occurrence1', 5000);
        if(isset($this->taxon_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/taxonID']]))
        {
            $this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']] = true;
        }
    }

    function process_association_1($row)
    {
        self::debug_iterations('Association1', 5000);
        $row['http://rs.tdwg.org/dwc/terms/occurrenceID'] = str_replace(':source', '', $row['http://rs.tdwg.org/dwc/terms/occurrenceID']);
        $row['http://eol.org/schema/targetOccurrenceID'] = str_replace(':target', '', $row['http://eol.org/schema/targetOccurrenceID']);
        if(isset($this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']]))
        {
            $this->occurrence_ids_to_lookup[$row['http://eol.org/schema/targetOccurrenceID']] = true;
        }
        if(isset($this->occurrence_ids_to_lookup[$row['http://eol.org/schema/targetOccurrenceID']]))
        {
            $this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']] = true;
        }
    }

    function process_occurrence_2($row)
    {
        self::debug_iterations('Occurrence2', 5000);
        if(isset($this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']]))
        {
            $this->taxon_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/taxonID']] = true;
        }
    }

    function process_taxon($row)
    {
        self::debug_iterations('Taxon', 5000);
        if(isset($this->taxon_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/taxonID']]))
        {
            $t = new \eol_schema\Taxon($row);
            $this->archive_builder->write_object_to_file($t);
        }
    }

    function process_occurrence($row)
    {
        self::debug_iterations('Occurrence', 5000);
        if(isset($this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']]))
        {
            $o = new \eol_schema\Occurrence($row);
            $this->archive_builder->write_object_to_file($o);
        }
    }

    function process_association($row)
    {
        self::debug_iterations('Association', 5000);
        $row['http://rs.tdwg.org/dwc/terms/occurrenceID'] = str_replace(':source', '', $row['http://rs.tdwg.org/dwc/terms/occurrenceID']);
        $row['http://eol.org/schema/targetOccurrenceID'] = str_replace(':target', '', $row['http://eol.org/schema/targetOccurrenceID']);
        if(isset($this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']]) ||
           isset($this->occurrence_ids_to_lookup[$row['http://eol.org/schema/targetOccurrenceID']]))
        {
            $a = new \eol_schema\Association($row);
            $this->archive_builder->write_object_to_file($a);
        }
    }

    function process_measurement($row)
    {
        self::debug_iterations('Measurement', 5000);
        if(isset($this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']]))
        {
            $m = new \eol_schema\MeasurementOrFact($row);
            if($m->measurementOfTaxon == 'yes') $m->measurementOfTaxon = 'true';
            else $m->measurementOfTaxon = 'false';
            $this->archive_builder->write_object_to_file($m);
        }
    }

    private static function debug_iterations($message_prefix, $iteration_size = 500)
    {
        static $iteration_counts = array();
        if(!isset($iteration_counts[$message_prefix])) $iteration_counts[$message_prefix] = 0;
        if($iteration_counts[$message_prefix] % $iteration_size == 0)
        {
            if($GLOBALS['ENV_DEBUG']) echo $message_prefix ." $iteration_counts[$message_prefix]: ". memory_get_usage() .": ". time_elapsed() ."\n";
        }
        $iteration_counts[$message_prefix]++;
    }

}


?>