<?php
/*
month:      run date:       execution time:
Feb2010     1Mar2010      
Mar2010     1Apr2010        1.5 hrs
Apr2010     3May2010        1.7 hrs
July2010    4Aug2010                
Aug2010     1Sep2010        5 hrs
Sep2010     7Oct2010        6.8 hrs
Oct2010     3Nov2010        10 hrs
Nov2010     1Dec2010        11 hrs

tables used:
    taxon_concepts
    taxon_concept_names
    page_names
    hierarchy_entries
    agents
    agents_resources
    harvest_events
    names
    ???

Google reference pages:
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html#d4Ecommerce
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDataFeed.html
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceCommonCalculations.html#revenue
*/

$GLOBALS['ENV_DEBUG'] = true;
//$GLOBALS['ENV_NAME'] = "integration";
//$GLOBALS['ENV_NAME'] = "staging";
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = microtime(1);
$temp_time = time_elapsed();            

$mysqli =& $GLOBALS['mysqli_connection'];
require_library('MonthlyGoogleAnalytics');
$run = new MonthlyGoogleAnalytics();

require_once('google_proc.php');

$arr = $run->process_parameters();//month and year parameters
$month = $arr[0]; $year = $arr[1]; $year_month = $year . "_" . $month; //$year_month = "2009_04";        

    //empty the 4 tables for the month
    $run->initialize_tables_4dmonth($year,$month); 

    //save google analytics stats
    $run->save_eol_taxa_google_stats($month,$year); 

    //save partner stats
    $run->save_agent_taxa($year_month); //start2

    //save partner summaries
    $run->save_agent_monthly_summary($year_month);                      

    //save eol-wide summaries
    $run->save_eol_monthly_summary($year,$month);
       
$elapsed_time_sec = microtime(1)-$timestart;
$time_elapsed_sec = time_elapsed() - $temp_time;

echo "\n elapsed time = $elapsed_time_sec sec               ";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " mins   ";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hrs ";
echo "\n";
echo "\n elapsed time = $time_elapsed_sec sec               ";
echo "\n elapsed time = " . $time_elapsed_sec/60 . " mins   ";
echo "\n elapsed time = " . $time_elapsed_sec/60/60 . " hrs ";
    
echo"\n\n Processing done. --end-- \n "; 
?>