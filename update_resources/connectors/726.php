<?php
namespace php_active_record;
/*
execution time: 1 minute
                                            2018
                                            Jul-2
measurementorfact:  20307   21993   21520   21485
taxon:              988     989     969     968
occurrence:                 2839    2839    2838

726	Wednesday 2019-09-18 08:58:15 AM	{"measurement_or_fact.tab":21485,"occurrence.tab":2838,"taxon.tab":968} //used parent-child in MoF
726	Thursday 2019-12-05 09:04:09 AM	    {"measurement_or_fact.tab":21485,"occurrence.tab":2838,"taxon.tab":968,"time_elapsed":false} Mac mini
726	Thursday 2019-12-05 09:09:30 AM	    {"measurement_or_fact.tab":21485,"occurrence.tab":2838,"taxon.tab":968,"time_elapsed":{"sec":17.5,"min":0.29,"hr":0}} eol-archive
removed contributor column in MoF - no change in numbers
726	Thu 2021-06-03 10:03:22 AM	        {"measurement_or_fact.tab":21485, "occurrence.tab":2838, "taxon.tab":968, "time_elapsed":{"sec":11.24, "min":0.19, "hr":0}}
726_meta_recoded	Thu 2021-06-03 10:03{"measurement_or_fact_specific.tab":21485, "occurrence.tab":2838, "taxon.tab":968, "time_elapsed":{"sec":14.56, "min":0.24, "hr":0}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/RotifersTypeSpecimenAPI');
$timestart = time_elapsed();
$resource_id = 726;
$func = new RotifersTypeSpecimenAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>