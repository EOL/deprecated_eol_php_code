<?php
/*
    get_pages_revisions.php
    from: https://www.mediawiki.org/wiki/API:Revisions
    https://en.wikipedia.org/w/api.php?action=query&prop=revisions&titles=Ocean sunfish&rvlimit=1&rvslots=main&formatversion=2&format=json
*/

$endPoint = "https://en.wikipedia.org/w/api.php";
$params = [
    "action" => "query",
    "prop" => "revisions",
    "titles" => "Ocean sunfish", //"Mola mola",
    "rvlimit" => "1", //"5",
    "rvslots" => "main",
    "formatversion" => "2",
    "format" => "json"
];
/* many other examples here: https://www.mediawiki.org/wiki/API:Revisions */

$url = $endPoint . "?" . http_build_query( $params );
$ch = curl_init( $url );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
$output = curl_exec( $ch );
curl_close( $ch );

$result = json_decode( $output );
print_r($result);
print_r($result->query->pages[0]->revisions[0]);

// foreach( $result["query"]["pages"] as $k => $v ) {
//     var_dump( $v["revisions"] );
// }
?>