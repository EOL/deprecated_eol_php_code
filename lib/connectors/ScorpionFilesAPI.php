<?php
namespace php_active_record;
// connector: [scorpion]
class ScorpionFilesAPI
{
    function __construct()
    {
        $this->domain = "http://www.ntnu.no/ub/scorpion-files/";
        $this->family_list = $this->domain . "higher_phylogeny.php";
        $this->download_options = array("resource_id" => "scorpion", "download_wait_time" => 1000000, "timeout" => 1800, "download_attempts" => 1, "delay_in_minutes" => 1);
        $this->download_options['expire_seconds'] = false;
    }

    function get_all_taxa()
    {
        $families = self::get_families();
        $taxa = self::get_species_list($families);
        self::save_to_text_file($taxa);
        self::get_scorpiones_text_objects();
        self::parse_species_pages();
    }

    private function get_families()
    {
        if($html = Functions::lookup_with_cache($this->family_list, $this->download_options))
        {
            $html = self::clean_html($html);
            $html = strip_tags(html_entity_decode($html), "<td><tr><a><img>"); //removes chars in between <!-- and --> except <td><tr><a><img>
            if(preg_match("/logo4.jpg(.*?)higher_phylogeny.php/ims", $html, $arr))
            {
                if(preg_match_all("/<td>(.*?)<\/td>/ims", $arr[1], $arr2))
                {
                    $families = array();
                    foreach($arr2[1] as $temp)
                    {
                        $family = strip_tags($temp);
                        $family = self::format_utf8(str_replace("- ", "", $family));
                        if(preg_match("/href=\"(.*?)\"/ims", $temp, $arr3)) $families[$family] = $this->domain . $arr3[1];
                    }
                }
            }
        }
        return $families;
    }

    private function get_species_list($families)
    {
        $taxa = array();
        foreach($families as $family => $url)
        {
            // $url = "http://www.ntnu.no/ub/scorpion-files/chactidae.php"; //debug

            echo "\n[$url]\n";
            if($html = Functions::lookup_with_cache($url, $this->download_options))
            {
                self::get_all_hrefs($html);
                $article = self::parse_text_object($html);
                $authorship = self::get_family_authorship($family, $html);
                $family = trim($family . " $authorship");
                
                if(preg_match("/HER KOMMER SLEKTSTABELLENE(.*?)<\/TBODY>/ims", $html, $arr))
                {
                    if(preg_match_all("/<td(.*?)<\/td>/ims", $arr[1], $arr2))
                    {
                        foreach($arr2[1] as $block)
                        {
                            $block = "<td " . $block;
                            $block = strip_tags(html_entity_decode($block), "<td><tr><a><img><tbody><em><strong><br><font>");
                            $block = self::clean_html($block);
                            
                            $block = str_ireplace("<BR>", "<br>", $block);
                            $raw = explode("<br>", $block);
                            $line_items = self::process_line_items($raw, $url);
                            $taxa[$family]['author'] = $authorship;
                            $taxa[$family]['items'][] = $line_items;
                            $taxa[$family]['text'] = $article;
                        }
                    }
                }
            }
            // print_r($taxa); // here just one family
        }
        // print_r($taxa); // here for all taxa
        return $taxa;
    }
    
    private function process_line_items($items, $url)
    {
        $items = array_filter($items); //remove null array
        $final = array();
        foreach($items as $item)
        {
            if(preg_match_all("/<font size=\"-2\">(.*?)<\/font>/ims", $item, $arr)) continue; //e.g. http://www.ntnu.no/ub/scorpion-files/buthidae.php - Buthoscorpio Werner, 1936
            if(preg_match_all("/<font size=\"1\">(.*?)<\/font>/ims", $item, $arr)) continue;
                
            $item = strip_tags($item, "<strong>");
            if(is_numeric(stripos($item, "strong")) && !self::is_nomen_dubium($item)) $genus = self::format_utf8(trim(strip_tags($item)));
            else
            {
                if(isset($genus))
                {
                    if(!trim($item)) continue;
                    $first_char = substr($genus, 0, 1).".";
                    $species = Functions::canonical_form($genus) . " " . trim(str_replace($first_char, "", $item));
                    $species = strip_tags($species);                    
                    if($species != Functions::canonical_form($genus) . " ") $final[$genus][] = self::format_utf8($species);                    
                }
            }            
        }
        return $final;
    }
    
