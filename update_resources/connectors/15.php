<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");
include_once(dirname(__FILE__) . "/../../lib/FlickrAPI.php");

$mysqli =& $GLOBALS['mysqli_connection'];



$auth_token = NULL;
if(FlickrAPI::valid_auth_token(FLICKR_PLEARY_AUTH_TOKEN))
{
    $auth_token = FLICKR_PLEARY_AUTH_TOKEN;
}

$taxa = FlickrAPI::get_all_eol_photos($auth_token);
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "15.xml";

$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

?>