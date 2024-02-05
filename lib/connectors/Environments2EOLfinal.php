<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from Environments2EOLAPI.php; from environments_2_eol.php for DATA-1851] */
class Environments2EOLfinal extends ContributorsMapAPI
{
    function __construct($archive_builder, $resource_id, $params = array())
    {
        // exit("\nObsolete: Vangelis tagger 2.\n");
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->params = $params; //print_r($params); exit("\nelix\n");
        /*Array(
            [task] => generate_eol_tags_pensoft
            [resource] => Pensoft_journals
            [resource_id] => 834_ENV
            [subjects] => GeneralDescription|Distribution
        )*/
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->debug = array();
        /* OLD - Vangelis
        if(Functions::is_production()) $this->root_path = '/u/scripts/vangelis_tagger/';
        else                           $this->root_path = '/opt/homebrew/var/www/vangelis_tagger/';
        */
        // /* NEW - Pensoft
        if(Functions::is_production()) $this->root_path = '/var/www/html/Pensoft_annotator/'; //'/html/Pensoft_annotator/';
        else                           $this->root_path = '/opt/homebrew/var/www/Pensoft_annotator/';
        
        if($resource_id == '617_ENV') {} //Wikipedia EN
        else { //rest of the resources
            $tmp = str_replace('_ENV', '', $resource_id);
            $this->root_path .= $tmp.'/';
            if(!is_dir($this->root_path)) mkdir($this->root_path);
            // exit("\n$this->root_path\n");
        }
        // */
        $this->eol_tags_path        = $this->root_path.'eol_tags/';
        $this->json_temp_path       = $this->root_path.'temp_json/';
        echo "\nEnvironments2EOLfinal resource_id: [$this->resource_id]\n";
        if($this->resource_id == '617_ENV') $this->modulo = 50000; //Wikipedia EN
        else                                $this->modulo = 2000;
        
        // /* Utility: reports for WoRMS
        if(Functions::is_production()) $this->source_tsv = '/var/www/html/Pensoft_annotator/26/eol_tags/eol_tags_noParentTerms.tsv'; //'/html/Pensoft_annotator/26/eol_tags/eol_tags_noParentTerms.tsv';
        else                           $this->source_tsv = '/opt/homebrew/var/www/Pensoft_annotator/26/eol_tags/eol_tags_noParentTerms.tsv';
        // */
    }
    function report_for_WoRMS() //https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65762&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65762
    {
        $i = 0;
        foreach(new FileIterator($this->source_tsv) as $line_number => $row) {
            if(!$row) continue;
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            $arr = explode("\t", $row); //print_r($arr); exit;
            if(!$arr) continue;
            /*Array(
                [0] => 134891_-_WoRMS:note:10
                [1] => 
                [2] => 
                [3] => marine
                [4] => ENVO_00000447
                [5] => eol-geonames
            )*/
            $lbl = $arr[3];
            $uri = $arr[4];
            $ontology = $arr[5];
            if($ontology == "eol-geonames") {
                $final[$lbl][$uri] = '';
            }
        }
        asort($final);      echo "\n1 ".count($final)."\n";
        ksort($final);      echo "\n2 ".count($final)."\n";
        print_r($final);    echo "\n3 ".count($final)."\n";
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {   
        require_library('connectors/RemoveHTMLTagsAPI');
        
        echo "\nresource_id is [$this->resource_id]\n";
        if(in_array($this->resource_id, array('21_ENV'))) {
            $options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'expire_seconds' => 60*60*1);
            $this->contributor_mappings = $this->get_contributor_mappings($this->resource_id, $options); // print_r($this->contributor_mappings);
            echo "\n contributor_mappings: ".count($this->contributor_mappings)."\n"; //exit("\nstop munax\n");
        }
        elseif($this->resource_id == '26_ENV') { //exclusive for WoRMS
            $this->contributor_mappings = $this->get_WoRMS_contributor_id_name_info(); // print_r($this->contributor_mappings);
            echo "\n contributor_mappings: ".count($this->contributor_mappings)."\n"; //exit("\nstop munax\n");
        }
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping -> not needed here
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        END DATA-1841 terms remapping
        self::initialize_mapping(); //for location string mappings
        */
        // /* customize
        echo "\n resource_id is [$this->resource_id]\n";
        if($this->resource_id == '26_ENV') { //this will just populate MoF. Too big in memory to do in DwCA_Utility.php.
            $tables = $info['harvester']->tables;
            $meta = $tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0];
            self::process_table($meta, 'create extension', 'measurementorfact_specific');
            $meta = $tables['http://rs.tdwg.org/dwc/terms/occurrence'][0];
            self::process_table($meta, 'create extension', 'occurrence_specific');
        }

