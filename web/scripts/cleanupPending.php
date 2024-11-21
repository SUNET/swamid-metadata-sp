<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new metadata\Configuration();

// file deepcode ignore FileInclusion:
include __DIR__ . '/../html/include/Metadata.php'; # NOSONAR

$entitiesHandler = $config->getDb()->prepare(
  'SELECT `id`, `entityID` FROM Entities WHERE `status` = 2 ORDER BY lastUpdated ASC, `entityID`');
$entitiesHandler->execute();
while ($pendingEntity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
  $metadata = new Metadata($pendingEntity['id']);
  if ($metadata->checkPendingIfPublished()) {
    $metadata->movePublishedPending();
    printf ("Cleanup: %s removed from Pending\n", $pendingEntity['entityID']);
  } else {
    printf ("Cleanup: Keeping %s in Pending\n", $pendingEntity['entityID']);
  }
}
