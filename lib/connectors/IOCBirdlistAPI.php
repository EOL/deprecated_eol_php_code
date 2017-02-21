<?php
namespace php_active_record;
/* connector: [ioc.php] - https://eol-jira.bibalex.org/browse/TRAM-499
*/
class IOCBirdlistAPI
{
    public function __construct($test_run = false, $folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxa_ids             = array();
        $this->taxa_reference_ids   = array(); // $this->taxa_reference_ids[taxon_id] = reference_ids
        $this->object_reference_ids = array();
        $this->object_agent_ids     = array();
        $this->reference_ids        = array();
        $this->agent_ids            = array();
        $this->download_options = array('resource_id' => $folder, 'timeout' => 172800, 'expire_seconds' => false, 'download_wait_time' => 2000000);
        
        $this->xml_data = "http://www.worldbirdnames.org/master_ioc-names_xml.xml";
        $this->xml_data = "http://localhost/cp/IOCBirdlist/data/master_ioc-names_xml.xml";
        
        $this->domain = "http://www.worldbirdnames.org";
        $this->source = "http://www.worldbirdnames.org";
        $this->bibliographic_citation = "Gill F & D Donsker (Eds). 2017. IOC World Bird List (v 7.1). http://dx.doi.org/10.14344/IOC.ML.7.1";
        
        $this->debug = array();
    }

    function get_all_taxa($resource_id)
    {
        /*
        self::process_taxa_synonyms();          echo "\n synonyms -- DONE";
        self::process_taxa_object_references(); echo "\n dataObject references -- DONE";
        self::process_taxa_object_agents();     echo "\n agents -- DONE";
        self::process_taxa_objects();           echo "\n dataObjects -- DONE";
        */
        
        self::create_aves();
        self::process_taxa();
        $this->archive_builder->finalize(true);
        
        print_r($this->debug['breeding_regions']);
        print_r($this->debug['breeding_subregions']);
        print_r($this->debug['nonbreeding_regions']);
    }

    private function process_taxa()
    {
        $options = $this->download_options;
        $options['cache'] = true; //cache param is exclusive to save_remote_file_to_local()
        $temp_path = Functions::save_remote_file_to_local($this->xml_data, $options);
        echo "\ntemp path: [$temp_path]\n";
        $xml = simplexml_load_file($temp_path);
        foreach($xml->list->order as $o)
        {
            $rek = array();
            // echo "\n --O".$o->latin_name;
            $rek['order']['latin'] = (string) $o->latin_name;
            $rek['order']['code'] = (string) $o->code;
            $rek['order']['note'] = (string) $o->note;
            foreach($o->family as $f)
            {
                // echo "\n ----F ".$f->latin_name;
                $rek['family']['latin'] = (string) $f->latin_name;
                $rek['family']['authority'] = (string) $f->authority;
                $rek['family']['english'] = (string) $f->english_name;
                $rek['family']['code'] = (string) $f->code;
                $rek['family']['note'] = (string) $f->note;
                $rek['family']['url'] = (string) $f->url;
                foreach($f->genus as $g)
                {
                    // echo "\n ------G ".$g->latin_name;
                    $rek['genus']['latin'] = (string) $g->latin_name;
                    $rek['genus']['authority'] = (string) $g->authority;
                    $rek['genus']['english'] = (string) $g->english_name;
                    $rek['genus']['code'] = (string) $g->code;
                    $rek['genus']['note'] = (string) $g->note;
                    foreach($g->species as $s)
                    {
                        // if($s->latin_name == "molybdophanes") exit("\n-elix $g->latin_name -\n");
                        // echo "\n --------S ".$s->latin_name;
                        $rek['species']['latin'] = (string) $s->latin_name;
                        $rek['species']['authority'] = (string) $s->authority;
                        $rek['species']['english'] = (string) $s->english_name;
                        $rek['species']['breeding_regions'] = (string) $s->breeding_regions;
                        $rek['species']['breeding_subregions'] = (string) $s->breeding_subregions;
                        $rek['species']['nonbreeding_regions'] = (string) $s->nonbreeding_regions;
                        if($s{"extinct"} == "yes") $rek['species']['status'] = "http://eol.org/schema/terms/extinct";
                        else                       $rek['species']['status'] = "http://eol.org/schema/terms/extant";
                        foreach($s->subspecies as $b)
                        {
                            // echo "\n ----------B ".$b->latin_name;
                            $rek['subspecies']['latin'] = (string) $b->latin_name;
                            $rek['subspecies']['authority'] = (string) $b->authority;
                            $rek['subspecies']['english'] = (string) $b->english_name;
                            $rek['subspecies']['breeding_regions'] = (string) $b->breeding_regions;
                            $rek['subspecies']['breeding_subregions'] = (string) $b->breeding_subregions;
                            $rek['subspecies']['nonbreeding_regions'] = (string) $b->nonbreeding_regions;
                            if($b{"extinct"} == "yes")  $rek['subspecies']['status'] = "http://eol.org/schema/terms/extinct";
                            else                        $rek['subspecies']['status'] = "http://eol.org/schema/terms/extant";

                            /* debug
                            if($rek['species']['latin'] == "molybdophanes") {print_r($rek); exit;}
                            if($s->latin_name == "molybdophanes") {print_r($rek); exit;}
                            */
                            
                            $rek = self::prepare_names($rek, true);
                            self::create_taxa($rek, true);
                            
                            //for debug only
                            if($val = $rek['species']['breeding_regions'])          $this->debug['breeding_regions'][$val] = '';
                            if($val = $rek['species']['breeding_subregions'])       $this->debug['breeding_subregions'][$val] = '';
                            if($val = $rek['species']['nonbreeding_regions'])       $this->debug['nonbreeding_regions'][$val] = '';
                            if($val = $rek['subspecies']['breeding_regions'])       $this->debug['breeding_regions'][$val] = '';
                            if($val = $rek['subspecies']['breeding_subregions'])    $this->debug['breeding_subregions'][$val] = '';
                            if($val = $rek['subspecies']['nonbreeding_regions'])    $this->debug['nonbreeding_regions'][$val] = '';
                        }
                        if(!$s->subspecies)
                        {
                            $rek = self::prepare_names($rek, false);
                            self::create_taxa($rek, false);
                            //for debug only
                            if($val = $rek['species']['breeding_regions'])          $this->debug['breeding_regions'][$val] = '';
                            if($val = $rek['species']['breeding_subregions'])       $this->debug['breeding_subregions'][$val] = '';
                            if($val = $rek['species']['nonbreeding_regions'])       $this->debug['nonbreeding_regions'][$val] = '';
                        }
                    }
                }
            }
        }
        unlink($temp_path);
    }

