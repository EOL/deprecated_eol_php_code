<?php
namespace php_active_record;
/* connector: [usa_endangered.php] 
*/
class USAendangeredSpeciesAPI
{
    function __construct($folder = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->debug = array();
        $this->download_options = array(
            'resource_id'        => $this->resource_id,
            'expire_seconds'     => 60*60*24*30, //expires in 1 month
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        // $this->download_options['expire_seconds'] = 0;
        $this->page['domain'] = 'https://ecos.fws.gov';
        $this->page['animals'] = 'https://ecos.fws.gov/ecp0/reports/ad-hoc-species-report?kingdom=V&kingdom=I&status=E&status=T&status=EmE&status=EmT&status=EXPE&status=EXPN&status=SAE&status=SAT&mapstatus=3&fcrithab=on&fstatus=on&fspecrule=on&finvpop=on&fgroup=on&header=Listed+Animals';
        $this->page['plants'] = 'https://ecos.fws.gov/ecp0/reports/ad-hoc-species-report?kingdom=P&status=E&status=T&status=EmE&status=EmT&status=EXPE&status=EXPN&status=SAE&status=SAT&mapstatus=3&fcrithab=on&fstatus=on&fspecrule=on&finvpop=on&fgroup=on&ffamily=on&header=Listed+Plants';
        $this->page['taxon']= 'https://ecos.fws.gov/ecp0/profile/speciesProfile?sId=';
    }
    function start()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);

        $groups = array('animals', 'plants');
        // $groups = array('animals');
        foreach($groups as $group) self::process_group($group);

