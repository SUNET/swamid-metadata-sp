<?php
include "/var/www/html/include/Metadata.php";

$metadata = new Metadata('/var/www/html');
$metadata->checkOldURLS(30,true);
?>