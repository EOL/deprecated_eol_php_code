<?php
namespace php_active_record;
/* connector: [211] http://eol.org/content_partners/10/resources/211

A certain list of manual steps should be followed for IUCN resources to be re-harvested.

First, download the csv export file (in this format export-?????.csv.zip) from IUCN using this:
URL:      http://www.iucnredlist.org/users/sign_in
Login:    eli@eol.org
Password: jijamali

Then among the available downloads in "My Downloads", 
I created this saved search: “For EOL Resources 211 and 737” - Exported on 24 August 2016

When re-harvesting, before downloading you need to click button [Refresh Exported Data], this usually takes half-day to finish. After which you'll receive an email confirmation: "IUCN Red List: Export complete".
This same export is used by both IUCN resources (IDs 211, 737).
http://eol.org/content_partners/10/resources/737
http://eol.org/content_partners/10/resources/211

Then I uploaded it to a web accessible folder which the connector uses at the moment. This case I’m using my public DropBox folder:
https://dl.dropboxusercontent.com/u/7597512/IUCN/export-?????.csv.zip

I now put these steps in the connector. So we won’t forget.

Github issue: https://github.com/EOL/eol_php_code/issues/155

*/
class IUCNRedlistAPI
{
    // http://www.assembla.com/wiki/show/sis/Red_List_API
    const API_PREFIX = "http://api.iucnredlist.org/details/";    // http://api.iucnredlist.org/details/22823/0
    const SPECIES_LIST_API = "http://api.iucnredlist.org/index/all.json";

