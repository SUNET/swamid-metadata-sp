CREATE TABLE `AccessRequests` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `hash` varchar(32) DEFAULT NULL,
  `requestDate` datetime DEFAULT NULL,
  UNIQUE KEY `entity_id_user_id` (`entity_id`,`user_id`),
  KEY `AccessRequests_FK_user_id` (`user_id`),
  CONSTRAINT `AccessRequests_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `AccessRequests_FK_user_id` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `AttributeConsumingService` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `Service_index` smallint(5) unsigned DEFAULT NULL,
  `isDefault` tinyint(3) unsigned DEFAULT NULL,
  KEY `AttributeConsumingService_FK_entity_id` (`entity_id`),
  CONSTRAINT `AttributeConsumingService_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `AttributeConsumingService_RequestedAttribute` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `Service_index` smallint(5) unsigned DEFAULT NULL,
  `FriendlyName` varchar(256) DEFAULT NULL,
  `Name` varchar(256) DEFAULT NULL,
  `NameFormat` varchar(256) DEFAULT NULL,
  `isRequired` tinyint(3) unsigned DEFAULT NULL,
  KEY `AttributeConsumingService_RequestedAttribute_FK_entity_id` (`entity_id`),
  CONSTRAINT `AttributeConsumingService_RequestedAttribute_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `AttributeConsumingService_Service` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `Service_index` smallint(5) unsigned DEFAULT NULL,
  `element` varchar(20) DEFAULT NULL,
  `lang` char(10) DEFAULT NULL,
  `data` text DEFAULT NULL,
  KEY `AttributeConsumingService_Service_FK_entity_id` (`entity_id`),
  CONSTRAINT `AttributeConsumingService_Service_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `ContactPerson` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `contactType` enum('technical','support','administrative','billing','other') DEFAULT NULL,
  `extensions` varchar(256) DEFAULT NULL,
  `company` varchar(256) DEFAULT NULL,
  `givenName` varchar(256) DEFAULT NULL,
  `surName` varchar(256) DEFAULT NULL,
  `emailAddress` varchar(256) DEFAULT NULL,
  `telephoneNumber` varchar(256) DEFAULT NULL,
  `subcontactType` varchar(256) DEFAULT NULL,
  KEY `ContactPerson_FK_entity_id` (`entity_id`),
  CONSTRAINT `ContactPerson_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
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
  `date` datetime DEFAULT NULL,
  `NrOfEntites` int(10) unsigned DEFAULT NULL,
  `NrOfSPs` int(10) unsigned DEFAULT NULL,
  `NrOfIdPs` int(10) unsigned DEFAULT NULL
);

CREATE TABLE `EntitiesStatus` (
  `date` datetime DEFAULT NULL,
  `ErrorsTotal` int(10) unsigned DEFAULT NULL,
  `ErrorsSPs` int(10) unsigned DEFAULT NULL,
  `ErrorsIdPs` int(10) unsigned DEFAULT NULL,
  `NrOfEntites` int(10) unsigned DEFAULT NULL,
  `NrOfSPs` int(10) unsigned DEFAULT NULL,
  `NrOfIdPs` int(10) unsigned DEFAULT NULL,
  `Changed` int(10) unsigned DEFAULT NULL
);

CREATE TABLE `EntityAttributes` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(30) DEFAULT NULL,
  `attribute` varchar(256) DEFAULT NULL,
  KEY `EntityAttributes_FK_entity_id` (`entity_id`),
  CONSTRAINT `EntityAttributes_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `EntityConfirmation` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `lastConfirmed` datetime DEFAULT NULL,
  UNIQUE KEY `entity_id` (`entity_id`),
  KEY `EntityConfirmation_FK_user_id` (`user_id`),
  CONSTRAINT `EntityConfirmation_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `EntityConfirmation_FK_user_id` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `EntityURLs` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `URL` text DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  UNIQUE KEY `entity_id_type` (`entity_id`,`type`),
  CONSTRAINT `EntityURLs_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `EntityUser` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `approvedBy` text DEFAULT NULL,
  `lastChanged` datetime DEFAULT NULL,
  UNIQUE KEY `entity_id_user_id` (`entity_id`,`user_id`),
  KEY `EntityUser_FK_user_id` (`user_id`),
  CONSTRAINT `EntityUser_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `EntityUser_FK_user_id` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `ExternalEntities` (
  `entityID` varchar(256) DEFAULT NULL,
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
  `ra` text DEFAULT NULL
);

CREATE TABLE `KeyInfo` (
  `entity_id` int(10) unsigned DEFAULT NULL,
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
  KEY `KeyInfo_FK_entity_id` (`entity_id`),
  CONSTRAINT `KeyInfo_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `MailReminders` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `type` tinyint(3) unsigned DEFAULT NULL,
  `level` tinyint(3) unsigned DEFAULT NULL,
  `mailDate` datetime DEFAULT NULL,
  UNIQUE KEY `entity_id_type` (`entity_id`,`type`),
  CONSTRAINT `MailReminders_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `Mdui` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `type` enum('SPSSO','IDPSSO','IDPDisco') DEFAULT NULL,
  `lang` char(10) DEFAULT NULL,
  `height` smallint(6) DEFAULT NULL,
  `width` smallint(6) DEFAULT NULL,
  `element` varchar(25) DEFAULT NULL,
  `data` text DEFAULT NULL,
  KEY `Mdui_FK_entity_id` (`entity_id`),
  CONSTRAINT `Mdui_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `Organization` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `lang` char(10) DEFAULT NULL,
  `element` varchar(25) DEFAULT NULL,
  `data` text DEFAULT NULL,
  KEY `Organization_FK_entity_id` (`entity_id`),
  CONSTRAINT `Organization_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `Scopes` (
  `entity_id` int(10) unsigned DEFAULT NULL,
  `scope` varchar(256) DEFAULT NULL,
  `regexp` tinyint(3) unsigned DEFAULT NULL,
  KEY `Scopes_FK_entity_id` (`entity_id`),
  CONSTRAINT `Scopes_FK_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `Entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `TestResults` (
  `entityID` varchar(256) DEFAULT NULL,
  `test` varchar(20) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `result` varchar(70) DEFAULT NULL,
  UNIQUE KEY `entityID_test` (`entityID`,`test`)
);

CREATE TABLE `URLs` (
  `URL` text DEFAULT NULL,
  `type` tinyint(3) unsigned DEFAULT NULL,
  `status` tinyint(3) unsigned DEFAULT NULL,
  `cocov1Status` tinyint(3) unsigned DEFAULT NULL,
  `lastSeen` datetime DEFAULT NULL,
  `lastValidated` datetime DEFAULT NULL,
  `validationOutput` text DEFAULT NULL
);

CREATE TABLE `Users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userID` text DEFAULT NULL,
  `email` text DEFAULT NULL,
  `fullName` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userID` (`userID`) USING HASH
);

CREATE TABLE `assuranceLog` (
  `entityID` varchar(256) DEFAULT NULL,
  `assurance` varchar(10) DEFAULT NULL,
  `logDate` date DEFAULT curdate(),
  UNIQUE KEY `entityID_assurance` (`entityID`,`assurance`) USING HASH
);
