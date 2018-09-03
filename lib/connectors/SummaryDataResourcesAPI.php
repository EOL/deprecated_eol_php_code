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
        $this->file['parent child']['fields'] = array('parent_term_URI', 'subclass_term_URI');
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-aug-16-1-2.csv";
        $this->file['preferred synonym']['fields'] = array('preferred_term_URI', 'deprecated_term_URI');
        
        $this->file['parent child'] = "http://localhost/cp/summary data resources/parent-child-aug-16-2.csv";
        $this->file['preferred synonym'] = "http://localhost/cp/summary data resources/preferredsynonym-aug-16-1-2.csv";
        
        $this->dwca_file = "http://localhost/cp/summary data resources/carnivora_sample.tgz";
    }
    function start()
    {
        // /* tests...
        $predicate = "http://reeffish.org/occursIn";
        // $predicate = "http://eol.org/schema/terms/Present";
        // $similar_terms = self::given_predicate_get_similar_terms($predicate);
        // print_r($similar_terms);
        self::given_predicates_get_values_from_traits_csv($similar_terms);

        if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
        exit("\n-end tests-\n");
        // */
    }
    private function setup_working_dir()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "traits.csv", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $tables['taxa'] = 'taxon.txt';
        return array("temp_dir" => $temp_dir, "tables" => $tables);
    }
    private function given_predicates_get_values_from_traits_csv($preds)
    {
        // if(Functions::is_production()) {
        if(true) {
            if(!($info = self::setup_working_dir())) return; //uncomment in real operation
            $this->extension_path = $info['temp_dir'];
            print_r($info);
            // remove temp dir
            // recursive_rmdir($info['temp_dir']);
            // echo ("\n temporary directory removed: " . $info['temp_dir']);
        }
        else { //local development only
            $info = Array('temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_26984/',
                          'tables' => Array('taxa' => "taxon.txt"));
            $this->extension_path = $info['temp_dir'];
            // remove temp dir
            // recursive_rmdir($info['temp_dir']);
            // echo ("\n temporary directory removed: " . $info['temp_dir']);
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
        return array_keys($final);
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