<?php
//<!-- <meta http-equiv="content-type" content="text/html; charset=utf-8" /> -->
//include_once(dirname(__FILE__) . "/../../config/environment.php");
require("initialize.php");


//require_once 'Excel/reader.php';
require_once('../../vendor/Excel/reader.php');

$data = new Spreadsheet_Excel_Reader();

//$data->setOutputEncoding('CP1251');
$data->setOutputEncoding('UTF-8');

$fn = get_val_var('fn');

/* this can either be url or dir path
$fn = 'http://mydomain.org/eol.xls';
$fn = 'eol.xls';
*/

$newfile = time();
$newfile = "temp/" . $newfile . ".xls";

if($fn != ""){}
elseif(isset($_FILES["file_upload"]["type"]))
{    
    if($_FILES["file_upload"]["type"] == "application/vnd.ms-excel") 
    {
        if ($_FILES["file_upload"]["error"] > 0)
        {
            //echo "Return Code: " . $_FILES["file_upload"]["error"] . "<br />";
        }
        else
        {
            /*
            echo "Upload: " . $_FILES["file_upload"]["name"] . "<br />";
            echo "Type: " . $_FILES["file_upload"]["type"] . "<br />";
            echo "Size: " . ($_FILES["file_upload"]["size"] / 1024) . " Kb<br />";
            echo "Temp file: " . $_FILES["file_upload"]["tmp_name"] . "<br />";
            */
            
            $fn = "temp/" . $_FILES["file_upload"]["name"];
            
            /*
            if (file_exists($fn))//echo $_FILES["file_upload"]["name"] . " already exists. ";
            else
              {                
                  move_uploaded_file($_FILES["file_upload"]["tmp_name"] , $fn);
                  //echo "Stored in: " . "upload/" . $_FILES["file_upload"]["name"];
              }
            */
            move_uploaded_file($_FILES["file_upload"]["tmp_name"] , $fn);
        }
    }
    else exit("<hr>Invalid file. <br> <a href='javascript:history.go(-1)'> &lt;&lt;Go back</a>");
}
else exit("<hr>Please enter a URL or browse a file to continue. <br> <a href='javascript:history.go(-1)'> &lt;&lt;Go back</a>");

if (!copy($fn, $newfile))exit("<hr>Failed to copy file. <br> <a href='javascript:history.go(-1)'> &lt;&lt;Go back</a>");


/* $newfile cannot be a url, it has to be a dir path. */
$xls = $data->read($newfile); //$xls = $data->read('test3.xls');

//error_reporting(E_ALL ^ E_NOTICE);

$no_of_sheets = count_sheets(); //echo "number of sheets: $no_of_sheets <br>";
//exit;

require("value_assignment.php");    //array assignement
//print"<p><hr>start display<hr><p>";
//require("value_display.php");    //for debugging //exit;
//header('Content-type: text/xml');    

$user_title = $data->sheets[2]['cells'][2];    //this is to get the 24 user 'title if different'
//print sizeof($user_title); print $user_title[6]; //exit;
//print_r($data); //print_r($data->formatRecords);

//##############################################################################################################################
//start buildup of xml files
//print"<hr>";

$dc_source='';

$temp = "<?xml version='1.0' encoding='utf-8' ?><response
    xmlns='http://www.eol.org/transfer/content/0.2'
    xmlns:xsd='http://www.w3.org/2001/XMLSchema'
    xmlns:dc='http://purl.org/dc/elements/1.1/'
    xmlns:dcterms='http://purl.org/dc/terms/'
    xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'
    xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'
    xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'
    xsi:schemaLocation='http://www.eol.org/transfer/content/0.2 http://services.eol.org/schema/content_0_2.xsd'>";
$temp .= start();
$temp .= "</response>";

$validate = get_val_var('validate');
//print $validate; //exit;


