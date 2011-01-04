<?php

class IUCNRedlistAPI
{
    // http://www.assembla.com/wiki/show/sis/Red_List_API
    const API_PREFIX = "http://api.iucnredlist.org/details/";    // http://api.iucnredlist.org/details/22823/0
    const SPECIES_LIST_API = "http://api.iucnredlist.org/index/all.json";
    
    public static function get_taxon_xml($resource_file = null)
    {
        $GLOBALS['language_to_iso_code'] = Functions::language_to_iso_code();
        $taxa = self::get_all_taxa($resource_file);
        return $taxa;
    }
    
    public static function get_all_taxa($resource_file = null)
    {
        $species_list_path = DOC_ROOT . "update_resources/connectors/files/iucn_species_list.json";
        
        // shell_exec("rm -f ". SPECIES_LIST_PATH);
        // shell_exec("curl ". SPECIES_LIST_API ." -o ". SPECIES_LIST_PATH);
        
        $used = array();
        $all_taxa = array();
        if(file_exists($species_list_path))
        {
            $species_list = json_decode(file_get_contents($species_list_path));
            $i = 0;
            // shuffle($species_list);
            foreach($species_list as $species_json)
            {
                if(isset($used[$species_json->species_id])) continue;
                $used[$species_json->species_id] = 1;
                
                if($i < 1000) { $i++; continue; }
                // if($species_json->species_id != '183586') continue;
                if($i >= 1500) break;
                $taxon = self::get_taxa_for_species($species_json);
                
                $taxon_xml = $taxon->__toXML();
                $taxon_xml = preg_replace("/\xE3\xBC/", "ü", $taxon_xml);
                $taxon_xml = preg_replace("/\xE3\xA9/", "é", $taxon_xml);
                $taxon_xml = preg_replace("/\xE3\x80/", "À", $taxon_xml);
                if($resource_file) fwrite($resource_file, $taxon_xml);
                else $all_taxa[] = $taxon;
                
                $i++;
            }
        }
        return $all_taxa;
    }
    
