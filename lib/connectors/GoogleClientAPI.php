<?php
namespace php_active_record;

// require_once DOC_ROOT . '/vendor/google_client_library/autoload.php';
require_once __DIR__ . '/../../vendor/google_client_library/autoload.php';


/* connector: [google_client.php]  */

class GoogleClientAPI
{
    function __construct()
    {
        if(!defined('APPLICATION_NAME')) define('APPLICATION_NAME', 'Google Sheets API PHP Quickstart');
        /* moved to autoload.php above by Eli
        define('CREDENTIALS_PATH',    '../../vendor/google_client_library/json/sheets.googleapis.com-php-quickstart.json');
        define('CLIENT_SECRET_PATH',  '../../vendor/google_client_library/json/client_secret.json');
        */
        // If modifying these scopes, delete your previously saved credentials
        // at ~/.credentials/sheets.googleapis.com-php-quickstart.json

        if(!defined('SCOPES')) define('SCOPES', implode(' ', array(\Google_Service_Sheets::SPREADSHEETS_READONLY)));
        if (php_sapi_name() != 'cli') {throw new Exception('This application must be run on the command line.');}
    }

    function access_google_sheet($params)
    {
        // Get the API client and construct the service object.
        $client = self::getClient();
        $service = new \Google_Service_Sheets($client);
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
