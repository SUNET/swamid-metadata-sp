<?php
namespace metadata;

use PDO;
use PDOException;

class Configuration {
  private $smtpAuth = false; // Used while sending out in PHPMailer
  private $smtp = false;
  private $imps = false;
  private $mode = 'Lab';
  private $baseURL = '';
  private $entitySelectionProfiles = array();
  private $db;
  private $userLevels = array(); // indexed by username, maps to user privilege level
  private $federation = array(); // hash of federation parameters

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
    $reqParamsFederation = array('displayName', 'displayNameQA', 'name', 'aboutURL', 'contactURL', 'logoURL', 'logoWidth', 'logoHeight');

    foreach ($reqParams as $param) {
      if (! isset(${$param})) {
        printf ('Missing %s in config.php<br>', $param);
        exit;
      }
    }

    foreach ($reqParamsDB as $param) {
      if (! isset($db[$param])) {
        printf ('Missing $db[%s] in config.php<br>', $param);
        exit;
      }
    }

    foreach ($reqParamsSmtp as $param) {
      if (! isset($smtp[$param])) {
        printf ('Missing $smtp[%s] in config.php<br>', $param);
        exit;
      }
    }

    foreach ($reqParamsFederation as $param) {
      if (! isset($federation[$param])) {
        printf ('Missing $federation[%s] in config.php<br>', $param);
        exit;
      }
    }

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
      foreach ($reqParamsSmtpSasl as $param) {
        if (! isset($smtp['sasl'][$param])) {
          printf ('Missing $smtp[sasl][%s] in config.php<br>', $param);
          exit;
        }
      }
    } else {
      # Default to port 25
      # Overrided by port in $smtp in config
      $this->smtp['port'] = 25;
    }
    if (isset($smtp['port'])) {
      $this->smtp['port'] = $smtp['port'];
    }
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
      $this->imps = $imps;
      foreach (array('oldDate', 'warn1', 'warn2', 'error') as $param) {
        if (! isset($imps[$param])) {
          printf ('Missing $imps[%s] in config.php<br>', $param);
          exit;
        }
      }
    }
  }

  private function checkDBVersion() {
    /*$dbVersionHandler = $this->db->query("SELECT value FROM params WHERE `id` = 'dbVersion'");
    if (! $dbVersion = $dbVersionHandler->fetch(PDO::FETCH_ASSOC)) {
      $dbVersion = 0;
    } else {
      $dbVersion=$dbVersion['value'];
    }
    if ($dbVersion < 4) {
      if ($dbVersion < 1) {
        $this->db->query("INSERT INTO params (`instance`, `id`, `value`) VALUES ('', 'dbVersion', 1)");
      }
      if ($dbVersion < 2 && $this->db->query(
        "ALTER TABLE invites ADD
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY
          FIRST")) {
        # 1 Update went well. Do the rest
        $this->db->query(
          "ALTER TABLE invites ADD
            `status` tinyint unsigned
          AFTER `hash`");
        $this->db->query(
          "ALTER TABLE invites ADD
            `inviteInfo` text DEFAULT NULL
          AFTER `attributes`");
        $this->db->query(
          "ALTER TABLE invites ADD
            `migrateInfo` text DEFAULT NULL
          AFTER `inviteInfo`");
        $this->db->query(
          "ALTER TABLE invites MODIFY COLUMN
            `hash` varchar(65) DEFAULT NULL");
        $this->db->query("UPDATE params SET value = 2 WHERE `instance`='' AND `id`='dbVersion'");
      }
      if ($dbVersion < 3) {
        # To ver 3
        $this->db->query('CREATE TABLE `instances` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `instance` varchar(30) DEFAULT NULL)');
        $this->db->query("INSERT INTO `instances` (`id`, `instance`) VALUES (1, 'Admin')");
        $this->db->query('CREATE TABLE `users` (
          `instance_id` int(10) unsigned NOT NULL,
          `ePPN` varchar(40) DEFAULT NULL,
          CONSTRAINT `users_ibfk_1` FOREIGN KEY (`instance_id`) REFERENCES `instances` (`id`) ON DELETE CASCADE)');
        $this->db->query('DELETE FROM `invites`');
        $this->db->query('ALTER TABLE `invites`
          CHANGE `instance` `instance_id` int(10) unsigned NOT NUM@tilda
          LL,
          ADD `lang` varchar(2) DEFAULT NULL,
          ADD FOREIGN KEY (`instance_id`)
            REFERENCES `instances` (`id`)
            ON DELETE CASCADE');
        $this->db->query("DELETE FROM `params` WHERE `id` = 'token'");
        $this->db->query("UPDATE params SET value = 3, `instance` = '1' WHERE `instance` = '' AND `id` = 'dbVersion'");
        $this->db->query('ALTER TABLE `params`
          CHANGE `instance` `instance_id` int(10) unsigned NOT NULL,
          ADD FOREIGN KEY (`instance_id`)
            REFERENCES `instances` (`id`)
            ON DELETE CASCADE');
      }
      $this->db->query('ALTER TABLE `users`
        ADD `externalId` text DEFAULT NULL,
        ADD `name` text DEFAULT NULL,
        ADD `scimId` varchar(40) DEFAULT NULL,
        ADD `personNIN` varchar(12) DEFAULT NULL,
        ADD `lastSeen` datetime DEFAULT NULL,
        ADD `status` tinyint');
      $this->db->query('DELETE FROM `users`');
      $this->db->query("UPDATE params SET value = 4 WHERE `instance_id` = 1 AND `id` = 'dbVersion'");
    }*/
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

  public function baseURL() {
    return $this->baseURL;
  }

  public function entitySelectionProfiles() {
    return $this->entitySelectionProfiles;
  }

  public function smtpAuth() {
    return $this->smtpAuth;
  }

  public function sendOut() {
    return $this->smtp['sendOut'];
  }

  public function getDb() {
    return $this->db;
  }

  public function getUserLevels() {
    return $this->userLevels;
  }

  public function getFederation() {
    return $this->federation;
  }

  public function getIMPS() {
    return $this->imps;
  }

  public function getMode() {
    return $this->mode;
  }
}
