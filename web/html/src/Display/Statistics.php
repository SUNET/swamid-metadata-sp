<?php
namespace metadata\Display;

use PDO;

/**
 * Class to display Metadata Statistics
 */
class Statistics extends Common {

  /**
   * Show Statistics
   *
   * @return void
   */
  public function showStatistics() {
    # Default values
    $entityActive='';
    $entitySelected='false';
    $entityShow='';
    #
    $ecActive='';
    $ecSelected='false';
    $ecShow='';
    #
    $ecsActive='';
    $ecsSelected='false';
    $ecsShow='';
    #
    $acActive='';
    $acSelected='false';
    $acShow='';
    #
    $assuranceActive='';
    $assuranceSelected='false';
    $assuranceShow='';

    if (isset($_GET["tab"])) {
      switch ($_GET["tab"]) {
        case 'ec' :
          $ecActive = self::HTML_ACTIVE;
          $ecSelected = self::HTML_TRUE;
          $ecShow = self::HTML_SHOW;
          break;
        case 'ecs' :
          $ecsActive = self::HTML_ACTIVE;
          $ecsSelected = self::HTML_TRUE;
          $ecsShow = self::HTML_SHOW;
          break;
        case 'ac' :
          $acActive = self::HTML_ACTIVE;
          $acSelected = self::HTML_TRUE;
          $acShow = self::HTML_SHOW;
          break;
        case 'assurance' :
          $assuranceActive = self::HTML_ACTIVE;
          $assuranceSelected = self::HTML_TRUE;
          $assuranceShow = self::HTML_SHOW;
          break;
        case 'entity' :
        default :
          $entityActive = self::HTML_ACTIVE;
          $entitySelected = self::HTML_TRUE;
          $entityShow = self::HTML_SHOW;
        }
    } else {
      $entityActive = self::HTML_ACTIVE;
      $entitySelected = self::HTML_TRUE;
      $entityShow = self::HTML_SHOW;
    }
    printf('    <div class="row">
      <div class="col">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
          <li class="nav-item">
            <a class="nav-link%s" id="entity-tab" data-toggle="tab" href="#entity" role="tab"
              aria-controls="entity" aria-selected="%s">Entity Statistics</a>
          </li>
          <li class="nav-item">
            <a class="nav-link%s" id="ec-tab" data-toggle="tab" href="#ec" role="tab"
              aria-controls="ec" aria-selected="%s">Entity Category</a>
          </li>
          <li class="nav-item">
            <a class="nav-link%s" id="ecs-tab" data-toggle="tab" href="#ecs" role="tab"
              aria-controls="ecs" aria-selected="%s">Entity Category Support</a>
          </li>
          <li class="nav-item">
            <a class="nav-link%s" id="ac-tab" data-toggle="tab" href="#ac" role="tab"
              aria-controls="ac" aria-selected="%s">Assurance Certification</a>
          </li>
          <li class="nav-item">
            <a class="nav-link%s" id="assurance-tab" data-toggle="tab" href="#assurance" role="tab"
              aria-controls="assurance" aria-selected="%s">Assurance</a>
          </li>%s',
      $entityActive, $entitySelected, $ecActive, $ecSelected,
      $ecsActive, $ecsSelected, $acActive, $acSelected, $assuranceActive, $assuranceSelected, "\n");
    printf('        </ul>
      </div>%s    </div>%s    <script src="/include/chart/chart.umd.js"></script>%s    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade%s%s" id="entity" role="tabpanel" aria-labelledby="entity-tab">%s',
        "\n", "\n", "\n",
      $entityShow, $entityActive, "\n");
    $this->showEntityStatistics();
    printf('      </div><!-- End tab-pane entity -->
      <div class="tab-pane fade%s%s" id="ec" role="tabpanel" aria-labelledby="ec-tab">%s',
      $ecShow, $ecActive, "\n");
    $this->showEcStatistics();
    printf('      </div><!-- End tab-pane ec -->
      <div class="tab-pane fade%s%s" id="ecs" role="tabpanel" aria-labelledby="ecs-tab">%s',
        $ecsShow, $ecsActive, "\n");
    $this->showEcsStatistics();
    printf('      </div><!-- End tab-pane ecs -->
      <div class="tab-pane fade%s%s" id="ac" role="tabpanel" aria-labelledby="ac-tab">%s',
        $acShow, $acActive, "\n");
    $this->showAcStatistics();
    printf('      </div><!-- End tab-pane ac -->
      <div class="tab-pane fade%s%s" id="assurance" role="tabpanel" aria-labelledby="assurance-tab">%s',
      $assuranceShow, $assuranceActive, "\n");
    $this->showRAFStatistics();
    printf('      </div><!-- End tab-pane assurance -->
    </div><!-- End tab-content -->%s', "\n");
  }

  /**
   * Show EntityStatistics over time
   *
   * @return void
   */
  protected function showEntityStatistics() {
    $labelsArray = array();
    $spArray = array();
    $idpArray = array();

    $nrOfEntites = 0;
    $nrOfSPs = 0;
    $nrOfIdPs = 0;

    $federation = $this->config->getFederation();
    $entitys = $this->config->getDb()->prepare(
      "SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn` FROM `Entities` WHERE `status` = 1 AND `publishIn` > 1;");
    $entitys->execute();
    while ($row = $entitys->fetch(PDO::FETCH_ASSOC)) {
      switch ($row['publishIn']) {
        case 1 :
          break;
        case 2 :
        case 3 :
        case 6 :
        case 7 :
          $nrOfEntites ++;
          $nrOfIdPs += $row['isIdP'] ? 1 : 0;
          $nrOfSPs += $row['isSP'] ? 1 : 0;
          break;
        default :
          printf ("Can't resolve publishIn = %d for enityID = %s", $row['publishIn'], $row['entityID']);
      }
    }

    printf ('        <h3>Entity Statistics</h3>
        <p>Statistics on number of entities in %s.</p>
        <canvas id="total" width="200" height="50"></canvas>
        <br><br>
        <h3>Statistics in numbers</h3>
        <table class="table table-striped table-bordered">
          <tr><th>Date</th><th>NrOfEntites</th><th>NrOfSPs</th><th>NrOfIdPs</th></tr>%s', $federation['displayName'], "\n");
    printf('          <tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td></tr>%s',
      'Now', $nrOfEntites, $nrOfSPs, $nrOfIdPs, "\n");
    array_unshift($labelsArray, 'Now');
    array_unshift($spArray, $nrOfSPs);
    array_unshift($idpArray, $nrOfIdPs);

    $statusRows = $this->config->getDb()->prepare(
      "SELECT `date`, `NrOfEntites`, `NrOfSPs`, `NrOfIdPs` FROM `EntitiesStatistics` ORDER BY `date` DESC;");
    $statusRows->execute();
    while ($row = $statusRows->fetch(PDO::FETCH_ASSOC)) {
      $dateLabel = substr($row['date'],2,8);
      printf('          <tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td></tr>%s',
        substr($row['date'],0,10), $row['NrOfEntites'], $row['NrOfSPs'], $row['NrOfIdPs'], "\n");
      array_unshift($labelsArray, $dateLabel);
      array_unshift($spArray, $row['NrOfSPs']);
      array_unshift($idpArray, $row['NrOfIdPs']);
    }
    $labels = implode("','", $labelsArray);
    $idps = implode(',', $idpArray);
    $sps = implode(',', $spArray);

    printf ('    %s        <script>%s', self::HTML_TABLE_END, "\n", "\n");
    printf ("          const ctxTotal = document.getElementById('total').getContext('2d');
          const myTotal = new Chart(ctxTotal, {
            type: 'line',
            data: {
              labels: ['%s'],
              datasets: [{
                label: 'IdP',
                backgroundColor: \"rgb(240,85,35)\",
                data: [%s],
                fill: 'origin'
              }, {
                label: 'SP',
                backgroundColor: \"rgb(2,71,254)\",
                data: [%s],
                fill: 0
              }]
            },
            options: {
              responsive: true,
              scales: {
                y: {
                  beginAtZero: true,
                  stacked: true,
                }
              }
            }
          });%s        </script>%s",
      $labels, $idps, $sps, "\n", "\n");
  }

  /**
   * Show Graph for EntityCategory
   *
   * @param string $canvas id for canvas where to display the graph
   *
   * @param int $marked Number of SP:s with this Entity Category
   *
   * @param int $total Total numer of SP:s
   *
   * @return void
   */
  protected function showEcGraph($canvas, $marked, $total) {
    printf ("          const ctxEC%s = document.getElementById('ec_%s').getContext('2d');
          const myEC%s = new Chart(ctxEC%s, {
            width: 200,
            type: 'pie',
            data: {
              labels: ['This Category', 'No/other Category'],
              datasets: [{
                data: [%d, %d],
                backgroundColor: [
                  'rgb(99, 255, 132)',
                  'rgb(255, 255, 255)',
                ],
                borderColor : 'rgb(0,0,0)',
                hoverOffset: 4
              }]
            },
          });%s",
      $canvas, $canvas, $canvas, $canvas, $marked, $total - $marked, "\n");
  }

  /**
   * Show Graph for EntityCategory Code Of Conduct
   *
   * @param int $both Number of SP:s with both CoCo v1 and CoCo v2
   *
   * @param int $cocov1 Number of SP:s with CoCo v1
   *
   * @param int $cocov2 Number of SP:s with CoCo v2
   *
   * @param int $total Total numer of SP:s
   *
   * @return void
   */
  protected function showEcGraphCoCo($both, $cocov1, $cocov2, $total) {
    printf ("          const ctxECcoco = document.getElementById('ec_coco').getContext('2d');
          const myECcoco = new Chart(ctxECcoco, {
            width: 200,
            type: 'pie',
            data: {
              labels: ['Both CoCo v1 and v2', 'CoCo v2 Only', 'CoCo v1 Only', 'No or Other Category'],
              datasets: [{
                data: [%d, %d, %d, %d],
                backgroundColor: [
                  'rgb(99, 255, 132)',
                  'rgb(199, 255, 132)',
                  'rgb(199, 255, 255)',
                  'rgb(255, 255, 255)',
                ],
                borderColor : 'rgb(0,0,0)',
                hoverOffset: 4
              }]
            },
          });%s",
      $both, $cocov2 - $both, $cocov1 - $both, $total - $cocov2 - $cocov1, "\n");
  }

  /**
   * Show Graph for Refeds EntityCategories
   *
   * @param int $both Number of SP:s with both R&S and PersonCoCo v1 and CoCo v2
   *
   * @param int $rands Number of SP:s with http://refeds.org/category/research-and-scholarship # NOSONAR Should be http://
   *
   * @param int $personalized Number of SP:s with https://refeds.org/category/personalized
   *
   * @param int $pseudonymous Number of SP:s with https://refeds.org/category/pseudonymous
   *
   * @param int $anonymous Number of SP:s with https://refeds.org/category/anonymous
   *
   * @param int $total Total numer of SP:s
   *
   * @return void
   */
  protected function showEcGraphRefeds($both, $rands, $personalized, $pseudonymous, $anonymous, $total) {
    printf ("          const ctxECRefeds = document.getElementById('ec_Refeds').getContext('2d');
          const myECRefeds = new Chart(ctxECRefeds, {
            width: 200,
            type: 'pie',
            data: {
              labels: ['Both R&S and Personalized', 'R&S Only', 'Personalized Only',
                      'Pseudonymous', 'Anonymous', 'No or Other Category'],
              datasets: [{
                data: [%d, %d, %d, %d, %d, %d],
                backgroundColor: [
                  'rgb(99, 255, 132)',
                  'rgb(199, 255, 132)',
                  'rgb(199, 255, 255)',
                  'rgb(255, 205, 86)',
                  'rgb(99, 132, 255)',
                  'rgb(255, 255, 255)',
                ],
                borderColor : 'rgb(0,0,0)',
                hoverOffset: 4
              }]
            },
          });%s",
      $both, $rands - $both, $personalized - $both, $pseudonymous, $anonymous, $total - $rands - $personalized - $pseudonymous - $anonymous, "\n");
  }

  /**
   * Show Graph for numer of EC:s per entityID
   *
   * @param array $labels Arrray with labels
   *
   * @param array $data Arrray with data points
   *
   * @return void
   */
  protected function showECCountGraph($labels, $data) {
    printf ("          const ctxECcounts = document.getElementById('ec_counts').getContext('2d');
          const myECcounts = new Chart(ctxECcounts, {
            width: 200,
            type: 'pie',
            data: {
              labels: ['%s'],
              datasets: [{
                data: [%s],
                backgroundColor: [
                  'rgb(255, 255, 255)',
                  'rgb(99, 255, 132)',
                  'rgb(199, 255, 132)',
                  'rgb(199, 255, 255)',
                  'rgb(255, 205, 86)',
                  'rgb(99, 132, 255)',
                  'rgb(255, 99, 132)',
                ],
                borderColor : 'rgb(0,0,0)',
                hoverOffset: 4
              }]
            },
          });%s",
      implode("','", $labels),
      implode(',', $data), "\n");
  }

  /**
   * Show EcStatistics tab
   *
   * @return void
   */
  protected function showEcStatistics() {
    $spHandler = $this->config->getDb()->prepare(
      'SELECT COUNT(`id`) AS `count` FROM `Entities` WHERE `isSP` = 1 AND `status` = 1 AND `publishIn` > 1;');
    $entityAttributesHandler = $this->config->getDb()->prepare(
      "SELECT COUNT(`attribute`) AS `count`, `attribute`
      FROM `EntityAttributes`, `Entities`
      WHERE `type` = 'entity-category' AND `entity_id` = `Entities`.`id` AND `isSP` = 1 AND `status` = 1 AND `publishIn` > 1
      GROUP BY `attribute`;");
    $bothCoCoHandler = $this->config->getDb()->prepare(
      "SELECT COUNT(`attribute`) AS `count`
      FROM `EntityAttributes`
      WHERE `type` = 'entity-category' AND
        `attribute`= 'https://refeds.org/category/code-of-conduct/v2' AND
        `entity_id` IN (
          SELECT entity_id
          FROM `EntityAttributes`, `Entities`
          WHERE `type` = 'entity-category' AND
          `entity_id` = `Entities`.`id` AND
          `isSP` = 1 AND
          `status` = 1 AND
          `publishIn` > 1 AND
          `attribute`= 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1');");
    $bothRASandPers = $this->config->getDb()->prepare(
      "SELECT COUNT(`attribute`) AS `count`
      FROM `EntityAttributes`
      WHERE `type` = 'entity-category' AND
        `attribute`= 'http://refeds.org/category/research-and-scholarship' AND
        `entity_id` IN (
          SELECT entity_id
          FROM `EntityAttributes`, `Entities`
          WHERE `type` = 'entity-category' AND
          `entity_id` = `Entities`.`id` AND
          `isSP` = 1 AND
          `status` = 1 AND
          `publishIn` > 1 AND
          `attribute`= 'https://refeds.org/category/personalized');");
    $noEcHandler = $this->config->getDb()->prepare(
      "SELECT COUNT(`id`) AS `count`
      FROM `Entities`
      WHERE `isSP` = 1 AND
        `status` = 1 AND
        `publishIn` > 1 AND
        `id` NOT IN (
          SELECT DISTINCT `entity_id`
          FROM `EntityAttributes`
          WHERE `type` = 'entity-category'
        );");
    $ecCountHandler = $this->config->getDb()->prepare(
      "SELECT COUNT(`entityID`) AS nrOfEntyID, `count`
      FROM EntityEntityAttributes
      GROUP BY `count`
      ORDER BY `count`;");
    $spHandler->execute();
    if ($sps = $spHandler->fetch(PDO::FETCH_ASSOC)) {
      $nrOfSPs = $sps['count'];
    } else {
      $nrOfSPs = 0;
    }
    $noEcHandler->execute();
    if ($sps = $noEcHandler->fetch(PDO::FETCH_ASSOC)) {
      $nrOfSPsWithoutEc = $sps['count'];
    } else {
      $nrOfSPsWithoutEc = 0;
    }
    $entityAttributesHandler->execute();
    while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      $ecTagged[$attribute['attribute']] = $attribute['count'];
    }
    $bothCoCoHandler->execute();
    if ($attribute = $bothCoCoHandler->fetch(PDO::FETCH_ASSOC)) {
      $ecTagged['bothCoCo'] = $attribute['count'];
    } else {
      $ecTagged['bothCoCo'] = 0;
    }
    $bothRASandPers->execute();
    if ($attribute = $bothRASandPers->fetch(PDO::FETCH_ASSOC)) {
      $ecTagged['bothRASandPers'] = $attribute['count'];
    } else {
      $ecTagged['bothRASandPers'] = 0;
    }
    $ecCountHandler->execute();
    $nrOfEcsPerEntityID = $ecCountHandler->fetchAll(PDO::FETCH_ASSOC);
    printf ('        <div class="row">
          <div class="col">
            <h3>REFEDS Categories</h3>
            <canvas id="ec_Refeds"></canvas>
          </div>
          <div class="col">
            <h3>Code Of Conduct</h3>
            <canvas id="ec_coco"></canvas>
          </div>
          <div class="col">
            <h3>European Student Identifier</h3>
            <canvas id="ec_esi"></canvas>
          </div>
          <div class="col">
            <h3>Categories per entityID</h3>
            <canvas id="ec_counts"></canvas>
          </div>
        </div>
        <br><br>
        <h3>Statistics in numbers</h3>
        <table class="table table-striped table-bordered">
          <tr><th>Total numer of SP:s</th><td>%d</td></tr>
          <tr><th>SP:s with REFEDS Anonymous Access Category</th><td>%d</td></tr>
          <tr><th>SP:s with REFEDS Pseudonymous Access Category</th><td>%d</td></tr>
          <tr><th>SP:s with REFEDS Personalized Access Category</th><td>%d</td></tr>
          <tr><th>SP:s with REFEDS Research and Scholarship Category</th><td>%d</td></tr>
          <tr><th>SP:s with REFEDS Personalized &amp; Research and Scholarship Categories</th><td>%d</td></tr>
          <tr><th>SP:s with REFEDS Code Of Conduct (v2) Access Category</th><td>%d</td></tr>
          <tr><th>SP:s with GÉANT Code Of Conduct (v1) Access Category</th><td>%d</td></tr>
          <tr><th>SP:s with REFEDS and GÉANT CoCo Categories</th><td>%d</td></tr>
          <tr><th>SP:s with European Student Identifier Access Category</th><td>%d</td></tr>
          <tr><th>SP:s with NO Access Category</th><td>%d</td></tr>
        </table>
        <table class="table table-striped table-bordered">%s',
      $nrOfSPs,
      $ecTagged[self::SAML_EC_ANONYMOUS], $ecTagged[self::SAML_EC_PSEUDONYMOUS], $ecTagged[self::SAML_EC_PERSONALIZED],
      $ecTagged[self::SAML_EC_RANDS], $ecTagged['bothRASandPers'],
      $ecTagged[self::SAML_EC_COCOV2], $ecTagged[self::SAML_EC_COCOV1], $ecTagged['bothCoCo'],
      $ecTagged[self::SAML_EC_ESI], $nrOfSPsWithoutEc, "\n");
    $labelArray = array('No EC');
    $dataArray = array($nrOfSPsWithoutEc);
    foreach ($nrOfEcsPerEntityID as $rarray) {
      $labelArray[] = '# of EC = ' . $rarray['count'];
      $dataArray[] = $rarray['nrOfEntyID'];
      printf('          <tr><th>Numer of SP:s with %d Entity Categor%s</th><td>%d</td></tr>%s',
        $rarray['count'], $rarray['count'] == 1 ? 'y' : 'ies', $rarray['nrOfEntyID'], "\n");
    }
    printf('          <tr><th>Numer of SP:s with no Entity Category</th><td>%d</td></tr>
        </table>
        <script>%s', $nrOfSPsWithoutEc, "\n");
    $this->showEcGraphRefeds($ecTagged['bothRASandPers'], $ecTagged[self::SAML_EC_RANDS],
      $ecTagged[self::SAML_EC_PERSONALIZED], $ecTagged[self::SAML_EC_PSEUDONYMOUS],
      $ecTagged[self::SAML_EC_ANONYMOUS], $nrOfSPs);
    $this->showEcGraphCoCo($ecTagged['bothCoCo'], $ecTagged[self::SAML_EC_COCOV1], $ecTagged[self::SAML_EC_COCOV2], $nrOfSPs);
    $this->showEcGraph('esi', $ecTagged[self::SAML_EC_ESI], $nrOfSPs);
    $this->showECCountGraph($labelArray, $dataArray);
    printf('        </script>%s', "\n");

  }

  /**
   * Show EcsStatistics tab
   *
   * @return void
   */
  protected function showEcsStatistics() {
    $ecsTagged = array(
      self::SAML_EC_RANDS => 'rands',
      self::SAML_EC_COCOV1 => 'cocov1-1',
      self::SAML_EC_ESI => 'esi',
      self::SAML_EC_ANONYMOUS => 'anonymous',
      self::SAML_EC_COCOV2 => 'cocov2-1',
      self::SAML_EC_PERSONALIZED => 'personalized',
      self::SAML_EC_PSEUDONYMOUS => 'pseudonymous');
    $ecsTested = array(
      'anonymous' => array('OK' => 0, 'Fail' => 0, 'MarkedWithECS' => 0),
      'pseudonymous' => array('OK' => 0, 'Fail' => 0, 'MarkedWithECS' => 0),
      'personalized' => array('OK' => 0, 'Fail' => 0, 'MarkedWithECS' => 0),
      'rands' => array('OK' => 0, 'Fail' => 0, 'MarkedWithECS' => 0),
      'cocov1-1' => array('OK' => 0, 'Fail' => 0, 'MarkedWithECS' => 0),
      'cocov2-1' => array('OK' => 0, 'Fail' => 0, 'MarkedWithECS' => 0),
      'esi' => array('OK' => 0, 'Fail' => 0, 'MarkedWithECS' => 0));
    $ecs = array(
      'anonymous' => 'REFEDS Anonymous Access',
      'pseudonymous' => 'REFEDS Pseudonymous Access',
      'personalized' => 'REFEDS Personalized Access',
      'rands' => 'REFEDS R&S',
      'cocov1-1' => 'GÉANT CoCo (v1)',
      'cocov2-1' => 'REFEDS CoCo (v2)',
      'esi' => 'European Student Identifier');

    $nrOfIdPs = 0;
    $idpHandler = $this->config->getDb()->prepare(
      'SELECT COUNT(`id`) AS `count` FROM `Entities` WHERE `isIdP` = 1 AND `status` = 1 AND `publishIn` > 1;');
    $idpHandler->execute();
    if ($idps = $idpHandler->fetch(PDO::FETCH_ASSOC)) {
      $nrOfIdPs = $idps['count'];
    }
    $entityAttributesHandler = $this->config->getDb()->prepare(
      "SELECT COUNT(`attribute`) AS `count`, `attribute`
      FROM `EntityAttributes`, `Entities`
      WHERE `type` = 'entity-category-support' AND `entity_id` = `Entities`.`id` AND `isIdP` = 1 AND `status` = 1 AND `publishIn` > 1
      GROUP BY `attribute`;");
    $entityAttributesHandler->execute();
    while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      $ecsTested[$ecsTagged[$attribute['attribute']]]['MarkedWithECS'] = $attribute['count'];
    }

    $testResultsHandeler = $this->config->getDb()->prepare(
      "SELECT COUNT(entityID) AS `count`, `test`, `result`
      FROM `TestResults`
      WHERE `TestResults`.`entityID` IN (SELECT `entityID`
      FROM `Entities` WHERE `isIdP` = 1 AND `publishIn` > 1)
      GROUP BY `test`, `result`;");
    $testResultsHandeler->execute();
    while ($testResult = $testResultsHandeler->fetch(PDO::FETCH_ASSOC)) {
      switch ($testResult['result']) {
        case 'CoCo OK, Entity Category Support OK' :
        case 'R&S attributes OK, Entity Category Support OK' :
        case 'CoCo OK, Entity Category Support missing' :
        case 'R&S attributes OK, Entity Category Support missing' :
        case 'Anonymous attributes OK, Entity Category Support OK' :
        case 'Personalized attributes OK, Entity Category Support OK' :
        case 'Pseudonymous attributes OK, Entity Category Support OK' :
        case 'Anonymous attributes OK, Entity Category Support missing' :
        case 'Personalized attributes OK, Entity Category Support missing' :
        case 'Pseudonymous attributes OK, Entity Category Support missing' :
        case 'schacPersonalUniqueCode OK' :
          $ecsTested[$testResult['test']]['OK'] += $testResult['count'];
          break;
        case 'Support for CoCo missing, Entity Category Support missing' :
        case 'R&S attribute missing, Entity Category Support missing' :
        case 'CoCo is not supported, BUT Entity Category Support is claimed' :
        case 'R&S attributes missing, BUT Entity Category Support claimed' :
        case 'Anonymous attribute missing, Entity Category Support missing' :
        case 'Anonymous attributes missing, BUT Entity Category Support claimed' :
        case 'Personalized attribute missing, Entity Category Support missing' :
        case 'Personalized attributes missing, BUT Entity Category Support claimed' :
        case 'Pseudonymous attribute missing, Entity Category Support missing' :
        case 'Pseudonymous attributes missing, BUT Entity Category Support claimed' :
        case 'Missing schacPersonalUniqueCode' :
          $ecsTested[$testResult['test']]['Fail'] += $testResult['count'];
          break;
        default :
          printf('Unknown result : %s', $testResult['result']);
      }
    }

    $count = 1;
    foreach ($ecs as $ec => $descr) {
      printf ('%s          <div class="col">
            <h3>%s</h3>%s            <canvas id="ecs_%s"></canvas>
          </div>%s',
        $count == 1 ? "        <div class=\"row\">\n" : '',
        $descr, "\n", str_replace('-','', $ec), "\n");
      $count ++;
      if ($count == 5) {
        printf ('        </div>%s', "\n");
        $count = 1;
      }
    }
    if ($count > 1) {
      while ($count < 5) {
        printf ('          <div class="col"></div>%s', "\n");
        $count ++;
      }
      printf ('        </div>%s', "\n");
    }
    printf ('        <br><br>
        <h3>Statistics in numbers</h3>
        <p>
          Based on release-check test performed over the last 12 months and Entity-Category-Support registered in metadata.
          <br>
          Out of %d IdPs in %s:
        </p>
        <table class="table table-striped table-bordered">
          <tr><th>EC</th><th>OK + ECS</th><th>OK no ECS</th><th>Fail</th><th>Not tested</th></tr>%s',
      $nrOfIdPs, $this->config->getFederation()['displayName'], "\n");
    $scripts = '';
    foreach ($ecs as $ec => $descr) {
      $markedECS = $ecsTested[$ec]['MarkedWithECS'];
      $ok = $ecsTested[$ec]['OK'] > $ecsTested[$ec]['MarkedWithECS']
        ? $ecsTested[$ec]['OK'] - $ecsTested[$ec]['MarkedWithECS']
        : 0;
      $fail = $ecsTested[$ec]['Fail'] > $nrOfIdPs ? 0 : $ecsTested[$ec]['Fail'];
      $notTested = $nrOfIdPs - $markedECS - $ok - $fail;
      printf('          <tr><td>%s</td><td>%d (%d %%)</td><td>%d (%d %%)</td><td>%d (%d %%)</td><td>%d (%d %%)</td></tr>%s',
        $descr, $markedECS, ($markedECS/$nrOfIdPs*100), $ok, ($ok/$nrOfIdPs*100),
        $fail, ($fail/$nrOfIdPs*100), $notTested, ($notTested/$nrOfIdPs*100), "\n");
      $ecdiv = 'ecs_' . str_replace('-','', $ec);
      $scripts .= sprintf ("          const ctx%s = document.getElementById('%s').getContext('2d');
          const my%s = new Chart(ctx%s, {
            width: 200,
            type: 'pie',
            data: {
              labels: ['OK + ECS', 'OK no ECS', 'Fail', 'Not tested'],
              datasets: [{
                data: [%d, %d, %d, %d],
                backgroundColor: [
                  'rgb(99, 255, 132)',
                  'rgb(255, 205, 86)',
                  'rgb(255, 99, 132)',
                  'rgb(255, 255, 255)',
                ],
                borderColor : 'rgb(0,0,0)',
                hoverOffset: 4
              }]
            },
          });%s",
        $ecdiv, $ecdiv,
        $ecdiv, $ecdiv, $markedECS, $ok, $fail, $notTested, "\n");
    }
    printf('    %s        <script>%s%s        </script>%s',
      self::HTML_TABLE_END, "\n", $scripts, "\n");
  }

  /**
   * Shows row for AssuranceCertification
   *
   * @param string $date date of row
   *
   * @param array $assurance data about different AssuranceCertifications
   *
   * @return void
   */
  protected function printAssuranceCertificationRow($date, $assurance) {
    printf('          <tr>
            <td>%s</td>%s',
      htmlspecialchars($date), "\n");
    printf('            <td>%s</td><td>%s</td><td>%s</td>%s',
      $assurance['NrOfEntites'],
      $assurance['SIRTFI'],
      $assurance['SIRTFI2'],
      "\n");
    if ($this->config->getFederation()['swamid_assurance']) {
        printf('            <td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>%s',
      $assurance['NrOfIdPs'],
      $assurance['AL1'],
      $assurance['AL2'],
      $assurance['AL2-MFA-HI'],
      $assurance['AL3'],
      "\n");
    }
    print ("          </tr>\n");
  }

  /**
   * Show Assurance Certification Statistics tab
   *
   * @return void
   */
  protected function showAcStatistics() {
    $assuranceArray = array();
    $labelsArray = array();
    $entityArray = array();
    $sirtfiArray = array();
    $sirtfi2Array = array();
    $idpArray = array();
    $al1Array = array ();
    $al2Array = array ();
    $al2mhArray = array ();
    $al3Array = array ();

    $swamid_assurance = $this->config->getFederation()['swamid_assurance'];
    $dateHandler = $this->config->getDb()->query(
      'SELECT DISTINCT `EntitiesAssuranceStatistics`.`date`,`NrOfEntites`, `NrOfIdPs`
      FROM `EntitiesAssuranceStatistics`
      LEFT JOIN `EntitiesStatistics`
        ON `EntitiesAssuranceStatistics`.`date` = `EntitiesStatistics`.`date`
      ORDER BY `date` DESC;');
    $assuranceHandler = $this->config->getDb()->query(
      'SELECT `date`, `assurance`, `nrOfEntities`
      FROM `EntitiesAssuranceStatistics`;');

    while ($row = $dateHandler->fetch(PDO::FETCH_ASSOC)) {
      $date = substr($row['date'],0,10);
      $assuranceArray[$date] = array(
          'NrOfEntites' => $row['NrOfEntites'],
          'NrOfIdPs' => $row['NrOfIdPs'],
          'SIRTFI' => 0,
          'SIRTFI2' => 0,
          'AL1' => 0,
          'AL2' => 0,
          'AL2-MFA-HI' => 0,
          'AL3' => 0,
      );
    }

    while ($row = $assuranceHandler->fetch(PDO::FETCH_ASSOC)) {
      $assuranceArray[substr($row['date'],0,10)][$row['assurance']] = $row['nrOfEntities'];
    }

    printf ('        <h3>Assurance Certification Statistics</h3>
        Date of publication of Certifications<ul>
          %s<li>Sirtfi: 2016-11-08</li>
          <li>Sirtfi 2: 2022-07-22</li>
        </ul>
        %s
        <canvas id="sirtfi" width="200" height="50"></canvas><br>
        <br>
        <h3>Statistics in numbers</h3>
        <table class="table table-striped table-bordered">
          <tr><th>Date</th><th>NrOfEntites</th><th>Sirtfi</th><th>Sirtfi2</th>%s</tr>%s',
      $swamid_assurance ?
        '<li>Swamid AL1: 2013-09-24</li><li>Swamid AL2: 2015-12-02</li>
        <li>Swamid AL2+MFA(-HI): 2018-09-12, deprecated 2020-12-31</li><li>Swamid AL3: 2020-06-15</li>'
        : '',
      $swamid_assurance ? '<canvas id="idps" width="200" height="50"></canvas><br>' : '',
      $swamid_assurance ? '<th>NrOfIdPs</th><th>AL1</th><th>AL2</th><th>AL2-MFA-HI</th><th>AL3</th>' : '', "\n");

    foreach ($assuranceArray as $date => $assurance) {
      $this->printAssuranceCertificationRow($date, $assurance);
      array_unshift($labelsArray, substr($date,2,8));
      array_unshift($entityArray, $assurance['NrOfEntites']);
      array_unshift($sirtfiArray, $assurance['SIRTFI'] - $assurance['SIRTFI2']);
      array_unshift($sirtfi2Array, $assurance['SIRTFI2']);
      array_unshift($idpArray, $assurance['NrOfIdPs']);
      array_unshift($al1Array, $assurance['AL1'] - $assurance['AL2']);
      array_unshift($al2Array, $assurance['AL2'] - $assurance['AL2-MFA-HI'] - $assurance['AL3']);
      array_unshift($al2mhArray, $assurance['AL2-MFA-HI']);
      array_unshift($al3Array, $assurance['AL3']);
    }

    printf ('    %s        <script>%s', self::HTML_TABLE_END, "\n");
    printf ("          const ctxSirtfi = document.getElementById('sirtfi').getContext('2d');
          const mySirtfi = new Chart(ctxSirtfi, {
            type: 'line',
            data: {
              labels: ['%s'],
              datasets: [{
                label: 'Sirtfi',
                backgroundColor: \"rgb(0, 123, 255)\",
                data: [%s],
                fill: 'origin'
              }, {
                label: 'Sirtfi2',
                backgroundColor: \"rgb(0, 255, 0)\",
                data: [%s],
                fill: 'origin'
              }, {
                label: '# of Entities',
                backgroundColor: \"rgb(0,0,0)\",
                data: [%s],
                stack: 'total',
              }]
            },
            options: {
              responsive: true,
              scales: {
                y: {
                  beginAtZero: true,
                  stacked: true,
                }
              }
            }
          });%s",
      implode("','", $labelsArray),
      implode(',', $sirtfiArray),
      implode(',', $sirtfi2Array),
      implode(',', $entityArray),
      "\n");

    if ($swamid_assurance) {
      printf ("          const ctxIdps = document.getElementById('idps').getContext('2d');
          const myIdps = new Chart(ctxIdps, {
            type: 'line',
            data: {
              labels: ['%s'],
              datasets: [{
                label: 'AL1',
                backgroundColor: \"rgb(255, 205, 86)\",
                data: [%s],
                fill: 'origin'
              },{
                label: 'AL2',
                backgroundColor: \"rgb(0, 123, 255)\",
                data: [%s],
                fill: 'origin'
              },{
                label: 'AL2-MFA-HI',
                backgroundColor: \"rgb(2, 255, 255)\",
                data: [%s],
                fill: 'origin'
              }, {
                label: 'AL3',
                backgroundColor: \"rgb(0, 255, 0)\",
                data: [%s],
                fill: 'origin'
              }, {
                label: '# of IdP:s',
                backgroundColor: \"rgb(0,0,0)\",
                data: [%s],
                stack: 'total',
              }]
            },
            options: {
              responsive: true,
              scales: {
                y: {
                  beginAtZero: true,
                  stacked: true,
                },
              }
            }
          });%s",
        implode("','", $labelsArray),
        implode(',', $al1Array),
        implode(',', $al2Array),
        implode(',', $al2mhArray),
        implode(',', $al3Array),
        implode(',', $idpArray), "\n");
    }
    print "        </script>\n";
  }

  /**
   * Shows row for Assurance
   *
   * @param string $idp EntityId of IdP
   *
   * @param array $assurance array with Assurance info
   *
   * @return void
   */
  protected function printAssuranceRow($idp, $assurance) {
    printf('          <tr>
            <td>%s</td>%s',
      htmlspecialchars($idp), "\n");
    if ($this->config->getFederation()['swamid_assurance']) {
        printf('            <td>%s</td><td>%s</td><td>%s</td>%s',
      $assurance['SWAMID-AL1'],
      $assurance['SWAMID-AL2'],
      $assurance['SWAMID-AL3'],
      "\n");
    }
    printf('            <td>%s</td><td>%s</td><td>%s</td>
            <td>%s</td>
          </tr>%s',
      $assurance['RAF-low'],
      $assurance['RAF-medium'],
      $assurance['RAF-high'],
      $assurance['None'], "\n");
  }

  /**
   * Show Graph for assurance recived from and marked in Metadata for IdP:s
   *
   * @param string $canvas Canvas to put graph in
   *
   * @param array $labels Arrray with labels
   *
   * @param array $data Arrray with data points
   *
   * @return void
   */
  protected function showAssuranceCountGraph($canvas, $labels, $data) {
    printf('        <script>
          const ctx%s = document.getElementById(\'%s\').getContext(\'2d\');
          const my%s = new Chart(ctx%s, {
            width: 200,
            type: \'pie\',
            data: {
              labels: [\'%s\'],
              datasets: [{
                data: [%s],
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
        </script>%s',
      $canvas, $canvas, $canvas, $canvas,
      implode("','", $labels),
      implode(',', $data), "\n");
  }

  /**
   * Show RAFStatistics for all seen IdP:s
   *
   * @return void
   */
  protected function showRAFStatistics() {
    $swamid_assurance = $this->config->getFederation()['swamid_assurance'];
    $idpCountHandler = $this->config->getDb()->query(
      'SELECT COUNT(DISTINCT `assuranceLog`.`entityID`) AS `idps`
      FROM `assuranceLog`, `Entities`
      WHERE `assuranceLog`.`entityID` = `Entities`.`EntityID`
        AND `Entities`.`status` = 1;');
    $idps = ($idpCountRow = $idpCountHandler->fetch(PDO::FETCH_ASSOC)) ? $idpCountRow['idps'] : 0;

    $idpAssuranceHandler = $this->config->getDb()->prepare(
      'SELECT COUNT(`assuranceLog`.`entityID`) as `count`, `assurance`
      FROM `assuranceLog`, `Entities`
      WHERE `assuranceLog`.`entityID` = `Entities`.`EntityID`
        AND `Entities`.`status` = 1
      GROUP BY `assurance`;');
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

    $metaAssuranceHandler = $this->config->getDb()->prepare(
      "SELECT COUNT(`Entities`.`id`) AS `count`, `attribute`
      FROM `Entities`, `EntityAttributes`
      WHERE `Entities`.`id` = `EntityAttributes`.`entity_id`
        AND `status` = 1
        AND `isIdP` = 1
        AND `publishIn` > 1
        AND `type` = 'assurance-certification'
      GROUP BY `attribute`;");
    $nrOfIdPs = $this->config->getDb()->query(
      "SELECT COUNT(`Entities`.`id`) AS `count`
      FROM `Entities`
      WHERE `status` = 1 AND `isIdP` = 1 AND `publishIn` > 1;")->fetchColumn();

    $metaAssuranceHandler->execute();
    $metaAssuranceCount = array(
      'http://www.swamid.se/policy/assurance/al1' => 0, # NOSONAR Should be http://
      'http://www.swamid.se/policy/assurance/al2' => 0, # NOSONAR Should be http://
      'http://www.swamid.se/policy/assurance/al3' => 0); # NOSONAR Should be http://
    while ($metaAssuranceRow = $metaAssuranceHandler->fetch(PDO::FETCH_ASSOC)) {
      $metaAssuranceCount[$metaAssuranceRow['attribute']] = $metaAssuranceRow['count'];
    }
    printf('        <div class="row">
          <div class="col">
            <div class="row"><div class="col">Total nr of IdP:s</div><div class="col">%d</div></div>%s',
      $idps, "\n");
    if ($swamid_assurance) {
        printf('            <div class="row"><div class="col">&nbsp;</div></div>
            <div class="row"><div class="col">Max SWAMID AL3</div><div class="col">%d</div></div>
            <div class="row"><div class="col">Max SWAMID AL2</div><div class="col">%d</div></div>
            <div class="row"><div class="col">Max SWAMID AL1</div><div class="col">%d</div></div>
            <div class="row"><div class="col">No SWAMID AL</div><div class="col">%d</div></div>%s',
      $assuranceCount['SWAMID-AL3'],
      $assuranceCount['SWAMID-AL2'] - $assuranceCount['SWAMID-AL3'],
      $assuranceCount['SWAMID-AL1'] - $assuranceCount['SWAMID-AL2'],
      $idps - $assuranceCount['SWAMID-AL1'],
      "\n");
    }
    printf('            <div class="row"><div class="col">&nbsp;</div></div>
            <div class="row"><div class="col">Max RAF High</div><div class="col">%d</div></div>
            <div class="row"><div class="col">Max RAF Medium</div><div class="col">%d</div></div>
            <div class="row"><div class="col">Max RAF Low</div><div class="col">%d</div></div>
            <div class="row"><div class="col">No RAF</div><div class="col">%d</div></div>
          </div>',
      $assuranceCount['RAF-high'],
      $assuranceCount['RAF-medium'] - $assuranceCount['RAF-high'],
      $assuranceCount['RAF-low'] - $assuranceCount['RAF-medium'],
      $idps - $assuranceCount['RAF-low'],
      "\n");
    printf(( $swamid_assurance ?  '
          <div class="col">
            <h3>SWAMID Assurance</h3>
            <canvas id="swamid"></canvas>
          </div>' : '' ) . '
          <div class="col">
            <h3>REFEDS Assurance</h3>
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
            <th>IdP</th>' . ( $swamid_assurance ? '
            <th>AL1</th><th>AL2</th><th>AL3</th>' : '' ) . '
            <th>RAF-Low</th><th>RAF-Medium</th><th>RAF-High</th><th>Nothing</th>
          </tr>%s', "\n");

    $assuranceHandler = $this->config->getDb()->prepare(
      'SELECT `assuranceLog`.`entityID`, `assurance`, `logDate`
      FROM `assuranceLog`, `Entities`
      WHERE `assuranceLog`.`entityID` = `Entities`.`EntityID`
        AND `Entities`.`status` = 1
      ORDER BY `entityID`, `assurance`;');
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
        if ($oldIdp) { $this->printAssuranceRow($oldIdp, $assurance); }
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
    if ($oldIdp) { $this->printAssuranceRow($oldIdp, $assurance);}
    printf('    %s        <br>%s', self::HTML_TABLE_END, "\n") ;

    if ($swamid_assurance) {
      $this->showAssuranceCountGraph('swamid',
        array('AL3', 'AL2', 'AL1', 'None'),
        array($assuranceCount['SWAMID-AL3'],
          $assuranceCount['SWAMID-AL2'] - $assuranceCount['SWAMID-AL3'],
          $assuranceCount['SWAMID-AL1'] - $assuranceCount['SWAMID-AL2'],
          $idps - $assuranceCount['SWAMID-AL1']));
    }
    $this->showAssuranceCountGraph('raf',
      array('High', 'Medium', 'Low', 'None'),
      array($assuranceCount['RAF-high'],
        $assuranceCount['RAF-medium'] - $assuranceCount['RAF-high'],
        $assuranceCount['RAF-low'] - $assuranceCount['RAF-medium'],
        $idps - $assuranceCount['RAF-low']));
    $this->showAssuranceCountGraph('meta',
      array('AL3', 'AL2', 'AL1', 'None'),
      array(
        $metaAssuranceCount['http://www.swamid.se/policy/assurance/al3'], # NOSONAR Should be http://
        $metaAssuranceCount['http://www.swamid.se/policy/assurance/al2'] - # NOSONAR Should be http://
          $metaAssuranceCount['http://www.swamid.se/policy/assurance/al3'], # NOSONAR Should be http://
        $metaAssuranceCount['http://www.swamid.se/policy/assurance/al1'] - # NOSONAR Should be http://
          $metaAssuranceCount['http://www.swamid.se/policy/assurance/al2'], # NOSONAR Should be http://
        $nrOfIdPs - $metaAssuranceCount['http://www.swamid.se/policy/assurance/al1'] # NOSONAR Should be http://
      ));
  }
}