    private function format_utf8($str)
    {
        if(!Functions::is_utf8($str)) return utf8_encode($str);
        return $str;
    }
    
    private function process_line_items_v1($items, $url)
    {    
        $final = array();
        foreach($items as $item)
        {
            if(!is_numeric(stripos($item, ". ")) && !self::is_nomen_dubium($item)) $genus = trim(strip_tags($item));
            else
            {
                $first_char = substr($genus, 0, 1).".";
                $final[$genus][] = $genus . " " . trim(str_replace($first_char, "", $item));
            }
        }
        return $final;
    }

    private function is_nomen_dubium($str)
    {
        if(is_numeric(stripos($str, "nomen dubium"))) return true;
        if(is_numeric(stripos($str, "incertae sedis"))) return true;
        return false;
    }

    private function parse_text_object($html)
    {
        $html = self::clean_html($html);
        if($pos = stripos($html, "SPECIES FILES:"))
        {
            $i = $pos;
            for($x = $pos; $x >= 0; $x--)
            {
                $substr = substr($html, $x-1, 7);
                if($substr == "<TBODY>")
                {
                    $start_pos = $x-1;
                    break;
                }
            }
            $article = substr($html, $start_pos, $pos-$start_pos);
            $article = strip_tags($article, "<p><br><font><em>");            
            if(substr($article, -41) == "<P><FONT face=Arial color=#000000 size=4>") $article = substr($article, 0, strlen($article)-41);
            
            $article = str_ireplace(array("<p></p>"), "", $article);
            $article = trim($article);
            return $article;
        }
        return false;
    }
    
    private function get_family_authorship($family, $html)
    {
        $html = self::clean_html($html);
        if(preg_match("/" . $family . "<BR><\/FONT>(.*?)<\/FONT>/ims", $html, $arr)) return self::format_utf8(strip_tags($arr[1]));
        if(preg_match("/" . $family . "<BR> <\/FONT>(.*?)<\/FONT>/ims", $html, $arr)) return self::format_utf8(strip_tags($arr[1]));
        echo "\nnot found [$family]\n";
    }
    
    private function save_to_text_file($taxa)
    {
        //classification
        $filename = DOC_ROOT . "public/tmp/scorpion_classification.txt";
        $WRITE = fopen($filename, "w");
        fwrite($WRITE, "Order" . "\t" . "Family" . "\t" . "Genus" . "\t" . "Species" ."\n");
        foreach($taxa as $family => $rekords)
        {
            foreach($rekords['items'] as $rec)
            {
                foreach($rec as $genus => $species_list)
                {
                    foreach($species_list as $species) fwrite($WRITE, "Scorpiones" . "\t" . $family . "\t" . $genus . "\t" . $species . "\n");                        
                }
            }
        }
        fclose($WRITE);
        self::convert_tab_to_xls($filename);

        //family and article
        $filename = DOC_ROOT . "public/tmp/scorpion_families.txt";
        $WRITE = fopen($filename, "w");
        fwrite($WRITE, "Family" . "\t" . "Article" ."\n");
        foreach($taxa as $family => $rekords)
        {
            fwrite($WRITE, $family . "\t" . self::format_utf8($rekords['text']) . "\n");
        }
        fclose($WRITE);
        self::convert_tab_to_xls($filename);

    }
    
