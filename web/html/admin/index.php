<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

const BIND_ASSURANCE = ':Assurance';
const HTML_CLASS_FA_UP = '<i class="fa fa-arrow-up"></i>';
const HTML_CLASS_FA_DOWN = '<i class="fa fa-arrow-down"></i>';
const HTML_OUTLINE = '-outline';
const HTML_CHECKED = ' checked';
const HTML_TEXT_CFE = "Can't find Entity";
const HTML_TEXT_MCNBS = 'Message could not be sent to contacts.<br>';
const HTML_TEXT_ME = 'Mailer Error: ';
const HTML_TEXT_MEDFOR = ' metadata for ';
const HTML_TEXT_YMFS = 'You must fulfill sections %s in %s.%s';
const HTML_INVLD_RQ_METHOD = 'Invalid request method.';

const REGEXP_ENTITYID = '/^https?:\/\/([^:\/]*)\/.*/';

const CLASS_PARSER = '\metadata\ParseXML';
const CLASS_VALIDATOR = '\metadata\Validate';

//Load composer's autoloader
require_once '../vendor/autoload.php';

$config = new \metadata\Configuration();

$html = new \metadata\HTML();

/* BEGIN RAF Logging */
$assuranceHandler = $config->getDb()->prepare(
  'INSERT INTO `assuranceLog` (`entityID`, `assurance`, `logDate`)
    VALUES (:EntityID, :Assurance, NOW()) ON DUPLICATE KEY UPDATE `logDate` = NOW()');
$assuranceHandler->bindParam(':EntityID', $_SERVER['Shib-Identity-Provider']);
if (isset($_SERVER['eduPersonAssurance'])) {
  foreach (explode(';', $_SERVER['eduPersonAssurance']) as $eduPersonAssurance) {
    if (substr($eduPersonAssurance, 0, 33) ==  'https://refeds.org/assurance/IAP/') {
      $assuranceHandler->bindValue(BIND_ASSURANCE,
        substr(str_replace ('https://refeds.org/assurance/IAP/', 'RAF-', $eduPersonAssurance),0,10));
      $assuranceHandler->execute();
    } elseif (substr($eduPersonAssurance, 0, 40) ==  'http://www.swamid.se/policy/assurance/al') { # NOSONAR Should be http://
      $assuranceHandler->bindValue(BIND_ASSURANCE,
      substr(str_replace ('http://www.swamid.se/policy/assurance/al', 'SWAMID-AL', $eduPersonAssurance),0,10)); # NOSONAR Should be http://
      $assuranceHandler->execute();
    }
  }
} else {
  $assuranceHandler->bindValue(BIND_ASSURANCE, 'None');
  $assuranceHandler->execute();
}
/* END RAF Logging */

$errorURL = isset($_SERVER['Meta-errorURL'])
  ? '<a href="' . $_SERVER['Meta-errorURL'] . '">Mer information</a><br>'
  : '<br>';
$errorURL = str_replace(array('ERRORURL_TS', 'ERRORURL_RP', 'ERRORURL_TID'),
  array(time(), 'https://metadata.swamid.se/shibboleth', $_SERVER['Shib-Session-ID']),
  $errorURL);

$errors = '';

if (isset($_SERVER['Meta-Assurance-Certification'])) {
  $AssuranceCertificationFound = false;
  foreach (explode(';',$_SERVER['Meta-Assurance-Certification']) as $AssuranceCertification) {
    if ($AssuranceCertification == 'http://www.swamid.se/policy/assurance/al1') { # NOSONAR Should be http://
      $AssuranceCertificationFound = true;
    }
  }
  if (! $AssuranceCertificationFound) {
    $errors .= sprintf('%s has no AssuranceCertification (http://www.swamid.se/policy/assurance/al1) ', # NOSONAR Should be http://
      htmlspecialchars($_SERVER['Shib-Identity-Provider']));
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

$foundEmployee = false;
$foundStudent = false;
$foundMember = false;
$foundAffiliate = false;
if (isset($_SERVER['eduPersonScopedAffiliation'])) {
  foreach (explode(';',$_SERVER['eduPersonScopedAffiliation']) as $ScopedAffiliation) {
    switch(explode('@',$ScopedAffiliation)[0]) {
      case 'employee' :
        $foundEmployee = true;
        break;
      case 'student' :
        $foundStudent = true;
        break;
      case 'member' :
        $foundMember = true;
        break;
      case 'affiliate' :
        $foundAffiliate = true;
        break;
      default :
    }
  }
} elseif (isset($_SERVER['eduPersonAffiliation'])) {
  $foundEmployee = false;
  foreach (explode(';',$_SERVER['eduPersonAffiliation']) as $Affiliation) {
    switch($Affiliation) {
      case 'employee' :
        $foundEmployee = true;
        break;
      case 'student' :
        $foundStudent = true;
        break;
      case 'member' :
        $foundMember = true;
        break;
      default :
    }
  }
} else {
  if (isset($_SERVER['Shib-Identity-Provider'])
    && $_SERVER['Shib-Identity-Provider'] == 'https://login.idp.eduid.se/idp.xml') {
    #OK to not send eduPersonScopedAffiliation / eduPersonAffiliation
    $foundMember = true;
  } else {
    $errors .=
      'Missing eduPersonScopedAffiliation and eduPersonAffiliation in SAML response<br>One of them is required<br>';
  }
}

if ($foundMember) {
  if ($foundStudent && ! $foundEmployee) {
    $errors .=
      'Expected affiliations are missing in eduPersonScopedAffiliation (must contain the subset';
    $errors .= ' of either <b>employee</b> + <b>member</b>, <b>affiliate</b> or only <b>member</b>).<br>';
    $errors .=
      'Please check <a href="https://wiki.sunet.se/pages/viewpage.action?pageId=17138034">Wiki</a> for more info.<br>';
    $errors .=
      'Login to <a href="https://release-check.swamid.se/result/">release-check</a> to verify.<br>';
  }
} elseif (!$foundAffiliate) {
  $errors .=
      'Expected affiliations are missing in eduPersonScopedAffiliation (must contain the subset';
  $errors .= ' of either <b>employee</b> + <b>member</b>, <b>affiliate</b> or only <b>member</b>).<br>';
  $errors .=
    'Please check <a href="https://wiki.sunet.se/pages/viewpage.action?pageId=17138034">Wiki</a> for more info.<br>';
  $errors .=
    'Login to <a href="https://release-check.swamid.se/result/">release-check</a> to verify.<br>';
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

// Safeguard POST requests against CSRF
if (sizeof($_POST) || $_SERVER['REQUEST_METHOD'] == 'POST') {
  $referer = $_SERVER['HTTP_REFERER'] ?? '';
  $baseURL = $config->baseURL();
  if (!$referer || substr($referer, 0, strlen($baseURL)) != $baseURL) {
    http_response_code(400); // Bad Request
    print 'Invalid request';
    exit;
  }
}

if ($errors != '') {
  $html->showHeaders('Problem');
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

$userLevel = $config->getUserLevels()[$EPPN] ?? 1;
$displayName = '<div> Logged in as : <br> ' . htmlspecialchars($fullName) . ' (' . htmlspecialchars($EPPN) .')</div>';
$html->setDisplayName($displayName);

$display = new \metadata\MetadataDisplay();

if (isset($_FILES['XMLfile'])) {
  importXML();
} elseif (isset($_REQUEST['edit'])) {
  if (isset($_REQUEST['Entity']) && (isset($_REQUEST['oldEntity']))) {
    $editMeta = new \metadata\MetadataEdit($_REQUEST['Entity'], $_REQUEST['oldEntity']);
    $editMeta->updateUser($EPPN, $mail, $fullName);
    if (checkAccess($_REQUEST['Entity'], $EPPN, $userLevel, 10, true) &&
        checkStatus($_REQUEST['Entity'], 3, $userLevel, 10)) {
      $html->showHeaders('Edit - '.$_REQUEST['edit']);
      $editMeta->edit($_REQUEST['edit']);
    }
  } else {
    showEntityList();
  }
} elseif (isset($_REQUEST['showEntity'])) {
  showEntity($_REQUEST['showEntity']);
} elseif (isset($_REQUEST['validateEntity'])) {
  validateEntity($_REQUEST['validateEntity']);
  showEntity($_REQUEST['validateEntity']);
} elseif (isset($_REQUEST['move2Pending'])) {
  if (checkAccess($_REQUEST['move2Pending'], $EPPN, $userLevel, 10, true)) {
    move2Pending($_REQUEST['move2Pending']);
  }
} elseif (isset($_REQUEST['move2Draft'])) {
  if (checkAccess($_REQUEST['move2Draft'], $EPPN, $userLevel, 10, true)) {
    move2Draft($_REQUEST['move2Draft']);
  }
} elseif (isset($_POST['mergeEntity'])) {
  if (checkAccess($_POST['mergeEntity'],$EPPN,$userLevel,10,true)) {
    if (isset($_POST['oldEntity'])) {
      mergeEntity($_POST['mergeEntity'], $_POST['oldEntity']);
      validateEntity($_POST['mergeEntity']);
    }
    showEntity($_POST['mergeEntity']);
  }
} elseif (isset($_REQUEST['removeEntity'])) {
  if (checkAccess($_REQUEST['removeEntity'],$EPPN,$userLevel,10, true)) {
    removeEntity($_REQUEST['removeEntity']);
  }
} elseif (isset($_POST['removeSSO']) && isset($_POST['type'])) {
  if (checkAccess($_POST['removeSSO'],$EPPN,$userLevel,10, true) &&
      checkStatus($_REQUEST['removeSSO'], 3, $userLevel, 10)) {
    removeSSO($_POST['removeSSO'], $_POST['type']);
  }
} elseif (isset($_REQUEST['rawXML'])) {
  $display->showRawXML($_REQUEST['rawXML']);
} elseif (isset(($_REQUEST['approveAccessRequest']))) {
  approveAccessRequest($_REQUEST['approveAccessRequest']);
} elseif (isset($_REQUEST['showHelp'])) {
  showHelp();
} else {
  $menuActive = 'publ';
  if (isset($_REQUEST['action'])) {
    if (isset($_REQUEST['Entity'])) {
      $entitiesId = $_REQUEST['Entity'];
      switch($_REQUEST['action']) {
        case 'createDraft' :
          if (!sizeof($_POST)) {
            print HTML_INVLD_RQ_METHOD;
          } else {
            $menuActive = 'new';
            $metadata = new \metadata\Metadata($entitiesId);
            $metadata->getUserId($EPPN);
            if ($metadata->isResponsible()) {
              if ($newEntity_id = $metadata->createDraft()) {
                validateEntity($newEntity_id);
                $menuActive = 'new';
                showEntity($newEntity_id);
              }
            } else {
              # User have no access yet.
              requestAccess($entitiesId);
            }
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
        case 'removeSaml1' :
          if (!sizeof($_POST)) {
            print HTML_INVLD_RQ_METHOD;
          } else {
            $metadata = new \metadata\Metadata($entitiesId);
            $metadata->getUserId($EPPN);
            if ($metadata->isResponsible()) {
              $metadata->removeSaml1Support();
              validateEntity($entitiesId);
              $menuActive = 'new';
              showEntity($entitiesId);
            } else {
              # User have no access yet.
              requestAccess($entitiesId);
            }
          }
          break;
        case 'draftRemoveSaml1' :
          if (!sizeof($_POST)) {
            print HTML_INVLD_RQ_METHOD;
          } else {
            $metadata = new \metadata\Metadata($entitiesId);
            $metadata->getUserId($EPPN);
            if ($metadata->isResponsible()) {
              if ($newEntity_id = $metadata->createDraft()) {
                $metadata->removeSaml1Support();
                validateEntity($newEntity_id);
                $menuActive = 'new';
                showEntity($newEntity_id);
              }
            } else {
              # User have no access yet.
              requestAccess($entitiesId);
            }
          }
          break;
        case 'removeObsoleteAlgorithms' :
          if (!sizeof($_POST)) {
            print HTML_INVLD_RQ_METHOD;
          } else {
            $metadata = new \metadata\Metadata($entitiesId);
            $metadata->getUserId($EPPN);
            if ($metadata->isResponsible()) {
              $metadata->removeObsoleteAlgorithms();
              validateEntity($entitiesId);
              showEntity($entitiesId);
            } else {
              # User have no access yet.
              requestAccess($entitiesId);
            }
          }
          break;
        case 'draftRemoveObsoleteAlgorithms' :
          if (!sizeof($_POST)) {
            print HTML_INVLD_RQ_METHOD;
          } else {
            $metadata = new \metadata\Metadata($entitiesId);
            $metadata->getUserId($EPPN);
            if ($metadata->isResponsible()) {
              if ($newEntity_id = $metadata->createDraft()) {
                $metadata->removeObsoleteAlgorithms();
                validateEntity($newEntity_id);
                $menuActive = 'new';
                showEntity($newEntity_id);
              }
            } else {
              # User have no access yet.
              requestAccess($entitiesId);
            }
          }
          break;
        case 'forceAccess' :
          if (!sizeof($_POST)) {
            print HTML_INVLD_RQ_METHOD;
          } else {
            $metadata = new \metadata\Metadata($entitiesId);
            if ($userLevel > 19) {
              $metadata->addAccess2Entity($metadata->getUserId($EPPN, $mail, $fullName, true), $EPPN);
            }
            showEntity($entitiesId);
          }
          break;
        case 'removeEditor' :
          $metadata = new \metadata\Metadata($entitiesId);
          $userIDtoRemove = $_POST['userIDtoRemove']; # request MUST be a POST
          if (checkAccess($entitiesId, $EPPN, $userLevel, 19, true) && ($userIDtoRemove != '') &&
              ($userLevel > 19 || $userIDtoRemove != $metadata->getUserId($EPPN)) ) {
            $metadata->removeAccessFromEntity($userIDtoRemove);
          }
          showEntity($entitiesId);
          break;
        case 'AddImps2IdP' :
          if ($userLevel > 19 && isset($_REQUEST['ImpsId'])) {
            $imps = new \metadata\IMPS();
            $imps->bindIdP2IMPS($entitiesId, $_REQUEST['ImpsId']);
          }
          $metadata = new \metadata\Metadata($entitiesId);
          showEntity($entitiesId);
          break;
        case 'Confirm IMPS' :
          $metadata = new \metadata\Metadata($entitiesId);
          if ($metadata->status() == 1) {
            $metadata->getUserId($EPPN);
            if ($metadata->isResponsible()) {
              $html->showHeaders($metadata->entityID());
              $menuActive = 'publ';
              showMenu();
              $imps = new \metadata\IMPS();
              if ($imps->validateIMPS($entitiesId, $_REQUEST['ImpsId'], $metadata->getUserId($EPPN))) {
                showEntity($entitiesId, false);
              }
            } else {
              # User have no access yet.
              requestAccess($entitiesId);
            }
          } else {
            $html->showHeaders('NotFound');
            $menuActive = 'publ';
            showMenu();
            print HTML_TEXT_CFE;
          }
          break;
        case 'addOrganization2Entity' :
          if (checkAccess($entitiesId, $EPPN, $userLevel, 10, false)) {
            if (isset($_POST['organizationId'])) {
              $updateEntitiesHandler = $config->getDb()->prepare( $userLevel > 19
              ? 'UPDATE Entities SET `OrganizationInfo_id` = :OrgId WHERE `id` = :Id;'
              : 'UPDATE Entities SET `OrganizationInfo_id` = :OrgId WHERE `id` = :Id AND status = 3;');
              $updateEntitiesHandler->execute(array('OrgId' => $_POST['organizationId'], 'Id' => $entitiesId));
            }
            showEntity($entitiesId);
          }
          break;
        case 'copyDefaultOrganization' :
          if (checkAccess($entitiesId, $EPPN, $userLevel, 10, false)) {
            $editMeta = new \metadata\MetadataEdit($entitiesId);
            $editMeta->copyDefaultOrganization();
            showEntity($entitiesId);
          }
          break;
        case 'createOrganizationFromEntity' :
          if (!sizeof($_POST)) {
            print HTML_INVLD_RQ_METHOD;
          } else {
            if ($userLevel > 19) {
              $imps = new \metadata\IMPS();
              $imps->createOrganizationFromEntity($entitiesId);
            }
            showEntity($entitiesId);
          }
          break;
        default :
          if ($userLevel > 19) {
            printf ('Unknown action : %s', urlencode($_REQUEST['action']));
            exit;
          }
      }
    } else {
      switch($_REQUEST['action']) {
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
          if (sizeof($_POST)) {
            annualConfirmationList($_POST);
          } else {
            showMyEntities();
          }
          break;
        case 'EntityStatistics' :
          $menuActive = 'EntityStatistics';
          $html->showHeaders('Entity Statistics');
          showMenu();
          $display->showEntityStatistics();
          break;
        case 'EcsStatistics' :
          $menuActive = 'EcsStatistics';
          $html->showHeaders('EntityCategorySupport status');
          showMenu();
          $display->showEcsStatistics();
          break;
        case 'RAFStatistics' :
          $menuActive = 'RAFStatistics';
          $html->showHeaders('RAF status');
          showMenu();
          $display->showRAFStatistics();
          break;
        case 'showURL' :
          $menuActive = '';
          $html->showHeaders('URL status');
          showMenu();
          if (isset($_REQUEST['URL'])) {
            if (isset($_POST['recheck'])) {
              $common = new \metadata\Common();
              $common->revalidateURL($_REQUEST['URL'], isset($_REQUEST['verbose']));
            }
            $display->showURLStatus($_REQUEST['URL']);
          }
          break;
        case 'URLlist' :
          if ($userLevel > 4) {
            $menuActive = 'URLlist';
            $html->showHeaders('URL status');
            showMenu();
            if (isset($_REQUEST['URL'])) {
              if (isset($_POST['recheck'])) {
                $common = new \metadata\Common();
                $common->revalidateURL($_REQUEST['URL'], isset($_REQUEST['verbose']));
              }
              $display->showURLStatus($_REQUEST['URL']);
            } else {
              $display->showURLStatus();
            }
          }
          break;
        case 'ErrorList' :
          $menuActive = 'Errors';
          $html->showHeaders('Error status');
          showMenu();
          $display->showErrorList();
          $html->addTableSort('error-table');
          $html->addTableSort('reminder-table');
          $html->addTableSort('reminder-table-actOn');
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
            $html->showHeaders('Clean Pending');
            showMenu();
            $display->showPendingList();
          }
          break;
        case 'ShowDiff' :
          $menuActive = 'CleanPending';
          $html->showHeaders('Clean Pending');
          showMenu();
          if (isset($_REQUEST['entity_id1']) && isset($_REQUEST['entity_id2'])) {
            $display->showXMLDiff($_REQUEST['entity_id1'], $_REQUEST['entity_id2']);
          }
          $display->showPendingList();
          break;
        case 'OrganizationsInfo' :
          $menuActive = 'OrganizationsInfo';
          $html->showHeaders('Show EntityInfo');
          showMenu();
          $display->showOrganizationLists();
          $html->addTableSort('Organizationsv-table');
          $html->addTableSort('Organizationen-table');
          break;
        case 'Members' :
          $menuActive = 'Members';
          $html->showHeaders('Show Member Information');
          showMenu();
          if (isset($_REQUEST['subAction']) && isset($_REQUEST['id']) && $userLevel > 10) {
            $imps = new \metadata\IMPS();
            switch ($_REQUEST['subAction']) {
              case 'editImps' :
                $imps->editIMPS($_REQUEST['id']);
                break;
              case 'saveImps' :
                if ($imps->saveImps($_REQUEST['id'])) {
                  $display->showMembers($userLevel);
                  $html->addTableSort('scope-table');
                } else {
                  $imps->editIMPS($_REQUEST['id']);
                }
                break;
              case 'removeImps' :
                if ($imps->removeImps($_REQUEST['id'])) {
                  $display->showMembers($userLevel);
                  $html->addTableSort('scope-table');
                }
                break;
              case 'editOrganization' :
                $imps->editOrganization($_REQUEST['id']);
                break;
              case 'saveOrganization' :
                if ($imps->saveOrganization($_REQUEST['id'])) {
                  $display->showMembers($userLevel);
                  $html->addTableSort('scope-table');
                } else {
                  $imps->editOrganization($_REQUEST['id']);
                }
                break;
              case 'removeOrganization':
                if ($imps->removeOrganization($_REQUEST['id'])) {
                  $display->showMembers($userLevel);
                  $html->addTableSort('scope-table');
                }
                break;
              default :
                print "Unkown action";
            }
          } else {
            $display->showMembers($userLevel);
            $html->addTableSort('scope-table');
          }
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
  global $config, $html;

  $feedOrder = 'feedDesc';
  $orgOrder = 'orgAsc';
  $entityIDOrder = 'entityIDAsc';
  $feedArrow = '';
  $orgArrow = '';
  $entityIDArrow = '';
  $warningArrow = '';
  $errorArrow = '';
  if (isset($_REQUEST['feedDesc'])) {
    $sortOrder = '`publishIn` DESC, `entityID`';
    $feedOrder = 'feedAsc';
    $feedArrow = HTML_CLASS_FA_UP;
  } elseif (isset($_REQUEST['feedAsc'])) {
    $sortOrder = '`publishIn` ASC, `entityID`';
    $feedArrow = HTML_CLASS_FA_DOWN;
  } elseif (isset($_REQUEST['orgDesc'])) {
    $sortOrder = '`OrganizationName` DESC, `entityID`';
    $orgArrow = HTML_CLASS_FA_UP;
  } elseif (isset($_REQUEST['orgAsc'])) {
    $sortOrder = '`OrganizationName` ASC, `entityID`';
    $orgOrder = 'orgDesc';
    $orgArrow = HTML_CLASS_FA_DOWN;
  } elseif (isset($_REQUEST['warnings'])) {
    $sortOrder = '`warnings` DESC, `errors` DESC, `errorsNB` DESC, `entityID`, `id`';
    $warningArrow = HTML_CLASS_FA_DOWN;
  } elseif (isset($_REQUEST['errors'])) {
    $sortOrder = '`errors` DESC, `errorsNB` DESC, `warnings` DESC, `entityID`, `id`';
    $errorArrow = HTML_CLASS_FA_DOWN;
  } elseif (isset($_REQUEST['entityIDDesc'])) {
    $sortOrder = '`entityID` DESC';
    $entityIDOrder = 'entityIDAsc';
    $entityIDArrow = HTML_CLASS_FA_UP;
  } else {
    $sortOrder = '`entityID` ASC';
    $entityIDOrder = 'entityIDDesc';
    $entityIDArrow = HTML_CLASS_FA_DOWN;
  }

  if (isset($_REQUEST['query'])) {
    $query = $_REQUEST['query'];
  } else {
     $query = '';
  }
  $filter = 'query='.urlencode($query);

  switch ($status) {
    case 1 :
      $html->showHeaders('Published');
      $action = 'pub';
      $minLevel = 0;
      $filter .= '&action=pub';
      break;
    case 2 :
      $html->showHeaders('Pending');
      $action = 'wait';
      $minLevel = 5;
      $filter .= '&action=wait';
      break;
    case 3 :
      $html->showHeaders('Drafts');
      $action = 'new';
      $minLevel = 5;
      $filter .= '&action=new';
      break;
    case 4 :
      $html->showHeaders('Deleted');
      $action = 'pub';
      $minLevel = 5;
      break;
    case 5 :
      $html->showHeaders('Pending already Published');
      $action = 'pub';
      $minLevel = 5;
      break;
    case 6 :
      $html->showHeaders('Published when added to Pending');
      $action = 'pub';
      $minLevel = 5;
      break;
    default :
      $html->showHeaders('');
  }
  showMenu();
  if ($status == 1) {
    $entities = $config->getDb()->prepare( # NOSONAR $sortOrder is secure
      "SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `data` AS OrganizationName,
        `lastUpdated`, `lastConfirmed` AS lastValidated, `warnings`, `errors`, `errorsNB`
      FROM Entities
      LEFT JOIN Organization ON Organization.entity_id = id AND element = 'OrganizationName' AND lang = 'en'
      LEFT JOIN EntityConfirmation ON EntityConfirmation.entity_id = id
      WHERE status = $status AND entityID LIKE :Query ORDER BY $sortOrder");
  } else {
    $entities = $config->getDb()->prepare( # NOSONAR $sortOrder is secure
      "SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `data` AS OrganizationName,
        `lastUpdated`, `lastValidated`, `warnings`, `errors`, `errorsNB`
      FROM Entities
      LEFT JOIN Organization ON Organization.entity_id = id AND element = 'OrganizationName' AND lang = 'en'
      WHERE status = $status AND entityID LIKE :Query ORDER BY $sortOrder");
  }
  $entities->bindValue(':Query', "%".$query."%");

  printf('
    <table class="table table-striped table-bordered">
      <tr>
        <th>IdP</th>
        <th>SP</th>
        <th><a href="?%s&%s">eduGAIN%s</a></th>
        <th>
          <form>
            <a href="?%s&%s">entityID%s</a>
            <input type="text" name="query" value="%s">
            <input type="hidden" name="action" value="%s">
            <input type="submit" value="Filter">
          </form>
        </th>
        <th><a href="?%s&%s">OrganizationName%s</a></th>
        <th>%s (UTC)</th>
        <th>%s (UTC)</th>
        <th><a href="?%s&warnings">warning%s</a> / <a href="?%s&errors">errors%s</a></th></tr>%s',
    $filter, $feedOrder, $feedArrow, $filter, $entityIDOrder, $entityIDArrow,
    htmlspecialchars($query), $action, $filter,
    $orgOrder, $orgArrow, ($status == 1) ? 'Last Updated' : 'Created' ,
    ($status == 1) ? 'Last Confirmed' : 'Last Validated',
    $filter, $warningArrow, $filter, $errorArrow, "\n");
  showList($entities, $minLevel);
}

####
# Shows Entity information
####
function showEntity($entitiesId, $showHeader = true)  {
  global $config, $html, $display, $userLevel, $menuActive, $EPPN;
  $federation = $config->getFederation();
  $entityHandler = $config->getDb()->prepare(
    'SELECT `entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `publishedId`
    FROM Entities WHERE `id` = :Id;');
  $publishArray = array();
  $publishArrayOld = array();
  $allowEdit = false;

  $entityHandler->bindParam(':Id', $entitiesId);
  $entityHandler->execute();
  if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    if (($entity['publishIn'] & 2) == 2) { $publishArray[] = $federation['displayName']; }
    if (($entity['publishIn'] & 4) == 4) { $publishArray[] = 'eduGAIN'; }
    if ($entity['status'] > 1 && $entity['status'] < 7) {
      if ($entity['publishedId'] > 0) {
        $entityHandlerOld = $config->getDb()->prepare(
          'SELECT `id`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE `id` = :Id AND `status` = 6;');
        $entityHandlerOld->bindParam(':Id', $entity['publishedId']);
        $headerCol2 = 'Old metadata - when requested publication';
      } else {
        $entityHandlerOld = $config->getDb()->prepare(
          'SELECT `id`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE `entityID` = :Id AND `status` = 1;');
        $entityHandlerOld->bindParam(':Id', $entity['entityID']);
        $headerCol2 = 'Published now';
      }
      $entityHandlerOld->execute();
      if ($entityOld = $entityHandlerOld->fetch(PDO::FETCH_ASSOC)) {
        $oldEntitiesId = $entityOld['id'];
        if (($entityOld['publishIn'] & 2) == 2) { $publishArrayOld[] = $federation['displayName']; }
        if (($entityOld['publishIn'] & 4) == 4) { $publishArrayOld[] = 'eduGAIN'; }
      } else {
        $oldEntitiesId = 0;
      }
      switch ($entity['status']) {
        case 3 :
          # Draft
          $headerCol1 = 'New metadata';
          $menuActive = 'new';
          $allowEdit = checkAccess($entitiesId, $EPPN, $userLevel, 10, false);
          break;
        case 4 :
          # Soft Delete
          $headerCol1 = 'Deleted metadata';
          $menuActive = 'publ';
          $allowEdit = false;
          $oldEntitiesId = 0;
          break;
        case 5 :
          # Pending that have been published
          $headerCol1 = 'Already published metadata (might not be the latest!)';
          $menuActive = 'publ';
          $allowEdit = false;
          break;
        case 6 :
          # Copy of published used to compare Pending
          $headerCol1 = 'Shadow metadata (might not be the latest!)';
          $menuActive = 'publ';
          $allowEdit = false;
          break;
        default :
          $headerCol1 = 'Waiting for publishing';
          $menuActive = 'wait';
          $allowEdit = checkAccess($entitiesId, false, $userLevel, 10, false);
      }
    } else {
      $headerCol1 = 'Published metadata';
      $menuActive = 'publ';
      $oldEntitiesId = 0;
    }
    if ($showHeader) {
      $html->showHeaders($entity['entityID']);
      showMenu();
    }?>
    <div class="row">
      <div class="col">
        <h3>entityID = <?=htmlspecialchars($entity['entityID'])?></h3>
      </div>
    </div><?php
    $entityError = $display->showStatusbar($entitiesId, $userLevel > 4 ? true : false);
    print "\n" . '    <div class="row">';
    switch ($entity['status']) {
      case 1:
        $metadata = new \metadata\Metadata($entitiesId);
        $metadata->getUserId($EPPN);
        if ($metadata->isResponsible()) {
          printf('%s      <a href=".?action=Annual+Confirmation&Entity=%d">
        <button type="button" class="btn btn-outline-%s">Annual Confirmation</button></a>',
          "\n", $entitiesId, getErrors($entitiesId) == '' ? 'success' : 'secondary');
          printf('%s      <form action="." method="POST"><input type="hidden" name="action" value="createDraft"><input type="hidden" name="Entity" value="%d">
        <button type="submit" class="btn btn-outline-primary">Create Draft</button></form>', "\n", $entitiesId);
          if ($entityError['saml1Error']) {
            printf('%s      <form action="." method="POST"><input type="hidden" name="action" value="draftRemoveSaml1"><input type="hidden" name="Entity" value="%d">
        <button type="submit" class="btn btn-outline-danger">Remove SAML1 support</button></form>',
              "\n", $entitiesId);
          }
          if ($entityError['algorithmError']) {
            printf('%s      <form action="." method="POST"><input type="hidden" name="action" value="draftRemoveObsoleteAlgorithms"><input type="hidden" name="Entity" value="%d">
        <button type="submit" class="btn btn-outline-danger">Remove Obsolete Algorithms</button></form>',
              "\n", $entitiesId);
          }
          printf('%s      <a href=".?action=Request+removal&Entity=%d">
        <button type="button" class="btn btn-outline-danger">Request removal</button></a>', "\n", $entitiesId);
        } else {
          printf('%s      <a href=".?action=Request+Access&Entity=%d">
        <button type="button" class="btn btn-outline-primary">Request admin access</button></a>', "\n", $entitiesId);
        }
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
          if ($entityError['saml1Error']) {
            printf('%s      <form action="." method="POST"><input type="hidden" name="action" value="removeSaml1"><input type="hidden" name="Entity" value="%d">
        <button type="submit" class="btn btn-outline-danger">Remove SAML1 support</button></form>',
              "\n", $entitiesId);
          }
          if ($entityError['algorithmError']) {
            printf('%s      <form action="." method="POST"><input type="hidden" name="action" value="removeObsoleteAlgorithms"><input type="hidden" name="Entity" value="%d">
        <button type="submit" class="btn btn-outline-danger">Remove Obsolete Algorithms</button></form>',
              "\n", $entitiesId);
          }
          if ($oldEntitiesId > 0) {
            printf('%s      <form action="." method="POST"><input type="hidden" name="mergeEntity" value="%d"><input type="hidden" name="oldEntity" value="%d">
        <button type="submit" class="btn btn-outline-primary">Merge from published</button></form>',
              "\n", $entitiesId, $oldEntitiesId);
          }
          printf ('%s      <form action="." method="POST">
        <input type="hidden" name="mergeEntity" value="%d">
        Merge from other entity : %s        <select name="oldEntity">%s', "\n", $entitiesId, "\n", "\n");
          print '          <option value="0">Please select an entity to merge from</option>';
          if ($entity['isIdP'] ) {
            if ($entity['isSP'] ) {
              // is both SP and IdP
              $mergeEntityHandler = $config->getDb()->prepare(
                'SELECT id, entityID FROM Entities WHERE status = 1 ORDER BY entityID;');
            } else {
              // isIdP only
              $mergeEntityHandler = $config->getDb()->prepare(
                'SELECT id, entityID FROM Entities WHERE status = 1 AND isIdP = 1 ORDER BY entityID;');
            }
          } else {
            // isSP only
            $mergeEntityHandler = $config->getDb()->prepare(
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
    print !empty($publishArray) ? implode (', ', $publishArray) : '<em>no federation yet</em>.';
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
    $display->showOrganizationInfo($entitiesId, $allowEdit, $userLevel > 19, $entityError['organizationErrors']);
    if ($entity['isIdP'] && $entity['status'] == 1 && $config->getIMPS()) { $display->showIMPS($entitiesId, $userLevel > 19, $entityError['IMPSError']); }
    $display->showEntityAttributes($entitiesId, $oldEntitiesId, $allowEdit);
    $able2beRemoveSSO = ($entity['isIdP'] && $entity['isSP'] && $allowEdit);
    if ($entity['isIdP'] ) { $display->showIdP($entitiesId, $oldEntitiesId, $allowEdit, $able2beRemoveSSO); }
    if ($entity['isSP'] ) { $display->showSp($entitiesId, $oldEntitiesId, $allowEdit, $able2beRemoveSSO); }
    if ($entity['isAA'] ) { $display->showAA($entitiesId, $oldEntitiesId, $allowEdit, $allowEdit); }
    $display->showOrganization($entitiesId, $oldEntitiesId, $allowEdit);
    $display->showContacts($entitiesId, $oldEntitiesId, $allowEdit);
    if ($entity['status'] == 1 && $federation['mdqBaseURL']) { $display->showMdqUrl($entity['entityID']); }
    $display->showXML($entitiesId);
    if ($oldEntitiesId > 0 && $userLevel > 10) {
      $display->showDiff($entitiesId, $oldEntitiesId);
    }
    $display->showEditors($entitiesId);
  } else {
    $html->showHeaders('NotFound');
    print HTML_TEXT_CFE;
  }
}

####
# Shows a list of entities
####
function showList($entities, $minLevel) {
  global $EPPN, $userLevel;

  $entities->execute();
  while ($row = $entities->fetch(PDO::FETCH_ASSOC)) {
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
        case 2 :
        case 3 :
          $export2Edugain = '';
          break;
        case 6 :
        case 7 :
          $export2Edugain = 'X';
          break;
        default :
          $export2Edugain = '';
      }
      $validationStatus = ($row['warnings'] == '') ? '' : '<i class="fas fa-exclamation-triangle"></i>';
      $validationStatus .= ($row['errors'] == '' && $row['errorsNB'] == '') ? '' : '<i class="fas fa-exclamation"></i>';
      printf ('
        <td class="text-center">%s</td>
        <td><a href="?showEntity=%s">%s</a></td>
        <td>%s</td>
        <td>%s</td>
        <td>%s</td>
        <td>%s</td>',
        $export2Edugain, $row['id'], htmlspecialchars($row['entityID']), htmlspecialchars($row['OrganizationName']),
        $row['lastUpdated'], $row['lastValidated'], $validationStatus);
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
  global $config, $html, $EPPN, $userLevel;

  $html->showHeaders('Annual Check');
  showMenu();
  if ($userLevel > 9) {
    printf ('    <div class="row">%s      <div class="col">%s', "\n", "\n");
    printf ('        <a href=".?action=myEntities&showMy">
          <button type="button" class="btn btn%s-success">Show My</button>
        </a>%s',
      isset($_REQUEST['showMy']) ? '' : HTML_OUTLINE, "\n");
    printf ('        <a href=".?action=myEntities&showPub">
          <button type="button" class="btn btn%s-success">Show Published</button>
        </a>%s',
      isset($_REQUEST['showPub']) ? '' : HTML_OUTLINE, "\n");
    printf ('      </div>%s    </div>%s', "\n", "\n");
    }
  if (isset($_REQUEST['showPub']) && $userLevel > 9) {
    $entitiesHandler = $config->getDb()->prepare("SELECT Entities.`id`, `entityID`, `errors`, `errorsNB`, `warnings`, `status`
      FROM Entities WHERE `status` = 1 AND publishIn > 1 ORDER BY `entityID`");
  } else {
    $entitiesHandler = $config->getDb()->prepare("SELECT Entities.`id`, `entityID`, `errors`, `errorsNB`, `warnings`, `status`
      FROM Users, EntityUser, Entities
      WHERE EntityUser.`entity_id` = Entities.`id`
        AND EntityUser.`user_id` = Users.`id`
        AND `status` < 4
        AND `userID` = :UserID
      ORDER BY `entityID`, `status`");
    $entitiesHandler->bindValue(':UserID', $EPPN);
  }
  $entityConfirmationHandler = $config->getDb()->prepare(
    "SELECT `lastConfirmed`, `fullName`, `email`, NOW() - INTERVAL 10 MONTH AS `warnDate`,
      NOW() - INTERVAL 12 MONTH AS 'errorDate'
    FROM Users, EntityConfirmation
    WHERE `user_id`= `id` AND `entity_id` = :Id");

  printf ('
    <form action="?action=myEntities" method="POST" enctype="multipart/form-data">
      <table id="annual-table" class="table table-striped table-bordered">
        <thead><tr>
          <th>entityID</th><th></th><th>Metadata status</th><th>Last confirmed(UTC)</th><th>By</th>
        </tr></thead>%s', "\n");
      $entitiesHandler->execute();
  while ($row = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    $entityConfirmationHandler->bindParam(':Id', $row['id']);
    $entityConfirmationHandler->execute();
    if ($entityConfirmation = $entityConfirmationHandler->fetch(PDO::FETCH_ASSOC)) {
      $lastConfirmed = $entityConfirmation['lastConfirmed'];
      $updater = htmlspecialchars($entityConfirmation['fullName']) . ' (' . htmlspecialchars($entityConfirmation['email']) . ')';
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
        $checkBox = $row['errors'] == '' && $row['errorsNB'] == '' && $row['warnings'] == ''
          ? sprintf('<input type="checkbox" id="validate_%d" name="validate_%d">', $row['id'], $row['id'])
          : '';
        break;
      case 2 :
        $pubStatus = 'Pending';
        $lastConfirmed = '';
        $confirmStatus = '';
        $updater = '';
        $checkBox = '';
        break;
      case 3 :
        $pubStatus = 'Draft';
        $lastConfirmed = '';
        $confirmStatus = '';
        $updater = '';
        $checkBox = '';
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
    printf('        <tr><td><a href="?showEntity=%d">%s</a></td><td>%s</td><td>%s (%s)</td><td>%s%s</td><td>%s</td></tr>%s',
      $row['id'], htmlspecialchars($row['entityID']), $checkBox, $errorStatus, $pubStatus, $lastConfirmed, $confirmStatus, $updater, "\n");
  }
  printf('      </table>
      <button type="submit" class="btn btn-primary">Validate selected</button>
    </form>%s', "\n");
  $html->addTableSort('annual-table');
}

####
# Shows form for upload of new XML
####
function showUpload() {
  global $html;
  $html->showHeaders('Add new XML');
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

  $import = new \metadata\NormalizeXML();
  $import->fromFile($_FILES['XMLfile']['tmp_name']);
  if ($import->getStatus()) {
    $entityID = $import->getEntityID();
    $validate = new \metadata\ValidateXMLSchema($import->getXML());
    if ($validate->validateSchema('../../schemas/schema.xsd')) {
      $metadata = new \metadata\Metadata($entityID, 'New');
      $metadata->importXML($import->cleanOutRegistrationInfo($import->getXML()));
      $metadata->getUser($EPPN, $mail, $fullName, true);
      $metadata->updateResponsible($EPPN);
      validateEntity($metadata->id());
      $prodmetadata = new \metadata\Metadata($entityID, 'Prod');
      if ($prodmetadata->entityExists()) {
        $mergeMetadata = new \metadata\MetadataMerge($metadata->id(), $prodmetadata->id());
        $mergeMetadata->mergeRegistrationInfo();
        $mergeMetadata->saveResults();
      }
      showEntity($metadata->id());
    } else {
      $html->showHeaders('Problem');
      printf('%s    <div class="row alert alert-danger" role="alert">
      <div class="col">
        <b>Error in XML-syntax:</b>
        %s
      </div>%s    </div>%s', "\n", $validate->getError(), "\n", "\n");
      $html->showFooter(array());
    }
  } else {
    $html->showHeaders('Problem');
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
  $metadata = new \metadata\MetadataEdit($entitiesId);
  $metadata->removeSSO($type);
  validateEntity($entitiesId);
  showEntity($entitiesId);
}

####
# Shows menu row
####
function showMenu() {
  global $userLevel, $menuActive, $config;
  $filter='';
  if (isset($_REQUEST['query'])) {
    $filter='&query='.urlencode($_REQUEST['query']);
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
  printf('<a href=".?action=OrganizationsInfo%s"><button type="button" class="btn btn%s-primary">Organizations</button></a>',
    $filter, $menuActive == 'OrganizationsInfo' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=EntityStatistics%s"><button type="button" class="btn btn%s-primary">Entity Statistics</button></a>',
    $filter, $menuActive == 'EntityStatistics' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=EcsStatistics%s"><button type="button" class="btn btn%s-primary">ECS statistics</button></a>',
    $filter, $menuActive == 'EcsStatistics' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=RAFStatistics%s"><button type="button" class="btn btn%s-primary">RAF statistics</button></a>',
    $filter, $menuActive == 'RAFStatistics' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=Members%s"><button type="button" class="btn btn%s-primary">Members</button></a>',
    $filter, $menuActive == 'Members' ? '' : HTML_OUTLINE);
  printf('<a href=".?action=ErrorList%s"><button type="button" class="btn btn%s-primary">Errors</button></a>',
    $filter, $menuActive == 'Errors' ? '' : HTML_OUTLINE);
  if ( $userLevel > 4 ) {
    printf('<a href=".?action=URLlist%s"><button type="button" class="btn btn%s-primary">URLlist</button></a>',
      $filter, $menuActive == 'URLlist' ? '' : HTML_OUTLINE);
    if ($config->getFederation()['mdsDbPath']) {
      printf('<a href="./mds.php" target="_blank"><button type="button" class="btn btn%s-primary">MDS</button></a>',
        HTML_OUTLINE);
    }
  }
  if ( $userLevel > 10 ) {
    printf('<a href=".?action=CleanPending%s"><button type="button" class="btn btn%s-primary">Clean Pending</button></a>',
      $filter, $menuActive == 'CleanPending' ? '' : HTML_OUTLINE);
  }
  print "\n    <br>\n    <br>\n";
}

function validateEntity($entitiesId) {
  global $config;
  $xmlParser = class_exists(CLASS_PARSER.$config->getFederation()['extend']) ?
    CLASS_PARSER.$config->getFederation()['extend'] :
    CLASS_PARSER;
  $samlValidator = class_exists(CLASS_VALIDATOR.$config->getFederation()['extend']) ?
    CLASS_VALIDATOR.$config->getFederation()['extend'] :
    CLASS_VALIDATOR;
  $parser = new $xmlParser($entitiesId);
  $parser->clearResult();
  $parser->clearWarning();
  $parser->clearError();
  $parser->parseXML();
  $validator = new $samlValidator($entitiesId);
  $validator->saml();
  $validator->validateURLs();
}

function move2Pending($entitiesId) {
  global $html, $menuActive;
  global $EPPN, $mail, $fullName;
  global $mailContacts, $mailRequester, $config;
  $federation = $config->getFederation();

  $draftMetadata = new \metadata\Metadata($entitiesId);

  if ($draftMetadata->entityExists()) {
    validateEntity($draftMetadata->id());
    if ( $draftMetadata->isIdP() && $draftMetadata->isSP()) {
      $sections = $federation['rulesSectsBoth'];
      $infoText = $federation['rulesInfoBoth'];
    } elseif ($draftMetadata->isIdP()) {
      $sections = $federation['rulesSectsIdP'];
      $infoText = $federation['rulesInfoIdP'];
    } elseif ($draftMetadata->isSP()) {
      $sections = $federation['rulesSectsSP'];
      $infoText = $federation['rulesInfoSP'];
    }
    $html->showHeaders($draftMetadata->entityID());
    $errors = getBlockingErrors($entitiesId);
    if ($errors == '') {
      if (isset($_REQUEST['publishedIn'])) {
        $publish = true;
        if ($_REQUEST['publishedIn'] < 1) {
          $errors .= "Missing where to publish Metadata.\n";
          $publish = false;
        }
        if (!isset($_POST['OrganisationOK'])) {
          $errors .= sprintf(HTML_TEXT_YMFS,
            $sections, $federation['rulesName'], "\n");
          $publish = false;
        }
      } else {
        $publish = false;
      }

      if ($publish) {
        $menuActive = 'wait';
        showMenu();

        setupMail();

        $shortEntityid = preg_replace(REGEXP_ENTITYID, '$1', $draftMetadata->entityID());
        $publishedMetadata = new \metadata\Metadata($draftMetadata->entityID(), 'prod');

        if ($publishedMetadata->entityExists()) {
          $mailContacts->Subject  = 'Info : Updated ' . $federation['displayName'] . HTML_TEXT_MEDFOR . $shortEntityid;
          $mailRequester->Subject = 'Updated ' . $federation['displayName'] . HTML_TEXT_MEDFOR . $shortEntityid;
          $shadowMetadata = new \metadata\Metadata($draftMetadata->entityID(), 'Shadow');
          $shadowMetadata->importXML($publishedMetadata->xml());
          $shadowMetadata->updateFeedByValue($publishedMetadata->feedValue());
          $oldEntitiesId = $shadowMetadata->id();

          # copy over also ServiceInfo
          $serviceURL = '';
          $enabled = 0;
          $publishedMetadata->getServiceInfo($publishedMetadata->id(), $serviceURL, $enabled);
          if ($serviceURL) {
            $shadowMetadata->storeServiceInfo($oldEntitiesId, $serviceURL, $enabled);
          }

          validateEntity($oldEntitiesId);
        } else {
          $mailContacts->Subject  = 'Info : New ' . $federation['displayName'] . HTML_TEXT_MEDFOR . $shortEntityid;
          $mailRequester->Subject = 'New ' . $federation['displayName'] . HTML_TEXT_MEDFOR . $shortEntityid;
          $oldEntitiesId = 0;
        }

        if ($config->getMode() == 'QA') {
          print "    <p>Your entity will be published within 15 to 30 minutes</p>\n";
          printf ('    <hr>
          <a href=".?showEntity=%d"><button type="button" class="btn btn-primary">Back to entity</button></a>',
            $entitiesId);
        } else {
          $displayName = $draftMetadata->entityDisplayName();

          $addresses = $draftMetadata->getTechnicalAndAdministrativeContacts();
          if ($config->sendOut()) {
            $mailRequester->addAddress($mail);
            foreach ($addresses as $address) {
              $mailContacts->addAddress($address);
            }
          }

          $hostURL = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'];

          //Content
          $mailContacts->isHTML(true);
          $mailContacts->Body = sprintf("<!DOCTYPE html>
          <html lang=\"en\">
            <head>
              <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
            </head>
            <body>
              <p>Hi.</p>
              <p>%s (%s) has requested an update of \"%s\" (%s)</p>
              <p>You have received this email because you are either
              the new or old technical and/or administrative contact.</p>
              <p>You can see the new version at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>
              <p>If you do not approve this update, forward this email to %s (%s)
              and request for the update to be denied.</p>
              <p>This is a message from the %s.<br>
              --<br>
              On behalf of %s</p>
            </body>
          </html>",
            htmlspecialchars($fullName), htmlspecialchars($mail), htmlspecialchars($displayName), htmlspecialchars($draftMetadata->entityID()), $hostURL, $entitiesId, $hostURL, $entitiesId,
            $federation['teamName'], $federation['teamMail'], $federation['toolName'], $federation['teamName']);
          $mailContacts->AltBody = sprintf("Hi.
          \n%s (%s) has requested an update of \"%s\" (%s)
          \nYou have received this email because you are either the new or old technical and/or administrative contact.
          \nYou can see the new version at %s/?showEntity=%d
          \nIf you do not approve this update, forward this email to %s (%s) and request for the update to be denied.
          \nThis is a message from the %s.
          --
          On behalf of %s",
            $fullName, $mail, $displayName, $draftMetadata->entityID(), $hostURL, $entitiesId,
            $federation['teamName'], $federation['teamMail'], $federation['toolName'], $federation['teamName']);

          $mailRequester->isHTML(true);
          $mailRequester->Body = sprintf("<!DOCTYPE html>
          <html lang=\"en\">
            <head>
              <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
            </head>
            <body>
              <p>Hi.</p>
              <p>You have requested an update of \"%s\" (%s)</p>
              <p>To continue the publication request, forward this email to %s (%s).
              If you don’t do this the publication request will not be processed.</p>
              <p>The new version can be found at <a href=\"%s/admin/?showEntity=%d\">%s/admin/?showEntity=%d</a></p>
              <p>An email has also been sent to the following addresses since they are the new or old technical
              and/or administrative contacts : </p>
              <p><ul>
              <li>%s</li>
              </ul>
              <p>This is a message from the %s.<br>
              --<br>
              On behalf of %s</p>
            </body>
          </html>",
            htmlspecialchars($displayName), htmlspecialchars($draftMetadata->entityID()), $federation['teamName'], $federation['teamMail'], $hostURL, $entitiesId,
            $hostURL, $entitiesId,implode ("</li>\n<li>",$addresses),
            $federation['toolName'], $federation['teamName']);
          $mailRequester->AltBody = sprintf("Hi.
          \nYou have requested an update of \"%s\" (%s)
          \nTo continue the publication request, forward this email to %s (%s).
          If you don’t do this the publication request will not be processed.
          \nThe new version can be found at %s/admin/?showEntity=%d
          \nAn email has also been sent to the following addresses since they are the new or old technical and/or administrative contacts : %s
          \nThis is a message from the %s.
          --
          On behalf of %s",
            $displayName, $draftMetadata->entityID(), $federation['teamName'], $federation['teamMail'],
            $hostURL, $entitiesId, implode (", ",$addresses),
            $federation['toolName'], $federation['teamName']);

          try {
            $mailContacts->send();
          } catch (Exception $e) {
            echo HTML_TEXT_MCNBS;
            echo HTML_TEXT_ME . $mailContacts->ErrorInfo . '<br>';
          }

          try {
            $mailRequester->send();
          } catch (Exception $e) {
            echo 'Message could not be sent to requester.<br>';
            echo HTML_TEXT_ME . $mailRequester->ErrorInfo . '<br>';
          }

          printf ("    <p>You should have got an email with information on how to proceed</p>
          <p>Information has also been sent to the following new or old technical and/or administrative contacts:</p>
          <ul>
            <li>%s</li>\n          </ul>\n", implode ("</li>\n            <li>",$addresses));
          printf ('    <hr>
            <a href=".?showEntity=%d"><button type="button" class="btn btn-primary">Back to entity</button></a>',
            $entitiesId);
        }
        $draftMetadata->updateFeedByValue($_REQUEST['publishedIn']);
        $draftMetadata->moveDraftToPending($oldEntitiesId);
        $draftMetadata->getUser($EPPN, true);
        $draftMetadata->updateResponsible($EPPN);
      } else {
        $menuActive = 'new';
        showMenu();
        if ($errors != '') {
          printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">
        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>',
           "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
        }
        printf('%s    <p>You are about to request publication of <b>%s</b></p>', "\n", htmlspecialchars($draftMetadata->entityID()));
        $publishedMetadata = new \metadata\Metadata($draftMetadata->entityID(), 'prod');

        $publishArrayOld = array();
        if ($publishedMetadata->entityExists()) {
          $oldPublishedValue = $publishedMetadata->feedValue();
          if (($oldPublishedValue & 2) == 2) { $publishArrayOld[] = $federation['displayName']; }
          if (($oldPublishedValue & 4) == 4) { $publishArrayOld[] = 'eduGAIN'; }
          printf('%s    <p>Currently published in <b>%s</b></p>', "\n", implode (' and ', $publishArrayOld));
        } else {
          $oldPublishedValue = $draftMetadata->isIdP() ? 7 : 3;
        }
        printf('    <h5>The entity should be published in:</h5>
    <form action="." method="POST">
      <input type="hidden" name="move2Pending" value="%d">%s',
          $entitiesId,"\n");
        if ($config->getMode() == 'QA') {
          printf('      <input type="radio" id="SWAMID" name="publishedIn" value="2" checked>
      <label for="SWAMID">' . $federation['displayName'] . ' ' . $config->getMode() . '</label>%s',"\n");
        } else {
          printf('      <input type="radio" id="SWAMID_eduGAIN" name="publishedIn" value="7"%s>
      <label for="SWAMID_eduGAIN">' . $federation['displayName'] . ' and eduGAIN</label><br>
      <input type="radio" id="SWAMID" name="publishedIn" value="3"%s>
      <label for="SWAMID">' . $federation['displayName'] . '</label>%s',
          ($oldPublishedValue & 4) == 4 ? HTML_CHECKED : '',
          ($oldPublishedValue & 6) == 2 ? HTML_CHECKED : '', "\n");
        }
        printf('      <br>
      <h5> Confirmation:</h5>
      <p>Registration criteria from %s:
        %s
      </p>
      <input type="checkbox" id="OrganisationOK" name="OrganisationOK">
      <label for="OrganisationOK">I confirm that this Entity fulfils sections <b>%s</b> in
        <a href="%s" target="_blank">
          %s
        </a>
      </label><br>
      <br>
      <input type="submit" name="action" value="Request publication">
    </form>
    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>',
          $federation['rulesName'],
          $infoText, $sections,
          $federation['rulesURL'], $federation['rulesName'],
          $entitiesId);
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
    $html->showHeaders('NotFound');
    $menuActive = 'new';
    showMenu();
    print HTML_TEXT_CFE;
  }
  print "\n";
}

function annualConfirmation($entitiesId){
  global $html, $menuActive;
  global $EPPN, $mail, $fullName, $userLevel;
  global $config;
  $federation = $config->getFederation();

  $metadata = new \metadata\Metadata($entitiesId);
  if ($metadata->status() == 1) {
    $confirm = false;
    # Entity is Published
    $errors = getErrors($entitiesId);
    $user_id = $metadata->getUserId($EPPN);
    if (isset ($_POST['user_id']) && ($user_id <> $_POST['user_id'] || $userLevel > 19) && $metadata->isResponsible()) {
      $metadata->removeAccessFromEntity($_POST['user_id']);
    }
    if ($errors == '') {
      if ($metadata->isResponsible()) {
        # User have access to entity
        if ( $metadata->isIdP() && $metadata->isSP()) {
          $sections = $federation['rulesSectsBoth'];
          $infoText = $federation['rulesInfoBoth'];
        } elseif ($metadata->isIdP()) {
          $sections = $federation['rulesSectsIdP'];
          $infoText = $federation['rulesInfoIdP'];
        } elseif ($metadata->isSP()) {
          $sections = $federation['rulesSectsSP'];
          $infoText = $federation['rulesInfoSP'];
        }

        if (isset($_POST['entityIsOK'])) {
          $metadata->updateUser($EPPN, $mail, $fullName, true);
          $confirm = true;
        } else {
          $errors .= isset($_POST['FormVisit'])
            ? sprintf(HTML_TEXT_YMFS, $sections, $federation['rulesName'], "\n")
            : '';
        }

        if ($confirm) {
          $metadata->confirmEntity($user_id);
          $menuActive = 'myEntities';
          showMyEntities();
        } else {
          $html->showHeaders($metadata->entityID());
          $menuActive = '';
          showMenu();
          if ($errors != '') {
            printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
          }
          printf(
            '%s    <p>You are confirming that <b>%s</b> is operational and fulfils %s</p>%s',
            "\n", $metadata->entityID(), $federation['rulesName'], "\n");
          printf('    <form action="." method="post">
      <input type="hidden" name="Entity" value="%d">
      <input type="hidden" name="FormVisit" value="true">
      <h5> Confirmation:</h5>
      <p>Registration criteria from %s:
        %s
      </p>
      <input type="checkbox" id="entityIsOK" name="entityIsOK">
      <label for="entityIsOK">I confirm that this Entity fulfils sections <b>%s</b> in <a href="%s" target="_blank">%s</a></label><br>
      <br>
      <input type="submit" name="action" value="Annual Confirmation">
    </form>
    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>%s',
            $entitiesId, $federation['rulesName'], $infoText, $sections,
            $federation['rulesURL'], $federation['rulesName'], $entitiesId, "\n");
        }
      } else {
        # User have no access yet.
        requestAccess($entitiesId);
      }
    } else {
      $html->showHeaders($metadata->entityID());
      printf('%s    <div class="row alert alert-danger" role="alert">
      <div class="col">
        <b>Please fix the following errors before confirming:</b><br>
        %s
      </div>
    </div>
    <a href=".?showEntity=%d"><button type="button" class="btn btn-outline-primary">Return to Entity</button></a>%s',
        "\n", str_ireplace("\n", "<br>", $errors), $entitiesId, "\n");
    }
    if (! $confirm) {
      printf('    <br><br><h5>The following have admin-access to this entity</h5><ul>%s', "\n");
      foreach ($metadata->getResponsibles() as $user) {
        $delete = ($user_id == $user['id'] && $userLevel < 20) ? '' :
          sprintf('<form action="." method="POST" name="removeEditor%d" style="display: inline;"><input type="hidden" name="action" value="Annual Confirmation"><input type="hidden" name="Entity" value="%d"><input type="hidden" name="user_id" value="%d">    <a href="#" onClick="document.forms.removeEditor%s.submit();"><i class="fas fa-trash"></i></a></form>',
            $user['id'], $entitiesId, $user['id'], $user['id'], $user['id']);
        printf ('      <li>%s%s (%s)</li>%s', $delete, htmlspecialchars($user['fullName']), htmlspecialchars($user['userID']), "\n");
      }
    }
    printf('    </ul>%s', "\n");

  } else {
    $html->showHeaders('NotFound');
    $menuActive = 'myEntities';
    showMenu();
    print HTML_TEXT_CFE;
  }
}

function annualConfirmationList($list){
  global $html, $menuActive;
  global $EPPN, $mail, $fullName;
  global $config;
  $federation = $config->getFederation();
  $isIdP = false;
  $isSP = false;
  $entityList = array();
  $sections = '' ;
  $infoText = '';
  $errors = '';

  foreach ($list as $postString => $on) {
    $postArray = explode('_', $postString);
    if ($postArray[0] == 'validate') {
      $metadata = new \metadata\Metadata($postArray[1]);
      if ($metadata->status() == 1) {
        # Entity is Published
        $metadata->getUserId($EPPN);
        print $metadata->getWarning();
        print $metadata->getError();
        if ($metadata->getWarning() == '' && $metadata->getError() == '' && $metadata->isResponsible()) {
          # User have access to entity and no warnings or error
          #check if IdP och SP and add save for later display
          $isIdP = $metadata->isIdP() ? true : $isIdP;
          $isSP = $metadata->isSP() ? true : $isSP;
          $entityList[$postArray[1]] = $metadata->entityID();
        } else {
          $errors .= sprintf('Problems with %s, skipping<br>', htmlspecialchars($metadata->entityID()));
          print "Found error";
        }
      }
    }
  }
  if (sizeof($entityList)) {
    if ( $isIdP && $isSP) {
      $sections = $federation['rulesSectsBoth'];
      $infoText = $federation['rulesInfoBoth'];
    } elseif ($isIdP) {
      $sections = $federation['rulesSectsIdP'];
      $infoText = $federation['rulesInfoIdP'];
    } elseif ($isSP) {
      $sections = $federation['rulesSectsSP'];
      $infoText = $federation['rulesInfoSP'];
    }
    if (isset($_POST['entityIsOK'])) {
      $metadata->updateUser($EPPN, $mail, $fullName, true);
      $user_id = $metadata->getUserId($EPPN);
      foreach ($entityList as $id => $entityID) {
        $metadata = new \metadata\Metadata($id);
        $metadata->confirmEntity($user_id);
      }
      $menuActive = 'myEntities';
      showMyEntities();
    } else {
      $errors .= isset($_POST['FormVisit'])
        ? sprintf(HTML_TEXT_YMFS, $sections, $federation['rulesName'], "\n")
        : '';

      $html->showHeaders('Validate list');
      $menuActive = '';
      showMenu();
      if ($errors != '') {
        printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
      }
      printf(
      '%s    <p>You are confirming that the list below is operational and fulfils %s</p>
    <form action="." method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="myEntities">
      <input type="hidden" name="FormVisit" value="true">
      <ul>', "\n", $federation['rulesName']);
      foreach ($entityList as $id => $entityID) {
        printf('      <li><input type="hidden" name="validate_%d" value="on">%s</li>%s', $id , $entityID, "\n");
      }
      printf('
      </ul>
      <h5> Confirmation:</h5>
      <p>Registration criteria from %s:
        %s
      </p>
      <input type="checkbox" id="entityIsOK" name="entityIsOK">
      <label for="entityIsOK">I confirm that this Entity fulfils sections <b>%s</b> in <a href="%s" target="_blank">%s</a></label><br>
      <br>
      <input type="submit" name="formAction" value="Annual Confirmation">
    </form>
    <a href="/admin/?action=myEntities"><button>Return to My entities</button></a>%s',
        $federation['rulesName'], $infoText, $sections,
        $federation['rulesURL'], $federation['rulesName'], "\n");
    }
  } else {
    $menuActive = 'myEntities';
    showMyEntities();
  }
}

function requestRemoval($entitiesId) {
  global $config, $html, $menuActive;
  global $EPPN, $mail, $fullName;
  global $mailContacts, $mailRequester;
  $federation = $config->getFederation();
  $metadata = new \metadata\Metadata($entitiesId);
  if ($metadata->status() == 1) {
    $userID = $metadata->getUserId($EPPN);
    if ($metadata->isResponsible()) {
      # User have access to entity
      $html->showHeaders($metadata->entityID());
      if (isset($_POST['confirmRemoval'])) {
        $menuActive = 'publ';
        showMenu();

        $removeHandler = $config->getDb()->prepare(
          'UPDATE `Entities`
          SET `removalRequestedBy` = :UserId
          WHERE `id` = :Entity_ID');
        $removeHandler->execute(array(':UserId' => $userID, ':Entity_ID' => $entitiesId));

        if ($config->getMode() == 'QA') {
          print "    <p>Your entity will be removed within 15 to 30 minutes</p>\n";
          printf ('    <hr>
          <a href=".?showEntity=%d"><button type="button" class="btn btn-primary">Back to entity</button></a>',
            $entitiesId);
        } else {
          $displayName = $metadata->entityDisplayName();

          setupMail();

          if ($config->sendOut()) {
            $mailRequester->addAddress($mail);
          }

          $addresses = array();
          $contactHandler = $config->getDb()->prepare(
            "SELECT DISTINCT emailAddress
            FROM `ContactPerson`
            WHERE entity_id = :Entity_ID AND (contactType='technical' OR contactType='administrative')");
          $contactHandler->bindParam(':Entity_ID',$entitiesId);
          $contactHandler->execute();
          while ($address = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
            if ($config->sendOut()) {
              $mailContacts->addAddress(substr($address['emailAddress'],7));
            }
            $addresses[] = substr($address['emailAddress'],7);
          }

          $hostURL = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'];

          //Content
          $mailContacts->isHTML(true);
          $mailContacts->Body = sprintf("<!DOCTYPE html>
            <html lang=\"en\">
              <head>
                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
              </head>
              <body>
                <p>Hi.</p>
                <p>%s (%s) has requested removal of the entity \"%s\" (%s) from the %s metadata.</p>
                <p>You have received this email because you are either the technical and/or administrative contact.</p>
                <p>You can see the current metadata at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>
                <p>If you do not approve request please forward this email to %s (%s)
                and request for the removal to be denied.</p>
                <p>This is a message from the %s.<br>
                --<br>
                On behalf of %s</p>
              </body>
            </html>",
            htmlspecialchars($fullName), htmlspecialchars($mail), htmlspecialchars($displayName), htmlspecialchars($metadata->entityID()), $federation['displayName'], $hostURL, $entitiesId, $hostURL, $entitiesId,
            $federation['teamName'], $federation['teamMail'], $federation['toolName'], $federation['teamName']);
          $mailContacts->AltBody = sprintf("Hi.
            \n%s (%s) has requested removal of the entity \"%s\" (%s) from the %s metadata.
            \nYou have received this email because you are either the technical and/or administrative contact.
            \nYou can see the current metadata at %s/?showEntity=%d
            \nIf you do not approve request please forward this email to %s (%s)
            and request for the removal to be denied.
            \nThis is a message from the %s.
            --
            On behalf of %s",
            $fullName, $mail, $displayName, $metadata->entityID(), $federation['displayName'], $hostURL, $entitiesId,
            $federation['teamName'], $federation['teamMail'], $federation['toolName'], $federation['teamName']);

          $mailRequester->isHTML(true);
          $mailRequester->Body   = sprintf("<!DOCTYPE html>
            <html lang=\"en\">
              <head>
                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
              </head>
              <body>
                <p>Hi.</p>
                <p>You have requested removal of the entity \"%s\" (%s) from the %s metadata.
                <p>Please forward this email to %s (%s).</p>
                <p>The current metadata can be found at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>
                <p>An email has also been sent to the following addresses since they are the technical
                and/or administrative contacts : </p>
                <p><ul>
                <li>%s</li>
                </ul>
                <p>This is a message from the %s.<br>
                --<br>
                On behalf of %s</p>
              </body>
            </html>",
            htmlspecialchars($displayName), htmlspecialchars($metadata->entityID()), $federation['displayName'],
            $federation['teamName'], $federation['teamMail'],
            $hostURL, $entitiesId,
            $hostURL, $entitiesId,implode ("</li>\n<li>",$addresses), $federation['toolName'], $federation['teamName']);
          $mailRequester->AltBody = sprintf("Hi.
            \nYou have requested removal of the entity \"%s\" (%s) from the %s metadata.
            \nPlease forward this email to %s (%s).
            \nThe current metadata can be found at %s/?showEntity=%d
            \nAn email has also been sent to the following addresses since they are the technical
            and/or administrative contacts : %s
            \nThis is a message from the %s.
            --
            On behalf of %s",
            $displayName, $metadata->entityID(), $federation['displayName'], $federation['teamName'], $federation['teamMail'],
            $hostURL, $entitiesId, implode (", ",$addresses),
            $federation['toolName'], $federation['teamName']);

          $shortEntityid = preg_replace(REGEXP_ENTITYID, '$1', $metadata->entityID());
          $mailContacts->Subject  = 'Info : Request to remove ' . $federation['displayName'] . HTML_TEXT_MEDFOR . $shortEntityid;
          $mailRequester->Subject = 'Request to remove ' . $federation['displayName'] . HTML_TEXT_MEDFOR . $shortEntityid;

          try {
            $mailContacts->send();
          } catch (Exception $e) {
            echo HTML_TEXT_MCNBS;
            echo HTML_TEXT_ME . $mailContacts->ErrorInfo . '<br>';
          }

          try {
            $mailRequester->send();
          } catch (Exception $e) {
            echo 'Message could not be sent to requester.<br>';
            echo HTML_TEXT_ME . $mailRequester->ErrorInfo . '<br>';
          }

          printf ("    <p>You should have got an email with information on how to proceed</p>\n    <p>Information has also been sent to the following technical and/or administrative contacts:</p>\n    <ul>\n      <li>%s</li>\n    </ul>\n", implode ("</li>\n    <li>",$addresses));
          printf ('    <hr>%s    <a href=".?showEntity=%d"><button type="button" class="btn btn-primary">Back to entity</button></a>',"\n",$entitiesId);
        }
      } else {
        $menuActive = 'publ';
        showMenu();
        printf('%s    <p>You are about to request removal of the entity with the entityID <b>%s</b> from the %s metadata.</p>', "\n", $metadata->entityID(), $federation['displayName']);
        if (($metadata->feedValue() & 2) == 2) { $publishArray[] = $federation['displayName']; }
        if (($metadata->feedValue() & 4) == 4) { $publishArray[] = 'eduGAIN'; }
        printf('%s    <p>Currently published in <b>%s</b></p>%s', "\n", implode (' and ', $publishArray), "\n");
        printf('    <form action="." method="POST">%s      <input type="hidden" name="Entity" value="%d">%s      <input type="checkbox" id="confirmRemoval" name="confirmRemoval">%s      <label for="confirmRemoval">I confirm that this Entity should be removed</label><br>%s      <br>%s      <input type="submit" name="action" value="Request removal">%s    </form>%s    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', "\n", $entitiesId, "\n", "\n", "\n", "\n", "\n", "\n" ,$entitiesId);
      }
    } else {
      # User have no access yet.
      requestAccess($entitiesId);
    }
  } else {
    $html->showHeaders('NotFound');
    $menuActive = 'publ';
    showMenu();
    print HTML_TEXT_CFE;
  }
  print "\n";
}

function setupMail() {
  global $config;
  global $mailContacts, $mailRequester;

  $mailContacts = new PHPMailer(true);
  $mailRequester = new PHPMailer(true);
  $mailContacts->isSMTP();
  $mailRequester->isSMTP();
  $mailContacts->CharSet = "UTF-8";
  $mailRequester->CharSet = "UTF-8";
  $mailContacts->Host = $config->getSmtp()['host'];
  $mailRequester->Host = $config->getSmtp()['host'];
  $mailContacts->Port = $config->getSmtp()['port'];
  $mailRequester->Port = $config->getSmtp()['port'];
  $mailContacts->SMTPAutoTLS = true;
  $mailRequester->SMTPAutoTLS = true;
  if ($config->smtpAuth()) {
    $mailContacts->SMTPAuth = true;
    $mailRequester->SMTPAuth = true;
    $mailContacts->Username = $config->getSmtp()['sasl']['user'];
    $mailRequester->Username = $config->getSmtp()['sasl']['user'];
    $mailContacts->Password = $config->getSmtp()['sasl']['password'];
    $mailRequester->Password = $config->getSmtp()['sasl']['password'];
    $mailContacts->SMTPSecure = 'tls';
    $mailRequester->SMTPSecure = 'tls';
  }

  //Recipients
  $mailContacts->setFrom($config->getSmtp()['from'], $config->getSmtp()['fromName']);
  $mailRequester->setFrom($config->getSmtp()['from'], $config->getSmtp()['fromName']);
  if ($config->getSMTP()['bcc']) {
    $mailContacts->addBCC($config->getSMTP()['bcc']);
    $mailRequester->addBCC($config->getSMTP()['bcc']);
  }
  $mailContacts->addReplyTo($config->getSMTP()['replyTo'], $config->getSMTP()['replyName']);
  $mailRequester->addReplyTo($config->getSMTP()['replyTo'], $config->getSMTP()['replyName']);
}

function move2Draft($entitiesId) {
  global $config, $html, $menuActive;
  $entityHandler = $config->getDb()->prepare('SELECT `entityID`, `xml` FROM Entities WHERE `status` = 2 AND `id` = :Id;');
  $entityHandler->bindParam(':Id', $entitiesId);
  $entityHandler->execute();
  if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    if (isset($_POST['action'])) {
      $draftMetadata = new \metadata\Metadata($entity['entityID'], 'New');
      $draftMetadata->importXML($entity['xml']);

      # copy over also ServiceInfo
      $serviceURL = '';
      $enabled = 0;
      $draftMetadata->getServiceInfo($entitiesId, $serviceURL, $enabled);
      if ($serviceURL) {
        $draftMetadata->storeServiceInfo($draftMetadata->id(), $serviceURL, $enabled);
      }

      validateEntity($draftMetadata->id());
      $menuActive = 'new';
      $draftMetadata->copyResponsible($entitiesId);
      showEntity($draftMetadata->id());
      $oldMetadata = new \metadata\Metadata($entitiesId);
      $oldMetadata->removeEntity();
    } else {
      $html->showHeaders($entity['entityID']);
      $menuActive = 'wait';
      showMenu();
      printf('%s    <p>You are about to cancel your request for publication of <b>%s</b></p>', "\n", htmlspecialchars($entity['entityID']));
      printf('    <form action="." method="POST">
      <input type="hidden" name="move2Draft" value="%d">
      <input type="submit" name="action" value="Confirm cancel publication request">
    </form>
    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', $entitiesId, $entitiesId);
    }
  } else {
    $html->showHeaders('NotFound');
    $menuActive = 'wait';
    showMenu();
    print HTML_TEXT_CFE;
  }
  print "\n";
}

function mergeEntity($entitiesId, $oldEntitiesId) {
  $metadata = new \metadata\MetadataMerge($entitiesId, $oldEntitiesId);
  $metadata->mergeFrom();
}

function removeEntity($entitiesId) {
  global $config, $html, $menuActive;
  $entityHandler = $config->getDb()->prepare('SELECT entityID, status FROM Entities WHERE id = :Id;');
  $entityHandler->bindParam(':Id', $entitiesId);
  $entityHandler->execute();
  if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    $html->showHeaders($entity['entityID']);
    $ok2Remove = true;
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
        $ok2Remove = false;
    }
    showMenu();
    if ($ok2Remove) {
      if (isset($_POST['action']) && $_POST['action'] == $button ) {
        $metadata = new \metadata\Metadata($entitiesId);
        $metadata->removeEntity();
        printf('    <p>You have removed <b>%s</b> from %s</p>%s', htmlspecialchars($entity['entityID']), $from, "\n");
      } else {
        printf('    <p>You are about to %s of <b>%s</b></p>%s    <form action="." method="POST">%s      <input type="hidden" name="removeEntity" value="%d">%s      <input type="submit" name="action" value="%s">%s    </form>%s    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', $action, htmlspecialchars($entity['entityID']), "\n", "\n", $entitiesId, "\n", $button, "\n", "\n",  $entitiesId);
      }
    } else {
      print "You can't Remove / Discard this entity";
    }
  } else {
    $html->showHeaders('NotFound');
    $menuActive = 'new';
    showMenu();
    print HTML_TEXT_CFE;
  }
  print "\n";
}

function checkAccess($entitiesId, $userID, $userLevel, $minLevel, $showError=false) {
  global $html;
  if ($userLevel >= $minLevel) {
    return true;
  }
  $metadata = new \metadata\Metadata($entitiesId);
  $metadata->getUser($userID);
  if ($metadata->isResponsible()) {
    return true;
  } else {
    if ($showError) {
      $html->showHeaders('');
      print "You doesn't have access to this entityID";
      printf('%s      <a href=".?showEntity=%d"><button type="button" class="btn btn-outline-danger">Back to entity</button></a>', "\n", $entitiesId);
    }
    return false;
  }
}

/**
 * Check entity is in required status
 *
 * @param int $entitiesId The entity to check
 * @param int $status The required status
 * @param int $userLevel The privilege level of the current user.  Optional if bypass is unused.
 * @param int $minLevel The minimum privilege level to bypass this check.  Zero or negative values disable bypass.  Optional, defaults to disabling bypass.
 * @return bool Wheter the check succeeded
 */
function checkStatus($entitiesId, $status, $userLevel=0, $minLevel=0, $showError=true) {
  global $html;
  // bypass check for admin users if allowed
  if ($minLevel > 0 && $userLevel >= $minLevel) {
    return true;
  }
  $metadata = new \metadata\Metadata($entitiesId);
  if ($metadata->status() == $status) {
    return true;
  } else {
    if ($showError) {
      $html->showHeaders('');
      print "The status of this entity does not permit this action.<br>";
      printf('%s      <a href=".?showEntity=%d"><button type="button" class="btn btn-outline-danger">Back to entity</button></a>', "\n", $entitiesId);
    }
    return false;
  }
}

# Request access to an entity
function requestAccess($entitiesId) {
  global $html, $menuActive;
  global $EPPN, $mail, $fullName, $userLevel;
  global $mailContacts, $mailRequester, $config;
  $federation = $config->getFederation();

  $metadata = new \metadata\Metadata($entitiesId);
  if ($metadata->entityExists()) {
    $user_id = $metadata->getUserId($EPPN);
    if ($metadata->isResponsible()) {
      # User already have access.
      $html->showHeaders($metadata->entityID());
      $menuActive = '';
      showMenu();
      printf('%s    <p>You already have access to <b>%s</b></p>%s', "\n", htmlspecialchars($metadata->entityID()), "\n");
      printf('    <a href="./?showEntity=%d"><button>Return to Entity</button></a>%s', $entitiesId, "\n");
    } else {
      $errors = '';
      $addresses = $metadata->getTechnicalAndAdministrativeContacts();

      if (isset($_POST['requestAccess'])) {
        # We are committing from the Form.
        # Fetch user_id again and make sure user exists
        $user_id = $metadata->getUserId($EPPN, $mail, $fullName, true);
        # Get code to send in email
        $requestCode = urlencode($metadata->createAccessRequest($user_id));
        setupMail();
        if ($config->sendOut()) {
          foreach ($addresses as $address) {
            $mailContacts->addAddress($address);
          }
        }
        $hostURL = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'];

        //Content
        $mailContacts->isHTML(true);
        $mailContacts->Body = sprintf("<!DOCTYPE html>
          <html lang=\"en\">
          <head>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
          </head>\n  <body>
          <p>Hi.</p>
          <p>%s (%s) has requested access to update %s</p>
          <p>You have received this email because you are either the technical and/or administrative contact.</p>
          <p>If you approve, please click on this link <a href=\"%s/admin/?approveAccessRequest=%s\">%s/admin/?approveAccessRequest=%s</a></p>
          <p>If you do not approve, you can ignore this email. No changes will be made.</p>
          <p>This is a message from the %s.<br>
          --<br>
          On behalf of %s</p>
          </body>\n</html>",
          htmlspecialchars($fullName), htmlspecialchars($mail), htmlspecialchars($metadata->entityID()), $hostURL, $requestCode, $hostURL, $requestCode,
          $federation['toolName'], $federation['teamName']);
        $mailContacts->AltBody = sprintf("Hi.
          \n%s (%s) has requested access to update %s
          \nYou have received this email because you are either the technical and/or administrative contact.
          \nIf you approve, please click on this link %s/admin/?approveAccessRequest=%s
          \nIf you do not approve, you can ignore this email. No changes will be made.
          \nThis is a message from the %s.
          --
          On behalf of %s",
        $fullName, $mail, $metadata->entityID(), $hostURL, $requestCode, $federation['toolName'], $federation['teamName']);
        $info = sprintf(
          "<p>The request has been sent to: %s</p>\n<p>Contact them and ask them to accept your request.</p>\n",
          implode (", ",$addresses));

        $shortEntityid = preg_replace(REGEXP_ENTITYID, '$1', $metadata->entityID());

        $mailContacts->Subject = 'Access request for ' . $shortEntityid;

        try {
          $mailContacts->send();
        } catch (Exception $e) {
          echo HTML_TEXT_MCNBS;
          echo HTML_TEXT_ME . $mailContacts->ErrorInfo . '<br>';
        }
        $menuActive = '';
        showText($info, true, false);
      } else {
        $errors .= isset($_POST['FormVisit']) ? "You must check the box to confirm.\n" : '';
        $html->showHeaders($metadata->entityID());
        $menuActive = '';
        showMenu();
        if ($errors != '') {
          printf('%s    <div class="row alert alert-danger" role="alert">
      <div class="col">
        <div class="row"><b>Errors:</b></div>
        <div class="row">%s</div>
      </div>%s    </div>', "\n", str_ireplace("\n", "<br>", $errors), "\n");
        }
        printf('%s    <p>You do not have access to <b>%s</b></p>%s', "\n", htmlspecialchars($metadata->entityID()), "\n");
        printf('    <form action="." method="POST">
      <input type="hidden" name="Entity" value="%d">
      <input type="hidden" name="FormVisit">
      <h5>Request access:</h5>
      <input type="checkbox" id="requestAccess" name="requestAccess">
      <label for="requestAccess">I confirm that I have the right to update this entity.</label><br>
      <p>A mail will be sent to the following addresses with further instructions: %s.</p>
      <input type="submit" name="action" value="Request Access">
    </form>
    <a href="./?showEntity=%d"><button>Return to Entity</button></a>%s',
          $entitiesId, implode (", ",$addresses), $entitiesId, "\n");
        if ($userLevel > 19) {
          printf('    <br><form action="." method="POST"><input type="hidden" name="action" value="forceAccess"><input type="hidden" name="Entity" value="%d"><button>Force access to Entity</button></form>%s', $entitiesId, "\n");
        }
      }
    }
  }
}

# Return Blocking errors
function getBlockingErrors($entitiesId) {
  global $config;
  $errors = '';

  $entityHandler = $config->getDb()->prepare('SELECT `entityID`, `errors` FROM Entities WHERE `id` = :Id;');
  $entityHandler->bindParam(':Id', $entitiesId);

  $entityHandler->execute();
  if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
    $errors .= $entity['errors'];
  }
  return $errors;
}

# Return All errors, both blocking and nonblocking
function getErrors($entitiesId) {
  global $config;
  $errors = '';

  $entityHandler = $config->getDb()->prepare('SELECT `entityID`, `errors`, `errorsNB` FROM Entities WHERE `id` = :Id;');
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
  $html->showHeaders('');
  $menuActive = '';
  showMenu();
  $display->showHelp();
}

function approveAccessRequest($code) {
  global $EPPN, $config;
  $federation = $config->getFederation();
  $codeArray = explode(':', base64_decode($code));
  if (isset($codeArray[2])) {
    $metadata = new \metadata\Metadata($codeArray[0]);
    if ($metadata->entityExists()) {
      $result = $metadata->validateCode($codeArray[1], $codeArray[2], $EPPN);
      if ($result['returnCode'] < 10) {
        $info = $result['info'];
        if ($result['returnCode'] == 2) {
          $mail = new PHPMailer(true);
          $mail->isSMTP();
          $mail->Host = $config->getSmtp()['host'];
          $mail->Port = $config->getSmtp()['port'];
          $mail->SMTPAutoTLS = true;
          if ($config->smtpAuth()) {
            $mail->SMTPAuth = true;
            $mail->Username = $config->getSmtp()['sasl']['user'];
            $mail->Password = $config->getSmtp()['sasl']['password'];
            $mail->SMTPSecure = 'tls';
          }

          //Recipients
          $mail->setFrom($config->getSmtp()['from'], 'Metadata');
          if ($config->getSMTP()['bcc']) {
            $mail->addBCC($config->getSMTP()['bcc']);
          }
          $mail->addReplyTo($config->getSmtp()['replyTo'], $config->getSmtp()['replyName']);
          if ($config->sendOut()) {
            $mail->addAddress($result['email']);
          }
          $mail->Body = sprintf("<!DOCTYPE html>
            <html lang=\"en\">
              <head>
                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
              </head>
              <body>
                <p>Hi.</p>
                <p>Your access to %s have been granted.</p>
                <p>-- <br>This mail was sent by %s, a service provided by %s.
                If you've any questions please contact %s.</p>
              </body>
            </html>",
            htmlspecialchars($metadata->entityID()),
            $federation['toolName'], $federation['teamName'], $federation['teamMail']);
          $mail->AltBody = sprintf("Hi.
            \nYour access to %s have been granted.
            \n--
            This mail was sent by %s, a service provided by %s.
            If you've any questions please contact %s.",
            $metadata->entityID(),
            $federation['toolName'], $federation['teamName'], $federation['teamMail']);
          $shortEntityid = preg_replace(REGEXP_ENTITYID, '$1', $metadata->entityID());
          $mail->Subject = 'Access granted for ' . $shortEntityid;

          $info = sprintf('<h3>Access granted</h3>Access to <b>%s</b> added for %s (%s).',
            htmlspecialchars($metadata->entityID()), htmlspecialchars($result['fullName']), htmlspecialchars($result['email']));

          try {
            $mail->send();
          } catch (Exception $e) {
            echo HTML_TEXT_MCNBS;
            echo HTML_TEXT_ME . $mail->ErrorInfo . '<br>';
          }
        }
        showText($info);
      } else {
        showText($result['info'], false, true);
      }
    } else {
      showText('Invalid code', false, true);
    }
  } else {
    showText('Invalid code', false, true);
  }
}

function showText($text, $showMenu = false, $error = false) {
  global $html, $menuActive;
  if ($error) {
    $html->showHeaders('Error');
  } else {
    $html->showHeaders('Info');
  }
  if ($showMenu) { showMenu(); }
  printf ('    <div class="row">%s      <div class="col">%s        %s%s      </div>%s    </div>%s',
    "\n", "\n", $text, "\n", "\n", "\n");
}
