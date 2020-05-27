<?php
namespace php_active_record;
class Globi_Refuted_Records
{
    function __construct()
    {

        /*
        (a) Records of non-carnivorous plants eating animals are likely to be errors
        (b) Records of plants parasitizing animals are likely to be errors
        (c) Records of plants having animals as hosts are likely to be errors
        (d) Records of plants pollinating or visiting flowers of any other organism are likely to be errors
        (e) Records of plants laying eggs are likely to be errors
        (f) Records of other organisms parasitizing or eating viruses are likely to be errors
        */
    }
    public function initialize_report()
    {
        $this->report['cols'] = array('identifier', 'argumentTypeId', 'argumentTypeName', 'argumentReasonID', 'argumentReasonName', 'interactionTypeId', 'interactionTypeName', 
            'referenceCitation', 'sourceCitation', 'sourceArchiveURI', 'sourceTaxonId', 'sourceTaxonName', 'sourceTaxonRank', 'sourceTaxonKingdomName', 'targetTaxonId', 
            'targetTaxonName', 'targetTaxonRank', 'targetTaxonKingdomName');
        $this->report['destination'] = CONTENT_RESOURCE_LOCAL_PATH.'GloBI_Refuted_Records.tsv';

        $this->reason['EOL-GloBI-validation1'] = 'Records of non-carnivorous plants eating animals are likely to be errors';
        $this->reason['EOL-GloBI-validation2'] = 'Records of plants parasitizing animals are likely to be errors';
        $this->reason['EOL-GloBI-validation3'] = 'Records of plants having animals as hosts are likely to be errors';
        $this->reason['EOL-GloBI-validation4'] = 'Records of plants pollinating or visiting flowers of any other organism are likely to be errors';
        $this->reason['EOL-GloBI-validation5'] = 'Records of plants laying eggs are likely to be errors';
        $this->reason['EOL-GloBI-validation6'] = 'Records of other organisms parasitizing or eating viruses are likely to be errors';


        $f = Functions::file_open($this->report['destination'], "w");
        $row = implode("\t", $this->report['cols']);
        fwrite($f, $row . "\n");
        fclose($f);
    }
    public function write_refuted_report($rec, $argument_Reason_index) //2nd param, either 1,2,3,4,5 or 6
    {
        // print_r($rec); exit("\n[$rep_name]\n");
        $write = self::assemble_records($rec, $argument_Reason_index);
    }
    private function assemble_records($rec, $argument_Reason_index)
    {   /*Array(
            [http://eol.org/schema/associationID] => globi:assoc:2051236-EOL:221296-ATE-GBIF:8906629
            [http://rs.tdwg.org/dwc/terms/occurrenceID] => globi:occur:source:2051236-EOL:221296-ATE
            [http://eol.org/schema/associationType] => http://purl.obolibrary.org/obo/RO_0002470
            [http://eol.org/schema/targetOccurrenceID] => globi:occur:target:2051236-EOL:221296-ATE-GBIF:8906629
            [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
            [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
            [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
            [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
            [http://purl.org/dc/terms/source] => http://gomexsi.tamucc.edu
            [http://purl.org/dc/terms/bibliographicCitation] => 
            [http://purl.org/dc/terms/contributor] => 
            [http://eol.org/schema/reference/referenceID] => globi:ref:2051236
        )
        [Records of other organisms parasitizing or eating viruses are likely to be errors]
    
        This file should have the following columns:
        identifier *
        argumentTypeId *
        argumentTypeName *
        argumentReasonID *
        argumentReasonName *
        interactionTypeId **
        interactionTypeName *
        referenceCitation **
        sourceCitation *
        sourceArchiveURI *
        
        sourceTaxonId
        sourceTaxonName
        sourceTaxonRank
        sourceTaxonKingdomName
        
        targetTaxonId
        targetTaxonName
        targetTaxonRank
        targetTaxonKingdomName
        */
        $e = array();
        $e['identifier'] = 'EOLrefute_'.$rec['http://eol.org/schema/associationID'];
        $e['argumentTypeId'] = 'https://en.wiktionary.org/wiki/refute';
        $e['argumentTypeName'] = 'refute';
        $e['argumentReasonID'] = 'EOL-GloBI-validation'.$argument_Reason_index;
        $e['argumentReasonName'] = $this->reason[$e['argumentReasonID']];
            $e['interactionTypeId'] = $rec['http://eol.org/schema/associationType'];
        $e['sourceCitation'] = 'Biotic interaction data that failed Encyclopedia of Life data validation';
        $e['sourceArchiveURI'] = 'https://github.com/globalbioticinteractions/refuted-biotic-interactions-by-eol/blob/master/interactions.tsv'; //or OpenData
            $e['sourceTaxonId'] = 
        $e['interactionTypeName'] = self::get_interactionTypeName($rec['http://eol.org/schema/associationType']);
            $e['referenceCitation'] = $rec['http://purl.org/dc/terms/source'];
        
        
        $occurrenceID       = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
        $targetOccurrenceID = $rec['http://eol.org/schema/targetOccurrenceID'];

        if($source_taxonID = $this->occurrenceIDS[$occurrenceID]) {
            $e['sourceTaxonId'] = $this->taxonIDS[$source_taxonID]['taxonID'];
            $e['sourceTaxonName'] = $this->taxonIDS[$source_taxonID]['sciname'];
            $e['sourceTaxonRank'] = $this->taxonIDS[$source_taxonID]['taxonRank'];
            $e['sourceTaxonKingdomName'] = $this->taxonIDS[$source_taxonID]['kingdom'];
        }
        else {
            print_r($rec);
            echo "\n".$e['argumentReasonID']."-".$e['argumentReasonName']."\n";
            exit("\nNo taxonID for this source occurID [$occurrenceID]\n");
        }
        
        if($target_taxonID = $this->targetOccurrenceIDS[$targetOccurrenceID]) {
            $e['targetTaxonId'] = $this->taxonIDS[$target_taxonID]['taxonID'];
            $e['targetTaxonName'] = $this->taxonIDS[$target_taxonID]['sciname'];
            $e['targetTaxonRank'] = $this->taxonIDS[$target_taxonID]['taxonRank'];
            $e['targetTaxonKingdomName'] = $this->taxonIDS[$target_taxonID]['kingdom'];
        }
        else {
            print_r($rec);
            echo "\n".$e['argumentReasonID']."-".$e['argumentReasonName']."\n";
            exit("\nNo taxonID for this target occurID [$targetOccurrenceID]\n");
        }
        
        print_r($e);
        /*
        interactionTypeId --> association.tab:associationType
        referenceCitation --> association.tab:source
        
        sourceTaxonId --> taxon.tab:taxonID (for the taxon from the occurrenceID)
        sourceTaxonName --> taxon.tab:scientificName (for the taxon from the occurrenceID)
        sourceTaxonRank --> taxon.tab:taxonRank (for the taxon from the occurrenceID)
        sourceTaxonKingdomName --> taxon.tab:kingdom (for the taxon from the occurrenceID)
        
        targetTaxonId --> taxon.tab:taxonID (for the taxon from the targetOccurrenceID)
        targetTaxonName --> taxon.tab:scientificName (for the taxon from the targetOccurrenceID)
        targetTaxonRank --> taxon.tab:taxonRank (for the taxon from the targetOccurrenceID)
        targetTaxonKingdomName --> taxon.tab:kingdom (for the taxon from the targetOccurrenceID)
        */
        /*
        *identifier -> this should just be an identifier for the refutation record. Each identifier should be unique. Maybe we should use something like EOLrefute* 
            where * is the value from association.tab:associationID. (not sure why some of this text gets crossed out, it shouldn't be)
        *argumentTypeId --> use value "https://en.wiktionary.org/wiki/refute" (without quotes) for all records
        *argumentTypeName --> use value "refute" (without quotes) for all records
        *argumentReasonID --> use something like EOL-GloBI-validation1 etc.
        *argumentReasonName --> use the the headline of the relevant validation rule in DATA-1853, e.g., "Records of non-carnivorous plants eating animals are likely to be errors"
        *sourceCitation --> Biotic interaction data that failed Encyclopedia of Life data validation
        *sourceArchiveURI --> TBD - url of wherever we put the Refuted Records file, either on OpenData or on github. 
            Jorrit has created a dataset template on github for us: https://github.com/globalbioticinteractions/refuted-biotic-interactions-by-eol/blob/master/interactions.tsv 
            We could add our data to the repository directly, or via another location using a resource re-direct in globi.json. 
            Please contact Jorrit (jhpoelen@xs4all.nl) directly to figure out the details.
        *interactionTypeName --> translate association.tab:associationType to associationType labels based on the following mappings:
        */
        
    }
    private function get_interactionTypeName($assoc_type)
    {
        $desc['http://purl.obolibrary.org/obo/RO_0002208'] = 'parasitoid of';
        $desc['http://purl.obolibrary.org/obo/RO_0002209'] = 'has parasitoid';
        $desc['http://purl.obolibrary.org/obo/RO_0002439'] = 'preys on';
        $desc['http://purl.obolibrary.org/obo/RO_0002440'] = 'symbiotically interacts with';
        $desc['http://purl.obolibrary.org/obo/RO_0002441'] = 'commensually interacts with';
        $desc['http://purl.obolibrary.org/obo/RO_0002442'] = 'mutualistically interacts with';
        $desc['http://purl.obolibrary.org/obo/RO_0002444'] = 'parasite of';
        $desc['http://purl.obolibrary.org/obo/RO_0002445'] = 'parasitized by';
        $desc['http://purl.obolibrary.org/obo/RO_0002454'] = 'has host';
        $desc['http://purl.obolibrary.org/obo/RO_0002455'] = 'pollinates';
        $desc['http://purl.obolibrary.org/obo/RO_0002456'] = 'pollinated by';
        $desc['http://purl.obolibrary.org/obo/RO_0002458'] = 'preyed upon by';
        $desc['http://purl.obolibrary.org/obo/RO_0002459'] = 'is vector for';
        $desc['http://purl.obolibrary.org/obo/RO_0002460'] = 'has vector';
        $desc['http://purl.obolibrary.org/obo/RO_0002470'] = 'eats';
        $desc['http://purl.obolibrary.org/obo/RO_0002471'] = 'is eaten by';
        $desc['http://purl.obolibrary.org/obo/RO_0002553'] = 'hyperparasite of';
        $desc['http://purl.obolibrary.org/obo/RO_0002554'] = 'hyperparasitized by';
        $desc['http://purl.obolibrary.org/obo/RO_0002556'] = 'pathogen of';
        $desc['http://purl.obolibrary.org/obo/RO_0002557'] = 'has pathogen';
        $desc['http://purl.obolibrary.org/obo/RO_0002618'] = 'visits';
        $desc['http://purl.obolibrary.org/obo/RO_0002619'] = 'visited by';
        $desc['http://purl.obolibrary.org/obo/RO_0002622'] = 'visits flowers of';
        $desc['http://purl.obolibrary.org/obo/RO_0002623'] = 'has flowers visited by';
        $desc['http://purl.obolibrary.org/obo/RO_0002626'] = 'kills';
        $desc['http://purl.obolibrary.org/obo/RO_0002627'] = 'is killed by';
        $desc['http://purl.obolibrary.org/obo/RO_0002632'] = 'ectoparasite of';
        $desc['http://purl.obolibrary.org/obo/RO_0002633'] = 'has ectoparasite';
        $desc['http://purl.obolibrary.org/obo/RO_0002634'] = 'endoparasite of';
        $desc['http://purl.obolibrary.org/obo/RO_0002635'] = 'has endoparasite';
        $desc['http://purl.obolibrary.org/obo/RO_0008503'] = 'kleptoparasite of';
        $desc['http://purl.obolibrary.org/obo/RO_0008504'] = 'kleptoparasitized by';
        $desc['http://purl.obolibrary.org/obo/RO_0008507'] = 'lays eggs on';
        $desc['http://purl.obolibrary.org/obo/RO_0008508'] = 'has eggs laid on by';
        if($name = @$desc[$assoc_type]) return $name;
        else exit("\nInvestigate: No name mapping yet for [$assoc_type].\n");
    }
}
?>