    public function get_scorpiones_text_objects() //specifically assigned two text objects
    {
        $filename = DOC_ROOT . "public/tmp/scorpion_order.txt";
        $WRITE = fopen($filename, "w");
        fwrite($WRITE, "Order" . "\t" . "Article" ."\n");
        
        $files = array($this->domain . "higher_phylogeny.php", $this->domain . "intro.php");
        foreach($files as $file)
        {
            if($html = Functions::lookup_with_cache($file, $this->download_options))
            {
                $html = self::clean_html($html);
                if(preg_match("/Higher taxonomy and phylogeny in scorpions(.*?)<TABLE/ims", $html, $arr))
                {
                    $str = strip_tags($arr[1], "<font><p><em><a><br>");
                    if(substr($str, 0, 12) == '.</font></p>') $str = trim(substr($str, 12, strlen($str))); //remove extra chars in the beginning of text
                }
                elseif(preg_match("/WELCOME TO THE SCORPION FILES!(.*?)<A HREF=\"http:\/\/www.ntnu.no\">/ims", $html, $arr))
                {
                    $str = strip_tags($arr[1], "<font><p><em><a><br>");
                    if(substr($str, 0, 7) == '</font>') $str = trim(substr($str, 7, strlen($str))); //remove extra chars in the beginning of text
                    $str = str_ireplace('<a href="higher_phylogeny.php">', '<a href="' . $this->domain . 'higher_phylogeny.php">', $str);
                }
            }            
            fwrite($WRITE, 'Scorpiones' . "\t" . $str . "\n");
        }
        
        fclose($WRITE);
        self::convert_tab_to_xls($filename);
    }
    
    public function get_all_hrefs($html)
    {
        if(!isset($this->all_hrefs)) $this->all_hrefs = array();
        if(preg_match_all("/href=\"(.*?)\"/ims", $html, $arr))
        {
            $this->all_hrefs = array_merge($this->all_hrefs, $arr[1]);
        }
    }
    
    private function parse_species_pages()
    {
        //initialize text files
        $headers['v1'] = array("Species", "Common names", "Distribution", "Habitat", "Venom", "Selected literature", "On the Internet", "General");
        $fields = implode("\t", $headers['v1']);
        $filename['v1'] = DOC_ROOT . "public/tmp/scorpion_species_pages_v1.txt";
        $WRITE = fopen($filename['v1'], "w"); fwrite($WRITE, $fields . "\n"); fclose($WRITE);

        $headers['v2'] = array("Species", "Subspecies", "Distribution", "Synonyms", "Habitat", "Medical", "Bibliography", "Comments");
        $fields = implode("\t", $headers['v2']);
        $filename['v2'] = DOC_ROOT . "public/tmp/scorpion_species_pages_v2.txt";
        $WRITE = fopen($filename['v2'], "w"); fwrite($WRITE, $fields . "\n"); fclose($WRITE);
        
        
        $final = array();
        $urls = self::get_species_url_lists();
        foreach($urls as $url)
        {
            echo "\n[$url] ";
            if($html = Functions::lookup_with_cache($url, $this->download_options))
            {
                $html = str_ireplace('&nbsp;', ' ', $html);
                $html = self::clean_html($html);
                
                //manual adjustment
                $html = str_ireplace("<em>Liocheles</em> waigiensis<br>", "<em>Liocheles waigiensis</em><br>", $html);
                $html = str_ireplace("<em>Vaejovis intermedius,</em><br>", "<em>Vaejovis intermedius</em>,<br>", $html);
                
                $html = str_ireplace('</FONT><FONT face=Arial size=3>', '</FONT> <FONT face=Arial size=3>', $html);
                
                if(preg_match("/<\/script>(.*?)<A HREF=\"http:\/\/www.ntnu.no\">/ims", $html, $arr))
                {
                    $sciname = false;
                    if(preg_match("/<font size=\"5\" face=\"Arial\">(.*?)<\/p>/ims", $html, $arr))     $sciname = strip_tags($arr[1]);
                    elseif(preg_match("/<FONT face=Arial size=5>(.*?)<\/p>/ims", $html, $arr))        $sciname = strip_tags($arr[1]);
                    if($sciname)
                    {
                        $final = self::parse_text_objects_v1($html, $url);
                        $final['Species'] = $sciname;
                        self::save_species_pages_to_text($final, $headers['v1'], $filename['v1']);
                    }
                    else
                    {
                        // echo " - no sciname 1 ";
                        if(preg_match("/Name:<\/td>(.*?)<\/td>/ims", $html, $arr)) $sciname = strip_tags($arr[1]);
                        $final = self::parse_text_objects_v2($html, $url);
                        $final['Species'] = $sciname;
                        self::save_species_pages_to_text($final, $headers['v2'], $filename['v2']);
                    }
                }
                else
                {
                    $sciname = false;
                    if(preg_match("/<font size=\"5\" face=\"Arial\">(.*?)<\/p>/ims", $html, $arr))     $sciname = strip_tags($arr[1]);
                    elseif(preg_match("/<FONT face=Arial size=5>(.*?)<\/p>/ims", $html, $arr))        $sciname = strip_tags($arr[1]);
                    if($sciname)
                    {
                        $final = self::parse_text_objects_v1($html, $url);
                        $final['Species'] = $sciname;
                        self::save_species_pages_to_text($final, $headers['v1'], $filename['v1']);                        
                    }
                    else echo " - no sciname 2 ";
                }
                echo " - [$sciname]";
            }
        }
        
        //converting to spreadsheet
        self::convert_tab_to_xls($filename['v1']);
        self::convert_tab_to_xls($filename['v2']);
        
    }
    
