<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load composer's autoloader
require '../vendor/autoload.php';

$baseDir = dirname($_SERVER['SCRIPT_FILENAME'], 2);
include $baseDir . '/config.php' ;

include '../include/Html.php';
$html = new HTML('', $Mode);

$errorURL = isset($_SERVER['Meta-errorURL']) ? '<a href="' . $_SERVER['Meta-errorURL'] . '">Mer information</a><br>' : '<br>';
$errorURL = str_replace(array('ERRORURL_TS', 'ERRORURL_RP', 'ERRORURL_TID'), array(time(), 'https://metadata.swamid.se/shibboleth', $_SERVER['Shib-Session-ID']), $errorURL);

$errors = '';
$filterFirst = true;

if (isset($_SERVER['Meta-Assurance-Certification'])) {
	$AssuranceCertificationFound = false;
	foreach (explode(';',$_SERVER['Meta-Assurance-Certification']) as $AssuranceCertification) {
		if ($AssuranceCertification == 'http://www.swamid.se/policy/assurance/al1')
			$AssuranceCertificationFound = true;
	}
	if (! $AssuranceCertificationFound) {
		$errors .= sprintf('%s has no AssuranceCertification (http://www.swamid.se/policy/assurance/al1) ', $_SERVER['Shib-Identity-Provider']);
	}
}

if (isset($_SERVER['eduPersonPrincipalName'])) {
	$EPPN = $_SERVER['eduPersonPrincipalName'];
} elseif (isset($_SERVER['subject-id'])) {
	$EPPN = $_SERVER['subject-id'];
} else {
	$errors .= 'Missing eduPersonPrincipalName/subject-id in SAML response ' . str_replace(array('ERRORURL_CODE', 'ERRORURL_CTX'), array('IDENTIFICATION_FAILURE', 'eduPersonPrincipalName'), $errorURL);
}

if (isset($_SERVER['eduPersonScopedAffiliation'])) {
	$foundEmployee = false;
	foreach (explode(';',$_SERVER['eduPersonScopedAffiliation']) as $ScopedAffiliation) {
		if (explode('@',$ScopedAffiliation)[0] == 'employee')
			$foundEmployee = true;
	}
	if (! $foundEmployee) {
		$errors .= sprintf('Did not find employee in eduPersonScopedAffiliation. Only got %s<br>', $_SERVER['eduPersonScopedAffiliation']);
	}
} elseif (isset($_SERVER['eduPersonAffiliation'])) {
	$foundEmployee = false;
	foreach (explode(';',$_SERVER['eduPersonAffiliation']) as $Affiliation) {
		if ($Affiliation == 'employee')
			$foundEmployee = true;
	}
	if (! $foundEmployee) {
		$errors .= sprintf('Did not find employee in eduPersonAffiliation. Only got %s<br>', $_SERVER['eduPersonAffiliation']);
	}
} else {
	if (isset($_SERVER['Shib-Identity-Provider'])) {
		switch ($_SERVER['Shib-Identity-Provider']) {
			case 'https://login.idp.eduid.se/idp.xml' :
				#OK to not send eduPersonScopedAffiliation / eduPersonAffiliation
				$filterFirst = false;
				break;
			default :
				$errors .= 'Missing eduPersonScopedAffiliation and eduPersonAffiliation in SAML response<br>One of them is required<br>';
		}

	} else $errors .= 'Missing eduPersonScopedAffiliation and eduPersonAffiliation in SAML response<br>One of them is required<br>';
}

if ( isset($_SERVER['mail'])) {
	$mailArray = explode(';',$_SERVER['mail']);
	$mail = $mailArray[0];
} else {
	$errors .= 'Missing mail in SAML response ' . str_replace(array('ERRORURL_CODE', 'ERRORURL_CTX'), array('IDENTIFICATION_FAILURE', 'mail'), $errorURL);
}

if (isset($_SERVER['displayName'])) {
	$fullName = $_SERVER['displayName'];
} elseif (isset($_SERVER['givenName'])) {
	$fullName = $_SERVER['givenName'];
	if(isset($_SERVER['sn']))
		$fullName .= ' ' .$_SERVER['sn'];
} else
	$fullName = '';

if ($errors != '') {
	$html->showHeaders('Metadata SWAMID - Problem');
	printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <b>Errors:</b><br>%s        %s%s      </div>%s    </div>%s', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n","\n");
	$html->showFooter(array());
	exit;
}

