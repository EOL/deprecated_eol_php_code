<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from 727.php for DATA-1819] */
class USDAPlants2019
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);

        /*
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], $ret);
        print_r($this->debug);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        */
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        $i = 0;
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
            print_r($rec); exit;
            /*
            )*/

            /* fix source link */
            $taxonID = $this->linkage_oID_tID[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']];
            if($taxonID == "EOL:11584278") continue; //exclude
            $sciname = $this->linkage_tID_sName[$taxonID];
            $rec['http://purl.org/dc/terms/source'] = "https://eol.org/search?q=".str_replace(" ", "%20", $sciname);
            
            $o = new \eol_schema\MeasurementOrFact();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function process_taxon($meta, $ret)
    {   //print_r($meta);
        $i = 0;
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
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => EOL:2
                [http://rs.tdwg.org/dwc/terms/scientificName] => Acanthocephala
                [http://rs.tdwg.org/dwc/terms/kingdom] => Animalia
                [http://rs.tdwg.org/dwc/terms/phylum] => 
                [http://rs.tdwg.org/dwc/terms/class] => 
                [http://rs.tdwg.org/dwc/terms/family] => 
                [http://rs.tdwg.org/dwc/terms/order] => 
                [http://rs.tdwg.org/dwc/terms/genus] => 
            )*/
            
            if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == 'EOL:11584278') continue;
            
            $this->linkage_tID_sName[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
            
            $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
            $ranks = array('phylum');
            foreach($ranks as $rangk) {
                if($val = $rec['http://rs.tdwg.org/dwc/terms/'.$rangk]) {
                    $val = Functions::canonical_form($val);
                    if(!isset($ret['ancestry'][$rangk][$val])) {
                        // $this->debug["not $rangk in DH"][$val] = @$ret['taxa'][$val]." in DH";
                        $rec['http://rs.tdwg.org/dwc/terms/'.$rangk] = ''; //discarded
                        if($correct_rank = @$ret['taxa'][$val]) {
                            $this->debug["not $rangk in DH"][$val] = " - $correct_rank in DH. Moved.";
                            $rec['http://rs.tdwg.org/dwc/terms/'.$correct_rank] = $val;
                            $this->debug['moved to'][$val] = $correct_rank;
                        }
                        else $this->debug["not $rangk in DH"][$val] = @$ret['taxa'][$val]." - not found in DH. Discarded.";
                    }
                }
            }
            
            $o = new \eol_schema\Taxon();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            if($o->taxonID == 'EOL:11584278') continue; //exclude scientificName = '(undescribed)'
            
            //start of adjustments: https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=63624&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63624
            $o->scientificName = self::fix_sciname($o->scientificName);
            $o = self::remove_authority_in_ancestry_fields($o);
            
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function remove_authority_in_ancestry_fields($o)
    {   /* From Jen: 
        ancestry columns: I think authority strings may cause problems here- they vary for the same taxon, and I'm not sure what that does to the ancestry tree. 
        I'm going to suggest the crude solution of truncating the values in all 6 ancestry columns after the first word. 
        */
        $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
        foreach($ranks as $rank) {
            if($str = trim($o->$rank)) {
                $arr = explode(" ", $str);
                $o->$rank = $arr[0];
            }
        }
        return $o;
    }
    private function fix_sciname($sci)
    {   /* From Jen: scientificName: If the first word in the namestring is in (), remove them- the parentheses, not the namestring */
        // EOL:10652191 (tribe) Borophagini G. G. Simpson, 1945
        // EOL:11584278 (undescribed)
        // EOL:37671125 (Multipeniata) sp. Ax & Schmidt-Rhaesa, 1992
        if(substr($sci,0,1) == '(') {
            if(preg_match("/\((.*?)\)/ims", $sci, $arr)) {
                $inside = $arr[1]; //str inside the parenthesis
                if(ctype_lower(substr($inside,0,1))) { //if first letter of str inside parenthesis is LOWER case
                    $sci = str_replace("($inside)", "", $sci);
                    $sci = Functions::remove_whitespace($sci);
                    return trim($sci);
                }
                else { //if first letter of str inside parenthesis is UPPER case
                    $sci = str_replace("($inside)", "$inside", $sci);
                    $sci = Functions::remove_whitespace($sci);
                    return trim($sci);
                }
            }
        }
        
        /* Cases were: one word, and starts with small letter
        EOL:62196	collomia	Plantae	Tracheophyta				
        EOL:62197	colubrina	Plantae	Tracheophyta				
        */
        if(ctype_lower(substr($sci,0,1))) $sci = ucfirst($sci);
        
        return $sci;
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        $i = 0;
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
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 6c6b79090187369e36a81b8fc84b14f6_708
                [http://rs.tdwg.org/dwc/terms/taxonID] => EOL:2
            )*/
            
            $this->linkage_oID_tID[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            
            $o = new \eol_schema\Occurrence();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            if($o->taxonID == 'EOL:11584278') continue; //exclude scientificName = '(undescribed)'
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
