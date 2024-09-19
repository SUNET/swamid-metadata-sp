<?php

$baseDir = dirname($_SERVER['SCRIPT_FILENAME'], 1);
require_once $baseDir . '/../../config.php';

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

header('Content-type: application/json');

$metaObj = new \stdClass();

$entityHandler = $db->prepare('SELECT entityID, publishIn, status FROM Entities WHERE status = 1 AND isIdP = 1 ORDER BY entityID;');
$entityHandler->execute();
while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
  switch ($entity['publishIn']) {
    case 2:
    case 3:
    // SWAMID
    case 4:
    case 5:
    // eduGAIN
    case 6:
    case 7:
    // SWAMID + eduGAIN
      $partObj = new \stdClass();
      $partObj->entityID = $entity['entityID'];
      $entityArray[] = $partObj;
      unset($clubObj);
      break;
    default :
  }
}
$Obj = new \stdClass();
$Obj->meta = $metaObj;
$Obj->objects = $entityArray;
print json_encode($Obj);