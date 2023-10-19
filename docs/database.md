# Database

## Entities

Field | Type | Usage
------|------| -----
id | INT UNSIGNED | id number in the database for this Entity
entityID | VARCHAR(256) | EntityID of the Entity
registrationInstant | VARCHAR(256) |
isIdP | TINYINT UNSIGNED | 0 = not IdP, 1 = Entity is an IdP
isSP | TINYINT UNSIGNED | 0 = not SP, 1 = Entity is an SP
publishIn | TINYINT UNSIGNED | Where this Entity is/should be published ( #1 = Testing, #2 = SWAMID, #3 = EduGAIN, #4 = QA)
status | TINYINT UNSIGNED | Status of Entity (1 = Published, 2 = Pending, 3 = Draft, 4 = Deleted (Soft), 5 = Pending that have been published, 6 = Shadow (linked from Pending to see status when added to pending queue))
ALlevel | TINYINT UNSIGNED | Maximun AL level.
lastUpdated | DATETIME | Last time the Entity was imorted (new) or updated in GIT (prod)
lastValidated | DATETIME | Last time the Entity was validated
validationOutput | TEXT | Info from validation
warnings | TEXT | Warnings
errors | TEXT | Errors
errorsNB | TEXT | Nonbreaking Errorª‹
xml | TEXT | The XML for the Entity

## EntityAttributes

Field | Type | Usage
------|------| -----
entity_id | INT UNSIGNED | Foreign key (id) from Entities table
type | VARCHAR(30) | ex entity-category, assurance-certification, entity-category-support or subject-id:req
attribute | VARCHAR(256) | Value of type (ex 'http://www.swamid.se/policy/assurance/al1' or 'https://refeds.org/category/personalized')

## Scopes

Field | Type | Usage
------|------| -----
entity_id | INT | UNSIGNED | Foreign key (id) from Entities table
scope | VARCHAR(256) | Scope for IdP
regexp | TINYINT UNSIGNED | 1 if Scope is an regexp

## Mdui

Field | Type | Usage
------|------| -----
entity_id | INT UNSIGNED | Foreign key (id) from Entities table
type | ENUM | One of SPSSO, IDPSSO or IDPDisco
lang | CHAR(2) | Language code
height | SMALLINT | Used only for Logo
width | SMALLINT | Used only for Logo
element | VARCHAR(25) | ex DisplayNam, Decription or IPHint
data | TEXT |Value of the Element

## KeyInfo

Field | Type | Usage
------|------| -----
entity_id | INT UNSIGNED | Foreign key (id) from Entities table
type | ENUM('SPSSO', 'IDPSSO', 'AttributeAuthority'),
use | ENUM('both', 'signing', 'encryption'),
name | VARCHAR(256) | Name of Key
notValidAfter | DATETIME | Not Valid After
subject | VARCHAR(256) | Subject of certificate
issuer | VARCHAR(256) | Issuer of certificate
bits | SMALLINT UNSIGNED | Number of bits in key
key_type | VARCHAR(256) | Type of Key
hash | VARCHAR(8) | Hash of Key
serialNumber | VARCHAR(44) | Serialnumer of certificate

## AttributeConsumingService

Field | Type | Usage
------|------| -----
entity_id | INT UNSIGNED | Foreign key (id) from Entities table
Service_index | SMALLINT UNSIGNED | Index of AttributeConsumingService
isDefault | TINYINT UNSIGNED | If this is the default AttributeConsumingService

## AttributeConsumingService_Service

Field | Type | Usage
------|------| -----
entity_id | INT UNSIGNED | Foreign key (id) from Entities table
Service_index | SMALLINT UNSIGNED | Index of AttributeConsumingService
element | VARCHAR(20) | ServiceName or ServiceDescription
lang | CHAR(2) | Language code
data | TEXT | Value of element

## AttributeConsumingService_RequestedAttribute

Field | Type | Usage
------|------| -----
entity_id | INT UNSIGNED | Foreign key (id) from Entities table
Service_index | SMALLINT UNSIGNED | Index of AttributeConsumingService
FriendlyName | VARCHAR(256) | FriendlyName
Name | VARCHAR(256) | Name
NameFormat | VARCHAR(256) | NameFormat
isRequired | TINYINT UNSIGNED | If this attribute is flagged as Required

## Organization

Field | Type | Usage
------|------| -----
entity_id | INT UNSIGNED | Foreign key (id) from Entities table
lang | CHAR(2) | Language code
element | VARCHAR(25) |
data | TEXT | Value of element

## ContactPerson

Field | Type | Usage
------|------| -----
entity_id | INT UNSIGNED | Foreign key (id) from Entities table
contactType | ENUM | One of technical, support, administrative, billing or other
extensions | VARCHAR(256) | 
company | VARCHAR(256) | 
givenName | VARCHAR(256) | 
surName | VARCHAR(256) | 
emailAddress | VARCHAR(256) | 
telephoneNumber | VARCHAR(256) | 
subcontactType | VARCHAR(256) | 

## EntityURLs

Field | Type | Usage
------|------| -----
entity_id | INT UNSIGNED | Foreign key (id) from Entities table
URL | TEXT | 
type | VARCHAR(20) | 

## URLs

Field | Type | Usage
------|------| -----
URL | TEXT | 
type | TINYINT UNSIGNED | 
status | TINYINT UNSIGNED | 
lastSeen | DATETIME | 
lastValidated | DATETIME | 
validationOutput | TEXT | 

## Users

Field | Type | Usage
------|------| -----
entity_id | INT UNSIGNED | Foreign key (id) from Entities table
userID | TEXT | ID (EPPN) of the User responsible for this Entity
email | TEXT | Mail address of the User responsible for this Entity

## TestResults

Field | Type | Usage
------|------| -----
entityID | VARCHAR(256) | 
test | VARCHAR(20) | 
time | DATETIME | 
result | VARCHAR(70) | 

## MailReminders
Field | Type | Usage
------|------| -----
entity_id | int(10) | Foreign key (id) from Entities table
type | tinyint(3) | Type of reminder ( 1 = Annual Confirmation)
days | smallint(4) |