switch ($EPPN) {
	case 'bjorn@sunet.se' :
	case 'jocar@sunet.se' :
		$userLevel = 20;
		break;
	case 'frkand02@umu.se' :
	case 'paulscot@kau.se' :
		$userLevel = 10;
		break;
	case 'ldc-esw@lu.se' :
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

include '../include/MetadataDisplay.php';
$display = new MetadataDisplay($baseDir);

include '../include/Metadata.php';

if (isset($_FILES['XMLfile'])) {
	importXML();
} elseif (isset($_GET['edit'])) {
	if (isset($_GET['Entity']) && (isset($_GET['oldEntity']))) {
		include '../include/MetadataEdit.php';
		$editMeta = new MetadataEdit($baseDir, $_GET['Entity'], $_GET['oldEntity']);
		$editMeta->updateUser($EPPN, $mail, $fullName);
		if (checkAccess($_GET['Entity'], $EPPN, $userLevel, 10, true)) {
			$html->showHeaders('Metadata SWAMID - Edit - '.$_GET['edit']);
			$editMeta->edit($_GET['edit']);
		}
	} else
		showEntityList();
} elseif (isset($_GET['showEntity'])) {
	showEntity($_GET['showEntity']);
} elseif (isset($_GET['validateEntity'])) {
	validateEntity($_GET['validateEntity']);
	showEntity($_GET['validateEntity']);
} elseif (isset($_GET['move2Pending'])) {
	if (checkAccess($_GET['move2Pending'], $EPPN, $userLevel, 10, true))
		move2Pending($_GET['move2Pending']);
} elseif (isset($_GET['move2Draft'])) {
	if (checkAccess($_GET['move2Draft'], $EPPN, $userLevel, 10, true))
		move2Draft($_GET['move2Draft']);
} elseif (isset($_GET['mergeEntity'])) {
	if (checkAccess($_GET['mergeEntity'],$EPPN,$userLevel,10,true)) {
		if (isset($_GET['oldEntity'])) {
			mergeEntity($_GET['mergeEntity'], $_GET['oldEntity']);
			validateEntity($_GET['mergeEntity']);
		}
		showEntity($_GET['mergeEntity']);
	}
} elseif (isset($_GET['removeEntity'])) {
	if (checkAccess($_GET['removeEntity'],$EPPN,$userLevel,10, true))
		removeEntity($_GET['removeEntity']);
} elseif (isset($_GET['removeSSO']) && isset($_GET['type'])) {
	if (checkAccess($_GET['removeSSO'],$EPPN,$userLevel,10, true))
		removeSSO($_GET['removeSSO'], $_GET['type']);
} elseif (isset($_GET['removeKey']) && isset($_GET['type']) && isset($_GET['use']) && isset($_GET['serialNumber'])) {
	if (checkAccess($_GET['removeKey'],$EPPN,$userLevel,10, true))
		removeKey($_GET['removeKey'], $_GET['type'], $_GET['use'], $_GET['serialNumber']);
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
			$Entity_id = $_GET['Entity'];
			switch($_GET['action']) {
				case 'createDraft' :
					$menuActive = 'new';
					$metadata = new Metadata($baseDir, $Entity_id);
					$user_id = $metadata->getUserId($EPPN);
					if ($metadata->isResponsible()) {
						if ($newEntity_id = $metadata->createDraft()) {
							$metadata->validateXML();
							$metadata->validateSAML();
							$metadata->copyResponsible($Entity_id);
							$menuActive = 'new';
							showEntity($newEntity_id);
						}
					} else {
						# User have no access yet.
						requestAccess($Entity_id);
					}
					break;
				case 'Request removal' :
					requestRemoval($Entity_id);
					break;
				case 'Annual Confirmation' :
					annualConfirmation($Entity_id);
					break;
				case 'Request Access' :
					requestAccess($Entity_id);
					break;
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
							$metadata = new Metadata($baseDir);
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
								$metadata = new Metadata($baseDir);
								$metadata->revalidateURL($_GET['URL']);
							}
							$display->showURLStatus($_GET['URL']);
						} else
							$display->showURLStatus();
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

	$showAll = true;

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
		$sort = 'feedDesc';
		$feedOrder = 'feedAsc';
		$feedArrow = '<i class="fa fa-arrow-up"></i>';
	} elseif (isset($_GET['feedAsc'])) {
		$sortOrder = '`publishIn` ASC, `entityID`';
		$sort = 'feedAsc';
		$feedArrow = '<i class="fa fa-arrow-down"></i>';
	} elseif (isset($_GET['orgDesc'])) {
		$sortOrder = '`OrganizationName` DESC, `entityID`';
		$sort = 'orgDesc';
		$orgArrow = '<i class="fa fa-arrow-up"></i>';
	} elseif (isset($_GET['orgAsc'])) {
		$sortOrder = '`OrganizationName` ASC, `entityID`';
		$sort = 'orgAsc';
		$orgOrder = 'orgDesc';
		$orgArrow = '<i class="fa fa-arrow-down"></i>';
	} elseif (isset($_GET['validationOutput'])) {
		$sortOrder = '`validationOutput` DESC, `entityID`, `id`';
		$validationArrow = '<i class="fa fa-arrow-down"></i>';
	} elseif (isset($_GET['warnings'])) {
		$sortOrder = '`warnings` DESC, `errors` DESC, `errorsNB` DESC, `entityID`, `id`';
		$warningArrow = '<i class="fa fa-arrow-down"></i>';
	} elseif (isset($_GET['errors'])) {
		$sortOrder = '`errors` DESC, `errorsNB` DESC, `warnings` DESC, `entityID`, `id`';
		$errorArrow = '<i class="fa fa-arrow-down"></i>';
	} elseif (isset($_GET['entityIDDesc'])) {
		$sortOrder = '`entityID` DESC';
		$sort = 'entityIDDesc';
		$entityIDOrder = 'entityIDAsc';
		$entityIDArrow = '<i class="fa fa-arrow-up"></i>';
	} else {
		$sortOrder = '`entityID` ASC';
		$sort = 'entityIDAsc';
		$entityIDOrder = 'entityIDDesc';
		$entityIDArrow = '<i class="fa fa-arrow-down"></i>';
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
	if (isset($_GET['action']))
		$filter .= '&action='.$_GET['action'];
	$entitys = $db->prepare("SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn`, `data` AS OrganizationName, `lastUpdated`, `lastValidated`, `validationOutput`, `warnings`, `errors`, `errorsNB` FROM Entities LEFT JOIN Organization ON Organization.entity_id = id AND element = 'OrganizationName' AND lang = 'en' WHERE status = $status AND entityID LIKE :Query ORDER BY $sortOrder");
	$entitys->bindValue(':Query', "%".$query."%");

	print '
    <table class="table table-striped table-bordered">
      <tr>
	  	<th>IdP</th><th>SP</th>';

	printf('<th>Registered in</th> <th><a href="?%s&%s">eduGAIN%s</a></th> <th><form><a href="?%s&%s">entityID%s</a> <input type="text" name="query" value="%s"><input type="hidden" name="action" value="%s"><input type="submit" value="Filter"></form></th><th><a href="?%s&%s">OrganizationName%s</a></th><th>%s</th><th>lastValidated (UTC)</th><th><a href="?%s&validationOutput">validationOutput%s</a></th><th><a href="?%s&warnings">warning%s</a> / <a href="?%s&errors">errors%s</a></th></tr>%s', $filter, $feedOrder, $feedArrow, $filter, $entityIDOrder, $entityIDArrow, $query, $action, $filter, $orgOrder, $orgArrow, ($status == 1) ? 'lastUpdated (UTC)' : 'created (UTC)' , $filter, $validationArrow, $filter, $warningArrow, $filter, $errorArrow, "\n");
	showList($entitys, $minLevel);
}

