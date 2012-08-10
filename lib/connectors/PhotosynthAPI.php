<?php
namespace php_active_record;
/* connector: 119 
A published resource but further maintenance was stopped as we don't show Photosynth interface anymore in V2.
*/

define("FORM_URL", "http://photosynth.net/PhotosynthHandler.ashx");
define("VIEW_URL", "http://photosynth.net/view.aspx?cid=");
define("COLLECTION_URL", "http://photosynth.net/view.aspx?cid=");
define("USER_URL", "http://photosynth.net/userprofilepage.aspx?user=");
define("TAG_SEARCHED", "eol");

class PhotosynthAPI
{
    public static function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $per_page = 100;
        
        // Get metadata about the EOL Flickr pool
        $response = self::search_collections(TAG_SEARCHED, 1, 0);
        if($response)
        {
            $total = $response->TotalResults;            
            // number of API calls to be made
            $total_pages = ceil($total / 100);
            for($i=0 ; $i<$total_pages ; $i++)
            {
                $arr = self::get_photosynth_taxa($per_page, $i, $used_collection_ids);
                $page_taxa = $arr[0];                
                if($page_taxa)
                {
                    foreach($page_taxa as $t) $all_taxa[] = $t;
                }
            }
        }
        return $all_taxa;
    }
    
    public static function get_photosynth_taxa($per_page, $page, $used_collection_ids)
    {
        $response = self::search_collections(TAG_SEARCHED, $per_page, $page);
        $page_taxa = array();
        foreach($response->Collections as $synth)
        {
            if(@$used_collection_ids[$synth->Id]) continue;
            
            $taxon = self::get_taxa_for_photo($synth);
            if($taxon) $page_taxa[] = $taxon;
            
            $used_collection_ids[$synth->Id] = true;
        }
        return array($page_taxa,$used_collection_ids);
    }
    
    public static function search_collections($search_term, $per_page, $page)
    {
        $parameters = array('validname' => 'collectionId',
                            'cmd'       => 'Search',
                            'text'      => "$per_page,$page,tag:\"$search_term\"");
        $response = Functions::curl_post_request(FORM_URL, $parameters);
        return json_decode($response);
    }
    
    public static function get_taxa_for_photo($synth)
    {
        $html = $html = Functions::get_remote_file_fake_browser(COLLECTION_URL . $synth->Id);
        $tags = self::get_synth_tags($html);
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;
        foreach($tags as $tag)
        {
            if(preg_match("/^taxonomy:subspecies=(.*)$/i", $tag, $arr))     $taxon["subspecies"] = strtolower(trim($arr[1]));
            elseif(preg_match("/^taxonomy:trinomial=(.*)$/i", $tag, $arr))  $taxon["trinomial"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:species=(.*)$/i", $tag, $arr))    $taxon["species"] = strtolower(trim($arr[1]));
            elseif(preg_match("/^taxonomy:binomial=(.*)$/i", $tag, $arr))   $taxon["scientificName"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:genus=(.*)$/i", $tag, $arr))      $taxon["genus"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:family=(.*)$/i", $tag, $arr))     $taxon["family"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:order=(.*)$/i", $tag, $arr))      $taxon["order"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:class=(.*)$/i", $tag, $arr))      $taxon["class"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:phylum=(.*)$/i", $tag, $arr))     $taxon["phylum"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:kingdom=(.*)$/i", $tag, $arr))    $taxon["kingdom"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:common=(.*)$/i", $tag, $arr))     $taxon["commonNames"][] = new \SchemaCommonName(array("name" => trim($arr[1])));
            elseif(preg_match("/^dc:license=(.*)$/i", $tag, $arr))          $license = strtolower(trim($arr[1]));
        }
        if(!$license) return false;
        if(!in_array($license, array('cc-by', 'cc-by-sa', 'cc-by-nc', 'cc-by-nc-sa', 'public domain')));        
        if(@!$temp_params["scientificName"] && @$taxon["trinomial"]) $taxon["scientificName"] = $taxon["trinomial"];
        if(@!$temp_params["scientificName"] && @$taxon["genus"] && @$taxon["species"] && !preg_match("/ /", $taxon["genus"]) && !preg_match("/ /", $taxon["species"])) $taxon["scientificName"] = $taxon["genus"]." ".$taxon["species"];
        if(@!$taxon["genus"] && @preg_match("/^([^ ]+) /", $taxon["scientificName"], $arr)) $taxon["genus"] = $arr[1];
        if(@!$taxon["scientificName"] && @!$taxon["genus"] && @!$taxon["family"] && @!$taxon["order"] && @!$taxon["class"] && @!$taxon["phylum"] && @!$taxon["kingdom"]) return false;                
        
        $taxon["dataObjects"] = array();
        if(preg_match("/window\.Microsoft\.Photosynth\.Viewer\.LoadParameters\(\".*?\",\"(.*?)\"/", $html, $arr))
        {
            $mediaURL = $arr[1];
            $data_object = self::get_data_object($synth, $license, $mediaURL, 'thumb');
            $taxon["dataObjects"][] = $data_object;
        }
        if(!$taxon["dataObjects"]) return false;
        
        $taxon_object = new \SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    public static function get_data_object($synth, $license, $mediaURL, $ctr)
    {
        switch($license)
        {
            case 'cc-by':
                $license = 'http://creativecommons.org/licenses/by/3.0/'; break;
            case 'cc-by-sa':
                $license = 'http://creativecommons.org/licenses/by-sa/3.0/'; break;
            case 'cc-by-nc':
                $license = 'http://creativecommons.org/licenses/by-nc/3.0/'; break;
            case 'cc-by-nc-sa':
                $license = 'http://creativecommons.org/licenses/by-nc-sa/3.0/'; break;
            case 'public domain':
                $license = 'http://creativecommons.org/licenses/publicdomain/'; break;
            default:
              return false;
        }
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = $synth->Id . "_" . $ctr;
        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $data_object_parameters["mimeType"] = "image/jpeg";
        $data_object_parameters["title"] = $synth->Name;        
        if($synth->Description == "") $description = "<a href='$synth->CollectionUrl'>Images collection</a>";  
        else                          $description = $synth->Description 
            . "<br><a target='$synth->Id' href='" . COLLECTION_URL . $synth->Id . "'>Photosynth</a>"
            . "<br><a target='dzc' href='$synth->CollectionUrl'>Images collection</a>";              
        $data_object_parameters["description"] = $description;        
        $data_object_parameters["mediaURL"] = $mediaURL;
        $data_object_parameters["source"] = COLLECTION_URL . $synth->Id;
        $data_object_parameters["license"] = $license;        
        $agent_parameters = array();
        $agent_parameters["fullName"] = $synth->OwnerFriendlyName;
        $agent_parameters["homepage"] = USER_URL . $synth->OwnerFriendlyName;
        $agent_parameters["role"] = "photographer";
        $data_object_parameters["agents"] = array();
        $data_object_parameters["agents"][] = new \SchemaAgent($agent_parameters);        
        return new \SchemaDataObject($data_object_parameters);
    }
    
    public function get_mediaURL($url)
    {
        if(!($xml = Functions::get_hashed_response($url)))
        {
            // url not accessible
            return;
        }                
        foreach($xml->Items->I as $rec)
        {           
            $mediaURL = str_ireplace('.dzi' , '_files/thumb.jpg', @$rec["Source"]);
        }     
        return $mediaURL;   
    }
    
    public function get_synth_tags($html)
    {
        $synth_tags = array();
        if(preg_match("/<div id=\"tagCloud\">(.*?)<\/div>/ims", $html, $arr))
        {
            if(preg_match_all("/aspx\?q=(.*?)\">/", $arr[1], $tags, PREG_SET_ORDER))
            {
                foreach($tags as $tag)
                {
                    $synth_tags[] = Functions::import_decode($tag[1]);
                }
            }
        }
        return $synth_tags;
    }
    
}
?>
