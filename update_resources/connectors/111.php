<?php
/* connector for Lichens database - Field Museum Grainger EMU

estimated execution time: 11-13 secs -> for the text XML dump of 10 species.

This connector reads an XML dump from provider. Some fields for the image objects are taken from
the image detail page of the provider, so this connector also does some scraping.

*/
$timestart = microtime(1);

//$species_page_url = "http://emuweb.fieldmuseum.org/arthropod/InsDisplay.php?irn="; //not working
$species_page_url = "http://emuweb.fieldmuseum.org/botany/botanytaxDisplay.php?irn=";
$image_url        = "http://emuweb.fieldmuseum.org/web/objects/common/webmedia.php?irn=";

include_once(dirname(__FILE__) . "/../../config/environment.php");

//$file = "http://localhost/eol_php_code/applications/content_server/resources/FMNH_2010_03_23.xml";
//$file = "files/FieldMuseumLichen/FMNH_2010_03_23.xml"; //don't use relative path

$file = dirname(__FILE__) . "/files/FieldMuseumLichen/FMNH_2010_03_23.xml";//always use absolute path
$xml = simplexml_load_file($file);

$i=0;
$wrap="\n";
//$wrap="<br>";
print "taxa count = " . count($xml) . "$wrap";

$resource = new Resource(111);

