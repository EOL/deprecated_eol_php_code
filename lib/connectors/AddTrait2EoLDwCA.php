<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from zookeys_add_trait.php for DATA-1897] */
class AddTrait2EoLDwCA
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        // $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        // require_library('connectors/TraitGeneric'); 
        // $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        // $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */
        
        $tables = $info['harvester']->tables;
        
        if($this->resource_id == "20_ENV_final") { //1st client: Zookeys (20_ENV.tar.gz)
            // /* initialize to access/re-use two functions: run_gnparser() AND run_gnverifier()
            require_library('connectors/Functions_Memoirs');
            require_library('connectors/ParseListTypeAPI_Memoirs');
            require_library('connectors/ParseUnstructuredTextAPI_Memoirs'); 
            $this->func2 = new ParseUnstructuredTextAPI_Memoirs(false, false);
            $desc = "host:Sillaginodes punctatus (Cuvier) (Sillaginidae), Sillago bassensis Cuvier (Sillaginidae). Eli boy";
            $desc = "hosts:Sillago maculata Quoy & Gaimard (Sillaginidae). xxx";
            $desc = "host:Passalus interstitialis Escholtz, 1829 (Coleoptera: Passalidae). xxx";
            $desc = "host:Arripis georgianus (Valenciennes) (Arripidae: Perciformes), Australian ruff. xxx";
            $desc = "host:Pseudocaranx wrighti (Whitley) (Carangidae: Perciformes), skipjack trevally. xxx";
            // $arr = $this->func2->run_gnparser($desc); print_r($arr); //exit;
            // $arr = $this->func2->run_gnverifier($desc); print_r($arr); exit;
            /* just test
            $names = self::search_host_traits($desc);
            print_r($names);
            exit("\n-end test-\n");
            */
            // */
            self::process_table($tables['http://eol.org/schema/media/document'][0], 'read_text_then_process_trait');
        }
        
        /* copied template
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);
        */
    }
    private function process_table($meta, $task)
    {   //print_r($meta);
        echo "\n task [$task]...\n"; $i = 0;
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
            } //print_r($rec); exit;
            
            if($this->resource_id == "20_ENV_final") { //1st client: Zookeys (20_ENV.tar.gz)
                $CVterm = $rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'];
                if($CVterm == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution") { //specific text object for Zookeys //print_r($rec); exit;
                    /*Array(
                        [http://purl.org/dc/terms/identifier] => zookeys.1.8.sp1_distribution
                        [http://rs.tdwg.org/dwc/terms/taxonID] => zookeys.1.8.sp1
                        [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
                        [http://purl.org/dc/terms/format] => text/html
                        [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
                        [http://purl.org/dc/terms/title] => Distribution
                        [http://purl.org/dc/terms/description] => Type-host: Apogon fasciatus (White) (Apogonidae). Other hosts: Sillaginodes punctatus (Cuvier) (Sillaginidae), Sillago bassensis Cuvier (Sillaginidae). Site: Intestine/gut. Type-locality: Moreton Bay, off Tangalooma, Queensland, 27°14'S, 153°19'E. Other localities: Off Mandurah, 32°31'S, 115°41'E & off Point Peron, Western Australia, 32°18'S, 115°38'E., off American River, South Australia 35°48'S 137°46'E.
                        [http://rs.tdwg.org/ac/terms/accessURI] => 
                        [http://eol.org/schema/media/thumbnailURL] => 
                        [http://rs.tdwg.org/ac/terms/furtherInformationURL] => 
                        [http://ns.adobe.com/xap/1.0/CreateDate] => 
                        [http://purl.org/dc/terms/language] => en
                        [http://purl.org/dc/terms/audience] => Expert users
                        [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by/3.0/
                        [http://ns.adobe.com/xap/1.0/rights/Owner] => 
                        [http://purl.org/dc/terms/bibliographicCitation] => 
                        [http://eol.org/schema/agent/agentID] => 
                        [http://eol.org/schema/reference/referenceID] => doi: 10.3897/zookeys.1.8
                    )*/
                    $traits = self::parse_text_object($rec);
                }
            }
            // https://parser.globalnames.org/api/v1/Pseudocaranx dentex (Bloch et Schneider)(Carangidae: Perciformes), white trevally
            // https://verifier.globalnames.org/api/v1/verifications/Pseudocaranx dentex (Bloch et Schneider) (Carangidae: Perciformes), white trevally
            // https://parser.globalnames.org/api/v1/Sillago maculata Quoy & Gaimard (Sillaginidae)
            // https://verifier.globalnames.org/api/v1/verifications/Sillago maculata Quoy & Gaimard (Sillaginidae)
            // https://parser.globalnames.org/api/v1/Cheilodactylus rubrolabiatus Allen ;& Heemstra (Cheilodactylidae)
            // https://verifier.globalnames.org/api/v1/verifications/Cheilodactylus rubrolabiatus Allen ;& Heemstra (Cheilodactylidae)
        }
    }
    private function parse_text_object($rec)
    {
        if($desc = $rec['http://purl.org/dc/terms/description']) {}
        else return array();
        if($names = self::search_host_traits($desc)) { // print_r($names);
            /*Array(
                [0] => Apogon fasciatus (White) (Apogonidae)
                [1] => Sillaginodes punctatus (Cuvier) (Sillaginidae)
                [2] => Sillago bassensis Cuvier (Sillaginidae)
            )*/
            $names = self::parse_names($names); // print_r($names);
            /*Array(
                [Apogon fasciatus (White, 1790)] => Array(
                        [ancestry] => Array(
                                [Apogonidae] => family
                    )
                [Sillaginodes punctatus (Cuvier, 1829)] => Array(
                        [ancestry] => Array(
                                [Sillaginidae] => family
                    )
                [Sillago bassensis Cuvier, 1829] => Array(
                        [ancestry] => Array(
                                [Sillaginidae] => family
                    )
            )*/
            self::write_host_traits($names);
        }
    }
    private function search_host_traits($desc)
    {
        //$desc = "Type-host: Pseudocaranx wrighti (Whitley) (Carangidae: Perciformes), skipjack trevally. Type-locality: Off North Mole, Fremantle, Western Australia, 32°03´S, 115°43´E, December 1994. Site: Intestine, pyloric caeca, rectum. Holotype: Queensland Museum, Reg. No. QM G230442, paratypes: Queensland Museum, Reg. Nos QM G230443-230451, BMNH Reg. Nos 2008.12.9.1-6.<br />"; //debug only - force assignment
        $lines = array();
        $final = array();
        if(preg_match_all("/host\:(.*?)\. /ims", $desc, $arr)) $lines = array_merge($lines, $arr[1]);
        if(preg_match_all("/hosts\:(.*?)\. /ims", $desc, $arr)) $lines = array_merge($lines, $arr[1]);
        if($lines) {
            $lines = array_map('trim', $lines);
            // print_r($lines); //return; //exit("\nelix 1\n");
            /*Array(
                [0] => Sillaginodes punctatus (Cuvier) (Sillaginidae), Sillago bassensis Cuvier (Sillaginidae)
            )*/
            foreach($lines as $line) {
                $parts = explode(", ", $line);
                if(count($parts) > 1) {
                    if(self::each_is_a_valid_binomial($parts)) $final = array_merge($final, $parts);
                    else $final[] = $line;
                }
                else $final[] = $line;
            }
        }
        
        // if($final) { print_r($final); exit("\nelix 2\n"); }
        // else exit("\nelix 3\n");
        return $final;
    }
    private function each_is_a_valid_binomial($parts)
    {   //print_r($parts); exit;
        foreach($parts as $part) {
            /* first filter */
            $obj = $this->func2->run_gnparser($part); //print_r($obj); exit;
            if($canonical_full = @$obj[0]->canonical->full) { // exit("\n[$canonical_full]\n");
                if(!self::more_than_one_word($canonical_full)) return false;
            }
            else return false;
            
            /* second filter */ //to filter out likes of "Australian ruff"
            $obj = $this->func2->run_gnverifier($part); //print_r($obj); //exit("\nelix 4\n");
            if($obj[0]->matchType != "Exact") return false;
        }
        return true;
    }
    private function more_than_one_word($string)
    {
        $parts = explode(" ", $string);
        if(count($parts) > 1) return true;
        else return false;
    }
    private function parse_names($names)
    {   /*
        Array(
            [0] => Apogon fasciatus (White) (Apogonidae)
            [1] => Sillaginodes punctatus (Cuvier) (Sillaginidae)
            [2] => Sillago bassensis Cuvier (Sillaginidae)
        Array(
            [0] => Pseudocaranx dentex (Bloch et Schneider)(Carangidae: Perciformes), white trevally
            [1] => Pseudocaranx wrighti (Whitley) (Carangidae: Perciformes), skipjack trevally
        */
        $final = array();
        foreach($names as $name) {
            $sciname = self::get_best_sciname($name); // echo "\nsciname: [$sciname]\n";
            $ancestry = self::get_ancestry($name); // print_r($ancestry);
            $final[$sciname] = array('ancestry' => $ancestry);
        }
        return $final;
    }
    private function get_best_sciname($name)
    {
        $obj = $this->func2->run_gnverifier($name); //print_r($obj); exit("\ncha 1\n");
        if($obj[0]->matchType == 'Exact') {
            if($val = $obj[0]->bestResult->matchedName) {
                if(self::more_than_one_word($val)) return $val;
            }
            if($val = $obj[0]->bestResult->currentName) {
                if(self::more_than_one_word($val)) return $val;
            }
            if($val = $obj[0]->bestResult->currentCanonicalFull) {
                if(self::more_than_one_word($val)) return $val;
            }
            exit("\nShould not go here...[$name]\n");
        }
    }
    private function get_ancestry($name)
    {   //e.g. "Atherinomorus ogilbyi (Whitley) (Atherinidae, Atheriniformes), Ogilby’s hardyhead"
        $final = array();
        $obj = $this->func2->run_gnverifier($name); //print_r($obj); exit("\ncha 1\n");
        if($obj[0]->matchType == 'Exact') {
            $ancestors = explode("|", $obj[0]->bestResult->classificationPath);
            $ranks = explode("|", $obj[0]->bestResult->classificationRanks);
            if($ancestors && $ranks) {
                if(preg_match_all("/\((.*?)\)/ims", $name, $arr)) { //get names in parenthesis
                    foreach($arr[1] as $item) {
                        $item = str_replace(array(":", ",", ";"), "", $item); //$item = "(Perciformes: Gadidae)"
                        $words = explode(" ", $item);
                        foreach($words as $word) {
                            if(in_array($word, $ancestors)) {
                                $index = self::get_index_given_array_value($word, $ancestors);
                                $final[$word] = $ranks[$index];
                            }
                        }
                    }
                }
            }
        }
        // print_r($final);
        return $final;
    }
    private function get_index_given_array_value($needle, $array)
    {
        while($fruit_name = current($array)) {
            if($fruit_name == $needle) return key($array);
            next($array);
        }
    }
    /* copied template - should be working
    private function initialize_mapping()
    {   $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // self::use_mapping_from_jen();
        // print_r($this->uris);
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...\n"; $i = 0;
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
            } //print_r($rec); exit;
            //===========================================================================================================================================================
            $sought_mtypes = array('http://purl.obolibrary.org/obo/VT_0001256', 'http://purl.obolibrary.org/obo/VT_0001259', 'http://www.wikidata.org/entity/Q245097');
            $mtype = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $lifeStage = '';
            if(in_array($mtype, $sought_mtypes)) $lifeStage = 'http://www.ebi.ac.uk/efo/EFO_0001272';
            $rec['http://rs.tdwg.org/dwc/terms/lifeStage'] = $lifeStage;
            //===========================================================================================================================================================
            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            
            // START DATA-1841 terms remapping
            $o = $this->func->given_m_update_mType_mValue($o);
            // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing
            // END DATA-1841 terms remapping
            
            $o->measurementID = Functions::generate_measurementID($o, $this->resource_id);
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        echo "\nprocess_occurrence...\n"; $i = 0;
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
            // Array(
            //     [http://rs.tdwg.org/dwc/terms/occurrenceID] => O1
            //     [http://rs.tdwg.org/dwc/terms/taxonID] => ABGR4
            //     ...
            // )
            $uris = array_keys($rec);
            $uris = array('http://rs.tdwg.org/dwc/terms/occurrenceID', 'http://rs.tdwg.org/dwc/terms/taxonID');
            $o = new \eol_schema\Occurrence_specific();
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    */
}
?>