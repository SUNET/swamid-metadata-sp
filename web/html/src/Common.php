<?php
namespace metadata;

use PDO;
use DOMDocument;
use DOMElement;

/**
 * Class to collect common functions for Validate and ParseXML
 */
class Common {
  use SAMLTrait;

  # Setup
  protected Configuration $config;

  protected int $dbIdNr = 0;
  protected string $entityID = 'Unknown';
  protected bool $entityExists = false;
  protected string $error = '';
  protected string $errorNB = '';
  protected bool $isIdP = false;
  protected bool $isSP = false;
  protected bool $isAA = false;
  protected int $feedValue = 0;
  protected string $registrationInstant = '';
  protected string $result = '';
  protected string $warning = '';
  protected int $status = 0;
  protected int $organizationInfoId = 0;

  protected DOMDocument $xml;
  protected DOMElement $entityDescriptor;
  private bool $handleXML = true;

  const BIND_BITS = ':Bits';
  const BIND_COCOV1STATUS = ':Cocov1Status';
  const BIND_CONTACTTYPE = ':ContactType';
  const BIND_DATA = ':Data';
  const BIND_ELEMENT = ':Element';
  const BIND_EMAIL = ':Email';
  const BIND_ENTITYID = ':EntityID';
  const BIND_ERRORS = ':Errors';
  const BIND_ERRORSNB = ':ErrorsNB';
  const BIND_FRIENDLYNAME = ':FriendlyName';
  const BIND_FULLNAME = ':FullName';
  const BIND_HEIGHT = ':Height';
  const BIND_ID = ':Id';
  const BIND_INDEX = ':Index';
  const BIND_ISREQUIRED = ':IsRequired';
  const BIND_ISSUER = ':Issuer';
  const BIND_KEY_TYPE = ':Key_type';
  const BIND_LANG = ':Lang';
  const BIND_NAME = ':Name';
  const BIND_NAMEFORMAT = ':NameFormat';
  const BIND_NOSIZE = ':NoSize';
  const BIND_NOTVALIDAFTER = ':NotValidAfter';
  const BIND_ORDER = ':Order';
  const BIND_REGEXP = ':Regexp';
  const BIND_REGISTRATIONINSTANT = ':RegistrationInstant';
  const BIND_RESULT = ':Result';
  const BIND_SCOPE = ':Scope';
  const BIND_SERIALNUMBER = ':SerialNumber';
  const BIND_STATUS = ':Status';
  const BIND_SUBJECT = ':Subject';
  const BIND_TYPE = ':Type';
  const BIND_URL = ':URL';
  const BIND_USE = ':Use';
  const BIND_VALIDATIONOUTPUT = ':validationOutput';
  const BIND_VALUE = ':Value';
  const BIND_WARNINGS = ':Warnings';
  const BIND_WIDTH = ':Width';
  const BIND_XML = ':Xml';

