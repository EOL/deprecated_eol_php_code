#!/usr/local/bin/php
<?php

//define("MYSQL_DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];




$resource = new Resource(31);
$resource->id = 31;



$urls = array();
$urls["AlgerLaver"]         = "http://www.biopix.com/Category.asp?Category=AlgerLaver&Language=en&ShowExScan=1";
$urls["PadderKrybdyr"]      = "http://www.biopix.com/Category.asp?Category=PadderKrybdyr&Language=en&ShowExScan=1";
$urls["Arthropoda"]         = "http://www.biopix.com/Category.asp?Category=Arthropoda&Language=en&ShowExScan=1";
$urls["Fugle"]              = "http://www.biopix.com/Category.asp?Category=Fugle&Language=en&ShowExScan=1";
$urls["Sommerfugle"]        = "http://www.biopix.com/Category.asp?Category=Sommerfugle&Language=en&ShowExScan=1";
$urls["KulturPlanter"]      = "http://www.biopix.com/Category.asp?Category=KulturPlanter&Language=en&ShowExScan=1";
$urls["Husdyr"]             = "http://www.biopix.com/Category.asp?Category=Husdyr&Language=en&ShowExScan=1";
$urls["Fisk"]               = "http://www.biopix.com/Category.asp?Category=Fisk&Language=en&ShowExScan=1";
$urls["Svampe"]             = "http://www.biopix.com/Category.asp?Category=Svampe&Language=en&ShowExScan=1";
$urls["Insekter"]           = "http://www.biopix.com/Category.asp?Category=Insekter&Language=en&ShowExScan=1";
$urls["LavereDyr"]          = "http://www.biopix.com/Category.asp?Category=LavereDyr&Language=en&ShowExScan=1";
$urls["Pattedyr"]           = "http://www.biopix.com/Category.asp?Category=Pattedyr&Language=en&ShowExScan=1";
$urls["Bloeddyr"]           = "http://www.biopix.com/Category.asp?Category=Bloeddyr&Language=en&ShowExScan=1";
$urls["Mosser"]             = "http://www.biopix.com/Category.asp?Category=Mosser&Language=en&ShowExScan=1";
$urls["Planter"]            = "http://www.biopix.com/Category.asp?Category=Planter&Language=en&ShowExScan=1";

$kingdoms = array();
$kingdoms["AlgerLaver"]     = array();
$kingdoms["PadderKrybdyr"]  = array("kingdom" => "Animalia", "phylum" => "Chordata");
$kingdoms["Arthropoda"]     = array("kingdom" => "Animalia", "phylum" => "Arthropoda");
$kingdoms["Fugle"]          = array("kingdom" => "Animalia", "phylum" => "Chordata", "class" => "Aves");
$kingdoms["Sommerfugle"]    = array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Lepidoptera");
$kingdoms["KulturPlanter"]  = array("kingdom" => "Plantae");
$kingdoms["Husdyr"]         = array("kingdom" => "Animalia");
$kingdoms["Fisk"]           = array("kingdom" => "Animalia", "phylum" => "Chordata");
$kingdoms["Svampe"]         = array("kingdom" => "Fungi");
$kingdoms["Insekter"]       = array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta");
$kingdoms["LavereDyr"]      = array("kingdom" => "Animalia");
$kingdoms["Pattedyr"]       = array("kingdom" => "Animalia", "phylum" => "Chordata", "class" => "Mammalia");
$kingdoms["Bloeddyr"]       = array("kingdom" => "Animalia", "phylum" => "Mollusca");
$kingdoms["Mosser"]         = array();
$kingdoms["Planter"]        = array("kingdom" => "Plantae");

$used_data = array();
$all_taxa = array();


foreach($urls as $key => $val)
{
    echo "$val ".Functions::time_elapsed()."\n";
    $html = preg_replace("/(\n|\r|\t)/", " ", Functions::get_remote_file($val));
    
    static $taxa_for_this_page = 0;
    $taxa_for_this_page = 0;
    
    if(preg_match("/<table>(<tr><td>.*<\/td><\/tr>)<\/table>/",$html,$arr))
    {
        $table = $arr[1];
        while(preg_match("/^(.*)<\/table>/",$table,$arr)) $table = $arr[1];
        
        $parts = explode("</tr>",$table);
        $count = count($parts)-1;
        foreach($parts as $key2 => $val2)
        {
            if($key2 == $count) break;
            if(preg_match("/href='(Species\.asp\?Searchtext=(.*)\&amp;Category=$key)'>(.*)<\/a>/i",$val2,$arr))
            {
                static $total_taxa = 0;
                echo "   taxon $taxa_for_this_page: total $total_taxa: $arr[2] ".Functions::time_elapsed()."\n";
                $taxa_for_this_page++;
                $total_taxa++;
                
                $name = $arr[3];
                while(preg_match("/^(.*)<\/a>/",$name,$arr2)) $name = $arr2[1];
                
                $url = "http://www.biopix.com/".$arr[1]."&ShowAll=1";
                //echo "$url\n";
                
                if($taxon = grab_images($url, $name, $arr[2], @$kingdoms[$key]))
                {
                    $all_taxa[] = $taxon;
                }
                
                //if($taxa_for_this_page >= 6) break;
            }
        }
    }
    //break;
}

