<?php
define("PODCAST_FEED", "http://education.eol.org/podcast/newfeed");
class LearningEducationAPI
{
    public static function get_all_taxa()
    {
        $taxon["Pandea rubra"]           = "Red Paper Latern Jellyfish";
        $taxon["Jadera haematoloma"]     = "Red-Shouldered Soapberry Bug";
        $taxon["Acroporidae"]            = "Coral";
        $taxon["Carcharodon carcharias"] = "Great White Shark";
        $taxon["Holothuroidea"]          = "Sea Cucumbers";
        $taxon["Cinchona pubescens"]     = "Quinine Tree";
        $taxon["Solenopsis invicta"]     = "E.O. Wilson";
        $taxon["Paraponera clavata"]     = "E.O. Wilson";
        $taxon["Xanthoparmelia plittii"] = "Lichens";
        $taxon["Umbilicaria mammulata"]  = "Lichens";
        $taxon["Eubalaena glacialis"]    = "Right Whale";
        $taxon["Urocyon littoralis"]     = "Island Fox";
        $GLOBALS['hard_coded_taxon'] = $taxon;
        $GLOBALS['sound_objects'] = self::prepare_sound_objects();
        return self::get_taxa();
    }
    
    public static function get_taxa()
    {
        $used_collection_ids = array();
        $response = self::create_taxa();//this will output the raw (but structured) output from the external service
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;
            $used_collection_ids[$rec["sciname"]] = true;
        }
        return $page_taxa;
    }
    
    function prepare_sound_objects()
    {
        $sounds = array();
        $xml = simplexml_load_file(PODCAST_FEED);
        foreach($xml->channel->item as $item)
        {
            $item_itunes = $item->children("http://www.itunes.com/dtds/podcast-1.0.dtd");
            $title = trim($item->title);
            
            /* sample RSS feed
            <item>
                <title>Red Paper Latern Jellyfish</title>
                <link>http://education.eol.org/podcast/red-paper-latern-jellyfish-0</link>
                <description>&lt;p&gt;Vacuumed up from</description>
                <enclosure url="http://education.eol.org/sites/default/files/audio_files/OSAAT_redlantern_0.mp3" length="3997603" type="audio/mpeg" />
                <itunes:duration>5:33</itunes:duration>
                <itunes:author />
                <itunes:subtitle>Vacuumed up from its habi</itunes:subtitle>
                <itunes:summary>Vacuumed up from</itunes:summary>
                <pubDate>Thu, 21 Apr 2011 16:35:07 +0000</pubDate>
                <guid>http://education.eol.org/sites/default/files/audio_files/OSAAT_redlantern_0.mp3</guid>
            </item>
            */
            
            $description = $item->description;
            if($item_itunes->duration) $description .= "<br>Duration: " . $item_itunes->duration;
            if($item->pubDate) $description .= "<br>Published: " . $item->pubDate;
            $agent = array();
            $agent[] = array("role" => "author", "homepage" => "http://www.eol.org", "name" => "Encyclopedia of Life");
            $agent[] = array("role" => "project", "homepage" => "http://www.atlantic.org/", "name" => "Atlantic Public Media");

            $sounds[$title][] = array("identifier"  => trim($item->guid),
                                      "mediaURL"    => trim($item->enclosure["url"]),
                                      "mimeType"    => trim($item->enclosure["type"]),
                                      "dataType"    => "http://purl.org/dc/dcmitype/Sound",
                                      "description" => $description,
                                      "title"       => trim($item->title),
                                      "location"    => "",
                                      "dc_source"   => trim($item->link),
                                      "agent"       => $agent);
        }
        return $sounds;
    }
    
    function create_taxa()
    {
        $hard_coded_taxon = $GLOBALS['hard_coded_taxon'];
        $taxa = array();
        foreach($hard_coded_taxon as $taxon => $title)
        {
            $title = trim($title);
            $taxa[] = array("id"        => "",
                            "kingdom"   => "",
                            "phylum"    => "",
                            "class"     => "",
                            "order"     => "",
                            "family"    => "",
                            "sciname"   => $taxon,
                            "dc_source" => "",
                            "do_sounds" => @$GLOBALS['sound_objects'][$title]
                           );
        }
        return $taxa;
    }

    function get_sciname($string)
    {
        $pos = strripos($string,'-');
        if(is_numeric($pos)) return trim(substr($string, $pos + 1, strlen($string)));
        else return trim($string);
    }

    public static function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;
        $taxon["identifier"] = "";
        $taxon["source"] = $rec["dc_source"];
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        $taxon["kingdom"] = ucfirst(trim($rec["kingdom"]));
        $taxon["phylum"] = ucfirst(trim($rec["phylum"]));
        $taxon["class"] = ucfirst(trim($rec["class"]));
        $taxon["order"] = ucfirst(trim($rec["order"]));
        $taxon["family"] = ucfirst(trim($rec["family"]));
        if(@!$taxon["genus"] && @preg_match("/^([^ ]+) /", ucfirst(trim($rec["sciname"])), $match)) $taxon["genus"] = $match[1];
        $sounds = $rec["do_sounds"];
        if($sounds)
        {
            foreach($sounds as $sound)
            {
                $data_object = self::get_data_object($sound);
                if(!$data_object) return false;
                $taxon["dataObjects"][] = new SchemaDataObject($data_object);
            }
        }
        $taxon_object = new SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    public static function get_data_object($rec)
    {
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = $rec["identifier"];
        $data_object_parameters["source"] = $rec["dc_source"];
        $data_object_parameters["dataType"] = $rec["dataType"];
        $data_object_parameters["mimeType"] = @$rec["mimeType"];
        $data_object_parameters["mediaURL"] = @$rec["mediaURL"];
        $data_object_parameters["rights"] = "";
        $data_object_parameters["rightsHolder"] = "Encyclopedia of Life";
        $data_object_parameters["title"] = @$rec["title"];
        $data_object_parameters["description"] = $rec["description"];
        $data_object_parameters["location"] = $rec["location"];
        $data_object_parameters["license"] = 'http://creativecommons.org/licenses/by-nc/3.0/';
        if(@$rec["subject"])
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = @$rec["subject"];
            $data_object_parameters["subjects"][] = new SchemaSubject($subjectParameters);
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
                $agents[] = new SchemaAgent($agentParameters);
            }
            $data_object_parameters["agents"] = $agents;
        }
        return $data_object_parameters;
    }
}
?>