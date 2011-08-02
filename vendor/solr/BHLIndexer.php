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
    
    public function __construct($solr_server = SOLR_SERVER)
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->solr_server = $solr_server;
    }
    
    public function index($optimize = true)
    {
        $this->solr = new SolrAPI($this->solr_server, 'bhl');
        $this->solr->delete_all_documents();
        
        $this->lookup_and_cache_publication_titles();
        
        $start = 0;
        $max_id = 0;
        $limit = 100000;
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM item_pages");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        $batches = ceil(($max_id-$start)/$limit);
        $numbatch = 0;
        // $start = 1300000;
        echo "Start: $start, Max: $max_id, Limit: $limit\n";
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $numbatch++;
            echo "Starting batch $numbatch of $batches\n";
            echo time_elapsed()."\n";
            echo memory_get_usage()."\n";
            unset($this->objects);
            $this->lookup_item_pages($i, $limit);
            $this->lookup_page_names($i, $limit);
            while(list($key, $val) = each($this->objects))
            {
                if(!$val['name_id']) unset($this->objects[$key]);
            }
            // print_r($this->objects);
            if(isset($this->objects)) $this->solr->send_attributes($this->objects);
            
            // exit;
        }
        
        
        if(isset($this->objects)) $this->solr->send_attributes($this->objects);
        $this->solr->commit();
        if($optimize) $this->solr->optimize();
    }
    
    function lookup_item_pages($start, $limit)
    {
        echo "\nquerying item page\n";
        $query = "SELECT ip.id, ip.year, ip.volume, ip.issue, ip.number, ip.title_item_id, ip.prefix FROM item_pages ip WHERE ip.id BETWEEN $start AND ". ($start+$limit);
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $year = $row[1];
            $volume = $row[2];
            $issue = $row[3];
            $number = $row[4];
            $title_item_id = $row[5];
            $prefix = $row[6];
            if($year == '17--?') $year = 1700;
            if($year && preg_match("/^\[?([12][0-9]{3})(\?|\]|-| |$)/", $year, $arr))
            {
                $year = $arr[1];
            }else
            {
                if($year) echo "$year\n";
                $year = 0;
            }
            
            $fields = array();
            $fields['year'] = $year;
            $fields['volume'] = $volume;
            $fields['issue'] = $issue;
            $fields['number'] = $number;
            $fields['prefix'] = $prefix;
            
            if(!@$GLOBALS['title_item_publication_title_details'][$title_item_id]) continue;
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
            
            $this->objects[$id] = $fields;
        }
    }
    
    function lookup_page_names($start, $limit)
    {
        echo "\nquerying page names\n";
        $query = "SELECT pn.item_page_id, pn.name_id FROM page_names pn WHERE pn.item_page_id BETWEEN $start AND ". ($start+$limit);
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $item_page_id = $row[0];
            $name_id = $row[1];
            if(@$this->objects[$item_page_id]) $this->objects[$item_page_id]['name_id'][$name_id] = 1;
        }
    }
    
    function lookup_and_cache_publication_titles()
    {
        echo memory_get_usage()."\n";
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
        echo memory_get_usage()."\n";
    }
}

?>