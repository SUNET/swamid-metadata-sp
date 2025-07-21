<?php
namespace metadata;

use PDO;

class IMPS {
  use CommonTrait;
  const BIND_ID = ':Id';
  const BIND_IMPS_ID = 'IMPS_id';
  const BIND_LANG = ':Lang';
  const BIND_USER_ID = 'User_id';

  const TEXT_MISSING_POST = "Missing POST variable(s)\n";

  private $config;
  private $errors = '';

  /**
   * Setup the class
   *
   * @return void
   */
  public function __construct() {
    global $config;
    if (isset($config)) {
      $this->config = $config;
    } else {
      $this->config = new Configuration();
    }
  }

  /**
   * Edit IMPS
   *
   * @param int $id Id of IMPS to edit
   *
   * @return void
   */
  public function editIMPS($id) {
    if ($this->errors != '') {
      printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">
      <b>Errors:</b><br>
      %s%s      </div>%s    </div>%s', "\n", "\n", str_ireplace("\n", "<br>", $this->errors), "\n", "\n", "\n");
    }
    print '    <div class="row">
      <div class="col">' . "\n";

    $organizationsHandler = $this->config->getDb()->prepare(
      "SELECT `id`, `OrganizationDisplayName`
      FROM `OrganizationInfo`, `OrganizationInfoData`
      WHERE `notMemberAfter` IS NULL AND
        `OrganizationInfo`.`id` = `OrganizationInfoData`.`OrganizationInfo_id` AND
        `lang` = 'en'
      ORDER BY `OrganizationDisplayName`;");
    $impsHandler = $this->config->getDb()->prepare(
      'SELECT `IMPS`.`id`, `name`, `maximumAL`, `lastUpdated`, `sharedIdp`, `OrganizationInfo_id`
      FROM `IMPS`
      WHERE `IMPS`.`id` = :Id;');
    $impsHandler->execute(array(self::BIND_ID => $id));
    if ($id == 0 || $imps = $impsHandler->fetch(PDO::FETCH_ASSOC)) {
      $name = isset($_POST['name']) ? $_POST['name'] : $imps['name'];
      $maximumAL = isset($_POST['maximumAL']) ? $_POST['maximumAL'] : $imps['maximumAL'];
      $lastUpdated = isset($_POST['lastUpdated']) ? $_POST['lastUpdated'] : $imps['lastUpdated'];
      $sharedIdp = isset($_POST['sharedIdp']) ? true : $imps['sharedIdp'];

      printf('        <form action="?action=Members&subAction=saveImps&id=%d&tab=imps#imps-%d" method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col-2">Organization</div>
            <div class="col">
              <select name="organizationId">%s', $imps['id'], $imps['id'], "\n");
      $organizationsHandler->execute();
      while ($organization = $organizationsHandler->fetch(PDO::FETCH_ASSOC)) {
        printf('               <option value="%d"%s>%s</option>%s',
          $organization['id'], $organization['id'] == $imps['OrganizationInfo_id'] ? ' selected' : '',
          $organization['OrganizationDisplayName'], "\n");
      }
      printf('              </select>
            </div>
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
        <a href="./?action=Members&tab=imps&id=%d#imps-%d"><button>Back</button></a>%s',
        htmlspecialchars($name), $maximumAL, htmlspecialchars($lastUpdated),
        $sharedIdp ? ' checked' : '', $imps['id'], $imps['id'], "\n");
    } else {
      print '        Can\'t find IMPS';
    }
    print '      </div><!-- end col -->
    </div><!-- end row -->';
  }

  /**
   * Save IMPS into database
   *
   * @param int $id Id of IMPS
   *
   * @return bool|PDO
   */
  public function saveIMPS($id) {
    if (isset($_POST['name']) && isset($_POST['maximumAL']) && isset($_POST['lastUpdated']) && isset($_POST['organizationId'])) {
      $maximumAL = $_POST['maximumAL'];
      $this->errors .= $_POST['name'] == '' ? "Name should not be empty.\n" : '';
      $this->errors .= ($maximumAL < 1 || $maximumAL > 3) ? "AL should be between 1 and 3\n" : '';
      $this->errors .= checkdate(intval(substr($_POST['lastUpdated'],5,2)), intval(substr($_POST['lastUpdated'],8,2)), intval(substr($_POST['lastUpdated'],0,4)))
        ? '' : "Invalid BOT date.\n";
    } else {
      $this->errors .= self::TEXT_MISSING_POST;
    }
    if ($this->errors != '') {
      return false;
    }
    if ($id == 0) {
      $impsHandler = $this->config->getDb()->prepare(
        'INSERT INTO `IMPS`
          (`name`,  `maximumAL`, `lastUpdated`, `sharedIdp`, `OrganizationInfo_id`)
        VALUES (:Name, :MaximunAL, :LastUpdated, :SharedIdP, :OrganizationInfoId);');
    } else {
      $impsHandler = $this->config->getDb()->prepare(
        'UPDATE `IMPS`
        SET `name` = :Name, `maximumAL` = :MaximunAL,
          `lastUpdated` = :LastUpdated,
          `lastValidated` = IF(lastValidated < :LastUpdated, :LastUpdated, lastValidated), `sharedIdp` = :SharedIdP,
          `OrganizationInfo_id` = :OrganizationInfoId
        WHERE `id` = :Id;');
    }
    $updatedArray = array(
      ':Name' => $_POST['name'],
      ':MaximunAL' => $maximumAL,
      ':LastUpdated' => $_POST['lastUpdated'],
      ':SharedIdP' => isset($_POST['sharedIdP']) ? 1 : 0,
      ':OrganizationInfoId' => $_POST['organizationId']);
    if ($id > 0) {
      $updatedArray[self::BIND_ID] = $id;
    }
    return $impsHandler->execute($updatedArray);
  }

  /**
   * Removed IMPS from database
   *
   * @param int $id Id of IMPS
   *
   * @return bool
   */
  public function removeImps($id) {
    $impsHandler = $this->config->getDb()->prepare(
      'SELECT `IMPS`.`id`, `name`, `maximumAL`, `lastUpdated`, `sharedIdp`
      FROM `IMPS`
      WHERE `IMPS`.`id` = :Id;');
    $impsHandler->execute(array(self::BIND_ID => $id));
    if ($imps = $impsHandler->fetch(PDO::FETCH_ASSOC)) {
      if (isset($_POST['Remove'])) {
        $impsRemoveHandler = $this->config->getDb()->prepare(
          'DELETE FROM `IMPS`
          WHERE `IMPS`.`id` = :Id;');
        return $impsRemoveHandler->execute(array(self::BIND_ID => $id));
      } else {
        printf('        <form action="?action=Members&subAction=removeImps&id=%d&tab=imps#imps-%d" method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col">Are you sure that you want to remove the IMPS below ? </h4></div>
          </div>
          <div class="row">
            <div class="col-2">Name</div>
            <div class="col">%s</div>
          </div>
          <div class="row">
            <div class="col-2">Allowed maximum AL</div>
            <div class="col">%d</div>
          </div>
          <div class="row">
            <div class="col-2">BOT decision</div>
            <div class="col">%s</div>
          </div>
          <input type="submit" name="Remove" value="Remove">
        </form>
        <a href="./?action=Members&tab=imps&id=%d#imps-%d"><button>Back</button></a>%s',
        $imps['id'], $imps['id'], $imps['name'], $imps['maximumAL'], $imps['lastUpdated'], $imps['id'], $imps['id'], "\n");
      }
    } else {
      print '        Can\'t find IMPS';
    }
    return false;
  }

  /**
   * Bind an IMPS to an Entity
   *
   * @param int $entity_Id Id of entity that the IMPS belongs to
   *
   * @param int $imps_Id Id of IMPS
   *
   * @return void
   */
  public function bindIdP2IMPS($entity_Id, $imps_Id) {
    $impsHandler = $this->config->getDb()->prepare('INSERT INTO `IdpIMPS`
      (`entity_id`, `IMPS_id`) VALUES
      (:Entity_id, :IMPS_id);');
    $impsHandler->execute(array('Entity_id' => $entity_Id, self::BIND_IMPS_ID => $imps_Id));
  }

  /**
   * Validate IMPS
   *
   * * Show form for Validation
   * * Updated IMPS with Validation
   *
   * @param int $entity_Id  Id of entity that the IMPS belongs to
   *
   * @param int $imps_Id  Id of IMPS
   *
   * @param int $userId Id of user validating the IMPS
   *
   * @return bool|PDO
   */
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

  /**
   * Edit Organization
   *
   * @param int $id Id of Organization to edit
   *
   * @return void
   */
  public function editOrganization($id) {
    $showAllOrgs = isset($_GET['showAllOrgs']) ? '&showAllOrgs' : '';
    if ($this->errors != '') {
      printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">
      <b>Errors:</b><br>
      %s%s      </div>%s    </div>%s', "\n", "\n", str_ireplace("\n", "<br>", $this->errors), "\n", "\n", "\n");
    }
    print '    <div class="row">
      <div class="col">' . "\n";

    $organizationsHandler = $this->config->getDb()->prepare(
      'SELECT `id`, `memberSince`, `notMemberAfter`
      FROM `OrganizationInfo`
      WHERE `id` = :Id;');
    $organizationsDataHandler = $this->config->getDb()->prepare(
      'SELECT `lang`, `OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`
      FROM `OrganizationInfoData`
      WHERE `OrganizationInfo_id` = :Id
      ORDER BY `lang`;');

    $allLang = array();
    foreach ($this->config->getFederation()['languages'] as $lang) {
      $allLang[$lang] = false;
    }
    $organizationsHandler->execute(array(self::BIND_ID => $id));
    if ($id == 0 || $organization = $organizationsHandler->fetch(PDO::FETCH_ASSOC)) {
      $usedLang = $allLang;
      $organizationsDataHandler->execute(array(self::BIND_ID => $id));
      if ($id == 0) {
        $organization['id'] = 0;
        $organization['memberSince'] = '';
        $organization['notMemberAfter'] = '';
      }
      $memberSince = isset($_POST['memberSince']) ? $_POST['memberSince'] : $organization['memberSince'];
      $notMemberAfter = isset($_POST['notMemberAfter']) ? $_POST['notMemberAfter'] : $organization['notMemberAfter'];

      printf('        <form action="?action=Members&subAction=saveOrganization&id=%d&tab=organizations%s#org-%d" method="POST" enctype="multipart/form-data">%s',
        $organization['id'], $showAllOrgs, $organization['id'], "\n");
      while($organizationData = $organizationsDataHandler->fetch(PDO::FETCH_ASSOC)) {
        $lang = $organizationData['lang'];
        $orgName = isset($_POST['OrganizationName'][$lang]) ? $_POST['OrganizationName'][$lang] : $organizationData['OrganizationName'];
        $orgDisplayName = isset($_POST['OrganizationDisplayName'][$lang]) ? $_POST['OrganizationDisplayName'][$lang] : $organizationData['OrganizationDisplayName'];
        $orgURL = isset($_POST['OrganizationURL'][$lang]) ? $_POST['OrganizationURL'][$lang] : $organizationData['OrganizationURL'];
        $this->printOrganization($lang, $orgName, $orgDisplayName, $orgURL);
        $usedLang[$lang] = true;
      }
      foreach ($this->config->getFederation()['languages'] as $lang) {
        if (! $usedLang[$lang]) {
          $orgName = isset($_POST['OrganizationName'][$lang]) ? $_POST['OrganizationName'][$lang] : '';
          $orgDisplayName = isset($_POST['OrganizationDisplayName'][$lang]) ? $_POST['OrganizationDisplayName'][$lang] : '';
          $orgURL = isset($_POST['OrganizationURL'][$lang]) ? $_POST['OrganizationURL'][$lang] : '';
          $this->printOrganization($lang, $orgName, $orgDisplayName, $orgURL);
        }
      }
      printf('          <div class="row">
            <div class="col"><h5>Membership</h5></div>
          </div>
          <div class="row">
            <div class="col-2">Member Since</div>
            <div class="col"><input type="text" name="memberSince" value="%s" size="10"></div>
          </div>
          <div class="row">
            <div class="col-2">Left</div>
            <div class="col"><input type="text" name="notMemberAfter" value="%s" size="10"></div>
          </div>
          <input type="submit" name="action" value="Add/Update">
        </form>
        <a href="./?action=Members&tab=organizations&id=%d%s#org-%d"><button>Back</button></a>%s',
        htmlspecialchars($memberSince), htmlspecialchars($notMemberAfter),
        $organization['id'], $showAllOrgs, $organization['id'], "\n");
    } else {
      print '        Can\'t find Organization';
    }
    print '      </div><!-- end col -->
    </div><!-- end row -->';
  }

  /**
   * Save Organization into database
   *
   * @param int $id Id of Organization
   *
   * @return bool|PDO
   */
  public function saveOrganization($id) {
    foreach (array('memberSince', 'notMemberAfter') as $key) {
      if (isset($_POST[$key])) {
        $this->errors = ($_POST[$key] == '' ||
          checkdate(intval(substr($_POST[$key],5,2)), intval(substr($_POST[$key],8,2)), intval(substr($_POST[$key],0,4))))
          ? '' : "Invalid date. \n";
      } else {
        $this->errors = self::TEXT_MISSING_POST;
      }
    }
    foreach (array('OrganizationName', 'OrganizationDisplayName', 'OrganizationURL') as $key) {
      foreach ($this->config->getFederation()['languages'] as $lang) {
        if (isset($_POST[$key][$lang])) {
          switch ($key) {
            case 'OrganizationName' :
              $this->errors .= $_POST[$key][$lang] == '' ? "Missing value for OrganizationName[$lang]. Must not be empty\n": '';
              break;
            case 'OrganizationDisplayName' :
              $this->errors .= $_POST[$key][$lang] == '' ? "Missing value for OrganizationDisplayName[$lang]. Must not be empty\n": '';
              break;
            case 'OrganizationURL' :
              $this->errors .= $_POST[$key][$lang] == '' ? "Missing value for OrganizationURL[$lang]. Must not be empty\n": '';
              break;
            default :
          }
        } else {
          $this->errors = self::TEXT_MISSING_POST;
        }
      }
    }
    if ($this->errors != '') { return false; }

    $clearOrganizationsDataHandler = $this->config->getDb()->prepare(
      'DELETE FROM `OrganizationInfoData`
      WHERE `OrganizationInfo_id` = :Id;');
    $addOrganizationsDataHandler = $this->config->getDb()->prepare(
      'INSERT INTO `OrganizationInfoData`
        (`OrganizationInfo_id`, `lang`, `OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`)
      VAlUES
        (:Id, :Lang, :OrganizationName, :OrganizationDisplayName, :OrganizationURL);');
    if ($id == 0) {
      $organizationsHandler = $this->config->getDb()->prepare(
        'INSERT INTO `OrganizationInfo`
          (`memberSince`, `notMemberAfter`)
        VAlUES
          (:memberSince, :notMemberAfter);');
    } else {
      $organizationsHandler = $this->config->getDb()->prepare(
          'UPDATE `OrganizationInfo`
          SET `memberSince` = :memberSince,
            `notMemberAfter` = :notMemberAfter
          WHERE `id` = :Id;');
    }
    $updatedArray = array(
      'memberSince' => $_POST['memberSince'] == '' ? null : $_POST['memberSince'],
      'notMemberAfter' => $_POST['notMemberAfter'] == '' ? null : $_POST['notMemberAfter']);
    if ($id > 0) {
      $updatedArray[self::BIND_ID] = $id;
      $result =  $organizationsHandler->execute($updatedArray);
    } else {
      $result =  $organizationsHandler->execute($updatedArray);
      $id = $this->config->getDb()->lastInsertId();
    }

    $clearOrganizationsDataHandler->execute(array(self::BIND_ID => $id));
    foreach ($this->config->getFederation()['languages'] as $lang) {
      $result = $result & $addOrganizationsDataHandler->execute(array(
        self::BIND_ID => $id,
        self::BIND_LANG => $lang,
        'OrganizationName' => $_POST['OrganizationName'][$lang],
        'OrganizationURL' => $_POST['OrganizationURL'][$lang],
        'OrganizationDisplayName' => $_POST['OrganizationDisplayName'][$lang]));
    }
    return $result;
  }

  /**
   * Removed Organization from database
   *
   * @param int $id Id of Organization
   *
   * @return bool
   */
  public function removeOrganization($id) {
    $organizationsHandler = $this->config->getDb()->prepare(
      'SELECT `id`, `memberSince`, `notMemberAfter`
      FROM `OrganizationInfo`
      WHERE `id` = :Id;');
    $organizationsHandler->execute(array(self::BIND_ID => $id));
    if ($organization = $organizationsHandler->fetch(PDO::FETCH_ASSOC)) {
      if (isset($_POST['Remove'])) {
        $organizationRemoveHandler = $this->config->getDb()->prepare(
          'DELETE FROM `OrganizationInfo`
          WHERE `id` = :Id;');
        return $organizationRemoveHandler->execute(array(self::BIND_ID => $id));
      } else {
        $impsHandler = $this->config->getDb()->prepare(
          'SELECT `IMPS`.`id`, `name`
          FROM `IMPS`
          WHERE  `OrganizationInfo_id` = :Id;');
        $impsHandler->execute(array(self::BIND_ID => $id));
        if ($imps = $impsHandler->fetch(PDO::FETCH_ASSOC)) {
          printf('        <div class="row">
          <div class="col">The following IMPS:es are bound to this Organization :</div>
        </div>
        <div class="row">
          <div class="col"><ul>%s', "\n");
          do {
            printf('            <li><a href="?action=Members&tab=imps&id=%d#imps-%d" target="_blank">%s</a></li>%s',
              $imps['id'], $imps['id'], $imps['name'], "\n");
          } while ($imps = $impsHandler->fetch(PDO::FETCH_ASSOC));
          printf('          </ul></div>%s        </div>%s', "\n", "\n");
        } else {
          $this->showRemoveOrganizationInfo($organization);
        }
      }
    } else {
      print '        Can\'t find Organization';
    }
    return false;
  }

  /**
   * Show form before removing an Organization
   *
   * @param array $organization
   *
   * @return void
   */
  private function showRemoveOrganizationInfo($organization) {
    $showAllOrgs = isset($_GET['showAllOrgs']) ? '&showAllOrgs' : '';
    $organizationsDataHandler = $this->config->getDb()->prepare(
      'SELECT `lang`, `OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`
      FROM `OrganizationInfoData`
      WHERE `OrganizationInfo_id` = :Id;');
    $organizationsDataHandler->execute(array(self::BIND_ID => $organization['id']));
    printf('        <div class="row">
          <div class="col">Are you sure that you want to remove the Organization below ? </h4></div>
        </div>
        <form action="?action=Members&subAction=removeOrganization&id=%d&tab=organizations%s#org-%d" method="POST" enctype="multipart/form-data">%s',
      $organization['id'], $showAllOrgs, $organization['id'], "\n");
    while ($organizationData = $organizationsDataHandler->fetch(PDO::FETCH_ASSOC)) {
      $this->printOrganization($organizationData['lang'], $organizationData['OrganizationName'],
        $organizationData['OrganizationDisplayName'], $organizationData['OrganizationURL'], true);
    }
    printf('          <div class="row">
            <div class="col"><h5>Membership</h5></div>
          </div>
          <div class="row">
            <div class="col-2">Member Since</div>
            <div class="col">%s</div>
          </div>
          <div class="row">
            <div class="col-2">Left</div>
            <div class="col">%s</div>
          </div>
          <input type="submit" name="Remove" value="Remove">
        </form>
        <a href="./?action=Members&tab=organizations&id=%d%s#org-%d"><button>Back</button></a>%s',
      $organization['memberSince'], $organization['notMemberAfter'], $organization['id'], $showAllOrgs, $organization['id'], "\n");
  }

  /**
   * Print OrgInfo for one language
   *
   * @param string $lang
   *
   * @param string $orgName
   *
   * @param string $orgDisplayName
   *
   * @param string $orgURL
   *
   * @return void
   */
  private function printOrganization($lang, $orgName, $orgDisplayName, $orgURL, $readOnly = false) {
    $readOnlyHTML = $readOnly ? ' readonly' : '';
    printf('          <div class="row">
            <div class="col"><h5>%s</h5></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationName</div>
            <div class="col"><input type="text" name="OrganizationName[%s]" value="%s" size="30"%s></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationDisplayName</div>
            <div class="col"><input type="text" name="OrganizationDisplayName[%s]" value="%s" size="30"%s></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationURL</div>
            <div class="col"><input type="text" name="OrganizationURL[%s]" value="%s" size="30"%s></div>
          </div>%s',
      isset(self::LANG_CODES[$lang]) ? self::LANG_CODES[$lang] : sprintf('Unkown lang code: %s', $lang),
      $lang, htmlspecialchars($orgName), $readOnlyHTML,
      $lang, htmlspecialchars($orgDisplayName), $readOnlyHTML,
      $lang, htmlspecialchars($orgURL), $readOnlyHTML, "\n");
  }

  /**
   * Create a new Organization
   *
   * @param int $entitiesId Id of Entity top copy Organization info from
   *
   * @return void
   */
  public function createOrganizationFromEntity($entitiesId) {
    $organizationHandler = $this->config->getDb()->prepare('SELECT `element`, `data`
      FROM `Organization` WHERE `entity_id` = :Id AND `lang` = :Lang;');
    $entityHandler = $this->config->getDb()->prepare(
      'UPDATE `Entities`
      SET `OrganizationInfo_id` = :OrgInfoId
      WHERE `id` = :Id;');
    $organizationsInfoHandler = $this->config->getDb()->prepare(
      'INSERT INTO `OrganizationInfo` (`memberSince`, `notMemberAfter`)
      VALUES (NULL, NULL);');
    $organizationsInfoDataHandler = $this->config->getDb()->prepare(
      'INSERT INTO `OrganizationInfoData`
        (`OrganizationInfo_id`, `lang`, `OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`)
      VALUES (:Id, :Lang, :OrganizationName, :OrganizationDisplayName, :OrganizationURL);');
    $organizationData = array();

    $organizationsInfoHandler->execute();
    $organizationsInfoId = $this->config->getDb()->lastInsertId();
    foreach ($this->config->getFederation()['languages'] as $lang) {
      $organizationHandler->execute(array(self::BIND_ID => $entitiesId, self::BIND_LANG => $lang));
      $organizationData['OrganizationName'] = '';
      $organizationData['OrganizationURL'] = '';
      $organizationData['OrganizationDisplayName'] = '';
      while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
        $organizationData[$organization['element']] = $organization['data'];
      }
      $organizationsInfoDataHandler->execute(array(
        self::BIND_ID => $organizationsInfoId,
        self::BIND_LANG => $lang,
        'OrganizationName' => $organizationData['OrganizationName'],
        'OrganizationURL' => $organizationData['OrganizationURL'],
        'OrganizationDisplayName' => $organizationData['OrganizationDisplayName']));
    }
    $entityHandler->execute(array(self::BIND_ID => $entitiesId, 'OrgInfoId' => $organizationsInfoId));
  }
}
