CREATE TABLE `AccessRequests` (
  `entity_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `hash` varchar(32) DEFAULT NULL,
  `requestDate` datetime DEFAULT NULL,
  PRIMARY KEY (`entity_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `AccessRequests_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `AccessRequests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE
);

CREATE TABLE `AttributeConsumingService` (
  `entity_id` int(10) unsigned NOT NULL,
  `Service_index` smallint(5) unsigned NOT NULL,
  `isDefault` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`entity_id`,`Service_index`),
  CONSTRAINT `AttributeConsumingService_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
);

CREATE TABLE `AttributeConsumingService_RequestedAttribute` (
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
);

CREATE TABLE `AttributeConsumingService_Service` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) unsigned NOT NULL,
  `Service_index` smallint(5) unsigned NOT NULL,
  `element` varchar(20) DEFAULT NULL,
  `lang` char(10) DEFAULT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entity_id` (`entity_id`),
  CONSTRAINT `AttributeConsumingService_Service_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
);

CREATE TABLE `ContactPerson` (
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
);

CREATE TABLE `Entities` (
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
  `validationOutput` text DEFAULT NULL,
  `warnings` text DEFAULT NULL,
  `errors` text DEFAULT NULL,
  `errorsNB` text DEFAULT NULL,
  `xml` text DEFAULT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `EntitiesStatistics` (
  `date` datetime NOT NULL,
  `NrOfEntites` int(10) unsigned DEFAULT NULL,
  `NrOfSPs` int(10) unsigned DEFAULT NULL,
  `NrOfIdPs` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`date`)
);

CREATE TABLE `EntitiesStatus` (
  `date` datetime NOT NULL,
  `ErrorsTotal` int(10) unsigned DEFAULT NULL,
  `ErrorsSPs` int(10) unsigned DEFAULT NULL,
  `ErrorsIdPs` int(10) unsigned DEFAULT NULL,
  `NrOfEntites` int(10) unsigned DEFAULT NULL,
  `NrOfSPs` int(10) unsigned DEFAULT NULL,
  `NrOfIdPs` int(10) unsigned DEFAULT NULL,
  `Changed` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`date`)
);

CREATE TABLE `EntityAttributes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) unsigned NOT NULL,
  `type` varchar(30) DEFAULT NULL,
  `attribute` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entity_id` (`entity_id`),
  CONSTRAINT `EntityAttributes_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
);

CREATE TABLE `EntityConfirmation` (
  `entity_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `lastConfirmed` datetime DEFAULT NULL,
  PRIMARY KEY (`entity_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `EntityConfirmation_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `EntityConfirmation_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE
);

CREATE TABLE `EntityURLs` (
  `entity_id` int(10) unsigned NOT NULL,
  `URL` text DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  PRIMARY KEY (`entity_id`,`type`),
  CONSTRAINT `EntityURLs_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
);

CREATE TABLE `EntityUser` (
  `entity_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `approvedBy` text DEFAULT NULL,
  `lastChanged` datetime DEFAULT NULL,
  PRIMARY KEY (`entity_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `EntityUser_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `EntityUser_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE
);

CREATE TABLE `ExternalEntities` (
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
);

CREATE TABLE `IMPS` (
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
);

CREATE TABLE `IdpIMPS` (
  `entity_id` int(10) unsigned NOT NULL,
  `IMPS_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`entity_id`,`IMPS_id`),
  KEY `IMPS_id` (`IMPS_id`),
  CONSTRAINT `IdpIMPS_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `IdpIMPS_ibfk_2` FOREIGN KEY (`IMPS_id`) REFERENCES `IMPS` (`id`) ON DELETE CASCADE
);

CREATE TABLE `KeyInfo` (
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
);

CREATE TABLE `MailReminders` (
  `entity_id` int(10) unsigned NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `level` tinyint(3) unsigned DEFAULT NULL,
  `mailDate` datetime DEFAULT NULL,
  PRIMARY KEY (`entity_id`,`type`),
  CONSTRAINT `MailReminders_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
);

CREATE TABLE `Mdui` (
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
);

CREATE TABLE `Organization` (
  `entity_id` int(10) unsigned NOT NULL,
  `lang` char(10) NOT NULL,
  `element` varchar(25) NOT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`entity_id`,`lang`,`element`),
  CONSTRAINT `Organization_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
);

CREATE TABLE `OrganizationInfo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `OrganizationNameSv` text DEFAULT NULL,
  `OrganizationDisplayNameSv` text DEFAULT NULL,
  `OrganizationURLSv` text DEFAULT NULL,
  `OrganizationNameEn` text DEFAULT NULL,
  `OrganizationDisplayNameEn` text DEFAULT NULL,
  `OrganizationURLEn` text DEFAULT NULL,
  `memberSince` date DEFAULT NULL,
  `notMemberAfter` date DEFAULT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `Scopes` (
  `entity_id` int(10) unsigned NOT NULL,
  `scope` varchar(256) NOT NULL,
  `regexp` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`entity_id`,`scope`),
  CONSTRAINT `Scopes_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE
);

CREATE TABLE `TestResults` (
  `entityID` varchar(256) NOT NULL,
  `test` varchar(20) NOT NULL,
  `time` datetime DEFAULT NULL,
  `result` varchar(70) DEFAULT NULL,
  PRIMARY KEY (`entityID`,`test`)
);

CREATE TABLE `URLs` (
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
);

CREATE TABLE `Users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userID` text DEFAULT NULL,
  `email` text DEFAULT NULL,
  `fullName` text DEFAULT NULL,
  `lastSeen` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userID` (`userID`) USING HASH
);

CREATE TABLE `assuranceLog` (
  `entityID` varchar(256) NOT NULL,
  `assurance` varchar(10) NOT NULL,
  `logDate` date DEFAULT NULL,
  PRIMARY KEY (`entityID`,`assurance`)
);


