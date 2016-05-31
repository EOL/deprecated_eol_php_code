<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;



shell_exec("rm -fr ". DOC_ROOT ."temp/data/*");
shell_exec("rm -fr ". DOC_ROOT ."temp/data.zip");

shell_exec("curl http://www.biodiversitylibrary.org/data/data.zip -o ". DOC_ROOT ."temp/data.zip");
shell_exec("unzip ". DOC_ROOT ."temp/data.zip -d ". DOC_ROOT ."temp/data");

insert_titles();
insert_items();
insert_pages();
insert_page_names();

$indexer = new BHLIndexer();
$indexer->index_all_pages();








function insert_titles()
{
    echo "Starting insert_titles\n";
    if(!($OUT = fopen(DOC_ROOT ."temp/titles.txt", "w+")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
      return;
    }
    if(!($FILE = fopen(DOC_ROOT ."temp/data/data/title.txt", "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .DOC_ROOT ."temp/data/data/title.txt");
      return;
    }
    $i=0;
    while(!feof($FILE))
    {
        $i++;
        if($line = fgets($FILE, 4096))
        {
            if($i==1) continue;
            $line = rtrim($line, "\n\r");
            $data = explode("\t", $line);
            $details = array();
            $details['id']           = $GLOBALS['db_connection']->escape($data[0]);
			echo "title id is: "+ $details['id'] + "\n";
            $details['marc_bib_id']  = $GLOBALS['db_connection']->escape($data[1]);
            $details['marc_leader']  = $GLOBALS['db_connection']->escape($data[2]);
            $details['title']        = $GLOBALS['db_connection']->escape($data[3]);
            $details['short_title']  = $GLOBALS['db_connection']->escape($data[4]);
            $details['details']      = $GLOBALS['db_connection']->escape($data[5]);
            $details['call_number']  = $GLOBALS['db_connection']->escape($data[6]);
            $details['start_year']   = $GLOBALS['db_connection']->escape($data[7]);
            $details['end_year']     = $GLOBALS['db_connection']->escape($data[8]);
            $details['language']     = $GLOBALS['db_connection']->escape($data[9]);
            $details['author']       = $GLOBALS['db_connection']->escape($data[10]);
            $details['abbreviation'] = '';
            $details['url']          = $GLOBALS['db_connection']->escape($data[11]);
            
            fwrite($OUT, implode("\t", $details) ."\n");
        }
    }
    
    if(filesize(DOC_ROOT ."temp/titles.txt"))
    {
        $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS publication_titles_tmp LIKE publication_titles");
        $GLOBALS['db_connection']->delete("TRUNCATE TABLE publication_titles_tmp");
        $GLOBALS['db_connection']->load_data_infile(DOC_ROOT ."temp/titles.txt", "publication_titles_tmp", "IGNORE", '', 100000, 500000);
		echo "Load the data in titles swap \n";
        $GLOBALS['db_connection']->swap_tables('publication_titles_tmp', 'publication_titles');
		echo "Load the data in titles \n";
    }
    shell_exec("rm ". DOC_ROOT ."temp/titles.txt");
}


function insert_items()
{
    echo "Starting insert_items\n";
    if(!($OUT = fopen(DOC_ROOT ."temp/items.txt", "w+")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT ."temp/items.txt");
      return;
    }
    if(!($FILE = fopen(DOC_ROOT ."temp/data/data/item.txt", "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT ."temp/data/data/item.txt");
      return;
    }
    $i=0;
    while(!feof($FILE))
    {
        $i++;
        if($line = fgets($FILE, 4096))
        {
            if($i==1) continue;
            $line = rtrim($line, "\n\r");
            $data = explode("\t", $line);
            $details = array();
            $details['id']                      = $GLOBALS['db_connection']->escape($data[0]);
            echo "item id is: "+ $details['id'] + "\n";
            $details['publication_title_id']    = $GLOBALS['db_connection']->escape($data[1]);
            $details['bar_code']                = $GLOBALS['db_connection']->escape($data[3]);
            $details['marc_item_id']            = $GLOBALS['db_connection']->escape($data[4]);
            $details['call_number']             = $GLOBALS['db_connection']->escape($data[5]);
            $details['volume_info']             = $GLOBALS['db_connection']->escape($data[6]);
            $details['url']                     = $GLOBALS['db_connection']->escape($data[7]);
            
            fwrite($OUT, implode("\t", $details) ."\n");
        }
    }
    
    if(filesize(DOC_ROOT ."temp/items.txt"))
    {
        $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS title_items_tmp LIKE title_items");
        $GLOBALS['db_connection']->delete("TRUNCATE TABLE title_items_tmp");
        $GLOBALS['db_connection']->load_data_infile(DOC_ROOT ."temp/items.txt", "title_items_tmp", "IGNORE", '', 100000, 500000);
		echo "Load the data in titles tmp \n";
        $GLOBALS['db_connection']->swap_tables('title_items_tmp', 'title_items');
		echo "Load the data in titles items \n";
    }
    shell_exec("rm ". DOC_ROOT ."temp/items.txt");
}

function insert_pages()
{
    echo "Starting insert_pages\n";
    if(!($OUT = fopen(DOC_ROOT ."temp/pages.txt", "w+")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT ."temp/pages.txt");
      return;
    }
    if(!($FILE = fopen(DOC_ROOT ."temp/data/data/page.txt", "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .DOC_ROOT ."temp/data/data/page.txt");
      return;
    }
    $i=0;
    while(!feof($FILE))
    {
        $i++;
        if($line = fgets($FILE, 4096))
        {
            if($i==1) continue;
            $line = rtrim($line, "\n\r");
            $data = explode("\t", $line);
            $details = array();
            $details['id']              = $GLOBALS['db_connection']->escape($data[0]);
			echo "page id is: "+ $details['id'] + "\n";
            $details['title_item_id']   = $GLOBALS['db_connection']->escape($data[1]);
            $details['year']            = $GLOBALS['db_connection']->escape($data[3]);
            $details['volume']          = $GLOBALS['db_connection']->escape($data[4]);
            $details['issue']           = $GLOBALS['db_connection']->escape($data[5]);
            $details['prefix']          = $GLOBALS['db_connection']->escape($data[6]);
            $details['number']          = $GLOBALS['db_connection']->escape($data[7]);
            $details['url']             = "http://www.biodiversitylibrary.org/page/". $details['id'];
            $details['page_type']       = $GLOBALS['db_connection']->escape($data[8]);
            
            fwrite($OUT, implode("\t", $details) ."\n");
        }
    }
    
    if(filesize(DOC_ROOT ."temp/pages.txt"))
    {
        $GLOBALS['db_connection']->insert("CREATE TABLE IF NOT EXISTS item_pages_tmp LIKE item_pages");
        $GLOBALS['db_connection']->delete("TRUNCATE TABLE item_pages_tmp");
        $GLOBALS['db_connection']->load_data_infile(DOC_ROOT ."temp/pages.txt", "item_pages_tmp", "IGNORE", '', 100000, 500000);
		echo "Load the data in pages tmp \n";
        $GLOBALS['db_connection']->swap_tables('item_pages_tmp', 'item_pages');
		echo "Load the data in pages \n";
    }
    shell_exec("rm ". DOC_ROOT ."temp/pages.txt");
}

function insert_page_names()
{
    echo "Starting insert_page_names\n";
    if(!($OUT = fopen(DOC_ROOT ."temp/page_names.txt", "w+")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .DOC_ROOT ."temp/page_names.txt");
      return;
    }
    if(!($FILE = fopen(DOC_ROOT ."temp/data/data/pagename.txt", "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT ."temp/data/data/pagename.txt");
      return;
    }
    $i=0;
    while(!feof($FILE))
    {
        $i++;
        if($line = fgets($FILE, 4096))
        {
            if($i==1) continue;
            $line = rtrim($line, "\n\r");
            $data = explode("\t", $line);
            $name_id = $data[0];
            $page_id = $data[2];

            if($name_id) fwrite($OUT, "$page_id\t$name_id\n");
        }
    }
    
    if(filesize(DOC_ROOT ."temp/page_names.txt"))
    {
        $GLOBALS['db_connection']->query("DROP TABLE IF EXISTS `page_names_tmp`");
        $GLOBALS['db_connection']->query("CREATE TABLE `page_names_tmp` (
              `item_page_id` int(10) unsigned NOT NULL,
              `name_id` int(10) unsigned NOT NULL,
              PRIMARY KEY (`name_id`,`item_page_id`),
              KEY `item_page_id` (`item_page_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $GLOBALS['db_connection']->load_data_infile(DOC_ROOT ."temp/page_names.txt", "page_names_tmp", "IGNORE", '', 100000, 500000);
        $GLOBALS['db_connection']->swap_tables('page_names_tmp', 'page_names');
    }
    shell_exec("rm ". DOC_ROOT ."temp/page_names.txt");
}

?>
