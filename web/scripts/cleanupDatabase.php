<?php
/* Cleanup Database and remove
    * SoftDeleted entities lastUpdated < 3 months
    * PublishedPending entities lastUpdated < 3 months
    * Shadow entities lastUpdated < 4 months (Should be 3 month + 2-3 days)
*/
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new \metadata\Configuration();

const BIND_STATUS = ':Status';
const BIND_REMOVEDATE = ':RemoveDate';

$removeDate = '1971-01-01';
$removeDateShadow = '1971-01-01';
$flagDates = $config->getDb()->query('SELECT
  NOW() - INTERVAL 3 MONTH AS `removeDate`,
  NOW() - INTERVAL 4 MONTH AS `removeDateShadow`,
  NOW() - INTERVAL 13 WEEK AS `removePending`,
  NOW() - INTERVAL 9 WEEK AS `removeDraft`', PDO::FETCH_ASSOC);
foreach ($flagDates as $dates) {
  # Need to use foreach to fetch row. $flagDates is a PDOStatement
  $removeDate = $dates['removeDate'];
  $removeDateShadow = $dates['removeDateShadow'];
  $removePending = $dates['removePending'];
  $removeDraft = $dates['removeDraft'];
}
$flagDates->closeCursor();

$entitiesUpdatedHandler = $config->getDb()->prepare(
  'SELECT id, `entityID`, `lastUpdated` FROM Entities WHERE `status` = :Status AND `lastUpdated` < :RemoveDate;');
$entitiesUpdatedHandler->bindValue(BIND_REMOVEDATE, $removeDate);

$entitiesValidatedHandler = $config->getDb()->prepare(
  'SELECT id, `entityID`, `lastValidated` FROM Entities WHERE `status` = :Status AND `lastValidated` < :RemoveDate;');

# Remove SoftDeleted entities
printf ("SoftDeleted Entities before %s\n", $removeDate);
$entitiesUpdatedHandler->bindValue(BIND_STATUS, 4);
$entitiesUpdatedHandler->execute();
while ($entity = $entitiesUpdatedHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new \metadata\Metadata($entity['id']);
  $metadata->removeEntity();
  printf (" -> %s %s\n", $entity['entityID'], $entity['lastUpdated']);
}
$entitiesUpdatedHandler->closeCursor();

# Remove PendingPublished entities
printf ("PendingPublished Entities before %s\n", $removeDate);
$entitiesUpdatedHandler->bindValue(BIND_STATUS, 5);
$entitiesUpdatedHandler->execute();
while ($entity = $entitiesUpdatedHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new \metadata\Metadata($entity['id']);
  $metadata->removeEntity();
  printf (" -> %s %s\n", $entity['entityID'], $entity['lastUpdated']);
}
$entitiesUpdatedHandler->closeCursor();

# Remove Shadow entities
printf ("Shadow Entities before %s\n", $removeDateShadow);
$entitiesUpdatedHandler->bindValue(BIND_REMOVEDATE, $removeDateShadow);
$entitiesUpdatedHandler->bindValue(BIND_STATUS, 6);
$entitiesUpdatedHandler->execute();
while ($entity = $entitiesUpdatedHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new \metadata\Metadata($entity['id']);
  $metadata->removeEntity();
  printf (" -> Shadow %s %s\n", $entity['entityID'], $entity['lastUpdated']);
}
$entitiesUpdatedHandler->closeCursor();

# Remove Pending entities
printf ("Pending Entities before %s\n", $removePending);
$entitiesValidatedHandler->bindValue(BIND_REMOVEDATE, $removePending);
$entitiesValidatedHandler->bindValue(BIND_STATUS, 2);
$entitiesValidatedHandler->execute();
while ($entity = $entitiesValidatedHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new \metadata\Metadata($entity['id']);
  $metadata->removeEntity();
  printf (" -> Pending %s %s\n", $entity['entityID'], $entity['lastValidated']);
}

# Remove Draft entities
printf ("Draft Entities before %s\n", $removeDraft);
$entitiesValidatedHandler->bindValue(BIND_REMOVEDATE, $removeDraft);
$entitiesValidatedHandler->bindValue(BIND_STATUS, 3);
$entitiesValidatedHandler->execute();
while ($entity = $entitiesValidatedHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new \metadata\Metadata($entity['id']);
  $metadata->removeEntity();
  printf (" -> Pending %s %s\n", $entity['entityID'], $entity['lastValidated']);
}

$entitiesValidatedHandler->closeCursor();
