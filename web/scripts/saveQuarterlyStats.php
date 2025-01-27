<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new metadata\Configuration();

// file deepcode ignore FileInclusion:
include __DIR__ . '/../html/include/Metadata.php'; #NOSONAR

$metadata = new Metadata();
$metadata->saveEntitiesStatistics();