    private function create_taxa($rek, $with_subspecies)
    {   /*
    [order] => Array
            [latin] => STRUTHIONIFORMES
            [code] => PHY
            [note] => The ratites (paleognaths) are the sister group to the rest of living birds
            [taxon] => Struthioniformes
            [parent] => Aves
    [family] => Array
            [latin] => Struthionidae
            [authority] => 
            [english] => Ostriches
            [code] => 
            [note] => 
            [url] => bow/ratites/
            [taxon] => Struthionidae
            [parent] => Struthioniformes
    [genus] => Array
            [latin] => Struthio
            [authority] => Linnaeus, 1758
            [english] => 
            [code] => 
            [note] => 
            [taxon] => Struthio Linnaeus, 1758
            [parent] => Struthionidae
    [species] => Array
            [latin] => camelus
            [authority] => Linnaeus, 1758
            [english] => 
            [breeding_regions] => AF
            [breeding_subregions] => w, c, e, sw
            [nonbreeding_regions] => 
            [taxon] => Struthio camelus Linnaeus, 1758
            [parent] => Struthio Linnaeus, 1758
    [subspecies] => Array
            [latin] => syriacus
            [authority] => Rothschild, 1919
            [english] => 
            [breeding_regions] => 
            [breeding_subregions] => Syrian and Arabian Deserts
            [nonbreeding_regions] => 
            [taxon] => Struthio camelus syriacus Rothschild, 1919
            [parent] => Struthio camelus Linnaeus, 1758
        */
        
        $ranks = array('order', 'family', 'genus', 'species');
        if($with_subspecies) $ranks[] = 'subspecies';
        foreach($ranks as $rank)
        {
            $taxon_name = $rek[$rank]['taxon'];
            $parent     = $rek[$rank]['parent'];
            $taxonID            = md5($taxon_name);
            $parentNameUsageID  = md5($parent);
            if(!isset($this->taxa_ids[$taxonID]))
            // if(true)
            {
                $this->taxa_ids[$taxonID] = '';
                $taxon = new \eol_schema\Taxon();
                $taxon->taxonID             = $taxonID;
                $taxon->taxonRank           = $rank;
                $taxon->scientificName      = $taxon_name;
                $taxon->parentNameUsageID   = $parentNameUsageID;
                if($val = @$rek[$rank]['url'])      $taxon->furtherInformationURL = $this->domain."/".$val;
                if($val = @$rek[$rank]['note'])     $taxon->taxonRemarks = $val;
                if($val = @$rek[$rank]['english'])  self::create_vernacular($val, $taxonID);
                $this->archive_builder->write_object_to_file($taxon);
                
                if(in_array($rank, array("species", "subspecies")))
                {
                    //for extinction status
                    $mrec = array(); //m for measurement
                    $mrec["taxon_id"] = $taxonID;
                    $mrec["catnum"] = "es"; //extinction status
                    $mrec["source"] = $this->source;
                    if($val = @$rek[$rank]['status']) self::add_string_types($mrec, $val, "http://eol.org/schema/terms/ExtinctionStatus", "true");
                }
                
            }
        }
        /*
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
        */
    }

    private function create_vernacular($vernacular, $taxonID)
    {
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $taxonID;
        $v->vernacularName  = $vernacular;
        $v->language        = "en";
        $this->archive_builder->write_object_to_file($v);
    }
    
