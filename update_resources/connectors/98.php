<?php
exit;
/* connector for hexacorallians
estimated execution time: 7.7 to 8 hrs

run April 6 to correct dc:identifier for dataObject
set to force-harvest April 7

Connector screen scrapes the partner website.

*/
$timestart = microtime(1);

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$wrap = "\n";
//$wrap = "<br>";

$resource = new Resource(98);
print "resource id = " . $resource->id . "$wrap";

$schema_taxa = array();
$used_taxa = array();

$id_list=array();

$total_taxid_count = 0;
$do_count = 0;

$url        ="http://hercules.kgs.ku.edu/hexacoral/anemone2/valid_species.cfm";
$home_url   ="http://hercules.kgs.ku.edu/hexacoral/anemone2/index.cfm";
$form_url   ="http://hercules.kgs.ku.edu/hexacoral/anemone2/valid_species_search.cfm";
$site_url   ="http://hercules.kgs.ku.edu/hexacoral/anemone2/";

$taxa_list = get_taxa_list($url);

$arr_desc_taxa = array();
$arr_categories = array();
$arr_outlinks = array();

print("$wrap count taxa_list = " . count($taxa_list) );

//
//for ($i = 0; $i < 5; $i++)
for ($i = 0; $i < count($taxa_list); $i++)
{
    //main loop
    print "$wrap $wrap";
    print $i+1 . " of " . count($taxa_list) . " id=" . $taxa_list[$i] . " ";
    $validname = $taxa_list[$i];
    //list($id,$image_url,$description,$desc_pic,$desc_taxa,$categories,$taxa,$copyright,$providers,$creation_date,$photo_credit,$outlinks) = process($form_url,$validname);
    $arr = process($form_url,$validname);
    $taxa = $arr[0];
    $dc_source = $arr[1];
    $classification = $arr[2];
    $arr_images = $arr[3];
    $html_skeletons = $arr[4];
    $url_for_skeletons = $arr[5];
    $html_biological_associations = $arr[6];
    $url_for_biological_associations = $arr[7];
    $arr_common_names = $arr[8];
    $arr_references = $arr[9];
    $html_nematocysts = $arr[10];
    $url_for_nematocysts = $arr[11];

    /*
    print"<pre>";
    print_r($html_nematocysts);
    print"</pre>";
    */

    if(trim($taxa) == "")
    {
        print " --blank taxa--";
        continue;
    }

    /*
    $desc_pic = utf8_encode($desc_pic);
    $desc_taxa = utf8_encode($desc_taxa);
    */

    $taxon = str_ireplace(" ", "_", $taxa_list[$i]);

    if(@$used_taxa[$taxon])
    {
        $taxon_parameters = $used_taxa[$taxon];
    }
    else
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = "hexacorals_" . $taxon; //$main->taxid;

        if(isset($classification["Class"][0]))
        {
            $taxon_parameters["kingdom"]    = @$classification["Kingdom"][0];
            $taxon_parameters["phylum"]     = @$classification["Phylum"][0];
            $taxon_parameters["class"]      = @$classification["Class"][0];
            $taxon_parameters["order"]      = @$classification["Order"][0];
            $taxon_parameters["family"]     = @$classification["Family"][0];
            $taxon_parameters["genus"]      = @$classification["Genus"][0];
        }

        $taxon_parameters["scientificName"]= $taxa;
        $taxon_parameters["source"] = $dc_source;

        $taxon_parameters["commonNames"] = array();
        foreach($arr_common_names as $commonname)
        {
            if($commonname)
            {
                $commonname = "<![CDATA[$commonname]]>";
                $taxon_parameters["commonNames"][] = new \SchemaCommonName(array("name" => $commonname, "language" => "en"));
            }
        }

        if(count($arr_references) > 0)
        {
            $references=array();
            //get_str_from_anchor_tag
            //get_href_from_anchor_tag
            $taxon_parameters["references"] = array();

            foreach ($arr_references as $ref)
            {
                $referenceParameters = array();
                $href = get_href_from_anchor_tag($ref);
                $ref = get_str_from_anchor_tag($ref);

                if(substr($ref,0,19)=="Goffredo S., Radeti")$ref="Goffredo S., Radeti J., Airi V., and Zaccanti F., 2005";
                $referenceParameters["fullReference"] = trim($ref);
                //$ref = "<![CDATA[$ref]]>";

                if($href)$referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "url" , "value" => $href));
                $references[] = new \SchemaReference($referenceParameters);
            }
            $taxon_parameters["references"] = $references;
        }
        $used_taxa[$taxon] = $taxon_parameters;
    }

    if(1==1)
    {
        $dc_source = $home_url;
        //$agent_name = $photo_credit;
        //$agent_role = "photographer";

        /* for debugging
        $image_url = "http://127.0.0.1/test.tif";
        $image_url = "http://www.findingspecies.org/indu/images/YIH_13569_MED_EOL.TIFF";
        */

        //start images
        foreach ($arr_images as $value)
        {
            $do_count++;
            //print"<hr> $value[0] ";
            ///*
            $arr_temp=img_href_src($value[0]);
            $dc_source = $site_url . $arr_temp[0];
            $image_url  = $site_url . $arr_temp[1];
            //*/
            $agent_name="";
            $agent_role="";
            $value[1] = strip_tags($value[1]);
            $desc_pic = "Name: $value[1] <br> Reference: $value[2] <br> View: $value[3] <br> Caption: $value[4]";
            $copyright="";

            $data_object_parameters = get_data_object("image",$taxon,$do_count,$dc_source,$agent_name,$agent_role,$desc_pic,$copyright,$image_url,"","");
            $taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_parameters);
        }
        //end images

        //start skeletons
        if($html_skeletons != "")
        {   $do_count++;
            $agent_name = ""; $agent_role = ""; $image_url=""; $copyright="";
            $title="Biology: Skeleton";
            $dc_source = $url_for_skeletons;
            $subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
            $data_object_parameters = get_data_object("text",$taxon,"skeleton",$dc_source,$agent_name,$agent_role,$html_skeletons,$copyright,$image_url,$title,$subject);
            $taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_parameters);
        }
        //end skeletons

        //start biological_associations
        if($html_biological_associations != "")
        {   $do_count++;
            $agent_name = ""; $agent_role = ""; $image_url=""; $copyright="";
            $title="Biological Associations";
            $dc_source = $url_for_biological_associations;
            $subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations";
            $data_object_parameters = get_data_object("text",$taxon,"bio_association",$dc_source,$agent_name,$agent_role,$html_biological_associations,$copyright,$image_url,$title,$subject);
            $taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_parameters);
        }
        //end biological_associations

        //start nematocysts
        if($html_nematocysts != "")
        {   $do_count++;
            $agent_name = ""; $agent_role = ""; $image_url=""; $copyright="";
            $title="Biology: Nematocysts";
            $dc_source = $url_for_nematocysts;
            $subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
            $data_object_parameters = get_data_object("text",$taxon,"nematocyst",$dc_source,$agent_name,$agent_role,$html_nematocysts,$copyright,$image_url,$title,$subject);
            $taxon_parameters["dataObjects"][] = new \SchemaDataObject($data_object_parameters);
        }
        //end

        $used_taxa[$taxon] = $taxon_parameters;

    }//with photos

    //end main loop
}

foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new \SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
if(!($OUT = fopen($old_resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$old_resource_path);
  return;
}
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---

$elapsed_time_sec = microtime(1)-$timestart;
echo "$wrap";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";

echo "$wrap$wrap Done processing.";


//######################################################################################################################
//######################################################################################################################
//######################################################################################################################


function img_href_src($str)
{
    /*
    <A HREF="imagedetail.cfm?imageid=5880&genus=Paranthosactis&species=denhartogi&subgenus=&subspecies=">
    <IMG SRC="images/05851_05900/05880.jpg" BORDER=0 HEIGHT=80 WIDTH=80></a>
    */

    $beg='<A HREF="'; $end1='">';
    $href = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));

    $beg='<IMG SRC="'; $end1='" BORDER=';
    $src = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));

    return array($href,$src);

}

function get_data_object($type,$taxon,$text_id,$dc_source,$agent_name,$agent_role,$description,$copyright,$image_url,$title,$subject)
{

    $dataObjectParameters = array();

    if($type == "text")
    {
        $dataObjectParameters["title"] = $title;

        //start subject
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();

        $subjectParameters["label"] = $subject;

        $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);
        //end subject

        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";
        $dataObjectParameters["mimeType"] = "text/html";
        $dataObjectParameters["source"] = $dc_source;

        $dataObjectParameters["identifier"] = $taxon . "_" . $text_id;

    }
    elseif($type == "image")
    {
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $dataObjectParameters["mimeType"] = "image/jpeg";
        $dataObjectParameters["source"] = $dc_source;
        $dataObjectParameters["mediaURL"] = $image_url;
        $dataObjectParameters["rights"] = $copyright;
        $dc_source ="";
        $dataObjectParameters["identifier"] = $image_url;
    }

    $dataObjectParameters["description"] = $description;
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;

    $dataObjectParameters["rightsHolder"] = "Hexacorallians of the World";
    $dataObjectParameters["language"] = "en";
    //$dataObjectParameters["license"] = "http://creativecommons.org/licenses/publicdomain/";
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";



    //==========================================================================================
    /* working...
    $agent = array(0 => array(     "role" => "photographer" , "homepage" => ""           , $photo_credit),
                   1 => array(     "role" => "project"      , "homepage" => $home_url    , "Public Health Image Library")
                  );
    */

    if($agent_name != "")
    {
        $agent = array(0 => array( "role" => $agent_role , "homepage" => $dc_source , $agent_name) );
        $agents = array();
        foreach($agent as $agent)
        {
            $agentParameters = array();
            $agentParameters["role"]     = $agent["role"];
            $agentParameters["homepage"] = $agent["homepage"];
            $agentParameters["logoURL"]  = "";
            $agentParameters["fullName"] = $agent[0];
            $agents[] = new \SchemaAgent($agentParameters);
        }
        $dataObjectParameters["agents"] = $agents;
    }
    //==========================================================================================
    $audience = array(  0 => array(     "Expert users"),
                        1 => array(     "General public")
                     );
    $audiences = array();
    foreach($audience as $audience)
    {
        $audienceParameters = array();
        $audienceParameters["label"]    = $audience[0];
        $audiences[] = new \SchemaAudience($audienceParameters);
    }
    $dataObjectParameters["audiences"] = $audiences;
    //==========================================================================================
    return $dataObjectParameters;
}

