<?php
namespace php_active_record;
/* connector: freedata_globi.php 
added 1st column headers in its resource file:
dynamichierarchytrunk14jun201720170615085118/
*/
class DHSmasherOutputAPI
{
    function __construct($params)
    {
        $this->params = $params;
        $this->folder = "smasher";
        $this->destination[$this->folder] = CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations.txt";
        $this->print_header = true;
        
        $this->download_options = array(
            'cache_path'         => '/Volumes/Thunderbolt4/eol_cache_gbif/',  //used in MacBook - generating map data using GBIF API and also the dynamic hierarchy smasher file process.
            // 'expire_seconds'     => 5184000, //orig 2 months to expire
            'download_wait_time' => 2000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->download_options['expire_seconds'] = false; //debug | true -- expires now

        //GBIF services
        $this->gbif_NameUsage = "http://api.gbif.org/v1/species/";
        /*
        $this->gbif_taxon_info      = "http://api.gbif.org/v1/species/match?name="; //http://api.gbif.org/v1/species/match?name=felidae&kingdom=Animalia
        $this->gbif_record_count    = "http://api.gbif.org/v1/occurrence/count?taxonKey=";
        $this->gbif_occurrence_data = "http://api.gbif.org/v1/occurrence/search?taxonKey=";
        */
        
        //services
        $this->smasher_cache = "/Volumes/Thunderbolt4/eol_cache_smasher/";
        
        //TRAM-581
        $this->url['api_search'] = "http://eol.org/api/search/1.0.json?page=1&exact=true&cache_ttl=&q=";
        $this->download_options2 = array("resource_id" => "trait_request", "download_wait_time" => 2000000, "timeout" => 3600, "download_attempts" => 1);
        $this->download_options2['expire_seconds'] = false;
        $this->path_EHE_tsv_files = CONTENT_RESOURCE_LOCAL_PATH . "smasher_EHE/";
        
        //furtherInformationURLs
        $this->fiu["APH"] = "http://aphid.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["BLA"] = "http://cockroach.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["COL"] = "http://coleorrhyncha.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["COR"] = "http://coreoidea.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["DER"] = "http://dermaptera.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["EMB"] = "http://embioptera.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["GRY"] = "http://grylloblattodea.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID="; 
        $this->fiu["LYG"] = "http://lygaeoidea.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["MAN"] = "http://mantophasmatodea.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["MNT"] = "http://mantodea.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["ORTH"] = "http://orthoptera.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["PHA"] = "http://phasmida.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["PLE"] = "http://plecoptera.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["PSO"] = "http://psocodea.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID=";
        $this->fiu["ZOR"] = "http://zoraptera.speciesfile.org/Common/basic/Taxa.aspx?TaxonNameID="; 
        $this->fiu["gbif"] = "https://www.gbif-uat.org/species/";
        $this->fiu["TPL"] = "http://www.theplantlist.org/tpl1.1/record/";
        
        
        
        $this->debug = array();
    }

    function search_ok($url)
    {
        if($json = Functions::lookup_with_cache($url, $this->download_options2)) {
            $obj = json_decode($json);
            if($rec = @$obj->results[0]) {
                $taxon_rec = array();
                // $taxon_rec['sciname'] = $sciname;
                $taxon_rec['EOLid'] = $rec->id;
                print_r($taxon_rec);
                return $taxon_rec;
            }
        }
        else return false;
    }
    
    function get_eol_id($rek, $first, $scientificName)
    {
        print_r($rek); //debug only
        /* $rek => Array (
            [taxonID] => -739550
            [acceptedNameUsageID] => -739550
            [parentNameUsageID] => -577816
            [scientificName] => Acanthixalus
            [taxonRank] => genus
            [source] => AMP:Acanthixalus
            [taxonomicStatus] => accepted
        ) */
        
        $first['scientificName'] = $scientificName; //deliberately add sciname in $first array
        print_r($first);
        $recs = self::get_recs_from_EHE($first);
        if($recs)
        {   /* [0] => Array (
                    [EOLid] => 42922
                    [richness_score] => 0
                    [scientificName] => "Acanthixalus"
                    [he_id] => 52661717
                    [source_hierarchy] => "Species 2000 & ITIS Catalogue of Life: April 2013 #1188"
                ) */
            print_r($recs); //debug only -> there are the recs fetched from EHE
            if($first['acronym'] == "AMP") //==================================== start AMP
            {
                foreach($recs as $rec) {    //1st option
                    if($rec['source_hierarchy'] == "AmphibiaWeb #119" && $first['scientificName'] == $rec['scientificName']) return $rec['EOLid'];
                }
                foreach($recs as $rec) {    //2nd option
                    if($rec['source_hierarchy'] == "Species 2000 & ITIS Catalogue of Life: April 2013 #1188")
                    {
                        if($rek['taxonRank'] == "genus") //exact match
                        {
                            if($rek['scientificName'] == $rec['scientificName']) return $rec['EOLid'];
                        }
                        elseif($rek['taxonRank'] == "species") //begins_with
                        {
                            if($rek['scientificName'] == substr($rec['scientificName'],0,strlen($rek['scientificName']))) return $rec['EOLid'];
                        }
                    }
                }
                //3rd option -> any exact match from any source hierarchy
            } //================================================================ end AMP

            if($first['acronym'] == "IOC") //==================================== start IOC
            {
                //start costly IOC workflow - https://eol-jira.bibalex.org/browse/TRAM-581?focusedCommentId=61277&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61277
                $avibase_hierarchy = "Avibase - IOC World Bird Names (2011) #860";
                $others = array();
                foreach($recs as $rec) {
                    if($rec['source_hierarchy'] != $avibase_hierarchy)
                    {
                        if($first['scientificName'] == $rec['scientificName']) $others[$rec['EOLid']][] = $rec['source_hierarchy'];
                        //new by eli
                        if(Functions::canonical_form($first['scientificName']) == Functions::canonical_form($rec['scientificName'])) $others[$rec['EOLid']][] = $rec['source_hierarchy'];
                        
                    }
                }
                //end costly IOC workflow
                
                foreach($recs as $rec) {    //1st option
                    if($rec['source_hierarchy'] == $avibase_hierarchy)
                    {
                        if(in_array($rek['taxonRank'], array("genus","family"))) //exact match
                        {
                            if($rek['scientificName'] == $rec['scientificName']) $others[$rec['EOLid']][] = $avibase_hierarchy;
                        }
                        elseif($rek['taxonRank'] == "species") //begins_with
                        {
                            if($rek['scientificName'] == substr($rec['scientificName'],0,strlen($rek['scientificName']))) $others[$rec['EOLid']][] = $avibase_hierarchy;
                        }
                    }
                }
                
                //start cont. costly  ===============================
                $result = array(); 
                $clements_hierarchy = "Clements Checklist resource #1128";
                foreach(array_keys($others) as $eol_id)
                {
                    if(in_array($clements_hierarchy, $others[$eol_id])) 
                    {
                        $result['clements']['hierarchies using this id'] = count($others[$eol_id]);
                        $result['clements']['EOLid'] = $eol_id;
                    }
                    if(in_array($avibase_hierarchy, $others[$eol_id])) 
                    {
                        $result['avibase']['hierarchies using this id'] = count($others[$eol_id]);
                        $result['avibase']['EOLid'] = $eol_id;
                    }
                }
                print_r($result);
                print_r(@$others[$result['clements']['EOLid']]);
                print_r(@$others[$result['avibase']['EOLid']]);
                /*
                [clements] => Array(
                        [hierarchies using this id] => 12
                        [EOLid] => 18990
                    )
                [avibase] => Array(
                        [hierarchies using this id] => 1
                        [EOLid] => 45509532
                    )
                */
                if(@$result['avibase']['hierarchies using this id'] == 1 && @$result['clements']['hierarchies using this id'] > 1) return $result['clements']['EOLid'];
                if(@$result['avibase']['hierarchies using this id'] == 1 && @$result['clements']['hierarchies using this id'] <= 1) return $result['avibase']['EOLid'];
                //just Eli
                if(!@$result['avibase']['hierarchies using this id'] && @$result['clements']['hierarchies using this id'] > 0) return $result['clements']['EOLid'];
                //end cont. costly ===============================
                
                //2nd option -> any exact match from any source hierarchy
            } //================================================================ end IOC

            if($first['acronym'] == "WOR") //==================================== start WOR
            {
                foreach($recs as $rec) {    //1st option
                    if($rec['source_hierarchy'] == "WORMS Species Information (Marine Species) #123" && $first['scientificName'] == $rec['scientificName']) return $rec['EOLid'];
                }
                //additional 1st option just Eli
                foreach($recs as $rec) {    //1st option
                    if($rec['source_hierarchy'] == "WORMS Species Information (Marine Species) #123" && Functions::canonical_form($first['scientificName']) == Functions::canonical_form($rec['scientificName'])) return $rec['EOLid'];
                }
                foreach($recs as $rec) {    //2nd option
                    if($rec['source_hierarchy'] == "Algeabase resource #1280" && $first['scientificName'] == $rec['scientificName']) return $rec['EOLid'];
                }
                //3rd option -> any exact match from any source hierarchy
            } //================================================================ end WOR

            if(!in_array($first['acronym'], array("AMP","IOC","WOR"))) // ===================== start REST of the acronyms
            {
                $ordered_priority_hierarchies = array("Species 2000 & ITIS Catalogue of Life: April 2013 #1188", "WORMS Species Information (Marine Species) #123",
                "NCBI Taxonomy #1172", "Integrated Taxonomic Information System (ITIS) #903",
                "FishBase (Fish Species) #143", "The Reptile Database #787", "Index Fungorum #596", "MycoBank Classification #1283",
                "Tropicos resource #636", "Algeabase resource #1280", "AntWeb (Ant Species) #121", "Paleobiology Database #967", "Extant & Habitat resource #1347");
                foreach($ordered_priority_hierarchies as $source_hierarchy) //option 1
                {
                    foreach($recs as $rec) {
                        if($rec['source_hierarchy'] == $source_hierarchy && $first['scientificName'] == $rec['scientificName']) return $rec['EOLid'];
                    }
                    
                    //by Eli
                    foreach($recs as $rec) {
                        if($rec['source_hierarchy'] == $source_hierarchy && Functions::canonical_form($first['scientificName']) == Functions::canonical_form($rec['scientificName'])) return $rec['EOLid'];
                    }
                    
                }
                //option 2 -> any exact match from any source hierarchy
            } // =========================== end REST of the acronyms


            //common to all -> any exact match from any source hierarchy
            foreach($recs as $rec) {
                if($first['scientificName'] == $rec['scientificName']) return $rec['EOLid'];
            }
            return "";
            

            


        }
        else echo "\nnext ...\n";
        return "";
    }
    
    
    // Sending get request to http://eol.org/api/search/1.0.json?page=1&exact=true&cache_ttl=&q=Abrus pulchellus subsp. suffruticosus : only attempt :: [lib/Functions.php [204]]<br>
    // Sending get request to http://eol.org/api/search/1.0.json?page=1&exact=true&cache_ttl=&q=Abrus pulchellus suffruticosus : only attempt :: [lib/Functions.php [204]]<br>
    // Sending get request to http://eol.org/api/search/1.0.json?page=1&exact=false&cache_ttl=&q=Abrus pulchellus subsp. suffruticosus : only attempt :: [lib/Functions.php [204]]<br>
    
    function utility2()
    {
        $included_acronyms = array("TPL");
        // $included_acronyms = array("IOC");
        // $included_acronyms = array("WOR");
        // $included_acronyms = array("AMP");
        
        
        $smasher_file = self::adjust_filename($this->params["smasher"]["url"]);
        $i = 0; $m = 466666; //466666; 280000
        foreach(new FileIterator($smasher_file) as $line => $row) {
            $i++;
            if(($i % 100000) == 0) echo " $i";
            if($i == 1) $fields = explode("\t", $row);
            else {
                $rec = array(); //just to be sure
                $rec = explode("\t", $row);
                $k = -1;
                $rek = array();
                foreach($fields as $field) {
                    $k++;
                    if($val = @$rec[$k]) $rek[$field] = $val;
                }
                if($rek)
                {
                    /* breakdown when caching:
                    $cont = false;
                    // if($i >=  1    && $i < $m) $cont = true;
                    // if($i >=  $m   && $i < $m*2) $cont = true;
                    // if($i >=  $m*2 && $i < $m*3) $cont = true;
                    // if($i >=  $m*3 && $i < $m*4) $cont = true;
                    // if($i >=  $m*4 && $i < $m*5) $cont = true;
                    // if($i >=  $m*5 && $i < $m*6) $cont = true;
                    // if($i >=  $m*6 && $i < $m*7) $cont = true;
                    // if($i >=  $m*7 && $i < $m*8) $cont = true;
                    // if($i >=  $m*8 && $i < $m*9) $cont = true;
                    // if($i >=  $m*9 && $i < $m*10) $cont = true;
                    if(!$cont) continue;
                    */

                    $sciname = $rek['scientificName'];
                    
                    // /* getting the EOLid ======================================= OK
                    // echo "\nsmasher record: ----------------------------";
                    // print_r($rek); //debug only
                    $first_source = self::get_first_source($rek['source']);
                    // print_r($first_source);
                    
                    // normal operation
                    // self::get_eol_id($rek, $first_source);
                    
                    // if(in_array($first_source['acronym'], $excluded_acronyms)) continue;
                    // self::get_eol_id($rek, $first_source);
                    
                    if(in_array($first_source['acronym'], $included_acronyms))
                    {
                        $fetched = self::fetch_record($first_source);
                        if($eol_id = self::retrieve_cache_EOLid($first_source, $fetched['scientificName']))
                        {
                            echo "\nRetrieved EOLid = [$eol_id] from cache\n";
                            // exit;
                        }
                        else
                        {
                            if($eol_id = self::get_eol_id($rek, $first_source, $fetched['scientificName'])) //scientificName is now from resource file
                            {
                                echo "\nEOLid = [$eol_id] SAVED to cache\n";
                                self::write_cache_EOLid($eol_id, $first_source, $fetched['scientificName']);
                                // exit;
                            }
                            else
                            {
                                echo "\n-NO EOLid-\n";
                                // exit;
                            }
                        }
                        
                    }
                    else continue;
                    // ==============================================================*/
                    
                    
                    
                    
                    /* caching EOL API name search ===================================== OK
                    $url1 = $this->url['api_search'].$sciname;
                    if($taxon_rec = self::search_ok($url1)) {}
                    else
                    {
                        $url2 = $this->url['api_search'].Functions::canonical_form($sciname);
                        if($taxon_rec = self::search_ok($url2)) {}
                        else
                        {
                            // ---------- not advisable to do, used the EHE instead. Sample why not good is sciname = "Abavorana", gives 46350291 using EHE while API gives 46350292 where exact=false.
                            // $url1 = str_ireplace("exact=true","exact=false",$url1);
                            // if($taxon_rec = self::search_ok($url1)) {}
                            // else
                            // {
                            //     $url2 = str_ireplace("exact=true","exact=false",$url2);
                            //     if($taxon_rec = self::search_ok($url2)) {}
                            //     else echo("\ntalagang wala lang...[$sciname]\n");
                            // }
                            // ---------
                        }
                    }
                     =============================================================== */
                    // exit("\n-end utility2-\n");
                }
            }
        }
    }
    
    function utility() //creating local cache based on resource files from google sheet
    {   /* */
        // $acronyms = array_keys($this->params);
        $less = array('gbif','WOR'); //WOR TPL gbif
        $acronyms = array('WOR'); //WOR TPL gbif
        print_r($acronyms);
        // exit;
        foreach($acronyms as $acronym)
        {
            $txtfile = self::adjust_filename($this->params[$acronym]["url"]);
            if(!file_exists($txtfile)) exit("\nfile does not exist: [$txtfile]\n");
            else echo "\nfound:1 [$txtfile]";
            $i = 0; $m = 200000;
            foreach(new FileIterator($txtfile) as $line => $row) {
                $i++;
                if($i == 1) $fields = explode("\t", $row);
                else
                {
                    $rec = array(); //just to be sure
                    $rec = explode("\t", $row);
                    $k = -1;
                    $rek = array();
                    foreach($fields as $field) {
                        $k++;
                        if($val = @$rec[$k]) $rek[$field] = $val;
                    }
                    if($rek)
                    {
                        /* breakdown when caching:
                        $cont = false;
                        // if($i >=  1    && $i < $m) $cont = true;
                        // if($i >=  $m   && $i < $m*2) $cont = true;
                        // if($i >=  $m*2 && $i < $m*3) $cont = true;
                        // if($i >=  $m*3 && $i < $m*4) $cont = true;
                        if($i >=  $m*4 && $i < $m*5) $cont = true;
                        // if($i >=  $m*5 && $i < $m*6) $cont = true;
                        if(!$cont) continue;
                        */
                        
                        /* Array
                        (
                            [first_source] => WOR:769374
                            [acronym] => WOR
                            [taxon_id] => 769374
                        ) */
                        // [taxonID] => urn:lsid:marinespecies.org:taxname:104395
                        
                        /* TPL
                        Array
                        (
                            [first_source] => TPL:kew-2588978
                            [acronym] => TPL
                            [taxon_id] => kew-2588978
                        )
                        Array
                        (
                            [taxonID] => kew-2588978
                            [taxonomicStatus] => accepted
                            [family] => Gesneriaceae
                            [genus] => Sinningia
                            [specificEpithet] => iarae
                            [scientificName] => Sinningia iarae
                            [taxonRank] => species
                            [scientificNameAuthorship] => Chautems
                            [nameAccordingTo] => WCSP (in review)
                            [scientificNameID] => urn:lsid:ipni.org:names:302524-2
                        )
                        */
                        //if-then used but not needed so far...
                        if($acronym == "WOR") $taxon_id = str_ireplace("urn:lsid:marinespecies.org:taxname:", "", $rek['taxonID']);
                        else                  $taxon_id = $rek['taxonID'];
                        
                        $first = array("first_source" => "$acronym:".$taxon_id, "acronym" => $acronym, "taxon_id" => $taxon_id);
                        print_r($first);
                        
                        //try to retrieve
                        if($arr = self::retrieve_cache($first))
                        {
                            echo("\n $acronym RETRIEVED cached json\n");
                            /* worked OK if you want to update resource files
                            echo("\n $acronym will delete since I will use a new version of WoRMS2EoL.zip dated 16-Aug-2017 \n");
                            self::delete_retrieve_cache($first); //utility to use if I want to use new/updated resource files versions from google sheet
                            */
                            // exit;
                        }
                        else
                        {
                            print_r($rek);
                            //start write
                            self::write_cache(json_encode($rek), $first);
                            echo("\n $acronym SAVED cached json\n");
                            // exit;
                        }
                        echo "\n-------------------------------\n";
                    }
                    
                }
            }
        }
        exit("\n-end utility-\n");
    }
    
    
    private function initialize()
    {
        require_library('connectors/FreeDataAPI');
        $func = new FreeDataAPI();
        $func->create_folder_if_does_not_exist($this->folder);
        return $func;
    }
    
    function start() // total rows from smasher file 2,700,000+
    {
        $func = self::initialize();
        /* self::integrity_check(); */ //works OK, will use it if there is a new batch of resource files
        // $excluded_acronyms = array('WOR', 'gbif', 'ictv', 'TPL'); //gbif
        // $included_acronyms = array('WOR'); //gbif TPL //debug only when caching
        // $included_acronyms = array('APH');
        $included_acronyms = array('TPL');
        // $included_acronyms = array('AMP');
        
        $smasher_file = self::adjust_filename($this->params["smasher"]["url"]);
        $i = 0; $m = 466666; //466666; 280000
        foreach(new FileIterator($smasher_file) as $line => $row) {
            $i++;
            if(($i % 100000) == 0) echo " $i";
            
            if($i == 1) $fields = explode("\t", $row);
            else {
                $rec = array(); //just to be sure
                $rec = explode("\t", $row);
                $k = -1;
                $rek = array();
                foreach($fields as $field) {
                    $k++;
                    if($val = @$rec[$k]) $rek[$field] = $val;
                }
                if($rek)
                {
                    /* breakdown when caching:
                    $cont = false;
                    if($i >=  1    && $i < $m) $cont = true;
                    // if($i >=  $m   && $i < $m*2) $cont = true;
                    // if($i >=  $m*2 && $i < $m*3) $cont = true;
                    // if($i >=  $m*3 && $i < $m*4) $cont = true;
                    // if($i >=  $m*4 && $i < $m*5) $cont = true;
                    // if($i >=  $m*5 && $i < $m*6) $cont = true;
                    // if($i >=  $m*6 && $i < $m*7) $cont = true;
                    // if($i >=  $m*7 && $i < $m*8) $cont = true;
                    // if($i >=  $m*8 && $i < $m*9) $cont = true;
                    // if($i >=  $m*9 && $i < $m*10) $cont = true;
                    if(!$cont) continue;
                    */
                    
                    echo "\nsmasher record: ----------------------------";
                    print_r($rek); //debug only
                    $first_source = self::get_first_source($rek['source']);
                    print_r($first_source);
                    
                    /* normal operation
                    self::process_record($rek, $first_source, $func);
                    */
                    
                    /*
                    if(in_array($first_source['acronym'], $excluded_acronyms)) continue;
                    self::process_record($rek, $first_source, $func);
                    */
                    
                    // /*
                    if(in_array($first_source['acronym'], $included_acronyms)) self::process_record($rek, $first_source, $func);
                    else continue;
                    // */
                    
                }
            }
            // if($i >= 20) break; //debug only
        }
        $func->last_part($this->folder); //this is a folder within CONTENT_RESOURCE_LOCAL_PATH
    }

    private function get_scientificName($rek, $first_source, $fetched)
    {   /* Here is how the different resources should be treated:
        1. Fetch scientificName from first source without modifications: AMP,EET,gbif,ictv,IOC,lhw,ODO,ONY,PPG,SPI,TER,WOR
        2. Fetch scientificName from first source, add a blank space and then the contents of the scientificNameAuthorship field: 
        APH,BLA,COL,COR,DER,EMB,GRY,LYG,MAN,MNT,ORTH,PHA,PLE,PSO,TPL(except genus and family ranks, see below),trunk,ZOR
        */
        $opt[1] = array("AMP","EET","gbif","ictv","IOC","lhw","ODO","ONY","PPG","SPI","TER","WOR");
        $opt[2] = array("APH","BLA","COL","COR","DER","EMB","GRY","LYG","MAN","MNT","ORTH","PHA","PLE","PSO","TPL","trunk","ZOR"); // TPL (except genus and family ranks, see below)

        $sciname = @$fetched['fetched']['scientificName'];
        $sciname = str_ireplace("†", "", $sciname);

        if(in_array($first_source['acronym'], $opt[1])) $final['scientificName'] = $sciname;
        elseif(in_array($first_source['acronym'], $opt[2]))
        {
            /*if TPL:
                scientificName: Smasher output verbatim
                canonicalName,scientificNameAuthorship,scientificNameID,taxonRemarks,namePublishedIn,furtherInformationURL: nothing
                datasetID: TPL
            */
            if($first_source['acronym'] == "TPL" && in_array($rek['taxonRank'], array("genus", "family")))
            {
                $final['scientificName'] = $rek['scientificName'];
                $final['canonicalName'] = '';
                $final['scientificNameAuthorship'] = '';
                $final['scientificNameID'] = '';
                $final['taxonRemarks'] = '';
                $final['namePublishedIn'] = '';
                $final['furtherInformationURL'] = '';
                $final['datasetID'] = 'TPL';
                $final['EOLid'] = '';
            }
            else
            {
                $final['scientificName'] = $sciname." ".$fetched['fetched']['scientificNameAuthorship'];
            }
        }
        else exit("\ninvestigate: [".$first_source['acronym']."] not in the list\n");
        return $final;
    }
    private function process_record($rek, $first, $func)
    {
        $rec = array();
        /* We want to update the values for the scientificName column and add the following columns to the Smasher output file:
        http://rs.gbif.org/terms/1.0/canonicalName
        http://rs.tdwg.org/dwc/terms/scientificNameAuthorship
        http://rs.tdwg.org/dwc/terms/scientificNameID
        http://rs.tdwg.org/dwc/terms/taxonRemarks
        http://rs.tdwg.org/dwc/terms/namePublishedIn
        http://rs.tdwg.org/ac/terms/furtherInformationURL
        http://rs.tdwg.org/dwc/terms/datasetID
        http://eol.org/schema/EOLid - this is a made-up uri for now */
        
        if($first['acronym'] == "TPL" && in_array($rek['taxonRank'], array("genus", "family"))) $d['fetched'] = array();
        else $d['fetched'] = self::fetch_record($first);
        print_r($d);
        
        $rec = self::get_scientificName($rek, $first, $d);
        $rec['canonicalName'] = @$d['fetched']['canonicalName']; //only gbif has it
        
        $arr = array("APH","BLA","COL","COR","DER","EMB","gbif","GRY","LYG","MAN","MNT","ORTH","PHA","PLE","PSO","TPL","trunk","ZOR");
        if(in_array($first['acronym'], $arr)) $rec['scientificNameAuthorship'] = @$d['fetched']['scientificNameAuthorship'];
        
        $arr = array("APH","BLA","COL","COR","DER","EMB","GRY","LYG","MAN","MNT","ORTH","PHA","PLE","PSO","TPL","ZOR");
        if(in_array($first['acronym'], $arr))   $rec['scientificNameID'] = @$d['fetched']['scientificNameID'];
        elseif($first['acronym'] == "WOR")      $rec['scientificNameID'] = @$d['fetched']['taxonID'];
        else                                    $rec['scientificNameID'] = '';
        
        $arr = array("APH","BLA","COL","COR","DER","EMB","gbif","GRY","IOC","LYG","MAN","MNT","ORTH","PHA","PLE","PSO","WOR","ZOR");
        if(in_array($first['acronym'], $arr))   $rec['taxonRemarks'] = @$d['fetched']['taxonRemarks'];
        
        $arr = array("APH","BLA","COL","COR","DER","EMB","GRY","LYG","MAN","MNT","ORTH","PHA","PLE","PSO","TPL","WOR","ZOR");
        if(in_array($first['acronym'], $arr))   $rec['namePublishedIn'] = @$d['fetched']['namePublishedIn'];
        
        $arr = array("AMP","EET","lhw","ODO","ONY","PPG","SPI","TER","trunk");
        if(in_array($first['acronym'], $arr))   $rec['furtherInformationURL'] = ''; //deliberately blank
        elseif($first['acronym'] == "WOR")      $rec['furtherInformationURL'] = @$d['fetched']['furtherInformationURL'];
        elseif(in_array($first['acronym'], array("ictv","IOC"))) $rec['furtherInformationURL'] = @$d['fetched']['source']; //in the future: $d['fetched']['furtherInformationURL']
        elseif($fiu = @$this->fiu[$first['acronym']])            $rec['furtherInformationURL'] = $fiu . $first['taxon_id'];

        if($first['acronym'] == "gbif") $rec['datasetID'] = @$d['fetched']['datasetID'];
        else                            $rec['datasetID'] = $first['acronym'];
        
        
        // print_r($d); exit;
        $rec['EOLid'] = self::get_eol_id($rek, $first, $rec['scientificName']);
        if(!$rec['EOLid']) exit("\nno EOLid\n");
        
        
        print_r($rec);
        // exit("\nstop muna\n");
        echo "\n-------------------------------------------------------------\n";
        
        $rec = array_map('trim', $rec);
        $func->print_header($rec, CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations.txt");
        $val = implode("\t", $rec);
        self::save_to_text_file($val);
    }
    
    function fetch_record($first)
    {   /* Array (  [first_source] => gbif:2058421
                    [acronym] => gbif
                    [taxon_id] => 2058421   )*/
                    
        if($first['acronym'] == "gbif")
        {
            if($arr = self::retrieve_cache($first)) //1st option is the cache from gbif's resource file
            {
                echo("\n gbif retrieved cached json\n");
                return $arr;
            }
            else
            {
                // exit("\nwala pang cache\n");
                if($json = Functions::lookup_with_cache($this->gbif_NameUsage . $first['taxon_id'], $this->download_options))
                {
                    $arr = json_decode($json, true);
                    print_r($arr);
                    return $arr;
                    //exit("\ngbif api\n");
                }
                else exit("\nFrom gbif but not found in API\n");
            }
        }
        elseif($first['acronym'] == "WOR")
        {
            if($arr = self::retrieve_cache($first))
            {
                echo("\n WORMS retrieved cached json\n");
                // exit;
                return $arr;
            }
        }
        else
        {
            if($arr = self::retrieve_cache($first))
            {
                echo("\nREST OF THE RESOURCES $first[acronym] retrieved cached json\n");
                // exit;
                return $arr;
            }
            
        }
        // the rest of the resources below...
        $txtfile = self::adjust_filename($this->params[$first['acronym']]["url"]);
        if(!file_exists($txtfile)) exit("\nfile does not exist: [$txtfile]\n");
        else echo "\nfound:2 [$txtfile] - ".$first['acronym']."\n";
        $i = 0;
        foreach(new FileIterator($txtfile) as $line => $row) {
            $i++;
            if($i == 1)
            {
                $fields = explode("\t", $row);
                if(!in_array("taxonID", $fields)) exit("\nCannot search, no taxonID from resource file.\n");
            }
            else {
                $rec = array(); //just to be sure
                $rec = explode("\t", $row);
                $k = -1;
                $rek = array();
                foreach($fields as $field) {
                    $k++;
                    if($val = @$rec[$k]) $rek[$field] = $val;
                }

                //========================================
                if($first['acronym'] == 'WOR')
                {
                    // urn:lsid:marinespecies.org:taxname:325577
                    if("urn:lsid:marinespecies.org:taxname:".trim($first['taxon_id']) == trim(@$rek['taxonID']))
                    {
                        print_r($rek);
                        self::write_cache(json_encode($rek), $first);
                        echo("\nWOR saved cache\n");
                        // exit;
                        return $rek;
                    }
                }
                else //the rest of the resources
                {
                    if($first['taxon_id'] == @$rek['taxonID'])
                    {
                        // if($first['acronym'] == 'gbif') {print_r($rek); exit("\ngbif test\n");} //testing...

                        print_r($rek);
                        self::write_cache(json_encode($rek), $first);
                        echo("\nREST of the resources...$first[acronym]... saved cache\n");
                        // exit;
                        return $rek;
                    }
                }
                //========================================
                
                
            }
        }

        // if($first['acronym'] == 'WOR') exit("\nfrom worms but not found in resource file [" . $first['taxon_id'] . "]\n"); //seen this
        
        return false;
    }
    
    
    private function adjust_filename($url)
    {   // /Library/WebServer/Documents/eol_php_code/
        // http://localhost/cp/dynamic_hierarchy/smasher/EOLDynamicHierarchyDraftAug2017/dwh_taxa.txt
        $url = str_ireplace("http://localhost", "", $url);
        return str_replace("eol_php_code/", "", DOC_ROOT).$url;
    }
    private function get_first_source($source)
    {
        $a = explode(",", $source);
        $f['first_source'] = trim($a[0]);
        $a = explode(":", $f['first_source']);
        $f['acronym'] = trim($a[0]);

        // $f['taxon_id'] = trim($a[1]); //orig
        $a[0] = null;
        $a = array_filter($a); //remove null arrays
        $f['taxon_id'] = implode(":", $a); //orig

        return $f;
    }
    private function integrity_check()
    {
        $acronyms = array_keys($this->params);
        print_r($acronyms);
        foreach($acronyms as $acronym)
        {
            $txtfile = self::adjust_filename($this->params[$acronym]["url"]);
            if(!file_exists($txtfile)) exit("\nfile does not exist: [$txtfile]\n");
            else echo "\nfound:3 [$txtfile]";
            $i = 0;
            foreach(new FileIterator($txtfile) as $line => $row) {
                $i++;
                if($i == 1)
                {
                    $fields = explode("\t", $row);
                    if(!in_array("taxonID", $fields)) exit("\nCannot search, no taxonID from resource file [$txtfile].\n");
                    break;
                }
            }
        }
        exit("\n-end integrity check-\n");
    }

    private function write_cache($json, $first, $main_path = false)
    {   /*Array
        (
            [first_source] => WOR:160762
            [acronym] => WOR
            [taxon_id] => 160762
        )*/
        
        if(!$main_path) $main_path = $this->smasher_cache;
        $md5 = md5($first['first_source']);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$md5.json";
        
        $WRITE = Functions::file_open($filename, "w");
        fwrite($WRITE, $json . "\n");
        fclose($WRITE);
    }

    private function write_cache_EOLid($EOLid, $first, $scientificName)
    {
        $main_path = $this->smasher_cache;
        $md5 = md5($first['first_source']."-".$scientificName);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$md5.eol";
        $WRITE = Functions::file_open($filename, "w");
        fwrite($WRITE, $EOLid);
        fclose($WRITE);
    }
    
    private function retrieve_cache_EOLid($first, $scientificName)
    {
        $main_path = $this->smasher_cache;
        $md5 = md5($first['first_source']."-".$scientificName);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $filename = $main_path . "$cache1/$cache2/$md5.eol";
        
        if(file_exists($filename))
        {
            $eolid = file_get_contents($filename);
            return $eolid;
        }
        return false;
    }
    private function retrieve_cache($first, $main_path = false)
    {
        if(!$main_path) $main_path = $this->smasher_cache;
        $md5 = md5($first['first_source']);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $filename = $main_path . "$cache1/$cache2/$md5.json";
        if(file_exists($filename))
        {
            $json = file_get_contents($filename);
            $arr = json_decode($json, true);
            print_r($arr);
            if($arr) return $arr;
        }
        return false;
    }
    private function delete_retrieve_cache($first, $main_path = false)
    {
        if(!$main_path) $main_path = $this->smasher_cache;
        $md5 = md5($first['first_source']);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $filename = $main_path . "$cache1/$cache2/$md5.json";
        if(file_exists($filename))
        {
            if(unlink($filename)) echo " - deleted OK";
            else echo " - not deleted for some reason";
        }
    }

    private function save_to_text_file($row)
    {
        if($row)
        {
            $WRITE = Functions::file_open($this->destination[$this->folder], "a");
            fwrite($WRITE, $row . "\n");
            fclose($WRITE);
        }
    }

    private function get_recs_from_EHE($first)
    {
        $recs = array(); //recs to return
        
        $txtfile = self::adjust_filename($this->params["EHE"]["url"]); //orig
        $txtfile = $this->path_EHE_tsv_files.substr($first['scientificName'],0,2).".tsv";
        
        if(!file_exists($txtfile)) exit("\nfile does not exist: [$txtfile]\n");
        else echo "\nfound:4 [$txtfile]";
        $i = 0; $m = 466666; 
        $fields = array("EOLid", "richness_score", "scientificName", "he_id", "source_hierarchy");
        echo "\nsearching...$first[scientificName]..in EHE..";
        foreach(new FileIterator($txtfile) as $line => $row) {
            $i++; $rec = array(); //just to be sure
            $rec = explode("\t", $row);
            $k = -1; $rek = array();
            foreach($fields as $field) {
                $k++;
                $rek[$field] = @$rec[$k];
            }
            if($rek) {
                $rek_scientificName = self::remove_quotes($rek['scientificName']);
                $rek_source_hierarchy = self::remove_quotes($rek['source_hierarchy']);

                /*
                //eli's 1st try -> not so good
                //gets records from EHE with BEGINS_WITH scientificName
                if($first['scientificName'] == substr($rek_scientificName,0,strlen($first['scientificName']))) {
                    $recs[] = $rek;
                }
                */
                
                //eli's 2nd try
                $canonical_sciname = Functions::canonical_form($first['scientificName']);
                if($canonical_sciname == substr($rek_scientificName,0,strlen($canonical_sciname))) {
                    $recs[] = $rek;
                }
                
                
                
            }
        }
        return $recs;
    }

    function utility3() //creating local cache of EHE by 2-letter TSV files
    {
        exit("\n-disabled-\n"); //ran only once... worked OK
        $txtfile = self::adjust_filename($this->params["EHE"]["url"]);
        if(!file_exists($txtfile)) exit("\nfile does not exist: [$txtfile]\n");
        else echo "\nfound:5 [$txtfile]";
        $i = 0; $m = 466666; 
        $fields = array("EOLid", "richness_score", "scientificName", "he_id", "source_hierarchy");
        foreach(new FileIterator($txtfile) as $line => $row) {
            $i++;
            if(true)
            {
                $rec = array(); //just to be sure
                $rec = explode("\t", $row);
                $k = -1;
                $rek = array();
                foreach($fields as $field) {
                    $k++;
                    $rek[$field] = @$rec[$k];
                }
                if($rek)
                {
                    $rek['scientificName'] = self::remove_quotes($rek['scientificName']);
                    $rek['source_hierarchy'] = self::remove_quotes($rek['source_hierarchy']);
                    $row = implode("\t", $rek);
                    // print_r($rek);
                    $substr = substr($rek['scientificName'],0,2);
                    echo " - [$substr]";
                    self::append_to_EHE_text_file($this->path_EHE_tsv_files.$substr.".tsv", $row);
                }
            }
            // if($i >= 1000) break; //debug only
        }
        exit("\n-end utility3-\n");
    }
    private function append_to_EHE_text_file($filename, $row)
    {
        $WRITE = Functions::file_open($filename, "a");
        fwrite($WRITE, $row . "\n");
        fclose($WRITE);
    }

    private function remove_quotes($str)
    {
        return str_replace('"', "", $str);
    }

    /*
    function extract_file($zip_path)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($zip_path, "interactions.tsv", array('timeout' => 172800, 'expire_seconds' => 2592000)); //expires in 1 month
        return $paths;
    }
    */
    /*
    Array
    (
        [taxonID] => -1662713
        [acceptedNameUsageID] => -1662713
        [parentNameUsageID] => -1411041
        [scientificName] => Aaaba
        [taxonRank] => genus
        [source] => gbif:3260806
        [taxonomicStatus] => accepted
    )
    Array
    (
        [first_source] => gbif:3260806
        [acronym] => gbif
        [taxon_id] => 3260806
    )
    found: [/Library/WebServer/Documents//cp/dynamic_hierarchy/smasher/backbone-current/Taxon.tsv]Array
    (
        [taxonID] => 3260806
        [datasetID] => 0938172b-2086-439c-a1dd-c21cb0109ed5
        [parentNameUsageID] => 3789
        [scientificName] => Aaaba Bellamy, 2002
        [scientificNameAuthorship] => Bellamy, 2002
        [canonicalName] => Aaaba
        [genericName] => Aaaba
        [taxonRank] => genus
        [taxonomicStatus] => accepted
        [kingdom] => Animalia
        [phylum] => Arthropoda
        [class] => Insecta
        [order] => Coleoptera
        [family] => Buprestidae
        [genus] => Aaaba
    )
    gbif test
    */

}
?>