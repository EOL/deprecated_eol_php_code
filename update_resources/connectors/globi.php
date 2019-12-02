<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$resource_id = 'globi';
$connector = new GlobiConnector($resource_id);
$connector->build_archive();

class GlobiConnector
{
    exit("\nSeems a long time ago with PLeary. Use globi_data.php instead.\n");
    const DUMP_URL = "/Users/pleary/Webroot/eol_php_code/temp/globi";

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
    }
    public function build_archive()
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        // $this->taxon_ids_to_lookup = array_flip(array('EOL:328615', 'EOL:206777', 'EOL:225829'));
        $this->taxon_ids_to_lookup = array();
        $this->occurrence_ids_to_lookup = array();
        $this->all_association_types = array();

        $this->archive_reader = new ContentArchiveReader(null, self::DUMP_URL);
        $this->archive_reader->process_row_type('http://rs.tdwg.org/dwc/terms/Occurrence', array($this, 'process_occurrence_1'));
        $this->archive_reader->process_row_type('http://eol.org/schema/Association', array($this, 'process_association_1'));
        $this->archive_reader->process_row_type('http://rs.tdwg.org/dwc/terms/Occurrence', array($this, 'process_occurrence_2'));
        $this->lookup_taxon_names();
        $this->archive_reader->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon', array($this, 'process_taxon'));
        $this->archive_reader->process_row_type('http://rs.tdwg.org/dwc/terms/Occurrence', array($this, 'process_occurrence'));
        $this->archive_reader->process_row_type('http://eol.org/schema/Association', array($this, 'process_association'));
        $this->archive_reader->process_row_type('http://rs.tdwg.org/dwc/terms/MeasurementOrFact', array($this, 'process_measurement'));
        $this->archive_reader->process_row_type('http://eol.org/schema/reference/Reference', array($this, 'process_reference'));
        $this->archive_builder->finalize(true);
        print_r($this->all_association_types);
    }
    function process_occurrence_1($row)
    {
        self::debug_iterations('Occurrence1', 5000);
        $this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']] = true;
    }
    function process_association_1($row)
    {
        self::debug_iterations('Association1', 5000);
        if(isset($this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']]))
        {
            $this->occurrence_ids_to_lookup[$row['http://eol.org/schema/targetOccurrenceID']] = true;
        }
        if(isset($this->occurrence_ids_to_lookup[$row['http://eol.org/schema/targetOccurrenceID']]))
        {
            $this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']] = true;
        }
        $this->all_association_types[$row['http://eol.org/schema/associationType']] = 1;
    }
    function process_occurrence_2($row)
    {
        self::debug_iterations('Occurrence2', 5000);
        if(isset($this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']]))
        {
            $this->taxon_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/taxonID']] = true;
        }
    }
    function lookup_taxon_names()
    {
        $this->taxon_concept_names = array();
        $batches = array_chunk(array_keys($this->taxon_ids_to_lookup), 10000);
        foreach($batches as $batch)
        {
            foreach($batch as $key => $id)
            {
                if($eol_id = self::eol_id($id)) $batch[$key] = $eol_id;
                else unset($batch[$key]);
            }
            $this->lookup_taxon_name_batch($batch);
        }
    }
    private function lookup_taxon_name_batch($taxon_concept_ids)
    {
        $entry_taxon_concept_ids = array();
        foreach($GLOBALS['db_connection']->iterate("
            SELECT pref.taxon_concept_id, he.id, n.string
            FROM taxon_concept_preferred_entries pref
            JOIN hierarchy_entries he ON (pref.hierarchy_entry_id=he.id)
            LEFT JOIN names n ON (he.name_id=n.id)
            WHERE pref.taxon_concept_id IN (". implode(",", $taxon_concept_ids) .")") as $row)
        {
            $entry_taxon_concept_ids[$row['id']] = $row['taxon_concept_id'];
            $this->taxon_concept_names[$row['taxon_concept_id']]['scientificName'] = $row['string'];
        }
    }
    function process_taxon($row)
    {
        self::debug_iterations('Taxon', 5000);
        if(isset($this->taxon_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/taxonID']]))
        {
            $t = @ new \eol_schema\Taxon($row);
            $id = $row['http://rs.tdwg.org/dwc/terms/taxonID'];
            $eol_id = self::eol_id($id);
            if($eol_id && $name = @$this->taxon_concept_names[$eol_id]['scientificName'])
            {
                $t->scientificName = $name;
                @$this->archive_builder->write_object_to_file($t);
            }
        }
    }
    function process_occurrence($row)
    {
        self::debug_iterations('Occurrence', 5000);
        if(isset($this->occurrence_ids_to_lookup[$row['http://rs.tdwg.org/dwc/terms/occurrenceID']]))
        {
            $o = @ new \eol_schema\Occurrence($row);
            $this->archive_builder->write_object_to_file($o);
        }
    }
    function process_association($row)
    {
        self::debug_iterations('Association', 5000);
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
    function process_reference($row)
    {
        self::debug_iterations('Reference', 5000);
        $r = @ new \eol_schema\Reference($row);
        $this->archive_builder->write_object_to_file($r);
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
    private static function eol_id($id)
    {
        if(preg_match("/^EOL:(.*)/", $id, $arr)) return $arr[1];
        return NULL;
    }
}
?>