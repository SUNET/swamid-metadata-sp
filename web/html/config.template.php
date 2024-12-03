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
$mode = 'Lab';
$baseURL = 'https://metadata.host.se/';