$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
if(!($OUT = fopen($old_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
  return;
}

$str = "<?xml version='1.0' encoding='utf-8' ?>\n";
$str .= "<response\n";
$str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
$str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
$str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
$str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
$str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
$str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
$str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
$str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
fwrite($OUT, $str);

$do_count=0;
foreach($xml->taxon as $t)
{
    $i++;
    print "$i ";

    //if($i >= 1 and $i <= 100)//debug
    if(true)//true operation
    {
        if(true)//true operation
        {
            /* no namespaces used
            $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
            $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
            */

            $identifier = "fieldmuseum_lichen_" . Functions::import_decode($t->EOL_Identifier);
            $source     = $species_page_url . $t->EOL_Identifier;
            $kingdom    = Functions::import_decode($t->ClaKingdom);
            $phylum     = Functions::import_decode($t->ClaPhylum);
            $class      = Functions::import_decode($t->ClaClass);
            $order      = Functions::import_decode($t->ClaOrder);
            $family     = Functions::import_decode($t->ClaFamily);
            $genus     = Functions::import_decode($t->ClaGenus);
            $sciname    = Functions::import_decode($t->ClaScientificName);

            $taxonParameters = array();
            $taxonParameters["identifier"]      = utf8_encode($identifier);
            $taxonParameters["source"]          = utf8_encode($source);
            $taxonParameters["kingdom"]         = Functions::import_decode($kingdom);
            $taxonParameters["phylum"]          = Functions::import_decode($phylum);
            $taxonParameters["class"]           = Functions::import_decode($class);
            $taxonParameters["order"]           = Functions::import_decode($order);
            $taxonParameters["family"]          = Functions::import_decode($family);
            $taxonParameters["genus"]           = Functions::import_decode($genus);
            $taxonParameters["scientificName"]  = Functions::import_decode($sciname);

            /* no synonyms
            $taxonParameters["synonyms"] = array();
            foreach($t->synonym as $syn)
            {
                $taxonParameters["synonyms"][] = new \SchemaSynonym(array("synonym" => $syn, "relationship" => $url = $syn["relationship"]));
            }
            */

            //start process dataObjects =====================================================================
            $taxonParameters["dataObjects"] = array();
            $dataObjects = array();

            $subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
            $dataType = "http://purl.org/dc/dcmitype/Text";
            $mimeType = "text/html";

            $rightsHolder = $t->dc_rights;

            $do_agents = array();
            $do_agents[] = array("name"=>"Robert L�cking"                        , "role"=>"compiler");
            $do_agents[] = array("name"=>"Audrey Sica"                           , "role"=>"compiler");
            $do_agents[] = array("name"=>"Joanna McCaffrey"                      , "role"=>"compiler");
            $do_agents[] = array("name"=>"Grainger Foundation (PI R. L�cking)"   , "role"=>"project");

            //print"<hr>$t->PhysDescription<hr>";

            $title = "";
            if($desc = $t->EOL_GeneralDescription)
            {   $do_count++;
                $do_identifier = $identifier . "_GenDesc";
                $dataObjects[] = get_data_object($desc,$do_identifier,$subject,$dataType,$mimeType,$title,$source,$do_agents,$rightsHolder);
            }

            $title = "Physical Description";
            if($desc = $t->EOL_PhysDescription)
            {   $do_count++;
                $do_identifier = $identifier . "_PhysDesc";
                $dataObjects[] = get_data_object($desc,$do_identifier,$subject,$dataType,$mimeType,$title,$source,$do_agents,$rightsHolder);
            }

            $title = "Type specimen information";
            if($desc = $t->PhysDescription)
            {   $do_count++;
                $do_identifier = $identifier . "_TypeSpecimen";
                $dataObjects[] = get_data_object($desc,$do_identifier,$subject,$dataType,$mimeType,$title,$source,$do_agents,$rightsHolder);
            }

            //stat images =================================================================================================
            $subject="";
            $dataType = "http://purl.org/dc/dcmitype/StillImage";
            $mimeType = "";
            $title = "";

            //print"<hr>";
            //<Img_1> <Caption_1> <mimeType_1>

            for ($j = 1; $j <= 10; $j++)
            {
                $img_str="Img_$j"; $cap_str="Caption_$j"; $mim_str="mimeType_$j";
                //print $t->$img_str . "<br>";
                if(trim($t->$img_str) != "")
                {

                    $mediaURL = str_ireplace("(", "", $t->$img_str);

                    $source = $mediaURL;

                    //start get rights and publisher from page
                    $arr = parse_image_page($source);
                    $rightsHolder   = trim($arr[0]);
                    $publisher      = trim($arr[1]);
                    //end get rights and publisher from page



                    $id = parse_url($mediaURL, PHP_URL_QUERY);
                    $id = trim(substr($id,stripos($id,"=")+1,strlen($id)));
                    $mediaURL = $image_url . $id;

                    $desc  = Functions::import_decode($t->$cap_str);
                    $mimeType = Functions::import_decode($t->$mim_str);

                    $do_count++;
                    //$do_identifier = $identifier . "_" . $do_count;
                    $do_identifier = $mediaURL;

                    $do_agents = array();
                    $do_agents[] = array("name"=>$publisher , "role"=>"publisher");

                    $dataObjects[] = get_data_object($desc,$do_identifier,$subject,$dataType,$mimeType,$title,$source,$do_agents,$rightsHolder,$mediaURL);

                }
            }

            //print"<hr>";
            //print"<pre>";print_r($img);print"</pre>";
            //end images =================================================================================================

            foreach($dataObjects as $k => $v)
            {
                $taxonParameters["dataObjects"][] = new \SchemaDataObject($v);
                unset($v);
            }
            //end process dataObjects =====================================================================

            ///////////////////////////////////////////////////////////////////////////////////
            $taxa = array();
            $taxa[] = new \SchemaTaxon($taxonParameters);

            //$new_resource_xml = SchemaDocument::get_taxon_xml($taxa);
            $str='';
            foreach($taxa as $tax)
            {
                $str .= $tax->__toXML();
            }
            fwrite($OUT, $str);

            print utf8_decode($sciname) . $wrap;

            ///////////////////////////////////////////////////////////////////////////////////
        }//if($do > 0)
    }
    else{break;}
}

$str = "</response>";
fwrite($OUT, $str);
fclose($OUT);


$elapsed_time_sec = microtime(1)-$timestart;
echo "$wrap";
echo "elapsed time = $elapsed_time_sec sec              $wrap";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   $wrap";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr $wrap";

echo "$wrap$wrap Done processing.";
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################

//==========================================================================================
function get_tabular_data($str)
{
    global $wrap;
    /*
    <table>
        <tr>
            <td>field 1</td>
            <td>value 1</td>
        </tr>
        <tr>
            <td>field 2</td>
            <td>value 3</td>
        </tr>
    </table>
    */

    $str = str_ireplace('<tr' , "xxx<tr", $str);
    $str = str_ireplace('xxx' , "&arr[]=", $str);
    $str=trim($str);
    $arr=array();
    parse_str($str);
    //print "after parse_str recs = " . count($arr) . "$wrap $wrap";
    $arr_tr = $arr;

    $i=0;

    $rights="";
    $publisher="";
    foreach($arr_tr as $tr)
    {
        $i++;
        $tr = str_ireplace("<td" , "xxx<td"     , $tr);
        $tr = str_ireplace('xxx' , "&arr[]=" , $tr);
        $arr=array();
        parse_str($tr);
        /*
        print "after parse_str recs = " . count($arr) . "$wrap $wrap";
        print"<pre>";print_r($arr);print"</pre>";
        */
        $field = trim(strip_tags($arr[0]));
        $value = trim(strip_tags($arr[1]));
        //print "$field = $value <br>";

        if($field == "Rights:")     $rights = clean_str($value);
        if($field == "Publisher:")  $publisher = clean_str($value);
    }

    //print"<pre>";print_r($return_arr);print"</pre>";

    return array($rights,$publisher);
}

function parse_image_page($file)
{
    if($str = Functions::get_remote_file($file))
    {
        $pos = stripos($str, "Image Only");
        $str = trim(substr($str,$pos,strlen($str)));

        $beg='<table'; $end1='</table>';
        $str = "$beg " . trim(trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""))) . " $end1";

        //print"<hr>$str";

        $arr = get_tabular_data($str);
        return $arr;
    }
    return;
}

