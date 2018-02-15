<?php
namespace php_active_record;
/* connector [170]
Connector screen scrapes the partner website, assembles the information and generates the EOL XML
http://archive.serpentproject.com/view/species/
*/

define("SERPENT_PAGE_URL", "http://archive.serpentproject.com/view/species/");
class SerpentAPI
{
    public function get_all_taxa()
    {
        $urls = self::compile_taxon_urls();
        $all_taxa = array();
        $used_collection_ids = array();
        $total = sizeof($urls);
        $i = 0;
        foreach($urls as $url)
        {
            $i++; echo "\n $i of $total " . $url['url'];
            $arr = self::get_Serpent_taxa($url['url'], $used_collection_ids);
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];
            $all_taxa = array_merge($all_taxa, $page_taxa);
            // if($i >= 10) break; //debug only
        }
        return $all_taxa;
    }

    public function get_Serpent_taxa($url, $used_collection_ids)
    {
        $response = self::search_collections($url);//this will output the raw (but structured) output from the external service
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;
            $used_collection_ids[$rec["sciname"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    function compile_taxon_urls()
    {
        $taxon_urls = array();
        $start_url = SERPENT_PAGE_URL;
        $urls = self::taxon_url_extractor($start_url, '<h1 class="pagetitle">Browse by Species</h1>', '<li>', 1);

        //for debug
        /*
        $urls = array();
        $urls[] = array("url" => "http://archive.serpentproject.com/view/species/Alcyonidium_diaphanum.html", "sciname" => "Browse by Species");
        $urls[] = array("url" => "http://archive.serpentproject.com/view/species/Caryophyllia_smithii.html", "sciname" => "Browse by Species");
        $urls[] = array("url" => "http://archive.serpentproject.com/view/species/Anarhichas_lupus.html", "sciname"=>"");
        */

        return $urls;

        /* Continue if you want to get individual URLs per data object. */
        /*
        $i = 0;
        foreach($urls as $url)
        {
            $i++;
            $partial_urls = self::taxon_url_extractor($url, '<h1 class="pagetitle">', '<td>', 0);
            $taxon_urls = array_merge($taxon_urls, $partial_urls);
        }
        $taxon_urls = array_unique($taxon_urls);
        return $taxon_urls;
        */
    }
    
    function taxon_url_extractor($url, $searched1, $searched2, $with_page_url, $html = NULL)
    {
        $urls = array();
        // if(!$html) $html = Functions::get_remote_file_fake_browser($url, array('download_wait_time' => 1000000)); // 1 second wait-time
        if(!$html) $html = Functions::lookup_with_cache($url, array('download_wait_time' => 1000000, 'expire_seconds' => 60*60*24*25)); // 1 second wait-time
        

        //Species: Asterias rubens</title>
        if(preg_match("/<h1 class=\"pagetitle\">(.*?)<\/h1>/ims", $html, $matches))
        {
            $sciname = trim(strip_tags($matches[1]));
            $sciname = trim(str_ireplace('Species:', "", $sciname));
            $sciname = trim($sciname);
        }
        else return $urls;

        $pos = stripos($html, $searched1);
        $html = substr($html, $pos, strlen($html));
        if(is_numeric($pos))
        {
            $html = strip_tags($html, "<a>$searched2");
            $html = str_ireplace($searched2, "&arr[]=", $html);
            $arr = array(); parse_str($html);
            foreach($arr as $r)
            {
                if(preg_match("/href=\"(.*?)\"/ims", $r, $matches))
                {
                    $url = $matches[1];
                    if  ( is_numeric(stripos($url, 'unidentified'))         ||
                          is_numeric(stripos($url, 'unknown'))              ||
                          is_numeric(stripos($url, '=3F'))                  ||
                          is_numeric(stripos($url, 'www.openarchives.org')) ||
                          is_numeric(stripos($url, 'www.eprints.org'))      ||
                          is_numeric(stripos($url, '.mov'))                 ||
                          is_numeric(stripos($url, '.avi'))
                        )continue;
                    if($with_page_url) $temp = SERPENT_PAGE_URL . $matches[1];
                    else               $temp = $matches[1]; 
                    $urls[] = array("url" => $temp, "sciname" => $sciname);
                }
            }
        }
        return $urls;
    }

    function search_collections($species_page_url)//this will output the raw (but structured) array
    {
        $html = Functions::lookup_with_cache($species_page_url, array('download_wait_time' => 1000000, 'expire_seconds' => 60*60*24*25)); // 1 second wait-time
        $html = utf8_decode($html);
        $response = self::scrape_species_page($html, $species_page_url);
        return $response;
    }

    function scrape_species_page($html, $species_page_url)
    {
        $arr_scraped = array();
        $arr_photos = array();
        $arr_sciname = array();

        //photos start =================================================================
        $arr_photos = array();
        $arr_photo_url = self::taxon_url_extractor($species_page_url, '<h1 class="pagetitle">', '<td>', 0, $html);
        if($arr_photo_url) $arr_photos = self::get_photo_details($arr_photo_url);
        $ancestry = array();
        $cnt = 0;
        foreach($arr_photos as $rec)
        {
            if(!@$rec["url"]) continue;
            if(@$rec['sciname']) $sciname = strip_tags(@$rec['sciname']);
            $desc = "";
            if(@$rec['description']) $desc.="Description: " . @$rec['description'] . " <br>";
            if(@$rec['Item Type:']) $desc.="Item Type: " . @$rec['Item Type:'] . " <br>";
            if(@$rec['Title:']) $desc.="Title: " . @$rec['Title:'] . " <br>";
            if(@$rec['Copyright:']) $desc.="Copyright: " . @$rec['Copyright:'] . " <br>";
            if(@$rec['Species:']) $desc.="Species: " . @$rec['Species:'] . " <br>";
            if(@$rec['Behaviour:']) $desc.="Behaviour: " . @$rec['Behaviour:'] . " <br>";
            if(@$rec['Site:']) $desc.="Site: " . @$rec['Site:'] . " <br>";
            if(@$rec['Site Description:']) $desc.="Site Description: " . @$rec['Site Description:'] . " <br>";
            if(@$rec['Depth:']) $desc.="Depth (m): " . @$rec['Depth:'] . " <br>";
            if(@$rec['Latitude:']) $desc.="Latitude: " . @$rec['Latitude:'] . " <br>";
            if(@$rec['Longitude:']) $desc.="Longitude: " . @$rec['Longitude:'] . " <br>";
            if(@$rec['Countries:']) $desc.="Countries: " . @$rec['Countries:'] . " <br>";
            if(@$rec['Habitat:']) $desc.="Habitat: " . @$rec['Habitat:'] . " <br>";
            if(@$rec['Rig:']) $desc.="Rig: " . @$rec['Rig:'] . " <br>";
            if(@$rec['Project Partners:']) $desc.="Project Partners: " . @$rec['Project Partners:'] . " <br>";
            if(@$rec['ROV:']) $desc.="ROV: " . @$rec['ROV:'] . " <br>";
            if(@$rec['Deposited By:']) $desc.="Deposited By: " . @$rec['Deposited By:'] . " <br>";
            if(@$rec['Deposited On:']) $desc.="Deposited On: " . @$rec['Deposited On:'] . " <br>";
            if(!$ancestry)
            {
                if(preg_match_all("/Kingdom /ims", @$rec['Classification:'], $matches))
                {
                    // Should only get the ancestry if there is only 1 ancestry displayed.
                    // There is no clear assignment of taxon and classification when displaying multiple ancestry and taxa.
                    if(count($matches[0]) == 1) $ancestry = self::parse_classification(@$rec['Classification:'], @$rec['Species:'], $sciname);
                }
            }
            $agent = array();
            $rights_holder = "";
            if(@$rec['Deposited By:'])
            {
                $arr_agent = self::separate_href(@$rec['Deposited By:']);
                $agent[] = array("role" => "source", "homepage" => @$arr_agent["homepage"], "name" => @$arr_agent["agent"]);
            }
            $rights_holder = "SERPENT Media Archive Project";
            if(@$rec["Item Type:"] == "Video")
            {
                $datatype = "http://purl.org/dc/dcmitype/MovingImage";
                $path_info = pathinfo(@$rec["url"]);
                $extension = strtolower($path_info['extension']);
                if($extension == "avi")continue; //avi is not yet supported.
                $mimetype = self::get_mimetype($extension);
            }
            elseif(@$rec["Item Type:"] == "Image")
            {
                $datatype = "http://purl.org/dc/dcmitype/StillImage";
                $mimetype = "image/jpeg";
            }
            $cnt++;
            $arr_photos["$sciname"][] = array(
                        "identifier"    => @$rec["url"],
                        "mediaURL"      => @$rec["url"],
                        "mimeType"      => $mimetype,
                        "date_created"  => @$rec["Deposited On:"],
                        "rights"        => @$rec["Copyright:"],
                        "rights_holder" => $rights_holder,
                        "dataType"      => $datatype,
                        "description"   => $desc,
                        "title"         => "",
                        "location"      => @$rec["Site:"],
                        "dc_source"     => @$rec["sourceURL"],
                        "agent"         => $agent);
        }
        //photos end =================================================================

        //text start references =================================================================
        $arr_ref = self::scrape_page_others($html, '<h2 class="Lit">References</h2>', "reference");
        if($arr_ref) $arr_ref = self::prepare_reference($arr_ref);
        else $arr_ref = array();
        //text end references =================================================================

        $arr_sciname[$sciname] = $species_page_url;
        foreach(array_keys($arr_sciname) as $sci)
        {
            $arr_scraped[] = array("id" => "",
                                   "kingdom" => @$ancestry["Kingdom"],
                                   "phylum" => @$ancestry["Phylum"],
                                   "class" => @$ancestry["Class"],
                                   "order" => @$ancestry["Order"],
                                   "family" => @$ancestry["Family"],
                                   "sciname" => $sci,
                                   "dc_source" => $species_page_url,
                                   "photos" =>@ $arr_photos["$sci"],
                                   "texts"=>@ $arr_texts["$sci"],
                                   "references" => $arr_ref
                                  );
        }
        return $arr_scraped;
    }

    function get_mimetype($ext)
    {
        $mimetype="";
        $mpg=array("mpg", "mpeg");
        if      ($ext == "wmv")         $mimetype = "video/x-ms-wmv";
        elseif  ($ext == "avi")         $mimetype = "video/x-msvideo";
        elseif  ($ext == "mp4")         $mimetype = "video/mp4";
        elseif  ($ext == "mov")         $mimetype = "video/quicktime";
        elseif  (in_array($ext, $mpg))  $mimetype = "video/mpeg";
        elseif  ($ext == "flv")         $mimetype = "video/x-flv";
        return $mimetype;
    }    

    function parse_classification($str, $species, $sciname)
    {
        //Kingdom Animalia -- Phylum Cnidaria (Cnidarians) -- Anthozoa (Sea Anemones and Corals) -- Actiniaria (Anemones) -- Actinoscyphiidae (Sea anemones) -- Actinoscyphia aurelia        
        if(is_numeric(stripos($str, ",")))
        {
            $arr_ancestry = explode(",", $str);
            $arr_species = explode(",", $species);
            $key = array_search($sciname, $arr_species);
            $str = $arr_ancestry[$key];
        }
        
        $ancestry = array();
        $arr = explode("--", $str);
        $ranks = array("Kingdom", "Phylum", "Class", "Order", "Family", "Genus");
        foreach($arr as $r)
        {
            foreach($ranks as $rank)
            {
                if(is_numeric(stripos($r, $rank)))
                {
                    $temp = trim(str_ireplace($rank, "", $r));
                    $pos = stripos($temp, "(");
                    if(is_numeric($pos)) $temp = substr($temp, 0, $pos);
                    $ancestry[$rank] = $temp;
                }
            }
        }
        return $ancestry;
    }

    function separate_href($str)
    {
        $arr = array();
        if(preg_match("/href=\"(.*?)\"/ims", $str, $matches)) $arr["homepage"] = $matches[1];
        if(preg_match("/>(.*?)</ims", $str, $matches)) $arr["agent"] = $matches[1];
        return $arr;
    }
    
    function prepare_reference($arr_ref)
    {
        $refs=array();
        foreach($arr_ref as $r)
        {
            if(preg_match("/href=(.*?)>/ims", $r, $matches))
            $url = $matches[1];
            $ref = str_ireplace('</td>', '. ', $r);
            $ref = strip_tags($ref);
            $refs[] = array("url" => $url, "ref" => $ref);
        }
        return $refs;
    }

    function get_photo_details($arr)
    {
        $arr_total = array();
        foreach($arr as $url)
        {
            $html = Functions::lookup_with_cache($url['url'], array('download_wait_time' => 1000000, 'expire_seconds' => 60*60*24*25)); // 1 second wait-time
            $html = utf8_decode($html);
            $arr_scraped = self::scrape_page($html, $url['url'], $url['sciname']);
            if($arr_scraped)$arr_total = array_merge($arr_total, $arr_scraped);
        }
        return $arr_total;
    }

    function scrape_page($html, $sourceURL, $sciname)
    {
        $orig_html = $html;
        $arr_details = array();
        //special case
        $html = str_ireplace("&amp;", "and", $html);
        $html = str_ireplace("Creator(s):", "Creators:", $html);
        $html = str_ireplace("Depth (m):", "Depth:", $html);
        //end special case
        
        //get description
        $description = "";
        if(preg_match("/<h2>Description<\/h2>(.*?)<table/ims", $html, $matches))$description = strip_tags($matches[1]);
        //end get description

        $pos = stripos($html, '<h1 class="pagetitle">');
        $html = substr($html, $pos, strlen($html));
        $html = str_ireplace("&gt;", "--", $html);//'greater than' char has to be replaced
        
        if(preg_match("/<table(.*?)<\/table/ims", $html, $matches)) $html = $matches[1];
        else return array();
        
        $html = str_ireplace("<tr>", "&arr[]=", $html);
        $arr = array(); parse_str($html);
        foreach($arr as $rec)
        {            
            $label = "";
            if(preg_match("/<th valign=\"top\">(.*?)<\/th>/ims", $rec, $matches)) $label = $matches[1];
            $value = "";
            if(preg_match("/<td valign=\"top\">(.*?)<\/td>/ims", $rec, $matches)) $value = $matches[1];
            if($label == "Site:") $value = strip_tags($value, "<br>");
            else                  $value = strip_tags($value);
            if($label == "Item Type:") $item_type = $value;
            $arr_details[$label] = $value;
        }

        $arr_details["sourceURL"] = $sourceURL;
        if(isset($item_type))
        {
            if($item_type == "Video")
            {
                if(preg_match("/src=\"(.*?).mov\"/ims", $orig_html, $matches)) $arr_details["url"] = $matches[1] . ".mov";
                if(preg_match("/src=\"(.*?).avi\"/ims", $orig_html, $matches)) $arr_details["url"] = $matches[1] . ".avi";
                $count=1;
            }
            elseif($item_type == "Image")
            {
                if(preg_match("/<a target=\"_blank\" href=\"(.*?)medium.jpg/ims", $orig_html, $matches)) $arr_details["url"] = $matches[1] . "medium.jpg";
                $count = count(explode("medium.jpg", $orig_html)) - 1;            
                //e.g. http://archive.serpentproject.com/1703/01//thumbnails/medium.jpg
            }
        }
        else $count = 0;

        $arr_details["sciname"] = $sciname;
        if($description)$arr_details["description"]=$description;

        //start multiply images for more than 1 image
        $final_arr = array();
        for ($i = 1; $i <= $count; $i++)
        {
            $temp_arr = $arr_details;
            if($item_type == "Image") $temp_arr["url"] = $sourceURL . "0" . $i . "/thumbnails/medium.jpg";
            $final_arr[] = $temp_arr;
        }
        return $final_arr;
    }

    function scrape_page_others($html, $searched, $return_value)
    {
        $pos = stripos($html, $searched);
        if(is_numeric($pos))
        {
            $html = trim(substr($html, $pos, strlen($html)));
            $pos = stripos($html, "</table>");
            $html = trim(substr($html, 0, $pos));
            $pos = stripos($html, '<td class="FieldValue">');
            if(!is_numeric($pos)) return;
        }
        else return;

        $html = str_ireplace('<tr class="odd">', '<tr>', $html);
        $html = str_ireplace('<tr class="even">', '<tr>', $html);
        
        //special case
        $html = str_ireplace('&amp;', "and", $html);
        //end special case        

        $str = str_ireplace('<tr>', "&arr[]=", $html); 
        $arr = array(); parse_str($str);

        $arr_value = array();
        foreach($arr as $r)
        {
            $pos = stripos($r, '</tr>');
            if(is_numeric($pos)) $arr_value[] = trim(substr($r, 0, $pos));
        }

        //to exclude any images <img>
        $arr = array();
        foreach($arr_value as $r)
        {
            $r = str_ireplace('<td></td>', "", $r);
            $r = str_ireplace('<a href=', "<a href=" . SERPENT_PAGE_URL, $r);
            $arr[] = strip_tags(trim($r), "<a><td>");
        }
        if($return_value == "reference") return $arr;

        //concatenate...
        $html = "";
        foreach($arr as $r)
        {
            $str = str_ireplace('<td class="FieldValue">', "", $r);
            $str = str_ireplace('<td class="FieldRef">', "Ref. ", $str);
            $str = str_ireplace('</td>', ". ", $str);
            $html .= $str;
            $html .= "<br>&nbsp;<br>";
        }
        return $html;
    }

    function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;
        $taxon["identifier"] = "serpent_" . str_replace(" ", "_", ucfirst(trim($rec["sciname"])));
        $taxon["source"] = $rec["dc_source"];                
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        $taxon["kingdom"] = ucfirst(trim($rec["kingdom"]));
        $taxon["phylum"] = ucfirst(trim($rec["phylum"]));
        $taxon["class"] = ucfirst(trim($rec["class"]));
        $taxon["order"] = ucfirst(trim($rec["order"]));
        $taxon["family"] = ucfirst(trim($rec["family"]));
        if(@$rec["photos"]) $taxon["dataObjects"] = self::prepare_objects($rec["photos"], @$taxon["dataObjects"], array());
        if(@$rec["texts"])  $taxon["dataObjects"] = self::prepare_objects($rec["texts"], @$taxon["dataObjects"], $rec["references"]);
        $taxon_object = new \SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    function prepare_objects($arr, $taxon_dataObjects, $references)
    {
        $arr_SchemaDataObject = array();
        if($arr)
        {
            $arr_ref = array();
            $length = sizeof($arr);
            $i = 0;
            foreach($arr as $rec)
            {
                $i++;
                if($length == $i)$arr_ref = $references;
                $data_object = self::get_data_object($rec, $arr_ref);
                if(!$data_object) return false;
                $taxon_dataObjects[] = new \SchemaDataObject($data_object);
            }
        }
        return $taxon_dataObjects;
    }

    function get_data_object($rec, $references)
    {
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = $rec["identifier"];
        $data_object_parameters["source"] = $rec["dc_source"];
        $data_object_parameters["dataType"] = $rec["dataType"];
        $data_object_parameters["mimeType"] = @$rec["mimeType"];
        $data_object_parameters["mediaURL"] = @$rec["mediaURL"];
        $data_object_parameters["rights"] = @$rec["rights"];
        $data_object_parameters["rightsHolder"] = @$rec["rights_holder"];
        $data_object_parameters["title"] = @$rec["title"];
        $data_object_parameters["description"] = utf8_encode($rec["description"]);
        $data_object_parameters["location"] = utf8_encode($rec["location"]);
        $data_object_parameters["license"] = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';

        //start reference
        $data_object_parameters["references"] = array();
        $ref = array();
        foreach($references as $r)
        {
            $referenceParameters = array();
            $referenceParameters["fullReference"] = trim($r["ref"]);
            $referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "url", "value" => trim($r["url"])));
            $ref[] = new \SchemaReference($referenceParameters);
        }
        $data_object_parameters["references"] = $ref;
        //end reference

        if(@$rec["subject"])
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = @$rec["subject"];
            $data_object_parameters["subjects"][] = new \SchemaSubject($subjectParameters);
        }

        if(@$rec["agent"])
        {
            $agents = array();
            foreach($rec["agent"] as $a)
            {
                $agentParameters = array();
                $agentParameters["role"]     = $a["role"];
                $agentParameters["homepage"] = $a["homepage"];
                $agentParameters["logoURL"]  = "";
                $agentParameters["fullName"] = $a["name"];
                $agents[] = new \SchemaAgent($agentParameters);
            }
            $data_object_parameters["agents"] = $agents;
        }
        return $data_object_parameters;
    }
}
?>