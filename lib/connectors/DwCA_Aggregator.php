<?php
namespace php_active_record;
/* connector: [aggregate_resources.php] - first client 
This lib basically combined DwCA's (.tar.gz) resources.
First client is combining several wikipedia languages -> combine_wikipedia_DwCAs(). Started with languages "ta", "el", "ceb".
2nd client is /connectors/wikipedia.php
*/
class DwCA_Aggregator extends DwCA_Aggregator_Functions
{
    function __construct($folder = NULL, $dwca_file = NULL, $DwCA_Type = 'wikipedia') //'wikipedia' is the first client of this lib.
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->DwCA_Type = $DwCA_Type;
        $this->debug = array();
        /* Please take note of some Meta XML entries have upper and lower case differences */
        $this->extensions = array("http://rs.gbif.org/terms/1.0/vernacularname"     => "vernacular",
                                  "http://rs.tdwg.org/dwc/terms/occurrence"         => "occurrence",
                                  "http://rs.tdwg.org/dwc/terms/measurementorfact"  => "measurementorfact",
                                  "http://rs.tdwg.org/dwc/terms/taxon"              => "taxon",
                                  "http://eol.org/schema/media/document"            => "document",
                                  "http://rs.gbif.org/terms/1.0/reference"          => "reference",
                                  "http://eol.org/schema/agent/agent"               => "agent",

                                  //start of other row_types: check for NOTICES or WARNINGS, add here those undefined URIs
                                  "http://rs.gbif.org/terms/1.0/description"        => "document",
                                  "http://rs.gbif.org/terms/1.0/multimedia"         => "document",
                                  "http://eol.org/schema/reference/reference"       => "reference",
                                  "http://eol.org/schema/association"               => "association");
        $this->attributions = array();
    }
    function get_langs()
    {
        $langs = array();
        $langs[1] = array('ta', 'ceb', 'el', 'mk', 'ky', 'sco', 'hi', 'fy', 'tl', 'jv', 'ia', 'be-x-old', 'oc', 'qu', 'ne', 'koi', 'frr', 'udm', 'ba', 'an', 'zh-min-nan', 'sw', 'ku', 'uz', 'te', 
                       'bs', 'io', 'my', 'mn', 'kv', 'lb', 'su', 'kn', 'tt', 'co', 'sq', 'csb', 'mr', 'fo', 'os', 'cv', 'kab', 'sah', 'nds', 'lmo', 'pa', 'wa', 'vls', 'gv', 'wuu', 'mi', 'nah', 
                       'dsb', 'kbd', 'to', 'mdf', 'li', 'as', 'bat-smg', 'olo', 'mhr', 'tg', 'pcd', 'ps', 'sd', 'vep', 'se', 'am', 'si', 'ht', 'gn', 'rue', 'mt', 'gu', 'ckb', 'als', 
                       'or', 'bh', 'myv', 'scn', 'gd', 'dv', 'pam', 'xmf', 'cdo', 'bar', 'nap', 'lfn', 'nds-nl', 'bo', 'stq', 'inh', 'lbe', 'ha');

        $langs[2] = array('lij', 'lez', 'sa', 'yi', 'ace', 'diq', 'ce', 'yo', 'rw', 'vec', 'sc', 'ln', 'hak', 'kw', 'bcl', 'za', 'ang', 'eml', 'av', 'chy', 'fj', 'ik', 'ug', 'zea', 'bxr', 'zh-classical', 'bjn', 'so', 'arz', 'mwl', 'sn', 'chr', 
                       'mai', 'tk', 'tcy', 'szy', 'mzn', 'wo', 'ab', 'ban', 'ay', 'tyv', 'atj', 'new', 'fiu-vro', 'mg', 'rm', 'ltg', 'ext', 'kl', 'roa-rup', 'nrm', 'rn', 'dty', 'hyw', 'lo', 'kg', 
                       'km', 'gom', 'frp', 'sat', 'gan', 'haw', 'hif', 'nso', 'xal', 'mnw', 'zu', 'bi', 'lad', 'map-bms', 'roa-tara', 'pdc', 'kbp', 'jbo', 'kaa', 'srn', 'vo', 'gag', 'ty', 'fur', 
                       'ie', 'lg', 'ts', 'bpy', 'iu', 'arc', 'gor', 'nov', 'crh', 'tum', 'glk', 'krc', 'ksh', 'na', 'ny', 'pfl', 'xh', 'tpi', 'cr', 'gcr', 'jam', 'ak', 'bm', 'cu', 'ks', 'pap', 
                       'got', 'ee', 'ady', 'pih', 'ki', 'shn', 'pi', 'sm', 'ti', 've', 'ch', 'ig', 'lrc', 'om', 'st', 'din', 'ss', 'tet', 'sg', 'ff', 'pnt', 'tn', 'cbk-zam', 'rmy', 'bug', 'data', 
                       'dz', 'nqo', 'mh', 'tw');

        // $langs = array('mk'); //for testing
        return $langs;
    }
    function combine_MoftheAES_DwCAs($resource_ids)
    {
        if($val = self::get_attributions()) $this->attributions = $val;
        $preferred_rowtypes = false; $ret = array();
        foreach($resource_ids as $resource_id) { $this->resource_id_current = $resource_id; echo "\nProcessing resource_id: [$resource_id]\n";
            if(in_array($resource_id, array("91225", "91362", "91362_resource")) || $this->resource_id == "Kubitzki"
              )  $dwca_file = CONTENT_RESOURCE_LOCAL_PATH.$resource_id.'.tar.gz';
            else $dwca_file = CONTENT_RESOURCE_LOCAL_PATH.$resource_id.'_ENV.tar.gz';
            if(file_exists($dwca_file)) {
                self::convert_archive($preferred_rowtypes, $dwca_file);
            }
            else $ret['DwCA file does not exist'][$dwca_file] = '';
        }
        if($ret) print_r($ret);
        $this->archive_builder->finalize(TRUE);
    }
    function combine_DwCAs($langs, $preferred_rowtypes = array())
    {
        foreach($langs as $this->lang) {
            echo "\n---Processing: [$this->lang]---\n";
            $dwca_file = CONTENT_RESOURCE_LOCAL_PATH.$this->lang.'.tar.gz';
            if(file_exists($dwca_file)) {
                self::convert_archive($preferred_rowtypes, $dwca_file);
            }
            else echo "\nDwCA file does not exist [$dwca_file]\n";
        }
        $this->archive_builder->finalize(TRUE);
    }
    function combine_wikipedia_DwCAs($langs)
    {
        foreach($langs as $this->lang) {
            echo "\n---Processing: [$this->lang]---\n";
            $dwca_file = CONTENT_RESOURCE_LOCAL_PATH.'wikipedia-'.$this->lang.'.tar.gz';
            if(file_exists($dwca_file)) {
                $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/media/document');
                self::convert_archive($preferred_rowtypes, $dwca_file);
            }
            else echo "\nDwCA file does not exist [$dwca_file]\n";
        }
        $this->archive_builder->finalize(TRUE);
    }
    function combine_Plazi_Treatment_DwCAs()
    {
        $tsv = CONTENT_RESOURCE_LOCAL_PATH."reports/TreatmentBank/Plazi_DwCA_list.txt";
        $DwCAs = file($tsv);
        $DwCAs = array_map('trim', $DwCAs); //print_r($DwCAs); exit;
        $no_of_lines = count($DwCAs); 
        $preferred_rowtypes = array("http://rs.tdwg.org/dwc/terms/taxon", "http://eol.org/schema/media/document");
        $preferred_rowtypes[] = "http://rs.gbif.org/terms/1.0/description"; //added Dec 4, 2023. To get the better quality text for textmining.
        $ret = array(); $i = 0;
        foreach($DwCAs as $dwca_file) { $i++; echo "\n$i of $no_of_lines -> ".pathinfo($dwca_file, PATHINFO_BASENAME);
            if(file_exists($dwca_file)) {
                $this->resource_id_current = $dwca_file;
                self::convert_archive($preferred_rowtypes, $dwca_file, array('timeout' => 172800, 'expire_seconds' => 60*60*24*30)); //30 days
            }
            else $ret['DwCA file does not exist'][$dwca_file] = '';
            // break; //debug only
            // if($i >= 5) break; //debug only
        }
        if($ret) print_r($ret);
        $this->archive_builder->finalize(TRUE);
    }
    private function convert_archive($preferred_rowtypes = false, $dwca_file, $download_options = array('timeout' => 172800, 'expire_seconds' => 0))
    {   /* param $preferred_rowtypes is the option to include-only those row_types you want on your final DwCA.*/
        echo "\nConverting archive to EOL DwCA...\n";
        $info = self::start($dwca_file, $download_options); //1 day expire -> 60*60*24*1
        $temp_dir = $info['temp_dir'];
        $this->temp_dir = $temp_dir; //first client is TreatmentBank. Used in reading eml.xml from the source DwCA
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        /* e.g. $index -> these are the row_types
        Array
            [0] => http://rs.tdwg.org/dwc/terms/taxon
            [1] => http://rs.gbif.org/terms/1.0/vernacularname
            [2] => http://rs.tdwg.org/dwc/terms/occurrence
            [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
        */
        // print_r($index); //exit; //good debug to see the all-lower case URIs
        $index = $this->let_media_document_go_first_over_description($index); // print_r($index); exit;
        foreach($index as $row_type) {
            /* ----------customized start------------ */
            if($this->resource_id == 'wikipedia_combined_languages') break; //all extensions will be processed elsewhere.
            if($this->resource_id == 'wikipedia_combined_languages_batch2') break; //all extensions will be processed elsewhere.
            /* ----------customized end-------------- */

            // /* copied template -- where regular DwCA is processed.
            if($preferred_rowtypes) {
                if(!in_array($row_type, $preferred_rowtypes)) continue;
            }
            if($extension_row_type = @$this->extensions[$row_type]) { //process only defined row_types
                // if($extension_row_type == 'document') continue; //debug only
                echo "\nprocessing...: [$row_type]: ".$extension_row_type."...\n";
                /* not used - copied template
                self::process_fields($harvester->process_row_type($row_type), $extension_row_type);
                */
                self::process_table($tables[$row_type][0], $extension_row_type, $row_type);
            }
            else echo "\nun-initialized: [$row_type]: ".$extension_row_type."\n";
            // */
        }
        
        // /* ================================= start of customization =================================
        if(in_array($this->resource_id, array('wikipedia_combined_languages', 'wikipedia_combined_languages_batch2'))) {
            $tables = $info['harvester']->tables;
            // print_r($tables); exit;
            /*Array(
                [0] => http://rs.tdwg.org/dwc/terms/taxon
                [1] => http://eol.org/schema/media/document
            )*/
            self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
            self::process_table($tables['http://eol.org/schema/media/document'][0], 'document');
        }
        // ================================= end of customization ================================= */ 
        
        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
        if($this->debug) print_r($this->debug);
    } //end convert_archive()
    private function start($dwca_file = false, $download_options = array('timeout' => 172800, 'expire_seconds' => false)) //probably default expires in a month 60*60*24*30. Not false.
    {
        if($dwca_file) $this->dwca_file = $dwca_file;
        
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths);
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_05106/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_05106/'
        );
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
    private function is_utf8($v)
    {
        $v = trim($v);
        if(!$v) return true;
        return Functions::is_utf8($v);
    }
    private function adjust_meta_value($meta, $what)
    {
        /*stdClass Object(
            [row_type] => http://eol.org/schema/media/Document
            [location] => media.txt
            [file_uri] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_86624//media.txt
            [fields] => Array(
                    [1] => Array(
                            [term] => http://rs.tdwg.org/dwc/terms/taxonID
                            [type] => 
                            [default] => 
                        )
                    [0] => Array(
                            [term] => http://purl.org/dc/terms/identifier
                            [type] => 
                            [default] => 
                        )
        */
        if($this->resource_id == "TreatmentBank" && $what == 'document') { // print_r($meta->fields); exit;
            if($meta->fields[1]['term'] == "http://rs.tdwg.org/dwc/terms/taxonID" ||
               $meta->fields[0]['term'] == "http://purl.org/dc/terms/identifier") {
                $taxonID = $meta->fields[1];
                $identifier = $meta->fields[0];
                $meta->fields = array_values($meta->fields); //important line
                $meta->fields[0] = $identifier;
                $meta->fields[1] = $taxonID;
            }
            // print_r($meta->fields); exit;
        }
        return $meta;
    }
    private function process_table($meta, $what, $row_type = "")
    {   //print_r($meta);
        $meta = self::adjust_meta_value($meta, $what); //only client for now is resource "TreatmentBank" from treatment_bank.php
        // echo "\nprocessing [$what]...\n";
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            // $row = Functions::conv_to_utf8($row); //new line
            
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            
            /* new block
            if($i == 1) {
                $tmp = explode("\t", $row);
                $column_count = count($tmp);
            }
            */
            
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            
            // if($column_count != count($tmp)) continue; //new line
            
            // /* New: Sep 2, 2021 -> customization needed since some fields from partner's DwCA is not recognized by EOL
            // print_r($meta->fields); exit;
            $excluded_terms = array();
            if($this->resource_id == "TreatmentBank" && $what == 'taxon') {
                $excluded_terms = array('http://plazi.org/terms/1.0/basionymAuthors', 'http://plazi.org/terms/1.0/basionymYear', 'http://plazi.org/terms/1.0/combinationAuthors', 'http://plazi.org/terms/1.0/combinationYear', 'http://plazi.org/terms/1.0/verbatimScientificName');
            }
            $replaced_terms = array();
            if($this->resource_id == "TreatmentBank" && $what == 'document') {
                $replaced_terms["http://rs.tdwg.org/dwc/terms/additionalInformationURL"] = "http://rs.tdwg.org/ac/terms/furtherInformationURL";
            }
            // */

            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                $term = $field['term'];
                if(!$term) continue;
                // /* New: Sep 2, 2021
                if(in_array($term, $excluded_terms)) { $k++; continue; }
                if($val = @$replaced_terms[$term]) $term = $val;
                // */
                $rec[$term] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\ndebug...\n");

            // if($what == "document") { print_r($rec); exit("\n111\n"); }

            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => Q140
                [http://purl.org/dc/terms/source] => http://ta.wikipedia.org/w/index.php?title=%E0%AE%9A%E0%AE%BF%E0%AE%99%E0%AF%8D%E0%AE%95%E0%AE%AE%E0%AF%8D&oldid=2702618
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => Q127960
                [http://rs.tdwg.org/dwc/terms/scientificName] => Panthera leo
                [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Carl Linnaeus, 1758
            )*/

            /* special case. Selected by openning media.tab using Numbers while set description = 'test'. Get taxonID for that row */
            // if($this->lang == 'el') {
                // if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == 'Q18498') continue; 
            // }
            // if($this->lang == 'mk') {
                // if(in_array($rec['http://rs.tdwg.org/dwc/terms/taxonID'], array('Q10876', 'Q5185', 'Q10892', 'Q152', 'Q10798', 'Q8314', 'Q15574019'))) continue;
            // }
            
            $uris = array_keys($rec);
            if($what == "taxon")                    $o = new \eol_schema\Taxon();
            elseif($what == "document")             $o = new \eol_schema\MediaResource();
            elseif($what == "occurrence")           $o = new \eol_schema\Occurrence_specific();
            elseif($what == "measurementorfact")    $o = new \eol_schema\MeasurementOrFact_specific();
            elseif($what == "association")          $o = new \eol_schema\Association();
            elseif($what == "vernacular")           $o = new \eol_schema\VernacularName();
            elseif($what == "agent")                $o = new \eol_schema\Agent();
            else exit("\nERROR: Undefined rowtype[$what].\n");
            
            if($this->DwCA_Type == 'wikipedia') {
                if($what == "taxon") {
                    $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                    if(stripos($rec['http://purl.org/dc/terms/source'], "wikipedia.org") !== false) $rec['http://purl.org/dc/terms/source'] = 'https://www.wikidata.org/wiki/'.$taxon_id; //string is found
                    if(!isset($this->taxon_ids[$taxon_id])) $this->taxon_ids[$taxon_id] = '';
                    else continue;
                }
            }
            elseif($this->DwCA_Type == 'regular') {
                if($what == "taxon") {
                    //taxonID must be unique
                    $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                    if(!isset($this->taxon_ids[$taxon_id])) $this->taxon_ids[$taxon_id] = '';
                    else continue;
                }
            }

            if($what == "document") {

                if($this->resource_id == "TreatmentBank") {
                    $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                    if($row_type == 'http://eol.org/schema/media/document') { //not http://rs.gbif.org/terms/1.0/description
                        // build-up an info list
                        $this->info_taxonID_mediaRec[$taxon_id] = array('UsageTerms'    => $rec['http://ns.adobe.com/xap/1.0/rights/UsageTerms'],
                                                                        'rights'        => $rec['http://purl.org/dc/terms/rights'],
                                                                        'Owner'         => $rec['http://ns.adobe.com/xap/1.0/rights/Owner'],
                                                                        'contributor'   => $rec['http://purl.org/dc/terms/contributor'],
                                                                        'creator'       => $rec['http://purl.org/dc/terms/creator'],
                                                                        'bibliographicCitation' => $rec['http://purl.org/dc/terms/bibliographicCitation']);
                    }
                    elseif($row_type == 'http://rs.gbif.org/terms/1.0/description') { //not http://eol.org/schema/media/document
                        /* Array( print_r($rec);
                            [http://rs.tdwg.org/dwc/terms/taxonID] => 03C44153FFA9FFABFF77F9DFFADFFA97.taxon
                            [http://purl.org/dc/terms/type] => description
                            [http://purl.org/dc/terms/description] => Immature stages Egg. Eggs elongate oval to somewhat cylindrical, chorion with distinct microsculpture in Chilocorus (Figs 4 a, 5 a), Brumoides (Fig. 4 b), and Priscibrumus Kovář. Eggs laid singly or in small groups on or in the vicinity of prey. Chilocorus spp. have a characteristic and peculiar habit of laying eggs on sibling larvae, pupae, and exuviae besides the host colony (Fig. 4 c – e). Larva. Larvae of Chilocorini have a nearly cylindrical or broadly fusiform body with the dorsal and lateral surfaces covered with setose projections (“ senti ”) or prominent parascoli (Figs 4 f, g; 5 b – e). After completing their development, the mature larvae of Chilocorini, particularly armoured-scale feeders, pass 1 – 2 days in an immobile, prepupal stage (Fig. 5 f). Pupa. Pupae are exarate and enclosed in longitudinally and medially split open larval exuvium (Figs 4 h, i; 5 g). In many Chilocorus spp., larvae congregate in small or large clusters on the lower side of branches or on the tree trunk for pupation (Drea & Gordon 1990). It is common to see large congregations of pupae in Indian species such as Chilocorus circumdatus (Gyllenhal) (Fig. 6 a, b), C. nigrita (Fig. 6 c, d) and C. infernalis Mulsant on various host plants.
                            [http://purl.org/dc/terms/language] => en
                            [http://purl.org/dc/terms/source] => POORANI, J. (2023): An illustrated guide to the lady beetles (Coleoptera: Coccinellidae) of the Indian Subcontinent. Part II. Tribe Chilocorini. Zootaxa 5378 (1): 1-108, DOI: 10.11646/zootaxa.5378.1.1, URL: https://www.mapress.com/zt/article/download/zootaxa.5378.1.1/52353
                        ) */
                        $description_type = $rec['http://purl.org/dc/terms/type'];
                        if(in_array($description_type, array('etymology', 'discussion', 'type_taxon'))) continue;                        
                        // if($description_type == 'type_taxon') { print_r($rec); exit; } //debug only good debug
                        $this->debug[$this->resource_id]['text type'][$rec['http://purl.org/dc/terms/type']] = '';
                        $json = json_encode($rec);
                        $rec['http://purl.org/dc/terms/identifier'] = md5($json);
                        $rec['http://rs.tdwg.org/ac/terms/additionalInformation'] = $rec['http://purl.org/dc/terms/type'];
                        $rec['http://purl.org/dc/terms/type'] = "http://purl.org/dc/dcmitype/Text";
                        $rec['http://purl.org/dc/terms/format'] = "text/html";
                        $rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses";
                        $rec['http://purl.org/dc/terms/title'] = "";
                        $rec['http://rs.tdwg.org/ac/terms/furtherInformationURL'] = "https://treatment.plazi.org/id/".str_replace(".taxon", "", $rec['http://rs.tdwg.org/dwc/terms/taxonID']);
                        $rec['http://purl.org/dc/terms/bibliographicCitation'] = $rec['http://purl.org/dc/terms/source'];
                        unset($rec['http://purl.org/dc/terms/source']);
                        // /* supplement with data from media row_type
                        if($val = $this->info_taxonID_mediaRec[$taxon_id]) {
                            $rec['http://ns.adobe.com/xap/1.0/rights/UsageTerms']   = $val['UsageTerms']; //Public Domain
                            $rec['http://purl.org/dc/terms/rights']                 = $val['rights']; //No known copyright restrictions apply. See Agosti, D., Egloff, W., 2009. Taxonomic information exchange and copyright: the Plazi approach. BMC Research Notes 2009, 2:53 for further explanation.
                            $rec['http://ns.adobe.com/xap/1.0/rights/Owner']        = $val['Owner'];
                            $rec['http://purl.org/dc/terms/contributor']            = $val['contributor']; //MagnoliaPress via Plazi
                            $rec['http://purl.org/dc/terms/creator']                = $val['creator']; //POORANI, J.
                            $rec['http://purl.org/dc/terms/bibliographicCitation']  = $val['bibliographicCitation']; //POORANI, J. (2023): An illustrated guide to the lady beetles (Coleoptera: Coccinellidae) of the Indian Subcontinent. Part II. Tribe Chilocorini. Zootaxa 5378 (1): 1-108, DOI: 10.11646/zootaxa.5378.1.1, URL: https://www.mapress.com/zt/article/download/zootaxa.5378.1.1/52353
                        }
                        // */                        
                    }
                }

                //identifier must be unique
                $identifier = $rec['http://purl.org/dc/terms/identifier'];
                if(!isset($this->object_ids[$identifier])) $this->object_ids[$identifier] = '';
                else continue;
            }
            elseif($what == "agent") {
                //identifier must be unique
                $identifier = $rec['http://purl.org/dc/terms/identifier'];
                if(!isset($this->agent_ids[$identifier])) $this->agent_ids[$identifier] = '';
                else continue;
            }
            elseif($what == "vernacular") {
                //row must be unique
                $identifier = $rec['http://rs.tdwg.org/dwc/terms/vernacularName']."|".$rec['http://purl.org/dc/terms/language']."|".$rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $identifier = md5($identifier);
                if(!isset($this->vernacular_ids[$identifier])) $this->vernacular_ids[$identifier] = '';
                else continue;
            }


            
            /* Investigation only --- works OK
            if($what == "taxon") {
                if($rec['http://rs.tdwg.org/dwc/terms/scientificName'] == "Plicatura faginea") {
                    echo "\n--- START Investigate ---\n";
                    print_r($rec); print_r($meta);
                    echo "\n--- END Investigate ---\n";
                }
            }
            */
            
            //================== start attributions =================== https://eol-jira.bibalex.org/browse/DATA-1887?focusedCommentId=66290&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66290
            /* Attributions:    You can use the two output columns for bibliographicCitation and FurtherInformationURL in the media file, 
                                and for bibliographicCitation and source in the MoF file. */
            if($this->attributions) {
                $citation = $this->attributions[$this->resource_id_current]['citation'];
                $source = $this->attributions[$this->resource_id_current]['source'];
                if($what == "document") {
                    $rec['http://purl.org/dc/terms/bibliographicCitation'] = $citation;
                    $rec['http://rs.tdwg.org/ac/terms/furtherInformationURL'] = $source;
                }
                elseif(in_array($what, array("measurementorfact", "association"))) {
                    $rec['http://purl.org/dc/terms/bibliographicCitation'] = $citation;
                    $rec['http://purl.org/dc/terms/source'] = $source;
                }
                $uris = array_keys($rec);
            }
            //================== end attributions ===================
            
            /* Good debug
            elseif($what == "document") {
                $desc = @$rec['http://purl.org/dc/terms/description'];
                if($desc) {
                    $desc = str_ireplace(array("\n", "\t", "\r", chr(9), chr(10), chr(13), chr(0x0D), chr(0x0A), chr(0x0D0A)), " ", $desc);
                    $desc = Functions::conv_to_utf8($desc);
                }
                $rec['http://purl.org/dc/terms/description'] = 'eli'; //$desc;

                if($val = trim(@$rec['http://ns.adobe.com/xap/1.0/rights/UsageTerms'])) {}
                else exit("\nNo license\n"); //continue;
            }
            */

            // /* ==================== start customize ====================
            if($this->resource_id == "TreatmentBank") {
                if($what == "document") {
                    $rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses";
                    
                    // /* New: exclude non-English text
                    if($lang = @$rec['http://purl.org/dc/terms/language']) {
                        if($lang && $lang != "en") continue;
                    }
                    // */
                    
                    /* Used by our original text object.
                    // remove taxonomic/nomenclature line from description
                    if($description = @$rec['http://purl.org/dc/terms/description']) {
                        $rec['http://purl.org/dc/terms/description'] = $this->remove_taxon_lines_from_desc($description);
                    } */
                    
                }
                
                // to have a unique media object identifier
                if($what == "document") {
                    // $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                    $do_identifier = $rec['http://purl.org/dc/terms/identifier'];
                    if(isset($this->data_object_identifiers[$do_identifier])) continue;
                    else $this->data_object_identifiers[$do_identifier] = '';
                }

                // /* shorten the bibliographicCitation: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=66418&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66418
                if($what == "document") {
                    if($bibliographicCitation = @$rec['http://purl.org/dc/terms/bibliographicCitation']) {
                        $rec['http://purl.org/dc/terms/bibliographicCitation'] = $this->shorten_bibliographicCitation($meta, $bibliographicCitation);
                        /* good debug
                        if(true) {
                        //if(stripos($bibliographicCitation, "Hespenheide, Henry A. (2019): A Review of the Genus Laemosaccus Schönherr, 1826 (Coleoptera: Curculionidae: Mesoptiliinae) from Baja California and America North of Mexico: Diversity and Mimicry") !== false) { //string is found
                        //if(stripos($bibliographicCitation, "Grismer, L. Lee, Wood, Perry L., Jr, Lim, Kelvin K. P. (2012): Cyrtodactylus Majulah") !== false) { //string is found
                            echo "\n===============================start\n";
                            // print_r($meta); echo "\nwhat: [$what]\n";
                            print_r($rec); //echo "\nresource_id: [$this->resource_id_current]\n";
                            echo "\n===============================end\n";
                            // exit("\n");
                        }
                        */
                    }
                    // print_r($rec); exit("\nexit muna...\n");
                    /* Array( --- as of Dec 4, 2023
                        [http://purl.org/dc/terms/identifier] => 03C44153FFA9FFABFF77F9DFFADFFA97.text
                        [http://rs.tdwg.org/dwc/terms/taxonID] => 03C44153FFA9FFABFF77F9DFFADFFA97.taxon
                        [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
                        [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses
                        [http://purl.org/dc/terms/format] => text/html
                        [http://purl.org/dc/terms/language] => en
                        [http://purl.org/dc/terms/title] => Chilocorini Mulsant 1846
                        [http://purl.org/dc/terms/description] => Form circular, broadly oval, or distinctly elongate oval (Fig. 2); dorsum often dome-shaped and strongly convex or moderately convex, shiny and glabrous (at the most only head and anterolateral flanks of pronotum with hairs), or with sparse, short and suberect pubescence on elytral disc and more visibly on lateral margins, or with distinct dorsal pubescence (Fig. 2g, i). Head capsule with anterior clypeal margin laterally strongly expanded over eyes, medially emarginate, rounded or laterally truncate (Fig. 3a–c). Anterior margin of pronotum deeply and trapezoidally excavate, lateral margins strongly descending below; anterior angles usually strongly produced anteriorly. Elytra basally much broader than pronotum. Antennae short (7–10 segmented) (Fig. 3g –j), shorter than half the width of head; antennal insertions hidden and broadly separated. Terminal maxillary palpomere (Fig. 3d–f) parallel-sided and apically obliquely transverse or securiform or elongate, slender, subcylindrical to tapered with oblique apex, or somewhat swollen with subtruncate apex. Prosternal intercoxal process without carinae (Fig. 3k). Elytral epipleura broad, sometimes strongly descending externally with inner carina reaching elytral apex or not. Legs often with strongly angulate tibiae; tarsal formula 4–4–4 (Fig. 3o, p); tarsal claws simple (Fig. 3u) or appendiculate (Fig. 3v). Abdominal postcoxal line incomplete (Fig. 3l, n) or complete (Fig. 3m). Female genitalia with elongate triangular or transverse coxites (Fig. 3q, r); spermatheca with (Fig. 3t) or without (Fig. 3s, w) a membranous, beak-like projection at apex; sperm duct between bursa copulatrix and spermatheca most often composed of two or three parts of different diameters (Fig. 3w); infundibulum present (Fig. 3w) or absent...
                        [http://rs.tdwg.org/ac/terms/furtherInformationURL] => https://treatment.plazi.org/id/03C44153FFA9FFABFF77F9DFFADFFA97
                        [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => Public Domain
                        [http://purl.org/dc/terms/rights] => No known copyright restrictions apply. See Agosti, D., Egloff, W., 2009. Taxonomic information exchange and copyright: the Plazi approach. BMC Research Notes 2009, 2:53 for further explanation.
                        [http://ns.adobe.com/xap/1.0/rights/Owner] => 
                        [http://purl.org/dc/terms/contributor] => MagnoliaPress via Plazi
                        [http://purl.org/dc/terms/creator] => POORANI, J.
                        [http://purl.org/dc/terms/bibliographicCitation] => POORANI, J. (2023): An illustrated guide to the lady beetles (Coleoptera: Coccinellidae) of the Indian Subcontinent. Part II. Tribe Chilocorini. Zootaxa 5378 (1): 1-108, DOI: 10.11646/zootaxa.5378.1.1, URL: https://www.mapress.com/zt/article/download/zootaxa.5378.1.1/52353
                    ) */
                }
                // */
                
                // /* ancestry fields must not have separators: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=66656&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66656
                if($what == "taxon") {
                    $ancestors = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
                    foreach($ancestors as $ancestor) {
                        if($val = trim(@$rec["http://rs.tdwg.org/dwc/terms/".$ancestor])) {
                            if(stripos($val, ";") !== false) $rec["http://rs.tdwg.org/dwc/terms/".$ancestor] = ""; //string is found
                            elseif(stripos($val, ",") !== false) $rec["http://rs.tdwg.org/dwc/terms/".$ancestor] = ""; //string is found
                            elseif(stripos($val, " ") !== false) $rec["http://rs.tdwg.org/dwc/terms/".$ancestor] = ""; //string is found
                        }
                    }
                    if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == "03A487F05711FB7CFECA8E029F9BA19D.taxon") continue;
                    if(stripos($rec['http://rs.tdwg.org/dwc/terms/scientificName'], "Acrididae;") !== false) continue; //string is found
                    if(stripos(@$rec['http://rs.gbif.org/terms/1.0/canonicalName'], "Acrididae;") !== false) continue; //string is found

                    // /* new: Nov 21, 2023:
                    if($scientificName = @$rec["http://rs.tdwg.org/dwc/terms/scientificName"]) {
                        if(!Functions::valid_sciname_for_traits($scientificName)) continue;
                    }
                    // */

                }
                // */
            } //end TreatmentBank
            // ==================== end customize ==================== */
            
            $uris = array_keys($rec);
            // print_r($uris);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                /* good debug
                echo "\n[$field][$uri]\n";
                if($field == "vernacularName" && $uri == "http://rs.tdwg.org/dwc/terms/vernacularName") {
                    if(!$rec[$uri]) continue;
                }
                */
                
                // /* some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];
                // */
                
                $o->$field = $rec[$uri];
            }
            
            // /* new: add a new text object using <title> tag from eml.xml. Will practically double the no. of text objects. Per https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=66921&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66921
            // Dec 4, 2023: Moved up. This will no longer double the no. of text objects but will overwrite the original text object we use to textmine.
            if($this->resource_id == "TreatmentBank") {
                if($what == "document") {
                    if($row_type == 'http://eol.org/schema/media/document') { //not for http://rs.gbif.org/terms/1.0/description
                        if($title = self::get_title_from_eml_xml()) {
                            $o->identifier = md5($title.$o->taxonID);
                            $o->title = 'Title for eol-geonames';
                            $o->description = $title;
                            $o->bibliographicCitation = '';
                            if(!isset($this->data_objects[$o->identifier])) {
                                $this->archive_builder->write_object_to_file($o);
                                $this->data_objects[$o->identifier] = '';
                            }
                            continue;
                        }    
                    }                
                }
            }
            // */

            $this->archive_builder->write_object_to_file($o);

            // if($i >= 2) break; //debug only
        } //end foreach()
    }
    private function get_attributions()
    {   //exit("\nresource_id: [$this->resource_id]\n");
        if(Functions::is_production()) {
            $source["MoftheAES_resources"]       = "/extra/other_files/Smithsonian/MoftheAES/from_Jen/MoftheAES_attribution_plus_four.txt";
            $source["NorthAmericanFlora"]        = "/extra/other_files/Smithsonian/BHL/from_Jen/FNA_attribution_mapping.txt";   //7 documents
            $source["NorthAmericanFlora_Fungi"]  = "/extra/other_files/Smithsonian/BHL/from_Jen/FNA_attribution_mapping.txt";   //Fungi list
            $source["NorthAmericanFlora_Plants"] = "/extra/other_files/Smithsonian/BHL/from_Jen/FNA_attribution_mapping.txt";   //Plants list
            $source["91362_resource"]            = "/extra/other_files/Smithsonian/BHL/from_Jen/FNA_attribution_mapping.txt";   //7 documents
        }
        else {
            $source["MoftheAES_resources"]       = "/Volumes/AKiTiO4/other_files/Smithsonian/MoftheAES/from_Jen/MoftheAES_attribution_plus_four.txt";
            $source["NorthAmericanFlora"]        = "/Volumes/AKiTiO4/other_files/Smithsonian/BHL/from_Jen/FNA_attribution_mapping.txt";   //7 documents
            $source["NorthAmericanFlora_Fungi"]  = "/Volumes/AKiTiO4/other_files/Smithsonian/BHL/from_Jen/FNA_attribution_mapping.txt";   //Fungi list
            $source["NorthAmericanFlora_Plants"] = "/Volumes/AKiTiO4/other_files/Smithsonian/BHL/from_Jen/FNA_attribution_mapping.txt";   //Plants list
            $source["91362_resource"]            = "/Volumes/AKiTiO4/other_files/Smithsonian/BHL/from_Jen/FNA_attribution_mapping.txt";   //7 documents
        }
        $i = 0;
        if($source = @$source[$this->resource_id]) {
            foreach(new FileIterator($source) as $line => $row) { $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
                $rec = explode("\t", $row);
                if($i == 1) {
                    $fields = $rec;
                    continue;
                }
                else {
                    $rek = array(); $k = 0;
                    foreach($fields as $fld) {
                        if($fld) $rek[$fld] = @$rec[$k];
                        $k++;
                    }
                    $rek = array_map('trim', $rek);
                }
                // print_r($rek); exit("\n-end-\n");
                /*Array(
                    [document number] => 30355
                    [citation] => Cresson, E.T. 1916. The Cresson Types of Hymenoptera. Memoirs of the American Entomological Society vol. 1. Philadelphia, USA
                    [source] => https://www.biodiversitylibrary.org/item/30355
                )*/
                if($val = @$rek['source']) $source = $val;
                elseif($val = @$rek['URL']) $source = $val;
                else exit("\nNo field for source or URL.\n");
                $ret[$rek['document number']] = array("citation" => $rek['citation'], "source" => $source);
            }
            if($val = @$ret['91362']) $ret['91362_resource'] = $val;
            return $ret;
        }
        else echo "\nNo attribution info yet for resource_id: [$this->resource_id]\n";
    }
    private function get_title_from_eml_xml()
    {
        $xml_string = file_get_contents($this->temp_dir."/eml.xml");
        $hash = simplexml_load_string($xml_string); //print_r($hash);
        $title = trim((string) $hash->dataset->title);
        // echo "\n---\n[$title]\n---\n"; exit("\nexit munax\n");
        if($title) return $title;
    }
}
?>