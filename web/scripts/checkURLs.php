<?php
# Check and validates 100 URLs

//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

// deepcode ignore FileInclusion:
include __DIR__ . '/../html/include/Metadata.php'; # NOSONAR

$metadata = new Metadata();
$metadata->validateURLs(100);