    private function parse_text_objects_v2($html, $url) //2nd type of html e.g. http://www.ntnu.no/ub/scorpion-files/a_amoreuxi_bio.php
    {
        $texts = array();
        if(preg_match("/Subspecies:<\/td>(.*?)<\/td>/ims", $html, $arr))    $texts['Subspecies'] = strip_tags($arr[1], "<em><br>");
        if(preg_match("/Distribution:<\/td>(.*?)<\/td>/ims", $html, $arr))     $texts['Distribution'] = strip_tags($arr[1], "<em><br>");
        if(preg_match("/Synonyms:<\/td>(.*?)<\/td>/ims", $html, $arr))         $texts['Synonyms'] = strip_tags($arr[1], "<em><br>");
        if(preg_match("/Habitat:<\/td>(.*?)<\/td>/ims", $html, $arr))         $texts['Habitat'] = strip_tags($arr[1], "<em><br>");
        if(preg_match("/Medical:<\/td>(.*?)<\/td>/ims", $html, $arr))         $texts['Medical'] = strip_tags($arr[1], "<em><br>");
        if(preg_match("/Bibliography:<\/td>(.*?)<\/td>/ims", $html, $arr))     $texts['Bibliography'] = strip_tags($arr[1], "<em><br>");
        if(preg_match("/Comments:<\/td>(.*?)<\/td>/ims", $html, $arr))         $texts['Comments'] = strip_tags($arr[1], "<em><br>");
        $texts = array_map('self::format_utf8', $texts);
        $texts = array_map('trim', $texts);
        return $texts;
    }
    
    private function parse_text_objects_v1($html, $url) //first type of html e.g. http://www.ntnu.no/ub/scorpion-files/c_bicolor.php
    {
        $html = strip_tags($html, "<p><i><a><em><br>");
        
        // if($url == "http://www.ntnu.no/ub/scorpion-files/h_lepturus.htm") echo("\n[$html]\n");
            
        //manual adjustment
        $html = str_ireplace('On the Internet:</p><A HREF="i_politius3.jpg">Picture of <I>I. politus</I> in the gallery</A> (Photo: Boris Striffler (C))', 'On the Internet:<br><A HREF="i_politius3.jpg">Picture of <I>I. politus</I> in the gallery</A> (Photo: Boris Striffler (C))</p>', $html);
        
        $texts = array();
        if(preg_match("/Common names:<br>(.*?)<\/p>/ims", $html, $arr))      $texts['Common names'] = $arr[1];
        elseif(preg_match("/Common names: <br>(.*?)<\/p>/ims", $html, $arr)) $texts['Common names'] = $arr[1];
        
        if(preg_match("/Distribution:<br>(.*?)<\/p>/ims", $html, $arr)) $texts['Distribution'] = $arr[1];
        if(preg_match("/Habitat:<br>(.*?)<\/p>/ims", $html, $arr))         $texts['Habitat'] = $arr[1];
        if(preg_match("/Venom:<br>(.*?)<\/p>/ims", $html, $arr))         $texts['Venom'] = $arr[1];
        
        if(preg_match("/Selected litterature:<br>(.*?)<\/p>/ims", $html, $arr))     $texts['Selected literature'] = $arr[1];
        elseif(preg_match("/Selected literature:<br>(.*?)<\/p>/ims", $html, $arr))     $texts['Selected literature'] = $arr[1];

        if(preg_match("/On the Internet:<br>(.*?)<P>/ims", $html, $arr))         $texts['On the Internet'] = $arr[1];        
        elseif(preg_match("/On the Internet:<br>(.*?)<\/p>/ims", $html, $arr))     $texts['On the Internet'] = $arr[1];
        
        if(preg_match("/General:<br>(.*?)<\/p> <p></ims", $html, $arr))     $texts['General'] = $arr[1];                
        elseif(preg_match("/General:<br>(.*?)<\/p> <p>/ims", $html, $arr))     $texts['General'] = $arr[1];        
        elseif(preg_match("/General:<br>(.*?)<\/p><p>/ims", $html, $arr))     $texts['General'] = $arr[1];        
        elseif(preg_match("/General:<br>(.*?)<\/p>/ims", $html, $arr))         $texts['General'] = $arr[1];
        
        return $texts;
    }
    
