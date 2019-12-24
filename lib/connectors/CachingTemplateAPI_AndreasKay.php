<?php
namespace php_active_record;
/* A template for caching calls*
This template is for DATA-1843: Andreas Kay resource
Future clients, can just copy this template and edit accordingly
*/
class CachingTemplateAPI_AndreasKay
{
    function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        $this->download_options = array(
            'resource_id'        => $resource_id,  //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //should not expire
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        $this->expire_seconds_specific = $this->download_options['expire_seconds'];

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache/";      //used in Functions.php for all general cache
        $this->main_path = $this->download_options['cache_path'].$this->download_options['resource_id']."/";
        if(!is_dir($this->main_path)) mkdir($this->main_path);
        $this->api['simple text'] = "https://gnrd.globalnames.org/name_finder.json?text=PLUS_SEPARATED_STRINGS";
        $this->api['scinames']    = 'https://gnrd.globalnames.org/name_finder.json?text=PLUS_SEPARATED_STRINGS&preferred_data_sources=12&unique=true';
        $this->file['pseudo_binomials_not_in_GNRD'] = CONTENT_RESOURCE_LOCAL_PATH . "/reports/".$this->resource_id."_pseudo_binomials_not_in_GNRD_temp.txt";
        
        $this->count['media with machine tags'] = 0;
        $this->count['lack machine tags'] = 0; //meaning without taxon tags format
        $this->count['lack machine tags but with binomials that we matched to taxon names'] = 0;
        $this->count['lack machine tags but with pseudo binomials that we matched to taxon names'] = 0;
        $this->count['lack machine tags but with non-binomials that we matched to taxon names'] = 0;
        $this->count['media with tags but nothing we can match'] = 0;
    }
    /* ======================================= start Andreas Kay functions ======================================= */
    public function AndreasKay_addtl_taxon_assignment($tags, $allowsQuestionMarksYN)
    {
        $GLOBALS['allowsQuestionMarksYN'] = $allowsQuestionMarksYN;
        $parameters = self::step1and2_look_for_binomials($tags, false); //2nd param false means NOT pseudo binomials
        if(@$parameters['scientificName']) {
            debug("\nStep 1 OK ".$GLOBALS['allowsQuestionMarksYN'].".\n");
            $this->count['lack machine tags but with binomials that we matched to taxon names']++;
            return $parameters;
        }
        else {
            debug("\nStep 1 failed ".$GLOBALS['allowsQuestionMarksYN'].".\n");
            $parameters = self::step1and2_look_for_binomials($tags, true); //2nd param true means pseudo binomials
            if(@$parameters['scientificName']) {
                debug("\nStep 2 OK ".$GLOBALS['allowsQuestionMarksYN'].".\n");
                $this->count['lack machine tags but with pseudo binomials that we matched to taxon names']++;
                return $parameters;
            }
            else {
                debug("\nStep 2 failed ".$GLOBALS['allowsQuestionMarksYN'].".\n");
                $parameters = self::step3_look_for_any_name_among_tags($tags);
                if(@$parameters['scientificName']) {
                    debug("\nStep 3 OK ".$GLOBALS['allowsQuestionMarksYN'].".\n");
                    $this->count['lack machine tags but with non-binomials that we matched to taxon names']++;
                    return $parameters;
                }
                else {
                    debug("\nStep 3 failed ".$GLOBALS['allowsQuestionMarksYN'].".\n");
                    // $this->count['media with tags but nothing we can match']++;
                    return false;
                }
            }
        }
    }
    private function step3_look_for_any_name_among_tags($tags)
    {
        if($name = self::pick_a_name_among_tags($tags)) {
            $final['scientificName'][] = $name;
            return $final;
        }
    }
    private function step1and2_look_for_binomials($tags, $pseudoBinomialsYN)
    {
        $binomials = self::get_binomials_from_tags($tags, $pseudoBinomialsYN);
        foreach($binomials as $name) {
            if($name) $final['scientificName'][] = $name;
        }
        return @$final;
    }
    private function get_binomials_from_tags($tags, $pseudoBinomialsYN)
    {   /*[9] => stdClass Object(
            [id] => 75329200-48862446481-3048
            [author] => 75374522@N06
            [authorname] => In Memoriam: Ecuador Megadiverso
            [raw] => rainforest
            [_content] => rainforest
            [machine_tag] => 0
        )*/

        /* debug only, good test during development
        $tags[] = (object) array('raw' => 'Gadus morhua');
        $tags[] = (object) array('raw' => 'Lates niloticus');
        */
        /* debug only, good test during development
        unset($tags[8]);
        $tags[] = (object) array('raw' => 'Gadusxyz sp.'); //debug only, good test during development
        */
        /* debug only, good test during development
        $tags = array();
        $tags[] = (object) array('raw' => 'Gadus sp.?'); //debug only, good test during development
        */
        /* debug only, good test during development
        unset($tags[8]);
        */
        // print_r($tags); exit;
        
        $final = array();
        foreach($tags as $tag) {
            if($GLOBALS['allowsQuestionMarksYN']) {
                $tag->raw = str_replace('?', '', $tag->raw);
            }
            else {
                if(stripos($tag->raw, "?") !== false) continue; //string is found
            }
            
            $arr = explode(" ", trim($tag->raw));
            $arr = array_map('trim', $arr);
            if(count($arr) == 2) {
                if(ctype_upper(substr($arr[0],0,1))) {
                    if(ctype_lower(substr($arr[1],0,1))) {
                        
                        if(!$pseudoBinomialsYN) {
                            if(!in_array($arr[1], array('sp.', 'sp'))) { // print_r($arr);
                                $binomial = implode(" ", $arr);
                                $final[] = self::check_name_in_GlobalNamesRecognitionDiscovery($binomial, false);
                            }
                            else continue;
                        }
                        else { //a pseudo binomial
                            if(in_array($arr[1], array('sp.', 'sp'))) { // print_r($arr);
                                $binomial = $arr[0];
                                $final[] = self::check_name_in_GlobalNamesRecognitionDiscovery($binomial, true);
                            }
                            else continue;
                        }
                        
                    }
                    else continue;
                }
                else continue;
            }
            else continue;
        }
        // print_r($final); exit("\n111\n");
        $final = array_map('trim', $final);
        return $final;
    }
    /* ======================================== end Andreas Kay functions ======================================== */
    private function check_name_in_GlobalNamesRecognitionDiscovery($str, $pseudoBinomialsYN)
    {
        $obj = self::get_GNRD_output($str, $pseudoBinomialsYN);
        /*stdClass Object(
            [token_url] => https://gnrd.globalnames.org/name_finder.json?token=5r08vf81iv
            [input_url] => 
            [file] => 
            [status] => 200
            [engine] => gnfinder
            [unique] => 
            [verbatim] => 1
            [parameters] => stdClass Object(
                    [return_content] => 
                    [with_verification] => 
                    [preferred_data_sources] => Array(
                        )
                    [detect_language] => 
                    [engine] => 0
                    [no_bayes] => 
                )
            [names] => Array(
                    [0] => stdClass Object(
                            [verbatim] => Lates niloticus
                            [scientificName] => Lates niloticus
                            [offsetStart] => 0
                            [offsetEnd] => 15
                        )
                )
            [language_used] => eng
            [execution_time] => stdClass Object(
                    [text_preparation_duration] => 0.0042290687561035
                    [find_names_duration] => 0.0027587413787842
                    [total_duration] => 0.0098972320556641
                )
            [total] => 1
            [queue_size] => 0
        )
        */
        if($recs = @$obj->names) {
            foreach($recs as $rec) {
                // print_r($rec); //exit("\n222\n");
                if($rec->verbatim == $rec->scientificName) {
                    return $rec->scientificName;
                }
            }
        }
        else {
            // print_r($obj); //exit("\nNo rec\n");
        }
    }
    public function get_GNRD_output($tc_id, $pseudoBinomialsYN) //this is the function called remotely. $tc_id is the name string.
    {
        if($obj = self::retrieve_GNRD_output($tc_id)) {
            if(@$obj->names[0]->verbatim || @$obj->names[0]->scientificName) return $obj;
            else {
                if($pseudoBinomialsYN) { //write report for Katja. Names that are pseudo binimials but GNRD doesn't recognize it
                    // echo "\nWriting report...";
                    $file = $this->file['pseudo_binomials_not_in_GNRD'];
                    $WRITE = Functions::file_open($file, "a");
                    fwrite($WRITE, $tc_id . "\n");
                    fclose($WRITE);
                }
            }
        }
        else exit("\nInvestigate: went here [$tc_id]\n");
    }
    public function initialize_report()
    {
        $WRITE = Functions::file_open($this->file['pseudo_binomials_not_in_GNRD'], "w");
        fclose($WRITE);
    }
    public function make_unique_rows()
    {
        $file = $this->file['pseudo_binomials_not_in_GNRD'];
        $destination = str_replace('_temp', '', $file);
        $lines = file($file);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines); //remove null arrays
        $lines = array_unique($lines); //make unique
        $lines = array_values($lines); //reindex key
        asort($lines); //print_r($lines);
        $WRITE = Functions::file_open($destination, "w");
        fwrite($WRITE, implode("\n", $lines)."\n");
        fclose($WRITE);
    }
    private function retrieve_GNRD_output($tc_id)
    {
        /* No longer needed for Andreas Kay resource. But maybe needed for other resources who'll use this template.
        $filename = self::generate_path_filename($tc_id);
        if(file_exists($filename)) {
            if($GLOBALS['ENV_DEBUG']) echo "\nCache already exists. [$filename]\n";
            $file_age_in_seconds = time() - filemtime($filename);
            if($file_age_in_seconds < $this->expire_seconds_specific) return self::retrieve_json($filename); //not yet expired
            if($this->expire_seconds_specific === false)              return self::retrieve_json($filename); //doesn't expire

            if($GLOBALS['ENV_DEBUG']) echo "\nCache expired. Will run cypher now...\n";
            self::run_query($tc_id, $filename);
            return self::retrieve_json($filename);
        }
        else {
            if($GLOBALS['ENV_DEBUG']) echo "\nRun cypher query...\n";
            self::run_query($tc_id, $filename);
            return self::retrieve_json($filename);
        }
        */
        return self::run_query($tc_id);
    }
    private function retrieve_json($filename)
    {
        $json = file_get_contents($filename);
        return json_decode($json, true);
    }
    private function run_query($tc_id, $filename = NULL)
    {
        $saved = array();
        $sciname = str_replace(' ', '+', $tc_id);
        $url = str_replace('PLUS_SEPARATED_STRINGS', $sciname, $this->api['simple text']);
        debug("\naccessing [$url]\n");
        $json = Functions::lookup_with_cache($url, $this->download_options);
        $obj = json_decode($json);
        // print_r($obj); //exit("\nstop 500\n");
        /*stdClass Object(
            [token_url] => https://gnrd.globalnames.org/name_finder.json?token=38aaa487ru
            [input_url] => 
            [file] => 
            [status] => 303
            [engine] => gnfinder
            [unique] => 
            [verbatim] => 1
            [parameters] => stdClass Object(
                    [return_content] => 
                    [with_verification] => 
                    [preferred_data_sources] => Array(
                        )
                    [detect_language] => 
                    [engine] => 0
                    [no_bayes] => 
                )
        )*/
        
        if($obj = self::process_obj_output($obj)) return $obj;
        else return false;
    }
    private function process_obj_output($obj)
    {
        if($obj->status == 200) return $obj;    //sometimes it goes here.
        elseif($obj->status == 303) {           //status 303 means you need to run 2nd token_url
            debug("\naccessing [$obj->token_url]\n");
            $json = Functions::lookup_with_cache($obj->token_url, $this->download_options);
            $obj2 = json_decode($json);
            if($obj2->status == 200) {
                return $obj2;
            }
            elseif($obj2->status == 303) {
                debug("\nstill 303 1\n");
                if($GLOBALS['ENV_DEBUG']) print_r($obj2);
                if($obj->token_url == $obj2->token_url) {
                    $obj3 = self::try_again_obj($obj2);
                    if($obj3->status == 200) return $obj3;
                    elseif($obj3->status == 303) {
                        debug("\nstill 303 2\n");
                        if($GLOBALS['ENV_DEBUG']) print_r($obj3);
                        sleep(10);
                        $obj4 = self::try_again_obj($obj3);
                        if($obj4->status == 200) return $obj4;
                        elseif($obj4->status == 303) {
                            debug("\nstill 303 3\n");
                            if($GLOBALS['ENV_DEBUG']) print_r($obj4);
                            exit("\nMight need to investigate: still 303\ntoken_url: [$obj4->token_url]\n");
                        }
                    }
                }
                exit("\nMight not go here anymore...\n");
            }
            /* No longer needed for Andreas Kay resource. But maybe needed for other resources who'll use this template.
            print_r($obj); exit;
            $WRITE = Functions::file_open($filename, "w");
            fwrite($WRITE, json_encode($obj)); fclose($WRITE);
            if($GLOBALS['ENV_DEBUG']) echo "\nSaved OK [$filename]\n";
            */
        }
        else return false;
    }
    private function try_again_obj($obj)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 0;
        $json = Functions::lookup_with_cache($obj->token_url, $options);
        $obj_new = json_decode($json);
        return $obj_new;
    }
    private function pick_a_name_among_tags($tags)
    {
        foreach($tags as $tag) @$words .= " $tag->raw";
        $words = Functions::remove_whitespace(trim($words));
        // echo "\nwords: [$words]\n";
        $words = str_replace(' ', '+', $words);
        $url = str_replace('PLUS_SEPARATED_STRINGS', $words, $this->api['scinames']);
        debug("\naccessing [$url]\n");
        $json = Functions::lookup_with_cache($url, $this->download_options);
        $obj = json_decode($json);
        if($obj = self::process_obj_output($obj)) {
            if($considered_scinames_by_GNRD = self::get_considered_scinames_by_GNRD($obj)) {
                /* return all binomials, if any */
                
            }
            else return false; //meaning no scinames found in Flickr tags
            
            $classification_paths = array();
            if($verified_names = @$obj->verified_names) {
                foreach($verified_names as $verified) {
                    if($val = $verified->results->classification_path) $classification_paths[$val] = '';
                    foreach($verified->preferred_results as $another) {
                        if($path = @$another->classification_path) $classification_paths[$path] = '';
                    }
                }
            }
            // print_r($classification_paths);
            /*Array(
                [Eucarya|Opisthokonta|Metazoa|Eumetazoa|Triploblastica|Bilateria|Eubilateria|Protostomia|Ecdysozoa|Panarthropoda|Arthropoda|Mandibulata|Pancrustacea|Hexapoda|Insecta|Endopterygota|Coleopterida|Coleoptera|Polyphaga|Cucujiformia|Chrysomeloidea|Chrysomelidae|Cassidinae] => 
                [Animalia|Arthropoda|Insecta|Coleoptera|Chrysomeloidea|Chrysomelidae] => 
                [Animalia|Arthropoda|Insecta|Coleoptera] => 
            )*/
            $classification_paths = array_keys($classification_paths);
            if(!$classification_paths) return false;
            /* Now get the most number of members in the classification. Most number means, most specific taxon. */
            $i = -1;
            $old_count = 0;
            /* just for testing...
            $test[] = $classification_paths[1];
            $test[] = $classification_paths[0];
            $test[] = $classification_paths[2];
            $classification_paths = $test;
            */
            // print_r($classification_paths);
            foreach($classification_paths as $path) { $i++;
                $arr = explode("|", $path);
                $current_count = count($arr);
                if($current_count > $old_count) $choice = $i;
                $old_count = $current_count;
            }
            // echo "\nfinal choice: [$choice]\n";
            $final_path = $classification_paths[$choice];
            $final_path = explode("|", $final_path);
            // print_r($final_path);
            $taxon = array_pop($final_path);
            // echo "\n$taxon\n";
            /* Last check is if the $taxon is in $considered_scinames_by_GNRD */
            if(in_array($taxon, $considered_scinames_by_GNRD)) return $taxon;
            else {
                if(in_array($GLOBALS['photo_id'], array('28428621653'))) return $taxon; //valid 
                else {
                    /*Array
                    (
                        [need to investigate] => Variimorda
                        [photo_id] => 37170462840
                        [considered_scinames_by_GNRD] => Array
                            (
                                [0] => Coleoptera
                                [1] => Mordellidae
                                [2] => Variimorda pustulosa
                            )

                        [words] => Andreas+Kay+beetle+Coleoptera+Ecuador+Mordellidae+Orkidea+Lodge+Tumbling+Flower+Beetle+Variimorda+pustulosa?
                        [classification_paths] => Array
                            (
                                [0] => Animalia|Arthropoda|Insecta|Coleoptera
                                [1] => Animalia|Arthropoda|Insecta|Coleoptera|Tenebrionoidea|Mordellidae
                                [2] => Biota|Animalia|Arthropoda|Hexapoda|Insecta|Coleoptera|Mordellidae|Variimorda
                            )
                    )
                    first:
                    run "considered_scinames_by_GNRD" againts our original first step: "AndreasKay_addtl_taxon_assignment($tags, $allowsQuestionMarksYN)"
                    */
                    if(AndreasKay_addtl_taxon_assignment) {
                        
                    }
                    else {
                        $arr = array('need to investigate'=>$taxon, 'photo_id'=>$GLOBALS['photo_id'], 
                                     'considered_scinames_by_GNRD'=>$considered_scinames_by_GNRD, 'words'=>$words, 'classification_paths'=>$classification_paths);
                        print_r($arr); //exit("\nNeed to investigate\n");
                    }
                    return $taxon;
                }
            }
        }
        else return false;
        // exit("\n");
        // Cassidinae
        // Chrysomelidae
        // Coleoptera
        // https://gnrd.globalnames.org/name_finder.json?text=Cassidinae+Chrysomelidae+Coleoptera&preferred_data_sources=12&unique=true
    }
    private function get_considered_scinames_by_GNRD($obj)
    {
        $final = array();
        foreach($obj->names as $n) $final[$n->scientificName] = '';
        return array_keys($final);
    }
    private function generate_path_filename($tc_id)
    {
        $main_path = $this->main_path;
        $md5 = md5($tc_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$tc_id.json";
        return $filename;
    }
}
?>