if($validate == 'on')
{    
    $path_parts = pathinfo(__FILE__);
    $temp = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];  
    $temp = str_ireplace($path_parts["basename"], "", $temp);
    //print"<i><font size='2'>$temp</font></i>"; exit;

    //$fn = "http://mydomain.org/eol_php_code/applications/xls2eol/" . $newfile . "";
    $fn = $temp . $newfile . "";    
    $fn = $temp . "process.php?fn=" . urlencode($fn) . "";    
    
    print"
    <i>Transformation done.</i> <p>
    <form name='validator_form' action='http://services.eol.org/validator/index.php/index.php' method='post'>
    <input type='hidden' size='30' name='file_url' value='$fn'>
    <input type='submit' value='Click here to Validate >> '>
    </td>    
    </form>
    <p><a href='javascript:history.go(-1)'> &lt;&lt; Back to menu</a>
    ";
    
    //Please wait. Forwarded to validation...    ";     
    exit;    
    /*
    <META HTTP-EQUIV='Refresh' Content='$secs; URL=$url_str'>    
    exit; 
    */
    
    ?>
    <script language="javascript1.2">document.forms.validator_form.submit()</script>
    <?php
    exit;
}

header('Content-type: text/xml');    
print $temp;

function start()
{
    global $sheet;        
    global $dc_source;
    
    //$fields = $sheet[2]; for ($i=0; $i <= sizeof($fields)-1; $i++){}
    
    $unique_taxon = array_unique($sheet[2]['Taxon Name']);    //print_r($unique_taxon);
    $unique_taxon = fix_index($unique_taxon);                //print_r($unique_taxon); //print sizeof($unique_taxon) . "<hr>";
    
    $xml_taxon="";
    $taxon_cnt=0;
    for ($i=0; $i <= sizeof($unique_taxon)-1; $i++) 
    {//start main loop
        if(isset($unique_taxon[$i]))
        {
            $taxon_cnt++;
            //print "<br>" . $unique_taxon[$i] . "" ;            
            $xml_taxon .= "<taxon>";                        
            $xml_taxon .= make_Taxon($unique_taxon[$i],$taxon_cnt);                    
            
            
            $xml_taxon .= make_DO($unique_taxon[$i]);                                
            $xml_taxon .= make_DO_multimedia($unique_taxon[$i]);                                
            $xml_taxon .= "</taxon>";                        
        }                
    }//end main loop
    //print"<hr>";
    //print $xml_taxon;
    return $xml_taxon;    
}


function make_DO_multimedia($taxon)
{
    global $sheet;
        
    $fields = $sheet[4]['Taxon Name'];    //Multimedia
    $m="";            
    for ($i=0; $i <= sizeof($fields)-1; $i++) //loop to $sheet[4]['Taxon Name'] and check if there are equal $taxon
    {
        if($taxon == @$sheet[4]['Taxon Name'][$i])
        {
                    
            $m .= "
            <dataObject>
            <dc:identifier>" . "mm_" . str_ireplace(" ", "_", utf8_encode($sheet[4]['Taxon Name'][$i])) . "_" . $i . "</dc:identifier>";

            $temp = get_DataType($sheet[4]['Data Type'][$i]);                        
            if($temp != ''){$m .= "<dataType>" . $temp . "</dataType>";}
            

            
            if($sheet[4]['MIME Type'][$i] != '')$m .= "<mimeType>" . $sheet[4]['MIME Type'][$i] . "</mimeType>";
        
            //$m .= get_agents($sheet[4]['Attribution Code'][$i]);    //old way
            $m .= get_agents($sheet[4]['Contributor Code'][$i]);    //old way
            
            if($sheet[4]['DateCreated'][$i] != '')$m .= "<dcterms:created>" . $sheet[4]['DateCreated'][$i] . "</dcterms:created>";            
            if($sheet[4]['DateModified'][$i] != '')$m .= "<dcterms:modified>" . $sheet[4]['DateModified'][$i] . "</dcterms:modified>";            
            if($sheet[4]['Caption'][$i] != '')$m .= "<dc:title>" . $sheet[4]['Caption'][$i] . "</dc:title>";            
            if($sheet[4]['Language'][$i] != '')$m .= "<dc:language>" . $sheet[4]['Language'][$i] . "</dc:language>";
            
            $m .= get_attribution($sheet[4]['Attribution Code'][$i]);                                                
            
            
            if($sheet[4]['Audience'][$i]!='')$m .= "<audience>" . $sheet[4]['Audience'][$i] . "</audience>";
            
            if($sheet[4]['Source URL'][$i] != '')$m .= "<dc:source>" . $sheet[4]['Source URL'][$i] . "</dc:source>";
            if($sheet[4]['Media URL'][$i] != '')$m .= "<mediaURL>" . $sheet[4]['Media URL'][$i] . "</mediaURL>";
            if($sheet[4]['Thumbnail URL'][$i] != '')$m .= "<thumbnailURL>" . $sheet[4]['Thumbnail URL'][$i] . "</thumbnailURL>";
            if($sheet[4]['Location'][$i] != '')$m .= "<location>" . $sheet[4]['Location'][$i] . "</location>";

            /*            
            <geo:Point>
                <geo:lat>55.701</geo:lat>
                <geo:long>12.552</geo:long>
            </geo:Point>
            */
            
            /* replaced by get_referece()
            $m .= "<reference>" . $sheet[4]['BibliographicCitation'][$i] . "</reference>";            
            */
            
            //to be done
            if($sheet[4]['Reference Code'][$i] != '')$m .= get_referece($sheet[4]['Reference Code'][$i],"Reference Code");    //for mm
            
            
            $m .= "</dataObject>";
        
        }
    }

    return $m;
}//end make_DO_multimedia


