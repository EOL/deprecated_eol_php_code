<?php
namespace php_active_record;
/* connector: [26]  
Latest news: We will might abondone this connector as WORMS now is open to moving to the DWC-A resource.

World Register of Marine Species: is is now scheduled as a cron task.

Partner provides two services. First is the service to get their list of taxa and their IDs based on a date range.
The 2nd one is the service to use the taxon ID to get the EOL XML for each taxon.

This is the service to get the list of taxa:
e.g. for the entire month of January 2012
http://www.marinespecies.org/aphia.php?p=eol&action=taxlist&startdate=20120101&enddate=20120131
e.g. or for the entire year of 2011
http://www.marinespecies.org/aphia.php?p=eol&action=taxlist&startdate=20110101&enddate=20111231

This is the service to get the EOL XML for a certain taxon using its ID e.g. 466138: 
http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=466138

I normally archive the past year. So in the connector code you'll see something like:
$urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2007.xml";
$urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2008.xml";
$urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2009.xml";
$urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2010.xml";
$urls[] = "http://dl.dropbox.com/u/7597512/WORMS/2011.xml";
$urls[] = "http://dl.dropbox.com/u/7597512/WORMS/2012.xml";
Archiving these reduces the load from the partner's server. 
I do this because there are times when partner's server can't render if you query
the entire year. So I archive the past year(s) and just query monthly the current year.
*/

define("WORMS_TAXON_API", "http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=");
define("WORMS_ID_LIST_API", "http://www.marinespecies.org/aphia.php?p=eol&action=taxlist");
class WormsAPI
{
    public function __construct()
    {
        $this->TEMP_FILE_PATH         = DOC_ROOT . "/update_resources/connectors/files/WORMS/";
        $this->WORK_LIST              = DOC_ROOT . "/update_resources/connectors/files/WORMS/work_list.txt";
        $this->WORK_IN_PROGRESS_LIST  = DOC_ROOT . "/update_resources/connectors/files/WORMS/work_in_progress_list.txt";
        $this->INITIAL_PROCESS_STATUS = DOC_ROOT . "/update_resources/connectors/files/WORMS/initial_process_status.txt";

        $this->exec_time_in_seconds = 0;
        $this->start_year_for_auto_compiling_of_ids = 2013; 
        /* start_year_for_auto_compiling_of_ids -- Normally this is the current year, if past year is already archived.
        e.g. archived years
        $urls[] = "http://dl.dropbox.com/u/7597512/WORMS/2011.xml";
        $urls[] = "http://dl.dropbox.com/u/7597512/WORMS/2012.xml";
        It is the year where the connector starts doing the on-demand compilation of taxon_id's, that will be used in calling the WORMS webservice.
        e.g. http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=466138

        This means that when Jan 2014 comes, the value should still be 2013. Only when you've archived 2013 already then you can set this to 2014.
        Connector will still run fine if you don't archive 2013 when 2014 comes, but value for this variable should remain as 2013.
        */
    }

    function initialize_text_files()
    {
        $f = fopen($this->WORK_LIST, "w"); fclose($f);
        $f = fopen($this->WORK_IN_PROGRESS_LIST, "w"); fclose($f);
        $f = fopen($this->INITIAL_PROCESS_STATUS, "w"); fclose($f);
        $f = fopen($this->TEMP_FILE_PATH . "bad_ids.txt", "w"); fclose($f);
        //this is not needed but just to have a clean directory
        self::delete_temp_files($this->TEMP_FILE_PATH . "batch_");
        self::delete_temp_files($this->TEMP_FILE_PATH . "temp_worms_batch_");
        self::delete_temp_files($this->TEMP_FILE_PATH . "xmlcontent_");
    }

