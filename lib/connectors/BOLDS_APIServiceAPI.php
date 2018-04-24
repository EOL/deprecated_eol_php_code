<?php
namespace php_active_record;
/* connector: [bolds.php]
processid	sampleid	recordID	catalognum	fieldnum	institution_storing	collection_code	bin_uri	
phylum_taxID	phylum_name	class_taxID	class_name	order_taxID	order_name	family_taxID	family_name	subfamily_taxID	subfamily_name	genus_taxID	genus_name	
species_taxID	species_name	subspecies_taxID	subspecies_name	identification_provided_by	identification_method	identification_reference	tax_note	voucher_status	
tissue_type	collection_event_id	collectors	collectiondate_start	collectiondate_end	collectiontime	collection_note	site_code	sampling_protocol	lifestage	sex	reproduction	
habitat	associated_specimens	associated_taxa	extrainfo	notes	lat	lon	coord_source	coord_accuracy	elev	depth	elev_accuracy	depth_accuracy	country	province_state	region	
sector	exactsite	
image_ids	image_urls	media_descriptors	captions	copyright_holders	copyright_years	copyright_licenses	copyright_institutions	photographers

processid	sampleid	recordID	catalognum	fieldnum	institution_storing	collection_code	bin_uri	
phylum_taxID	phylum_name	class_taxID	class_name	order_taxID	order_name	family_taxID	family_name	subfamily_taxID	subfamily_name	genus_taxID	genus_name	
species_taxID	species_name	subspecies_taxID	subspecies_name	identification_provided_by	identification_method	identification_reference	tax_note	voucher_status	
tissue_type	collection_event_id	collectors	collectiondate_start	collectiondate_end	collectiontime	collection_note	site_code	sampling_protocol	lifestage	sex	reproduction	
habitat	associated_specimens	associated_taxa	extrainfo	notes	lat	lon	coord_source	coord_accuracy	elev	depth	elev_accuracy	depth_accuracy	country	province_state	region	
sector	exactsite	
image_ids	image_urls	media_descriptors	captions	copyright_holders	copyright_years	copyright_licenses	copyright_institutions	photographers	
sequenceID	markercode	genbank_accession	nucleotides	trace_ids	trace_names	trace_links	run_dates	sequencing_centers	directions	seq_primers	marker_codes


id	occurrenceID	catalogNumber	fieldNumber	identificationRemarks	basisOfRecord	institutionCode	
phylum	class	order	family	genus	scientificName	
identifiedBy	associatedOccurrences	associatedTaxa	collectionCode	eventID	locationRemarks	eventTime	habitat	samplingProtocol	locationID	eventDate	recordedBy	country	stateProvince	
locality	
decimalLatitude	decimalLongitude	coordinatePrecision	georeferenceSources	maximumDepthInMeters	minimumDepthInMeters	maximumElevationInMeters	minimumElevationInMeters	eventRemarks	
lifestage	sex	preparations	rightsHolder	rights	language

*/
class BOLDS_APIServiceAPI
{
    function __construct($folder = false)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->resource_agent_ids = array();

        $this->max_images_per_taxon = 10;
        $this->page['home'] = "http://www.boldsystems.org/index.php/TaxBrowser_Home";
        $this->page['sourceURL'] = "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=";
        $this->service['phylum'] = "http://v2.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=";
        
        $this->service["taxId"] = "http://www.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=all&includeTree=true&taxId=";
        
