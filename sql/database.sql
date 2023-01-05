CREATE TABLE `Entities` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`publishedId` int(10) unsigned NOT NULL DEFAULT 0,
	`entityID` varchar(256) DEFAULT NULL,
	`registrationInstant` varchar(256) DEFAULT NULL,
	`isIdP` tinyint(3) unsigned DEFAULT NULL,
	`isSP` tinyint(3) unsigned DEFAULT NULL,
	`publishIn` tinyint(3) unsigned DEFAULT NULL,
	`status` tinyint(3) unsigned DEFAULT NULL,
	`ALlevel` tinyint(3) unsigned DEFAULT NULL,
	`lastUpdated` datetime DEFAULT NULL,
	`lastValidated` datetime DEFAULT NULL,
	`validationOutput` text DEFAULT NULL,
	`warnings` text DEFAULT NULL,
	`errors` text DEFAULT NULL,
	`errorsNB` text DEFAULT NULL,
	`xml` text DEFAULT NULL);

CREATE TABLE `EntityAttributes` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`type` varchar(30) DEFAULT NULL,
	`attribute` varchar(256) DEFAULT NULL);

CREATE TABLE `Scopes` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`scope` varchar(256) DEFAULT NULL,
	`regexp` tinyint(3) unsigned DEFAULT NULL);

CREATE TABLE `Mdui` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`type` enum('SPSSO', 'IDPSSO', 'IDPDisco') DEFAULT NULL,
	`lang` char(2) DEFAULT NULL,
	`height` smallint(6) DEFAULT NULL,
	`width` smallint(6) DEFAULT NULL,
	`element` varchar(25) DEFAULT NULL,
	`data` text DEFAULT NULL);

CREATE TABLE `KeyInfo` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`type` enum('SPSSO', 'IDPSSO', 'AttributeAuthority') DEFAULT NULL,
	`use` enum('both', 'signing', 'encryption') DEFAULT NULL,
	`order` tinyint(3) unsigned DEFAULT NULL,
	`name` varchar(256) DEFAULT NULL,
	`notValidAfter` datetime DEFAULT NULL,
	`subject` varchar(256) DEFAULT NULL,
	`issuer` varchar(256) DEFAULT NULL,
	`bits` smallint(5) unsigned DEFAULT NULL,
	`key_type` varchar(256) DEFAULT NULL,
	`serialNumber` varchar(44) DEFAULT NULL);

CREATE TABLE `AttributeConsumingService` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`Service_index` smallint(5) unsigned DEFAULT NULL,
	`isDefault` tinyint(3) unsigned DEFAULT NULL);

CREATE TABLE `AttributeConsumingService_Service` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`Service_index` smallint(5) unsigned DEFAULT NULL,
	`element` varchar(20) DEFAULT NULL,
	`lang` char(2) DEFAULT NULL,
	`data` text DEFAULT NULL);

CREATE TABLE `AttributeConsumingService_RequestedAttribute` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`Service_index` smallint(5) unsigned DEFAULT NULL,
	`FriendlyName` varchar(256) DEFAULT NULL,
	`Name` varchar(256) DEFAULT NULL,
	`NameFormat` varchar(256) DEFAULT NULL,
	`isRequired` tinyint(3) unsigned DEFAULT NULL);

CREATE TABLE `Organization` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`lang` char(10) DEFAULT NULL,
	`element` varchar(25) DEFAULT NULL,
	`data` text DEFAULT NULL);

CREATE TABLE `ContactPerson` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`contactType` enum('technical', 'support', 'administrative', 'billing', 'other') DEFAULT NULL,
	`extensions` varchar(256) DEFAULT NULL,
	`company` varchar(256) DEFAULT NULL,
	`givenName` varchar(256) DEFAULT NULL,
	`surName` varchar(256) DEFAULT NULL,
	`emailAddress` varchar(256) DEFAULT NULL,
	`telephoneNumber` varchar(256) DEFAULT NULL,
	`subcontactType` varchar(256) DEFAULT NULL);

CREATE TABLE `EntityURLs` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`URL` text DEFAULT NULL,
	`type` varchar(20) DEFAULT NULL,
	UNIQUE KEY `entity_id_type` (`entity_id`,`type`));

CREATE TABLE `URLs` (
	`URL` text DEFAULT NULL,
	`type` tinyint(3) unsigned DEFAULT NULL,
	`status` tinyint(3) unsigned DEFAULT NULL,
	`cocov1Status` tinyint(3) unsigned DEFAULT NULL,
	`lastSeen` datetime DEFAULT NULL,
	`lastValidated` datetime DEFAULT NULL,
	`validationOutput` text DEFAULT NULL);

CREATE TABLE `Users` (
	`entity_id` int(10) unsigned DEFAULT NULL,
	`userID` text DEFAULT NULL,
	`email` text DEFAULT NULL,
	UNIQUE KEY `entity_id_userID` (`entity_id`,`userID`) USING HASH);

CREATE TABLE `TestResults` (
	`entityID` varchar(256) DEFAULT NULL,
	`test` varchar(20) DEFAULT NULL,
	`time` datetime DEFAULT NULL,
	`result` varchar(70) DEFAULT NULL,
	UNIQUE KEY `entityID_test` (`entityID`,`test`));

CREATE TABLE `EntitiesStatus` (
	`date` datetime DEFAULT NULL,
	`ErrorsTotal` int(10) unsigned DEFAULT NULL,
	`ErrorsSPs` int(10) unsigned DEFAULT NULL,
	`ErrorsIdPs` int(10) unsigned DEFAULT NULL,
	`NrOfEntites` int(10) unsigned DEFAULT NULL,
	`NrOfSPs` int(10) unsigned DEFAULT NULL,
	`NrOfIdPs` int(10) unsigned DEFAULT NULL,
	`Changed` int(10) unsigned DEFAULT NULL);

CREATE TABLE `EntitiesStatistics` (
	`date` datetime DEFAULT NULL,
	`NrOfEntites` int(10) unsigned DEFAULT NULL,
	`NrOfSPs` int(10) unsigned DEFAULT NULL,
	`NrOfIdPs` int(10) unsigned DEFAULT NULL);
