<?php
namespace metadata;

use PDO;

class IMPS {
  const BIND_ID = ':Id';
  const BIND_IMPS_ID = 'IMPS_id';
  const BIND_USER_ID = ':User_id';

  private $config;
  private $errors = '';

  public function __construct() {
    global $config;
    if (isset($config)) {
      $this->config = $config;
    } else {
      $this->config = new Configuration();
    }
  }

  public function editIMPS($id) {
    if ($this->errors != '') {
      printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">
      <b>Errors:</b><br>
      %s%s      </div>%s    </div>%s', "\n", "\n", str_ireplace("\n", "<br>", $this->errors), "\n", "\n", "\n");
    }
    print '    <div class="row">
      <div class="col">' . "\n";

    $impsHandler = $this->config->getDb()->prepare(
      'SELECT `IMPS`.`id`, `OrganizationInfo`.`OrganizationNameSv` AS `OrganizationName`, `name`, `maximumAL`, `lastUpdated`, `sharedIdp`
      FROM `IMPS`, `OrganizationInfo`
      WHERE `IMPS`.`OrganizationInfo_id` = `OrganizationInfo`.`id`
        AND `IMPS`.`id` = :Id;');
    $impsHandler->execute(array(self::BIND_ID => $id));
    if ($imps = $impsHandler->fetch(PDO::FETCH_ASSOC)) {
      $name = isset($_POST['name']) ? $_POST['name'] : $imps['name'];
      $maximumAL = isset($_POST['maximumAL']) ? $_POST['maximumAL'] : $imps['maximumAL'];
      $lastUpdated = isset($_POST['lastUpdated']) ? $_POST['lastUpdated'] : $imps['lastUpdated'];
      $sharedIdp = isset($_POST['sharedIdp']) ? true : $imps['sharedIdp'];

      printf('        <form action="?action=Members&subAction=saveImps&id=%d&tab=imps" method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col-2">Organization</div>
            <div class="col">%s</div>
          </div>
          <div class="row">
            <div class="col-2">Name</div>
            <div class="col"><input type="text" name="name" value="%s" size="30"></div>
          </div>
          <div class="row">
            <div class="col-2">Allowed maximum AL</div>
            <div class="col"><input type="text" name="maximumAL" value="%d" size="1"></div>
          </div>
          <div class="row">
            <div class="col-2">BOT decision</div>
            <div class="col"><input type="text" name="lastUpdated" value="%s" size="10" maxlength="10"></div>
          </div>
          <div class="row">
            <div class="col-2">Shared IdP</div>
            <div class="col"><input type="checkbox" name="sharedIdP"%s></div>
          </div>
          <input type="submit">
        </form>
        <a href="./?action=Members&tab=imps&id=%d"><button>Back</button></a>%s', $imps['id'], $imps['OrganizationName'],
        htmlspecialchars($name), $maximumAL, htmlspecialchars($lastUpdated),
        $sharedIdp ? ' checked' : '', $imps['id'], "\n");
    } else {
      print '        Can\'t find IMPS';
    }
    print '      </div><!-- end col -->
    </div><!-- end row -->';
  }

  public function saveIMPS($id) {
    if (isset($_POST['name']) && isset($_POST['maximumAL']) && isset($_POST['lastUpdated'])) {
      $maximumAL = $_POST['maximumAL'];
      if ($maximumAL > 0 && $maximumAL < 4) {
        $impsHandler = $this->config->getDb()->prepare(
          'UPDATE `IMPS`
          SET `name` = :Name, `maximumAL` = :MaximunAL,
            `lastUpdated` = :LastUpdated,
            `lastValidated` = IF(lastValidated < :LastUpdated, :LastUpdated, lastValidated), `sharedIdp` = :SharedIdP
          WHERE `id` = :Id;');
        return $impsHandler->execute(array(
          self::BIND_ID => $id, ':Name' => $_POST['name'],
          ':MaximunAL' => $maximumAL,
          ':LastUpdated' => $_POST['lastUpdated'],
          ':SharedIdP' => isset($_POST['sharedIdP']) ? 1 : 0));
      } else {
        $this->errors .= "AL should be between 1 and 3\n";
      }
    } else {
      $this->errors .= "Missing POST variable(s)\n";
    }
    return false;
  }
  public function BindIdP2IMPS($entity_Id, $imps_Id) {
    $impsHandler = $this->config->getDb()->prepare('INSERT INTO `IdpIMPS`
      (`entity_id`, `IMPS_id`) VALUES
      (:Entity_id, :IMPS_id);');
    $impsHandler->execute(array('Entity_id' => $entity_Id, self::BIND_IMPS_ID => $imps_Id));
  }

  public function validateIMPS($entity_Id, $imps_Id, $userId) {
    $checkHandler = $this->config->getDb()->prepare(
      'SELECT * FROM `IdpIMPS`
      WHERE `entity_id` = :Entity_id AND
        `IMPS_id` = :IMPS_id;');
    $checkHandler->execute(array('Entity_id' => $entity_Id, self::BIND_IMPS_ID => $imps_Id));
    $impsHandler = $this->config->getDb()->prepare(
      'SELECT `IMPS`.`id`, `name`, `lastValidated`, `lastUpdated` , `email`, `fullName`
      FROM `IMPS`
      LEFT JOIN `Users` ON `Users`.`id` = `IMPS`.`user_id`
      WHERE `IMPS`.`id` = :IMPS_id;');
    $idpsHandler = $this->config->getDb()->prepare(
      'SELECT `entity_id`, `entityID` FROM `IdpIMPS`, `Entities`
      WHERE `IMPS_id` = :IMPS_id AND
        `entity_id` = `Entities`.`id`;');
    $assuranceHandler = $this->config->getDb()->prepare(
      "SELECT `attribute`
      FROM `EntityAttributes`
      WHERE `entity_id` = :Entity_id AND
        `type` = 'assurance-certification' AND
        `attribute` LIKE '%http://www.swamid.se/policy/assurance/al%'
      ORDER BY attribute DESC
      LIMIT 1");
    $impsHandler->execute(array(self::BIND_IMPS_ID => $imps_Id));
    if ($checkHandler->fetch()) {
      if ($imps = $impsHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($imps['lastUpdated'] < $this->config->getIMPS()['oldDate']) {
          printf('    <div class="row alert alert-danger" role="alert">
          <div class="col">
            <div class="row"><b>Error:</b></div>
            <div class="row"><p><b>Updated IMPS required!</b><br>Current approved IMPS is based on a earlier version of the assurance profile.</p></div>
          </div>%s    </div>', "\n");
          return false;
        }
        if (isset($_GET['FormVisit'])) {
          if (isset($_GET['impsIsValid'])) {
            $impsConfirmHandler = $this->config->getDb()->prepare(
              'UPDATE `IMPS`
              SET `lastValidated` = NOW(), `user_id` = :User_id
              WHERE `id` = :IMPS_id;');
            $impsConfirmHandler->execute(array(self::BIND_IMPS_ID => $imps_Id, self::BIND_USER_ID => $userId));
            return true;
          } else {
            printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <div class="row"><b>Error:</b></div>%s        <div class="row">You must check that you confirm!</div>%s      </div>%s    </div>', "\n", "\n", "\n", "\n", "\n", "\n");
          }
        }
        $validatedBy = $imps['lastUpdated'] == substr($imps['lastValidated'], 0 ,10) ? '(BoT)' : $imps['fullName'] . "(" . $imps['email'] . ")";
        printf ('      <div class="row">
        <div class="col">
          <h4>Confirmation of Identity Management Practice Statement (IMPS)</h4>
          <ul>
            <li>Name of IMPS : %s</li>
            <li>Accepted by Board of Trustees : %s</li>
            <li>Last validated : %s</li>
            <li>Last validated by : %s</li>
          </ul>
          If you are missing your IMPS please contact Swamid Operations<br>
          <br>
          The following Identity Providers are bound to this IMPS :
          <ul>%s',
          $imps['name'], substr($imps['lastUpdated'], 0, 10),
          substr($imps['lastValidated'], 0, 10), $validatedBy, "\n");
        $idpsHandler->execute(array(self::BIND_IMPS_ID => $imps_Id));
        while ($idp = $idpsHandler->fetch(PDO::FETCH_ASSOC)) {
          $assuranceHandler->execute(array('Entity_id' => $entity_Id));
          if ($assurance = $assuranceHandler->fetch(PDO::FETCH_ASSOC)) {
            $assuranceLevel = substr($assurance['attribute'],40,1);
          } else {
            $assuranceLevel = 0;
          }
          printf('           <li>%s (AL%d)</li>%s', $idp['entityID'], $assuranceLevel, "\n");
        }
        printf('          </ul>
          <form>
            <input type="hidden" name="Entity" value="%d">
            <input type="hidden" name="ImpsId" value="%d">
            <input type="hidden" name="FormVisit" value="true">
            <input type="checkbox" id="impsIsValid" name="impsIsValid">
            <label for="impsIsValid">On behalf of our Member Organisation, I confirm that our IMPS is accurate and valid, and that the Identity Providers adhere to it.</label><br>
            <br>
            <input type="submit" name="action" value="Confirm IMPS">
          </form>
          <a href=".?showEntity=%d"><button>Return to Entity</button></a>
        </div>%s      </div>', $entity_Id, $imps_Id, $entity_Id, "\n");
      } else {
        printf('    <div class="row alert alert-danger" role="alert">%s      <div class="col">
        <div class="row"><b>Error:</b></div>
        <div class="row">No IMPS found!</div>%s      </div>%s    </div>', "\n", "\n", "\n");
      }
    } else {
      printf('    <div class="row alert alert-danger" role="alert">
      <div class="col">
        <div class="row"><b>Error:</b></div>
        <div class="row">Idp and IMPS is not connected!</div>
      </div>%s    </div>', "\n");
    }
    return false;
    }
}