####
# Shows Entity information
####
function showEntity($Entity_id)  {
	global $db, $html, $display, $userLevel, $menuActive, $EPPN;
	$entityHandler = $db->prepare('SELECT `entityID`, `isIdP`, `isSP`, `publishIn`, `status`, `publishedId` FROM Entities WHERE `id` = :Id;');
	$publishArray = array();
	$publishArrayOld = array();
	$allowEdit = false;

	$entityHandler->bindParam(':Id', $Entity_id);
	$entityHandler->execute();
	if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
		if (($entity['publishIn'] & 2) == 2) $publishArray[] = 'SWAMID';
		if (($entity['publishIn'] & 4) == 4) $publishArray[] = 'eduGAIN';
		if (($entity['publishIn'] & 1) == 1) $publishArray[] = 'SWAMID-testing';
		if ($entity['status'] > 1 && $entity['status'] < 6) {
			if ($entity['publishedId'] > 0) {
				$entityHandlerOld = $db->prepare('SELECT `id`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE `id` = :Id AND `status` = 6;');
				$entityHandlerOld->bindParam(':Id', $entity['publishedId']);
				$headerCol2 = 'Old metadata - when requested publication';
			} else {
				$entityHandlerOld = $db->prepare('SELECT `id`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE `entityID` = :Id AND `status` = 1;');
				$entityHandlerOld->bindParam(':Id', $entity['entityID']);
				$headerCol2 = 'Old metadata - published now';
			}
			$entityHandlerOld->execute();
			if ($entityOld = $entityHandlerOld->fetch(PDO::FETCH_ASSOC)) {
				$oldEntity_id = $entityOld['id'];
				if (($entityOld['publishIn'] & 2) == 2) $publishArrayOld[] = 'SWAMID';
				if (($entityOld['publishIn'] & 4) == 4) $publishArrayOld[] = 'eduGAIN';
				if (($entityOld['publishIn'] & 1) == 1) $publishArrayOld[] = 'SWAMID-testing';
			} else {
				$oldEntity_id = 0;
			}
			switch ($entity['status']) {
				case 3:
					# Draft
					$headerCol1 = 'New metadata';
					$menuActive = 'new';
					$allowEdit = checkAccess($Entity_id, $EPPN, $userLevel, 10, false);
					break;
				case 4:
					# Soft Delete
					$headerCol1 = 'Deleted metadata';
					$menuActive = 'publ';
					$allowEdit = false;
					$oldEntity_id = 0;
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
					$allowEdit = checkAccess($Entity_id, false, $userLevel, 10, false);
			}
		} else {
			$headerCol1 = 'Published metadata';
			$menuActive = 'publ';
			$oldEntity_id = 0;
		}
		$html->showHeaders('Metadata SWAMID - ' . $entity['entityID']);
		showMenu();?>
    <div class="row">
      <div class="col">
        <h3>entityID = <?=$entity['entityID']?></h3>
      </div>
    </div><?php
		$display->showStatusbar($Entity_id, $userLevel > 4 ? true : false);
		print "\n" . '    <div class="row">';
		switch ($entity['status']) {
			case 1:
				printf('%s      <a href=".?action=Annual+Confirmation&Entity=%d"><button type="button" class="btn btn-outline-%s">Annual Confirmation</button></a>', "\n", $Entity_id, getErrors($Entity_id) == '' ? 'success' : 'danger');
				printf('%s      <a href=".?action=createDraft&Entity=%d"><button type="button" class="btn btn-outline-primary">Create draft</button></a>', "\n", $Entity_id);
				printf('%s      <a href=".?action=Request+removal&Entity=%d"><button type="button" class="btn btn-outline-danger">Request removal</button></a>', "\n", $Entity_id);
				break;
			case 2:
				if (checkAccess($Entity_id, false, $userLevel, 10, false)) {
					printf('%s      <a href=".?removeEntity=%d"><button type="button" class="btn btn-outline-danger">Delete Pending</button></a>', "\n", $Entity_id);
				}
				if (checkAccess($Entity_id, $EPPN, $userLevel, 10, false)) {
						printf('%s      <a href=".?move2Draft=%d"><button type="button" class="btn btn-outline-danger">Cancel publication request</button></a>', "\n", $Entity_id);
				}
				break;
			case 3:
				if (checkAccess($Entity_id, $EPPN, $userLevel, 10, false)) {
					$errors = getBlockingErrors($Entity_id);
					printf('%s      <a href=".?move2Pending=%d"><button type="button" class="btn btn-outline-%s">Request publication</button></a>', "\n", $Entity_id, getBlockingErrors($Entity_id) == '' ? 'success' : 'danger' );
					printf('%s      <a href=".?removeEntity=%d"><button type="button" class="btn btn-outline-danger">Discard Draft</button></a>', "\n", $Entity_id);
					print "\n      <br>";
					if ($oldEntity_id > 0) {
						printf('%s      <a href=".?mergeEntity=%d&oldEntity=%d"><button type="button" class="btn btn-outline-primary">Merge from published</button></a>', "\n", $Entity_id, $oldEntity_id);
					}
					printf ('%s      <form>%s        <input type="hidden" name="mergeEntity" value="%d">%s        Merge from other entity : %s        <select name="oldEntity">', "\n", "\n", $Entity_id, "\n", "\n");
					if ($entity['isIdP'] ) {
						if ($entity['isSP'] ) {
							// is both SP and IdP
							$mergeEntityHandler = $db->prepare('SELECT id, entityID FROM Entities WHERE status = 1 ORDER BY entityID;');
						} else {
							// isIdP only
							$mergeEntityHandler = $db->prepare('SELECT id, entityID FROM Entities WHERE status = 1 AND isIdP = 1 ORDER BY entityID;');
						}
					} else {
						// isSP only
						$mergeEntityHandler = $db->prepare('SELECT id, entityID FROM Entities WHERE status = 1 AND isSP = 1 ORDER BY entityID;');
					}
					$mergeEntitys = $mergeEntityHandler->execute();
					while ($mergeEntity = $mergeEntityHandler->fetch(PDO::FETCH_ASSOC)) {
						printf('%s          <option value="%d">%s</option>', "\n", $mergeEntity['id'], $mergeEntity['entityID']);
					}
					printf ('%s        </select>%s        <button type="submit">Merge</button>%s      </form>', "\n", "\n", "\n");
				}
				break;
		}

		 ?>

    </div>
    <div class="row">
      <div class="col">
        <h3><?=$headerCol1?></h3>
        Published in : <?php
		print (implode (', ', $publishArray));
		if ($oldEntity_id > 0) { ?>

      </div>
      <div class="col">
        <h3><?=$headerCol2?></h3>
        Published in : <?php
			print (implode (', ', $publishArrayOld));
		} ?>

      </div>
    </div>
    <br><?php
		$display->showEntityAttributes($Entity_id, $oldEntity_id, $allowEdit);
		$able2beRemoveSSO = ($entity['isIdP'] && $entity['isSP'] && $allowEdit);
		if ($entity['isIdP'] ) $display->showIdP($Entity_id, $oldEntity_id, $allowEdit, $able2beRemoveSSO);
		if ($entity['isSP'] ) $display->showSp($Entity_id, $oldEntity_id, $allowEdit, $able2beRemoveSSO);
		$display->showOrganization($Entity_id, $oldEntity_id, $allowEdit);
		$display->showContacts($Entity_id, $oldEntity_id, $allowEdit);
		$display->showXML($Entity_id);
		$display->showEditors($Entity_id);

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
			printf ('      <tr>');
			print $row['isIdP'] ? '<td class="text-center">X</td>' : '<td></td>';
			print $row['isSP'] ? '<td class="text-center">X</td>' : '<td></td>';

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
			printf ('<td class="text-center">%s</td><td class="text-center">%s</td><td><a href="?showEntity=%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>', $registerdIn, $export2Edugain, $row['id'], $row['entityID'], $row['OrganizationName'], $row['lastUpdated'], $row['lastValidated'], $validationOutput, $validationStatus);
			print "</tr>\n";
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
		printf ('    <div class="row">%s      <div class="col">%s        <a href=".?action=myEntities&%s"><button type="button" class="btn btn-outline-success">Show %s</button></a>%s      </div>%s    </div>%s', "\n", "\n", isset($_GET['showAll']) ? 'showMy' : 'showAll', isset($_GET['showAll']) ? 'My' : 'All',"\n", "\n", "\n");
    }
	if (isset($_GET['showAll']) && $userLevel > 9) {
		$entitysHandler = $db->prepare("SELECT Entities.`id`, `entityID`, `errors`, `errorsNB`, `warnings`, `status` FROM Entities WHERE `status` = 1 ORDER BY `entityID`");
	} else {
		$entitysHandler = $db->prepare("SELECT Entities.`id`, `entityID`, `errors`, `errorsNB`, `warnings`, `status` FROM Users, EntityUser, Entities WHERE EntityUser.`entity_id` = Entities.`id` AND EntityUser.`user_id` = Users.`id` AND `status` < 4 AND `userID` = :UserID ORDER BY `entityID`, `status`");
		$entitysHandler->bindValue(':UserID', $EPPN);
	}
	$entityConfirmationHandler = $db->prepare("SELECT `lastConfirmed`, `fullName`, `email`, NOW() - INTERVAL 10 MONTH AS `warnDate`, NOW() - INTERVAL 12 MONTH AS 'errorDate' FROM Users, EntityConfirmation WHERE `user_id`= `id` AND `entity_id` = :Id");

	printf ('%s    <table id="annual-table" class="table table-striped table-bordered">%s      <thead><tr>%s        <th>entityID</th><th>Metadata status</th><th>Last confirmed(UTC)</th><th>By</th>%s      </tr></thead>%s', "\n", "\n", "\n", "\n", "\n");
	$entitysHandler->execute();
	while ($row = $entitysHandler->fetch(PDO::FETCH_ASSOC)) {
		$entityConfirmationHandler->bindParam(':Id', $row['id']);
		$entityConfirmationHandler->execute();
		if ($entityConfirmation = $entityConfirmationHandler->fetch(PDO::FETCH_ASSOC)) {
			$lastConfirmed = $entityConfirmation['lastConfirmed'];
			$updater = $entityConfirmation['fullName'] . ' (' . $entityConfirmation['email'] . ')';
			$confirmStatus = ($entityConfirmation['warnDate'] > $entityConfirmation['lastConfirmed']) ? ($entityConfirmation['errorDate'] > $entityConfirmation['lastConfirmed']) ? ' <i class="fa-regular fa-bell"></i>' : ' <i class="fa-regular fa-clock"></i>' : '';
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
		}
		$errorStatus = ($row['errors'] == '' && $row['errorsNB'] == '') ? ($row['warnings'] == '') ? '<i class="fas fa-check"></i>' : '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-exclamation"></i>';
		printf('      <tr><td><a href="?showEntity=%d">%s</a></td><td>%s (%s)</td><td>%s%s</td><td>%s</td></tr>%s', $row['id'], $row['entityID'], $errorStatus, $pubStatus, $lastConfirmed, $confirmStatus, $updater, "\n");
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
	global $html, $baseDir;
	global $EPPN,$mail, $fullName;

	include '../include/NormalizeXML.php';
	include '../include/ValidateXML.php';
	include '../include/MetadataEdit.php';
	$import = new NormalizeXML();
	$import->fromFile($_FILES['XMLfile']['tmp_name']);
	if ($import->getStatus()) {
		$entityID = $import->getEntityID();
		$validate = new ValidateXML($import->getXML());
		if ($validate->validateXML($baseDir . '/../schemas/schema.xsd')) {
			$metadata = new Metadata($baseDir, $entityID, 'New');
			$metadata->importXML($import->getXML());
			$metadata->getUser($EPPN, $mail, $fullName, true);
			$metadata->updateResponsible($EPPN);
			$metadata->validateXML(true);
			$metadata->validateSAML(true);

			$prodmetadata = new Metadata($baseDir, $entityID, 'Prod');
			if ($prodmetadata->EntityExists()) {
				$editMetadata = new MetadataEdit($baseDir, $metadata->ID(), $prodmetadata->ID());
				$editMetadata->mergeRegistrationInfo();
				$editMetadata->saveXML();
			}
			showEntity($metadata->ID());
		} else {
			$html->showHeaders('Metadata SWAMID - Problem');
			printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <b>Error in XML-syntax:</b>%s        %s%s      </div>%s    </div>%s', "\n", "\n", "\n", "\n", $validate->getError(), "\n", "\n","\n");
			$html->showFooter(array());
		}
	} else {
		$html->showHeaders('Metadata SWAMID - Problem');
		printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <b>Error in XML-file:</b>%s        %s%s      </div>%s    </div>%s', "\n", "\n", "\n", "\n", $import->getError(), "\n", "\n","\n");
		$html->showFooter(array());
	}
}

