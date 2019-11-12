historical:

        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-aug-16-1-2.csv";
        $this->file['preferred synonym']['path'] = "http://localhost/cp/summary data resources/preferredsynonym-aug-16-1-2-3.csv";
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-sept-27.csv";


        $this->file['parent child']['path_habitat'] = "http://localhost/cp/summary data resources/habitat-parent-child.csv"; 
        $this->file['parent child']['path_habitat'] = "http://localhost/cp/summary data resources/habitat-parent-child-6-1.csv";
        $this->file['parent child']['path_habitat'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/c5ff5c62-a2ef-44be-9f59-88cd99bc8af2/download/habitat-parent-child-6-1.csv";


        $this->file['parent child']['path_geoterms'] = "http://localhost/cp/summary data resources/geoterms-parent-child.csv";
        $this->file['parent child']['path_geoterms'] = "http://localhost/cp/summary data resources/geoterms-parent-child-1.csv";
        //these next 2 versions are exactly the same as of Aug 7, 2019
        // geoterms-parent-child-1.csv
        // geoterms-parent-child-feb19.csv
        $this->file['parent child']['path_geoterms'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/e1dcb51b-9a03-4069-b5bf-e18b6bc15798/download/geoterms-parent-child-1.csv";
        $this->file['parent child']['path_geoterms'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/e1dcb51b-9a03-4069-b5bf-e18b6bc15798/download/geoterms-parent-child-feb19.csv";

——————————————————
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
$ mysql -u root -p --local-infile SDR;

DH_lookup
metadata_LSM
metadata_refs
page_ids_FLOPO_0900032
page_ids_Habitat
page_ids_Present
traits_BV
traits_LSM
traits_TS
traits_TSp

select count(*) from DH_lookup;
select count(*) from metadata_LSM;
select count(*) from metadata_refs;
select count(*) from page_ids_FLOPO_0900032;
select count(*) from page_ids_Habitat;
select count(*) from page_ids_Present;
select count(*) from traits_BV;
select count(*) from traits_LSM;
select count(*) from traits_TS;
select count(*) from traits_TSp;

TABLES                  |2019May29 |  |2019Jun13 |
DH_lookup;              |  2237553 |
metadata_LSM;           |  1821929 |  |  1857279 |
metadata_refs;          |   985159 |  |   984177 |
page_ids_FLOPO_0900032; |   189717 |  |   189717 |
page_ids_Habitat;       |   295243 |  |   295368 |
page_ids_Present;       |  1229590 |  |  1229626 |
traits_BV;              |  4689720 |  |  4691339 |
traits_LSM;             |   194249 |  |   194355 |
traits_TS;              |  1458588 |  |  1458588 |
traits_TSp;             |  1033725 |  |  1033725 |



----------------------------------------------------------------------------------------------------------------------------------------
mysql> 
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_1.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_2.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_3.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_4.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_5.txt' into table traits_BV;

load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TS_1.txt' into table traits_TS;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TS_2.txt' into table traits_TS;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TS_3.txt' into table traits_TS;

load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TSp_1.txt' into table traits_TSp;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TSp_2.txt' into table traits_TSp;

load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_LSM_1.txt' into table traits_LSM;

for MacBook:
load data local infile '/Users/eagbayani/Sites/cp/summary_data_resources/MySQL_append_files/traits_BV_1.txt' into table traits_BV;
load data local infile '/Users/eagbayani/Sites/cp/summary_data_resources/MySQL_append_files/traits_BV_2.txt' into table traits_BV;
load data local infile '/Users/eagbayani/Sites/cp/summary_data_resources/MySQL_append_files/traits_BV_3.txt' into table traits_BV;
load data local infile '/Users/eagbayani/Sites/cp/summary_data_resources/MySQL_append_files/traits_BV_4.txt' into table traits_BV;
load data local infile '/Users/eagbayani/Sites/cp/summary_data_resources/MySQL_append_files/traits_BV_5.txt' into table traits_BV;
load data local infile '/Users/eagbayani/Sites/cp/summary_data_resources/MySQL_append_files/traits_TS_1.txt' into table traits_TS;
load data local infile '/Users/eagbayani/Sites/cp/summary_data_resources/MySQL_append_files/traits_TS_2.txt' into table traits_TS;
load data local infile '/Users/eagbayani/Sites/cp/summary_data_resources/MySQL_append_files/traits_TSp_1.txt' into table traits_TSp;
load data local infile '/Users/eagbayani/Sites/cp/summary_data_resources/MySQL_append_files/traits_TSp_2.txt' into table traits_TSp;
load data local infile '/Users/eagbayani/Sites/cp/summary_data_resources/MySQL_append_files/traits_LSM_1.txt' into table traits_LSM;

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

----------------------------------------------------------------------------------------------------------------------------------------
These 3 were not used at all:
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

{this is same file as above: non_parent_methods.zip}
scp basal_values.zip archive:~/temp/.
https://editors.eol.org/other_files/SDR/basal_values.zip

scp parent_basal_values.zip archive:~/temp/.
https://editors.eol.org/other_files/SDR/parent_basal_values.zip

scp lifeStage_statMeth_resource.txt.zip archive:~/temp/.
https://editors.eol.org/other_files/SDR/lifeStage_statMeth_resource.txt.zip

scp taxon_summary.zip archive:~/temp/.
https://editors.eol.org/other_files/SDR/taxon_summary.zip

scp parent_taxon_summary.zip archive:~/temp/.
https://editors.eol.org/other_files/SDR/parent_taxon_summary.zip

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

Below for 2019Aug22 trait export:
[reached L52] => Array
       [10459935] => Array
       [6551609] => Array
       [2195] => Array
       [2366] => Array
       [46451825] => Array

       Getting children of [8814528]...
       Getting children of [46557930]...
       Getting children of [2774383]...
       Getting children of [2634372]...

----------------------------------------------------------------------------------------------------------------------------------------
mysqldump -d -h localhost -u root -p SDR > SDR_structure_only.sql
- dump one database, worked OK

mysql -u root -p --one-database SDR < SDR_structure_only.sql
-> restore one database, worked OK

mysqldump -p --all-databases > all_databases.sql
-> dump all databases
----------------------------------------------------------------------------------------------------------------------------------------
from MacBook, i used afp// then + ip, to connect from macbook to mac mini. then used eli_macbook as user and e173 as pw.
connect to server: afp://
then connect /web/, from Sites/ run ln below:
sudo ln -s /Volumes/web/cp/ cp
----------------------------------------------------------------------------------------------------------------------------------------

BUT THIS WAS NOW AUTOMATED IN generate_page_id_txt_files_MySQL() ... no more manual steps like these:

/*For 2019Aug22 traits version: steps below:
$ mysql -u root -p --local-infile SDR;
mysql> 
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_1.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_2.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_3.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_4.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_BV_5.txt' into table traits_BV;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_LSM_1.txt' into table traits_LSM;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TS_1.txt' into table traits_TS;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TS_2.txt' into table traits_TS;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TS_3.txt' into table traits_TS;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TSp_1.txt' into table traits_TSp;
load data local infile '/Volumes/AKiTiO4/web/cp/summary_data_resources/MySQL_append_files/traits_TSp_2.txt' into table traits_TSp;
*/
----------------------------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------------------------




