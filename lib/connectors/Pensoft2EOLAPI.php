<?php
namespace php_active_record;
/* connector: [environments_2_eol.php] 

This is for Pensoft annotator.
While an old, close to obsolete version (Environments2EOLAPI.php) is for Vangelis tagger.

Below is just for reference how to access OpenData resource: e.g. Amphibia Web text
https://opendata.eol.org/api/3/action/resource_search?query=name:AmphibiaWeb%20text
https://opendata.eol.org/api/3/action/resource_show?id=639efbfb-3b79-49e7-894f-50df4fa25da8
*/
class Pensoft2EOLAPI
{
    function __construct($param)
    {
        $this->param = $param; // print_r($param); exit;
        if($param['resource_id'] == '617_ENV') $this->modulo = 10000; //50000; //Wikipedia EN
        else                                   $this->modulo = 1000;
        /*-----------------------Resources-------------------*/
        // $this->DwCA_URLs['AmphibiaWeb text'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/21.tar.gz';
        /*-----------------------Subjects-------------------*/
        $this->subjects['Distribution'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution';
        $this->subjects['Description'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description';
        $this->subjects['TaxonBiology'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology';
        /* Wikipedia EN
        http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description:  389994
        http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology: 382437
        */
        /*-----------------------Paths----------------------*/
        if(Functions::is_production()) $this->root_path = '/html/Pensoft_annotator/';
        else                           $this->root_path = '/Library/WebServer/Documents/Pensoft_annotator/';
        
        if($this->param['resource_id'] == '617_ENV') {} //Wikipedia EN
        else { //rest of the resources
            $tmp = str_replace('_ENV', '', $param['resource_id']);
            $this->root_path .= $tmp.'/';
            if(!is_dir($this->root_path)) mkdir($this->root_path);
            // exit($this->root_path);
        }
        
        /*
        $this->eol_tagger_path      = $this->root_path.'eol_tagger/';
        $this->text_data_path       = $this->root_path.'test_text_data/';
        $this->eol_scripts_path     = $this->root_path.'eol_scripts/';
        */
        $this->eol_tags_path        = $this->root_path.'eol_tags/';
        $this->eol_tags_destination = $this->eol_tags_path.'eol_tags.tsv';
        $this->json_temp_path['metadata'] = $this->root_path.'temp_json/';
        $this->json_temp_path['partial'] = $this->root_path.'json_partial/'; //for partial, every 2000 chars long
        
        if(!is_dir($this->json_temp_path['metadata'])) mkdir($this->json_temp_path['metadata']);
        if(!is_dir($this->json_temp_path['partial'])) mkdir($this->json_temp_path['partial']);
        if(!is_dir($this->eol_tags_path)) mkdir($this->eol_tags_path);
        
        /*-----------------------Others---------------------*/
        $this->num_of_saved_recs_bef_run_tagger = 1000; //1000 orig;
        if($val = @$param['subjects']) $this->allowed_subjects = self::get_allowed_subjects($val); // print_r($this->allowed_subjects); exit;
        
        $this->download_options = array('expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);
        $this->call['opendata resource via name'] = "https://opendata.eol.org/api/3/action/resource_search?query=name:RESOURCE_NAME";
        $this->entities_file = 'https://github.com/eliagbayani/vangelis_tagger/raw/master/eol_tagger/for_entities.txt';
    }
    function generate_eol_tags_pensoft($resource)
    {   ///* customize
        if($this->param['resource_id'] == '21_ENV') { //AmphibiaWeb text
            $this->descendants_of_saline_water = self::get_descendants_of_saline_water(); //saline water. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65409&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65409
        }
        //*/
        
        self::lookup_opendata_resource();
        // /* un-comment in real operation
        self::initialize_files();
        // */
        $info = self::parse_dwca($resource); // print_r($info); exit;
        $tables = $info['harvester']->tables;
        print_r(array_keys($tables)); //exit;

        // /* this is used to apply all the remaps, deletions, adjustments:
        self::init_DATA_1841_terms_remapped();
        self::initialize_mRemark_assignments();
        self::initialize_delete_mRemarks();
        self::initialize_delete_uris();
        // */

        // /* un-comment in real operation
        self::process_table($tables['http://eol.org/schema/media/document'][0]); //generates individual text files & runs environment tagger
        // exit("\nDebug early exit...\n"); //if u want to investigate the individual text files.
        // print_r($this->debug);
        
        /* report for Jen - 'difference' report
        self::generate_difference_report(); exit("\n-end report-\n");
        */
        self::noParentTerms_less_entities_file(); //exit("\nstop muna 1\n");
        
        // */
        /* ----- stat 2nd part ----- */
        $obj_identifiers = self::get_unique_obj_identifiers(); // get unique IDs from noParentTerms
        $agent_ids = self::save_media_metadata_for_these_objects($obj_identifiers, $tables['http://eol.org/schema/media/document'][0]);
        if($val = @$tables['http://eol.org/schema/agent/agent']) self::save_agent_metadata_for_these_agents($agent_ids, $val[0]);
        // /* un-comment in real operation
        recursive_rmdir($info['temp_dir']); //remove temp folder used for DwCA parsing
        // */
        /* ----- stat 3rd part ----- */ //adjust DwCA in question. Either add MoF or update MoF.
        $dwca_file = $this->DwCA_URLs[$resource];
        require_library('connectors/DwCA_Utility');
        $func = new DwCA_Utility($this->param['resource_id'], $dwca_file);
        $preferred_rowtypes = array();
        /* These 2 will be processed in Environments2EOLfinal.php which will be called from DwCA_Utility.php
        http://rs.tdwg.org/dwc/terms/occurrence
        http://rs.tdwg.org/dwc/terms/measurementorfact
        */
        $preferred_rowtypes = false; //means process all rowtypes, except what's in $excluded_rowtypes
        // $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/occurrence', 'http://rs.tdwg.org/dwc/terms/measurementorfact'); //not used
        
        $excluded_rowtypes = array();
        // /* start customize
        if($this->param['resource_id'] == '617_ENV') $excluded_rowtypes = array('http://eol.org/schema/media/document'); //Wikipedia EN -> creates a new DwCA
        if($this->param['resource_id'] == '21_ENV') $excluded_rowtypes = array(); //AmphibiaWeb text -> doesn't create a new DwCA
        // */
        $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
        Functions::finalize_dwca_resource($this->param['resource_id'], false, true);
        // exit("\nstop muna - used in debugging\n");

        /* 4th part */
        if(is_dir($this->json_temp_path['metadata'])) {
            recursive_rmdir($this->json_temp_path['metadata']);
            mkdir($this->json_temp_path['metadata']);
        }
    }
    private function generate_difference_report()
    {
        // print_r($this->all_envo_terms); exit;
        $old = $this->all_envo_terms;
        print_r($old);
        $this->all_envo_terms = array_keys($this->all_envo_terms);
        // print_r($this->all_envo_terms); //exit;
        foreach($this->all_envo_terms as $t) $pensoft_envo_terms[] = pathinfo($t, PATHINFO_BASENAME);
        $envo_from_entities = self::get_envo_from_entities_file();
        // print_r($envo_from_entities); exit;
        $difference = array_diff($pensoft_envo_terms, $envo_from_entities);
        echo "\n pensoft_envo_terms: ".count($pensoft_envo_terms);
        echo "\n envo_from_entities: ".count($envo_from_entities);
        echo "\n difference: ".count($difference)."\n";
        $difference = array_values($difference); //reindex key
        // print_r($difference);
        /* $old e.g. Array(
            [http://purl.obolibrary.org/obo/ENVO_01000739] => habitat
            [http://purl.obolibrary.org/obo/ENVO_01001023] => radiation
            [http://purl.obolibrary.org/obo/ENVO_00002164] => fossil
        */
        $i = 0;
        foreach($difference as $term) { $i++;
            $uri = 'http://purl.obolibrary.org/obo/'.$term;
            echo "\n[$i] $uri -> ".$old[$uri];
        }
        exit("\n-end difference report-\n");
    }
    private function get_envo_from_entities_file()
    {
        $local = Functions::save_remote_file_to_local($this->entities_file, array('cache' => 1, 'expire_seconds' => 60)); //60*60*24
        foreach(new FileIterator($local) as $line => $row) {
            if(!$row) continue;
            $tmp = explode("\t", $row);
            // print_r($tmp); //exit;
            /*Array(
                [0] => 1009000003
                [1] => -27
                [2] => ENVO:01000057
            )*/
            $final[str_replace('ENVO:', 'ENVO_', $tmp[2])] = '';
        }
        unlink($local);
        // print_r($final); exit;
        $final = array_keys($final);
        echo "\nentities count 1: ".count($final);
        $filter_out = self::filter_out_from_entities();
        $final = array_diff($final, $filter_out);
        echo "\nentities count 2: ".count($final);
        return $final;
    }
    private function initialize_files()
    {
        // /* copied template, not needed in Pensoft yet
        /* OBSOLETE
        $files = array($this->eol_tags_destination, $this->eol_tags_path.'eol_tags_noParentTerms.tsv');     //Vangelis tagger
        */
        $files = array($this->eol_tags_path.'eol_tags_noParentTerms.tsv');                                  //Pensoft annotator
        foreach($files as $file) {
            if($f = Functions::file_open($file, "w")) {
                fclose($f);
                echo "\nFile truncated: [$file]\n";
            }
        }
        // */
        if(is_dir($this->json_temp_path['metadata'])) {
            recursive_rmdir($this->json_temp_path['metadata']);
            mkdir($this->json_temp_path['metadata']);
        }
        else mkdir($this->json_temp_path['metadata']);
    }
    private function parse_dwca($resource, $download_options = array('timeout' => 172800, 'expire_seconds' => 60*60*24*30))
    {   
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->DwCA_URLs[$resource], "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        print_r($paths); //exit("\n-exit muna-\n");
        // */
        /* development only
        $paths = Array("archive_path" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_04626/",
                       "temp_dir" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_04626/");
        */
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    private function process_table($meta) //generates individual text files & runs environment tagger
    {   //print_r($meta);
        echo "\nprocess media tab...\n";
        echo "\nRun Pensoft annotator...\n";
        $i = 0; $saved = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % $this->modulo) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\n[1]\n");

            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            // if($taxonID != 'Q1000262') continue; //debug only
            
            /* debug only
            // if($i >= 1 && $i <= 400000) {}
            if($i >= 590000 && $i <= 600000) {}
            else continue; 
            */
            
            if(self::valid_record($rec)) {
                $this->debug['subjects'][$rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm']] = '';
                // $this->debug['titles'][$rec['http://purl.org/dc/terms/title']] = ''; //debug only
                $saved++;
                $this->results = array();
                // $this->eli = array(); //good debug
                self::save_article_2_txtfile($rec);
                // exit("\nstop muna\n");
            }
            // if($i >= 10) break; //debug only
        }
    }
    private function save_article_2_txtfile($rec)
    {   /* Array(
        [http://purl.org/dc/terms/identifier] => 8687_distribution
        [http://rs.tdwg.org/dwc/terms/taxonID] => 8687
        [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
        [http://purl.org/dc/terms/format] => text/plain
        [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
        [http://purl.org/dc/terms/title] => Distribution and Habitat
        [http://purl.org/dc/terms/description] => <p><i>Abavorana nazgul</i> is only known from the mountain, Gunung Jerai, in the state of Kedah on the west coast of Peninsular Malaysia. It is associated with riparian habitats, and can be found near streams. It has been only been found at elevations between 800 – 1200 m (Quah et al. 2017).</p>
        [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://amphibiaweb.org/cgi/amphib_query?where-genus=Abavorana&where-species=nazgul&account=amphibiaweb
        [http://purl.org/dc/terms/language] => en
        [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by/3.0/
        [http://eol.org/schema/agent/agentID] => 40dafcb8c613187d62bc1033004b43b9
        [http://eol.org/schema/reference/referenceID] => d08a99802fc760abbbfc178a391f9336; 8d5b9dee4f523c6243387c962196b8e0; 4d496c9853b52d6d4ee443b4a6103cca
        )*/

        // exit("\ntaxonID: ".$rec['http://rs.tdwg.org/dwc/terms/taxonID']."\n"); //debug only
        // exit("\n[".$this->param['resource_id']."]\n"); //e.g. '617_ENV'
        $basename = $rec['http://rs.tdwg.org/dwc/terms/taxonID']."_-_".$rec['http://purl.org/dc/terms/identifier'];
        $desc = strip_tags($rec['http://purl.org/dc/terms/description']);
        $desc = trim(Functions::remove_whitespace($desc));
        self::retrieve_annotation($basename, $desc);
        self::write_to_pensoft_tags($basename);
    }
    private function write_to_pensoft_tags($basename)
    {
        $file = $this->eol_tags_path."eol_tags_noParentTerms.tsv";
        if($f = Functions::file_open($file, "a")) {
            /*Array( [http://purl.obolibrary.org/obo/ENVO_00002011] => freshwater
                     [http://purl.obolibrary.org/obo/ENVO_00000026] => well
            )*/
            // print_r($this->eli); //good debug
            foreach($this->results as $uri => $label) {
                if($ret = self::apply_adjustments($uri, $label)) {
                    $uri = $ret['uri'];
                    $label = $ret['label'];
                    $this->all_envo_terms[$uri] = $label; //for stats only - report for Jen
                }
                else continue;
                
                if(stripos($uri, "ENVO_") !== false) { //string is found
                    $arr = array($basename, '', '', $label, pathinfo($uri, PATHINFO_FILENAME));
                }
                else $arr = array($basename, '', '', $label, $uri);
                
                fwrite($f, implode("\t", $arr)."\n");
            }
            fclose($f);
        }
    }
    public function retrieve_annotation($id, $desc)
    {
        $len = strlen($desc);
        $loops = $len/2000; //echo("\n\n[$loops]");
        $loops = ceil($loops);
        $ctr = 0;
        sleep(0.5);
        for($loop = 1; $loop <= $loops; $loop++) { //echo "\n[$loop of $loops]";
            $str = substr($desc, $ctr, 2000);
            $str = utf8_encode($str);
            // if($loop == 29) exit("\n--------\n[$str]\n---------\n");
            $id = md5($str);
            self::retrieve_partial($id, $str, $loop);
            $ctr = $ctr + 2000;
        }
        // print_r($this->results);
        // exit("\n[$loops]\n");
        if(isset($this->results)) return $this->results;
    }
    private function retrieve_partial($id, $desc, $loop)
    {
        // echo "\n[$id]\n";
        if($arr = self::retrieve_json($id, 'partial', $desc)) {
            // if($loop == 29) { print_r($arr['data']); //exit; }
            self::select_envo($arr['data']);
            // echo("\nretrieved partial OK\n"); //good debug
        }
        else {
            if($json = self::run_partial($desc)) {
                self::save_json($id, $json, 'partial');
                // echo("\nSaved partial OK\n"); //good debug
                /* now start access newly created. The var $this->results will now be populated. */
                if($arr = self::retrieve_json($id, 'partial', $desc)) {
                    self::select_envo($arr['data']);
                    // echo("\nretrieved (newly created) partial OK\n"); //good debug
                }
                else exit("\nShould not go here, since record should be created now.\n");
            }
            else exit(" -- nothing to save..."); //doesn't go here
        }
    }
    private function select_envo($arr)
    {   /*Array(
            [0] => Array(
                    [id] => http://purl.obolibrary.org/obo/ENVO_00000083
                    [lbl] => Hill
                    [context] => 2015. ^ Patterson, B. D. (2004). The Lions of Tsavo: Exploring the Legacy of Africa's Notorious Man-Eaters. New York: McGraw <b>Hill</b> Professional. ISBN 978-0-07-136333-4. ^ Patterson, B. D.; Neiburger, E. J.; Kasiki, S. M. (2003). 2.0.CO;2 "Tooth Breakage and Dental Disease
                    [length] => 4
                    [position] => 877
                    [ontology] => envo
                    [type] => CLASS
                    [is_synonym] => 
                    [color] => #F7F3E3
                    [is_word] => 1
                    [hash] => dda9a35f1c55d220ce83d768af23bfd5
                )
        */
        foreach($arr as $rek) {
            // /* customize
            // exit("\n".$this->param['resource_id']."\n");
            if($this->param['resource_id'] == '21_ENV') { //AmphibiaWeb text
                if($rek['id'] == 'http://purl.obolibrary.org/obo/ENVO_00002010') continue; //saline water. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65409&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65409
                if(isset($this->descendants_of_saline_water[$rek['id']])) continue;
            }
            // */
            
            if($this->param['resource_id'] == '617_ENV') { //Wikipedia EN
                if(ctype_lower(substr($rek['lbl'],0,1))) { //bec. references has a lot like 'Urban C.' which are authors.
                    $this->results[$rek['id']] = $rek['lbl'];
                    // $this->eli[$rek['id']][] = $rek['lbl']; //good debug
                }
            }
            else { //rest of the resources --> Just be sure the citation, reference, biblio parts of text is not included as input to Pensoft
                $this->results[$rek['id']] = $rek['lbl'];
            }
        }
    }
    private function retrieve_json($id, $what, $desc)
    {
        $file = self::retrieve_path($id, $what);
        // echo "\nfile = [$file]\n"; //good debug
        if(is_file($file)) {
            $json = file_get_contents($file); // echo "\nRetrieved OK [$id]";
            return json_decode($json, true);
        }
    }
    private function run_partial($desc)
    {   // echo "\nRunning Pensoft annotator...";
        $cmd = 'curl -s GET "http://api.pensoft.net/annotator?text='.urlencode($desc).'&ontologies=envo"';
        $cmd .= " 2>&1";
        $json = shell_exec($cmd);
        // echo "\n$desc\n---------";
        // echo "\n$json\n-------------\n"; //exit("\n111\n");
        return $json;
    }
    private function retrieve_path($id, $what) //$id is "$taxonID_$identifier"
    {
        $filename = "$id.json";
        $md5 = md5($id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        return $this->json_temp_path[$what] . "$cache1/$cache2/$filename";
    }
    private function save_json($id, $json, $what)
    {
        $file = self::build_path($id, $what);
        if($f = Functions::file_open($file, "w")) {
            fwrite($f, $json);
            fclose($f);
        }
        else exit("\nCannot write file\n");
    }
    private function build_path($id, $what) //$id is "$taxonID_$identifier"
    {
        $filename = "$id.json";
        $md5 = md5($id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($this->json_temp_path[$what] . $cache1)) mkdir($this->json_temp_path[$what] . $cache1);
        if(!file_exists($this->json_temp_path[$what] . "$cache1/$cache2")) mkdir($this->json_temp_path[$what] . "$cache1/$cache2");
        return $this->json_temp_path[$what] . "$cache1/$cache2/$filename";
    }
    private function gen_noParentTerms()
    {   echo "\nRun gen_noParentTerms()...\n";
        $current_dir = getcwd(); //get current dir
        chdir($this->root_path);
        /*
        ./eol_scripts/exclude-parents-E.pl eol_tags/eol_tags.tsv eol_scripts/envo_child_parent.tsv > eol_tags/eol_tags_noParentTerms.tsv
        */
        $cmd = "./eol_scripts/exclude-parents-E.pl $this->eol_tags_destination $this->eol_scripts_path"."envo_child_parent.tsv > $this->eol_tags_path"."eol_tags_noParentTerms.tsv";
        shell_exec($cmd);
        chdir($current_dir); //go back to current dir
        /* un-comment if you want to investigate raw source files: eol_tags.tsv and eol_tags_noParentTerms.tsv
        exit("\nStop muna, will investigate\n"); //comment in real operation
        */
    }
    private function valid_record($rec)
    {   if($rec['http://purl.org/dc/terms/type'] == 'http://purl.org/dc/dcmitype/Text' &&
           in_array(@$rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'], $this->allowed_subjects) &&
           @$rec['http://purl.org/dc/terms/description'] && $rec['http://rs.tdwg.org/dwc/terms/taxonID'] && 
           $rec['http://purl.org/dc/terms/identifier']) return true;
        else return false;
    }
    private function get_allowed_subjects($pipe_delimited)
    {   $arr = explode("|", $pipe_delimited);
        foreach($arr as $subject) {
            if($val = @$this->subjects[$subject]) $allowed_subjects[] = $val;
            else exit("\nSubject not yet initialized [$subject]\n");
        }
        return $allowed_subjects;
    }
    function build_info_tables(){}
    private function get_unique_obj_identifiers()
    {
        $tsv = $this->eol_tags_path.'eol_tags_noParentTerms.tsv';
        foreach(new FileIterator($tsv) as $line_number => $row) {
            $arr = explode("\t", $row); // print_r($arr); exit;
            /* Array(
                [0] => 1005_-_1005_distribution.txt
                [1] => 117
                [2] => 122
                [3] => shrubs
                [4] => ENVO:00000300
            )*/
            $arr[0] = str_replace('.txt', '', $arr[0]);
            $a = explode("_-_", $arr[0]);
            if($val = @$a[1]) $ids[$val] = '';
        }
        return $ids;
    }
    private function save_media_metadata_for_these_objects($obj_identifiers, $meta)
    {   echo "\nsave_media_metadata_for_these_objects()...";
        // $this->json_temp_path = create_temp_dir() . "/"; //abandoned. not used anymore.
        echo("\njson temp path: ".$this->json_temp_path['metadata']."\n");
        $agent_ids = array();
        $i = 0; $saved = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % $this->modulo) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\n".count($obj_identifiers)."\n");
            /* Array(
                [http://purl.org/dc/terms/identifier] => 8687_distribution
                [http://rs.tdwg.org/dwc/terms/taxonID] => 8687
                [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
                [http://purl.org/dc/terms/format] => text/plain
                [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
                [http://purl.org/dc/terms/title] => Distribution and Habitat
                [http://purl.org/dc/terms/description] => <p><i>Abavorana nazgul</i> is only known from the mountain, Gunung Jerai, in the state of Kedah on the west coast of Peninsular Malaysia. It is associated with riparian habitats, and can be found near streams. It has been only been found at elevations between 800 – 1200 m (Quah et al. 2017).</p>
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://amphibiaweb.org/cgi/amphib_query?where-genus=Abavorana&where-species=nazgul&account=amphibiaweb
                [http://purl.org/dc/terms/language] => en
                [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by/3.0/
                [http://eol.org/schema/agent/agentID] => 40dafcb8c613187d62bc1033004b43b9
                [http://eol.org/schema/reference/referenceID] => d08a99802fc760abbbfc178a391f9336; 8d5b9dee4f523c6243387c962196b8e0; 4d496c9853b52d6d4ee443b4a6103cca
            )*/
            $identifier = $rec['http://purl.org/dc/terms/identifier'];
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if(isset($obj_identifiers[$identifier])) {
                $final = array();
                if($val = @$rec['http://purl.org/dc/terms/source']) $final['source'] = $val;
                if($val = @$rec['http://rs.tdwg.org/ac/terms/furtherInformationURL']) $final['source'] = $val;
                if($val = @$rec['http://purl.org/dc/terms/bibliographicCitation']) $final['bibliographicCitation'] = $val;
                if($val = @$rec['http://purl.org/dc/terms/contributor']) $final['contributor'] = $val;
                if($val = @$rec['http://eol.org/schema/reference/referenceID']) $final['referenceID'] = $val;
                if($val = @$rec['http://eol.org/schema/agent/agentID']) {
                    $final['agentID'] = $val;
                    $ids = explode(";", trim($val));
                    $ids = array_map('trim', $ids);
                    foreach($ids as $id) {
                        $agent_ids[$id] = '';
                    }
                }
                if($final) {
                    $json = json_encode($final);
                    self::save_json($taxonID."_".$identifier, $json, 'metadata');
                }
            }
        }
        return $agent_ids;
    }
    private function save_agent_metadata_for_these_agents($agent_ids, $meta)
    {   echo "\nsave_agent_metadata_for_these_agents()...";
        echo("\njson temp path: ".$this->json_temp_path['metadata']."\n");
        $i = 0; $saved = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 1000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\n".count($agent_ids)."\n");
            /* Array(
                [http://purl.org/dc/terms/identifier] => 40dafcb8c613187d62bc1033004b43b9
                [http://xmlns.com/foaf/spec/#term_name] => Zheng Oong
                [http://eol.org/schema/agent/agentRole] => author
                [http://xmlns.com/foaf/spec/#term_homepage] => 
            )*/
            $identifier = $rec['http://purl.org/dc/terms/identifier'];
            if(isset($agent_ids[$identifier])) {
                $final = array();
                if($val = @$rec['http://purl.org/dc/terms/identifier']) $final['identifier'] = $val;
                if($val = @$rec['http://xmlns.com/foaf/spec/#term_name']) $final['term_name'] = $val;
                if($val = @$rec['http://eol.org/schema/agent/agentRole']) $final['agentRole'] = $val;
                if($val = @$rec['http://xmlns.com/foaf/spec/#term_homepage']) $final['term_homepage'] = $val;
                if($final) {
                    $json = json_encode($final);
                    self::save_json("agent_".$identifier, $json, 'metadata');
                }
            }
        }
    }
    private function get_opendata_dwca_url($resource_name)
    {
        $url = str_replace('RESOURCE_NAME', $resource_name, $this->call['opendata resource via name']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $arr = json_decode($json, true); // print_r($arr);
            if($recs = @$arr['result']['results']) {
                foreach($recs as $rec) {
                    if($rec['name'] == $resource_name) return $rec['url'];
                }
            }
        }
    }
    private function lookup_opendata_resource()
    {
        print_r($this->param);
        /* Array(
            [task] => generate_eol_tags
            [resource] => AmphibiaWeb text
            [resource_id] => 21_ENV
            [subjects] => Distribution
        )*/
        $resource_name = $this->param['resource'];
        if($dwca_url = self::get_opendata_dwca_url($resource_name)) {
            /* based here:
            $this->DwCA_URLs['AmphibiaWeb text'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/21.tar.gz';
            */
            $this->DwCA_URLs[$resource_name] = $dwca_url;
            print_r($this->DwCA_URLs);
        }
        else exit("\nOpenData resource not found [$resource_name]\n");
        // exit("\n-exit muna-\n");
    }
    private function noParentTerms_less_entities_file()
    {   echo "\nCleaning noParentTerms...\n";
        /* step 1: get_envo_from_entities_file */
        $envo_from_entities = self::get_envo_from_entities_file();
        // print_r($envo_from_entities); exit;
        /*Array(
            [0] => _entities_3
            [1] => ENVO_00000002
            [2] => ENVO_00000012
            [3] => ENVO_00000013
            [4] => ENVO_00000014
        */
        foreach($envo_from_entities as $envo_term) $envoFromEntities[$envo_term] = '';
        unset($envo_from_entities);
        
        /* step 2: loop */
        if(copy($this->eol_tags_path."eol_tags_noParentTerms.tsv", $this->eol_tags_path."eol_tags_noParentTerms.tsv.old")) echo "\nCopied OK (eol_tags_noParentTerms.tsv)\n";
        else exit("\nERROR: Copy failed (eol_tags_noParentTerms.tsv)\n");
        /* sample entry in eol_tags_noParentTerms.tsv.old
        Q27075389_-_3fbbae3f2254cfaa6d3116e0289bf7a5			boreal	http://www.wikidata.org/entity/Q1342399
        Q27075917_-_1513ce4574ed644a72e3f8471b848964			boreal	http://www.wikidata.org/entity/Q1342399
        Q28122714_-_6403c7c5a4729f8a0a26c58725779c5b			subarctic	http://www.wikidata.org/entity/Q1342399
        Q62854736_-_f1bc9ada6ddeb011d7e1c3037a71f6fe			subarctic	http://www.wikidata.org/entity/Q1342399
        Q140_-_3534a7422ad054e6972151018c05cb38			habitat	ENVO_01000739
        Q140_-_3534a7422ad054e6972151018c05cb38			radiation	ENVO_01001023
        Q140_-_3534a7422ad054e6972151018c05cb38			climate	ENVO_01001082
        */
        $f = Functions::file_open($this->eol_tags_path."eol_tags_noParentTerms.tsv", "w"); fclose($f); //initialize
        $file = $this->eol_tags_path."eol_tags_noParentTerms.tsv.old"; $i = 0;
        foreach(new FileIterator($file) as $line => $row) {
            $i++; //if(($i % $this->modulo) == 0) echo "\n".number_format($i);
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            // print_r($tmp); exit;
            /*Array(
                [0] => Q140_-_3534a7422ad054e6972151018c05cb38
                [1] => 
                [2] => 
                [3] => habitat
                [4] => ENVO_01000739
            )*/
            $envo_term = pathinfo($tmp[4], PATHINFO_BASENAME); //bec it can be "http://www.wikidata.org/entity/Q1342399" or "ENVO_01001082".
            if(isset($envoFromEntities[$envo_term])) {
                $f = Functions::file_open($this->eol_tags_path."eol_tags_noParentTerms.tsv", "a");
                fwrite($f, $row."\n");
                fclose($f);
            }
        }
        $out = shell_exec("wc -l " . $this->eol_tags_path."eol_tags_noParentTerms.tsv.old"); echo "\n eol_tags_noParentTerms.tsv.old ($out)\n";
        $out = shell_exec("wc -l " . $this->eol_tags_path."eol_tags_noParentTerms.tsv");     echo "\n eol_tags_noParentTerms.tsv ($out)\n";
    }
    private function apply_adjustments($uri, $label) //apply it here: ALL_remap_replace_remove.txt
    {
        if(in_array($uri, array("http://purl.obolibrary.org/obo/ENVO_00000029", "http://purl.obolibrary.org/obo/ENVO_00000104")) && $label == 'ravine') $uri = "http://purl.obolibrary.org/obo/ENVO_00000100";
        if($new_uri = @$this->mRemarks[$label]) $uri = $new_uri;
        if($new_uri = @$this->remapped_terms[$uri]) $uri = $new_uri;
        if(isset($this->delete_MoF_with_these_labels[$label])) return false;
        if(isset($this->delete_MoF_with_these_uris[$uri])) return false;

        // /* customize
        if($this->param['resource_id'] == '21_ENV') { //AmphibiaWeb text
            if($uri == 'http://purl.obolibrary.org/obo/ENVO_00002010') return false; //saline water. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65409&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65409
            if(isset($this->descendants_of_saline_water[$uri])) return false;
        }
        // */
        
        return array('label' => $label, 'uri' => $uri);
    }
    private function init_DATA_1841_terms_remapped()
    {
        require_library('connectors/TropicosArchiveAPI');
        /* START DATA-1841 terms remapping */
        $url = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Terms_remapped/DATA_1841_terms_remapped.tsv";
        $func = new TropicosArchiveAPI(NULL); //to initialize variable $this->uri_values in TropicosArchiveAPI
        $this->remapped_terms = $func->add_additional_mappings(true, $url, 60); //*this is not add_additional_mappings() 60*60*24
        echo "\nremapped_terms: ".count($this->remapped_terms)."\n";
        /* END DATA-1841 terms remapping */
    }
    private function initialize_delete_mRemarks()
    {
        // if measurementRemarks is any of these, then delete MoF
        $a1 = array('range s', 'ranges', 'range s', 'rang e', 'bamboo', 'barrens', 'breaks', 'mulga', 'chanaral');
        $a2 = array('ridge', 'plateau', 'plateaus', 'crests', 'canyon', 'terrace', 'canyons', 'gullies', 'notches', 'terraces', 'bluff', 'cliffs', 'gulch', 'gully', 'llanos', 'plantations', 'sierra', 'tunnel');
        $a3 = array('chemical product', 'cosmetic product', 'paper product', 'zoological garden', 'world heritage site', 'wildlife management area', 'warehouse', 'vivarium', 'terrarium', 'saline water aquarium', 
        'road cut', 'road', 'populated place', 'plant feed', 'oil spill', 'oil tank', 'oil well', 'oil reservoir', 'oil', 'nature reserve', 'national nature reserve', 'national park', 
        'national wildlife refuge', 'mouth', 'military training area', 'industrial waste', 'geographic feature', 'geothermal field', 'geothermal power plant', 'fresh water aquarium', 'elevation', 
        'bridge', 'blowhole', 'bakery', 'aquarium', 'anthropogenic geographic feature', 'animal habitation', 'air conditioning unit', 'activated sludge', 'agricultural feature');
        $labels = array_merge($a1, $a2, $a3);
        foreach($labels as $label) $this->delete_MoF_with_these_labels[$label] = '';
    }
    private function initialize_mRemark_assignments()
    {
        $mRemarks["open waters"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["open-water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["openwater"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["open water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["dry stream beds"] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        $mRemarks["dry streambeds"] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        $mRemarks["dry stream-beds"] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        $mRemarks["dry stream bed"] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        $mRemarks["dry streambed"] = "http://purl.obolibrary.org/obo/ENVO_00000278";
        $mRemarks["coral heads"] = "http://purl.obolibrary.org/obo/ENVO_01000049";
        $mRemarks["coral head"] = "http://purl.obolibrary.org/obo/ENVO_01000049";
        $mRemarks["glades"] = "http://purl.obolibrary.org/obo/ENVO_00000444";
        $mRemarks["glade"] = "http://purl.obolibrary.org/obo/ENVO_00000444";
        $mRemarks["seaway"] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        $mRemarks["tide way"] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        $mRemarks["tideway"] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        $mRemarks["sea-way"] = "http://purl.obolibrary.org/obo/ENVO_00000447";
        $mRemarks["herbaceous areas"] = "http://purl.obolibrary.org/obo/ENVO_01001305";
        $mRemarks["loch"] = "http://purl.obolibrary.org/obo/ENVO_01000252";
        $mRemarks["croplands"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["cropland"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["crop land"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["agricultural regions"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["agricultural region"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["crop-lands"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["cultivated croplands"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["cultivated s"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["crop lands"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["sea vents"] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        $mRemarks["active chimneys"] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        $mRemarks["sea vent"] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        $mRemarks["active chimney"] = "http://purl.obolibrary.org/obo/ENVO_01000030";
        $mRemarks["embayments"] = "http://purl.obolibrary.org/obo/ENVO_00000032";
        $mRemarks["embayment"] = "http://purl.obolibrary.org/obo/ENVO_00000032";
        $mRemarks["brush"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["bush"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["brushes"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["caatinga"] = "http://purl.obolibrary.org/obo/ENVO_00000883";
        $mRemarks["caatingas"] = "http://purl.obolibrary.org/obo/ENVO_00000883";
        $mRemarks["coniferous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["coniferous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["coniferous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["coniferousforest"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["coniferousforests"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["deciduous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["deciduous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["deciduous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["deciduous-forest"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["deciduousforest"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["deciduousforests"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["equatorial forest"] = "http://purl.obolibrary.org/obo/ENVO_01000220";
        $mRemarks["equatorial forests"] = "http://purl.obolibrary.org/obo/ENVO_01000220";
        $mRemarks["equatorial rain forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["equatorial rain forests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["equatorial rainforest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["equatorial rainforests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["jungle"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["jungles"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["mallee scrub"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["mangrove forest"] = "http://purl.obolibrary.org/obo/ENVO_01000181";
        $mRemarks["mangrove forests"] = "http://purl.obolibrary.org/obo/ENVO_01000181";
        $mRemarks["mangrove- forest"] = "http://purl.obolibrary.org/obo/ENVO_01000181";
        $mRemarks["monsoon forest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["monsoon forests"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["monsoon-forest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["mulga scrub"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["pine grove"] = "http://purl.obolibrary.org/obo/ENVO_01000240";
        $mRemarks["pine groves"] = "http://purl.obolibrary.org/obo/ENVO_01000240";
        $mRemarks["pinegrove"] = "http://purl.obolibrary.org/obo/ENVO_01000240";
        $mRemarks["rain forest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rain forest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rain forests"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rain-forest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rain-forests"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rainforest"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["rainforests"] = "http://eol.org/schema/terms/wet_forest";
        $mRemarks["sage brush"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["sage-brush"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["sagebrush"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["sagebrushes"] = "http://purl.obolibrary.org/obo/ENVO_01000176";
        $mRemarks["taiga"] = "http://eol.org/schema/terms/boreal_forests_taiga";
        $mRemarks["taigas"] = "http://eol.org/schema/terms/boreal_forests_taiga";
        $mRemarks["thorn forest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["thorn forests"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["thorn-forest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["thornforest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["thornforests"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["tropical rain forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rain forests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rain-forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rainforest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rainforests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropicalrainforests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";

        $mRemarks["coast"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coastal"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coastal areas"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coastal strip"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coastal region"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coasts"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["coastal regions"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["costal"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["littoral"] = "http://eol.org/schema/terms/littoralZone";
        $mRemarks["Sea coast"] = "http://purl.obolibrary.org/obo/ENVO_01000687";
        $mRemarks["forests"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["forest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["deciduous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["groves"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["deciduous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["Forest Reserve"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["Forest Reserves"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["open-water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["open water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["rivers"] = "http://purl.obolibrary.org/obo/ENVO_01000253";
        $mRemarks["foothill"] = "http://purl.obolibrary.org/obo/ENVO_00000083";
        $mRemarks["foothills"] = "http://purl.obolibrary.org/obo/ENVO_00000083";
        $mRemarks["palm grove"] = "http://purl.obolibrary.org/obo/ENVO_01000220";
        $mRemarks["glades"] = "http://purl.obolibrary.org/obo/ENVO_00000444";
        $mRemarks["agricultural sites"] = "http://purl.obolibrary.org/obo/ENVO_00000077";
        $mRemarks["open-water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["open water"] = "http://purl.obolibrary.org/obo/ENVO_00002030";
        $mRemarks["mountains"] = "http://purl.obolibrary.org/obo/ENVO_00000081";
        $mRemarks["hills"] = "http://purl.obolibrary.org/obo/ENVO_00000083";
        $mRemarks["rainforests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["rainforest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rainforests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["rain forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["thorn forest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["deciduous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["tropical rainforest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["tropical rain forests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["deciduous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000816";
        $mRemarks["tropical rain forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["coniferous forests"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["thorn forests"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["rain-forest"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["rain forests"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["Jungle"] = "http://purl.obolibrary.org/obo/ENVO_01000228";
        $mRemarks["coniferous forest"] = "http://purl.obolibrary.org/obo/ENVO_01000196";
        $mRemarks["equatorial forest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["monsoon forests"] = "http://purl.obolibrary.org/obo/ENVO_00000879";
        $mRemarks["thornforest"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        $mRemarks["reforested areas"] = "http://purl.obolibrary.org/obo/ENVO_01000174";
        // per https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=64619&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64619 */
        $mRemarks["seamounts"] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
        $mRemarks["seamount"] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
        $mRemarks["seamount chain"] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
        $mRemarks["range of seamounts"] = 'http://purl.obolibrary.org/obo/ENVO_00000264';
        $this->mRemarks = $mRemarks;
    }
    private function initialize_delete_uris()
    {
        $uris = array('http://purl.obolibrary.org/obo/ENVO_00000104', 'http://purl.obolibrary.org/obo/ENVO_00002033', 'http://purl.obolibrary.org/obo/ENVO_00000304', 
        'http://purl.obolibrary.org/obo/ENVO_00000486', 'http://purl.obolibrary.org/obo/ENVO_00002000', 'http://purl.obolibrary.org/obo/ENVO_00000086', 
        'http://purl.obolibrary.org/obo/ENVO_00000220', 'http://purl.obolibrary.org/obo/ENVO_00000113', 'http://purl.obolibrary.org/obo/ENVO_00002232', 
        'http://purl.obolibrary.org/obo/ENVO_02000047', 'http://purl.obolibrary.org/obo/ENVO_00003031', 'http://purl.obolibrary.org/obo/ENVO_00002276', 
        'http://purl.obolibrary.org/obo/ENVO_00000121', 'http://purl.obolibrary.org/obo/ENVO_00000099', 'http://purl.obolibrary.org/obo/ENVO_00000377', 
        'http://purl.obolibrary.org/obo/ENVO_00000165', 'http://purl.obolibrary.org/obo/ENVO_00003903', 'http://purl.obolibrary.org/obo/ENVO_02000054', 
        'http://purl.obolibrary.org/obo/ENVO_00010624', 'http://purl.obolibrary.org/obo/ENVO_01000243', 'http://purl.obolibrary.org/obo/ENVO_01000114', 
        'http://purl.obolibrary.org/obo/ENVO_00003885', 'http://purl.obolibrary.org/obo/ENVO_00003044', 'http://purl.obolibrary.org/obo/ENVO_00000369', 
        'http://purl.obolibrary.org/obo/ENVO_00000158', 'http://purl.obolibrary.org/obo/ENVO_00000526', 'http://purl.obolibrary.org/obo/ENVO_02000058', 
        'http://purl.obolibrary.org/obo/ENVO_00002169', 'http://purl.obolibrary.org/obo/ENVO_00002206', 'http://purl.obolibrary.org/obo/ENVO_00002026', 
        'http://purl.obolibrary.org/obo/ENVO_00002170', 'http://purl.obolibrary.org/obo/ENVO_00000272', 'http://purl.obolibrary.org/obo/ENVO_00002116', 
        'http://purl.obolibrary.org/obo/ENVO_00002186', 'http://purl.obolibrary.org/obo/ENVO_00000293', 'http://purl.obolibrary.org/obo/ENVO_00000223', 
        'http://purl.obolibrary.org/obo/ENVO_00000514', 'http://purl.obolibrary.org/obo/ENVO_2000001', 'http://purl.obolibrary.org/obo/ENVO_00000320', 
        'http://purl.obolibrary.org/obo/ENVO_02000006', 'http://purl.obolibrary.org/obo/ENVO_00000474', 'http://purl.obolibrary.org/obo/ENVO_00000523', 
        'http://purl.obolibrary.org/obo/ENVO_00000074', 'http://purl.obolibrary.org/obo/ENVO_00000309', 'http://purl.obolibrary.org/obo/ENVO_00000037', 
        'http://purl.obolibrary.org/obo/ENVO_00002158', 'http://purl.obolibrary.org/obo/ENVO_00000291', 'http://purl.obolibrary.org/obo/ENVO_00003064', 
        'http://purl.obolibrary.org/obo/ENVO_00000449', 'http://purl.obolibrary.org/obo/ENVO_01000136', 'http://purl.obolibrary.org/obo/ENVO_00010506', 
        'http://purl.obolibrary.org/obo/ENVO_00002020', 'http://purl.obolibrary.org/obo/ENVO_00002027', 'http://purl.obolibrary.org/obo/ENVO_00000114', 
        'http://purl.obolibrary.org/obo/ENVO_00000294', 'http://purl.obolibrary.org/obo/ENVO_00000295', 'http://purl.obolibrary.org/obo/ENVO_00000471', 
        'http://purl.obolibrary.org/obo/ENVO_00000443', 'http://purl.obolibrary.org/obo/ENVO_00002002', 'http://purl.obolibrary.org/obo/ENVO_00000411', 
        'http://purl.obolibrary.org/obo/ENVO_00002164', 'http://purl.obolibrary.org/obo/ENVO_00002983', 'http://purl.obolibrary.org/obo/ENVO_00000011', 
        'http://purl.obolibrary.org/obo/ENVO_00000050', 'http://purl.obolibrary.org/obo/ENVO_00000131', 'http://purl.obolibrary.org/obo/ENVO_00002168', 
        'http://purl.obolibrary.org/obo/ENVO_00000340', 'http://purl.obolibrary.org/obo/ENVO_00005780', 'http://purl.obolibrary.org/obo/ENVO_00002041', 
        'http://purl.obolibrary.org/obo/ENVO_00002171', 'http://purl.obolibrary.org/obo/ENVO_00002028', 'http://purl.obolibrary.org/obo/ENVO_00002023', 
        'http://purl.obolibrary.org/obo/ENVO_00002025', 'http://purl.obolibrary.org/obo/ENVO_00003859', 'http://purl.obolibrary.org/obo/ENVO_00000468', 
        'http://purl.obolibrary.org/obo/ENVO_02000000', 'http://purl.obolibrary.org/obo/ENVO_00000098', 'http://purl.obolibrary.org/obo/ENVO_00000174', 
        'http://purl.obolibrary.org/obo/ENVO_00000311', 'http://purl.obolibrary.org/obo/ENVO_00000424', 'http://purl.obolibrary.org/obo/ENVO_00000391', 
        'http://purl.obolibrary.org/obo/ENVO_00000533', 'http://purl.obolibrary.org/obo/ENVO_00000178', 'http://purl.obolibrary.org/obo/ENVO_00000066', 
        'http://purl.obolibrary.org/obo/ENVO_01000057', 'http://purl.obolibrary.org/obo/ENVO_01000066', 'http://purl.obolibrary.org/obo/ENVO_00000509', 
        'http://purl.obolibrary.org/obo/ENVO_00000427', 'http://purl.obolibrary.org/obo/ENVO_00010621', 'http://purl.obolibrary.org/obo/ENVO_01000207', 
        'http://purl.obolibrary.org/obo/ENVO_00002035', 'http://purl.obolibrary.org/obo/ENVO_00010442', 'http://purl.obolibrary.org/obo/ENVO_00000076', 
        'http://purl.obolibrary.org/obo/ENVO_00001996', 'http://purl.obolibrary.org/obo/ENVO_00000003', 'http://purl.obolibrary.org/obo/ENVO_00000180', 
        'http://purl.obolibrary.org/obo/ENVO_00000477', 'http://purl.obolibrary.org/obo/ENVO_00000414', 'http://purl.obolibrary.org/obo/ENVO_00000359', 
        'http://purl.obolibrary.org/obo/ENVO_00000048', 'http://purl.obolibrary.org/obo/ENVO_00005804', 'http://purl.obolibrary.org/obo/ENVO_00005805', 
        'http://purl.obolibrary.org/obo/ENVO_2000006', 'http://purl.obolibrary.org/obo/ENVO_02000004', 'http://purl.obolibrary.org/obo/ENVO_00002271', 
        'http://purl.obolibrary.org/obo/ENVO_00000480', 'http://purl.obolibrary.org/obo/ENVO_00002139', 'http://purl.obolibrary.org/obo/ENVO_00000305', 
        'http://purl.obolibrary.org/obo/ENVO_00000134', 'http://purl.obolibrary.org/obo/ENVO_00002984', 'http://purl.obolibrary.org/obo/ENVO_00000191', 
        'http://purl.obolibrary.org/obo/ENVO_00000339', 'http://purl.obolibrary.org/obo/ENVO_00003860', 'http://purl.obolibrary.org/obo/ENVO_00000481', 
        'http://purl.obolibrary.org/obo/ENVO_00002214', 'http://purl.obolibrary.org/obo/ENVO_00000358', 'http://purl.obolibrary.org/obo/ENVO_00000302', 
        'http://purl.obolibrary.org/obo/ENVO_00001995', 'http://purl.obolibrary.org/obo/ENVO_00000022', 'http://purl.obolibrary.org/obo/ENVO_01000017', 
        'http://purl.obolibrary.org/obo/ENVO_00002055', 'http://purl.obolibrary.org/obo/ENVO_00004638', 'http://purl.obolibrary.org/obo/ENVO_00003930', 
        'http://purl.obolibrary.org/obo/ENVO_00000092', 'http://purl.obolibrary.org/obo/ENVO_00002016', 'http://purl.obolibrary.org/obo/ENVO_00002018', 
        'http://purl.obolibrary.org/obo/ENVO_00003043', 'http://purl.obolibrary.org/obo/ENVO_00002056', 'http://purl.obolibrary.org/obo/ENVO_00000403', 
        'http://purl.obolibrary.org/obo/ENVO_00003030', 'http://purl.obolibrary.org/obo/ENVO_00000539', 'http://purl.obolibrary.org/obo/ENVO_01000016', 
        'http://purl.obolibrary.org/obo/ENVO_00000361', 'http://purl.obolibrary.org/obo/ENVO_00002044', 'http://purl.obolibrary.org/obo/ENVO_00000393', 
        'http://purl.obolibrary.org/obo/ENVO_00000027', 'http://purl.obolibrary.org/obo/ENVO_00000419', 'http://purl.obolibrary.org/obo/ENVO_00000331', 
        'http://purl.obolibrary.org/obo/ENVO_00000330', 'http://purl.obolibrary.org/obo/ENVO_00000394', 'http://purl.obolibrary.org/obo/ENVO_00010504', 
        'http://purl.obolibrary.org/obo/ENVO_00000543', 'http://purl.obolibrary.org/obo/ENVO_00003323', 'http://purl.obolibrary.org/obo/ENVO_00003096', 
        'http://purl.obolibrary.org/obo/ENVO_02000001', 'http://purl.obolibrary.org/obo/ENVO_00000122', 'http://purl.obolibrary.org/obo/ENVO_00000499', 
        'http://purl.obolibrary.org/obo/ENVO_00000094', 'http://purl.obolibrary.org/obo/ENVO_00002264', 'http://purl.obolibrary.org/obo/ENVO_00002272', 
        'http://purl.obolibrary.org/obo/ENVO_00002001', 'http://purl.obolibrary.org/obo/ENVO_00002043', 'http://purl.obolibrary.org/obo/ENVO_00000029', 
        'http://purl.obolibrary.org/obo/ENVO_00000547', 'http://purl.obolibrary.org/obo/ENVO_00000292', 'http://purl.obolibrary.org/obo/ENVO_00000421', 
        'http://purl.obolibrary.org/obo/ENVO_00000043', 'http://purl.obolibrary.org/obo/ENVO_00000409', 'http://purl.obolibrary.org/obo/ENVO_00002040', 
        'http://purl.obolibrary.org/obo/ENVO_00001998', 'http://purl.obolibrary.org/obo/ENVO_00000376', 'http://purl.obolibrary.org/obo/ENVO_00002152', 
        'http://purl.obolibrary.org/obo/ENVO_00002123', 'http://purl.obolibrary.org/obo/ENVO_00000530', 'http://purl.obolibrary.org/obo/ENVO_00000564', 
        'http://purl.obolibrary.org/obo/ENVO_00002277', 'http://purl.obolibrary.org/obo/ENVO_00000438', 'http://purl.obolibrary.org/obo/ENVO_2000004',     
        'http://purl.obolibrary.org/obo/ENVO_00000367', 'http://purl.obolibrary.org/obo/ENVO_00000363', 'http://purl.obolibrary.org/obo/ENVO_00000305', 
        'http://purl.obolibrary.org/obo/ENVO_00000358', 'http://purl.obolibrary.org/obo/ENVO_00000064', 'http://purl.obolibrary.org/obo/ENVO_00000515', 
        'http://purl.obolibrary.org/obo/ENVO_01000246', 'http://purl.obolibrary.org/obo/ENVO_00010622', 'http://purl.obolibrary.org/obo/ENVO_00010625', 
        'http://purl.obolibrary.org/obo/ENVO_00002000', 'http://purl.obolibrary.org/obo/ENVO_00000376', 'http://purl.obolibrary.org/obo/ENVO_00000011', 
        'http://purl.obolibrary.org/obo/ENVO_00000291', 'http://purl.obolibrary.org/obo/ENVO_00002277', 'http://purl.obolibrary.org/obo/ENVO_00000393', 
        'http://purl.obolibrary.org/obo/ENVO_00000547', 'http://purl.obolibrary.org/obo/ENVO_01000243', 'http://purl.obolibrary.org/obo/ENVO_00000514', 
        'http://purl.obolibrary.org/obo/ENVO_00000533', 'http://purl.obolibrary.org/obo/ENVO_00000104', 'http://purl.obolibrary.org/obo/ENVO_00000320', 
        'http://purl.obolibrary.org/obo/ENVO_00000220', 'http://purl.obolibrary.org/obo/ENVO_00000029', 'http://purl.obolibrary.org/obo/ENVO_00000293', 
        'http://purl.obolibrary.org/obo/ENVO_00000174', 'http://purl.obolibrary.org/obo/ENVO_00000480', 'http://purl.obolibrary.org/obo/ENVO_00004638', 
        'http://purl.obolibrary.org/obo/ENVO_00002139', 'http://purl.obolibrary.org/obo/ENVO_00000477', 'http://purl.obolibrary.org/obo/ENVO_2000001', 
        'http://purl.obolibrary.org/obo/ENVO_00000331', 'http://purl.obolibrary.org/obo/ENVO_00000292', 'http://purl.obolibrary.org/obo/ENVO_01000016', 
        'http://purl.obolibrary.org/obo/ENVO_00000499', 'http://purl.obolibrary.org/obo/ENVO_00000427', 'http://purl.obolibrary.org/obo/ENVO_00002041', 
        'http://purl.obolibrary.org/obo/ENVO_00000294', 'http://purl.obolibrary.org/obo/ENVO_00000122', 'http://purl.obolibrary.org/obo/ENVO_00010624', 
        'http://purl.obolibrary.org/obo/ENVO_00002271', 'http://purl.obolibrary.org/obo/ENVO_00002026', 'http://purl.obolibrary.org/obo/ENVO_00000302', 
        'http://purl.obolibrary.org/obo/ENVO_00000550', 'http://purl.obolibrary.org/obo/ENVO_00000178', 'http://purl.obolibrary.org/obo/ENVO_00000480', 
        'http://purl.obolibrary.org/obo/ENVO_00000086', 'http://purl.obolibrary.org/obo/ENVO_00002055', 'http://purl.obolibrary.org/obo/ENVO_01000047',
        'http://purl.obolibrary.org/obo/ENVO_2000000', 'http://purl.obolibrary.org/obo/ENVO_00003893', 'http://purl.obolibrary.org/obo/ENVO_00003895', 'http://purl.obolibrary.org/obo/ENVO_00010625', 
        'http://purl.obolibrary.org/obo/ENVO_00000375', 'http://purl.obolibrary.org/obo/ENVO_00000374', 'http://purl.obolibrary.org/obo/ENVO_00003963', 'http://purl.obolibrary.org/obo/ENVO_00010622', 
        'http://purl.obolibrary.org/obo/ENVO_00000349', 'http://purl.obolibrary.org/obo/ENVO_00002197', 'http://purl.obolibrary.org/obo/ENVO_00000515', 'http://purl.obolibrary.org/obo/ENVO_00000064', 
        'http://purl.obolibrary.org/obo/ENVO_00000062', 'http://purl.obolibrary.org/obo/ENVO_02000055', 'http://purl.obolibrary.org/obo/ENVO_00002061', 'http://purl.obolibrary.org/obo/ENVO_00002183', 
        'http://purl.obolibrary.org/obo/ENVO_01000003', 'http://purl.obolibrary.org/obo/ENVO_00002185', 'http://purl.obolibrary.org/obo/ENVO_00002985', 'http://purl.obolibrary.org/obo/ENVO_00000363', 
        'http://purl.obolibrary.org/obo/ENVO_00000366', 'http://purl.obolibrary.org/obo/ENVO_00000367', 'http://purl.obolibrary.org/obo/ENVO_00000364', 'http://purl.obolibrary.org/obo/ENVO_00000479', 
        'http://purl.obolibrary.org/obo/ENVO_00000561', 'http://purl.obolibrary.org/obo/ENVO_00002267', 'http://purl.obolibrary.org/obo/ENVO_00000000', 'http://purl.obolibrary.org/obo/ENVO_00000373', 
        'http://purl.obolibrary.org/obo/ENVO_00002215', 'http://purl.obolibrary.org/obo/ENVO_00002198', 'http://purl.obolibrary.org/obo/ENVO_00000176', 'http://purl.obolibrary.org/obo/ENVO_00000075', 
        'http://purl.obolibrary.org/obo/ENVO_00000168', 'http://purl.obolibrary.org/obo/ENVO_00003864', 'http://purl.obolibrary.org/obo/ENVO_00002196', 'http://purl.obolibrary.org/obo/ENVO_00000002', 
        'http://purl.obolibrary.org/obo/ENVO_00005803', 'http://purl.obolibrary.org/obo/ENVO_00002874', 'http://purl.obolibrary.org/obo/ENVO_00002046', 'http://purl.obolibrary.org/obo/ENVO_00000077', 
        'http://purl.obolibrary.org/obo/ENVO_01000760');
        foreach($uris as $uri) $this->delete_MoF_with_these_uris[$uri] = '';
    }
    private function filter_out_from_entities()
    {   //from: https://eol-jira.bibalex.org/browse/DATA-1858?focusedCommentId=65359&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65359
        return array('ENVO_00000026', 'ENVO_01000342', 'ENVO_00000241', 'ENVO_01000001', 'ENVO_00002982', 'ENVO_01000628', 'ENVO_00002053', 'ENVO_00000014', 'ENVO_01000018', 'ENVO_00000167', 
        'ENVO_00002007', 'ENVO_00000856', 'ENVO_00000084', 'ENVO_00000040', 'ENVO_00000083', 'ENVO_01000155', 'ENVO_00000078', 'ENVO_00000444', 'ENVO_00000025', 'ENVO_00000032', 'ENVO_00002008', 
        'ENVO_00000495', 'ENVO_00000101', 'ENVO_00002015', 'ENVO_00000255', 'ENVO_00002054', 'ENVO_00000418', 'ENVO_00000463', 'ENVO_00000247', 'ENVO_01000236', 'ENVO_00000284', 'ENVO_00002034', 
        'ENVO_00000439', 'ENVO_00000115', 'ENVO_00000381', 'ENVO_00000133', 'ENVO_01000005', 'ENVO_00002140', 'ENVO_00000231', 'ENVO_00000166', 'ENVO_00012408', 'ENVO_00010505', 'ENVO_00002226', 
        'ENVO_00000235', 'ENVO_00000275', 'ENVO_00002870', 'ENVO_00000475', 'ENVO_00002269', 'ENVO_00000138', 'ENVO_01000158', 'ENVO_00000195', 'ENVO_00001997', 'ENVO_02000059', 'ENVO_00000440', 
        'ENVO_00002013', 'ENVO_00000102', 'ENVO_00005792', 'ENVO_00000298', 'ENVO_00010358', 'ENVO_01000002', 'ENVO_01000006', 'ENVO_00000085', 'ENVO_00000163', 'ENVO_00000520', 'ENVO_00002118', 
        'ENVO_00002144', 'ENVO_00003982', 'ENVO_00000149', 'ENVO_00000110', 'ENVO_00000313', 'ENVO_00000429', 'ENVO_00000500', 'ENVO_00000236', 'ENVO_00000245', 'ENVO_00005754', 'ENVO_00000422', 
        'ENVO_00000535', 'ENVO_00000120', 'ENVO_00000155', 'ENVO_01000019', 'ENVO_00000069', 'ENVO_00000139', 'ENVO_00000145', 'ENVO_00000473', 'ENVO_00000534', 'ENVO_00005742', 'ENVO_00005747', 
        'ENVO_00000072', 'ENVO_00000287', 'ENVO_00000400', 'ENVO_00000496', 'ENVO_00000497', 'ENVO_00000544', 'ENVO_00002270', 'ENVO_00000036', 'ENVO_00000119', 'ENVO_00000140', 'ENVO_00000157', 
        'ENVO_00000256', 'ENVO_00002063', 'ENVO_00003041', 'ENVO_00005799', 'ENVO_01000063', 'ENVO_00000042', 'ENVO_00000079', 'ENVO_00000152', 'ENVO_00000160', 'ENVO_00000252', 'ENVO_00000271', 
        'ENVO_00000282', 'ENVO_00000289', 'ENVO_00000290', 'ENVO_00000470', 'ENVO_00000483', 'ENVO_00000522', 'ENVO_00000548', 'ENVO_00002231', 'ENVO_00005739', 'ENVO_00005756', 'ENVO_00005767', 
        'ENVO_00005775', 'ENVO_01000219', 'ENVO_02000084');
    }
    private function get_descendants_of_saline_water()
    {
        $url = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/AmphibiaWeb/descendants_of_salt_water.csv';
        $local = Functions::save_remote_file_to_local($url, array('cache' => 1));
        $arr = explode("\n", file_get_contents($local));
        $arr = array_map('trim', $arr);
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        unlink($local);
        foreach($arr as $uri) $final[$uri] = '';
        // print_r($final); exit("\n\n");
        return $final;
    }
}
?>