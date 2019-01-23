<?php
namespace php_active_record;
/* DATA-1754 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MADtoolNatDBAPI');
$timestart = time_elapsed();
/*
$a['acer_pensylvanicum'] = Array(
            'MeasurementOfTaxon=true' => Array(
                    'http://purl.obolibrary.org/obo/FLOPO_0008548' => Array(
                            'http://purl.obolibrary.org/obo/PATO_0001731' => Array(
                                    'pft_da_.falster.2015__' => 30,
                                    'm' => "studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae"
                                )
                        ),
                    'http://purl.obolibrary.org/obo/TO_0000207' => Array(
                            "7.81" => Array(
                                    'h.t__.falster.2015_m_' => 2,
                                    'm' => "studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae"
                                ),
                            "4.8" => Array(
                                    'h.t__.falster.2015_m_' => 2,
                                    'm' => "studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae"
                                )
                        )
                ),
            'occurrence' => Array(
                    'http://rs.tdwg.org/dwc/terms/fieldNotes' => Array(
                            'field wild' => Array(
                                    'growingcondition_fw_.falster.2015__' => 30,
                                    'm' => "studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae"
                                )
                        )
                ),
            
            "child measurement" => Array(
                    'http://eol.org/schema/terms/AnnualPrecipitation' => Array(
                            '1300' => Array(
                                    'map__.falster.2015_mm_' => 30,
                                    'm' => 'studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae'
                                )
                        ),
                    'http://eol.org/schema/terms/meanAnnualTemperature' => Array(
                            6 => Array(
                                    'mat__.falster.2015_deg_' => 30,
                                    'm' => 'studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae'
                                )
                        )
                )
        );
print_r($a);
foreach($a as $species => $rec1) {
    echo "\n$species\n";
    foreach($rec1 as $record_type => $rec2) {
        echo "\n --- $record_type\n";
        foreach($rec2 as $mType => $rec3) {
            echo "\n ------ $mType\n";
            // print_r($rec3);
            foreach($rec3 as $mVal => $rec4) {
                echo "\n --------- $mVal\n";
                // print_r($rec4);
                $keys = array_keys($rec4);
                // print_r($keys);
                $tmp = $keys[0];
                $metadata = $rec4['m'];
                $samplesize = $rec4[$keys[0]];
                echo "\n - tmp = [$tmp]\n - metadata = [$metadata]\n - samplesize = [$samplesize]\n";
            }
        }
    }
}

foreach($a['acer_pensylvanicum']['child measurement'] as $mType => $rec3) {
    echo "\n ------ $mType\n";
    // print_r($rec3);
    foreach($rec3 as $mVal => $rec4) {
        echo "\n --------- $mVal\n";
        // print_r($rec4);
        $keys = array_keys($rec4);
        // print_r($keys);
        $tmp = $keys[0];
        $metadata = $rec4['m'];
        $samplesize = $rec4[$keys[0]];
        echo "\n - tmp = [$tmp]\n - metadata = [$metadata]\n - samplesize = [$samplesize]\n";
    }
}
exit("\n");
*/

$resource_id = "mad_natdb";
$func = new MADtoolNatDBAPI($resource_id);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
