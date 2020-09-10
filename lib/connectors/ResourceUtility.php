<?php
namespace php_active_record;
/* This is a generic utility for DwCA post-processing.
first client: called from DwCA_Utility.php, which is called from remove_taxa_without_MoF.php
2nd client  : add canonical_name inside taxon.tab using gnparser command-line
            : called from DwCA_Utility.php, which is called from add_canonical_in_taxa.php
*/

class ResourceUtility
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        $this->extracted_scinames = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_scinames.txt";
        $this->gnparsed_scinames = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . "_canonical.txt";
        
    }
    /*============================================================ STARTS add_canonical_in_taxa =================================================*/
    function add_canonical_in_taxa($info) //Func2
    {   
        $tables = $info['harvester']->tables;
        self::process_taxon_Func2($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write sciname list for gnparser');
        self::insert_canonical_in_taxa();
    }
    private function insert_canonical_in_taxa()
    {   //step 1: run gnparser
        $file_cnt = 0;
        while(true) { $file_cnt++;
            $source = $this->extracted_scinames."_".$file_cnt;
            $destination = $this->gnparsed_scinames."_".$file_cnt;
            if(file_exists($source)) {
                $cmd = "gnparser file -f simple --input $source --output $destination"; //'simple' or 'json-compact'
                $out = shell_exec($cmd); echo "\n$out\n";
            }
            else break;
        }
    }
    private function process_taxon_Func2($meta, $task)
    {   //print_r($meta);
        echo "\nResourceUtility...($task)...\n"; $i = 0;
        
        $file_cnt = 1;
        $WRITE = fopen($this->extracted_scinames."_".$file_cnt, "w"); $eli = 0;
        
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug1...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => urn:lsid:marinespecies.org:taxname:1
                [http://rs.tdwg.org/dwc/terms/scientificName] => Biota
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] =>
                ...
            )*/
            if($task == 'write sciname list for gnparser') {
                if(($i % 400000) == 0) {
                    $file_cnt++;
                    fclose($WRITE);
                    $WRITE = fopen($this->extracted_scinames."_".$file_cnt, "w");
                }
                if($val = trim($rec['http://rs.tdwg.org/dwc/terms/scientificName'])){}
                else $eli++;
                
                fwrite($WRITE, $rec['http://rs.tdwg.org/dwc/terms/scientificName'] . "\n");
            }
            elseif($task == 'write taxa') {
                $uris = array_keys($rec);
                $o = new \eol_schema\Taxon();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
        }
        fclose($WRITE);
        echo "\nelix = $eli\n";
        // exit;
    }
    /*============================================================ ENDS add_canonical_in_taxa ===================================================*/
    
    /*============================================================ STARTS remove_taxa_without_MoF =================================================*/
    function remove_taxa_without_MoF($info) //Func1
    {   
        $tables = $info['harvester']->tables;
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);    //build $this->taxon_ids
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);              //write taxa
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        echo "\nResourceUtility...read occurrences...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /**/
            //------------------------------------------------------------------------------
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $this->taxon_ids[$taxonID] = '';
            //------------------------------------------------------------------------------
        }
    }
    private function process_taxon($meta)
    {   //print_r($meta);
        echo "\nResourceUtility...write taxa...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /**/
            //------------------------------------------------------------------------------
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if(!isset($this->taxon_ids[$taxonID])) continue;
            //------------------------------------------------------------------------------
            $uris = array_keys($rec);
            $o = new \eol_schema\Taxon();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    /*============================================================ ENDS remove_taxa_without_MoF ==================================================*/
}
?>