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
        
        //WORMS services
        $this->smasher_cache = "/Volumes/Thunderbolt4/eol_cache_smasher/";
        
        //TRAM-581
        $this->url['api_search'] = "http://eol.org/api/search/1.0.json?page=1&exact=true&cache_ttl=&q=";
        $this->download_options2 = array("resource_id" => "trait_request", "download_wait_time" => 2000000, "timeout" => 3600, "download_attempts" => 1);
        $this->download_options2['expire_seconds'] = false;
        
        
        $this->debug = array();
    }

    /*
    private function initialize()
    {
        require_library('connectors/FreeDataAPI');
        $func = new FreeDataAPI();
        $func->create_folder_if_does_not_exist($this->folder);
        return $func;
    }
    */
    function search_ok($url)
    {
        if($json = Functions::lookup_with_cache($url, $this->download_options2))
        {
            $obj = json_decode($json);
            if($rec = @$obj->results[0])
            {
                $taxon_rec = array();
                // $taxon_rec['sciname'] = $sciname;
                $taxon_rec['EOLid'] = $rec->id;
                print_r($taxon_rec);
                return $taxon_rec;
            }
        }
        else return false;
    }
    function utility2()
    {
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
                    // /* breakdown when caching:
                    $cont = false;
                    // if($i >=  1    && $i < $m) $cont = true;
                    // if($i >=  $m   && $i < $m*2) $cont = true;
                    // if($i >=  $m*2 && $i < $m*3) $cont = true;
                    // if($i >=  $m*3 && $i < $m*4) $cont = true;
                    // if($i >=  $m*4 && $i < $m*5) $cont = true;
                    if($i >=  $m*5 && $i < $m*6) $cont = true;
                    // if($i >=  $m*6 && $i < $m*7) $cont = true;
                    // if($i >=  $m*7 && $i < $m*8) $cont = true;
                    // if($i >=  $m*8 && $i < $m*9) $cont = true;
                    // if($i >=  $m*9 && $i < $m*10) $cont = true;
                    if(!$cont) continue;
                    // */
                    
                    // /*
                    print_r($rek); //debug only
                    $sciname = $rek['scientificName'];
                    $url1 = $this->url['api_search'].$sciname;
                    if($taxon_rec = self::search_ok($url1)) {}
                    else
                    {
                        $url2 = $this->url['api_search'].Functions::canonical_form($sciname);
                        if($taxon_rec = self::search_ok($url2)) {}
                        else
                        {
                            $url1 = str_ireplace("exact=true","exact=false",$url1);
                            if($taxon_rec = self::search_ok($url1)) {}
                            else
                            {
                                $url2 = str_ireplace("exact=true","exact=false",$url2);
                                if($taxon_rec = self::search_ok($url2)) {}
                                else echo("\ntalagang wala lang...[$sciname]\n");
                            }
                        }
                    }
                    // exit("\n-end utility2-\n");

                    // if(in_array($first_source['acronym'], $excluded_acronyms)) continue;
                    // self::process_record($rek, $first_source);
                    
                    // if(in_array($first_source['acronym'], $included_acronyms)) self::process_record($rek, $first_source);
                    // else continue;
                    // */
                }
            }
        }
    }
    
    function utility()
    {   /*
    $p["BLA"] = array("desc" => "Cockroach Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-blattodea-v8.8/taxon.txt");
    $p["COL"] = array("desc" => "Coleorrhyncha Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-coleorrhyncha-v9.6/taxon.txt");
    $p["COR"] = array("desc" => "Coreoidea Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-coreoidea-v8.6/taxon.txt");
    $p["EET"] = array("desc" => "Earthworms", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/eolearthwormpatch/taxa.txt");
    $p["GRY"] = array("desc" => "Grylloblattodea Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-grylloblattodea-v1.4/taxon.txt");
    $p["IOC"] = array("desc" => "IOC World Bird List with higherClassification", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/ioc-birdlist-with-higherclassification/taxon.tab");
    $p["LYG"] = array("desc" => "Lygaeoidea Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-lygaeoidea-v1.0/taxon.txt");
    $p["ORTH"] = array("desc" => "Orthoptera Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-orthoptera-v12.6/taxon.txt");
    $p["PPG"] = array("desc" => "Pteridophyte Phylogeny Group Classification", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/ppg12016/ferntaxa.txt");
    $p["PSO"] = array("desc" => "Psocodea Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-psocodea-v8.6/taxon.txt");
    $p["ZOR"] = array("desc" => "Zoraptera Species File", "url" => "http://localhost/cp/dynamic_hierarchy/smasher/dwca-zoraptera-v1.4/taxon.txt");
        */
        // $acronyms = array_keys($this->params);
        $acronyms = array('gbif'); //WOR TPL
        print_r($acronyms);
        foreach($acronyms as $acronym)
        {
            $txtfile = self::adjust_filename($this->params[$acronym]["url"]);
            if(!file_exists($txtfile)) exit("\nfile does not exist: [$txtfile]\n");
            else echo "\nfound: [$txtfile]";
            $i = 0;
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
                        if($json = self::retrieve_cache($first))
                        {
                            $arr = json_decode($json, true);
                            print_r($arr);
                            echo("\n $acronym retrieved cached json\n");
                            // exit;
                        }
                        else
                        {
                            print_r($rek);
                            //start write
                            self::write_cache(json_encode($rek), $first);
                            echo("\n $acronym saved cached json\n");
                            // exit;
                        }
                        echo "\n-------------------------------\n";
                    }
                    
                }
            }
        }
        exit("\n-end utility-\n");
    }
    function start() // total rows from smasher file 2,700,000+
    {
        /* self::integrity_check(); */ //works OK, will use it if there is a new batch of resource files
        // $excluded_acronyms = array('WOR', 'gbif', 'ictv'); //gbif
        // $included_acronyms = array('WOR'); //gbif //debug only when caching
        $included_acronyms = array('TPL');
        
        
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
                    // /* breakdown when caching:
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
                    // */
                    
                    // /*
                    print_r($rek); //debug only
                    $first_source = self::get_first_source($rek['source']);
                    print_r($first_source);

                    // if(in_array($first_source['acronym'], $excluded_acronyms)) continue;
                    // self::process_record($rek, $first_source);
                    
                    if(in_array($first_source['acronym'], $included_acronyms)) self::process_record($rek, $first_source);
                    else continue;
                    // */
                    
                }
            }
        }
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
    private function process_record($rek, $first_source)
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
        
        if($first_source['acronym'] == "TPL" && in_array($rek['taxonRank'], array("genus", "family"))) $d['fetched'] = array();
        else $d['fetched'] = self::fetch_record($first_source);
        print_r($d);
        
        $rec = self::get_scientificName($rek, $first_source, $d);
        print_r($rec);
        echo "\n-------------------------------------------------------------\n";
        
        // exit("\n")
        // $rec = array_map('trim', $rec);
        // $func->print_header($rec, CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations.txt");
        // $val = implode("\t", $rec);
        // self::save_to_text_file($val);
    }
    
    function fetch_record($first)
    {   /* Array (
            [first_source] => gbif:2058421
            [acronym] => gbif
            [taxon_id] => 2058421
        )*/
        
        if($first['acronym'] == "gbif")
        {
            if($json = Functions::lookup_with_cache($this->gbif_NameUsage . $first['taxon_id'], $this->download_options))
            {
                $arr = json_decode($json, true);
                print_r($arr);
                return $arr;
                //exit("\ngbif api\n");
            }
            else exit("\nFrom gbif but not found in API\n");
        }
        elseif($first['acronym'] == "WOR")
        {
            if($json = self::retrieve_cache($first))
            {
                $arr = json_decode($json, true);
                print_r($arr);
                echo("\n WORMS retrieved cached json\n");
                return $arr;
            }
        }
        else
        {
            if($json = self::retrieve_cache($first))
            {
                $arr = json_decode($json, true);
                print_r($arr);
                echo("\nREST OF THE RESOURCES retrieved cached json\n");
                return $arr;
            }
            
        }
        // the rest of the resources below...
        $txtfile = self::adjust_filename($this->params[$first['acronym']]["url"]);
        if(!file_exists($txtfile)) exit("\nfile does not exist: [$txtfile]\n");
        else echo "\nfound: [$txtfile]\n";
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
                        echo("\nREST of the resources... saved cache\n");
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
        $f['taxon_id'] = trim($a[1]);
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
            else echo "\nfound: [$txtfile]";
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
    private function retrieve_cache($first, $main_path = false)
    {
        if(!$main_path) $main_path = $this->smasher_cache;
        $md5 = md5($first['first_source']);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $filename = $main_path . "$cache1/$cache2/$md5.json";
        if(file_exists($filename)) return file_get_contents($filename);
        else return false;
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