        if(in_array($this->resource_id, array('10088_5097_ENV', '10088_6943_ENV', '118935_ENV', '120081_ENV', '120082_ENV', '118986_ENV', 
            '118920_ENV', '120083_ENV', '118237_ENV', 'MoftheAES_ENV', '30355_ENV', "27822_ENV", "30354_ENV", "119035_ENV", "118946_ENV", "118936_ENV", 
            "118950_ENV", "120602_ENV", "119187_ENV", "118978_ENV", "118941_ENV", "119520_ENV", "119188_ENV",
            '15423_ENV', '91155_ENV'))
                || @$this->params['resource'] == 'all_BHL'
                || stripos($this->resource_id, "SCtZ-") !== false
                || stripos($this->resource_id, "scb-") !== false
                || stripos($this->resource_id, "scz-") !== false
          ) {
            $tables = $info['harvester']->tables;
            
            /* this will just populate Associations. Not available in DwCA_Utility.php. */
            if($tbl = @$tables['http://eol.org/schema/association']) {
                $meta = $tbl[0];
                self::process_table($meta, 'create extension', 'association');
            }

            if(!in_array($this->resource_id, array('118935_ENV'))) { //list-type resources don't have media objects
            }
            
            /* this will populate media_object less the subject#uses */
            if($tbl = @$tables['http://eol.org/schema/media/document']) {
                $meta = $tbl[0];
                self::process_table($meta, 'create extension', 'document');
            }
            else exit("\nditox 100\n");
            
            // /* this will populate MoF xxx_ENV.tar.gz with MoF (size patterns) from xxx.tar.gz 
            if($tbl = @$tables['http://rs.tdwg.org/dwc/terms/measurementorfact']) {
                $meta = $tbl[0];
                self::process_table($meta, 'create extension', 'measurementorfact_specific');
            }
            // */
        }
        
