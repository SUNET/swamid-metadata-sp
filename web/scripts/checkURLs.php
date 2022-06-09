<?php
# Check and validates 100 URLs
include "/var/www/html/include/Metadata.php";

$metadata = new Metadata('/var/www/html');
$metadata->validateURLs(100);
?>
