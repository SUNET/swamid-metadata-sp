<?php
namespace metadata;

use PDO;
use DOMDocument;

/**
 * Class to collect common functions for Validate and ParseXML
 */
class Common {
  use SAMLTrait;

  # Setup
  protected $config;

  protected $dbIdNr = 0;
  protected $entityID = 'Unknown';
  protected $entityExists = false;
  protected $error = '';
  protected $errorNB = '';
  protected $isIdP = false;
  protected $isSP = false;
  protected $isAA = false;
  protected $registrationInstant = '';
  protected $result = '';
  protected $warning = '';
  protected $xml;
  private $handleXML = true;

  const BIND_COCOV1STATUS = ':Cocov1Status';
  const BIND_ERRORS = ':Errors';
  const BIND_ERRORSNB = ':ErrorsNB';
  const BIND_HEIGHT = ':Height';
  const BIND_ID = ':Id';
  const BIND_NOSIZE = ':NoSize';
  const BIND_REGISTRATIONINSTANT = ':RegistrationInstant';
  const BIND_RESULT = ':Result';
  const BIND_STATUS = ':Status';
  const BIND_TYPE = ':Type';
  const BIND_URL = ':URL';
  const BIND_VALIDATIONOUTPUT = ':validationOutput';
  const BIND_WARNINGS = ':Warnings';
  const BIND_WIDTH = ':Width';
  const BIND_XML = ':Xml';

