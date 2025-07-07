<?php
# Updated via puppet/cosmos to metadata.swamid.se
# Used by error.swamid.se to produce helpdesk.php

//Load composer's autoloader
require_once 'vendor/autoload.php';

$config = new \metadata\Configuration();

print "<?php\n\n\$helpdesks = array(\n";

$entityHandler = $config->getDb()->prepare("SELECT `id`, `entityID` FROM `Entities` WHERE `status` = 1 AND `isIdP` = 1 ORDER BY `entityID` ASC");
$displayHandler = $config->getDb()->prepare("SELECT `lang`, `data` FROM `Mdui` WHERE `type` = 'IDPSSO' AND `element`= 'DisplayName' AND `entity_id`= :Id ORDER BY `lang` DESC");
$contactHandler = $config->getDb()->prepare("SELECT `emailAddress` FROM `ContactPerson` WHERE `contactType` = 'support' AND `entity_id`= :Id");
$errorUrlHandler = $config->getDb()->prepare("SELECT `URL` FROM `EntityURLs` WHERE `type` = 'error' AND `entity_id`= :Id");
$displayHandler->bindParam(':Id', $Entity_id);
$contactHandler->bindParam(':Id', $Entity_id);
$errorUrlHandler->bindParam(':Id', $Entity_id);
$entityHandler->execute();
while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
  $Entity_id = $entity['id'];
  printf("  '%s' => array(\n    'feed' => 'SWAMID',\n    'displayname' => array(\n", $entity['entityID']);
  $displayHandler->execute();
  while ($displayName = $displayHandler->fetch(PDO::FETCH_ASSOC)) {
    printf ("      '%s' => '%s',\n", $displayName['lang'], addslashes($displayName['data']));
  }
  print "    ),\n";
  $contactHandler->execute();
  if ($contact = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
    printf ("    'contactperson_email' => '%s',\n", substr($contact['emailAddress'], 7));
  }
  $errorUrlHandler->execute();
  if ($errorURL = $errorUrlHandler->fetch(PDO::FETCH_ASSOC)) {
    printf ("    'errorurl' => '%s',\n", $errorURL['URL']);
  }
  print "  ),\n";
}
print ");\n\n?>\n";