####
# Remove an IDPSSO / SPSSO Decriptor that isn't used
####
function removeSSO($Entity_id, $type) {
	global $baseDir;
	include '../include/MetadataEdit.php';
	$metadata = new MetadataEdit($baseDir, $Entity_id);
	$metadata->removeSSO($type);
	validateEntity($Entity_id);
	showEntity($Entity_id);
}

####
# Remove an IDPSSO / SPSSO Key that is old & unused
####
function removeKey($Entity_id, $type, $use, $serialNumber) {
	global $baseDir;
	include '../include/MetadataEdit.php';
	$metadata = new MetadataEdit($baseDir, $Entity_id);
	$metadata->removeKey($type, $use, $serialNumber);
	validateEntity($Entity_id);
	showEntity($Entity_id);
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
	printf('<a href=".?action=myEntities%s"><button type="button" class="btn btn%s-primary">My entities</button></a>', $filter, $menuActive == 'myEntities' ? '' : '-outline');
	printf('<a href=".?action=pub%s"><button type="button" class="btn btn%s-primary">Published</button></a>', $filter, $menuActive == 'publ' ? '' : '-outline');
	printf('<a href=".?action=new%s"><button type="button" class="btn btn%s-primary">Drafts</button></a>', $filter, $menuActive == 'new' ? '' : '-outline');
	printf('<a href=".?action=wait%s"><button type="button" class="btn btn%s-primary">Pending</button></a>', $filter, $menuActive == 'wait' ? '' : '-outline');
	printf('<a href=".?action=upload%s"><button type="button" class="btn btn%s-primary">Upload new XML</button></a>', $filter, $menuActive == 'upload' ? '' : '-outline');
	printf('<a href=".?action=EntityStatistics%s"><button type="button" class="btn btn%s-primary">Entity Statistics</button></a>', $filter, $menuActive == 'EntityStatistics' ? '' : '-outline');
	printf('<a href=".?action=EcsStatistics%s"><button type="button" class="btn btn%s-primary">ECS statistics</button></a>', $filter, $menuActive == 'EcsStatistics' ? '' : '-outline');
	printf('<a href=".?action=ErrorList%s"><button type="button" class="btn btn%s-primary">Errors</button></a>', $filter, $menuActive == 'Errors' ? '' : '-outline');
	if ( $userLevel > 4 ) {
		printf('<a href=".?action=URLlist%s"><button type="button" class="btn btn%s-primary">URLlist</button></a>', $filter, $menuActive == 'URLlist' ? '' : '-outline');
	}
	if ( $userLevel > 10 ) {
		printf('<a href=".?action=CleanPending%s"><button type="button" class="btn btn%s-primary">Clean Pending</button></a>', $filter, $menuActive == 'CleanPending' ? '' : '-outline');
	}
	print "\n    <br>\n    <br>\n";
}

