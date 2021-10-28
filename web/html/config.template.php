<?php
$dbFile = "/var/www/db/xml-lab.db";
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
