<?php

const HTML_OUTLINE = '-outline';
const TEXT_PLAIN = 'Content-Type: text/plain; charset=utf-8';
const ACCEPT_SAML_METADATA = 'Accept: application/samlmetadata+xml';

//Load composer's autoloader
require_once 'vendor/autoload.php';

$config = new \metadata\Configuration();

$html = new \metadata\HTML();

$display = new \metadata\MetadataDisplay();

if (isset($_GET['showEntity'])) {
  showEntity($_GET['showEntity']);
} elseif (isset($_GET['showEntityID'])) {
  showEntity($_GET['showEntityID'],true);
} elseif (isset($_GET['rawXML'])) {
  $display->showRawXML($_GET['rawXML']);
} elseif (isset($_GET['rawXMLEntityID'])) {
  $display->showRawXML($_GET['rawXMLEntityID'], true);
} elseif (isset($_GET['show'])) {
  switch($_GET['show']) {
    case 'Pending' :
      showPendingQueue();
      break;
    case 'RemoveRequested' :
      showRemoveQueue();
      break;
    case 'Info' :
      showInfo();
      break;
    case 'InterIdP' :
      showInterfederation('IDP');
      break;
    case 'InterSP' :
      showInterfederation('SP');
      break;
    case 'EntityFromMDQ' :
      if (isset($_GET['entityID'])) {
        showEntityFromMDQ($_GET['entityID']);
      } else {
        showInfo();
      }
      break;
    case 'feed' :
      if (isset($_GET['id'])) {
        showFeed($_GET['id']);
      } elseif (isset($_GET['entityID'])) {
        showFeed($_GET['entityID'], true);
      } else {
        exit; // intentionally empty output
      }
      break;
    default :
      showEntityList($_GET['show']);
  }
} else {
  showInfo();
}

$html->showFooter($display->getCollapseIcons());
# End of page

