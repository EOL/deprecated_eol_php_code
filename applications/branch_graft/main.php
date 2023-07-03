<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$browser = Functions::getBrowser(); // echo "Browser: " . $browser;
if($browser == 'Firefox') $browser_comment = "Browse...";
else                      $browser_comment = "Choose File"; //Safari Chrome etc
?>
<table border="1" cellpadding="15" cellspacing="1" align="center" width="40%">
    <tr align="center"><td><b>Branch Grafting Tool</b></td></tr>
    <!---
    <tr>
        <td>
        <table><tr>
            <td width="70%">
                <font size="3">
                 <ul>
                  <li>Download to your computer the input spreadsheet template <a href="temp/Trait_template.xlsx">here</a>.</li>
                  <li>Open spreadsheet, enter your own data and save.</li>
                  <li>Upload file using [<?php echo $browser_comment ?>] button below.</li>
                </ul>
                </font>
            </td>
            <td>
                <form action="form_result_upload.php" method="post" enctype="multipart/form-data">
                <font size="2">
                    You can also upload an updated spreadsheet template, with an updated vocabulary sheet.<br>
                    Upload template: <input type="file" name="file_upload" id="file_upload" size="100"><br>
                        <input type="submit" value="Submit template">
                        <input type="reset" value="Reset">
                </font>
                </form>
            
            </td>
        </tr></table>
        </td>
    </tr>
    --->
<!---    
--->

    <form action="form_result.php" method="post" enctype="multipart/form-data">
    <tr><td>
            <font size="3">
            <b>File A</b> and <b>File B</b> are taxa files in Darwin Core format, but not Darwin Core archives.<br>
            Both files are formatted as TSV (tab-separated-values).
            </font>
    </td></tr>

    <tr><td>
            <font size="3">
            <b>File A</b> : The file with the hierarchy we want to edit. <br><br>
            Upload file: </font><input type="file" name="file_upload" id="file_upload" size="100" required>
            <br><br><small>(.tsv) OR (.tsv.zip)</small>
    </td></tr>
    <tr><td>
            <font size="3">
            <b>File B</b> : The file with the branch we want to graft. <br><br>
            Upload file: </font><input type="file" name="file_upload2" id="file_upload2" size="100" required>
            <br><br><small>(.tsv) OR (.tsv.zip)</small>        
    </td></tr>
    <tr><td>
            <font size="3">
            <b>File A taxonID</b> (remove descendants): <input type="text" name="fileA_taxonID" size="50" required> <small>(required)</small> <br><br>
            <b>File B taxonID</b> (graft descendants):  <input type="text" name="fileB_taxonID" size="50"> <small>(optional)</small>
    </td></tr>

    <!---
    --->
    <!---
    --->
    <tr align="center">
        <td>
            <input type='text' name='Filename_ID' hidden>
            <input type='text' name='Short_Desc' hidden>
            <input type="submit" value="Run">
            <input type="reset" value="Reset">
        </td>
    </tr>
    </form>
    <!---
    <tr align="left">
        <td>
        <?php echo "<a href='https://opendata.eol.org/dataset/trait-spreadsheet-repository'>OpenData Resources For Uploaded Spreadsheets</a>"; ?>
         <?php echo "<a href='../../../html/tools.html'>Tools</a>"; ?>
        </td>
    </tr>
    --->
</table>
