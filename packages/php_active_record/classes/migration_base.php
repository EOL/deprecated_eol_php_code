<?php
// namespace php_active_record;
// 
// class MigrationBase
// {
//     public static function do_up($filename)
//     {
//         echo "       == Migrating up: `$filename`\n";
//         
//         $GLOBALS['db_connection']->begin_transaction();
//         
//         $id = $GLOBALS['db_connection']->insert("insert into `php_active_record_migrations` (version) values ('". $GLOBALS['db_connection']->escape($filename) ."')");
//         
//         $sql = static::up();
//         $GLOBALS['db_connection']->multi_query($sql);
//         if($GLOBALS['db_connection']->errno())
//         {
//             echo "      Migration up failed: ". $GLOBALS['db_connection']->error() ."\n\n";
//             $GLOBALS['db_connection']->rollback();
//             exit;
//         }
//         
//         
//         $GLOBALS['db_connection']->update("update `php_active_record_migrations` set completed_at=NOW() where id=$id");
//         
//         $GLOBALS['db_connection']->end_transaction();
//     }
//     
//     public static function do_down($filename)
//     {
//         echo "       == Migrating down: `$filename`\n";
//         $GLOBALS['db_connection']->begin_transaction();
//         
//         $result = $GLOBALS['db_connection']->master("select id from `php_active_record_migrations` where version='". $GLOBALS['db_connection']->escape($filename) ."'");
//         if($result && $row=$result->fetch_assoc()) $id = $row['id'];
//         else
//         {
//             echo "      Migration down failed: no such migration to revert `$filename`\n\n";
//             exit;
//         }
//         
//         $sql = static::down();
//         $GLOBALS['db_connection']->multi_query($sql);
//         if($GLOBALS['db_connection']->errno())
//         {
//             echo "      Migration down failed: ". $GLOBALS['db_connection']->error() ."\n\n";
//             $GLOBALS['db_connection']->rollback();
//             exit;
//         }        
//         
//         $GLOBALS['db_connection']->update("delete from `php_active_record_migrations` where id=$id");
//         
//         $GLOBALS['db_connection']->end_transaction();
//     }
//     
//     public static function called_class()
//     {
//         $called_class = get_called_class();
//         if(preg_match("/\\\([^\\\]*)$/", $called_class, $arr)) $called_class = $arr[1];
//         return $called_class;
//     }
// }

?>