function get_taxa_list($file)
{
    global $wrap;

    $str = Functions::get_remote_file($file);
    $beg='<SELECT name=validname size=1>'; $end1='</SELECT>';
    $str = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));

    $str = str_ireplace('<OPTION value="' , "&arr[]=", $str);
    $arr=array();
    parse_str($str);
    print "after parse_str recs = " . count($arr) . "$wrap $wrap";

    $arr2=array();
    for ($i = 0; $i < count($arr); $i++)
    {
        $temp = "xxx" . $arr[$i];

        $beg='xxx'; $end1='"';
        $sciname = trim(parse_html($temp,$beg,$end1,$end1,$end1,$end1,""));

         /* for debug
        //$sn = "Paranthosactis denhartogi";//has classification
        //$sn = "Zoanthus sociatus";//has skeleton, common names, biological associations
        //$sn = "Favites abdita";//has skeleton
        //$sn = "Abyssopathes lyra";//has images

        $sn = array("Paranthosactis denhartogi", "Zoanthus sociatus", "Favites abdita","Urticina crassicornis","Abyssopathes lyra","Verrillactis paguri","Montastraea annularis");
        //$sn = array("Verrillactis paguri","Urticina crassicornis");
        //$sn = array("Paranthosactis denhartogi", "Zoanthus sociatus");
        //$sn = array("Monactis vestita"); //has all specimens
        //Montastraea annularis  with common names and classification
        //$sn = array("Turbinaria reniformis","Anthemiphyllia patera","Madrepora carolina"); // with encoding errors
        //$sn = array("Urticina crassicornis","Verrillactis paguri"); // has nematocysts

        //$sn = array("Favites abdita");

        if (in_array(trim($sciname), $sn))
        {
            print"$wrap $sciname";
            $arr2["$sciname"]=true;
        }
         */

        // /* regular routine
        print"$wrap $sciname";
        $arr2["$sciname"]=1;
        // */

    }
    $arr = array_keys($arr2);

    // /* regular routine
    array_splice($arr, 0, 1);   //deletes first array element
    array_pop($arr);            //deletes last array element
    // */

    //print"<pre>";print_r($arr);print"</pre>";
    //print"<hr>$str";
    return $arr;
}

