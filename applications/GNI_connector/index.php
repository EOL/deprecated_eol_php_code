<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>EOL2GNI Transformation</title>
        <script language="javascript1.2">
            function proc()
            {
                var number = document.getElementById('resource_id').selectedIndex;
                //alert(document.getElementById('resource_id').options[number].value);
                document.getElementById('txt2').value = '';
                document.getElementById('txt2').value = document.getElementById('resource_id').options[number].value;
                //document.getElementById('subscribe').href = document.getElementById('txt2').value;
                //document.getElementById('preview').href = 'EOL2GNI.php?url=' + document.getElementById('txt2').value;
                //document.getElementById('downl').href = document.getElementById('txt2').value;
            }
            function transform(process)
            {
                var number = document.getElementById('resource_id').selectedIndex;
                if(number == 0)
                {   alert("Select EOL resource or paste URL to proceed.");
                    return;
                }    
                //location.href = 'EOL2GNI.php?url=' + document.getElementById('txt2').value;
                //top.location.href = 'EOL2GNI.php?url=' + document.getElementById('txt2').value;
                url = 'EOL2GNI.php?url=' + URLEncode(document.getElementById('txt2').value);
                str = "";
                if(document.getElementById('what_eval').checked){str = document.getElementById('what_eval').value;}
                if(document.getElementById('what_eval_spm').checked){str = document.getElementById('what_eval_spm').value;}
                if(document.getElementById('what_tran').checked){str = document.getElementById('what_tran').value;}
                url += "&what=" + str;
                if(process == 1)url += "&download=1"
                window.open(url, "new_window");
            }
            function erase()
            {//document.getElementById('txt2').value = '';
            }
            function URLEncode(url) //Function to encode URL.
            {
                // The Javascript escape and unescape functions do not correspond
                // with what browsers actually do...
                var SAFECHARS = "0123456789" + // Numeric
                "ABCDEFGHIJKLMNOPQRSTUVWXYZ" + // Alphabetic
                "abcdefghijklmnopqrstuvwxyz" +
                "-_.!~*'()"; // RFC2396 Mark characters
                var HEX = "0123456789ABCDEF";    
                var plaintext = url;
                var encoded = "";
                for (var i = 0; i < plaintext.length; i++ ) 
                {
                    var ch = plaintext.charAt(i);
                    if (ch == " ") 
                    {
                        encoded += "+"; // x-www-urlencoded, rather than %20
                    } 
                    else if (SAFECHARS.indexOf(ch) != -1) 
                    {
                        encoded += ch;
                    } 
                    else 
                    {
                        var charCode = ch.charCodeAt(0);
                        if (charCode > 255) 
                        {
                            alert( "Unicode Character '"
                            + ch
                            + "' cannot be encoded using standard URL encoding.\n" +
                            "(URL encoding only supports 8-bit characters.)\n" +
                            "A space (+) will be substituted." );
                            encoded += "+";
                        } 
                        else
                        {
                            encoded += "%";
                            encoded += HEX.charAt((charCode >> 4) & 0xF);
                            encoded += HEX.charAt(charCode & 0xF);
                        }
                    }
                }
                return encoded;
            }    
            function check_url()
            {
                var number = document.getElementById('resource_id').selectedIndex;    
                if(number == 0)
                {    alert("Select EOL resource or paste URL to proceed.");
                    return;
                }    
                document.forms.validator_form.file_url.value = document.getElementById('txt2').value
                document.forms.validator_form.submit();
            }
        </script>
</head>

<body>

<?php
/* has to be connected to the vpn */
include_once(dirname(__FILE__) . "/../../config/environment.php");

print"<table border='1' cellpadding='5' cellspacing='0'>";
$qry = "select title, id, accesspoint_url, service_type_id from resources order by title";
$result = $GLOBALS['db_connection']->query($qry);    

print"<td><font size='2'><i>Select an EOL resource</i></font>
<select id='resource_id' name=resource_id onChange='proc()' style='font-size : small; font-family : Arial; background-color : Aqua;'><option>";
while($result && $row=$result->fetch_assoc())
{
    print"<option value=$row[id]>$row[title] [$row[id]] ";    
    if($row["service_type_id"]==2)print"[**has connector]";
}
print"</select> n=" . $result->num_rows . "</td>";
print"
<tr><td>
    <font size='2'><i>Or paste your own EOL resource URL</i></font><br>
    <input type='text' id='txt2' size='80' onClick='erase()' style='font-size : small; font-family : Arial; background-color : Aqua;'>
    <br><a href='javascript:transform(1)' id='downl'>Download</a>
</td></tr>
<tr><td>
    <input id='what_eval' type='radio' name='what' value='evaluate'>Statistics
    <input id='what_eval_spm' type='radio' name='what' value='evaluate_spm'>With SPM breakdown
    
    &nbsp;&nbsp;&nbsp;
    <input id='what_tran' type='radio' name='what' value='transform' checked>Transform to GNI TCS
    &nbsp;&nbsp;&nbsp;
    <input type='button' value='Proceed &gt;&gt;' onClick='transform(0)'>
</td></tr>
<tr><td>
    <input type='button' value='Validate EOL Resource' onClick='check_url()'> 
</td></tr>
</table>";
?>

<form target='new_window' name='validator_form' action='http://services.eol.org/validator/index.php' method='post'>
<input type='hidden' name='file_url'>
<input type='hidden' name='Submit'>
</form>

<?php
$path_parts = pathinfo(__FILE__);
$temp = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];  
$temp = str_ireplace($path_parts["basename"], "EOL2GNI.php", $temp);
$temp .= "?url=";
print "<i><font size='2'>$temp</font></i>";
?>

</body>
</html>