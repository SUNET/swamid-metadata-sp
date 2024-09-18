#!/bin/bash
sed -i '' "s@schemaLocation='http://docs.oasis-open.org/security/saml/v2.0/saml-schema-metadata-2.0.xsd' />@schemaLocation='saml-schema-metadata-2.0.xsd' />@" *.xsd

sed -i '' "s@schemaLocation='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd' />@schemaLocation='oasis-200401-wss-wssecurity-secext-1.0.xsd' />@" *.xsd

sed -i '' 's@schemaLocation="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"/>@schemaLocation="oasis-200401-wss-wssecurity-utility-1.0.xsd"/>@' *.xsd
sed -i '' "s@schemaLocation='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd' />@schemaLocation='oasis-200401-wss-wssecurity-utility-1.0.xsd' />@" *.xsd

sed -i '' "s@schemaLocation='http://docs.oasis-open.org/ws-sx/ws-securitypolicy/200702/ws-securitypolicy-1.2.xsd'/>@schemaLocation='ws-securitypolicy-1.2.xsd'/>@" *.xsd

sed -i '' "s@schemaLocation='http://schemas.xmlsoap.org/ws/2004/09/mex/MetadataExchange.xsd' />@schemaLocation='MetadataExchange.xsd' />@" *.xsd

sed -i '' 's@schemaLocation="http://www.w3.org/2001/xml.xsd"/>@schemaLocation="xml.xsd"/>@' *.xsd

sed -i '' "s@schemaLocation='http://www.w3.org/2006/03/addressing/ws-addr.xsd' />@schemaLocation='ws-addr.xsd' />@" *.xsd
sed -i '' 's@schemaLocation="http://www.w3.org/2006/03/addressing/ws-addr.xsd" />@schemaLocation="ws-addr.xsd" />@' *.xsd

sed -i '' 's@schemaLocation="http://www.w3.org/TR/2002/REC-xmldsig-core-20020212/xmldsig-core-schema.xsd"/>@schemaLocation="xmldsig-core-schema.xsd"/>@' *.xsd
sed -i '' 's@schemaLocation="http://www.w3.org/TR/xmldsig-core/xmldsig-core-schema.xsd"/>@schemaLocation="xmldsig-core-schema.xsd"/>@' *.xsd
sed -i '' "s@schemaLocation='http://www.w3.org/TR/2002/REC-xmldsig-core-20020212/xmldsig-core-schema.xsd'/>@schemaLocation='xmldsig-core-schema.xsd'/>@" *.xsd

sed -i '' 's@schemaLocation="http://www.w3.org/TR/2002/REC-xmlenc-core-20021210/xenc-schema.xsd"/>@schemaLocation="xenc-schema.xsd"/>@' *.xsd
sed -i '' "s@schemaLocation='http://www.w3.org/TR/2002/REC-xmlenc-core-20021210/xenc-schema.xsd'/>@schemaLocation='xenc-schema.xsd'/>@" *.xsd