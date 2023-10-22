<?php
namespace php_active_record;
/* DATA-1754 
natdb	Thursday 2019-01-24 10:37:40 AM	{"measurement_or_fact.tab"         :165791,"occurrence.tab"         :92783,"taxon.tab":2778}
natdb	Monday 2019-01-28 10:56:44 AM	{"measurement_or_fact.tab"         :150997,"occurrence_specific.tab":92783,"taxon.tab":2778}
natdb	Tuesday 2019-01-29 05:00:27 AM	{"MoA":127187,"occurrence_specific.tab":94701,"taxon.tab":2778}
natdb	Tuesday 2019-01-29 08:09:05 AM	{"MoA":127187,"occurrence_specific.tab":94701,"reference.tab":8,"taxon.tab":2778}
natdb	Tuesday 2019-01-29 05:57:54 PM	{"MoA":132656,"occurrence_specific.tab":97870,"reference.tab":8,"taxon.tab":2778} - PATO_0000146 now as 'child measurement'. Before it was as 'occurrence'
natdb	Tuesday 2019-01-29 06:41:50 PM	{"MoA":132656,"occurrence_specific.tab":97870,"reference.tab":8,"taxon.tab":2778} - MacMini
natdb	Sunday 2019-02-24 10:35:57 PM	{"MoA":132656,"occurrence_specific.tab":97870,"reference.tab":8,"taxon.tab":2778} - eol-archive
natdb	Monday 2019-10-14 04:41:26 AM	{"MoA":132656,"occurrence_specific.tab":97870,"reference.tab":8,"taxon.tab":2778,"time_elapsed":{"sec":542.37,"min":9.04,"hr":0.15}} - MacMini
natdb	Monday 2019-10-14 05:42:34 AM	{"MoA":132656,"occurrence_specific.tab":97870,"reference.tab":11,"taxon.tab":2778,"time_elapsed":{"sec":477.22,"min":7.95,"hr":0.13}} - MacMini
natdb	Monday 2019-10-14 06:12:34 AM	{"MoA":132656,"occurrence_specific.tab":97870,"reference.tab":11,"taxon.tab":2778,"time_elapsed":{"sec":242.49,"min":4.04,"hr":0.07}} - eol-archive
Start DATA-1481 terms remapping
natdb	Monday 2019-12-02 08:12:33 AM	{"MoA":132656,"occurrence_specific.tab":97870,"reference.tab":11,"taxon.tab":2778,"time_elapsed":{"sec":260.54,"min":4.34,"hr":0.07000000000000001}}
natdb	Wednesday 2020-01-15 09:24:06 AM{"MoA":130356,"occurrence_specific.tab":97870,"reference.tab":11,"taxon.tab":2778,"time_elapsed":{"sec":250.58,"min":4.18,"hr":0.07000000000000001}}
natdb	Monday 2020-01-27 04:12:46 AM	{"MoA":130356,"occurrence_specific.tab":97870,"reference.tab":11,"taxon.tab":2778,"time_elapsed":{"sec":251.15,"min":4.19,"hr":0.07000000000000001}}
natdb	Friday 2020-02-28 02:42:38 AM	{"MoA":130356,"occurrence_specific.tab":97870,"reference.tab":11,"taxon.tab":2778,"time_elapsed":{"sec":250.3,"min":4.17,"hr":0.07000000000000001}}
natdb	Friday 2020-07-17 11:24:08 AM	{"MoA":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":293.77, "min":4.9, "hr":0.08}}

natdb	                Wed 2021-02-24 10:30:06 AM	{"MoA":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":314.37, "min":5.24, "hr":0.09}}
natdb_meta_recoded_1	Wed 2021-02-24 10:31:57 AM	{"MoA":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":111.05, "min":1.85, "hr":0.03}}
natdb_meta_recoded	    Wed 2021-02-24 10:33:41 AM	{"MoA":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":103.38, "min":1.72, "hr":0.03}}

natdb	                Sun 2022-09-18 11:06:37 AM	{"MoA":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":331.83, "min":5.53, "hr":0.09}}
natdb_meta_recoded_1	Sun 2022-09-18 11:08:30 AM	{"MoA":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":112.25, "min":1.87, "hr":0.03}}
natdb_meta_recoded	    Sun 2022-09-18 11:10:16 AM	{"MoA":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":106.05, "min":1.77, "hr":0.03}}

natdb	                Wed 2022-09-28 12:06:09 PM	{"MoA":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":341.96, "min":5.7, "hr":0.09}}
natdb_meta_recoded_1	Wed 2022-09-28 12:08:06 PM	{"MoA":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":116.18, "min":1.94, "hr":0.03}}
natdb_meta_recoded	    Wed 2022-09-28 12:09:50 PM	{"MoA":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":104.3, "min":1.74, "hr":0.03}}
natdb_meta_recoded	    Thu 2022-09-29 10:40:07 AM	{"MoA":212962, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":105.41, "min":1.76, "hr":0.03}}

Below is expected decrease in MoF. After fixing legacy connector MADtoolNatDBAPI.php
natdb	                Mon 2022-10-03 10:57:23 PM	{"MoA":211530, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":331.22, "min":5.52, "hr":0.09}}
natdb_meta_recoded_1	Mon 2022-10-03 10:59:14 PM	{"MoA":211530, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":109.52, "min":1.83, "hr":0.03}}
natdb_meta_recoded	    Mon 2022-10-03 11:01:05 PM	{"MoA":211530, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":108.04, "min":1.8, "hr":0.03}}


Now includes other steps (metadata recoding) after main connector:
php5.6 mad_natdb.php jenkins
    -> generates natdb.tar.gz
php5.6 resource_utility.php jenkins '{"resource_id": "natdb_meta_recoded_1", "task": "metadata_recoding"}'
    -> occurrenceRemarks - moved from Occurrence to MoF's measurementRemarks
    -> generates natdb_meta_recoded_1.tar.gz
php5.6 resource_utility.php jenkins '{"resource_id": "natdb_meta_recoded", "task": "metadata_recoding"}'
    -> lifeStage - moved from MoF to Occurrence's lifeStage
    -> generates natdb_meta_recoded.tar.gz
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MADtoolNatDBAPI');
require_library('connectors/RemoveHTMLTagsAPI');
$timestart = time_elapsed();

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