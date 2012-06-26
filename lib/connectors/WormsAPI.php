<?php
namespace php_active_record;
/* connector: [26]  */

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
                self::divide_text_file(100000, $ids); //debug original value 10000
                Functions::delete_a_task("Initial process start", $this->INITIAL_PROCESS_STATUS);//removes a task from task list
            }
        }
        // step 2: Run multiple instances, for WORMS ideally a total of 3
        while(true)
        {
            $task = Functions::get_a_task($this->WORK_LIST);//get task to work on
            if($task)
            {
                print "\n Process this: $task";
                Functions::delete_a_task($task, $this->WORK_LIST);//remove a task from task list
                Functions::add_a_task($task, $this->WORK_IN_PROGRESS_LIST);
                $task = str_ireplace("\n", "", $task);//remove carriage return got from text file
                
                if($call_multiple_instance) //call 2 other instances for a total of 3 instances running
                {
                    Functions::run_another_connector_instance($resource_id, 2);
                    $call_multiple_instance = 0;
                }
                
                self::get_all_taxa($task);
                print "\n Task $task is done. \n";
                Functions::delete_a_task("$task\n", $this->WORK_IN_PROGRESS_LIST); //remove a task from task list
            }
            else
            {
                print "\n\n [$task] Work list done --- " . date('Y-m-d h:i:s a', time()) . "\n";
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
                print "\n $i. ";
                $line = trim($line);
                $fields = explode("\t", $line);
                $taxon_id = trim($fields[0]);
                if($contents = self::process($taxon_id))
                {
                    print " -ok- ";
                    fwrite($OUT, $contents);
                }
                else print " -bad- ";
            }
        }
        fclose($READ);
        fclose($OUT);
    }

    function process($id)
    {
        $file = WORMS_TAXON_API . $id;
        echo "$file\n";
        if($contents = Functions::get_remote_file($file))
        {
            if($xml = simplexml_load_string($contents))
            {
                $pos1 = stripos($contents,"<taxon>");
                $pos2 = stripos($contents,"</taxon>");
                if($pos1 != "" and $pos2 != "")
                {
                    $contents = trim(substr($contents, $pos1, $pos2 - $pos1 + 8));
                    return $contents;
                }
            }
        }
        @$GLOBALS['WORMS_bad_id'] .= $id . ",";
        return false;
    }
    
    private function generate_url_list($urls)
    {
        $start_year = 2012;
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
        print_r($urls);
        return $urls;
    } 

    function get_id_list()
    {
        $urls = array();
        //$urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2011_small.xml"; //for debug

        $urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2007.xml";
        $urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2008.xml";
        $urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2009.xml";
        $urls[] = DOC_ROOT . "/update_resources/connectors/files/WORMS/2010.xml";
        $urls[] = "http://dl.dropbox.com/u/7597512/WORMS/2011.xml";

        //append year 2012 and onwards
        $urls = self::generate_url_list($urls);

        /* debug
        $r = array();
        $r[] = $urls[0];
        $urls = $r;
        */
        
        print "\n URLs = " . sizeof($urls) . "\n";
        $ids = array();
        $file_ctr = 0;
        foreach($urls as $url)
        {
            print "\n Processing: $url \n";
            //save file locally first as it is too big to read remotely
            $content = "";
            if($file_handle = fopen($url, "r"))
            {
                while (!feof($file_handle)) $content .= fgets($file_handle);
                fclose($file_handle);
                $file_ctr++;
                $file_ctr_str = Functions::format_number_with_leading_zeros($file_ctr, 3);
                $OUT = fopen($this->TEMP_FILE_PATH . "xmlcontent_" . $file_ctr_str . ".xml", "w");
                fwrite($OUT, $content);
                fclose($OUT);

                //start read locally and get the IDs
                $url = $this->TEMP_FILE_PATH . "xmlcontent_" . $file_ctr_str . ".xml";
                if($xml = Functions::get_hashed_response($url))
                {
                    foreach($xml->taxdetail as $taxdetail)
                    {
                        $id = @$taxdetail["id"];
                        $ids[] = $id;
                    }
                }
                sleep(30); //debug orig 30
            }
            else print "\n -- not being able to process \n";
        }

        //delete temp XML files
        self::delete_temp_files($this->TEMP_FILE_PATH . "xmlcontent_", "xml");

        $ids = array_unique($ids);
        print "\n total ids: " . sizeof($ids);
        print "\n" . sizeof($urls) . " URLs | taxid count = " . sizeof($ids) . "\n";

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

        /*debug: to be used when searching for an id
        foreach(array(582008, 582009, 582010) as $id)
        {
            if(in_array($id, $ids)) print "\n $id found";
            else print "\n $id not found";
        }
        exit;
        */
        
        return $ids;
    }

    function divide_text_file($divisor, $ids)
    {
        $i = 0;
        $file_ctr = 0;
        $str = "";
        print "\n";
        foreach($ids as $id)
        {
            $i++;
            $str .= $id . "\n";
            print "$i. " . $id . "\n";
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
        print "\n\n Start compiling all XML...\n";
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
                print " -end compiling XML's- ";
                break;
            }
            print " $i ";
            $READ = fopen($filename, "r");
            $contents = fread($READ, filesize($filename));
            fclose($READ);
            if($contents) fwrite($OUT, $contents);
            else print "\n no contents $i";
        }
        fwrite($OUT, "</response>");
        fclose($OUT);
        print"\n All XML compiled\n\n";
    }
}
?>
