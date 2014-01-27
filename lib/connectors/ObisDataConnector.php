<?php
namespace php_active_record;

class ObisDataConnector
{
    const TAXA_FILE_PATH = "/Users/pleary/Downloads/datasets/OBIS_nov_2011/tnames_OBIS.csv";
    const DATA_FILE_PATH = "/Users/pleary/Downloads/datasets/OBIS_nov_2011/ranges_OBIS.csv";

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
    }

    public function build_archive()
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->setup();
        $this->taxon_names = array();
        $this->read_taxon_names();
        $this->read_data();
        $this->archive_builder->finalize(true);
    }


    public function read_taxon_names()
    {
        foreach(new FileIterator(self::TAXA_FILE_PATH) as $line_number => $line)
        {
            if($line_number % 1000 == 0) echo "$line_number :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $line_data = ContentArchiveReader::line_to_array($line, ",", "\"");
            $taxon_id = trim($line_data[0]);
            if(!$taxon_id) continue;
            $taxon_name = trim($line_data[1] ." ". $line_data[2]);
            if($taxon_name) $this->taxon_names[$taxon_id] = $taxon_name;
            else echo "No name on line: $line_number\n";
        }
    }

    public function read_data()
    {
        $this->column_labels = array();
        $this->column_indices = array();
        foreach(new FileIterator(self::DATA_FILE_PATH) as $line_number => $line)
        {
            if($line_number % 1000 == 0) echo "$line_number :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $line_data = ContentArchiveReader::line_to_array($line, ",", "\"");
            if($line_number == 0)
            {
                $this->column_labels = $line_data;
                foreach($this->column_labels as $k => $v) $this->column_indices[$v] = $k;
                continue;
            }
            $this->process_line_data($line_data);
        }
    }

    public function process_line_data($line_data)
    {
        $t = new \eol_schema\Taxon();
        $t->taxonID = $line_data[$this->column_indices['tname_id']];
        if(!$t->taxonID || @!$this->taxon_names[$t->taxonID]) return;

        $t->scientificName = $this->taxon_names[$t->taxonID];
        // if(!preg_match("/(Makaira nigricans|Clupea harengus|Globigerinoides quadrilobatus immaturus)/", $t->scientificName)) return;
        $this->archive_builder->write_object_to_file($t);

        $taxa_count = @$line_data[$this->column_indices['ntaxa']];
        if(!$taxa_count || $taxa_count == 1) $taxon_count_remark = null;
        else $taxon_count_remark = "$taxa_count taxa";

        $mindate = @$line_data[$this->column_indices['mindate']];
        $maxdate = @$line_data[$this->column_indices['maxdate']];
        $occurrence_date = implode(array_filter(array($mindate, $maxdate)), " to ");

        $latlong_occurrence = new \eol_schema\Occurrence();
        $latlong_occurrence->occurrenceID = md5($t->taxonID . 'latlong_occurrence');
        $latlong_occurrence->taxonID = $t->taxonID;
        if($latlong_count = @$line_data[$this->column_indices['n']]) $latlong_occurrence->individualCount = $latlong_count;
        if($taxon_count_remark) $latlong_occurrence->occurrenceRemarks = $taxon_count_remark;
        if($occurrence_date) $latlong_occurrence->eventDate = $occurrence_date;
        $this->archive_builder->write_object_to_file($latlong_occurrence);

        $depth_occurrence = new \eol_schema\Occurrence();
        $depth_occurrence->occurrenceID = md5($t->taxonID . 'depth_occurrence');
        $depth_occurrence->taxonID = $t->taxonID;
        if($depth_count = @$line_data[$this->column_indices['ndepth']]) $depth_occurrence->individualCount = $depth_count;
        if($taxon_count_remark) $depth_occurrence->occurrenceRemarks = $taxon_count_remark;
        if($occurrence_date) $depth_occurrence->eventDate = $occurrence_date;
        $this->archive_builder->write_object_to_file($depth_occurrence);

        $chemistry_occurrence = new \eol_schema\Occurrence();
        $chemistry_occurrence->occurrenceID = md5($t->taxonID . 'chemistry_occurrence');
        $chemistry_occurrence->taxonID = $t->taxonID;
        if($chemistry_count = @$line_data[$this->column_indices['nwoa']]) $chemistry_occurrence->individualCount = $chemistry_count;
        if($taxon_count_remark) $chemistry_occurrence->occurrenceRemarks = $taxon_count_remark;
        if($occurrence_date) $chemistry_occurrence->eventDate = $occurrence_date;
        $this->archive_builder->write_object_to_file($chemistry_occurrence);

        foreach($this->field_metadata as $field_name => $metadata)
        {
            if($metadata['type'] == 'latlong') $occurrence = $latlong_occurrence;
            elseif($metadata['type'] == 'depth') $occurrence = $depth_occurrence;
            elseif($metadata['type'] == 'chemistry') $occurrence = $chemistry_occurrence;
            else continue;
            $value = @trim($line_data[$this->column_indices[$field_name]]);
            if($value || $value === "0")
            {
                $m = new \eol_schema\MeasurementOrFact();
                $m->occurrenceID = $occurrence->occurrenceID;
                $m->measurementOfTaxon = 'true';
                $m->measurementType = $metadata['uri'];
                $m->measurementValue = $value;
                $m->statisticalMethod = $metadata['modifier_uri'];
                if(isset($metadata['unit_uri'])) $m->measurementUnit = $metadata['unit_uri'];
                $this->archive_builder->write_object_to_file($m);
            } // else echo "Skipping $field_name on $t->taxonID\n";
        }
    }

    private function setup()
    {
        $this->field_metadata = array(
            'minlat' => array(
                'type'          => 'latlong',
                'uri'           => 'http://rs.tdwg.org/dwc/terms/decimalLatitude',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000185'),
            'maxlat' => array(
                'type'          => 'latlong',
                'uri'           => 'http://rs.tdwg.org/dwc/terms/decimalLatitude',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000185'),
            'minlon' => array(
                'type'          => 'latlong',
                'uri'           => 'http://rs.tdwg.org/dwc/terms/decimalLongitude',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000185'),
            'maxlon' => array(
                'type'          => 'latlong',
                'uri'           => 'http://rs.tdwg.org/dwc/terms/decimalLongitude',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000185'),
            'mindepth' => array(
                'type'          => 'depth',
                'uri'           => 'http://rs.tdwg.org/dwc/terms/verbatimDepth',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000008'),
            'maxdepth' => array(
                'type'          => 'depth',
                'uri'           => 'http://rs.tdwg.org/dwc/terms/verbatimDepth',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000008'),
            'minnitrate' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/DissolvedNitrate',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000064'),
            'maxnitrate' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/DissolvedNitrate',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000064'),
            'mino2sat' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/OxygenSaturation',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000187'),
            'maxo2sat' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/OxygenSaturation',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000187'),
            'minoxygen' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/DissolvedOxygen',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000207'),
            'maxoxygen' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/DissolvedOxygen',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000207'),
            'minphosphate' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/DissolvedPhosphate',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000064'),
            'maxphosphate' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/DissolvedPhosphate',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000064'),
            'minsalinity' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/Salinity',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://eol.org/schema/terms/practicalSalinityUnits'),
            'maxsalinity' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/Salinity',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://eol.org/schema/terms/practicalSalinityUnits'),
            'minsilicate' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/DissolvedSilicate',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000064'),
            'maxsilicate' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/DissolvedSilicate',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000064'),
            'mintemperature' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/SeawaterTemperature',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000027'),
            'maxtemperature' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/SeawaterTemperature',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000027')
       );
    }
}

?>