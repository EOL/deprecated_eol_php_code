<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from 708_new.php] */
class New_EnvironmentsEOLDataConnector
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        if(Functions::is_production()) {
            // $this->eol_taxon_concept_names_tab    = "/extra/other_files/DWH/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt"; //working but old DH ver.
            $this->eol_taxon_concept_names_tab    = "/extra/other_files/DWH/TRAM-809/DH_v1_1/taxon.tab";    //latest active DH ver.
        }
        else {
            // $this->eol_taxon_concept_names_tab    = "/Volumes/AKiTiO4/other_files/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt"; //working but old DH ver.
            $this->eol_taxon_concept_names_tab = "/Volumes/AKiTiO4/d_w_h/EOL Dynamic Hierarchy Active Version/DH_v1_1/taxon.tab"; //latest active DH ver.
        }
        
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        $tables = $info['harvester']->tables; 
        self::get_all_phylum_in_DH();
        // self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        // self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]); //main operation in DATA-1812: For every record, create an additional record in reverse.
        // self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]); //this is to exclude taxonID = EOL:11584278 (undescribed)
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
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 7efeff6e1f506b3523f66d644e41b75b_708
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 6c6b79090187369e36a81b8fc84b14f6_708
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Habitat
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_00000446
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => text mining
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "terrestrial"
                [http://purl.org/dc/terms/source] => http://eol.org/pages/2
                [http://purl.org/dc/terms/contributor] => <a href="http://environments-eol.blogspot.com/2013/03/welcome-to-environments-eol-few-words.html">Environments-EOL</a>
                [http://eol.org/schema/reference/referenceID] => 
            )*/
            
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
    private function process_taxon($meta)
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
    private function get_all_phylum_in_DH($path = false, $listOnly = false) //total rows = 2,724,672 | rows where EOLid is not blank = 2,237,550
    {
        if(!$path) $path = $this->eol_taxon_concept_names_tab;
        $i = 0;
        foreach(new FileIterator($path) as $line => $row) {
            if(!$row) continue;
            $i++;
            if($i == 1) $fields = explode("\t", $row);
            else {
                $rec = explode("\t", $row);
                $k = -1; $rek = array();
                foreach($fields as $field) {
                    $k++;
                    $rek[$field] = $rec[$k];
                }
                // print_r($rek); exit;
                /*Array(
                    [taxonID] => EOL-000000000001
                    [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                    [furtherInformationURL] => 
                    [acceptedNameUsageID] => 
                    [parentNameUsageID] => 
                    [scientificName] => Life
                    [higherClassification] => 
                    [taxonRank] => clade
                    [taxonomicStatus] => valid
                    [taxonRemarks] => 
                    [datasetID] => trunk
                    [canonicalName] => Life
                    [EOLid] => 2913056
                    [EOLidAnnotations] => 
                    [Landmark] => 3
                )*/
            }
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
