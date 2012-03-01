<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];

$urls = array();
$urls["AlgerLaver"]         = "http://www.biopix.com/category.aspx?category=AlgerLaver&families=0";
$urls["PadderKrybdyr"]      = "http://www.biopix.com/category.aspx?category=PadderKrybdyr&families=0";
$urls["Arthropoda"]         = "http://www.biopix.com/category.aspx?category=Arthropoda&families=0";
$urls["Fugle"]              = "http://www.biopix.com/category.aspx?category=Fugle&families=0";
$urls["Sommerfugle"]        = "http://www.biopix.com/category.aspx?category=Sommerfugle&families=0";
$urls["KulturPlanter"]      = "http://www.biopix.com/category.aspx?category=KulturPlanter&families=0";
$urls["Husdyr"]             = "http://www.biopix.com/category.aspx?category=Husdyr&families=0";
$urls["Fisk"]               = "http://www.biopix.com/category.aspx?category=Fisk&families=0";
$urls["Svampe"]             = "http://www.biopix.com/category.aspx?category=Svampe&families=0";
$urls["Insekter"]           = "http://www.biopix.com/category.aspx?category=Insekter&families=0";
$urls["LavereDyr"]          = "http://www.biopix.com/category.aspx?category=LavereDyr&families=0";
$urls["Pattedyr"]           = "http://www.biopix.com/category.aspx?category=Pattedyr&families=0";
$urls["Bloeddyr"]           = "http://www.biopix.com/category.aspx?category=Bloeddyr&families=0";
$urls["Mosser"]             = "http://www.biopix.com/category.aspx?category=Mosser&families=0";
$urls["Planter"]            = "http://www.biopix.com/category.aspx?category=Planter&families=0";

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

$resource_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . "31_temp.xml", "w+");
fwrite($resource_file, \SchemaDocument::xml_header());

foreach($urls as $key => $val)
{
    echo "$val ".Functions::time_elapsed()."\n";
    $html = Functions::get_remote_file($val, NULL, 120);
    
    $kingdoms_for_section = $kingdoms[$key];
    
    $taxa_for_this_page = 0;
    static $total_taxa = 0;
    
    if(preg_match_all("/<a class='catmenu' href='([^']*?\.aspx)'>([^<].*?)<\/a>/ims", $html, $matches, PREG_SET_ORDER))
    {
        foreach($matches as $match)
        {
            $url = "http://www.biopix.com/". $match[1];
            $name = $match[2];
            $taxa_for_this_page++;
            $total_taxa++;
            
            echo "   taxon $taxa_for_this_page: total $total_taxa: $name: $url ".Functions::time_elapsed()."\n";
            if($taxon = get_images_for_taxon($url, $kingdoms_for_section))
            {
                $all_taxa[] = $taxon;
                fwrite($resource_file, $taxon->__toXML());
            }
            // if($taxa_for_this_page >= 10) break;
        }
    }
}

fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . "31_previous.xml");
@rename(CONTENT_RESOURCE_LOCAL_PATH . "31.xml", CONTENT_RESOURCE_LOCAL_PATH . "31_previous.xml");
rename(CONTENT_RESOURCE_LOCAL_PATH . "31_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . "31.xml");

// set Flickr to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . "31.xml") > 600)
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Force Harvest')->id." WHERE id=31");
}












function get_images_for_taxon($url, $kingdom)
{
    $html = preg_replace("/(\n|\r|\t)/", " ", Functions::get_remote_file($url, NULL, 120));
    if(preg_match("/href='ssspecies.aspx\?species=(.*?)&amp;[^']+' title='Slideshow'>/", $html, $arr))
    {
        $taxon_url = "http://www.biopix.com/species.asp?searchtext=". $arr[1];
        $name = ucfirst(str_replace("-", " ", $arr[1]));
        if(preg_match("/^(.*)&amp;/", $name, $arr)) $name = $arr[1];
        return grab_images($taxon_url, $name, $kingdom);
    }else
    {
        echo "THERE IS NO LINK TO THE TAXON ON $url\n";
    }
}


function grab_images($url, $name, $kingdom)
{
    global $used_data;
    
    $family = "";
    static $images_for_this_taxon = 0;
    $images_for_this_taxon = 0;
    
    $url = str_replace(" ", "%20", $url);
    $html = preg_replace("/(\n|\r|\t)/", " ", Functions::get_remote_file($url, NULL, 120));
    
    if(preg_match("/href='family\.asp\?category=.*?&amp;family=(.*?)'>\.\.\. *see all/ims", $html, $arr))
    {
        $family = ucfirst($arr[1]);
    }
    
    // fix becuase names were showing up as Abies-alba
    $taxon_parameters = get_taxon($name, $kingdom, $family, $url);
    if(!$taxon_parameters) return false;
    
    if(preg_match_all("/href='(photo.aspx\?photoid=(.*?)&amp;[^']*?)'><img alt='(.*?)'/ims", $html, $matches, PREG_SET_ORDER))
    {
        foreach($matches as $match)
        {
            $image_details_url = "http://www.biopix.com/".$match[1];
            $photo_id = $match[2];
            $alt_name = $match[3];
            
            if(@!$used_data[$image_details_url])
            {
                static $total_images = 0;
                echo "      image $images_for_this_taxon: name: $alt_name url: $image_details_url total: $total_images ".Functions::time_elapsed()."\n";
                $total_images++;
                $images_for_this_taxon++;
                
                if($data_object = image_detail($image_details_url, $photo_id))
                {
                    $taxon_parameters["dataObjects"][] = $data_object;
                }
            }
        }
    }
    
    $taxon = new \SchemaTaxon($taxon_parameters);
    return $taxon;
}

function image_detail($url, $photo_id)
{
    $html = preg_replace("/(\n|\r|\t)/", " ", Functions::get_remote_file($url, NULL, 120));
    $location = "";
    $note = "";
    $image_url = "";
    
    if(preg_match("/<h1>Location<\/h1><br\/>(.*?)<br \/>/", $html, $arr)) $location = trim($arr[1]);
    if(preg_match("/<h1>Note<\/h1><br\/><div style='height:100px'>(.*?)<\/div>/", $html, $arr))
    {
        $note = $arr[1];
        if(substr($note, -1) == ".") $note = substr($note, 0, -1);
    }
    
    if(preg_match("/src='photos\/(.*?)' \/>/", $html, $arr)) $image_url = "http://www.biopix.com/photos/".rawurlencode($arr[1]);
    
    $suffix = ".jpg";
    
    if(preg_match("/(\.[a-z]{2,4})$/i",$image_url,$arr)) $suffix = strtolower($arr[1]);
    if(!$image_url) return false;
    
    if($parameters = get_data_object($image_url, $photo_id, $url, $note, $location, $suffix))
    {
        return new \SchemaDataObject($parameters);
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
    
    return $dataObjectParameters;
}

function get_taxon($name, $taxonomy, $family, $url)
{
    $taxon_parameters = array();
    $taxon_parameters["identifier"] = str_replace(" ", "_", $name);
    $taxon_parameters["scientificName"] = ucfirst($name);
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
