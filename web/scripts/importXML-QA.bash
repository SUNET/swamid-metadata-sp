#!/bin/bash
file=$1
if [ -r $file ]; then
	feed=""
	fileName=$(basename $file)
	SWAMIDDir=$(dirname $file)
	cd $SWAMIDDir
	date=$(ls -l -D '%Y-%m-%d %H:%M:%S' $file | awk '{print $6,$7}')
	feed="Testing Swamid"

	php /var/www/scripts/importAndValidateXML.php $file "$feed" "$date"
else
	echo "Cant read $file"
fi
