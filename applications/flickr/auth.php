<?php
//#!/usr/local/bin/php



define("ENVIRONMENT", "test");
define("DEBUG", true);
define("MYSQL_DEBUG", true);
include_once("../../config/start.php");



$function = @$_POST["function"];


echo "<pre>";
print_r($_GET);
echo "</pre>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<br><br><br>";


// $response = FlickrAPI::auth_get_token($_GET["frob"]);
// 
// echo "<pre>";print_r($response);echo "</pre>";
// 
// $profile = FlickrAPI::people_get_info($response->auth->user["nsid"], $response->auth->token);
// $auth_token = $response->auth->token;

$auth_token = '72157606690941918-97c1c060a2d18b5b';

$images = FlickrAPI::get_eol_photos(30, 167, $auth_token);
exit;



if(!$function)
{
    $response = FlickrAPI::auth_get_token($_GET["frob"]);
    
    echo "<pre>";print_r($response);echo "</pre>";

    $profile = FlickrAPI::people_get_info($response->auth->user["nsid"], $response->auth->token);
    
    $images = FlickrAPI::get_eol_photos(30, 49, $response->auth->token);
    
    
    echo "<pre>";
    print_r($authorization);
    echo "</pre>";

    echo "<form action='auth.php' method='post' enctype='multipart/form-data' onSubmit''>
            <table border=1>
                <tr><td>File:</td><td><input type=file name=photo accept=text size=50></td></tr>
                <tr><td>Title:</td><td><input type=text name=title size=50></td></tr>
                <tr><td>Description:</td><td><textarea name=description rows=3 cols=30></textarea></td></tr>
                <tr><td>Tags:</td><td><input type=text name=tags size=50></td></tr>
                
                <input type=hidden name=is_public value=1>
                <input type=hidden name=safety_level value=1>
                <input type=hidden name=user_nsid value='".$authorization["user_nsid"]."'>
                <input type=hidden name=auth_token value='".$authorization["auth_token"]."'>
                <input type=hidden name=function value='authenticate'>
                
                <input type=submit value='Submit'>
                
            </table>
        </form>";
}elseif($function == "authenticate")
{
    $photo = $_FILES["photo"];
    $title = $_POST["title"];
    $description = $_POST["description"];
    $tags = $_POST["tags"];
    $is_public = $_POST["is_public"];
    $safety_level = $_POST["safety_level"];
    $auth_token = $_POST["auth_token"];
    
    $params = array(
    	"api_key"	    => FLICKR_API_KEY,
    	"title"         => $title,
    	"description"   => $description,
    	"tags"          => $tags,
    	"is_public"     => $is_public,
    	"safety_level"  => $safety_level,
    	"auth_token"    => $auth_token
    );
    
    $params["api_sig"] = FlickrAPI::generateSignature($params);
    $params["photo"] = "@".$photo['tmp_name'];
    
    echo "<pre>";
    print_r($params);
    echo "</pre>";
    
    echo "<pre>";
    print_r($photo);
    echo "</pre>";

    
    
    $ch = curl_init();
    

    curl_setopt($ch, CURLOPT_URL, "http://www.flickr.com/services/upload/");

    curl_setopt($ch, CURLOPT_POST, true);
    if (isset($params)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }

    curl_setopt($ch, CURLOPT_FAILONERROR, 1);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT,50);

    set_time_limit(50);
    
    $result = curl_exec($ch);

    if (0 == curl_errno($ch)) {
        curl_close($ch);
        echo $result."<br>";
        echo "<pre>";
        print_r(curl_getinfo($ch));
        echo "</pre>";
    } else {
        echo "Request failed. ".curl_error($ch)." - ".curl_errno($ch)." - http://www.flickr.com/services/upload/";
        curl_close($ch);
    }
}


?>