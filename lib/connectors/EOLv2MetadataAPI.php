<?php
namespace php_active_record;

class EOLv2MetadataAPI
{
    public function __construct($folder)
    {
        $this->folder = $folder;
        $this->mysqli =& $GLOBALS['db_connection'];
        // IF(cp.description_of_data IS NOT NULL, cp.description_of_data, r.description) as desc_of_data
        // $result = $mysqli->query("SELECT r.hierarchy_id, max(he.id) as max FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) GROUP BY r.hierarchy_id");
        // $result = $mysqli->query("SELECT r.hierarchy_id, max(he.id) as max FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) GROUP BY r.hierarchy_id");
        // $harvest_event = HarvestEvent::find($row['max']);
        // if(!$harvest_event->published_at) $GLOBALS['hierarchy_preview_harvest_event'][$row['hierarchy_id']] = $row['max'];
        $this->path['temp_dir'] = "/Volumes/Thunderbolt4/EOL_V2/";
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
    }
    public function start_user_added_text() //udo = 23848 | published = 13143
    {
        // -- udo.*, tcpe.hierarchy_entry_id, dt.schema_value
        // -- , ii.schema_value
        // -- , tii.label, doii.info_item_id
        // -- LEFT JOIN info_items ii ON (dotoc.toc_id = ii.toc_id)
        // -- LEFT JOIN data_objects_info_items doii ON (udo.data_object_id = doii.data_object_id)
        // -- LEFT JOIN translated_info_items tii ON (doii.info_item_id = tii.info_item_id)
        $sql = "SELECT udo.data_object_id, udo.user_id, udo.taxon_concept_id
        , if(l.iso_639_1 is not null, l.iso_639_1, '') as iso_lang
        , concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' ', if(u.username is not null, concat('(',u.username,')'), '')) as user_name
        , lic.title as license
        , d.data_rating, d.description, d.data_type_id, d.rights_statement, d.rights_holder, d.bibliographic_citation, d.object_title as title, d.location, d.source_url
        , d.created_at, d.updated_at
        , ttoc.label as subject , tdt.label as data_type
        , dotoc.toc_id
        FROM users_data_objects udo
        LEFT JOIN data_objects d ON (udo.data_object_id = d.id) 
        LEFT JOIN data_types dt ON (d.data_type_id = dt.id)
        LEFT JOIN users u ON (udo.user_id = u.id)
        LEFT JOIN taxon_concept_preferred_entries tcpe ON (udo.taxon_concept_id = tcpe.taxon_concept_id)
        LEFT JOIN languages l ON (d.language_id=l.id)
        LEFT JOIN licenses lic ON  (d.license_id = lic.id)
        LEFT JOIN data_objects_table_of_contents dotoc ON (udo.data_object_id = dotoc.data_object_id)
        LEFT JOIN translated_table_of_contents ttoc ON (dotoc.toc_id = ttoc.table_of_contents_id)
        LEFT JOIN translated_data_types tdt ON (d.data_type_id = tdt.data_type_id)
        where (ttoc.language_id = 152 OR ttoc.language_id is null) and
              (tdt.language_id = 152 OR tdt.language_id is null) and d.published = 1 and udo.visibility_id = 1";
        // $sql .= " and udo.user_id = 20470 and d.id = 23862470";
        // $sql .= " and d.id = 16900774"; //4926441"; //10194243"; //29733168"; //22464391"; //27221235"; //29321098"; //"; //32590447";//"; //10523111";//4926441";
        // $sql .= " limit 10"; //16900774 data_object_id with associated taxa
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows . "\n"; exit;
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            // if(in_array($row['data_object_id'], array(22464391))) continue;
            
            $info = self::get_taxon_info($row['taxon_concept_id']);
            $objects = $row;
            
            $objects['taxon_concept_id'] = array($row['taxon_concept_id']);
            $associated_tc_ids = self::check_for_added_association_for_this_object($row['data_object_id']);
            if($associated_tc_ids) $objects['taxon_concept_id'] = array_merge($objects['taxon_concept_id'], $associated_tc_ids);
            
            $temp = self::get_object_info($row);
            $objects = array_merge($objects, $temp);
            $objects['refs'] = self::get_refs($row['data_object_id']);
            $recs[] = array(
            // 'iso_lang' => $row['iso_lang']
            // , 'lang_native' => $row['lang_native']
            // , 'lang_english' => $row['lang_english']
            'user_name' => $row['user_name']
            , 'user_id' => $row['user_id']
            , 'taxon_name' => $info['taxon_name']
            , 'taxon_id' => $row['taxon_concept_id']
            , 'rank' => $info['rank']
            , 'he_parent_id' => $info['he_parent_id']
            , 'objects' => $objects
            , 'ancestry' => $info['ancestry'] //temporarily commented
            );
            
