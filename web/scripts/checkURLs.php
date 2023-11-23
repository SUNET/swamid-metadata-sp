<?php
# Check and validates 100 URLs
// deepcode ignore FileInclusion:
include __DIR__ . '/../html/include/Metadata.php'; # NOSONAR

$metadata = new Metadata();
$metadata->validateURLs(100);