    function __construct()
    {
        $this->export_basename = "export-74550"; //previously "export-47427"
        // $this->species_list_export = "http://localhost/cp_new/IUCN/" . $this->export_basename . ".csv.zip";
        $this->species_list_export = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/IUCN/" . $this->export_basename . ".csv.zip";
        
        /* direct download from IUCN server does not work:
        $this->species_list_export = "http://www.iucnredlist.org/search/download/59026.csv"; -- this doesn't work
        */
        
        $this->download_options = array('timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //expires in 3 months - orig value. NO sched yet in harvest frequency
        // $this->download_options['expire_seconds'] = false; //debug only
    }
    public function get_taxon_xml($resource_file = null, $type = null)
    {
        $GLOBALS['language_to_iso_code'] = Functions::language_to_iso_code();
        if($type == "IUCN using .csv export") $taxa = self::get_all_taxa_v2($resource_file);
        else                                  $taxa = self::get_all_taxa($resource_file); //seems for birdlife only
        return $taxa;
    }
    private function get_all_taxa_v2($resource_file = null) // this is using the IUCN CSV export
    {
        $names_no_entry_from_partner = self::get_names_no_entry_from_partner();
        
        $basename = $this->export_basename;
        $download_options = $this->download_options;
        $download_options['expire_seconds'] = 60*60*24*25; //orig value is 60*60*24*25
        $text_path = self::load_zip_contents($this->species_list_export, $download_options, array($basename), ".csv");
        print_r($text_path);
        
        $taxon_ids = self::csv_to_array($text_path[$basename]);
        $all_taxa = array();
        $i = 0;
        foreach($taxon_ids as $taxon_id) {
            $i++;
            if(($i % 1000) == 0) echo "\nbatch $i";
            
            //http://api.iucnredlist.org/details/164845
            // $taxon_id = "164845"; //debug only
            
            if(in_array($taxon_id, $names_no_entry_from_partner)) continue;
            
            $taxon = self::get_taxa_for_species(null, $taxon_id);
            if(!$taxon) continue;
            $taxon_xml = $taxon->__toXML();
            $taxon_xml = preg_replace("/\xE3\xBC/", "ü", $taxon_xml);
            $taxon_xml = preg_replace("/\xE3\xA9/", "é", $taxon_xml);
            $taxon_xml = preg_replace("/\xE3\x80/", "À", $taxon_xml);
            $taxon_xml = utf8_encode($taxon_xml);
            $taxon_xml = utf8_decode($taxon_xml);
            if(!Functions::is_utf8($taxon_xml)) continue;
            if($resource_file) fwrite($resource_file, $taxon_xml);
            else $all_taxa[] = $taxon;
            
            // if($i >= 50) break; //debug only
        }
        // remove temp dir
        $path = $text_path[$basename];
        $parts = pathinfo($path);
        $parts["dirname"] = str_ireplace($basename, "", $parts["dirname"]);
        recursive_rmdir($parts["dirname"]);
        echo "\n temporary directory removed: " . $parts["dirname"];
        return $all_taxa;
    }
    private function csv_to_array($csv_file)
    {
        $taxon_ids = array();
        $i = 0;
        if(!($file = Functions::file_open($csv_file, "r"))) return;
        while(!feof($file)) {
            $temp = fgetcsv($file);
            $i++;
            if($i > 1) {
                if(!$temp) continue;
                if(count($temp) != 23) continue;
                $taxon_ids[$temp[0]] = 1;
            }
        }
        fclose($file);
        return array_keys($taxon_ids);
    }
    private function load_zip_contents($zip_path, $download_options, $files, $extension)
    {
        $text_path = array();
        $temp_path = create_temp_dir();
        if($file_contents = Functions::lookup_with_cache($zip_path, $download_options)) {
            $parts = pathinfo($zip_path);
            $temp_file_path = $temp_path . "/" . $parts["basename"];
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("unzip -o $temp_file_path -d $temp_path");
            if(file_exists($temp_path . "/" . $files[0] . $extension)) {
                foreach($files as $file) {
                    $text_path[$file] = $temp_path . "/" . $file . $extension;
                }
            }
        }
        else echo "\n\n Connector terminated. Remote files are not ready.\n\n";
        return $text_path;
    }
    public static function get_all_taxa($resource_file = null)
    {
        $GLOBALS['birdlife_names'] = self::parse_birdlife_checklist();
        
        $species_list_path = DOC_ROOT . "update_resources/connectors/files/iucn_species_list.json";
        
        shell_exec("rm -f $species_list_path");
        shell_exec("curl ". self::SPECIES_LIST_API ." -o $species_list_path");
        
        $used = array();
        $all_taxa = array();
        if(file_exists($species_list_path)) {
            $species_list = json_decode(file_get_contents($species_list_path));
            $i = 0;
            // shuffle($species_list);
            foreach($species_list as $species_json) {
                if(isset($used[$species_json->species_id])) continue;
                $used[$species_json->species_id] = 1;
                
                // if($i < 6000) { $i++; continue; }
                // if($species_json->species_id != '22823') continue;  // polar bear
                // if($i >= 100) break;
                
                $i++;
                debug("$species_json->species_id ($i)\n");
                
                $taxon = self::get_taxa_for_species($species_json);
                $taxon_xml = $taxon->__toXML();
                $taxon_xml = preg_replace("/\xE3\xBC/", "ü", $taxon_xml);
                $taxon_xml = preg_replace("/\xE3\xA9/", "é", $taxon_xml);
                $taxon_xml = preg_replace("/\xE3\x80/", "À", $taxon_xml);
                if($resource_file) fwrite($resource_file, $taxon_xml);
                else $all_taxa[] = $taxon;
            }
        }
        return $all_taxa;
    }
    public function get_taxa_for_species($species_json, $species_id = NULL)
    {
        if($species_json) $species_id = $species_json->species_id;

        $download_options = $this->download_options;
        $download_options['validation_regex'] = 'x_section';
        
        $url = self::API_PREFIX . $species_id;
        debug("api call: $url");
        $details_html = Functions::lookup_with_cache($url, $download_options);

        if(!$details_html) return array();
        $details_html = utf8_encode($details_html);
        $details_html = utf8_decode($details_html);
        if(!Functions::is_utf8($details_html)) return array();
        
        // this is a hack to get the document to load as UTF8. Nothing else seemed to work when using DOMDocument
        // http://www.php.net/manual/en/domdocument.loadhtml.php#95251
        $details_html = str_replace("<!DOCTYPE html>", "<?xml encoding=\"UTF-8\">", $details_html);
        $details_html = str_replace("<meta charset=\"UTF-8\"/>", "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">", $details_html);
        $details_html = str_replace("& ", "&amp; ", $details_html);
        $details_html = preg_replace("/<([0-9])/", "&lt;\\1", $details_html);
        
        if(preg_match("/^(.*<div id='citation'>)(.*?)(<\/div>.*)$/ims", $details_html, $arr)) {
            $arr[2] = str_replace("\n", " ", $arr[2]);
            $arr[2] = str_replace("\r", " ", $arr[2]);
            $arr[2] = str_replace("\t", " ", $arr[2]);
            $arr[2] = str_replace("<span>", "", $arr[2]);
            $arr[2] = str_replace("</span>", "", $arr[2]);
            $arr[2] = str_replace("&nbsp;", " ", $arr[2]);
            $arr[2] = str_replace("</td>", "", $arr[2]);
            $details_html = $arr[1] . $arr[2] . $arr[3];
        }
        while(preg_match("/  /", $details_html)) $details_html = str_replace("  ", " ", $details_html);
        
        $dom_doc = new \DOMDocument("1.0", "UTF-8");
        $dom_doc->loadHTML($details_html);
        $dom_doc->encoding = 'UTF-8';
        $dom_doc->preserveWhiteSpace = false;
        $xpath = new \DOMXpath($dom_doc);
        
        $taxon_parameters = array();
        $taxon_parameters['identifier'] = $species_id;
        // $taxon_parameters['source'] = "http://www.iucnredlist.org/apps/redlist/details/" . $species_id; //obsolete. Replaced by one below
        
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
        
        $taxon_parameters['source'] = "http://apiv3.iucnredlist.org/api/v3/website/".$scientific_name; //e.g. http://apiv3.iucnredlist.org/api/v3/website/Panthera%20leo
        
        $element = $xpath->query("//div[@id='x_taxonomy']//div[@id='species_authority']");
        $species_authority = @$element->item(0)->nodeValue;
        
        $taxon_parameters['scientificName'] = htmlspecialchars_decode(trim($scientific_name ." ". $species_authority));
        
        $taxon_parameters['commonNames'] = array();
        $common_name_languages = $xpath->query("//ul[@id='common_names']//div[@class='lang']");
        foreach($common_name_languages as $language_list) {
            $language = $language_list->nodeValue;
            $language_names = $xpath->query("//ul[@id='common_names']/li[@class='x_lang' and div = '$language']//li[@class='name']");
            
            if(isset($GLOBALS['language_to_iso_code'][$language])) $language = $GLOBALS['language_to_iso_code'][$language];
            foreach($language_names as $language_name) {
                $common_name = @ucfirst(strtolower(trim($language_name->nodeValue)));
                $common_name = utf8_encode($common_name);
                $common_name = utf8_decode($common_name);
                if(Functions::is_utf8($common_name)) $taxon_parameters['commonNames'][] = new \SchemaCommonName(array('name' => $common_name, 'language' => $language));
            }
        }
        
        $taxon_parameters['synonyms'] = array();
        $synonyms = $xpath->query("//ul[@id='synonyms']//li[@class='synonym']");
        foreach($synonyms as $synonym_node) {
            $synonym = trim($synonym_node->nodeValue);
            $taxon_parameters['synonyms'][] = new \SchemaSynonym(array('synonym' => $synonym, 'relationship' => 'synonym'));
        }
        
        list($agents, $citation) = self::get_agents_and_citation($dom_doc, $xpath);
        if(preg_match("/^(BirdLife International [12][0-9]{3}\. <i>(.*?)<\/i>)/", $citation, $arr)) {
            $birdlife_url = self::get_birdlife_url($arr[2]);
            $reference_parameters = array();
            $reference_parameters['fullReference'] = $arr[1];
            if($birdlife_url) $reference_parameters['referenceIdentifiers'] = array(new \SchemaReferenceIdentifier(array('label' => 'url', 'value' => $birdlife_url)));
            $taxon_parameters['references'][] = new \SchemaReference($reference_parameters);
        }
        
        $taxon_parameters['dataObjects'] = array();
        
        $section = self::get_redlist_status($dom_doc, $xpath, $species_id);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_assessment_information', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Conservation', $agents, $citation, 'IUCN Red List Assessment');
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'range_description', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution', $agents, $citation, 'Range Description');
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_population', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Trends', $agents, $citation);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_habitat', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat', $agents, $citation);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_threats', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Threats', $agents, $citation);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $section = self::get_text_section($dom_doc, $xpath, $species_id, 'x_conservation_actions', 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Management', $agents, $citation);
        if($section) $taxon_parameters['dataObjects'][] = $section;
        
        $taxon = new \SchemaTaxon($taxon_parameters);
        // echo $taxon->__toXML();
        return $taxon;
    }
    public static function get_text_section($dom_doc, $xpath, $species_id, $div_id, $subject, $agents, $citation, $title = null)
    {
        $element = $xpath->query("//div[@id='$div_id']");
        // the element doesn't exist. The next line was returning the entire document for some reason even if we found no match
        if(!$element->item(0)) return null;
        $section_html = $dom_doc->saveXML($element->item(0));
        $section_title = $div_id;
        
        if(preg_match("/^<div.*?>(.*)<\/div>$/ims", $section_html, $arr)) $section_html = trim($arr[1]);
        if(preg_match("/^<h2>(.*?)<\/h2>(.*)$/ims", $section_html, $arr)) {
            $section_title = trim($arr[1]);
            $section_html = trim($arr[2]);
        }
        
        if($title) $section_title = $title;
        $section_html = preg_replace("/<div class=\"x_label\">(.*?)<\/div>/", "<br/><b>\\1</b><br/>", $section_html);
        $section_html = str_replace("\n", " ", $section_html);
        $section_html = str_replace("\t", " ", $section_html);
        while(preg_match("/  /", $section_html)) $section_html = str_replace("  ", " ", $section_html);
        $section_html = preg_replace("/^<br\/>/", "", $section_html);
        $section_html = str_replace("<b/>", "", $section_html);
        $section_html = str_replace("<strong/>", "", $section_html);
        $section_html = str_replace("<span style=\"background-color: black;\"/>", "", $section_html);
        $section_html = preg_replace("/^<p><br\/><\/p>/", "", $section_html);
        
        if($section_html) {
            $section_html = str_ireplace("background-color: yellow", "", $section_html); // will remove the weird yellow background
            $section_html = utf8_encode($section_html);
            $section_html = utf8_decode($section_html);
            if(!Functions::is_utf8($section_html)) {
                echo "\n\n[-not utf8-]";
                echo "\n\n[$section_html]\n\n";
                return null;
            }
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
            $object_parameters['subjects'] = array(new \SchemaSubject(array('label' => $subject)));
            $object_parameters['agents'] = $agents;
            $object_parameters['bibliographicCitation'] = $citation;
            
            // echo "$div_id :: $title :: ".htmlspecialchars_decode($section_html)."\n\n";
            
            return new \SchemaDataObject($object_parameters);
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
        $object_parameters['title'] = "IUCNConservationStatus";
        $object_parameters['license'] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $object_parameters['rights'] = "© International Union for Conservation of Nature and Natural Resources";
        $object_parameters['rightsHolder'] = "International Union for Conservation of Nature and Natural Resources";
        $object_parameters['source'] = "http://www.iucnredlist.org/apps/redlist/details/" . $species_id;
        $object_parameters['subjects'] = array(new \SchemaSubject(array('label' => 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus')));
        return new \SchemaDataObject($object_parameters);
    }
    public static function get_agents_and_citation($dom_doc, $xpath)
    {
        $element = $xpath->query("//div[@id='x_citation']//div[@id='citation']");
        $citation = $dom_doc->saveXML($element->item(0));
        if(preg_match("/^<div id=\"citation\">(.*?)<\/div>$/ims", trim($citation), $arr)) $citation = htmlspecialchars_decode(trim($arr[1]));
        if(preg_match("/^(.*)\. Downloaded on .*/ims", trim($citation), $arr)) $citation = $arr[1];
        
        $agents = array();
        if(preg_match("/^(.*?) [0-9]{4}\. +<i>/", $citation, $arr)) {
            $all_authors = $arr[1];
            $agents[] = new \SchemaAgent(array('fullName' => $all_authors, 'role' => 'author'));
        }
        
        $element = $xpath->query("//div[@id='assessors']");
        $assessors = trim($element->item(0)->nodeValue);
        $agents[] = new \SchemaAgent(array('fullName' => $assessors, 'role' => 'compiler'));
        
        return array($agents, $citation);
    }
    public function get_names_no_entry_from_partner()
    {
        $names = array();
        $dump_file = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/IUCN/names_no_entry_from_partner.txt";
        $local = Functions::save_remote_file_to_local($dump_file, array("cache" => 1));
        if(file_exists($local)) {
            foreach(new FileIterator($local) as $line_number => $line) {
                if($line) $names[$line] = "";
            }
        }
        unlink($local);
        return array_keys($names);
    }
    public static function parse_birdlife_checklist()
    {
        $birdlife_checklist_path = DOC_ROOT . "update_resources/connectors/files/BirdLife_Checklist_Version_4.txt";
        $birdlife_names = array();
        $birdlife_synonyms = array();
        if(file_exists($birdlife_checklist_path)) {
            foreach(new FileIterator($birdlife_checklist_path) as $line_number => $line) {
                // echo "$line_number - $line\n";
                if($line_number < 2) continue;
                $columns = explode("\t", $line);
                $scientific_name = @trim($columns[3]);
                $synonyms = @trim($columns[21]);
                $id = @trim($columns[24]);
                if(!$id) continue;
                $birdlife_names[strtolower($scientific_name)] = $id;

                if($synonyms) {
                    $synonyms = explode(";", $synonyms);
                    foreach($synonyms as $synonym) {
                        $synonym = preg_replace("/\(.*?\)/", "", trim($synonym));
                        if(!$synonym) continue;
                        echo "$synonym : $id\n";
                        $birdlife_synonyms[strtolower(trim($synonym))] = $id;
                    }
                }
            }
        }
        ksort($birdlife_names);
        ksort($birdlife_synonyms);
        return array($birdlife_names, $birdlife_synonyms);
    }
    public function get_birdlife_url($taxon_name)
    {
        $taxon_name = strtolower($taxon_name);
        if(isset($GLOBALS['birdlife_names'][0][$taxon_name])) {
            return "http://www.birdlife.org/datazone/speciesfactsheet.php?id=" . $GLOBALS['birdlife_names'][0][$taxon_name];
        }
        elseif(isset($GLOBALS['birdlife_names'][1][$taxon_name])) {
            return "http://www.birdlife.org/datazone/speciesfactsheet.php?id=" . $GLOBALS['birdlife_names'][1][$taxon_name];
        }
    }
}

?>
