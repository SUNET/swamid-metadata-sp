<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

const HTML_TITLE_PROBLEM = 'Metadata SWAMID - Problem';
const HTML_CLASS_FA_UP = '<i class="fa fa-arrow-up"></i>';
const HTML_CLASS_FA_DOWN = '<i class="fa fa-arrow-down"></i>';
const HTML_OUTLINE = '-outline';

//Load composer's autoloader
require_once '../vendor/autoload.php';

require_once '../config.php';

require_once '../include/Html.php';
$html = new HTML('', $Mode);

$errorURL = isset($_SERVER['Meta-errorURL'])
  ? '<a href="' . $_SERVER['Meta-errorURL'] . '">Mer information</a><br>'
  : '<br>';
$errorURL = str_replace(array('ERRORURL_TS', 'ERRORURL_RP', 'ERRORURL_TID'),
  array(time(), 'https://metadata.swamid.se/shibboleth', $_SERVER['Shib-Session-ID']),
  $errorURL);

$errors = '';
$filterFirst = true;

if (isset($_SERVER['Meta-Assurance-Certification'])) {
  $AssuranceCertificationFound = false;
  foreach (explode(';',$_SERVER['Meta-Assurance-Certification']) as $AssuranceCertification) {
    if ($AssuranceCertification == 'http://www.swamid.se/policy/assurance/al1') {
      $AssuranceCertificationFound = true;
    }
  }
  if (! $AssuranceCertificationFound) {
    $errors .= sprintf('%s has no AssuranceCertification (http://www.swamid.se/policy/assurance/al1) ',
      $_SERVER['Shib-Identity-Provider']);
  }
}

if (isset($_SERVER['eduPersonPrincipalName'])) {
  $EPPN = $_SERVER['eduPersonPrincipalName'];
} elseif (isset($_SERVER['subject-id'])) {
  $EPPN = $_SERVER['subject-id'];
} else {
  $errors .= 'Missing eduPersonPrincipalName/subject-id in SAML response ' . str_replace(
    array('ERRORURL_CODE', 'ERRORURL_CTX'),
    array('IDENTIFICATION_FAILURE', 'eduPersonPrincipalName'),
    $errorURL);
}

if (isset($_SERVER['eduPersonScopedAffiliation'])) {
  $foundEmployee = false;
  foreach (explode(';',$_SERVER['eduPersonScopedAffiliation']) as $ScopedAffiliation) {
    if (explode('@',$ScopedAffiliation)[0] == 'employee') {
      $foundEmployee = true;
    }
  }
  if (! $foundEmployee) {
    $errors .= sprintf('Did not find employee in eduPersonScopedAffiliation. Only got %s<br>',
      $_SERVER['eduPersonScopedAffiliation']);
  }
} elseif (isset($_SERVER['eduPersonAffiliation'])) {
  $foundEmployee = false;
  foreach (explode(';',$_SERVER['eduPersonAffiliation']) as $Affiliation) {
    if ($Affiliation == 'employee') {
      $foundEmployee = true;
    }
  }
  if (! $foundEmployee) {
    $errors .= sprintf('Did not find employee in eduPersonAffiliation. Only got %s<br>',
      $_SERVER['eduPersonAffiliation']);
  }
} else {
  if (isset($_SERVER['Shib-Identity-Provider'])
    && $_SERVER['Shib-Identity-Provider'] == 'https://login.idp.eduid.se/idp.xml') {
    #OK to not send eduPersonScopedAffiliation / eduPersonAffiliation
    $filterFirst = false;
  } else {
    $errors .=
      'Missing eduPersonScopedAffiliation and eduPersonAffiliation in SAML response<br>One of them is required<br>';
  }
}

if ( isset($_SERVER['mail'])) {
  $mailArray = explode(';',$_SERVER['mail']);
  $mail = $mailArray[0];
} else {
  $errors .= 'Missing mail in SAML response ' . str_replace(
    array('ERRORURL_CODE', 'ERRORURL_CTX'),
    array('IDENTIFICATION_FAILURE', 'mail'),
    $errorURL);
}

if (isset($_SERVER['displayName'])) {
  $fullName = $_SERVER['displayName'];
} elseif (isset($_SERVER['givenName'])) {
  $fullName = $_SERVER['givenName'];
  $fullName .= isset($_SERVER['sn']) ? ' ' .$_SERVER['sn'] : '';
} else {
  $fullName = '';
}