    private function save_species_pages_to_text($rec, $headers, $filename)
    {
        $WRITE = fopen($filename, "a"); 
        foreach($headers as $fld)
        {
            $val = trim($rec[$fld]);
            $val = self::format_utf8($val);
            if(strip_tags($val) == "") $val = '';
            fwrite($WRITE, $val . "\t");
        }
        fwrite($WRITE, "\n"); 
        fclose($WRITE);        
    }
    
    private function get_species_url_lists()
    {
        $arr = $this->all_hrefs;
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key

        $final = array();
        foreach($arr as $href)
        {
            $str = "http://www.ub.ntnu.no/scorpion-files/";
            if(substr($href, 0, strlen($str)) == $str) $final[$href] = '';
            if(substr($href, 1, 1) == "_") $final[$this->domain . $href] = '';
        }
        
        //manual adjustment
        unset($final["http://www.ub.ntnu.no/scorpion-files/medical.php"]);
        unset($final["http://www.ub.ntnu.no/scorpion-files/euscorpius_id.php"]);
        unset($final["http://www.ub.ntnu.no/scorpion-files/litterature.php"]);
        unset($final["http://www.ub.ntnu.no/scorpion-files/e_carpathicus_habitat.htm"]);
        unset($final["http://www.ub.ntnu.no/scorpion-files/e_flavicaudis_habitatuk.htm"]);
        unset($final["http://www.ntnu.no/ub/scorpion-files/i_dufoureius_habitat_crete.php"]);
        unset($final["http://www.ntnu.no/ub/scorpion-files/i_dufoureius_habitat.php"]);
        unset($final["http://www.ub.ntnu.no/scorpion-files/higher_phylogeny.php"]);
        unset($final["http://www.ntnu.no/ub/scorpion-files/a_crassicauda_kuwait.php"]);
        unset($final["http://www.ntnu.no/ub/scorpion-files/l_quinquestriatus_info.pdf"]);
        unset($final["http://www.ub.ntnu.no/scorpion-files/iuridae_updates.pdf"]);
        
        // print_r($final); echo "\n" . count($final) . "\n";
        return array_keys($final);
    }

    public function convert_tab_to_xls($source)
    {
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php';
        $inputFileName = $source;
        $outputFileName = str_replace(".txt", ".xls", $inputFileName);
        // start conversion
        $objReader = \PHPExcel_IOFactory::createReader('CSV');
        // If the files uses a delimiter other than a comma (e.g. a tab), then tell the reader
        $objReader->setDelimiter("\t");
        // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
        // $objReader->setInputEncoding('UTF-16LE');
        /* other settings:
        $objReader->setEnclosure(" ");
        $objReader->setLineEnding($endrow);
        */
        $objPHPExcel = $objReader->load($inputFileName);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($outputFileName);

        unlink($inputFileName); //if you want to delete the .txt files, and leave only the .xls files
    }

    private function clean_html($html)
    {
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        return Functions::remove_whitespace($html);
    }
    
}
?>