function process($url,$validname)
{
    global $wrap;
    $contents = cURL_it($validname,$url);
    if($contents) print "";
    else print "$wrap bad post [$validname] $wrap ";
    $arr = parse_contents($contents);
    return $arr;
}

function get_tabular_data($url,$item)
{   //return;

    global $wrap;
    $table = Functions::get_remote_file($url);

    if      ($item == "synonyms")                   $beg='<TH><B>Authorship</b></TH>';
    elseif  ($item == "references")                 $beg='<th align=left><b>Nomenclature Notes </b></td></th>';
    elseif  ($item == "skeletons")                  $beg='<th>Percent Magnesium</th>';
    elseif  ($item == "biological_associations")    $beg='<TH COLSPAN="2">Algal symbionts</TH>';
    elseif  ($item == "classification")             $beg='<th>Current Classification: Click on a taxon to view its components</th>';
    elseif  ($item == "common_names")               $beg='<TBODY>';
    elseif  ($item == "nematocysts")                $beg='<th width="60">State</th>';
    //elseif  ($item == "specimens")                 $beg='<th>Source </th>';

    if    ($item == "classification")   $end1='</td>'; //$end1='<br> </td>'; //$end1='</tr>';//
    elseif($item == "common_names")     $end1='</TBODY>';
    else                                $end1='</table>';

    $temp = trim(parse_html($table,$beg,$end1,$end1,$end1,$end1,""));

    if($item == "classification" and $temp == "")
    {
        $beg='<TH>Current classification</TH>';
        $end='</td>';
        $temp = trim(parse_html($table,$beg,$end1,$end1,$end1,$end1,""));
    }


    if( $item != "common_names" and
        $item != "specimens"
      ) $temp = substr($temp,5,strlen($temp));//to remove the '</tr>' at the start of the string

    $temp = str_ireplace(array( '<tr class=listrow1 >',
                                '<tr class=listrow2 >',
                                '<tr  class=listrow2  >',
                                '<tr  class="listrow2"  >',
                                '<tr class="listrow1" >',
                                '<tr  class="listrow2"  >',
                                '<tr class="listrow1" >'), '<tr>', $temp);

    $temp = str_ireplace('<TR class="common2">','<tr>',$temp);

    if($item == "specimens")// to fix the weird <tr> withouth ending </tr>
    {
        $temp = str_ireplace('<tr>','</tr><tr>',$temp);
    }

    $temp = str_ireplace('<tr>' , "", $temp);
    $temp = trim(str_ireplace('</tr>' , "***", $temp));

    if($item != "classification")    $temp = substr($temp,0,strlen($temp)-3);//remove last '***'

    $arr = explode("***", $temp);
    $arr_records=array();

    for ($i = 0; $i < count($arr); $i++)
    {
        $str = $arr[$i];
        $str = str_ireplace('<td>' , "", $str);
        $str = trim(str_ireplace('</td>' , "***", $str));
        if($item != "classification") $str = substr($str,0,strlen($str)-3);//remove last '***'

        $str=strip_tags($str,"<a><b><B><br><BR>");

        //$str = htmlspecialchars_decode($str);

        $arr2 = explode("***", $str);

        $arr_records[]=$arr2;
    }

    if($item == "skeletons")//to check if 2nd column from table is NO
    {
        //print"<hr>";
        $temp_arr = $arr_records[0];
        //print trim($temp_arr[1]);
        if(trim($temp_arr[1])=="NO")$arr_records=array();
    }
    //print"<pre>";print_r($arr_records);print"</pre>";

    return $arr_records;
}

