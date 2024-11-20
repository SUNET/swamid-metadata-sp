<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

include_once __DIR__ . '/../html/config.php';  # NOSONAR

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "DB Error";
}

$updateMailRemindersHandler = $db->prepare('INSERT INTO MailReminders (`entity_id`, `type`, `level`, `mailDate`)
  VALUES (:Entity_Id, :Type, :Level, NOW()) ON DUPLICATE KEY UPDATE `level` = :Level, `mailDate` = NOW()');
$removeMailRemindersHandler = $db->prepare('DELETE FROM MailReminders
  WHERE `entity_id` = :Entity_Id AND `type` = :Type');
$getMailRemindersHandler = $db->prepare(
  'SELECT `entity_id`, `level` FROM MailReminders WHERE `type` = :Type');

confirmEntities();
oldCerts();
checkOldPending();
checkOldDraft();

function confirmEntities() {
  global $updateMailRemindersHandler, $removeMailRemindersHandler, $getMailRemindersHandler, $db;
  # Time to confirm entities again ?
  $reminders = array();
  $getMailRemindersHandler->execute(array('Type' => 1));
  while ($entity = $getMailRemindersHandler->fetch(PDO::FETCH_ASSOC)) {
    $reminders[$entity['entity_id']] = $entity['level'];
  }
  $getMailRemindersHandler->closeCursor();

  $flagDates = $db->query('SELECT NOW() - INTERVAL 10 MONTH AS `warn1Date`,
    NOW() - INTERVAL 11 MONTH AS `warn2Date`,
    NOW() - INTERVAL 12 MONTH AS `errorDate`', PDO::FETCH_ASSOC);
  foreach ($flagDates as $dates) {
    # Need to use foreach to fetch row. $flagDates is a PDOStatement
    $warn1Date = $dates['warn1Date'];
    $warn2Date = $dates['warn2Date'];
    $errorDate = $dates['errorDate'];
  }
  $flagDates->closeCursor();

  $entitiesHandler = $db->prepare("SELECT DISTINCT `Entities`.`id`, `entityID`, `lastConfirmed`, `data` AS DisplayName
    FROM `Entities`
    LEFT JOIN `EntityConfirmation` ON `EntityConfirmation`.`entity_id` = `Entities`.`id`
    LEFT JOIN `Mdui` ON `Mdui`.`entity_id` = `Entities`.`id` AND `element` = 'DisplayName' AND `lang` = 'en'
    WHERE `status` = 1 AND `publishIn` > 1 ORDER BY `entityID`");

  $entitiesHandler->execute();
  while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    if ($warn1Date > $entity['lastConfirmed']) {
      if (! isset($reminders[$entity['id']])) {
        $reminders[$entity['id']] = 0;
      }
      if ($errorDate > $entity['lastConfirmed'] && $reminders[$entity['id']] < 3) {
        printf('Error %s %s%s', $entity['lastConfirmed'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 1, 'Level' => 3));
        sendEntityConfirmation($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), 12);
      } elseif ($warn2Date > $entity['lastConfirmed'] && $reminders[$entity['id']] < 2) {
        printf('Warn2 %s %s%s', $entity['lastConfirmed'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 1, 'Level' => 2));
        sendEntityConfirmation($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), 11);
      } elseif ($warn1Date > $entity['lastConfirmed'] && $reminders[$entity['id']] < 1) {
        printf('Warn1 %s %s%s', $entity['lastConfirmed'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 1, 'Level' => 1));
        sendEntityConfirmation($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), 10);
      }
      unset($reminders[$entity['id']]);
    }
  }

  $reminder = 0;
  $removeMailRemindersHandler->bindValue(':Type', 1);
  $removeMailRemindersHandler->bindParam(':Entity_Id', $reminder);
  foreach ($reminders as $reminder => $level) {
    $removeMailRemindersHandler->execute();
  }
  $entitiesHandler->closeCursor();
}

