<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from globi_data.php] */
class GloBIDataAPI extends Globi_Refuted_Records
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        require_library('connectors/Eol_v3_API');
        $this->func_eol_v3 = new Eol_v3_API();
        
        $this->api['iNat taxon'] = 'https://api.inaturalist.org/v1/taxa/TAXON_ID';
        $this->api['GBIF taxon'] = 'http://api.gbif.org/v1/species/TAXON_ID';
        $this->api['GBIF taxon 2'] = 'https://api.gbif.org/v1/species?name=SCINAME';
        $this->download_options = array(
            'resource_id'        => 'iNat',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 750000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);
        $this->download_options_gbif = $this->download_options;
        $this->download_options_gbif['resource_id'] = 'gbif';
        $this->Carnivorous_plant_whitelist = array('Aldrovanda', 'Brocchinia', 'Byblis', 'Catopsis', 'Cephalotus', 'Darlingtonia', 'Dionaea', 'Drosera', 'Drosophyllum', 'Genlisea',
                                                   'Heliamphora', 'Nepenthes', 'Philcoxia', 'Pinguicula', 'Roridula', 'Sarracenia', 'Stylidium', 'Triphyophyllum', 'Utricularia');
        $this->preferred_term_table = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/GloBI/reverse_assocs.csv';
        $this->excluded_ranks = array('class', 'infraclass', 'infrakingdom', 'infraorder', 'infraphylum', 'kingdom', 'order', 'phylum', 'subclass', 'subkingdom', 'suborder', 'subphylum', 'subtribe', 'superclass', 'superfamily', 'superkingdom', 'superorder', 'superphylum', 'division', 'domain', 'grandorder', 'parvorder', 'realm', 'subdivision', 'tribe');
        
        $this->debug = array();
    }
    /*================================================================= STARTS HERE ======================================================================*/
    private function get_preferred_term_info()
    {
        $options = $this->download_options;
        $options['cache'] = 1;
        $options['expire_seconds'] = 60*60*24; //1 day
        $csv_file = Functions::save_remote_file_to_local($this->preferred_term_table, $options);
        $i = 0;
        if(!$file = Functions::file_open($csv_file, "r")) return;
        while(!feof($file)) { $i++;
            $temp = fgetcsv($file);
            if(($i % 1000) == 0) echo "\nbatch $i";
            if($i == 1) {
                $fields = $temp; //print_r($fields);
                continue;
            }
            else {
                $rec = array();
                $k = 0;
                // 2 checks if valid record
                if(!$temp) continue;
                foreach($temp as $t) {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
            }
            // print_r($rec); exit;
            /* Array(
                [preferred.name] => commensal with
                [preferred.uri] => http://purl.obolibrary.org/obo/RO_0002441
                [reverse.name] => 
                [reverse.uri] => 
                [notes] => symmetrical
            )*/
            if($preferred = $rec['preferred.uri']) {
                $final[$preferred] = $preferred;
                if($reverse = $rec['reverse.uri']) {
                    $final[$reverse] = $preferred;
                    // print_r($final); exit;
                }
            }
        } // print_r($final);
        return $final;
    }
    function start($info)
    {
        /* just a test:
        $x = self::lookup_gbif_ancestor_using_sciname('Acacia', array(), 'kingdom');
        exit("\n-end-[$x]\n");
        */

        $this->preferred_term_info_list = self::get_preferred_term_info(); //for reverse order
        $this->initialize_report(); //for refuted records;
        $tables = $info['harvester']->tables; 
        
        // /* New filter: specific taxon to remove: https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=67098&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67098
        $this->exclude_taxonIDs["NCBI:32644"] = '';
        # from: https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=67104&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67104
        $excluded_unidentified_taxa = array("GBIF:102484797", "NCBI:47299", "NCBI:10535", "NCBI:694448", "GBIF:104159016", "EOL_V2:11783218", "NCBI:1214906", "NCBI:11309", "NCBI:2355", "NCBI:12054", "NCBI:10291", "NCBI:47173", "NCBI:47174", "NCBI:1654823", "NCBI:1631194", "NCBI:1631195", "NCBI:31931", "NCBI:1856564");
        foreach($excluded_unidentified_taxa as $id) $this->exclude_taxonIDs[$id] = '';
        # To do: create a script that detects taxon names with 'unidentified' string and include its taxon ID here.
        # Instead of manually doing it.
        // */
        
        // /* Remove all associations for Homo sapiens: https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=67241&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67241
        # GBIF:2436436	http://www.gbif.org/species/2436436			Homo sapiens		Animalia	Chordata	Mammalia	Primates	Hominidae	Homo	species		
        # EOL:327955	http://eol.org/pages/327955			Homo sapiens		Animalia	Chordata	Mammalia	Primates	Hominidae	Homo	species		
        # WD:Q15978631	https://www.wikidata.org/wiki/Q15978631			Homo sapiens								species		
        # NBN:NHMSYS0000376773	https://data.nbn.org.uk/Taxa/NHMSYS0000376773			Homo sapiens		Animalia	Chordata	Mammalia	Primates	Hominidae	Homo	species		
        # ITIS:180092	http://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=180092			Homo sapiens		Animalia	Chordata	Mammalia	Primates	Hominidae	Homo	
        # WORMS:1455977	https://www.marinespecies.org/aphia.php?p=taxdetails&id=1455977			Homo sapiens		Animalia	Chordata	Mammalia	Primates	Hominidae	Homo	species		

        # Hmm instead of the 6 Homo sapiens above, I removed the 7 covid names below instead:
        # GBIF:9207297	http://www.gbif.org/species/9207297			Severe acute respiratory syndrome-related coronavirus					Nidovirales	Coronaviridae	Betacoronavirus	species		
        # NCBI:694009	https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=694009			Severe acute respiratory syndrome-related coronavirus		Orthornavirae	Pisuviricota	Pisoniviricetes	Nidovirales	Coronaviridae	Betacoronavirus	species		
        # NCBI:1003835	https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=1003835			Severe fever with thrombocytopenia syndrome virus		Orthornavirae	Negarnaviricota	Ellioviricetes	Bunyavirales	Phenuiviridae	Bandavirus			
        # NCBI:2697049	https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=2697049			Severe acute respiratory syndrome coronavirus 2		Orthornavirae	Pisuviricota	Pisoniviricetes	Nidovirales	Coronaviridae	Betacoronavirus			
        # GBIF:10069196	http://www.gbif.org/species/10069196			Severe acute respiratory syndrome-related coronavirus					Nidovirales	Coronaviridae	Betacoronavirus	species		
        # INAT_TAXON:1081492	https://inaturalist.org/taxa/1081492			Severe acute respiratory syndrome-related coronavirus 2								
        # 24843470				Betacoronavirus Severe acute respiratory syndrome-related coronavirus		Viruses	Phylum not assigned	Class not assigned	NidoviraleCoronaviridae		species		
        $excluded_unidentified_taxa = array("GBIF:9207297", "NCBI:694009", "NCBI:1003835", "NCBI:2697049", "GBIF:10069196", "INAT_TAXON:1081492", "24843470");
        foreach($excluded_unidentified_taxa as $id) $this->exclude_taxonIDs[$id] = '';
        // */

        // /* remove scinames Animalia & Metazoa: Eli found manually 8 scientificNames: (n=8)
        // taxonID|furtherInformationURL|referenceID|parentNameUsageID|scientificName
        // http://taxon-concept.plazi.org/id/Metazoa/Pseudevoplitusroraimensis_Grazia_2002|http://taxon-concept.plazi.org/id/Metazoa/Pseudevoplitusroraimensis_Grazia_2002|||Metazoa||Metazoa||||||||
        // NCBI:33208|https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=33208|||Metazoa||Metazoa||||||kingdom||
        // http://taxon-concept.plazi.org/id/Animalia/Sympetrum_Newman_1833|http://taxon-concept.plazi.org/id/Animalia/Sympetrum_Newman_1833|||Animalia||Animalia||||||||
        // EOL:1|http://eol.org/pages/1|||Animalia||Animalia||||||kingdom||
        // ITIS:202423|http://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=202423|||Animalia||Animalia||||||kingdom||
        // GBIF:1|http://www.gbif.org/species/1|||Animalia||Animalia||||||kingdom||
        // INAT_TAXON:1|https://inaturalist.org/taxa/1|||Animalia||Animalia||||||kingdom||
        // NBN:NBNSYS0100001342|https://data.nbn.org.uk/Taxa/NBNSYS0100001342|||Animalia||Animalia||||||kingdom||
        $excluded_unidentified_taxa = array("http://taxon-concept.plazi.org/id/Metazoa/Pseudevoplitusroraimensis_Grazia_2002", "NCBI:33208", "http://taxon-concept.plazi.org/id/Animalia/Sympetrum_Newman_1833", "EOL:1", "ITIS:202423", "GBIF:1", "INAT_TAXON:1", "NBN:NBNSYS0100001342");
        $excluded_unidentified_taxa[] = "WORMS:2"; //newly added Dec 28, 2023

        foreach($excluded_unidentified_taxa as $id) $this->exclude_taxonIDs[$id] = '';
        // */
        
        /* Animalia & Metazoa entries as of Nov 20, 2023 (n=6)
        EOL:1	http://eol.org/pages/1			Animalia
        ITIS:202423	http://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=202423			Animalia
        GBIF:1	http://www.gbif.org/species/1			Animalia
        NBN:NBNSYS0100001342	https://data.nbn.org.uk/Taxa/NBNSYS0100001342			Animalia
        http://taxon-concept.plazi.org/id/Metazoa/Pseudevoplitusroraimensis_Grazia_2002	http://taxon-concept.plazi.org/id/Metazoa/Pseudevoplitusroraimensis_Grazia_2002			Metazoa
        NCBI:33208	https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=33208			Metazoa

        as of Dec 28, 2023
        GBIF:1	http://www.gbif.org/species/1			Animalia	
        NBN:NBNSYS0100001342	https://data.nbn.org.uk/Taxa/NBNSYS0100001342			Animalia	
        EOL:1	http://eol.org/pages/1			Animalia	
        ITIS:202423	http://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=202423			Animalia	
        WORMS:2	https://www.marinespecies.org/aphia.php?p=taxdetails&id=2			Animalia	

        NCBI:33208	https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=33208			Metazoa	
        http://taxon-concept.plazi.org/id/Metazoa/Pseudevoplitusroraimensis_Grazia_2002	http://taxon-concept.plazi.org/id/Metazoa/Pseudevoplitusroraimensis_Grazia_2002			Metazoa	
        */

        // /* New per Jen:
        echo "\nexclude_taxonIDs 1: ".count($this->exclude_taxonIDs)."\n";
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'build info 2'); //generates $this->exclude_taxonIDs
        echo "\nexclude_taxonIDs 2: ".count($this->exclude_taxonIDs)."\n";
        if(isset($this->exclude_taxonIDs["NCBI:32644"])) print("\nGood...will proceed.\n");
        else exit("\nSomething is wrong...will terminate.\n");
        // */


        
        //step 1 is build info list
        self::process_reference($tables['http://eol.org/schema/reference/reference'][0], 'build info'); //includes a carry-over portion
        self::process_association($tables['http://eol.org/schema/association'][0], 'build info');       //generates $this->targetOccurrenceIDS $this->toDeleteOccurrenceIDS
                                                                                                        //generates $this->occurrenceIDS
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'build info');  //generates $this->taxonIDS AND assigns taxonID to $this->targetOccurrenceIDS
                                                                                                        //                              assigns taxonID to $this->occurrenceIDS
                                                                                                        
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'build info');            //assigns kingdom value to $this->taxonIDS
        //step 2 write extension
        self::process_association($tables['http://eol.org/schema/association'][0], 'create extension'); //main operation in DATA-1812: For every record, create an additional record in reverse.
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'create extension'); //primarily to copy occurrence AND implement $this->toDeleteOccurrenceIDS
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'create extension');
        
        if(@$this->debug['hierarchy without kingdom']) {
            $tmp = array_keys($this->debug['hierarchy without kingdom']);
            $this->debug['hierarchy without kingdom'] = '';
            $this->debug['does not have kingdom'] = ''; //value removed coz too long in Jenkins output. Comment this line if you want to check taxa without kingdom.
            print_r($this->debug);
            if($tmp) {
                sort($tmp); print_r($tmp);
            }
        }
        //backup report with timestamp
        $source = CONTENT_RESOURCE_LOCAL_PATH.'interactions.tsv';
        $destination = CONTENT_RESOURCE_LOCAL_PATH.'interactions_'.date("Y_m_d_H_i").'.tsv';
        if(!copy($source, $destination)) echo "\nFailed to copy [$source]...\n";
    }
    private function process_association($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_association [$what]\n";
        $OR = self::get_orig_reverse_uri();
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            } // print_r($rec); exit;
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
            if($what == 'build info') { //process_association()
                // /* compiled build info --- May 22, 2020 - Katja - https://eol-jira.bibalex.org/browse/DATA-1853
                // source:
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002455', 'http://purl.obolibrary.org/obo/RO_0002618', 'http://purl.obolibrary.org/obo/RO_0008507'))) { //
                    $this->occurrenceIDS[$occurrenceID] = '';
                    $this->targetOccurrenceIDS[$targetOccurrenceID] = ''; //for refuted records use
                }
                // target:
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002623'))) { //
                    $this->targetOccurrenceIDS[$targetOccurrenceID] = '';
                }
                // both:
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002454', 'http://purl.obolibrary.org/obo/RO_0002622', 'http://purl.obolibrary.org/obo/RO_0002632', 
                                                    'http://purl.obolibrary.org/obo/RO_0002634', 'http://purl.obolibrary.org/obo/RO_0002444', 'http://purl.obolibrary.org/obo/RO_0008503', 
                                                    'http://purl.obolibrary.org/obo/RO_0002208', 'http://purl.obolibrary.org/obo/RO_0002556', 'http://purl.obolibrary.org/obo/RO_0002470', 
                                                    'http://purl.obolibrary.org/obo/RO_0002439'))) { //
                    $this->occurrenceIDS[$occurrenceID] = '';
                    $this->targetOccurrenceIDS[$targetOccurrenceID] = '';
                }
                // */
                
                // http://purl.obolibrary.org/obo/RO_0002623 (flowers visited by)
                // http://purl.obolibrary.org/obo/RO_0002622 (visits flowers of)
                
                // /* per Jen: https://eol-jira.bibalex.org/browse/DATA-1812?focusedCommentId=64696&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64696
                // Another filter for this resource, please! The situation is similar to the flower visitors you fixed earlier. 
                // And, like that case, this tweak should happen before reverse records are created
                // 
                // Now, apparently, plants are eating things. There are two relationship terms involved:
                // http://purl.obolibrary.org/obo/RO_0002470 (eats)
                // http://purl.obolibrary.org/obo/RO_0002439 (preys on)
                // 
                // */
                
                /* May 22, 2020 - Katja - https://eol-jira.bibalex.org/browse/DATA-1853
                1. REMOVE ALL RECORDS WITH VERY GENERAL associationType VALUES
                I think the current version of the connector already removes "biotically interacts with" (http://purl.obolibrary.org/obo/RO_0002437), 
                but we should also strip out records with the following unspecific associationType values:
                http://purl.obolibrary.org/obo/RO_0002220	adjacent to
                http://purl.obolibrary.org/obo/RO_0002321	ecologically related to
                http://purl.obolibrary.org/obo/RO_0008506	ecologically co-occurs with
                */
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002437', 'http://purl.obolibrary.org/obo/RO_0002220', 
                                                    'http://purl.obolibrary.org/obo/RO_0002321', 'http://purl.obolibrary.org/obo/RO_0008506'))) { //delete all records of this associationType
                    $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                    $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                }
            }
            elseif($what == 'create extension') { //process_association()
                /* first change request */
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002437', 'http://purl.obolibrary.org/obo/RO_0002220', 
                                                    'http://purl.obolibrary.org/obo/RO_0002321', 'http://purl.obolibrary.org/obo/RO_0008506',
                                                    'http://purl.obolibrary.org/obo/RO_0008505' //Jen: https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=65789&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65789
                                                    ))) { //delete all records of this associationType
                    $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                    $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                    continue;
                }

                /* START: second change request 
                if association_type == RO_0002623 (flowers visited by) AND targetTaxon is under kingdom "Plantae" or "Viridiplantae"
                    then: replace association_type with RO_0002622
                if association_type == RO_0002622 (visits flowers of) AND targetTaxon is under kingdom "Animalia" or "Metazoa"
                    then: replace association_type with RO_0002623
                */
                if($associationType == 'http://purl.obolibrary.org/obo/RO_0002623') {
                    $targetTaxon_kingdom = self::get_taxon_kingdom_4occurID($targetOccurrenceID, 'target');
                    if(self::kingdom_is_plants_YN($targetTaxon_kingdom)) {
                        $rec['http://eol.org/schema/associationType'] = 'http://purl.obolibrary.org/obo/RO_0002622';
                    }
                }
                if($associationType == 'http://purl.obolibrary.org/obo/RO_0002622') {
                    $targetTaxon_kingdom = self::get_taxon_kingdom_4occurID($targetOccurrenceID, 'target');
                    if(self::kingdom_is_animals_YN($targetTaxon_kingdom)) {
                        $rec['http://eol.org/schema/associationType'] = 'http://purl.obolibrary.org/obo/RO_0002623';
                    }
                }
                $associationType = $rec['http://eol.org/schema/associationType'];                
                /* END: second change request */

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
                
                // On May 22, 2020, this was commented. Replaced by an updated criteria by Katja below (3.a)
                // if    ($associationType == 'http://purl.obolibrary.org/obo/RO_0002470' && self::get_taxon_kingdom_4occurID($occurrenceID, 'source')=='Plantae') continue;
                // elseif($associationType == 'http://purl.obolibrary.org/obo/RO_0002439' && self::get_taxon_kingdom_4occurID($occurrenceID, 'source')=='Plantae') continue;
                
                // */

                /* May 22, 2020 - Katja - https://eol-jira.bibalex.org/browse/DATA-1853
                2. RECODE CERTAIN associationType VALUES
                (a) I thought we already had all records with associationType "pollinates" (http://purl.obolibrary.org/obo/RO_0002455) recoded to "visits flowers of" 
                (http://purl.obolibrary.org/obo/RO_0002622), but I still see 58454 records with associationType http://purl.obolibrary.org/obo/RO_0002455 in the current resource file here: https://opendata.eol.org/dataset/globi/resource/c8392978-16c2-453b-8f0e-668fbf284b61
                We should change all these records to associationType http://purl.obolibrary.org/obo/RO_0002622, and this should happen before we create the reverse records, 
                i.e., there should not be any "pollinated by" (http://purl.obolibrary.org/obo/RO_0002456) records in the EOL resource file, 
                all reverse records should be "has flowers visited by" (http://purl.obolibrary.org/obo/RO_0002623).
                */
                if($associationType = self::suggested_remaps_if_any($associationType)) {
                    $rec['http://eol.org/schema/associationType'] = $associationType;
                    $associationType = $rec['http://eol.org/schema/associationType'];
                }
                /*
                (b) For records where the sourceTaxon has kingdom "Viruses" and associationType is "eats" (http://purl.obolibrary.org/obo/RO_0002470), 
                please change the associationType to "pathogen of" (http://purl.obolibrary.org/obo/RO_0002556).
                */
                if($associationType == 'http://purl.obolibrary.org/obo/RO_0002470') { //eats RO_0002470
                    $sourceTaxon_kingdom = self::get_taxon_kingdom_4occurID($occurrenceID, 'source');
                    if(self::kingdom_is_viruses_YN($sourceTaxon_kingdom)) {
                        $rec['http://eol.org/schema/associationType'] = 'http://purl.obolibrary.org/obo/RO_0002556';
                        $associationType = $rec['http://eol.org/schema/associationType'];
                        // echo "\nFound: sourceTaxon is VIRUSES; assocType is 'eats' [$associationType]; change the associationType to 'pathogen of'...\n";
                        @$this->debug['stats']['change the associationType to pathogen_of']++;
                        /*
                        print_r($rec); exit("\nfound [$kingdom]...\n");
                        Array(
                            [http://eol.org/schema/associationID] => globi:assoc:6162107-GBIF:8809483-ATE-GBIF:3172291
                            [http://rs.tdwg.org/dwc/terms/occurrenceID] => globi:occur:source:6162107-GBIF:8809483-ATE
                            [http://eol.org/schema/associationType] => http://purl.obolibrary.org/obo/RO_0002556
                            [http://eol.org/schema/targetOccurrenceID] => globi:occur:target:6162107-GBIF:8809483-ATE-GBIF:3172291
                            [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                            [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                            [http://rs.tdwg.org/dwc/terms/measurementMethod] => 
                            [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                            [http://purl.org/dc/terms/source] => Food Webs and Species Interactions in the Biodiversity of UK and Ireland (Online). 2015. Data provided by Malcolm Storey. Also available from http://bioinfo.org.uk.
                            [http://purl.org/dc/terms/bibliographicCitation] => 
                            [http://purl.org/dc/terms/contributor] => 
                            [http://eol.org/schema/reference/referenceID] => globi:ref:6162107
                        )
                        found [Viruses]...*/
                    }
                }
                /*
                3. REMOVE RECORDS THAT REPRESENT ERRONEOUS ASSOCIATION CLAIMS
                (a)[1] Records of non-carnivorous plants eating animals are likely to be errors
                    sourceTaxon has kingdom "Plantae" OR "Viridiplantae" AND genus is not in whitelist
                AND targetTaxon has kingdom "Animalia" OR "Metazoa"
                AND associationType is "eats" (http://purl.obolibrary.org/obo/RO_0002470) OR 
                                   "preys on" (http://purl.obolibrary.org/obo/RO_0002439) */
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002470', 'http://purl.obolibrary.org/obo/RO_0002439'))) { //'eats' or 'preys on'
                    $taxonID = self::get_taxonID_given_occurID($occurrenceID, 'source');
                    if(!in_array($taxonID, self::special_list_of_not_plantae())) {
                        $sourceTaxon_kingdom = self::get_taxon_kingdom_4occurID($occurrenceID, 'source');
                        if(self::kingdom_is_plants_YN($sourceTaxon_kingdom)) {
                            $targetTaxon_kingdom = self::get_taxon_kingdom_4occurID($targetOccurrenceID, 'target');
                            if(self::kingdom_is_animals_YN($targetTaxon_kingdom)) {
                                $sourceTaxon_genus = self::get_taxon_ancestor_4occurID($occurrenceID, 'source', 'genus'); //3rd param is the rank of the ancestor being sought
                                if(!in_array($sourceTaxon_genus, $this->Carnivorous_plant_whitelist)) {
                                    // echo "\nFound: sourceTaxon is PLANT; targetTaxon is ANIMALIA; assocType is 'eats'/'preys on' [$associationType]; source_genus [$sourceTaxon_genus] not in whitelist...\n";
                                    @$this->debug['stats']['1. Records of non-carnivorous plants eating animals are likely to be errors']++;
                                    self::write_refuted_report($rec, 1);
                                    $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                                    $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                                    continue;
                                }
                            }
                        }
                    }
                }
                
                /* (b)[2] Records of plants parasitizing animals are likely to be errors
                    sourceTaxon has kingdom "Plantae" OR "Viridiplantae"
                AND targetTaxon has kingdom "Animalia" OR "Metazoa"
                AND associationType is "ectoparasite of" (http://purl.obolibrary.org/obo/RO_0002632) OR 
                                       "endoparasite of" (http://purl.obolibrary.org/obo/RO_0002634) OR 
                                       "parasite of" (http://purl.obolibrary.org/obo/RO_0002444) OR 
                                       "kleptoparasite of" (http://purl.obolibrary.org/obo/RO_0008503) OR 
                                       "parasitoid of" http://purl.obolibrary.org/obo/RO_0002208 OR 
                                       "pathogen of" (http://purl.obolibrary.org/obo/RO_0002556)
                */
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002632', 'http://purl.obolibrary.org/obo/RO_0002634', 'http://purl.obolibrary.org/obo/RO_0002444', 'http://purl.obolibrary.org/obo/RO_0008503', 'http://purl.obolibrary.org/obo/RO_0002208', 'http://purl.obolibrary.org/obo/RO_0002556'))) { //plants parasitizing animals
                    $taxonID = self::get_taxonID_given_occurID($occurrenceID, 'source');
                    if(!in_array($taxonID, self::special_list_of_not_plantae())) {
                        $sourceTaxon_kingdom = self::get_taxon_kingdom_4occurID($occurrenceID, 'source');
                        if(self::kingdom_is_plants_YN($sourceTaxon_kingdom)) {
                            $targetTaxon_kingdom = self::get_taxon_kingdom_4occurID($targetOccurrenceID, 'target');
                            if(self::kingdom_is_animals_YN($targetTaxon_kingdom)) {
                                // echo "\nFound: sourceTaxon is PLANT [$sourceTaxon_kingdom]; targetTaxon is ANIMALIA [$targetTaxon_kingdom]; [$associationType]; plants parasitizing animals...\n";
                                @$this->debug['stats']['2. Records of plants parasitizing animals are likely to be errors']++;
                                self::write_refuted_report($rec, 2);
                                $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                                $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                                continue;
                            }
                        }
                    }
                }
                
                /* (c)[3] Records of plants having animals as hosts are likely to be errors
                (we might have to create a whitelist if somebody gives us a dataset of algae living in sloth fur)
                    sourceTaxon has kingdom "Plantae" OR "Viridiplantae"
                AND targetTaxon has kingdom "Animalia" OR "Metazoa"
                AND associationType is "has host" (http://purl.obolibrary.org/obo/RO_0002454)
                */
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002454'))) { //plants having animals as hosts
                    $taxonID = self::get_taxonID_given_occurID($occurrenceID, 'source');

                    // /* new: Nov 20, 2023: per Jen: "I think we have a new source of high rank GloBI records, eg: https://eol.org/records/R20-PK113723253" 
                    // https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=67738&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67738
                    if(isset($this->exclude_taxonIDs[$taxonID])) $this->toDeleteOccurrenceIDS_Jen[$occurrenceID] = '';
                    $tmpID = self::get_taxonID_given_occurID($targetOccurrenceID, 'target');
                    if(isset($this->exclude_taxonIDs[$tmpID])) $this->toDeleteOccurrenceIDS_Jen[$targetOccurrenceID] = '';
                    // */

                    if(!in_array($taxonID, self::special_list_of_not_plantae())) {
                        $sourceTaxon_kingdom = self::get_taxon_kingdom_4occurID($occurrenceID, 'source');
                        if(self::kingdom_is_plants_YN($sourceTaxon_kingdom)) {
                            $targetTaxon_kingdom = self::get_taxon_kingdom_4occurID($targetOccurrenceID, 'target');
                            if(self::kingdom_is_animals_YN($targetTaxon_kingdom)) {
                                // echo "\nFound: sourceTaxon is PLANT; targetTaxon is ANIMALIA; [$associationType]; plants having animals as hosts...\n";
                                @$this->debug['stats']['3. Records of plants having animals as hosts are likely to be errors']++;
                                self::write_refuted_report($rec, 3);
                                $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                                $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                                continue;
                            }
                        }
                    }
                }
                
                /* (d)[4] Records of plants pollinating or visiting flowers of any other organism are likely to be errors
                sourceTaxon has kingdom "Plantae" OR "Viridiplantae"
                AND associationType is "pollinates" (http://purl.obolibrary.org/obo/RO_0002455) OR 
                                        visits (http://purl.obolibrary.org/obo/RO_0002618) OR 
                                        visits flowers of (http://purl.obolibrary.org/obo/RO_0002622)
                */
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002455', 'http://purl.obolibrary.org/obo/RO_0002618', 'http://purl.obolibrary.org/obo/RO_0002622'))) { //
                    $taxonID = self::get_taxonID_given_occurID($occurrenceID, 'source');
                    if(!in_array($taxonID, self::special_list_of_not_plantae())) {
                        $sourceTaxon_kingdom = self::get_taxon_kingdom_4occurID($occurrenceID, 'source');
                        if(self::kingdom_is_plants_YN($sourceTaxon_kingdom)) {
                            // echo "\nFound: sourceTaxon is PLANT; [$associationType]; plants pollinating or visiting flowers of any other organism...\n";
                            @$this->debug['stats']['4. Records of plants pollinating or visiting flowers of any other organism are likely to be errors']++;
                            self::write_refuted_report($rec, 4);
                            $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                            $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                            continue;
                        }
                    }
                }
                
                /* (e)[5] Records of plants laying eggs are likely to be errors
                sourceTaxon has kingdom "Plantae" OR "Viridiplantae"
                AND associationType is "lays eggs on" (http://purl.obolibrary.org/obo/RO_0008507)
                */
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0008507'))) { //
                    $taxonID = self::get_taxonID_given_occurID($occurrenceID, 'source');
                    if(!in_array($taxonID, self::special_list_of_not_plantae())) {
                        $sourceTaxon_kingdom = self::get_taxon_kingdom_4occurID($occurrenceID, 'source');
                        if(self::kingdom_is_plants_YN($sourceTaxon_kingdom)) {
                            // echo "\nFound: sourceTaxon is PLANT; [$associationType]; plants laying eggs...\n";
                            @$this->debug['stats']['5. Records of plants laying eggs are likely to be errors']++;
                            self::write_refuted_report($rec, 5);
                            $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                            $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                            continue;
                        }
                    }
                }
                
                /* Version 1.0: worked for the longest time
                (f)[6] Records of other organisms parasitizing or eating viruses are likely to be errors
                sourceTaxon does NOT have kingdom "Viruses"
                AND targetTaxon has kingdom "Viruses"
                AND associationType is "ectoparasite of" (http://purl.obolibrary.org/obo/RO_0002632) OR 
                                       "endoparasite of" (http://purl.obolibrary.org/obo/RO_0002634) OR 
                                       "parasite of" (http://purl.obolibrary.org/obo/RO_0002444) OR 
                                       "kleptoparasite of" (http://purl.obolibrary.org/obo/RO_0008503) OR 
                                       "parasitoid of" http://purl.obolibrary.org/obo/RO_0002208 OR 
                                       "pathogen of" (http://purl.obolibrary.org/obo/RO_0002556) OR 
                                       "eats" (http://purl.obolibrary.org/obo/RO_0002470) OR 
                                       "preys on" (http://purl.obolibrary.org/obo/RO_0002439)
                */
                // /*
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002632', 'http://purl.obolibrary.org/obo/RO_0002634', 'http://purl.obolibrary.org/obo/RO_0002444', 'http://purl.obolibrary.org/obo/RO_0008503', 'http://purl.obolibrary.org/obo/RO_0002208', 'http://purl.obolibrary.org/obo/RO_0002556', 'http://purl.obolibrary.org/obo/RO_0002470', 'http://purl.obolibrary.org/obo/RO_0002439'))) { //
                    $sourceTaxon_kingdom = self::get_taxon_kingdom_4occurID($occurrenceID, 'source');
                    if(!self::kingdom_is_viruses_YN($sourceTaxon_kingdom)) {
                        $targetTaxon_kingdom = self::get_taxon_kingdom_4occurID($targetOccurrenceID, 'target');
                        if(self::kingdom_is_viruses_YN($targetTaxon_kingdom)) {
                            // echo "\nFound: sourceTaxon is not VIRUSES; targetTaxon is VIRUSES; [$associationType]; organisms parasitizing or eating viruses...\n";
                            @$this->debug['stats']['6a. Records of other organisms parasitizing or eating viruses are likely to be errors']++;
                            self::write_refuted_report($rec, 6);
                            $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                            $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                            continue;
                        }
                    }
                }
                // */
                /* Version 2.0: DATA-1872 as of Dec 7, 2020
                (f)[6] Records of other organisms parasitizing or eating viruses are likely to be errors
                sourceTaxon has kingdom "Viruses"
                AND targetTaxon does NOT have kingdom "Viruses"
                AND associationType is:
                "has ectoparasite" (http://purl.obolibrary.org/obo/RO_0002633) OR
                "has endoparasite" (http://purl.obolibrary.org/obo/RO_0002635) OR
                "parasitized by" (http://purl.obolibrary.org/obo/RO_0002445) OR
                "kleptoparasitized by" (http://purl.obolibrary.org/obo/RO_0008504) OR
                "has parasitoid" (http://purl.obolibrary.org/obo/RO_0002209) OR
                "has pathogen" (http://purl.obolibrary.org/obo/RO_0002557)
                */
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002633', 'http://purl.obolibrary.org/obo/RO_0002635', 
                'http://purl.obolibrary.org/obo/RO_0002445', 'http://purl.obolibrary.org/obo/RO_0008504', 
                'http://purl.obolibrary.org/obo/RO_0002209', 'http://purl.obolibrary.org/obo/RO_0002557'))) { //
                    $sourceTaxon_kingdom = self::get_taxon_kingdom_4occurID($occurrenceID, 'source');
                    if(self::kingdom_is_viruses_YN($sourceTaxon_kingdom)) {
                        $targetTaxon_kingdom = self::get_taxon_kingdom_4occurID($targetOccurrenceID, 'target');
                        if(!self::kingdom_is_viruses_YN($targetTaxon_kingdom)) {
                            // echo "\nFound: sourceTaxon is VIRUSES; targetTaxon is not VIRUSES; [$associationType]; organisms parasitizing or eating viruses...\n";
                            @$this->debug['stats']['6b. Records of other organisms parasitizing or eating viruses are likely to be errors']++;
                            self::write_refuted_report($rec, 6);
                            $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                            $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                            continue;
                        }
                    }
                }
                
                /* https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=65082&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65082
                Hi Eli,
                Can we please add an additional data quality filter for this resource?
                Please remove records & add them to the refutation data set based of the following rule:
                (g) Records of organisms other than plants having flower visitors are probably errors.
                sourceTaxon has kingdom "Viruses" OR "Animalia" OR "Metazoa"
                AND associationType is "flowers visited by" (http://purl.obolibrary.org/obo/RO_0002623)
                Thanks!
                */
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002623'))) { //"flowers visited by"
                    $sourceTaxon_kingdom = self::get_taxon_kingdom_4occurID($occurrenceID, 'source');
                    if(!self::kingdom_is_plants_YN($sourceTaxon_kingdom)) {
                        @$this->debug['stats']['7a. Records of organisms other than plants having flower visitors are probably errors']++;
                        self::write_refuted_report($rec, 7);
                        $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                        $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                        continue;
                    }
                    //below is basically similar above. As of Aug 12, 2020 it has not passed here.
                    if(self::kingdom_is_viruses_YN($sourceTaxon_kingdom) || self::kingdom_is_animals_YN($sourceTaxon_kingdom)) {
                        @$this->debug['stats']['7b. Records of organisms other than plants having flower visitors are probably errors']++;
                        self::write_refuted_report($rec, 7);
                        $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                        $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                        continue;
                    }
                }
                /* Per Katja: https://eol-jira.bibalex.org/browse/DATA-1874
                Hi Eli,
                Could you please add another tweak? We need to expand rule (g) to also cover the reverse. Here's the original rule:
                (g) Records of organisms other than plants having flower visitors are probably errors.
                sourceTaxon has kingdom "Viruses" OR "Animalia" OR "Metazoa"
                AND associationType is "flowers visited by" (http://purl.obolibrary.org/obo/RO_0002623)
                Please add to this:
                OR
                targetTaxon has kingdom "Viruses" OR "Animalia" OR "Metazoa"
                AND associationType is "visits flowers of" (http://purl.obolibrary.org/obo/RO_0002622)
                */
                if(in_array($associationType, array('http://purl.obolibrary.org/obo/RO_0002622'))) { //"visits flowers of"
                    $targetTaxon_kingdom = self::get_taxon_kingdom_4occurID($targetOccurrenceID, 'target');
                    if(!self::kingdom_is_plants_YN($targetTaxon_kingdom)) {
                        @$this->debug['stats']['7c. Records of organisms other than plants having flower visitors are probably errors']++;
                        self::write_refuted_report($rec, 7);
                        $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                        $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                        continue;
                    }
                    //below is basically similar above. As of Dec 22, 2020 it has not passed here.
                    if(self::kingdom_is_viruses_YN($targetTaxon_kingdom) || self::kingdom_is_animals_YN($targetTaxon_kingdom)) {
                        @$this->debug['stats']['7d. Records of organisms other than plants having flower visitors are probably errors']++;
                        self::write_refuted_report($rec, 7);
                        $this->toDeleteOccurrenceIDS[$occurrenceID] = '';
                        $this->toDeleteOccurrenceIDS[$targetOccurrenceID] = '';
                        continue;
                    }
                }
                //-----------------------------------------------------------------------------
                $o = new \eol_schema\Association();
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                
                // /* New per Jen:
                if(isset($this->toDeleteOccurrenceIDS_Jen[$o->occurrenceID])) continue;
                if(isset($this->toDeleteOccurrenceIDS_Jen[$o->targetOccurrenceID])) continue;
                // */
                
                if($o->associationType == 'http://eol.org/schema/terms/DispersalVector') $o->associationType = 'http://eol.org/schema/terms/IsDispersalVectorFor'; //DATA-1841
                // /* START new: implement preferred term
                if($reverse_type = @$OR[$o->associationType]) { //there is reverse
                    /* 1st check if there is preferred between the two */
                    if($preferred = @$this->preferred_term_info_list[$o->associationType]) {
                        if($preferred == $o->associationType) {
                            $this->archive_builder->write_object_to_file($o);
                            continue;
                        }
                        elseif($preferred == $reverse_type) { //reverse is the preferred
                            /* copied block below */
                            $o->associationID = 'ReversePreferred_'.$o->associationID;
                            $o->occurrenceID = $rec['http://eol.org/schema/targetOccurrenceID'];
                            $o->targetOccurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                            $o->associationType = $reverse_type;
                            $this->archive_builder->write_object_to_file($o);
                            continue;
                        }
                        else exit("\nshould not go here 1\n"); //means the preferred is not the orig and not the reverse
                    }
                    elseif($preferred = @$this->preferred_term_info_list[$reverse_type]) {
                        if($preferred == $o->associationType) {
                            $this->archive_builder->write_object_to_file($o);
                            continue;
                        }
                        elseif($preferred == $reverse_type) { //reverse is the preferred
                            /* copied block below */
                            $o->associationID = 'ReversePreferred_'.$o->associationID;
                            $o->occurrenceID = $rec['http://eol.org/schema/targetOccurrenceID'];
                            $o->targetOccurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                            $o->associationType = $reverse_type;
                            $this->archive_builder->write_object_to_file($o);
                            continue;
                        }
                        else exit("\nshould not go here 2\n"); //means the preferred is not the reverse nor the orig
                    }
                    else { //this is my question: when a term has a reverse, but there is no suggestion of a preferred one yet. I will still use both, meaning two records will still be added
                        $this->archive_builder->write_object_to_file($o);
                    }
                }
                else { //there is no reverse
                    $this->archive_builder->write_object_to_file($o);
                    continue;
                }
                // END new: implement preferred term */

                /* now do the reverse when applicable:
                So what's needed in the resource: The only changes needed should be in the associations file. 
                For every record, create an additional record, with all the same metadata, and the same two occurrenceIDs, 
                but switching which appears in which column (occurrenceID and targetOccurrenceID). 
                The value in relationshipType should change to the "reverse relationship". I'll make you a mapping.
                */
                if($reverse_type = @$OR[$o->associationType]) {
                    $o->associationID = 'ReverseOf_'.$o->associationID;
                    $o->occurrenceID = $rec['http://eol.org/schema/targetOccurrenceID'];
                    $o->targetOccurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                    $o->associationType = $reverse_type;
                    $this->archive_builder->write_object_to_file($o);
                    @$this->debug['statsz']['should be no more of this type, otherwise report to Jen']++;
                }
                // if($i >= 10) break; //debug only
            } //end main division: create ext. and build-up
        } //end loop
    }
    private function process_occurrence($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_occurrence [$what]\n";
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i);
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
            if($what == 'build info') { //process_occurrence()
                if(isset($this->targetOccurrenceIDS[$occurrenceID])) {
                    $this->taxonIDS[$taxonID] = '';
                    $this->targetOccurrenceIDS[$occurrenceID] = $taxonID;
                }
                if(isset($this->occurrenceIDS[$occurrenceID])) {
                    $this->taxonIDS[$taxonID] = '';
                    $this->occurrenceIDS[$occurrenceID] = $taxonID;
                }
                
                // /* New per Jen:
                if(isset($this->exclude_taxonIDs[$taxonID])) $this->toDeleteOccurrenceIDS_Jen[$occurrenceID] = '';
                // */
            }
            elseif($what == 'create extension') { //process_occurrence()
                if(isset($this->toDeleteOccurrenceIDS[$occurrenceID])) continue;

                $this->taxonIDhasOccurrence[$taxonID] = ''; //so we can only create taxon with occurrence.

                if(isset($this->toDeleteOccurrenceIDS_Jen[$occurrenceID])) continue; //Deliberately placed here so those taxa with the specified ranks can still be created in taxon.tab

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
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i);
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

            // /* forced assign a rank so it can be included in $this->excluded_ranks routine below. Partner didn't assign a rank.
            if($taxonID == "http://taxon-concept.plazi.org/id/Animalia/Malacostraca_Latreille_1802") {
                $rec['http://rs.tdwg.org/dwc/terms/taxonRank'] = 'class';
            }
            // */

            if($what == 'build info') {
                $kingdom = $rec['http://rs.tdwg.org/dwc/terms/kingdom'];
                if(isset($this->taxonIDS[$taxonID])) {
                    $this->taxonIDS[$taxonID]['kingdom'] = $kingdom;
                    $this->taxonIDS[$taxonID]['orig kingdom'] = $kingdom;
                    $this->taxonIDS[$taxonID]['sciname'] = (string) $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
                    $this->taxonIDS[$taxonID]['genus']   = $rec['http://rs.tdwg.org/dwc/terms/genus'];
                    //for refuted records
                    $this->taxonIDS[$taxonID]['taxonID']   = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                    $this->taxonIDS[$taxonID]['taxonRank'] = $rec['http://rs.tdwg.org/dwc/terms/taxonRank'];
                    $this->taxonIDS[$taxonID]['phylum']    = $rec['http://rs.tdwg.org/dwc/terms/phylum'];
                    $this->taxonIDS[$taxonID]['class']     = $rec['http://rs.tdwg.org/dwc/terms/class'];
                    $this->taxonIDS[$taxonID]['order']     = $rec['http://rs.tdwg.org/dwc/terms/order'];
                    $this->taxonIDS[$taxonID]['family']    = $rec['http://rs.tdwg.org/dwc/terms/family'];
                    if(!$kingdom) {
                        //option 1
                        if($rec['http://rs.tdwg.org/dwc/terms/class'] == 'Actinopterygii') $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia';
                        else {
                            //option 2
                            $tmp = @$rec['http://rs.tdwg.org/dwc/terms/phylum'] . "_" .  @$rec['http://rs.tdwg.org/dwc/terms/class'] . "_" . @$rec['http://rs.tdwg.org/dwc/terms/order'] . "_" . @$rec['http://rs.tdwg.org/dwc/terms/family'] . "_" . @$rec['http://rs.tdwg.org/dwc/terms/genus'];
                            if(stripos($tmp, "Aves_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia'; //string is found
                            elseif(stripos($tmp, "Magnoliophyta_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Plantae'; //string is found
                            elseif(stripos($tmp, "Amphibia_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia'; //string is found
                            elseif(stripos($tmp, "Anthozoa_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia'; //string is found
                            elseif(stripos($tmp, "Ascidiacea_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia'; //string is found
                            elseif(stripos($tmp, "Asteroidea_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia'; //string is found
                            elseif(stripos($tmp, "Bivalvia_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia'; //string is found
                            elseif(stripos($tmp, "Demospongiae_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia'; //string is found
                            elseif(stripos($tmp, "Echinoidea_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia'; //string is found
                            elseif(stripos($tmp, "Elasmobranchii_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia'; //string is found
                            elseif(stripos($tmp, "Gastropoda_") !== false) $this->taxonIDS[$taxonID]['kingdom'] = 'Animalia'; //string is found
                            else {
                                //option 3: GBIF sciname lookup
                                $scinames = explode("_", $tmp);
                                $scinames = array_map('trim', $scinames);
                                $scinames = array_filter($scinames); //remove null arrays
                                $scinames = array_unique($scinames); //make unique
                                $scinames = array_values($scinames); //reindex key
                                // print_r($scinames);
                                if($val = self::get_ancestor_from_GBIF_using_scinames($scinames, 'kingdom')) $this->taxonIDS[$taxonID]['kingdom'] = $val;
                                else $this->debug['hierarchy without kingdom'][$tmp] = '';
                            }
                        }
                    }
                }
            }
            elseif($what == 'build info 2') { //process_taxon()
                // /* New per Jen: https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=65929&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65929
                $taxonRank = $rec['http://rs.tdwg.org/dwc/terms/taxonRank'];
                if(in_array($taxonRank, $this->excluded_ranks)) $this->exclude_taxonIDs[$taxonID] = '';
                // */

                // /* New per Jen: https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=67316&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67316
                $scientificName = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
                if(stripos($scientificName, "http:") !== false) $this->exclude_taxonIDs[$taxonID] = ''; //string is found
                if(stripos($scientificName, "https:") !== false) $this->exclude_taxonIDs[$taxonID] = ''; //string is found
                // */

                // /* dynamically remove associations for those names with string 'unidentified' (as of Sep 6, 2023)
                if(stripos($scientificName, "unidentified") !== false) $this->exclude_taxonIDs[$taxonID] = ''; //string is found
                // */

                // /* remove scinames Animalia & Metazoa: https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=67344&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67344
                if(in_array($scientificName, array("Animalia", "Metazoa"))) $this->exclude_taxonIDs[$taxonID] = '';
                // */

                // /* Eli's initiative: exclude all higher than species level taxa from Plazi
                // http://taxon-concept.plazi.org/id/Animalia/Syngnathinae_Bonaparte_1831	http://taxon-concept.plazi.org/id/Animalia/Syngnathinae_Bonaparte_1831			Syngnathinae		Animalia	Chordata	Actinopterygii	Syngnathiformes	Syngnathidae	Syngnathinae			
                if(stripos($taxonID, "plazi.") !== false) { //from Plazi //string is found
                    if(stripos($scientificName, " ") !== false) {} //has space, meaning species or lower level //string is found
                    else $this->exclude_taxonIDs[$taxonID] = ''; //doesn't have space, meaning a higher than species level -> then it must be excluded for associations
                }
                // */

                // /* what the heck, let us remove all those 1-word scientificNames. Assuming they are higher-level taxa.
                    if(stripos($scientificName, " ") !== false) {} //has space, meaning species or lower level //string is found
                    else $this->exclude_taxonIDs[$taxonID] = ''; //doesn't have space, meaning a higher than species level -> then it must be excluded for associations
                // */

            }
            elseif($what == 'create extension') { //process_taxon()
                if(isset($this->taxonIDhasOccurrence[$taxonID])) {
                    $o = new \eol_schema\Taxon();
                    $uris = array_keys($rec);
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    
                    /* New per Jen: https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=65929&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65929
                    Jen: "No, thanks, let's keep those in the taxa file. There's no need to change the GloBI taxonomy, in case that is of interest to someone."
                    if(isset($this->exclude_taxonIDs[$o->taxonID])) continue;
                    */
                    
                    $this->archive_builder->write_object_to_file($o);
                }
            }
        }
    }
    private function process_reference($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_reference [$what]\n";
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i);
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
            
            // /* manual fixing some entries in full_reference: https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=66933&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66933
            if($full_ref = @$rec['http://eol.org/schema/reference/full_reference']) {
                if(stripos($full_ref, "theBlack Mountain'District") !== false) { //string is found
                    $full_ref = str_ireplace("theBlack Mountain'District", "the 'Black Mountain' District", $full_ref);
                }
                if(stripos($full_ref, 'the \"Black Mountain\" district') !== false) { //string is found
                    $full_ref = str_ireplace('the \"Black Mountain\" district', "the 'Black Mountain' district", $full_ref);
                }
                $rec['http://eol.org/schema/reference/full_reference'] = $full_ref;
            }
            // */
            
            /*Array(
                [http://purl.org/dc/terms/identifier] => globi:ref:63
                [http://eol.org/schema/reference/publicationType] => 
                [http://eol.org/schema/reference/full_reference] => Hendler G, Miller JE, Pawson DL, Kier PM (1995). Sea Stars, Sea Urchins and Allies: Echinoderms of Florida and the Caribbean (G. Hendler, Ed.). Florida: Smithsonian Institution.
                [http://eol.org/schema/reference/primaryTitle] => 
                [http://purl.org/dc/terms/title] => 
                [http://purl.org/ontology/bibo/pages] => 
                [http://purl.org/ontology/bibo/pageStart] => 
                [http://purl.org/ontology/bibo/pageEnd] => 
                [http://purl.org/ontology/bibo/volume] => 
                [http://purl.org/ontology/bibo/edition] => 
                [http://purl.org/dc/terms/publisher] => 
                [http://purl.org/ontology/bibo/authorList] => 
                [http://purl.org/ontology/bibo/editorList] => 
                [http://purl.org/dc/terms/created] => 
                [http://purl.org/dc/terms/language] => 
                [http://purl.org/ontology/bibo/uri] => https://books.google.com.mx/books?id=-0MWAQAAIAAJ&pg=PR4&dq=Sea+Stars,+Sea+Urchins+and+Allies:+Echinoderms+of+Florida+and+the+Caribbean&hl=es-419&sa=X&ved=0ahUKEwiVpM_8wdDjAhVlUt8KHdgBBrAQ6wEITzAF
                [http://purl.org/ontology/bibo/doi] => 
                [http://schemas.talis.com/2005/address/schema#localityName] =>
            )*/
            $refID = $rec['http://purl.org/dc/terms/identifier'];
            if($what == 'build info') {
                /*
                refuted:referenceCitation (from DwC-A: reference:full_reference)
                refuted:referenceDoi (from DwC-A: reference:referenceDoi)
                refuted:referenceUrl (from DwC-A: reference:referenceUrl)
                */
                $this->references[$refID]['refuted:referenceCitation']  = $rec['http://eol.org/schema/reference/full_reference'];
                $this->references[$refID]['refuted:referenceDoi']       = $rec['http://purl.org/ontology/bibo/doi'];
                $this->references[$refID]['refuted:referenceUrl']       = $rec['http://purl.org/ontology/bibo/uri'];
            }
            // /* this is the carry-over portion
            $o = new \eol_schema\Reference();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                if($field == 'schema#localityName') $field = 'localityName'; //just a correction
                $o->$field = $rec[$uri];

                // /* new: Oct 19, 2023
                if(in_array($field, array("full_reference", "primaryTitle", "title", "doi", "localityName"))) $o->$field = RemoveHTMLTagsAPI::remove_html_tags($o->$field);
                // */
            }
            $this->archive_builder->write_object_to_file($o);
            // */
        }
    }
    private function get_taxon_ancestor_4occurID($targetORsource_OccurrenceID, $targetORsource, $rank) //OccurrenceID points to a taxon, then return its ancestor value with $rank. e.g. 'genus'
    {
        $taxonID = self::get_taxonID_given_occurID($targetORsource_OccurrenceID, $targetORsource);
        $sciname = @$this->taxonIDS[$taxonID]['sciname'];
        
        if($taxonID) {
            if($ancestor = $this->taxonIDS[$taxonID][$rank]) return $ancestor;
            elseif(in_array($taxonID, array('EOL:xxx'))) return 'xxx';
            elseif(in_array($sciname, array('xxx', 'yyy'))) return 'xxx';
            else {
                if($val = self::lookup_gbif_ancestor_using_sciname($sciname, array(), $rank)) {
                    return $val;
                }
                elseif(substr($taxonID,0,4) == 'EOL:' || substr($taxonID,0,7) == 'EOL_V2:') {
                    if(!isset($this->not_found_in_EOL[$taxonID])) {
                        if($val = self::get_ancestor_from_EOLtaxonID($taxonID, $sciname, $rank)) return $val;
                        else {
                            $this->not_found_in_EOL[$taxonID] = '';
                            // echo " - not found in EOL: $targetORsource - ";
                            $this->debug['does not have ancestor']['EOL'][$taxonID][$sciname] = ''; // echo("\nInvestigate: this taxonID [$taxonID] does not have kingdom char\n");
                            return;
                        }
                    }
                }
                elseif(substr($taxonID,0,11) == 'INAT_TAXON:') { //e.g. INAT_TAXON:900074
                    if($val = self::get_ancestor_by_rank_from_iNATtaxonID($taxonID, array(), $rank)) return $val;
                    else {
                        $this->debug["does not have $rank"]['INAT'][$taxonID][$sciname] = ''; // echo("\nInvestigate: this taxonID [$taxonID] does not have kingdom char\n");
                        return;
                    }
                }
                $this->debug["does not have $rank"]['EOL GBIF INAT'][$taxonID][$sciname] = ''; // echo("\nInvestigate: this taxonID [$taxonID] does not have kingdom char\n");
            }
        }
        else exit("\nInvestigate func get_taxon_ancestor_4occurID(): this $targetORsource OccurrenceID does not have taxonID \n");
    }
    public function get_taxonID_given_occurID($targetORsource_OccurrenceID, $targetORsource)
    {
        $taxonID = false;
        if    ($targetORsource == 'target') $taxonID = $this->targetOccurrenceIDS[$targetORsource_OccurrenceID];
        elseif($targetORsource == 'source') $taxonID = $this->occurrenceIDS[$targetORsource_OccurrenceID];
        if(!$taxonID) exit("\nCannot link to taxonID: occurID=[$targetORsource_OccurrenceID] TorS=[$targetORsource]\n");
        else return $taxonID;
    }
    private function format_sciname($sciname)
    {
        $sciname = strip_tags($sciname);
        $sciname = trim(str_ireplace('undetermined', '', $sciname));
        $sciname = trim(str_ireplace('unspecified', '', $sciname));
        $sciname = trim(str_ireplace(' sp.', '', $sciname));
        $sciname = trim(str_ireplace(' spp.', '', $sciname));
        $sciname = trim(str_ireplace(' agg.', '', $sciname));
        $sciname = trim(str_ireplace(' var.', '', $sciname));
        $sciname = Functions::remove_whitespace($sciname);
        return $sciname;
    }
    private function get_taxon_kingdom_4occurID($targetORsource_OccurrenceID, $targetORsource) //targetOccurrenceID points to a taxon, then return its kingdom value
    {
        $taxonID = self::get_taxonID_given_occurID($targetORsource_OccurrenceID, $targetORsource);
        $sciname = (string) @$this->taxonIDS[$taxonID]['sciname'];
        $orig_sciname = $sciname;

        $sciname = self::format_sciname($sciname); //manual cleaning
        
        $return_kingdom = false;
        if($taxonID) {
            if($kingdom = $this->taxonIDS[$taxonID]['kingdom']) $return_kingdom = $kingdom; // Animalia or Plantae
            elseif(in_array($taxonID, array('EOL:23306280', 'EOL:5051697', 'EOL:5536407', 'EOL:5231462', 'EOL:6922431', 'EOL:5540593', 'EOL_V2:5170411', 'EOL:107287', 
            'EOL_V2:5169796', 'EOL:2879598', 'IRMNG:11155392', 'EOL:5356331', 'EOL:703626', 'EOL:2865819', 'EOL_V2:5544078', 'EOL:5164786', 'EOL_V2:5426294', 
            'EOL:5024066', 'WD:Q5389420', 'EOL:29378842', 'EOL:40469587', 'EOL:71360', 'EOL:5744742', 'EOL:5631615', 'EOL_V2:6346627', 'EOL_V2:5350526', 'EOL_V2:5178076', 
            'EOL_V2:5349701', 'EOL_V2:5387667', 'EOL:5187953', 'EOL_V2:5664483', 'EOL_V2:5177870', 'EOL_V2:5386317', 'EOL_V2:5223689', 'EOL_V2:5666458', 'EOL_V2:5745926', 
            'EOL_V2:5745719', 'EOL_V2:5531579', 'EOL_V2:5223650', 'EOL_V2:5344435', 'EOL_V2:2879124', 'EOL_V2:5535347', 'EOL_V2:6191776', 'EOL_V2:5020941', 'EOL_V2:485027'))) $return_kingdom = 'Plantae';
            elseif(in_array($taxonID, array('EOL:5425400', 'EOL:55106', 'EOL:3832795', 'FBC:FB:SpecCode:5038', 'EOL:3682636', 'EOL:31599461', 'EOL:54655', 
            'EOL_V2:6272187', 'EOL_V2:3121417'))) $return_kingdom = 'Animalia';
            elseif(in_array($sciname, array('Ectohomeosoma kasyellum', 'Setothesea asigna', 'Haematopsis grataria', 'Zooplankton', 'Alleophasma cyllarus', 'Latoria canescens?', 'Invertebrata', 
                'Haemolaelaps glasgowi', 'Nyctiophylax vestitus', 'Coccinelidae', 'Arthropoda', 'Euryinae', 'Phygadenon'))) $return_kingdom = 'Animalia';
            // May need to add by Eli:
            // elseif(in_array($sciname, array('Lichenostigma epipolinum', 'Zwackhiomyces euplocinus'))) $return_kingdom = 'Fungi';
            elseif(in_array($sciname, array('Plant'))) $return_kingdom = 'Plantae';
            else {
                if($val = self::lookup_gbif_ancestor_using_sciname($sciname, array(), 'kingdom')) $return_kingdom = $val;
                elseif(substr($taxonID,0,4) == 'EOL:' || substr($taxonID,0,7) == 'EOL_V2:') {
                    if(!isset($this->not_found_in_EOL[$taxonID])) {
                        if($val = self::get_ancestor_from_EOLtaxonID($taxonID, $sciname, 'kingdom')) $return_kingdom = $val;
                        else {
                            $this->not_found_in_EOL[$taxonID] = '';
                            // echo " - not found in EOL: $targetORsource - ";
                            $this->debug['does not have kingdom']['EOL'][$taxonID][$sciname] = ''; // echo("\nInvestigate: this taxonID [$taxonID] does not have kingdom char\n");
                        }
                    }
                }
                elseif(substr($taxonID,0,11) == 'INAT_TAXON:') { //e.g. INAT_TAXON:900074
                    if($val = self::get_ancestor_by_rank_from_iNATtaxonID($taxonID, array(), 'kingdom')) $return_kingdom = $val;
                    else {
                        $this->debug['does not have kingdom']['INAT'][$taxonID][$sciname] = ''; // echo("\nInvestigate: this taxonID [$taxonID] does not have kingdom char\n");
                    }
                }
                else {
                    if(stripos($sciname, " trees") !== false) $return_kingdom = 'Plantae'; //string is found
                    if(stripos($sciname, " shrubs") !== false) $return_kingdom = 'Plantae'; //string is found
                    if(stripos($sciname, " plants") !== false) $return_kingdom = 'Plantae'; //string is found
                }
            }

            if($return_kingdom) {
                $this->taxonIDS[$taxonID]['kingdom'] = $return_kingdom;
                return $return_kingdom;
            }
            else $this->debug['does not have kingdom']['EOL GBIF INAT'][$taxonID][$sciname] = ''; // echo("\nInvestigate: this taxonID [$taxonID] does not have kingdom char\n");
            
        }
        else exit("\nInvestigate func get_taxon_kingdom_4occurID(): this [$targetORsource] OccurrenceID does not have taxonID \n");
    }
    function get_ancestor_by_rank_from_iNATtaxonID($taxonID, $options = array(), $sought_rank = 'kingdom')
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
                    // if    ($anc['rank'] == 'kingdom' && $anc['name'] == 'Animalia') return 'Animalia';
                    // elseif($anc['rank'] == 'kingdom' && $anc['name'] == 'Plantae') return 'Plantae';
                    if($anc['rank'] == $sought_rank) {
                        $this->debug["$sought_rank from iNat"][$anc['name']] = '';
                        return $anc['name'];
                    }
                }
            }
        }
        else debug("\nnot found [$id] in iNaturalist()\n");
        return false;
    }
    function get_ancestor_from_EOLtaxonID($taxonID, $sciname, $sought_rank)
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
                    if($sought_rank == 'kingdom') {
                        if($rec['nameAccordingTo'] == 'Plant Forms, Habitat and Distribution') return 'Plantae';
                    }
                }
                /* Let us try GBIF */
                foreach($arr as $rec) {
                    if($rec['nameAccordingTo'] == 'GBIF classification') {
                        $gbif_id = $rec['sourceIdentifier'];
                        if($ancestor = self::get_ancestor_from_gbif($gbif_id, array(), $sought_rank)) {
                            // echo "\nkingdom from GBIF: [$kingdom]\n";
                            $this->debug["$sought_rank from GBIF"][$ancestor] = '';
                            return $ancestor;
                        }
                        if($sciname = @$rec['scientificName']) {
                            if($val = self::lookup_gbif_ancestor_using_sciname($sciname, array(), $sought_rank)) return $val;
                        }
                        break;
                    }
                }
                
                /* Let us try GBIF 2 */
                foreach($arr as $rec) {
                    if($rec['nameAccordingTo'] != 'GBIF classification') {
                        if($sciname = @$rec['scientificName']) {
                            if($val = self::lookup_gbif_ancestor_using_sciname($sciname, array(), $sought_rank)) return $val;
                        }
                    }
                }
                
            }
        }
        return false;
    }
    function get_ancestor_from_gbif($gbif_id, $options = array(), $rank) //$rank e.g. 'kingdom'
    {
        if(!isset($this->not_found_in_GBIF[$gbif_id])) {
            if(!$options) $options = $this->download_options_gbif;
            $url = str_replace("TAXON_ID", $gbif_id, $this->api['GBIF taxon']);
            if($json = Functions::lookup_with_cache($url, $options)) {
                $arr = json_decode($json, true);
                // print_r($arr); exit("\n-end gbif-\n");
                if($val = @$arr[$rank]) return $val;
            }
            $this->not_found_in_GBIF[$gbif_id] = '';
        }
    }
    private function get_ancestor_from_GBIF_using_scinames($scinames, $rank) //$rank e.g. 'kingdom'
    {
        foreach($scinames as $sciname) {
            $sciname = self::format_sciname($sciname); //manual cleaning
            if(!isset($this->not_found_in_GBIF[$sciname])) {
                if($ancestor = self::lookup_gbif_ancestor_using_sciname($sciname, array(), $rank)) return $ancestor;
                $this->not_found_in_GBIF[$sciname] = '';
            }
        }
    }
    private function lookup_gbif_ancestor_using_sciname($sciname, $options = array(), $rank)
    {
        if(!$sciname) return;
        $sciname = trim(str_ireplace('incertae sedis', '', $sciname));
        if($rank == 'kingdom') {
            if(self::is_taxon_under_kingdom_viruses($sciname)) return 'Viruses';
        }
        //------------------------------------------------------------------------------------------------
        if(!$options) $options = $this->download_options_gbif;
        $options['expire_seconds'] = false; //should be false. ancestor value doesn't normally change
        $url = str_replace("SCINAME", urlencode($sciname), $this->api['GBIF taxon 2']);
        if($json = Functions::lookup_with_cache($url, $options)) {
            $arr = json_decode($json, true);
            // print_r($arr); exit("\n-end gbif-\n");
            foreach($arr['results'] as $r) {
                if($val = @$r[$rank]) return $val;
                
                /* Should be an improvement, only for $rank == 'kingdom' --- COMMENTED bec. it caused sudden program exit (infinite loop), due to inconsistent ancestor valus in GBIF API.
                if($rank == 'kingdom') {
                    if($order = @$r['order']) {
                        if($ancestor = self::lookup_gbif_ancestor_using_sciname($order, $options = array(), $rank)) return $ancestor;
                    }
                    if($family = @$r['family']) {
                        if($ancestor = self::lookup_gbif_ancestor_using_sciname($family, $options = array(), $rank)) return $ancestor;
                    }
                }
                */
            }
        }
        
        //3rd try, if has ' virus' in the sciname
        if($rank == 'kingdom') {
            if(stripos($sciname, " virus") !== false) return 'Viruses'; //string is found
            if(stripos($sciname, "virus ") !== false) return 'Viruses'; //string is found
            if(stripos($sciname, "viruses ") !== false) return 'Viruses'; //string is found
            if(substr($sciname,-5) == 'virus') return 'Viruses'; //last 5 chars in sciname is 'virus'.
        }
        
        /* STILL A BIG MISTAKE, BEC. OF NAMES OF VIRUSES
        //4th try
        $canonical = Functions::canonical_form($sciname);
        if($sciname == $canonical) {
            //if sciname has space
            if(stripos($sciname, " ") !== false) { //string is found
                $names = explode(" ", $sciname);
                $names = array_map('trim', $names);
                $names = array_filter($names); //remove null arrays
                $names = array_unique($names); //make unique
                $names = array_values($names); //reindex key
                if($name = @$names[0]) {
                    if($ancestor = self::lookup_gbif_ancestor_using_sciname($name, array(), $rank)) return $ancestor;
                }
            }
        }
        */
    }
    private function get_orig_reverse_uri()
    {
        //first 3 are those symmetrical, based on table from: https://eol-jira.bibalex.org/secure/attachment/74309/reverse_assocs.csv
        $uri['http://purl.obolibrary.org/obo/RO_0002441'] = 'http://purl.obolibrary.org/obo/RO_0002441';
        $uri['http://purl.obolibrary.org/obo/RO_0002437'] = 'http://purl.obolibrary.org/obo/RO_0002437';
        $uri['http://purl.obolibrary.org/obo/RO_0002442'] = 'http://purl.obolibrary.org/obo/RO_0002442';
        
        $uri['http://purl.obolibrary.org/obo/RO_0002220'] = 'http://purl.obolibrary.org/obo/RO_0002220';
        $uri['http://purl.obolibrary.org/obo/RO_0008506'] = 'http://purl.obolibrary.org/obo/RO_0008506';
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
        $uri['http://purl.obolibrary.org/obo/RO_0002471'] = 'http://purl.obolibrary.org/obo/RO_0002470';
        $uri['http://purl.obolibrary.org/obo/RO_0002627'] = 'http://purl.obolibrary.org/obo/RO_0002626';
        $uri['http://purl.obolibrary.org/obo/RO_0002459'] = 'http://purl.obolibrary.org/obo/RO_0002460';
        $uri['http://purl.obolibrary.org/obo/RO_0002626'] = 'http://purl.obolibrary.org/obo/RO_0002627';
        $uri['http://purl.obolibrary.org/obo/RO_0008507'] = 'http://purl.obolibrary.org/obo/RO_0008508';
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
    private function suggested_remaps_if_any($associationType)
    {   /* (a) I thought we already had all records with associationType "pollinates" (http://purl.obolibrary.org/obo/RO_0002455) recoded to "visits flowers of" 
        (http://purl.obolibrary.org/obo/RO_0002622), but I still see 58454 records with associationType http://purl.obolibrary.org/obo/RO_0002455 in the current resource file here: https://opendata.eol.org/dataset/globi/resource/c8392978-16c2-453b-8f0e-668fbf284b61
        We should change all these records to associationType http://purl.obolibrary.org/obo/RO_0002622, and this should happen before we create the reverse records, 
        i.e., there should not be any "pollinated by" (http://purl.obolibrary.org/obo/RO_0002456) records in the EOL resource file, 
        all reverse records should be "has flowers visited by" (http://purl.obolibrary.org/obo/RO_0002623). */
        if    ($associationType == 'http://purl.obolibrary.org/obo/RO_0002455') return 'http://purl.obolibrary.org/obo/RO_0002622';
        elseif($associationType == 'http://purl.obolibrary.org/obo/RO_0002456') return 'http://purl.obolibrary.org/obo/RO_0002623';
        return $associationType;
    }
    private function kingdom_is_plants_YN($kingdom)
    {
        $kingdom = strtolower($kingdom);
        if(in_array($kingdom, array('plantae', 'viridiplantae', 'plants', 'plant'))) return true;
    }
    private function kingdom_is_animals_YN($kingdom)
    {
        $kingdom = strtolower($kingdom);
        if(in_array($kingdom, array('animalia', 'animals', 'animal', 'metazoa', 'metazoan'))) return true;
    }
    private function kingdom_is_viruses_YN($kingdom)
    {
        $kingdom = strtolower($kingdom);
        if(in_array($kingdom, array('viruses', 'virus'))) return true;
        /* per Katja: https://eol-jira.bibalex.org/browse/DATA-1872?focusedCommentId=65459&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65459
        It turns out that NCBI treats Viruses as a superkingdom and then has a bunch of viral kingdoms. 
        Can we please add these viral kingdoms in all of our filters that check for Viruses? Here's the list:
            Bamfordvirae https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=2732005
            Helvetiavirae https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=2732006
            Loebvirae https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=2732090
            Sangervirae https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=2732091
            Shotokuvirae https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=2732092
            Trapavirae https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=2732093
            Orthornavirae https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=2732396
            Pararnavirae https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=2732397
        */
        if(in_array($kingdom, array('bamfordvirae', 'helvetiavirae', 'loebvirae', 'sangervirae', 'shotokuvirae', 'trapavirae', 'orthornavirae', 'pararnavirae'))) return true;
    }
    private function is_taxon_under_kingdom_viruses($taxon)
    {
        $known_viruses = array('Adomaviridae', 'Fusariviridae', 'Pithoviridae', 'Pithovirus', 'Albetovirus', 'Aumaivirus', 'Blunervirus', 'Botybirnavirus', 'Cilevirus', 'Deltavirus', 
        'Dinodnavirus', 'Higrevirus', 'Idaeovirus', 'Negevirus', 'Ourmiavirus', 'Pandoravirus', 'Papanivirus', 'Salterprovirus', 'Sinaivirus', 'Sobemovirus', 'Tenuivirus', 
        'Tilapinevirus', 'Virtovirus');
        if(in_array($taxon, $known_viruses)) return true;
        
        if(stripos($taxon, "virophage") !== false)      return true; //string is found
        if(stripos($taxon, "virus-") !== false)         return true; //string is found
        if(stripos($taxon, "viroid") !== false)         return true; //string is found
        if(stripos($taxon, "barnacle viurs") !== false) return true; //string is found --> 'Beihai barnacle viurs 1'
        
        /*
        'Nanobacterium' was excluded. Per: https://eol-jira.bibalex.org/browse/DATA-1853?focusedCommentId=64880&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64880
        */
    }
    private function special_list_of_not_plantae() //per Katja: https://eol-jira.bibalex.org/browse/DATA-1854?focusedCommentId=64886&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64886
    {   /* Not a plant
        sourceTaxonId	sourceTaxonName
        EOL:4968393	Hymenolepis cantaniana
        GBIF:8766908	Southwellia ransomi
        NCBI:1926997	Bivalve RNA virus G4
        NCBI:1926998	Bivalve hepelivirus G
        NCBITaxon:10407	Hepatitis B virus
        NCBITaxon:11307	Sonchus yellow net nucleorhabdovirus
        NCBITaxon:12201	Gloriosa stripe mosaic virus
        NCBITaxon:12461	Hepatitis E virus
        NCBITaxon:300879	Cassia yellow blotch virus
        NCBITaxon:433462	Canna yellow streak virus
        NCBITaxon:509628	Hepatitis E virus type 3
        */
        return array('EOL:4968393', 'GBIF:8766908', 'NCBI:1926997', 'NCBI:1926998', 'NCBITaxon:10407', 'NCBITaxon:11307', 'NCBITaxon:12201', 'NCBITaxon:12461', 'NCBITaxon:300879', 'NCBITaxon:433462', 'NCBITaxon:509628');
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>