        $this->download_options = array('cache' => 1, 'resource_id' => 'BOLDS', 'expire_seconds' => 60*60*24*30*6, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1); //6 months to expire
    }

    function start()
    {
        exit("\nThis did not get used.\n");
        $taxon_ids = self::get_all_taxon_ids();
        $this->archive_builder->finalize(true);
        print_r($this->debug);
    }
    private function get_all_taxon_ids()
    {
        $phylums = self::get_all_phylums();
        $phylums = array_keys($phylums);

        // $phylums = array('Pyrrophycophyta', 'Heterokontophyta'); done
        // $phylums = array('Onychophora', 'Platyhelminthes', 'Porifera', 'Priapulida', 'Rotifera', 'Sipuncula'); done
        // $phylums = array('Basidiomycota', 'Chytridiomycota', 'Glomeromycota', 'Myxomycota', 'Zygomycota', 'Chlorarachniophyta', 'Ciliophora'); done
        // $phylums = array('Brachiopoda', 'Bryozoa', 'Chaetognatha', 'Cnidaria', 'Cycliophora', '', 'Gnathostomulida', 'Hemichordata', 'Nematoda', 'Nemertea'); done
        // $phylums = array('Annelida', 'Acanthocephala'); //done
        // $phylums = array('Ascomycota'); done
        // $phylums = array('Tardigrada', 'Xenoturbellida', 'Bryophyta', 'Chlorophyta', 'Lycopodiophyta', 'Pinophyta', 'Pteridophyta', 'Rhodophyta'); done
        // $phylums = array('Echinodermata'); done
        // $phylums = array('Mollusca'); done

        $phylums = array('Pyrrophycophyta', 'Heterokontophyta', 'Onychophora', 'Platyhelminthes', 'Porifera', 'Priapulida', 'Rotifera', 'Sipuncula', 'Basidiomycota', 'Chytridiomycota', 
        'Glomeromycota', 'Myxomycota', 'Zygomycota', 'Chlorarachniophyta', 'Ciliophora', 'Brachiopoda', 'Bryozoa', 'Chaetognatha', 'Cnidaria', 'Cycliophora', 'Gnathostomulida', 
        'Hemichordata', 'Nematoda', 'Nemertea', 'Annelida', 'Acanthocephala', 'Ascomycota', 'Tardigrada', 'Xenoturbellida', 'Bryophyta', 'Chlorophyta', 'Lycopodiophyta', 'Pinophyta', 
        'Pteridophyta', 'Rhodophyta', 'Echinodermata', 'Mollusca');
        
        //------------------------- the 3 big ones:
        // $phylums = array('Arthropoda');
        // $phylums = array('Magnoliophyta');
        $phylums = array('Chordata');

        $download_options = $this->download_options;
        $download_options['expire_seconds'] = false;
        
        foreach($phylums as $phylum) {
            echo "\n$phylum ";
            $final = array();
            $temp_file = Functions::save_remote_file_to_local($this->service['phylum'].$phylum, $download_options);
            $reader = new \XMLReader();
            $reader->open($temp_file);
            while(@$reader->read()) {
                if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "record") {
                    $string = $reader->readOuterXML();
                    if($xml = simplexml_load_string($string)) {
                        // print_r($xml);
                        $ranks = array('phylum', 'class', 'order', 'family', 'genus', 'species');
                        foreach($ranks as $rank) {
                            // echo "\n - $phylum ".@$xml->taxonomy->$rank->taxon->taxid."\n";
                            if($taxid = (string) @$xml->taxonomy->$rank->taxon->taxid) {
                                $final[$taxid] = '';
                            }
                        }
                    }
                }
            }
            unlink($temp_file);
            self::process_ids_for_this_phylum(array_keys($final), $phylum);
            // break; //debug
        }
    }
    private function process_ids_for_this_phylum($taxids, $phylum)
    {
        // print_r($taxids); exit;
        $total = number_format(count($taxids)); $i = 0;
        foreach($taxids as $taxid) {
            $i++;
            if(($i % 1000) == 0) echo "\n".number_format($i)." of $total - $phylum ";
            self::process_record($taxid);
            // if($i >= 20) break; //debug
        }
    }
    private function process_record($taxid)
    {
        /*
        Array (
                    [taxid] => 23
                    [taxon] => Mollusca
                    [tax_rank] => phylum
                    [tax_division] => Animals
                    [parentid] => 1
                    [taxonrep] => Mollusca
        
        [sitemap] => http://www.boldsystems.org/index.php/TaxBrowser_Maps_CollectionSites?taxid=2
        */
        if($json = Functions::lookup_with_cache($this->service['taxId'].$taxid, $this->download_options)) {
            $a = json_decode($json, true);
            // print_r($a); echo "\n[$taxid]\n"; //exit;
            $a = @$a[$taxid]; //needed
            if(@$a['taxon']) {
                self::create_taxon_archive($a);
                self::create_media_archive($a);
                self::create_trait_archive($a);
            }
        }
        // exit("\n");
    }
    private function create_media_archive($a)
    {   /* [images] => Array(
                       [0] => Array(
                               [copyright_institution] => Centre for Biodiversity Genomics
                               [copyright] => 
                               [copyright_license] => CreativeCommons - Attribution Non-Commercial Share-Alike
                               [copyright_holder] => CBG Photography Group
                               [copyright_contact] => ccdbcol@uoguelph.ca
                               [copyright_year] => 2008
                               
                               [specimenid] => 968120
                               [imagequality] => 5
                               [photographer] => Nick Jeffery
                               [image] => ANCN/IMG_6772+1228833566.JPG
                               [fieldnum] => L#08PUK-055
                               [sampleid] => 08BBANN-009
                               [mam_uri] => bold.org/323285
                               [meta] => Lateral
                               [catalognum] => 08BBANN-009
                               [taxonrep] => Clitellata
                               [aspectratio] => 1.499
                               [original] => 1
                               [external] => 
                           )
        */
        
        // /* un-comment in real operation
        if($images = @$a['images']) {
            // print_r($images);
            foreach($images as $img) {
                if($img['image']) {
                    $mr = new \eol_schema\MediaResource();
                    // if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
                    if($agent_ids = self::format_agents($img)) $mr->agentID = implode("; ", $agent_ids);
                    $mr->taxonID                = $a['taxid'];
                    $mr->identifier             = $img['image'];
                    $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
                    // $mr->language               = 'en';
                    $mr->format                 = Functions::get_mimetype($img['image']);
                    $mr->furtherInformationURL  = $this->page['sourceURL'].$a['taxid'];
                    $mr->description            = self::format_description($img);
                    $mr->UsageTerms             = self::format_license($img['copyright_license']);
                    if(!$mr->UsageTerms) continue; //invalid license
                    $mr->Owner                  = self::format_rightsHolder($img);
                    $mr->rights                 = '';
                    $mr->accessURI              = "http://www.boldsystems.org/pics/".$img['image'];
                    $mr->Rating                 = $img['imagequality']; //will need to check what values they have here...
                    if(!isset($this->object_ids[$mr->identifier])) {
                        $this->archive_builder->write_object_to_file($mr);
                        $this->object_ids[$mr->identifier] = '';
                    }
                }
            }
        }
        // */
        
        //[sitemap] => http://www.boldsystems.org/index.php/TaxBrowser_Maps_CollectionSites?taxid=2
        if($map_url = $a['sitemap']) {
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID                = $a['taxid'];
            $mr->identifier             = "map_".$a['taxid'];
            $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
            $mr->format                 = 'image/png';
            $mr->furtherInformationURL  = $this->page['sourceURL'].$a['taxid'];
            $mr->description            = '';
            $mr->UsageTerms             = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
            $mr->Owner                  = '';
            $mr->rights                 = '';
            $mr->accessURI              = $map_url;
            $mr->subtype                = 'Map';
            $mr->Rating                 = '';
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->object_ids[$mr->identifier] = '';
            }
        }
        
    }
    private function create_taxon_archive($a)
    {   /*                      
        [taxid] => 23
        [taxon] => Mollusca
        [tax_rank] => phylum
        [tax_division] => Animals
        [parentid] => 1
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $a['taxid'];
        $taxon->scientificName      = $a['taxon'];
        $taxon->taxonRank           = $a['tax_rank'];
        $taxon->parentNameUsageID   = $a['parentid'];
        if($taxon->parentNameUsageID == 1) {
            $taxon->parentNameUsageID .= "_".$a['tax_division'];
        }
        /* no data for:
        $taxon->taxonomicStatus          = '';
        $taxon->acceptedNameUsageID      = '';
        */
        if(isset($this->taxon_ids[$taxon->taxonID])) return;
        $this->taxon_ids[$taxon->taxonID] = '';
        $this->archive_builder->write_object_to_file($taxon);
        
        /* create these 4 taxon entries
            animals (Animalia),                         1_Animals
            plants (Plantae),                           1_Plants
            fungi (Fungi),                              1_Fungi
            protozoa and eucaryotic algae (Protista)    1_Protists
        */
        $add['1_Animals'] = 'Animalia';
        $add['1_Plants'] = 'Plantae';
        $add['1_Fungi'] = 'Fungi';
        $add['1_Protists'] = 'Protista';
        foreach($add as $taxid => $sciname) {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID             = $taxid;
            $taxon->scientificName      = $sciname;
            $taxon->taxonRank           = 'kingdom';
            if(isset($this->taxon_ids[$taxon->taxonID])) continue;
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function get_all_phylums()
    {
        if($html = Functions::lookup_with_cache($this->page['home'], $this->download_options)) {
            /* <li><a class="link" href="/index.php/Taxbrowser_Taxonpage?taxid=11">Acanthocephala [747]</a></li> */
            if(preg_match_all("/Taxbrowser_Taxonpage\?taxid\=(.*?) \[/ims", $html, $a)) {
                foreach($a[1] as $tmp) {
                    $tmp = explode('">', $tmp);
                    $final[$tmp[1]] = $tmp[0];
                }
            }
        }
        // print_r(array_keys($final)); exit;
        return $final;
    }
    private function format_description($img)
    {
        /*
        [specimenid] => 968120
        [imagequality] => 5
        [photographer] => Nick Jeffery
        [image] => ANCN/IMG_6772+1228833566.JPG
        [fieldnum] => L#08PUK-055
        [sampleid] => 08BBANN-009
        [mam_uri] => bold.org/323285
        [meta] => Lateral
        [catalognum] => 08BBANN-009
        [taxonrep] => Clitellata
        [aspectratio] => 1.499
        [original] => 1
        [external] => 
        */
        $final = "";
        if($val = @$img['meta'])            $final .= "$val. ";
        if($val = @$img['catalognum'])      $final .= "Catalog no.: $val. ";
        if($val = @$img['specimenid'])      $final .= "Specimen ID: $val. ";
        if($val = @$img['fieldnum'])        $final .= "Field no.: $val. ";
        if($val = @$img['taxonrep'])        $final .= "Taxon rep.: $val. ";
        if($val = @$img['imagequality'])    $final .= "Image quality: $val. ";
        if($val = @$img['aspectratio'])     $final .= "Aspect ratio: $val. ";
        return trim($final);
    }
    private function format_rightsHolder($img)
    {
        /*
        [copyright_institution] => Centre for Biodiversity Genomics
        [copyright] => 
        [copyright_license] => CreativeCommons - Attribution Non-Commercial Share-Alike
        [copyright_holder] => CBG Photography Group
        [copyright_contact] => ccdbcol@uoguelph.ca
        [copyright_year] => 2008
        */
        $final = "";
        if($val = @$img['copyright']) $final .= "$val. ";
        if($val = @$img['copyright_institution']) $final .= "$val. ";
        if($val = @$img['copyright_holder']) $final .= "$val. ";
        if($val = @$img['copyright_year']) $final .= "Year: $val. ";
        if($val = @$img['copyright_contact']) $final .= "Contact: $val. ";
        return trim($final);
    }
    private function format_license($license)
    {
        $license = strtolower(trim($license));
        
        if(stripos($license, "no derivatives") !== false)   return false; //string is found
        if(stripos($license, "by-nc-nd") !== false)         return false; //string is found
        
        if(stripos($license, "attribution share-alike") !== false)      return "http://creativecommons.org/licenses/by-sa/3.0/"; //string is found
        if(stripos($license, "(by-nc)") !== false)                      return "http://creativecommons.org/licenses/by-nc/3.0/"; //string is found
        if(stripos($license, "non-commercial share-alike") !== false)   return "http://creativecommons.org/licenses/by-nc-sa/3.0/"; //string is found
        if(stripos($license, "noncommercial sharealike") !== false)     return "http://creativecommons.org/licenses/by-nc-sa/3.0/"; //string is found
        if(stripos($license, "attribution (by)") !== false)             return "http://creativecommons.org/licenses/by/3.0/"; //string is found
        if(stripos($license, "non-commercial only") !== false)          return "http://creativecommons.org/licenses/by-nc/3.0/"; //string is found
        
        $arr["creativecommons - attribution non-commercial share-alike"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creativecommons - attribution"]                            = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creativecommons - attribution non-commercial"]             = "http://creativecommons.org/licenses/by-nc/3.0/";
        $arr["creativecommons - attribution share-alike"]                = "http://creativecommons.org/licenses/by-sa/3.0/";
        $arr["creative commons by nc sa"]                                = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creative commons-by-nc-sa"]                                = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["no rights reserved"]                                       = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creativecommons"]                                          = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creative commons"]                                         = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creativecom"]                                              = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creativecommons  attribution noncommercial share alike"]   = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creativecommons attribution non-commercial share-alike"]   = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        
        $arr["creativecommons (by-nc-sa)"]  = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creativecommons-by-nc-sa"]    = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creative commoms-by-nc-sa"]   = "http://creativecommons.org/licenses/by-nc-sa/3.0/";

        if($val = @$arr[$license]) return $val;
        else {
            // exit("\nInvalid license [$license]\n");
            $this->debug['undefined license'][$license] = '';
        }
    }
    private function format_agents($img)
    {   // [photographer] => Nick Jeffery
        $agent_ids = array();
        if($agent = trim(@$img['photographer'])) {
            $r = new \eol_schema\Agent();
            $r->term_name = $agent;
            $r->identifier = md5("$agent|photographer");
            $r->agentRole = "photographer";
            $r->term_homepage = "";
            $agent_ids[] = $r->identifier;
            if(!in_array($r->identifier, $this->resource_agent_ids)) {
               $this->resource_agent_ids[] = $r->identifier;
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    private function create_trait_archive($a)
    {
        /*             [stats] => Array(
                            [publicspecies] => 11115
                            [publicbins] => 15448
                            [publicmarkersequences] => Array(
                                    [COI-3P] => 338
                                    [COI-5P] => 113573
                                    [28S-D9-D10] => 2
                                    [CYTB] => 287
                                    [atp6] => 28
                                    [12S] => 534
                                )
                            [publicrecords] => 117712
                            [publicsubspecies] => 320
                            [specimenrecords] => 159082
                            [sequencedspecimens] => 143785
                            [barcodespecimens] => 124800
                            [species] => 14891
                            [barcodespecies] => 13237
                        )
        
        *only non-family ranks will have TraitData:
        publicrecords
            http://eol.org/schema/terms/NumberPublicRecordsInBOLD (numeric)
        specimenrecords:
            http://eol.org/schema/terms/NumberRecordsInBOLD (numeric)
            http://eol.org/schema/terms/RecordInBOLD (Yes/No)
        */
        if($val = @$a['stats']['publicrecords']) {
            $rec = array();
            $rec["taxon_id"]            = $a['taxid'];
            $rec["catnum"]              = self::generate_id_from_array_record($a);
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/NumberPublicRecordsInBOLD";
            $rec['measurementValue']    = $val;
            $rec["source"]              = $this->page['sourceURL'].$a['taxid'];
            self::add_string_types($rec);
        }
        if($specimenrecords = @$a['stats']['specimenrecords']) {
            $rec = array();
            $rec["taxon_id"]            = $a['taxid'];
            $rec["catnum"]              = self::generate_id_from_array_record($a);
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/NumberRecordsInBOLD";
            $rec['measurementValue']    = $specimenrecords;
            $rec["source"]              = $this->page['sourceURL'].$a['taxid'];
            self::add_string_types($rec);
            
            if($specimenrecords > 0) {
                $rec = array();
                $rec["taxon_id"]            = $a['taxid'];
                $rec["catnum"]              = self::generate_id_from_array_record($a);
                $rec['measurementOfTaxon']  = "true";
                $rec['measurementType']     = "http://eol.org/schema/terms/RecordInBOLD";
                $rec['measurementValue']    = 'http://eol.org/schema/terms/yes';
                $rec["source"]              = $this->page['sourceURL'].$a['taxid'];
                self::add_string_types($rec);
            }
        }
        else {
            $rec = array();
            $rec["taxon_id"]            = $a['taxid'];
            $rec["catnum"]              = self::generate_id_from_array_record($a);
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/RecordInBOLD";
            $rec['measurementValue']    = 'http://eol.org/schema/terms/no';
            $rec["source"]              = $this->page['sourceURL'].$a['taxid'];
            self::add_string_types($rec);
        }
    }
    private function add_string_types($rec, $a = false) //$a is only for debugging
    {
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"], $rec);
        unset($rec['catnum']);
        unset($rec['taxon_id']);
        
        $m = new \eol_schema\MeasurementOrFact();
        $m->occurrenceID = $occurrence_id;
        foreach($rec as $key => $value) $m->$key = $value;
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }

    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        if($val = @$rec['lifestage']) $o->lifeStage = $val;
        $o->taxonID = $taxon_id;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    private function generate_id_from_array_record($arr)
    {
        $json = json_encode($arr);
        return md5($json);
    }
}
?>