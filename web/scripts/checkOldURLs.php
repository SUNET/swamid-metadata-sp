<?php
# Check URLs where lastSeen is older than 30 days and remove if not in use in any entity

//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

// deepcode ignore FileInclusion:
require_once __DIR__ . '/../html/include/Metadata.php'; # NOSONAR

// deepcode ignore FileInclusion:
$metadata = new Metadata();
$metadata->checkOldURLS(30,true);
