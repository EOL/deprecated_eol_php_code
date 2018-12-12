<?php
namespace php_active_record;
/* connector: [dwh.php] */
class DHSourceHierarchiesAPI
{
    function __construct()
    {
        /*
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        if(Functions::is_production()) {}
        */
        /* not being used here
        $this->AphiaRecordByAphiaID_download_options = array('download_wait_time' => 1000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26, 'expire_seconds' => false);
        $this->webservice['AphiaRecordByAphiaID'] = "http://www.marinespecies.org/rest/AphiaRecordByAphiaID/";
        */
        $this->gnparser = "http://parser.globalnames.org/api?q=";
        if(Functions::is_production()) {
            $this->smasher_download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false); //false
            $this->main_path = "/extra/eli_dwh/"; //download_wait_time is 1/4 of a second -> 1000000/4
        }
        else {
            $this->smasher_download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/', //new, started from blank
                // 'cache_path'         => '/Volumes/Thunderbolt4/z backup of AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false); //false
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/dynamic_working_hierarchy-master/";
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/eli_dwh/"; //old - initial runs
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/eli_dwh2/"; //new - TRAM-800
        }
        /* Functions::lookup_with_cache($this->gnparser.urlencode($rec['scientificName']), $this->smasher_download_options); */
        
        $this->debug = array();
        $this->taxonomy_header = array("uid", "parent_uid", "name", "rank", "sourceinfo"); //('uid	|	parent_uid	|	name	|	rank	|	sourceinfo	|	' + '\n')
        $this->synonym_header = array("uid", "name", "type", "rank");                      //('uid	|	name	|	type	|	rank	|	' + '\n')


/*paste these in terminal
php update_resources/connectors/dwh.php _ EET
php update_resources/connectors/dwh.php _ ASW
php update_resources/connectors/dwh.php _ ictv
php update_resources/connectors/dwh.php _ CLP
php update_resources/connectors/dwh.php _ trunk
php update_resources/connectors/dwh.php _ ERE
php update_resources/connectors/dwh.php _ IOC
php update_resources/connectors/dwh.php _ BOM
php update_resources/connectors/dwh.php _ NCBI
php update_resources/connectors/dwh.php _ ONY
php update_resources/connectors/dwh.php _ ODO
php update_resources/connectors/dwh.php _ WOR
php update_resources/connectors/dwh.php _ COL
*/
        //for testing
        $this->sh['xxx']['source']          = $this->main_path."/xxx/";
        $this->sh['xxx']['has_syn']         = false;
        $this->sh['xxx']['run_gnparse']     = true;

        // /* new list ---------------------------------------------------------------------------------------------------
        $this->sh['EET']['source']          = $this->main_path."/eolearthwormpatch/";
        $this->sh['EET']['has_syn']         = false;
        $this->sh['EET']['run_gnparse']     = true;

        $this->sh['ASW']['source']          = $this->main_path."/amphibianspeciesoftheworld/";
        $this->sh['ASW']['has_syn']         = false; //has syn but we don't want them
        $this->sh['ASW']['run_gnparse']     = true;

        $this->sh['ictv']['source']         = $this->main_path."/ICTV-virus_taxonomy-with-higherClassification/";
        $this->sh['ictv']['has_syn']        = false;
        $this->sh['ictv']['run_gnparse']    = false;

        $this->sh['CLP']['source']          = $this->main_path."/Catalogue_of_Life_Protists_DH/";
        $this->sh['CLP']['has_syn']         = false;
        $this->sh['CLP']['run_gnparse']     = true;

        $this->sh['trunk']['source']        = $this->main_path."/dynamichierarchytrunk2018-11-21/";
        $this->sh['trunk']['has_syn']       = false;
        $this->sh['trunk']['run_gnparse']   = false;

        $this->sh['ERE']['source']          = $this->main_path."/eoldynamichierarchyerebidaepatch/";
        $this->sh['ERE']['has_syn']         = false;
        $this->sh['ERE']['run_gnparse']     = false;

        $this->sh['IOC']['source']          = $this->main_path."/ioc-birdlist/";
        $this->sh['IOC']['has_syn']         = false;
        $this->sh['IOC']['run_gnparse']     = true;

        $this->sh['COL']['source']          = $this->main_path."/Catalogue_of_Life_DH/";
        $this->sh['COL']['has_syn']         = true;
        $this->sh['COL']['run_gnparse']     = true;

        $this->sh['BOM']['source']          = $this->main_path."/kitchingetal2018/";
        $this->sh['BOM']['has_syn']         = true;
        $this->sh['BOM']['run_gnparse']     = true;

        $this->sh['NCBI']['source']         = $this->main_path."/NCBI_Taxonomy_Harvest_DH/";
        $this->sh['NCBI']['has_syn']        = true;
        $this->sh['NCBI']['run_gnparse']    = false; //has specific field for just canonical name

        $this->sh['ONY']['source']          = $this->main_path."/oliveira2012onychophora/";
        $this->sh['ONY']['has_syn']         = false;
        $this->sh['ONY']['run_gnparse']     = true;
        
        $this->sh['ODO']['source']          = $this->main_path."/worldodonata/";
        $this->sh['ODO']['has_syn']         = false; //has syn but we don't want them
        $this->sh['ODO']['run_gnparse']     = true;

        $this->sh['WOR']['source']          = $this->main_path."/WoRMS_DH/";
        $this->sh['WOR']['has_syn']         = true;
        $this->sh['WOR']['run_gnparse']     = true;
        // --------------------------------------------------------------------------------------------------- */
        $this->taxonomy_header_tmp = array("name", "uid", "parent_uid", "rank");
        $this->synonym_header_tmp = array("name", "uid", "accepted_x_id", "type");
        
