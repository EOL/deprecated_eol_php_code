<?php
namespace php_active_record;
/* connector: [171]  */
define("GOOGLE_CHART_DOMAIN", "http://chart.apis.google.com/");
define("OBIS_SPECIES_PAGE", "http://www.iobis.org/mapper/?taxon_id=");

/* from 1st version of the connector
define("OBIS_DATA_FILE"    , DOC_ROOT . "/update_resources/connectors/files/OBIS/depthenv20100825_small.csv");
define("OBIS_DATA_FILE"    , DOC_ROOT . "/update_resources/connectors/files/OBIS/spenv_small.csv");
*/

define("OBIS_DATA_FILE"    , DOC_ROOT . "/update_resources/connectors/files/OBIS/OBIS data.csv");
define("OBIS_ANCESTRY_FILE"  , DOC_ROOT . "/update_resources/connectors/files/OBIS/tnames20100825.csv");

/* to use small dataset
define("OBIS_DATA_FILE"    , DOC_ROOT . "/update_resources/connectors/files/OBIS/OBIS data_small.csv");
define("OBIS_ANCESTRY_FILE"  , DOC_ROOT . "/update_resources/connectors/files/OBIS/tnames20100825_small.csv");
*/

define("OBIS_RANK_FILE"      , DOC_ROOT . "/update_resources/connectors/files/OBIS/rank.xls");
define("OBIS_DATA_PATH"      , DOC_ROOT . "/update_resources/connectors/files/OBIS/");

class ObisAPI
{
    public static function get_all_taxa($resource_id)
    {
        //divide big file to a more consumable chunks
        $file_count = self::divide_big_csv_file(20000);

        $all_taxa = array();
        $used_collection_ids = array();

        for ($i = 1; $i <= $file_count; $i++)
        {
            $arr = self::get_obis_taxa(OBIS_DATA_PATH . $i . ".csv", $used_collection_ids, OBIS_ANCESTRY_FILE);
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];

            $xml = \SchemaDocument::get_taxon_xml($page_taxa);
            $resource_path = OBIS_DATA_PATH . "temp_obis_" . $i . ".xml";
            $OUT = fopen($resource_path, "w"); 
            fwrite($OUT, $xml); 
            fclose($OUT);
        }