function oldCerts() {
  global $updateMailRemindersHandler, $removeMailRemindersHandler, $getMailRemindersHandler, $db;
  # Time to update certs ?
  $reminders = array();
  $getMailRemindersHandler->execute(array('Type' => 2));
  while ($entity = $getMailRemindersHandler->fetch(PDO::FETCH_ASSOC)) {
    $reminders[$entity['entity_id']] = $entity['level'];
  }
  $getMailRemindersHandler->closeCursor();

  $flagDates = $db->query('SELECT NOW() + INTERVAL 1 MONTH AS `warn1Date`,
    NOW() `nowDate`', PDO::FETCH_ASSOC);
  foreach ($flagDates as $dates) {
    # Need to use foreach to fetch row. $flagDates is a PDOStatement
    $warn1Date = $dates['warn1Date'];
    $nowDate = $dates['nowDate'];
  }
  $flagDates->closeCursor();

  $keyHandler = $db->prepare('SELECT `notValidAfter`, `type`, `use`, `order`
    FROM `KeyInfo`
    WHERE `KeyInfo`.`entity_id` = :Id
    ORDER BY `type`, `notValidAfter` DESC');
  $entitiesHandler = $db->prepare("SELECT DISTINCT `Entities`.`id`, `entityID`, `data` AS DisplayName
    FROM `KeyInfo`, `Entities`
    LEFT JOIN `Mdui` ON `Mdui`.`entity_id` = Entities.`id` AND `element` = 'DisplayName' AND `lang` = 'en'
    WHERE `Entities`.`id` = `KeyInfo`.`entity_id`
      AND `Entities`.`status` = 1
      AND `publishIn` > 1
      AND `notValidAfter` < :Date
    ORDER BY `entityID`");
  $entitiesHandler->bindValue(':Date', $warn1Date);
  $entitiesHandler->execute();
  while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    $keyHandler->bindValue(':Id', $entity['id']);
    $keyHandler->execute();
    $keyType = 'None';
    $keyStatus = 0;
    $errorText = '';
    $maxStatus = 0;

    while ($key = $keyHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($keyType != $key['type']) {
        if ($keyStatus > 1) {
          $errorText .= parserKeyError($keyStatus, $keyType);
          $maxStatus = $keyStatus > $maxStatus ? $keyStatus : $maxStatus;
        }
        $sign = 3;
        $encr = 3;
        $keyStatus = 0;
        $keyType = $key['type'];
      }
      switch($key['use']) {
        case 'encryption' :
          if ($key['notValidAfter'] > $warn1Date) {
            $encr = 0;
          } elseif ($key['notValidAfter'] > $nowDate) {
            if ($encr > 0) {
              $keyStatus = 1;
              $encr = 1;
            }
          } elseif ($encr > 1) {
            # Before warn1Date and no OK
            $keyStatus = 2;
            $encr = 2;
          }
          break;
        case 'signing' :
          if ($key['notValidAfter'] > $warn1Date) {
            $sign = 0;
          } elseif ($key['notValidAfter'] > $nowDate) {
            if ($sign > 0) {
              $keyStatus = 1;
              $sign = 1;
            }
          } elseif ($sign > 1) {
            # Before warn1Date and no OK
            $keyStatus = 2;
            $sign = 2;
          }
          break;
        case 'both' :
          if ($key['notValidAfter'] > $warn1Date) {
            $sign = 0;
            $encr = 0;
          } elseif ($key['notValidAfter'] > $nowDate) {
            if (($sign + $encr) > 0) {
              $keyStatus = 1;
              $sign = 1;
              $encr = 1;
            }
          } elseif (($sign + $encr) > 2) {
            # Before warn1Date and no OK
            $keyStatus = 2;
            $sign = 2;
            $encr = 2;
          }
          break;
        default :
          printf ("Missing %s\n",$key['use']);
      }
    }
    if ($keyStatus > 0) {
      $errorText .= parserKeyError($keyStatus, $keyType);
      $maxStatus = $keyStatus > $maxStatus ? $keyStatus : $maxStatus;
    }
    if ($maxStatus > 0) {
      if (! isset($reminders[$entity['id']])) {
        $reminders[$entity['id']] = 0;
      }
      if ($reminders[$entity['id']] < $maxStatus) {
        printf("\nProblem with %s\n%s",$entity['entityID'],$errorText);
        $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 2, 'Level' => $maxStatus));
        sendCertReminder($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $maxStatus);
      }
      unset($reminders[$entity['id']]);
    }
  }
  $reminder = 0;
  $removeMailRemindersHandler->bindValue(':Type', 2);
  $removeMailRemindersHandler->bindParam(':Entity_Id', $reminder);
  foreach ($reminders as $reminder => $level) {
    $removeMailRemindersHandler->execute();
  }
  $entitiesHandler->closeCursor();
}

