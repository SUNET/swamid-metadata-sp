<?php
class MetadataDisplay {
  # Setup
  private $metaDb;
  private $collapseIcons = false;
  private $mode = '';
  # From common.php
  private $standardAttributes = array();
  private $langCodes = array();
  private $FriendlyNames = array();

  const BIND_APPROVEDBY = ':ApprovedBy';
  const BIND_ENTITYID = ':EntityID';
  const BIND_ID = ':Id';
  const BIND_INDEX = ':Index';
  const BIND_TYPE = ':Type';
  const BIND_URL = ':URL';

  const SAML_EC_ANONYMOUS = 'https://refeds.org/category/anonymous';
  const SAML_EC_COCOV1 = 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1'; # NOSONAR Should be http://
  const SAML_EC_COCOV2 = 'https://refeds.org/category/code-of-conduct/v2';
  const SAML_EC_ESI = 'https://myacademicid.org/entity-categories/esi';
  const SAML_EC_PERSONALIZED = 'https://refeds.org/category/personalized';
  const SAML_EC_PSEUDONYMOUS = 'https://refeds.org/category/pseudonymous';
  const SAML_EC_RANDS = 'http://refeds.org/category/research-and-scholarship'; # NOSONAR Should be http://

  const HTML_ACTIVE = ' active';
  const HTML_CLASS_ALERT_WARNING = ' class="alert-warning" role="alert"';
  const HTML_CLASS_ALERT_DANGER = ' class="alert-danger" role="alert"';
  const HTML_SHOW_URL = '%s - <a href="?action=showURL&URL=%s" target="_blank">%s</a>%s';
  const HTML_SPACER = '      ';
  const HTML_TARGET_BLANK = '<a href="%s" class="text-%s" target="_blank">%s</a>';
  const HTML_TABLE_END = "    </table>\n";
  const HTML_SELECTED = ' selected';
  const HTML_SHOW = ' show';

