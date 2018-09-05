<?php
namespace php_active_record;
/* [SDR.php] */
class SummaryDataResourcesAPI
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        /*
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */
        $this->download_options = array('resource_id' => 'SDR', 'timeout' => 60*5, 'expire_seconds' => false, 'cache' => 1, 'download_wait_time' => 1000000);
        $this->debug = array();
        
        /* Terms relationships -> https://opendata.eol.org/dataset/terms-relationships */
        $this->file['parent child']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/f8036c30-f4ab-4796-8705-f3ccd20eb7e9/download/parent-child-aug-16-2.csv";
        // $this->file['parent child']['fields'] = array('parent_term_URI', 'subclass_term_URI');
        $this->file['parent child']['fields'] = array('parent', 'child'); //used more simple words
        
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-aug-16-1-2.csv";
        // $this->file['preferred synonym']['fields'] = array('preferred_term_URI', 'deprecated_term_URI');
        $this->file['preferred synonym']['fields'] = array('preferred', 'deprecated');

        $this->file['parent child']['path'] = "http://localhost/cp/summary data resources/parent-child-aug-16-2a.csv";
        $this->file['preferred synonym']['path'] = "http://localhost/cp/summary data resources/preferredsynonym-aug-16-1-2a.csv";
        
        $this->dwca_file = "http://localhost/cp/summary data resources/carnivora_sample.tgz";
        $this->report_file = CONTENT_RESOURCE_LOCAL_PATH . '/sample.txt';
        $this->temp_file = CONTENT_RESOURCE_LOCAL_PATH . '/temp.txt';
    }
    private function given_value_uri()
    {
        return array("http://www.marineregions.org/gazetteer.php?p=details&id=australia", "http://www.marineregions.org/gazetteer.php?p=details&id=4366", 
        "http://www.marineregions.org/gazetteer.php?p=details&id=4364", "http://www.geonames.org/2186224", "http://www.geonames.org/3370751", 
        "http://www.marineregions.org/gazetteer.php?p=details&id=1914", "http://www.marineregions.org/gazetteer.php?p=details&id=1904", 
        "http://www.marineregions.org/gazetteer.php?p=details&id=1910", "http://www.marineregions.org/gazetteer.php?p=details&id=4276", 
        "http://www.marineregions.org/gazetteer.php?p=details&id=4365", "http://www.geonames.org/953987");
    }
    private function get_ancestor_ranking_from_set_of_uris($uris)
    {   /*
Hi Jen, we are now just down to 1 discrepancy. But I think (hopefully) the last one is just something you've missed doing it manually.
But more importantly, let me share my algorithm how I chose the parent. Please review closely and suggest improvement or even revise if needed.
I came up with this using our case scenario for page_id 46559197 and your explanations why you chose your parents.
Like what I said it came down to now just 1 discrepancy.

I process each of the 12 terms, one by one.
    http://www.marineregions.org/gazetteer.php?p=details&id=australia
    http://www.marineregions.org/gazetteer.php?p=details&id=4366
    http://www.marineregions.org/gazetteer.php?p=details&id=4364
    http://www.geonames.org/2186224
    http://www.geonames.org/3370751
    http://www.marineregions.org/gazetteer.php?p=details&id=1914
    http://www.marineregions.org/gazetteer.php?p=details&id=1904
    http://www.marineregions.org/gazetteer.php?p=details&id=1910
    http://www.marineregions.org/gazetteer.php?p=details&id=4276
    http://www.marineregions.org/gazetteer.php?p=details&id=4365
    http://www.geonames.org/953987
    http://www.marineregions.org/mrgid/1914

        
1.  First I get the preferred term(s) of the term in question. 
    Case A: If there are any: e.g. (pref1, pref2) Mostly only 1 preferred term.
        I get the immediate parent(s) each of the preferred terms. 
        e.g. pref1_parent1, pref1_parent2, pref1_parent3
        

    Case B: If there are NO preferred term(s)
        I get the immediate parent(s) of the term in question.
        e.g. term_parent1, term_parent2

2.  Then whatever the Case be, I sent the collected items to the ranking selection.
    
        */
        $final = array();
        foreach($uris as $term) {
            if($preferred_terms = @$this->preferred_names_of[$term]) {
                // echo "\nThere are preferred term(s):\n";
                // print_r($preferred_terms);
                foreach($preferred_terms as $pterm) {
                    @$final_preferred[$pterm]++;
                    // @$final[$pterm]++;
                    // @$final[$pterm]++;
                    // @$final[$pterm]++;
                    // echo "\nparent(s) of $pterm:";
                    if($parents = @$this->parents_of[$pterm]) {
                        // print_r($parents);
                        foreach($parents as $parent) @$final[$parent]++;
                    }
                    // else echo " -- NO parent";
                }
            }
            else { //no preferred term
                if($parents = @$this->parents_of[$term]) {
                    foreach($parents as $parent) @$final[$parent]++;
                }
                else exit("\n\nHmmm no preferred and no immediate parent for term: [$term]\n\n");
            }
        }//end main
        /*
        foreach($uris as $term) {
            if($parents = @$this->parents_of[$term]) {
                foreach($parents as $parent) @$final[$parent]++;
            }
        }//end main
        */
        arsort($final);
        print_r($final);
        $final = array_keys($final);
        // print_r($final);
        $this->ancestor_ranking = $final;

        arsort($final_preferred);
        print_r($final_preferred);
        $final_preferred = array_keys($final_preferred);
        // print_r($final_preferred);
        $this->ancestor_ranking_preferred = $final_preferred;
        
    }
    private function get_rank_most_parent($parents, $preferred_terms = array())
    {
        if(!$preferred_terms) {
            //1st option: if any is a preferred name then choose that
            foreach($this->ancestor_ranking_preferred as $parent) {
                if(in_array($parent, $parents)) {
                    $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                    return $parent;
                }
            }
        }
        else {

            // don't do THIS if preferred + parents are all inside $this->ancestor_ranking
            $all_inside = true;
            $temp = array_merge($parents, $preferred_terms);
            foreach($temp as $id) {
                if(!in_array($id, $this->ancestor_ranking)) $all_inside = false;
            }
            
            if(!$all_inside) {
                //THIS:
                foreach($this->ancestor_ranking as $parent) {
                    if(in_array($parent, $preferred_terms)) {
                        $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                        return $parent;
                    }
                }
            }
            
            if(count($preferred_terms) == 1 && in_array($preferred_terms[0], $this->ancestor_ranking) 
                                            && in_array($preferred_terms[0], $this->ancestor_ranking_preferred))
            {
                // $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $preferred_terms[0]."\n"); fclose($WRITE);
                // exit("\nelix [".$preferred_terms[0]."]\n");
                // return $preferred_terms[0];
            }
            
            
            
        }
        
        //2nd option
        $inclusive = array_merge($parents, $preferred_terms);
        foreach($this->ancestor_ranking as $parent) {
            if(in_array($parent, $inclusive)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                return $parent;
            }
        }
        
        echo "\nInvestigate parents not included in ranking... weird...\n";
        print_r($inclusive);
        exit("\n===============\n");
    }
    function start()
    {
        self::working_dir();
        self::generate_terms_values_child_parent_list();
        self::generate_preferred_child_parent_list();
        $uris = self::given_value_uri();
        self::get_ancestor_ranking_from_set_of_uris($uris);

        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=australia";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4366";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4364";
        $terms[] = "http://www.geonames.org/2186224";
        $terms[] = "http://www.geonames.org/3370751";                               //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1914";  //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1904";  //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1910";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4276";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4365";
        $terms[] = "http://www.geonames.org/953987";
        $terms[] = "http://www.marineregions.org/mrgid/1914";

        $WRITE = fopen($this->temp_file, 'w'); fclose($WRITE);
        foreach($terms as $term) {
            self::get_parent_of_term($term);
        }
        
        exit("\nend 01\n");
    }
    private function get_parent_of_term($term)
    {
        echo "\n--------------------------------------------------------------------------------------------------------------------------------------- \n";
        echo "term in question: [$term]:\n";
        /*
        if($parents = @$this->parents_of[$term]) {
            echo "\nParents:\n"; print_r($parents);
        }
        else echo "\nNO PARENT\n";
        */
        
        if($preferred_terms = @$this->preferred_names_of[$term]) {
            echo "\nThere are preferred term(s):\n";
            print_r($preferred_terms);
            foreach($preferred_terms as $term) {
                echo "\nparent(s) of $term:\n";
                if($parents = @$this->parents_of[$term]) {
                    print_r($parents);
                    echo "\nCHOSEN PARENT: ".self::get_rank_most_parent($parents, $preferred_terms)."\n";
                    // foreach($parents as $parent) {
                    //     echo "\n[$parent]:\n";
                    //     print_r($this->children_of[$parent]);
                    // }
                }
                else echo " -- NO parent";
            }
            /* seems not needed
            foreach($preferred_terms as $term) {
                echo "\nprefered name of $term:";
                if($names = @$this->prefered_name_of[$term]) {
                    print_r($names);
                }
                else echo " -- NO preferred name";
            }
            */
        }
        else {
            echo "\nThere is NO preferred term\n";
            if($immediate_parents = $this->parents_of[$term]) {
                echo "\nThere are immediate parent(s) for term in question:\n";
                print_r($immediate_parents);
                echo "\nCHOSEN PARENT*: ".self::get_rank_most_parent($immediate_parents)."\n";
                // foreach($immediate_parents as $immediate) {
                //     echo "\nparent(s) of $immediate:";
                //     if($parents = @$this->parents_of[$immediate]) {
                //         print_r($parents);
                //     }
                //     else echo " -- NO parent";
                // }
            }
        }
    }
    private function generate_preferred_child_parent_list()
    {
        $temp_file = Functions::save_remote_file_to_local($this->file['preferred synonym']['path'], $this->download_options);
        $file = fopen($temp_file, 'r'); $i = 0;
        $fields = $this->file['preferred synonym']['fields'];
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($line) {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [preferred] => http://marineregions.org/mrgid/19161
                    [deprecated] => http://marineregions.org/gazetteer.php?p=details&id=19161
                )*/
                $this->preferred_names_of[$rec['deprecated']][] = $rec['preferred'];
            }
        }
        fclose($file); unlink($temp_file);
    }
    private function get_ancestry_of_term($page_id)
    {
        $final = array(); $final2 = array();
        if($parent_ids = @$this->terms_values_child_parent_list[$page_id]) {
            foreach($parent_ids as $temp_id) {
                while(true) {
                    if($parent_ids2 = @$this->terms_values_child_parent_list[$temp_id]) {
                        foreach($parent_ids2 as $temp_id2) {
                            while(true) {
                                if($parent_ids3 = @$this->terms_values_child_parent_list[$temp_id2]) {
                                    foreach($parent_ids3 as $temp_id3) {
                                        $final['L3'][] = $temp_id3;
                                        $final2[$temp_id3] = '';
                                        $temp_id2 = $temp_id3;
                                    }
                                }
                                else break;
                            }
                            $final['L2'][] = $temp_id2;
                            $final2[$temp_id2] = '';
                            $temp_id = $temp_id2;
                        }
                    }
                    else break;
                }
                $final['L1'][] = $temp_id;
                $final2[$temp_id] = '';
                $page_id = $temp_id;
            }
        }
        return array($final, array_keys($final2));
        /*
        $final = array();
        $temp_id = $page_id;
        while(true) {
            if($parent_id = @$this->terms_values_child_parent_list[$temp_id]) {
                $final[] = $parent_id;
                $temp_id = $parent_id;
            }
            else break;
        }
        return $final;
        */
    }
    private function generate_terms_values_child_parent_list()
    {
        $temp_file = Functions::save_remote_file_to_local($this->file['parent child']['path'], $this->download_options);
        $file = fopen($temp_file, 'r');
        $i = 0;
        $fields = $this->file['parent child']['fields'];
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($line) {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [parent] => ﻿http://purl.obolibrary.org/obo/ENVO_00000111
                    [child] => http://purl.obolibrary.org/obo/ENVO_01000196
                )*/
                $this->parents_of[$rec['child']][] = $rec['parent'];
                $this->children_of[$rec['parent']][] = $rec['child'];
            }
        }
        fclose($file); unlink($temp_file);
    }
    
    function start_v1()
    {
        self::working_dir();
        $this->child_parent_list = self::generate_child_parent_list();
        // /* tests...
        $predicate = "http://reeffish.org/occursIn";
        $predicate = "http://eol.org/schema/terms/Present";
        $similar_terms = self::given_predicate_get_similar_terms($predicate);
        // print_r($similar_terms); exit;
        
        self::print_taxon_and_ancestry($similar_terms);
        
        self::given_predicates_get_values_from_traits_csv($similar_terms);

        exit("\n-end tests-\n");
        // */
        if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
        // remove temp dir
        /* un-comment in real operation
        recursive_rmdir($this->main_paths['temp_dir']);
        echo ("\n temporary directory removed: " . $this->main_paths['temp_dir']);
        */
    }
    private function generate_child_parent_list()
    {
        $file = fopen($this->main_paths['archive_path'].'/parents.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [child] => 47054812
                    [parent] => 7662
                )*/
                $final[$rec['child']] = $rec['parent'];
            }
        }
        fclose($file);
        return $final;
    }
    private function print_taxon_and_ancestry($preds)
    {
        $WRITE = fopen($this->report_file, 'a');
        fwrite($WRITE, "Taxa (with ancestry) having data for predicate in question and similar terms: \n\n");
        fwrite($WRITE, implode("\t", array("page_id", "scientific_name", "ancestry"))."\n");
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    [scientific_name] => <i>Tremarctos ornatus</i>
                    [resource_pk] => M_00329828
                    [predicate] => http://eol.org/schema/terms/Present
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://www.worldwildlife.org/publications/wildfinder-database
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [literal] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [measurement] => 
                    [units] => 
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [resource_id] => 20
                )*/
                if(in_array($rec['predicate'], $preds)) {
                    // echo "\n".self::get_value($rec);
                    // print_r($rec); //exit;
                    $ancestry = self::get_ancestry_using_page_id($rec['page_id']);
                    // print_r($ancestry);
                    if(!isset($printed_already[$rec['page_id']])) {
                        fwrite($WRITE, implode("\t", array($rec['page_id'], $rec['scientific_name'], implode("|", $ancestry)))."\n");
                        $printed_already[$rec['page_id']] = '';
                    }
                }
            }
        }
        fclose($file);
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fclose($WRITE);
        // exit("\nelix 100\n");
    }

    private function given_predicates_get_values_from_traits_csv($preds)
    {
        $WRITE = fopen($this->report_file, 'a');
        fwrite($WRITE, "Records from traits.csv having data for predicate in question and similar terms: \n\n");
        fwrite($WRITE, implode("\t", array("page_id", "scientific_name", "predicate", "value_uri OR literal"))."\n");
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) {
                $fields = $line;
                print_r($fields); //exit;
            }
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    [scientific_name] => <i>Tremarctos ornatus</i>
                    [resource_pk] => M_00329828
                    [predicate] => http://eol.org/schema/terms/Present
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://www.worldwildlife.org/publications/wildfinder-database
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [literal] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [measurement] => 
                    [units] => 
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [resource_id] => 20
                )*/
                if(in_array($rec['predicate'], $preds)) {
                    // echo "\n".self::get_value($rec);
                    // print_r($rec); //exit;
                    
                    fwrite($WRITE, implode("\t", array($rec['page_id'], $rec['scientific_name'], $rec['predicate'], self::get_value($rec)))."\n");
                }
            }
        }
        fclose($file);
    }
    private function get_ancestry_using_page_id($page_id)
    {
        $final = array();
        $temp_id = $page_id;
        while(true) {
            if($parent_id = @$this->child_parent_list[$temp_id]) {
                $final[] = $parent_id;
                $temp_id = $parent_id;
            }
            else break;
        }
        return $final;
    }
    private function get_value($rec)
    {
        if($val = @$rec['value_uri']) return $val;
        if($val = @$rec['literal']) return $val;
    }
    private function setup_working_dir()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "traits.csv", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        return $paths;
    }
    private function working_dir()
    {
        if(Functions::is_production()) {
            if(!($info = self::setup_working_dir())) return;
            $this->main_paths = $info;
        }
        else { //local development only
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/carnivora_sample',
                          'temp_dir'     => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/');
            $this->main_paths = $info;
        }
    }
    private function given_predicate_get_similar_terms($pred)
    {
        $final = array();
        $final[$pred] = ''; //processed predicate is included
        
        //from 'parent child':
        $temp_file = Functions::save_remote_file_to_local($this->file['parent child'], $this->download_options);
        $file = fopen($temp_file, 'r');
        while(($line = fgetcsv($file)) !== FALSE) {
          if($line[0] == $pred) $final[$line[1]] = '';
        }
        fclose($file); unlink($temp_file);
        
        //from 'preferred synonym':
        $temp_file = Functions::save_remote_file_to_local($this->file['preferred synonym'], $this->download_options);
        $file = fopen($temp_file, 'r');
        while(($line = fgetcsv($file)) !== FALSE) {
          if($line[1] == $pred) $final[$line[0]] = '';
        }
        fclose($file); unlink($temp_file);
        $final = array_keys($final);
        
        //start write
        $WRITE = fopen($this->report_file, 'w');
        fwrite($WRITE, "REPORT FOR PREDICATE: $pred\n\n");
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fwrite($WRITE, "Similar terms from [terms relationship files]:\n\n");
        foreach($final as $url) fwrite($WRITE, $url . "\n");
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fclose($WRITE);
        //end write
        
        return $final;
    }
    
    /*
    function start()
    {
        self::parse_references();           //exit("\nstop references\n");
        self::parse_classification();    //exit("\nstop classification\n");
        self::parse_images();            //exit("\nstop images\n");
        $this->archive_builder->finalize(TRUE);
        if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function parse_classification()
    {
        if($html = Functions::lookup_with_cache($this->main_text_ver2, $this->download_options)) {
            if(preg_match("/<h2 class=\"block-title\">CephBase Classification<\/h2>(.*?)<div class=\"region-inner region-content-inner\">/ims", $html, $arr)) {
                // <a href="http://cephbase.eol.org/taxonomy/term/438" class=""><em>Sepiadarium</em> <em>austrinum</em></a>
                if(preg_match_all("/<a href=\"http\:\/\/cephbase.eol.org\/taxonomy\/term\/(.*?)<\/a>/ims", $arr[1], $arr2)) {
                    // print_r($arr2[1]); exit;
                    // echo "\n".count($arr2[1])."\n";
                    //[1620] => 280" class=""><em>Nautilus</em> <em>pompilius</em> <em>pompilius</em>
                    foreach($arr2[1] as $str) {
                        $str = Functions::remove_whitespace(strip_tags($str));
                        if(preg_match("/xxx(.*?)\"/ims", "xxx".$str, $arr)) $id = $arr[1];
                        if(preg_match("/>(.*?)xxx/ims", $str."xxx", $arr)) $sciname = $arr[1];
                        $rec[$id] = $sciname;
                    }
                    echo "\n count 2: ".count($rec)."\n";
                }
            }
        }
        // print_r($rec); exit;
        $total = count($rec); $i = 0;
        foreach($rec as $taxon_id => $sciname) { $i++;
            // $taxon_id = 466; //debug - accepted
            // $taxon_id = 1228; //debug - not accepted
            // $taxon_id = 326; //multiple text object - associations
            echo "\n$i of $total: [$sciname] [$taxon_id]";
            $taxon = self::parse_taxon_info($taxon_id);
            self::write_taxon($taxon);
            self::write_text_object($taxon);
            // if($i >= 10) break; //debug only
            // break; //debug only - one record to process...
        }
    }
    private function write_text_object($rec)
    {
        if($rec['rank'] == "species" || $rec['rank'] == "subspecies") {
            if($output = self::parse_text_object($rec['taxon_id'])) {
                $data = $output['data'];
                // print_r($data);
                foreach($data as $association => $info) {
                    $write = array();
                    $write['taxon_id'] = $rec['taxon_id'];
                    $write['agent'] = @$output['author'];
                    // echo "\n[$association]\n------------\n";
                    $write['text'] = "$association: ".implode("<br>", $info['items']);
                    foreach($info['refs_final'] as $ref) {
                        $ref_no = $ref['ref_no'];
                        $write['ref_ids'][] = $ref_no;
                        $r = new \eol_schema\Reference();
                        $r->identifier      = $ref_no;
                        $r->full_reference  = $ref['full_ref'];
                        $r->uri             = $this->page['reference_page'].$ref_no;
                        // $r->publicationType = @$ref['details']['Publication Type:'];
                        // $r->pages           = @$ref['details']['Pagination:'];
                        // $r->volume          = @$ref['details']['Volume:'];
                        // $r->authorList      = @$ref['details']['Authors:'];
                        if(!isset($this->reference_ids[$ref_no])) {
                            $this->reference_ids[$ref_no] = '';
                            $this->archive_builder->write_object_to_file($r);
                        }
                    }
                    if($write['taxon_id'] && $write['text']) self::write_text_2archive($write);
                }
            }
        }
    }
    private function write_text_2archive($write)
    {   
        // print_r($write); exit;
        $mr = new \eol_schema\MediaResource();
        $taxonID = $write['taxon_id'];
        $mr->taxonID        = $taxonID;
        $mr->identifier     = md5($taxonID.$write['text']);
        $mr->type           = "http://purl.org/dc/dcmitype/Text";
        $mr->format         = "text/html";
        $mr->language       = 'en';
        $mr->furtherInformationURL = str_replace('taxon_id', $taxonID, $this->page['text_object_page']);
        $mr->CVterm         = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations";
        // $mr->Owner          = '';
        // $mr->rights         = '';
        // $mr->title          = '';
        $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $mr->description    = $write['text'];
        if($reference_ids = @$write['ref_ids'])  $mr->referenceID = implode("; ", $reference_ids);
        
        if($agent = @$write['agent']) {
            if($agent_ids = self::create_agent($agent['name'], $agent['homepage'], "author")) $mr->agentID = implode("; ", $agent_ids);
        }
        
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
        
    }
    private function parse_text_object($taxon_id)
    {
        $final = array();
        $url = str_replace('taxon_id', $taxon_id, $this->page['text_object_page']);
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match("/<div class=\"field-label\">Associations:&nbsp;<\/div>(.*?)<footer/ims", $html, $arr)) {
                $str = $arr[1];
                if(preg_match_all("/<h4>(.*?)<\/h4>/ims", $str, $arr)) {
                    // print_r($arr[1]);
                    $assocs = $arr[1];
                    foreach($assocs as $assoc) {
                        // echo "\n[$assoc]:";
                        if(preg_match("/<h4>$assoc<\/h4>(.*?)<\/ul>/ims", $str, $arr)) {
                            $final[$assoc]['items'] = $arr[1];
                            // print_r($arr[1]);
                        }
                    }
                }
                
                $i = 0;
                if(preg_match_all("/<h5>References<\/h5>(.*?)<\/ul>/ims", $str, $arr)) {
                    foreach($arr[1] as $ref) {
                        $final[$assocs[$i]]['refs'] = $ref;
                        $i++;
                    }
                }
            }
        }
        // print_r($final);
        // massage $final
        if($final) {
            foreach($final as $key => $value) {
                // print_r($value);
                $fields = array('items', 'refs');
                foreach($fields as $field) {
                    $str = $value[$field];
                    // echo "\n[$key][$field]:";
                    if(preg_match_all("/<li>(.*?)<\/li>/ims", $str, $arr)) $final2[$key][$field] = $arr[1];
                    // echo "\n$str \n ========================================== \n";
                }
            }
            // print_r($final2); exit;
            
            //further massaging:
            foreach($final2 as $key => $value) {
                if($refs = $final2[$key]['refs']) $final2[$key]['refs_final'] = self::adjust_refs($refs);
            }
            
            $output['author'] = self::get_text_author($html);
            $output['data'] = $final2;
            return $output; //final output
        }
    }
    private function get_text_author($html)
    {
        $agent = array();
        if(preg_match("/<footer class=\"submitted\">(.*?)<\/footer>/ims", $html, $arr)) {
            // echo "\n".$arr[1]."\n";
            if(preg_match("/<a href=\"\/user\/(.*?)\"/ims", $arr[1], $arr2)) {
                $agent['homepage'] = "http://cephbase.eol.org/user/".$arr2[1];
            }
            if(preg_match("/<a(.*?)<\/a>/ims", $arr[1], $arr2)) {
                $agent['name'] = strip_tags("<a".$arr2[1]);
            }
            // print_r($agent);
        }
        return $agent;
    }
    private function adjust_refs($refs)
    {
        $final = array();
        foreach($refs as $str) {
            $rec = array();
            // href="/node/108">
            if(preg_match("/href=\"\/node\/(.*?)\"/ims", $str, $arr)) $rec['ref_no'] = $arr[1];
            $rec['full_ref'] = strip_tags($str);
            $final[] = $rec;
        }
        return $final;
    }
    private function write_taxon($rec)
    {   
        // print_r($rec); exit;
        $taxon_id = $rec['taxon_id'];
        $this->taxon_scinames[$rec['canonical']] = $taxon_id; //used in media extension
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $taxon_id;
        $taxon->scientificName      = $rec['canonical'];
        $taxon->scientificNameAuthorship = $rec['authorship'];
        $taxon->taxonRank           = $rec['rank'];
        if($val = @$rec['usage']['Unacceptability Reason']) $taxon->taxonomicStatus = $val;
        else                                                $taxon->taxonomicStatus = 'accepted';
        
        $ranks = array("kingdom", "phylum", "class", "order", "family", "genus");
        if($val = @$rec['ancestry']) {
            foreach($val as $a) {
                if(in_array($a['rank'], $ranks)) $taxon->$a['rank'] = $a['sciname'];
            }
        }
        
        if($arr = @$this->taxon_refs[$taxon_id]) {
            if($reference_ids = array_keys($arr)) $taxon->referenceID = implode("; ", $reference_ids);
        }
        
        $taxon->furtherInformationURL = $this->page['taxon_page'].$taxon_id;
        
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function write_image($m)
    {   
        $mr = new \eol_schema\MediaResource();
        
        if(!@$m['sciname']) {
            // print_r($m);
            $m['sciname'] = "Cephalopoda";
            $taxonID = 8;
        }
        
        $taxonID = '';
        if(isset($this->taxon_scinames[$m['sciname']])) $taxonID = $this->taxon_scinames[$m['sciname']];
        else {
            $this->debug['undefined sciname'][$m['sciname']] = '';
        }
        
        $mr->taxonID        = $taxonID;
        $mr->identifier     = pathinfo($m['media_url'], PATHINFO_BASENAME);
        $mr->format         = Functions::get_mimetype($m['media_url']);
        $mr->type           = Functions::get_datatype_given_mimetype($mr->format);
        $mr->language       = 'en';
        $mr->furtherInformationURL = $m['source_url'];
        $mr->accessURI      = $m['media_url'];
        // $mr->CVterm         = $o['subject'];
        $mr->Owner          = @$m['creator'];
        // $mr->rights         = $o['dc_rights'];
        // $mr->title          = $o['dc_title'];
        $mr->UsageTerms     = $m['license'];
        $mr->description    = self::concatenate_desc($m);
        // $mr->LocationCreated = $o['location'];
        // $mr->bibliographicCitation = $o['dcterms_bibliographicCitation'];
        // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids = self::create_agent(@$m['creator'])) $mr->agentID = implode("; ", $agent_ids);
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
        // print_r($mr); exit;
    }
    private function concatenate_desc($m)
    {
        $final = @$m['description'];
        if($val = @$m['imaging technique']) $final .= " Imaging technique: $val";
    }
    private function create_agent($creator_name, $home_page = "", $role = "")
    {
        if(!$creator_name) return false;
        $r = new \eol_schema\Agent();
        $r->term_name       = $creator_name;
        if($role) $r->agentRole = $role;
        else      $r->agentRole = 'creator';
        $r->identifier = md5("$r->term_name|$r->agentRole");
        if($home_page) $r->term_homepage = $home_page;
        $agent_ids[] = $r->identifier;
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }
    private function parse_image_info($url)
    {
        // $url = "http://cephbase.eol.org/file-colorboxed/24"; //debug only
        $final = array();
        $final['source_url'] = $url;
        // <div class="field field-name-field-taxonomic-name field-type-taxonomy-term-reference field-label-above">
        // <div class="field field-name-field-description field-type-text-long field-label-none">
        // <div class="field field-name-field-imaging-technique field-type-taxonomy-term-reference field-label-above">
        // <div class="field field-name-field-cc-licence field-type-creative-commons field-label-above">
        // <div class="field field-name-field-creator field-type-text field-label-above">
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            // if(preg_match("/<div class=\"field field-name-field-taxonomic-name field-type-taxonomy-term-reference field-label-above\">(.*?)<div class=\"field field-name-field/ims", $html, $arr)) {
            if(preg_match("/<div class=\"field field-name-field-taxonomic-name field-type-taxonomy-term-reference field-label-above\">(.*?)Download the original/ims", $html, $arr)) {
                $str = $arr[1];
                if(preg_match("/<div class=\"field-item even\">(.*?)<\/div>/ims", $str, $arr)) {
                    $str = trim($arr[1]);
                    $final['sciname'] = $str;
                }
            }
            if(preg_match("/<div class=\"field field-name-field-description field-type-text-long field-label-none\">(.*?)Download the original/ims", $html, $arr)) {
                $str = $arr[1];
                if(preg_match("/<div class=\"field-item even\">(.*?)<\/div>/ims", $str, $arr)) {
                    $str = trim($arr[1]);
                    $final['description'] = $str;
                }
            }
            if(preg_match("/<div class=\"field field-name-field-imaging-technique field-type-taxonomy-term-reference field-label-above\">(.*?)Download the original/ims", $html, $arr)) {
                $str = $arr[1];
                if(preg_match("/<div class=\"field-item even\">(.*?)<\/div>/ims", $str, $arr)) {
                    $str = trim($arr[1]);
                    $final['imaging technique'] = $str;
                }
            }
            if(preg_match("/<div class=\"field field-name-field-cc-licence field-type-creative-commons field-label-above\">(.*?)Download the original/ims", $html, $arr)) {
                $str = $arr[1];
                if(preg_match("/<div class=\"field-item even\">(.*?)<\/div>/ims", $str, $arr)) {
                    $str = trim($arr[1]);
                    if(preg_match("/href=\"(.*?)\"/ims", $str, $arr)) {
                        $license = $arr[1];
                        if(substr($license,0,2) == "//") $final['license'] = "http:".$license;
                        else                             $final['license'] = $license;
                    }
                    else $final['license'] = $str;
                }
                if($final['license'] == "All rights reserved.") $final['license'] = "all rights reserved";
                // $final['license'] = "http://creativecommons.org/licenses/by-nc-sa/3.0/"; //debug force
            }
            if(preg_match("/<div class=\"field field-name-field-creator field-type-text field-label-above\">(.*?)Download the original/ims", $html, $arr)) {
                $str = $arr[1];
                if(preg_match("/<div class=\"field-item even\">(.*?)<\/div>/ims", $str, $arr)) {
                    $str = trim($arr[1]);
                    $final['creator'] = $str;
                }
            }
            //<h2 class="element-invisible"><a href="http://cephbase.eol.org/sites/cephbase.eol.org/files/cb0001.jpg">cb0001.jpg</a></h2>
            if(preg_match("/<h2 class=\"element-invisible\">(.*?)<\/h2>/ims", $html, $arr)) {
                if(preg_match("/href=\"(.*?)\"/ims", $arr[1], $arr2)) $final['media_url'] = $arr2[1];
            }
        }
        // print_r($final); exit;
        return $final;
    }
    private function get_last_page_for_image($html, $type = 'image')
    {   //<a title="Go to last page" href="/gallery?page=29&amp;f[0]=tid%3A1">last »</a>
        if($type == 'image') {
            if(preg_match("/<a title=\"Go to last page\" href=\"\/gallery\?page\=(.*?)&amp;/ims", $html, $arr)) return $arr[1];
        }
        elseif($type == 'reference') {
            if(preg_match("/<a title=\"Go to last page\" href=\"\/biblio\?page\=(.*?)&amp;/ims", $html, $arr)) return $arr[1];
        }
        return 0;
    }
    */
}
?>