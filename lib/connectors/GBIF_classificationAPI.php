<?php
namespace php_active_record;
// connector: [gbif_classification.php]
class GBIF_classificationAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array(
            'resource_id'        => 'eol_api_v3',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'cache' => 1);

        if(Functions::is_production()) {
            $this->service["backbone_dwca"] = "http://rs.gbif.org/datasets/backbone/backbone-current.zip";
        }
        else {
            $this->service["backbone_dwca"] = "http://localhost/cp/GBIF_Backbone_Archive/backbone-current.zip";
        }
        $this->log_file = CONTENT_RESOURCE_LOCAL_PATH.'gbif_names_not_found_in_eol.txt';
    }
    private function access_dwca()
    {   
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->service["backbone_dwca"], "meta.xml", $this->download_options);
        // */
        /* local when developing
        $paths = Array(
            "archive_path" => "/Library/WebServer/Documents/eol_php_code/tmp/dir_66855_gbif/",
            "temp_dir" => "/Library/WebServer/Documents/eol_php_code/tmp/dir_66855_gbif/"
        );
        */
        return $paths;
    }
    function start()
    {   $paths = self::access_dwca();
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }

        if(!($file = Functions::file_open($this->log_file, "w"))) return;
        fwrite($file, implode("\t", array('taxonID', 'scientificName', 'searched string', 'flag'))."\n");
        fclose($file);
        
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        $this->archive_builder->finalize(TRUE);

        // /* un-comment in real operation
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
    }
    private function process_taxon($meta)
    {   //print_r($meta);
        require_library('connectors/Eol_v3_API');
        $func = new Eol_v3_API();
        
        echo "\nprocess_taxon...\n"; $i = 0;
        $m = 5858200/7; //total rows = 5,858,143. Rounded to 5858200. For caching.
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
            
            // /* breakdown when caching
            $cont = false;
            // if($i >=  1    && $i < $m)    $cont = true;          //1st run
            // if($i >=  $m   && $i < $m*2)  $cont = true;          //2nd run
            if($i >=  $m*2 && $i < $m*3)  $cont = true;          //3rd run
            // if($i >=  $m*3 && $i < $m*4)  $cont = true;          //4th run
            // if($i >=  $m*4 && $i < $m*5)  $cont = true;          //5th run
            // if($i >=  $m*5 && $i < $m*6)  $cont = true;          //6th run
            // if($i >=  $m*6 && $i < $m*7)  $cont = true;          //7th run
            if(!$cont) continue;
            // */
            
            // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 9651193                   [http://rs.tdwg.org/dwc/terms/datasetID] => 61a5f178-b5fb-4484-b6d8-9b129739e59d
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 95
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] =>               [http://rs.tdwg.org/dwc/terms/originalNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/scientificName] => SH200216.07FU      [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => 
                [http://rs.gbif.org/terms/1.0/canonicalName] =>                     [http://rs.gbif.org/terms/1.0/genericName] => 
                [http://rs.tdwg.org/dwc/terms/specificEpithet] =>                   [http://rs.tdwg.org/dwc/terms/infraspecificEpithet] => 
                [http://rs.tdwg.org/dwc/terms/taxonRank] => unranked
                [http://rs.tdwg.org/dwc/terms/nameAccordingTo] =>                   [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => accepted          [http://rs.tdwg.org/dwc/terms/nomenclaturalStatus] => 
                [http://rs.tdwg.org/dwc/terms/taxonRemarks] => 
                [http://rs.tdwg.org/dwc/terms/kingdom] => Fungi                     [http://rs.tdwg.org/dwc/terms/phylum] => Ascomycota
                [http://rs.tdwg.org/dwc/terms/class] =>                             [http://rs.tdwg.org/dwc/terms/order] => 
                [http://rs.tdwg.org/dwc/terms/family] =>                            [http://rs.tdwg.org/dwc/terms/genus] => 
            )*/
            $taxonomicStatus = $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'];

            /* good debug
            if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == 4943435) {
                    print_r($rec); //exit;
            }
            else continue;
            */
            
            // if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == 7009828) { print_r($rec); exit; } //debug only

            if($taxonomicStatus != 'accepted') continue;

            if($val = $rec['http://rs.gbif.org/terms/1.0/canonicalName'])       $sciname = $val;
            elseif($val = $rec['http://rs.tdwg.org/dwc/terms/scientificName'])  $sciname = Functions::canonical_form($val);
            else { self::log_record($rec, '', '1'); continue; }
            if(!$sciname) { self::log_record($rec, '', '2'); continue; }

            $str = substr($sciname,0,2);
            if(strtoupper($str) == $str) { //probably viruses
                // echo "\nwill ignore [$sciname]\n";
                // self::log_record($rec, $sciname);
                continue;
            }
            else {
                $eol_rec = Array('id' => '', 'title' => '', 'link' => '', 'content' => '');
                // /*
                if($GLOBALS['ENV_DEBUG'] == true) echo "\nwill process [$i][$sciname] "; // print_r($rec);
                if($ret = $func->search_name($sciname, $this->download_options)) {
                    if($GLOBALS['ENV_DEBUG'] == true) echo " - ".count($ret['results']);
                    $eol_rec = self::get_actual_name($ret, $sciname);
                    /* good debug
                    if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == '4943435') {
                        print_r($rec);
                        print_r($ret);
                        print_r($eol_rec);
                        exit;
                    }
                    */
                }
                self::write_archive($rec, $eol_rec);
                if(!$eol_rec['id']) { self::log_record($rec, $sciname, '3'); continue; }
                // */
            }
            // if($i >= 90) break;
        }
    }
    private function log_record($rec, $sciname = '', $flag = '')
    {
        if(!($file = Functions::file_open($this->log_file, "a"))) return;
        fwrite($file, implode("\t", array($rec['http://rs.tdwg.org/dwc/terms/taxonID'], $rec['http://rs.tdwg.org/dwc/terms/scientificName'], "[$sciname]", $flag))."\n");
        fclose($file);
    }
    private function write_archive($rec, $eol_rec)
    {
        // print_r($rec); print_r($eol_rec);
        $fields = array_keys($rec);
        // print_r($fields); exit;
        /*Array( $eol_rec
            [id] => 37570
            [title] => Lichenobactridium
            [link] => https://eol.org/pages/37570
            [content] => Lichenobactridium; Lichenobactridium P. Diederich & J. Etayo in F.J.A. Daniels et al., 1995
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->EOLid = $eol_rec['id'];
        // $taxon->EOLidAnnotations = $eol_rec['content'];
        foreach($fields as $field) {
            $var = pathinfo($field, PATHINFO_BASENAME);
            if(in_array($var, array('genericName'))) continue;
            $taxon->$var = $rec[$field];
        }
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function get_actual_name($ret, $sciname)
    {
        foreach($ret['results'] as $r) { //first loop gets exact match only
            /*Array(
                [id] => 37570
                [title] => Lichenobactridium
                [link] => https://eol.org/pages/37570
                [content] => Lichenobactridium; Lichenobactridium P. Diederich & J. Etayo in F.J.A. Daniels et al., 1995
            )*/
            if($sciname == $r['title']) return $r;
        }
        if($ret['results']) return $ret['results'][0]; //alternatively, just return the first record
    }
    /*
    private function create_taxon_archive($a)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonomicStatus          = self::compute_taxonomicStatus($a);
        $taxon->taxonID                  = self::compute_taxonID($a, $taxon->taxonomicStatus);
        $taxon->scientificName           = $a[$this->map['scientificName']];
        $taxon->scientificNameAuthorship = @$a[$this->map['scientificNameAuthorship']];
        $taxon->taxonRank                = self::compute_taxonRank($a);
        $taxon->acceptedNameUsageID      = self::numerical_part(@$a[$this->map['acceptedNameUsageID']]);
        $this->archive_builder->write_object_to_file($taxon);
    }
    */
}
?>