<form action="form_result.php" method="post" enctype="multipart/form-data">
    <table border="1" cellpadding="15" cellspacing="1" align="center" width="40%">
        <tr align="center">
            <td>Excel Specimen Image Export Tool
            <small><br><a href='https://eol-jira.bibalex.org/browse/COLLAB-1004?focusedCommentId=64188&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64188'>(work in progress)</a></small>
            </td>
        </tr>
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
                <small>(.xlsx, .xls) OR recommended (.xls.zip, .xlsx.zip)
                <br><br>
                *It is recommended that you zip your file before uploading.</small>
            </td>
        </tr>
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
                <tr><td>Project:</td> <td><input type='text' name='Proj'><small>e.g. KANB</small></td></tr>
                <tr><td>Department</td>
                    <td>
                         <select name='Dept'>
                          <option value="herps">Amphibians & Reptiles</option>
                          <option value="birds">Birds</option>
                          <option value="botany">Botany</option>
                          <option value="fishes" selected>Fishes</option>
                          <option value="mammals">Mammals</option>
                          <option value="paleo">Paleobiology</option>
                        </select>
                    </td>
                </tr>
                <tr><td>License:</td>
                    <td>
                         <select name='Lic'>
                         <?php
                         $licenses = array('CreativeCommons – Attribution (by)', 'CreativeCommons – Attribution Share-alike (by-sa)', 'CreativeCommons – Attribution Non-Commercial (by-nc)', 'CreativeCommons – Attribution Non-Commercial Share-alike (by-nc-sa)');
                         foreach($licenses as $license) echo "<option value='$license'>$license</option>";
                         ?>
                        </select>
                    </td>
                </tr>
                <tr><td>License Year:</td> <td><input type='text' name='Lic_yr'></td></tr>
                <tr><td>License Institution:</td> <td><input type='text' name='Lic_inst'><small>e.g. Smithsonian Institution National Museum of Natural History</small></td></tr>
                <tr><td>License Contact:</td> <td><input type='text' name='Lic_cont'></td></tr>
                </table>
            </td>
        </tr>
        <tr align="center">
            <td>
                <input type="submit" value="Generate specimen export file">
                <input type="reset" value="Reset">
                <!--- <input type="checkbox" name="validate">Validate --->
            </td>
        </tr>
        <tr align="left">
            <td><small><?php echo "<a href='../specimen_export/'>Specimen Export Tool</a>"; ?><small></td>
        </tr>
    </table>
</form>