        // exit;
        $this->archive_builder->finalize(true);
        Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function process_group($group)
    {
        if($html = Functions::lookup_with_cache($this->page[$group], $this->download_options)) {
            if(preg_match("/\"resultTable\">(.*?)<\/table>/ims", $html, $arr)) {
                $html = $arr[1];
                if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
                    // print_r($arr[1][0]); exit;
                    $fields = self::get_fields_from_tr($arr[1][0]);
                    $rows = $arr[1];
                    array_shift($rows);
                    echo "\n".count($rows)."\n";
                    // echo "\n".$rows[0]; echo "\n".$rows[1466];
                    $limit = 0; //only for debug to limit
                    foreach($rows as $row) {
                        // echo "\n".$row;
                        $limit++;
                        if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $arr)) {
                            $tds = $arr[1];
                            $rec = array(); $i = -1;
                            foreach($fields as $field) {
                                $i++;
                                $rec[$field] = $tds[$i];
                            }
                            // print_r($rec); exit;
                            
                            /* good debug - process one species
                            if($rec['common_name'] == 'Nassau grouper') {
                                print_r($rec);
                                if($rec) self::process_rec($rec);
                            }
                            */

                            // /* normal operation
                            if($rec) self::process_rec($rec);
                            // */
                            
                            // if($limit >= 5) break; //debug only
                        }
                    }
                }
            }
        }
    }
    private function process_rec($rec)
    {   /*Array(
            [scientific_name] => <a href="/ecp/species/615"><i>Zyzomys pedunculatus</i></a>
            [common_name] => Australian native mouse
            [critical_habitat] => N/A
            [species_group] => Mammals
            [federal_listing_status] => <i class='fa fa-info-circle' title='Endangered! E = endangered. A species in danger of extinction throughout all or a significant portion of its range.'></i>  Endangered
            [special_rules] => N/A
            [where_listed] => Wherever found
            [taxon_id] => 615
            [taxon_name] => Zyzomys pedunculatus
            [conserv_stat] => Endangered
        )*/
        if($rec['common_name'] == 'No common name') $rec['common_name'] = '';
        if(preg_match("/\/species\/(.*?)\"/ims", $rec['scientific_name'], $arr)) $rec['taxon_id'] = $arr[1];
        $rec['taxon_name'] = strip_tags($rec['scientific_name']);
        if(preg_match("/title=\'(.*?)\!/ims", $rec['federal_listing_status'], $arr)) $rec['conserv_stat'] = $arr[1];
        if(@$rec['taxon_name'] && @$rec['conserv_stat']) self::write_archive($rec);
    }
    private function write_archive($rec)
    {
        self::create_taxon($rec);
        if(@$rec['common_name']) self::create_vernaculars($rec);
        
        $info = self::create_objects($rec);
        $rec['ref_ids'] = @$info['ref_ids'];
        
        if(@$rec['conserv_stat']) self::create_trait($rec);
        $this->debug[$rec['conserv_stat']] = '';
    }
    private function create_objects($rec)
    {
        $ref_ids = array();
        if($html = Functions::lookup_with_cache($this->page['taxon'].$rec['taxon_id'], $this->download_options)) {
            if($refs = self::parse_refs($html, $rec)) {
                // print_r($refs);
                $ref_ids = self::create_references($refs);
            }
            // exit("\n-refs end-\n");
        }
        /* debug only
        if(!$ref_ids) {
            print_r($rec); print_r($ref_ids);
            exit("\nno ref above this\n");
        }
        else {
            // print_r($rec); print_r($ref_ids);
            // exit("\nwith ref above this\n");
        }
        */
        return array('ref_ids' => $ref_ids);
    }
    private function parse_refs($html, $rek)
    {
        $final = array();
        if(preg_match("/Federal Register Documents<\/div>(.*?)<\/table>/ims", $html, $arr)) {
            $html = $arr[1];
            $html = str_ireplace(' style="white-space:nowrap;"', "", $html);
            // echo("\n$html\n");
            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
                // print_r($arr[1]); exit;
                $fields = self::get_fields_from_tr($arr[1][0]);
                $rows = $arr[1];
                array_shift($rows);
                echo "\nRefs rows: ".count($rows)."\n";
                foreach($rows as $row) {
                    // echo "\n".$row;
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $arr)) {
                        $tds = $arr[1];
                        $rec = array(); $i = -1;
                        foreach($fields as $field) {
                            $i++;
                            $rec[$field] = trim($tds[$i]);
                        }
                        // echo "\n-----------"; print_r($rec); echo "\n-----------";
                        /*Array(
                            [Date] => 1970-06-02 00:00:00.0
                            [Citation Page] => 35 FR 8491 8498
                            [Title] => <a target="_blank" href="/docs/federal_register/fr21.pdf">Part 17 - Conservation of Endangered Species and Other Fish or Wildlife (First List of Endangered Foreign Fish and Wildlife as Appendix A)</a>
                        )*/
                        if(preg_match("/\">(.*?)<\/a>/ims", $rec['Title'], $arr)) {
                            $rec['full_ref'] = $arr[1];
                            if($val = $rec['Date']) $rec['full_ref'] .= ". ".$val.".";
                        }
                        elseif($val = trim(strip_tags($rec['Title']))) {
                            $rec['full_ref'] = $val;
                            if($val = $rec['Date']) $rec['full_ref'] .= ". ".$val.".";
                        }
                        if(preg_match("/href=\"(.*?)\"/ims", $rec['Title'], $arr)) {
                            $rec['url'] = $arr[1];
                            if(substr($rec['url'],0,4) != 'http') $rec['url'] = $this->page['domain'].$rec['url'];
                        }
                        if(@$rec['full_ref']) $final[] = $rec;
                        else
                        {
                            print_r($rec); print_r($row); print_r($rek);
                            echo("\nno full_ref\n"); //exit;
                            continue;
                        }
                    }
                }
            }
        }
        // print_r($final); exit;
        return $final;
    }
    private function create_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['taxon_id'];
        $taxon->scientificName  = $rec['taxon_name'];
        // $taxon->taxonRank             = '';
        // $taxon->furtherInformationURL = $this->page['taxon'].$rec['taxon_id'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function create_trait($rek)
    {
        $rec = array();
        $rec["taxon_id"] = $rek['taxon_id'];
        $rec["catnum"] = $rek['taxon_id'].'_'.$rek['conserv_stat'];
        $mType = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus';
        $mValue = self::get_URI($rek['conserv_stat']);
        // $rec['measurementRemarks'] = $string_val;
        // $rec['bibliographicCitation'] = $this->partner_bibliographicCitation;
        $rec['source'] = $this->page['taxon'].$rek['taxon_id'];
        if($ref_ids = @$rek['ref_ids']) $rec['referenceID'] = implode("; ", $ref_ids);
        $this->func->add_string_types($rec, $mValue, $mType, "true");
    }
    private function get_URI($str)
    {
        if($str == 'Endangered') return 'http://eol.org/schema/terms/federalEndangered';
        elseif($str == 'Threatened') return 'http://eol.org/schema/terms/federalThreatened';
        elseif($str == 'Experimental Population, Non-Essential') return $str;
        elseif($str == 'Similarity of Appearance to a Threatened Taxon') return $str;
    }
    private function create_vernaculars($rec)
    {
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $rec['taxon_id'];
        $v->vernacularName  = $rec['common_name'];
        $v->language        = self::guess_language($rec['common_name']);
        // $v->countryCode     = '';
        $md5 = md5($rec['taxon_id'].$rec['common_name']);
        if(!isset($this->comnames[$md5])) {
            $this->archive_builder->write_object_to_file($v);
            $this->comnames[$md5] = '';
        }
    }
    private function guess_language($comname)
    {
        if(in_array($comname, array('kookoolau','Olulu','Kamanomano'))) return '';
        if(stripos($comname, "`") !== false) return ''; //string is found
        else                                 return 'en';
    }
    private function get_fields_from_tr($str)
    {
        //for orig main
        if(preg_match_all("/class=\"(.*?)\"/ims", $str, $arr)) {
            return $arr[1];
        }
        //for refs
        if(preg_match_all("/<th>(.*?)<\/th>/ims", $str, $arr)) {
            return $arr[1];
        }
    }
    private function create_references($recs)
    {
        // print_r($recs); //exit;
        echo "\nrecs count: ".count($recs)."\n";
        $ref_ids = array();
        foreach($recs as $rec) {
            /*[1] => Array (
                        [Date] => 1970-04-14 00:00:00.0
                        [Citation Page] => 35 FR 6069
                        [Title] => <a target="_blank" href="/docs/federal_register/fr20.pdf">Notice of Proposed Rulemaking (Endangered Species Conservation); 35 FR 6069</a>
                        [full_ref] => Notice of Proposed Rulemaking (Endangered Species Conservation); 35 FR 6069. 1970-04-14 00:00:00.0.
                        [url] => https://ecos.fws.gov/docs/federal_register/fr20.pdf
                    )
            */
            $r = new \eol_schema\Reference();
            $r->identifier = md5($rec['full_ref'].@$rec['url']);
            $r->full_reference = $rec['full_ref'];
            $r->title = $rec['Title'];
            $r->pages = $rec['Citation Page'];
            $r->created = $rec['Date'];
            $r->uri = @$rec['url'];
            $ref_ids[$r->identifier] = '';
            if(!isset($this->reference_ids[$r->identifier])) {
                $this->reference_ids[$r->identifier] = '';
                $this->archive_builder->write_object_to_file($r);
            }
        }
        // print_r(array_keys($ref_ids)); echo "\nref_ids above this\n";
        return array_keys($ref_ids);
    }
    //********************************************************************************************************************************************************
    //********************************************************************************************************************************************************
    private function create_text_object($rec)
    {
        // print_r($rec); //exit;
        /*Array(
            [DEF_id] => desc_1
            [type] => http://purl.org/dc/dcmitype/Text
            [Subject] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription
            [REF|Plant|theplant] => 1
            [description] => <b>Bole:</b>  Small/medium. To 24 m.  <b>Bark:</b>  Grey/pale green. Smooth.  <b>Slash:</b>  Yellow with white or yellow lines.  <b>Leaf:</b>  Simple. Alternate.  <b>Petiole:</b>  0.5 - 2.5 cm.  <b>Lamina:</b>  Medium. 4 - 19 × 2.5 - 10 cm (Juvenile up to 25 × 27 cm). Ovate/elliptic. Cuneate/cordate. Asymmetric. 5 - 7 nerved from base. Acuminate. Entire. Hairy/glabrous. Simple.  <b>Domatia:</b>  Present/absent. Small tufts of hairs.  <b>Glands:</b>   Absent.  <b>Stipules:</b>  Absent.  <b>Thorns & Spines:</b>  Absent.  <b>Flower:</b>  White/pale yellow. Fragrant. Infloresence 3 - 23 flowered axillary cymes. Hermaphrodite.  <b>Fruit:</b>  Globose 0.8 - 1.0 × 0.4 - 0.9 cm.
            [REF|Reference|ref] => 1
            [blank_1] => http://creativecommons.org/licenses/by-sa/3.0/
            [Title] => Botanical Description
        )
        Array(
            [DEF_id] => desc_659
            [type] => http://purl.org/dc/dcmitype/Text
            [Subject] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription
            [REF|Plant|theplant] => 654
            [description] => <b>Bole:</b>  Small. To 10 m.  <b>Bark:</b>  NR.  <b>Slash:</b>  NR.  <b>Leaf:</b>  Simple. Alternate.  <b>Petiole:</b>  0.5 - 3 cm. Bristly pubescent.  <b>Lamina:</b>  Medium. 7 - 18 × 3 - 7 cm. Ovate/oblong/oblong-lanceolate. Cuneate. Acuminate. Serrate. Glabrous above; slightly hairy beneath.  <b>Domatia:</b>  Absent.  <b>Glands:</b>   Brown dots underneath leaves.  <b>Stipules:</b>  Present.  <b>Thorns & Spines:</b>  Absent.  <b>Flower:</b>  Slender terminal thyrse.  <b>Fruit:</b>  Capsule 3-lobed. 0.1 - 1.7 cm long.
            [REF|Reference|ref] => 1
            [blank_1] => http://creativecommons.org/licenses/by-sa/3.0/
            [Title] => Botanical Description
        )*/
        $this->taxa_with_trait[$rec['REF|Plant|theplant']] = ''; //to be used when creating taxon.tab
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $rec['REF|Plant|theplant'];
        $mr->identifier     = $rec['DEF_id'];
        $mr->type           = $rec['type'];
        $mr->language       = 'en';
        $mr->format         = "text/html";
        $mr->CVterm         = $rec['Subject'];
        // $mr->Owner          = '';
        // $mr->rights         = '';
        $mr->title          = $rec['Title'];
        $mr->UsageTerms     = $rec['blank_1'];
        $mr->description    = $rec['description'];
        // $mr->LocationCreated = '';
        $mr->bibliographicCitation = $this->partner_bibliographicCitation;
        $mr->furtherInformationURL = $this->partner_source_url;
        $mr->referenceID = $rec['REF|Reference|ref'];
        if(!@$rec['REF|Reference|ref']) {
            print_r($rec);
            exit("\nNo reference!\n");
        }
        // if($agent_ids = )  $mr->agentID = implode("; ", $agent_ids);
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
}
?>
