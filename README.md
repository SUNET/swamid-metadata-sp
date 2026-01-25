# SWAMID Metadata Tool

This is a tool for handling XML metadata for a federation.
Built for having a copy of production XML and importing each file when they are changed. In SWAMID we have all files in a GIT repository and all examples are based on this.
The users can then add/update the XML and request publication. Operations team will then verify and import into GIT-repository / or automaticaly add based on installation.

## Requirements

- Webserver with some kind of login for everything below `/admin`, only tested with Shibboleth for the moment. We use docker image created from https://github.com/SUNET/docker-swamid-metadata-sp.
- MySQL/MariaDB database. Local or on a remote server/cluster.
- composer

## Simple Setup

### DB

`docker run --net docker -v /path/to/sql:/docker-entrypoint-initdb.d -v /var/lib/mysql:/var/lib/mysql --name mariadb -e MARIADB_ROOT_PASSWORD=my-secret-pw -e MARIADB_DATABASE=metadata -e MARIADB_USER=metdata_admin -e MARIADB_PASSWORD=adminpwd -d mariadb:latest`

### Website
- `git clone https://github.com/SUNET/swamid-metadata-sp.git /opt/swamid-metadata-sp`
- `cd /opt/swamid-metadata-sp/web/html/`
- run composer one of the ways below
  - `composer install` (if installed on host)
  - done automatically on startup if using docker-swamid-metadata-sp
- `cp config.template.php config.php`

and edit `config.php` for your needs

Make sure that the following 3 files exist in /etc/sslcerts if using https://github.com/SUNET/docker-swamid-metadata-sp
- cert.pem
- privkey.pem
- chain.pem

`docker run --net docker --hostname metadata.org.edu -v '/opt/swamid-metadata-sp/web:/var/www' -v '/etc/ssl:/etc/ssl' -v '/etc/sslcerts:/etc/dehydrated' -v '/etc/shibboleth/certs:/etc/shibboleth/certs' -v '/opt/metadata:/opt/metadata' -p 443:443 -d --name metadata-sp swamid-metadata-sp:latest`

## Tooling

There is a bundle of scripts in `html/scripts`. We run those with `docker exec metadata-sp php /var/www/scripts/<scriptname>`

### importAndValidateXML.php

Parameters :
- \<xml-file\> XML-file to import
- \<feed\> one of the values in config.php-\>federation-\>localFeed or eduGAINFeed

Output:
- entityID
- Result XML Parsing
- Warnings from validation

Imports XML file, also validates XML based on rules.

### removeEntity.php

Parameters :
- \<entityID\> entityID of Entity to remove
- \<type\> Type of entity to remove. Could be Prod, Shadow or New

Remove an Entity from database. Not actualy removed but marked as *softDeleted* and hidden from display.
Actual removal is done by `cleanupDatabase.php`

### removeEntity.bash

Parameters :
- \<file\> file containg an entityID of Entity to be removed

Wrapper script that takes an file and parses out the first line for an entityID that should be removed.
Output from `importAndValidateXML.php` is perfect for this :-)

### revalidate.php

Parameters :
- \<days\> Validate all entities with lastValidation less than this number of days
- \<entities\> Max nr of entities to validate in this run

Revalidates entities on a regular basis. Goes through all rules and updates found errors.

### checkURLs.php

Revisit URL to verify that it's still exists and is reachable.
- If last vistit was successfull wait minimum 7 days before next visit
- If last vistit was unsuccessfull wait minimum 6 hours before next visit

Never check more than 100 URL:s in each run.

### cleanupPending.php

Output:
- Status if Entity was kept or (re)moved.

Removes Entities in status *Pending* if same entityID exists in production with newer timestamp AND with same XML.
Not actualy removed but marked as *PendingPublished* and hidden from display.
Actual removal is done by `cleanupDatabase.php`

### updateTestResults.php

Update test result from release-check if config.php-\>federation-\>releaseCheckResultsURL is set.

### updateExternalEntities.php

Update "external" entities from xmlfile configures in config.php-\>federation-\>metadata_main_path

Ignores Entities that have an registrationAuthority listed in config.php-\>federation-\>metadata_registration_authority_exclude

## Tooling recommed to run on a weekly or bi-weekly schedule

### checkOldURLs.php

Recheck status for old URL:s
- Remove URL:s not seen during validation of Entities for the last 30 days.
- Remove Coco v1 flag for URL:s not longer acting as PrivacyPolicyURL on a Coco v1 SP.

### cleanupDatabase.php

Cleanup database by removing
- Entities marked as *softDeleted* 3 month ago
- Entities marked as *PendingPublished* 3 month ago
- *shadow* for entities removed 4 month ago (catch all, should normaly be removed at the same time as the entity)
- *pending* entities not touched / published for 13 weeks
- *draft* entities not touched for 9 weeks
- Users not logged in for 6 months and not responsible for any Entities in the database

### sendMailReminders.php

Send out mailreminders
 - 10, 11 and 12 month after last date of validation/confirmation that the Metadata for an Entity is correct.
 - Old certs 1 month before expiration and after expiration
 - Entities in status *pending* not touched for 1, 4 and 11 weeks
 - Entities in status *draft* not touched for 2 and 7 weeks

## Tooling recommended to run on a monthly or quarterly schedule

### saveQuarterlyStats.php

Save statistics for display in *Statistics/Entity Statistics* and *Statistics/Assurance Certification* tabs.
Recomended is once per month or once per quarter to not overload the page.

## Tooling for manual handling

### handleEntityUser.php

Usage depends on number of parameters passed:
- With no parameter - List all users
- With one parameter \<user\> - List all entities the *user* has access to
- With two parameters \<user\> \<entityID\> - Add access for *user* to *entityID* with status *published*

### movePublishedPending.php

Parameter :
- \<id\> id of entity

Change status from *pending* to *PendingPublished* for an Entity in status *pending* with *id*

## Tooling for initial bootstrap

In particular when migrating from another tooling for managing a federation, the following scripts may be useful to bootstrap the Metadata Tool.

### importOrganizations.php

Import Organisations from JSON data (file or stdin) and store in the `OrganizationInfo` table.  The `--use-id` flag forces reusing the database ID (instead of having it auto-assigned by the DB).

Optional parameter: `--use-id`.

Required arguments: file name or `-` for stdin.

### assignOrganizations.php

For each published entity that is not linked to an Organisation (`OrganizationInfo` entry), try finding a corresponding entry that matches the `OrganizationName` in the entity metadata (comparing entries tagged with `en` language code) and link the entity to this entry.

Optional parameter: `--dry-run`: do not perform any changes, only show what would be done.

### importServices.php

Import service catalogue listings and attach the **service URL** found in the catalogue to each SP entity listed in the catalogue.

Required arguments: file name or `-` for stdin.
