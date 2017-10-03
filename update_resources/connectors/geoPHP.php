<?php
// include_once('geoPHP.inc');
include_once(dirname(__FILE__) . "/../../vendor/geoPHP/geoPHP.inc");

// Polygon WKT example
$polygon = geoPHP::load('POLYGON((1 1,5 1,5 5,1 5,1 1),(2 2,2 3,3 3,3 2,2 2))','wkt');
$area = $polygon->getArea();
$centroid = $polygon->getCentroid();
$centX = $centroid->getX();
$centY = $centroid->getY();

print "\nThis polygon has an area of ".$area." and a centroid with X=".$centX." and Y=".$centY."\n";

// MultiPoint json example
$json = 
'{
   "type": "MultiPoint",
   "coordinates": [
       [100.0, 0.0], [101.0, 1.0]
   ]
}';

$multipoint = geoPHP::load($json, 'json');
$multipoint_points = $multipoint->getComponents();
$first_wkt = $multipoint_points[0]->out('wkt');

print "\nThis multipoint has ".$multipoint->numGeometries()." points. The first point has a wkt representation of ".$first_wkt."\n";

//=================================
echo "<hr>";
$input = "MULTILINESTRING((10 10,20 20,10 40))";
$input = "POLYGON ((-82.6171875 32.54681317351517, -72.7734375 44.84029065139799, -59.765625 53.12040528310657, -67.1484375 57.136239319177434, -74.1796875 60.75915950226991, -84.55078125 66.56, 14.23828125 66.56, 1.0546875 46.55886030311719, -5.2734375 32.84267363195431, -82.6171875 32.54681317351517))";
$geometry = geoPHP::load($input,'wkt');
$reduced_geometry = geoPHP::geometryReduce($geometry);

// print_r($reduced_geometry);

//=================================
echo "<hr>";
$geometry = geoPHP::geometryList();
print_r($geometry);

//=================================
echo "<hr>";

/*
GEOMETRYCOLLECTION(
    POLYGON ((-175.78125 32.54681317351514, -175.78125 66.51326044311185, -135.703125 61.938950426660604, -121.640625 52.908902047770255, -117.7734375 32.71, -175.78125 32.54681317351514)),
    POLYGON ((116.71874999999999 32.71, 130.78125 47.517200697839414, 137.109375 63.548552232036414, 150.46875 62.59334083012024, 165.9375 63.860035895395306, 175.78125 66.51326044311185, 175.78125 33.137551192346145, 116.71874999999999 32.71))
    )

MULTIPOLYGON(
    ((-175.78125 32.54681317351514, -175.78125 66.51326044311185, -135.703125 61.938950426660604, -121.640625 52.908902047770255, -117.7734375 32.71, -175.78125 32.54681317351514)),
    ((116.71874999999999 32.71, 130.78125 47.517200697839414, 137.109375 63.548552232036414, 150.46875 62.59334083012024, 165.9375 63.860035895395306, 175.78125 66.51326044311185, 175.78125 33.137551192346145, 116.71874999999999 32.71))
    )
*/


/*
Hi Patrick,
I've now installed geoPHP locally on my machine. And your examples are running OK.

Actual task:
Given these 2 samples of our GEOMETRYCOLLECTION below. May I request, can you please give me the equivalent MULTIPOLYGON for each.

Sample 1:
POLYGON ((-82.6171875 32.54681317351517, -72.7734375 44.84029065139799, -59.765625 53.12040528310657, -67.1484375 57.136239319177434, -74.1796875 60.75915950226991, -84.55078125 66.56, 14.23828125 66.56, 1.0546875 46.55886030311719, -5.2734375 32.84267363195431, -82.6171875 32.54681317351517))

Sample 2:
GEOMETRYCOLLECTION(POLYGON ((-175.78125 32.54681317351514, -175.78125 66.51326044311185, -135.703125 61.938950426660604, -121.640625 52.908902047770255, -117.7734375 32.71, -175.78125 32.54681317351514)),POLYGON ((116.71874999999999 32.71, 130.78125 47.517200697839414, 137.109375 63.548552232036414, 150.46875 62.59334083012024, 165.9375 63.860035895395306, 175.78125 66.51326044311185, 175.78125 33.137551192346145, 116.71874999999999 32.71)))

Can you show me the actual function or combination of functions I need to call to give me the MULTIPOLYGON version of a GEOMETRYCOLLECTION.

This one give me an array().
$input = "POLYGON ((-82.6171875 32.54681317351517, -72.7734375 44.84029065139799, -59.765625 53.12040528310657, -67.1484375 57.136239319177434, -74.1796875 60.75915950226991, -84.55078125 66.56, 14.23828125 66.56, 1.0546875 46.55886030311719, -5.2734375 32.84267363195431, -82.6171875 32.54681317351517))";
$geometry = geoPHP::load($input,'wkt');
$reduced_geometry = geoPHP::geometryReduce($geometry);

Thanks,
Eli
*/

?>

