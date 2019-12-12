<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from globi_data.php] */
class GloBIDataAPI
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
        
        //step 1 is build info list
        self::process_association($tables['http://eol.org/schema/association'][0], 'build info');       //generates $this->targetOccurrenceIDS $this->toDeleteOccurrenceIDS
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'build info');  //generates $this->taxonIDS AND assigns taxonID to $this->targetOccurrenceIDS
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);                          //assigns kingdom value to $this->taxonIDS
        //step 2 write extension
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'create extension'); //this is just to copy occurrence
        self::process_association($tables['http://eol.org/schema/association'][0], 'create extension'); //main operation in DATA-1812: For every record, create an additional record in reverse.
    }
    private function process_association($meta, $what)
    {   //print_r($meta);
        $OR = self::get_orig_reverse_uri();
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
            /*Array(
                [http://eol.org/schema/associationID] => globi:assoc:1-EOL:1000300-INTERACTS_WITH-EOL:1033696
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => globi:occur:source:1-EOL:1000300-INTERACTS_WITH
                [http://eol.org/schema/associationType] => http://purl.obolibrary.org/obo/RO_0002437
                [http://eol.org/schema/targetOccurrenceID] => globi:occur:target:1-EOL:1000300-INTERACTS_WITH-EOL:1033696
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://purl.org/dc/terms/source] => A. Thessen. 2014. Species associations extracted from EOL text data objects via text mining. Accessed at <associations_all_revised.txt> on 24 Jun 2019.
                [http://purl.org/dc/terms/bibliographicCitation] => 
                [http://purl.org/dc/terms/contributor] => 
                [http://eol.org/schema/reference/referenceID] => globi:ref:1
            )*/
            $associationType = $rec['http://eol.org/schema/associationType'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $targetOccurrenceID = $rec['http://eol.org/schema/targetOccurrenceID'];
            if($what == 'build info') {
                // http://purl.obolibrary.org/obo/RO_0002623 (flowers visited by)
                // http://purl.obolibrary.org/obo/RO_0002622 (visits flowers of)
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002623', 'http://purl.obolibrary.org/obo/RO_0002622'))) {
                    $this->targetOccurrenceIDS[$targetOccurrenceID] = '';
                }
                
                if($associationType == 'http://purl.obolibrary.org/obo/RO_0002437') { //delete all records of this associationType
                    $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                    $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                }
            }
            elseif($what == 'create extension') {
                /* first change request */
                if($associationType == 'http://purl.obolibrary.org/obo/RO_0002437') continue; //delete all records of this associationType
                /* second change request 
                if association_type == RO_0002623 (flowers visited by) AND targetOccurrenceID has a taxon with Plantae in the kingdom column
                then: replace association_type with RO_0002622

                if association_type == RO_0002622 (visits flowers of) AND targetOccurrenceID has a taxon with Animalia in the kingdom column
                then: replace association_type with RO_0002623
                */
                if($associationType == 'http://purl.obolibrary.org/obo/RO_0002623' && self::target_taxon_kingdom($targetOccurrenceID)=='Pl') $rec['http://eol.org/schema/associationType'] = 'http://purl.obolibrary.org/obo/RO_0002622';
                if($associationType == 'http://purl.obolibrary.org/obo/RO_0002622' && self::target_taxon_kingdom($targetOccurrenceID)=='An') $rec['http://eol.org/schema/associationType'] = 'http://purl.obolibrary.org/obo/RO_0002623';
                /* end second change request */
                //-----------------------------------------------------------------------------
                $o = new \eol_schema\Association();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                if($o->associationType == 'http://eol.org/schema/terms/DispersalVector') $o->associationType = 'http://eol.org/schema/terms/IsDispersalVectorFor'; //DATA-1841
                $this->archive_builder->write_object_to_file($o);

                /* now do the reverse when applicable:
                So what's needed in the resource: The only changes needed should be in the associations file. 
                For every record, create an additional record, with all the same metadata, and the same two occurrenceIDs, but switching which appears in which 
                column (occurrenceID and targetOccurrenceID). The value in relationshipType should change to the "reverse relationship". I'll make you a mapping.
                */
                if($reverse_type = @$OR[$o->associationType]) {
                    $o->associationID = 'ReverseOf_'.$o->associationID;
                    $o->occurrenceID = $rec['http://eol.org/schema/targetOccurrenceID'];
                    $o->targetOccurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                    $o->associationType = $reverse_type;
                    $this->archive_builder->write_object_to_file($o);
                }
                // if($i >= 10) break; //debug only
            }
        }
    }
    private function process_occurrence($meta, $what)
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
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => globi:occur:source:1-EOL:1000300-INTERACTS_WITH
                [http://rs.tdwg.org/dwc/terms/taxonID] => EOL:1000300
                [http://rs.tdwg.org/dwc/terms/institutionCode] => 
                [http://rs.tdwg.org/dwc/terms/collectionCode] => 
                [http://rs.tdwg.org/dwc/terms/catalogNumber] => 
                [http://rs.tdwg.org/dwc/terms/sex] => 
                [http://rs.tdwg.org/dwc/terms/lifeStage] => 
                [http://rs.tdwg.org/dwc/terms/reproductiveCondition] => 
                [http://rs.tdwg.org/dwc/terms/behavior] => 
                [http://rs.tdwg.org/dwc/terms/establishmentMeans] => 
                [http://rs.tdwg.org/dwc/terms/occurrenceRemarks] => 
                [http://rs.tdwg.org/dwc/terms/individualCount] => 
                [http://rs.tdwg.org/dwc/terms/preparations] => 
                [http://rs.tdwg.org/dwc/terms/fieldNotes] => 
                [http://rs.tdwg.org/dwc/terms/basisOfRecord] => 
                [http://rs.tdwg.org/dwc/terms/samplingProtocol] => 
                [http://rs.tdwg.org/dwc/terms/samplingEffort] => 
                [http://rs.tdwg.org/dwc/terms/identifiedBy] => 
                [http://rs.tdwg.org/dwc/terms/dateIdentified] => 
                [http://rs.tdwg.org/dwc/terms/eventDate] => 
                [http://purl.org/dc/terms/modified] => 
                [http://rs.tdwg.org/dwc/terms/locality] => 
                [http://rs.tdwg.org/dwc/terms/decimalLatitude] => 
                [http://rs.tdwg.org/dwc/terms/decimalLongitude] => 
                [http://rs.tdwg.org/dwc/terms/verbatimLatitude] => 
                [http://rs.tdwg.org/dwc/terms/verbatimLongitude] => 
                [http://rs.tdwg.org/dwc/terms/verbatimElevation] => 
                [http:/eol.org/globi/terms/physiologicalState] => 
                [http:/eol.org/globi/terms/bodyPart] => 
            )*/
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if($what == 'build info') {
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(isset($this->targetOccurrenceIDS[$occurrenceID])) {
                    $this->taxonIDS[$taxonID] = '';
                    $this->targetOccurrenceIDS[$occurrenceID] = $taxonID;
                }
            }
            elseif($what == 'create extension') {
                if(isset($this->toDeleteOccurrenceIDS[$occurrenceID])) continue;
                
                $o = new \eol_schema\Occurrence_specific();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
            
            // if($i >= 10) break; //debug only
        }
    }
    private function process_taxon($meta)
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
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => EOL:750524
                [http://rs.tdwg.org/dwc/terms/scientificName] => Copestylum vesicularium
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/kingdom] => Animalia
                [http://rs.tdwg.org/dwc/terms/phylum] => Arthropoda
                [http://rs.tdwg.org/dwc/terms/class] => Insecta
                [http://rs.tdwg.org/dwc/terms/order] => Diptera
                [http://rs.tdwg.org/dwc/terms/family] => Syrphidae
                [http://rs.tdwg.org/dwc/terms/genus] => Copestylum
                [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://eol.org/pages/750524
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => 
                [http://rs.tdwg.org/dwc/terms/taxonRemarks] => 
                [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
                [http://eol.org/schema/reference/referenceID] => 
            )*/
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if(isset($this->taxonIDS[$taxonID])) $this->taxonIDS[$taxonID] = substr($rec['http://rs.tdwg.org/dwc/terms/kingdom'],0,2);
        }
    }
    private function target_taxon_kingdom($targetOccurrenceID) //targetOccurrenceID points to a taxon with this kingdom value
    {
        if($taxonID = $this->targetOccurrenceIDS[$targetOccurrenceID]) {
            if($char = $this->taxonIDS[$taxonID]) {
                return $char; // An or Pl => Animalia or Plantae
            }
            elseif($taxonID == 'EOL:5536407') return 'Pl';
            else exit("\nInvestigate: this taxonID [$taxonID] does not have kingdom char\n");
        }
        else exit("\nInvestigate: this targetOccurrenceID [$targetOccurrenceID] does not have taxonID \n");
    }
    private function get_orig_reverse_uri()
    {
        $uri['http://purl.obolibrary.org/obo/RO_0002220'] = 'http://purl.obolibrary.org/obo/RO_0002220';
        $uri['http://purl.obolibrary.org/obo/RO_0008506'] = 'http://purl.obolibrary.org/obo/RO_0008506';
        $uri['http://purl.obolibrary.org/obo/RO_0002441'] = 'http://purl.obolibrary.org/obo/RO_0002441';
        $uri['http://purl.obolibrary.org/obo/GO_0044402'] = 'http://purl.obolibrary.org/obo/GO_0044402';
        $uri['http://purl.obolibrary.org/obo/RO_0008505'] = 'http://eol.org/schema/terms/HabitatCreatedBy';
        $uri['http://purl.obolibrary.org/obo/RO_0002470'] = 'http://purl.obolibrary.org/obo/RO_0002471';
        $uri['http://purl.obolibrary.org/obo/RO_0002632'] = 'http://purl.obolibrary.org/obo/RO_0002633';
        $uri['http://purl.obolibrary.org/obo/RO_0002634'] = 'http://purl.obolibrary.org/obo/RO_0002635';
        $uri['http://purl.obolibrary.org/obo/RO_0008501'] = 'http://purl.obolibrary.org/obo/RO_0008502';
        $uri['http://purl.obolibrary.org/obo/RO_0002623'] = 'http://purl.obolibrary.org/obo/RO_0002622';
        $uri['http://purl.obolibrary.org/obo/RO_0002633'] = 'http://purl.obolibrary.org/obo/RO_0002632';
        $uri['http://purl.obolibrary.org/obo/RO_0008508'] = 'http://purl.obolibrary.org/obo/RO_0008507';
        $uri['http://purl.obolibrary.org/obo/RO_0002635'] = 'http://purl.obolibrary.org/obo/RO_0002634';
        $uri['http://purl.obolibrary.org/obo/RO_0008502'] = 'http://purl.obolibrary.org/obo/RO_0008501';
        $uri['http://purl.obolibrary.org/obo/RO_0002554'] = 'http://purl.obolibrary.org/obo/RO_0002553';
        $uri['http://purl.obolibrary.org/obo/RO_0008503'] = 'http://purl.obolibrary.org/obo/RO_0008504';
        $uri['http://purl.obolibrary.org/obo/RO_0002209'] = 'http://purl.obolibrary.org/obo/RO_0002208';
        $uri['http://purl.obolibrary.org/obo/RO_0002557'] = 'http://purl.obolibrary.org/obo/RO_0002556';
        $uri['http://purl.obolibrary.org/obo/RO_0002460'] = 'http://purl.obolibrary.org/obo/RO_0002459';
        $uri['http://purl.obolibrary.org/obo/RO_0002553'] = 'http://purl.obolibrary.org/obo/RO_0002554';
        $uri['http://purl.obolibrary.org/obo/RO_0002437'] = 'http://purl.obolibrary.org/obo/RO_0002437';
        $uri['http://purl.obolibrary.org/obo/RO_0002471'] = 'http://purl.obolibrary.org/obo/RO_0002470';
        $uri['http://purl.obolibrary.org/obo/RO_0002627'] = 'http://purl.obolibrary.org/obo/RO_0002626';
        $uri['http://purl.obolibrary.org/obo/RO_0002459'] = 'http://purl.obolibrary.org/obo/RO_0002460';
        $uri['http://purl.obolibrary.org/obo/RO_0002626'] = 'http://purl.obolibrary.org/obo/RO_0002627';
        $uri['http://purl.obolibrary.org/obo/RO_0008507'] = 'http://purl.obolibrary.org/obo/RO_0008508';
        $uri['http://purl.obolibrary.org/obo/RO_0002442'] = 'http://purl.obolibrary.org/obo/RO_0002442';
        $uri['http://purl.obolibrary.org/obo/RO_0002444'] = 'http://purl.obolibrary.org/obo/RO_0002445';
        $uri['http://purl.obolibrary.org/obo/RO_0002445'] = 'http://purl.obolibrary.org/obo/RO_0002444';
        $uri['http://purl.obolibrary.org/obo/RO_0002208'] = 'http://purl.obolibrary.org/obo/RO_0002209';
        $uri['http://purl.obolibrary.org/obo/RO_0002556'] = 'http://purl.obolibrary.org/obo/RO_0002557';
        $uri['http://purl.obolibrary.org/obo/RO_0002456'] = 'http://purl.obolibrary.org/obo/RO_0002455';
        $uri['http://purl.obolibrary.org/obo/RO_0002455'] = 'http://purl.obolibrary.org/obo/RO_0002456';
        $uri['http://purl.obolibrary.org/obo/RO_0002458'] = 'http://purl.obolibrary.org/obo/RO_0002439';
        $uri['http://purl.obolibrary.org/obo/RO_0002439'] = 'http://purl.obolibrary.org/obo/RO_0002458';
        $uri['http://purl.obolibrary.org/obo/RO_0002440'] = 'http://purl.obolibrary.org/obo/RO_0002440';
        $uri['http://purl.obolibrary.org/obo/RO_0002619'] = 'http://purl.obolibrary.org/obo/RO_0002618';
        $uri['http://purl.obolibrary.org/obo/RO_0002618'] = 'http://purl.obolibrary.org/obo/RO_0002619';
        $uri['http://purl.obolibrary.org/obo/RO_0002622'] = 'http://purl.obolibrary.org/obo/RO_0002623';
        $uri['http://eol.org/schema/terms/HabitatCreatedBy'] = 'http://purl.obolibrary.org/obo/RO_0008505';
        $uri['http://purl.obolibrary.org/obo/RO_0008504'] = 'http://purl.obolibrary.org/obo/RO_0008503';
        $uri['http://eol.org/schema/terms/IsDispersalVectorFor'] = 'http://eol.org/schema/terms/HasDispersalVector';
        $uri['http://eol.org/schema/terms/HasDispersalVector'] = 'http://eol.org/schema/terms/IsDispersalVectorFor';
        return $uri;
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
