<?php
namespace php_active_record;
/*
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// ini_set('memory_limit','7096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

$title      = "Ocean sunfish"; //"Ocean sunfish" en; //"Atlantic cod"; //"Mola mola" es ; //;
$language   = "en"; //"en";
$options    = array('resource_id' => 'wikipedia_revisions', 'expire_seconds' => 60*60*24*10, //10 days cache
                    'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1);
$params = array();
$params['title'] = $title;
$params['language'] = $language;

require_library('connectors/WikipediaRevisionsAPI');
$func = new WikipediaRevisionsAPI($params);

/*
check if revision history already exists:
    if not: create rev history ---> proceed with downloading the page; expires now
    if yes: get the revision history record
        compare the old and new timestamp:
            if timestamps are equal     ---> set $options['expire_seconds'] = false;
            if timestamps are not equal ---> set $options['expire_seconds] = 0;
*/
if($rev_history = $func->get_page_revision_history($params['title'], $params['language'])) {
    echo "\nHas page revision history already.\n";
    if($rev_latest = $func->get_page_latest_revision($params['title'], $params['language'])) {
        echo "\nrev_history"; print_r($rev_history);
        echo "\nrev_latest"; print_r($rev_latest);
        $history_last_edited = $rev_history['timestamp'];
        $latest_last_edited = $rev_latest['timestamp'];
        if($history_last_edited == $latest_last_edited) $expire_seconds = false; //does not expire
        else {
                                                        $expire_seconds = 0;     //expires now
                                                        echo "\nDifferent timestamp.";
        }
    }
    else {
        echo "\nNo wikipedia page for this title and language**.\n"; //Does not go here actually.
        $expire_seconds = "do not proceed";
    }
}
else { //revision history not found; create one
    echo "\nNo page revision history yet.\n";
    if($rev_initial = $func->get_page_latest_revision($params['title'], $params['language'])) {
        $func->save_to_history($rev_initial, $params['title'], $params['language']);
        echo "\nInitial rev history saved."; print_r($rev_initial);
        $expire_seconds = 0; //expires now    
    }
    else {
        echo "\nNo wikipedia page for this title and language~~.\n";
        $expire_seconds = "do not proceed";
    }
}

if($expire_seconds === 0)                   echo "\nExpires now.\n";
elseif($expire_seconds === false)           echo "\nSame timestamp, does not expire.\n";
elseif($expire_seconds == "do not proceed") echo "\nWikipedia not found.\n";
else exit("\nInvestigate: this case is not captured.\n");


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

/* did some research on Wikipedia dumps, but proved to be unusable since it is so big and there is no dump specifically for taxa pages.
wget -c -nv --no-check-certificate https://dumps.wikimedia.org/wikidatawiki/entities/latest-all.json.gz
wget -c -nv --no-check-certificate https://dumps.wikimedia.org/commonswiki/latest/commonswiki-latest-pages-articles.xml.bz2

wget -c http://ftp.ussg.iu.edu/linux/ubuntu-releases/5.10/ubuntu-5.10-install-i386.iso

https://dumps.wikimedia.org/enwikisource/20230801/enwikisource-20230801-pages-articles-multistream.xml.bz2
-------------------------------------------------------------------

https://dumps.wikimedia.org/backup-index.html
2023-08-07 04:24:33 enwiki: Dump complete
    enwiki-20230801-pages-articles-multistream.xml.bz2 20.7 GB
        https://dumps.wikimedia.org/enwiki/20230801/enwiki-20230801-pages-articles-multistream.xml.bz2
    enwiki-20230801-pages-articles-multistream-index.txt.bz2 241.0 MB
        https://dumps.wikimedia.org/enwiki/20230801/enwiki-20230801-pages-articles-multistream-index.txt.bz2

2023-08-03 01:35:32 enwikisource: Dump complete
    enwikisource-20230801-pages-articles-multistream.xml.bz2 3.0 GB
        https://dumps.wikimedia.org/enwikisource/20230801/enwikisource-20230801-pages-articles-multistream.xml.bz2
    enwikisource-20230801-pages-articles-multistream-index.txt.bz2 24.0 MB --- cannot be used, nothing relevant...
    https://dumps.wikimedia.org/enwikisource/20230801/enwikisource-20230801-pages-articles-multistream-index.txt.bz2
            bzip2 -dkf enwikisource-20230801-pages-articles-multistream-index.txt.bz2


============================================ https://dumps.wikimedia.org/enwiki/latest/ --- start
not multistream:
https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles.xml.bz2
https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles.xml.bz2-rss.xml

multistream:
https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles-multistream-index.txt.bz2
https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles-multistream-index.txt.bz2-rss.xml
https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles-multistream-index1.txt-p1p41242.bz2
https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles-multistream-index1.txt-p1p41242.bz2-rss.xml
https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles-multistream-index10.txt-p4045403p5399366.bz2
https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles-multistream-index10.txt-p4045403p5399366.bz2-rss.xml
https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles-multistream-index11.txt-p5399367p6899366.bz2
https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-pages-articles-multistream-index11.txt-p5399367p6899366.bz2-rss.xml
============================================ https://dumps.wikimedia.org/enwiki/latest/ --- end

https://dumps.wikimedia.org/enwiki/20230801/enwiki-20230801-pages-articles-multistream.xml.bz2

bzip2 -dkf commonswiki-latest-pages-articles.xml.bz2
bzip2 -dfk enwiki-20230801-pages-articles-multistream-index.txt.bz2

https://dumps.wikimedia.org/commonswiki/latest/
    commonswiki-latest-pages-articles.xml.bz2          03-Aug-2023 07:14         16156257139
https://dumps.wikimedia.org/enwiki/latest/
    enwiki-latest-pages-articles.xml.bz2               02-Aug-2023 02:34         21168530020
------------------------------------------------------

Special:Export 
https://en.wikipedia.org/w/index.php?title=Special:Export&pages=Atlantic%20cod&offset=1&limit=5&action=submit%27
https://en.wikipedia.org/w/index.php?title=Special:Export&pages=Atlantic%20cod&offset=1&limit=1&action=submit%27

https://en.wikipedia.org/w/index.php?title=Atlantic_cod&action=info
*/
?>