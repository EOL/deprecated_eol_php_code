#############################################################
# # RUN AGAINST LIVE SLAVE
#############################################################
# 1. get the current set of scientific and common names
# -------------------------------------------##############
# -------------------------------------------##############
# save the results locally as a CSV file (using the MySQL Query Browser tool, run the query and choose "File | Export Resultset | Export As CSV File")
# e.g.:  C:\\Users\\user\\Desktop\\hierarchies_names.csv
  -- OLD QUERY
	-- SELECT DISTINCT hn.hierarchiesID, hn.normal scientificName, hn.commonNameEN
	-- FROM eolData.hierarchiesNames hn
	
#new


SELECT tcn.taxon_concept_id, n.string
FROM
taxon_concept_names tcn
JOIN names n ON (tcn.name_id=n.id)
JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id)
WHERE
tcn.vern=0
AND tcn.preferred=1
AND tc.supercedure_id=0
AND tc.published=1
GROUP BY tcn.taxon_concept_id
ORDER BY tcn.source_hierarchy_entry_id DESC;
--LIMIT 0,10;

#############################################################
# 2. get the agent hierarchy data
# -------------------------------------------##############
# run this query against the mysql server on wattle.eol.org
# -------------------------------------------##############
# save the results locally as a CSV file (using the MySQL Query Browser tool, run the query and choose "File | Export Resultset | Export As CSV File")
# e.g.:  C:\\Users\\user\\Desktop\\agents_hierarchies.csv

-- OLD!
-- SELECT DISTINCT a.agentName, c2.hierarchiesID
-- FROM eolData.eolAgents a
-- 	INNER JOIN eolData.objectAgents oa 
-- 		ON oa.agentID = a.agentID
-- 		AND oa.relationID=2
-- 	INNER JOIN eolData.dataObjects do 
-- 		ON do.dataObjectID = oa.dataObjectID
-- 	INNER JOIN eolData.concepts2 c2 
-- 		ON c2.namebankID = do.namebankID
-- WHERE a.agentName IN (
-- 	'AmphibiaWeb', 'BioLib.cz', 'Biolib.de', 'Biopix', 'Catalogue of Life', 'FishBase', 
-- 	'Global Biodiversity Information Facility (GBIF)', 'IUCN', 'Micro*scope', 
-- 	'Solanaceae Source', 'Tree of Life web project', 'uBio','AntWeb','ARKive', 'The Nearctic Spider Database','Animal Diversity Web'
-- )


###NEW
SELECT DISTINCT a.full_name, tcn.taxon_concept_id
FROM
agents a
JOIN agents_resources ar ON (a.id=ar.agent_id)
JOIN harvest_events he ON (ar.resource_id=he.resource_id)
JOIN harvest_events_taxa het ON (he.id=het.harvest_event_id)
JOIN taxa t ON (het.taxon_id=t.id)
JOIN taxon_concept_names tcn ON (t.name_id=tcn.name_id)
WHERE a.full_name IN (
	'AmphibiaWeb', 'BioLib.cz', 'Biolib.de', 'Biopix', 'Catalogue of Life', 'FishBase',
	'Global Biodiversity Information Facility (GBIF)', 'IUCN', 'Micro*scope',
	'Solanaceae Source', 'Tree of Life web project', 'uBio','AntWeb','ARKive', 'The Nearctic Spider Database','Animal Diversity Web'
);
--LIMIT 0, 10;


#############################################################
# 3. get the agent hierarchy data (Biodiversity Heritage Library is a special case)
# -------------------------------------------##############
# -------------------------------------------##############
#save the results locally as a CSV file (using the MySQL Query Browser tool, run the query and choose "File | Export Resultset | Export As CSV File")
# e.g.:  C:\\Users\\user\\Desktop\\agents_hierarchies_bhl.csv
-- SELECT DISTINCT 'BHL' agentName, c2.hierarchiesID
-- FROM eolData.pageNames pn
-- 	INNER JOIN eolData.concepts2 c2 
-- 		ON c2.namebankID = pn.namebankID;
		
###NEW

SELECT DISTINCT 'BHL' full_name, tcn.taxon_concept_id
FROM page_names pn
JOIN taxon_concept_names tcn ON (pn.name_id=tcn.name_id)
LIMIT 0, 10;




#############################################################
# local QUERIES
#############################################################
# 4. clear the local copy of scientific and common names
# ---------------------------##################
# run this query against the local mysql server where you are doing the eol_statistics processing
# ---------------------------##################
TRUNCATE TABLE eol_statistics.hierarchies_names

#############################################################
# 5. clear the local copy of agents_hierarchies
# ---------------------------##################
# run this query against the local mysql server where you are doing the eol_statistics processing
# ---------------------------##################
TRUNCATE TABLE eol_statistics.agents_hierarchies

