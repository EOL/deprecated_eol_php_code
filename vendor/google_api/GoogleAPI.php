<?php
namespace google_api;

class GoogleAPI
{
    static $service_name = '';
    public $application_name = '';
    
    public function __construct($username, $password, $auth_token = null, $application_name, $options = array())
    {
        $this->auth_token = $auth_token;
        $this->application_name = $application_name;
        $this->username = $username;
        $this->password = $password;
        $this->login($options);
    }
    
    public function login($options = array())
    {
        if($this->auth_token) return $this->auth_token;
        $params = array();
        $params['accountType'] = 'GOOGLE';
        $params['Email'] = $this->username;
        $params['Passwd'] = $this->password;
        $params['service'] = static::$service_name;
        $params['source'] = $this->application_name;
        print_r($params);
        $response = http_post('https://www.google.com/accounts/ClientLogin', $params, array(), $options);
        if(isset($response['AUTH']))
        {
            if(preg_match("/AUTH=([a-z0-9_-]+)/ims", $response, $arr))
            {
                $this->auth_token = $arr[1];
                return $this->auth_token;
            }
        }
        throw new \Exception("Invalid Google API login: $this->username");
    }
}

?>