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
if ($userLevel < 20) { exit; }
$displayName = '<div> Logged in as : <br> ' . htmlspecialchars($fullName) . ' (' . htmlspecialchars($EPPN) .')</div>';
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
    case 'organizations' :
      $menuActive = 'organizations';
      showMenu();
      showOrganizationDifference();
      $html->addTableSort('Organizationen-table');
      $html->addTableSort('Organizationsv-table');
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
    printf('          <tr><td><a href=./?removeEntity=%d target="_blank"><i class="fas fa-trash"></i></a>%d</td><td>%s</td><td>%s</td></tr>%s', $entity['id'], $entity['id'], htmlspecialchars($entity['entityID']), $entity['lastValidated'], "\n");
  }
  print HTML_TABLE_END;
}

function showSoftDeletedEntities() {
  global $config;
  $entityHandler = $config->getDb()->prepare('SELECT id, `entityID`, `lastUpdated` FROM Entities WHERE status = 4;');
  $entityHandler->execute();
  printf ('        <h5>Entities in Soft Delete</h5>%s        <table id="softDel-table" class="table table-striped table-bordered">%s          <thead><tr><th>Action</th><th>EntityID</th><th>Removed</th></tr></thead>%s', "\n", "\n", "\n");
  while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    printf('          <tr><td><a href="./?showEntity=%d" target="_blank"><button class="btn btn-outline-success">View</button></a><form action="." method="POST" style="display: inline;"><input type="hidden" name="action" value="createDraft"><input type="hidden" name="Entity" value="%d"><button class="btn btn-outline-primary">Create Draft</button></form></td><td>%s</td><td>%s</td></tr>%s', $entity['id'], $entity['id'], htmlspecialchars($entity['entityID']), $entity['lastUpdated'], "\n");
  }
  print HTML_TABLE_END;
}

function showPublishedPendingEntities() {
  global $config;
  $entityHandler = $config->getDb()->prepare(
    'SELECT `Entities`.`id`, `entityID`, `lastUpdated`, `email`, `Users`.`lastSeen`
    FROM `Entities`, `EntityUser`, `Users`
    WHERE `status` = 5 AND `Entities`.`id` = `entity_id` AND `user_id` = `Users`.`id`
    ORDER BY `Entities`.`id`, `Users`.`lastSeen` DESC;');
  $entityHandler->execute();
  printf ('        <h5>Entities from Pending that is Published</h5>
        <table id="pubPend-table" class="table table-striped table-bordered">
          <thead><tr><th>Id</th><th>EntityID</th><th>Published</th><th>Requester</th></tr></thead>%s',
        "\n");
  $lastId = 0;
  while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    if ($lastId != $entity['id'] ) {
      printf('          <tr><td><a href=./?showEntity=%d target="_blank">%d</a></td><td>%s</td><td>%s</td><td>%s</td></tr>%s',
        $entity['id'], $entity['id'], htmlspecialchars($entity['entityID']), $entity['lastUpdated'], htmlspecialchars($entity['email']), "\n");
      $lastId = $entity['id'];
    }
  }
  print HTML_TABLE_END;
}

function showUsers(){
  global $config;
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
        htmlspecialchars($user['fullName']), htmlspecialchars($user['userID']), "\n");
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
          $statusName, $entity['id'], htmlspecialchars($entity['entityID']), "\n");
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
      $user['id'], htmlspecialchars($user['userID']), htmlspecialchars($user['email']), htmlspecialchars($user['fullName']), $user['lastSeen'], $user['count'], "\n");
  }
  print HTML_TABLE_END;
}

function showValidationOutput() {
  global $config;

  $entitiesHandler = $config->getDb()->prepare('SELECT `id`, `entityID` , `validationOutput` FROM `Entities` WHERE `status` < 4 AND `validationOutput` != ""');
  $entitiesHandler->execute();
  printf ('        <h5>Validation Output</h5>
        <table id="validation-table" class="table table-striped table-bordered">
          <thead><tr><th>entityID</th><th>Validation error</th></tr></thead>%s', "\n");
  while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    printf('          <tr><td><a href=./?showEntity=%d target="_blank">%s</a></td><td>%s</td></tr>%s', $entity['id'], htmlspecialchars($entity['entityID']), str_ireplace("\n", "<br>", $entity['validationOutput']), "\n");
  }
  print HTML_TABLE_END;
}

/**
 * Show Diffenrence betwen Organizations in Metadata and OrganizationInfo table in DB
 *
 * @return void
 */