//echo SchemaDocument::get_taxon_xml($all_taxa);



$new_resource_xml = utf8_encode(SchemaDocument::get_taxon_xml($all_taxa));

$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";

$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
















function grab_images($url, $name, $searchText, $kingdom)
{
    global $used_data;
    
    $family = "";
    static $images_for_this_taxon = 0;
    $images_for_this_taxon = 0;
    
    $html = preg_replace("/(\n|\r|\t)/", " ", Functions::get_remote_file($url));
    
    if(preg_match("/href='Family\.asp\?[^']+'>\.\.\.see all (.*?)<\/a>/", $html, $arr))
    {
        $family = $arr[1];
    }
    
    
    $taxon_parameters = get_taxon($searchText, $kingdom, $family, $url);
    if(!$taxon_parameters) return false;
    
    
    if(preg_match("/<\/h1><br \/><br \/>(.*)<td class=\"bodycol3\">/", $html, $arr))
    {
        $table = $arr[1];
        while(preg_match("/^(.*)<td class=\"bodycol3\">/", $table, $arr)) $table = $arr[1];
        
        $parts = explode("</table>",$table);
        $count = count($parts)-1;
        foreach($parts as $key2 => $val2)
        {
            if($key2 == $count) break;
            if(preg_match("/href='(.*)'><img alt='(.*)' src/i", $val2, $arr))
            {
                $image_details_url = "http://www.biopix.com/".$arr[1];
                $photo_id = 0;
                if(preg_match("/PhotoId=([0-9]+)(&|$)/i", $image_details_url, $arr))
                {
                    $photo_id = $arr[1];
                }
                
                if(@!$used_data[$image_details_url])
                {
                    static $total_images = 0;
                    echo "      image $images_for_this_taxon: total $total_images ".Functions::time_elapsed()."\n";
                    $total_images++;
                    $images_for_this_taxon++;
                    
                    if($data_object = image_detail($image_details_url, $photo_id))
                    {
                        $taxon_parameters["dataObjects"][] = $data_object;
                    }
                }
            }
        }
    }
    
    return new SchemaTaxon($taxon_parameters);
}

function image_detail($url, $photo_id)
{    
    $html = preg_replace("/(\n|\r|\t)/", " ", Functions::get_remote_file($url));
    $location = "";
    $note = "";
    $image_url = "";
    
    if(preg_match("/<b>Location<\/b><br \/>(.*?)<br \/>/", $html, $arr))
    {
        $location = $arr[1];
    }
    
    if(preg_match("/<b>Note<\/b><br \/><br \/><span class='textareasmall'>(.*?)<br \/><\/span>/", $html, $arr))
    {
        $note = $arr[1];
        if(preg_match("/^(.*)\.$/",$note,$arr)) $note = $arr[1];
    }
    
    
    
    if(preg_match("/src='Temp\/([^']*)' \/><\/a>/",$html,$arr)) $image_url = "http://www.biopix.com/PhotosMedium/".rawurlencode($arr[1]);
    
    $suffix = ".jpg";
    
    if(preg_match("/(\.[a-z]{2,4})$/i",$image_url,$arr)) $suffix = $arr[1];
    
    if(!$image_url) return false;
    
    
    
    if($parameters = get_data_object($image_url, $photo_id, $url, $note, $location, $suffix))
    {
        return new SchemaDataObject($parameters);
    }
    
    return false;
}


function get_data_object($image_url, $photo_id, $source_url, $description, $location, $file_extension)
{
    $mime_type = "image/jpeg";
    if($file_extension == ".jpeg") $mime_type = "image/jpeg";
    elseif($file_extension == ".jpg") $mime_type = "image/jpeg";
    elseif($file_extension == ".png") $mime_type = "image/png";
    elseif($file_extension == ".gif") $mime_type = "image/gif";
    else return false;
    
    $dataObjectParameters = array();
    if($photo_id) $dataObjectParameters["identifier"] = $photo_id;
    $dataObjectParameters["description"] = $description;
    $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
    $dataObjectParameters["mimeType"] = $mime_type;
    $dataObjectParameters["source"] = $source_url;
    $dataObjectParameters["location"] = $location;
    $dataObjectParameters["mediaURL"] = $image_url;
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by-nc/3.0/";
    
    $dataObjectParameters["audiences"] = array();
    $audienceParameters = array();
    
    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
    
    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
    
    return $dataObjectParameters;
}

function get_taxon($name, $taxonomy, $family, $url)
{
    $taxon_parameters = array();
    $taxon_parameters["identifier"] = str_replace(" ", "_", $name);
    $taxon_parameters["scientificName"] = $name;
    $taxon_parameters["source"] = $url;
    $taxon_parameters["family"] = $family;
    $taxon_parameters["dataObjects"] = array();
    
    foreach($taxonomy as $rank => $name)
    {
        $taxon_parameters[$rank] = $name;
    }
    
    return $taxon_parameters;
}

?>