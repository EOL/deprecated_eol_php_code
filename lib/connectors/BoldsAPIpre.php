<?php
namespace php_active_record;
/* This connector assembles BOLDS' higher-level taxa list (hl_master_list.txt) {37 hours} */

/* As of 2011 Aug16 in production DB
Statistics of barcoding coverage: 178530
Locations of barcode samples: 135839
Barcode data: 71263 (41,341 with barcode image)
*/

class BoldsAPIpre
{
    const SPECIES_SERVICE_URL = "http://www.boldsystems.org/views/taxbrowser.php?taxid=";

    private static $TEMP_FILE_PATH;
    private static $WORK_LIST;
    private static $WORK_IN_PROGRESS_LIST;
    private static $INITIAL_PROCESS_STATUS;

    private static $MASTER_LIST;
    private static $TG_MASTER_LIST;

    function start_process($resource_id, $call_multiple_instance)
    {
        self::$TEMP_FILE_PATH         = DOC_ROOT . "/update_resources/connectors/files/BOLD/";
        self::$WORK_LIST              = DOC_ROOT . "/update_resources/connectors/files/BOLD/tg_work_list.txt";
        self::$WORK_IN_PROGRESS_LIST  = DOC_ROOT . "/update_resources/connectors/files/BOLD/tg_work_in_progress_list.txt";
        self::$INITIAL_PROCESS_STATUS = DOC_ROOT . "/update_resources/connectors/files/BOLD/tg_initial_process_status.txt";

        self::$MASTER_LIST = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_master_list.txt";
        self::$TG_MASTER_LIST = DOC_ROOT . "/update_resources/connectors/files/BOLD/tg_master_list.txt";

        if(!trim(Functions::get_a_task(self::$WORK_IN_PROGRESS_LIST)))//don't do this if there are harvesting task(s) in progress
        {
            if(!trim(Functions::get_a_task(self::$INITIAL_PROCESS_STATUS)))//don't do this if initial process is still running
            {
                if($fp = fopen(self::$MASTER_LIST, "w")) fclose($fp);
                Functions::add_a_task("Initial process start", self::$INITIAL_PROCESS_STATUS);
                self::create_taxa_group_list();
                Functions::create_work_list_from_master_file(self::$TG_MASTER_LIST, 1, self::$TEMP_FILE_PATH, "tg_batch_", self::$WORK_LIST); //orig value 1
                Functions::delete_a_task("Initial process start", self::$INITIAL_PROCESS_STATUS);
            }
        }

        // Run multiple instances, for Bolds ideally a total of 2
        while(true)
        {
            $task = Functions::get_a_task(self::$WORK_LIST);//get a task to work on
            if($task)
            {
                print "\n Process this: $task";
                Functions::delete_a_task($task, self::$WORK_LIST);
                Functions::add_a_task($task, self::$WORK_IN_PROGRESS_LIST);
                $task = str_ireplace("\n", "", $task);//remove carriage return got from text file
                if($call_multiple_instance)
                {
                    Functions::run_another_connector_instance($resource_id, 1); //call 1 other instance for a total of 2 instances running
                    $call_multiple_instance = 0;
                }
                self::build_taxa_group_list($task);
                print "\n Task $task is done. \n";
                Functions::delete_a_task("$task\n", self::$WORK_IN_PROGRESS_LIST); //remove a task from task list
            }
            else
            {
                print "\n\n [$task] Work list done --- " . date('Y-m-d h:i:s a', time()) . "\n";
                break;
            }
        }
        if(!$task = trim(Functions::get_a_task(self::$WORK_IN_PROGRESS_LIST))) //don't do this if there are task(s) in progress
        {
            // Delete temp files
            self::delete_temp_files(self::$TEMP_FILE_PATH . "tg_batch_", "txt");
        }
    }