function make_DO($taxon)
{
    global $sheet;
    global $DO_text;
    global $DO_text_title;
    global $user_title;
    
    global $dc_source;
    
    //$fields = $sheet[2]['DataObject ID'];    //Text descriptions
    $fields = $sheet[2]['Taxon Name'];    //Text descriptions
    
    //print"<br>";    
    //print "<hr> sizeof sheet2: " . sizeof($fields); //exit;
    $m="";        
    
    for ($i=0; $i <= sizeof($fields)-1; $i++) //
    {
        if($taxon == @$sheet[2]['Taxon Name'][$i])
        {
            //print $sheet[2]['Associations'][$i] . " ";            

            for ($j=0; $j <= sizeof($DO_text)-1; $j++)
            {
                $str = trim($DO_text[$j]);
                $title = trim($DO_text_title[$j]);
                if($sheet[2][$str][$i] != "")
                {
                    $m .= "
                    <dataObject>
                    <dc:identifier>do_" . str_ireplace(" ", "_", utf8_encode($sheet[2]['Taxon Name'][$i])) . "_" . $j . "</dc:identifier>
                    
                    <dataType>http://purl.org/dc/dcmitype/Text</dataType>
                    <mimeType>text/html</mimeType>";
                    
                    //$m .= get_agents($sheet[2]['Attribution Code'][$i]);    //old way
                    $m .= get_agents($sheet[2]['Contributor Code'][$i]);        
                    
                    if($sheet[2]['DateCreated'][$i]  != '')$m .= "<dcterms:created>" . $sheet[2]['DateCreated'][$i] . "</dcterms:created>";
                    if($sheet[2]['DateModified'][$i] != '')$m .= "<dcterms:modified>" . $sheet[2]['DateModified'][$i] . "</dcterms:modified>";
                    
                    //$jj = $j+7; //withouth 'audience' in sheet 'text descriptions'
                    $jj = $j+8;
                    if(@$user_title[$jj] == 'Title if different')    $m .= "<dc:title>" . $title . "</dc:title>";
                    else                                            $m .= "<dc:title>" . @$user_title[$jj] . "</dc:title>";
                    
                    
            
                    $m .= get_attribution($sheet[2]['Attribution Code'][$i]);                        
            
                    if($sheet[2]['Audience'][$i]) $m .= "<audience>" . $sheet[2]['Audience'][$i] . "</audience>";
                    if($dc_source != '')$m .= "<dc:source>"     . $dc_source     . "</dc:source>";
                    //$m .= "<dc:source></dc:source>";


//$desc = utf8_encode($sheet[2][$str][$i]);
$desc = $sheet[2][$str][$i];

/*
$desc = str_ireplace('ì', '&#147;', $desc);
$desc = str_ireplace('î', '&#148;', $desc);
$desc = str_ireplace('í', '&#146;', $desc);
*/
//$desc = str_ireplace("‚", "&#" . ord("‚") . ";", $desc);
/*
$desc = str_ireplace('ì', '&#116;', $desc);
$desc = str_ireplace('î', '&#39;', $desc);
$desc = str_ireplace('í', '&#39;', $desc);
*/
//$desc = utf8_decode($desc);



                    $m .= "<subject>http://rs.tdwg.org/ontology/voc/SPMInfoItems#" . $str . "</subject>
                    <dc:description>" . $desc . "</dc:description>
                    ";        
                    
                    /* replaced by Reference Code                
                    if($sheet[2]['DataObject ID'][$i] != '')
                    {    $m .= get_referece($sheet[2]['DataObject ID'][$i],"DataObject ID");    //for DO
                    }
                    */                    
                    
                    if($sheet[2]['Reference Code'][$i] != '')$m .= get_referece($sheet[2]['Reference Code'][$i],"Reference Code");    //for DO

                    $m .= "</dataObject>";                    
                }
            }            
        }
    }
    return $m;
}//end make_DO