function get_data_object($desc,$do_identifier,$subject,$dataType,$mimeType,$title,$source,$do_agents,$rightsHolder,$mediaURL=NULL)
{


    $dataObjectParameters = array();
    $dataObjectParameters["identifier"] = $do_identifier;
    $dataObjectParameters["dataType"]   = $dataType;
    $dataObjectParameters["mimeType"]   = $mimeType;
    $dataObjectParameters["title"]      = $title;
    $dataObjectParameters["language"]      = "en";

    $dataObjectParameters["description"] = $desc;

    if($subject != "")
    {
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = $subject;
        $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);
    }



    //if($mimeType == "text/html")
    //{
        $agents = array();
        foreach($do_agents as $agent)
        {
            $agentParameters = array();
            $agentParameters["role"]     = $agent["role"];
            $agentParameters["homepage"] = "http://emuweb.fieldmuseum.org/botany/botanytaxon.php";
            //$agentParameters["logoURL"]  = $agent["logoURL"];
            //$agentParameters["fullName"] = Functions::import_decode($agent["name"]);
            $agentParameters["fullName"] = utf8_encode($agent["name"]);
            $agents[] = new \SchemaAgent($agentParameters);
        }
        $dataObjectParameters["agents"] = $agents;
    //}

    $dataObjectParameters["license"]       = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
    $dataObjectParameters["source"]        = $source;
    $dataObjectParameters["rightsHolder"]  = Functions::import_decode($rightsHolder);

    /*
    $dataObjectParameters["created"]       = $do->created;
    $dataObjectParameters["modified"]      = $do->modified;


    $dataObjectParameters["thumbnailURL"]  = $do->thumbnailURL;
    $dataObjectParameters["location"]      = Functions::import_decode($do->location);
    */

    if($mimeType != "text/html")$dataObjectParameters["mediaURL"] = $mediaURL;

    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();
    $audienceParameters = array();

    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);

    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);
       ///////////////////////////////////

    return $dataObjectParameters;
}
function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL,$exit_on_first_match=false)    //str = the html block
{
    $beg_len = strlen(trim($beg));
    $end1_len = strlen(trim($end1));
    $end2_len = strlen(trim($end2));
    $end3_len = strlen(trim($end3));
    $end4_len = strlen(trim($end4));
    //print "[[$str]]";

    $str = trim($str);
    $str = $str . "|||";
    $len = strlen($str);
    $arr = array(); $k=0;
    for ($i = 0; $i < $len; $i++)
    {
        if(strtolower(substr($str,$i,$beg_len)) == strtolower($beg))
        {
            $i=$i+$beg_len;
            $pos1 = $i;
            //print substr($str,$i,10) . "<br>";
            $cont = 'y';
            while($cont == 'y')
            {
                if(    strtolower(substr($str,$i,$end1_len)) == strtolower($end1) or
                    strtolower(substr($str,$i,$end2_len)) == strtolower($end2) or
                    strtolower(substr($str,$i,$end3_len)) == strtolower($end3) or
                    strtolower(substr($str,$i,$end4_len)) == strtolower($end4) or
                    substr($str,$i,3) == '|||' )
                {
                    $pos2 = $i - 1;
                    $cont = 'n';
                    $arr[$k] = substr($str,$pos1,$pos2-$pos1+1);
                    //print "$arr[$k] $wrap";
                    $k++;
                }
                $i++;
            }//end while
            $i--;

            //start exit on first occurrence of $beg
            if($exit_on_first_match)break;
            //end exit on first occurrence of $beg

        }
    }//end outer loop
    if($all == "")
    {
        $id='';
        for ($j = 0; $j < count($arr); $j++){$id = $arr[$j];}
        return $id;
    }
    elseif($all == "all") return $arr;
}//end function
function clean_str($str)
{
    $str = str_ireplace("�", "", $str);
    $str = utf8_encode($str);
    $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB"), "", $str);
    return $str;
}
?>