function showOrganizationDifference() {
  global $config;

  $organizationHandler = $config->getDb()->prepare(
    "SELECT COUNT(id) AS count, `Org1`.`data` AS `OrganizationName`,
      `Org2`.`data` AS `OrganizationDisplayName`, `Org3`.`data` AS `OrganizationURL`
    FROM `Entities`
    LEFT JOIN `Organization` Org1
      ON `Entities`.`id` = `Org1`.`entity_id` AND `Org1`.`element` = 'OrganizationName' AND `Org1`.`lang` = :Lang
    LEFT JOIN `Organization` Org2
      ON `Entities`.`id` = `Org2`.`entity_id` AND `Org2`.`element` = 'OrganizationDisplayName'
      AND `Org2`.`lang` = :Lang
    LEFT JOIN `Organization` Org3
      ON `Entities`.`id` = `Org3`.`entity_id` AND `Org3`.`element` = 'OrganizationURL' AND `Org3`.`lang` = :Lang
    WHERE `Entities`.`status` = 1 AND `Entities`.`publishIn` > 1
    GROUP BY `OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`
    ORDER BY `OrganizationName` COLLATE utf8mb4_swedish_ci, `OrganizationDisplayName` COLLATE utf8mb4_swedish_ci, `OrganizationURL` COLLATE utf8mb4_swedish_ci;");

  $orgInfoHandler = $config->getDb()->prepare(
    'SELECT `OrganizationInfo_id`, `OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`
    FROM OrganizationInfoData
    WHERE `lang`= :Lang
    ORDER BY `OrganizationName` COlLATE utf8mb4_swedish_ci, `OrganizationDisplayName` COlLATE utf8mb4_swedish_ci, `OrganizationURL` COlLATE utf8mb4_swedish_ci;');
  printf ('    <h5>Comparing of Organizations in Metadata and Members/OrginfoTable</h5>%s', "\n");
  $languages = $config->getFederation()['languages'];
  foreach ($languages as $lang) {
    if (count($languages)>1) {
      printf ('    <h6>Lang = %s</h6>%s', $lang, "\n");
    }
    printf('    <table id="Organization%s-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>OrganizationName</th>
          <th>OrganizationDisplayName</th>
          <th>OrganizationURL</th>
          <th>Count</th>
        </tr>
      </thead>%s',
      $lang, "\n");
    $organizationHandler->execute(array('Lang' => $lang));
    $orgInfoHandler->execute(array('Lang' => $lang));
    $orgInfo = $orgInfoHandler->fetch(PDO::FETCH_ASSOC);
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($organization['OrganizationName'] != '' && $organization['OrganizationDisplayName'] != '' &&
        $organization['OrganizationURL'] != '') {
        $orgString = $organization['OrganizationName'] . $organization['OrganizationDisplayName'] . $organization['OrganizationURL'];
        $newOrgInfo = true;
        $orgsSame = false;
        while($newOrgInfo && !$orgsSame && $orgInfo) {
          $orgInfoString = $orgInfo['OrganizationName'] . $orgInfo['OrganizationDisplayName'] . $orgInfo['OrganizationURL'];
          if ($orgInfoString < $orgString) {
            printNewOrgInfo($orgInfo);
            $orgInfo = $orgInfoHandler->fetch(PDO::FETCH_ASSOC);
          } elseif ($orgInfoString == $orgString) {
            $orgsSame = true;
            $orgInfo = $orgInfoHandler->fetch(PDO::FETCH_ASSOC);
          } else {
            $newOrgInfo = false;
          }
        }

        printf ('      <tr class="table-%s">
        <td>%s</td>
        <td>%s</td>
        <td>%s</td>
        <td><a href=".?action=OrganizationsInfo&name=%s&display=%s&url=%s&lang=%s">%d</a></td>
      </tr>%s',
          $orgsSame ? 'success' : 'warning',
          htmlspecialchars($organization['OrganizationName']), htmlspecialchars($organization['OrganizationDisplayName']),
          htmlspecialchars($organization['OrganizationURL']),
          urlencode($organization['OrganizationName']), urlencode($organization['OrganizationDisplayName']),
          urlencode($organization['OrganizationURL']), $lang,
          $organization['count'], "\n");
      }
    }
    print "    </table>\n";
  }
}

function printNewOrgInfo($organization) {
  printf ('      <tr class="table-danger">
        <td>%s</td>
        <td>%s</td>
        <td>%s</td>
        <td><a href="./?action=Members&tab=organizations&id=%d&showAllOrgs#org-%d" target="_blank">Edit</a>(%d)</td>
      </tr>%s',
          htmlspecialchars($organization['OrganizationName']), htmlspecialchars($organization['OrganizationDisplayName']),
          htmlspecialchars($organization['OrganizationURL']),
          $organization['OrganizationInfo_id'], $organization['OrganizationInfo_id'], $organization['OrganizationInfo_id'], "\n");
}


/**
 * Shows menu row
 *
 * @return void
 */
function showMenu() {
  global $menuActive;

  print "\n    ";
  printf('<a href="?action=softDel"><button type="button" class="btn btn%s-primary">SoftDeleted</button></a>', $menuActive == 'softDel' ? '' : HTML_OUTLINE);
  printf('<a href="?action=pubPend"><button type="button" class="btn btn%s-primary">Published</button></a>', $menuActive == 'pubPend' ? '' : HTML_OUTLINE);
  printf('<a href="?action=shadow"><button type="button" class="btn btn%s-primary">Shadow</button></a>', $menuActive == 'shadow' ? '' : HTML_OUTLINE);
  printf('<a href="?action=users"><button type="button" class="btn btn%s-primary">Users</button></a>', $menuActive == 'users' ? '' : HTML_OUTLINE);
  printf('<a href="?action=validation"><button type="button" class="btn btn%s-primary">Validation</button></a>', $menuActive == 'validation' ? '' : HTML_OUTLINE);
  printf('<a href="?action=organizations"><button type="button" class="btn btn%s-primary">Organizations</button></a>', $menuActive == 'organizations' ? '' : HTML_OUTLINE);
  print "\n    <br>\n    <br>\n";
}
