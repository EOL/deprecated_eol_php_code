<?php
echo '<pre>';
if($_GET) {
    // echo "\nGet:\n";
    // print_r($_GET);
    $auth_code = $_GET['code'];
}
elseif($_POST) {
    // echo "\nPost:\n";
    // print_r($_POST);
}

if(!isset($auth_code)) {
    echo "\nNo auth_code yet.\n";
    return;
}
// echo "\nauth_code generated is: [$auth_code]\n";

$site = "https://www.inaturalist.org";
$app_id = 'cfe0aa14b145d1b2b527e5d8076d32839db7d773748d5182308cade1c4475b38';
$app_secret = '9cdfbdd2d87f4e91a22c08a22da76db66d04ae1feee08de8f4f93955501c4bd5';
$redirect_uri = 'https://editors.eol.org/eol_php_code/applications/iNaturalist_OAuth2/redirect.php';

/*
payload = {
  :client_id => app_id,
  :client_secret => app_secret,
  :code => auth_code,
  :redirect_uri => redirect_uri,
  :grant_type => "authorization_code"
}
puts "POST #{site}/oauth/token, payload: #{payload.inspect}"
puts response = RestClient.post("#{site}/oauth/token", payload)
*/

$url = $site.'/oauth/token';
$arr['client_id'] = $app_id;
$arr['client_secret'] = $app_secret;
$arr['code'] = $auth_code;
$arr['redirect_uri'] = $redirect_uri;
$arr['grant_type'] = 'authorization_code';

if($ret = curl_post_request($url, $arr)) {
    // echo "\n<br>POST ok<br>\n";
    // print_r($ret);
    /* # response will be a chunk of JSON looking like
    # {
    #   "access_token":"xxx",
    #   "token_type":"bearer",
    #   "expires_in":null,
    #   "refresh_token":null,
    #   "scope":"write"
    # }
    
    {"access_token":"4334a67655996f81d11b5bf8f2283c2a73f2a4afce6eb6b8dd3b70bb1199162c",
     "token_type":"Bearer",
     "scope":"write login",
     "created_at":1575989930}
     
    <form name='fn' action="https://editors.eol.org/eol_php_code/applications/BOLD2iNAT/index.php" method="post">

    */

    ?>
    Please wait, loading ...
    <form name='fn' action="http://localhost/eol_php_code/applications/BOLD2iNAT/index.php" method="post">
      <input type='hidden' name='inat_response' value='<?php echo $ret ?>'>
      <!--- <input type='submit'> --->
    </form>
    <script>
    document.forms.fn.submit()
    </script>
    <?php
}
else echo "\n<br>ERROR: POST failed\n";
echo '</pre>';

function curl_post_request($url, $parameters_array = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    if(isset($parameters_array) && is_array($parameters_array)) curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters_array);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    // echo("Sending post request to $url with params ".print_r($parameters_array, 1).": only attempt");
    echo("\n<br>Sending post request to $url with these params: <br>");
    foreach($parameters_array as $key => $val) {
        if(in_array($key, array('redirect_uri', 'grant_type'))) echo "\n$key = $val";
        else echo "\n$key = ".substr($val,0,3)."...";
    }
    
    $result = curl_exec($ch);
    if(0 == curl_errno($ch)) {
        curl_close($ch);
        return $result;
    }
    echo "\n<br>Curl error ($url): " . curl_error($ch);
    return false;
}
?>