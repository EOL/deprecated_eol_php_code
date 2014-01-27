<?php
namespace php_active_record;

class AnageDataConnector
{
    const DUMP_URL = "/Users/pleary/Downloads/datasets/anage_data.txt";

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        $this->taxon_ids = array();
        $this->occurrences = array();
    }

    public function build_archive()
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->setup();
        $this->column_labels = array();
        $this->column_indices = array();
        foreach(new FileIterator(self::DUMP_URL) as $line_number => $line)
        {
            if($line_number % 1000 == 0) echo "$line_number :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $line_data = explode("\t", $line);
            if($line_number == 0)
            {
                $this->column_labels = $line_data;
                foreach($this->column_labels as $k => $v) $this->column_indices[$v] = $k;
                continue;
            }
            $this->process_line_data($line_data);
            // if($line_number >= 1000) break;
        }
        $this->archive_builder->finalize(true);
    }

    public function process_line_data($line_data)
    {
        if($taxon = $this->add_taxon($line_data))
        {
            $this->add_numeric_data($line_data, $taxon);
        }
    }

    private function add_taxon($line_data)
    {
        $t = new \eol_schema\Taxon();
        $t->scientificName = trim($line_data[$this->column_indices['Genus']] ." ". $line_data[$this->column_indices['Species']]);
        $t->family = $line_data[$this->column_indices['Family']];
        $t->order = $line_data[$this->column_indices['Order']];
        $t->class = $line_data[$this->column_indices['Class']];
        $t->phylum = $line_data[$this->column_indices['Phylum']];
        $t->kingdom = $line_data[$this->column_indices['Kingdom']];
        // if(!preg_match("/^Mus musculus/", $t->scientificName)) return;
        $t->taxonID = md5($t->scientificName . $t->family . $t->order . $t->class . $t->phylum . $t->kingdom);
        $t->source = "http://genomics.senescence.info/species/entry.php?species=". str_replace(" ", "_", $t->scientificName);
        $this->archive_builder->write_object_to_file($t);

        if($v = $line_data[$this->column_indices['Common name']])
        {
            $vernacular = new \eol_schema\VernacularName();
            $vernacular->taxonID = $t->taxonID;
            $vernacular->vernacularName = $v;
            $vernacular->language = 'en';
            $this->archive_builder->write_object_to_file($vernacular);
        }
        return $t;
    }

    private function add_numeric_data($line_data, $taxon)
    {
        $longevity_occurrence = new \eol_schema\Occurrence();
        $longevity_occurrence->occurrenceID = md5($taxon->taxonID . "longevity_occurrence");
        $longevity_occurrence->taxonID = $taxon->taxonID;
        if($establishment_means = $line_data[$this->column_indices['Specimen origin']])
        {
            $longevity_occurrence->establishmentMeans = $establishment_means;
        }
        $this->archive_builder->write_object_to_file($longevity_occurrence);
        $this->add_sample_size($longevity_occurrence, $line_data);
        $this->add_data_quality($longevity_occurrence, $line_data);

        foreach($this->field_metadata as $field_name => $metadata)
        {
            if($metadata['type'] == 'longevity') $occurrence = $longevity_occurrence;
            else $occurrence = $this->build_occurrence($taxon, @$metadata['life_stage'], @$metadata['sex']);
            $value = @trim($line_data[$this->column_indices[$field_name]]);
            if($value == "999") continue;
            if($value || $value === "0")
            {
                $m = new \eol_schema\MeasurementOrFact();
                $m->occurrenceID = $occurrence->occurrenceID;
                $m->measurementOfTaxon = 'true';
                $m->measurementType = $metadata['uri'];
                $m->measurementValue = $value;
                if($metadata['type'] == 'longevity')
                {
                    $m->measurementRemarks = "Confidence in the longevity data (data quality) is classified as: 'low' (only used for species without an established maximum longevity in AnAge), 'questionable', 'acceptable' and 'high'. This qualifier is based on the reliability of the original reference from which maximum longevity was obtained, sample size, whether a given species has been studied and reproduces in captivity, and whether there are any conflicting reports.";
                }else
                {
                    $m->measurementRemarks = "Life history data based on reviews and large-scale datasets, see source for details.";
                }
                if($metadata['type'] == 'mortality')
                {
                    $m->measurementMethod = "The mortality rate doubling time (MRDT) was determined using the Gompertz equation, see http://genomics.senescence.info/software/demographic.html";
                }elseif($metadata['type'] == 'growth_rate')
                {
                    $m->measurementMethod = "Growth rate values represent postnatal growth rate and are expressed in days^-1. They were calculated by fitting empirical data taken from published growth curves to sigmoidal growth functions and are considered appropriate for comparative analyses within the same taxonomic class. Please be aware, however, that growth rates for mammals were derived from the Gompertz function while growth rates for birds were derived from the logistic function, so comparisons between the two classes need to take this into account.";
                }
                if(isset($metadata['modifier_uri'])) $m->statisticalMethod = $metadata['modifier_uri'];
                if(isset($metadata['unit_uri'])) $m->measurementUnit = $metadata['unit_uri'];
                $m->source = "http://genomics.senescence.info/species/entry.php?species=". str_replace(" ", "_", $taxon->scientificName);
                $this->archive_builder->write_object_to_file($m);
            } // else echo "Skipping $field_name on $t->taxonID\n";
        }
    }

    private function add_sample_size($occurrence, $line_data)
    {
        if($sample_size = $line_data[$this->column_indices['Sample size']])
        {
            if($sample_size == 'tiny') $sample_size = 'tiny (fewer than 10 specimens)';
            elseif($sample_size == 'small') $sample_size = 'small (10-100)';
            elseif($sample_size == 'medium') $sample_size = 'medium (100-1000)';
            elseif($sample_size == 'large') $sample_size = 'large (over 1000)';
            elseif($sample_size == 'hude') $sample_size = 'huge (millions)';
            $m = new \eol_schema\MeasurementOrFact();
            $m->occurrenceID = $occurrence->occurrenceID;
            $m->measurementType = "http://eol.org/schema/terms/SampleSize";
            $m->measurementValue = $sample_size;
            $this->archive_builder->write_object_to_file($m);
        }
    }

    private function add_data_quality($occurrence, $line_data)
    {
        if($data_quality = $line_data[$this->column_indices['Data quality']])
        {
            $m = new \eol_schema\MeasurementOrFact();
            $m->occurrenceID = $occurrence->occurrenceID;
            $m->measurementType = "http://purl.bioontology.org/ontology/CSP/5004-0024";
            $m->measurementValue = $data_quality;
            $this->archive_builder->write_object_to_file($m);
        }
    }

    private function build_occurrence($taxon, $life_stage, $sex)
    {
        if(isset($this->occurrences[$taxon->taxonID][$life_stage][$sex]))
        {
            return $this->occurrences[$taxon->taxonID][$life_stage][$sex];
        }
        $occurrence = new \eol_schema\Occurrence();
        $occurrence->occurrenceID = md5($taxon->taxonID . "occurrence" . $life_stage . $sex);
        $occurrence->taxonID = $taxon->taxonID;
        $occurrence->lifeStage = $life_stage;
        $occurrence->sex = $sex;
        $this->archive_builder->write_object_to_file($occurrence);
        $this->occurrences[$taxon->taxonID][$life_stage][$sex] = $occurrence;
        return $occurrence;
    }

    private function setup()
    {
        $this->field_metadata = array(
            'Adult weight (g)' => array(
                'type'          => '',
                'uri'           => 'http://purl.obolibrary.org/obo/PATO_0000128',
                'life_stage'    => 'http://www.ebi.ac.uk/efo/EFO_0001272',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000021'),
            'Female maturity (days)' => array(
                'type'          => '',
                'uri'           => 'http://purl.obolibrary.org/obo/VT_0002683',
                'sex'           => 'http://purl.obolibrary.org/obo/PATO_0000383',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000033'),
            'Male maturity (days)' => array(
                'type'          => '',
                'uri'           => 'http://purl.obolibrary.org/obo/VT_0002683',
                'sex'           => 'http://purl.obolibrary.org/obo/PATO_0000384',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000033'),
            'Weaning (days)' => array(
                'type'          => '',
                'uri'           => 'http://eol.org/schema/terms/WeaningAge',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000033'),
            'Birth weight (g)' => array(
                'type'          => '',
                'uri'           => 'http://purl.obolibrary.org/obo/PATO_0000128',
                'life_stage'    => 'http://purl.obolibrary.org/obo/UBERON_0007221',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000021'),
            'Metabolic rate (W)' => array(
                'type'          => '',
                'uri'           => 'http://purl.bioontology.org/ontology/SNOMEDCT/165109007',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000114'),
            'Body mass (g)' => array(
                'type'          => '',
                'uri'           => 'http://purl.obolibrary.org/obo/VT_0001259',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000021'),
            'Temperature (K)' => array(
                'type'          => '',
                'uri'           => 'http://purl.bioontology.org/ontology/CSP/2871-4249',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000012'),
            'Litter/Clutch size' => array(
                'type'          => '',
                'uri'           => 'http://purl.obolibrary.org/obo/VT_0001933'),
            'Litters/Clutches per year' => array(
                'type'          => '',
                'uri'           => 'http://eol.org/schema/terms/LittersPerYear',
                'unit_uri'      => 'http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#C74924'),
            'Growth rate (1/days)' => array(
                'type'          => 'growth_rate',
                'uri'           => 'http://purl.bioontology.org/ontology/SNOMEDCT/260865002',
                'unit_uri'      => 'http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#C66968'),
            'Gestation/Incubation (days)' => array(
                'type'          => '',
                'uri'           => 'http://eol.org/schema/terms/PrenatalDevelopmentDuration',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000033'),
            'IMR (per yr)' => array(
                'type'          => '',
                'uri'           => 'http://eol.org/schema/terms/InitialMortalityRate',
                'unit_uri'      => 'http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#C74924'),
            'Inter-litter/Interbirth interval' => array(
                'type'          => '',
                'uri'           => 'http://eol.org/schema/terms/InterBirthInterval',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000033'),
            'Maximum longevity (yrs)' => array(
                'type'          => 'longevity',
                'uri'           => 'http://purl.obolibrary.org/obo/VT_0001661',
                'modifier_uri'  => 'http://semanticscience.org/resource/SIO_001114',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000036'),
            'MRDT (yrs)' => array(
                'type'          => 'mortality',
                'uri'           => 'http://eol.org/schema/terms/MortalityRateDoublingTime',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000036'),
            'Weaning weight (g)' => array(
                'type'          => '',
                'uri'           => 'http://purl.obolibrary.org/obo/PATO_0000128',
                'life_stage'    => 'http://purl.bioontology.org/ontology/CSP/0070-2069',
                'unit_uri'      => 'http://purl.obolibrary.org/obo/UO_0000021')
       );
    }
}

?>