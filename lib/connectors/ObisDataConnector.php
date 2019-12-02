<?php
namespace php_active_record;
/* connector: [692] One-time import.
Partner provides two CSV files, connector parses these files and generates the EOL archive.
We use WORMS taxonomy to get the rank info and remove those OBIS taxa with rank higher than family.
*/
class ObisDataConnector
{
    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;

        /* local
        $this->obis_csv_zip_file = "http://localhost/cp/OBIS/OBIS_ranges.zip";
        $this->worms_taxon_tab_zip_file = "http://localhost/cp/OBIS/worms_taxon.tab.zip";
        */
        $this->obis_csv_zip_file = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/OBIS/OBIS_ranges.zip";
        $this->worms_taxon_tab_zip_file = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/OBIS/worms_taxon.tab.zip";
        
        $this->text_path = array();
        $this->download_options = array('timeout' => 3600, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->excluded_ranks = array("kingdom", "subkingdom", "phylum", "subphylum", "class", "subclass", "infraclass", "superclass", "order", "suborder", "infraorder", "superorder");
    }
    public function build_archive()
    {
        $this->access_raw_data();
        
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $this->resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->setup();
        $this->taxon_names = array();
        $this->read_taxon_names();
        $this->read_data();
        $this->archive_builder->finalize(true);

        // remove temp dir
        self::remove_temp_dir($this->text_path["obis"]["tnames_OBIS"], "tnames_OBIS");
        self::remove_temp_dir($this->text_path["worms"]["worms_taxon"], "worms_taxon");
    }
    public function read_taxon_names()
    {
        foreach(new FileIterator($this->text_path["obis"]["tnames_OBIS"]) as $line_number => $line) {
            if($line_number % 1000 == 0) echo "$line_number :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $line_data = ContentArchiveReader::line_to_array($line, ",", "\"");
            $taxon_id = trim($line_data[0]);
            if(!$taxon_id) continue;
            if(!self::taxon_is_valid_based_on_worms(trim($line_data[1]))) continue;
            $taxon_name = trim($line_data[1] ." ". $line_data[2]);
            if($taxon_name) $this->taxon_names[$taxon_id] = $taxon_name;
            else echo "No name on line: $line_number\n";
        }
    }
    public function read_data()
    {
        $this->column_labels = array();
        $this->column_indices = array();
        foreach(new FileIterator($this->text_path["obis"]["ranges_OBIS"]) as $line_number => $line) {
            if($line_number % 1000 == 0) echo "$line_number :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $line_data = ContentArchiveReader::line_to_array($line, ",", "\"");
            if($line_number == 0) {
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
        $latlong_occurrence->occurrenceID = Functions::generate_measurementID($latlong_occurrence, $this->resource_id, 'occurrence');
        $this->archive_builder->write_object_to_file($latlong_occurrence);

        $depth_occurrence = new \eol_schema\Occurrence();
        $depth_occurrence->occurrenceID = md5($t->taxonID . 'depth_occurrence');
        $depth_occurrence->taxonID = $t->taxonID;
        if($depth_count = @$line_data[$this->column_indices['ndepth']]) $depth_occurrence->individualCount = $depth_count;
        if($taxon_count_remark) $depth_occurrence->occurrenceRemarks = $taxon_count_remark;
        if($occurrence_date) $depth_occurrence->eventDate = $occurrence_date;
        $depth_occurrence->occurrenceID = Functions::generate_measurementID($depth_occurrence, $this->resource_id, 'occurrence');
        $this->archive_builder->write_object_to_file($depth_occurrence);

        $chemistry_occurrence = new \eol_schema\Occurrence();
        $chemistry_occurrence->occurrenceID = md5($t->taxonID . 'chemistry_occurrence');
        $chemistry_occurrence->taxonID = $t->taxonID;
        if($chemistry_count = @$line_data[$this->column_indices['nwoa']]) $chemistry_occurrence->individualCount = $chemistry_count;
        if($taxon_count_remark) $chemistry_occurrence->occurrenceRemarks = $taxon_count_remark;
        if($occurrence_date) $chemistry_occurrence->eventDate = $occurrence_date;
        $chemistry_occurrence->occurrenceID = Functions::generate_measurementID($chemistry_occurrence, $this->resource_id, 'occurrence');
        $this->archive_builder->write_object_to_file($chemistry_occurrence);

        foreach($this->field_metadata as $field_name => $metadata) {
            if($metadata['type'] == 'latlong') $occurrence = $latlong_occurrence;
            elseif($metadata['type'] == 'depth') $occurrence = $depth_occurrence;
            elseif($metadata['type'] == 'chemistry') $occurrence = $chemistry_occurrence;
            else continue;
            $value = @trim($line_data[$this->column_indices[$field_name]]);
            
            if(in_array($field_name, array("mindepth", "maxdepth"))) {
                if($value > 11000) {
                    echo "\n $field_name = $value \n";
                    continue;
                }
            }
            
            if($value || $value === "0") {
                $m = new \eol_schema\MeasurementOrFact();
                $m->occurrenceID = $occurrence->occurrenceID;
                $m->measurementOfTaxon = 'true';
                $m->measurementType = $metadata['uri'];
                $m->measurementValue = $value;
                $m->statisticalMethod = $metadata['modifier_uri'];
                if(isset($metadata['unit_uri'])) $m->measurementUnit = $metadata['unit_uri'];
                $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
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
                'uri'           => 'http://eol.org/schema/terms/WaterTemperature', //'http://eol.org/schema/terms/SeawaterTemperature', obsolete uri DATA-1841
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001113',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000027'),
            'maxtemperature' => array(
                'type'          => 'chemistry',
                'uri'           => 'http://eol.org/schema/terms/WaterTemperature', //'http://eol.org/schema/terms/SeawaterTemperature', obsolete uri DATA-1841
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000027')
       );
    }
    private function access_raw_data()
    {
        require_library('connectors/IUCNRedlistDataConnector');
        $func = new IUCNRedlistDataConnector();
        $basenames = array("tnames_OBIS", "ranges_OBIS"); // csv files: taxa file, data file respectively
        $this->text_path["obis"] = $func->load_zip_contents($this->obis_csv_zip_file, $this->download_options, $basenames, ".csv");
        $this->text_path["worms"] = $func->load_zip_contents($this->worms_taxon_tab_zip_file, $this->download_options, array("worms_taxon"), ".tab");
        print_r($this->text_path);
        $this->worms_taxa = self::get_worms_taxa();
    }
    private function get_worms_taxa()
    {
        require_library('connectors/FishBaseAPI');
        $func = new FishBaseAPI();
        $fields = array();
        $excluded_fields = array("taxonID", "furtherInformationURL", "referenceID", "namePublishedIn", "taxonomicStatus", "taxonRemarks", "rightsHolder", "parentNameUsageID");
        $taxa = $func->make_array($this->text_path["worms"]["worms_taxon"], $fields, "", $excluded_fields);
        array_shift($taxa);
        foreach($taxa as $taxon) $final[Functions::canonical_form($taxon["scientificName"])] = $taxon["taxonRank"];
        unset($taxa);
        return $final;
    }
    private function taxon_is_valid_based_on_worms($canonical)
    {
        $canonical = trim(preg_replace('/\s*\([^)]*\)/', '', $canonical)); //remove parenthesis
        if(!isset($this->worms_taxa[$canonical])) {
            /* DATA-1435 - we will no longer exclude taxa in OBIS that are not in the WORMS resource.
            print "\n not found in WORMS [$canonical] will exclude";
            return false;
            */
        }
        else
        {
            if(in_array($this->worms_taxa[$canonical], $this->excluded_ranks))
            {
                print "\n rank not included [$canonical] - " . $this->worms_taxa[$canonical] . " will be excluded.";
                return false;
            }
        }
        if(in_array($canonical, array("Eutheria"))) return false; // based on DATA-1435, Jen's comment Jun 24, 2014
        return true;
    }
    private function remove_temp_dir($path, $basename)
    {
        $parts = pathinfo($path);
        $parts["dirname"] = str_ireplace($basename, "", $parts["dirname"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }
}
?>