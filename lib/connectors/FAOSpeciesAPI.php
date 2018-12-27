<?php
namespace php_active_record;
/* connector: [fao_species.php] */
class FAOSpeciesAPI
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array(
            'resource_id'        => 'FAO',                              //resource_id here is just a folder name in cache
            'expire_seconds'     => false,
            'download_wait_time' => 1000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        $this->debug = array();
        $this->species_list = "http://www.fao.org/figis/ws/factsheets/domain/species/";
        $this->factsheet_page = "http://www.fao.org/fishery/species/the_id/en";
        $this->local_species_page = "http://localhost/cp_new/FAO_species_catalog/www.fao.org/fishery/species/the_id/en.html";
    }
    function start()
    {
        $ids = self::get_ids(); echo "\n".count($ids)."\n";
        foreach($ids as $id) {
            $rec = self::assemble_record($id);
            self::create_archive($rec);
            break;
            // exit("\n-stopx-\n");
        }
        $this->archive_builder->finalize(true);
        // if($val = @$this->debug['Country Local Names'])       print_r($val);
        // if($val = @$this->debug['Geographical Distribution']) print_r($val);
        if($val = @$this->debug['No biblio']) print_r($val);
        if($val = @$this->debug['No refs']) print_r($val);
    }
    private function create_archive($rec)
    {
        $rec['taxon_id'] = $rec['FAO Names']['taxonomic_code'];
        print_r($rec);
        self::create_taxon($rec);
        if($val = @$rec['Diagnostic Features'])   self::create_text_object($val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription", $rec);
        if($val = @$rec['Habitat and Biology'])   self::create_text_object($val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology", $rec);
        if($val = @$rec['Size'])                  self::create_text_object($val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size", $rec);
        if($val = @$rec['Interest to Fisheries']) self::create_text_object($val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Use", $rec);
    }
    private function create_text_object($txt, $subject, $rec)
    {
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $rec['taxon_id'];
            $tmp = pathinfo($subject, PATHINFO_FILENAME);
            $tmp = explode("#", $tmp);
        $mr->identifier     = $rec['taxon_id']."_".$tmp[1];
        $mr->type           = 'http://purl.org/dc/dcmitype/Text';
        $mr->language       = 'en';
        $mr->format         = 'text/html';
        $mr->furtherInformationURL = $rec['furtherInformationURL'];
        $mr->CVterm         = $subject;
        // $mr->Owner          = '';
        // $mr->rights         = $o['dc_rights'];
        // $mr->title          = $o['dc_title'];
        $mr->UsageTerms     = 'https://creativecommons.org/licenses/by-nc-sa/3.0/';
        // $mr->audience       = 'Everyone';
        $mr->description    = $txt;
        // $mr->LocationCreated = $o['location'];
        $mr->bibliographicCitation = $rec['biblio'];
        // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
        // if($agent_ids     =     @$this->object_agent_ids[$o['int_do_id']])  $mr->agentID = implode("; ", $agent_ids);
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    private function create_taxon($rec)
    {   /*Array(
            [sciname] => Boops boops (Linnaeus, 1758) 
            [furtherInformationURL] => http://www.fao.org/fishery/species/2385/en
            [FAO Names] => Array(
                    [taxonomic_code] => 1703926101
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['FAO Names']['taxonomic_code'];
        $taxon->scientificName          = $rec['sciname'];
        $taxon->furtherInformationURL   = $rec['furtherInformationURL'];
        // if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function assemble_record($id)
    {
        $id = 2996;
        $url = str_replace("the_id", $id, $this->local_species_page);
        echo "\n$url\n";
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = str_replace(array("\n"), "<p>", $html);
            $html = str_replace(array("\t"), "<br>", $html);
            // echo $html; exit;
            $html = Functions::remove_whitespace($html);
            $rec = self::get_sciname($html, $id);
            $rec['biblio'] = self::get_source_of_information($html, $id);
            $rec['references'] = self::get_references($html, $id);
            $sections = array("FAO Names", "Diagnostic Features", "Geographical Distribution", "Habitat and Biology", "Size", "Interest to Fisheries", "Local Names", "Source of Information");
            foreach($sections as $section) {
                if(preg_match("/>$section<(.*?)bgcolor=\"#6699ff\" align=\"left\"/ims", $html, $arr)) {
                    $str = "<".str_replace(array("<br>", "&nbsp;"), " ", $arr[1]);
                    $str = Functions::remove_whitespace($str);
                    $str = strip_tags($str, "<p><i>");
                    // if($section == "Local Names") {
                        echo "\n[$section][$id]---------------------\n$str\n---------------------\n";
                    // }
                    if($section == "FAO Names") $rec[$section] = self::parse_FAO_Names($str, $id);
                    elseif($section == "Local Names") $rec[$section] = self::parse_Local_Names($str);
                    elseif($section == "Geographical Distribution") $rec[$section] = self::parse_Geographical_Distribution($str, $id);
                    else {
                        $rec[$section] = self::other_str_format($str);
                    }
                }
            }
        }
        // print_r($rec);
        return $rec;
    }
    private function get_source_of_information($html, $id)
    {   /*bgcolor="#6699ff" align="left">Source of Information</td><td bgcolor="#6699ff" align="right" width="25%"></td></tr><tr><td colspan="2"><a href="http://www.fao.org/fi/eims_search/advanced_s_result.asp?JOB_NO=x9293" target="_blank">Sharks of the world </a> An annotated and illustrated catalogue of shark species known to date. Volume 2 Bullhead, mackerel and carpet sharks (Heterodontiformes, Lamniformes and Orectolobiformes). Leonard J.V. Compagno&nbsp;2001.&nbsp;
         FAO Species Catalogue for Fishery Purposes. No. 1, Vol. 2. Rome, FAO. 2001. p.269.</td></tr></tbody></table>
        */
        if(preg_match("/>Source of Information<\/td>(.*?)<\/table>/ims", $html, $arr)) return strip_tags($arr[1], "<a>");
        else $this->debug['No biblio'][$id] = '';
    }
    private function get_references($html, $id)
    {   /*>Bibliography</div><div>
        <div class="sourceEntryTitle">Compagno, 1984</div>
        <div class="sourceEntryTitle">Fowler, 1941</div>
        <div class="sourceEntryTitle">Goto &amp; Nakaya, 1996</div>
        <div class="sourceEntryTitle">Kharin, 1987</div>
        <div class="sourceEntryTitle">Smith, 1913</div>
        <div class="sourceEntryTitle">Teng, 1959b</div>
        </div></div></td></tr></tbody></table>
        */
        if(preg_match("/>Bibliography<\/div>(.*?)<\/table>/ims", $html, $arr)) {
            if(preg_match_all("/<div class=\"sourceEntryTitle\">(.*?)<\/div>/ims", $arr[1], $arr2)) {
                $final = array_map('trim', $arr2[1]);
                return $final;
            }
        }
        else $this->debug['No refs'][$id] = '';
    }
    private function get_sciname($html, $id)
    {
        if(preg_match("/<td id=\"head_title_instance\" style=\";font-style:italic\">(.*?)<\/td>/ims", $html."xxx", $arr)) $rec['sciname'] = strip_tags($arr[1]);
        else exit("\nNo sciname [$id]\n");
        $rec['furtherInformationURL'] = str_replace("the_id", $id, $this->factsheet_page);
        return $rec;
    }
    private function other_str_format($str)
    {
        $str = trim($str);
        if(substr($str,0,3) == "<p>") $str = trim(substr($str,3,strlen($str)));
        if(substr($str, -3) == "<p>") $str = substr($str,0,strlen($str)-3);
        return $str;
    }
    private function parse_Geographical_Distribution($str, $id)
    {
        // echo "\n[$str]\n";
        $str = str_replace(". </i>","</i> .", $str);
        $str = str_replace("e. g.", "e.g.", $str);
        
        $arr = explode("<p>", $str);
        // print_r($arr);
        if($str = @$arr[2]) {}
        else {
            return;
            exit("\nInvestigate id [$id]. No geographical dist.\n");
        }
        
        $letters = array("N.W","e.g","i.e","fig19","St","fig","D","S","P","L","A","E","h","p","N","R","M","O","T","I","C");
        foreach($letters as $letter) $str = str_replace($letter.". ", $letter."xxx ", $str);
        // $str = str_replace("S. ", "Sxxx ", $str);
        // $str = str_replace("P. ", "Pxxx ", $str);
        // $str = str_replace("L. ", "Lxxx ", $str);
        
        $arr = explode(". ", $str." ");
        $arr = array_map('trim', $arr);
        $arr = array_filter($arr);
        // print_r($arr);
        foreach($arr as $distrib) {
            foreach($letters as $letter) $distrib = str_replace($letter."xxx ", $letter.". ", $distrib);
            $this->debug['Geographical Distribution'][$distrib] = '';
        }
        return "";
    }
    private function parse_Local_Names($str)
    {
        //manual adjustments
        $str = str_replace("(see Bini, 1970:56)", "(see Bini, 1970[colon]56)", $str);
        
        $comnames = array();
        /* 
        Japan : <p> <p> Higezame . 
        Mexico : <p> <p> Sand shark , <p> Gata .West Indies : <p> <p> Sand shark , <p> Gata .Brazil : <p> <p> Gata atlantica , <p> Cacao lixa .
        */
        $str = strip_tags($str);
        $str = Functions::remove_whitespace($str);
        
        $letters = array("incl","are","etc","U.S.A","S");
        foreach($letters as $letter) $str = str_replace($letter.". ", $letter."xxx ", $str);
        
        $arr = explode('. ', $str." ");
        $arr = array_map('trim', $arr);
        $arr = array_filter($arr);
        // print_r($arr);
        foreach($arr as $val) {
            foreach($letters as $letter) $val = str_replace($letter."xxx ", $letter.". ", $val);
            
            $arr2 = explode(":", $val);
            $arr2 = array_map('trim', $arr2);
            
            $lang_ctry = trim($arr2[0]);
            $lang_ctry = str_replace("(see Bini, 1970[colon]56)", "(see Bini, 1970:56)", $lang_ctry);
            
            
            $arr3 = explode(",", $arr2[1]);
            $arr3 = array_map('trim', $arr3);
            // print_r($arr3);
            foreach($arr3 as $comname) {
                $this->debug['Country Local Names'][$lang_ctry] = '';
                $comnames[] = array("lang" => $lang_ctry, "comname" => $comname);
            }
        }
        // exit("\n-end Local Names-\n");
        return $comnames;
    }
    private function parse_FAO_Names($str, $id)
    {
        $final = array();
        // $str = str_replace("&nbsp;", " ", $str);
        // $str = Functions::remove_whitespace($str);
        // echo "\n[$str]\n";
        if(preg_match("/Taxonomic Code:(.*?)xxx/ims", $str."xxx", $arr)) $final['taxonomic_code'] = trim($arr[1]);
        else exit("\nNo taxonomic_code [$id]\n");
        //get comnames
        $tmp = explode("3Alpha", $str);
        $str = $tmp[0];
        $str = trim(strip_tags($str));
        $str = str_replace(".", "", $str);
        echo "\n[$str]\n";
        $arr = explode(",", $str);
        $arr = array_map("trim", $arr);
        // print_r($arr);
        $comnames = array();
        foreach($arr as $val) {
            $tmp = explode(" - ", $val);
            $comnames[] = array("lang" => strtolower($tmp[0]), "comname" => $tmp[1]);
        }
        $final['comnames'] = $comnames;
        // print_r($final);
        // exit("\n-end FAO-\n");
        return $final;
    }
    private function get_ids()
    {
        $xml = Functions::lookup_with_cache($this->species_list, $this->download_options);
        if(preg_match_all("/factsheet=\"(.*?)\"/ims", $xml, $arr)) {
            $ids = array_unique($arr[1]);
            return $ids;
        }
    }
    function utility_SiteSucker()
    {
        $ids = self::get_ids();
        echo "\n".count($ids)."\n";
        /* just one-time - save the output to: http://localhost/fao.html. Then this url will be inputed to SiteSucker. Saved in Desktop/FAO/FAO_Species.suck
        $html = '';
        foreach($ids as $id) $html .= '<br><a href="http://www.fao.org/fishery/species/'.$id.'/en">'.$id.'</a>';
        echo $html; exit;
        */
        exit("\n-end-\n");
    }
}
?>
