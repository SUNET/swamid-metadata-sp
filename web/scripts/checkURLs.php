<?php
# Check and validates 100 URLs

//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$common = new \metadata\Common();
$common->validateURLs(100);
