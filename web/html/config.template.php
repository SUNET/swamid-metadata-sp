<?php
$dbServername = "mariadb";
$dbUsername = "metdata_admin";
$dbPassword = "adminpwd";
$dbName = "metadata";

$SMTPHost = 'smtp.host.se';
$SASLUser = 'SASL User';
$SASLPassword = 'SASL Password';
$MailFrom = 'Sending user/alias';
$SendOut = false;   // Switch to true to start sending mails

$standardAttributes = array(
	'assurance-certification' => array(
		array('type' => 'IdP', 'value' =>'http://www.swamid.se/policy/assurance/al1'),
		array('type' => 'IdP', 'value' =>'http://www.swamid.se/policy/assurance/al2'),
		array('type' => 'IdP', 'value' =>'http://www.swamid.se/policy/assurance/al3'),
		array('type' => 'IdP/SP', 'value' =>'https://refeds.org/sirtfi')),
	'entity-category' => array(
		array('type' => 'SP', 'value' =>'http://refeds.org/category/research-and-scholarship'),
		array('type' => 'SP', 'value' =>'http://refeds.org/category/anonymous'),
		array('type' => 'SP', 'value' =>'http://refeds.org/category/pseudonymous'),
		array('type' => 'SP', 'value' =>'http://refeds.org/category/personalized'),
		array('type' => 'SP', 'value' =>'http://www.geant.net/uri/dataprotection-code-of-conduct/v1'),
		array('type' => 'SP', 'value' =>'https://myacademicid.org/entity-categories/esi'),
		array('type' => 'IdP', 'value' =>'http://refeds.org/category/hide-from-discovery')),
	'entity-category-support' => array(
		array('type' => 'IdP', 'value' =>'http://refeds.org/category/research-and-scholarship'),
		array('type' => 'IdP', 'value' =>'http://www.geant.net/uri/dataprotection-code-of-conduct/v1')),
	'subject-id:req' => array(
		array('type' => 'SP', 'value' =>'subject-id'),
		array('type' => 'SP', 'value' =>'pairwise-id'),
		array('type' => 'SP', 'value' =>'none'),
		array('type' => 'SP', 'value' =>'any'))
);
$FriendlyNames = array(
	'urn:oid:2.5.4.6'						=> array('desc' => 'c', 'swamidStd' => true),
	'urn:oid:2.5.4.3'						=> array('desc' => 'cn', 'swamidStd' => true),
	'urn:oid:0.9.2342.19200300.100.1.43'	=> array('desc' => 'co', 'swamidStd' => true),
	'urn:oid:2.16.840.1.113730.3.1.241'		=> array('desc' => 'displayName', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.5923.1.1.1.1'		=> array('desc' => 'eduPersonAffiliation', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.5923.1.1.1.11'		=> array('desc' => 'eduPersonAssurance', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.5923.1.1.1.7'		=> array('desc'  =>'eduPersonEntitlement', 'swamidStd' => false),
	'urn:oid:1.3.6.1.4.1.5923.1.1.1.16'		=> array('desc' => 'eduPersonOrcid', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.5923.1.1.1.5'		=> array('desc' => 'eduPersonPrimaryAffiliation', 'swamidStd' => false),
	'urn:oid:1.3.6.1.4.1.5923.1.1.1.6'		=> array('desc' => 'eduPersonPrincipalName', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.5923.1.1.1.9'		=> array('desc' => 'eduPersonScopedAffiliation', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.5923.1.1.1.10'		=> array('desc' => 'eduPersonTargetedID', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.5923.1.1.1.13'		=> array('desc' => 'eduPersonUniqueID', 'swamidStd' => true),
	'urn:oid:2.16.840.1.113730.3.1.4'		=> array('desc' => 'employeeType', 'swamidStd' => false),
	'urn:oid:2.5.4.42'						=> array('desc' => 'givenName', 'swamidStd' => true),
	'urn:oid:0.9.2342.19200300.100.1.10'	=> array('desc' => 'manager', 'swamidStd' => false),
	'urn:oid:0.9.2342.19200300.100.1.3'		=> array('desc' => 'mail', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.2428.90.1.6'		=> array('desc' => 'norEduOrgAcronym', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.2428.90.1.5'		=> array('desc' => 'norEduPersonNIN', 'swamidStd' => true),
	'urn:oid:2.5.4.10'						=> array('desc' => 'o', 'swamidStd' => true),
	'urn:oid:1.2.752.29.4.13'				=> array('desc' => 'personalIdentityNumber', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.25178.1.2.3'		=> array('desc' => 'schacDateOfBirth', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.25178.1.2.9'		=> array('desc' => 'schacHomeOrganization', 'swamidStd' => true),
	'urn:oid:1.3.6.1.4.1.25178.1.2.10'		=> array('desc' => 'schacHomeOrganizationType', 'swamidStd' => true),
	'urn:oid:2.5.4.4'						=> array('desc' => 'sn', 'swamidStd' => true),
	'urn:oid:0.9.2342.19200300.100.1.1'		=> array('desc' => 'uid', 'swamidStd' => false),

	'urn:mace:dir:attribute-def:cn' => array('desc'=> 'cn', 'swamidStd' => false),
	'urn:mace:dir:attribute-def:displayName' => array ('desc'=> 'displayName', 'swamidStd' => false),
	'urn:mace:dir:attribute-def:eduPersonPrincipalName' => array ('desc'=> 'eduPersonPrincipalName', 'swamidStd' => false),
	'urn:mace:dir:attribute-def:eduPersonScopedAffiliation' => array ('desc'=> 'eduPersonScopedAffiliation', 'swamidStd' => false),
	'urn:mace:dir:attribute-def:eduPersonTargetedID' => array ('desc'=> 'eduPersonTargetedID', 'swamidStd' => false),
	'urn:mace:dir:attribute-def:givenName' => array ('desc'=> 'givenName', 'swamidStd' => false),
	'urn:mace:dir:attribute-def:mail' => array ('desc'=> 'mail', 'swamidStd' => false),
	'urn:mace:dir:attribute-def:sn' => array ('desc'=> 'sn', 'swamidStd' => false),

	'urn:oid:1.2.840.113549.1.9.1.1' => array ('desc' => 'Wrong - email', 'swamidStd' => false)
);