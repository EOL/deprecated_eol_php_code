<?php
namespace php_active_record;
/* connector: [DHconn.php] */
class DHConnLib
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();
        if(Functions::is_production()) { //not yet run in production...
            $this->download_options = array(
                'cache_path'         => '/extra/active_DH_cache/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/extra/other_files/DWH/TRAM-809/DH_v1_1/";
        }
        else {
            $this->download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/active_DH_cache/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/EOL Dynamic Hierarchy Active Version/DH_v1_1/";
        }
        
        $this->listOf_order_family_genus['order'] = CONTENT_RESOURCE_LOCAL_PATH . '/listOf_order_4maps.txt';
        $this->listOf_order_family_genus['family'] = CONTENT_RESOURCE_LOCAL_PATH . '/listOf_family_4maps.txt';
        $this->listOf_order_family_genus['genus'] = CONTENT_RESOURCE_LOCAL_PATH . '/listOf_genus_4maps.txt';

    }
    // ----------------------------------------------------------------- start -----------------------------------------------------------------
    function generate_children_of_taxa_from_DH()
    {
        self::get_taxID_nodes_info($this->main_path.'/taxon.tab', 'initialize');
        self::get_taxID_nodes_info($this->main_path.'/taxon.tab', 'buildup ancestry and children');

        self::get_taxID_nodes_info($this->main_path.'/taxon.tab', 'save children of genus and family', 'order');
        self::get_taxID_nodes_info($this->main_path.'/taxon.tab', 'save children of genus and family', 'family');
        self::get_taxID_nodes_info($this->main_path.'/taxon.tab', 'save children of genus and family', 'genus');

        /* tests only - OK
        $eol_id = '46564414'; //Gadus
        // $ancestry = self::get_ancestry_of_taxID($eol_id); print_r($ancestry); //worked OK
        $children = self::get_children_from_json_cache($eol_id, array(), true); print_r($children); //worked OK
        echo "\ncount: ".count($this->taxID_info)."\n";
        exit("\n-end tests-\n");
        */
        exit("\nend muna\n");
    }
    private function get_taxID_nodes_info($txtfile, $purpose, $filter_rank = '')
    {
        echo "\nPurpose: $purpose...\n";
        if($purpose == 'initialize') $this->mint2EOLid = array();
        elseif($purpose == 'buildup ancestry and children') { $this->taxID_info = array(); $this->descendants = array(); }

        if($purpose == 'save children of genus and family') {
            $FILE = Functions::file_open($this->listOf_order_family_genus[$filter_rank], 'w'); //this file will be used DATA-1818
            fwrite($FILE, implode("\t", array('canonicalName', 'EOLid', 'taxonRank', 'taxonomicStatus'))."\n");
        }
        
        $i = 0; $found = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i)." ";
            // if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); //exit("\nstopx\n");
            /*Array(
                [taxonID] => EOL-000000285725
                [source] => COL:9aaa4a27dfd2a6bedfb6f58f737de541
                [furtherInformationURL] => http://www.catalogueoflife.org/col/details/species/id/9aaa4a27dfd2a6bedfb6f58f737de541
                [acceptedNameUsageID] => 
                [parentNameUsageID] => EOL-000000285680
                [scientificName] => Mandevilla foliosa (Müll. Arg.) Hemsl.
                [higherClassification] => Life|Cellular|Eukaryota|Archaeplastida|Chloroplastida|Streptophyta|Embryophytes|Tracheophyta|Spermatophytes|Angiosperms|Eudicots|Superasterids|Asterids|Gentianales|Apocynaceae|Mandevilla
                [taxonRank] => species
                [taxonomicStatus] => accepted
                [taxonRemarks] => 
                [datasetID] => COL-141
                [canonicalName] => Mandevilla foliosa
                [EOLid] => 6847986
                [EOLidAnnotations] => 
                [Landmark] => 
            )*/
            if($purpose == 'initialize') $this->mint2EOLid[$rec['taxonID']] = $rec['EOLid'];
            elseif($purpose == 'buildup ancestry and children') {
                if($parent_id = @$this->mint2EOLid[$rec['parentNameUsageID']]) {
                    $this->taxID_info[$rec['EOLid']] = array("pID" => $parent_id, 'r' => $rec['taxonRank'], 'n' => $rec['scientificName']); //used for ancesty and more
                    $this->descendants[$parent_id][$rec['EOLid']] = ''; //used for descendants (children)
                }
                else { // nothing to be done here. nature of the beast. Since not all EOL-000000000000 has an EOLid.
                    // print_r($rec);
                    // echo "\nInvestigate: this parentNameUsageID [".$rec['parentNameUsageID']."] [$parent_id] doesn't have an EOLid \n";
                }
            }
            elseif($purpose == 'save children of genus and family') {
                // if(in_array($rec['taxonRank'], array('order', 'family', 'genus'))) { //old scheme - abandoned
                if($rec['taxonRank'] == $filter_rank) {
                    if($eol_id = $rec['EOLid']) { $found++;
                        $json = self::get_children_from_json_cache($eol_id);
                        // $children = json_decode($json, true); // print_r($children); //debug only

                        // /* text file here will be used in generating map data for taxa with descendants/children (DATA-1818)
                        if($val = $rec['canonicalName']) $sciname = $val;
                        else                             $sciname = Functions::canonical_form($rec['scientificName']);
                        $save = array($sciname, $eol_id, $rec['taxonRank'], $rec['taxonomicStatus']);
                        fwrite($FILE, implode("\t", $save)."\n");
                        // */
                        
                        // if($found >= 5) break; //debug only
                    }
                }
            }
        }
        if($purpose == 'save children of genus and family') fclose($FILE);
    }
    function get_children_from_json_cache($name, $options = array(), $gen_descendants_ifNot_availableYN = true)
    {
        // download_wait_time
        if(!isset($options['expire_seconds'])) $options['expire_seconds'] = false;
        if(!isset($options['cache_path'])) $options['cache_path'] = $this->download_options['cache_path'];
        $md5 = md5($name);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$name"."_ch".".json";
        if(file_exists($cache_path)) {
            // echo "\nRetrieving cache ($name)...\n"; //good debug
            $file_contents = file_get_contents($cache_path);
            $cache_is_valid = true;
            if(($file_contents && $cache_is_valid) || (strval($file_contents) == "0" && $cache_is_valid)) {
                $file_age_in_seconds = time() - filemtime($cache_path);
                if($file_age_in_seconds < $options['expire_seconds']) return $file_contents;
                if($options['expire_seconds'] === false) return $file_contents;
            }
            @unlink($cache_path);
        }
        else echo "\nAlert: json not yet saved for this taxon ($name).\n"; //almost not seen, since all concerned taxa will have a json file. Even for those without children will have '[]' json value
        if($gen_descendants_ifNot_availableYN) {
            //generate json
            // echo "\nGenerating cache json for the first time ($name)...\n"; //good debug
            $children = self::get_descendants_of_taxID($name); // echo "\nchildren: "; print_r($children);
            $json = json_encode($children);
            if($json) {
                if($FILE = Functions::file_open($cache_path, 'w')) {
                    fwrite($FILE, $json);
                    fclose($FILE);
                }
            }
            return $json;
        }
    }
    function get_descendants_of_taxID($uid, $direct_descendants_only_YN = false, $this_descendants = array())
    {
        if(!isset($this->descendants)) $this->descendants = $this_descendants;
        $final = array();
        $descendants = array();
        if($val = @$this->descendants[$uid]) $descendants = array_keys($val);
        if($direct_descendants_only_YN) return $descendants;
        if($descendants) {
            foreach($descendants as $child) {
                $final[$child] = '';
                if($val = @$this->descendants[$child]) {
                    $descendants2 = array_keys($val);
                    foreach($descendants2 as $child2) {
                        $final[$child2] = '';
                        if($val = @$this->descendants[$child2]) {
                            $descendants3 = array_keys($val);
                            foreach($descendants3 as $child3) {
                                $final[$child3] = '';
                                if($val = @$this->descendants[$child3]) {
                                    $descendants4 = array_keys($val);
                                    foreach($descendants4 as $child4) {
                                        $final[$child4] = '';
                                        if($val = @$this->descendants[$child4]) {
                                            $descendants5 = array_keys($val);
                                            foreach($descendants5 as $child5) {
                                                $final[$child5] = '';
                                                if($val = @$this->descendants[$child5]) {
                                                    $descendants6 = array_keys($val);
                                                    foreach($descendants6 as $child6) {
                                                        $final[$child6] = '';
                                                        if($val = @$this->descendants[$child6]) {
                                                            $descendants7 = array_keys($val);
                                                            foreach($descendants7 as $child7) {
                                                                $final[$child7] = '';
                                                                if($val = @$this->descendants[$child7]) {
                                                                    $descendants8 = array_keys($val);
                                                                    foreach($descendants8 as $child8) {
                                                                        $final[$child8] = '';
                                                                        if($val = @$this->descendants[$child8]) {
                                                                            $descendants9 = array_keys($val);
                                                                            foreach($descendants9 as $child9) {
                                                                                $final[$child9] = '';
                                                                                if($val = @$this->descendants[$child9]) {
                                                                                    $descendants10 = array_keys($val);
                                                                                    foreach($descendants10 as $child10) {
                                                                                        $final[$child10] = '';
                                                                                        if($val = @$this->descendants[$child10]) {
                                                                                            $descendants11 = array_keys($val);
                                                                                            foreach($descendants11 as $child11) {
                                                                                                $final[$child11] = '';
                                                                                                if($val = @$this->descendants[$child11]) {
                                                                                                    $descendants12 = array_keys($val);
                                                                                                    foreach($descendants12 as $child12) {
                                                                                                        $final[$child12] = '';
                                                                                                        if($val = @$this->descendants[$child12]) {
                                                                                                            $descendants13 = array_keys($val);
                                                                                                            foreach($descendants13 as $child13) {
                                                                                                                $final[$child13] = '';
                                                                                                                if($val = @$this->descendants[$child13]) {
                                                                                                                    $descendants14 = array_keys($val);
                                                                                                                    foreach($descendants14 as $child14) {
                                                                                                                        $final[$child14] = '';
                                                                                                                        if($val = @$this->descendants[$child14]) {
                                                                                                                            $descendants15 = array_keys($val);
                                                                                                                            foreach($descendants15 as $child15) {
                                                                                                                                $final[$child15] = '';
                                                                                                                                if($val = @$this->descendants[$child15]) {
                                                                                                                                    $descendants16 = array_keys($val);
                                                                                                                                    foreach($descendants16 as $child16) {
                                                                                                                                        $final[$child16] = '';
                                                                                                                                        if($val = @$this->descendants[$child16]) {
                                                                                                                                            $descendants17 = array_keys($val);
                                                                                                                                            foreach($descendants17 as $child17) {
                                                                                                                                                $final[$child17] = '';

if($val = @$this->descendants[$child17]) {
    $descendants18 = array_keys($val);
    foreach($descendants18 as $child18) {
        $final[$child18] = '';
        if($val = @$this->descendants[$child18]) {
            $descendants19 = array_keys($val);
            foreach($descendants19 as $child19) {
                $final[$child19] = '';
                if($val = @$this->descendants[$child19]) {
                    $descendants20 = array_keys($val);
                    foreach($descendants20 as $child20) {
                        $final[$child20] = '';
                        if($val = @$this->descendants[$child20]) {
                            $descendants21 = array_keys($val);
                            foreach($descendants21 as $child21) {
                                $final[$child21] = '';
                                if($val = @$this->descendants[$child21]) {
                                    $descendants22 = array_keys($val);
                                    foreach($descendants22 as $child22) {
                                        $final[$child22] = '';
                                        if($val = @$this->descendants[$child22]) {
                                            $descendants23 = array_keys($val);
                                            foreach($descendants23 as $child23) {
                                                $final[$child23] = '';
                                                if($val = @$this->descendants[$child23]) {
                                                    $descendants24 = array_keys($val);
                                                    foreach($descendants24 as $child24) {
                                                        $final[$child24] = '';
                                                        if($val = @$this->descendants[$child24]) {
                                                            $descendants25 = array_keys($val);
                                                            foreach($descendants25 as $child25) {
                                                                $final[$child25] = '';
                                                                if($val = @$this->descendants[$child25]) {
                                                                    $descendants26 = array_keys($val);
                                                                    foreach($descendants26 as $child26) {
                                                                        $final[$child26] = '';
                                                                        if($val = @$this->descendants[$child26]) {
                                                                            $descendants27 = array_keys($val);
                                                                            foreach($descendants27 as $child27) {
                                                                                $final[$child27] = '';
                                                                                if($val = @$this->descendants[$child27]) {
                                                                                    $descendants28 = array_keys($val);
                                                                                    foreach($descendants28 as $child28) {
                                                                                        $final[$child28] = '';
                                                                                        if($val = @$this->descendants[$child28]) {
                                                                                            $descendants29 = array_keys($val);
                                                                                            foreach($descendants29 as $child29) {
                                                                                                $final[$child29] = '';
                                                                                                if($val = @$this->descendants[$child29]) {
                                                                                                    $descendants30 = array_keys($val);
                                                                                                    foreach($descendants30 as $child30) {
                                                                                                        $final[$child30] = '';
                                                                                                        exit("\nReached level 30, will need to extend.\n");
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
                                                                                                                                            }
                                                                                                                                        }
                                                                                                                                    }
                                                                                                                                }
                                                                                                                            }
                                                                                                                        }
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if($final) {
            $final = array_keys($final);
            $final = array_filter($final); //remove null arrays
            $final = array_unique($final); //make unique --- not actually needed here, but just put it anyway.
            $final = array_values($final); //reindex key
            return $final;
        }
        return array();
    }
    private function get_ancestry_of_taxID($tax_id)
    {
        $final = array();
        $final[] = $tax_id;
        while($parent_id = @$this->taxID_info[$tax_id]['pID']) {
            if(!in_array($parent_id, $final)) $final[] = $parent_id;
            else {
                if($parent_id == 1) return $final;
                else {
                    print_r($final);
                    exit("\nInvestigate $parent_id already in array.\n");
                }
            }
            $tax_id = $parent_id;
        }
        return $final;
    }
    /*========================================================================================Ends here. Below here is remnants from a copied template */ 
    /*
    private function write2txt_unclassified_parents()
    {
        $WRITE = fopen($this->main_path.'/taxonomy1.txt', "a");
        foreach($this->unclassified_parent as $sci => $rec) {
            // print_r($rec); exit;
            $rek = array();
            $rek[] = $rec['uid'];
            $rek[] = $rec['pID'];
            $rek[] = $rec['n'];
            $rek[] = ''; //$rec['r']; -- no longer 'no rank'
            $rek[] = '';
            $rek[] = '';
            fwrite($WRITE, implode("\t|\t", $rek)."\t|\t"."\n");
        }
        fclose($WRITE);
        unset($this->unclassified_parent);
    }
    */
}
?>