<?php
namespace php_active_record;
/* connector: [cypher.php]
---------------------------------------------------------------------------
Cypher info:
https://github.com/EOL/eol_website/blob/master/doc/api-access.md
check if api using cypher is available:
- login to eol.org
- paste in browser: https://eol.org/service/cypher?query=MATCH%20(n:Trait)%20RETURN%20n%20LIMIT%201;
*/
class CypherQueryAPI
{
    function __construct($folder = null, $query = null)
    {
        $this->download_options = array(
            'resource_id'        => 'cypher_query',  //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 750000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        $this->expire_seconds_4cypher_query = false; //60*60*24; //1 day expires. Default false, other value on demand.
        $this->expire_seconds_4cypher_query = $this->download_options['expire_seconds'];

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->download_options['cache_path'] = "/Volumes/Crucial_2TB/eol_cache/";      //used in Functions.php for all general cache
        $this->main_path = $this->download_options['cache_path'].$this->download_options['resource_id']."/";
        if(!is_dir($this->main_path)) mkdir($this->main_path);

        /* not used atm.
        // for creating archives
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */

        $this->basename = "cypher_".date('Y_m_d_His');
        $this->per_page = 500; //100;                       //per page with DISTINCT
        $this->per_page_2 = 1000;                           //per page without DISTINCT
        $this->debug = array();
    }
    private function initialize_path($input)
    {
        $this->report_path = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/";
        if(!is_dir($this->report_path)) mkdir($this->report_path);
        // exit("\n[".json_encode($input)."]\n");
        // exit("\n".strlen(json_encode($input))."\n");
        $tmp = md5(json_encode($input));
        $this->report_path .= "$tmp/";
        if(!is_dir($this->report_path)) mkdir($this->report_path);
    }
    function query_trait_db($input)
    {        
        if($input['params']['source'] == "https://doi.org/10.1007/s13127-017-0350-6") $this->with_DISTINCT_YN = false;

        print_r($input); //exit;
        self::initialize_path($input);
        // /* report filename
        /* orig working
        $tmp = md5(json_encode($input));
        $this->tsv_file = $this->report_path."/".$tmp."_".$input["trait kind"].".tsv";
        */
        $this->tsv_file = $this->report_path."/".$input["trait kind"]."_qry.tsv";
        // */

        if($val = @$input["per_page"]) $this->per_page = $val;
        if(isset($input["per_page"])) unset($input["per_page"]); // unset so that initial queries made won't get wasted. Where there is no $input["per_page"] yet.

        $skip = 0;
        while(true) {
            $input['skip'] = $skip;
            $input['limit'] = $this->per_page;
            $input = self::query_maker($input);
            $filename = self::generate_path_filename($input); // exit("\n[$filename\n");
            $json = self::retrieve_trait_data($input, $filename);
            $obj = json_decode($json); //print_r($obj); return; //exit("\nstop query muna\n");
            if($total = count(@$obj->data)) {
                // print_r($obj); exit; //good debug
                self::write_tsv($obj, $filename, $skip);
            }
            print("\n No. of rows: ".$total." | $skip | row#: ".@$this->real_row."\n");
            $skip += $this->per_page;
            if($total < $this->per_page) break;
            // break; //debug only
            // if($skip == 2000) break; //debug only
        }
        print("\n-----Processing ends-----\n");
        // print_r($input); //good debug
        print("\nReport file: ".$this->tsv_file."\n");
    }
    private function query_maker($input)
    {
        $skip = $input['skip'];
        $limit = $input['limit'];
        if($input['type'] == "wikidata_base_qry_citation") {
            $citation = urlencode($input['params']['citation']);
            
            /* old
            $qry = 'MATCH (t:Trait)<-[:trait|inferred_trait]-(p:Page),
            (t)-[:predicate]->(pred:Term)
            WHERE t.citation = "'.$citation.'"
            OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
            OPTIONAL MATCH (t)-[:units_term]->(units:Term)
            OPTIONAL MATCH (t)-[:lifestage_term]->(stage:Term)
            OPTIONAL MATCH (t)-[:sex_term]->(sex:Term)
            OPTIONAL MATCH (t)-[:statistical_method_term]->(stat:Term)
            OPTIONAL MATCH (t)-[:metadata]->(ref:MetaData)-[:predicate]->(:Term {name:"reference"})
            RETURN DISTINCT p.canonical, p.page_id, pred.name, stage.name, sex.name, stat.name, obj.name, t.measurement, units.name, t.source, t.citation, ref.literal
            ORDER BY p.canonical 
            SKIP '.$skip.' LIMIT '.$limit;
            */

            // /* new
            if(    $input['trait kind'] == 'trait') {
                $qry = 'MATCH (t:Trait)<-[:trait]-(p:Page), ';
            $qry .= '(t)-[:predicate]->(pred:Term)
            WHERE t.citation = "'.$citation.'"
            OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
            OPTIONAL MATCH (t)-[:units_term]->(units:Term)
            OPTIONAL MATCH (t)-[:lifestage_term]->(stage:Term)
            OPTIONAL MATCH (t)-[:sex_term]->(sex:Term)
            OPTIONAL MATCH (t)-[:statistical_method_term]->(stat:Term)
            OPTIONAL MATCH (t)-[:metadata]->(ref:MetaData)-[:predicate]->(:Term {name:"reference"})
            RETURN DISTINCT p.canonical, p.page_id, pred.name, stage.name, sex.name, stat.name, obj.name, t.measurement, units.name, t.source, t.citation, ref.literal
            ORDER BY p.canonical 
            SKIP '.$skip.' LIMIT '.$limit;    
            }
            elseif($input['trait kind'] == 'inferred_trait') {
                $qry = 'MATCH (t:Trait)<-[:inferred_trait]-(p:Page), ';
            $qry .= '(t)-[:predicate]->(pred:Term)
            WHERE t.citation = "'.$citation.'"
            OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
            OPTIONAL MATCH (t)-[:units_term]->(units:Term)
            OPTIONAL MATCH (t)-[:lifestage_term]->(stage:Term)
            OPTIONAL MATCH (t)-[:sex_term]->(sex:Term)
            OPTIONAL MATCH (t)-[:statistical_method_term]->(stat:Term)
            OPTIONAL MATCH (t)-[:metadata]->(ref:MetaData)-[:predicate]->(:Term {name:"reference"})
            RETURN DISTINCT p.canonical, p.page_id, t.eol_pk, p.rank, pred.name, stage.name, sex.name, stat.name, obj.name, t.measurement, units.name, t.source, t.citation, ref.literal
            ORDER BY p.canonical 
            SKIP '.$skip.' LIMIT '.$limit;
            } //t.eol_pk, p.rank,
            // */
        }
        elseif($input['type'] == "wikidata_base_qry_source") { //exit("\ngoes here...\n");
            // /* new block
            if($input['params']['source'] == "https://doi.org/10.1007/s13127-017-0350-6") $obj_name_uri = "obj.name, obj.uri,"; //pnas
            else                                                                          $obj_name_uri = "obj.name,"; //orig
            // */
    
            $source = urlencode($input['params']['source']);
            // $qry = 'MATCH (t:Trait)<-[:trait|inferred_trait]-(p:Page),
            if(    $input['trait kind'] == 'trait') { //exit("\ngoes here2...\n");
                $qry = 'MATCH (t:Trait)<-[:trait]-(p:Page), ';
            // /* ORIG CACHED
            $qry .= '(t)-[:predicate]->(pred:Term)
            WHERE t.source = "'.$source.'"
            OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
            OPTIONAL MATCH (t)-[:units_term]->(units:Term)
            OPTIONAL MATCH (t)-[:lifestage_term]->(stage:Term)
            OPTIONAL MATCH (t)-[:sex_term]->(sex:Term)
            OPTIONAL MATCH (t)-[:statistical_method_term]->(stat:Term)
            OPTIONAL MATCH (t)-[:metadata]->(ref:MetaData)-[:predicate]->(:Term {name:"reference"})
            RETURN DISTINCT p.canonical, p.page_id, pred.name, stage.name, sex.name, stat.name, '.$obj_name_uri.' t.measurement, units.name, t.source, t.citation, ref.literal
            ORDER BY p.canonical 
            SKIP '.$skip.' LIMIT '.$limit;
            // */
            }
            elseif($input['trait kind'] == 'inferred_trait') {
                $qry = 'MATCH (t:Trait)<-[:inferred_trait]-(p:Page), ';
            // /* ORIG CACHED - recently added t.eol_pk, p.rank

            if($this->with_DISTINCT_YN) {
            $qry .= '(t)-[:predicate]->(pred:Term)
            WHERE t.source = "'.$source.'"
            OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
            OPTIONAL MATCH (t)-[:units_term]->(units:Term)
            OPTIONAL MATCH (t)-[:lifestage_term]->(stage:Term)
            OPTIONAL MATCH (t)-[:sex_term]->(sex:Term)
            OPTIONAL MATCH (t)-[:statistical_method_term]->(stat:Term)
            OPTIONAL MATCH (t)-[:metadata]->(ref:MetaData)-[:predicate]->(:Term {name:"reference"})
            RETURN DISTINCT p.canonical, p.page_id, t.eol_pk, p.rank, pred.name, stage.name, sex.name, stat.name, obj.name, t.measurement, units.name, t.source, t.citation, ref.literal
            ORDER BY p.canonical 
            SKIP '.$skip.' LIMIT '.$limit;
            // */ //t.eol_pk, p.rank,    
            }
            else { //print_r($input); exit("\ngoes here\n"); //good debug
                $qry .= '(t)-[:predicate]->(pred:Term)
                WHERE t.source = "'.$source.'"
                OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
                OPTIONAL MATCH (t)-[:units_term]->(units:Term)
                OPTIONAL MATCH (t)-[:lifestage_term]->(stage:Term)
                OPTIONAL MATCH (t)-[:sex_term]->(sex:Term)
                OPTIONAL MATCH (t)-[:statistical_method_term]->(stat:Term)
                OPTIONAL MATCH (t)-[:metadata]->(ref:MetaData)-[:predicate]->(:Term {name:"reference"})
                RETURN p.canonical, p.page_id, t.eol_pk, p.rank, pred.name, stage.name, sex.name, stat.name, '.$obj_name_uri.' t.measurement, units.name, t.source, t.citation, ref.literal
                ORDER BY p.canonical 
                SKIP '.$skip.' LIMIT '.$limit; // no DISTINCT
                // */ //t.eol_pk, p.rank,    
            }

            }

        }
        elseif($input['type'] == "traits_stop_at") {
            $qry = 'MATCH (t)-[:metadata]->(stopNode:MetaData)-[:predicate]->(stop:Term {name:"stops at"})
            RETURN DISTINCT t.eol_pk, stop.name SKIP '.$skip.' LIMIT '.$limit;
        }

        elseif($input['type'] == "wikidata_base_qry_resourceID") {
            $this->with_DISTINCT_YN = false;

            $resource_id = urlencode($input['params']['resource_id']);
            if($input['trait kind'] == 'trait') {
                $qry = 'MATCH (t:Trait)<-[:trait]-(p:Page), (t)-[:supplier]->(:Resource {resource_id: '.$resource_id.'}), ';
                $qry .= '(t)-[:predicate]->(pred:Term)
                OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
                OPTIONAL MATCH (t)-[:units_term]->(units:Term)
                OPTIONAL MATCH (t)-[:lifestage_term]->(stage:Term)
                OPTIONAL MATCH (t)-[:sex_term]->(sex:Term)
                OPTIONAL MATCH (t)-[:statistical_method_term]->(stat:Term)
                OPTIONAL MATCH (t)-[:metadata]->(ref:MetaData)-[:predicate]->(:Term {name:"reference"})
                RETURN p.canonical, p.page_id, pred.name, stage.name, sex.name, stat.name, obj.name, obj.uri, t.measurement, units.name, t.source, t.citation, ref.literal
                ORDER BY p.canonical 
                SKIP '.$skip.' LIMIT '.$limit;
            }
            elseif($input['trait kind'] == 'inferred_trait') {
                $qry = 'MATCH (t:Trait)<-[:inferred_trait]-(p:Page), (t)-[:supplier]->(:Resource {resource_id: '.$resource_id.'}), ';
                $qry .= '(t)-[:predicate]->(pred:Term)
                OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
                OPTIONAL MATCH (t)-[:units_term]->(units:Term)
                OPTIONAL MATCH (t)-[:lifestage_term]->(stage:Term)
                OPTIONAL MATCH (t)-[:sex_term]->(sex:Term)
                OPTIONAL MATCH (t)-[:statistical_method_term]->(stat:Term)
                OPTIONAL MATCH (t)-[:metadata]->(ref:MetaData)-[:predicate]->(:Term {name:"reference"})
                RETURN p.canonical, p.page_id, t.eol_pk, p.rank, pred.name, stage.name, sex.name, stat.name, obj.name, obj.uri, t.measurement, units.name, t.source, t.citation, ref.literal
                ORDER BY p.canonical 
                SKIP '.$skip.' LIMIT '.$limit;
            }
        }

        if($input['type'] == "katja_start_stop_nodes") {
            $this->with_DISTINCT_YN = true;
            if($this->with_DISTINCT_YN) {
                $qry = 'MATCH (p:Page)-[:trait]->(t:Trait)-[:metadata]->(MetaData)-[:predicate]->(:Term {uri:"https://eol.org/schema/terms/starts_at"}),
                (t)-[:supplier]->(res:Resource)
                OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
                OPTIONAL MATCH (t)-[:normal_units_term]->(units:Term)
                RETURN DISTINCT p.canonical, p.page_id, t.scientificname, t.predicate, obj.uri, obj.name, t.normal_measurement, units.uri, units.name, 
                t.normal_units,res.resource_id, res.name 
                ORDER BY p.canonical ';
                $qry .= 'SKIP '.$skip.' LIMIT '.$limit;
            }
            else { //print_r($input); exit("\ngoes here\n"); //good debug
                exit("\nnot here...\n");
            }
        }
        elseif($input['type'] == "katja_m1_m2") {
            $this->with_DISTINCT_YN = true;
            if($this->with_DISTINCT_YN) {
                $qry = 'MATCH (t:Trait)-[:metadata]->(m1:MetaData)-[:predicate]->(:Term {uri:"https://eol.org/schema/terms/starts_at"}),
                (t)-[:metadata]->(m2:MetaData)-[:predicate]->(:Term {uri:"https://eol.org/schema/terms/stops_at"})
                RETURN DISTINCT m1.measurement,m2.measurement ';
                $qry .= 'SKIP '.$skip.' LIMIT '.$limit;
            }
            else { //print_r($input); exit("\ngoes here\n"); //good debug
                exit("\nnot here...\n");
            }
        }

        else exit("\nERROR: Undefiend query.\n");
        $input['query'] = $qry;
        return $input;
    }
    private function retrieve_trait_data($input, $filename)
    {   
        // /* a security block that prevents from creating blank cache files
        if(file_exists($filename)) {
            if(filesize($filename) == 0) unlink($filename); //this means this file was left hanging after program is terminated (ctrl+c).
        }
        // */

        if(file_exists($filename)) {
            debug("\nCypher cache already exists. [$filename]\n");
            
            // $this->download_options['expire_seconds'] = 60; //debug only - force assign --- test success
            
            $file_age_in_seconds = time() - filemtime($filename);
            if($file_age_in_seconds < $this->expire_seconds_4cypher_query) return self::retrieve_json($filename); //not yet expired
            if($this->expire_seconds_4cypher_query === false)              return self::retrieve_json($filename); //doesn't expire
            
            debug("\nCache expired. Will run cypher now...\n");
            self::run_cypher_query($input, $filename);
            return self::retrieve_json($filename);
        }
        else {
            debug("\nRun cypher query...\n");
            self::run_cypher_query($input, $filename);
            return self::retrieve_json($filename);
        }
    }
    private function retrieve_json($filename)
    {
        $json = file_get_contents($filename);
        return $json; //json_decode($json, true);
    }
    private function run_cypher_query($input, $filename)
    {   /*
        Array(
            [params] => Array(
                    [citation] => the quick brown fox
                )
            [type] => wikidata_base_qry_citation
        )
        Array(
            [params] => Array(
                    [source] => https://doi.org/10.1111/j.1469-185X.1984.tb00411.x
                )
            [type] => wikidata_base_qry_source
        )*/
        $json = self::run_query($input['query']);
        $WRITE = Functions::file_open($filename, "w");
        fwrite($WRITE, $json); fclose($WRITE);
        debug("\nSaved OK [$filename]\n");
    }
    private function run_query($qry)
    {
        $in_file = DOC_ROOT."/temp/".$this->basename.".in";
        $WRITE = Functions::file_open($in_file, "w");
        fwrite($WRITE, $qry); fclose($WRITE);
        $destination = DOC_ROOT."temp/".$this->basename.".out.json";
        /* worked in eol-archive but may need to add: /bin/cat instead of just 'cat'
        $cmd = 'wget -O '.$destination.' --header "Authorization: JWT `cat '.DOC_ROOT.'temp/api.token`" https://eol.org/service/cypher?query="`cat '.$in_file.'`"';
        */

        /* worked OK in MacStudio terminal
        $cmd = 'wget -O '.$destination.' --header "Authorization: JWT `/bin/cat '.DOC_ROOT.'temp/api.token`" https://eol.org/service/cypher?query="`/bin/cat '.$in_file.'`"';
           worked OK in MacStudio terminal and jenkins
        $cmd = '/opt/homebrew/bin/wget -O '.$destination.' --header "Authorization: JWT `/bin/cat '.DOC_ROOT.'temp/api.token`" https://eol.org/service/cypher?query="`/bin/cat '.$in_file.'`"';
        */
        $cmd = WGET_PATH.' -O '.$destination.' --header "Authorization: JWT `/bin/cat '.DOC_ROOT.'temp/api.token`" https://eol.org/service/cypher?query="`/bin/cat '.$in_file.'`"';
        
        // $cmd .= ' 2>/dev/null'; //this will throw away the output
        $secs = 60*2; //orig 
        $secs = 30; echo "\nSleep $secs secs..."; sleep($secs); echo " Continue...\n"; //delay 2 seconds
        $output = shell_exec($cmd); //$output here is blank since we ended command with '2>/dev/null' --> https://askubuntu.com/questions/350208/what-does-2-dev-null-mean
        // echo "\nTerminal out: [$output]\n"; //good debug
        $json = file_get_contents($destination);
        unlink($in_file);
        unlink($destination);
        // $obj = json_decode($json);
        // return @$obj->data[0][0];
        return $json;
    }
    private function generate_path_filename($input)
    {
        // print_r($input); //exit("\nthis is to be md5 hashed\n"); //good debug
        $main_path = $this->main_path;
        $md5 = md5(json_encode($input));
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$md5.json";
        return $filename;
    }
    private function write_tsv($obj, $filename, $skip)
    {
        // print_r($obj); exit("\nelix\n".$this->with_DISTINCT_YN."\n");
        if($skip == 0) {
            /* working but moved up
            $base = pathinfo($filename, PATHINFO_FILENAME); //e.g. "e54dbf6839f325a6a0d5095e82bc5e70"
            $this->tsv_file = $this->report_path."/".$base.".tsv"; //working but moved up
            */
            $WRITE = Functions::file_open($this->tsv_file, "w");
            fwrite($WRITE, implode("\t", $obj->columns)."\n"); 
        }
        else $WRITE = Functions::file_open($this->tsv_file, "a");
        
        foreach($obj->data as $rec) {
            // /* new block for without DISTINCT --- working
            if(isset($this->with_DISTINCT_YN)) { //bec this func is being used by others e.g. get_traits_stop_at()
                if(!$this->with_DISTINCT_YN) {
                    $json_rec = json_encode($rec);
                    $md5 = md5($json_rec);
                    if(isset($this->unique_row[$md5])) continue;
                    else $this->unique_row[$md5] = '';
                }    
            }
            // */
            // print_r($rec);
            fwrite($WRITE, implode("\t", $rec)."\n");
            // print("-[".$rec[0]."]-[".$rec[9]."]");   // just a visual record lookup during runtime.
        }
        fclose($WRITE);
    }
    /* fpnas
    discarded_rows.tsv: [0]
    export_file.qs: [13]
    taxonomic_mappings_for_review.tsv: [13]
    trait_qry.tsv: [13]
    unprocessed_taxa.tsv: [0]
        discarded_rows.tsv: [0]
        export_file.qs: [174262]
        inferred_trait_qry.tsv: [198186]
        taxonomic_mappings_for_review.tsv: [174262]
        unprocessed_taxa.tsv: [23924]

    403648 traits row 31
    discarded_rows.tsv: [0]
    export_file.qs: [10]
    taxonomic_mappings_for_review.tsv: [10]
    trait_qry.tsv: [10]
    unprocessed_taxa.tsv: [0]
        discarded_rows.tsv: [0]
        export_file.qs: [301687]
        inferred_trait_qry.tsv: [403647]
        taxonomic_mappings_for_review.tsv: [301687]
        unprocessed_taxa.tsv: [101960]
    */
    function run_all_resources($spreadsheet)
    {
        $spreadsheet = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/resources/".$spreadsheet;
        $i = 0;
        foreach(new FileIterator($spreadsheet) as $line_number => $line) { $i++;
            if(!$line) continue;
            $row = str_getcsv($line);
            if(!$row) continue;
            if($i == 1) { $fields = $row; $count = count($fields); continue;}
            else { //main records
                $values = $row; $k = 0; $rec = array();
                foreach($fields as $field) { $rec[$field] = $values[$k]; $k++; }
                $rec = array_map('trim', $rec); //important step
                // print_r($rec); exit;

                /* good way to run 1 resource for investigation
                // if($rec['trait.source'] != 'https://www.wikidata.org/entity/Q116263059') continue; //1st group
                // if($rec['trait.source'] != 'https://doi.org/10.2307/3503472') continue; //2nd group                     stop.name OK
                if($rec['trait.source'] != 'https://doi.org/10.1073/pnas.1907847116') continue; //3rd group
                // if($rec['trait.source'] != 'https://doi.org/10.2994/1808-9798(2008)3[58:HTBAAD]2.0.CO;2') continue; //row 12
                // if($rec['trait.source'] != 'https://doi.org/10.1007/s00049-005-0325-5') continue; //row 18
                // if($rec['trait.source'] != 'https://doi.org/10.1111/j.1365-2311.1965.tb02304.x') continue; //row 31 403648 traits
                */

                // /* takbo
                $real_row = $i - 1;
                if($real_row == 31) $this->with_DISTINCT_YN = false;
                else                $this->with_DISTINCT_YN = true; //the rest goes here
                $this->unique_row = array();
                // if(!in_array($real_row, array(3,1,2,4,6,7,8,9,10))) continue; //DONE ALREADY | row 5 ignore deltakey | 11 our very first
                //---------------------------------------------------------------
                // if(!in_array($real_row, array(11))) continue; // our very first

                if(!in_array($real_row, array(3))) continue; //dev only  --- fpnas 198187
                // row 12 -- zero results for query by citation and source
                // if(!in_array($real_row, array(13,14,15,16,17,18,19,20))) continue; //dev only --  QuickStatements Done
                // if(!in_array($real_row, array(21,22,23,24,25,26,27,28,29,30))) continue; //dev only -- ready for review, with ancestry
                // if(!in_array($real_row, array(31))) continue; // 7 connectors 403648

                // if(!in_array($real_row, array(13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,3,31))) continue; //dev only --  caching...
                // if(!in_array($real_row, array(31))) continue; // two biggest 3,31

                // if(!in_array($real_row, array(23))) continue; //during dev, investigates...

                $this->real_row = $real_row;
                echo "\nrow: $real_row\n";
                // */

                self::run_resource_query($rec);
                // break; //process just first record
            }
        }
    }
    private function run_resource_query($rec)
    {   /* Array(
        [r.resource_id] => 1054
        [trait.source] => https://www.wikidata.org/entity/Q116263059
        [trait.citation] => McDermott, F. (1964). The Taxonomy of the Lampyridae (Coleoptera). Transactions of the American Entomological Society (1890-), 90(1), 1-72. Retrieved January 29, 2021, from http://www.jstor.org/stable/25077867
        )*/

        print_r($rec); //exit("\nstop 1\n");
            if($rec['trait.source'] == 'https://www.wikidata.org/entity/Q116180473') $use_citation = false; //TRUE; //our very first one, orig true
        elseif($rec['trait.source'] == 'https://doi.org/10.2994/1808-9798(2008)3[58:HTBAAD]2.0.CO;2') $use_citation = TRUE;            
        else $use_citation = FALSE; //the rest goes here.

        /* just testing queries if both via citation and via source is the same:
        if($rec['trait.source'] == 'https://www.wikidata.org/entity/Q116263059') $use_citation = TRUE;
        */

        if($use_citation) {
            // /* option 1
            $citation = $rec['trait.citation'];
            $input = array();
            $input["params"] = array("citation" => $citation);
            $input["type"] = "wikidata_base_qry_citation";
            if($this->with_DISTINCT_YN) $input["per_page"] = $this->per_page; //500 orig
            else                        $input["per_page"] = $this->per_page_2; //1000
            
            $input["trait kind"] = "trait";
            $this->query_trait_db($input);
            
            $input["trait kind"] = "inferred_trait";
            $this->query_trait_db($input);
            // */
        }
        else {
            // /* option 2
            $source = $rec['trait.source'];
            $input = array();
            $input["params"] = array("source" => $source);
            $input["type"] = "wikidata_base_qry_source";
            if($this->with_DISTINCT_YN) $input["per_page"] = $this->per_page; //500 orig
            else                        $input["per_page"] = $this->per_page_2; //1000

            $input["trait kind"] = "trait"; //print_r($input); exit;
            $this->query_trait_db($input);
            
            $input["trait kind"] = "inferred_trait";
            $this->query_trait_db($input);
            // */
        }
    }
    function get_traits_stop_at($input)
    {   /* didn't use it here
        self::initialize_path($input);
        */
        $this->report_path = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/";
        // /* report filename
        $this->tsv_file = $this->report_path."/".$input["type"]."_qry.tsv";
        // */

        if($val = @$input["per_page"]) $this->per_page = $val;
        if(isset($input["per_page"])) unset($input["per_page"]); // unset so that initial queries made won't get wasted. Where there is no $input["per_page"] yet.
        $skip = 0;
        while(true) {
            $input['skip'] = $skip;
            $input['limit'] = $this->per_page;
            $input = self::query_maker($input);
            $filename = self::generate_path_filename($input); //exit("\n[$filename]\n");
            $json = self::retrieve_trait_data($input, $filename);
            $obj = json_decode($json); //print_r($obj); //return; //exit("\nstop query muna\n");
            if($total = count(@$obj->data)) {
                // print_r($obj); exit; //good debug
                self::write_tsv($obj, $filename, $skip);
                foreach($obj->data as $r) $info[$r[0]] = '';
            }
            print("\n No. of rows: ".$total."\n");
            $skip += $this->per_page;
            if($total < $this->per_page) break;
            // break; //debug only
        }
        print("\nfilename: [$filename]\n-----Processing ends-----\n");
        print_r($input); //good debug
        print("\nReport file: ".$this->tsv_file."\n");
        return $info;
    }
}
?>