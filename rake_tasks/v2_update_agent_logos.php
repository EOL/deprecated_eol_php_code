<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];

ini_set('display_errors', true);






$mysqli->begin_transaction();
$cm = new ContentManager();
$result = $mysqli->query("SELECT id, logo_cache_url FROM users WHERE logo_cache_url IS NOT NULL AND logo_cache_url < 1000000");
while($result && $row=$result->fetch_assoc())
{
    $id = $row['id'];
    $logo_cache_url = $row['logo_cache_url'];
    echo "$id $logo_cache_url\n"; continue;
    if($new_logo_cache_url = $cm->grab_file('http://content1.eol.org/content_partners/'.$logo_cache_url.'_large.png', 'partner'))
    {
        echo "$id - $logo_cache_url - $new_logo_cache_url\n";
        $mysqli->update("UPDATE users SET logo_cache_url='$new_logo_cache_url' WHERE id=$id");
    }
}

$result = $mysqli->query("SELECT id, logo_cache_url FROM content_partners WHERE logo_cache_url IS NOT NULL AND logo_cache_url < 1000000");
while($result && $row=$result->fetch_assoc())
{
    $id = $row['id'];
    $logo_cache_url = $row['logo_cache_url'];
    echo "$id $logo_cache_url\n"; continue;
    if($new_logo_cache_url = $cm->grab_file('http://content1.eol.org/content_partners/'.$logo_cache_url.'_large.png', 'partner'))
    {
        echo "$id - $logo_cache_url - $new_logo_cache_url\n";
        $mysqli->update("UPDATE content_partners SET logo_cache_url='$new_logo_cache_url' WHERE id=$id");
    }
}

$result = $mysqli->query("SELECT id, logo_cache_url FROM collections WHERE logo_cache_url IS NOT NULL AND logo_cache_url < 1000000");
while($result && $row=$result->fetch_assoc())
{
    $id = $row['id'];
    $logo_cache_url = $row['logo_cache_url'];
    echo "$id $logo_cache_url\n"; continue;
    if($new_logo_cache_url = $cm->grab_file('http://content1.eol.org/content_partners/'.$logo_cache_url.'_large.png', 'partner'))
    {
        echo "$id - $logo_cache_url - $new_logo_cache_url\n";
        $mysqli->update("UPDATE collections SET logo_cache_url='$new_logo_cache_url' WHERE id=$id");
    }
}


$mysqli->end_transaction();


?>
