<?php
namespace php_active_record;
/* */
class ParseUnstructuredTextAPI
{
    function __construct()
    {
        $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->path['pdf2text_output'] = '/Volumes/AKiTiO4/other_files/pdfparser-0.18.2/';
        $this->service['GNRD'] = 'http://gnrd.globalnames.org/name_finder.json?url=https://editors.eol.org/other_files/temp/FILENAME&unique=true';
        //e.g. http://gnrd.globalnames.org/name_finder.json?url=https://editors.eol.org/other_files/temp/pdf2text_output.txt&unique=true
        $this->possible_prefix_word = array('Family', 'Genus');
    }
    function parse_text_file($filename)
    {
        $scinames = self::get_unique_scinames($filename);
        $edited_file = self::add_taxon_tags_to_text_file($scinames, $filename); //big process
        self::show_parsed_texts_for_mining($edited_file);
    }
    private function show_parsed_texts_for_mining($edited_file)
    {
        $contents = file_get_contents($edited_file);
        if(preg_match_all("/<taxon (.*?)<\/taxon>/ims", $contents, $a)) {
            // print_r($a[1]);
            foreach($a[1] as $block) {
                $rows = explode("\n", $block);
                if(count($rows) >= 5) echo "\n-----------------------\n<$block</sciname>\n-----------------------\n";
            }
        }
    }
    private function add_taxon_tags_to_text_file($scinames, $filename)
    {
        $local = $this->path['pdf2text_output'].$filename;
        $temp_file = $local.".tmp";
        $edited_file = str_replace(".txt", "_edited.txt", $local);
        copy($local, $edited_file);
        
        $WRITE = fopen($temp_file, "w"); //initialize
        $hits = 0;
        foreach($scinames as $sciname) {
            if(substr($sciname,1,2) == ". ") continue; //T. bigibbosum
            $sciname_len = strlen($sciname);
            // /* loop text file
            $i = 0;
            foreach(new FileIterator($edited_file) as $line => $row) {
                $i++; if(($i % 5000) == 0) echo " $i";
                if(!$row) continue;
                if(strtolower(substr($row,0,$sciname_len)) == strtolower($sciname)) { //put <taxon> tag
                    $hits++;
                    if($hits == 1) $row = "<taxon sciname='$sciname'> ".$row;
                    else           $row = "</taxon><taxon sciname='$sciname'> ".$row;
                }
                else { //check for prefix word if 'Family' or 'Genus'
                    $arr = explode(" ", $row);
                    if(in_array($arr[0], $this->possible_prefix_word)) {
                        if(strtolower(substr($arr[1],0,$sciname_len)) == strtolower($sciname)) { //put <taxon> tag
                            $hits++;
                            if($hits == 1) $row = "<taxon sciname='$sciname'> ".$row;
                            else           $row = "</taxon><taxon sciname='$sciname'> ".$row;
                        }
                    }
                }
                fwrite($WRITE, $row."\n");
                // if($i >= 1000) break;
            }
            // */
            fclose($WRITE);
            if(copy($temp_file, $edited_file)) unlink($temp_file);
            $WRITE = fopen($temp_file, "w"); //initialize
        }// loop scinames
        return $edited_file;
    }
    private function get_unique_scinames($filename) //get unique names using GNRD
    {
        $url = str_replace('FILENAME', $filename, $this->service['GNRD']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
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
}
?>
