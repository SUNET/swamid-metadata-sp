<?php
namespace metadata;

/**
 * Class to hold (and allow extending) attribute definitions
 */
class AttributeDefsTuakiri extends AttributeDefs {
  /**
   * FRIENDLY_NAMES
   *
   */
  const FRIENDLY_NAMES_TUAKIRI = array(
    // extra attribute in Tuakiri
    'urn:oid:1.3.6.1.4.1.27856.1.2.5' => array(
      'desc' => 'auEduPersonSharedToken', 'standard' => true
    ),
    'urn:oid:2.5.4.11' => array(
      'desc' => 'ou', 'standard' => false
    ),
    'urn:oid:2.5.4.16' => array(
      'desc' => 'postalAddress', 'standard' => false
    ),
    'urn:oid:2.5.4.20' => array(
      'desc' => 'telephoneNumber', 'standard' => false
    ),
    'urn:oid:0.9.2342.19200300.100.1.41' => array(
      'desc' => 'mobile', 'standard' => false
    ),

    // Tuakiri overrides
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.7' => array(
      'desc' => 'eduPersonEntitlement', 'standard' => true // accept in Tuakiri
    ),
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.5' => array(
      'desc' => 'eduPersonPrimaryAffiliation', 'standard' => true // accept in Tuakiri
    ),

    // TODO: HIDE UNWANTED SAML attrs
  );

  const FRIENDLY_NAMES_EXCLUDE = array(
    'urn:mace:dir:attribute-def:cn' => false,
    'urn:mace:dir:attribute-def:displayName' => false,
    'urn:mace:dir:attribute-def:eduPersonPrincipalName' => false,
    'urn:mace:dir:attribute-def:eduPersonScopedAffiliation' => false,
    'urn:mace:dir:attribute-def:eduPersonTargetedID' => false,
    'urn:mace:dir:attribute-def:givenName' => false,
    'urn:mace:dir:attribute-def:mail' => false,
    'urn:mace:dir:attribute-def:sn' => false,
    'urn:oid:1.2.840.113549.1.9.1.1' => false, // Wrong email
    'urn:oid:0.9.2342.19200300.100.1.10' => false, // manager
    'urn:oid:2.16.840.1.113730.3.1.4' => false, // employeeType
    'urn:oid:1.3.6.1.4.1.5923.1.1.1.13' => false, // eduPersonUniqueId
    'urn:oid:0.9.2342.19200300.100.1.1' => false, // uid
    'urn:oid:1.2.752.29.4.13' => false, // personalIdentityNumber
    'urn:oid:0.9.2342.19200300.100.1.43' => false, // co
    'urn:oid:1.3.6.1.4.1.25178.1.2.3' => false, // schacDateOfBirth
    'urn:oid:2.16.840.1.113730.3.1.13' => false, // mailLocalAddress
    'urn:oid:2.5.4.6' => false, // c
  );

  public function getAttributeFriendlyNames() {
    return array_merge(array_diff_key(self::FRIENDLY_NAMES, self::FRIENDLY_NAMES_EXCLUDE), self::FRIENDLY_NAMES_TUAKIRI);
  }
}

