<?php
namespace php_active_record;
/* connectors which used this are:
 - AfricaTreeDBAPI.php 
 - CITESspeciesAPI.php
*/
class TraitGeneric
{
    function __construct($resource_id, $archive_builder)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
    }
    public function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"].$measurementType; //because one catalog no. can have 2 MeasurementOrFact entries. Each for country and habitat.

        if($measurementOfTaxon == "child") { //per Jen: https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=63196&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63196
            /* child records: they will have no occurrence ID, MoT can be blank or false */
            $occurrence_id = "";
            $measurementOfTaxon = "";
        }
        else $occurrence_id = $this->add_occurrence($taxon_id, $catnum, $rec);

        $m = new \eol_schema\MeasurementOrFact_specific();
        $m->occurrenceID       = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        $m->measurementType    = $measurementType;
        $m->measurementValue   = $value;
        if($val = @$rec['measurementUnit'])         $m->measurementUnit = $val;
        if($val = @$rec['measurementMethod'])       $m->measurementMethod = $val;
        if($val = @$rec['statisticalMethod'])       $m->statisticalMethod = $val;
        if($val = @$rec['measurementRemarks'])      $m->measurementRemarks = $val;
        if($val = @$rec['parentMeasurementID'])     $m->parentMeasurementID = $val;

        if($measurementOfTaxon == "true") {
            if($val = @$rec['measurementDeterminedDate']) $m->measurementDeterminedDate = $val;
            if($val = @$rec['measurementDeterminedBy']) $m->measurementDeterminedBy = $val;
            if($val = @$rec['source'])                  $m->source = $val;
            if($val = @$rec['associationID'])           $m->associationID = $val;
            if($val = @$rec['measurementAccuracy'])     $m->measurementAccuracy = $val;
            if($val = @$rec['bibliographicCitation'])   $m->bibliographicCitation = $val;
            if($val = @$rec['contributor'])             $m->contributor = $val;
            if($val = @$rec['referenceID'])             $m->referenceID = $val;
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
        $o = new \eol_schema\Occurrence_specific();
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
        if($val = @$rec['occur']['occurrenceRemarks'])   $o->occurrenceRemarks = $val;
        if($val = @$rec['occur']['individualCount'])     $o->individualCount = $val;
        if($val = @$rec['occur']['preparations'])        $o->preparations = $val;
        if($val = @$rec['occur']['fieldNotes'])          $o->fieldNotes = $val;
        if($val = @$rec['occur']['samplingProtocol'])    $o->samplingProtocol = $val;
        if($val = @$rec['occur']['samplingEffort'])      $o->samplingEffort = $val;
        if($val = @$rec['occur']['recordedBy'])          $o->recordedBy = $val;
        if($val = @$rec['occur']['identifiedBy'])        $o->identifiedBy = $val;
        if($val = @$rec['occur']['dateIdentified'])      $o->dateIdentified = $val;
        if($val = @$rec['occur']['eventDate'])           $o->eventDate = $val;
        if($val = @$rec['occur']['modified'])            $o->modified = $val;
        if($val = @$rec['occur']['locality'])            $o->locality = $val;
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
    public function initialize_terms_remapping()
    {
        /* START DATA-1841 terms remapping */
        $url = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Terms_remapped/DATA_1841_terms_remapped.tsv";
        require_library('connectors/TropicosArchiveAPI');
        $func = new TropicosArchiveAPI(NULL);
        $this->remapped_terms = $func->add_additional_mappings(true, $url, 60*60*24*30); //*this is not add_additional_mappings() like how was used normally in Functions().
        echo "\nremapped_terms lib: ".count($this->remapped_terms)."\n";
        /* END DATA-1841 terms remapping */
    }
    public function pre_add_string_types($rec, $value, $measurementType, $measurementOfTaxon)
    {
        // START DATA-1841 terms remapping
        if($new_uri = @$this->remapped_terms[$measurementType]) $measurementType = $new_uri;
        if($new_uri = @$this->remapped_terms[$value])           $value = $new_uri;
        $ret = self::add_string_types($rec, $value, $measurementType, $measurementOfTaxon);
        return $ret;
        // END DATA-1841 terms remapping
    }
    public function given_m_update_mType_mValue($m)
    {
        // echo "\nFrom lib: ".count($this->remapped_terms)."\n"; //just for testing
        if($new_uri = @$this->remapped_terms[$m->measurementType]) $m->measurementType = $new_uri;
        if($new_uri = @$this->remapped_terms[$m->measurementValue]) $m->measurementValue = $new_uri;
        return $m;
    }
}
?>