  /**
   * Setup the class
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
      $sql = 'SELECT `id`, `entityID`, `isIdP`, `isSP`, `isAA`, `publishIn`, `status`, `errors`, `errorsNB`, `warnings`,
          `registrationInstant`, `validationOutput`';
      $sql .= $this->handleXML ? ', `xml` ' : ' ';
      $sql .= 'FROM `Entities` WHERE `id` = :Id';
      $entityHandler = $this->config->getDb()->prepare($sql);
      $entityHandler->execute(array(self::BIND_ID => $id));
      if ($entity = $entityHandler->fetch(PDO::FETCH_ASSOC)) {
        $this->entityExists = true;
        $this->dbIdNr = $entity['id'];
        $this->entityID = $entity['entityID'];
        $this->isIdP = $entity['isIdP'];
        $this->isSP = $entity['isSP'];
        $this->isAA = $entity['isAA'];
        $this->warning = $entity['warnings'];
        $this->error = $entity['errors'];
        $this->errorNB = $entity['errorsNB'];
        $this->result = $entity['validationOutput'];
        $this->registrationInstant = $entity['registrationInstant'];
        if ($this->handleXML) {
          $this->xml = new DOMDocument;
          $this->xml->preserveWhiteSpace = false;
          $this->xml->formatOutput = true;
          $this->xml->loadXML($entity['xml']);
          $this->xml->encoding = 'UTF-8';
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
   * @param integer $type
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
   * @param boolean $verbose if we should be verbose during revalidation
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
   * @param integer $limit Number of URL:s to validate
   *
   * @param boolean $verbose if we should be verbose during validation
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
    if ($limit > 10) {
      $sql = "SELECT `URL`, `type` FROM `URLs`
        WHERE `lastValidated` < ADDTIME(NOW(), '-7 0:0:0')
          OR ((`status` > 0 OR `cocov1Status` > 0) AND `lastValidated` < ADDTIME(NOW(), '-6:0:0'))
        ORDER BY `lastValidated` LIMIT $limit;";
    } else {
      $sql = "SELECT `URL`, `type`
        FROM `URLs`
        WHERE `lastValidated` < ADDTIME(NOW(), '-20 0:0:0')
          OR ((`status` > 0 OR `cocov1Status` > 0) AND `lastValidated` < ADDTIME(NOW(), '-8:0:0'))
        ORDER BY `lastValidated` LIMIT $limit;";
    }
    $urlHandler = $this->config->getDb()->prepare($sql);
    $urlHandler->execute();
    $count = 0;
    if ($verbose) {
      printf ('    <table class="table table-striped table-bordered">%s', "\n");
    }
    while ($url = $urlHandler->fetch(PDO::FETCH_ASSOC)) {
      $urlUpdateHandler->bindValue(self::BIND_URL, $url['URL']);

      curl_setopt($ch, CURLOPT_URL, $url['URL']);
      $height = 0;
      $width = 0;
      $nosize = 0;
      $verboseInfo = sprintf('<tr><td>%s</td><td>', $url['URL']);
      $output = curl_exec($ch);
      if (curl_errno($ch)) {
        $verboseInfo .= 'Curl error';
        $urlUpdateHandler->bindValue(self::BIND_RESULT, curl_error($ch));
        $urlUpdateHandler->bindValue(self::BIND_STATUS, 3);
        $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
      } else {
        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
          case 200 :
            $verboseInfo .= 'OK : content-type = ' . curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            if (substr(curl_getinfo($ch, CURLINFO_CONTENT_TYPE),0,6) == 'image/') {
              if (substr(curl_getinfo($ch, CURLINFO_CONTENT_TYPE),0,13) == 'image/svg+xml') {
                $nosize = 1;
              } else {
                $size = getimagesizefromstring($output);
                $width = $size[0];
                $height = $size[1];
              }
            }
            switch ($url['type']) {
              case 1 :
              case 2 :
                $urlUpdateHandler->bindValue(self::BIND_RESULT, 'Reachable');
                $urlUpdateHandler->bindValue(self::BIND_STATUS, 0);
                $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 0);
                break;
              case 3 :
                if (strpos ( $output, self::SAML_EC_COCOV1) > 1 ) {
                  $urlUpdateHandler->bindValue(self::BIND_RESULT, 'Policy OK');
                  $urlUpdateHandler->bindValue(self::BIND_STATUS, 0);
                  $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 0);
                } else {
                  $urlUpdateHandler->bindValue(self::BIND_RESULT,
                    'Policy missing link to ' . self::SAML_EC_COCOV1);
                  $urlUpdateHandler->bindValue(self::BIND_STATUS, 0);
                  $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
                }
                break;
              default :
                break;
            }
            break;
          case 403 :
            $verboseInfo .= '403';
            $urlUpdateHandler->bindValue(self::BIND_RESULT, "Access denied. Can't check URL.");
            $urlUpdateHandler->bindValue(self::BIND_STATUS, 2);
            $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
            break;
          case 404 :
            $verboseInfo .= '404';
            $urlUpdateHandler->bindValue(self::BIND_RESULT, 'Page not found.');
            $urlUpdateHandler->bindValue(self::BIND_STATUS, 2);
            $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
            break;
          case 503 :
            $verboseInfo .= '503';
            $urlUpdateHandler->bindValue(self::BIND_RESULT, "Service Unavailable. Can't check URL.");
            $urlUpdateHandler->bindValue(self::BIND_STATUS, 2);
            $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
            break;
          default :
            $verboseInfo .= $http_code;
            $urlUpdateHandler->bindValue(self::BIND_RESULT,
              "Contact operation@swamid.se. Got code $http_code from web-server. Cant handle :-(");
            $urlUpdateHandler->bindValue(self::BIND_STATUS, 2);
            $urlUpdateHandler->bindValue(self::BIND_COCOV1STATUS, 1);
        }
      }
      $this->checkURLStatus($url['URL'], $verbose);
      $urlUpdateHandler->bindValue(self::BIND_HEIGHT, $height);
      $urlUpdateHandler->bindValue(self::BIND_WIDTH, $width);
      $urlUpdateHandler->bindValue(self::BIND_NOSIZE, $nosize);
      $urlUpdateHandler->execute();
      $count ++;
      if ($verbose) {
        printf ('      %s</td></tr>%s', $verboseInfo, "\n");
      }
    }
    if ($verbose) {
      printf ('    </table>%s', "\n");
    }
    curl_close($ch);
    if ($limit > 10) {
      printf ("Checked %d URL:s\n", $count);
    }
  }

  /**
   * Check old URL:s
   *
   * Checks URL:s not seen in age number of days.
   * Run a curl agains the URL:s up for validation.
   * Start by those with oldest lastValidated
   *
   * @param integer $limit Number of URL:s to validate
   *
   * @param boolean $verbose if we should be verbose during validation
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
   * @param integer $limit Number of URL:s to validate
   *
   * @param boolean $verbose if we should be verbose during validation
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
  protected function saveResults() {
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
}
