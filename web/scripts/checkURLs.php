<?php
$baseDir = dirname($_SERVER['PHP_SELF'], 2) . '/html';
# Check and validates 100 URLs
// deepcode ignore FileInclusion: 
include $baseDir.'/include/Metadata.php';

$metadata = new Metadata($baseDir);
$metadata->validateURLs(100);
?>
