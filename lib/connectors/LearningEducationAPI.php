<?php
namespace php_active_record;
/* connector: 257 
Partner provided an XML feed for their dataObjects and connector scrapes the site for the taxa names.
*/

define("PODCAST_FEED", "http://podcast.eol.org/podcast/newfeed");
define("PODCAST_DETAILS_PAGE", "http://podcast.eol.org/podcast/");

class LearningEducationAPI
{
    public static function get_all_taxa()
    {
        $podcast_taxon_names = self::all_podcast_scientific_names();
        $podcast_data_objects = self::all_podcast_data_objects();

        $all_taxa = array();
        foreach($podcast_data_objects as $title => $data_object)
        {
            if($scientific_names = @$podcast_taxon_names[$title])
            {
                foreach($scientific_names as $scientific_name)
                {
                    $taxon_parameters = array();
                    $taxon_parameters["scientificName"] = ucfirst(trim($scientific_name));
                    if(preg_match("/^([^ ]+) /", $taxon_parameters["scientificName"], $arr)) $taxon_parameters["genus"] = $arr[1];
                    $taxon_parameters["dataObjects"][] = $data_object;
                    $all_taxa[] = new \SchemaTaxon($taxon_parameters);
                }
            }
        }
        return $all_taxa;
    }

    public static function all_podcast_scientific_names()
    {
        $podcast_taxa = array();
        $details_page_html = Functions::get_remote_file(PODCAST_DETAILS_PAGE, array('timeout' => 240));
        if(preg_match_all("/<a href=\".*?\"><h2>(.*?)<\/h2><\/a> *?<h3 style=\"font-style:italic;margin:0 0 3px 0;\">(.*?)<\/h3>/ims", $details_page_html, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                $podcast_title = trim($match[1]);
                if($podcast_title == "One Species at a Time") continue;
                // turning commas into ands
                $match[2] = str_replace(", ", " and ", $match[2]);
                $match[2] = str_replace("variety", "var.", $match[2]);
                // splitting on ands
                $taxon_names = explode(" and ", $match[2]);
                $podcast_taxa[$podcast_title] = $taxon_names;
            }
        }
        return $podcast_taxa;
    }

    public static function all_podcast_data_objects()
    {
        $podcast_data_objects = array();
        
        $xml = Functions::get_hashed_response(PODCAST_FEED, array('download_wait_time' => 1000000, 'timeout' => 240, 'download_attempts' => 5));
        foreach($xml->channel->item as $item)
        {
            $data_object_parameters = array();
            $item_itunes = $item->children("http://www.itunes.com/dtds/podcast-1.0.dtd");
            $description = $item->description;
            if($item_itunes->duration) $description .= "<br>Duration: " . $item_itunes->duration;
            if($item->pubDate) $description .= "<br>Published: " . $item->pubDate;

            $title = trim($item->title);
            $data_object_parameters["identifier"] = trim($item->guid);
            $data_object_parameters["source"] = trim($item->link);
            $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/Sound";
            $data_object_parameters["mimeType"] = trim($item->enclosure["type"]);
            $data_object_parameters["mediaURL"] = trim($item->enclosure["url"]);
            $data_object_parameters["rightsHolder"] = "Encyclopedia of Life";
            $data_object_parameters["title"] = trim($item->title);
            $data_object_parameters["description"] = $description;
            $data_object_parameters["license"] = 'http://creativecommons.org/licenses/by-nc/3.0/';

            // add agents
            $agents = array();
            $agents[] = new \SchemaAgent(array("role" => "author", "homepage" => "http://www.eol.org", "fullName" => "Encyclopedia of Life"));
            $agents[] = new \SchemaAgent(array("role" => "project", "homepage" => "http://www.atlantic.org/", "fullName" => "Atlantic Public Media"));
            $data_object_parameters["agents"] = $agents;

            // create object
            $podcast_data_objects[$title] = new \SchemaDataObject($data_object_parameters);
        }
        return $podcast_data_objects;
    }

}
?>