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
            'resource_id'        => 'eol_api_v3',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 750000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        $this->expire_seconds_4cypher_query = 60*60*24; //1 day expires. Used when resource(s) get re-harvested to get latest score based on Trait records.
        $this->expire_seconds_4cypher_query = $this->download_options['expire_seconds'];

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->download_options['cache_path'] = "/Volumes/Crucial_2TB/eol_cache/";      //used in Functions.php for all general cache
        $this->main_path = $this->download_options['cache_path'].$this->download_options['resource_id']."/";
        if(!is_dir($this->main_path)) mkdir($this->main_path);
                
        // for creating archives
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->basename = "cypher_".date('YmdHis');
        
        $this->debug = array();
    }

    function process_all_eol_taxa_using_DH($path, $purpose = 'main', $range = array()) //rows = 1,906,685 -> rank 'species' and with EOLid
    {
        self::api_using_tc_id($taxon_concept_id, $rek['scientificName']);
    }
    private function get_trait_totals($tc_id)
    {
        $arr = self::retrieve_trait_totals($tc_id);
        return $arr;
    }
    private function retrieve_trait_totals($tc_id)
    {
        $filename = self::generate_path_filename($tc_id);
        if(file_exists($filename)) {
            if($GLOBALS['ENV_DEBUG']) echo "\nCypher cache already exists. [$filename]\n";
            
            // $this->download_options['expire_seconds'] = 60; //debug only - force assign --- test success
            
            $file_age_in_seconds = time() - filemtime($filename);
            if($file_age_in_seconds < $this->expire_seconds_4cypher_query) return self::retrieve_json($filename); //not yet expired
            if($this->expire_seconds_4cypher_query === false)              return self::retrieve_json($filename); //doesn't expire
            
            if($GLOBALS['ENV_DEBUG']) echo "\nCache expired. Will run cypher now...\n";
            self::run_cypher_query($tc_id, $filename);
            return self::retrieve_json($filename);
        }
        else {
            if($GLOBALS['ENV_DEBUG']) echo "\nRun cypher query...\n";
            self::run_cypher_query($tc_id, $filename);
            return self::retrieve_json($filename);
        }
    }
    private function retrieve_json($filename)
    {
        $json = file_get_contents($filename);
        return json_decode($json, true);
    }
    private function run_cypher_query($tc_id, $filename)
    {
        $saved = array();
        /* total traits */
        $qry = "MATCH (t:Trait)<-[:trait]-(p:Page), (t)-[:supplier]->(r:Resource), (t)-[:predicate]->(pred:Term) WHERE p.page_id = ".$tc_id." OPTIONAL MATCH (t)-[:units_term]->(units:Term) RETURN COUNT(pred.name) LIMIT 5";
        $saved['total traits'] = self::run_query($qry);
        /* total measurementTypes */
        $qry = "MATCH (t:Trait)<-[:trait]-(p:Page), (t)-[:supplier]->(r:Resource), (t)-[:predicate]->(pred:Term) WHERE p.page_id = ".$tc_id." OPTIONAL MATCH (t)-[:units_term]->(units:Term) RETURN COUNT(DISTINCT pred.name) LIMIT 5";
        $saved['total mtypes'] = self::run_query($qry);
        // print_r($saved); exit;
        $WRITE = Functions::file_open($filename, "w");
        fwrite($WRITE, json_encode($saved)); fclose($WRITE);
        if($GLOBALS['ENV_DEBUG']) echo "\nSaved OK [$filename]\n";
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
        
        
        
        $cmd .= ' 2>/dev/null'; //this will throw away the output
        $output = shell_exec($cmd); //$output here is blank since we ended command with '2>/dev/null' --> https://askubuntu.com/questions/350208/what-does-2-dev-null-mean
        $json = file_get_contents($destination);
        $obj = json_decode($json);
        return @$obj->data[0][0];
    }
    private function generate_path_filename($tc_id)
    {
        $main_path = $this->main_path;
        $md5 = md5($tc_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$tc_id.json";
        return $filename;
    }
}
?>