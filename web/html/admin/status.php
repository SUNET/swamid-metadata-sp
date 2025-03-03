<?php

const HTML_OUTLINE = '-outline';
const HTML_TABLE_END = "        </table>\n";

//Load composer's autoloader
require_once '../vendor/autoload.php';

$config = new \metadata\Configuration();

$html = new \metadata\HTML();

if (isset($_SERVER['eduPersonPrincipalName'])) {
  $EPPN = $_SERVER['eduPersonPrincipalName'];
} elseif (isset($_SERVER['subject-id'])) {
  $EPPN = $_SERVER['subject-id'];
} else {
  $errors .= 'Missing eduPersonPrincipalName/subject-id in SAML response ' . str_replace(array('ERRORURL_CODE', 'ERRORURL_CTX'), array('IDENTIFICATION_FAILURE', 'eduPersonPrincipalName'), $errorURL);
}

if (isset($_SERVER['displayName'])) {
  $fullName = $_SERVER['displayName'];
} elseif (isset($_SERVER['givenName'])) {
  $fullName = $_SERVER['givenName'];
  if (isset($_SERVER['sn'])) {
    $fullName .= ' ' .$_SERVER['sn'];
  }
} else {
  $fullName = '';
}

$userLevel = $config->getUserLevels()[$EPPN] ?? 1;
if ($userLevel < 20) { exit; };
$displayName = '<div> Logged in as : <br> ' . $fullName . ' (' . $EPPN .')</div>';
$html->setDisplayName($displayName);

$html->showHeaders('');
if (isset($_GET['action'])) {
  switch($_GET['action']) {
    case 'shadow' :
      $menuActive = 'shadow';
      showMenu();
      showShadowEntities();
      $html->addTableSort('shadow-table');
      break;
    case 'softDel' :
      $menuActive = 'softDel';
      showMenu();
      showSoftDeletedEntities();
      $html->addTableSort('softDel-table');
      break;
    case 'pubPend' :
      $menuActive = 'pubPend';
      showMenu();
      showPublishedPendingEntities();
      $html->addTableSort('pubPend-table');
      break;
    case 'users' :
      $menuActive = 'users';
      showMenu();
      showUsers();
      $html->addTableSort('user-table');
      break;
    case 'validation' :
      $menuActive = 'validation';
      showMenu();
      showValidationOutput();
      $html->addTableSort('validation-table');
      break;
    default :
  }
} else {
  showMenu();
}

$html->showFooter(array());
# End of page

function showShadowEntities() {
  global $config;
  $entityHandler = $config->getDb()->prepare('SELECT id, `entityID`, `lastValidated` FROM Entities WHERE status = 6 AND id NOT IN (SELECT publishedId FROM Entities WHERE `status`=5 OR `status`=2);');
  $entityHandler->execute();
  printf ('        <h5>Entities only in shadow</h5>%s        <table id="shadow-table" class="table table-striped table-bordered">%s          <thead><tr><th>Id</th><th>EntityID</th><th>Created</th></tr></thead>%s', "\n", "\n", "\n");
  while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    printf('          <tr><td><a href=./?removeEntity=%d target="_blank"><i class="fas fa-trash"></i></a>%d</td><td>%s</td><td>%s</td></tr>%s', $entity['id'], $entity['id'], $entity['entityID'], $entity['lastValidated'], "\n");
  }
  print HTML_TABLE_END;
}

function showSoftDeletedEntities() {
  global $config;;
  $entityHandler = $config->getDb()->prepare('SELECT id, `entityID`, `lastUpdated` FROM Entities WHERE status = 4;');
  $entityHandler->execute();
  printf ('        <h5>Entities in Soft Delete</h5>%s        <table id="softDel-table" class="table table-striped table-bordered">%s          <thead><tr><th>Action</th><th>EntityID</th><th>Removed</th></tr></thead>%s', "\n", "\n", "\n");
  while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    printf('          <tr><td><a href=./?showEntity=%d target="_blank">View</a> | <a href=./?action=createDraft&Entity=%d target="_blank">Create Draft</a></td><td>%s</td><td>%s</td></tr>%s', $entity['id'], $entity['id'], $entity['entityID'], $entity['lastUpdated'], "\n");
  }
  print HTML_TABLE_END;
}

function showPublishedPendingEntities() {
  global $config;;
  $entityHandler = $config->getDb()->prepare(
    'SELECT Entities.`id`, `entityID`, `lastUpdated`, `email`
    FROM Entities, EntityUser, Users
    WHERE `status` = 5 AND Entities.`id` = `entity_id` AND `user_id` = Users.`id`;');
  $entityHandler->execute();
  printf ('        <h5>Entities from Pending that is Published</h5>
        <table id="pubPend-table" class="table table-striped table-bordered">
          <thead><tr><th>Id</th><th>EntityID</th><th>Published</th><th>Requester</th></tr></thead>%s',
        "\n");
  while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    printf('          <tr><td><a href=./?showEntity=%d target="_blank">%d</a></td><td>%s</td><td>%s</td><td>%s</td></tr>%s', $entity['id'], $entity['id'], $entity['entityID'], $entity['lastUpdated'], $entity['email'], "\n");
  }
  print HTML_TABLE_END;
}

