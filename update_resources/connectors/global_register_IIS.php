<?php
namespace php_active_record;
/* Global Register of Introduced and Invasive Species : DATA-1838

e.g. Belgium
https://www.gbif.org/dataset/6d9e952f-948c-4483-9807-575348147c7e
https://api.gbif.org/v1/dataset/6d9e952f-948c-4483-9807-575348147c7e/document

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GlobalRegister_IntroducedInvasiveSpecies');
$timestart = time_elapsed();
$cmdline_params['jenkins_or_cron'] = @$argv[1]; //irrelevant here

/* local
$params["dwca_file"]     = "http://127.0.0.1/cp_new/GBIF_dwca/countries/xxx.zip";
*/

// remote
$params["dwca_file"]     = "https://editors.eol.org/other_files/GBIF_DwCA/xxx.zip";

// e.g.
// Belgium -- https://ipt.inbo.be/archive.do?r=unified-checklist
// South Africa -- http://ipt.ala.org.au/archive.do?r=south-africa-griis-gbif


$resource_id = 'griis'; //Global Register of Introduced and Invasive Species
$func = new GlobalRegister_IntroducedInvasiveSpecies($resource_id);
$func->compare_meta_between_datasets(); //a utility to generate report for Jen

// $func->start($params);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
/*
Friday Oct 11
DATA-1839: Brazilian Flora
https://eol-jira.bibalex.org/browse/DATA-1839

Hi Jen, all 4 adjustments you mentioned are now done. [OpenData|https://opendata.eol.org/dataset/brazilian-flora/resource/04e94dff-d997-4e3f-946c-2c4bf5173256]
- all measurementType and measurementValue URIs now fixed (swapped) places for all the records from the speciesprofile data.
- phytogeographicDomain now have mValue URIs accordingly.
Amazônia        https://www.wikidata.org/entity/Q2841453
Caatinga        https://www.wikidata.org/entity/Q375816
Cerrado         https://www.wikidata.org/entity/Q278512
Mata Atlântica  https://www.wikidata.org/entity/Q477047
Pampa           https://www.wikidata.org/entity/Q184382
Pantanal        https://www.wikidata.org/entity/Q157603
- bibliographicCitation now added to all MoF.
- taxa file, removed some of the original columns that might confuse the harvester. 
- taxa file, moved nomenclaturalStatus to taxonRemarks
Thanks



Dear Shyama Narayan Pagad,
s.pagad@auckland.ac.nz
Is there a programatic way to get the URL paths to the DwCA for the 123 datasets here:
e.g. 
Belgium -- https://ipt.inbo.be/archive.do?r=unified-checklist
Great Britain -- http://ipt.ala.org.au/archive.do?r=griis-united_kingdom
South Africa -- http://ipt.ala.org.au/archive.do?r=south-africa-griis-gbif
Thanks,
Eli Agbayani (eol.org)

Dear Shyama Narayan Pagad,
s.pagad@auckland.ac.nz
Why is it that Belgium is not found here:
http://ipt.ala.org.au/
But Belgium is one of the 123 datasets here:
https://www.gbif.org/dataset/search?publishing_org=cdef28b1-db4e-4c58-aa71-3c5238c2d0b5
Thanks,
Eli Agbayani (eol.org)

*/
?>