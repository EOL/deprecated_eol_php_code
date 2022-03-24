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
        $this->tsv['synonyms_NCBI'] = $this->main_path."/synonyms_NCBI.txt";
        $this->tsv['synonyms_WOR'] = $this->main_path."/synonyms_WOR.txt";
        $this->tsv['synonyms_ITIS'] = $this->main_path."/synonyms_ITIS.txt";

        $this->tsv['synonyms_problematic_COL'] = $this->main_path."/synonyms_problematic_COL.txt";
        $this->tsv['synonyms_problematic_Collembola'] = $this->main_path."/synonyms_problematic_Collembola.txt";
        $this->tsv['synonyms_problematic_COL2'] = $this->main_path."/synonyms_problematic_COL2.txt";
        $this->tsv['synonyms_problematic_ODO'] = $this->main_path."/synonyms_problematic_ODO.txt";
        $this->tsv['synonyms_problematic_NCBI'] = $this->main_path."/synonyms_problematic_NCBI.txt";
        $this->tsv['synonyms_problematic_WOR'] = $this->main_path."/synonyms_problematic_WOR.txt";
        $this->tsv['synonyms_problematic_ITIS'] = $this->main_path."/synonyms_problematic_ITIS.txt";

        $this->tsv['synonyms_upd_1_COL']        = $this->main_path."/synonyms_upd_1_COL.txt";
        $this->tsv['synonyms_upd_1_Collembola'] = $this->main_path."/synonyms_upd_1_Collembola.txt";
        $this->tsv['synonyms_upd_1_COL2']       = $this->main_path."/synonyms_upd_1_COL2.txt";
        $this->tsv['synonyms_upd_1_ODO']        = $this->main_path."/synonyms_upd_1_ODO.txt";
        $this->tsv['synonyms_upd_1_NCBI']       = $this->main_path."/synonyms_upd_1_NCBI.txt";
        $this->tsv['synonyms_upd_1_WOR']        = $this->main_path."/synonyms_upd_1_WOR.txt";
        $this->tsv['synonyms_upd_1_ITIS']       = $this->main_path."/synonyms_upd_1_ITIS.txt";

        $this->tsv['synonyms_upd_2_COL']        = $this->main_path."/synonyms_upd_2_COL.txt";
        $this->tsv['synonyms_upd_2_Collembola'] = $this->main_path."/synonyms_upd_2_Collembola.txt";
        $this->tsv['synonyms_upd_2_COL2']       = $this->main_path."/synonyms_upd_2_COL2.txt";
        $this->tsv['synonyms_upd_2_ODO']        = $this->main_path."/synonyms_upd_2_ODO.txt";
        $this->tsv['synonyms_upd_2_NCBI']       = $this->main_path."/synonyms_upd_2_NCBI.txt";
        $this->tsv['synonyms_upd_2_WOR']        = $this->main_path."/synonyms_upd_2_WOR.txt";
        $this->tsv['synonyms_upd_2_ITIS']       = $this->main_path."/synonyms_upd_2_ITIS.txt";

        $this->tsv['Consolidated_Syn_1']       = $this->main_path."/synonyms_consolidated_1.txt";
        $this->tsv['Consolidated_Syn_2']       = $this->main_path."/synonyms_consolidated_2.txt";
        $this->tsv['Consolidated_Syn_3']       = $this->main_path."/synonyms_consolidated_3.txt";
        $this->tsv['Consolidated_Syn_4']       = $this->main_path."/synonyms_consolidated_4.txt";
        
        
        /* start of COL2 and the rest */
        $this->tsv['COL_2021'] = $this->main_path."/data/COL_2021_dwca/Taxon.tsv";
        
        // if(file_exists($this->tsv['Collembola'])) exit("\nfile exists ok\n");
        // else exit("\nfile does not exist...\n");
        
        $this->tsv['WorldOdonataList'] = $this->main_path."/data/worldodonatalist/taxa.txt";

        // /*
        $this->tsv['NCBI_source'] = $this->main_path."/data/NCBI_Taxonomy_Harvest/taxon.tab";
        // from NCBI_Taxonomy_Harvest.tar.gz
        // generated by: php5.6 dwh_ncbi_TRAM_795.php jenkins '{"with_Vernaculars": 1}'
        // */

        // /*
        $this->tsv['WOR_source'] = $this->main_path."/data/WoRMS2EoL/taxon.txt";
        // from partner: http://www.marinespecies.org/export/eol/WoRMS2EoL.zip
        // */
        
        // /*
        $this->tsv['ITIS_source'] = $this->main_path."/data/itis_2022-02-28_all_nodes/taxon.tab";
        // php5.6 dwh_itis.php jenkins '{"allNodesYN":"1", "resource_id":"itis_2022-02-28"}'
        // -> generates itis_2022-02-28_all_nodes.tar.gz
        // php5.6 synonyms_handling.php jenkins itis_2022-02-28_all_nodes
        // -> generates itis_2022-02-28_all_nodes.tar.gz (smaller size)
        // */
        
        $this->min_synonym_headers  = array('taxonID', 'source', 'acceptedNameUsageID',                           'scientificName', 'taxonRank', 'canonicalName', 'taxonomicStatus', 'furtherInformationURL', 'datasetID', 'hash');
        $this->min_synonym_headers2 = array('taxonID', 'source', 'acceptedNameUsageID', 'DH_acceptedNameUsageID', 'scientificName', 'taxonRank', 'canonicalName', 'taxonomicStatus', 'furtherInformationURL', 'datasetID', 'hash');
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
        /*
        $head = array('z_partner', 'z_identifier');
        $head = array_merge($head, $this->min_synonym_headers);
        $this->synonyms_headers = $head; // print_r($head); exit;
        */
        $this->synonyms_headers = $this->min_synonym_headers;
        
        /* step 3: assemble synonyms --- COL
        self::parse_tsv($this->tsv['COL_taxonIDs'], 'get_COL_taxonIDs COL', false); //creates $this->COL_taxonIDs
        $WRITE = fopen($this->tsv['synonyms_COL'], "w"); fwrite($WRITE, implode("\t", $this->synonyms_headers)."\n");
        self::parse_tsv($this->tsv['COL_2019_new'], 'get_COL_synonyms', $WRITE, 'COL');
        */
        
        /* step 4: assemble synonyms --- COL Collembola
        self::parse_tsv($this->tsv['COL_taxonIDs'], 'get_COL_taxonIDs Collembola', false); //creates $this->Collembola_taxonIDs
        // print_r($this->Collembola_taxonIDs); echo "\nCollembola_taxonIDs: ".count($this->Collembola_taxonIDs)."\n";
        $WRITE = fopen($this->tsv['synonyms_Collembola'], "w"); fwrite($WRITE, implode("\t", $this->synonyms_headers)."\n");
        self::parse_tsv($this->tsv['Collembola_new'], 'get_Collembola_synonyms', $WRITE, 'COL');
        */
        // exit("\n-stop 1-\n");
        /* ======== start for COL2, ITIS, NCBI, ODO, WOR ======== */
        $head = $this->min_synonym_headers;
        $this->synonyms_headers = $head; // print_r($head); exit;
        
        /* all five prefixes worked OK
        $partners = array('COL2', 'ITIS', 'NCBI', 'ODO', 'WOR');
        // $partners = array('COL2'); //during dev only
        // $partners = array('ODO'); //during dev only
        // $partners = array('NCBI'); //during dev only
        // $partners = array('WOR'); //during dev only
        // $partners = array('ITIS'); //during dev only
        foreach($partners as $partner) {
            $this->Partner_taxonIDs = array(); //it is a partner-exclusive var., thus it is being initialized for every partner prefix.
            self::parse_tsv($this->tsv['taxonIDs_from_source_col'], 'get_taxonIDs_2process', false, $partner); //generate $this->Partner_taxonIDs
            echo "\n$partner: ".count($this->Partner_taxonIDs)."\n";
            $WRITE = fopen($this->tsv['synonyms_'.$partner], "w"); fwrite($WRITE, implode("\t", $head)."\n");
                if($partner == 'COL2') $source_file = 'COL_2021';
            elseif($partner == 'ODO')  $source_file = 'WorldOdonataList';
            elseif($partner == 'NCBI') $source_file = 'NCBI_source';
            elseif($partner == 'WOR')  $source_file = 'WOR_source';
            elseif($partner == 'ITIS')  $source_file = 'ITIS_source';
            else exit("\n$partner not yet initialized 01.\n");
            
            self::parse_tsv($this->tsv[$source_file], 'get_Partner_synonyms', $WRITE, $partner);
        }
        */
        
        /* a utility before #3 - just a utility not part of normal operation ---> SUCCESS all synonym rows are unique from all 7 partners OK
        $partners = array('Collembola', 'COL', 'COL2', 'ITIS', 'NCBI', 'ODO', 'WOR');
        foreach($partners as $partner) {
            echo "\naccessing...".$this->tsv['synonyms_'.$partner]."\n";
            self::parse_tsv($this->tsv['synonyms_'.$partner], 'util_1', false, $partner);
        }
        exit("\n-end utility-\n");
        */
        
        /* #3. Filter out problematic synonyms
        Once we have all the synonyms from the source files, we need to filter out synonyms that contradict an accepted name assertion in DH 2.1.
        For example, this synonym from ITIS:
        552289 https://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=552289#null	727501 Xenarthra Cope, 1889 order Cope, 1889 invalid other, see comments Xenarthra

        ITIS	-none for ITIS-		ITIS:552289	727501	Xenarthra Cope, 1889	order	Xenarthra	not accepted	https://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=552289#null	ITIS
        -> from synonyms_ITIS.txt

        conflicts with this DH 2.1 accepted name assertion:
        EOL-000000628304 MAM:Xenarthra EOL-000000628303 Xenarthra accepted MAM Xenarthra 1308046

        To find conflicts between synonym and DH 2.1 accepted name data, 
        compare the canonical of each synonym to all canonicals of accepted names in the DH. 
        If the canonical of a synonym matches the canonical of any DH 2.1 accepted name, discard the synonym, unless one of the following is true:
        A. The only DH 2.1 accepted name match to the synonym is the accepted name of the synonym.

        For example this synonym from ODO:
        Lestes-scalaris-1 Lestes-scalaris Lestes scalaris Calvert, 1909 species https://www.pugetsound.edu/academics/academic-resources/slater-museum/biodiversity-resources/dragonflies/world-odonata-list2/	synonym

        ODO	-none for ODO-		ODO:Lestes-scalaris-1	Lestes-scalaris	Lestes scalaris Calvert, 1909	species	Lestes scalaris	not accepted	https://www.pugetsound.edu/academics/academic-resources/slater-museum/biodiversity-resources/dragonflies/world-odonata-list2/	ODO
        -> from synonyms_ODO.txt
        
        maps to this accepted DH 2.1 taxon:
        EOL-000000983393 ODO:Lestes-scalaris https://www.pugetsound.edu/academics/academic-resources/slater-museum/biodiversity-resources/dragonflies/world-odonata-list2/	EOL-000000983342 Lestes scalaris Gundlach, 1888 species accepted ODO Lestes scalaris 1034222

        We want to keep this synonym because the only canonical DH 2.1 canonical match for it is its accepted name, which is ok.

        B. The rank of the synonym is genus and the rank of the DH 2.1 canonical match is not genus.
        C. The rank of the DH 2.1 canonical match is genus and the rank of the synonym is not genus.

        This process will also remove some synonyms that are not actually conflicting with DH assertions, 
        e.g., in the case of same rank homonyms, but that's ok for now. 
        Please report all the synonyms that were removed during this step 
        (scientificName, source, acceptedNameUsageID, taxonID of other DH taxon for which there is a canonical match).
        */
        /* start #3
        // $partners = array('Collembola', 'COL', 'COL2', 'ITIS', 'NCBI', 'ODO', 'WOR');
        // $partners = array('COL'); //during dev only
        $partners = array('Collembola'); //during dev only
        // $partners = array('COL2'); //during dev only
        // $partners = array('ODO'); //during dev only
        // $partners = array('NCBI'); //during dev only
        // $partners = array('WOR'); //during dev only
        // $partners = array('ITIS'); //during dev only
        foreach($partners as $partner) {
            $this->syn_canonical = array();                 //initialize - partner exclusive
            $this->syn_canonical_matched_DH21 = array();    //initialize - partner exclusive
            self::parse_tsv($this->tsv['synonyms_'.$partner], 'open_Partner_synonyms', false, $partner);
            self::parse_tsv($this->tsv['DH21_current'], 'check_syn_with_DH21_canonical', false, $partner);
            $this->to_be_removed = self::main_3($partner);
            // print_r($this->syn_canonical_matched_DH21); print_r($this->to_be_removed); exit; //debug only
            unset($this->syn_canonical);
            unset($this->syn_canonical_matched_DH21);
            
            //start refresh synonyms 1
            // if(in_array($partner, array('COL', "Collembola"))) $head = array_merge(array('z_partner', 'z_identifier'), $this->min_synonym_headers);
            // else $head = $this->min_synonym_headers;
            // $this->synonyms_headers = $head;
            $this->synonyms_headers = $this->min_synonym_headers;
            $WRITE = fopen($this->tsv['synonyms_upd_1_'.$partner], "w"); fwrite($WRITE, implode("\t", $this->synonyms_headers)."\n");
            self::parse_tsv($this->tsv['synonyms_'.$partner], 'update_1', $WRITE, $partner);
        }
        exit("\n-stop 2-\n");
        */ //end #3
        
        /* START --- replace syn acceptedNameUsageID with DH21 acceptedNameUsageID --- for non-COL
        $partners = array('COL2', 'ITIS', 'NCBI', 'ODO', 'WOR');
        // $partners = array('ODO'); //during dev only
        $this->synonyms_headers = $this->min_synonym_headers2;
        foreach($partners as $partner) {
            $this->identifier_taxonID_info = array(); //partner exclusive
            self::parse_tsv($this->tsv['DH21_current'], 'build_identifier_taxonID_info', false, $partner); //builds $this->identifier_taxonID_info
            echo "\nidentifier_taxonID_info [$partner]: ".count($this->identifier_taxonID_info)."\n"; //exit;
            $WRITE = fopen($this->tsv['synonyms_upd_2_'.$partner], "w"); fwrite($WRITE, implode("\t", $this->synonyms_headers)."\n");
            self::parse_tsv($this->tsv['synonyms_upd_1_'.$partner], 'update_2', $WRITE, $partner);
        }
        exit("\n-stop 3-\n");
        END --- */

        /* START --- replace syn acceptedNameUsageID with DH21 acceptedNameUsageID --- for COL
        $partners = array('Collembola', 'COL');
        $partners = array('Collembola'); //during dev only
        $partners = array('COL'); //during dev only
        $this->synonyms_headers = $this->min_synonym_headers2;
        foreach($partners as $partner) {
            $this->accepted_identifier_info = array(); //partner exclusive
            self::parse_tsv($this->tsv['COL_taxonIDs'], 'build_accepted_identifier_info', false, $partner); //builds $this->accepted_identifier_info()

            $this->identifier_taxonID_info = array(); //partner exclusive
            self::parse_tsv($this->tsv['DH21_current'], 'build_identifier_taxonID_info', false, $partner); //builds $this->identifier_taxonID_info

            echo "\nidentifier_taxonID_info [$partner]: ".count($this->identifier_taxonID_info)."\n"; //exit;
            $WRITE = fopen($this->tsv['synonyms_upd_2_'.$partner], "w"); fwrite($WRITE, implode("\t", $this->synonyms_headers)."\n");
            self::parse_tsv($this->tsv['synonyms_upd_1_'.$partner], 'update_2', $WRITE, $partner);
        }
        exit("\n-stop 4-\n");
        END --- */
        

        /* investigate syn from DH21
        self::parse_tsv($this->tsv['DH21_current'], 'check', false, '');
        print_r($this->debug); echo "\n".count($this->debug['datasetID'])."\n"; exit;
        */
        
        /* 4. Deduplicate synonyms
        For this step, please add the manually curated synonyms from the DH 2.1 file to the other synonyms. 
        These all have taxonID values starting with SYN-.

        If we get exactly the same synonym from more than one source, we should keep only one version, using the following criteria:

        Two synonyms are equivalent IF both of the following are true:
        A. They have the same acceptedNameUsageID
        B. They have exactly the same scientificName

        In cases where this happens, discard the synonyms from lower priority resources using the following source data set 
        priority sequence: ITIS > WOR > COL2 > COL > NCBI > ODO > trunk

        Where ITIS is the highest priority data set and trunk is the lowest priority data set, 
        i.e., if there is a manually curated synonym that’s already in one of the other synonym data sets, 
        we want to get rid of the manually curated one.
        */
        
        /* #4 Deduplicate synonyms */
        /* step 1: consolidate all synonyms
        $partners = array('trunk', 'Collembola', 'COL', 'COL2', 'ITIS', 'NCBI', 'ODO', 'WOR'); //complete
        // $partners = array('ODO'); //during dev only
        // $partners = array('trunk'); //during dev only
        // $partners = array('trunk', 'COL2', 'ITIS', 'NCBI', 'ODO', 'WOR'); //during dev only
        // $partners = array('Collembola', 'COL'); //during dev only
        $this->synonyms_headers = $this->min_synonym_headers2;
        $WRITE = fopen($this->tsv['Consolidated_Syn_1'], "w"); fwrite($WRITE, implode("\t", $this->synonyms_headers)."\n"); fclose($WRITE);
        foreach($partners as $partner) {
            $WRITE = fopen($this->tsv['Consolidated_Syn_1'], "a");
            if($partner == 'trunk') $source_file = 'DH21_current';
            else                    $source_file = 'synonyms_upd_2_'.$partner;
            self::parse_tsv($this->tsv[$source_file], 'consolidate_synonyms', $WRITE, $partner);
        }
        print_r($this->debug);
        exit("\n-stop 4-\n");
        */
        /*Array(
            [trunk syn] => 2138
            [Collembola syn] => 3272
            [COL syn] => 1334518
            [COL2 syn] => 9580
            [ITIS syn] => 61650
            [NCBI syn] => 15545
            [ODO syn] => 4170
            [WOR syn] => 168418
            1,599,292 synonyms_consolidated_1.txt
        )*/

        /* ===== tests only =====
        $this->taxonIDs_2remove = array(); $this->hashes_2remove = array();
        $recs = array();
        $recs[] = Array(
                    'taxonID' => 'SYN-000001683001',                    'source' => '',
                    'acceptedNameUsageID' => 'EOL-000000084582',        'DH_acceptedNameUsageID' => 'EOL-000000084582',
                    'scientificName' => 'Acantharia',                   'taxonRank' => 'class',
                    'canonicalName' => 'Acantharia',                    'taxonomicStatus' => 'not accepted',
                    'furtherInformationURL' => '',                      //'datasetID' => 'ITIS',
                                                                        'datasetID' => 'trunk',
                    'hash' => ''
                );
        $recs[] = Array(
                    'taxonID' => '',                                    'source' => 'NCBI:65574_2',
                    'acceptedNameUsageID' => '65574',                   'DH_acceptedNameUsageID' => 'EOL-000000084582',
                    'scientificName' => 'Acantharia',                   'taxonRank' => 'class',
                    'canonicalName' => 'Acantharia',                    'taxonomicStatus' => 'not accepted',
                    'furtherInformationURL' => 'https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=65574',
                    'datasetID' => 'NCBI',
                    // 'datasetID' => 'ITIS',
                    'hash' => 'da6783c16b93c4810442ca5e97fdb0de'
                );
        self::parse_combo($recs);
        print_r($this->taxonIDs_2remove); print_r($this->hashes_2remove);
        exit("\n-end test\n");
        */
        
        
        /* step 2: record combo hits
        $this->combo_hits = array();
        self::record_combo_hits('Consolidated_Syn_1');
            // total combo hits: 1599027
            // total raw combo hits: [251]
            // taxonIDs_2remove: 16
            // hashes_2remove: 241
        // step 3: remove duplicate syns in Consolidated_Syn_1
        $this->synonyms_headers = $this->min_synonym_headers2;
        $WRITE = fopen($this->tsv['Consolidated_Syn_2'], "w"); fwrite($WRITE, implode("\t", $this->synonyms_headers)."\n");
        self::parse_tsv($this->tsv['Consolidated_Syn_1'], 'consolidate_synonyms_2', $WRITE, '');
        print_r($this->debug);
        exit("\n-stop 5-\n");
        // 1,599,036 synonyms_consolidated_2.txt
        */
        
        /* ---------- test Consolidated_Syn_2 if there are still duplicates --- there should be none/zero
        $this->combo_hits = array();
        self::record_combo_hits('Consolidated_Syn_2');
            // total raw combo hits: [0]
            // taxonIDs_2remove: 0
            // hashes_2remove: 0
        exit("\n-stop 6-\n");
        // self::record_combo_hits('Consolidated_Syn_1'); //just for testing, backtrack a bit. Not part of normal operation
        */
        
        /* ---------- investigate DH11 before going to #5
        self::parse_tsv($this->tsv['DH11'], 'check', false, '');
        print_r($this->debug); exit("\n-end check-\n");
        */
        /* DH11 synonyms:
        Array(
            [source] => Array(
                    [NCBI] => 
                    [ASW] => 
                    [ODO] => 
                    [BOM] => 
                    [COL] => 
                )
            [DH11 synonyms total] => 1,680,845
        )*/
        /* ---------- investigate
        self::parse_tsv($this->tsv['Consolidated_Syn_2'], 'check', false, '');
        print_r($this->debug); exit("\n-end check-\n");
        */
        /* from Consolidated_Syn_2
        [source] => Array(
                DH21        DH11
                [COL] =>    --- COL
                [COL2] =>   --- COL
                [ITIS] =>   no DH11 syn
                [NCBI] =>   --- NCBI
                [ODO] =>    --- ODO
                [WOR] =>    no DH11 syn
        [DH21 synonyms total] => 1,596,905
        
        A. The scientificName value is exactly the same
        B. The acceptedNameUsageID value is exactly the same
        C. The source value in DH 1.1 corresponds to the source prefix in DH 2.1, based on the following criteria:
        • For DH 2.1 synonyms with source prefixes COL or COL2 look only for DH 1.1 matches where source is COL
        • For DH 2.1 synonyms with source prefix NCBI look only for DH 1.1 matches where source is NCBI
        • For DH 2.1 synonyms with source prefix ODO look only for DH 1.1 matches where source is ODO
        • For DH 2.1 synonyms that lack a source value, look for DH 1.1. matches where source is BOM or where the source value is empty.
        There were no ITIS or WOR synonyms in DH 1.1, so you don’t need to worry about finding matches for those.
        */

        /* #5. Assign taxonID values for synonyms
        self::parse_tsv($this->tsv['Consolidated_Syn_2'], 'build_SN_Accepted_prefix_info', false, ''); //builds $this->SN_Accepted_prefix_info
        self::parse_tsv($this->tsv['DH11'], 'find_hits_DH21_DH11', false, ''); //builds $this->hits_DH21_DH11
        // print_r($this->new_id);
        echo "\nnew_ids: ".count($this->new_id)."\n";
        unset($this->SN_Accepted_prefix_info);
        unset($this->hits_DH21_DH11);
        //     [26980b1945e1c76bcce741995f2bd170] => SYN-000001673780
        //     [23767120207cfcc7c1239585d31f217e] => SYN-000001673781
        //     [bda4241ca3611e3c267f8e13ab8820d2] => SYN-000001673782
        // new_ids: 1,324,011 --- salvaged SYN- IDs from DH11 that can be used in DH21 synonyms.

        $this->synonyms_headers = $this->min_synonym_headers2;
        $WRITE = fopen($this->tsv['Consolidated_Syn_3'], "w"); fwrite($WRITE, implode("\t", $this->synonyms_headers)."\n");
        self::parse_tsv($this->tsv['Consolidated_Syn_2'], 'save_new_SYN_ids_to_DH21_synonyms', $WRITE, '');
        print_r($this->debug);
        */
        // as of Mar 24, 2022
        // 1599292 synonyms_consolidated_1.txt
        // 1599036 synonyms_consolidated_2.txt
        // 1599037 synonyms_consolidated_3.txt
        
        /* investigate SYN- series
        $this->old_SYN_id = 'SYN-000000000000';
        // $source_file = 'Consolidated_Syn_3';
        // $source_file = 'DH11';
        $source_file = 'DH21_current';
        self::parse_tsv($this->tsv[$source_file], 'check', false, '');
        echo "\nbiggest SYN_id [$source_file]: $this->old_SYN_id\n";
        */
        // biggest SYN_id [Consolidated_Syn_3]: SYN-000001684772
        // biggest SYN_id [DH11]:               SYN-000001682086
        // biggest SYN_id [DH21_current]:       SYN-000001684772
        
        // /* LAST PART: assigning "SYN-xxx" for left un-matched synonyms. And making SYN- IDs unique.
        $this->SYN_series = "SYN-000001684772";
        $this->SYN_series = "SYN-100000000000"; //considered series 2 for any intent and purpose - GOOD OK
        $this->SYN_series = "SYN-1"; //+ 11 zeros
        $this->SYN_ctr = 0;
        $this->synonyms_headers = $this->min_synonym_headers; //used the less no. of header fields vs $this->min_synonym_headers2
        $WRITE = fopen($this->tsv['Consolidated_Syn_4'], "w"); fwrite($WRITE, implode("\t", $this->synonyms_headers)."\n");
        self::parse_tsv($this->tsv['Consolidated_Syn_3'], 'assigning_new_SYN_id_series', $WRITE, '');
        Functions::show_totals($this->tsv['Consolidated_Syn_4']);
        self::parse_tsv($this->tsv['Consolidated_Syn_4'], 'check_taxonID_if_unique', false, '');
        Functions::show_totals($this->tsv['Consolidated_Syn_4']);
        // */
        // /Volumes/AKiTiO4/d_w_h/TRAM-996//synonyms_consolidated_4.txt: [1,599,038] as of Mar 24, 2022
        // /Volumes/AKiTiO4/d_w_h/TRAM-996//synonyms_consolidated_4.txt: [1,599,028] as of Mar 24, 2022, after removing blank rows.
    }
    private function record_combo_hits($source_file)
    {
        self::parse_tsv($this->tsv[$source_file], 'find_combo_hits', false, ''); //builds $this->combo_hits
        // print_r($this->combo_hits);
        echo "\ntotal combo hits: ".count($this->combo_hits)."\n";
        $this->taxonIDs_2remove = array(); $this->hashes_2remove = array();
        self::parse_combo_hits();
        // print_r($this->taxonIDs_2remove); print_r($this->hashes_2remove);
        echo "\ntaxonIDs_2remove: ".count($this->taxonIDs_2remove)."\n";
        echo "\nhashes_2remove: ".count($this->hashes_2remove)."\n";
    }
    private function parse_combo_hits()
    {   $i = 0;
        foreach($this->combo_hits as $combo => $recs) {
            if(count($recs) > 1) { $i++;
                // echo "\n[$combo]"; print_r($recs);
                self::parse_combo($recs);
            }
        }
        echo "\ntotal raw combo hits: [$i]\n";
    }
    function parse_combo($recs)
    {   //print_r($recs); //exit;
        /* In cases where this happens, discard the synonyms from lower priority resources using the 
        following source data set priority sequence: ITIS > WOR > COL2 > COL > NCBI > ODO > trunk */
        $p['ITIS'] = 1;
        $p['WOR'] = 2;
        $p['COL2'] = 3;
        $p['COL'] = 4;
        $p['NCBI'] = 5;
        $p['ODO'] = 6;
        $p['trunk'] = 7;
        $final = array();
        foreach($recs as $rec) {
            $datasetID = $rec['datasetID'];
            if(substr($datasetID,0,4) == 'COL-') $datasetID = 'COL'; //COL-1130
            if($index = $p[$datasetID]) $final[$index] = $rec;
            else { print_r($rec); exit("\ndatasetID not yet initialized\n"); }
        }
        //print_r($final);
        asort($final); //print_r($final);
        $i = 0;
        foreach($final as $rec) { $i++;
            if($i == 1) { //echo "\nretain"; print_r($rec);
                if($val = $rec['taxonID']) $taxonIDs_2retain[$val] = '';
                if($val = $rec['hash']) $hashes_2retain[$val] = '';
            }
            // else {
            //     // echo "\nremove"; print_r($rec);
            //     if($val = $rec['taxonID']) $this->taxonIDs_2remove[$val] = '';
            //     if($val = $rec['hash']) $this->hashes_2remove[$val] = '';
            // }
        }
        //refresh
        foreach($recs as $rec) {
            if(isset($taxonIDs_2retain[$rec['taxonID']]) || isset($hashes_2retain[$rec['hash']])) {}
            else {
                if($val = $rec['taxonID']) $this->taxonIDs_2remove[$val] = '';
                if($val = $rec['hash']) $this->hashes_2remove[$val] = '';
            }
        }
    }
    private function main_3($partner)
    {   // print_r($this->syn_canonical_matched_DH21); exit;
        echo "\ntotal: ".count($this->syn_canonical_matched_DH21)."\n";
        /**/
        
        $WRITE = fopen($this->tsv['synonyms_problematic_'.$partner], "w");
        $head = array('scientificName', 'source', 'acceptedNameUsageID', 'DH_taxonID', 'DH_identifier', 'syn_hash');
        fwrite($WRITE, implode("\t", $head)."\n");
        
        $to_be_removed = array();
        foreach($this->syn_canonical_matched_DH21 as $synonym => $recs) {
            if(count($recs) > 1) { //print_r($recs); exit("more than 1 [$synonym]");
                /*Array(
                    [0] => Array(
                            [s] => Array(
                                    [sn] => Paludicola Wagler, 1830
                                    [s] => ITIS:1094751
                                    [r] => genus
                                    [a] => 207817
                                    [h] => 0d605965b4b729718b59cca15cb9b143
                                )
                            [H] => Array(
                                    [p] => NCBI
                                    [i] => 2038676
                                    [t] => EOL-000000009869
                                )
                        )
                    [1] => Array(
                            [s] => Array(
                                    [sn] => Paludicola Wagler, 1830
                                    [s] => ITIS:1094751
                                    [r] => genus
                                    [a] => 207817
                                    [h] => 0d605965b4b729718b59cca15cb9b143
                                )
                            [H] => Array(
                                    [p] => NCBI
                                    [i] => 2729669
                                    [t] => EOL-000002500239
                                )
                        )
                )*/
                foreach($recs as $rec) {
                    // /* block copied below, except for the 'break;' row
                $DH_prefix     = $rec['H']['p'];
                $DH_identifier = $rec['H']['i'];
                $DH_taxonID    = $rec['H']['t'];
                if($rec['s']['a'] != $DH_identifier) { // acceptedNameUsageID neq identifier
                    $to_be_removed[$rec['s']['h']] = ''; //to be removed
                    $save = array($rec['s']['sn'], $rec['s']['s'], $rec['s']['a'], $DH_taxonID, $DH_identifier, $rec['s']['h']);
                    fwrite($WRITE, implode("\t", $save)."\n");
                    break;
                }
                else { //at this point: acceptedNameUsageID == DH_identifier
                    if($partner != $DH_prefix) {
                        $to_be_removed[$rec['s']['h']] = ''; //to be removed
                        $save = array($rec['s']['sn'], $rec['s']['s'], $rec['s']['a'], $DH_taxonID, $DH_identifier, $rec['s']['h']);
                        fwrite($WRITE, implode("\t", $save)."\n");
                        break;
                    }
                    else { //those synonyms that are not removed
                        // print_r($rec); //good debug
                    }
                }
                    // */
                }
                
            }
            else {
                $rec = $recs[0]; //print_r($rec); exit("\nelix 3\n");
                /*Array(
                    [s] => Array(
                            [sn] => Aeshna annulata Latreille, 1805 (nec Fabricius, 1798)
                            [s] => ODO:Aeshna-annulata-4
                            [r] => species
                            [a] => Cordulegaster-boltonii
                            [h] => 09304eebc6da2261746e5e18f7cfd1ca
                        )
                    [H] => Array(
                            [p] => ODO
                            [i] => Aeshna-annulata
                            [t] => EOL-000000976787
                        )
                )
                */
                $DH_prefix     = $rec['H']['p'];
                $DH_identifier = $rec['H']['i'];
                $DH_taxonID    = $rec['H']['t'];
                if($rec['s']['a'] != $DH_identifier) { // acceptedNameUsageID neq identifier
                    $to_be_removed[$rec['s']['h']] = ''; //to be removed
                    $save = array($rec['s']['sn'], $rec['s']['s'], $rec['s']['a'], $DH_taxonID, $DH_identifier, $rec['s']['h']);
                    fwrite($WRITE, implode("\t", $save)."\n");
                }
                else { //at this point: acceptedNameUsageID == DH_identifier
                    if($partner != $DH_prefix) {
                        $to_be_removed[$rec['s']['h']] = ''; //to be removed
                        $save = array($rec['s']['sn'], $rec['s']['s'], $rec['s']['a'], $DH_taxonID, $DH_identifier, $rec['s']['h']);
                        fwrite($WRITE, implode("\t", $save)."\n");
                    }
                    else { //those synonyms that are not removed
                        // print_r($rec); //good debug
                    }
                }
            }
        }
        fclose($WRITE);
        echo "\nto_be_removed [$partner]: [".count($to_be_removed)."]\n";
        return $to_be_removed;
    }
    private function parse_tsv($txtfile, $task, $WRITE = false, $partner = '')
    {   $i = 0; echo "\nStart $task...[$partner]\n";
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 300000) == 0) echo "\n[$task] - ".number_format($i)." ";
            // if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields);
                $fields = array_map('trim', $fields);
                // print_r($fields); //exit;
                continue;
            }
            else {
                if($task == 'open_Partner_synonyms') {
                    // print_r($row); exit("\nupdate this script 1\n");
                    if(!@$row[9]) continue; //'hash'
                }
                elseif(in_array($task, array('util_1', 'update_1', 'update_2'))) {
                    // print_r($row); exit("\nupdate this script 2\n");
                    if(!@$row[1]) continue; //'source'
                }
                // elseif($task == 'consolidate_synonyms' & $partner == 'trunk') {
                //     if(!@$row[0]) continue; //to capture taxonID e.g. "SYN-000001681243"
                // }
                elseif(in_array($task, array('consolidate_synonyms', 'find_combo_hits', 'consolidate_synonyms_2'))) {} //just get all recs encountered from tsv
                elseif($task == 'get_Collembola_synonyms') {
                    if(!@$row[0]) continue;
                }
                else { //rest goes here
                    // if(!@$row[1]) continue;
                }
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
            if($task == 'check') { //print_r($rec); exit;
                /*Array(
                    [taxonID] => SYN-000001683069
                    [source] => 
                    [acceptedNameUsageID] => EOL-000003165652
                    [DH_acceptedNameUsageID] => EOL-000003165652
                    [scientificName] => 2019-nCoV
                    [taxonRank] => 
                    [canonicalName] => 2019-nCoV
                    [taxonomicStatus] => not accepted
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [hash] => 
                )*/
                /* check if SYN ids are unique: OK they are unique [synonyms_consolidated_3.txt]
                $taxonID = $rec['taxonID'];
                if(substr($rec['taxonID'],0,4) == "SYN-") {
                    if(!isset($unique[$taxonID])) $unique[$taxonID] = '';
                    else exit("\nnon unique SYN id: [$taxonID]\n");
                }
                */
                /* getting biggest SYN-xxx
                $taxonID = $rec['taxonID'];
                if(substr($rec['taxonID'],0,4) == "SYN-") {
                    if($taxonID > $this->old_SYN_id) $this->old_SYN_id = $taxonID;
                }
                */
                /*
                if(substr($rec['taxonID'],0,4) == "SYN-") { //print_r($rec); exit;
                    $this->debug['source'][$rec['source']] = '';
                    @$this->debug['DH11 synonyms total']++;
                }
                */
                /*
                $ret = self::get_prefix_identifier_from_source($rec['source']);
                $this->debug['source'][$ret[0]] = '';
                @$this->debug['DH21 synonyms total']++;
                */
            }
            //==============================================================================
            if($task == 'assigning_new_SYN_id_series') { //print_r($rec); exit;
                /*Array(
                    [taxonID] => SYN-000001683069
                    [source] => 
                    [acceptedNameUsageID] => EOL-000003165652
                    [DH_acceptedNameUsageID] => EOL-000003165652
                    [scientificName] => 2019-nCoV
                    [taxonRank] => 
                    [canonicalName] => 2019-nCoV
                    [taxonomicStatus] => not accepted
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [hash] => 
                )*/
                if(!$rec['taxonID']) {
                    /* this is the series used: $this->SYN_series = "SYN-1"; //+ 11 zeros */
                    $this->SYN_ctr++;
                    $rec['taxonID'] = $this->SYN_series.Functions::format_number_with_leading_zeros($this->SYN_ctr, 11); //meaning 11 zeros
                }
                $rec['acceptedNameUsageID'] = $rec['DH_acceptedNameUsageID']; //final assignment
                if(!$rec['scientificName']) continue;
                $save = array();
                foreach($this->synonyms_headers as $head) $save[] = $rec[$head];
                // print_r($save); print_r($this->synonyms_headers); exit;
                fwrite($WRITE, implode("\t", $save)."\n");
            }
            //==============================================================================
            if($task == 'check_taxonID_if_unique') {
                $taxonID = $rec['taxonID'];
                if(!$taxonID && !$rec['scientificName']) continue; //seems blank row
                if(!$taxonID) { exit("\nblank taxonID\n"); print_r($rec); }
                if(!isset($unique[$taxonID])) $unique[$taxonID] = '';
                else exit("\nnon unique taxonID: [$taxonID]\n");
            }
            //==============================================================================
            if($task == 'build_SN_Accepted_prefix_info') { //print_r($rec); exit;
                /*Array(
                    [taxonID] => 
                    [source] => COL:8f40f23a98b9090e950f7218d7b1737f
                    [acceptedNameUsageID] => 3009726
                    [DH_acceptedNameUsageID] => EOL-000003051475
                    [scientificName] => Megalothorax bonetella Najt & Rapoport, 1965
                    [taxonRank] => species
                    [canonicalName] => Megalothorax bonetella
                    [taxonomicStatus] => not accepted
                    [furtherInformationURL] => http://www.catalogueoflife.org/col/details/species/id/6e503f12ba03d36fb004aef898d6ff9e/synonym/8f40f23a98b9090e950f7218d7b1737f
                    [datasetID] => COL-1130
                    [hash] => 16a33241e893a2bc352a7654c6deb3da
                )*/
                $ret = self::get_prefix_identifier_from_source($rec['source']);
                $prefix = $ret[0];
                if(in_array($prefix, array('ITIS', 'WOR'))) continue; //There were no ITIS or WOR synonyms in DH 1.1, so you don’t need to worry about finding matches for those.
                $combo = $rec['scientificName']."|".$rec['DH_acceptedNameUsageID']."|".$prefix;
                if($val = $rec['taxonID']) $value = $val;
                if($val = $rec['hash']) $value = $val;
                $this->SN_Accepted_prefix_info[$combo] = $val;
            }
            if($task == 'find_hits_DH21_DH11') { //looping DH11
                if(substr($rec['taxonID'],0,4) == "SYN-") { //print_r($rec); exit;
                    /*Array(
                        [taxonID] => SYN-000000000001
                        [source] => NCBI
                        [acceptedNameUsageID] => EOL-000000000001
                        [scientificName] => all
                        [taxonRank] => no rank
                        [taxonomicStatus] => synonym
                        [taxonRemarks] => 
                        [datasetID] => NCBI
                        [canonicalName] => 
                        ...
                    )*/
                    $combo = $rec['scientificName']."|".$rec['acceptedNameUsageID']."|".$rec['source'];
                    if(isset($this->SN_Accepted_prefix_info[$combo])) {
                        $DH21_taxonID_OR_hash = $this->SN_Accepted_prefix_info[$combo];
                        $this->new_id[$DH21_taxonID_OR_hash] = $rec['taxonID'];
                    }
                }
            }
            if($task == 'save_new_SYN_ids_to_DH21_synonyms') { //looping DH21 synonyms
                //     [8fa925c03c1307a67dffc39a20520332] => SYN-000001673778
                //     [f7955cef3ce90a6390ae9ad3169327cd] => SYN-000001673779
                //     [26980b1945e1c76bcce741995f2bd170] => SYN-000001673780
                //     [23767120207cfcc7c1239585d31f217e] => SYN-000001673781
                //     [bda4241ca3611e3c267f8e13ab8820d2] => SYN-000001673782
                
                if($val = $rec['taxonID']) $DH21_taxonID_OR_hash = $val;
                if($val = $rec['hash']) $DH21_taxonID_OR_hash = $val;
                if(isset($this->new_id[$DH21_taxonID_OR_hash])) {
                    if($new_taxonID = $this->new_id[$DH21_taxonID_OR_hash]) {
                        $rec['taxonID'] = $new_taxonID;
                        @$this->debug['connections saved']++;
                    }
                    else exit("\nshould not go here...\n");
                }
                $save = array();
                foreach($this->synonyms_headers as $head) $save[] = $rec[$head];
                // print_r($save); print_r($this->synonyms_headers); exit;
                fwrite($WRITE, implode("\t", $save)."\n");
            }
            //==============================================================================
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
            if($task == 'build_accepted_identifier_info') { // print_r($rec); exit;
                /*Array(
                    [partner] => Collembola
                    [identifier] => d3fe342a0f6ed9a8d6e8dd0fce2aad88
                    [taxonID] => 54706559
                )*/
                if($rec['partner'] == $partner) $this->accepted_identifier_info[$rec['taxonID']] = $rec['identifier'];
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
                    /*
                    $ret['z_partner'] = 'COL';
                    $ret['z_identifier'] = self::format_z_identifier('COL', $rec);
                    */
                    $ret['taxonID'] = self::format_taxonID('COL', $rec);
                    $ret['source'] = self::format_source('COL', $rec);
                    $ret['furtherInformationURL'] = self::format_furtherInformationURL('COL', $rec);
                    $ret['acceptedNameUsageID'] = $rec['acceptedNameUsageID'];
                    $ret['scientificName'] = $rec['scientificName'];
                    $ret['taxonRank'] = $rec['taxonRank'];
                    $ret['taxonomicStatus'] = 'not accepted';
                    $ret['datasetID'] = self::format_datasetID('COL', $rec);
                    $ret['canonicalName'] = self::format_canonicalName('COL', $rec, $ret['taxonRank']);
                    $ret['hash'] = self::format_hash($partner, $ret, $rec);
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
                if($condition) { //print_r($rec); exit("\nhuli ka...\n");
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
                    /*
                    $ret['z_partner'] = 'COL';
                    $ret['z_identifier'] = self::format_z_identifier('COL', $rec);
                    */
                    $ret['taxonID'] = self::format_taxonID('COL', $rec);
                    $ret['source'] = self::format_source('COL', $rec);
                    $ret['furtherInformationURL'] = self::format_furtherInformationURL('COL', $rec);
                    $ret['acceptedNameUsageID'] = $rec['acceptedNameUsageID'];
                    $ret['scientificName'] = $rec['scientificName'];
                    $ret['taxonRank'] = $rec['taxonRank'];
                    $ret['taxonomicStatus'] = 'not accepted';
                    $ret['datasetID'] = self::format_datasetID('COL', $rec);
                    $ret['canonicalName'] = self::format_canonicalName('COL', $rec, $ret['taxonRank']);
                    $ret['hash'] = self::format_hash($partner, $ret, $rec);
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
                if($partner == 'WOR') $rec['taxonID'] = "urn:lsid:marinespecies.org:taxname:".$rec['taxonID'];
                if($partner == $rec['partner']) $this->Partner_taxonIDs[$rec['taxonID']] = '';
            }
            //==============================================================================
            if($task == 'get_Partner_synonyms') { //print_r($rec); exit;
                /**/
                if($partner == 'COL2') $rec = self::rename_field_indexes($rec, $partner); //"dwc:taxonomicStatus" -> "taxonomicStatus"
                elseif(in_array($partner, array('ODO', 'NCBI', 'WOR', 'ITIS'))) {} //no need to adjust field indexes
                else exit("\n[$partner] not yet initialized 02.\n");
                
                $taxonomicStatus        = $rec['taxonomicStatus'];
                $acceptedNameUsageID    = $rec['acceptedNameUsageID'];
                
                if(in_array($partner, array('COL2', 'ODO', 'NCBI'))) {
                    $condition = $taxonomicStatus == 'synonym' && isset($this->Partner_taxonIDs[$acceptedNameUsageID]);
                }
                elseif(in_array($partner, array('WOR'))) {
                    $condition = $taxonomicStatus == 'unaccepted' && isset($this->Partner_taxonIDs[$acceptedNameUsageID]);
                }
                elseif(in_array($partner, array('ITIS'))) {
                    $condition = in_array($taxonomicStatus, array('invalid', 'not accepted')) && isset($this->Partner_taxonIDs[$acceptedNameUsageID]);
                }
                else exit("\n[$partner] not yet initialized 03.\n");
                
                /*
                ITIS: Fetch taxa where taxonomicStatus is “invalid” OR “not accepted” and the acceptedNameUsageID points to an ITIS DH 2.1 taxon.
                Please run the ITIS hierarchy connector (TRAM-806) to get the latest version of the resource and then get the synonyms from that.
                */
                
                if($condition) { //print_r($rec); //exit("\nfound...\n");
                    if($partner == 'WOR') {
                        $json = json_encode($rec);
                        $json = str_replace("urn:lsid:marinespecies.org:taxname:", "", $json);
                        $rec = json_decode($json, true);
                        // print_r($rec); exit("\ntest...\n");
                    }
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
                    Array(    --- NCBI
                        [taxonID] => 11_3
                        [furtherInformationURL] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=11
                        [acceptedNameUsageID] => 11
                        [parentNameUsageID] => 
                        [scientificName] => Cellvibrio gilvus
                        [taxonRank] => species
                        [taxonomicStatus] => synonym
                        [referenceID] => 6512; 31631; 31632
                    )
                    Array(   --- WOR
                        [taxonID] => urn:lsid:marinespecies.org:taxname:63
                        [scientificName] => Ischnochitonina Bergenhayn, 1930
                        [parentNameUsageID] => urn:lsid:marinespecies.org:taxname:382003
                        [kingdom] => Animalia
                        [phylum] => Mollusca
                        [class] => Polyplacophora
                        [order] => Chitonida
                        [family] => 
                        [genus] => 
                        [taxonRank] => suborder
                        [furtherInformationURL] => https://www.molluscabase.org/aphia.php?p=taxdetails&id=63
                        [taxonomicStatus] => unaccepted
                        [taxonRemarks] => 
                        [namePublishedIn] => 
                        [referenceID] => WoRMS:citation:63
                        [acceptedNameUsageID] => urn:lsid:marinespecies.org:taxname:382004
                        [rights] => 
                        [rightsHolder] => 
                        [datasetName] => 
                    )
                    Array(  --- ITIS
                        [taxonID] => 14195
                        [furtherInformationURL] => https://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=14195#null
                        [acceptedNameUsageID] => 846123
                        [parentNameUsageID] => 
                        [scientificName] => Hepaticopsida
                        [taxonRank] => class
                        [scientificNameAuthorship] => 
                        [taxonomicStatus] => not accepted
                        [taxonRemarks] => other, see comments
                        [canonicalName] => Hepaticopsida
                    )*/
                    $ret = array();
                    /*
                    $ret['z_partner'] = $partner;
                    $ret['z_identifier'] = self::format_z_identifier($partner, $rec); //none for COL2
                    */
                    $ret['taxonID'] = self::format_taxonID($partner, $rec);
                    $ret['source'] = self::format_source($partner, $rec);
                    $ret['furtherInformationURL'] = self::format_furtherInformationURL($partner, $rec);
                    $ret['acceptedNameUsageID'] = $rec['acceptedNameUsageID'];
                    $ret['scientificName'] = $rec['scientificName'];
                    $ret['taxonRank'] = $rec['taxonRank'];
                    $ret['taxonomicStatus'] = 'not accepted';
                    $ret['datasetID'] = self::format_datasetID($partner, $rec);
                    $ret['canonicalName'] = self::format_canonicalName($partner, $rec, $ret['taxonRank']);
                    $ret['hash'] = self::format_hash($partner, $ret, $rec);
                    $save = array();
                    foreach($this->synonyms_headers as $head) $save[] = $ret[$head];
                    // print_r($save); //print_r($this->synonyms_headers); print_r($rec); exit;
                    fwrite($WRITE, implode("\t", $save)."\n");
                }
                // if($i >= 10) break;
            }
            //==============================================================================
            if($task == 'util_1') { //print_r($rec); exit("\nelix1\n");
                /*Array(
                    [z_partner] => COL
                    [z_identifier] => 8f40f23a98b9090e950f7218d7b1737f
                    [taxonID] => 
                    [source] => COL:8f40f23a98b9090e950f7218d7b1737f
                    [acceptedNameUsageID] => 3009726
                    [scientificName] => Megalothorax bonetella Najt & Rapoport, 1965
                    [taxonRank] => species
                    [canonicalName] => Megalothorax bonetella
                    [taxonomicStatus] => not accepted
                    [furtherInformationURL] => http://www.catalogueoflife.org/col/details/species/id/6e503f12ba03d36fb004aef898d6ff9e/synonym/8f40f23a98b9090e950f7218d7b1737f
                    [datasetID] => COL-1130
                    [hash] => 49a7e001c992b7e1a57f13cb4c4f1588
                )*/
                $hash = $rec['hash'];
                if(isset($this->hash_IDs2[$hash])) { print_r($rec); exit("\nnon-unique hash [$partner]\n"); }
                else {
                    $this->hash_IDs2[$hash] = '';
                    @$this->hash_IDs2_count++;
                }
            }
            //==============================================================================
            if($task == 'open_Partner_synonyms') { // print_r($rec); exit("\nelix1\n");
                /*Array(
                    [taxonID] => 
                    [source] => ODO:Heliocharitidae
                    [acceptedNameUsageID] => Dicteriadidae
                    [scientificName] => Heliocharitidae
                    [taxonRank] => family
                    [canonicalName] => Heliocharitidae
                    [taxonomicStatus] => not accepted
                    [furtherInformationURL] => https://www.pugetsound.edu/academics/academic-resources/slater-museum/biodiversity-resources/dragonflies/world-odonata-list2/
                    [datasetID] => ODO
                    [hash] => 634d60f4a35728bde0ec5a6a3e48a79e
                )
                Please report all the synonyms that were removed during this step 
                (scientificName, source, acceptedNameUsageID, taxonID of other DH taxon for which there is a canonical match). */
                if($canonical = $rec['canonicalName']) $this->syn_canonical[$canonical] = array('sn' => $rec['scientificName'], 's' => $rec['source'], 'r' => $rec['taxonRank'], 'a' => $rec['acceptedNameUsageID'], 'h' => $rec['hash']);
                else { print_r($rec); exit("\nNo canonicalName\n"); }
            }
            //==============================================================================
            if($task == 'check_syn_with_DH21_canonical') { // print_r($rec); exit("\nelix1\n");
                /*Array(
                    [taxonID] => EOL-000000000001
                    [source] => trunk:4038af35-41da-469e-8806-40e60241bb58
                    [furtherInformationURL] => 
                    [acceptedNameUsageID] => 
                    [parentNameUsageID] => 
                    [scientificName] => Life
                    [taxonRank] => 
                    [taxonomicStatus] => accepted
                    [datasetID] => trunk
                    [canonicalName] => Life
                    [eolID] => 2913056
                    [Landmark] => 
                    [higherClassification] => 
                )*/
                if($rec['taxonomicStatus'] == 'accepted') {
                    if($canonical = $rec['canonicalName']) {
                        if(isset($this->syn_canonical[$canonical])) {
                            $DH_rank = $rec['taxonRank'];
                            $syn_rank = $this->syn_canonical[$canonical]['r'];
                            // B. The rank of the synonym is genus and the rank of the DH 2.1 canonical match is not genus.
                            // C. The rank of the DH 2.1 canonical match is genus and the rank of the synonym is not genus.
                            if($syn_rank == 'genus' && $DH_rank != 'genus') continue; //don't discard
                            if($DH_rank == 'genus' && $syn_rank != 'genus') continue; //don't discard

                            //save first:
                            $ret = self::get_prefix_identifier_from_source($rec['source']);
                            $prefix = $ret[0];
                            $identifier = $ret[1];
                            $syn_rec = $this->syn_canonical[$canonical];
                            $DH_rec = array('p' => $prefix, 'i' => $identifier, 't' => $rec['taxonID']);
                            $this->syn_canonical_matched_DH21[$canonical][] = array("s" => $syn_rec, 'H' => $DH_rec);
                        }
                    }
                }
                
            }
            //==============================================================================
            if($task == 'update_1') { //print_r($rec); exit("\nelix 2\n");
                /*Array(
                    [taxonID] => 
                    [source] => ODO:Heliocharitidae
                    [acceptedNameUsageID] => Dicteriadidae
                    [scientificName] => Heliocharitidae
                    [taxonRank] => family
                    [canonicalName] => Heliocharitidae
                    [taxonomicStatus] => not accepted
                    [furtherInformationURL] => https://www.pugetsound.edu/academics/academic-resources/slater-museum/biodiversity-resources/dragonflies/world-odonata-list2/
                    [datasetID] => ODO
                    [hash] => 634d60f4a35728bde0ec5a6a3e48a79e
                )    
                Array(
                    [z_partner] => COL
                    [z_identifier] => 8f40f23a98b9090e950f7218d7b1737f
                    [taxonID] => 
                    [source] => COL:8f40f23a98b9090e950f7218d7b1737f
                    [acceptedNameUsageID] => 3009726
                    [scientificName] => Megalothorax bonetella Najt & Rapoport, 1965
                    [taxonRank] => species
                    [canonicalName] => Megalothorax bonetella
                    [taxonomicStatus] => not accepted
                    [furtherInformationURL] => http://www.catalogueoflife.org/col/details/species/id/6e503f12ba03d36fb004aef898d6ff9e/synonym/8f40f23a98b9090e950f7218d7b1737f
                    [datasetID] => COL-1130
                    [hash] => 49a7e001c992b7e1a57f13cb4c4f1588
                )*/
                $hash = $rec['hash'];
                if(!isset($this->to_be_removed[$hash])) {
                    $save = array();
                    foreach($this->synonyms_headers as $head) $save[] = $rec[$head];
                    // print_r($save); print_r($this->synonyms_headers); exit;
                    fwrite($WRITE, implode("\t", $save)."\n");
                }
            }
            //==============================================================================
            if($task == 'build_identifier_taxonID_info') { //print_r($rec); exit;
                /*Array(
                    [taxonID] => EOL-000000000001
                    [source] => trunk:4038af35-41da-469e-8806-40e60241bb58
                    [furtherInformationURL] => 
                    [acceptedNameUsageID] => 
                    [parentNameUsageID] => 
                    [scientificName] => Life
                    [taxonRank] => 
                    [taxonomicStatus] => accepted
                    [datasetID] => trunk
                    [canonicalName] => Life
                    [eolID] => 2913056
                    [Landmark] => 
                    [higherClassification] => 
                )*/
                $ret = self::get_prefix_identifier_from_source($rec['source']); $prefix = $ret[0]; $identifier = $ret[1];
                if($partner == 'Collembola') {
                    if('COL' == $prefix) $this->identifier_taxonID_info[$identifier] = $rec['taxonID'];
                }
                else { //the rest
                    if($partner == $prefix) $this->identifier_taxonID_info[$identifier] = $rec['taxonID'];
                }
            }
            //==============================================================================
            if($task == 'update_2') { //print_r($rec); exit;
                /*Array(
                    [taxonID] => 
                    [source] => ODO:Heliocharitidae
                    [acceptedNameUsageID] => Dicteriadidae
                    [scientificName] => Heliocharitidae
                    [taxonRank] => family
                    [canonicalName] => Heliocharitidae
                    [taxonomicStatus] => not accepted
                    [furtherInformationURL] => https://www.pugetsound.edu/academics/academic-resources/slater-museum/biodiversity-resources/dragonflies/world-odonata-list2/
                    [datasetID] => ODO
                    [hash] => 634d60f4a35728bde0ec5a6a3e48a79e
                )*/
                $acceptedNameUsageID = $rec['acceptedNameUsageID'];
                
                // /* for COL and Collembola
                if(in_array($partner, array('COL', 'Collembola'))) {
                    if($val = $this->accepted_identifier_info[$acceptedNameUsageID]) $acceptedNameUsageID = $val;
                    else { print_r($rec); exit("\nNo lookup 2...\n"); }
                }
                // */
                
                if($val = @$this->identifier_taxonID_info[$acceptedNameUsageID]) $rec['DH_acceptedNameUsageID'] = $val;
                else {
                    $this->debug['no lookup'][$partner.":".$acceptedNameUsageID] = '';
                    print_r($rec); exit("\nNo lookup...\n");
                }
                $save = array();
                foreach($this->synonyms_headers as $head) $save[] = $rec[$head];
                // print_r($save); print_r($this->synonyms_headers); exit;
                fwrite($WRITE, implode("\t", $save)."\n");
            }
            //==============================================================================
            if($task == 'consolidate_synonyms') {
                /*Array( --- trunk
                    [taxonID] => SYN-000001683069
                    [source] => 
                    [furtherInformationURL] => 
                    [acceptedNameUsageID] => EOL-000003165652
                    [parentNameUsageID] => 
                    [scientificName] => 2019-nCoV
                    [taxonRank] => 
                    [taxonomicStatus] => not accepted
                    [datasetID] => trunk
                    [canonicalName] => 2019-nCoV
                    [eolID] => 
                    [Landmark] => 
                    [higherClassification] => 
                )*/
                // taxonID  source  acceptedNameUsageID DH_acceptedNameUsageID  scientificName  taxonRank   canonicalName   taxonomicStatus furtherInformationURL   datasetID   hash
                // taxonID  source  acceptedNameUsageID                         scientificName  taxonRank   canonicalName   taxonomicStatus furtherInformationURL   datasetID   hash
                if($partner == 'trunk') { //from DH21
                    $rec['DH_acceptedNameUsageID'] = $rec['acceptedNameUsageID'];
                    $rec['hash'] = ''; //we don't need a value atm or ever...
                    $condition = substr($rec['taxonID'],0,4) == "SYN-";
                }
                else $condition = true; //from all 7 prefixes
                if($condition) {
                    $save = array();
                    foreach($this->synonyms_headers as $head) $save[] = $rec[$head];
                    // print_r($save); print_r($this->synonyms_headers); exit;
                    fwrite($WRITE, implode("\t", $save)."\n");
                    @$this->debug[$partner.' syn']++;
                    @$this->debug['all syn']++;
                }
            }
            //==============================================================================
            if($task == 'consolidate_synonyms_2') {
                if($taxonID = $rec['taxonID']) {
                    if(isset($this->taxonIDs_2remove[$taxonID])) continue;
                }
                if($hash = $rec['hash']) {
                    if(isset($this->hashes_2remove[$hash])) continue;
                }
                $save = array();
                foreach($this->synonyms_headers as $head) $save[] = $rec[$head];
                // print_r($save); print_r($this->synonyms_headers); exit;
                fwrite($WRITE, implode("\t", $save)."\n");
                @$this->debug['saved synonyms']++;
            }
            //==============================================================================
            
            if($task == 'find_combo_hits') { //print_r($rec); exit;
                /*Array(
                    [taxonID] => SYN-000001683069
                    [source] => 
                    [acceptedNameUsageID] => EOL-000003165652
                    [DH_acceptedNameUsageID] => EOL-000003165652
                    [scientificName] => 2019-nCoV
                    [taxonRank] => 
                    [canonicalName] => 2019-nCoV
                    [taxonomicStatus] => not accepted
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [hash] => 
                )*/
                $acceptedNameUsageID = $rec['DH_acceptedNameUsageID'];
                $scientificName = $rec['scientificName'];
                if($acceptedNameUsageID && $scientificName) {
                    $combo = "$acceptedNameUsageID|$scientificName";
                    if(isset($this->combo_hits[$combo])){
                        if(!in_array($rec, $this->combo_hits[$combo])) $this->combo_hits[$combo][] = $rec;
                    }
                    else {
                        $this->combo_hits[$combo][] = $rec;
                    }
                }
            }
            //==============================================================================

        } //end foreach()
        // if(in_array($task, array('assemble_taxonIDs_from_source_col', 'assemble_COL_identifiers'))) fclose($WRITE);
        if(isset($WRITE)) {
            if($WRITE) fclose($WRITE);
        }
        if(isset($this->hash_IDs)) echo "\n----------------------\nhash_IDs count for [$partner]: ".count($this->hash_IDs)."\n----------------------\n";
        if($task == 'util_1') echo "\n-----\n[$partner ".count($this->hash_IDs2)."] $this->hash_IDs2_count\n-----\n";
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
            // /* manual adjustments
            $sciname = str_replace('"', "", $rec['scientificName']);
            if($partner == 'COL') {
                $sciname = str_replace("lii`fordi", "lilfordi", $sciname);
                $sciname = str_replace("L`Hardy", "L'Hardy", $sciname);
            }
            // */
            $canonical = $this->func->add_cannocial_using_gnparser($sciname, $taxonRank); // exit("\n[$canonical]\n");
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
    private function format_hash($partner, $ret, $rec)
    {
        $ret['hash'] = md5(json_encode($ret));
        if(!isset($this->hash_IDs[$ret['hash']])) $this->hash_IDs[$ret['hash']] = '';
        else {
            print_r($rec); print_r($ret);
            exit("\n[$partner] -> Investigate identical synonym row.\n");
        }
        return $ret['hash'];
    }
    private function get_prefix_identifier_from_source($source)
    {   //e.g. trunk:4038af35-41da-469e-8806-40e60241bb58
        $arr = explode(":", $source);
        $arr = array_map('trim', $arr);
        return array($arr[0], @$arr[1]);
    }
}