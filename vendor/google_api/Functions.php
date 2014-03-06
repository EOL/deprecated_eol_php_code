<?php
namespace google_api;

function http_post($url, $parameters = array(), $headers = array(), $options = array())
{
    return http_request("post", $url, $parameters, $headers, $options);
}

function http_get($url, $headers = array(), $options = array())
{
    return http_request("get", $url, array(), $headers, $options);
}

function http_request($method, $url, $parameters = array(), $headers = array(), $options = array())
{
    if(!isset($options["timeout"])) $options["timeout"] = 100; // used in CURLOPT_TIMEOUT
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if($method == "post") curl_setopt($ch, CURLOPT_POST, true);
    if(isset($parameters) && $parameters) curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    if($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, $options["timeout"]);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    if(isset($_SERVER['SERVER_NAME'])) curl_setopt($ch, CURLOPT_REFERER, $_SERVER['SERVER_NAME']);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-us) AppleWebKit/525.27.1 (KHTML, like Gecko) Version/3.2.1 Safari/525.27.1");
    
    $result = curl_exec($ch);
    
    if(0 == curl_errno($ch))
    {
        curl_close($ch);
        return $result;
    }
    echo "url: $url<br>";
    echo "<br>$result<br>";
    print_r($parameters);
    print_r($headers);
    throw new \Exception("Curl exception: ". curl_error($ch));
}

?>