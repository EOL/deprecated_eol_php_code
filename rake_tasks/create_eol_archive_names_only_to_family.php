<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli =& $GLOBALS['db_connection'];

require_library('EOLArchiveNamesToFamily');
$archive_creator = new EOLArchiveNamesToFamily();
$archive_creator->create();

if(is_dir('/opt/downloads'))
{
    copy(DOC_ROOT.'temp/eol_names_and_ranks_to_family_archive.tar.gz', '/opt/downloads/eol_names_and_ranks_to_family_archive.tar.gz');
    
    $md5sum = md5_file('/opt/downloads/eol_names_and_ranks_to_family_archive.tar.gz');
    $MD5_FILE = fopen('/opt/downloads/eol_names_and_ranks_to_family_archive.md5', 'w+');
    fwrite($MD5_FILE, $md5sum);
    fclose($MD5_FILE);
}

?>
