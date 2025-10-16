<?php
namespace metadata;

use PDO;
use PDOException;

class Configuration {
  /**
   * SMTP configuration
   */
  private array $smtp = array();

  /**
   * If we use SMTP AUTH
   */
  private bool $smtpAuth = false; // Used while sending out in PHPMailer

  /**
   * Information for IMPS
   */
  private $imps = false;

  /**
   * Mode of application
   *
   * Prod / QA or Lab
   */
  private string $mode = 'Lab';

  /**
   * BaseURL of the application
   *
   * Should be the hostname including htt(s)://
   *
   */
  private string $baseURL = '';

  /**
   * Profiles for entitySelectionProfiles
   *
   * https://wiki.refeds.org/display/GROUPS/Entity+Selection+Profile+entity+attribute
   */
  private array $entitySelectionProfiles = array();

  /**
   * The database connection
   */
  private PDO $db;

  /**
   * maps to user privilege level
   *
   * indexed by username,
   */
  private array $userLevels = array();

  /**
   * Informatiom about the federation running the application
   */
  private array $federation = array();

  /**
   * Setup the class
   *
   * Return an array with the smtp configuration
   *
   * @param bool $startDB If we should start the database connection or not.
   *
   * @return void
   */
  public function __construct($startDB = true) {
    include __DIR__ . '/../config.php'; # NOSONAR

    $reqParams = array('db', 'smtp', 'mode', 'baseURL', 'userLevels', 'federation');
    $reqParamsDB = array('servername', 'username', 'password',
      'name');
    $reqParamsSmtp = array('host', 'from', 'replyTo', 'replyName', 'sendOut');
    $reqParamsSmtpSasl = array('user', 'user');
    $reqParamsFederation = array('displayName', 'longName',
      'noAccountHtml', 'localFeed', 'eduGAINFeed',
      'aboutURL', 'contactURL', 'toolName', 'teamName',
      'teamMail', 'logoURL', 'logoWidth', 'logoHeight', 'languages',
      'metadata_main_path', 'metadata_registration_authority_exclude',
      'metadata_registration_authority', 'metadata_registration_policy',
      'rulesName', 'rulesURL', 'roloverDocURL',
      'rulesSectsBoth', 'rulesSectsIdP', 'rulesSectsSP',
      'rulesInfoBoth', 'rulesInfoIdP', 'rulesInfoSP',
      'swamid_assurance', 'checkOrganization', 'cleanAttribuesFromIDPSSODescriptor',
      'releaseCheckResultsURL', 'mdqBaseURL');

    $defaultValuesFederation = array(
      'storeServiceInfo' => false,
      'mdsDbPath' => '',
      'urlCheckUA' => 'https://metadata.swamid.se/validate',
      'urlCheckDataEnabled' => false,
      'urlCheckPlainHTTPEnabled' => false,
    );

    foreach ($reqParams as $param) {
      if (! isset(${$param})) {
        printf ('Missing %s in config.php<br>', $param);
        exit;
      }
    }

    $this->checkParams($db, $reqParamsDB, 'db');
    $this->checkParams($smtp, $reqParamsSmtp, 'smtp');

    $this->checkParams($federation, $reqParamsFederation, 'federation', $defaultValuesFederation);
    if (! isset($federation['extend'])) {
      $federation['extend'] = '';
    }

    $this->mode =  $mode;
    $this->baseURL = $baseURL;
    $this->entitySelectionProfiles = isset($entitySelectionProfiles) ? $entitySelectionProfiles : array();

    # SMTP
    $this->smtp = $smtp;
    if ( isset($smtp['sasl'])) {
      $this->smtpAuth = true;
      # Default to port 587 for Auth
      # Overrided by port in $smtp in config
      $this->smtp['port'] = 587;
      $this->checkParams($smtp['sasl'], $reqParamsSmtpSasl, 'smtp[sasl]');
    } else {
      # Default to port 25
      # Overrided by port in $smtp in config
      $this->smtp['port'] = 25;
    }
    # Overide default 25/587 if configured
    if (isset($smtp['port'])) {
      $this->smtp['port'] = $smtp['port'];
    }
    # Defaults to false if not set
    if (! isset($smtp['bcc'])) {
      $this->smtp['bcc'] = false;
    }

    # Database
    if ($startDB) {
      $options = array();
      if (isset($db['caPath'])) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $db['caPath'];
      }
      try {
        $dbDSN = sprintf('mysql:host=%s;dbname=%s', $db['servername'], $db['name']);
        $this->db = new PDO($dbDSN, $db['username'], $db['password'], $options);
        // set the PDO error mode to exception
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
      }
      $this->checkDBVersion();
    }

