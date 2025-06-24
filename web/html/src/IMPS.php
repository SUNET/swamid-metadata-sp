<?php
namespace metadata;

use PDO;

class IMPS {
  const BIND_ID = ':Id';
  const BIND_IMPS_ID = 'IMPS_id';
  const BIND_USER_ID = ':User_id';

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
      'SELECT `id`, `OrganizationDisplayNameSv`
      FROM `OrganizationInfo`
      WHERE `notMemberAfter` IS NULL
      ORDER BY `OrganizationDisplayNameSv`;');
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
          $organization['OrganizationDisplayNameSv'], "\n");
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
      $this->errors .= "Missing POST variable(s)\n";
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
  public function BindIdP2IMPS($entity_Id, $imps_Id) {
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
      'SELECT `id`, `OrganizationNameSv`, `OrganizationDisplayNameSv`, `OrganizationURLSv`, `OrganizationNameEn`,
        `OrganizationDisplayNameEn`, `OrganizationURLEn`, `memberSince`, `notMemberAfter`
      FROM `OrganizationInfo`
      WHERE `id` = :Id;');
    $organizationsHandler->execute(array(self::BIND_ID => $id));
    if ($id == 0 || $organization = $organizationsHandler->fetch(PDO::FETCH_ASSOC)) {
      $orgNameSv = isset($_POST['OrganizationNameSv']) ? $_POST['OrganizationNameSv'] : $organization['OrganizationNameSv'];
      $orgDisplayNameSv = isset($_POST['OrganizationDisplayNameSv']) ? $_POST['OrganizationDisplayNameSv'] : $organization['OrganizationDisplayNameSv'];
      $orgURLSv = isset($_POST['OrganizationURLSv']) ? $_POST['OrganizationURLSv'] : $organization['OrganizationURLSv'];
      $orgNameEn = isset($_POST['OrganizationNameEn']) ? $_POST['OrganizationNameEn'] : $organization['OrganizationNameEn'];
      $orgDisplayNameEn = isset($_POST['OrganizationDisplayNameEn']) ? $_POST['OrganizationDisplayNameEn'] : $organization['OrganizationDisplayNameEn'];
      $orgURLEn = isset($_POST['OrganizationURLEn']) ? $_POST['OrganizationURLEn'] : $organization['OrganizationURLEn'];
      $memberSince = isset($_POST['memberSince']) ? $_POST['memberSince'] : $organization['memberSince'];
      $notMemberAfter = isset($_POST['notMemberAfter']) ? $_POST['notMemberAfter'] : $organization['notMemberAfter'];

      printf('        <form action="?action=Members&subAction=saveOrganization&id=%d&tab=organizations%s#org-%d" method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col"><h5>Swedish</h5></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationName</div>
            <div class="col"><input type="text" name="OrganizationNameSv" value="%s" size="30"></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationDisplayName</div>
            <div class="col"><input type="text" name="OrganizationDisplayNameSv" value="%s" size="30"></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationURL</div>
            <div class="col"><input type="text" name="OrganizationURLSv" value="%s" size="30"></div>
          </div>
          <div class="row">
            <div class="col"><h5>English</h5></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationName</div>
            <div class="col"><input type="text" name="OrganizationNameEn" value="%s" size="30"></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationDisplayName</div>
            <div class="col"><input type="text" name="OrganizationDisplayNameEn" value="%s" size="30"></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationURL</div>
            <div class="col"><input type="text" name="OrganizationURLEn" value="%s" size="30"></div>
          </div>
          <div class="row">
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
          <input type="submit">
        </form>
        <a href="./?action=Members&tab=organizations&id=%d%s#org-%d"><button>Back</button></a>%s',
        $organization['id'], $showAllOrgs, $organization['id'],
        htmlspecialchars($orgNameSv), htmlspecialchars($orgDisplayNameSv), htmlspecialchars($orgURLSv),
        htmlspecialchars($orgNameEn), htmlspecialchars($orgDisplayNameEn), htmlspecialchars($orgURLEn),
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
    foreach (array(
      'OrganizationNameSv', 'OrganizationDisplayNameSv', 'OrganizationURLSv',
      'OrganizationNameEn', 'OrganizationDisplayNameEn', 'OrganizationURLEn',
      'memberSince', 'notMemberAfter') as $key) {
      if (isset($_POST[$key])) {
        switch ($key) {
          case 'OrganizationNameSv' :
          case 'OrganizationNameEn' :
            $this->errors .= $_POST[$key] == '' ? "Missing value for OrganizationName. Must not be empty\n": '';
            break;
          case 'OrganizationDisplayNameSv' :
          case 'OrganizationDisplayNameEn' :
            $this->errors .= $_POST[$key] == '' ? "Missing value for OrganizationDisplayName. Must not be empty\n": '';
            break;
          case 'OrganizationURLSv' :
          case 'OrganizationURLEn' :
            $this->errors .= $_POST[$key] == '' ? "Missing value for OrganizationURL. Must not be empty\n": '';
            break;
          case 'memberSince' :
            $this->errors .= $_POST[$key] == '' ? "Missing value for Member Since. Must not be empty\n": '';
          case 'notMemberAfter' :
            $this->errors .= ($_POST[$key] == '' ||
              checkdate(intval(substr($_POST[$key],5,2)), intval(substr($_POST[$key],8,2)), intval(substr($_POST[$key],0,4))))
              ? '' : "Invalid date. \n";
            break;
          default :
        }
      } else {
        $this->errors .= "Missing POST variable(s)\n";
        return false;
      }
    }
    if ($this->errors != '') { return false; }

    if ($id == 0) {
      $organizationsHandler = $this->config->getDb()->prepare(
        'INSERT INTO `OrganizationInfo`
          (`OrganizationNameSv`, `OrganizationDisplayNameSv`, `OrganizationURLSv`,
          `OrganizationNameEn`, `OrganizationDisplayNameEn`, `OrganizationURLEn`,
          `memberSince`, `notMemberAfter`)
        VAlUES
          (:OrganizationNameSv, :OrganizationDisplayNameSv, :OrganizationURLSv,
          :OrganizationNameEn, :OrganizationDisplayNameEn, :OrganizationURLEn,
          :memberSince, :notMemberAfter);');
    } else {
      $organizationsHandler = $this->config->getDb()->prepare(
          'UPDATE `OrganizationInfo`
          SET `OrganizationNameSv` = :OrganizationNameSv,
            `OrganizationDisplayNameSv` = :OrganizationDisplayNameSv,
            `OrganizationURLSv` = :OrganizationURLSv,
            `OrganizationNameEn` = :OrganizationNameEn,
            `OrganizationDisplayNameEn` = :OrganizationDisplayNameEn,
            `OrganizationURLEn` = :OrganizationURLEn,
            `memberSince` = :memberSince,
            `notMemberAfter` = :notMemberAfter
          WHERE `id` = :Id;');
    }
    $updatedArray = array(
      'OrganizationNameSv' => $_POST['OrganizationNameSv'],
      'OrganizationDisplayNameSv' => $_POST['OrganizationDisplayNameSv'],
      'OrganizationURLSv' => $_POST['OrganizationURLSv'],
      'OrganizationNameEn' => $_POST['OrganizationNameEn'],
      'OrganizationDisplayNameEn' => $_POST['OrganizationDisplayNameEn'],
      'OrganizationURLEn' => $_POST['OrganizationURLEn'],
      'memberSince' => $_POST['memberSince'],
      'notMemberAfter' => $_POST['notMemberAfter'] == '' ? NULL : $_POST['notMemberAfter']);
    if ($id > 0) {
      $updatedArray[self::BIND_ID] = $id;
    }
    return $organizationsHandler->execute($updatedArray);
  }

  /**
   * Removed Organization from database
   *
   * @param int $id Id of Organization
   *
   * @return bool
   */
  public function removeOrganization($id) {
    $showAllOrgs = isset($_GET['showAllOrgs']) ? '&showAllOrgs' : '';
    $organizationsHandler = $this->config->getDb()->prepare(
      'SELECT `id`, `OrganizationNameSv`, `OrganizationDisplayNameSv`, `OrganizationURLSv`, `OrganizationNameEn`,
        `OrganizationDisplayNameEn`, `OrganizationURLEn`, `memberSince`, `notMemberAfter`
      FROM `OrganizationInfo`
      WHERE `id` = :Id;');
    $impsHandler = $this->config->getDb()->prepare(
      'SELECT `IMPS`.`id`, `name`
      FROM `IMPS`
      WHERE  `OrganizationInfo_id` = :Id;');
    $organizationsHandler->execute(array(self::BIND_ID => $id));
    if ($organization = $organizationsHandler->fetch(PDO::FETCH_ASSOC)) {
      if (isset($_POST['Remove'])) {
        $organizationRemoveHandler = $this->config->getDb()->prepare(
          'DELETE FROM `OrganizationInfo`
          WHERE `id` = :Id;');
        return $organizationRemoveHandler->execute(array(self::BIND_ID => $id));
      } else {
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
          printf('        <div class="row">
          <div class="col">Are you sure that you want to remove the Organization below ? </h4></div>
        </div>
        <form action="?action=Members&subAction=removeOrganization&id=%d&tab=organizations%s#org-%d" method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col"><h5>Swedish</h5></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationName</div>
            <div class="col">%s</div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationDisplayName</div>
            <div class="col">%s</div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationURL</div>
            <div class="col">%s</div>
          </div>
          <div class="row">
            <div class="col"><h5>English</h5></div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationName</div>
            <div class="col">%s</div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationDisplayName</div>
            <div class="col">%s</div>
          </div>
          <div class="row">
            <div class="col-2">OrganizationURL</div>
            <div class="col">%s</div>
          </div>
          <div class="row">
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
          $organization['id'], $showAllOrgs, $organization['id'],
          $organization['OrganizationNameSv'], $organization['OrganizationDisplayNameSv'], $organization['OrganizationURLSv'],
          $organization['OrganizationNameEn'], $organization['OrganizationDisplayNameEn'], $organization['OrganizationURLEn'],
          $organization['memberSince'], $organization['notMemberAfter'], $organization['id'], $showAllOrgs, $organization['id'], "\n");
        }
      }
    } else {
      print '        Can\'t find Organization';
    }
    return false;
  }
}