    private function prepare_names($rek, $with_subspecies) //taxon and parent
    {
        /*
        [order] => Array
                [latin] => STRUTHIONIFORMES
        [family] => Array
                [latin] => Struthionidae
                [authority] => 
        [genus] => Array
                [latin] => Struthio
                [authority] => Linnaeus, 1758
        [species] => Array
                [latin] => camelus
                [authority] => Linnaeus, 1758
        [subspecies] => Array
                [latin] => syriacus
                [authority] => Rothschild, 1919
        */
        //get taxon
        $rek['order']['taxon']      = ucfirst(strtolower($rek['order']['latin']));
        $rek['family']['taxon']     = trim($rek['family']['latin']." ".$rek['family']['authority']);
        $rek['genus']['taxon']      = trim($rek['genus']['latin']." ".$rek['genus']['authority']);
        $rek['species']['taxon']    = trim($rek['genus']['latin']." ".$rek['species']['latin']." ".$rek['species']['authority']);
        if($with_subspecies)
        {
            $rek['subspecies']['taxon'] = trim($rek['genus']['latin']." ".$rek['species']['latin']." ".$rek['subspecies']['latin']." ".$rek['subspecies']['authority']);
        }
        //get parent
        $rek['order']['parent']      = "Aves";
        $rek['family']['parent']     = $rek['order']['taxon'];
        $rek['genus']['parent']      = $rek['family']['taxon'];
        $rek['species']['parent']    = $rek['genus']['taxon'];
        if($with_subspecies) $rek['subspecies']['parent'] = $rek['species']['taxon'];
        return $rek;
    }

    private function create_aves() //and 3 others non-rank taxa
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = md5("Aves");
        $taxon->taxonRank           = "class";
        $taxon->scientificName      = "Aves";
        $this->archive_builder->write_object_to_file($taxon);
        
        //3 non-rank taxa
        $taxa = array("Paleognathae", "Neognathae", "Neoaves");
        foreach($taxa as $sciname)
        {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID             = md5($sciname);
            $taxon->scientificName      = $sciname;
            if($sciname == "Neoaves") $taxon->taxonRemarks = "Neoaves includes three major components: (1) a basal unresolved polytomy of at least 9 Orders, (2) a Core Waterbird Clade (Aequornithes) and (3) a Core Landbird Clade (Telluraves) (Prum et al. 2015, Suh et al. 2016).";
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    
    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"];
        $occurrence_id = $taxon_id . "_" . $catnum;
        $m = new \eol_schema\MeasurementOrFact();
        $this->add_occurrence($taxon_id, $occurrence_id, $rec);
        $m->occurrenceID       = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        if($measurementOfTaxon == "true")
        {
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
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $occurrence_id, $rec)
    {
        if(isset($this->occurrence_ids[$occurrence_id])) return;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        if($val = @$rec['sex']) $o->sex = $val;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return;
    }
    
    //===================================================================================================================================================
    //===================================================================================================================================================
    
    private function get_ref_details_from_fishbase_and_create_ref($ref_id)
    {
        $r = new \eol_schema\Reference();
        $r->full_reference = $fb_full_ref;
        $r->identifier = $ref_id;
        $r->uri = $url;
        if(!isset($this->reference_ids[$ref_id]))
        {
            $this->reference_ids[$ref_id] = md5($fb_full_ref);
            $this->archive_builder->write_object_to_file($r);
            return md5($fb_full_ref);
        }
    }
    
    private function process_taxa_object_agents()
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = $a['agent'];
        $r->agentRole       = $a['role'];
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        $r->term_homepage   = $a['homepage'];
        $agent_ids[] = $r->identifier;
        if(!isset($this->agent_ids[$r->identifier]))
        {
           $this->agent_ids[$r->identifier] = $r->term_name;
           $this->archive_builder->write_object_to_file($r);
        }
    }

    private function create_references($refs)
    {
        $r = new \eol_schema\Reference();
        $r->full_reference = $ref['reference'];
        $r->identifier = md5($r->full_reference);
        $r->uri = $ref['url'];
        $reference_ids[] = $r->identifier;
    
        if(!isset($this->reference_ids[$ref_id]))
        {
            $this->reference_ids[$ref_id] = $r->identifier; //normally the value should be just '', but $this->reference_ids will be used in - convert_FBrefID_with_archiveID()
            $this->archive_builder->write_object_to_file($r);
        }
    }
    
    private function process_taxa_objects()
    {
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $taxonID;
        $mr->identifier     = $o['dc_identifier'];
        $mr->type           = $o['dataType'];
        $mr->language       = 'en';
        $mr->format         = $o['mimeType'];
        if(substr($o['dc_source'], 0, 4) == "http") $mr->furtherInformationURL = $o['dc_source'];
        $mr->accessURI      = $o['mediaURL'];
        $mr->thumbnailURL   = $o['thumbnailURL'];
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
        $this->archive_builder->write_object_to_file($mr);
    }

}
?>