    # Users
    $this->userLevels = $userLevels;

    # Federation params
    $this->federation = $federation;

    # IMPS
    if (isset($imps)) {
      $this->checkParams($imps, array('oldDate', 'warn1', 'warn2', 'error'), 'imps');

      $this->imps = $imps;
    }
  }

  /**
   * Check if all requied parameters is present
   *
   * @param array $checkParam Parameter array to check
   *
   * @param array $reqParams Requierd parameters in checkParam
   *
   * @param string Name of array in config
   *
   * @return void
   */
  private function checkParams(&$checkParam, $reqParams, $nameOfParam, $defaultValues = array()) {
    foreach ($defaultValues as $param => $defaultValue) {
      if (! isset($checkParam[$param])) {
        $checkParam[$param] = $defaultValue;
      }
    }
    foreach ($reqParams as $param) {
      if (! isset($checkParam[$param])) {
        printf ('Missing $%s[%s] in config.php<br>', $nameOfParam, $param);
        exit;
      }
    }
  }

  /**
   * Check Database version
   *
   * Check and upgrade if needed
   *
   * @return void
   */
  private function checkDBVersion() {
    try {
      $dbVersionHandler = $this->db->query("SELECT `value` FROM `params` WHERE `id` = 'dbVersion';");
      $dbVersionResult = $dbVersionHandler->fetch(PDO::FETCH_ASSOC);
      $dbVersion = $dbVersionResult['value'];
    } catch(PDOException $e) {
      try {
        $dbEntityHandler = $this->db->query('SELECT `id` FROM `Entities` LIMIT 1');
        $dbEntityHandler->fetch(PDO::FETCH_ASSOC);
        // We have an existing DB. Only need to add params table;
        $dbVersion = 0;
      } catch(PDOException $e) {
        $this->createTables();
        // Don't forget to update version in createTables!!!
        $dbVersion = 3;
      }
    }
    if ($dbVersion < 2) {
      if ($dbVersion < 1) {
        $this->db->query(
          'CREATE TABLE `params` (
            `id` varchar(20) DEFAULT NULL,
            `value` text DEFAULT NULL
          );');
        $this->db->query(
          "INSERT INTO `params` (`id`, `value`) VALUES ('dbVersion', '1');");

        $this->db->query('CREATE TABLE `DiscoveryResponse` (
          `entity_id` int(10) unsigned NOT NULL,
          `index` smallint(5) unsigned NOT NULL,
          `location` text DEFAULT NULL,
          PRIMARY KEY (`entity_id`,`index`),
          CONSTRAINT `DiscoveryResponse_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
        );');
      }
      $this->db->query('START TRANSACTION;');
      $this->db->query('CREATE TABLE `OrganizationInfoData` (
        `OrganizationInfo_id` int(10) unsigned DEFAULT 0,
        `lang` char(10) DEFAULT NULL,
        `OrganizationName` text DEFAULT NULL,
        `OrganizationDisplayName` text DEFAULT NULL,
        `OrganizationURL` text DEFAULT NULL,
        CONSTRAINT `OrganizationInfoData_ibfk_1` FOREIGN KEY (`OrganizationInfo_id`) REFERENCES `OrganizationInfo` (`id`) ON DELETE CASCADE
      );');
      $this->db->query('ALTER TABLE `Entities`
        ADD `OrganizationInfo_id` int(10) unsigned DEFAULT 0 AFTER `lastValidated`');

      $organizationInfoHandler = $this->db->prepare(
        'SELECT `id`, `OrganizationNameSv`, `OrganizationDisplayNameSv`, `OrganizationURLSv`,
          `OrganizationNameEn`, `OrganizationDisplayNameEn`, `OrganizationURLEn`
        FROM `OrganizationInfo`;');
      $organizationInfoHandlerData = $this->db->prepare(
        'INSERT INTO `OrganizationInfoData` (
          `OrganizationInfo_id`, `lang`,
          `OrganizationName`, `OrganizationDisplayName`, `OrganizationURL`)
        VALUES (:Id, :Lang, :OrganizationName, :OrganizationDisplayName, :OrganizationURL);');

      $organizationInfoHandler->execute();
      while ($orgInfo = $organizationInfoHandler->fetch(PDO::FETCH_ASSOC)) {
        $organizationInfoHandlerData->execute(array(
          ':Id' => $orgInfo['id'],
          ':Lang' => 'sv',
          ':OrganizationName' => $orgInfo['OrganizationNameSv'],
          ':OrganizationDisplayName' => $orgInfo['OrganizationDisplayNameSv'],
          ':OrganizationURL' => $orgInfo['OrganizationURLSv']));
        $organizationInfoHandlerData->execute(array(
          ':Id' => $orgInfo['id'],
          ':Lang' => 'en',
          ':OrganizationName' => $orgInfo['OrganizationNameEn'],
          ':OrganizationDisplayName' => $orgInfo['OrganizationDisplayNameEn'],
          ':OrganizationURL' => $orgInfo['OrganizationURLEn']));
      }

      $this->db->query('ALTER TABLE `OrganizationInfo`
        DROP COLUMN `OrganizationNameSv`,
        DROP COLUMN `OrganizationDisplayNameSv`,
        DROP COLUMN `OrganizationURLSv`,
        DROP COLUMN `OrganizationNameEn`,
        DROP COLUMN `OrganizationDisplayNameEn`,
        DROP COLUMN `OrganizationURLEn`;');
      # Cleanup unused id column:s with autoincrement
      $this->db->query('ALTER TABLE `AttributeConsumingService_RequestedAttribute`
        DROP COLUMN `id`;');
      $this->db->query('ALTER TABLE `AttributeConsumingService_Service`
        DROP COLUMN `id`;');
      $this->db->query('ALTER TABLE `ContactPerson`
        DROP COLUMN `id`;');
      $this->db->query('ALTER TABLE `EntityAttributes`
        DROP COLUMN `id`;');
      $this->db->query('ALTER TABLE `KeyInfo`
        DROP COLUMN `id`;');
      $this->db->query('ALTER TABLE `Mdui` DROP COLUMN `id`;');
      $this->db->query(
        "UPDATE `params` SET `value` = '2' WHERE `id` = 'dbVersion';");
      $this->db->query('COMMIT;');
    }

    if ($dbVersion < 3) {
      $this->db->query('START TRANSACTION;');
      $this->db->query('CREATE TABLE `ServiceInfo` (
        `entity_id` int(10) unsigned NOT NULL,
        `ServiceURL` text NOT NULL,
        `enabled` tinyint(3) unsigned DEFAULT NULL,
        PRIMARY KEY (`entity_id`),
        CONSTRAINT `ServiceInfo_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
      );');
      $this->db->query(
        "UPDATE `params` SET `value` = '3' WHERE `id` = 'dbVersion';");
      $this->db->query('COMMIT;');
    }
  }

  /**
   * Create tables if not already exists
   * Run the first time of use
   *
   * @return void
   */
  private function createTables() {
    $this->db->query('CREATE TABLE `Entities` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `publishedId` int(10) unsigned NOT NULL DEFAULT 0,
      `entityID` varchar(256) DEFAULT NULL,
      `registrationInstant` varchar(256) DEFAULT NULL,
      `isIdP` tinyint(3) unsigned DEFAULT NULL,
      `isSP` tinyint(3) unsigned DEFAULT NULL,
      `isAA` tinyint(3) unsigned DEFAULT NULL,
      `publishIn` tinyint(3) unsigned DEFAULT NULL,
      `status` tinyint(3) unsigned DEFAULT NULL,
      `removalRequestedBy` int(10) unsigned DEFAULT 0,
      `lastUpdated` datetime DEFAULT NULL,
      `lastValidated` datetime DEFAULT NULL,
      `OrganizationInfo_id` int(10) unsigned DEFAULT 0,
      `validationOutput` text DEFAULT NULL,
      `warnings` text DEFAULT NULL,
      `errors` text DEFAULT NULL,
      `errorsNB` text DEFAULT NULL,
      `xml` text DEFAULT NULL,
      PRIMARY KEY (`id`)
    );');

    $this->db->query('CREATE TABLE `Users` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `userID` text DEFAULT NULL,
      `email` text DEFAULT NULL,
      `fullName` text DEFAULT NULL,
      `lastSeen` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `userID` (`userID`) USING HASH
    );');

    $this->db->query('CREATE TABLE `AccessRequests` (
      `entity_id` int(10) unsigned NOT NULL,
      `user_id` int(10) unsigned NOT NULL,
      `hash` varchar(32) DEFAULT NULL,
      `requestDate` datetime DEFAULT NULL,
      PRIMARY KEY (`entity_id`,`user_id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `AccessRequests_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE,
      CONSTRAINT `AccessRequests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `AttributeConsumingService` (
      `entity_id` int(10) unsigned NOT NULL,
      `Service_index` smallint(5) unsigned NOT NULL,
      `isDefault` tinyint(3) unsigned DEFAULT NULL,
      PRIMARY KEY (`entity_id`,`Service_index`),
      CONSTRAINT `AttributeConsumingService_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `AttributeConsumingService_RequestedAttribute` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `entity_id` int(10) unsigned NOT NULL,
      `Service_index` smallint(5) unsigned NOT NULL,
      `FriendlyName` varchar(256) DEFAULT NULL,
      `Name` varchar(256) DEFAULT NULL,
      `NameFormat` varchar(256) DEFAULT NULL,
      `isRequired` tinyint(3) unsigned DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `entity_id` (`entity_id`),
      CONSTRAINT `AttributeConsumingService_RequestedAttribute_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `AttributeConsumingService_Service` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `entity_id` int(10) unsigned NOT NULL,
      `Service_index` smallint(5) unsigned NOT NULL,
      `element` varchar(20) DEFAULT NULL,
      `lang` char(10) DEFAULT NULL,
      `data` text DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `entity_id` (`entity_id`),
      CONSTRAINT `AttributeConsumingService_Service_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );');

    $this->db->query("CREATE TABLE `ContactPerson` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `entity_id` int(10) unsigned NOT NULL,
      `contactType` enum('technical','support','administrative','billing','other') DEFAULT NULL,
      `extensions` varchar(256) DEFAULT NULL,
      `company` varchar(256) DEFAULT NULL,
      `givenName` varchar(256) DEFAULT NULL,
      `surName` varchar(256) DEFAULT NULL,
      `emailAddress` varchar(256) DEFAULT NULL,
      `telephoneNumber` varchar(256) DEFAULT NULL,
      `subcontactType` varchar(256) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `entity_id` (`entity_id`),
      CONSTRAINT `ContactPerson_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );");

    $this->db->query('CREATE TABLE `EntitiesStatistics` (
      `date` datetime NOT NULL,
      `NrOfEntites` int(10) unsigned DEFAULT NULL,
      `NrOfSPs` int(10) unsigned DEFAULT NULL,
      `NrOfIdPs` int(10) unsigned DEFAULT NULL,
      PRIMARY KEY (`date`)
    );');

    $this->db->query('CREATE TABLE `EntityAttributes` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `entity_id` int(10) unsigned NOT NULL,
      `type` varchar(30) DEFAULT NULL,
      `attribute` varchar(256) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `entity_id` (`entity_id`),
      CONSTRAINT `EntityAttributes_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `EntityConfirmation` (
      `entity_id` int(10) unsigned NOT NULL,
      `user_id` int(10) unsigned NOT NULL,
      `lastConfirmed` datetime DEFAULT NULL,
      PRIMARY KEY (`entity_id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `EntityConfirmation_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE,
      CONSTRAINT `EntityConfirmation_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `EntityURLs` (
      `entity_id` int(10) unsigned NOT NULL,
      `URL` text DEFAULT NULL,
      `type` varchar(20) NOT NULL,
      PRIMARY KEY (`entity_id`,`type`),
      CONSTRAINT `EntityURLs_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `EntityUser` (
      `entity_id` int(10) unsigned NOT NULL,
      `user_id` int(10) unsigned NOT NULL,
      `approvedBy` text DEFAULT NULL,
      `lastChanged` datetime DEFAULT NULL,
      PRIMARY KEY (`entity_id`,`user_id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `EntityUser_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE,
      CONSTRAINT `EntityUser_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `ExternalEntities` (
      `entityID` varchar(256) NOT NULL,
      `updated` tinyint(3) unsigned DEFAULT NULL,
      `isIdP` tinyint(3) unsigned DEFAULT NULL,
      `isSP` tinyint(3) unsigned DEFAULT NULL,
      `isAA` tinyint(3) unsigned DEFAULT NULL,
      `displayName` text DEFAULT NULL,
      `serviceName` text DEFAULT NULL,
      `organization` text DEFAULT NULL,
      `contacts` text DEFAULT NULL,
      `scopes` text DEFAULT NULL,
      `ecs` text DEFAULT NULL,
      `ec` text DEFAULT NULL,
      `assurancec` text DEFAULT NULL,
      `ra` text DEFAULT NULL,
      PRIMARY KEY (`entityID`)
    );');

    $this->db->query('CREATE TABLE `OrganizationInfo` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `memberSince` date DEFAULT NULL,
      `notMemberAfter` date DEFAULT NULL,
      PRIMARY KEY (`id`)
    );');

    $this->db->query('CREATE TABLE `IMPS` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `OrganizationInfo_id` int(10) unsigned DEFAULT NULL,
      `name` text DEFAULT NULL,
      `maximumAL` tinyint(3) DEFAULT NULL,
      `lastUpdated` date DEFAULT NULL,
      `lastValidated` datetime DEFAULT NULL,
      `user_id` int(10) unsigned DEFAULT NULL,
      `sharedIdp` tinyint(3) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `OrganizationInfo_id` (`OrganizationInfo_id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `IMPS_ibfk_1` FOREIGN KEY (`OrganizationInfo_id`) REFERENCES `OrganizationInfo` (`id`) ON DELETE CASCADE,
      CONSTRAINT `IMPS_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `IdpIMPS` (
      `entity_id` int(10) unsigned NOT NULL,
      `IMPS_id` int(10) unsigned NOT NULL,
      PRIMARY KEY (`entity_id`,`IMPS_id`),
      KEY `IMPS_id` (`IMPS_id`),
      CONSTRAINT `IdpIMPS_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE,
      CONSTRAINT `IdpIMPS_ibfk_2` FOREIGN KEY (`IMPS_id`) REFERENCES `IMPS` (`id`) ON DELETE CASCADE
    );');

    $this->db->query("CREATE TABLE `KeyInfo` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `entity_id` int(10) unsigned NOT NULL,
      `type` enum('SPSSO','IDPSSO','AttributeAuthority') DEFAULT NULL,
      `use` enum('both','signing','encryption') DEFAULT NULL,
      `order` tinyint(3) unsigned DEFAULT NULL,
      `name` varchar(256) DEFAULT NULL,
      `notValidAfter` datetime DEFAULT NULL,
      `subject` varchar(256) DEFAULT NULL,
      `issuer` varchar(256) DEFAULT NULL,
      `bits` smallint(5) unsigned DEFAULT NULL,
      `key_type` varchar(256) DEFAULT NULL,
      `serialNumber` varchar(44) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `entity_id` (`entity_id`),
      CONSTRAINT `KeyInfo_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );");

    $this->db->query('CREATE TABLE `MailReminders` (
      `entity_id` int(10) unsigned NOT NULL,
      `type` tinyint(3) unsigned NOT NULL,
      `level` tinyint(3) unsigned DEFAULT NULL,
      `mailDate` datetime DEFAULT NULL,
      PRIMARY KEY (`entity_id`,`type`),
      CONSTRAINT `MailReminders_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );');

    $this->db->query("CREATE TABLE `Mdui` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `entity_id` int(10) unsigned NOT NULL,
      `type` enum('SPSSO','IDPSSO','IDPDisco') DEFAULT NULL,
      `lang` char(10) DEFAULT NULL,
      `height` smallint(5) unsigned DEFAULT NULL,
      `width` smallint(5) unsigned DEFAULT NULL,
      `element` varchar(25) DEFAULT NULL,
      `data` text DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `entity_id` (`entity_id`),
      CONSTRAINT `Mdui_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );");

    $this->db->query('CREATE TABLE `Organization` (
      `entity_id` int(10) unsigned NOT NULL,
      `lang` char(10) NOT NULL,
      `element` varchar(25) NOT NULL,
      `data` text DEFAULT NULL,
      PRIMARY KEY (`entity_id`,`lang`,`element`),
      CONSTRAINT `Organization_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `OrganizationInfoData` (
        `OrganizationInfo_id` int(10) unsigned DEFAULT 0,
        `lang` char(10) DEFAULT NULL,
        `OrganizationName` text DEFAULT NULL,
        `OrganizationDisplayName` text DEFAULT NULL,
        `OrganizationURL` text DEFAULT NULL,
        CONSTRAINT `OrganizationInfoData_ibfk_1` FOREIGN KEY (`OrganizationInfo_id`) REFERENCES `OrganizationInfo` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `Scopes` (
      `entity_id` int(10) unsigned NOT NULL,
      `scope` varchar(256) NOT NULL,
      `regexp` tinyint(3) unsigned DEFAULT NULL,
      PRIMARY KEY (`entity_id`,`scope`),
      CONSTRAINT `Scopes_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `TestResults` (
      `entityID` varchar(256) NOT NULL,
      `test` varchar(20) NOT NULL,
      `time` datetime DEFAULT NULL,
      `result` varchar(70) DEFAULT NULL,
      PRIMARY KEY (`entityID`,`test`)
    );');

    $this->db->query('CREATE TABLE `URLs` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `URL` text DEFAULT NULL,
      `type` tinyint(3) unsigned DEFAULT NULL,
      `status` tinyint(3) unsigned DEFAULT NULL,
      `cocov1Status` tinyint(3) unsigned DEFAULT NULL,
      `height` smallint(5) unsigned DEFAULT NULL,
      `width` smallint(5) unsigned DEFAULT NULL,
      `nosize` tinyint(3) unsigned DEFAULT NULL,
      `lastSeen` datetime DEFAULT NULL,
      `lastValidated` datetime DEFAULT NULL,
      `validationOutput` text DEFAULT NULL,
      PRIMARY KEY (`id`)
    );');

    $this->db->query('CREATE TABLE `assuranceLog` (
      `entityID` varchar(256) NOT NULL,
      `assurance` varchar(10) NOT NULL,
      `logDate` date DEFAULT NULL,
      PRIMARY KEY (`entityID`,`assurance`)
    );');

    $this->db->query('CREATE TABLE `params` (
      `id` varchar(20) DEFAULT NULL,
      `value` text DEFAULT NULL
    );');
    $this->db->query("INSERT INTO `params` (`id`, `value`) VALUES ('dbVersion', '3');");

    $this->db->query('CREATE TABLE `DiscoveryResponse` (
      `entity_id` int(10) unsigned NOT NULL,
      `index` smallint(5) unsigned NOT NULL,
      `location` text DEFAULT NULL,
      PRIMARY KEY (`entity_id`,`index`),
      CONSTRAINT `DiscoveryResponse_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );');

    $this->db->query('CREATE TABLE `ServiceInfo` (
      `entity_id` int(10) unsigned NOT NULL,
      `ServiceURL` text NOT NULL,
      `enabled` tinyint(3) unsigned DEFAULT NULL,
      PRIMARY KEY (`entity_id`),
      CONSTRAINT `ServiceInfo_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
    );');
  }

  /**
   * Return smtp config
   *
   * Return an array with the smtp configuration
   *
   * @return array
   */
  public function getSMTP() {
    return $this->smtp;
  }

  /**
   * Return base URL
   *
   * Return a string with the base URL
   *
   * @return string
   */
  public function baseURL() {
    return $this->baseURL;
  }

  /**
   * Return entitySelectionProfiles
   *
   * Return an array with entitySelectionProfiles
   *
   * @return array
   */
  public function entitySelectionProfiles() {
    return $this->entitySelectionProfiles;
  }

  /**
   * Return smtpAuth
   *
   * Return if we should use SMTPAUTH or not
   *
   * @return bool
   */
  public function smtpAuth() {
    return $this->smtpAuth;
  }

  /**
   * Return sendOut
   *
   * Return if we should send out mails or not
   *
   * @return bool
   */
  public function sendOut() {
    return $this->smtp['sendOut'];
  }

  /**
   * Return database
   *
   * Return a database pointer
   *
   * @return PDO
   */
  public function getDb() {
    return $this->db;
  }

  /**
   * Return Userlevels
   *
   * Return an array with usres and their userlevel
   *
   * @return array
   */
  public function getUserLevels() {
    return $this->userLevels;
  }

  /**
   * Return federation
   *
   * Return an array with the federation configuration
   *
   * @return array
   */
  public function getFederation() {
    return $this->federation;
  }

  /**
   * Return IMPS configuration
   *
   * Return an array with configuration values for IMPS
   *
   */
  public function getIMPS() {
    return $this->imps;
  }

  /**
   * Return mode of application
   */
  public function getMode() {
    return $this->mode;
  }
}
