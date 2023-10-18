<?php
require_once '../config.php';

require_once '../include/Html.php';
$html = new HTML('', $Mode);

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

if (isset($_SERVER['eduPersonPrincipalName'])) {
  $EPPN = $_SERVER['eduPersonPrincipalName'];
} elseif (isset($_SERVER['subject-id'])) {
  $EPPN = $_SERVER['subject-id'];
} else {
  $EPPN = '';
}

if (isset($_SERVER['displayName'])) {
  $fullName = $_SERVER['displayName'];
} elseif (isset($_SERVER['givenName'])) {
  $fullName = $_SERVER['givenName'];
  $fullName .= isset($_SERVER['sn']) ? ' ' .$_SERVER['sn'] : '';
} else {
  $fullName = '';
}

$displayName = '<div> Logged in as : <br> ' . $fullName . ' (' . $EPPN .')</div>';
$html->setDisplayName($displayName);
$html->showHeaders('Metadata SWAMID - RAF status');
printf('
    <table class="table table-striped table-bordered">
      <tr>
        <th>IdP</th>
        <th>AL1</th>
        <th>AL2</th>
        <th>AL3</th>
        <th>RAF-Low</th>
        <th>RAF-Medium</th>
        <th>RAF-High</th>
        <th>Nothing</th>
      </tr>%s', "\n");

$assuranceHandler = $db->prepare(
  'SELECT `entityID`, `assurance`, `logDate`
  FROM `assuranceLog` ORDER BY `entityID`, `assurance`');
$assuranceHandler->execute();
$oldIdp = false;
$assurance = array();
$assurance['SWAMID-AL1'] = '';
$assurance['SWAMID-AL2'] = '';
$assurance['SWAMID-AL3'] = '';
$assurance['RAF-low'] = '';
$assurance['RAF-medium'] = '';
$assurance['RAF-high'] = '';
$assurance['None'] = '';


while ($assuranceRow = $assuranceHandler->fetch(PDO::FETCH_ASSOC)) {
  if($assuranceRow['entityID'] != $oldIdp) {
    if ($oldIdp) {
      printRow($oldIdp, $assurance);
    }
    $oldIdp = $assuranceRow['entityID'];
    $assurance['SWAMID-AL1'] = '';
    $assurance['SWAMID-AL2'] = '';
    $assurance['SWAMID-AL3'] = '';
    $assurance['RAF-low'] = '';
    $assurance['RAF-medium'] = '';
    $assurance['RAF-high'] = '';
    $assurance['None'] = '';
  }
  $assurance[$assuranceRow['assurance']] = $assuranceRow['logDate'];
}
if ($oldIdp) {
  printRow($oldIdp, $assurance);
}
print "    </table>\n    <br>\n";
$html->showFooter(array());
# End of page

function printRow($idp, $assurance) {
  printf('      <tr>
      <td>%s</td>
      <td>%s</td><td>%s</td><td>%s</td>
      <td>%s</td><td>%s</td><td>%s</td>
      <td>%s</td>
    </tr>%s',
    $idp,
    $assurance['SWAMID-AL1'],
    $assurance['SWAMID-AL2'],
    $assurance['SWAMID-AL3'],
    $assurance['RAF-low'],
    $assurance['RAF-medium'],
    $assurance['RAF-high'],
    $assurance['None'], "\n");
}