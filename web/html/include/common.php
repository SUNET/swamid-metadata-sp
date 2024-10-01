<?php
$this->standardAttributes = array(
  'assurance-certification' => array(
    array('type' => 'IdP',    'value' => 'http://www.swamid.se/policy/assurance/al1', 'swamidStd' => true), # NOSONAR Should be http://
    array('type' => 'IdP',    'value' => 'http://www.swamid.se/policy/assurance/al2', 'swamidStd' => true), # NOSONAR Should be http://
    array('type' => 'IdP',    'value' => 'http://www.swamid.se/policy/assurance/al3', 'swamidStd' => true), # NOSONAR Should be http://
    array('type' => 'IdP/SP', 'value' => 'https://refeds.org/sirtfi', 'swamidStd' => true),
    array('type' => 'IdP/SP', 'value' => 'https://refeds.org/sirtfi2', 'swamidStd' => true)),
  'entity-category' => array(
    array('type' => 'SP',  'value' => 'http://refeds.org/category/research-and-scholarship', 'swamidStd' => true), # NOSONAR Should be http://
    array('type' => 'SP',  'value' => 'https://refeds.org/category/anonymous', 'swamidStd' => true),
    array('type' => 'SP',  'value' => 'https://refeds.org/category/pseudonymous', 'swamidStd' => true),
    array('type' => 'SP',  'value' => 'https://refeds.org/category/personalized', 'swamidStd' => true),
    array('type' => 'SP',  'value' => 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1', # NOSONAR Should be http://
      'swamidStd' => true),
    array('type' => 'SP',  'value' => 'https://refeds.org/category/code-of-conduct/v2', 'swamidStd' => true),
    array('type' => 'SP',  'value' => 'https://myacademicid.org/entity-categories/esi', 'swamidStd' => true),
    array('type' => 'IdP', 'value' => 'http://refeds.org/category/hide-from-discovery', 'swamidStd' => true)), # NOSONAR Should be http://

  'entity-category-support' => array(
    array('type' => 'IdP', 'value' => 'https://refeds.org/category/anonymous', 'swamidStd' => true),
    array('type' => 'IdP', 'value' => 'https://refeds.org/category/pseudonymous', 'swamidStd' => true),
    array('type' => 'IdP', 'value' => 'https://refeds.org/category/personalized', 'swamidStd' => true),
    array('type' => 'IdP', 'value' => 'http://refeds.org/category/research-and-scholarship', 'swamidStd' => true), # NOSONAR Should be http://
    array('type' => 'IdP', 'value' => 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1', # NOSONAR Should be http://
      'swamidStd' => true),
    array('type' => 'IdP', 'value' => 'https://myacademicid.org/entity-categories/esi', 'swamidStd' => true),
    array('type' => 'IdP', 'value' => 'https://refeds.org/category/code-of-conduct/v2', 'swamidStd' => true)),
  'subject-id:req' => array(
    array('type' => 'SP', 'value' => 'subject-id', 'swamidStd' => true),
    array('type' => 'SP', 'value' => 'pairwise-id', 'swamidStd' => true),
    array('type' => 'SP', 'value' => 'none', 'swamidStd' => true),
    array('type' => 'SP', 'value' => 'any', 'swamidStd' => true))
);
$this->FriendlyNames = array(
  'urn:oid:2.5.4.6'                    => array('desc' => 'c', 'swamidStd' => true),
  'urn:oid:2.5.4.3'                    => array('desc' => 'cn', 'swamidStd' => true),
  'urn:oid:0.9.2342.19200300.100.1.43' => array('desc' => 'co', 'swamidStd' => true),
  'urn:oid:2.16.840.1.113730.3.1.241'  => array('desc' => 'displayName', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.5923.1.1.1.1'   => array('desc' => 'eduPersonAffiliation', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.5923.1.1.1.11'  => array('desc' => 'eduPersonAssurance', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.5923.1.1.1.7'   => array('desc' => 'eduPersonEntitlement', 'swamidStd' => false),
  'urn:oid:1.3.6.1.4.1.5923.1.1.1.16'  => array('desc' => 'eduPersonOrcid', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.5923.1.1.1.5'   => array('desc' => 'eduPersonPrimaryAffiliation', 'swamidStd' => false),
  'urn:oid:1.3.6.1.4.1.5923.1.1.1.6'   => array('desc' => 'eduPersonPrincipalName', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.5923.1.1.1.9'   => array('desc' => 'eduPersonScopedAffiliation', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.5923.1.1.1.10'  => array('desc' => 'eduPersonTargetedID', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.5923.1.1.1.13'  => array('desc' => 'eduPersonUniqueId', 'swamidStd' => true),
  'urn:oid:2.16.840.1.113730.3.1.4'    => array('desc' => 'employeeType', 'swamidStd' => false),
  'urn:oid:2.16.840.1.113730.3.1.13'   => array('desc' => 'mailLocalAddress', 'swamidStd' => true),
  'urn:oid:2.5.4.42'                   => array('desc' => 'givenName', 'swamidStd' => true),
  'urn:oid:0.9.2342.19200300.100.1.10' => array('desc' => 'manager', 'swamidStd' => false),
  'urn:oid:0.9.2342.19200300.100.1.3'  => array('desc' => 'mail', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.2428.90.1.6'    => array('desc' => 'norEduOrgAcronym', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.2428.90.1.10'   => array('desc' => 'norEduPersonLegalName', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.2428.90.1.5'    => array('desc' => 'norEduPersonNIN', 'swamidStd' => true),
  'urn:oid:2.5.4.10'                   => array('desc' => 'o', 'swamidStd' => true),
  'urn:oid:1.2.752.29.4.13'            => array('desc' => 'personalIdentityNumber', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.25178.1.2.3'    => array('desc' => 'schacDateOfBirth', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.25178.1.2.9'    => array('desc' => 'schacHomeOrganization', 'swamidStd' => true),
  'urn:oid:1.3.6.1.4.1.25178.1.2.10'   => array('desc' => 'schacHomeOrganizationType', 'swamidStd' => true),
  'urn:oid:2.5.4.4'                    => array('desc' => 'sn', 'swamidStd' => true),
  'urn:oid:0.9.2342.19200300.100.1.1'  => array('desc' => 'uid', 'swamidStd' => false),

  'urn:mace:dir:attribute-def:cn'      => array('desc' => 'cn', 'swamidStd' => false),
  'urn:mace:dir:attribute-def:displayName' => array('desc' => 'displayName', 'swamidStd' => false),
  'urn:mace:dir:attribute-def:eduPersonPrincipalName' => array(
    'desc' => 'eduPersonPrincipalName', 'swamidStd' => false),
  'urn:mace:dir:attribute-def:eduPersonScopedAffiliation' =>
    array('desc' => 'eduPersonScopedAffiliation', 'swamidStd' => false),
  'urn:mace:dir:attribute-def:eduPersonTargetedID' => array('desc' => 'eduPersonTargetedID', 'swamidStd' => false),
  'urn:mace:dir:attribute-def:givenName' => array('desc' => 'givenName', 'swamidStd' => false),
  'urn:mace:dir:attribute-def:mail'    => array('desc' => 'mail', 'swamidStd' => false),
  'urn:mace:dir:attribute-def:sn'      => array('desc' => 'sn', 'swamidStd' => false),

  'urn:oid:1.2.840.113549.1.9.1.1'     => array('desc' => 'Wrong - email', 'swamidStd' => false)
);
$this->langCodes = array(
  'en'  =>  'English',
  'sv'  =>  'Swedish',
  'da'  =>  'Danish',
  'no'  =>  'Norwegian',
  'fi'  =>  'Finnish',
  'is'  =>  'Icelandic',
  'de'  =>  'German',
  'fr'  =>  'French',
  'es'  =>  'Spanish',
  'se'  =>  'Northern Sami',
  'nb'  =>  'Bokmål, Norwegian',
  'nn'  =>  'Nynorsk, Norwegian',
  'ab'  =>  'Abkhazian',
  'aa'  =>  'Afar',
  'af'  =>  'Afrikaans',
  'ak'  =>  'Akan',
  'sq'  =>  'Albanian',
  'am'  =>  'Amharic',
  'ar'  =>  'Arabic',
  'an'  =>  'Aragonese',
  'hy'  =>  'Armenian',
  'as'  =>  'Assamese',
  'av'  =>  'Avaric',
  'ae'  =>  'Avestan',
  'ay'  =>  'Aymara',
  'az'  =>  'Azerbaijani',
  'bm'  =>  'Bambara',
  'ba'  =>  'Bashkir',
  'eu'  =>  'Basque',
  'be'  =>  'Belarusian',
  'bn'  =>  'Bengali',
  'bh'  =>  'Bihari languages',
  'bi'  =>  'Bislama',
  'bs'  =>  'Bosnian',
  'br'  =>  'Breton',
  'bg'  =>  'Bulgarian',
  'my'  =>  'Burmese',
  'ca'  =>  'Catalan',
  'km'  =>  'Central Khmer',
  'ch'  =>  'Chamorro',
  'ce'  =>  'Chechen',
  'zh'  =>  'Chinese',
  'za'  =>  'Chuang',
  'cv'  =>  'Chuvash',
  'kw'  =>  'Cornish',
  'co'  =>  'Corsican',
  'cr'  =>  'Cree',
  'hr'  =>  'Croatian',
  'cs'  =>  'Czech',
  'nl'  =>  'Dutch',
  'dz'  =>  'Dzongkha',
  'eo'  =>  'Esperanto',
  'et'  =>  'Estonian',
  'ee'  =>  'Ewe',
  'fo'  =>  'Faroese',
  'fj'  =>  'Fijian',
  'ff'  =>  'Fulah',
  'gl'  =>  'Galician',
  'lg'  =>  'Ganda',
  'ka'  =>  'Georgian',
  'ki'  =>  'Gikuyu',
  'el'  =>  'Greek, Modern (1453-)',
  'kl'  =>  'Greenlandic',
  'gn'  =>  'Guarani',
  'gu'  =>  'Gujarati',
  'ht'  =>  'Haitian Creole',
  'ha'  =>  'Hausa',
  'he'  =>  'Hebrew',
  'hz'  =>  'Herero',
  'hi'  =>  'Hindi',
  'ho'  =>  'Hiri Motu',
  'hu'  =>  'Hungarian',
  'io'  =>  'Ido',
  'ig'  =>  'Igbo',
  'id'  =>  'Indonesian',
  'ia'  =>  'Interlingua (IALA)',
  'ie'  =>  'Interlingue, Occidental',
  'iu'  =>  'Inuktitut',
  'ik'  =>  'Inupiaq',
  'ga'  =>  'Irish',
  'it'  =>  'Italian',
  'ja'  =>  'Japanese',
  'jv'  =>  'Javanese',
  'kn'  =>  'Kannada',
  'kr'  =>  'Kanuri',
  'ks'  =>  'Kashmiri',
  'kk'  =>  'Kazakh',
  'rw'  =>  'Kinyarwanda',
  'kv'  =>  'Komi',
  'kg'  =>  'Kongo',
  'ko'  =>  'Korean',
  'ku'  =>  'Kurdish',
  'kj'  =>  'Kwanyama',
  'ky'  =>  'Kyrgyz',
  'lo'  =>  'Lao',
  'la'  =>  'Latin',
  'lv'  =>  'Latvian',
  'li'  =>  'Limburgish',
  'ln'  =>  'Lingala',
  'lt'  =>  'Lithuanian',
  'lu'  =>  'Luba-Katanga',
  'lb'  =>  'Luxembourgish',
  'mk'  =>  'Macedonian',
  'mg'  =>  'Malagasy',
  'ms'  =>  'Malay',
  'ml'  =>  'Malayalam',
  'dv'  =>  'Maldivian',
  'mt'  =>  'Maltese',
  'gv'  =>  'Manx',
  'mi'  =>  'Maori',
  'mr'  =>  'Marathi',
  'mh'  =>  'Marshallese',
  'ro'  =>  'Moldovan',
  'mn'  =>  'Mongolian',
  'na'  =>  'Nauru',
  'nv'  =>  'Navaho',
  'nd'  =>  'Ndebele, North',
  'nr'  =>  'Ndebele, South',
  'ng'  =>  'Ndonga',
  'ne'  =>  'Nepali',
  'ii'  =>  'Nuosu',
  'ny'  =>  'Nyanja',
  'oj'  =>  'Ojibwa',
  'cu'  =>  'Old Church Slavonic',
  'or'  =>  'Oriya',
  'om'  =>  'Oromo',
  'os'  =>  'Ossetic',
  'pi'  =>  'Pali',
  'pa'  =>  'Panjabi',
  'fa'  =>  'Persian',
  'pl'  =>  'Polish',
  'pt'  =>  'Portuguese',
  'oc'  =>  'Provençal',
  'ps'  =>  'Pushto',
  'qu'  =>  'Quechua',
  'rm'  =>  'Romansh',
  'rn'  =>  'Rundi',
  'ru'  =>  'Russian',
  'sm'  =>  'Samoan',
  'sg'  =>  'Sango',
  'sa'  =>  'Sanskrit',
  'sc'  =>  'Sardinian',
  'gd'  =>  'Scottish Gaelic',
  'sr'  =>  'Serbian',
  'sn'  =>  'Shona',
  'sd'  =>  'Sindhi',
  'si'  =>  'Sinhalese',
  'sk'  =>  'Slovak',
  'sl'  =>  'Slovenian',
  'so'  =>  'Somali',
  'st'  =>  'Sotho, Southern',
  'su'  =>  'Sundanese',
  'sw'  =>  'Swahili',
  'ss'  =>  'Swati',
  'tl'  =>  'Tagalog',
  'ty'  =>  'Tahitian',
  'tg'  =>  'Tajik',
  'ta'  =>  'Tamil',
  'tt'  =>  'Tatar',
  'te'  =>  'Telugu',
  'th'  =>  'Thai',
  'bo'  =>  'Tibetan',
  'ti'  =>  'Tigrinya',
  'to'  =>  'Tonga (Tonga Islands)',
  'ts'  =>  'Tsonga',
  'tn'  =>  'Tswana',
  'tr'  =>  'Turkish',
  'tk'  =>  'Turkmen',
  'tw'  =>  'Twi',
  'uk'  =>  'Ukrainian',
  'ur'  =>  'Urdu',
  'ug'  =>  'Uyghur',
  'uz'  =>  'Uzbek',
  've'  =>  'Venda',
  'vi'  =>  'Vietnamese',
  'vo'  =>  'Volapük',
  'wa'  =>  'Walloon',
  'cy'  =>  'Welsh',
  'fy'  =>  'Western Frisian',
  'wo'  =>  'Wolof',
  'xh'  =>  'Xhosa',
  'yi'  =>  'Yiddish',
  'yo'  =>  'Yoruba',
  'zu'  =>  'Zulu',
);

$this->digestMethods  = array(
  # https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-algsupport-v1.0-cs01.html
  # The <alg:DigestMethod> element describes a Message Digest algorithm.
  # 6.2 Message Digests
  # https://www.w3.org/TR/xmldsig-core/#sec-MessageDigests
  # 6.2.1 SHA-1
  'http://www.w3.org/2000/09/xmldsig#sha1' => 'discouraged',
  # 6.2.2 SHA-224
  'http://www.w3.org/2001/04/xmldsig-more#sha224' => 'good',
  # 6.2.3 SHA-256
  'http://www.w3.org/2001/04/xmlenc#sha256' => 'good',
  # 6.2.4 SHA-384
  'http://www.w3.org/2001/04/xmldsig-more#sha384' => 'good',
  # 6.2.5 SHA-512
  'http://www.w3.org/2001/04/xmlenc#sha512' => 'good',
  # RFC 9231 Additional XML Security Uniform Resource Identifiers (URIs)
  # https://www.rfc-editor.org/rfc/rfc9231.html#section-2.1
  # 2.1.1. MD5
  'http://www.w3.org/2001/04/xmldsig-more#md5' => 'obsolete',
  # 2.1.2. SHA-224
  'http://www.w3.org/2001/04/xmldsig-more#sha224' => 'good',
  # 2.1.3. SHA-384
  'http://www.w3.org/2001/04/xmldsig-more#sha384' => 'good',
  # 2.1.4. Whirlpool
  'http://www.w3.org/2007/05/xmldsig-more#whirlpool' => 'good',
  # 2.1.5. SHA-3 Algorithms
  'http://www.w3.org/2007/05/xmldsig-more#sha3-224' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha3-256' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha3-384' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha3-512' => 'good',
  # https://www.w3.org/TR/xmlenc-core1/#sec-Alg-MessageDigest
  # Message Digest
  'http://www.w3.org/2000/09/xmldsig#sha1' => 'discouraged',
  'http://www.w3.org/2001/04/xmlenc#sha256' => 'good',
  'http://www.w3.org/2001/04/xmlenc#sha384' => 'good',
  'http://www.w3.org/2001/04/xmlenc#sha512' => 'good',
  'http://www.w3.org/2001/04/xmlenc#ripemd160' => 'good',
);
$this->signingMethods = array(
  # https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-algsupport-v1.0-cs01.html
  # The <alg:SigningMethod> element describes a Signature or Message Authentication Code algorithm.
  # 6.3 Message Authentication Code
  # https://www.w3.org/TR/xmldsig-core/#sec-MACs
  'http://www.w3.org/2000/09/xmldsig#hmac-sha1' => 'discouraged',
  'http://www.w3.org/2001/04/xmldsig-more#hmac-sha224' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#hmac-sha256' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#hmac-sha384' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#hmac-sha512' => 'good',
  # 6.4 Signature Algorithms
  # https://www.w3.org/TR/xmldsig-core/#sec-SignatureAlg
  # 6.4.1 DSA
  'http://www.w3.org/2000/09/xmldsig#dsa-sha1' => 'good',
  'http://www.w3.org/2009/xmldsig11#dsa-sha256' => 'good',
  # 6.4.2 RSA (PKCS#1 v1.5)
  'http://www.w3.org/2000/09/xmldsig#rsa-sha1' => 'discouraged',
  'http://www.w3.org/2001/04/xmldsig-more#rsa-sha224' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512' => 'good',
  # 6.4.3 ECDSA
  'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha1' => 'discouraged',
  'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha224' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha384' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha512' => 'good',
  # Obsolete
  'http://www.w3.org/2001/04/xmldsig-more#rsa-md5' => 'obsolete',
  # RFC 9231 Additional XML Security Uniform Resource Identifiers (URIs)
  # https://www.rfc-editor.org/rfc/rfc9231.html#section-2.2
  # 2.2.1 HMAC-MD5
  'http://www.w3.org/2001/04/xmldsig-more#hmac-md5' => 'obsolete',
  # 2.2.2. HMAC SHA Variations
  'http://www.w3.org/2001/04/xmldsig-more#hmac-sha224' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#hmac-sha256' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#hmac-sha384' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#hmac-sha512' => 'good',
  # 2.2.3. HMAC-RIPEMD160
  'http://www.w3.org/2001/04/xmldsig-more#hmac-ripemd160' => 'good',
  # 2.2.4. Poly1305
  'http://www.w3.org/2021/04/xmldsig-more#poly1305' => 'good',
  # 2.2.5. SipHash-2-4
  'http://www.w3.org/2021/04/xmldsig-more#siphash-2-4' => 'good',
  # 2.2.6. XMSS and XMSSMT
  'http://www.w3.org/2021/04/xmldsig-more#xmss-sha2-10-192' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-sha2-10-256' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-sha2-10-512' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-sha2-16-192' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-sha2-16-256' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-sha2-16-512' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-sha2-20-192' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-sha2-20-256' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-sha2-20-512' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake-10-256' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake-10-512' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake-16-256' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake-16-512' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake-20-256' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake-20-512' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake256-10-192' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake256-10-256' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake256-16-192' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake256-16-256' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake256-20-192' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#xmss-shake256-20-256' => 'good',
  # https://www.rfc-editor.org/rfc/rfc9231.html#section-2.3
  # 2.3.1. RSA-MD5
  'http://www.w3.org/2001/04/xmldsig-more#rsa-md5' => 'obsolete',
  # 2.3.2. RSA-SHA256
  'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256' => 'good',
  # 2.3.3. RSA-SHA384
  'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384' => 'good',
  # 2.3.4. RSA-SHA512
  'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512' => 'good',
  # 2.3.5. RSA-RIPEMD160
  'http://www.w3.org/2001/04/xmldsig-more#rsa-ripemd160' => 'good',
  # 2.3.6. ECDSA-SHA*, ECDSA-RIPEMD160, ECDSA-Whirlpool
  'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha1' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha224' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha384' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha512' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#ecdsa-sha3-224' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#ecdsa-sha3-256' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#ecdsa-sha3-384' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#ecdsa-sha3-512' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#ecdsa-ripemd160' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#ecdsa-whirlpool' => 'good',
  # 2.3.7. ESIGN-SHA*
  'http://www.w3.org/2001/04/xmldsig-more#esign-sha1' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#esign-sha224' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#esign-sha256' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#esign-sha384' => 'good',
  'http://www.w3.org/2001/04/xmldsig-more#esign-sha512' => 'good',
  # 2.3.8. RSA-Whirlpool
  'http://www.w3.org/2007/05/xmldsig-more#rsa-whirlpool' => 'good',
  # 2.3.9. RSASSA-PSS with Parameters
  'http://www.w3.org/2007/05/xmldsig-more#rsa-pss' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#MGF1' => 'good',
  # 2.3.10. RSASSA-PSS without Parameters
  'http://www.w3.org/2007/05/xmldsig-more#sha3-224-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha3-256-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha3-384-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha3-512-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#md2-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#md5-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha1-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha224-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha256-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha384-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#sha512-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#ripemd128-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#ripemd160-rsa-MGF1' => 'good',
  'http://www.w3.org/2007/05/xmldsig-more#whirlpool-rsa-MGF1' => 'good',
  # 2.3.11. RSA-SHA224
  'http://www.w3.org/2001/04/xmldsig-more#rsa-sha224' => 'good',
  # 2.3.12. Edwards-Curve
  'http://www.w3.org/2021/04/xmldsig-more#eddsa-ed25519ph' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#eddsa-ed25519ctx' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#eddsa-ed25519' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#eddsa-ed448' => 'good',
  'http://www.w3.org/2021/04/xmldsig-more#eddsa-ed448ph' => 'good',
);
$this->encryptionMethods = array(
  # https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-algsupport-v1.0-cs01.html#__RefHeading__5803_234507477
  # Per [XMLEnc], the <md:EncryptionMethod> element MUST contain an Algorithm attribute containing the identifier for the algorithm defined for use with the specification
  # 5.1.1 Table of Algorithms
  # https://www.w3.org/TR/xmlenc-core1/#sec-Table-of-Algorithms
  # Block Encryption
  #'http://www.w3.org/2001/04/xmlenc#tripledes-cbc' => 'discouraged',
  #'http://www.w3.org/2001/04/xmlenc#aes128-cbc' => 'discouraged',
  #'http://www.w3.org/2001/04/xmlenc#aes256-cbc' => 'discouraged',
  'http://www.w3.org/2001/04/xmlenc#tripledes-cbc' => 'good',
  'http://www.w3.org/2001/04/xmlenc#aes128-cbc' => 'good',
  'http://www.w3.org/2001/04/xmlenc#aes256-cbc' => 'good',
  'http://www.w3.org/2009/xmlenc11#aes128-gcm' => 'good',
  #'http://www.w3.org/2001/04/xmlenc#aes192-cbc' => 'discouraged',
  'http://www.w3.org/2001/04/xmlenc#aes192-cbc' => 'good',
  'http://www.w3.org/2009/xmlenc11#aes192-gcm' => 'good',
  'http://www.w3.org/2009/xmlenc11#aes256-gcm' => 'good',
  # Key Derivation
  'http://www.w3.org/2009/xmlenc11#ConcatKDF' => 'good',
  'http://www.w3.org/2009/xmlenc11#pbkdf2' => 'good',
  # Key Transport
  'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p' => 'good',
  'http://www.w3.org/2009/xmlenc11#rsa-oaep' => 'good',
  # 5.5.1
  'http://www.w3.org/2001/04/xmlenc#rsa-1_5' => 'obsolete',
  # Key Agreement
  'http://www.w3.org/2009/xmlenc11#ECDH-ES' => 'good',
  'http://www.w3.org/2001/04/xmlenc#dh' => 'good',
  'http://www.w3.org/2009/xmlenc11#dh-es' => 'good',
  # Symmetric Key Wrap
  'http://www.w3.org/2001/04/xmlenc#kw-tripledes' => 'good',
  'http://www.w3.org/2001/04/xmlenc#kw-aes128' => 'good',
  'http://www.w3.org/2001/04/xmlenc#kw-aes256' => 'good',
  'http://www.w3.org/2001/04/xmlenc#kw-aes192' => 'good',
  # Message Digest
  'http://www.w3.org/2000/09/xmldsig#sha1' => 'discouraged',
  'http://www.w3.org/2001/04/xmlenc#sha256' => 'good',
  'http://www.w3.org/2001/04/xmlenc#sha384' => 'good',
  'http://www.w3.org/2001/04/xmlenc#sha512' => 'good',
  'http://www.w3.org/2001/04/xmlenc#ripemd160' => 'good',
  # Canonicalization
  'http://www.w3.org/TR/2001/REC-xml-c14n-20010315' => 'good',
  'http://www.w3.org/TR/2001/REC-xml-c14n-20010315#WithComments' => 'good',
  'http://www.w3.org/2006/12/xml-c14n11' => 'good',
  'http://www.w3.org/2006/12/xml-c14n11#WithComments' => 'good',
  'http://www.w3.org/2001/10/xml-exc-c14n#' => 'good',
  'http://www.w3.org/2001/10/xml-exc-c14n#WithComments' => 'good',
  # Encoding + Transforms
  'http://www.w3.org/2000/09/xmldsig#base64' => 'good',
);