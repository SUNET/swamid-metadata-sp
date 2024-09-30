<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

const BIND_ASSURANCE = ':Assurance';
const HTML_TITLE = 'Metadata SWAMID - ';
const HTML_TITLE_PROBLEM = 'Metadata SWAMID - Problem';
const HTML_CLASS_FA_UP = '<i class="fa fa-arrow-up"></i>';
const HTML_CLASS_FA_DOWN = '<i class="fa fa-arrow-down"></i>';
const HTML_OUTLINE = '-outline';
const HTML_CHECKED = ' checked';
const HTML_TEXT_CFE = "Can't find Entity";
const HTML_TEXT_MCNBS = 'Message could not be sent to contacts.<br>';
const HTML_TEXT_ME = 'Mailer Error: ';

const HTML_TEXT_STP_BOTH = '4.1.1, 4.1.2, 4.2.1 and 4.2.2';
const HTML_TEXT_STP_IDP = '4.1.1 and 4.1.2';
const HTML_TEXT_STP_SP = '4.2.1 and 4.2.2';
const HTML_TEXT_STPINFO_BOTH = '<ul>
          <li>4.1.1 For an organisation to be eligible to register an Identity Provider in SWAMID metadata the organisation MUST be a member of the SWAMID Identity Federation.</li>
          <li>4.1.2 All Member Organisations MUST fulfil one or more of the SWAMID Identity Assurance Profiles to be eligible to have an Identity Provider registered in SWAMID metadata.</li>
          <li>4.2.1 A Relying Party is eligible for registration in SWAMID if they are:<ul>
            <li>a service owned by a Member Organisation;</li>
            <li>a service under contract with at least one Member Organisation;</li>
            <li>a government agency service used by at least one Member Organisation;</li>
            <li>a service that is operated at least in part for the purpose of supporting research and scholarship interaction, collaboration or management; or</li>
            <li>a service granted special approval by SWAMID Board of Trustees after recommendation by SWAMID Operations.</li>
          </ul></li>
          <li>4.2.2 For a Relying Party to be registered in SWAMID the Service Owner MUST accept the <a href="https://mds.swamid.se/md/swamid-tou-en.txt" target="_blank">SWAMID Metadata Terms of Access and Use</a>.</li>
        </ul>';
const HTML_TEXT_STPINFO_IDP = '<ul>
          <li>4.1.1 For an organisation to be eligible to register an Identity Provider in SWAMID metadata the organisation MUST be a member of the SWAMID Identity Federation.</li>
          <li>4.1.2 All Member Organisations MUST fulfil one or more of the SWAMID Identity Assurance Profiles to be eligible to have an Identity Provider registered in SWAMID metadata.</li>
        </ul>';
const HTML_TEXT_STPINFO_SP = '<ul>
          <li>4.2.1 A Relying Party is eligible for registration in SWAMID if they are:<ul>
            <li>a service owned by a Member Organisation;</li>
            <li>a service under contract with at least one Member Organisation;</li>
            <li>a government agency service used by at least one Member Organisation;</li>
            <li>a service that is operated at least in part for the purpose of supporting research and scholarship interaction, collaboration or management; or</li>
            <li>a service granted special approval by SWAMID Board of Trustees after recommendation by SWAMID Operations.</li>
          </ul></li>
          <li>4.2.2 For a Relying Party to be registered in SWAMID the Service Owner MUST accept the <a href="https://mds.swamid.se/md/swamid-tou-en.txt" target="_blank">SWAMID Metadata Terms of Access and Use</a>.</li>
        </ul>';
const OPERATIONS_NAME = 'SWAMID Operations';
const OPERATIONS_MAIL = 'operations@swamid.se';
const REGEXP_ENTITYID = '/^https?:\/\/([^:\/]*)\/.*/';

//Load composer's autoloader
require_once '../vendor/autoload.php';

require_once '../config.php'; #NOSONAR

require_once '../include/Html.php'; #NOSONAR
$html = new HTML($Mode);

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

/* BEGIN RAF Logging */
$assuranceHandler = $db->prepare(
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
  case 'pax@sunet.se' :
  case 'jocar@sunet.se' :
  case 'mifr@sunet.se' :
    $userLevel = 20;
    break;
  case 'frkand02@umu.se' :
  case 'paulscot@kau.se' :
    $userLevel = 10;
    break;
  case 'johpe12@liu.se' :
  case 'toylon98@umu.se' :
    $userLevel = 5;
    break;
  default :
    $userLevel = 1;
}
$displayName = '<div> Logged in as : <br> ' . $fullName . ' (' . $EPPN .')</div>';
$html->setDisplayName($displayName);


require_once '../include/MetadataDisplay.php'; #NOSONAR
$display = new MetadataDisplay();

require_once '../include/Metadata.php'; #NOSONAR