            if($associated_tc_ids) {
                $this->debug['obj with associated taxa'][$row['data_object_id']] = '';
                foreach($associated_tc_ids as $tc_id) {
                    $info = self::get_taxon_info($tc_id);
                    $recs[] = array(
                    'taxon_name'  => $info['taxon_name']
                    , 'taxon_id'    => $tc_id
                    , 'rank'        => $info['rank']
                    , 'he_parent_id'=> $info['he_parent_id']
                    , 'ancestry'    => $info['ancestry'] //temporarily commented
                    );
                }
            }
            
        }
        // print_r($recs); //exit("\n".count($recs)."\n");
        // self::write_to_text_comnames($recs);
        self::gen_dwca_resource($recs);
        print_r($this->debug);
    }
    private function check_for_added_association_for_this_object($data_object_id)
    {
        $sql = "select distinct cal.taxon_concept_id from eol_logging_production.curator_activity_logs cal where cal.activity_id = 48 and cal.target_id = $data_object_id";
        /* -- add_association activity id = 48
           -- here target_id is data_object_id */
        $tc_ids = array();
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            $tc_ids[] = $row['taxon_concept_id'];
        }
        return $tc_ids;
    }
    private function get_refs($data_object_id)
    {
        $final = array();
        $sql = "select r.* FROM data_objects_refs dor JOIN refs r ON (dor.ref_id = r.id) where dor.data_object_id = $data_object_id and r.published = 1";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            /*
            `provider_mangaed_id` varchar(255) DEFAULT NULL,
            `volume` varchar(50) DEFAULT NULL,
            `edition` varchar(50) DEFAULT NULL,
            `publisher` varchar(255) DEFAULT NULL,
            `user_submitted` tinyint(1) NOT NULL DEFAULT '0',
            `visibility_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
            `published` tinyint(3) unsigned NOT NULL DEFAULT '0',
            */
            $rec = array();
            $rec["identifier"] = $row['id'];
            $rec["publicationType"] = '';
            $rec["full_reference"] = $row['full_reference'];
            $rec["primaryTitle"] = '';
            $rec["title"] = $row['title'];
            $rec["pages"] = $row['pages'];
            $rec["pageStart"] = $row['page_start'];
            $rec["pageEnd"] = $row['page_end'];
            $rec["volume"] = $row['volume'];
            $rec["edition"] = $row['edition'];
            $rec["publisher"] = $row['publisher'];
            $rec["authorList"] = $row['authors'];
            $rec["editorList"] = $row['editors'];
            $rec["created"] = $row['publication_created_at'];
            $rec["language"] = $row['language_id'];
            $rec["uri"] = "";
            $rec["doi"] = "";
            $rec["localityName"] = "";
            if($rec['full_reference']) $final[] = $rec;
        }
        return $final;
    }
    private function get_object_info($row)
    {
        $final = array();
        $final['subjectURI'] = self::get_subjectURI($row);
        // print_r($row);
        return $final;
    }
    private function get_subjectURI($row)
    {
        if($row['toc_id'] == 322) return "http://eol.org/schema/eol_info_items.xml#FossilHistory";
        
        if($row['toc_id']) {
            $sql = "SELECT ii.schema_value as subjectURI from info_items ii where ii.toc_id = ".$row['toc_id'];
            /* works OK but doesn't detect if > 1 row is returned
            if($val = $this->mysqli->select_value($sql)) return $val;
            else {
                echo("\n\nInvestigate no toc_id\n");
                print_r($row); exit;
            } */
            $result = $this->mysqli->query($sql);
            // echo "\n".count($result)."\n"; 
            if(count($result) > 1) {
                echo("\n\nInvestigate > 1 subjectURI \n");
                print_r($row); print_r($result); exit;
            }
            while($result && $row2=$result->fetch_assoc()) {
                if($val = $row2['subjectURI']) return $val;
            }
            if(!$result) {
                echo("\n\nInvestigate no subjectURI found\n");
                print_r($row); exit;
            }
        }


        // http://www.eol.org/voc/table_of_contents#FossilHistory (322)
        // http://eol.org/schema/eol_info_items.xml#FossilHistory
        if($row['subject'] == "Fossil History") return "http://eol.org/schema/eol_info_items.xml#FossilHistory";
        
        //2nd option if above didn't get anything
        //loop to info_items.schema_value and find #Education
        $sql = "SELECT ii.schema_value from info_items ii";
        $result = $this->mysqli->query($sql);
        // echo "\n".$row['subject']."\n"; //exit;
        while($result && $row2=$result->fetch_assoc()) {
            if(preg_match("/\\#".$row['subject']."(.*?)xxx/ims", $row2['schema_value']."xxx", $arr)) return $row2['schema_value'];
        }

        echo("\n\nInvestigate STILL no subjectURI found\n");
        print_r($row); exit;
        
    }
    //select if(field_a is not null, field_a, field_b) --- if then else in MySQL
    public function start_user_preferred_comnames() //total recs for agents_synonyms: 113283
    {
        $sql = "select asy.synonym_id, n.id as name_id, n.string as common_name, asy.agent_id, u.given_name, u.family_name, s.hierarchy_entry_id, s.vetted_id, s.preferred
        , he.taxon_concept_id
        , tv.label as vettedness
        , if(l.iso_639_1 is not null, l.iso_639_1, '') as iso_lang, l.source_form as lang_native, s3.label as lang_english
        , concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' ', if(u.username is not null, concat('(',u.username,')'), '')) as user_name, u.id as user_id 
        from agents_synonyms asy
        left outer join eol_logging_production.synonyms s on (asy.synonym_id = s.id)
        left outer join eol_logging_production.names n on (s.name_id = n.id)
        left outer join agents a on (asy.agent_id = a.id)
        left outer JOIN users u ON (asy.agent_id = u.agent_id)
        left outer join hierarchy_entries he on (s.hierarchy_entry_id = he.id)
        left outer join translated_vetted tv on (s.vetted_id = tv.vetted_id)
        left JOIN eol_v2.translated_languages s3 ON (s.language_id=s3.original_language_id)
        left JOIN languages l ON (s.language_id=l.id)
        where (tv.language_id = 152 OR tv.language_id is null) and (s3.language_id = 152 OR s3.language_id is null)";
        // $sql .= " and he.taxon_concept_id is null"; //just for testing asy.synonym_id that is no longer existing in synonyms table
        // $sql .= ' and n.string like "atlantic cod%"';
        // $sql .= ' and n.string like "white-throated sparrow%"';
        // $sql .= ' and n.string like "brown bear%"';
        // $sql .= ' and n.string = "Karhu"';
        $sql .= " order by n.string, s3.label";
        // $sql .= " limit 1000";
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows . "\n"; exit;
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            $row = array_map('trim', $row);
            if(!isset($recs[$row['name_id']])) {
                if(!trim($row['common_name'])) continue;
                $info = self::get_taxon_info($row['taxon_concept_id']);
                $recs[$row['name_id']] = array('common_name' => $row['common_name'], 'preferred' => $row['preferred'], 'iso_lang' => $row['iso_lang'], 'lang_native' => $row['lang_native']
                , 'lang_english' => $row['lang_english']
                , 'user_name' => $row['user_name']
                , 'user_id' => $row['user_id']
                , 'taxon_name' => @$info['taxon_name']
                , 'taxon_id' => $row['taxon_concept_id']
                , 'rank' => @$info['rank']
                , 'he_parent_id' => @$info['he_parent_id']
                , 'ancestry' => $info['ancestry'] //working OK but just commented for now
                );
                echo "\n".$recs[$row['name_id']]['common_name'];
            }
        }
        // print_r($recs);
        self::write_to_text_comnames($recs);
        echo "\n". $result->num_rows . "\n"; //exit;
    }

    public function start_user_added_comnames() //total records: 87127
    {
        $sql = "select cal.user_id, cal.taxon_concept_id, cal.activity_id, cal.target_id, cal.changeable_object_type_id
        , s.name_id, s.language_id, n.string as common_name, s.preferred, concat(ifnull(u.given_name,''), ' ', ifnull(u.family_name,''), ' (', ifnull(u.username,''), ')') as user_name, s3.label
        , if(l.iso_639_1 is not null, l.iso_639_1, '') as iso_lang, l.source_form as lang_native, s3.label as lang_english
        from eol_logging_production.curator_activity_logs cal 
        left join eol_logging_production.synonyms s on (cal.target_id=s.id)
        left join eol_logging_production.names n on (s.name_id=n.id)
        left join users u on (cal.user_id=u.id)
        left JOIN eol_v2.translated_languages s3 ON (s.language_id=s3.original_language_id)
        left JOIN languages l ON (s.language_id=l.id)
        where cal.activity_id = 61 and s.name_id is not null and s3.language_id = 152";
        // $sql .= " and cal.user_id = 20470";
        $sql .= " order by n.string";
        // $sql .= " limit 5";
        
        // $m = 10000;
        // $sql .= " limit $m";
        // $sql .= " LIMIT $m OFFSET ".$m;
        // $sql .= " LIMIT $m OFFSET ".$m*2;
        // $sql .= " LIMIT $m OFFSET ".$m*3;
        // $sql .= " LIMIT $m OFFSET ".$m*4;
        // $sql .= " LIMIT $m OFFSET ".$m*5;
        // $sql .= " LIMIT $m OFFSET ".$m*6;
        // $sql .= " LIMIT $m OFFSET ".$m*7;
        // $sql .= " LIMIT $m OFFSET ".$m*8;
        
        // investigate 46326157 46326105
        // and cal.taxon_concept_id = 46326157
        // and cal.user_id = 20470 
        // and cal.taxon_concept_id = 209718 #922651 #209718
        // 61 add_common_name
        // 47 vetted_common_name
        // 73 trust_common_name
        // 26 added_common_name --- NO RECORD 4454117 (no supercedure) 382622 (with supercedure_id)
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows . "\n"; exit;
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            if(!isset($recs[$row['name_id']])) {
                $info = self::get_taxon_info($row['taxon_concept_id']);
                $recs[$row['name_id']] = array('common_name' => $row['common_name'], 'preferred' => $row['preferred'], 'iso_lang' => $row['iso_lang'], 'lang_native' => $row['lang_native']
                , 'lang_english' => $row['lang_english']
                , 'user_name' => $row['user_name']
                , 'user_id' => $row['user_id']
                , 'taxon_name' => $info['taxon_name']
                , 'taxon_id' => $row['taxon_concept_id']
                , 'rank' => $info['rank']
                , 'he_parent_id' => $info['he_parent_id']
                , 'ancestry' => $info['ancestry']
                );
            }
        }
        // print_r($recs); //exit("\n".count($recs)."\n");
        self::write_to_text_comnames($recs);
        self::gen_dwca_resource($recs);
    }
    private function gen_dwca_resource($recs)
    {
        /* 
        [common_name] => Bobbit worm
        [iso_lang] => en
        [lang_native] => English
        [lang_english] => English
        [user_name] => Jennifer Hammock (jhammock)
        [user_id] => 20470
        [taxon_name] => Eunice aphroditois
        [taxon_id] => 404312
        [rank] => species
        [he_parent_id] => 52691614
        */
        foreach($recs as $rec) {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $rec['taxon_id'];
            $taxon->scientificName  = $rec['taxon_name'];
            $taxon->taxonRank         = $rec['rank'];
            foreach($rec['ancestry'] as $a) {
                /* 
                [he_id] => 52691614
                [taxon_name] => Eunice
                [taxon_concept_id] => 50908
                [he_parent_id] => 52691523
                [rank] => genus
                */
                if(in_array($a['rank'], array('kingdom','phylum','class','order','family','genus'))) {
                    $taxon->$a['rank'] = ucfirst($a['taxon_name']);
                }
            }
            // $taxon->kingdom         = $t['dwc_Kingdom'];
            // $taxon->phylum          = $t['dwc_Phylum'];
            // $taxon->class           = $t['dwc_Class'];
            // $taxon->order           = $t['dwc_Order'];
            // $taxon->family          = $t['dwc_Family'];
            // $taxon->genus           = $t['dwc_Genus'];
            // if($agent_ids = self::create_agent_extension($rec)) $taxon->agentID = implode("; ", $agent_ids);

            // $taxon->recordedBy = "eli"; - not working
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
            
            if($common_name = @$rec['common_name']) {
                $v = new \eol_schema\VernacularName();
                $v->taxonID         = $taxon->taxonID;
                $v->vernacularName  = $common_name;
                $v->language        = $rec['iso_lang'];
                $v->taxonRemarks    = "Contributed by: ".$rec['user_name']." (".$rec['user_id'].").";
                $v->source          = "http://www.eol.org/users/".$rec['user_id'];
                $v->source          = "http://eol.org/pages/".$taxon->taxonID."/names/common_names";
                // if($agent_ids = self::create_agent_extension($rec)) $v->agentID = implode("; ", $agent_ids); - not working
                $this->archive_builder->write_object_to_file($v);
            }
            
            if($obj = @$rec['objects'])
            {   /* [objects] => Array(
                                    [data_object_id] => 1893733
                                    [user_id] => 11
                                    [taxon_concept_id] => 918848 -- now is an array type
                                    [iso_lang] => en
                                    [user_name] => Paddy Patterson (paddy)
                                    [license] => public domain
                                    [data_rating] => 2.5
                                    [description] => This species has been reported on several continents, and may be presumed to have a world-wide distribution.
                                    [data_type_id] => 3
                                    [subject] => Distribution
                                    [data_type] => Text
                                    [toc_id] => 309
                                    [subjectURI] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
                                )
                */
                if($obj['data_type'] != "Text") {
                    echo "\n\nObject not Text\n";
                    print_r($rec); exit;
                }

                $desc = self::format_str($obj['description'], $obj['data_object_id']);
                
                if(!$desc) continue;
                $mr = new \eol_schema\MediaResource();
                $mr->taxonID        = implode("; ", $obj['taxon_concept_id']);
                $mr->identifier     = $obj['data_object_id'];
                $mr->type           = "http://purl.org/dc/dcmitype/Text";
                $mr->language       = $obj['iso_lang'];
                $mr->format         = "text/html";
                $mr->furtherInformationURL = $obj['source_url']; //"http://www.eol.org/data_objects/".$obj['data_object_id'];
                // $mr->accessURI      = '';
                // $mr->thumbnailURL   = '';
                
                $mr->CVterm         = $obj['subjectURI'];
                $mr->Owner          = $obj['rights_holder'];
                $mr->rights         = $obj['rights_statement'];
                $mr->title          = $obj['title'];
                $mr->UsageTerms     = self::get_license_url($obj['license']);
                // $mr->audience       = 'Everyone';
                
                /* working - good for debug ------------------------------------------------------
                $filename = CONTENT_RESOURCE_LOCAL_PATH ."eli.html";
                $FILE = Functions::file_open($filename, 'w');
                fwrite($FILE, $desc);
                fclose($FILE);
                $desc = file_get_contents($filename);
                */
                
                $mr->description    = $desc;
                $mr->LocationCreated = $obj['location'];
                $mr->bibliographicCitation = $obj['bibliographic_citation'];
                $mr->Rating                = $obj['data_rating'];
                $mr->CreateDate = $obj['created_at'];
                $mr->modified = $obj['updated_at'];

                if($reference_ids = self::create_ref_extension($obj['refs']))  $mr->referenceID = implode("; ", $reference_ids);
                if($agent_ids = self::create_agent_extension($obj)) $mr->agentID = implode("; ", $agent_ids);
            
                if(!isset($this->object_ids[$mr->identifier])) {
                    $this->archive_builder->write_object_to_file($mr);
                    $this->object_ids[$mr->identifier] = '';
                }
            }
        }
        
        $this->archive_builder->finalize(true);
        return;
    }
    private function format_str($str, $data_object_id)
    {
        // if(stripos($str, "style=") !== false) $this->debug['data_object_id'][$data_object_id] = ''; //just debug
        $str = str_replace(array("\n", "\t", "\r", chr(9), chr(10), chr(13)), " ", $str);
        $str = Functions::remove_whitespace($str);
        if(preg_match_all("/style=\"(.*?)\"/ims", $str, $arr)) {
            foreach($arr[1] as $remove) {
                $str = str_ireplace('style="'.$remove.'"', "", $str);
            }
        }
        
        // <!--[if gte mso 9]><xml> <o:OfficeDocumentSettings> <o:AllowPNG/> </o:OfficeDocumentSettings> </xml><![endif]--> 
        if(preg_match_all("/<!--(.*?)-->/ims", $str, $arr)) {
            foreach($arr[1] as $remove) {
                $str = str_ireplace('<!--'.$remove.'-->', "", $str);
            }
        }
        
        // e.g. http://www.eol.org/data_objects/22464391 | http://www.eol.org/data_objects/27431054
        if(stripos($str, 'src="data:image') !== false) { //string is found
            if(preg_match_all("/src=\"data:image(.*?)\"/ims", $str, $arr)) {
                foreach($arr[1] as $remove) {
                    $str = str_ireplace('src="data:image'.$remove.'"', "", $str);
                }
            }
        }
        return trim($str);
    }
    private function remove_utf8_bom($text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        /* another option:
        text = str_replace("\xEF\xBB\xBF",'',$text); 
        */
        return $text;
    }
    
    private function create_ref_extension($refs)
    {
        $reference_ids = array();
        foreach($refs as $rec)
        {
            $r = new \eol_schema\Reference();
            $fields = array_keys($rec);
            foreach($fields as $field) {
                $r->$field = $rec[$field];
            }
            $reference_ids[] = $r->identifier;
            if(!isset($this->reference_ids[$r->identifier])) {
                $this->reference_ids[$r->identifier] = '';
                $this->archive_builder->write_object_to_file($r);
            }
            /* just for reference...
            $rec["identifier"] = $row['id'];
            $rec["publicationType"] = 
            $rec["full_reference"] = $row['full_reference'];
            $rec["primaryTitle"] = 
            $rec["title"] = $row['title'];
            $rec["pages"] = $row['pages'];
            $rec["pageStart"] = $row['page_start'];
            $rec["pageEnd"] = $row['page_end'];
            $rec["volume"] = $row['volume'];
            $rec["edition"] = $row['edition'];
            $rec["publisher"] = $row['publisher'];
            $rec["authorList"] = $row['authors'];
            $rec["editorList"] = $row['editors'];
            $rec["created"] = $row['publication_created_at'];
            $rec["language"] = $row['language_id']
            $rec["uri"] = "";
            $rec["doi"] = "";
            $rec["localityName"] = "";
            */
        }
        return $reference_ids;
    }
    private function get_license_url($license) //e.g. public domain
    {
        if($license == "public domain") return "http://creativecommons.org/licenses/publicdomain/";
        $sql = "SELECT l.source_url from licenses l where l.title = '".$license."' and l.source_url is not null";
        if($val = $this->mysqli->select_value($sql)) return $val;
        elseif($license == "all rights reserved") return $license;
        else exit("\n\nInvestigate no license [$license]\n");
    }
    private function create_agent_extension($rec)
    {
        // [user_name] => Jennifer Hammock (jhammock)
        // [user_id] => 20470
        $r = new \eol_schema\Agent();
        $r->term_name       = $rec['user_name'];
        $r->agentRole       = 'author';
        $r->identifier      = $rec['user_id'];
        $r->term_homepage   = "http://www.eol.org/users/".$rec['user_id'];
        $agent_ids[] = $r->identifier;
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }
    
    private function get_ancestry($he_id)
    {
        $ancestry = array();
        while(true) {
            echo "\n querying he_id [$he_id]";
            $sql = "SELECT n.string as final_name, he.rank_id, h.label, he.id as he_id, he.parent_id as he_parent_id, r.label as rank, he.ancestry, he.lft, he.rgt, he.name_id, he.taxon_concept_id
            FROM hierarchy_entries he
            left outer JOIN eol_logging_production.names n ON (he.name_id=n.id)
            left outer JOIN hierarchies h ON (he.hierarchy_id=h.id)
            LEFT outer JOIN translated_ranks r ON (he.rank_id=r.rank_id)
            WHERE r.language_id = 152 and he.id = $he_id and he.vetted_id = 5";
            $result = $this->mysqli->query($sql);
            $new_he_id = false;
            while($result && $row=$result->fetch_assoc()) {
                $info = array('he_id' => $row['he_id'], 'taxon_name' => ucfirst($row['final_name']), 'taxon_concept_id' => $row['taxon_concept_id'], 'he_parent_id' => $row['he_parent_id'], 'rank' => $row['rank']);
                $ancestry[] = $info;
                // print_r($info);
                $new_he_id = $row['he_parent_id'];
                echo "\n new he_id [$new_he_id]";
            }
            if($he_id != $new_he_id && $new_he_id) $he_id = $new_he_id;
            else break;
        }
        // print_r($ancestry); exit;
        return $ancestry;
    }
    private function get_taxon_info($taxon_id)
    {
        if(!$taxon_id) return array();
        if    ($rec = self::get_taxon_info_from_json($taxon_id)) return $rec;
        elseif($rec = self::query_taxon_info($taxon_id)) return $rec;
        /* debugging only
        $rec = self::query_taxon_info($taxon_id);
        return $rec;
        */
    }
    private function query_taxon_info($taxon_concept_id)
    {
        echo "\nquerying dbase...[$taxon_concept_id]";
        $sql = "SELECT tc.id, n.string, cf.string as final_name, he.rank_id, h.label, he.id as he_id, he.parent_id as he_parent_id, r.label as rank
                FROM taxon_concepts tc
                JOIN taxon_concept_preferred_entries pe ON (tc.id=pe.taxon_concept_id)
                JOIN hierarchy_entries he ON (pe.hierarchy_entry_id=he.id)
                JOIN eol_logging_production.names n ON (he.name_id=n.id)
                JOIN hierarchies h ON (he.hierarchy_id=h.id)
                LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)
                LEFT JOIN translated_ranks r ON (he.rank_id=r.rank_id)
                WHERE tc.supercedure_id = 0
                AND tc.published = 1
                AND tc.id = $taxon_concept_id
                AND r.language_id = 152";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            $info = array('taxon_name' => $row['final_name'], 'taxon_concept_id' => $row['id'], 'he_parent_id' => $row['he_parent_id'], 'rank' => $row['rank']);
            if($info) $info['ancestry'] = self::get_ancestry($info['he_parent_id']);
            self::save_taxon_info_to_json($taxon_concept_id, $info);
            return self::get_taxon_info_from_json($taxon_concept_id);
        }
        
        //2nd option is supercedure_id
        if($supercedure_id = self::get_supercedure_id($taxon_concept_id)) {
            if($supercedure_id != $taxon_concept_id) return self::get_taxon_info($supercedure_id);
        }
        echo "\n[$taxon_concept_id get supercedure UN-SUCCESSFUL]\n";
        
        //3rd option
        $sql = "SELECT n.string as final_name, he.taxon_concept_id,
        he.rank_id, h.label, he.id as he_id, he.parent_id as he_parent_id, r.label as rank, he.ancestry, he.lft, he.rgt, he.name_id, he.guid
        FROM hierarchy_entries he
        left outer JOIN eol_logging_production.names n ON (he.name_id=n.id)
        left outer JOIN hierarchies h ON (he.hierarchy_id=h.id)
        LEFT outer JOIN translated_ranks r ON (he.rank_id=r.rank_id)
        WHERE r.language_id = 152 and he.taxon_concept_id = $taxon_concept_id and he.vetted_id = 5";
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            $info = array('taxon_name' => $row['final_name'], 'taxon_concept_id' => $taxon_concept_id, 'he_parent_id' => $row['he_parent_id'], 'rank' => $row['rank']);
            if($info) $info['ancestry'] = self::get_ancestry($info['he_parent_id']);
            self::save_taxon_info_to_json($taxon_concept_id, $info);
            return self::get_taxon_info_from_json($taxon_concept_id);
        }
        echo "\n3rd option UN-SUCCESSFULL \n";
        
        //4th option
        $sql = "SELECT n.string as final_name, he.taxon_concept_id,
                he.rank_id, h.label, he.id as he_id, he.parent_id as he_parent_id, r.label as rank, he.ancestry, he.lft, he.rgt, he.name_id, he.guid
                FROM hierarchy_entries he
                left outer JOIN eol_logging_production.names n ON (he.name_id=n.id)
                left outer JOIN hierarchies h ON (he.hierarchy_id=h.id)
                LEFT outer JOIN translated_ranks r ON (he.rank_id=r.rank_id)
                WHERE (r.language_id = 152 or r.language_id is null) and he.taxon_concept_id = $taxon_concept_id"; // and he.vetted_id = 5
        $result = $this->mysqli->query($sql);
        while($result && $row=$result->fetch_assoc()) {
            $info = array('taxon_name' => $row['final_name'], 'taxon_concept_id' => $taxon_concept_id, 'he_parent_id' => $row['he_parent_id'], 'rank' => $row['rank']);
            if($info) $info['ancestry'] = self::get_ancestry($info['he_parent_id']);
            self::save_taxon_info_to_json($taxon_concept_id, $info);
            return self::get_taxon_info_from_json($taxon_concept_id);
        }
        echo "\n4th option UN-SUCCESSFULL \n";
        exit("\nInvestigate [$taxon_concept_id]\n");
    }
    private function get_supercedure_id($taxon_concept_id)
    {
        $orig = $taxon_concept_id;
        while(true) {
            $sql = "select * from taxon_concepts t where t.id = $taxon_concept_id";
            $result = $this->mysqli->query($sql);
            if($result && $row=$result->fetch_assoc()) {
                // print_r($row);
                $supercedure_id = $row['supercedure_id'];
                if($supercedure_id && $supercedure_id != 0) {
                    $taxon_concept_id = $supercedure_id;
                    echo "\n new tc_id [$taxon_concept_id]";
                }
                else break;
            }
            else break;
        }
        echo("\nfrom: [$orig] to final: tc_id [$taxon_concept_id]\n");
        return $taxon_concept_id;
    }
    private function save_taxon_info_to_json($taxon_id, $info)
    {
        echo "\nsaving to json...";
        $json = json_encode($info);
        $main_path = $this->path['temp_dir'];
        $md5 = md5($taxon_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$taxon_id.json";
        /*
        if(file_exists($filename)) {
            $file_age_in_seconds = time() - filemtime($filename);
            if($file_age_in_seconds < $this->download_options['expire_seconds'])    return; //no need to save
            if($this->download_options['expire_seconds'] === false)                 return; //no need to save
        } */
        //saving...
        $FILE = Functions::file_open($filename, 'w');
        fwrite($FILE, $json);
        fclose($FILE);
    }
    private function get_taxon_info_from_json($taxon_id)
    {
        // echo "\nretrieving json...";
        $main_path = $this->path['temp_dir'];
        $md5 = md5($taxon_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $filename = $main_path . "$cache1/$cache2/$taxon_id.json";
        if(file_exists($filename)) {
            $json = file_get_contents($filename);
            // print_r(json_decode($json, true));
            return json_decode($json, true);
        }
        else return array();
    }
    private function write_to_text_comnames($recs)
    {
        $comname_head   = array("Namestring", "Preferred",  "ISO lang.", "Language"     , "User name", "User EOL ID", "Taxon name", "Taxon ID", "Rank", "Kingdom", "Phylum", "Class", "Order", "Family", "Genus");
        $comname_fields = array('common_name', "preferred", 'iso_lang' , 'lang_english' , 'user_name', 'user_id'    , 'taxon_name', 'taxon_id', 'rank'); //was removed but working: he_parent_id
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . $this->folder.".txt";
        $FILE = Functions::file_open($txtfile, "w");
        fwrite($FILE, implode("\t", $comname_head)."\n");
        $i = 0;
        foreach($recs as $resource_id => $rec) {
            $cols = array(); $i++;
            foreach($comname_fields as $fld) $cols[] = self::clean_str($rec[$fld], false);
            // if((($i % 30) == 0)) fwrite($FILE, implode("\t", $comname_head)."\n"); --- not needed coz we'll use this text file to generate the final DwCA resource
            //start ancestry inclusion
            $ancestry = array('kingdom' => "", 'phylum' => "", 'class' => "", 'order' => "", 'family' => "", 'genus' => "");
            if(@$rec['ancestry']) {
                foreach(@$rec['ancestry'] as $a) {
                    /* 
                    [he_id] => 52691614
                    [taxon_name] => Eunice
                    [taxon_concept_id] => 50908
                    [he_parent_id] => 52691523
                    [rank] => genus
                    */
                    if(in_array($a['rank'], array('kingdom','phylum','class','order','family','genus'))) {
                        $ancestry[$a['rank']] = $a['taxon_name'];
                    }
                }
            }
            //end ancestry
            $cols = array_merge($cols, $ancestry);
            fwrite($FILE, implode("\t", $cols)."\n");
        }
        fclose($FILE);
    }

    //==========================================================================================
    public function start_resource_metadata()
    {
        $sql = "SELECT r.id as resource_id, r.title as resource_name, r.collection_id, r.description, r.accesspoint_url as orig_data_source_url
        , r.bibliographic_citation, IF(r.vetted = 1, 'Yes','No') as vettedYN, IF(r.auto_publish = 1, 'Yes','No') as auto_publishYN, r.notes
        , concat(cp.full_name, ' (',cp.id,')') as content_partner
        , '-to be filled up-' as harvest_url_direct
        , '-to be filled up-' as harvest_url_4connector
        , '-to be filled up-' as connector_info
        , l.title  as dataset_license , r.dataset_rights_holder,                  r.dataset_rights_statement
        , l2.title as default_license , r.rights_holder as default_rights_holder, r.rights_statement as default_rights_statement
        , s2.label as resource_status, s3.label as default_language
        FROM resources r
        LEFT OUTER JOIN content_partners cp ON  (r.content_partner_id = cp.id)
        LEFT OUTER JOIN licenses l ON  (r.dataset_license_id = l.id)
        LEFT OUTER JOIN licenses l2 ON (r.license_id         = l2.id)
        LEFT OUTER JOIN translated_resource_statuses s2 ON (r.resource_status_id=s2.resource_status_id)
        LEFT OUTER JOIN translated_languages         s3 ON (r.language_id=s3.original_language_id)
        WHERE s2.language_id = 152 AND s3.language_id = 152";
        // $sql .= " AND r.id = 42";
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows; exit;
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            if(!isset($recs[$row['resource_id']])) {
                $first_pub = $this->mysqli->select_value("SELECT min(he.published_at) as last_published FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.id = ".$row['resource_id']);
                $last_pub = $this->mysqli->select_value("SELECT max(he.published_at) as last_published FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.id = ".$row['resource_id']);
                $recs[$row['resource_id']] = array('resource_id' => $row['resource_id'], 'resource_name' => $row['resource_name']
                , 'first_pub' => $first_pub, 'last_pub' => $last_pub, 'collection_id' => $row['collection_id']
                , 'description' => $row['description']
                , 'orig_data_source_url' => $row['orig_data_source_url']
                , 'harvest_url_direct' => $row['harvest_url_direct']
                , 'harvest_url_4connector' => $row['harvest_url_4connector']
                , 'connector_info' => $row['connector_info']
                , 'dataset_license' => $row['dataset_license'], 'dataset_rights_holder' => $row['dataset_rights_holder'], 'dataset_rights_statement' => $row['dataset_rights_statement']
                , 'default_license' => $row['default_license'], 'default_rights_holder' => $row['default_rights_holder'], 'default_rights_statement' => $row['default_rights_statement']
                , 'bibliographic_citation' => $row['bibliographic_citation'], 'resource_status' => $row['resource_status']
                , 'default_language' => $row['default_language'], 'vettedYN' => $row['vettedYN'], 'auto_publishYN' => $row['auto_publishYN'], 'notes' => $row['notes']
                , 'content_partner' => $row['content_partner']);
            }
        }
        // print_r($recs);
        self::write_to_text_resource($recs);
        self::write_to_html_resource($recs);
    }
    private function write_to_text_resource($recs)
    {
        $resource_head = array("Resource ID", "Resource name", "First Published", "Last Published", "Collection ID", "Description", "Original Data Source URL", "Harvest URL (direct)", 
        "Harvest URL (for connector)", "connector info", "Dataset license", "Dataset Rights Holder", "Dataset Rights Statement", "Default license", "Default Rights Holder", 
        "Default Rights Statement", "Bibliographic Citation", "Default Language", "Vetted", "Auto Publish", "Notes", "Status", "Content Partner");
        $resource_fields = array("resource_id", "resource_name", "first_pub", "last_pub", "collection_id", "description", "orig_data_source_url", "harvest_url_direct",
        "harvest_url_4connector", "connector_info", 
        "dataset_license", "dataset_rights_holder", "dataset_rights_statement", 
        "default_license", "default_rights_holder", "default_rights_statement", "bibliographic_citation", "default_language", "vettedYN", "auto_publishYN", "notes", "resource_status", "content_partner");
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "resource_metadata.txt";
        $FILE = Functions::file_open($txtfile, "w");
        fwrite($FILE, implode("\t", $resource_head)."\n");
        $i = 0;
        foreach($recs as $resource_id => $rec) {
            $cols = array(); $i++;
            foreach($resource_fields as $fld) $cols[] = self::clean_str($rec[$fld], false);
            if((($i % 30) == 0)) fwrite($FILE, implode("\t", $resource_head)."\n");
            fwrite($FILE, implode("\t", $cols)."\n");
        }
        fclose($FILE);
    }
    private function write_to_html_resource($recs)
    {
        $resource_head = array("Resource ID", "Resource name", "First Published", "Last Published", "Collection ID", "Description", "Original Data Source URL", "Harvest URL (direct)", 
        "Harvest URL (for connector)", "connector info", "Dataset license", "Dataset Rights Holder", "Dataset Rights Statement", "Default license", "Default Rights Holder", 
        "Default Rights Statement", "Bibliographic Citation", "Default Language", "Vetted", "Auto Publish", "Notes", "Status", "Content Partner");
        $resource_fields = array("resource_id", "resource_name", "first_pub", "last_pub", "collection_id", "description", "orig_data_source_url", "harvest_url_direct",
        "harvest_url_4connector", "connector_info", 
        "dataset_license", "dataset_rights_holder", "dataset_rights_statement", 
        "default_license", "default_rights_holder", "default_rights_statement", "bibliographic_citation", "default_language", "vettedYN", "auto_publishYN", 
        "notes", "resource_status", "content_partner");

        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "resource_metadata.html";
        $FILE = Functions::file_open($txtfile, "w");
        fwrite($FILE, "<html><body><table border='1'>"."\n");
        
        $i = 0;
        foreach($recs as $resource_id => $rec) {
            $i++;
            if(($i % 2) == 0) $bgcolor = 'lightblue';
            else              $bgcolor = 'lightyellow';
            
            if((($i % 10) == 0) || $i == 1) {
                fwrite($FILE, "<tr bgcolor='$bgcolor'>"."\n");
                foreach($resource_head as $header) fwrite($FILE, "<td align='center' style='font-weight:bold;'>$header</td>"."\n");
                fwrite($FILE, "</tr>"."\n");
            }

            fwrite($FILE, "<tr bgcolor='$bgcolor'>"."\n");
            foreach($resource_fields as $fld) fwrite($FILE, "<td>".self::clean_str($rec[$fld], true, $fld)."</td>"."\n");
            fwrite($FILE, "</tr>"."\n");
        }
        fwrite($FILE, "</table></body></html>"."\n");
        fclose($FILE);
    }
    
    public function start_partner_metadata()
    {   /* orig
        $sql = "SELECT cp.id as partner_id, cp.full_name as partner_name, s.label as status, r.id as resource_id, r.title as resource_title, s2.label as resource_status,
        cp.description as overview, cp.homepage as url, 
        cpa.mou_url as agreement_url, cpa.signed_on_date as signed_date, cpa.signed_by, cpa.created_at as create_date,
        cp.description_of_data as desc_of_data, cp.user_id as manager_eol_id
        FROM content_partners cp
        JOIN translated_content_partner_statuses s ON (cp.content_partner_status_id=s.id)
        JOIN resources r ON (cp.id=r.content_partner_id)
        JOIN translated_resource_statuses s2 ON (r.resource_status_id=s2.id)
        JOIN content_partner_agreements cpa ON (cp.id=cpa.content_partner_id)
        WHERE s.language_id = 152 AND s2.language_id = 152 
        ORDER BY cp.id limit 6000"; */
        //better query than above
        $sql = "SELECT cp.id as partner_id, cp.full_name as partner_name, s.label as status, cpa.is_current,
        cp.description as overview, cp.homepage as url, 
        cpa.mou_url as agreement_url, cpa.signed_on_date as signed_date, cpa.signed_by, cpa.created_at as create_date,
        cp.description_of_data as desc_of_data, cp.user_id as manager_eol_id
        FROM content_partners cp
        LEFT OUTER JOIN translated_content_partner_statuses s ON (cp.content_partner_status_id=s.id)
        LEFT OUTER JOIN content_partner_agreements cpa ON (cp.id=cpa.content_partner_id)
        WHERE s.language_id = 152 
        ORDER BY cp.id, cpa.is_current desc limit 6000";
        $result = $this->mysqli->query($sql);
        // echo "\n". $result->num_rows; exit;
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            if(!isset($recs[$row['partner_id']])) {
                $recs[$row['partner_id']] = array('partner_name' => $row['partner_name'], 'partner_id' => $row['partner_id'], 'status' => $row['status'],
                'overview' => $row['overview'], 'url' => $row['url'], 'agreement_url_from_db' => $row['agreement_url'], 'agreement_url' => self::fix_agreement_url($row['agreement_url']), 
                'signed_by' => $row['signed_by'], 'signed_date' => $row['signed_date'], 'create_date' => $row['create_date'], 'desc_of_data' => $row['desc_of_data'],
                'manager_eol_id' => $row['manager_eol_id'] );
                $recs[$row['partner_id']]['mou_url_editors'] = self::move_url_to_editors($recs[$row['partner_id']]['agreement_url']);

                $sql = "SELECT cpc.id as eol_contact_id, cpc.given_name, cpc.family_name, cpc.email, cpc.homepage, cpc.telephone, cpc.address, s.label as contact_role
                FROM content_partner_contacts cpc JOIN translated_contact_roles s ON (cpc.contact_role_id=s.id) 
                WHERE cpc.content_partner_id = ".$row['partner_id']." AND s.language_id = 152 ORDER BY cpc.id";
                $contacts = $this->mysqli->query($sql);
                while($contacts && $row2=$contacts->fetch_assoc()) {
                    $recs[$row['partner_id']]['contacts'][] = array('eol_contact_id' => $row2['eol_contact_id'], 'given_name' => $row2['given_name'], 'family_name' => $row2['family_name'], 'email' => $row2['email'],
                    'homepage' => $row2['homepage'], 'telephone' => $row2['telephone'], 'address' => $row2['address'], 'contact_role' => $row2['contact_role'],);
                }
                
                $sql = "SELECT r.id as resource_id, r.title as resource_title, s2.label as resource_status
                FROM resources r
                JOIN translated_resource_statuses s2 ON (r.resource_status_id=s2.resource_status_id)
                WHERE s2.language_id = 152 and r.content_partner_id = ".$row['partner_id']." ORDER BY r.id";
                $resources = $this->mysqli->query($sql);
                while($resources && $row3=$resources->fetch_assoc()) {
                    $first_pub = $this->mysqli->select_value("SELECT min(he.published_at) as last_published FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.id = ".$row3['resource_id']);
                    $last_pub = $this->mysqli->select_value("SELECT max(he.published_at) as last_published FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.id = ".$row3['resource_id']);
                    $recs[$row['partner_id']]['resources'][] = array('resource_id' => $row3['resource_id'], 'resource_title' => $row3['resource_title'], 'first_pub' => $first_pub, 'last_pub' => $last_pub, 'status' => $row3['resource_status']);
                }
            }
        }
        // print_r($recs);
        self::write_to_text($recs);
        self::write_to_html($recs);
    }
    private function write_to_html($recs)
    {
        $partner_head = array("Partner ID", "Partner name", "Overview", "URL", "Agreement URL", "Signed By", "Signed Date", "Create Date", "Description of Data", "Manager EOL ID", "Status");
        $resource_head = array("Resource ID", "Title", "First Published", "Last Updated", "Status");
        $contact_head = array("Contact ID", "Given Name", "Family Name", "Email", "Homepage", "Telephone", "Address", "Role");
        
        // [agreement_url_from_db] [agreement_url] -> not used for partner
        $partner_fields = array("partner_id", "partner_name", "overview", "url", "mou_url_editors", "signed_by", "signed_date", "create_date", "desc_of_data", "manager_eol_id", "status");
        $resource_fields = array("resource_id", "resource_title", "first_pub", "last_pub", "status");
        $contact_fields = array("eol_contact_id", "given_name", "family_name", "email", "homepage", "telephone", "address", "contact_role");
        
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "partner_metadata.html";
        $FILE = Functions::file_open($txtfile, "w");
        fwrite($FILE, "<html><body><table border='1'>"."\n");
        
        $i = 0;
        foreach($recs as $partner_id => $rec) {
            $i++;
            if(($i % 2) == 0) $bgcolor = 'lightblue';
            else              $bgcolor = 'lightyellow';
            
            fwrite($FILE, "<tr bgcolor='$bgcolor'>"."\n");
            foreach($partner_head as $header) fwrite($FILE, "<td align='center' style='font-weight:bold;'>$header</td>"."\n");
            fwrite($FILE, "</tr>"."\n");

            fwrite($FILE, "<tr bgcolor='$bgcolor'>"."\n");
            foreach($partner_fields as $fld) fwrite($FILE, "<td>".self::clean_str($rec[$fld])."</td>"."\n");
            fwrite($FILE, "</tr>"."\n");

            fwrite($FILE, "<tr bgcolor='$bgcolor'>"."\n");

            //contacts
            fwrite($FILE, "<td colspan='5' align='center'>"."\n");
            if(@$rec['contacts']) {
                    fwrite($FILE, "<table border='1'>"."\n");
                    fwrite($FILE, "<tr>"."\n");
                    foreach($contact_head as $header) fwrite($FILE, "<td align='center' style='font-weight:bold;'>$header</td>"."\n");
                    fwrite($FILE, "</tr>"."\n");
                    foreach(@$rec['contacts'] as $rec3) {
                        fwrite($FILE, "<tr>"."\n");
                        foreach($contact_fields as $fld) fwrite($FILE, "<td>".self::clean_str($rec3[$fld])."</td>"."\n");
                        fwrite($FILE, "</tr>"."\n");
                    }
                    fwrite($FILE, "</table>"."\n");
            }
            fwrite($FILE, "</td>"."\n");

            fwrite($FILE, "<td colspan='6' align='center'>"."\n");
            if(@$rec['resources']) {
                fwrite($FILE, "<table border='1'>"."\n");
                // resources
                fwrite($FILE, "<tr>"."\n");
                foreach($resource_head as $header) fwrite($FILE, "<td align='center' style='font-weight:bold;'>$header</td>"."\n");
                fwrite($FILE, "</tr>"."\n");
                foreach(@$rec['resources'] as $rec2) {
                    fwrite($FILE, "<tr>"."\n");
                    foreach($resource_fields as $fld) fwrite($FILE, "<td>".self::clean_str($rec2[$fld])."</td>"."\n");
                    fwrite($FILE, "</tr>"."\n");
                }
                fwrite($FILE, "</table>"."\n");
            }
            fwrite($FILE, "</td>"."\n");

            fwrite($FILE, "</tr>"."\n");
        }
        fwrite($FILE, "</table></body></html>"."\n");
        fclose($FILE);
    }
    private function clean_str($str, $htmlYN = true, $fld = "")
    {
        $str = str_replace(array("\t", "\n", chr(9), chr(13), chr(10)), " ", $str);
        $str = trim($str);
        if($htmlYN) {
            $display = $str;
            if($fld == "notes") {
                if(strlen($str) > 200) $str = substr($str, 0, 200)."...";
            }
            if($fld == "orig_data_source_url") {
                if(strlen($display) > 75) $display = substr($display, 0, 75)."...";
            }
            if(substr($str,0,4) == 'http') $str = "<a href='$str'>".$display."</a>";
        }
        return $str;
        // chr(9) tab key
        // chr(13) = Carriage Return - (moves cursor to lefttmost side)
        // chr(10) = New Line (drops cursor down one line) 
    }
    private function fix_agreement_url($url_from_db)
    {
        //                   /files/pdfs/mou/EOL_FishBase-mou.pdf
        // http://www.eol.org/files/pdfs/mou/EOL_FishBase-mou.pdf
        if(substr($url_from_db, 0, 16) == "/files/pdfs/mou/") $url_from_db = "http://www.eol.org".$url_from_db;
        $url_from_db = str_replace("content8.eol.org", "content.eol.org", $url_from_db);
        $url_from_db = str_replace("content4.eol.org", "content.eol.org", $url_from_db);
        $url_from_db = str_replace("content1.eol.org", "content.eol.org", $url_from_db);
        // self::save_mou_to_local($url_from_db); //will comment this line once MOUs are saved
        return $url_from_db; //returns a transformed $url_from_db
    }
    private function save_mou_to_local($url)
    {
        if(!$url) return;
        if(substr($url,0,5) != "http:") return;
        $options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //cache expires in 3 months
        $options['file_extension'] = "pdf";
        if($file = Functions::save_remote_file_to_local($url, $options)) {
            echo "\n [$url]: $file\n";
            $final = pathinfo($url, PATHINFO_FILENAME);
            $local = pathinfo($file, PATHINFO_FILENAME);
            $destination = str_replace($local, $final, $file);
            rename($file, $destination);
        }
    }
    public function save_all_MOUs()
    {
        $sql = "SELECT c.mou_url as url FROM content_partner_agreements c WHERE c.mou_url is not null GROUP BY c.mou_url ORDER BY c.mou_url";
        $result = $this->mysqli->query($sql);
        $recs = array();
        while($result && $row=$result->fetch_assoc()) {
            if($val = @$row['url']) self::fix_agreement_url($val);
        }
    }
    private function move_url_to_editors($url)
    {
        if(substr($url,0,5) == 'http:') {
            //https://editors.eol.org/other_files/EOL_Partner_MOUs/EOL_Naturalis-mou.pdf
            $basename = pathinfo($url, PATHINFO_BASENAME);
            return "https://editors.eol.org/other_files/EOL_Partner_MOUs/".$basename;
        }
    }
    
    private function write_to_text($recs)
    {
        $partner_head = array("Partner ID", "Partner name", "Overview", "URL", "Agreement URL", "Signed By", "Signed Date", "Create Date", "Description of Data", "Manager EOL ID", "Status");
        $resource_head = array("Resource ID", "Title", "First Published", "Last Updated", "Status");
        $contact_head = array("Contact ID", "Given Name", "Family Name", "Email", "Homepage", "Telephone", "Address", "Role");
        
        // [agreement_url_from_db] [agreement_url] -> not used for partner
        $partner_fields = array("partner_id", "partner_name", "overview", "url", "mou_url_editors", "signed_by", "signed_date", "create_date", "desc_of_data", "manager_eol_id", "status");
        $resource_fields = array("resource_id", "resource_title", "first_pub", "last_pub", "status");
        $contact_fields = array("eol_contact_id", "given_name", "family_name", "email", "homepage", "telephone", "address", "contact_role");
        
        $txtfile = CONTENT_RESOURCE_LOCAL_PATH . "partner_metadata.txt";
        $FILE = Functions::file_open($txtfile, "w");
        fwrite($FILE, implode("\t", $partner_head)."\n");
        
        foreach($recs as $partner_id => $rec) {
            $cols = array();
            foreach($partner_fields as $fld) $cols[] = self::clean_str($rec[$fld], false);
            fwrite($FILE, implode("\t", $cols)."\n");

            //resources
            if(@$rec['resources']) {
                fwrite($FILE, "\t".implode("\t", $resource_head)."\n");
                foreach(@$rec['resources'] as $rec2) {
                    $cols = array();
                    foreach($resource_fields as $fld) $cols[] = self::clean_str($rec2[$fld], false);
                    fwrite($FILE, "\t".implode("\t", $cols)."\n");
                }
            }

            //contacts
            if(@$rec['contacts'])
            {
                fwrite($FILE, "\t".implode("\t", $contact_head)."\n");
                foreach(@$rec['contacts'] as $rec3) {
                    $cols = array();
                    foreach($contact_fields as $fld) $cols[] = self::clean_str($rec3[$fld], false);
                    fwrite($FILE, "\t".implode("\t", $cols)."\n");
                }
            }
        }
        fclose($FILE);
    }
    
    /*
    public function begin()
    {
        $start = $this->mysqli->select_value("SELECT MIN(id) FROM names");
        $max_id = $this->mysqli->select_value("SELECT MAX(id) FROM names");
        $limit = 100000;
        
        $this->mysqli->begin_transaction();
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->check_st_john($i, $limit);
            // $this->generate_ranked_canonical_forms($i, $limit);
        }
        $this->mysqli->end_transaction();
    }
    
    public function check_st_john($start, $limit)
    {
        $query = "SELECT id, string FROM names WHERE string REGEXP BINARY 'st\\\\\.-[a-z]'
            AND id BETWEEN $start AND ". ($start+$limit-1);
        foreach($this->mysqli->iterate_file($query) as $row)
        {
            $id = $row[0];
            $string = $row[1];
            $canonical_form_string = Functions::canonical_form($string);
            if($canonical_form = CanonicalForm::find_or_create_by_string($canonical_form_string))
            {
                echo "UPDATE names SET canonical_form_id=$canonical_form->id WHERE id=$id\n";
                $this->mysqli->update("UPDATE names SET canonical_form_id=$canonical_form->id, ranked_canonical_form_id=$canonical_form->id WHERE id=$id");
            }
        }
        $this->mysqli->commit();
    }
    
    public function check($start, $limit)
    {
        echo "Looking up $start, $limit\n";
        $query = "SELECT n.id, canonical.string, ranked_canonical.string  FROM names n
            JOIN canonical_forms canonical ON (n.canonical_form_id=canonical.id)
            JOIN canonical_forms ranked_canonical ON (n.ranked_canonical_form_id=ranked_canonical.id)
            WHERE n.ranked_canonical_form_id != n.canonical_form_id
            AND n.id BETWEEN $start AND ". ($start+$limit-1);
        foreach($this->mysqli->iterate_file($query) as $row)
        {
            $name_id = $row[0];
            $canonical = trim($row[1]);
            $ranked_canonical = trim($row[2]);
            
            // ranked has "zz", normal does not. This is indicative of a particular kind of encoding problem
            if(strpos($ranked_canonical, "zz") !== false && strpos($canonical, "zz") === false)
            {
                // echo "UPDATE names SET ranked_canonical_form_id = canonical_form_id WHERE id = $id\n";
                $this->mysqli->update("UPDATE names SET ranked_canonical_form_id = canonical_form_id WHERE id = $name_id");
            }
        }
        $this->mysqli->commit();
    }
    
    public function generate_ranked_canonical_forms($start, $limit)
    {
        echo "Looking up $start, $limit\n";
        $query = "SELECT id, string FROM names
            WHERE id BETWEEN $start AND ". ($start+$limit-1)."
            AND (ranked_canonical_form_id IS NULL OR ranked_canonical_form_id=0)";
        foreach($this->mysqli->iterate_file($query) as $row)
        {
            $id = $row[0];
            $string = trim($row[1]);
            if(!$string || strlen($string) == 1) continue;

            // $canonical_form = trim($client->lookup_string($string));
            // if($count % 5000 == 0)
            // {
            //     echo "       > Parsed $count names ($id : $string : $canonical_form). Time: ". time_elapsed() ."\n";
            // }
            // $count++;
            // 
            // // report this problem
            // if(!$canonical_form) continue;
            // 
            // $canonical_form_id = CanonicalForm::find_or_create_by_string($canonical_form)->id;
            // $GLOBALS['db_connection']->query("UPDATE names SET ranked_canonical_form_id=$canonical_form_id WHERE id=$id");
        }
        $this->mysqli->commit();
    }
    */
}

?>
