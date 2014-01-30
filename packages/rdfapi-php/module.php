<?php

// ----------------------------------------------------------------------------------
// RDF API for PHP 
// ----------------------------------------------------------------------------------
// @version                  : $Id: RdfAPI.php 268 2006-05-15 05:28:09Z tgauss $
// Authors                   : Chris Bizer (chris@bizer.de),
//                             Radoslaw Oldakowski (radol@gmx.de)
// Description               : This file includes constatns and model package.
// ----------------------------------------------------------------------------------


//if(!defined("RDFAPI_INCLUDE_DIR")) define("RDFAPI_INCLUDE_DIR", "this will break");
define('RDFAPI_INCLUDE_DIR', dirname(__FILE__)."/api/");

// Include Constants and base Object
require_once( RDFAPI_INCLUDE_DIR . 'constants.php' );
require_once( RDFAPI_INCLUDE_DIR . 'util/Object.php' );
include_once( RDFAPI_INCLUDE_DIR . PACKAGE_MODEL);


?>