function get_images($url)
{
    global $wrap;

    $table = Functions::get_remote_file($url);
    $beg='<th>Caption</th>'; $end1='<th colspan=5 >For more information';
    $temp = trim(parse_html($table,$beg,$end1,$end1,$end1,$end1,""));

    $temp = substr($temp,5,strlen($temp));//to remove the '</tr>' at the start of the string
    $temp = substr($temp,0,strlen($temp)-5);//to remove the '<tr>' at the end of the string
    $temp = str_ireplace(array("<tr class=listrow1 >","<tr class=listrow2 >","<tr  class=listrow2  >"), "<tr>", $temp);

    $temp = str_ireplace('<tr>' , "", $temp);
    $temp = trim(str_ireplace('</tr>' , "***", $temp));
    $temp = substr($temp,0,strlen($temp)-3);//remove last '***'
    $arr = explode("***", $temp);
    $arr_images=array();

    for ($i = 0; $i < count($arr); $i++)
    {
        $str = $arr[$i];
        $str = str_ireplace('<td>' , "", $str);
        $str = trim(str_ireplace('</td>' , "***", $str));
        $str = substr($str,0,strlen($str)-3);//remove last '***'
        $arr2 = explode("***", $str);
        $arr_images[]=$arr2;
    }
    return $arr_images;
}

