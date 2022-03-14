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
        /* copied template
        $this->tsv['DH11_Jen'] = $this->main_path."/dh1_1/DH1_1working.txt";
        $this->tsv['DH21_Jen'] = $this->main_path."/dh2_1/DH2_1working.txt";
        $this->tsv['DH11'] = $this->main_path."/DH11_working_new.txt";
        $this->tsv['DH21'] = $this->main_path."/DH21_working_new.txt";
        $this->tsv['remappings_Katja'] = $this->main_path."/Katja/remappings.txt";
        */
        $this->tsv['DH21_current'] = $this->main_path."/data/dh2.1mar2022/taxon.tab";
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
        
        
        // if(file_exists($this->tsv['Collembola'])) exit("\nfile exists ok\n");
        // else exit("\nfile does not exist...\n");
        
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

        /* step 1: run once only - DONE
        $head = array('partner', 'taxonID');
        $WRITE = fopen($this->tsv['taxonIDs_from_source_col'], "w"); fwrite($WRITE, implode("\t", $head)."\n");
        self::parse_tsv($this->tsv['DH21_current'], 'assemble_taxonIDs_from_source_col', $WRITE);

        $head = array('partner', 'identifier');
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
        self::parse_tsv($this->tsv['COL_identifiers'], 'get_COL_identifiers', $WRITE);
        print_r($this->debug);
        */
        
        // /* step 3: assemble synonyms --- COL
        self::parse_tsv($this->tsv['COL_taxonIDs'], 'get_COL_taxonIDs COL', false); //creates $this->COL_taxonIDs
        // self::parse_tsv($this->tsv['COL_taxonIDs'], 'get_COL_taxonIDs Collembola', false); //creates $this->Collembola_taxonIDs

        require_library('connectors/FillUpMissingParentsAPI');
        $this->func = new FillUpMissingParentsAPI(false, false, false);

        $head = array('z_partner', 'z_identifier');
        $head = array_merge($head, array('taxonID', 'source', 'acceptedNameUsageID', 'scientificName', 'taxonRank', 'canonicalName', 'taxonomicStatus', 'furtherInformationURL', 'datasetID'));
        $this->synonyms_headers = $head; // print_r($head); exit;
        
        $WRITE = fopen($this->tsv['synonyms_COL'], "w"); fwrite($WRITE, implode("\t", $head)."\n");
        self::parse_tsv($this->tsv['COL_2019_new'], 'get_COL_synonyms', $WRITE, 'COL');
        // */
        
        /* step 4: assemble synonyms --- COL Collembola
        $WRITE = fopen($this->tsv['synonyms_Collembola'], "w"); fwrite($WRITE, implode("\t", $head)."\n");
        self::parse_tsv($this->tsv['Collembola_new'], 'get_Collembola_synonyms', $WRITE, 'COL');
        */
        
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
            if($task == 'get_COL_identifiers') {
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
                    )*/
                    $ret = array();
                    $ret['taxonID'] = self::format_taxonID('COL', $rec);
                    $ret['source'] = self::format_source('COL', $rec);
                    $ret['furtherInformationURL'] = self::format_furtherInformationURL('COL', $rec);
                    $ret['acceptedNameUsageID'] = $rec['acceptedNameUsageID'];
                    $ret['scientificName'] = $rec['scientificName'];
                    $ret['taxonRank'] = $rec['taxonRank'];
                    $ret['taxonomicStatus'] = 'not accepted';
                    $ret['datasetID'] = self::format_datasetID('COL', $rec);
                    $ret['canonicalName'] = self::format_canonicalName('COL', $rec, $ret['taxonRank']);
                }
            }
            //==============================================================================
            
        } //end foreach()
        if(in_array($task, array('assemble_taxonIDs_from_source_col', 'assemble_COL_identifiers'))) fclose($WRITE);
    } // end parse_tsv()
    private function format_taxonID($partner, $rec)
    {
        
    }
    private function format_source($partner, $rec)
    {
        if(in_array($partner, array('COL2', 'ITIS', 'NCBI', 'ODO', 'WOR'))) {
            
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
            $canonical = $this->func->add_cannocial_using_gnparser($rec['scientificName'], $taxonRank);
            // exit("\n[$canonical]\n");
            return $canonical;
        }
    }
    private function format_datasetID($partner, $rec)
    {
        if(in_array($partner, array('ITIS', 'NCBI', 'ODO', 'WOR'))) return $partner;
        elseif(in_array($partner, array('COL', 'COL2'))) {
            if(is_numeric($rec['datasetID'])) return "$partner-".$rec['datasetID'];
        }
    }
    private function format_furtherInformationURL($partner, $rec)
    {
        if($partner == 'COL') return $rec['references'];
        elseif($partner == 'COL2') return "http://www.catalogueoflife.org/data/taxon/".$rec['taxonID'];
        elseif(in_array($partner, array('ITIS', 'NCBI', 'ODO', 'WOR'))) return $rec['furtherInformationURL'];
    }
}