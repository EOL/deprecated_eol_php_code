<?php
namespace php_active_record;
/* connector: [tram_996.php] - TRAM-996 */
class DH_v21_TRAM_996
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        if(Functions::is_production()) {} //not used
        else {
            $this->download_options = array(
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/TRAM-996/";
        }

        $this->tsv['DH21_current_old'] = $this->main_path."/data/dh2.1mar2022/taxon.tab";
        $this->tsv['DH21_current'] = $this->main_path."/data/dh2.1mar2022/taxon_new.tab";
        $this->tsv['DH11'] = $this->main_path."/data/DH_v1_1/taxon.tab";
        
        $this->tsv['taxonIDs_from_source_col'] = $this->main_path."/taxonIDs_from_source_col.txt";
        $this->tsv['COL_identifiers'] = $this->main_path."/COL_identifiers.txt";
        $this->tsv['COL_taxonIDs'] = $this->main_path."/COL_taxonIDs.txt";
        
        $this->tsv['COL_2019'] = $this->main_path."/data/COL_2019_dwca/taxa.txt";
        $this->tsv['COL_2019_new'] = $this->main_path."/data/COL_2019_dwca/taxa_new.txt";
        $this->tsv['Collembola'] = $this->main_path."/data/col2020-08-01/taxa.txt";
        $this->tsv['Collembola_new'] = $this->main_path."/data/col2020-08-01/taxa_new.txt";

        $this->tsv['synonyms_COL'] = $this->main_path."/synonyms_COL.txt";
        $this->tsv['synonyms_Collembola'] = $this->main_path."/synonyms_Collembola.txt";
        $this->tsv['synonyms_COL2'] = $this->main_path."/synonyms_COL2.txt";
        $this->tsv['synonyms_ODO'] = $this->main_path."/synonyms_ODO.txt";
        
        /* start of COL2 and the rest */
        $this->tsv['COL_2021'] = $this->main_path."/data/COL_2021_dwca/Taxon.tsv";
        
        // if(file_exists($this->tsv['Collembola'])) exit("\nfile exists ok\n");
        // else exit("\nfile does not exist...\n");
        
        $this->tsv['WorldOdonataList'] = $this->main_path."/data/worldodonatalist/taxa.txt";
        
        
    }
    function start()
    {   /*
        from DH21:      EOL-000000477889	COL:33591a27876ebb8bd505763fecfa88f3
        from COL 2019:  11472753	33591a27876ebb8bd505763fecfa88f3
        from COL 2019:  [acceptednameusageid] => 11472753
                        [taxonomicstatus] => synonym
                        [taxonrank] => species
                        [scientificname] => Vicia macrophylla (Maxim.)B.Fedtsch. */

        // self::parse_tsv($this->tsv['DH21_current'], 'check', false);

        /* manual adjustment: https://eol-jira.bibalex.org/browse/TRAM-996?focusedCommentId=66739&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66739
        $txt = file_get_contents($this->tsv['DH21_current_old']);
        $txt = str_ireplace("65541be3e018d0cd9ae0b3e2bcffefa4", "34c3e147c24f44f17fce5e12d676970a", $txt);
        $WRITE = fopen($this->tsv['DH21_current'], "w"); fwrite($WRITE, $txt); fclose($WRITE);
        exit("\n-end-\n");
        */

        /* step 1: run once only - DONE --- COL2, ITIS, NCBI, ODO, WOR
        $head = array('partner', 'taxonID');
        $WRITE = fopen($this->tsv['taxonIDs_from_source_col'], "w"); fwrite($WRITE, implode("\t", $head)."\n");
        self::parse_tsv($this->tsv['DH21_current'], 'assemble_taxonIDs_from_source_col', $WRITE);

        $head = array('partner', 'identifier'); --- COL
        $WRITE = fopen($this->tsv['COL_identifiers'], "w"); fwrite($WRITE, implode("\t", $head)."\n");
        self::parse_tsv($this->tsv['DH21_current'], 'assemble_COL_identifiers', $WRITE);
        */
        
        /* step 1.1: remove bom in COL 2019
        $txt = file_get_contents($this->tsv['COL_2019']);
        $txt = Functions::remove_utf8_bom($txt);
        $WRITE = fopen($this->tsv['COL_2019_new'], "w"); fwrite($WRITE, $txt); fclose($WRITE);
        */
        /* step 1.2: remove bom in Collembola
        $txt = file_get_contents($this->tsv['Collembola']);
        $txt = Functions::remove_utf8_bom($txt);
        $WRITE = fopen($this->tsv['Collembola_new'], "w"); fwrite($WRITE, $txt); fclose($WRITE);
        */

        /* step 2: assemble COL taxonIDs
        self::parse_tsv($this->tsv['COL_2019_new'], 'assemble_COL_info', false); //creates $this->COL_identifier_taxonID_info
        self::parse_tsv($this->tsv['Collembola_new'], 'assemble_COL_info2', false);  //creates $this->COL_identifier_taxonID_info2

        $head = array('partner', 'identifier', 'taxonID');
        $WRITE = fopen($this->tsv['COL_taxonIDs'], "w"); fwrite($WRITE, implode("\t", $head)."\n");
        self::parse_tsv($this->tsv['COL_identifiers'], 'generate_COL_taxonIDs', $WRITE);
        print_r($this->debug);
        */

        require_library('connectors/FillUpMissingParentsAPI');
        $this->func = new FillUpMissingParentsAPI(false, false, false);

        $head = array('z_partner', 'z_identifier');
        $head = array_merge($head, array('taxonID', 'source', 'acceptedNameUsageID', 'scientificName', 'taxonRank', 'canonicalName', 'taxonomicStatus', 'furtherInformationURL', 'datasetID'));
        $this->synonyms_headers = $head; // print_r($head); exit;
        
        /* step 3: assemble synonyms --- COL
        self::parse_tsv($this->tsv['COL_taxonIDs'], 'get_COL_taxonIDs COL', false); //creates $this->COL_taxonIDs
        $WRITE = fopen($this->tsv['synonyms_COL'], "w"); fwrite($WRITE, implode("\t", $head)."\n");
        self::parse_tsv($this->tsv['COL_2019_new'], 'get_COL_synonyms', $WRITE, 'COL');
        */
        
        /* step 4: assemble synonyms --- COL Collembola
        self::parse_tsv($this->tsv['COL_taxonIDs'], 'get_COL_taxonIDs Collembola', false); //creates $this->Collembola_taxonIDs
        $WRITE = fopen($this->tsv['synonyms_Collembola'], "w"); fwrite($WRITE, implode("\t", $head)."\n");
        self::parse_tsv($this->tsv['Collembola_new'], 'get_Collembola_synonyms', $WRITE, 'COL');
        */
        
        /* ======== start for COL2, ITIS, NCBI, ODO, WOR ======== */
        $partners = array('COL2', 'ITIS', 'NCBI', 'ODO', 'WOR');
        $partners = array('COL2'); //during dev only
        $partners = array('ODO'); //during dev only
        
        foreach($partners as $partner) {
            $this->Partner_taxonIDs = array();
            self::parse_tsv($this->tsv['taxonIDs_from_source_col'], 'get_taxonIDs_2process', false, $partner); //generate $this->Partner_taxonIDs
            echo "\n]$partner: ".count($this->Partner_taxonIDs)."\n";

            $WRITE = fopen($this->tsv['synonyms_'.$partner], "w"); fwrite($WRITE, implode("\t", $head)."\n");
            
            if($partner == 'COL2') $source_file = 'COL_2021';
            if($partner == 'ODO') $source_file = 'WorldOdonataList';
            
            else exit("\n$partner not yet initialized 01.\n");
            self::parse_tsv($this->tsv[$source_file], 'get_Partner_synonyms', $WRITE, $partner);
            
        }
        
        
    }
    private function parse_tsv($txtfile, $task, $WRITE = false, $partner = '')
    {   $i = 0; echo "\nStart $task...\n";
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 300000) == 0) echo "\n[$task] - ".number_format($i)." ";
            // if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields);
                $fields = array_map('trim', $fields);
                // print_r($fields); exit;
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopx\n");
            /*Array( "DH21"
                [taxonid] => EOL-000003007779
                [source] => COL:d3fe342a0f6ed9a8d6e8dd0fce2aad88
                [furtherinformationurl] => http://www.catalogueoflife.org/col/details/species/id/d3fe342a0f6ed9a8d6e8dd0fce2aad88
                [acceptednameusageid] => 
                [parentnameusageid] => EOL-000000096357
                [scientificname] => Acytostelium aggregatum Cavender & Vadell, 2000
                [taxonrank] => species
                [taxonomicstatus] => accepted
                [datasetid] => COL
                [canonicalname] => Acytostelium aggregatum
                [eolid] => 732616
                [landmark] => 
                [higherclassification] => Life|Cellular Organisms|Eukaryota|Amoebozoa|Evosea|Eumycetozoa|Dictyostelia|Acytosteliales|Acytosteliaceae|Acytostelium
            )*/
            //==============================================================================
            if($task == 'check') { print_r($rec); exit; }
            if($task == 'assemble_taxonIDs_from_source_col') {
                $source = $rec['source'];
                $arr = explode(":", $source);
                $source_partner = $arr[0];
                $source_taxonID = @$arr[1];
                if(in_array($source_partner, array('COL2', 'ITIS', 'NCBI', 'ODO', 'WOR'))) {
                    $arr = array($source_partner, $source_taxonID);
                    fwrite($WRITE, implode("\t", $arr)."\n");
                }
            }
            //==============================================================================
            if($task == 'assemble_COL_identifiers') {
                $source = $rec['source'];
                $arr = explode(":", $source);
                $source_partner = $arr[0];
                $source_taxonID = @$arr[1];
                if(in_array($source_partner, array('COL'))) {
                    $arr = array($source_partner, $source_taxonID);
                    fwrite($WRITE, implode("\t", $arr)."\n");
                }
            }
            //==============================================================================
            if($task == 'assemble_COL_info') {
                $taxonID = $rec['taxonID']; $identifier = $rec['identifier'];
                $this->COL_identifier_taxonID_info[$identifier] = $taxonID;
            }
            if($task == 'assemble_COL_info2') { // print_r($rec); exit;
                $taxonID = $rec['taxonID']; $identifier = $rec['identifier'];
                $this->COL_identifier_taxonID_info2[$identifier] = $taxonID;
            }
            //==============================================================================
            if($task == 'generate_COL_taxonIDs') {
                $identifier = $rec['identifier'];
                $taxonID = ''; $partner = 'COL';
                    if($val = @$this->COL_identifier_taxonID_info[$identifier]) $taxonID = $val;
                elseif($val = @$this->COL_identifier_taxonID_info2[$identifier]) { $taxonID = $val; $partner = 'Collembola'; }
                else $this->debug['dh21 col identifier not in col_2019'][$identifier] = '';
                $arr = array($partner, $identifier, $taxonID);
                fwrite($WRITE, implode("\t", $arr)."\n");
            }
            //==============================================================================
            if($task == 'get_COL_taxonIDs COL') { // print_r($rec); exit;
                /*Array(
                    [partner] => COL
                    [identifier] => d3fe342a0f6ed9a8d6e8dd0fce2aad88
                    [taxonID] => 54706559
                )*/
                if($rec['partner'] == 'COL') $this->COL_taxonIDs[$rec['taxonID']] = '';
            }
            if($task == 'get_COL_taxonIDs Collembola') { // print_r($rec); exit;
                /*Array(
                    [partner] => Collembola
                    [identifier] => d3fe342a0f6ed9a8d6e8dd0fce2aad88
                    [taxonID] => 54706559
                )*/
                if($rec['partner'] == 'Collembola') $this->Collembola_taxonIDs[$rec['taxonID']] = '';
            }
            //==============================================================================
            if($task == 'get_COL_synonyms') { //print_r($rec); exit;
                $taxonomicStatus        = $rec['taxonomicStatus'];
                $acceptedNameUsageID    = $rec['acceptedNameUsageID'];
                
                $condition = $taxonomicStatus == 'synonym' && isset($this->COL_taxonIDs[$acceptedNameUsageID]);
                if($condition) { // print_r($rec); exit;
                    /*Array(
                        [taxonID] => 316502
                        [identifier] => 
                        [datasetID] => 26
                        [datasetName] => ScaleNet in Species 2000 & ITIS Catalogue of Life: 2019
                        [acceptedNameUsageID] => 316423
                        [parentNameUsageID] => 
                        [taxonomicStatus] => synonym
                        [taxonRank] => species
                        [verbatimTaxonRank] => 
                        [scientificName] => Canceraspis brasiliensis Hempel, 1934
                        [kingdom] => Animalia
                        [phylum] => 
                        [class] => 
                        [order] => 
                        [superfamily] => 
                        [family] => 
                        [genericName] => Canceraspis
                        [genus] => Limacoccus
                        [subgenus] => 
                        [specificEpithet] => brasiliensis
                        [infraspecificEpithet] => 
                        [scientificNameAuthorship] => Hempel, 1934
                        [source] => 
                        [namePublishedIn] => 
                        [nameAccordingTo] => 
                        [modified] => 
                        [description] => 
                        [taxonConceptID] => 
                        [scientificNameID] => Coc-100-7
                        [references] => http://www.catalogueoflife.org/annual-checklist/2019/details/species/id/6a3ba2fef8659ce9708106356d875285/synonym/3eb3b75ad13a5d0fbd1b22fa1074adc0
                        [isExtinct] => 
                    )
                    In [COL_taxonIDs.txt]: this is the accepted taxa list
                    COL	6a3ba2fef8659ce9708106356d875285	316423
                    */
                    $ret = array();
                    $ret['z_partner'] = 'COL';
                    $ret['z_identifier'] = self::format_z_identifier('COL', $rec);
                    $ret['taxonID'] = self::format_taxonID('COL', $rec);
                    $ret['source'] = self::format_source('COL', $rec);
                    $ret['furtherInformationURL'] = self::format_furtherInformationURL('COL', $rec);
                    $ret['acceptedNameUsageID'] = $rec['acceptedNameUsageID'];
                    $ret['scientificName'] = $rec['scientificName'];
                    $ret['taxonRank'] = $rec['taxonRank'];
                    $ret['taxonomicStatus'] = 'not accepted';
                    $ret['datasetID'] = self::format_datasetID('COL', $rec);
                    $ret['canonicalName'] = self::format_canonicalName('COL', $rec, $ret['taxonRank']);
                    $save = array();
                    foreach($this->synonyms_headers as $head) $save[] = $ret[$head];
                    // print_r($save); print_r($this->synonyms_headers); exit;
                    fwrite($WRITE, implode("\t", $save)."\n");
                }
                // if($i >= 10) break;
            }
            //==============================================================================
            if($task == 'get_Collembola_synonyms') { //print_r($rec); exit;
                $taxonomicStatus        = $rec['taxonomicStatus'];
                $acceptedNameUsageID    = $rec['acceptedNameUsageID'];
                
                $condition = $taxonomicStatus == 'synonym' && isset($this->Collembola_taxonIDs[$acceptedNameUsageID]);
                if($condition) { //print_r($rec); exit;
                    /*Array(
                        [taxonID] => 3011910
                        [identifier] => 
                        [datasetID] => 1130
                        [datasetName] => Collembola.org in Species 2000 & ITIS Catalogue of Life: 2020-08-01 Beta
                        [acceptedNameUsageID] => 3009726
                        [parentNameUsageID] => 
                        [taxonomicStatus] => synonym
                        [taxonRank] => species
                        [verbatimTaxonRank] => 
                        [scientificName] => Megalothorax bonetella Najt & Rapoport, 1965
                        [kingdom] => Animalia
                        [phylum] => 
                        [class] => 
                        [order] => 
                        [superfamily] => 
                        [family] => 
                        [genericName] => Megalothorax
                        [genus] => Megalothorax
                        [subgenus] => 
                        [specificEpithet] => bonetella
                        [infraspecificEpithet] => 
                        [scientificNameAuthorship] => Najt & Rapoport, 1965
                        [source] => 
                        [namePublishedIn] => 
                        [nameAccordingTo] => 
                        [modified] => 
                        [description] => 
                        [taxonConceptID] => 
                        [scientificNameID] => eabaf608-344b-4ff9-ae96-86df83b0014c
                        [references] => http://www.catalogueoflife.org/col/details/species/id/6e503f12ba03d36fb004aef898d6ff9e/synonym/8f40f23a98b9090e950f7218d7b1737f
                        [isExtinct] => 
                    )
                    In [COL_taxonIDs.txt]: this is the accepted taxa list
                    Collembola	6e503f12ba03d36fb004aef898d6ff9e	3009726
                    */
                    $ret = array();
                    $ret['z_partner'] = 'COL';
                    $ret['z_identifier'] = self::format_z_identifier('COL', $rec);
                    $ret['taxonID'] = self::format_taxonID('COL', $rec);
                    $ret['source'] = self::format_source('COL', $rec);
                    $ret['furtherInformationURL'] = self::format_furtherInformationURL('COL', $rec);
                    $ret['acceptedNameUsageID'] = $rec['acceptedNameUsageID'];
                    $ret['scientificName'] = $rec['scientificName'];
                    $ret['taxonRank'] = $rec['taxonRank'];
                    $ret['taxonomicStatus'] = 'not accepted';
                    $ret['datasetID'] = self::format_datasetID('COL', $rec);
                    $ret['canonicalName'] = self::format_canonicalName('COL', $rec, $ret['taxonRank']);
                    $save = array();
                    foreach($this->synonyms_headers as $head) $save[] = $ret[$head];
                    // print_r($save); print_r($this->synonyms_headers); exit;
                    fwrite($WRITE, implode("\t", $save)."\n");
                }
                // if($i >= 10) break;
            }
            //==============================================================================
            if($task == 'get_taxonIDs_2process') { //print_r($rec); exit;
                /*Array(
                    [partner] => NCBI
                    [taxonID] => 1935183
                )*/
                if($partner == $rec['partner']) $this->Partner_taxonIDs[$rec['taxonID']] = '';
            }
            //==============================================================================
            if($task == 'get_Partner_synonyms') { //print_r($rec); exit;
                /**/
                if($partner == 'COL2') $rec = self::rename_field_indexes($rec, $partner); //"dwc:taxonomicStatus" -> "taxonomicStatus"
                elseif(in_array($partner, array('ODO'))) {} //no need to adjust field indexes
                else exit("\n[$partner] not yet initialized 02.\n");
                
                $taxonomicStatus        = $rec['taxonomicStatus'];
                $acceptedNameUsageID    = $rec['acceptedNameUsageID'];
                
                if(in_array($partner, array('COL2', 'ODO'))) {
                    $condition = $taxonomicStatus == 'synonym' && isset($this->Partner_taxonIDs[$acceptedNameUsageID]);
                }
                else exit("\n[$partner] not yet initialized 03.\n");
                
                if($condition) { //print_r($rec); exit;
                    /*Array(    --- COL2
                        [dwc:taxonID] => 4BP2T
                        [dwc:parentNameUsageID] => 
                        [dwc:acceptedNameUsageID] => 6TH9B
                        [dwc:originalNameUsageID] => 
                        [dwc:datasetID] => 1029
                        [dwc:taxonomicStatus] => synonym
                        [dwc:taxonRank] => species
                        [dwc:scientificName] => Ozyptila schusteri Schick, 1965
                        [gbif:genericName] => Ozyptila
                        [dwc:specificEpithet] => schusteri
                        [dwc:infraspecificEpithet] => 
                        [dwc:nameAccordingTo] => 
                        [dwc:namePublishedIn] => 
                        [dwc:nomenclaturalCode] => ICZN
                        [dwc:nomenclaturalStatus] => 
                        [dwc:taxonRemarks] => 
                        [dcterms:references] => 
                    )
                    Array(    --- ODO
                        [taxonID] => Heliocharitidae
                        [acceptedNameUsageID] => Dicteriadidae
                        [parentNameUsageID] => 
                        [scientificName] => Heliocharitidae
                        [taxonRank] => family
                        [furtherInformationURL] => https://www.pugetsound.edu/academics/academic-resources/slater-museum/biodiversity-resources/dragonflies/world-odonata-list2/
                        [taxonomicStatus] => synonym
                        [taxonRemarks] => 
                        [higherClassification] => 
                    )
                    */
                    $ret = array();
                    $ret['z_partner'] = $partner;
                    $ret['z_identifier'] = self::format_z_identifier($partner, $rec); //none for COL2
                    $ret['taxonID'] = self::format_taxonID($partner, $rec);
                    $ret['source'] = self::format_source($partner, $rec);
                    $ret['furtherInformationURL'] = self::format_furtherInformationURL($partner, $rec);
                    $ret['acceptedNameUsageID'] = $rec['acceptedNameUsageID'];
                    $ret['scientificName'] = $rec['scientificName'];
                    $ret['taxonRank'] = $rec['taxonRank'];
                    $ret['taxonomicStatus'] = 'not accepted';
                    $ret['datasetID'] = self::format_datasetID($partner, $rec);
                    $ret['canonicalName'] = self::format_canonicalName($partner, $rec, $ret['taxonRank']);
                    $save = array();
                    foreach($this->synonyms_headers as $head) $save[] = $ret[$head];
                    print_r($save); print_r($this->synonyms_headers); //print_r($rec); exit;
                    fwrite($WRITE, implode("\t", $save)."\n");
                }
                // if($i >= 10) break;
            }
            
            //==============================================================================
            //==============================================================================

        } //end foreach()
        // if(in_array($task, array('assemble_taxonIDs_from_source_col', 'assemble_COL_identifiers'))) fclose($WRITE);
        if(isset($WRITE)) {
            if($WRITE) fclose($WRITE);
        }
        
    } // end parse_tsv()
    private function format_taxonID($partner, $rec)
    {
        
    }
    private function format_z_identifier($partner, $rec)
    {
        if($partner == 'COL') {
            if(preg_match("/synonym\/(.*?)eli3cha22/ims", $rec['references']."eli3cha22", $a)) return $a[1];
        }
        else return "-none for $partner-";
    }
    private function format_source($partner, $rec)
    {   /*source - Please construct the source values in the same way we did it for the accepted names: 
        source prefix:taxonID for COL2, ITIS, NCBI, ODO, WOR. 
        For COL, we need to use a workaround since the taxonIDs in this data set are ephemeral and there are no values in the identifier column 
            for synonyms. However, they do have a synonym identifier hidden in the urls they provide in the references column. 
            For example, in the url below, we can use the ID provided after synonym/, 
            resulting in a DH 2.1 source value of COL:3eb3b75ad13a5d0fbd1b22fa1074adc0
            http://www.catalogueoflife.org/annual-checklist/2019/details/species/id/6a3ba2fef8659ce9708106356d875285/synonym/3eb3b75ad13a5d0fbd1b22fa1074adc0*/
        if(in_array($partner, array('COL2', 'ITIS', 'NCBI', 'ODO', 'WOR'))) {
            return "$partner:".$rec['taxonID'];
        }
        elseif($partner == 'COL') {
            if(preg_match("/synonym\/(.*?)eli3cha22/ims", $rec['references']."eli3cha22", $a)) return "COL:".$a[1];
        }
    }
    private function format_canonicalName($partner, $rec, $taxonRank)
    {   /* canonicalName - Use the value from the canonicalName column for ITIS, 
            use gnparser to generate canonical forms for synonyms from the other data sets. 
            Use the full canonical form for taxa of rank subgenus, series, subseries, section, and subsection. 
            Use the simple canonical form for all other taxa. For taxa that don't get parsed, leave the canonicalName blank. */
        
        if($partner == 'ITIS') return $rec['canonicalName'];
        else {
            $canonical = $this->func->add_cannocial_using_gnparser($rec['scientificName'], $taxonRank); // exit("\n[$canonical]\n");
            return $canonical;
        }
    }
    private function format_datasetID($partner, $rec)
    {   /*datasetID - Please use the source prefix for ITIS, NCBI, ODO, WOR. For COL & COL2, 
        we want to use the datasetID value from the original COL data files. 
        Also, please use COL- as a prescript for these IDs. 
            For example, if the COL datasetID is 5, our datasetID would be COL-5. 
            If the COL datasetID is Species 2000 or if there is no datasetID available, simply use COL as the datasetID.*/
        if(in_array($partner, array('ITIS', 'NCBI', 'ODO', 'WOR'))) return $partner;
        elseif(in_array($partner, array('COL', 'COL2'))) {
            if(is_numeric($rec['datasetID'])) return "COL-".$rec['datasetID'];
            else return 'COL';
        }
    }
    private function format_furtherInformationURL($partner, $rec)
    {   /* furtherInformationURL - Please use the furtherInformationURL value for ITIS, NCBI, ODO, WOR. 
            For COL, use the value from the references column. 
            For COL2, construct the url as follows: http://www.catalogueoflife.org/data/taxon/[dwc:taxonID]*/
        if($partner == 'COL') return $rec['references'];
        elseif($partner == 'COL2') return "http://www.catalogueoflife.org/data/taxon/".$rec['taxonID'];
        elseif(in_array($partner, array('ITIS', 'NCBI', 'ODO', 'WOR'))) return $rec['furtherInformationURL'];
    }
    private function rename_field_indexes($rec, $partner)
    {
        /*Array(    --- COL2
            [dwc:taxonID] => 4BP2T
            [dwc:parentNameUsageID] => 
            [dwc:acceptedNameUsageID] => 6TH9B
            [dwc:originalNameUsageID] => 
            [dwc:datasetID] => 1029
            [dwc:taxonomicStatus] => synonym
            [dwc:taxonRank] => species
            [dwc:scientificName] => Ozyptila schusteri Schick, 1965
            [gbif:genericName] => Ozyptila
            [dwc:specificEpithet] => schusteri
            [dwc:infraspecificEpithet] => 
            [dwc:nameAccordingTo] => 
            [dwc:namePublishedIn] => 
            [dwc:nomenclaturalCode] => ICZN
            [dwc:nomenclaturalStatus] => 
            [dwc:taxonRemarks] => 
            [dcterms:references] => 
        )*/
        foreach($rec as $index => $value) {
            $new_index = str_replace("dwc:", "", $index);
            $new_index = str_replace("dcterms:", "", $new_index);
            $final[$new_index] = $value;
        }
        return $final;
    }
}