== HarvestEvents.php
# creates dwc:taxonConceptID triples ONLY in two methods:
create_taxon_relations_graph
	...This is only called by Resource#publish
create_taxa_graph
	...this is never called.  (?)

== Problems:
It looks like the code REQUIRES an "identifier" on entries, which we know we don't always have. Hmmmn.

== Observations:

== The query:

```sql
PREFIX eol: <http://eol.org/schema/> PREFIX dwc: <http://rs.tdwg.org/dwc/terms/> PREFIX eolterms: <http://eol.org/schema/terms/>
SELECT DISTINCT *
	WHERE {
		{
			?trait dwc:occurrenceID ?occurrence .
			?occurrence dwc:taxonID ?taxon .
			?trait eol:measurementOfTaxon eolterms:true .
			GRAPH <http://eol.org/resources/969/mappings> {
				?taxon dwc:taxonConceptID ?page
			}
			OPTIONAL { ?occurrence dwc:lifeStage ?life_stage } .
			OPTIONAL { ?occurrence dwc:sex ?sex }
		}
	}
```
