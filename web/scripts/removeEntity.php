<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$metadata = new \metadata\Metadata($argv[1],$argv[2]);
$metadata->move2SoftDelete();
