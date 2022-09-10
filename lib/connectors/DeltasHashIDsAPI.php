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
                                  "http://eol.org/schema/association"               => "association",
                                  "http://rs.tdwg.org/dwc/terms/taxon"              => "taxon",

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
        $extensions = array_keys($tables); //print_r($extensions); exit;
        /*Array(
            [0] => http://rs.gbif.org/terms/1.0/vernacularname
            [1] => http://rs.tdwg.org/dwc/terms/taxon
            [2] => http://rs.tdwg.org/dwc/terms/measurementorfact
            [3] => http://rs.tdwg.org/dwc/terms/occurrence
        )*/
        
        if(in_array($this->resource_id, array("71_delta", "15_delta"))) { //Wikimedia | Flickr
            $extensions = array_diff($extensions, array("http://rs.tdwg.org/dwc/terms/taxon", "http://eol.org/schema/agent/agent")); // print_r($extensions); exit;
            /*Array(
                [2] => http://eol.org/schema/media/document
            )*/
            // $extensions = array("http://eol.org/schema/media/document"); //debug only - force value
            foreach($extensions as $tbl) {
                $this->unique_ids = array();
                self::process_table($tables[$tbl][0], 'hash_identifiers', $this->extensions[$tbl]);
            }
        }
        elseif(in_array($this->resource_id, array("368_delta", "26_delta"))) { //PaleDB | WoRMS
            $this->unique_ids = array();
            $tbl = "http://rs.tdwg.org/dwc/terms/occurrence";
            self::process_Occurrence($tables[$tbl][0], 'hash_identifiers', $this->extensions[$tbl]);

            // /* new: to accommodate MoF child records
            $tbl = "http://rs.tdwg.org/dwc/terms/measurementorfact";
            self::process_MoF($tables[$tbl][0], 'pre_hash_identifiers', $this->extensions[$tbl]);
            // print_r($this->old_new_measurementID);
            // echo "\n".$this->old_new_measurementID['71aa534c631c2a69cd5487cee6028e35_26_ENV_final']."\n"; exit;
            // */
            
            $this->unique_ids = array();
            $tbl = "http://rs.tdwg.org/dwc/terms/measurementorfact";
            self::process_MoF($tables[$tbl][0], 'hash_identifiers', $this->extensions[$tbl]);
        }
        
        elseif(in_array($this->resource_id, array("globi_associations_delta"))) { //Globi
            $this->unique_ids = array();
            $tbl = "http://eol.org/schema/reference/reference";
            self::process_table($tables[$tbl][0], 'hash_identifiers', $this->extensions[$tbl]); //plain carry-over

            $this->unique_ids = array();
            $tbl = "http://rs.tdwg.org/dwc/terms/taxon";
            self::process_table($tables[$tbl][0], 'carry-over', $this->extensions[$tbl]); //carry-over but also to make taxa unique

            $this->unique_ids = array();
            $tbl = "http://rs.tdwg.org/dwc/terms/occurrence";
            self::process_Occurrence($tables[$tbl][0], 'hash_identifiers', $this->extensions[$tbl]);
            
            $this->unique_ids = array();
            $tbl = "http://eol.org/schema/association";
            self::process_Association($tables[$tbl][0], 'hash_identifiers', $this->extensions[$tbl]);
        }
        else exit("\nNot yet initialized 2.0 [$this->resource_id]\n");
    }
    private function process_Occurrence($meta, $what, $class)
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
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 73e24fc3724cf0b60ecdbf2c4eeed717_368
                [http://rs.tdwg.org/dwc/terms/taxonID] => 1
                [http://rs.tdwg.org/dwc/terms/lifeStage] => 
            )
            Array( --- GloBI
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => globi:occur:source:2-ITIS:554049-ATE
                [http://rs.tdwg.org/dwc/terms/taxonID] => ITIS:554049
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
                [http://rs.tdwg.org/dwc/terms/basisOfRecord] => 
                [http://eol.org/schema/terms/physiologicalState] => 
                [http://eol.org/schema/terms/bodyPart] => 
            )*/
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if($what == 'hash_identifiers') {
                /*
                if($class == "occurrence")  $o = new \eol_schema\Occurrence();
                elseif($class == "occurrence_specific")  $o = new \eol_schema\Occurrence_specific();
                else exit("\nUndefined class [$class]. Will terminate.\n");
                */
                $o = new \eol_schema\Occurrence_specific();
                $uris = array_keys($rec); // print_r($uris); //exit;
                $row_str = "";
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                    
                    if($this->resource_id == "globi_associations_delta") { //occurrence table is use solely for Associations
                        $row_str .= $rec[$uri]." | ";
                    }
                    /* may not need to do this. The problem is not this but this: https://eol-jira.bibalex.org/browse/DATA-1814?focusedCommentId=66717&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66717
                    elseif($this->resource_id == "368_delta") $row_str .= $rec[$uri]." | ";
                    */
                    else { //rest goes here
                        // /* there are Occurrence rows with same column values but diff. occurrenceID...
                        if($field != 'occurrenceID') $row_str .= $rec[$uri]." | ";
                        // */
                    }
                }
                
                $o->occurrenceID = md5($row_str); //exit("\n[$row_str][$row_str]\n");
                $this->old_new_occurID[$occurrenceID] = $o->occurrenceID; //new, for delta hashing...
                if(!isset($this->unique_ids[$o->occurrenceID])) {
                    $this->unique_ids[$o->occurrenceID] = '';
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            // if($i >= 10) break; //debug only
        }
    }
    private function process_MoF($meta, $what, $class)
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
            // if($i == 1954) print_r($rec); //exit;
            // if($i == 1955) print_r($rec); //exit;
            // if($i == 1956) print_r($rec); //exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 2a7071241876030d430ca5e2a48fbd36_368
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 73e24fc3724cf0b60ecdbf2c4eeed717_368
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/ExtinctionStatus
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://eol.org/schema/terms/extant
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://eol.org/schema/terms/statisticalMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://purl.org/dc/terms/source] => https://paleobiodb.org/classic/checkTaxonInfo?is_real_user=1&taxon_no=1
                [http://purl.org/dc/terms/bibliographicCitation] => The Paleobiology Database, https://paleobiodb.org
            )*/
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $measurementID = $rec['http://rs.tdwg.org/dwc/terms/measurementID'];
            if($what == 'pre_hash_identifiers') {
                $o = (object)[];
                $uris = array_keys($rec); // print_r($uris); //exit;
                $row_str = "";
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                    if($field != 'measurementID') $row_str .= $rec[$uri]." | ";
                }
                
                if($occurrenceID) { //not child records
                    if($new_occur_id = @$this->old_new_occurID[$occurrenceID]) $o->occurrenceID = $new_occur_id;
                    else exit("\nNo occur id: [$occurrenceID] Line no.: [$i]\n"); //should not go here
                }
                else { //child MoF records really don't have occurrenceID by design. Also include measurementID in md5 for MoF child records.
                    $row_str .= $measurementID." | ";
                }
                
                $o->measurementID = md5($row_str); //exit("\n[$row_str][$row_str]\n");
                $this->old_new_measurementID[$measurementID] = $o->measurementID;
            }
            if($what == 'hash_identifiers') {
                if($parentMeasurementID = @$rec['http://eol.org/schema/parentMeasurementID']) {
                    if($new_parent = $this->old_new_measurementID[$parentMeasurementID]) $rec['http://eol.org/schema/parentMeasurementID'] = $new_parent;
                    else exit("\nInvestigate, cannot link old and new parent IDs\n");
                }
                
                $o = new \eol_schema\MeasurementOrFact_specific();
                $uris = array_keys($rec); // print_r($uris); //exit;
                $row_str = "";
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                    if($field != 'measurementID') $row_str .= $rec[$uri]." | ";
                }
                
                if($occurrenceID) { //not child records
                    if($new_occur_id = @$this->old_new_occurID[$occurrenceID]) $o->occurrenceID = $new_occur_id;
                    else exit("\nNo occur id: [$occurrenceID] Line no.: [$i]\n"); //should not go here
                }
                else { //child MoF records really don't have occurrenceID by design. Also include measurementID in md5 for MoF child records.
                    $row_str .= $measurementID." | ";
                }
                
                $o->measurementID = md5($row_str); //exit("\n[$row_str][$row_str]\n");
                if(!isset($this->unique_ids[$o->measurementID])) {
                    $this->unique_ids[$o->measurementID] = '';
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            // if($i >= 10) break; //debug only
        }
    }


    private function process_Association($meta, $what, $class)
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
            // print_r($rec); exit;
            /*Array( --- GloBI
                [http://eol.org/schema/associationID] => globi:assoc:2-ITIS:554049-ATE-ITIS:24773
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => globi:occur:source:2-ITIS:554049-ATE
                [http://eol.org/schema/associationType] => http://purl.obolibrary.org/obo/RO_0002470
                [http://eol.org/schema/targetOccurrenceID] => globi:occur:target:2-ITIS:554049-ATE-ITIS:24773
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://purl.org/dc/terms/source] => Groom, Q.J., Maarten De Groot, M. & Marčiulynienė, D. (2020) Species interation data manually extracted from literature for species .
                [http://purl.org/dc/terms/bibliographicCitation] => 
                [http://purl.org/dc/terms/contributor] => 
                [http://eol.org/schema/reference/referenceID] => globi:ref:2
            )*/
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $targetOccurrenceID = $rec['http://eol.org/schema/targetOccurrenceID'];
            $associationID = $rec['http://eol.org/schema/associationID'];
            if($what == 'hash_identifiers') {
                if($class == "association")   $o = new \eol_schema\Association();
                else exit("\nUndefined class 01 [$class]. Will terminate.\n");

                // /*
                if($old_referenceID = @$rec['http://eol.org/schema/reference/referenceID']) {
                    if($new_referenceID = @$this->old_new_referenceID[$old_referenceID]) $rec['http://eol.org/schema/reference/referenceID'] = $new_referenceID;
                }
                // */

                $uris = array_keys($rec); // print_r($uris); //exit;
                $row_str = "";
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                    if($field != 'associationID') $row_str .= $rec[$uri]." | ";
                }
                
                if($occurrenceID) {
                    if($new_occur_id = @$this->old_new_occurID[$occurrenceID]) $o->occurrenceID = $new_occur_id;
                    else exit("\nNo occur id: [$occurrenceID] Line no.: [$i]\n"); //should not go here
                }
                else { //child MoF records really don't have occurrenceID by design. Also include measurementID in md5 for MoF child records.
                    exit("\nNo occurrenceID really?: [$occurrenceID] Line no.: [$i]\n");
                    $row_str .= $associationID." | ";
                }

                if($targetOccurrenceID) {
                    if($new_occur_id = @$this->old_new_occurID[$targetOccurrenceID]) $o->targetOccurrenceID = $new_occur_id;
                    else exit("\nNo targetOccurrenceID: [$targetOccurrenceID] Line no.: [$i]\n"); //should not go here
                }
                else { //child MoF records really don't have occurrenceID by design. Also include measurementID in md5 for MoF child records.
                    exit("\nNo targetOccurrenceID really?: [$targetOccurrenceID] Line no.: [$i]\n");
                    $row_str .= $associationID." | ";
                }
                
                $o->associationID = md5($row_str); //exit("\n[$row_str][$row_str]\n");
                if(!isset($this->unique_ids[$o->associationID])) {
                    $this->unique_ids[$o->associationID] = '';
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            // if($i >= 10) break; //debug only
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
                // elseif($class == "taxon")       $o = new \eol_schema\Taxon(); --- not yet used here
                elseif($class == "document")    $o = new \eol_schema\MediaResource();
                else exit("\nUndefined class 02 [$class]. Will terminate.\n");

                // /*
                if($class == "reference") {
                    $old_identifier = $rec['http://purl.org/dc/terms/identifier'];
                }
                // */

                $uris = array_keys($rec); // print_r($uris); //exit;
                $row_str = "";
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                    if($field != 'identifier') $row_str .= $rec[$uri]." | ";
                }
                
                if($class != "vernacular") {
                    $o->identifier = md5($row_str); //exit("\n[$row_str][$row_str]\n");
                }
                
                // /* for storing hashed referenceID, to be used in other tables
                if($class == "reference") {
                    $this->old_new_referenceID[$old_identifier] = $o->identifier;
                }
                // */
                
                if(!isset($this->unique_ids[$o->identifier])) {
                    $this->unique_ids[$o->identifier] = '';
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            elseif($what == 'carry-over') { //1st client is GloBi taxon.tab
                if    ($class == "vernacular")  $o = new \eol_schema\VernacularName();
                elseif($class == "agent")       $o = new \eol_schema\Agent();
                elseif($class == "reference")   $o = new \eol_schema\Reference();
                elseif($class == "taxon")       $o = new \eol_schema\Taxon();
                elseif($class == "document")    $o = new \eol_schema\MediaResource();
                else exit("\nUndefined class 03 [$class]. Will terminate.\n");

                // /*
                if($old_referenceID = @$rec['http://eol.org/schema/reference/referenceID']) {
                    if($new_referenceID = @$this->old_new_referenceID[$old_referenceID]) $rec['http://eol.org/schema/reference/referenceID'] = $new_referenceID;
                }
                // */

                $uris = array_keys($rec); // print_r($uris); //exit;
                $row_str = "";
                foreach($uris as $uri) {
                    $field = self::get_field_from_uri($uri);
                    $o->$field = $rec[$uri];
                }
                
                // /*
                if($class == "taxon")           $unique_field = $o->taxonID;
                elseif($class == "vernacular")  $unique_field = md5($o->vernacularName.@$o->source.$o->language);
                else                            $unique_field = $o->identifier; //the rest goes here
                // */
                
                if(!isset($this->unique_ids[$unique_field])) {
                    $this->unique_ids[$unique_field] = '';
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            // if($i >= 10) break; //debug only
        }
    }
    private function get_field_from_uri($uri)
    {
        $field = pathinfo($uri, PATHINFO_BASENAME);
        $parts = explode("#", $field);
        if($parts[0]) $field = $parts[0];
        if(@$parts[1]) $field = $parts[1];
        return $field;
    }
}
?>