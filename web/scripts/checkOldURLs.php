<?php
include "/var/www/html/include/MetadataDisplay.php";

$metadataDisplay = new MetadataDisplay('/var/www/html');
$metadataDisplay->checkOldURLS(30,true);
?>