  /**
   * Setup the class
   *
   * @param int $id id in database for entity
   *
   * @param bool $xml if we should setup xml handling
   *
   * @return void
   */
  public function __construct($id = 0, $xml = true) {
    global $config;
    $this->handleXML = $xml;
    if (isset($config)) {
      $this->config = $config;
    } else {
      $this->config = new Configuration();
    }
    if ($id > 0) {
      $sql = 'SELECT `id`, `entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `OrganizationInfo_id`,
        `errors`, `errorsNB`, `warnings`, `registrationInstant`, `validationOutput`';
      $sql .= $this->handleXML ? ', `xml` ' : ' ';
      $sql .= 'FROM `Entities` WHERE `id` = :Id';
      $entityHandler = $this->config->getDb()->prepare($sql);
      $entityHandler->execute(array(self::BIND_ID => $id));
      if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->entityExists = true;
        $this->dbIdNr = $entity['id'];
        $this->status = $entity['status'];
        $this->organizationInfoId = $entity['OrganizationInfo_id'];
        $this->entityID = $entity['entityID'];
        $this->isIdP = $entity['isIdP'] == 1;
        $this->isSP = $entity['isSP'] == 1;
        $this->isAA = $entity['isAA'] == 1;
        $this->feedValue = $entity['publishIn'];
        $this->warning = strval($entity['warnings']);
        $this->error = strval($entity['errors']);
        $this->errorNB = strval($entity['errorsNB']);
        $this->result = strval($entity['validationOutput']);
        $this->registrationInstant = strval($entity['registrationInstant']);
        if ($this->handleXML) {
          $this->xml = new DOMDocument;
          $this->xml->preserveWhiteSpace = false;
          $this->xml->formatOutput = true;
          $this->xml->loadXML($entity['xml']);
          $this->xml->encoding = 'UTF-8';
          $this->getEntityDescriptor($this->xml);
        }
      }
    }
  }

  /**
   * Add URL to list for checking
   *
   * Add the URL to list of URL:s to check
   *
   * @param string $url URL that should be checked
   *
   * @param int $type
   *  - 1 Check reachable (OK If reachable)
   *  - 2 Check reachable (NEED to be reachable)
   *  - 3 Check CoCo privacy
   *
   * @return void
   */
  protected function addURL($url, $type) {
    $urlHandler = $this->config->getDb()->prepare('SELECT `type` FROM `URLs` WHERE `URL` = :URL');
    $urlHandler->bindValue(self::BIND_URL, $url);
    $urlHandler->execute();

    if ($currentType = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($currentType['type'] < $type) {
        // Update type and lastSeen + force revalidate
        $urlUpdateHandler = $this->config->getDb()->prepare("
          UPDATE `URLs` SET `type` = :Type, `lastValidated` = '1972-01-01', `lastSeen` = NOW() WHERE `URL` = :URL;");
        $urlUpdateHandler->bindParam(self::BIND_URL, $url);
        $urlUpdateHandler->bindParam(self::BIND_TYPE, $type);
        $urlUpdateHandler->execute();
      } else {
        // Update lastSeen
        $urlUpdateHandler = $this->config->getDb()->prepare("UPDATE `URLs` SET `lastSeen` = NOW() WHERE `URL` = :URL;");
        $urlUpdateHandler->bindParam(self::BIND_URL, $url);
        $urlUpdateHandler->execute();
      }
    } else {
      $urlAddHandler = $this->config->getDb()->prepare("INSERT INTO `URLs`
        (`URL`, `type`, `status`, `lastValidated`, `lastSeen`)
        VALUES (:URL, :Type, 10, '1972-01-01', NOW());");
      $urlAddHandler->bindParam(self::BIND_URL, $url);
      $urlAddHandler->bindParam(self::BIND_TYPE, $type);
      $urlAddHandler->execute();
    }
  }

  /**
   * Revalidates an URL
   *
   * @param string $url URL that should be revalidated
   *
   * @param bool $verbose if we should be verbose during revalidation
   *
   * @return void
   */
  public function revalidateURL($url, $verbose = false) {
    $urlUpdateHandler = $this->config->getDb()->prepare("UPDATE `URLs` SET `lastValidated` = '1972-01-01' WHERE `URL` = :URL;");
    $urlUpdateHandler->bindParam(self::BIND_URL, $url);
    $urlUpdateHandler->execute();
    $this->validateURLs(5, $verbose);
  }

  /**
   * validate an URL
   *
   * Run a curl agains the URL:s up for validation.
   * Start by those with oldest lastValidated
   *
   * @param int $limit Number of URL:s to validate
   *
   * @param bool $verbose if we should be verbose during validation
   *
   * @return void
   */
  public function validateURLs($limit=10, $verbose = false){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'https://metadata.swamid.se/validate');

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    curl_setopt($ch, CURLINFO_HEADER_OUT, 0);

    $urlUpdateHandler = $this->config->getDb()->prepare("UPDATE `URLs`
      SET `lastValidated` = NOW(), `status` = :Status, `cocov1Status` = :Cocov1Status,
        `height` = :Height, `width` = :Width, `nosize` = :NoSize, `validationOutput` = :Result
      WHERE `URL` = :URL;");
    $sql = ($limit > 10) ?
      "SELECT `URL`, `type` FROM `URLs`
        WHERE `lastValidated` < ADDTIME(NOW(), '-7 0:0:0')
          OR ((`status` > 0 OR `cocov1Status` > 0) AND `lastValidated` < ADDTIME(NOW(), '-6:0:0'))
        ORDER BY `lastValidated` LIMIT $limit;"
    :
      "SELECT `URL`, `type`
        FROM `URLs`
        WHERE `lastValidated` < ADDTIME(NOW(), '-20 0:0:0')
          OR ((`status` > 0 OR `cocov1Status` > 0) AND `lastValidated` < ADDTIME(NOW(), '-8:0:0'))
        ORDER BY `lastValidated` LIMIT $limit;";
    $urlHandler = $this->config->getDb()->prepare($sql);
    $urlHandler->execute();
    $count = 0;
    while ($url = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      $updateArray = array(
        self::BIND_URL => $url['URL'],
        self::BIND_HEIGHT => 0,
        self::BIND_WIDTH => 0,
        self::BIND_NOSIZE => 0
      );

      curl_setopt($ch, CURLOPT_URL, $url['URL']);
      $verboseInfo = sprintf('<tr><td>%s</td><td>', $url['URL']);
      $output = curl_exec($ch);
      if (curl_errno($ch)) {
        $verboseInfo .= 'Curl error';
        $updateArray[self::BIND_RESULT] = curl_error($ch);
        $updateArray[self::BIND_STATUS] = 3;
        $updateArray[self::BIND_COCOV1STATUS] = 1;
      } else {
        $this->checkCurlReturnCode($ch, $output, $url['type'], $updateArray, $verboseInfo);
      }
      $this->checkURLStatus($url['URL'], $verbose);
      $urlUpdateHandler->execute($updateArray);
      $count ++;
      $verboseInfo .= sprintf ('      </td></tr>%s', "\n");
    }
    if ($verbose) {
      printf('    <table class="table table-striped table-bordered">%s%s    </table>%s', "\n", $verboseInfo, "\n");
    }
    curl_close($ch);
    if ($limit > 10) {
      printf ("Checked %d URL:s\n", $count);
    }
  }

  private function checkCurlReturnCode($ch, $output, $type, &$updateArray, &$verboseInfo) {
    switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
      case 200 :
        $verboseInfo .= 'OK : content-type = ' . curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        if (substr(curl_getinfo($ch, CURLINFO_CONTENT_TYPE),0,6) == 'image/') {
          if (substr(curl_getinfo($ch, CURLINFO_CONTENT_TYPE),0,13) == 'image/svg+xml') {
            $updateArray[self::BIND_NOSIZE] = 1;
          } else {
            $size = getimagesizefromstring($output);
            $updateArray[self::BIND_WIDTH] = $size[0];
            $updateArray[self::BIND_HEIGHT] = $size[1];
          }
        }
        if ($type == 3) {
          if (strpos ( $output, self::SAML_EC_COCOV1) > 1 ) {
            $updateArray[self::BIND_RESULT] = 'Policy OK';
            $updateArray[self::BIND_STATUS] = 0;
            $updateArray[self::BIND_COCOV1STATUS] = 0;
          } else {
            $updateArray[self::BIND_RESULT] = 'Policy missing link to ' . self::SAML_EC_COCOV1;
            $updateArray[self::BIND_STATUS] = 0;
            $updateArray[self::BIND_COCOV1STATUS] = 1;
          }
        } else {
          $updateArray[self::BIND_RESULT] = 'Reachable';
          $updateArray[self::BIND_STATUS] = 0;
          $updateArray[self::BIND_COCOV1STATUS] = 0;
        }
        break;
      case 403 :
        $verboseInfo .= '403';
        $updateArray[self::BIND_RESULT] = "Access denied. Can't check URL.";
        $updateArray[self::BIND_STATUS] = 2;
        $updateArray[self::BIND_COCOV1STATUS] = 1;
        break;
      case 404 :
        $verboseInfo .= '404';
        $updateArray[self::BIND_RESULT] = 'Page not found.';
        $updateArray[self::BIND_STATUS] = 2;
        $updateArray[self::BIND_COCOV1STATUS] = 1;
        break;
      case 503 :
        $verboseInfo .= '503';
        $updateArray[self::BIND_RESULT] = "Service Unavailable. Can't check URL.";
        $updateArray[self::BIND_STATUS] = 2;
        $updateArray[self::BIND_COCOV1STATUS] = 1;
        break;
      default :
        $verboseInfo .= $http_code;
        $updateArray[self::BIND_RESULT] = "Contact operation@swamid.se. Got code $http_code from web-server. Cant handle :-(";
        $updateArray[self::BIND_STATUS] = 2;
        $updateArray[self::BIND_COCOV1STATUS] = 1;
    }
  }

  /**
   * Check old URL:s
   *
   * Checks URL:s not seen in age number of days.
   * Run a curl agains the URL:s up for validation.
   * Start by those with oldest lastValidated
   *
   * @param int $limit Number of URL:s to validate
   *
   * @param bool $verbose if we should be verbose during validation
   *
   * @return void
   */
  public function checkOldURLS($age = 30, $verbose = false) {
    $sql = sprintf("SELECT `URL`, `lastSeen` from `URLs` where `lastSeen` < ADDTIME(NOW(), '-%d 0:0:0')", $age);
    $urlHandler = $this->config->getDb()->prepare($sql);
    $urlHandler->execute();
    while ($urlInfo = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      if ($verbose) { printf ("Checking : %s last seen %s\n", $urlInfo['URL'], $urlInfo['lastSeen']); }
      $this->checkURLStatus($urlInfo['URL'], $verbose);
    }
  }

  /**
   * Check URL:s status
   *
   * Checks URL:s not seen in age number of days.
   * Run a curl agains the URL:s up for validation.
   * Start by those with oldest lastValidated
   *
   * @param int $limit Number of URL:s to validate
   *
   * @param bool $verbose if we should be verbose during validation
   *
   * @return void
   */
  private function checkURLStatus($url, $verbose = false){
    $urlHandler = $this->config->getDb()->prepare('SELECT `type`, `validationOutput`, `lastValidated`
      FROM `URLs` WHERE `URL` = :URL');
    $urlHandler->bindValue(self::BIND_URL, $url);
    $urlHandler->execute();
    if ($urlInfo = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      $missing = true;
      $coCoV1 = false;
      $logo = false;
      $entityHandler = $this->config->getDb()->prepare('SELECT `entity_id`, `entityID`, `status`
        FROM `EntityURLs`, `Entities` WHERE `entity_id` = `id` AND `URL` = :URL AND `status` < 4');
      $entityHandler->bindValue(self::BIND_URL, $url);
      $entityHandler->execute();
      $ssoUIIHandler = $this->config->getDb()->prepare('SELECT `entity_id`, `type`, `element`, `lang`, `entityID`, `status`
        FROM `Mdui`, `Entities` WHERE `Mdui`.`entity_id` = `Entities`.`id` AND `data` = :URL AND `status`< 4');
      $ssoUIIHandler->bindValue(self::BIND_URL, $url);
      $ssoUIIHandler->execute();
      $organizationHandler = $this->config->getDb()->prepare('SELECT `entity_id`, `element`, `lang`, `entityID`, `status`
        FROM `Organization`, `Entities` WHERE `entity_id` = `id` AND `data` = :URL AND `status`< 4');
      $organizationHandler->bindValue(self::BIND_URL, $url);
      $organizationHandler->execute();
      $entityAttributesHandler = $this->config->getDb()->prepare("SELECT `attribute`
        FROM `EntityAttributes` WHERE `entity_id` = :Id AND type = 'entity-category'");
      if ($entityHandler->fetch(PDO::FETCH_ASSOC)) {
        $missing = false;
      }
      while ($entity = $ssoUIIHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($entity['type'] == 'SPSSO' && $entity['element'] == 'PrivacyStatementURL') {
          $entityAttributesHandler->bindParam(self::BIND_ID, $entity['entity_id']);
          $entityAttributesHandler->execute();
          while ($attribute = $entityAttributesHandler->fetch(PDO::FETCH_ASSOC)) {
            if ($attribute['attribute'] == 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1') { # NOSONAR Should be http://
              $coCoV1 = true;
            }
          }
        }
        switch ($entity['element']) {
          case 'Logo' :
            $logo = true;
            $missing = false;
            break;
          case 'InformationURL' :
          case 'PrivacyStatementURL' :
            $missing = false;
            break;
          default :
            break;
        }
      }
      while ($entity = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
        if ($entity['element'] == 'OrganizationURL') {
          $missing = false;
        }
      }
      if ($missing) {
        $urlHandler = $this->config->getDb()->prepare('DELETE FROM `URLs` WHERE `URL` = :URL');
        $urlHandler->bindValue(self::BIND_URL, $url);
        $urlHandler->execute();
        if ($verbose) { print "Removing URL. Not in use any more\n"; }
      } elseif ($urlInfo['type'] > 2 && !$coCoV1 ) {
        if ($logo) {
          $urlHandler = $this->config->getDb()->prepare('UPDATE `URLs` SET `type` = 2 WHERE `URL` = :URL');
        } else {
          $urlHandler = $this->config->getDb()->prepare('UPDATE `URLs` SET `type` = 1 WHERE `URL` = :URL');
        }
        $urlHandler->bindValue(self::BIND_URL, $url);
        $urlHandler->execute();
        if ($verbose) { print "Not CoCo v1 any more. Removes that flag.\n"; }
      }
    }
  }

  /**
   * Get Result
   *
   * @return string
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Clear Result
   *
   * @return void
   */
  public function clearResult() {
    $this->result = '';
  }

  /**
   * Get Warnings
   *
   * @return string
   */
  public function getWarning() {
    return $this->warning;
  }

  /**
   * Clear Warning
   *
   * @return void
   */
  public function clearWarning() {
    $this->warning = '';
  }

  /**
   * Get Errors
   *
   * @return string
   */
  public function getError() {
    return $this->error . $this->errorNB;
  }

  /**
   * Clear Error
   *
   * @return void
   */
  public function clearError() {
    $this->error = '';
    $this->errorNB = '';
  }

  /**
   * Save Results
   *
   * @return void
   */
  public function saveResults() {
    $sql = 'UPDATE `Entities`
      SET `validationOutput` = :validationOutput,
        `warnings` = :Warnings,
        `errors` = :Errors,
        `errorsNB` = :ErrorsNB,
        `lastValidated` = NOW(),
        `registrationInstant` = :RegistrationInstant ';
    $sql .= $this->handleXML ? ', `xml` = :Xml ' : ' ';
    $sql .= 'WHERE `id` = :Id;';
    $resultHandler = $this->config->getDb()->prepare($sql);
    $resultHandler->bindValue(self::BIND_ID, $this->dbIdNr);
    $resultHandler->bindValue(self::BIND_VALIDATIONOUTPUT, $this->result);
    $resultHandler->bindValue(self::BIND_WARNINGS, $this->warning);
    $resultHandler->bindValue(self::BIND_ERRORS, $this->error);
    $resultHandler->bindValue(self::BIND_ERRORSNB, $this->errorNB);
    $resultHandler->bindValue(self::BIND_REGISTRATIONINSTANT, $this->registrationInstant);
    if ($this->handleXML) {
      $resultHandler->bindValue(self::BIND_XML, $this->xml->saveXML());
    }
    $resultHandler->execute();
  }

  /**
   * Find EntityDescriptor
   *
   * Find EntityDescriptor in $xml and return DOMNode of EntityDescriptor
   *
   * @param DOMNode $xml DOMNode in a XML object
   *
   * @return DOMElement
   */
  protected function getEntityDescriptor($xml) {
    $child = $xml->firstChild;
    while ($child) {
      if ($child->nodeName == self::SAML_MD_ENTITYDESCRIPTOR) {
        $this->entityDescriptor = $child;
        return $child;
      }
      $child = $child->nextSibling;
    }
    return null;
  }

  /**
   * Find Extensions below EntityDescriptor
   *
   * Return DOM of XML if found / created in other cases return null
   *
   * @param DOMNode $xml DOMNode in a XML object
   *
   * @param bool $create If we should create missing Extensions
   *
   * @return DOMElement
   */
  protected function getExtensions($create = true) {
    # Find md:Extensions in XML
    $child = $this->entityDescriptor->firstChild;
    $extensions = null;
    while ($child && ! $extensions) {
      switch ($child->nodeName) {
        case self::SAML_MD_EXTENSIONS :
          $extensions = $child;
          break;
        case self::SAML_MD_SPSSODESCRIPTOR :
        case self::SAML_MD_IDPSSODESCRIPTOR :
        case self::SAML_MD_AUTHNAUTHORITYDESCRIPTOR :
        case self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR :
        case self::SAML_MD_PDPDESCRIPTOR :
        case self::SAML_MD_AFFILIATIONDESCRIPTOR :
        case self::SAML_MD_ORGANIZATION :
        case self::SAML_MD_CONTACTPERSON :
        case self::SAML_MD_ADDITIONALMETADATALOCATION :
        default :
          if ($create) {
            $extensions = $this->xml->createElement(self::SAML_MD_EXTENSIONS);
            $this->entityDescriptor->insertBefore($extensions, $child);
          }
          # Leave switch and while loop
          break 2;
      }
      $child = $child->nextSibling;
    }
    if (! $extensions && $create) {
      # Add if missing
      $extensions = $this->xml->createElement(self::SAML_MD_EXTENSIONS);
      $this->entityDescriptor->appendChild($extensions);
    }
    return $extensions;
  }

  /**
   * Find SSODescriptor below EntityDescriptor
   *
   * Return DOM of XML if found in other cases return null
   *
   * @param DOMNode $xml DOMNode in a XML object
   *
   * @param string $type Type of SSODescript to look for
   *
   * @return DOMNode|bool
   */
  protected function getSSODecriptor($type) {
    switch ($type) {
      case 'SPSSO' :
        $ssoDescriptorName = self::SAML_MD_SPSSODESCRIPTOR;
        break;
      case 'IDPSSO' :
        $ssoDescriptorName = self::SAML_MD_IDPSSODESCRIPTOR;
        break;
      case 'AttributeAuthority' :
        $ssoDescriptorName = self::SAML_MD_ATTRIBUTEAUTHORITYDESCRIPTOR;
        break;
      default:
    }
    # Find md:xxxSSODescriptor in XML
    $child = $this->entityDescriptor->firstChild;
    $ssoDescriptor = null;
    while ($child && ! $ssoDescriptor) {
      $ssoDescriptor = $child->nodeName == $ssoDescriptorName ? $child : null;
      $child = $child->nextSibling;
    }
    return $ssoDescriptor;
  }

  /**
   * Find Extensions below a SSODescriptor
   *
   * Return DOM of XML if found / created in other cases return null
   *
   * @param DOMNode $xml DOMNode of a SSODescriptor as a XML object
   *
   * @param bool $create If we should create missing Extensions
   *
   * @return DOMNode|bool
   */
  protected function getSSODescriptorExtensions($ssoDescriptor, $create = true) {
    $child = $ssoDescriptor->firstChild;
    $extensions = null;
    if ($child) {
      if ($child->nodeName == self::SAML_MD_EXTENSIONS) {
        $extensions = $child;
      } elseif ($create) {
        $extensions = $this->xml->createElement(self::SAML_MD_EXTENSIONS);
        $ssoDescriptor->insertBefore($extensions, $child);
      }
    } elseif($create) {
      $extensions = $this->xml->createElement(self::SAML_MD_EXTENSIONS);
      $ssoDescriptor->appendChild($extensions);
    }
    return $extensions;
  }

  protected function getUUInfo($extensions, $create = true) {
    $child = $extensions->firstChild;
    $uuInfo = null;
    $mduiFound = false;
    while ($child && ! $uuInfo) {
      switch ($child->nodeName) {
        case self::SAML_MDUI_UIINFO :
          $mduiFound = true;
          $uuInfo = $child;
          break;
        case self::SAML_MDUI_DISCOHINTS :
          $mduiFound = true;
          if ($create) {
            $uuInfo = $this->xml->createElement(self::SAML_MDUI_UIINFO);
            $extensions->insertBefore($uuInfo, $child);
          }
          break;
        default :
          $uuInfo = $this->xml->createElement(self::SAML_MDUI_UIINFO);
          $extensions->appendChild($uuInfo);
      }
      $child = $child->nextSibling;
    }
    if (! $mduiFound) {
      $this->entityDescriptor->setAttributeNS(self::SAMLXMLNS_URI,
        self::SAMLXMLNS_MDUI, self::SAMLXMLNS_MDUI_URL);
    }
    return $uuInfo;
  }
}
