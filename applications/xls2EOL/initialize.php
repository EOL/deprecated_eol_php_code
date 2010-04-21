<?php

$wsheet[0]='Contributors';
$wsheet[1]='Attributions';
$wsheet[2]='Text descriptions';
$wsheet[3]='References';
$wsheet[4]='Multimedia';
$wsheet[5]='Taxon Information';
$wsheet[6]='More common names (optional)';

//Contributors                
$sheet[0]['Code']             = array();
$sheet[0]['Display Name']     = array();
$sheet[0]['Role']             = array();
$sheet[0]['Logo URL']         = array();
$sheet[0]['Homepage']         = array();
$sheet[0]['Family Name']     = array();
$sheet[0]['Given Name']     = array();
$sheet[0]['Email']             = array();
$sheet[0]['Telephone']         = array();
$sheet[0]['Mailing Address']= array();

//Attributions
$sheet[1]['Code']                     = array();
//$sheet[1]['Contributor Code']         = array();
$sheet[1]['License']                 = array();
$sheet[1]['RightsStatement']         = array();
$sheet[1]['RightsHolder']             = array();
$sheet[1]['BibliographicCitation']    = array();

/*
$sheet[1]['Contributor 1 code']     = array();
$sheet[1]['Contributor 2 code']     = array();
$sheet[1]['Contributor 3 code']     = array();
$sheet[1]['Contributor 4 code']     = array();
$sheet[1]['Contributor 5 code']     = array();
*/

//Text descriptions
//$sheet[2]['DataObject ID']= array();

$sheet[2]['Reference Code']= array();
$sheet[2]['Attribution Code']= array();
$sheet[2]['Contributor Code']= array();

$sheet[2]['Audience']= array();

$sheet[2]['DateCreated']= array();
$sheet[2]['DateModified']= array();
$sheet[2]['Taxon Name']= array();

$sheet[2]['Associations']= array();
$sheet[2]['Behaviour']= array();
$sheet[2]['Biology']= array();
$sheet[2]['Conservation']= array();
$sheet[2]['ConservationStatus']= array();
$sheet[2]['Cyclicity']= array();
$sheet[2]['Cytology']= array();
$sheet[2]['Description']= array();
$sheet[2]['DiagnosticDescription']= array();
$sheet[2]['Diseases']= array();
$sheet[2]['Dispersal']= array();
$sheet[2]['Distribution']= array();
$sheet[2]['Ecology']= array();
$sheet[2]['Evolution']= array();
$sheet[2]['GeneralDescription']= array();
$sheet[2]['Genetics']= array();
$sheet[2]['Growth']= array();
$sheet[2]['Habitat']= array();
$sheet[2]['Key']= array();
$sheet[2]['Legislation']= array();
$sheet[2]['LifeCycle']= array();
$sheet[2]['LifeExpectancy']= array();
$sheet[2]['LookAlikes']= array();
$sheet[2]['Management']= array();
$sheet[2]['Migration']= array();
$sheet[2]['MolecularBiology']= array();
$sheet[2]['Morphology']= array();
$sheet[2]['Physiology']= array();
$sheet[2]['PopulationBiology']= array();
$sheet[2]['Procedures']= array();
$sheet[2]['Reproduction']= array();
$sheet[2]['RiskStatement']= array();
$sheet[2]['Size']= array();
$sheet[2]['TaxonBiology']= array();
$sheet[2]['Threats']= array();
$sheet[2]['Trends']= array();
$sheet[2]['TrophicStrategy']= array();
$sheet[2]['Uses']= array();

$DO_text = array(
'Associations', 'Behaviour', 'Biology', 'Conservation', 'ConservationStatus', 'Cyclicity', 'Cytology', 'Description', 
'DiagnosticDescription', 'Diseases', 'Dispersal', 'Distribution', 'Ecology', 'Evolution', 'GeneralDescription', 
'Genetics', 'Growth', 'Habitat', 'Key', 'Legislation', 'LifeCycle', 'LifeExpectancy', 'LookAlikes', 'Management', 'Migration', 
'MolecularBiology', 'Morphology', 'Physiology', 'PopulationBiology', 'Procedures', 'Reproduction', 'RiskStatement', 'Size', 
'TaxonBiology', 'Threats', 'Trends', 'TrophicStrategy', 'Uses');

$DO_text_title = array(
'Associations', 'Behaviour', 'Biology', 'Conservation', 'Conservation Status', 'Cyclicity', 'Cytology', 'Description', 
'Diagnostic Description', 'Diseases', 'Dispersal', 'Distribution', 'Ecology', 'Evolution', 'General Description', 
'Genetics', 'Growth', 'Habitat', 'Key', 'Legislation', 'LifeCycle', 'Life Expectancy', 'Look Alikes', 'Management', 'Migration', 
'Molecular Biology', 'Morphology', 'Physiology', 'Population Biology', 'Procedures', 'Reproduction', 'Risk Statement', 'Size', 
'Taxon Biology', 'Threats', 'Trends', 'Trophic Strategy', 'Uses');

