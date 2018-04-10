<?php
namespace php_active_record;
// connector: [pbdb_fresh_harvest.php]
class PaleoDBAPI_v2
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('cache' => 1, 'resource_id' => $folder, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1, 
        'expire_seconds' => 60*60*24*30*3); //cache expires in 3 months // orig
        $this->download_options['expire_seconds'] = false; //debug

        $this->service["taxon"] = "http://localhost/cp/PaleoDB/TRAM-746/alltaxa.json";

        /* used in PaleoDBAPI.php
        $this->service["collection"] = "http://paleobiodb.org/data1.1/colls/list.csv?vocab=pbdb&limit=10&show=bin,attr,ref,loc,paleoloc,prot,time,strat,stratext,lith,lithext,geo,rem,ent,entname,crmod&taxon_name=";
        $this->service["occurrence"] = "http://paleobiodb.org/data1.1/occs/list.csv?show=loc,time&limit=10&base_name=";
        $this->service["reference"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=displayRefResults&type=view&reference_no=";
        $this->service["source"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=checkTaxonInfo&is_real_user=1&taxon_no=";
        */
    }

    function get_all_taxa()
    {
        self::parse_big_json_file();
    }
    
    private function parse_big_json_file()
    {
        $jsonfile = Functions::save_remote_file_to_local($this->service["taxon"], $this->download_options);
        $i = 0;
        foreach(new FileIterator($jsonfile) as $line_number => $line) {
            $i++;
            echo "\n-------------------------\n".$line;
            if(substr($line, 0, strlen('{"oid":')) == '{"oid":') {
                $str = substr($line, 0, -1); //remove last char (",") the comma, very important
                $arr = json_decode($str, true);
                echo "\n start arr() ------- \n";
                print_r($arr);
                echo "\n----end arr()---\n";
            }
            if($i > 30) break; //debug
        }
        unlink($jsonfile);
    }

}
?>