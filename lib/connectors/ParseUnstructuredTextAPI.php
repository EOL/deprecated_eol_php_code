<?php
namespace php_active_record;
/* */
class ParseUnstructuredTextAPI
{
    function __construct()
    {
        $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);

        $this->service['GNRD'] = 'http://gnrd.globalnames.org/name_finder.json?url=https://editors.eol.org/other_files/temp/FILENAME&unique=true';
        //e.g. http://gnrd.globalnames.org/name_finder.json?url=https://editors.eol.org/other_files/temp/pdf2text_output.txt&unique=true
        $this->possible_prefix_word = array('Family', 'Genus');
        
        /* START pdftotext */
        $this->path['pdftotext_output'] = '/Volumes/AKiTiO4/other_files/pdftotext/'; //pertains to xpdf in legacy codebase
        $this->service['GNRD'] = 'http://gnrd.globalnames.org/name_finder.json?url=https://editors.eol.org/other_files/temp/FILENAME&unique=true';
        /* END pdftotext */
        
        /* START epub series */
        $this->path['epub_output_txts_dir'] = '/Volumes/AKiTiO4/other_files/epub/'; //dir for converted epubs to txts
        $this->service['GNRD text input'] = 'http://gnrd.globalnames.org/name_finder.json?text=';
        /* END epub series */
    }
    /* Special chard mentioned by Dima, why GNRD stops running.
    str_replace("")
    str_replace("")
    */
    /*#################################################################################################################################*/
    function parse_pdftotext_result($filename) //Mar 25, 2021 - start epub series
    {   
        // $this->scinames = self::get_unique_scinames($filename); //print_r($this->scinames); exit;
        self::get_main_scinames($filename);
        print_r($this->lines_to_tag); echo "\nscinames: ".count($this->lines_to_tag)."\n";
        $edited_file = self::add_taxon_tags_to_text_file_v3($filename);
        self::remove_some_rows($edited_file);
        self::show_parsed_texts_for_mining($edited_file);
    }
    //else           $row = "</taxon><taxon sciname='$sciname'> ".$row;
    private function get_main_scinames($filename)
    {
        $local = $this->path['epub_output_txts_dir'].$filename;
        $start_of_row_2_exclude = array("FIGURE", "Key to the", "Genus", "Family", "*", "(", "Contents", "Literature", "Miscellaneous", 
        "Introduction", "Appendix", "ACKNOWLEDGMENTS", "TERMINOLOGY");
        // /* loop text file
        $i = 0; $ctr = 0;
        foreach(new FileIterator($local) as $line => $row) { $ctr++;
            $i++; if(($i % 5000) == 0) echo " $i";
            $row = trim($row);
            
            $cont = true;
            // /* criteria 1
            foreach($start_of_row_2_exclude as $start_of_row) {
                $len = strlen($start_of_row);
                if(substr($row,0,$len) == $start_of_row) {
                    $rows = array();
                    $cont = false; break;
                }
            }
            if(!$cont) continue;
            // */
            
            // /* criteria 2: if first word is all caps e.g. ABSTRACT
            if($row) {
                $words = explode(" ", $row);
                if(ctype_upper($words[0]) && strlen($words[0]) > 1) {
                    // print_r($words); //exit;
                    $rows = array();
                    continue;
                }
            }
            // */
            
            $rows[] = $row;
            if(count($rows) == 5) { //start evaluating records of 5 rows
                if(!$rows[0] && !$rows[1] && !$rows[3] && !$rows[4]) {
                    if($rows[2]) {
                        $words = explode(" ", $rows[2]);
                        if(count($words) <= 6)  {
                            if(substr($rows[2],1,1) != ".") { //not e.g. "C. Allan Child"
                                if(self::is_sciname($rows[2])) {
                                    print_r($rows);
                                    // $this->lines_to_tag[$rows[2]] = '';
                                    $this->lines_to_tag[$ctr-2] = '';
                                }
                            }
                        }
                    }
                }
                array_shift($rows); //remove 1st element, once it reaches 5 rows.
            }
        }
        // */
    }
    private function is_sciname($string)
    {
        /* from GNRD
        http://gnrd.globalnames.org/name_finder.json?text=A+spider+named+Pardosa+moesta+Banks,+1892 
        */
        $url = $this->service['GNRD text input'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            if($obj->names) return true;
        }
        return false;
    }
    private function add_taxon_tags_to_text_file_v3($filename)
    {
        $local = $this->path['epub_output_txts_dir'].$filename;
        $temp_file = $local.".tmp";
        $edited_file = str_replace(".txt", "_edited.txt", $local);
        copy($local, $edited_file);
        
        $WRITE = fopen($temp_file, "w"); //initialize
        $hits = 0;
        
        // /* loop text file
        $i = 0;
        foreach(new FileIterator($edited_file) as $line => $row) { $i++; if(($i % 5000) == 0) echo " $i";
            $row = trim($row);
            if(isset($this->lines_to_tag[$i])) { $hits++;
                if($hits == 1)  $row = "<taxon sciname='$row'> ".$row;
                else            $row = "</taxon><taxon sciname='$row'> ".$row;
                // exit("\ngot one finally\n".$row."\n");
            }
            // else echo "\n[$row]\n";
            fwrite($WRITE, $row."\n");
        }//end loop text
        fclose($WRITE);
        if(copy($temp_file, $edited_file)) unlink($temp_file);
        
        // print_r($this->lines_to_tag);
        return $edited_file;
    }
    private function remove_some_rows($edited_file)
    {
        // exit("\nxxx[$edited_file]\n");
        $local = $edited_file;
        $temp_file = $local.".tmp";
        $WRITE = fopen($temp_file, "w"); //initialize
        $start_of_row_2_exclude = array("FIGURE", "Key to the", "Genus", "Family");
        
        // /* loop text file
        $i = 0;
        foreach(new FileIterator($local) as $line => $row) { $i++; if(($i % 5000) == 0) echo " $i";
            $row = trim($row);
            
            $cont = true;
            // /* criteria 1
            foreach($start_of_row_2_exclude as $start_of_row) {
                $len = strlen($start_of_row);
                if(substr($row,0,$len) == $start_of_row) {
                    $rows = array();
                    $cont = false; break;
                }
            }
            if(!$cont) continue;
            // */
            
            fwrite($WRITE, $row."\n");
        }//end loop text
        fclose($WRITE);
        if(copy($temp_file, $edited_file)) unlink($temp_file);
        
    }
    /*#################################################################################################################################*/
    function parse_text_file($filename)
    {
        $scinames = self::get_unique_scinames($filename);
        $edited_file = self::add_taxon_tags_to_text_file_v1($scinames, $filename); //big process
        self::show_parsed_texts_for_mining($edited_file);
    }
    private function show_parsed_texts_for_mining($edited_file)
    {
        $with_blocks_file = str_replace("_edited.txt", "_blocks.txt", $edited_file);
        $WRITE = fopen($with_blocks_file, "w"); //initialize
        $contents = file_get_contents($edited_file);
        if(preg_match_all("/<taxon (.*?)<\/taxon>/ims", $contents, $a)) {
            // print_r($a[1]);
            foreach($a[1] as $block) {
                $rows = explode("\n", $block);
                // if(count($rows) >= 5) {
                if(true) {
                    $show = "\n-----------------------\n<$block</sciname>\n-----------------------\n";
                    fwrite($WRITE, $show); // echo $show;
                }
            }
        }
        fclose($WRITE);
        echo "\nblocks: ".count($a[1])."\n";
    }
    private function get_unique_scinames($filename) //get unique names using GNRD
    {
        $url = str_replace('FILENAME', $filename, $this->service['GNRD']);
        $options = $this->download_options;
        // $options['expire_seconds'] = 10; //10 seconds
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            foreach($obj->names as $name) $final[$name->scientificName] = '';
        }
        $scinames = array_keys($final);
        return self::arrange_order_of_names($scinames);
    }
    private function arrange_order_of_names($scinames)
    {   
        // echo("\n orig ".count($scinames)."\n");
        foreach($scinames as $sciname) { $arr = explode(" ", $sciname);
            if(count($arr) >= 3) $final[] = $sciname;
        }
        foreach($scinames as $sciname) { $arr = explode(" ", $sciname);
            if(count($arr) == 2) $final[] = $sciname;
        }
        foreach($scinames as $sciname) { $arr = explode(" ", $sciname);
            if(count($arr) == 1) $final[] = $sciname;
        }
        foreach($scinames as $sciname) { $arr = explode(" ", $sciname);
            if(count($arr) == 0) $final[] = $sciname;
        }
        // print_r($final); exit("\n sorted ".count($final)."\n");
        return $final;
    }
    function parse_pdf2htmlEX_result($filename)
    {
        $html_file = $this->path['pdf2htmlEX_output'].$filename;
        $html = file_get_contents($html_file);
        $html = strip_tags($html, "<br>");
        echo "\n$html\n";
    }
}
?>
