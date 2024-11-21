<?php
/* Cleanup Database and remove
    * SoftDeleted entities lastUpdated < 3 months
    * PublishedPending entities lastUpdated < 3 months
    * Shadow entities lastUpdated < 4 months (Should be 3 month + 2-3 days)
*/
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new metadata\Configuration();

// file deepcode ignore FileInclusion:
#include __DIR__ . '/../html/include/Metadata.php'; # NOSONAR

const BIND_STATUS = ':Status';
const BIND_REMOVEDATE = ':RemoveDate';

switch ($argc) {
  case 3 :
    # UserId as 1:st param
    # entityID as 2:nd
    $user = getUser($argv[1]);
    addAccess($user, $argv[2]);
    break;
  case 2 :
    # UserId as 1:st param
    $user = getUser($argv[1]);
    listAccess($user);
    break;
  default :
    # No param list users. Case 1:
    listUsers();
    break;
}

function listUsers() {
  global $config;
  $usersHandler = $config->getDb()->prepare('SELECT `userID`, `fullName` FROM Users ORDER BY `userID`');
  $usersHandler->execute();
  while ($user = $usersHandler->fetch(PDO::FETCH_ASSOC)) {
    printf("%s -> %s\n", $user['userID'], $user['fullName']);
  }
  printf("For a list of entities connected to a user :\n   %s <username>\n", __FILE__);
}

function getUser($userID) {
  global $config;
  $usersHandler = $config->getDb()->prepare('SELECT `id`, `userID`, `fullName` FROM Users WHERE `userID` = :UserID');
  $usersHandler->execute(array('UserID'=> $userID));
  if ($user = $usersHandler->fetch(PDO::FETCH_ASSOC)) {
    return $user;
  } else {
    printf ("User %s is missing!\n", $userID);
    exit;
  }
}

function listAccess(&$user) {
  global $config;
  printf ("User %s has access to : \n", $user['userID']);
  $entitiesAccessHandler = $config->getDb()->prepare("SELECT `entityID`
      FROM EntityUser, Entities
      WHERE EntityUser.`entity_id` = Entities.`id`
        AND EntityUser.`user_id` = :UsersId
        AND `status` = 1
      ORDER BY `entityID`, `status`");
  $entitiesAccessHandler->execute(array('UsersId'=> $user['id']));
  while ($entity = $entitiesAccessHandler->fetch(PDO::FETCH_ASSOC)) {
    printf("%s\n", $entity['entityID']);
  }
  printf("To add access to more entities :\n   %s %s <entityID>\n", __FILE__, $user['userID']);
}

function addAccess(&$user, $entityID) {
  global $config;
  $entitiesHandler = $config->getDb()->prepare("SELECT `id`
    FROM Entities
    WHERE `entityID` = :EntityID
      AND status = 1");
  $entitiesAccessHandler = $config->getDb()->prepare("SELECT *
    FROM EntityUser
    WHERE `entity_id` = :EntityId
      AND `user_id` = :UsersId");
  $entitiesAddAccessHandler = $config->getDb()->prepare("INSERT
    INTO EntityUser
    SET `entity_id` = :EntityId, `user_id` = :UsersId, `approvedBy` = 'Admin', `lastChanged` = NOW()");

  $entitiesHandler->execute(array('EntityID' => $entityID));
  if ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    $entitiesAccessHandler->execute(array('UsersId' => $user['id'], 'EntityId' => $entity['id']));
    if ($entitiesAccessHandler->fetch()) {
      printf ("User %s (%s) already had access to %s.\n", $user['userID'], $user['fullName'], $entityID);
    } else {
      $entitiesAddAccessHandler->execute(array('UsersId' => $user['id'], 'EntityId' => $entity['id']));
      printf ("Added %s (%s) to %s.\n", $user['userID'], $user['fullName'], $entityID);
    }
  } else {
    printf ("Entity with entityID %s is missing!\n", $entityID);
  }
}