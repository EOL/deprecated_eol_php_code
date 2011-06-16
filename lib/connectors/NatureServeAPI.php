<?php
namespace php_active_record;

class NatureServeAPI
{
    // https://services.natureserve.org/idd/rest/ns/v1.1/globalSpecies/comprehensive?NSAccessKeyId=72ddf45a-c751-44c7-9bca-8db3b4513347&uid=ELEMENT_GLOBAL.2.104386
    const API_PREFIX = "https://services.natureserve.org/idd/rest/ns/v1.1/globalSpecies/comprehensive?NSAccessKeyId=72ddf45a-c751-44c7-9bca-8db3b4513347&uid=";
    const SPECIES_LIST_URL = "https://tranxfer.natureserve.org/download/longterm/EOL/gname_uid_crosswalk.xml ";
    
    public static function get_all_taxa($resource_file = null)
    {
        $species_list_path = DOC_ROOT . "update_resources/connectors/files/natureserve_species_list.xml";
        
        // shell_exec("rm -f $species_list_path");
        // shell_exec("curl ". self::SPECIES_LIST_URL ." -o $species_list_path");
        
        $reader = new \XMLReader();
        $reader->open($species_list_path);
        echo memory_get_usage()."\n";
        $records = array();
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "DATA_RECORD")
            {
                $record = simplexml_load_string($reader->readOuterXML(), null, LIBXML_NOCDATA);
                $records[] = (string) $record->EGT_UID;
                // if(str_word_count(self::canonical_form($record->GNAME)) > 2)
                // {
                //     echo preg_replace("/^(.*? .*?) (.*)$/", "$1", self::canonical_form($record->GNAME))."\n";
                // }
            }
        }
        
        echo memory_get_usage()."\n";
        echo "Records: ". count($records) ."\n";
        
        $chunk_size = 5;
        shuffle($records);
        $chunks = array_chunk($records, $chunk_size);
        $i = 0;
        $start_time = time_elapsed();
        foreach($chunks as $chunk)
        {
            self::lookup_multiple_ids($chunk);
            $i += $chunk_size;
            $estimated_total_time = (((time_elapsed() - $start_time) / $i) * count($records));
            echo "Time ($i) ". time_elapsed() ." : $estimated_total_time : ". ($estimated_total_time / (60 * 60)) ."\n";
        }
    }
    
    public static function lookup_multiple_ids($ids)
    {
        $url = self::API_PREFIX . implode(",", $ids);
        echo "$url\n\n";
        $details_xml = Functions::get_remote_file(self::API_PREFIX . implode(",", $ids));
        $xml = simplexml_load_string($details_xml);
        foreach($xml->globalSpecies as $species_record)
        {
            $uid = (string) $species_record['uid'];
            self::process_species_xml($species_record);
        }
    }
    
    public static function process_species_xml($details_xml)
    {
        $source_url = (string) @$details_xml->natureServeExplorerURI;
        
        $kingdom = (string) @$details_xml->classification->taxonomy->formalTaxonomy->kingdom;
        $phylum = (string) @$details_xml->classification->taxonomy->formalTaxonomy->phylum;
        $class = (string) @$details_xml->classification->taxonomy->formalTaxonomy->class;
        $order = (string) @$details_xml->classification->taxonomy->formalTaxonomy->order;
        $family = (string) @$details_xml->classification->taxonomy->formalTaxonomy->family;
        $genus = (string) @$details_xml->classification->taxonomy->formalTaxonomy->genus;
        
        $scientific_name = (string) @$details_xml->classification->names->scientificName->unformattedName;
        $author = (string) @$details_xml->classification->names->scientificName->nomenclaturalAuthor;
        $common_name = (string) @$details_xml->classification->names->natureServePrimaryGlobalCommonName;
        
        $canonical_form = self::canonical_form($scientific_name);
        $species = "";
        if(preg_match("/(.*? .*?) (.*)/", $canonical_form, $arr))
        {
            $species = $arr[1];
        }
        
        
        
        $references = array();
        if($r = (string) @$details_xml->classification->names->scientificName->conceptReference->formattedFullCitation)
        {
            $references[trim($r)] = 1;
        }
        if(isset($details_xml->references))
        {
            foreach($details_xml->references->citation as $reference)
            {
                $references[trim((string) $reference)] = 1;
            }
        }
        
        // this is some kind of placeholder reference and likely is not to be displayed
        unset($references["NatureServe. Unpublished. Concept reference for taxa where a reference cannot be recorded due to insufficient BCD data for conversion; to be used as a placeholder until the correct citation is identified."]);
        unset($references["NatureServe. Unpublished. Concept reference for taxa for which no reference which describes the circumscription has been recorded; to be used as a placeholder until such a citation is identified."]);
        unset($references["NatureServe. Unpublished. Concept reference for taxa which have not yet been described; to be used as a placeholder until a citation is available which describes the circumscription of the taxon."]);
        
        
        $full_scientific_name = trim($scientific_name . " " . $author);
        
        
        echo "K : $kingdom\n";
        echo "P : $phylum\n";
        echo "C : $class\n";
        echo "O : $order\n";
        echo "F : $family\n";
        echo "G : $genus\n";
        echo "Sp: $species\n";
        echo "SN: $full_scientific_name\n";
        echo "Co: $common_name\n";
        echo "CF: $canonical_form\n";
        print_r($references);
        
        echo "\n";
    }
    
    public static function canonical_form($string)
    {
        $canonical_form = $string;
        $canonical_form = preg_replace("/^x (.*)$/", "$1", $canonical_form);
        $canonical_form = preg_replace("/ pop\. .+$/", "", $canonical_form);
        $canonical_form = preg_replace("/ ssp\. [0-9]+$/", "", $canonical_form);
        $canonical_form = preg_replace("/ sp\..*$/", "", $canonical_form);
        $canonical_form = preg_replace("/ nr\..*$/", "", $canonical_form);
        $canonical_form = preg_replace("/ cf\..*$/", "", $canonical_form);
        $canonical_form = preg_replace("/ var\. [0-9]+$/", "", $canonical_form);
        $canonical_form = preg_replace("/ [0-9]+$/", "", $canonical_form);
        $canonical_form = preg_replace("/ \([A-Z][a-z]+\)/", "", $canonical_form);
        $canonical_form = preg_replace("/ hybrid$/", "", $canonical_form);
        $canonical_form = preg_replace("/ n\.$/", "", $canonical_form);
        $canonical_form = preg_replace("/ new genus$/", "", $canonical_form);
        $canonical_form = preg_replace("/ Genus 1 species$/", "", $canonical_form);
        $canonical_form = str_replace(" var. ", " ", $canonical_form);
        $canonical_form = str_replace(" aff. ", " ", $canonical_form);
        $canonical_form = str_replace(" ssp. ", " ", $canonical_form);
        $canonical_form = str_replace(" x ", " ", $canonical_form);
        return $canonical_form;
    }
}

?>