function validateEntity($Entity_id) {
	global $baseDir;
	$metadata = new Metadata($baseDir, $Entity_id);
	$metadata->validateXML(true);
	$metadata->validateSAML();
}

function move2Pending($Entity_id) {
	global $db, $html, $display, $userLevel, $menuActive, $baseDir;
	global $EPPN, $mail, $fullName;
	global $mailContacts, $mailRequetser, $SendOut;

	$draftMetadata = new Metadata($baseDir, $Entity_id);

	if ($draftMetadata->EntityExists()) {
		if ( $draftMetadata->isIdP() && $draftMetadata->isSP()) {
			$sections = '4.1.1, 4.1.2, 4.2.1 and 4.2.2' ;
		} elseif ($draftMetadata->isIdP()) {
			$sections = '4.1.1 and 4.1.2' ;
		} elseif ($draftMetadata->isSP()) {
			$sections = '4.2.1 and 4.2.2' ;
		}
		$html->showHeaders('Metadata SWAMID - ' . $draftMetadata->EntityID());
		$errors = getBlockingErrors($Entity_id);
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
			} else
				$publish = false;

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
				$mailContacts->Body		= sprintf("<p>Hi.</p>\n<p>%s (%s, %s) has requested an update of %s</p>\n<p>You have received this mail because you are either the new or old technical and/or administrative contact.</p>\n<p>You can see the new version at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>\n<p>If you do not approve this update please forward this mail to SWAMID Operations (operations@swamid.se) and request for the update to be denied.</p>", $EPPN, $fullName, $mail, $draftMetadata->EntityID(), $hostURL, $Entity_id, $hostURL, $Entity_id);
				$mailContacts->AltBody	= sprintf("Hi.\n\n%s (%s, %s) has requested an update of %s\n\nYou have received this mail because you are either the new or old technical and/or administrative contact.\n\nYou can see the new version at %s/?showEntity=%d\n\nIf you do not approve this update please forward this mail to SWAMID Operations (operations@swamid.se) and request for the update to be denied.", $EPPN, $fullName, $mail, $draftMetadata->EntityID(), $hostURL, $Entity_id);

				$mailRequetser->isHTML(true);
				$mailRequetser->Body	= sprintf("<p>Hi.</p>\n<p>You have requested an update of %s</p>\n<p>Please forward this email to SWAMID Operations (operations@swamid.se).</p>\n<p>The new version can be found at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>\n<p>An email has also been sent to the following addresses since they are the new or old technical and/or administrative contacts : </p>\n<p><ul>\n<li>%s</li>\n</ul>\n", $draftMetadata->EntityID(), $hostURL, $Entity_id, $hostURL, $Entity_id,implode ("</li>\n<li>",$addresses));
				$mailRequetser->AltBody	= sprintf("Hi.\n\nYou have requested an update of %s\n\nPlease forward this email to SWAMID Operations (operations@swamid.se).\n\nThe new version can be found at %s/?showEntity=%d\n\nAn email has also been sent to the following addresses since they are the new or old technical and/or administrative contacts : %s\n\n", $draftMetadata->EntityID(), $hostURL, $Entity_id, implode (", ",$addresses));

				$short_entityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $draftMetadata->EntityID());
				$publishedMetadata = new Metadata($baseDir, $draftMetadata->EntityID(), 'prod');

				if ($publishedMetadata->EntityExists()) {
					$mailContacts->Subject	= 'Info : Updated SWAMID metadata for ' . $short_entityid;
					$mailRequetser->Subject	= 'Updated SWAMID metadata for ' . $short_entityid;
					$shadowMetadata = new Metadata($baseDir, $draftMetadata->EntityID(), 'Shadow');
					$shadowMetadata->importXML($publishedMetadata->XML());
					$shadowMetadata->updateFeedByValue($publishedMetadata->feedValue());
					$shadowMetadata->validateXML();
					$shadowMetadata->validateSAML();
					$oldEntity_id = $shadowMetadata->ID();
				} else {
					$mailContacts->Subject	= 'Info : New SWAMID metadata for ' . $short_entityid;
					$mailRequetser->Subject	= 'New SWAMID metadata for ' . $short_entityid;
					$oldEntity_id = 0;
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
				printf ('    <hr>%s    <a href=".?showEntity=%d"><button type="button" class="btn btn-primary">Back to entity</button></a>',"\n",$Entity_id);
				$draftMetadata->updateFeedByValue($_GET['publishedIn']);
				$draftMetadata->moveDraftToPending($oldEntity_id);
			} else {
				$menuActive = 'new';
				showMenu();
				if ($errors != '') {
					printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
				}
				printf('%s    <p>You are about to request publication of <b>%s</b></p>', "\n", $draftMetadata->EntityID());
				$publishedMetadata = new Metadata($baseDir, $draftMetadata->EntityID(), 'prod');

				$publishArrayOld = array();
				if ($publishedMetadata->EntityExists()) {
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
      <label for="OrganisationOK">I confirm that this Entity fulfils sections <b>%s</b> in <a href="http://www.swamid.se/policy/technology/saml-websso" target="_blank">SWAMID SAML WebSSO Technology Profile</a></label><br>
      <br>
      <input type="submit" name="action" value="Request publication">
    </form>
    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', $Entity_id, $oldPublishedValue == 7 ? ' checked' : '', $oldPublishedValue == 3 ? ' checked' : '', $oldPublishedValue == 1 ? ' checked' : '', $sections, $Entity_id);
			}
		} else {
			printf('
    <div class="row alert alert-danger" role="alert">
      <div class="col">
        <b>Please fix the following errors before requesting publication:</b><br>
        %s
      </div>
    </div>
    <a href=".?showEntity=%d"><button type="button" class="btn btn-outline-primary">Return to Entity</button></a>', str_ireplace("\n", "<br>", $errors), $Entity_id);
		}
	} else {
		$html->showHeaders('Metadata SWAMID - NotFound');
		$menuActive = 'new';
		showMenu();
		print "Can't find Entity";
	}
	print "\n";
}