  public function __construct() {
    require __DIR__  . '/../config.php'; #NOSONAR
    require __DIR__ . '/common.php'; #NOSONAR

    try {
      $this->metaDb = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
      // set the PDO error mode to exception
      $this->metaDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    $this->collapseIcons = array();
    $this->mode = $Mode;
  }

  ####
  # Shows menu row
  ####
  public function showStatusbar($entityId, $admin = false){
    $entityError = array(
      'saml1Error' => false,
      'algorithmError' => false
    );
    $entityHandler = $this->metaDb->prepare('
      SELECT `entityID`, `isIdP`, `isSP`, `isAA`, `validationOutput`, `warnings`, `errors`, `errorsNB`, `status`
      FROM Entities WHERE `id` = :Id;');
    $urlHandler1 = $this->metaDb->prepare('
      SELECT `status`, `cocov1Status`,  `URL`, `lastValidated`, `validationOutput`
      FROM URLs
      WHERE URL IN (SELECT `data` FROM Mdui WHERE `entity_id` = :Id)');
    $urlHandler2 = $this->metaDb->prepare("
      SELECT `status`, `URL`, `lastValidated`, `validationOutput`
      FROM URLs
      WHERE URL IN (SELECT `URL` FROM EntityURLs WHERE `entity_id` = :Id AND type = 'error')");
    $urlHandler3 = $this->metaDb->prepare("
      SELECT `status`, `URL`, `lastValidated`, `validationOutput`
      FROM URLs
      WHERE URL IN (SELECT `data` FROM Organization WHERE `element` = 'OrganizationURL' AND `entity_id` = :Id)");
    $impsHandler = $this->metaDb->prepare(
      'SELECT `IMPS_id`, `lastValidated`,
        NOW() - INTERVAL 10 MONTH AS `warnDate`,
        NOW() - INTERVAL 12 MONTH AS `errorDate`,
        lastValidated + INTERVAL 12 MONTH AS `expireDate`
      FROM `IdpIMPS`, `IMPS`
      WHERE `IdpIMPS`.`IMPS_id` = `IMPS`.`id` AND
        `IdpIMPS`.`entity_id` = :Id
      ORDER BY `lastValidated`');
    $testResults = $this->metaDb->prepare('SELECT `test`, `result`, `time`
      FROM TestResults WHERE entityID = :EntityID');
    $entityAttributesHandler = $this->metaDb->prepare("SELECT `attribute`
      FROM EntityAttributes WHERE `entity_id` = :Id AND type = :Type;");
    $entityAttributesHandler->bindParam(self::BIND_ID, $entityId);

    $entityHandler->execute(array(self::BIND_ID => $entityId));
    if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
      $errors = '';
      $warnings = '';
      $notice = '';

      $entityError['saml1Error'] = strpos(
        $entity['errors'] . $entity['errorsNB'] . $entity['warnings'],
        'claims support for SAML1.');
      $entityError['saml1Error'] =  strpos($entity['errors'], 'oasis-sstc-saml-bindings-1.1: SAML1 Binding in ') === false ? $entityError['saml1Error'] : true;
      $entityError['algorithmError'] = strpos($entity['errors'], ' is obsolete in xml');

      if ($entity['isIdP']) {
        $ecsTagged = array(self::SAML_EC_ESI => false,
          self::SAML_EC_RANDS => false,
          self::SAML_EC_COCOV1 => false,
          self::SAML_EC_ANONYMOUS => false,
          self::SAML_EC_COCOV2 => false,
          self::SAML_EC_PERSONALIZED => false,
          self::SAML_EC_PSEUDONYMOUS => false);
        $ecsTested = array(
          'anonymous' => false,
          'esi' => false,
          'cocov1-1' => false,
          'cocov2-1' => false,
          'personalized' => false,
          'pseudonymous' => false,
          'rands' => false);

        $entityAttributesHandler->bindValue(self::BIND_TYPE, 'entity-category-support');
        $entityAttributesHandler->execute();
        while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
          $ecsTagged[$attribute['attribute']] = true;
        }

        $testResults->execute(array(self::BIND_ENTITYID => $entity['entityID']));
        while ($testResult = $testResults->fetch(PDO::FETCH_ASSOC)) {
          $ecsTested[$testResult['test']] = true;
          switch ($testResult['test']) {
            case 'rands' :
              $tag = self::SAML_EC_RANDS;
              break;
            case 'cocov1-1' :
              $tag = self::SAML_EC_COCOV1;
              break;
            case 'anonymous':
              $tag = self::SAML_EC_ANONYMOUS;
              break;
            case 'cocov2-1':
              $tag = self::SAML_EC_COCOV2;
              break;
            case 'personalized':
              $tag = self::SAML_EC_PERSONALIZED;
              break;
            case 'pseudonymous':
              $tag = self::SAML_EC_PSEUDONYMOUS;
              break;
            case 'esi':
              $tag = self::SAML_EC_ESI;
              break;
            default :
              printf('Unknown test : %s', $testResult['test']);
          }
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
              if  (! $ecsTagged[$tag]) {
                $warnings .= sprintf('SWAMID Release-check: %s is supported according to release-check', $tag);
                $warnings .= " but not marked in Metadata (EntityAttributes/entity-category-support).\n";
              }
              break;
            case 'Support for CoCo missing, Entity Category Support missing' :
            case 'R&S attribute missing, Entity Category Support missing' :
            case 'CoCo is not supported, BUT Entity Category Support is claimed' :
            case 'R&S attributes missing, BUT Entity Category Support claimed' :
            case 'Anonymous attribute missing, Entity Category Support missing' :
            case 'Personalized attribute missing, Entity Category Support missing' :
            case 'Pseudonymous attribute missing, Entity Category Support missing' :
            case 'Anonymous attributes missing, BUT Entity Category Support claimed' :
            case 'Personalized attributes missing, BUT Entity Category Support claimed' :
            case 'Pseudonymous attributes missing, BUT Entity Category Support claimed' :
            case 'Missing schacPersonalUniqueCode' :
              $errors .= ($ecsTagged[$tag]) ? sprintf("SWAMID Release-check: (%s) %s.\n",
                $testResult['time'], $testResult['result']) : '';
              break;
            default :
              printf('Unknown result : %s', $testResult['result']);
          }
        }
        foreach ($ecsTested as $tag => $tested) {
          if (! $ecsTested[$tag]) {
            $warnings .= sprintf('SWAMID Release-check: Updated test for %s missing please rerun', $tag);
            $warnings .= sprintf(' at <a href="https://%s.release-check.%sswamid.se/">Release-check</a>%s',
              $tag, $this->mode == 'QA' ? 'qa.' : '', "\n");
          }
        }
        // Error URLs
        $urlHandler2->execute(array(self::BIND_ID => $entityId));
        while ($url = $urlHandler2->fetch(PDO::FETCH_ASSOC)) {
          if ($url['status'] > 0) {
            $errors .= sprintf(self::HTML_SHOW_URL,
              $url['validationOutput'], urlencode($url['URL']), $url['URL'], "\n");
          }
        }

        if ($this->mode != 'QA' && $entity['status'] == 1) {
          $impsHandler->execute(array(self::BIND_ID => $entityId));
          if ($imps = $impsHandler->fetch(PDO::FETCH_ASSOC)) {
            if ($imps['warnDate'] > $imps['lastValidated']) {
              if ($imps['errorDate'] > $imps['lastValidated']) {
                $errors .= sprintf('The Member Organisation MUST annually confirm that their approved Identity Management Practice Statement is still accurate.%s', "\n");
              } else {
                $warnings .= sprintf('The Member Organisation MUST annually confirm that their approved Identity Management Practice Statement is still accurate. This must be done before %s.%s', substr($imps['expireDate'], 0, 10), "\n");
              }
            }
          } else {
            $errors .= sprintf('IdP is not bound to any IMPS%s', "\n");
          }
        }
      }

      $coCov1SP = false;
      if ($entity['isSP']) {
        $entityAttributesHandler->bindValue(self::BIND_TYPE, 'entity-category');
        $entityAttributesHandler->execute();
        while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
          if ($attribute['attribute'] == self::SAML_EC_COCOV1) {
            $coCov1SP = true;
          }
        }
      }

      // MDUI
      $urlHandler1->execute(array(self::BIND_ID => $entityId));
      while ($url = $urlHandler1->fetch(PDO::FETCH_ASSOC)) {
        if ($url['status'] > 0 || ($coCov1SP  && $url['cocov1Status'] > 0)) {
          $errors .= sprintf(self::HTML_SHOW_URL,
            $url['validationOutput'], urlencode($url['URL']), $url['URL'], "\n");
        }
      }
      // OrganizationURL
      $urlHandler3->execute(array(self::BIND_ID => $entityId));
      while ($url = $urlHandler3->fetch(PDO::FETCH_ASSOC)) {
        if ($url['status'] > 0) {
          $errors .= sprintf(self::HTML_SHOW_URL,
            $url['validationOutput'], urlencode($url['URL']), $url['URL'], "\n");
        }
      }
      $errors .= $entity['errors'] . $entity['errorsNB'];
      if ($errors != '') {
        printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">
        <b>Errors:</b><br>
        %s%s      </div>%s    </div>', "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n");
      }
      $warnings .= $entity['warnings'];
      if ( $warnings != '') {
        printf('%s    <div class="row alert alert-warning" role="alert">%s      <div class="col">
        <b>Warnings:</b><br>
        %s%s      </div>%s    </div>', "\n", "\n", str_ireplace("\n", "<br>", $warnings), "\n", "\n");
      }

      if ($entity['isAA']) {
        $notice .= 'The AttributeAuthority is a part of the Identity Provider and follow the same rules for SWAMID Tech 5.1.21 and 5.2.x.<br>';
        $notice .= 'If the AttributeAuthority part of the entity is not used SWAMID recommends that is removed.<br>';
      }
      if ($entity['validationOutput'] != '') {
        $notice .= $entity['validationOutput'];
      }
      if ($notice != '') {
        printf('%s    <div class="row alert alert-primary" role="alert">%s      <div class="col">
        <b>Notice:</b><br>
        %s%s      </div>%s    </div>', "\n", "\n", str_ireplace("\n", "<br>", $notice), "\n", "\n");
      }
    }
    if ($admin && $entity['status'] < 4) {
      printf('%s    <div class="row">
    <a href=".?validateEntity=%d">
      <button type="button" class="btn btn-outline-primary">Validate</button>
    </a></div>', "\n", $entityId);
    }
    return $entityError;
  }

  ####
  # Shows CollapseHeader
  ####
  private function showCollapse($title, $name, $haveSub=true, $step=0, $expanded=true,
    $extra = false, $entityId=0, $oldEntityId=0) {
    $spacer = '';
    while ($step > 0 ) {
      $spacer .= self::HTML_SPACER;
      $step--;
    }
    if ($expanded) {
      $icon = 'down';
      $show = 'show ';
    } else {
      $icon = 'right';
      $show = '';
    }
    switch ($extra) {
      case 'SSO' :
        $extraButton = sprintf('<a href="?removeSSO=%d&type=%s"><i class="fas fa-trash"></i></a>', $entityId, $name);
        break;
      case 'EntityAttributes' :
      case 'IdPMDUI' :
      case 'SPMDUI' :
      case 'DiscoHints' :
      case 'IdPKeyInfo' :
      case 'SPKeyInfo' :
      case 'AAKeyInfo' :
      case 'AttributeConsumingService' :
      case 'Organization' :
      case 'ContactPersons' :
        $extraButton = sprintf('<a href="?edit=%s&Entity=%d&oldEntity=%d"><i class="fa fa-pencil-alt"></i></a>',
          $extra, $entityId, $oldEntityId);
        break;
      default :
        $extraButton = '';
    }
    printf('
    %s<h4>
      %s<i id="%s-icon" class="fas fa-chevron-circle-%s"></i>
      %s<a data-toggle="collapse" href="#%s" aria-expanded="%s" aria-controls="%s">%s</a>
      %s%s
    %s</h4>
    %s<div class="%scollapse multi-collapse" id="%s">
    %s  <div class="row">%s',
      $spacer, $spacer, $name, $icon, $spacer, $name, $expanded, $name, $title,
      $spacer, $extraButton, $spacer, $spacer, $show, $name, $spacer, "\n");
    if ($haveSub) {
      printf('%s        <span class="border-right"><div class="col-md-auto"></div></span>%s',$spacer, "\n");
    }
    printf('%s        <div class="col%s">', $spacer, $oldEntityId > 0 ? '-6' : '');
    $this->collapseIcons[] = $name;
  }
  private function showNewCol($step) {
    $spacer = '';
    while ($step > 0 ) {
      $spacer .= self::HTML_SPACER;
      $step--;
    } ?>

        <?=$spacer?></div><!-- end col -->
        <?=$spacer?><div class="col-6"><?php
  }

  ####
  # Shows CollapseEnd
  ####
  private function showCollapseEnd($name, $step = 0){
    $spacer = '';
    while ($step > 0 ) {
      $spacer .= self::HTML_SPACER;
      $step--;
    }?>

        <?=$spacer?></div><!-- end col -->
      <?=$spacer?></div><!-- end row -->
    <?=$spacer?></div><!-- end collapse <?=$name?>--><?php
  }

  ####
  # Shows Info about IMPS connected to this entity
  ####
  public function showIMPS($entityId, $allowEdit = false) {
    $impsListHandler = $this->metaDb->prepare(
      'SELECT `id`, `name`, `maximumAL`
      FROM `IMPS`');
    $displayNameHandler = $this->metaDb->prepare(
      "SELECT `data`
      FROM `Mdui`
      WHERE `element` = 'DisplayName'
        AND  `lang`='sv'
        AND `entity_id` = :Id");
    $impsHandler = $this->metaDb->prepare(
      'SELECT `IMPS`.`id`, `name`, `maximumAL`, `lastValidated`, `lastUpdated` , `email`, `fullName`,
        NOW() - INTERVAL 10 MONTH AS `warnDate`,
        NOW() - INTERVAL 12 MONTH AS `errorDate`
      FROM `IdpIMPS`, `IMPS`
      LEFT JOIN `Users` ON `Users`.`id` = `IMPS`.`user_id`
      WHERE `IdpIMPS`.`IMPS_id` = `IMPS`.`id` AND `IdpIMPS`.`entity_id` = :Id');
    $impsHandler->execute(array(self::BIND_ID => $entityId));
    $this->showCollapse('IMPS', 'IMPS', false, 0, false, false, $entityId, 0);
    if ($imps = $impsHandler->fetch(PDO::FETCH_ASSOC)) {
      while ($imps) {
        $state = $imps['warnDate'] > $imps['lastValidated'] ? 'warning' : 'none';
        $state = $imps['errorDate'] > $imps['lastValidated'] ? 'danger' : $state;

        $validatedBy = $imps['lastUpdated'] == substr($imps['lastValidated'], 0 ,10) ? '(BoT)' : $imps['fullName'] . " (" . $imps['email'] . ")";
        printf ('%s          <div class="alert-%s"><b>%s</b><ul>
            <li>Accepted by Board of Trustees : %s</li>
            <li>Last validated : %s</li>
            <li>Last validated by : %s</li>
          </ul>
          <a href=".?action=Confirm+IMPS&Entity=%d&ImpsId=%d">
            <button type="button" class="btn btn-primary">Validate</button>
          </a></div>',
          "\n", $state, $imps['name'], substr($imps['lastUpdated'], 0, 10),
          substr($imps['lastValidated'], 0, 10), $validatedBy, $entityId, $imps['id']);
        $imps = $impsHandler->fetch(PDO::FETCH_ASSOC);
      }
    } else {
      if ($allowEdit) {
        $displayNameHandler->execute(array(self::BIND_ID => $entityId));
        if (! $displayName = $displayNameHandler->fetch(PDO::FETCH_ASSOC)) {
          $displayName['data'] = 'Unkown';
        }
        $impsListHandler->execute();
        printf ('%s          <div class="alert alert-danger" role="alert">
            IdP is not bound to any IMPS<br>
            Bind to :
            <form>
              <input type="hidden" name="action" value="AddImps2IdP">
              <input type="hidden" name="Entity" value="%d">
              <select name="ImpsId">', "\n", $entityId);
        while ($imps = $impsListHandler->fetch(PDO::FETCH_ASSOC)){
          printf ('                <option%s value="%d">%s</option>',
          $imps['name'] == $displayName['data'] ? self::HTML_SELECTED : '', $imps['id'], $imps['name']);
        }
        printf ('
              </select>
              <input type="submit" value="Bind">
            </form>
          </div>');
      } else {
        printf ('%s          <div class="alert alert-danger" role="alert">
            IdP is not bound to any IMPS
          </div>', "\n");
      }
    }
    $this->showCollapseEnd('IMPS', 0);
  }

  ####
  # Shows EntityAttributes if exists
  ####
  public function showEntityAttributes($entityId, $oldEntityId=0, $allowEdit = false) {
    if ($allowEdit) {
      $this->showCollapse('EntityAttributes', 'Attributes', false, 0, true, 'EntityAttributes',
        $entityId, $oldEntityId);
    } else {
      $this->showCollapse('EntityAttributes', 'Attributes', false, 0, true, false, $entityId, $oldEntityId);
    }
    $this->showEntityAttributesPart($entityId, $oldEntityId, true);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(0);
      $this->showEntityAttributesPart($oldEntityId, $entityId, false);
    }
    $this->showCollapseEnd('Attributes', 0);
  }
  private function showEntityAttributesPart($entityId, $otherEntityId, $added) {
    $entityAttributesHandler = $this->metaDb->prepare('SELECT `type`, `attribute`
      FROM EntityAttributes WHERE `entity_id` = :Id ORDER BY `type`, `attribute`;');
    if ($otherEntityId) {
      $entityAttributesHandler->bindParam(self::BIND_ID, $otherEntityId);
      $entityAttributesHandler->execute();
      while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        $type = $attribute['type'];
        $value = $attribute['attribute'];
        if (! isset($otherAttributeValues[$type])) {
          $otherAttributeValues[$type] = array();
        }
        $otherAttributeValues[$type][$value] = true;
      }
    }
    $entityAttributesHandler->bindParam(self::BIND_ID, $entityId);
    $entityAttributesHandler->execute();

    if ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      $type = $attribute['type'];
      $value = $attribute['attribute'];
      if ($otherEntityId) {
        $state = ($added) ? 'success' : 'danger';
        $state = isset($otherAttributeValues[$type][$value]) ? 'dark' : $state;
      } else {
        $state = 'dark';
      }
      $error = self::HTML_CLASS_ALERT_WARNING;
      if (isset($this->standardAttributes[$type])) {
        foreach ($this->standardAttributes[$type] as $data) {
          if ($data['value'] == $value) {
            $error = ($data['swamidStd']) ? '' : self::HTML_CLASS_ALERT_DANGER;
          }
        }
      }
      ?>

          <b><?=$type?></b>
          <ul>
            <li><div<?=$error?>><span class="text-<?=$state?>"><?=$value?></span></div></li><?php
      $oldType = $type;
      while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
        $type = $attribute['type'];
        $value = $attribute['attribute'];
        if ($otherEntityId) {
          $state = ($added) ? 'success' : 'danger';
          $state = isset($otherAttributeValues[$type][$value]) ? 'dark' : $state;
        } else {
          $state = 'dark';
        }
        $error = self::HTML_CLASS_ALERT_WARNING;
        if (isset($this->standardAttributes[$type])) {
          foreach ($this->standardAttributes[$type] as $data) {
            if ($data['value'] == $value) {
              $error = ($data['swamidStd']) ? '' : self::HTML_CLASS_ALERT_DANGER;
            }
          }
        }
        if ($oldType != $type) {
          print "\n          </ul>";
          printf ("\n          <b>%s</b>\n          <ul>", $type);
          $oldType = $type;
        }
        printf ('%s            <li><div%s><span class="text-%s">%s</span></div></li>', "\n", $error, $state, $value);
      }?>

          </ul><?php
    }
  }

  ####
  # Shows IdP info
  ####
  public function showIdP($entityId, $oldEntityId=0, $allowEdit = false, $removable = false) {
    if ($removable) {
      $removable = 'SSO';
    }
    $this->showCollapse('IdP data', 'IdP', true, 0, true, $removable, $entityId);
    print '
          <div class="row">
            <div class="col-6">';
    $this->showErrorURL($entityId, $oldEntityId, true, $allowEdit);
    $this->showScopes($entityId, $oldEntityId, true, $allowEdit);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(1);
      $this->showErrorURL($oldEntityId, $entityId);
      $this->showScopes($oldEntityId, $entityId);
    }
    print '
            </div><!-- end col -->
          </div><!-- end row -->';
    $this->showCollapse('MDUI', 'UIInfo_IDPSSO', false, 1, true,
      $allowEdit ? 'IdPMDUI' : false, $entityId, $oldEntityId);
    $this->showMDUI($entityId, 'IDPSSO', $oldEntityId, true);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(1);
      $this->showMDUI($oldEntityId, 'IDPSSO', $entityId);
    }
    $this->showCollapseEnd('UIInfo_IdPSSO', 1);
    $this->showCollapse('DiscoHints', 'DiscoHints', false, 1, true,
      $allowEdit ? 'DiscoHints' : false, $entityId, $oldEntityId);
    $this->showDiscoHints($entityId, $oldEntityId, true);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(1);
      $this->showDiscoHints($oldEntityId, $entityId);
    }
    $this->showCollapseEnd('DiscoHints', 1);
    $this->showCollapse('KeyInfo', 'KeyInfo_IdPSSO', false, 1, true,
      $allowEdit ? 'IdPKeyInfo' : false, $entityId, $oldEntityId);
    $this->showKeyInfo($entityId, 'IDPSSO', $oldEntityId, true);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(1);
      $this->showKeyInfo($oldEntityId, 'IDPSSO', $entityId);
    }
    $this->showCollapseEnd('KeyInfo_IdPSSO', 1);
    $this->showCollapseEnd('IdP', 0);
  }

  ####
  # Shows SP info
  ####
  public function showSp($entityId, $oldEntityId=0, $allowEdit = false, $removable = false) {
    if ($removable) {
      $removable = 'SSO';
    }
    $this->showCollapse('SP data', 'SP', true, 0, true, $removable, $entityId);
    $this->showCollapse('MDUI', 'UIInfo_SPSSO', false, 1, true, $allowEdit ? 'SPMDUI' : false, $entityId, $oldEntityId);
    $this->showMDUI($entityId, 'SPSSO', $oldEntityId, true);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(1);
      $this->showMDUI($oldEntityId, 'SPSSO', $entityId);
    }
    $this->showCollapseEnd('UIInfo_SPSSO', 1);

    $this->showCollapse('KeyInfo', 'KeyInfo_SPSSO', false, 1, true,
      $allowEdit ? 'SPKeyInfo' : false, $entityId, $oldEntityId);
    $this->showKeyInfo($entityId, 'SPSSO', $oldEntityId, true);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(1);
      $this->showKeyInfo($oldEntityId, 'SPSSO', $entityId);
    }
    $this->showCollapseEnd('KeyInfo_SPSSO', 1);
    $this->showCollapse('AttributeConsumingService', 'AttributeConsumingService', false, 1, true,
      $allowEdit ? 'AttributeConsumingService' : false, $entityId, $oldEntityId);
    $this->showAttributeConsumingService($entityId, $oldEntityId, true);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(1);
      $this->showAttributeConsumingService($oldEntityId, $entityId);
    }
    $this->showCollapseEnd('AttributeConsumingService', 1);
    $this->showCollapseEnd('SP', 0);
  }

  public function showAA($entityId, $oldEntityId=0, $allowEdit = false, $removable = false) {
    if ($removable) {
      $removable = 'SSO';
    }
    $this->showCollapse('AttributeAuthority', 'AttributeAuthority', true, 0, true, $removable, $entityId);
    $this->showCollapse('KeyInfo', 'KeyInfo_AttributeAuthority', false, 1, true,
      $allowEdit ? 'AAKeyInfo' : false, $entityId, $oldEntityId);
    $this->showKeyInfo($entityId, 'AttributeAuthority', $oldEntityId, true);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(1);
      $this->showKeyInfo($oldEntityId, 'AttributeAuthority', $entityId);
    }
    $this->showCollapseEnd('KeyInfo_AttributeAuthority', 1);
    $this->showCollapseEnd('AttributeAuthority', 0);
  }

  ####
  # Shows erroURL
  ####
  private function showErrorURL($entityId, $otherEntityId=0, $added = false, $allowEdit = false) {
    $errorURLHandler = $this->metaDb->prepare("SELECT DISTINCT `URL`
      FROM EntityURLs WHERE `entity_id` = :Id AND `type` = 'error';");
    if ($otherEntityId) {
      $errorURLHandler->bindParam(self::BIND_ID, $otherEntityId);
      $errorURLHandler->execute();
      if ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC)) {
        $otherURL = $errorURL['URL'];
      } else {
        $otherURL = '';
      }
      $state = ($added) ? 'success' : 'danger';
    } else {
      $otherURL = '';
      $state = 'dark';
    }
    $errorURLHandler->bindParam(self::BIND_ID, $entityId);
    $errorURLHandler->execute();
    $edit = $allowEdit ?
      sprintf(' <a href="?edit=IdPErrorURL&Entity=%d&oldEntity=%d"><i class="fa fa-pencil-alt"></i></a>',
      $entityId, $otherEntityId) : '';
    if ($errorURL = $errorURLHandler->fetch(PDO::FETCH_ASSOC)) {
      $thisURL = $errorURL['URL'];
    } else {
      $thisURL = '';
      $state = 'dark';
    }
    if ($otherEntityId) {
      $state = $thisURL == $otherURL ? 'dark' : $state;
    }
    printf('%s              <b>errorURL%s</b>
              <ul><li>
                <p class="text-%s" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;max-width: 30em;">',
      "\n", $edit, $state);
    if ($thisURL != '') {
      printf (self::HTML_TARGET_BLANK, $thisURL, $state, $thisURL);
    } else {
      print 'Missing';
    }
    print '</p></li></ul>';
  }

  ####
  # Shows showScopes
  ####
  private function showScopes($entityId, $otherEntityId=0, $added = false, $allowEdit = false) {
    $scopesHandler = $this->metaDb->prepare('SELECT `scope`, `regexp` FROM Scopes WHERE `entity_id` = :Id;');
    if ($otherEntityId) {
      $scopesHandler->bindParam(self::BIND_ID, $otherEntityId);
      $scopesHandler->execute();
      while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
        $otherScopes[$scope['scope']] = $scope['regexp'];
      }
    }
    $edit = $allowEdit ?
      sprintf(' <a href="?edit=IdPScopes&Entity=%d&oldEntity=%d"><i class="fa fa-pencil-alt"></i></a>',
      $entityId, $otherEntityId) : '';
    print "\n              <b>Scopes$edit</b>
              <ul>\n";
    $scopesHandler->bindParam(self::BIND_ID, $entityId);
    $scopesHandler->execute();
    while ($scope = $scopesHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($otherEntityId) {
        $state = ($added) ? 'success' : 'danger';
        $state = (isset($otherScopes[$scope['scope']]) && $otherScopes[$scope['scope']] == $scope['regexp']) ?
          'dark' : $state;
      } else {
        $state = 'dark';
      }
      printf ('                <li><span class="text-%s">%s (regexp="%s")</span></li>%s',
        $state, $scope['scope'], $scope['regexp'] ? 'true' : 'false', "\n");
    }
    print '              </ul>';
  }

