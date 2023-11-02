<?php
session_start();
// exit("\nUnder construction...\n");
if(isset($_POST["inat_response"])) {
    $json = $_POST["inat_response"];
    $_SESSION["inat_response"] = $json;
    $post_params = json_decode($json);
    // print_r($post_params); echo " -- Used Post."; //good debug
    /*stdClass Object ( [access_token] => 1019173383d3203b03880573618827659203eac68c46dcba4eb48b5d33d47151 
    [token_type] => Bearer 
    [scope] => write login 
    [created_at] => 1579587745 )
    */
}
elseif(isset($_SESSION["inat_response"])) {
    $json = $_SESSION["inat_response"];
    $post_params = json_decode($json);
    // print_r($post_params); echo " -- Used Session."; //good debug
}
else {
    $client_id = 'cfe0aa14b145d1b2b527e5d8076d32839db7d773748d5182308cade1c4475b38';
    // $redirect_uri = 'https%3A%2F%2Feditors.eol.org%2Feol_php_code%2Fapplications%2FiNaturalist_OAuth2%2Fredirect.php';
    $redirect_uri = 'https://editors.eol.org/eol_php_code/applications/iNaturalist_OAuth2/redirect.php';
    $response_type = 'code';
    ?>
    <!--- working OK
    <form name='fn' action="https://www.inaturalist.org/oauth/authorize?response_type=code" method="get">
      <input type='hidden' name='client_id' value='<?php echo $client_id ?>'>
      <input type='hidden' name='redirect_uri' value='<?php echo $redirect_uri ?>'>
      <input type='hidden' name='response_type' value='<?php echo $response_type ?>'>
    </form>
    <script>
    // document.forms.fn.submit()
    </script>
    --->
    <?php
    /* working OK
    echo "<hr><br><b>'BOLD-to-iNat Tool'</b> is not yet authorized by iNaturalist.";
    echo "<p>Click <a href='#' onClick='document.forms.fn.submit()'>AUTHORIZE</a>  to proceed.</p><hr>";
    */
    ?>

    <form name='fn' action="target.php" method="post">
    <table>
    <tr><!---
        <td>NMNH Fishes Department iNaturalist Username: </td>
        --->
        <td>iNaturalist Username: </td>
        <td><input type='text' name='username' value='' required></td>
    </tr>
    <tr>
        <td>Password: </td>
        <td><input type='password' name='password' value='' required></td>
    </tr>
    <tr><td></td><td><input type='submit' value='Submit'></td></tr>
    </table>
    </form>

    <?php
    exit;
}
?>
<form action="form_result.php" method="post" enctype="multipart/form-data">
    <input type='hidden' name='JWT' value='<?php echo $post_params->access_token ?>'>
    <input type='hidden' name='token_type' value='<?php echo $post_params->token_type ?>'>
    <input type='hidden' name='Dept' value='fishes'>
    <table border="1" cellpadding="15" cellspacing="1" align="center" width="40%">
        <tr align="center">
            <td><b>Smithsonian MarineGEO Observations -- to -- iNaturalist</b></td>
        </tr>
        <tr align="center">
            <td>
                <table align='center'>
                <tr align="center">
                    <!--- old logo
                    https://marinegeo.si.edu/sites/all/themes/si_marinegeo/logo.png
                    --->
                    <td width="50%" bgcolor=''><img src='https://marinegeo.si.edu/sites/default/files/images/marineGEO-logo.png' height='40'></td>
                    <td width="50%"><img src='https://static.inaturalist.org/sites/1-logo.svg?1573071870' height='25'></td>
                </tr>
                <tr align="center">
                    <td colspan='2'>This tool uploads observations to iNaturalist using a specific CSV file.</td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <!---
                <table>
                <tr><td>Project:</td> <td><input type='text' name='Proj' value='' required><small> e.g. KANB</small></td></tr>
                <tr><td></td>
                    <td>
                        Refresh download <input type='checkbox' name='Proj_refresh'>
                        <br><small>Check tickbox if you want to get a fresh download of the project dataset.
                        <br>Otherwise it will use the saved dataset from previous run.
                        </small>
                    </td>
                </tr>
                <tr><td>Taxon (optional):</td> <td><input type='text' name='Taxon' value=''><small> e.g. Lutjanus</small></td></tr>
                </table>
                --->
                
                <table>
                <tr>
                    <td>
                        <font size="3">CSV File Upload: </font><input type="file" name="file_upload" id="file_upload" size="100">
                    </td>
                </tr>
                <tr>
                    <td>
                        <!---
                        <small>(.csv) OR (.csv.zip)</small>
                        --->
                    </td>
                </tr>
                </table>
                
            </td>
        </tr>
        <tr align="center">
            <td>
                <input type="submit" value="Add Observations to iNaturalist">
                <input type="reset" value="Reset">
            </td>
        </tr>
        <tr align="left">
            <td><small>
            <a href='../specimen_image_export/main.php'>Specimen Image Export Tool</a> <br>&nbsp;<br>
            <a href='../BOLD2iNAT/main.php'>BOLDS-to-iNaturalist Tool</a> <br>&nbsp;<br>
            
            <a href='https://www.inaturalist.org/observations?place_id=any&subview=table&user_id=nmnh_fishes&verifiable=any'>NMNH Fishes Observations in iNaturalist</a> <br>
            <a href='https://www.inaturalist.org/observations?place_id=any&subview=table&user_id=smithsonian_marinegeo&verifiable=any'>Smithsonian MarineGEO Observations in iNaturalist</a> <br>
            <a href='https://www.inaturalist.org/observations?place_id=any&subview=table&user_id=sercfisheries&verifiable=any'>Username <b>sercfisheries</b> in iNaturalist</a> <br>
            <a href='https://www.inaturalist.org/observations?place_id=any&subview=table&user_id=hakaiinstitute&verifiable=any'>Username <b>hakaiinstitute</b> in iNaturalist</a> <br>
            <small></td>
        </tr>
    </table>
</form>