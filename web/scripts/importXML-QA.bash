#!/bin/bash
file=$1
if [ -r $file ]; then
  SWAMIDDir=$(dirname $file)
  cd $SWAMIDDir
  feed="Testing Swamid"

  php /var/www/scripts/importAndValidateXML.php $file "$feed"
else
  echo "Cant read $file"
fi
