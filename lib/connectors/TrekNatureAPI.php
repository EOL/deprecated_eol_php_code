<?php
namespace php_active_record;
/* connector: [895] WEB-5844 */

class TrekNatureAPI
{
    function __construct($resource_id = false)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_agent_ids = array();
        $this->download_options = array('download_wait_time' => 500000, 'timeout' => 900, 'download_attempts' => 1);
        $this->download_options["expire_seconds"] = false; // "expire_seconds" -- false => won't expire; 0 => expires now
        $this->image_list_page = "http://www.treknature.com/members/fragman/photos/";
        $this->image_summary_page = "http://www.treknature.com/viewphotos.php";
    }

    function get_all_taxa()
    {
        self::scrape_image_info();
        $this->archive_builder->finalize(TRUE);
    }

    private function scrape_image_info()
    {
        // as of 30Dec2014 there are 1-187 pages. e.g. http://www.treknature.com/members/fragman/photos/page2.htm
        $page = 1; //debug original value is 1;
        while(true)
        {
            $url = $this->image_list_page . "page" . $page . ".htm";
            if($html = Functions::lookup_with_cache($url, $this->download_options))
            {
                echo "\n[$url]";
                // <a href="/viewphotos.php?l=3&p=300942">
                if(preg_match_all("/viewphotos.php(.*?)\"/ims", $html, $arr))
                {
                    if($val = $arr[1]) {} //print_r($val);
                    else echo "\ninvestigate no images [$url]\n";
                    
                    foreach($arr[1] as $param)
                    {
                        $rec = array();
                        $image_url = $this->image_summary_page . $param;
                        if($html2 = Functions::lookup_with_cache($image_url, $this->download_options))
                        {
                            $rec["page"] = $url;
                            $rec["source"] = $image_url;
                            $temp = explode("p=", $rec["source"]);
                            $rec["image_id"] = $temp[1];
                            
                            // Genre: <a href="/photos.php?filter=LA">Landscapes</a></td>
                            if(preg_match("/Genre:(.*?)<\/td>/ims", $html2, $arr2))
                            {
                                if($rec["image_id"] == "20786") $rec["sciname"] = "Alnus glutinosa";
                                elseif(is_numeric(stripos($arr2[1], "Landscapes"))) continue;
                            }
                            
                            if(!@$rec["sciname"])
                            {
                                if(preg_match("/<h1>(.*?)<\/h1>/ims", $html2, $arr2)) $rec["sciname"] = $arr2[1];
                            }
                            
                            if(preg_match("/Photographer's Note<\/span><\/td><\/tr>(.*?)<\/td>/ims", $html2, $arr2)) $rec["caption"] = trim(strip_tags($arr2[1]));
                            // <h1>Capparis decidua</h1><br><img src="http://i1.treknature.com/photos/1990/capparis_decidua1.jpg" WIDTH="750" HEIGHT="500" border="1" alt="Capparis decidua"></td>
                            if(preg_match("/<\/h1>(.*?)<\/td>/ims", $html2, $arr2))
                            {
                                if(preg_match("/src=\"(.*?)\"/ims", $arr2[1], $arr2)) $rec["src"] = $arr2[1];
                            }
                            if(preg_match("/<h2>Photos:(.*?)<\/div>/ims", $html2, $arr2))
                            {
                                $temp = array_map('trim', explode(" >> ", $arr2[1]));
                                $temp = array_map('strip_tags', $temp);
                                // print_r($temp);
                                if    ($val = @$temp[5]) $rec["location"] = str_ireplace("register", "", $val) . ", " . $temp[2];
                                elseif($val = @$temp[4]) $rec["location"] = str_ireplace("register", "", $val) . ", " . $temp[2];
                            }
                        }
                        // print_r($rec);
                        if($rec) self::process_record($rec);
                        else echo "\ninvestigate no image details [$image_url]\n";
                    }
                }
                else echo "\ninvestigate no images [$url]\n";
                
                if(!is_numeric(stripos($html, '.htm">></a>'))) break; // last page, nothing to follow
            }
            // if($page >= 2) break; //debug
            $page++;
        }
    }

    private function process_record($rec)
    {
        $rec["sciname"] = self::valid_sciname($rec);

        if($rec["sciname"])
        {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = str_replace(" ", "_", $rec["sciname"]);
            $taxon->scientificName              = $rec["sciname"];
            $taxon->furtherInformationURL       = $rec["page"];
            if(!isset($this->taxa[$taxon->taxonID]))
            {
                $this->taxa[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
        }
        else return;

        if($rec["src"])
        {
            $agent_ids = self::process_agent();
            $mr = new \eol_schema\MediaResource();
            // if($reference_ids) $mr->referenceID = implode("; ", $reference_ids); //not used at the moment...
            if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
            $mr->taxonID                = $taxon->taxonID;
            $mr->identifier             = $rec["image_id"];
            $mr->type                   = 'http://purl.org/dc/dcmitype/StillImage';
            $mr->format                 = Functions::get_mimetype($rec["src"]);
            $mr->furtherInformationURL  = $rec["source"];
            $mr->CVterm                 = '';
            $mr->title                  = '';
            $mr->UsageTerms             = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
            $mr->accessURI              = $rec["src"];
            $mr->Owner                  = '';
            $mr->publisher              = '';
            $mr->description            = utf8_encode($rec["caption"]);
            $this->archive_builder->write_object_to_file($mr);
        }
    }

    private function valid_sciname($rec)
    {
        if($rec["image_id"] == "254976") return "Ranunculus asiaticus";
        
        $sciname = trim($rec["sciname"]);
        
        if($sciname == "Sinai Hawthorn")                return "Crataedus sinaica";
        elseif($sciname == "Mt Hermon Viper")           return "Vipera bornmuellri";
        elseif($sciname == "boars")                     return "Sus scrofa";
        elseif($sciname == "Sambar")                    return "Rusa unicolor";
        elseif($sciname == "spoonbills")                return "Threskiornithidae";
        elseif($sciname == "drongo")                    return "Dicrurus";
        elseif($sciname == "Pygmy Cormorant")           return "Microcarbo pygmeus";
        elseif($sciname == "Red Naped Ibis")            return "Pseudibis papillosa";
        elseif($sciname == "Hooded Crow")               return "Corvus cornix";
        elseif($sciname == "Syrian Woodpecker")         return "Dendrocopos syriacus";
        elseif($sciname == "Asian Wild Ass")            return "Equus hemionus";
        elseif($sciname == "Red-breasted Goose")        return "Branta ruficollis";
        elseif($sciname == "California Condor")         return "Gymnogyps californianus";
        elseif($sciname == "Gila Monster")              return "Heloderma suspectum";
        elseif($sciname == "Black-wonged Stilt")        return "Himantopus himantopus";
        elseif($sciname == "Black-winged Stilt")        return "Himantopus himantopus";
        elseif($sciname == "Glossy Ibis")               return "Plegadis falcinellus";
        elseif($sciname == "Nubian Ibex")               return "Capra nubiana";
        elseif($sciname == "Trumpeter Swan")            return "Cygnus buccinator";
        elseif($sciname == "Australian Wood Duck")      return "Chenonetta jubata";
        elseif($sciname == "Bronze Wing")               return "Phaps";
        elseif($sciname == "White Spider Orchid")       return "Caladenia longicauda";
        elseif($sciname == "Yellow Patersonia")         return "Patersonia";
        elseif($sciname == "Black Kangaroo Paw")        return "Macropidia fuliginosa";
        elseif($sciname == "stachys circinata")         return "Stachys circinata";
        elseif($sciname == "Squacco Heron")             return "Ardeola ralloides";
        elseif($sciname == "Spur-Winged Plover")        return "Vanellus spinosus";
        elseif($sciname == "Great Cormorant")           return "Phalacrocorax carbo";
        elseif($sciname == "bulb mix")                  return "Sparaxis elegans";
        elseif($sciname == "Marine Gastropod Molluscs") return "Gastropoda";
        elseif($sciname == "Blue Cranes")               return "Anthropoides paradiseus";
        elseif($sciname == "American Gull")             return "Larus smithsonianus";
        elseif($sciname == "Egyptian Goose")            return "Alopochen aegyptiaca";
        elseif($sciname == "Creeping Cherry")           return "Cerasus prostrata";
        elseif($sciname == "Italian Sainfoin")          return "Hedysarum coronarium";
        elseif($sciname == "Doum Palm")                 return "Hyphaene thebaica";
        elseif($sciname == "Cape Gannets")              return "Morus capensis";
        elseif($sciname == "African Olive")             return "Olea africana";
        elseif($sciname == "White Broom")               return "Retama raetam";
        elseif($sciname == "Pelargonium Splendens")     return "Pelargonium splendens";
        elseif($sciname == "Wild Sweet Pea")            return "Lathyrus odoratus";
        elseif($sciname == "Strawberry Tree")           return "Arbutus andrachne";
        elseif($sciname == "Mt Tabor Oak")              return "Quercus ithaburensis";
        elseif($sciname == "Lizard Orchid")             return "Himanthoglossum hircinum";
        elseif($sciname == "Queen Mallow")              return "Lavatera trimestris";
        elseif($sciname == "Syrian Pear")               return "Pyrus syriaca";
        elseif($sciname == "Crowned Anemone")           return "Anemone coronaria";
        elseif($sciname == "Caper fruit")               return "Capparis spinosa";
        elseif($sciname == "Black Pine")                return "Pinus nigra";
        elseif($sciname == "Cyprus Cedar")              return "Cedrus brevifolia";
        elseif($sciname == "Desert Tulip")              return "Tulipa systola";
        elseif($sciname == "Desert Globe Thistle")      return "Echinops polyceras";
        elseif($sciname == "Winter Corcus")             return "Crocus hyemalis";
        elseif($sciname == "figs")                      return "Ficus carica";
        elseif($sciname == "Desert Swallowtail in Sinai") return "Papilio saharae saharae";
        elseif($sciname == "Syrian Orchid")             return "Orchis syriaca";
        elseif($sciname == "Trodoos Orchid")            return "Orchis troodi";
        elseif($sciname == "Galilee Orchid")            return "Orchis galilaea";
        elseif($sciname == "Maritime Squill")           return "Drimia maritima";
        elseif($sciname == "Nazareth Iris")             return "Iris bismarckiana";
        elseif($sciname == "Aleppo Pine")               return "Pinus halepensis";
        elseif($sciname == "Prickly Lettuce")           return "Lactuca serriola";
        elseif($sciname == "peacock")                   return "Pavo";
        elseif($sciname == "fern")                      return "Plantae";
        elseif($sciname == "fynbos")                    return "Leucospermum catherinae";
        elseif($sciname == "From flower to fruit")      return "Cerastium fragillimum";
        elseif($sciname == "Coral Peony")               return "Paeonia";
        elseif($sciname == "the hidden tiger")          return "Panthera tigris";
        elseif($sciname == "lunch time")                return "Araneae";
        elseif($sciname == "Unknown flower")            return "Magnoliophyta";
        elseif($sciname == "Jericho Garlic")            return "Allium hierochuntinum";
        elseif($sciname == "Lebanon Garlic")            return "Allium libani";
        elseif($sciname == "Black Garlic")              return "Allium nigrum";
        elseif($sciname == "Joshua Tree National Park") return "Yucca brevifolia";
        elseif($sciname == "Italian Orchid")            return "Orchis italica";
        elseif($sciname == "lizard")                    return "Reptilia";
        elseif($sciname == "Tragacanth Vegetation in Mt Hermon")    return "Astragalus cruentiflorus";
        elseif($sciname == "who is eating creeping cherry fruits?") return "Cerasus prostrata";
        elseif(is_numeric(stripos($sciname, "gazelle")))        return "Gazella";
        elseif(is_numeric(stripos($sciname, "langur")))         return "Semnopithecus";
        elseif(is_numeric(stripos($sciname, "Capra nubiana")))  return "Capra nubiana";
        elseif(is_numeric(stripos($sciname, "Cyclamen")))       return "Cyclamen";
        elseif(is_numeric(stripos($sciname, "Aloe vera")))      return "Aloe vera";
        elseif(is_numeric(stripos($sciname, "Senna")))          return "Senna";
        elseif(is_numeric(stripos($sciname, "butterfly")))      return "Lepidoptera";
        elseif(is_numeric(stripos($sciname, "bamboo")))         return "Bambuseae";
        elseif(is_numeric(stripos($sciname, "woodpecker")))     return "Picidae";
        elseif(is_numeric(stripos($sciname, "Petrified")))      return "Plantae";
        elseif(is_numeric(stripos($sciname, "Fossil Trunk")))   return "Plantae";
        elseif(is_numeric(stripos($sciname, "saguaro land")))   return "Carnegiea gigantea";
        elseif(is_numeric(stripos($sciname, "truffles")))       return "Fungi";

        $exclude_exact_match = array("alpine blooming", "eggs", "desert bloom", "saline", "Indian Forest", "spring", "Red Sea in winter", "caterpillar on caper", "spring in West Australia", "Hamamat Main", "Lake Towada", "desert snow", "succulent", "Mt Hermon Fritillary");
        foreach($exclude_exact_match as $str)
        {
            if($sciname == $str) return false;
        }
        
        if(substr($sciname, -7) == " desert")
        {
            echo "\nexcluded 7 [$sciname][$rec[source]]";
            return false;
        }

        $exclude = array("wonderful", "glacier", "peacock", "river", "fruit", "flower", "lake", "saline", " land", "garlic");
        foreach($exclude as $str)
        {
            if(is_numeric(stripos($sciname, $str)))
            {
                echo "\nexcluded 1 [$sciname][$rec[source]]";
                return false;
            }
        }
        
        if(ctype_lower(substr($sciname,0,1)))
        {
            echo "\nexcluded 2 [$sciname][$rec[source]]";
            return false;
        }
        
        $temp = explode(" ", $sciname);
        if(ctype_upper(substr(@$temp[1],0,1)))
        {
            echo "\nexcluded 3 [$sciname][$rec[source]]";
            return false;
        }
        
        return $sciname;
    }
    
    private function process_agent()
    {
        $agent_ids = array();
        $r = new \eol_schema\Agent();
        $r->term_name       = "Ori Fragman Sapir";
        $r->identifier      = md5("Ori Fragman Sapir");
        $r->agentRole       = "creator";
        $r->term_homepage   = "http://www.treknature.com/members/fragman/";
        $agent_ids[] = $r->identifier;
        if(!isset($this->resource_agent_ids[$r->identifier]))
        {
           $this->resource_agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }
    
    private function clean_str($str)
    {
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011", "	", ""), " ", trim($str));
        $str = str_ireplace(array("    "), " ", trim($str));
        $str = str_ireplace(array("   "), " ", trim($str));
        $str = str_ireplace(array("  "), " ", trim($str));
        return $str;
    }

}
?>