        // Combine all XML files.
        self::combine_all_xmls($resource_id);
        // Set to force harvest
        if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml")) $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::force_harvest()->id . " WHERE id=" . $resource_id);
        // Delete temp files
        self::delete_temp_files(OBIS_DATA_PATH . "temp_obis_", "xml");
        self::delete_temp_files(OBIS_DATA_PATH, "csv");
    }

    public static function get_obis_taxa($url, $used_collection_ids, $ancestry)
    {
        $response = self::search_collections($url, $ancestry);
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            $used_collection_ids[$rec["sciname"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    function search_collections($url, $ancestry)//this will output the raw (but structured) output from the external service
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $arr_taxon_detail = $parser->convert_sheet_to_array($ancestry);
        //id    tname    tauthor    valid_id    rank_id    parent_id    worms_id
        $arr_taxon_detail = self::prepare_taxon_detail($arr_taxon_detail, $parser);
        $response = self::prepare_species_page($url, $arr_taxon_detail);
        return $response;
    }

    function prepare_species_page($url, $arr_taxon_detail)
    {
        $arr_taxa = array();
        $i = 0;
        $file = fopen($url, "r");
        while($file && !feof($file))
        {
            $line = fgets($file);
            $line = str_replace(", ", "| ", $line);
            $columns = explode(",", $line);
            if (sizeof($columns) != 37) continue; // some extra commas ","
            $k = 0;
            if($i == 0) $labels = array();
            $arr_csv = array();
            foreach($columns as $column)
            {
                if($i == 0) $labels[] = trim($column);
                else
                {
                    $arr_csv[@$labels[$k]] = trim($column);
                    $k++;
                }
            }
            if($i == 0) 
            {
                $i++;
                continue;
            }
            $arr_csv["tauthor"] = str_replace('"', '', $arr_csv["tauthor"]);
            $arr_csv["tauthor"] = str_replace('| ', ', ', $arr_csv["tauthor"]);

            //===================
            $agent = array();
            $agent[] = array("role" => "project", "homepage" => "http://www.iobis.org/", "fullName" => "OBIS");
            $rightsHolder = "Ocean Biogeographic Information System";
            $sciname = self::remove_parenthesis_entry($arr_csv["tname"]);

            /* sample data
            Bottom depth -1,295 - 9,438 m
            Sample depth 0 - 7,000 m
            Temperature 0.466 - 28.683 ?
            Nitrate 0.008 - 40.553 umol/l
            Salinity 17.801 - 37.338 PPS
            Oxygen 0.171 - 6.734 ml/l
            Phosphate 0.038 - 3.170 umol/l
            Silicate 0.756 - 158.684 umol/l
            */

            $header = "";
            $header .= "Depth range based ";
            if($arr_csv["ndepth"] != "") 
            {
                $header .= "on ". $arr_csv["ndepth"] . "";  //brackets
                if($arr_csv["ndepth"] > 1) $header .= " specimens ";
                else                           $header .= " specimen ";
            }
            if($arr_csv["ndepth"] != "") $header .= "in ";
            else                             $header .= "on ";
            if($arr_csv["ntaxa"] != "")  
            {
                $header .= "" . $arr_csv["ntaxa"] . ""; //brackets
                if($arr_csv["ntaxa"] > 1) $header .= " taxa.";
                else                          $header .= " taxon.";
            }
            if($arr_csv["nwoa"] != "")   
            {
                $header .= "<br>Water temperature and chemistry ranges based on " . $arr_csv["nwoa"] . "";  //brackets
                if($arr_csv["nwoa"] > 1) $header .= " samples.";
                else                         $header .= " sample.";
            }
            $environmental_info = "";
            $graphical_info = "";                        
            $environments = array("depth", "temperature", "nitrate", "salinity", "oxygen", "phosphate", "silicate");
            foreach($environments as $environment)
            {
                if($arr_csv["min" . $environment] != "" || $arr_csv["max" . $environment] != "")
                {
                    if    ($environment == "depth")       $title = "Depth range (m): ";
                    elseif($environment == "temperature") $title = "Temperature range (&deg;C): ";
                    elseif($environment == "nitrate")     $title = "Nitrate (umol/L): ";
                    elseif($environment == "salinity")    $title = "Salinity (PPS): ";
                    elseif($environment == "oxygen")      $title = "Oxygen (ml/l): ";
                    elseif($environment == "phosphate")   $title = "Phosphate (umol/l): ";
                    elseif($environment == "silicate")    $title = "Silicate (umol/l): ";       
                    
                    if($environment != "depth")
                    {
                        $arr_csv["min" . $environment] = number_format($arr_csv["min" . $environment],3);
                        $arr_csv["max" . $environment] = number_format($arr_csv["max" . $environment],3);
                    }
                                                     
                    $title .= $arr_csv["min" . $environment] . " - " . $arr_csv["max" . $environment];
                    $environmental_info .= "<br>&nbsp;&nbsp;" . $title;
                    
                    $bar_graph = self::generate_bar_graph($arr_csv["max" . $environment], $arr_csv["min" . $environment], $title, $environment);
                    if($bar_graph) $graphical_info .= "<br><br>$title $bar_graph";                    
                }
            }

            $desc = "";
            if($environmental_info != "")
            {
                $desc .= $header;
                if($environmental_info != "") $desc .= "<br><br>Environmental ranges" . $environmental_info;
                if($graphical_info != "")     $desc .= "<br><br>Graphical representation" . $graphical_info;
            }
            else
            {
                $i++;
                continue;
            }

            $desc .= "<br>&nbsp;<br>Note: this information has not been validated. Check this *<a target='obis_gallery' href='http://www.eol.org/content_partners/257'>note</a>*. Your feedback is most welcome.";
            $species_id = $arr_csv["tname_id"];

            $identifier    = $species_id . "-env_info";
            $dataType      = "http://purl.org/dc/dcmitype/Text";
            $mimeType      = "text/html";
            $title         = "Environmental Information";
            $source        = OBIS_SPECIES_PAGE . $species_id;
            $description   = $desc;
            $mediaURL      = "";
            $agent         = $agent;
            $license       = "";
            $location      = "";
            $rightsHolder  = $rightsHolder;
            $refs          = array();
            $subject       = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat";
            $arr_objects = array();
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
                         
            if(sizeof($arr_objects))
            {
                $ancestry = self::assign_ancestry(@$arr_taxon_detail[$species_id]['ancestry']);        
                $arr_taxa[] = array("identifier"   => $species_id,
                                    "source"       => $source,
                                    "kingdom"      => @$ancestry["Kingdom"],
                                    "phylum"       => @$ancestry["Phylum"],
                                    "class"        => @$ancestry["Class"],
                                    "order"        => @$ancestry["Order"],
                                    "family"       => @$ancestry["Family"],
                                    "sciname"      => $sciname,
                                    "data_objects" => $arr_objects
                                   );
            }
            $i++;
        }
        fclose($file);
        return $arr_taxa;
    }

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject)
    {
        return array("identifier"  => $identifier,
                     "dataType"    => $dataType,
                     "mimeType"    => $mimeType,
                     "source"      => $source,
                     "description" => $description,
                     "license"     => $license,
                     "subject"     => $subject
                    );
    }
    
    function remove_parenthesis_entry($sciname)
    {
        // e.g. "Bulla (Leucophysema) argoblysis" should only return "Bulla argoblysis"
        $pos1 = stripos($sciname, "(");
        if(is_numeric($pos1))
        {
            $pos2 = stripos($sciname, ")");
            return self::clean_str(trim(substr($sciname, 0, $pos1) . substr($sciname, $pos2 + 1, strlen($sciname))));
        }
        return $sciname;
    }

    function generate_line_graph($max, $min)
    {
        $range1 = self::get_max_range($max, $min);
        $range2 = 0;
        $title = "Depth range (m)";
        $comma_separated = $min . "," . $max;
        $chxt = "y,x";
        $chxl = "1:|Line graph";
        return "<img src='" . GOOGLE_CHART_DOMAIN . "chart?chs=200x150&chxt=$chxt&chxl=$chxl&chxr=0,$range1,$range2&chtt=$title&cht=lc&chd=t:$comma_separated&chds=$range1,$range2'>";
    }

    function generate_bar_graph($max, $min, $title, $environment = NULL)
    {
        $range = $max - $min;
        if($range == 0) return;
        $ranges["depth"]["min"] = $min;
        $ranges["depth"]["max"] = $max;
        $ranges["nitrate"]["min"] = 0;
        $ranges["nitrate"]["max"] = 49.846;
        $ranges["oxygen"]["min"] = 0;
        $ranges["oxygen"]["max"] = 9.323;
        $ranges["phosphate"]["min"] = 0.006;
        $ranges["phosphate"]["max"] = 3.661;
        $ranges["salinity"]["min"] = 5;
        $ranges["salinity"]["max"] = 40.817;
        $ranges["silicate"]["min"] = 0;
        $ranges["silicate"]["max"] = 230.3;
        $ranges["temperature"]["min"] = -2.072;
        $ranges["temperature"]["max"] = 29.546;
        if($environment == "depth")
        {
            $max_range = self::get_max_range($max, $min);
            $min_range = 0;
        }
        else
        {
            $max_range = $ranges[$environment]["max"];
            $min_range = $ranges[$environment]["min"];
        }
        // replace 000000 with 00000000 to not show a number
        $chm = "N,FF0000,-1,,12|N,000000,0,,12,,c|N,00000000,1,,12,,c|N,ffffff,2,,12,,c";
        if($environment == "temperature") $chf = "b1,lg,90,FF0000,0,76A4FB,1";
        else                              $chf = "";
        if($range == 1) $chm = "";
        // &chtt=$title -- add in URL to have title
        if(in_array($environment, array("nitrate","silicate","phosphate"))) $chco = "ffffff,00ff00"; // nitrate
        else                                                                $chco = "ffffff,389ced"; // depth, phosphate, oxygen
        if($environment == "depth") 
        {
            $chxt = "y";
            $chxl = "";            
        }
        else
        { 
            $chxt = "y,r"; // r is for the right axis
            $min_value = "";
            if($ranges[$environment]["min"] != 0) $min_value = ": " . $ranges[$environment]["min"];
            $chxl = "1:|global oceans min" . $min_value . "|average|global oceans max: " . $ranges[$environment]["max"];            
        }    
        return "<img src='" . GOOGLE_CHART_DOMAIN . "chart?cht=bvs&chs=350x150&chd=t:$min|$range&chxr=0,$min_range,$max_range&chds=0,$max_range&chbh=50,20,15&chxt=$chxt&chm=$chm&chma=20&chf=$chf&chco=$chco&chxl=$chxl'>";
    }

    function get_max_range($max, $min)
    {
        $digits = strlen(strval(intval($max)));
        $step = "1" . str_repeat("0", $digits-1);
        return $max + intval($step);
    }

    function assign_ancestry($taxon_ancestry)
    {
        $ancestry = array();
        if($taxon_ancestry)
        {
            $ranks = array("Kingdom", "Phylum", "Class", "Order", "Family", "Genus");
            foreach($taxon_ancestry as $r)
            {
                if(in_array($r['rank'], $ranks))
                {
                    $ancestry[$r['rank']] = $r['name'];
                }
            }
        }
        return $ancestry;
    }

    function prepare_taxon_detail($taxon, $parser)
    {
        $rank_data = self::prepare_rank_data($parser);
        $arr_taxon_detail = array();
        $i = 0;
        foreach($taxon["id"] as $id)
        {
            //id    tname    tauthor    valid_id    rank_id    parent_id    worms_id
            $rank_id = $taxon['rank_id'][$i];
            $arr_taxon_detail[$id] = array("tname" => $taxon['tname'][$i], "rank_id" => $rank_id, "rank_name" => @$rank_data[$rank_id], "parent_id" => $taxon['parent_id'][$i]);
            $i++;
        }
        $arr_ancestry = array();
        foreach($arr_taxon_detail as $id => $rec)
        {
            $ancestry = self::get_ancestry($id, $arr_taxon_detail, $rank_data);
            $arr_ancestry[$id] = $ancestry;
        }

        //final step
        $arr_taxon_detail = array();
        $i=0;
        foreach($taxon["id"] as $id)
        {
            //id    tname    tauthor    valid_id    rank_id    parent_id    worms_id
            $rank_id = $taxon['rank_id'][$i];
            $arr_taxon_detail[$id] = array("tname" => $taxon['tname'][$i], "rank_name" => @$rank_data[$rank_id], "ancestry" => $arr_ancestry[$id]);
            $i++;
        }
        return $arr_taxon_detail;
    }

    function get_ancestry($id, $arr_taxon_detail, $rank_data)
    {
        $arr = array();
        $continue = true; 
        $searched_id = $id;
        while($continue) 
        {
            if(@$arr_taxon_detail[$searched_id]['parent_id'] != $searched_id)
            {
                $temp_id        = @$arr_taxon_detail[$searched_id]['parent_id'];
                $temp_rank_id   = @$arr_taxon_detail[$temp_id]['rank_id'];
                $arr[] = array("id" => $temp_id, "name" => @$arr_taxon_detail[$temp_id]['tname'], "rank" => @$rank_data[$temp_rank_id]);
                $searched_id = @$arr_taxon_detail[$searched_id]['parent_id'];
            }
            else $continue = false;
        }
        return $arr;
    }

    function prepare_rank_data($parser)
    {
        $arr_rank = array();
        $arr = $parser->convert_sheet_to_array(OBIS_RANK_FILE);
        $i = 0;
        foreach($arr['rank_id'] as $rank_id)
        {
            $arr_rank[$rank_id] = $arr['rank_name'][$i];
            $i++;
        }    
        return $arr_rank;
    }

    function clean_str($str)
    {
        $str = str_ireplace(array("\r", "\t", "\o"), '', $str);
        $str = str_ireplace(array("  "), ' ', $str);
        return $str;
    }

    private function divide_big_csv_file($divisor)
    {
        self::delete_temp_files(OBIS_DATA_PATH, "csv");
        $i = 0;
        $line = "";
        $file_count = 0;
        $file = fopen(OBIS_DATA_FILE, "r");
        $labels = "";
        while(!feof($file))
        {
            $i++;
            $line .= fgets($file);
            if(!$labels) 
            {
               $labels = $line;
               $line = "";
               continue;
            }
            if($i == $divisor)
            {
                $i = 0;
                $file_count++;
                $OUT = fopen(OBIS_DATA_PATH . $file_count . ".csv", "w");
                fwrite($OUT, $labels);
                fwrite($OUT, $line);
                fclose($OUT);
                $line = "";
            }
        }
        // last writes
        if($line)
        {
            $file_count++;
            $OUT = fopen(OBIS_DATA_PATH . $file_count . ".csv", "w");
            fwrite($OUT, $labels);
            fwrite($OUT, $line);
            fclose($OUT);
        }
        return $file_count;
    }

    private function delete_temp_files($file_path, $file_extension)
    {
        $i = 0;
        while(true)
        {
            $i++;
            $filename = $file_path . $i . "." . $file_extension;
            if(file_exists($filename))
            {
                print "\n unlink: $filename";
                unlink($filename);
            }
            else return;
        }
    }

    private function combine_all_xmls($resource_id)
    {
        print "\n\n Start compiling all XML...\n";
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
        $OUT = fopen($old_resource_path, "w");
        $str = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $str .= "<response\n";
        $str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
        $str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
        $str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
        $str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
        $str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
        fwrite($OUT, $str);
        $i = 0;
        while(true)
        {
            $i++;
            $filename = OBIS_DATA_PATH . "temp_obis_" . $i . ".xml";
            if(!is_file($filename))
            {
                print " -end compiling XML's- ";
                break;
            }
            print " $i ";
            $READ = fopen($filename, "r");
            $contents = fread($READ, filesize($filename));
            fclose($READ);
            if(trim($contents))
            {
                $pos1 = stripos($contents, "<taxon>");
                $pos2 = stripos($contents, "</response>");
                $str  = substr($contents, $pos1, $pos2 - $pos1);
                if($pos1 && $pos2) fwrite($OUT, $str);
            }
        }
        fwrite($OUT, "</response>");
        fclose($OUT);
        print "\n All XML compiled\n\n";
    }

}
?>