function parse_contents($str)
{
    global $wrap;
    global $site_url;

    /* it can be:
    <a href="speciesdetail.cfm?genus=Abyssopathes&subgenus=&species=lyra&subspecies=&synseniorid=9266&validspecies=Abyssopathes%20lyra&authorship=%28Brook%2C%201889%29">Abyssopathes lyra (Brook, 1889)</a>
    or
    <a href="speciesdetail_for_nosyn.cfm?species=dentata&genus=Sandalolitha&subgenus=&subspecies=">Sandalolitha dentata Quelch, 1884</a>
    //
    */
    $temp='';
    $beg='speciesdetail.cfm?'; $end1='</a>';
    $temp = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));
    if($temp=='')
    {
        $beg='speciesdetail_for_nosyn.cfm?'; $end1='</a>';
        $temp = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));
    }

    //anemone2/speciesdetail_for_nosyn.cfm?spe

    $temp = '<a href="' . $site_url . '' . $beg . $temp . "</a>";
    //get url_for_main_menu
        $beg='="'; $end1='">';
        $url_for_main_menu = trim(parse_html($temp,$beg,$end1,$end1,$end1,$end1,""));
        //print"$wrap [<a href='$url_for_main_menu'>url_for_main_menu</a>]";
    //end url_for_main_menu

    //get sciname
        $beg='">'; $end1='</a>';
        $taxa = trim(parse_html($temp,$beg,$end1,$end1,$end1,$end1,""));
        print"$wrap taxa[$taxa]";
    //end sciname

    $main_menu = Functions::get_remote_file($url_for_main_menu);
    //get url for images page
        $url_for_images_page="";
        //"images.cfm?&genus=Abyssopathes&subgenus=&species=lyra&subspecies=&seniorid=9266&validspecies=Abyssopathes%20lyra&authorship=%28Brook%2C%201889%29">Images</a>
        $beg='images.cfm'; $end1='">';
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        $arr_images=array();
        if($temp != "")
        {
            $url_for_images_page = $site_url . $beg . $temp;
            //print"$wrap [<a href='$url_for_images_page'>images</a>]";
            ///*
            $arr_images = get_images($url_for_images_page);
            //*/
        }else print"$wrap no images";


    //end url for images page

    //get url for classification
        $url_for_classification="";
        //"showclassification2.cfm?synseniorid=2914&genus=Aiptasiogeton&subgenus=&species=eruptaurantia&subspecies=&origgenus=Actinothoe&origspecies=eruptaurantia&origsubspecies=&origsubgenus=&&validspecies=Aiptasiogeton%20eruptaurantia&authorship=%28Field%2C%201949%29">Classification</a>
        $beg='showclassification2.cfm'; $end1='">';
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        if($temp == "")
        {
            //http://hercules.kgs.ku.edu/hexacoral/anemone2/classification_path_no_syn.cfm?genus=Astr%C3%A6a&subgenus=&species=abdita&subspecies=
            $beg='classification_path_no_syn.cfm'; $end1='">';
            $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        }

        if($temp != "")
        {
            $url_for_classification = $site_url . $beg . $temp;
            //print"$wrap [<a href='$url_for_classification'>classification</a>]";
            $arr_classification = get_tabular_data($url_for_classification,"classification");
            if($arr_classification) $arr_classification=parse_classification($arr_classification);
        }else print"$wrap no classification";
    //end url for classification

    //get url for strict_synonymy
        $url_for_strict_synonymy="";
        //"synonymy_strict.cfm?seniorid=2914&validspecies=Aiptasiogeton%20eruptaurantia&authorship=%28Field%2C%201949%29">Strict synonymy</a>
        $beg='synonymy_strict.cfm'; $end1='">';
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        if($temp != "")
        {
            $url_for_strict_synonymy = $site_url . $beg . $temp;
            //print"$wrap [<a href='$url_for_strict_synonymy'>strict_synonymy</a>]";
            $arr_synonyms = get_tabular_data($url_for_strict_synonymy,"synonyms");

        }else print"$wrap no strict_synonymy";
    //end url for strict_synonymy

    //get url for references
        $url_for_references="";
        //"all_mentions_of_names2.cfm?species...
        $beg='all_mentions_of_names.cfm'; $end1='">';
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        if($temp=="")
        {
            $beg='all_mentions_of_names2.cfm'; $end1='">';
            $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        }

        $arr_references = array();
        if($temp != "")
        {
            $url_for_references = $site_url . $beg . $temp;
            //print"$wrap [<a href='$url_for_references'>references</a>]";
            $arr_references = get_tabular_data($url_for_references,"references");

            //start process
            $arr=array();
            foreach ($arr_references as $value)
            {
                $temp="";
                foreach ($value as $item)
                {
                    $temp .= "." . $item;
                }
                $temp = trim(substr($temp,1,strlen($temp)));//to remove the '.' on the first char

                //<a href="reference_detail.cfm?ref_number=58&type=Article">
                $temp = str_ireplace("reference_detail.cfm",$site_url . "reference_detail.cfm",$temp);

                //if we want to remove the anchor
                //$temp = get_str_from_anchor_tag($temp);

                $arr["$temp"]=1;
            }
            $arr_references = array_keys($arr);
        }else print"$wrap no references";
    //end url for references

    //get url for common_names
        $url_for_common_names="";
        //"common.cfm?seniorid=2914&validspecies=Aiptasiogeton%20eruptaurantia&authorship=%28Field%2C%201949%29">Strict synonymy</a>
        $beg='common.cfm'; $end1='">';
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        $arr_common_names=array();
        if($temp != "")
        {
            $url_for_common_names = $site_url . $beg . $temp;
            //print"$wrap [<a href='$url_for_common_names'>common_names</a>]";
            $arr_common_names = get_tabular_data($url_for_common_names,"common_names");
            //start process
            $arr=array();
            foreach ($arr_common_names as $value)
            {
                //$temp = strtolower($value[0]); //not a good idea especially for special chars
                $temp = $value[0];
                $temp = trim(get_str_from_anchor_tag($temp));
                //print"[$temp]";
                $arr["$temp"]=1;
            }
            $arr_common_names = array_keys($arr);
        }else print"$wrap no common_names";
    //end url for common_names

    //get url for skeletons
        //e.g. for species (Favites abdita) with skeleton
        $url_for_skeletons="";
        //http://hercules.kgs.ku.edu/hexacoral/anemone2/skeleton.cfm?genus=Favites&subgenus=&species=abdita&subspecies=
        $beg='skeleton.cfm'; $end1='">';
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        $html_skeletons="";
        if($temp != "")
        {   $url_for_skeletons = $site_url . $beg . $temp;
            //print"$wrap [<a href='$url_for_skeletons'>skeletons</a>]";
            $arr_skeletons = get_tabular_data($url_for_skeletons,"skeletons");
            if($arr_skeletons)//to check if it isn't null
            {
                $arr_fields = array("Author","Skeleton?","Mineral or Organic?","Mineral","Percent Magnesium");
                $html_skeletons = arr2html($arr_skeletons,$arr_fields,$url_for_main_menu);
                $html_skeletons = "<div style='font-size : small;'>$html_skeletons</div>";
            }

        }else print"$wrap no skeletons";
    //end url for skeleton

    //get url for biological_associations
        $url_for_biological_associations="";
        $beg='symbiont_info.cfm'; $end1='">';
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        $html_biological_associations="";
        if($temp != "")
        {   $url_for_biological_associations = $site_url . $beg . $temp;
            //print"$wrap [<a href='$url_for_biological_associations'>biological_associations</a>]";
            $arr_biological_associations = get_tabular_data($url_for_biological_associations,"biological_associations");
            $arr_fields = array("Algal symbionts");
            $html_biological_associations = arr2html($arr_biological_associations,$arr_fields,$url_for_main_menu);
            $html_biological_associations = "<div style='font-size : small;'>$html_biological_associations</div>";
        }else print"$wrap no biological_associations";
    //end url for biological_associations

    //get url for nematocysts
        $url_for_nematocysts="";
        $beg='cnidae_information.cfm'; $end1='">';
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        $html_nematocysts="";
        if($temp != "")
        {   $url_for_nematocysts = $site_url . $beg . $temp;
            //print"$wrap [<a href='$url_for_nematocysts'>nematocysts</a>]";
            $arr_nematocysts = get_tabular_data($url_for_nematocysts,"nematocysts");
            $arr_fields = array("Location","Image","Cnidae Type","Range of <br> Lengths (m)"," ","Range of <br >Widths (m)","n","N","State");
            $html_nematocysts = arr2html($arr_nematocysts,$arr_fields,$url_for_main_menu);
            $html_nematocysts = "<div style='font-size : small;'>$html_nematocysts</div>";
            //to have the 2nd row have colspan=9
            $html_nematocysts = str_ireplace("</th></tr><tr><td>", "</th></tr><tr><td colspan='9'>", $html_nematocysts);
        }else print"$wrap no nematocysts";
    //end url for nematocysts


    //get url for specimens
    /*
        $url_for_specimens="";
        //all_specimens_xml.cfm?
        $beg='all_specimens_xml.cfm'; $end1='">';
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));
        $arr_specimens=array();
        if($temp != "")
        {
            $url_for_specimens = $site_url . $beg . $temp;
            print"$wrap [<a href='$url_for_specimens'>specimens</a>]";
            $arr_specimens = get_tabular_data($url_for_specimens,"specimens");
            //start process
            $arr=array();
            foreach ($arr_specimens as $value)
            {
                $temp = @$value[5];
                $arr["$temp"]=1;
            }
            $arr_specimens = array_keys($arr);
        }else print"$wrap no specimens";
    */
    //end url for specimens


    //print"<hr>$main_menu";
    //========================================================================================
    //return array ($id,$image_url,$description,$desc_pic,$desc_taxa,$categories,$taxa,$copyright,$providers,$creation_date,$photo_credit,$outlinks);
    return array($taxa,$url_for_main_menu
                    ,$arr_classification,$arr_images
                    ,$html_skeletons,$url_for_skeletons
                    ,$html_biological_associations,$url_for_biological_associations
                    ,$arr_common_names
                    ,$arr_references
                    ,$html_nematocysts,$url_for_nematocysts
                );
}//function parse_contents($contents)
function arr2html($arr_data,$arr_fields,$url_for_main_menu)
{
    $html="<a target='hexacorallians' href='$url_for_main_menu'>More info</a><br><table style='font-size : small;' border='1' cellpadding='4' cellspacing='0'>";
    $html .="<tr>";
    foreach ($arr_fields as $value)
    {
        $html .="<th>$value</th>";
    }
    $html .="</tr>";
    foreach ($arr_data as $value)
    {
        $html .="<tr>";
        foreach ($value as $item)
        {
            if(stripos($item, "href") != "" )
            {
                $beg='">'; $end1='</a>';
                $item = trim(parse_html($item,$beg,$end1,$end1,$end1,$end1,""));
            }
            $html .="<td>$item</td>";
        };
        $html .="</tr>";
    }
    $html .="</table>";
    return $html;
}
function parse_classification($arr)
{
    global $wrap;
    $var = trim($arr[0][0]);
    //print"<hr>$var";
    $arr = explode("<br>", $var);
    //print"<pre>";print_r($arr);print"</pre>";
    $arr2=array();
    foreach ($arr as $str)
    {
        $beg='<b>'; $end1='</b>';
        $rank = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));
        $name = get_str_from_anchor_tag($str);
        if($rank) $arr2["$rank"][]=$name; //print"$wrap $rank -- $name";
    }
    print"<pre>";print_r($arr2);print"</pre>";
    return $arr2;
}