    public static function get_taxa_for_species($species_json)
    {
        $species_id = $species_json->species_id;
        echo "$species_id\n";
        $details_html = Functions::get_remote_file(self::API_PREFIX.$species_id);
        
        // this is a hack to get the document to load as UTF8. Nothing else seemed to work when using DOMDocument
        // http://www.php.net/manual/en/domdocument.loadhtml.php#95251
        $details_html = str_replace("<!DOCTYPE html>", "<?xml encoding=\"UTF-8\">", $details_html);
        // $FILE = fopen(DOC_ROOT . "tmp/test.html", "w+");
        // fwrite($FILE, $details_html);
        // fclose($FILE);
        
        $details_html = str_replace("Downloaded on <b>", "Downloaded on ", $details_html);
        $details_html = str_replace("& ", "&amp; ", $details_html);
        
        $dom_doc = new DOMDocument("1.0", "UTF-8");
        $dom_doc->loadHTML($details_html);
        $dom_doc->preserveWhiteSpace = false;
        $xpath = new DOMXpath($dom_doc);
        
        $taxon_parameters = array();
        $taxon_parameters['identifier'] = $species_id;
        $taxon_parameters['source'] = "http://www.iucnredlist.org/apps/redlist/details/" . $species_id;
        
        $element = $xpath->query("//div[@id='x_taxonomy']//div[@id='kingdom']");
        $taxon_parameters['kingdom'] = @ucfirst(strtolower(trim($element->item(0)->nodeValue)));
        
        $element = $xpath->query("//div[@id='x_taxonomy']//div[@id='phylum']");
        $taxon_parameters['phylum'] = @ucfirst(strtolower(trim($element->item(0)->nodeValue)));
        
        $element = $xpath->query("//div[@id='x_taxonomy']//div[@id='class']");
        $taxon_parameters['class'] = @ucfirst(strtolower(trim($element->item(0)->nodeValue)));
        
        $element = $xpath->query("//div[@id='x_taxonomy']//div[@id='order']");
        $taxon_parameters['order'] = @ucfirst(strtolower(trim($element->item(0)->nodeValue)));
        
        $element = $xpath->query("//div[@id='x_taxonomy']//div[@id='family']");
        $taxon_parameters['family'] = @ucfirst(strtolower(trim($element->item(0)->nodeValue)));
        
        $element = $xpath->query("//h1[@id='scientific_name']");
        $scientific_name = @$element->item(0)->nodeValue;
        
        $element = $xpath->query("//div[@id='x_taxonomy']//div[@id='species_authority']");
        $species_authority = @$element->item(0)->nodeValue;
        
        $taxon_parameters['scientificName'] = htmlspecialchars_decode(trim($scientific_name ." ". $species_authority));
        
        $taxon_parameters['commonNames'] = array();
        $common_name_languages = $xpath->query("//ul[@id='common_names']//div[@class='lang']");
        foreach($common_name_languages as $language_list)
        {
            $language = $language_list->nodeValue;
            $langauge_names = $xpath->query("//ul[@id='common_names']/li[@class='x_lang' and div = '$language']//li[@class='name']");
            
            if(isset($GLOBALS['language_to_iso_code'][$language])) $language = $GLOBALS['language_to_iso_code'][$language];
            foreach($langauge_names as $langauge_name)
            {
                $common_name = @ucfirst(strtolower(trim($langauge_name->nodeValue)));
                $taxon_parameters['commonNames'][] = new SchemaCommonName(array('name' => $common_name, 'language' => $language));
            }
        }
        
        $taxon_parameters['synonyms'] = array();
        $synonyms = $xpath->query("//ul[@id='synonyms']//li[@class='synonym']");
        foreach($synonyms as $synonym_node)
        {
            $synonym = trim($synonym_node->nodeValue);
            $taxon_parameters['synonyms'][] = new SchemaSynonym(array('synonym' => $synonym, 'relationship' => 'synonym'));
        }
        
        
        
        
        list($agents, $citation) = self::get_agents_and_citation($dom_doc, $xpath);
        
        $taxon_parameters['dataObjects'] = array();
        
        $section = self::get_redlist_status($dom_doc, $xpath, $species_id);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_assessment_information', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Conservation', $agents, $citation);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_range', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution', $agents, $citation);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_population', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Trends', $agents, $citation);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_habitat', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat', $agents, $citation);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_threats', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Threats', $agents, $citation);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_conservation_actions', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Management', $agents, $citation);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $taxon = new SchemaTaxon($taxon_parameters);
        // echo $taxon->__toXML();
        return $taxon;
    }
    
    public static function get_text_section($dom_doc, $xpath, $species_id, $div_id, $subject, $agents, $citation)
    {
        $element = $xpath->query("//div[@id='$div_id']");
        $section_html = $dom_doc->saveXML($element->item(0));
        $section_title = $div_id;
        
        if(preg_match("/^<div.*?>(.*)<\/div>$/ims", $section_html, $arr)) $section_html = trim($arr[1]);
        if(preg_match("/^<h2>(.*?)<\/h2>(.*)$/ims", $section_html, $arr))
        {
            $section_title = trim($arr[1]);
            $section_html = trim($arr[2]);
        }
        
        $section_html = preg_replace("/<div class=\"x_label\">(.*?)<\/div>/", "<b>\\1</b><br/>", $section_html);
        $section_html = str_replace("\n", " ", $section_html);
        $section_html = str_replace("\t", " ", $section_html);
        while(preg_match("/  /", $section_html)) $section_html = str_replace("  ", " ", $section_html);
        
        if($section_html)
        {
            $identifier = $species_id ."/". $div_id;
            $object_parameters = array();
            $object_parameters['identifier'] = $identifier;
            $object_parameters['title'] = $section_title;
            $object_parameters['description'] = htmlspecialchars_decode($section_html);
            $object_parameters['dataType'] = "http://purl.org/dc/dcmitype/Text";
            $object_parameters['mimeType'] = "text/html";
            $object_parameters['language'] = "en";
            $object_parameters['license'] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $object_parameters['rights'] = "© International Union for Conservation of Nature and Natural Resources";
            $object_parameters['source'] = "http://www.iucnredlist.org/apps/redlist/details/" . $species_id;
            $object_parameters['subjects'] = array(new SchemaSubject(array('label' => $subject)));
            $object_parameters['agents'] = $agents;
            $object_parameters['bibliographicCitation'] = $citation;
            
            return new SchemaDataObject($object_parameters);
        }
        return null;
    }
    
    public static function get_redlist_status($dom_doc, $xpath, $species_id)
    {
        $element = $xpath->query("//div[@id='x_category_and_criteria']//div[@id='red_list_category_title']");
        $redlist_category = trim($element->item(0)->nodeValue);
        
        $element = $xpath->query("//div[@id='x_category_and_criteria']//div[@id='red_list_category_code']");
        $redlist_category_code = trim($element->item(0)->nodeValue);
        
        $section_text = $redlist_category;
        if($redlist_category_code) $section_text .= " ($redlist_category_code)";
        
        $identifier = $species_id ."/red_list_category";
        $object_parameters = array();
        $object_parameters['identifier'] = $identifier;
        $object_parameters['description'] = $section_text;
        $object_parameters['dataType'] = "http://purl.org/dc/dcmitype/Text";
        $object_parameters['mimeType'] = "text/html";
        $object_parameters['language'] = "en";
        $object_parameters['license'] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $object_parameters['rights'] = "© International Union for Conservation of Nature and Natural Resources";
        $object_parameters['rightsHolder'] = "International Union for Conservation of Nature and Natural Resources";
        $object_parameters['source'] = "http://www.iucnredlist.org/apps/redlist/details/" . $species_id;
        $object_parameters['subjects'] = array(new SchemaSubject(array('label' => 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus')));
        
        return new SchemaDataObject($object_parameters);
    }
    
    public static function get_agents_and_citation($dom_doc, $xpath)
    {
        $element = $xpath->query("//div[@id='x_citation']//div[@id='citation']");
        $citation = $dom_doc->saveXML($element->item(0));
        if(preg_match("/^<div id=\"citation\">(.*?)<\/div>$/", $citation, $arr)) $citation = htmlspecialchars_decode(trim($arr[1]));
        
        $agents = array();
        if(preg_match("/^(.*?) [0-9]{4}\. +<i>/", $citation, $arr))
        {
            $all_authors = $arr[1];
            if(preg_match("/(^|, )[".UPPER."][".UPPER.LOWER."'-]+, [".UPPER."]\./", $all_authors))
            {
                $all_authors = str_replace(" and ", " ", $all_authors);
                $all_authors = str_replace("&amp;", ", ", $all_authors);
                $all_authors = str_replace("&", ", ", $all_authors);
                $all_authors = str_replace(" ,", ",", $all_authors);
                $all_authors = str_replace("  ", " ", $all_authors);
                $authors = preg_split("/(.*?,.*?,)/", $all_authors, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                foreach($authors as $author)
                {
                    $author = trim($author);
                    if(substr($author, -1) == ",") $author = substr($author, 0, -1);
                    $agents[] = new SchemaAgent(array('fullName' => $author, 'role' => 'author'));
                }
            }elseif(preg_match("/(^|, )[".UPPER.LOWER."'-]+( [".UPPER.LOWER."'-]+){1,3},/", $all_authors))
            {
                $all_authors = str_replace(" and ", " ", $all_authors);
                $all_authors = str_replace("&amp;", ", ", $all_authors);
                $all_authors = str_replace("&", ", ", $all_authors);
                $all_authors = str_replace(" ,", ",", $all_authors);
                $all_authors = str_replace("  ", " ", $all_authors);
                $authors = explode(",", $all_authors);
                foreach($authors as $author)
                {
                    $author = trim($author);
                    $agents[] = new SchemaAgent(array('fullName' => $author, 'role' => 'author'));
                }
            }else $agents[] = new SchemaAgent(array('fullName' => $all_authors, 'role' => 'author'));
        }
        
        // echo "$citation\n";
        // print_r($agents);
        
        return array($agents, $citation);
    }
}

?>