<?php
namespace php_active_record;

require_once __DIR__ . '/../../vendor/google_client_lib_2023/autoload.php';

/* sample connector: [google_client.php] */

class GoogleClientAPI
{
    function __construct()
    {
        if(Functions::is_production()) $this->cache_path = '/extra/other_files/wikidata_cache/';
        else                           $this->cache_path = '/Volumes/Crucial_2TB/wikidata_cache/';
        if(!is_dir($this->cache_path)) mkdir($this->cache_path);

        $this->credentials_json_path = __DIR__ . '/../../vendor/google_client_lib_2023/json/credentials.json';
    }
    function access_google_sheet($params, $use_cache_YN = true)
    {
        // /*
        require_library('connectors/CacheMngtAPI');
        $this->func = new CacheMngtAPI($this->cache_path);
        // */

        // /* New solution:
        $md5_id = md5(json_encode($params));
        if($use_cache_YN) {
            if($records = $this->func->retrieve_json_obj($md5_id, false)) echo "\nCACHE EXISTS.\n"; //2nd param false means returned value is an array()
            else {
                echo "\nNO CACHE YET\n";
                $records = self::do_the_google_thing($params);
                $json = json_encode($records);
                $this->func->save_json($md5_id, $json);
            }
        }
        else {
            echo "\nCACHE FORCE-EXPIRE\n";
            $records = self::do_the_google_thing($params);
            $json = json_encode($records);
            $this->func->save_json($md5_id, $json);
        }
        // */
        return $records;   
    }
    private function do_the_google_thing($params)
    {
        //Reading data from spreadsheet.
        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets and PHP');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $client->setAuthConfig($this->credentials_json_path);
        $service = new \Google_Service_Sheets($client);

        /*
        $spreadsheetId = "129IRvjoFLUs8kVzjdchT_ImlCGGXIdVKYkKwIv7ld0U"; //It is present in your URL
        $get_range = "measurementTypes!A1:B9";
        Note:  Sheet name is found in the bottom of your sheet and range can be an example
        "A2: B10" or “A2: C50" or “B1: B10" etc.
        */

        //Request to get data from spreadsheet.
        $response = $service->spreadsheets_values->get($params['spreadsheetID'], $params['range']);
        $values = $response->getValues();
        return $values;
    }
}
?>