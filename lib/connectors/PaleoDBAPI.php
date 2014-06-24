<?php
namespace php_active_record;
// connector: [368]; formerly [719]
class PaleoDBAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->resource_reference_ids = array();
        $this->download_options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->occurrence_ids = array();
        $this->invalid_names_status = array("replaced by", "invalid subgroup of", "nomen dubium", "nomen nudum", "nomen vanum", "nomen oblitum");
        $this->service["taxon"] = "http://paleobiodb.org/data1.1/taxa/list.csv?rel=all_taxa&status=valid&show=attr,app,size,phylo,ent,entname,crmod&limit=1000000";
        // $this->service["taxon"] = "https://dl.dropboxusercontent.com/u/7597512/PaleoDB/paleobiodb.csv";
        // $this->service["taxon"] = "http://localhost/~eolit/cp/PaleoDB/paleobiodb.csv";
        $this->service["collection"] = "http://paleobiodb.org/data1.1/colls/list.csv?vocab=pbdb&limit=10&show=bin,attr,ref,loc,paleoloc,prot,time,strat,stratext,lith,lithext,geo,rem,ent,entname,crmod&taxon_name=";
        $this->service["occurrence"] = "http://paleobiodb.org/data1.1/occs/list.csv?show=loc,time&limit=10&base_name=";
        $this->service["reference"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=displayRefResults&type=view&reference_no=";
        $this->service["source"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=checkTaxonInfo&is_real_user=1&taxon_no=";
        /*
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
            [subjective synonym of] - 16,223
            [objective synonym of]  - 399
            [belongs to]            - 210,214
        */
    }

    function get_all_taxa()
    {
        $this->parse_csv_file("taxon");
        $this->create_archive();
        // stats
        print_r($this->debug["rank"]);
        print_r($this->debug["is_extant"]);
        $statuses = array_keys($this->debug["status"]);
        print_r($statuses);
        foreach($statuses as $status) echo "\n $status: " . count($this->debug["status"][$status]);
        echo "\n";
    }

    private function parse_csv_file($type, $taxon = array())
    {
        echo "\n Processing $type...\n";
        if($type == "collection")
        {
            $no_of_fields = 68;
            if(!in_array(@$taxon["rank"], array("species", "subspecies"))) return;
            $taxon_id = $taxon["orig_no"];
            $url = $this->service[$type] . $taxon["taxon_name"];
            $path = Functions::save_remote_file_to_local($url, $this->download_options);
        }
        elseif($type == "occurrence")
        {
            $no_of_fields = 25;
            if(!in_array(@$taxon["rank"], array("species", "subspecies"))) return;
            $taxon_id = $taxon["orig_no"];
            $url = $this->service[$type] . $taxon["taxon_name"];
            $path = Functions::save_remote_file_to_local($url, $this->download_options);
        }
        elseif($type == "taxon")
        {
            $no_of_fields = 32;
            $path = Functions::save_remote_file_to_local($this->service["taxon"], array("timeout" => 999999, "cache" => 1));
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
                if($j == 1)
                {
                    $fields = explode(",", $line);
                    continue;
                }
                else
                {
                    $values = explode(",", $line);
                    $values = str_getcsv($line);
                    if(count($values) == $no_of_fields)
                    {
                        $i = 0;
                        foreach($values as $value)
                        {
                            $field = str_replace('"', '', $fields[$i]);
                            $rec[$field] = str_replace('"', '', $value);
                            $i++;
                        }
                    }
                    else
                    {
                        print_r($values);
                        echo "\n investigate rec is not $no_of_fields";
                    }
                }
                if($rec)
                {
                    if    ($type == "collection")   self::process_taxon_collection($rec, $taxon_id, $url);
                    elseif($type == "occurrence")   self::process_taxon_occurrence($rec, $taxon_id, $url);
                    elseif($type == "taxon")        self::process_taxon($rec);
                }
            }
        }
        unlink($path);
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
        $taxon->kingdom                     = $rec["kingdom"];
        $taxon->phylum                      = $rec["phylum"];
        $taxon->class                       = $rec["class"];
        $taxon->order                       = $rec["order"];
        $taxon->family                      = $rec["family"];
        $taxon->parentNameUsageID           = $rec["parent_no"];
        if($rec["senior_no"] != $rec["orig_no"]) $taxon->acceptedNameUsageID = $rec["senior_no"];
        else                                     $taxon->acceptedNameUsageID = '';
        $taxon->taxonomicStatus             = self::process_status($rec["status"]);
        // if($taxon->taxonomicStatus == "synonym") return; //debug - exclude synonyms during preview phase
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxa[$taxon->taxonID] = $taxon;
            $this->taxon_ids[$taxon->taxonID] = 1;
            if($v = $rec["common_name"])
            {
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

    private function get_reference_ids($ref_nos)
    {
        $ref_nos = explode(",", $ref_nos);
        if(!$ref_nos) return array();
        $ref_nos = array_map('trim', $ref_nos);
        $reference_ids = array();
        foreach($ref_nos as $ref_no)
        {
            $url = $this->service["reference"] . $ref_no;
            if($html = Functions::lookup_with_cache($url, $this->download_options))
            {
                if(preg_match("/Full reference<\/span>(.*?)<span/ims", $html, $arr))
                {
                    $full_ref = strip_tags($arr[1], "<i>");
                    $r = new \eol_schema\Reference();
                    $r->full_reference = $full_ref;
                    $r->identifier = md5($r->full_reference);
                    $r->uri = $url;
                    $reference_ids[] = $r->identifier;
                    if(!isset($this->resource_reference_ids[$r->identifier]))
                    {
                       $this->resource_reference_ids[$r->identifier] = '';
                       $this->archive_builder->write_object_to_file($r);
                    }
                }
            }
        }
        return $reference_ids;
    }

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
        $occurrence = $this->add_occurrence($taxon_id,  $id);
        $m->occurrenceID = $occurrence->occurrenceID;
        $m->measurementOfTaxon = $measurementOfTaxon;
        $m->measurementType = $measurementType;
        $m->measurementValue = $value;
        if($val = @$rec["source_url"]) $m->source = $val;
        if($label == "is_extant")
        {
            // if($reference_ids = self::get_reference_ids(trim($rec["reference_no"]))) $m->referenceID = implode("; ", $reference_ids); deliberately commented for now.
        }
        if($measurementOfTaxon == "true") $m->source = $this->service["source"] . $taxon_id;
        if(in_array($label, array("firstapp_ea", "firstapp_la", "lastapp_ea", "lastapp_la"))) $m->measurementUnit = "http://eol.org/schema/terms/paleo_megaannum";
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $id)
    {
        $occurrence_id = $taxon_id . '_' .  $id;
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

    function create_archive()
    {
        echo "\n Creating archive...\n";
        foreach($this->taxa as $t)
        {
            if(!isset($this->taxon_ids[$t->parentNameUsageID])) $t->parentNameUsageID = '';
            if(!isset($this->taxon_ids[$t->acceptedNameUsageID])) $t->acceptedNameUsageID = '';
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }

}
?>