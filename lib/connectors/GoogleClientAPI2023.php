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
        /*
        // Get the API client and construct the service object.
        $client = self::getClient();
        $service = new \Google_Service_Sheets($client);
        $response = $service->spreadsheets_values->get($params['spreadsheetID'], $params['range']);
        $values = $response->getValues();
        return $values;
        */

        //Reading data from spreadsheet.
        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets and PHP');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $client->setAuthConfig(__DIR__ . '/../../vendor/google_client_lib_2023/json/credentials.json');
        $service = new \Google_Service_Sheets($client);

        // $spreadsheetId = "129IRvjoFLUs8kVzjdchT_ImlCGGXIdVKYkKwIv7ld0U"; //It is present in your URL
        // $get_range = "measurementTypes!A1:B9";
        /*
        Note:  Sheet name is found in the bottom of your sheet and range can be an example
        "A2: B10" or “A2: C50" or “B1: B10" etc.
        */

        //Request to get data from spreadsheet.
        $response = $service->spreadsheets_values->get($params['spreadsheetID'], $params['range']);
        $values = $response->getValues();
        return $values;


    }

    /*
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
    */
    function getClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName(APPLICATION_NAME);
        $client->setScopes(SCOPES);
        $client->setAuthConfig(CLIENT_SECRET_PATH);
        $client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        $credentialsPath = self::expandHomeDirectory(CREDENTIALS_PATH);
        if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
        } else 
        {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            // Store the credentials to disk.
            if(!file_exists(dirname($credentialsPath)))  mkdir(dirname($credentialsPath), 0700, true);
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) 
        {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    /*
    * Expands the home directory alias '~' to the full path.
    * @param string $path the path to expand.
    * @return string the expanded path.
    */
    function expandHomeDirectory($path) 
    {
        $homeDirectory = getenv('HOME');
        if(empty($homeDirectory)) 
        {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }

}
?>
