<?php
namespace php_active_record;
/* DATA-1903: iNaturalist image connector from GBIF export
multimedia.txt - sample entry --- wc -l => 62,617,827 multimedia.txt
gbifID	type	format	identifier	references	title	description	source	audience	created	creator	contributor	publisher	licenserightsHolder
891018910	StillImage	image/jpeg	https://inaturalist-open-data.s3.amazonaws.com/photos/2735077/original.jpg	https://www.inaturalist.org/photos/2735077					2004-09-19T08:11:05-07:00	Kim, Hyun-tae		iNaturalist	http://creativecommons.org/licenses/by/4.0/	Kim, Hyun-tae
891018910	StillImage	image/jpeg	https://inaturalist-open-data.s3.amazonaws.com/photos/2735072/original.jpg	https://www.inaturalist.org/photos/2735072					2004-09-19T07:46:12-07:00	Kim, Hyun-tae		iNaturalist	http://creativecommons.org/licenses/by/4.0/	Kim, Hyun-tae
891018910	StillImage	image/jpeg	https://inaturalist-open-data.s3.amazonaws.com/photos/2735074/original.jpg	https://www.inaturalist.org/photos/2735074					2004-09-19T07:50:32-07:00	Kim, Hyun-tae		iNaturalist	http://creativecommons.org/licenses/by/4.0/	Kim, Hyun-tae
891018910	StillImage	image/jpeg	https://inaturalist-open-data.s3.amazonaws.com/photos/2735075/original.jpg	https://www.inaturalist.org/photos/2735075					2004-09-19T07:51:52-07:00	Kim, Hyun-tae		iNaturalist	http://creativecommons.org/licenses/by/4.0/	Kim, Hyun-tae
3499747367	StillImage	image/jpeg	https://inaturalist-open-data.s3.amazonaws.com/photos/179775723/original.jpg	https://www.inaturalist.org/photos/179775723					2021-06-14T11:39:01-07:00	chipkrilowicz		iNaturalist	http://creativecommons.org/licenses/by-nc/4.0/	chipkrilowicz
3499747372	StillImage	image/jpeg	https://inaturalist-open-data.s3.amazonaws.com/photos/179783113/original.jpg	https://www.inaturalist.org/photos/179783113					2022-02-17T14:18:40-08:00	pcopping_ecp		iNaturalist	http://creativecommons.org/licenses/by-nc/4.0/	pcopping_ecp
3499747377	StillImage	image/jpeg	https://inaturalist-open-data.s3.amazonaws.com/photos/179787931/original.jpg	https://www.inaturalist.org/photos/179787931					2020-11-26T08:49:23-08:00	rook70031		iNaturalist	http://creativecommons.org/licenses/by-nc/4.0/	rook70031
3499747382	StillImage	image/jpeg	https://inaturalist-open-data.s3.amazonaws.com/photos/179790805/original.jpg	https://www.inaturalist.org/photos/179790805					2015-04-19T09:22:52-07:00	rook70031		iNaturalist	http://creativecommons.org/licenses/by-nc/4.0/	rook70031

occurrence.txt - sample entry --- wc -l => 37,808,318 occurrence.txt
gbifID	abstract	accessRights	accrualMethod	accrualPeriodicity	accrualPolicy	alternative	audience	available	bibliographicCitation	conformsTo	contributor	coverage	created	creator	date	dateAccepted	dateCopyrighted	dateSubmitted	description	educationLevel	extent	format	hasFormat	hasPart	hasVersion	identifier	instructionalMethod	isFormatOf	isPartOf	isReferencedBy	isReplacedBy	isRequiredBy	isVersionOf	issued	language	license	mediator	medium	modified	provenance	publisher	references	relatioreplaces	requires	rights	rightsHolder	source	spatial	subject	tableOfContents	temporal	title	type	valid	institutionID	collectionID	datasetID	institutionCode	collectionCode	datasetName	ownerInstitutionCode	basisOfRecord	informationWithheld	dataGeneralizations	dynamicProperties	occurrenceID	catalogNumber	recordNumber	recordedBy	recordedByID	individualCount	organismQuantity	organismQuantityType	sex	lifeStage	reproductiveCondition	behavior	establishmentMeans	degreeOfEstablishment	pathway	georeferenceVerificationStatus	occurrenceStatus	preparations	disposition	associatedOccurrences	associatedReferences	associatedSequences	associatedTaxa	otherCatalogNumbers	occurrenceRemarks	organismID	organismName	organismScope	associatedOrganisms	previousIdentifications	organismRemarks	materialSampleID	eventID	parentEventID	fieldNumber	eventDate	eventTime	startDayOfYear	endDayOfYear	year	month	day	verbatimEventDate	habitatsamplingProtocol	sampleSizeValue	sampleSizeUnit	samplingEffort	fieldNotes	eventRemarks	locationID	higherGeographyID	higherGeography	continent	waterBody	islandGroup	island	countryCode	stateProvince	county	municipality	locality	verbatimLocality	verbatimElevation	verticalDatum	verbatimDepth	minimumDistanceAboveSurfaceInMeters	maximumDistanceAboveSurfaceInMeters	locationAccordingTo	locationRemarksdecimalLatitude	decimalLongitude	coordinateUncertaintyInMeters	coordinatePrecision	pointRadiusSpatialFit	verbatimCoordinateSystem	verbatimSRS	footprintWKT	footprintSRS	footprintSpatialFit	georeferencedBy	georeferencedDate	georeferenceProtocol	georeferenceSources	georeferenceRemarks	geologicalContextID	earliestEonOrLowestEonothem	latestEonOrHighestEonothem	earliestEraOrLowestErathem	latestEraOrHighestErathem	earliestPeriodOrLowestSystem	latestPeriodOrHighestSystem	earliestEpochOrLowestSeries	latestEpochOrHighestSeries	earliestAgeOrLowestStaglatestAgeOrHighestStage	lowestBiostratigraphicZone	highestBiostratigraphicZone	lithostratigraphicTerms	group	formation	member	bed	identificationID	verbatimIdentification	identificationQualifier	typeStatus	identifiedBy	identifiedByID	dateIdentified	identificationReferences	identificationVerificationStatus	identificationRemarks	taxonID	scientificNameID	acceptedNameUsageID	parentNameUsageID	originalNameUsageID	nameAccordingToID	namePublishedInID	taxonConceptID	scientificName	acceptedNameUsage	parentNameUsage	originalNameUsage	nameAccordingTonamePublishedIn	namePublishedInYear	higherClassification	kingdom	phylum	class	order	family	subfamily	genus	genericName	subgenus	infragenericEpithet	specificEpithet	infraspecificEpithet	cultivarEpithet	taxonRank	verbatimTaxonRank	vernacularName	nomenclaturalCode	taxonomicStatus	nomenclaturalStatus	taxonRemarks	datasetKey	publishingCountry	lastInterpreted	elevation	elevationAccuracy	depth	depthAccuracy	distanceAboveSurface	distanceAboveSurfaceAccuracy	issue	mediaType	hasCoordinate	hasGeospatialIssues	taxonKey	acceptedTaxonKey	kingdomKey	phylumKey	classKey	orderKey	familyKey	genusKey	subgenusKey	speciesKey	species	acceptedScientificName	verbatimScientificName	typifiedName	protocol	lastParsed	lastCrawled	repatriated	relativeOrganismQuantity	level0Gid	level0Name	level1Gid	level1Name	level2Gid	level2Name	level3Gid	level3Name	iucnRedListCategory
891078427																			71878										CC0_1_0			2016-02-15T16:12:45Z		iNaturalist.org	https://www.inaturalist.org/observations/71878					Charlie Hohn										iNaturalist	Observations	iNaturalist Research-grade Observations		HUMAN_OBSERVATION				http://www.inaturalist.org/observations/71878	71878		Charlie Hohn							fruiting						PRESENT2012-04-29T12:47:54	16:47:54Z			2012	4	29	Sun Apr 29 2012 12:47:54 GMT-0400 (EDT)						US	Vermont				1939 Elder Hill Rd, Bristol, Vermont, US								44.113185	-72.963711	10.0																		104321				Charlie Hohn		2012-04-29T22:01:36				83799		5338762					Mitchella repens L.								Plantae	Tracheophyta	Magnoliopsida	Gentianales	Rubiaceae		Mitchella	Mitchella			repens			SPECIES				ACCEPTED			50c9509d-22c7-4a22-a47d-8c48425ef4a7	US	2022-02-25T20:10:56.432Z								StillImage	true	false	5338762	53387627707728	220	412	8798	2907867		5338762	Mitchella repens	Mitchella repens L.	Mitchella repens		DWC_ARCHIVE	2022-02-25T20:10:56.432Z	2022-02-25T15:36:07.701Z	false		USA	United States	USA.46_1	Vermont	USA.46.1_1	Addison			NE
891103650																			141301										CC_BY_NC_4_0			2021-07-29T15:03:38Z		iNaturalist.orghttps://www.inaturalist.org/observations/141301					Sara Bárrios									iNaturalist	Observations	iNaturalist Research-grade Observations		HUMAN_OBSERVATION				http://www.inaturalist.org/observations/141301	141301		Sara Bárrios													PRESENT		2012-10-20T00:00:00				2012	10	20	2012-10-20										GB	England				loughton								51.66161	0.050928	4105.0		212249				Sara Bárrios		2012-10-30T19:39:10				48767		3293632					Hypholoma fasciculare (Huds.) P.Kumm.								Fungi	Basidiomycota	Agaricomycetes	Agaricales	Strophariaceae		Hypholoma	Hypholoma			fasciculare			SPECIES				ACCEPTED			50c9509d-22c7-4a22-a47d-8c48425ef4a7	GB	2022-02-25T19:39:04.839Z							COORDINATE_ROUNDED	StillImage	true	false	3293632	3293632	5	34	186	1499	4186	2533397		3293632	Hypholoma fasciculare	Hypholoma fasciculare (Huds.) P.Kumm.	Hypholoma fasciculare		DWC_ARCHIVE	2022-02-25T19:39:04.839Z	2022-02-25T15:36:07.701Z	false		GBR	United Kingdom	GBR.1_1	England	GBR.1.33_1	Essex	GBR.1.33.7_1	Epping Forest	NE
891132737																			219396										CC_BY_4_0			2014-10-20T00:11:12Z		iNaturalist.orghttps://www.inaturalist.org/observations/219396					sea-kangaroo									iNaturalist	Observations	iNaturalist Research-grade Observations		HUMAN_OBSERVATION				http://www.inaturalist.org/observations/219396	219396		sea-kangaroo													PRESENT		Dive site Bon Bini Na Kas, max depth 17.4 m/57 ft. Several in a big school of feeding Blue Tang.									2008-05-16T00:00:00				2008	5	16	2008-05-16										BQ	Bonaire				Bonaire, Netherlands Antilles								12.211317	-68.331364	188.0																				349334				sea-kangaroo		2013-03-20T02:29:05				128172		2396844					Mulloidichthys martinicus (Cuvier, 1829)								Animalia	Chordata	Actinopterygii	Perciformes	Mullidae		Mulloidichthys	Mulloidichthys			martinicus			SPECIES				ACCEPTED			50c9509d-22c7-4a22-a47d-8c48425ef4a7	US	2022-02-25T20:10:56.580Z							COORDINATE_ROUNDED	StillImage	true	false	2396844	2396844	1	44	204	587	4287	2396834		2396844	Mulloidichthys martinicus	Mulloidichthys martinicus (Cuvier, 1829)	Mulloidichthys martinicus		DWC_ARCHIVE	2022-02-25T20:10:56.580Z	2022-02-25T15:36:07.701Z	true		LC
1668790731																			294212										CC_BY_NC_4_0			2017-09-23T04:22:24Z		iNaturalist.orghttps://www.inaturalist.org/observations/294212					benmhogan									iNaturalist	Observations	iNaturalist Research-grade Observations		HUMAN_OBSERVATION				http://www.inaturalist.org/observations/294212	294212		benmhogan													PRESENT		2013-05-10T00:00:00				2013	5	10	2013-05-10										US	Washington				King County, US-WA, US								47.646652	-122.301045	7.0																				3358619				John Oliver		2015-07-22T18:51:38				6317		2476674					Calypte anna (R.Lesson, 1829)								Animalia	Chordata	Aves	Apodiformes	Trochilidae	Calypte	Calypte			anna			SPECIES				ACCEPTED			50c9509d-22c7-4a22-a47d-8c48425ef4a7	US	2022-02-25T19:55:48.253Z								StillImage	true	false	2476674	2476674	1	44	212	1448	5289	2476673		2476674	Calypte anna	Calypte anna (R.Lesson, 1829)	Calypte anna		DWC_ARCHIVE	2022-02-25T19:55:48.2532022-02-25T15:36:07.701Z	false		USA	United States	USA.48_1	Washington	USA.48.17_1	King			LC
1571061666																			362775										CC_BY_NC_4_0			2017-06-28T15:38:15Z		iNaturalist.orghttps://www.inaturalist.org/observations/362775					Kenneth Bader									iNaturalist	Observations	iNaturalist Research-grade Observations		HUMAN_OBSERVATION				http://www.inaturalist.org/observations/362775	362775		Kenneth Bader													PRESENT		2013-08-11T00:00:00				2013	8	11	2013-08-11										US	Texas				St. Edwards Park, Austin, TX								30.405492	-97.791255	29343.0																				600163				Kenneth Bader		2013-08-11T21:51:47				167640		2879863					Quercus buckleyi Nixon & Dorr								Plantae	Tracheophyta	Magnoliopsida	Fagales	Fagaceae		Quercus	Quercus			buckleyi			SPECIES				ACCEPTED			50c9509d-22c7-4a22-a47d-8c48425ef4a7	US	2022-02-25T19:39:12.024Z								StillImage	true	false	2879863	2879863	6	7707728	220	1354	4689	2877951		2879863	Quercus buckleyi	Quercus buckleyi Nixon & Dorr	Quercus buckleyi		DWC_ARCHIVE	2022-02-25T19:39:12.024Z	2022-02-25T15:36:07.701Z	false		USA	United States	USA.44_1	Texas	USA.44.227_1	Travis			LC
[root@eol-archive GBIF_service]# tail occurrence.txt 
3468669496																			106161304										CC_BY_NC_4_0			2022-02-05T16:59:15Z		iNaturalist.org	https://www.inaturalist.org/observations/106161304					Jamie Simmons							iNaturalist	Observations	iNaturalist Research-grade Observations		HUMAN_OBSERVATION				https://www.inaturalist.org/observations/106161304	106161304		Jamie Simmons													PRESENT								(Thayer's subspecies of Iceland Gull.) Second photo provided for size comparison (with Western or Western x Glaucous-winged hybrid).											2022-01-27T16:20:00	00:20:00Z	2022	1	27	2022/01/27 4:20 PM PST															US	Oregon				Lincoln County, OR, USA								44.729831	-124.059999	39.0	235380980				Jamie Simmons		2022-02-05T07:29:06				556736		9509445				Larus glaucoides thayeri W.S.Brooks, 1915								Animalia	Chordata	Aves	CharadriiformesLaridae		Larus	Larus			glaucoides	thayeri		SUBSPECIES				ACCEPTED			50c9509d-22c7-4a22-a47d-8c48425ef4a7	US	2022-02-25T20:11:42.416Z							COORDINATE_ROUNDED	StillImage;StillImage	true	false	9509445	9509445	1	44	212	7192402	9316	2481126		2481156	Larus glaucoides	Larus glaucoides thayeri W.S.Brooks, 1915	Larus glaucoides thayeri		DWC_ARCHIVE	2022-02-25T20:11:42.416Z	2022-02-25T15:36:07.701Z	false			
3468972759																			106232260										CC_BY_NC_4_0			2022-02-08T06:22:15Z		iNaturalist.org	https://www.inaturalist.org/observations/106232260					Alex R								iNaturalist	Observations	iNaturalist Research-grade Observations		HUMAN_OBSERVATION				https://www.inaturalist.org/observations/106232260	106232260		Alex R													PRESENT2019-04-27T10:59:00	10:59:00Z			2019	4	27	2019/04/27 10:59 AM UTC								TZ	Arusha				Ngorongoro Crater, Tanzania								-3.161752	35.58767	13276.0																				235578468				Alex R		2022-02-06T10:05:49				13798		2494030					Ploceus spekei (Heuglin, 1861)								Animalia	Chordata	Aves	Passeriformes	Ploceidae	Ploceus	Ploceus			spekei			SPECIES				ACCEPTED			50c9509d-22c7-4a22-a47d-8c48425ef4a7	US	2022-02-25T19:43:00.444Z							COORDINATE_ROUNDED	StillImage	true	false	2494030	249403044	212	729	9336	2494008		2494030	Ploceus spekei	Ploceus spekei (Heuglin, 1861)	Ploceus spekei		DWC_ARCHIVE	2022-02-25T19:43:00.444Z	2022-02-25T15:36:07.701Z	true		TZA	Tanzania	TZA.1_1	Arusha	TZA.1.9_1	Ngorongoro	TZA.1.9.11_1	Ngorongoro	LC
3499685349																			106813742										CC_BY_4_0			2022-02-16T12:34:35Z		iNaturalist.org	https://www.inaturalist.org/observations/106813742					JG								iNaturalist	Observations	iNaturalist Research-grade Observations		HUMAN_OBSERVATION				https://www.inaturalist.org/observations/106813742	106813742		JG							no evidence of flowering				PRESENT								Rabo-de-bugio. Exemplar localizado em restinga herbácea-subarbustiva de dunas frontais.		2021-11-07T15:08:17	18:08:17Z			2021	11	7	2021-11-07 15:08:17								BR	Santa Catarina				Mar Grosso, Laguna - SC, Brasil								-28.469872	-48.766727	28.0																			237264827				JG		2022-02-15T19:59:35				131244		2968593					Dalbergia ecastaphyllum (L.) Taub.								Plantae	Tracheophyta	Magnoliopsida	Fabales	Fabaceae	Dalbergia	Dalbergia			ecastaphyllum			SPECIES				ACCEPTED			50c9509d-22c7-4a22-a47d-8c48425ef4a7	US	2022-02-25T20:08:56.654Z							COORDINATE_ROUNDED	StillImage;StillImage;StillImage	true	false	2968593	2968593	6	7707728	220	1370	5386	2968358		2968593	Dalbergia ecastaphyllum	Dalbergia ecastaphyllum (L.) Taub.	Dalbergia ecastaphyllum		DWC_ARCHIVE	2022-02-25T20:08:56.654Z	2022-02-25T15:36:07.701Z	true		BRA	Brazil	BRA.24_1	Santa Catarina	BRA.24.143_1	Laguna	BRA.24.143.1_1	Laguna	LC
3499685559																			106903238										CC_BY_NC_4_0			2022-02-17T07:43:29Z		iNaturalist.org	https://www.inaturalist.org/observations/106903238					Amaya M.							iNaturalist	Observations	iNaturalist Research-grade Observations		HUMAN_OBSERVATION				https://www.inaturalist.org/observations/106903238	106903238		Amaya M.													PRESENT																			2022-02-16T17:45:00	04:45:00Z			2022	2	16	2022/02/16 5:45 PM NZDT									NZ	Wellington				Tawa, Wellington, New Zealand								-41.17481	174.821104	475.0																			237519088				Amaya M.		2022-02-17T06:44:17				392677		1376774				Microterys nietneri (Motschulsky, 1859)								Animalia	Arthropoda	Insecta	Hymenoptera	Encyrtidae		Microterys	Microterys			nietneri			SPECIES				ACCEPTED			50c9509d-22c7-4a22-a47d-8c48425ef4a7	NZ	2022-02-25T20:09:34.524Z							COORDINATE_ROUNDED	StillImage;StillImage;StillImage	true	false	1376774	1376774	1	54	216	1457	9440	1376573		1376774	Microterys nietneri	Microterys nietneri (Motschulsky, 1859)	Microterys nietneri		DWC_ARCHIVE	2022-02-25T20:09:34.524Z	2022-02-25T15:36:07.701Z	false		NZL	New Zealand	NZL.18_1	Wellington	NZL.18.8_1	Wellington			NE

inat_images	Thu 2022-03-03 07:23:52 AM	{"agent.tab":228535, "media_resource.tab":10,836,311, "taxon.tab":290388, "time_elapsed":{"sec":29208.32, "min":486.81, "hr":8.11}}
-> with 150 limit per taxon
inat_images_100limit	Sun 2022-03-06 0{"agent.tab":194651, "media_resource.tab":8742707, "taxon.tab":290388, "time_elapsed":{"sec":26243.11, "min":437.39, "hr":7.29}}
-> with 100 limit per taxon
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/iNatImagesAPI');
ini_set('memory_limit','12096M'); //this can be removed and choose a caching solution. But let us try this first.
$timestart = time_elapsed();
$resource_id = 'inat_images'; //being used currently, caching image scores
$resource_id = 'inat_images_100limit'; //first crack, no score ranking, just use first 100 images per taxon in iNaturalist.
$resource_id = 'inat_images_75limit'; //first crack, no score ranking, just use first 100 images per taxon in iNaturalist.

$func = new iNatImagesAPI($resource_id, false, true);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //2nd param False - not a big file | 3rd param True - can delete working folder
/* 
sample multimedia.txt record:
Array
(
    [gbifid] => 3499536769
    [type] => StillImage
    [format] => image/jpeg
    [identifier] => https://inaturalist-open-data.s3.amazonaws.com/photos/179418423/original.jpeg
    [references] => https://www.inaturalist.org/photos/179418423
    [title] => 
    [description] => 
    [source] => 
    [audience] => 
    [created] => 2014-08-21T17:46:31Z
    [creator] => Martin de Jong
    [contributor] => 
    [publisher] => iNaturalist
    [license] => http://creativecommons.org/licenses/by-nc/4.0/
    [rightsholder] => Martin de Jong
)
sample occurrence.txt record:
Array
(
    [gbifid] => 3499536769
    [abstract] => 
    [accessrights] => 
    [accrualmethod] => 
    [accrualperiodicity] => 
    [accrualpolicy] => 
    [alternative] => 
    [audience] => 
    [available] => 
    [bibliographiccitation] => 
    [conformsto] => 
    [contributor] => 
    [coverage] => 
    [created] => 
    [creator] => 
    [date] => 
    [dateaccepted] => 
    [datecopyrighted] => 
    [datesubmitted] => 
    [description] => 
    [educationlevel] => 
    [extent] => 
    [format] => 
    [hasformat] => 
    [haspart] => 
    [hasversion] => 
    [identifier] => 106689439
    [instructionalmethod] => 
    [isformatof] => 
    [ispartof] => 
    [isreferencedby] => 
    [isreplacedby] => 
    [isrequiredby] => 
    [isversionof] => 
    [issued] => 
    [language] => 
    [license] => CC_BY_NC_4_0
    [mediator] => 
    [medium] => 
    [modified] => 2022-02-14T21:01:21Z
    [provenance] => 
    [publisher] => 
    [references] => https://www.inaturalist.org/observations/106689439
    [relation] => 
    [replaces] => 
    [requires] => 
    [rights] => 
    [rightsholder] => Martin de Jong
    [source] => 
    [spatial] => 
    [subject] => 
    [tableofcontents] => 
    [temporal] => 
    [title] => 
    [type] => 
    [valid] => 
    [institutionid] => 
    [collectionid] => 
    [datasetid] => 
    [institutioncode] => iNaturalist
    [collectioncode] => Observations
    [datasetname] => iNaturalist research-grade observations
    [ownerinstitutioncode] => 
    [basisofrecord] => HUMAN_OBSERVATION
    [informationwithheld] => Coordinate uncertainty increased to 25717m to protect threatened taxon
    [datageneralizations] => 
    [dynamicproperties] => 
    [occurrenceid] => https://www.inaturalist.org/observations/106689439
    [catalognumber] => 106689439
    [recordnumber] => 
    [recordedby] => Martin de Jong
    [recordedbyid] => 
    [individualcount] => 
    [organismquantity] => 
    [organismquantitytype] => 
    [sex] => 
    [lifestage] => Juvenile
    [reproductivecondition] => 
    [behavior] => 
    [establishmentmeans] => 
    [degreeofestablishment] => 
    [pathway] => 
    [georeferenceverificationstatus] => 
    [occurrencestatus] => PRESENT
    [preparations] => 
    [disposition] => 
    [associatedoccurrences] => 
    [associatedreferences] => 
    [associatedsequences] => 
    [associatedtaxa] => 
    [othercatalognumbers] => 
    [occurrenceremarks] => 
    [organismid] => 
    [organismname] => 
    [organismscope] => 
    [associatedorganisms] => 
    [previousidentifications] => 
    [organismremarks] => 
    [materialsampleid] => 
    [eventid] => 
    [parenteventid] => 
    [fieldnumber] => 
    [eventdate] => 2014-08-21T10:46:00
    [eventtime] => 10:46:00Z
    [startdayofyear] => 
    [enddayofyear] => 
    [year] => 2014
    [month] => 8
    [day] => 21
    [verbatimeventdate] => 2014/08/21 10:46 AM UTC
    [habitat] => 
    [samplingprotocol] => 
    [samplesizevalue] => 
    [samplesizeunit] => 
    [samplingeffort] => 
    [fieldnotes] => 
    [eventremarks] => 
    [locationid] => 
    [highergeographyid] => 
    [highergeography] => 
    [continent] => 
    [waterbody] => 
    [islandgroup] => 
    [island] => 
    [countrycode] => DE
    [stateprovince] => Schleswig-Holstein
    [county] => 
    [municipality] => 
    [locality] => 
    [verbatimlocality] => Nordfriesland, Schleswig-Holsteinisches Wattenmeer, DE-SH, DE
    [verbatimelevation] => 
    [verticaldatum] => 
    [verbatimdepth] => 
    [minimumdistanceabovesurfaceinmeters] => 
    [maximumdistanceabovesurfaceinmeters] => 
    [locationaccordingto] => 
    [locationremarks] => 
    [decimallatitude] => 54.54877
    [decimallongitude] => 8.530272
    [coordinateuncertaintyinmeters] => 25717.0
    [coordinateprecision] => 
    [pointradiusspatialfit] => 
    [verbatimcoordinatesystem] => 
    [verbatimsrs] => 
    [footprintwkt] => 
    [footprintsrs] => 
    [footprintspatialfit] => 
    [georeferencedby] => 
    [georeferenceddate] => 
    [georeferenceprotocol] => 
    [georeferencesources] => 
    [georeferenceremarks] => 
    [geologicalcontextid] => 
    [earliesteonorlowesteonothem] => 
    [latesteonorhighesteonothem] => 
    [earliesteraorlowesterathem] => 
    [latesteraorhighesterathem] => 
    [earliestperiodorlowestsystem] => 
    [latestperiodorhighestsystem] => 
    [earliestepochorlowestseries] => 
    [latestepochorhighestseries] => 
    [earliestageorloweststage] => 
    [latestageorhigheststage] => 
    [lowestbiostratigraphiczone] => 
    [highestbiostratigraphiczone] => 
    [lithostratigraphicterms] => 
    [group] => 
    [formation] => 
    [member] => 
    [bed] => 
    [identificationid] => 236894644
    [verbatimidentification] => 
    [identificationqualifier] => 
    [typestatus] => 
    [identifiedby] => Claude Nozères
    [identifiedbyid] => 
    [dateidentified] => 2022-02-13T17:57:35
    [identificationreferences] => 
    [identificationverificationstatus] => 
    [identificationremarks] => possibly
    [taxonid] => 63740
    [scientificnameid] => 
    [acceptednameusageid] => 
    [parentnameusageid] => 
    [originalnameusageid] => 
    [nameaccordingtoid] => 
    [namepublishedinid] => 
    [taxonconceptid] => 
    [scientificname] => Gadus morhua Linnaeus, 1758
    [acceptednameusage] => 
    [parentnameusage] => 
    [originalnameusage] => 
    [nameaccordingto] => 
    [namepublishedin] => 
    [namepublishedinyear] => 
    [higherclassification] => 
    [kingdom] => Animalia
    [phylum] => Chordata
    [class] => Actinopterygii
    [order] => Gadiformes
    [family] => Gadidae
    [subfamily] => 
    [genus] => Gadus
    [genericname] => Gadus
    [subgenus] => 
    [infragenericepithet] => 
    [specificepithet] => morhua
    [infraspecificepithet] => 
    [cultivarepithet] => 
    [taxonrank] => SPECIES
    [verbatimtaxonrank] => 
    [vernacularname] => 
    [nomenclaturalcode] => 
    [taxonomicstatus] => ACCEPTED
    [nomenclaturalstatus] => 
    [taxonremarks] => 
    [datasetkey] => 50c9509d-22c7-4a22-a47d-8c48425ef4a7
    [publishingcountry] => US
    [lastinterpreted] => 2022-02-25T19:49:19.687Z
    [elevation] => 
    [elevationaccuracy] => 
    [depth] => 
    [depthaccuracy] => 
    [distanceabovesurface] => 
    [distanceabovesurfaceaccuracy] => 
    [issue] => COORDINATE_ROUNDED
    [mediatype] => StillImage
    [hascoordinate] => true
    [hasgeospatialissues] => false
    [taxonkey] => 8084280
    [acceptedtaxonkey] => 8084280
    [kingdomkey] => 1
    [phylumkey] => 44
    [classkey] => 204
    [orderkey] => 549
    [familykey] => 3701
    [genuskey] => 2335052
    [subgenuskey] => 
    [specieskey] => 8084280
    [species] => Gadus morhua
    [acceptedscientificname] => Gadus morhua Linnaeus, 1758
    [verbatimscientificname] => Gadus morhua
    [typifiedname] => 
    [protocol] => DWC_ARCHIVE
    [lastparsed] => 2022-02-25T19:49:19.687Z
    [lastcrawled] => 2022-02-25T15:36:07.701Z
    [repatriated] => true
    [relativeorganismquantity] => 
    [level0gid] => 
    [level0name] => 
    [level1gid] => 
    [level1name] => 
    [level2gid] => 
    [level2name] => 
    [level3gid] => 
    [level3name] => 
    [iucnredlistcategory] => VU
)
*/
?>