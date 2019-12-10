<?php
echo '<pre>';
if($_GET) {
    echo "\nGet:\n";
    print_r($_GET);
}
elseif($_POST) {
    echo "\nPost:\n";
    print_r($_POST);
}
echo '</pre>';
?>