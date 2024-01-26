<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from move_col_inMoF_2child_inMoF.php] */
class Move_col_inMoF_2child_inMoF_API
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {           
        $tables = $info['harvester']->tables;
        /* For TRY database these MoF columns will become child records in MoF: SampleSize and bodyPart */

        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'build_info_list');
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write_MoF');
        
        if(isset($this->debug)) print_r($this->debug);
    }
    /*================================================================== ENDS HERE =======================================================================*/
    private function process_measurementorfact($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...[$what]\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            // print_r($meta->fields);
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k]; //put "@" as @$tmp[$k] during development
                $k++;
            }
            $rec = array_map('trim', $rec); //print_r($rec); exit("\nstop muna\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 48e062565510ab5a6ed2e92680f56c08_try
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => TRY_Adenogramma glomerata
                [http://eol.org/schema/measurementOfTaxon] => TRUE
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/FLOPO_0900022
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/FLOPO_0900044
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://rs.tdwg.org/dwc/terms/measurementAccuracy] => 
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => literature and database review
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://purl.org/dc/terms/source] => https://www.try-db.org/TryWeb/dp.php
                [http://purl.org/dc/terms/bibliographicCitation] => Kattge, J., S. Daz, S. Lavorel, I. C. Prentice, P. Leadley, G. Bnisch, E. Garnier, M. Westoby, P. B. Reich, I. J. Wright, J. H. C. Cornelissen, C. Violle, S. P. Harrison, P. M. Van Bodegom, M. Reichstein, B. J. Enquist, N. A. Soudzilovskaia, D. D. Ackerly, M. Anand, O. Atkin, M. Bahn, T. R. Baker, D. Baldocchi, R. Bekker, C. C. Blanco, B. Blonder, W. J. Bond, R. Bradstock, D. E. Bunker, F. Casanoves, J. Cavender-Bares, J. Q. Chambers, F. S. Chapin, J. Chave, D. Coomes, W. K. Cornwell, J. M. Craine, B. H. Dobrin, L. Duarte, W. Durka, J. Elser, G. Esser, M. Estiarte, W. F. Fagan, J. Fang, F. Fernndez-Mndez, A. Fidelis, B. Finegan, O. Flores, H. Ford, D. Frank, G. T. Freschet, N. M. Fyllas, R. V Gallagher, W. A. Green, A. G. Gutierrez, T. Hickler, S. Higgins, J. G. Hodgson, A. Jalili, S. Jansen, C. Joly, A. J. Kerkhoff, D. Kirkup, K. Kitajima, M. Kleyer, S. Klotz, J. M. H. Knops, K. Kramer, I. Khn, H. Kurokawa, D. Laughlin, T. D. Lee, M. Leishman, F. Lens, T. Lenz, S. L. Lewis, J. Lloyd, J. Llusi, F. Louault, S. Ma, M. D. Mahecha, P. Manning, T. Massad, B. Medlyn, J. Messier, A. T. Moles, S. C. Mller, K. Nadrowski, S. Naeem, . Niinemets, S. Nllert, A. Nske, R. Ogaya, J. Oleksyn, V. G. Onipchenko, Y. Onoda, J. Ordoez, G. Overbeck, W. A. Ozinga, S. Patio, S. Paula, J. G. Pausas, J. Peuelas, O. L. Phillips, V. Pillar, H. Poorter, L. Poorter, P. Poschlod, A. Prinzing, R. Proulx, A. Rammig, S. Reinsch, B. Reu, L. Sack, B. Salgado-Negret, J. Sardans, S. Shiodera, B. Shipley, A. Siefert, E. Sosinski, J.-F. Soussana, E. Swaine, N. Swenson, K. Thompson, P. Thornton, M. Waldram, E. Weiher, M. White, S. White, S. J. Wright, B. Yguel, S. Zaehle, A. E. Zanne, and C. Wirth. 2011. TRY - a global database of plant traits. Global Change Biology 17:29052935
                [http://eol.org/schema/reference/referenceID] => 
                [http://eol.org/schema/terms/meanlog10] => 
                [http://eol.org/schema/terms/SDlog10] => 
                [http://eol.org/schema/terms/SampleSize] => 
                [http://eol.org/schema/terms/bodyPart] => 
            )*/
            $measurementID = @$rec['http://rs.tdwg.org/dwc/terms/measurementID'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $measurementOfTaxon = strtolower($rec['http://eol.org/schema/measurementOfTaxon']);
            $measurementType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $measurementValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];

            $SampleSize = $rec['http://eol.org/schema/terms/SampleSize'];
            $bodyPart = $rec['http://eol.org/schema/terms/bodyPart'];

            //===========================================================================================================================================================
            if($what == 'build_info_list') {
                if($SampleSize) $this->mID_with_SampleSize[$measurementID] = $SampleSize;
                if($bodyPart) $this->mID_with_bodyPart[$measurementID] = $bodyPart;
            }
            //===========================================================================================================================================================
            if($what == 'write_MoF') {
                /*
                child record in MoF:
                    - doesn't have: occurrenceID | measurementOfTaxon
                    - has parentMeasurementID
                    - has also a unique measurementID, as expected.
                minimum cols on a child record in MoF
                    - measurementID
                    - measurementType
                    - measurementValue
                    - parentMeasurementID
                */
                if($measurementValue = @$this->mID_with_SampleSize[$measurementID]) {
                    $measurementType = "http://eol.org/schema/terms/SampleSize";
                    $parentMeasurementID = $measurementID;
                    self::write_child($measurementType, $measurementValue, $parentMeasurementID);
                }
                if($measurementValue = @$this->mID_with_bodyPart[$measurementID]) {
                    $measurementType = "http://eol.org/schema/terms/bodyPart";
                    $parentMeasurementID = $measurementID;
                    self::write_child($measurementType, $measurementValue, $parentMeasurementID);
                }

                self::write_MoF_rec($rec); // rest of the un-changed carry-over MoF records
            }
            //===========================================================================================================================================================
            // if($i >= 10) break; //debug only
        }
    }
    private function write_MoF_rec($rec)
    {   
        $m = new \eol_schema\MeasurementOrFact_specific();
        $uris = array_keys($rec); // print_r($uris); exit;
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            if(in_array($field, array("meanlog10", "SDlog10", "SampleSize", "bodyPart"))) continue;
            $m->$field = $rec[$uri];
        }

        // /* add measurementID if missing. Fist client TRY dbase has measurementID, anyway we leave this block for future clients.
        if(!isset($m->measurementID)) {
            $m->measurementID = Functions::generate_measurementID($m, $this->resource_id); //3rd param is optional. If blank then it will consider all properties of the extension
        }
        // */

        // /* measurementValue should not be blank
        if(!$m->measurementValue) return;
        // */
        
        if(!isset($this->measurementIDs[$m->measurementID])) {
            $this->measurementIDs[$m->measurementID] = '';
            $this->archive_builder->write_object_to_file($m);
        }
    }
    private function write_child($measurementType, $measurementValue, $parentMeasurementID)
    {
        $m2 = new \eol_schema\MeasurementOrFact_specific();
        $rek = array();
        $rek['http://rs.tdwg.org/dwc/terms/measurementID'] = md5("$measurementType|$measurementValue|$parentMeasurementID");
        $rek['http://rs.tdwg.org/dwc/terms/measurementType'] = $measurementType;
        $rek['http://rs.tdwg.org/dwc/terms/measurementValue'] = $measurementValue;
        $rek['http://eol.org/schema/parentMeasurementID'] = $parentMeasurementID;
        $uris = array_keys($rek);
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            $m2->$field = $rek[$uri];
        }
        if(!isset($this->measurementIDs[$m2->measurementID])) {
            $this->measurementIDs[$m2->measurementID] = '';
            $this->archive_builder->write_object_to_file($m2);
        }
    }
}
?>