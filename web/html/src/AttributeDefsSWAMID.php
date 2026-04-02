<?php
namespace metadata;

/**
 * Class to hold (and allow extending) attribute definitions
 */
class AttributeDefsSWAMID extends AttributeDefs {
  /**
   * FRIENDLY_NAMES
   *
   */
  const FRIENDLY_NAMES_SWAMID = array(
    'urn:oid:1.3.6.1.4.1.2428.90.1.6' => array(
      'desc' => 'norEduOrgAcronym', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.2428.90.1.10' => array(
      'desc' => 'norEduPersonLegalName', 'standard' => true
    ),
    'urn:oid:1.3.6.1.4.1.2428.90.1.5' => array(
      'desc' => 'norEduPersonNIN', 'standard' => true
    ),
  );

  /**
   * Returns attribute definitions customised for Tuakiri
   *
   * @return array
   */

  public function getAttributeFriendlyNames() {
    return array_merge(self::FRIENDLY_NAMES, self::FRIENDLY_NAMES_SWAMID);
  }
}

