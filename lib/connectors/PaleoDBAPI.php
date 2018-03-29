<?php
namespace php_active_record;
// connector: [368]; formerly [719]
class PaleoDBAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->taxa = array();
        $this->name_id = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->download_options = array('cache' => 1, 'resource_id' => $folder, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1, 
        'expire_seconds' => 60*60*24*30*3); //cache expires in 3 months // orig
        $this->download_options['expire_seconds'] = false; //debug

        $this->occurrence_ids = array();
        $this->invalid_names_status = array("replaced by", "invalid subgroup of", "nomen dubium", "nomen nudum", "nomen vanum", "nomen oblitum");
        
        $this->service["taxon"] = "https://paleobiodb.org/data1.1/taxa/list.csv?rel=all_taxa&status=valid&show=attr,app,size,phylo,ent,entname,crmod&limit=1000000";

        /* paleobiodb.csv - old version, with just 32 cols
        // $this->service["taxon"] = "https://dl.dropboxusercontent.com/u/7597512/PaleoDB/paleobiodb.csv";
        // $this->service["taxon"] = "http://localhost/cp/PaleoDB/paleobiodb_small.csv";
        */
        
        /* pbdb_taxa.csv - new version with 33 cols - working as of 11-Jul-2016
        // $this->service["taxon"] = "https://dl.dropboxusercontent.com/u/5763406/resources/PaleoDB/pbdb_taxa.csv";
        $this->service["taxon"] = "http://localhost/cp/PaleoDB/pbdb_taxa.csv";
        // $this->service["taxon"] = "http://localhost/cp/PaleoDB/pbdb_taxa_small.csv";
        */

        $this->service["collection"] = "http://paleobiodb.org/data1.1/colls/list.csv?vocab=pbdb&limit=10&show=bin,attr,ref,loc,paleoloc,prot,time,strat,stratext,lith,lithext,geo,rem,ent,entname,crmod&taxon_name=";
        $this->service["occurrence"] = "http://paleobiodb.org/data1.1/occs/list.csv?show=loc,time&limit=10&base_name=";
        $this->service["reference"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=displayRefResults&type=view&reference_no=";
        $this->service["source"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=checkTaxonInfo&is_real_user=1&taxon_no=";

        $this->taxon_no_of_cols = 33; //old version value is 32
        /*
        new ver. 33 "taxon_no","orig_no","record_type","associated_records","rank","taxon_name","common_name","attribution","pubyr","status","parent_no","senior_no","reference_no","is_extant","firstapp_ea","firstapp_la","lastapp_ea","lastapp_la","size","extant_size","kingdom","phylum","class","order","family","authorizer_no","enterer_no","modifier_no","authorizer","enterer","modifier","created","modified"
        old ver. 32 "taxon_no","orig_no","record_type",                     "rank","taxon_name","common_name","attribution","pubyr","status","parent_no","senior_no","reference_no","is_extant","firstapp_ea","firstapp_la","lastapp_ea","lastapp_la","size","extant_size","kingdom","phylum","class","order","family","authorizer_no","enterer_no","modifier_no","authorizer","enterer","modifier","created","modified"
        
        ranks so far:
            [kingdom] => 
            [unranked clade] => 
            [subkingdom] => 
            [phylum] => 
            [subphylum] => 
            [superclass] => 
            [class] => 
            [subclass] => 
            [infraclass] => 
            [infraorder] => 
            [order] => 
            [superorder] => 
            [informal] => 
            [suborder] => 
            [superfamily] => 
            [family] => 
            [subfamily] => 
            [genus] => 
            [superphylum] => 
            [tribe] => 
            [subtribe] => 
            [subgenus] => 
            [species] => 
            [subspecies] => 
            
        valid status:
            [subjective synonym of] - 16,223 | 16,409
            [objective synonym of]  - 399 | 407
            [belongs to]            - 210,214 | 211,733
        */
    }

    function get_all_taxa()
    {
        $this->path['temp_dir'] = DOC_ROOT . $GLOBALS['MAIN_CACHE_PATH'] . "368/";
        $this->parse_csv_file("taxon");
        $this->create_archive();
        // stats
        print_r($this->debug["rank"]);
        print_r($this->debug["is_extant"]);
        $statuses = array_keys($this->debug["status"]);
        print_r($statuses);
        foreach($statuses as $status) echo "\n $status: " . count($this->debug["status"][$status]);
        echo "\nDeleting temporary folder...";
    }

    private function parse_csv_file($type, $taxon = array())
    {
        echo "\n Processing $type...\n";
        if($type == "collection") {
            $no_of_fields = 68;
            if(!in_array(@$taxon["rank"], array("species", "subspecies"))) return;
            $taxon_id = $taxon["orig_no"];
            $url = $this->service[$type] . $taxon["taxon_name"];
            $path = Functions::save_remote_file_to_local($url, $this->download_options);
        }
        elseif($type == "occurrence") {
            $no_of_fields = 25;
            if(!in_array(@$taxon["rank"], array("species", "subspecies"))) return;
            $taxon_id = $taxon["orig_no"];
            $url = $this->service[$type] . $taxon["taxon_name"];
            $path = Functions::save_remote_file_to_local($url, $this->download_options);
        }
        elseif($type == "taxon") {
            $no_of_fields = $this->taxon_no_of_cols;
            $download_options = $this->download_options;
            $download_options['timeout'] = 999999;
            $path = Functions::save_remote_file_to_local($this->service["taxon"], $download_options);
        }

        $j = 0;
        foreach(new FileIterator($path) as $line_number => $line)
        {
            $rec = array();
            $j++;
            if(($j % 25000) == 0) echo "\n$j. [$type]";
            // if($j >= 1000) break; //debug
            if($line)
            {
                $line = trim($line);
                if($j == 1) {
                    $fields = explode(",", $line);
                    continue;
                }
                else
                {
                    $values = explode(",", $line);
                    $values = str_getcsv($line);
                    if(count($values) == $no_of_fields) {
                        $i = 0;
                        foreach($values as $value) {
                            $field = str_replace('"', '', $fields[$i]);
                            $rec[$field] = str_replace('"', '', $value);
                            $i++;
                        }
                    }
                    else {
                        print_r($values);
                        echo "\n investigate rec is not $no_of_fields";
                    }
                }
                if($rec) {
                    if    ($type == "collection")   self::process_taxon_collection($rec, $taxon_id, $url);
                    elseif($type == "occurrence")   self::process_taxon_occurrence($rec, $taxon_id, $url);
                    elseif($type == "taxon")        self::process_taxon($rec);
                }
            }
        }
        unlink($path);
    }
    
    private function no_zero_value($val)
    {
        $val = trim((string) $val);
        if($val == 0) return "";
        if($val == "0") return "";
        return $val;
    }
    private function process_taxon($rec)
    {
        // for stats
        $this->debug["rank"][$rec["rank"]] = '';
        $this->debug["status"][$rec["status"]][$rec["orig_no"]] = '';
        $this->debug["is_extant"][$rec["is_extant"]] = '';

        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec["orig_no"];
        $taxon->scientificName              = $rec["taxon_name"];
        $taxon->scientificNameAuthorship    = $rec["attribution"];
        $taxon->taxonRank                   = $rec["rank"];
        
        $ancestry = array();
        $ancestry['kingdom']                     = $rec["kingdom"];
        $ancestry['phylum']                      = $rec["phylum"];
        $ancestry['class']                       = $rec["class"];
        $ancestry['order']                       = $rec["order"];
        $ancestry['family']                      = $rec["family"];
        self::save_ancestry_to_json($taxon->taxonID, $ancestry);
        
        $taxon->parentNameUsageID           = self::no_zero_value($rec["parent_no"]);
        if($rec["senior_no"] != $rec["orig_no"]) $taxon->acceptedNameUsageID = $rec["senior_no"];
        else                                     $taxon->acceptedNameUsageID = '';
        $taxon->taxonomicStatus             = self::process_status($rec["status"]);
        // if($taxon->taxonomicStatus == "synonym") return; //debug - exclude synonyms during preview phase
        if(!isset($this->taxa[$taxon->taxonID])) {
            $this->taxa[$taxon->taxonID] = $taxon;
            $this->name_id[$taxon->scientificName] = $taxon->taxonID;
            
            if($v = $rec["common_name"]) {
                $vernacular = new \eol_schema\VernacularName();
                $vernacular->taxonID = $taxon->taxonID;
                $vernacular->vernacularName = $v;
                $vernacular->language = 'en';
                $this->archive_builder->write_object_to_file($vernacular);
            }
            $type = "taxon";
            if($val = self::process_extant($rec["is_extant"])) self::add_string_types($rec, "is_extant", $val, "http://eol.org/schema/terms/ExtinctionStatus", "true", $type);
            if($val = $rec["firstapp_ea"]) self::add_string_types($rec, "firstapp_ea", $val, "http://eol.org/schema/terms/paleo_firstapp_ea", "true", $type);
            if($val = $rec["firstapp_la"]) self::add_string_types($rec, "firstapp_la", $val, "http://eol.org/schema/terms/paleo_firstapp_la", "true", $type);
            if($val = $rec["lastapp_ea"]) self::add_string_types($rec, "lastapp_ea", $val, "http://eol.org/schema/terms/paleo_lastapp_ea", "true", $type);
            if($val = $rec["lastapp_la"]) self::add_string_types($rec, "lastapp_la", $val, "http://eol.org/schema/terms/paleo_lastapp_la", "true", $type);
            // disabled at the moment
            // self::parse_csv_file("collection", $rec);
            // self::parse_csv_file("occurrence", $rec);
        }
    }
    
    private function save_ancestry_to_json($taxon_id, $ancestry) //opposite of get_ancestry_from_json()
    {
        $json = json_encode($ancestry);
        $main_path = $this->path['temp_dir'];
        $md5 = md5($taxon_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$taxon_id.json";

        if(file_exists($filename)) {
            $file_age_in_seconds = time() - filemtime($filename);
            if($file_age_in_seconds < $this->download_options['expire_seconds'])    return; //no need to save
            if($this->download_options['expire_seconds'] === false)                 return; //no need to save
        }
        //saving...
        $FILE = Functions::file_open($filename, 'w');
        fwrite($FILE, $json);
        fclose($FILE);
    }
    private function get_ancestry_from_json($taxon_id) //opposite of save_ancestry_to_json()
    {
        $main_path = $this->path['temp_dir'];
        $md5 = md5($taxon_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $filename = $main_path . "$cache1/$cache2/$taxon_id.json";
        if(file_exists($filename)) {
            $json = file_get_contents($filename);
            return json_decode($json, true);
        }
        else return array();
    }

    private function get_reference_ids($ref_nos)
    {
        $ref_nos = explode(",", $ref_nos);
        if(!$ref_nos) return array();
        $ref_nos = array_map('trim', $ref_nos);
        $reference_ids = array();
        foreach($ref_nos as $ref_no) {
            $url = $this->service["reference"] . $ref_no;
            if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                if(preg_match("/Full reference<\/span>(.*?)<span/ims", $html, $arr)) {
                    $full_ref = strip_tags($arr[1], "<i>");
                    $r = new \eol_schema\Reference();
                    $r->full_reference = $full_ref;
                    $r->identifier = md5($r->full_reference);
                    $r->uri = $url;
                    $reference_ids[] = $r->identifier;
                    if(!isset($this->resource_reference_ids[$r->identifier])) {
                       $this->resource_reference_ids[$r->identifier] = '';
                       $this->archive_builder->write_object_to_file($r);
                    }
                }
            }
        }
        return $reference_ids;
    }
    /* disabled for now
    private function process_taxon_occurrence($rec, $taxon_id, $source_url)
    {
        $rec["orig_no"] = $taxon_id;
        $rec["source_url"] = $source_url;
        $type = "occurrence";
        if($val = $rec["occurrence_no"])    self::add_string_types($rec, "occurrence_no", $val, "http://rs.tdwg.org/dwc/terms/occurrenceID", "true", $type);
        if($val = $rec["collection_no"])    self::add_string_types($rec, "collection_no", $val, "http://rs.tdwg.org/dwc/terms/collectionCode", "false", $type);
        if($val = $rec["early_int_no"])     self::add_string_types($rec, "early_int_no", $val, "http://eol.org/schema/terms/paleo_firstapp_ei", "false", $type);
        if($val = $rec["late_int_no"])      self::add_string_types($rec, "late_int_no", $val, "http://eol.org/schema/terms/paleo_firstapp_li", "false", $type);
        if($val = $rec["taxon_name"])       self::add_string_types($rec, "taxon_name", $val, "http://rs.tdwg.org/dwc/terms/scientificName", "false", $type);
        if($val = $rec["state"])            self::add_string_types($rec, "state", $val, "http://rs.tdwg.org/dwc/terms/stateProvince", "false", $type);
        if($val = $rec["county"])           self::add_string_types($rec, "county", $val, "http://rs.tdwg.org/dwc/terms/county", "false", $type);
        if($val = $rec["cc"])               self::add_string_types($rec, "cc", $val, "http://rs.tdwg.org/dwc/terms/country", "false", $type);
    }
    
    private function process_taxon_collection($rec, $taxon_id, $source_url)
    {
        $rec["orig_no"] = $taxon_id;
        $rec["source_url"] = $source_url;
        $type = "collection";
        if($val = $rec["collection_name"])  self::add_string_types($rec, "collection_name", $val, "", "true", $type);
        if($val = $rec["collection_no"])    self::add_string_types($rec, "collection_no", $val, "http://rs.tdwg.org/dwc/terms/collectionCode", "false", $type);
        if($val = $rec["early_int_no"])     self::add_string_types($rec, "early_int_no", $val, "http://eol.org/schema/terms/paleo_firstapp_ei", "false", $type);
        if($val = $rec["late_int_no"])      self::add_string_types($rec, "late_int_no", $val, "http://eol.org/schema/terms/paleo_firstapp_li", "false", $type);
    }
    */
    private function process_status($status)
    {
        if($status == "belongs to") return "valid";
        elseif(is_numeric(stripos($status, "synonym"))) return "synonym";
        elseif(in_array($status, $this->invalid_names_status)) return "invalid";
        else return $status;
    }
    
    private function process_extant($extant)
    {
        if($extant == "") return false;
        elseif($extant == 1) return "http://eol.org/schema/terms/extant";
        elseif($extant == 0) return "http://eol.org/schema/terms/extinct";
        else return false;
    }
    
    private function add_string_types($rec, $label, $value, $measurementType, $measurementOfTaxon, $type)
    {
        $taxon_id = $rec['orig_no'];
        if($type == "collection") $id = $rec["collection_no"];
        if($type == "occurrence") $id = $rec["occurrence_no"];
        else            $id = $label;
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id,  $id);
        $m->occurrenceID = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        $m->measurementType = $measurementType;
        $m->measurementValue = $value;
        if($val = @$rec["source_url"]) $m->source = $val;
        if($label == "is_extant") {
            // if($reference_ids = self::get_reference_ids(trim($rec["reference_no"]))) $m->referenceID = implode("; ", $reference_ids); deliberately commented for now.
        }
        if($measurementOfTaxon == "true") $m->source = $this->service["source"] . $taxon_id;
        if(in_array($label, array("firstapp_ea", "firstapp_la", "lastapp_ea", "lastapp_la"))) $m->measurementUnit = "http://eol.org/schema/terms/paleo_megaannum";
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $id)
    {
        $occurrence_id = $taxon_id . '_' .  $id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;

        /* old ways
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
        */
    }

    function create_archive()
    {
        echo "\n Creating archive...\n";
        foreach($this->taxa as $t) {
            if(!isset($this->taxa[$t->parentNameUsageID]) && $t->parentNameUsageID) {
                // print "\n parent_id of $t->taxonID does not exist:[$t->parentNameUsageID]";
                if    ($id = self::create_missing_taxon($t))       $t->parentNameUsageID = self::no_zero_value($id);
                elseif($id = self::get_missing_parent_via_api($t)) $t->parentNameUsageID = self::no_zero_value($id);
                else                                               $t->parentNameUsageID = "";
                // echo " - new parent id = [$t->parentNameUsageID]\n";
            }
            if(!isset($this->taxa[$t->acceptedNameUsageID]) && $t->acceptedNameUsageID) {
                // print "\n acceptedNameUsageID of $t->taxonID does not exist:[$t->acceptedNameUsageID]";
                $t->acceptedNameUsageID = '';
            }
            
            // check if parent_id is a synonym, if yes get the acceptedNameUsageID of the synonym taxon as parent_id
            if($taxon_id = $t->parentNameUsageID) {
                if(@$this->taxa[$taxon_id]->taxonomicStatus == "synonym") {
                    $t->parentNameUsageID = self::no_zero_value($this->taxa[$taxon_id]->acceptedNameUsageID);
                    // echo "\n parent_id of $t->taxonID is replaced from: [$taxon_id] to: [$t->parentNameUsageID]\n";
                }
            }
            
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }
    
    private function get_missing_parent_via_api($t)
    {
        $rnk[8]  = "subfamily";
        $rnk[5]  = "genus";
        $rnk[25] = ""; //sciname for it is "Life"
        $rnk[7]  = "tribe";         // parent_id of 188896 does not exist:[161173]
        $rnk[11] = "infraorder";    // parent_id of 193147 does not exist:[193148]
        $rnk[3]  = "species";       // parent_id of 232231 does not exist:[131664]
        $rnk[26] = "informal";      // parent_id of 297613 does not exist:[84242]
        $rnk[10] = "superfamily";   // parent_id of 306307 does not exist:[306306]
        $rnk[12] = "suborder";      // parent_id of 312506 does not exist:[312505]
        $rnk[18] = "superclass";    // parent_id of 162968 does not exist:[162969]
        $rnk[15] = "Infraclass";    // parent_id of 276031 does not exist:[247806]
        $rnk[4]  = "genus";         // parent_id of 61287 does not exist:[61315]
        
        $url = "https://paleobiodb.org/data1.1/taxa/single.json?id=" . $t->parentNameUsageID . "&show=attr";
        
        $ids_to_set_cache_expires = array(); //just a check, put here parent_ids that needed a fresh http access, cache expires.
        $download_options = $this->download_options;
        if(in_array($t->parentNameUsageID, $ids_to_set_cache_expires)) $download_options['expire_seconds'] = 0; //cache expires
        
        if($json = Functions::lookup_with_cache($url, $download_options))
        {
            /*[records] => Array
                (
                    [0] => stdClass Object
                        (
                            [oid] => 170201
                            [gid] => 170201
                            [typ] => txn
                            [rnk] => 5
                            [nam] => Acaciella
                            [att] => Walter et al. 2000
                            [par] => 0
                            [rid] => Array([0] => 33229)
                        )
                )
            */
            $obj = json_decode($json);
            foreach($obj->records as $rec) {
                // echo "\n" . $rec->nam;
                //start create archive
                $taxon = new \eol_schema\Taxon();
                $taxon->taxonID                  = $t->parentNameUsageID;
                $taxon->scientificName           = $rec->nam;
                $taxon->scientificNameAuthorship = @$rec->att;
                $taxon->parentNameUsageID        = self::no_zero_value(@$rec->par);
                
                if($rank = @$rnk[$rec->rnk]) $taxon->taxonRank = $rank;
                else {
                    if($rank != "") {
                        echo "\nundefined rank: [$rec->rnk]\n";
                        exit("\ninvestigate 001\n");
                    }
                }
                $taxon->taxonomicStatus = "valid";
                
                if(!isset($this->taxa[$taxon->taxonID])) {
                    $this->taxa[$taxon->taxonID] = '';
                    $this->archive_builder->write_object_to_file($taxon);
                    $this->name_id[$taxon->scientificName] = $taxon->taxonID;
                }
                // echo " --- $taxon->taxonID used as parent ccc \n";
                return $taxon->taxonID;
            }
        }
        
    }
    
    private function create_missing_taxon($t)
    {
        $ancestry = self::get_ancestry_from_json($t->taxonID);
        if(    $val = @$ancestry['family']  && $t->scientificName != @$ancestry['family'])  $info = array("sciname" => $val, "rank" => "family");
        elseif($val = @$ancestry['order']   && $t->scientificName != @$ancestry['order'])   $info = array("sciname" => $val, "rank" => "order");
        elseif($val = @$ancestry['class']   && $t->scientificName != @$ancestry['class'])   $info = array("sciname" => $val, "rank" => "class");
        elseif($val = @$ancestry['phylum']  && $t->scientificName != @$ancestry['phylum'])  $info = array("sciname" => $val, "rank" => "phylum");
        elseif($val = @$ancestry['kingdom'] && $t->scientificName != @$ancestry['kingdom']) $info = array("sciname" => $val, "rank" => "kingdom");
        else return false;
        if($id = @$this->name_id[$info["sciname"]]) // there is an existing taxon for the parent either from k.p.c.o.f.
        {
            // echo " --- $id used as parent aaa \n";
            return $id;
        }
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = str_replace(" ", "_", $info["sciname"]);
        $taxon->scientificName  = $info["sciname"];
        $taxon->taxonRank       = $info["rank"];
        $taxon->taxonomicStatus = "valid";
        if(!isset($this->taxa[$taxon->taxonID]))
        {
            $this->taxa[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
            $this->name_id[$taxon->scientificName] = $taxon->taxonID;
        }
        // echo " --- $taxon->taxonID used as parent bbb \n";
        return $taxon->taxonID;
    }

}
?>