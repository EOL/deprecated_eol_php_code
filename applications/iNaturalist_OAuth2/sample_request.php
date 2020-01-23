<?php

namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");

/*
# Store the token (access_token) in your web app. You can now use it to make authorized
# requests on behalf of the user, like retrieving profile data:
token = JSON.parse(response)["access_token"]
headers = {"Authorization" => "Bearer #{token}"}
puts "GET /users/edit.json, headers: #{headers.inspect}"
puts RestClient.get("#{site}/users/edit.json", headers)
*/

$json = '{"access_token":"8335053bea16842021631f3872b02666ff9198330aa88fab110fe027e72c8b7c",
 "token_type":"Bearer",
 "scope":"write login",
 "created_at":1575989930}';
$obj = json_decode($json);
print_r($obj);

$token = $obj->access_token;
$headers['Authorization'] = "Bearer%20$token";

$site = "https://www.inaturalist.org";
$url = $site."/users/edit.json";

/* worked OK
https://www.inaturalist.org/users/edit.json?Authorization=Bearer 4334a67655996f81d11b5bf8f2283c2a73f2a4afce6eb6b8dd3b70bb1199162c
*/

/*
if($ret = curl_get_request($url, $headers)) {
    echo "\nGET ok\n";
    print_r($ret);
}
else echo "\nERROR: GET failed\n";
*/

if($ret = Functions::curl_post_request($url, $headers)) {
    echo "\nPOST ok\n";
    print_r($ret);
}
else echo "\nERROR: POST failed\n";
echo '</pre>';




function curl_get_request($url, $data = array())
{
    $params = '';
    foreach($data as $key=>$value) $params .= $key.'='.$value.'&';
    $params = trim($params, '&');
    $url = $url."?".$params;
    echo "\n$url\n";
    $result = Functions::lookup_with_cache($url);
    return $result;
}

?>