        /* old list
        $this->sh['WOR']['source']        = $this->main_path."/worms_v5/";
        $this->sh['IOC']['source'] = $this->main_path."/ioc-birdlist_v3/";
        $this->sh['trunk']['source']        = $this->main_path."/trunk_20180521/";
        $this->sh['COL']['source']          = $this->main_path."/col_v1/";
        $this->sh['ictv']['source']         = $this->main_path."/ictv_v3/";
        $this->sh['ictv']['run_gnparse']    = false; //
        $this->sh['ODO']['source']      = $this->main_path."/odonata_v2/";
        $this->sh['ONY']['source']  = $this->main_path."/onychophora_v3/";
        $this->sh['EET']['source']   = $this->main_path."/earthworms_v3/";
        $this->sh['pbdb']['source']         = $this->main_path."/pbdb_v1/";
        $this->sh['pbdb']['run_gnparse']    = false; //has separate field for 'scientificNameAuthorship'
        */
        /* old
        //row_terminator was instroduced for ncbi
        //this was just Eli's initiative. May wait for Katja's instructions here...
        $this->sh['ncbi']['source']         = $this->main_path."/ncbi_v1/";
        $this->sh['ncbi']['run_gnparse']    = false; //has specific field for just canonical name
        $this->sh['ncbi']['iterator_options'] = array('row_terminator' => "\t|\n");
        */
    }
    
    public function test($what)
    {
        $s = self::generate_syn_for_python_file();
        print_r($s); exit("\n");
        /*
        $this->what = $what;
        $string = "Malmopsylla† karatavica Bekker-Migdisova, 1985";
        //$string = '“montereina” greeleyi (MacFarland, 1909)';
        // $string = "V latipennis Baehr, 2006";
        $string = "Curcuma vitellina Škornick. & H.Ð.Tran";

        $string = "Bolivina suЬincrassata Khalilov, 1956";
        //$string = "Bolivina suЬincrassata var. caucasica Khalilov, 1956";
        //$string = "Bolivina suЬincrassata var. costata Khalilov, 1956";
        //$string = "Bolivina dilataЬilis Khalilov, 1956";
        
        echo "\norig: $string";
        $string = str_replace("†","",$string);
        $string = Functions::conv_to_utf8($string);
        echo "\nutf8: $string";
        echo "\ngnparser canonical: ".self::gnsparse_canonical($string, 'cache');
        $c = Functions::canonical_form($string);
        exit("\ncanonical: $c\n");
        */
        /*
        $json = Functions::lookup_with_cache($this->gnparser.urlencode('Notoscolex wellingtonensis (Spencer, 1895)'), $this->smasher_download_options);
        exit("\n".$json."\n");
        */
        /*
        $sciname = "Amorimia exotropica (Griseb.) W.R.Anderson";
        // $canonical = self::gnsparse_canonical($sciname, 'api');
        // echo "\n[$canonical]\n";
        $canonical = self::gnsparse_canonical($sciname, 'cache');
        echo "\nparsing...[$sciname] ---> [$canonical]\n";

        // $options = $this->smasher_download_options; $options['expire_seconds'] = 0; //expires now
        // $canonical = self::gnsparse_canonical($sciname, 'cache', $options);
        // echo "\nparsing...[$sciname] ---> [$canonical]\n";

        exit("\nstopx\n");
        */
        /*
        $sciname = "Gadus morhua Eli 1972";
        $json = Functions::lookup_with_cache($this->gnparser.urlencode($sciname), $this->smasher_download_options);
        print_r(json_decode($json, true));
        $json = self::get_json_from_cache($sciname);
        print_r(json_decode($json, true));
        exit;
        */
        /*
        $cmd = 'gnparser name "Notoscolex imparicystis (Jamieson, 1973)"';
        $json = shell_exec($cmd);
        print_r(json_decode($json, true));
        exit;

        gnparser file --input xaa.txt --output xaa_gnparsed.txt
        gnparser file --input xab.txt --output xab_gnparsed.txt
        gnparser file --input xac.txt --output xac_gnparsed.txt
        gnparser file --input xad.txt --output xad_gnparsed.txt
        gnparser file --input xae.txt --output xae_gnparsed.txt
        gnparser file --input xaf.txt --output xaf_gnparsed.txt
        gnparser file --input xag.txt --output xag_gnparsed.txt
        gnparser file --input xah.txt --output xah_gnparsed.txt
        ftp://ftp.ncbi.nlm.nih.gov/pub/taxonomy/
        */
        /*
        gnparser file -f json-compact --input test.txt --output test_gnparsed.txt
        self::save_2local_gnparsed_file_new($what, "test_gnparsed.txt"); exit("\n-end test-\n");

        gnparser file -f simple --input test.txt --output test_gnparsed.txt


        gnparser file -f json-compact --input xaa.txt --output xaa_gnparsed.txt
        gnparser file -f json-compact --input xab.txt --output xab_gnparsed.txt
        gnparser file -f json-compact --input xac.txt --output xac_gnparsed.txt
        gnparser file -f json-compact --input xad.txt --output xad_gnparsed.txt
        gnparser file -f json-compact --input xae.txt --output xae_gnparsed.txt
        gnparser file -f json-compact --input xaf.txt --output xaf_gnparsed.txt
        gnparser file -f json-compact --input xag.txt --output xag_gnparsed.txt
        gnparser file -f json-compact --input xah.txt --output xah_gnparsed.txt

        self::save_2local_gnparsed_file_new($what, "xaa_gnparsed.txt"); exit("\n-end xaa_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xab_gnparsed.txt"); exit("\n-end xab_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xac_gnparsed.txt"); exit("\n-end xac_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xad_gnparsed.txt"); exit("\n-end xad_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xae_gnparsed.txt"); exit("\n-end xae_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xaf_gnparsed.txt"); exit("\n-end xaf_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xag_gnparsed.txt"); exit("\n-end xag_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xah_gnparsed.txt"); exit("\n-end xah_gnparsed-\n");
        */

        /* CoL divided into smaller chunks
        self::save_2local_gnparsed_file($what, "xaa_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xab_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xac_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xad_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xae_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xaf_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xag_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xah_gnparsed.txt");
        exit;
        */
        // self::parent_id_check($what); exit;
        /*
        $str = "Aloencyrtus angustifrons. (Annecke, 1964)";     echo "\n[$str]";
        $str = self::fix_with_period($str);                     exit("\n[$str]\n");
        */
        
        
        /*
        Penicillium	Penicillium cvjetkovicii S.W. Peterson, Jurjevi? & Frisvad 2015
        Zodarion	Zodarion van Bosmans, 2009
        Selenops	Selenops ab Logunov & Jäger, 2015
        Leptonetela	Leptonetela la Wang & Li, 2017
        Hyalopsora	Hyalopsora adianti-capilli-veneris (DC.) Syd. & P. Syd. 1903
        Uredo	Uredo elymi-capitis-medusae Gonz. Frag. 1913
        Melanopsis	Melanopsis cvijici Brusina, 1902
        Bythinella	Bythinella cvijici Pavlovi?, 1933
        Viviparus	Viviparus cvijici Pavlovi?, 1932
        Ruteloryctes	Ruteloryctes bis Dechambre, 2006
        Catoptes	Catoptes interruptusfabricius,1781 (Fabricius, 1781)
        
        */
        
        // /* get uuid from COL
        $str = "Hyalopsora adianti-capilli-veneris (DC.) Syd. & P. Syd. 1903
        Selenops ab Logunov & Jäger, 2015
        Leptonetela la Wang & Li, 2017
        Melanopsis cvijici Brusina, 1902
        Bythinella cvijici Pavlovi?, 1933
        Ruteloryctes bis Dechambre, 2006
        Uredo elymi-capitis-medusae Gonz. Frag. 1913
        Phyllosticta chenopodii-boni-henrici S?vul. & Sandu 1933
        Dactylaria cvetkovicii Munt.-Cvetk. 1957
        Cercospora ipomoeae-pedis-caprae J.M. Yen & Lim 1970
        Meliola strychni-nux-vomicae Gawande, D.K. Agarwal & Hosag. 2004
        Phoma gentianae-sino-ornatae Punith. & R. Harling 1993
        Aspergillus cvjetkovicii Jurjevi?, S.W. Peterson & B.W. Horn 2012
        Agaricus iesu-et-marthae L.A. Parra 2013
        Penicillium cvjetkovicii S.W. Peterson, Jurjevi? & Frisvad 2015
        Zodarion van Bosmans, 2009
        Melanopsis cvijici Brusina, 1902
        Bythinella cvijici Pavlovi?, 1933
        Viviparus cvijici Pavlovi?, 1932
        Meroptera cviatella Dyar, 1905
        Eilema cvirgineola Hampson, 1900
        Drepana x-z-nigrum Bryk, 1942
        Semiothisa da Dyar, 1916
        Mimosa coelho-de-moraesii Pickel & Handro
        Brownea rosa-de-monte Bergius
        Lohmannella cvetkovi (Petrova 1965)
        Plebejus lilacina-rufolunalata-casaicus (Tutt)
        Catoptes interruptusfabricius,1781 (Fabricius, 1781)";
        $str = "Coscinospira hemprichii var. β bacillaris Ehrenberg, 1840
        Coscinospira hemprichii var. γ compressa Ehrenberg, 1840
        Coscinospira hemprichii var. α lenticularis Ehrenberg, 1840"; //WOR
        $str = "Bolivina suЬincrassata Khalilov, 1956
        Bolivina suЬincrassata var. caucasica Khalilov, 1956
        Bolivina suЬincrassata var. costata Khalilov, 1956
        Bolivina dilataЬilis Khalilov, 1956"; //WOR
        $str = "Anolis bimaculatus ?lividus Underwood In Williams Et Al., 1959"; //COL
        $str = "Vorticella d'udekemi Kahl, 1933
        Zoothamnium d'udekemi Kahl, 1935"; //CLP
        
        $str = "Gamasiphis pulchellus (Berlese, 1887):
        Gnathopleustes den (J. L. Barnard, 1969)
        Sympycnus du Curran, 1929
        Chrysosoma du Curran, 1929
        Tanypus cvaneomaculatus (Doleschall, 1856)
        Cnemaspis rammalensis Vidanapathirana, Gehan-rajeev, Wickramasinghe,fernando & Mendis-wickramasinghe, 2014
        Cyrtodactylus ranongensis Sumontha, Pauwels,panitvong, Kunya & Grismer, 2015
        Trimeresurus phuketensis Sumontha, Kunya, Pauwels,nitikul & Punnadee, 2011
        Archarias cvlindrirostris Chevrolat, L.A.A., 1881
        Cholus cvlindrirostris Klima, A., 1936
        Spenophorus de haani Gyllenhal, L. in Schönherr, C.J., 1838
        Prodioctes de haani Pascoe, F.P., 1873
        Anapygus de haani Faust, J., 1894
        Phaeophanus o´connori Broun, T., 1921
        Phaedropholus o´connori Broun, T., 1910
        Listroderes v.caudiculatus Fairmaire, L., 1890
        Macromitrium st.-johnii E. B. Bartram, 1940
        Athyrium boreo-occidentali-indobharaticola-birianum Fraser-Jenk.
        Dryopteris papuae-novae-guineae Li Bing Zhang
        Grammitis friderici-et-pauli (Christ) Copel.
        Archigrammitis friderici-et-pauli (Christ) Parris
        Crocus cvijicii Kosanin
        Tillandsia van-den-bergii Ehlers & Hase
        Omophron suturale2 Gistel, 1848
        Chlaenius micans2 W.S.MacLeay, 1825
        Papaver corona-sancti-stephani Zapal.
        Astragalus kurnet-es-saudae Eig
        Hoya mata-ole-afiensis Kloppenb.
        Blepharis noli-me-tangere S. Moore
        Artemisia duthreuil-de-rhinsi Krasch.
        Trachymene ceratocarpa (W. Fitzg.) G,. Keighery &amp; B.L. Rye
        Microsiphum nudum
        Megakhosara sp Sharov, 1961
        Stegopterum sp Sharov, 1961
        Nocaracris van Ünal, 2016
        Agnetina den Cao, T.K.T. & Bae, 2006"; //COL

        $arr = explode("\n", $str); $arr = array_map('trim', $arr);
        $arr = array_unique($arr);  foreach($arr as $a) $final[$a] = '';
        print_r($final); self::scan_resource_file($meta, $final); exit("\n");
        // */
    }
    public function start($what)
    {
        /*===================================starts here=====================================================================*/
        $this->what = $what;
        
        // /* get problematic names from Google sheet
        $this->problematic_names = self::get_problematic_names();   //UN-COMMENT IN REAL OPERATION
        // print_r($this->problematic_names); exit;
        // */
        
        $meta_xml_path = $this->sh[$what]['source']."meta.xml";
        $meta = self::analyze_meta_xml($meta_xml_path);
        if($meta == "No core entry in meta.xml") $meta = self::analyze_eol_meta_xml($meta_xml_path);
        $meta['what'] = $what;
        print_r($meta); //exit;

        // /* utility write all names. This has now become the only sustainable approach especially for big resources like COL, since it has 3,620,095 rows
        self::utility_write_all_names($meta); exit("\n-end write all names-\n"); //works OK
        
        // Then start caching... No longer used. OBSOLETE
        // self::run_TSV_file_with_gnparser_new("COL_ALL_NAMES_2_gnparsed.txt", $what); exit("\nCaching TSV for [$what] done!\n");
        // */

        /* this is one-time run for every dataset - all 13 datasets ============================================================= OBSOLETE ever since utility_write_all_names()
        self::run_file_with_gnparser_new($meta);    exit("\nCaching for [$what] done!\n"); //is used for blank slate, meaning new cache path or new gnparser version.
        self::run_file_with_gnparser_new_v2($meta); exit("\nCaching for [$what] done!\n"); //is used to get names left behind from above. Only processes names, where cache doesn't exist yet
        ========================================================================================================================= */
        
        $with_authorship = false;
        if($this->sh[$what]['run_gnparse'] == false) {}
        else { //normal
            if(self::need_2run_gnparser_YN($meta)) {
                $with_authorship = true;
                /* wise move before. That is when using the old gnparser version. The new doesn't have a \n line separator between json records.
                self::run_file_with_gnparser($meta);
                self::save_2local_gnparsed_file($what);
                */
            }
        }
        
        /* 5. Duplicate taxa --- utility generating duplicates report for Katja ==========================================================================================
        // WOR has a bunch of species and subspecific taxa that have the same canonical form but different authors. These are mostly foraminiferans and a few diatoms. 
        // I'm not sure what to do about these. Clearly, they can't all be accepted names, but WOR still has them as such. I don't quite remember how we handled these 
        // in previous smasher runs. If smasher can't handle these apparent duplicate taxa, we could consider cleaning them up by keeping the one with the oldest date and 
        // removing the ones with the more recent data, along with their children.
        // self::check_for_duplicate_canonicals($meta, $with_authorship); exit("\n-end checking for duplicates [$what]-\n");
        self::check_for_duplicate_canonicals_new($meta, "taxonomy"); exit("\n-end checking for duplicates (new) [$what]-\n");
        ================================================================================================================================================================= */
        //initialize this report file
        $path = $this->sh[$what]['source']."../zFailures/$what".".txt"; if(file_exists($path)) unlink($path);
        
        self::process_taxon_file($meta, $with_authorship);
        self::parent_id_check($what);
        self::show_totals($what);
        if($this->sh[$what]['run_gnparse'] != $with_authorship) echo "\nInvestigate the need to run gnparser [$what]\n";
        else                                                    echo "\n-OK-\n";
    }
    private function get_problematic_names() //sheet found here: https://eol-jira.bibalex.org/browse/TRAM-800
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1A08xM14uDjsrs-R5BXqZZrbI_LiDNKeO6IfmpHHc6wg';
        $params['range']         = 'gnparser failures!B2:D1000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]] = $item[2];
        return $final;
    }
    private function show_totals($what)
    {
        $filenames = array('taxonomy.tsv', 'synonym.tsv', 'taxon.tab', 'taxa.txt');
        foreach($filenames as $filename) {
            $file = $this->sh[$what]['source'].$filename;
            if(file_exists($file)) {
                $total = shell_exec("wc -l < ".escapeshellarg($file));
                $total = trim($total);  echo "\n$filename: [$total]\n";
            }
        }
    }
    private function get_ctr_value($what)
    {
        $directory = $this->sh[$what]['source'];
        $filecount = 0;
        $files = glob($directory . "taxonomy_*_gnparsed.txt"); //taxonomy_1_gnparsed.txt
        if($files) $filecount = count($files);
        return $filecount;
    }
    private function check_for_duplicate_canonicals_new($meta, $pre, $test = array())
    {
        $what = $meta['what'];
        $ctr = self::get_ctr_value($what);
        echo "\nctr = $ctr \n";
        for ($c = 1; $c <= $ctr; $c++) {
            $txtfile = $this->sh[$what]['source'].$pre."_part_".$c."_gnparsed.txt"; echo "\nprocessing [$txtfile]\n";
            //just for progress indicator
            $total_rows = self::get_total_rows($txtfile); echo "\nTotal rows: [".number_format($total_rows)."]\n"; $modulo = self::get_modulo($total_rows);
            $i = 0;
            foreach(new FileIterator($txtfile) as $line_number => $line) {
                $i++; if(($i % $modulo) == 0) echo "\n $pre $c of $ctr - ".number_format($i)." ";
                if($i == 1) $line = strtolower($line);
                $row = explode("\t", $line);
                // print_r($row);
                if($i == 1) {
                    $fields = $row;
                    //fix $fields: important
                    $count = 1; //since new gnparsed file only has 1 column
                    $fields[$count+1] = 'canonicalName';
                    $fields[$count+2] = 'valueRanked';
                    $fields[$count+3] = 'other1';
                    $fields[$count+4] = 'other2';
                    $fields[$count+5] = 'other3';
                    // print_r($fields);
                    continue;
                }
                else {
                    if(!@$row[0]) continue;
                    $k = 0; $rec = array();
                    foreach($fields as $fld) {
                        $rec[$fld] = @$row[$k];
                        $k++;
                    }
                }
                // print_r($rec); exit("\nstopx\n");
                /*
                Array
                (
                    [c48620d4-1a15-537f-aff7-80c2fd19d607] => 24696776-f258-5c5e-a9f6-6da439513efd
                    [name] => Bacteria
                    [canonicalName] => Bacteria
                    [valueRanked] => Bacteria
                    [other1] => 
                    [other2] => 
                    [other3] => 1
                )
                */
                if($val = $rec['canonicalName']) @$test[$val][] = $rec['name'];
            }
        }
        return $test;
    }
    
    private function check_for_duplicate_canonicals($meta, $with_authorship)
    {
        $what = $meta['what']; $i = 0; $test = array();
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if(($i % 10000) == 0) echo "\n".number_format($i)."\n";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            /* rray(
                [taxonID] => 6
                [furtherInformationURL] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=6
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 
                [scientificName] => Bacteria
                [taxonRank] => kingdom
                [taxonomicStatus] => accepted
                [taxonRemarks] => 
            )*/
            // print_r($rec); exit; //use to test if field - value is OK ==================================================================
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            if($with_authorship) {
                if($canon = self::gnsparse_canonical($rec['scientificName'], "cache")) {
                    @$test[$canon][] = $rec['scientificName'];
                }
            }
        }
        self::print_duplicates($what, $test, "_duplicates.txt");
    }
    private function print_duplicates($what, $test, $postfix)
    {
        // print_r($test);
        echo "\n==============================\n";
        echo "\nPrinting duplicates [$postfix]: ".count($test)."\n";
        echo "\n==============================\n";
        $path = $this->sh[$what]['source']."../zFailures/$what".$postfix;
        $FILE = Functions::file_open($path, 'w');
        foreach($test as $canon => $origs) {
            if(count($origs) > 1) {
                $k = 0;
                foreach($origs as $orig) {
                    if($canon != $orig && $canon) {
                        fwrite($FILE, $canon."\t".$orig."\n");
                        $k++;
                    }
                }
                fwrite($FILE, "\n");
                if($k == 1) fwrite($FILE, "***\n");
            }
        }
        fclose($FILE);
        //just to clean-up, delete zero size files
        $path = $this->sh[$what]['source']."../zFailures/$what".$postfix;
        if(file_exists($path)) {
            if(!filesize($path)) {
                echo "\nNo duplicates for [$postfix] [$what]\n"; unlink($path);
            }
        }
    }
    private function is_record_valid($what, $rec)
    {
        if($what == "NCBI") {
            if(in_array($rec['taxonomicStatus'], array("in-part", "authority", "misspelling", "equivalent name", "genbank synonym", "misnomer", "teleomorph"))) return false;
        }
        elseif($what == "COL") {
            if(in_array($rec['taxonomicStatus'], array("ambiguous synonym", "misapplied name"))) return false;
        }
        return true;
    }
    private function need_2run_gnparser_YN($meta)
    {
        $what = $meta['what']; $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            // print_r($rec); exit; //use to test if field - value is OK ==================================================================
            if($val = self::gnsparse_canonical($rec['scientificName'], "cache")) {
                if($val != $rec['scientificName']) return true;
            }
            if($i >= 15) break;
        }
        return false;
    }
    private function process_taxon_file($meta, $with_authorship)
    {
        if($with_authorship) echo "\nWith authorship\n";
        else                 echo "\nWithout authorship\n";
        $what = $meta['what']; $has_synonym = false;
        $fn_tax = fopen($this->sh[$what]['source']."taxonomy.tsv", "w"); //will overwrite existing
        $fn_syn = fopen($this->sh[$what]['source']."synonym.tsv", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t|\t", $this->taxonomy_header)."\t|\t"."\n");
        fwrite($fn_syn, implode("\t|\t", $this->synonym_header)."\t|\t"."\n");
        $i = 0;
        $m = 3620095/3; //for CoL
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file'], false, true, @$this->sh[$what]['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 10000) == 0) echo "\n".number_format($i)."\n";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            /* old
            if($what == 'ncbi') $tmp = explode("\t|\t", $row);
            else                $tmp = explode("\t", $row);
            */
                                $tmp = explode("\t", $row);
            
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ncheck first [$with_authorship]\n"); //use to test if field - value is OK

            /*
            if(in_array($what, array('COL'))) {
                breakdown when caching:
                $cont = false;
                // if($i >=  1    && $i < $m)   $cont = true;
                // if($i >=  $m   && $i < $m*2) $cont = true;
                // if($i >=  $m*2 && $i < $m*3) $cont = true;
                if(!$cont) continue;
            }*/
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            // if(in_array($what, array('WOR', 'NCBI', 'BOM', 'COL', 'trunk', 'ODO', 'ONY', 'ERE', 'CLP', 'ASW', 'IOC', 'ictv', 'EET'))) { //excluded 'pbdb', from initial endeavor
                /*  [index] => 1
                    [taxonomicStatus] => accepted
                    [taxonRank] => superfamily
                    [datasetID] => dd18e3cf-04ba-4b0d-8349-1dd4b7ac5000
                    [parentNameUsageID] => 324b4a02-700b-4ae2-9dbd-65570f42f83c
                    [scientificNameAuthorship] => 
                    [higherClassification] => life,cellular organisms,Eukaryota,Opisthokonta,Metazoa,Bilateria,Protostomia,Ecdysozoa,Panarthropoda,Arthropoda,Chelicerata,Arachnida,Acari,Acariformes,Trombidiformes,Prostigmata,Anystina,Parasitengona
                    [acceptedNameUsageID] => 00016d53-eae4-494c-8f79-3e9ddcd5e634
                    [scientificName] => Arrenuroidea
                    [taxonID] => 00016d53-eae4-494c-8f79-3e9ddcd5e634

                    if accepted_id != taxon_id:
                        print('synonym found')
                        out_file_s.write(accepted_id + '\t|\t' + name + '\t|\t' + 'synonym' + '\t|\t' + '\t|\t' + '\n')
                    else:
                        out_file_t.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                */
                
                // if($rec['scientificName'] == "Cataladrilus (Cataladrilus) Qiu and Bouche, 1998") {
                //     print_r($rec); exit("\ndebugging...\n");
                // }
                
                if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
                
                $t = array();
                $t['parent_id']     = $rec['parentNameUsageID'];    //row[4]
                if($with_authorship) $t['name'] = self::gnsparse_canonical($rec['scientificName'], 'cache'); //row[8]
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']      = $rec['taxonID'];              //row[9]
                $t['accepted_id']   = @$rec['acceptedNameUsageID'];  //row[7]
                $t['rank']          = ($val = @$rec['taxonRank']) ? self::clean_rank($val): "no rank"; //row[2]
                $t['source']        = '';

                /*this is to fix this issue: Notes on data set preprocessing: #2. gnparser https://docs.google.com/spreadsheets/d/1A08xM14uDjsrs-R5BXqZZrbI_LiDNKeO6IfmpHHc6wg/edit?usp=sharing#gid=789044618 */
                if($val = @$this->problematic_names[$rec['taxonID']]) $t['name'] = $val;

                if($this->sh[$what]['has_syn']) {
                    if(($t['accepted_id'] != $t['taxon_id']) && $t['accepted_id'] != "") {
                        self::write2file("syn", $fn_syn, $t);
                        $has_synonym = true;
                    }
                    elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") self::write2file("tax", $fn_tax, $t);
                }
                elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") self::write2file("tax", $fn_tax, $t);
            // }
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            /*
            if(in_array($what, array('ERE', 'CLP', 'ASW', 'IOC', 'ictv', 'EET'))) { //headers changed from version: ioc-birdlist_v2 to ioc-birdlist_v3
                    // [taxonID] => 09af091e166bfa45493c6242ebf16a7c
                    // [scientificName] => Celeus elegans leotaudi Hellmayr, 1906
                    // [taxonRank] => subspecies
                    // [parentNameUsageID] => d6edba5dd4d993cbab690c2df8fc937f
                    // [taxonRemarks] => 
                    // [canonicalName] => Celeus elegans leotaudi
                    // [source] => http://www.worldbirdnames.org/bow/woodpeckers/
                    // [scientificNameAuthorship] => Hellmayr, 1906
                    // out_file_t.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                $t = array();
                $t['parent_id'] = $rec['parentNameUsageID'];
                if($with_authorship) $t['name'] = self::gnsparse_canonical($rec['scientificName'], 'cache');
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']  = $rec['taxonID'];
                $t['rank']      = ($val = @$rec['taxonRank']) ? $val: "no rank";
                $t['source']    = '';
                self::write2file("tax", $fn_tax, $t);
            }*/
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            /*
            if(in_array($what, array('ictv'))) {
                parent_id = row[2]
                name = row[3]
                taxon_id = row[0]
                rank = row[5].lower()
                source = ''
                out_file.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                    [0] => ICTV:Sobemovirus
                    [1] => 
                    [2] => ICTV:unplaced Viruses
                    [3] => Sobemovirus
                    [4] => Viruses|unplaced
                    [5] => genus
                    [taxonID] => ICTV:Sobemovirus
                    [source] => 
                    [parentNameUsageID] => ICTV:unplaced Viruses
                    [scientificName] => Sobemovirus
                    [higherClassification] => Viruses|unplaced
                    [taxonRank] => genus
            }
            */
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        }
        fclose($fn_tax);
        fclose($fn_syn);
        if(!$has_synonym) unlink($this->sh[$what]['source']."synonym.tsv");
    }
    private function clean_rank($rank)
    {
        $rank = strtolower($rank);
        if($rank == "subsp.")       $rank = "subspecies";
        elseif($rank == "var.")     $rank = "variety";
        elseif($rank == "f.")       $rank = "form";
        elseif($rank == "varietas") $rank = "variety";
        elseif($rank == "forma")    $rank = "form";
        return $rank;
    }
    private function parent_id_check($what)
    {
        echo "\nStarts parent_id check...\n";
        $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].'taxonomy.tsv') as $line => $row) {
            $i++; if($i == 1) continue;
            $rec = explode("\t|\t", $row);
            $uids[$rec[0]] = '';
        }
        echo "\nuids: ".count($uids)."\n";
        $i = 0; $undefined_parents = array();
        foreach(new FileIterator($this->sh[$what]['source'].'taxonomy.tsv') as $line => $row) {
            $i++; if($i == 1) continue;
            $rec = explode("\t|\t", $row);
            if($parent_uid = @$rec[1]) {
                // echo " [$parent_uid]";
                if(!isset($uids[$parent_uid])) $undefined_parents[$parent_uid] = '';
            }
        }
        echo "\nUndefined parents: ".count($undefined_parents)."\n";
        if($undefined_parents) {
            echo "\nUndefined parents for [$what]:\n";
            print_r($undefined_parents);
        }
    }
    private function run_file_with_gnparser_new($meta) //creates name_only.txt and converts it to name_only_gnparsed.txt using gnparser. gnparser converts entire file
    {
        $xname = "name_only1";
        $m = 3620095/10; //for CoL
        $what = $meta['what']; $i = 0;
        echo "\nRunning gnparser...\n";
        $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            
            /* A good way to pinpoint the row count - works OK
            if($rec['scientificName'] == "Euchilofulvius carinatus (Poppius, 1913)") exit("\n---[$i]---\n");
            else continue;
            */
            
            /* breakdown when caching:
            $cont = false;
            // if($i >=  1    && $i < $m)   $cont = true;
            // if($i >=  $m   && $i < $m*2) $cont = true;
            // if($i >=  $m*2 && $i < $m*3) $cont = true;
            // if($i >=  $m*3 && $i < $m*4) $cont = true;
            // if($i >=  $m*4 && $i < $m*5) $cont = true;
            // if($i >=  $m*5 && $i < $m*6) $cont = true;
            // if($i >=  $m*6 && $i < $m*7) $cont = true;
            // if($i >=  $m*7 && $i < $m*8) $cont = true;
            // if($i >=  $m*8 && $i < $m*9) $cont = true;
            // if($i >=  $m*9 && $i < $m*10) $cont = true;
            // if($i >= 1,851,000 && $i < 1900000) $cont = true; done
            // if($i >= 1,908,000 && $i < 2000000) $cont = true; done
            if(!$cont) continue;
            */
            
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            if($val = @$rec['scientificName']) fwrite($WRITE, $val."\n");
            if(($i % 1000) == 0) {
                echo "\nmain count:[".number_format($i)."]\n";
                fclose($WRITE);
                $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
                $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
            }
        }
        //last batch
        fclose($WRITE);
        $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
        $out = shell_exec($cmd); echo "\n$out\n";
        self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
    }
    private function run_file_with_gnparser_new_v2($meta) //
    {
        $xname = "name_onlyx2";
        $what = $meta['what']; $i = 0; $saved = 0;
        echo "\nRunning gnparser...\n";
        $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if(($i % 5000) == 0) echo "\n --->:[".number_format($i)."]"; //stopped at 1,645,000 for COL
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            // /* breakdown when caching:
            $cont = false;
            if($i >=  931834 && $i < 4000000) $cont = true;
            if(!$cont) continue;
            // */
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            if($val = @$rec['scientificName']) {
                if(!self::cache_exists($val)) {
                    fwrite($WRITE, $val."\n");
                    $saved++;
                }
            }
            if($saved == 1000) {
                echo "\nmain countx:[".number_format($i)."]\n";
                fclose($WRITE);
                $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
                $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
                $saved = 0;
            }
        }
        //last batch
        fclose($WRITE);
        if($saved) {
            $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
            $out = shell_exec($cmd); echo "\n$out\n";
            self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
        }
    }
    /*
    private function run_file_with_gnparser($meta) //creates name_only.txt and converts it to name_only_gnparsed.txt using gnparser. gnparser converts entire file
    {
        $what = $meta['what']; $i = 0;
        if(file_exists($this->sh[$what]['source'].'name_only_gnparsed_DONE.txt')) {
            echo "\nAll names for [$what] has already been cached.\n";
            return;
        }
        echo "\nRunning gnparser...\n";
        $WRITE = fopen($this->sh[$what]['source']."name_only.txt", "w"); //will overwrite existing
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            // print_r($rec); //exit; //use to test if field - value is OK
            if($val = @$rec['scientificName']) fwrite($WRITE, $val."\n");
        }
        fclose($WRITE);
        // Works OK during older version of gnparser. The later version doesn't have a line separator (\n) between json record.
        // //convert entire file (names) to gnparser version
        // $cmd = "gnparser file --input ".$this->sh[$what]['source']."name_only.txt --output ".$this->sh[$what]['source']."name_only_gnparsed.txt";
        // $out = shell_exec($cmd);
        // echo "\n$out\n";
    }
    */
    private function save_2local_gnparsed_file_new($what, $filename = false) //for latest gnparser
    {
        $big_json = file_get_contents($this->sh[$what]['source'].$filename);
        $arrs = json_decode($big_json, true);
        $i = 0;
        foreach($arrs as $arr) {
            $i++; if($i == 1) continue;
            $json = json_encode($arr);
            // echo "\n$json\n"; continue;
            //copied below -------------------------------------- start
            $name = $arr['verbatim'];
            if(($i % 500) == 0) echo "\n".number_format($i)." $name - ";
            //now check if json already cached. Ignore if it does and save/cache it if it doesn't
            $options['cache_path'] = $this->smasher_download_options['cache_path'];
            $md5 = md5($name);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
            if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
            $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
            if(!file_exists($cache_path) || filesize($cache_path) == 0) {
                if(($i % 500) == 0) echo " - saving...";
                if($FILE = Functions::file_open($cache_path, 'w')) {
                    fwrite($FILE, $json);
                    fclose($FILE);
                }
            }
            else if(($i % 500) == 0) echo " - already saved/cached";
            //copied below -------------------------------------- end
        }
    }
    private function save_2local_gnparsed_file($what, $filename = false)
    {
        if(file_exists($this->sh[$what]['source'].'name_only_gnparsed_DONE.txt')) {
            echo "\nAll names for [$what] has already been cached.\n";
            return;
        }
        $i = 0;
        if(!$filename) $filename = "name_only_gnparsed.txt";
        foreach(new FileIterator($this->sh[$what]['source'].$filename) as $line => $json) {
            $i++; if($i == 1) continue;
            // echo "\n$json\n";
            $arr = json_decode($json, true);
            // print_r($arr); exit;
            $name = $arr['verbatim'];
            if(($i % 1000) == 0) echo "\n".number_format($i)." $name - ";
            //now check if json already cached. Ignore if it does and save/cache it if it doesn't
            $options['cache_path'] = $this->smasher_download_options['cache_path'];
            $md5 = md5($name);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
            if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
            $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
            if(!file_exists($cache_path) || filesize($cache_path) == 0) {
                if(($i % 1000) == 0) echo " - saving...";
                if($FILE = Functions::file_open($cache_path, 'w')) {
                    fwrite($FILE, $json);
                    fclose($FILE);
                }
            }
            else if(($i % 1000) == 0) echo " - already saved/cached";
        }
        if(file_exists($this->sh[$what]['source'].'name_only_gnparsed.txt')) Functions::file_rename($this->sh[$what]['source'].'name_only_gnparsed.txt', $this->sh[$what]['source'].'name_only_gnparsed_DONE.txt');
    }
    private function get_json_from_cache($name, $options = array()) //json generated by gnparser
    {
        // download_wait_time
        if(!isset($options['expire_seconds'])) $options['expire_seconds'] = false;
        if(!isset($options['cache_path'])) $options['cache_path'] = $this->smasher_download_options['cache_path'];
        $md5 = md5($name);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
        if(file_exists($cache_path)) {
            // echo "\nRetrieving cache ($name)...\n"; //good debug
            $file_contents = file_get_contents($cache_path);
            $cache_is_valid = true;
            if(($file_contents && $cache_is_valid) || (strval($file_contents) == "0" && $cache_is_valid)) {
                $file_age_in_seconds = time() - filemtime($cache_path);
                if($file_age_in_seconds < $options['expire_seconds']) return $file_contents;
                if($options['expire_seconds'] === false) return $file_contents;
            }
            @unlink($cache_path);
        }
        //generate json
        echo "\nGenerating cache json for the first time ($name)...\n";
        $cmd = 'gnparser name -f json-compact "'.$name.'"';
        $json = shell_exec($cmd);
        if($json) {
            if($FILE = Functions::file_open($cache_path, 'w+')) {
                fwrite($FILE, $json);
                fclose($FILE);
            }
            //just to check if you can now get the canonical
            if($obj = json_decode($json)) {
                if($ret = @$obj->canonical_name->value)     echo " ---> OK [$ret]";
                elseif($ret = @$obj->canonicalName->value)  echo " ---> OK [$ret]";
                else                                        echo " ---> FAIL";
            }
        }
        return $json;
    }
    
    private function write2file($ext, $fn, $t)
    {
        if($ext == "syn")     fwrite($fn, $t['accepted_id'] . "\t|\t" . $t['name'] . "\t|\t" . 'synonym' . "\t|\t" . "\t|\t" . "\n");
        elseif($ext == "tax") fwrite($fn, $t['taxon_id'] . "\t|\t" . $t['parent_id'] . "\t|\t" . $t['name'] . "\t|\t" . $t['rank'] . "\t|\t" . $t['source'] . "\t|\t" . "\n");
    }
    private function get_canonical_via_api($sciname, $options)
    {
        $json = Functions::lookup_with_cache($this->gnparser.urlencode($sciname), $options);
        if($obj = json_decode($json)) {
            if($ret = @$obj->namesJson[0]->canonical_name->value) return $ret;
        }
    }
    private function gnsparse_canonical($sciname, $method, $download_options = array())
    {
        if(!$download_options) $download_options = $this->smasher_download_options;

        $sciname = self::fix_sciname($sciname); //just to make-the-same approach as utility_write_all_names()
        
        /*
        if($sciname == "all") return "all";
        elseif($sciname == "root") return "root";
        elseif($sciname == "not Bacteria Haeckel 1894") return "not Bacteria";
        // elseif($sciname == "unplaced extinct Onychophora") return "unplaced extinct Onychophora";
        // elseif($sciname == "[Cellvibrio] gilvus") return "[Cellvibrio] gilvus";
        // elseif($sciname == "unplaced Cryptophyceae") return "unplaced Cryptophyceae";
        //force
        if($sciname == "Ichthyoidei- Eichwald, 1831") $sciname = "Ichthyoidei Eichwald, 1831";
        elseif($sciname == "Raniadae- Smith, 1831") $sciname = "Raniadae Smith, 1831";
        elseif($sciname == "prokaryote") $sciname = "Prokaryote";
        elseif($sciname == "prokaryotes") $sciname = "Prokaryotes";
        elseif($sciname == "Amblyomma (Cernyomma) hirtum. Camicas et al., 1998") $sciname = "Amblyomma (Cernyomma) hirtum Camicas et al., 1998";
        elseif($sciname == "Cryptops (Cryptops) vector Chamberlin 1939") $sciname = "Cryptops (Cryptops) vector";
        */
        if($method == "api") {
            if($canonical = self::get_canonical_via_api($sciname, $this->smasher_download_options)) return $canonical;
        }
        elseif($method == "cache") {
            $json = self::get_json_from_cache($sciname, $download_options);
            if($obj = json_decode($json)) {
                if($ret = @$obj->canonical_name->value) return $ret;
                elseif($ret = @$obj->canonicalName->value) return $ret;
                else { //the gnparser code was updated due to bug. So some names has be be re-run using cmdline OR API with expire_seconds = 0

                    self::write_gnparser_failures($this->what, $obj->verbatim);
                    return $obj->verbatim; //un-successfull
                }
            }
        }
        echo("\nInvestigate cannot get canonical name [$sciname][$method]\n");
    }
    private function write_gnparser_failures($what, $name, $postfix = "")
    {
        if($this->sh[$what]['run_gnparse'] == false) return;
        
        $path = $this->sh[$what]['source']."../zFailures/$what".$postfix.".txt";
        if($FILE = Functions::file_open($path, 'a')) {
            // echo "\nadded name failures [$what]: [$name]\n"; //good debug
            fwrite($FILE, $name."\n");
            fclose($FILE);
        }
    }
    public function analyze_eol_meta_xml($meta_xml_path, $row_type = false)
    {
        if(!$row_type) $row_type = "http://rs.tdwg.org/dwc/terms/Taxon";
        if(file_exists($meta_xml_path)) {
            $xml_string = file_get_contents($meta_xml_path);
            $xml = simplexml_load_string($xml_string);
            
            if(!isset($xml->table)) {
                if(isset($xml->core)) $xml_table = $xml->core; //e.g. meta.xml from WoRMS http://www.marinespecies.org/export/eol/WoRMS2EoL.zip
            }
            else                      $xml_table = $xml->table;
            
            foreach($xml_table as $tbl) {
                if($tbl['rowType'] == $row_type) {
                    if(in_array($tbl['ignoreHeaderLines'], array(1, true))) $ignoreHeaderLines = true;
                    else                                                    $ignoreHeaderLines = false;
                    $fields = array();
                    foreach($tbl->field as $f) {
                        $term = (string) $f['term'][0];
                        $uris[] = $term;
                        $fields[] = pathinfo($term, PATHINFO_FILENAME);
                    }
                    $file = (string) $tbl->files->location;
                    return array('fields' => $fields, 'taxon_file' => $file, 'file' => $file, 'ignoreHeaderLines' => $ignoreHeaderLines);
                }
                else {}
            }
            exit("\nInvestigate undefined row_type [$row_type]\n");
        }
        else {
            echo "\nNo meta.xml present. Will use first-row header from taxon file\n";
        }
        exit("\nInvestigate 02.\n");
    }
    private function analyze_meta_xml($meta_xml_path)
    {
        if(file_exists($meta_xml_path)) {
            $xml_string = file_get_contents($meta_xml_path);
            $xml = simplexml_load_string($xml_string);
            // print_r($xml->core);
            if(!isset($xml->core)) {
                echo "\nNo core entry in meta.xml\n";
                return "No core entry in meta.xml";
            }
            if(in_array($xml->core['ignoreHeaderLines'], array(1, true))) $ignoreHeaderLines = true;
            else                                                          $ignoreHeaderLines = false;
            $fields = array();
            if($xml->core['index'] == 0) $fields[] = "index";
            if($xml->core->field[0]['index'] == 0) $fields = array(); //this will ignore <id index="0" />
            foreach($xml->core->field as $f) {
                $term = (string) $f['term'][0];
                $uris[] = $term;
                $fields[] = pathinfo($term, PATHINFO_FILENAME);
            }
            $file = (string) $xml->core->files->location;
            return array('fields' => $fields, 'taxon_file' => $file, 'ignoreHeaderLines' => $ignoreHeaderLines);
        }
        else {
            echo "\nNo meta.xml present. Will use first-row header from taxon file\n";
        }
        exit("\nInvestigate 01.\n");
    }
    private function utility_write_all_names($meta)
    {
        $what = $meta['what']; $i = 0; $ctr = 1;
        //initialize this report file
        $path = $this->sh[$what]['source']."../zFailures/$what"."_failures.txt"; if(file_exists($path)) unlink($path);
        
        $fn_tax = fopen($this->sh[$what]['source']."taxonomy_".$ctr.".txt", "w"); //will overwrite existing
        $fn_syn = fopen($this->sh[$what]['source']."synonym_".$ctr.".txt", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t", $this->taxonomy_header_tmp)."\n");
        fwrite($fn_syn, implode("\t", $this->synonym_header_tmp) ."\n");

        $fn_tax_part = fopen($this->sh[$what]['source']."taxonomy_part_".$ctr.".txt", "w"); //will overwrite existing
        $fn_syn_part = fopen($this->sh[$what]['source']."synonym_part_".$ctr.".txt", "w"); //will overwrite existing
        fwrite($fn_tax_part, implode("\t", array("name"))."\n");
        fwrite($fn_syn_part, implode("\t", array("name")) ."\n");
        
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            //=======================================================================================
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            $t = array();
            $t['parent_id']     = $rec['parentNameUsageID'];
            $t['name']          = self::fix_sciname($rec['scientificName']);
            $t['taxon_id']      = $rec['taxonID'];
            $t['accepted_id']   = @$rec['acceptedNameUsageID'];
            $t['rank']          = ($val = @$rec['taxonRank']) ? self::clean_rank($val): "no rank";
            $t['source']        = '';
            if($this->sh[$what]['has_syn']) {
                if(($t['accepted_id'] != $t['taxon_id']) && $t['accepted_id'] != "") {
                    self::write2file_tmp("syn", $fn_syn, $t);
                    self::write2file_tmp("syn_part", $fn_syn_part, $t);
                    $has_synonym = true;
                }
                elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") {
                    self::write2file_tmp("tax", $fn_tax, $t);
                    self::write2file_tmp("tax_part", $fn_tax_part, $t);
                }
            }
            elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") {
                self::write2file_tmp("tax", $fn_tax, $t);
                self::write2file_tmp("tax_part", $fn_tax_part, $t);
            }
            //=======================================================================================
            if(($i % 500000) == 0) { //500000 orig
                fclose($fn_tax); fclose($fn_syn);
                
                echo "\nrunning gnparser to taxonomy_".$ctr.".txt\n";
                $cmd = "gnparser file -f simple --input ".$this->sh[$what]['source']."taxonomy_part_".$ctr.".txt --output ".$this->sh[$what]['source']."taxonomy_part_".$ctr."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                echo "\nrunning gnparser to synonym_".$ctr.".txt\n";
                $cmd = "gnparser file -f simple --input ".$this->sh[$what]['source']."synonym_part_".$ctr.".txt --output ".$this->sh[$what]['source']."synonym_part_".$ctr."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                
                $ctr++;
                $fn_tax = fopen($this->sh[$what]['source']."taxonomy_".$ctr.".txt", "w"); //will overwrite existing
                $fn_syn = fopen($this->sh[$what]['source']."synonym_".$ctr.".txt", "w"); //will overwrite existing
                fwrite($fn_tax, implode("\t", $this->taxonomy_header_tmp)."\n");
                fwrite($fn_syn, implode("\t", $this->synonym_header_tmp) ."\n");
                
                $fn_tax_part = fopen($this->sh[$what]['source']."taxonomy_part_".$ctr.".txt", "w"); //will overwrite existing
                $fn_syn_part = fopen($this->sh[$what]['source']."synonym_part_".$ctr.".txt", "w"); //will overwrite existing
                fwrite($fn_tax_part, implode("\t", array("name"))."\n");
                fwrite($fn_syn_part, implode("\t", array("name")) ."\n");
            }
        }
        fclose($fn_tax); fclose($fn_syn);
        //last batch
        echo "\nrunning gnparser to taxonomy_".$ctr.".txt\n";
        $cmd = "gnparser file -f simple --input ".$this->sh[$what]['source']."taxonomy_part_".$ctr.".txt --output ".$this->sh[$what]['source']."taxonomy_part_".$ctr."_gnparsed.txt";
        $out = shell_exec($cmd); echo "\n$out\n";
        echo "\nrunning gnparser to synonym_".$ctr.".txt\n";
        $cmd = "gnparser file -f simple --input ".$this->sh[$what]['source']."synonym_part_".$ctr.".txt --output ".$this->sh[$what]['source']."synonym_part_".$ctr."_gnparsed.txt";
        $out = shell_exec($cmd); echo "\n$out\n";
        
        //now we then create the final taxonomy.tsv by looping to all taxonomy_?.txt
        $meta['ctr'] = $ctr;
        $ret = self::build_final_taxonomy_tsv($meta, "taxonomy");  
        if($this->sh[$what]['run_gnparse'] == true) self::print_duplicates($what, $ret, "_duplicates_new.txt");
        $ret = self::build_final_taxonomy_tsv($meta, "synonym");   
        if($this->sh[$what]['run_gnparse'] == true) self::print_duplicates($what, $ret, "_duplicates_syn.txt");

        //clean-up
        $txtfile = $this->sh[$what]['source']."synonym.tsv";
        $total_rows = self::get_total_rows($txtfile);
        if($total_rows <= 1) unlink($txtfile);
    }
    private function get_canonicals_from_gnparser_generated_file($meta, $pre, $cur_ctr)
    {
        $what = $meta['what'];
        $txtfile = $this->sh[$what]['source'].$pre."_part_".$cur_ctr."_gnparsed.txt"; echo "\nreading [$txtfile]\n";
        $i = 0; $final = array(); $withAuthor = array();
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++;
            $arr = explode("\t", $line);
            if($i > 1) {
                $final[] = $arr[2]; //canonicalName
                $withAuthor[] = $arr[1];
            }
        }
        echo "\ncanonicals $pre: ".count($final)."\n";
        echo "\nwith author $pre: ".count($withAuthor)."\n";
        // echo "\n-".$final[0];
        // echo "\n-".$final[1];
        // echo "\n-".$final[2];
        // echo "\n-".$final[7540];
        // echo "\n-".$final[7541];
        // exit("\n");
        return array($final, $withAuthor);
    }
    private function build_final_taxonomy_tsv($meta, $pre)
    {
        $ctr = $meta['ctr']; $what = $meta['what'];
        $fn_tax = fopen($this->sh[$what]['source'].$pre.".tsv", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t|\t", $this->{$pre."_header"})."\t|\t"."\n");
        $test = array();
        for ($c = 1; $c <= $ctr; $c++) {
            $ret = self::get_canonicals_from_gnparser_generated_file($meta, $pre, $c);
            $canonicals = $ret[0];
            $withAuthor = $ret[1];
            
            $txtfile = $this->sh[$what]['source'].$pre."_".$c.".txt"; echo "\nprocessing [$txtfile]\n";

            //just for progress indicator
            $total_rows = self::get_total_rows($txtfile);
            echo "\nTotal rows: [".number_format($total_rows-1)."]\n"; //minus 1 bec. first row is header
            $modulo = self::get_modulo($total_rows);
            
            $i = 0;
            foreach(new FileIterator($txtfile) as $line_number => $line) {
                $i++; if(($i % $modulo) == 0) echo "\n $pre $c of $ctr - ".number_format($i)." ";
                if($i == 1) $line = strtolower($line);
                $row = explode("\t", $line); // print_r($row);
                if($i == 1) {
                    $fields = $row;
                    // print_r($fields);
                    continue;
                }
                else {
                    if(!@$row[0]) continue;
                    $k = 0; $rec = array();
                    foreach($fields as $fld) {
                        $rec[$fld] = @$row[$k];
                        $k++;
                    }
                }
                
                if($pre == "synonym") {
                    // print_r($rec); 
                    // exit("\nstopx\n");
                }
                // print_r($rec); exit("\nstopx\n");
                /*Array( --- taxonomy
                    [name] => Erebidae
                    [uid] => Erebidae
                    [parent_uid] => 
                    [rank] => family
                )
                Array( --- synonym
                    [name] => Zanolidae McDunnough 1938
                    [uid] => Zanolidae
                    [accepted_x_id] => Apatelodidae
                    [type] => synonym
                )*/
                $canon = $canonicals[$i-2];
                if(!$canon) {
                    self::write_gnparser_failures($what, $rec['name'], "_failures");
                    $canon = $withAuthor[$i-2]; //if failure, will get the original string as the canonical.
                }
                // echo "\n[$canon] - [".$rec['name']."]\n"; //good debug
                
                if($this->sh[$what]['run_gnparse'] == false) $canon = $withAuthor[$i-2]; //e.g. NCBI should not compute for any canonical, no gnparser activity here.

                $t = array();
                $t['parent_id']     = @$rec['parent_uid'];      //only for taxonomy
                $t['name']          = $canon;                   //for both
                $t['taxon_id']      = $rec['uid'];              //only for taxonomy
                $t['accepted_id']   = @$rec['accepted_x_id'];   //only for synonym
                $t['rank']          = @$rec['rank'];            //only for taxonomy
                $t['source']        = '';
                
                /*this is to fix this issue: Notes on data set preprocessing: #2. gnparser https://docs.google.com/spreadsheets/d/1A08xM14uDjsrs-R5BXqZZrbI_LiDNKeO6IfmpHHc6wg/edit?usp=sharing#gid=789044618 */
                if($val = @$this->problematic_names[$rec['uid']]) $t['name'] = $val;
                
                $test[$t['name']][] = $withAuthor[$i-2]; //for computing duplicates
                
                if($pre == "taxonomy") self::write2file("tax", $fn_tax, $t);
                else                   self::write2file("syn", $fn_tax, $t); //originally fn_syn, from above
            }
            echo "\n[$c] - Incrementing count for checking duplicates [$pre]: ".count($test)."\n";
        }
        return $test;
    }
    private function write2file_tmp($ext, $fn, $t)
    {
        // if($ext == "syn")     fwrite($fn, $t['name'] . "\t" . $t['accepted_id'] . "\t" . 'synonym' . "\n");
        if($ext == "syn")     fwrite($fn, $t['name'] . "\t" . $t['taxon_id'] . "\t" . $t['accepted_id'] . "\t" . 'synonym' . "\n");
        elseif($ext == "tax") fwrite($fn, $t['name'] . "\t" . $t['taxon_id'] . "\t" . $t['parent_id'] . "\t" . $t['rank'] . "\n");
        if(in_array($ext, array("tax_part", "syn_part"))) fwrite($fn, $t['name'] . "\n");
    }
    private function run_TSV_file_with_gnparser_new($file, $what)
    {
        $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].$file) as $line => $row) {
            $i++;
            if(!$row) continue;
            $arr = explode("\t", $row);
            // if(($i % 10000) == 0) echo "\n".number_format($i);
            echo " -".number_format($i)."- ";
            /*Array(
                [0] => 77f24f37-c0ee-5d53-b21b-56a9c1c2e25b
                [1] => Caulanthus crassicaulis var. glaber M.E. Jones   -   verbatim
                [2] => Caulanthus crassicaulis glaber                   -   canonicalName->value
                [3] => Caulanthus crassicaulis var. glaber              -   canonicalName->valueRanked
                [4] => M. E. Jones
                [5] => 
                [6] => 1
            )*/
            $verbatim = $arr[1];
            if(!self::cache_exists($verbatim)) {
                echo "\n$verbatim -> no rec";
                self::gnsparse_canonical($verbatim, 'cache');
            }
        }
    }
    private function cache_exists($name, $options = array())
    {
        if(!isset($options['cache_path'])) $options['cache_path'] = $this->smasher_download_options['cache_path'];
        $md5 = md5($name);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
        if(file_exists($cache_path)) return true;
        else                         return false;
    }
    private function get_total_rows($file)
    {
        /* source: https://stackoverflow.com/questions/3137094/how-to-count-lines-in-a-document */
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);
        return $total;
    }
    private function get_modulo($total_rows)
    {
        if($total_rows >= 500000) $modulo = 100000;
        elseif($total_rows >= 100000 && $total_rows < 500000) $modulo = 50000;
        elseif($total_rows >= 50000 && $total_rows < 100000) $modulo = 10000;
        else $modulo = 5000;
        return $modulo;
    }
    private function fix_with_period($str)
    {   /* this:    Aloencyrtus angustifrons. (Annecke, 1964) 
           becomes: Aloencyrtus angustifrons (Annecke, 1964) */
        $str = trim($str);
        $arr = explode(" ", $str);
        if(count($arr) > 3) {
            if(substr($arr[1], -1) == ".") {
                $arr[1] = substr($arr[1], 0, -1); //remove last char
                $str = implode(" ", $arr);
            }
        }
        return $str;
    }
    private function fix_sciname($str)
    {
        $str = str_replace('"', "", $str);
        $str = str_replace(",,", ",", $str); //e.g. Matsucoccus sinensis Chen,, 1937
        //from COL ======================================================================================================= start
        $str = str_ireplace("Curtitoma georg?ssiani", "Curtitoma georgossiani", $str);
        $str = str_ireplace("Leptochiton ?ommandorensis", "Leptochiton commandorensis", $str);
        $str = str_ireplace("Leptoplana lutea?", "Leptoplana lutea", $str);
        $str = str_ireplace("Echinus e?culentus", "Echinus esculentus", $str);
        $str = str_ireplace("Cucumaria croceo?da", "Cucumaria croceoida", $str);
        $str = str_ireplace("Cossonus lacupros?", "Cossonus lacupros", $str);
        $str = str_ireplace("Heterolaophonte islandi?a", "Heterolaophonte islandica", $str);
        $str = str_ireplace("Paradiscogaster ?indersi", "Paradiscogaster flindersi", $str);
        $str = str_ireplace("Dendronotus kalikal?", "Dendronotus kalikal", $str);

        /* convert from: "Ontholestes rosti Bernhauer,ms" to: "Ontholestes rosti Bernhauer,(ms)" */
        $str = str_ireplace("Bernhauer,ms", "Bernhauer,(ms)", $str);
        $str = str_ireplace("Scheerpeltz,ms", "Scheerpeltz,(ms)", $str);    //Ocypus	Ocypus schaeferi Scheerpeltz,ms
        $str = str_ireplace("Smetana,ms", "Smetana,(ms)", $str);            //Platydracus	Platydracus juang Smetana,ms
        
        if(stripos($str, " pro syn.") !== false)    $str = str_ireplace(" pro syn.", "", $str); //e.g. Aristida coerulescens pro	Aristida coerulescens Hochst. ex Steud., pro syn.
        if(stripos($str, "“montereina”") !== false) $str = str_ireplace("“montereina”", "Montereina", $str); //e.g. “montereina” greeleyi (MacFarland, 1909)
        //from COL ======================================================================================================= end

        //with (.) period
        $str = self::fix_with_period($str); // convert from: "Amblyomma hirtum."  to: "Amblyomma hirtum"
        
        $str = str_ireplace("?kornick", "Škornick", $str);
        $str = str_ireplace("?erný", "Černý", $str);
        $str = str_ireplace("?tyroký", "Čtyroký", $str);
        $str = str_ireplace("†", "", $str); //remove dagger
        return $str;
    }
    private function scan_resource_file($meta, $final) //a utility
    {
        $what = $meta['what']; $i = 0; $ctr = 1;
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit; //use to test if field - value is OK
            //=======================================================================================
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            /* COL
            Array(
                [taxonID] => 328843
                [furtherInformationURL] => http://www.catalogueoflife.org/annual-checklist/2015/details/species/id/b928b50927a32e8d9075eee882eef492/synonym/912c3bee1ce61534d3a4daf2598266c5
                [acceptedNameUsageID] => 328840
                [parentNameUsageID] => 
                [scientificName] => Pulvinaria maskelli novemarticulata Green, 1915
                [taxonRank] => infraspecies
                [taxonomicStatus] => synonym
            )
            Array( WOR
                [taxonID] => 6
                [furtherInformationURL] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=6
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 
                [scientificName] => Bacteria
                [taxonRank] => kingdom
                [taxonomicStatus] => accepted
                [taxonRemarks] => 
            )*/
            $sciname = $rec['scientificName'];
            if(isset($final[$sciname])) $final[$sciname] = $rec['taxonID'];
        }
        print_r($final); echo "\n";
        foreach($final as $sci => $taxon_id) echo $taxon_id."\t".$sci."\n";
        echo "\n-end-\n";
    }
    public function compare_results()
    {
        // print_r($this->sh['WOR']['source'])
        $sets = array_keys($this->sh);
        print_r($sets);
        foreach($sets as $what) {
            $txtfile = $this->sh[$what]['source']."taxonomy.tsv";
            $total_rows = self::get_total_rows($txtfile);
            echo "\nTotal $what: [".number_format($total_rows)."]\n";

            $txtfile = $this->sh[$what]['source']."taxonomy orig.tsv";
            $total_rows = self::get_total_rows($txtfile);
            echo "\nTotal $what old: [".number_format($total_rows)."]\n";
        }
    }
    private function generate_syn_for_python_file()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1XreJW9AMKTmK13B32AhiCVc7ZTerNOH6Ck_BJ2d4Qng';
        $params['range']         = 'Sheet1!A2:F1000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        /* PriorityHierarchy	taxonID	scientificName	SynonymHierarchy	taxonID	scientificName */
        // foreach($arr as $item) $final[] = array("PriorityH" => $item[0], "Priority_sci" => $item[2], "SynonymH" => $item[3], "Synonym_sci" => $item[5]);
        foreach($arr as $item) $final[$item[3]][] = array("PriorityH" => $item[0], "Priority_sci" => $item[2], "SynonymH" => $item[3], "Synonym_sci" => $item[5]);
        // print_r($final);
        $synonym_hierarchies = array_keys($final);
        print_r($synonym_hierarchies);
        $str = "";
        foreach($synonym_hierarchies as $synonym_hierarchy) {
            $str .= "alignment = dwh.alignment($synonym_hierarchy)\n";
            foreach($final[$synonym_hierarchy] as $rec) {
                $str .= "alignment.same(".$synonym_hierarchy.".taxon('".$rec['Synonym_sci']."'), "."dwh".".taxon('".$rec['Priority_sci']."'))\n";
            }
        }
        echo $str;
        /* $final array
        [COL] => Array(
                    [94] => Array(
                             [PriorityH] => trunk
                             [Priority_sci] => Scalidophora
                             [SynonymH] => COL
                             [Synonym_sci] => Cephalorhyncha
                         )
        alignment = dwh.alignment(wor)
        alignment.same(wor.taxon('Cephalorhyncha'), dwh.taxon('Scalidophora'))
        alignment.same(wor.taxon('Codonosiga'), dwh.taxon('Codosiga'))
        */
    }
    /*
    private function build_final_taxonomy_tsv_old($meta, $pre)
    {
        $ctr = $meta['ctr']; $what = $meta['what'];
        $fn_tax = fopen($this->sh[$what]['source'].$pre.".tsv", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t|\t", $this->{$pre."_header"})."\t|\t"."\n");
        
        for ($c = 1; $c <= $ctr; $c++) {
            $txtfile = $this->sh[$what]['source'].$pre."_".$c."_gnparsed.txt"; echo "\nprocessing [$txtfile]\n";

            //just for progress indicator
            $total_rows = self::get_total_rows($txtfile);
            echo "\nTotal rows: [".number_format($total_rows)."]\n";
            $modulo = self::get_modulo($total_rows);
            
            $i = 0;
            foreach(new FileIterator($txtfile) as $line_number => $line) {
                $i++; if(($i % $modulo) == 0) echo "\n $pre $c of $ctr - ".number_format($i)." ";
                if($i == 1) $line = strtolower($line);
                $row = explode("\t", $line); // print_r($row);
                if($i == 1) {
                    $fields = $row;
                    //fix $fields: important
                    $count = count($this->{$pre."_header_tmp"});
                    $fields[$count+1] = 'canonicalName';
                    $fields[$count+2] = 'valueRanked';
                    $fields[$count+3] = 'other1';
                    $fields[$count+4] = 'other2';
                    $fields[$count+5] = 'other3';
                    // print_r($fields);
                    continue;
                }
                else {
                    if(!@$row[0]) continue;
                    $k = 0; $rec = array();
                    foreach($fields as $fld) {
                        $rec[$fld] = @$row[$k];
                        $k++;
                    }
                }
                // print_r($rec); exit("\nstopx\n");
                Array(
                    [f33063e7-083e-5910-83b4-9a96c170f159] => 9d241baa-f15b-5231-815f-69c2b59ad659
                    [name] => Limacoccus brasiliensis (Hempel, 1934)
                    [uid] => 316423
                    [parent_uid] => 43080004
                    [rank] => species
                    [canonicalName] => Limacoccus brasiliensis
                    [valueRanked] => Limacoccus brasiliensis
                    [other1] => (Hempel 1934)
                    [other2] => 1934
                    [other3] => 3
                )
                Array(
                    [a274cdda-3ca9-559b-9476-6e45eea18eed] => 59f5f484-b052-52f1-8fc0-0b288ca6f2ee
                    [name] => Canceraspis brasiliensis Hempel, 1934
                    [uid] => 316423
                    [type] => synonym
                    [canonicalName] => Canceraspis brasiliensis
                    [valueRanked] => Canceraspis brasiliensis
                    [other1] => Hempel 1934
                    [other2] => 1934
                    [other3] => 3
                )
                if(!$rec['canonicalName']) self::write_gnparser_failures($what, $rec['name'], "_failures");
                
                $t = array();
                $t['parent_id']     = @$rec['parent_uid'];      //only for taxonomy
                $t['name']          = $rec['canonicalName'];    //for both
                $t['taxon_id']      = $rec['uid'];              //only for taxonomy
                $t['accepted_id']   = $rec['uid'];              //only for synonym
                $t['rank']          = @$rec['rank'];            //only for taxonomy
                $t['source']        = '';
                if($pre == "taxonomy") self::write2file("tax", $fn_tax, $t);
                else                   self::write2file("syn", $fn_tax, $t); //originally fn_syn, from above
            }
        }
    }*/
}
?>