function showUsers(){
  global $config;;
  if (isset($_GET['user'])) {
    $userHandler = $config->getDb()->prepare('SELECT `userID`, `fullName` FROM Users WHERE id = :Id');
    $userHandler->bindValue(':Id', $_GET['user']);
    $userHandler->execute();
    if ($user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
      $entitiesHandler = $config->getDb()->prepare('SELECT `id`, `entityID`, `status`
        FROM Entities, EntityUser
        WHERE entity_id = Entities.id AND user_id = :Id
        ORDER BY `status`, `entityID`');
      $entitiesHandler->bindValue(':Id', $_GET['user']);
      $entitiesHandler->execute();
      printf ('        <h5>%s (%s)</h5>
        <table id="entity-table" class="table table-striped table-bordered">
          <thead><tr><th>Status</th><th>EntityID</th></tr></thead>%s',
        $user['fullName'], $user['userID'], "\n");
      while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
        switch ($entity['status']) {
          case 1 :
             $statusName = 'Published';
            break;
          case 2 :
            $statusName = 'Pending';
            break;
          case 3 :
            $statusName = 'Draft';
            break;
          case 4 :
            $statusName = 'Deleted';
            break;
          case 5 :
            $statusName = 'PublishedPending';
            break;
          case 6 :
            $statusName = 'Shadow';
            break;
          default :
            $statusName = 'Unknown';
        }
        printf('          <tr><td>%s</td><td><a href=./?showEntity=%d target="_blank">%s<a></td></tr>%s',
          $statusName, $entity['id'], $entity['entityID'], "\n");
      }
      print HTML_TABLE_END;
    }
  }

  $usersHandler = $config->getDb()->prepare('SELECT `id`, `userID`, `email`, `fullName`, `lastSeen`, COUNT(entity_id) AS count
    FROM Users  LEFT JOIN EntityUser ON Users.id = user_id GROUP BY id;');
  $usersHandler->execute();
  printf ('        <h5>Users</h5>
        <table id="user-table" class="table table-striped table-bordered">
          <thead><tr><th>UserID</th><th>Email</th><th>Name</th><th>Last seen</th><th>Number of entities</th></tr></thead>%s', "\n");
  while ($user = $usersHandler->fetch(PDO::FETCH_ASSOC)) {
    printf('          <tr><td><a href="?action=users&user=%d">%s<a></td></td><td>%s</td><td>%s</td><td>%s</td><td>%d</td></tr>%s',
      $user['id'], $user['userID'], $user['email'], $user['fullName'], $user['lastSeen'], $user['count'], "\n");
  }
  print HTML_TABLE_END;
}

function showValidationOutput() {
  global $config;;

  $entitiesHandler = $config->getDb()->prepare('SELECT `id`, `entityID` , `validationOutput` FROM `Entities` WHERE `status` < 4 AND `validationOutput` != ""');
  $entitiesHandler->execute();
  printf ('        <h5>Validation Output</h5>
        <table id="validation-table" class="table table-striped table-bordered">
          <thead><tr><th>entityID</th><th>Validation error</th></tr></thead>%s', "\n");
  while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    printf('          <tr><td><a href=./?showEntity=%d target="_blank">%s</a></td><td>%s</td></tr>%s', $entity['id'], $entity['entityID'], str_ireplace("\n", "<br>", $entity['validationOutput']), "\n");
  }
  print HTML_TABLE_END;
}

####
# Shows menu row
####
function showMenu() {
  global $menuActive;

  print "\n    ";
  printf('<a href="?action=softDel"><button type="button" class="btn btn%s-primary">SoftDeleted</button></a>', $menuActive == 'softDel' ? '' : HTML_OUTLINE);
  printf('<a href="?action=pubPend"><button type="button" class="btn btn%s-primary">Published</button></a>', $menuActive == 'pubPend' ? '' : HTML_OUTLINE);
  printf('<a href="?action=shadow"><button type="button" class="btn btn%s-primary">Shadow</button></a>', $menuActive == 'shadow' ? '' : HTML_OUTLINE);
  printf('<a href="?action=users"><button type="button" class="btn btn%s-primary">Users</button></a>', $menuActive == 'users' ? '' : HTML_OUTLINE);
  printf('<a href="?action=validation"><button type="button" class="btn btn%s-primary">Validation</button></a>', $menuActive == 'validation' ? '' : HTML_OUTLINE);
  print "\n    <br>\n    <br>\n";
}
