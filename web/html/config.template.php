<?php
$db = array(
  'name'        => 'metadata',      # Name of Database
  'servername'  => 'mariadb',       # Name of DB server
  'username'    => 'metadata_admin', # Username for DB
  'password'    => 'adminpwd',       # Password for DB NOSONAR

  # optional parameter

  ###
  # The file path to the SSL certificate authority.
  # Activates PDO::MYSQL_ATTR_SSL_CA in options.
  ###
  # 'caPath' => '/etc/ssl/CA.pem',
);

$smtp = array(
  'host'        => 'smtp.host.se',        # SMTP host
  'from'        => 'metadata@host.se',    # Address to send from (will get bounces)
  'replyTo'     => 'operations@host.se',  # Address where any reply:s should go
  'replyName'   => 'Operations Host',     # Name in mail where any reply:s should go
  'sendOut'     => false,

  # optional parameters

  ###
  # if sasl is set PHPMailer will use SMTPAuth and default to port 587 for sending
  ###
  #'sasl'        => array(
  #  'user'      => 'SASL User',           #
  #  'password'  => 'SASL Password',       #
  #),

  ###
  # If bcc is set, a copy of outgoing mail will be sent to this address
  ###
  # 'bcc'         => 'admin@host.se',      #
);

# Optional if you want to support https://refeds.org/entity-selection-profile for SeamlessAccess Idp filtering
#$entitySelectionProfiles = array(
#  'swamid-only'                 => array('desc' => 'Registered in SWAMID','base64' => 'eyJwcm9maWxlcyI6eyJzd2FtaWQtb25seSI6eyJzdHJpY3QiOnRydWUsImVudGl0aWVzIjpbeyJzZWxlY3QiOiJodHRwOi8vd3d3LnN3YW1pZC5zZS8iLCJtYXRjaCI6InJlZ2lzdHJhdGlvbkF1dGhvcml0eSIsImluY2x1ZGUiOnRydWV9XX19fQo='),
#  'swamid-edugain'              => array('desc' => 'Registered in SWAMID or imported from eduGAIN','base64' => 'eyJwcm9maWxlcyI6InN3YW1pZC1lZHVnYWluIjp7InN0cmljdCI6dHJ1ZSwiZW50aXRpZXMiOlt7InNlbGVjdCI6ImZpbGU6Ly8vb3B0L3B5ZmYvbWV0YWRhdGEvb3BlbmF0aGVucy54bWwiLCJtYXRjaCI6Im1kX3NvdXJjZSIsImluY2x1ZGUiOmZhbHNlfV19fX0K')
#);

$mode = 'Lab';
$baseURL = 'https://metadata.host.se/';

$userLevels = array(
  'adminuser1@federation.org' => 20,
  'adminuser2@federation.org' => 20,
  'user1@inst1.org' => 10,
  'user1@inst2.org' => 5,
);

