#!/bin/bash
file=$1
if [ -r $file ]; then
	feed=""
	fileName=$(basename $file)
	SWAMIDDir=$(dirname $file)
	cd $SWAMIDDir
	date=$(/usr/bin/git log -n 1 --pretty=format:"%ad" --date=format-local:'%Y-%m-%d %H:%M:%S' $file)
	feed="Testing Swamid"

	php /var/www/scripts/importAndValidateXML.php $file "$feed" "$date"
else
	echo "Cant read $file"
fi
