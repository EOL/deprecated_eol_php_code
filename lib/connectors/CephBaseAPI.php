<?php
namespace php_active_record;
/* 

*/
class CephBaseAPI
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('resource_id' => 'cephbase', 'timeout' => 60*5, 'expire_seconds' => false, 'download_wait_time' => 2000000);

        $this->main_text_ver1 = "http://localhost/cp/CephBase/taxa_html.txt";
        $this->main_text_ver1 = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/CephBase/taxa_html.txt";
        
        $this->main_text_ver2 = "http://localhost/cp/CephBase/html/CephBase Classification | CephBase.html";
        $this->main_text_ver2 = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/CephBase/CephBase Classification | CephBase.html";
        
        $this->page['Photos & Videos'] = "http://cephbase.eol.org/gallery?f[0]=tid%3A1";
        $this->page['page_range'] = "http://cephbase.eol.org/gallery?page=page_no&f[0]=tid:1"; //replace 'page_no' with actual page no.
        $this->page['image_page'] = "http://cephbase.eol.org/file-colorboxed/";                //add the file OR image no.
        $this->page['taxon_page'] = "http://cephbase.eol.org/taxonomy/term/";
        $this->page['taxa_refs'] = "http://localhost/cp/CephBase/html/Literature References | CephBase.html";
    }
    function start()
    {
        self::parse_classification();   //exit("\nstop classification\n");
        self::parse_images();           //exit("\nstop images\n");
        // self::parse_references(); exit("\nstop references\n");
        exit();
    }
    private function parse_references()
    {
        /*
        <a href="http://cephbase.eol.org/biblio/?f[0]=im_field_taxonomic_name%3A602" rel="nofollow" class="facetapi-inactive">Watasenia scintillans (25)<span class="element-invisible">Apply Watasenia scintillans filter</span></a>
        */
        if($html = Functions::lookup_with_cache($this->page['taxa_refs'], $this->download_options)) {
            if(preg_match_all("/<a href=\"\/taxonomy\/term\/(.*?)<\/a>/ims", $html, $arr)) {
            }
        }
        
    }
    private function parse_classification()
    {
        /* working version 1
        if($html = Functions::lookup_with_cache($this->main_text_ver1, $this->download_options)) {
            //<a href="/taxonomy/term/437" ><em>Sepiadarium</em> <em>auritum</em></a>
            if(preg_match("/<h2 class=\"title\">CephBase Classification<\/h2>(.*?)<div class=\"region-inner region-content-inner\">/ims", $html, $arr)) {
                if(preg_match_all("/<a href=\"\/taxonomy\/term\/(.*?)<\/a>/ims", $arr[1], $arr2)) {
                    foreach($arr2[1] as $str) {
                        $str = Functions::remove_whitespace(strip_tags($str));
                        // echo "\n[$str]";
                        //[8" >Cephalopoda]
                        if(preg_match("/xxx(.*?)\"/ims", "xxx".$str, $arr)) $id = $arr[1];
                        if(preg_match("/>(.*?)xxx/ims", $str."xxx", $arr)) $sciname = $arr[1];
                        $rec[$id] = $sciname;
                    }
                }
            }
        }
        echo "\n count 1: ".count($rec)."\n";
        */
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
            echo "\n$i of $total: [$sciname] [$taxon_id]";
            $taxon = self::parse_taxon_info($taxon_id);
            // exit("\nelix\n");
        }
    }
    private function parse_taxon_info($taxon_id)
    {
        if($html = Functions::lookup_with_cache($this->page['taxon_page'].$taxon_id, $this->download_options)) {
            /*<div class="field-label">Subspecies:</div>
               <div class="field-items">
                 <div class="field-item" style="padding-left:3px;">
                 <em>Nautilus</em> <em>pompilius</em> <em>pompilius</em> Linnaeus 1758        </div>
            */
            if(preg_match("/<div class=\"field-label\">(.*?):<\/div>/ims", $html, $arr)) $rec['rank'] = $arr[1];
            if(preg_match("/<div class=\"field-item\"(.*?)<\/div>/ims", $html, $arr)) $rec['sciname'] = strip_tags("<div ".$arr[1]);
            $rec = array_map('trim', $rec);
            $rec['usage'] = self::get_usage($html);
            $rec['ancestry'] = self::get_ancestry($html);
            $rec['refs'] = self::get_references($taxon_id);
            // print_r($rec);
            // exit("\n");
            return $rec;
        }
    }
    private function get_usage($html)
    {
        /*
        <div class="field field-name-field-usage field-type-list-text field-label-inline clearfix">
            <div class="field-label">Usage:&nbsp;</div>
            <div class="field-items">
                <div class="field-item even">not accepted</div>
            </div>
        </div>
        
        <div class="field field-name-field-unacceptability-reason field-type-list-text field-label-inline clearfix">
            <div class="field-label">Unacceptability Reason:&nbsp;</div>
            <div class="field-items">
                <div class="field-item even">synonym</div>
            </div>
        </div>
        */
        $rec = array();
        if(preg_match("/<div class\=\"field\-label\">Usage\:\&nbsp\;<\/div>(.*?)<\/div>/ims", $html, $arr)) $rec['Usage'] = strip_tags($arr[1]);
        if(preg_match("/<div class=\"field-label\">Unacceptability Reason:&nbsp;<\/div>(.*?)<\/div>/ims", $html, $arr)) $rec['Unacceptability Reason'] = strip_tags($arr[1]);
        // print_r($rec); //exit;
        return $rec;
    }
    private function get_references($taxon_id)
    {
        
    }
    private function get_ancestry($html)
    {
        $final = array();
        /*
        <span class="field-content"><strong>Genus:</strong> <a href="/taxonomy/term/60" title="<em>Nautilus</em>"><em>Nautilus</em></a></span>  </div> 
        */
        if(preg_match_all("/<span class=\"field-content\"><strong>(.*?)<\/span>/ims", $html, $arr)) {
            foreach($arr[1] as $str) {
                /*Array(
                    [0] => Family:</strong> <a href="/taxonomy/term/12" title="Sepiadariidae">Sepiadariidae</a>
                    [1] => Genus:</strong> <a href="/taxonomy/term/65" title="<em>Sepiadarium</em>"><em>Sepiadarium</em></a>
                )
                */
                $rec = array();
                if(preg_match("/xxx(.*?):/ims", "xxx".$str, $arr)) $rec['rank'] = $arr[1];
                if(preg_match("/title=\"(.*?)\"/ims", "xxx".$str, $arr)) $rec['sciname'] = strip_tags($arr[1]);
                if(preg_match("/term\/(.*?)\"/ims", "xxx".$str, $arr)) $rec['id'] = $arr[1];
                $final[] = $rec;
            }
        }
        return $final;
    }
    private function parse_images()
    {
        if($html = Functions::lookup_with_cache($this->page['Photos & Videos'], $this->download_options)) {
            $last_page = self::get_last_page_for_image($html);
            echo "\npage range is from: 0 to $last_page\n";
            $start = 0; //orig
            // $start = 2; //debug only
            for ($page_no = $start; $page_no <= $last_page; $page_no++) {
                self::parse_page_no($page_no);
            }
        }
    }
    private function parse_page_no($page_no)
    {   //<h2 class="element-invisible"><a href="http://cephbase.eol.org/sites/cephbase.eol.org/files/cb0563.jpg">cb0563.jpg</a></h2>
        $url = str_replace('page_no', $page_no, $this->page['page_range']);
        echo "\n[$page_no] - [$url]";
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            /* working but insufficient
            if(preg_match_all("/<h2 class=\"element-invisible\"><a(.*?)<\/h2>/ims", $html, $arr)) {
                print_r($arr[1]);
            }
            */
            
            /*
            http://cephbase.eol.org/file-colorboxed/4
            <a href="/file/4"><img typeof="foaf:Image"
            */
            if(preg_match_all("/<a href\=\"\/file\/(.*?)\"/ims", $html, $arr)) {
                foreach($arr[1] as $file_no) {
                    $url =  $this->page['image_page'].$file_no;
                    self::parse_image_info($url);
                }
            }
        }
    }
    private function parse_image_info($url)
    {
        $final = array();
        echo "\n[$url]";
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
                    if(preg_match("/href=\"(.*?)\"/ims", $str, $arr)) $final['license'] = $arr[1];
                }
            }
            if(preg_match("/<div class=\"field field-name-field-creator field-type-text field-label-above\">(.*?)Download the original/ims", $html, $arr)) {
                $str = $arr[1];
                if(preg_match("/<div class=\"field-item even\">(.*?)<\/div>/ims", $str, $arr)) {
                    $str = trim($arr[1]);
                    $final['creator'] = $str;
                }
            }
        }
        print_r($final); //exit;
        return $final;
    }
    private function get_last_page_for_image($html)
    {   //<a title="Go to last page" href="/gallery?page=29&amp;f[0]=tid%3A1">last »</a>
        if(preg_match("/<a title=\"Go to last page\" href=\"\/gallery\?page\=(.*?)&amp;/ims", $html, $arr)) return $arr[1];
    }
    
    /*
    function get_all_taxa($resource_id)
    {
        $this->uris = self::get_uris();
        $this->bibliographic_citation = self::get_fishbase_remote_citation();
        self::prepare_data();
        // remove tmp dir
        $this->TEMP_FILE_PATH = str_ireplace("/fishbase", "", $this->TEMP_FILE_PATH);
        // if($this->TEMP_FILE_PATH) shell_exec("rm -fr $this->TEMP_FILE_PATH");
        recursive_rmdir($this->TEMP_FILE_PATH); // debug uncomment in real operation
        echo ("\n temporary directory removed: " . $this->TEMP_FILE_PATH);
        if($this->test_run) return $all_taxa; //used in testing
    }

    private function process_distribution_text($str)
    {
        $str = str_ireplace("Ref.", "Ref*", $str);
        $temp = explode(".", $str);
        $temp = array_map('trim', $temp);
        $final = array();
        foreach($temp as $t) {
            if(strpos($t, ":") !== false) $final[] = str_ireplace("Ref*", "Ref.", $t);
        }
        
        $new_distribution_texts = array();
        foreach($final as $t) {
            $reference_ids = array();
            if($ref_ids = self::get_ref_id_from_string($t)) {
                foreach($ref_ids as $ref_id) self::get_ref_details_from_fishbase_and_create_ref($ref_id);
            }
            $new_distribution_texts[] = array("desc" => $t, "reference_ids" => $ref_ids);
        }
        return $new_distribution_texts;
    }
    
    private function get_ref_details_from_fishbase_and_create_ref($ref_id)
    {
        $url = 'http://www.fishbase.org/references/FBRefSummary.php?ID=' . $ref_id;
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match("/Citation<\/td>(.*?)<\/td>/ims", $html, $arr)) {
                $fb_full_ref = self::clean_html(strip_tags($arr[1]));
                
                $reference_ids = array();
                if(!Functions::is_utf8($fb_full_ref)) $fb_full_ref = utf8_encode($fb_full_ref);
                
                $r = new \eol_schema\Reference();
                $r->full_reference = $fb_full_ref;
                $r->identifier = $ref_id;
                $r->uri = $url;
                if(!isset($this->reference_ids[$ref_id])) {
                    $this->reference_ids[$ref_id] = md5($fb_full_ref);
                    $this->archive_builder->write_object_to_file($r);
                    return md5($fb_full_ref);
                }
            }
        }
    }
    private function get_ref_id_from_string($str)
    {
        if(preg_match_all("/\(Ref\.(.*?)\)/ims", $str, $arr)) {
            $str = trim(implode(",", $arr[1]));
            $str = str_ireplace("Ref.", "", $str);
            $arr = explode(",", $str);
            $arr = array_map('trim', $arr);
            $arr = array_unique($arr); //make unique
            $arr = array_values($arr); //reindex key
            $final = array();
            foreach($arr as $a) {
                if(is_numeric($a)) $final[] = $a;
            }
            return $final;
        }
        return false;
    }
    
    private function get_fishbase_remote_citation()
    {
        if($html = Functions::lookup_with_cache('http://www.fishbase.org/summary/citation.php', $this->download_options)) {
            if(preg_match("/Cite FishBase itself as(.*?)<p /ims", $html, $arr)) {
                $temp = $arr[1];
                $temp = str_ireplace(".<br>", ". ", $temp);
                return trim(strip_tags($temp));
            }
        }
    }
    
    function prepare_data()
    {
        self::process_taxa_references();        echo "\n taxa references -- DONE";
        self::process_taxa();                   echo "\n taxa -- DONE";
        self::process_taxa_comnames();          echo "\n common names -- DONE";
        self::process_taxa_synonyms();          echo "\n synonyms -- DONE";
        self::process_taxa_object_references(); echo "\n dataObject references -- DONE";
        self::process_taxa_object_agents();     echo "\n agents -- DONE";
        self::process_taxa_objects();           echo "\n dataObjects -- DONE";
        $this->archive_builder->finalize(true);
        return true;
    }

    private function process_taxa_synonyms()
    {
        $fields = array("synonym", "author", "relationship", "int_id", "timestamp", "autoctr");
        $taxon_synonyms = self::make_array($this->text_path['TAXON_SYNONYMS_PATH'], $fields, "int_id", array(1,4,5));
        foreach($taxon_synonyms as $taxon_id => $synonyms) {
            $taxon_id = str_replace("\N", "", $taxon_id);
            if(!$taxon_id = trim($taxon_id)) continue;
            foreach($synonyms as $s) {
                foreach($s as $key => $value) $s[$key] = str_replace("\N", "", $value);
                $taxon = new \eol_schema\Taxon();
                $taxon->taxonID             = md5($s['synonym']);
                $taxon->scientificName      = utf8_encode($s['synonym']);
                if($val = @$this->taxa_ids[$taxon_id]) $taxon->acceptedNameUsageID = $val;
                else continue;
                if($s['relationship'] == 'valid name') $s['relationship'] = 'synonym';
                if(strtolower($s['relationship']) != 'xxx') $taxon->taxonomicStatus = $s['relationship'];
                if(!isset($this->synonym_ids[$taxon->taxonID])) {
                    $this->synonym_ids[$taxon->taxonID] = '';
                    $this->archive_builder->write_object_to_file($taxon);
                }
            }
        }
    }
    
    private function process_taxa_object_agents()
    {
        $fields = array("agent", "homepage", "logoURL", "role", "int_do_id", "timestamp");
        $taxon_dataobject_agent = self::make_array($this->text_path['TAXON_DATAOBJECT_AGENT_PATH'], $fields, "int_do_id", array(5));
        foreach($taxon_dataobject_agent as $do_id => $agents) { //do_id is int_do_id in FB text file
            $agent_ids = array();
            foreach($agents as $a) {
                if(!$a['agent']) continue;
                $r = new \eol_schema\Agent();
                $r->term_name       = $a['agent'];
                $r->agentRole       = $a['role'];
                $r->identifier      = md5("$r->term_name|$r->agentRole");
                $r->term_homepage   = $a['homepage'];
                $agent_ids[] = $r->identifier;
                if(!isset($this->agent_ids[$r->identifier])) {
                   $this->agent_ids[$r->identifier] = $r->term_name;
                   $this->archive_builder->write_object_to_file($r);
                }
            }
            $this->object_agent_ids[$do_id] = $agent_ids;
        }
    }

    private function process_taxa_object_references()
    {
        $fields = array("reference", "bici", "coden", "doi", "eissn", "handle", "isbn", "issn", "lsid", "oclc", "sici", "url", "urn", "int_do_id");
        $taxon_dataobject_reference = self::make_array($this->text_path['TAXON_DATAOBJECT_REFERENCE_PATH'], $fields, "int_do_id", array(1,2,3,4,5,7,8,9,10,12));
        foreach($taxon_dataobject_reference as $do_id => $refs) { //do_id is int_do_id in FB text file
            $reference_ids = self::create_references($refs);
            $this->object_reference_ids[$do_id] = $reference_ids;
        }
    }
    
    private function create_references($refs)
    {
        $reference_ids = array();
        foreach($refs as $ref) {
            foreach($ref as $key => $value) $ref[$key] = str_replace("\N", "", $value);
            if(!Functions::is_utf8($ref['reference'])) $ref['reference'] = utf8_encode($ref['reference']);
            $r = new \eol_schema\Reference();
            $r->full_reference = $ref['reference'];
            $r->identifier = md5($r->full_reference);
            $r->uri = $ref['url'];
            $reference_ids[] = $r->identifier;
            
            //get ref_id
            if(preg_match("/id=(.*?)&/ims", $ref['url'], $arr)) $ref_id = trim($arr[1]);
            elseif(preg_match("/id=(.*?)xxx/ims", $ref['url']."xxx", $arr)) $ref_id = trim($arr[1]);
            else {
                echo "\nno ref id; investigate: " . $ref["url"];
                $ref_id = '';
            }
            
            if(!isset($this->reference_ids[$ref_id])) {
                $this->reference_ids[$ref_id] = $r->identifier; //normally the value should be just '', but $this->reference_ids will be used in - convert_FBrefID_with_archiveID()
                $this->archive_builder->write_object_to_file($r);
            }
        }
        return array_unique($reference_ids);
    }
    
    private function process_taxa_objects()
    {
        $fields = array("TaxonID", "dc_identifier", "dataType", "mimeType", "dcterms_created", "dcterms_modified", "dc_title", "dc_language", "license", "dc_rights", "dcterms_bibliographicCitation", "dc_source", "subject", "dc_description", "mediaURL", "thumbnailURL", "location", "xml_lang", "geo_point", "lat", "long", "alt", "timestamp", "int_id", "int_do_id", "dc_rightsHolder");
        $taxa_objects = self::make_array($this->text_path['TAXON_DATAOBJECT_PATH'], $fields, "int_id", array(0,4,5,7,17,18,19,20,21,22));
        $debug = array();
        $debug["sex"] = array();
        $debug["title"] = array();
        $debug["unit"] = array();
        $debug["method"] = array();
        $k = 0;
        
        foreach($taxa_objects as $taxon_id => $objects) { //taxon_id is int_id in FB text file
            $k++;
            foreach($objects as $o) {
                foreach($o as $key => $value) $o[$key] = str_replace("\N", "", $value);
                if($val = @$this->taxa_ids[$taxon_id]) $taxonID = $val;
                else continue;
                $description = utf8_encode($o['dc_description']);
                
                //for TraitBank
                $rec = array();
                $rec["taxon_id"] = $taxonID;
                $rec["catnum"] = $o['dc_identifier'];
                $orig_catnum = $o['dc_identifier'];
                if(substr($o['dc_source'],0,4) == "http")                          $rec["source"]      = $o['dc_source'];
                if($reference_ids = @$this->object_reference_ids[$o['int_do_id']]) $rec["referenceID"] = implode("; ", $reference_ids);
                if($agent_ids = @$this->object_agent_ids[$o['int_do_id']])         $rec["contributor"] = self::convert_agent_ids_with_names($agent_ids);
                
                if($o['subject'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size" || $o['subject'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat") {
                    if($o['subject'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size") {
                        $str = str_ireplace("unsexed;", "unsexed", $description);
                        $parts = self::get_description_parts($str, false);
                        $items = self::process_size_data($parts);
                    }
                    elseif($o['subject'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat") {
                        $parts = self::get_description_parts($description, false); 
                        $items = self::process_habitat_data($parts);
                    }
                    foreach($items as $item) {
                        $rec["catnum"] = '';
                        $rec["referenceID"] = '';
                        $rec["measurementMethod"] = '';
                        $rec["statisticalMethod"] = '';
                        $rec["measurementRemarks"] = '';
                        $rec["measurementUnit"] = '';
                        $rec["sex"] = '';
                        if($item['value'] === "") exit("\nblank value\n");
                        
                        if($val = @$item['range_value']) $rec["catnum"] = $orig_catnum . "_" . md5($item['measurement'].$val);
                        else                             $rec["catnum"] = $orig_catnum . "_" . md5($item['measurement'].$item['value'].@$item['mRemarks']); //specifically used for TraitBank; mRemarks is added to differentiate e.g. freshwater and catadromous.
                        
                        if($val = @$item['ref_id']) {
                            if($ref_ids = self::convert_FBrefID_with_archiveID($val)) $rec["referenceID"] = implode("; ", $ref_ids);
                            // else print_r($items);
                        }
                        if($val = @$item['mMethod'])  $rec['measurementMethod'] = $val;
                        if($val = @$item['sMethod'])  $rec['statisticalMethod'] = $val;
                        if($val = @$item['mRemarks']) $rec['measurementRemarks'] = $val;
                        if($val = @$item['unit'])     $rec['measurementUnit'] = $val;
                        if($val = @$item['sex'])      $rec['sex'] = $val;
                        self::add_string_types($rec, $item['value'], $item['measurement'], "true");
                    }
                }
                elseif($o['subject'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution") {
                    // self::add_string_types($rec, $description, "http://eol.org/schema/terms/Present", "true"); => changed to what is below, per DATA-1630
                    $texts = self::process_distribution_text($description);
                    foreach($texts as $text) {
                        $rec["referenceID"] = '';
                        if($val = @$text['reference_ids']) {
                            if($ref_ids = self::convert_FBrefID_with_archiveID($val)) $rec["referenceID"] = implode("; ", $ref_ids);
                        }
                        self::add_string_types($rec, $text['desc'], "http://eol.org/schema/terms/Present", "true");
                    }
                    
                }
                else { // regular data objects
                    $mr = new \eol_schema\MediaResource();
                    $mr->taxonID        = $taxonID;
                    $mr->identifier     = $o['dc_identifier'];
                    $mr->type           = $o['dataType'];
                    $mr->language       = 'en';
                    $mr->format         = $o['mimeType'];
                    if(substr($o['dc_source'], 0, 4) == "http") $mr->furtherInformationURL = self::use_best_fishbase_server($o['dc_source']);
                    $mr->accessURI      = self::use_best_fishbase_server($o['mediaURL']);
                    $mr->thumbnailURL   = self::use_best_fishbase_server($o['thumbnailURL']);
                    $mr->CVterm         = $o['subject'];
                    $mr->Owner          = $o['dc_rightsHolder'];
                    $mr->rights         = $o['dc_rights'];
                    $mr->title          = $o['dc_title'];
                    $mr->UsageTerms     = $o['license'];
                    // $mr->audience       = 'Everyone';
                    $mr->description    = utf8_encode($o['dc_description']);
                    if(!Functions::is_utf8($mr->description)) continue;
                    $mr->LocationCreated = $o['location'];
                    $mr->bibliographicCitation = $o['dcterms_bibliographicCitation'];
                    if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
                    if($agent_ids     =     @$this->object_agent_ids[$o['int_do_id']])  $mr->agentID = implode("; ", $agent_ids);
                    
                    if(!isset($this->object_ids[$mr->identifier])) {
                        $this->archive_builder->write_object_to_file($mr);
                        $this->object_ids[$mr->identifier] = '';
                    }
                }
            }
            // if($k > 10) break; //debug
        }
    }
    private function use_best_fishbase_server($url)
    {
        if(trim($url)) return str_ireplace('fishbase.us', 'fishbase.org', $url);
    }
    private function process_taxa_comnames()
    {
        $fields = array("commonName", "xml_lang", "int_id");
        $taxon_comnames = self::make_array($this->text_path['TAXON_COMNAMES_PATH'], $fields, "int_id");
        foreach($taxon_comnames as $taxon_id => $names) //taxon_id is int_id in FB text file
        {
            if(!$taxon_id = trim($taxon_id)) continue;
            foreach($names as $name) {
                foreach($name as $key => $value) $name[$key] = str_replace("\N", "", $value);
                if(!Functions::is_utf8($name['commonName'])) continue;
                $v = new \eol_schema\VernacularName();
                $v->taxonID         = $this->taxa_ids[$taxon_id];
                $v->vernacularName  = trim($name['commonName']);
                $v->language        = $name['xml_lang'];
                $this->archive_builder->write_object_to_file($v);
            }
        }
    }
    
    private function process_taxa_references()
    {
        $fields = array("reference", "bici", "coden", "doi", "eissn", "handle", "isbn", "issn", "lsid", "oclc", "sici", "url", "urn", "int_id", "timestamp", "autoctr");
        $taxon_references = self::make_array($this->text_path['TAXON_REFERENCES_PATH'], $fields, "int_id", array(1,2,3,4,5,7,8,9,10,12,14,15));
        foreach($taxon_references as $taxon_id => $refs) //taxon_id is int_id in FB text file
        {
            $reference_ids = self::create_references($refs);
            $this->taxa_reference_ids[$taxon_id] = $reference_ids;
        }
    }
    
    private function process_taxa()
    {
        $fields = array("TaxonID", "dc_identifier", "dc_source", "dwc_Kingdom", "dwc_Phylum", "dwc_Class", "dwc_Order", "dwc_Family", "dwc_Genus", "dwc_ScientificName", "dcterms_created", "dcterms_modified", "int_id", "ProviderID");
        $taxa = self::make_array($this->text_path['TAXON_PATH'], $fields, "", array(0,10,11,13));
        if($taxa === false) return false;
        foreach($taxa as $t) {
            $this->taxa_ids[$t['int_id']] = $t['dc_identifier'];
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $t['dc_identifier'];
            $taxon->scientificName  = utf8_encode($t['dwc_ScientificName']);
            $taxon->kingdom         = $t['dwc_Kingdom'];
            $taxon->phylum          = $t['dwc_Phylum'];
            $taxon->class           = $t['dwc_Class'];
            $taxon->order           = $t['dwc_Order'];
            $taxon->family          = $t['dwc_Family'];
            $taxon->genus           = $t['dwc_Genus'];
            $taxon->furtherInformationURL = $t['dc_source'];
            if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
            $this->archive_builder->write_object_to_file($taxon);
        }
    }

    function make_array($filename, $fields, $index_key="", $excluded_fields=array(), $separator="\t")
    {
        $data = array();
        $included_fields = array();
        foreach(new FileIterator($filename) as $line_number => $line) {
            if($line) {
                $line = str_ireplace("\	", "", $line); //manual adjustment
                $line = trim($line);
                $values = explode($separator, $line);
                $i = 0;
                $temp = array();
                $continue_save = false;
                if(!$fields) $fields = array_map('trim', $values);
                foreach($fields as $field) {
                    if(is_int(@$excluded_fields[0])) $compare = $i;
                    else                             $compare = $field;
                    if(!in_array($compare, $excluded_fields)) {
                        $temp[$field] = trim(@$values[$i]);
                        $included_fields[$field] = 1;
                        if($temp[$field] != "") $continue_save = true; // as long as there is a single field with value then the row will be saved
                    }
                    $i++;
                }
                if($continue_save) $data[] = $temp;
            }
        }
        $included_fields = array_keys($included_fields);
        if($index_key) {
            $included_fields = array_unique($included_fields);
            return self::assign_key_to_table($data, $index_key, $included_fields);
        }
        else return $data;
    }

    function assign_key_to_table($table, $index_key, $included_fields)
    {
        $data = array();
        $included_fields = array_diff($included_fields, array($index_key));
        foreach($table as $record) {
            $index_value = $record["$index_key"];
            $temp = array();
            foreach($included_fields as $field) $temp[$field] = $record[$field];
            $data[$index_value][] = $temp;
        }
        return $data;
    }

    function get_references($references)
    {
        // might need or not need this...
        $ref = utf8_encode($reference['reference']);
        if(Functions::is_utf8($ref)) $refs[] = array("url" => $reference['url'], "fullReference" => Functions::import_decode($ref));
    }

    function get_common_names($names)
    {
        // might need or not need this...
        $common = utf8_encode($name['commonName']);
        if(Functions::is_utf8($common)) $arr_names[] = array("name" => Functions::import_decode($common), "language" => $name['xml_lang']);
    }

    private function process_size_data($parts)
    {
        $records = array();
        foreach($parts as $part) {
            $rec = array();
            if(stripos($part, ":") !== false) //found a colon ':'
            {   //max. reported age: 33 years (Ref. 93630)
                $arr = explode(":", $part);
                $rec["title"] = trim($arr[0]);
                $right_of_colon = trim($arr[1]);
                $arr = explode(" ", $right_of_colon);
                $rec["value"] = $arr[0];
                $rec["unit"] = $arr[1];
            }
            else {   //33.7 cm SL (male/unsexed (Ref. 93606))
                if($val = self::get_sex_from_size_str($part)) $rec["sex"] = $val;
                $rec["title"] = "max. size";
                $arr = explode(" ", $part);
                $rec["value"] = $arr[0];
                $rec["unit"] = $arr[1];
                if(preg_match("/" . $rec["unit"] . "(.*?)\(/ims", $part, $arr)) $rec["method"] = trim($arr[1]);
            }
            if($val = self::get_ref_id($part)) $rec["ref_id"] = $val;
            if($rec) $records[] = $rec;
        }
        //start creating Traitbank record
        $final = array();
        $valid_lengths = array("SL", "TL", "FL", "WD");
        foreach($records as $rec) {
            $r = array();
            if($rec['title'] == "max. size") {
                if(!in_array($rec['method'], $valid_lengths)) continue;
                if($measurement = $rec['method']) $r['measurement'] = $this->uris[$measurement];
            }
            else {
                $r['measurement'] = $this->uris[$rec['title']];
                $measurement = $rec['title'];
            }
            $r['value'] = $rec['value'];
            if($val = @$rec['sex'])                            $r['sex']        = $this->uris[$val];
            if($val = @$rec['unit'])                           $r['unit']       = $this->uris[$val];
            if($val = @$rec['ref_id'])                         $r['ref_id']     = $val;
            if($val = @$this->uris["$measurement (mMethod)"])  $r['mMethod']    = $val;
            if($val = @$this->uris["$measurement (sMethod)"])  $r['sMethod']    = $val;
            if($val = @$this->uris["$measurement (mRemarks)"]) $r['mRemarks']   = $val;
            if($r) $final[] = $r;
        }
        return $final;
    }
    
    private function get_sex_from_size_str($string)
    {
        if(strpos($string, "male/unsexed") !== false) return "male/unsexed";
        elseif(strpos($string, "female") !== false) return "female";
        return false;
    }

    private function get_ref_id($string)
    {
        // if(preg_match_all("/Ref\.(.*?)\)/ims", $string, $arr)) return $arr[1];
        if(preg_match_all("/Ref\.(.*?)\)/ims", $string, $arr)) return array_map('trim', $arr[1]);
        return false;
    }
    
    private function process_habitat_data($parts)
    {
        $records = array();
        foreach($parts as $part) {
            $rec = array();
            if(self::is_habitat_a_range($part)) {
                $arr = explode(" range", $part);
                $rec["title"] = $arr[0] . " range";
                
                if(@$arr[1]) {
                    $arr2 = explode(", usually", $arr[1]);
                    $arr2 = array_map('trim', $arr2);
                }
                else // e.g. usually ? - 10 m (Ref. 5595) range
                {
                    $arr = explode("usually ", $part);
                    $rec["title"] = $arr[0] . "depth range"; //this is actually - usual range
                    $arr2 = explode(", usually", $arr[1]);
                    $arr2 = array_map('trim', $arr2);
                }
                
                $rec["value"] = trim(str_replace(array(":"), "", $arr2[0]));
                $rec["value"] = trim(preg_replace('/\s*\([^)]*\)/', '', $rec["value"])); //remove parenthesis
                
                if($val = self::get_range_unit($rec["value"])) {
                    $rec["unit"] = $val;
                    $rec["value"] = str_ireplace(" $val", "", $rec["value"]);
                }
                
                //get min max values
                $temp = explode("-", $rec["value"]);
                $temp = array_map('trim', $temp);
                $rec["min"] = @$temp[0];
                $rec["max"] = @$temp[1];
                
                if($val = @$arr2[1]) $rec["remarks"] = "usually " . $val;
                if($val = self::get_ref_id($part)) $rec["ref_id"] = $val;
            }
            else {
                $rec["value"] = trim(preg_replace('/\s*\([^)]*\)/', '', $part)); //remove parenthesis
                if($val = self::get_ref_id($part)) $rec["ref_id"] = $val;
            }
            if($rec) $records[] = $rec;
        }
        
        // print_r($records);
        //start creating Traitbank record
        $final = array();
        foreach($records as $rec) {
            if(@$rec['title'] == "dH range") continue;
            
            if(!@$rec['title']) { // meaning habitat valuese e.g. demersal, freshwater, non-migratory*
                $two_values = array("catadromous", "anadromous", "diadromous", "amphidromous", "oceano-estuarine");
                if(!in_array($rec['value'], $two_values)) {
                    $r = array();
                    if($rec['value'] == "non-migratory")    $r['measurement'] = "http://www.owl-ontologies.com/unnamed.owl#MigratoryStatus";
                    else                                    $r['measurement'] = $this->uris['habitat'];
                    $measurement = $rec['value'];
                    $r['value'] = $this->uris[$measurement];
                    
                    if($r['value'] == "EXCLUDE") continue;
                    
                    if($val = @$rec['ref_id']) $r['ref_id'] = $val;
                    if($val = @$this->uris["$measurement (mRemarks)"]) $r['mRemarks'] = $val;
                    if($r) $final[] = $r;
                }
                else { //two values
                    $r = array();
                    $r['measurement'] = $this->uris['habitat'];
                    $measurement = $rec['value'];
                    $temp = explode(",", $this->uris[$measurement]);
                    $temp = array_map('trim', $temp);
                    foreach($temp as $t) { //enter each of the multiple values
                        $r['value'] = $t;
                        if($val = @$rec['ref_id']) $r['ref_id'] = $val;
                        if($val = @$this->uris["$measurement (mRemarks)"]) $r['mRemarks'] = $val;
                        if($r) $final[] = $r;
                    }
                }
            }
            else { // "pH range" OR "depth range"
                $r = array();
                $r['range_value'] = $rec['value'];
                if($rec['title'] == "depth range")  $measurement = "mindepth";
                elseif($rec['title'] == "pH range") $measurement = "min pH";
                $r['measurement'] = $this->uris[$measurement];
                $r['value'] = $rec['min'];
                if($val = @$rec['unit'])    $r['unit']      = $this->uris[$val];
                if($val = @$rec['ref_id'])  $r['ref_id']    = $val;
                if($rec['max']) {
                    if($val = @$this->uris["$measurement (sMethod)"])  $r['sMethod'] = $val;
                }
                if($r) $final[] = $r;
                if($rec['max']) {
                    $r = array();
                    $r['range_value'] = $rec['value'];
                    if($rec['title'] == "depth range")  $measurement = "maxdepth";
                    elseif($rec['title'] == "pH range") $measurement = "max pH";
                    $r['measurement'] = $this->uris[$measurement];
                    $r['value'] = $rec['max'];
                    if($val = @$rec['unit'])    $r['unit']      = $this->uris[$val];
                    if($val = @$rec['ref_id'])  $r['ref_id']    = $val;
                    if($val = @$this->uris["$measurement (sMethod)"])  $r['sMethod'] = $val;
                    if($r) $final[] = $r;
                }
            }
        }
        return $final;
    }
    
    private function get_range_unit($string)
    {
        $arr = explode(" ", $string);
        $char = $arr[count($arr)-1];
        if(!is_numeric(substr($char,0,1)) && !in_array($char, array("?"))) return $char;
        else return false;
    }
    
    private function is_habitat_a_range($habitat)
    {
        $ranges = array("depth range", "dH range", "pH range", "usually ");
        foreach($ranges as $range) {
            if(stripos($habitat, $range) !== false) return true;
        }
        return false;
    }

    private function convert_FBrefID_with_archiveID($FB_ref_ids)
    {
        $final = array();
        foreach($FB_ref_ids as $id) {
            if($val = @$this->reference_ids[$id]) $final[] = $val;
            else {
                echo "\nundefined ref_id: [$id] ";
                if($val = self::get_ref_details_from_fishbase_and_create_ref($id)) {
                    echo " -- FOUND: Salvaged ref_id"; //last run didn't find anything here.
                    $final[] = $val;
                }
            }
        }
        return $final;
    }
    
    private function get_uris()
    {
        $fields["value"]    = "value_uri"; //a generic spreadsheet
        $params["fields"]   = $fields;
        $params["dataset"]  = "FishBase";
        
        $spreadsheet_options = array('resource_id' => 'gbif', 'cache' => 1, 'timeout' => 3600, 'file_extension' => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2); //set 'cache' to 0 if you don't want to cache spreadsheet
        $spreadsheet_options['expire_seconds'] = 60*60; //expires after 1 hour
        $params['spreadsheet_options'] = $spreadsheet_options;
        
        require_library('connectors/GBIFCountryTypeRecordAPI');
        $func = new GBIFCountryTypeRecordAPI("x");
        return $func->get_uris($params, $this->uri_mappings_spreadsheet);
    }
    
    private function convert_agent_ids_with_names($agent_ids)
    {
        $arr = array();
        foreach($agent_ids as $agent_id) {
            if($val = @$this->agent_ids[$agent_id]) $arr[$val] = '';
        }
        $arr = array_keys($arr);
        return implode(";", $arr);
    }

    private function clean_text_file($file_path)
    {
        echo "\nUpdating $file_path";
        //read
        if(!($file = Functions::file_open($file_path, "r"))) return;
        $contents = fread($file, filesize($file_path));
        fclose($file);
        $contents = str_ireplace(chr(10).chr(13)."\\", "", $contents);
        //write
        if(!($TMP = Functions::file_open($file_path, "w"))) return;
        fwrite($TMP, $contents);
        fclose($TMP);
        echo "\nChanges saved\n"; exit;
    }

    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"];
        $occurrence_id = $catnum; // simply used catnum
        
        //start special -------------------------------------------------------------
        $var = md5($measurementType . $value . $taxon_id);
        if(isset($this->unique_measurements[$var])) return;
        //end special -------------------------------------------------------------
        
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $occurrence_id, $rec);
        $m->occurrenceID       = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        if($measurementOfTaxon == "true") {
            $m->source      = @$rec["source"];
            $m->contributor = @$rec["contributor"];
            if($referenceID = @$rec["referenceID"]) $m->referenceID = $referenceID;
        }
        $m->measurementType  = $measurementType;
        $m->measurementValue = $value;
        $m->bibliographicCitation = $this->bibliographic_citation;
        if($val = @$rec['measurementUnit'])     $m->measurementUnit = $val;
        if($val = @$rec['measurementMethod'])   $m->measurementMethod = $val;
        if($val = @$rec['statisticalMethod'])   $m->statisticalMethod = $val;
        if($val = @$rec['measurementRemarks'])  $m->measurementRemarks = $val;
        // $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue'));
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);
        
        //start of special -------------------------------------------------------------
        $var = md5($m->measurementType . $m->measurementValue . $taxon_id);
        $this->unique_measurements[$var] = '';
        //end special -------------------------------------------------------------
    }

    private function add_occurrence($taxon_id, $occurrence_id, $rec)
    {
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        if($val = @$rec['sex']) $o->sex = $val;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }

    private function get_description_parts($string, $for_stats = true)
    {
        //bathydemersal; marine; depth range 50 - 700 m (Ref. 56504)
        if($for_stats) $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
        if($for_stats) $string = self::remove_numeric_from_string($string);
        $string = str_ireplace("marine, usually", "marine; usually", $string);
        $string = str_ireplace("freshwater, usually", "freshwater; usually", $string);
        $string = str_ireplace("brackish, usually", "brackish; usually", $string);
        $string = str_ireplace("(Ref. )", "", $string);
        $arr = explode(";", $string);
        return array_map('trim', $arr);
    }

    private function remove_numeric_from_string($string)
    {
        $digits = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0", " - ", "usually", "?");
        return str_ireplace($digits, '', $string);
    }

    private function clean_html($html)
    {
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        return Functions::remove_whitespace($html);
    }
    */
}
?>