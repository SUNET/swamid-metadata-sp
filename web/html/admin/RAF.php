<?php
require_once '../config.php'; # NOSONAR

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

$idpCountHandler = $db->prepare(
  'SELECT COUNT(DISTINCT `entityID`) as `idps` FROM `assuranceLog`');
$idpCountHandler->execute();
if ($idpCountRow = $idpCountHandler->fetch(PDO::FETCH_ASSOC)) {
  $idps = $idpCountRow['idps'];
} else {
  $idps = 0;
}

$idpAssuranceHandler = $db->prepare(
  'SELECT COUNT(`entityID`) as `count`, `assurance` FROM `assuranceLog` GROUP BY `assurance`');
$idpAssuranceHandler->execute();
$assuranceCount = array(
  'SWAMID-AL1' => 0,
  'SWAMID-AL2' => 0,
  'SWAMID-AL3' => 0,
  'RAF-low' => 0,
  'RAF-medium' => 0,
  'RAF-high' => 0,
  'None' => 0);
while ($idpAssuranceRow = $idpAssuranceHandler->fetch(PDO::FETCH_ASSOC)) {
  $assuranceCount[$idpAssuranceRow['assurance']] = $idpAssuranceRow['count'];
}

$metaAssuranceHandler = $db->prepare(
  "SELECT COUNT(`id`) AS `count`, `attribute`
  FROM `Entities`, `EntityAttributes`
  WHERE `Entities`.`id` = `EntityAttributes`.`entity_id`
    AND `status` = 1
    AND `isIdP` = 1
    AND `publishIn` > 1
    AND `type` = 'assurance-certification'
  GROUP BY `attribute`");

$metaAssuranceHandler->execute();
$metaAssuranceCount = array(
  'SWAMID-AL1' => 0,
  'SWAMID-AL2' => 0,
  'SWAMID-AL3' => 0);
while ($metaAssuranceRow = $metaAssuranceHandler->fetch(PDO::FETCH_ASSOC)) {
  $metaAssuranceCount[$metaAssuranceRow['attribute']] = $metaAssuranceRow['count'];
}

$displayName = '<div> Logged in as : <br> ' . $fullName . ' (' . $EPPN .')</div>';
$html->setDisplayName($displayName);
$html->showHeaders('Metadata SWAMID - RAF status');
printf('    <div class="row">
      <div class="col">
        <div class="row"><div class="col">Total nr of IdP:s</div><div class="col">%d</div></div>
        <div class="row"><div class="col">&nbsp;</div></div>
        <div class="row"><div class="col">Max SWAMID AL3</div><div class="col">%d</div></div>
        <div class="row"><div class="col">Max SWAMID AL2</div><div class="col">%d</div></div>
        <div class="row"><div class="col">Max SWAMID AL1</div><div class="col">%d</div></div>
        <div class="row"><div class="col">No SWAMID AL</div><div class="col">%d</div></div>
        <div class="row"><div class="col">&nbsp;</div></div>
        <div class="row"><div class="col">Max RAF High</div><div class="col">%d</div></div>
        <div class="row"><div class="col">Max RAF Medium</div><div class="col">%d</div></div>
        <div class="row"><div class="col">Max RAF Low</div><div class="col">%d</div></div>
        <div class="row"><div class="col">No RAF</div><div class="col">%d</div></div>
      </div>
      <div class="col">
        <h3>SWAMID</h3>
        <canvas id="swamid"></canvas>
      </div>
      <div class="col">
        <h3>REFEDS</h3>
        <canvas id="raf"></canvas>
      </div>
      <div class="col">
        <h3>Assurance in metadata</h3>
        <canvas id="meta"></canvas>
      </div>
    </div>
    <br>
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
      </tr>%s',
  $idps,
  $assuranceCount['SWAMID-AL3'],
  $assuranceCount['SWAMID-AL2'] - $assuranceCount['SWAMID-AL3'],
  $assuranceCount['SWAMID-AL1'] - $assuranceCount['SWAMID-AL2'],
  $idps - $assuranceCount['SWAMID-AL1'],
  $assuranceCount['RAF-high'],
  $assuranceCount['RAF-medium'] - $assuranceCount['RAF-high'],
  $assuranceCount['RAF-low'] - $assuranceCount['RAF-medium'],
  $idps - $assuranceCount['RAF-low'],
  "\n");

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

printf('      <script src="/include/chart/chart.min.js"></script>
      <script>
        const ctxswamid = document.getElementById(\'swamid\').getContext(\'2d\');
        const myswamid = new Chart(ctxswamid, {
          width: 200,
          type: \'pie\',
          data: {
            labels: [\'AL3\', \'AL2\', \'AL1\', \'None\'],
            datasets: [{
              label: \'SWAMID\',
              data: [%d, %d, %d, %d],
              backgroundColor: [
                \'rgb(99, 255, 132)\',
                \'rgb(255, 205, 86)\',
                \'rgb(255, 99, 132)\',
                \'rgb(255, 255, 255)\',
              ],
              borderColor : \'rgb(0,0,0)\',
              hoverOffset: 4
            }]
          },
        });
      </script>
      <script>
        const ctxraf = document.getElementById(\'raf\').getContext(\'2d\');
        const myraf = new Chart(ctxraf, {
          width: 200,
          type: \'pie\',
          data: {
            labels: [\'High\', \'Medium\', \'Low\', \'None\'],
            datasets: [{
              label: \'RAF\',
              data: [%d, %d, %d, %d],
              backgroundColor: [
                \'rgb(99, 255, 132)\',
                \'rgb(255, 205, 86)\',
                \'rgb(255, 99, 132)\',
                \'rgb(255, 255, 255)\',
              ],
              borderColor : \'rgb(0,0,0)\',
              hoverOffset: 4
            }]
          },
        });
      </script>
      <script>
        const ctxmeta = document.getElementById(\'meta\').getContext(\'2d\');
        const mymeta = new Chart(ctxmeta, {
          width: 200,
          type: \'pie\',
          data: {
            labels: [\'AL3\', \'AL2\', \'AL1\'],
            datasets: [{
              label: \'Metadata\',
              data: [%d, %d, %d],
              backgroundColor: [
                \'rgb(99, 255, 132)\',
                \'rgb(255, 205, 86)\',
                \'rgb(255, 99, 132)\',
              ],
              borderColor : \'rgb(0,0,0)\',
              hoverOffset: 4
            }]
          },
        });
      </script>',
  $assuranceCount['SWAMID-AL3'],
  $assuranceCount['SWAMID-AL2'] - $assuranceCount['SWAMID-AL3'],
  $assuranceCount['SWAMID-AL1'] - $assuranceCount['SWAMID-AL2'],
  $idps - $assuranceCount['SWAMID-AL1'],
  $assuranceCount['RAF-high'],
  $assuranceCount['RAF-medium'] - $assuranceCount['RAF-high'],
  $assuranceCount['RAF-low'] - $assuranceCount['RAF-medium'],
  $idps - $assuranceCount['RAF-low'],
  $metaAssuranceCount['http://www.swamid.se/policy/assurance/al3'],
  $metaAssuranceCount['http://www.swamid.se/policy/assurance/al2'] -
    $metaAssuranceCount['http://www.swamid.se/policy/assurance/al3'],
  $metaAssuranceCount['http://www.swamid.se/policy/assurance/al1'] -
    $metaAssuranceCount['http://www.swamid.se/policy/assurance/al2']);

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