function annualConfirmation($Entity_id){
	global $html, $menuActive, $baseDir;
	global $EPPN, $mail, $fullName;

	$metadata = new Metadata($baseDir, $Entity_id);
	if ($metadata->status() == 1) {
		# Entity is Published
		$errors = getErrors($Entity_id);
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
					$errors .= isset($_GET['FormVisit']) ? "You must fulfill sections $sections in SWAMID SAML WebSSO Technology Profile.\n" : '';
					$confirm = false;
				}

				if ($confirm) {
					$metadata->confirmEntity($user_id);
					$menuActive = 'myEntities';
					showMyEntities();
				} else {
					$html->showHeaders('Metadata SWAMID - ' . $metadata->EntityID());
					$menuActive = '';
					showMenu();
					if ($errors != '') {
						printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
					}
					printf('%s    <p>You are confirming that <b>%s</b> is operational and fulfils SWAMID SAML WebSSO Technology Profile</p>%s', "\n", $metadata->EntityID(), "\n");
					printf('    <form>%s      <input type="hidden" name="Entity" value="%d">%s      <input type="hidden" name="FormVisit" value="true">%s      <h5> Confirmation:</h5>%s      <input type="checkbox" id="entityIsOK" name="entityIsOK">%s      <label for="entityIsOK">I confirm that this Entity fulfils sections <b>%s</b> in <a href="http://www.swamid.se/policy/technology/saml-websso" target="_blank">SWAMID SAML WebSSO Technology Profile</a></label><br>%s      <br>%s      <input type="submit" name="action" value="Annual Confirmation">%s    </form>%s    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>%s', "\n", $Entity_id, "\n", "\n", "\n", "\n", $sections, "\n", "\n", "\n", "\n", $Entity_id, "\n");
				}
			} else {
				# User have no access yet.
				requestAccess($Entity_id);
			}
		} else {
			$html->showHeaders('Metadata SWAMID - ' . $metadata->EntityID());
			printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <b>Please fix the following errors before confirming:</b><br>%s        %s%s      </div>%s    </div>%s    <a href=".?showEntity=%d"><button type="button" class="btn btn-outline-primary">Return to Entity</button></a>%s', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n", "\n", $Entity_id, "\n");
		}
	} else {
		$html->showHeaders('Metadata SWAMID - NotFound');
		$menuActive = 'new';
		showMenu();
		print "Can't find Entity";
	}
}

