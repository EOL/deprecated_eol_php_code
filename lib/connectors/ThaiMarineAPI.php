<?php
namespace php_active_record;
// connector: [729]
class ThaiMarineAPI
{
    const MAIN_PAGE = "http://chaloklum-diving.com/marine-life-koh-phangan/";
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->media_ids = array();
        $this->site_links = array();
        $this->site_links_info = array();
        $this->site_thumbnails = array();
        $this->site_thumbnails_gallery = array();
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->debug = array();
        $this->agent_id = array();
        $this->pagination_links = array();
    }

    function get_all_taxa()
    {
        $this->agent_id[] = self::create_agent();
        $links = self::get_links(self::MAIN_PAGE);
        $this->site_links = array_merge($this->site_links, $links);
        self::process_links($links);
        self::process_links($this->pagination_links);
        self::process_site_thumbnails_gallery($this->site_thumbnails_gallery);
        $this->create_archive();
    }

    private function process_links($links)
    {
        foreach($links as $link)
        {
            $links2 = self::get_links($link);
            $this->site_links = array_merge($this->site_links, $links2);
            foreach($links2 as $link2)
            {
                $links3 = self::get_links($link2);
                $this->site_links = array_merge($this->site_links, $links3);
                foreach($links3 as $link3)
                {
                    $links4 = self::get_links($link3);
                    $this->site_links = array_merge($this->site_links, $links4);
                    foreach($links4 as $link4)
                    {
                        $links5 = self::get_links($link4);
                        $this->site_links = array_merge($this->site_links, $links5);
                        foreach($links5 as $link5)
                        {
                            $links6 = self::get_links($link5);
                            $this->site_links = array_merge($this->site_links, $links6);
                            foreach($links6 as $link6)
                            {
                                $links7 = self::get_links($link6);
                                $this->site_links = array_merge($this->site_links, $links7);
                                foreach($links7 as $link7)
                                {
                                    $links8 = self::get_links($link7);
                                    $this->site_links = array_merge($this->site_links, $links8);
                                }
                            }
                        }
                    }
                }
                // break; //debug
            }
            // break; //debug
        }
    }
    
    private function create_agent()
    {
        $r = new \eol_schema\Agent();
        $r->term_name = "Our Dive Team";
        $r->agentRole = "photographer";
        $r->identifier = md5($r->term_name . "|" . $r->agentRole);
        $r->term_homepage = "http://chaloklum-diving.com/";
        $this->archive_builder->write_object_to_file($r);
        return $r->identifier;
    }

    private function process_site_thumbnails_gallery($records)
    {
        /*<a href="http://chaloklum-diving.com/wp-content/blogs.dir/5/files/whipray-jenkins-himantura-jenkinsii/whipray-jenkins-2.jpg" rel="shadowbox[sbpost-675];player=img;" 
        title="Jenkins Whipray; Himantura jenkinsii"  >
        <img title="Jenkins Whipray; Himantura jenkinsii" alt="Jenkins Whipray; Himantura jenkinsii" 
        src="http://chaloklum-diving.com/wp-content/blogs.dir/5/files/whipray-jenkins-himantura-jenkinsii/thumbs/thumbs_whipray-jenkins-2.jpg" width="190" height="140" />
        </a>*/
        foreach($records as $key => $recs)
        {
            foreach($recs["rekords"] as $rec)
            {
                if(preg_match("/href=\"(.*?)\"/ims", $rec, $arr)) $source = $arr[1];
                if(preg_match("/src=\"(.*?)\"/ims", $rec, $arr)) $image = $arr[1];
                if(preg_match("/alt=\"(.*?)\"/ims", $rec, $arr)) $desc = $arr[1];
                if(in_array($recs["title"], array("Marine Plants; Plantae."))) $sciname = self::get_sciname($desc);
                else
                {
                    $sciname = self::get_sciname($recs["title"]);
                    if(in_array($sciname, array("Other", "Mixed photos", "Odd fish sightings", "Juvenile", "s", "Squirrelfish", "Chlororus-sordidus", "Meiacanthus-lineatus"))) $sciname = self::get_sciname($desc);
                }
                //manual adjustment
                if($key == "http://chaloklum-diving.com/marine-life-koh-phangan/corals-more-cnidaria/hexacorals-zoantharia/hard-corals-scleractinia/mixed-hard-corals/") $sciname = "Scleractinia"; // hard corals
                $sciname = str_ireplace(array(" close", " field", " small"), "", $sciname);
                if(in_array($sciname, array("Unidentified Juvenile", "Unidentified Juvenile", "Unidentified microscopic baby fish", "2-parrots-tusk-rabbit-fish-wrasse-damsel-dartgoby", "yellowspottedtrevally-n-bluebarredparrotfishmale", "bannerfish-fusilier-n-coralfish", "banners-n-butterfly", "fusiliers-n-damsels", "grouper-n-snapper", "grouper-squirrel-n-moray-tail", "lined-butterflys-moon-wrasse-n-russells-snapper", "parrot-banner-damsels", "queenfish-n-bakaruda", "spanish-mackerel-n-pickhandle-barracuda", "threadfin-butterfly-n-wrasse", "titan-chums-s", "trigger-racoons-moonies-a-cleaner", "white-speckled-n-eye", "white-speckled-n-eye-n-black-tip", "wrasse-box-n-damsels", "wrasse-rabbitfish-n-seargeant"))) continue; // disregard
                
                /* used in debugging...
                if($sciname == "xxx")
                {
                    echo "\n" . $recs["title"];
                    echo "\n sn:[$sciname][$key][$desc]\n";
                    exit;
                }*/
                
                if(!$sciname) continue;
                $image = str_ireplace("thumbs/thumbs_", "", $image);
                $record = array("sciname" => $sciname, "image" => $image, "desc" => $desc, "source" => $key);
                self::create_instances_from_taxon_object($record);
                $this->debug[$sciname] = 1;
            }
        }
        // print_r($this->debug);
    }
    
    private function get_sciname($string)
    {
        $string = self::remove_parenthesis($string);
        if($string == "Crustaceans; phyllum Arthropoda.") return "Arthropoda";
        elseif($string == "Sea Cucumbers, Urchins and Stars: Echinodermata") return "Echinodermata";
        elseif($string == "Bryozoans, aka Ectoprocta") return "Ectoprocta";
        elseif($string == "Pocillopora species close") return "Pocillopora";
        elseif($string == "Zigzag Oyster sex") return "Lopha folium";
        // elseif(in_array($string, array("Unidentified Seaweed", "Unidentified Branching Algae", "Very small Green Algae 5mm", "Unidentified Branching Algae on Chaloklum Pier", "Red Algae maybe", "Red Branching Algae on Tube Anemone", "Encrusting Algae maybe", "Encrusting Algae meets Bryozoan", "Filamentous Green Algae"))) return "Algae";
        elseif(in_array($string, array("Unidentified Seaweed"))) return "Algae";
        elseif($string == "&quot;Phallic Coral&quot;, Psammocora species") return "Psammocora";
        elseif($string == "Spikeweed, Actinotrichia fragilis maybe...") return "Actinotrichia fragilis";
        elseif(is_numeric(stripos($string, "Sargasso Seaweed"))) return "Sargassum";
        elseif(is_numeric(stripos($string, " Algae"))) return "Algae";
        elseif($string == "Branching Red Algae Amphiroa species") return "Amphiroa";
        elseif($string == "Avrainvillea species on Koh Phangan, Thailand") return "Avrainvillea";
        elseif($string == "Branching Red Algae - Amphiroa valonioides") return "Amphiroa valonioides";
        elseif($string == "Bryopsis minor with communal sea squirt") return "Bryopsis minor";
        elseif($string == "Bryopsis on soft coral") return "Bryopsis";
        elseif($string == "Other Monocle Bream") return "Scolopsis bilineata";
        elseif($string == "Crimson Soldierfish Myripristis murdjan") return "Myripristis murdjan";
        elseif($string == "Jans&#8217;s Pipefish; Doryhamphus janssi") return "Doryhamphus janssi";
        elseif($string == "Soldier &amp; Squirrelfish; Holocentridae") return "Holocentridae";
        elseif($string == "Other Carangidae") return "Carangidae";
        elseif($string == "Reef Cuttlefish mating behaviour") return "Sepiida";
        elseif($string == "Other Wrasse") return "Labridae";
        elseif($string == "Longnose Parrotfish; Hipposcarus-harid") return "Hipposcarus harid";
        elseif($string == "Blue spotted Flathead; Platycephalus-caeruleopunctatus") return "Platycephalus caeruleopunctatus";
        elseif($string == "Other Rhizostome Jellyfish") return "Rhizostomae";
        elseif($string == "Other Annelid Worms") return "Annelida";
        elseif($string == "Other Octocorals") return "Octocorallia";
        elseif($string == "Other Sea Cucumbers") return "Echinodermata";
        elseif($string == "Checkered Snapper Lutjanus decussatus") return "Lutjanus decussatus";
        elseif($string == "Other Sea Anemones") return "Actiniaria";
        elseif($string == "Blotched Goby Coryphopterus inframaculatus") return "Coryphopterus inframaculatus";
        elseif($string == "One-spot Snapper Lutjanus monostigma") return "Lutjanus monostigma";
        elseif($string == "Lined Fang Blenny; Meiacanthus-lineatus") return "Meiacanthus lineatus";
        elseif($string == "Reef Octopus encounter May 2010") return "Octopus";
        elseif($string == "Comb Jellys; Phyllum Ctenophora") return "Ctenophora";
        elseif($string == "Stony Coral Heteropsammia eupammides") return "Heteropsammia eupammides";
        elseif($string == "Stony Coral Balanophyllia elegans") return "Balanophyllia elegans";
        elseif($string == "Giant clam with Montipora coral surrounding.") return "Acropora";
        elseif($string == "bakaruda-n-bigeyes") return "Sphyraena";
        elseif($string == "Bandfish Acanthocepola species") return "Acanthocepola";
        elseif($string == "Snakefish Trachinocephalus myops") return "Trachinocephalus myops";
        elseif($string == "Deep bodied Silverbelly Gerres erythrourus") return "Gerres erythrourus";
        elseif($string == "Ponyfish Leiognathus fasciatus") return "Leiognathus fasciatus";
        elseif($string == "Parapercis species we think") return "Parapercis";
        elseif($string == "Moorish Idol Zanclus cornutus") return "Zanclus cornutus";
        elseif($string == "Large Shrimp Goby Cryptocentrus species") return "Cryptocentrus";
        elseif($string == "Green Shrimp Goby Cryptocentrus octafasciatus") return "Cryptocentrus octafasciatus";
        elseif(in_array($string, array("Flagfin Shrimp Goby Mahidolia mystacina with Shrimp", "Flagfin Shrimp Goby Mahidolia mystacina"))) return "Mahidolia mystacina";
        elseif(in_array($string, array("blenny-head", "blenny-head-2", "blenny-head-3", "blenny-upper-body"))) return "Blenniidae";
        elseif(in_array($string, array("false-cleanerfish-2", "false-cleanerfish-aspidontus-taeniatus"))) return "Aspidontus taeniatus";
        elseif($string == "blenny-omobranchus-sp-uuh") return "Omobranchus";
        elseif(in_array($string, array("rabbitfish-juve-demise", "rabbitfish-juves", "rabbitfish-juves-2", "rabbitfish-juves-en-masse"))) return "Siganus";
        elseif(in_array($string, array("Unidentified Goby", "Unidentified Goby tail", "Shrimp Gobies unidentified", "Shrimp Goby unidentified", "Drab Shrimp Goby with Shrimp"))) return "Gobiidae";
        elseif(in_array($string, array("pallid-pipefish-uuh", "slender-pipefish-2"))) return "Syngnathinae";
        elseif(in_array($string, array("squirrelfish-red-1", "squirrelfish-red-3"))) return "Holocentrus";
        elseif($string == "giant-trevally-caranx-ignobilis") return "Caranx ignobilis";
        elseif($string == "trevally-fringe-finned-pantolobus-radiatus") return "Pantolobus radiatus";
        elseif($string == "trevally-golden-juv-gnathanodon-speciosus") return "Gnathanodon speciosus";
        elseif($string == "trevally-fringe-finned-2") return "Caranx";
        elseif($string == "slender-pipefish-trachyrhampus-longirostris-1") return "Trachyrhampus longirostris";
        elseif($string == "Platycephalus-caeruleopunctatus") return "Platycephalus caeruleopunctatus";
        elseif(in_array($string, array("Unidentified Juvenile", "Small juvenile fish with Sea Pen"))) return "";
        elseif($string == "Unidentified Hard Coral") return "Scleractinia";
        elseif(in_array($string, array("Unidentified bivalve Mollusc", "Bivalve with encrusting Sponge and Barnacles", "Large Mussel, free standing out on the sand.", "Unidentified Clam; small", "Unidentified Clam"))) return "Bivalvia";
        elseif(in_array($string, array("Scythozoa; other Jellyfish", "Pelagidae; Sea nettles", "Ellisellidae fam; Wire Coral", "Pennatulacea; Sea Pens", "Gorgonacea; Sea Fans/Whip Corals", "Ellisellidae; Sea Fans.", "Echinigorgia; Sea Fans", "Nephtheidae; more Soft corals", "Alcyoniidae; some Soft Corals", "Heteractis Anemone, unknown species", "Anthopleura species, perhaps", "Dardanus lagapodes; Hermit Crab", "Dardanus megistos; Hermit Crab", "Oceanapia sagittaria; Porifera."))) //get the sciname on the left side
        {
            $temp = explode(";", $string);
            if(count($temp) == 1) $temp = explode(",", $string);
            if($val = @$temp[0]) return Functions::canonical_form($val);
            else return "";
        }
        else // get the sciname on the right side - normally
        {
            $string = trim(str_ireplace(" family", "", $string));
            $temp = explode(";", $string);
            if(count($temp) == 1) $temp = explode(",", $string);
            if($val = @$temp[1]) return Functions::canonical_form($val);
            else return Functions::canonical_form($temp[0]);
        }
    }

    private function get_links($url)
    {
        if(!$url = trim($url)) return array(); // for blank urls
        $links = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match_all("/<div class=\"ngg-albumtitle\">(.*?)<\/div>/ims", $html, $arr))
            {
                foreach($arr[1] as $line)
                {
                    $this->site_links_info[$url][] = $line;
                    if(preg_match("/\"(.*?)\"/ims", $line, $arr2)) $links[] = $arr2[1];
                }
            }
            if(preg_match_all("/<div class=\"ngg-thumbnail\">(.*?)<p><\/p>/ims", $html, $arr)) $this->site_thumbnails[$url] = $arr[1];
            if(preg_match_all("/<div class=\"ngg-gallery-thumbnail\" >(.*?)<\/div>/ims", $html, $arr))
            {
                // <h1 class="category-title">Blue Sponge; Haliclona species.</h1>
                if(preg_match("/<div class='ngg-navigation'>(.*?)<\/div>/ims", $html, $arr3))
                {
                    $urls = self::get_pagination_links($arr3[1]);
                    $this->pagination_links = array_merge($this->pagination_links, $urls);
                }
                if(preg_match("/<h1 class=\"category-title\">(.*?)<\/h1>/ims", $html, $arr2))
                {
                    $this->site_thumbnails_gallery[$url]["title"] = $arr2[1];
                    $this->site_thumbnails_gallery[$url]["rekords"] = $arr[1];
                }
                else echo "\n investigate [$url] no title \n";
            }
        }
        return $links;
    }
    
    private function get_pagination_links($html)
    {
        if(preg_match_all("/href=\"(.*?)\"/ims", $html, $arr)) return array_unique($arr[1]);
    }
    
    function create_instances_from_taxon_object($rec)
    {
        $taxon_id = str_replace(" ", "_", $rec["sciname"]);
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $taxon_id;
        $taxon->scientificName              = $rec["sciname"];
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxa[$taxon->taxonID] = $taxon;
            $this->taxon_ids[$taxon->taxonID] = 1;
        }
        $parts = pathinfo($rec["image"]);
        $media_id = $parts["basename"];
        self::create_images($rec["desc"], $taxon_id, $media_id, $rec["image"], $rec["source"]);
    }

    private function create_images($description, $taxon_id, $media_id, $media_url, $source_url)
    {
        if(in_array($media_id, $this->media_ids)) return;
        $this->media_ids[] = $media_id;
        $mr = new \eol_schema\MediaResource();
        if($val = $this->agent_id) $mr->agentID = implode("; ", $val);
        $mr->taxonID                = $taxon_id;
        $mr->identifier             = $media_id;
        $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
        $mr->language               = 'en';
        $mr->format                 = Functions::get_mimetype($media_url);
        $mr->CVterm                 = "";
        $mr->rights                 = "";
        $mr->Owner                  = "Chaloklum Diving";
        $mr->title                  = "";
        $mr->UsageTerms             = "http://creativecommons.org/licenses/by/3.0/";
        $mr->description            = $description;
        $mr->accessURI              = $media_url;
        $mr->furtherInformationURL  = $source_url;
        $this->archive_builder->write_object_to_file($mr);
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }

    private function remove_parenthesis($string)
    {
        return trim(preg_replace('/\s*\([^)]*\)/', '', $string));
    }

}
?>