<?php
namespace php_active_record;
/* connectors which used this are:
 - AfricaTreeDBAPI.php 
 - CITESspeciesAPI.php
-------------------------------------------------------------------------
child record in MoF:
    - doesn't have: occurrenceID | measurementOfTaxon -> meaning blank for child records
    - has parentMeasurementID
    - has also a unique measurementID, as expected.
minimum cols on a child record in MoF
    - measurementID
    - measurementType
    - measurementValue
    - parentMeasurementID
-------------------------------------------------------------------------
- MeasurementOfTaxon should be blank for child records.
- MeasurementOfTaxon should be 'false' if to represent additional metadata. OBSOLETE
-------------------------------------------------------------------------
*/
class TraitGeneric
{
    function __construct($resource_id, $archive_builder, $is_long_type = true)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->is_long_type = $is_long_type;

        /*
        if(method_exists('RemoveHTMLTagsAPI','remove_html_tags')) echo "\nRemoveHTMLTagsAPI lib already set.\n";
        else {
            echo "\nRemoveHTMLTagsAPI lib not yet set.\n";
            require_library('connectors/RemoveHTMLTagsAPI');
        }
        */
        require_library('connectors/RemoveHTMLTagsAPI');
    }
    public function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        if($value == 'DISCARD') return false;
        if($measurementType == 'DISCARD') return false;
        if(!$value) return false;
        if(!$measurementType) return false;

        // /* new Feb 24, 2021
        if(isset($rec['http://eol.org/schema/parentMeasurementID'])) {
            $rec['parentMeasurementID'] = $rec['http://eol.org/schema/parentMeasurementID'];
            print("\nEli investigate this resource [$this->resource_id]\n");
        } //should be $rec['parentMeasurementID] not $rec['http://eol.org/schema/parentMeasurementID']. Weird I missed it, it was the latter for some time now.
        // */

        // /* Per Jen: https://eol-jira.bibalex.org/browse/DATA-1863?focusedCommentId=65399&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65399
        // - MeasurementOfTaxon should be blank for child records.
        // - MeasurementOfTaxon should be 'false' if to represent additional metadata. OBSOLETE
        if($measurementOfTaxon == '') {
            if(@$rec['parentMeasurementID']) $measurementOfTaxon = 'child'; //means a child record
            else {
                $measurementOfTaxon = 'false'; // should not go here OBSOLETE
                print_r($rec);
                exit("\nERROR: [TraitGeneric.php] [$this->resource_id]: Should not go here xyz\n");
            }
        }
        if(@$rec['parentMeasurementID']) $measurementOfTaxon = 'child'; //means a child record
        // */
        
        /* moved below, under non-child MoF
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"].$measurementType; //because one catalog no. can have 2 MeasurementOrFact entries. Each for country and habitat.
        */
        
        if($measurementOfTaxon == "child") { //per Jen: https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=63196&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63196
            /* child records: they will have no occurrence ID, MoT can be blank or false */
            $occurrence_id = "";
            $measurementOfTaxon = "";
        }
        else {
            $taxon_id = $rec["taxon_id"];
            $catnum   = $rec["catnum"].$measurementType; //because one catalog no. can have 2 MeasurementOrFact entries. Each for country and habitat.
            $occurrence_id = $this->add_occurrence($taxon_id, $catnum, $rec);
        }

        if($this->is_long_type) $m = new \eol_schema\MeasurementOrFact_specific();
        else                    $m = new \eol_schema\MeasurementOrFact();
        $m->occurrenceID       = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        $m->measurementType    = $measurementType;
        $m->measurementValue   = $value;
        if($val = @$rec['measurementUnit'])         $m->measurementUnit = $val;
        if($val = @$rec['measurementMethod'])       $m->measurementMethod = $val;
        if($val = @$rec['statisticalMethod'])       $m->statisticalMethod = $val;
        if($val = @$rec['measurementRemarks'])      $m->measurementRemarks = RemoveHTMLTagsAPI::remove_html_tags($val);
        if($val = @$rec['parentMeasurementID'])     $m->parentMeasurementID = $val;

        if($measurementOfTaxon == "true") {
            if($val = @$rec['measurementDeterminedDate']) $m->measurementDeterminedDate = $val;
            if($val = @$rec['measurementDeterminedBy']) $m->measurementDeterminedBy = $val;
            if($val = @$rec['source'])                  $m->source = $val;
            if($val = @$rec['associationID'])           $m->associationID = $val;
            if($val = @$rec['measurementAccuracy'])     $m->measurementAccuracy = $val;
            if($val = @$rec['bibliographicCitation'])   $m->bibliographicCitation = RemoveHTMLTagsAPI::remove_html_tags($val);
            if($val = @$rec['contributor'])             $m->contributor = $val;
            if($val = @$rec['referenceID'])             $m->referenceID = $val;
        }
        else {
            if(!$m->parentMeasurementID) {
                print_r($rec);
                print_r($m);
                exit("\nERROR: [TraitGeneric.php] [$this->resource_id] Investigate: no parentID for a mOfTaxon that is not 'true'\n");
            }
        }
        
        // start arbitrary fields here ---------------------------
        //for MAD NatDB
        if($val = @$rec['lifeStage']) $m->lifeStage = $val;
        //for Coraltraits
        if($val = @$rec['SIO_000770']) $m->SIO_000770 = $val;
        if($val = @$rec['STATO_0000035']) $m->STATO_0000035 = $val;
        if($val = @$rec['OBI_0000235']) $m->OBI_0000235 = $val;
        if($val = @$rec['SIO_000769']) $m->SIO_000769 = $val;
        if($val = @$rec['STATO_0000231']) $m->STATO_0000231 = $val;
        // end ---------------------------------------------------
        
        if($val = @$rec['measurementID']) $m->measurementID = $val; //new Aug 22, 2019
        else {
            // $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue')); //3rd param is optional. If blank then it will consider all properties of the extension
            $m->measurementID = Functions::generate_measurementID($m, $this->resource_id); //3rd param is optional. If blank then it will consider all properties of the extension
        }
        
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
        return array('occurrenceID' => $occurrence_id, 'measurementID' => $m->measurementID);
    }
    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        if($val = @$rec['occur']['occurrenceID']) $occurrence_id = $val;
        else                                      $occurrence_id = md5($taxon_id . '_' . $catnum);
        if($this->is_long_type) $o = new \eol_schema\Occurrence_specific();
        else                    $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;

        //below are non-standard assignments, used by partner source files
        if($val = @$rec['occur']['dateCollected'])   $o->eventDate = $val;
        
        //below are standard properties from https://editors.eol.org/other_files/ontology/occurrence_extension.xml
        /* normally I assign these two: see above
        if($val = @$rec['occur']['occurrenceID'])    $o->occurrenceID = $val; 
        if($val = @$rec['occur']['taxonID'])         $o->taxonID = $val;
        */
        if($val = @$rec['occur']['eventID'])             $o->eventID = $val;
        if($val = @$rec['occur']['institutionCode'])     $o->institutionCode = $val;
        if($val = @$rec['occur']['collectionCode'])      $o->collectionCode = $val;
        if($val = @$rec['occur']['catalogNumber'])       $o->catalogNumber = $val;
        if($val = @$rec['occur']['sex'])                 $o->sex = $val;
        if($val = @$rec['occur']['lifeStage'])           $o->lifeStage = $val;
        if($val = @$rec['occur']['reproductiveCondition']) $o->reproductiveCondition = $val;
        if($val = @$rec['occur']['behavior'])            $o->behavior = $val;
        if($val = @$rec['occur']['establishmentMeans'])  $o->establishmentMeans = $val;
        if($val = @$rec['occur']['occurrenceRemarks'])   $o->occurrenceRemarks = RemoveHTMLTagsAPI::remove_html_tags($val);
        if($val = @$rec['occur']['individualCount'])     $o->individualCount = $val;
        if($val = @$rec['occur']['preparations'])        $o->preparations = $val;
        if($val = @$rec['occur']['fieldNotes'])          $o->fieldNotes = RemoveHTMLTagsAPI::remove_html_tags($val);
        if($val = @$rec['occur']['samplingProtocol'])    $o->samplingProtocol = $val;
        if($val = @$rec['occur']['samplingEffort'])      $o->samplingEffort = $val;
        if($val = @$rec['occur']['recordedBy'])          $o->recordedBy = $val;
        if($val = @$rec['occur']['identifiedBy'])        $o->identifiedBy = $val;
        if($val = @$rec['occur']['dateIdentified'])      $o->dateIdentified = $val;
        if($val = @$rec['occur']['eventDate'])           $o->eventDate = $val;
        if($val = @$rec['occur']['modified'])            $o->modified = $val;
        if($val = @$rec['occur']['locality'])            $o->locality = RemoveHTMLTagsAPI::remove_html_tags($val);
        if($val = @$rec['occur']['decimalLatitude'])     $o->decimalLatitude = $val;
        if($val = @$rec['occur']['decimalLongitude'])    $o->decimalLongitude = $val;
        if($val = @$rec['occur']['verbatimLatitude'])    $o->verbatimLatitude = $val;
        if($val = @$rec['occur']['verbatimLongitude'])   $o->verbatimLongitude = $val;
        if($val = @$rec['occur']['verbatimElevation'])   $o->verbatimElevation = $val;
        
        //start adding arbitrary fields in occurrence file --------------------------------------------
        if($val = @$rec['occur']['SampleSize'])     $o->SampleSize = $val;
        if($val = @$rec['occur']['PATO_0000146'])   $o->PATO_0000146 = $val;
        if($val = @$rec['occur']['EO_0007196'])     $o->EO_0007196 = $val;
        //end adding -----------------------------------------------------------------------------------
        
        if(@$rec['occur']['occurrenceID']) {}
        else $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    public function initialize_terms_remapping($expire_seconds = 60*60*24*30)
    {
        /* START DATA-1841 terms remapping */
        $url = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Terms_remapped/DATA_1841_terms_remapped.tsv";
        require_library('connectors/TropicosArchiveAPI');
        $func = new TropicosArchiveAPI(NULL);
        $this->remapped_terms = $func->add_additional_mappings(true, $url, $expire_seconds); //*this is not add_additional_mappings() like how was used normally in Functions().
        debug("\n(TraitGeneric.php) remapped_terms lib: ".count($this->remapped_terms)."\n");
        return $this->remapped_terms;
        /* END DATA-1841 terms remapping */
    }
    public function pre_add_string_types($rec, $value, $measurementType, $measurementOfTaxon)
    {
        // START DATA-1841 terms remapping
        // echo "\nFrom lib: ".count($this->remapped_terms)."\n"; //just for testing
        if($new_uri = @$this->remapped_terms[$measurementType]) $measurementType = $new_uri;
        if($new_uri = @$this->remapped_terms[$value])           $value = $new_uri;
        
        /* ------------------------- start customize ------------------------- */
        /* for: R package harvest: the MAD tool -> https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=64581&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64581 */
        if($value == 'http://www.wikidata.org/entity/Q1420208') $measurementType = 'http://eol.org/schema/terms/TrophicGuild';
        
        /* for WoRMS -> https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=64582&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64582 */
        if($value == 'http://www.wikidata.org/entity/Q45879481') $measurementType = 'http://eol.org/schema/terms/TrophicGuild';
        
        /* for WoRMS -> https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=64588&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64588 */
        if($measurementType == 'http://purl.obolibrary.org/obo/OBA_VT0100005') {
            if(in_array(@$rec['measurementUnit'], array('http://purl.obolibrary.org/obo/UO_0000016', 'http://purl.obolibrary.org/obo/UO_0000015', 'http://purl.obolibrary.org/obo/UO_0000017', 
                                                        'http://purl.obolibrary.org/obo/UO_0000008'))) $measurementType = 'http://purl.obolibrary.org/obo/CMO_0000013';
            if(in_array(@$rec['measurementUnit'], array('http://purl.obolibrary.org/obo/UO_0000009', 
                                                        'http://purl.obolibrary.org/obo/UO_0010038'))) $measurementType = 'http://purl.obolibrary.org/obo/VT_0001259';
        }
        
        /* for WoRMS -> https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=64617&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64617 */
        if($measurementType == 'http://eol.org/schema/terms/EcomorphologicalGuild' && $value == 'http://purl.obolibrary.org/obo/ENVO_01000181') $measurementType = 'http://purl.obolibrary.org/obo/NCIT_C25513';
        if(in_array($value, array('https://www.wikidata.org/entity/Q12806437', 'https://www.wikidata.org/entity/Q170430', 'http://eol.org/schema/terms/subsurfaceDepositFeeder'))) $measurementType = 'http://eol.org/schema/terms/TrophicGuild';
        if($value == 'http://rs.tdwg.org/dwc/terms/measurementRemarks') return false;
        if($measurementType == 'http://rs.tdwg.org/dwc/terms/measurementRemarks') return false;
        if(@$rec['measurementMethod'] == 'inherited from urn:lsid:marinespecies.org:taxname:123082, Echinoidea Leske, 1778') return false;
        
        /* for CoralTraits -> https://eol-jira.bibalex.org/browse/DATA-1793?focusedCommentId=64583&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64583 */
        if($measurementType == 'http://eol.org/schema/terms/Colonial') {
            if($value == 'http://eol.org/schema/terms/yes') {
                $measurementType = 'http://purl.obolibrary.org/obo/NCIT_C25513';
                // $value = 'http://purl.obolibrary.org/obo/ENVO_01000049'; OBSOLETE
                $value = 'http://purl.obolibrary.org/obo/ENVO_01000029'; //per Jen: https://eol-jira.bibalex.org/browse/DATA-1793?focusedCommentId=64850&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64850
            }
            else return false;
        }
        
        /* for CoralTraits -> https://eol-jira.bibalex.org/browse/DATA-1793?focusedCommentId=64587&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64587 */
        if($measurementType == 'http://purl.obolibrary.org/obo/OBA_VT0100005') {
            if(    in_array(@$rec['measurementUnit'], array('http://purl.obolibrary.org/obo/UO_0000081'))) $measurementType = 'http://purl.obolibrary.org/obo/PATO_0001709';
            elseif(in_array(@$rec['measurementUnit'], array('http://purl.obolibrary.org/obo/UO_0000017'))) $measurementType = 'http://eol.org/schema/terms/EggDiameter';
            elseif(in_array(@$rec['measurementUnit'], array('http://purl.obolibrary.org/obo/UO_0000015'))) $measurementType = 'http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#C25285';
        }
        
        /* ------------------------- end customize ------------------------- */
        
        $ret = self::add_string_types($rec, $value, $measurementType, $measurementOfTaxon);
        return $ret;
        // END DATA-1841 terms remapping
    }
    public function given_m_update_mType_mValue($m, $resource_id = false)
    {
        // echo "\nFrom lib: ".count($this->remapped_terms)."\n"; //just for testing
        if($new_uri = @$this->remapped_terms[$m->measurementType]) $m->measurementType = $new_uri;
        if($new_uri = @$this->remapped_terms[$m->measurementValue]) $m->measurementValue = $new_uri;
        
        /* ------------------------- start customize ------------------------- */ //repeated customize section above...
        if($m->measurementValue == 'http://www.wikidata.org/entity/Q1420208') $m->measurementType = 'http://eol.org/schema/terms/TrophicGuild'; //R package
        if($m->measurementValue == 'http://www.wikidata.org/entity/Q45879481') $m->measurementType = 'http://eol.org/schema/terms/TrophicGuild'; //WoRMS
        if($m->measurementType == 'http://purl.obolibrary.org/obo/OBA_VT0100005') { //WoRMS
            if(in_array(@$m->measurementUnit, array('http://purl.obolibrary.org/obo/UO_0000016', 'http://purl.obolibrary.org/obo/UO_0000015', 'http://purl.obolibrary.org/obo/UO_0000017', 
                                                        'http://purl.obolibrary.org/obo/UO_0000008'))) $m->measurementType = 'http://purl.obolibrary.org/obo/CMO_0000013';
            if(in_array(@$m->measurementUnit, array('http://purl.obolibrary.org/obo/UO_0000009', 
                                                        'http://purl.obolibrary.org/obo/UO_0010038'))) $m->measurementType = 'http://purl.obolibrary.org/obo/VT_0001259';
        }
        /* ------------------------- start customize ------------------------- */ //WoRMS
        if($m->measurementType == 'http://eol.org/schema/terms/EcomorphologicalGuild' && $m->measurementValue == 'http://purl.obolibrary.org/obo/ENVO_01000181') $m->measurementType = 'http://purl.obolibrary.org/obo/NCIT_C25513';
        if(in_array($m->measurementValue, array('https://www.wikidata.org/entity/Q12806437', 'https://www.wikidata.org/entity/Q170430', 'http://eol.org/schema/terms/subsurfaceDepositFeeder'))) $m->measurementType = 'http://eol.org/schema/terms/TrophicGuild';
        if($resource_id == 26) {
            if($m->measurementType == 'http://rs.tdwg.org/dwc/terms/measurementRemarks') return false;
            if($m->measurementMethod == 'inherited from urn:lsid:marinespecies.org:taxname:123082, Echinoidea Leske, 1778') return false;
        }
        /* ------------------------- end customize ------------------------- */
        
        return $m;
    }
}
?>