#############################################################
# 6. load the names from the export to the local database
# ---------------------------##################
# run this query against the local mysql server where you are doing the eol_statistics processing
# ---------------------------##################
LOAD DATA LOCAL INFILE '/Users/peter/Documents/EOL/EOLStats2/july2009/hierarchies_names.csv' 
INTO TABLE eol_statistics.hierarchies_names 
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' 
LINES TERMINATED BY '\n';
#####DONE


#############################################################
# 7. load the agents_hierarchies from the export to the local database
# ---------------------------##################
# run this query against the local mysql server where you are doing the eol_statistics processing
# ---------------------------##################
LOAD DATA LOCAL INFILE '/Users/peter/Documents/EOL/EOLStats2/july2009/agents_hierarchies.csv' 
INTO TABLE eol_statistics.agents_hierarchies 
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' 
LINES TERMINATED BY '\n';

#####DONE
#############################################################
# 8. load the BHL agents_hierarchies from the export to the local database
LOAD DATA LOCAL INFILE '/Users/peter/Documents/EOL/EOLStats2/july2009/agents_hierarchies_bhl.csv' 
INTO TABLE eol_statistics.agents_hierarchies 
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' 
LINES TERMINATED BY '\n';

#############################################################
# 9. get some overall taxa counts
# ---------------------------##################
# run this query against the local mysql server where you are doing the eol_statistics processing
# ---------------------------##################
# these are used on the Provider_Statistics workbooks in the "Count of Taxa Pages" cells (currently D4..E4)
SELECT
  (SELECT COUNT(*) FROM eol_statistics.hierarchies_names) all_taxa_count,
  agentName, 
  COUNT(*) agent_taxa_count
FROM eol_statistics.agents_hierarchies
GROUP BY agentName
ORDER BY agentName;

#############################################################
# 10. get the google_analytics - for the overall site
# ---------------------------##################
# run this query against the local mysql server where you are doing the eol_statistics processing
# ---------------------------##################
# these are data for the EOL_Statistics workbook in the "Top 100 Pages" cells (currently A25..J???)
# the complete set of records provides the basis for the counts and sums that appear 
# in the section entitiled "Calculated from the Google Analytics Detail" (currently cells D17..E21)
# after the sums and totals are caculated, change the cells from formulas to values,
# then delete all but the first 100 rows
# the summary numbers are also used on the Provider_Statistics workbooks in the EOL Site section (currently cells E4..E8)
SELECT 
	g.id, g.date_added, 
	g.taxon_id, g.url, 
	hn.scientificName, hn.commonNameEN,
	g.page_views, g.unique_page_views, TIME_TO_SEC(g.time_on_page) time_on_page_seconds, 
	g.bounce_rate, g.percent_exit
FROM eol_statistics.google_analytics_page_statistics g
	LEFT OUTER JOIN eol_statistics.hierarchies_names hn
		ON hn.hierarchiesID = g.taxon_id
WHERE g.date_added > ADDDATE(CURDATE(), -1)
ORDER BY page_views DESC, unique_page_views DESC, time_on_page_seconds DESC;

#############################################################
# 11. get the taxon google_analytics - for the provider specific workbooks
# ---------------------------##################
# run this query against the local mysql server where you are doing the eol_statistics processing
# ---------------------------##################
# these are data for the Provider_Statistics workbooks in the "Top 100 Taxa Pages" cells (currently A18..G???)
# the complete set of records provides the basis for the counts and sums that appear 
# in the section entitiled "Taxa Pages with ??? Content" (currently cells D4..D8 & F4..F8)
# after the sums and totals are caculated, change the cells from formulas to values,
# then delete all but the first 100 rows
SELECT ah.agentName,g.taxon_id, hn.scientificName, hn.commonNameEN,SUM(g.page_views) total_page_views,SUM(g.unique_page_views) total_unique_page_views,SUM(TIME_TO_SEC(g.time_on_page)) total_time_on_page_seconds
 FROM eol_statistics.google_analytics_page_statistics g INNER JOIN eol_statistics.agents_hierarchies ah	ON ah.hierarchiesID = g.taxon_id
 LEFT OUTER JOIN eol_statistics.hierarchies_names hn ON hn.hierarchiesID=g.taxon_id
 WHERE g.date_added > ADDDATE(CURDATE(), -1)
 GROUP BY ah.agentName, g.taxon_id
 ORDER BY ah.agentName, total_page_views DESC, total_unique_page_views DESC, total_time_on_page_seconds DESC;
 INTO OUTFILE '/Users/peter/Documents/EOL/EOLStats2/july2009/site_statistics.txt';
# the INTO OUTFILE clause makes a tab delimited file by default that can be easily copied and pasted into an Excel workbook



############################################################
# 12. Get the count for viewed taxa pages
#
#
SELECT distinct g.taxon_id
FROM eol_statistics.google_analytics_page_statistics g
WHERE g.date_added > ADDDATE(CURDATE(), -1) and g.taxon_id>0;
