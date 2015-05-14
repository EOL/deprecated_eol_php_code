<?php
namespace php_active_record;
/* connector: [417]
http://www.illinoiswildflowers.info/
http://www.illinoiswildflowers.info/plant_insects/database.html
Connector scrapes the site: http://www.illinoiswildflowers.info/plant_insects/database.html
and assembles the information and generates the EOL XML.
*/
class PlantFeedingInsectsAPI
{
    public function __construct($test_run = false, $debug_info = true)
    {
        $this->test_run = $test_run;
        $this->debug_info = $debug_info;

        $this->path = 'http://www.illinoiswildflowers.info/flower_insects';
        $this->path = 'http://www.illinoiswildflowers.info/plant_insects';
        $this->urls = array();
        $this->urls[] = array("active" => 1, "type" => "insects", "ancestry" => array("kingdom" => "Plantae", "phylum" => "", "class" => "", "order" => "", "family" => ""));
    }

    function get_all_taxa($resource_id)
    {
        self::get_associations();
        if($this->debug_info) echo "\n\n total: " . count($GLOBALS['taxon']) . "\n";
        $all_taxa = array();
        $i = 0;
        $total = count(array_keys($GLOBALS['taxon']));
        foreach($GLOBALS['taxon'] as $taxon_name => $record)
        {
            $i++; 
            if($this->debug_info) echo "\n$i of $total " . $taxon_name;
            $record["taxon_name"] = $taxon_name;
            $arr = self::get_plant_feeding_taxa($record);
            $page_taxa = $arr[0];
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            unset($page_taxa);
        }
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        if(!($OUT = fopen($resource_path, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
          return;
        }
        fwrite($OUT, $xml);
        fclose($OUT);
        return $all_taxa; //used for testing
    }

    function get_associations()
    {
        $bird_type = array("birds", "bees", "wasps", "flies", "moths", "beetles", "bugs");
        $i = 0;
        foreach($this->urls as $path)
        {
            if($path['type'] == 'insects') $url = "http://www.illinoiswildflowers.info/plant_insects/database.html";
            else                           $url = $this->path . '/insects/' . $path['type'] . ".htm";
            if($path["active"])
            {
                if($this->debug_info) echo "\n\n$i " . $path['type'] . " [$url]\n";        
                if($path['type'] == "insects")              self::process_insects($url, $path["ancestry"]);
                elseif(in_array($path['type'], $bird_type)) self::process_birds($url, $path["ancestry"], $path['type']);
            }
            $i++;
        }
    }

    function process_birds($url, $ancestry, $type){}

    function process_insects($url, $ancestry)
    {
        if(!$html = Functions::get_remote_file($url, array('download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 5))) // 1sec wait, 10mins timeout, 5 attempts
        {
            echo("\n\n Content partner's server is down3, $url\n");
            return;
        }
        /*<a href="plants/velvetleaf.htm" name="velvetleaf">Abutilon theophrastii (Velvet Leaf)</a>*/
        if(preg_match_all("/href=\"plants(.*?)<\/a>/ims", $html, $matches))
        {
            $i = 0;
            foreach($matches[1] as $match)
            {
                /*/purs_spdwell.htm" name="purs_spdwell">Veronica peregrina (Purslane Speedwell)*/
                if(preg_match("/>(.*?)\(/ims", $match, $string_match)) $taxon_name = self::clean_str($string_match[1]);
                $taxon_name = utf8_encode($taxon_name);
                // if($taxon_name != "Aronia × prunifolia") continue; //debug

                if(preg_match("/\/(.*?)\"/ims", $match, $string_match)) $html = self::clean_str($string_match[1]);
                if(preg_match("/\((.*?)\)/ims", $match, $string_match)) 
                {
                    $common_name = self::clean_str($string_match[1]);
                    $GLOBALS['taxon'][$taxon_name]['comnames'][] = $common_name;
                }
                $GLOBALS['taxon'][$taxon_name]['html'] = "/plants/$html";
                $GLOBALS['taxon'][$taxon_name]['ancestry'] = $ancestry;
                $i++; 
                if($this->test_run)
                {
                    if($i >= 2) break; //debug
                }
            }
        }
        self::get_title_description('insects');
    }

    function get_title_description($type = null)
    {
        foreach($GLOBALS['taxon'] as $taxon_name => $value)
        {
            // if($taxon_name != "Hylaeus affinis") continue; //debug
            if(@$value['association'] != "" || @$value['gendesc'] != "") continue;

            $url = $this->path . '/insects/' . $value['html'];
            if($type == 'insects') $url = str_ireplace("/insects/", "/", $url);
            $GLOBALS['taxon'][$taxon_name]['html'] = $url;

            if($this->debug_info) echo "\n $url -- $taxon_name";
            if(!$html = Functions::get_remote_file($url, array('download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 5)))
            {
                echo("\n\n Content partner's server is down4, $url\n");
                $GLOBALS['taxon'][$taxon_name]['association'] = 'no object';
                continue;
            } 

            if(preg_match("/<big>(.*?)<\/td>/ims", $html, $match))
            {
                $desc = strip_tags(self::clean_str($match[1]), "<BR><I>");
                $desc = self::clean_str($desc);
                $desc = utf8_encode($desc);
                $GLOBALS['taxon'][$taxon_name]['association'] = $desc;
                $GLOBALS['taxon'][$taxon_name]['association_title'] = "Plant-Feeding Insects of <i>$taxon_name</i> in Illinois";
                if(preg_match("/\[(.*?)\]/ims", $desc, $string_match)) $GLOBALS['taxon'][$taxon_name]['ancestry']['family'] = $string_match[1];
            }
        }
    }

    public static function get_plant_feeding_taxa($taxon_record)
    {
        $response = self::parse_xml($taxon_record);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
        }
        return array($page_taxa);
    }

    function parse_xml($taxon_record)
    {
        $arr_data = array();
        $arr_objects = array();
        $arr_objects = self::get_objects($taxon_record, $arr_objects);
        $common_names = self::get_common_names(@$taxon_record['comnames']);
        $arr_data[] = array("identifier"   => str_replace(" ", "_", $taxon_record['taxon_name']) . "_plant_feeding",
                            "source"       => @$taxon_record['html'],
                            "kingdom"      => @$taxon_record['ancestry']['kingdom'],
                            "phylum"       => @$taxon_record['ancestry']['phylum'],
                            "class"        => @$taxon_record['ancestry']['class'],
                            "order"        => @$taxon_record['ancestry']['order'],
                            "family"       => @$taxon_record['ancestry']['family'],
                            "genus"        => '',
                            "sciname"      => $taxon_record['taxon_name'],
                            "reference"    => array(), // formerly taxon_refs
                            "synonyms"     => array(),
                            "commonNames"  => $common_names,
                            "data_objects" => $arr_objects
                           );
        return $arr_data;
    }

    function get_objects($record, $arr_objects)
    {
        $texts = array();
        if(@$record['gendesc'])     $texts[] = array("desc"     => $record['gendesc'], 
                                                     "subject"  => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription",
                                                     "title"    => '',
                                                     "type"     => 'gendesc');
        if(@$record['association'] && @$record['association'] != 'no object') $texts[] = array("desc" => $record['association'], 
                                                     "subject"  => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations",
                                                     "title"    => @$record['association_title'],
                                                     "type"     => 'association');
        /* no title: Hypoxis hirsuta, Celastrus scandens */
        foreach($texts as $text)
        {
            $agent = array();
            $agent[] = array("role" => 'source', "homepage" => 'http://www.illinoiswildflowers.info/plant_insects/database.html', "fullName" => 'John Hilty');
            $refs = self::get_references();
            $identifier     = str_replace(" ", "_", $record['taxon_name']) . "_plant_feeding_" . $text['type'];
            $description    = $text['desc'];
            $license        = "http://creativecommons.org/licenses/by-nc/3.0/";
            $agent          = $agent;
            $rightsHolder   = "John Hilty";
            $rights         = "Copyright © 2002-" . date("Y") . " by Dr. John Hilty";
            $location       = '';
            $dataType       = "http://purl.org/dc/dcmitype/Text";
            $mimeType       = "text/html";
            $title          = $text['title'];
            $subject        = $text['subject'];
            $source         = $record['html'];
            $mediaURL       = '';
            $refs           = $refs;
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $arr_objects);
        }
        return $arr_objects;
    }

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $arr_objects)
    {
        $arr_objects[]=array( "identifier"   => $identifier,
                              "dataType"     => $dataType,
                              "mimeType"     => $mimeType,
                              "title"        => $title,
                              "source"       => $source,
                              "description"  => $description,
                              "mediaURL"     => $mediaURL,
                              "agent"        => $agent,
                              "license"      => $license,
                              "location"     => $location,
                              "rights"       => $rights,
                              "rightsHolder" => $rightsHolder,
                              "reference"    => $refs,
                              "subject"      => $subject,
                              "language"     => "en"
                            );
        return $arr_objects;
    }

    function get_references()
    {
        $reference = "Hilty, J. Editor. " . date("Y") . ". Plant-Feeding Insect Database of Illinois Wildflowers. 
        World Wide Web electronic publication. illinoiswildflowers.info, version (" . date("m/Y")  . ") 
        <br>See: 
        <a href='http://www.illinoiswildflowers.info/plant_insects/misc/abbreviations.html'>Author Abbreviations</a>, 
        <a href='http://www.illinoiswildflowers.info/plant_insects/misc/citations.html'>Citations</a>";
        $refs = array();
        $refs[] = array("url" => '', "fullReference" => $reference);
        return $refs;
    }

    function get_common_names($names)
    {
        $arr_names = array();
        if($names) 
        {
            foreach($names as $name) $arr_names[] = array("name" => $name, "language" => 'en');
        }
        return $arr_names;
    }

    function clean_str($str)
    {    
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB"), " ", trim($str));
        $str = str_ireplace(array("    ", "   ", "  "), " ", trim($str));
        return $str;
    }

}
?>