function requestRemoval($Entity_id) {
	global $db, $html, $menuActive, $baseDir;
	global $EPPN, $mail, $fullName;
	global $mailContacts, $mailRequetser, $SendOut;
	$metadata = new Metadata($baseDir, $Entity_id);
	if ($metadata->status() == 1) {
		$user_id = $metadata->getUserId($EPPN);
		if ($metadata->isResponsible()) {
			# User have access to entity
			$html->showHeaders('Metadata SWAMID - ' . $metadata->EntityID());
			if (isset($_GET['confirmRemoval'])) {
				$menuActive = 'publ';
				showMenu();
				$fullName = iconv("UTF-8", "ISO-8859-1", $fullName);

				setupMail();

				if ($SendOut)
					$mailRequetser->addAddress($mail);

				$addresses = array();
				$contactHandler = $db->prepare("SELECT DISTINCT emailAddress FROM ContactPerson WHERE entity_id = :Entity_ID AND (contactType='technical' OR contactType='administrative')");
				$contactHandler->bindParam(':Entity_ID',$Entity_id);
				$contactHandler->execute();
				while ($address = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
					if ($SendOut)
						$mailContacts->addAddress(substr($address['emailAddress'],7));
					$addresses[] = substr($address['emailAddress'],7);
				}

				$hostURL = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'];

				//Content
				$mailContacts->isHTML(true);
				$mailContacts->Body		= sprintf("<p>Hi.</p>\n<p>%s (%s, %s) has requested removal of the entity with the entityID %s from the SWAMID metadata.</p>\n<p>You have received this mail because you are either the technical and/or administrative contact.</p>\n<p>You can see the current metadata at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>\n<p>If you do not approve request please forward this mail to SWAMID Operations (operations@swamid.se) and request for the removal to be denied.</p>", $EPPN, $fullName, $mail, $metadata->EntityID(), $hostURL, $Entity_id, $hostURL, $Entity_id);
				$mailContacts->AltBody	= sprintf("Hi.\n\n%s (%s, %s) has requested removal of the entity with the entityID %s from the SWAMID metadata.\n\nYou have received this mail because you are either the technical and/or administrative contact.\n\nYou can see the current metadata at %s/?showEntity=%d\n\nIf you do not approve this request please forward this mail to SWAMID Operations (operations@swamid.se) and request for the removal to be denied.", $EPPN, $fullName, $mail, $metadata->EntityID(), $hostURL, $Entity_id);

				$mailRequetser->isHTML(true);
				$mailRequetser->Body	= sprintf("<p>Hi.</p>\n<p>You have requested removal of the entity with the entityID %s from the SWAMID metadata.</p>\n<p>Please forward this email to SWAMID Operations (operations@swamid.se).</p>\n<p>The current metadata can be found at <a href=\"%s/?showEntity=%d\">%s/?showEntity=%d</a></p>\n<p>An email has also been sent to the following addresses since they are the technical and/or administrative contacts : </p>\n<p><ul>\n<li>%s</li>\n</ul>\n", $metadata->EntityID(), $hostURL, $Entity_id, $hostURL, $Entity_id,implode ("</li>\n<li>",$addresses));
				$mailRequetser->AltBody	= sprintf("Hi.\n\nYou have requested removal of the entity with the entityID %s from the SWAMID metadata.\n\nPlease forward this email to SWAMID Operations (operations@swamid.se).\n\nThe current metadata can be found at %s/?showEntity=%d\n\nAn email has also been sent to the following addresses since they are the technical and/or administrative contacts : %s\n\n", $metadata->EntityID(), $hostURL, $Entity_id, implode (", ",$addresses));

				$short_entityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $metadata->EntityID());
				$mailContacts->Subject	= 'Info : Request to remove SWAMID metadata for ' . $short_entityid;
				$mailRequetser->Subject	= 'Request to remove SWAMID metadata for ' . $short_entityid;

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
				printf ('    <hr>%s    <a href=".?showEntity=%d"><button type="button" class="btn btn-primary">Back to entity</button></a>',"\n",$Entity_id);
			} else {
				$menuActive = 'publ';
				showMenu();
				printf('%s    <p>You are about to request removal of the entity with the entityID <b>%s</b> from the SWAMID metadata.</p>', "\n", $metadata->EntityID());
				if (($metadata->feedValue() & 2) == 2) $publishArray[] = 'SWAMID';
				if (($metadata->feedValue() & 4) == 4) $publishArray[] = 'eduGAIN';
				if ($metadata->feedValue() == 1) $publishArray[] = 'SWAMID-testing';
				printf('%s    <p>Currently published in <b>%s</b></p>%s', "\n", implode (' and ', $publishArray), "\n");
				printf('    <form>%s      <input type="hidden" name="Entity" value="%d">%s      <input type="checkbox" id="confirmRemoval" name="confirmRemoval">%s      <label for="confirmRemoval">I confirm that this Entity should be removed</label><br>%s      <br>%s      <input type="submit" name="action" value="Request removal">%s    </form>%s    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', "\n", $Entity_id, "\n", "\n", "\n", "\n", "\n", "\n" ,$Entity_id);
			}
		} else {
			# User have no access yet.
			requestAccess($Entity_id);
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

function move2Draft($Entity_id) {
	global $db, $html, $display, $menuActive, $baseDir;
	global $EPPN,$mail;
	$entityHandler = $db->prepare('SELECT `entityID`, `xml` FROM Entities WHERE `status` = 2 AND `id` = :Id;');
	$entityHandler->bindParam(':Id', $Entity_id);
	$entityHandler->execute();
	if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
		if (isset($_GET['action'])) {
			$draftMetadata = new Metadata($baseDir, $entity['entityID'], 'New');
			$draftMetadata->importXML($entity['xml']);
			$draftMetadata->validateXML(true);
			$draftMetadata->validateSAML(true);
			$menuActive = 'new';
			$draftMetadata->copyResponsible($Entity_id);
			showEntity($draftMetadata->ID());
			$oldMetadata = new Metadata($baseDir, $Entity_id);
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
    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', $Entity_id, $Entity_id);
		}
	} else {
		$html->showHeaders('Metadata SWAMID - NotFound');
		$menuActive = 'wait';
		showMenu();
		print "Can't find Entity";
	}
	print "\n";
}

function mergeEntity($Entity_id, $oldEntity_id) {
	global $baseDir;
	include '../include/MetadataEdit.php';
	$metadata = new MetadataEdit($baseDir, $Entity_id, $oldEntity_id);
	$metadata->mergeFrom();
}

function removeEntity($Entity_id) {
	global $db, $html, $menuActive, $baseDir;
	$entityHandler = $db->prepare('SELECT entityID, status FROM Entities WHERE id = :Id;');
	$entityHandler->bindParam(':Id', $Entity_id);
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
				$metadata = new Metadata($baseDir, $Entity_id);
				$metadata->removeEntity();
				printf('    <p>You have removed <b>%s</b> from %s</p>%s', $entity['entityID'], $from, "\n");
			} else {
				printf('    <p>You are about to %s of <b>%s</b></p>%s    <form>%s      <input type="hidden" name="removeEntity" value="%d">%s      <input type="submit" name="action" value="%s">%s    </form>%s    <a href="/admin/?showEntity=%d"><button>Return to Entity</button></a>', $action, $entity['entityID'], "\n", "\n", $Entity_id, "\n", $button, "\n", "\n",  $Entity_id);
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

function checkAccess($Entity_id, $userID, $userLevel, $minLevel, $showError=false) {
	global $html, $baseDir;
	if ($userLevel >= $minLevel)
		return true;
	$metadata = new Metadata($baseDir, $Entity_id);
	$metadata->getUser($userID);
	if ($metadata->isResponsible()) {
		return true;
	} else {
		if ($showError) {
			$html->showHeaders('Metadata SWAMID');
			print "You doesn't have access to this entityID";
			printf('%s      <a href=".?showEntity=%d"><button type="button" class="btn btn-outline-danger">Back to entity</button></a>', "\n", $Entity_id);
		}
		return false;
	}
}

# Request access to an entity
function requestAccess($Entity_id) {
	global $html, $baseDir;
	global $EPPN, $mail, $fullName;
	global $mailContacts, $mailRequetser, $SendOut;

	$metadata = new Metadata($baseDir, $Entity_id);
	if ($metadata->EntityExists()) {
		$user_id = $metadata->getUserId($EPPN);
		if ($metadata->isResponsible()) {
			# User already have access.
			$html->showHeaders('Metadata SWAMID - ' . $metadata->EntityID());
			$menuActive = '';
			showMenu();
			printf('%s    <p>You already have access to <b>%s</b></p>%s', "\n", $metadata->EntityID(), "\n");
			printf('    <a href="./?showEntity=%d"><button>Return to Entity</button></a>%s', $Entity_id, "\n");
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
				$mailContacts->Body		= sprintf("<p>Hi.</p>\n<p>%s (%s, %s) has requested access to update %s</p>\n<p>You have received this mail because you are either the technical and/or administrative contact.</p>\n<p>If you approve, please click on this link <a href=\"%s/admin/?approveAccessRequest=%s\">%s/?approveAccessRequest=%s</a></p>\n<p>If you do not approve, you can ignore this email. No changes will be made.</p>", $EPPN, $fullName, $mail, $metadata->EntityID(), $hostURL, $requestCode, $hostURL, $requestCode);
				$mailContacts->AltBody	= sprintf("Hi.\n\n%s (%s, %s) has requested access to update %s\n\nYou have received this mail because you are either the technical and/or administrative contact.\n\nIf you approve, please click on this link %s/admin/?approveAccessRequest=%s\n\nIf you do not approve, you can ignore this email. No changes will be made.", $EPPN, $fullName, $mail, $metadata->EntityID(), $hostURL, $requestCode);
				$info = sprintf("<p>The request has been sent to: %s</p>\n<p>Contact them and ask them to accept your request.</p>\n", implode (", ",$addresses));

				$short_entityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $metadata->EntityID());

				$mailContacts->Subject	= 'Access request for ' . $short_entityid;

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
				$html->showHeaders('Metadata SWAMID - ' . $metadata->EntityID());
				$menuActive = '';
				showMenu();
				if ($errors != '') {
					printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Errors:</b></div>%s        <div class="row">%s</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
				}
				printf('%s    <p>You do not have access to <b>%s</b></p>%s', "\n", $metadata->EntityID(), "\n");
				printf('    <form>%s      <input type="hidden" name="Entity" value="%d">%s      <input type="hidden" name="FormVisit">%s      <h5>Request access:</h5>%s      <input type="checkbox" id="requestAccess" name="requestAccess">%s      <label for="requestAccess">I confirm that I have the right to update this entity.</label><br>%s      <p>A mail will be sent to the following addresses with further instructions: %s.</p>%s      <input type="submit" name="action" value="Request Access">%s    </form>%s    <a href="./?showEntity=%d"><button>Return to Entity</button></a>%s', "\n", $Entity_id, "\n", "\n", "\n", "\n", "\n", implode (", ",$addresses), "\n", "\n", "\n", $Entity_id, "\n");
			}
		}
	}
}

