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
    {
        self::lookup_opendata_resource();
        // /* un-comment in real operation
        self::initialize_files();
        // */
        $info = self::parse_dwca($resource); // print_r($info); exit;
        $tables = $info['harvester']->tables;
        print_r(array_keys($tables)); //exit;
        // /* un-comment in real operation
        self::process_table($tables['http://eol.org/schema/media/document'][0]); //generates individual text files & runs environment tagger
        // exit("\nDebug early exit...\n"); //if u want to investigate the individual text files.
        print_r($this->debug);
        
        // /* report for Jen - 'difference' report
        self::generate_difference_report(); exit("\n-end report-\n");
        // */
        
        // These 3 may not be needed anymore for Pensoft
        // self::clean_eol_tags_tsv(); //remove rows with author-like strings e.g. "Hill S", "Urbani C"
        // self::gen_noParentTerms();
        // self::clean_noParentTerms(); //concatenate e.g. "cliff|cliffs"
        
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
        if($this->param['resource_id'] == '617_ENV') $excluded_rowtypes = array('http://eol.org/schema/media/document');
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
        $this->all_envo_terms = array_keys($this->all_envo_terms);
        print_r($this->all_envo_terms); //exit;
        foreach($this->all_envo_terms as $t) $pensoft_envo_terms[] = pathinfo($t, PATHINFO_BASENAME);
        $envo_from_entities = self::get_envo_from_entities_file();
        // print_r($envo_from_entities); exit;
        $difference = array_diff($pensoft_envo_terms, $envo_from_entities);
        echo "\n pensoft_envo_terms: ".count($pensoft_envo_terms);
        echo "\n envo_from_entities: ".count($envo_from_entities);
        echo "\n difference: ".count($difference)."\n";
        $difference = array_values($difference); //reindex key
        print_r($difference);
        exit("\n-end difference report-\n");
    }
    private function get_envo_from_entities_file()
    {
        $local = Functions::save_remote_file_to_local($this->entities_file, array('cache' => 1, 'expire_seconds' => 60*60*24));
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
        return array_keys($final);
    }
    function clean_eol_tags_tsv()
    {   echo "\nCleaning eol_tags.tsv...\n";
        if(copy($this->eol_tags_path."eol_tags.tsv", $this->eol_tags_path."eol_tags.tsv.old")) echo "\nCopied OK (eol_tags.tsv)\n";
        else exit("\nERROR: Copy failed (eol_tags.tsv)\n");
        $f = Functions::file_open($this->eol_tags_path."eol_tags.tsv", "w");
        $file = $this->eol_tags_path."eol_tags.tsv.old"; $i = 0;
        foreach(new FileIterator($file) as $line => $row) {
            $i++; //if(($i % $this->modulo) == 0) echo "\n".number_format($i);
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            // print_r($tmp); exit;
            /*Array(
                [0] => Q140_-_3534a7422ad054e6972151018c05cb38.txt
                [1] => 868
                [2] => 877
                [3] => grasslands
                [4] => ENVO:00000106
            )*/
            $env_str = $tmp[3];
            if(is_string(substr($env_str,0,1))) { //always starts with a letter. Never a number.
                // exit("\n$env_str\n");
                if(self::is_environment_string_valid($env_str)) fwrite($f, $row."\n");
            }
            else {
                print_r($tmp);
                exit("\nInvestigate, first char not letter.\n");
            }
        }
        /* un-comment if you want to see removed invalid environment strings
        if($val = @$this->debug['removed']) print_r($val);
        */
        fclose($f);
        $out = shell_exec("wc -l " . $this->eol_tags_path."eol_tags.tsv.old"); echo "\n eol_tags.tsv.old ($out)\n";
        $out = shell_exec("wc -l " . $this->eol_tags_path."eol_tags.tsv");     echo "\n eol_tags.tsv ($out)\n";
    }
    private function is_environment_string_valid($str)
    {
        // "Hill S" or "Urbani C" -> invalid author-like strings...
        $arr = explode(" ", $str);
        if($second_word = @$arr[1]) { //means multiple words
            $first_char_of_first_word = substr($arr[0], 0, 1);
            if(strlen($second_word) == 1 && ctype_upper($first_char_of_first_word)) {
                $this->debug['removed'][$str] = ''; //echo "\ninvalid $str\n";
                return false;
            }
        }
        return true;
    }
    private function initialize_files()
    {
        // /* copied template, not needed in Pensoft yet
        $files = array($this->eol_tags_destination, $this->eol_tags_path.'eol_tags_noParentTerms.tsv');     //Vangelis tagger
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
        $paths = Array("archive_path" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_71886/",
                       "temp_dir" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_71886/");
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
                self::save_article_2_txtfile($rec);
                // exit("\nstop muna\n");
            }
            // if($i >= 5) break; //debug only
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
            foreach($this->results as $uri => $label) {
                $arr = array($basename, '', '', $label, pathinfo($uri, PATHINFO_FILENAME));
                fwrite($f, implode("\t", $arr)."\n");
            }
            fclose($f);
        }
    }
    private function retrieve_annotation($id, $desc)
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
    }
    private function retrieve_partial($id, $desc, $loop)
    {
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
            if(ctype_lower(substr($rek['lbl'],0,1))) {
                $this->results[$rek['id']] = $rek['lbl'];
                $this->all_envo_terms[$rek['id']] = $rek['lbl']; //for stats only - report for Jen
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
}
?>