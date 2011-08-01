<?php
namespace php_active_record;

class NameStat
{
    const API_PAGES = "http://www.eol.org/api/pages/";
    const API_PAGES_PARAMS = "?images=75&text=75&subjects=all";
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
    }
    
    function show_table($taxa)
    {
        print "<table cellpadding='3' cellspacing='0' border='1' style='font-size : x-small; font-family : Arial Unicode MS;'>
            <tr align='center'>
                <td rowspan='2'>Searched</td>
                <td rowspan='2'>Name</td>
                <td rowspan='2'>ID</td>
                <td colspan='3'># of Data Objects</td>
                <td rowspan='2'>Score</td>
                <td rowspan='2'>Last<br>curated</td>
                <td rowspan='2'>Overview - Brief Summary<br>(word count)</td>
                <td rowspan='2'>Overview - Comprehensive Description<br>(word count)</td>
            </tr>
            <tr align='center'>
                <td>Text</td>
                <td>Image</td>
                <td>Total</td>
            </tr>";
        
        $sciname = "";
        $color = "white";
        foreach($taxa as $row)
        {
            if($sciname != $row["orig_sciname"])
            {
                $sciname = $row["orig_sciname"];
                if($color == "white") $color = "aqua";
                else                  $color = "white";
            }
            print "
            <tr bgcolor='$color'>
                <td>"               . utf8_decode($row["orig_sciname"]) . "</td>
                <td>"               . utf8_decode($row["sciname"]) . "</td>
                <td align='center'><a target='_eol' href='http://www.eol.org/pages/" . $row["tc_id"] . "'>" . $row["tc_id"] . "</a></td>
                <td align='right'>"  . $row["text"] . "</td>
                <td align='right'>"  . $row["image"] . "</td>
                <td align='right'>"  . $row["total_objects"] . "</td>
                <td align='right'>"  . $row["score"] . "&nbsp;</td>
                <td align='right'>"  . $row["last_curated"] . "&nbsp;</td>
                <td align='right'>"  . $row["overview_word_count"] . "&nbsp;</td>
                <td align='right'>"  . $row["general_description_word_count"] . "&nbsp;</td>
            </tr>";
        }
        print "</table>";
    }
    
    function sort_details($taxa_details, $returns)
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
    
    function cmp($a, $b)
    {
        if(!isset($GLOBALS["sort_order"])) $GLOBALS["sort_order"] = 'total_objects';
        $sort_order = $GLOBALS["sort_order"];
        return $a[$sort_order] < $b[$sort_order];
    }
    
    function get_details($xml, $orig_sciname, $strict)
    {
        $taxa = array();
        $richness = new PageRichnessCalculator();
        foreach($xml->entry as $species)
        {
            $score = $richness->score_for_page($species->id);
            $score = @$score['total'];
            if($strict)
            {
                if(strtolower(trim($orig_sciname)) == strtolower(trim(Functions::canonical_form(trim($species->title)))))
                {
                    $taxon_do = self::get_objects_info($species->id, $species->title, $orig_sciname, $score);
                    $taxa[] = $taxon_do;
                }
            }
            else
            {
                $taxon_do = self::get_objects_info($species->id, $species->title, $orig_sciname, $score);
                $taxa[] = $taxon_do;
            }
        }
        return $taxa;
    }
    
    function get_objects_info($id, $sciname, $orig_sciname, $score)
    {
        $sciname_4color = "";
        $total_objects = 0;
        $id = str_ireplace("http://www.eol.org/pages/", "", $id);
        $file = self::API_PAGES . $id . self::API_PAGES_PARAMS;
        $text = 0;
        $image = 0;
        if($xml = Functions::get_hashed_response($file))
        {            
            if($xml->taxon->dataObject)
            {
                foreach($xml->taxon->dataObject as $object)
                {
                    if      ($object->dataType == "http://purl.org/dc/dcmitype/StillImage") $image++;
                    elseif  ($object->dataType == "http://purl.org/dc/dcmitype/Text") $text++;
                }
            }
            $total_objects = $image + $text;
        }
        if($orig_sciname != $sciname_4color) $sciname_4color = $sciname;
        
        return array($orig_sciname => 1, "orig_sciname" => $orig_sciname, "tc_id" => $id, "sciname" => $sciname, "text" => $text, "image" => $image,
                     "total_objects" => $total_objects,
                     "score" => $score,
                     "last_curated" => self::get_last_curation_date($id),
                     "overview_word_count" => self::get_word_count($id, "brief summary"),
                     "general_description_word_count" => self::get_word_count($id, "comprehensive description")
                    );
    }

    function get_last_curation_date($taxon_concept_id)
    {
        $mysqli = load_mysql_environment('slave_eol');
        $query = "SELECT last_curated_dates.last_curated FROM last_curated_dates WHERE last_curated_dates.taxon_concept_id = $taxon_concept_id ORDER BY last_curated_dates.id DESC LIMIT 1";
        $result = $mysqli->query($query);
        while($result && $row=$result->fetch_assoc()) return $row['last_curated'];
    }

    function get_word_count($taxon_concept_id, $chapter)
    {
        $concept_data_object_counts = array();
        $text_id = DataType::find_by_label('Text');
        $trusted_id     = Vetted::find("trusted");
        $untrusted_id   = Vetted::find("untrusted");
        $unreviewed_id  = Vetted::find("unknown");
        if($chapter == "brief summary")
        {
            $scope[] = InfoItem::find("http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology");
            $scope[] = InfoItem::find("http://rs.tdwg.org/ontology/voc/SPMInfoItems#Introduction");
        }
        elseif($chapter == "comprehensive description")
        {
            $scope[] = InfoItem::find("http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription");
            $scope[] = InfoItem::find("http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description");
            $scope[] = InfoItem::find("http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology");
        }
        
        $query = "SELECT do.description, do.vetted_id FROM data_objects_taxon_concepts dotc JOIN data_objects do ON dotc.data_object_id = do.id LEFT JOIN data_objects_info_items doii ON do.id = doii.data_object_id WHERE do.published=1
                  AND do.visibility_id=".Visibility::find("visible")."
                  AND do.data_type_id = $text_id
                  AND dotc.taxon_concept_id = $taxon_concept_id
                  AND doii.info_item_id in (".implode(",", $scope).")";
        $result = $this->mysqli_slave->query($query);
        while($result && $row=$result->fetch_assoc())
        {
            $description    = $row['description'];
            $vetted_id      = $row['vetted_id'];
            $words_count = str_word_count(strip_tags($description), 0);
            @$concept_data_object_counts['total_w'] += $words_count;
            if($vetted_id == $trusted_id) @$concept_data_object_counts['t_w'] += $words_count;
            elseif($vetted_id == $untrusted_id) @$concept_data_object_counts['ut_w'] += $words_count;
            elseif($vetted_id == $unreviewed_id) @$concept_data_object_counts['ur_w'] += $words_count;
        }
        return @$concept_data_object_counts['total_w'];
    }

    function sort_by_key($arr, $key_string, $key_string2)
    {
        foreach ($arr as $key => $row)
        {
            $sort_key[$key] = $row[$key_string];
            $sort_key2[$key] = $row[$key_string2];
        }
        if($arr) array_multisort($sort_key, SORT_ASC, $sort_key2, SORT_DESC, $arr);
        return $arr;
    }

}
?>