#!/bin/bash
file=$1
if [ -r $file ]; then
	feed=""
	fileName=$(basename $file)
	SWAMIDDir=$(dirname $file)
	if (grep -q "/$fileName" $SWAMIDDir/../swamid-testing-idp-1.0.mxml $SWAMIDDir/../swamid-testing-sp-1.0.mxml); then
		feed="Testing"
	fi
	if (grep -q "/$fileName" $SWAMIDDir/../swamid-idp-2.0.mxml $SWAMIDDir/../swamid-sp-2.0.mxml); then
		feed="Testing Swamid"
	fi
	if (grep -q "/$fileName" $SWAMIDDir/../swamid-edugain-idp-1.0.mxml $SWAMIDDir/../swamid-edugain-sp-1.0.mxml); then
		feed="Testing Swamid Edugain"
	fi

	php /var/www/scripts/importAndValidateXML.php $file "$feed"
else
	echo "Cant read $file"
fi
