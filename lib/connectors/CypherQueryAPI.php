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
        /* add: 'resource_id' => "eol_api_v3" ;if you want to add the cache inside a folder [eol_api_v3] inside [eol_cache] */
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

        $this->basename = "cypher_".date('YmdHis');
        $this->debug = array();
    }

    function query_trait_db($input)
    {
        $json = self::retrieve_trait_data($input);
        $obj = json_decode($json);
        print_r($obj);
        // return @$obj->data[0][0];
    }
    private function retrieve_trait_data($input)
    {
        $filename = self::generate_path_filename($input);
        if(file_exists($filename)) {
            debug("\nCypher cache already exists. [$filename]\n");
            
            // $this->download_options['expire_seconds'] = 60; //debug only - force assign --- test success
            
            $file_age_in_seconds = time() - filemtime($filename);
            if($file_age_in_seconds < $this->expire_seconds_4cypher_query) return self::retrieve_json($filename); //not yet expired
            if($this->expire_seconds_4cypher_query === false)              return self::retrieve_json($filename); //doesn't expire
            
            debug("\nCache expired. Will run cypher now...\n");
            self::run_cypher_query($tc_id, $filename);
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
        
        if($input['type'] == "wikidata_base_qry_citation") {
            $citation = urlencode($input['params']['citation']);
            $qry = 'MATCH (t:Trait)<-[:trait|inferred_trait]-(p:Page),
            (t)-[:predicate]->(pred:Term)
            WHERE t.citation="'.$citation.'"
            OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
            OPTIONAL MATCH (t)-[:units_term]->(units:Term)
            OPTIONAL MATCH (t)-[:lifestage_term]->(stage:Term)
            OPTIONAL MATCH (t)-[:sex_term]->(sex:Term)
            OPTIONAL MATCH (t)-[:statistical_method_term]->(stat:Term)
            OPTIONAL MATCH (t)-[:metadata]->(ref:MetaData)-[:predicate]->(:Term {name:"reference"})
            RETURN DISTINCT p.canonical, p.page_id, pred.name, stage.name, sex.name, stat.name, obj.name, t.measurement, units.name, t.source, t.citation, ref.literal
            LIMIT 10';    
        }
        elseif($input['type'] == "wikidata_base_qry_source") {
            $source = urlencode($input['params']['source']);
            $qry = 'MATCH (t:Trait)<-[:trait|inferred_trait]-(p:Page),
            (t)-[:predicate]->(pred:Term)
            WHERE t.source="'.$source.'"
            OPTIONAL MATCH (t)-[:object_term]->(obj:Term)
            OPTIONAL MATCH (t)-[:units_term]->(units:Term)
            OPTIONAL MATCH (t)-[:lifestage_term]->(stage:Term)
            OPTIONAL MATCH (t)-[:sex_term]->(sex:Term)
            OPTIONAL MATCH (t)-[:statistical_method_term]->(stat:Term)
            OPTIONAL MATCH (t)-[:metadata]->(ref:MetaData)-[:predicate]->(:Term {name:"reference"})
            RETURN DISTINCT p.canonical, p.page_id, pred.name, stage.name, sex.name, stat.name, obj.name, t.measurement, units.name, t.source, t.citation, ref.literal
            LIMIT 10';
        }
        else exit("\nERROR: Undefiend query.\n");

        $json = self::run_query($qry);

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
        $cmd = 'wget -O '.$destination.' --header "Authorization: JWT `/bin/cat '.DOC_ROOT.'temp/api.token`" https://eol.org/service/cypher?query="`/bin/cat '.$in_file.'`"';
        // $cmd .= ' 2>/dev/null'; //this will throw away the output
        $output = shell_exec($cmd); //$output here is blank since we ended command with '2>/dev/null' --> https://askubuntu.com/questions/350208/what-does-2-dev-null-mean
        echo "\n[$output]\n";
        $json = file_get_contents($destination);
        unlink($in_file);
        unlink($destination);
        // $obj = json_decode($json);
        // return @$obj->data[0][0];
        return $json;
    }
    private function generate_path_filename($input)
    {
        $main_path = $this->main_path;
        $md5 = md5(json_encode($input));
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$md5.json";
        return $filename;
    }
}
?>