<?php

shell_exec("php ".dirname(__FILE__)."/hierarchies_content.php");
shell_exec("php ".dirname(__FILE__)."/top_images.php");
shell_exec("php ".dirname(__FILE__)."/random_taxa.php");
shell_exec("php ".dirname(__FILE__)."/random_hierarchy_images.php");
shell_exec("php ".dirname(__FILE__)."/table_of_contents.php");

?>