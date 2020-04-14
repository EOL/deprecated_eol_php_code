<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from globi_data.php] */
class GloBIDataAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        require_library('connectors/Eol_v3_API');
        $this->func_eol_v3 = new Eol_v3_API();
        
        $this->api['iNat taxon'] = 'https://api.inaturalist.org/v1/taxa/TAXON_ID';
        $this->api['GBIF taxon'] = 'http://api.gbif.org/v1/species/TAXON_ID';
        $this->download_options = array(
            'resource_id'        => 'iNat',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30*2, //maybe 2 months to expire
            'download_wait_time' => 750000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);
        $this->download_options_gbif = $this->download_options;
        $this->download_options_gbif['resource_id'] = 'gbif';
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        /* just testing...
        $url = 'https://editors.eol.org/eoearth/wiki/Main_Page';
        $options = $this->download_options;
        $options['download_attempts'] = 1;
        if($html = Functions::lookup_with_cache($url, $options)) {
            echo "\nstrlen: ".strlen($html)."\n";
        }
        exit("\n-end-\n");
        */
        
        $tables = $info['harvester']->tables; 
        
        //step 1 is build info list
        self::process_association($tables['http://eol.org/schema/association'][0], 'build info');       //generates $this->targetOccurrenceIDS $this->toDeleteOccurrenceIDS
                                                                                                        //generates $this->occurrenceIDS
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'build info');  //generates $this->taxonIDS AND assigns taxonID to $this->targetOccurrenceIDS
                                                                                                        //                              assigns taxonID to $this->occurrenceIDS
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'build info');            //assigns kingdom value to $this->taxonIDS
        //step 2 write extension
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'create extension'); //this is just to copy occurrence
        self::process_association($tables['http://eol.org/schema/association'][0], 'create extension'); //main operation in DATA-1812: For every record, create an additional record in reverse.
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'create extension');
        
        $tmp = array_keys($this->debug['hierarchy without kingdom']);
        $this->debug['hierarchy without kingdom'] = '';
        print_r($this->debug);
        sort($tmp);
        print_r($tmp);
    }
    private function process_association($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_association [$what]\n";
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
                
                // /* per Jen: https://eol-jira.bibalex.org/browse/DATA-1812?focusedCommentId=64696&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64696
                // Another filter for this resource, please! The situation is similar to the flower visitors you fixed earlier. 
                // And, like that case, this tweak should happen before reverse records are created
                // 
                // Now, apparently, plants are eating things. There are two relationship terms involved:
                // http://purl.obolibrary.org/obo/RO_0002470 (eats)
                // http://purl.obolibrary.org/obo/RO_0002439 (preys on)
                // 
                // I was going to do something more complicated, but it turns out there are barely any "true" records of carnivorous plants. 
                // Let's just remove all records with these predicates and a source taxon with Plantae in the Kingdom column.
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002470', 'http://purl.obolibrary.org/obo/RO_0002439'))) {
                    $this->occurrenceIDS[$occurrenceID] = '';
                }
                // */
                
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
                if($associationType == 'http://purl.obolibrary.org/obo/RO_0002623' && self::get_taxon_kingdom_4occurID($targetOccurrenceID, 'target')=='Pl') $rec['http://eol.org/schema/associationType'] = 'http://purl.obolibrary.org/obo/RO_0002622';
                if($associationType == 'http://purl.obolibrary.org/obo/RO_0002622' && self::get_taxon_kingdom_4occurID($targetOccurrenceID, 'target')=='An') $rec['http://eol.org/schema/associationType'] = 'http://purl.obolibrary.org/obo/RO_0002623';
                /* end second change request */

                // /* per: https://eol-jira.bibalex.org/browse/DATA-1812?focusedCommentId=64696&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64696
                // Another filter for this resource, please! The situation is similar to the flower visitors you fixed earlier. 
                // And, like that case, this tweak should happen before reverse records are created
                // 
                // Now, apparently, plants are eating things. There are two relationship terms involved:
                // http://purl.obolibrary.org/obo/RO_0002470 (eats)
                // http://purl.obolibrary.org/obo/RO_0002439 (preys on)
                // 
                // I was going to do something more complicated, but it turns out there are barely any "true" records of carnivorous plants. 
                // Let's just remove all records with these predicates and a source taxon with Plantae in the Kingdom column.
                if    ($associationType == 'http://purl.obolibrary.org/obo/RO_0002470' && self::get_taxon_kingdom_4occurID($occurrenceID, 'source')=='Pl') continue;
                elseif($associationType == 'http://purl.obolibrary.org/obo/RO_0002439' && self::get_taxon_kingdom_4occurID($occurrenceID, 'source')=='Pl') continue;
                // */

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
        echo "\nprocess_occurrence [$what]\n";
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
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if($what == 'build info') {
                if(isset($this->targetOccurrenceIDS[$occurrenceID])) {
                    $this->taxonIDS[$taxonID] = '';
                    $this->targetOccurrenceIDS[$occurrenceID] = $taxonID;
                }
                if(isset($this->occurrenceIDS[$occurrenceID])) {
                    $this->taxonIDS[$taxonID] = '';
                    $this->occurrenceIDS[$occurrenceID] = $taxonID;
                }
            }
            elseif($what == 'create extension') {
                if(isset($this->toDeleteOccurrenceIDS[$occurrenceID])) continue;
                
                $this->taxonIDhasOccurrence[$taxonID] = ''; //so we can only create taxon with occurrence.
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
    private function process_taxon($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_taxon [$what]\n";
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
            if($what == 'build info') {
                $kingdom = $rec['http://rs.tdwg.org/dwc/terms/kingdom'];
                if(isset($this->taxonIDS[$taxonID])) {
                    $this->taxonIDS[$taxonID]['kingdom'] = substr($kingdom,0,2);
                    $this->taxonIDS[$taxonID]['sciname'] = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
                    if(!$kingdom) {
                        //option 1
                        if($rec['http://rs.tdwg.org/dwc/terms/class'] == 'Actinopterygii') $this->taxonIDS[$taxonID]['kingdom'] = 'An';
                        else {
                            //option 2
                            $tmp = @$rec['http://rs.tdwg.org/dwc/terms/phylum'] . "_" .  @$rec['http://rs.tdwg.org/dwc/terms/class'] . "_" . @$rec['http://rs.tdwg.org/dwc/terms/order']
                                                                                . "_" . @$rec['http://rs.tdwg.org/dwc/terms/family'] . "_" . @$rec['http://rs.tdwg.org/dwc/terms/genus'];
                            
                            if(stripos($tmp, "Aves_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'An'; //string is found
                            elseif(stripos($tmp, "Magnoliophyta_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Pl'; //string is found
                            
                            $this->debug['hierarchy without kingdom'][$tmp] = '';
                        }
                    }
                }
            }
            elseif($what == 'create extension') {
                if(isset($this->taxonIDhasOccurrence[$taxonID])) {
                    $o = new \eol_schema\Taxon();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    $this->archive_builder->write_object_to_file($o);
                }
            }
        }
    }
    private function get_taxon_kingdom_4occurID($targetORsource_OccurrenceID, $targetORsource) //targetOccurrenceID points to a taxon with this kingdom value
    {
        $taxonID = false;
        if    ($targetORsource == 'target') $taxonID = $this->targetOccurrenceIDS[$targetORsource_OccurrenceID];
        elseif($targetORsource == 'source') $taxonID = $this->occurrenceIDS[$targetORsource_OccurrenceID];
        
        $sciname = @$this->taxonIDS[$taxonID]['sciname'];
        
        if($taxonID) {
            if($char = $this->taxonIDS[$taxonID]['kingdom']) {
                return $char; // An or Pl => Animalia or Plantae
            }
            elseif(in_array($taxonID, array('EOL:23306280', 'EOL:5051697', 'EOL:5536407', 'EOL:5231462', 'EOL:6922431', 'EOL:5540593', 'EOL_V2:5170411', 'EOL:107287', 
            'EOL_V2:5169796', 'EOL:2879598', 'IRMNG:11155392', 'EOL:5356331', 'EOL:703626', 'EOL:2865819', 'EOL_V2:5544078', 'EOL:5164786', 'EOL_V2:5426294', 
            'EOL:5024066', 'WD:Q5389420', 'EOL:29378842', 'EOL:40469587', 'EOL:71360', 'EOL:5744742', 'EOL:5631615', 'EOL_V2:6346627', 'EOL_V2:5350526', 'EOL_V2:5178076', 
            'EOL_V2:5349701', 'EOL_V2:5387667', 'EOL:5187953', 'EOL_V2:5664483', 'EOL_V2:5177870', 'EOL_V2:5386317', 'EOL_V2:5223689', 'EOL_V2:5666458', 'EOL_V2:5745926', 
            'EOL_V2:5745719', 'EOL_V2:5531579', 'EOL_V2:5223650', 'EOL_V2:5344435', 'EOL_V2:2879124', 'EOL_V2:5535347', 'EOL_V2:6191776', 'EOL_V2:5020941', 'EOL_V2:485027'))) return 'Pl';
            elseif(in_array($taxonID, array('EOL:5425400', 'EOL:55106', 'EOL:3832795', 'FBC:FB:SpecCode:5038', 'EOL:3682636', 'EOL:31599461', 'EOL:54655', 
            'EOL_V2:6272187', 'EOL_V2:3121417'))) return 'An';
            else {
                /*Array(
                    [does not have kingdom] => Array(
                            [INAT_TAXON:48460] => Life
                        )
                )
                */
                if(substr($taxonID,0,4) == 'EOL:' || substr($taxonID,0,7) == 'EOL_V2:') {
                    if(!isset($this->not_found_in_EOL[$taxonID])) {
                        if($val = self::get_kingdom_from_EOLtaxonID($taxonID)) return $val;
                        else {
                            $this->not_found_in_EOL[$taxonID] = '';
                            echo " - not found in EOL: $targetORsource - ";
                            $this->debug['does not have kingdom']['EOL'][$taxonID][$sciname] = ''; // echo("\nInvestigate: this taxonID [$taxonID] does not have kingdom char\n");
                            return;
                        }
                    }
                }
                elseif(substr($taxonID,0,11) == 'INAT_TAXON:') { //e.g. INAT_TAXON:900074
                    if($val = self::get_kingdom_from_iNATtaxonID($taxonID)) return $val;
                    else {
                        $this->debug['does not have kingdom']['INAT'][$taxonID][$sciname] = ''; // echo("\nInvestigate: this taxonID [$taxonID] does not have kingdom char\n");
                        return;
                    }
                }
                
                if($sciname = @$this->taxonIDS[$taxonID]['sciname']) {
                    if(stripos($sciname, " trees") !== false) return 'Pl'; //string is found
                    if(stripos($sciname, " shrubs") !== false) return 'Pl'; //string is found
                    if(stripos($sciname, " plants") !== false) return 'Pl'; //string is found
                }
                
                $this->debug['does not have kingdom']['not EOL INAT'][$taxonID][$sciname] = ''; // echo("\nInvestigate: this taxonID [$taxonID] does not have kingdom char\n");
            }
        }
        else exit("\nInvestigate: this $targetORsource OccurrenceID [$taxonID] does not have taxonID \n");
    }
    function get_kingdom_from_iNATtaxonID($taxonID, $options = array())
    {
        $id = str_replace('INAT_TAXON:', '', $taxonID);
        if(!$options) $options = $this->download_options;
        $url = str_replace("TAXON_ID", $id, $this->api['iNat taxon']);
        if($json = Functions::lookup_with_cache($url, $options)) {
            $arr = json_decode($json, true);
            $arr = $arr['results']; // print_r($arr); exit;
            foreach($arr as $a) {
                if(!@$a['ancestors']) continue;
                foreach(@$a['ancestors'] as $anc) {
                    if($anc['rank'] == 'kingdom' && $anc['name'] == 'Animalia') return 'An';
                    elseif($anc['rank'] == 'kingdom' && $anc['name'] == 'Plantae') return 'Pl';
                    elseif($anc['rank'] == 'kingdom') {
                        $this->debug['kingdom from iNat NOT Pl nor An'][$anc['name']] = '';
                        return $anc['name'];
                    }
                }
            }
        }
        else debug("\nnot found [$id] in iNaturalist()\n");
        return false;
    }
    function get_kingdom_from_EOLtaxonID($taxonID)
    {
        if(stripos($taxonID, "EOL:") !== false) $id = str_replace('EOL:', '', $taxonID); //string is found
        if(stripos($taxonID, "EOL_V2:") !== false) $id = str_replace('EOL_V2:', '', $taxonID); //string is found

        $options = array(
            'resource_id'        => 'eol_api_v3',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 750000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.1);

        if($arr = $this->func_eol_v3->search_eol_page_id($id, $options, 'Pages5')) {
            if($arr = $arr['taxonConcept']['taxonConcepts']) {
                // print_r($arr);
                foreach($arr as $rec) {
                    /*[0] => Array(
                                [identifier] => 8331858
                                [scientificName] => Blepharis capensis (L.f.) Pers.
                                [name] => Blepharis capensis (L.f.) Pers.
                                [nameAccordingTo] => EOL Dynamic Hierarchy 0.9
                                [canonicalForm] => <i>Blepharis capensis</i>
                                [sourceIdentifier] => -967795
                                [taxonRank] => species
                            )
                    */
                    if($rec['nameAccordingTo'] == 'Plant Forms, Habitat and Distribution') {
                        echo " [Pl]";
                        return 'Pl';
                    }
                }
                /* Let us try GBIF */
                foreach($arr as $rec) {
                    if($rec['nameAccordingTo'] == 'GBIF classification') {
                        $gbif_id = $rec['sourceIdentifier'];
                        if($kingdom = self::get_kingdom_from_gbif($gbif_id)) {
                            // echo "\nkingdom from GBIF: [$kingdom]\n";
                            $this->debug['kingdom from GBIF'][$kingdom] = '';
                            if($kingdom == 'Plantae') return 'Pl';
                            if($kingdom == 'Animalia') return 'An';
                            return $kingdom;
                        }
                        break;
                    }
                }
            }
        }
        // exit("\nNot found...\n");
        return false;
    }
    function get_kingdom_from_gbif($gbif_id, $options = array())
    {
        if(!isset($this->not_found_in_GBIF[$gbif_id])) {
            if(!$options) $options = $this->download_options_gbif;
            $url = str_replace("TAXON_ID", $gbif_id, $this->api['GBIF taxon']);
            if($json = Functions::lookup_with_cache($url, $options)) {
                $arr = json_decode($json, true);
                // print_r($arr); exit("\n-end gbif-\n");
                if($val = @$arr['kingdom']) return $val;
            }
            $this->not_found_in_GBIF[$gbif_id] = '';
        }
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
