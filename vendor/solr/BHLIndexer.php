<?php
namespace php_active_record;

class BHLIndexer
{
    // create index publication_title_id on title_items(publication_title_id);
    // create index item_page_id on page_names(item_page_id);

    private $mysqli;
    private $mysqli_slave;
    private $solr;
    private $objects;
    private $solr_server;

    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->solr = new SolrAPI(SOLR_SERVER, 'bhl');
		echo "solr is created here \n";
    }

    public function index_all_pages()
    {
        $limit = 500000;
        $start = $this->mysqli->select_value("SELECT MIN(id) FROM item_pages");
        $max_id = 100000;//$this->mysqli->select_value("SELECT MAX(id) FROM item_pages");
		echo "Min is: " + $start + "\n";
		echo "Max is: " + $max_id + "\n";
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $upper_range = $i + $limit - 1;
            if($upper_range > $max_id) $upper_range = $max_id;
            $item_page_ids = range($i, $upper_range);
            $this->index_item_pages($item_page_ids);
        }
		echo "before commit \n";
        $this->solr->commit_objects_in_file();
		echo "after commit \n";
    }

    public function index_item_pages(&$item_page_ids = array())
    {
        $this->lookup_and_cache_publication_titles();
        if(!$item_page_ids){
        	echo "ERROR: --index item pages-- No item pages ids \n";
        	return;
		}
        $batches = array_chunk($item_page_ids, 10000);
        foreach($batches as $batch)
        {
            $this->index_batch($batch);
        }
    }

    private function index_batch(&$item_page_ids)
    {
        if(!$item_page_ids){
        	echo "ERROR: --index batch-- No item pages ids \n";
        	return;
		}
        unset($this->objects);
        $this->objects = array();
        static $num_batch = 0;
        $num_batch++;
        if($GLOBALS['ENV_DEBUG']) echo "Looking up $num_batch Time: ". time_elapsed()." .. Mem: ". memory_get_usage() ."\n";
        $this->lookup_item_pages($item_page_ids);
        if(isset($this->objects))
        {
        	echo "--index batch-- objects are set \n";
            $lookup_ids = array_keys($this->objects);
            $this->lookup_page_names($lookup_ids);
			echo "after looking page names \n";
        }
        while(list($key, $val) = each($this->objects))
        {
            if(!$val['name_id']){
            	echo "before unsetting the objects \n";
            	unset($this->objects[$key]);
            }
        }

        // print_r($this->objects);
        echo "before delete by id \n";
        $this->solr->delete_by_ids($item_page_ids, false);
		echo "after delete by id \n";
        if(isset($this->objects)){
        	echo "There are objects existing and will be sent to solr \n";
        	$this->solr->send_attributes_in_bulk($this->objects);
        }
        echo "before commit \n";
        $this->solr->commit();
		echo "after commit \n";
    }

    function lookup_item_pages(&$item_page_ids = array())
    {
        $query = "
            SELECT ip.id, ip.year, ip.volume, ip.issue, ip.number, ip.title_item_id, ip.prefix
            FROM item_pages ip
            WHERE ip.id IN (". implode(",", $item_page_ids) .")";
		$debug_count = 0;
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $title_item_id = $row[5];
            $fields = array();
            $fields['year'] = $row[1];
            $fields['volume'] = $row[2];
            $fields['issue'] = $row[3];
            $fields['number'] = $row[4];
            $fields['prefix'] = $row[6];

            if($fields['year'] == '17--?') $fields['year'] = 1700;
            if($fields['year'] && preg_match("/^\[?([12][0-9]{3})(\?|\]|-| |$)/", $fields['year'], $arr))
            {
                $fields['year'] = $arr[1];
            }else
            {
                $fields['year'] = 0;
            }

            if(!@$GLOBALS['title_item_publication_title_details'][$title_item_id])
            {
            	echo "No globals of title item publication title details set \n";
            	continue;
            }
            $fields['publication_id'] = SolrApi::text_filter($GLOBALS['title_item_publication_title_details'][$title_item_id]['id']);
            $fields['title_item_id'] = SolrApi::text_filter($GLOBALS['title_item_publication_title_details'][$title_item_id]['title_item_id']);
            $fields['publication_title'] = SolrApi::text_filter($GLOBALS['title_item_publication_title_details'][$title_item_id]['title']);
            $fields['details'] = SolrApi::text_filter($GLOBALS['title_item_publication_title_details'][$title_item_id]['details']);
            $fields['volume_info'] = SolrApi::text_filter($GLOBALS['title_item_publication_title_details'][$title_item_id]['volume_info']);
            $fields['start_year'] = $GLOBALS['title_item_publication_title_details'][$title_item_id]['start_year'];
            $fields['end_year'] = $GLOBALS['title_item_publication_title_details'][$title_item_id]['end_year'];
            if(!$fields['year']) $fields['year'] = $fields['start_year'];
            if(!$fields['year']) $fields['year'] = 0;
            $fields['name_id'] = array();
			if ($debug_count < 10){
				reset($fields);
				while (list($key, $val) = each($fields)){
					echo "$key => $val, ";
				}
				echo "\n";
				$debug_count++;
			}
            $this->objects[$id] = $fields;
        }
    }

    function lookup_page_names(&$item_page_ids = array())
    {
        echo "\nquerying page names\n";
        $query = "
            SELECT pn.item_page_id, pn.name_id
            FROM page_names pn
            WHERE pn.item_page_id IN (". implode(",", $item_page_ids) .")";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $item_page_id = $row[0];
            $name_id = $row[1];
            if(@$this->objects[$item_page_id]) $this->objects[$item_page_id]['name_id'][$name_id] = 1;
        }
		echo "after setting page names \n";
    }

    function lookup_and_cache_publication_titles()
    {
        if(isset($GLOBALS['title_item_publication_title_details'])) return;
        $GLOBALS['title_item_publication_title_details'] = array();
        $query = "SELECT ti.id, pt.id, pt.title, pt.start_year, pt.end_year, pt.details, ti.volume_info FROM publication_titles pt JOIN title_items ti ON (pt.id=ti.publication_title_id)";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $title_item_id = $row[0];
            $id = $row[1];
            $title = $row[2];
            $start_year = $row[3];
            $end_year = $row[4];
            $details = $row[5];
            $volume_info = $row[6];
            $GLOBALS['title_item_publication_title_details'][$title_item_id] = array(
                'id' => $id, 'title' => $title, 'start_year' => $start_year,
                'end_year' => $end_year, 'details' => $details, 'title_item_id' => $title_item_id,
                'volume_info' => $volume_info);
        }
    }
}

?>
