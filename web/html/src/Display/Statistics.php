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
    $ecsActive='';
    $ecsSelected='false';
    $ecsShow='';
    #
    $assuranceActive='';
    $assuranceSelected='false';
    $assuranceShow='';

    if (isset($_GET["tab"])) {
      switch ($_GET["tab"]) {
        case 'ecs' :
          $ecsActive = self::HTML_ACTIVE;
          $ecsSelected = self::HTML_TRUE;
          $ecsShow = self::HTML_SHOW;
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
            <a class="nav-link%s" id="ecs-tab" data-toggle="tab" href="#ecs" role="tab"
              aria-controls="ecs" aria-selected="%s">Entity Category Support</a>
          </li>
          <li class="nav-item">
            <a class="nav-link%s" id="assurance-tab" data-toggle="tab" href="#assurance" role="tab"
              aria-controls="assurance" aria-selected="%s">Assurance</a>
          </li>%s',
      $entityActive, $entitySelected,
      $ecsActive, $ecsSelected, $assuranceActive, $assuranceSelected, "\n");
    printf('        </ul>
      </div>%s    </div>%s    <script src="/include/chart/chart.min.js"></script>%s    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade%s%s" id="entity" role="tabpanel" aria-labelledby="entity-tab">%s',
        "\n", "\n", "\n",
      $entityShow, $entityActive, "\n");
    $this->showEntityStatistics();
    printf('      </div><!-- End tab-pane entity -->
      <div class="tab-pane fade%s%s" id="ecs" role="tabpanel" aria-labelledby="ecs-tab">%s',
        $ecsShow, $ecsActive, "\n");
    $this->showEcsStatistics();
    printf('      </div><!-- End tab-pane ecs -->
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
  public function showEntityStatistics() {
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
                yAxes: {
                  beginAtZero: true,
                  stacked: true,
                }
              }
            }
          });%s        </script>%s",
      $labels, $idps, $sps, "\n", "\n");
  }

  /**
   * Show EcsStatistics tab
   *
   * @return void
   */
  public function showEcsStatistics() {
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

    $idpHandler = $this->config->getDb()->prepare(
      'SELECT COUNT(`id`) AS `count` FROM `Entities` WHERE `isIdP` = 1 AND `status` = 1 AND `publishIn` > 1;');
    $idpHandler->execute();
    if ($idps = $idpHandler->fetch(PDO::FETCH_ASSOC)) {
      $nrOfIdPs = $idps['count'];
    } else {
      $nrOfIdPs = 0;
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
      if ($count == 1) {
        printf ('        <div class="row">%s          <div class="col">%s', "\n", "\n");
      } else {
        printf ('          <div class="col">%s', "\n");
      }
      printf ('            <h3>%s</h3>%s            <canvas id="ecs_%s"></canvas>%s', $descr, "\n", str_replace('-','', $ec), "\n");
      if ($count == 4) {
        printf ('          </div>%s        </div>%s', "\n", "\n");
        $count = 1;
      } else {
        printf ('          </div>%s', "\n");
        $count ++;
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
    }
    printf('    %s        <script>%s', self::HTML_TABLE_END, "\n", "\n");
    foreach ($ecs as $ec => $descr) {
      $markedECS = $ecsTested[$ec]['MarkedWithECS'];
      $ok = $ecsTested[$ec]['OK'] > $ecsTested[$ec]['MarkedWithECS']
        ? $ecsTested[$ec]['OK'] - $ecsTested[$ec]['MarkedWithECS'] : 0;
      $fail = $ecsTested[$ec]['Fail'] > $nrOfIdPs ? 0 : $ecsTested[$ec]['Fail'];
      $notTested = $nrOfIdPs - $markedECS - $ok - $fail;
      $ecdiv = 'ecs_' . str_replace('-','', $ec);
      printf ("          const ctx%s = document.getElementById('%s').getContext('2d');%s", $ecdiv, $ecdiv, "\n");
      printf ("          const my%s = new Chart(ctx%s, {
            width: 200,
            type: 'pie',
            data: {
              labels: ['OK + ECS', 'OK no ECS', 'Fail', 'Not tested'],
              datasets: [{
                label: 'Errors',
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
        $ecdiv, $ecdiv, $markedECS, $ok, $fail, $notTested, "\n");
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
  private function printAssuranceRow($idp, $assurance) {
    $swamid_assurance = $this->config->getFederation()['swamid_assurance'];
    printf('          <tr>
            <td>%s</td>%s',
      htmlspecialchars($idp), "\n");
    if ($swamid_assurance) {
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
   * Show RAFStatistics for all seen IdP:s
   *
   * @return void
   */
  public function showRAFStatistics() {
    $swamid_assurance = $this->config->getFederation()['swamid_assurance'];
    $idpCountHandler = $this->config->getDb()->prepare(
      'SELECT COUNT(DISTINCT `entityID`) as `idps` FROM `assuranceLog`;');
    $idpCountHandler->execute();
    if ($idpCountRow = $idpCountHandler->fetch(PDO::FETCH_ASSOC)) {
      $idps = $idpCountRow['idps'];
    } else {
      $idps = 0;
    }

    $idpAssuranceHandler = $this->config->getDb()->prepare(
      'SELECT COUNT(`entityID`) as `count`, `assurance` FROM `assuranceLog` GROUP BY `assurance`;');
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
      $idps,
      "\n");
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
            <th>AL1</th>
            <th>AL2</th>
            <th>AL3</th>' : '' ) . '
            <th>RAF-Low</th>
            <th>RAF-Medium</th>
            <th>RAF-High</th>
            <th>Nothing</th>
          </tr>%s',
      "\n");

    $assuranceHandler = $this->config->getDb()->prepare(
      'SELECT `entityID`, `assurance`, `logDate`
      FROM `assuranceLog` ORDER BY `entityID`, `assurance`;');
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
          $this->printAssuranceRow($oldIdp, $assurance);
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
      $this->printAssuranceRow($oldIdp, $assurance);
    }
    printf('    %s        <br>%s', self::HTML_TABLE_END, "\n") ;

    if ($swamid_assurance) {
      printf('        <script>
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
        </script>%s',
        $assuranceCount['SWAMID-AL3'],
        $assuranceCount['SWAMID-AL2'] - $assuranceCount['SWAMID-AL3'],
        $assuranceCount['SWAMID-AL1'] - $assuranceCount['SWAMID-AL2'],
        $idps - $assuranceCount['SWAMID-AL1'],
        "\n");
    }
    printf('        <script>
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
        </script>%s',
      $assuranceCount['RAF-high'],
      $assuranceCount['RAF-medium'] - $assuranceCount['RAF-high'],
      $assuranceCount['RAF-low'] - $assuranceCount['RAF-medium'],
      $idps - $assuranceCount['RAF-low'],
      "\n");
    printf('        <script>
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
        </script>%s',
    $metaAssuranceCount['http://www.swamid.se/policy/assurance/al3'], # NOSONAR Should be http://
    $metaAssuranceCount['http://www.swamid.se/policy/assurance/al2'] - # NOSONAR Should be http://
      $metaAssuranceCount['http://www.swamid.se/policy/assurance/al3'], # NOSONAR Should be http://
    $metaAssuranceCount['http://www.swamid.se/policy/assurance/al1'] - # NOSONAR Should be http://
      $metaAssuranceCount['http://www.swamid.se/policy/assurance/al2'], # NOSONAR Should be http://
    "\n");
  }
}