<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load composer's autoloader
require_once '/var/www/html/vendor/autoload.php';

include_once "/var/www/html/config.php";  # NOSONAR

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

$updateMailRemindersHandler = $db->prepare('INSERT INTO MailReminders (`entity_id`, `type`, `level`, `mailDate`)
  VALUES (:Entity_Id, :Type, :Level, NOW()) ON DUPLICATE KEY UPDATE `level` = :Level, `mailDate` = NOW()');
$getMailRemindersHandler = $db->prepare(
  'SELECT `id`, `level`
  FROM Entities
  LEFT JOIN MailReminders ON `entity_id` = `id` AND `type` = :Type');

# Time to confirm entities again ?
$reminders = array();
$getMailRemindersHandler->execute(array('Type' => 1));
while ($entity = $getMailRemindersHandler->fetch(PDO::FETCH_ASSOC)) {
  $reminders[$entity['id']] = $entity['level'];
}
$getMailRemindersHandler->closeCursor();

$flagDates = $db->query('SELECT NOW() - INTERVAL 11 MONTH AS `warnDate`,
  NOW() - INTERVAL 12 MONTH AS `errorDate`', PDO::FETCH_ASSOC);
foreach ($flagDates as $dates) {
  # Need to use foreach to fetch row. $flagDates is a PDOStatement
  $warnDate = $dates['warnDate'];
  $errorDate = $dates['errorDate'];
}
$flagDates->closeCursor();

$entitiesHandler = $db->prepare("SELECT Entities.`id`, `entityID`, `lastConfirmed`
  FROM Entities
  LEFT JOIN EntityConfirmation ON EntityConfirmation.entity_id = id
  WHERE `status` = 1 AND publishIn > 1 ORDER BY `entityID`");

$entitiesHandler->execute();
while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
  if ($errorDate > $entity['lastConfirmed'] && $reminders[$entity['id']] < 2) {
    printf('Error %s%s', $entity['lastConfirmed'], "\n");
    $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 1, 'Level' => 2));
    sendEntityConfirmation($entity['id'], $entity['entityID'], 12);
  } elseif ($warnDate > $entity['lastConfirmed'] && $reminders[$entity['id']] < 1) {
    printf('Warn %s%s', $entity['lastConfirmed'], "\n");
    $updateMailRemindersHandler->execute(array('Entity_Id' => $entity['id'], 'Type' => 1, 'Level' => 1));
    sendEntityConfirmation($entity['id'], $entity['entityID'], 11);
  }
}
$entitiesHandler->closeCursor();


function sendEntityConfirmation($id, $entityID, $months) {
  global $SMTPHost, $SASLUser, $SASLPassword, $MailFrom, $SendOut, $baseURL;
 
  $mailContacts = new PHPMailer(true);
  $mailContacts->isSMTP();
  $mailContacts->Host = $SMTPHost;
  $mailContacts->SMTPAuth = true;
  $mailContacts->SMTPAutoTLS = true;
  $mailContacts->Port = 587;
  $mailContacts->SMTPAuth = true;
  $mailContacts->Username = $SASLUser;
  $mailContacts->Password = $SASLPassword;
  $mailContacts->SMTPSecure = 'tls';
  
  //Recipients
  $mailContacts->setFrom($MailFrom, 'Metadata - Admin');
  $mailContacts->addBCC('bjorn@sunet.se');
  $mailContacts->addReplyTo('operations@swamid.se', 'SWAMID Operations');
  
  $addresses = getTechnicalAndAdministrativeContacts($id);
  if ($SendOut) {
    foreach ($addresses as $address) {
      $mailContacts->addAddress($address);
    }
  }

  //Content
  $mailContacts->isHTML(true);
  $mailContacts->Body    = sprintf("<html>\n  <body>
    <p>Hi.</p>
    <p>Entity %s has not been validated/confirmed for %d months.</p>
    <p>You have received this mail because you are either the technical and/or administrative contact.</p>
    <p>You can update your entity at <a href=\"%s/admin/?showEntity=%d\">%s/admin/?showEntity=%d</a></p>
  </body>\n</html>",
    $entityID, $months, $baseURL, $id, $baseURL, $id);
  $mailContacts->AltBody = sprintf("Hi.\n\nEntity %s has not been validated/confirmed for %d months.
    \nYou have received this mail because you are either the technical and/or administrative contact.
    \nYou can update your entity at %s/admin/?showEntity=%d",
    $entityID, $months, $baseURL, $id);

  $shortEntityid = preg_replace('/^https?:\/\/([^:\/]*)\/.*/', '$1', $entityID);
  $mailContacts->Subject  = 'Warning : SWAMID metadata for ' . $shortEntityid . ' needs to be validated';
  
  try {
    $mailContacts->send();
  } catch (Exception $e) {
    echo 'Message could not be sent to contacts.<br>';
    echo 'Mailer Error: ' . $mailContacts->ErrorInfo . '<br>';
  }

}

function getTechnicalAndAdministrativeContacts($id) {
  global $db;
  $addresses = array();

  $contactHandler = $db->prepare("SELECT DISTINCT emailAddress
    FROM Entities, ContactPerson
    WHERE id = entity_id
      AND id = :ID AND status = 1
      AND (contactType='technical' OR contactType='administrative')
      AND emailAddress <> ''");
  $contactHandler->execute(array('ID' => $id));
  while ($address = $contactHandler->fetch(PDO::FETCH_ASSOC)) {
    $addresses[] = substr($address['emailAddress'],7);
  }
  return $addresses;
}
