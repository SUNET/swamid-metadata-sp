<?php

const HTML_OUTLINE = '-outline';

//Load composer's autoloader
require_once '../vendor/autoload.php';

$config = new metadata\Configuration();

require_once '../include/Html.php';
$html = new HTML($config);

if (isset($_SERVER['eduPersonPrincipalName'])) {
  $EPPN = $_SERVER['eduPersonPrincipalName'];
} elseif (isset($_SERVER['subject-id'])) {
  $EPPN = $_SERVER['subject-id'];
} else {
  $errors .= 'Missing eduPersonPrincipalName/subject-id in SAML response ' .
    str_replace(
      array('ERRORURL_CODE', 'ERRORURL_CTX'),
      array('IDENTIFICATION_FAILURE', 'eduPersonPrincipalName'),
      $errorURL);
}

if (isset($_SERVER['displayName'])) {
  $fullName = $_SERVER['displayName'];
} elseif (isset($_SERVER['givenName'])) {
  $fullName = $_SERVER['givenName'];
  if (isset($_SERVER['sn'])) {
    $fullName .= ' ' .$_SERVER['sn'];
  }
} else  {
  $fullName = '';
}

$userLevel = $config->getUserLevels()[$EPPN] ?? 1;
if ($userLevel < 5) { exit; };
$displayName = '<div> Logged in as : <br> ' . $fullName . ' (' . $EPPN .')</div>';
$html->setDisplayName($displayName);
$collapseIcons = array();

