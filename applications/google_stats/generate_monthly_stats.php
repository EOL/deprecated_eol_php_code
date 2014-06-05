<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('MonthlyGoogleAnalytics');


list($year, $month) = get_month_and_year();
if(!$year || !$month || $year < 2008 || $year > date('Y') || $month < 1 || $month > 12 || ($year == date('Y') && $month > date('m')))
{
    print "\n Invalid parameters! \n e.g. for July 2009 enter: \n\t php generate_monthly_stats.php 7 2009 \n\n";
}else
{
    print "month: $month \n year: $year \n\n";
    
    $run = new MonthlyGoogleAnalytics($year, $month);
    $run->initialize_tables();  //empty the 4 tables for the month
    $passed = $run->save_eol_taxa_google_stats();  //save google analytics stats
    $passed = true;
    if($passed)
    {
        $run->save_agent_taxa();            //save partner stats
        $run->save_agent_monthly_summary(); //save partner summaries
        $run->save_eol_monthly_summary();   //save eol-wide summaries
        // $run->send_email_notification();
    }
}


function get_month_and_year()
{
    global $argv;
    $month = null;
    $year = null;
    if(isset($argv[1])) $month = $argv[1];
    if(isset($argv[2])) $year = $argv[2];
    
    if(!$month && !$year)
    {
        $last_month_date = mktime(0, 0, 0, date("m")-1, 1, date("Y"));
        $month = date('n', $last_month_date);
        $year = date('Y', $last_month_date);
    }
    if(strlen($month) == 1) $month = "0$month";
    return array($year, $month);
}

?>