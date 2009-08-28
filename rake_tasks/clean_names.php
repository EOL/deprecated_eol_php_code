#!/usr/local/bin/php
<?php

include_once("../php/config.php");

$taskManager = new Tasks();

$taskManager->clean_names();

?>