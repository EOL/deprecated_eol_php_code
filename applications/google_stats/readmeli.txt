fb - count of taxa pages
Select Count(agents_hierarchies_2009_11.hierarchiesID) From agents_hierarchies_2009_11 Where agents_hierarchies_2009_11.agentName = 'FishBase'
--------------------------------------------------------------------
fb - list query
Select distinct
google_analytics_page_statistics_2009_11.taxon_id,
hierarchies_names_2009_11.scientificName,
Sum(google_analytics_page_statistics_2009_11.page_views) AS page_views,
Sum(google_analytics_page_statistics_2009_11.unique_page_views),
sum(time_to_sec(google_analytics_page_statistics_2009_11.time_on_page))
From
agents_hierarchies_2009_11
Inner Join google_analytics_page_statistics_2009_11 ON agents_hierarchies_2009_11.hierarchiesID = google_analytics_page_statistics_2009_11.taxon_id
Inner Join hierarchies_names_2009_11 ON google_analytics_page_statistics_2009_11.taxon_id = hierarchies_names_2009_11.hierarchiesID
Where
agents_hierarchies_2009_11.agentName = 'FishBase' AND
google_analytics_page_statistics_2009_11.taxon_id Is Not Null
Group By
google_analytics_page_statistics_2009_11.taxon_id,
hierarchies_names_2009_11.scientificName
Order By page_views Desc
--------------------------------------------------------------------
fb - that were viewed during the month
Select distinct
google_analytics_page_statistics_2009_11.taxon_id
From agents_hierarchies_2009_11
Inner Join google_analytics_page_statistics_2009_11 ON agents_hierarchies_2009_11.hierarchiesID = google_analytics_page_statistics_2009_11.taxon_id
Where agents_hierarchies_2009_11.agentName = 'FishBase'
--------------------------------------------------------------------
fb - total pv and upv for the month
Select 
Sum(google_analytics_page_statistics_2009_11.unique_page_views),
Sum(google_analytics_page_statistics_2009_11.page_views),
sum(time_to_sec(google_analytics_page_statistics_2009_11.time_on_page))/60/60
From
agents_hierarchies_2009_11
Inner Join google_analytics_page_statistics_2009_11 ON agents_hierarchies_2009_11.hierarchiesID = google_analytics_page_statistics_2009_11.taxon_id
Where
agents_hierarchies_2009_11.agentName = 'FishBase'
--------------------------------------------------------------------
qry list
Select
google_analytics_page_statistics_2009_11.page_views AS page_views,
google_analytics_page_statistics_2009_11.unique_page_views,
google_analytics_page_statistics_2009_11.time_on_page,
google_analytics_page_statistics_2009_11.bounce_rate,
google_analytics_page_statistics_2009_11.percent_exit
From google_analytics_page_statistics_2009_11
Order By page_views Desc
--------------------------------------------------------------------
total eol taxa pages
Select Count(hierarchies_names_2009_11.hierarchiesID) From hierarchies_names_2009_11
--------------------------------------------------------------------
total numbers - pv upv time_on_pages
Select
Sum(google_analytics_page_statistics_2009_11.page_views),
Sum(google_analytics_page_statistics_2009_11.unique_page_views) AS page_views,
sum(time_to_sec(google_analytics_page_statistics_2009_11.time_on_page))/60/60
From google_analytics_page_statistics_2009_11
Order By page_views Desc
--------------------------------------------------------------------
Viewed Taxa Pages
SELECT distinct g.taxon_id FROM eol_statistics.google_analytics_page_statistics_2009_11 g WHERE g.taxon_id>0
--------------------------------------------------------------------