function get_synonym($taxon)
{
    global $sheet;
    $fields = $sheet[7]['Taxon Name'];    //Synonyms
    $str="";
    for ($i=0; $i <= sizeof($fields)-1; $i++) 
    {
        if($taxon == @$fields[$i])
        {
            $str .= "<synonym relationship='" . $sheet[7]['Relationship'][$i] . "'>" . utf8_encode($sheet[7]['Synonym'][$i]) . "</synonym>";                       
        }
    }            
    return $str;        
}


function get_comname($taxon)
{
    global $sheet;
    $fields = $sheet[6]['Taxon Name'];    //More common names (optional)
    $str="";
    for ($i=0; $i <= sizeof($fields)-1; $i++) 
    {
        if($taxon == @$fields[$i])
        {
            $str .= "<commonName xml:lang='" . $sheet[6]['Language'][$i] . "'>" . $sheet[6]['Common Name'][$i] . "</commonName>";            
        }
    }            
    return $str;        
}


function get_referece($code,$what_code)//dataobject id
{
    /*
    $what_code = 'DataObject ID' or 'Reference Code'    
    */    
    //print"<hr>$code - $what_code <br>";    
    //exit;
    
    global $sheet;
    $fields = $sheet[3][$what_code];    //References
    
    if($what_code == "DataObject ID")
    {
        $ref_code_list[0] = $code;
    }        
    elseif($what_code == "Reference Code")
    {
        $comma_separated = trim($code);
        $comma_separated = str_ireplace(" ", "", $comma_separated);            
        $ref_code_list = explode(",", $comma_separated);                
    }
    //print sizeof($ref_code_list);    

    $str="";        
    for ($m=0; $m <= sizeof($ref_code_list)-1; $m++)         
    {
        //print "x";
        for ($i=0; $i <= sizeof($fields)-1; $i++) 
        {
            //print "y";
            if($ref_code_list[$m] == @$fields[$i])
            {
                
                if($sheet[3]['Bibliographic Citation'][$i] != '')
                {
            
                    $arr = array('BICI', 'CODEN', 'DOI', 'EISSN', 'Handle', 'ISSN', 'ISBN', 'LSID', 'OCLC', 'SICI', 'URL', 'URN');        
                    $str .= "<reference ";
                    for ($j=0; $j <= sizeof($arr)-1; $j++) 
                    {    
                        $temp_index = $arr[$j];
                        $temp = $sheet[3][$temp_index][$i];            
                        if($temp != ''){$str .= trim(strtolower($temp_index)) . "='" . htmlentities(trim($temp)) . "' ";}
                    }        
                    $str .= "><![CDATA[" . $sheet[3]['Bibliographic Citation'][$i] . "]]></reference>";                                        
                }                
            }
        }            
    }
    
    //exit;    
    /* working well
    $str="";
    for ($i=0; $i <= sizeof($fields)-1; $i++) 
    {
        if($code == @$fields[$i])
        {
            $arr = array('BICI', 'CODEN', 'DOI', 'EISSN', 'Handle', 'ISSN', 'ISBN', 'LSID', 'OCLC', 'SICI', 'URL', 'URN');        
            $str .= "<reference ";
            for ($j=0; $j <= sizeof($arr)-1; $j++) 
            {    $temp_index = $arr[$j];
                $temp = $sheet[3][$temp_index][$i];            
                if($temp != ''){$str .= strtolower($temp_index) . "='" . $temp . "' ";}
            }        
            $str .= ">" . $sheet[3]['Bibliographic Citation'][$i] . "</reference>";                        
        }
    }        
    */

        
    return $str;
}//end get_ref