    function start_process($resource_id, $call_multiple_instance)
    {
        if(!trim(Functions::get_a_task($this->WORK_IN_PROGRESS_LIST)))//don't do this if there are harvesting task(s) in progress
        {
            if(!trim(Functions::get_a_task($this->INITIAL_PROCESS_STATUS)))//don't do this if initial process is still running
            {
                Functions::add_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);
                // step 1: divides the big list of ids into small files
                $ids = self::get_id_list();
                self::divide_text_file(10000, $ids); //debug original value 10000
                Functions::delete_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);//removes a task from task list
            }
        }
        // step 2: Run multiple instances, for WORMS ideally a total of 3
        while(true)
        {
            $task = Functions::get_a_task($this->WORK_LIST);//get task to work on
            if($task)
            {
                echo "\n Process this: $task";
                Functions::delete_a_task($task, $this->WORK_LIST);//remove a task from task list
                Functions::add_a_task($task, $this->WORK_IN_PROGRESS_LIST);
                $task = str_ireplace("\n", "", $task);//remove carriage return got from text file
                if($call_multiple_instance) //call 2 other instances for a total of 3 instances running
                {
                    Functions::run_another_connector_instance($resource_id, 4);
                    $call_multiple_instance = 0;
                }
                self::get_all_taxa($task);
                echo "\n Task $task is done. \n";
                Functions::delete_a_task("$task\n", $this->WORK_IN_PROGRESS_LIST); //remove a task from task list
            }
            else
            {
                echo "\n\n [$task] Work list done --- " . date('Y-m-d h:i:s a', time()) . "\n";
                break;
            }
        }
        if(!$task = trim(Functions::get_a_task($this->WORK_IN_PROGRESS_LIST))) //don't do this if there are task(s) in progress
        {
            // step 3: Combine all XML files. This only runs when all of instances of step 2 are done
            self::combine_all_xmls($resource_id);
            // set to force harvest
            if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml")) $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::force_harvest()->id . " WHERE id=" . $resource_id);
            // delete temp files
            self::delete_temp_files($this->TEMP_FILE_PATH . "batch_", "txt");
            self::delete_temp_files($this->TEMP_FILE_PATH . "temp_worms_" . "batch_", "xml");
        }
        self::save_bad_ids_to_txt();
    }

    public function get_all_taxa($task)
    {
        $filename = $this->TEMP_FILE_PATH . $task . ".txt";
        $READ = fopen($filename, "r");
        $i = 0;
        $temp_resource_path = $this->TEMP_FILE_PATH . "temp_worms_" . $task . ".xml";
        $OUT = fopen($temp_resource_path, "w");
        while(!feof($READ))
        {
            if($line = fgets($READ))
            {
                $i++;
                echo "\n $i. ";
                $line = trim($line);
                $fields = explode("\t", $line);
                $taxon_id = trim($fields[0]);
                if($contents = self::process($taxon_id))
                {
                    echo " -ok- ";
                    fwrite($OUT, $contents);
                }
                else echo " -bad- ";
            }
        }
        echo "\n\n average download per record: [$this->exec_time_in_seconds/$i] = " . $this->exec_time_in_seconds/$i;
        fclose($READ);
        fclose($OUT);
    }

    function process($id)
    {
        $timestart = time_elapsed(); echo "\n start timer";
        $file = WORMS_TAXON_API . $id;
        echo "$file\n";
        if($contents = Functions::get_remote_file($file, DOWNLOAD_WAIT_TIME, 600, 5))
        {
            if(simplexml_load_string($contents))
            {
                $pos1 = stripos($contents, "<taxon>");
                $pos2 = stripos($contents, "</taxon>");
                if($pos1 != "" and $pos2 != "")
                {
                    $contents = trim(substr($contents, $pos1, $pos2 - $pos1 + 8));
                    $elapsed_time_sec = time_elapsed() - $timestart;
                    echo "\n";
                    echo "elapsed time = " . $elapsed_time_sec . " seconds \n";
                    $this->exec_time_in_seconds += $elapsed_time_sec;
                    return $contents;
                }
            }
        }
        @$GLOBALS['WORMS_bad_id'] .= $id . ",";
        return false;
    }

    private function generate_url_list($urls)
    {
        $start_year = $this->start_year_for_auto_compiling_of_ids;
        $current_year = date("Y");
        for ($year = $start_year; $year <= $current_year; $year++)
        {
            if($year == $current_year) $month_limit = date("n");
            else $month_limit = 12;
            for ($month = 1; $month <= $month_limit; $month++)
            {
                $start_date = $year . Functions::format_number_with_leading_zeros($month, 2) . "01";
                $end_date = $year . Functions::format_number_with_leading_zeros($month, 2) . "31";
                $urls[] = WORMS_ID_LIST_API . "&startdate=" . $start_date . "&enddate=" . $end_date;
            }
        }
        return $urls;
    }

    function get_id_list()
    {
        $urls = array();
        // $urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2011_small.xml"; //for debug

        $urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2007.xml";
        $urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2008.xml";
        $urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2009.xml";
        $urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2010.xml";
        $urls[] = "http://dl.dropbox.com/u/7597512/WORMS/2011.xml";
        $urls[] = "http://dl.dropbox.com/u/7597512/WORMS/2012.xml";

        //append year (the current year) and onwards
        $urls = self::generate_url_list($urls);

        /* debug
        $r = array();
        $r[] = $urls[0];
        $urls = $r;
        */

        echo "\n URLs = " . sizeof($urls) . "\n";
        print_r($urls);

        $ids = array();
        $file_ctr = 0;
        foreach($urls as $url)
        {
            echo "\n Processing: $url \n";
            if($xml = Functions::get_hashed_response($url, DOWNLOAD_WAIT_TIME, 240, 5))
            {
                foreach($xml->taxdetail as $taxdetail)
                {
                    $id = @$taxdetail["id"];
                    $ids[] = $id;
                }
            }
            sleep(30); //debug orig 30
        }

        //delete temp XML files
        self::delete_temp_files($this->TEMP_FILE_PATH . "xmlcontent_", "xml");

        $ids = array_unique($ids);
        echo "\n total ids: " . sizeof($ids);
        echo "\n" . sizeof($urls) . " URLs | taxid count = " . sizeof($ids) . "\n";

        /* debug
        $r = array();
        $r[] = $ids[0];
        $r[] = $ids[1];
        $r[] = $ids[2];
        $r[] = $ids[3];
        $r[] = $ids[4];
        $r[] = $ids[5];
        $r[] = $ids[6];
        $r[] = $ids[7];
        $r[] = $ids[8];
        $r[] = $ids[9];
        $ids = $r;
        */
        
        // $ids = array(); $ids[] = 246718;//9182;//243944;
        
        /*debug: to be used when searching for an id
        foreach(array(582008, 582009, 582010) as $id)
        {
            if(in_array($id, $ids)) echo "\n $id found";
            else echo "\n $id not found";
        }
        */

        return $ids;
    }

    function divide_text_file($divisor, $ids)
    {
        $i = 0;
        $file_ctr = 0;
        $str = "";
        foreach($ids as $id)
        {
            $i++;
            $str .= $id . "\n";
            echo "\n $i. " . $id;
            if($i == $divisor)//no. of names per text file
            {
                $file_ctr++;
                $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 3);
                $OUT = fopen($this->TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w");
                fwrite($OUT, $str);
                fclose($OUT);
                $str = "";
                $i = 0;
            }
        }
        //last writes
        if($str)
        {
            $file_ctr++;
            $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 3);
            $OUT = fopen($this->TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w");
            fwrite($OUT, $str);
            fclose($OUT);
        }
        //create work_list
        $str = "";
        FOR($i = 1; $i <= $file_ctr; $i++) $str .= "batch_" . Functions::format_number_with_leading_zeros($i, 3) . "\n";
        $filename = $this->WORK_LIST;
        if($OUT = fopen($filename, "w"))
        {
            fwrite($OUT, $str);
            fclose($OUT);
        }
    }

    function delete_temp_files($file_path, $file_extension = '*')
    {
        foreach (glob($file_path . "*." . $file_extension) as $filename)
        {
           unlink($filename);
        }
    }

    private function save_bad_ids_to_txt()
    {
        $OUT = fopen($this->TEMP_FILE_PATH . "bad_ids.txt", "a");
        fwrite($OUT, "===================" . "\n");
        fwrite($OUT, date("F j, Y, g:i:s a") . "\n");
        fwrite($OUT, @$GLOBALS['WORMS_bad_id'] . "\n");
        fclose($OUT);
    }

    function combine_all_xmls($resource_id)
    {
        debug("\n\n Start compiling all XML...");
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        $OUT = fopen($old_resource_path, "w");
        $str = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $str .= "<response\n";
        $str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
        $str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
        $str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
        $str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
        $str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
        fwrite($OUT, $str);
        $i = 0;
        while(true)
        {
            $i++;
            $i_str = Functions::format_number_with_leading_zeros($i, 3);
            $filename = $this->TEMP_FILE_PATH . "temp_worms_" . "batch_" . $i_str . ".xml";
            if(!is_file($filename))
            {
                echo " -end compiling XML's- ";
                break;
            }
            echo " $i ";
            $READ = fopen($filename, "r");
            $contents = fread($READ, filesize($filename));
            fclose($READ);
            if($contents) fwrite($OUT, $contents);
            else echo "\n no contents $i";
        }
        fwrite($OUT, "</response>");
        fclose($OUT);
        echo "\n All XML compiled\n\n";
    }

}
?>
