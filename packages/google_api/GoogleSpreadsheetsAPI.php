<?php
namespace google_api;

class GoogleSpreadsheetsAPI extends GoogleAPI
{
    static $service_name = 'wise';
    
    public function get_spreadsheets()
    {
        return simplexml_load_string(http_get('https://spreadsheets.google.com/feeds/spreadsheets/private/full', array('GData-Version: 3.0', "Authorization: GoogleLogin auth=" . $this->auth_token)));
    }

    public function get_response($url)
    {
        return simplexml_load_string(http_get($url, array('GData-Version: 3.0', "Authorization: GoogleLogin auth=" . $this->auth_token)));
    }
}

?>