function get_agents($code)
{
    global $sheet;    
    $str="";        
    //$comma_separated = trim($sheet[0]["Contributor Code"][$i]);
    $comma_separated = trim($code);
    $comma_separated = str_ireplace(" ", "", $comma_separated);            
    $arr = explode(",", $comma_separated);
    for ($j=0; $j <= sizeof($arr)-1; $j++) 
    {
        $k = array_search($arr[$j],$sheet[0]['Code']);
        if(is_int($k))
        {    $str .= "<agent";
            if($sheet[0]['Homepage'][$k] != "")    $str .= " homepage='"   . $sheet[0]['Homepage'][$k] . "'";
            if($sheet[0]['Logo URL'][$k] != "")    $str .= " logoURL='"    . $sheet[0]['Logo URL'][$k] . "'";
            if($sheet[0]['Role'][$k]     != "")    $str .= " role='"       . strtolower($sheet[0]['Role'][$k]) . "'";
            $str .= ">" . $sheet[0]['Display Name'][$k] . "</agent>";
        }                
    }            
    return $str;
}


function get_agents_oldx($code)    //not being used
{
    global $sheet;
    $fields = $sheet[1]['Code'];    //Attributions
    
    $str="";
    $i = array_search($code,$fields);
    if(is_int($i))
    {
        /* working well - contributon codes 1 - 5
        //$sheet[1]['Contributor 1 code']     = array();
        //$sheet[1]['Contributor 2 code']     = array();
        //$sheet[1]['Contributor 3 code']     = array();
        //$sheet[1]['Contributor 4 code']     = array();
        //$sheet[1]['Contributor 5 code']     = array();        
        for ($j=1; $j <= 5; $j++)
        {
            $temp = $sheet[1]["Contributor $j code"][$i];
            if($temp != '')
            {    $k = array_search($temp,$sheet[0]['Code']);
                if(is_int($k))
                {    $str .= "<agent ";
                    if($sheet[0]['Homepage'][$k] != "")    $str .= "homepage='"     . $sheet[0]['Homepage'][$k] . "' ";
                    if($sheet[0]['Logo URL'][$k] != "")    $str .= "logoURL='"     . $sheet[0]['Logo URL'][$k] . "' ";
                    if($sheet[0]['Role'][$k]     != "")    $str .= "role='"         . strtolower($sheet[0]['Role'][$k]) . "' ";
                    $str .= ">" . $sheet[0]['Display Name'][$k] . "</agent>";
                }
            }
        }        
        */
        if($sheet[1]["Contributor Code"][$i] != '')
        {
            $comma_separated = trim($sheet[1]["Contributor Code"][$i]);
            $comma_separated = str_ireplace(" ", "", $comma_separated);            
            $arr = explode(",", $comma_separated);
            for ($j=0; $j <= sizeof($arr)-1; $j++) 
            {
                $k = array_search($arr[$j],$sheet[0]['Code']);
                if(is_int($k))
                {    $str .= "<agent";
                    if($sheet[0]['Homepage'][$k] != "")    $str .= " homepage='"     . $sheet[0]['Homepage'][$k] . "'";
                    if($sheet[0]['Logo URL'][$k] != "")    $str .= " logoURL='"     . $sheet[0]['Logo URL'][$k] . "'";
                    if($sheet[0]['Role'][$k]     != "")    $str .= " role='"         . strtolower($sheet[0]['Role'][$k]) . "'";
                    $str .= ">" . $sheet[0]['Display Name'][$k] . "</agent>";
                }                
            }            
        }                    
    }
    return $str;
}