# Return Blocking errors
function getBlockingErrors($Entity_id) {
	global $db;
	$errors = '';

	$entityHandler = $db->prepare('SELECT `entityID`, `errors` FROM Entities WHERE `id` = :Id;');
	$entityHandler->bindParam(':Id', $Entity_id);
	/*$urlHandler1 = $db->prepare('SELECT `status`, `URL`, `lastValidated`, `validationOutput` FROM URLs WHERE URL IN (SELECT `data` FROM Mdui WHERE `entity_id` = :Id)');
	$urlHandler1->bindParam(':Id', $Entity_id);
	$urlHandler2 = $db->prepare("SELECT `status`, `URL`, `lastValidated`, `validationOutput` FROM URLs WHERE URL IN (SELECT `URL` FROM EntityURLs WHERE `entity_id` = :Id UNION SELECT `data` FROM Organization WHERE `element` = 'OrganizationURL' AND `entity_id` = :Id)");
	$urlHandler2->bindParam(':Id', $Entity_id);
	$urlHandler3 = $db->prepare("SELECT `status`, `URL`, `lastValidated`, `validationOutput` FROM URLs WHERE URL IN (SELECT `data` FROM Organization WHERE `element` = 'OrganizationURL' AND `entity_id` = :Id)");
	$urlHandler3->bindParam(':Id', $Entity_id);*/

	$entityHandler->execute();
	if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
		/*$urlHandler1->execute();
		while ($url = $urlHandler1->fetch(PDO::FETCH_ASSOC)) {
			if ($url['status'] > 0)
				$errors .= sprintf("%s - %s\n", $url['validationOutput'], $url['URL']);
		}
		$urlHandler2->execute();
		while ($url = $urlHandler2->fetch(PDO::FETCH_ASSOC)) {
			if ($url['status'] > 0)
				$errors .= sprintf("%s - %s\n", $url['validationOutput'], $url['URL']);
		}
		$urlHandler3->execute();
		while ($url = $urlHandler3->fetch(PDO::FETCH_ASSOC)) {
			if ($url['status'] > 0)
				$errors .= sprintf("%s - %s\n", $url['validationOutput'], $url['URL']);
		}*/
		$errors .= $entity['errors'];
	}
	return $errors;
}

# Return All errors, both blocking and nonblocking
function getErrors($Entity_id) {
	global $db;
	$errors = '';

	$entityHandler = $db->prepare('SELECT `entityID`, `status`, `validationOutput`, `warnings`, `errors`, `errorsNB` FROM Entities WHERE `id` = :Id;');
	$entityHandler->bindParam(':Id', $Entity_id);

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
	global $baseDir;
	global $EPPN;
	$codeArray = explode(':', base64_decode($code));
	if (isset($codeArray[2])) {
		$metadata = new Metadata($baseDir, $codeArray[0]);
		if ($metadata->EntityExists()) {
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
					$mail->Body		= sprintf("<p>Hi.</p>\n<p>Your access to %s have been granted.</p>\n<p>-- <br>This mail was sent by SWAMID Metadata Admin Tool, a service provided by SWAMID Operations. If you've any questions please contact operations@swamid.se.</p>", $metadata->entityID());
					$mail->AltBody	= sprintf("Hi.\n\nYour access to %s have been granted.\n\n-- \nThis mail was sent by SWAMID Metadata Admin Tool, a service provided by SWAMID Operations. If you've any questions please contact operations@swamid.se.", $metadata->EntityID());
					$mail->Subject	= 'Access granted for ' . $short_entityid;

					$info = sprintf('<h3>Access granted</h3>Access to <b>%s</b> added for %s (%s).', $metadata->EntityID(), $result['fullName'], $result['email']);

					try {
						$mail->send();
					} catch (Exception $e) {
						echo 'Message could not be sent to contacts.<br>';
						echo 'Mailer Error: ' . $mailContacts->ErrorInfo . '<br>';
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
	print $text;
}

function showInfo($text) {
	global $html, $menuActive;
	$html->showHeaders('Metadata SWAMID');
	showMenu();
	printf ('    <div class="row">%s      <div class="col">%s        %s%s      </div>%s    </div>%s', "\n", "\n", $text, "\n", "\n", "\n");
}