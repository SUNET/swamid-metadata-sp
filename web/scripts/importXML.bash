#!/bin/bash
file=$1
if [ -r $file ]; then
  feed=""
  dirName=$(dirname $file)
  SWAMIDDir=$(basename $dirName)
  if [ $SWAMIDDir = "swamid-testing" ]; then
    feed="Testing"
  fi
  if [ $SWAMIDDir = "swamid-2.0" ]; then
    feed="Testing swamid-2.0"
  fi
  if [ $SWAMIDDir = "swamid-edugain" ]; then
    feed="Testing swamid-2.0 swamid-edugain"
  fi

  php /var/www/scripts/importAndValidateXML.php $file "$feed"
else
  echo "Cant read $file"
fi