function get_license($license)
{   
    switch ($license) 
    {   case "Public Domain":   $license='http://creativecommons.org/licenses/publicdomain/'; break;
        case "CC-BY":           $license='http://creativecommons.org/licenses/by/3.0/'; break;
        case "CC-BY-NC":        $license='http://creativecommons.org/licenses/by-nc/3.0/'; break;
        case "CC-BY-SA":        $license='http://creativecommons.org/licenses/by-sa/3.0/'; break;
        case "CC-BY-NC-SA":     $license='http://creativecommons.org/licenses/by-nc-sa/3.0/'; break;
        default:                $license='';
    }        
    return $license;
}

function get_DataType($datatype)
{   
    switch ($datatype) 
    {   case "Video":    $datatype='http://purl.org/dc/dcmitype/MovingImage'; break;
        case "Sound":    $datatype='http://purl.org/dc/dcmitype/Sound'; break;
        case "Image":    $datatype='http://purl.org/dc/dcmitype/StillImage'; break;
        case "Text":     $datatype='http://purl.org/dc/dcmitype/Text'; break;
        default:         $datatype='';
    }        
    return $datatype;
}


function get_attribution($code)
{    
    global $sheet;
    $fields = $sheet[1]['Code'];    //Attributions
    //print"<br>";    
    //print "<hr> sizeof sheet5: " . sizeof($fields); print "<hr> sizeof sheet5: " . sizeof($sheet[5]); exit;
    
    $str="";
    $i = array_search($code,$fields); //print "i = [$i]";
    if(is_int($i))
    {
        $license = $sheet[1]['License'][$i];
        $license = get_license($license);             
        if($license != ''){$str .= "<license>" . $license . "</license>";}    

        if($sheet[1]['RightsStatement'][$i] != '')$str .= "<dc:rights>" . $sheet[1]['RightsStatement'][$i] . "</dc:rights>";
        if($sheet[1]['RightsHolder'][$i] != '')$str .= "<dcterms:rightsHolder>" . $sheet[1]['RightsHolder'][$i] . "</dcterms:rightsHolder>";
        if($sheet[1]['BibliographicCitation'][$i] != '')$str .= "<dcterms:bibliographicCitation>" . $sheet[1]['BibliographicCitation'][$i] . "</dcterms:bibliographicCitation>";
    }
    
    return $str;
}//end get_attribution()