try {
  $fileName = __DIR__ . "/../MDS.db";
  $dsn = "sqlite:$fileName";

  $db = new PDO($dsn);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

$html->showHeaders('MDS info');
if (isset($_GET['action'])) {
  switch($_GET['action']) {
    case 'hosts' :
      $menuActive = 'hosts';
      showMenu();
      showHosts();
      $html->addTableSort('host-table');
      break;
    case 'software' :
      $menuActive = 'software';
      showMenu();
      showSoftwares();
      break;
    case 'feeds' :
      $menuActive = 'feeds';
      showMenu();
      showFeeds();
      $html->addTableSort('feed-table');
      break;
    default :
  }
} else {
  showMenu();
  showHosts();
  $html->addTableSort('host-table');
}

$html->showFooter($collapseIcons);
# End of page

function showSoftwares(){
  global $db, $collapseIcons;
  $softwareAgeHandler = $db->prepare('SELECT MIN(`lastSeen`) AS startSeen FROM `hostSoftware`');
  $softwaresHandler = $db->prepare(
    'SELECT COUNT (`hosts_id`) AS count, `id`, `name`
    FROM `softwares`, `hostSoftware`
    WHERE `id` = `hostSoftware`.`softwares_id`
    GROUP BY `id`
    ORDER BY `name` COLLATE NOCASE ASC');
  $hostHandler = $db->prepare(
    'SELECT `id`, `ip`, `name`, `lastSeen`
    FROM `hosts`, `hostSoftware`
    WHERE `hosts_id` = `hosts`.`id` AND `softwares_id` = :SoftwareID
    ORDER BY `ip`');
  $softwareAgeHandler->execute();
  if ($softwareAge = $softwareAgeHandler->fetch(PDO::FETCH_ASSOC)) {
    printf('
    <h5>Software seen since %s</h5>', $softwareAge['startSeen']);
  }
  $softwaresHandler->execute();
  while ($software = $softwaresHandler->fetch(PDO::FETCH_ASSOC)) {
    $name = 'id' . $software['id'];
    $show = '';
    printf('
    <h5>
      <i id="%s-icon" class="fas fa-chevron-circle-right"></i>
      <a data-toggle="collapse" href="#%s" aria-expanded="false" aria-controls="%s">%s (%d)</a>
    </h5>
    <div class="%scollapse multi-collapse" id="%s">
      <div class="row">
        <div class="col"><ul>%s', $name, $name, $name, $software['name'], $software['count'], $show, $name, "\n");
    $collapseIcons[] = $name;
    $hostHandler->execute(array('SoftwareID' => $software['id']));
    while ($host = $hostHandler->fetch(PDO::FETCH_ASSOC)){
      printf('        <li><a href="?action=hosts&host=%d">%s</a> (%s) - %s</li>%s',
        $host['id'], $host['ip'], $host['name'], $host['lastSeen'], "\n");
    }
    printf('        </ul></div><!-- end col -->
      </div><!-- end row -->
    </div><!-- end collapse %s-->', $name);
  }
}

function showHosts(){
  global $db;
  if (isset($_GET['host'])) {
    $hostHandler = $db->prepare(
      'SELECT `id`, `ip`, `name`
      FROM `hosts`
      WHERE `id` = :Host_id');
    $hostHandler->execute(array('Host_id' => $_GET['host']));
    if ($host = $hostHandler->fetch(PDO::FETCH_ASSOC)) {
      $softwaresHandler = $db->prepare(
        'SELECT `name`, `lastSeen`
        FROM `softwares`, `hostSoftware`
        WHERE `hosts_id` = :Host_id AND `softwares_id` = `softwares`.`id`
        ORDER BY `name` COLLATE NOCASE ASC');
      $feedsHandler = $db->prepare(
        'SELECT `name`, `lastSeen`
        FROM `feeds`, `hostFeed`
        WHERE `hosts_id` = :Host_id AND `feeds_id` = `feeds`.`id`');
      $softwaresHandler->execute(array('Host_id' => $_GET['host']));
      $feedsHandler->execute(array('Host_id' => $_GET['host']));
      printf ('        <h5>%s (%s)</h5>
        <h5>Softwares:</h5>
        <ul>%s',
        $host['ip'], $host['name'], "\n");
      while ($software = $softwaresHandler->fetch(PDO::FETCH_ASSOC)) {
        printf ('        <li>%s - %s</li>%s', $software['name'], $software['lastSeen'], "\n");
      }
      printf ('        </ul>
        <h5>Feeds:</h5>
        <ul>%s', "\n");
      while ($feed = $feedsHandler->fetch(PDO::FETCH_ASSOC)) {
        printf ('        <li>%s - %s</li>%s', $feed['name'], $feed['lastSeen'], "\n");
      }
      printf ('        </ul>%s', "\n");
    }
    printf ('        <hr>%s', "\n");

  }
  $hostsHandler = $db->prepare(
    'SELECT `hosts`.`id`, `ip`, `hosts`.`name` AS hName, `softwares`.`name` AS sName, lastSeen
    FROM `hosts`, `softwares`, `hostSoftware`
    WHERE `hosts_id` = `hosts`.`id` AND `softwares_id` = `softwares`.`id`');
  $hostsHandler->execute();
  printf ('        <h5>Hosts</h5>
  <table id="host-table" class="table table-striped table-bordered">
    <thead><tr><th>IP</th><th>Name</th><th>Software</th><th>Last seen</th></tr></thead>%s', "\n");
  while ($host = $hostsHandler->fetch(PDO::FETCH_ASSOC)) {
    printf('          <tr><td><a href="?action=hosts&host=%d">%s</a></td></td><td>%s</td><td>%s</td><td>%s</td></tr>%s',
      $host['id'], $host['ip'], $host['hName'], $host['sName'], $host['lastSeen'], "\n");
  }
  printf ('        </table>%s', "\n");
}

function printFeedRow($hostInfo, $feeds) {
  printf('    <tr><td>%s</td>', $hostInfo);
  foreach ($feeds as $feed) {
    printf('<td>%s</td>', $feed ? 'X' : '');
  }
  print "</tr>\n";
}

function showFeeds() {
  global $db;
  $feedAgeHandler = $db->prepare('SELECT MIN(`lastSeen`) AS startSeen FROM `hostFeed`');
  $feedsHandler = $db->prepare('SELECT * FROM feeds ORDER BY name');
  $hostsHandler = $db->prepare(
    'SELECT `hosts`.`id`, `ip`, `hosts`.`name`, `feeds_id`
    FROM `hosts`, `hostFeed`
    WHERE `hosts_id` = `hosts`.`id`
    ORDER BY `ip`');
  $feedAgeHandler->execute();
  $feedAge = $feedAgeHandler->fetch(PDO::FETCH_ASSOC);
  printf ('        <h5>Feeds seen since %s</h5>
  <table id="feed-table" class="table table-striped table-bordered">
    <thead><tr><th>Host</th>', $feedAge['startSeen']);
  $feedsHandler->execute();
  $feedCount = 0;
  while ($feed = $feedsHandler->fetch(PDO::FETCH_ASSOC)) {
    $feedArray[$feed['id']] = ++$feedCount;
    $name = str_replace(array('swamid-', '.xml'), '', $feed['name']);
    printf ('<th>%s</th>', $name);
  }
  printf ('</tr></thead>%s', "\n");

  $hostsHandler->execute();
  $oldId = 0;
  while ($host = $hostsHandler->fetch(PDO::FETCH_ASSOC)) {
    if ($oldId == 0) {
      $oldId = $host['id'];
      $hostInfo = sprintf('<a href="?action=hosts&host=%d">%s</a> %s', $host['id'], $host['ip'], $host['name']);
      for ($i = 1; $i <= $feedCount; $i++) {
        $feedListArray[$i] = false;
      }
    }
    if ($oldId != $host['id']) {
      printFeedRow($hostInfo, $feedListArray);
      $oldId = $host['id'];
      $hostInfo = sprintf('<a href="?action=hosts&host=%d">%s</a> %s', $host['id'], $host['ip'], $host['name']);
      for ($i = 1; $i <= $feedCount; $i++) {
        $feedListArray[$i] = false;
      }
    }
    $feedListArray[$feedArray[$host['feeds_id']]] = true;
  }
  if ($oldId > 0) {
    printFeedRow($hostInfo, $feedListArray);
  }
  printf ('        </table>%s', "\n");
}

####
# Shows menu row
####
function showMenu() {
  global $menuActive;

  print "\n    ";
  printf('<a href="?action=hosts"><button type="button" class="btn btn%s-primary">Hosts</button></a>', $menuActive == 'hosts' ? '' : HTML_OUTLINE);
  printf('<a href="?action=software"><button type="button" class="btn btn%s-primary">Software</button></a>', $menuActive == 'software' ? '' : HTML_OUTLINE);
  printf('<a href="?action=feeds"><button type="button" class="btn btn%s-primary">Feeds</button></a>', $menuActive == 'feeds' ? '' : HTML_OUTLINE);
  print "\n    <br>\n    <br>\n";
}
