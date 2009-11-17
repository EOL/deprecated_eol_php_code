<?php







define('SOLR_SERVER', 'http://localhost:8983/solr');
define('SOLR_FILE_DELIMITER', '|');
define('SOLR_MULTI_VALUE_DELIMETER', ';');

########################################
/* Content Server */

define("DOWNLOAD_WAIT_TIME", "100000"); //.1 seconds
define("DOWNLOAD_ATTEMPTS", "2");
define("DOWNLOAD_TIMEOUT_SECONDS", "10");


if(defined("ENVIRONMENT") && ENVIRONMENT=="test")
{
    define("CONTENT_PARTNER_LOCAL_PATH", LOCAL_ROOT."temp/");
    define("CONTENT_LOCAL_PATH", LOCAL_ROOT."temp/");
    define("CONTENT_TEMP_PREFIX", LOCAL_ROOT."temp/");
    define("CONTENT_RESOURCE_LOCAL_PATH", LOCAL_ROOT."temp/");
}else
{
    define("CONTENT_PARTNER_LOCAL_PATH", LOCAL_ROOT."/applications/content_server/content_partners/");
    define("CONTENT_LOCAL_PATH", LOCAL_ROOT."/applications/content_server/content/");
    define("CONTENT_TEMP_PREFIX", LOCAL_ROOT."/applications/content_server/tmp/");
    define("CONTENT_RESOURCE_LOCAL_PATH", LOCAL_ROOT."/applications/content_server/resources/");
    define("CONTENT_GNI_RESOURCE_PATH", LOCAL_ROOT."/applications/content_server/gni_tcs_files/");
}

if(defined('USING_IMAGEMAGICK') && USING_IMAGEMAGICK)
{
    define("MAGICK_HOME", "/usr/local/ImageMagick/");
    putenv("MAGICK_HOME=".MAGICK_HOME);
    putenv("PATH=".MAGICK_HOME."/bin/:".getenv("PATH"));
    putenv("DYLD_LIBRARY_PATH=".MAGICK_HOME."/lib");
}

if(defined('USING_SPM') && USING_SPM)
{
    define("RDFAPI_INCLUDE_DIR", LOCAL_ROOT."classes/modules/rdfapi-php/api/");
    define("LSID_RESOLVER"  , "http://lsid.tdwg.org/");
    
    define("RDF_NS"         , "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
    define("RDFS_NS"        , "http://www.w3.org/2000/01/rdf-schema#");
    define("XSD_NS"         , "http://www.w3.org/2001/XMLSchema#");
    define("SPM_NS"         , "http://rs.tdwg.org/ontology/voc/SpeciesProfileModel#");
    define("SPMI_NS"        , "http://rs.tdwg.org/ontology/voc/SPMInfoItems#");
    define("TC_NS"          , "http://rs.tdwg.org/ontology/voc/TaxonConcept#");
    define("TN_NS"          , "http://rs.tdwg.org/ontology/voc/TaxonName#");
}

define("PARTNER_LOGO_LARGE", "100x100");
define("PARTNER_LOGO_SMALL", "60x60");
define("CONTENT_IMAGE_LARGE", "460x345");
define("CONTENT_IMAGE_MEDIUM", "147x147");
define("CONTENT_IMAGE_SMALL", "62x47");

define("TAXON_CACHE_PREFIX", "http://www.eol.org/expire_taxa/");
$GLOBALS['eol_content_servers'] = array();

$GLOBALS['clear_cache_urls'] = array();

define("CYBERSOURCE_PUBLIC_KEY", "");
define("CYBERSOURCE_PRIVATE_KEY", "");
define("CYBERSOURCE_SERIAL_NUMBER", "");
define("CYBERSOURCE_MERCHANT_ID", "");


########################################
/* Google */

define("GOOGLE_ANALYTICS_API_USERNAME", "");
define("GOOGLE_ANALYTICS_API_PASSWORD", "");


########################################
/* Flickr */

define("FLICKR_API_KEY", "");
define("FLICKR_SHARED_SECRET", "");
define("FLICKR_REST_PREFIX", "http://api.flickr.com/services/rest/?");
define("FLICKR_AUTH_PREFIX", "http://api.flickr.com/services/auth/?");
define("FLICKR_UPLOAD_URL", "http://www.flickr.com/services/upload/");
define("FLICKR_EOL_GROUP_ID", "");
define("FLICKR_PLEARY_AUTH_TOKEN", "");

$GLOBALS['flickr_licenses'] = array();
//$GLOBALS['flickr_licenses'][0] = "All Rights Reserved";
$GLOBALS['flickr_licenses'][1] = "http://creativecommons.org/licenses/by-nc-sa/2.0/";
$GLOBALS['flickr_licenses'][2] = "http://creativecommons.org/licenses/by-nc/2.0/";
//$GLOBALS['flickr_licenses'][3] = "http://creativecommons.org/licenses/by-nc-nd/2.0/";
$GLOBALS['flickr_licenses'][4] = "http://creativecommons.org/licenses/by/2.0/";
$GLOBALS['flickr_licenses'][5] = "http://creativecommons.org/licenses/by-sa/2.0/";
//$GLOBALS['flickr_licenses'][6] = "http://creativecommons.org/licenses/by-nd/2.0/";


########################################
/* SimpleTest */

define('SIMPLE_TEST', LOCAL_ROOT.'/classes/modules/simpletest/');


########################################
/* Matching Hierarchies */

define('MATCH_SCORE_THRESHOLD', .601);


########################################
/* Character Set for Regular Expressions */

define("UPPER","A-ZÀÂÅÅÃÄÁÆČÇÉÈÊËÍÌÎÏÑÓÒÔØÕÖÚÙÛÜßĶŘŠŞŽŒ");
define("LOWER","a-záááàâåãäăæčćçéèêëĕíìîïǐĭñńóòôøõöŏúùûüůśšşřğžźýýÿœœ");

?>