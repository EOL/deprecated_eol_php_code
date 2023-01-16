<?php
namespace php_active_record;

require_once __DIR__ . '/../../vendor/google_client_lib_2023/autoload.php';


/* connector: [google_client.php]  */

class GoogleClientAPI2023
{
    function __construct()
    {
    }

    function access_google_sheet($params)
    {
        //Reading data from spreadsheet.
        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets and PHP');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $client->setAuthConfig(__DIR__ . '/../../vendor/google_client_lib_2023/json/credentials.json');
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
