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
    <tr>
        <td>NMNH Fishes Department iNaturalist Username: </td>
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
            <td><b>BOLD SYSTEMS -- to -- iNaturalist</b></td>
        </tr>
        <tr align="center">
            <td>
                <table align='center'>
                <tr align="center">
                    <td width="50%" bgcolor='darkgreen'><img src='http://www.boldsystems.org/libhtml_v4/images/BOLDlogo.png' height='25'></td>
                    <td width="50%"><img src='https://static.inaturalist.org/sites/1-logo.svg?1573071870' height='25'></td>
                </tr>
                <tr align="center">
                    <td colspan='2'>This tool uploads observations from BOLD Systems to iNaturalist.
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <!---
        <tr align="center">
            <td>
                <font size="3">
                    Download the input spreadsheet template <a href="https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MarineGEO_sie/image_input.xlsx">here</a>.
                    <br>
                </font>
            </td>
        </tr>
        <tr>
            <td>
                <font size="3">Excel File URL: </font><input type="text" name="form_url" size="100">
                <br>
                <small>
                    <i>e.g. http://mydomain.org/spreadsheets/image_input.xlsx</i>
                </small>
            </td>
        </tr>
        <tr>
            <td>
                <font size="3">Excel File Upload: </font><input type="file" name="file_upload" id="file_upload" size="100">
            </td>
        </tr>
        <tr>
            <td>
                <small>(.xlsx or .xls) OR (.xlsx.zip, .xls.zip)</small>
            </td>
        </tr>
        --->
        <!---
        Project: a short text string, eg: "KANB"
        Department: a short text string, which we can constrain to a menu of options, eg: "FISH"
        License: a text string, which we can constrain to a menu of options, eg: "CreativeCommons – Attribution Non-Commercial (by-nc)"
        License Year: a four digit number, unless you'd like to constrain it further. (I'm told a range of dates might happen, 1940, etc.)
        License Institution: a text string, which we can constrain to a menu of options, eg: "Smithsonian Institution National Museum of Natural History"
        License Contact: a short text string, eg: "williamsjt@si.edu" . <== optional field
        sample $json value: '{"Proj":"KANB", "Dept":"FISH", "Lic":"CreativeCommons – Attribution Non-Commercial (by-nc)", "Lic_yr":"", "Lic_inst":"", "Lic_cont":""}';
        --->
        <tr>
            <td>
                <table>
                <tr><td>Project:</td> <td><input type='text' name='Proj' required><small>e.g. KANB</small></td></tr>
                <tr><td>Taxon (optional):</td> <td><input type='text' name='Taxon'><small>e.g. Lutjanus</small></td></tr>
                <!---
                <tr><td>Department</td>
                    <td>
                         <select name='Dept'>
                          <option value="herps" disabled>Amphibians & Reptiles</option>
                          <option value="birds" disabled>Birds</option>
                          <option value="botany" disabled>Botany</option>
                          <option value="fishes" selected>Fishes</option>
                          <option value="mammals" disabled>Mammals</option>
                          <option value="paleo" disabled>Paleobiology</option>
                        </select>
                    </td>
                </tr>
                <tr><td>License:</td>
                    <td>
                         <select name='Lic'>
                         <?php
                         $licenses = array('CreativeCommons – Attribution (by)', 'CreativeCommons – Attribution Share-alike (by-sa)', 'CreativeCommons – Attribution Non-Commercial (by-nc)', 'CreativeCommons – Attribution Non-Commercial Share-alike (by-nc-sa)');
                         foreach($licenses as $license) {
                             $var = '';
                             if($license == 'CreativeCommons – Attribution Non-Commercial (by-nc)') $var = 'selected';
                             echo "<option value='$license' $var>$license</option>";
                         }
                         ?>
                        </select>
                    </td>
                </tr>
                <tr><td>License Year:</td> <td><input type='text' name='Lic_yr'></td></tr>
                <tr>
                    <td>License Institution:</td> 
                    <td>
                        <select name='Lic_inst'>
                            <option value=""></option>
                            <option value="Smithsonian Institution National Museum of Natural History">Smithsonian Institution National Museum of Natural History</option>
                            <option value="NOAA Fisheries">NOAA Fisheries</option>
                        </select>
                    </td>
                </tr>
                <tr><td>License Contact:</td> <td><input type='text' name='Lic_cont'></td></tr>
                --->
                </table>
            </td>
        </tr>
        
        <tr align="center">
            <td>
                <input type="submit" value="Add BOLD Observations to iNaturalist">
                <input type="reset" value="Reset">
                <!--- <input type="checkbox" name="validate">Validate --->
            </td>
        </tr>
        <tr align="left">
            <td><small><?php echo "<a href='../specimen_image_export/'>Specimen Image Export Tool</a>"; ?><small></td>
        </tr>
    </table>
</form>