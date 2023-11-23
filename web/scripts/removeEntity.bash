#!/bin/bash
file=$1
if [ -r $file ]; then
  feed=""
  fileName=$(basename $file)
  SWAMIDDir=$(dirname $file)
  entityID=$(head -1 $file)
  echo "Removing $entityID"
  php /var/www/scripts/removeEntity.php $entityID Prod
  rm $file
else
  echo "Cant read $file"
fi