function parserKeyError($keyStatus, $keyType) {
  switch($keyStatus) {
    case 1 :
      return ' -> At least one key for ' . $keyType . "Descriptor will expire within 1 month\n";
    case 2 :
      return ' -> At least one key for ' . $keyType . "Descriptor have expired\n";
    default:
  }
}

function checkOldPending() {
  global $updateMailRemindersHandler, $removeMailRemindersHandler, $getMailRemindersHandler, $db;

  # Warn for pending not handled
  $reminders = array();
  $getMailRemindersHandler->execute(array('Type' => 3));
  while ($entity = $getMailRemindersHandler->fetch(PDO::FETCH_ASSOC)) {
    $reminders[$entity['entity_id']] = $entity['level'];
  }
  $getMailRemindersHandler->closeCursor();

  $flagDates = $db->query('SELECT NOW() - INTERVAL 1 WEEK AS `warn1Date`,
    NOW() - INTERVAL 4 WEEK AS `warn2Date`,
    NOW() - INTERVAL 11 WEEK AS `warn3Date`', PDO::FETCH_ASSOC);
  foreach ($flagDates as $dates) {
    # Need to use foreach to fetch row. $flagDates is a PDOStatement
    $warn1Date = $dates['warn1Date'];
    $warn2Date = $dates['warn2Date'];
    $warn3Date = $dates['warn3Date'];
  }
  $flagDates->closeCursor();

  $entitiesHandler = $db->prepare("SELECT DISTINCT `Entities`.`id`, `entityID`, `lastValidated`,
      `lastValidated` + INTERVAL 12 WEEK AS removeDate, `data` AS DisplayName
    FROM `Entities`
    LEFT JOIN `Mdui` ON `Mdui`.`entity_id` = Entities.`id` AND `element` = 'DisplayName' AND `lang` = 'en'
    WHERE `Entities`.`status` = 2
      AND lastValidated < :Date
    ORDER BY `entityID`");
  $entitiesHandler->bindValue(':Date', $warn1Date);
  $entitiesHandler->execute();
  while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    if ($warn1Date > $entity['lastValidated']) {
      if (! isset($reminders[$entity['id']])) {
        $reminders[$entity['id']] = 0;
      }
      if ($warn3Date > $entity['lastValidated'] && $reminders[$entity['id']] < 3) {
        printf('Pending since %s %s%s', $entity['lastValidated'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 3, 'Level' => 3));
        sendOldUpdates($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $entity['removeDate'], 11);
      } elseif ($warn2Date > $entity['lastValidated'] && $reminders[$entity['id']] < 2) {
        printf('Pending since %s %s%s', $entity['lastValidated'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 3, 'Level' => 2));
        sendOldUpdates($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $entity['removeDate'], 4);
      } elseif ($warn1Date > $entity['lastValidated'] && $reminders[$entity['id']] < 1) {
        printf('Pending since %s %s%s', $entity['lastValidated'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 3, 'Level' => 1));
        sendOldUpdates($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $entity['removeDate'], 1);
      }
      unset($reminders[$entity['id']]);
    }
  }

  $reminder = 0;
  $removeMailRemindersHandler->bindValue(':Type', 3);
  $removeMailRemindersHandler->bindParam(':Entity_Id', $reminder);
  foreach ($reminders as $reminder => $level) {
    $removeMailRemindersHandler->execute();
  }
  $entitiesHandler->closeCursor();
}

function checkOldDraft() {
  global $updateMailRemindersHandler, $removeMailRemindersHandler, $getMailRemindersHandler, $db;

  # Warn for drafts not handled
  $reminders = array();
  $getMailRemindersHandler->execute(array('Type' => 4));
  while ($entity = $getMailRemindersHandler->fetch(PDO::FETCH_ASSOC)) {
    $reminders[$entity['entity_id']] = $entity['level'];
  }
  $getMailRemindersHandler->closeCursor();

  $flagDates = $db->query('SELECT NOW() - INTERVAL 2 WEEK AS `warn1Date`,
    NOW() - INTERVAL 7 WEEK AS `warn2Date`', PDO::FETCH_ASSOC);
  foreach ($flagDates as $dates) {
    # Need to use foreach to fetch row. $flagDates is a PDOStatement
    $warn1Date = $dates['warn1Date'];
    $warn2Date = $dates['warn2Date'];
  }
  $flagDates->closeCursor();

  $entitiesHandler = $db->prepare("SELECT DISTINCT `Entities`.`id`, `entityID`, `lastValidated`,
      `lastValidated` + INTERVAL 8 WEEK AS removeDate, `data` AS DisplayName
    FROM `Entities`
    LEFT JOIN `Mdui` ON `Mdui`.`entity_id` = Entities.`id` AND `element` = 'DisplayName' AND `lang` = 'en'
    WHERE `Entities`.`status` = 3
      AND lastValidated < :Date
    ORDER BY `entityID`");
  $entitiesHandler->bindValue(':Date', $warn1Date);
  $entitiesHandler->execute();
  while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    if ($warn1Date > $entity['lastValidated']) {
      if (! isset($reminders[$entity['id']])) {
        $reminders[$entity['id']] = 0;
      }
      if ($warn2Date > $entity['lastValidated'] && $reminders[$entity['id']] < 2) {
        printf('Draft since %s %s%s', $entity['lastValidated'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 4, 'Level' => 2));
        sendOldUpdates($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $entity['removeDate'], 7, false);
      } elseif ($warn1Date > $entity['lastValidated'] && $reminders[$entity['id']] < 1) {
        printf('Draft since %s %s%s', $entity['lastValidated'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 4, 'Level' => 1));
        sendOldUpdates($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $entity['removeDate'], 2, false);
      }
      unset($reminders[$entity['id']]);
    }
  }

  $reminder = 0;
  $removeMailRemindersHandler->bindValue(':Type', 3);
  $removeMailRemindersHandler->bindParam(':Entity_Id', $reminder);
  foreach ($reminders as $reminder => $level) {
    $removeMailRemindersHandler->execute();
  }
  $entitiesHandler->closeCursor();
}

function sendEntityConfirmation($id, $entityID, $displayName, $months) {
  global $SendOut, $baseURL, $mailContacts;

  setupMail();

  if ($SendOut) {
    $addresses = getAdmins($id);
    foreach ($addresses as $address) {
      $mailContacts->addAddress($address);
    }
    if ($months == 12) {
      $addresses = getTechnicalAndAdministrativeContacts($id);
      foreach ($addresses as $address) {
        $mailContacts->addAddress($address);
      }
    }
  }

  //Content
  $mailContacts->isHTML(true);
  $mailContacts->Body    = sprintf("<html>\n  <body>
    <p>Hi.</p>
    <p>The entity \"%s\" (%s) has not been validated/confirmed for %d months.
    The SWAMID SAML WebSSO Technology Profile requires an annual confirmation that the entity is operational
    and fulfils the Technology Profile. If not annually confirmed the Operations team will start the process
    to remove the entity from SWAMID metadata registry.</p>
    <p>You have received this email because you are either the technical and/or administrative contact.</p>
    <p>You can confirm, update or remove your entity at
    <a href=\"%sadmin/?showEntity=%d\">%sadmin/?showEntity=%d</a> .</p>
    <p>This is a message from the SWAMID SAML WebSSO metadata administration tool.<br>
    --<br>
    On behalf of SWAMID Operations</p>
  </body>\n</html>",
  $displayName, $entityID, $months, $baseURL, $id, $baseURL, $id);
  $mailContacts->AltBody = sprintf("Hi.\n\nThe entity \"%s\" (%s) has not been validated/confirmed for %d months.
    The SWAMID SAML WebSSO Technology Profile requires an annual confirmation that the entity is operational and fulfils
    the Technology Profile. If not annually confirmed the Operations team will start the process to remove the entity
    from SWAMID metadata registry.
    \nYou have received this email because you are either the technical and/or administrative contact.
    \nYou can confirm, update or remove your entity at %sadmin/?showEntity=%d .
    \nThis is a message from the SWAMID SAML WebSSO metadata administration tool.
    --
    On behalf of SWAMID Operations",
    $displayName, $entityID, $months, $baseURL, $id);

  $shortEntityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $entityID);
  $mailContacts->Subject  = 'Warning : SWAMID metadata for ' . $shortEntityid . ' needs to be validated';

  try {
    $mailContacts->send();
  } catch (Exception $e) {
    echo 'Message could not be sent to contacts.<br>';
  }
}

function sendCertReminder($id, $entityID, $displayName, $maxStatus) {
  global $SendOut, $baseURL, $mailContacts;

  setupMail();

  if ($SendOut) {
    $addresses = getTechnicalAndAdministrativeContacts($id);
    foreach ($addresses as $address) {
      $mailContacts->addAddress($address);
    }
  }

  $expireStatus = $maxStatus == 1  ? ' is about to expire' : ' is expired';
  $shortEntityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $entityID);
  $mailContacts->Subject  = sprintf('Warning : Certificate in metadata for %s%s',
    $shortEntityid , $expireStatus);

  //Content
  $mailContacts->isHTML(true);
  $mailContacts->Body    = sprintf("<html>\n  <body>
    <p>Hi.</p>
    <p>The SAML certificate in your metadata registered in SWAMID \"%s\" (%s)%s.</p>
    <p>The SWAMID SAML WebSSO Technology Profile requires that signing and
    encryption certificates MUST NOT be expired.<p>
    <p>You have received this email because you are either the technical and/or administrative contact.</p>
    <p>You can view your entity at <a href=\"%sadmin/?showEntity=%d\">%sadmin/?showEntity=%d</a> .</p>
    <p>See our wiki on how you can roll the certificate without any disturbances on the service.</p>
    <p><a href=\"https://wiki.sunet.se/display/SWAMID/Key+rollover\">https://wiki.sunet.se/display/SWAMID/Key+rollover</a>
    <p>This is a message from the SWAMID SAML WebSSO metadata administration tool.<br>
    --<br>
    On behalf of SWAMID Operations</p>
  </body>\n</html>",
  $displayName, $entityID, $expireStatus, $baseURL, $id, $baseURL, $id);
  $mailContacts->AltBody = sprintf("Hi.\n
    \nThe SAML certificate in your metadata registered in SWAMID \"%s\" (%s)%s.
    \nThe SWAMID SAML WebSSO Technology Profile requires that signing and encryption certificates MUST NOT be expired.
    \nYou have received this email because you are either the technical and/or administrative contact.
    \nYou can view your entity at %sadmin/?showEntity=%d .
    \nSee our wiki on how you can roll the certificate without any disturbances on the service.
    \nhttps://wiki.sunet.se/display/SWAMID/Key+rollover
    \nThis is a message from the SWAMID SAML WebSSO metadata administration tool.
    --
    On behalf of SWAMID Operations",
    $displayName, $entityID, $expireStatus, $baseURL, $id);

  try {
    $mailContacts->send();
  } catch (Exception $e) {
    echo 'Message could not be sent to contacts.<br>';
  }
}

