<?php
namespace php_active_record;
/* DATA-1754 
natdb	Thursday 2019-01-24 10:37:40 AM	{"measurement_or_fact.tab"         :165791,"occurrence.tab"         :92783,"taxon.tab":2778}
natdb	Monday 2019-01-28 10:56:44 AM	{"measurement_or_fact.tab"         :150997,"occurrence_specific.tab":92783,"taxon.tab":2778}
natdb	Tuesday 2019-01-29 05:00:27 AM	{"measurement_or_fact_specific.tab":127187,"occurrence_specific.tab":94701,"taxon.tab":2778}
natdb	Tuesday 2019-01-29 08:09:05 AM	{"measurement_or_fact_specific.tab":127187,"occurrence_specific.tab":94701,"reference.tab":8,"taxon.tab":2778}
natdb	Tuesday 2019-01-29 05:57:54 PM	{"measurement_or_fact_specific.tab":132656,"occurrence_specific.tab":97870,"reference.tab":8,"taxon.tab":2778} - PATO_0000146 now as 'child measurement'. Before it was as 'occurrence'
natdb	Tuesday 2019-01-29 06:41:50 PM	{"measurement_or_fact_specific.tab":132656,"occurrence_specific.tab":97870,"reference.tab":8,"taxon.tab":2778} - MacMini
natdb	Sunday 2019-02-24 10:35:57 PM	{"measurement_or_fact_specific.tab":132656,"occurrence_specific.tab":97870,"reference.tab":8,"taxon.tab":2778} - eol-archive
natdb	Monday 2019-10-14 04:41:26 AM	{"measurement_or_fact_specific.tab":132656,"occurrence_specific.tab":97870,"reference.tab":8,"taxon.tab":2778,"time_elapsed":{"sec":542.37,"min":9.04,"hr":0.15}} - MacMini
natdb	Monday 2019-10-14 05:42:34 AM	{"measurement_or_fact_specific.tab":132656,"occurrence_specific.tab":97870,"reference.tab":11,"taxon.tab":2778,"time_elapsed":{"sec":477.22,"min":7.95,"hr":0.13}} - MacMini
natdb	Monday 2019-10-14 06:12:34 AM	{"measurement_or_fact_specific.tab":132656,"occurrence_specific.tab":97870,"reference.tab":11,"taxon.tab":2778,"time_elapsed":{"sec":242.49,"min":4.04,"hr":0.07}} - eol-archive
Start DATA-1481 terms remapping
natdb	Monday 2019-12-02 05:51:27 AM	{"measurement_or_fact_specific.tab":98007,"occurrence_specific.tab":97870,"reference.tab":11,"taxon.tab":2778,"time_elapsed":{"sec":257.53,"min":4.29,"hr":0.07}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MADtoolNatDBAPI');
$timestart = time_elapsed();

// $str = "eliboy";
// echo "\n[".substr($str, -3)."]\n";
// $str = substr($str,0,strlen($str)-3);
// echo "\n[$str]\n";
// exit;

/*
$a['eli boy']["child measurement"] = Array
    (
        "http://eol.org/schema/terms/AnnualPrecipitation" => Array
            (
                1300 => Array
                    (
                        "map__.falster.2015_mm_" => 15,
                        "r" => Array
                            (
                                'md' => "studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae",
                                'mr' => "",
                                'mu' => "http://purl.obolibrary.org/obo/UO_0000016",
                                'ds' => ".falster.2015",
                                'ty' => "n"
                            )
                    )
            ),
        "http://eol.org/schema/terms/meanAnnualTemperature" => Array
            (
                6 => Array
                    (
                        "mat__.falster.2015_deg_" => 15,
                        "r" => Array
                            (
                                'md' => "studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae",
                                'mr' => "",
                                'mu' => "http://purl.obolibrary.org/obo/UO_0000027",
                                'ds' => ".falster.2015",
                                'ty' => "n"
                            )
                    )
            )
    );
print_r($a);
$measurements = get_child_measurements($a['eli boy']['child measurement']);
print_r($measurements); exit;
*/

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

$resource_id = "natdb";
$func = new MADtoolNatDBAPI($resource_id);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>
