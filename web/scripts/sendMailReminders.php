<?php

const BIND_DATE = ':Date';
const BIND_ID = ':Id';
const BIND_LEVEL = ':Level';
const BIND_TYPE = ':Type';

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new \metadata\Configuration();

$updateMailRemindersHandler = $config->getDb()->prepare('INSERT INTO MailReminders (`entity_id`, `type`, `level`, `mailDate`)
  VALUES (:Id, :Type, :Level, NOW()) ON DUPLICATE KEY UPDATE `level` = :Level, `mailDate` = NOW()');
$removeMailRemindersHandler = $config->getDb()->prepare('DELETE FROM MailReminders
  WHERE `entity_id` = :Id AND `type` = :Type');
$getMailRemindersHandler = $config->getDb()->prepare(
  'SELECT `entity_id`, `level` FROM MailReminders WHERE `type` = :Type');

confirmEntities();
oldCerts();
checkOldPending();
checkOldDraft();
if ($config->getIMPS()) {
  print "Checking old IMPS:es.\n";
  checkOldIMPS();
} else {
  print "Skipping check of old IMPS since config missing.\n";
}

function confirmEntities() {
  global $updateMailRemindersHandler, $removeMailRemindersHandler, $getMailRemindersHandler, $config;
  # Time to confirm entities again ?
  $reminders = array();
  $getMailRemindersHandler->execute(array(BIND_TYPE => 1));
  while ($entity = $getMailRemindersHandler->fetch(PDO::FETCH_ASSOC)) {
    $reminders[$entity['entity_id']] = $entity['level'];
  }
  $getMailRemindersHandler->closeCursor();

  $flagDates = $config->getDb()->query('SELECT NOW() - INTERVAL 10 MONTH AS `warn1Date`,
    NOW() - INTERVAL 11 MONTH AS `warn2Date`,
    NOW() - INTERVAL 12 MONTH AS `errorDate`', PDO::FETCH_ASSOC);
  foreach ($flagDates as $dates) {
    # Need to use foreach to fetch row. $flagDates is a PDOStatement
    $warn1Date = $dates['warn1Date'];
    $warn2Date = $dates['warn2Date'];
    $errorDate = $dates['errorDate'];
  }
  $flagDates->closeCursor();

  $entitiesHandler = $config->getDb()->prepare("SELECT DISTINCT `Entities`.`id`, `entityID`, `lastConfirmed`, `data` AS DisplayName
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
        $updateMailRemindersHandler->execute(array(BIND_ID => $entity['id'], BIND_TYPE => 1, BIND_LEVEL => 3));
        sendEntityConfirmation($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), 12);
      } elseif ($warn2Date > $entity['lastConfirmed'] && $reminders[$entity['id']] < 2) {
        printf('Warn2 %s %s%s', $entity['lastConfirmed'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $entity['id'], BIND_TYPE => 1, BIND_LEVEL => 2));
        sendEntityConfirmation($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), 11);
      } elseif ($warn1Date > $entity['lastConfirmed'] && $reminders[$entity['id']] < 1) {
        printf('Warn1 %s %s%s', $entity['lastConfirmed'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $entity['id'], BIND_TYPE => 1, BIND_LEVEL => 1));
        sendEntityConfirmation($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), 10);
      }
      unset($reminders[$entity['id']]);
    }
  }

  $reminder = 0;
  $removeMailRemindersHandler->bindValue(BIND_TYPE, 1);
  $removeMailRemindersHandler->bindParam(BIND_ID, $reminder);
  foreach ($reminders as $reminder => $level) {
    $removeMailRemindersHandler->execute();
  }
  $entitiesHandler->closeCursor();
}