function get_str_from_anchor_tag($str)
{
    $beg='">'; $end1='</a>';
    $temp = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));

    //to get string after </a> if there are any
    $str .= "xxx";
    $beg='</a>'; $end1='xxx';
    $temp2 = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));

    return $temp . " " . $temp2;
}
function get_href_from_anchor_tag($str)
{
    //      <a href="reference_detail.cfm?ref_number=58&type=Article">
    $beg='href="'; $end1='">';
    return trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",true));//exist on first match = true
}

function cURL_it($validname,$url)
{
    $fields = 'validname=' . $validname;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // not to display the post submission
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
    curl_setopt($ch,CURLOPT_POST, $fields);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
    $output = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $ans = stripos($output,"The page cannot be found");
    $ans = strval($ans);
    if($ans != "")  return false;
    else            return $output;
}//function cURL_it($philid)

// /*
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
// */

function array_trim($a,$len)
{
    $b=array();
    $j = 0;
    //print "<hr> -- "; print count($a); print "<hr> -- ";
    for ($i = 0; $i < $len; $i++)
    {
        //if (array_key_exists($i,$a))
        if(isset($a[$i]))
        {
            if (trim($a[$i]) != "") { $b[$j++] = $a[$i]; }
            else print "[walang laman]";
        }
    }
    return $b;
}
function clean_str($str)
{
    $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);
    return $str;
}

?>