$federation = array(
  'displayName' => 'SWAMID',
  'longName' => 'Academic Identity Federation SWAMID',
  'displayNameQA' => 'SWAMID QA',
  'noAccountHtml' => 'you can create an eduID account at <a href="https://eduid.se">eduID.se</a>',
  'name' => 'swamid',
  'localFeed' => 'swamid-2.0',
  'eduGAINFeed' => 'swamid-edugain',
  'aboutURL' => 'https://www.sunet.se/swamid/',
  'contactURL' => 'https://www.sunet.se/swamid/kontakt/',
  'toolName' => 'SWAMID SAML WebSSO metadata administration tool',
  'teamName' => 'SWAMID Operations',
  'teamMail' => 'operations@swamid.se',
  'logoURL' => '/swamid-logo-2-100x115.png',
  'logoWidth' => 55,
  'logoHeight' => 63,
  'languages' => array('sv', 'en'),

  'metadata_main_path' => '/opt/metadata/swamid-2.0.xml',
  'metadata_registration_authority_exclude' => array(
      'http://www.swamid.se/',     # NOSONAR Should be http://
      'http://www.swamid.se/loop', # NOSONAR Should be http://
  ),

  'rulesName' => 'SWAMID SAML WebSSO Technology Profile',
  'rulesURL' => 'https://www.swamid.se/policy/technology/saml-websso',
  'roloverDocURL' => 'https://wiki.sunet.se/display/SWAMID/Key+rollover',

  'rulesSectsBoth' => '4.1.1, 4.1.2, 4.2.1 and 4.2.2',
  'rulesSectsIdP' => '4.1.1 and 4.1.2',
  'rulesSectsSP' => '4.2.1 and 4.2.2',
  'rulesInfoBoth' => '<ul>
          <li>4.1.1 For an organisation to be eligible to register an Identity Provider in SWAMID metadata the organisation MUST be a member of the SWAMID Identity Federation.</li>
          <li>4.1.2 All Member Organisations MUST fulfil one or more of the SWAMID Identity Assurance Profiles to be eligible to have an Identity Provider registered in SWAMID metadata.</li>
          <li>4.2.1 A Relying Party is eligible for registration in SWAMID if they are:<ul>
            <li>a service owned by a Member Organisation;</li>
            <li>a service under contract with at least one Member Organisation;</li>
            <li>a government agency service used by at least one Member Organisation;</li>
            <li>a service that is operated at least in part for the purpose of supporting research and scholarship interaction, collaboration or management; or</li>
            <li>a service granted special approval by SWAMID Board of Trustees after recommendation by SWAMID Operations.</li>
          </ul></li>
          <li>4.2.2 For a Relying Party to be registered in SWAMID the Service Owner MUST accept the <a href="https://mds.swamid.se/md/swamid-tou-en.txt" target="_blank">SWAMID Metadata Terms of Access and Use</a>.</li>
        </ul>',
  'rulesInfoIdP' => '<ul>
          <li>4.1.1 For an organisation to be eligible to register an Identity Provider in SWAMID metadata the organisation MUST be a member of the SWAMID Identity Federation.</li>
          <li>4.1.2 All Member Organisations MUST fulfil one or more of the SWAMID Identity Assurance Profiles to be eligible to have an Identity Provider registered in SWAMID metadata.</li>
        </ul>',
  'rulesInfoSP' => '<ul>
          <li>4.2.1 A Relying Party is eligible for registration in SWAMID if they are:<ul>
            <li>a service owned by a Member Organisation;</li>
            <li>a service under contract with at least one Member Organisation;</li>
            <li>a government agency service used by at least one Member Organisation;</li>
            <li>a service that is operated at least in part for the purpose of supporting research and scholarship interaction, collaboration or management; or</li>
            <li>a service granted special approval by SWAMID Board of Trustees after recommendation by SWAMID Operations.</li>
          </ul></li>
          <li>4.2.2 For a Relying Party to be registered in SWAMID the Service Owner MUST accept the <a href="https://mds.swamid.se/md/swamid-tou-en.txt" target="_blank">SWAMID Metadata Terms of Access and Use</a>.</li>
        </ul>',

  'swamid_assurance' => true,
  # If we should check/force a organization connected to each entity
  'checkOrganization' => false,
  # URL to get release-check test results from - or empty string if not used
  'releaseCheckResultsURL' => 'https://release-check.swamid.se/metaDump.php',
  # Base URL for MDQ - or empty string if not available
  'mdqBaseURL' => 'https://mds.swamid.se/entities/',
  # Optional if you want to extend Validate and ParseXML with an extended version
  # See ValidateSWAMID and ParseXMLSWAMID for examples
  #'extend' => 'SWAMID',
);

# Optional if you want to support IMPS:es
#$imps = array(
#  'oldDate' => '2020-12-31', # If older then this date. IMPS needs to be updated!
  # Number of months since last validation before sending
#  'warn1' => '10', # 1:st warning
#  'warn2' => '11', # 2:nd warning
#  'error' => '12' # last warning
#);