  ####
  # Shows mdui:UIInfo for IdP or SP
  ####
  private function showMDUI($entityId, $type, $otherEntityId = 0, $added = false) {
    $mduiHandler = $this->metaDb->prepare('SELECT `element`, `lang`, `height`, `width`, `data`
      FROM Mdui WHERE `entity_id` = :Id AND `type` = :Type ORDER BY `lang`, `element`;');
    $mduiHandler->bindParam(self::BIND_TYPE, $type);
    $otherMDUIElements = array();
    $mduiHandler->bindParam(self::BIND_ID, $otherEntityId);
    $mduiHandler->execute();
    $urlHandler = $this->metaDb->prepare('SELECT `nosize`, `height`, `width` FROM URLs WHERE `URL` = :URL');
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $element = $mdui['element'];
      $size = $mdui['height'].'x'.$mdui['width'];
      if (! isset($otherMDUIElements[$mdui['lang']]) ) {
        $otherMDUIElements[$mdui['lang']] = array();
      }
      if (! isset($otherMDUIElements[$mdui['lang']][$element]) ) {
        $otherMDUIElements[$mdui['lang']][$element] = array();
      }
      $otherMDUIElements[$mdui['lang']][$element][$size] = array(
          'value' => $mdui['data'],
          'height' => $mdui['height'],
          'width' => $mdui['width'],
          'state' => 'removed');
    }

    $oldLang = 'xxxxxxx';
    $mduiHandler->bindParam(self::BIND_ID, $entityId);
    $mduiHandler->execute();
    $showEndUL = false;
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($oldLang != $mdui['lang']) {
        $lang = $mdui['lang'];
        if (isset($this->langCodes[$lang])) {
          $fullLang = $this->langCodes[$lang];
        } elseif ($lang == "") {
          $fullLang = "(NOT ALLOWED - switch to en/sv)";
        } else {
          $fullLang = "Unknown";
        }

        printf('%s                <b>Lang = "%s" - %s</b>%s                <ul>',
          $showEndUL ? "\n                </ul>\n" : "\n", $lang, $fullLang, "\n");
        $showEndUL = true;
        $oldLang = $lang;
      }
      $element = $mdui['element'];
      $size = $mdui['height'].'x'.$mdui['width'];
      $data = $mdui['data'];
      if ($otherEntityId) {
        $state = ($added) ? 'success' : 'danger';
        if (isset ($otherMDUIElements[$lang]) &&
          isset ($otherMDUIElements[$lang][$element]) &&
          isset ($otherMDUIElements[$lang][$element][$size])) {
          if ($otherMDUIElements[$lang][$element][$size]['value'] == $data) {
            $state = 'dark';
            $otherMDUIElements[$lang][$element][$size]['state'] = 'same';
          } else {
            $otherMDUIElements[$lang][$element][$size]['state'] = 'changed';
          }
        }
      } else {
        $state = 'dark';
      }
      switch ($element) {
        case 'Logo' :
          $urlHandler->execute(array(self::BIND_URL => $data));
          $statusText = '';
          if ($URLInfo = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
            if ($URLInfo['height'] == $mdui['height'] || $URLInfo['nosize'] == 1) {
              $statusIcon = '';
            } else {
              $statusIcon = '<i class="fas fa-exclamation"></i>';
              $statusText .= sprintf('<br><span class="text-danger">Marked height is %s but actual height is %d</span>',
                $mdui['height'], $URLInfo['height']);
            }
            if ($URLInfo['width'] != $mdui['width'] && $URLInfo['nosize'] == 0) {
              $statusIcon = '<i class="fas fa-exclamation"></i>';
              $statusText .= sprintf('<br><span class="text-danger">Marked width is %s but actual width is %d</span>',
                $mdui['width'], $URLInfo['width']);
            }
          } else {
            $statusIcon = '<i class="fas fa-exclamation-triangle"></i>';
          }
          $data = sprintf (self::HTML_TARGET_BLANK, $data, $state, $data);
          printf ('%s                  <li>%s <span class="text-%s">%s (%s) = %s</span>%s</li>',
            "\n", $statusIcon, $state, $element, $size, $data, $statusText);
          break;
        case 'InformationURL' :
        case 'PrivacyStatementURL' :
          $data = sprintf (self::HTML_TARGET_BLANK, $data, $state, $data);
          printf ('%s                  <li><span class="text-%s">%s = %s</span></li>',
          "\n", $state, $element, $data);
          break;
        default :
          printf ('%s                  <li><span class="text-%s">%s = %s</span></li>',
          "\n", $state, $element, $data);
      }
    }
    if ($showEndUL) {
      print "\n                </ul>";
    }
  }

  ####
  # Shows mdui:DiscoHints for IdP
  ####
  private function showDiscoHints($entityId, $otherEntityId=0, $added = false) {
    $mduiHandler = $this->metaDb->prepare("SELECT `element`, `data`
      FROM Mdui WHERE `entity_id` = :Id AND `type` = 'IDPDisco' ORDER BY `element`;");
    $otherMDUIElements = array();
    $mduiHandler->bindParam(self::BIND_ID, $otherEntityId);
    $mduiHandler->execute();
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $element = $mdui['element'];
      if (! isset($otherMDUIElements[$element]) ) {
        $otherMDUIElements[$element] = array();
      }
      $otherMDUIElements[$element][$mdui['data']] = true;
    }

    $oldElement = 'xxxxxxx';
    $mduiHandler->bindParam(self::BIND_ID, $entityId);
    $mduiHandler->execute();
    $showEndUL = false;
    while ($mdui = $mduiHandler->fetch(PDO::FETCH_ASSOC)) {
      $element = $mdui['element'];
      $data = $mdui['data'];
      if ($oldElement != $element) {
        printf('%s                <b>%s</b>%s                <ul>',
          $showEndUL ? "\n                </ul>\n" : "\n", $element, "\n");
        $showEndUL = true;
        $oldElement = $element;
      }

      if ($otherEntityId) {
        $state = ($added) ? 'success' : 'danger';
        if (isset ($otherMDUIElements[$element]) && isset ($otherMDUIElements[$element][$data])) {
          $state = 'dark';
        }
      } else {
        $state = 'dark';
      }
      printf ('%s                  <li><span class="text-%s">%s</span></li>', "\n", $state, $data);
    }
    if ($showEndUL) {
      print "\n                </ul>";
    }
  }

  ####
  # Shows KeyInfo for IdP or SP
  ####
  private function showKeyInfo($entityId, $type, $otherEntityId=0, $added = false) {
    $keyInfoStatusHandler = $this->metaDb->prepare('SELECT `use`, `notValidAfter`
      FROM KeyInfo WHERE entity_id = :Id AND type = :Type');
    $keyInfoStatusHandler->bindParam(self::BIND_TYPE, $type);
    $keyInfoStatusHandler->bindParam(self::BIND_ID, $entityId);
    $keyInfoStatusHandler->execute();
    $validEncryptionFound = false;
    $validSigningFound = false;
    $timeNow = date('Y-m-d H:i:00');
    $timeWarn = date('Y-m-d H:i:00', time() + 7776000);  // 90 * 24 * 60 * 60 = 90 days / 3 month
    while ($keyInfoStatus = $keyInfoStatusHandler->fetch(PDO::FETCH_ASSOC)) {
      switch ($keyInfoStatus['use']) {
        case 'encryption' :
          if ($keyInfoStatus['notValidAfter'] > $timeNow ) {
            $validEncryptionFound = true;
          }
          break;
        case 'signing' :
          if ($keyInfoStatus['notValidAfter'] > $timeNow ) {
            $validSigningFound = true;
          }
          break;
        case 'both' :
          if ($keyInfoStatus['notValidAfter'] > $timeNow ) {
            $validEncryptionFound = true;
            $validSigningFound = true;
          }
          break;
        default :
      }
    }

    $keyInfoHandler = $this->metaDb->prepare('
      SELECT `use`, `order`, `name`, `notValidAfter`, `subject`, `issuer`, `bits`, `key_type`, `serialNumber`
        FROM KeyInfo WHERE `entity_id` = :Id AND `type` = :Type ORDER BY `order`;');
    $keyInfoHandler->bindParam(self::BIND_TYPE, $type);
    if ($otherEntityId) {
      $otherKeyInfos = array();
      $keyInfoHandler->bindParam(self::BIND_ID, $otherEntityId);
      $keyInfoHandler->execute();

      while ($keyInfo = $keyInfoHandler->fetch(PDO::FETCH_ASSOC)) {
        $otherKeyInfos[$keyInfo['serialNumber']][$keyInfo['use']] = 'removed';
      }
    }

    $keyInfoHandler->bindParam(self::BIND_ID, $entityId);
    $keyInfoHandler->execute();
    while ($keyInfo = $keyInfoHandler->fetch(PDO::FETCH_ASSOC)) {
      $error = '';
      $validCertExists = false;
      switch ($keyInfo['use']) {
        case 'encryption' :
          $use = 'encryption';
          if ($keyInfo['notValidAfter'] <= $timeNow && $validEncryptionFound) {
            $validCertExists = true;
          }
          break;
        case 'signing' :
          $use = 'signing';
          if ($keyInfo['notValidAfter'] <= $timeNow && $validSigningFound) {
            $validCertExists = true;
          }
          break;
        case 'both' :
          $use = 'encryption & signing';
          if ($keyInfo['notValidAfter'] <= $timeNow && $validEncryptionFound &&  $validSigningFound) {
            $validCertExists = true;
          }
          break;
        default :
      }
      $name = $keyInfo['name'] == '' ? '' : '(' . $keyInfo['name'] .')';

      if ($keyInfo['notValidAfter'] <= $timeNow ) {
        $error = ($validCertExists) ? self::HTML_CLASS_ALERT_WARNING : self::HTML_CLASS_ALERT_DANGER;
      } elseif ($keyInfo['notValidAfter'] <= $timeWarn ) {
        $error = self::HTML_CLASS_ALERT_WARNING;
      }

      if (($keyInfo['bits'] < 2048 && $keyInfo['key_type'] == "RSA") || $keyInfo['bits'] < 256) {
        $error = self::HTML_CLASS_ALERT_DANGER;
      }

      if ($otherEntityId) {
        $state = ($added) ? 'success' : 'danger';
        if (isset($otherKeyInfos[$keyInfo['serialNumber']][$keyInfo['use']])) {
          $state = 'dark';
        }
      } else {
        $state = 'dark';
      }
      printf('%s                <span class="text-%s text-truncate"><b>KeyUse = "%s"</b> %s</span>
                <ul%s>
                  <li>notValidAfter = %s</li>
                  <li>Subject = %s</li>
                  <li>Issuer = %s</li>
                  <li>Type / bits = %s / %d</li>
                  <li>Serial Number = %s</li>
                </ul>',
          "\n", $state, $use, $name, $error, $keyInfo['notValidAfter'],
          $keyInfo['subject'], $keyInfo['issuer'], $keyInfo['key_type'], $keyInfo['bits'], $keyInfo['serialNumber']);
    }
  }

  ####
  # Shows AttributeConsumingService for a SP
  ####
  private function showAttributeConsumingService($entityId, $otherEntityId=0, $added = false) {
    $serviceIndexHandler = $this->metaDb->prepare('SELECT `Service_index`
      FROM AttributeConsumingService WHERE `entity_id` = :Id;');
    $serviceElementHandler = $this->metaDb->prepare('SELECT `element`, `lang`, `data`
      FROM AttributeConsumingService_Service
      WHERE `entity_id` = :Id AND `Service_index` = :Index
      ORDER BY `element` DESC, `lang`;');

    $serviceElementHandler->bindParam(self::BIND_INDEX, $serviceIndex);
    $requestedAttributeHandler = $this->metaDb->prepare('SELECT `FriendlyName`, `Name`, `NameFormat`, `isRequired`
      FROM AttributeConsumingService_RequestedAttribute
      WHERE `entity_id` = :Id AND `Service_index` = :Index
      ORDER BY `isRequired` DESC, `FriendlyName`;');
    $requestedAttributeHandler->bindParam(self::BIND_INDEX, $serviceIndex);
    if ($otherEntityId) {
      $serviceIndexHandler->bindParam(self::BIND_ID, $otherEntityId);
      $serviceElementHandler->bindParam(self::BIND_ID, $otherEntityId);
      $requestedAttributeHandler->bindParam(self::BIND_ID, $otherEntityId);
      $serviceIndexHandler->execute();
      while ($index = $serviceIndexHandler->fetch(PDO::FETCH_ASSOC)) {
        $serviceIndex = $index['Service_index'];
        $otherServiceElements[$serviceIndex] = array();
        $otherRequestedAttributes[$serviceIndex] = array();
        $serviceElementHandler->execute();
        while ($serviceElement = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
          $otherServiceElements[$serviceIndex][$serviceElement['element']][$serviceElement['lang']] =
            $serviceElement['data'];
        }
        $requestedAttributeHandler->execute();
        while ($requestedAttribute = $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
          $otherRequestedAttributes[$serviceIndex][$requestedAttribute['Name']] = $requestedAttribute['isRequired'];
        }
      }
    }

    $serviceIndexHandler->bindParam(self::BIND_ID, $entityId);
    $serviceElementHandler->bindParam(self::BIND_ID, $entityId);
    $requestedAttributeHandler->bindParam(self::BIND_ID, $entityId);

    $serviceIndexHandler->execute();
    while ($index = $serviceIndexHandler->fetch(PDO::FETCH_ASSOC)) {
      $serviceIndex = $index['Service_index'];
      $serviceElementHandler->execute();
      printf ('%s                <b>Index = %d</b>%s                <ul>', "\n", $serviceIndex, "\n");
      while ($serviceElement = $serviceElementHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($otherEntityId) {
          $state = ($added) ? 'success' : 'danger';
          $state = (isset(
            $otherServiceElements[$serviceIndex][$serviceElement['element']][$serviceElement['lang']]) &&
            $otherServiceElements[$serviceIndex][$serviceElement['element']][$serviceElement['lang']] ==
              $serviceElement['data'] )
              ? 'dark' : $state;
        } else {
          $state = 'dark';
        }
        printf('%s                  <li><span class="text-%s">%s[%s] = %s</span></li>',
          "\n", $state, $serviceElement['element'], $serviceElement['lang'], $serviceElement['data']);
      }
      $requestedAttributeHandler->execute();
      print "\n                  <li>RequestedAttributes : <ul>";
      while ($requestedAttribute = $requestedAttributeHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($otherEntityId) {
          $state = ($added) ? 'success' : 'danger';
          $state = (isset (
            $otherRequestedAttributes[$serviceIndex][$requestedAttribute['Name']]) &&
            $otherRequestedAttributes[$serviceIndex][$requestedAttribute['Name']] == $requestedAttribute['isRequired'] )
              ? 'dark' : $state;
        } else {
          $state = 'dark';
        }
        $error = '';
        if ($requestedAttribute['FriendlyName'] == '') {
          if (isset($this->FriendlyNames[$requestedAttribute['Name']])) {
            $friendlyNameDisplay = sprintf('(%s)', $this->FriendlyNames[$requestedAttribute['Name']]['desc']);
            if (! $this->FriendlyNames[$requestedAttribute['Name']]['swamidStd']) {
              $error = self::HTML_CLASS_ALERT_WARNING;
            }
          } else {
            $friendlyNameDisplay = '(Unknown)';
            $error = self::HTML_CLASS_ALERT_WARNING;
          }
        } else {
          $friendlyNameDisplay = $requestedAttribute['FriendlyName'];
          if (isset ($this->FriendlyNames[$requestedAttribute['Name']])) {
            if ($requestedAttribute['FriendlyName'] != $this->FriendlyNames[$requestedAttribute['Name']]['desc']
              || ! $this->FriendlyNames[$requestedAttribute['Name']]['swamidStd']) {
                $error = self::HTML_CLASS_ALERT_WARNING;
            }
          } else {
            $error = self::HTML_CLASS_ALERT_WARNING;
          }
        }
        printf('%s                    <li%s><span class="text-%s"><b>%s</b> - %s%s</span></li>',
          "\n", $error, $state, $friendlyNameDisplay, $requestedAttribute['Name'],
          $requestedAttribute['isRequired'] == '1' ? ' (Required)' : '');
      }
      print "\n                  </ul></li>\n                </ul>";
    }
  }

  ####
  # Shows Organization information if exists
  ####
  public function showOrganization($entityId, $oldEntityId=0, $allowEdit = false) {
    if ($allowEdit) {
      $this->showCollapse('Organization', 'Organization', false, 0, true, 'Organization', $entityId, $oldEntityId);
    } else {
      $this->showCollapse('Organization', 'Organization', false, 0, true, false, $entityId, $oldEntityId);
    }
    $this->showOrganizationPart($entityId, $oldEntityId, 1);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(0);
      $this->showOrganizationPart($oldEntityId, $entityId, 0);
    }
    $this->showCollapseEnd('Organization', 0);
  }
  private function showOrganizationPart($entityId, $otherEntityId, $added) {
    $organizationHandler = $this->metaDb->prepare('SELECT `element`, `lang`, `data`
      FROM Organization WHERE `entity_id` = :Id ORDER BY `element`, `lang`;');
    if ($otherEntityId) {
      $organizationHandler->bindParam(self::BIND_ID, $otherEntityId);
      $organizationHandler->execute();
      while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
        if (! isset($otherOrganizationElements[$organization['element']]) ) {
          $otherOrganizationElements[$organization['element']] = array();
        }
        $otherOrganizationElements[$organization['element']][$organization['lang']] = $organization['data'];
      }
    }
    $organizationHandler->bindParam(self::BIND_ID, $entityId);
    $organizationHandler->execute();
    print "\n        <ul>";
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($otherEntityId) {
        $state = ($added) ? 'success' : 'danger';
        $state = (isset ($otherOrganizationElements[$organization['element']][$organization['lang']])
          && $otherOrganizationElements[$organization['element']][$organization['lang']] == $organization['data'] )
           ? 'dark' : $state;
      } else {
        $state = 'dark';
      }
      if ($organization['element'] == 'OrganizationURL' ) {
        printf ('%s          <li><span class="text-%s">%s[%s] = <a href="%s" class="text-%s">%s</a></span></li>',
          "\n", $state, $organization['element'], $organization['lang'],
          $organization['data'], $state, $organization['data']);
      } else {
        printf ('%s          <li><span class="text-%s">%s[%s] = %s</span></li>',
          "\n", $state, $organization['element'], $organization['lang'], $organization['data']);
      }
    }
    print "\n        </ul>";
  }

  ####
  # Shows Contact information if exists
  ####
  public function showContacts($entityId, $oldEntityId=0, $allowEdit = false) {
    if ($allowEdit) {
      $this->showCollapse('ContactPersons', 'ContactPersons',
        false, 0, true, 'ContactPersons', $entityId, $oldEntityId);
    } else {
      $this->showCollapse('ContactPersons', 'ContactPersons',
        false, 0, true, false, $entityId, $oldEntityId);
    }
    $this->showContactsPart($entityId, $oldEntityId, 1);
    if ($oldEntityId != 0 ) {
      $this->showNewCol(0);
      $this->showContactsPart($oldEntityId, $entityId, 0);
    }
    $this->showCollapseEnd('ContactPersons', 0);
  }
  private function showContactsPart($entityId, $otherEntityId, $added) {
    $contactPersonHandler = $this->metaDb->prepare('SELECT *
      FROM ContactPerson WHERE `entity_id` = :Id ORDER BY `contactType`;');
    if ($otherEntityId) {
      $contactPersonHandler->bindParam(self::BIND_ID, $otherEntityId);
      $contactPersonHandler->execute();
      while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
        if (! isset($otherContactPersons[$contactPerson['contactType']])) {
          $otherContactPersons[$contactPerson['contactType']] = array(
            'company' => '',
            'givenName' => '',
            'surName' => '',
            'emailAddress' => '',
            'telephoneNumber' => '',
            'extensions' => ''
          );
        }
        if ($contactPerson['company']) {
            $otherContactPersons[$contactPerson['contactType']]['company'] = $contactPerson['company'];
        }
        if ($contactPerson['givenName']) {
            $otherContactPersons[$contactPerson['contactType']]['givenName'] = $contactPerson['givenName'];
        }
        if ($contactPerson['surName']) {
            $otherContactPersons[$contactPerson['contactType']]['surName'] = $contactPerson['surName'];
        }
        if ($contactPerson['emailAddress']) {
            $otherContactPersons[$contactPerson['contactType']]['emailAddress'] = $contactPerson['emailAddress'];
        }
        if ($contactPerson['telephoneNumber']) {
            $otherContactPersons[$contactPerson['contactType']]['telephoneNumber'] = $contactPerson['telephoneNumber'];
        }
        if ($contactPerson['extensions']) {
            $otherContactPersons[$contactPerson['contactType']]['extensions'] = $contactPerson['extensions'];
        }
      }
    }
    $contactPersonHandler->bindParam(self::BIND_ID, $entityId);
    $contactPersonHandler->execute();
    while ($contactPerson = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($contactPerson['subcontactType'] == '') {
        printf ("\n        <b>%s</b><br>\n", $contactPerson['contactType']);
      } else {
        printf ("\n        <b>%s[%s]</b><br>\n", $contactPerson['contactType'], $contactPerson['subcontactType']);
      }
      print "        <ul>\n";
      if ($contactPerson['company']) {
        if ($otherEntityId) {
          $state = ($added) ? 'success' : 'danger';
          $state = (isset ($otherContactPersons[$contactPerson['contactType']])
            && $otherContactPersons[$contactPerson['contactType']]['company'] == $contactPerson['company'])
              ? 'dark' : $state;
        } else {
          $state = 'dark';
        }
        printf ('          <li><span class="text-%s">Company = %s</span></li>%s',
          $state, $contactPerson['company'], "\n");
      }
      if ($contactPerson['givenName']) {
        if ($otherEntityId) {
          $state = ($added) ? 'success' : 'danger';
          $state = (isset ($otherContactPersons[$contactPerson['contactType']])
            && $otherContactPersons[$contactPerson['contactType']]['givenName'] == $contactPerson['givenName'])
              ? 'dark' : $state;
        } else {
          $state = 'dark';
        }
        printf ('          <li><span class="text-%s">GivenName = %s</span></li>%s',
          $state, $contactPerson['givenName'], "\n");
      }
      if ($contactPerson['surName']) {
        if ($otherEntityId) {
          $state = ($added) ? 'success' : 'danger';
          $state = (isset ($otherContactPersons[$contactPerson['contactType']])
            && $otherContactPersons[$contactPerson['contactType']]['surName'] == $contactPerson['surName'])
              ? 'dark' : $state;
        } else {
          $state = 'dark';
        }
        printf ('          <li><span class="text-%s">SurName = %s</span></li>%s',
          $state, $contactPerson['surName'], "\n");
      }
      if ($contactPerson['emailAddress']) {
        if ($otherEntityId) {
          $state = ($added) ? 'success' : 'danger';
          $state = (isset ($otherContactPersons[$contactPerson['contactType']])
            && $otherContactPersons[$contactPerson['contactType']]['emailAddress'] == $contactPerson['emailAddress'])
              ? 'dark' : $state;
        } else {
          $state = 'dark';
        }
        printf ('          <li><span class="text-%s">EmailAddress = %s</span></li>%s',
          $state, $contactPerson['emailAddress'], "\n");
      }
      if ($contactPerson['telephoneNumber']) {
        if ($otherEntityId) {
          $state = ($added) ? 'success' : 'danger';
          $state = (isset ($otherContactPersons[$contactPerson['contactType']])
            && $otherContactPersons[$contactPerson['contactType']]['telephoneNumber'] ==
              $contactPerson['telephoneNumber'])
              ? 'dark' : $state;
        } else {
          $state = 'dark';
        }
        printf ('          <li><span class="text-%s">TelephoneNumber = %s</span></li>%s',
          $state, $contactPerson['telephoneNumber'], "\n");
      }
      if ($contactPerson['extensions']) {
        if ($otherEntityId) {
          $state = ($added) ? 'success' : 'danger';
          $state = (isset ($otherContactPersons[$contactPerson['contactType']])
            && $otherContactPersons[$contactPerson['contactType']]['extensions'] == $contactPerson['extensions'])
              ? 'dark' : $state;
        } else {
          $state = 'dark';
        }
        printf ('          <li><span class="text-%s">Extensions = %s</span></li>%s',
          $state, $contactPerson['extensions'], "\n");
      }
      print "        </ul>";
    }
  }

  ####
  # Shows XML for entiry
  ####
  public function showMdqUrl($entityID, $Mode) {
    $this->showCollapse('Signed XML in SWAMID', 'MDQ', false, 0, true, false, 0, 0);
    $url = sprintf('https://mds.swamid.se/%sentities/%s', $Mode == 'QA' ? 'qa/' : '', urlencode($entityID));
    printf ('        URL at MDQ : <a href="%s">%s</a><br><br>%s',
      $url, $url, "\n");
    $this->showCollapseEnd('MDQ', 0);
  }

  public function showXML($entityId) {
    printf ('
    <h4>
      <i class="fas fa-chevron-circle-right"></i>
      <a href=".?rawXML=%d" target="_blank">Show XML</a>
    </h4>
    <h4>
      <i class="fas fa-chevron-circle-right"></i>
      <a href=".?rawXML=%d&download" target="_blank">Download XML</a>
    </h4>%s',
      $entityId, $entityId, "\n");
  }

  public function showRawXML($entityId, $urn = false) {
    $entityHandler = $urn
      ? $this->metaDb->prepare('SELECT `xml` FROM Entities WHERE `entityID` = :Id AND `status` = 1;')
      : $this->metaDb->prepare('SELECT `xml` FROM Entities WHERE `id` = :Id;');
    $entityHandler->bindParam(self::BIND_ID, $entityId);
    $entityHandler->execute();
    if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
      header('Content-Type: application/xml; charset=utf-8');
      if (isset($_GET['download'])) {
        header('Content-Disposition: attachment; filename=metadata.xml');
      }
      print $entity['xml'];
    } else {
      print "Not Found";
    }
    exit;
  }

  public function showDiff($entityId, $otherEntityId) {
    $this->showCollapse('XML Diff', 'XMLDiff', false, 0, false);
    $this->showXMLDiff($entityId, $otherEntityId);
    $this->showCollapseEnd('XMLDiff');
  }

  public function showEditors($entityId){
    $this->showCollapse('Editors', 'Editors', false, 0, true, false, $entityId, 0);
    $usersHandler = $this->metaDb->prepare('SELECT `userID`, `email`, `fullName`
      FROM EntityUser, Users WHERE `entity_id` = :Id AND id = user_id ORDER BY `userID`;');
    $usersHandler->bindParam(self::BIND_ID, $entityId);
    $usersHandler->execute();
    print "        <ul>\n";
    while ($user = $usersHandler->fetch(PDO::FETCH_ASSOC)) {
      printf ('          <li>%s (Identifier : %s, Email : %s)</li>%s',
        $user['fullName'], $user['userID'], $user['email'], "\n");
    }
    print "        </ul>";
    $this->showCollapseEnd('Editors', 0);
  }

  public function showURLStatus($url = false){
    if($url) {
      $urlHandler = $this->metaDb->prepare('SELECT `type`, `validationOutput`, `lastValidated`, `height`, `width`
        FROM URLs WHERE `URL` = :URL');
      $urlHandler->bindValue(self::BIND_URL, $url);
      $urlHandler->execute();
      $entityHandler = $this->metaDb->prepare('SELECT `entity_id`, `entityID`, `status`
        FROM EntityURLs, Entities WHERE entity_id = id AND `URL` = :URL');
      $entityHandler->bindValue(self::BIND_URL, $url);
      $entityHandler->execute();
      $ssoUIIHandler = $this->metaDb->prepare('SELECT `entity_id`, `type`, `element`, `lang`, `entityID`, `status`
        FROM `Mdui`, `Entities` WHERE `entity_id` = `Entities`.`id` AND `data` = :URL');
      $ssoUIIHandler->bindValue(self::BIND_URL, $url);
      $ssoUIIHandler->execute();
      $organizationHandler = $this->metaDb->prepare('SELECT `entity_id`, `element`, `lang`, `entityID`, `status`
        FROM Organization, Entities WHERE entity_id = id AND `data` = :URL');
      $organizationHandler->bindValue(self::BIND_URL, $url);
      $organizationHandler->execute();
      $entityAttributesHandler = $this->metaDb->prepare("SELECT `attribute`
        FROM EntityAttributes WHERE `entity_id` = :Id AND type = 'entity-category'");

      printf ('    <table class="table table-striped table-bordered">%s', "\n");
      printf ('      <tr><th>URL</th><td>%s</td></tr>%s', htmlspecialchars($url), "\n");
      if ($urlInfo = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
        printf ('      <tr>
          <th>Checked</th>
          <td>
            %s (UTC) <a href=".?action=%s&URL=%s&recheck">
              <button type="button" class="btn btn-primary">Recheck now</button>
            </a>
            <a href=".?action=%s&URL=%s&recheck&verbose">
              <button type="button" class="btn btn-primary">Recheck now (verbose)</button>
            </a>
          </td>
        </tr>
        <tr><th>Status</th><td>%s</td></tr>%s',
          $urlInfo['lastValidated'], htmlspecialchars($_GET['action']) ,
          urlencode($url), htmlspecialchars($_GET['action']) ,
          urlencode($url), $urlInfo['validationOutput'] , "\n");
        if ($urlInfo['height'] > 0 && $urlInfo['width'] > 0 ) {
          printf ('      <tr><th>Height</th><td>%s</td></tr>
        <tr><th>Width</th><td>%s</td></tr>%s', $urlInfo['height'], $urlInfo['width'], "\n");
        }
        switch ($urlInfo['validationOutput']) {
          case 'SSL certificate problem: unable to get local issuer certificate' :
            printf ('      <tr><th>Possible solution</th><td>You are missing intermediate certificate(s).<br>
              Verify at <a href="https://www.ssllabs.com/ssltest/analyze.html?d=%s">SSL Labs</a></td></tr>%s',
              urlencode($url), "\n");
            break;
          case 'Policy missing link to http://www.geant.net/uri/dataprotection-code-of-conduct/v1' : # NOSONAR Should be http://
            printf ('      <tr><th>Possible solution</th>
              <td>You are missing link / have a java-script to generate this page.<br>
              Verify with curl -s %s | grep http://www.geant.net/uri/dataprotection-code-of-conduct/v1<br>
              This should output this URL.</td></tr>%s',
              htmlspecialchars($url), "\n");
            break;
          default :
              break;
        }
      }
      printf ('%s    <table class="table table-striped table-bordered">
      <tr><th>Entity</th><th>Part</th><th></tr>%s', self::HTML_TABLE_END, "\n");
      while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
        printf ('      <tr><td><a href="?showEntity=%d">%s</td><td>%s</td><tr>%s',
          $entity['entity_id'], $entity['entityID'], 'ErrorURL', "\n");
      }
      while ($entity = $ssoUIIHandler->fetch(PDO::FETCH_ASSOC)) {
        $ecInfo = '';
        if ($entity['type'] == 'SPSSO' && $entity['element'] == 'PrivacyStatementURL') {
          $entityAttributesHandler->bindParam(self::BIND_ID, $entity['entity_id']);
          $entityAttributesHandler->execute();
          while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
            if ($attribute['attribute'] == self::SAML_EC_COCOV1) {
              $ecInfo = ' CoCo';
            }
          }
        }
        switch ($entity['element']) {
          case 'Logo' :
          case 'InformationURL' :
          case 'PrivacyStatementURL' :
            printf ('      <tr><td><a href="?showEntity=%d">%s</a> (%s)</td><td>%s:%s[%s]%s</td><tr>%s',
              $entity['entity_id'], $entity['entityID'], $this->getEntityStatusType($entity['status']),
              substr($entity['type'],0,-3), $entity['element'], $entity['lang'], $ecInfo, "\n");
            break;
          default :
        }
      }
      while ($entity = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($entity['element'] == 'OrganizationURL') {
          printf ('      <tr><td><a href="?showEntity=%d">%s</a> (%s)</td><td>%s[%s]</td><tr>%s',
            $entity['entity_id'], $entity['entityID'], $this->getEntityStatusType($entity['status']),
            $entity['element'], $entity['lang'], "\n");
        }
      }
      print self::HTML_TABLE_END;
    } else {
      $oldType = 0;
      $urlHandler = $this->metaDb->prepare(
        'SELECT `URL`, `type`, `status`, `cocov1Status`, `lastValidated`, `lastSeen`, `validationOutput`
        FROM URLs WHERE `status` > 0 OR `cocov1Status` > 0 ORDER BY type DESC, `URL`;');
      $urlHandler->execute();

      while ($url = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($oldType != $url['type']) {
          switch ($url['type']) {
            case 1:
              $typeInfo = 'URL check';
              break;
            case 2:
              $typeInfo = 'URL check - Needs to be reachable';
              break;
            case 3:
              $typeInfo = 'CoCo - PrivacyURL';
              break;
            default :
              $typeInfo = '?' . $url['type'];
          }
          if ($oldType > 0) {
            print self::HTML_TABLE_END;
          }
          printf ('    <h3>%s</h3>%s    <table class="table table-striped table-bordered">%s      <tr>
        <th>URL</th><th>Last seen</th><th>Last validated</th><th>Result</th></tr>%s', $typeInfo, "\n", "\n", "\n");
          $oldType = $url['type'];
        }
        printf ('      <tr><td><a href="?action=URLlist&URL=%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><tr>%s',
          urlencode($url['URL']), $url['URL'], $url['lastSeen'], $url['lastValidated'], $url['validationOutput'], "\n");
      }
      if ($oldType > 0) { print self::HTML_TABLE_END; }

      $warnTime = date('Y-m-d H:i', time() - 25200 ); // (7 * 60 * 60 =  7 hours)
      $warnTimeweek = date('Y-m-d H:i', time() - 608400 ); // (7 * 24 * 60 * 60 + 3600 =  7 days 1 hour)
      $urlWaitHandler = $this->metaDb->prepare(
        "SELECT `URL`, `validationOutput`, `lastValidated`, `lastSeen`, `status`
        FROM URLs
        WHERE `lastValidated` < ADDTIME(NOW(), '-7 0:0:0')
          OR (`status` > 0 AND `lastValidated` < ADDTIME(NOW(), '-6:0:0'))
        ORDER BY `lastValidated`;");
      $urlWaitHandler->execute();
      printf ('    <h3>Waiting for validation</h3>%s    <table class="table table-striped table-bordered">
      <tr>
        <th>URL</th>
        <th>Last seen</th>
        <th>Last validated</th>
        <th>Result</th>
      </tr>%s', "\n", "\n");
      while ($url = $urlWaitHandler->fetch(PDO::FETCH_ASSOC)) {
        $warn = (($url['lastValidated'] < $warnTime && $url['status'] > 0) || $url['lastValidated'] < $warnTimeweek)
          ? '! ' : '';
        printf ('      <tr>
        <td><a href="?action=URLlist&URL=%s">%s%s</td><td>%s</td><td>%s</td><td>%s</td><tr>%s',
          urlencode($url['URL']), $warn, $url['URL'], $url['lastSeen'],
          $url['lastValidated'], $url['validationOutput'], "\n");
      }
      print self::HTML_TABLE_END;

    }
  }

  private function getEntityStatusType($status) {
    switch ($status) {
      case 1 :
        $returnStatus = 'Published';
        break;
      case 2 :
        $returnStatus = 'Pending';
        break;
      case 3 :
        $returnStatus = 'Draft';
        break;
      case 4 :
        $returnStatus = 'Deleted';
        break;
      case 5 :
        $returnStatus = 'POST Pending';
        break;
      case 6 :
        $returnStatus = 'Shadow Pending';
        break;
      default :
        $returnStatus = $status . ' unknown status';
    }
    return $returnStatus;
  }

  public function showErrorList($download = false) {
    if (! $download) {
      # Default values
      $errorsActive='';
      $errorsSelected='false';
      $errorsShow='';
      #
      $remindersActive='';
      $remindersSelected='false';
      $remindersShow='';
      #
      $remindersUrgentActive='';
      $remindersUrgentSelected='false';
      $remindersUrgentShow='';
      #
      $idPsActive = '';
      $idPsSelected = 'false';
      $idPsShow = '';
      $idPsId = 0;

      if (isset($_GET["tab"])) {
        switch ($_GET["tab"]) {
          case 'reminders' :
            $remindersActive = self::HTML_ACTIVE;
            $remindersSelected ='true';
            $remindersShow = self::HTML_SHOW;
            break;
          case 'reminders-urgent' :
            $remindersUrgentActive = self::HTML_ACTIVE;
            $remindersUrgentSelected='true';
            $remindersUrgentShow = self::HTML_SHOW;
            break;
          case 'IdPs' :
            $idPsActive = self::HTML_ACTIVE;
            $idPsSelected = 'true';
            $idPsShow = self::HTML_SHOW;
            $idPsId = isset($_GET['id']) ? $_GET['id'] : 0;
            break;
          default :
            $errorsActive = self::HTML_ACTIVE;
            $errorsSelected='true';
            $errorsShow = self::HTML_SHOW;
          }
      } else {
        $errorsActive = self::HTML_ACTIVE;
        $errorsSelected='true';
        $errorsShow = self::HTML_SHOW;
      }

      printf('    <div class="row">
      <div class="col">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
          <li class="nav-item">
            <a class="nav-link%s" id="errors-tab" data-toggle="tab" href="#errors" role="tab"
              aria-controls="errors" aria-selected="%s">Errors</a>
          </li>
          <li class="nav-item">
            <a class="nav-link%s" id="reminders-tab" data-toggle="tab" href="#reminders" role="tab"
              aria-controls="reminders" aria-selected="%s">Reminders</a>
          </li>
          <li class="nav-item">
            <a class="nav-link%s" id="reminders-urgent-tab" data-toggle="tab" href="#reminders-urgent" role="tab"
              aria-controls="reminders-urgent" aria-selected="%s">Reminders - Act on</a>
          </li>
          <li class="nav-item">
            <a class="nav-link%s" id="idps-tab" data-toggle="tab" href="#idps" role="tab"
              aria-controls="idps" aria-selected="%s">IdP:s missing IMPS</a>
          </li>
        </ul>
      </div>%s    </div>%s    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade%s%s" id="errors" role="tabpanel" aria-labelledby="errors-tab">%s',
          $errorsActive, $errorsSelected, $remindersActive, $remindersSelected, $remindersUrgentActive, $remindersUrgentSelected, $idPsActive, $idPsSelected, "\n", "\n",
          $errorsShow, $errorsActive, "\n");
    }
    $this->showErrorEntitiesList($download);
    if (! $download) {
      printf('      </div><!-- End tab-pane errors -->
      <div class="tab-pane fade%s%s" id="reminders" role="tabpanel" aria-labelledby="reminders-tab">%s',
        $remindersShow, $remindersActive, "\n");
      $this->showErrorMailReminders();
      printf('      </div><!-- End tab-pane reminders -->
      <div class="tab-pane fade%s%s" id="reminders-urgent" role="tabpanel" aria-labelledby="reminders-urgent-tab">%s',
        $remindersUrgentShow, $remindersUrgentActive, "\n");
      $this->showErrorMailReminders(false);
      printf('      </div><!-- End tab-pane reminders-urgent -->
      <div class="tab-pane fade%s%s" id="idps" role="tabpanel" aria-labelledby="idps-tab">%s',
        $idPsShow, $idPsActive, "\n");
      $this->showIdPsMissingIMPS($idPsId);
      printf('%s      </div><!-- End tab-pane idps -->%s    </div><!-- End tab-content -->%s',
        "\n", "\n", "\n");
    }
  }
  private function showErrorEntitiesList($download) {
    $emails = array();
    $entityHandler = $this->metaDb->prepare(
      "SELECT `id`, `publishIn`, `isIdP`, `isSP`, `entityID`, `errors`, `errorsNB`
      FROM Entities WHERE (`errors` <> '' OR `errorsNB` <> '') AND `status` = 1 ORDER BY entityID");
    $entityHandler->execute();
    $contactPersonHandler = $this->metaDb->prepare(
      'SELECT contactType, emailAddress FROM ContactPerson WHERE `entity_id` = :Id;');

    if ($download) {
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename=errorlog.csv');
      print "Type,Feed,Entity,Contact address\n";
    } else {
      printf('        <br>
        <h5>Entities with errors</h5>
        <a href=".?action=ErrorListDownload">
          <button type="button" class="btn btn-primary">Download CSV</button>
        </a>
        <br>
        <table id="error-table" class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>Type</th>
              <th>Feed</th>
              <th>Entity</th>
              <th>Contact address</th>
              <th>Error</th>
            </tr>
          </thead>%s',
        "\n");
    }
    while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
      $contactPersonHandler->bindValue(self::BIND_ID, $entity['id']);
      $contactPersonHandler->execute();
      $emails['administrative'] = '';
      $emails['support'] = '';
      $emails['technical'] = '';
      while ($contact = $contactPersonHandler->fetch(PDO::FETCH_ASSOC)) {
        $emails[$contact['contactType']] = substr($contact['emailAddress'],7);
      }
      if ($emails['technical'] != '' ) {
        $email = $emails['technical'];
      } elseif($emails['administrative'] != '') {
        $email = $emails['administrative'];
      } elseif ($emails['support'] != '' ) {
        $email = $emails['support'];
      } else {
        $email = 'Missing';
      }
      if ($entity['isIdP']) {
        $type = ($entity['isSP']) ? 'IdP & SP' : 'IdP';
      } else {
        $type = 'SP';
      }
      switch ($entity['publishIn']) {
        case 1 :
          $feed = 'T';
          break;
        case 2 :
        case 3 :
          $feed = 'S';
          break;
        case 6 :
        case 7 :
          $feed = 'E';
          break;
        default :
          $feed = '?';
      }
      if ($download) {
        printf ('%s,%s,%s,%s%s', $type, $feed, $entity['entityID'], $email, "\n");
      } else {
        printf ('          <tr>
            <td>%s</td>
            <td>%s</td>
            <td>
              <a href="?showEntity=%d"><span class="text-truncate">%s</span>
            </td>
            <td>%s</td>
            <td>%s</td>%s          </tr>%s',
          $type, $feed, $entity['id'], $entity['entityID'], $email,
          str_ireplace("\n", "<br>",$entity['errors'].$entity['errorsNB']), "\n", "\n");
      }
    }
    if (!$download) {print "    " . self::HTML_TABLE_END; }
  }
  private function showErrorMailReminders($showAll=true) {
    $entityHandler = $this->metaDb->prepare(
      'SELECT MailReminders.*, `entityID`, `lastConfirmed`, `lastValidated`
      FROM `MailReminders`, `Entities`
      LEFT JOIN `EntityConfirmation` ON `EntityConfirmation`.`entity_id` = `Entities`.`id`
      WHERE `Entities`.`id` = `MailReminders`.`entity_id`
      ORDER BY `entityID`, `type`');
    $entityHandler->execute();
    printf ('        <br>
        <h5>Entities that we sent mail reminders to</h5>
        <p>Updated every wednesday at 7:15 UTC</p>
        <table id="reminder-table" class="table table-striped table-bordered">
          <thead><tr><th>EntityID</th><th>Reason</th><th>Mail sent</th><th>Last Confirmed/Validated</th></tr></thead>%s',
      "\n");
    while ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
      $showUrgent = false;
      switch ($entity['type']) {
        case 1 :
          // Confirmation/Validation reminder
          switch ($entity['level']) {
            case 1 :
              $reason = 'Not validated/confirmed for 10 months';
              break;
            case 2 :
              $reason = 'Not validated/confirmed for 11 months';
              break;
            case 3:
              $reason = 'Not validated/confirmed for 12 months';
              $showUrgent = true;
              break;
            default :
              $showUrgent = true;
              $reason = 'Not validated/confirmed';
          }
          $date = $entity['lastConfirmed'];
          break;
        case 2:
          // Cert expire
          switch ($entity['level']) {
            case 1 :
              $reason = 'Certificate will expire within a month';
              break;
            case 2 :
              $showUrgent = true;
              $reason = 'Certificate have expired';
              break;
            default :
              $showUrgent = true;
              $reason = 'Certificate error';
          }
          $date = $entity['lastConfirmed'];
          break;
        case 3 :
          // Peending queue
          switch ($entity['level']) {
            case 1 :
              $reason = '1 week in pending queue';
              break;
            case 2 :
              $reason = '4 weeks in pending queue';
              break;
            case 3:
              $reason = '11 weeks in pending queue';
              break;
            default :
              $reason = 'To long in pending queue';
          }
          $date = $entity['lastValidated'];
          break;
        case 4 :
          // Drafts queue
          switch ($entity['level']) {
            case 1 :
              $reason = '2 weeks in drafts queue';
              break;
            case 2 :
              $reason = '7 weeks in drafts queue';
              break;
            default :
              $reason = 'To long in drafts queue';
          }
          $date = $entity['lastValidated'];
          break;
        default :
      }
      if ($showAll || $showUrgent) {
        printf(
          '          <tr>
            <td><a href=./?showEntity=%d target="_blank">%s</a></td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
          </tr>%s',
          $entity['entity_id'], $entity['entityID'], $reason, $entity['mailDate'], $date,"\n");
      }
    }
    printf ('    %s', self::HTML_TABLE_END);
  }
  private function showIdPsMissingIMPS() {
    $idpHandler = $this->metaDb->prepare(
      'SELECT `id`, `entityID`, `publishIn`
      FROM `Entities`
      WHERE `status` = 1 AND `isIdP` = 1 AND id NOT IN (SELECT `entity_id` FROM `IdpIMPS`)
      ORDER BY `publishIn` DESC, `entityID`');
    $impsHandler = $this->metaDb->prepare(
      'SELECT `IMPS`.`id`,`name`, `maximumAL`
        FROM `IMPS`
        WHERE `id` NOT IN (SELECT `IMPS_id` FROM `IdpIMPS`)
        ORDER BY `name`');
    $idpHandler->execute();
    printf('        <div class="row">
          <div class="col">
            <h4>IdP:s missing an IMPS</h4>
            <ul>%s' ,"\n");
    while ($idp = $idpHandler->fetch(PDO::FETCH_ASSOC)) {
      $testing = $idp['publishIn'] == 1 ? ' (Testing)' : '';
      printf('              <li><a href="?showEntity=%s" target="_blank">%s</a>%s</li>%s', $idp['id'], $idp['entityID'], $testing, "\n");
    }
    $impsHandler->execute();
    printf('            </ul>
            <h4>IMPS:s missing an IdP</h4>
            <ul>%s' ,"\n");
    while ($imps = $impsHandler->fetch(PDO::FETCH_ASSOC)) {
      printf('              <li><a href="?action=Members&tab=imps&id=%d">%s</a></li>%s', $imps['id'], $imps['name'], "\n");

    }
    printf('            </ul>
          </div><!-- end col -->
        </div><!-- end row -->');
  }

  public function showXMLDiff($entityId1, $entityId2) {
    $entityHandler = $this->metaDb->prepare('SELECT `id`, `entityID`, `xml` FROM Entities WHERE `id` = :Id');
    $entityHandler->bindValue(self::BIND_ID, $entityId1);
    $entityHandler->execute();
    if ($entity1 = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
      $entityHandler->bindValue(self::BIND_ID, $entityId2);
      $entityHandler->execute();
      if ($entity2 = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
        if (! class_exists('NormalizeXML')) {
          require_once __DIR__ . '/NormalizeXML.php';
        }
        $normalize1 = new NormalizeXML();
        $normalize1->fromString($entity1['xml']);
        $normalize2 = new NormalizeXML();
        $normalize2->fromString($entity2['xml']);
        if ($normalize1->getStatus() && $normalize2->getStatus()) {
          printf ('<h4>Diff of %s</h4>', $entity1['entityID']);
          require_once __DIR__ . '/Diff.php';
          require_once __DIR__ . '/Diff/Renderer/Text/Unified.php';
          $options = array(
            //'ignoreWhitespace' => true,
            //'ignoreCase' => true,
          );
          $diff = new Diff(explode("\n", $normalize2->getXML()), explode("\n", $normalize1->getXML()), $options);
          $renderer = new Diff_Renderer_Text_Unified;
          printf('<pre>%s</pre>', htmlspecialchars($diff->render($renderer)));
        } else {
          print $normalize1->getError();
        }
      }
    }
  }

  public function showPendingList() {
    $entitiesHandler = $this->metaDb->prepare(
      'SELECT Entities.`id`, `entityID`, `xml`, `lastUpdated`, `email`, `lastChanged`
      FROM Entities, EntityUser, Users
      WHERE `status` = 2 AND Entities.`id` = `entity_id` AND `user_id` = Users.`id`
      ORDER BY lastUpdated ASC, `entityID`, `lastChanged` DESC');
    $entityHandler = $this->metaDb->prepare(
      'SELECT `id`, `xml`, `lastUpdated` FROM Entities WHERE `status` = 1 AND `entityID` = :EntityID');
    $entityHandler->bindParam(self::BIND_ENTITYID, $entityID);
    $entitiesHandler->execute();

    if (! class_exists('NormalizeXML')) {
      require_once __DIR__ . '/NormalizeXML.php';
    }
    $normalize = new NormalizeXML();

    printf ('    <table class="table table-striped table-bordered">
      <tr><th>Entity</th><th>Updater</th><th>Time</th><th>TimeOK</th><th>XML</th></tr>%s', "\n", );
    $lastId = 0;
    while ($pendingEntity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($lastId == $pendingEntity['id']) {
        # Only show one line for each entity
        continue;
      }
      $lastId = $pendingEntity['id'];
      $entityID = $pendingEntity['entityID'];

      $normalize->fromString($pendingEntity['xml']);
      if ($normalize->getStatus()) {
        if ($normalize->getEntityID() == $entityID) {
          $pendingXML = $normalize->getXML();
          $entityHandler->execute();
          if ($publishedEntity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
            $okRemove = sprintf('%s <a href=".?action=ShowDiff&entity_id1=%d&entity_id2=%d">Diff</a>',
              $entityID, $pendingEntity['id'], $publishedEntity['id']);
            printf('      <tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>%s',
              $okRemove, $pendingEntity['email'], $pendingEntity['lastUpdated'],
              ($pendingEntity['lastUpdated'] < $publishedEntity['lastUpdated']) ? 'X' : '',
              ($pendingXML == $publishedEntity['xml']) ? 'X' : '', "\n" );
          } else {
            printf('      <tr><td>%s</td><td>%s</td><td>%s</td><td colspan="2">Not published</td></tr>%s',
              $entityID, $pendingEntity['email'], $pendingEntity['lastUpdated'], "\n" );
          }
        } else {
          printf('      <tr><td>%s</td><td colspan="4">%s</td></tr>%s',  $entityID, 'Diff in entityID', "\n");
        }
      } else {
        printf('      <tr><td>%s</td><td>%s</td><td>%s</td><td colspan="2">%s</td></tr>%s',
          $entityID, 'Problem with XML', "\n");
      }
    }
    print self::HTML_TABLE_END;
  }

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

    $idpHandler = $this->metaDb->prepare(
      "SELECT COUNT(`id`) AS `count` FROM Entities WHERE `isIdP` = 1 AND `status` = 1 AND `publishIn` > 1");
    $idpHandler->execute();
    if ($idps = $idpHandler->fetch(PDO::FETCH_ASSOC)) {
      $nrOfIdPs = $idps['count'];
    } else {
      $nrOfIdPs = 0;
    }
    $entityAttributesHandler = $this->metaDb->prepare(
      "SELECT COUNT(`attribute`) AS `count`, `attribute`
      FROM EntityAttributes, Entities
      WHERE type = 'entity-category-support' AND `entity_id` = `Entities`.`id` AND `isIdP` = 1 AND `status` = 1 AND `publishIn` > 1
      GROUP BY `attribute`");
    $entityAttributesHandler->execute();
    while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
      $ecsTested[$ecsTagged[$attribute['attribute']]]['MarkedWithECS'] = $attribute['count'];
    }

    $testResultsHandeler = $this->metaDb->prepare(
      "SELECT COUNT(entityID) AS `count`, `test`, `result`
      FROM TestResults
      WHERE TestResults.`entityID` IN (SELECT `entityID`
      FROM Entities WHERE `isIdP` = 1 AND `publishIn` > 1)
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
        printf ('    <div class="row">%s      <div class="col">%s', "\n", "\n");
      } else {
        printf ('      <div class="col">%s', "\n");
      }
      printf ('        <h3>%s</h3>%s        <canvas id="%s"></canvas>%s', $descr, "\n", str_replace('-','', $ec), "\n");
      if ($count == 4) {
        printf ('      </div>%s    </div>%s', "\n", "\n");
        $count = 1;
      } else {
        printf ('      </div>%s', "\n");
        $count ++;
      }
    }
    if ($count > 1) {
      while ($count < 5) {
        printf ('      <div class="col"></div>%s', "\n");
        $count ++;
      }
      printf ('    </div>%s', "\n");
    }
    printf ('    <br><br>
    <h3>Statistics in numbers</h3>
    <p>
      Based on release-check test performed over the last 12 months and Entity-Category-Support registered in metadata.
      <br>
      Out of %d IdPs in swamid:
    </p>
    <table class="table table-striped table-bordered">
      <tr><th>EC</th><th>OK + ECS</th><th>OK no ECS</th><th>Fail</th><th>Not tested</th></tr>%s',
      $nrOfIdPs, "\n");
    foreach ($ecs as $ec => $descr) {
      $markedECS = $ecsTested[$ec]['MarkedWithECS'];
      $ok = $ecsTested[$ec]['OK'] > $ecsTested[$ec]['MarkedWithECS']
        ? $ecsTested[$ec]['OK'] - $ecsTested[$ec]['MarkedWithECS']
        : 0;
      $fail = $ecsTested[$ec]['Fail'] > $nrOfIdPs ? 0 : $ecsTested[$ec]['Fail'];
      $notTested = $nrOfIdPs - $markedECS - $ok - $fail;
      printf('      <tr><td>%s</td><td>%d (%d %%)</td><td>%d (%d %%)</td><td>%d (%d %%)</td><td>%d (%d %%)</td></tr>%s',
        $descr, $markedECS, ($markedECS/$nrOfIdPs*100), $ok, ($ok/$nrOfIdPs*100),
        $fail, ($fail/$nrOfIdPs*100), $notTested, ($notTested/$nrOfIdPs*100), "\n");
    }
    printf('%s    <script src="/include/chart/chart.min.js"></script>%s    <script>%s', self::HTML_TABLE_END, "\n", "\n");
    foreach ($ecs as $ec => $descr) {
      $markedECS = $ecsTested[$ec]['MarkedWithECS'];
      $ok = $ecsTested[$ec]['OK'] > $ecsTested[$ec]['MarkedWithECS']
        ? $ecsTested[$ec]['OK'] - $ecsTested[$ec]['MarkedWithECS'] : 0;
      $fail = $ecsTested[$ec]['Fail'] > $nrOfIdPs ? 0 : $ecsTested[$ec]['Fail'];
      $notTested = $nrOfIdPs - $markedECS - $ok - $fail;
      $ecdiv = str_replace('-','', $ec);
      printf ("      const ctx%s = document.getElementById('%s').getContext('2d');%s", $ecdiv, $ecdiv, "\n");
      printf ("      const my%s = new Chart(ctx%s, {
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
   print "    </script>\n";
  }

  private function printAssuranceRow($idp, $assurance) {
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

  public function showRAFStatistics() {
    $idpCountHandler = $this->metaDb->prepare(
      'SELECT COUNT(DISTINCT `entityID`) as `idps` FROM `assuranceLog`');
    $idpCountHandler->execute();
    if ($idpCountRow = $idpCountHandler->fetch(PDO::FETCH_ASSOC)) {
      $idps = $idpCountRow['idps'];
    } else {
      $idps = 0;
    }

    $idpAssuranceHandler = $this->metaDb->prepare(
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

    $metaAssuranceHandler = $this->metaDb->prepare(
      "SELECT COUNT(`Entities`.`id`) AS `count`, `attribute`
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
        <h3>SWAMID Assurance</h3>
        <canvas id="swamid"></canvas>
      </div>
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

    $assuranceHandler = $this->metaDb->prepare(
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
    print self::HTML_TABLE_END . "    <br>\n";

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
    $metaAssuranceCount['http://www.swamid.se/policy/assurance/al3'], # NOSONAR Should be http://
    $metaAssuranceCount['http://www.swamid.se/policy/assurance/al2'] - # NOSONAR Should be http://
      $metaAssuranceCount['http://www.swamid.se/policy/assurance/al3'], # NOSONAR Should be http://
    $metaAssuranceCount['http://www.swamid.se/policy/assurance/al1'] - # NOSONAR Should be http://
      $metaAssuranceCount['http://www.swamid.se/policy/assurance/al2']); # NOSONAR Should be http://
  }

  public function showEntityStatistics() {
    $labelsArray = array();
    $spArray = array();
    $idpArray = array();

    $nrOfEntites = 0;
    $nrOfSPs = 0;
    $nrOfIdPs = 0;

    $entitys = $this->metaDb->prepare(
      "SELECT `id`, `entityID`, `isIdP`, `isSP`, `publishIn` FROM Entities WHERE status = 1 AND publishIn > 2");
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

    printf ('    <h3>Entity Statistics</h3>
    <p>Statistics on number of entities in SWAMID.</p>
    <canvas id="total" width="200" height="50"></canvas>
    <br><br>
    <h3>Statistics in numbers</h3>
    <table class="table table-striped table-bordered">
      <tr><th>Date</th><th>NrOfEntites</th><th>NrOfSPs</th><th>NrOfIdPs</th></tr>%s', "\n");
    printf('      <tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td></tr>%s',
      'Now', $nrOfEntites, $nrOfSPs, $nrOfIdPs, "\n");
    array_unshift($labelsArray, 'Now');
    array_unshift($spArray, $nrOfSPs);
    array_unshift($idpArray, $nrOfIdPs);

    $statusRows = $this->metaDb->prepare(
      "SELECT `date`, `NrOfEntites`, `NrOfSPs`, `NrOfIdPs` FROM EntitiesStatistics ORDER BY `date` DESC");
    $statusRows->execute();
    while ($row = $statusRows->fetch(PDO::FETCH_ASSOC)) {
      $dateLabel = substr($row['date'],2,8);
      printf('      <tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td></tr>%s',
        substr($row['date'],0,10), $row['NrOfEntites'], $row['NrOfSPs'], $row['NrOfIdPs'], "\n");
      array_unshift($labelsArray, $dateLabel);
      array_unshift($spArray, $row['NrOfSPs']);
      array_unshift($idpArray, $row['NrOfIdPs']);
    }
    $labels = implode("','", $labelsArray);
    $idps = implode(',', $idpArray);
    $sps = implode(',', $spArray);

    printf ('%s    <script src="/include/chart/chart.min.js"></script>%s    <script>%s', self::HTML_TABLE_END, "\n", "\n");
    printf ("      const ctxTotal = document.getElementById('total').getContext('2d');
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
      });%s    </script>%s",
      $labels, $idps, $sps, "\n", "\n");
  }

  public function showOrganizationLists() {
    $organizationHandler = $this->metaDb->prepare(
      "SELECT COUNT(id) AS count, `Org1`.`data` AS `OrganizationName`,
        `Org2`.`data` AS `OrganizationDisplayName`, `Org3`.`data` AS `OrganizationURL`
      FROM `Entities`
      LEFT JOIN `Organization` Org1
        ON `Entities`.`id` = `Org1`.`entity_id` AND `Org1`.`element` = 'OrganizationName' AND `Org1`.`lang` = :Lang
      LEFT JOIN `Organization` Org2
        ON `Entities`.`id` = `Org2`.`entity_id` AND `Org2`.`element` = 'OrganizationDisplayName'
        AND `Org2`.`lang` = :Lang
      LEFT JOIN `Organization` Org3
        ON `Entities`.`id` = `Org3`.`entity_id` AND `Org3`.`element` = 'OrganizationURL' AND `Org3`.`lang` = :Lang
      WHERE `Entities`.`status` = 1 AND `Entities`.`publishIn` > 1
      GROUP BY `OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`");
    if (isset($_GET['lang'])) {
      switch ($_GET['lang']) {
        case 'sv' :
          $showSv = true;
          $showEn = false;
          break;
        case 'en' :
        default :
          $showSv = false;
          $showEn = true;
      }
      if (isset($_GET['name']) && isset($_GET['display']) && isset($_GET['url'])) {
        $entitiesHandler = $this->metaDb->prepare(
          "SELECT `id`, `entityID`, `Org1`.`data` AS `OrganizationName`,
            `Org2`.`data` AS `OrganizationDisplayName`, `Org3`.`data` AS `OrganizationURL`
          FROM `Entities`, `Organization` AS Org1, `Organization` AS Org2, `Organization` AS Org3
          WHERE `Entities`.`status` = 1 AND `Entities`.`publishIn` > 1
            AND `Entities`.`id` = `Org1`.`entity_id` AND `Org1`.`element` = 'OrganizationName'
            AND `Org1`.`lang` = :Lang AND `Org1`.`data` = :OrganizationName
            AND `Entities`.`id` = `Org2`.`entity_id` AND `Org2`.`element` = 'OrganizationDisplayName'
            AND `Org2`.`lang` = :Lang AND `Org2`.`data` = :OrganizationDisplayName
            AND `Entities`.`id` = `Org3`.`entity_id` AND `Org3`.`element` = 'OrganizationURL'
            AND `Org3`.`lang` = :Lang AND `Org3`.`data` = :OrganizationURL
          ORDER BY `entityID`");
        $entitiesHandler->execute(array('OrganizationName' => $_GET['name'],
          'OrganizationDisplayName' => $_GET['display'],
          'OrganizationURL' => $_GET['url'],
          'Lang' => $_GET['lang']));
        printf ('    <h4>Entities</h4>
    <table id="Entities-table" class="table table-striped table-bordered">
      <thead><tr>
        <th>entityID</th>
        <th>OrganizationName</th>
        <th>OrganizationDisplayName</th>
        <th>OrganizationURL</th>
      </tr></thead>%s',
        "\n");
        while ($entity = $entitiesHandler->fetch(PDO::FETCH_ASSOC)) {
          printf ('      <tr>
        <td><a href="?showEntity=%d">%s</a></td>
        <td>%s</td>
        <td>%s</td>
        <td>%s</td>
      </tr>%s',
            $entity['id'], $entity['entityID'],
            $entity['OrganizationName'], $entity['OrganizationDisplayName'],
            $entity['OrganizationURL'], "\n");
        }
        printf ('%s', self::HTML_TABLE_END);
      }
    } else {
      $showSv = false;
      $showEn = true;
    }

    $organizationHandler->execute(array('Lang' => 'sv'));
    $this->showCollapse('Swedish', 'Organizations-sv', false, 0, $showSv);
    $this->printOrgList($organizationHandler, 'sv');
    $this->showCollapseEnd('Organizations-sv', 0);

    $organizationHandler->execute(array('Lang' => 'en'));
    $this->showCollapse('English', 'Organizations-en', false, 0, $showEn);
    $this->printOrgList($organizationHandler, 'en');
    $this->showCollapseEnd('Organizations-en', 0);

  }
  private function printOrgList($organizationHandler, $lang){
    printf ('
          <table id="Organization%s-table" class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>OrganizationName</th>
                <th>OrganizationDisplayName</th>
                <th>OrganizationURL</th>
                <th>Count</th>
              </tr>
            </thead>%s',
      $lang, "\n");
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($organization['OrganizationName'] != '' && $organization['OrganizationDisplayName'] != '' &&
        $organization['OrganizationURL'] != '') {
        printf ('            <tr>
              <td>%s</td>
              <td>%s</td>
              <td>%s</td>
              <td><a href="?action=OrganizationsInfo&name=%s&display=%s&url=%s&lang=%s">%d</a></td>
            </tr>%s',
          $organization['OrganizationName'], $organization['OrganizationDisplayName'],
          $organization['OrganizationURL'],
          $organization['OrganizationName'], $organization['OrganizationDisplayName'],
          $organization['OrganizationURL'], $lang,
          $organization['count'], "\n");
      }
    }
    printf ('      %s', self::HTML_TABLE_END);
  }

  public function showMembers($userLevel) {
    # Default values
    $impsActive = '';
    $impsSelected = 'false';
    $impsShow = '';
    $impsId = 0;
    #
    $organizationsActive = '';
    $organizationsSelected = 'false';
    $organizationsShow = '';
    $orgId = 0;
    #
    $scopesActive='';
    $scopesSelected='false';
    $scopesShow='';

    if (isset($_GET["tab"])) {
      switch ($_GET["tab"]) {
        case 'organizations' :
          $organizationsActive = ' active';
          $organizationsSelected = 'true';
          $organizationsShow = ' show';
          $orgId = isset($_GET['id']) ? $_GET['id'] : 0;
          break;
        case 'scopes' :
          $scopesActive=self::HTML_ACTIVE;
          $scopesSelected='true';
          $scopesShow=self::HTML_SHOW;
        default :
          $impsActive = ' active';
          $impsSelected = 'true';
          $impsShow = ' show';
          $impsId = isset($_GET['id']) ? $_GET['id'] : 0;
      }
    } else {
      $impsActive = ' active';
      $impsSelected = 'true';
      $impsShow = ' show';
    }

    printf('    <div class="row">
      <div class="col">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
          <li class="nav-item">
            <a class="nav-link%s" id="scope-tab" data-toggle="tab" href="#IMPS" role="tab"
              aria-controls="IMPS" aria-selected="%s">IMPS</a>
          </li>
          <li class="nav-item">
            <a class="nav-link%s" id="organizations-tab" data-toggle="tab" href="#organizations" role="tab"
              aria-controls="organizations" aria-selected="%s">Organizations</a>
          </li>
          <li class="nav-item">
            <a class="nav-link%s" id="scope-tab" data-toggle="tab" href="#scopes" role="tab"
              aria-controls="scopes" aria-selected="%s">Scopes</a>
          </li>
        </ul>
      </div>%s    </div>%s    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade%s%s" id="IMPS" role="tabpanel" aria-labelledby="IMPS-tab">',
      $impsActive, $impsSelected, $organizationsActive, $organizationsSelected, $scopesActive, $scopesSelected, "\n", "\n",
      $impsShow, $impsActive);
    $this->showIMPSList($impsId, $userLevel);
    printf('%s      </div><!-- End tab-pane IMPS -->
      <div class="tab-pane fade%s%s" id="organizations" role="tabpanel" aria-labelledby="organizations-tab">',
        "\n", $organizationsShow, $organizationsActive);
    $this->showOrganizationInfoLists($orgId, $userLevel);
    printf('%s      </div><!-- End tab-pane organizations -->
      <div class="tab-pane fade%s%s" id="scopes" role="tabpanel" aria-labelledby="scopes-tab">',
        "\n", $scopesShow, $scopesActive);
    $this->showScopeList();
    printf('%s      </div><!-- End tab-pane scopes -->
    </div><!-- End tab-content -->%s',"\n", "\n");
  }
  private function showIMPSList($id, $userLevel) {
    $impsHandler = $this->metaDb->prepare(
      'SELECT `IMPS`.`id`,`name`, `maximumAL`, `lastUpdated`, `lastValidated`,
        `OrganizationInfo`.`id` AS orgId, `OrganizationDisplayNameSv`, `OrganizationDisplayNameEn`,
        `email`, `fullName`
        FROM `OrganizationInfo`, `IMPS`
        LEFT JOIN `Users` ON `Users`.`id` = `IMPS`.`user_id`
        WHERE `OrganizationInfo_id` = `OrganizationInfo`.`id` AND `OrganizationInfo`.`notMemberAfter` is NULL
        ORDER BY `name`');
    $idpHandler = $this->metaDb->prepare(
      'SELECT `id`, `entityID`
      FROM `Entities`, `IdpIMPS`
      WHERE `id` = `entity_id` AND `IMPS_id` = :Id');
    $impsHandler->execute();
    while ($imps = $impsHandler->fetch(PDO::FETCH_ASSOC)) {
      $idpHandler->execute(array(self::BIND_ID => $imps['id']));
      $lastValidated = substr($imps['lastValidated'], 0 ,10);
      $name = $imps['name'] . " (AL" . $imps['maximumAL'] . ") - " . $lastValidated;
      #showCollapse($title, $name, $haveSub=true, $step=0, $expanded=true, $extra = false, $entityId=0, $oldEntityId=0)
      $this->showCollapse($name, "imps-" . $imps['id'], false, 1, $id == $imps['id'], false, 0, 0);
      $orgName = $imps['OrganizationDisplayNameSv'] == '' ? $imps['OrganizationDisplayNameEn'] : $imps['OrganizationDisplayNameSv'];
      if ($userLevel > 10) {
        printf('%s                <a href="?action=Members&subAction=editImps&id=%d"><i class="fa fa-pencil-alt"></i></a>
                <a href="?action=Members&subAction=removeImps&id=%d"><i class="fas fa-trash"></i></a>', "\n", $imps['id'], $imps['id']);
      }
      $validatedBy = $imps['lastUpdated'] == $lastValidated ? '(BoT)' : $imps['fullName'] . "(" . $imps['email'] . ")";
      printf('%s                <ul>
                  <li>Organization  : <a href="?action=Members&tab=organizations&id=%d">%s</a></li>
                  <li>Allowed maximum AL : %d</li>
                  <li>Accepted by Board of Trustees : %s</li>
                  <li>Last validated : %s</li>
                  <li>Last validated by : %s</li>
                </ul>
                <h5>Connected IdP:s</h5>
                <ul>%s',
        "\n", $imps['orgId'], $orgName, $imps['maximumAL'],
        $imps['lastUpdated'], $lastValidated, $validatedBy, "\n");
        while ($idp = $idpHandler->fetch(PDO::FETCH_ASSOC)) {
          printf ('                  <li><a href="?showEntity=%d" target="_blank">%s</a></li>%s', $idp['id'], $idp['entityID'] , "\n");
        }
        print '                </ul>';
      $this->showCollapseEnd("imps-" . $imps['id'], 1);
    }
  }
  private function showOrganizationInfoLists($id, $userLevel) {
    $organizationHandler = $this->metaDb->prepare(
      'SELECT `OrganizationInfo`.`id` AS orgId,
          `OrganizationNameSv`, `OrganizationDisplayNameSv`, `OrganizationURLSv`,
          `OrganizationNameEn`, `OrganizationDisplayNameEn`, `OrganizationURLEn`,
          `memberSince`, `notMemberAfter`, COUNT(`IMPS`.`id`) AS count
        FROM `OrganizationInfo`, `IMPS`
        WHERE `OrganizationInfo_id` = `OrganizationInfo`.`id`
        GROUP BY(orgId)
        ORDER BY `OrganizationDisplayNameSv`, `OrganizationDisplayNameEn`');
    $impsHandler = $this->metaDb->prepare(
      'SELECT `id`,`name`, `maximumAL`, `lastValidated`
        FROM `IMPS`
        WHERE `OrganizationInfo_id` = :Id
        ORDER BY `name`');
    $organizationHandler->execute();
    while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
      $impsHandler->execute(array(self::BIND_ID => $organization['orgId']));
      $name = $organization['OrganizationDisplayNameSv'] == '' ? $organization['OrganizationDisplayNameEn'] : $organization['OrganizationDisplayNameSv'];
      $name .= "(" . $organization['count'] . ")";
      $this->showCollapse($name, "org-" . $organization['orgId'], false, 1, $id == $organization['orgId'], false, 0, 0);
      printf('%s                <ul>
                  <li>Swedish (sv)
                    <ul>
                      <li>Name : %s</li>
                      <li>DisplayName : %s</li>
                      <li>URL : %s</li>
                    </ul>
                  </li>
                  <li>English (en)
                    <ul>
                      <li>Name : %s</li>
                      <li>DisplayName : %s</li>
                      <li>URL : %s</li>
                    </ul>
                  </li>
                  <li>memberSince : %s</li>
                  <li>IMPS:s
                    <ul>%s', "\n",
                $organization['OrganizationNameSv'],
                $organization['OrganizationDisplayNameSv'],
                $organization['OrganizationURLSv'],
                $organization['OrganizationNameEn'],
                $organization['OrganizationDisplayNameEn'],
                $organization['OrganizationURLEn'],
                $organization['memberSince'],
                  "\n");
      while ($imps = $impsHandler->fetch(PDO::FETCH_ASSOC)) {
        printf ('                      <li><a href="?action=Members&tab=imps&id=%d">%s</a> (AL%d) - %s</li>%s', $imps['id'], $imps['name'], $imps['maximumAL'], substr($imps['lastValidated'], 0, 10),"\n");
      }
      print '                    </ul>
                  </li>
                </ul>';
      $this->showCollapseEnd("org-" . $organization['orgId'], 1);
    }
  }
  private function showScopeList() {
    printf ('        <table id="scope-table" class="table table-striped table-bordered">
          <thead><tr><th>Scope</th><th>EntityID</th><th>OrganizationName</th></tr></thead>%s', "\n");
    $scopeHandler = $this->metaDb->prepare("SELECT DISTINCT `scope`, `entityID`, `data`, `id`
                        FROM `Scopes` ,`Entities`, `Organization`
                        WHERE `Scopes`.`entity_id` = `Entities`.`id` AND
                          `publishIn` > 1 AND
                          `status` = 1 AND
                          `Organization`.`entity_id` = `Entities`.`id` AND
                          `Organization`.`lang` = 'sv' AND
                          `element`= 'OrganizationName'");
    $scopeHandler->execute();
    while ($scope = $scopeHandler->fetch(PDO::FETCH_ASSOC)) {
      printf ('          <tr>
            <td>%s</td>
            <td><a href="?showEntity=%d"><span class="text-truncate">%s</span></td>
            <td>%s</td>
          </tr>%s',
        $scope['scope'], $scope['id'], $scope['entityID'], $scope['data'], "\n");
    }
    printf ('    %s', self::HTML_TABLE_END);
  }

  public function showHelp() {
    print "    <p>The SWAMID Metadata Tool is the place where you can see,
      register, update and remove metadata for Identity Providers and
      Service Providers in the Academic Identity Federation SWAMID.</p>\n";
      $this->showCollapse('Request admin access', 'RequestAdminAccess', false, 0, false);?>

      To be able to update, remove or confirm an entity you must have administrative access to that entity. How to request access:
      <ol>
        <li>Go to the tab "Published".</li>
        <li>Choose the entity you want to have administrative access to by clicking on its entityID.</li>
        <li>Click on the button "Request admin access" to start updating the entity.</li>
        <li>Follow the instructions on the next web page.</li>
        <li>Continue to the next step by pressing on the button ”Request Access”.</li>
        <li>An e-mail will be sent to the technical and administrative contacts for confirmation of the requrest.</li>
        <li>Reach out to the administrative contact and ask them to accept your request by following the instructions in the mail.</li>
      </ol><?php
    $this->showCollapseEnd('RequestAdminAccess', 0);
    $this->showCollapse('Register a new entity in SWAMID', 'RegisterNewEntity', false, 0, false);?>

          <ol>
            <li>Go to the tab "Upload new XML".</li>
            <li>Upload the metadata file by clicking "Browse" and selecting the file on your local file system.
              Press "Submit".</li>
            <li>If the new entity is a new version of a existing entity select the EntityId from the
              "Merge from other entity:" dropdown and click "Merge".</li>
            <li>Add or update metadata information by clicking on the pencil for each metadata section.
              Continue adding and changing information in the metadata until the information is
              correct and there are no errors left.
              <ul>
                <li>For a Service Provider remember to add metadata attributes for entity categories,
                  otherwise you will not get any attributes from Identity Providers without manual configuration
                  in the Identity Providers. For more information on entity categories see the wiki page
                  "Entity Categories for Service Providers".</li>
                <li>It is highly recommended that the service adheres to the security profile
                  <a href="https://refeds.org/sirtfi" rel="noopener">Sirtfi</a>.</li>
                <li>You have up to two weeks to work on your draft. Every change is automatically saved.
                  To find out how to pick up where you left off, see the help topic "Continue working on a draft".</li>
              </ul>
            </li>
            <li>When you are finished and there are no more errors press the button ”Request publication”.</li>
            <li>Follow the instructions on the next web page and choose if the entity shall be published in
              SWAMID and eduGAIN or SWAMID Only federation.</li>
            <li>Continue to the next step by pressing on the button ”Request publication”.</li>
            <li>An e-mail will be sent to your registered address.
              Forward this to SWAMID operations as described in the e-mail.</li>
            <li>SWAMID Operations will now check and publish the request.</li>
          </ol><?php
    $this->showCollapseEnd('RegisterNewEntity', 0);
    $this->showCollapse('Update published entity in SWAMID', 'UpdateEntity', false, 0, false);?>

          <ol>
            <li>Go to the tab "Published".</li>
            <li>Choose the entity you want to  update by clicking on its entityID.</li>
            <li>Click on the button "Create draft" to start updating the entity.</li>
            <li>Add or update metadata information by clicking on the pencil for each metadata section.
              Continue adding and changing information in the metadata until the information is correct
              and there are no errors left.
              <ul>
                <li>For a Service Provider remember to add metadata attributes for entity categories,
                  otherwise you will not get any attributes from Identity Providers without manual configuration
                  in the Identity Providers. For more information on entity categories see the wiki page
                  "Entity Categories for Service Providers".</li>
                <li>It is highly recommended that the service adheres to the security profile
                  <a href="https://refeds.org/sirtfi" target="_blank" rel="noopener">Sirtfi</a>.</li>
                <li>You have up to two weeks to work on your draft. Every change is automatically saved.
                  To find out how to pick up where you left off, see the help topic "Continue working on a draft".</li>
              </ul>
            </li>
            <li>When you are finished and there are no more errors press the button ”Request publication”.</li>
            <li>Follow the instructions on the next web page and choose if the entity shall be published in
              SWAMID and eduGAIN or SWAMID Only federation.</li>
            <li>Continue to the next step by pressing on the button ”Request publication”.</li>
            <li>An e-mail will be sent to your registered address.
              Forward this to SWAMID operations as described in the e-mail.</li>
            <li>SWAMID Operations will now check and publish the request.</li>
          </ol><?php
    $this->showCollapseEnd('UpdateEntity', 0);
    $this->showCollapse('Continue working on a draft', 'ContinueUpdateEntity', false, 0, false);?>

          <ol>
            <li>Go to the tab "Drafts".</li>
            <li>Select the entity you want to continue to update by clicking on its entityID.
              You can only remove drafts for entities that you personally have previously started to update.</li>
            <li>Add or update metadata information by clicking on the pencil for each metadata section.
              Continue adding and changing information in the metadata until the information is correct
              and there are no errors left.
              <ul>
                <li>For a Service Provider remember to add metadata attributes for entity categories,
                  otherwise you will not get any attributes from Identity Providers without manual configuration
                  in the Identity Providers. For more information on entity categories see the wiki page
                  "Entity Categories for Service Providers".</li>
                <li>It is highly recommended that the service adheres to the security profile
                  <a href="https://refeds.org/sirtfi" target="_blank" rel="noopener">Sirtfi</a>.</li>
              </ul>
            </li>
            <li>When you are finished and there are no more errors press the button ”Request publication”.</li>
            <li>Follow the instructions on the next web page and choose if the entity shall be published in
              SWAMID and eduGAIN or SWAMID Only federation.</li>
            <li>Continue to the next step by pressing on the button ”Request publication”.</li>
            <li>An e-mail will be sent to your registered address.
              Forward this to SWAMID operations as described in the e-mail.</li>
            <li>SWAMID Operations will now check and publish the request.</li>
          </ol><?php
    $this->showCollapseEnd('ContinueUpdateEntity', 0);
    $this->showCollapse('Stop and remove a draft update', 'DiscardDraft', false, 0, false);?>

          <ol>
            <li>Go to the tab "Drafts".</li>
            <li>Select the entity for which you want to remove the draft by clicking on its entityID.
              You can only remove drafts for entities that you personally have previously started to update.</li>
            <li>Press the button ”Discard Draft”.</li>
            <li>Confirm the action by pressing the button ”Remove”.</li>
          </ol><?php
    $this->showCollapseEnd('DiscardDraft', 0);
    $this->showCollapse('Withdraw a publication request', 'WithdrawPublicationRequest', false, 0, false);?>

          <ol>
            <li>Go to the tab "Pending".</li>
            <li>Choose the entity for which you want to withdraw the publication request.
              You can only withdraw a publication request for entites where you earlier have requested publication.</li>
            <li>To withdraw the request press the button ”Cancel publication request”.</li>
            <li>To ensure that you are sure of the withdrawel you need to press the button
              ”Cancel request” before the request is processed.</li>
            <li>The entity is now back in draft mode so that you can continue to update,
              if you want to to cancel the update press the buton "Discard Draft" and "Remove" on next page.</li>
          </ol><?php
    $this->showCollapseEnd('WithdrawPublicationRequest', 0);
  }
  #############
  # Return collapseIcons
  #############
  public function getCollapseIcons() {
    return $this->collapseIcons;
  }
}