        // */
        self::add_environmental_traits();
        /* Below will be used if there are adjustments to existing MoF and Occurrences
        $tables = $info['harvester']->tables;
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        */
        if($this->debug) {
            if(isset($this->debug['neglect uncooperative contributor'])) {
                echo "\n neglect uncooperative contributor: ".count($this->debug['neglect uncooperative contributor'])." \n";
                unset($this->debug['neglect uncooperative contributor']);
            }
            print_r($this->debug);
        }
    }
    private function initialize_mapping()
    {   
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
    }
    private function borrow_data()
    {
        require_library('connectors/EnvironmentsEOLDataConnector');
        $func = new EnvironmentsEOLDataConnector();
        $this->excluded_uris = $func->excluded_measurement_values(); //from here: https://eol-jira.bibalex.org/browse/DATA-1739?focusedCommentId=62373&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62373
        return $func;
    }
    private function add_environmental_traits()
    {
        echo "\nProcessing...Environments2EOLfinal...$this->resource_id\n";
        $old_func = self::borrow_data(); // print_r($this->excluded_uris); exit("\nexcluded uris\n");
        require_library('connectors/TraitGeneric');
        
        // /* New: customize identifiers
        // exit("\n[".$this->resource_id."]\nxxx\n");
        if($this->resource_id == "TreatmentBank_ENV") $resource_id = "TreatmentB";
        else                                          $resource_id = $this->resource_id;
        // */
        
        $this->func = new TraitGeneric($resource_id, $this->archive_builder);
        $tsv = $this->eol_tags_path.'eol_tags_noParentTerms.tsv';
        $i = 0;
        foreach(new FileIterator($tsv) as $line_number => $row) {
            if(!$row) continue;
            $i++; if(($i % $this->modulo) == 0) echo "\n".number_format($i);
            $arr = explode("\t", $row); // print_r($arr); exit;
            if(!$arr) continue;
            /* Array(
                [0] => 1005_-_1005_distribution.txt
                [1] => 117
                [2] => 122
                [3] => shrubs
                [4] => ENVO:00000300
                [5] => envo
                [6] =>                      --- this is new, a placeholder for measurementType (DATA-1893)
            )*/
            // print_r($arr); exit("\n-stop muna-\n"); //DATA-1893

                if($val = @$arr[6])            $mType = $val; //DATA-1893
            elseif(@$arr[5] == "envo")         $mType = 'http://purl.obolibrary.org/obo/RO_0002303';
            elseif(@$arr[5] == "eol-geonames") $mType = 'http://eol.org/schema/terms/Present';
            elseif(@$arr[5] == "growth")       $mType = 'http://purl.obolibrary.org/obo/FLOPO_0900032';
            else {
                print_r($arr);
                exit("\nERROR: Undefined ontology: [".@$arr[5]."]\nWill terminate now (1).\n");
            }
            
            $arr[0] = str_replace('.txt', '', $arr[0]);
            $a = explode("_-_", $arr[0]);
            $taxonID = @$a[0];
            $identifier = @$a[1];
            $rek = self::retrieve_json($taxonID."_".$identifier); //VERY IMPORTANT: where media taxon object metadata is retrieved and used in MoF
            // print_r($rek); exit("\n-end 1-\n");
            /* sample for 21_ENV
            Array(
                [source] => http://amphibiaweb.org/cgi/amphib_query?where-genus=Abavorana&where-species=nazgul&account=amphibiaweb
                [referenceID] => d08a99802fc760abbbfc178a391f9336; 8d5b9dee4f523c6243387c962196b8e0; 4d496c9853b52d6d4ee443b4a6103cca
                [agentID] => 40dafcb8c613187d62bc1033004b43b9
            )
            */
            /*Array( this saved in json in Pensoft2EOLAPI.php
                [source] => http://dx.doi.org/10.5479/si.00810282.7
                [bibliographicCitation] => Maddocks, Rosalie F. 1969. "Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean." Smithsonian Contributions to Zoology. 1-56. https://doi.org/10.5479/si.00810282.7
            )*/
            if(@$arr[3] && @$arr[4]) {
                // /* post legacy filters: start Aug 5, 2020: /DATA-1851?focusedCommentId=65084&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65084
                $tags_not_to_be_used = array("Playa", "nest", "aquarium", "logged areas", "trenches", "bamboo");

                //new start: customize remove traits per resource:
                if($this->resource_id == '617_ENV') { //Wikipedia EN traits
                    $tags_not_to_be_used[] = "ice";
                }
                //new end

                if(in_array($arr[3], $tags_not_to_be_used)) continue;
                // */
                
                $rec = array();
                $rec["taxon_id"] = $taxonID;
                $rec["catnum"] = md5($row);
                $rec['measurementType'] = $mType;
                
                // /* customized:
                if($this->resource_id == '26_ENV')  $rec['measurementRemarks'] = "";
                else                                $rec['measurementRemarks'] = "source text: \"" . $arr[3] . "\"";
                // */
                
                /* customize --- add text object description to measurementRemarks in MoF
                             --- works OK but no instructions to do so
                             --- part of pair #001 2of2
                if($this->params['resource'] == 'Pensoft_journals') {
                    $tmp = $rec['measurementRemarks'].". ".$rek['measurementRemarks'];
                    $rec['measurementRemarks'] = Functions::remove_whitespace($tmp);
                }
                */
                
                $basename = $arr[4]; //e.g. 'ENVO:00000300'
                if(stripos($basename, "ENVO") !== false) { //string is found
                    $string_uri = 'http://purl.obolibrary.org/obo/'.str_replace(':', '_', $basename);
                }
                else $string_uri = $basename;

                // /* from legacy filters: EnvironmentsEOLDataConnector.php
                if(in_array($string_uri, $this->excluded_uris)) {
                    // echo "\nOh there is one filtered!\n"; //debug only
                    @$this->debug['legacy filter']['excluded uris occurrences']++;
                    continue;
                }
                // */
                
                if($val = @$rek['source']) $rec['source'] = $val;
                else {
                    if($this->resource_id == '26_ENV') {
                        $rec['source'] = "http://www.marinespecies.org/aphia.php?p=taxdetails&id=".$taxonID;
                    }
                }
                if($val = @$rek['bibliographicCitation']) $rec['bibliographicCitation'] = $val;
                if($val = @$rek['referenceID']) $rec['referenceID'] = $val;
                /* old -- commented so no more 'contributor' column in MoF. Only the child record contributors will exist.
                if($val = @$rek['contributor']) $rec['contributor'] = $val;
                if($val = @$rek['agentID'])     $rec['contributor'] = self::format_contributor_using_agentIDs($val);
                */
                // /* new
                $contributor_names = "";
                if($val = @$rek['contributor']) $contributor_names = $val;
                if($val = @$rek['agentID']) {
                    if($contributor_names) $contributor_names .= "; ".self::get_names_from_agentIDs($val);
                    else                   $contributor_names = self::get_names_from_agentIDs($val);
                    
                    // start converting names to URLs
                    /* working but commented as strategy changed once again. No problem as long as script is well documented. Easy to change.
                    $arr = explode(";", $contributor_names);
                    $arr = array_map('trim', $arr);
                    $uris = array();
                    foreach($arr as $contributor) {
                        if($uri = @$this->contributor_mappings[$contributor]) {}
                        else { //no mapping yet for this contributor
                            $this->debug['undefined contributor'][$contributor] = '';
                            $uri = $contributor;
                        }
                        $uris[$uri] = '';
                    }
                    $uris = array_keys($uris);
                    $rec["contributor"] = implode(";", $uris);
                    */
                }
                // */
                
                // /* from legacy filters: EnvironmentsEOLDataConnector.php
                $rec['measurementValue'] = $string_uri;
                if($rec = $old_func->adjustments($rec)) {
                    
                    // /* get first contributor - should be a contributor column
                    $arr = explode(";", $contributor_names);
                    $arr = array_map('trim', $arr);
                    $first = $arr[0];
                    if($uri = @$this->contributor_mappings[$first]) {}
                    else { //no mapping yet for this contributor

                        // $this->debug['neglect uncooperative contributor 1'][$first] = '';
                        if(!isset($this->contributor_mappings[$first])) $this->debug['neglect uncooperative contributor'][$first] = '';

                        /* neglect the most uncooperative strings in any resource for contributor, compiler or determinedBy: per https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=66158&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66158
                        $uri = $first; */
                        $uri = '';
                    }
                    // first contributor is a column, the rest goes as child MoF. First client AmphibiaWeb text (21_ENV). I guess goes for all resources
                    $rec["contributor"] = $uri;
                    // */
                    
                    $ret = $this->func->add_string_types($rec, $rec['measurementValue'], $rec['measurementType'], "true");
                    $parentID = $ret['measurementID'];
                    
                    // /* start adding child records - contributor -- working but a mistake since contributors must be columns in MoF, not child.
                    if($contributor_names) {
                        $rex = array();
                        $rex["taxon_id"] = $rec["taxon_id"];
                        $rex["catnum"] = $rec["catnum"];
                        $rex['parentMeasurementID'] = $parentID;
                        $arr = explode(";", $contributor_names);
                        $arr = array_map('trim', $arr);
                        $cnt = 0;
                        foreach($arr as $contributor) { $cnt++;
                            if($uri = @$this->contributor_mappings[$contributor]) {}
                            else { //no mapping yet for this contributor
                                
                                // $this->debug['neglect uncooperative contributor 2'][$contributor] = '';
                                if(!isset($this->contributor_mappings[$contributor])) $this->debug['neglect uncooperative contributor'][$contributor] = '';
                                
                                /* neglect the most uncooperative strings in any resource for contributor, compiler or determinedBy: per https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=66158&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66158
                                $uri = $contributor; */
                                $uri = '';
                            }
                            /* first contributor is a column, the rest goes as child MoF. First client AmphibiaWeb text (21_ENV). I guess goes for all resources */
                            if($cnt == 1) {}
                            else {
                                if($uri) $this->func->add_string_types($rex, $uri, 'http://purl.org/dc/terms/contributor', "child");
                            }
                        }
                    }
                    // */
                    
                }
                // */
            }
        }
    }
    private function get_names_from_agentIDs($agendIDs) //assumed agendIDs are semi-colon separated values
    {
        $final = array();
        $ids = explode(";", trim($agendIDs));
        $ids = array_map('trim', $ids);
        foreach($ids as $id) {
            $arr = self::retrieve_json('agent_'.$id);
            if(!@$arr) continue;
            $arr = array_map('trim', $arr);
            if(!@$arr['term_name']) continue;
            /* Array(
                [identifier] => e4caf6a093328770804c83ba12c4e52c
                [term_name] => Albertina P. Lima
                [agentRole] => author
                [term_homepage] => http://eol.org
            )*/
            // print_r($arr); exit("\neli 100\n");
            unset($arr['identifier']);
            if($val = $arr['term_name']) {
                $final[$val] = '';
            }
            /* copied template
            if($val = @$arr['agentRole']) $final .= " ($val).";
            */
        }
        $final = array_keys($final);
        return implode(";", $final);
    }
    private function format_contributor_using_agentIDs($agendIDs) //assumed agendIDs are semi-colon separated values
    {
        $final = '';
        $ids = explode(";", trim($agendIDs));
        $ids = array_map('trim', $ids);
        foreach($ids as $id) {
            $arr = self::retrieve_json('agent_'.$id);
            if(!@$arr) continue;
            $arr = array_map('trim', $arr);
            if(!@$arr['term_name']) continue;
            /* Array(
                [identifier] => e4caf6a093328770804c83ba12c4e52c
                [term_name] => Albertina P. Lima
                [agentRole] => author
                [term_homepage] => http://eol.org
            )*/
            // print_r($arr); exit("\neli 100\n");
            unset($arr['identifier']);
            if($val = $arr['term_name']) $final .= " $val";
            if($val = @$arr['agentRole']) $final .= " ($val).";
            if($homepage = @$arr['term_homepage']) {
                if(self::valid_url($homepage)) $final .= " $homepage";
            }
        }
        return trim($final);
    }
    private function retrieve_json($id)
    {
        $file = self::build_path($id);
        if(is_file($file)) {
            $json = file_get_contents($file); // echo "\nRetrieved OK [$id]";
            return json_decode($json, true);
        }
        // else echo("\nFile not found [$id] [$file]\n"); //It means that the record doesn't have any attribution so no file was saved.
    }
    private function build_path($id) //$id is "$taxonID_$identifier"
    {
        $filename = "$id.json";
        $md5 = md5($id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        return $this->json_temp_path . "$cache1/$cache2/$filename";
    }
    private function valid_url($url)
    {
        if(substr($url, 0, 4) == 'http') return true;
    }
    /* Below will be used if there are adjustments to existing MoF and Occurrences
    private function process_measurementorfact($meta)
    {}
    private function process_occurrence($meta)
    {}
    */
    private function process_table($meta, $what, $class) //a generic method to populate an extension.
    {   //print_r($meta);
        echo "\nprocess_table [$what][$class] xyz\n";
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
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
            // print_r($rec); exit;
            /**/
            if($what == 'create extension') {
                if    ($class == "vernacular")          $o = new \eol_schema\VernacularName();
                elseif($class == "agent")               $o = new \eol_schema\Agent();
                elseif($class == "reference")           $o = new \eol_schema\Reference();
                elseif($class == "taxon")               $o = new \eol_schema\Taxon();
                elseif($class == "document")            $o = new \eol_schema\MediaResource();
                elseif($class == "occurrence")          $o = new \eol_schema\Occurrence();
                elseif($class == "measurementorfact")   $o = new \eol_schema\MeasurementOrFact();
                elseif($class == "occurrence_specific")          $o = new \eol_schema\Occurrence_specific();
                elseif($class == "measurementorfact_specific")   $o = new \eol_schema\MeasurementOrFact_specific();
                elseif($class == "association")   $o = new \eol_schema\Association();

                $uris = array_keys($rec);
                
                // /* start customized
                if($this->resource_id == '26_ENV') { //in MoF, exclude where mType = Present. These are those orig location text from WoRMS
                                                     //in Occurrence, exclude respective occurrence record
                    $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                    if($class == "measurementorfact_specific") {
                        if($rec['http://rs.tdwg.org/dwc/terms/measurementType'] == 'http://eol.org/schema/terms/Present') {
                            $this->occurrenceIDs_to_delete[$occurrenceID] = '';
                            continue;
                        }
                    }
                    elseif($class == "occurrence_specific") {
                        if(isset($this->occurrenceIDs_to_delete[$occurrenceID])) continue;
                        
                        // /* this is manual removal, an additional manual fix from: https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=66382&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66382
                        if($lifeStage = @$rec['http://rs.tdwg.org/dwc/terms/lifeStage']) {
                            if($lifeStage == 'stage') $rec['http://rs.tdwg.org/dwc/terms/lifeStage'] = "";
                        }
                        // */
                    }
                }
                // */
                
                // /*
                if(in_array($this->resource_id, array('10088_5097_ENV', '10088_6943_ENV', '118935_ENV', '120081_ENV', '120082_ENV', '118986_ENV', 
                    '118920_ENV', '120083_ENV', 
                    '118237_ENV', 'MoftheAES_ENV', '30355_ENV', "27822_ENV", "30354_ENV", "119035_ENV", "118946_ENV", "118936_ENV", "118950_ENV", 
                    "120602_ENV", "119187_ENV", "118978_ENV", "118941_ENV", "119520_ENV", "119188_ENV",
                    '15423_ENV', '91155_ENV'))
                        || @$this->params['resource'] == 'all_BHL'
                        || stripos($this->resource_id, "SCtZ-") !== false
                        || stripos($this->resource_id, "scb-") !== false
                        || stripos($this->resource_id, "scz-") !== false
                  ) { //create document but excluded subject#use
                    // print_r($rec); exit("\nexit muna Eli...\n");
                    /*Array(
                        [http://purl.org/dc/terms/identifier] => 9f2b9ca8dae440e29db6d83475a962f7
                        [http://rs.tdwg.org/dwc/terms/taxonID] => e17367c269d607dcc99ec90ab8a4861e
                        [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
                        [http://purl.org/dc/terms/format] => text/html
                        [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses
                        [http://purl.org/dc/terms/description] => Paratrygon aieraba (Müller and Henle, 1841), AM, Raya amazónica; R. Rosa, pers. comm. List of Freshwater Fishes of Peru
                        [http://purl.org/dc/terms/language] => en
                        [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by-nc-sa/3.0/
                        [http://rs.tdwg.org/ac/terms/additionalInformation] => List of Freshwater Fishes of Peru
                    )*/
                    if(@$rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] == 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses') continue;
                }
                // */
                
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];

                    // /* new: Oct 19, 2023
                    if(in_array($field, array("full_reference", "primaryTitle", "title", "description", "bibliographicCitation"))) $o->$field = RemoveHTMLTagsAPI::remove_html_tags($o->$field);
                    // */

                }
                $this->archive_builder->write_object_to_file($o);
            }
        }
    }
}
?>