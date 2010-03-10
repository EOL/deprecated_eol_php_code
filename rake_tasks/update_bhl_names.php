<?php

include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];





shell_exec("curl http://www.biodiversitylibrary.org/data/data.zip -o ". LOCAL_ROOT ."temp/data.zip");
shell_exec("unzip ". LOCAL_ROOT ."temp/data.zip -d ". LOCAL_ROOT ."temp/data");


$mysqli->begin_transaction();

$mysqli->delete("DELETE FROM publication_titles");
$mysqli->delete("DELETE FROM title_items");
$mysqli->delete("DELETE FROM item_pages");
$mysqli->delete("DELETE FROM page_names");

insert_titles();
insert_items();
insert_pages();
insert_page_names();

$mysqli->end_transaction();

shell_exec("rm -f ". LOCAL_ROOT ."temp/data/*");
shell_exec("rm -f ". LOCAL_ROOT ."temp/data.zip");








function insert_titles()
{
    global $mysqli;
    
    echo "Starting insert_titles\n";
    shell_exec("iconv -f UTF-16 -t UTF-8 ". LOCAL_ROOT ."temp/data/title.txt > ". LOCAL_ROOT ."temp/data/title_c.txt");
    $OUT = fopen(LOCAL_ROOT."temp/titles.txt", "w+");
    $FILE = fopen(LOCAL_ROOT."temp/data/title_c.txt", "r");
    $i=0;
    while(!feof($FILE))
    {
        $i++;
        if($line = fgets($FILE, 4096))
        {
            if($i==1) continue;
            $line = rtrim($line, "\n\r");
            $data = explode("\t", $line);
            if(count($data) != 12) continue;
            $data[12] = $data[11];
            $data[11] = '';
            foreach($data as &$datum) $datum = $mysqli->escape($datum);
            
            fwrite($OUT, "'". implode("'\t'", $data) ."'\n");
        }
        //if($i>50000) break;
    }
    
    $mysqli->load_data_infile(LOCAL_ROOT."temp/titles.txt", "publication_titles");
    //shell_exec("rm ". LOCAL_ROOT."temp/titles.txt");
}


function insert_items()
{
    global $mysqli;
    
    echo "Starting insert_items\n";
    shell_exec("iconv -f UTF-16 -t UTF-8 ". LOCAL_ROOT ."temp/data/item.txt > ". LOCAL_ROOT ."temp/data/item_c.txt");
    $OUT = fopen(LOCAL_ROOT."temp/items.txt", "w+");
    $FILE = fopen(LOCAL_ROOT."temp/data/item_c.txt", "r");
    $i=0;
    while(!feof($FILE))
    {
        $i++;
        if($line = fgets($FILE, 4096))
        {
            if($i==1) continue;
            $line = rtrim($line, "\n\r");
            $data = explode("\t", $line);
            if(count($data) != 11) continue;
            unset($data[7]);
            unset($data[8]);
            unset($data[9]);
            unset($data[10]);
            foreach($data as &$datum) $datum = $mysqli->escape($datum);
            
            fwrite($OUT, "'". implode("'\t'", $data) ."'\n");
        }
        //if($i>50000) break;
    }
    
    $mysqli->load_data_infile(LOCAL_ROOT."temp/items.txt", "title_items");
    //shell_exec("rm ". LOCAL_ROOT."temp/items.txt");
}

function insert_pages()
{
    global $mysqli;
    
    echo "Starting insert_pages\n";
    $OUT = fopen(LOCAL_ROOT."temp/pages.txt", "w+");
    $FILE = fopen(LOCAL_ROOT."temp/data/page.txt", "r");
    $i=0;
    while(!feof($FILE))
    {
        $i++;
        if($line = fgets($FILE, 4096))
        {
            if($i==1) continue;
            $line = rtrim($line, "\n\r");
            $data = explode("\t", $line);
            if(count($data) != 8) continue;
            $data[8] = $data[7];
            $data[7] = "http://www.biodiversitylibrary.org/page/".$data[0];
            foreach($data as &$datum) $datum = $mysqli->escape($datum);
            
            fwrite($OUT, "'". implode("'\t'", $data) ."'\n");
        }
        //if($i>50000) break;
    }
    
    $mysqli->load_data_infile(LOCAL_ROOT."temp/pages.txt", "item_pages");
    //shell_exec("rm ". LOCAL_ROOT."temp/pages.txt");
}

function insert_page_names()
{
    global $mysqli;
    
    echo "Starting insert_page_names\n";
    $OUT = fopen(LOCAL_ROOT."temp/page_names.txt", "w+");
    $FILE = fopen(LOCAL_ROOT."temp/data/PageName.txt", "r");
    $i=0;
    while(!feof($FILE))
    {
        $i++;
        if($line = fgets($FILE, 4096))
        {
            if($i==1) continue;
            $line = rtrim($line, "\n\r");
            $data = explode("\t", $line);
            if(count($data) != 3) continue;
            $name_id = $data[0];
            $page_id = $data[2];
                
            fwrite($OUT, "$page_id\t$name_id\n");
        }
        //if($i>50000) break;
    }
    
    $mysqli->load_data_infile(LOCAL_ROOT."temp/page_names.txt", "page_names");
    //shell_exec("rm ". LOCAL_ROOT."temp/page_names.txt");
}

?>