if (isset($_FILES['XMLfile'])) {
  importXML();
} elseif (isset($_GET['edit'])) {
  if (isset($_GET['Entity']) && (isset($_GET['oldEntity']))) {
    require_once '../include/MetadataEdit.php'; #NOSONAR
    $editMeta = new MetadataEdit($_GET['Entity'], $_GET['oldEntity']);
    $editMeta->updateUser($EPPN, $mail, $fullName);
    if (checkAccess($_GET['Entity'], $EPPN, $userLevel, 10, true)) {
      $html->showHeaders(HTML_TITLE . 'Edit - '.$_GET['edit']);
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
          $metadata->getUserId($EPPN);
          if ($metadata->isResponsible()) {
            if ($newEntity_id = $metadata->createDraft()) {
              $metadata->validateXML();
              $metadata->validateSAML();
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
        case 'removeSaml1' :
          $metadata = new Metadata($entitiesId);
          $metadata->getUserId($EPPN);
          if ($metadata->isResponsible()) {
            $metadata->removeSaml1Support();
            $metadata->clearWarning();
            $metadata->clearError();
            $metadata->validateXML();
            $metadata->validateSAML();
            $menuActive = 'new';
            showEntity($entitiesId);
          } else {
            # User have no access yet.
            requestAccess($entitiesId);
          }
          break;
        case 'draftRemoveSaml1' :
          $metadata = new Metadata($entitiesId);
          $metadata->getUserId($EPPN);
          if ($metadata->isResponsible()) {
            if ($newEntity_id = $metadata->createDraft()) {
              $metadata->removeSaml1Support();
              $metadata->clearWarning();
              $metadata->clearError();
              $metadata->validateXML();
              $metadata->validateSAML();
              $menuActive = 'new';
              showEntity($newEntity_id);
            }
          } else {
            # User have no access yet.
            requestAccess($entitiesId);
          }
          break;
        case 'removeObsoleteAlgorithms' :
          $metadata = new Metadata($entitiesId);
          $metadata->getUserId($EPPN);
          if ($metadata->isResponsible()) {
            $metadata->removeObsoleteAlgorithms();
            $metadata->clearWarning();
            $metadata->clearError();
            $metadata->validateXML();
            $metadata->validateSAML();
            showEntity($entitiesId);
          } else {
            # User have no access yet.
            requestAccess($entitiesId);
          }
          break;
        case 'draftRemoveObsoleteAlgorithms' :
          $metadata = new Metadata($entitiesId);
          $metadata->getUserId($EPPN);
          if ($metadata->isResponsible()) {
            if ($newEntity_id = $metadata->createDraft()) {
              $metadata->removeObsoleteAlgorithms();
              $metadata->clearWarning();
              $metadata->clearError();
              $metadata->validateXML();
              $metadata->validateSAML();
              $menuActive = 'new';
              showEntity($newEntity_id);
            }
          } else {
            # User have no access yet.
            requestAccess($entitiesId);
          }
          break;
        case 'forceAccess' :
          $metadata = new Metadata($entitiesId);
          if ($userLevel > 19) {
            $metadata->addAccess2Entity($metadata->getUserId($EPPN, $mail, $fullName, true), $EPPN);
          }
          showEntity($entitiesId);
          break;
        case 'AddImps2IdP' :
          if ($userLevel > 19 && isset($_GET['ImpsId'])) {
            $imps = new metadata\IMPS();
            $imps->BindIdP2IMPS($entitiesId, $_GET['ImpsId']);
          }
          $metadata = new Metadata($entitiesId);
          showEntity($entitiesId);
          break;
        case 'Confirm IMPS' :
          $metadata = new Metadata($entitiesId);
          if ($metadata->status() == 1) {
            $metadata->getUserId($EPPN);
            if ($metadata->isResponsible()) {
              $html->showHeaders(HTML_TITLE . $metadata->entityID());
              $menuActive = 'publ';
              showMenu();
              $imps = new metadata\IMPS();
              if ($imps->validateIMPS($entitiesId, $_GET['ImpsId'], $metadata->getUserId($EPPN))) {
                showEntity($entitiesId, false);
              }
            } else {
              # User have no access yet.
              requestAccess($entitiesId);
            }
          } else {
            $html->showHeaders(HTML_TITLE . 'NotFound');
            $menuActive = 'publ';
            showMenu();
            print HTML_TEXT_CFE;
          }
          break;
        default :
          if ($userLevel > 19) {
            printf ('Missing action : %s', urlencode($_GET['action']));
            exit;
          }
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
          if (sizeof($_POST)) {
            annualConfirmationList($_POST);
          } else {
            showMyEntities();
          }
          break;
        case 'EntityStatistics' :
          $menuActive = 'EntityStatistics';
          $html->showHeaders(HTML_TITLE . 'Entity Statistics');
          showMenu();
          $display->showEntityStatistics();
          break;
        case 'EcsStatistics' :
          $menuActive = 'EcsStatistics';
          $html->showHeaders(HTML_TITLE . 'EntityCategorySupport status');
          showMenu();
          $display->showEcsStatistics();
          break;
        case 'RAFStatistics' :
          $menuActive = 'RAFStatistics';
          $html->showHeaders(HTML_TITLE . 'RAF status');
          showMenu();
          $display->showRAFStatistics();
          break;
        case 'showURL' :
          $menuActive = '';
          $html->showHeaders(HTML_TITLE . 'URL status');
          showMenu();
          if (isset($_GET['URL'])) {
            if (isset($_GET['recheck'])) {
              $metadata = new Metadata();
              $metadata->revalidateURL($_GET['URL'], isset($_GET['verbose']));
            }
            $display->showURLStatus($_GET['URL']);
          }
          break;
        case 'URLlist' :
          if ($userLevel > 4) {
            $menuActive = 'URLlist';
            $html->showHeaders(HTML_TITLE . 'URL status');
            showMenu();
            if (isset($_GET['URL'])) {
              if (isset($_GET['recheck'])) {
                $metadata = new Metadata();
                $metadata->revalidateURL($_GET['URL'], isset($_GET['verbose']));
              }
              $display->showURLStatus($_GET['URL']);
            } else {
              $display->showURLStatus();
            }
          }
          break;
        case 'ErrorList' :
          $menuActive = 'Errors';
          $html->showHeaders(HTML_TITLE . 'Error status');
          showMenu();
          $display->showErrorList();
          $html->addTableSort('error-table');
          $html->addTableSort('reminder-table');
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
            $html->showHeaders(HTML_TITLE . 'Clean Pending');
            showMenu();
            $display->showPendingList();
          }
          break;
        case 'ShowDiff' :
          $menuActive = 'CleanPending';
          $html->showHeaders(HTML_TITLE . 'Clean Pending');
          showMenu();
          if (isset($_GET['entity_id1']) && isset($_GET['entity_id2'])) {
            $display->showXMLDiff($_GET['entity_id1'], $_GET['entity_id2']);
          }
          $display->showPendingList();
          break;
        case 'OrganizationsInfo' :
          $menuActive = 'OrganizationsInfo';
          $html->showHeaders(HTML_TITLE . 'Show EntityInfo');
          showMenu();
          $display->showOrganizationLists();
          $html->addTableSort('Organizationsv-table');
          $html->addTableSort('Organizationen-table');
          break;
        case 'Members' :
          $menuActive = 'Members';
          $html->showHeaders(HTML_TITLE . 'Show Member Information');
          showMenu();
          if (isset($_GET['subAction']) && isset($_GET['id'])) {
            $imps = new metadata\IMPS();
            switch ($_GET['subAction']) {
              case 'editImps' :
                $imps->editIMPS($_GET['id']);
                break;
              case 'saveImps' :
                if ($imps->saveImps($_GET['id'])) {
                  $display->showMembers($userLevel);
                  $html->addTableSort('scope-table');
                } else {
                  $imps->editIMPS($_GET['id']);
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
  global $db, $html;

  $feedOrder = 'feedDesc';
  $orgOrder = 'orgAsc';
  $entityIDOrder = 'entityIDAsc';
  $feedArrow = '';
  $orgArrow = '';
  $entityIDArrow = '';
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
  } else {
     $query = '';
  }
  $filter = 'query='.urlencode($query);

  switch ($status) {
    case 1 :
      $html->showHeaders(HTML_TITLE . 'Published');
      $action = 'pub';
      $minLevel = 0;
      $filter .= '&action=pub';
      break;
    case 2 :
      $html->showHeaders(HTML_TITLE . 'Pending');
      $action = 'wait';
      $minLevel = 5;
      $filter .= '&action=wait';
      break;
    case 3 :
      $html->showHeaders(HTML_TITLE . 'Drafts');
      $action = 'new';
      $minLevel = 5;
      $filter .= '&action=new';
      break;
    case 4 :
      $html->showHeaders(HTML_TITLE . 'Deleted');
      $action = 'pub';
      $minLevel = 5;
      break;
    case 5 :
      $html->showHeaders(HTML_TITLE . 'Pending already Published');
      $action = 'pub';
      $minLevel = 5;
      break;
    case 6 :
      $html->showHeaders(HTML_TITLE . 'Published when added to Pending');
      $action = 'pub';
      $minLevel = 5;
      break;
    default :
      $html->showHeaders(HTML_TITLE);
  }
  showMenu();
  if ($status == 1) {
    $entities = $db->prepare( # NOSONAR $sortOrder is secure
      "SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `data` AS OrganizationName,
        `lastUpdated`, `lastConfirmed` AS lastValidated, `warnings`, `errors`, `errorsNB`
      FROM Entities
      LEFT JOIN Organization ON Organization.entity_id = id AND element = 'OrganizationName' AND lang = 'en'
      LEFT JOIN EntityConfirmation ON EntityConfirmation.entity_id = id
      WHERE status = $status AND entityID LIKE :Query ORDER BY $sortOrder");
  } else {
    $entities = $db->prepare( # NOSONAR $sortOrder is secure
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
  global $db, $html, $display, $userLevel, $menuActive, $EPPN, $Mode;
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
    if ($entity['status'] > 1 && $entity['status'] < 7) {
      if ($entity['publishedId'] > 0) {
        $entityHandlerOld = $db->prepare(
          'SELECT `id`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE `id` = :Id AND `status` = 6;');
        $entityHandlerOld->bindParam(':Id', $entity['publishedId']);
        $headerCol2 = 'Old metadata - when requested publication';
      } else {
        $entityHandlerOld = $db->prepare(
          'SELECT `id`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE `entityID` = :Id AND `status` = 1;');
        $entityHandlerOld->bindParam(':Id', $entity['entityID']);
        $headerCol2 = 'Published now';
      }
      $entityHandlerOld->execute();
      if ($entityOld = $entityHandlerOld->fetch(PDO::FETCH_ASSOC)) {
        $oldEntitiesId = $entityOld['id'];
        if (($entityOld['publishIn'] & 2) == 2) { $publishArrayOld[] = 'SWAMID'; }
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
      $html->showHeaders(HTML_TITLE . $entity['entityID']);
      showMenu();
    }?>
    <div class="row">
      <div class="col">
        <h3>entityID = <?=$entity['entityID']?></h3>
      </div>
    </div><?php
    $entityError = $display->showStatusbar($entitiesId, $userLevel > 4 ? true : false);
    print "\n" . '    <div class="row">';
    switch ($entity['status']) {
      case 1:
        $metadata = new Metadata($entitiesId);
        $metadata->getUserId($EPPN);
        if ($metadata->isResponsible()) {
          printf('%s      <a href=".?action=Annual+Confirmation&Entity=%d">
        <button type="button" class="btn btn-outline-%s">Annual Confirmation</button></a>',
          "\n", $entitiesId, getErrors($entitiesId) == '' ? 'success' : 'secondary');
          printf('%s      <a href=".?action=createDraft&Entity=%d">
        <button type="button" class="btn btn-outline-primary">Create draft</button></a>', "\n", $entitiesId);
          if ($entityError['saml1Error']) {
            printf('%s      <a href=".?action=draftRemoveSaml1&Entity=%d">
        <button type="button" class="btn btn-outline-danger">Remove SAML1 support</button></a>',
              "\n", $entitiesId);
          }
          if ($entityError['algorithmError']) {
            printf('%s      <a href=".?action=draftRemoveObsoleteAlgorithms&Entity=%d">
        <button type="button" class="btn btn-outline-danger">Remove Obsolete Algorithms</button></a>',
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
            printf('%s      <a href=".?action=removeSaml1&Entity=%d">
        <button type="button" class="btn btn-outline-danger">Remove SAML1 support</button></a>',
              "\n", $entitiesId);
          }
          if ($entityError['algorithmError']) {
            printf('%s      <a href=".?action=removeObsoleteAlgorithms&Entity=%d">
        <button type="button" class="btn btn-outline-danger">Remove Obsolete Algorithms</button></a>',
              "\n", $entitiesId);
          }
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
    if ($entity['isIdP'] && $entity['status'] == 1 && $Mode != 'QA') { $display->showIMPS($entitiesId, $userLevel > 19); }
    $display->showEntityAttributes($entitiesId, $oldEntitiesId, $allowEdit);
    $able2beRemoveSSO = ($entity['isIdP'] && $entity['isSP'] && $allowEdit);
    if ($entity['isIdP'] ) { $display->showIdP($entitiesId, $oldEntitiesId, $allowEdit, $able2beRemoveSSO); }
    if ($entity['isSP'] ) { $display->showSp($entitiesId, $oldEntitiesId, $allowEdit, $able2beRemoveSSO); }
    if ($entity['isAA'] ) { $display->showAA($entitiesId, $oldEntitiesId, $allowEdit, $allowEdit); }
    $display->showOrganization($entitiesId, $oldEntitiesId, $allowEdit);
    $display->showContacts($entitiesId, $oldEntitiesId, $allowEdit);
    if ($entity['status'] == 1) { $display->showMdqUrl($entity['entityID'], $Mode); }
    $display->showXML($entitiesId);
    if ($oldEntitiesId > 0 && $userLevel > 10) {
      $display->showDiff($entitiesId, $oldEntitiesId);
    }
    $display->showEditors($entitiesId);
  } else {
    $html->showHeaders(HTML_TITLE . 'NotFound');
    print HTML_TEXT_CFE;
  }
}

####
# Shows a list of entitys
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
        case 3 :
          $export2Edugain = '';
          break;
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
        $export2Edugain, $row['id'], $row['entityID'], $row['OrganizationName'],
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
  global $db, $html, $EPPN, $userLevel;

  $html->showHeaders(HTML_TITLE . 'Annual Check');
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
    printf ('      </div>%s    </div>%s', "\n", "\n");
    }
  if (isset($_GET['showPub']) && $userLevel > 9) {
    $entitiesHandler = $db->prepare("SELECT Entities.`id`, `entityID`, `errors`, `errorsNB`, `warnings`, `status`
      FROM Entities WHERE `status` = 1 AND publishIn > 1 ORDER BY `entityID`");
  } else {
    $entitiesHandler = $db->prepare("SELECT Entities.`id`, `entityID`, `errors`, `errorsNB`, `warnings`, `status`
      FROM Users, EntityUser, Entities
      WHERE EntityUser.`entity_id` = Entities.`id`
        AND EntityUser.`user_id` = Users.`id`
        AND `status` < 4
        AND `userID` = :UserID
      ORDER BY `entityID`, `status`");
    $entitiesHandler->bindValue(':UserID', $EPPN);
  }
  $entityConfirmationHandler = $db->prepare(
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
      $row['id'], $row['entityID'], $checkBox, $errorStatus, $pubStatus, $lastConfirmed, $confirmStatus, $updater, "\n");
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
  $html->showHeaders(HTML_TITLE . 'Add new XML');
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
# Shows menu row
####
function showMenu() {
  global $userLevel, $menuActive;
  $filter='';
  if (isset($_GET['query'])) {
    $filter='&query='.urlencode($_GET['query']);
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
    printf('<a href="./mds.php" target="_blank"><button type="button" class="btn btn%s-primary">MDS</button></a>',
      HTML_OUTLINE);
  }
  if ( $userLevel > 10 ) {
    printf('<a href=".?action=CleanPending%s"><button type="button" class="btn btn%s-primary">Clean Pending</button></a>',
      $filter, $menuActive == 'CleanPending' ? '' : HTML_OUTLINE);
  }
  print "\n    <br>\n    <br>\n";
}

function validateEntity($entitiesId) {
  $metadata = new Metadata($entitiesId);
  $metadata->clearWarning();
  $metadata->clearError();
  $metadata->validateXML();
  $metadata->validateSAML();
}

function move2Pending($entitiesId) {
  global $html, $menuActive;
  global $EPPN, $mail, $fullName;
  global $mailContacts, $mailRequester, $SendOut, $Mode;

  $draftMetadata = new Metadata($entitiesId);

  if ($draftMetadata->entityExists()) {
    $draftMetadata->clearWarning();
    $draftMetadata->clearError();
    $draftMetadata->validateXML();
    $draftMetadata->validateSAML();
    if ( $draftMetadata->isIdP() && $draftMetadata->isSP()) {
      $sections = HTML_TEXT_STP_BOTH ;
      $infoText = HTML_TEXT_STPINFO_BOTH;
    } elseif ($draftMetadata->isIdP()) {
      $sections = HTML_TEXT_STP_IDP ;
      $infoText = HTML_TEXT_STPINFO_IDP;
    } elseif ($draftMetadata->isSP()) {
      $sections = HTML_TEXT_STP_SP ;
      $infoText = HTML_TEXT_STPINFO_SP;
    }
    $html->showHeaders(HTML_TITLE . $draftMetadata->entityID());
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

        setupMail();

        $shortEntityid = preg_replace(REGEXP_ENTITYID, '$1', $draftMetadata->entityID());
        $publishedMetadata = new Metadata($draftMetadata->entityID(), 'prod');

        if ($publishedMetadata->entityExists()) {
          $mailContacts->Subject  = 'Info : Updated SWAMID metadata for ' . $shortEntityid;
          $mailRequester->Subject = 'Updated SWAMID metadata for ' . $shortEntityid;
          $shadowMetadata = new Metadata($draftMetadata->entityID(), 'Shadow');
          $shadowMetadata->importXML($publishedMetadata->xml());
          $shadowMetadata->updateFeedByValue($publishedMetadata->feedValue());
          $shadowMetadata->validateXML();
          $shadowMetadata->validateSAML();
          $oldEntitiesId = $shadowMetadata->id();
        } else {
          $mailContacts->Subject  = 'Info : New SWAMID metadata for ' . $shortEntityid;
          $mailRequester->Subject = 'New SWAMID metadata for ' . $shortEntityid;
          $oldEntitiesId = 0;
        }

        if ($Mode == 'QA') {
          printf ("    <p>Your entity will be published within 15 to 30 minutes</p>\n");
          printf ('    <hr>
          <a href=".?showEntity=%d"><button type="button" class="btn btn-primary">Back to entity</button></a>',
            $entitiesId);
        } else {
          $displayName = $draftMetadata->entityDisplayName();

          $addresses = $draftMetadata->getTechnicalAndAdministrativeContacts();
          if ($SendOut) {
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
              <p>If you do not approve this update, forward this email to SWAMID Operations (operations@swamid.se)
              and request for the update to be denied.</p>
              <p>This is a message from the SWAMID SAML WebSSO metadata administration tool.<br>
              --<br>
              On behalf of SWAMID Operations</p>
            </body>
          </html>",
            $fullName, $mail, $displayName, $draftMetadata->entityID(), $hostURL, $entitiesId, $hostURL, $entitiesId);
          $mailContacts->AltBody = sprintf("Hi.
          \n%s (%s) has requested an update of \"%s\" (%s)
          \nYou have received this email because you are either the new or old technical and/or administrative contact.
          \nYou can see the new version at %s/?showEntity=%d
          \nIf you do not approve this update, forward this email to SWAMID Operations (operations@swamid.se) and request for the update to be denied.
          \nThis is a message from the SWAMID SAML WebSSO metadata administration tool.
          --
          On behalf of SWAMID Operations",
            $fullName, $mail, $displayName, $draftMetadata->entityID(), $hostURL, $entitiesId);

          $mailRequester->isHTML(true);
          $mailRequester->Body = sprintf("<!DOCTYPE html>
          <html lang=\"en\">
            <head>
              <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
            </head>
            <body>
              <p>Hi.</p>
              <p>You have requested an update of \"%s\" (%s)</p>
              <p>To continue the publication request, forward this email to SWAMID Operations (operations@swamid.se).
              If you don’t do this the publication request will not be processed.</p>
              <p>The new version can be found at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>
              <p>An email has also been sent to the following addresses since they are the new or old technical
              and/or administrative contacts : </p>
              <p><ul>
              <li>%s</li>
              </ul>
              <p>This is a message from the SWAMID SAML WebSSO metadata administration tool.<br>
              --<br>
              On behalf of SWAMID Operations</p>
            </body>
          </html>",
            $displayName, $draftMetadata->entityID(), $hostURL, $entitiesId,
            $hostURL, $entitiesId,implode ("</li>\n<li>",$addresses));
          $mailRequester->AltBody = sprintf("Hi.
          \nYou have requested an update of \"%s\" (%s)
          \nTo continue the publication request, forward this email to SWAMID Operations (operations@swamid.se).
          If you don’t do this the publication request will not be processed.
          \nThe new version can be found at %s/?showEntity=%d
          \nAn email has also been sent to the following addresses since they are the new or old technical and/or administrative contacts : %s
          \nThis is a message from the SWAMID SAML WebSSO metadata administration tool.
          --
          On behalf of SWAMID Operations",
            $displayName, $draftMetadata->entityID(), $hostURL, $entitiesId, implode (", ",$addresses));

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
        $draftMetadata->updateFeedByValue($_GET['publishedIn']);
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
        printf('%s    <p>You are about to request publication of <b>%s</b></p>', "\n", $draftMetadata->entityID());
        $publishedMetadata = new Metadata($draftMetadata->entityID(), 'prod');

        $publishArrayOld = array();
        if ($publishedMetadata->entityExists()) {
          $oldPublishedValue = $publishedMetadata->feedValue();
          if (($oldPublishedValue & 2) == 2) { $publishArrayOld[] = 'SWAMID'; }
          if (($oldPublishedValue & 4) == 4) { $publishArrayOld[] = 'eduGAIN'; }
          printf('%s    <p>Currently published in <b>%s</b></p>', "\n", implode (' and ', $publishArrayOld));
        } else {
          $oldPublishedValue = $draftMetadata->isIdP() ? 7 : 3;
        }
        printf('    <h5>The entity should be published in:</h5>
    <form>
      <input type="hidden" name="move2Pending" value="%d">%s',
          $entitiesId,"\n");
        if ($Mode == 'QA') {
          printf('      <input type="radio" id="SWAMID" name="publishedIn" value="2" checked>
      <label for="SWAMID">SWAMID QA</label>%s',"\n");
        } else {
          printf('      <input type="radio" id="SWAMID_eduGAIN" name="publishedIn" value="7"%s>
      <label for="SWAMID_eduGAIN">SWAMID and eduGAIN</label><br>
      <input type="radio" id="SWAMID" name="publishedIn" value="3"%s>
      <label for="SWAMID">SWAMID</label>%s',
          $oldPublishedValue == 7 ? HTML_CHECKED : '',
          $oldPublishedValue == 3 ? HTML_CHECKED : '', "\n");
        }
        printf('      <br>
      <h5> Confirmation:</h5>
      <p>Registration criteria from SWAMID SAML WebSSO Technology Profile:
        %s
      </p>
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
          $infoText, $sections, $entitiesId);
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
    $html->showHeaders(HTML_TITLE . 'NotFound');
    $menuActive = 'new';
    showMenu();
    print HTML_TEXT_CFE;
  }
  print "\n";
}

function annualConfirmation($entitiesId){
  global $html, $menuActive;
  global $EPPN, $mail, $fullName, $userLevel;

  $metadata = new Metadata($entitiesId);
  if ($metadata->status() == 1) {
    $confirm = false;
    # Entity is Published
    $errors = getErrors($entitiesId);
    $user_id = $metadata->getUserId($EPPN);
    if (isset ($_GET['user_id']) && ($user_id <> $_GET['user_id'] || $userLevel > 19) && $metadata->isResponsible()) {
      $metadata->removeAccessFromEntity($_GET['user_id']);
    }
    if ($errors == '') {
      if ($metadata->isResponsible()) {
        # User have access to entity
        if ( $metadata->isIdP() && $metadata->isSP()) {
          $sections = HTML_TEXT_STP_BOTH ;
          $infoText = HTML_TEXT_STPINFO_BOTH;
        } elseif ($metadata->isIdP()) {
          $sections = HTML_TEXT_STP_IDP ;
          $infoText = HTML_TEXT_STPINFO_IDP;
        } elseif ($metadata->isSP()) {
          $sections = HTML_TEXT_STP_SP ;
          $infoText = HTML_TEXT_STPINFO_SP;
        }

        if (isset($_GET['entityIsOK'])) {
          $metadata->updateUser($EPPN, $mail, $fullName, true);
          $confirm = true;
        } else {
          $errors .= isset($_GET['FormVisit'])
            ? "You must fulfill sections $sections in SWAMID SAML WebSSO Technology Profile.\n"
            : '';
        }

        if ($confirm) {
          $metadata->confirmEntity($user_id);
          $menuActive = 'myEntities';
          showMyEntities();
        } else {
          $html->showHeaders(HTML_TITLE . $metadata->entityID());
          $menuActive = '';
          showMenu();
          if ($errors != '') {
            printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
          }
          printf(
            '%s    <p>You are confirming that <b>%s</b> is operational and fulfils SWAMID SAML WebSSO Technology Profile</p>%s',
            "\n", $metadata->entityID(), "\n");
          printf('    <form>
      <input type="hidden" name="Entity" value="%d"
      <input type="hidden" name="FormVisit" value="true">
      <h5> Confirmation:</h5>
      <p>Registration criteria from SWAMID SAML WebSSO Technology Profile:
        %s
      </p>
      <input type="checkbox" id="entityIsOK" name="entityIsOK">
      <label for="entityIsOK">I confirm that this Entity fulfils sections <b>%s</b> in <a href="http://www.swamid.se/policy/technology/saml-websso" target="_blank">SWAMID SAML WebSSO Technology Profile</a></label><br>
      <br>
      <input type="submit" name="action" value="Annual Confirmation">
    </form>
    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>%s',
            $entitiesId, $infoText, $sections, $entitiesId, "\n");
        }
      } else {
        # User have no access yet.
        requestAccess($entitiesId);
      }
    } else {
      $html->showHeaders(HTML_TITLE . $metadata->entityID());
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
          sprintf('<a href="?action=Annual+Confirmation&Entity=%d&user_id=%d"><i class="fas fa-trash"></i></a>',
            $entitiesId, $user['id']);
        printf ('      <li>%s%s (%s)</li>%s', $delete, $user['fullName'], $user['userID'], "\n");
      }
    }
    printf('    </ul>%s', "\n");

  } else {
    $html->showHeaders(HTML_TITLE . 'NotFound');
    $menuActive = 'myEntities';
    showMenu();
    print HTML_TEXT_CFE;
  }
}

function annualConfirmationList($list){
  global $html, $menuActive;
  global $EPPN, $mail, $fullName;
  $isIdP = false;
  $isSP = false;
  $entityList = array();
  $sections = '' ;
  $infoText = '';
  $errors = '';

  foreach ($list as $postString => $on) {
    $postArray = explode('_', $postString);
    if ($postArray[0] == 'validate') {
      $metadata = new Metadata($postArray[1]);
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
          $errors .= sprintf('Problems with %s, skipping<br>', $metadata->entityID());
          print "Found error";
        }
      }
    }
  }
  if (sizeof($entityList)) {
    if ( $isIdP && $isSP) {
      $sections = HTML_TEXT_STP_BOTH ;
      $infoText = HTML_TEXT_STPINFO_BOTH;
    } elseif ($isIdP) {
      $sections = HTML_TEXT_STP_IDP ;
      $infoText = HTML_TEXT_STPINFO_IDP;
    } elseif ($isSP) {
      $sections = HTML_TEXT_STP_SP ;
      $infoText = HTML_TEXT_STPINFO_SP;
    }
    if (isset($_POST['entityIsOK'])) {
      $metadata->updateUser($EPPN, $mail, $fullName, true);
      $user_id = $metadata->getUserId($EPPN);
      foreach ($entityList as $id => $entityID) {
        $metadata = new Metadata($id);
        $metadata->confirmEntity($user_id);
      }
      $menuActive = 'myEntities';
      showMyEntities();
    } else {
      $errors .= isset($_POST['FormVisit'])
        ? "You must fulfill sections $sections in SWAMID SAML WebSSO Technology Profile.\n"
        : '';

      $html->showHeaders(HTML_TITLE . 'Validate list');
      $menuActive = '';
      showMenu();
      if ($errors != '') {
        printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
      }
      printf(
      '%s    <p>You are confirming that the list below is operational and fulfils SWAMID SAML WebSSO Technology Profile</p>
    <form action="?action=myEntities" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="FormVisit" value="true">
      <ul>', "\n");
      foreach ($entityList as $id => $entityID) {
        printf('      <li><input type="hidden" name="validate_%d" value="on">%s</li>%s', $id , $entityID, "\n");
      }
      printf('
      </ul>
      <h5> Confirmation:</h5>
      <p>Registration criteria from SWAMID SAML WebSSO Technology Profile:
        %s
      </p>
      <input type="checkbox" id="entityIsOK" name="entityIsOK">
      <label for="entityIsOK">I confirm that this Entity fulfils sections <b>%s</b> in <a href="http://www.swamid.se/policy/technology/saml-websso" target="_blank">SWAMID SAML WebSSO Technology Profile</a></label><br>
      <br>
      <input type="submit" name="action" value="Annual Confirmation">
    </form>
    <a href="/admin/?action=myEntities"><button>Return to My entities</button></a>%s',
        $infoText, $sections, "\n");
    }
  } else {
    $menuActive = 'myEntities';
    showMyEntities();
  }
}

function requestRemoval($entitiesId) {
  global $db, $html, $menuActive;
  global $EPPN, $mail, $fullName;
  global $mailContacts, $mailRequester, $SendOut;
  $metadata = new Metadata($entitiesId);
  if ($metadata->status() == 1) {
    $metadata->getUserId($EPPN);
    if ($metadata->isResponsible()) {
      # User have access to entity
      $html->showHeaders(HTML_TITLE . $metadata->entityID());
      if (isset($_GET['confirmRemoval'])) {
        $menuActive = 'publ';
        showMenu();
        $displayName = $metadata->entityDisplayName();

        setupMail();

        if ($SendOut) {
          $mailRequester->addAddress($mail);
        }

        $addresses = array();
        $contactHandler = $db->prepare(
          "SELECT DISTINCT emailAddress
          FROM ContactPerson
          WHERE entity_id = :Entity_ID AND (contactType='technical' OR contactType='administrative')");
        $contactHandler->bindParam(':Entity_ID',$entitiesId);
        $contactHandler->execute();
        while ($address = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
          if ($SendOut) {
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
              <p>%s (%s) has requested removal of the entity \"%s\" (%s) from the SWAMID metadata.</p>
              <p>You have received this email because you are either the technical and/or administrative contact.</p>
              <p>You can see the current metadata at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>
              <p>If you do not approve request please forward this email to SWAMID Operations (operations@swamid.se)
              and request for the removal to be denied.</p>
              <p>This is a message from the SWAMID SAML WebSSO metadata administration tool.<br>
              --<br>
              On behalf of SWAMID Operations</p>
            </body>
          </html>",
          $fullName, $mail, $displayName, $metadata->entityID(), $hostURL, $entitiesId, $hostURL, $entitiesId);
        $mailContacts->AltBody = sprintf("Hi.
          \n%s (%s) has requested removal of the entity \"%s\" (%s) from the SWAMID metadata.
          \nYou have received this email because you are either the technical and/or administrative contact.
          \nYou can see the current metadata at %s/?showEntity=%d
          \nIf you do not approve request please forward this email to SWAMID Operations (operations@swamid.se)
          and request for the removal to be denied.
          \nThis is a message from the SWAMID SAML WebSSO metadata administration tool.
          --
          On behalf of SWAMID Operations",
          $fullName, $mail, $displayName, $metadata->entityID(), $hostURL, $entitiesId);

        $mailRequester->isHTML(true);
        $mailRequester->Body   = sprintf("<!DOCTYPE html>
          <html lang=\"en\">
            <head>
              <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
            </head>
            <body>
              <p>Hi.</p>
              <p>You have requested removal of the entity \"%s\" (%s) from the SWAMID metadata.
              <p>Please forward this email to SWAMID Operations (operations@swamid.se).</p>
              <p>The current metadata can be found at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>
              <p>An email has also been sent to the following addresses since they are the technical
              and/or administrative contacts : </p>
              <p><ul>
              <li>%s</li>
              </ul>
              <p>This is a message from the SWAMID SAML WebSSO metadata administration tool.<br>
              --<br>
              On behalf of SWAMID Operations</p>
            </body>
          </html>",
          $displayName, $metadata->entityID(),
          $hostURL, $entitiesId,
          $hostURL, $entitiesId,implode ("</li>\n<li>",$addresses));
        $mailRequester->AltBody = sprintf("Hi.
          \nYou have requested removal of the entity \"%s\" (%s) from the SWAMID metadata.
          \nPlease forward this email to SWAMID Operations (operations@swamid.se).
          \nThe current metadata can be found at %s/?showEntity=%d
          \nAn email has also been sent to the following addresses since they are the technical
          and/or administrative contacts : %s
          \nThis is a message from the SWAMID SAML WebSSO metadata administration tool.
          --
          On behalf of SWAMID Operations",
          $displayName, $metadata->entityID(), $hostURL, $entitiesId, implode (", ",$addresses));

        $shortEntityid = preg_replace(REGEXP_ENTITYID, '$1', $metadata->entityID());
        $mailContacts->Subject  = 'Info : Request to remove SWAMID metadata for ' . $shortEntityid;
        $mailRequester->Subject = 'Request to remove SWAMID metadata for ' . $shortEntityid;

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
      } else {
        $menuActive = 'publ';
        showMenu();
        printf('%s    <p>You are about to request removal of the entity with the entityID <b>%s</b> from the SWAMID metadata.</p>', "\n", $metadata->entityID());
        if (($metadata->feedValue() & 2) == 2) { $publishArray[] = 'SWAMID'; }
        if (($metadata->feedValue() & 4) == 4) { $publishArray[] = 'eduGAIN'; }
        printf('%s    <p>Currently published in <b>%s</b></p>%s', "\n", implode (' and ', $publishArray), "\n");
        printf('    <form>%s      <input type="hidden" name="Entity" value="%d">%s      <input type="checkbox" id="confirmRemoval" name="confirmRemoval">%s      <label for="confirmRemoval">I confirm that this Entity should be removed</label><br>%s      <br>%s      <input type="submit" name="action" value="Request removal">%s    </form>%s    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', "\n", $entitiesId, "\n", "\n", "\n", "\n", "\n", "\n" ,$entitiesId);
      }
    } else {
      # User have no access yet.
      requestAccess($entitiesId);
    }
  } else {
    $html->showHeaders(HTML_TITLE . 'NotFound');
    $menuActive = 'publ';
    showMenu();
    print HTML_TEXT_CFE;
  }
  print "\n";
}

function setupMail() {
  global $mailContacts, $mailRequester;
  global $SMTPHost, $SASLUser, $SASLPassword, $MailFrom;

  $mailContacts = new PHPMailer(true);
  $mailRequester = new PHPMailer(true);
  $mailContacts->isSMTP();
  $mailRequester->isSMTP();
  $mailContacts->CharSet = "UTF-8";
  $mailRequester->CharSet = "UTF-8";
  $mailContacts->Host = $SMTPHost;
  $mailRequester->Host = $SMTPHost;
  $mailContacts->SMTPAuth = true;
  $mailRequester->SMTPAuth = true;
  $mailContacts->SMTPAutoTLS = true;
  $mailRequester->SMTPAutoTLS = true;
  $mailContacts->Port = 587;
  $mailRequester->Port = 587;
  $mailContacts->SMTPAuth = true;
  $mailRequester->SMTPAuth = true;
  $mailContacts->Username = $SASLUser;
  $mailRequester->Username = $SASLUser;
  $mailContacts->Password = $SASLPassword;
  $mailRequester->Password = $SASLPassword;
  $mailContacts->SMTPSecure = 'tls';
  $mailRequester->SMTPSecure = 'tls';

  //Recipients
  $mailContacts->setFrom($MailFrom, 'Metadata - Admin');
  $mailRequester->setFrom($MailFrom, 'Metadata - Admin');
  $mailContacts->addBCC('bjorn@sunet.se');
  $mailRequester->addBCC('bjorn@sunet.se');
  $mailContacts->addReplyTo(OPERATIONS_MAIL, OPERATIONS_NAME);
  $mailRequester->addReplyTo(OPERATIONS_MAIL, OPERATIONS_NAME);
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
      $draftMetadata->clearWarning();
      $draftMetadata->clearError();
      $draftMetadata->validateXML();
      $draftMetadata->validateSAML(true);
      $menuActive = 'new';
      $draftMetadata->copyResponsible($entitiesId);
      showEntity($draftMetadata->id());
      $oldMetadata = new Metadata($entitiesId);
      $oldMetadata->removeEntity();
    } else {
      $html->showHeaders(HTML_TITLE . $entity['entityID']);
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
    $html->showHeaders(HTML_TITLE . 'NotFound');
    $menuActive = 'wait';
    showMenu();
    print HTML_TEXT_CFE;
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
    $html->showHeaders(HTML_TITLE . $entity['entityID']);
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
    $html->showHeaders(HTML_TITLE . 'NotFound');
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
  $metadata = new Metadata($entitiesId);
  $metadata->getUser($userID);
  if ($metadata->isResponsible()) {
    return true;
  } else {
    if ($showError) {
      $html->showHeaders(HTML_TITLE);
      print "You doesn't have access to this entityID";
      printf('%s      <a href=".?showEntity=%d"><button type="button" class="btn btn-outline-danger">Back to entity</button></a>', "\n", $entitiesId);
    }
    return false;
  }
}

# Request access to an entity
function requestAccess($entitiesId) {
  global $html, $menuActive;
  global $EPPN, $mail, $fullName, $userLevel;
  global $mailContacts, $mailRequester, $SendOut;

  $metadata = new Metadata($entitiesId);
  if ($metadata->entityExists()) {
    $user_id = $metadata->getUserId($EPPN);
    if ($metadata->isResponsible()) {
      # User already have access.
      $html->showHeaders(HTML_TITLE . $metadata->entityID());
      $menuActive = '';
      showMenu();
      printf('%s    <p>You already have access to <b>%s</b></p>%s', "\n", $metadata->entityID(), "\n");
      printf('    <a href="./?showEntity=%d"><button>Return to Entity</button></a>%s', $entitiesId, "\n");
    } else {
      $errors = '';
      $addresses = $metadata->getTechnicalAndAdministrativeContacts();

      if (isset($_GET['requestAccess'])) {
        # We are committing from the Form.
        # Fetch user_id again and make sure user exists
        $user_id = $metadata->getUserId($EPPN, $mail, $fullName, true);
        # Get code to send in email
        $requestCode = urlencode($metadata->createAccessRequest($user_id));
        setupMail();
        if ($SendOut) {
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
          <p>This is a message from the SWAMID SAML WebSSO metadata administration tool.<br>
          --<br>
          On behalf of SWAMID Operations</p>
          </body>\n</html>",
          $fullName, $mail, $metadata->entityID(), $hostURL, $requestCode, $hostURL, $requestCode);
        $mailContacts->AltBody = sprintf("Hi.
          \n%s (%s) has requested access to update %s
          \nYou have received this email because you are either the technical and/or administrative contact.
          \nIf you approve, please click on this link %s/admin/?approveAccessRequest=%s
          \nIf you do not approve, you can ignore this email. No changes will be made.
          \nThis is a message from the SWAMID SAML WebSSO metadata administration tool.
          --
          On behalf of SWAMID Operations",
        $fullName, $mail, $metadata->entityID(), $hostURL, $requestCode);
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
        $errors .= isset($_GET['FormVisit']) ? "You must check the box to confirm.\n" : '';
        $html->showHeaders(HTML_TITLE . $metadata->entityID());
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
          $entitiesId, implode (", ",$addresses), $entitiesId, "\n");
        if ($userLevel > 19) {
          printf('    <br><a href="./?action=forceAccess&Entity=%d"><button>Force access to Entity</button></a>%s', $entitiesId, "\n");
        }
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

  $entityHandler = $db->prepare('SELECT `entityID`, `errors`, `errorsNB` FROM Entities WHERE `id` = :Id;');
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
  $html->showHeaders(HTML_TITLE);
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
          $mail->addReplyTo(OPERATIONS_MAIL, OPERATIONS_NAME);
          $mail->addAddress($result['email']);
          $mail->Body = sprintf("<!DOCTYPE html>
            <html lang=\"en\">
              <head>
                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
              </head>
              <body>
                <p>Hi.</p>
                <p>Your access to %s have been granted.</p>
                <p>-- <br>This mail was sent by SWAMID Metadata Admin Tool, a service provided by SWAMID Operations.
                If you've any questions please contact operations@swamid.se.</p>
              </body>
            </html>",
            $metadata->entityID());
          $mail->AltBody = sprintf("Hi.
            \nYour access to %s have been granted.
            \n--
            This mail was sent by SWAMID Metadata Admin Tool, a service provided by SWAMID Operations.
            If you've any questions please contact operations@swamid.se.",
            $metadata->entityID());
          $shortEntityid = preg_replace(REGEXP_ENTITYID, '$1', $metadata->entityID());
          $mail->Subject = 'Access granted for ' . $shortEntityid;

          $info = sprintf('<h3>Access granted</h3>Access to <b>%s</b> added for %s (%s).',
            $metadata->entityID(), $result['fullName'], $result['email']);

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
    $html->showHeaders(HTML_TITLE . 'Error');
  } else {
    $html->showHeaders(HTML_TITLE . 'Info');
  }
  if ($showMenu) { showMenu(); }
  printf ('    <div class="row">%s      <div class="col">%s        %s%s      </div>%s    </div>%s',
    "\n", "\n", $text, "\n", "\n", "\n");
}
