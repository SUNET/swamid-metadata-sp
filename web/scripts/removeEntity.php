<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

include __DIR__ . '/../html/include/Metadata.php'; #NOSONAR
$metadata = new Metadata($argv[1],$argv[2]);
$metadata->move2SoftDelete();
