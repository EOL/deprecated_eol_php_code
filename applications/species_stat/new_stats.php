<?php
// define('DEBUG', true);
// define('MYSQL_DEBUG', true);
// define('DEBUG_TO_FILE', true);

//define("ENVIRONMENT", "integration");
define("ENVIRONMENT", "slave_32");
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$wrap = "\n";
$wrap = "<br>";

$stats = new SiteStatistics();
$start = Functions::time_elapsed();

echo "$wrap---------------- Main$wrap$wrap";
echo "total_pages: ".$stats->total_pages()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "total_pages_in_col: ".$stats->total_pages_in_col()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "total_pages_not_in_col: ".$stats->total_pages_not_in_col()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "pages_with_content: ".$stats->pages_with_content()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "pages_with_text: ".$stats->pages_with_text()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "pages_with_images: ".$stats->pages_with_images()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "pages_with_text_and_images: ".$stats->pages_with_text_and_images()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "pages_with_images_no_text: ".$stats->pages_with_images_no_text()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "pages_with_text_no_images: ".$stats->pages_with_text_no_images()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "pages_with_links_no_text: ".$stats->pages_with_links_no_text()."$wrap";
echo Functions::time_elapsed()."$wrap";

echo "$wrap---------------- Content$wrap$wrap";
// echo "pages_with_vetted_objects: ".$stats->pages_with_vetted_objects()."$wrap";
// echo Functions::time_elapsed()."$wrap";
echo "pages_in_col_no_content: ".$stats->pages_in_col_no_content()."$wrap";
echo Functions::time_elapsed()."$wrap";
// echo "pages_in_col_one_category: ".$stats->pages_in_col_one_category()."$wrap";
// echo Functions::time_elapsed()."$wrap";
// echo "pages_not_in_col_one_category: ".$stats->pages_not_in_col_one_category()."$wrap";
// echo Functions::time_elapsed()."$wrap";
// echo "pages_in_col_more_categories: ".$stats->pages_in_col_more_categories()."$wrap";
// echo Functions::time_elapsed()."$wrap";
// echo "pages_not_in_col_more_categories: ".$stats->pages_not_in_col_more_categories()."$wrap";
// echo Functions::time_elapsed()."$wrap";

echo "$wrap---------------- BHL$wrap$wrap";
echo "pages_with_bhl: ".$stats->pages_with_bhl()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "pages_with_bhl_no_text: ".$stats->pages_with_bhl_no_text()."$wrap";
echo Functions::time_elapsed()."$wrap";

echo "$wrap---------------- Curators$wrap$wrap";
echo "pages_awaiting_publishing: ".$stats->pages_awaiting_publishing()."$wrap";
echo Functions::time_elapsed()."$wrap";
// echo "col_content_needs_curation: ".$stats->col_content_needs_curation()."$wrap";
// echo Functions::time_elapsed()."$wrap";
// echo "non_col_content_needs_curation: ".$stats->non_col_content_needs_curation()."$wrap";
// echo Functions::time_elapsed()."$wrap";

echo "$wrap---------------- Lifedesk$wrap$wrap";
echo "lifedesk_taxa: ".$stats->lifedesk_taxa()."$wrap";
echo Functions::time_elapsed()."$wrap";
echo "lifedesk_data_objects: ".$stats->lifedesk_data_objects()."$wrap";
echo Functions::time_elapsed()."$wrap";

?>