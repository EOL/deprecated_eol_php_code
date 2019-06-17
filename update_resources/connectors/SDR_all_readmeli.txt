load a table from an .sql dump:
mysql -uroot -p DatabaseName < path\TableName.sql

e.g. sample only, not used actually
mysql -uroot -p SDR < old_DH_copy.sql
-> worked OK
---------------------------------------------------------------------------------------------------------------------------------------- when doing manually:
$ mysql -u root -p --local-infile SDR;

copy table structure only:
mysql> CREATE TABLE new_table LIKE old_table;

to load from txt file:
mysql> load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_1.txt' into table traits;
----------------------------------------------------------------------------------------------------------------------------------------
mysql> 
show tables; 
show warnings; 
show tables;
-> worked OK, even with multi-line entries. Just a test that was a success.
----------------------------------------------------------------------------------------------------------------------------------------
mysql> 
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_1.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_2.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_3.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_4.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_5.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TS_1.txt' into table traits_TS;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TS_2.txt' into table traits_TS;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TSp_1.txt' into table traits_TSp;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TSp_2.txt' into table traits_TSp;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_LSM_1.txt' into table traits_LSM;
-> worked OK

OBSOLETE below: traits has now been subdivided...
mysql> 
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_1.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_2.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_3.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_4.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_5.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_6.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_7.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_8.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_9.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_10.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_11.txt' into table traits;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_12.txt' into table traits;
----------------------------------------------------------------------------------------------------------------------------------------
mysql> 
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/metadata_refs_1.txt' into table metadata_refs;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/metadata_refs_2.txt' into table metadata_refs;
----------------------------------------------------------------------------------------------------------------------------------------
this was manually done for now: Jun 9, 2019 - for ALL TRAIT EXPORT
-- for parent basal values process:
Array
(
    [0] => http://eol.org/schema/terms/Present
    [1] => http://eol.org/schema/terms/Habitat
    [2] => http://purl.obolibrary.org/obo/FLOPO_0900032
)

mysql> 
INSERT INTO page_ids_Present SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Present'
  Query OK, 1229590 rows affected (21 min 8.09 sec)
  Records: 1229590  Duplicates: 0  Warnings: 0
INSERT INTO page_ids_Habitat SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Habitat';
INSERT INTO page_ids_FLOPO_0900032 SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://purl.obolibrary.org/obo/FLOPO_0900032';



INSERT INTO page_ids_Present_withRank SELECT DISTINCT t.page_id, d.taxonRank from SDR.traits_BV t JOIN DWH.old_DH d ON t.page_id = d.EOLid WHERE t.predicate = 'http://eol.org/schema/terms/Present';
Query OK, 666338 rows affected (5 hours 44 min 15.93 sec)
Records: 666338  Duplicates: 0  Warnings: 0

These two haven't been run yet:
SELECT DISTINCT t.page_id, d.taxonRank from SDR.traits_BV t JOIN DWH.old_DH d ON t.page_id = d.EOLid WHERE t.predicate = 'http://eol.org/schema/terms/Habitat';
SELECT DISTINCT t.page_id, d.taxonRank from SDR.traits_BV t JOIN DWH.old_DH d ON t.page_id = d.EOLid WHERE t.predicate = 'http://purl.obolibrary.org/obo/FLOPO_0900032';
----------------------------------------------------------------------------------------------------------------------------------------
/Volumes/AKiTiO4/web/cp/summary_data_resources/z submitted/2019 06 09/
scp non_parent_methods.zip archive:~/temp/.
https://editors.eol.org/eol_php_code/applications/content_server/resources/non_parent_methods.zip

scp Carnivora_parent_methods.zip archive:~/temp/.
https://editors.eol.org/eol_php_code/applications/content_server/resources/Carnivora_parent_methods.zip

----------------------------------------------------------------------------------------------------------------------------------------
2634370
  2634372
    2639124
    2908256
      10459935
      42430800

[reached L45] => Array
(
    [2908256] => Array
    [2913056] => Array
)
[reached L32] => Array
         [1] => Array
         [2908256] => Array
         [2910700] => Array
         [2913056] => Array
         [8814528] => Array
         [2774383] => Array
----------------------------------------------------------------------------------------------------------------------------------------
mysqldump -d -h localhost -u root -p SDR > SDR_structure_only.sql
- worked OK
----------------------------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------------------------




