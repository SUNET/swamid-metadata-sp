CREATE TABLE Entities (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`entityID` VARCHAR(256), 
	`registrationInstant` VARCHAR(256),
	`isIdP` TINYINT UNSIGNED,
	`isSP` TINYINT UNSIGNED,
	`publishIn` TINYINT UNSIGNED,
	`status` TINYINT UNSIGNED,
	`ALlevel` TINYINT UNSIGNED,
	`lastUpdated` DATETIME,
	`lastValidated` DATETIME,
	`validationOutput` TEXT,
	`warnings` TEXT,
	`errors` TEXT,
	`xml` TEXT);

CREATE TABLE EntityAttributes (
	`entity_id` INT UNSIGNED,
	`type` VARCHAR(30),
	`attribute` VARCHAR(256));

CREATE TABLE Scopes (
	`entity_id` INT UNSIGNED,
	`scope` VARCHAR(256),
	`regexp` TINYINT UNSIGNED);

CREATE TABLE Mdui (
	`entity_id` INT UNSIGNED,
	`type` ENUM('SPSSO', 'IDPSSO', 'IDPDisco'),
	`lang` CHAR(2),
	`height` SMALLINT,
	`width` SMALLINT,
	`element` VARCHAR(25),
	`data` TEXT);

CREATE TABLE KeyInfo (
	`entity_id` INT UNSIGNED,
	`type` ENUM('SPSSO', 'IDPSSO', 'AttributeAuthority'),
	`use` ENUM('both', 'signing', 'encryption'),
	`name` VARCHAR(256),
	`notValidAfter` DATETIME,
	`subject` VARCHAR(256),
	`issuer` VARCHAR(256),
	`bits` SMALLINT UNSIGNED,
	`key_type` VARCHAR(256),
	`hash` VARCHAR(8));

CREATE TABLE AttributeConsumingService (
	`entity_id` INT UNSIGNED,
	`Service_index` SMALLINT UNSIGNED,
	`isDefault` TINYINT UNSIGNED);

CREATE TABLE AttributeConsumingService_Service (
	`entity_id` INT UNSIGNED,
	`Service_index` SMALLINT UNSIGNED,
	`element` VARCHAR(20),
	`lang` CHAR(2),
	`data` TEXT);

CREATE TABLE AttributeConsumingService_RequestedAttribute (
	`entity_id` INT UNSIGNED,
	`Service_index` SMALLINT UNSIGNED,
	`FriendlyName` VARCHAR(256),
	`Name` VARCHAR(256),
	`NameFormat` VARCHAR(256),
	`isRequired` TINYINT UNSIGNED);

CREATE TABLE Organization (
	`entity_id` INT UNSIGNED,
	`lang` CHAR(2),
	`element` VARCHAR(25),
	`data` TEXT);

CREATE TABLE ContactPerson (
	`entity_id` INT UNSIGNED,
	`contactType` ENUM('technical', 'support', 'administrative', 'billing', 'other'),
	`extensions` VARCHAR(256),
	`company` VARCHAR(256),
	`givenName` VARCHAR(256),
	`surName` VARCHAR(256),
	`emailAddress` VARCHAR(256),
	`telephoneNumber` VARCHAR(256),
	`subcontactType` VARCHAR(256));

CREATE TABLE EntityURLs (
	`entity_id` INT UNSIGNED,
	`URL` TEXT,
	`type` VARCHAR(20),
	UNIQUE(entity_id, type)
);

CREATE TABLE URLs (
	`URL` TEXT,
	`type` TINYINT UNSIGNED,
	`status` TINYINT UNSIGNED,
	`lastSeen` DATETIME,
	`lastValidated` DATETIME,
	`validationOutput` TEXT);

CREATE TABLE Users (
	`entity_id` INT UNSIGNED,
	`userID` TEXT,
	`email` TEXT,
	UNIQUE(entity_id, userID));