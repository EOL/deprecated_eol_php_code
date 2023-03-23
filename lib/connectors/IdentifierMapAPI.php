<?php
namespace php_active_record;
/* connector: [identifier_map.php]
First client:
- WikiDataMtce.php COLLAB-1006
*/
class IdentifierMapAPI
{
    function __construct($folder = null, $query = null)
    {
        /* add: 'resource_id' => "folder_name" ;if you want to add the cache inside a folder [folder_name] inside [eol_cache] */
        $this->download_options = array(
            'resource_id'        => 'identifier_map',  //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 750000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5, 'cache' => 1);

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->download_options['cache_path'] = "/Volumes/Crucial_2TB/eol_cache/"; //used in Functions.php for all general cache
        $this->identifier_map_url = "https://eol.org/data/provider_ids.csv.gz";
        $this->debug = array();
    }
    
    function read_identifier_map_to_var($input = array("resource_id" => 'all'))
    {
        $options = $this->download_options;
        $options['file_extension'] = "gz";
        $options['timeout'] = 60*60*60*3; //3 hrs
        $resource_id = $input['resource_id'];

        // if($local_file = Functions::save_remote_file_to_local($this->identifier_map_url, $options)) { exit("\nlocal: [$local_file\n"); //working - un-comment in real operation
        if(true) {
            $local_file = "/Volumes/AKiTiO4/eol_php_code_tmp/tmp_53596.file.gz";
            echo "\nfilesize [$local_file]: ".filesize($local_file)."\nread_identifier_map_to_var()..."; //exit;
            print_r($input);
            $destination = pathinfo($local_file, PATHINFO_DIRNAME);
            /* working OK - un-comment in real operation
            $cmd = "cd ".$destination;
            $cmd .= "| gzip -d -k -f -N 'provider_ids.csv' $local_file"; //-d decompress | -k keep file.gz | -f overwrite | -N destination name
            $cmd .= " 2>&1";
            echo "\n$cmd\n";
            $output = shell_exec($cmd);
            echo "\n$output\n";
            */
            $local_file = $destination."/provider_ids.csv"; print("\nlocal_file: [$local_file]\n");
            $i = 0;
            foreach(new FileIterator($local_file) as $line => $row) {
                $i++;
                if($i == 1) {
                    $fields = explode(",", $row); //orig \t
                    continue;
                }
                else {
                    if(!$row) continue;
                    $tmp = explode(",", $row); // orig \t
                    $rec = array(); $k = 0;
                    foreach($fields as $field) {
                        $rec[$field] = $tmp[$k];
                        $k++;
                    }
                    $rec = array_map('trim', $rec);
                    // print_r($rec); exit("\nelix1\n");
                }

                /*Array(
                    [node_id] => 40989346
                    [resource_pk] => EOL-000000000001
                    [resource_id] => 724
                    [page_id] => 2913056
                    [preferred_canonical_for_page] => Life
                )
                For the taxa mappings, we can use the identifier map (https://opendata.eol.org/dataset/identifier-map). 
                Look for lines with resource_id (column 3) 1072 (wikidata hierarchy) 
                and use the mapping of the EOL page id (column 4) to the wikidata entity id (column 2).
                */
                if($resource_id == 'all') {
                    $final[$rec['page_id']] = array('i' => $rec['resource_pk'], 'c' => $rec['preferred_canonical_for_page']);
                }
                elseif($resource_id == $rec['resource_id']) {
                    // print_r($rec); exit;
                    $final[$rec['page_id']][] = array('i' => $rec['resource_pk'], 'c' => $rec['preferred_canonical_for_page']);
                    /* debug
                    if($rec['page_id'] == 16251768) {
                        print_r($rec); exit;
                    }
                    */
                }

            }
            // unlink($local_file); //un-comment in real operation
        }
        // print_r($final);
        // print_r($final['16251768']);
        return $final;
    }
}
?>