    private function build_taxa_group_list($task)
    {
        $FILE = fopen(self::$TEMP_FILE_PATH . $task . ".txt", "r");
        $i = 0; 
        $save_count = 0; 
        $no_eol_page = 0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $group = str_replace("\n", "", $line);
                print "group: [$group]\n";
                self::get_BOLD_taxa($group);
            }
        }
        fclose($FILE);
    }
    
    private function create_taxa_group_list()
    {
        $taxa_groups = array("Fungi", "Plants", "Protists", "Animals_1", "Animals_Arthropoda_Insecta_Coleoptera"
        , "Animals_Arthropoda_Insecta_Diptera", "Animals_Arthropoda_Insecta_Hemiptera", "Animals_Arthropoda_Insecta_Hymenoptera"
        , "Animals_Arthropoda_Insecta_Lepidoptera_Geometridae", "Animals_Arthropoda_Insecta_Lepidoptera_Noctuidae"
        , "Animals_Arthropoda_Insecta_Lepidoptera_Nymphalidae", "Animals_Arthropoda_Insecta_Lepidoptera_Sphingidae"
        , "Animals_Arthropoda_Insecta_Lepidoptera_Tortricidae", "Animals_Arthropoda_Insecta_Lepidoptera_Arctiidae"
        , "Animals_Arthropoda_Insecta_Lepidoptera_1", "Animals_Arthropoda_Insecta_Lepidoptera_2"
        , "Animals_Arthropoda_Insecta_Lepidoptera_3", "Animals_Arthropoda_Insecta_Lepidoptera_4"
        , "Animals_Arthropoda_Insecta_Lepidoptera_5", "Animals_Arthropoda_Insecta_Lepidoptera_6"
        , "Animals_Arthropoda_Insecta_Trichoptera", "Animals_Arthropoda_Insecta_others"
        , "Animals_Arthropoda_Malacostraca", "Animals_Arthropoda_Arachnida"
        , "Animals_Arthropoda_others", "Animals_2"
        , "Animals_Echinodermata", "Animals_Chordata_Actinopterygii"
        , "Animals_Chordata_Aves", "Animals_Chordata_others"
        , "Animals_3", "Animals_4");

        /*
        $taxa_groups = array("elix1", "elix2"); //debug
        $taxa_groups = array("Animals_4"); //debug
        */

        if($fp = fopen(self::$TG_MASTER_LIST, "w")) 
        foreach($taxa_groups as $group)
        {
            fwrite($fp, $group . "\n");
            print "$group \n";
        }
        fclose($fp);
    }

    private function delete_temp_files($file_path, $file_extension)
    {
        $i = 0;
        while(true)
        {
            $i++;
            $i_str = Functions::format_number_with_leading_zeros($i, 3);
            $filename = $file_path . $i_str . "." . $file_extension;
            if(file_exists($filename))
            {
                print "\n unlink: $filename";
                unlink($filename);
            }
            else return;
        }
    }

    private function get_BOLD_taxa($species_group)
    {
        //Animals
        if($species_group == "Animals")
        {
            $arr_phylum = array(0 => array( "name" => "Acanthocephala"  , "id" => 11),
                                1 => array( "name" => "Annelida"        , "id" => 2),
                                2 => array( "name" => "Arthropoda"      , "id" => 20),
                                3 => array( "name" => "Brachiopoda"     , "id" => 9),
                                4 => array( "name" => "Bryozoa"         , "id" => 7),
                                5 => array( "name" => "Chaetognatha"    , "id" => 13),
                                6 => array( "name" => "Chordata"        , "id" => 18),
                                7 => array( "name" => "Cnidaria"        , "id" => 3),
                                8 => array( "name" => "Cycliophora"     , "id" => 79455),
                                9 => array( "name" => "Echinodermata"   , "id" => 4),
                                10 => array( "name" => "Echiura"         , "id" => 27333),
                                11 => array( "name" => "Gnathostomulida" , "id" => 78956),
                                12 => array( "name" => "Hemichordata"    , "id" => 21),
                                13 => array( "name" => "Mollusca"        , "id" => 23),
                                14 => array( "name" => "Nematoda"        , "id" => 19),
                                15 => array( "name" => "Onychophora"     , "id" => 10),
                                16 => array( "name" => "Platyhelminthes" , "id" => 5),
                                17 => array( "name" => "Pogonophora"     , "id" => 28524),
                                18 => array( "name" => "Porifera"        , "id" => 24818),
                                19 => array( "name" => "Rotifera"        , "id" => 16),
                                20 => array( "name" => "Sipuncula"       , "id" => 15),
                                21 => array( "name" => "Tardigrada"      , "id" => 26033),
                                22 => array( "name" => "Xenoturbellida"  , "id" => 88647)
                               );
        }
        if($species_group == "Animals_1")
        {
            $arr_phylum = array(0 => array( "name" => "Acanthocephala"  , "id" => 11),
                                1 => array( "name" => "Annelida"        , "id" => 2),                        
                                2 => array( "name" => "Brachiopoda"     , "id" => 9),
                                3 => array( "name" => "Bryozoa"         , "id" => 7)
                               );                        
        }
        if($species_group == "Animals_Arthropoda") $arr_phylum = array(0 => array( "name" => "Arthropoda", "id" => 20));
        if($species_group == "Animals_Arthropoda_Insecta") $arr_class = array(0 => array( "name" => "Insecta", "id" => 82));
        if($species_group == "Animals_Arthropoda_Insecta_Coleoptera")  $arr_order = array(0 => array( "name" => "Coleoptera"  , "id" => 413));
        if($species_group == "Animals_Arthropoda_Insecta_Diptera")     $arr_order = array(0 => array( "name" => "Diptera"     , "id" => 127));
        if($species_group == "Animals_Arthropoda_Insecta_Hemiptera")   $arr_order = array(0 => array( "name" => "Hemiptera"   , "id" => 133));
        if($species_group == "Animals_Arthropoda_Insecta_Hymenoptera") $arr_order = array(0 => array( "name" => "Hymenoptera" , "id" => 125));
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera") $arr_order = array(0 => array( "name" => "Lepidoptera" , "id" => 113));
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_Geometridae") $arr_family = array(0 => array( "name" => "Geometridae" , "id" => 525));
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_Noctuidae")   $arr_family = array(0 => array( "name" => "Noctuidae"   , "id" => 561));
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_Nymphalidae") $arr_family = array(0 => array( "name" => "Nymphalidae" , "id" => 723));
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_Sphingidae")  $arr_family = array(0 => array( "name" => "Sphingidae"  , "id" => 551));
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_Tortricidae") $arr_family = array(0 => array( "name" => "Tortricidae" , "id" => 425));
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_Arctiidae")   $arr_family = array(0 => array( "name" => "Arctiidae"   , "id" => 603));
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_1")  
        {
            $arr_family = array(0 => array( "name" => "Acanthopteroctetidae"   , "id" => 86520),
                                1 => array( "name" => "Acrolepiidae"   , "id" => 667),
                                2 => array( "name" => "Acrolophidae"   , "id" => 473),
                                3 => array( "name" => "Adelidae"   , "id" => 453),
                                4 => array( "name" => "Agathiphagidae"   , "id" => 533),
                                5 => array( "name" => "Aididae"   , "id" => 86533),
                                6 => array( "name" => "Alucitidae"   , "id" => 33546),
                                7 => array( "name" => "Amphisbatidae"   , "id" => 531),
                                8 => array( "name" => "Andesianidae"   , "id" => 93305),
                                9 => array( "name" => "Anomoeotidae"   , "id" => 271637),
                                10 => array( "name" => "Anthelidae"   , "id" => 643),
                                11 => array( "name" => "Apatelodidae"   , "id" => 269113),
                                12 => array( "name" => "Apateloidae"   , "id" => 177453),
                                13 => array( "name" => "Arrhenophanidae"   , "id" => 86543),
                                14 => array( "name" => "Autostichidae"   , "id" => 455),
                                15 => array( "name" => "Batrachedridae"   , "id" => 553),
                                16 => array( "name" => "Bedelliidae"   , "id" => 73621),
                                17 => array( "name" => "Bombycidae"   , "id" => 709),
                                18 => array( "name" => "Brachodidae"   , "id" => 76981),
                                19 => array( "name" => "Brahmaeidae"   , "id" => 85481)                     
                                );
        }
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_2")  
        {
            $arr_family = array(0 => array( "name" => "Bucculatricidae"   , "id" => 471),
                             1 => array( "name" => "Callidulidae"   , "id" => 86575),
                             2 => array( "name" => "Carposinidae"   , "id" => 683),
                             3 => array( "name" => "Carthaeidae"   , "id" => 86486),
                             4 => array( "name" => "Castniidae"   , "id" => 58871),
                             5 => array( "name" => "Cesidosidae"   , "id" => 210190),
                             6 => array( "name" => "Chimabachidae"   , "id" => 183138),
                             7 => array( "name" => "Choreutidae"   , "id" => 679),
                             8 => array( "name" => "Cimeliidae"   , "id" => 94684),
                             9 => array( "name" => "Coleophoridae"   , "id" => 649),
                             10 => array( "name" => "Copromorphidae"   , "id" => 53617),
                             11 => array( "name" => "Cosmopterigidae"   , "id" => 429),
                             12 => array( "name" => "Cossidae"   , "id" => 703),
                             13 => array( "name" => "Crambidae"   , "id" => 24760),
                             14 => array( "name" => "Crinopterygidae"   , "id" => 86616),
                             15 => array( "name" => "Cryptolechiidae"   , "id" => 183440),
                             16 => array( "name" => "Cyclotornidae"   , "id" => 53594),
                             17 => array( "name" => "Dalceridae"   , "id" => 57912),
                             18 => array( "name" => "Deoclonidae"   , "id" => 112166),
                             19 => array( "name" => "Depressariidae"   , "id" => 53558)                     
                            );
        }
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_3")  
        {
            $arr_family = array(0 => array( "name" => "Dioptidae"   , "id" => 257570),
                             1 => array( "name" => "Doidae"   , "id" => 58791),
                             2 => array( "name" => "Douglasiidae"   , "id" => 687),
                             3 => array( "name" => "Drepanidae"   , "id" => 701),
                             4 => array( "name" => "Dudgeoneidae"   , "id" => 123178),
                             5 => array( "name" => "Elachistidae"   , "id" => 575),
                             6 => array( "name" => "Endromidae"   , "id" => 86561),
                             7 => array( "name" => "Epermeniidae"   , "id" => 727),
                             8 => array( "name" => "Epicopeiidae"   , "id" => 86570),
                             9 => array( "name" => "Epiplemidae"   , "id" => 70354),
                             10 => array( "name" => "Epipyropidae"   , "id" => 435),
                             11 => array( "name" => "Erebidae"   , "id" => 33532),
                             12 => array( "name" => "Eriocottidae"   , "id" => 93269),
                             13 => array( "name" => "Eriocraniidae"   , "id" => 731),
                             14 => array( "name" => "Ethmiidae"   , "id" => 260996),
                             15 => array( "name" => "Eupterotidae"   , "id" => 477),
                             16 => array( "name" => "Galacticidae"   , "id" => 77397),
                             17 => array( "name" => "Gelechiidae"   , "id" => 545),
                             18 => array( "name" => "Glyphidoceridae"   , "id" => 479),
                             19 => array( "name" => "Glyphipterigidae"   , "id" => 491)                     
                            );
        }
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_4")  
        {
            $arr_family = array(0 => array( "name" => "Gracillariidae"   , "id" => 583),
                             1 => array( "name" => "Hedylidae"   , "id" => 441),
                             2 => array( "name" => "Heliodinidae"   , "id" => 447),
                             3 => array( "name" => "Heliozelidae"   , "id" => 697),
                             4 => array( "name" => "Hepialidae"   , "id" => 557),
                             5 => array( "name" => "Hesperiidae"   , "id" => 675),
                             6 => array( "name" => "Heterobathmiidae"   , "id" => 611),
                             7 => array( "name" => "Heterogynidae"   , "id" => 189108),
                             8 => array( "name" => "Himantopteridae"   , "id" => 210215),
                             9 => array( "name" => "Hyblaeidae"   , "id" => 617),
                             10 => array( "name" => "Hypertrophidae"   , "id" => 53565),
                             11 => array( "name" => "Immidae"   , "id" => 663),
                             12 => array( "name" => "Incurvariidae"   , "id" => 475),
                             13 => array( "name" => "Lacturidae"   , "id" => 53547),
                             14 => array( "name" => "Lasiocampidae"   , "id" => 623),
                             15 => array( "name" => "Lecithoceridae"   , "id" => 601),
                             16 => array( "name" => "Lemoniidae"   , "id" => 86621),
                             17 => array( "name" => "Limacodidae"   , "id" => 529),
                             18 => array( "name" => "Lophocoronidae"   , "id" => 139788),
                             19 => array( "name" => "Lycaenidae"   , "id" => 555)                     
                            );
        }
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_5")  
        {
            $arr_family = array(0 => array( "name" => "Lymantriidae"   , "id" => 585),
                             1 => array( "name" => "Lyonetiidae"   , "id" => 501),
                             2 => array( "name" => "Lypusidae"   , "id" => 187140),
                             3 => array( "name" => "Megalopygidae"   , "id" => 605),
                             4 => array( "name" => "Metarbelidae"   , "id" => 274648),
                             5 => array( "name" => "Micropterigidae"   , "id" => 587),
                             6 => array( "name" => "Mimallonidae"   , "id" => 55500),
                             7 => array( "name" => "Mirinidae"   , "id" => 86597),
                             8 => array( "name" => "Mnesarchaeidae"   , "id" => 86609),
                             9 => array( "name" => "Neopseustidae"   , "id" => 86643),
                             10 => array( "name" => "Nepticulidae"   , "id" => 537),
                             11 => array( "name" => "Nolidae"   , "id" => 267046),
                             12 => array( "name" => "Notodonitidae"   , "id" => 177618),
                             13 => array( "name" => "Notodontidae"   , "id" => 521),
                             14 => array( "name" => "Oecophoridae"   , "id" => 433),
                             15 => array( "name" => "Oenosandridae"   , "id" => 53517),
                             16 => array( "name" => "Opostegidae"   , "id" => 513),
                             17 => array( "name" => "Oxytenidae"   , "id" => 55521),
                             18 => array( "name" => "Palaeosetidae"   , "id" => 139909),
                             19 => array( "name" => "Palaephatidae"   , "id" => 139910),
                             20 => array( "name" => "Papilionidae"   , "id" => 489),
                             21 => array( "name" => "Peleopodidae"   , "id" => 71865),
                             22 => array( "name" => "Pieridae"   , "id" => 705),
                             23 => array( "name" => "Plutellidae"   , "id" => 695),
                             24 => array( "name" => "Prodoxidae"   , "id" => 711)
                            );
        }
        if($species_group == "Animals_Arthropoda_Insecta_Lepidoptera_6")  
        {
            $arr_family = array(0 => array( "name" => "Prototheoridae"   , "id" => 210246),
                             1 => array( "name" => "Psychidae"   , "id" => 651),
                             2 => array( "name" => "Pterophoridae"   , "id" => 637),
                             3 => array( "name" => "Pyralidae"   , "id" => 689),
                             4 => array( "name" => "Ratardidae"   , "id" => 93596),
                             5 => array( "name" => "Riodinidae"   , "id" => 591),
                             6 => array( "name" => "Roeslerstammiidae"   , "id" => 53578),
                             7 => array( "name" => "Saturniidae"   , "id" => 451),
                             8 => array( "name" => "Satyridae"   , "id" => 208897),
                             9 => array( "name" => "Schreckensteiniidae"   , "id" => 653),
                             10 => array( "name" => "Scythrididae"   , "id" => 100107),
                             11 => array( "name" => "Sematuridae"   , "id" => 58865),
                             12 => array( "name" => "Sesiidae"   , "id" => 503),
                             13 => array( "name" => "Thyatiridae"   , "id" => 421),
                             14 => array( "name" => "Thyretidae"   , "id" => 163256),
                             15 => array( "name" => "Thyrididae"   , "id" => 493),
                             16 => array( "name" => "Tineidae"   , "id" => 419),
                             17 => array( "name" => "Tineodidae"   , "id" => 76989),
                             18 => array( "name" => "Tischeriidae"   , "id" => 625),
                             19 => array( "name" => "Uraniidae"   , "id" => 691),
                             20 => array( "name" => "Urodidae"   , "id" => 23508),
                             21 => array( "name" => "Xyloryctidae"   , "id" => 417),
                             22 => array( "name" => "Yponomeutidae"   , "id" => 665),
                             23 => array( "name" => "Ypsolophidae"   , "id" => 699),
                             24 => array( "name" => "Zygaenidae"   , "id" => 487)
                            );
        }
        if($species_group == "Animals_Arthropoda_Insecta_Trichoptera") $arr_order = array(0 => array( "name" => "Trichoptera" , "id" => 99));
        if($species_group == "Animals_Arthropoda_Insecta_others")
        {
            $arr_order = array(0 => array( "name" => "Archaeognatha"    , "id" => 87070),
                               1 => array( "name" => "Blattaria"        , "id" => 151950),
                               2 => array( "name" => "Blattodea"        , "id" => 160574),
                               3 => array( "name" => "Dermaptera"       , "id" => 160573),
                               4 => array( "name" => "Dictyoptera"      , "id" => 131),
                               5 => array( "name" => "Diplura"          , "id" => 24810),
                               6 => array( "name" => "Embioptera"       , "id" => 152886),
                               7 => array( "name" => "Ephemeroptera"    , "id" => 405),
                               8 => array( "name" => "Grylloblattodea"  , "id" => 79520),
                               9 => array( "name" => "Homoptera"        , "id" => 228197),
                               10 => array( "name" => "Isoptera"        , "id" => 97),
                               11 => array( "name" => "Mantodea"        , "id" => 80725),
                               12 => array( "name" => "Mantophasmatodea", "id" => 78987),
                               13 => array( "name" => "Mecoptera"       , "id" => 109),
                               14 => array( "name" => "Megaloptera"     , "id" => 27042),
                               15 => array( "name" => "Neuroptera"      , "id" => 107),
                               16 => array( "name" => "Odonata"         , "id" => 105),
                               17 => array( "name" => "Orthoptera"      , "id" => 101),
                               18 => array( "name" => "Phasmatodea"     , "id" => 115),
                               19 => array( "name" => "Phasmida"        , "id" => 266323),
                               20 => array( "name" => "Phthiraptera"    , "id" => 103),
                               21 => array( "name" => "Plecoptera"      , "id" => 135),
                               22 => array( "name" => "Psocoptera"      , "id" => 123),
                               23 => array( "name" => "Raphidioptera"   , "id" => 194686),
                               24 => array( "name" => "Saltatoria"      , "id" => 208619),
                               25 => array( "name" => "Siphonaptera"    , "id" => 91399),
                               26 => array( "name" => "Strepsiptera"    , "id" => 106972),
                               27 => array( "name" => "Thysanoptera"    , "id" => 111),
                               28 => array( "name" => "Thysanura"       , "id" => 121)
                              );
        }
        if($species_group == "Animals_Arthropoda_Malacostraca") $arr_class = array(0 => array( "name" => "Malacostraca" , "id" => 69));
        if($species_group == "Animals_Arthropoda_Arachnida")    $arr_class = array(0 => array( "name" => "Arachnida" , "id" => 63));
        if($species_group == "Animals_Arthropoda_others")        
        {
            $arr_class = array(0 => array( "name" => "Branchiopoda"     , "id" => 68),
                               1 => array( "name" => "Cephalocarida"    , "id" => 73),
                               2 => array( "name" => "Chilopoda"        , "id" => 75),
                               3 => array( "name" => "Cirripedia"       , "id" => 84284),
                               4 => array( "name" => "Collembola"       , "id" => 372),
                               5 => array( "name" => "Diplopoda"        , "id" => 85),
                               6 => array( "name" => "Maxillopoda"      , "id" => 72),
                               7 => array( "name" => "Merostomata"      , "id" => 74),
                               8 => array( "name" => "Ostracoda"        , "id" => 80),
                               9 => array( "name" => "Pentastomida"     , "id" => 83),
                               10 => array( "name" => "Pycnogonida"     , "id" => 26059),
                               11 => array( "name" => "Remipedia"       , "id" => 84),
                               12 => array( "name" => "Symphyla"        , "id" => 80390)                            
                              );
        }
        if($species_group == "Animals_2")
        {
            $arr_phylum = array(0 => array( "name" => "Chaetognatha"    , "id" => 13),
                                1 => array( "name" => "Cnidaria"        , "id" => 3),
                                2 => array( "name" => "Cycliophora"     , "id" => 79455)
                               );
        }
        if($species_group == "Animals_Chordata")                $arr_phylum = array(0 => array( "name" => "Chordata" , "id" => 18));
        if($species_group == "Animals_Chordata_Actinopterygii") $arr_class = array(0 => array( "name" => "Actinopterygii" , "id" => 77));
        if($species_group == "Animals_Chordata_Aves")           $arr_class = array(0 => array( "name" => "Aves"           , "id" => 51));
        if($species_group == "Animals_Chordata_others")
        {
            $arr_class = array(0 => array( "name" => "Amphibia"             , "id" => 50),
                               1 => array( "name" => "Appendicularia"       , "id" => 96365),
                               2 => array( "name" => "Ascidiacea"           , "id" => 61),
                               3 => array( "name" => "Cephalaspidomorphi"   , "id" => 64),
                               4 => array( "name" => "Cephalochordata"      , "id" => 65),
                               5 => array( "name" => "Elasmobranchii"       , "id" => 34196),
                               6 => array( "name" => "Holocephali"          , "id" => 34231),
                               7 => array( "name" => "Larvacea"             , "id" => 263620),
                               8 => array( "name" => "Mammalia"             , "id" => 62),
                               9 => array( "name" => "Myxini"               , "id" => 66),
                               10 => array( "name" => "Reptilia"            , "id" => 76),
                               11 => array( "name" => "Sarcopterygii"       , "id" => 52),
                               12 => array( "name" => "Thaliacea"           , "id" => 27266)
                              );
        }
        if($species_group == "Animals_Echinodermata") $arr_phylum = array(0 => array( "name" => "Echinodermata", "id" => 4));
        if($species_group == "Animals_3")
        {
            $arr_phylum = array(0 => array( "name" => "Echiura"         , "id" => 27333),
                                1 => array( "name" => "Gnathostomulida" , "id" => 78956),
                                2 => array( "name" => "Hemichordata"    , "id" => 21),
                                3 => array( "name" => "Mollusca"        , "id" => 23),
                                4 => array( "name" => "Nematoda"        , "id" => 19)
                               );
        }
        if($species_group == "Animals_4")
        {
            $arr_phylum = array(0 => array( "name" => "Onychophora"     , "id" => 10),
                                1 => array( "name" => "Platyhelminthes" , "id" => 5),
                                2 => array( "name" => "Pogonophora"     , "id" => 28524),
                                3 => array( "name" => "Porifera"        , "id" => 24818),
                                4 => array( "name" => "Rotifera"        , "id" => 16),
                                5 => array( "name" => "Sipuncula"       , "id" => 15),
                                6 => array( "name" => "Tardigrada"      , "id" => 26033),
                                7 => array( "name" => "Xenoturbellida"  , "id" => 88647)
                               );
        }
        //Fungi 
        if($species_group == "Fungi")
        {
            $arr_phylum = array(0 => array( "name" => "Ascomycota"      , "id" => 34),
                                1 => array( "name" => "Basidiomycota"   , "id" => 23675),
                                2 => array( "name" => "Chytridiomycota" , "id" => 23691),
                                3 => array( "name" => "Myxomycota"      , "id" => 83947),
                                4 => array( "name" => "Zygomycota"      , "id" => 23738)
                               );
        }
        //Plants 
        if($species_group == "Plants")
        {
            $arr_phylum = array(0 => array( "name" => "Bryophyta"           , "id" => 176192),
                                1 => array( "name" => "Chlorarachniophyta"  , "id" => 109954),
                                2 => array( "name" => "Chlorophyta"         , "id" => 112296),
                                3 => array( "name" => "Lycopodiophyta"      , "id" => 38696),
                                4 => array( "name" => "Magnoliophyta"       , "id" => 12),
                                5 => array( "name" => "Pinophyta"          , "id" => 251587),
                                6 => array( "name" => "Pteridophyta"       , "id" => 38074),
                                7 => array( "name" => "Pyrrophycophyta"    , "id" => 91354),
                                8 => array( "name" => "Rhodophyta"         , "id" => 48327),
                                9 => array( "name" => "Stramenopiles"      , "id" => 109924)
                               );
        }
        //Protists
        if($species_group == "Protists")
        {
            $arr_phylum = array(0 => array( "name" => "Bacillariophyta"    , "id" => 74445),
                                1 => array( "name" => "Ciliophora"         , "id" => 72834),
                                2 => array( "name" => "Dinozoa"            , "id" => 70855),
                                3 => array( "name" => "Heterokontophyta"   , "id" => 53944),
                                4 => array( "name" => "Opalozoa"           , "id" => 72171),
                                5 => array( "name" => "Straminipila"       , "id" => 23715)
                               );
        }

        /* debug
        if($species_group == "elix1")
        {
            $arr_phylum = array();
            $arr_phylum[] = array( "name" => "Xenoturbellida"  , "id" => 88647);
        }    
        if($species_group == "elix2")
        {
            $arr_family = array();
            $arr_family[] = array( "name" => "Proteocephalidae"     , "id" => 72162);
        } 
        */

        if(isset($arr_phylum))      $arr_taxa = $arr_phylum;
        elseif(isset($arr_class))   $arr_taxa = $arr_class;
        elseif(isset($arr_order))   $arr_taxa = $arr_order;
        elseif(isset($arr_family))  $arr_taxa = $arr_family;
        $list = array_merge($arr_taxa, self::get_all_taxa_under_this_group($arr_taxa));
        print"\n All Taxa in BOLD: " . count($list);
        self::save_to_txt($list);

    }//get_BOLD_taxa

    private function save_to_txt($arr)
    {
        $str = "";        
        foreach ($arr as $value) $str .= $value["id"] . "\t" . $value["name"] . "\n";
        if($fp = fopen(self::$MASTER_LIST, "a"))
        {
            fwrite($fp,$str);
            fclose($fp);
        }
    }

    private function get_all_taxa_under_this_group($arr)
    {   
        $main_name_id_list = array();
        $arr_for_deletion = array();
        foreach ($arr as $a)
        {
            print "\n a11 " . $a["name"] . " -- " . $a["id"];
            $str = Functions::get_remote_file(self::SPECIES_SERVICE_URL . $a["id"]);
            $arr2 = self::proc_subtaxa_block($str);
            if(!$arr2) $arr_for_deletion[] = $a;
            $main_name_id_list = array_merge($main_name_id_list, $arr2);
            foreach ($arr2 as $a2)//class loop
            {
                print "\n a22 " . $a2["name"] . " -- " . $a2["id"];
                $str = Functions::get_remote_file(self::SPECIES_SERVICE_URL . $a2["id"]);
                $arr3 = self::proc_subtaxa_block($str);
                if(!$arr3) $arr_for_deletion[] = $a2;                    
                $main_name_id_list = array_merge($main_name_id_list, $arr3);
                foreach ($arr3 as $a3)//order loop
                {
                    print "\n a33 " . $a3["name"] . " -- " . $a3["id"];
                    $str = Functions::get_remote_file(self::SPECIES_SERVICE_URL . $a3["id"]);
                    $arr4 = self::proc_subtaxa_block($str);
                    if(!$arr4) $arr_for_deletion[] = $a3;
                    $main_name_id_list = array_merge($main_name_id_list, $arr4);
                    foreach ($arr4 as $a4)//family loop
                    {
                        print "\n a44 " . $a4["name"] . " -- " . $a4["id"];
                        $str = Functions::get_remote_file(self::SPECIES_SERVICE_URL . $a4["id"]);
                        $arr5 = self::proc_subtaxa_block($str);
                        if(!$arr5) $arr_for_deletion[] = $a4;
                        $main_name_id_list = array_merge($main_name_id_list, $arr5);
                        foreach ($arr5 as $a5)//subfamily if there is any or Genus loop
                        {
                            print "\n a55 " . $a5["name"] . " -- " . $a5["id"];
                            $str = Functions::get_remote_file(self::SPECIES_SERVICE_URL . $a5["id"]);
                            $arr6 = self::proc_subtaxa_block($str);
                            if(!$arr6) $arr_for_deletion[] = $a5;
                            $main_name_id_list = array_merge($main_name_id_list, $arr6);
                            foreach ($arr6 as $a6)//Genus if there was subfamily above loop
                            {
                                print "\n a66 " . $a6["name"] . " -- " . $a6["id"];
                                $str = Functions::get_remote_file(self::SPECIES_SERVICE_URL . $a6["id"]);
                                $arr7 = self::proc_subtaxa_block($str);
                                if(!$arr7) $arr_for_deletion[] = $a6;
                                $main_name_id_list = array_merge($main_name_id_list, $arr7);
                            }
                        }
                    }
                }
            }
        }
        /* we won't need this anymore as we now stopped the process before hand
        return self::remove_species_level_taxa($main_name_id_list, $arr_for_deletion); //to remove species level taxa
        */
        return $main_name_id_list;
    }

    function remove_species_level_taxa($list, $deletion)
    {
        $list_without_species_level_taxa = array();
        foreach($list as $rec)
        {
            if(!in_array($rec, $deletion)) $list_without_species_level_taxa[] = $rec;
        }
        return $list_without_species_level_taxa;
    }

    private function proc_subtaxa_block($str)
    {
        if(preg_match("/<h2>Sub-taxa<\/h2>(.*?)<\/ul>/ims", $str, $matches)) $str = $matches[1]; 
        //stops processing, doesn't go deeper if taxon is already in the species level

        /* use this if you want all higher-level taxa only */
            $pos = stripos($str, "Species (");
            if(is_numeric($pos))
            {
                print " -stop here- ";
                return array();
            }
            //for cleaning
            $pos = stripos($str, "taxbrowser.php?taxid=");
            if(!is_numeric($pos))
            {
                print " -stop here- ";
                return array();
            }

        /* use this if you want all taxa, including species level taxa
            $pos = stripos($str, "taxbrowser.php?taxid=");
            if(!is_numeric($pos))
            {
                print " -stop here- ";
                return array();
            }
        */
        
        
        $final = self::get_name_id_from_array($str);
        return $final;
    }

    private function get_name_id_from_array($str)
    {
        $str = strip_tags($str,"<a>");
        $str = str_ireplace('<a', 'xxx<a', $str);
        $str = str_ireplace('xxx', "&arr[]=", $str);
        $arr = array(); parse_str($str);
        $final = array();
        foreach ($arr as $a)
        {
            $name = "xxx" . self::get_str_from_anchor_tag($a);
            if(preg_match("/xxx(.*?)\[/ims", $name, $matches)) $name = $matches[1];
            $id = self::get_href_from_anchor_tag($a)."xxx";
            if(preg_match("/taxid=(.*?)xxx/ims", $id, $matches)) $id = $matches[1];
            $final[] = array("name" => $name, "id" => $id);
        }
        return $final;
    }

    private function get_str_from_anchor_tag($str)
    {
        if(preg_match("/\">(.*?)<\/a>/ims", $str, $matches)) return $matches[1];
    }
    private function get_href_from_anchor_tag($str)
    {
        if(preg_match("/href=\"(.*?)\"/ims", $str, $matches)) return $matches[1];
    }

}
?>