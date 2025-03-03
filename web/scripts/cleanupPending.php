<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new \metadata\Configuration();

$entitiesHandler = $config->getDb()->prepare(
  'SELECT `id`, `entityID` FROM Entities WHERE `status` = 2 ORDER BY lastUpdated ASC, `entityID`');
$entitiesHandler->execute();
while ($pendingEntity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new \metadata\Metadata($pendingEntity['id']);
  if ($metadata->checkPendingIfPublished()) {
    $metadata->movePublishedPending();
    printf ("Cleanup: %s removed from Pending\n", $pendingEntity['entityID']);
  } else {
    printf ("Cleanup: Keeping %s in Pending\n", $pendingEntity['entityID']);
  }
}
