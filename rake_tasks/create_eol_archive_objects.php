<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli =& $GLOBALS['db_connection'];
$GLOBALS['ENV_DEBUG'] = true;
$GLOBALS['ENV_DEBUG_TO_FILE'] = false;

require_library('EOLArchiveObjects');
$archive_creator = new EOLArchiveObjects();
$archive_creator->create();

if(is_dir('/opt/downloads'))
{
    copy(DOC_ROOT.'temp/eol_archive_objects.tar.gz', '/opt/downloads/eol_archive_objects.tar.gz');
    $md5sum = md5_file('/opt/downloads/eol_archive_objects.tar.gz');
    if(!($MD5_FILE = fopen('/opt/downloads/eol_archive_objects.md5', 'w+')))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . '/opt/downloads/eol_archive_objects.md5');
      return;
    }
    fwrite($MD5_FILE, $md5sum);
    fclose($MD5_FILE);
}

?>
