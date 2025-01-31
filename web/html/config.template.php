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
  'displayNameQA' => 'SWAMID QA',
  'name' => 'swamid',
  'aboutURL' => 'https://www.sunet.se/swamid/',
  'contactURL' => 'https://www.sunet.se/swamid/kontakt/',
  'logoURL' => '/swamid-logo-2-100x115.png',
  'logoWidth' => 55,
  'logoHeight' => 63,
);

# Optional if you want to support IMPS:es
#$imps = array(
#  'oldDate' => '2020-12-31', # If older then this date. IMPS needs to be updated!
  # Number of months since last validation before sending
#  'warn1' => '10', # 1:st warning
#  'warn2' => '11', # 2:nd warning
#  'error' => '12' # last warning
#);