function oldCerts() {
  global $updateMailRemindersHandler, $removeMailRemindersHandler, $getMailRemindersHandler, $config;
  # Time to update certs ?
  $reminders = array();
  $getMailRemindersHandler->execute(array(BIND_TYPE => 2));
  while ($entity = $getMailRemindersHandler->fetch(PDO::FETCH_ASSOC)) {
    $reminders[$entity['entity_id']] = $entity['level'];
  }
  $getMailRemindersHandler->closeCursor();

  $flagDates = $config->getDb()->query('SELECT NOW() + INTERVAL 1 MONTH AS `warn1Date`,
    NOW() `nowDate`', PDO::FETCH_ASSOC);
  foreach ($flagDates as $dates) {
    # Need to use foreach to fetch row. $flagDates is a PDOStatement
    $warn1Date = $dates['warn1Date'];
    $nowDate = $dates['nowDate'];
  }
  $flagDates->closeCursor();

  $keyHandler = $config->getDb()->prepare('SELECT `notValidAfter`, `type`, `use`, `order`
    FROM `KeyInfo`
    WHERE `KeyInfo`.`entity_id` = :Id
    ORDER BY `type`, `notValidAfter` DESC');
  $entitiesHandler = $config->getDb()->prepare("SELECT DISTINCT `Entities`.`id`, `entityID`, `data` AS DisplayName
    FROM `KeyInfo`, `Entities`
    LEFT JOIN `Mdui` ON `Mdui`.`entity_id` = Entities.`id` AND `element` = 'DisplayName' AND `lang` = 'en'
    WHERE `Entities`.`id` = `KeyInfo`.`entity_id`
      AND `Entities`.`status` = 1
      AND `publishIn` > 1
      AND `notValidAfter` < :Date
    ORDER BY `entityID`");
  $entitiesHandler->bindValue(BIND_DATE, $warn1Date);
  $entitiesHandler->execute();
  while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    $keyHandler->bindValue(BIND_ID, $entity['id']);
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
          printf ("Unknown key use value %s\n",$key['use']);
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
        $updateMailRemindersHandler->execute(array(BIND_ID => $entity['id'], BIND_TYPE => 2, BIND_LEVEL => $maxStatus));
        sendCertReminder($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $maxStatus);
      }
      unset($reminders[$entity['id']]);
    }
  }
  $reminder = 0;
  $removeMailRemindersHandler->bindValue(BIND_TYPE, 2);
  $removeMailRemindersHandler->bindParam(BIND_ID, $reminder);
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
  global $updateMailRemindersHandler, $removeMailRemindersHandler, $getMailRemindersHandler, $config;

  # Warn for pending not handled
  $reminders = array();
  $getMailRemindersHandler->execute(array(BIND_TYPE => 3));
  while ($entity = $getMailRemindersHandler->fetch(PDO::FETCH_ASSOC)) {
    $reminders[$entity['entity_id']] = $entity['level'];
  }
  $getMailRemindersHandler->closeCursor();

  $flagDates = $config->getDb()->query('SELECT NOW() - INTERVAL 1 WEEK AS `warn1Date`,
    NOW() - INTERVAL 4 WEEK AS `warn2Date`,
    NOW() - INTERVAL 11 WEEK AS `warn3Date`', PDO::FETCH_ASSOC);
  foreach ($flagDates as $dates) {
    # Need to use foreach to fetch row. $flagDates is a PDOStatement
    $warn1Date = $dates['warn1Date'];
    $warn2Date = $dates['warn2Date'];
    $warn3Date = $dates['warn3Date'];
  }
  $flagDates->closeCursor();

  $entitiesHandler = $config->getDb()->prepare("SELECT DISTINCT `Entities`.`id`, `entityID`, `lastValidated`,
      `lastValidated` + INTERVAL 12 WEEK AS removeDate, `data` AS DisplayName
    FROM `Entities`
    LEFT JOIN `Mdui` ON `Mdui`.`entity_id` = Entities.`id` AND `element` = 'DisplayName' AND `lang` = 'en'
    WHERE `Entities`.`status` = 2
      AND lastValidated < :Date
    ORDER BY `entityID`");
  $entitiesHandler->bindValue(BIND_DATE, $warn1Date);
  $entitiesHandler->execute();
  while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    if ($warn1Date > $entity['lastValidated']) {
      if (! isset($reminders[$entity['id']])) {
        $reminders[$entity['id']] = 0;
      }
      if ($warn3Date > $entity['lastValidated'] && $reminders[$entity['id']] < 3) {
        printf('Pending since %s %s%s', $entity['lastValidated'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $entity['id'], BIND_TYPE => 3, BIND_LEVEL => 3));
        sendOldUpdates($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $entity['removeDate'], 11);
      } elseif ($warn2Date > $entity['lastValidated'] && $reminders[$entity['id']] < 2) {
        printf('Pending since %s %s%s', $entity['lastValidated'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $entity['id'], BIND_TYPE => 3, BIND_LEVEL => 2));
        sendOldUpdates($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $entity['removeDate'], 4);
      } elseif ($warn1Date > $entity['lastValidated'] && $reminders[$entity['id']] < 1) {
        printf('Pending since %s %s%s', $entity['lastValidated'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $entity['id'], BIND_TYPE => 3, BIND_LEVEL => 1));
        sendOldUpdates($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $entity['removeDate'], 1);
      }
      unset($reminders[$entity['id']]);
    }
  }

  $reminder = 0;
  $removeMailRemindersHandler->bindValue(BIND_TYPE, 3);
  $removeMailRemindersHandler->bindParam(BIND_ID, $reminder);
  foreach ($reminders as $reminder => $level) {
    $removeMailRemindersHandler->execute();
  }
  $entitiesHandler->closeCursor();
}

function checkOldDraft() {
  global $updateMailRemindersHandler, $removeMailRemindersHandler, $getMailRemindersHandler, $config;

  # Warn for drafts not handled
  $reminders = array();
  $getMailRemindersHandler->execute(array(BIND_TYPE => 4));
  while ($entity = $getMailRemindersHandler->fetch(PDO::FETCH_ASSOC)) {
    $reminders[$entity['entity_id']] = $entity['level'];
  }
  $getMailRemindersHandler->closeCursor();

  $flagDates = $config->getDb()->query('SELECT NOW() - INTERVAL 2 WEEK AS `warn1Date`,
    NOW() - INTERVAL 7 WEEK AS `warn2Date`', PDO::FETCH_ASSOC);
  foreach ($flagDates as $dates) {
    # Need to use foreach to fetch row. $flagDates is a PDOStatement
    $warn1Date = $dates['warn1Date'];
    $warn2Date = $dates['warn2Date'];
  }
  $flagDates->closeCursor();

  $entitiesHandler = $config->getDb()->prepare("SELECT DISTINCT `Entities`.`id`, `entityID`, `lastValidated`,
      `lastValidated` + INTERVAL 8 WEEK AS removeDate, `data` AS DisplayName
    FROM `Entities`
    LEFT JOIN `Mdui` ON `Mdui`.`entity_id` = Entities.`id` AND `element` = 'DisplayName' AND `lang` = 'en'
    WHERE `Entities`.`status` = 3
      AND lastValidated < :Date
    ORDER BY `entityID`");
  $entitiesHandler->bindValue(BIND_DATE, $warn1Date);
  $entitiesHandler->execute();
  while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
    if ($warn1Date > $entity['lastValidated']) {
      if (! isset($reminders[$entity['id']])) {
        $reminders[$entity['id']] = 0;
      }
      if ($warn2Date > $entity['lastValidated'] && $reminders[$entity['id']] < 2) {
        printf('Draft since %s %s%s', $entity['lastValidated'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $entity['id'], BIND_TYPE => 4, BIND_LEVEL => 2));
        sendOldUpdates($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $entity['removeDate'], 7, false);
      } elseif ($warn1Date > $entity['lastValidated'] && $reminders[$entity['id']] < 1) {
        printf('Draft since %s %s%s', $entity['lastValidated'], $entity['entityID'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $entity['id'], BIND_TYPE => 4, BIND_LEVEL => 1));
        sendOldUpdates($entity['id'], $entity['entityID'],
          iconv("UTF-8", "ISO-8859-1", $entity['DisplayName']), $entity['removeDate'], 2, false);
      }
      unset($reminders[$entity['id']]);
    }
  }

  $reminder = 0;
  $removeMailRemindersHandler->bindValue(BIND_TYPE, 3);
  $removeMailRemindersHandler->bindParam(BIND_ID, $reminder);
  foreach ($reminders as $reminder => $level) {
    $removeMailRemindersHandler->execute();
  }
  $entitiesHandler->closeCursor();
}

function checkOldIMPS() {
  global $updateMailRemindersHandler, $removeMailRemindersHandler, $getMailRemindersHandler, $config;

  # Warn for IMPS:es not validated
  $idpImpsHandler = $config->getDb()->prepare('SELECT `IMPS_id`
    FROM `IdpIMPS`
    WHERE `entity_id` = :Id');

  $reminders = array();
  $reminderIMPS = array();
  $getMailRemindersHandler->execute(array(BIND_TYPE => 5));
  while ($entity = $getMailRemindersHandler->fetch(PDO::FETCH_ASSOC)) {
    $idpImpsHandler->execute(array('Id' => $entity['entity_id']));
    while ($imps = $idpImpsHandler->fetch(PDO::FETCH_ASSOC)) {
      $reminderIMPS[$imps['IMPS_id']] = $entity['level'];
    }
    $reminders[$entity['entity_id']] = true;
  }
  $getMailRemindersHandler->closeCursor();

  $flagDates = $config->getDb()->query('SELECT NOW() - INTERVAL ' . $config->getIMPS()['warn1'] . ' MONTH AS `warn1Date`,
    NOW() - INTERVAL ' . $config->getIMPS()['warn2'] . ' MONTH AS `warn2Date`,
    NOW() - INTERVAL ' . $config->getIMPS()['error'] . ' MONTH AS `errorDate`', PDO::FETCH_ASSOC);

  foreach ($flagDates as $dates) {
    # Need to use foreach to fetch row. $flagDates is a PDOStatement
    $warn1Date = $dates['warn1Date'];
    $warn2Date = $dates['warn2Date'];
    $errorDate = $dates['errorDate'];
  }
  $oldDate = $config->getIMPS()['oldDate'];
  $flagDates->closeCursor();

  $impsHandler = $config->getDb()->prepare('SELECT `IMPS`.`id`, `name`, `lastValidated`, `lastUpdated` , `entity_id`
    FROM `IdpIMPS`, `IMPS`
    LEFT JOIN `Users` ON `Users`.`id` = `IMPS`.`user_id`
    WHERE `IMPS_id` = `IMPS`.`id`');
  $impsHandler->execute();
  while ($imps = $impsHandler->fetch((PDO::FETCH_ASSOC))) {
    if ($warn1Date > $imps['lastValidated']) {
      if (! isset($reminderIMPS[$imps['id']])) {
        $reminderIMPS[$imps['id']] = 0;
      }
      if ($oldDate > $imps['lastUpdated'] && $reminderIMPS[$imps['id']] < 4) {
        printf('Error old profile %s %s%s', $imps['lastValidated'], $imps['name'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $imps['entity_id'], BIND_TYPE => 5, BIND_LEVEL => 4));
        sendImpsReminder($imps['entity_id'], iconv("UTF-8", "ISO-8859-1", $imps['name']), 99);
      } elseif ($errorDate > $imps['lastValidated'] && $reminderIMPS[$imps['id']] < 3) {
        printf('Error %s %s%s', $imps['lastValidated'], $imps['name'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $imps['entity_id'], BIND_TYPE => 5, BIND_LEVEL => 3));
        sendImpsReminder($imps['entity_id'], iconv("UTF-8", "ISO-8859-1", $imps['name']), $config->getIMPS()['error']);
      } elseif ($warn2Date > $imps['lastValidated'] && $reminderIMPS[$imps['id']] < 2) {
        printf('Warn2 %s %s%s', $imps['lastValidated'], $imps['name'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $imps['entity_id'], BIND_TYPE => 5, BIND_LEVEL => 2));
        sendImpsReminder($imps['entity_id'], iconv("UTF-8", "ISO-8859-1", $imps['name']), $config->getIMPS()['warn2']);
      } elseif ($warn1Date > $imps['lastValidated'] && $reminderIMPS[$imps['id']] < 1) {
        printf('Warn1 %s %s%s', $imps['lastValidated'], $imps['name'], "\n");
        $updateMailRemindersHandler->execute(array(BIND_ID => $imps['entity_id'], BIND_TYPE => 5, BIND_LEVEL => 1));
        sendImpsReminder($imps['entity_id'], iconv("UTF-8", "ISO-8859-1", $imps['name']), $config->getIMPS()['warn1']);
      }
      unset($reminders[$imps['entity_id']]);
    }
  }

  foreach ($reminders as $reminder => $level) {
    print "Removing $reminder\n";
    $removeMailRemindersHandler->execute(array(BIND_TYPE => 5, BIND_ID => $reminder));
  }
  $impsHandler->closeCursor();
}

function sendEntityConfirmation($id, $entityID, $displayName, $months) {
  global $config, $mailContacts;
  $federation = $config->getFederation();

  setupMail();

  if ($config->sendOut()) {
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
    The %s requires an annual confirmation that the entity is operational
    and fulfils the Technology Profile.</p>
    <p>If the entity should no longer be used within %s please remove it from the metadata registry.</p>
    <p>If not annually confirmed the %s team will start the process to remove the entity from the %s metadata registry.</p>
    <p>You have received this email because you are either the technical and/or administrative contact.</p>
    <p>You can confirm, update or remove your entity at
    <a href=\"%sadmin/?showEntity=%d\">%sadmin/?showEntity=%d</a> .</p>
    <p>This is a message from the %s.<br>
    --<br>
    On behalf of %s</p>
  </body>\n</html>",
  $displayName, $entityID, $months,
  $federation['rulesName'],
  $federation['displayName'],
  $federation['teamName'], $federation['displayName'],
  $config->baseURL(), $id, $config->baseURL(), $id,
  $federation['toolName'],
  $federation['teamName']);
  $mailContacts->AltBody = sprintf("Hi.\n\nThe entity \"%s\" (%s) has not been validated/confirmed for %d months.
    The %s requires an annual confirmation that the entity is operational and fulfils
    the Technology Profile.
    \nIf the entity should no longer be used within %s please remove it from the metadata registry.
    \nIf not annually confirmed the %s team will start the process to remove the entity from the %s metadata registry.
    \nYou have received this email because you are either the technical and/or administrative contact.
    \nYou can confirm, update or remove your entity at %sadmin/?showEntity=%d .
    \nThis is a message from the %s.
    --
    On behalf of %s",
    $displayName, $entityID, $months,
    $federation['rulesName'],
    $federation['displayName'],
    $federation['teamName'], $federation['displayName'],
    $config->baseURL(), $id,
    $federation['toolName'],
    $federation['teamName']);

  $shortEntityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $entityID);
  $mailContacts->Subject  = 'Warning : ' . $federation['displayName'] . ' metadata for ' . $shortEntityid . ' needs to be validated';

  try {
    $mailContacts->send();
  } catch (Exception $e) {
    echo 'Message could not be sent to contacts.<br>' . "\n";
  }
}

function sendCertReminder($id, $entityID, $displayName, $maxStatus) {
  global $config, $mailContacts;
  $federation = $config->getFederation();

  setupMail();

  if ($config->sendOut()) {
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
    <p>The SAML certificate in your metadata registered in %s \"%s\" (%s)%s.</p>
    <p>The %s requires that signing and
    encryption certificates MUST NOT be expired.<p>
    <p>You have received this email because you are either the technical and/or administrative contact.</p>
    <p>You can view your entity at <a href=\"%sadmin/?showEntity=%d\">%sadmin/?showEntity=%d</a> .</p>
    <p>See our documentation on how you can roll the certificate without any disturbances on the service.</p>
    <p><a href=\"%s\">%s</a>
    <p>This is a message from the %s.<br>
    --<br>
    On behalf of %s</p>
  </body>\n</html>",
  $federation['displayName'], $displayName, $entityID, $expireStatus,
  $federation['rulesName'],
  $config->baseURL(), $id, $config->baseURL(), $id,
  $federation['roloverDocURL'], $federation['roloverDocURL'],
  $federation['toolName'],
  $federation['teamName']);
  $mailContacts->AltBody = sprintf("Hi.\n
    \nThe SAML certificate in your metadata registered in %s \"%s\" (%s)%s.
    \nThe %s requires that signing and encryption certificates MUST NOT be expired.
    \nYou have received this email because you are either the technical and/or administrative contact.
    \nYou can view your entity at %sadmin/?showEntity=%d .
    \nSee our documentation on how you can roll the certificate without any disturbances on the service.
    \n%s
    \nThis is a message from the %s.
    --
    On behalf of %s",
    $federation['displayName'], $displayName, $entityID, $expireStatus,
    $federation['rulesName'],
    $config->baseURL(), $id,
    $federation['roloverDocURL'],
    $federation['toolName'],
    $federation['teamName']);

  try {
    $mailContacts->send();
  } catch (Exception $e) {
    echo 'Message could not be sent to contacts.<br>' . "\n";
  }
}

function sendOldUpdates($id, $entityID, $displayName, $removeDate, $weeks, $pending = true) {
  global $config, $mailContacts;
  $federation = $config->getFederation();

  setupMail();

  $address = getLastUpdater($id);
  if ($config->sendOut() && $address ) {
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
    <p>This is a message from the %s.<br>
    --<br>
    On behalf of %s</p>
  </body>\n</html>",
  $displayName, $entityID, $pending ? 'Pending' : 'Drafts', $weeks,
  $pending ? 'publication request' : 'draft', substr($removeDate,0,10),
  $pending ? '<p>To get a change published forward this mail to ' . $federation['teamMail'] . '</p>' : '',
  $pending ? 'request' : 'draft',
  $config->baseURL(), $id, $config->baseURL(), $id,
  $federation['toolName'], $federation['teamName']);
  $mailContacts->AltBody = sprintf("Hi.\n\nThe entity \"%s\" (%s) has been in %s for %d week(s).
    If nothing happens your %s will be removed short after %s.
    \nYou have received this email because you are the last person updating this entity.
    %s\nYou can view or cancel your %s at %sadmin/?showEntity=%d .
    \nThis is a message from the %s.
    --
    On behalf of %s",
    $displayName, $entityID, $pending ? 'Pending' : 'Drafts', $weeks,
    $pending ? 'publication request' : 'draft', substr($removeDate,0,10),
    $pending ? "\nTo get a change published forward this mail to " . $federation['teamMail'] : '',
    $pending ? 'request' : 'draft',
    $config->baseURL(), $id,
    $federation['toolName'], $federation['teamName']);

  $shortEntityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $entityID);
  $mailContacts->Subject  = sprintf ('Warning : %s %s metadata for %s needs to be acted on',
    $federation['displayName'],
    $pending ? 'pending' : 'draft', $shortEntityid );

  try {
    $mailContacts->send();
  } catch (Exception $e) {
    echo 'Message could not be sent to contacts.<br>' . "\n";
  }
}

function sendImpsReminder($id, $name, $months) {
  global $config, $mailContacts;

  setupMail();

  if ($config->sendOut()) {
    $addresses = getAdmins($id);
    foreach ($addresses as $address) {
      $mailContacts->addAddress($address);
    }
    if ($months >= $config->getIMPS()['error']) {
      $addresses = getTechnicalAndAdministrativeContacts($id);
      foreach ($addresses as $address) {
        $mailContacts->addAddress($address);
      }
    }
  }

  //Content
  $mailContacts->isHTML(true);
  if ($months == 99) {
    $mailContacts->Body    = sprintf("<html>\n  <body>
    <p>Hi.</p>
    <p>The Identity Management Practice Statement (IMPS) for \"%s\" has not been validated/confirmed.
    Current approved IMPS is based on a earlier version of the assurance profile.
    The SWAMID Assurance Profiles requires an annual confirmation that the IMPS is still accurate
    and that the Identity Providers adhere to it. If not annually confirmed the Operations team will start the process
    to remove the entity related to this IMPS from SWAMID metadata registry.</p>
    <p>You have received this email because you are either the technical and/or administrative contact of a related IdP.</p>
    <p>You can view information about your IMPS at
    <a href=\"%sadmin/?showEntity=%d\">%sadmin/?showEntity=%d</a> .</p>
    <p>This is a message from the SWAMID SAML WebSSO metadata administration tool.<br>
    --<br>
    On behalf of SWAMID Operations</p>\n  </body>\n</html>",
    $name, $config->baseURL(), $id, $config->baseURL(), $id);
    $mailContacts->AltBody = sprintf("Hi.\n\nThe Identity Management Practice Statement (IMPS) for \"%s\" has not been validated/confirmed.
    Current approved IMPS is based on a earlier version of the assurance profile.
    The SWAMID Assurance Profiles requires an annual confirmation that the IMPS is still accurate
    and that the Identity Providers adhere to it. If not annually confirmed the Operations team will start the process
    to remove the entity related to this IMPS from SWAMID metadata registry.
    \nYou have received this email because you are either the technical and/or administrative contact of a related IdP.</p>
    \nYou can view information about your IMPS at %sadmin/?showEntity=%d .
    \nThis is a message from the SWAMID SAML WebSSO metadata administration tool.
    --
    On behalf of SWAMID Operations",
    $name, $config->baseURL(), $id);
  } else {
    $mailContacts->Body    = sprintf("<html>\n  <body>
    <p>Hi.</p>
    <p>The Identity Management Practice Statement (IMPS) for \"%s\" has not been validated/confirmed for %d months.
    The SWAMID Assurance Profiles requires an annual confirmation that the IMPS is still accurate
    and that the Identity Providers adhere to it. If not annually confirmed the Operations team will start the process
    to remove the entity related to this IMPS from SWAMID metadata registry.</p>
    <p>You have received this email because you are either the technical and/or administrative contact of a related IdP.</p>
    <p>You can validate/confirm your IMPS at
    <a href=\"%sadmin/?showEntity=%d\">%sadmin/?showEntity=%d</a> .</p>
    <p>This is a message from the SWAMID SAML WebSSO metadata administration tool.<br>
    --<br>
    On behalf of SWAMID Operations</p>\n  </body>\n</html>",
    $name, $months, $config->baseURL(), $id, $config->baseURL(), $id);
    $mailContacts->AltBody = sprintf("Hi.\n\nThe Identity Management Practice Statement (IMPS) for \"%s\" has not been validated/confirmed for %d months.
    The SWAMID Assurance Profiles requires an annual confirmation that the IMPS is still accurate
    and that the Identity Providers adhere to it. If not annually confirmed the Operations team will start the process
    to remove the entity related to this IMPS from SWAMID metadata registry.
    \nYou have received this email because you are either the technical and/or administrative contact of a related IdP.</p>
    \nYou can validate/confirm your IMPS at %sadmin/?showEntity=%d .
    \nThis is a message from the SWAMID SAML WebSSO metadata administration tool.
    --
    On behalf of SWAMID Operations",
    $name, $months, $config->baseURL(), $id);
  }
  $mailContacts->Subject  = 'Warning : SWAMID IMPS ' . $name . ' needs to be validated';

  try {
    $mailContacts->send();
  } catch (Exception $e) {
    echo 'Message could not be sent to contacts.<br>' . "\n";
  }
}

function getTechnicalAndAdministrativeContacts($id) {
  global $config;
  $addresses = array();

  $contactHandler = $config->getDb()->prepare("SELECT DISTINCT emailAddress
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
  global $config;

  $userHandler = $config->getDb()->prepare("SELECT DISTINCT `email`
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
  global $config;
  $addresses = array();

  $userHandler = $config->getDb()->prepare("SELECT DISTINCT `email`
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
  global $config, $mailContacts;

  $mailContacts = new PHPMailer(true);
  $mailContacts->isSMTP();
  $mailContacts->Host = $config->getSmtp()['host'];
  $mailContacts->Port = $config->getSmtp()['port'];
  $mailContacts->SMTPAutoTLS = true;
  if ($config->smtpAuth()) {
    $mailContacts->SMTPAuth = true;
    $mailContacts->Username = $config->getSmtp()['sasl']['user'];
    $mailContacts->Password = $config->getSmtp()['sasl']['password'];
    $mailContacts->SMTPSecure = 'tls';
  }

  //Recipients
  $mailContacts->setFrom($config->getSmtp()['from'], 'Metadata - Admin');
  if ($config->getSMTP()['bcc']) {
    $mailContacts->addBCC($config->getSMTP()['bcc']);
  }
  $mailContacts->addReplyTo($config->getSMTP()['replyTo'], $config->getSMTP()['replyName']);
}
