<?php
/* Cleanup Database and remove
    * SoftDeleted entities lastUpdated < 3 months
    * PublishedPending entities lastUpdated < 3 months
    * Shadow entities lastUpdated < 4 months (Should be 3 month + 2-3 days)
*/
// file deepcode ignore FileInclusion:
include __DIR__ . '/../html/config.php'; # NOSONAR
// file deepcode ignore FileInclusion:
include __DIR__ . '/../html/include/Metadata.php'; # NOSONAR

const BIND_STATUS = ':Status';
const BIND_REMOVEDATE = ':RemoveDate';

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error Connecting DB";
}

$removeDate = '1971-01-01';
$removeDateShadow = '1971-01-01';
$flagDates = $db->query('SELECT
  NOW() - INTERVAL 3 MONTH AS `removeDate`,
  NOW() - INTERVAL 4 MONTH AS `removeDateShadow`', PDO::FETCH_ASSOC);
foreach ($flagDates as $dates) {
  # Need to use foreach to fetch row. $flagDates is a PDOStatement
  $removeDate = $dates['removeDate'];
  $removeDateShadow = $dates['removeDateShadow'];
}
$flagDates->closeCursor();

printf ("Cleaning Entities before %s\n", $removeDate);
$entitiesHandler = $db->prepare(
  'SELECT id, `entityID`, `lastUpdated` FROM Entities WHERE `status` = :Status AND `lastUpdated` < :RemoveDate;');
$entitiesHandler->bindValue(BIND_REMOVEDATE, $removeDate);

# Remove SoftDeleted entities
$entitiesHandler->bindValue(BIND_STATUS, 4);
$entitiesHandler->execute();
while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new Metadata($entity['id']);
  $metadata->removeEntity();
  printf (" -> %s Deleted %s\n", $entity['entityID'], $entity['lastUpdated']);
}
$entitiesHandler->closeCursor();

# Remove PendingPublished entities
$entitiesHandler->bindValue(BIND_STATUS, 5);
$entitiesHandler->execute();
while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new Metadata($entity['id']);
  $metadata->removeEntity();
  printf (" -> %s Published %s\n", $entity['entityID'], $entity['lastUpdated']);
}
$entitiesHandler->closeCursor();

# Remove Shadow entities
$entitiesHandler->bindValue(BIND_REMOVEDATE, $removeDateShadow);
$entitiesHandler->bindValue(BIND_STATUS, 6);
$entitiesHandler->execute();
while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new Metadata($entity['id']);
  $metadata->removeEntity();
  printf (" -> %s Shadow %s\n", $entity['entityID'], $entity['lastUpdated']);
}
$entitiesHandler->closeCursor();
