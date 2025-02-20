<?php
# Check URLs where lastSeen is older than 30 days and remove if not in use in any entity

//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$common = new \metadata\Common();
$common->checkOldURLS(30,true);