function sendOldUpdates($id, $entityID, $displayName, $removeDate, $weeks, $pending = true) {
  global $SendOut, $baseURL, $mailContacts;

  setupMail();

  $address = getLastUpdater($id);
  if ($SendOut && $address ) {
    printf ("Sending info to %s\n", $address);
    $mailContacts->addAddress($address);
  } else {
    printf ("Would have sent to %s\n", $address);
  }

  //Content
  $mailContacts->isHTML(true);
  $mailContacts->Body    = sprintf("<html>\n  <body>
    <p>Hi.</p>
    <p>The entity \"%s\" (%s) has been in %s for %d week(s).
    If nothing happens your %s will be removed short after %s.</p>
    <p>You have received this email because you are the last person updating this entity.</p>
    %s<p>You can view or cancel your %s at
    <a href=\"%sadmin/?showEntity=%d\">%sadmin/?showEntity=%d</a> .</p>
    <p>This is a message from the SWAMID SAML WebSSO metadata administration tool.<br>
    --<br>
    On behalf of SWAMID Operations</p>
  </body>\n</html>",
  $displayName, $entityID, $pending ? 'Pending' : 'Drafts', $weeks,
  $pending ? 'publication request' : 'draft', substr($removeDate,0,10),
  $pending ? '<p>To get a change published forward this mail to operations@swamid.se</p>' : '',
  $pending ? 'request' : 'draft',
  $baseURL, $id, $baseURL, $id);
  $mailContacts->AltBody = sprintf("Hi.\n\nThe entity \"%s\" (%s) has been in %s for %d week(s).
    If nothing happens your %s will be removed short after %s.
    \nYou have received this email because you are the last person updating this entity.
    %s\nYou can view or cancel your %s at %sadmin/?showEntity=%d .
    \nThis is a message from the SWAMID SAML WebSSO metadata administration tool.
    --
    On behalf of SWAMID Operations",
    $displayName, $entityID, $pending ? 'Pending' : 'Drafts', $weeks,
    $pending ? 'publication request' : 'draft', substr($removeDate,0,10),
    $pending ? "\nTo get a change published forward this mail to operations@swamid.se" : '',
    $pending ? 'request' : 'draft',
    $baseURL, $id);

  $shortEntityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $entityID);
  $mailContacts->Subject  = sprintf ('Warning : SWAMID %s metadata for %s needs to be acted on',
    $pending ? 'pending' : 'draft', $shortEntityid );

  try {
    $mailContacts->send();
  } catch (Exception $e) {
    echo 'Message could not be sent to contacts.<br>';
  }
}

