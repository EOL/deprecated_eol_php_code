<?php
namespace php_active_record;

/*
month:      run date:       execution time:

tables used:
    users
    resources
    taxon_concepts
    hierarchy_entries
    harvest_events_hierarchy_entries
    harvest_events

Google reference pages:
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html#d4Ecommerce
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDataFeed.html
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceCommonCalculations.html#revenue
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$temp_time = time_elapsed();            
$mysqli =& $GLOBALS['mysqli_connection'];

/* can be commented bec. this is ran before hand
require_library('TaxonPageMetrics');
$stats = new TaxonPageMetrics();
$stats->generate_taxon_concept_with_bhl_links_textfile(); //
*/

require_library('MonthlyGoogleAnalytics');
$run = new MonthlyGoogleAnalytics();
require_once('google_proc.php');
$arr = $run->process_parameters();//month and year parameters
$month = $arr[0]; $year = $arr[1]; $year_month = $year . "_" . $month; //$year_month = "2009_04";        

$run->initialize_tables_4dmonth($year,$month);  //empty the 4 tables for the month
$run->save_eol_taxa_google_stats($month,$year); //save google analytics stats
$run->save_agent_taxa($year_month);             //save partner stats
$run->save_agent_monthly_summary($year_month);  //save partner summaries
$run->save_eol_monthly_summary($year,$month);   //save eol-wide summaries

$time_elapsed_sec = time_elapsed() - $temp_time;
echo "\n";
echo "\n elapsed time = " . $time_elapsed_sec/60 . " minutes  ";
echo "\n elapsed time = " . $time_elapsed_sec/60/60 . " hours ";
echo"\n\n Processing done. --end-- \n "; 
?>