if ($errors != '') {
  $html->showHeaders(HTML_TITLE_PROBLEM);
  printf('
    <div class="row alert alert-danger" role="alert">
      <div class="col">
        <b>Errors:</b><br>
        %s
      </div>
    </div>%s', str_ireplace("\n", "<br>", $errors), "\n");
  $html->showFooter(array());
  exit;
}

switch ($EPPN) {
  case 'bjorn@sunet.se' :
  case 'jocar@sunet.se' :
  case 'mifr@sunet.se' :
    $userLevel = 20;
    break;
  case 'frkand02@umu.se' :
  case 'paulscot@kau.se' :
    $userLevel = 10;
    break;
  case 'johpe12@liu.se' :
  case 'pax@sunet.se' :
  case 'toylon98@umu.se' :
    $userLevel = 5;
    break;
  case 'pontus.fagerstrom@sunet.se' :
    $userLevel = 2;
    break;
  default :
    $userLevel = 1;
}
$displayName = '<div> Logged in as : <br> ' . $fullName . ' (' . $EPPN .')</div>';
$html->setDisplayName($displayName);

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

require_once '../include/MetadataDisplay.php';
$display = new MetadataDisplay();

require_once '../include/Metadata.php';

if (isset($_FILES['XMLfile'])) {
  importXML();
} elseif (isset($_GET['edit'])) {
  if (isset($_GET['Entity']) && (isset($_GET['oldEntity']))) {
    require_once '../include/MetadataEdit.php';
    $editMeta = new MetadataEdit($_GET['Entity'], $_GET['oldEntity']);
    $editMeta->updateUser($EPPN, $mail, $fullName);
    if (checkAccess($_GET['Entity'], $EPPN, $userLevel, 10, true)) {
      $html->showHeaders('Metadata SWAMID - Edit - '.$_GET['edit']);
      $editMeta->edit($_GET['edit']);
    }
  } else {
    showEntityList();
  }
} elseif (isset($_GET['showEntity'])) {
  showEntity($_GET['showEntity']);
} elseif (isset($_GET['validateEntity'])) {
  validateEntity($_GET['validateEntity']);
  showEntity($_GET['validateEntity']);
} elseif (isset($_GET['move2Pending'])) {
  if (checkAccess($_GET['move2Pending'], $EPPN, $userLevel, 10, true)) {
    move2Pending($_GET['move2Pending']);
  }
} elseif (isset($_GET['move2Draft'])) {
  if (checkAccess($_GET['move2Draft'], $EPPN, $userLevel, 10, true)) {
    move2Draft($_GET['move2Draft']);
  }
} elseif (isset($_GET['mergeEntity'])) {
  if (checkAccess($_GET['mergeEntity'],$EPPN,$userLevel,10,true)) {
    if (isset($_GET['oldEntity'])) {
      mergeEntity($_GET['mergeEntity'], $_GET['oldEntity']);
      validateEntity($_GET['mergeEntity']);
    }
    showEntity($_GET['mergeEntity']);
  }
} elseif (isset($_GET['removeEntity'])) {
  if (checkAccess($_GET['removeEntity'],$EPPN,$userLevel,10, true)) {
    removeEntity($_GET['removeEntity']);
  }
} elseif (isset($_GET['removeSSO']) && isset($_GET['type'])) {
  if (checkAccess($_GET['removeSSO'],$EPPN,$userLevel,10, true)) {
    removeSSO($_GET['removeSSO'], $_GET['type']);
  }
} elseif (isset($_GET['removeKey']) && isset($_GET['type']) && isset($_GET['use']) && isset($_GET['serialNumber'])) {
  if (checkAccess($_GET['removeKey'],$EPPN,$userLevel,10, true)) {
    removeKey($_GET['removeKey'], $_GET['type'], $_GET['use'], $_GET['serialNumber']);
  }
} elseif (isset($_GET['rawXML'])) {
  $display->showRawXML($_GET['rawXML']);
} elseif (isset(($_GET['approveAccessRequest']))) {
  approveAccessRequest($_GET['approveAccessRequest']);
} elseif (isset($_GET['showHelp'])) {
  showHelp();
} else {
  $menuActive = 'publ';
  if (isset($_GET['action'])) {
    if (isset($_GET['Entity'])) {
      $entitiesId = $_GET['Entity'];
      switch($_GET['action']) {
        case 'createDraft' :
          $menuActive = 'new';
          $metadata = new Metadata($entitiesId);
          $user_id = $metadata->getUserId($EPPN);
          if ($metadata->isResponsible()) {
            if ($newEntity_id = $metadata->createDraft()) {
              $metadata->validateXML();
              $metadata->validateSAML();
              $metadata->copyResponsible($entitiesId);
              $menuActive = 'new';
              showEntity($newEntity_id);
            }
          } else {
            # User have no access yet.
            requestAccess($entitiesId);
          }
          break;
        case 'Request removal' :
          requestRemoval($entitiesId);
          break;
        case 'Annual Confirmation' :
          annualConfirmation($entitiesId);
          break;
        case 'Request Access' :
          requestAccess($entitiesId);
          break;
        default :
      }
    } else {
      switch($_GET['action']) {
        case 'new' :
          $menuActive = 'new';
          showEntityList(3);
          break;
        case 'wait' :
          $menuActive = 'wait';
          showEntityList(2);
          break;
        case 'upload' :
          $menuActive = 'upload';
          showUpload();
          break;
        case 'myEntities' :
          $menuActive = 'myEntities';
          showMyEntities();
          break;
        case 'EntityStatistics' :
          $menuActive = 'EntityStatistics';
          $html->showHeaders('Metadata SWAMID - Entity Statistics');
          showMenu();
          $display->showEntityStatistics();
          break;
        case 'EcsStatistics' :
          $menuActive = 'EcsStatistics';
          $html->showHeaders('Metadata SWAMID - EntityCategorySupport status');
          showMenu();
          $display->showEcsStatistics();
          break;
        case 'showURL' :
          $menuActive = '';
          $html->showHeaders('Metadata SWAMID - URL status');
          showMenu();
          if (isset($_GET['URL'])) {
            if (isset($_GET['recheck'])) {
              $metadata = new Metadata();
              $metadata->revalidateURL($_GET['URL']);
            }
            $display->showURLStatus($_GET['URL']);
          }
          break;
        case 'URLlist' :
          if ($userLevel > 4) {
            $menuActive = 'URLlist';
            $html->showHeaders('Metadata SWAMID - URL status');
            showMenu();
            if (isset($_GET['URL'])) {
              if (isset($_GET['recheck'])) {
                $metadata = new Metadata();
                $metadata->revalidateURL($_GET['URL']);
              }
              $display->showURLStatus($_GET['URL']);
            } else {
              $display->showURLStatus();
            }
          }
          break;
        case 'ErrorList' :
          $menuActive = 'Errors';
          $html->showHeaders('Metadata SWAMID - Errror status');
          showMenu();
          $display->showErrorList();
          $html->addTableSort('error-table');
          break;
        case 'ErrorListDownload' :
          if ($userLevel > 1) {
            $display->showErrorList(true);
            exit;
          }
          break;
        case 'CleanPending' :
          if ($userLevel > 10) {
            $menuActive = 'CleanPending';
            $html->showHeaders('Metadata SWAMID - Clean Pending');
            showMenu();
            $display->showPendingList();
          }
          break;
        case 'ShowDiff' :
          $menuActive = 'CleanPending';
          $html->showHeaders('Metadata SWAMID - Clean Pending');
          showMenu();
          if (isset($_GET['entity_id1']) && isset($_GET['entity_id2'])) {
            $display->showXMLDiff($_GET['entity_id1'], $_GET['entity_id2']);
          }
          $display->showPendingList();
          break;
        case 'showScopes' :
          $menuActive = 'Scopes';
          $html->showHeaders('Metadata SWAMID - Show scopes');
          showMenu();
          $display->showScopeLists();
          $html->addTableSort('scope-table');
          break;
        default :
          showEntityList();
      }
    }
  } else {
    $menuActive = 'myEntities';
    showMyEntities();
  }
}

$html->showFooter($display->getCollapseIcons());
# End of page

####
# Shows EntityList
####
function showEntityList($status = 1) {
  global $db, $html, $EPPN, $filterFirst;

  $feedOrder = 'feedDesc';
  $orgOrder = 'orgAsc';
  $entityIDOrder = 'entityIDAsc';
  $feedArrow = '';
  $orgArrow = '';
  $entityIDArrow = '';
  $validationArrow = '';
  $warningArrow = '';
  $errorArrow = '';
  if (isset($_GET['feedDesc'])) {
    $sortOrder = '`publishIn` DESC, `entityID`';
    $feedOrder = 'feedAsc';
    $feedArrow = HTML_CLASS_FA_UP;
  } elseif (isset($_GET['feedAsc'])) {
    $sortOrder = '`publishIn` ASC, `entityID`';
    $feedArrow = HTML_CLASS_FA_DOWN;
  } elseif (isset($_GET['orgDesc'])) {
    $sortOrder = '`OrganizationName` DESC, `entityID`';
    $orgArrow = HTML_CLASS_FA_UP;
  } elseif (isset($_GET['orgAsc'])) {
    $sortOrder = '`OrganizationName` ASC, `entityID`';
    $orgOrder = 'orgDesc';
    $orgArrow = HTML_CLASS_FA_DOWN;
  } elseif (isset($_GET['validationOutput'])) {
    $sortOrder = '`validationOutput` DESC, `entityID`, `id`';
    $validationArrow = HTML_CLASS_FA_DOWN;
  } elseif (isset($_GET['warnings'])) {
    $sortOrder = '`warnings` DESC, `errors` DESC, `errorsNB` DESC, `entityID`, `id`';
    $warningArrow = HTML_CLASS_FA_DOWN;
  } elseif (isset($_GET['errors'])) {
    $sortOrder = '`errors` DESC, `errorsNB` DESC, `warnings` DESC, `entityID`, `id`';
    $errorArrow = HTML_CLASS_FA_DOWN;
  } elseif (isset($_GET['entityIDDesc'])) {
    $sortOrder = '`entityID` DESC';
    $entityIDOrder = 'entityIDAsc';
    $entityIDArrow = HTML_CLASS_FA_UP;
  } else {
    $sortOrder = '`entityID` ASC';
    $entityIDOrder = 'entityIDDesc';
    $entityIDArrow = HTML_CLASS_FA_DOWN;
  }

  if (isset($_GET['query'])) {
    $query = $_GET['query'];
  } elseif (isset($_GET['first']) && $filterFirst) {
    $query = '.'.explode('@',$EPPN)[1];
  } else {
     $query = '';
  }
  $filter = 'query='.$query;

  switch ($status) {
    case 1:
      $html->showHeaders('Metadata SWAMID - Published');
      $action = 'pub';
      $minLevel = 0;
      break;
    case 2:
      $html->showHeaders('Metadata SWAMID - Pending');
      $action = 'wait';
      $minLevel = 5;
      break;
    case 3:
      $html->showHeaders('Metadata SWAMID - Drafts');
      $action = 'new';
      $minLevel = 5;
      break;
    case 4:
      $html->showHeaders('Metadata SWAMID - Deleted');
      $action = 'pub';
      $minLevel = 5;
      break;
    case 5:
      $html->showHeaders('Metadata SWAMID - Pending already Published');
      $action = 'pub';
      $minLevel = 5;
      break;
    case 6:
      $html->showHeaders('Metadata SWAMID - Published when added to Pending');
      $action = 'pub';
      $minLevel = 5;
      break;
    default:
      $html->showHeaders('Metadata SWAMID');
  }
  showMenu();
  if (isset($_GET['action'])) {
    $filter .= '&action='.$_GET['action'];
  }
  $entitys = $db->prepare(
    "SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `data` AS OrganizationName,
      `lastUpdated`, `lastValidated`, `validationOutput`, `warnings`, `errors`, `errorsNB`
    FROM Entities
    LEFT JOIN Organization ON Organization.entity_id = id AND element = 'OrganizationName' AND lang = 'en'
    WHERE status = $status AND entityID LIKE :Query ORDER BY $sortOrder");
  $entitys->bindValue(':Query', "%".$query."%");

  printf('
    <table class="table table-striped table-bordered">
      <tr>
        <th>IdP</th>
        <th>SP</th>
        <th>Registered in</th>
        <th><a href="?%s&%s">eduGAIN%s</a></th>
        <th>
          <form>
            <a href="?%s&%s">entityID%s</a>
            <input type="text" name="query" value="%s">
            <input type="hidden" name="action" value="%s">
            <input type="submit" value="Filter">
          </form>
        </th>
        <th><a href="?%s&%s">OrganizationName%s</a></th><th>%s</th><th>lastValidated (UTC)</th>
        <th><a href="?%s&validationOutput">validationOutput%s</a></th>
        <th><a href="?%s&warnings">warning%s</a> / <a href="?%s&errors">errors%s</a></th></tr>%s',
    $filter, $feedOrder, $feedArrow, $filter, $entityIDOrder, $entityIDArrow, $query, $action, $filter,
    $orgOrder, $orgArrow, ($status == 1) ? 'lastUpdated (UTC)' : 'created (UTC)' ,
    $filter, $validationArrow, $filter, $warningArrow, $filter, $errorArrow, "\n");
  showList($entitys, $minLevel);
}

####
# Shows Entity information
####
function showEntity($entitiesId)  {
  global $db, $html, $display, $userLevel, $menuActive, $EPPN;
  $entityHandler = $db->prepare(
    'SELECT `entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `publishedId`
    FROM Entities WHERE `id` = :Id;');
  $publishArray = array();
  $publishArrayOld = array();
  $allowEdit = false;

  $entityHandler->bindParam(':Id', $entitiesId);
  $entityHandler->execute();
  if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    if (($entity['publishIn'] & 2) == 2) { $publishArray[] = 'SWAMID'; }
    if (($entity['publishIn'] & 4) == 4) { $publishArray[] = 'eduGAIN'; }
    if (($entity['publishIn'] & 1) == 1) { $publishArray[] = 'SWAMID-testing'; }
    if ($entity['status'] > 1 && $entity['status'] < 6) {
      if ($entity['publishedId'] > 0) {
        $entityHandlerOld = $db->prepare(
          'SELECT `id`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE `id` = :Id AND `status` = 6;');
        $entityHandlerOld->bindParam(':Id', $entity['publishedId']);
        $headerCol2 = 'Old metadata - when requested publication';
      } else {
        $entityHandlerOld = $db->prepare(
          'SELECT `id`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE `entityID` = :Id AND `status` = 1;');
        $entityHandlerOld->bindParam(':Id', $entity['entityID']);
        $headerCol2 = 'Old metadata - published now';
      }
      $entityHandlerOld->execute();
      if ($entityOld = $entityHandlerOld->fetch(PDO::FETCH_ASSOC)) {
        $oldEntitiesId = $entityOld['id'];
        if (($entityOld['publishIn'] & 2) == 2) { $publishArrayOld[] = 'SWAMID'; }
        if (($entityOld['publishIn'] & 4) == 4) { $publishArrayOld[] = 'eduGAIN'; }
        if (($entityOld['publishIn'] & 1) == 1) { $publishArrayOld[] = 'SWAMID-testing'; }
      } else {
        $oldEntitiesId = 0;
      }
      switch ($entity['status']) {
        case 3:
          # Draft
          $headerCol1 = 'New metadata';
          $menuActive = 'new';
          $allowEdit = checkAccess($entitiesId, $EPPN, $userLevel, 10, false);
          break;
        case 4:
          # Soft Delete
          $headerCol1 = 'Deleted metadata';
          $menuActive = 'publ';
          $allowEdit = false;
          $oldEntitiesId = 0;
          break;
        case 5:
          # Pending that have been published
        case 6:
          # Copy of published used to compare Pending
          $headerCol1 = 'Already published metadata (might not be the latest!)';
          $menuActive = 'publ';
          $allowEdit = false;
          break;
        default:
          $headerCol1 = 'Waiting for publishing';
          $menuActive = 'wait';
          $allowEdit = checkAccess($entitiesId, false, $userLevel, 10, false);
      }
    } else {
      $headerCol1 = 'Published metadata';
      $menuActive = 'publ';
      $oldEntitiesId = 0;
    }
    $html->showHeaders('Metadata SWAMID - ' . $entity['entityID']);
    showMenu();?>
    <div class="row">
      <div class="col">
        <h3>entityID = <?=$entity['entityID']?></h3>
      </div>
    </div><?php
    $display->showStatusbar($entitiesId, $userLevel > 4 ? true : false);
    print "\n" . '    <div class="row">';
    switch ($entity['status']) {
      case 1:
        printf('%s      <a href=".?action=Annual+Confirmation&Entity=%d">
        <button type="button" class="btn btn-outline-%s">Annual Confirmation</button></a>',
          "\n", $entitiesId, getErrors($entitiesId) == '' ? 'success' : 'danger');
        printf('%s      <a href=".?action=createDraft&Entity=%d">
        <button type="button" class="btn btn-outline-primary">Create draft</button></a>', "\n", $entitiesId);
        printf('%s      <a href=".?action=Request+removal&Entity=%d">
        <button type="button" class="btn btn-outline-danger">Request removal</button></a>', "\n", $entitiesId);
        break;
      case 2:
        if (checkAccess($entitiesId, false, $userLevel, 10, false)) {
          printf('%s      <a href=".?removeEntity=%d">
          <button type="button" class="btn btn-outline-danger">Delete Pending</button></a>', "\n", $entitiesId);
        }
        if (checkAccess($entitiesId, $EPPN, $userLevel, 10, false)) {
            printf('%s      <a href=".?move2Draft=%d">
            <button type="button" class="btn btn-outline-danger">Cancel publication request</button></a>',
               "\n", $entitiesId);
        }
        break;
      case 3:
        if (checkAccess($entitiesId, $EPPN, $userLevel, 10, false)) {
          printf('%s      <a href=".?move2Pending=%d">
          <button type="button" class="btn btn-outline-%s">Request publication</button></a>',
            "\n", $entitiesId, getBlockingErrors($entitiesId) == '' ? 'success' : 'danger' );
          printf('%s      <a href=".?removeEntity=%d">
          <button type="button" class="btn btn-outline-danger">Discard Draft</button></a>',
            "\n", $entitiesId);
          print "\n      <br>";
          if ($oldEntitiesId > 0) {
            printf('%s      <a href=".?mergeEntity=%d&oldEntity=%d">
            <button type="button" class="btn btn-outline-primary">Merge from published</button></a>',
              "\n", $entitiesId, $oldEntitiesId);
          }
          printf ('%s      <form>
        <input type="hidden" name="mergeEntity" value="%d">
        Merge from other entity : %s        <select name="oldEntity">', "\n", $entitiesId, "\n");
          if ($entity['isIdP'] ) {
            if ($entity['isSP'] ) {
              // is both SP and IdP
              $mergeEntityHandler = $db->prepare(
                'SELECT id, entityID FROM Entities WHERE status = 1 ORDER BY entityID;');
            } else {
              // isIdP only
              $mergeEntityHandler = $db->prepare(
                'SELECT id, entityID FROM Entities WHERE status = 1 AND isIdP = 1 ORDER BY entityID;');
            }
          } else {
            // isSP only
            $mergeEntityHandler = $db->prepare(
              'SELECT id, entityID FROM Entities WHERE status = 1 AND isSP = 1 ORDER BY entityID;');
          }
          $mergeEntityHandler->execute();
          while ($mergeEntity = $mergeEntityHandler->fetch(PDO::FETCH_ASSOC)) {
            printf('%s          <option value="%d">%s</option>', "\n", $mergeEntity['id'], $mergeEntity['entityID']);
          }
          printf ('%s        </select>%s        <button type="submit">Merge</button>%s      </form>', "\n", "\n", "\n");
        }
        break;
      default :
    }

     ?>

    </div>
    <div class="row">
      <div class="col">
        <h3><?=$headerCol1?></h3>
        Published in : <?php
    print implode (', ', $publishArray);
    if ($oldEntitiesId > 0) { ?>

      </div>
      <div class="col">
        <h3><?=$headerCol2?></h3>
        Published in : <?php
      print implode (', ', $publishArrayOld);
    } ?>

      </div>
    </div>
    <br><?php
    $display->showEntityAttributes($entitiesId, $oldEntitiesId, $allowEdit);
    $able2beRemoveSSO = ($entity['isIdP'] && $entity['isSP'] && $allowEdit);
    if ($entity['isIdP'] ) { $display->showIdP($entitiesId, $oldEntitiesId, $allowEdit, $able2beRemoveSSO); }
    if ($entity['isSP'] ) { $display->showSp($entitiesId, $oldEntitiesId, $allowEdit, $able2beRemoveSSO); }
    if ($entity['isAA'] ) { $display->showAA($entitiesId, $oldEntitiesId, $allowEdit, true); }
    $display->showOrganization($entitiesId, $oldEntitiesId, $allowEdit);
    $display->showContacts($entitiesId, $oldEntitiesId, $allowEdit);
    $display->showXML($entitiesId);
    $display->showEditors($entitiesId);

  } else {
    $html->showHeaders('Metadata SWAMID - NotFound');
    print "Can't find Entity";
  }
}

####
# Shows a list of entitys
####
function showList($entitys, $minLevel) {
  global $db, $EPPN, $userLevel;

  $entitys->execute();
  while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
    if (checkAccess($row['id'], $EPPN, $userLevel, $minLevel)) {
      printf ('      <tr>
        ');
      print $row['isIdP']
        ? '<td class="text-center">X</td>'
        : '<td></td>';
      print $row['isSP']
        ? '
        <td class="text-center">X</td>'
        : '
        <td></td>';

      switch ($row['publishIn']) {
        case 1 :
          $registerdIn = 'Test-only';
          $export2Edugain = '';
          break;
        case 3 :
          $registerdIn = 'SWAMID';
          $export2Edugain = '';
          break;
        case 7 :
          $registerdIn = 'SWAMID';
          $export2Edugain = 'X';
          break;
        default :
          $registerdIn = '';
          $export2Edugain = '';
      }
      $validationStatus = ($row['warnings'] == '') ? '' : '<i class="fas fa-exclamation-triangle"></i>';
      $validationStatus .= ($row['errors'] == '' && $row['errorsNB'] == '') ? '' : '<i class="fas fa-exclamation"></i>';
      $validationOutput = ($row['validationOutput'] == '') ? '' : '<i class="fas fa-question"></i>';
      printf ('
        <td class="text-center">%s</td>
        <td class="text-center">%s</td>
        <td><a href="?showEntity=%s">%s</a></td>
        <td>%s</td>
        <td>%s</td>
        <td>%s</td>
        <td>%s</td>
        <td>%s</td>',
        $registerdIn, $export2Edugain, $row['id'], $row['entityID'], $row['OrganizationName'],
        $row['lastUpdated'], $row['lastValidated'], $validationOutput, $validationStatus);
      print "\n      </tr>\n";
    }
  } ?>
    </table>
<?php
}

####
# Shows list for entities this user have access to do Annual Check for.
####
function showMyEntities() {
  global $db, $html, $EPPN, $userLevel;

  $html->showHeaders('Metadata SWAMID - Annual Check');
  showMenu();
  if ($userLevel > 9) {
    printf ('    <div class="row">%s      <div class="col">%s', "\n", "\n");
    printf ('        <a href=".?action=myEntities&showMy">
          <button type="button" class="btn btn%s-success">Show My</button>
        </a>%s',
      isset($_GET['showMy']) ? '' : HTML_OUTLINE, "\n");
    printf ('        <a href=".?action=myEntities&showPub">
          <button type="button" class="btn btn%s-success">Show Published</button>
        </a>%s',
      isset($_GET['showPub']) ? '' : HTML_OUTLINE, "\n");
    printf ('        <a href=".?action=myEntities&showPubTest">
          <button type="button" class="btn btn%s-success">Show Published in test</button>
        </a>%s',
      isset($_GET['showPubTest']) ? '' : HTML_OUTLINE, "\n");
    printf ('      </div>%s    </div>%s', "\n", "\n");
    }
  if (isset($_GET['showPub']) && $userLevel > 9) {
    $entitysHandler = $db->prepare("SELECT Entities.`id`, `entityID`, `errors`, `errorsNB`, `warnings`, `status`
      FROM Entities WHERE `status` = 1 AND publishIn > 1 ORDER BY `entityID`");
  } elseif (isset($_GET['showPubTest']) && $userLevel > 9) {
    $entitysHandler = $db->prepare("SELECT Entities.`id`, `entityID`, `errors`, `errorsNB`, `warnings`, `status`
      FROM Entities WHERE `status` = 1 AND publishIn = 1 ORDER BY `entityID`");
  } else {
    $entitysHandler = $db->prepare("SELECT Entities.`id`, `entityID`, `errors`, `errorsNB`, `warnings`, `status`
      FROM Users, EntityUser, Entities
      WHERE EntityUser.`entity_id` = Entities.`id`
        AND EntityUser.`user_id` = Users.`id`
        AND `status` < 4
        AND `userID` = :UserID
      ORDER BY `entityID`, `status`");
    $entitysHandler->bindValue(':UserID', $EPPN);
  }
  $entityConfirmationHandler = $db->prepare(
    "SELECT `lastConfirmed`, `fullName`, `email`, NOW() - INTERVAL 10 MONTH AS `warnDate`,
      NOW() - INTERVAL 12 MONTH AS 'errorDate'
    FROM Users, EntityConfirmation
    WHERE `user_id`= `id` AND `entity_id` = :Id");

  printf ('
    <table id="annual-table" class="table table-striped table-bordered">
      <thead><tr>
        <th>entityID</th><th>Metadata status</th><th>Last confirmed(UTC)</th><th>By</th>
      </tr></thead>%s', "\n");
  $entitysHandler->execute();
  while ($row = $entitysHandler->fetch(PDO::FETCH_ASSOC)) {
    $entityConfirmationHandler->bindParam(':Id', $row['id']);
    $entityConfirmationHandler->execute();
    if ($entityConfirmation = $entityConfirmationHandler->fetch(PDO::FETCH_ASSOC)) {
      $lastConfirmed = $entityConfirmation['lastConfirmed'];
      $updater = $entityConfirmation['fullName'] . ' (' . $entityConfirmation['email'] . ')';
      if ($entityConfirmation['warnDate'] > $entityConfirmation['lastConfirmed']) {
        $confirmStatus =  $entityConfirmation['errorDate'] > $entityConfirmation['lastConfirmed']
          ? ' <i class="fa-regular fa-bell"></i>'
          : ' <i class="fa-regular fa-clock"></i>';
      } else {
        $confirmStatus = '';
      }
    } else {
      $confirmStatus = ' <i class="fa-regular fa-bell"></i>';
      $lastConfirmed = 'Never';
      $updater = '';
    }
    switch ($row['status']) {
      case 1 :
        $pubStatus = 'Published';
        break;
      case 2 :
        $pubStatus = 'Pending';
        $lastConfirmed = '';
        $confirmStatus = '';
        $updater = '';
        break;
      case 3 :
        $pubStatus = 'Draft';
        $lastConfirmed = '';
        $confirmStatus = '';
        $updater = '';
        break;
      default :
    }
    if ($row['errors'] == '' && $row['errorsNB'] == '') {
      $errorStatus =  ($row['warnings'] == '')
        ? '<i class="fas fa-check"></i>'
        : '<i class="fas fa-exclamation-triangle"></i>';
    } else {
      $errorStatus = '<i class="fas fa-exclamation"></i>';
    }
    printf('      <tr><td><a href="?showEntity=%d">%s</a></td><td>%s (%s)</td><td>%s%s</td><td>%s</td></tr>%s',
      $row['id'], $row['entityID'], $errorStatus, $pubStatus, $lastConfirmed, $confirmStatus, $updater, "\n");
  }
  print "    </table>\n";
  $html->addTableSort('annual-table');
}

####
# Shows form for upload of new XML
####
function showUpload() {
  global $html;
  $html->showHeaders('Metadata SWAMID - Add new XML');
  showMenu();
  ?>
    <form action="." method="post" enctype="multipart/form-data">
      <div class="custom-file">
        <input type="file" class="custom-file-input" name="XMLfile" id="customFile">
        <label class="custom-file-label" for="customFile">Choose file</label>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary">Submit</button>
      </div>
    </form><?php
}

####
# Import and validate uploaded XML.
####
function importXML(){
  global $html;
  global $EPPN,$mail, $fullName;

  require_once '../include/NormalizeXML.php';
  require_once '../include/ValidateXML.php';
  require_once '../include/MetadataEdit.php';
  $import = new NormalizeXML();
  $import->fromFile($_FILES['XMLfile']['tmp_name']);
  if ($import->getStatus()) {
    $entityID = $import->getEntityID();
    $validate = new ValidateXML($import->getXML());
    if ($validate->validateSchema('../../schemas/schema.xsd')) {
      $metadata = new Metadata($entityID, 'New');
      $metadata->importXML($import->getXML());
      $metadata->getUser($EPPN, $mail, $fullName, true);
      $metadata->updateResponsible($EPPN);
      $metadata->validateXML();
      $metadata->validateSAML();

      $prodmetadata = new Metadata($entityID, 'Prod');
      if ($prodmetadata->entityExists()) {
        $editMetadata = new MetadataEdit($metadata->id(), $prodmetadata->id());
        $editMetadata->mergeRegistrationInfo();
        $editMetadata->saveXML();
      }
      showEntity($metadata->id());
    } else {
      $html->showHeaders(HTML_TITLE_PROBLEM);
      printf('%s    <div class="row alert alert-danger" role="alert">
      <div class="col">
        <b>Error in XML-syntax:</b>
        %s
      </div>%s    </div>%s', "\n", $validate->getError(), "\n", "\n");
      $html->showFooter(array());
    }
  } else {
    $html->showHeaders(HTML_TITLE_PROBLEM);
    printf('%s    <div class="row alert alert-danger" role="alert">
      <div class="col">
        <b>Error in XML-file:</b>
        %s
      </div>%s    </div>%s', "\n", $import->getError(), "\n", "\n");
    $html->showFooter(array());
  }
}

####
# Remove an IDPSSO / SPSSO Decriptor that isn't used
####
function removeSSO($entitiesId, $type) {
  require_once '../include/MetadataEdit.php';
  $metadata = new MetadataEdit($entitiesId);
  $metadata->removeSSO($type);
  validateEntity($entitiesId);
  showEntity($entitiesId);
}

####
# Remove an IDPSSO / SPSSO Key that is old & unused
####
function removeKey($entitiesId, $type, $use, $serialNumber) {
  require_once '../include/MetadataEdit.php';
  $metadata = new MetadataEdit($entitiesId);
  $metadata->removeKey($type, $use, $serialNumber);
  validateEntity($entitiesId);
  showEntity($entitiesId);
}

####
# Shows menu row
####
function showMenu() {
  global $userLevel, $menuActive, $EPPN, $filterFirst;
  $filter='';
  if (isset($_GET['query'])) {
    $filter='&query='.$_GET['query'];
  } elseif (isset($_GET['first']) && $filterFirst) {
    $filter='&query=.'. explode('@',$EPPN)[1];
  }

  print "\n    ";
  printf('<a href=".?action=myEntities%s"><button type="button" class="btn btn%s-primary">My entities</button></a>',
    $filter, $menuActive == 'myEntities' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=pub%s"><button type="button" class="btn btn%s-primary">Published</button></a>',
    $filter, $menuActive == 'publ' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=new%s"><button type="button" class="btn btn%s-primary">Drafts</button></a>',
    $filter, $menuActive == 'new' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=wait%s"><button type="button" class="btn btn%s-primary">Pending</button></a>',
    $filter, $menuActive == 'wait' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=upload%s"><button type="button" class="btn btn%s-primary">Upload new XML</button></a>',
    $filter, $menuActive == 'upload' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=showScopes%s"><button type="button" class="btn btn%s-primary">IdP scopes</button></a>',
    $filter, $menuActive == 'showScopes' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=EntityStatistics%s"><button type="button" class="btn btn%s-primary">Entity Statistics</button></a>',
    $filter, $menuActive == 'EntityStatistics' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=EcsStatistics%s"><button type="button" class="btn btn%s-primary">ECS statistics</button></a>',
    $filter, $menuActive == 'EcsStatistics' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=ErrorList%s"><button type="button" class="btn btn%s-primary">Errors</button></a>',
    $filter, $menuActive == 'Errors' ? '' : HTML_OUTLINE);


  if ( $userLevel > 4 ) {
    printf('%s    <a href=".?action=URLlist%s"><button type="button" class="btn btn%s-primary">URLlist</button></a>',
      "\n", $filter, $menuActive == 'URLlist' ? '' : HTML_OUTLINE);
  }
  if ( $userLevel > 10 ) {
    printf('%s    <a href=".?action=CleanPending%s">
      <button type="button" class="btn btn%s-primary">Clean Pending</button>
    </a>',
      "\n", $filter, $menuActive == 'CleanPending' ? '' : HTML_OUTLINE);
  }
  print "\n    <br>\n    <br>\n";
}

function validateEntity($entitiesId) {
  $metadata = new Metadata($entitiesId);
  $metadata->validateXML();
  $metadata->validateSAML();
}

function move2Pending($entitiesId) {
  global $db, $html, $display, $userLevel, $menuActive;
  global $EPPN, $mail, $fullName;
  global $mailContacts, $mailRequetser, $SendOut;

  $draftMetadata = new Metadata($entitiesId);

  if ($draftMetadata->entityExists()) {
    if ( $draftMetadata->isIdP() && $draftMetadata->isSP()) {
      $sections = '4.1.1, 4.1.2, 4.2.1 and 4.2.2' ;
    } elseif ($draftMetadata->isIdP()) {
      $sections = '4.1.1 and 4.1.2' ;
    } elseif ($draftMetadata->isSP()) {
      $sections = '4.2.1 and 4.2.2' ;
    }
    $html->showHeaders('Metadata SWAMID - ' . $draftMetadata->entityID());
    $errors = getBlockingErrors($entitiesId);
    if ($errors == '') {
      if (isset($_GET['publishedIn'])) {
        $publish = true;
        if ($_GET['publishedIn'] < 1) {
          $errors .= "Missing where to publish Metadata.\n";
          $publish = false;
        }
        if (!isset($_GET['OrganisationOK'])) {
          $errors .= "You must fulfill sections $sections in SWAMID SAML WebSSO Technology Profile.\n";
          $publish = false;
        }
      } else {
        $publish = false;
      }

      if ($publish) {
        $menuActive = 'wait';
        showMenu();

        $fullName = iconv("UTF-8", "ISO-8859-1", $fullName);

        setupMail();

        $addresses = $draftMetadata->getTechnicalAndAdministrativeContacts();
        if ($SendOut) {
          $mailRequetser->addAddress($mail);
          foreach ($addresses as $address) {
            $mailContacts->addAddress($address);
          }
        }

        $hostURL = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'];

        //Content
        $mailContacts->isHTML(true);
        $mailContacts->Body    = sprintf("<p>Hi.</p>\n<p>%s (%s, %s) has requested an update of %s</p>\n<p>You have received this mail because you are either the new or old technical and/or administrative contact.</p>\n<p>You can see the new version at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>\n<p>If you do not approve this update please forward this mail to SWAMID Operations (operations@swamid.se) and request for the update to be denied.</p>", $EPPN, $fullName, $mail, $draftMetadata->entityID(), $hostURL, $entitiesId, $hostURL, $entitiesId);
        $mailContacts->AltBody = sprintf("Hi.\n\n%s (%s, %s) has requested an update of %s\n\nYou have received this mail because you are either the new or old technical and/or administrative contact.\n\nYou can see the new version at %s/?showEntity=%d\n\nIf you do not approve this update please forward this mail to SWAMID Operations (operations@swamid.se) and request for the update to be denied.", $EPPN, $fullName, $mail, $draftMetadata->entityID(), $hostURL, $entitiesId);

        $mailRequetser->isHTML(true);
        $mailRequetser->Body    = sprintf("<p>Hi.</p>\n<p>You have requested an update of %s</p>\n<p>Please forward this email to SWAMID Operations (operations@swamid.se).</p>\n<p>The new version can be found at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>\n<p>An email has also been sent to the following addresses since they are the new or old technical and/or administrative contacts : </p>\n<p><ul>\n<li>%s</li>\n</ul>\n", $draftMetadata->entityID(), $hostURL, $entitiesId, $hostURL, $entitiesId,implode ("</li>\n<li>",$addresses));
        $mailRequetser->AltBody = sprintf("Hi.\n\nYou have requested an update of %s\n\nPlease forward this email to SWAMID Operations (operations@swamid.se).\n\nThe new version can be found at %s/?showEntity=%d\n\nAn email has also been sent to the following addresses since they are the new or old technical and/or administrative contacts : %s\n\n", $draftMetadata->entityID(), $hostURL, $entitiesId, implode (", ",$addresses));

        $shortEntityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $draftMetadata->entityID());
        $publishedMetadata = new Metadata($draftMetadata->entityID(), 'prod');

        if ($publishedMetadata->entityExists()) {
          $mailContacts->Subject  = 'Info : Updated SWAMID metadata for ' . $shortEntityid;
          $mailRequetser->Subject = 'Updated SWAMID metadata for ' . $shortEntityid;
          $shadowMetadata = new Metadata($draftMetadata->entityID(), 'Shadow');
          $shadowMetadata->importXML($publishedMetadata->xml());
          $shadowMetadata->updateFeedByValue($publishedMetadata->feedValue());
          $shadowMetadata->validateXML();
          $shadowMetadata->validateSAML();
          $oldEntitiesId = $shadowMetadata->id();
        } else {
          $mailContacts->Subject  = 'Info : New SWAMID metadata for ' . $shortEntityid;
          $mailRequetser->Subject = 'New SWAMID metadata for ' . $shortEntityid;
          $oldEntitiesId = 0;
        }

        try {
          $mailContacts->send();
        } catch (Exception $e) {
          echo 'Message could not be sent to contacts.<br>';
          echo 'Mailer Error: ' . $mailContacts->ErrorInfo . '<br>';
        }

        try {
          $mailRequetser->send();
        } catch (Exception $e) {
          echo 'Message could not be sent to requester.<br>';
          echo 'Mailer Error: ' . $mailRequetser->ErrorInfo . '<br>';
        }

        printf ("    <p>You should have got an email with information on how to proceed</p>\n    <p>Information has also been sent to the following new or old technical and/or administrative contacts:</p>\n    <ul>\n      <li>%s</li>\n    </ul>\n", implode ("</li>\n    <li>",$addresses));
        printf ('    <hr>%s    <a href=".?showEntity=%d"><button type="button" class="btn btn-primary">Back to entity</button></a>',"\n",$entitiesId);
        $draftMetadata->updateFeedByValue($_GET['publishedIn']);
        $draftMetadata->moveDraftToPending($oldEntitiesId);
      } else {
        $menuActive = 'new';
        showMenu();
        if ($errors != '') {
          printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">
        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>',
           "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
        }
        printf('%s    <p>You are about to request publication of <b>%s</b></p>', "\n", $draftMetadata->entityID());
        $publishedMetadata = new Metadata($draftMetadata->entityID(), 'prod');

        $publishArrayOld = array();
        if ($publishedMetadata->entityExists()) {
          $oldPublishedValue = $publishedMetadata->feedValue();
          if (($oldPublishedValue & 2) == 2) $publishArrayOld[] = 'SWAMID';
          if (($oldPublishedValue & 4) == 4) $publishArrayOld[] = 'eduGAIN';
          if ($oldPublishedValue == 1) $publishArrayOld[] = 'SWAMID-testing';
          printf('%s    <p>Currently published in <b>%s</b></p>', "\n", implode (' and ', $publishArrayOld));
        } else {
          $oldPublishedValue = $draftMetadata->isIdP() ? 7 : 3;
        }
        printf('    <h5>The entity should be published in:</h5>
    <form>
      <input type="hidden" name="move2Pending" value="%d">
      <input type="radio" id="SWAMID_eduGAIN" name="publishedIn" value="7"%s>
      <label for="SWAMID_eduGAIN">SWAMID and eduGAIN</label><br>
      <input type="radio" id="SWAMID_Testing" name="publishedIn" value="3"%s>
      <label for="SWAMID_Testing">SWAMID</label><br>
      <input type="radio" id="Testing" name="publishedIn" value="1"%s>
      <label for="Testing">Testing only</label>
      <br>
      <h5> Confirmation:</h5>
      <input type="checkbox" id="OrganisationOK" name="OrganisationOK">
      <label for="OrganisationOK">I confirm that this Entity fulfils sections <b>%s</b> in
        <a href="http://www.swamid.se/policy/technology/saml-websso" target="_blank">
          SWAMID SAML WebSSO Technology Profile
        </a>
      </label><br>
      <br>
      <input type="submit" name="action" value="Request publication">
    </form>
    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>',
        $entitiesId,
        $oldPublishedValue == 7 ? ' checked' : '',
        $oldPublishedValue == 3 ? ' checked' : '',
        $oldPublishedValue == 1 ? ' checked' : '', $sections, $entitiesId);
      }
    } else {
      printf('
    <div class="row alert alert-danger" role="alert">
      <div class="col">
        <b>Please fix the following errors before requesting publication:</b><br>
        %s
      </div>
    </div>
    <a href=".?showEntity=%d">
      <button type="button" class="btn btn-outline-primary">Return to Entity</button>
    </a>',
        str_ireplace("\n", "<br>", $errors), $entitiesId);
    }
  } else {
    $html->showHeaders('Metadata SWAMID - NotFound');
    $menuActive = 'new';
    showMenu();
    print "Can't find Entity";
  }
  print "\n";
}

function annualConfirmation($entitiesId){
  global $html, $menuActive;
  global $EPPN, $mail, $fullName;

  $metadata = new Metadata($entitiesId);
  if ($metadata->status() == 1) {
    # Entity is Published
    $errors = getErrors($entitiesId);
    if ($errors == '') {
      $user_id = $metadata->getUserId($EPPN);
      if ($metadata->isResponsible()) {
        # User have access to entity
        if ( $metadata->isIdP() && $metadata->isSP()) {
          $sections = '4.1.1, 4.1.2, 4.2.1 and 4.2.2' ;
        } elseif ($metadata->isIdP()) {
          $sections = '4.1.1 and 4.1.2' ;
        } elseif ($metadata->isSP()) {
          $sections = '4.2.1 and 4.2.2' ;
        }

        if (isset($_GET['entityIsOK'])) {
          $metadata->updateUser($EPPN, $mail, $fullName, true);
          $confirm = true;
        } else {
          $errors .= isset($_GET['FormVisit'])
            ? "You must fulfill sections $sections in SWAMID SAML WebSSO Technology Profile.\n"
            : '';
          $confirm = false;
        }

        if ($confirm) {
          $metadata->confirmEntity($user_id);
          $menuActive = 'myEntities';
          showMyEntities();
        } else {
          $html->showHeaders('Metadata SWAMID - ' . $metadata->entityID());
          $menuActive = '';
          showMenu();
          if ($errors != '') {
            printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
          }
          printf('%s    <p>You are confirming that <b>%s</b> is operational and fulfils SWAMID SAML WebSSO Technology Profile</p>%s', "\n", $metadata->entityID(), "\n");
          printf('    <form>%s      <input type="hidden" name="Entity" value="%d">%s      <input type="hidden" name="FormVisit" value="true">%s      <h5> Confirmation:</h5>%s      <input type="checkbox" id="entityIsOK" name="entityIsOK">%s      <label for="entityIsOK">I confirm that this Entity fulfils sections <b>%s</b> in <a href="http://www.swamid.se/policy/technology/saml-websso" target="_blank">SWAMID SAML WebSSO Technology Profile</a></label><br>%s      <br>%s      <input type="submit" name="action" value="Annual Confirmation">%s    </form>%s    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>%s', "\n", $entitiesId, "\n", "\n", "\n", "\n", $sections, "\n", "\n", "\n", "\n", $entitiesId, "\n");
        }
      } else {
        # User have no access yet.
        requestAccess($entitiesId);
      }
    } else {
      $html->showHeaders('Metadata SWAMID - ' . $metadata->entityID());
      printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <b>Please fix the following errors before confirming:</b><br>%s        %s%s      </div>%s    </div>%s    <a href=".?showEntity=%d"><button type="button" class="btn btn-outline-primary">Return to Entity</button></a>%s', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n", "\n", $entitiesId, "\n");
    }
  } else {
    $html->showHeaders('Metadata SWAMID - NotFound');
    $menuActive = 'new';
    showMenu();
    print "Can't find Entity";
  }
}

function requestRemoval($entitiesId) {
  global $db, $html, $menuActive;
  global $EPPN, $mail, $fullName;
  global $mailContacts, $mailRequetser, $SendOut;
  $metadata = new Metadata($entitiesId);
  if ($metadata->status() == 1) {
    $user_id = $metadata->getUserId($EPPN);
    if ($metadata->isResponsible()) {
      # User have access to entity
      $html->showHeaders('Metadata SWAMID - ' . $metadata->entityID());
      if (isset($_GET['confirmRemoval'])) {
        $menuActive = 'publ';
        showMenu();
        $fullName = iconv("UTF-8", "ISO-8859-1", $fullName);

        setupMail();

        if ($SendOut)
          $mailRequetser->addAddress($mail);

        $addresses = array();
        $contactHandler = $db->prepare("SELECT DISTINCT emailAddress FROM ContactPerson WHERE entity_id = :Entity_ID AND (contactType='technical' OR contactType='administrative')");
        $contactHandler->bindParam(':Entity_ID',$entitiesId);
        $contactHandler->execute();
        while ($address = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
          if ($SendOut)
            $mailContacts->addAddress(substr($address['emailAddress'],7));
          $addresses[] = substr($address['emailAddress'],7);
        }

        $hostURL = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'];

        //Content
        $mailContacts->isHTML(true);
        $mailContacts->Body    = sprintf("<p>Hi.</p>\n<p>%s (%s, %s) has requested removal of the entity with the entityID %s from the SWAMID metadata.</p>\n<p>You have received this mail because you are either the technical and/or administrative contact.</p>\n<p>You can see the current metadata at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>\n<p>If you do not approve request please forward this mail to SWAMID Operations (operations@swamid.se) and request for the removal to be denied.</p>", $EPPN, $fullName, $mail, $metadata->entityID(), $hostURL, $entitiesId, $hostURL, $entitiesId);
        $mailContacts->AltBody = sprintf("Hi.\n\n%s (%s, %s) has requested removal of the entity with the entityID %s from the SWAMID metadata.\n\nYou have received this mail because you are either the technical and/or administrative contact.\n\nYou can see the current metadata at %s/?showEntity=%d\n\nIf you do not approve this request please forward this mail to SWAMID Operations (operations@swamid.se) and request for the removal to be denied.", $EPPN, $fullName, $mail, $metadata->entityID(), $hostURL, $entitiesId);

        $mailRequetser->isHTML(true);
        $mailRequetser->Body   = sprintf("<p>Hi.</p>\n<p>You have requested removal of the entity with the entityID %s from the SWAMID metadata.</p>\n<p>Please forward this email to SWAMID Operations (operations@swamid.se).</p>\n<p>The current metadata can be found at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>\n<p>An email has also been sent to the following addresses since they are the technical and/or administrative contacts : </p>\n<p><ul>\n<li>%s</li>\n</ul>\n", $metadata->entityID(), $hostURL, $entitiesId, $hostURL, $entitiesId,implode ("</li>\n<li>",$addresses));
        $mailRequetser->AltBody = sprintf("Hi.\n\nYou have requested removal of the entity with the entityID %s from the SWAMID metadata.\n\nPlease forward this email to SWAMID Operations (operations@swamid.se).\n\nThe current metadata can be found at %s/?showEntity=%d\n\nAn email has also been sent to the following addresses since they are the technical and/or administrative contacts : %s\n\n", $metadata->entityID(), $hostURL, $entitiesId, implode (", ",$addresses));

        $shortEntityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $metadata->entityID());
        $mailContacts->Subject  = 'Info : Request to remove SWAMID metadata for ' . $shortEntityid;
        $mailRequetser->Subject = 'Request to remove SWAMID metadata for ' . $shortEntityid;

        try {
          $mailContacts->send();
        } catch (Exception $e) {
          echo 'Message could not be sent to contacts.<br>';
          echo 'Mailer Error: ' . $mailContacts->ErrorInfo . '<br>';
        }

        try {
          $mailRequetser->send();
        } catch (Exception $e) {
          echo 'Message could not be sent to requester.<br>';
          echo 'Mailer Error: ' . $mailRequetser->ErrorInfo . '<br>';
        }

        printf ("    <p>You should have got an email with information on how to proceed</p>\n    <p>Information has also been sent to the following technical and/or administrative contacts:</p>\n    <ul>\n      <li>%s</li>\n    </ul>\n", implode ("</li>\n    <li>",$addresses));
        printf ('    <hr>%s    <a href=".?showEntity=%d"><button type="button" class="btn btn-primary">Back to entity</button></a>',"\n",$entitiesId);
      } else {
        $menuActive = 'publ';
        showMenu();
        printf('%s    <p>You are about to request removal of the entity with the entityID <b>%s</b> from the SWAMID metadata.</p>', "\n", $metadata->entityID());
        if (($metadata->feedValue() & 2) == 2) $publishArray[] = 'SWAMID';
        if (($metadata->feedValue() & 4) == 4) $publishArray[] = 'eduGAIN';
        if ($metadata->feedValue() == 1) $publishArray[] = 'SWAMID-testing';
        printf('%s    <p>Currently published in <b>%s</b></p>%s', "\n", implode (' and ', $publishArray), "\n");
        printf('    <form>%s      <input type="hidden" name="Entity" value="%d">%s      <input type="checkbox" id="confirmRemoval" name="confirmRemoval">%s      <label for="confirmRemoval">I confirm that this Entity should be removed</label><br>%s      <br>%s      <input type="submit" name="action" value="Request removal">%s    </form>%s    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', "\n", $entitiesId, "\n", "\n", "\n", "\n", "\n", "\n" ,$entitiesId);
      }
    } else {
      # User have no access yet.
      requestAccess($entitiesId);
    }
  } else {
    $html->showHeaders('Metadata SWAMID - NotFound');
    $menuActive = 'publ';
    showMenu();
    print "Can't find Entity";
  }
  print "\n";
}

function setupMail() {
  global $mailContacts, $mailRequetser;
  global $SMTPHost, $SASLUser, $SASLPassword, $MailFrom;

  $mailContacts = new PHPMailer(true);
  $mailRequetser = new PHPMailer(true);
  /*$mailContacts->SMTPDebug = 2;
  $mailRequetser->SMTPDebug = 2;*/
  $mailContacts->isSMTP();
  $mailRequetser->isSMTP();
  $mailContacts->Host = $SMTPHost;
  $mailRequetser->Host = $SMTPHost;
  $mailContacts->SMTPAuth = true;
  $mailRequetser->SMTPAuth = true;
  $mailContacts->SMTPAutoTLS = true;
  $mailRequetser->SMTPAutoTLS = true;
  $mailContacts->Port = 587;
  $mailRequetser->Port = 587;
  $mailContacts->SMTPAuth = true;
  $mailRequetser->SMTPAuth = true;
  $mailContacts->Username = $SASLUser;
  $mailRequetser->Username = $SASLUser;
  $mailContacts->Password = $SASLPassword;
  $mailRequetser->Password = $SASLPassword;
  $mailContacts->SMTPSecure = 'tls';
  $mailRequetser->SMTPSecure = 'tls';

  //Recipients
  $mailContacts->setFrom($MailFrom, 'Metadata - Admin');
  $mailRequetser->setFrom($MailFrom, 'Metadata - Admin');
  $mailContacts->addBCC('bjorn@sunet.se');
  $mailRequetser->addBCC('bjorn@sunet.se');
  $mailContacts->addReplyTo('operations@swamid.se', 'SWAMID Operations');
  $mailRequetser->addReplyTo('operations@swamid.se', 'SWAMID Operations');
}

function move2Draft($entitiesId) {
  global $db, $html, $display, $menuActive;
  global $EPPN,$mail;
  $entityHandler = $db->prepare('SELECT `entityID`, `xml` FROM Entities WHERE `status` = 2 AND `id` = :Id;');
  $entityHandler->bindParam(':Id', $entitiesId);
  $entityHandler->execute();
  if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    if (isset($_GET['action'])) {
      $draftMetadata = new Metadata($entity['entityID'], 'New');
      $draftMetadata->importXML($entity['xml']);
      $draftMetadata->validateXML();
      $draftMetadata->validateSAML(true);
      $menuActive = 'new';
      $draftMetadata->copyResponsible($entitiesId);
      showEntity($draftMetadata->id());
      $oldMetadata = new Metadata($entitiesId);
      $oldMetadata->removeEntity();
    } else {
      $html->showHeaders('Metadata SWAMID - ' . $entity['entityID']);
      $menuActive = 'wait';
      showMenu();
      printf('%s    <p>You are about to cancel your request for publication of <b>%s</b></p>', "\n", $entity['entityID']);
      printf('    <form>
      <input type="hidden" name="move2Draft" value="%d">
      <input type="submit" name="action" value="Confirm cancel publication request">
    </form>
    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', $entitiesId, $entitiesId);
    }
  } else {
    $html->showHeaders('Metadata SWAMID - NotFound');
    $menuActive = 'wait';
    showMenu();
    print "Can't find Entity";
  }
  print "\n";
}

function mergeEntity($entitiesId, $oldEntitiesId) {
  require_once '../include/MetadataEdit.php';
  $metadata = new MetadataEdit($entitiesId, $oldEntitiesId);
  $metadata->mergeFrom();
}

function removeEntity($entitiesId) {
  global $db, $html, $menuActive;
  $entityHandler = $db->prepare('SELECT entityID, status FROM Entities WHERE id = :Id;');
  $entityHandler->bindParam(':Id', $entitiesId);
  $entityHandler->execute();
  if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    $html->showHeaders('Metadata SWAMID - ' . $entity['entityID']);
    $OK2Remove = true;
    switch($entity['status']) {
      case 2 :
        $menuActive = 'wait';
        $button = 'Confirm delete pending';
        $from = 'Pending';
        $action = 'delete the pending entity';
        break;
      case 3 :
        $menuActive = 'new';
        $button = 'Confirm discard draft';
        $from = 'Drafts';
        $action = 'discard the draft';
        break;
      case 6 :
        $menuActive = 'wait';
        $button = 'Confirm delete shadow';
        $from = 'Shadow entity';
        $action = 'delete the shadow entity';
        break;
      default :
        $OK2Remove = false;
    }
    showMenu();
    if ($OK2Remove) {
      if (isset($_GET['action']) && $_GET['action'] == $button ) {
        $metadata = new Metadata($entitiesId);
        $metadata->removeEntity();
        printf('    <p>You have removed <b>%s</b> from %s</p>%s', $entity['entityID'], $from, "\n");
      } else {
        printf('    <p>You are about to %s of <b>%s</b></p>%s    <form>%s      <input type="hidden" name="removeEntity" value="%d">%s      <input type="submit" name="action" value="%s">%s    </form>%s    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', $action, $entity['entityID'], "\n", "\n", $entitiesId, "\n", $button, "\n", "\n",  $entitiesId);
      }
    } else {
      print "You can't Remove / Discard this entity";
    }
  } else {
    $html->showHeaders('Metadata SWAMID - NotFound');
    $menuActive = 'new';
    showMenu();
    print "Can't find Entity";
  }
  print "\n";
}

function checkAccess($entitiesId, $userID, $userLevel, $minLevel, $showError=false) {
  global $html;
  if ($userLevel >= $minLevel)
    return true;
  $metadata = new Metadata($entitiesId);
  $metadata->getUser($userID);
  if ($metadata->isResponsible()) {
    return true;
  } else {
    if ($showError) {
      $html->showHeaders('Metadata SWAMID');
      print "You doesn't have access to this entityID";
      printf('%s      <a href=".?showEntity=%d"><button type="button" class="btn btn-outline-danger">Back to entity</button></a>', "\n", $entitiesId);
    }
    return false;
  }
}

# Request access to an entity
function requestAccess($entitiesId) {
  global $html, $menuActive;
  global $EPPN, $mail, $fullName;
  global $mailContacts, $mailRequetser, $SendOut;

  $metadata = new Metadata($entitiesId);
  if ($metadata->entityExists()) {
    $user_id = $metadata->getUserId($EPPN);
    if ($metadata->isResponsible()) {
      # User already have access.
      $html->showHeaders('Metadata SWAMID - ' . $metadata->entityID());
      $menuActive = '';
      showMenu();
      printf('%s    <p>You already have access to <b>%s</b></p>%s', "\n", $metadata->entityID(), "\n");
      printf('    <a href="./?showEntity=%d"><button>Return to Entity</button></a>%s', $entitiesId, "\n");
    } else {
      $errors = '';
      $addresses = $metadata->getTechnicalAndAdministrativeContacts();

      if (isset($_GET['requestAccess'])) {
        # We are commint from the Form.
        # Fetch user_id again and make sure user exists
        $user_id = $metadata->getUserId($EPPN, $mail, $fullName, true);
        # Get code to send in email
        $requestCode = urlencode($metadata->createAccessRequest($user_id));
        $fullName = iconv("UTF-8", "ISO-8859-1", $fullName);
        setupMail();
        if ($SendOut) {
          foreach ($addresses as $address) {
            $mailContacts->addAddress($address);
          }
        }
        $hostURL = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'];

        //Content
        $mailContacts->isHTML(true);
        $mailContacts->Body    = sprintf("<p>Hi.</p>\n<p>%s (%s, %s) has requested access to update %s</p>\n<p>You have received this mail because you are either the technical and/or administrative contact.</p>\n<p>If you approve, please click on this link <a href=\"%s/admin/?approveAccessRequest=%s\">%s/?approveAccessRequest=%s</a></p>\n<p>If you do not approve, you can ignore this email. No changes will be made.</p>", $EPPN, $fullName, $mail, $metadata->entityID(), $hostURL, $requestCode, $hostURL, $requestCode);
        $mailContacts->AltBody = sprintf("Hi.\n\n%s (%s, %s) has requested access to update %s\n\nYou have received this mail because you are either the technical and/or administrative contact.\n\nIf you approve, please click on this link %s/admin/?approveAccessRequest=%s\n\nIf you do not approve, you can ignore this email. No changes will be made.", $EPPN, $fullName, $mail, $metadata->entityID(), $hostURL, $requestCode);
        $info = sprintf(
          "<p>The request has been sent to: %s</p>\n<p>Contact them and ask them to accept your request.</p>\n",
          implode (", ",$addresses));

        $shortEntityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $metadata->entityID());

        $mailContacts->Subject = 'Access request for ' . $shortEntityid;

        try {
          $mailContacts->send();
        } catch (Exception $e) {
          echo 'Message could not be sent to contacts.<br>';
          echo 'Mailer Error: ' . $mailContacts->ErrorInfo . '<br>';
        }
        $menuActive = '';
        showInfo($info);
      } else {
        $errors .= isset($_GET['FormVisit']) ? "You must check the box to confirm.\n" : '';
        $html->showHeaders('Metadata SWAMID - ' . $metadata->entityID());
        $menuActive = '';
        showMenu();
        if ($errors != '') {
          printf('%s    <div class="row alert alert-danger" role="alert">
      <div class="col">
        <div class="row"><b>Errors:</b></div>
        <div class="row">%s</div>
      </div>%s    </div>', "\n", str_ireplace("\n", "<br>", $errors), "\n");
        }
        printf('%s    <p>You do not have access to <b>%s</b></p>%s', "\n", $metadata->entityID(), "\n");
        printf('    <form>
      <input type="hidden" name="Entity" value="%d">
      <input type="hidden" name="FormVisit">
      <h5>Request access:</h5>
      <input type="checkbox" id="requestAccess" name="requestAccess">
      <label for="requestAccess">I confirm that I have the right to update this entity.</label><br>
      <p>A mail will be sent to the following addresses with further instructions: %s.</p>
      <input type="submit" name="action" value="Request Access">
    </form>
    <a href="./?showEntity=%d"><button>Return to Entity</button></a>%s',
          $entitiesId, implode (", ",$addresses),  $entitiesId, "\n");
      }
    }
  }
}

# Return Blocking errors
function getBlockingErrors($entitiesId) {
  global $db;
  $errors = '';

  $entityHandler = $db->prepare('SELECT `entityID`, `errors` FROM Entities WHERE `id` = :Id;');
  $entityHandler->bindParam(':Id', $entitiesId);
  
  $entityHandler->execute();
  if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    $errors .= $entity['errors'];
  }
  return $errors;
}

# Return All errors, both blocking and nonblocking
function getErrors($entitiesId) {
  global $db;
  $errors = '';

  $entityHandler = $db->prepare('SELECT `entityID`, `status`, `validationOutput`, `warnings`, `errors`, `errorsNB` FROM Entities WHERE `id` = :Id;');
  $entityHandler->bindParam(':Id', $entitiesId);

  $entityHandler->execute();
  if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    $errors = $entity['errorsNB'];
    $errors .= $entity['errors'];
  }
  return $errors;
}

function showHelp() {
  global $html, $display, $menuActive;
  $html->showHeaders('Metadata SWAMID');
  $menuActive = '';
  showMenu();
  $display->showHelp();
}

function approveAccessRequest($code) {
  global $EPPN;
  $codeArray = explode(':', base64_decode($code));
  if (isset($codeArray[2])) {
    $metadata = new Metadata($codeArray[0]);
    if ($metadata->entityExists()) {
      $result = $metadata->validateCode($codeArray[1], $codeArray[2], $EPPN);
      if ($result['returnCode'] < 10) {
        $info = $result['info'];
        if ($result['returnCode'] == 2) {
          global $SMTPHost, $SASLUser, $SASLPassword, $MailFrom;

          $mail = new PHPMailer(true);
          /*$mail->SMTPDebug = 2;*/
          $mail->isSMTP();
          $mail->Host = $SMTPHost;
          $mail->SMTPAuth = true;
          $mail->SMTPAutoTLS = true;
          $mail->Port = 587;
          $mail->SMTPAuth = true;
          $mail->Username = $SASLUser;
          $mail->Password = $SASLPassword;
          $mail->SMTPSecure = 'tls';

          //Recipients
          $mail->setFrom($MailFrom, 'Metadata');
          $mail->addReplyTo('operations@swamid.se', 'SWAMID Operations');
          $mail->addAddress($result['email']);
          $mail->Body    = sprintf("<p>Hi.</p>\n<p>Your access to %s have been granted.</p>\n<p>-- <br>This mail was sent by SWAMID Metadata Admin Tool, a service provided by SWAMID Operations. If you've any questions please contact operations@swamid.se.</p>", $metadata->entityID());
          $mail->AltBody = sprintf("Hi.\n\nYour access to %s have been granted.\n\n-- \nThis mail was sent by SWAMID Metadata Admin Tool, a service provided by SWAMID Operations. If you've any questions please contact operations@swamid.se.", $metadata->entityID());
          $mail->Subject = 'Access granted for ' . $shortEntityid;

          $info = sprintf('<h3>Access granted</h3>Access to <b>%s</b> added for %s (%s).',
            $metadata->entityID(), $result['fullName'], $result['email']);

          try {
            $mail->send();
          } catch (Exception $e) {
            echo 'Message could not be sent to contacts.<br>';
            echo 'Mailer Error: ' . $mail->ErrorInfo . '<br>';
          }
        }
        showText($info);
      } else {
        showError($result['info']);
      }
    } else {
      showError('Invalid code');
    }
  } else {
    showError('Invalid code');
  }
}


function showText($text, $showMenu = false, $error = false) {
  global $html, $menuActive;
  $html->showHeaders('Metadata SWAMID');
  if ($showMenu) showMenu();
  printf ('    <div class="row">%s      <div class="col">%s        %s%s      </div>%s    </div>%s', "\n", "\n", $text, "\n", "\n", "\n");
}

function showError($text) {
  global $html;
  $html->showHeaders('Metadata SWAMID - Error');
  printf('%s', $text);
}

function showInfo($text) {
  global $html, $menuActive;
  $html->showHeaders('Metadata SWAMID');
  showMenu();
  printf ('    <div class="row">%s      <div class="col">%s        %s%s      </div>%s    </div>%s', "\n", "\n", $text, "\n", "\n", "\n");
}