function getTechnicalAndAdministrativeContacts($id) {
  global $db;
  $addresses = array();

  $contactHandler = $db->prepare("SELECT DISTINCT emailAddress
    FROM `Entities`, `ContactPerson`
    WHERE `Entities`.`id` = `entity_id`
      AND `Entities`.`id` = :ID AND `status` = 1
      AND (`contactType`='technical' OR `contactType`='administrative')
      AND `emailAddress` <> ''");
  $contactHandler->execute(array('ID' => $id));
  while ($address = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
    $addresses[] = substr($address['emailAddress'],7);
  }
  return $addresses;
}

function getLastUpdater($id) {
  global $db;

  $userHandler = $db->prepare("SELECT DISTINCT `email`
    FROM `EntityUser`, `Users`
    WHERE `Users`.`id` = `user_id` AND `entity_id` = :ID
    ORDER BY lastChanged DESC;");

  $userHandler->execute(array('ID' => $id));
  if ($address = $userHandler->fetch(PDO::FETCH_ASSOC)) {
    return $address['email'];
  }
  return false;
}

function getAdmins($id) {
  global $db;
  $addresses = array();

  $userHandler = $db->prepare("SELECT DISTINCT `email`
    FROM `EntityUser`, `Users`
    WHERE `Users`.`id` = `user_id` AND `entity_id` = :ID AND `email` <> ''
    ORDER BY lastChanged DESC;");

  $userHandler->execute(array('ID' => $id));
  while ($address = $userHandler->fetch(PDO::FETCH_ASSOC)) {
    $addresses[] = $address['email'];
  }
  return $addresses;
}

function setupMail() {
  global $SMTPHost, $SASLUser, $SASLPassword, $MailFrom, $mailContacts;
  $mailContacts = new PHPMailer(true);
  $mailContacts->isSMTP();
  $mailContacts->Host = $SMTPHost;
  $mailContacts->SMTPAuth = true;
  $mailContacts->SMTPAutoTLS = true;
  $mailContacts->Port = 587;
  $mailContacts->Username = $SASLUser;
  $mailContacts->Password = $SASLPassword;
  $mailContacts->SMTPSecure = 'tls';

  //Recipients
  $mailContacts->setFrom($MailFrom, 'Metadata - Admin');
  $mailContacts->addBCC('bjorn@sunet.se');
  $mailContacts->addReplyTo('operations@swamid.se', 'SWAMID Operations');
}
