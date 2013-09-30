<?php
namespace php_active_record;

/*
This class API is used for content partners who don't want EOL to create a new page for their names, 
but only get those names from them which already has an EOL page.
If name is linked to multiple taxon pages, it gets the one with the most no. of objects.
e.g. DicoverLife Maps and Keys, US Fish and Wildlife Service - Endangered Species Program
*/
class CheckIfNameHasAnEOLPage
{
    const API_SEARCH = "http://eol.org/api/search/";
    const API_PAGES = "http://eol.org/api/pages/";
    const API_PAGES_PARAMS = "?images=75&text=75&subjects=all";

    function check_if_name_has_EOL_page($scientific_name)
    {
        $file = self::API_SEARCH . str_ireplace(" ","%20",$scientific_name); //. "?exact=1";
        if($xml = Functions::get_hashed_response($file, array('download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 2, 'delay_in_minutes' => 2)))
        {
            foreach($xml->entry as $species)
            {
                if(trim($scientific_name) == trim(Functions::canonical_form(trim($species->title)))) return array(true, $xml);
            }
        }
        return array(false, $xml);
    }

    function get_taxon_simple_stat($scientific_name, $xml) //$xml is from API 'search' result
    {
        $details = self::get_details($xml, $scientific_name, true); //'true' meaning, the orig name should be = to the canonical form of the API result.
        $details = self::sort_details($details, 1); // 2nd param is the no. of returned records after sorting
        return @$details[0]; //return the one with the most no. of data objects
    }

    private function get_details($xml, $orig_sciname, $strict)
    {
        $taxa = array();
        foreach($xml->entry as $species)
        {
            if($strict)
            {
                if(strtolower(trim($orig_sciname)) == strtolower(trim(Functions::canonical_form(trim($species->title)))))
                {
                    $taxon_do = self::get_objects_info($species->id, $species->title, $orig_sciname);
                    $taxa[] = $taxon_do;
                }
            }
            else
            {
                $taxon_do = self::get_objects_info($species->id, $species->title, $orig_sciname);
                $taxa[] = $taxon_do;
            }
        }
        return $taxa;
    }

    private function get_objects_info($id, $scientific_name, $orig_sciname)
    {
        $total_objects = 0;
        $id = str_ireplace("http://www.eol.org/pages/", "", $id);
        $file = self::API_PAGES . $id . self::API_PAGES_PARAMS;
        $text = 0;
        $image = 0;
        if($xml = Functions::get_hashed_response($file, array('download_wait_time' => 1000000, 'timeout' => 240, 'download_attempts' => 5)))
        {
            if($xml->dataObject)
            {
                foreach($xml->dataObject as $object)
                {
                    if     ($object->dataType == "http://purl.org/dc/dcmitype/StillImage") $image++;
                    elseif ($object->dataType == "http://purl.org/dc/dcmitype/Text") $text++;
                }
            }
            $total_objects = $image + $text;
        }
        return array($orig_sciname => 1, "orig_sciname" => $orig_sciname, "tc_id" => $id, "sciname" => $scientific_name, "text" => $text, "image" => $image, "total_objects" => $total_objects);
    }

    private function sort_details($taxa_details, $returns)
    {
        usort($taxa_details, "self::cmp");
        //start limit number of returns
        $new = array();
        if($returns > 0)
        {
            for ($i = 0; $i < $returns; $i++) if(@$taxa_details[$i]) $new[] = $taxa_details[$i];
            return $new;
        }
        else return $taxa_details;
    }

    private function cmp($a, $b)
    {
        return $a["total_objects"] < $b["total_objects"];
    }

}
?>