function make_Taxon($taxon,$taxon_cnt)
{
    global $sheet;
    global $dc_source;

    $dc_source = '';
        
    $fields = $sheet[5]['Taxon Name'];    //Taxon Information (optional)
    //print"<br>";    
    //print "<hr> sizeof sheet5: " . sizeof($fields); print "<hr> sizeof sheet5: " . sizeof($sheet[5]); exit;
    $m="";        
    
    /*working well
    for ($i=0; $i <= sizeof($fields)-1; $i++) //loop to $sheet[5]['Taxon Name'] and check if there are equal $sheet[3]['Taxon Name']
    {
        if($taxon == @$fields[$i])
        {
        }
    }
    */
    
    $i = array_search($taxon,$fields); //print "i = [$i]";
    if(is_int($i))
    {    
        $m .= "<dc:identifier>species_" . $taxon_cnt . "</dc:identifier>";
        
        if($sheet[5]['Source URL'][$i] != '')
        {
            $m .= "<dc:source>"     . $sheet[5]['Source URL'][$i]     . "</dc:source>";
            $dc_source = $sheet[5]['Source URL'][$i];
        }
        
        if($sheet[5]['Kingdom'][$i] != '')$m .= "<dwc:Kingdom>" . $sheet[5]['Kingdom'][$i]         . "</dwc:Kingdom>";
        if($sheet[5]['Phylum'][$i] != '')$m .= "<dwc:Phylum>"     . $sheet[5]['Phylum'][$i]         . "</dwc:Phylum>";
        if($sheet[5]['Class'][$i] != '')$m .= "<dwc:Class>"     . $sheet[5]['Class'][$i]         . "</dwc:Class>";
        if($sheet[5]['Order'][$i] != '')$m .= "<dwc:Order>"     . $sheet[5]['Order'][$i]         . "</dwc:Order>";
        if($sheet[5]['Family'][$i] != '')$m .= "<dwc:Family>"     . $sheet[5]['Family'][$i]         . "</dwc:Family>";
        if($sheet[5]['Genus'][$i] != '')$m .= "<dwc:Genus>"     . $sheet[5]['Genus'][$i]         . "</dwc:Genus>";
        
        $m .= "<dwc:ScientificName>" . $sheet[5]['Scientific Name'][$i] . "</dwc:ScientificName>";
        
    }
    else
    {    
        $m .= "
        <dc:identifier>species_" . $taxon_cnt . "</dc:identifier>
        <dwc:ScientificName>" . $taxon . "</dwc:ScientificName>";
        
        /*
        <dc:source></dc:source>
        <dwc:Kingdom></dwc:Kingdom>
        <dwc:Phylum></dwc:Phylum>
        <dwc:Class></dwc:Class>
        <dwc:Order></dwc:Order>
        <dwc:Family></dwc:Family>
        <dwc:Genus></dwc:Genus>        
        */
        
    }


    
    
    $m .= get_comname($taxon);
    $m .= get_synonym($taxon);
        
    if($sheet[5]['Reference Code'][$i] != '')$m .= get_referece($sheet[5]['Reference Code'][$i],"Reference Code");
            
    
    //print "<hr>"; print_r($fields); //exit;
    //print "<hr>"; print_r($sheet[5]['Kingdom']); //exit;
    


    return $m;

}

function fix_index($a) 
{     
    /* this fixes the index from 0 to n - step1 */
    
    krsort($a);
    $len = key($a); //print $len;
    
    $b=array();
    
    $j = 0; 
    //print "<hr> -- "; print count($a); print "<hr> -- ";
    for ($i = 0; $i <= $len; $i++) 
    { 
        if (array_key_exists($i,$a))
        {
            if (trim($a[$i]) != "") { $b[$j++] = $a[$i]; }         
        }
    }     
    return $b; 
}

function count_sheets()
{
    global $data;
    $cnt=0;
    for ($i=0; $i <= 12; $i++) 
    {
        if( @$data->sheets[$i]['numRows'] == 0    or
            @$data->sheets[$i]['numCols'] == 0        
          ){break;}
        else {$cnt++;}
        /*    
        print "rows: " . $data->sheets[$i]['numRows'];
        print "cols: " . $data->sheets[$i]['numCols'];
        print "<br>";            
        */
    }
    $cnt = $cnt - 1;
    return $cnt;        
}


function reorder_index($a,$start) 
{     
    /* this actually gets array elements starting from $start and re-orders the index key */
    
    $len = sizeof($a);
    $b=array();
    
    $start_row = $start;
    
    $j = 0; 
    for ($i = $start_row; $i < $len; $i++) 
    { 
        //if($i > 0)
        //{ 
            $b[$j] = $a[$i]; 
            $j++;
        //}         
    }     
    return $b;     
}

function get_val_var($v)
{
    if     (isset($_GET["$v"]))$var=$_GET["$v"];
    elseif (isset($_POST["$v"]))$var=$_POST["$v"];    
    if(isset($var)) return $var;
    else return NULL;
}
?>