####
# Shows EntityList
####
function showEntityList($show) {
  global $config, $html;

  $query = isset($_GET['query']) ? $_GET['query'] : '';

  switch ($show) {
    case 'IdP' :
      $html->showHeaders('IdP:s');
      $entities = $config->getDb()->prepare(
        "SELECT `id`, `entityID`, `publishIn`, `data` AS OrganizationName
        FROM Entities
        LEFT JOIN Organization ON `entity_id` = `id`
          AND `element` = 'OrganizationName' AND `lang` = 'en'
        WHERE `status` = 1 AND `isIdP` = 1 AND `entityID` LIKE :Query
        ORDER BY `entityID` ASC");
      showMenu('IdPs', $query);
      $extraTH = '<th>AL1</th><th>AL2</th><th>AL3</th><th>SIRTFI</th><th>SIRTFI2</th><th>Hide</th>';
      break;
    case 'SP' :
      $html->showHeaders('SP:s');
      $entities = $config->getDb()->prepare(
        "SELECT `id`, `entityID`, `publishIn`, `data` AS OrganizationName
        FROM Entities
        LEFT JOIN Organization ON `entity_id` = `id`
          AND `element` = 'OrganizationName' AND `lang` = 'en'
        WHERE `status` = 1 AND `isSP` = 1 AND `entityID` LIKE :Query
        ORDER BY `entityID` ASC");
      showMenu('SPs', $query);
      $extraTH = sprintf(
        '<th>Anon</th><th>Pseuso</th><th>Pers</th><th>CoCo v1</th><th>CoCo v2</th><th>R&S</th><th>ESI</th><th>SIRTFI</th><th>SIRTFI2</th>');
      break;
    case 'All' :
      $html->showHeaders('All');
      $entities = $config->getDb()->prepare(
        "SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `data` AS OrganizationName
        FROM Entities
        LEFT JOIN Organization ON `entity_id` = `id`
          AND `element` = 'OrganizationName' AND `lang` = 'en'
        WHERE `status` = 1 AND `entityID` LIKE :Query
        ORDER BY `entityID` ASC");
      showMenu('all', $query);
      $extraTH = '<th>IdP</th><th>SP</th>';
      break;
    default :
      $html->showHeaders('Error');
      print 'Show what ??????';
      return;
  }
  printf ('    <div class="table-responsive"><table id="entities-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>
            <form>
              entityID <input type="text" name="query" value="%s">
              <input type="hidden" name="show" value="%s">
              <input type="submit" value="Filter">
            </form>
          </th>
          <th>Published in</th>
          <th>DisplayName</th>
          <th>OrganizationName</th>
          %s
        </tr>
      </thead>%s'
    , htmlspecialchars($query), htmlspecialchars($show), $extraTH, "\n");
  $html->addTableSort('entities-table');
  $entities->bindValue(':Query', "%".$query."%");
  showList($entities, $show);
}

####
# Shows menu row
####
function showMenu($menuActive, $query = '') {
  global $config;
  $federation = $config->getFederation();

  print "\n    ";
  $query = $query == '' ? '' : '&query=' . urlencode($query);
  printf('<a href="./?show=All%s"><button type="button" class="btn btn%s-primary">All in %s</button></a>', $query, $menuActive == 'all' ? '' : HTML_OUTLINE, $federation['displayName']);
  printf('<a href="./?show=IdP%s"><button type="button" class="btn btn%s-primary">IdP in %s</button></a>', $query, $menuActive == 'IdPs' ? '' : HTML_OUTLINE, $federation['displayName']);
  printf('<a href="./?show=SP%s"><button type="button" class="btn btn%s-primary">SP in %s</button></a>', $query, $menuActive == 'SPs' ? '' : HTML_OUTLINE, $federation['displayName']);
  printf('<a href="./?show=InterIdP"><button type="button" class="btn btn%s-primary">IdP via interfederation</button></a>', $menuActive == 'fedIdPs' ? '' : HTML_OUTLINE);
  printf('<a href="./?show=InterSP"><button type="button" class="btn btn%s-primary">SP via interfederation</button></a>', $menuActive == 'fedSPs' ? '' : HTML_OUTLINE);
  printf('<a href="./?show=Info%s"><button type="button" class="btn btn%s-primary">Info</button></a>', $query, $menuActive == 'info' ? '' : HTML_OUTLINE);
  print "\n";
}

####
# Shows Entity information
####
function showEntity($entity_id, $urn = false)  {
  global $config, $html;
  $display = $config->getExtendedClass('MetadataDisplay');
  $federation = $config->getFederation();
  $entityHandler = $urn ?
    $config->getDb()->prepare('SELECT `id`, `entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `publishedId`
      FROM Entities WHERE entityID = :Id AND status = 1;') :
    $config->getDb()->prepare('SELECT `id`, `entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `publishedId`
      FROM Entities WHERE id = :Id;');
  $publishArray = array();
  $publishArrayOld = array();

  $entityHandler->bindParam(':Id', $entity_id);
  $entityHandler->execute();
  if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    $entities_id = $entity['id'];
    $html->setDestination('?showEntity='.$entities_id);
    if (($entity['publishIn'] & 2) == 2) { $publishArray[] = $federation['displayName']; }
    if (($entity['publishIn'] & 4) == 4) { $publishArray[] = 'eduGAIN'; }
    if ($entity['status'] > 1 && $entity['status'] < 6) {
      if ($entity['publishedId'] > 0) {
        $entityHandlerOld = $config->getDb()->prepare('SELECT `id`, `isIdP`, `isSP`, `publishIn`
          FROM Entities
          WHERE `id` = :Id AND `status` = 6;');
        $entityHandlerOld->bindParam(':Id', $entity['publishedId']);
        $headerCol2 = 'Old metadata - when requested publication';
      } else {
        $entityHandlerOld = $config->getDb()->prepare('SELECT `id`, `isIdP`, `isSP`, `publishIn`
          FROM Entities
          WHERE `entityID` = :Id AND `status` = 1;');
        $entityHandlerOld->bindParam(':Id', $entity['entityID']);
        $headerCol2 = 'Old metadata - published now';
      }
      $entityHandlerOld->execute();
      if ($entityOld = $entityHandlerOld->fetch(PDO::FETCH_ASSOC)) {
        $oldEntity_id = $entityOld['id'];
        if (($entityOld['publishIn'] & 2) == 2) { $publishArrayOld[] = $federation['displayName']; }
        if (($entityOld['publishIn'] & 4) == 4) { $publishArrayOld[] = 'eduGAIN'; }
      } else {
        $oldEntity_id = 0;
      }
      switch ($entity['status']) {
        case 2:
          $headerCol1 = 'Waiting for publishing';
          break;
        case 3:
          # Draft
          $headerCol1 = 'New metadata';
          break;
        case 4:
          # Soft Delete
          $headerCol1 = 'Deleted metadata';
          $oldEntity_id = 0;
          break;
        case 5:
          # Pending that have been published
        case 6:
          # Copy of published used to compare Pending
          $headerCol1 = 'Already published metadata (might not be the latest!)';
          break;
        default :
          $headerCol1 = '';
      }
    } else {
      $headerCol1 = '';
      $oldEntity_id = 0;
    }
    $html->showHeaders($entity['entityID']);
    showMenu('',''); ?>

    <div class="row">
      <div class="col">
        <h3>entityID = <?=htmlspecialchars($entity['entityID'])?></h3>
      </div>
    </div><?php $display->showStatusbar($entities_id); ?>

    <div class="row">
      <div class="col">
        <?=($headerCol1 <>'') ? "<h3>" . $headerCol1 . "</h3>\n" : ''; ?>
        Published in : <?php
    print implode (', ', $publishArray);
    if ($oldEntity_id > 0) { ?>

      </div>
      <div class="col">
        <h3><?=$headerCol2?></h3>
        Published in : <?php
      print implode (', ', $publishArrayOld);
    } ?>

      </div>
    </div><?php
    $display->showEntityAttributes($entities_id, $oldEntity_id);
    if ($entity['isIdP'] ) { $display->showIdP($entities_id, $oldEntity_id); }
    if ($entity['isSP'] ) { $display->showSp($entities_id, $oldEntity_id); }
    if ($entity['isAA'] ) { $display->showAA($entities_id, $oldEntity_id); }
    $display->showOrganization($entities_id, $oldEntity_id);
    $display->showContacts($entities_id, $oldEntity_id);
    if ($entity['status'] == 1 && $federation['mdqBaseURL']) { $display->showMdqUrl($entity['entityID']); }
    $display->showXML($entities_id);
  } else {
    $html->showHeaders('NotFound');
    print "Can't find Entity";
  }
}

####
# Shows a list of entitys
####
function showList($entities, $show) {
  global $config;
  $federation = $config->getFederation();
  $entityAttributesHandler = $config->getDb()->prepare('SELECT * FROM `EntityAttributes` WHERE `entity_id` = :Id;');
  $mduiHandler = $config->getDb()->prepare("SELECT `data` FROM `Mdui`
    WHERE `element` = 'DisplayName' AND `entity_id` = :Id
    ORDER BY `type`, `lang`;");

  $countSWAMID = 0;
  $counteduGAIN = 0;
  $countECanon = 0;
  $countECpseuso = 0;
  $countECpers = 0;
  $countECcocov1 = 0;
  $countECcocov2 = 0;
  $countECrs = 0;
  $countECesi = 0;
  $countHideFromDisc = 0;
  $countECSanon = 0;
  $countECSpseuso = 0;
  $countECSpers = 0;
  $countECScocov1 = 0;
  $countECScocov2 = 0;
  $countECSrs = 0;
  $countECSesi = 0;
  $countAL1 = 0;
  $countAL2 = 0;
  $countAL3 = 0;
  $countSIRTFI = 0;
  $countSIRTFI2 = 0;

  $entities->execute();
  while ($row = $entities->fetch(PDO::FETCH_ASSOC)) {
    $isAL1 = '';
    $isAL2 = '';
    $isAL3 = '';
    $isAnon = '';
    $isPseuso = '';
    $isPers = '';
    $isSIRTFI = '';
    $isSIRTFI2 = '';
    $isCocov1 = '';
    $isCocov2 = '';
    $isRS = '';
    $isESI = '';
    $hasHide = '';

    $mduiHandler->bindValue(':Id', $row['id']);
    $mduiHandler->execute();
    if ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $displayName = $mdui['data'];
    } else {
      $displayName = '';
    }
    $entityAttributesHandler->bindValue(':Id', $row['id']);
    $entityAttributesHandler->execute();
    while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      switch ($attribute['type']) {
        case 'entity-category' :
          switch ($attribute['attribute']) {
            case 'https://refeds.org/category/anonymous' :
              $countECanon ++;
              $isAnon = 'X';
              break;
            case 'https://refeds.org/category/code-of-conduct/v2' :
              $countECcocov2 ++;
              $isCocov2 = 'X';
              break;
            case 'https://refeds.org/category/pseudonymous' :
              $countECpseuso ++;
              $isPseuso = 'X';
              break;
            case 'https://refeds.org/category/personalized' :
              $countECpers ++;
              $isPers = 'X';
              break;
            case 'http://refeds.org/category/research-and-scholarship' : # NOSONAR Should be http://
              $countECrs ++;
              $isRS = 'X';
              break;
            case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' : # NOSONAR Should be http://
              $countECcocov1 ++;
              $isCocov1 = 'X';
              break;
            case 'https://myacademicid.org/entity-categories/esi' :
              $countECesi ++;
              $isESI = 'X';
              break;
            case 'http://refeds.org/category/hide-from-discovery' : # NOSONAR Should be http://
              $countHideFromDisc ++;
              $hasHide = 'X';
              break;
            default :
          }
          break;
        case 'entity-category-support' :
          switch ($attribute['attribute']) {
            case 'https://refeds.org/category/anonymous' :
              $countECSanon ++;
              break;
            case 'https://refeds.org/category/code-of-conduct/v2' :
              $countECScocov2 ++;
              break;
            case 'https://refeds.org/category/personalized' :
              $countECSpers ++;
              break;
            case 'https://refeds.org/category/pseudonymous' :
              $countECSpseuso ++;
              break;
            case 'http://refeds.org/category/research-and-scholarship' : # NOSONAR Should be http://
              $countECSrs ++;
              break;
            case 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' : # NOSONAR Should be http://
              $countECScocov1 ++;
              break;
            case 'https://myacademicid.org/entity-categories/esi' :
              $countECSesi ++;
              break;
            default :
          }
          break;
        case 'assurance-certification' :
          switch ($attribute['attribute']) {
            case 'http://www.swamid.se/policy/assurance/al1' : # NOSONAR Should be http://
              $countAL1 ++;
              $isAL1 = 'X';
              break;
            case 'http://www.swamid.se/policy/assurance/al2' : # NOSONAR Should be http://
              $countAL2 ++;
              $isAL2 = 'X';
              break;
            case 'http://www.swamid.se/policy/assurance/al3' : # NOSONAR Should be http://
              $countAL3 ++;
              $isAL3 = 'X';
              break;
            case 'https://refeds.org/sirtfi' :
              $countSIRTFI ++;
              $isSIRTFI = 'X';
              break;
            case 'https://refeds.org/sirtfi2' :
              $countSIRTFI2 ++;
              $isSIRTFI2 = 'X';
              break;
            default :
          }
        default :
      }
    }
    switch ($row['publishIn']) {
      case 2 :
      case 3 :
        $countSWAMID ++;
        $registeredIn = $federation['displayName'];
        break;
      case 6 :
      case 7 :
        $countSWAMID ++;
        $counteduGAIN ++;
        $registeredIn = $federation['displayName']. ' + eduGAIN';
        break;
      default :
        $registeredIn = '';
    }
    printf ('      <tr>
        <td><a href=".?showEntity=%s"><span class="text-truncate">%s</span></a></td>
        <td>%s</td>
        <td>%s</td>
        <td>%s</td>',
      $row['id'], htmlspecialchars($row['entityID']), $registeredIn, htmlspecialchars($displayName), htmlspecialchars($row['OrganizationName']));

    switch ($show) {
      case 'IdP' :
        printf ('
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>', $isAL1, $isAL2, $isAL3, $isSIRTFI, $isSIRTFI2, $hasHide);
        break;
      case 'SP' :
        printf ('
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>',
          $isAnon, $isPseuso, $isPers, $isCocov1, $isCocov2, $isRS, $isESI, $isSIRTFI, $isSIRTFI);
        break;
      case 'All' :
        printf("\n        %s", $row['isIdP'] ? '<td class="text-center">X</td>' : '<td></td>');
        printf("\n        %s", $row['isSP'] ? '<td class="text-center">X</td>' : '<td></td>');
        break;
      default :
    }
    print "\n      </tr>\n";
  } ?>
    </table></div>
    <h4>Statistics</h4>
    <table class="table table-striped table-bordered">
      <caption>Table of Entities statistics</caption>
      <tr>
        <th id="" rowspan="2">&nbsp;Registered in</th>
        <th id=""><?= $federation['displayName'] ?></th><td><?=$countSWAMID?></td>
      </tr>
      <tr><th id="">eduGAIN-Export</th><td><?=$counteduGAIN?></td></tr><?php
  if ($show == 'All' || $show == 'SP') { ?>

      <tr>
        <th id="ecInPproduction" rowspan="8">Entity Categories in production</th>
        <th id="ecAnonymous">Anonymous</th><td><?=$countECanon?></td>
      </tr>
      <tr><th id="ecPseudonymous">Pseudonymous</th><td><?=$countECpseuso?></td></tr>
      <tr><th id="ecPersonalized">Personalized</th><td><?=$countECpers?></td></tr>
      <tr><th id="ecCocov1">CoCo v1</th><td><?=$countECcocov1?></td></tr>
      <tr><th id="ecCocov2">CoCo v2</th><td><?=$countECcocov2?></td></tr>
      <tr><th id="ecRandS">R&S</th><td><?=$countECrs?></td></tr>
      <tr><th id="ecesi">ESI</th><td><?=$countECesi?></td></tr>
      <tr><th id="ecHideFromDisco">DS-hide </th><td><?=$countHideFromDisc?></td></tr><?php
  } else {
    printf('
      <tr>
        <th id="ecInPproduction">Entity Categories in production</th>
        <th id="ecHideFromDisco">DS-hide </th><td>%d</td>
      </tr>%s', $countHideFromDisc, "\n");
  }
  if ($show == 'All' || $show == 'IdP') { ?>
      <tr>
        <th id="ecsInPproduction" rowspan="7">Support Categorys in production</th>
        <th id="ecsAnonymous">Anonymous</th><td><?=$countECSanon?></td>
      </tr>
      <tr><th id="ecsPseudonymous">Pseudonymous</th><td><?=$countECSpseuso?></td></tr>
      <tr><th id="ecsPersonalized">Personalized</th><td><?=$countECSpers?></td></tr>
      <tr><th id="ecsCocov1">CoCo v1</th><td><?=$countECScocov1?></td></tr>
      <tr><th id="ecsCocov2">CoCo v2</th><td><?=$countECScocov2?></td></tr>
      <tr><th id="ecsRandS">R&S</th><td><?=$countECSrs?></td></tr>
      <tr><th id="ecsesi">ESI</th><td><?=$countECSesi?></td></tr>
      <tr>
        <th id="acal" rowspan="5">Assurance profiles in production</th>
        <th id="acal1">AL1</th><td><?=$countAL1?></td>
      </tr>
      <tr><th id="acal2">AL2 </th><td><?=$countAL2?></td></tr>
      <tr><th id="acal3">AL3 </th><td><?=$countAL3?></td></tr>
      <tr><th id="acsirtfi">SIRTFI </th><td><?=$countSIRTFI?></td></tr>
      <tr><th id="acsirtfi2">SIRTFI2 </th><td><?=$countSIRTFI2?></td></tr><?php
  } else {
    printf('
      <tr>
        <th  id="acal" rowspan="2">Assurance profiles in production</th><th id="acsirtfi">SIRTFI</th><td>%d</td>
      </tr>
      <tr><th id="acsirtfi2">SIRTFI2</th><td>%d</td></tr>', $countSIRTFI, $countSIRTFI2);
  }?>

    </table>
<?php
}

function showInfo() {
  global $html, $config;
  $federation = $config->getFederation();
  $html->showHeaders('Info');
  showMenu('info',''); ?>
    <div class="row">
      <div class="col">
        <br>
        <h3><?= $federation['displayName'] ?> Metadata Tool</h3>
        <p>Welcome to the <?= $federation['displayName'] ?> Metadata Tool. With this tool you can browse and examine
          metadata available through <?= $federation['displayName'] ?>.
        <h4>Public available information</h4>
        <p>To view entities, i.e. Identity Providers and Service Providers, available in <?= $federation['displayName'] ?>, select a tab:<ul>
          <li><b>All in <?= $federation['displayName'] ?></b> lists all entities registered in <?= $federation['displayName'] ?>.</li>
          <li><b>IdP in <?= $federation['displayName'] ?></b> lists Identity Providers registered in <?= $federation['displayName'] ?>
            including identity assurance profiles.</li>
          <li><b>SP in <?= $federation['displayName'] ?></b> lists Service Providers registered in <?= $federation['displayName'] ?>
            including requested entity categories.</li>
          <li><b>IdP via interfederation</b> lists Identity Providers imported into <?= $federation['displayName'] ?> from interfederations.</li>
          <li><b>SP via interfederation</b> lists Service Providers imported into <?= $federation['displayName'] ?> from interfederations.</li>
        </ul></p>
        <p>The entities can be sorted and filtered using the headers of the tables and the entityID search form.
          E.g entering "umu.se" in the entityID search form will list all entities
          including "umu.se" in their entityID.</p>
        <h4>Add or Update Identity Provider or Service Provider metadata</h4>
        <p>Login using the orange button at the top right corner of this page to add, update or request removal of
          your entites in <?= $federation['displayName'] ?>. <?= $federation['displayName'] ?> Operations authenticates and validates all updates before changes are
          published in the <?= $federation['displayName'] ?> metadata. After login, help on adding/updating entites is available in the menu
          at the top. When you have requested publication you will get an e-mail that you need to forward to
          operations for compleation.</p>
        <p>If you do not have an active user account at a <?= $federation['displayName'] ?> Identity Provider,
          <?= $federation['noAccountHtml'] ?>.</p>
      </div>
    </div>
<?php
}

function showFeed($id, $urn = false) {
  global $config;
  header(TEXT_PLAIN);
  $federation = $config->getFederation();
  $entity = $urn
    ? $config->getDb()->prepare('SELECT `publishIn` FROM Entities WHERE `entityID` = :Id AND `status` = 1;')
    : $config->getDb()->prepare('SELECT `publishIn` FROM Entities WHERE `id` = :Id;');
  $entity->bindParam(':Id', $id);
  $entity->execute();
  if ($row = $entity->fetch(PDO::FETCH_ASSOC)) {
    switch($row['publishIn']) {
      case 2 :
      case 3 :
        printf ("%s\n", $federation['localFeed']);
        break;
      case 6 :
      case 7 :
        printf ("%s\n", $federation['eduGAINFeed']);
        break;
      default :
        printf ("%s\n", $federation['localFeed']);
    }
  } else {
    http_response_code(404);
    print "Not Found";
  }
  exit;
}

function showPendingQueue() {
  global $config;
  header(TEXT_PLAIN);
  $entities = $config->getDb()->prepare('SELECT `id`, `entityID` FROM Entities WHERE `status` = 2');
  $entities->execute();
  while ($row = $entities->fetch(PDO::FETCH_ASSOC)) {
    printf ('%d %s%s',$row['id'], $row['entityID'], "\n");
  }
  exit;
}

function showRemoveQueue() {
  global $config;
  header(TEXT_PLAIN);
  $entities = $config->getDb()->prepare('SELECT `id`, `entityID` FROM Entities WHERE `removalRequestedBy` > 0 AND `status` = 1');
  $entities->execute();
  while ($row = $entities->fetch(PDO::FETCH_ASSOC)) {
    printf ('%d %s%s',$row['id'], $row['entityID'], "\n");
  }
  exit;
}

function showInterfederation($type){
  global $html, $config;
  $federation = $config->getFederation();
  if ($type == 'IDP') {
    $html->showHeaders('eduGAIN - IdP:s');
    showMenu('fedIdPs','');
    printf ('    <table  id="IdP-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>entityID</th>
          <th>Organization</th>
          <th>Contacts</th>
          <th>Scopes</th>
          <th>Entity category support</th>
          <th>Assurance Certification</th>
          <th>Registration Authority</th>
        </tr>
      </thead>%s', "\n");
    $html->addTableSort('IdP-table');
    $entityList = $config->getDb()->query('SELECT `entityID`, `organization`, `contacts`, `scopes`, `ecs`, `assurancec`, `ra`
      FROM ExternalEntities WHERE isIdP = 1');
    foreach ($entityList as $entity) {
      $entityId_html = $federation['mdqBaseURL'] ?
          sprintf('<a href="./?show=EntityFromMDQ&entityID=%s" target="_blank">%s</a>', urlencode($entity['entityID']), htmlspecialchars($entity['entityID'])) :
          htmlspecialchars($entity['entityID']);
      printf ('        <tr>
          <td>%s</td>
          <td>%s</td>
          <td>%s</td>
          <td>%s</td>
          <td>%s</td>
          <td>%s</td>
          <td>%s</td>
        </tr>%s',
        $entityId_html, $entity['organization'], $entity['contacts'],
        htmlspecialchars($entity['scopes']), htmlspecialchars($entity['ecs']), htmlspecialchars($entity['assurancec']), htmlspecialchars($entity['ra']), "\n");
    }
  } else {
    $html->showHeaders('eduGAIN - SP:s');
    showMenu('fedSPs','');
    printf ('    <table id="SP-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>entityID</th>
          <th>Service Name</th>
          <th>Organization</th>
          <th>Contacts</th>
          <th>Entity Categories</th>
          <th>Assurance Certification</th>
          <th>Registration Authority</th>
        </tr>
      </thead>%s', "\n");
    $html->addTableSort('SP-table');
    $entityList = $config->getDb()->query('SELECT `entityID`, `displayName`, `serviceName`,
        `organization`, `contacts`, `ec`, `assurancec`, `ra`
      FROM ExternalEntities WHERE isSP = 1');
    foreach ($entityList as $entity) {
      $entityId_html = $federation['mdqBaseURL'] ?
          sprintf('<a href="./?show=EntityFromMDQ&entityID=%s" target="_blank">%s</a>', urlencode($entity['entityID']), htmlspecialchars($entity['entityID'])) :
          htmlspecialchars($entity['entityID']);
      printf ('        <tr>
          <td>%s</td>
          <td>%s<br>%s</td>
          <td>%s</td>
          <td>%s</td>
          <td>%s</td>
          <td>%s</td>
          <td>%s</td>
        </tr>%s',
        $entityId_html,  htmlspecialchars($entity['displayName']), htmlspecialchars($entity['serviceName']), $entity['organization'],
        $entity['contacts'], htmlspecialchars($entity['ec']), htmlspecialchars($entity['assurancec']), htmlspecialchars($entity['ra']), "\n");
    }
  }
}

function showEntityFromMDQ($entityID) {
  global $config;
  $federation = $config->getFederation();
  if (!$federation['mdqBaseURL']) {
    http_response_code(500);
    printf("ERROR: MDQ not configured\n");
    exit;
  }
  $target_url = sprintf('%s%s', $federation['mdqBaseURL'], urlencode($entityID));
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
  curl_setopt($ch, CURLOPT_USERAGENT, $config->getFederation()['urlCheckUA']);
  curl_setopt($ch, CURLOPT_PROTOCOLS, $config->getFederation()['urlCheckPlainHTTPEnabled'] ? CURLPROTO_HTTP | CURLPROTO_HTTPS : CURLPROTO_HTTPS);
  curl_setopt($ch, CURLOPT_MAXFILESIZE, $config->getFederation()['urlCheckMaxSize']);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(ACCEPT_SAML_METADATA));

  $allowed_schemes = $config->getFederation()['urlCheckPlainHTTPEnabled'] ? array('http', 'https') : array('https');
  $default_ports = array( 'http' => 80, 'https' => 443);

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

  curl_setopt($ch, CURLINFO_HEADER_OUT, 0);

  // sanity check URL before passing to URL
  $parsed_url = parse_url($target_url);
  $url_scheme = $parsed_url['scheme'] ?? '';
  // guard against missing componets
  if ($parsed_url && in_array($url_scheme, $allowed_schemes) && ($parsed_url['port'] ?? $default_ports[$url_scheme]) == $default_ports[$url_scheme]) {
    curl_setopt($ch, CURLOPT_URL, $target_url);
    $output = curl_exec($ch);
    // check if we received a valid redirect
    if (curl_errno($ch)) {
      http_response_code(500);
      print 'Curl error';
      exit;
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
      // propagate only 404, for other non-OK HTTP response codes, return 500
      http_response_code($http_code == 404 ? $http_code : 500);
      printf('Could not retrieve metadata from MDQ URL %s (received HTTP code %d)', $target_url, $http_code);
      exit;
    }
    header('Content-Type: application/xml; charset=utf-8');
    print $output;
    exit;
  } else {
    http_response_code(500);
    print "Invalid URL $target_url";
    exit;
  }
}