/*
$sheet[2]['Associations']= array();
$sheet[2]['ConservationStatus']= array();
$sheet[2]['DiagnosticDescription']= array();
$sheet[2]['Diseases']= array();
$sheet[2]['Dispersal']= array();
$sheet[2]['Distribution']= array();
$sheet[2]['Evolution']= array();
$sheet[2]['GeneralDescription']= array();
$sheet[2]['Genetics']= array();
$sheet[2]['Growth']= array();
$sheet[2]['Habitat']= array();
$sheet[2]['LifeCycle']= array();
$sheet[2]['LifeExpectancy']= array();
$sheet[2]['LookAlikes']= array();
$sheet[2]['Management']= array();
$sheet[2]['Morphology']= array();
$sheet[2]['Physiology']= array();
$sheet[2]['PopulationBiology']= array();
$sheet[2]['Procedures']= array();
$sheet[2]['Reproduction']= array();
$sheet[2]['Size']= array();
$sheet[2]['TaxonBiology']= array();
$sheet[2]['Threats']= array();
$sheet[2]['Uses']= array();

$DO_text = array('Associations',    'ConservationStatus', 'DiagnosticDescription',    'Diseases',    'Dispersal', 'Distribution',    
'Evolution', 'GeneralDescription', 'Genetics',    'Growth', 'Habitat', 'LifeCycle', 'LifeExpectancy', 'LookAlikes',
'Management', 'Morphology', 'Physiology', 'PopulationBiology', 'Procedures',
'Reproduction', 'Size', 'TaxonBiology', 'Threats', 'Uses');

$DO_text_title = array('Associations',    'Conservation Status', 'Diagnostic Description', 'Diseases', 'Dispersal', 'Distribution',    
'Evolution', 'General Description', 'Genetics',    'Growth', 'Habitat', 'Life Cycle', 'Life Expectancy', 'Look Alikes',
'Management', 'Morphology', 'Physiology', 'Population Biology', 'Procedures',
'Reproduction', 'Size', 'TaxonBiology', 'Threats', 'Uses');
*/

//References
//$sheet[3]['DataObject ID']             = array();
$sheet[3]['Reference Code']         = array();
//$sheet[3]['Taxon Name']             = array();
$sheet[3]['Bibliographic Citation']    = array();
$sheet[3]['BICI']     = array();
$sheet[3]['CODEN']     = array();
$sheet[3]['DOI']     = array();
$sheet[3]['EISSN']     = array();
$sheet[3]['Handle'] = array();
$sheet[3]['ISSN']     = array();
$sheet[3]['ISBN']     = array();
$sheet[3]['LSID']     = array();
$sheet[3]['OCLC']     = array();
$sheet[3]['SICI']     = array();
$sheet[3]['URL']     = array();
$sheet[3]['URN']     = array();

//Multimedia
$sheet[4]['Taxon Name'] = array();
$sheet[4]['DateCreated'] = array();
$sheet[4]['DateModified'] = array();
//$sheet[4]['File Name'] = array();
$sheet[4]['Data Type'] = array();    
$sheet[4]['MIME Type'] = array();    
$sheet[4]['Media URL'] = array();
$sheet[4]['Thumbnail URL'] = array();
$sheet[4]['Source URL'] = array();
$sheet[4]['Caption'] = array();
$sheet[4]['Language'] = array();
$sheet[4]['Audience'] = array();
$sheet[4]['Location'] = array();
$sheet[4]['Latitude'] = array();    
$sheet[4]['Longitude']                 = array();
$sheet[4]['Altitude']                 = array();
$sheet[4]['Attribution Code']         = array();
$sheet[4]['Contributor Code']         = array();
$sheet[4]['Reference Code']         = array();

//$sheet[4]['BibliographicCitation']     = array();

//Taxon Information (optional)
$sheet[5]['Reference Code']             = array();
$sheet[5]['Taxon Name']                 = array();
$sheet[5]['Kingdom']                     = array();
$sheet[5]['Phylum']                     = array();
$sheet[5]['Class']                         = array();
$sheet[5]['Order']                         = array();
$sheet[5]['Family']                     = array();
$sheet[5]['Genus']                         = array();
$sheet[5]['Scientific Name']             = array();
$sheet[5]['Preferred Common Name']         = array();
$sheet[5]['Language of Common Name']     = array();
$sheet[5]['Source URL']                 = array();

//More common names (optional)
$sheet[6]['Taxon Name']     = array();
$sheet[6]['Common Name']     = array();
$sheet[6]['Language']     = array();
$sheet[6]['Primary EOL Common Name']     = array();
$sheet[6]['EOL Image']     = array();
$sheet[6]['Notes']     = array();

//Synonyms
$sheet[7]['Taxon Name']     = array();
$sheet[7]['Synonym']     = array();
$sheet[7]['Relationship']     = array();

/*
 $data->sheets[0]['numRows'] - count rows
 $data->sheets[0]['numCols'] - count columns
 $data->sheets[0]['cells'][$i][$j] - data from $i-row $j-column

 $data->sheets[0]['cellsInfo'][$i][$j] - extended info about cell
    
    $data->sheets[0]['cellsInfo'][$i][$j]['type'] = "date" | "number" | "unknown"
        if 'type' == "unknown" - use 'raw' value, because  cell contain value with format '0.00';
    $data->sheets[0]['cellsInfo'][$i][$j]['raw'] = value if cell without format 
    $data->sheets[0]['cellsInfo'][$i][$j]['colspan'] 
    $data->sheets[0]['cellsInfo'][$i][$j]['rowspan'] 
*/


?>