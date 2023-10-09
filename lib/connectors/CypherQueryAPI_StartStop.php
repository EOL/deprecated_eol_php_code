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
class CypherQueryAPI_StartStop
{
    function __construct($folder = null)
    {
        $this->download_options = array(
            'resource_id'        => 'cypher_query',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 750000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        $this->expire_seconds_4cypher_query = false; //60*60*24; //1 day expires. Default false, other value on demand.
        $this->expire_seconds_4cypher_query = $this->download_options['expire_seconds'];

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->download_options['cache_path'] = "/Volumes/Crucial_2TB/eol_cache/";      //used in Functions.php for all general cache
        $this->main_path = $this->download_options['cache_path'].$this->download_options['resource_id']."/";
        if(!is_dir($this->main_path)) mkdir($this->main_path);

        $this->basename = "cypher_".date('Y_m_d_His');
        $this->per_page = 500; //100;       //per page with DISTINCT
        $this->per_page_2 = 1000;           //per page without DISTINCT
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
    {   exit("\nWorks OK but I maintained to use the orig lib in ['connectors/CypherQueryAPI']\n");
        if(@$input['params']['source'] == "https://doi.org/10.1007/s13127-017-0350-6") $this->with_DISTINCT_YN = false;

        print_r($input); //exit;
        self::initialize_path($input);
        // /* report filename
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
            // if($skip == 200) break; //debug only
        }
        print("\n-----Processing ends-----\n");
        // print_r($input); //good debug
        print("\nReport file: ".$this->tsv_file."\n");
    }
    private function query_maker($input)
    {
        $skip = $input['skip'];
        $limit = $input['limit'];
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
        $secs = 60*2; 
        $secs = 30;
        echo "\nSleep $secs secs..."; sleep($secs); echo " Continue...\n"; //delay 2 seconds
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
}
/* On Mon, Oct 9, 2023 at 3:59 AM Katja Schulz <eolspecies@gmail.com> wrote:
Hi Eli,

I have added Jen's suggestions to the Need a better way to get a list of all start & stop nodes for inferred records doc. I have tested these two queries:

MATCH (p:Page)-[:trait]->(t:Trait)-[:metadata]->(MetaData)-[:predicate]->(:Term {uri:"https://eol.org/schema/terms/starts_at"}),
(t)-[:supplier]->(res:Resource)
OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
OPTIONAL MATCH (t)-[:normal_units_term]->(units:Term)
RETURN DISTINCT p.canonical, p.page_id, t.scientificname, t.predicate, obj.uri, obj.name, t.normal_measurement, units.uri, units.name, t.normal_units,res.resource_id, res.name
LIMIT 50

MATCH (t:Trait)-[:metadata]->(m1:MetaData)-[:predicate]->(:Term {uri:"https://eol.org/schema/terms/starts_at"}),
(t)-[:metadata]->(m2:MetaData)-[:predicate]->(:Term {uri:"https://eol.org/schema/terms/stops_at"})
RETURN DISTINCT m1.measurement,m2.measurement
LIMIT 50

The first one gives me everything I need, except for the stop nodes (which I can get with the second query) and the predicate, which is not a huge deal. I may still try to fiddle with the query to figure out how to get the predicates, but even if I can't manage that, I can get an idea of the predicate by looking at the values.

Thanks, Katja
*/
?>